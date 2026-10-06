# ASAY V3.13.4-P1 — Düzeltmeler ve inceleme

Yüklenen `asay.zip` içindeki uygulama esas alınmıştır. Kullanıcının üretim veritabanı bu arşivde bulunmadığından doğrulama ayrı yerel MariaDB üzerinde sentetik kayıtlarla yapılmıştır. Rapor, istenen iş akışlarının incelemesidir; tüm sistemin kapsamlı güvenlik veya muhasebe denetimi değildir.

## Bulunan ve düzeltilen hatalar

| Hata | Düzeltme |
|---|---|
| Aktif filtre iptal/kayıp durumlarını dışlamıyordu. | Aktif ve diğer özel filtrelerden kapalı durumlar çıkarıldı; İptal Edilenler ve Kaybedilenler filtreleri eklendi. Tümü bütün kayıtları gösterir. |
| Farklı dövizlerdeki cari etiketleri ilk dolu hesapla sınırlıydı; kartta yalnız iki etiket gösteriliyordu. | Etiketler cari grubu genelinde birleştirildi, tüm etiketler okunabilir boyutta gösterildi. Uygula işlemi bütün döviz hesaplarını günceller. |
| Ayarlarda etiket kataloğunu yöneten bir bölüm yoktu. | Ayrı Etiket Yönetimi kategorisi; arama, ekleme, renk/kategori düzenleme, kullanım sayısı ve kullanılmayan etiketi silme eklendi. Kullanımdaki etiket adını değiştirme/silme korunur. |
| Etiket seçim penceresi isimleri doğrudan HTML ve inline olay koduna yerleştiriyordu. | Güvenli DOM metinleri ve olay dinleyicileri kullanıldı. |
| Kaydedilmiş evrakın tüm A4 HTML'si documentPreview içine yerleştiriliyor, logo/kaşe kimlikleri çoğalıyordu. | Logo/kaşe gerçek A4 kapsayıcısına geri yüklenir; içerik ayrı yüklenir; sürükleme ve boyut kontrolleri yeniden bağlanır. |
| Logo baskıda ve ekranda zorunlu maksimum boyuta takılıyordu. | Boyut sınırını dayatan stiller kaldırıldı. Boyut, yatay/dikey konum alanları ve eşzamanlı değer gösterimi eklendi. |
| Çok sayfalı PDF kenar boşlukları A4'e göre kaydedilen koordinatları kaydırıyordu. | Baskı ve editör sayfa başlangıcı/kenar boşluğu eşleştirildi. Logo/kaşe kaydedilirken mutlak sayfa koordinatına çevrilir. |
| PDF sütun/blok/serbest metin ayarları kayıt izin haritalarında eksikti. | İstemci ve sunucuda docs yazma yetkisine bağlandı. |
| Tahsilat sonrası eşit tam sayı/kayan noktalı tutarlar sahte veri çakışması oluşturuyordu. | State hash, depolama JSON'u ile aynı sayı temsilini kullanır. Gerçek değişiklikler çakışma üretmeye devam eder. |
| Genel kasa hareketinde seçilen tarih cari kaydına yazılıyor ancak kasa hareketine bugünün tarihi yazılıyordu. | Kasa geçmişi de seçilen tarihle kaydedilir. |
| Teslimatı geri alma kontrolü yalnız parti kimliğine bakıyordu. | Sipariş kimliği de kontrol edilerek başka siparişin aynı numaralı partisiyle karışması önlendi. |
| Evrak kaydı başarısız olsa bile düzenleme durumu kapanıyordu. | Başarısız kayıtta belge açık kalır; tekrar kayıt yeni belge çoğaltmadan aynı belgeyi günceller. |
| Güncelleme sonrası tarayıcı eski JS/CSS önbelleğini kullanabilirdi. | V3.13.4-p1 varlık sürümü eklendi. |

## Pakette zaten bulunan ve doğrulanan özellikler

- Teslim edilmiş parti için **Teslimatı Geri Al**.
- Gerçek müşteri tahsilatlarında **Düzenle** ve **Sil**.
- Gerçek müşteri tahsilatının ödeme planına dağıtılması.
- Aynı siparişte müşteri ve tedarikçi ödeme takibi, gerçek tedarikçi ödeme geçmişi, döviz bazında kalan borç.
- İptal/kayıp talebin durum seçicisiyle yeniden açılması.

Canlı test: 400 EUR tahsilat → planda 300 + 100 EUR; 250 EUR'ya düzenleme → banka/tahsilat 250, kalan 750 EUR; silme → tahsilat/banka 0, kalan 1.000 EUR. Tedarikçi için 100 EUR ödeme → 500 EUR kalan borç. Kaydedilmiş logo sürüklenip 200 px'e büyütüldü, sayfa yenilendikten ve baskı stiline geçildikten sonra konumu/boyutu korundu. Yatay çok sayfalı belgede 180 px kaşe için koordinatlar da eşleşti.

## Doğrulama

PHP/JS/statik güvenlik kontrolleri, mevcut PDF geometri kontrolü, state kayıt kuyruğu kontrolü, sayısal hash regresyonu, canlı MySQL şema sözleşmesi ve Playwright tarayıcı akışları geçti. Tarayıcı akışlarında yakalanmamış JavaScript hatası yok. 390 px mobil ayarlar görünümü de kontrol edildi. Tekrarlanabilir tarayıcı testinin ayrıntıları `tests/BROWSER_VALIDATION.md` içindedir.

## Sistem için gerekli davranışlar / sonraki kontroller

1. Müşteri tahsilatı, tedarikçi ödemesi, cari ve banka hareketi aynı işlemde mutabık kalmalı; döviz tutarları birbirine karıştırılmamalı.
2. Düzenleme/silme/teslimat geri alma işlemleri yetki kontrolü ve audit geçmişiyle yürümeli. Mevcut gerçek işlem korumaları korunmuştur.
3. İptal/kayıp kayıtlar silinmeden saklanmalı; gerçek finans işlemi bulunan siparişi yeniden açarken mevcut korumalar uygulanmalı.
4. Etiket ve evrak düzenlemeleri yeniden yükleme sonrasında korunmalı.
5. Üretimde güncel kur, yedekleme/geri yükleme ve eşzamanlı kullanıcı işlemleri gerçek verinin kopyasında ayrıca doğrulanmalı.

Kapsam dışında / doğrulanmamış: gerçek üretim verisi, Google Drive bağlantısı, canlı TCMB kur erişimi, tüm rollerle yetki senaryoları ve tüm yedek geri yükleme çeşitleri. Bu oturumda kur ekranı son başarılı önbellek kaydını kullanmıştır; bu, canlı kur erişiminin başarılı olduğunu göstermez. Eski tip partiye bağlı finans hareketleri varsa teslimatı geri alma bu hareketler kontrol edilmeden yapılamaz; bu koruma kasıtlıdır.

## Kullanım

Mevcut kurulumun dosya/veritabanı yedeğini alın. Güncel dosyaları uygulayın; kendi `config/database.local.php`, Drive yapılandırmanız ve `storage` dosyalarınızı koruyun. Bu P1 düzeltmeleri ek SQL şeması gerektirmez. PHP 8.4 / MySQL-MariaDB ile test edilmiştir. Üretim hesabı/şifresi veya yerel test veritabanı güncellenmiş ZIP'e eklenmemiştir.

Bulut ortamında `/workspace/toolchain/start.sh` servisleri başlatır. Kurulum/başlangıç yönergeleri ortam taslağına kaydedilmiştir; kaydetme yayınlama değildir. Canlı localhost bağlantıları bu kurulum arayüzünde ön izleme olarak desteklenmez; ekran görüntüleri ve PDF çıktısı sağlanmıştır.
