# ASAY İhale & Teklif OS

PHP + MySQL/MariaDB uygulaması. Bu depo, yüklenen ASAY arşivindeki kaynak kodunu ve V3.13.4-P1 düzeltmelerini içerir.

## Gereksinimler ve çalıştırma

PHP 8.4, PDO MySQL, mbstring, XML ve ZIP uzantıları ile MySQL/MariaDB gereklidir. PHP 8.4 / MariaDB 11.8 üzerinde test edilmiştir.

1. `config/drive.example.php` dosyasını `config/drive.php` olarak kopyalayın. Google Drive kullanmayacaksanız boş varsayılan ayarları bırakabilirsiniz.
2. MySQL/MariaDB sunucusunu çalıştırın. Uygulama ayarlarını kurulum ekranında belirtin; yerel ayarlar `config/database.local.php` dosyasına kaydedilir.
3. Depo kökünde `php -S 127.0.0.1:8080` komutunu çalıştırın.
4. Tarayıcıda yerel `install.php` ekranını açın. Yeni ve boş bir geliştirme veritabanı kullanın; mevcut üretim verisini sıfırlamayın.
5. Oluşturduğunuz yönetici hesabıyla giriş yapın.

Veritabanı bağlantı bilgileri, Google Drive OAuth ayarları, müşteri evrakları ve veri yedekleri Git'e eklenmez. `storage` klasörünü uygulama kullanıcısının yazabileceği şekilde oluşturun; `.htaccess` korunmalıdır. Üretim dağıtımında PHP'nin geliştirme sunucusu yerine PHP destekli bir web sunucusu kullanın. Apache dışındaki sunucularda `storage` ve `config` dizinlerine HTTP erişimini ayrıca engelleyin.

## Canlı ön izleme

GitHub kaynak kodunu barındırır. GitHub Pages PHP ve MySQL çalıştırmadığı için bu uygulamanın canlı ön izlemesini tek başına sunamaz. Canlı uygulama için PHP + MySQL/MariaDB destekleyen bir sunucuya dağıtım ve veritabanı bağlantısı gerekir. Bu depo henüz bir canlı dağıtım servisine bağlı değildir.

## Düzeltmeler ve doğrulama

[Düzeltmeler, bulunan hatalar ve kalan kontroller](docs/INCELEME_V3_13_4_P1.md)

```sh
python tests/static_check.py
python tests/pdf_parity_check.py
php tests/state_hash_roundtrip.php
ASAY_E2E_DB=1 php tests/e2e_db_contract.php
```

[İzole veritabanında tarayıcı testleri](tests/BROWSER_VALIDATION.md). Tarayıcı fixture testleri iş verisini değiştirir; üretim veritabanında çalıştırmayın.

## Test ortamından ekran görüntüleri

Bu görüntüler örnek verilerle çalışan uygulamadan alınmıştır; canlı site bağlantısı değildir.

![Ayarlar ve etiket yönetimi](docs/screenshots/ayarlar-p2.png)

[Packing List ve yeni düzenleme düğmeleri](docs/screenshots/packing-p2.png) · [PDF stüdyosu](docs/screenshots/pdf-studyosu.png) · [Ödeme takibi](docs/screenshots/odeme-takibi.png) · [Mobil ayarlar](docs/screenshots/ayarlar-mobil.png)

## GitHub Codespaces ile uygulamayı görerek geliştirme

[Codespaces ön izleme ortamını aç](https://github.com/codespaces/new?hide_repo_select=true&ref=main&repo=secondpandaonthemoon-sudovat%2Fihale_codex)

Alternatif: **Code → Codespaces → Create codespace on main**. PHP ve MariaDB servisleri `.devcontainer` üzerinden başlar. **Ports → 8080 → Open in Browser** ile uygulamayı açın. İlk kullanımda `install.php` üzerinden boş geliştirme veritabanına yönetici hesabı oluşturun; host `db`, veritabanı ve kullanıcı `asay_preview` olarak hazır gelir. Veritabanı şifresi alanına `asay_preview_dev_only` yazın; yönetici hesabı için kendi şifrenizi seçin. Bu geliştirme veritabanı üretim verisinden ayrıdır. Codespaces kullanımınız GitHub hesabınızın kota ve koşullarına bağlıdır.

Sonraki GitHub güncellemelerini açık Codespace'e almak için, yerel değişikliklerinizi koruyarak `git pull --ff-only` çalıştırın. Ön izleme portunu özel tutun.

[V3.13.4-P2 evrak ve etiket düzeltmeleri](docs/INCELEME_V3_13_4_P2.md)
