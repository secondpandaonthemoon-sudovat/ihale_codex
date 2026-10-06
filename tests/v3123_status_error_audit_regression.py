from pathlib import Path
R=Path(__file__).resolve().parents[1]
app=(R/'assets/js/app.js').read_text(encoding='utf-8')
ops=(R/'api/operations.php').read_text(encoding='utf-8')
boot=(R/'app/bootstrap.php').read_text(encoding='utf-8')
css=(R/'assets/css/app.css').read_text(encoding='utf-8')
checks={
 'version': "'3.13.4'" in boot and 'V3.13.4' in boot,
 'all_status_options': "return Object.keys(labels).map" in app and "cancelled:'İptal Edildi'" in app,
 'legacy_status_normalize': "legacyStatusMap" in app and "canonical_request_status" in ops,
 'request_is_canonical': "r.status==='won'||r.customerQuote?.status==='Kazanıldı'" not in app and "r.customerQuote?.status==='Kazanıldı'||r.status==='won'" not in app,
 'quote_derived': 'request_quote_status($status)' in ops and "$r['customerQuote']['status']=request_quote_status($status)" in ops,
 'won_rollback_guard': 'order_real_activity_reason' in ops and 'unwind_win_commitments' in ops and 'reopen_win_commitments' in ops,
 'supplier_commitment_symmetry': 'tedarikçi borcu kapatıldı' in ops and 'tedarikçi borcu tekrar açıldı' in ops,
 'json_hygiene': "str_contains($__asayScript,'/api/')" in boot and "ini_set('display_errors','0')" in boot,
 'persistent_server_error': 'showServerError' in app and 'Sunucu / JSON Hatası' in app and 'server-error-overlay' in css,
 'error_copy': 'Hata Detayını Kopyala' in app,
 'warn_duration': "type==='warn'?9000:4200" in app,
}
for k,v in checks.items(): print(('PASS' if v else 'FAIL')+': '+k)
raise SystemExit(0 if all(checks.values()) else 1)
