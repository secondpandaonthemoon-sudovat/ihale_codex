<?php
declare(strict_types=1);
require_once __DIR__.'/../app/bootstrap.php';
require_once __DIR__.'/../app/simple_zip.php';
require_once __DIR__.'/../app/db_export.php';
require_once __DIR__.'/../app/schema_manager.php';
require_login();
$u=require_admin();

const ASAY_DB_BACKUP_FORMAT='ASAY_ERP_DB_BACKUP';
const ASAY_DB_BACKUP_VERSION=2;
const ASAY_MAX_BACKUP_BYTES=104857600; // 100 MB restore upload cap

function dbm_tables(): array { return asay_db_table_whitelist(); }
function dbm_core_tables(): array { return ['users','app_state','parties','requests']; }
function dbm_csrf(): void { require_csrf((string)($_SERVER['HTTP_X_ASAY_CSRF']??'')); }

function dbm_existing_tables(PDO $pdo): array { return asay_existing_tables($pdo); }

function dbm_write_json_backup_file(PDO $pdo,string $path): array {
    $fh=@fopen($path,'wb');if(!$fh)throw new RuntimeException('Yedek dosyası oluşturulamadı.');
    $existing=array_values(dbm_existing_tables($pdo));$tableSet=array_flip($existing);$totalRows=0;
    $write=function(string $s)use($fh){if(fwrite($fh,$s)===false)throw new RuntimeException('Yedek dosyası yazılamadı.');};
    try{
        $meta=[
          'format'=>ASAY_DB_BACKUP_FORMAT,'formatVersion'=>ASAY_DB_BACKUP_VERSION,'appVersion'=>ASAY_APP_BUILD,
          'createdAt'=>date('c'),'database'=>(string)(db_config()['database']??''),'tableOrder'=>$existing
        ];
        $prefix=json_encode($meta,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_INVALID_UTF8_SUBSTITUTE);
        if($prefix===false)throw new RuntimeException('Yedek üst bilgisi oluşturulamadı.');
        $prefix=substr($prefix,0,-1).',"tables":{';
        $write($prefix);$firstTable=true;
        foreach(dbm_tables() as $name){
            if(!isset($tableSet[$name]))continue;
            $q=$pdo->query('SHOW CREATE TABLE `'.$name.'`')->fetch(PDO::FETCH_NUM);
            if(!$q||empty($q[1]))throw new RuntimeException('Tablo şeması okunamadı: '.$name);
            if(!$firstTable)$write(',');$firstTable=false;
            $write(json_encode($name).':{"createSql":'.json_encode((string)$q[1],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).',"rows":[');
            $st=$pdo->query('SELECT * FROM `'.$name.'`');$firstRow=true;$rowCount=0;
            while($row=$st->fetch(PDO::FETCH_ASSOC)){
                $j=json_encode($row,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_INVALID_UTF8_SUBSTITUTE);
                if($j===false)throw new RuntimeException('Yedek satırı JSON formatına çevrilemedi: '.$name);
                if(!$firstRow)$write(',');$firstRow=false;$write($j);$rowCount++;$totalRows++;
            }
            $write('],"rowCount":'.$rowCount.'}');
        }
        $write('}}');
    } finally { fclose($fh); }
    return ['tables'=>count($existing),'rows'=>$totalRows,'bytes'=>is_file($path)?filesize($path):0];
}
function dbm_json_backup_temp(PDO $pdo): array {
    $path=tempnam(sys_get_temp_dir(),'asaydb_');if($path===false)throw new RuntimeException('Geçici yedek dosyası oluşturulamadı.');
    $meta=dbm_write_json_backup_file($pdo,$path);return ['path'=>$path,'meta'=>$meta];
}
function dbm_backup_dir(): string {
    $dir=__DIR__.'/../storage/db-backups';
    if(!is_dir($dir)&&!mkdir($dir,0750,true)&&!is_dir($dir))throw new RuntimeException('Sunucu güvenlik yedeği klasörü oluşturulamadı.');
    return $dir;
}
function dbm_prune_safety(int $keep=10): void {
    $dir=dbm_backup_dir();$files=glob($dir.'/pre_*.asaydb.json')?:[];
    usort($files,fn($a,$b)=>(filemtime($b)?:0)<=>(filemtime($a)?:0));
    foreach(array_slice($files,$keep) as $old)@unlink($old);
}
function dbm_write_safety_backup(PDO $pdo,string $prefix='pre_restore'): array {
    $dir=dbm_backup_dir();$name=$prefix.'_'.date('Ymd_His').'_'.bin2hex(random_bytes(3)).'.asaydb.json';$path=$dir.'/'.$name;
    $meta=dbm_write_json_backup_file($pdo,$path);@chmod($path,0640);dbm_prune_safety(10);
    return ['path'=>$path,'name'=>$name,'size'=>(int)filesize($path),'createdAt'=>date('d.m.Y H:i:s',filemtime($path)),'meta'=>$meta];
}
function dbm_latest_safety(): ?array {
    $dir=dbm_backup_dir();$files=glob($dir.'/pre_*.asaydb.json')?:[];$files=array_values(array_filter($files,fn($p)=>!str_ends_with($p,'.restored')));
    if(!$files)return null;usort($files,fn($a,$b)=>(filemtime($b)?:0)<=>(filemtime($a)?:0));$p=$files[0];
    return ['path'=>$p,'name'=>basename($p),'size'=>(int)filesize($p),'createdAt'=>date('d.m.Y H:i:s',filemtime($p))];
}
function dbm_validate_json_array(array $data): array {
    if(($data['format']??'')!==ASAY_DB_BACKUP_FORMAT)throw new RuntimeException('Bu dosya ASAY ERP güvenli veritabanı yedeği değil.');
    if((int)($data['formatVersion']??0)<1)throw new RuntimeException('Yedek format sürümü desteklenmiyor.');
    $tables=(array)($data['tables']??[]);if(!$tables)throw new RuntimeException('Yedekte tablo verisi bulunamadı.');
    $allowed=array_flip(dbm_tables());
    foreach(array_keys($tables) as $name)if(!isset($allowed[$name]))throw new RuntimeException('Yedekte izin verilmeyen tablo var: '.$name);
    foreach(dbm_core_tables() as $name)if(!isset($tables[$name]))throw new RuntimeException('Zorunlu ASAY ERP tablosu eksik: '.$name);
    foreach($tables as $name=>$def){
        if(empty($def['createSql'])||!is_array($def['rows']??null))throw new RuntimeException('Yedek tablo yapısı bozuk: '.$name);
        if(!preg_match('/^\s*CREATE\s+TABLE\s+`?'.preg_quote($name,'/').'`?/i',(string)$def['createSql']))throw new RuntimeException('Tablo şeması adı eşleşmiyor: '.$name);
    }
    return ['type'=>'ASAY Güvenli Yedek','tableCount'=>count($tables),'sourceVersion'=>(string)($data['appVersion']??'Bilinmiyor'),'createdAt'=>(string)($data['createdAt']??'')];
}
function dbm_read_uploaded(): array {
    if(empty($_FILES['backup'])||!is_uploaded_file($_FILES['backup']['tmp_name']))throw new RuntimeException('Yedek dosyası alınamadı.');
    $f=$_FILES['backup'];$size=(int)($f['size']??0);$tmp=(string)($f['tmp_name']??'');
    if($size<=0)throw new RuntimeException('Yedek dosyası boş.');
    if($size>ASAY_MAX_BACKUP_BYTES)throw new RuntimeException('Yedek dosyası 100 MB uygulama sınırını aşıyor.');
    $fh=@fopen($tmp,'rb');if(!$fh)throw new RuntimeException('Yedek dosyası açılamadı.');
    try{$magic=fread($fh,4);}finally{fclose($fh);}
    if($magic==="PK\x03\x04"){
        return ['name'=>(string)($f['name']??'backup'),'raw'=>'','zipPath'=>$tmp,'size'=>$size];
    }
    $raw=file_get_contents($tmp);if($raw===false)throw new RuntimeException('Yedek dosyası okunamadı.');
    return ['name'=>(string)($f['name']??'backup'),'raw'=>$raw,'zipPath'=>'','size'=>$size];
}
function dbm_strip_leading_sql_comments(string $s): string {
    $s=ltrim($s);
    while(true){
        if(str_starts_with($s,'--')){$p=strpos($s,"\n");$s=$p===false?'':ltrim(substr($s,$p+1));continue;}
        if(str_starts_with($s,'#')){$p=strpos($s,"\n");$s=$p===false?'':ltrim(substr($s,$p+1));continue;}
        if(str_starts_with($s,'/*')&&!str_starts_with($s,'/*!')){$p=strpos($s,'*/');if($p===false)return '';$s=ltrim(substr($s,$p+2));continue;}
        break;
    }
    return $s;
}
function dbm_split_sql(string $sql): array {
    $out=[];$buf='';$len=strlen($sql);$quote=null;$lineComment=false;$blockComment=false;
    for($i=0;$i<$len;$i++){
        $c=$sql[$i];$n=$i+1<$len?$sql[$i+1]:'';
        if($lineComment){$buf.=$c;if($c==="\n")$lineComment=false;continue;}
        if($blockComment){$buf.=$c;if($c==='*'&&$n==='/'){$buf.='/';$i++;$blockComment=false;}continue;}
        if($quote!==null){$buf.=$c;if($c==='\\'&&$i+1<$len){$buf.=$sql[++$i];continue;}if($c===$quote){if(($quote==="'"||$quote==='"')&&$n===$quote){$buf.=$n;$i++;continue;}$quote=null;}continue;}
        if($c==='-'&&$n==='-'&&($i+2>=$len||ctype_space($sql[$i+2]))){$lineComment=true;$buf.='--';$i++;continue;}
        if($c==='#'){$lineComment=true;$buf.=$c;continue;}
        if($c==='/'&&$n==='*'){$blockComment=true;$buf.='/*';$i++;continue;}
        if($c==="'"||$c==='"'||$c==='`'){$quote=$c;$buf.=$c;continue;}
        if($c===';'){$st=trim($buf);if($st!=='')$out[]=$st;$buf='';continue;}$buf.=$c;
    }
    if(trim($buf)!=='')$out[]=trim($buf);return $out;
}
function dbm_sql_statement_table(string $s): ?string {
    $x=dbm_strip_leading_sql_comments($s);
    if(preg_match('/^(?:CREATE\s+TABLE(?:\s+IF\s+NOT\s+EXISTS)?|ALTER\s+TABLE|INSERT\s+INTO|DROP\s+TABLE(?:\s+IF\s+EXISTS)?|TRUNCATE\s+TABLE)\s+`?([A-Za-z0-9_]+)`?/i',$x,$m))return $m[1];
    return null;
}
function dbm_validate_sql(string $sql): array {
    $allowed=array_flip(dbm_tables());$seen=[];$statements=dbm_split_sql($sql);
    if(!$statements)throw new RuntimeException('SQL dosyasında çalıştırılabilir sorgu bulunamadı.');
    foreach($statements as $st){
        $x=dbm_strip_leading_sql_comments($st);if($x==='')continue;
        // Security is checked on parsed statement starts, never on data strings.
        if(preg_match('/^(CREATE|ALTER|DROP)\s+(DATABASE|USER|TRIGGER|EVENT|PROCEDURE|FUNCTION)\b/i',$x,$m))throw new RuntimeException('SQL dosyasında izin verilmeyen komut bulundu: '.$m[0]);
        if(preg_match('/^(GRANT|REVOKE|LOAD\s+DATA)\b/i',$x,$m)||preg_match('/\bINTO\s+OUTFILE\b/i',$x))throw new RuntimeException('SQL dosyasında izin verilmeyen yönetim/dosya komutu bulundu.');
        if(preg_match('/^USE\b/i',$x))throw new RuntimeException('SQL dosyasında USE komutu bulunamaz; hedef DB uygulama tarafından belirlenir.');
        $table=dbm_sql_statement_table($st);
        if($table!==null){
            if(!isset($allowed[$table]))throw new RuntimeException('SQL yedeğinde ASAY ERP dışı tablo bulundu: '.$table);
            $seen[$table]=true;continue;
        }
        if(preg_match('/^(SET\b|START\s+TRANSACTION\b|COMMIT\b|ROLLBACK\b|LOCK\s+TABLES\b|UNLOCK\s+TABLES\b|\/\*!)/i',$x))continue;
        throw new RuntimeException('SQL yedeğinde desteklenmeyen sorgu bulundu: '.substr(preg_replace('/\s+/',' ',$x),0,100));
    }
    foreach(dbm_core_tables() as $name)if(!isset($seen[$name]))throw new RuntimeException('SQL yedeğinde zorunlu ASAY ERP tablosu eksik: '.$name);
    return ['type'=>'phpMyAdmin / ASAY SQL','tableCount'=>count($seen),'sourceVersion'=>'SQL Yedeği','createdAt'=>''];
}
function dbm_detect_backup(string $name,string $raw,string $zipPath=''): array {
    if($zipPath!==''){
        $index=asay_zip_file_index($zipPath);
        if(!isset($index['database/asaydb.json']))throw new RuntimeException('Tam sistem yedeğinde database/asaydb.json bulunamadı.');
        $dbRaw=asay_zip_file_read_entry($zipPath,$index['database/asaydb.json'],64*1024*1024);
        $data=json_decode($dbRaw,true);if(!is_array($data))throw new RuntimeException('Tam sistem yedeğindeki DB verisi bozuk.');
        $meta=dbm_validate_json_array($data);$files=0;$storageBytes=0;
        foreach($index as $n=>$entry){
            if(str_starts_with($n,'storage/')&&!str_ends_with($n,'/')){$files++;$storageBytes+=(int)($entry['usize']??0);}
        }
        if($storageBytes>250*1024*1024)throw new RuntimeException('Tam sistem yedeğinin açılmış storage boyutu 250 MB güvenlik sınırını aşıyor.');
        $meta['type']='ASAY Tam Sistem Yedeği';$meta['fileCount']=$files;$meta['storageBytes']=$storageBytes;
        return ['kind'=>'full_file','meta'=>$meta,'data'=>$data,'zipPath'=>$zipPath,'zipIndex'=>$index];
    }
    $trim=ltrim($raw);
    if(str_starts_with($raw,"PK\x03\x04")){
        $entries=asay_zip_entries_from_data($raw);
        if(!isset($entries['database/asaydb.json']))throw new RuntimeException('Tam sistem yedeğinde database/asaydb.json bulunamadı.');
        $data=json_decode($entries['database/asaydb.json'],true);if(!is_array($data))throw new RuntimeException('Tam sistem yedeğindeki DB verisi bozuk.');
        $meta=dbm_validate_json_array($data);$files=0;foreach(array_keys($entries) as $n)if(str_starts_with($n,'storage/'))$files++;
        $meta['type']='ASAY Tam Sistem Yedeği';$meta['fileCount']=$files;
        return ['kind'=>'full','meta'=>$meta,'data'=>$data,'entries'=>$entries];
    }
    if(str_starts_with($trim,'{')){
        $data=json_decode($raw,true);if(!is_array($data))throw new RuntimeException('JSON yedek dosyası bozuk.');
        $meta=dbm_validate_json_array($data);return ['kind'=>'json','meta'=>$meta,'data'=>$data];
    }
    $meta=dbm_validate_sql($raw);return ['kind'=>'sql','meta'=>$meta,'sql'=>$raw];
}
function dbm_drop_app_tables(PDO $pdo): void { asay_schema_drop($pdo); }
function dbm_request_items_v2(PDO $pdo): void {
    try{$exists=(bool)$pdo->query("SHOW TABLES LIKE 'request_items'")->fetchColumn();}catch(Throwable $e){$exists=false;}
    if(!$exists)return;
    $q=$pdo->query("SHOW COLUMNS FROM request_items LIKE 'source_item_id'");
    if($q->fetch())return;
    // Derived mirror; canonical app_state will repopulate on next save.
    $pdo->exec('DROP TABLE request_items');
    $pdo->exec("CREATE TABLE request_items (
      id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, request_id BIGINT UNSIGNED NOT NULL, source_item_id BIGINT UNSIGNED NOT NULL,
      item_name VARCHAR(255) NOT NULL, qty DECIMAL(18,4) NOT NULL DEFAULT 0, unit VARCHAR(40) NULL,
      brand_model VARCHAR(190) NULL, specification TEXT NULL, payload_json LONGTEXT NULL,
      UNIQUE KEY uq_request_source_item(request_id,source_item_id), INDEX idx_req_item_request(request_id), INDEX idx_req_item_source(source_item_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}
function dbm_refresh_core_mirrors(PDO $pdo): void {
    $raw=$pdo->query('SELECT state_json FROM app_state WHERE id=1')->fetchColumn();$st=$raw?json_decode((string)$raw,true):[];
    if(!is_array($st))return;
    // request_items composite mirror
    $pdo->exec('DELETE FROM request_items');
    $qi=$pdo->prepare('INSERT INTO request_items(request_id,source_item_id,item_name,qty,unit,brand_model,specification,payload_json) VALUES(?,?,?,?,?,?,?,?)');
    foreach(($st['requests']??[]) as $r){$rid=(int)($r['id']??0);if($rid<=0)continue;foreach(($r['items']??[]) as $it){$sid=(int)($it['id']??0);if($sid<=0)continue;$qi->execute([$rid,$sid,$it['name']??'Kalem',(float)($it['qty']??0),$it['unit']??null,$it['brandModel']??null,$it['spec']??null,json_encode($it,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)]);}}
    // delivery batch item mirror uses the composite request-item mirror id
    $pdo->exec('DELETE FROM delivery_batch_items');
    $findItem=$pdo->prepare('SELECT id FROM request_items WHERE request_id=? AND source_item_id=? LIMIT 1');
    $insertDelivery=$pdo->prepare('INSERT INTO delivery_batch_items(batch_id,request_item_id,item_name,qty,unit,supplier_name,po_id,sale_total,cost_base,payload_json) VALUES(?,?,?,?,?,?,?,?,?,?)');
    foreach(($st['orders']??[]) as $o){$rid=(int)($o['requestId']??0);foreach(($o['deliveryBatches']??[]) as $b){$bid=(int)($b['id']??0);if($bid<=0)continue;foreach(($b['items']??[]) as $bi){$source=(int)($bi['itemId']??0);$mirror=null;if($rid>0&&$source>0){$findItem->execute([$rid,$source]);$mirror=$findItem->fetchColumn()?:null;}$insertDelivery->execute([$bid,$mirror,$bi['name']??'Kalem',(float)($bi['qty']??0),$bi['unit']??null,$bi['supplier']??null,(int)($bi['poId']??0)?:null,(float)($bi['saleTotal']??0),(float)($bi['costBase']??0),json_encode($bi,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)]);}}}
    // supplier quote totals
    $req=[];foreach(($st['requests']??[]) as $r)$req[(int)($r['id']??0)]=$r;
    $qs=$pdo->prepare('INSERT INTO supplier_quotes(id,request_id,supplier_name,reference_no,currency,total,payload_json) VALUES(?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE request_id=VALUES(request_id),supplier_name=VALUES(supplier_name),reference_no=VALUES(reference_no),currency=VALUES(currency),total=VALUES(total),payload_json=VALUES(payload_json)');
    foreach(($st['suppliers']??[]) as $sq){$rid=(int)($sq['requestId']??0);$sid=(int)($sq['id']??0);if($rid<=0||$sid<=0)continue;$r=$req[$rid]??[];$qty=[];foreach(($r['items']??[]) as $it)$qty[(string)($it['id']??0)]=(float)($it['qty']??0);$total=0.0;foreach(($sq['offers']??[]) as $iid=>$price)$total+=(float)$price*(float)($qty[(string)$iid]??0);$total+=(float)($sq['freight']??0);$cur=strtoupper((string)($sq['currency']??$r['currency']??'EUR'));if(!in_array($cur,['EUR','USD','TRY'],true))$cur='EUR';$qs->execute([$sid,$rid,$sq['name']??'Tedarikçi',$sq['ref']??null,$cur,$total,json_encode($sq,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)]);}
    // legacy NULL-key duplicate party cleanup
    $pdo->exec("UPDATE requests r JOIN parties oldp ON r.party_id=oldp.id JOIN parties newp ON newp.party_key IS NOT NULL AND newp.party_type=oldp.party_type AND LOWER(TRIM(newp.name))=LOWER(TRIM(oldp.name)) SET r.party_id=newp.id WHERE oldp.party_key IS NULL");
    $pdo->exec("UPDATE account_transactions a JOIN parties oldp ON a.party_id=oldp.id JOIN parties newp ON newp.party_key IS NOT NULL AND newp.party_type=oldp.party_type AND LOWER(TRIM(newp.name))=LOWER(TRIM(oldp.name)) SET a.party_id=newp.id WHERE oldp.party_key IS NULL");
    $pdo->exec("DELETE oldp FROM parties oldp JOIN parties newp ON newp.party_key IS NOT NULL AND newp.party_type=oldp.party_type AND LOWER(TRIM(newp.name))=LOWER(TRIM(oldp.name)) WHERE oldp.party_key IS NULL");
}
function dbm_apply_current_schema(PDO $pdo): void {
    asay_schema_apply($pdo,__DIR__.'/../database/schema.sql');dbm_request_items_v2($pdo);
    $alters=[
      "ALTER TABLE parties MODIFY COLUMN party_type ENUM('customer','supplier','public','private','customs','freight','service') NOT NULL",
      "ALTER TABLE parties ADD COLUMN party_key VARCHAR(80) NULL AFTER party_type","ALTER TABLE parties ADD UNIQUE KEY uq_party_key(party_key)",
      "ALTER TABLE users ADD COLUMN department VARCHAR(100) NULL AFTER role","ALTER TABLE users ADD COLUMN note VARCHAR(255) NULL AFTER department",
      "ALTER TABLE users ADD COLUMN photo_data LONGTEXT NULL AFTER note","ALTER TABLE app_state ADD COLUMN revision BIGINT UNSIGNED NOT NULL DEFAULT 0 AFTER updated_by"
    ];
    foreach($alters as $q){try{$pdo->exec($q);}catch(Throwable $e){}}
    dbm_refresh_core_mirrors($pdo);
}
function dbm_restore_json(PDO $pdo,array $data,bool $applyCurrent=true): void {
    dbm_validate_json_array($data);$tables=(array)$data['tables'];
    // Always install the CURRENT application schema first. Backup createSql is metadata only.
    asay_schema_fresh($pdo,__DIR__.'/../database/schema.sql');
    $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
    try{
        foreach(dbm_tables() as $name){
            if(!isset($tables[$name]))continue;$rows=(array)($tables[$name]['rows']??[]);if(!$rows)continue;
            $currentCols=[];foreach($pdo->query('SHOW COLUMNS FROM `'.$name.'`')->fetchAll(PDO::FETCH_ASSOC) as $cc)$currentCols[(string)$cc['Field']]=true;
            foreach($rows as $row){
                $row=(array)$row;$filtered=array_intersect_key($row,$currentCols);if(!$filtered)continue;
                $cols=array_keys($filtered);$colSql=implode(',',array_map(fn($c)=>'`'.str_replace('`','``',(string)$c).'`',$cols));$ph=implode(',',array_fill(0,count($cols),'?'));
                $q=$pdo->prepare('INSERT INTO `'.$name.'` ('.$colSql.') VALUES ('.$ph.')');$q->execute(array_values($filtered));
            }
        }
    } finally {$pdo->exec('SET FOREIGN_KEY_CHECKS=1');}
    if($applyCurrent){dbm_request_items_v2($pdo);dbm_refresh_core_mirrors($pdo);}
}
function dbm_restore_sql(PDO $pdo,string $sql): void {
    dbm_validate_sql($sql);$statements=dbm_split_sql($sql);
    // Legacy SQL schemas are NOT recreated. Current schema stays authoritative.
    asay_schema_fresh($pdo,__DIR__.'/../database/schema.sql');
    $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
    try{
        foreach($statements as $st){
            $x=dbm_strip_leading_sql_comments($st);if($x==='')continue;
            if(!preg_match('/^INSERT\s+INTO\s+`?([A-Za-z0-9_]+)`?/i',$x,$m))continue;
            if(!in_array($m[1],dbm_tables(),true))continue;
            $pdo->exec($st);
        }
    } finally {$pdo->exec('SET FOREIGN_KEY_CHECKS=1');}
    dbm_request_items_v2($pdo);dbm_refresh_core_mirrors($pdo);
}
function dbm_rrmdir(string $dir): void {
    if(!is_dir($dir))return;$it=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir,FilesystemIterator::SKIP_DOTS),RecursiveIteratorIterator::CHILD_FIRST);
    foreach($it as $f){$f->isDir()?@rmdir($f->getPathname()):@unlink($f->getPathname());}@rmdir($dir);
}
function dbm_restore_storage_zip(string $zipPath,array $index): int {
    $root=realpath(__DIR__.'/..');if($root===false)throw new RuntimeException('Uygulama kök yolu bulunamadı.');
    $storage=$root.'/storage';$stage=$storage.'/.restore-stage-'.bin2hex(random_bytes(5));$rollback=$storage.'/.restore-rollback-'.bin2hex(random_bytes(5));
    if(!mkdir($stage,0750,true))throw new RuntimeException('Storage restore staging klasörü oluşturulamadı.');
    $count=0;$total=0;
    try{
        foreach($index as $name=>$entry){
            if(!str_starts_with($name,'storage/'))continue;
            if(str_contains($name,'../')||str_contains($name,"\0")||str_starts_with($name,'/'))throw new RuntimeException('Yedekte güvensiz storage yolu var.');
            if(preg_match('#^storage/(?:db-backups|backups|system-backups)/#',$name))continue;
            $rel=substr($name,8);if($rel===''||str_ends_with($rel,'/'))continue;
            $usize=(int)($entry['usize']??0);$total+=$usize;
            if($total>250*1024*1024)throw new RuntimeException('Tam sistem yedeğinin açılmış storage boyutu 250 MB güvenlik sınırını aşıyor.');
            $target=$stage.'/'.$rel;$dir=dirname($target);
            if(!is_dir($dir)&&!mkdir($dir,0750,true)&&!is_dir($dir))throw new RuntimeException('Staging klasörü oluşturulamadı.');
            asay_zip_file_extract_to($zipPath,$entry,$target);$count++;
        }
        mkdir($rollback,0750,true);$managed=['request-docs','trash'];
        foreach($managed as $dirName){
            $live=$storage.'/'.$dirName;$new=$stage.'/'.$dirName;$old=$rollback.'/'.$dirName;
            if(is_dir($live)&&!@rename($live,$old))throw new RuntimeException('Mevcut storage güvenli alana taşınamadı: '.$dirName);
            if(is_dir($new)&&!@rename($new,$live)){
                if(is_dir($old))@rename($old,$live);
                throw new RuntimeException('Yeni storage etkinleştirilemedi: '.$dirName);
            }
        }
        dbm_rrmdir($rollback);dbm_rrmdir($stage);return $count;
    }catch(Throwable $e){
        foreach(['request-docs','trash'] as $dirName){
            $live=$storage.'/'.$dirName;$old=$rollback.'/'.$dirName;
            if(is_dir($old)){if(is_dir($live))dbm_rrmdir($live);@rename($old,$live);}
        }
        dbm_rrmdir($stage);dbm_rrmdir($rollback);throw $e;
    }
}
function dbm_restore_storage_entries(array $entries): int {
    $root=realpath(__DIR__.'/..');if($root===false)throw new RuntimeException('Uygulama kök yolu bulunamadı.');
    $storage=$root.'/storage';$stage=$storage.'/.restore-stage-'.bin2hex(random_bytes(5));$rollback=$storage.'/.restore-rollback-'.bin2hex(random_bytes(5));
    if(!mkdir($stage,0750,true))throw new RuntimeException('Storage restore staging klasörü oluşturulamadı.');$count=0;$total=0;
    try{
        foreach($entries as $name=>$data){
            if(!str_starts_with($name,'storage/'))continue;if(str_contains($name,'../')||str_contains($name,"\0"))throw new RuntimeException('Yedekte güvensiz storage yolu var.');
            if(preg_match('#^storage/(?:db-backups|backups|system-backups)/#',$name))continue;$rel=substr($name,8);if($rel===''||str_ends_with($rel,'/'))continue;
            $total+=strlen($data);if($total>250*1024*1024)throw new RuntimeException('Tam sistem yedeğinin açılmış storage boyutu 250 MB güvenlik sınırını aşıyor.');
            $target=$stage.'/'.$rel;$dir=dirname($target);if(!is_dir($dir)&&!mkdir($dir,0750,true)&&!is_dir($dir))throw new RuntimeException('Staging klasörü oluşturulamadı.');
            if(file_put_contents($target,$data,LOCK_EX)===false)throw new RuntimeException('Storage staging dosyası yazılamadı: '.$rel);$count++;
        }
        // Snapshot semantics for managed document directories: old data is moved aside, then staging becomes live.
        mkdir($rollback,0750,true);$managed=['request-docs','trash'];$swapped=[];
        foreach($managed as $dirName){
            $live=$storage.'/'.$dirName;$new=$stage.'/'.$dirName;$old=$rollback.'/'.$dirName;
            if(is_dir($live)&&!@rename($live,$old))throw new RuntimeException('Mevcut storage güvenli alana taşınamadı: '.$dirName);
            if(is_dir($new)&&!@rename($new,$live)){
                if(is_dir($old))@rename($old,$live);
                throw new RuntimeException('Yeni storage etkinleştirilemedi: '.$dirName);
            }
            $swapped[]=$dirName;
        }
        dbm_rrmdir($rollback);dbm_rrmdir($stage);return $count;
    }catch(Throwable $e){
        foreach(['request-docs','trash'] as $dirName){$live=$storage.'/'.$dirName;$old=$rollback.'/'.$dirName;if(is_dir($old)){if(is_dir($live))dbm_rrmdir($live);@rename($old,$live);}}
        dbm_rrmdir($stage);dbm_rrmdir($rollback);throw $e;
    }
}
function dbm_make_full_backup(PDO $pdo,string $zipPath): array {
    $dbTmp=tempnam(sys_get_temp_dir(),'asayfull_db_');if($dbTmp===false)throw new RuntimeException('Geçici DB yedeği oluşturulamadı.');
    $dbMeta=dbm_write_json_backup_file($pdo,$dbTmp);$zip=new AsaySimpleZip();if(!$zip->open($zipPath)){@unlink($dbTmp);throw new RuntimeException('Tam sistem ZIP dosyası oluşturulamadı.');}
    try{
        $zip->addFile($dbTmp,'database/asaydb.json');$fileCount=0;$bytes=0;
        $storage=__DIR__.'/../storage';
        if(is_dir($storage)){
            $it=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($storage,FilesystemIterator::SKIP_DOTS));
            foreach($it as $f){
                if(!$f->isFile())continue;$path=$f->getPathname();$rel=str_replace('\\','/',substr($path,strlen($storage)+1));
                if(preg_match('#^(?:db-backups|backups|system-backups)/#',$rel))continue;
                if($rel==='.htaccess')continue;
                if($zip->addFile($path,'storage/'.$rel)){$fileCount++;$bytes+=$f->getSize();}
            }
        }
        $manifest=['format'=>'ASAY_ERP_FULL_BACKUP','version'=>1,'appVersion'=>ASAY_APP_BUILD,'createdAt'=>date('c'),'database'=>$dbMeta,'storageFiles'=>$fileCount,'storageBytes'=>$bytes];
        $zip->addFromString('manifest.json',json_encode($manifest,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
        $zip->close();return ['db'=>$dbMeta,'files'=>$fileCount,'storageBytes'=>$bytes,'bytes'=>(int)filesize($zipPath)];
    } finally {@unlink($dbTmp);}
}
function dbm_ini_bytes(string $v): int {$v=trim($v);if($v==='')return 0;$last=strtolower($v[strlen($v)-1]);$n=(int)$v;return match($last){'g'=>$n*1024*1024*1024,'m'=>$n*1024*1024,'k'=>$n*1024,default=>(int)$v};}
function dbm_human_limit(): string {$u=ini_get('upload_max_filesize')?:'';$p=ini_get('post_max_size')?:'';return 'upload '.$u.' / post '.$p;}
function dbm_reset_business_state(PDO $pdo,array $u): array {
    $row=$pdo->query('SELECT state_json,revision FROM app_state WHERE id=1 FOR UPDATE')->fetch();if(!$row)throw new RuntimeException('Uygulama state bulunamadı.');
    $st=json_decode((string)$row['state_json'],true);if(!is_array($st))throw new RuntimeException('Uygulama state verisi bozuk.');
    $counts=['requests'=>count($st['requests']??[]),'orders'=>count($st['orders']??[]),'accounts'=>count($st['accounts']??[]),'cashAccounts'=>count($st['cashAccounts']??[]),'cashMovements'=>count($st['cash']??[]),'documents'=>count($st['documents']??[])+count($st['requestAttachments']??[])];
    // IMPORTANT: iterate the canonical arrays themselves. `foreach(($st['x'] ?? []) as &$v)`
    // mutates a temporary copy in PHP and leaves the original state unchanged.
    if(!isset($st['accounts'])||!is_array($st['accounts']))$st['accounts']=[];
    foreach($st['accounts'] as &$a){$a['entries']=[];$a['requestOpen']=0;$a['total']=0;$a['request']='';if(array_key_exists('balance',$a))$a['balance']=0;if(array_key_exists('dueDate',$a))$a['dueDate']='';}unset($a);
    if(!isset($st['cashAccounts'])||!is_array($st['cashAccounts']))$st['cashAccounts']=[];
    foreach($st['cashAccounts'] as &$a){$a['balance']=0;if(array_key_exists('openingBalance',$a))$a['openingBalance']=0;}unset($a);
    foreach(['requests','suppliers','orders','selected','customerQuotes','documents','requestAttachments','guarantees','cash'] as $k)$st[$k]=[];
    if(isset($st['requestNumber'])&&is_array($st['requestNumber']))$st['requestNumber']['next']=1;
    $st['_storage']=['canonical'=>'app_state','mirrors'=>'normalized_tables','schema'=>'v3.13.4','updatedAt'=>date('c'),'businessResetAt'=>date('c')];
    $rev=(int)$row['revision']+1;$json=json_encode($st,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);if($json===false)throw new RuntimeException('Sıfırlanmış state JSON oluşturulamadı.');
    $q=$pdo->prepare('UPDATE app_state SET state_json=?,updated_by=?,revision=? WHERE id=1');$q->execute([$json,(int)$u['id'],$rev]);save_module_states($pdo,$st,(int)$u['id']);
    $pdo->exec('DELETE FROM delivery_batch_items');$pdo->exec('DELETE FROM delivery_batches');$pdo->exec('DELETE FROM purchase_orders');$pdo->exec('DELETE FROM sales_orders');
    $pdo->exec('DELETE FROM commercial_documents');$pdo->exec('DELETE FROM customer_quotes');$pdo->exec('DELETE FROM supplier_quotes');$pdo->exec('DELETE FROM request_items');$pdo->exec('DELETE FROM requests');
    $pdo->exec('DELETE FROM account_transactions');$pdo->exec('DELETE FROM cash_transactions');$pdo->exec('DELETE FROM finance_journal');$pdo->exec('DELETE FROM operation_idempotency');$pdo->exec('DELETE FROM entity_locks');
    $pdo->exec('UPDATE file_registry SET deleted_at=COALESCE(deleted_at,NOW()) WHERE request_id IS NOT NULL OR NULLIF(TRIM(request_no),\'\') IS NOT NULL');
    $pdo->exec('DELETE FROM cash_accounts');$cq=$pdo->prepare('INSERT INTO cash_accounts(id,name,account_type,currency,balance,iban,swift,payload_json) VALUES(?,?,?,?,0,?,?,?)');foreach(($st['cashAccounts']??[]) as $a)$cq->execute([(int)$a['id'],$a['name']??'Hesap',$a['type']??'Banka',$a['currency']??'EUR',$a['iban']??null,$a['swift']??null,json_encode($a,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)]);
    // Hard post-condition: a business reset may preserve master cards, never financial balances/history.
    foreach(($st['accounts']??[]) as $a){if(abs((float)($a['requestOpen']??0))>0.001||!empty($a['entries']))throw new RuntimeException('Cari hesap sıfırlama doğrulaması başarısız.');}
    foreach(($st['cashAccounts']??[]) as $a){if(abs((float)($a['balance']??0))>0.001)throw new RuntimeException('Kasa/Banka bakiye sıfırlama doğrulaması başarısız.');}
    return ['revision'=>$rev,'counts'=>$counts,'preservedAccounts'=>count($st['accounts']??[]),'preservedCashAccounts'=>count($st['cashAccounts']??[])];
}

$action=(string)($_GET['action']??'status');
try{
    $pdo=db();
    if($action==='status'){
        $latest=dbm_latest_safety();if($latest)unset($latest['path']);
        json_response(['ok'=>true,'database'=>(string)(db_config()['database']??''),'existingTableCount'=>count(dbm_existing_tables($pdo)),'allowedTableCount'=>count(dbm_tables()),'lastSafetyBackup'=>$latest,'uploadLimit'=>dbm_human_limit()]);
    }
    dbm_csrf();

    if($action==='backup'){
        if($_SERVER['REQUEST_METHOD']!=='POST')json_response(['ok'=>false,'error'=>'Geçersiz istek.'],405);
        $tmp=tempnam(sys_get_temp_dir(),'asay_json_');$meta=dbm_write_json_backup_file($pdo,$tmp);$name='ASAY_ERP_DB_'.date('Ymd_His').'.asaydb.json';
        audit('database_backup_download','database',null,$meta);
        header('Content-Type: application/json; charset=utf-8');header('Content-Disposition: attachment; filename="'.$name.'"');header('Content-Length: '.filesize($tmp));readfile($tmp);@unlink($tmp);exit;
    }
    if($action==='backup_sql'){
        if($_SERVER['REQUEST_METHOD']!=='POST')json_response(['ok'=>false,'error'=>'Geçersiz istek.'],405);
        $tmp=tempnam(sys_get_temp_dir(),'asay_sql_');$meta=asay_write_sql_backup($pdo,$tmp,ASAY_APP_BUILD);$name='ASAY_ERP_DB_'.date('Ymd_His').'.sql';
        audit('database_backup_sql_download','database',null,$meta);
        header('Content-Type: application/sql; charset=utf-8');header('Content-Disposition: attachment; filename="'.$name.'"');header('Content-Length: '.filesize($tmp));readfile($tmp);@unlink($tmp);exit;
    }
    if($action==='backup_full'){
        if($_SERVER['REQUEST_METHOD']!=='POST')json_response(['ok'=>false,'error'=>'Geçersiz istek.'],405);
        $tmp=tempnam(sys_get_temp_dir(),'asay_full_');@unlink($tmp);$tmp.='.zip';$meta=dbm_make_full_backup($pdo,$tmp);$name='ASAY_ERP_FULL_'.date('Ymd_His').'.asayfull.zip';
        audit('database_backup_full_download','backup',null,$meta);
        header('Content-Type: application/zip');header('Content-Disposition: attachment; filename="'.$name.'"');header('Content-Length: '.filesize($tmp));readfile($tmp);@unlink($tmp);exit;
    }
    if($action==='reset_business_data'){
        if($_SERVER['REQUEST_METHOD']!=='POST')json_response(['ok'=>false,'error'=>'Geçersiz istek.'],405);
        $in=json_decode((string)file_get_contents('php://input'),true)?:[];if(($in['confirm']??'')!=='SIFIRLA')json_response(['ok'=>false,'error'=>'Sıfırlama onayı için SIFIRLA yazılmalıdır.'],400);
        $safety=dbm_write_safety_backup($pdo,'pre_business_reset');
        try{$pdo->beginTransaction();$result=dbm_reset_business_state($pdo,$u);$pdo->commit();}
        catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}
        try{audit('business_data_reset','database','business',['safetyBackup'=>$safety['name'],'counts'=>$result['counts'],'preservedAccounts'=>$result['preservedAccounts'],'preservedCashAccounts'=>$result['preservedCashAccounts']]);}catch(Throwable $ignore){}
        unset($safety['path']);json_response(['ok'=>true,'safetyBackup'=>$safety,'result'=>$result]);
    }
    if($action==='validate'){
        if($_SERVER['REQUEST_METHOD']!=='POST')json_response(['ok'=>false,'error'=>'Geçersiz istek.'],405);
        $f=dbm_read_uploaded();$det=dbm_detect_backup($f['name'],$f['raw'],$f['zipPath']??'');json_response(['ok'=>true,'meta'=>$det['meta']]);
    }
    if($action==='restore'){
        if($_SERVER['REQUEST_METHOD']!=='POST'||($_POST['confirm']??'')!=='ASAY_RESTORE')json_response(['ok'=>false,'error'=>'Geri yükleme onayı eksik.'],400);
        $f=dbm_read_uploaded();$det=dbm_detect_backup($f['name'],$f['raw'],$f['zipPath']??'');$safety=dbm_write_safety_backup($pdo,'pre_restore');
        // Preserve the currently authenticated Admin so an old backup cannot lock the operator out.
        $preserveAdmin=['id'=>(int)($u['id']??0),'name'=>(string)($u['name']??'Admin'),'email'=>(string)($u['email']??''),'password_hash'=>''];
        try{$q=$pdo->prepare('SELECT password_hash FROM users WHERE id=?');$q->execute([$preserveAdmin['id']]);$preserveAdmin['password_hash']=(string)($q->fetchColumn()?:'');}catch(Throwable $ignore){}
        try{
            if(in_array($det['kind'],['json','full','full_file'],true))dbm_restore_json($pdo,$det['data'],true);else dbm_restore_sql($pdo,$det['sql']);
            if($det['kind']==='full_file')$restoredFiles=dbm_restore_storage_zip($det['zipPath'],$det['zipIndex']);
            elseif($det['kind']==='full')$restoredFiles=dbm_restore_storage_entries($det['entries']);
            else $restoredFiles=0;
            if($preserveAdmin['id']>0&&$preserveAdmin['email']!==''&&$preserveAdmin['password_hash']!==''){
                $id=$preserveAdmin['id'];$byId=$pdo->prepare('SELECT id FROM users WHERE id=?');$byId->execute([$id]);
                if($byId->fetchColumn()){
                    $pdo->prepare("UPDATE users SET name=?,email=?,password_hash=?,role='Admin',status='active' WHERE id=?")
                        ->execute([$preserveAdmin['name'],$preserveAdmin['email'],$preserveAdmin['password_hash'],$id]);
                }else{
                    $byMail=$pdo->prepare('SELECT id FROM users WHERE email=? LIMIT 1');$byMail->execute([$preserveAdmin['email']]);$mailId=$byMail->fetchColumn();
                    if($mailId)$pdo->prepare("UPDATE users SET name=?,password_hash=?,role='Admin',status='active' WHERE id=?")->execute([$preserveAdmin['name'],$preserveAdmin['password_hash'],$mailId]);
                    else $pdo->prepare("INSERT INTO users(id,name,email,password_hash,role,status) VALUES(?,?,?,?,'Admin','active')")->execute([$id,$preserveAdmin['name'],$preserveAdmin['email'],$preserveAdmin['password_hash']]);
                }
            }
        }catch(Throwable $restoreError){
            $rollbackError='';
            try{$pre=json_decode((string)file_get_contents($safety['path']),true);if(!is_array($pre))throw new RuntimeException('Otomatik güvenlik yedeği okunamadı.');dbm_restore_json($pdo,$pre,true);}
            catch(Throwable $re){$rollbackError=' Otomatik geri dönüş de başarısız: '.$re->getMessage();}
            throw new RuntimeException('Geri yükleme başarısız: '.$restoreError->getMessage().$rollbackError);
        }
        try{audit('database_restore','database',null,['source'=>$det['meta'],'safetyBackup'=>$safety['name'],'restoredFiles'=>$restoredFiles??0]);}catch(Throwable $e){}
        unset($safety['path']);json_response(['ok'=>true,'meta'=>$det['meta'],'safetyBackup'=>$safety,'restoredFiles'=>$restoredFiles??0]);
    }
    if($action==='rollback'){
        if($_SERVER['REQUEST_METHOD']!=='POST')json_response(['ok'=>false,'error'=>'Geçersiz istek.'],405);
        $in=json_decode((string)file_get_contents('php://input'),true)?:[];if(($in['confirm']??'')!=='ASAY_ROLLBACK')json_response(['ok'=>false,'error'=>'Geri alma onayı eksik.'],400);
        $latest=dbm_latest_safety();if(!$latest)throw new RuntimeException('Geri dönülebilecek restore öncesi güvenlik yedeği bulunamadı.');
        $current=dbm_write_safety_backup($pdo,'pre_rollback');$pre=json_decode((string)file_get_contents($latest['path']),true);if(!is_array($pre))throw new RuntimeException('Güvenlik yedeği okunamadı.');
        try{dbm_restore_json($pdo,$pre,true);}catch(Throwable $e){
            try{$now=json_decode((string)file_get_contents($current['path']),true);if(is_array($now))dbm_restore_json($pdo,$now,true);}catch(Throwable $ignore){}
            throw new RuntimeException('Geri alma başarısız: '.$e->getMessage());
        }
        @rename($latest['path'],$latest['path'].'.restored');dbm_prune_safety(10);
        try{audit('database_restore_rollback','database',null,['source'=>$latest['name']]);}catch(Throwable $e){}
        json_response(['ok'=>true]);
    }
    json_response(['ok'=>false,'error'=>'Bilinmeyen veritabanı işlemi.'],404);
}catch(Throwable $e){
    json_response(['ok'=>false,'error'=>app_debug_enabled()?$e->getMessage():'Veritabanı işlemi tamamlanamadı. Lütfen dosya biçimini, sunucu limitlerini ve yetkileri kontrol edin.'],400);
}
