from pathlib import Path
root=Path(__file__).resolve().parents[1]
idx=(root/'index.php').read_text(encoding='utf-8')
app=(root/'assets/js/app.js').read_text(encoding='utf-8')
boot=(root/'app/bootstrap.php').read_text(encoding='utf-8')
state=(root/'api/state.php').read_text(encoding='utf-8')
ops=(root/'api/operations.php').read_text(encoding='utf-8')
checks={
 'version': "'3.13.4'" in boot and 'V3.13.4' in boot,
 'shared_queue': 'function enqueueDbMutation(fn)' in idx and 'window.enqueueDbMutation=enqueueDbMutation' in idx,
 'inline_flush': 'function savePendingStatePatchNow()' in idx and 'const flushed=await savePendingStatePatchNow()' in idx,
 'authoritative_queue': 'function runAuthoritativeDbMutation(fn)' in idx and 'window.runAuthoritativeDbMutation=runAuthoritativeDbMutation' in idx,
 'operations_serialized': 'window.runAuthoritativeDbMutation?await window.runAuthoritativeDbMutation(execute)' in app,
 'supplier_serialized': 'async function saveSupplierDirectoryServer' in app and 'return window.runAuthoritativeDbMutation?await window.runAuthoritativeDbMutation(execute):await execute()' in app,
 'server_state_baseline': 'window.acceptServerState(j.state||{},j.revision,j.keyHashes||{})' in app,
 'real_conflict_preserved': 'Aynı veri bölümünde başka bir oturum/kullanıcı gerçekten değişiklik yaptı' in idx and 'r.status===409' in idx,
 'schema': "'schema'=>'v3.13.4'" in state and "'schema'=>'v3.13.4'" in ops,
}
bad=[k for k,v in checks.items() if not v]
for k,v in checks.items(): print(('PASS' if v else 'FAIL')+': '+k)
raise SystemExit(1 if bad else 0)
