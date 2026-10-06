from pathlib import Path
R=Path(__file__).resolve().parents[1]
ops=(R/'api/operations.php').read_text(encoding='utf-8')
up=(R/'upgrade.php').read_text(encoding='utf-8')
boot=(R/'app/bootstrap.php').read_text(encoding='utf-8')
checks={
 'version_3131': "const ASAY_APP_VERSION = '3.13.4'" in boot and 'V3.13.4' in boot,
 'delete_loads_full_journals': "SELECT * FROM finance_journal WHERE reversal_of IS NULL" in ops,
 'delete_cleans_journal_account_effects': 'foreach($journalRows as $jr)' in ops and 'finance_delete_account_cleanup($s,$jr,$payload,$allCashRows' in ops,
 'cleanup_before_cash_delete': ops.find('foreach($journalRows as $jr)') < ops.find('$removedCash=remove_cash_rows($s', ops.find("elseif($action==='request_delete')")),
 'upgrade_orphan_repair': 'function repair_orphan_account_entries' in up and 'deleted_request_numbers_from_audit' in up,
 'upgrade_recalculates_balance': "$a['requestOpen']=round($sum,2)" in up,
 'upgrade_rebuilds_account_mirror': 'refresh_account_transactions_from_state' in up,
 'manual_opening_not_blanket_deleted': "knownFragments=['müşteri alacağı','tedarikçi borcu','müşteri tahsilatı','tedarikçi ödemesi']" in up,
}
for k,v in checks.items(): print(('PASS' if v else 'FAIL')+': '+k)
raise SystemExit(0 if all(checks.values()) else 1)
