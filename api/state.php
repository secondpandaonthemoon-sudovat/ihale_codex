<?php
declare(strict_types=1);
require_once __DIR__.'/../app/bootstrap.php';
require_login();
$action=$_GET['action']??'load';
function amount_number($v): float { if(is_numeric($v))return (float)$v; $s=preg_replace('/[^0-9,.-]/u','',(string)$v);$s=str_replace(['. ','.'],['',''],$s);$s=str_replace(',','.',$s);return is_numeric($s)?(float)$s:0.0; }
function ensure_request_item_mirror_schema(PDO $pdo): void {
 try{
   $exists=(bool)$pdo->query("SHOW TABLES LIKE 'request_items'")->fetchColumn();
   if(!$exists)throw new RuntimeException('request_items tablosu bulunamadı.');
   $q=$pdo->query("SHOW COLUMNS FROM request_items LIKE 'source_item_id'");
   if(!$q->fetch())throw new RuntimeException('Güncel veritabanı şeması gerekli. Lütfen upgrade.php dosyasını bir kez çalıştırın.');
 }catch(Throwable $e){throw new RuntimeException('Talep kalem mirror şeması hazır değil: '.$e->getMessage(),0,$e);}
}
function mirror_delete_stale_ids(PDO $pdo,string $table,string $column,array $ids): void {
 $allowed=[
  'requests'=>['id'],'request_items'=>['id'],'supplier_quotes'=>['id'],'customer_quotes'=>['request_id'],
  'sales_orders'=>['id'],'purchase_orders'=>['id'],'delivery_batches'=>['id'],'commercial_documents'=>['document_uid'],
  'cash_accounts'=>['id']
 ];
 if(!isset($allowed[$table])||!in_array($column,$allowed[$table],true))throw new RuntimeException('Geçersiz mirror tablo temizleme hedefi.');
 $ids=array_values(array_unique(array_filter($ids,fn($v)=>$v!==null&&$v!=='')));
 if(!$ids){$pdo->exec("DELETE FROM `$table`");return;}
 $ph=implode(',',array_fill(0,count($ids),'?'));
 $q=$pdo->prepare("DELETE FROM `$table` WHERE `$column` NOT IN ($ph)");$q->execute($ids);
}
function sync_normalized(array $state): void {
 $pdo=db();
 ensure_request_item_mirror_schema($pdo);
 // app_state is the canonical source. Normalized tables are query/report mirrors only.
 // Stable-identity mirrors are UPSERTed incrementally; only transaction mirrors without durable IDs are rebuilt.
 $pdo->exec('DELETE FROM account_transactions');
 $pdo->exec('DELETE FROM cash_transactions');
 $pdo->exec('DELETE FROM delivery_batch_items');

 // ---- Parties: one canonical keyed party per business entity. ----
 $partyMap=[];$partyKeys=[];
 foreach(($state['accounts']??[]) as $a){
   $name=trim((string)($a['name']??''));if($name==='')continue;
   $type=in_array($a['type']??'', ['customer','supplier','public','private','customs','freight','service'],true)?$a['type']:'customer';
   $pkey=trim((string)($a['partyKey']??''));
   if($pkey==='')$pkey='legacy-'.substr(hash('sha256',mb_strtoupper($name,'UTF-8').'|'.$type),0,24);
   if(isset($partyMap[$pkey]))continue;
   $s=$pdo->prepare('SELECT id FROM parties WHERE party_key=? LIMIT 1');$s->execute([$pkey]);$id=$s->fetchColumn();
   if(!$id){
     // Reuse an old NULL-key duplicate when the same name/type exists, instead of creating another party.
     $s=$pdo->prepare('SELECT id FROM parties WHERE party_key IS NULL AND party_type=? AND LOWER(TRIM(name))=LOWER(TRIM(?)) ORDER BY id DESC LIMIT 1');
     $s->execute([$type,$name]);$id=$s->fetchColumn();
     if($id){
       $pdo->prepare('UPDATE parties SET party_key=?,party_type=?,name=?,tax_no=?,contact_name=?,email=?,phone=?,country=?,address=? WHERE id=?')
           ->execute([$pkey,$type,$name,$a['taxNo']??null,$a['contact']??null,$a['email']??null,$a['phone']??null,$a['country']??null,$a['address']??null,$id]);
     }else{
       $s=$pdo->prepare('INSERT INTO parties(party_type,party_key,name,tax_no,contact_name,email,phone,country,address) VALUES(?,?,?,?,?,?,?,?,?)');
       $s->execute([$type,$pkey,$name,$a['taxNo']??null,$a['contact']??null,$a['email']??null,$a['phone']??null,$a['country']??null,$a['address']??null]);$id=$pdo->lastInsertId();
     }
   }else{
     $pdo->prepare('UPDATE parties SET party_type=?,name=?,tax_no=?,contact_name=?,email=?,phone=?,country=?,address=? WHERE id=?')
         ->execute([$type,$name,$a['taxNo']??null,$a['contact']??null,$a['email']??null,$a['phone']??null,$a['country']??null,$a['address']??null,$id]);
   }
   $partyMap[$pkey]=(int)$id;$partyKeys[]=$pkey;
 }
 // Remove legacy NULL-key duplicates if a keyed canonical party with same type/name exists.
 $pdo->exec("UPDATE requests r JOIN parties oldp ON r.party_id=oldp.id JOIN parties newp ON newp.party_key IS NOT NULL AND newp.party_type=oldp.party_type AND LOWER(TRIM(newp.name))=LOWER(TRIM(oldp.name)) SET r.party_id=newp.id WHERE oldp.party_key IS NULL");
 $pdo->exec("DELETE oldp FROM parties oldp JOIN parties newp ON newp.party_key IS NOT NULL AND newp.party_type=oldp.party_type AND LOWER(TRIM(newp.name))=LOWER(TRIM(oldp.name)) WHERE oldp.party_key IS NULL");

 // ---- Requests ----
 $requestIds=[];$requestById=[];
 foreach(($state['requests']??[]) as $r){
   $rid=(int)($r['id']??0);$no=trim((string)($r['no']??''));if($rid<=0||$no==='')continue;
   $requestIds[]=$rid;$requestById[$rid]=$r;
   $partyId=null;$cpk=(string)($r['customerPartyKey']??'');if($cpk!==''&&isset($partyMap[$cpk]))$partyId=$partyMap[$cpk];
   if(!$partyId){
     foreach(($state['accounts']??[]) as $aa){
       if(($aa['name']??'')===($r['customer']??'')&&($aa['type']??'customer')!=='supplier'){
         $pk=(string)($aa['partyKey']??'');if($pk!==''&&isset($partyMap[$pk])){$partyId=$partyMap[$pk];break;}
       }
     }
   }
   $deadline=null;if(!empty($r['deadline'])){$x=(string)$r['deadline'];if(preg_match('/^(\d{2})\.(\d{2})\.(\d{4})$/',$x,$m))$deadline="$m[3]-$m[2]-$m[1]";elseif(preg_match('/^\d{4}-\d{2}-\d{2}$/',$x))$deadline=$x;}
   $payload=json_encode($r,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
   $q=$pdo->prepare('INSERT INTO requests(id,request_no,title,party_id,currency,status,deadline,payload_json) VALUES(?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE request_no=VALUES(request_no),title=VALUES(title),party_id=VALUES(party_id),currency=VALUES(currency),status=VALUES(status),deadline=VALUES(deadline),payload_json=VALUES(payload_json)');
   $q->execute([$rid,$no,$r['title']??'Talep',$partyId,$r['currency']??'EUR',$r['status']??'open',$deadline,$payload]);
 }
 mirror_delete_stale_ids($pdo,'requests','id',$requestIds);

 // ---- Request items: composite identity request_id + source_item_id prevents cross-request ID collisions. ----
 $itemMirrorMap=[];$requestItemMirrorIds=[];
 foreach(($state['requests']??[]) as $r){
   $rid=(int)($r['id']??0);if($rid<=0)continue;
   foreach(($r['items']??[]) as $it){
     $sourceId=(int)($it['id']??0);if($sourceId<=0)continue;
     $payload=$it;$payload['_source_item_id']=$sourceId;
     $q=$pdo->prepare('INSERT INTO request_items(request_id,source_item_id,item_name,qty,unit,brand_model,specification,payload_json) VALUES(?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE item_name=VALUES(item_name),qty=VALUES(qty),unit=VALUES(unit),brand_model=VALUES(brand_model),specification=VALUES(specification),payload_json=VALUES(payload_json),id=LAST_INSERT_ID(id)');
     $q->execute([$rid,$sourceId,$it['name']??'Kalem',(float)($it['qty']??0),$it['unit']??null,$it['brandModel']??null,$it['spec']??null,json_encode($payload,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)]);
     $mirrorId=(int)$pdo->lastInsertId();
     if($mirrorId<=0){$z=$pdo->prepare('SELECT id FROM request_items WHERE request_id=? AND source_item_id=?');$z->execute([$rid,$sourceId]);$mirrorId=(int)$z->fetchColumn();}
     $itemMirrorMap[$rid.':'.$sourceId]=$mirrorId;$requestItemMirrorIds[]=$mirrorId;
   }
 }
 mirror_delete_stale_ids($pdo,'request_items','id',$requestItemMirrorIds);

 // ---- Supplier quote mirror: qty × effective unit + freight, quote currency preserved. ----
 $supplierIds=[];
 foreach(($state['suppliers']??[]) as $sq){
   $sid=(int)($sq['id']??0);$rid=(int)($sq['requestId']??0);if($sid<=0||$rid<=0)continue;$supplierIds[]=$sid;
   $r=$requestById[$rid]??[];$qtyByItem=[];foreach(($r['items']??[]) as $it)$qtyByItem[(string)($it['id']??0)]=(float)($it['qty']??0);
   $tot=0.0;foreach(($sq['offers']??[]) as $iid=>$v)$tot+=(float)$v*(float)($qtyByItem[(string)$iid]??0);$tot+=(float)($sq['freight']??0);
   $cur=strtoupper((string)($sq['currency']??$r['currency']??'EUR'));if(!in_array($cur,['EUR','USD','TRY'],true))$cur='EUR';
   $q=$pdo->prepare('INSERT INTO supplier_quotes(id,request_id,supplier_name,reference_no,currency,total,payload_json) VALUES(?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE request_id=VALUES(request_id),supplier_name=VALUES(supplier_name),reference_no=VALUES(reference_no),currency=VALUES(currency),total=VALUES(total),payload_json=VALUES(payload_json)');
   $q->execute([$sid,$rid,$sq['name']??'Tedarikçi',$sq['ref']??null,$cur,$tot,json_encode($sq,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)]);
 }
 mirror_delete_stale_ids($pdo,'supplier_quotes','id',$supplierIds);

 // ---- Customer quotes ----
 $customerQuoteRequestIds=[];
 foreach(($state['requests']??[]) as $r){
   if(empty($r['customerQuote']))continue;$rid=(int)$r['id'];$customerQuoteRequestIds[]=$rid;$cq=$r['customerQuote'];
   $q=$pdo->prepare('INSERT INTO customer_quotes(request_id,quote_no,revision_no,status,currency,cost_total,sales_total,profit,payload_json) VALUES(?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE quote_no=VALUES(quote_no),revision_no=VALUES(revision_no),status=VALUES(status),currency=VALUES(currency),cost_total=VALUES(cost_total),sales_total=VALUES(sales_total),profit=VALUES(profit),payload_json=VALUES(payload_json)');
   $q->execute([$rid,$cq['no']??null,(int)($cq['revision']??0),$cq['status']??null,$r['currency']??'EUR',(float)($cq['cost']??0),(float)($cq['total']??0),(float)($cq['profit']??0),json_encode($cq,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)]);
 }
 mirror_delete_stale_ids($pdo,'customer_quotes','request_id',$customerQuoteRequestIds);

 // ---- Orders / POs / delivery batches ----
 $orderIds=[];$poIds=[];$batchIds=[];
 foreach(($state['orders']??[]) as $o){
   $oid=(int)($o['id']??0);if($oid<=0)continue;$orderIds[]=$oid;
   $q=$pdo->prepare('INSERT INTO sales_orders(id,request_id,order_no,customer_name,currency,total,customer_paid,customer_due,status,payload_json) VALUES(?,?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE request_id=VALUES(request_id),order_no=VALUES(order_no),customer_name=VALUES(customer_name),currency=VALUES(currency),total=VALUES(total),customer_paid=VALUES(customer_paid),customer_due=VALUES(customer_due),status=VALUES(status),payload_json=VALUES(payload_json)');
   $q->execute([$oid,(int)$o['requestId'],$o['no']??null,$o['customer']??null,$o['currency']??'EUR',(float)($o['total']??0),(float)($o['customerPaid']??0),(float)($o['customerDue']??0),!empty($o['open'])?'open':'closed',json_encode($o,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)]);
   foreach(($o['pos']??[]) as $po){
     $pid=(int)($po['id']??0);if($pid<=0)continue;$poIds[]=$pid;
     $x=$pdo->prepare('INSERT INTO purchase_orders(id,sales_order_id,po_no,supplier_name,status,total,paid,due,payload_json) VALUES(?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE sales_order_id=VALUES(sales_order_id),po_no=VALUES(po_no),supplier_name=VALUES(supplier_name),status=VALUES(status),total=VALUES(total),paid=VALUES(paid),due=VALUES(due),payload_json=VALUES(payload_json)');
     $x->execute([$pid,$oid,$po['no']??null,$po['supplier']??null,$po['status']??null,(float)($po['total']??0),(float)($po['paid']??0),(float)($po['due']??0),json_encode($po,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)]);
   }
   foreach(($o['deliveryBatches']??[]) as $batch){
     $bid=(int)($batch['id']??0);if($bid<=0)continue;$batchIds[]=$bid;
     $x=$pdo->prepare('INSERT INTO delivery_batches(id,sales_order_id,batch_no,status,planned_date,delivered_date,customer_amount,customer_collected,customer_due,payload_json,created_by) VALUES(?,?,?,?,?,?,?,?,?,?,NULL) ON DUPLICATE KEY UPDATE sales_order_id=VALUES(sales_order_id),batch_no=VALUES(batch_no),status=VALUES(status),planned_date=VALUES(planned_date),delivered_date=VALUES(delivered_date),customer_amount=VALUES(customer_amount),customer_collected=VALUES(customer_collected),customer_due=VALUES(customer_due),payload_json=VALUES(payload_json)');
     $x->execute([$bid,$oid,$batch['batchNo']??null,$batch['status']??'Planlandı',$batch['plannedDate']??null,$batch['deliveredDate']??null,(float)($batch['customerAmount']??0),(float)($batch['customerCollected']??0),(float)($batch['customerDue']??0),json_encode($batch,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)]);
     $rid=(int)($o['requestId']??0);
     foreach(($batch['items']??[]) as $bi){
       $sourceItemId=(int)($bi['itemId']??0);$mirrorId=$itemMirrorMap[$rid.':'.$sourceItemId]??null;
       $y=$pdo->prepare('INSERT INTO delivery_batch_items(batch_id,request_item_id,item_name,qty,unit,supplier_name,po_id,sale_total,cost_base,payload_json) VALUES(?,?,?,?,?,?,?,?,?,?)');
       $y->execute([$bid,$mirrorId,$bi['name']??'Kalem',(float)($bi['qty']??0),$bi['unit']??null,$bi['supplier']??null,(int)($bi['poId']??0)?:null,(float)($bi['saleTotal']??0),(float)($bi['costBase']??0),json_encode($bi,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)]);
     }
   }
 }
 mirror_delete_stale_ids($pdo,'sales_orders','id',$orderIds);
 mirror_delete_stale_ids($pdo,'purchase_orders','id',$poIds);
 mirror_delete_stale_ids($pdo,'delivery_batches','id',$batchIds);

 // ---- Commercial documents ----
 $docUids=[];
 foreach(($state['documents']??[]) as $d){
   $uid=(string)($d['uid']??$d['id']??('doc-'.substr(hash('sha256',json_encode($d)),0,20)));$docUids[]=$uid;
   $docNo=(string)($d['no']??$d['documentNo']??$d['number']??$uid);$revNo=(int)($d['revision']??$d['revisionNo']??0);
   $q=$pdo->prepare('INSERT INTO commercial_documents(document_uid,request_id,document_type,document_no,revision_no,payload_json) VALUES(?,?,?,?,?,?) ON DUPLICATE KEY UPDATE request_id=VALUES(request_id),document_type=VALUES(document_type),document_no=VALUES(document_no),revision_no=VALUES(revision_no),payload_json=VALUES(payload_json)');
   $q->execute([$uid,(int)($d['requestId']??0)?:null,$d['type']??'document',$docNo,$revNo,json_encode($d,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)]);
 }
 mirror_delete_stale_ids($pdo,'commercial_documents','document_uid',$docUids);

 // ---- Cash accounts ----
 $cashAccountIds=[];
 foreach(($state['cashAccounts']??[]) as $a){
   $aid=(int)($a['id']??0);if($aid<=0)continue;$cashAccountIds[]=$aid;
   $q=$pdo->prepare('INSERT INTO cash_accounts(id,name,account_type,currency,balance,iban,swift,payload_json) VALUES(?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE name=VALUES(name),account_type=VALUES(account_type),currency=VALUES(currency),balance=VALUES(balance),iban=VALUES(iban),swift=VALUES(swift),payload_json=VALUES(payload_json)');
   $q->execute([$aid,$a['name']??'Hesap',$a['type']??'Banka',$a['currency']??'EUR',(float)($a['balance']??0),$a['iban']??null,$a['swift']??null,json_encode($a,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)]);
 }
 mirror_delete_stale_ids($pdo,'cash_accounts','id',$cashAccountIds);

 // ---- Transaction mirrors ----
 foreach(($state['accounts']??[]) as $a){
   $pkey=(string)($a['partyKey']??'');$pid=$partyMap[$pkey]??null;
   foreach(($a['entries']??[]) as $e){
     $date=(string)($e['date']??date('Y-m-d'));if(!preg_match('/^\d{4}-\d{2}-\d{2}$/',$date))$date=date('Y-m-d');
     $q=$pdo->prepare('INSERT INTO account_transactions(party_id,currency,txn_date,reference_no,description,debit,credit) VALUES(?,?,?,?,?,?,?)');
     $q->execute([$pid,$a['currency']??'EUR',$date,$e['ref']??null,$e['desc']??'Cari Hareket',(float)($e['debit']??0),(float)($e['credit']??0)]);
   }
 }
 $curByAccount=[];foreach(($state['cashAccounts']??[]) as $ca)$curByAccount[$ca['name']??'']=$ca['currency']??null;
 foreach(($state['cash']??[]) as $c){
   $date=(string)($c['date']??'');if(preg_match('/^(\d{2})\.(\d{2})\.(\d{4})$/',$date,$m))$date="$m[3]-$m[2]-$m[1]";if(!preg_match('/^\d{4}-\d{2}-\d{2}$/',$date))$date=date('Y-m-d');
   $q=$pdo->prepare('INSERT INTO cash_transactions(txn_date,account_name,request_no,party_name,txn_type,currency,amount,note) VALUES(?,?,?,?,?,?,?,?)');
   $q->execute([$date,$c['account']??'Hesap',$c['request']??null,$c['party']??null,($c['type']??'Giriş')==='Çıkış'?'Çıkış':'Giriş',$curByAccount[$c['account']??'']??null,amount_number($c['amount']??0),$c['note']??null]);
 }
}


try{
 if($action==='load'){$pdo=db();$q=$pdo->query('SELECT state_json,updated_at,revision FROM app_state WHERE id=1');$row=$q->fetch();$st=$row?json_decode($row['state_json'],true):[];$u=current_user();$clientState=$u?filter_state_for_user(is_array($st)?$st:[],$u):[];json_response(['ok'=>true,'state'=>$clientState,'keyHashes'=>$u?state_hashes_for_client(is_array($st)?$st:[],$u):[],'updated_at'=>$row['updated_at']??null,'revision'=>(int)($row['revision']??0),'user'=>$u]);}
 if($action==='patch'||$action==='patch_beacon'){
  $u=current_user();if(!can_write_state($u))json_response(['ok'=>false,'error'=>'Bu kullanıcı yalnızca görüntüleme yetkisine sahip.'],403);
  $in=json_decode((string)file_get_contents('php://input'),true);if(!is_array($in))json_response(['ok'=>false,'error'=>'Geçersiz patch JSON'],422);
  $patch=sanitize_state_value(is_array($in['patch']??null)?$in['patch']:[]);$base=is_array($in['baseHashes']??null)?$in['baseHashes']:[];
  if(!$patch){if($action==='patch')json_response(['ok'=>true,'revision'=>(int)($_SERVER['HTTP_X_ASAY_REVISION']??0),'keyHashes'=>[]]);http_response_code(204);exit;}
  foreach(array_keys($patch) as $k)if(!state_write_key_allowed((string)$k,$u))json_response(['ok'=>false,'error'=>'Bu veri alanını değiştirme yetkiniz yok: '.$k],403);
  $pdo=db();$pdo->beginTransaction();$row=$pdo->query('SELECT revision,state_json FROM app_state WHERE id=1 FOR UPDATE')->fetch();
  if(!$row){$serverState=[];$current=0;}else{$serverState=json_decode((string)$row['state_json'],true)?:[];$current=(int)$row['revision'];}
  $conflicts=[];foreach($patch as $k=>$v){$serverHash=state_key_hash($serverState[$k]??null);$expected=(string)($base[$k]??'');if($expected!==''&&!hash_equals($serverHash,$expected))$conflicts[]=(string)$k;}
  if($conflicts){$pdo->rollBack();if($action==='patch')json_response(['ok'=>false,'error'=>'Aynı modülde başka kullanıcı değişiklik yaptı.','conflictKeys'=>$conflicts,'revision'=>$current],409);http_response_code(409);exit;}
  foreach($patch as $k=>$v)$serverState[$k]=$v;$serverState['_storage']=['canonical'=>'app_state','mirrors'=>'normalized_tables','schema'=>'v3.13.4','updatedAt'=>date('c')];
  $json=json_encode($serverState,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);if($json===false||strlen($json)>32*1024*1024){$pdo->rollBack();json_response(['ok'=>false,'error'=>'State boyutu sınırı aşıldı'],413);}
  $rev=$current+1;if($row){$q=$pdo->prepare('UPDATE app_state SET state_json=?,updated_by=?,revision=? WHERE id=1');$q->execute([$json,(int)$u['id'],$rev]);}else{$q=$pdo->prepare('INSERT INTO app_state(id,state_json,updated_by,revision) VALUES(1,?,?,?)');$q->execute([$json,(int)$u['id'],$rev]);}
  $patchKeys=array_keys($patch);$mirrorKeys=['requests','orders','suppliers','accounts','cashAccounts','cash','documents','customerQuotes'];try{if(array_intersect($patchKeys,$mirrorKeys))sync_normalized($serverState);save_module_states($pdo,$serverState,(int)$u['id'],$patchKeys);}catch(Throwable $e){throw new RuntimeException('Veri aynalama işlemi başarısız: '.$e->getMessage(),0,$e);}
  $pdo->commit();$newHashes=[];foreach(array_keys($patch) as $k)$newHashes[$k]=state_key_hash($serverState[$k]??null);audit('state_patch','app_state','1',['revision'=>$rev,'keys'=>array_keys($patch)]);
  if($action==='patch')json_response(['ok'=>true,'saved_at'=>date('c'),'revision'=>$rev,'keyHashes'=>$newHashes]);http_response_code(204);exit;
 }
 if($action==='save'||$action==='beacon'){
  $u=current_user();if(!can_write_state($u))json_response(['ok'=>false,'error'=>'Bu kullanıcı yalnızca görüntüleme yetkisine sahip.'],403);
  $raw=file_get_contents('php://input');$state=json_decode($raw,true);if(!is_array($state))json_response(['ok'=>false,'error'=>'Geçersiz state JSON'],422);$state=sanitize_state_value($state);$state['_storage']=['canonical'=>'app_state','mirrors'=>'normalized_tables','schema'=>'v3.13.4','updatedAt'=>date('c')];$json=json_encode($state,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);if(strlen($json)>32*1024*1024)json_response(['ok'=>false,'error'=>'State boyutu sınırı aşıldı'],413);
  $expected=(int)($_SERVER['HTTP_X_ASAY_REVISION']??($_GET['rev']??0));$pdo=db();$pdo->beginTransaction();$row=$pdo->query('SELECT revision,state_json FROM app_state WHERE id=1 FOR UPDATE')->fetch();
  if($row){$serverState=json_decode((string)$row['state_json'],true)?:[];$preserve=function(array $keys)use(&$state,$serverState){foreach($keys as $k)if(array_key_exists($k,$serverState))$state[$k]=$serverState[$k];};if(!has_app_write_permission('request',$u))$preserve(['requests','orders']);if(!has_app_write_permission('supplier',$u))$preserve(['suppliers','supplierDirectory']);if(!has_app_write_permission('cost',$u))$preserve(['selected','quoteMarkup','customerQuotes']);if(!has_app_write_permission('finance',$u))$preserve(['accounts']);if(!has_app_write_permission('cash',$u))$preserve(['cash','cashAccounts']);if(!has_app_write_permission('docs',$u))$preserve(['documents','requestAttachments']);if(!has_app_write_permission('settings',$u))$preserve(['company','roles','ui','appIdentity']);$json=json_encode($state,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);}
  if(!$row){$s=$pdo->prepare('INSERT INTO app_state(id,state_json,updated_by,revision) VALUES(1,?,?,1)');$s->execute([$json,$_SESSION['user_id']]);$rev=1;}else{$current=(int)$row['revision'];if($expected!==$current){$pdo->rollBack();json_response(['ok'=>false,'error'=>'Kayıt çakışması','revision'=>$current],409);}$rev=$current+1;$s=$pdo->prepare('UPDATE app_state SET state_json=?,updated_by=?,revision=? WHERE id=1');$s->execute([$json,$_SESSION['user_id'],$rev]);}
  try{sync_normalized($state);}catch(Throwable $e){throw new RuntimeException('Normalize tablo senkronizasyonu başarısız: '.$e->getMessage(),0,$e);}
  try{save_module_states($pdo,$state,(int)($_SESSION['user_id']??0));}catch(Throwable $e){throw new RuntimeException('Modül state senkronizasyonu başarısız: '.$e->getMessage(),0,$e);}
  $pdo->commit();audit('state_save','app_state','1',['revision'=>$rev]);if($action==='save')json_response(['ok'=>true,'saved_at'=>date('c'),'revision'=>$rev]);http_response_code(204);exit;
 }
 json_response(['ok'=>false,'error'=>'Bilinmeyen işlem'],400);
}catch(Throwable $e){try{if(db()->inTransaction())db()->rollBack();}catch(Throwable $ignore){}json_response(['ok'=>false,'error'=>public_error($e,'Veri kaydı tamamlanamadı.')],500);}
