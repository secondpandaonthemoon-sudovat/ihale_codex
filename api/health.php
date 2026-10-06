<?php
declare(strict_types=1);
require_once __DIR__.'/../app/bootstrap.php';
require_login();
$u=require_admin();
try{
    $pdo=db();$pdo->query('SELECT 1')->fetchColumn();
    $raw=$pdo->query('SELECT state_json,revision,updated_at FROM app_state WHERE id=1')->fetch();
    $st=$raw?json_decode((string)$raw['state_json'],true):[];if(!is_array($st))$st=[];
    $expected=[
      'requests'=>count($st['requests']??[]),
      'supplier_quotes'=>count($st['suppliers']??[]),
      'commercial_documents'=>count($st['documents']??[]),
      'cash_accounts'=>count($st['cashAccounts']??[]),
      'cash_account_duplicate_ids'=>count(array_filter(array_count_values(array_map(fn($x)=>(string)($x['id']??''),$st['cashAccounts']??[])),fn($n)=>$n>1)),
    ];
    $expected['request_items']=array_sum(array_map(fn($r)=>count($r['items']??[]),$st['requests']??[]));
    $actual=[];$warnings=[];
    foreach(array_keys($expected) as $table){
      try{$actual[$table]=(int)$pdo->query('SELECT COUNT(*) FROM `'.$table.'`')->fetchColumn();}
      catch(Throwable $e){$actual[$table]=-1;$warnings[]=$table.' tablosu okunamadı';continue;}
      if($actual[$table]!==$expected[$table])$warnings[]=$table.' mirror farkı: app_state='.$expected[$table].', mirror='.$actual[$table];
    }
    $status=$warnings?'degraded':'healthy';
    json_response(['ok'=>true,'status'=>$status,'appVersion'=>ASAY_APP_BUILD,'timezone'=>date_default_timezone_get(),'revision'=>(int)($raw['revision']??0),'checked_at'=>date('c'),'canonical'=>'app_state','mirrors'=>['expected'=>$expected,'actual'=>$actual,'warnings'=>$warnings]]);
}catch(Throwable $e){
    json_response(['ok'=>false,'status'=>'unhealthy','appVersion'=>ASAY_APP_BUILD,'error'=>public_error($e,'Veritabanı sağlık kontrolü başarısız.')],500);
}
