from pathlib import Path
r=Path(__file__).resolve().parents[1]
a=(r/'assets/js/app.js').read_text(encoding='utf-8'); o=(r/'api/operations.php').read_text(encoding='utf-8'); i=(r/'index.php').read_text(encoding='utf-8')
checks={'+ Müşteriden Tahsil Et' in a,'+ Tedarikçiye Ödeme Yap' in a,'orderFinanceSummaryHtml' in a,'payPercent' in i,'prepayBatch' in i,'payBatch' in i,'Bu PO için partili teslimat aktif' not in o}
print('PASS' if all(checks) else 'FAIL')
raise SystemExit(0 if all(checks) else 1)
