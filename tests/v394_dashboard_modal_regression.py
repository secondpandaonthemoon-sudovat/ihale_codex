from pathlib import Path
root=Path(__file__).resolve().parents[1]
idx=(root/'index.php').read_text(encoding='utf-8')
app=(root/'assets/js/app.js').read_text(encoding='utf-8')
css=(root/'assets/css/app.css').read_text(encoding='utf-8')
runtime=(root/'assets/js/runtime.js').read_text(encoding='utf-8')
boot=(root/'app/bootstrap.php').read_text(encoding='utf-8')
checks={
 'version':"'3.13.4'" in boot and 'V3.13.4' in boot,
 'dashboard_guarantee':'dashGuaranteeActive' in idx and 'dashboardGuaranteeList' in idx and 'Teminat Takip’e Git' in idx,
 'dashboard_guarantee_logic':'guaranteeActive' in app and 'dashGuaranteeExpired' in app and 'dashboard-guarantee-row' in app,
 'modal_layer':'.modal-backdrop{z-index:10000!important' in css and '.app-dialog-backdrop{z-index:20000!important' in css,
 'checkbox_hitarea':'label:has(input[type="checkbox"])' in css and 'min-height:36px' in css,
 'body_modal_state':"classList.add('ui-modal-open')" in app and "classList.add('ui-system-dialog-open')" in runtime,
}
failed=[k for k,v in checks.items() if not v]
for k,v in checks.items():print(('PASS' if v else 'FAIL')+': '+k)
raise SystemExit(1 if failed else 0)
