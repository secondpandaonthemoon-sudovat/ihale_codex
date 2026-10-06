# ASAY V3.13.4-P2 — Evrak ve etiket düzeltmeleri

## Düzeltmeler

- **Packing List ondalık giriş:** Miktar, birim net ve birim brüt alanları her tuşta yeniden oluşturulmuyor. Virgül ve nokta ile giriş destekleniyor; toplamlar ve PDF eşzamanlı güncelleniyor. `0,569` ve `1,234` değerleri tuş tuş girilerek, odağın korunması ve toplamlar test edildi.
- **Kaydedilmiş evrakları silme:** Sil düğmesi ve onay eklendi. Sunucuda docs yazma yetkisi ve CSRF kontrolü yapılır; seçilen evrak canonical state, module_state ve commercial_documents tablosundan birlikte kaldırılır. Diğer evraklar ve talepler korunur; audit kaydı tutulur.
- **Tekrar kaydetmede çoğalma:** Kaydedilen evrak düzenleme modunda tutulur; sonraki kayıt aynı evrakı günceller. Eşzamanlı çift tıklama engellenir. Yeni belge için ayrı **+ Yeni Belge** düğmesi bulunur.
- **Merkezi etiket yönetimi:** Etiketler katalogdan ve eski cari/talep kayıtlarından sunucuda toplanır. Kullanımdaki etiketin adı, rengi ve kategorisi düzenlenebilir; bağlantılar birlikte güncellenir. Silme onayında kullanım sayısı gösterilir; yalnız etiket bağlantıları kaldırılır, cari/talep ve finans kayıtları korunur. Hatalı yükleme boş liste gibi gösterilmez.
- **Özel PDF sütunları:** Başlık ve sabit değer düzenlemesi korunur; Sil düğmesi ve onay eklendi. Sütun silindiğinde ona ait özel satır değerleri de kaldırılır.
- **PDF blokları:** Blok adı düzenleme, yukarı/aşağı taşıma, silme ve varsayılan blokları geri getirme eklendi. Blok adları/sırası kayıtlı evrak düzenine dahil edilir ve tekrar açıldığında korunur. Blok adını değiştirmek, belgede oluşturulan içerik metnini değiştirmez; içerik ilgili belge alanlarından düzenlenir.
- **Önbellek:** V3.13.4-p2 varlık sürümü ile güncel JS/CSS yüklenir.

## Doğrulama

Playwright ile gerçek yazma, alan odağı, ondalık toplamları, arka arkaya/eşzamanlı kayıt, sütun düzenleme/silme, blok düzenleme/silme/geri getirme, eski etiket keşfi, kullanımdaki etiket yeniden adlandırma/silme ve evrak silmenin yenileme sonrası korunması doğrulandı. Eksik CSRF ile etiket mutasyonu reddedildi. P1 ödeme/PDF testleri de çalıştırıldı. Yakalanmamış tarayıcı hatası yok.

PHP/JS/statik kontroller, PDF geometri kontrolü, sayısal state hash kontrolü ve canlı MySQL şema kontrolü geçti. Üretim veritabanı üzerinde işlem yapılmadı.

PHP 8.4/MariaDB 11.8 Docker ortamı ayrıca oluşturuldu; izole boş veritabanında ilk kurulum, giriş, state yükleme ve etiket API erişimi doğrulandı. Bu çalışma ortamının ağ geçidi için yalnız yerel test yapılandırmasında platformun güvenilir sertifikası eklendi; TLS doğrulaması kapatılmadı. GitHub hesabında bir Codespace henüz oluşturulmadı.

## GitHub üzerinden geliştirme ön izlemesi

Depodaki `.devcontainer` yapılandırması GitHub Codespaces için PHP 8.4 ve MariaDB 11.8 servislerini hazırlar. GitHub'da **Code → Codespaces → Create codespace on main** ile açın. Ports bölümündeki **8080 / ASAY canlı geliştirme** bağlantısını kullanın. İlk açılışta boş `asay_preview` veritabanına yönetici hesabı oluşturmanız gerekir. Kurulumdaki veritabanı şifresi `asay_preview_dev_only` değeridir. Varsayılan ön izleme veritabanı bilgileri yalnız izole geliştirme ortamı içindir; canlı veritabanınızı kullanmayın.

Kaynak değişiklikleri bu ortamda çalışan uygulamada görünür. Başka bir Codespace açıkken GitHub main güncellendiğinde Terminal'de `git pull --ff-only` çalıştırın; yerel değişiklik varsa önce onları koruyun. GitHub kaynak koduna gönderim tek başına açık Codespace'i veya mevcut canlı sunucuyu güncellemez.

Bu, kalıcı üretim yayını değildir. Gerçek canlı siteye dağıtım için PHP/MySQL sunucusunun hedefi ve erişimi gerekir.
