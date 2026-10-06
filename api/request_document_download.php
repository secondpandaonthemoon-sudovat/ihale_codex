<?php
declare(strict_types=1);
require_once __DIR__.'/../app/bootstrap.php';
require_login();
$u=current_user();if(!$u||!has_app_permission('docs',$u)){http_response_code(403);exit('Evrak görüntüleme yetkisi gerekli');}
$id=(int)($_GET['id']??0);if(!$id){http_response_code(400);exit('Dosya id gerekli');}
$s=db()->prepare('SELECT * FROM file_registry WHERE id=? AND deleted_at IS NULL');$s->execute([$id]);$r=$s->fetch();if(!$r){http_response_code(404);exit('Dosya bulunamadı');}
try{$st=db()->query('SELECT state_json FROM app_state WHERE id=1')->fetchColumn();$js=$st?json_decode((string)$st,true):[];$exists=false;foreach(($js['requests']??[]) as $rr)if((int)($rr['id']??0)===(int)$r['request_id']){$exists=true;break;}if(!$exists){http_response_code(404);exit('Bağlı talep bulunamadı');}}catch(Throwable $e){http_response_code(500);exit('Yetki doğrulaması yapılamadı');}
$base=realpath(__DIR__.'/../storage');$path=$base.DIRECTORY_SEPARATOR.str_replace(['/',"\\"],DIRECTORY_SEPARATOR,$r['relative_path']);$real=realpath($path);
if(!$base||!$real||!str_starts_with($real,$base.DIRECTORY_SEPARATOR)||!is_file($real)){http_response_code(404);exit('Dosya bulunamadı');}
audit('file_download','request_document',(string)$id,['name'=>$r['original_name']]);
header('X-Content-Type-Options: nosniff');header('Content-Type: '.($r['mime_type']?:'application/octet-stream'));header('Content-Length: '.filesize($real));header('Content-Disposition: inline; filename="'.rawurlencode($r['original_name']).'"; filename*=UTF-8\'\''.rawurlencode($r['original_name']));readfile($real);
