<?php
require_once __DIR__.'/app/bootstrap.php';
if(!is_installed()){header('Location: install.php');exit;}
if(!empty($_SESSION['user_id'])){header('Location: index.php');exit;}
$error='';
function ensure_login_attempt_table(): void {
 try{db()->exec("CREATE TABLE IF NOT EXISTS login_attempts (
   attempt_key CHAR(64) PRIMARY KEY,email_hash CHAR(64) NOT NULL,ip_hash CHAR(64) NOT NULL,
   failures INT UNSIGNED NOT NULL DEFAULT 0,locked_until DATETIME NULL,last_attempt DATETIME NOT NULL,
   INDEX idx_login_locked(locked_until),INDEX idx_login_last(last_attempt)
 ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");}catch(Throwable $e){}
}
function login_attempt_key(string $email): array {
 $ip=(string)($_SERVER['REMOTE_ADDR']??'unknown');
 $eh=hash('sha256',strtolower(trim($email)));$ih=hash('sha256',$ip);
 return [hash('sha256',$eh.'|'.$ih),$eh,$ih];
}
if($_SERVER['REQUEST_METHOD']==='POST'){
 ensure_login_attempt_table();
 $email=trim($_POST['email']??'');$pass=$_POST['password']??'';
 [$key,$eh,$ih]=login_attempt_key($email);$pdo=db();
 try{$pdo->exec("DELETE FROM login_attempts WHERE last_attempt < DATE_SUB(NOW(),INTERVAL 2 DAY)");}catch(Throwable $e){}
 $a=$pdo->prepare('SELECT failures,locked_until,last_attempt FROM login_attempts WHERE attempt_key=?');$a->execute([$key]);$attempt=$a->fetch();
 if($attempt&&((!empty($attempt['locked_until'])&&strtotime((string)$attempt['locked_until'])<=time())||strtotime((string)($attempt['last_attempt']??''))<time()-1800)){
   $pdo->prepare('UPDATE login_attempts SET failures=0,locked_until=NULL WHERE attempt_key=?')->execute([$key]);$attempt['failures']=0;$attempt['locked_until']=null;
 }
 if($attempt&&!empty($attempt['locked_until'])&&strtotime((string)$attempt['locked_until'])>time()){
   $mins=max(1,(int)ceil((strtotime((string)$attempt['locked_until'])-time())/60));
   $error='Çok fazla hatalı giriş denemesi. '.$mins.' dakika sonra tekrar deneyin.';
 } else {
   $s=$pdo->prepare('SELECT * FROM users WHERE email=? AND status=\'active\' LIMIT 1');$s->execute([$email]);$u=$s->fetch();
   if($u&&password_verify($pass,$u['password_hash'])){
     $pdo->prepare('DELETE FROM login_attempts WHERE attempt_key=?')->execute([$key]);
     session_regenerate_id(true);$_SESSION['user_id']=$u['id'];
     try{$pdo->prepare('INSERT INTO audit_log(user_id,action,payload_json) VALUES(?,?,?)')->execute([$u['id'],'login',json_encode(['ip_hash'=>$ih],JSON_UNESCAPED_SLASHES)]);}catch(Throwable $e){error_log('ASAY login audit failed: '.$e->getMessage());}
     header('Location: index.php');exit;
   }
   $failures=(int)($attempt['failures']??0)+1;$locked=$failures>=5?date('Y-m-d H:i:s',time()+600):null;
   $q=$pdo->prepare('INSERT INTO login_attempts(attempt_key,email_hash,ip_hash,failures,locked_until,last_attempt) VALUES(?,?,?,?,?,NOW()) ON DUPLICATE KEY UPDATE failures=VALUES(failures),locked_until=VALUES(locked_until),last_attempt=NOW()');
   $q->execute([$key,$eh,$ih,$failures,$locked]);
   try{try{$pdo->prepare('INSERT INTO audit_log(user_id,action,payload_json) VALUES(NULL,?,?)')->execute(['login_failed',json_encode(['email_hash'=>$eh,'ip_hash'=>$ih,'failures'=>$failures],JSON_UNESCAPED_SLASHES)]);}catch(Throwable $e){}}catch(Throwable $e){}
   $error=$locked?'Çok fazla hatalı giriş denemesi. 10 dakika boyunca giriş geçici olarak kilitlendi.':'E-posta veya şifre hatalı.';
 }
}
?><!doctype html><html lang="tr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>ASAY ERP Giriş</title><style>body{font-family:Arial;background:linear-gradient(135deg,#eef3f9,#f9fbfd);margin:0;display:grid;place-items:center;min-height:100vh;color:#172033}.box{width:min(430px,92vw);background:#fff;border:1px solid #d8e0ea;border-radius:18px;padding:30px;box-shadow:0 20px 60px #163d7322}.logo{width:48px;height:48px;border-radius:13px;background:#163d73;color:#fff;display:grid;place-items:center;font-weight:900}.f{margin:14px 0}.f label{display:block;font-size:12px;font-weight:700;margin-bottom:5px}.f input{width:100%;box-sizing:border-box;padding:12px;border:1px solid #ccd5e0;border-radius:9px}.btn{width:100%;padding:12px;border:0;border-radius:9px;background:#163d73;color:#fff;font-weight:700}.err{background:#fff0f0;color:#b42318;padding:10px;border-radius:9px}</style></head><body><div class="box"><div class="logo">AS</div><h1>ASAY İhale & Teklif OS</h1><p>PHP + MySQL çalışma ortamı</p><?php if($error):?><div class="err"><?=htmlspecialchars($error)?></div><?php endif;?><form method="post"><div class="f"><label>E-posta</label><input name="email" type="email" required autofocus></div><div class="f"><label>Şifre</label><input name="password" type="password" required></div><button class="btn">Giriş Yap</button></form></div></body></html>