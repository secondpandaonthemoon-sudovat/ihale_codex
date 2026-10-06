<?php
declare(strict_types=1);

/**
 * Minimal ZIP writer/reader for ASAY ERP.
 * Supports STORE (0) and DEFLATE (8), no encryption, no data descriptors.
 * Used as a fallback when PHP ZipArchive is unavailable.
 */
function asay_zip_dos_time(int $ts): array {
    $d=getdate($ts);
    $year=max(1980,(int)$d['year']);
    $date=(($year-1980)<<9)|((int)$d['mon']<<5)|(int)$d['mday'];
    $time=((int)$d['hours']<<11)|((int)$d['minutes']<<5)|intdiv((int)$d['seconds'],2);
    return [$time,$date];
}
function asay_u32(int $v): int { return $v < 0 ? $v + 4294967296 : $v; }

final class AsaySimpleZip {
    private $fh=null;
    private string $path='';
    private array $central=[];
    private int $offset=0;

    public function open(string $path,int $flags=0): bool {
        $this->path=$path;
        $this->fh=@fopen($path,'w+b');
        $this->central=[];$this->offset=0;
        return is_resource($this->fh);
    }
    public function addFromString(string $name,string $data): bool {
        if(!is_resource($this->fh))return false;
        $name=str_replace('\\','/',$name);
        $name=ltrim($name,'/');
        if($name===''||str_contains($name,'../'))return false;
        [$tm,$dt]=asay_zip_dos_time(time());
        $usize=strlen($data);
        $crc=asay_u32(crc32($data));
        $method=0;$compressed=$data;
        if(function_exists('gzdeflate')&&$usize>32){
            $z=@gzdeflate($data,6);
            if($z!==false&&strlen($z)<$usize){$method=8;$compressed=$z;}
        }
        $csize=strlen($compressed);$nameLen=strlen($name);
        $localOffset=$this->offset;
        $header=pack('VvvvvvVVVvv',0x04034b50,20,0,$method,$tm,$dt,$crc,$csize,$usize,$nameLen,0);
        fwrite($this->fh,$header.$name.$compressed);
        $this->offset+=strlen($header)+$nameLen+$csize;
        $this->central[]=[
          'name'=>$name,'method'=>$method,'time'=>$tm,'date'=>$dt,'crc'=>$crc,
          'csize'=>$csize,'usize'=>$usize,'offset'=>$localOffset
        ];
        return true;
    }
    public function addFile(string $path,string $name): bool {
        if(!is_resource($this->fh)||!is_file($path))return false;
        $name=str_replace('\\','/',$name);$name=ltrim($name,'/');
        if($name===''||str_contains($name,'../'))return false;
        $in=@fopen($path,'rb');if(!$in)return false;
        $usize=(int)filesize($path);$crcHex=@hash_file('crc32b',$path);if($crcHex===false){fclose($in);return false;}
        $crc=(int)hexdec($crcHex);[$tm,$dt]=asay_zip_dos_time(filemtime($path)?:time());
        $method=0;$csize=$usize;$nameLen=strlen($name);$localOffset=$this->offset;
        $header=pack('VvvvvvVVVvv',0x04034b50,20,0,$method,$tm,$dt,$crc,$csize,$usize,$nameLen,0);
        fwrite($this->fh,$header.$name);$this->offset+=strlen($header)+$nameLen;
        while(!feof($in)){$buf=fread($in,1048576);if($buf===false){fclose($in);return false;}if($buf!==''){fwrite($this->fh,$buf);$this->offset+=strlen($buf);}}
        fclose($in);
        $this->central[]=['name'=>$name,'method'=>$method,'time'=>$tm,'date'=>$dt,'crc'=>$crc,'csize'=>$csize,'usize'=>$usize,'offset'=>$localOffset];
        return true;
    }
    public function close(): bool {
        if(!is_resource($this->fh))return false;
        $cdOffset=$this->offset;$cd='';
        foreach($this->central as $e){
            $name=$e['name'];$nameLen=strlen($name);
            $cd.=pack('VvvvvvvVVVvvvvvVV',
              0x02014b50,20,20,0,$e['method'],$e['time'],$e['date'],
              $e['crc'],$e['csize'],$e['usize'],$nameLen,0,0,0,0,0,$e['offset']
            ).$name;
        }
        fwrite($this->fh,$cd);
        $count=count($this->central);
        $eocd=pack('VvvvvVVv',0x06054b50,0,0,$count,$count,strlen($cd),$cdOffset,0);
        fwrite($this->fh,$eocd);
        fclose($this->fh);$this->fh=null;
        return true;
    }
}
function asay_zip_entries_from_data(string $zipData): array {
    $eocd=strrpos($zipData,"PK\x05\x06");
    if($eocd===false||strlen($zipData)<$eocd+22)throw new RuntimeException('ZIP merkez dizini bulunamadı.');
    $e=unpack('vdisk/vdiskStart/ventriesDisk/ventries/VcdSize/VcdOffset/vcommentLen',substr($zipData,$eocd+4,18));
    if(!$e)throw new RuntimeException('ZIP merkez dizini okunamadı.');
    $pos=(int)$e['cdOffset'];$limit=$pos+(int)$e['cdSize'];$out=[];$entryCount=0;$totalUncompressed=0;
    while($pos+46<=strlen($zipData)&&$pos<$limit){
        $entryCount++;if($entryCount>5000)throw new RuntimeException('ZIP içinde 5000’den fazla girdi var.');
        if(substr($zipData,$pos,4)!=="PK\x01\x02")break;
        $h=unpack('vverMade/vverNeed/vflags/vmethod/vmtime/vmdate/Vcrc/Vcsize/Vusize/vfnlen/vextralen/vcommentlen/vdisk/vintattr/Vextattr/Vlocaloff',substr($zipData,$pos+4,42));
        if(!$h)throw new RuntimeException('ZIP girdisi okunamadı.');
        $name=substr($zipData,$pos+46,(int)$h['fnlen']);
        $totalUncompressed+=(int)$h['usize'];if($totalUncompressed>300*1024*1024)throw new RuntimeException('ZIP açılmış toplam boyutu 300 MB güvenlik sınırını aşıyor.');
        if($name===''||str_contains($name,'../')||str_starts_with($name,'/'))throw new RuntimeException('ZIP içinde güvensiz dosya yolu var.');
        $lo=(int)$h['localoff'];
        if(substr($zipData,$lo,4)!=="PK\x03\x04")throw new RuntimeException('ZIP yerel başlığı bozuk.');
        $lh=unpack('vver/vflags/vmethod/vmtime/vmdate/Vcrc/Vcsize/Vusize/vfnlen/vextralen',substr($zipData,$lo+4,26));
        if(!$lh)throw new RuntimeException('ZIP yerel başlığı okunamadı.');
        $start=$lo+30+(int)$lh['fnlen']+(int)$lh['extralen'];
        $compressed=substr($zipData,$start,(int)$h['csize']);$method=(int)$h['method'];
        if($method===0)$data=$compressed;
        elseif($method===8){
            if(!function_exists('gzinflate'))throw new RuntimeException('ZIP deflate için zlib desteği gerekli.');
            $data=@gzinflate($compressed);
            if($data===false)throw new RuntimeException('ZIP girdisi açılamadı: '.$name);
        } else throw new RuntimeException('Desteklenmeyen ZIP sıkıştırma yöntemi: '.$method);
        if(strlen($data)!==(int)$h['usize'])throw new RuntimeException('ZIP dosya boyutu doğrulanamadı: '.$name);
        $out[$name]=$data;
        $pos+=46+(int)$h['fnlen']+(int)$h['extralen']+(int)$h['commentlen'];
    }
    return $out;
}

/** File-backed ZIP index/extract helpers used by full backup restore to avoid loading the whole ZIP into RAM. */
function asay_zip_file_index(string $path): array {
    $fh=@fopen($path,'rb');if(!$fh)throw new RuntimeException('ZIP dosyası açılamadı.');
    try{
        $size=(int)filesize($path);$tailSize=min($size,65557);fseek($fh,$size-$tailSize);$tail=fread($fh,$tailSize);if($tail===false)throw new RuntimeException('ZIP sonu okunamadı.');
        $eocd=strrpos($tail,"PK\x05\x06");if($eocd===false||strlen($tail)<$eocd+22)throw new RuntimeException('ZIP merkez dizini bulunamadı.');
        $e=unpack('vdisk/vdiskStart/ventriesDisk/ventries/VcdSize/VcdOffset/vcommentLen',substr($tail,$eocd+4,18));if(!$e)throw new RuntimeException('ZIP merkez dizini okunamadı.');
        $count=(int)$e['entries'];if($count>5000)throw new RuntimeException('ZIP içinde 5000’den fazla girdi var.');
        fseek($fh,(int)$e['cdOffset']);$out=[];$total=0;
        for($i=0;$i<$count;$i++){
            $sig=fread($fh,4);if($sig!=="PK\x01\x02")throw new RuntimeException('ZIP merkez dizin girdisi bozuk.');
            $raw=fread($fh,42);if($raw===false||strlen($raw)!==42)throw new RuntimeException('ZIP merkez dizin girdisi eksik.');
            $h=unpack('vverMade/vverNeed/vflags/vmethod/vmtime/vmdate/Vcrc/Vcsize/Vusize/vfnlen/vextralen/vcommentlen/vdisk/vintattr/Vextattr/Vlocaloff',$raw);if(!$h)throw new RuntimeException('ZIP girdisi okunamadı.');
            $name=fread($fh,(int)$h['fnlen']);if($name===false)throw new RuntimeException('ZIP dosya adı okunamadı.');
            if($name===''||str_contains($name,'../')||str_starts_with($name,'/')||str_contains($name,"\0"))throw new RuntimeException('ZIP içinde güvensiz dosya yolu var.');
            if((int)$h['extralen']>0)fseek($fh,(int)$h['extralen'],SEEK_CUR);if((int)$h['commentlen']>0)fseek($fh,(int)$h['commentlen'],SEEK_CUR);
            $total+=(int)$h['usize'];if($total>300*1024*1024)throw new RuntimeException('ZIP açılmış toplam boyutu 300 MB güvenlik sınırını aşıyor.');
            $method=(int)$h['method'];if(!in_array($method,[0,8],true))throw new RuntimeException('Desteklenmeyen ZIP sıkıştırma yöntemi: '.$method);
            $out[$name]=['name'=>$name,'method'=>$method,'csize'=>(int)$h['csize'],'usize'=>(int)$h['usize'],'crc'=>(int)$h['crc'],'offset'=>(int)$h['localoff']];
        }
        return $out;
    } finally {fclose($fh);}
}
function asay_zip_file_data_offset($fh,array $entry): int {
    fseek($fh,(int)$entry['offset']);$sig=fread($fh,4);if($sig!=="PK\x03\x04")throw new RuntimeException('ZIP yerel başlığı bozuk.');
    $raw=fread($fh,26);if($raw===false||strlen($raw)!==26)throw new RuntimeException('ZIP yerel başlığı okunamadı.');
    $lh=unpack('vver/vflags/vmethod/vmtime/vmdate/Vcrc/Vcsize/Vusize/vfnlen/vextralen',$raw);if(!$lh)throw new RuntimeException('ZIP yerel başlığı çözülemedi.');
    return (int)$entry['offset']+30+(int)$lh['fnlen']+(int)$lh['extralen'];
}
function asay_zip_file_extract_to(string $zipPath,array $entry,string $dest): void {
    $in=@fopen($zipPath,'rb');if(!$in)throw new RuntimeException('ZIP dosyası açılamadı.');$out=@fopen($dest,'wb');if(!$out){fclose($in);throw new RuntimeException('ZIP hedef dosyası oluşturulamadı.');}
    try{
        $start=asay_zip_file_data_offset($in,$entry);fseek($in,$start);$remaining=(int)$entry['csize'];$written=0;$method=(int)$entry['method'];$ctx=null;$crcCtx=hash_init('crc32b');
        if($method===8){if(!function_exists('inflate_init'))throw new RuntimeException('ZIP deflate için zlib desteği gerekli.');$ctx=inflate_init(ZLIB_ENCODING_RAW);if($ctx===false)throw new RuntimeException('ZIP deflate başlatılamadı.');}
        while($remaining>0){
            $take=min(1048576,$remaining);$chunk=fread($in,$take);if($chunk===false||$chunk==='')throw new RuntimeException('ZIP içeriği eksik.');$remaining-=strlen($chunk);
            $data=$method===0?$chunk:inflate_add($ctx,$chunk,$remaining===0?ZLIB_FINISH:ZLIB_SYNC_FLUSH);if($data===false)throw new RuntimeException('ZIP girdisi açılamadı: '.$entry['name']);
            if($data!==''){if(fwrite($out,$data)===false)throw new RuntimeException('ZIP hedefi yazılamadı: '.$entry['name']);hash_update($crcCtx,$data);$written+=strlen($data);}
        }
        if($written!==(int)$entry['usize'])throw new RuntimeException('ZIP dosya boyutu doğrulanamadı: '.$entry['name']);
        $actual=strtolower(hash_final($crcCtx));$expected=str_pad(strtolower(dechex(((int)$entry['crc']) & 0xffffffff)),8,'0',STR_PAD_LEFT);
        if($actual!==$expected)throw new RuntimeException('ZIP CRC doğrulaması başarısız: '.$entry['name']);
    } finally {fclose($in);fclose($out);}
}
function asay_zip_file_read_entry(string $zipPath,array $entry,int $maxBytes=67108864): string {
    if((int)$entry['usize']>$maxBytes)throw new RuntimeException('ZIP girdisi bellek sınırını aşıyor: '.$entry['name']);$tmp=tempnam(sys_get_temp_dir(),'asay_zip_read_');if($tmp===false)throw new RuntimeException('Geçici ZIP dosyası oluşturulamadı.');
    try{asay_zip_file_extract_to($zipPath,$entry,$tmp);$data=file_get_contents($tmp);if($data===false)throw new RuntimeException('ZIP girdisi okunamadı.');return $data;}finally{@unlink($tmp);}
}
