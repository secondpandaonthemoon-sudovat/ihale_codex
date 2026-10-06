<?php
declare(strict_types=1);
require_once __DIR__.'/../app/bootstrap.php';
$u=current_user();if(!$u)json_response(['ok'=>false],401);
$x=json_decode(file_get_contents('php://input'),true)?:[];
$action=(string)($x['action']??'');
$allowed=['client_js_error','client_unhandled_rejection','render_error'];
if(!in_array($action,$allowed,true))json_response(['ok'=>false,'error'=>'İzin verilmeyen istemci audit olayı.'],422);
$type=(string)($x['entity_type']??'ui');if(!in_array($type,['ui'],true))$type='ui';
$id=isset($x['entity_id'])?substr((string)$x['entity_id'],0,120):null;
$payload=is_array($x['payload']??null)?$x['payload']:[];
$payload=array_slice($payload,0,12,true);
foreach($payload as $k=>$v)if(is_string($v))$payload[$k]=mb_substr($v,0,1000);
audit($action,$type,$id,$payload);
json_response(['ok'=>true]);
