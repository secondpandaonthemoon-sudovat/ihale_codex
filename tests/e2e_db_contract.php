<?php
declare(strict_types=1);
require_once __DIR__.'/../app/bootstrap.php';
if((string)getenv('ASAY_E2E_DB')!=='1'){fwrite(STDOUT,"SKIP: set ASAY_E2E_DB=1 to run live MySQL contract test\n");exit(0);} 
try{
 $pdo=db();$col=$pdo->query("SHOW COLUMNS FROM parties LIKE 'party_type'")->fetch();if(!$col)throw new RuntimeException('parties.party_type yok');
 foreach(['customer','supplier','public','private','customs','freight','service'] as $v)if(!str_contains((string)$col['Type'],"'$v'"))throw new RuntimeException('party_type eksik: '.$v);
 $tz=date_default_timezone_get();if($tz!=='Europe/Istanbul' && !getenv('ASAY_TIMEZONE'))throw new RuntimeException('Timezone beklenmeyen: '.$tz);
 $pdo->query('SELECT id,revision FROM app_state LIMIT 1')->fetch();
 fwrite(STDOUT,"PASS: live MySQL schema/timezone contract\n");
}catch(Throwable $e){fwrite(STDERR,"FAIL: ".$e->getMessage()."\n");exit(1);} 
