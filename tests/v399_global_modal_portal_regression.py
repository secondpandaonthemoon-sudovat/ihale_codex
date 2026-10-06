from pathlib import Path
root=Path(__file__).resolve().parents[1]
app=(root/'assets/js/app.js').read_text(encoding='utf-8')
css=(root/'assets/css/app.css').read_text(encoding='utf-8')
runtime=(root/'assets/js/runtime.js').read_text(encoding='utf-8')
boot=(root/'app/bootstrap.php').read_text(encoding='utf-8')
checks={
 'version': "'3.13.4'" in boot and 'V3.13.4' in boot,
 'portal_helper': 'function portalModalToBody' in app and "document.body.appendChild(m)" in app,
 'open_uses_portal': "const m=portalModalToBody(document.getElementById(id))" in app,
 'dynamic_modal_observer': 'MutationObserver' in app and 'initializeModalPortals' in app,
 'all_modal_center': 'z-index:40000!important' in css and 'align-items:center!important' in css and 'justify-content:center!important' in css,
 'mobile_center': '@media(max-width:640px)' in css and '.modal-backdrop{align-items:center!important;justify-content:center!important' in css,
 'system_dialog_body': 'document.body.appendChild(host)' in runtime and 'app-dialog-backdrop' in css and 'z-index:42000!important' in css,
}
for k,v in checks.items(): print(('PASS' if v else 'FAIL')+': '+k)
raise SystemExit(0 if all(checks.values()) else 1)
