from pathlib import Path
root=Path(__file__).resolve().parents[1]
app=(root/'assets/js/app.js').read_text(encoding='utf-8')
ops=(root/'api/operations.php').read_text(encoding='utf-8')
guar=(root/'assets/js/guarantees.js').read_text(encoding='utf-8')
css=(root/'assets/css/app.css').read_text(encoding='utf-8')
boot=(root/'app/bootstrap.php').read_text(encoding='utf-8')
checks={
 'version': "const ASAY_APP_VERSION = '3.13.4'" in boot,
 'supplier_auto_account': "$supplierAcc=&supplier_account" in ops,
 'hide_zero_supplier_lines': "if(!(Number(vi.entered||0)>0))return ''" in app,
 'comparison_net_basis_client': "const effective=net" in app and "Kıyas: KDV Hariç" in app,
 'comparison_net_basis_server': "supplier_vat_net" in ops and "comparison_auto_best" in ops,
 'auto_best_preserves_tab': "En uygun tedarikçiler KDV Hariç net fiyat" in app and "reqTab(rid,'comparison'" in app,
 'responsive_compare': ".compare-scroll{width:100%;max-width:100%;overflow-x:auto" in css and "position:sticky" in css,
 'legacy_fixed_cost_removed': "Eski EK MALİYETLER/Operasyon Giderleri alanı kaldırıldı" in app,
 'manual_cost_status': 'setCostLineRealizationStatus' in app and 'Ödeme Yap' in app and 'Kısmi' in app,
 'planned_line_finance_link': "plannedCostLineId" in app and "plannedCostLineId" in ops,
 'cost_quick_account_return': "maliyet sağlayıcı listesine eklendi" in app,
 'guarantee_return_context': "guaranteeReturnRequestId" in guar and "returnToGuaranteeRequest" in guar,
}
bad=[k for k,v in checks.items() if not v]
for k,v in checks.items(): print(('PASS' if v else 'FAIL')+': '+k)
raise SystemExit(1 if bad else 0)
