<?php
declare(strict_types=1);
require_once __DIR__.'/../app/bootstrap.php';
require_once __DIR__.'/../app/simple_zip.php';
require_login();

$mode=(string)($_GET['mode']??'requests');
if($mode!=='requests'){
  $file=__DIR__.'/../templates/Cari_Kart_Aktarim_Sablonu.xlsx';
  if(!is_file($file))json_response(['ok'=>false,'error'=>'Cari kart şablonu bulunamadı'],404);
  header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
  header('Content-Disposition: attachment; filename="Cari_Kart_Aktarim_Sablonu.xlsx"');
  header('Content-Length: '.filesize($file));readfile($file);exit;
}

function xx($v): string {return htmlspecialchars((string)$v,ENT_XML1|ENT_QUOTES,'UTF-8');}
function xlsx_col(int $n): string {$s='';for($n++;$n>0;$n=intdiv($n-1,26))$s=chr(65+(($n-1)%26)).$s;return $s;}
function inline_cell(string $ref,$value,int $style=0): string {
  $s=$style?' s="'.$style.'"':'';
  return '<c r="'.$ref.'" t="inlineStr"'.$s.'><is><t xml:space="preserve">'.xx($value).'</t></is></c>';
}
function row_xml(int $row,array $values,int $style=0): string {
  $xml='<row r="'.$row.'">';
  foreach(array_values($values) as $i=>$v)$xml.=inline_cell(xlsx_col($i).$row,$v,$style);
  return $xml.'</row>';
}
function normalize_name(string $v): string {
  $v=mb_strtoupper(trim(preg_replace('/\s+/u',' ',$v)??$v),'UTF-8');
  return $v;
}

$pdo=db();
$stateRaw=$pdo->query('SELECT state_json FROM app_state WHERE id=1')->fetchColumn();
$state=$stateRaw?json_decode((string)$stateRaw,true):[];
$customers=[];
$seenParty=[];$seenName=[];
foreach(($state['accounts']??[]) as $a){
  $type=(string)($a['type']??'customer');
  if(!in_array($type,['customer','public','private'],true))continue;
  $name=trim((string)($a['name']??''));if($name==='')continue;
  $party=(string)($a['partyKey']??('pty-'.($a['id']??0)));
  $nk=normalize_name($name);
  if(isset($seenParty[$party])||isset($seenName[$nk]))continue;
  $seenParty[$party]=true;$seenName[$nk]=true;
  $label=['customer'=>'Müşteri','public'=>'Resmî Kurum','private'=>'Özel Kurum'][$type]??'Müşteri';
  $customers[]=['name'=>$name,'type'=>$label,'partyKey'=>$party,'taxNo'=>(string)($a['taxNo']??'')];
}
usort($customers,fn($a,$b)=>strnatcasecmp($a['name'],$b['name']));

$tmp=tempnam(sys_get_temp_dir(),'asayreqtpl');
if(class_exists('ZipArchive')){$zip=new ZipArchive();$opened=$zip->open($tmp,ZipArchive::OVERWRITE)===true;}
else{$zip=new AsaySimpleZip();$opened=$zip->open($tmp);}
if(!$opened)json_response(['ok'=>false,'error'=>'Excel şablonu oluşturulamadı'],500);

$contentTypes='<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
<Default Extension="xml" ContentType="application/xml"/>
<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
<Override PartName="/xl/worksheets/sheet2.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
<Override PartName="/xl/worksheets/sheet3.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>
</Types>';
$zip->addFromString('[Content_Types].xml',$contentTypes);
$zip->addFromString('_rels/.rels','<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');

$workbook='<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
<sheets>
 <sheet name="Talepler" sheetId="1" r:id="rId1"/>
 <sheet name="Açıklamalar" sheetId="2" r:id="rId2"/>
 <sheet name="Müşteri Listesi" sheetId="3" r:id="rId3"/>
</sheets>
<definedNames><definedName name="CustomerList">\'Müşteri Listesi\'!$A$2:$A$'.max(2,count($customers)+1).'</definedName></definedNames>
</workbook>';
$zip->addFromString('xl/workbook.xml',$workbook);
$zip->addFromString('xl/_rels/workbook.xml.rels','<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>
<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet2.xml"/>
<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet3.xml"/>
<Relationship Id="rId4" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>
</Relationships>');

$styles='<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
<fonts count="3"><font><sz val="10"/><name val="Arial"/></font><font><b/><color rgb="FFFFFFFF"/><sz val="10"/><name val="Arial"/></font><font><b/><color rgb="FF163D73"/><sz val="10"/><name val="Arial"/></font></fonts>
<fills count="4"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FF163D73"/><bgColor indexed="64"/></patternFill></fill><fill><patternFill patternType="solid"><fgColor rgb="FFEAF0F8"/><bgColor indexed="64"/></patternFill></fill></fills>
<borders count="2"><border/><border><left style="thin"><color rgb="FFD8E0EA"/></left><right style="thin"><color rgb="FFD8E0EA"/></right><top style="thin"><color rgb="FFD8E0EA"/></top><bottom style="thin"><color rgb="FFD8E0EA"/></bottom></border></borders>
<cellStyleXfs count="1"><xf/></cellStyleXfs>
<cellXfs count="4"><xf fontId="0" fillId="0" borderId="0"/><xf fontId="1" fillId="2" borderId="1" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf><xf fontId="2" fillId="3" borderId="1" applyFont="1" applyFill="1" applyBorder="1"/><xf fontId="0" fillId="0" borderId="1" applyBorder="1"/></cellXfs>
</styleSheet>';
$zip->addFromString('xl/styles.xml',$styles);

$headers=['Müşteri / Kurum','Müşteri Tipi','Excel Referans / Talep No','Talep Başlığı','Para Birimi','Son Teklif Tarihi','Ülke','Yetkili','Telefon','E-posta','Vergi / Kurum No','Teslim Şekli','Teslim Yeri','Öncelik','Açıklama'];
$widths=[28,16,23,34,13,18,18,20,18,26,20,14,20,14,38];
$cols='<cols>';foreach($widths as $i=>$w)$cols.='<col min="'.($i+1).'" max="'.($i+1).'" width="'.$w.'" customWidth="1"/>';$cols.='</cols>';
$sheet1='<?xml version="1.0" encoding="UTF-8" standalone="yes"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetViews><sheetView workbookViewId="0"><pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>'.$cols.'<sheetData>';
$sheet1.=row_xml(1,$headers,1);
for($r=2;$r<=1000;$r++)$sheet1.='<row r="'.$r.'"/>';
$sheet1.='</sheetData><dataValidations count="5">
<dataValidation type="list" allowBlank="1" showErrorMessage="1" errorTitle="Geçersiz müşteri" error="Müşteri/Kurum listesinden seçim yapın veya mevcut cari adını birebir yazın." sqref="A2:A1000"><formula1>CustomerList</formula1></dataValidation>
<dataValidation type="list" allowBlank="1" sqref="B2:B1000"><formula1>"Müşteri,Resmî Kurum,Özel Kurum"</formula1></dataValidation>
<dataValidation type="list" allowBlank="1" sqref="E2:E1000"><formula1>"EUR,USD,TRY"</formula1></dataValidation>
<dataValidation type="list" allowBlank="1" sqref="L2:L1000"><formula1>"EXW,FCA,FOB,CFR,CIF,DAP,DDP"</formula1></dataValidation>
<dataValidation type="list" allowBlank="1" sqref="N2:N1000"><formula1>"Normal,Öncelikli,Acil"</formula1></dataValidation>
</dataValidations></worksheet>';
$zip->addFromString('xl/worksheets/sheet1.xml',$sheet1);

$info=[
 ['ASAY ERP – Talep / İhale Excel Aktarım Şablonu',''],
 ['Kural','Açıklama'],
 ['Müşteri / Kurum','Bu alanın açılır listesi şablon indirilirken ASAY ERP veritabanındaki mevcut müşteri ve kurum carilerinden oluşturulur.'],
 ['Yeni müşteri','Gerçekten yeni bir müşteri ise elle yazabilirsiniz. Sistem mevcut carilerle eşleşme kontrolü yapar; yakın/eşleşen kayıt bulunursa yeni cari açmaz.'],
 ['Tekrar önleme','Müşteri adı büyük/küçük harf, Türkçe karakter ve küçük yazım farkları için normalize edilir. Vergi/Kurum No varsa öncelikli eşleştirme anahtarıdır.'],
 ['Durum','Excel’den aktarılan tüm talepler otomatik olarak Fiyat Toplanıyor durumunda açılır.'],
 ['Bağlantı','Aktarım tedarikçi, teklif, sipariş veya evrak oluşturmaz; yalnız Talep + Müşteri Cari bağlantısı kurar.'],
 ['Tarih formatı','Son Teklif Tarihi: YYYY-AA-GG. Örn: 2026-12-31.'],
 ['Şablon güncelliği','Yeni cari ekledikten sonra müşteri açılır listesinin güncel olması için şablonu yeniden indirin.']
];
$sheet2='<?xml version="1.0" encoding="UTF-8" standalone="yes"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><cols><col min="1" max="1" width="24" customWidth="1"/><col min="2" max="2" width="88" customWidth="1"/></cols><sheetData>';
foreach($info as $i=>$row)$sheet2.=row_xml($i+1,$row,$i===0?1:($i===1?2:3));
$sheet2.='</sheetData></worksheet>';
$zip->addFromString('xl/worksheets/sheet2.xml',$sheet2);

$sheet3='<?xml version="1.0" encoding="UTF-8" standalone="yes"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><cols><col min="1" max="1" width="42" customWidth="1"/><col min="2" max="2" width="18" customWidth="1"/><col min="3" max="3" width="24" customWidth="1"/></cols><sheetData>';
$sheet3.=row_xml(1,['Müşteri / Kurum','Cari Tipi','Vergi / Kurum No'],1);
$r=2;foreach($customers as $c){$sheet3.=row_xml($r++,[$c['name'],$c['type'],$c['taxNo']],3);}
if(!$customers)$sheet3.=row_xml(2,['Henüz müşteri/kurum carisi yok','',''],3);
$sheet3.='</sheetData></worksheet>';
$zip->addFromString('xl/worksheets/sheet3.xml',$sheet3);

$zip->close();
audit('request_import_template_download','request',null,['customers'=>count($customers)]);
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="Talep_Ihale_Aktarim_Sablonu_Guncel.xlsx"');
header('Content-Length: '.filesize($tmp));
readfile($tmp);@unlink($tmp);
