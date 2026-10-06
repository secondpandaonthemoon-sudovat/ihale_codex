GOOGLE DRIVE YEDEKLEME
1) Google Cloud Console'da Drive API'yi etkinleştirin.
2) OAuth 2.0 Web Application istemcisi oluşturun.
3) Redirect URI: http://localhost/asay/google-drive/callback.php
4) config/drive.php içine client_id ve client_secret girin.
5) Uygulamada Ayarlar > Google Drive Yedekleme bağlantısını açın.
6) Google Drive'a Bağlan'a basın. drive.file scope kullanılır.
7) Bağlandıktan sonra SQL yedeğini tek tuşla Drive'a yükleyebilirsiniz.

Token storage/google_drive_token.json içinde saklanır; storage/.htaccess web erişimini engeller.
