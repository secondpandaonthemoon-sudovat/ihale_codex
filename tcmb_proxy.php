<?php
declare(strict_types=1);
require_once __DIR__.'/app/bootstrap.php';
require_login();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: private, max-age=60');
const TCMB_CACHE_TTL=900;
$requestedDate=trim((string)($_GET['date']??''));
$isHistorical=$requestedDate!=='';
$url='https://www.tcmb.gov.tr/kurlar/today.xml';
$cachePath=__DIR__.'/storage/tcmb_rates.json';
if($isHistorical){
  $safeDate=str_replace('-','',$requestedDate);
  $cacheDir=__DIR__.'/storage/tcmb_history';
  if(!is_dir($cacheDir))@mkdir($cacheDir,0775,true);
  $cachePath=$cacheDir.'/'.$safeDate.'.json';
}

function tcmb_fail(string $m,int $s=502): never{http_response_code($s);echo json_encode(['ok'=>false,'error'=>$m],JSON_UNESCAPED_UNICODE);exit;}
if($isHistorical&&!preg_match('/^\d{4}-\d{2}-\d{2}$/',$requestedDate))tcmb_fail('Geçersiz tarih formatı.',400);
function tcmb_num(string $v): float{return (float)str_replace(',','.',trim($v));}
function tcmb_parse_regex(string $xml,string $code): array{
 if(!preg_match('/<Currency\b[^>]*(?:CurrencyCode|Kod)\s*=\s*["\']'.preg_quote($code,'/').'["\'][^>]*>(.*?)<\/Currency>/si',$xml,$m))return [];
 $b=$m[1];$read=function(string $tag)use($b){return preg_match('/<'.$tag.'\b[^>]*>(.*?)<\/'.$tag.'>/si',$b,$x)?tcmb_num(strip_tags($x[1])):0.0;};
 return ['buying'=>$read('ForexBuying'),'selling'=>$read('ForexSelling'),'banknote_buying'=>$read('BanknoteBuying'),'banknote_selling'=>$read('BanknoteSelling')];
}
function tcmb_meta_regex(string $xml): array{
 $date='';$bulletin='';if(preg_match('/<Tarih_Date\b([^>]*)>/i',$xml,$m)){$attrs=$m[1];if(preg_match('/\bTarih=["\']([^"\']+)["\']/i',$attrs,$x))$date=$x[1];if(preg_match('/\bBulten_No=["\']([^"\']+)["\']/i',$attrs,$x))$bulletin=$x[1];}return [$date,$bulletin];
}
function tcmb_cached(string $path,bool $allowStale=false): ?array{
 if(!is_file($path))return null;$raw=@file_get_contents($path);$j=$raw?json_decode($raw,true):null;if(!is_array($j)||empty($j['usd']['selling'])||empty($j['eur']['selling']))return null;
 $age=time()-(int)($j['_cached_at']??0);if(!$allowStale&&$age>TCMB_CACHE_TTL)return null;$j['cache_age']=$age;$j['cached']=true;unset($j['_cached_at']);return $j;
}
$cached=tcmb_cached($cachePath,$isHistorical);if($cached){echo json_encode($cached,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);exit;}
$xmlText=false;$effectiveRequested=$requestedDate;
if($isHistorical){
  $base=new DateTimeImmutable($requestedDate,new DateTimeZone('Europe/Istanbul'));
  for($i=0;$i<10;$i++){
    $d=$base->modify('-'.$i.' day');$ym=$d->format('Ym');$dmY=$d->format('dmY');
    $tryUrl='https://www.tcmb.gov.tr/kurlar/'.$ym.'/'.$dmY.'.xml';
    $try=false;
    if(function_exists('curl_init')){$ch=curl_init($tryUrl);curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>10,CURLOPT_CONNECTTIMEOUT=>5,CURLOPT_FOLLOWLOCATION=>true,CURLOPT_USERAGENT=>'ASAY-ERP/'.ASAY_APP_VERSION]);$try=curl_exec($ch);$code=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE);curl_close($ch);if($code>=400)$try=false;}
    if($try===false&&filter_var(ini_get('allow_url_fopen'),FILTER_VALIDATE_BOOLEAN))$try=@file_get_contents($tryUrl);
    if($try!==false&&strlen((string)$try)>200){$xmlText=$try;$effectiveRequested=$d->format('Y-m-d');break;}
  }
}else{

if(function_exists('curl_init')){$ch=curl_init($url);curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>12,CURLOPT_CONNECTTIMEOUT=>6,CURLOPT_FOLLOWLOCATION=>true,CURLOPT_USERAGENT=>'ASAY-ERP/'.ASAY_APP_VERSION]);$xmlText=curl_exec($ch);curl_close($ch);if($xmlText===false)$xmlText=false;}
if($xmlText===false&&filter_var(ini_get('allow_url_fopen'),FILTER_VALIDATE_BOOLEAN))$xmlText=@file_get_contents($url);
}
if(!$xmlText){
 if(!$isHistorical){$stale=tcmb_cached($cachePath,true);if($stale){$stale['stale']=true;$stale['warning']='TCMB erişilemedi; son başarılı sunucu kaydı kullanılıyor.';echo json_encode($stale,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);exit;}}
 tcmb_fail($isHistorical?'İstenen tarih için TCMB arşiv kuru bulunamadı.':'TCMB XML alınamadı.');
}
$usd=[];$eur=[];$date='';$bulletin='';
if(function_exists('simplexml_load_string')){libxml_use_internal_errors(true);$xml=@simplexml_load_string($xmlText);if($xml){foreach($xml->Currency as $c){$code=(string)$c['CurrencyCode'];if($code==='USD')$usd=['buying'=>(float)$c->ForexBuying,'selling'=>(float)$c->ForexSelling,'banknote_buying'=>(float)$c->BanknoteBuying,'banknote_selling'=>(float)$c->BanknoteSelling];if($code==='EUR')$eur=['buying'=>(float)$c->ForexBuying,'selling'=>(float)$c->ForexSelling,'banknote_buying'=>(float)$c->BanknoteBuying,'banknote_selling'=>(float)$c->BanknoteSelling];}$date=(string)$xml['Tarih'];$bulletin=(string)$xml['Bulten_No'];}}
if(empty($usd['selling'])||empty($eur['selling'])){$usd=tcmb_parse_regex($xmlText,'USD');$eur=tcmb_parse_regex($xmlText,'EUR');[$date,$bulletin]=tcmb_meta_regex($xmlText);}
if(empty($usd['selling'])||empty($eur['selling']))tcmb_fail('USD/EUR kuru bulunamadı.');
$out=['ok'=>true,'source'=>'TCMB','date'=>$date,'requested_date'=>$requestedDate?:null,'effective_date'=>$isHistorical?$effectiveRequested:null,'historical'=>$isHistorical,'bulletin_no'=>$bulletin,'usd'=>$usd,'eur'=>$eur,'eur_usd'=>$eur['selling']/$usd['selling'],'cached'=>false,'fetched_at'=>date(DATE_ATOM)];
$store=$out;$store['_cached_at']=time();if(!is_dir(dirname($cachePath)))@mkdir(dirname($cachePath),0775,true);@file_put_contents($cachePath,json_encode($store,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),LOCK_EX);@chmod($cachePath,0600);
echo json_encode($out,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
