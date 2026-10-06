ASAY ERP V3.9.1 TCMB KALICILIK PATCH

Mevcut V3.9.0 kurulumunun üzerine kopyalayın.
- Veritabanını silmez/değiştirmez.
- config/database.local.php içermez.
- config/drive.php içermez.
- Google Drive tokenı içermez.

Düzeltme:
1) TCMB kuru artık MySQL app_state.fx alanına kalıcı kaydedilir.
2) Sayfa açılışındaki MySQL/TCMB yarış durumu kaldırıldı.
3) localStorage içindeki daha yeni kur, eski DB state tarafından ezilmez.
4) Server operation sonrası kur bilgisi kaybolmaz.
5) Tedarikçi teklifindeki 24 saat kur doğrulaması stabil hale getirildi.
