# Tarayıcı doğrulaması

`browser_regression.cjs` yalnız izole bir yerel test veritabanı içindir. Talep, cari, sipariş, evrak ve kasa fixture verilerini değiştirir. Üretim veritabanında çalıştırmayın.

Gereksinimler: PHP 8.4 + PDO MySQL, MariaDB, Chromium ve Playwright. Boş test veritabanına `install.php` ile kurulum yapın; test yöneticisinin e-postası `test@example.invalid` olmalıdır. Betik bu test kullanıcısına her çalışmada rastgele şifre atar; şifreyi yazdırmaz veya dosyaya kaydetmez. Yerel PHP sunucusu `127.0.0.1:8080` üzerinde çalışmalıdır.

Bu bulut ortamında:

```sh
cd /workspace/ihale_codex
ASAY_E2E_LOCAL=1 \
ASAY_PHP_BIN=/workspace/toolchain/bin/php \
ASAY_PLAYWRIGHT_MODULE=/workspace/browser-tests/node_modules/playwright \
node tests/browser_regression.cjs
```

Doğrulanan davranışlar:

- Etiket oluşturma ve yeniden yüklemeden sonra kalıcılık.
- Aktif, iptal, kaybedilen ve tüm talepler filtreleri.
- Cari etiketlerinin farklı döviz hesaplarında korunması.
- Gerçek tahsilatın ödeme planına dağıtılması.
- Tahsilat düzenleme ve silmede sipariş/banka/plan tutarlılığı.
- Teslimatın geri alınması ve iptal talebin yeniden açılması.
- Gerçek tedarikçi ödemesi, kalan borç, ödeme tarihi ve geçmişi.
- Kaydedilmiş logo sürükleme, boyutlandırma ve yeniden yükleme.
- Dikey PDF logo ve yatay çok sayfalı PDF kaşe koordinat eşleşmesi.
- Mobil ayarlar görünümü ve yakalanmamış tarayıcı hataları.

Ekran görüntüleri, test PDF'si ve çalıştırma çıktıları `/workspace/artifacts` altında oluşur.

P2 akışları: Packing List'te `0,569` / `1,234` / `12,5` değerlerinin tuş tuş girilmesi ve odağın korunması; çift kayıtla belge çoğaltılmaması; özel sütun düzenleme/silme; PDF blok ad/sıra/silme/geri getirme ve kayıtlı düzenin yeniden yüklenmesi; katalogda bulunmayan eski cari etiketinin görüntülenmesi, kullanımdaki etiketin yeniden adlandırılması/silinmesi; CSRF reddi; yalnız seçilen kaydedilmiş evrakın kalıcı silinmesi.
