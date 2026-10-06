from pathlib import Path
root=Path(__file__).resolve().parents[1]
app=(root/'assets/js/app.js').read_text(encoding='utf-8')
ops=(root/'api/operations.php').read_text(encoding='utf-8')
boot=(root/'app/bootstrap.php').read_text(encoding='utf-8')
checks={
 'version': "'3.13.4'" in boot and 'V3.13.4' in boot,
 'general_report_real_moves': "gerçek hareket satırlarını" in app and "data.rows.forEach" in app,
 'try_fx_helpers': "tryEntryEquivalents" in app and "Kur kaydı yok" in app and "EUR Karşılığı" in app and "USD Karşılığı" in app,
 'fx_snapshot_server_entry': "function add_entry" in ops and "fxSnapshot" in ops and "global $s" in ops,
 'accounts_report_dates': "type==='accounts'" in app and "from.style.display='';to.style.display=''" in app,
 'statement_exports_fx': "EUR Karşılığı;USD Karşılığı;İşlem Kuru" in app,
}
bad=[k for k,v in checks.items() if not v]
for k,v in checks.items(): print(('PASS' if v else 'FAIL')+': '+k)
raise SystemExit(1 if bad else 0)
