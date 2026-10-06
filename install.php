<?php
declare(strict_types=1);
require_once __DIR__.'/app/bootstrap.php';
require_once __DIR__.'/app/schema_manager.php';

$error='';$done=false;$locked=false;$existing=[];$detectedUrl=app_base_url();
$defaults=db_config();

function install_pdo(array $c,bool $withoutDatabase=false): PDO {
    $dsn='mysql:host='.$c['host'].';port='.$c['port'].';'.($withoutDatabase?'':'dbname='.$c['database'].';').'charset=utf8mb4';
    return new PDO($dsn,$c['username'],$c['password'],[
      PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
      PDO::ATTR_EMULATE_PREPARES=>false,
    ]);
}
function install_table_exists(PDO $pdo,string $table): bool {
    $q=$pdo->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name=?");
    $q->execute([$table]);return (int)$q->fetchColumn()>0;
}
if($_SERVER['REQUEST_METHOD']!=='POST'){
    try{
        $probe=install_pdo($defaults);
        if(install_table_exists($probe,'users')){
            $locked=(int)$probe->query('SELECT COUNT(*) FROM users')->fetchColumn()>0;
        }
        $existing=asay_schema_existing($probe);
    }catch(Throwable $e){$locked=false;$existing=[];}
}

if($_SERVER['REQUEST_METHOD']==='POST'&&!$locked){
  try{
    $host=trim((string)($_POST['db_host']??'localhost'))?:'localhost';
    $port=max(1,(int)($_POST['db_port']??3306));
    $database=preg_replace('/[^a-zA-Z0-9_]/','',(string)($_POST['db_name']??''));
    $username=trim((string)($_POST['db_user']??''));
    $password=(string)($_POST['db_pass']??'');
    $name=trim((string)($_POST['name']??'Admin Kullanıcı'));
    $email=strtolower(trim((string)($_POST['email']??'')));
    $pass=(string)($_POST['password']??'');

    if($database===''||$username==='')throw new RuntimeException('Veritabanı adı ve kullanıcı adı zorunludur.');
    if(strlen($pass)<10)throw new RuntimeException('Şifre en az 10 karakter olmalı.');
    if(!filter_var($email,FILTER_VALIDATE_EMAIL))throw new RuntimeException('Geçerli bir yönetici e-posta adresi girin.');
    if($name==='')throw new RuntimeException('Yönetici adı zorunludur.');

    $cfg=['host'=>$host,'port'=>$port,'database'=>$database,'username'=>$username,'password'=>$password,'charset'=>'utf8mb4'];

    try{
        $server=install_pdo($cfg,true);
        $server->exec("CREATE DATABASE IF NOT EXISTS `".$database."` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    }catch(Throwable $e){
        throw new RuntimeException('MySQL sunucu bağlantısı kurulamadı veya veritabanı oluşturulamadı: '.$e->getMessage(),0,$e);
    }
    try{$pdo=install_pdo($cfg,false);}catch(Throwable $e){throw new RuntimeException('Hedef veritabanına bağlanılamadı: '.$e->getMessage(),0,$e);}

    $existing=asay_schema_existing($pdo);
    if($existing&&!isset($_POST['clean_install']))throw new RuntimeException('Bu veritabanında eski ASAY tabloları var. Sıfırdan kurulum için temiz kurulum seçeneğini işaretleyin.');
    if($existing&&($_POST['clean_confirm']??'')!=='SIFIRDAN_KUR')throw new RuntimeException('Temiz kurulum onayı için SIFIRDAN_KUR yazın.');

    if($existing)asay_schema_drop($pdo);
    try{
        asay_schema_apply($pdo,__DIR__.'/database/schema.sql');
        $pdo->prepare("INSERT INTO users(name,email,password_hash,role,status) VALUES(?,?,?,?, 'active')")
            ->execute([$name,$email,password_hash($pass,PASSWORD_DEFAULT),'Admin']);
        $adminId=(int)$pdo->lastInsertId();
        $pdo->prepare("INSERT INTO schema_migrations(version,applied_at,applied_by,note) VALUES('V3.13.4',NOW(),?,'Fresh clean install') ON DUPLICATE KEY UPDATE applied_at=VALUES(applied_at),applied_by=VALUES(applied_by),note=VALUES(note)")
            ->execute([$adminId]);
        $initial=['requests'=>[],'supplierDirectory'=>[],'suppliers'=>[],'orders'=>[],'accounts'=>[],'cashAccounts'=>[],'cash'=>[],'guarantees'=>[],
          'roles'=>[['name'=>'Admin','request'=>true,'requestWrite'=>true,'supplier'=>true,'supplierWrite'=>true,'cost'=>true,'costWrite'=>true,'finance'=>true,'financeWrite'=>true,'cash'=>true,'cashWrite'=>true,'docs'=>true,'docsWrite'=>true,'settings'=>true,'settingsWrite'=>true]],
          'users'=>[['id'=>$adminId,'name'=>$name,'email'=>$email,'role'=>'Admin','status'=>'active']]];
        $pdo->prepare('INSERT INTO app_state(id,state_json,updated_by,revision) VALUES(1,?,?,1)')
            ->execute([json_encode($initial,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),$adminId]);
        $pdo->prepare('INSERT INTO audit_log(user_id,action,entity_type,entity_id,payload_json) VALUES(?,?,?,?,?)')
            ->execute([$adminId,'fresh_install','system','V3.13.4',json_encode(['database'=>$database],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)]);
    }catch(Throwable $e){
        try{asay_schema_drop($pdo);}catch(Throwable $ignore){}
        throw new RuntimeException('Kurulum tamamlanamadı; yarım tablolar temizlendi. Hata: '.$e->getMessage(),0,$e);
    }

    $local="<?php\nreturn ".var_export($cfg,true).";\n";
    $localPath=__DIR__.'/config/database.local.php';
    if(@file_put_contents($localPath,$local,LOCK_EX)===false){
        try{asay_schema_drop($pdo);}catch(Throwable $ignore){}
        throw new RuntimeException('config/database.local.php yazılamadı. Klasör yazma izinlerini kontrol edin.');
    }
    @chmod($localPath,0640);
    $done=true;$existing=[];
  }catch(Throwable $e){$error=$e->getMessage();}
}
?><!doctype html><html lang="tr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>ASAY ERP Temiz Kurulum</title><style>
body{font-family:Arial,sans-serif;background:#f4f7fb;margin:0;display:grid;place-items:center;min-height:100vh;color:#172033;padding:24px}.box{width:min(780px,96vw);background:#fff;border:1px solid #d8e0ea;border-radius:18px;padding:28px;box-shadow:0 20px 60px #163d7322}.grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}.f.full{grid-column:1/-1}.f label{display:block;font-size:12px;font-weight:700;margin-bottom:5px}.f input{width:100%;box-sizing:border-box;padding:11px;border:1px solid #ccd5e0;border-radius:9px}.btn{width:100%;padding:12px;border:0;border-radius:9px;background:#163d73;color:#fff;font-weight:700;margin-top:16px}.ok{background:#eaf8ef;color:#16733c;padding:12px;border-radius:9px}.err{background:#fff0f0;color:#b42318;padding:12px;border-radius:9px;margin-bottom:12px}.warn{background:#fff8e7;border:1px solid #f2d596;padding:12px;border-radius:10px;margin:12px 0}.warn input[type=checkbox]{width:18px;height:18px;min-width:18px;margin:0 7px 0 0;vertical-align:middle}.warn label{display:flex;align-items:center;gap:6px}.note{background:#f8fafc;border:1px solid #e4e7ec;padding:12px;border-radius:10px;font-size:12px;color:#667085;margin:12px 0}h1{margin-top:0}@media(max-width:650px){.grid{grid-template-columns:1fr}.f.full{grid-column:auto}}</style></head><body><div class="box">
<h1>ASAY ERP Temiz Kurulum · V3.13.4</h1><p>PHP + MySQL/MariaDB sıfır kurulum. MariaDB 10.4+ uyumludur.</p><div class="note"><b>Algılanan site:</b> <?=htmlspecialchars($detectedUrl)?></div>
<?php if($locked):?><div class="err">Kurulum kilitli: aktif kullanıcı hesabı bulunan kurulum tespit edildi.</div><p><a href="login.php">Giriş ekranına dön →</a></p>
<?php elseif($done):?><div class="ok">Temiz kurulum tamamlandı. Yeni Admin hesabınız oluşturuldu.</div><p><a href="login.php">Giriş ekranına geç →</a></p>
<?php else:?><?php if($error):?><div class="err"><?=htmlspecialchars($error)?></div><?php endif;?>
<?php if($existing):?><div class="warn"><b>Eski/yarım ASAY tabloları bulundu:</b> <?=htmlspecialchars(implode(', ',$existing))?><br>Yeni kurulum için aşağıdaki temiz kurulum onayını kullanın.</div><?php endif;?>
<form method="post"><h3>1. MySQL</h3><div class="grid"><div class="f"><label>Host</label><input name="db_host" value="<?=htmlspecialchars((string)($defaults['host']??'localhost'))?>" required></div><div class="f"><label>Port</label><input name="db_port" type="number" value="<?=htmlspecialchars((string)($defaults['port']??3306))?>" required></div><div class="f"><label>Veritabanı</label><input name="db_name" value="<?=htmlspecialchars((string)($defaults['database']??'asay_erp'))?>" required></div><div class="f"><label>Kullanıcı</label><input name="db_user" value="<?=htmlspecialchars((string)($defaults['username']??''))?>" required></div><div class="f full"><label>Şifre</label><input name="db_pass" type="password"></div></div>
<h3>2. İlk Admin</h3><div class="grid"><div class="f"><label>Yönetici Adı</label><input name="name" required></div><div class="f"><label>E-posta</label><input name="email" type="email" required></div><div class="f full"><label>Şifre (min. 10 karakter)</label><input name="password" type="password" required></div></div>
<?php if($existing):?><div class="warn"><label><input type="checkbox" name="clean_install" value="1"> <b>ASAY tablolarını temizleyerek sıfırdan kur</b></label><div class="f" style="margin-top:8px"><label>Onay için SIFIRDAN_KUR yazın</label><input name="clean_confirm"></div><small>Bu seçenek yalnız ASAY uygulama tablolarını siler. Bu veritabanındaki ASAY verileri kaybolur.</small></div><?php endif;?>
<button class="btn">Temiz Kurulumu Başlat</button></form><div class="note">En temiz yöntem yeni/boş bir veritabanıdır. Eski yedekleri yalnız kurulum ve giriş doğrulandıktan sonra Ayarlar → Veritabanı Yedekleme bölümünden içeri alın.</div><?php endif;?></div></body></html>
