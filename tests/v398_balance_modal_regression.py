from pathlib import Path
root=Path(__file__).resolve().parents[1]
idx=(root/'index.php').read_text(encoding='utf-8')
app=(root/'assets/js/app.js').read_text(encoding='utf-8')
css=(root/'assets/css/app.css').read_text(encoding='utf-8')
rt=(root/'assets/js/runtime.js').read_text(encoding='utf-8')
checks={
 'dedicated_modal':'cashBalanceAdjustModal' in idx and 'cbaTarget' in idx and 'cbaReason' in idx,
 'no_prompt_balance':'function adjustCashBalance(id)' in app and 'openModal(\'cashBalanceAdjustModal\')' in app and 'appPrompt(`${a.name}' not in app,
 'save_action':'saveCashBalanceAdjustment' in app and "action:'cash_balance_adjustment'" in app,
 'viewport_center':'#cashBalanceAdjustModal{position:fixed!important;inset:0!important;z-index:30000!important' in css and 'align-items:center!important' in css,
 'system_dialog_body':'host.parentElement!==document.body' in rt,
}
for k,v in checks.items(): print(('PASS' if v else 'FAIL')+': '+k)
raise SystemExit(0 if all(checks.values()) else 1)
