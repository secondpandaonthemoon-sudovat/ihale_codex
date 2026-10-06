@echo off
setlocal
cd /d "%~dp0"
if exist test_write del /f /q test_write >nul 2>&1
if exist config\database.local.php echo [OK] database.local.php korundu.
if exist storage\google_drive_token.json echo [OK] Google Drive token korundu.
echo [OK] V3.12.0 paket temizligi tamamlandi.
echo Uygulamayi acip Ayarlar ^> Sistem Tani veya RUN_QA_WINDOWS.bat ile kontrol edebilirsiniz.
pause
