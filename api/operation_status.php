<?php
declare(strict_types=1);
require_once __DIR__.'/../app/bootstrap.php';
require_login();
header('Cache-Control: no-store');
try{
 $key=trim((string)($_GET['key']??''));if($key==='')json_response(['ok'=>false,'error'=>'İşlem anahtarı gerekli.'],422);
 $pdo=db();$q=$pdo->prepare('SELECT action_type,created_at FROM operation_idempotency WHERE idempotency_key=? LIMIT 1');$q->execute([$key]);$hit=$q->fetch();
 if(!$hit)json_response(['ok'=>true,'committed'=>false]);
 $row=$pdo->query('SELECT state_json,revision FROM app_state WHERE id=1')->fetch();$st=$row?json_decode((string)$row['state_json'],true):[];$u=current_user();
 json_response(['ok'=>true,'committed'=>true,'action'=>$hit['action_type']??null,'created_at'=>$hit['created_at']??null,'state'=>filter_state_for_user(is_array($st)?$st:[],$u),'keyHashes'=>state_hashes_for_client(is_array($st)?$st:[],$u),'revision'=>(int)($row['revision']??0)]);
}catch(Throwable $e){json_response(['ok'=>false,'error'=>public_error($e,'İşlem durumu doğrulanamadı.')],500);}
