from pathlib import Path
import sys,re
R=Path(__file__).resolve().parents[1]
app=(R/'assets/js/app.js').read_text()
ops=(R/'api/operations.php').read_text()
boot=(R/'app/bootstrap.php').read_text()
checks=[]
def ck(n,c): checks.append((n,bool(c)))
ck('version', "'3.13.4'" in boot and 'V3.13.4' in boot)
ck('cash_account_statement_button', 'openCashAccountStatement(${a.id})' in app and 'function openCashAccountStatement(id)' in app)
ck('single_cash_running_balance', "'Yürüyen Bakiye'" in app and 'cashMoveSignedValue' in app and 'Dönem Açılış' in app)
ck('payment_bank_validation', 'para biriminde ödeme yapılacak Kasa/Banka hesabı seçin' in app and 'Sunucu bakiyesi:' in ops)
ck('payment_currency_validation', 'Aynı para birimindeki hesabı seçin veya önce Virman yapın.' in app)
ck('po_ui_restore', 'captureOrderUi' in app and 'restoreOrderUi(ui)' in app)
ck('business_error_surface', '$e instanceof RuntimeException' in ops and "'Operasyon tamamlanamadı.'" in ops)
for n,v in checks: print(('PASS' if v else 'FAIL')+': '+n)
if not all(v for _,v in checks): sys.exit(1)
