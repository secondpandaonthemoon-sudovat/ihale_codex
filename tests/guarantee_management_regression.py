from pathlib import Path
r=Path(__file__).resolve().parents[1]
app=(r/'assets/js/app.js').read_text(encoding='utf-8')
g=(r/'assets/js/guarantees.js').read_text(encoding='utf-8')
idx=(r/'index.php').read_text(encoding='utf-8')
ops=(r/'api/operations.php').read_text(encoding='utf-8')
checks={
 'nav':'data-view="guarantees"' in idx,
 'view':'id="view-guarantees"' in idx,
 'modal':'id="guaranteeModal"' in idx,
 'state':'guarantees:[]' in app,
 'request_tab':'requestGuaranteeHtml' in g and "'guarantee'" in app,
 'presence':'setGuaranteePresence' in g and 'Teminat Var' in g and 'Teminat Yok' in g,
 'types':all(x in g for x in ['Geçici Teminat','Kesin Teminat','Ek Kesin Teminat','Avans Teminat','Banka Teminat Mektubu']),
 'auto_calc':'sales*rate/100' in g,
 'duration':'guaranteeDurationValue' in g and 'guaranteeExpiryDate' in g,
 'excel':'exportGuaranteesExcel' in g and 'api/export_xlsx.php' in g,
 'pdf':'printGuaranteesPdf' in g,
 'finance':'guarantee_deposit' in ops and 'guarantee_release' in ops,
 'permissions':"guarantee_save'=>['finance']" in ops and "has_app_write_permission('cash'" in ops,
 'dashboard':'guarantee_expiry' in app,
 'separate_from_cost':"['guarantee','Teminat']" not in app and "'guarantee'=>'Teminat'" not in ops and 'includeInCost' not in g,
 'disable_guard':'Aktif teminat kaydı varken Teminat Yok seçilemez' in ops,
}
for k,v in checks.items(): print(('PASS' if v else 'FAIL')+': '+k)
raise SystemExit(0 if all(checks.values()) else 1)
