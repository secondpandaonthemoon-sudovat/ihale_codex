from pathlib import Path
root=Path(__file__).resolve().parents[1]
app=(root/'assets/js/app.js').read_text(encoding='utf-8')
ops=(root/'api/operations.php').read_text(encoding='utf-8')
boot=(root/'app/bootstrap.php').read_text(encoding='utf-8')
checks={
'version': "'3.13.4'" in boot and 'V3.13.4' in boot,
'grouped_candidates': "groupedAccounts().filter(g=>g.type!=='customer')" in app,
'all_party_types_visible': 'operationalExpensePartyCandidates' in app and 'accountTypeLabel(g.type)' in app,
'missing_currency_visible': 'hesabı ödeme sırasında otomatik açılır' in app,
'party_key_payload': 'partyKey:' in app and "v.startsWith('pk:')" in app,
'backend_auto_currency_account': 'function &operational_expense_account' in ops and 'autoCreatedCurrencyAccount' in ops,
'customer_excluded': "$type==='customer'" in ops and "party['type']??''" in ops and "==='customer'" in ops,
}
bad=[k for k,v in checks.items() if not v]
for k,v in checks.items(): print(('PASS' if v else 'FAIL')+': '+k)
raise SystemExit(1 if bad else 0)
