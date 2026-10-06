from pathlib import Path
root=Path(__file__).resolve().parents[1]
app=(root/'assets/js/app.js').read_text(encoding='utf-8')
ops=(root/'api/operations.php').read_text(encoding='utf-8')
idx=(root/'index.php').read_text(encoding='utf-8')
checks={
 'no_technical_badge':'Ters Kayıt Gerekli' not in app,
 'edit_button':'openAccountEntryEdit' in app and '>Düzenle</button>' in app,
 'delete_button':'deleteOrCancelAccountEntry' in app and '>Sil</button>' in app,
 'manual_delete_api':"$action==='account_entry_delete'" in ops and "'account_entry_delete'=>['finance']" in ops,
 'delete_adjusts_balance':"$a['requestOpen']=round($oldOpen-$effect,2)" in ops,
 'delete_audited':"audit('account_entry_delete'" in ops,
 'linked_reverse':'reverse_finance' in app and 'accountEntryLinkedJournalId' in app,
 'friendly_modal_copy':'güvenli şekilde iptal edilir' in idx,
}
failed=[k for k,v in checks.items() if not v]
for k,v in checks.items(): print(('PASS' if v else 'FAIL')+': '+k)
if failed: raise SystemExit(1)
