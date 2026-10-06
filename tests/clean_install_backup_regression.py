from pathlib import Path
R=Path(__file__).resolve().parents[1]
checks=[]
def ck(n,v):
    print(('PASS' if v else 'FAIL')+': '+n); checks.append(v)
install=(R/'install.php').read_text(encoding='utf-8')
dbm=(R/'api/database_manager.php').read_text(encoding='utf-8')
sm=(R/'app/schema_manager.php').read_text(encoding='utf-8')
login=(R/'login.php').read_text(encoding='utf-8')
ck('clean_confirmation','SIFIRDAN_KUR' in install and 'clean_install' in install)
ck('statement_schema_apply','asay_schema_apply' in install and '$pdo->exec($sql)' not in install)
ck('shared_schema_manager','asay_schema_fresh' in sm and 'FOREIGN_KEY_CHECKS' in sm)
ck('json_current_schema','Backup createSql is metadata only' in dbm)
ck('sql_data_only_restore','Legacy SQL schemas are NOT recreated' in dbm and "preg_match('/^INSERT" in dbm)
ck('admin_preserved_restore','Preserve the currently authenticated Admin' in dbm and 'preserveAdmin' in dbm)
ck('audit_nonfatal',"ASAY login audit failed" in login)
raise SystemExit(0 if all(checks) else 1)
