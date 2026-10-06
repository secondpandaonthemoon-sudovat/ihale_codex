from pathlib import Path
root=Path(__file__).resolve().parents[1]
ops=(root/'api/operations.php').read_text(encoding='utf-8')
app=(root/'assets/js/app.js').read_text(encoding='utf-8')
dbm=(root/'api/database_manager.php').read_text(encoding='utf-8')
admin=(root/'assets/js/admin.js').read_text(encoding='utf-8')
idx=(root/'index.php').read_text(encoding='utf-8')
boot=(root/'app/bootstrap.php').read_text(encoding='utf-8')
state=(root/'api/state.php').read_text(encoding='utf-8')
checks={
 'version': "const ASAY_APP_VERSION = '3.13.4'" in boot and 'V3.13.4' in boot,
 'schema_marker': "'schema'=>'v3.13.4'" in ops and "'schema'=>'v3.13.4'" in state,
 'request_hard_delete': "elseif($action==='request_delete')" in ops and "audit('request_hard_delete'" in ops,
 'request_finance_cleanup': 'remove_cash_rows($s' in ops and "DELETE FROM finance_journal WHERE id IN" in ops and 'recalc_account_open($a)' in ops,
 'request_no_old_block': 'Silme yerine İptal Edildi durumunu kullanın.' not in ops,
 'finance_hard_delete_api': "'finance_delete'=>['finance','cash']" in ops and "elseif($action==='finance_delete')" in ops and "audit('finance_hard_delete'" in ops,
 'statement_uses_hard_delete': "action:'finance_delete',journalId:jid,accountId:Number(accountId),txnId:e.txnId||''" in app,
 'cash_uses_hard_delete': "async function deleteCashMovement" in app and "action:'finance_delete',journalId:jid" in app,
 'explicit_reversal_still_exists': "action:'reverse_finance'" in app and "elseif($action==='reverse_finance')" in ops,
 'reset_api': "if($action==='reset_business_data')" in dbm and "dbm_write_safety_backup($pdo,'pre_business_reset')" in dbm,
 'reset_preserves_master_accounts': "foreach($st['accounts'] as &$a)" in dbm and "$a['entries']=[]" in dbm and "foreach($st['cashAccounts'] as &$a)" in dbm,
 'reset_clears_business': "foreach(['requests','suppliers','orders','selected','customerQuotes','documents','requestAttachments','guarantees','cash'] as $k)" in dbm,
 'reset_request_sequence': "$st['requestNumber']['next']=1" in dbm,
 'reset_clears_finance_tables': "DELETE FROM account_transactions" in dbm and "DELETE FROM cash_transactions" in dbm and "DELETE FROM finance_journal" in dbm,
 'reset_updates_modules': 'save_module_states($pdo,$st' in dbm,
 'reset_ui': 'businessResetConfirm' in idx and 'businessResetBtn' in idx and 'İş Verilerini Sıfırla' in idx,
 'reset_confirmation': "confirmText!=='SIFIRLA'" in admin and "action=reset_business_data" in admin,
 'latest_safety_generalized': "glob($dir.'/pre_*.asaydb.json')" in dbm,
 'cash_reconcile_after_delete': 'reconcile_cash_balance_from_ledger($s,$a)' in ops,
}
bad=[k for k,v in checks.items() if not v]
for k,v in checks.items(): print(('PASS' if v else 'FAIL')+': '+k)
raise SystemExit(1 if bad else 0)
