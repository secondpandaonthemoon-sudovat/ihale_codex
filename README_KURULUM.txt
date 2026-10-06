ASAY ERP PHP + MySQL V3.7.0 — Stabilizasyon / Güvenlik / Bakım

GÜNCELLEME
1) Mevcut veritabanınızın yedeğini alın.
2) V3.7.0 dosyalarını uygulama klasörüne yükleyin.
3) Admin hesabıyla upgrade.php açın ve V3.7.0 migration işlemini bir kez çalıştırın.
4) Tarayıcı önbelleğini yenileyin.

V3.7.0
- Cari party_type: customer/supplier/public/private/customs/freight/service.
- Merkezi uygulama sürümü ve Europe/Istanbul timezone.
- Tüm API mutasyonlarında merkezi CSRF koruması.
- Aynı-origin fetch çağrılarında otomatik CSRF header.
- TCMB 15 dk sunucu cache + son başarılı kayıt fallback + login koruması.
- Google Drive token atomic write + 0600 izin.
- Native confirm/prompt yerine uygulama dialog katmanı.
- Runtime/security kodu assets/js/runtime.js dosyasına ayrıldı.
- V3.6.1 çift para birimli Check List, Packing List ve PDF düzeltmeleri korunur.
