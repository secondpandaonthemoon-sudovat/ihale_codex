<?php
declare(strict_types=1);
require_once __DIR__.'/app/bootstrap.php';
require_login();
$u=current_user();
if(!$u||!(user_is_admin($u)||has_app_write_permission('settings',$u))){http_response_code(403);exit('Admin veya Ayarlar yazma yetkisi gerekli.');}

define('TARGET_VERSION', ASAY_APP_BUILD);
$messages=[];$error='';$already=false;$csrf=csrf_token();

function migration_applied(PDO $pdo,string $version): bool {
    try{
        $pdo->exec("CREATE TABLE IF NOT EXISTS schema_migrations (
          version VARCHAR(40) PRIMARY KEY, applied_at DATETIME NOT NULL, applied_by BIGINT UNSIGNED NULL, note VARCHAR(255) NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $q=$pdo->prepare('SELECT COUNT(*) FROM schema_migrations WHERE version=?');$q->execute([$version]);
        return (int)$q->fetchColumn()>0;
    }catch(Throwable $e){return false;}
}
function cleanup_duplicate_parties(PDO $pdo): int {
    // Move mirror references from legacy NULL-key duplicates to keyed canonical parties.
    $pdo->exec("UPDATE requests r JOIN parties oldp ON r.party_id=oldp.id JOIN parties newp ON newp.party_key IS NOT NULL AND newp.party_type=oldp.party_type AND LOWER(TRIM(newp.name))=LOWER(TRIM(oldp.name)) SET r.party_id=newp.id WHERE oldp.party_key IS NULL");
    $pdo->exec("UPDATE account_transactions a JOIN parties oldp ON a.party_id=oldp.id JOIN parties newp ON newp.party_key IS NOT NULL AND newp.party_type=oldp.party_type AND LOWER(TRIM(newp.name))=LOWER(TRIM(oldp.name)) SET a.party_id=newp.id WHERE oldp.party_key IS NULL");
    return $pdo->exec("DELETE oldp FROM parties oldp JOIN parties newp ON newp.party_key IS NOT NULL AND newp.party_type=oldp.party_type AND LOWER(TRIM(newp.name))=LOWER(TRIM(oldp.name)) WHERE oldp.party_key IS NULL");
}
function upgrade_state(PDO $pdo): array {
    $raw=$pdo->query('SELECT state_json FROM app_state WHERE id=1')->fetchColumn();
    $st=$raw?json_decode((string)$raw,true):[];
    return is_array($st)?$st:[];
}
function migrate_role_write_flags(PDO $pdo,array &$st): int {
    $defaults=[
      'Admin'=>['request'=>true,'supplier'=>true,'cost'=>true,'finance'=>true,'cash'=>true,'docs'=>true,'settings'=>true],
      'Satış'=>['request'=>true,'supplier'=>false,'cost'=>false,'finance'=>false,'cash'=>false,'docs'=>true,'settings'=>false],
      'Satın Alma'=>['request'=>true,'supplier'=>true,'cost'=>true,'finance'=>false,'cash'=>false,'docs'=>false,'settings'=>false],
      'Finans'=>['request'=>false,'supplier'=>false,'cost'=>false,'finance'=>true,'cash'=>true,'docs'=>true,'settings'=>false],
      'Görüntüleyici'=>['request'=>false,'supplier'=>false,'cost'=>false,'finance'=>false,'cash'=>false,'docs'=>false,'settings'=>false],
    ];
    $changed=0;
    foreach(($st['roles']??[]) as &$role){$name=(string)($role['name']??'');foreach(['request','supplier','cost','finance','cash','docs','settings'] as $perm){$k=$perm.'Write';if(!array_key_exists($k,$role)){$role[$k]=!empty($defaults[$name][$perm]);$changed++;}}}unset($role);
    if($changed){$json=json_encode($st,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);$pdo->prepare('UPDATE app_state SET state_json=?,updated_by=?,revision=revision+1 WHERE id=1')->execute([$json,(int)($_SESSION['user_id']??0)]);save_module_states($pdo,$st,(int)($_SESSION['user_id']??0));}
    return $changed;
}
function refresh_request_items_from_state(PDO $pdo,array $st): int {
    $pdo->exec('DELETE FROM request_items');$count=0;
    $q=$pdo->prepare('INSERT INTO request_items(request_id,source_item_id,item_name,qty,unit,brand_model,specification,payload_json) VALUES(?,?,?,?,?,?,?,?)');
    foreach(($st['requests']??[]) as $r){
        $rid=(int)($r['id']??0);if($rid<=0)continue;
        foreach(($r['items']??[]) as $it){
            $sid=(int)($it['id']??0);if($sid<=0)continue;
            $q->execute([$rid,$sid,$it['name']??'Kalem',(float)($it['qty']??0),$it['unit']??null,$it['brandModel']??null,$it['spec']??null,json_encode($it,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)]);$count++;
        }
    }
    return $count;
}
function refresh_delivery_items_from_state(PDO $pdo,array $st): int {
    $pdo->exec('DELETE FROM delivery_batch_items');$count=0;
    $find=$pdo->prepare('SELECT id FROM request_items WHERE request_id=? AND source_item_id=? LIMIT 1');
    $ins=$pdo->prepare('INSERT INTO delivery_batch_items(batch_id,request_item_id,item_name,qty,unit,supplier_name,po_id,sale_total,cost_base,payload_json) VALUES(?,?,?,?,?,?,?,?,?,?)');
    foreach(($st['orders']??[]) as $o){
        $rid=(int)($o['requestId']??0);
        foreach(($o['deliveryBatches']??[]) as $b){
            $bid=(int)($b['id']??0);if($bid<=0)continue;
            foreach(($b['items']??[]) as $bi){
                $source=(int)($bi['itemId']??0);$mirror=null;
                if($rid>0&&$source>0){$find->execute([$rid,$source]);$mirror=$find->fetchColumn()?:null;}
                $ins->execute([$bid,$mirror,$bi['name']??'Kalem',(float)($bi['qty']??0),$bi['unit']??null,$bi['supplier']??null,(int)($bi['poId']??0)?:null,(float)($bi['saleTotal']??0),(float)($bi['costBase']??0),json_encode($bi,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)]);$count++;
            }
        }
    }
    return $count;
}
function refresh_supplier_totals_from_state(PDO $pdo,array $st): int {
    $requests=[];foreach(($st['requests']??[]) as $r)$requests[(int)($r['id']??0)]=$r;$count=0;
    $q=$pdo->prepare('INSERT INTO supplier_quotes(id,request_id,supplier_name,reference_no,currency,total,payload_json) VALUES(?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE request_id=VALUES(request_id),supplier_name=VALUES(supplier_name),reference_no=VALUES(reference_no),currency=VALUES(currency),total=VALUES(total),payload_json=VALUES(payload_json)');
    foreach(($st['suppliers']??[]) as $sq){
        $rid=(int)($sq['requestId']??0);$sid=(int)($sq['id']??0);if($rid<=0||$sid<=0)continue;$r=$requests[$rid]??[];$qty=[];
        foreach(($r['items']??[]) as $it)$qty[(string)($it['id']??0)]=(float)($it['qty']??0);
        $total=0.0;foreach(($sq['offers']??[]) as $iid=>$price)$total+=(float)$price*(float)($qty[(string)$iid]??0);
        $total+=(float)($sq['freight']??0);$cur=strtoupper((string)($sq['currency']??$r['currency']??'EUR'));if(!in_array($cur,['EUR','USD','TRY'],true))$cur='EUR';
        $q->execute([$sid,$rid,$sq['name']??'Tedarikçi',$sq['ref']??null,$cur,$total,json_encode($sq,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)]);$count++;
    }
    return $count;
}

function refresh_account_transactions_from_state(PDO $pdo,array $st): int {
    $pdo->exec('DELETE FROM account_transactions');
    $partyByKey=[];$partyByName=[];
    foreach($pdo->query('SELECT id,party_key,party_type,name FROM parties')->fetchAll(PDO::FETCH_ASSOC) as $p){
        if(!empty($p['party_key']))$partyByKey[(string)$p['party_key']]=(int)$p['id'];
        $partyByName[mb_strtoupper(trim((string)$p['name']),'UTF-8').'|'.(string)$p['party_type']]=(int)$p['id'];
    }
    $q=$pdo->prepare('INSERT INTO account_transactions(party_id,currency,txn_date,reference_no,description,debit,credit) VALUES(?,?,?,?,?,?,?)');$count=0;
    foreach(($st['accounts']??[]) as $a){
        $pid=$partyByKey[(string)($a['partyKey']??'')]??($partyByName[mb_strtoupper(trim((string)($a['name']??'')),'UTF-8').'|'.(string)($a['type']??'customer')]??null);
        foreach(($a['entries']??[]) as $e){$date=(string)($e['date']??date('Y-m-d'));if(!preg_match('/^\d{4}-\d{2}-\d{2}$/',$date))$date=date('Y-m-d');$q->execute([$pid,$a['currency']??'EUR',$date,$e['ref']??null,$e['desc']??'Cari Hareket',(float)($e['debit']??0),(float)($e['credit']??0)]);$count++;}
    }
    return $count;
}
function deleted_request_numbers_from_audit(PDO $pdo): array {
    $out=[];
    try{
        $q=$pdo->query("SELECT payload_json FROM audit_log WHERE action='request_hard_delete' ORDER BY id DESC");
        foreach($q->fetchAll(PDO::FETCH_COLUMN) as $raw){$x=json_decode((string)$raw,true);$no=trim((string)($x['request_no']??''));if($no!=='')$out[$no]=true;}
    }catch(Throwable $e){}
    return $out;
}
function repair_orphan_account_entries(PDO $pdo,array &$st): array {
    $activeReq=[];foreach(($st['requests']??[]) as $r){$no=trim((string)($r['no']??''));if($no!=='')$activeReq[$no]=true;}
    $activePo=[];foreach(($st['orders']??[]) as $o)foreach(($o['pos']??[]) as $po){$no=trim((string)($po['no']??''));if($no!=='')$activePo[$no]=true;}
    $deletedReq=deleted_request_numbers_from_audit($pdo);
    $prefix=preg_quote(trim((string)($st['requestNumber']['prefix']??'RFQ'))?:'RFQ','/');
    $knownFragments=['müşteri alacağı','tedarikçi borcu','müşteri tahsilatı','tedarikçi ödemesi'];
    $removed=0;$changedAccounts=0;
    foreach(($st['accounts']??[]) as &$a){
        $before=count($a['entries']??[]);$keep=[];
        foreach(($a['entries']??[]) as $e){
            $ref=trim((string)($e['ref']??''));$desc=mb_strtolower(trim((string)($e['desc']??'')),'UTF-8');
            $requestLike=$ref!==''&&(isset($deletedReq[$ref])||preg_match('/^(?:'.$prefix.'|REQ)-/iu',$ref));
            $poLike=$ref!==''&&preg_match('/^PO-\d{4}-\d+$/iu',$ref);
            $known=false;foreach($knownFragments as $frag)if(str_contains($desc,$frag)){$known=true;break;}
            $orphanRequest=$requestLike&&!isset($activeReq[$ref]);
            $orphanPo=$poLike&&!isset($activePo[$ref]);
            if(($orphanRequest||$orphanPo)&&($known||!empty($e['systemGenerated']))){$removed++;continue;}
            $keep[]=$e;
        }
        if(count($keep)!==$before){$a['entries']=$keep;$sum=0.0;foreach($keep as $e)$sum+=(float)($e['debit']??0)-(float)($e['credit']??0);$a['requestOpen']=round($sum,2);$changedAccounts++;}
        $rq=trim((string)($a['request']??''));if($rq!==''&&!isset($activeReq[$rq])&&(isset($deletedReq[$rq])||preg_match('/^(?:'.$prefix.'|REQ)-/iu',$rq)))$a['request']='';
    }unset($a);
    return ['removed'=>$removed,'accounts'=>$changedAccounts];
}
function repair_empty_business_residual_balances(PDO $pdo,array &$st): array {
    // V3.13.0/1 reset bug: reset loops iterated a temporary array expression, so
    // transaction mirrors were emptied while master-card balances could remain in app_state.
    // Only repair when the database proves there is no surviving financial/operational ledger.
    $tableCount=function(string $table)use($pdo): int {try{return (int)$pdo->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();}catch(Throwable $e){return 0;}};
    $stateOperational=count($st['requests']??[])+count($st['orders']??[])+count($st['suppliers']??[])+count($st['cash']??[]);
    $ledgerCount=$tableCount('finance_journal')+$tableCount('account_transactions')+$tableCount('cash_transactions')+$tableCount('requests')+$tableCount('sales_orders')+$tableCount('purchase_orders');
    if($stateOperational!==0||$ledgerCount!==0)return ['eligible'=>false,'accounts'=>0,'cashAccounts'=>0];
    $acChanged=0;$cashChanged=0;
    if(!isset($st['accounts'])||!is_array($st['accounts']))$st['accounts']=[];
    foreach($st['accounts'] as &$a){
        $dirty=!empty($a['entries'])||abs((float)($a['requestOpen']??0))>0.001||abs((float)($a['total']??0))>0.001||trim((string)($a['request']??''))!==''||abs((float)($a['balance']??0))>0.001;
        if($dirty)$acChanged++;
        $a['entries']=[];$a['requestOpen']=0;$a['total']=0;$a['request']='';if(array_key_exists('balance',$a))$a['balance']=0;if(array_key_exists('dueDate',$a))$a['dueDate']='';
    }unset($a);
    if(!isset($st['cashAccounts'])||!is_array($st['cashAccounts']))$st['cashAccounts']=[];
    foreach($st['cashAccounts'] as &$a){$dirty=abs((float)($a['balance']??0))>0.001||abs((float)($a['openingBalance']??0))>0.001;if($dirty)$cashChanged++;$a['balance']=0;if(array_key_exists('openingBalance',$a))$a['openingBalance']=0;}unset($a);
    if($acChanged||$cashChanged){
        $st['_storage']=['canonical'=>'app_state','mirrors'=>'normalized_tables','schema'=>'v3.13.4','updatedAt'=>date('c'),'residualBalanceRepairAt'=>date('c')];
        $json=json_encode($st,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);if($json===false)throw new RuntimeException('Sıfırlama bakiye onarım state JSON oluşturulamadı.');
        $pdo->prepare('UPDATE app_state SET state_json=?,updated_by=?,revision=revision+1 WHERE id=1')->execute([$json,(int)($_SESSION['user_id']??0)]);
        save_module_states($pdo,$st,(int)($_SESSION['user_id']??0));
        $pdo->exec('DELETE FROM cash_accounts');
        $q=$pdo->prepare('INSERT INTO cash_accounts(id,name,account_type,currency,balance,iban,swift,payload_json) VALUES(?,?,?,?,0,?,?,?)');
        foreach($st['cashAccounts'] as $a)$q->execute([(int)($a['id']??0),$a['name']??'Hesap',$a['type']??'Banka',$a['currency']??'EUR',$a['iban']??null,$a['swift']??null,json_encode($a,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)]);
    }
    return ['eligible'=>true,'accounts'=>$acChanged,'cashAccounts'=>$cashChanged];
}
function reconcile_order_payment_tracking(PDO $pdo,array &$st): array {
    $customerKinds=['customer_prepayment'=>true,'generic_customer_receipt'=>true,'delivery_batch_collection'=>true];
    $supplierKinds=['generic_supplier_payment'=>true,'delivery_batch_supplier_payment'=>true];
    $cashByOrder=[];
    foreach(($st['cash']??[]) as $c){
        if(!empty($c['reversed'])||($c['kind']??'')==='reversal')continue;
        $oid=(int)($c['orderId']??0);if($oid<=0)continue;$cashByOrder[$oid][]=$c;
    }
    $ordersChanged=0;$customerRows=0;$supplierRows=0;
    foreach(($st['orders']??[]) as &$o){
        $oid=(int)($o['id']??0);$rows=$cashByOrder[$oid]??[];$total=round((float)($o['total']??0),2);
        $customerCash=0.0;$customerMatches=0;
        foreach($rows as $c){$k=(string)($c['kind']??'');if(!isset($customerKinds[$k]))continue;$customerMatches++;$customerCash+=round((float)($c['orderAmount']??$c['amountValue']??$c['amount']??0),2);}
        $customerPaid=$customerMatches>0?round($customerCash,2):round((float)($o['customerPaid']??0),2);
        $customerPaid=max(0,min($customerPaid,$total));
        $before=json_encode([$o['customerPaid']??0,$o['customerDue']??0,$o['paymentSchedule']??[],$o['pos']??[]],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        $o['customerPaid']=$customerPaid;
        if(!empty($o['financialCancelled'])){$o['customerDue']=0;$o['refundDue']=$customerPaid;}else{$o['customerDue']=max(0,round($total-$customerPaid,2));}
        if(!empty($o['paymentSchedule'])&&is_array($o['paymentSchedule'])){
            foreach($o['paymentSchedule'] as &$stg){$amt=round((float)($stg['amount']??0),2);$stg['paid']=0.0;$stg['due']=$amt;$stg['status']='Bekliyor';}unset($stg);
            $left=$customerPaid;
            foreach($o['paymentSchedule'] as &$stg){if($left<=0.001)break;$amt=max(0,round((float)($stg['amount']??0),2));$x=min($amt,$left);$stg['paid']=round($x,2);$stg['due']=max(0,round($amt-$x,2));$stg['status']=$stg['due']<=0.001?'Tamamlandı':($stg['paid']>0?'Kısmi':'Bekliyor');$left=round($left-$x,2);}unset($stg);
        }
        $customerRows+=$customerMatches;
        foreach(($o['pos']??[]) as &$po){
            $pid=(int)($po['id']??0);$ptotal=round((float)($po['total']??0),2);$sum=0.0;$matches=0;
            foreach($rows as $c){$k=(string)($c['kind']??'');if(!isset($supplierKinds[$k])||(int)($c['poId']??0)!==$pid)continue;$matches++;$sum+=round((float)($c['orderAmount']??$c['amountValue']??$c['amount']??0),2);}
            $paid=$matches>0?round($sum,2):round((float)($po['paid']??0),2);$paid=max(0,min($paid,$ptotal));$po['paid']=$paid;$po['due']=max(0,round($ptotal-$paid,2));$supplierRows+=$matches;
        }unset($po);
        $after=json_encode([$o['customerPaid']??0,$o['customerDue']??0,$o['paymentSchedule']??[],$o['pos']??[]],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        if($before!==$after)$ordersChanged++;
    }unset($o);
    if($ordersChanged){
        $st['_storage']=array_merge(is_array($st['_storage']??null)?$st['_storage']:[],['schema'=>'v3.13.4','paymentTrackingReconciledAt'=>date('c')]);
        $json=json_encode($st,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);if($json===false)throw new RuntimeException('Ödeme takibi mutabakat state JSON oluşturulamadı.');
        $pdo->prepare('UPDATE app_state SET state_json=?,updated_by=?,revision=revision+1 WHERE id=1')->execute([$json,(int)($_SESSION['user_id']??0)]);save_module_states($pdo,$st,(int)($_SESSION['user_id']??0));
    }
    return ['orders'=>$ordersChanged,'customerRows'=>$customerRows,'supplierRows'=>$supplierRows];
}
function request_items_needs_v2(PDO $pdo): bool {
    $q=$pdo->query("SHOW COLUMNS FROM request_items LIKE 'source_item_id'");
    return !$q->fetch();
}
function rebuild_request_items_v2(PDO $pdo): void {
    // request_items is a derived mirror. Recreate safely; app_state remains canonical.
    $pdo->exec('DROP TABLE IF EXISTS request_items');
    $pdo->exec("CREATE TABLE request_items (
      id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      request_id BIGINT UNSIGNED NOT NULL,
      source_item_id BIGINT UNSIGNED NOT NULL,
      item_name VARCHAR(255) NOT NULL,
      qty DECIMAL(18,4) NOT NULL DEFAULT 0,
      unit VARCHAR(40) NULL,
      brand_model VARCHAR(190) NULL,
      specification TEXT NULL,
      payload_json LONGTEXT NULL,
      UNIQUE KEY uq_request_source_item(request_id,source_item_id),
      INDEX idx_req_item_request(request_id),
      INDEX idx_req_item_source(source_item_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}
$pdo=db();$already=migration_applied($pdo,TARGET_VERSION);

if($_SERVER['REQUEST_METHOD']==='POST'){
    try{
        require_csrf((string)($_POST['_csrf']??''));
        $pdo=db();
        $sql=file_get_contents(__DIR__.'/database/schema.sql');
        if($sql===false)throw new RuntimeException('database/schema.sql okunamadı.');
        $pdo->exec($sql);

        // Existing V3.3.x request_items used a globally unique id that clashes across requests.
        if(request_items_needs_v2($pdo)){rebuild_request_items_v2($pdo);$messages[]='Talep kalem mirror tablosu composite kimlik yapısına geçirildi.';}

        // Common legacy columns/indexes.
        $alters=[
          "ALTER TABLE parties MODIFY COLUMN party_type ENUM('customer','supplier','public','private','customs','freight','service') NOT NULL",
          "ALTER TABLE parties ADD COLUMN party_key VARCHAR(80) NULL AFTER party_type",
          "ALTER TABLE parties ADD UNIQUE KEY uq_party_key(party_key)",
          "ALTER TABLE users ADD COLUMN department VARCHAR(100) NULL AFTER role",
          "ALTER TABLE users ADD COLUMN note VARCHAR(255) NULL AFTER department",
          "ALTER TABLE users ADD COLUMN photo_data LONGTEXT NULL AFTER note",
          "ALTER TABLE app_state ADD COLUMN revision BIGINT UNSIGNED NOT NULL DEFAULT 0 AFTER updated_by"
        ];
        foreach($alters as $q){try{$pdo->exec($q);}catch(PDOException $e){$code=(int)($e->errorInfo[1]??0);if(!in_array($code,[1060,1061],true))throw $e;}}

        $deleted=cleanup_duplicate_parties($pdo);
        if($deleted>0)$messages[]=$deleted.' eski/çift cari mirror kaydı temizlendi.';

        $st=upgrade_state($pdo);
        $paymentRepair=reconcile_order_payment_tracking($pdo,$st);
        if(($paymentRepair['orders']??0)>0)$messages[]=$paymentRepair['orders'].' siparişin müşteri ödeme planı ve tedarikçi ödeme toplamları gerçek finans hareketleriyle mutabık hale getirildi.';
        $resetBalanceRepair=repair_empty_business_residual_balances($pdo,$st);
        if(($resetBalanceRepair['accounts']??0)>0||($resetBalanceRepair['cashAccounts']??0)>0)$messages[]='Sıfırlamadan kalan eski bakiyeler temizlendi: '.(int)$resetBalanceRepair['accounts'].' cari alt hesap · '.(int)$resetBalanceRepair['cashAccounts'].' kasa/banka hesabı.';
        $orphanRepair=repair_orphan_account_entries($pdo,$st);
        if(($orphanRepair['removed']??0)>0){$json=json_encode($st,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);$pdo->prepare('UPDATE app_state SET state_json=?,updated_by=?,revision=revision+1 WHERE id=1')->execute([$json,(int)$u['id']]);save_module_states($pdo,$st,(int)$u['id']);$messages[]=$orphanRepair['removed'].' yetim cari hareketi temizlendi; '.$orphanRepair['accounts'].' cari bakiye yeniden hesaplandı.';}
        $roleFlags=migrate_role_write_flags($pdo,$st);if($roleFlags>0)$messages[]=$roleFlags.' eski rol yazma yetkisi güncel güvenli alanlara dönüştürüldü.';
        $itemCount=refresh_request_items_from_state($pdo,$st);$messages[]=$itemCount.' talep kalemi composite mirror yapısına senkronlandı.';
        $deliveryItemCount=refresh_delivery_items_from_state($pdo,$st);$messages[]=$deliveryItemCount.' teslimat kalemi yeni request-item mirror kimliklerine bağlandı.';
        $supplierCount=refresh_supplier_totals_from_state($pdo,$st);$messages[]=$supplierCount.' tedarikçi teklif mirror toplamı miktar × fiyat + navlun olarak yenilendi.';
        $accountTxnCount=refresh_account_transactions_from_state($pdo,$st);$messages[]=$accountTxnCount.' cari hareket mirror kaydı canonical state ile yeniden oluşturuldu.';

        $q=$pdo->prepare('INSERT INTO schema_migrations(version,applied_at,applied_by,note) VALUES(?,NOW(),?,?) ON DUPLICATE KEY UPDATE note=VALUES(note)');
        $q->execute([TARGET_VERSION,(int)$u['id'],ASAY_APP_BUILD.' şema, güvenlik, timezone, runtime ve bakım stabilizasyonu']);
        if($pdo->inTransaction())$pdo->commit();
        audit('schema_upgrade','database',TARGET_VERSION,['duplicate_parties_deleted'=>$deleted,'role_write_flags'=>$roleFlags,'request_items'=>$itemCount,'delivery_items'=>$deliveryItemCount,'supplier_quotes'=>$supplierCount]);
        $messages[]=ASAY_APP_BUILD.' güncellemesi başarıyla uygulandı.';
        $already=true;
    }catch(Throwable $e){
        if(isset($pdo)&&$pdo->inTransaction())$pdo->rollBack();
        $error=app_debug_enabled()?$e->getMessage():'Güncelleme tamamlanamadı. Ayrıntı için ASAY_DEBUG modunu kullanın veya sunucu loglarını kontrol edin.';
    }
}
?><!doctype html>
<html lang="tr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>ASAY <?=htmlspecialchars(ASAY_APP_BUILD)?> Güncelleme</title>
<style>body{font-family:Arial;background:#f4f7fb;display:grid;place-items:center;min-height:100vh;color:#172033}.box{width:min(760px,92vw);background:#fff;padding:28px;border-radius:16px;border:1px solid #ddd}.btn{padding:12px 16px;border:0;border-radius:8px;background:#163d73;color:#fff;font-weight:700}.btn:disabled{opacity:.45}.ok{background:#eaf8ef;padding:10px;color:#16733c;border-radius:8px;margin:7px 0}.err{background:#fff0f0;padding:10px;color:#b42318;border-radius:8px}.note{background:#f8fafc;padding:11px;border-radius:8px;color:#667085}</style>
</head><body><div class="box"><h1>ASAY ERP <?=htmlspecialchars(ASAY_APP_BUILD)?> Güncellemesi</h1>
<p>Veri bütünlüğü, yetkilendirme, güvenlik, yedekleme, hosting uyumluluğu ve normalize SQL mirror yapısını günceller.</p>
<?php if($error):?><div class="err"><?=htmlspecialchars($error)?></div><?php endif;?>
<?php foreach($messages as $m):?><div class="ok"><?=htmlspecialchars($m)?></div><?php endforeach;?>
<?php if($already && $_SERVER['REQUEST_METHOD']!=='POST'):?><div class="ok"><?=htmlspecialchars(ASAY_APP_BUILD)?> güncellemesi bu veritabanına daha önce uygulanmış. Tekrar çalıştırmanız gerekmiyor.</div><?php endif;?>
<form method="post" style="margin-top:18px"><input type="hidden" name="_csrf" value="<?=htmlspecialchars($csrf)?>"><button class="btn" <?=$already?'disabled':''?>><?=$already?'Güncelleme Zaten Uygulandı':htmlspecialchars(ASAY_APP_BUILD).' Güncellemesini Uygula'?></button></form>
<p class="note">Güncelleme öncesinde Ayarlar → Veritabanı bölümünden yedek almanız önerilir.</p>
<p><a href="index.php">← Uygulamaya dön</a></p></div></body></html>
