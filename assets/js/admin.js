/* ASAY ERP V3.13.4 — Admin, settings, users and DB management */
let dbRestoreValidationOk=false;
function dbAdminHeaders(extra={}){return {'X-ASAY-CSRF':window.DB_ADMIN_CSRF||'',...extra}}
function formatDbBytes(n){
 const v=Number(n||0);if(!v)return '—';
 if(v<1024)return v+' B';if(v<1024**2)return (v/1024).toFixed(1)+' KB';
 if(v<1024**3)return (v/1024**2).toFixed(1)+' MB';return (v/1024**3).toFixed(2)+' GB'
}
async function loadDatabaseManagerStatus(){
 const db=document.getElementById('dbManagerDatabase');if(!db)return;
 try{
   const r=await fetch('api/database_manager.php?action=status',{cache:'no-store'}),j=await r.json();
   if(!r.ok||!j.ok)throw new Error(j.error||'Veritabanı durumu alınamadı');
   db.textContent=j.database||'—';
   document.getElementById('dbManagerTableCount').textContent=(j.existingTableCount??0)+' / '+(j.allowedTableCount??0);
   document.getElementById('dbManagerLastSafety').textContent=j.lastSafetyBackup?j.lastSafetyBackup.createdAt+' · '+formatDbBytes(j.lastSafetyBackup.size):'Yok';
   document.getElementById('dbManagerUploadLimit').textContent=j.uploadLimit||'Sunucu varsayılanı';
   const rb=document.getElementById('dbRollbackBtn');if(rb)rb.disabled=!j.lastSafetyBackup
 }catch(e){db.textContent='Bağlantı Hatası';toast(e.message,'warn')}
}
function resetDatabaseRestoreValidation(){
 dbRestoreValidationOk=false;
 const b=document.getElementById('dbRestoreBtn');if(b)b.disabled=true;
 const box=document.getElementById('dbValidationResult');if(box){box.className='db-validation-result';box.textContent='Dosya seçildi. Geri yüklemeden önce “Yedeği Kontrol Et” düğmesini kullanın.'}
}
async function downloadDatabaseBackup(){
 const btn=event?.currentTarget;if(btn){btn.disabled=true;btn.textContent='Yedek hazırlanıyor…'}
 try{
   const r=await fetch('api/database_manager.php?action=backup',{method:'POST',headers:dbAdminHeaders()});
   if(!r.ok){let m='Yedek oluşturulamadı';try{const j=await r.json();m=j.error||m}catch(_e){}throw new Error(m)}
   const blob=await r.blob(),cd=r.headers.get('Content-Disposition')||'';
   const m=cd.match(/filename="?([^";]+)"?/i),name=m?.[1]||('ASAY_ERP_DB_'+new Date().toISOString().slice(0,10)+'.asaydb.json');
   const a=document.createElement('a');a.href=URL.createObjectURL(blob);a.download=name;document.body.appendChild(a);a.click();a.remove();setTimeout(()=>URL.revokeObjectURL(a.href),1500);
   toast('Veritabanı yedeği indirildi.','good');loadDatabaseManagerStatus()
 }catch(e){toast(e.message,'warn')}finally{if(btn){btn.disabled=false;btn.textContent='Tam DB Yedeğini İndir'}}
}
async function downloadDatabaseSqlBackup(){
 const btn=event?.currentTarget;if(btn){btn.disabled=true;btn.textContent='SQL hazırlanıyor…'}
 try{
   const r=await fetch('api/database_manager.php?action=backup_sql',{method:'POST',headers:dbAdminHeaders()});
   if(!r.ok){let m='SQL yedeği oluşturulamadı';try{const j=await r.json();m=j.error||m}catch(_e){}throw new Error(m)}
   const blob=await r.blob(),cd=r.headers.get('Content-Disposition')||'';
   const m=cd.match(/filename="?([^";]+)"?/i),name=m?.[1]||('ASAY_ERP_DB_'+new Date().toISOString().slice(0,10)+'.sql');
   const url=URL.createObjectURL(blob),a=document.createElement('a');a.href=url;a.download=name;document.body.appendChild(a);a.click();a.remove();setTimeout(()=>URL.revokeObjectURL(url),1500);
   toast('SQL veritabanı yedeği indirildi.','good')
 }catch(e){toast(e.message,'warn')}finally{if(btn){btn.disabled=false;btn.textContent='SQL Yedeği (.sql)'}}
}
async function downloadFullSystemBackup(){
 const btn=event?.currentTarget;if(btn){btn.disabled=true;btn.textContent='Tam yedek hazırlanıyor…'}
 try{
   const r=await fetch('api/database_manager.php?action=backup_full',{method:'POST',headers:dbAdminHeaders()});
   if(!r.ok){let m='Tam sistem yedeği oluşturulamadı';try{const j=await r.json();m=j.error||m}catch(_e){}throw new Error(m)}
   const blob=await r.blob(),cd=r.headers.get('Content-Disposition')||'';
   const m=cd.match(/filename="?([^";]+)"?/i),name=m?.[1]||('ASAY_ERP_FULL_'+new Date().toISOString().slice(0,10)+'.asayfull.zip');
   const url=URL.createObjectURL(blob),a=document.createElement('a');a.href=url;a.download=name;document.body.appendChild(a);a.click();a.remove();setTimeout(()=>URL.revokeObjectURL(url),1500);
   toast('Tam sistem yedeği indirildi.','good')
 }catch(e){toast(e.message,'warn')}finally{if(btn){btn.disabled=false;btn.textContent='Tam Sistem (.asayfull.zip)'}}
}

function syncBusinessResetButton(){
 const input=document.getElementById('businessResetConfirm'),btn=document.getElementById('businessResetBtn');if(btn)btn.disabled=String(input?.value||'').trim()!=='SIFIRLA'
}
async function resetBusinessData(){
 const input=document.getElementById('businessResetConfirm'),confirmText=String(input?.value||'').trim();if(confirmText!=='SIFIRLA')return toast('Devam etmek için onay alanına SIFIRLA yazın.','warn');
 if(!await appConfirm('Tüm iş verileri ve finans hareketleri temizlenecek. Cari kartlar ile Kasa/Banka hesapları korunacak ancak bakiyeleri sıfırlanacak. İşlemden önce otomatik güvenlik yedeği alınacak. Devam edilsin mi?','İş Verilerini Sıfırla'))return;
 const btn=document.getElementById('businessResetBtn');if(btn){btn.disabled=true;btn.textContent='Sıfırlanıyor…'}
 try{
   const r=await fetch('api/database_manager.php?action=reset_business_data',{method:'POST',headers:dbAdminHeaders({'Content-Type':'application/json'}),body:JSON.stringify({confirm:'SIFIRLA'})}),j=await r.json();if(!r.ok||!j.ok)throw new Error(j.error||'İş verileri sıfırlanamadı');
   const c=j.result?.counts||{},backup=j.safetyBackup?.name||'otomatik güvenlik yedeği';
   await appConfirm(`Sıfırlama tamamlandı.\\n\\nTemizlenen: ${Number(c.requests||0)} talep · ${Number(c.orders||0)} sipariş · ${Number(c.cashMovements||0)} kasa/banka hareketi.\\nKorunan: ${Number(j.result?.preservedAccounts||0)} cari alt hesabı · ${Number(j.result?.preservedCashAccounts||0)} kasa/banka hesabı.\\nGüvenlik yedeği: ${backup}\\n\\nSistem temiz iş verileriyle yeniden açılacak.`,'Sıfırlama Tamamlandı');
   location.href='index.php?business_reset=1'
 }catch(e){toast(e.message,'warn');if(btn){btn.textContent='İş Verilerini Sıfırla';syncBusinessResetButton()}loadDatabaseManagerStatus()}
}
async function validateDatabaseBackup(){
 const f=document.getElementById('dbRestoreFile')?.files?.[0],box=document.getElementById('dbValidationResult');
 if(!f)return toast('Önce bir yedek dosyası seçin.','warn');
 box.className='db-validation-result';box.textContent='Dosya güvenlik kontrolünden geçiriliyor…';
 const fd=new FormData();fd.append('backup',f);
 try{
   const r=await fetch('api/database_manager.php?action=validate',{method:'POST',headers:dbAdminHeaders(),body:fd}),j=await r.json();
   if(!r.ok||!j.ok)throw new Error(j.error||'Yedek doğrulanamadı');
   dbRestoreValidationOk=true;document.getElementById('dbRestoreBtn').disabled=false;
   const meta=j.meta||{};
   box.className='db-validation-result ok';
   box.innerHTML=`<b>✓ Geçerli ASAY ERP yedeği</b><br>Tür: ${reportEsc(meta.type||'—')} · Tablo: ${Number(meta.tableCount||0)}${meta.fileCount!==undefined?' · Fiziksel Dosya: '+Number(meta.fileCount||0):''} · Kaynak: ${reportEsc(meta.sourceVersion||'Bilinmiyor')}<br>${meta.createdAt?'Yedek tarihi: '+reportEsc(meta.createdAt):''}`;
 }catch(e){dbRestoreValidationOk=false;document.getElementById('dbRestoreBtn').disabled=true;box.className='db-validation-result bad';box.textContent='⛔ '+e.message}
}
async function restoreDatabaseBackup(){
 const f=document.getElementById('dbRestoreFile')?.files?.[0];if(!f||!dbRestoreValidationOk)return toast('Önce dosyayı doğrulayın.','warn');
 if(!await appConfirm('Bu işlem mevcut ASAY ERP veritabanını seçtiğiniz yedekle değiştirecek. Mevcut DB önce otomatik güvenlik yedeğine alınacak. Devam edilsin mi?','Veritabanı Geri Yükleme'))return;
 const btn=document.getElementById('dbRestoreBtn');btn.disabled=true;btn.textContent='Geri yükleniyor…';
 const fd=new FormData();fd.append('backup',f);fd.append('confirm','ASAY_RESTORE');
 try{
   const r=await fetch('api/database_manager.php?action=restore',{method:'POST',headers:dbAdminHeaders(),body:fd}),j=await r.json();
   if(!r.ok||!j.ok)throw new Error(j.error||'Geri yükleme başarısız');
   await appConfirm('Veritabanı başarıyla geri yüklendi. Sistem yeni veritabanı ile yeniden açılacak.','Geri Yükleme Tamamlandı');
   location.href='index.php?db_restored=1'
 }catch(e){toast(e.message,'warn');btn.disabled=false;btn.textContent='Kontrol Edilen Yedeği Geri Yükle';loadDatabaseManagerStatus()}
}
async function rollbackDatabaseRestore(){
 if(!await appConfirm('Son otomatik güvenlik yedeğine dönülsün mü? Mevcut durum ayrıca yedeklenecektir.','Geri Alma'))return;
 const btn=document.getElementById('dbRollbackBtn');btn.disabled=true;btn.textContent='Geri dönülüyor…';
 try{
   const r=await fetch('api/database_manager.php?action=rollback',{method:'POST',headers:dbAdminHeaders({'Content-Type':'application/json'}),body:JSON.stringify({confirm:'ASAY_ROLLBACK'})}),j=await r.json();
   if(!r.ok||!j.ok)throw new Error(j.error||'Geri alma başarısız');
   await appConfirm('Son güvenlik yedeğindeki veritabanına geri dönüldü. Sistem yeniden açılacak.','Geri Alma Tamamlandı');
   location.href='index.php?db_rollback=1'
 }catch(e){toast(e.message,'warn');btn.disabled=false;btn.textContent='Son Restore Öncesine Geri Dön';loadDatabaseManagerStatus()}
}
async function loadAuditLog(){try{const r=await fetch('api/audit.php?limit=250'),j=await r.json();if(!r.ok||!j.ok)throw new Error(j.error||'Audit yüklenemedi');const b=document.getElementById('auditRows');if(b)b.innerHTML=j.rows.map(x=>`<tr><td>${x.created_at}</td><td>${x.user_name||'Sistem'}</td><td>${x.action}</td><td>${x.entity_type||''} ${x.entity_id||''}</td><td><small>${reportEsc(x.payload_json||'')}</small></td></tr>`).join('')||'<tr><td colspan="5" class="empty">Kayıt yok.</td></tr>'}catch(e){toast(e.message,'warn')}}function renderPermissions(){const tbody=document.querySelector('#permissionsTable tbody');if(!tbody)return;const fields=['request','supplier','cost','finance','cash','docs','settings'];tbody.innerHTML=state.roles.map((r,ri)=>`<tr><td><input style="height:34px;border:1px solid var(--line);border-radius:7px;padding:0 7px;background:var(--surface);color:var(--text);width:130px" value="${escAttr(r.name)}" onchange="state.roles[${ri}].name=this.value;renderUsers()"></td>${fields.map(f=>{const wk=f+'Write',write=(wk in r)?!!r[wk]:legacyWriteDefault(r.name,f);return `<td><input class="permission-check" type="checkbox" ${r[f]?'checked':''} onchange="state.roles[${ri}].${f}=this.checked;if(!this.checked)state.roles[${ri}].${wk}=false;renderPermissions()"></td><td><input class="permission-check" type="checkbox" ${write?'checked':''} ${!r[f]?'disabled':''} onchange="state.roles[${ri}].${wk}=this.checked"></td>`}).join('')}</tr>`).join('')}
function addRole(){state.roles.push({name:'Yeni Rol',request:true,requestWrite:false,supplier:false,supplierWrite:false,cost:false,costWrite:false,finance:false,financeWrite:false,cash:false,cashWrite:false,docs:false,docsWrite:false,settings:false,settingsWrite:false});renderPermissions();renderUsers();toast('Yeni rol eklendi.','good')}
function bankSelectOptions(currency,selectedId){return state.cashAccounts.filter(a=>a.type==='Banka'&&a.currency===currency).map(a=>`<option value="${a.id}" ${+selectedId===a.id?'selected':''}>${a.name} · ${a.bankName||''}</option>`).join('')}
function renderCompanyBankInfo(currency){const id=+(document.getElementById(currency==='EUR'?'coEurBankAccount':'coUsdBankAccount')?.value||0),a=state.cashAccounts.find(x=>x.id===id),box=document.getElementById(currency==='EUR'?'coEurBankInfo':'coUsdBankInfo');if(!box)return;box.innerHTML=a?`<b>${a.bankName||a.name}</b><br>Şube: ${a.branch||'-'} · SWIFT: ${a.swift||'-'}<br>${a.iban||'-'} · ${a.currency}`:`${currency} için banka hesabı bulunamadı.`}
function toggleCustomRequestPattern(){const f=document.getElementById('requestPatternField'),s=document.getElementById('requestNoFormat');if(f&&s)f.style.display=s.value==='custom'?'block':'none'}
function loadRequestNumberSettings(){const c=requestNumberSettings(),m={requestNoPrefix:'prefix',requestNoFormat:'format',requestNoNext:'next',requestNoPattern:'pattern'};Object.entries(m).forEach(([id,k])=>{const e=document.getElementById(id);if(e)e.value=c[k]??''});toggleCustomRequestPattern()}
function saveRequestNumberSettings(){const c=requestNumberSettings();c.prefix=document.getElementById('requestNoPrefix')?.value.trim()||'RFQ';c.format=document.getElementById('requestNoFormat')?.value||'prefix-year-seq';c.next=Math.max(1,+document.getElementById('requestNoNext')?.value||1);c.pattern=document.getElementById('requestNoPattern')?.value.trim()||'{PREFIX}-{YEAR}-{SEQ4}'}
function defaultActionSettings(){return {
 request:{enabled:true,today:true,overdue:true,l1Days:5,l1Color:'#f59e0b',l2Days:3,l2Color:'#ef4444',l3Days:1,l3Color:'#7c3aed',todayColor:'#be123c',overdueColor:'#111827'},
 ready:{enabled:true,color:'#d97706'},docsPending:{enabled:true,color:'#f59e0b'},
 customer:{enabled:true,today:true,overdue:true,l1Days:5,l1Color:'#f59e0b',l2Days:3,l2Color:'#ef4444',l3Days:1,l3Color:'#7c3aed',todayColor:'#be123c',overdueColor:'#111827'},
 supplier:{enabled:true,today:true,overdue:true,l1Days:5,l1Color:'#f59e0b',l2Days:3,l2Color:'#ef4444',l3Days:1,l3Color:'#7c3aed',todayColor:'#be123c',overdueColor:'#111827'}
}}
function normalizedActionSettings(){
 const d=defaultActionSettings(),s=state.company.actionSettings||{};
 return {
  request:{...d.request,...(s.request||{})},
  ready:{...d.ready,...(s.ready||{})},docsPending:{...d.docsPending,...(s.docsPending||{})},
  customer:{...d.customer,...(s.customer||{})},
  supplier:{...d.supplier,...(s.supplier||{})}
 }
}
function actionSettingValue(id,v){const e=document.getElementById(id);if(e)e.value=String(v)}
function loadActionSettings(){
 const s=normalizedActionSettings();state.company.actionSettings=s;
 const load=(p,c)=>{actionSettingValue(p+'Enabled',c.enabled?1:0);actionSettingValue(p+'Today',c.today?1:0);actionSettingValue(p+'Overdue',c.overdue?1:0);actionSettingValue(p+'L1Days',c.l1Days??5);actionSettingValue(p+'L1Color',c.l1Color||'#f59e0b');actionSettingValue(p+'L2Days',c.l2Days??3);actionSettingValue(p+'L2Color',c.l2Color||'#ef4444');actionSettingValue(p+'L3Days',c.l3Days??1);actionSettingValue(p+'L3Color',c.l3Color||'#7c3aed');actionSettingValue(p+'TodayColor',c.todayColor||'#be123c');actionSettingValue(p+'OverdueColor',c.overdueColor||'#111827')};
 load('actReq',s.request);load('actCustomer',s.customer);load('actSupplier',s.supplier);
 actionSettingValue('actReadyEnabled',s.ready.enabled?1:0);actionSettingValue('actReadyColor',s.ready.color||'#d97706');actionSettingValue('actDocsPendingEnabled',s.docsPending.enabled?1:0);actionSettingValue('actDocsPendingColor',s.docsPending.color||'#f59e0b')
}
function saveActionSettings(){
 const bool=id=>document.getElementById(id)?.value!=='0',num=(id,fallback)=>Math.max(0,Number(document.getElementById(id)?.value??fallback)||0),color=(id,f)=>document.getElementById(id)?.value||f;
 const collect=p=>({enabled:bool(p+'Enabled'),today:bool(p+'Today'),overdue:bool(p+'Overdue'),l1Days:num(p+'L1Days',5),l1Color:color(p+'L1Color','#f59e0b'),l2Days:num(p+'L2Days',3),l2Color:color(p+'L2Color','#ef4444'),l3Days:num(p+'L3Days',1),l3Color:color(p+'L3Color','#7c3aed'),todayColor:color(p+'TodayColor','#be123c'),overdueColor:color(p+'OverdueColor','#111827')});
 state.company.actionSettings={request:collect('actReq'),ready:{enabled:bool('actReadyEnabled'),color:color('actReadyColor','#d97706')},docsPending:{enabled:bool('actDocsPendingEnabled'),color:color('actDocsPendingColor','#f59e0b')},customer:collect('actCustomer'),supplier:collect('actSupplier')};
 return state.company.actionSettings
}
function setAllActionSettings(enabled){
 ['actReqEnabled','actReadyEnabled','actDocsPendingEnabled','actCustomerEnabled','actSupplierEnabled'].forEach(id=>actionSettingValue(id,enabled?1:0));
 if(enabled){['actReqToday','actReqOverdue','actCustomerToday','actCustomerOverdue','actSupplierToday','actSupplierOverdue'].forEach(id=>actionSettingValue(id,1))}
 toast(enabled?'Tüm aksiyon türleri açıldı. Ayarları Kaydet ile kalıcı hale getirin.':'Tüm aksiyon türleri kapatıldı. Ayarları Kaydet ile kalıcı hale getirin.','good')
}
function actionDateAllowed(info,cfg){
 if(!info||!cfg?.enabled)return false;
 if(info.days<0)return !!cfg.overdue;
 if(info.days===0)return !!cfg.today;
 return info.days<=Math.max(Number(cfg.l1Days||0),Number(cfg.l2Days||0),Number(cfg.l3Days||0))
}
function actionSeverity(info,cfg){
 if(!info)return {label:'',color:'#d97706',level:0};
 if(info.days<0)return {label:'Gecikmiş',color:cfg.overdueColor||'#111827',level:5};
 if(info.days===0)return {label:'Bugün',color:cfg.todayColor||'#be123c',level:4};
 const l3=Number(cfg.l3Days||1),l2=Number(cfg.l2Days||3),l1=Number(cfg.l1Days||5);
 if(info.days<=l3)return {label:'Çok Kritik',color:cfg.l3Color||'#7c3aed',level:3};
 if(info.days<=l2)return {label:'Kritik',color:cfg.l2Color||'#ef4444',level:2};
 if(info.days<=l1)return {label:'Yaklaşıyor',color:cfg.l1Color||'#f59e0b',level:1};
 return {label:'',color:cfg.l1Color||'#f59e0b',level:0}
}
function defaultStatusColors(){return {draft:'#94a3b8',collecting:'#3b82f6',ready:'#06b6d4',quote:'#4f46e5',sent:'#8b5cf6',won:'#22c55e',lost:'#64748b',cancelled:'#ef4444',comparisonSuitable:'#22c55e',comparisonConditional:'#f59e0b',comparisonUnsuitable:'#ef4444',comparisonNoPrice:'#94a3b8',comparisonSelected:'#7c3aed'}}function applyStatusColors(){state.company.statusColors={...defaultStatusColors(),...(state.company.statusColors||{})};Object.entries(state.company.statusColors).forEach(([k,v])=>document.documentElement.style.setProperty('--status-'+k,v))}function loadStatusColors(){applyStatusColors();Object.entries(state.company.statusColors).forEach(([k,v])=>{const id='statusColor'+k[0].toUpperCase()+k.slice(1),e=document.getElementById(id);if(e)e.value=v;previewStatusColor(k,v)})}function saveStatusColors(){const d=defaultStatusColors();Object.keys(d).forEach(k=>{const e=document.getElementById('statusColor'+k[0].toUpperCase()+k.slice(1));if(e)d[k]=e.value});state.company.statusColors=d;applyStatusColors()}function loadCompanyToForm(){const m={coName:'name',coBrand:'brand',coWeb:'web',coEmail:'email',coPhone:'phone',coTaxOffice:'taxOffice',coTaxNo:'taxNo',coMersis:'mersis',coRegistry:'registry',coAddress:'address',coFooter:'footer'};Object.entries(m).forEach(([id,k])=>{const e=document.getElementById(id);if(e)e.value=state.company[k]||''});const eur=document.getElementById('coEurBankAccount'),usd=document.getElementById('coUsdBankAccount');if(eur){eur.innerHTML=bankSelectOptions('EUR',state.company.eurBankAccountId);if(!eur.value&&eur.options.length)eur.selectedIndex=0}if(usd){usd.innerHTML=bankSelectOptions('USD',state.company.usdBankAccountId);if(!usd.value&&usd.options.length)usd.selectedIndex=0}renderCompanyBankInfo('EUR');renderCompanyBankInfo('USD');loadActionSettings();loadStatusColors()}
function saveAppIdentity(){if(!state.appIdentity)state.appIdentity={};const n=document.getElementById('appNameSetting'),s=document.getElementById('appSubtitleSetting');if(n)state.appIdentity.name=n.value.trim()||'ASAY İhale & Teklif OS';if(s)state.appIdentity.subtitle=s.value.trim();applyAppIdentity()}
function applyAppIdentity(){const a=state.appIdentity||{},brand=document.getElementById('appBrandName'),sub=document.getElementById('appBrandSubtitle'),mark=document.getElementById('appBrandmark'),av=document.getElementById('sidebarUserAvatar');if(brand)brand.textContent=a.name||'ASAY İhale & Teklif OS';if(sub)sub.textContent=a.subtitle||'Connected Commerce Workspace';if(mark)mark.innerHTML=a.logo?`<img src="${a.logo}" style="width:100%;height:100%;object-fit:contain;border-radius:inherit">`:'AS';if(av)av.innerHTML=a.userPhoto?`<img src="${a.userPhoto}" style="width:100%;height:100%;object-fit:cover;border-radius:50%">`:'EA';const n=document.getElementById('appNameSetting'),s=document.getElementById('appSubtitleSetting');if(n)n.value=a.name||'ASAY İhale & Teklif OS';if(s)s.value=a.subtitle||'';document.title=(a.name||'ASAY İhale & Teklif OS')+' — Yönetim';let fav=document.querySelector('link[rel~="icon"]');if(a.favicon){if(!fav){fav=document.createElement('link');fav.rel='icon';document.head.appendChild(fav)}fav.href=a.favicon}}
function restoreAppIdentity(){applyAppIdentity()}
function loadIdentityImage(input,type){const f=input.files?.[0];if(!f)return;const rd=new FileReader();rd.onload=()=>{state.appIdentity[type]=rd.result;applyAppIdentity();const map={logo:'appLogoPreview',favicon:'faviconPreview',userPhoto:'userPhotoPreview',pdfLogo:'pdfIdentityLogoPreview'},box=document.getElementById(map[type]);if(box)box.innerHTML=`<img src="${rd.result}" alt="">`};rd.readAsDataURL(f)}
async function saveSettings(){try{saveAppIdentity();applyUiSettings();saveRequestNumberSettings();saveActionSettings();saveStatusColors();const m={coName:'name',coBrand:'brand',coWeb:'web',coEmail:'email',coPhone:'phone',coTaxOffice:'taxOffice',coTaxNo:'taxNo',coMersis:'mersis',coRegistry:'registry',coAddress:'address',coFooter:'footer'};Object.entries(m).forEach(([id,k])=>{const e=document.getElementById(id);if(e)state.company[k]=e.value});state.company.eurBankAccountId=+document.getElementById('coEurBankAccount')?.value||null;state.company.usdBankAccountId=+document.getElementById('coUsdBankAccount')?.value||null;const eb=state.cashAccounts.find(a=>a.id===state.company.eurBankAccountId),ub=state.cashAccounts.find(a=>a.id===state.company.usdBankAccountId);if(eb){state.company.eurBank=eb.name;state.company.eurIban=eb.iban}if(ub){state.company.usdBank=ub.name;state.company.usdIban=ub.iban}saveUiSettings();renderDocument();loadCompanyToForm();loadRequestNumberSettings();updateDashboard();if(!await saveDbState(true))throw new Error(window.lastDbSaveError||'DB kayıt hatası');toast('Ayarlar başarıyla kaydedildi.','good')}catch(e){console.error(e);toast('Ayarlar kaydedilemedi: '+e.message,'warn')}}
function restorePersistentSettings(){/* MySQL state tek kaynak */}
function renderUsers(){sanitizeClientStateInPlace();const box=document.getElementById('userList');if(!box)return;box.innerHTML=state.users.map(u=>`<div class="user-row"><div><b>${u.name}</b><small>${u.email}</small></div><div class="hide-user-md"><span class="badge b-blue">${u.role}</span></div><div class="hide-user-md">${u.department||'—'}</div><div><span class="user-status">${u.status==='active'?'Aktif':'Pasif'}</span></div><div class="actions"><button class="btn sm" onclick="openUserModal(${u.id})">Düzenle</button><button class="btn red sm" onclick="deleteUser(${u.id})">Sil</button></div></div>`).join('')}
let editingUserId=null,pendingUserPhoto='';
function loadUserPhoto(input){const f=input.files?.[0];if(!f)return;const reader=new FileReader();reader.onload=()=>pendingUserPhoto=reader.result;reader.readAsDataURL(f)}
function fillUserRoleSelect(value){const s=document.getElementById('usrRole');s.innerHTML=state.roles.map(r=>`<option>${r.name}</option>`).join('');s.value=value}
function openUserModal(id=null){editingUserId=id;const u=id?state.users.find(x=>x.id===id):null;pendingUserPhoto=u?.photo||'';document.getElementById('userModalTitle').textContent=u?'Kullanıcı Düzenle':'Yeni Kullanıcı';document.getElementById('usrName').value=u?.name||'';document.getElementById('usrEmail').value=u?.email||'';fillUserRoleSelect(u?.role||state.roles[0]?.name||'Admin');document.getElementById('usrDepartment').value=u?.department||'Satış';document.getElementById('usrStatus').value=u?.status||'active';document.getElementById('usrNote').value=u?.note||'';document.getElementById('usrPassword').value='';document.getElementById('usrPasswordLabel').textContent=u?'Şifre (boş bırakırsanız değişmez)':'Şifre (en az 8 karakter)';openModal('userModal')}
async function loadDbUsers(){try{const r=await fetch('api/users.php',{cache:'no-store'}),j=await r.json();if(r.ok&&j.ok){state.users=j.users;renderUsers()}}catch(e){console.error(e);toast('Kullanıcı listesi MySQL’den alınamadı.','warn')}}
async function saveUser(){const name=document.getElementById('usrName').value.trim(),email=document.getElementById('usrEmail').value.trim(),password=document.getElementById('usrPassword').value;if(!name||!email)return toast('Ad Soyad ve e-posta zorunlu.','warn');if(!editingUserId&&password.length<8)return toast('Yeni kullanıcı şifresi en az 8 karakter olmalı.','warn');const payload={id:editingUserId,name,email,password,role:document.getElementById('usrRole').value,department:document.getElementById('usrDepartment').value,status:document.getElementById('usrStatus').value,note:document.getElementById('usrNote').value.trim(),photo:pendingUserPhoto||''};try{const r=await fetch('api/users.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(payload)}),j=await r.json();if(!r.ok||!j.ok)throw new Error(j.error||'Kullanıcı kaydedilemedi');closeModal('userModal');editingUserId=null;await loadDbUsers();toast('Kullanıcı MySQL üzerinde kaydedildi.','good')}catch(e){toast(e.message,'warn')}}
async function deleteUser(id){const u=state.users.find(x=>x.id===id);if(!u||!await appConfirm(u.name+' kullanıcısı silinsin mi?','Kullanıcı Silme'))return;try{const r=await fetch('api/users.php?id='+encodeURIComponent(id),{method:'DELETE'}),j=await r.json();if(!r.ok||!j.ok)throw new Error(j.error||'Kullanıcı silinemedi');await loadDbUsers();toast('Kullanıcı MySQL’den silindi.','good')}catch(e){toast(e.message,'warn')}}

