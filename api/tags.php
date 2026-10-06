<?php
declare(strict_types=1);
require_once __DIR__.'/../app/bootstrap.php';
require_login();
$u=current_user();
if(!has_app_permission('settings',$u))json_response(['ok'=>false,'error'=>'Etiket yönetimi için Ayarlar erişimi gerekli.'],403);
function catalog_tag_name(mixed $t): string {return is_array($t)?(string)($t['name']??$t[0]??''):(is_string($t)?$t:'');}
function catalog_from_state(array $s): array {
 $map=[];
 foreach(array_merge($s['tagCatalog']??[],...array_map(fn($r)=>$r['tags']??[],array_merge($s['requests']??[],$s['accounts']??[]))) as $t){
  $name=trim(catalog_tag_name($t));if($name===''||isset($map[$name]))continue;
  $color=is_array($t)?(string)($t['color']??$t[1]??'#667085'):'#667085';
  $map[$name]=['name'=>$name,'color'=>preg_match('/^#[0-9a-f]{6}$/i',$color)?$color:'#667085','category'=>is_array($t)?(string)($t['category']??$t[2]??'Genel'):'Genel'];
 }return array_values($map);
}
function catalog_usage(array $s): array {
 $counts=[];$parties=[];
 foreach(($s['requests']??[]) as $r)foreach(array_unique(array_map('catalog_tag_name',$r['tags']??[])) as $name)if($name!=='')$counts[$name]=($counts[$name]??0)+1;
 foreach(($s['accounts']??[]) as $a){$key=(string)($a['partyKey']??(($a['type']??'customer').'|'.($a['name']??'')));foreach(($a['tags']??[]) as $t){$name=catalog_tag_name($t);if($name!=='')$parties[$key][$name]=true;}}
 foreach($parties as $names)foreach(array_keys($names) as $name)$counts[$name]=($counts[$name]??0)+1;
 return $counts;
}
try{
 $pdo=db();$method=$_SERVER['REQUEST_METHOD'];
 if($method==='GET'){$row=$pdo->query('SELECT state_json FROM app_state WHERE id=1')->fetch();$s=$row?json_decode($row['state_json'],true):[];json_response(['ok'=>true,'catalog'=>catalog_from_state($s?:[]),'usage'=>catalog_usage($s?:[])]);}
 if($method!=='POST')json_response(['ok'=>false,'error'=>'Desteklenmeyen yöntem.'],405);
 if(!has_app_write_permission('settings',$u))json_response(['ok'=>false,'error'=>'Etiketleri değiştirme yetkiniz yok.'],403);
 require_csrf();$in=json_decode((string)file_get_contents('php://input'),true);if(!is_array($in))json_response(['ok'=>false,'error'=>'Geçersiz JSON.'],422);
 $action=(string)($in['action']??'');if(!in_array($action,['upsert','delete'],true))json_response(['ok'=>false,'error'=>'Geçersiz etiket işlemi.'],422);
 $pdo->beginTransaction();$row=$pdo->query('SELECT revision,state_json FROM app_state WHERE id=1 FOR UPDATE')->fetch();if(!$row)throw new RuntimeException('Uygulama verisi bulunamadı.');$s=json_decode($row['state_json'],true)?:[];
 $catalog=catalog_from_state($s);$old=trim((string)($in['oldName']??''));$index=null;foreach($catalog as $i=>$t)if($t['name']===$old){$index=$i;break;}
 if($old!==''&&$index===null)throw new RuntimeException('Etiket bulunamadı; listeyi yenileyin.');
 $name=$old;$color='#667085';$category='Genel';
 if($action==='upsert'){
  $name=trim(strip_tags((string)($in['name']??'')));$category=trim(strip_tags((string)($in['category']??'')))?:'Genel';$color=(string)($in['color']??'#667085');
  if($name===''||mb_strlen($name)>80||mb_strlen($category)>80||!preg_match('/^#[0-9a-f]{6}$/i',$color))throw new RuntimeException('Etiket adı, kategori veya renk geçersiz.');
  foreach($catalog as $t)if($t['name']!==$old&&mb_strtolower($t['name'])===mb_strtolower($name))throw new RuntimeException('Bu etiket adı zaten var.');
  $tag=['name'=>$name,'color'=>$color,'category'=>$category];if($index===null)$catalog[]=$tag;else $catalog[$index]=$tag;
 }else{if($index===null)throw new RuntimeException('Silinecek etiket bulunamadı.');array_splice($catalog,$index,1);}
 $keys=['tagCatalog'];
 if($old!=='')foreach(['requests','accounts'] as $module){$changed=false;foreach(($s[$module]??[]) as $i=>$entity){$tags=[];foreach(($entity['tags']??[]) as $t){if(catalog_tag_name($t)!==$old){$tags[]=$t;continue;}$changed=true;if($action==='delete')continue;$tags[]=is_array($t)?(array_is_list($t)?[$name,$color,$category]:array_replace($t,['name'=>$name,'color'=>$color,'category'=>$category])):$name;}if($tags!==($entity['tags']??[]))$s[$module][$i]['tags']=$tags;}if($changed)$keys[]=$module;}
 $s['tagCatalog']=array_values($catalog);$rev=(int)$row['revision']+1;
 $pdo->prepare('UPDATE app_state SET state_json=?,revision=?,updated_by=? WHERE id=1')->execute([json_encode($s,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR),$rev,(int)$u['id']]);save_module_states($pdo,$s,(int)$u['id'],$keys);$pdo->commit();
 audit('tag_'.$action,'tag',$old?:$name,['name'=>$name,'oldName'=>$old,'affectedModules'=>$keys]);
 json_response(['ok'=>true,'catalog'=>$s['tagCatalog'],'usage'=>catalog_usage($s),'state'=>filter_state_for_user($s,$u),'revision'=>$rev,'keyHashes'=>state_hashes_for_client($s,$u)]);
}catch(Throwable $e){if(isset($pdo)&&$pdo->inTransaction())$pdo->rollBack();json_response(['ok'=>false,'error'=>public_error($e,'Etiket işlemi başarısız.')],422);}
