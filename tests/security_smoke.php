<?php
declare(strict_types=1);
require_once __DIR__.'/../app/bootstrap.php';
require_once __DIR__.'/../app/business_rules.php';
$bad='<script>alert(1)</script><b onclick="x()">Firma</b>';
$clean=sanitize_state_value(['title'=>$bad,'html'=>'<div onclick="x()">OK</div><script>x()</script>']);
if(str_contains((string)$clean['title'],'<')||str_contains((string)$clean['html'],'script')||str_contains((string)$clean['html'],'onclick')){fwrite(STDERR,"sanitize failed\n");exit(2);} 
if(!asay_po_transition_allowed('Hazırlanıyor','Üretimde')){fwrite(STDERR,"po forward failed\n");exit(3);} 
if(asay_po_transition_allowed('Teslim Edildi','Hazırlanıyor')){fwrite(STDERR,"po rollback should be blocked\n");exit(4);} 
if(asay_po_progress('Teslim Edildi')!==100.0){fwrite(STDERR,"po progress failed\n");exit(5);} 
echo "PASS\n";
