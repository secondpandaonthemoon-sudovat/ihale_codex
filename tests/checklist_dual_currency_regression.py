from pathlib import Path
import sys
root=Path(__file__).resolve().parents[1]
js='\n'.join(p.read_text(encoding='utf-8') for p in (root/'assets/js').glob('*.js'))
checks={
 'supplier real currency': 'originalCurrency=supplierQuoteCurrency(sup,r)' in js,
 'eur snapshot conversion': "supplierConvertedAmount(sup,price,'EUR',false,r)" in js,
 'dual unit price': 'checklist-dual-price' in js and "fmtMoneyCur(orig,cur)" in js,
 'eur line': "EUR: ${fmtMoneyCur(eur,'EUR')}" in js,
 'supplier totals': 'checklistSupplierTotalsHtml(rows)' in js,
 'compact columns': "['unitPrice','Birim Fiyat (Orijinal / EUR)']" in js and "['total','Toplam (Orijinal / EUR)']" in js,
}
failed=[k for k,v in checks.items() if not v]
if failed:
 print('FAIL: '+'; '.join(failed));sys.exit(1)
print('PASS: checklist original currency + EUR regression guards')
