<?php
require_once __DIR__.'/app/bootstrap.php';
if(!empty($_SESSION['user_id'])){try{db()->prepare('INSERT INTO audit_log(user_id,action) VALUES(?,?)')->execute([$_SESSION['user_id'],'logout']);}catch(Throwable $e){}}
$_SESSION=[];if(ini_get('session.use_cookies')){$p=session_get_cookie_params();setcookie(session_name(),'',time()-42000,$p['path'],$p['domain'],$p['secure'],$p['httponly']);}session_destroy();header('Location: login.php');exit;
