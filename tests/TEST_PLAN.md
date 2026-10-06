# ASAY ERP V3.5.0 — Canlı E2E Test Planı

1. **Login / Yetki**: Admin, Satış, Satın Alma, Finans, Görüntüleyici hesaplarıyla giriş; menü görünürlüğü ve server-side write reddi.
2. **Talep CRUD**: konu başlığı, kalemler, Excel tek talep import, düzenle, Kaydet ve Devam Et, durum değişimi.
3. **Tedarikçi Teklifi**: hızlı tedarikçi ekle, cari opsiyonu, KDV dahil/hariç, farklı para birimi, teknik uygunluk.
4. **Kıyaslama**: seçim, otomatik en uygun, uygunsuz teklif engeli, ek giderler, Excel export.
5. **Kazanıldı → Sipariş**: müşteri alacağı, PO borcu, para birimi, ödeme planı.
6. **PO State Machine**: yalnız izin verilen geçişler; Teslim Edildi → Hazırlanıyor reddi; partili teslimat kuralları.
7. **Finans**: tahsilat, tedarikçi ödeme, virman, ters kayıt, sistem kaynaklı cari hareketinin doğrudan edit reddi.
8. **Dokümanlar**: yükleme, dosya başı açıklama, özellik düzenleme, çöpe taşı, geri yükle, ZIP güvenlik limiti.
9. **Backup/Restore**: JSON, SQL, Tam Sistem; restore öncesi safety backup; storage staging rollback.
10. **Çok Kullanıcı**: iki tarayıcı ile farklı modüllerde eşzamanlı kayıt; farklı top-level modüllerde gereksiz conflict olmamalı, aynı modülde conflict uyarısı gelmeli.
11. **Responsive**: 1440px, 1024px, 768px, 390px; menü, modal, tablo, kıyaslama ve buton grupları.
12. **Hosting**: InfinityFree PHP extensions, upload/post/memory limits, TCMB erişimi, HTTPS cookie, Google Drive bağlantısı.
