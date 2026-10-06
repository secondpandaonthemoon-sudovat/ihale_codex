from pathlib import Path
root=Path(__file__).resolve().parents[1]
g=(root/'assets/js/guarantees.js').read_text(encoding='utf-8')
i=(root/'index.php').read_text(encoding='utf-8')
a=(root/'assets/js/app.js').read_text(encoding='utf-8')
checks={
 'request_helper':'function guaranteeRequestInfo' in g and "r?.title" in g,
 'table_header':'Talep / Konu Başlığı / Kurum' in i,
 'search_title':'ri.title' in g and 'Konu başlığı, müşteri' in i,
 'excel_title':"'Konu Başlığı'" in g,
 'pdf_title':'<th>Konu Başlığı</th>' in g,
 'dashboard_title':"rr?.title" in a,
}
for k,v in checks.items(): print(('PASS' if v else 'FAIL')+': '+k)
raise SystemExit(0 if all(checks.values()) else 1)
