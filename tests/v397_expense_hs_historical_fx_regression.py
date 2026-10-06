from pathlib import Path
root=Path(__file__).resolve().parents[1]
app=(root/'assets/js/app.js').read_text(encoding='utf-8')
docs=(root/'assets/js/documents.js').read_text(encoding='utf-8')
idx=(root/'index.php').read_text(encoding='utf-8')
tcmb=(root/'tcmb_proxy.php').read_text(encoding='utf-8')
boot=(root/'app/bootstrap.php').read_text(encoding='utf-8')
ops=(root/'api/operations.php').read_text(encoding='utf-8')
checks={
 'version': "'3.13.4'" in boot and 'V3.13.4' in boot,
 'expense_bank_required': 'Ödeme yapılacak Kasa / Banka hesabını seçin' in app and 'Ödemeyi Onayla ve Gerçekleştir' in idx,
 'expense_not_auto': 'yalnız bu ekranı açar' in idx and 'Cari ve Kasa/Banka seçilmeden finans hareketi oluşmaz' in app,
 'hs_editor': 'documentItemEditor' in idx and 'updateDocumentItemTradeData' in app and 'GTİP / HS Code' in idx,
 'hs_pdf_sync': "x.hs||x.gtip||''" in app and 'renderDocumentItemEditor()' in docs,
 'historical_tcmb': "$_GET['date']" in tcmb and 'tcmb_history' in tcmb and 'effective_date' in tcmb,
 'historical_fx_client': 'fetchHistoricalTcmb' in app and 'hydrateMissingTryFx' in app and 'entryNeedsHistoricalFx' in app and 'Hesaplanıyor…' in app,
 'entry_date_accuracy': '?string $entryDate=null' in ops and "add_entry($party,$ref,$desc,$amount,0,$date)" in ops,
}
for k,v in checks.items(): print(('PASS' if v else 'FAIL')+': '+k)
raise SystemExit(0 if all(checks.values()) else 1)
