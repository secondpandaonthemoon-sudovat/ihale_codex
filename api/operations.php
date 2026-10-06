<?php
declare(strict_types=1);
require_once __DIR__.'/../app/bootstrap.php';
require_once __DIR__.'/../app/business_rules.php';
require_login();
$u=current_user();if(!$u)json_response(['ok'=>false,'error'=>'Oturum gerekli'],401);
$in=json_decode(file_get_contents('php://input'),true)?:[];$in=sanitize_state_value($in);$action=(string)($in['action']??'');
$actionPerms=[
 'account_bulk_import'=>['finance'],'account_entry_update'=>['finance'],'account_entry_delete'=>['finance'],'finance_journal_meta_update'=>['finance','cash'],'finance_customer_receipt_update'=>['finance','cash'],
 'cash_movement'=>['cash'],'cash_transfer'=>['cash'],'cash_balance_adjustment'=>['cash'],'cash_account_upsert'=>['cash'],'reverse_finance'=>['finance','cash'],'finance_delete'=>['finance','cash'],
 'prepayment'=>['finance','cash'],'guarantee_save'=>['finance'],'guarantee_release'=>['finance'],'guarantee_delete'=>['finance'],'guarantee_config_save'=>['request'],'delivery_batch_collect'=>['finance','cash'],'delivery_batch_supplier_pay'=>['finance','cash'],'comparison_expense_realize'=>['finance','cash','cost'],
 'request_single_import'=>['request'],'request_bulk_import'=>['request'],'request_update'=>['request'],'delivery_batch_create'=>['request'],'delivery_batch_update'=>['request'],'delivery_batch_delete'=>['request'],'delivery_batch_deliver'=>['request'],'delivery_batch_undo'=>['request'],'delivery_batch_cancel'=>['request'],'po_status'=>['request'],'po_revision'=>['request'],'status_change'=>['request'],'request_delete'=>['request'],
 'supplier_quote_save'=>['supplier'],'supplier_quote_delete'=>['supplier'],
 'cost_lines_save'=>['cost'],'quote_cost_mode'=>['cost'],'quote_item'=>['cost'],'quote_total'=>['cost'],'quote_revision'=>['cost'],'quote_mark_sent'=>['cost'],'comparison_currency'=>['cost'],'comparison_select'=>['cost'],'comparison_auto_best'=>['cost'],'customer_quote_create'=>['cost'],'comparison_expenses_save'=>['cost']
];
if(!isset($actionPerms[$action]))json_response(['ok'=>false,'error'=>'Bilinmeyen veya yetkilendirilmemiş operasyon.'],422);
foreach($actionPerms[$action] as $perm)if(!has_app_write_permission($perm,$u))json_response(['ok'=>false,'error'=>'Bu işlem için '.$perm.' yazma yetkisi gerekli.'],403);

function next_id(array $rows): int {$m=0;foreach($rows as $r)$m=max($m,(int)($r['id']??0));return $m+1;}
function &find_request(array &$s,int $id){foreach($s['requests'] as &$r)if((int)$r['id']===$id)return $r;$n=null;return $n;}
function &find_order_by_request(array &$s,int $rid){foreach($s['orders'] as &$o)if((int)$o['requestId']===$rid)return $o;$n=null;return $n;}
function &customer_account(array &$s,array $r){foreach($s['accounts'] as &$a)if((((!empty($r['customerPartyKey'])&&($a['partyKey']??'')===$r['customerPartyKey']))||((empty($r['customerPartyKey']))&&($a['name']??'')===($r['customer']??'')))&&($a['currency']??'')===($r['currency']??'EUR')&&($a['type']??'customer')!=='supplier')return $a;$nid=next_id($s['accounts']);$s['accounts'][]=['id'=>$nid,'partyKey'=>$r['customerPartyKey']??('pty-'.$nid),'type'=>'customer','name'=>$r['customer']??'Müşteri','currency'=>$r['currency']??'EUR','total'=>0,'request'=>$r['no']??'','requestOpen'=>0,'entries'=>[]];return $s['accounts'][array_key_last($s['accounts'])];}
function &find_order_by_id(array &$s,int $id){foreach($s['orders'] as &$o)if((int)$o['id']===$id)return $o;$n=null;return $n;}
function &supplier_account(array &$s,string $name,string $currency,string $requestNo=''){
 foreach($s['accounts'] as &$a)if(($a['type']??'')==='supplier'&&mb_strtoupper(trim((string)($a['name']??'')))===mb_strtoupper(trim($name))&&($a['currency']??'EUR')===$currency)return $a;
 $nid=next_id($s['accounts']);$existingKey='';foreach($s['accounts'] as $aa)if(($aa['type']??'')==='supplier'&&mb_strtoupper(trim((string)($aa['name']??'')))===mb_strtoupper(trim($name))){$existingKey=(string)($aa['partyKey']??'');break;}$s['accounts'][]=['id'=>$nid,'partyKey'=>$existingKey?:('pty-'.$nid),'type'=>'supplier','name'=>$name,'currency'=>$currency,'total'=>0,'request'=>$requestNo,'requestOpen'=>0,'entries'=>[]];
 return $s['accounts'][array_key_last($s['accounts'])];
}
function &find_batch(array &$o,int $batchId){$o['deliveryBatches']=$o['deliveryBatches']??[];foreach($o['deliveryBatches'] as &$b)if((int)$b['id']===$batchId)return $b;$n=null;return $n;}
function batch_next_id(array $orders): int {$m=0;foreach($orders as $o)foreach(($o['deliveryBatches']??[]) as $b)$m=max($m,(int)($b['id']??0));return $m+1;}
function batch_allocated(array $o,int $itemId,bool $deliveredOnly=false): float {$n=0;foreach(($o['deliveryBatches']??[]) as $b){if(($b['status']??'')==='İptal Edildi')continue;if($deliveredOnly&&($b['status']??'')!=='Teslim Edildi')continue;foreach(($b['items']??[]) as $it)if((int)($it['itemId']??0)===$itemId)$n+=(float)($it['qty']??0);}return $n;}
function po_base_total(array $po): float {$n=0;foreach(($po['items']??[]) as $it)$n+=(float)($it['cost']??0)*(float)($it['qty']??0);return $n;}
function batch_po_allocated_freight(array $o,int $poId): float {$n=0;foreach(($o['deliveryBatches']??[]) as $b){if(($b['status']??'')==='İptal Edildi')continue;foreach(($b['supplierPayments']??[]) as $sp)if((int)($sp['poId']??0)===$poId)$n+=(float)($sp['freightShare']??0);}return $n;}
function cash_name_norm(string $v): string {$v=mb_strtoupper(trim($v),'UTF-8');return preg_replace('/\s+/u',' ',$v)??$v;}
function &cash_account_resolve(array &$s,int $id,string $name='',string $currency=''){
 $nameN=cash_name_norm($name);$cur=strtoupper(trim($currency));$idMatches=[];$fingerMatches=[];
 foreach($s['cashAccounts'] as $k=>&$b){
   $idOk=(int)($b['id']??0)===$id;if($idOk)$idMatches[]=$k;
   $nameOk=$nameN!==''&&cash_name_norm((string)($b['name']??''))===$nameN;
   $curOk=$cur===''||strtoupper((string)($b['currency']??''))===$cur;
   if($nameOk&&$curOk&&($id<=0||$idOk))$fingerMatches[]=$k;
 }unset($b);
 if(count($fingerMatches)===1)return $s['cashAccounts'][$fingerMatches[0]];
 if($nameN!==''&&$cur!==''){
   $global=[];foreach($s['cashAccounts'] as $k=>$b)if(cash_name_norm((string)($b['name']??''))===$nameN&&strtoupper((string)($b['currency']??''))===$cur)$global[]=$k;
   if(count($global)===1)return $s['cashAccounts'][$global[0]];
 }
 if(count($idMatches)===1)return $s['cashAccounts'][$idMatches[0]];
 if(count($idMatches)>1)throw new RuntimeException('Kasa/Banka hesap kimliği çakışıyor. Hesap: '.($name?:('#'.$id)).($cur?' · '.$cur:'').'. Sistem yöneticisi Kasa/Banka kimliklerini kontrol etmelidir.');
 $n=null;return $n;
}
function &cash_account_by_id(array &$s,int $id){return cash_account_resolve($s,$id);}
function cash_ledger_balance(array $s,array $account): ?float {
 $id=(int)($account['id']??0);$name=cash_name_norm((string)($account['name']??''));$cur=strtoupper((string)($account['currency']??''));$sum=0.0;$anchored=false;$matched=false;
 foreach(($s['cash']??[]) as $c){
   $idMatch=$id>0&&(int)($c['accountId']??0)===$id;$legacyMatch=(int)($c['accountId']??0)<=0&&cash_name_norm((string)($c['account']??''))===$name&&strtoupper((string)($c['currency']??$cur))===$cur;
   if(!$idMatch&&!$legacyMatch)continue;$matched=true;if((string)($c['kind']??'')==='cash_opening_balance')$anchored=true;
   $v=(float)($c['amountValue']??$c['amount']??0);$sum+=(($c['type']??'Giriş')==='Çıkış'?-1:1)*$v;
 }
 return ($matched&&$anchored)?round($sum,2):null;
}
function reconcile_cash_balance_from_ledger(array &$s,array &$account): ?array {
 $ledger=cash_ledger_balance($s,$account);if($ledger===null)return null;$stored=round((float)($account['balance']??0),2);$delta=round($ledger-$stored,2);
 if(abs($delta)>0.001){$account['balance']=$ledger;return ['old'=>$stored,'new'=>$ledger,'delta'=>$delta];}
 return ['old'=>$stored,'new'=>$stored,'delta'=>0.0];
}
function &cash_account_from_input(array &$s,array $in,string $prefix='bank'){
 $id=(int)($in[$prefix.'Id']??0);$name=trim((string)($in[$prefix.'Name']??''));$cur=strtoupper(trim((string)($in[$prefix.'Currency']??'')));
 return cash_account_resolve($s,$id,$name,$cur);
}
function canonical_request_status(string $status): string {
 $map=['Taslak'=>'draft','Yeni'=>'draft','Fiyat Toplanıyor'=>'collecting','Kıyaslamaya Hazır'=>'ready','Müşteri Teklifi Hazır'=>'quote','Teklif Gönderildi'=>'sent','Gönderildi'=>'sent','Kazanıldı'=>'won','Kaybedildi'=>'lost','İptal'=>'cancelled','İptal Edildi'=>'cancelled'];
 $v=$map[$status]??$status;return in_array($v,['draft','collecting','ready','quote','sent','won','lost','cancelled'],true)?$v:'collecting';
}
function request_quote_status(string $status): string {return match($status){'sent'=>'Gönderildi','won'=>'Kazanıldı','lost'=>'Kaybedildi','cancelled'=>'İptal Edildi',default=>'Taslak'};}
function order_real_activity_reason(array $r,array $o): string {
 if((float)($o['customerPaid']??0)>0.001)return 'müşteriden tahsilat yapılmış';
 foreach(($o['pos']??[]) as $po)if((float)($po['paid']??0)>0.001)return ($po['no']??'PO').' için tedarikçi ödemesi yapılmış';
 foreach(($o['deliveryBatches']??[]) as $bb)if(in_array((string)($bb['status']??''),['Teslim Edildi','Kısmi Teslimat'],true))return 'teslimat gerçekleştirilmiş';
 foreach(($r['realizedComparisonExpenses']??[]) as $x){if(!empty($x['reversed']))continue;if((float)($x['paidAmount']??$x['paymentAmount']??0)>0.001)return 'gerçek operasyon gideri ödenmiş';}
 return '';
}
function &supplier_order_account(array &$s,array $po,array $r){$name=(string)($po['supplier']??'');$cur=(string)($po['currency']??($r['currency']??'EUR'));foreach($s['accounts'] as &$a)if(($a['type']??'')==='supplier'&&mb_strtoupper(trim((string)($a['name']??'')))===mb_strtoupper(trim($name))&&($a['currency']??'EUR')===$cur)return $a;$n=null;return $n;}
function unwind_win_commitments(array &$s,array &$r,array &$o,string $label): void {
 $total=(float)($r['customerQuote']['total']??$o['total']??0);$ca=&customer_account($s,$r);
 if(!empty($o['receivableOpened'])){$ca['requestOpen']=(float)($ca['requestOpen']??0)-$total;add_entry($ca,$r['no'],$label.' / müşteri alacağı kapatıldı',0,$total);}
 foreach(($o['pos']??[]) as $po){$amt=(float)($po['total']??0);if($amt<=0)continue;$sa=&supplier_order_account($s,$po,$r);if($sa){$sa['requestOpen']=(float)($sa['requestOpen']??0)+$amt;add_entry($sa,$r['no'],$label.' / tedarikçi borcu kapatıldı',$amt,0);}}
 $o['financialCancelled']=true;$o['open']=false;$o['customerDue']=0;$o['refundDue']=(float)($o['customerPaid']??0);$o['statusRollbackAt']=date('c');
}
function reopen_win_commitments(array &$s,array &$r,array &$o): void {
 $total=(float)($r['customerQuote']['total']??$o['total']??0);$ca=&customer_account($s,$r);$ca['requestOpen']=(float)($ca['requestOpen']??0)+$total;add_entry($ca,$r['no'],'İş yeniden kazanıldı / müşteri alacağı tekrar açıldı',$total,0);
 foreach(($o['pos']??[]) as $po){$amt=(float)($po['total']??0);if($amt<=0)continue;$sa=&supplier_order_account($s,$po,$r);if(!$sa)$sa=&supplier_account($s,(string)($po['supplier']??'Tedarikçi'),(string)($po['currency']??($r['currency']??'EUR')),$r['no']);$sa['requestOpen']=(float)($sa['requestOpen']??0)-$amt;add_entry($sa,$r['no'],'İş yeniden kazanıldı / tedarikçi borcu tekrar açıldı',0,$amt);}
 $o['financialCancelled']=false;$o['open']=true;$o['customerDue']=max(0,$total-(float)($o['customerPaid']??0));$o['refundDue']=0;unset($o['statusRollbackAt']);
}
function finance_fx_snapshot(array $candidate=[],?string $date=null): array {
 global $s;
 $fallback=is_array($s['fx']??null)?$s['fx']:[];$src=$candidate?:$fallback;
 $usd=(float)($src['usdTry']??0);$eur=(float)($src['eurTry']??0);
 if(!($usd>0&&$eur>0)){$usd=(float)($fallback['usdTry']??0);$eur=(float)($fallback['eurTry']??0);$src=$fallback;}
 if(!($usd>0&&$eur>0))return [];
 // Reject absurd/tampered values; historical exactness is carried by rateDate/source snapshot.
 if($usd<1||$usd>1000||$eur<1||$eur>1000)throw new RuntimeException('Kur snapshot değeri güvenli aralık dışında.');
 $rateDate=(string)($src['rateDate']??$src['effective_date']??$date??'');
 if($rateDate!==''&&!preg_match('/^\d{4}-\d{2}-\d{2}$/',$rateDate))$rateDate='';
 return ['usdTry'=>$usd,'eurTry'=>$eur,'eurUsd'=>$eur/$usd,'source'=>(string)($src['source']??'TCMB'),'updated'=>(string)($src['updated']??$src['fetched_at']??date('c')),'capturedAt'=>(string)($src['capturedAt']??date('c')),'rateDate'=>$rateDate];
}
function add_entry(array &$a,string $ref,string $desc,float $debit,float $credit,?string $entryDate=null,?array $fxSnapshot=null): void {
 $a['entries']=$a['entries']??[];
 $safeDate=$entryDate&&preg_match('/^\d{4}-\d{2}-\d{2}$/',$entryDate)?$entryDate:date('Y-m-d');
 $entry=['txnId'=>'txn-'.bin2hex(random_bytes(6)),'date'=>$safeDate,'createdAt'=>date('c'),'createdBy'=>current_user()['name']??null,'ref'=>$ref,'desc'=>$desc,'debit'=>round($debit,2),'credit'=>round($credit,2),'systemGenerated'=>true];
 $fx=finance_fx_snapshot($fxSnapshot??[],$safeDate);if($fx)$entry['fxSnapshot']=$fx;
 $a['entries'][]=$entry;
}
function sync_delivery_tables(PDO $pdo,array $state,?int $uid=null): void {
 $pdo->exec('DELETE FROM delivery_batch_items');$pdo->exec('DELETE FROM delivery_batches');
 $qb=$pdo->prepare('INSERT INTO delivery_batches(id,sales_order_id,batch_no,status,planned_date,delivered_date,customer_amount,customer_collected,customer_due,payload_json,created_by) VALUES(?,?,?,?,?,?,?,?,?,?,?)');
 $qi=$pdo->prepare('INSERT INTO delivery_batch_items(batch_id,request_item_id,item_name,qty,unit,supplier_name,po_id,sale_total,cost_base,payload_json) VALUES(?,?,?,?,?,?,?,?,?,?)');
 $findItem=null;
 try{$findItem=$pdo->prepare('SELECT id FROM request_items WHERE request_id=? AND source_item_id=? LIMIT 1');}catch(Throwable $e){$findItem=null;}
 foreach(($state['orders']??[]) as $o){
   $rid=(int)($o['requestId']??0);
   foreach(($o['deliveryBatches']??[]) as $b){
     $qb->execute([(int)$b['id'],(int)$o['id'],$b['batchNo']??null,$b['status']??'Planlandı',$b['plannedDate']??null,$b['deliveredDate']??null,(float)($b['customerAmount']??0),(float)($b['customerCollected']??0),(float)($b['customerDue']??0),json_encode($b,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),$uid]);
     foreach(($b['items']??[]) as $it){
       $mirrorId=null;$sourceId=(int)($it['itemId']??0);
       if($findItem&&$rid>0&&$sourceId>0){$findItem->execute([$rid,$sourceId]);$mirrorId=$findItem->fetchColumn()?:null;}
       $qi->execute([(int)$b['id'],$mirrorId,$it['name']??'Kalem',(float)($it['qty']??0),$it['unit']??null,$it['supplier']??null,(int)($it['poId']??0)?:null,(float)($it['saleTotal']??0),(float)($it['costBase']??0),json_encode($it,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)]);
     }
   }
 }
}

function &account_by_id(array &$s,int $id){foreach($s['accounts'] as &$a)if((int)($a['id']??0)===$id)return $a;$n=null;return $n;}
function &operational_expense_account(array &$s,int $partyId,string $partyKey,string $currency){
 if($partyId>0){$a=&account_by_id($s,$partyId);if($a&&strtoupper((string)($a['currency']??''))===strtoupper($currency))return $a;}
 $partyKey=trim($partyKey);$base=null;
 if($partyKey!=='')foreach($s['accounts'] as &$a){if((string)($a['partyKey']??'')!==$partyKey)continue;if(strtoupper((string)($a['currency']??''))===strtoupper($currency))return $a;if($base===null)$base=$a;}unset($a);
 if(!$base){$n=null;return $n;}
 $type=(string)($base['type']??'service');if($type==='customer'){$n=null;return $n;}
 $nid=next_id($s['accounts']);$copy=$base;$copy['id']=$nid;$copy['currency']=strtoupper($currency);$copy['total']=0;$copy['requestOpen']=0;$copy['entries']=[];$copy['createdAt']=date('c');$copy['autoCreatedCurrencyAccount']=true;$s['accounts'][]=$copy;
 return $s['accounts'][array_key_last($s['accounts'])];
}
function &find_po_by_id(array &$s,int $poId,?array &$owner=null){
 foreach($s['orders'] as &$o){$o['pos']=$o['pos']??[];foreach($o['pos'] as &$po)if((int)($po['id']??0)===$poId){$owner=$o;return $po;}}$n=null;return $n;
}
function &find_po_context(array &$s,int $poId,int $orderId=0,string $poNo='',?array &$owner=null){
 if($orderId>0){
   foreach($s['orders'] as &$o){
     if((int)($o['id']??0)!==$orderId)continue;$o['pos']=$o['pos']??[];
     foreach($o['pos'] as &$po){
       if(($poId>0&&(int)($po['id']??0)===$poId)||($poNo!==''&&(string)($po['no']??'')===$poNo)){$owner=$o;return $po;}
     }
   }
 }
 if($poId>0){$po=&find_po_by_id($s,$poId,$owner);if($po)return $po;}
 if($poNo!==''){
   foreach($s['orders'] as &$o){$o['pos']=$o['pos']??[];foreach($o['pos'] as &$po)if((string)($po['no']??'')===$poNo){$owner=$o;return $po;}}
 }
 $n=null;return $n;
}
function recalc_account_open(array &$a): float {
 $sum=0.0;foreach(($a['entries']??[]) as $e)$sum+=(float)($e['debit']??0)-(float)($e['credit']??0);
 $a['requestOpen']=round($sum,2);return $a['requestOpen'];
}
function sync_cash_transaction_mirror(PDO $pdo,array $state): void {
 try{
   $pdo->exec('DELETE FROM cash_transactions');
   $curById=[];$curByName=[];foreach(($state['cashAccounts']??[]) as $a){$curById[(int)($a['id']??0)]=(string)($a['currency']??'');$curByName[cash_name_norm((string)($a['name']??''))]=(string)($a['currency']??'');}
   $q=$pdo->prepare('INSERT INTO cash_transactions(txn_date,account_name,request_no,party_name,txn_type,currency,amount,note) VALUES(?,?,?,?,?,?,?,?)');
   foreach(($state['cash']??[]) as $c){$date=(string)($c['date']??'');if(preg_match('/^(\d{2})\.(\d{2})\.(\d{4})$/',$date,$m))$date="$m[3]-$m[2]-$m[1]";if(!preg_match('/^\d{4}-\d{2}-\d{2}$/',$date))$date=date('Y-m-d');$aid=(int)($c['accountId']??0);$cur=(string)($c['currency']??($curById[$aid]??($curByName[cash_name_norm((string)($c['account']??''))]??'')));$q->execute([$date,$c['account']??'Hesap',$c['request']??null,$c['party']??null,($c['type']??'Giriş')==='Çıkış'?'Çıkış':'Giriş',$cur?:null,(float)($c['amountValue']??$c['amount']??0),$c['note']??null]);}
 }catch(Throwable $e){throw new RuntimeException('Kasa/Banka hareket mirror senkronizasyonu başarısız: '.$e->getMessage(),0,$e);}
}
function cash_movement_effect(array $c): float {$v=(float)($c['amountValue']??$c['amount']??0);return (($c['type']??'Giriş')==='Çıkış'?-1.0:1.0)*$v;}
function remove_cash_rows(array &$s,callable $match): array {
 $keep=[];$removed=[];$effects=[];
 foreach(($s['cash']??[]) as $c){if(!$match($c)){$keep[]=$c;continue;}$removed[]=$c;$aid=(int)($c['accountId']??0);if($aid>0)$effects[$aid]=($effects[$aid]??0)+cash_movement_effect($c);}
 $s['cash']=$keep;
 foreach($s['cashAccounts'] as &$a){$aid=(int)($a['id']??0);if(isset($effects[$aid])){$a['balance']=round((float)($a['balance']??0)-$effects[$aid],2);reconcile_cash_balance_from_ledger($s,$a);}}unset($a);
 return ['rows'=>$removed,'effects'=>$effects];
}
function remove_account_entry_by_txn(array &$a,string $txnId): bool {
 if($txnId==='')return false;foreach(($a['entries']??[]) as $i=>$e)if((string)($e['txnId']??'')===$txnId){array_splice($a['entries'],$i,1);return true;}return false;
}
function remove_account_entry_signature(array &$a,float $amount,string $direction,string $desc='',string $ref=''): bool {
 $best=-1;$bestScore=-1;foreach(($a['entries']??[]) as $i=>$e){$v=$direction==='debit'?(float)($e['debit']??0):(float)($e['credit']??0);$other=$direction==='debit'?(float)($e['credit']??0):(float)($e['debit']??0);if(abs($v-$amount)>0.011||abs($other)>0.011)continue;$score=0;if($desc!==''&&(string)($e['desc']??'')===$desc)$score+=4;elseif($desc!==''&&str_contains(mb_strtolower((string)($e['desc']??''),'UTF-8'),mb_strtolower($desc,'UTF-8')))$score+=1;if($ref!==''&&(string)($e['ref']??'')===$ref)$score+=2;if($score>$bestScore){$bestScore=$score;$best=(int)$i;}}
 if($best<0)return false;array_splice($a['entries'],$best,1);return true;
}
function remove_account_entries_exact_desc(array &$a,float $amount,string $desc,string $ref=''): int {
 $n=0;$keep=[];foreach(($a['entries']??[]) as $e){$v=max((float)($e['debit']??0),(float)($e['credit']??0));$same=abs($v-$amount)<0.011&&(string)($e['desc']??'')===$desc&&($ref===''||(string)($e['ref']??'')===$ref);if($same){$n++;continue;}$keep[]=$e;}$a['entries']=$keep;return $n;
}
function find_existing_customer_account_index(array $s,?array $r,string $partyName,string $currency): int {
 foreach(($s['accounts']??[]) as $i=>$a){if(($a['type']??'customer')==='supplier')continue;$partyOk=$r&&!empty($r['customerPartyKey'])?(string)($a['partyKey']??'')===(string)$r['customerPartyKey']:normalized_party_cmp((string)($a['name']??''),$partyName);if($partyOk&&strtoupper((string)($a['currency']??''))===strtoupper($currency))return (int)$i;}return -1;
}
function normalized_party_cmp(string $a,string $b): bool {return cash_name_norm($a)===cash_name_norm($b);}
function finance_delete_account_cleanup(array &$s,array $jr,array $payload,array $cashRows,string $txnId='',int $accountId=0,bool $hadReversal=false): void {
 $type=(string)($jr['action_type']??'');$amount=(float)($jr['amount']??0);$ref=(string)($payload['request']??'');$desc=(string)($payload['description']??'');$cashOriginal=null;foreach($cashRows as $c)if((int)($c['journalId']??0)===(int)$jr['id']){$cashOriginal=$c;break;}if($cashOriginal){if($desc==='')$desc=(string)($cashOriginal['note']??'');if($ref==='')$ref=(string)($cashOriginal['request']??'');}
 $idx=-1;if($accountId>0)foreach(($s['accounts']??[]) as $i=>$a)if((int)($a['id']??0)===$accountId){$idx=(int)$i;break;}
 if($idx<0&&in_array($type,['generic_customer_receipt','generic_supplier_payment','operational_expense_payment'],true)){$pid=(int)($payload['partyId']??0);if($pid>0)foreach(($s['accounts']??[]) as $i=>$a)if((int)($a['id']??0)===$pid){$idx=(int)$i;break;}}
 $rid=(int)($jr['request_id']??0);$r=null;if($rid>0)foreach(($s['requests']??[]) as $rr)if((int)($rr['id']??0)===$rid){$r=$rr;break;}
 if($idx<0&&in_array($type,['customer_prepayment','delivery_batch_collection'],true))$idx=find_existing_customer_account_index($s,$r,(string)($jr['party_name']??''),(string)($jr['currency']??''));
 if($idx<0&&$type==='delivery_batch_supplier_payment')foreach(($s['accounts']??[]) as $i=>$a)if(($a['type']??'')==='supplier'&&normalized_party_cmp((string)($a['name']??''),(string)($jr['party_name']??''))&&strtoupper((string)($a['currency']??''))===strtoupper((string)($jr['currency']??''))){$idx=(int)$i;break;}
 if($idx<0)return;$a=&$s['accounts'][$idx];$selectedWasReversal=false;if($txnId!=='')foreach(($a['entries']??[]) as $ee)if((string)($ee['txnId']??'')===$txnId){$selectedWasReversal=str_starts_with(mb_strtolower(trim((string)($ee['desc']??'')),'UTF-8'),'ters kayıt:');break;}$selectedRemoved=remove_account_entry_by_txn($a,$txnId);
 if($type==='operational_expense_payment'){
   $base=$desc;$removed=0;$removed+=remove_account_entries_exact_desc($a,$amount,$base.' / Gider tahakkuku');$removed+=remove_account_entries_exact_desc($a,$amount,$base.' / Ödeme');if($hadReversal){$removed+=remove_account_entries_exact_desc($a,$amount,'Ters kayıt: operasyon gideri ödeme');$removed+=remove_account_entries_exact_desc($a,$amount,'Ters kayıt: operasyon gideri tahakkuk');}
 }elseif(in_array($type,['customer_prepayment','delivery_batch_collection','generic_customer_receipt'],true)){
   if(!$selectedRemoved||$selectedWasReversal)remove_account_entry_signature($a,$amount,'credit',$desc,$ref);if($hadReversal)remove_account_entries_exact_desc($a,$amount,'Ters kayıt: müşteri tahsilatı',$ref);
 }elseif(in_array($type,['delivery_batch_supplier_payment','generic_supplier_payment'],true)){
   if(!$selectedRemoved||$selectedWasReversal)remove_account_entry_signature($a,$amount,'debit',$desc,$ref);if($hadReversal)remove_account_entries_exact_desc($a,$amount,'Ters kayıt: tedarikçi ödemesi',$ref);
 }
 recalc_account_open($a);unset($a);
}
function sync_account_transaction_mirror(PDO $pdo,array $state): void {
 try{
   $pdo->exec('DELETE FROM account_transactions');
   $partyByKey=[];$partyByName=[];
   foreach($pdo->query('SELECT id,party_key,party_type,name FROM parties')->fetchAll(PDO::FETCH_ASSOC) as $p){
     if(!empty($p['party_key']))$partyByKey[(string)$p['party_key']]=(int)$p['id'];
     $partyByName[mb_strtoupper(trim((string)$p['name']),'UTF-8').'|'.(string)$p['party_type']]=(int)$p['id'];
   }
   $q=$pdo->prepare('INSERT INTO account_transactions(party_id,currency,txn_date,reference_no,description,debit,credit) VALUES(?,?,?,?,?,?,?)');
   foreach(($state['accounts']??[]) as $a){
     $pid=$partyByKey[(string)($a['partyKey']??'')]??($partyByName[mb_strtoupper(trim((string)($a['name']??'')),'UTF-8').'|'.(string)($a['type']??'customer')]??null);
     foreach(($a['entries']??[]) as $e){$date=(string)($e['date']??date('Y-m-d'));if(!preg_match('/^\\d{4}-\\d{2}-\\d{2}$/',$date))$date=date('Y-m-d');$q->execute([$pid,$a['currency']??'EUR',$date,$e['ref']??null,$e['desc']??'Cari Hareket',(float)($e['debit']??0),(float)($e['credit']??0)]);}
   }
 }catch(Throwable $e){throw new RuntimeException('Cari hareket mirror senkronizasyonu başarısız: '.$e->getMessage(),0,$e);}
}

function order_has_active_batches(array $o): bool {foreach(($o['deliveryBatches']??[]) as $b)if(($b['status']??'')!=='İptal Edildi')return true;return false;}
function payment_schedule_from_plan(array $r,float $total): array {
 $plan=$r['paymentPlan']??[];if(!is_array($plan)||!count($plan))$plan=[['percent'=>100,'label'=>$r['payment']??'Ödeme']];
 $out=[];$sum=0.0;$i=0;
 foreach($plan as $p){$pct=max(0,(float)($p['percent']??0));if($pct<=0)continue;$amt=round($total*$pct/100,2);$sum+=$amt;$out[]=['id'=>++$i,'label'=>trim((string)($p['label']??('Aşama '.$i))),'percent'=>$pct,'amount'=>$amt,'paid'=>0,'due'=>$amt,'status'=>'Bekliyor'];}
 if($out){$diff=round($total-$sum,2);$last=array_key_last($out);$out[$last]['amount']=round($out[$last]['amount']+$diff,2);$out[$last]['due']=$out[$last]['amount'];}
 return $out;
}
function reprice_payment_schedule(array &$o,float $total): void {
 if(empty($o['paymentSchedule'])||!is_array($o['paymentSchedule']))return;
 $sum=0.0;foreach($o['paymentSchedule'] as $i=>&$st){$pct=max(0,(float)($st['percent']??0));$amount=round($total*$pct/100,2);$st['amount']=$amount;$paid=(float)($st['paid']??0);if($paid>$amount+0.001)throw new RuntimeException('Yeni toplam, ödeme planında tahsil edilmiş bir aşamanın altına düşemez. Önce ters tahsilat yapın.');$st['due']=max(0,round($amount-$paid,2));$st['status']=$st['due']<=0.001?'Tamamlandı':($paid>0?'Kısmi':'Bekliyor');$sum+=$amount;}
 if($o['paymentSchedule']){$last=array_key_last($o['paymentSchedule']);$diff=round($total-$sum,2);$o['paymentSchedule'][$last]['amount']=round($o['paymentSchedule'][$last]['amount']+$diff,2);$paid=(float)($o['paymentSchedule'][$last]['paid']??0);$o['paymentSchedule'][$last]['due']=max(0,round($o['paymentSchedule'][$last]['amount']-$paid,2));}
}
function allocate_schedule_payment(array &$o,float $amount): array {
 $left=round($amount,2);$alloc=[];
 $o['paymentSchedule']=$o['paymentSchedule']??[];foreach($o['paymentSchedule'] as &$st){if($left<=0.001)break;$due=max(0,(float)($st['due']??0));if($due<=0)continue;$x=min($due,$left);$st['paid']=round((float)($st['paid']??0)+$x,2);$st['due']=max(0,round((float)$st['amount']-$st['paid'],2));$st['status']=$st['due']<=0.001?'Tamamlandı':'Kısmi';$left=round($left-$x,2);$alloc[]=['id'=>$st['id']??null,'label'=>$st['label']??'','amount'=>$x];}unset($st);
 return $alloc;
}
function reverse_schedule_payment(array &$o,float $amount): void {
 $left=round($amount,2);if(empty($o['paymentSchedule']))return;
 for($i=count($o['paymentSchedule'])-1;$i>=0&&$left>0.001;$i--){$paid=max(0,(float)($o['paymentSchedule'][$i]['paid']??0));if($paid<=0)continue;$x=min($paid,$left);$o['paymentSchedule'][$i]['paid']=round($paid-$x,2);$o['paymentSchedule'][$i]['due']=max(0,round((float)$o['paymentSchedule'][$i]['amount']-$o['paymentSchedule'][$i]['paid'],2));$o['paymentSchedule'][$i]['status']=$o['paymentSchedule'][$i]['paid']<=0.001?'Bekliyor':'Kısmi';$left=round($left-$x,2);}
}
function recompute_delivery_po_status(array &$o,array $fallback=[]): void {
 foreach(($o['pos']??[]) as &$po){
  $poOrdered=0.0;$poDelivered=0.0;foreach(($po['items']??[]) as $pit){$poOrdered+=(float)($pit['qty']??0);$poDelivered+=batch_allocated($o,(int)($pit['itemId']??0),true);}
  $pid=(int)($po['id']??0);
  if($poOrdered>0&&$poDelivered>=$poOrdered-0.0001){$po['status']='Teslim Edildi';$po['production']=100;}
  elseif($poDelivered>0){$po['status']='Kısmi Teslimat';$po['production']=max(95,min(99,(float)($po['production']??95)));}
  else{
   $snap=$fallback[(string)$pid]??$fallback[$pid]??null;
   if(is_array($snap)){$po['status']=(string)($snap['status']??'Sevkiyata Hazır');$po['production']=(float)($snap['production']??90);}
   elseif(in_array((string)($po['status']??''),['Teslim Edildi','Kısmi Teslimat'],true)){$po['status']='Sevkiyata Hazır';$po['production']=min(90,(float)($po['production']??90));}
  }
 }unset($po);
}
function cash_row_iso_date(array $c): string {$d=(string)($c['date']??'');if(preg_match('/^(\d{2})\.(\d{2})\.(\d{4})$/',$d,$m))return $m[3].'-'.$m[2].'-'.$m[1];return preg_match('/^\d{4}-\d{2}-\d{2}$/',$d)?$d:date('Y-m-d');}
function remove_account_entry_signature_date(array &$a,float $amount,string $direction,string $desc,string $ref,string $date): bool {
 $best=-1;$bestScore=-1;foreach(($a['entries']??[]) as $i=>$e){$v=$direction==='debit'?(float)($e['debit']??0):(float)($e['credit']??0);$other=$direction==='debit'?(float)($e['credit']??0):(float)($e['debit']??0);if(abs($v-$amount)>0.011||abs($other)>0.011)continue;$score=0;if((string)($e['desc']??'')===$desc)$score+=4;if((string)($e['ref']??'')===$ref)$score+=3;if((string)($e['date']??'')===$date)$score+=2;if($score>$bestScore){$bestScore=$score;$best=(int)$i;}}if($best<0)return false;array_splice($a['entries'],$best,1);return true;
}
function normalize_batch_freight(array &$o): void {
 $o['pos']=$o['pos']??[];$o['deliveryBatches']=$o['deliveryBatches']??[];
 foreach($o['pos'] as &$po){
   $poId=(int)($po['id']??0);$freight=max(0,round((float)($po['total']??0)-po_base_total($po),2));$refs=[];$baseSum=0.0;
   foreach($o['deliveryBatches'] as &$b){
     if(($b['status']??'')==='İptal Edildi')continue;$b['supplierPayments']=$b['supplierPayments']??[];
     foreach($b['supplierPayments'] as $j=>&$sp){if((int)($sp['poId']??0)!==$poId)continue;$refs[]=[&$sp];$baseSum+=(float)($sp['baseAmount']??0);}unset($sp);
   }unset($b);
   $allocated=0.0;$n=count($refs);foreach($refs as $i=>$wrap){$sp=&$wrap[0];$share=($i===$n-1)?round($freight-$allocated,2):($baseSum>0?round($freight*((float)$sp['baseAmount']/$baseSum),2):0);$allocated+=$share;$sp['freightShare']=$share;$sp['total']=round((float)$sp['baseAmount']+$share,2);$paid=(float)($sp['paid']??0);if($paid>$sp['total']+0.001)throw new RuntimeException('Parti revizyonu, daha önce ödenmiş tedarikçi tutarının altına düşemez.');$sp['due']=max(0,round($sp['total']-$paid,2));unset($sp);}
 }unset($po);
}


function sync_core_mirrors(PDO $pdo,array $state): void {
 foreach(($state['requests']??[]) as $r){
   $deadline=null;$x=(string)($r['deadline']??'');if(preg_match('/^\d{4}-\d{2}-\d{2}$/',$x))$deadline=$x;
   $q=$pdo->prepare('INSERT INTO requests(id,request_no,title,currency,status,deadline,payload_json) VALUES(?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE request_no=VALUES(request_no),title=VALUES(title),currency=VALUES(currency),status=VALUES(status),deadline=VALUES(deadline),payload_json=VALUES(payload_json)');
   $q->execute([(int)$r['id'],$r['no']??('REQ-'.$r['id']),$r['title']??'Talep',$r['currency']??'EUR',$r['status']??'draft',$deadline,json_encode($r,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)]);
   if(!empty($r['customerQuote'])){$cq=$r['customerQuote'];$q=$pdo->prepare('INSERT INTO customer_quotes(request_id,quote_no,revision_no,status,currency,cost_total,sales_total,profit,payload_json) VALUES(?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE quote_no=VALUES(quote_no),revision_no=VALUES(revision_no),status=VALUES(status),currency=VALUES(currency),cost_total=VALUES(cost_total),sales_total=VALUES(sales_total),profit=VALUES(profit),payload_json=VALUES(payload_json)');$q->execute([(int)$r['id'],$cq['no']??null,(int)($cq['revision']??0),$cq['status']??null,$r['currency']??'EUR',(float)($cq['cost']??0),(float)($cq['total']??0),(float)($cq['profit']??0),json_encode($cq,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)]);}
 }
 foreach(($state['orders']??[]) as $o){
   $q=$pdo->prepare('INSERT INTO sales_orders(id,request_id,order_no,customer_name,currency,total,customer_paid,customer_due,status,payload_json) VALUES(?,?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE order_no=VALUES(order_no),customer_name=VALUES(customer_name),currency=VALUES(currency),total=VALUES(total),customer_paid=VALUES(customer_paid),customer_due=VALUES(customer_due),status=VALUES(status),payload_json=VALUES(payload_json)');
   $q->execute([(int)$o['id'],(int)$o['requestId'],$o['no']??null,$o['customer']??null,$o['currency']??'EUR',(float)($o['total']??0),(float)($o['customerPaid']??0),(float)($o['customerDue']??0),!empty($o['open'])?'open':'closed',json_encode($o,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)]);
   foreach(($o['pos']??[]) as $po){$x=$pdo->prepare('INSERT INTO purchase_orders(id,sales_order_id,po_no,supplier_name,status,total,paid,due,payload_json) VALUES(?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE po_no=VALUES(po_no),supplier_name=VALUES(supplier_name),status=VALUES(status),total=VALUES(total),paid=VALUES(paid),due=VALUES(due),payload_json=VALUES(payload_json)');$x->execute([(int)$po['id'],(int)$o['id'],$po['no']??null,$po['supplier']??null,$po['status']??null,(float)($po['total']??0),(float)($po['paid']??0),(float)($po['due']??0),json_encode($po,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)]);}
 }
 foreach(($state['cashAccounts']??[]) as $a){$q=$pdo->prepare('INSERT INTO cash_accounts(id,name,account_type,currency,balance,iban,swift,payload_json) VALUES(?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE name=VALUES(name),account_type=VALUES(account_type),currency=VALUES(currency),balance=VALUES(balance),iban=VALUES(iban),swift=VALUES(swift),payload_json=VALUES(payload_json)');$q->execute([(int)$a['id'],$a['name']??'Hesap',$a['type']??'Banka',$a['currency']??'EUR',(float)($a['balance']??0),$a['iban']??null,$a['swift']??null,json_encode($a,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)]);}
}

function order_request_state(array $s,array $o): ?array {foreach(($s['requests']??[]) as $r)if((int)($r['id']??0)===(int)($o['requestId']??0))return $r;return null;}
function is_valid_operational_order_state(array $s,array $o): bool {
 if(($o['open']??true)===false||!empty($o['financialCancelled']))return false;
 $r=order_request_state($s,$o);if(!$r)return false;$rs=canonical_request_status((string)($r['status']??''));
 if(in_array($rs,['lost','cancelled'],true))return false;
 $won=($rs==='won');
 return $won&&count($o['pos']??[])>0;
}
function has_live_order_for_request(array $s,int $rid): bool {
 foreach(($s['orders']??[]) as $o){
   if((int)($o['requestId']??0)!==$rid)continue;
   if(($o['open']??true)===false||!empty($o['financialCancelled']))continue;
   $requestStatus='';foreach(($s['requests']??[]) as $rr)if((int)($rr['id']??0)===$rid){$requestStatus=canonical_request_status((string)($rr['status']??''));break;}
   if(in_array($requestStatus,['lost','cancelled'],true))continue;
   $pos=(array)($o['pos']??[]);
   if(!$pos)continue;
   foreach($pos as $po){
     $st=mb_strtolower(trim((string)($po['status']??'')),'UTF-8');
     if(!in_array($st,['teslim edildi','iptal edildi','cancelled','canceled','delivered'],true))return true;
   }
 }
 return false;
}
function selected_supplier_id_state(array $s,int $rid,int $itemId): ?int {
 $k=$rid.':'.$itemId;$v=$s['selected'][$k]??null;
 if($v===null){
   $legacy=$s['selected'][(string)$itemId]??null;
   if($legacy!==null){
     $sid=(int)$legacy;$belongs=false;
     foreach(($s['suppliers']??[]) as $sp)if((int)($sp['id']??0)===$sid&&(int)($sp['requestId']??0)===$rid){$belongs=true;break;}
     if($belongs)$v=$sid;
   }
 }
 return $v===null?null:(int)$v;
}
function next_customer_quote_no(array $s): string {$y=date('Y');$prefix='ASAY-Q-'.$y.'-';$m=0;foreach(($s['requests']??[]) as $r){$n=(string)($r['customerQuote']['no']??'');if(str_starts_with($n,$prefix))$m=max($m,(int)substr($n,strlen($prefix)));}return $prefix.str_pad((string)($m+1),4,'0',STR_PAD_LEFT);}
function fx_rates(array $fx): array {
 $usd=max(0.0000001,(float)($fx['usdTry']??0));$eur=max(0.0000001,(float)($fx['eurTry']??0));
 return ['usdTry'=>$usd,'eurTry'=>$eur,'eurUsd'=>$eur/$usd,'source'=>$fx['source']??'','updated'=>$fx['updated']??''];
}
function fx_convert(float $amount,string $from,string $to,array $fx): float {
 $from=strtoupper($from);$to=strtoupper($to);if($from===$to)return $amount;$r=fx_rates($fx);
 $try=$from==='TRY'?$amount:($from==='USD'?$amount*$r['usdTry']:($from==='EUR'?$amount*$r['eurTry']:0));
 if($to==='TRY')return $try;if($to==='USD')return $try/$r['usdTry'];if($to==='EUR')return $try/$r['eurTry'];return 0;
}
function require_payment_fx(string $from,string $to,mixed $rate,mixed $confirmed=true): float {
 $from=strtoupper($from);$to=strtoupper($to);if($from===$to)return 1.0;
 $r=(float)$rate;if(empty($confirmed)||!($r>0))throw new RuntimeException('Farklı para birimi için geçerli kur/parite zorunlu.');
 return $r;
}
function decimal_string(float $v,int $precision=12): string {
 $s=number_format($v,$precision,'.','');
 $s=rtrim(rtrim($s,'0'),'.');
 return $s===''?'0':$s;
}
function supplier_vat_gross(string $base,string $mode,float $rate): string {
 $b=max(0,(float)$base);$rate=max(0,$rate);
 $gross=$mode==='included'?$b:$b*(1+$rate/100);
 return decimal_string($gross,12);
}
function supplier_vat_net(string $base,string $mode,float $rate): float {
 $b=max(0,(float)$base);$rate=max(0,$rate);
 return $mode==='included'&&$rate>0?$b/(1+$rate/100):$b;
}
function supplier_currency(array $sup,array $r): string {$c=strtoupper((string)($sup['currency']??($r['currency']??'EUR')));return in_array($c,['TRY','EUR','USD'],true)?$c:'EUR';}
function supplier_fx(array $s,array $sup): array {
 $fx=$sup['fxSnapshot']??null;
 if(is_array($fx)&&(float)($fx['usdTry']??0)>0&&(float)($fx['eurTry']??0)>0)return $fx;
 $live=$s['fx']??null;
 if(is_array($live)&&(float)($live['usdTry']??0)>0&&(float)($live['eurTry']??0)>0)return $live;
 throw new RuntimeException('Geçerli döviz kuru bulunamadı. TCMB kurunu yenileyin; çapraz döviz işlemi 1:1 varsayımla yapılamaz.');
}
function supplier_offer_converted(array $s,array $r,array $sup,int $iid,string $to): float {
 $from=supplier_currency($sup,$r);$amount=(float)($sup['offers'][$iid]??0);return $from===strtoupper($to)?$amount:fx_convert($amount,$from,$to,supplier_fx($s,$sup));
}
function request_cost_line_total(array $s,array $r,string $target): float {
 $sum=0.0;$target=strtoupper($target);
 foreach(($r['costLines']??[]) as $line){
   $amount=max(0,(float)($line['amount']??0));if(!($amount>0))continue;
   $from=strtoupper((string)($line['currency']??($r['comparisonCurrency']??$r['currency']??'EUR')));
   if($from===$target){$sum+=$amount;continue;}
   $fx=$line['fxSnapshot']??($r['costFxSnapshot']??($s['fx']??[]));
   if((float)($fx['usdTry']??0)>0&&(float)($fx['eurTry']??0)>0)$sum+=fx_convert($amount,$from,$target,$fx);
 }
 return $sum;
}
function sync_customer_quote_state(array &$s,array &$r): void {
 $rid=(int)$r['id'];$oldByUid=[];$oldById=[];$oldUidCount=[];
 foreach(($r['customerQuote']['items']??[]) as $x){
   $uid=trim((string)($x['itemUid']??''));if($uid!=='')$oldUidCount[$uid]=($oldUidCount[$uid]??0)+1;
   $iid=(int)($x['itemId']??0);if($iid>0&&!isset($oldById[$iid]))$oldById[$iid]=$x;
 }
 foreach(($r['customerQuote']['items']??[]) as $x){$uid=trim((string)($x['itemUid']??''));if($uid!==''&&($oldUidCount[$uid]??0)===1)$oldByUid[$uid]=$x;}
 // Eski sürümlerden kalmış duplicate/missing UID'leri request item ID üzerinden kalıcı ve tekil hale getir.
 $seenUid=[];foreach(($r['items']??[]) as &$reqItem){$iid=(int)($reqItem['id']??0);$uid=trim((string)($reqItem['uid']??''));if($uid===''||isset($seenUid[$uid]))$uid='req'.$rid.'-item-'.$iid;$base=$uid;$n=2;while(isset($seenUid[$uid]))$uid=$base.'-'.$n++;$reqItem['uid']=$uid;$seenUid[$uid]=true;}unset($reqItem);
 $items=[];$rawCost=0.0;$total=0.0;$markup=(float)($s['quoteMarkup']??18);$target=strtoupper((string)($r['currency']??'EUR'));
 $mode=(string)($r['customerQuote']['costMode']??'raw');if(!in_array($mode,['raw','landed'],true))$mode='raw';
 foreach(($r['items']??[]) as $idx=>$it){
   $iid=(int)$it['id'];$uid=(string)($it['uid']??('req'.$rid.'-item-'.$iid));$sid=selected_supplier_id_state($s,$rid,$iid);$sup=null;
   foreach(($s['suppliers']??[]) as $ss)if((int)($ss['id']??0)===$sid&&(int)($ss['requestId']??0)===$rid){$sup=$ss;break;}
   $raw=(float)($sup['offers'][$iid]??0);$rawCur=$sup?supplier_currency($sup,$r):$target;$c=$sup?($rawCur===$target?$raw:fx_convert($raw,$rawCur,$target,supplier_fx($s,$sup))):0;
   $prev=$oldById[$iid]??($oldByUid[$uid]??null);$sale=$prev!==null&&isset($prev['sale'])?(float)$prev['sale']:round($c*(1+$markup/100),2);$qty=(float)($it['qty']??0);
   $row=['itemId'=>$iid,'itemUid'=>$uid,'name'=>$it['name']??'Kalem','qty'=>$qty,'unit'=>$it['unit']??'','supplierId'=>$sid,'supplier'=>$sup['name']??'','costRaw'=>$c,'cost'=>$c,'costOriginal'=>$raw,'costCurrency'=>$rawCur,'fxSnapshot'=>$sup['fxSnapshot']??null,'sale'=>$sale,'total'=>round($sale*$qty,2)];
   $items[]=$row;$rawCost+=$c*$qty;$total+=$row['total'];
 }
 $expense=request_cost_line_total($s,$r,$target);$landed=$rawCost+$expense;
 if($mode==='landed'&&$expense>0&&count($items)){
   $den=$rawCost>0?$rawCost:array_sum(array_map(fn($x)=>(float)($x['qty']??0)>0?(float)$x['qty']:1.0,$items));
   foreach($items as &$row){$base=(float)$row['costRaw']*(float)$row['qty'];$weight=$rawCost>0?($base/$den):(((float)$row['qty']>0?(float)$row['qty']:1.0)/$den);$alloc=$expense*$weight;$row['costAllocated']=round($alloc,6);if((float)$row['qty']>0)$row['cost']=((float)$row['costRaw'])+($alloc/(float)$row['qty']);}unset($row);
 }
 $activeCost=$mode==='landed'?$landed:$rawCost;
 $r['customerQuote']['items']=$items;$r['customerQuote']['costMode']=$mode;$r['customerQuote']['rawCost']=round($rawCost,2);$r['customerQuote']['expenseTotal']=round($expense,2);$r['customerQuote']['landedCost']=round($landed,2);$r['customerQuote']['cost']=round($activeCost,2);$r['customerQuote']['total']=round($total,2);$r['customerQuote']['profit']=round($total-$activeCost,2);$s['customerQuotes'][(string)$rid]=$r['customerQuote'];
}
function full_comparison_state(array $s,array $r): bool {$rid=(int)$r['id'];if(!count($r['items']??[]))return false;foreach($r['items'] as $it){$iid=(int)$it['id'];$sid=selected_supplier_id_state($s,$rid,$iid);if(!$sid)return false;$ok=false;foreach(($s['suppliers']??[]) as $sp)if((int)($sp['id']??0)===$sid&&(int)($sp['requestId']??0)===$rid&&(float)($sp['offers'][$iid]??0)>0&&(string)($sp['technical'][$iid]??'suitable')!=='unsuitable'){$ok=true;break;}if(!$ok)return false;}return true;}
function uuid4(): string {$d=random_bytes(16);$d[6]=chr((ord($d[6])&0x0f)|0x40);$d[8]=chr((ord($d[8])&0x3f)|0x80);return vsprintf('%s%s-%s-%s-%s-%s%s%s',str_split(bin2hex($d),4));}
function save_locked(PDO $pdo,array $state,int $uid,int $rev): int {$state['_storage']=['canonical'=>'app_state','mirrors'=>'normalized_tables','schema'=>'v3.13.4','updatedAt'=>date('c')];$new=$rev+1;$q=$pdo->prepare('UPDATE app_state SET state_json=?,updated_by=?,revision=? WHERE id=1');$q->execute([json_encode($state,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),$uid,$new]);return $new;}
function norm_party_name(string $v): string {
  $v=mb_strtoupper(trim(preg_replace('/\s+/u',' ',$v)??$v),'UTF-8');
  $map=['Ç'=>'C','Ğ'=>'G','İ'=>'I','I'=>'I','Ö'=>'O','Ş'=>'S','Ü'=>'U','Â'=>'A','Î'=>'I','Û'=>'U'];
  $v=strtr($v,$map);
  $v=preg_replace('/[^A-Z0-9]+/u',' ',$v)??$v;
  return trim(preg_replace('/\s+/',' ',$v)??$v);
}
function find_import_party(array $s,string $name,string $taxNo=''): ?array {
  $accounts=array_values(array_filter(($s['accounts']??[]),fn($a)=>in_array((string)($a['type']??'customer'),['customer','public','private'],true)));
  if($taxNo!==''){
    foreach($accounts as $a)if(trim((string)($a['taxNo']??''))===$taxNo)return $a;
  }
  $needle=norm_party_name($name);
  foreach($accounts as $a)if(norm_party_name((string)($a['name']??''))===$needle)return $a;
  if(strlen($needle)<12)return null;
  $scores=[];
  foreach($accounts as $a){
    $candidate=norm_party_name((string)($a['name']??''));if(strlen($candidate)<12)continue;
    $max=max(strlen($needle),strlen($candidate));if(!$max)continue;
    $distance=levenshtein($needle,$candidate);
    $ratio=$distance/$max;
    if($distance<=2&&$ratio<=0.08)$scores[]=['ratio'=>$ratio,'distance'=>$distance,'account'=>$a];
  }
  if(!$scores)return null;
  usort($scores,fn($x,$y)=>($x['ratio']<=>$y['ratio'])?:($x['distance']<=>$y['distance']));
  if(count($scores)>1&&abs($scores[0]['ratio']-$scores[1]['ratio'])<0.015)return null;
  return $scores[0]['account'];
}
function import_party_key(array $s,string $name,string $taxNo=''): string {
  $a=find_import_party($s,$name,$taxNo);
  return $a?(string)($a['partyKey']??('pty-'.($a['id']??0))):'pty-import-'.bin2hex(random_bytes(6));
}
function import_number($v): float {
  if(is_int($v)||is_float($v))return (float)$v;
  $s=trim((string)$v);if($s==='')return 0.0;$s=preg_replace('/\s+/u','',$s);
  $hasComma=str_contains($s,',');$hasDot=str_contains($s,'.');
  if($hasComma&&$hasDot){
    if(strrpos($s,',')>strrpos($s,'.'))$s=str_replace('.','',$s);
    else $s=str_replace(',','',$s);
  }
  if(str_contains($s,','))$s=str_replace(',','.',$s);
  $s=preg_replace('/[^0-9.\-]/','',$s);
  return is_numeric($s)?(float)$s:0.0;
}
function import_next_item_id(array $s): int {
  $m=0;foreach(($s['requests']??[]) as $r)foreach(($r['items']??[]) as $i)$m=max($m,(int)($i['id']??0));
  return $m+1;
}
function import_request_no(array &$s): string {
  $cfg=$s['requestNumber']??['prefix'=>'RFQ','format'=>'prefix-year-seq','next'=>1,'pattern'=>'{PREFIX}-{YEAR}-{SEQ4}'];
  $prefix=trim((string)($cfg['prefix']??'RFQ'))?:'RFQ';$year=date('Y');$seq=max(1,(int)($cfg['next']??1));
  do{
    $seq4=str_pad((string)$seq,4,'0',STR_PAD_LEFT);
    $fmt=(string)($cfg['format']??'prefix-year-seq');
    if($fmt==='prefix-seq')$no=$prefix.'-'.$seq4;
    elseif($fmt==='custom')$no=str_replace(['{PREFIX}','{YEAR}','{SEQ4}','{SEQ}'],[$prefix,$year,$seq4,(string)$seq],(string)($cfg['pattern']??'{PREFIX}-{YEAR}-{SEQ4}'));
    else $no=$prefix.'-'.$year.'-'.$seq4;
    $seq++;
  }while(array_filter($s['requests']??[],fn($r)=>(string)($r['no']??'')===$no));
  $s['requestNumber']=$cfg;$s['requestNumber']['next']=$seq;return $no;
}
try{
 $pdo=db();$postCommitDeletes=[];$pdo->beginTransaction();$row=$pdo->query('SELECT state_json,revision FROM app_state WHERE id=1 FOR UPDATE')->fetch();if(!$row)throw new RuntimeException('Uygulama state bulunamadı.');$s=json_decode($row['state_json'],true)?:[];$s['requests']=$s['requests']??[];$s['orders']=$s['orders']??[];$s['accounts']=$s['accounts']??[];$s['cashAccounts']=$s['cashAccounts']??[];$s['cash']=$s['cash']??[];
 $idem=trim((string)($in['idempotencyKey']??''));
 if($idem!==''){
   $iq=$pdo->prepare('SELECT action_type FROM operation_idempotency WHERE idempotency_key=? LIMIT 1');$iq->execute([$idem]);
   if($iq->fetchColumn()){ $pdo->commit(); json_response(['ok'=>true,'state'=>$s,'revision'=>(int)$row['revision'],'duplicate'=>true]); }
 }
 if($action==='quote_item'||$action==='quote_total'){
   $rid=(int)$in['requestId'];$r=&find_request($s,$rid);if(!$r||empty($r['customerQuote']))throw new RuntimeException('Müşteri teklifi bulunamadı.');$q=&$r['customerQuote'];$oldTotal=(float)($q['total']??0);
   if($action==='quote_item'){$iid=(int)$in['itemId'];$uid=trim((string)($in['itemUid']??''));$found=false;foreach($q['items'] as &$it){$uidMatch=$uid!==''&&(string)($it['itemUid']??'')===$uid;$idMatch=$uid===''&&(int)$it['itemId']===$iid;if($uidMatch||$idMatch){$it['sale']=max(0,(float)$in['value']);$it['total']=$it['sale']*(float)($it['qty']??0);$found=true;break;}}if(!$found)throw new RuntimeException('Teklif kalemi bulunamadı.');}
   else{$newTotal=max(0,(float)$in['value']);$cur=0;foreach($q['items'] as $it)$cur+=(float)($it['total']??0);if($cur>0){foreach($q['items'] as &$it){$ratio=(float)($it['total']??0)/$cur;$it['total']=$newTotal*$ratio;$it['sale']=((float)($it['qty']??0)>0)?$it['total']/(float)$it['qty']:0;}}elseif(count($q['items'])){$each=$newTotal/count($q['items']);foreach($q['items'] as &$it){$it['total']=$each;$it['sale']=((float)($it['qty']??0)>0)?$each/(float)$it['qty']:0;}}}
   $q['total']=0;foreach($q['items'] as $it)$q['total']+=(float)$it['total'];$mode=(string)($q['costMode']??'raw');$q['cost']=$mode==='landed'?(float)($q['landedCost']??0):(float)($q['rawCost']??0);$q['profit']=$q['total']-$q['cost'];$delta=$q['total']-$oldTotal;
   $o=&find_order_by_request($s,$rid);if($o&&!empty($o['financialCancelled']))throw new RuntimeException('İptal/kayıp durumundaki siparişte satış tutarı değiştirilemez. Önce durumu yeniden Kazanıldı yapın.');if($o){$paid=(float)($o['customerPaid']??0);if($q['total']+0.001<$paid)throw new RuntimeException('Yeni satış toplamı daha önce tahsil edilmiş tutardan küçük olamaz. Önce ters tahsilat yapın.');$o['total']=$q['total'];$o['customerDue']=max(0,$q['total']-$paid);reprice_payment_schedule($o,(float)$q['total']);if(!empty($o['receivableOpened'])&&abs($delta)>0.001){$a=&customer_account($s,$r);$a['requestOpen']=(float)($a['requestOpen']??0)+$delta;add_entry($a,$r['no'],'Müşteri teklifi satış tutarı revizyon farkı',max(0,$delta),max(0,-$delta));}}
   $r['statusHistory']=$r['statusHistory']??[];$r['statusHistory'][]=['date'=>date('c'),'event'=>'quote_amount_update','user'=>$u['name'],'oldTotal'=>$oldTotal,'newTotal'=>$q['total']];audit('quote_amount_update','request',(string)$rid,['old'=>$oldTotal,'new'=>$q['total']]);
 }


 elseif($action==='request_update'){
   $rid=(int)($in['requestId']??0);$r=&find_request($s,$rid);if(!$r)throw new RuntimeException('Düzenlenecek talep bulunamadı.');if(has_live_order_for_request($s,$rid))throw new RuntimeException('Siparişe dönüşmüş talebin ana kalemleri bu ekrandan değiştirilemez.');
   $title=trim((string)($in['title']??''));$customer=trim((string)($in['customer']??''));if($title==='')throw new RuntimeException('Konu Başlığı zorunludur.');if($customer==='')throw new RuntimeException('Müşteri / kurum seçimi zorunludur.');
   $currency=strtoupper((string)($in['currency']??($r['currency']??'EUR')));if(!in_array($currency,['EUR','USD','TRY'],true))throw new RuntimeException('Geçersiz para birimi.');
   $oldIds=[];foreach(($r['items']??[]) as $x)$oldIds[(int)($x['id']??0)]=true;$used=[];$usedUid=[];$maxId=0;foreach(($s['requests']??[]) as $rq)foreach(($rq['items']??[]) as $x)$maxId=max($maxId,(int)($x['id']??0));$items=[];
   foreach((array)($in['items']??[]) as $it){$id=(int)($it['id']??0);if($id<=0||isset($used[$id]))$id=++$maxId;$used[$id]=true;$name=trim((string)($it['name']??''));if($name==='')throw new RuntimeException('Talep kalemlerinde ürün adı boş olamaz.');$uid=trim((string)($it['uid']??''));if($uid===''||isset($usedUid[$uid]))$uid='req'.$rid.'-item-'.$id;$base=$uid;$n=2;while(isset($usedUid[$uid]))$uid=$base.'-'.$n++;$usedUid[$uid]=true;$items[]=['id'=>$id,'uid'=>$uid,'name'=>$name,'qty'=>max(0,(float)($it['qty']??0)),'unit'=>trim((string)($it['unit']??'Adet'))?:'Adet','brandModel'=>trim((string)($it['brandModel']??'')),'spec'=>trim((string)($it['spec']??''))];}
   if(!count($items))throw new RuntimeException('En az bir talep kalemi gerekli.');
   $r['title']=$title;$r['customer']=$customer;$r['customerPartyKey']=(string)($in['customerPartyKey']??($r['customerPartyKey']??''));$r['currency']=$currency;$r['payment']=(string)($in['payment']??($r['payment']??''));$r['paymentType']=(string)($in['paymentType']??($r['paymentType']??''));$r['paymentPlan']=(array)($in['paymentPlan']??($r['paymentPlan']??[]));$r['priority']=(string)($in['priority']??($r['priority']??'normal'));$r['bank']=(string)($in['bank']??($r['bank']??''));$r['bankAccountId']=isset($in['bankAccountId'])&&$in['bankAccountId']!==''?(int)$in['bankAccountId']:null;$r['delivery']=(string)($in['delivery']??($r['delivery']??''));$r['deliveryPlace']=trim((string)($in['deliveryPlace']??($r['deliveryPlace']??'')));$r['deadline']=(string)($in['deadline']??($r['deadline']??''));$r['description']=trim((string)($in['description']??($r['description']??'')));$r['items']=$items;
   // Remove selections for deleted item IDs; supplier offers for retained IDs remain intact.
   $valid=array_fill_keys(array_map(fn($x)=>(int)$x['id'],$items),true);foreach(array_keys($s['selected']??[]) as $k){if(str_starts_with((string)$k,$rid.':')){$parts=explode(':',(string)$k,2);$iid=(int)($parts[1]??0);if(!isset($valid[$iid]))unset($s['selected'][$k]);}}
   if(!empty($r['customerQuote']))sync_customer_quote_state($s,$r);audit('request_update','request',(string)$rid,['items'=>count($items),'title'=>$title]);
 }
 elseif($action==='supplier_quote_save'){
   $rid=(int)($in['requestId']??0);$r=&find_request($s,$rid);if(!$r)throw new RuntimeException('Talep bulunamadı.');if(has_live_order_for_request($s,$rid))throw new RuntimeException('Siparişe dönüşmüş talepte tedarikçi teklifi doğrudan değiştirilemez.');
   $sid=(int)($in['supplierId']??0);$name=trim((string)($in['name']??''));if($name==='')throw new RuntimeException('Tedarikçi adı gerekli.');
   $offerBase=[];$vat=[];$vatGross=[];$technical=[];$offers=[];
   foreach((array)($in['offerBase']??[]) as $k=>$v){
     $iid=(string)((int)$k);$raw=trim((string)$v);
     if(!preg_match('/^\d+(?:\.\d+)?$/',$raw))throw new RuntimeException('Geçersiz tedarikçi birim fiyatı: '.$raw);
     $raw=preg_replace('/^0+(?=\d)/','',$raw);if($raw==='')$raw='0';
     $vm=(array)(($in['vat']??[])[$k]??($in['vat']??[])[$iid]??[]);
     $mode=(string)($vm['mode']??'excluded');if(!in_array($mode,['excluded','included'],true))$mode='excluded';
     $rate=max(0,min(100,(float)($vm['rate']??0)));
     $tech=(string)(($in['technical']??[])[$k]??($in['technical']??[])[$iid]??'suitable');
     if(!in_array($tech,['suitable','conditional','unsuitable'],true))$tech='suitable';
     $offerBase[$iid]=$raw;$vat[$iid]=['mode'=>$mode,'rate'=>$rate];$technical[$iid]=$tech;
     $vatGross[$iid]=supplier_vat_gross($raw,$mode,$rate);
     // V3.9.3: offers alanı kıyaslama için KDV Hariç (net) fiyatı taşır.
     // Kullanıcının aynen girdiği rakam offerBase içinde korunur.
     $offers[$iid]=decimal_string(supplier_vat_net($raw,$mode,$rate),12);
   }
   $currency=strtoupper((string)($in['currency']??($r['currency']??'EUR')));if(!in_array($currency,['TRY','EUR','USD'],true))$currency=$r['currency']??'EUR';
   $fxIn=(array)($in['fxSnapshot']??[]);$fxBase=$s['fx']??[];$usd=(float)($fxIn['usdTry']??$fxBase['usdTry']??0);$eur=(float)($fxIn['eurTry']??$fxBase['eurTry']??0);if(!($usd>0)||!($eur>0))throw new RuntimeException('Geçerli TCMB USD/EUR kuru bulunamadı. Teklif kaydından önce kuru yenileyin.');$fxSnapshot=['usdTry'=>$usd,'eurTry'=>$eur,'source'=>(string)($fxIn['source']??$fxBase['source']??'TCMB'),'updated'=>(string)($fxIn['updated']??$fxBase['updated']??date('c')),'capturedAt'=>(string)($fxIn['capturedAt']??date('c'))];$fxSnapshot['eurUsd']=$fxSnapshot['eurTry']/$fxSnapshot['usdTry'];
   $data=['requestId'=>$rid,'name'=>$name,'ref'=>trim((string)($in['ref']??''))?:('SUP-'.time()),'validity'=>(string)($in['validity']??'15 gün'),'payment'=>(string)($in['payment']??''),'delivery'=>max(0,(int)($in['delivery']??0)),'currency'=>$currency,'fxSnapshot'=>$fxSnapshot,'freight'=>max(0,round((float)($in['freight']??0),2)),'description'=>trim((string)($in['description']??'')),'offerBase'=>$offerBase,'vat'=>$vat,'vatGross'=>$vatGross,'technical'=>$technical,'offers'=>$offers];
   $found=false;if($sid)foreach($s['suppliers'] as &$sp)if((int)($sp['id']??0)===$sid&&(int)($sp['requestId']??0)===$rid){$sp=array_merge($sp,$data);$found=true;break;}if($sid&&!$found)throw new RuntimeException('Düzenlenecek tedarikçi teklifi bulunamadı. Sayfayı yenileyip tekrar deneyin.');if(!$sid){$data['id']=next_id($s['suppliers']);$s['suppliers'][]=$data;$sid=(int)$data['id'];}
   $dirFound=false;foreach(($s['supplierDirectory']??[]) as $sd)if(mb_strtoupper(trim((string)($sd['name']??'')))===mb_strtoupper($name)){$dirFound=true;break;}if(!$dirFound){$s['supplierDirectory']=$s['supplierDirectory']??[];$s['supplierDirectory'][]=['id'=>next_id($s['supplierDirectory']),'name'=>$name,'contact'=>'','email'=>'','phone'=>'','country'=>'','note'=>''];}
   // V3.9.3: Teklif kaydedilen tedarikçi, Cari modülünde otomatik olarak görünmelidir.
   // Aynı firma/para biriminde hesap varsa tekrar oluşturulmaz.
   $supplierAcc=&supplier_account($s,$name,$currency,$r['no']??'');
   if(!empty($r['customerQuote'])){sync_customer_quote_state($s,$r);$r['customerQuote']['revision']=(int)($r['customerQuote']['revision']??0)+1;$r['customerQuote']['status']='Taslak';if(($r['status']??'')==='sent')$r['status']='quote';$r['status']='quote';$s['customerQuotes'][(string)$rid]=$r['customerQuote'];}
   audit('supplier_quote_save','request',(string)$rid,['supplier_id'=>$sid,'supplier'=>$name,'currency'=>$currency,'fx_snapshot'=>$fxSnapshot]);
 }
 elseif($action==='supplier_quote_delete'){
   $rid=(int)($in['requestId']??0);$sid=(int)($in['supplierId']??0);
   $r=&find_request($s,$rid);if(!$r)throw new RuntimeException('Talep bulunamadı.');
   if(has_live_order_for_request($s,$rid))throw new RuntimeException('Siparişe dönüşmüş talepte tedarikçi teklifi silinemez.');
   $idx=null;$deleted=null;
   foreach(($s['suppliers']??[]) as $i=>$sp)if((int)($sp['id']??0)===$sid&&(int)($sp['requestId']??0)===$rid){$idx=$i;$deleted=$sp;break;}
   if($idx===null)throw new RuntimeException('Tedarikçi teklifi bulunamadı.');
   array_splice($s['suppliers'],$idx,1);

   $s['selected']=$s['selected']??[];
   foreach(array_keys($s['selected']) as $k)if((int)($s['selected'][$k]??0)===$sid)unset($s['selected'][$k]);

   if(!empty($r['customerQuote'])){
     sync_customer_quote_state($s,$r);
     $r['customerQuote']['revision']=(int)($r['customerQuote']['revision']??0)+1;
     $r['customerQuote']['status']='Taslak';
     $r['status']='quote';
     $s['customerQuotes'][(string)$rid]=$r['customerQuote'];
   }
   audit('supplier_quote_delete','request',(string)$rid,['supplier_id'=>$sid,'supplier'=>$deleted['name']??'']);
 }
 elseif($action==='guarantee_config_save'){
   $rid=(int)($in['requestId']??0);$r=&find_request($s,$rid);if(!$r)throw new RuntimeException('Talep bulunamadı.');
   $enabled=!empty($in['enabled']);if(!$enabled){foreach(($s['guarantees']??[]) as $gg)if((int)($gg['requestId']??0)===$rid&&($gg['status']??'active')!=='released')throw new RuntimeException('Aktif teminat kaydı varken Teminat Yok seçilemez. Önce teminatı çözün veya silin.');}$rate=max(0,(float)($in['rate']??0));$sales=max(0,(float)($in['salesAmount']??0));$amount=round($sales*$rate/100,2);
   $type=trim((string)($in['type']??'Geçici Teminat'));
   $r['guaranteeConfig']=['enabled'=>$enabled,'type'=>$type,'rate'=>$rate,'salesAmount'=>$sales,'amount'=>$enabled?$amount:0];
   audit('guarantee_config_save','request',(string)$rid,$r['guaranteeConfig']);
 }
 elseif($action==='guarantee_save'){
   $s['guarantees']=$s['guarantees']??[];$id=(int)($in['id']??0);$rid=(int)($in['requestId']??0);$r=$rid?find_request($s,$rid):null;
   $partyKey=trim((string)($in['partyKey']??''));$partyName=trim((string)($in['partyName']??($r['customer']??'')));
   if($partyKey!=='')foreach(($s['accounts']??[]) as $aa)if((string)($aa['partyKey']??'')===$partyKey){$partyName=(string)($aa['name']??$partyName);break;}
   if($partyName==='')throw new RuntimeException('Müşteri / kurum zorunludur.');
   $type=trim((string)($in['type']??'Geçici Teminat'));$allowedTypes=['Geçici Teminat','Kesin Teminat','Ek Kesin Teminat','Avans Teminat','Banka Teminat Mektubu','Diğer Teminat'];if(!in_array($type,$allowedTypes,true))throw new RuntimeException('Geçersiz teminat türü.');
   $instrument=(string)($in['instrument']??'cash');if(!in_array($instrument,['cash','letter'],true))throw new RuntimeException('Geçersiz teminat enstrümanı.');
   $cur=strtoupper((string)($in['currency']??($r['currency']??'EUR')));if(!in_array($cur,['EUR','USD','TRY'],true))throw new RuntimeException('Geçersiz para birimi.');
   $sales=max(0,(float)($in['salesAmount']??0));$rate=max(0,(float)($in['rate']??0));$amount=round($sales*$rate/100,2);if($amount<=0)throw new RuntimeException('Teminat tutarı sıfırdan büyük olmalı.');
   $bankId=(int)($in['bankId']??0);$bankName='';if($bankId){$bank=&cash_account_by_id($s,$bankId);if(!$bank)throw new RuntimeException('Banka/Kasa bulunamadı.');$bankName=(string)$bank['name'];if(strtoupper((string)($bank['currency']??''))!==$cur)throw new RuntimeException('Teminat ve banka/kasa para birimi aynı olmalı.');}
   $date=trim((string)($in['depositDate']??date('Y-m-d')));if(!preg_match('/^\d{4}-\d{2}-\d{2}$/',$date))throw new RuntimeException('Geçersiz yatırılma tarihi.');
   $expiry=trim((string)($in['expiryDate']??''));if($expiry!==''&&!preg_match('/^\d{4}-\d{2}-\d{2}$/',$expiry))throw new RuntimeException('Geçersiz vade tarihi.');
   $durationValue=max(0,(int)($in['durationValue']??0));$durationUnit=(string)($in['durationUnit']??'month');if(!in_array($durationUnit,['day','month','year'],true))$durationUnit='month';
   $fundNow=!empty($in['fundNow'])&&$instrument==='cash';if($fundNow&&!has_app_write_permission('cash',$u))throw new RuntimeException('Kasa/Banka çıkışı için cash yazma yetkisi gerekli.');$reference=trim((string)($in['reference']??''));$note=trim((string)($in['note']??''));
   $existingIndex=null;foreach($s['guarantees'] as $gi=>$gg)if((int)($gg['id']??0)===$id){$existingIndex=$gi;break;}
   $existing=$existingIndex!==null?$s['guarantees'][$existingIndex]:null;if($existing&&!empty($existing['funded'])&&($fundNow||$bankId!==(int)($existing['bankId']??0)||abs($amount-(float)($existing['amount']??0))>0.001))throw new RuntimeException('Finansal çıkış yapılmış teminatın banka/tutarı değiştirilemez. Önce teminatı çözün.');
   $funded=$existing['funded']??false;$journalId=$existing['journalId']??null;$cashId=$existing['cashId']??null;
   if($fundNow&&!$funded){if(!$bankId)throw new RuntimeException('Finansal çıkış için banka/kasa seçin.');$bank=&cash_account_by_id($s,$bankId);if((float)($bank['balance']??0)<$amount-0.001)throw new RuntimeException('Kasa/Banka bakiyesi yetersiz.');$bank['balance']=(float)$bank['balance']-$amount;$cashId=next_id($s['cash']);$group=uuid4();$s['cash'][]=['id'=>$cashId,'date'=>date('d.m.Y',strtotime($date)),'accountId'=>$bankId,'account'=>$bankName,'currency'=>$cur,'request'=>$r['no']??'','party'=>$partyName,'type'=>'Çıkış','amountValue'=>$amount,'amount'=>$amount,'note'=>'Teminat yatırma / '.$type,'archived'=>false,'kind'=>'guarantee_deposit','groupUuid'=>$group,'reversed'=>false];$q=$pdo->prepare('INSERT INTO finance_journal(group_uuid,action_type,request_id,party_name,cash_account_id,currency,amount,payload_json,created_by) VALUES(?,?,?,?,?,?,?,?,?)');$q->execute([$group,'guarantee_deposit',$rid?:null,$partyName,$bankId,$cur,$amount,json_encode(['cashId'=>$cashId,'reference'=>$reference,'type'=>$type],JSON_UNESCAPED_UNICODE),$u['id']]);$journalId=(int)$pdo->lastInsertId();foreach($s['cash'] as &$cc)if((int)($cc['id']??0)===$cashId)$cc['journalId']=$journalId;$funded=true;}
   $row=['id'=>$id?:next_id($s['guarantees']),'requestId'=>$rid,'requestNo'=>$r['no']??'','partyKey'=>$partyKey,'partyName'=>$partyName,'type'=>$type,'instrument'=>$instrument,'currency'=>$cur,'salesAmount'=>$sales,'rate'=>$rate,'amount'=>$amount,'bankId'=>$bankId,'bankName'=>$bankName,'depositDate'=>$date,'durationValue'=>$durationValue,'durationUnit'=>$durationUnit,'expiryDate'=>$expiry,'reference'=>$reference,'note'=>$note,'funded'=>$funded,'cashId'=>$cashId,'journalId'=>$journalId,'status'=>$existing['status']??'active','createdAt'=>$existing['createdAt']??date('c'),'updatedAt'=>date('c')];
   if($existingIndex!==null)$s['guarantees'][$existingIndex]=$row;else$s['guarantees'][]=$row;
   if($r){$r['guaranteeConfig']=['enabled'=>true,'type'=>$type,'rate'=>$rate,'salesAmount'=>$sales,'amount'=>$amount];}
   audit('guarantee_save','guarantee',(string)$row['id'],['request_id'=>$rid,'amount'=>$amount,'currency'=>$cur,'funded'=>$funded,'instrument'=>$instrument]);
 }
 elseif($action==='guarantee_release'){
   $id=(int)($in['id']??0);$date=trim((string)($in['date']??date('Y-m-d')));if(!preg_match('/^\d{4}-\d{2}-\d{2}$/',$date))$date=date('Y-m-d');$s['guarantees']=$s['guarantees']??[];$idx=null;foreach($s['guarantees'] as $i=>$g)if((int)($g['id']??0)===$id){$idx=$i;break;}if($idx===null)throw new RuntimeException('Teminat bulunamadı.');$g=&$s['guarantees'][$idx];if(($g['status']??'active')==='released')throw new RuntimeException('Teminat zaten çözülmüş.');
   if(!empty($g['funded'])&&($g['instrument']??'cash')==='cash'){if(!has_app_write_permission('cash',$u))throw new RuntimeException('Teminat iadesi için cash yazma yetkisi gerekli.');$bankId=(int)($g['bankId']??0);$bank=&cash_account_by_id($s,$bankId);if(!$bank)throw new RuntimeException('Teminatın banka/kasa hesabı bulunamadı.');$amount=(float)$g['amount'];$bank['balance']=(float)$bank['balance']+$amount;$cashId=next_id($s['cash']);$group=uuid4();$s['cash'][]=['id'=>$cashId,'date'=>date('d.m.Y',strtotime($date)),'accountId'=>$bankId,'account'=>$bank['name'],'currency'=>$g['currency'],'request'=>$g['requestNo']??'','party'=>$g['partyName'],'type'=>'Giriş','amountValue'=>$amount,'amount'=>$amount,'note'=>'Teminat çözümü / iadesi','archived'=>false,'kind'=>'guarantee_release','groupUuid'=>$group,'reversed'=>false];$q=$pdo->prepare('INSERT INTO finance_journal(group_uuid,action_type,request_id,party_name,cash_account_id,currency,amount,payload_json,created_by) VALUES(?,?,?,?,?,?,?,?,?)');$q->execute([$group,'guarantee_release',(int)($g['requestId']??0)?:null,$g['partyName'],$bankId,$g['currency'],$amount,json_encode(['cashId'=>$cashId,'guaranteeId'=>$id,'date'=>$date],JSON_UNESCAPED_UNICODE),$u['id']]);$g['releaseJournalId']=(int)$pdo->lastInsertId();$g['releaseCashId']=$cashId;}
   $g['status']='released';$g['releasedAt']=date('c');$g['releaseDate']=$date;audit('guarantee_release','guarantee',(string)$id,['amount'=>$g['amount'],'currency'=>$g['currency']]);
 }
 elseif($action==='guarantee_delete'){
   $id=(int)($in['id']??0);$found=null;foreach(($s['guarantees']??[]) as $g)if((int)($g['id']??0)===$id){$found=$g;break;}if(!$found)throw new RuntimeException('Teminat bulunamadı.');if(!empty($found['funded'])&&($found['status']??'active')!=='released')throw new RuntimeException('Finansal çıkışı olan aktif teminat silinemez. Önce teminatı çözün.');$s['guarantees']=array_values(array_filter($s['guarantees'],fn($g)=>(int)($g['id']??0)!==$id));audit('guarantee_delete','guarantee',(string)$id,[]);
 }
 elseif($action==='comparison_expenses_save'){
   throw new RuntimeException('Legacy Ek Maliyetler kaydı kapatıldı. Maliyet > Diğer Giderler / Gerçek Ödemeler akışını kullanın.');
   $rid=(int)($in['requestId']??0);$r=&find_request($s,$rid);if(!$r)throw new RuntimeException('Talep bulunamadı.');
   if(has_live_order_for_request($s,$rid))throw new RuntimeException('Siparişe dönüşmüş talepte kıyaslama giderleri değiştirilemez.');
   $cur=strtoupper((string)($in['currency']??($r['comparisonCurrency']??$r['currency']??'EUR')));
   if(!in_array($cur,['EUR','USD','TRY'],true))throw new RuntimeException('Geçersiz gider para birimi.');
   $src=(array)($in['expenses']??[]);$clean=[];
   foreach(['freight','customs','unloading','inland'] as $key){
     $raw=trim((string)($src[$key]??'0'));
     if(!preg_match('/^\d+(?:\.\d+)?$/',$raw))throw new RuntimeException('Geçersiz ek gider tutarı: '.$key);
     $clean[$key]=$raw;
   }
   $fxIn=(array)($in['fxSnapshot']??[]);$fxBase=$s['fx']??[];
   $fxSnapshot=[
     'usdTry'=>max(0.0000001,(float)($fxIn['usdTry']??$fxBase['usdTry']??1)),
     'eurTry'=>max(0.0000001,(float)($fxIn['eurTry']??$fxBase['eurTry']??1)),
     'source'=>(string)($fxIn['source']??$fxBase['source']??'TCMB/Fallback'),
     'updated'=>(string)($fxIn['updated']??$fxBase['updated']??date('c')),
     'capturedAt'=>(string)($fxIn['capturedAt']??date('c'))
   ];
   $fxSnapshot['eurUsd']=$fxSnapshot['eurTry']/$fxSnapshot['usdTry'];
   $r['comparisonExpenses']=array_merge(['currency'=>$cur,'fxSnapshot'=>$fxSnapshot],$clean);
   audit('comparison_expenses_save','request',(string)$rid,['currency'=>$cur,'expenses'=>$clean]);
 }
 elseif($action==='cost_lines_save'){
   $rid=(int)($in['requestId']??0);$r=&find_request($s,$rid);if(!$r)throw new RuntimeException('Talep bulunamadı.');if(has_live_order_for_request($s,$rid))throw new RuntimeException('Siparişe dönüşmüş talepte maliyet kalemleri değiştirilemez.');
   $clean=[];$fxIn=(array)($in['fxSnapshot']??[]);$fxBase=$s['fx']??[];$fx=['usdTry'=>(float)($fxIn['usdTry']??$fxBase['usdTry']??0),'eurTry'=>(float)($fxIn['eurTry']??$fxBase['eurTry']??0),'source'=>(string)($fxIn['source']??$fxBase['source']??'TCMB'),'updated'=>(string)($fxIn['updated']??$fxBase['updated']??date('c')),'capturedAt'=>(string)($fxIn['capturedAt']??date('c'))];
   foreach((array)($in['lines']??[]) as $line){$amount=max(0,(float)($line['amount']??0));$cur=strtoupper((string)($line['currency']??($r['comparisonCurrency']??$r['currency']??'EUR')));if(!in_array($cur,['EUR','USD','TRY'],true))throw new RuntimeException('Geçersiz gider para birimi.');$partyId=trim((string)($line['partyId']??''));$partyName='';if($partyId!==''){foreach(($s['accounts']??[]) as $a)if((string)($a['partyKey']??'')===$partyId){$partyName=(string)($a['name']??'');break;}}$lineId=trim((string)($line['id']??''))?:('cost-'.bin2hex(random_bytes(4)));$oldLine=null;foreach(($r['costLines']??[]) as $ol)if((string)($ol['id']??'')===$lineId){$oldLine=$ol;break;}$clean[]=['id'=>$lineId,'category'=>trim((string)($line['category']??'')),'partyId'=>$partyId,'partyName'=>$partyName,'description'=>trim((string)($line['description']??'')),'invoiceNo'=>trim((string)($line['invoiceNo']??$oldLine['invoiceNo']??'')),'invoiceDate'=>trim((string)($line['invoiceDate']??$oldLine['invoiceDate']??'')),'dueDate'=>trim((string)($line['dueDate']??$oldLine['dueDate']??'')),'attachmentId'=>(int)($line['attachmentId']??$oldLine['attachmentId']??0),'amount'=>$amount,'currency'=>$cur,'fxSnapshot'=>$fx,'realizedJournalId'=>(int)($oldLine['realizedJournalId']??$line['realizedJournalId']??0)];}
   $r['costLines']=$clean;$r['costFxSnapshot']=$fx;if(!empty($r['customerQuote']))sync_customer_quote_state($s,$r);audit('cost_lines_save','request',(string)$rid,['count'=>count($clean)]);
 }
 elseif($action==='quote_cost_mode'){
   $rid=(int)($in['requestId']??0);$mode=(string)($in['mode']??'raw');if(!in_array($mode,['raw','landed'],true))throw new RuntimeException('Geçersiz maliyet modu.');$r=&find_request($s,$rid);if(!$r||empty($r['customerQuote']))throw new RuntimeException('Müşteri teklifi bulunamadı.');$r['customerQuote']['costMode']=$mode;sync_customer_quote_state($s,$r);audit('quote_cost_mode','request',(string)$rid,['mode'=>$mode]);
 }
 elseif($action==='comparison_currency'){
   $rid=(int)($in['requestId']??0);$cur=strtoupper((string)($in['currency']??'EUR'));if(!in_array($cur,['EUR','USD','TRY'],true))throw new RuntimeException('Geçersiz kıyas para birimi.');
   $r=&find_request($s,$rid);if(!$r)throw new RuntimeException('Talep bulunamadı.');$r['comparisonCurrency']=$cur;audit('comparison_currency','request',(string)$rid,['currency'=>$cur]);
 }
 elseif($action==='comparison_select'){
   $rid=(int)($in['requestId']??0);$iid=(int)($in['itemId']??0);$sid=(int)($in['supplierId']??0);$r=&find_request($s,$rid);if(!$r)throw new RuntimeException('Talep bulunamadı.');if(has_live_order_for_request($s,$rid))throw new RuntimeException('Siparişe dönüşmüş talepte kıyaslama değiştirilemez.');
   $itemOk=false;foreach(($r['items']??[]) as $it)if((int)($it['id']??0)===$iid){$itemOk=true;break;}if(!$itemOk)throw new RuntimeException('Talep kalemi bulunamadı.');
   if($sid){$ok=false;$badTech=false;foreach(($s['suppliers']??[]) as $sp)if((int)($sp['id']??0)===$sid&&(int)($sp['requestId']??0)===$rid&&(float)($sp['offers'][$iid]??0)>0){$tech=(string)($sp['technical'][$iid]??'suitable');if($tech==='unsuitable'){$badTech=true;break;}$ok=true;break;}if($badTech)throw new RuntimeException('Teknik olarak Uygun Değil işaretlenen teklif seçilemez.');if(!$ok)throw new RuntimeException('Seçilen tedarikçide bu kalem için geçerli fiyat yok.');}
   $s['selected']=$s['selected']??[];$key=$rid.':'.$iid;if($sid)$s['selected'][$key]=$sid;else unset($s['selected'][$key]);unset($s['selected'][(string)$iid]);
   if(!empty($r['customerQuote'])){sync_customer_quote_state($s,$r);$r['customerQuote']['revision']=(int)($r['customerQuote']['revision']??0)+1;$r['customerQuote']['status']='Taslak';$r['status']='quote';$s['customerQuotes'][(string)$rid]=$r['customerQuote'];}audit('comparison_change','request',(string)$rid,['item_id'=>$iid,'supplier_id'=>$sid?:null]);
 }
 elseif($action==='comparison_auto_best'){
   $rid=(int)($in['requestId']??0);$r=&find_request($s,$rid);if(!$r)throw new RuntimeException('Talep bulunamadı.');if(has_live_order_for_request($s,$rid))throw new RuntimeException('Siparişe dönüşmüş talepte kıyaslama değiştirilemez.');$s['selected']=$s['selected']??[];
   $cmp=strtoupper((string)($r['comparisonCurrency']??$r['currency']??'EUR'));foreach(($r['items']??[]) as $it){$iid=(int)$it['id'];$bestId=0;$best=null;foreach(($s['suppliers']??[]) as $sp){if((int)($sp['requestId']??0)!==$rid)continue;$raw=(string)($sp['offerBase'][$iid]??$sp['offers'][$iid]??'0');$vm=(array)($sp['vat'][$iid]??[]);$mode=(string)($vm['mode']??'excluded');$rate=(float)($vm['rate']??0);$net=supplier_vat_net($raw,$mode,$rate);$tech=(string)($sp['technical'][$iid]??'suitable');$from=supplier_currency($sp,$r);$p=$net>0&&$tech!=='unsuitable'?($from===$cmp?$net:fx_convert($net,$from,$cmp,supplier_fx($s,$sp))):0;if($p>0&&($best===null||$p<$best)){$best=$p;$bestId=(int)$sp['id'];}}if($bestId)$s['selected'][$rid.':'.$iid]=$bestId;}
   if(!empty($r['customerQuote'])){sync_customer_quote_state($s,$r);$r['customerQuote']['revision']=(int)($r['customerQuote']['revision']??0)+1;$r['customerQuote']['status']='Taslak';$r['status']='quote';$s['customerQuotes'][(string)$rid]=$r['customerQuote'];}audit('comparison_auto_best','request',(string)$rid,[]);
 }
 elseif($action==='customer_quote_create'){
   $rid=(int)($in['requestId']??0);$r=&find_request($s,$rid);if(!$r)throw new RuntimeException('Talep bulunamadı.');if(has_live_order_for_request($s,$rid))throw new RuntimeException('Siparişe dönüşmüş talepte yeni müşteri teklifi oluşturulamaz.');if(!full_comparison_state($s,$r))throw new RuntimeException('Her talep kaleminde fiyatlı bir tedarikçi seçilmelidir.');
   if(empty($r['customerQuote']))$r['customerQuote']=['no'=>next_customer_quote_no($s),'revision'=>0,'status'=>'Taslak','currency'=>$r['currency']??'EUR','created'=>date('d.m.Y')];$mode=(string)($in['costMode']??($r['customerQuote']['costMode']??'raw'));$r['customerQuote']['costMode']=in_array($mode,['raw','landed'],true)?$mode:'raw';sync_customer_quote_state($s,$r);$r['status']='quote';$r['customerQuote']['status']=request_quote_status('quote');$s['customerQuotes'][(string)$rid]=$r['customerQuote'];audit('customer_quote_create','request',(string)$rid,['quote_no'=>$r['customerQuote']['no']]);
 }
 elseif($action==='quote_revision'){
   $rid=(int)($in['requestId']??0);$r=&find_request($s,$rid);if(!$r||empty($r['customerQuote']))throw new RuntimeException('Müşteri teklifi bulunamadı.');
   if(find_order_by_request($s,$rid))throw new RuntimeException('Siparişe dönüşmüş teklifte yeni revizyon açılamaz. Önce sipariş revizyon süreci kullanılmalı.');
   $r['customerQuote']['revision']=(int)($r['customerQuote']['revision']??0)+1;$r['customerQuote']['status']='Taslak';$r['status']='quote';
   $r['statusHistory']=$r['statusHistory']??[];$r['statusHistory'][]=['date'=>date('c'),'event'=>'quote_revision','revision'=>$r['customerQuote']['revision'],'user'=>$u['name']];audit('quote_revision','request',(string)$rid,['revision'=>$r['customerQuote']['revision']]);
 }
 elseif($action==='quote_mark_sent'){
   $rid=(int)($in['requestId']??0);$r=&find_request($s,$rid);if(!$r||empty($r['customerQuote']))throw new RuntimeException('Müşteri teklifi bulunamadı.');
   $r['customerQuote']['status']='Gönderildi';$r['status']='sent';$r['sentAt']=date('c');$r['statusHistory']=$r['statusHistory']??[];$r['statusHistory'][]=['date'=>date('c'),'event'=>'quote_sent','user'=>$u['name']];audit('quote_sent','request',(string)$rid);
 }
 elseif($action==='po_status'){
   $poId=(int)($in['poId']??0);$orderId=(int)($in['orderId']??0);$poNo=trim((string)($in['poNo']??''));$batchId=(int)($in['batchId']??0);$status=(string)($in['status']??'');if(!in_array($status,asay_po_statuses(),true))throw new RuntimeException('Geçersiz PO durumu.');
   $owner=null;$po=&find_po_context($s,$poId,$orderId,$poNo,$owner);if(!$po||!$owner)throw new RuntimeException('PO bulunamadı. Sipariş/PO eşleşmesini yenilemek için sayfayı yenileyin. [PO ID: '.$poId.' · Sipariş ID: '.$orderId.' · PO No: '.($poNo?:'—').']');if(!is_valid_operational_order_state($s,$owner))throw new RuntimeException('Kapalı, iptal, kayıp veya geçersiz sipariş PO durumu değiştirilemez.');
   if(order_has_active_batches($owner)&&in_array($status,['Kısmi Teslimat','Teslim Edildi'],true))throw new RuntimeException('Partili teslimat aktifken Kısmi Teslimat/Teslim Edildi durumu yalnız teslimat partilerinden hesaplanır.');
   $fromStatus=(string)($po['status']??'Hazırlanıyor');if(!asay_po_transition_allowed($fromStatus,$status))throw new RuntimeException($fromStatus.' durumundan '.$status.' durumuna doğrudan geçiş yapılamaz.');$po['status']=$status;$po['production']=asay_po_progress($status,(float)($po['production']??0));
   $actualPoId=(int)($po['id']??$poId);audit('po_status_change','purchase_order',(string)$actualPoId,['status'=>$status,'po_no'=>$po['no']??$poNo,'order_id'=>(int)($owner['id']??0)]);
 }

 elseif($action==='po_revision'){
   $poId=(int)($in['poId']??0);$orderId=(int)($in['orderId']??0);$poNo=trim((string)($in['poNo']??''));$note=trim((string)($in['note']??''));$owner=null;$po=&find_po_context($s,$poId,$orderId,$poNo,$owner);if(!$po||!$owner)throw new RuntimeException('PO bulunamadı. [PO ID: '.$poId.' · Sipariş ID: '.$orderId.' · PO No: '.($poNo?:'—').']');if(!is_valid_operational_order_state($s,$owner))throw new RuntimeException('Kapalı, iptal, kayıp veya geçersiz sipariş PO revizyonu yapılamaz.');
   $actualPoId=(int)($po['id']??$poId);$actualPoNo=(string)($po['no']??$poNo);
   $po['revisions']=$po['revisions']??[];$po['revisions'][]=['revision'=>(int)($po['revision']??0),'createdAt'=>date('c'),'createdBy'=>$u['name'],'note'=>$note,'snapshot'=>['status'=>$po['status']??'','production'=>$po['production']??0,'total'=>$po['total']??0,'paid'=>$po['paid']??0,'due'=>$po['due']??0,'payment'=>$po['payment']??'']];
   $po['revision']=(int)($po['revision']??0)+1;$po['revisionNote']=$note;$po['updatedAt']=date('c');audit('po_revision','purchase_order',(string)$actualPoId,['revision'=>$po['revision'],'note'=>$note,'po_no'=>$actualPoNo,'order_id'=>(int)($owner['id']??0)]);
 }
 elseif($action==='account_entry_update'){
   $accountId=(int)($in['accountId']??0);$entryIndex=(int)($in['entryIndex']??-1);$txnId=trim((string)($in['txnId']??''));$a=&account_by_id($s,$accountId);if(!$a)throw new RuntimeException('Cari alt hesabı bulunamadı.');
   $a['entries']=$a['entries']??[];if(!count($a['entries'])&&abs((float)($a['requestOpen']??0))>0.001){$opening=(float)$a['requestOpen'];$a['entries'][]=['txnId'=>'txn-'.bin2hex(random_bytes(6)),'date'=>date('Y-m-d'),'createdAt'=>date('c'),'createdBy'=>$u['name'],'ref'=>$a['request']??'DEVİR','desc'=>'Devreden Açılış Bakiyesi','debit'=>$opening>0?$opening:0,'credit'=>$opening<0?abs($opening):0];}
   $idx=$entryIndex;
   if($txnId!==''){foreach($a['entries'] as $i=>$e)if((string)($e['txnId']??'')===$txnId){$idx=(int)$i;break;}}
   if($idx<0||!isset($a['entries'][$idx]))throw new RuntimeException('Düzenlenecek cari hareketi bulunamadı. Ekstreyi yenileyip tekrar deneyin.');
   $date=trim((string)($in['date']??''));if(!preg_match('/^\d{4}-\d{2}-\d{2}$/',$date))throw new RuntimeException('Geçerli bir işlem tarihi seçin.');
   $direction=(string)($in['direction']??'');if(!in_array($direction,['debit','credit'],true))throw new RuntimeException('Geçersiz borç/alacak yönü.');
   $amount=round((float)($in['amount']??0),2);if($amount<=0)throw new RuntimeException('Tutar sıfırdan büyük olmalı.');$ref=trim((string)($in['ref']??''));$desc=trim((string)($in['description']??''));if($desc==='')throw new RuntimeException('Açıklama zorunlu.');
   $old=$a['entries'][$idx];
   $legacySystem=preg_match('/(tahsilat|ödeme|sipariş|teslimat|peşinat|gider|ters kayıt|alacağı|borcu|bakiye düzeltme)/iu',(string)($old['desc']??''));
   if(!empty($old['systemGenerated'])||!empty($old['journalId'])||!empty($old['groupUuid'])||!empty($old['kind'])||$legacySystem)throw new RuntimeException('Bu hareket finans/kasa/sipariş kaydıyla bağlantılıdır. Doğrudan düzenlenemez; Ters İşlem uygulayıp doğru kaydı oluşturun.');
   $oldOpen=(float)($a['requestOpen']??0);$oldEffect=(float)($old['debit']??0)-(float)($old['credit']??0);$history=(array)($old['editHistory']??[]);$history[]=['editedAt'=>date('c'),'editedBy'=>$u['name'],'before'=>['date'=>$old['date']??null,'ref'=>$old['ref']??null,'desc'=>$old['desc']??null,'debit'=>(float)($old['debit']??0),'credit'=>(float)($old['credit']??0)]];if(count($history)>20)$history=array_slice($history,-20);
   $a['entries'][$idx]=array_merge($old,['txnId'=>$old['txnId']??('txn-'.bin2hex(random_bytes(6))),'date'=>$date,'ref'=>$ref,'desc'=>$desc,'debit'=>$direction==='debit'?$amount:0,'credit'=>$direction==='credit'?$amount:0,'editedAt'=>date('c'),'editedBy'=>$u['name'],'editHistory'=>$history]);
   $newEffect=(float)$a['entries'][$idx]['debit']-(float)$a['entries'][$idx]['credit'];$newOpen=round($oldOpen+($newEffect-$oldEffect),2);$a['requestOpen']=$newOpen;sync_account_transaction_mirror($pdo,$s);audit('account_entry_update','account',(string)$accountId,['txn_id'=>$a['entries'][$idx]['txnId'],'old'=>['debit'=>(float)($old['debit']??0),'credit'=>(float)($old['credit']??0),'date'=>$old['date']??null,'ref'=>$old['ref']??null],'new'=>['debit'=>$a['entries'][$idx]['debit'],'credit'=>$a['entries'][$idx]['credit'],'date'=>$date,'ref'=>$ref],'request_open_before'=>$oldOpen,'request_open_after'=>$newOpen]);
 }
 elseif($action==='account_entry_delete'){
   $accountId=(int)($in['accountId']??0);$entryIndex=(int)($in['entryIndex']??-1);$txnId=trim((string)($in['txnId']??''));$a=&account_by_id($s,$accountId);if(!$a)throw new RuntimeException('Cari alt hesabı bulunamadı.');
   $a['entries']=$a['entries']??[];$idx=$entryIndex;if($txnId!==''){foreach($a['entries'] as $i=>$e)if((string)($e['txnId']??'')===$txnId){$idx=(int)$i;break;}}
   if($idx<0||!isset($a['entries'][$idx]))throw new RuntimeException('Silinecek cari hareketi bulunamadı. Ekstreyi yenileyip tekrar deneyin.');
   $old=$a['entries'][$idx];$legacySystem=preg_match('/(tahsilat|ödeme|sipariş|teslimat|peşinat|gider|ters kayıt|alacağı|borcu|bakiye düzeltme)/iu',(string)($old['desc']??''));
   if(!empty($old['systemGenerated'])||!empty($old['journalId'])||!empty($old['groupUuid'])||!empty($old['kind'])||$legacySystem)throw new RuntimeException('Bağlı finans/sipariş hareketi doğrudan silinemez. İlgili finans işlemini iptal edin.');
   $oldOpen=(float)($a['requestOpen']??0);$effect=(float)($old['debit']??0)-(float)($old['credit']??0);array_splice($a['entries'],$idx,1);$a['requestOpen']=round($oldOpen-$effect,2);
   sync_account_transaction_mirror($pdo,$s);audit('account_entry_delete','account',(string)$accountId,['txn_id'=>$old['txnId']??null,'deleted'=>['date'=>$old['date']??null,'ref'=>$old['ref']??null,'desc'=>$old['desc']??null,'debit'=>(float)($old['debit']??0),'credit'=>(float)($old['credit']??0)],'request_open_before'=>$oldOpen,'request_open_after'=>$a['requestOpen']]);
 }
 elseif($action==='cash_account_upsert'){
   $id=(int)($in['id']??0);$name=trim((string)($in['name']??''));$type=(string)($in['type']??'Banka');$currency=(string)($in['currency']??'EUR');if($name===''||!in_array($type,['Banka','Kasa'],true)||!in_array($currency,['EUR','USD','TRY'],true))throw new RuntimeException('Geçersiz Kasa/Banka bilgisi.');
   $data=['name'=>$name,'type'=>$type,'currency'=>$currency,'bankName'=>$type==='Banka'?trim((string)($in['bankName']??'')):'','branch'=>$type==='Banka'?trim((string)($in['branch']??'')):'','swift'=>$type==='Banka'?mb_strtoupper(trim((string)($in['swift']??''))):'','iban'=>trim((string)($in['iban']??''))];
   if($id){$a=&cash_account_by_id($s,$id);if(!$a)throw new RuntimeException('Kasa/Banka hesabı bulunamadı.');if(($a['currency']??$currency)!==$currency&&(abs((float)($a['balance']??0))>0.001||array_filter($s['cash'],fn($c)=>(int)($c['accountId']??0)===$id)))throw new RuntimeException('Hareket veya bakiye bulunan hesabın para birimi değiştirilemez.');$balance=(float)($a['balance']??0);$a=array_merge($a,$data);$a['id']=$id;$a['balance']=$balance;audit('cash_account_update','cash_account',(string)$id,['name'=>$name]);}
   else{$id=next_id($s['cashAccounts']);$opening=round((float)($in['openingBalance']??0),2);$a=['id'=>$id]+$data+['balance'=>$opening];$s['cashAccounts'][]=$a;if(abs($opening)>0.001){$cashId=next_id($s['cash']);$group=uuid4();$typeText=$opening>=0?'Giriş':'Çıkış';$s['cash'][]=['id'=>$cashId,'date'=>date('d.m.Y'),'accountId'=>$id,'account'=>$name,'currency'=>$currency,'request'=>'AÇILIŞ','party'=>'Açılış Bakiyesi','type'=>$typeText,'amountValue'=>abs($opening),'amount'=>abs($opening),'note'=>'Hesap açılış bakiyesi','archived'=>false,'kind'=>'cash_opening_balance','groupUuid'=>$group,'reversed'=>false];$q=$pdo->prepare('INSERT INTO finance_journal(group_uuid,action_type,party_name,cash_account_id,currency,amount,payload_json,created_by) VALUES(?,?,?,?,?,?,?,?)');$q->execute([$group,'cash_opening_balance','Açılış Bakiyesi',$id,$currency,abs($opening),json_encode(['cashId'=>$cashId,'delta'=>$opening],JSON_UNESCAPED_UNICODE),$u['id']]);$jid=(int)$pdo->lastInsertId();foreach($s['cash'] as &$c)if((int)($c['id']??0)===$cashId)$c['journalId']=$jid;}audit('cash_account_create','cash_account',(string)$id,['name'=>$name,'opening'=>$opening]);}
 }
 elseif($action==='cash_balance_adjustment'){
   $id=(int)($in['accountId']??0);$target=round((float)($in['targetBalance']??0),2);$reason=trim((string)($in['reason']??''));if($reason==='')throw new RuntimeException('Bakiye düzeltme açıklaması zorunludur.');$a=&cash_account_by_id($s,$id);if(!$a)throw new RuntimeException('Kasa/Banka hesabı bulunamadı.');$old=round((float)($a['balance']??0),2);$delta=round($target-$old,2);if(abs($delta)<0.001)throw new RuntimeException('Yeni bakiye mevcut bakiye ile aynı.');$a['balance']=$target;$cashId=next_id($s['cash']);$group=uuid4();$s['cash'][]=['id'=>$cashId,'date'=>date('d.m.Y'),'accountId'=>$id,'account'=>$a['name'],'currency'=>$a['currency'],'request'=>'DÜZELTME','party'=>'Bakiye Düzeltme','type'=>$delta>0?'Giriş':'Çıkış','amountValue'=>abs($delta),'amount'=>abs($delta),'note'=>$reason,'archived'=>false,'kind'=>'cash_balance_adjustment','groupUuid'=>$group,'reversed'=>false];$q=$pdo->prepare('INSERT INTO finance_journal(group_uuid,action_type,party_name,cash_account_id,currency,amount,payload_json,created_by) VALUES(?,?,?,?,?,?,?,?)');$q->execute([$group,'cash_balance_adjustment','Bakiye Düzeltme',$id,$a['currency'],abs($delta),json_encode(['cashId'=>$cashId,'oldBalance'=>$old,'targetBalance'=>$target,'delta'=>$delta,'reason'=>$reason],JSON_UNESCAPED_UNICODE),$u['id']]);$jid=(int)$pdo->lastInsertId();foreach($s['cash'] as &$c)if((int)($c['id']??0)===$cashId)$c['journalId']=$jid;audit('cash_balance_adjustment','cash_account',(string)$id,['old'=>$old,'new'=>$target,'delta'=>$delta,'reason'=>$reason]);
 }
 elseif($action==='cash_transfer'){
   $fromId=(int)($in['fromId']??0);$toId=(int)($in['toId']??0);$amount=round((float)($in['amount']??0),2);$rate=(float)($in['rate']??0);$note=trim((string)($in['note']??''))?:'Hesaplar arası virman';if($fromId===$toId||$amount<=0)throw new RuntimeException('Geçersiz virman.');$from=&cash_account_by_id($s,$fromId);$to=&cash_account_by_id($s,$toId);if(!$from||!$to)throw new RuntimeException('Kaynak veya hedef hesap bulunamadı.');if((float)$from['balance']<$amount-0.001)throw new RuntimeException('Kaynak hesap bakiyesi yetersiz.');$finalRate=($from['currency']??'')===($to['currency']??'')?1:$rate;if($finalRate<=0)throw new RuntimeException('Geçerli kur/parite gerekli.');$target=round($amount*$finalRate,2);$from['balance']=(float)$from['balance']-$amount;$to['balance']=(float)$to['balance']+$target;$group=uuid4();$outId=next_id($s['cash']);$inId=$outId+1;$fxText=($from['currency']===$to['currency'])?'Kur: 1':('Kur/Parite: 1 '.$from['currency'].' = '.number_format($finalRate,6,'.','').' '.$to['currency']);$s['cash'][]=['id'=>$outId,'date'=>date('d.m.Y'),'accountId'=>$fromId,'account'=>$from['name'],'currency'=>$from['currency'],'request'=>'VİRMAN','party'=>$to['name'],'type'=>'Çıkış','amountValue'=>$amount,'amount'=>$amount,'note'=>$note.' · '.$fxText,'archived'=>false,'kind'=>'cash_transfer','groupUuid'=>$group,'reversed'=>false];$s['cash'][]=['id'=>$inId,'date'=>date('d.m.Y'),'accountId'=>$toId,'account'=>$to['name'],'currency'=>$to['currency'],'request'=>'VİRMAN','party'=>$from['name'],'type'=>'Giriş','amountValue'=>$target,'amount'=>$target,'note'=>$note.' · '.$fxText,'archived'=>false,'kind'=>'cash_transfer','groupUuid'=>$group,'reversed'=>false];$q=$pdo->prepare('INSERT INTO finance_journal(group_uuid,action_type,party_name,cash_account_id,currency,amount,payload_json,created_by) VALUES(?,?,?,?,?,?,?,?)');$q->execute([$group,'cash_transfer',$to['name'],$fromId,$from['currency'],$amount,json_encode(['cashOutId'=>$outId,'cashInId'=>$inId,'fromId'=>$fromId,'toId'=>$toId,'targetAmount'=>$target,'targetCurrency'=>$to['currency'],'rate'=>$finalRate,'note'=>$note],JSON_UNESCAPED_UNICODE),$u['id']]);$jid=(int)$pdo->lastInsertId();foreach($s['cash'] as &$c)if(($c['groupUuid']??'')===$group)$c['journalId']=$jid;audit('cash_transfer','finance_journal',(string)$jid,['from'=>$fromId,'to'=>$toId,'amount'=>$amount,'target'=>$target,'rate'=>$finalRate]);
 }
 elseif($action==='comparison_expense_realize'){
   $rid=(int)($in['requestId']??0);$r=&find_request($s,$rid);if(!$r)throw new RuntimeException('Talep bulunamadı.');
   $order=&find_order_by_request($s,$rid);$won=(($r['status']??'')==='won')||($order!==null);if(!$won)throw new RuntimeException('Gerçek operasyon gideri yalnız kazanılmış veya siparişe dönüşmüş taleplerde kaydedilebilir.');
   $labels=['freight'=>'Navlun','customs'=>'Gümrükçü','unloading'=>'İndirme','inland'=>'İç Nakliye','other'=>'Diğer'];$category=(string)($in['category']??'');if(!isset($labels[$category]))throw new RuntimeException('Geçersiz gider türü.');
   $customCategory=trim((string)($in['customCategory']??''));if($category==='other'){if($customCategory==='')throw new RuntimeException('Diğer gider türü için açıklama zorunlu.');if(mb_strlen($customCategory)>120)throw new RuntimeException('Diğer gider türü çok uzun.');$labels['other']=$customCategory;}
   $amount=round((float)($in['amount']??0),2);if($amount<=0)throw new RuntimeException('Gider tutarı sıfırdan büyük olmalı.');
   $cur=strtoupper((string)($in['currency']??$r['currency']??'EUR'));if(!in_array($cur,['EUR','USD','TRY'],true))throw new RuntimeException('Geçersiz gerçek gider para birimi.');
   $reportCur=strtoupper((string)($r['currency']??$in['reportCurrency']??$cur));if(!in_array($reportCur,['EUR','USD','TRY'],true))$reportCur=$cur;
   $partyId=(int)($in['partyId']??0);$partyKey=trim((string)($in['partyKey']??''));$bankId=(int)($in['bankId']??0);$party=&operational_expense_account($s,$partyId,$partyKey,$cur);$bank=&cash_account_resolve($s,$bankId,trim((string)($in['bankName']??'')),strtoupper(trim((string)($in['bankCurrency']??''))));if(!$party||!$bank)throw new RuntimeException('Cari/hizmet sağlayıcı veya kasa/banka bulunamadı.');if((string)($party['type']??'')==='customer')throw new RuntimeException('Operasyon gideri için müşteri carisi kullanılamaz.');$partyId=(int)($party['id']??0);if(strtoupper((string)($party['currency']??''))!==$cur)throw new RuntimeException('Gider carisi gerçek gider para birimiyle eşleşmiyor.');
   $date=trim((string)($in['date']??date('Y-m-d')));if(!preg_match('/^\d{4}-\d{2}-\d{2}$/',$date))$date=date('Y-m-d');$ref=trim((string)($in['ref']??''))?:($r['no'].'-'.$category);$desc=trim((string)($in['description']??''))?:($labels[$category].' gerçek gideri');$invoiceNo=trim((string)($in['invoiceNo']??''));$invoiceDate=trim((string)($in['invoiceDate']??''));$dueDate=trim((string)($in['dueDate']??''));$attachmentId=(int)($in['attachmentId']??0);
   $fxSnapshot=finance_fx_snapshot((array)($in['fxSnapshot']??[]),$date);$usd=(float)($fxSnapshot['usdTry']??0);$eur=(float)($fxSnapshot['eurTry']??0);
   $bankCur=strtoupper((string)($bank['currency']??$cur));$fxMode=(string)($in['fxMode']??'tcmb');if(!in_array($fxMode,['tcmb','manual'],true))$fxMode='tcmb';$manualRate=(float)($in['manualRate']??0);$crossPayment=$bankCur!==$cur;$crossReport=$reportCur!==$cur;
   if(($crossPayment||$crossReport)&&(!($usd>0)||!($eur>0))&&!($fxMode==='manual'&&$manualRate>0&&$bankCur===$reportCur))throw new RuntimeException('Çapraz döviz / operasyon karşılığı için geçerli TCMB kuru gerekli.');
   $tcmbPaymentRate=$crossPayment?fx_convert(1,$cur,$bankCur,$fxSnapshot):1;$usedPaymentRate=$crossPayment?(($fxMode==='manual'&&$manualRate>0)?$manualRate:$tcmbPaymentRate):1;if(!($usedPaymentRate>0))throw new RuntimeException('Geçerli ödeme kuru bulunamadı.');$bankAmount=round($amount*$usedPaymentRate,2);
   if($reportCur===$cur)$reportAmount=$amount;elseif($fxMode==='manual'&&$manualRate>0&&$reportCur===$bankCur)$reportAmount=$bankAmount;else $reportAmount=round(fx_convert($amount,$cur,$reportCur,$fxSnapshot),2);if(!($reportAmount>0))throw new RuntimeException('Operasyon raporlama karşılığı hesaplanamadı.');
   if((float)($bank['balance']??0)<$bankAmount-0.001)throw new RuntimeException('Kasa/Banka bakiyesi yetersiz. Gerekli: '.number_format($bankAmount,2,',','.').' '.$bankCur);$bank['balance']=(float)$bank['balance']-$bankAmount;
   add_entry($party,$ref,$desc.' / Gider tahakkuku',0,$amount,$date,$fxSnapshot);add_entry($party,$ref,$desc.' / Ödeme',$amount,0,$date,$fxSnapshot);$cashId=next_id($s['cash']);$group=uuid4();
   $s['cash'][]=['id'=>$cashId,'date'=>date('d.m.Y',strtotime($date)),'accountId'=>$bankId,'account'=>$bank['name'],'currency'=>$bankCur,'request'=>$r['no'],'party'=>$party['name'],'partyId'=>$partyId,'type'=>'Çıkış','amountValue'=>$bankAmount,'expenseAmount'=>$amount,'expenseCurrency'=>$cur,'reportAmount'=>$reportAmount,'reportCurrency'=>$reportCur,'fxRate'=>$usedPaymentRate,'fxRateSource'=>$fxMode==='manual'?'manual':'tcmb','amount'=>$bankAmount,'note'=>$desc,'archived'=>false,'kind'=>'operational_expense_payment','orderId'=>$order['id']??null,'groupUuid'=>$group,'reversed'=>false];
   $payload=['cashId'=>$cashId,'partyId'=>$partyId,'category'=>$category,'customCategory'=>$customCategory,'request'=>$r['no'],'description'=>$desc,'date'=>$date,'bankAmount'=>$bankAmount,'bankCurrency'=>$bankCur,'expenseAmount'=>$amount,'expenseCurrency'=>$cur,'reportAmount'=>$reportAmount,'reportCurrency'=>$reportCur,'usedPaymentRate'=>$usedPaymentRate,'tcmbPaymentRate'=>$tcmbPaymentRate,'fxRateSource'=>$fxMode==='manual'?'manual':'tcmb','manualRate'=>$manualRate,'fxSnapshot'=>$fxSnapshot,'invoiceNo'=>$invoiceNo,'invoiceDate'=>$invoiceDate,'dueDate'=>$dueDate,'attachmentId'=>$attachmentId];$q=$pdo->prepare('INSERT INTO finance_journal(group_uuid,action_type,request_id,order_id,party_name,cash_account_id,currency,amount,payload_json,created_by) VALUES(?,?,?,?,?,?,?,?,?,?)');$q->execute([$group,'operational_expense_payment',$rid,$order['id']??null,$party['name'],$bankId,$cur,$amount,json_encode($payload,JSON_UNESCAPED_UNICODE),$u['id']]);$jid=(int)$pdo->lastInsertId();foreach($s['cash'] as &$cc)if((int)($cc['id']??0)===$cashId)$cc['journalId']=$jid;unset($cc);
   $plannedCostLineId=trim((string)($in['plannedCostLineId']??''));$r['realizedComparisonExpenses']=$r['realizedComparisonExpenses']??[];$r['realizedComparisonExpenses'][]=['id'=>next_id($r['realizedComparisonExpenses']),'category'=>$category,'customCategory'=>$customCategory,'label'=>$labels[$category],'amount'=>$amount,'currency'=>$cur,'paymentAmount'=>$bankAmount,'paymentCurrency'=>$bankCur,'reportAmount'=>$reportAmount,'reportCurrency'=>$reportCur,'usedPaymentRate'=>$usedPaymentRate,'tcmbPaymentRate'=>$tcmbPaymentRate,'fxRateSource'=>$fxMode==='manual'?'manual':'tcmb','partyId'=>$partyId,'partyName'=>$party['name'],'bankId'=>$bankId,'bankName'=>$bank['name'],'cashId'=>$cashId,'journalId'=>$jid,'plannedCostLineId'=>$plannedCostLineId,'date'=>$date,'ref'=>$ref,'description'=>$desc,'invoiceNo'=>$invoiceNo,'invoiceDate'=>$invoiceDate,'dueDate'=>$dueDate,'attachmentId'=>$attachmentId,'fxSnapshot'=>$fxSnapshot,'createdAt'=>date('c'),'createdBy'=>$u['name']];if($plannedCostLineId!=='')foreach(($r['costLines']??[]) as &$cl)if((string)($cl['id']??'')===$plannedCostLineId){$cl['realizedJournalId']=$jid;$cl['realizedAt']=date('c');break;}unset($cl);
   audit('operational_expense_payment','request',(string)$rid,['category'=>$category,'expense_amount'=>$amount,'expense_currency'=>$cur,'payment_amount'=>$bankAmount,'payment_currency'=>$bankCur,'report_amount'=>$reportAmount,'report_currency'=>$reportCur,'fx_source'=>$fxMode,'party_id'=>$partyId,'bank_id'=>$bankId,'journal_id'=>$jid]);
 }
 elseif($action==='cash_movement'){
   $bankId=(int)($in['bankId']??0);$partyId=(int)($in['partyId']??0);$mode=(string)($in['mode']??'');$amount=round((float)($in['amount']??0),2);$poId=(int)($in['poId']??0);$orderId=(int)($in['orderId']??0);$batchId=(int)($in['batchId']??0);$poNo=trim((string)($in['poNo']??''));
   if(!in_array($mode,['receipt','payment'],true)||$amount<=0)throw new RuntimeException('Geçersiz finans hareketi.');
   $bank=&cash_account_resolve($s,$bankId,trim((string)($in['bankName']??'')),strtoupper(trim((string)($in['bankCurrency']??''))));$party=&account_by_id($s,$partyId);if(!$bank||!$party)throw new RuntimeException('Kasa/Banka veya cari bulunamadı.');$balRepair=reconcile_cash_balance_from_ledger($s,$bank);if($balRepair&&abs((float)$balRepair['delta'])>0.001)audit('cash_balance_auto_reconcile','cash_account',(string)($bank['id']??0),$balRepair);
   if(($bank['currency']??'')!==($party['currency']??''))throw new RuntimeException('Kasa/Banka ve cari para birimi aynı olmalı.');
   $cur=$party['currency']??'EUR';$date=trim((string)($in['date']??date('Y-m-d')));if(!preg_match('/^\d{4}-\d{2}-\d{2}$/',$date))$date=date('Y-m-d');$requestNo=trim((string)($in['request']??($party['request']??'')));$ref=trim((string)($in['ref']??($requestNo?:'FİNANS')));$desc=trim((string)($in['description']??($mode==='receipt'?'Müşteri Tahsilatı':'Tedarikçiye Ödeme')));
   $linkedOrder=null;foreach($s['orders'] as &$oo){if(!is_valid_operational_order_state($s,$oo))continue;$rr=&find_request($s,(int)($oo['requestId']??0));if(($oo['request']??'')===$requestNo||($rr&&($rr['no']??'')===$requestNo)){$linkedOrder=&$oo;break;}}
   if($mode==='receipt'){
     if(($party['type']??'customer')==='supplier')throw new RuntimeException('Tedarikçi carisi için ödeme işlemi seçilmeli.');
     
     $due=max(0,(float)($party['requestOpen']??0));if($due>0&&$amount>$due+0.001)throw new RuntimeException('Tahsilat açık alacaktan büyük olamaz.');
     $bank['balance']=(float)$bank['balance']+$amount;$party['requestOpen']=(float)($party['requestOpen']??0)-$amount;add_entry($party,$ref,$desc,0,$amount,$date);if($linkedOrder){$linkedOrder['customerPaid']=(float)($linkedOrder['customerPaid']??0)+$amount;$linkedOrder['customerDue']=max(0,(float)($linkedOrder['customerDue']??0)-$amount);allocate_schedule_payment($linkedOrder,$amount);}
     $kind='generic_customer_receipt';$type='Giriş';
   }else{
     if(($party['type']??'')!=='supplier')throw new RuntimeException('Müşteri/kurum carisi için tahsilat işlemi seçilmeli.');
     if((float)$bank['balance']<$amount-0.001)throw new RuntimeException('Kasa/Banka bakiyesi yetersiz. Hesap: '.($bank['name']??'Hesap').' · Sunucu bakiyesi: '.number_format((float)$bank['balance'],2,',','.').' '.$cur.' · Ödeme: '.number_format($amount,2,',','.').' '.$cur);
     $due=max(0,-(float)($party['requestOpen']??0));if($due>0&&$amount>$due+0.001)throw new RuntimeException('Ödeme açık borçtan büyük olamaz.');
     if($poId||$poNo!==''){$owner=null;$po=&find_po_context($s,$poId,$orderId,$poNo,$owner);if(!$po||!$owner)throw new RuntimeException('PO bulunamadı. [PO ID: '.$poId.' · Sipariş ID: '.$orderId.' · PO No: '.($poNo?:'—').']');if(!is_valid_operational_order_state($s,$owner))throw new RuntimeException('Kapalı, iptal, kayıp veya geçersiz sipariş PO’suna ödeme yapılamaz.');if(mb_strtoupper(trim((string)$po['supplier']))!==mb_strtoupper(trim((string)$party['name'])))throw new RuntimeException('Seçilen PO bu tedarikçiye ait değil.');if(($party['currency']??'')!==($po['currency']??($party['currency']??'')))throw new RuntimeException('Tedarikçi cari para birimi ile PO para birimi eşleşmiyor.');if($amount>(float)($po['due']??0)+0.001)throw new RuntimeException('Ödeme PO kalan borcundan büyük olamaz.');$actualPoId=(int)($po['id']??$poId);$actualPoNo=(string)($po['no']??$poNo);$po['paid']=(float)($po['paid']??0)+$amount;$po['due']=max(0,(float)$po['due']-$amount);$poId=$actualPoId;$poNo=$actualPoNo;$linkedOrder=&find_order_by_id($s,(int)($owner['id']??0));}
     else{}
     $bank['balance']=(float)$bank['balance']-$amount;$party['requestOpen']=min(0,(float)($party['requestOpen']??0)+$amount);add_entry($party,$ref,$desc,$amount,0,$date);$kind='generic_supplier_payment';$type='Çıkış';
   }
   $cashId=next_id($s['cash']);$group=uuid4();$s['cash'][]=['id'=>$cashId,'date'=>date('d.m.Y',strtotime($date)),'accountId'=>$bankId,'account'=>$bank['name'],'currency'=>$cur,'request'=>$requestNo,'party'=>$party['name'],'partyId'=>$partyId,'type'=>$type,'amountValue'=>$amount,'amount'=>$amount,'note'=>$desc,'archived'=>false,'kind'=>$kind,'poId'=>$poId?:null,'poNo'=>$poNo?:null,'orderId'=>$linkedOrder['id']??($orderId?:null),'batchId'=>$batchId?:null,'groupUuid'=>$group,'reversed'=>false];
   $q=$pdo->prepare('INSERT INTO finance_journal(group_uuid,action_type,request_id,order_id,party_name,cash_account_id,currency,amount,payload_json,created_by) VALUES(?,?,?,?,?,?,?,?,?,?)');$rid=null;if($linkedOrder)$rid=$linkedOrder['requestId']??null;$q->execute([$group,$kind,$rid,$linkedOrder['id']??null,$party['name'],$bankId,$cur,$amount,json_encode(['cashId'=>$cashId,'partyId'=>$partyId,'poId'=>$poId,'poNo'=>$poNo,'orderId'=>$linkedOrder['id']??($orderId?:null),'batchId'=>$batchId?:null,'request'=>$requestNo],JSON_UNESCAPED_UNICODE),$u['id']]);$jid=(int)$pdo->lastInsertId();foreach($s['cash'] as &$c)if((int)$c['id']===$cashId)$c['journalId']=$jid;audit($kind,'finance_journal',(string)$jid,['amount'=>$amount,'party_id'=>$partyId,'po_id'=>$poId]);
 }
 elseif($action==='delivery_batch_create'){
   $oid=(int)($in['orderId']??0);$o=&find_order_by_id($s,$oid);if(!$o)throw new RuntimeException('Sipariş bulunamadı.');if(($o['open']??true)===false||!empty($o['financialCancelled']))throw new RuntimeException('Kapalı/iptal siparişte teslimat partisi oluşturulamaz.');
   $r=&find_request($s,(int)$o['requestId']);if(!$r||empty($r['customerQuote']['items']))throw new RuntimeException('Sipariş kalemleri bulunamadı.');$inputItems=$in['items']??[];if(!is_array($inputItems)||!count($inputItems))throw new RuntimeException('Parti kalemi seçilmedi.');
   $qmap=[];foreach($r['customerQuote']['items'] as $it)$qmap[(int)$it['itemId']]=$it;$poBySupplier=[];foreach(($o['pos']??[]) as $po){foreach(($po['items']??[]) as $pit)$poBySupplier[(int)($pit['supplierId']??0)]=$po;}
   $batchItems=[];$supplierBase=[];$customerAmount=0.0;
   foreach($inputItems as $ii){$iid=(int)($ii['itemId']??0);$qty=round((float)($ii['qty']??0),4);if($qty<=0)continue;if(!isset($qmap[$iid]))throw new RuntimeException('Geçersiz sipariş kalemi.');$qi=$qmap[$iid];$ordered=(float)($qi['qty']??0);$allocated=batch_allocated($o,$iid,false);if($qty>$ordered-$allocated+0.0001)throw new RuntimeException(($qi['name']??'Kalem').' için parti adedi kalan sipariş miktarını aşıyor.');
     $sid=(int)($qi['supplierId']??0);$po=$poBySupplier[$sid]??null;if(!$po)throw new RuntimeException(($qi['name']??'Kalem').' için bağlı tedarikçi PO bulunamadı.');$sale=(float)($qi['sale']??0);$cost=(float)($qi['cost']??0);$saleTotal=round($sale*$qty,2);$costBase=round($cost*$qty,2);$customerAmount+=$saleTotal;$supplierBase[(int)$po['id']]=($supplierBase[(int)$po['id']]??0)+$costBase;
     $batchItems[]=['itemId'=>$iid,'name'=>$qi['name']??'Kalem','qty'=>$qty,'unit'=>$qi['unit']??'','saleUnit'=>$sale,'saleTotal'=>$saleTotal,'costUnit'=>$cost,'costBase'=>$costBase,'supplierId'=>$sid,'supplier'=>$qi['supplier']??$po['supplier'],'poId'=>(int)$po['id']];
   }
   if(!count($batchItems))throw new RuntimeException('Parti adedi girilmedi.');
   $supplierPayments=[];foreach($supplierBase as $poId=>$base){$po=null;foreach($o['pos'] as $pp)if((int)$pp['id']===$poId){$po=$pp;break;}if(!$po)continue;$fullBase=po_base_total($po);$freight=max(0,(float)$po['total']-$fullBase);$alreadyFreight=batch_po_allocated_freight($o,$poId);$allDeliveredAfter=true;foreach(($po['items']??[]) as $pit){$iid=(int)$pit['itemId'];$ordered=(float)$pit['qty'];$inThis=0;foreach($batchItems as $bi)if((int)$bi['itemId']===$iid)$inThis+=(float)$bi['qty'];if(batch_allocated($o,$iid,false)+$inThis<$ordered-0.0001){$allDeliveredAfter=false;break;}}$share=$fullBase>0?round($freight*($base/$fullBase),2):0;if($allDeliveredAfter)$share=max(0,round($freight-$alreadyFreight,2));$total=round($base+$share,2);$supplierPayments[]=['poId'=>$poId,'poNo'=>$po['no']??'','supplier'=>$po['supplier']??'Tedarikçi','currency'=>$po['currency']??($o['currency']??'EUR'),'baseAmount'=>round($base,2),'freightShare'=>$share,'total'=>$total,'paid'=>0,'due'=>$total];}
   $bid=batch_next_id($s['orders']);$seq=1;foreach(($o['deliveryBatches']??[]) as $bb)$seq=max($seq,(int)($bb['sequence']??0)+1);$bno=($o['no']??'ORD').'-P'.str_pad((string)$seq,3,'0',STR_PAD_LEFT);
   $o['deliveryBatches']=$o['deliveryBatches']??[];$o['deliveryBatches'][]=['id'=>$bid,'sequence'=>$seq,'batchNo'=>$bno,'status'=>'Planlandı','plannedDate'=>$in['plannedDate']??null,'deliveredDate'=>null,'description'=>trim((string)($in['description']??'')),'shipmentRef'=>trim((string)($in['shipmentRef']??'')),'carrier'=>trim((string)($in['carrier']??'')),'vehicleContainer'=>trim((string)($in['vehicleContainer']??'')),'receiver'=>trim((string)($in['receiver']??'')),'items'=>$batchItems,'customerAmount'=>round($customerAmount,2),'customerCollected'=>0,'customerDue'=>round($customerAmount,2),'supplierPayments'=>$supplierPayments,'createdAt'=>date('c'),'createdBy'=>$u['name']];normalize_batch_freight($o);
   audit('delivery_batch_create','order',(string)$oid,['batch_id'=>$bid,'batch_no'=>$bno,'customer_amount'=>$customerAmount]);
 }

 elseif($action==='delivery_batch_update'){
   $oid=(int)($in['orderId']??0);$bid=(int)($in['batchId']??0);$o=&find_order_by_id($s,$oid);if(!$o)throw new RuntimeException('Sipariş bulunamadı.');$b=&find_batch($o,$bid);if(!$b)throw new RuntimeException('Parti bulunamadı.');if(($b['status']??'')!=='Planlandı')throw new RuntimeException('Yalnız Planlandı durumundaki parti düzenlenebilir.');
   foreach(($s['cash']??[]) as $c)if((int)($c['batchId']??0)===$bid && empty($c['reversed']))throw new RuntimeException('Finans hareketi bulunan parti düzenlenemez. Önce ters kayıt oluşturun.');
   $r=&find_request($s,(int)$o['requestId']);if(!$r||empty($r['customerQuote']['items']))throw new RuntimeException('Sipariş kalemleri bulunamadı.');$qmap=[];foreach($r['customerQuote']['items'] as $it)$qmap[(int)$it['itemId']]=$it;$poBySupplier=[];foreach(($o['pos']??[]) as $po)foreach(($po['items']??[]) as $pit)$poBySupplier[(int)($pit['supplierId']??0)]=$po;
   $batchItems=[];$supplierBase=[];$customerAmount=0.0;
   foreach(($in['items']??[]) as $ii){$iid=(int)($ii['itemId']??0);$qty=round((float)($ii['qty']??0),4);if($qty<=0)continue;if(!isset($qmap[$iid]))throw new RuntimeException('Geçersiz sipariş kalemi.');$qi=$qmap[$iid];$ordered=(float)($qi['qty']??0);$otherAllocated=0;foreach(($o['deliveryBatches']??[]) as $bb){if((int)($bb['id']??0)===$bid||($bb['status']??'')==='İptal Edildi')continue;foreach(($bb['items']??[]) as $it)if((int)($it['itemId']??0)===$iid)$otherAllocated+=(float)($it['qty']??0);}if($qty>$ordered-$otherAllocated+0.0001)throw new RuntimeException(($qi['name']??'Kalem').' için parti adedi kalan sipariş miktarını aşıyor.');
     $sid=(int)($qi['supplierId']??0);$po=$poBySupplier[$sid]??null;if(!$po)throw new RuntimeException('Bağlı PO bulunamadı.');$sale=(float)($qi['sale']??0);$cost=(float)($qi['cost']??0);$saleTotal=round($sale*$qty,2);$costBase=round($cost*$qty,2);$customerAmount+=$saleTotal;$supplierBase[(int)$po['id']]=($supplierBase[(int)$po['id']]??0)+$costBase;$batchItems[]=['itemId'=>$iid,'name'=>$qi['name']??'Kalem','qty'=>$qty,'unit'=>$qi['unit']??'','saleUnit'=>$sale,'saleTotal'=>$saleTotal,'costUnit'=>$cost,'costBase'=>$costBase,'supplierId'=>$sid,'supplier'=>$qi['supplier']??$po['supplier'],'poId'=>(int)$po['id']];
   }
   if(!count($batchItems))throw new RuntimeException('Parti adedi girilmedi.');$supplierPayments=[];foreach($supplierBase as $poId=>$base){$po=null;foreach($o['pos'] as $pp)if((int)$pp['id']===$poId){$po=$pp;break;}if(!$po)continue;$fullBase=po_base_total($po);$freight=max(0,(float)$po['total']-$fullBase);$otherFreight=0;foreach(($o['deliveryBatches']??[]) as $bb){if((int)$bb['id']===$bid||($bb['status']??'')==='İptal Edildi')continue;foreach(($bb['supplierPayments']??[]) as $sp)if((int)$sp['poId']===$poId)$otherFreight+=(float)($sp['freightShare']??0);}$share=$fullBase>0?round($freight*($base/$fullBase),2):0;$supplierPayments[]=['poId'=>$poId,'poNo'=>$po['no']??'','supplier'=>$po['supplier']??'Tedarikçi','currency'=>$po['currency']??($o['currency']??'EUR'),'baseAmount'=>round($base,2),'freightShare'=>$share,'total'=>round($base+$share,2),'paid'=>0,'due'=>round($base+$share,2)];}
   $b['plannedDate']=$in['plannedDate']??$b['plannedDate'];$b['description']=trim((string)($in['description']??''));$b['shipmentRef']=trim((string)($in['shipmentRef']??($b['shipmentRef']??'')));$b['carrier']=trim((string)($in['carrier']??($b['carrier']??'')));$b['vehicleContainer']=trim((string)($in['vehicleContainer']??($b['vehicleContainer']??'')));$b['receiver']=trim((string)($in['receiver']??($b['receiver']??'')));$b['items']=$batchItems;$b['customerAmount']=round($customerAmount,2);$b['customerCollected']=0;$b['customerDue']=round($customerAmount,2);$b['supplierPayments']=$supplierPayments;normalize_batch_freight($o);$b['updatedAt']=date('c');$b['updatedBy']=$u['name'];audit('delivery_batch_update','order',(string)$oid,['batch_id'=>$bid,'batch_no'=>$b['batchNo']??'']);
 }
 elseif($action==='delivery_batch_delete'){
   $oid=(int)($in['orderId']??0);$bid=(int)($in['batchId']??0);$o=&find_order_by_id($s,$oid);if(!$o)throw new RuntimeException('Sipariş bulunamadı.');$b=&find_batch($o,$bid);if(!$b)throw new RuntimeException('Parti bulunamadı.');if(($b['status']??'')!=='Planlandı')throw new RuntimeException('Yalnız Planlandı durumundaki parti silinebilir.');foreach(($s['cash']??[]) as $c)if((int)($c['batchId']??0)===$bid && empty($c['reversed']))throw new RuntimeException('Finans hareketi bulunan parti silinemez. Önce ters kayıt oluşturun.');
   $before=count($o['deliveryBatches']);$o['deliveryBatches']=array_values(array_filter($o['deliveryBatches'],fn($x)=>(int)($x['id']??0)!==$bid));if(count($o['deliveryBatches'])===$before)throw new RuntimeException('Parti silinemedi.');normalize_batch_freight($o);audit('delivery_batch_delete','order',(string)$oid,['batch_id'=>$bid,'batch_no'=>$b['batchNo']??'']);
 }
 elseif($action==='delivery_batch_deliver'){
   $oid=(int)($in['orderId']??0);$bid=(int)($in['batchId']??0);$o=&find_order_by_id($s,$oid);if(!$o)throw new RuntimeException('Sipariş bulunamadı.');$b=&find_batch($o,$bid);if(!$b)throw new RuntimeException('Teslimat partisi bulunamadı.');if(($b['status']??'')==='Teslim Edildi')throw new RuntimeException('Bu parti zaten teslim edildi.');if(($b['status']??'')==='İptal Edildi')throw new RuntimeException('İptal edilmiş parti teslim edilemez.');$snap=[];foreach(($o['pos']??[]) as $po)$snap[(string)($po['id']??0)]=['status'=>$po['status']??'Hazırlanıyor','production'=>(float)($po['production']??0)];$b['deliveryUndoSnapshot']=$snap;$b['status']='Teslim Edildi';$b['deliveredDate']=$in['deliveredDate']??date('Y-m-d');$b['deliveredAt']=date('c');$b['deliveredBy']=$u['name']??null;
   recompute_delivery_po_status($o);
   audit('delivery_batch_deliver','order',(string)$oid,['batch_id'=>$bid,'batch_no'=>$b['batchNo']??'']);
 }
 elseif($action==='delivery_batch_undo'){
   $oid=(int)($in['orderId']??0);$bid=(int)($in['batchId']??0);$o=&find_order_by_id($s,$oid);if(!$o)throw new RuntimeException('Sipariş bulunamadı.');$b=&find_batch($o,$bid);if(!$b)throw new RuntimeException('Teslimat partisi bulunamadı.');if(($b['status']??'')!=='Teslim Edildi')throw new RuntimeException('Yalnız teslim edilmiş parti geri alınabilir.');
   foreach(($s['cash']??[]) as $c){if((int)($c['orderId']??0)!==$oid||(int)($c['batchId']??0)!==$bid||!empty($c['reversed']))continue;if(in_array((string)($c['kind']??''),['delivery_batch_collection','delivery_batch_supplier_payment'],true))throw new RuntimeException('Bu partiye bağlı eski tip teslimat finans hareketi var. Önce ilgili finans hareketini Sil ile kaldırın.');}
   $fallback=is_array($b['deliveryUndoSnapshot']??null)?$b['deliveryUndoSnapshot']:[];$previousDate=$b['deliveredDate']??null;$b['status']='Planlandı';$b['deliveredDate']=null;unset($b['deliveredAt'],$b['deliveredBy']);$b['deliveryUndoneAt']=date('c');$b['deliveryUndoneBy']=$u['name']??null;$b['lastDeliveredDate']=$previousDate;recompute_delivery_po_status($o,$fallback);
   audit('delivery_batch_undo','order',(string)$oid,['batch_id'=>$bid,'batch_no'=>$b['batchNo']??'','previous_delivered_date'=>$previousDate]);
 }
 elseif($action==='delivery_batch_cancel'){
   $oid=(int)($in['orderId']??0);$bid=(int)($in['batchId']??0);$o=&find_order_by_id($s,$oid);if(!$o)throw new RuntimeException('Sipariş bulunamadı.');$b=&find_batch($o,$bid);if(!$b)throw new RuntimeException('Parti bulunamadı.');if(($b['status']??'')!=='Planlandı')throw new RuntimeException('Yalnız planlanan ve henüz teslim edilmemiş parti iptal edilebilir.');if((float)($b['customerCollected']??0)>0)throw new RuntimeException('Tahsilat bulunan parti iptal edilemez. Önce ters kayıt oluşturun.');foreach(($b['supplierPayments']??[]) as $sp)if((float)($sp['paid']??0)>0)throw new RuntimeException('Tedarikçi ödemesi bulunan parti iptal edilemez. Önce ters kayıt oluşturun.');$b['status']='İptal Edildi';$b['cancelledAt']=date('c');normalize_batch_freight($o);audit('delivery_batch_cancel','order',(string)$oid,['batch_id'=>$bid]);
 }
 elseif($action==='delivery_batch_collect'){
   throw new RuntimeException('Legacy parti tahsilat akışı kapatıldı. Sipariş ana kartındaki Müşteriden Tahsil Et işlemini kullanın.');
   $oid=(int)($in['orderId']??0);$bid=(int)($in['batchId']??0);$amount=round((float)($in['amount']??0),2);if($amount<=0)throw new RuntimeException('Tahsilat tutarı sıfırdan büyük olmalı.');$o=&find_order_by_id($s,$oid);if(!$o)throw new RuntimeException('Sipariş bulunamadı.');$b=&find_batch($o,$bid);if(!$b||($b['status']??'')!=='Teslim Edildi')throw new RuntimeException('Tahsilat yalnız teslim edilmiş parti için yapılabilir.');$r=&find_request($s,(int)$o['requestId']);$max=min((float)($b['customerDue']??0),(float)($o['customerDue']??0));if($amount>$max+0.001)throw new RuntimeException('Tahsilat parti veya sipariş kalan alacağını aşıyor.');$bankId=(int)($in['bankId']??0);$bank=&cash_account_by_id($s,$bankId);if(!$bank)throw new RuntimeException('Kasa/Banka bulunamadı.');$cur=$o['currency']??'EUR';$fx=require_payment_fx($cur,(string)($bank['currency']??$cur),$in['fxRate']??0,$in['fxRateConfirmed']??false);$bankAmount=($bank['currency']??$cur)===$cur?$amount:round($amount*$fx,2);$bank['balance']=(float)$bank['balance']+$bankAmount;$o['customerPaid']=(float)($o['customerPaid']??0)+$amount;$o['customerDue']=max(0,(float)($o['customerDue']??0)-$amount);allocate_schedule_payment($o,$amount);$b['customerCollected']=(float)($b['customerCollected']??0)+$amount;$b['customerDue']=max(0,(float)($b['customerDue']??0)-$amount);$a=&customer_account($s,$r);$a['requestOpen']=(float)($a['requestOpen']??0)-$amount;$desc=trim((string)($in['description']??(($b['batchNo']??'Parti').' müşteri tahsilatı')));add_entry($a,$r['no'],$desc,0,$amount);$cashId=next_id($s['cash']);$group=uuid4();$s['cash'][]=['id'=>$cashId,'date'=>date('d.m.Y'),'accountId'=>$bankId,'account'=>$bank['name'],'currency'=>$cur,'request'=>$r['no'],'party'=>$r['customer'],'type'=>'Giriş','amountValue'=>$bankAmount,'orderAmount'=>$amount,'fxRate'=>$fx,'amount'=>$bankAmount,'note'=>$desc,'archived'=>false,'kind'=>'delivery_batch_collection','orderId'=>$oid,'batchId'=>$bid,'groupUuid'=>$group,'reversed'=>false];$j=$pdo->prepare('INSERT INTO finance_journal(group_uuid,action_type,request_id,order_id,party_name,cash_account_id,currency,amount,payload_json,created_by) VALUES(?,?,?,?,?,?,?,?,?,?)');$j->execute([$group,'delivery_batch_collection',$r['id'],$oid,$r['customer'],$bankId,$cur,$amount,json_encode(['cashId'=>$cashId,'batchId'=>$bid,'bankAmount'=>$bankAmount,'bankCurrency'=>$bank['currency'],'fxRate'=>$fx],JSON_UNESCAPED_UNICODE),$u['id']]);$jid=(int)$pdo->lastInsertId();foreach($s['cash'] as &$c)if((int)$c['id']===$cashId)$c['journalId']=$jid;audit('delivery_batch_collection','order',(string)$oid,['batch_id'=>$bid,'amount'=>$amount,'journal_id'=>$jid]);
 }
 elseif($action==='delivery_batch_supplier_pay'){
   throw new RuntimeException('Legacy parti tedarikçi ödeme akışı kapatıldı. Sipariş ana kartındaki Tedarikçiye Ödeme Yap işlemini kullanın.');
   $oid=(int)($in['orderId']??0);$bid=(int)($in['batchId']??0);$poId=(int)($in['poId']??0);$amount=round((float)($in['amount']??0),2);if($amount<=0)throw new RuntimeException('Ödeme tutarı sıfırdan büyük olmalı.');$o=&find_order_by_id($s,$oid);if(!$o)throw new RuntimeException('Sipariş bulunamadı.');$b=&find_batch($o,$bid);if(!$b||($b['status']??'')!=='Teslim Edildi')throw new RuntimeException('Tedarikçi ödemesi yalnız teslim edilmiş parti için yapılabilir.');$sp=null;foreach($b['supplierPayments'] as &$xx)if((int)$xx['poId']===$poId){$sp=&$xx;break;}if(!$sp)throw new RuntimeException('Parti tedarikçi payı bulunamadı.');$po=null;foreach($o['pos'] as &$pp)if((int)$pp['id']===$poId){$po=&$pp;break;}if(!$po)throw new RuntimeException('PO bulunamadı.');$max=min((float)$sp['due'],(float)$po['due']);if($amount>$max+0.001)throw new RuntimeException('Ödeme parti veya PO kalan borcunu aşıyor.');$bankId=(int)($in['bankId']??0);$bank=&cash_account_by_id($s,$bankId);if(!$bank)throw new RuntimeException('Kasa/Banka bulunamadı.');$cur=$po['currency']??($sp['currency']??($o['currency']??'EUR'));$fx=require_payment_fx($cur,(string)($bank['currency']??$cur),$in['fxRate']??0,$in['fxRateConfirmed']??false);$bankAmount=($bank['currency']??$cur)===$cur?$amount:round($amount*$fx,2);if((float)$bank['balance']<$bankAmount-0.001)throw new RuntimeException('Kasa/Banka bakiyesi yetersiz.');$bank['balance']=(float)$bank['balance']-$bankAmount;$po['paid']=(float)($po['paid']??0)+$amount;$po['due']=max(0,(float)$po['due']-$amount);$sp['paid']=(float)($sp['paid']??0)+$amount;$sp['due']=max(0,(float)$sp['due']-$amount);$r=&find_request($s,(int)$o['requestId']);$a=&supplier_account($s,(string)$sp['supplier'],$cur,$r['no']);$a['requestOpen']=min(0,(float)($a['requestOpen']??0)+$amount);$desc=trim((string)($in['description']??(($b['batchNo']??'Parti').' tedarikçi ödemesi')));add_entry($a,$r['no'],$desc,$amount,0);$cashId=next_id($s['cash']);$group=uuid4();$s['cash'][]=['id'=>$cashId,'date'=>date('d.m.Y'),'accountId'=>$bankId,'account'=>$bank['name'],'currency'=>$cur,'request'=>$r['no'],'party'=>$sp['supplier'],'type'=>'Çıkış','amountValue'=>$bankAmount,'orderAmount'=>$amount,'fxRate'=>$fx,'amount'=>$bankAmount,'note'=>$desc,'archived'=>false,'kind'=>'delivery_batch_supplier_payment','orderId'=>$oid,'batchId'=>$bid,'poId'=>$poId,'groupUuid'=>$group,'reversed'=>false];$j=$pdo->prepare('INSERT INTO finance_journal(group_uuid,action_type,request_id,order_id,party_name,cash_account_id,currency,amount,payload_json,created_by) VALUES(?,?,?,?,?,?,?,?,?,?)');$j->execute([$group,'delivery_batch_supplier_payment',$r['id'],$oid,$sp['supplier'],$bankId,$cur,$amount,json_encode(['cashId'=>$cashId,'batchId'=>$bid,'poId'=>$poId,'bankAmount'=>$bankAmount,'bankCurrency'=>$bank['currency'],'fxRate'=>$fx],JSON_UNESCAPED_UNICODE),$u['id']]);$jid=(int)$pdo->lastInsertId();foreach($s['cash'] as &$c)if((int)$c['id']===$cashId)$c['journalId']=$jid;audit('delivery_batch_supplier_payment','order',(string)$oid,['batch_id'=>$bid,'po_id'=>$poId,'amount'=>$amount,'journal_id'=>$jid]);
 }
 elseif($action==='prepayment'){
   $oid=(int)$in['orderId'];$amount=round((float)$in['amount'],2);$bankId=(int)$in['bankId'];$batchId=(int)($in['batchId']??0);if($amount<=0)throw new RuntimeException('Tahsilat tutarı sıfırdan büyük olmalı.');$o=null;foreach($s['orders'] as &$oo)if((int)$oo['id']===$oid){$o=&$oo;break;}if(!$o)throw new RuntimeException('Sipariş bulunamadı.');if(($o['open']??true)===false||!empty($o['financialCancelled']))throw new RuntimeException('Kapalı/iptal siparişte tahsilat yapılamaz.');$r=&find_request($s,(int)$o['requestId']);if(!$r)throw new RuntimeException('Talep bulunamadı.');$q=$r['customerQuote']??null;if(!$q)throw new RuntimeException('Müşteri teklifi yok.');$a=&customer_account($s,$r);
   if(empty($o['receivableOpened'])){$a['requestOpen']=(float)($a['requestOpen']??0)+(float)$q['total'];add_entry($a,$r['no'],'Kazanılan teklif / müşteri alacağı',(float)$q['total'],0);$o['receivableOpened']=true;$o['customerPaid']=0;$o['customerDue']=(float)$q['total'];}
   $remain=(float)($o['customerDue']??((float)$o['total']-(float)($o['customerPaid']??0)));if($amount>$remain+0.001)throw new RuntimeException('Tahsilat kalan müşteri alacağından büyük olamaz.');$bank=&cash_account_resolve($s,$bankId,trim((string)($in['bankName']??'')),strtoupper(trim((string)($in['bankCurrency']??''))));if(!$bank)throw new RuntimeException('Kasa/Banka bulunamadı.');$orderCur=$o['currency']??$r['currency'];$fxRate=require_payment_fx($orderCur,(string)($bank['currency']??$orderCur),$in['fxRate']??0,$in['fxRateConfirmed']??false);$bankAmount=($bank['currency']??$orderCur)===$orderCur?$amount:$amount*$fxRate;
   $bank['balance']=(float)$bank['balance']+$bankAmount;$o['customerPaid']=(float)($o['customerPaid']??0)+$amount;$o['customerDue']=max(0,$remain-$amount);$scheduleAlloc=allocate_schedule_payment($o,$amount);$a['requestOpen']=(float)($a['requestOpen']??0)-$amount;$desc=trim((string)($in['description']??'Müşteri tahsilatı'));add_entry($a,$r['no'],$desc,0,$amount);
   $cashId=next_id($s['cash']);$group=uuid4();$s['cash'][]=['id'=>$cashId,'date'=>date('d.m.Y'),'accountId'=>$bankId,'account'=>$bank['name'],'currency'=>$o['currency']??$r['currency'],'request'=>$r['no'],'party'=>$r['customer'],'type'=>'Giriş','amountValue'=>$bankAmount,'orderAmount'=>$amount,'fxRate'=>$fxRate,'amount'=>$bankAmount,'note'=>$desc,'archived'=>false,'kind'=>'customer_prepayment','orderId'=>$oid,'batchId'=>$batchId?:null,'groupUuid'=>$group,'reversed'=>false];
   $j=$pdo->prepare('INSERT INTO finance_journal(group_uuid,action_type,request_id,order_id,party_name,cash_account_id,currency,amount,payload_json,created_by) VALUES(?,?,?,?,?,?,?,?,?,?)');$j->execute([$group,'customer_prepayment',$r['id'],$oid,$r['customer'],$bankId,$orderCur,$amount,json_encode(['cashId'=>$cashId,'description'=>$desc,'bankAmount'=>$bankAmount,'bankCurrency'=>$bank['currency'],'fxRate'=>$fxRate,'batchId'=>$batchId?:null,'scheduleAlloc'=>$scheduleAlloc],JSON_UNESCAPED_UNICODE),$u['id']]);$jid=(int)$pdo->lastInsertId();foreach($s['cash'] as &$c)if((int)$c['id']===$cashId)$c['journalId']=$jid;audit('customer_prepayment','order',(string)$oid,['amount'=>$amount,'journal_id'=>$jid]);
 }
 elseif($action==='finance_customer_receipt_update'){
   $jid=(int)($in['journalId']??0);$newAmount=round((float)($in['amount']??0),2);$newBankId=(int)($in['bankId']??0);$newDate=trim((string)($in['date']??date('Y-m-d')));$newDesc=trim((string)($in['description']??''));$newBatchId=(int)($in['batchId']??0);if($newAmount<=0)throw new RuntimeException('Tahsilat tutarı sıfırdan büyük olmalı.');if(!preg_match('/^\d{4}-\d{2}-\d{2}$/',$newDate))throw new RuntimeException('Geçerli bir tahsilat tarihi seçin.');if($newDesc==='')throw new RuntimeException('Açıklama zorunlu.');
   $q=$pdo->prepare('SELECT * FROM finance_journal WHERE id=? AND reversal_of IS NULL FOR UPDATE');$q->execute([$jid]);$jr=$q->fetch();if(!$jr)throw new RuntimeException('Tahsilat kaydı bulunamadı.');$actionType=(string)($jr['action_type']??'');if(!in_array($actionType,['customer_prepayment','generic_customer_receipt','delivery_batch_collection'],true))throw new RuntimeException('Bu hareket müşteri tahsilatı olarak düzenlenemez.');$chk=$pdo->prepare('SELECT COUNT(*) FROM finance_journal WHERE reversal_of=?');$chk->execute([$jid]);if((int)$chk->fetchColumn()>0)throw new RuntimeException('Ters işlem uygulanmış tahsilat düzenlenemez. Silip yeniden kaydedin.');
   $cashIndex=-1;foreach(($s['cash']??[]) as $i=>$c)if((int)($c['journalId']??0)===$jid&&!($c['reversed']??false)&&($c['kind']??'')!=='reversal'){$cashIndex=(int)$i;break;}if($cashIndex<0)throw new RuntimeException('Bağlı Kasa/Banka hareketi bulunamadı.');$cash=&$s['cash'][$cashIndex];$payload=json_decode((string)($jr['payload_json']??'{}'),true)?:[];$oldAmount=round((float)($jr['amount']??0),2);$oldBankAmount=round((float)($payload['bankAmount']??$cash['amountValue']??$cash['amount']??$oldAmount),2);$oldBankId=(int)($jr['cash_account_id']??$cash['accountId']??0);$oldBank=&cash_account_by_id($s,$oldBankId);$newBank=&cash_account_by_id($s,$newBankId);if(!$oldBank||!$newBank)throw new RuntimeException('Eski veya yeni Kasa/Banka hesabı bulunamadı.');
   $oid=(int)($jr['order_id']??$cash['orderId']??0);$o=null;if($oid){$tmp=&find_order_by_id($s,$oid);if($tmp)$o=&$tmp;}if(!$o)throw new RuntimeException('Bağlı sipariş bulunamadı.');$rid=(int)($jr['request_id']??$o['requestId']??0);$r=&find_request($s,$rid);if(!$r)throw new RuntimeException('Bağlı talep bulunamadı.');$sourceCur=(string)($jr['currency']??$o['currency']??$r['currency']??'EUR');$fx=require_payment_fx($sourceCur,(string)($newBank['currency']??$sourceCur),$in['fxRate']??0,$in['fxRateConfirmed']??false);$newBankAmount=round((($newBank['currency']??$sourceCur)===$sourceCur?$newAmount:$newAmount*$fx),2);$max=round((float)($o['customerDue']??0)+$oldAmount,2);if($newAmount>$max+0.001)throw new RuntimeException('Yeni tahsilat tutarı kalan müşteri alacağını aşıyor. En fazla '.number_format($max,2,',','.').' '.$sourceCur.' olabilir.');
   $oldDesc=(string)($payload['description']??$cash['note']??'Müşteri tahsilatı');$oldRef=(string)($payload['request']??$cash['request']??$r['no']);$oldDate=cash_row_iso_date($cash);$a=null;if($actionType==='generic_customer_receipt'){$pid=(int)($payload['partyId']??$cash['partyId']??0);if($pid)$a=&account_by_id($s,$pid);}if(!$a)$a=&customer_account($s,$r);$removedOldEntry=remove_account_entry_signature_date($a,$oldAmount,'credit',$oldDesc,$oldRef,$oldDate);if(!$removedOldEntry)throw new RuntimeException('Tahsilata bağlı müşteri cari hareketi bulunamadı; veri bütünlüğü için düzenleme durduruldu. Önce ekstreyi kontrol edin.');$a['requestOpen']=round((float)($a['requestOpen']??0)+$oldAmount,2);
   reverse_schedule_payment($o,$oldAmount);$o['customerPaid']=max(0,round((float)($o['customerPaid']??0)-$oldAmount,2));if(!empty($o['financialCancelled'])){$o['refundDue']=max(0,round((float)($o['refundDue']??0)-$oldAmount,2));}else{$o['customerDue']=round((float)($o['customerDue']??0)+$oldAmount,2);}
   $oldBatchId=(int)($payload['batchId']??$cash['batchId']??0);if($actionType==='delivery_batch_collection'&&$oldBatchId){$ob=&find_batch($o,$oldBatchId);if($ob){$ob['customerCollected']=max(0,round((float)($ob['customerCollected']??0)-$oldAmount,2));$ob['customerDue']=round((float)($ob['customerDue']??0)+$oldAmount,2);}}
   $oldBank['balance']=round((float)($oldBank['balance']??0)-$oldBankAmount,2);$newBank['balance']=round((float)($newBank['balance']??0)+$newBankAmount,2);$o['customerPaid']=round((float)($o['customerPaid']??0)+$newAmount,2);if(!empty($o['financialCancelled'])){$o['refundDue']=round((float)($o['refundDue']??0)+$newAmount,2);$o['customerDue']=0;}else{$o['customerDue']=max(0,round((float)($o['customerDue']??0)-$newAmount,2));}allocate_schedule_payment($o,$newAmount);$a['requestOpen']=round((float)($a['requestOpen']??0)-$newAmount,2);add_entry($a,$r['no'],$newDesc,0,$newAmount,$newDate);recalc_account_open($a);
   if($actionType==='delivery_batch_collection'){$targetBatchId=$newBatchId?:$oldBatchId;$nb=&find_batch($o,$targetBatchId);if(!$nb||($nb['status']??'')!=='Teslim Edildi')throw new RuntimeException('Parti tahsilatı yalnız teslim edilmiş bir partiye bağlanabilir.');if($newAmount>(float)($nb['customerDue']??0)+0.001)throw new RuntimeException('Yeni tahsilat tutarı seçilen partinin kalan alacağını aşıyor.');$nb['customerCollected']=round((float)($nb['customerCollected']??0)+$newAmount,2);$nb['customerDue']=max(0,round((float)($nb['customerDue']??0)-$newAmount,2));$newBatchId=$targetBatchId;}
   $cash['date']=date('d.m.Y',strtotime($newDate));$cash['accountId']=$newBankId;$cash['account']=$newBank['name'];$cash['amountValue']=$newBankAmount;$cash['amount']=$newBankAmount;$cash['orderAmount']=$newAmount;$cash['fxRate']=$fx;$cash['note']=$newDesc;$cash['batchId']=$newBatchId?:null;$cash['editedAt']=date('c');$cash['editedBy']=$u['name']??null;
   $payload['bankAmount']=$newBankAmount;$payload['bankCurrency']=$newBank['currency']??$sourceCur;$payload['fxRate']=$fx;$payload['description']=$newDesc;$payload['date']=$newDate;$payload['batchId']=$newBatchId?:null;$payload['request']=$r['no'];$uq=$pdo->prepare('UPDATE finance_journal SET cash_account_id=?,amount=?,payload_json=? WHERE id=?');$uq->execute([$newBankId,$newAmount,json_encode($payload,JSON_UNESCAPED_UNICODE),$jid]);reconcile_cash_balance_from_ledger($s,$oldBank);if($newBankId!==$oldBankId)reconcile_cash_balance_from_ledger($s,$newBank);sync_account_transaction_mirror($pdo,$s);sync_cash_transaction_mirror($pdo,$s);audit('finance_customer_receipt_update','finance_journal',(string)$jid,['old_amount'=>$oldAmount,'new_amount'=>$newAmount,'old_bank_id'=>$oldBankId,'new_bank_id'=>$newBankId,'date'=>$newDate]);unset($cash);
 }
 elseif($action==='finance_delete'){
   $jid=(int)($in['journalId']??0);$q=$pdo->prepare('SELECT * FROM finance_journal WHERE id=? FOR UPDATE');$q->execute([$jid]);$jr=$q->fetch();if(!$jr)throw new RuntimeException('Finans kaydı bulunamadı.');if(($jr['action_type']??'')==='reversal'&&(int)($jr['reversal_of']??0)>0){$jid=(int)$jr['reversal_of'];$q->execute([$jid]);$jr=$q->fetch();if(!$jr)throw new RuntimeException('Asıl finans kaydı bulunamadı.');}
   $supported=['customer_prepayment','delivery_batch_collection','delivery_batch_supplier_payment','generic_customer_receipt','generic_supplier_payment','operational_expense_payment','cash_transfer','cash_balance_adjustment','cash_opening_balance'];if(!in_array((string)$jr['action_type'],$supported,true))throw new RuntimeException('Bu kayıt türü için gerçek silme desteklenmiyor.');
   $rq=$pdo->prepare('SELECT id FROM finance_journal WHERE reversal_of=?');$rq->execute([$jid]);$reversalIds=array_map('intval',$rq->fetchAll(PDO::FETCH_COLUMN));$hadReversal=count($reversalIds)>0;$payload=json_decode((string)($jr['payload_json']??'{}'),true)?:[];$amount=(float)$jr['amount'];$actionType=(string)$jr['action_type'];$oid=(int)($jr['order_id']??0);$o=null;if($oid){$tmp=&find_order_by_id($s,$oid);if($tmp)$o=&$tmp;}$rid=(int)($jr['request_id']??0);$r=null;if($rid){$tmpR=&find_request($s,$rid);if($tmpR)$r=&$tmpR;}
   $cashRows=[];foreach(($s['cash']??[]) as $c)if((int)($c['journalId']??0)===$jid||(int)($c['reversalOf']??0)===$jid)$cashRows[]=$c;
   if(!$hadReversal){
     if(in_array($actionType,['customer_prepayment','delivery_batch_collection','generic_customer_receipt'],true)&&$o){$o['customerPaid']=max(0,(float)($o['customerPaid']??0)-$amount);reverse_schedule_payment($o,$amount);if(!empty($o['financialCancelled'])){$o['customerDue']=0;$o['refundDue']=max(0,(float)($o['refundDue']??0)-$amount);}else{$o['customerDue']=round((float)($o['customerDue']??0)+$amount,2);}if($actionType==='delivery_batch_collection'){$b=&find_batch($o,(int)($payload['batchId']??0));if($b){$b['customerCollected']=max(0,(float)($b['customerCollected']??0)-$amount);$b['customerDue']=round((float)($b['customerDue']??0)+$amount,2);}}}
     elseif($actionType==='delivery_batch_supplier_payment'){if(!$o)throw new RuntimeException('Sipariş bulunamadı.');$poId=(int)($payload['poId']??0);$b=&find_batch($o,(int)($payload['batchId']??0));$sp=null;if($b)foreach(($b['supplierPayments']??[]) as &$xx)if((int)($xx['poId']??0)===$poId){$sp=&$xx;break;}$po=null;foreach(($o['pos']??[]) as &$pp)if((int)($pp['id']??0)===$poId){$po=&$pp;break;}if($sp){$sp['paid']=max(0,(float)($sp['paid']??0)-$amount);$sp['due']=round((float)($sp['due']??0)+$amount,2);}if($po){$po['paid']=max(0,(float)($po['paid']??0)-$amount);$po['due']=round((float)($po['due']??0)+$amount,2);}}
     elseif($actionType==='generic_supplier_payment'){ $poId=(int)($payload['poId']??0);if($poId){$owner=null;$po=&find_po_by_id($s,$poId,$owner);if($po){$po['paid']=max(0,(float)($po['paid']??0)-$amount);$po['due']=round((float)($po['due']??0)+$amount,2);}} }
   }
   finance_delete_account_cleanup($s,$jr,$payload,$cashRows,trim((string)($in['txnId']??'')),(int)($in['accountId']??0),$hadReversal);
   if($actionType==='operational_expense_payment'&&$r){$planned='';$r['realizedComparisonExpenses']=array_values(array_filter(($r['realizedComparisonExpenses']??[]),function($x)use($jid,&$planned){if((int)($x['journalId']??0)===$jid){$planned=(string)($x['plannedCostLineId']??'');return false;}return true;}));if($planned!=='')foreach(($r['costLines']??[]) as &$cl)if((string)($cl['id']??'')===$planned){$activeJ=0;foreach(($r['realizedComparisonExpenses']??[]) as $other)if((string)($other['plannedCostLineId']??'')===$planned&&!($other['reversed']??false))$activeJ=(int)($other['journalId']??0);$cl['realizedJournalId']=$activeJ;if(!$activeJ)unset($cl['realizedAt']);break;}unset($cl);}
   $removedCash=remove_cash_rows($s,fn($c)=>(int)($c['journalId']??0)===$jid||(int)($c['reversalOf']??0)===$jid);
   if($reversalIds){$qd=$pdo->prepare('DELETE FROM finance_journal WHERE id IN ('.implode(',',array_fill(0,count($reversalIds),'?')).')');$qd->execute($reversalIds);}$pdo->prepare('DELETE FROM finance_journal WHERE id=?')->execute([$jid]);sync_account_transaction_mirror($pdo,$s);sync_cash_transaction_mirror($pdo,$s);audit('finance_hard_delete','finance_journal',(string)$jid,['source_action'=>$actionType,'amount'=>$amount,'removed_cash_rows'=>count($removedCash['rows']),'removed_reversals'=>count($reversalIds)]);
 }
 elseif($action==='reverse_finance'){
   $jid=(int)($in['journalId']??0);$j=$pdo->prepare('SELECT * FROM finance_journal WHERE id=? FOR UPDATE');$j->execute([$jid]);$jr=$j->fetch();if(!$jr)throw new RuntimeException('Finans kaydı bulunamadı.');
   $chk=$pdo->prepare('SELECT COUNT(*) FROM finance_journal WHERE reversal_of=?');$chk->execute([$jid]);if((int)$chk->fetchColumn()>0)throw new RuntimeException('Bu işlem daha önce ters kaydedildi.');
   $supported=['customer_prepayment','delivery_batch_collection','delivery_batch_supplier_payment','generic_customer_receipt','generic_supplier_payment','operational_expense_payment','cash_transfer','cash_balance_adjustment','cash_opening_balance'];if(!in_array($jr['action_type'],$supported,true))throw new RuntimeException('Bu kayıt türü için ters işlem desteklenmiyor.');
   $amount=(float)$jr['amount'];$payload=json_decode((string)($jr['payload_json']??'{}'),true)?:[];$bankAmount=(float)($payload['bankAmount']??$amount);$bank=&cash_account_by_id($s,(int)$jr['cash_account_id']);if(!$bank)throw new RuntimeException('Kasa/Banka bulunamadı.');
   $oid=(int)($jr['order_id']??0);$o=null;if($oid){$tmp=&find_order_by_id($s,$oid);if($tmp)$o=&$tmp;}$rid=(int)($jr['request_id']??0);$r=null;if($rid){$tmpR=&find_request($s,$rid);if($tmpR)$r=&$tmpR;}
   $actionType=(string)$jr['action_type'];$reverseType='';$reverseParty=(string)($jr['party_name']??'');
   if($actionType==='cash_transfer'){
     $to=&cash_account_by_id($s,(int)($payload['toId']??0));$target=round((float)($payload['targetAmount']??0),2);if(!$to)throw new RuntimeException('Virman hedef hesabı bulunamadı.');if((float)$to['balance']<$target-0.001)throw new RuntimeException('Virman ters kaydı için hedef hesap bakiyesi yetersiz.');$bank['balance']=(float)$bank['balance']+$amount;$to['balance']=(float)$to['balance']-$target;$reverseParty=$to['name'];$reverseType='Giriş';
     foreach($s['cash'] as &$c)if((int)($c['journalId']??0)===$jid)$c['reversed']=true;$s['cash'][]=['id'=>next_id($s['cash']),'date'=>date('d.m.Y'),'accountId'=>$bank['id'],'account'=>$bank['name'],'currency'=>$bank['currency'],'request'=>'VİRMAN TERS','party'=>$to['name'],'type'=>'Giriş','amountValue'=>$amount,'amount'=>$amount,'note'=>'Virman ters kaydı','archived'=>false,'kind'=>'reversal','reversalOf'=>$jid];$s['cash'][]=['id'=>next_id($s['cash']),'date'=>date('d.m.Y'),'accountId'=>$to['id'],'account'=>$to['name'],'currency'=>$to['currency'],'request'=>'VİRMAN TERS','party'=>$bank['name'],'type'=>'Çıkış','amountValue'=>$target,'amount'=>$target,'note'=>'Virman ters kaydı','archived'=>false,'kind'=>'reversal','reversalOf'=>$jid];
   }elseif(in_array($actionType,['cash_balance_adjustment','cash_opening_balance'],true)){
     $delta=(float)($payload['delta']??(($actionType==='cash_opening_balance')?$amount:0));$bank['balance']=(float)$bank['balance']-$delta;$reverseParty='Bakiye Düzeltme';$reverseType=$delta>0?'Çıkış':'Giriş';foreach($s['cash'] as &$c)if((int)($c['journalId']??0)===$jid)$c['reversed']=true;
   }elseif(in_array($actionType,['customer_prepayment','delivery_batch_collection','generic_customer_receipt'],true)){
     if((float)$bank['balance']<$bankAmount-0.001)throw new RuntimeException('Ters işlem için kasa/banka bakiyesi yetersiz.');
     $bank['balance']=(float)$bank['balance']-$bankAmount;
     if($o){$o['customerPaid']=max(0,(float)($o['customerPaid']??0)-$amount);reverse_schedule_payment($o,$amount);if(!empty($o['financialCancelled'])){$o['customerDue']=0;$o['refundDue']=max(0,(float)($o['refundDue']??0)-$amount);}else{$o['customerDue']=(float)($o['customerDue']??0)+$amount;}}
     if($actionType==='generic_customer_receipt'){$a=&account_by_id($s,(int)($payload['partyId']??0));if(!$a)throw new RuntimeException('Müşteri carisi bulunamadı.');$a['requestOpen']=(float)($a['requestOpen']??0)+$amount;add_entry($a,(string)($payload['request']??'TERS'),'Ters kayıt: müşteri tahsilatı',$amount,0);$reverseParty=$a['name'];}
     else{if(!$o||!$r)throw new RuntimeException('Sipariş/talep bulunamadı.');$a=&customer_account($s,$r);$a['requestOpen']=(float)($a['requestOpen']??0)+$amount;add_entry($a,$r['no'],'Ters kayıt: müşteri tahsilatı',$amount,0);if($actionType==='delivery_batch_collection'){$b=&find_batch($o,(int)($payload['batchId']??0));if($b){$b['customerCollected']=max(0,(float)($b['customerCollected']??0)-$amount);$b['customerDue']=(float)($b['customerDue']??0)+$amount;}}$reverseParty=$r['customer'];}
     $reverseType='Çıkış';
   }elseif($actionType==='operational_expense_payment'){
     $bank['balance']=(float)$bank['balance']+$bankAmount;
     $a=&account_by_id($s,(int)($payload['partyId']??0));if(!$a)throw new RuntimeException('Gider carisi bulunamadı.');
     add_entry($a,(string)($payload['request']??'TERS'),'Ters kayıt: operasyon gideri ödeme',0,$amount,date('Y-m-d'),(array)($payload['fxSnapshot']??[]));
     add_entry($a,(string)($payload['request']??'TERS'),'Ters kayıt: operasyon gideri tahakkuk',$amount,0,date('Y-m-d'),(array)($payload['fxSnapshot']??[]));
     if($r&&!empty($r['realizedComparisonExpenses']))foreach($r['realizedComparisonExpenses'] as &$rx)if((int)($rx['journalId']??0)===$jid){$rx['reversed']=true;$planned=(string)($rx['plannedCostLineId']??'');if($planned!==''){foreach(($r['costLines']??[]) as &$cl)if((string)($cl['id']??'')===$planned){$activeJ=0;foreach(($r['realizedComparisonExpenses']??[]) as $other)if((string)($other['plannedCostLineId']??'')===$planned&&!($other['reversed']??false))$activeJ=(int)($other['journalId']??0);$cl['realizedJournalId']=$activeJ;if(!$activeJ)unset($cl['realizedAt']);break;}unset($cl);}}
     $reverseParty=$a['name'];$reverseType='Giriş';
   }else{
     $bank['balance']=(float)$bank['balance']+$bankAmount;$poId=(int)($payload['poId']??0);
     if($actionType==='delivery_batch_supplier_payment'){
       if(!$o||!$r)throw new RuntimeException('Sipariş/talep bulunamadı.');$bid=(int)($payload['batchId']??0);$b=&find_batch($o,$bid);$sp=null;if($b)foreach($b['supplierPayments'] as &$xx)if((int)$xx['poId']===$poId){$sp=&$xx;break;}$po=null;foreach($o['pos'] as &$pp)if((int)$pp['id']===$poId){$po=&$pp;break;}if(!$sp||!$po)throw new RuntimeException('Parti tedarikçi kaydı bulunamadı.');$sp['paid']=max(0,(float)$sp['paid']-$amount);$sp['due']=(float)$sp['due']+$amount;$po['paid']=max(0,(float)$po['paid']-$amount);$po['due']=(float)$po['due']+$amount;$a=&supplier_account($s,(string)$sp['supplier'],$po['currency']??($o['currency']??'EUR'),$r['no']);$a['requestOpen']=(float)($a['requestOpen']??0)-$amount;add_entry($a,$r['no'],'Ters kayıt: tedarikçi ödemesi',0,$amount);$reverseParty=$sp['supplier'];
     }else{$a=&account_by_id($s,(int)($payload['partyId']??0));if(!$a)throw new RuntimeException('Tedarikçi carisi bulunamadı.');$a['requestOpen']=(float)($a['requestOpen']??0)-$amount;add_entry($a,(string)($payload['request']??'TERS'),'Ters kayıt: tedarikçi ödemesi',0,$amount);if($poId){$owner=null;$po=&find_po_by_id($s,$poId,$owner);if($po){$po['paid']=max(0,(float)($po['paid']??0)-$amount);$po['due']=(float)($po['due']??0)+$amount;}}$reverseParty=$a['name'];}
     $reverseType='Giriş';
   }
   if($actionType!=='cash_transfer'){foreach($s['cash'] as &$c)if((int)($c['journalId']??0)===$jid)$c['reversed']=true;$s['cash'][]=['id'=>next_id($s['cash']),'date'=>date('d.m.Y'),'accountId'=>$bank['id'],'account'=>$bank['name'],'currency'=>$bank['currency']??$jr['currency'],'request'=>$r['no']??($payload['request']??''),'party'=>$reverseParty,'type'=>$reverseType,'amountValue'=>$bankAmount,'orderAmount'=>$amount,'amount'=>$bankAmount,'note'=>'Ters kayıt','archived'=>false,'kind'=>'reversal','batchId'=>(int)($payload['batchId']??0),'reversalOf'=>$jid];}
   $group=uuid4();$q=$pdo->prepare('INSERT INTO finance_journal(group_uuid,action_type,request_id,order_id,party_name,cash_account_id,currency,amount,reversal_of,payload_json,created_by) VALUES(?,?,?,?,?,?,?,?,?,?,?)');$q->execute([$group,'reversal',$jr['request_id'],$oid?:null,$jr['party_name'],$jr['cash_account_id'],$jr['currency'],$amount,$jid,json_encode(['sourceAction'=>$actionType],JSON_UNESCAPED_UNICODE),$u['id']]);audit('finance_reverse','finance_journal',(string)$jid,['amount'=>$amount,'source_action'=>$actionType]);
 }
 elseif($action==='finance_journal_meta_update'){
   $jid=(int)($in['journalId']??0);$date=trim((string)($in['date']??date('Y-m-d')));if(!preg_match('/^\d{4}-\d{2}-\d{2}$/',$date))$date=date('Y-m-d');$ref=trim((string)($in['ref']??''));$desc=trim((string)($in['description']??''));if($desc==='')throw new RuntimeException('Açıklama zorunlu.');
   $q=$pdo->prepare('SELECT * FROM finance_journal WHERE id=? AND reversal_of IS NULL FOR UPDATE');$q->execute([$jid]);$jr=$q->fetch();if(!$jr)throw new RuntimeException('Finans hareketi bulunamadı veya silinmiş.');
   $cashFound=false;foreach($s['cash'] as &$cm)if((int)($cm['journalId']??0)===$jid&&!($cm['reversed']??false)){ $cm['date']=date('d.m.Y',strtotime($date));$cm['request']=$ref;$cm['note']=$desc;$cashFound=true;}unset($cm);if(!$cashFound)throw new RuntimeException('Bağlı Kasa/Banka hareketi bulunamadı.');
   $payload=json_decode((string)($jr['payload_json']??'{}'),true)?:[];$partyId=(int)($payload['partyId']??0);if($partyId){$pa=&account_by_id($s,$partyId);if($pa){foreach($pa['entries'] as &$e){$amt=max((float)($e['debit']??0),(float)($e['credit']??0));if(abs($amt-(float)$jr['amount'])<0.011 && (string)$e['ref']!==''){if((string)($e['ref']??'')===(string)($payload['request']??$e['ref'])){$e['date']=$date;$e['ref']=$ref;$e['desc']=$desc;}}}unset($e);}}
   $payload['request']=$ref;$payload['description']=$desc;$payload['date']=$date;$uQ=$pdo->prepare('UPDATE finance_journal SET payload_json=? WHERE id=?');$uQ->execute([json_encode($payload,JSON_UNESCAPED_UNICODE),$jid]);audit('finance_journal_meta_update','finance_journal',(string)$jid,['date'=>$date,'ref'=>$ref,'description'=>$desc]);
 }
 elseif($action==='account_bulk_import'){
   $rows=$in['rows']??[];if(!is_array($rows)||!count($rows))throw new RuntimeException('İçe aktarılacak cari satırı yok.');if(count($rows)>1000)throw new RuntimeException('Tek seferde en fazla 1.000 cari içe aktarılabilir.');
   $created=0;$skipped=0;
   foreach($rows as $row){
     $name=trim((string)($row['name']??''));if($name===''){$skipped++;continue;}
     $type=mb_strtolower(trim((string)($row['type']??'customer')),'UTF-8');
     $typeMap=['müşteri'=>'customer','musteri'=>'customer','customer'=>'customer','tedarikçi'=>'supplier','tedarikci'=>'supplier','supplier'=>'supplier','resmi kurum'=>'public','resmî kurum'=>'public','public'=>'public','özel kurum'=>'private','ozel kurum'=>'private','private'=>'private'];
     $type=$typeMap[$type]??'customer';
     $currency=strtoupper((string)($row['currency']??'EUR'));if(!in_array($currency,['EUR','USD','TRY'],true))$currency='EUR';
     $taxNo=trim((string)($row['taxNo']??''));$duplicate=false;
     foreach($s['accounts'] as $a){
       $sameCurrency=(string)($a['currency']??'EUR')===$currency;
       $sameTax=$taxNo!==''&&trim((string)($a['taxNo']??''))===$taxNo;
       $sameName=norm_party_name((string)($a['name']??''))===norm_party_name($name);
       if($sameCurrency&&(($taxNo!==''&&$sameTax)||($taxNo===''&&$sameName&&(string)($a['type']??'customer')===$type))){$duplicate=true;break;}
     }
     if($duplicate){$skipped++;continue;}
     $id=next_id($s['accounts']);$key=import_party_key($s,$name,$taxNo);$balance=import_number($row['balance']??0);
     $a=['id'=>$id,'partyKey'=>$key,'type'=>$type,'name'=>$name,'currency'=>$currency,'requestOpen'=>$balance,'request'=>'Excel İçe Aktarım','taxNo'=>$taxNo,'contact'=>trim((string)($row['contact']??'')),'phone'=>trim((string)($row['phone']??'')),'email'=>trim((string)($row['email']??'')),'website'=>trim((string)($row['website']??'')),'address'=>trim((string)($row['address']??'')),'country'=>trim((string)($row['country']??'')),'dueDate'=>trim((string)($row['dueDate']??'')),'description'=>trim((string)($row['description']??'')),'note'=>trim((string)($row['note']??'')),'entries'=>[]];
     if(abs($balance)>0.001)add_entry($a,'EXCEL-IMPORT','Excel açılış bakiyesi',$balance>0?$balance:0,$balance<0?abs($balance):0);
     $s['accounts'][]=$a;$created++;
   }
   audit('account_bulk_import','account',null,['created'=>$created,'skipped'=>$skipped]);
 }
 elseif($action==='request_single_import'){
   $meta=$in['meta']??[];$itemsIn=$in['items']??[];
   if(!is_array($meta)||!is_array($itemsIn)||!count($itemsIn))throw new RuntimeException('Tek talep için en az bir kalem gerekli.');
   if(count($itemsIn)>500)throw new RuntimeException('Bir talepte en fazla 500 Excel kalemi aktarılabilir.');
   $partyKey=trim((string)($meta['customerPartyKey']??''));if($partyKey==='')throw new RuntimeException('Mevcut Müşteri / Kurum seçimi zorunlu.');
   $matched=null;
   foreach(($s['accounts']??[]) as $a){
     if((string)($a['partyKey']??'')===$partyKey && in_array((string)($a['type']??'customer'),['customer','public','private'],true)){$matched=$a;break;}
   }
   if(!$matched)throw new RuntimeException('Seçilen müşteri/kurum cari kartı bulunamadı. Cari listesini yenileyip tekrar seçin.');
   $customer=trim((string)($matched['name']??''));if($customer==='')throw new RuntimeException('Seçilen cari kartının adı boş.');
   $currency=strtoupper(trim((string)($meta['currency']??'EUR')));if(!in_array($currency,['EUR','USD','TRY'],true))$currency='EUR';
   $deadline=trim((string)($meta['deadline']??''));if($deadline===''||!preg_match('/^\d{4}-\d{2}-\d{2}$/',$deadline))$deadline=date('Y-m-d',strtotime('+14 days'));
   $sourceRef=trim((string)($meta['sourceRef']??''));
   if($sourceRef!=='')foreach(($s['requests']??[]) as $rr)if(trim((string)($rr['importSourceRef']??''))===$sourceRef)throw new RuntimeException('Bu dış referans / talep no daha önce kullanılmış: '.$sourceRef);
   $delivery=strtoupper(trim((string)($meta['delivery']??'CIF')));if(!in_array($delivery,['EXW','FCA','FOB','CFR','CIF','DAP','DDP'],true))$delivery='CIF';
   $priority=(string)($meta['priority']??'normal');if(!in_array($priority,['normal','priority','urgent'],true))$priority='normal';
   $iid=import_next_item_id($s);$items=[];
   foreach($itemsIn as $row){
     $name=trim((string)($row['name']??''));if($name==='')continue;
     $qty=import_number($row['qty']??0);if($qty<=0)throw new RuntimeException($name.' kaleminin miktarı 0’dan büyük olmalı.');
     $items[]=['id'=>$iid++,'name'=>$name,'qty'=>$qty,'unit'=>trim((string)($row['unit']??''))?:'Adet','brandModel'=>trim((string)($row['brandModel']??'')),'spec'=>trim((string)($row['spec']??''))];
   }
   if(!count($items))throw new RuntimeException('Geçerli talep kalemi bulunamadı.');
   $rid=next_id($s['requests']);$no=import_request_no($s);$title=trim((string)($meta['title']??''));if($title==='')throw new RuntimeException('Excel talebi için Konu Başlığı zorunludur.');
   $s['requests'][]=[
     'id'=>$rid,'no'=>$no,'title'=>$title,'customer'=>$customer,'customerPartyKey'=>$partyKey,
     'country'=>trim((string)($matched['country']??'')),'delivery'=>$delivery,'deliveryPlace'=>trim((string)($meta['deliveryPlace']??'')),
     'deadline'=>$deadline,'deadlineClass'=>'green','days'=>'','status'=>'collecting',
     'payment'=>'T/T %30 Peşin / %70 Sevkiyat Öncesi','paymentType'=>'tt30_70','paymentPlan'=>[['percent'=>30,'label'=>'Peşin'],['percent'=>70,'label'=>'Sevkiyat Öncesi']],
     'priority'=>$priority,'bank'=>'','currency'=>$currency,'description'=>trim((string)($meta['description']??'')),
     'tags'=>[['Excel Tek Talep','#0891b2']],'items'=>$items,'importSourceRef'=>$sourceRef
   ];
   audit('request_single_import','request',(string)$rid,['request_no'=>$no,'customer_party_key'=>$partyKey,'items'=>count($items),'forced_status'=>'collecting']);
 } elseif($action==='request_bulk_import'){
   $rows=$in['rows']??[];if(!is_array($rows)||!count($rows))throw new RuntimeException('İçe aktarılacak talep satırı yok.');if(count($rows)>1000)throw new RuntimeException('Tek seferde en fazla 1.000 talep içe aktarılabilir.');
   $created=0;$skipped=0;$matchedExisting=0;
   foreach($rows as $row){
     $customer=trim((string)($row['customer']??''));if($customer===''){$skipped++;continue;}
     $title=trim((string)($row['title']??''))?:'Excel Talebi';
     $currency=strtoupper((string)($row['currency']??'EUR'));if(!in_array($currency,['EUR','USD','TRY'],true))$currency='EUR';
     $deadline=trim((string)($row['deadline']??''));if($deadline!==''&&!preg_match('/^\d{4}-\d{2}-\d{2}$/',$deadline))$deadline='';
     $sourceRef=trim((string)($row['sourceRef']??''));
     $sig=norm_party_name($customer).'|'.norm_party_name($title).'|'.$deadline.'|'.$currency;
     $duplicate=false;
     foreach($s['requests'] as $rr){
       if($sourceRef!==''&&trim((string)($rr['importSourceRef']??''))===$sourceRef){$duplicate=true;break;}
       $existingSig=norm_party_name((string)($rr['customer']??'')).'|'.norm_party_name((string)($rr['title']??'')).'|'.trim((string)($rr['deadline']??'')).'|'.strtoupper((string)($rr['currency']??'EUR'));
       if($existingSig===$sig){$duplicate=true;break;}
     }
     if($duplicate){$skipped++;continue;}
     $taxNo=trim((string)($row['taxNo']??''));$matchedParty=find_import_party($s,$customer,$taxNo);
     if($matchedParty){
       $matchedExisting++;
       $customer=(string)($matchedParty['name']??$customer);
       $partyKey=(string)($matchedParty['partyKey']??('pty-'.($matchedParty['id']??0)));
       if($taxNo==='')$taxNo=trim((string)($matchedParty['taxNo']??''));
     }else $partyKey=import_party_key($s,$customer,$taxNo);
     $type=mb_strtolower(trim((string)($row['customerType']??($matchedParty['type']??'customer'))),'UTF-8');
     $typeMap=['müşteri'=>'customer','musteri'=>'customer','customer'=>'customer','resmi kurum'=>'public','resmî kurum'=>'public','public'=>'public','özel kurum'=>'private','ozel kurum'=>'private','private'=>'private'];
     $type=$typeMap[$type]??'customer';
     $hasAccount=false;foreach($s['accounts'] as $a)if((string)($a['partyKey']??'')===$partyKey&&(string)($a['currency']??'')===$currency){$hasAccount=true;break;}
     if(!$hasAccount){$aid=next_id($s['accounts']);$s['accounts'][]=['id'=>$aid,'partyKey'=>$partyKey,'type'=>$type,'name'=>$customer,'currency'=>$currency,'requestOpen'=>0,'request'=>'Excel Talep Aktarımı','taxNo'=>$taxNo,'contact'=>trim((string)($row['contact']??'')),'phone'=>trim((string)($row['phone']??'')),'email'=>trim((string)($row['email']??'')),'country'=>trim((string)($row['country']??'')),'address'=>'','entries'=>[]];}
     $rid=next_id($s['requests']);$no=import_request_no($s);$priority=mb_strtolower(trim((string)($row['priority']??'normal')),'UTF-8');
     $priorityMap=['normal'=>'normal','öncelikli'=>'priority','oncelikli'=>'priority','priority'=>'priority','acil'=>'urgent','urgent'=>'urgent'];$priority=$priorityMap[$priority]??'normal';
     $s['requests'][]=['id'=>$rid,'no'=>$no,'title'=>$title,'customer'=>$customer,'customerPartyKey'=>$partyKey,'country'=>trim((string)($row['country']??'')),'delivery'=>trim((string)($row['delivery']??''))?:'CIF','deliveryPlace'=>trim((string)($row['deliveryPlace']??'')),'deadline'=>$deadline?:date('Y-m-d',strtotime('+14 days')),'deadlineClass'=>'green','days'=>'','status'=>'collecting','payment'=>'T/T %30 Peşin / %70 Sevkiyat Öncesi','paymentType'=>'tt30_70','paymentPlan'=>[['percent'=>30,'label'=>'Peşin'],['percent'=>70,'label'=>'Sevkiyat Öncesi']],'priority'=>$priority,'bank'=>'','currency'=>$currency,'description'=>trim((string)($row['description']??'')),'tags'=>[['Excel Aktarım','#0891b2']], 'items'=>[],'importSourceRef'=>$sourceRef];
     $created++;
   }
   audit('request_bulk_import','request',null,['created'=>$created,'skipped'=>$skipped,'matched_existing_customer'=>$matchedExisting,'forced_status'=>'collecting']);
 }
 elseif($action==='request_delete'){
   $rid=(int)($in['requestId']??0);$r=&find_request($s,$rid);if(!$r)throw new RuntimeException('Talep bulunamadı.');$no=(string)($r['no']??'');
   $orderIds=[];foreach(($s['orders']??[]) as $o)if((int)($o['requestId']??0)===$rid)$orderIds[]=(int)($o['id']??0);
   $journalRows=[];$qj=$pdo->prepare('SELECT * FROM finance_journal WHERE reversal_of IS NULL AND (request_id=?'.($orderIds?' OR order_id IN ('.implode(',',array_fill(0,count($orderIds),'?')).')':'').') FOR UPDATE');$qj->execute(array_merge([$rid],$orderIds));$journalRows=$qj->fetchAll(PDO::FETCH_ASSOC);$originalJournalIds=array_map(fn($x)=>(int)$x['id'],$journalRows);$journalIds=$originalJournalIds;$reversalIds=[];
   if($originalJournalIds){$qr=$pdo->prepare('SELECT id,reversal_of FROM finance_journal WHERE reversal_of IN ('.implode(',',array_fill(0,count($originalJournalIds),'?')).') FOR UPDATE');$qr->execute($originalJournalIds);$reversalRows=$qr->fetchAll(PDO::FETCH_ASSOC);$reversalIds=array_map(fn($x)=>(int)$x['id'],$reversalRows);$journalIds=array_values(array_unique(array_merge($originalJournalIds,$reversalIds)));}
   $hasFinancialLinks=false;foreach(($s['accounts']??[]) as $a)foreach(($a['entries']??[]) as $e)if((string)($e['ref']??'')===$no){$hasFinancialLinks=true;break 2;}if(!$hasFinancialLinks)foreach(($s['cash']??[]) as $c)if((string)($c['request']??'')===$no||in_array((int)($c['orderId']??0),$orderIds,true)||in_array((int)($c['journalId']??0),$journalIds,true)||in_array((int)($c['reversalOf']??0),$journalIds,true)){$hasFinancialLinks=true;break;}
   if($hasFinancialLinks&&!user_is_admin($u)&&(!has_app_write_permission('finance',$u)||!has_app_write_permission('cash',$u)))throw new RuntimeException('Bu talep finans/cari hareketi içeriyor. Tam silme için Finans ve Kasa/Banka yazma yetkileri gerekli.');
   // Journal-linked cari entries may use a PO number or a custom payment reference instead of the request number.
   // Remove those effects first while request/order context still exists, then perform exact request-reference cleanup.
   $allCashRows=$s['cash']??[];$reversalOfSet=[];foreach(($reversalRows??[]) as $rr)$reversalOfSet[(int)($rr['reversal_of']??0)]=true;
   foreach($journalRows as $jr){$payload=json_decode((string)($jr['payload_json']??'{}'),true)?:[];finance_delete_account_cleanup($s,$jr,$payload,$allCashRows,'',0,!empty($reversalOfSet[(int)$jr['id']]));}
   $removedCash=remove_cash_rows($s,fn($c)=>(string)($c['request']??'')===$no||in_array((int)($c['orderId']??0),$orderIds,true)||in_array((int)($c['journalId']??0),$journalIds,true)||in_array((int)($c['reversalOf']??0),$journalIds,true));
   $removedEntries=0;foreach($s['accounts'] as &$a){$before=count($a['entries']??[]);$a['entries']=array_values(array_filter(($a['entries']??[]),fn($e)=>(string)($e['ref']??'')!==$no));$removedEntries+=$before-count($a['entries']);recalc_account_open($a);if(($a['request']??'')===$no)$a['request']='';}unset($a);
   $s['requests']=array_values(array_filter($s['requests'],fn($x)=>(int)($x['id']??0)!==$rid));$s['suppliers']=array_values(array_filter(($s['suppliers']??[]),fn($x)=>(int)($x['requestId']??0)!==$rid));$s['orders']=array_values(array_filter($s['orders'],fn($x)=>(int)($x['requestId']??0)!==$rid));$s['documents']=array_values(array_filter(($s['documents']??[]),fn($x)=>(int)($x['requestId']??0)!==$rid));$s['requestAttachments']=array_values(array_filter(($s['requestAttachments']??[]),fn($x)=>(int)($x['requestId']??0)!==$rid));$s['guarantees']=array_values(array_filter(($s['guarantees']??[]),fn($x)=>(int)($x['requestId']??0)!==$rid));unset($s['customerQuotes'][(string)$rid]);foreach(array_keys($s['selected']??[]) as $k)if(str_starts_with((string)$k,$rid.':'))unset($s['selected'][$k]);
   if($journalIds){$qd=$pdo->prepare('DELETE FROM finance_journal WHERE id IN ('.implode(',',array_fill(0,count($journalIds),'?')).')');$qd->execute($journalIds);}
   if($orderIds){$qbi=$pdo->prepare('DELETE dbi FROM delivery_batch_items dbi JOIN delivery_batches db ON db.id=dbi.batch_id WHERE db.sales_order_id IN ('.implode(',',array_fill(0,count($orderIds),'?')).')');$qbi->execute($orderIds);$qp=$pdo->prepare('DELETE FROM purchase_orders WHERE sales_order_id IN ('.implode(',',array_fill(0,count($orderIds),'?')).')');$qp->execute($orderIds);$qb=$pdo->prepare('DELETE FROM delivery_batches WHERE sales_order_id IN ('.implode(',',array_fill(0,count($orderIds),'?')).')');$qb->execute($orderIds);$qo=$pdo->prepare('DELETE FROM sales_orders WHERE id IN ('.implode(',',array_fill(0,count($orderIds),'?')).')');$qo->execute($orderIds);}
   foreach(['request_items','supplier_quotes','customer_quotes','commercial_documents'] as $tbl){$qd=$pdo->prepare('DELETE FROM `'.$tbl.'` WHERE request_id=?');$qd->execute([$rid]);}$pdo->prepare('DELETE FROM requests WHERE id=?')->execute([$rid]);
   $fq=$pdo->prepare('SELECT id,request_id,stored_name,relative_path FROM file_registry WHERE request_id=? AND deleted_at IS NULL');$fq->execute([$rid]);$frows=$fq->fetchAll();$storageBase=realpath(__DIR__.'/../storage')?:__DIR__.'/../storage';$trashBase=$storageBase.DIRECTORY_SEPARATOR.'trash'.DIRECTORY_SEPARATOR.'request-docs'.DIRECTORY_SEPARATOR.date('Ymd').DIRECTORY_SEPARATOR.$rid;if(!is_dir($trashBase)&&!@mkdir($trashBase,0770,true)&&!is_dir($trashBase))throw new RuntimeException('Talep dokümanları çöp kutusuna hazırlanamadı.');foreach($frows as $fr){$src=$storageBase.DIRECTORY_SEPARATOR.str_replace('/',DIRECTORY_SEPARATOR,ltrim((string)$fr['relative_path'],'/'));$newRel=(string)$fr['relative_path'];if(is_file($src)){$trashName=(int)$fr['id'].'_'.basename((string)$fr['stored_name']);$dest=$trashBase.DIRECTORY_SEPARATOR.$trashName;if(!@copy($src,$dest))throw new RuntimeException('Talep dokümanı çöp kutusuna kopyalanamadı.');@chmod($dest,0640);$newRel='trash/request-docs/'.date('Ymd').'/'.$rid.'/'.$trashName;$postCommitDeletes[]=$src;}$uq=$pdo->prepare('UPDATE file_registry SET relative_path=?,deleted_at=NOW() WHERE id=?');$uq->execute([$newRel,(int)$fr['id']]);}
   sync_account_transaction_mirror($pdo,$s);sync_cash_transaction_mirror($pdo,$s);audit('request_hard_delete','request',(string)$rid,['request_no'=>$no,'removed_account_entries'=>$removedEntries,'removed_cash_rows'=>count($removedCash['rows']),'removed_journals'=>count($journalIds),'soft_deleted_files'=>count($frows)]);
 }
 elseif($action==='status_change'){
   $rid=(int)$in['requestId'];$status=(string)$in['status'];$reason=trim((string)($in['reason']??''));$allowed=['draft','collecting','ready','quote','sent','won','lost','cancelled'];if(!in_array($status,$allowed,true))throw new RuntimeException('Geçersiz durum.');if(in_array($status,['lost','cancelled'],true)&&$reason==='')throw new RuntimeException('Kaybedildi/İptal Edildi için neden zorunludur.');$r=&find_request($s,$rid);if(!$r)throw new RuntimeException('Talep bulunamadı.');$old=canonical_request_status((string)($r['status']??'draft'));$r['status']=$old;
   if($status==='won'&&empty($r['customerQuote']))throw new RuntimeException('Kazanıldı durumuna geçmek için önce müşteri teklifi oluşturulmalı.');
   $o=&find_order_by_request($s,$rid);$preWin=['draft','collecting','ready','quote','sent'];
   if($old==='won'&&in_array($status,$preWin,true)&&$o&&!empty($o['open'])&&empty($o['financialCancelled'])){$why=order_real_activity_reason($r,$o);if($why!=='')throw new RuntimeException('Kazanıldı durumundan '.($status==='collecting'?'Fiyat Toplanıyor':$status).' durumuna dönülemez; '.$why.'. Önce ilgili gerçek finans/teslimat işlemini Sil ile kaldırın veya talebi İptal/Kaybedildi olarak kapatın.');unwind_win_commitments($s,$r,$o,'Kazanım geri alındı');}
   $r['status']=$status;$r['statusHistory']=$r['statusHistory']??[];$r['statusHistory'][]=['date'=>date('c'),'from'=>$old,'to'=>$status,'reason'=>$reason,'user'=>$u['name']];
   if($reason)$r['statusReason']=$reason;elseif(!in_array($status,['lost','cancelled'],true))unset($r['statusReason']);
   if($status==='won'){$r['wonAt']=$r['wonAt']??date('c');if(empty($r['docsDueDate']))$r['docsDueDate']=date('Y-m-d',strtotime('+3 days'));unset($r['lostAt'],$r['cancelledAt']);}
   elseif($status==='lost'){$r['lostAt']=date('c');unset($r['cancelledAt']);}
   elseif($status==='cancelled'){$r['cancelledAt']=date('c');unset($r['lostAt']);}
   else{unset($r['lostAt'],$r['cancelledAt']);}
   if(!empty($r['customerQuote']))$r['customerQuote']['status']=request_quote_status($status);
   $o=&find_order_by_request($s,$rid);
   if($status==='won'&&!$o&&!empty($r['customerQuote'])){
     $cq=$r['customerQuote'];if(empty($cq['items']))throw new RuntimeException('Kazanıldı için müşteri teklif kalemleri gerekli.');foreach($cq['items'] as $ci)if(empty($ci['supplierId']))throw new RuntimeException('Kazanıldı için tüm teklif kalemlerinde tedarikçi seçili olmalı.');$orderId=next_id($s['orders']);$year=date('Y');$order=['id'=>$orderId,'no'=>'ORD-'.$year.'-'.str_pad((string)$orderId,4,'0',STR_PAD_LEFT),'request'=>$r['no'],'requestId'=>$rid,'customer'=>$r['customer'],'currency'=>$r['currency']??'EUR','total'=>(float)($cq['total']??0),'open'=>true,'pos'=>[],'customerPaid'=>0,'customerDue'=>(float)($cq['total']??0),'receivableOpened'=>true,'paymentSchedule'=>payment_schedule_from_plan($r,(float)($cq['total']??0))];$by=[];foreach(($cq['items']??[]) as $it){$sid=(int)($it['supplierId']??0);$by[$sid][]=$it;}$allPos=[];foreach(($s['orders']??[]) as $oo)foreach(($oo['pos']??[]) as $pp)$allPos[]=$pp;$poId=next_id($allPos);$poSeq=$poId;
     foreach($by as $sid=>$items){$sup=null;foreach(($s['suppliers']??[]) as $ss)if((int)($ss['id']??0)===(int)$sid){$sup=$ss;break;}if(!$sup)continue;$poCur=supplier_currency($sup,$r);$pt=0;$poItems=[];foreach($items as $it){$baseCost=(float)($it['cost']??0);$fromCur=$r['currency']??'EUR';$fallbackCost=$fromCur===$poCur?$baseCost:fx_convert($baseCost,$fromCur,$poCur,supplier_fx($s,$sup));$raw=(float)($it['costOriginal']??$fallbackCost);$row=$it;$row['cost']=$raw;$row['costOriginal']=$raw;$row['costCurrency']=$poCur;$poItems[]=$row;$pt+=$raw*(float)($it['qty']??0);}$pt+=(float)($sup['freight']??0);$po=['id'=>$poId++,'no'=>'PO-'.$year.'-'.str_pad((string)$poSeq++,4,'0',STR_PAD_LEFT),'supplier'=>$sup['name'],'currency'=>$poCur,'fxSnapshot'=>$sup['fxSnapshot']??null,'status'=>'Hazırlanıyor','production'=>0,'total'=>$pt,'paid'=>0,'due'=>$pt,'payment'=>$sup['payment']??'','bank'=>$r['bank']??'','items'=>$poItems];$order['pos'][]=$po;$found=false;foreach($s['accounts'] as &$sa){if(($sa['type']??'')==='supplier'&&mb_strtoupper(trim((string)$sa['name']))===mb_strtoupper(trim((string)$sup['name']))&&($sa['currency']??'EUR')===$poCur){$sa['requestOpen']=(float)($sa['requestOpen']??0)-$pt;add_entry($sa,$r['no'],'Satın alma siparişi / tedarikçi borcu',0,$pt);$found=true;break;}}if(!$found){$nid=next_id($s['accounts']);$s['accounts'][]=['id'=>$nid,'partyKey'=>'pty-'.$nid,'type'=>'supplier','name'=>$sup['name'],'currency'=>$poCur,'total'=>-$pt,'request'=>$r['no'],'requestOpen'=>-$pt,'entries'=>[['txnId'=>'txn-'.bin2hex(random_bytes(6)),'date'=>date('Y-m-d'),'createdAt'=>date('c'),'createdBy'=>$u['name'],'ref'=>$r['no'],'desc'=>'Satın alma siparişi / tedarikçi borcu','debit'=>0,'credit'=>$pt]]];}}
     $s['orders'][]=$order;$o=&$s['orders'][array_key_last($s['orders'])];$ca=&customer_account($s,$r);$ca['requestOpen']=(float)($ca['requestOpen']??0)+(float)$order['total'];add_entry($ca,$r['no'],'Kazanılan teklif / müşteri alacağı',(float)$order['total'],0);audit('order_create_from_win','order',(string)$orderId,['request_id'=>$rid,'total'=>$order['total']]);
   }
   if($o){$total=(float)($r['customerQuote']['total']??$o['total']??0);if(in_array($status,['lost','cancelled'],true)&&empty($o['financialCancelled'])){$why=order_real_activity_reason($r,$o);$ca=&customer_account($s,$r);if(!empty($o['receivableOpened'])){$ca['requestOpen']=(float)($ca['requestOpen']??0)-$total;add_entry($ca,$r['no'],($status==='lost'?'Kaybedildi':'İptal edildi').' / müşteri alacağı kapatıldı',0,$total);}if($why===''){foreach(($o['pos']??[]) as $po){$amt=(float)($po['total']??0);$sa=&supplier_order_account($s,$po,$r);if($sa&&$amt>0){$sa['requestOpen']=(float)($sa['requestOpen']??0)+$amt;add_entry($sa,$r['no'],($status==='lost'?'Kaybedildi':'İptal edildi').' / tedarikçi borcu kapatıldı',$amt,0);}}}$o['financialCancelled']=true;$o['open']=false;$o['customerDue']=0;$o['refundDue']=(float)($o['customerPaid']??0);$o['closeActivityReason']=$why;}
     elseif($status==='won'&&!empty($o['financialCancelled'])){$why=order_real_activity_reason($r,$o);if($why!=='')throw new RuntimeException('Bu siparişte daha önce gerçek işlem var: '.$why.'. Yeniden Kazanıldı açmadan önce ilgili hareketleri kontrol edin.');reopen_win_commitments($s,$r,$o);}}
   audit('request_status_change','request',(string)$rid,['from'=>$old,'to'=>$status,'reason'=>$reason]);
 }
 else throw new RuntimeException('Bilinmeyen operasyon.');
 if($idem!==''){$iq=$pdo->prepare('INSERT INTO operation_idempotency(idempotency_key,action_type,created_by) VALUES(?,?,?)');$iq->execute([$idem,$action,(int)$u['id']]);}
 $rev=save_locked($pdo,$s,(int)$u['id'],(int)$row['revision']);save_module_states($pdo,$s,(int)$u['id']);sync_delivery_tables($pdo,$s,(int)$u['id']);sync_core_mirrors($pdo,$s);$pdo->commit();foreach($postCommitDeletes as $oldFile)@unlink($oldFile);$clientState=filter_state_for_user($s,$u);json_response(['ok'=>true,'state'=>$clientState,'keyHashes'=>state_hashes_for_client($s,$u),'revision'=>$rev]);
}catch(Throwable $e){
 if(isset($pdo)&&$pdo->inTransaction())$pdo->rollBack();
 $msg=$e instanceof RuntimeException ? trim($e->getMessage()) : '';
 if($msg==='')$msg=public_error($e,'Operasyon tamamlanamadı.');
 else error_log('ASAY operasyon doğrulama: '.$msg);
 json_response(['ok'=>false,'error'=>$msg],422);
}
