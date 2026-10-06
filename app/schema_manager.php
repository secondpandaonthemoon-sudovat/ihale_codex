<?php
declare(strict_types=1);

function asay_schema_tables(): array {
    return [
      'users','parties','app_state','audit_log','file_registry','requests','account_transactions',
      'cash_accounts','cash_transactions','finance_journal','entity_locks','request_items',
      'supplier_quotes','customer_quotes','sales_orders','purchase_orders','module_state',
      'operation_idempotency','commercial_documents','delivery_batches','delivery_batch_items',
      'login_attempts','schema_migrations'
    ];
}
function asay_schema_split_sql(string $sql): array {
    $out=[];$buf='';$len=strlen($sql);$quote=null;$line=false;$block=false;
    for($i=0;$i<$len;$i++){
        $c=$sql[$i];$n=$i+1<$len?$sql[$i+1]:'';
        if($line){$buf.=$c;if($c==="\n")$line=false;continue;}
        if($block){$buf.=$c;if($c==='*'&&$n==='/'){$buf.='/';$i++;$block=false;}continue;}
        if($quote!==null){$buf.=$c;if($c==='\\'&&$i+1<$len){$buf.=$sql[++$i];continue;}if($c===$quote){if(($quote==="'"||$quote==='"')&&$n===$quote){$buf.=$n;$i++;continue;}$quote=null;}continue;}
        if($c==='-'&&$n==='-'&&($i+2>=$len||ctype_space($sql[$i+2]))){$line=true;$buf.='--';$i++;continue;}
        if($c==='#'){$line=true;$buf.=$c;continue;}
        if($c==='/'&&$n==='*'){$block=true;$buf.='/*';$i++;continue;}
        if($c==="'"||$c==='"'||$c==='`'){$quote=$c;$buf.=$c;continue;}
        if($c===';'){$s=trim($buf);if($s!=='')$out[]=$s;$buf='';continue;}
        $buf.=$c;
    }
    if(trim($buf)!=='')$out[]=trim($buf);
    return $out;
}
function asay_schema_existing(PDO $pdo): array {
    $allow=array_flip(asay_schema_tables());$out=[];
    foreach($pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_NUM) as $r){$n=(string)$r[0];if(isset($allow[$n]))$out[]=$n;}
    return $out;
}
function asay_schema_drop(PDO $pdo): void {
    $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
    try{foreach(array_reverse(asay_schema_tables()) as $t)$pdo->exec('DROP TABLE IF EXISTS `'.$t.'`');}
    finally{$pdo->exec('SET FOREIGN_KEY_CHECKS=1');}
}
function asay_schema_apply(PDO $pdo,string $schemaFile): void {
    $sql=@file_get_contents($schemaFile);if($sql===false)throw new RuntimeException('Şema dosyası okunamadı: '.$schemaFile);
    $statements=asay_schema_split_sql($sql);if(!$statements)throw new RuntimeException('Şema dosyasında sorgu bulunamadı.');
    $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
    try{
        foreach($statements as $i=>$st){
            try{$pdo->exec($st);}catch(Throwable $e){
                $flat=substr(preg_replace('/\\s+/',' ',trim($st)),0,140);
                throw new RuntimeException('Şema kurulumu '.($i+1).'. adımda başarısız: '.$flat.' | '.$e->getMessage(),0,$e);
            }
        }
    } finally {$pdo->exec('SET FOREIGN_KEY_CHECKS=1');}
    foreach(['users','app_state','audit_log','parties','requests','login_attempts','schema_migrations'] as $must){
        $q=$pdo->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name=?");
        $q->execute([$must]);
        if((int)$q->fetchColumn()!==1)throw new RuntimeException('Kurulum sonrası zorunlu tablo oluşmadı: '.$must);
    }
}
function asay_schema_fresh(PDO $pdo,string $schemaFile): void {
    asay_schema_drop($pdo);asay_schema_apply($pdo,$schemaFile);
}
function asay_schema_clear_data(PDO $pdo): void {
    $existing=array_flip(asay_schema_existing($pdo));$pdo->exec('SET FOREIGN_KEY_CHECKS=0');
    try{foreach(array_reverse(asay_schema_tables()) as $t)if(isset($existing[$t]))$pdo->exec('TRUNCATE TABLE `'.$t.'`');}
    finally{$pdo->exec('SET FOREIGN_KEY_CHECKS=1');}
}
