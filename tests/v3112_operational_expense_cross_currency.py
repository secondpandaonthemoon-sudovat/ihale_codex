from pathlib import Path
root=Path(__file__).resolve().parents[1]
app=(root/'assets/js/app.js').read_text(encoding='utf-8')
ops=(root/'api/operations.php').read_text(encoding='utf-8')
idx=(root/'index.php').read_text(encoding='utf-8')
boot=(root/'app/bootstrap.php').read_text(encoding='utf-8')
checks={
 'version': "'3.13.4'" in boot and 'V3.13.4' in boot,
 'all_banks': "const banks=(state.cashAccounts||[])" in app,
 'preview': 'cerPaymentPreview' in idx and 'syncComparisonExpensePaymentPreview' in app,
 'historical_fx': 'fetchHistoricalTcmb(date)' in app,
 'backend_conversion': 'fx_convert(1,$cur,$bankCur,$fxSnapshot)' in ops and '$bankAmount=round($amount*$usedPaymentRate,2)' in ops,
 'bank_amount_payload': "'bankAmount'=>$bankAmount" in ops and "'bankCurrency'=>$bankCur" in ops,
 'expense_kept_original': "'amount'=>$amount,'currency'=>$cur,'paymentAmount'=>$bankAmount" in ops,
 'reversal_bank_currency': "'currency'=>$bank['currency']??$jr['currency']" in ops,
}
bad=[k for k,v in checks.items() if not v]
for k,v in checks.items(): print(('PASS' if v else 'FAIL')+': '+k)
raise SystemExit(1 if bad else 0)
