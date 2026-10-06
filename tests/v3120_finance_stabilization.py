from pathlib import Path
root=Path(__file__).resolve().parents[1]
ops=(root/'api/operations.php').read_text(encoding='utf-8')
app=(root/'assets/js/app.js').read_text(encoding='utf-8')
idx=(root/'index.php').read_text(encoding='utf-8')
st=(root/'api/state.php').read_text(encoding='utf-8')
checks={
'version':'3.13.4' in (root/'app/bootstrap.php').read_text(encoding='utf-8'),
'guarantee_release_date':"$date=trim((string)($in['date']" in ops and "$g['releaseDate']=$date" in ops,
'entry_explicit_fx':'?array $fxSnapshot=null' in ops and 'finance_fx_snapshot' in ops,
'legacy_batch_disabled':'Legacy parti tahsilat akışı kapatıldı' in ops and 'Legacy parti tedarikçi ödeme akışı kapatıldı' in ops,
'legacy_extra_disabled':'Legacy Ek Maliyetler kaydı kapatıldı' in ops,
'partial_expense':'costLineRemaining' in app and 'reverseCostLinePayments' in app and 'Kısmi' in app,
'invoice_due':'cerInvoiceNo' in idx and 'cerDueDate' in idx and 'attachmentId' in ops,
'expense_rate_visible':'usedPaymentRate' in app and '<th>Kur</th>' in app,
'dashboard_expense_due':'operational_expense_due' in app,
'account_role':'accNewRole' in idx and 'serviceRole' in app,
'canonical_state':"'canonical'=>'app_state'" in st and "'canonical'=>'app_state'" in ops,
'package_clean':not (root/'test_write').exists(),
}
bad=[k for k,v in checks.items() if not v]
for k,v in checks.items(): print(('PASS' if v else 'FAIL')+': '+k)
raise SystemExit(1 if bad else 0)
