<?php
declare(strict_types=1);
require_once __DIR__.'/../app/simple_zip.php';

$zip=tempnam(sys_get_temp_dir(),'asay_zip_smoke_');
if($zip===false)exit(10);
@unlink($zip);$zip.='.zip';
$out=tempnam(sys_get_temp_dir(),'asay_zip_out_');
if($out===false)exit(11);
try{
    $z=new AsaySimpleZip();
    if(!$z->open($zip))throw new RuntimeException('ZIP open failed');
    $z->addFromString('database/asaydb.json',json_encode(['format'=>'ASAY_ERP_DB_BACKUP','payload'=>str_repeat('x',4096)]));
    $payload=str_repeat('ASAY-STREAM-',100000);
    $z->addFromString('storage/request-docs/1/test.bin',$payload);
    $z->close();

    $idx=asay_zip_file_index($zip);
    if(!isset($idx['database/asaydb.json'],$idx['storage/request-docs/1/test.bin']))throw new RuntimeException('Index missing entries');
    $db=asay_zip_file_read_entry($zip,$idx['database/asaydb.json'],1024*1024);
    if(!str_contains($db,'ASAY_ERP_DB_BACKUP'))throw new RuntimeException('DB entry read failed');
    asay_zip_file_extract_to($zip,$idx['storage/request-docs/1/test.bin'],$out);
    if(hash_file('sha256',$out)!==hash('sha256',$payload))throw new RuntimeException('Stream extracted hash mismatch');
    echo "PASS: file-backed ZIP stream\n";
}catch(Throwable $e){
    fwrite(STDERR,$e->getMessage()."\n");exit(1);
}finally{
    @unlink($zip);@unlink($out);
}
