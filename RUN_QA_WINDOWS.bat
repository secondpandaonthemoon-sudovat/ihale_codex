@echo off
setlocal
set "PHP=C:\xampp\php\php.exe"
if not exist "%PHP%" (
  echo [HATA] C:\xampp\php\php.exe bulunamadi.
  echo XAMPP yolunuz farkliysa bu dosyadaki PHP satirini duzenleyin.
  pause
  exit /b 1
)
cd /d "%~dp0"
echo === ASAY ERP V3.13.0 Yerel QA ===
echo.
echo [1/3] Kritik PHP dosyalari syntax kontrolu...
for %%F in (index.php install.php login.php upgrade.php api\operations.php api\state.php api\database_manager.php api\users.php app\bootstrap.php app\schema_manager.php) do (
  "%PHP%" -l "%%F" || goto :fail
)
echo.
echo [2/3] Security smoke...
"%PHP%" tests\security_smoke.php || goto :fail
echo.
echo [3/3] Canli MariaDB contract...
set ASAY_E2E_DB=1
"%PHP%" tests\e2e_db_contract.php || goto :fail
echo.
echo [OK] Temel PHP + Security + MariaDB contract testleri BASARILI.
pause
exit /b 0
:fail
echo.
echo [HATA] QA testi basarisiz. Yukaridaki ilk FAIL/HATA satirini kaydedin.
pause
exit /b 1
