<?php
declare(strict_types=1);
require_once __DIR__.'/../app/bootstrap.php';
function drive_config(): array {
  $c=require __DIR__.'/../config/drive.php';
  if(empty($c['redirect_uri'])){
    $c['redirect_uri']=app_base_url().'/google-drive/callback.php';
  }
  return $c;
}
function drive_token_path(): string { return __DIR__.'/../storage/google_drive_token.json'; }
function drive_read_token(): array { $p=drive_token_path(); if(!is_file($p)) return []; $j=json_decode((string)file_get_contents($p),true); return is_array($j)?$j:[]; }
function drive_write_token(array $t): void { $p=drive_token_path(); if(!is_dir(dirname($p))) mkdir(dirname($p),0775,true); $tmp=$p.'.tmp'; if(file_put_contents($tmp,json_encode($t,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES),LOCK_EX)===false)throw new RuntimeException('Drive token dosyası yazılamadı.'); @chmod($tmp,0600); if(!@rename($tmp,$p)){@unlink($tmp);throw new RuntimeException('Drive token dosyası güvenli biçimde kaydedilemedi.');} @chmod($p,0600); }
function drive_http(string $url,array $opts=[]): array {
  if(!function_exists('curl_init')) throw new RuntimeException('PHP cURL uzantısı gerekli.');
  $ch=curl_init($url);curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>60,CURLOPT_FOLLOWLOCATION=>true]+$opts);$body=curl_exec($ch);$code=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE);$err=curl_error($ch);curl_close($ch);if($body===false)throw new RuntimeException($err?:'Drive HTTP hatası');$json=json_decode($body,true);if($code<200||$code>=300)throw new RuntimeException('Google API HTTP '.$code.': '.($json['error']['message']??$body));return is_array($json)?$json:[];
}
function drive_access_token(): string {
  $cfg=drive_config();$t=drive_read_token();if(!empty($t['access_token'])&&!empty($t['expires_at'])&&time()<(int)$t['expires_at']-60)return (string)$t['access_token'];
  if(empty($t['refresh_token'])) throw new RuntimeException('Google Drive henüz bağlanmamış.');
  $post=http_build_query(['client_id'=>$cfg['client_id'],'client_secret'=>$cfg['client_secret'],'refresh_token'=>$t['refresh_token'],'grant_type'=>'refresh_token']);
  $j=drive_http('https://oauth2.googleapis.com/token',[CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>$post,CURLOPT_HTTPHEADER=>['Content-Type: application/x-www-form-urlencoded']]);
  $t['access_token']=$j['access_token']??'';$t['expires_at']=time()+(int)($j['expires_in']??3600);drive_write_token($t);return (string)$t['access_token'];
}
function drive_create_folder(string $name): string {
  $token=drive_access_token();$j=drive_http('https://www.googleapis.com/drive/v3/files?fields=id,name',[CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>json_encode(['name'=>$name,'mimeType'=>'application/vnd.google-apps.folder']),CURLOPT_HTTPHEADER=>['Authorization: Bearer '.$token,'Content-Type: application/json']]);return (string)($j['id']??'');
}
function drive_backup_folder_id(): string {
  $cfg=drive_config();$t=drive_read_token();if(!empty($t['backup_folder_id']))return (string)$t['backup_folder_id'];$id=drive_create_folder($cfg['backup_folder_name']??'ASAY ERP Backups');if(!$id)throw new RuntimeException('Drive yedek klasörü oluşturulamadı.');$t=drive_read_token();$t['backup_folder_id']=$id;drive_write_token($t);return $id;
}
function drive_upload_file(string $path,string $name,string $mime='application/sql'): array {
  if(!is_file($path))throw new RuntimeException('Yüklenecek dosya bulunamadı.');$token=drive_access_token();$folder=drive_backup_folder_id();$boundary='asay_'.bin2hex(random_bytes(8));$meta=json_encode(['name'=>$name,'parents'=>[$folder]],JSON_UNESCAPED_SLASHES);$data=file_get_contents($path);$body="--$boundary\r\nContent-Type: application/json; charset=UTF-8\r\n\r\n$meta\r\n--$boundary\r\nContent-Type: $mime\r\n\r\n$data\r\n--$boundary--";
  return drive_http('https://www.googleapis.com/upload/drive/v3/files?uploadType=multipart&fields=id,name,webViewLink,createdTime',[CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>$body,CURLOPT_HTTPHEADER=>['Authorization: Bearer '.$token,'Content-Type: multipart/related; boundary='.$boundary,'Content-Length: '.strlen($body)]]);
}
