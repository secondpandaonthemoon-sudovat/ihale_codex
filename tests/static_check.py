from pathlib import Path
import re, subprocess, collections, sys
ROOT=Path(__file__).resolve().parents[1]
errors=[]
# PHP lint
for f in ROOT.rglob('*.php'):
    r=subprocess.run(['php','-l',str(f)],capture_output=True,text=True)
    if r.returncode: errors.append(f'PHP lint {f.relative_to(ROOT)}: {r.stderr or r.stdout}')
# JS syntax: external + inline without PHP
js_files=list((ROOT/'assets/js').glob('*.js'))
index=(ROOT/'index.php').read_text(encoding='utf-8')
for i,src in enumerate(re.findall(r'<script(?![^>]*\bsrc=)[^>]*>(.*?)</script>',index,re.S|re.I)):
    if '<?' not in src and src.strip():
        p=ROOT/'tests'/f'_inline_{i}.js';p.write_text(src,encoding='utf-8');js_files.append(p)
for f in js_files:
    r=subprocess.run(['node','--check',str(f)],capture_output=True,text=True)
    if r.returncode: errors.append(f'JS syntax {f.relative_to(ROOT)}: {r.stderr}')
for f in list((ROOT/'tests').glob('_inline_*.js')):
    try:f.unlink()
    except OSError:pass
# functions / handlers across app.js + inline
js='\n'.join(p.read_text(encoding='utf-8') for p in (ROOT/'assets/js').glob('*.js'))+'\n'+ '\n'.join(x for x in re.findall(r'<script(?![^>]*\bsrc=)[^>]*>(.*?)</script>',index,re.S|re.I) if '<?' not in x)
funcs=re.findall(r'\bfunction\s+([A-Za-z_$][\w$]*)\s*\(',js)
dup={k:v for k,v in collections.Counter(funcs).items() if v>1}
if dup: errors.append('Duplicate JS functions: '+repr(dup))
handlers=set()
for attr in re.findall(r'\bon(?:click|change|input|submit|blur|focus|keyup|keydown)\s*=\s*"([^"]*)"',index,re.I):
    handlers.update(re.findall(r'\b([A-Za-z_$][\w$]*)\s*\(',attr))
for attr in re.findall(r'\bon(?:click|change|input|submit|blur|focus|keyup|keydown)=\\?"([^"`]+)','\n'.join(p.read_text(encoding='utf-8') for p in (ROOT/'assets/js').glob('*.js')),re.I):
    handlers.update(re.findall(r'\b([A-Za-z_$][\w$]*)\s*\(',attr))
builtins={'if','for','while','confirm','alert','Number','String','Math','parseInt','parseFloat','setTimeout','closest','find','replaceAll','splice','stopPropagation','preventDefault','toUpperCase','toggle'}
missing=sorted(x for x in handlers if x not in set(funcs) and x not in builtins)
if missing: errors.append('Missing inline handler functions: '+', '.join(missing))
# fetch endpoints
alljs=index+'\n'+'\n'.join(p.read_text(encoding='utf-8') for p in (ROOT/'assets/js').glob('*.js'))
for url in sorted(set(re.findall(r"fetch\(\s*['\"]([^'\"]+)",alljs))):
    path=url.split('?')[0]
    if path.startswith('api/') and not (ROOT/path).exists(): errors.append('Missing API endpoint: '+url)

# Every handled backend operation must have an explicit permission-map entry.
ops=(ROOT/'api/operations.php').read_text(encoding='utf-8')
handled=set(re.findall(r"\$action\s*===\s*['\"]([A-Za-z0-9_]+)['\"]",ops))
perm_block=ops[ops.find('$actionPerms=['):ops.find('];',ops.find('$actionPerms=['))+2]
permitted=set(re.findall(r"['\"]([A-Za-z0-9_]+)['\"]\s*=>",perm_block))
missing_perms=sorted(handled-permitted)
if missing_perms: errors.append('Operations without explicit permission mapping: '+', '.join(missing_perms))
# User-facing forced saves must be awaited/queued; fire-and-forget true saves are regressions.
appjs='\n'.join(p.read_text(encoding='utf-8') for p in (ROOT/'assets/js').glob('*.js'))
for m in re.finditer(r'(?<!await )saveDbState\(true\)',appjs):
    context=appjs[max(0,m.start()-25):m.start()+30]
    if 'queueDbSave' not in context: errors.append('Unawaited saveDbState(true) near offset '+str(m.start()))

# mandatory V3.5 checks
checks={
 'filtered_state_load':'filter_state_for_user' in (ROOT/'api/state.php').read_text(),
 'delta_patch':'action===\'patch\'' in (ROOT/'api/state.php').read_text() and 'pendingStatePatch' in index,
 'write_permissions':'has_app_write_permission' in (ROOT/'app/bootstrap.php').read_text(),
 'viewer_fail_closed':"$u['role']==='Görüntüleyici'" in (ROOT/'app/bootstrap.php').read_text(),
 'safe_fx':'require_payment_fx' in (ROOT/'api/operations.php').read_text() and 'Geçerli TCMB USD/EUR kuru bulunamadı' in (ROOT/'api/operations.php').read_text(),
 'po_rules':(ROOT/'app/business_rules.php').exists(),
 'document_trash':'restore_from_trash' in (ROOT/'api/request_documents.php').read_text(),
 'full_restore_stage':'.restore-stage-' in (ROOT/'api/database_manager.php').read_text(),
 'file_backed_zip_restore':'dbm_restore_storage_zip' in (ROOT/'api/database_manager.php').read_text() and 'asay_zip_file_index' in (ROOT/'app/simple_zip.php').read_text(),
 'clamav_optional':'ASAY_CLAMAV_HOST' in (ROOT/'app/upload_security.php').read_text(),
 'health_admin':'require_admin()' in (ROOT/'api/health.php').read_text(),
 'supplier_api':(ROOT/'api/supplier_directory.php').exists(),
 'external_js':(ROOT/'assets/js/app.js').exists() and (ROOT/'assets/js/runtime.js').exists() and 'assets/js/app.js?v=' in index and 'assets/js/runtime.js?v=' in index,
 'external_css':(ROOT/'assets/css/app.css').exists() and (ROOT/'assets/css/components.css').exists() and 'assets/css/app.css?v=' in index and 'assets/css/components.css?v=' in index,
 'excel_widths':'columnWidths' in (ROOT/'api/export_xlsx.php').read_text(),
 'excel_request_subject':'excelReqTitle' in (ROOT/'assets/js/app.js').read_text() and "$meta['title']" in (ROOT/'api/operations.php').read_text(),
}
for k,v in checks.items():
    if not v: errors.append('Critical check failed: '+k)
# release should not have root legacy version notes
legacy=[p.name for p in ROOT.glob('V*.txt')]
if legacy: errors.append('Legacy changelog files remain at root: '+', '.join(legacy[:10]))
# smoke tests
r=subprocess.run(['php',str(ROOT/'tests/security_smoke.php')],capture_output=True,text=True)
if r.returncode: errors.append('Security smoke: '+(r.stderr or r.stdout))
r=subprocess.run(['php',str(ROOT/'tests/zip_stream_smoke.php')],capture_output=True,text=True)
if r.returncode: errors.append('ZIP stream smoke: '+(r.stderr or r.stdout))
if errors:
    print('\n'.join('FAIL: '+e for e in errors));sys.exit(1)
print('PASS: PHP/JS/static/security checks')
