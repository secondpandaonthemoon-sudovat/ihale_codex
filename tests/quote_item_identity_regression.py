from pathlib import Path
p=Path(__file__).resolve().parents[1]/'api'/'operations.php'
s=p.read_text(encoding='utf-8')
checks={
 'ID-first previous quote mapping': "$prev=$oldById[$iid]??($oldByUid[$uid]??null);" in s,
 'duplicate old UID counted': '$oldUidCount[$uid]=' in s,
 'only unique old UID used as fallback': "($oldUidCount[$uid]??0)===1" in s,
 'request UID collision repair': "isset($seenUid[$uid])" in s,
 'request update UID uniqueness': '$usedUid=[]' in s and "isset($usedUid[$uid])" in s,
 'quote_item UID exact match': "$uidMatch=$uid!==''&&(string)($it['itemUid']??'')===$uid" in s,
}
bad=[k for k,v in checks.items() if not v]
if bad:
    print('FAIL:', ', '.join(bad)); raise SystemExit(1)
print('PASS: customer quote item identity regression guards')
