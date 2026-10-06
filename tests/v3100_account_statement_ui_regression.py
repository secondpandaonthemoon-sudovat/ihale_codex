from pathlib import Path
root=Path(__file__).resolve().parents[1]
app=(root/'assets/js/app.js').read_text(encoding='utf-8')
css=(root/'assets/css/app.css').read_text(encoding='utf-8')
boot=(root/'app/bootstrap.php').read_text(encoding='utf-8')
checks={
 'version': "'3.13.4'" in boot and 'V3.13.4' in boot,
 'single_no_txn_user': '<th>İşlem ID</th>' not in app[app.find('function renderAccountStatement'):app.find('function accountEntryEditable') if app.find('function accountEntryEditable')>0 else app.find('function renderAccounts')],
 'general_headers_simplified': "const headers=['Tarih','Cari Tipi','Firma / Kurum','Döviz','Referans','Açıklama','Borç','Alacak','EUR Karşılığı','USD Karşılığı','İşlem Kuru','Bakiye','Durum']" in app,
 'debt_status': "weOwe=currentBalance<-0.001" in app and "'BORÇLUYUZ'" in app,
 'red_class': 'report-we-owe' in css and 'report-debt-mini' in css,
 'row_classes': 'renderReportTable(headers,rows,rowClasses=[]' in app,
}
for k,v in checks.items(): print(('PASS' if v else 'FAIL')+': '+k)
raise SystemExit(0 if all(checks.values()) else 1)
