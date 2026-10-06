<?php
declare(strict_types=1);
require_once __DIR__.'/../app/bootstrap.php';
require_login();$u=current_user();
if(!has_app_write_permission('docs',$u))json_response(['ok'=>false,'error'=>'Evrak silme yetkiniz yok.'],403);
if($_SERVER['REQUEST_METHOD']!=='DELETE')json_response(['ok'=>false,'error'=>'Desteklenmeyen yöntem.'],405);
require_csrf();$id=(int)($_GET['id']??0);if($id<=0)json_response(['ok'=>false,'error'=>'Geçersiz evrak kimliği.'],422);
try{
 $pdo=db();$pdo->beginTransaction();$row=$pdo->query('SELECT revision,state_json FROM app_state WHERE id=1 FOR UPDATE')->fetch();if(!$row)throw new RuntimeException('Uygulama verisi bulunamadı.');$s=json_decode($row['state_json'],true)?:[];$found=null;
 foreach(($s['documents']??[]) as $d)if((int)($d['id']??0)===$id){$found=$d;break;}if(!$found){$pdo->rollBack();json_response(['ok'=>false,'error'=>'Evrak bulunamadı.'],404);}
 if(array_key_exists('uid',$_GET)&&(string)($_GET['uid']??'')!==(string)($found['uid']??'')){$pdo->rollBack();json_response(['ok'=>false,'error'=>'Evrak kimliği değişti; listeyi yenileyin.'],409);}
 $s['documents']=array_values(array_filter($s['documents'],fn($d)=>(int)($d['id']??0)!==$id));
 $uid=(string)($found['uid']??$found['id']);$pdo->prepare('DELETE FROM commercial_documents WHERE document_uid=?')->execute([$uid]);
 $rev=(int)$row['revision']+1;$pdo->prepare('UPDATE app_state SET state_json=?,revision=?,updated_by=? WHERE id=1')->execute([json_encode($s,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR),$rev,(int)$u['id']]);save_module_states($pdo,$s,(int)$u['id'],['documents']);$pdo->commit();
 audit('commercial_document_delete','document',(string)$id,['no'=>$found['no']??'','requestId'=>$found['requestId']??null]);
 json_response(['ok'=>true,'state'=>filter_state_for_user($s,$u),'revision'=>$rev,'keyHashes'=>state_hashes_for_client($s,$u)]);
}catch(Throwable $e){if(isset($pdo)&&$pdo->inTransaction())$pdo->rollBack();json_response(['ok'=>false,'error'=>public_error($e,'Evrak silinemedi.')],422);}
