<?php
declare(strict_types=1);
require_once __DIR__.'/../app/bootstrap.php';
require_login();

if($_SERVER['REQUEST_METHOD']!=='POST')json_response(['ok'=>false,'error'=>'POST gerekli'],405);
if(empty($_FILES['file'])||!is_uploaded_file($_FILES['file']['tmp_name']))json_response(['ok'=>false,'error'=>'Excel / CSV dosyası seçin.'],422);
$f=$_FILES['file'];if((int)$f['size']>10*1024*1024)json_response(['ok'=>false,'error'=>'Dosya en fazla 10 MB olabilir.'],413);
$ext=strtolower(pathinfo((string)$f['name'],PATHINFO_EXTENSION));
if(!in_array($ext,['xlsx','csv'],true))json_response(['ok'=>false,'error'=>'Yalnız .xlsx veya .csv yükleyin.'],422);

function excel_col_index(string $ref): int {
  preg_match('/^[A-Z]+/i',$ref,$m);$s=strtoupper($m[0]??'A');$n=0;
  for($i=0;$i<strlen($s);$i++)$n=$n*26+(ord($s[$i])-64);
  return max(0,$n-1);
}
function zip_entry_fallback(string $zipData,string $wanted): string|false {
  $eocd=strrpos($zipData,"PK\x05\x06");
  if($eocd===false||strlen($zipData)<$eocd+22)return false;
  $e=unpack('vdisk/vdiskStart/ventriesDisk/ventries/VcdSize/VcdOffset/vcommentLen',substr($zipData,$eocd+4,18));
  if(!$e)return false;
  $pos=(int)$e['cdOffset'];$limit=$pos+(int)$e['cdSize'];
  while($pos+46<=strlen($zipData)&&$pos<$limit){
    if(substr($zipData,$pos,4)!=="PK\x01\x02")break;
    $h=unpack('vverMade/vverNeed/vflags/vmethod/vmtime/vmdate/Vcrc/Vcsize/Vusize/vfnlen/vextralen/vcommentlen/vdisk/vintattr/Vextattr/Vlocaloff',substr($zipData,$pos+4,42));
    if(!$h)return false;
    $name=substr($zipData,$pos+46,(int)$h['fnlen']);
    if($name===$wanted){
      $lo=(int)$h['localoff'];
      if(substr($zipData,$lo,4)!=="PK\x03\x04")return false;
      $lh=unpack('vver/vflags/vmethod/vmtime/vmdate/Vcrc/Vcsize/Vusize/vfnlen/vextralen',substr($zipData,$lo+4,26));
      if(!$lh)return false;
      $start=$lo+30+(int)$lh['fnlen']+(int)$lh['extralen'];
      $compressed=substr($zipData,$start,(int)$h['csize']);
      $method=(int)$h['method'];
      if($method===0)return $compressed;
      if($method===8){
        if(!function_exists('gzinflate'))throw new RuntimeException('XLSX sıkıştırmasını açmak için PHP zlib desteği gerekli.');
        $out=@gzinflate($compressed);
        if($out===false)throw new RuntimeException('XLSX içeriği açılamadı (deflate).');
        return $out;
      }
      throw new RuntimeException('XLSX içinde desteklenmeyen ZIP sıkıştırma yöntemi: '.$method);
    }
    $pos+=46+(int)$h['fnlen']+(int)$h['extralen']+(int)$h['commentlen'];
  }
  return false;
}
function xlsx_entry(string $path,string $entry): string|false {
  if(class_exists('ZipArchive')){
    $z=new ZipArchive();
    if($z->open($path)===true){
      $out=$z->getFromName($entry);
      $z->close();
      if($out!==false)return $out;
    }
  }
  $data=@file_get_contents($path);
  if($data===false)throw new RuntimeException('XLSX dosyası okunamadı.');
  return zip_entry_fallback($data,$entry);
}
function xlsx_xml_decode(string $v): string {
  return html_entity_decode(strip_tags($v),ENT_QUOTES|ENT_XML1,'UTF-8');
}
function xlsx_attr(string $attrs,string $name): string {
  if(preg_match('/(?:^|\s)'.preg_quote($name,'/').'="([^"]*)"/i',$attrs,$m))return xlsx_xml_decode($m[1]);
  if(preg_match("/(?:^|\s)".preg_quote($name,'/')."='([^']*)'/i",$attrs,$m))return xlsx_xml_decode($m[1]);
  return '';
}
function xlsx_text_nodes(string $xml): string {
  $out='';
  if(preg_match_all('/<(?:[A-Za-z0-9_]+:)?t\b[^>]*>(.*?)<\/(?:[A-Za-z0-9_]+:)?t>/si',$xml,$m)){
    foreach($m[1] as $v)$out.=xlsx_xml_decode($v);
  }
  return $out;
}
function xlsx_rows(string $path): array {
  $shared=[];
  $ss=xlsx_entry($path,'xl/sharedStrings.xml');
  if($ss!==false&&preg_match_all('/<(?:[A-Za-z0-9_]+:)?si\b[^>]*>(.*?)<\/(?:[A-Za-z0-9_]+:)?si>/si',$ss,$sis)){
    foreach($sis[1] as $si)$shared[]=xlsx_text_nodes($si);
  }

  $sheet=xlsx_entry($path,'xl/worksheets/sheet1.xml');
  if($sheet===false)throw new RuntimeException('İlk Excel sayfası bulunamadı.');

  $rows=[];
  if(!preg_match_all('/<(?:[A-Za-z0-9_]+:)?row\b[^>]*>(.*?)<\/(?:[A-Za-z0-9_]+:)?row>/si',$sheet,$rowMatches))return $rows;
  foreach($rowMatches[1] as $rowXml){
    $out=[];
    if(preg_match_all('/<(?:[A-Za-z0-9_]+:)?c\b([^>]*)>(.*?)<\/(?:[A-Za-z0-9_]+:)?c>/si',$rowXml,$cells,PREG_SET_ORDER)){
      foreach($cells as $cell){
        $attrs=$cell[1];$body=$cell[2];
        $ref=xlsx_attr($attrs,'r');if($ref==='')continue;
        $idx=excel_col_index($ref);$type=xlsx_attr($attrs,'t');$v='';
        if($type==='s'){
          if(preg_match('/<(?:[A-Za-z0-9_]+:)?v\b[^>]*>(.*?)<\/(?:[A-Za-z0-9_]+:)?v>/si',$body,$vm))$v=$shared[(int)xlsx_xml_decode($vm[1])]??'';
        } elseif($type==='inlineStr'){
          $v=xlsx_text_nodes($body);
        } elseif($type==='str'){
          if(preg_match('/<(?:[A-Za-z0-9_]+:)?v\b[^>]*>(.*?)<\/(?:[A-Za-z0-9_]+:)?v>/si',$body,$vm))$v=xlsx_xml_decode($vm[1]);
        } elseif($type==='b'){
          if(preg_match('/<(?:[A-Za-z0-9_]+:)?v\b[^>]*>(.*?)<\/(?:[A-Za-z0-9_]+:)?v>/si',$body,$vm))$v=trim(xlsx_xml_decode($vm[1]))==='1'?'TRUE':'FALSE';
        } else {
          if(preg_match('/<(?:[A-Za-z0-9_]+:)?v\b[^>]*>(.*?)<\/(?:[A-Za-z0-9_]+:)?v>/si',$body,$vm))$v=xlsx_xml_decode($vm[1]);
          elseif(preg_match('/<(?:[A-Za-z0-9_]+:)?is\b[^>]*>(.*?)<\/(?:[A-Za-z0-9_]+:)?is>/si',$body,$im))$v=xlsx_text_nodes($im[1]);
        }
        $out[$idx]=$v;
      }
    }
    if($out){
      $max=max(array_keys($out));$dense=[];
      for($i=0;$i<=$max;$i++)$dense[]=$out[$i]??'';
      $rows[]=$dense;
    }
    if(count($rows)>=1001)break;
  }
  return $rows;
}
function csv_rows(string $path): array {
  $h=fopen($path,'rb');if(!$h)throw new RuntimeException('CSV açılamadı.');
  $first=fgets($h);rewind($h);$delim=(substr_count((string)$first,';')>substr_count((string)$first,','))?';':',';
  $rows=[];while(($r=fgetcsv($h,0,$delim))!==false){$rows[]=$r;if(count($rows)>=1001)break;}fclose($h);return $rows;
}
try{
  $rows=$ext==='xlsx'?xlsx_rows($f['tmp_name']):csv_rows($f['tmp_name']);
  if(!$rows)throw new RuntimeException('Dosyada veri bulunamadı.');
  $headers=array_map(fn($v)=>trim((string)$v),array_shift($rows));
  $headers=array_map(fn($v,$i)=>$v!==''?$v:'Sütun '.($i+1),$headers,array_keys($headers));
  $rows=array_values(array_filter($rows,fn($r)=>count(array_filter($r,fn($v)=>trim((string)$v)!==''))>0));
  json_response(['ok'=>true,'filename'=>$f['name'],'headers'=>$headers,'rows'=>array_slice($rows,0,1000),'total'=>count($rows)]);
}catch(Throwable $e){json_response(['ok'=>false,'error'=>public_error($e,'Excel dosyası okunamadı veya desteklenmeyen içerik var.')],422);}