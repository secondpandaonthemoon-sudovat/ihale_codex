<?php
declare(strict_types=1);

/** Optional ClamAV/clamd upload scan. Configure ASAY_CLAMAV_HOST and ASAY_CLAMAV_PORT. */
function asay_scan_upload(string $path): array {
    $host=trim((string)(getenv('ASAY_CLAMAV_HOST')?:''));
    if($host==='')return ['enabled'=>false,'clean'=>true,'message'=>'ClamAV yapılandırılmamış'];
    $port=(int)(getenv('ASAY_CLAMAV_PORT')?:3310);if($port<1||$port>65535)$port=3310;
    $sock=@stream_socket_client('tcp://'.$host.':'.$port,$errno,$errstr,3,STREAM_CLIENT_CONNECT);
    if(!$sock)throw new RuntimeException('Dosya güvenlik tarayıcısına bağlanılamadı.');
    stream_set_timeout($sock,8);fwrite($sock,"zINSTREAM\0");
    $fh=@fopen($path,'rb');if(!$fh){fclose($sock);throw new RuntimeException('Yüklenecek dosya güvenlik taraması için açılamadı.');}
    try{
        while(!feof($fh)){
            $chunk=fread($fh,1024*512);if($chunk===false)throw new RuntimeException('Dosya güvenlik taraması okunamadı.');
            if($chunk==='')break;fwrite($sock,pack('N',strlen($chunk)).$chunk);
        }
        fwrite($sock,pack('N',0));$response='';
        while(!feof($sock)){$part=fread($sock,4096);if($part===false||$part==='')break;$response.=$part;if(str_contains($response,"\0"))break;}
    } finally {fclose($fh);fclose($sock);}
    $response=trim(str_replace("\0",'', $response));
    if(str_contains($response,'FOUND'))return ['enabled'=>true,'clean'=>false,'message'=>$response];
    if(!str_contains($response,'OK'))throw new RuntimeException('Dosya güvenlik taraması doğrulanamadı.');
    return ['enabled'=>true,'clean'=>true,'message'=>$response];
}
