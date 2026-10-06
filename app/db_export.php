<?php
declare(strict_types=1);

function asay_db_table_whitelist(): array {
    return [
      'users','parties','app_state','audit_log','file_registry','requests','account_transactions',
      'cash_accounts','cash_transactions','finance_journal','entity_locks','request_items',
      'supplier_quotes','customer_quotes','sales_orders','purchase_orders','module_state',
      'operation_idempotency','commercial_documents','delivery_batches','delivery_batch_items',
      'login_attempts','schema_migrations'
    ];
}
function asay_existing_tables(PDO $pdo): array {
    $allowed=array_flip(asay_db_table_whitelist());$out=[];
    foreach($pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_NUM) as $r){
        $n=(string)$r[0];if(isset($allowed[$n]))$out[]=$n;
    }
    return $out;
}
function asay_sql_literal(PDO $pdo,mixed $v): string {
    if($v===null)return 'NULL';
    if(is_int($v)||is_float($v))return (string)$v;
    return $pdo->quote((string)$v);
}
function asay_write_sql_backup(PDO $pdo,string $path,string $appVersion='ASAY ERP'): array {
    $fh=@fopen($path,'wb');if(!$fh)throw new RuntimeException('SQL yedek dosyası oluşturulamadı.');
    $existing=array_flip(asay_existing_tables($pdo));$count=0;$rowsTotal=0;
    $w=function(string $s='')use($fh){if(fwrite($fh,$s."\n")===false)throw new RuntimeException('SQL yedeği yazılamadı.');};
    try{
        $w('-- ASAY ERP MySQL / MariaDB SQL Backup');
        $w('-- App Version: '.$appVersion);
        $w('-- Created At: '.date('c'));
        $w('-- Database name intentionally omitted for portability.');
        $w('SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";');
        $w('SET FOREIGN_KEY_CHECKS=0;');
        $w('SET UNIQUE_CHECKS=0;');
        $w('SET NAMES utf8mb4;');
        $w('START TRANSACTION;');$w();
        foreach(array_reverse(asay_db_table_whitelist()) as $name)if(isset($existing[$name]))$w('DROP TABLE IF EXISTS `'.$name.'`;');
        $w();
        foreach(asay_db_table_whitelist() as $name){
            if(!isset($existing[$name]))continue;$count++;
            $q=$pdo->query('SHOW CREATE TABLE `'.$name.'`')->fetch(PDO::FETCH_NUM);
            if(!$q||empty($q[1]))throw new RuntimeException('Tablo şeması okunamadı: '.$name);
            $w('-- --------------------------------------------------------');$w('-- Table structure for `'.$name.'`');$w((string)$q[1].';');$w();
            $st=$pdo->query('SELECT * FROM `'.$name.'`');$chunk=[];$cols=null;
            while($row=$st->fetch(PDO::FETCH_ASSOC)){
                $rowsTotal++;
                if($cols===null)$cols=array_keys($row);
                $vals=[];foreach($cols as $c)$vals[]=asay_sql_literal($pdo,$row[$c]??null);
                $chunk[]='('.implode(',',$vals).')';
                if(count($chunk)>=100){
                    $colSql=implode(',',array_map(fn($c)=>'`'.str_replace('`','``',(string)$c).'`',$cols));
                    $w('INSERT INTO `'.$name.'` ('.$colSql.') VALUES');$w(implode(",\n",$chunk).';');$w();$chunk=[];
                }
            }
            if($chunk&&$cols!==null){
                $colSql=implode(',',array_map(fn($c)=>'`'.str_replace('`','``',(string)$c).'`',$cols));
                $w('INSERT INTO `'.$name.'` ('.$colSql.') VALUES');$w(implode(",\n",$chunk).';');$w();
            }
        }
        $w('COMMIT;');$w('SET UNIQUE_CHECKS=1;');$w('SET FOREIGN_KEY_CHECKS=1;');
    } finally { fclose($fh); }
    return ['tables'=>$count,'rows'=>$rowsTotal,'bytes'=>is_file($path)?filesize($path):0];
}
