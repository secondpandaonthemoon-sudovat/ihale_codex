<?php
declare(strict_types=1);
require_once __DIR__.'/../app/bootstrap.php';
require_once __DIR__.'/../app/db_export.php';
require_login();require_admin();
require_once __DIR__.'/drive_client.php';
$msg='';$err='';$token=drive_read_token();$csrf=csrf_token();

if($_SERVER['REQUEST_METHOD']==='POST'){
  try{
    require_csrf((string)($_POST['_csrf']??''));
    $dir=__DIR__.'/../storage/backups';if(!is_dir($dir)&&!mkdir($dir,0750,true)&&!is_dir($dir))throw new RuntimeException('Yedek klasörü oluşturulamadı.');
    $file='ASAY_ERP_DB_'.date('Ymd_His').'.sql';$path=$dir.'/'.$file;
    $meta=asay_write_sql_backup(db(),$path,ASAY_APP_BUILD);
    $up=drive_upload_file($path,$file,'application/sql');
    audit('google_drive_backup','backup',$up['id']??null,['name'=>$file]+$meta);
    $msg='SQL yedeği PHP üzerinden oluşturuldu ve Google Drive’a yüklendi: '.$file;
    // local cache retention
    $files=glob($dir.'/ASAY_ERP_DB_*.sql')?:[];usort($files,fn($a,$b)=>(filemtime($b)?:0)<=>(filemtime($a)?:0));foreach(array_slice($files,5) as $old)@unlink($old);
  }catch(Throwable $e){$err=app_debug_enabled()?$e->getMessage():'Google Drive yedekleme tamamlanamadı. Drive bağlantısını ve sunucu yazma izinlerini kontrol edin.';}
}
?><!doctype html><html lang="tr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Google Drive Yedekleme</title><style>body{font-family:Arial;background:#f4f7fb;display:grid;place-items:center;min-height:100vh;color:#172033}.box{width:min(720px,92vw);background:#fff;border:1px solid #d8e0ea;border-radius:18px;padding:28px}.btn{display:inline-block;padding:11px 14px;border:0;border-radius:9px;background:#163d73;color:#fff;text-decoration:none;font-weight:700}.ok{background:#eaf8ef;color:#16733c;padding:12px;border-radius:9px}.err{background:#fff0f0;color:#b42318;padding:12px;border-radius:9px}.note{background:#f8fafc;border:1px solid #e4e7ec;padding:10px;border-radius:9px;color:#667085}</style></head><body><div class="box"><h1>Google Drive Yedekleme</h1><p>ASAY ERP veritabanının phpMyAdmin uyumlu SQL yedeğini <b>mysqldump/exec kullanmadan</b> PHP üzerinden üretir ve Drive klasörüne yükler.</p><?php if($msg):?><div class="ok"><?=htmlspecialchars($msg)?></div><?php endif;?><?php if($err):?><div class="err"><?=htmlspecialchars($err)?></div><?php endif;?><?php if(empty($token['refresh_token'])):?><p>Durum: Bağlı değil.</p><a class="btn" href="connect.php">Google Drive’a Bağlan</a><?php else:?><p>Durum: <b>Bağlı</b></p><form method="post"><input type="hidden" name="_csrf" value="<?=htmlspecialchars($csrf)?>"><button class="btn">Şimdi SQL Yedeği Al ve Drive’a Yükle</button></form><?php endif;?><div class="note" style="margin-top:14px">Bu işlem DB yedeğidir. Fiziksel dokümanlar için Ayarlar → Veritabanı → Tam Sistem Yedeği kullanın.</div><p style="margin-top:20px"><a href="../index.php">← Uygulamaya dön</a></p></div></body></html>
