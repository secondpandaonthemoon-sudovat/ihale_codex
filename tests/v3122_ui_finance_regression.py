from pathlib import Path
r=Path(__file__).resolve().parents[1]
a=(r/'assets/js/app.js').read_text(encoding='utf-8');i=(r/'index.php').read_text(encoding='utf-8');o=(r/'api/operations.php').read_text(encoding='utf-8')
checks={
'cash_selected': 'activeCashAccountId' in a and 'Hesap Hareketleri' in a,
'cash_no_type_col': '<th>Tür</th>' not in i[i.find('cashMovementPanelTitle'):i.find('cashMovementPanelTitle')+1500],
'cash_edit_delete': 'openFinanceMovementEdit' in a and "deleteCashMovement" in a,
'account_edit_delete': 'deleteOrCancelAccountEntry' in a and 'openFinanceMovementEdit' in a,
'reversal_hidden': "x.kind!=='reversal'" in a and "!/^Ters kayıt:/iu" in a,
'server_balance': 'Sunucu bakiyesi:' in o,
'no_client_stale_balance': 'Mevcut: ${fmtMoneyCur(bank.balance' not in a,
'compact_compare': 'comparison-workspace' in a and 'Detaylı Kıyaslama Tablosu' in a,
'meta_update': "finance_journal_meta_update" in o and 'financeMovementEditModal' in i,
}
for k,v in checks.items(): print(('PASS' if v else 'FAIL')+': '+k)
raise SystemExit(0 if all(checks.values()) else 1)
