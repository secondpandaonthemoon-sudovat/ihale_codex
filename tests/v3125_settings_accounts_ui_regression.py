from pathlib import Path
r=Path(__file__).resolve().parents[1]
idx=(r/'index.php').read_text(encoding='utf-8');app=(r/'assets/js/app.js').read_text(encoding='utf-8');css=(r/'assets/css/app.css').read_text(encoding='utf-8');comp=(r/'assets/css/components.css').read_text(encoding='utf-8');boot=(r/'app/bootstrap.php').read_text(encoding='utf-8')
checks={
'version':"'3.13.4'" in boot and 'V3.13.4' in boot,
'settings_tabs':all(f'data-settings-category="{x}"' in idx for x in ['appearance','actions','colors','company','access','system']),
'settings_engine':'function showSettingsCategory' in app and 'function initSettingsWorkspace' in app and 'settingsCategoryNodes' in app,
'settings_hide_css':'.settings-category-hidden' in comp and '.settings-nav-card.active' in comp,
'compact_accounts':'account-entity-card compact' in app and 'account-compact-balances' in app and 'account-compact-foot' in app,
'no_embedded_account_details':'account-card-details' not in app[app.find('function renderAccounts'):app.find('let editingAccountName')],
'detail_management':"openAccountEdit('${accountGroupKey(g)}')" in app and "deleteAccountGroup('${accountGroupKey(g)}')" in app,
'account_workspace':'accounts-workspace' in idx and '.accounts-workspace' in css,
}
bad=[k for k,v in checks.items() if not v]
for k,v in checks.items():print(('PASS' if v else 'FAIL')+': '+k)
raise SystemExit(1 if bad else 0)
