<?php
declare(strict_types=1);
require_once __DIR__.'/../app/bootstrap.php';
if(empty($_SESSION['user_id'])) json_response(['ok'=>false,'error'=>'Oturum gerekli'],401);
require_admin();
function valid_role_name(string $role): bool {
    if(strcasecmp($role,'Admin')===0)return true;
    try{
        $raw=db()->query('SELECT state_json FROM app_state WHERE id=1')->fetchColumn();
        $st=$raw?json_decode((string)$raw,true):[];
        foreach(($st['roles']??[]) as $r)if((string)($r['name']??'')===$role)return true;
    }catch(Throwable $e){}
    return $role==='Görüntüleyici';
}
function active_admin_count(): int {
    return (int)db()->query("SELECT COUNT(*) FROM users WHERE LOWER(TRIM(role))='admin' AND status='active'")->fetchColumn();
}

try{
 if($_SERVER['REQUEST_METHOD']==='GET'){
  $rows=db()->query("SELECT id,name,email,role,department,note,photo_data AS photo,status FROM users ORDER BY id")->fetchAll();
  json_response(['ok'=>true,'users'=>$rows]);
 }
 if($_SERVER['REQUEST_METHOD']==='POST'){
  $p=json_decode(file_get_contents('php://input'),true)?:[];$id=(int)($p['id']??0);$name=trim((string)($p['name']??''));$email=trim((string)($p['email']??''));$password=(string)($p['password']??'');
  if($name===''||!filter_var($email,FILTER_VALIDATE_EMAIL)) json_response(['ok'=>false,'error'=>'Geçerli ad ve e-posta gerekli.'],422);
  $role=trim((string)($p['role']??'Görüntüleyici'));if(!valid_role_name($role))json_response(['ok'=>false,'error'=>'Geçersiz veya artık mevcut olmayan rol seçildi.'],422);$department=trim((string)($p['department']??''));$status=in_array($p['status']??'', ['active','passive'],true)?$p['status']:'active';$note=trim((string)($p['note']??''));$photo=(string)($p['photo']??'');
  if($id){
   $oldQ=db()->prepare('SELECT role,status FROM users WHERE id=?');$oldQ->execute([$id]);$oldUser=$oldQ->fetch();
   if(!$oldUser)json_response(['ok'=>false,'error'=>'Kullanıcı bulunamadı.'],404);
   if(strcasecmp((string)$oldUser['role'],'Admin')===0&&$oldUser['status']==='active'&&(strcasecmp($role,'Admin')!==0||$status!=='active')&&active_admin_count()<=1)json_response(['ok'=>false,'error'=>'Son aktif Admin kullanıcısının rolü değiştirilemez veya hesabı pasife alınamaz.'],409);
   if($password!=='' && strlen($password)<8) json_response(['ok'=>false,'error'=>'Şifre en az 8 karakter olmalı.'],422);
   if($password!==''){$s=db()->prepare('UPDATE users SET name=?,email=?,role=?,department=?,status=?,note=?,photo_data=?,password_hash=? WHERE id=?');$s->execute([$name,$email,$role,$department,$status,$note,$photo,password_hash($password,PASSWORD_DEFAULT),$id]);}
   else{$s=db()->prepare('UPDATE users SET name=?,email=?,role=?,department=?,status=?,note=?,photo_data=? WHERE id=?');$s->execute([$name,$email,$role,$department,$status,$note,$photo,$id]);}
   audit('user_update','user',(string)$id,['email'=>$email,'role'=>$role,'status'=>$status]);json_response(['ok'=>true,'id'=>$id]);
  }
  if(strlen($password)<8) json_response(['ok'=>false,'error'=>'Yeni kullanıcı şifresi en az 8 karakter olmalı.'],422);
  $s=db()->prepare('INSERT INTO users(name,email,password_hash,role,department,status,note,photo_data) VALUES(?,?,?,?,?,?,?,?)');$s->execute([$name,$email,password_hash($password,PASSWORD_DEFAULT),$role,$department,$status,$note,$photo]);$new=(int)db()->lastInsertId();audit('user_create','user',(string)$new,['email'=>$email,'role'=>$role]);json_response(['ok'=>true,'id'=>$new]);
 }
 if($_SERVER['REQUEST_METHOD']==='DELETE'){
  $id=(int)($_GET['id']??0);if(!$id)json_response(['ok'=>false,'error'=>'Kullanıcı id gerekli'],422);if($id===(int)$_SESSION['user_id'])json_response(['ok'=>false,'error'=>'Kendi oturum hesabınızı silemezsiniz.'],409);
  $s=db()->prepare("SELECT role,status FROM users WHERE id=?");$s->execute([$id]);$u=$s->fetch();if(!$u)json_response(['ok'=>false,'error'=>'Kullanıcı bulunamadı'],404);
  if(strcasecmp((string)$u['role'],'Admin')===0&&$u['status']==='active'){ $n=active_admin_count();if($n<=1)json_response(['ok'=>false,'error'=>'Son aktif Admin kullanıcısı silinemez.'],409); }
  db()->prepare('DELETE FROM users WHERE id=?')->execute([$id]);audit('user_delete','user',(string)$id);json_response(['ok'=>true]);
 }
 json_response(['ok'=>false,'error'=>'Desteklenmeyen yöntem'],405);
}catch(PDOException $e){if((int)$e->errorInfo[1]===1062)json_response(['ok'=>false,'error'=>'Bu e-posta zaten kayıtlı.'],409);json_response(['ok'=>false,'error'=>public_error($e,'Kullanıcı işlemi tamamlanamadı.')],500);}catch(Throwable $e){json_response(['ok'=>false,'error'=>public_error($e,'Kullanıcı işlemi tamamlanamadı.')],500);}
