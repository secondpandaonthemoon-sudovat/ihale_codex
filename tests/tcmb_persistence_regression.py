from pathlib import Path
import sys
R=Path(__file__).resolve().parents[1]
app=(R/'assets/js/app.js').read_text()
boot=(R/'assets/js/boot.js').read_text()
index=(R/'index.php').read_text()
checks={
 'no_boot_race':'refreshTcmbFx(false)' not in boot,
 'db_persist_after_refresh':'saved=await saveDbState(true)' in app,
 'fresh_cache_merge':'function restoreFreshFxCache()' in app and 'restoreFreshFxCache();ensureRequestUnits()' in index,
 'hydrate_then_refresh':'if(!fxIsUsable(24))await refreshTcmbFx(false)' in index,
 'snapshot_metadata':'rateDate:state.fx?.rateDate' in app and 'fetchedAt:state.fx?.fetchedAt' in app,
 'supplier_guard':'if(!fxIsUsable(24))return toast' in app,
}
for k,v in checks.items(): print(('PASS' if v else 'FAIL')+': '+k)
if not all(checks.values()): sys.exit(1)
