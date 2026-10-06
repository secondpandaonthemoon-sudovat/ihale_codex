from pathlib import Path
root=Path(__file__).resolve().parents[1]
app=(root/'assets/js/app.js').read_text(encoding='utf-8')
ops=(root/'api/operations.php').read_text(encoding='utf-8')
status=(root/'api/operation_status.php').read_text(encoding='utf-8')
health=(root/'api/health.php').read_text(encoding='utf-8')
checks={
 'reconcile_uncertain':'reconcileUncertainServerOperation' in app and 'operation_status.php?key=' in app,
 'idempotency_status':'operation_idempotency' in status and "'committed'=>true" in status,
 'bank_fingerprint_client':"bankName:bank.name,bankCurrency:bank.currency" in app,
 'bank_fingerprint_server':'cash_account_resolve' in ops and "in['bankName']" in ops and "in['bankCurrency']" in ops,
 'duplicate_id_guard':'count($idMatches)>1' in ops and 'hesap kimliği çakışıyor' in ops,
 'balance_error_refresh':"/bakiye|hesap kimliği|Kasa\\/Banka/i.test(msg)" in app and 'loadDbState' in app,
 'health_duplicate_counter':'cash_account_duplicate_ids' in health,
 'ledger_reconcile':'cash_ledger_balance' in ops and 'cash_balance_auto_reconcile' in ops,
}
bad=[]
for k,v in checks.items():print(('PASS' if v else 'FAIL')+': '+k);bad.append(k) if not v else None
raise SystemExit(1 if bad else 0)
