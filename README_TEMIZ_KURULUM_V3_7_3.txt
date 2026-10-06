ASAY ERP V3.9.3 CLEAN INSTALL

ÖNERİLEN SIFIR KURULUM:
1. C:\xampp\htdocs\asay klasörünün eski halini ayrıca yedekleyin ve sonra silin.
2. Bu ZIP içindeki klasörü C:\xampp\htdocs\asay olarak çıkarın.
3. Mümkünse phpMyAdmin üzerinden yeni/boş bir asay_erp veritabanı oluşturun.
4. http://localhost/asay/install.php açın.
5. MySQL ve yeni Admin bilgilerini sıfırdan girin.
6. Aynı DB'de eski ASAY tabloları varsa installer bunları listeler. Sıfır kurulum istiyorsanız temiz kurulum kutusunu işaretleyip SIFIRDAN_KUR yazın.
7. Kurulumdan sonra önce giriş yapın ve yeni bir .asaydb + SQL + Tam Sistem yedeği alın.
8. Eski yedeği geri yükleyecekseniz önce Validate/Doğrula kullanın.

YEDEKLEME DAVRANIŞI:
- .asaydb.json: önerilen veritabanı yedeği.
- .sql: taşınabilir veri yedeği. Restore sırasında eski şema kurulmaz; güncel V3.9.3 şeması kullanılır, yalnız ASAY verileri içeri alınır.
- .asayfull.zip: DB + storage evrakları.
- Restore öncesi otomatik safety backup alınır.
- Eski SQL şemasının current uygulamayı geriye düşürmesi engellenmiştir.
