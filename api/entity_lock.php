<?php
declare(strict_types=1);
require_once __DIR__.'/../app/bootstrap.php';

$u=current_user();
if(!$u) json_response(['ok'=>false,'error'=>'Oturum gerekli'],401);
if(!can_write_state($u)) json_response(['ok'=>false,'error'=>'Bu kullanıcı kayıt kilidi oluşturamaz.'],403);

$in=json_decode(file_get_contents('php://input'),true)?:[];
$action=(string)($in['action']??'acquire');
$type=preg_replace('/[^a-z0-9_-]/i','',(string)($in['entity_type']??''));
$id=(string)($in['entity_id']??'');
if(!$type||$id==='') json_response(['ok'=>false,'error'=>'Varlık bilgisi gerekli'],422);

$permMap=['request'=>'request','quote'=>'request','order'=>'request','account'=>'finance','cash'=>'cash','document'=>'docs','settings'=>'settings'];
$perm=$permMap[$type]??'request';
if(!has_app_permission($perm,$u)) json_response(['ok'=>false,'error'=>'Bu kaydı düzenleme yetkiniz yok.'],403);

$pdo=db();
$pdo->exec('DELETE FROM entity_locks WHERE expires_at<NOW()');

if($action==='release'){
  $q=$pdo->prepare('DELETE FROM entity_locks WHERE entity_type=? AND entity_id=? AND user_id=?');
  $q->execute([$type,$id,$u['id']]);
  json_response(['ok'=>true]);
}
if($action==='renew'){
  $token=(string)($in['token']??'');
  $q=$pdo->prepare('UPDATE entity_locks SET expires_at=DATE_ADD(NOW(),INTERVAL 5 MINUTE) WHERE entity_type=? AND entity_id=? AND user_id=? AND token=?');
  $q->execute([$type,$id,$u['id'],$token]);
  if(!$q->rowCount()) json_response(['ok'=>false,'error'=>'Kayıt kilidi artık size ait değil.'],409);
  json_response(['ok'=>true,'token'=>$token]);
}

$token=bin2hex(random_bytes(16));
$q=$pdo->prepare('SELECT l.*,u.name user_name FROM entity_locks l JOIN users u ON u.id=l.user_id WHERE entity_type=? AND entity_id=?');
$q->execute([$type,$id]);$r=$q->fetch();
if($r && (int)$r['user_id']!==(int)$u['id']){
  json_response(['ok'=>false,'locked'=>true,'error'=>'Bu kayıt şu anda '.$r['user_name'].' tarafından düzenleniyor.','user'=>$r['user_name']],409);
}
$q=$pdo->prepare('INSERT INTO entity_locks(entity_type,entity_id,user_id,token,expires_at) VALUES(?,?,?,?,DATE_ADD(NOW(),INTERVAL 5 MINUTE)) ON DUPLICATE KEY UPDATE user_id=VALUES(user_id),token=VALUES(token),expires_at=VALUES(expires_at)');
$q->execute([$type,$id,$u['id'],$token]);
audit('entity_lock_acquire',$type,$id,['token'=>$token]);
json_response(['ok'=>true,'token'=>$token]);
