<?php
declare(strict_types=1);
require_once __DIR__.'/../app/bootstrap.php';
require_login();$u=current_user();require_app_write_permission('supplier',$u);
if($_SERVER['REQUEST_METHOD']!=='POST')json_response(['ok'=>false,'error'=>'Yalnız POST desteklenir.'],405);
$in=sanitize_state_value(json_decode(file_get_contents('php://input'),true)?:[]);
$name=trim((string)($in['name']??''));if($name==='')json_response(['ok'=>false,'error'=>'Tedarikçi firma adı zorunlu.'],422);
$row=['name'=>$name,'contact'=>trim((string)($in['contact']??'')),'email'=>trim((string)($in['email']??'')),'phone'=>trim((string)($in['phone']??'')),'country'=>trim((string)($in['country']??'')),'note'=>trim((string)($in['note']??''))];
$createAccount=!empty($in['createAccount']);$currency=strtoupper((string)($in['currency']??'EUR'));if(!in_array($currency,['TRY','EUR','USD'],true))$currency='EUR';
if($createAccount&&!has_app_write_permission('finance',$u))json_response(['ok'=>false,'error'=>'Tedarikçi carisi oluşturmak için Cari/Finans yazma yetkisi gerekli.'],403);
function sd_norm(string $v): string{return mb_strtoupper(trim(preg_replace('/\s+/u',' ',$v)??$v),'UTF-8');}
try{
 $pdo=db();$pdo->beginTransaction();$q=$pdo->query('SELECT state_json,revision FROM app_state WHERE id=1 FOR UPDATE');$dbrow=$q->fetch();if(!$dbrow)throw new RuntimeException('Uygulama state kaydı bulunamadı.');$st=json_decode((string)$dbrow['state_json'],true)?:[];$st['supplierDirectory']=is_array($st['supplierDirectory']??null)?$st['supplierDirectory']:[];
 $idx=null;foreach($st['supplierDirectory'] as $i=>$x)if(sd_norm((string)($x['name']??''))===sd_norm($name)){$idx=$i;break;}
 if($idx===null){$max=0;foreach($st['supplierDirectory'] as $x)$max=max($max,(int)($x['id']??0));$row['id']=$max+1;$st['supplierDirectory'][]=$row;}else{$row['id']=(int)($st['supplierDirectory'][$idx]['id']??0);$st['supplierDirectory'][$idx]=array_merge($st['supplierDirectory'][$idx],$row);}
 $accountCreated=false;
 if($createAccount){$st['accounts']=is_array($st['accounts']??null)?$st['accounts']:[];$exists=false;$partyKey='';foreach($st['accounts'] as $a)if(($a['type']??'')==='supplier'&&sd_norm((string)($a['name']??''))===sd_norm($name)){if(($a['currency']??'EUR')===$currency)$exists=true;if(!$partyKey)$partyKey=(string)($a['partyKey']??'');}
   if(!$exists){$max=0;foreach($st['accounts'] as $a)$max=max($max,(int)($a['id']??0));$id=$max+1;$partyKey=$partyKey?:('pty-'.$id);$st['accounts'][]=['id'=>$id,'partyKey'=>$partyKey,'type'=>'supplier','name'=>$name,'currency'=>$currency,'total'=>0,'request'=>'','requestOpen'=>0,'entries'=>[],'contact'=>$row['contact'],'email'=>$row['email'],'phone'=>$row['phone'],'country'=>$row['country'],'note'=>$row['note']];$accountCreated=true;}
 }
 $st=sanitize_state_value($st);$rev=(int)$dbrow['revision']+1;$up=$pdo->prepare('UPDATE app_state SET state_json=?,updated_by=?,revision=? WHERE id=1');$up->execute([json_encode($st,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),(int)$u['id'],$rev]);save_module_states($pdo,$st,(int)$u['id'],['supplierDirectory','accounts']);$pdo->commit();audit('supplier_directory_upsert','supplier_directory',(string)($row['id']??''),['name'=>$name,'account_created'=>$accountCreated,'currency'=>$currency]);$clientState=filter_state_for_user($st,$u);json_response(['ok'=>true,'supplier'=>$row,'accountCreated'=>$accountCreated,'state'=>$clientState,'keyHashes'=>state_hashes_for_client($st,$u),'revision'=>$rev]);
}catch(Throwable $e){if(isset($pdo)&&$pdo->inTransaction())$pdo->rollBack();json_response(['ok'=>false,'error'=>public_error($e,'Tedarikçi kaydedilemedi.')],500);}
