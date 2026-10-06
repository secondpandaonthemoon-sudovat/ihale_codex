from pathlib import Path
root=Path(__file__).resolve().parents[1]
dbm=(root/'api/database_manager.php').read_text(encoding='utf-8')
up=(root/'upgrade.php').read_text(encoding='utf-8')
boot=(root/'app/bootstrap.php').read_text(encoding='utf-8')
checks={
 'canonical_accounts_loop': "foreach($st['accounts'] as &$a)" in dbm,
 'canonical_cash_loop': "foreach($st['cashAccounts'] as &$a)" in dbm,
 'no_temp_accounts_reset_loop': "foreach(($st['accounts']??[]) as &$a)" not in dbm,
 'no_temp_cash_reset_loop': "foreach(($st['cashAccounts']??[]) as &$a)" not in dbm,
 'reset_postcondition_cash': 'Kasa/Banka bakiye sıfırlama doğrulaması başarısız.' in dbm,
 'reset_postcondition_account': 'Cari hesap sıfırlama doğrulaması başarısız.' in dbm,
 'legacy_repair': 'repair_empty_business_residual_balances' in up,
 'repair_finance_guard': "finance_journal" in up and "cash_transactions" in up and "account_transactions" in up,
 'version': 'V3.13.4' in boot,
}
for k,v in checks.items(): print(('PASS' if v else 'FAIL'),k)
raise SystemExit(0 if all(checks.values()) else 1)
