<?php
declare(strict_types=1);
const ASAY_APP_VERSION = '3.13.4';
const ASAY_APP_ASSET_VERSION = ASAY_APP_VERSION . '-p1';
const ASAY_APP_BUILD = 'V3.13.4 · Etiket, PDF & Finans Düzeltmeleri (P1)';
if (!date_default_timezone_set((string)(getenv('ASAY_TIMEZONE') ?: 'Europe/Istanbul'))) {
    date_default_timezone_set('Europe/Istanbul');
}

// API yanıtlarında PHP warning/notice çıktısının JSON gövdesini bozmasını engelle.
$__asayScript=str_replace('\\','/',(string)($_SERVER['SCRIPT_NAME']??''));
if(str_contains($__asayScript,'/api/')){
    ini_set('display_errors','0');
    ini_set('log_errors','1');
    error_reporting(E_ALL);
}
unset($__asayScript);

if (session_status() !== PHP_SESSION_ACTIVE) {
    $https=(!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off')||((string)($_SERVER['HTTP_X_FORWARDED_PROTO']??'')==='https');
    ini_set('session.use_strict_mode','1');
    ini_set('session.cookie_httponly','1');
    ini_set('session.cookie_samesite','Lax');
    if($https)ini_set('session.cookie_secure','1');
    session_set_cookie_params([
      'lifetime'=>0,'path'=>'/','secure'=>$https,'httponly'=>true,'samesite'=>'Lax'
    ]);
    session_start();
}
if(!headers_sent()){
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: same-origin');
    header('X-Frame-Options: SAMEORIGIN');
    header("Permissions-Policy: camera=(), microphone=(), geolocation=()");
    header("Content-Security-Policy: default-src 'self'; base-uri 'self'; object-src 'none'; frame-ancestors 'self'; form-action 'self'; img-src 'self' data: blob:; media-src 'self' blob:; connect-src 'self'; font-src 'self' data:; style-src 'self' 'unsafe-inline'; script-src 'self' 'unsafe-inline'; frame-src 'self' blob: data:");
}
function db_config(): array {
    $base=require __DIR__ . '/../config/database.php';
    $local=__DIR__ . '/../config/database.local.php';
    if(is_file($local)){
        $custom=require $local;
        if(is_array($custom))$base=array_replace($base,$custom);
    }
    return $base;
}
function app_base_url(): string {
    $https=(!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off')||((string)($_SERVER['HTTP_X_FORWARDED_PROTO']??'')==='https');
    $scheme=$https?'https':'http';
    $host=(string)($_SERVER['HTTP_HOST']??'localhost');
    $script=str_replace('\\','/',(string)($_SERVER['SCRIPT_NAME']??'/'));
    $dir=rtrim(str_replace('/google-drive','',dirname($script)),'/');
    return $scheme.'://'.$host.($dir==='/'?'':$dir);
}
function db(bool $withoutDatabase=false): PDO {
    static $pdo = null;
    if (!$withoutDatabase && $pdo instanceof PDO) return $pdo;
    $c = db_config();
    $dsn = 'mysql:host='.$c['host'].';port='.$c['port'].';'.($withoutDatabase?'':'dbname='.$c['database'].';').'charset='.$c['charset'];
    $x = new PDO($dsn,$c['username'],$c['password'],[
      PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
      PDO::ATTR_EMULATE_PREPARES=>false,
    ]);
    if (!$withoutDatabase) $pdo=$x;
    return $x;
}
function is_installed(): bool {
    try { $q=db()->query("SHOW TABLES LIKE 'users'"); return (bool)$q->fetchColumn(); } catch(Throwable $e){ return false; }
}
function request_expects_json(): bool {
    $script=str_replace('\\','/',(string)($_SERVER['SCRIPT_NAME']??''));
    $accept=strtolower((string)($_SERVER['HTTP_ACCEPT']??''));
    return str_contains($script,'/api/')||str_contains($accept,'application/json');
}
function auth_fail_response(string $message='Oturum gerekli'): never {
    if(request_expects_json())json_response(['ok'=>false,'error'=>$message,'auth_required'=>true],401);
    header('Location: login.php');exit;
}
function require_login(): void {
    if (!is_installed()) {
        if(request_expects_json())json_response(['ok'=>false,'error'=>'Sistem kurulumu tamamlanmamış.','install_required'=>true],503);
        header('Location: install.php');exit;
    }
    if (empty($_SESSION['user_id'])) auth_fail_response();
    $u=current_user();
    if(!$u || $u['status']!=='active'){
        session_unset();session_destroy();
        auth_fail_response('Oturum geçersiz veya kullanıcı hesabı pasif.');
    }
}
function json_response(array $data,int $status=200): never { http_response_code($status); header('Content-Type: application/json; charset=utf-8'); echo json_encode($data,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES); exit; }
function current_user(): ?array {
    if(empty($_SESSION['user_id'])) return null;
    $s=db()->prepare('SELECT id,name,email,role,status FROM users WHERE id=? LIMIT 1');
    $s->execute([$_SESSION['user_id']]);
    $u=$s->fetch();
    if(!$u)return null;
    $role=trim((string)($u['role']??''));
    if(strtolower($role)==='admin')$role='Admin';
    $u['role']=$role;
    return $u;
}

function user_is_admin(?array $u): bool { return !!$u && strcasecmp(trim((string)($u['role']??'')),'Admin')===0; }

function require_admin(): array {
    $u=current_user();
    if(!$u) json_response(['ok'=>false,'error'=>'Oturum gerekli'],401);
    // Sistem yönetimi için tek yetki kaynağı: gerçek Admin rolü veya Ayarlar yazma yetkisi.
    // Böylece legacy/custom rol adları ile merkezi permission matrisi birbiriyle çakışmaz.
    $role=trim((string)($u['role']??''));
    if(user_is_admin($u)) return $u;
    if(function_exists('has_app_write_permission') && has_app_write_permission('settings',$u)) return $u;
    json_response(['ok'=>false,'error'=>'Bu işlem için Admin veya Ayarlar yazma yetkisi gerekli.','role'=>$role],403);
}
function app_debug_enabled(): bool {
    $v=strtolower((string)(getenv('ASAY_DEBUG')?:''));
    return in_array($v,['1','true','yes','on'],true);
}
function ensure_csrf_token(): string {
    if(empty($_SESSION['csrf_token']))$_SESSION['csrf_token']=bin2hex(random_bytes(32));
    return (string)$_SESSION['csrf_token'];
}
function csrf_token(): string { return ensure_csrf_token(); }
function csrf_valid(?string $token): bool {
    $want=(string)($_SESSION['csrf_token']??'');
    return $want!==''&&is_string($token)&&$token!==''&&hash_equals($want,$token);
}
function require_csrf(?string $token=null): void {
    $token=$token??($_SERVER['HTTP_X_ASAY_CSRF']??$_POST['_csrf']??$_GET['_csrf']??'');
    if(!csrf_valid(is_string($token)?$token:'')) json_response(['ok'=>false,'error'=>'Güvenlik doğrulaması başarısız. Sayfayı yenileyip tekrar deneyin.'],403);
}
function app_role_config(?array $u=null): ?array {
    $u=$u?:current_user();
    if(!$u||$u['status']!=='active')return null;
    if(user_is_admin($u))return ['name'=>'Admin'];
    try{
        $row=db()->query('SELECT state_json FROM app_state WHERE id=1')->fetch();
        $st=$row?json_decode((string)$row['state_json'],true):[];
        foreach(($st['roles']??[]) as $r)if(($r['name']??'')===($u['role']??''))return $r;
    }catch(Throwable $e){ error_log('ASAY role config read failed: '.$e->getMessage()); }
    return null;
}
function has_app_permission(string $perm, ?array $u=null): bool {
    $u=$u?:current_user();
    if(!$u||$u['status']!=='active')return false;
    if(user_is_admin($u))return true;
    $r=app_role_config($u);
    return $r? !empty($r[$perm]) : false;
}
function has_app_write_permission(string $perm, ?array $u=null): bool {
    $u=$u?:current_user();
    if(!$u||$u['status']!=='active')return false;
    if(user_is_admin($u))return true;
    if($u['role']==='Görüntüleyici')return false;
    $r=app_role_config($u);if(!$r)return false;
    $k=$perm.'Write';
    if(array_key_exists($k,$r))return !empty($r[$k]);
    // Safe compatibility defaults for legacy built-in roles. Unknown/custom roles fail closed until Admin sets explicit Write flags.
    $legacy=[
      'Satış'=>['request'=>true,'docs'=>true],
      'Satın Alma'=>['request'=>true,'supplier'=>true,'cost'=>true],
      'Finans'=>['finance'=>true,'cash'=>true,'docs'=>true],
    ];
    return !empty($legacy[(string)($r['name']??'')][$perm]);
}
function can_write_state(?array $u): bool {
    if(!$u||$u['status']!=='active'||$u['role']==='Görüntüleyici')return false;
    if(user_is_admin($u))return true;
    foreach(['request','supplier','cost','finance','cash','docs','settings'] as $p)if(has_app_write_permission($p,$u))return true;
    return false;
}
function audit(string $action,?string $type=null,?string $id=null,$payload=null): void {
    try{$s=db()->prepare('INSERT INTO audit_log(user_id,action,entity_type,entity_id,payload_json) VALUES(?,?,?,?,?)');$s->execute([$_SESSION['user_id']??null,$action,$type,$id,$payload===null?null:json_encode($payload,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)]);}catch(Throwable $e){error_log('ASAY audit failed: '.$e->getMessage());}
}
function require_app_permission(string $perm, ?array $u=null): array {
    $u=$u?:current_user();
    if(!$u) json_response(['ok'=>false,'error'=>'Oturum gerekli'],401);
    if(!has_app_permission($perm,$u)) json_response(['ok'=>false,'error'=>'Bu modülü görüntüleme yetkiniz yok.'],403);
    return $u;
}
function require_app_write_permission(string $perm, ?array $u=null): array {
    $u=$u?:current_user();
    if(!$u)json_response(['ok'=>false,'error'=>'Oturum gerekli'],401);
    if(!has_app_write_permission($perm,$u))json_response(['ok'=>false,'error'=>'Bu işlem için yazma yetkiniz yok.'],403);
    return $u;
}
function public_error(Throwable $e,string $fallback='İşlem tamamlanamadı.'): string {
    error_log('ASAY '.$fallback.' '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine());
    return app_debug_enabled()?$e->getMessage():$fallback;
}
function sanitize_saved_html(string $html): string {
    $html=preg_replace('#<(script|style|iframe|object|embed|link|meta)[^>]*>.*?</\\1>#is','',$html)??'';
    $html=preg_replace('/\\son[a-z]+\\s*=\\s*("[^"]*"|\'[^\']*\'|[^\\s>]+)/i','',$html)??$html;
    $html=preg_replace('/javascript\\s*:/i','',$html)??$html;
    return $html;
}
function sanitize_state_value(mixed $v,?string $key=null): mixed {
    if(is_array($v)){foreach($v as $k=>$x)$v[$k]=sanitize_state_value($x,is_string($k)?$k:null);return $v;}
    if(!is_string($v))return $v;
    if($key==='html')return sanitize_saved_html($v);
    if($key!==null&&(str_ends_with(strtolower($key),'data')||in_array($key,['logo','seal','photo','photo_data'],true)))return $v;
    $v=str_replace("\0",'',strip_tags($v));
    return function_exists('mb_substr')?mb_substr($v,0,200000,'UTF-8'):substr($v,0,200000);
}
function filter_state_for_user(array $st,array $u): array {
    if(user_is_admin($u))return sanitize_state_value($st);
    $out=$st;
    $can=fn(string $p)=>has_app_permission($p,$u);
    if(!$can('request')){$out['requests']=[];$out['orders']=[];}
    if(!$can('supplier')){$out['supplierDirectory']=[];if(!$can('cost'))$out['suppliers']=[];}
    if(!$can('cost')){$out['selected']=[];$out['customerQuotes']=[];$out['quoteMarkup']=null;
        if(isset($out['suppliers'])&&is_array($out['suppliers']))foreach($out['suppliers'] as &$sp){unset($sp['offers'],$sp['offerBase'],$sp['vat'],$sp['vatGross'],$sp['freight'],$sp['fxSnapshot']);}unset($sp);
    }
    if(!$can('finance'))$out['accounts']=[];
    if(!$can('finance')&&!$can('cost'))$out['guarantees']=[];
    if(!$can('cash')){$out['cash']=[];$out['cashAccounts']=[];}
    if(!$can('docs')){$out['documents']=[];$out['requestAttachments']=[];}
    if(!$can('settings')){
        unset($out['roles'],$out['users']);
        if(isset($out['company'])&&is_array($out['company']))$out['company']=array_intersect_key($out['company'],array_flip(['name','brand','web','email','phone','address','footer']));
    }
    return sanitize_state_value($out);
}

function state_key_hash(mixed $value): string {
 // Match canonical state_json storage: 400 and 400.0 are the same amount.
 $json=json_encode($value,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
 return hash('sha256',$json===false?'null':$json);
}
function state_write_key_allowed(string $key,array $u): bool {
 $map=[
  'requests'=>'request','orders'=>'request',
  'suppliers'=>'supplier','supplierDirectory'=>'supplier',
  'selected'=>'cost','quoteMarkup'=>'cost','customerQuotes'=>'cost','won'=>'cost',
  'accounts'=>'finance','guarantees'=>'finance','cash'=>'cash','cashAccounts'=>'cash',
  'documents'=>'docs','requestAttachments'=>'docs','pdfColumnsByType'=>'docs','pdfBlocksByType'=>'docs','pdfFreeTextsByType'=>'docs','pdfRowCustom'=>'docs','checklistNotes'=>'docs',
  'company'=>'settings','roles'=>'settings','ui'=>'settings','appIdentity'=>'settings','requestNumber'=>'settings','requestUnits'=>'settings','pdfColumns'=>'settings',
 ];
 if($key==='tagCatalog')return has_app_write_permission('request',$u)||has_app_write_permission('finance',$u)||has_app_write_permission('settings',$u);
 if($key==='fx')return has_app_write_permission('request',$u)||has_app_write_permission('supplier',$u)||has_app_write_permission('cost',$u)||has_app_write_permission('settings',$u);
 $perm=$map[$key]??null;return $perm!==null&&has_app_write_permission($perm,$u);
}
function state_hashes_for_client(array $state,array $u): array {
 $out=[];foreach($state as $k=>$v)if(state_write_key_allowed((string)$k,$u))$out[(string)$k]=state_key_hash($v);return $out;
}

function business_module_keys(): array { return ['requests','suppliers','supplierDirectory','selected','orders','customerQuotes','documents','requestAttachments','accounts','guarantees','cashAccounts','cash']; }
function save_module_states(PDO $pdo,array $state,?int $userId=null,?array $changedKeys=null): void {
    $q=$pdo->prepare('INSERT INTO module_state(module_key,payload_json,revision,updated_by) VALUES(?,?,1,?) ON DUPLICATE KEY UPDATE payload_json=VALUES(payload_json),revision=revision+1,updated_by=VALUES(updated_by)');
    $keys=business_module_keys();if($changedKeys!==null){$want=array_flip(array_map('strval',$changedKeys));$keys=array_values(array_filter($keys,fn($k)=>isset($want[$k])));}
    foreach($keys as $k){$q->execute([$k,json_encode($state[$k]??[],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),$userId]);}
}
function overlay_module_states(PDO $pdo,array $state): array {
    try{$rows=$pdo->query('SELECT module_key,payload_json FROM module_state')->fetchAll();foreach($rows as $r){if(in_array($r['module_key'],business_module_keys(),true)){$v=json_decode((string)$r['payload_json'],true);if($v!==null)$state[$r['module_key']]=$v;}}}catch(Throwable $e){}
    return $state;
}


function enforce_api_mutation_csrf(): void {
    $method=strtoupper((string)($_SERVER['REQUEST_METHOD']??'GET'));
    $script=str_replace('\\','/',(string)($_SERVER['SCRIPT_NAME']??''));
    if(!str_contains($script,'/api/') || !in_array($method,['POST','PUT','PATCH','DELETE'],true))return;
    if(empty($_SESSION['user_id']))return; // endpoint auth keeps the canonical 401 response
    require_csrf();
}
enforce_api_mutation_csrf();
