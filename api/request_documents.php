<?php
declare(strict_types=1);
require_once __DIR__.'/../app/bootstrap.php';
require_once __DIR__.'/../app/upload_security.php';
require_login();
$u=current_user();
if(!$u||!has_app_permission('docs',$u))json_response(['ok'=>false,'error'=>'Evrak modülü görüntüleme yetkisi gerekli'],403);

$base=realpath(__DIR__.'/../storage') ?: (__DIR__.'/../storage');
$dir=$base.DIRECTORY_SEPARATOR.'request-docs';
$trashDir=$base.DIRECTORY_SEPARATOR.'trash'.DIRECTORY_SEPARATOR.'request-docs';
foreach([$dir,$trashDir] as $d)if(!is_dir($d)&&!@mkdir($d,0770,true)&&!is_dir($d))json_response(['ok'=>false,'error'=>'Doküman klasörü oluşturulamadı'],500);

$allowedExt=['pdf','doc','docx','xls','xlsx','csv','txt','rtf','jpg','jpeg','png','webp','zip','dwg','dxf'];
$allowedMime=[
 'application/pdf','application/msword','application/vnd.openxmlformats-officedocument.wordprocessingml.document',
 'application/vnd.ms-excel','application/vnd.openxmlformats-officedocument.spreadsheetml.sheet','text/csv','text/plain','application/rtf',
 'image/jpeg','image/png','image/webp','application/zip','application/x-zip-compressed','application/octet-stream','image/vnd.dwg','image/vnd.dxf','application/acad'
];
$categories=['tender'=>'İhale Dokümanı','technical'=>'Teknik Şartname','contract'=>'Sözleşme','drawing'=>'Çizim','customer'=>'Müşteri Evrakı','supplier'=>'Tedarikçi Evrakı','other'=>'Diğer'];
function clean_name(string $name): string {$name=preg_replace('/[^A-Za-z0-9._-]+/u','_',basename($name));return trim((string)$name,'_')?:'document';}
function file_url(int $id): string{return 'api/request_document_download.php?id='.$id;}
function storage_path(string $base,string $relative): string{return $base.DIRECTORY_SEPARATOR.str_replace(['/',"\\"],DIRECTORY_SEPARATOR,ltrim($relative,'/\\'));}
function move_to_trash(array $row,string $base,string $trashDir): ?string {
    $src=storage_path($base,(string)$row['relative_path']);if(!is_file($src))return null;
    $sub=$trashDir.DIRECTORY_SEPARATOR.date('Ymd').DIRECTORY_SEPARATOR.(int)$row['request_id'];if(!is_dir($sub))@mkdir($sub,0770,true);
    $name=(int)$row['id'].'_'.basename((string)$row['stored_name']);$dest=$sub.DIRECTORY_SEPARATOR.$name;
    if(!@rename($src,$dest)){if(!@copy($src,$dest)||!@unlink($src))throw new RuntimeException('Doküman çöp kutusuna taşınamadı.');}
    return 'trash/request-docs/'.date('Ymd').'/'.(int)$row['request_id'].'/'.$name;
}
function restore_from_trash(array $row,string $base,string $dir): string {
    $src=storage_path($base,(string)$row['relative_path']);if(!is_file($src))throw new RuntimeException('Çöp kutusundaki fiziksel dosya bulunamadı.');
    $rdir=$dir.DIRECTORY_SEPARATOR.(int)$row['request_id'];if(!is_dir($rdir))@mkdir($rdir,0770,true);
    $dest=$rdir.DIRECTORY_SEPARATOR.(string)$row['stored_name'];
    if(!@rename($src,$dest)){if(!@copy($src,$dest)||!@unlink($src))throw new RuntimeException('Doküman geri yüklenemedi.');}
    return 'request-docs/'.(int)$row['request_id'].'/'.(string)$row['stored_name'];
}
function map_file_row(array $row,array $categories): array {return [
 'id'=>(int)$row['id'],'serverId'=>(int)$row['id'],'requestId'=>(int)$row['request_id'],'name'=>(string)$row['original_name'],
 'category'=>(string)($row['category']??'other'),'categoryLabel'=>$categories[(string)($row['category']??'other')]??(string)($row['category']??'other'),
 'version'=>max(1,(int)($row['version_no']??1)),'description'=>(string)($row['description']??''),'size'=>(int)($row['size_bytes']??0),
 'mime'=>(string)($row['mime_type']??''),'uploadedBy'=>(string)($row['uploaded_by_name']??''),'created'=>!empty($row['created_at'])?date('d.m.Y H:i',strtotime((string)$row['created_at'])):'',
 'deleted'=>!empty($row['deleted_at']),'deletedAt'=>$row['deleted_at']??null,'url'=>empty($row['deleted_at'])?file_url((int)$row['id']):null
];}

if($_SERVER['REQUEST_METHOD']==='GET'){
 $rid=max(0,(int)($_GET['request_id']??0));$trash=!empty($_GET['trash']);if($trash&&!has_app_write_permission('docs',$u))json_response(['ok'=>false,'error'=>'Çöp kutusu için evrak yazma yetkisi gerekli'],403);
 $sql='SELECT f.*,u.name uploaded_by_name FROM file_registry f LEFT JOIN users u ON u.id=f.uploaded_by WHERE '.($trash?'f.deleted_at IS NOT NULL':'f.deleted_at IS NULL');$args=[];
 if($rid){$sql.=' AND f.request_id=?';$args[]=$rid;}$sql.=' ORDER BY f.id ASC';$q=db()->prepare($sql);$q->execute($args);$rows=$q->fetchAll();
 json_response(['ok'=>true,'files'=>array_map(fn($r)=>map_file_row($r,$categories),$rows)]);
}

if($_SERVER['REQUEST_METHOD']==='POST'){
 if(!has_app_write_permission('docs',$u))json_response(['ok'=>false,'error'=>'Evrak ekleme/değiştirme yetkisi gerekli'],403);
 if(($_POST['action']??'')==='restore'){
   $id=(int)($_POST['id']??0);$q=db()->prepare('SELECT * FROM file_registry WHERE id=? AND deleted_at IS NOT NULL');$q->execute([$id]);$row=$q->fetch();if(!$row)json_response(['ok'=>false,'error'=>'Çöp kutusunda doküman bulunamadı'],404);
   try{$rel=restore_from_trash($row,$base,$dir);db()->prepare('UPDATE file_registry SET relative_path=?,deleted_at=NULL WHERE id=?')->execute([$rel,$id]);audit('file_restore','request_document',(string)$id,['request_id'=>$row['request_id']]);json_response(['ok'=>true]);}catch(Throwable $e){json_response(['ok'=>false,'error'=>public_error($e,'Doküman geri yüklenemedi.')],500);}
 }
 $rid=(int)($_POST['request_id']??0);$desc=trim((string)($_POST['description']??''));$cat=(string)($_POST['category']??'other');$version=max(1,(int)($_POST['version_no']??1));
 if(!$rid||empty($_FILES['files']))json_response(['ok'=>false,'error'=>'Talep ve dosya gerekli'],400);
 $reqNo=null;try{$rq=db()->prepare('SELECT request_no FROM requests WHERE id=? LIMIT 1');$rq->execute([$rid]);$reqNo=$rq->fetchColumn()?:null;if($reqNo===null){$st=db()->query('SELECT state_json FROM app_state WHERE id=1')->fetchColumn();$js=$st?json_decode((string)$st,true):[];foreach(($js['requests']??[]) as $rr)if((int)($rr['id']??0)===$rid){$reqNo=$rr['no']??null;break;}}}catch(Throwable $e){}
 if($reqNo===null)json_response(['ok'=>false,'error'=>'İhale/talep verisi henüz veritabanında bulunamadı. Talebi kaydedip tekrar deneyin.'],404);if(!isset($categories[$cat]))$cat='other';
 $rdir=$dir.DIRECTORY_SEPARATOR.$rid;if(!is_dir($rdir))@mkdir($rdir,0770,true);$names=(array)$_FILES['files']['name'];$tmp=(array)$_FILES['files']['tmp_name'];$err=(array)$_FILES['files']['error'];$size=(array)$_FILES['files']['size'];$out=[];$rejected=[];$finfo=new finfo(FILEINFO_MIME_TYPE);
 foreach($names as $i=>$orig){
   $error=(int)($err[$i]??UPLOAD_ERR_NO_FILE);if($error!==UPLOAD_ERR_OK){$rejected[]=['name'=>$orig,'reason'=>'Yükleme hatası '.$error];continue;}
   $bytes=(int)($size[$i]??0);if($bytes<=0||$bytes>25*1024*1024){$rejected[]=['name'=>$orig,'reason'=>'Dosya boyutu 25 MB sınırını aşıyor veya boş'];continue;}
   $ext=strtolower(pathinfo((string)$orig,PATHINFO_EXTENSION));$mime=(string)$finfo->file($tmp[$i]);if(!in_array($ext,$allowedExt,true)||!in_array($mime,$allowedMime,true)){$rejected[]=['name'=>$orig,'reason'=>'İzin verilmeyen dosya türü'];continue;}
   try{$scan=asay_scan_upload($tmp[$i]);if(!$scan['clean']){$rejected[]=['name'=>$orig,'reason'=>'Güvenlik taraması dosyayı reddetti'];continue;}}catch(Throwable $e){$rejected[]=['name'=>$orig,'reason'=>public_error($e,'Dosya güvenlik taraması tamamlanamadı.')];continue;}
   if($ext==='zip'&&class_exists('ZipArchive')){$za=new ZipArchive();if($za->open($tmp[$i])===true){$unc=0;if($za->numFiles>2000){$za->close();$rejected[]=['name'=>$orig,'reason'=>'ZIP içinde çok fazla dosya var'];continue;}for($zi=0;$zi<$za->numFiles;$zi++){$st=$za->statIndex($zi);$unc+=(int)($st['size']??0);if($unc>150*1024*1024)break;}$za->close();if($unc>150*1024*1024){$rejected[]=['name'=>$orig,'reason'=>'ZIP açılmış boyutu güvenlik sınırını aşıyor'];continue;}}}
   $stored=bin2hex(random_bytes(16)).'.'.$ext;$dest=$rdir.DIRECTORY_SEPARATOR.$stored;if(!move_uploaded_file($tmp[$i],$dest)){$rejected[]=['name'=>$orig,'reason'=>'Dosya sunucuya taşınamadı'];continue;}@chmod($dest,0640);
   $rel='request-docs/'.$rid.'/'.$stored;$q=db()->prepare('INSERT INTO file_registry(request_id,request_no,category,description,version_no,original_name,stored_name,relative_path,mime_type,extension,size_bytes,uploaded_by) VALUES(?,?,?,?,?,?,?,?,?,?,?,?)');$q->execute([$rid,$reqNo,$cat,$desc,$version,(string)$orig,$stored,$rel,$mime,$ext,$bytes,$u['id']]);$fid=(int)db()->lastInsertId();
   $row=['id'=>$fid,'request_id'=>$rid,'original_name'=>(string)$orig,'category'=>$cat,'version_no'=>$version,'description'=>$desc,'size_bytes'=>$bytes,'mime_type'=>$mime,'uploaded_by_name'=>$u['name'],'created_at'=>date('Y-m-d H:i:s'),'deleted_at'=>null];$out[]=map_file_row($row,$categories);audit('file_upload','request_document',(string)$fid,['request_id'=>$rid,'name'=>$orig,'category'=>$cat,'version'=>$version]);
 }
 json_response(['ok'=>true,'files'=>$out,'rejected'=>$rejected]);
}

if($_SERVER['REQUEST_METHOD']==='PATCH'){
 if(!has_app_write_permission('docs',$u))json_response(['ok'=>false,'error'=>'Evrak düzenleme yetkisi gerekli'],403);$in=json_decode(file_get_contents('php://input'),true)?:[];$id=(int)($in['id']??0);if(!$id)json_response(['ok'=>false,'error'=>'Doküman id gerekli'],400);
 $desc=trim((string)($in['description']??''));$cat=(string)($in['category']??'other');if(!isset($categories[$cat]))$cat='other';$version=max(1,(int)($in['version']??1));$q=db()->prepare('UPDATE file_registry SET description=?,category=?,version_no=? WHERE id=? AND deleted_at IS NULL');$q->execute([$desc,$cat,$version,$id]);if(!$q->rowCount())json_response(['ok'=>false,'error'=>'Doküman bulunamadı veya değişiklik yok'],404);audit('file_metadata_update','request_document',(string)$id,['category'=>$cat,'version'=>$version]);json_response(['ok'=>true]);
}

if($_SERVER['REQUEST_METHOD']==='DELETE'){
 if(!has_app_write_permission('docs',$u))json_response(['ok'=>false,'error'=>'Evrak silme yetkisi gerekli'],403);$requestId=(int)($_GET['request_id']??0);
 try{
   if($requestId){$q=db()->prepare('SELECT * FROM file_registry WHERE request_id=? AND deleted_at IS NULL');$q->execute([$requestId]);$rows=$q->fetchAll();$pdo=db();$pdo->beginTransaction();foreach($rows as $row){$rel=move_to_trash($row,$base,$trashDir);if($rel)$pdo->prepare('UPDATE file_registry SET relative_path=?,deleted_at=NOW() WHERE id=?')->execute([$rel,$row['id']]);else$pdo->prepare('UPDATE file_registry SET deleted_at=NOW() WHERE id=?')->execute([$row['id']]);}$pdo->commit();audit('file_trash_request_all','request',(string)$requestId,['count'=>count($rows)]);json_response(['ok'=>true,'count'=>count($rows)]);}
   $id=(int)($_GET['id']??0);if(!$id)json_response(['ok'=>false,'error'=>'Dosya id gerekli'],400);$q=db()->prepare('SELECT * FROM file_registry WHERE id=? AND deleted_at IS NULL');$q->execute([$id]);$row=$q->fetch();if(!$row)json_response(['ok'=>false,'error'=>'Dosya bulunamadı'],404);$rel=move_to_trash($row,$base,$trashDir);if($rel)db()->prepare('UPDATE file_registry SET relative_path=?,deleted_at=NOW() WHERE id=?')->execute([$rel,$id]);else db()->prepare('UPDATE file_registry SET deleted_at=NOW() WHERE id=?')->execute([$id]);audit('file_trash','request_document',(string)$id,['request_id'=>$row['request_id']]);json_response(['ok'=>true]);
 }catch(Throwable $e){try{if(db()->inTransaction())db()->rollBack();}catch(Throwable $ignore){}json_response(['ok'=>false,'error'=>public_error($e,'Doküman silinemedi.')],500);}
}
json_response(['ok'=>false,'error'=>'Desteklenmeyen işlem'],405);
