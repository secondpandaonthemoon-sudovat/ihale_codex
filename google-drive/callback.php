<?php
declare(strict_types=1);
require_once __DIR__.'/../app/bootstrap.php';
require_login();require_admin();
require_once __DIR__.'/drive_client.php';
$c=drive_config();

function oauth_fail(string $title,string $detail=''): never {
  http_response_code(400);
  $safeTitle=htmlspecialchars($title,ENT_QUOTES,'UTF-8');
  $safeDetail=htmlspecialchars($detail,ENT_QUOTES,'UTF-8');
  echo '<!doctype html><html lang="tr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Google Drive OAuth</title><style>body{font-family:Arial;background:#f4f7fb;color:#172033;margin:0;display:grid;place-items:center;min-height:100vh}.box{width:min(720px,92vw);background:white;border:1px solid #d8e0ea;border-radius:16px;padding:26px;box-shadow:0 20px 50px #163d7320}.err{background:#fff0f0;color:#b42318;padding:12px;border-radius:9px}.note{margin-top:12px;color:#667085;font-size:13px;line-height:1.5}code{word-break:break-all}</style></head><body><div class="box"><h1>Google Drive OAuth bağlantısı tamamlanamadı</h1><div class="err"><b>'.$safeTitle.'</b>'.($safeDetail!==''?'<br>'.$safeDetail:'').'</div><div class="note">Kontrol edin: Google Cloud OAuth istemcisi <b>Web application</b> olmalı; Authorized redirect URI uygulamadaki değerle birebir aynı olmalı; External + Testing kullanıyorsanız Google hesabınızı Test users listesine ekleyin.</div><p><a href="backup.php">Yedekleme sayfasına dön</a></p></div></body></html>';
  exit;
}

if(!empty($_GET['error'])){
  oauth_fail('Google yetkilendirmeyi reddetti: '.(string)$_GET['error'],(string)($_GET['error_description']??''));
}
$code=(string)($_GET['code']??'');
$state=(string)($_GET['state']??'');
if($code==='')oauth_fail('Google authorization code göndermedi.','Callback URL: '.$c['redirect_uri']);
if($state==='')oauth_fail('OAuth state parametresi gelmedi.','Tarayıcı oturumu veya callback adresi değişmiş olabilir.');

$valid=false;
$states=$_SESSION['drive_oauth_states']??[];
if(is_array($states)&&isset($states[$state])&&(int)$states[$state]>=time()-900)$valid=true;
if(!$valid){
  $legacy=(string)($_SESSION['drive_oauth_state']??'');
  if($legacy!==''&&hash_equals($legacy,$state))$valid=true;
}
if(!$valid)oauth_fail('OAuth state doğrulaması başarısız.','Bağlantıyı aynı tarayıcıda ve aynı host üzerinden başlatın. localhost ile 127.0.0.1 adreslerini karıştırmayın.');

unset($_SESSION['drive_oauth_states'][$state],$_SESSION['drive_oauth_state']);
session_write_close();
$post=http_build_query([
 'code'=>$code,
 'client_id'=>$c['client_id'],
 'client_secret'=>$c['client_secret'],
 'redirect_uri'=>$c['redirect_uri'],
 'grant_type'=>'authorization_code'
]);
try{
  $j=drive_http('https://oauth2.googleapis.com/token',[CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>$post,CURLOPT_HTTPHEADER=>['Content-Type: application/x-www-form-urlencoded']]);
}catch(Throwable $e){
  oauth_fail('Google token değişimi başarısız.',$e->getMessage().' | Redirect URI: '.$c['redirect_uri']);
}
$old=drive_read_token();
$j['refresh_token']=$j['refresh_token']??($old['refresh_token']??'');
$j['expires_at']=time()+(int)($j['expires_in']??3600);
drive_write_token($j);
header('Location: backup.php?connected=1');exit;
