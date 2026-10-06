<?php
require_once __DIR__.'/../app/bootstrap.php';
$state=['cash'=>[['amount'=>400.0,'fxRate'=>1.0,'orderAmount'=>400.0]],'fraction'=>250.75];
$restored=json_decode(json_encode($state,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),true);
if(state_key_hash($state)!==state_key_hash($restored))throw new RuntimeException('State hash changes across storage roundtrip');
$restored['fraction']=250.76;
if(state_key_hash($state)===state_key_hash($restored))throw new RuntimeException('Real amount changes must conflict');
echo "PASS: numeric state hash roundtrip and genuine conflict detection\n";
