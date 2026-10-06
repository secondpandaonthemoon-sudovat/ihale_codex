from pathlib import Path
r=Path(__file__).resolve().parents[1]
app=(r/'assets/js/app.js').read_text(encoding='utf-8')
idx=(r/'index.php').read_text(encoding='utf-8')
op=(r/'api/operations.php').read_text(encoding='utf-8')
boot=(r/'app/bootstrap.php').read_text(encoding='utf-8')
inst=(r/'install.php').read_text(encoding='utf-8')
checks={
 'version':"const ASAY_APP_VERSION = '3.13.4'" in boot and 'V3.13.4' in inst,
 'guarantee_tab':"reqTab(${r.id},'guarantee'" in app and 'requestGuaranteeHtml(r)' in app,
 'guarantee_global':'data-view="guarantees"' in idx and 'view-guarantees' in idx,
 'no_guarantee_cost_fixed':"['guarantee','Teminat']" not in app and "guarantee:cv('guarantee')" not in app,
 'no_guarantee_cost_backend':"'inland','guarantee'" not in op and "'guarantee'=>'Teminat'" not in op,
 'normalizer':'function normalizeStateSchema()' in app and 'normalizeStateSchema();restoreFreshFxCache();ensureRequestUnits()' in idx,
 'guarantee_state':"'accounts','guarantees','cashAccounts'" in boot and 'guarantees:[]' in app,
 'permission_map':"guarantees:'finance'" in idx and 'has_app_write_permission' in boot,
}
for k,v in checks.items(): print(('PASS' if v else 'FAIL')+': '+k)
raise SystemExit(0 if all(checks.values()) else 1)
