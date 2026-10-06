#!/bin/sh
set -eu
cd /var/www/html
mkdir -p storage/request-docs storage/db-backups storage/trash storage/tcmb_history
if [ ! -f config/drive.php ]; then cp config/drive.example.php config/drive.php; fi
printf '%s\n' 'Ön izleme hazır. İlk açılışta install.php üzerinden asay_preview veritabanına yönetici hesabı oluşturun. Canlı veritabanınızı kullanmayın.'
