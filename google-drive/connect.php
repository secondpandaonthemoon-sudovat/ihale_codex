<?php
declare(strict_types=1);
require_once __DIR__.'/../app/bootstrap.php';
require_login();require_admin();
require_once __DIR__.'/drive_client.php';
$c=drive_config();
if(empty($c['client_id'])||empty($c['client_secret'])){exit('Önce config/drive.php içine Google OAuth Client ID ve Client Secret girin.');}
$state=bin2hex(random_bytes(16));
$states=$_SESSION['drive_oauth_states']??[];
if(!is_array($states))$states=[];
$now=time();
$states=array_filter($states,fn($ts)=>is_int($ts)&&$ts>=$now-900);
$states[$state]=$now;
$_SESSION['drive_oauth_states']=$states;
$_SESSION['drive_oauth_state']=$state;
$q=http_build_query([
 'client_id'=>$c['client_id'],
 'redirect_uri'=>$c['redirect_uri'],
 'response_type'=>'code',
 'scope'=>$c['scope'],
 'access_type'=>'offline',
 'prompt'=>'consent',
 'state'=>$state,
 'include_granted_scopes'=>'true'
]);
session_write_close();
header('Location: https://accounts.google.com/o/oauth2/v2/auth?'.$q);exit;
