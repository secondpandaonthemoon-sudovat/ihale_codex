// ASAY ERP V3.12.1 · State Revision / Finans Çakışma Düzeltmesi
function parseMoneyInput(v){
 if(typeof v==='number')return Number.isFinite(v)?v:0;
 let s=String(v??'').trim().replace(/\s/g,'').replace(/[€$₺]/g,'');
 if(!s)return 0;
 const hasComma=s.includes(','),hasDot=s.includes('.');
 if(hasComma&&hasDot){
   if(s.lastIndexOf(',')>s.lastIndexOf('.'))s=s.replace(/\./g,'').replace(',','.');
   else s=s.replace(/,/g,'');
 }else if(hasComma){
   s=s.replace(/\./g,'').replace(',','.');
 }else if((s.match(/\./g)||[]).length>1){
   const last=s.lastIndexOf('.');s=s.slice(0,last).replace(/\./g,'')+s.slice(last);
 }
 const n=Number(s);return Number.isFinite(n)?n:0
}
function formatMoneyNumber(n){return Number(n||0).toLocaleString('tr-TR',{minimumFractionDigits:2,maximumFractionDigits:2})}
function normalizeExactDecimalInput(v){
 let s=String(v??'').trim().replace(/\s/g,'').replace(/[€$₺]/g,'');
 if(!s)return '0';
 let sign='';if(s.startsWith('-')){sign='-';s=s.slice(1)}
 const hasComma=s.includes(','),hasDot=s.includes('.');
 if(hasComma&&hasDot){
   if(s.lastIndexOf(',')>s.lastIndexOf('.'))s=s.replace(/\./g,'').replace(',','.');
   else s=s.replace(/,/g,'');
 }else if(hasComma){
   s=s.replace(/\./g,'').replace(',','.');
 }else if((s.match(/\./g)||[]).length>1){
   const last=s.lastIndexOf('.');s=s.slice(0,last).replace(/\./g,'')+s.slice(last);
 }
 s=s.replace(/[^0-9.]/g,'');
 if(!s||s==='.')return '0';
 const parts=s.split('.'),intPart=(parts[0]||'0').replace(/^0+(?=\d)/,'')||'0',frac=(parts[1]||'').replace(/[^0-9]/g,'');
 return sign+intPart+(frac?'.'+frac:'')
}
function exactDecimalToTR(v){
 const s=String(v??'0').trim();if(!s)return '0';
 const neg=s.startsWith('-'),raw=neg?s.slice(1):s,[i='0',f='']=raw.split('.');
 const grouped=(Number(i||0)).toLocaleString('tr-TR',{maximumFractionDigits:0});
 return (neg?'-':'')+grouped+(f?','+f:'')
}
function formatUnitPriceDisplay(v){
 const s=String(v??'0');
 return exactDecimalToTR(/^-?\d+(?:\.\d+)?$/.test(s)?s:normalizeExactDecimalInput(s))
}

function formatQuantity(n){
 const v=Number(n||0);
 return Number.isInteger(v)?v.toLocaleString('tr-TR',{maximumFractionDigits:0}):v.toLocaleString('tr-TR',{minimumFractionDigits:0,maximumFractionDigits:4})
}
const money=(n,c='EUR')=>fmtMoneyCur(n,c);
function sanitizeClientHtml(html){return String(html||'').replace(/<(script|style|iframe|object|embed|link|meta)\b[^>]*>[\s\S]*?<\/\1>/gi,'').replace(/\son[a-z]+\s*=\s*("[^"]*"|'[^']*'|[^\s>]+)/gi,'').replace(/javascript\s*:/gi,'')}
function sanitizeClientStateValue(v,key=''){
 if(Array.isArray(v))return v.map(x=>sanitizeClientStateValue(x,''));
 if(v&&typeof v==='object'){const out={};Object.entries(v).forEach(([k,x])=>out[k]=sanitizeClientStateValue(x,k));return out}
 if(typeof v!=='string')return v;
 if(key==='html')return sanitizeClientHtml(v);
 if(/data$/i.test(key)||['logo','seal','photo','photo_data'].includes(key))return v;
 return v.replace(/\0/g,'').replace(/<[^>]*>/g,'').slice(0,200000)
}
function sanitizeClientStateInPlace(){Object.keys(state).forEach(k=>{state[k]=sanitizeClientStateValue(state[k],k)});normalizeStateSchema();return state}
window.sanitizeClientStateInPlace=sanitizeClientStateInPlace;
const state={
 requests:[],
 supplierDirectory:[],
 suppliers:[],
 selected:{},
 quoteMarkup:22,
 won:false,
 orders:[],
 customerQuotes:{},
 requestAttachments:[],
 pdfColumns:null,
 documents:[],
 accounts:[],
 cashAccounts:[],
 guarantees:[],
 tagCatalog:[{name:'EBRD',color:'#7f56d9',category:'Finansman'},{name:'Ukrayna',color:'#246bfd',category:'Pazar'},{name:'Kosova',color:'#079455',category:'Pazar'},{name:'Acil',color:'#d92d20',category:'Öncelik'},{name:'Öncelikli',color:'#d97706',category:'Öncelik'},{name:'Teklif Bekliyor',color:'#0891b2',category:'Durum'}],
 fx:{usdTry:0,eurTry:0,eurUsd:0,source:'Kur bekleniyor',updated:'—',capturedAt:null},
 company:{name:'AS AY YAPI VE DIŞ TİC. LTD. ŞTİ.',brand:'ASAY Export',web:'www.asayexport.com',email:'info@asayexport.com',phone:'+90',taxOffice:'',taxNo:'',mersis:'',registry:'',address:'İstanbul / Türkiye',eurBankAccountId:null,usdBankAccountId:null,eurBank:'',eurIban:'',usdBank:'',usdIban:'',footer:'AS AY YAPI VE DIŞ TİC. LTD. ŞTİ. · Istanbul / Türkiye',actionSettings:{request:{enabled:true,days:7,today:true,overdue:true},ready:{enabled:true},customer:{enabled:true,days:7,today:true,overdue:true},supplier:{enabled:true,days:7,today:true,overdue:true}}},
 roles:[{name:'Admin',request:true,requestWrite:true,supplier:true,supplierWrite:true,cost:true,costWrite:true,finance:true,financeWrite:true,cash:true,cashWrite:true,docs:true,docsWrite:true,settings:true,settingsWrite:true},{name:'Satış',request:true,requestWrite:true,supplier:false,supplierWrite:false,cost:false,costWrite:false,finance:true,financeWrite:false,cash:false,cashWrite:false,docs:true,docsWrite:true,settings:false,settingsWrite:false},{name:'Satın Alma',request:true,requestWrite:true,supplier:true,supplierWrite:true,cost:true,costWrite:true,finance:false,financeWrite:false,cash:false,cashWrite:false,docs:false,docsWrite:false,settings:false,settingsWrite:false},{name:'Finans',request:false,requestWrite:false,supplier:false,supplierWrite:false,cost:true,costWrite:false,finance:true,financeWrite:true,cash:true,cashWrite:true,docs:true,docsWrite:true,settings:false,settingsWrite:false},{name:'Görüntüleyici',request:true,requestWrite:false,supplier:false,supplierWrite:false,cost:false,costWrite:false,finance:false,financeWrite:false,cash:false,cashWrite:false,docs:false,docsWrite:false,settings:false,settingsWrite:false}],
 users:[],
 ui:{theme:'light',density:'comfortable',sidebar:'standard',cardStyle:'soft',radius:14,fontScale:100,accent:'#246bfd',menuText:'#c9d8ed',menuIcon:'#c9d8ed'},
 appIdentity:{name:'ASAY İhale & Teklif OS',subtitle:'Connected Commerce Workspace',logo:'',favicon:'',userPhoto:'',pdfLogo:''},
 requestNumber:{prefix:'RFQ',format:'prefix-year-seq',next:1,pattern:'{PREFIX}-{YEAR}-{SEQ4}'},
 requestUnits:['Adet','Set','Takım','Kg','Gram','Ton','Metre','m²','m³','Litre','Koli','Paket','Palet','Rulo'],
 cash:[]
};
function normalizeStateSchema(){
 const arrays=['requests','supplierDirectory','suppliers','orders','requestAttachments','documents','accounts','cashAccounts','cash','guarantees','roles','users','tagCatalog','requestUnits'];arrays.forEach(k=>{if(!Array.isArray(state[k]))state[k]=[]});
 if(!state.selected||typeof state.selected!=='object'||Array.isArray(state.selected))state.selected={};if(!state.customerQuotes||typeof state.customerQuotes!=='object'||Array.isArray(state.customerQuotes))state.customerQuotes={};
 if(!state.fx||typeof state.fx!=='object')state.fx={usdTry:0,eurTry:0,eurUsd:0,source:'Kur bekleniyor',updated:'—'};if(!state.company||typeof state.company!=='object')state.company={};if(!state.ui||typeof state.ui!=='object')state.ui={};if(!state.appIdentity||typeof state.appIdentity!=='object')state.appIdentity={};if(!state.requestNumber||typeof state.requestNumber!=='object')state.requestNumber={prefix:'RFQ',format:'prefix-year-seq',next:1,pattern:'{PREFIX}-{YEAR}-{SEQ4}'};
 state.requests.forEach(r=>{if(!r||typeof r!=='object')return;if(!Array.isArray(r.items))r.items=[];if(!Array.isArray(r.tags))r.tags=[];if(!Array.isArray(r.paymentPlan))r.paymentPlan=[];if(!Array.isArray(r.costLines))r.costLines=[];if(!Array.isArray(r.realizedComparisonExpenses))r.realizedComparisonExpenses=[];if(!r.guaranteeConfig||typeof r.guaranteeConfig!=='object')r.guaranteeConfig={enabled:false,type:'Geçici Teminat',rate:0,salesAmount:0,amount:0};delete r.guaranteeConfig.includeInCost;if(r.comparisonExpenses&&typeof r.comparisonExpenses==='object')delete r.comparisonExpenses.guarantee;const legacyStatusMap={'Taslak':'draft','Yeni':'draft','Fiyat Toplanıyor':'collecting','Kıyaslamaya Hazır':'ready','Müşteri Teklifi Hazır':'quote','Teklif Gönderildi':'sent','Gönderildi':'sent','Kazanıldı':'won','Kaybedildi':'lost','İptal':'cancelled','İptal Edildi':'cancelled'};r.status=legacyStatusMap[r.status]||r.status||'collecting';if(!['draft','collecting','ready','quote','sent','won','lost','cancelled'].includes(r.status))r.status='collecting';if(r.customerQuote&&typeof r.customerQuote==='object'){const quoteByStatus={draft:'Taslak',collecting:'Taslak',ready:'Taslak',quote:'Taslak',sent:'Gönderildi',won:'Kazanıldı',lost:'Kaybedildi',cancelled:'İptal Edildi'};r.customerQuote.status=quoteByStatus[r.status]||'Taslak'}});state.guarantees.forEach(g=>{if(g&&typeof g==='object')delete g.includeInCost});
 // Eski sürümde yalnız teklif olarak eklenmiş tedarikçileri de Cari modülünde görünür hale getir.
 const supplierAccountKey=(name,cur)=>normalizedPartyName(name)+'|'+String(cur||'EUR').toUpperCase();
 const existingSupplierAccounts=new Set(state.accounts.filter(a=>a?.type==='supplier').map(a=>supplierAccountKey(a.name,a.currency)));
 state.suppliers.forEach(s=>{const name=String(s?.name||'').trim(),cur=String(s?.currency||'EUR').toUpperCase();if(!name||!['EUR','USD','TRY'].includes(cur))return;const k=supplierAccountKey(name,cur);if(existingSupplierAccounts.has(k))return;const same=state.accounts.find(a=>a?.type==='supplier'&&normalizedPartyName(a.name)===normalizedPartyName(name));const id=Math.max(0,...state.accounts.map(a=>Number(a?.id)||0))+1;state.accounts.push({id,partyKey:same?.partyKey||('pty-'+id),type:'supplier',name,currency:cur,total:0,request:'Teklif Kaynağı',requestOpen:0,entries:[]});existingSupplierAccounts.add(k)});
 state.orders.forEach(o=>{if(!Array.isArray(o.pos))o.pos=[];o.pos.forEach(po=>{if(!Array.isArray(po.items))po.items=[];if(!Array.isArray(po.batches))po.batches=[]})});state.accounts.forEach(a=>{if(!Array.isArray(a.entries))a.entries=[];if(!Array.isArray(a.tags))a.tags=[]});return state
}
window.normalizeStateSchema=normalizeStateSchema;normalizeStateSchema();
function nextNumericId(list){return Math.max(0,...(list||[]).map(x=>Number(x?.id)||0))+1}
function nextItemId(){return Math.max(0,...(state.requests||[]).flatMap(r=>(r.items||[]).map(i=>Number(i.id)||0)))+1}
function clearOperationalStateForFreshInstall(){state.requests=[];state.suppliers=[];state.selected={};state.orders=[];state.customerQuotes={};state.documents=[];state.requestAttachments=[];state.accounts=[];state.cashAccounts=[];state.cash=[];state.guarantees=[];state.supplierDirectory=[];state.requestNumber={prefix:'RFQ',format:'prefix-year-seq',next:1,pattern:'{PREFIX}-{YEAR}-{SEQ4}'}}
state.requests.forEach(r=>r.items=(r.items||[]).map(i=>({...i,brandModel:i.brandModel||''})));
const titles={dashboard:['Dashboard','Bugünün operasyon ve finans görünümü'],requests:['Talep Merkezi','İhale ve müşteri taleplerinin uçtan uca yönetimi'],attachments:['Dokümanlar','İhale, şartname, çizim ve talep dosyaları'],orders:['Sipariş & Operasyon','Üretim, sevkiyat ve tedarikçi ödemeleri'],accounts:['Cari Hesaplar','Talep bazlı açık cari ve genel bakiye'],cash:['Kasa & Banka','Tahsilat, ödeme ve virman'],guarantees:['Teminat Takip','Teminat, banka mektubu ve vade yönetimi'],docs:['Ticari Evraklar','A4 teklif, proforma, packing ve invoice'],settings:['Ayarlar','Tema, firma ve PDF tasarımı']};
function go(v){
 const target=document.getElementById('view-'+v),meta=titles[v];if(!target||!meta){toast('Açılmak istenen bölüm bulunamadı: '+v,'warn');return false}
 if(v==='attachments')setTimeout(()=>refreshAttachmentLibraryFromServer(true),0);
 document.querySelectorAll('.view').forEach(x=>x.classList.remove('active'));
 target.classList.add('active');
 document.querySelectorAll('.navbtn').forEach(x=>x.classList.toggle('active',x.dataset.view===v));
 document.getElementById('pageTitle').textContent=meta[0];document.getElementById('pageSubtitle').textContent=meta[1];
 document.getElementById('sidebar').classList.remove('open');document.getElementById('scrim').classList.remove('show');window.scrollTo({top:0,behavior:'smooth'});
 if(v==='orders')renderOrders(); if(v==='accounts')renderAccounts(); if(v==='cash')renderCash(); if(v==='guarantees')renderGuarantees(); if(v==='docs'){syncDocRequestSelect();renderSavedDocs();applyPdfScale();} if(v==='settings'){renderPermissions();loadCompanyToForm();loadRequestNumberSettings();renderUsers();if(window.currentCanWrite?.('settings')||String(currentSessionUser?.role||'').toLowerCase()==='admin')loadDbUsers();loadUiSettings();applyAppIdentity();initSettingsWorkspace();}
 return true
}
document.querySelectorAll('.navbtn').forEach(b=>b.addEventListener('click',()=>go(b.dataset.view)));
document.getElementById('mobileToggle').onclick=()=>{document.getElementById('sidebar').classList.toggle('open');document.getElementById('scrim').classList.toggle('show')};
document.getElementById('scrim').onclick=()=>{document.getElementById('sidebar').classList.remove('open');document.getElementById('scrim').classList.remove('show')};
function toast(msg,type='good'){const t=document.createElement('div');t.className='toast '+type;t.textContent=msg;t.title='Kapatmak için tıklayın';t.onclick=()=>t.remove();document.getElementById('toasts').appendChild(t);setTimeout(()=>t.remove(),type==='warn'?9000:4200)}
let lastServerErrorDetails='';
function closeServerError(){document.getElementById('serverErrorOverlay')?.remove()}
async function copyServerErrorDetails(){try{await navigator.clipboard.writeText(lastServerErrorDetails);toast('Hata detayı panoya kopyalandı.','good')}catch(_e){toast('Hata detayı kopyalanamadı.','warn')}}
function showServerError(title,message,details=''){
 lastServerErrorDetails=[title,message,details].filter(Boolean).join('\n\n');closeServerError();const ov=document.createElement('div');ov.id='serverErrorOverlay';ov.className='server-error-overlay';const card=document.createElement('div');card.className='server-error-card';const h=document.createElement('h3');h.textContent=title||'Sunucu Hatası';const p=document.createElement('p');p.textContent=message||'İşlem tamamlanamadı.';const pre=document.createElement('pre');pre.textContent=details||'Detay bulunmuyor.';const act=document.createElement('div');act.className='actions';const cp=document.createElement('button');cp.className='btn';cp.textContent='Hata Detayını Kopyala';cp.onclick=copyServerErrorDetails;const cl=document.createElement('button');cl.className='btn primary';cl.textContent='Kapat';cl.onclick=closeServerError;act.append(cp,cl);card.append(h,p,pre,act);ov.appendChild(card);document.body.appendChild(ov)
}

let excelImportMode='accounts',excelImportHeaders=[],excelImportRows=[];
const importFields={
 accounts:[
  ['','— Aktarma —'],['name','Firma / Kurum Adı *'],['type','Cari Tipi'],['currency','Para Birimi'],['balance','Açılış Bakiyesi'],
  ['taxNo','Vergi / Kurum No'],['contact','Yetkili'],['phone','Telefon'],['email','E-posta'],['website','Web Sitesi'],['address','Adres'],
  ['country','Ülke'],['dueDate','Vade Tarihi'],['description','Açıklama'],['note','Not']
 ],
 requests:[
  ['','— Aktarma —'],['name','Ürün / Hizmet Adı *'],['qty','Miktar *'],['unit','Birim'],['brandModel','Marka / Model'],['spec','Teknik Özellik / Açıklama']
 ]
};
function excelRequestCustomerOptions(){
 return groupedAccounts()
  .filter(g=>['customer','public','private'].includes(g.type))
  .sort((a,b)=>a.name.localeCompare(b.name,'tr'))
  .map(g=>`<option value="${escAttr(g.partyKey)}">${g.name} · ${accountTypeLabel(g.type)}</option>`).join('')
}
function openExcelImport(mode){
 excelImportMode=mode;excelImportHeaders=[];excelImportRows=[];
 document.getElementById('excelImportFile').value='';
 document.getElementById('excelImportMapping').innerHTML='<div class="empty">Önce Excel dosyasını seçin.</div>';
 document.getElementById('excelImportPreview').innerHTML='<div class="empty">Veri önizlemesi burada gösterilecek.</div>';
 document.getElementById('excelImportCount').textContent='0 satır';document.getElementById('excelImportFileBadge').textContent='Dosya bekleniyor';document.getElementById('excelImportCommit').disabled=true;
 const isReq=mode==='requests',meta=document.getElementById('excelSingleRequestMeta');
 meta.style.display=isReq?'block':'none';
 document.getElementById('excelMappingTitle').textContent=isReq?'3. Talep Kalemlerini Eşleştir':'2. Excel Sütunlarını Sistem Alanlarıyla Eşleştir';
 document.getElementById('excelImportTitle').textContent=isReq?'Excel’den Tek Talep Yükle':'Excel’den Cari Kart Yükle';
 document.getElementById('excelImportSub').textContent=isReq?'Bir Excel dosyası = bir talep. Müşteriyi mevcut cari kartlardan seçin, Excel’den yalnız kalemleri yükleyin.':'Cari kartlarını sütun eşleştirme ve tekrar kontrolü ile güvenli şekilde içe aktarın.';
 document.getElementById('excelImportRule').innerHTML=isReq
  ?'<div class="import-status-lock"><b>Tek Talep Modu:</b> Excel yalnız ürün/hizmet kalemlerini içerir. Müşteri, döviz, tarih ve teslim bilgileri bu pencereden seçilir. Talep otomatik olarak <b>Fiyat Toplanıyor</b> durumunda açılır.</div>'
  :'<b>Tekrar kontrolü:</b> Vergi No varsa Vergi No + para birimi; yoksa Firma Adı + Cari Tipi + para birimi ile tekrarlar atlanır.';
 if(isReq){
  const sel=document.getElementById('excelReqCustomer');sel.innerHTML='<option value="">— Mevcut Müşteri / Kurum Seç —</option>'+excelRequestCustomerOptions();
  document.getElementById('excelReqSourceRef').value='';
  document.getElementById('excelReqCurrency').value='EUR';
  document.getElementById('excelReqDeadline').value=new Date(Date.now()+14*86400000).toISOString().slice(0,10);
  document.getElementById('excelReqDelivery').value='CIF';
  document.getElementById('excelReqDeliveryPlace').value='';
  document.getElementById('excelReqPriority').value='normal';
  document.getElementById('excelReqDescription').value='';
 }
 openModal('excelImportModal')
}
function normalizeImportHeader(v){return String(v||'').trim().toLocaleLowerCase('tr-TR').replace(/[çÇ]/g,'c').replace(/[ğĞ]/g,'g').replace(/[ıİ]/g,'i').replace(/[öÖ]/g,'o').replace(/[şŞ]/g,'s').replace(/[üÜ]/g,'u').replace(/[^a-z0-9]+/g,' ')}
function suggestImportField(header){
 const h=normalizeImportHeader(header),dict=excelImportMode==='accounts'
 ?[['name',['firma','kurum','cari adi','unvan','musteri','tedarikci']],['type',['cari tipi','tip']],['currency',['para birimi','doviz','currency']],['balance',['acilis bakiyesi','bakiye']],['taxNo',['vergi no','vergi numarasi','kurum no']],['contact',['yetkili','ilgili kisi']],['phone',['telefon','phone']],['email',['e posta','email','mail']],['website',['web sitesi','website','web']],['address',['adres']],['country',['ulke','country']],['dueDate',['vade tarihi','vade']],['description',['aciklama']],['note',['not']]]
 :[['name',['urun hizmet adi','urun','hizmet','malzeme','kalem','urun adi']],['qty',['miktar','adet','qty','quantity']],['unit',['birim','unit']],['brandModel',['marka model','marka','model']],['spec',['teknik ozellik aciklama','teknik ozellik','ozellik','aciklama','spec']]];
 for(const [key,words] of dict)if(words.some(w=>h===w||h.includes(w)))return key;return ''
}
async function previewExcelImport(){
 const file=document.getElementById('excelImportFile').files[0];if(!file)return;
 const fd=new FormData();fd.append('file',file);
 document.getElementById('excelImportFileBadge').textContent='Okunuyor…';
 try{
  const res=await fetch('api/import_preview.php',{method:'POST',body:fd}),j=await res.json();if(!res.ok||!j.ok)throw new Error(j.error||'Excel okunamadı');
  excelImportHeaders=j.headers||[];excelImportRows=j.rows||[];
  document.getElementById('excelImportFileBadge').textContent=j.filename||file.name;document.getElementById('excelImportCount').textContent=excelImportRows.length+(excelImportMode==='requests'?' kalem':' satır');
  renderExcelMapping();renderExcelPreview();document.getElementById('excelImportCommit').disabled=!excelImportRows.length
 }catch(e){toast(e.message,'warn');document.getElementById('excelImportFileBadge').textContent='Dosya hatası'}
}
function renderExcelMapping(){
 const fields=importFields[excelImportMode],box=document.getElementById('excelImportMapping');
 box.innerHTML=excelImportHeaders.map((h,i)=>{const suggested=suggestImportField(h);return `<div class="excel-map-row"><b title="${escAttr(h)}">${h}</b><span>→</span><select data-import-col="${i}" onchange="renderExcelPreview()">${fields.map(([v,l])=>`<option value="${v}" ${v===suggested?'selected':''}>${l}</option>`).join('')}</select></div>`}).join('')
}
function currentImportMapping(){
 const m={};document.querySelectorAll('[data-import-col]').forEach(s=>{if(s.value)m[+s.dataset.importCol]=s.value});return m
}
function mappedImportRows(){
 const map=currentImportMapping();return excelImportRows.map(row=>{const o={};Object.entries(map).forEach(([ci,key])=>o[key]=String(row[+ci]??'').trim());return o}).filter(o=>Object.values(o).some(v=>v!==''))
}
function renderExcelPreview(){
 const rows=mappedImportRows().slice(0,10),fields=excelImportMode==='accounts'?['name','type','currency','taxNo','country']:['name','qty','unit','brandModel','spec'];
 const labels=Object.fromEntries(importFields[excelImportMode]);
 const box=document.getElementById('excelImportPreview');if(!rows.length){box.innerHTML='<div class="empty">Eşleştirilmiş veri yok.</div>';return}
 box.innerHTML=`<table class="table"><thead><tr>${fields.map(f=>`<th>${labels[f]||f}</th>`).join('')}</tr></thead><tbody>${rows.map(r=>`<tr>${fields.map(f=>`<td>${String(r[f]||'—')}</td>`).join('')}</tr>`).join('')}</tbody></table>`
}
async function commitExcelImport(){
 const rows=mappedImportRows();if(!rows.length)return toast('İçe aktarılacak veri yok.','warn');
 if(excelImportMode==='accounts'){
  if(!rows.some(r=>r.name))return toast('Firma / Kurum Adı alanını bir Excel sütunuyla eşleştirin.','warn');
  if(!await appConfirm(`${rows.length} cari satırı tekrar kontrolü ile içe aktarılsın mı?`,'Cari İçe Aktarma'))return;
  if(await runServerOperation({action:'account_bulk_import',rows,idempotencyKey:makeIdempotencyKey()})){closeModal('excelImportModal');toast('Cari Excel içe aktarma tamamlandı.','good');go('accounts')}
  return
 }
 const customerPartyKey=document.getElementById('excelReqCustomer').value;
 if(!customerPartyKey)return toast('Mevcut Müşteri / Kurum seçin. Yeni müşteri gerekiyorsa önce Cari Hesaplar bölümünden oluşturun.','warn');
 if(!rows.some(r=>r.name))return toast('Ürün / Hizmet Adı alanını Excel sütunuyla eşleştirin.','warn');
 const invalidQty=rows.find(r=>r.name&&parseMoneyInput(r.qty)<=0);if(invalidQty)return toast('Her talep kaleminin miktarı 0’dan büyük olmalı: '+invalidQty.name,'warn');
 const items=rows.filter(r=>r.name).map(r=>({name:r.name,qty:parseMoneyInput(r.qty),unit:r.unit||'Adet',brandModel:r.brandModel||'',spec:r.spec||''}));
 if(!items.length)return toast('Geçerli talep kalemi bulunamadı.','warn');
 const excelTitle=document.getElementById('excelReqTitle').value.trim();if(!excelTitle)return toast('Konu Başlığı zorunludur.','warn');
 const meta={
  customerPartyKey,
  title:excelTitle,
  sourceRef:document.getElementById('excelReqSourceRef').value.trim(),
  currency:document.getElementById('excelReqCurrency').value,
  deadline:document.getElementById('excelReqDeadline').value,
  delivery:document.getElementById('excelReqDelivery').value,
  deliveryPlace:document.getElementById('excelReqDeliveryPlace').value.trim(),
  priority:document.getElementById('excelReqPriority').value,
  description:document.getElementById('excelReqDescription').value.trim()
 };
 const customerName=document.getElementById('excelReqCustomer').selectedOptions[0]?.textContent||'seçili müşteri';
 if(!await appConfirm(`${customerName} için ${items.length} kalemli TEK talep oluşturulsun mu?`,'Talep Oluştur'))return;
 if(await runServerOperation({action:'request_single_import',meta,items,idempotencyKey:makeIdempotencyKey()})){
  closeModal('excelImportModal');toast(`${items.length} kalemli tek talep oluşturuldu. Durum: Fiyat Toplanıyor.`,'good');go('requests')
 }
}
function downloadImportTemplate(){
 const file=excelImportMode==='accounts'?'templates/Cari_Kart_Aktarim_Sablonu.xlsx':'templates/Tek_Talep_Kalemleri_Sablonu.xlsx';
 const a=document.createElement('a');a.href=file;
 a.download=excelImportMode==='accounts'?'Cari_Kart_Aktarim_Sablonu.xlsx':'Tek_Talep_Kalemleri_Sablonu.xlsx';
 document.body.appendChild(a);a.click();a.remove()
}
const activeEntityLocks={};
async function acquireEntityLock(type,id,modalId){
 if(!id)return true;
 try{
  const r=await fetch('api/entity_lock.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'acquire',entity_type:type,entity_id:String(id)})});
  const j=await r.json();if(!r.ok||!j.ok)throw new Error(j.error||'Kayıt kilitlenemedi');
  activeEntityLocks[modalId]={type,id:String(id),token:j.token};return true
 }catch(e){toast(e.message,'warn');return false}
}
async function releaseEntityLock(modalId){
 const l=activeEntityLocks[modalId];if(!l)return;
 delete activeEntityLocks[modalId];
 try{await fetch('api/entity_lock.php',{method:'POST',headers:{'Content-Type':'application/json'},keepalive:true,body:JSON.stringify({action:'release',entity_type:l.type,entity_id:l.id,token:l.token})})}catch(_e){}
}
setInterval(()=>{Object.entries(activeEntityLocks).forEach(async([mid,l])=>{try{const r=await fetch('api/entity_lock.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'renew',entity_type:l.type,entity_id:l.id,token:l.token})});if(!r.ok){delete activeEntityLocks[mid];toast('Düzenleme kilidi kaybedildi: '+l.type+' '+l.id,'warn')}}catch(_e){}})},120000);
window.addEventListener('beforeunload',()=>{Object.keys(activeEntityLocks).forEach(mid=>releaseEntityLock(mid))});

function modalFormSignature(m){return [...m.querySelectorAll('input,select,textarea')].filter(x=>x.type!=='file').map(x=>`${x.id||x.name}:${x.type==='checkbox'||x.type==='radio'?x.checked:x.value}`).join('|')}
function modalHasUnsavedChanges(m){return !!m&&m.dataset.openSignature!==undefined&&m.dataset.openSignature!==modalFormSignature(m)}
function portalModalToBody(m){
 if(!m||!m.classList?.contains('modal-backdrop'))return m;
 if(m.parentElement!==document.body)document.body.appendChild(m);
 m.dataset.portalized='1';
 return m;
}
function portalAllModals(){document.querySelectorAll('.modal-backdrop').forEach(portalModalToBody)}
function bindModalBackdrop(m){
 if(!m||m.dataset.backdropBound==='1')return;
 m.dataset.backdropBound='1';
 m.addEventListener('click',e=>{if(e.target===m){e.preventDefault();e.stopPropagation();requestModalClose(m)}});
}
function initializeModalPortals(){
 portalAllModals();
 document.querySelectorAll('.modal-backdrop').forEach(bindModalBackdrop);
 new MutationObserver(ms=>ms.forEach(rec=>rec.addedNodes.forEach(n=>{if(n.nodeType!==1)return;if(n.matches?.('.modal-backdrop')){portalModalToBody(n);bindModalBackdrop(n)}n.querySelectorAll?.('.modal-backdrop').forEach(x=>{portalModalToBody(x);bindModalBackdrop(x)})}))).observe(document.body,{childList:true,subtree:true});
}
function openModal(id){
 const m=portalModalToBody(document.getElementById(id));if(!m)return;
 bindModalBackdrop(m);
 m._returnFocus=document.activeElement instanceof HTMLElement?document.activeElement:null;
 m.setAttribute('role','dialog');m.setAttribute('aria-modal','true');const panel=m.querySelector('.modal');if(panel&&!panel.hasAttribute('tabindex'))panel.setAttribute('tabindex','-1');
 m.classList.add('show');document.body.classList.add('ui-modal-open');document.body.style.overflow='hidden';m.dataset.openSignature=modalFormSignature(m);
 requestAnimationFrame(()=>{const target=m.querySelector('input:not([type=hidden]):not([disabled]),select:not([disabled]),textarea:not([disabled]),button:not(.close):not([disabled]),.close');(target||panel)?.focus?.()})
}
function closeModal(id){
 const m=document.getElementById(id);if(!m)return;
 m.classList.remove('show');m.classList.remove('nested-modal');m.classList.remove('modal-protect-pulse');m.style.removeProperty('z-index');delete m.dataset.parentModal;delete m.dataset.openSignature;
 releaseEntityLock(id);const hasOpenModal=!!document.querySelector('.modal-backdrop.show');document.body.style.overflow=hasOpenModal?'hidden':'';document.body.classList.toggle('ui-modal-open',hasOpenModal);const ret=m._returnFocus;m._returnFocus=null;setTimeout(()=>ret?.focus?.(),0)
}
async function requestModalClose(m){if(!m)return;if(modalHasUnsavedChanges(m)&&!await appConfirm('Kaydedilmemiş değişiklikler var. Pencere kapatılsın mı?','Kaydedilmemiş Değişiklik'))return;closeModal(m.id)}
if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',initializeModalPortals,{once:true});else initializeModalPortals();
document.addEventListener('keydown',e=>{
 const open=[...document.querySelectorAll('.modal-backdrop.show')];if(!open.length)return;const top=open[open.length-1];
 if(e.key==='Escape'){e.preventDefault();e.stopPropagation();requestModalClose(top);return}
 if(e.key==='Tab'){
   const focusable=[...top.querySelectorAll('button:not([disabled]),a[href],input:not([disabled]):not([type=hidden]),select:not([disabled]),textarea:not([disabled]),[tabindex]:not([tabindex="-1"])')].filter(x=>x.offsetParent!==null);if(!focusable.length)return;
   const first=focusable[0],last=focusable[focusable.length-1];if(e.shiftKey&&document.activeElement===first){e.preventDefault();last.focus()}else if(!e.shiftKey&&document.activeElement===last){e.preventDefault();first.focus()}
 }
});
function openQuick(){openModal('quickModal')} function openLatestComparison(){const r=state.requests.find(x=>requestSuppliers(x.id).length>0)||state.requests[0];if(!r)return toast('Kıyaslanacak talep bulunmuyor.','warn');openRequestComparison(r.id)} let editingRequestId=null;
const requestPaymentPresets={
 cash:{label:'Peşin / T/T',plan:[{percent:100,label:'Peşin / T/T'}]},
 tt30_70:{label:'T/T %30 Peşin / %70 Sevkiyat Öncesi',plan:[{percent:30,label:'Peşin'},{percent:70,label:'Sevkiyat Öncesi'}]},
 tt50_50:{label:'%50 Peşin / %50 Nakliye Öncesi',plan:[{percent:50,label:'Peşin'},{percent:50,label:'Nakliye Öncesi'}]},
 net30:{label:'Vadeli 30 Gün',plan:[{percent:100,label:'30 Gün Vadeli'}]},
 lc:{label:'Akreditif (L/C)',plan:[{percent:100,label:'Akreditif (L/C)'}]},
 cad:{label:'Vesaik Mukabili (CAD)',plan:[{percent:100,label:'Vesaik Mukabili (CAD)'}]},
 open_account:{label:'Mal Mukabili',plan:[{percent:100,label:'Mal Mukabili'}]}
};
let manualPaymentRows=[];
function paymentKeyFromRequest(r){
 if(r?.paymentType)return r.paymentType;const p=String(r?.payment||'');
 if(p.includes('%50')&&p.toLocaleLowerCase('tr-TR').includes('nakliye'))return 'tt50_50';
 if(p.includes('%30')&&p.includes('%70'))return 'tt30_70';
 if(p.includes('Vadeli 30'))return 'net30';if(p.includes('Akreditif'))return 'lc';if(p.includes('Vesaik'))return 'cad';if(p.includes('Mal Mukabili'))return 'open_account';
 if(p.includes('Özel')||Array.isArray(r?.paymentPlan)&&r.paymentPlan.length>1)return 'manual';return 'cash'
}
function renderManualPaymentRows(){
 const box=document.getElementById('manualPaymentRows');if(!box)return;
 box.innerHTML=manualPaymentRows.length?manualPaymentRows.map((row,i)=>`<div class="payment-plan-row"><div class="field"><label>Oran %</label><input type="number" min="0.01" max="100" step=".01" value="${Number(row.percent||0)}" oninput="manualPaymentRows[${i}].percent=Number(this.value)||0;updateManualPaymentTotal()"></div><div class="field"><label>Ödeme Aşaması</label><input value="${escAttr(row.label||'')}" placeholder="Örn. Sipariş onayında" oninput="manualPaymentRows[${i}].label=this.value"></div><button class="btn red sm" type="button" onclick="removeManualPaymentRow(${i})">Sil</button></div>`).join(''):'<div class="empty">Ödeme aşaması yok. + Aşama Ekle ile başlayın.</div>';
 updateManualPaymentTotal()
}
function addManualPaymentRow(){manualPaymentRows.push({percent:0,label:''});renderManualPaymentRows()}
function removeManualPaymentRow(i){manualPaymentRows.splice(i,1);renderManualPaymentRows()}
function updateManualPaymentTotal(){const t=manualPaymentRows.reduce((n,x)=>n+(Number(x.percent)||0),0),el=document.getElementById('manualPaymentTotal');if(el){el.textContent='%'+t.toLocaleString('tr-TR',{maximumFractionDigits:2});el.style.color=Math.abs(t-100)<.001?'var(--success)':'var(--danger)'}}
function syncRequestPaymentPlan(){const key=document.getElementById('newPayment')?.value||'tt30_70',box=document.getElementById('manualPaymentPlan');if(box)box.classList.toggle('show',key==='manual');if(key==='manual'&&!manualPaymentRows.length){manualPaymentRows=[{percent:50,label:'Peşin'},{percent:50,label:'Nakliye Öncesi'}];renderManualPaymentRows()}}
function currentRequestPaymentData(){
 const key=document.getElementById('newPayment').value;
 if(key==='manual'){const plan=manualPaymentRows.map(x=>({percent:Number(x.percent)||0,label:String(x.label||'').trim()})).filter(x=>x.percent>0||x.label),total=plan.reduce((n,x)=>n+x.percent,0);if(!plan.length)throw new Error('Manuel ödeme planına en az bir aşama ekleyin.');if(plan.some(x=>x.percent<=0||!x.label))throw new Error('Her manuel ödeme aşamasında oran ve açıklama zorunludur.');if(Math.abs(total-100)>.001)throw new Error('Manuel ödeme oranlarının toplamı %100 olmalıdır. Mevcut toplam: %'+total.toLocaleString('tr-TR',{maximumFractionDigits:2}));return {key:'manual',label:'Özel / Manuel',plan}}
 const preset=requestPaymentPresets[key]||requestPaymentPresets.tt30_70;return {key,label:preset.label,plan:preset.plan.map(x=>({...x}))}
}
function priorityLabel(v){return v==='urgent'?'Acil':v==='priority'?'Öncelikli':'Normal'}
function syncPriorityTags(r,priority){r.priority=priority;const names=entityTagNames(r.tags).filter(n=>!['Acil','Öncelikli'].includes(n));if(priority==='urgent')names.push('Acil');else if(priority==='priority')names.push('Öncelikli');r.tags=names.map(n=>{const t=tagByName(n);return [n,t?.color||'#667085',t?.category||'Genel']})}
async function openRequestModal(id=null){
 if(id&&!await acquireEntityLock('request',id,'requestModal'))return;
 refreshRequestCustomerSelect();editingRequestId=id;
 const r=id?state.requests.find(x=>x.id===id):null;
 if(r)ensureUniqueRequestItemIds(r);
 document.querySelector('#requestModal h2').textContent=r?'Talep / İhale Düzenle':'Yeni Talep / İhale';
 if(r){
  document.getElementById('newCustomerSelect').value=r.customerPartyKey||groupedAccounts().find(g=>g.name===r.customer)?.partyKey||'';
  document.getElementById('newType').value='Yurtdışı Müşteri Talebi';
  document.getElementById('newCurrency').value=r.currency;
  document.getElementById('newDeadline').value=/^\d{4}-\d{2}-\d{2}$/.test(r.deadline||'')?r.deadline:todayISO();
  document.getElementById('newDelivery').value=r.delivery||'CIF';
  document.getElementById('newDeliveryPlace').value=r.deliveryPlace||r.country||'';
  const payKey=paymentKeyFromRequest(r);document.getElementById('newPayment').value=payKey;manualPaymentRows=(r.paymentPlan||requestPaymentPresets[payKey]?.plan||[]).map(x=>({percent:Number(x.percent)||0,label:x.label||''}));document.getElementById('newPriority').value=r.priority||'normal';renderManualPaymentRows();syncRequestPaymentPlan();
  document.getElementById('newSubject').value=r.title||'';
  document.getElementById('newDescription').value=r.description||'';
  tempItems=r.items.map(x=>({id:x.id,name:x.name,qty:x.qty,unit:x.unit,brandModel:x.brandModel||'',spec:x.spec||''}));
 }else{
  document.getElementById('newSubject').value='';document.getElementById('newDescription').value='';document.getElementById('newPriority').value='normal';document.getElementById('newPayment').value='tt30_70';manualPaymentRows=[];syncRequestPaymentPlan();
  tempItems=[];const d=new Date();d.setDate(d.getDate()+14);document.getElementById('newDeadline').value=d.toISOString().slice(0,10);
 }
 document.getElementById('requestAttachmentFiles').value='';document.getElementById('requestAttachmentDescription').value='';document.getElementById('requestAttachmentPending').textContent=r?'Yeni dosya ekleyebilirsiniz.':'Dosyalar talep kaydedildiğinde yüklenecek.';openModal('requestModal');syncBank(r?.bankAccountId??null,r?.bank||'');renderRequestItemEditor()
}
function todayISO(){const d=new Date(),z=n=>String(n).padStart(2,'0');return `${d.getFullYear()}-${z(d.getMonth()+1)}-${z(d.getDate())}`}
function todayTR(){return new Date().toLocaleDateString('tr-TR')}
function requestDeadlineInfo(r){let d=null,x=String(r.deadline||'');if(/^\d{4}-\d{2}-\d{2}$/.test(x))d=new Date(x+'T12:00:00');else{const m=x.match(/^(\d{2})\.(\d{2})\.(\d{4})$/);if(m)d=new Date(`${m[3]}-${m[2]}-${m[1]}T12:00:00`)}if(!d||isNaN(d))return {class:r.deadlineClass||'green',text:r.days||'—'};const now=new Date();now.setHours(0,0,0,0);d.setHours(0,0,0,0);const days=Math.ceil((d-now)/86400000);return {class:days<0?'red':days===0?'red':days<=3?'yellow':'green',text:days<0?`${Math.abs(days)} gün gecikti`:days===0?'Bugün':days===1?'Yarın':days+' gün'}}
function refreshRequestCustomerSelect(){
 const s=document.getElementById('newCustomerSelect');if(!s)return;ensurePartyKeys();const current=s.value,groups=groupedAccounts().filter(g=>['customer','public','private'].includes(g.type));
 s.innerHTML='<option value="">Müşteri / kurum seçin</option>'+groups.map(g=>`<option value="${escAttr(g.partyKey)}">${g.name}${g.profile.taxNo?' · '+g.profile.taxNo:''}</option>`).join('');
 if(groups.some(g=>String(g.partyKey)===String(current)))s.value=current
}
function openCashModal(partyName='',mode='receipt'){
 const bank=document.getElementById('cashBank'),req=document.getElementById('cashRequest'),party=document.getElementById('cashParty');
 bank.innerHTML=state.cashAccounts.map(a=>`<option value="${a.id}">${a.name} · ${a.currency} · ${fmtMoneyCur(a.balance,a.currency)}</option>`).join('');
 req.innerHTML='<option value="">Genel / Talep Yok</option>'+state.requests.map(r=>`<option value="${r.no}">${r.no} · ${r.customer} · ${r.currency}</option>`).join('');
 party.innerHTML=state.accounts.map(a=>`<option value="${a.id}">${accountTypeLabel(a.type)} · ${a.name} · ${a.currency}</option>`).join('');
 const pa=state.accounts.find(a=>a.name===partyName&&a.currency===activeAccountCurrency)||state.accounts.find(a=>a.name===partyName);if(pa)party.value=pa.id;
 document.getElementById('cashType').value=mode;document.getElementById('cashDate').value=todayISO();document.getElementById('cashAmount').value='';document.getElementById('cashRef').value=pa?.request||'';document.getElementById('cashDesc').value=mode==='receipt'?'Müşteri Tahsilatı':'Tedarikçiye Ödeme';
 if(pa){const ca=state.cashAccounts.find(x=>x.currency===pa.currency&&x.type==='Banka')||state.cashAccounts.find(x=>x.currency===pa.currency);if(ca)bank.value=ca.id;const rr=state.requests.find(r=>r.no===pa.request);if(rr)req.value=rr.no}
 syncCashFormCurrency();syncCashPoOptions();openModal('cashModal')
}
function syncCashPoOptions(){const field=document.getElementById('cashPoField'),sel=document.getElementById('cashPo'),party=state.accounts.find(x=>x.id===+document.getElementById('cashParty')?.value),mode=document.getElementById('cashType')?.value,req=document.getElementById('cashRequest')?.value||'';if(!field||!sel)return;const isPay=mode==='payment'&&party?.type==='supplier';field.style.display=isPay?'grid':'none';if(!isPay){sel.innerHTML='<option value="">Genel Cari Ödemesi</option>';return}const rows=[];state.orders.filter(isValidOperationalOrder).forEach(o=>(o.pos||[]).forEach(po=>{const batchActive=(o.deliveryBatches||[]).some(b=>b.status!=='İptal Edildi');if(!batchActive&&normalizedPartyName(po.supplier)===normalizedPartyName(party.name)&&Number(po.due||0)>0&&(!req||o.request===req||state.requests.find(r=>r.id===o.requestId)?.no===req))rows.push({po,o})}));sel.innerHTML='<option value="">Genel Cari Ödemesi</option>'+rows.map(x=>`<option value="${x.po.id}">${x.po.no} · ${x.o.request||''} · ${money(x.po.due,x.o.currency||'EUR')} kalan</option>`).join('')}
function syncCashFormCurrency(){const a=state.cashAccounts.find(x=>x.id===+document.getElementById('cashBank')?.value),p=state.accounts.find(x=>x.id===+document.getElementById('cashParty')?.value),box=document.getElementById('cashFormInfo');if(box)box.innerHTML=a&&p?`Kasa: <b>${a.currency}</b> · Cari: <b>${p.currency}</b>${a.currency!==p.currency?' · <b style="color:var(--danger)">Para birimleri farklı. Önce Virman kullanın.</b>':''}<br>Açık bakiye: <b>${fmtMoneyCur(p.requestOpen||0,p.currency)}</b>`:''}
function toggleNewCustomer(force){const b=document.getElementById('newCustomerBox');b.classList.toggle('show',force===undefined?!b.classList.contains('show'):force)}
function saveInlineCustomer(){const name=document.getElementById('inlineCustomerName').value.trim();if(!name)return toast('Firma / kurum adı gerekli.','warn');const type=document.getElementById('newType').value.includes('Resmî')?'public':'customer',country=document.getElementById('inlineCustomerCountry').value.trim(),contact=document.getElementById('inlineCustomerContact').value.trim(),phone=document.getElementById('inlineCustomerPhone').value.trim(),email=document.getElementById('inlineCustomerEmail').value.trim();const cur=document.getElementById('newCurrency').value;let a=state.accounts.find(x=>x.type===type&&normalizedPartyName(x.name)===normalizedPartyName(name)&&x.currency===cur);if(!a){const id=Math.max(0,...state.accounts.map(x=>x.id||0))+1;a={id,partyKey:'pty-'+id+'-'+Date.now().toString(36),type,name,currency:cur,total:0,request:'Manuel Cari',requestOpen:0,country,contact,phone,email,entries:[]};state.accounts.push(a)}refreshRequestCustomerSelect();document.getElementById('newCustomerSelect').value=a.partyKey;toggleNewCustomer(false);persistAccounts();toast(name+' kaydedildi ve cari kartı oluşturuldu.','good')}
document.getElementById('newType').addEventListener('change',e=>document.querySelectorAll('.vat-field').forEach(x=>x.style.display=e.target.value.includes('Yurtiçi')?'grid':'none'));
document.getElementById('newCurrency').addEventListener('change',syncBank);
function requestBankAccounts(currency){
 const cur=String(currency||document.getElementById('newCurrency')?.value||'EUR').toUpperCase();
 return (state.cashAccounts||[]).filter(a=>String(a.currency||'').toUpperCase()===cur);
}
function syncBank(preferredId=null,preferredName=''){
 const cur=document.getElementById('newCurrency')?.value||'EUR',sel=document.getElementById('newBank'),help=document.getElementById('newBankHelp');if(!sel)return;
 const previous=preferredId!==null&&preferredId!==undefined&&preferredId!==''?String(preferredId):String(sel.value||'');
 const accounts=requestBankAccounts(cur);
 if(!accounts.length){sel.innerHTML='<option value="">'+cur+' için kasa / banka hesabı bulunmuyor</option>';sel.value='';sel.disabled=true;if(help)help.textContent=cur+' para biriminde Kasa & Banka hesabı açın.';return}
 sel.disabled=false;
 sel.innerHTML='<option value="">Kasa / banka seçin</option>'+accounts.map(a=>`<option value="${a.id}">${esc(a.name)} · ${esc(a.type||'Hesap')} · ${fmtMoneyCur(Number(a.balance||0),a.currency)}</option>`).join('');
 let target=accounts.find(a=>String(a.id)===previous)||accounts.find(a=>preferredName&&String(a.name)===String(preferredName));
 if(!target){const companyId=cur==='USD'?state.company?.usdBankAccountId:cur==='EUR'?state.company?.eurBankAccountId:null;target=accounts.find(a=>String(a.id)===String(companyId||''))||accounts.find(a=>a.type==='Banka')||accounts[0]}
 if(target)sel.value=String(target.id);
 if(help)help.textContent=accounts.length+' gerçek '+cur+' kasa/banka hesabı bulundu.';
}
function selectedRequestBank(){const id=+(document.getElementById('newBank')?.value||0),a=(state.cashAccounts||[]).find(x=>Number(x.id)===id);return {id:a?Number(a.id):null,name:a?.name||''}}

let tempItems=[];
function escAttr(v){return String(v??'').replaceAll('&','&amp;').replaceAll('"','&quot;').replaceAll('<','&lt;').replaceAll('>','&gt;')}
function esc(v){return String(v??'').replaceAll('&','&amp;').replaceAll('<','&lt;').replaceAll('>','&gt;')}
function ensureRequestUnits(){
 const defaults=['Adet','Set','Takım','Kg','Gram','Ton','Metre','m²','m³','Litre','Koli','Paket','Palet','Rulo'];
 if(!Array.isArray(state.requestUnits)||!state.requestUnits.length)state.requestUnits=[...defaults];
 state.requestUnits=[...new Set(state.requestUnits.map(x=>String(x||'').trim()).filter(Boolean))];
 return state.requestUnits
}
function normalizeUnitKey(v){
 return String(v||'').trim().toLocaleLowerCase('tr-TR')
  .replace(/[çÇ]/g,'c').replace(/[ğĞ]/g,'g').replace(/[ıİ]/g,'i').replace(/[öÖ]/g,'o').replace(/[şŞ]/g,'s').replace(/[üÜ]/g,'u')
  .replace(/\s+/g,' ')
}
function dbRequestUnit(v){
 const raw=String(v||'').trim(),units=ensureRequestUnits();
 if(!raw)return units.includes('Adet')?'Adet':units[0];
 const key=normalizeUnitKey(raw),found=units.find(x=>normalizeUnitKey(x)===key);
 return found||raw
}
function requestUnitOptions(current=''){
 const units=[...ensureRequestUnits()];
 if(current&&!units.some(x=>normalizeUnitKey(x)===normalizeUnitKey(current)))units.push(current);
 return units.map(x=>`<option value="${escAttr(x)}" ${normalizeUnitKey(x)===normalizeUnitKey(current)?'selected':''}>${x}</option>`).join('')
}
function renderRequestItemEditor(){
 const box=document.getElementById('requestItemEditor'),count=document.getElementById('requestItemCount');if(!box)return;
 if(count)count.textContent=tempItems.length+' kalem';
 box.innerHTML=tempItems.length?tempItems.map((it,i)=>`<div class="edit-row request-item-row" data-request-item-index="${i}">
  <div class="request-item-sn">${i+1}</div>
  <input value="${escAttr(it.name)}" placeholder="Talep edilen ürün / hizmet" oninput="tempItems[${i}].name=this.value">
  <input type="text" inputmode="decimal" value="${formatQuantity(it.qty||1)}" placeholder="Miktar" oninput="tempItems[${i}].qty=parseMoneyInput(this.value)">
  <select onchange="tempItems[${i}].unit=this.value">${requestUnitOptions(it.unit||'Adet')}</select>
  <input placeholder="Marka / Model" value="${escAttr(it.brandModel||'')}" oninput="tempItems[${i}].brandModel=this.value">
  <input placeholder="Teknik Özellik" value="${escAttr(it.spec||'')}" oninput="tempItems[${i}].spec=this.value">
  <button type="button" class="btn red sm" onclick="removeRequestItem(${i})">Sil</button>
 </div>`).join(''):'<div class="empty">Talep kalemi yok. + Kalem Ekle veya Excel’den Kalem Yükle ile başlayabilirsiniz.</div>'
}
function addRequestItem(){
 tempItems.push({name:'',qty:1,unit:ensureRequestUnits().includes('Adet')?'Adet':ensureRequestUnits()[0],brandModel:'',spec:''});
 renderRequestItemEditor()
}
async function removeRequestItem(i){if(!await appConfirm('Bu talep kalemi silinsin mi?','Talep Kalemi'))return;tempItems.splice(i,1);renderRequestItemEditor()}
function triggerRequestItemsExcelUpload(){
 const f=document.getElementById('requestItemsExcelFile');if(f){f.value='';f.click()}
}
function requestExcelHeaderKey(v){
 return String(v||'').trim().toLocaleLowerCase('tr-TR')
  .replace(/[çÇ]/g,'c').replace(/[ğĞ]/g,'g').replace(/[ıİ]/g,'i').replace(/[öÖ]/g,'o').replace(/[şŞ]/g,'s').replace(/[üÜ]/g,'u')
  .replace(/[^a-z0-9]+/g,' ').trim()
}
async function importRequestItemsExcel(input){
 const file=input?.files?.[0];if(!file)return;
 const fd=new FormData();fd.append('file',file);
 try{
  toast('Excel kalemleri okunuyor…','good');
  const res=await fetch('api/import_preview.php',{method:'POST',body:fd}),j=await res.json();
  if(!res.ok||!j.ok)throw new Error(j.error||'Excel dosyası okunamadı');
  const headers=(j.headers||[]).map(requestExcelHeaderKey);
  const find=(names)=>headers.findIndex(h=>names.some(n=>h===n||h.includes(n)));
  const ixName=find(['talep edilen urunler','talep edilen urun','urun hizmet adi','urun']);
  const ixQty=find(['miktar','qty','quantity']);
  const ixUnit=find(['birim','unit']);
  const ixBrand=find(['marka model','marka','model']);
  const ixSpec=find(['teknik ozellik','teknik aciklama','ozellik','spec']);
  if(ixName<0||ixQty<0)throw new Error('Excel başlıkları tanınamadı. Şablonda “Talep Edilen Ürünler” ve “Miktar” kolonları zorunludur.');
  const imported=[],unknownUnits=new Set();
  for(const row of (j.rows||[])){
   const name=String(row[ixName]??'').trim();if(!name)continue;
   const qty=parseMoneyInput(row[ixQty]??0);if(!(qty>0))continue;
   const rawUnit=ixUnit>=0?String(row[ixUnit]??'').trim():'Adet';
   const mappedUnit=dbRequestUnit(rawUnit||'Adet');
   if(rawUnit&&!ensureRequestUnits().some(x=>normalizeUnitKey(x)===normalizeUnitKey(rawUnit)))unknownUnits.add(rawUnit);
   imported.push({name,qty,unit:mappedUnit,brandModel:ixBrand>=0?String(row[ixBrand]??'').trim():'',spec:ixSpec>=0?String(row[ixSpec]??'').trim():''})
  }
  if(!imported.length)throw new Error('Excel içinde geçerli talep kalemi bulunamadı.');
  tempItems.push(...imported);
  renderRequestItemEditor();
  const box=document.getElementById('requestItemEditor');if(box)box.scrollTop=box.scrollHeight;
  toast(imported.length+' Excel kalemi Talep Edilen Ürünler listesine eklendi.','good');
  if(unknownUnits.size)toast('DB birim listesinde olmayan değerler bulundu: '+[...unknownUnits].join(', ')+'. Listeden kontrol edip düzeltebilirsiniz.','warn')
 }catch(e){toast('Excel kalemleri yüklenemedi: '+(e?.message||e),'warn')}
 finally{if(input)input.value=''}
}
function editRequestItems(rid){openRequestModal(rid)}
function requestNumberSettings(){state.requestNumber=state.requestNumber||{prefix:'RFQ',format:'prefix-year-seq',next:1,pattern:'{PREFIX}-{YEAR}-{SEQ4}'};return state.requestNumber}
function generateRequestNo(){const c=requestNumberSettings(),year=new Date().getFullYear(),seq=Math.max(1,Number(c.next||1)),seq4=String(seq).padStart(4,'0'),prefix=(c.prefix||'RFQ').trim()||'RFQ';let no;if(c.format==='prefix-seq')no=`${prefix}-${seq4}`;else if(c.format==='custom')no=(c.pattern||'{PREFIX}-{YEAR}-{SEQ4}').replaceAll('{PREFIX}',prefix).replaceAll('{YEAR}',String(year)).replaceAll('{SEQ4}',seq4).replaceAll('{SEQ}',String(seq));else no=`${prefix}-${year}-${seq4}`;if(state.requests.some(r=>r.no===no)){c.next=seq+1;return generateRequestNo()}c.next=seq+1;return no}
let pendingRequestContinue=null,requestSaveInProgress=false;
function clearRequestListFiltersForContinue(){
 const search=document.getElementById('requestSearch'),completion=document.getElementById('requestCompletionFilter'),tagCat=document.getElementById('requestTagCategoryFilter'),tag=document.getElementById('requestTagFilter');
 if(search)search.value='';if(completion)completion.value='active';if(tagCat)tagCat.value='';if(tag)tag.value='';
}
function consumePendingRequestContinue(){
 const target=pendingRequestContinue;if(!target)return false;
 const id=Number(target.rid||0),r=state.requests.find(x=>Number(x.id)===id);
 if(!id||!r){pendingRequestContinue=null;return false}
 const card=document.querySelector(`.request-card[data-rid="${id}"]`);
 if(!card)return false;
 pendingRequestContinue=null;
 card.classList.add('open');
 const tab=target.tab||((r.items||[]).length?'suppliers':'items');
 const names={items:'Talep Kalemleri',suppliers:'Tedarikçi Teklifleri',comparison:'Kıyaslama',cost:'Maliyet',guarantee:'Teminat',customer:'Müşteri Teklifi',order:'Sipariş',docs:'Evraklar'};
 const btn=[...card.querySelectorAll('.tab')].find(x=>x.textContent.includes(names[tab]||tab));
 if(btn)reqTab(id,tab,btn);
 requestAnimationFrame(()=>card.scrollIntoView({behavior:'smooth',block:'center'}));
 return true
}
function continueRequestAfterSave(rid){
 const id=Number(rid||0);if(!id)return false;
 const r=state.requests.find(x=>Number(x.id)===id);if(!r)return false;
 clearRequestListFiltersForContinue();
 pendingRequestContinue={rid:id,tab:(r.items||[]).length?'suppliers':'items'};
 go('requests');
 renderRequests();
 if(!consumePendingRequestContinue()){
   requestAnimationFrame(()=>{if(!consumePendingRequestContinue())toast('Talep kaydedildi ancak devam görünümü açılamadı. Talep Merkezi’nden kaydı açabilirsiniz.','warn')})
 }
 return true
}

async function saveRequest(cont){
 if(requestSaveInProgress)return;
 requestSaveInProgress=true;
 const saveBtn=document.getElementById('requestSaveBtn'),continueBtn=document.getElementById('requestSaveContinueBtn');
 if(saveBtn)saveBtn.disabled=true;if(continueBtn)continueBtn.disabled=true;
 try{
 const selectedBank=selectedRequestBank(),bank=selectedBank.name,bankAccountId=selectedBank.id;
 const currency=document.getElementById('newCurrency').value;
 const customerPartyKey=document.getElementById('newCustomerSelect').value;const customerGroup=groupedAccounts().find(g=>String(g.partyKey)===String(customerPartyKey));const customer=customerGroup?.name||'';const customerAccForParty=customerGroup?Object.values(customerGroup.accounts)[0]:null;
 let paymentData;try{paymentData=currentRequestPaymentData()}catch(e){return toast(e.message,'warn')}
 const priority=document.getElementById('newPriority').value||'normal';
 const subject=(document.getElementById('newSubject')?.value||'').trim();
 if(!subject)return toast('Konu Başlığı zorunludur. Talep/ihalenin konusunu yazın.','warn');
 if(editingRequestId){
  const r=state.requests.find(x=>x.id===editingRequestId),requestBeforeEdit=JSON.parse(JSON.stringify(state.requests.find(x=>x.id===editingRequestId)));
  Object.assign(r,{customer,customerPartyKey,currency,payment:paymentData.label,paymentType:paymentData.key,paymentPlan:paymentData.plan,priority,bank,bankAccountId,delivery:document.getElementById('newDelivery').value,deliveryPlace:document.getElementById('newDeliveryPlace').value.trim(),deadline:document.getElementById('newDeadline').value||r.deadline,description:document.getElementById('newDescription').value.trim(),title:subject,items:tempItems.map((x,i)=>({id:Number(x.id)||0,uid:x.uid||'',name:x.name,qty:x.qty,unit:x.unit,brandModel:x.brandModel||'',spec:x.spec||''}))});ensureUniqueRequestItemIds(r);syncPriorityTags(r,priority);
  const files=[...document.getElementById('requestAttachmentFiles').files],cat=document.getElementById('requestAttachmentCategory').value,ver=+document.getElementById('requestAttachmentVersion').value||1,docDesc=document.getElementById('requestAttachmentDescription').value.trim();
  const savedRequestId=r.id,savedRequestNo=r.no;
  const ok=await runServerOperation({action:'request_update',requestId:r.id,title:r.title,customer:r.customer,customerPartyKey:r.customerPartyKey,currency:r.currency,payment:r.payment,paymentType:r.paymentType,paymentPlan:r.paymentPlan,priority:r.priority,bank:r.bank,bankAccountId:r.bankAccountId||null,delivery:r.delivery,deliveryPlace:r.deliveryPlace,deadline:r.deadline,description:r.description,items:r.items,idempotencyKey:makeIdempotencyKey()});
  if(!ok){const pos=state.requests.findIndex(x=>Number(x.id)===Number(savedRequestId));if(pos>=0&&requestBeforeEdit)state.requests[pos]=requestBeforeEdit;toast('Talep güncellenemedi. Pencere açık bırakıldı.','warn');return}
  closeModal('requestModal');renderRequests();updateDashboard();
  if(files.length){const uploaded=await uploadFilesForRequest(savedRequestId,files,docDesc||'Talep ekranından eklendi',cat,ver);if(!uploaded)toast('Talep güncellendi ancak dosya yüklenemedi. Talep kaydı korunuyor.','warn')}
  toast(savedRequestNo+' güncellendi.','good');editingRequestId=null;
  if(cont)continueRequestAfterSave(savedRequestId);
  return;
 }
 const requestNumberSnapshot=JSON.parse(JSON.stringify(state.requestNumber||{}));
 const no=generateRequestNo();
 const customerAcc=state.accounts.find(a=>a.name===customer),newRid=nextNumericId(state.requests);let iid=nextItemId();const newRequest={id:newRid,no,title:subject,customer,customerPartyKey,country:customerAcc?.country||document.getElementById('newDeliveryPlace').value.trim()||'Türkiye',delivery:document.getElementById('newDelivery').value,deliveryPlace:document.getElementById('newDeliveryPlace').value.trim(),deadline:document.getElementById('newDeadline').value||todayISO(),deadlineClass:'green',days:'',status:'collecting',payment:paymentData.label,paymentType:paymentData.key,paymentPlan:paymentData.plan,priority,bank,bankAccountId,currency,description:document.getElementById('newDescription').value.trim(),tags:[['Yeni','#246bfd']],items:tempItems.map(x=>{const id=iid++;return {...x,id,uid:`req${newRid}-item-${id}`,spec:x.spec||''}})};syncPriorityTags(newRequest,priority);state.requests.unshift(newRequest);
 const queuedFiles=[...document.getElementById('requestAttachmentFiles').files],cat=document.getElementById('requestAttachmentCategory').value,ver=+document.getElementById('requestAttachmentVersion').value||1,queuedDocDesc=document.getElementById('requestAttachmentDescription').value.trim();
 let createdAccountId=null;
 if(customer&&!state.accounts.some(a=>a.partyKey===customerPartyKey&&a.currency===currency)){const id=Math.max(0,...state.accounts.map(a=>a.id||0))+1;createdAccountId=id;state.accounts.push({id,partyKey:customerPartyKey||('pty-'+id),type:document.getElementById('newType').value.includes('Resmî')?'public':'customer',name:customer,currency,total:0,request:no,requestOpen:0,entries:[]});persistAccounts()}
 const saved=await saveDbState(true);
 if(!saved){
  state.requests=state.requests.filter(x=>x.id!==newRid);
  if(createdAccountId!==null)state.accounts=state.accounts.filter(a=>a.id!==createdAccountId);
  state.requestNumber=requestNumberSnapshot;
  renderRequests();updateDashboard();
  toast('Talep veritabanına kaydedilemedi: '+(window.lastDbSaveError||'Bilinmeyen DB hatası')+'. Pencere açık bırakıldı ve kalemler korunuyor.','warn');
  return
 }
 closeModal('requestModal');renderRequests();updateDashboard();toast(no+' veritabanına kaydedildi. Müşteri cari kartı hazırlandı.');
 if(queuedFiles.length){const uploaded=await uploadFilesForRequest(newRid,queuedFiles,queuedDocDesc||'Talep oluşturulurken eklendi',cat,ver);if(!uploaded)toast('Talep kaydı korunuyor; yalnız dosya yüklemesi başarısız oldu. Dokümanlar bölümünden tekrar ekleyebilirsiniz.','warn')}
 if(cont)continueRequestAfterSave(newRid)
 }finally{
  requestSaveInProgress=false;
  if(saveBtn)saveBtn.disabled=false;if(continueBtn)continueBtn.disabled=false;
 }
}

let tagTarget={type:'request',id:null,name:''},tagWorking=[];
function normalizeTagCatalog(){state.tagCatalog=(state.tagCatalog||[]).map(t=>Array.isArray(t)?{name:t[0],color:t[1]||'#667085',category:t[2]||'Genel'}:{name:t.name,color:t.color||'#667085',category:t.category||'Genel'});return state.tagCatalog}
function tagByName(name){return normalizeTagCatalog().find(t=>t.name===name)}
function entityTagNames(tags){return (tags||[]).map(t=>Array.isArray(t)?t[0]:(typeof t==='string'?t:t.name)).filter(Boolean)}
function entityTagObjects(tags){return entityTagNames(tags).map(n=>tagByName(n)||{name:n,color:'#667085',category:'Genel'})}
function openTagManager(id){openTagManagerFor('request',id)}
function openAccountTagManager(ref){openTagManagerFor('account',null,ref)}
function openTagManagerFor(type,id=null,name=''){normalizeTagCatalog();tagTarget={type,id,name};let current=[];if(type==='request'){const r=state.requests.find(x=>x.id===id);current=entityTagNames(r?.tags)}else{const g=groupedAccounts().find(x=>String(x.partyKey)===String(name))||groupedAccounts().find(x=>x.name===name);current=entityTagNames(g?.profile?.tags)}tagWorking=[...new Set(current)];document.getElementById('tagModalTitle').textContent=type==='request'?'Talep Etiketleri':'Cari Etiketleri';document.getElementById('tagModalSub').textContent=type==='request'?'Talebi merkezi etiket kataloğu ile sınıflandırın.':'Cari kartını merkezi etiket kataloğu ile sınıflandırın.';renderTagManager();openModal('tagModal')}
function renderTagManager(){
 const groups={};normalizeTagCatalog().forEach(t=>(groups[t.category]??=[]).push(t));const box=document.getElementById('tagManagerList');box.replaceChildren();
 Object.entries(groups).sort((a,b)=>a[0].localeCompare(b[0],'tr')).forEach(([category,tags])=>{
  const group=document.createElement('div');group.className='tag-group';const head=document.createElement('div');head.className='tag-group-head';const title=document.createElement('b');title.textContent=category;head.append(title);group.append(head);const list=document.createElement('div');list.className='tag-manager-list';
  tags.forEach(t=>{const button=document.createElement('button');button.type='button';button.className='tag-option'+(tagWorking.includes(t.name)?' selected':'');button.setAttribute('aria-pressed',String(tagWorking.includes(t.name)));const dot=document.createElement('span');dot.className='tag-dot';dot.style.background=t.color;button.append(dot,document.createTextNode(t.name));const cat=document.createElement('small');cat.textContent=t.category;button.append(cat);button.onclick=()=>toggleTag(t.name);list.append(button)});group.append(list);box.append(group);
 });if(!box.childElementCount){const empty=document.createElement('div');empty.className='empty';empty.textContent='Henüz etiket yok.';box.append(empty)}
 for(const [id,values] of [['existingTagNames',normalizeTagCatalog().map(t=>t.name)],['existingTagCategories',[...new Set(normalizeTagCatalog().map(t=>t.category))]]]){const dl=document.getElementById(id);if(!dl)continue;dl.replaceChildren();values.forEach(value=>{const option=document.createElement('option');option.value=value;dl.append(option)})}
}
function toggleTag(name){tagWorking=tagWorking.includes(name)?tagWorking.filter(x=>x!==name):[...tagWorking,name];renderTagManager()}
async function createTag(){const name=document.getElementById('newTagName').value.trim(),category=document.getElementById('newTagCategory').value.trim()||'Genel',color=document.getElementById('newTagColor').value;if(!name)return toast('Etiket adı gerekli.','warn');const catalog=normalizeTagCatalog(),existing=catalog.find(t=>t.name.toLocaleLowerCase('tr-TR')===name.toLocaleLowerCase('tr-TR'));const finalName=existing?existing.name:name;if(!existing)catalog.push({name,color,category});if(!tagWorking.includes(finalName))tagWorking.push(finalName);document.getElementById('newTagName').value='';renderTagManager();refreshTagFilters();if(!await saveDbState(true))return toast('Etiket kaydedilemedi: '+(window.lastDbSaveError||'DB hatası'),'warn');toast(existing?'Mevcut etiket seçildi.':'Etiket oluşturuldu ve aktif seçildi.','good')}
async function saveTags(){if(tagTarget.type==='request'){const r=state.requests.find(x=>x.id===tagTarget.id);if(r)r.tags=tagWorking.map(n=>{const t=tagByName(n);return [n,t?.color||'#667085',t?.category||'Genel']})}else{const g=groupedAccounts().find(x=>String(x.partyKey)===String(tagTarget.name))||groupedAccounts().find(x=>x.name===tagTarget.name);if(!g)return toast('Etiket eklenecek cari bulunamadı.','warn');state.accounts.filter(a=>String(a.partyKey)===String(g.partyKey)).forEach(a=>a.tags=[...tagWorking])}refreshTagFilters();renderRequests();renderAccounts();persistAccounts();if(!await saveDbState(true))return toast('Etiketler kaydedilemedi: '+(window.lastDbSaveError||'DB hatası'),'warn');closeModal('tagModal');toast('Etiketler güncellendi.','good')}
function refreshTagFilters(){normalizeTagCatalog();const cats=[...new Set(state.tagCatalog.map(t=>t.category))].sort((a,b)=>a.localeCompare(b,'tr'));[['requestTagCategoryFilter','requestTagFilter'],['accountTagCategoryFilter','accountTagFilter']].forEach(([cid,tid])=>{const c=document.getElementById(cid);if(c){const old=c.value;c.innerHTML='<option value="">Tüm Etiket Kategorileri</option>'+cats.map(x=>`<option value="${x}">${x}</option>`).join('');c.value=cats.includes(old)?old:''}syncTagFilterOptions(cid.startsWith('request')?'request':'account')})}
function syncTagFilterOptions(scope){const cid=scope==='request'?'requestTagCategoryFilter':'accountTagCategoryFilter',tid=scope==='request'?'requestTagFilter':'accountTagFilter',c=document.getElementById(cid),t=document.getElementById(tid);if(!t)return;const cat=c?.value||'',old=t.value,list=normalizeTagCatalog().filter(x=>!cat||x.category===cat);t.innerHTML='<option value="">Tüm Etiketler</option>'+list.map(x=>`<option value="${x.name}">${x.name}</option>`).join('');t.value=list.some(x=>x.name===old)?old:''}
function clearAccountTagFilter(){const c=document.getElementById('accountTagCategoryFilter'),t=document.getElementById('accountTagFilter');if(c)c.value='';if(t)t.value='';syncTagFilterOptions('account');renderAccounts()}
async function deleteRequest(id){
 const r=state.requests.find(x=>Number(x.id)===Number(id));if(!r)return;
 const orders=(state.orders||[]).filter(o=>Number(o.requestId)===Number(id)),orderIds=orders.map(o=>Number(o.id));
 const cashCount=(state.cash||[]).filter(c=>String(c.request||'')===String(r.no||'')||orderIds.includes(Number(c.orderId||0))).length;
 const accountEntryCount=(state.accounts||[]).reduce((n,a)=>n+(a.entries||[]).filter(e=>String(e.ref||'')===String(r.no||'')).length,0);
 const supplierQuoteCount=(state.suppliers||[]).filter(x=>Number(x.requestId)===Number(id)).length;
 const docCount=(state.documents||[]).filter(x=>Number(x.requestId)===Number(id)).length+(state.requestAttachments||[]).filter(x=>Number(x.requestId)===Number(id)).length;
 const msg=`${r.no} tamamen silinecek.\n\nBağlı kayıtlar da geri alınacak / kaldırılacak:\n• ${orders.length} sipariş\n• ${supplierQuoteCount} tedarikçi teklifi\n• ${accountEntryCount} cari hareketi\n• ${cashCount} kasa/banka hareketi\n• ${docCount} evrak/doküman\n\nCari kartlar ile Kasa/Banka hesapları korunacak; yalnız bu talebin bakiye ve hareket etkileri temizlenecek. Bu işlem geri alınamaz.`;
 if(!await appConfirm(msg,'Talebi Tamamen Sil'))return;
 if(await runServerOperation({action:'request_delete',requestId:id,idempotencyKey:makeIdempotencyKey()}))toast(r.no+' ve bağlı tüm operasyon/finans kayıtları kaldırıldı. Cari ve Kasa/Banka bakiyeleri yeniden hesaplandı.','good')
}

function requestDocCompletion(rid){const types=new Set(requestDocuments(rid).map(d=>d.type)),required=['offer','proforma','packing','invoice'];return {count:required.filter(t=>types.has(t)).length,complete:required.every(t=>types.has(t))}}
function requestDelivered(r){const o=requestOrder(r),pos=Array.isArray(o?.pos)?o.pos:[];return !!(o&&pos.length&&pos.every(po=>po?.status==='Teslim Edildi'))}
function captureRequestUi(){const opened=[...document.querySelectorAll('.request-card.open')].map(x=>+x.dataset.rid);const tabs={};opened.forEach(id=>{const p=document.querySelector(`.request-card[data-rid="${id}"] .tabpane.active`);if(p)tabs[id]=(p.dataset.pane||'').split('-').pop()});return {opened,tabs,scrollY:window.scrollY}}
function restoreRequestUi(ui){if(!ui)return;ui.opened.forEach(id=>{const c=document.querySelector(`.request-card[data-rid="${id}"]`);if(c)c.classList.add('open');const name=ui.tabs[id];if(name){const b=[...c?.querySelectorAll('.tab')||[]].find(x=>x.textContent.toLowerCase().includes(name==='docs'?'evrak':name==='order'?'sipariş':name));if(b)reqTab(id,name,b)}});requestAnimationFrame(()=>window.scrollTo({top:ui.scrollY,behavior:'auto'}))}
function dashboardDueInfo(dateStr){
 if(!dateStr)return null;const d=new Date(String(dateStr).slice(0,10)+'T12:00:00');if(isNaN(d))return null;
 const n=new Date();n.setHours(0,0,0,0);d.setHours(0,0,0,0);const days=Math.round((d-n)/86400000);
 return {days,cls:days<0?'red':days<=3?'red':days<=7?'yellow':'green',text:days<0?`${Math.abs(days)} gün gecikti`:days===0?'Bugün':days===1?'Yarın':`${days} gün sonra`}
}
function actionDayBadge(info){
 if(!info)return null;
 if(info.days<0)return {text:`${Math.abs(info.days)} GÜN GECİKTİ`,kind:'overdue'};
 if(info.days===0)return {text:'BUGÜN',kind:'today'};
 return {text:`${info.days} GÜN`,kind:'future'}
}
function dashboardOpenQuoteVolume(){
 const v={EUR:0,USD:0,TRY:0};
 state.requests.filter(r=>r.customerQuote&&!['won','lost','cancelled'].includes(r.status)).forEach(r=>{const c=r.currency||r.customerQuote.currency||'EUR';v[c]=(v[c]||0)+Number(r.customerQuote.total||0)});
 return v
}
let dashboardActionsCache=[];
function dashboardActionOverride(key){state.actionOverrides=state.actionOverrides||{};return state.actionOverrides[key]||{}}
function dashboardActionVisible(a){
 const o=dashboardActionOverride(a.key);
 if(o.snoozeUntil&&String(o.snoozeUntil)>todayISO())return false;
 return true
}
function saveDashboardActionOverride(key,patch){
 state.actionOverrides=state.actionOverrides||{};
 const clean={...(state.actionOverrides[key]||{}),...patch};
 delete clean.completed;delete clean.completedAt;
 state.actionOverrides[key]=clean;
 queueDbSave('Aksiyon ayarı');updateDashboard()
}
function snoozeDashboardAction(key){
 const d=new Date();d.setDate(d.getDate()+1);
 saveDashboardActionOverride(key,{snoozeUntil:d.toISOString().slice(0,10)});
 toast('Aksiyon 1 gün ertelendi. Gerçek işlem tamamlanmadıkça kapanmaz.','good');
 renderAllActions()
}
function clearDashboardActionSnooze(key){
 const o=dashboardActionOverride(key);delete o.snoozeUntil;
 state.actionOverrides[key]=o;queueDbSave('Aksiyon ayarı');updateDashboard();renderAllActions()
}
async function assignDashboardAction(key){
 const who=await appPrompt('Sorumlu kişi / ekip',dashboardActionOverride(key).assignee||'','Aksiyon Sorumlusu');
 if(who===null)return;
 saveDashboardActionOverride(key,{assignee:who.trim()});renderAllActions()
}
function actionHistoryLabel(a){return a.closedReason||'Gerçek veri durumuna göre otomatik kapandı'}
function syncAutomaticActionHistory(allActions){
 state.actionHistory=Array.isArray(state.actionHistory)?state.actionHistory:[];
 state.actionOpenSnapshot=state.actionOpenSnapshot||{};
 const nowKeys=new Set(allActions.map(a=>a.key));
 const previous=state.actionOpenSnapshot;
 Object.entries(previous).forEach(([key,prev])=>{
   if(!nowKeys.has(key)){
     const already=state.actionHistory.some(h=>h.key===key&&h.sourceOpenedAt===prev.openedAt&&h.closedAt);
     if(!already)state.actionHistory.unshift({
       key,title:prev.title||key,sub:prev.sub||'',type:prev.type||'',view:prev.view||'',
       sourceOpenedAt:prev.openedAt||null,closedAt:new Date().toISOString(),
       closedReason:'Gerçek kayıt durumu değiştiği için otomatik kapandı'
     })
   }
 });
 const next={};
 allActions.forEach(a=>{
   const old=previous[a.key];
   next[a.key]={title:a.title,sub:a.sub,type:a.type||'',view:a.view,openedAt:old?.openedAt||new Date().toISOString()}
 });
 state.actionOpenSnapshot=next;
 if(state.actionHistory.length>250)state.actionHistory=state.actionHistory.slice(0,250)
}
function renderAllActions(){
 const openBox=document.getElementById('allActionsOpenList'),historyBox=document.getElementById('allActionsHistoryList');
 const rows=dashboardActionsCache||[];
 if(openBox)openBox.innerHTML=rows.length?rows.map(a=>{
   const o=dashboardActionOverride(a.key);
   return `<div class="action-center-row" style="border-left-color:${a.color||'var(--accent)'}">
    <div><b>${a.title}</b>${a.dayBadge?` <span class="action-day-badge ${a.dayBadge.kind}" style="background:${a.color||'#64748b'};color:#fff">${a.dayBadge.text}</span>`:''}
    <div class="sub">${a.sub}${o.assignee?' · Sorumlu: '+o.assignee:''}${o.snoozeUntil?' · '+o.snoozeUntil+' tarihine kadar ertelendi':''}</div></div>
    <div class="action-center-actions">
      <button class="btn sm" onclick="go('${a.view}');closeModal('allActionsModal')">${a.btn}</button>
      <button class="btn sm" onclick="assignDashboardAction('${a.key}')">Sorumlu Ata</button>
      ${o.snoozeUntil?`<button class="btn sm" onclick="clearDashboardActionSnooze('${a.key}')">Ertelemeyi Kaldır</button>`:`<button class="btn sm" onclick="snoozeDashboardAction('${a.key}')">+1 Gün Ertele</button>`}
    </div>
   </div>`
 }).join(''):'<div class="empty">Açık aksiyon bulunmuyor.</div>';
 const hist=(state.actionHistory||[]).slice(0,50);
 if(historyBox)historyBox.innerHTML=hist.length?hist.map(h=>`<div class="action-history-row"><div><b>${h.title}</b><div class="sub">${h.sub||''}</div><small>${actionHistoryLabel(h)} · ${new Date(h.closedAt).toLocaleString('tr-TR')}</small></div></div>`).join(''):'<div class="empty">Henüz otomatik kapanan aksiyon bulunmuyor.</div>'
}
function openAllActions(){renderAllActions();openModal('allActionsModal')}
function dashboardOrderRequest(o){
 return state.requests.find(r=>Number(r.id)===Number(o?.requestId))||null
}
function isValidOperationalOrder(o){
 if(!o||o.open===false||o.financialCancelled===true)return false;
 const r=dashboardOrderRequest(o);
 if(!r)return false; // orphan / deleted request
 if(['lost','cancelled'].includes(r.status))return false;
 const won=r.status==='won';
 if(!won)return false; // only real won requests may drive operations
 if(!Array.isArray(o.pos)||!o.pos.length)return false;
 return true
}
function isActiveOperationalOrder(o){
 if(!isValidOperationalOrder(o))return false;
 return orderAggregateStatus(o).key!=='delivered'
}
function dashboardOperationalOrders(){
 return state.orders.filter(isActiveOperationalOrder)
}
function dashboardOperationDiagnostics(){
 const rows=(state.orders||[]).map(o=>{
  const r=dashboardOrderRequest(o),agg=orderAggregateStatus(o);
  return {orderId:o.id,no:o.no,requestId:o.requestId,request:r?.no||null,requestStatus:r?.status||null,quoteStatus:r?.customerQuote?.status||null,open:o.open,financialCancelled:o.financialCancelled||false,pos:(o.pos||[]).length,aggregate:agg.key,valid:isValidOperationalOrder(o),active:isActiveOperationalOrder(o)}
 });
 return {build:(window.ASAY_APP_BUILD||'V3.11.1'),ordersInState:state.orders?.length||0,activeOrders:rows.filter(x=>x.active).length,rows}
}
function updateDashboard(){
 const set=(id,v)=>{const e=document.getElementById(id);if(e)e.textContent=v};
 const openQuotes=state.requests.filter(r=>r.customerQuote&&!['won','lost','cancelled'].includes(r.status));
 const wonRequests=state.requests.filter(r=>r.status==='won');
 const activeOrders=dashboardOperationalOrders();
 const pos=activeOrders.flatMap(o=>(o.pos||[]).map(p=>({p,o})));
 const activePos=pos.filter(({p,o})=>p.status!=='Teslim Edildi'&&orderAggregateStatus(o).key!=='delivered');
 const readyPos=activePos.filter(x=>x.p.status==='Sevkiyata Hazır');
 const activeRequests=state.requests.filter(r=>!['cancelled','lost'].includes(r.status));
 const collectingRequests=activeRequests.filter(r=>r.status==='collecting').length;

 set('mRequests',activeRequests.length);
 set('mRequestsCollecting',collectingRequests+' tanesi fiyat topluyor');
 set('mOpenQuotes',openQuotes.length);
 const qv=dashboardOpenQuoteVolume(),volParts=[];
 if(qv.EUR)volParts.push(fmtMoneyCur(qv.EUR,'EUR'));
 if(qv.USD)volParts.push(fmtMoneyCur(qv.USD,'USD'));
 if(qv.TRY)volParts.push(fmtMoneyCur(qv.TRY,'TRY'));
 set('mOpenQuoteVolume',volParts.length?volParts.join(' · ')+' açık teklif hacmi':'Açık teklif hacmi yok');
 set('mWon',wonRequests.length);
 set('mWonSub',wonRequests.length?wonRequests.length+' iş siparişe dönüştü':'Siparişe dönüşen iş yok');
 set('mActiveOps',activeOrders.length);const activeMetric=document.getElementById('mActiveOps');if(activeMetric)activeMetric.title=`Build ${window.ASAY_APP_BUILD||'V3.11.1'} · State sipariş: ${state.orders?.length||0} · Dashboard aktif: ${activeOrders.length}`;
 set('mActiveOpsSub',activeOrders.length
   ?(readyPos.length?`${activeOrders.length} aktif sipariş · ${readyPos.length} sevkiyata hazır`:`${activeOrders.length} aktif sipariş · sevkiyata hazır PO yok`)
   :'Aktif üretim / teslimat siparişi yok');

 const totals={EUR:{sales:0,purchase:0},USD:{sales:0,purchase:0}};
 state.orders.filter(isValidOperationalOrder).forEach(o=>{const r=dashboardOrderRequest(o),c=r?.currency||o.currency||'EUR';if(!totals[c])return;const q=r?.customerQuote;totals[c].sales+=Number(q?.total||o.total||0);totals[c].purchase+=Number(q?.cost||(o.pos||[]).reduce((n,p)=>n+Number(p.total||0),0))});
 set('dashSalesEur',fmtMoneyCur(totals.EUR.sales,'EUR'));
 set('dashPurchaseEur',fmtMoneyCur(totals.EUR.purchase,'EUR'));
 set('dashSalesUsd',fmtMoneyCur(totals.USD.sales,'USD'));
 set('dashPurchaseUsd',fmtMoneyCur(totals.USD.purchase,'USD'));

 const recEur=state.accounts.filter(a=>a.type!=='supplier'&&a.currency==='EUR').reduce((n,a)=>n+Math.max(0,Number(a.requestOpen||0)),0);
 const payEur=state.accounts.filter(a=>a.type==='supplier'&&a.currency==='EUR').reduce((n,a)=>n+Math.max(0,-Number(a.requestOpen||0)),0);
 const cashEur=state.cashAccounts.filter(a=>a.currency==='EUR').reduce((n,a)=>n+Number(a.balance||0),0);
 const cashUsd=state.cashAccounts.filter(a=>a.currency==='USD').reduce((n,a)=>n+Number(a.balance||0),0);
 const openEurPos=pos.filter(x=>(state.requests.find(r=>r.id===x.o.requestId)?.currency||'EUR')==='EUR'&&Number(x.p.due||0)>0);

 set('dashReceivable',fmtMoneyCur(recEur,'EUR'));set('dashPayable',fmtMoneyCur(payEur,'EUR'));
 set('dashCashEur',fmtMoneyCur(cashEur,'EUR'));set('dashCashUsd',fmtMoneyCur(cashUsd,'USD'));
 set('dashSupplierDueEur',fmtMoneyCur(openEurPos.reduce((n,x)=>n+Number(x.p.due||0),0),'EUR'));
 set('dashSupplierDueCount',openEurPos.length+' açık tedarikçi PO');
 set('dashCustomerDueEur',fmtMoneyCur(recEur,'EUR'));
 set('dashCustomerDueCount',state.accounts.filter(a=>a.type!=='supplier'&&a.currency==='EUR'&&Number(a.requestOpen||0)>0).length+' açık cari');

 // V3.11.1 Dashboard Teminat Takip
 const guaranteeAll=(state.guarantees||[]).filter(g=>g&&typeof g==='object');
 const guaranteeActive=guaranteeAll.filter(g=>String(g.status||'active')!=='released');
 const guaranteeTotalEur=guaranteeActive.reduce((n,g)=>n+(typeof guaranteeToEur==='function'?Number(guaranteeToEur(g)||0):Number(g.amount||0)),0);
 const guaranteeSoon=guaranteeActive.filter(g=>{const d=typeof guaranteeDaysLeft==='function'?guaranteeDaysLeft(g):null;return d!==null&&d>=0&&d<=30}).length;
 const guaranteeExpired=guaranteeActive.filter(g=>{const d=typeof guaranteeDaysLeft==='function'?guaranteeDaysLeft(g):null;return d!==null&&d<0}).length;
 set('dashGuaranteeActive',guaranteeActive.length);set('dashGuaranteeTotal',fmtMoneyCur(guaranteeTotalEur,'EUR'));set('dashGuaranteeSoon',guaranteeSoon);set('dashGuaranteeExpired',guaranteeExpired);
 const guaranteeDash=document.getElementById('dashboardGuaranteeList');
 if(guaranteeDash){
   const sorted=guaranteeActive.slice().sort((a,b)=>{const da=typeof guaranteeDaysLeft==='function'?guaranteeDaysLeft(a):99999,db=typeof guaranteeDaysLeft==='function'?guaranteeDaysLeft(b):99999;return (da??99999)-(db??99999)}).slice(0,6);
   guaranteeDash.innerHTML=sorted.length?sorted.map(g=>{const d=typeof guaranteeDaysLeft==='function'?guaranteeDaysLeft(g):null,st=typeof guaranteeDisplayStatus==='function'?guaranteeDisplayStatus(g):'active',label=typeof guaranteeStatusLabel==='function'?guaranteeStatusLabel(st):'Aktif',badge=typeof guaranteeStatusBadge==='function'?guaranteeStatusBadge(st):'b-blue',left=d===null?'Vade yok':d<0?Math.abs(d)+' gün geçti':d===0?'Bugün':d+' gün kaldı';const rr=(state.requests||[]).find(r=>Number(r.id)===Number(g.requestId));return `<button type="button" class="dashboard-guarantee-row" onclick="go('guarantees')"><span><b>${esc(rr?.title||g.requestNo||g.partyName||'Teminat')}</b><small>${esc(g.requestNo||rr?.no||'')} · ${esc(g.partyName||rr?.customer||'')} · ${esc(g.type||'Teminat')}</small></span><span class="dashboard-guarantee-amount">${fmtMoneyCur(Number(g.amount||0),g.currency||'EUR')}</span><span class="badge ${badge}">${label}</span><span class="dashboard-guarantee-days ${d!==null&&d<0?'is-overdue':''}">${left}</span></button>`}).join(''):'<div class="empty">Aktif teminat bulunmuyor.</div>';
 }

 const actions=[],actionCfg=normalizedActionSettings();
 if(actionCfg.docsPending?.enabled!==false)state.requests.filter(r=>(r.status==='won')&&!requestDocCompletion(r.id).complete).forEach(r=>{
   const dc=requestDocCompletion(r.id);
   const docsInfo=dashboardDueInfo(r.docsDueDate||'');const docsSev=docsInfo?actionSeverity(docsInfo,actionCfg.request):{level:1};actions.push({key:'docs:'+r.id,type:'docs_pending',sort:1-docsSev.level,cls:'yellow action-won-docs',color:'#22c55e',severity:'Kazanıldı · Evrak Eksik',dayBadge:actionDayBadge(docsInfo),title:r.no+' · '+r.title,sub:`Kazanıldı · Ticari evraklar ${dc.count}/4 tamamlandı${r.docsDueDate?' · Evrak hedefi '+r.docsDueDate:''}`,view:'docs',btn:'Evrakları Aç'})
 });
 state.requests.forEach(r=>{(r.costLines||[]).forEach((line,idx)=>{if(!line.dueDate)return;const remaining=costLineRemaining(r,line);if(!(remaining>0.005))return;const info=dashboardDueInfo(line.dueDate);if(!info||info.days>30)return;const overdue=info.days<0;actions.push({key:`opexp:${r.id}:${line.id||idx}`,type:'operational_expense_due',sort:overdue?0:3,cls:overdue?'red':'yellow',color:overdue?'#d92d20':'#d97706',severity:overdue?'Operasyon Gideri Gecikti':'Operasyon Gideri Vadesi',dayBadge:actionDayBadge(info),title:`${r.no} · ${line.category||'Operasyon Gideri'}`,sub:`${line.invoiceNo?'Fatura '+line.invoiceNo+' · ':''}Vade ${line.dueDate} · ${info.text} · Kalan ${fmtMoneyCur(remaining,line.currency||r.currency||'EUR')}`,view:'requests',btn:'Maliyeti Aç'})})});
 (state.guarantees||[]).filter(g=>(g.status||'active')!=='released'&&g.expiryDate).forEach(g=>{
   const info=dashboardDueInfo(g.expiryDate);if(!info||info.days>30)return;const overdue=info.days<0;actions.push({key:'guarantee:'+g.id,type:'guarantee_expiry',sort:overdue?0:2,cls:overdue?'red':'yellow',color:overdue?'#d92d20':'#d97706',severity:overdue?'Teminat Süresi Geçti':'Teminat Vadesi Yaklaşıyor',dayBadge:actionDayBadge(info),title:(g.requestNo?g.requestNo+' · ':'')+(g.partyName||'Teminat'),sub:`${g.type||'Teminat'} · ${fmtMoneyCur(Number(g.amount||0),g.currency||'EUR')} · Vade ${g.expiryDate} · ${info.text}`,view:'guarantees',btn:'Teminatı Aç'})
 });
 state.requests.filter(r=>!['sent','cancelled','lost','won'].includes(r.status)).forEach(r=>{
   const info=dashboardDueInfo(r.deadline);
   if(actionDateAllowed(info,actionCfg.request)){
     const sev=actionSeverity(info,actionCfg.request),cls=sev.level>=2?'red':'yellow';
     const pAdj=r.priority==='urgent'?-2:r.priority==='priority'?-1:0;actions.push({key:'request:'+r.id,type:'request_deadline',sort:5-sev.level+pAdj,cls,color:sev.color,severity:sev.label,dayBadge:actionDayBadge(info),title:r.no+' · '+r.title,sub:`${priorityLabel(r.priority||'normal')} · Son teklif / ihale tarihi: ${r.deadline} · ${info.text}`,view:'requests',btn:'Talebi Aç'})
   }
 });
 if(actionCfg.ready.enabled)readyPos.forEach(x=>{
   const cur=state.requests.find(r=>r.id===x.o.requestId)?.currency||'EUR';
   actions.push({key:'ready:'+x.p.id,type:'shipment_ready',sort:1,cls:'yellow',color:actionCfg.ready.color,severity:'Sevkiyata Hazır',title:x.p.no+' · '+x.p.supplier,sub:'Sevkiyata hazır'+(Number(x.p.due||0)>0?' · '+fmtMoneyCur(x.p.due,cur)+' kalan borç':''),view:'orders',btn:'Operasyonu Aç'})
 });
 state.accounts.forEach(a=>{
   const amount=Number(a.requestOpen||0),info=dashboardDueInfo(a.dueDate);if(!info||amount===0)return;
   const supplier=a.type==='supplier',cfg=supplier?actionCfg.supplier:actionCfg.customer,relevant=supplier?amount<0:amount>0;
   if(!relevant||!actionDateAllowed(info,cfg))return;
   const sev=actionSeverity(info,cfg),cls=sev.level>=2?'red':'yellow';
   actions.push({key:'account:'+a.id,type:supplier?'supplier_payment':'customer_receipt',sort:5-sev.level,cls,color:sev.color,severity:sev.label,dayBadge:actionDayBadge(info),title:(supplier?'Ödeme: ':'Tahsilat: ')+a.name,sub:`Vade ${a.dueDate} · ${info.text} · ${fmtMoneyCur(Math.abs(amount),a.currency)}`,view:'accounts',btn:'Cariyi Aç'})
 });
 state.actionOverrides=state.actionOverrides||{};
Object.values(state.actionOverrides).forEach(o=>{if(o&&typeof o==='object'){delete o.completed;delete o.completedAt}});
syncAutomaticActionHistory(actions);
const visibleActions=actions.filter(dashboardActionVisible);visibleActions.sort((a,b)=>a.sort-b.sort);dashboardActionsCache=visibleActions;
 set('dashboardActionCount',visibleActions.length+' aksiyon');
 const ab=document.getElementById('dashboardActions');
 if(ab)ab.innerHTML=visibleActions.length?visibleActions.slice(0,10).map(a=>`<div class="deadline action-critical ${a.cls}" style="${a.color?`border-left-color:${a.color};background:color-mix(in srgb,${a.color} 12%,var(--surface));box-shadow:0 0 0 1px color-mix(in srgb,${a.color} 22%,transparent),0 8px 20px color-mix(in srgb,${a.color} 10%,transparent)`:''}"><div><b>${a.title}${a.severity?` <span class="action-severity-badge" style="background:${a.color}22;color:${a.color};border:1px solid ${a.color}55">${a.severity}</span>`:''}${a.dayBadge?` <span class="action-day-badge ${a.dayBadge.kind}" style="background:${a.color};color:#fff;border:1px solid ${a.color}">${a.dayBadge.text}</span>`:''}</b><span>${a.sub}</span></div><button class="btn sm" onclick="go('${a.view}')">${a.btn}</button></div>`).join(''):'<div class="empty">Bugün veya önümüzdeki 7 gün için aksiyon bulunmuyor.</div>';

 const body=document.getElementById('dashboardOrderRows');
 if(body)body.innerHTML=activePos.map(({p:po,o})=>`<tr><td data-label="Sipariş"><b>${po.no}</b></td><td data-label="Tedarikçi">${po.supplier}</td><td data-label="Müşteri">${o.customer}</td><td data-label="Durum"><span class="badge ${po.status==='Sevkiyata Hazır'?'b-yellow':po.status==='Yolda'?'b-purple':'b-blue'}">${po.status}</span></td><td data-label="Üretim"><div class="progress"><i style="width:${po.production}%"></i></div></td><td data-label="Termin">${po.status}</td><td data-label="Tutar">${money(po.total,o.currency||'EUR')}</td></tr>`).join('')||'<tr><td colspan="7" class="empty">Aktif üretim / teslimat siparişi bulunmuyor.</td></tr>';
}
function refreshOperationalViews(){updateDashboard();renderOrders();renderRequests();renderSavedDocs();}
function openDocsForRequest(rid){go('docs');setTimeout(()=>{const e=document.getElementById('docRequestSelect');if(e){e.value=rid;syncDocQuoteSelect();renderDocument();renderSavedDocs();updateBackToRequestButton()}},40)}
function ensureUniqueRequestItemIds(r){
 if(!r||!Array.isArray(r.items))return false;
 const used=new Set(),usedUid=new Set();let changed=false,next=Math.max(0,...state.requests.flatMap(q=>(q.items||[]).map(i=>Number(i.id)||0)))+1;
 r.items=r.items.map((it,idx)=>{
   let id=Number(it?.id)||0;
   if(id<=0||used.has(id)){while(used.has(next))next++;id=next++;changed=true}
   used.add(id);
   let uid=String(it?.uid||'').trim();
   if(!uid||usedUid.has(uid)){uid=`req${Number(r.id)||0}-item-${id}`;changed=true}
   const base=uid;let n=2;while(usedUid.has(uid)){uid=`${base}-${n++}`;changed=true}
   usedUid.add(uid);
   if(uid!==it.uid)changed=true;
   return {...it,id,uid}
 });
 return changed
}
function ensureAllRequestItemIds(){let changed=false;(state.requests||[]).forEach(r=>{if(ensureUniqueRequestItemIds(r))changed=true});return changed}
function quoteItemKey(x){return String(x?.itemUid||x?.uid||`item-${Number(x?.itemId)||0}`)}

function selectionKey(rid,itemId){return `${rid}:${itemId}`}
function selectedSupplierId(rid,itemId){
 const k=selectionKey(rid,itemId);
 let raw=state.selected?.[k]??null;
 if(raw===null||raw===undefined||raw===''){
   // Legacy V3.3.x stored some selections only by itemId. Accept them only when
   // that supplier actually belongs to this request; this prevents cross-RFQ ID collisions.
   const legacy=state.selected?.[String(itemId)]??state.selected?.[itemId]??null;
   const sid=Number(legacy);
   if(Number.isFinite(sid)&&sid>0&&requestSuppliers(rid).some(s=>Number(s.id)===sid))raw=sid;
 }
 if(raw===null||raw===undefined||raw==='')return null;
 const n=Number(raw);return Number.isFinite(n)&&n>0?n:null
}
function selectedComparisonCost(r){
 const sups=requestSuppliers(r.id);
 return (r.items||[]).reduce((sum,it)=>{const sup=sups.find(s=>s.id===selectedSupplierId(r.id,it.id)),price=Number(sup?.offers?.[it.id]||0);return sum+price*Number(it.qty||0)},0)
}
function requestWonDocsState(r){
 const dc=requestDocCompletion(r.id),won=r.status==='won';
 return {won,complete:dc.complete,pending:won&&!dc.complete,count:dc.count}
}
function requestHeadlineStats(r){
 const incoming=requestSuppliers(r.id).length,comp=Number(r.customerQuote?.cost||selectedComparisonCost(r)||0),sale=Number(r.customerQuote?.total||0),profit=sale?Number(r.customerQuote?.profit??(sale-comp)):0,cur=r.currency||'EUR';
 return `<div class="request-head-metrics"><div class="request-head-metric"><span>Tedarikçi Teklifi</span><b>${incoming}</b></div><div class="request-head-metric"><span>Kıyaslanan</span><b>${comp?fmtMoneyCur(comp,cur):'—'}</b></div><div class="request-head-metric"><span>Müşteri Teklifi</span><b>${sale?fmtMoneyCur(sale,cur):'—'}</b></div><div class="request-head-metric"><span>Beklenen Kâr</span><b>${sale?fmtMoneyCur(profit,cur):'—'}</b></div></div>`
}
function requestStatusOptions(current){const labels={draft:'Taslak',collecting:'Fiyat Toplanıyor',ready:'Kıyaslamaya Hazır',quote:'Müşteri Teklifi Hazır',sent:'Teklif Gönderildi',won:'Kazanıldı',lost:'Kaybedildi',cancelled:'İptal Edildi'};return Object.keys(labels).map(v=>`<option value="${v}" ${current===v?'selected':''}>${labels[v]}</option>`).join('')}
let pendingStatusChange=null;
function changeRequestStatus(rid,status){const r=state.requests.find(x=>x.id===rid);if(!r)return;if(status===r.status)return;if(['lost','cancelled'].includes(status)){pendingStatusChange={rid,status,old:r.status};document.getElementById('statusReasonTitle').textContent=status==='lost'?'Kaybedildi Nedeni':'İptal Nedeni';document.getElementById('statusReasonText').value='';openModal('statusReasonModal');renderRequests();return}applyStatusChange(rid,status,'')}
async function confirmPendingStatusChange(){if(!pendingStatusChange)return;const preset=document.getElementById('statusReasonPreset').value,text=document.getElementById('statusReasonText').value.trim(),reason=(preset==='Diğer'?text:[preset,text].filter(Boolean).join(' · '));if(!reason)return toast('Durum nedeni zorunlu.','warn');const {rid,status}=pendingStatusChange;closeModal('statusReasonModal');pendingStatusChange=null;await applyStatusChange(rid,status,reason)}
async function applyStatusChange(rid,status,reason=''){if(await runServerOperation({action:'status_change',requestId:rid,status,reason})){const r=state.requests.find(x=>x.id===rid);toast(r?.no+' durumu '+statusLabel(status)+' olarak güncellendi.','good')}}
function requestOverviewHtml(r){const items=Array.isArray(r?.items)?r.items:[],q=r?.customerQuote,sups=requestSuppliers(r?.id),selected=items.filter(it=>selectedSupplierId(r.id,it.id)).length,cost=q?.cost||0,sale=q?.total||0,profit=q?.profit||0,margin=sale?profit/sale*100:0;return `<div class="request-commercial-summary"><div class="commercial-mini"><span>Gelen Teklif</span><b>${sups.length}</b></div><div class="commercial-mini"><span>Uygun / Seçili</span><b>${selected} / ${items.length}</b></div><div class="commercial-mini"><span>Alış Toplam</span><b>${q?money(cost,r.currency):'—'}</b></div><div class="commercial-mini"><span>Verilen Teklif</span><b>${q?money(sale,r.currency):'Bekliyor'}</b></div><div class="commercial-mini"><span>Kâr</span><b>${q?money(profit,r.currency):'—'}</b></div><div class="commercial-mini"><span>Marj</span><b>${q?'% '+margin.toFixed(1):'—'}</b></div></div>`;}
function isDeliveredRequest(r){const o=requestOrder(r);return !!(o&&o.pos?.length&&o.pos.every(po=>po.status==='Teslim Edildi'))}
function deliveredDocsPending(r){const docs=requestDocuments(r.id),types=new Set(docs.map(d=>d.type));return isDeliveredRequest(r)&&['offer','proforma','packing','invoice'].filter(t=>types.has(t)).length<4}
function updateRequestMetrics(){
 const rows=Array.isArray(state.requests)?state.requests:[],set=(id,v)=>{const e=document.getElementById(id);if(e)e.textContent=String(v)};
 const collecting=rows.filter(r=>r.status==='collecting').length;
 const ready=rows.filter(r=>r.status==='ready').length;
 const sent=rows.filter(r=>r.status==='sent').length;
 const won=rows.filter(r=>r.status==='won').length;
 set('reqMetricActive',rows.filter(r=>!['cancelled','lost'].includes(r.status)).length);set('reqMetricCollecting',collecting);set('reqMetricReady',ready);set('reqMetricSent',sent);set('reqMetricWon',won)
}
function renderRequestCardHtml(r){const dc=requestDocCompletion(r.id),di=requestDeadlineInfo(r);return `<article class="request-card deadline-${di.class} ${dc.complete?'docs-complete':''} ${deliveredDocsPending(r)?'delivered-docs-pending':''} ${(r.status==='won')&&!dc.complete?'won-docs-pending':''} ${(r.status==='won')&&dc.complete?'won-docs-complete':''} status-${r.status||'draft'}" data-rid="${r.id}">
  <div class="request-summary" onclick="toggleRequest(${r.id},event)">
   <div class="request-title"><b>${r.no} · ${r.title}<span class="priority-badge ${r.priority==='urgent'?'priority-urgent':r.priority==='priority'?'priority-priority':'priority-normal'}">${priorityLabel(r.priority||'normal')}</span></b><small>${r.customer} · ${r.country}</small><div class="tags">${entityTagObjects(r.tags).map(t=>`<span class="tag" style="background:${t.color}22;color:${t.color};border-color:${t.color}55">${t.name}</span>`).join('')}</div><div class="doc-progress ${dc.complete?'complete':''}">${dc.complete?'✓ Evrak seti tamamlandı':deliveredDocsPending(r)?'⚠ Teslim edildi · Evraklar eksik ('+dc.count+'/4)':((r.status==='won')?'⚠ Kazanıldı · Evraklar eksik ('+dc.count+'/4)':'Evrak: '+dc.count+'/4')}${(r.status==='won')&&!dc.complete?'<span class="won-docs-badge">⚠ Evrak Eksik</span>':''}<span class="badge b-gray" style="margin-left:6px">Talep Dokümanı: ${(state.requestAttachments||[]).filter(a=>a.requestId===r.id).length}</span></div>${requestHeadlineStats(r)}</div>
   <div class="data-cell"><span>Son Teklif</span><b>${r.deadline} · ${di.text}</b></div>
   <div class="data-cell"><span>Durum</span><select class="request-status-select status-${r.status||'draft'}" onclick="event.stopPropagation()" onchange="event.stopPropagation();changeRequestStatus(${r.id},this.value)">${requestStatusOptions(r.status||'draft')}</select></div>
   <div class="data-cell hide-md"><span>Ödeme</span><b>${r.payment}</b>${r.paymentType==='manual'&&Array.isArray(r.paymentPlan)?`<small>${r.paymentPlan.map(x=>'%'+x.percent+' '+x.label).join(' / ')}</small>`:''}</div>
   <div class="data-cell hide-md"><span>Kasa / Banka</span><b>${r.bank}</b></div>
   <div class="chev">⌄</div>
  </div>
  <div class="request-body"><div class="inner"><div class="request-content">
   <div class="request-card-actions"><button class="btn sm" onclick="openRequestModal(${r.id})">Düzenle</button><button class="btn sm" onclick="openTagManager(${r.id})">🏷 Etiketler</button><button class="btn sm" onclick="go('accounts')">Müşteri Carisi</button><button class="btn red sm" onclick="deleteRequest(${r.id})">Talebi Sil</button></div>
   ${r.description?`<div class="description-box"><span>Açıklama</span><div class="description-clamp">${r.description}</div></div>`:''}
   ${r.statusReason?`<div class="description-box"><span>Durum Nedeni</span><div>${r.statusReason}</div>${r.wonAt?`<small>Kazanma: ${new Date(r.wonAt).toLocaleString('tr-TR')}</small>`:''}${r.lostAt?`<small> · Kaybedilme: ${new Date(r.lostAt).toLocaleString('tr-TR')}</small>`:''}${r.cancelledAt?`<small> · İptal: ${new Date(r.cancelledAt).toLocaleString('tr-TR')}</small>`:''}</div>`:(r.wonAt?`<div class="inline-note"><b>Kazanma Tarihi:</b> ${new Date(r.wonAt).toLocaleString('tr-TR')}</div>`:'')}
   ${requestOverviewHtml(r)}
   <div class="flowbar" style="margin-bottom:12px">
    <div class="step done"><div class="stepno">✓</div><div><b>Talep</b><small>${formatQuantity((r.items||[]).length)} kalem</small></div></div>
    <div class="step ${requestSuppliers(r.id).length?'done':'active'}"><div class="stepno">${requestSuppliers(r.id).length?'✓':'2'}</div><div><b>Tedarikçi</b><small>${requestSuppliers(r.id).length?requestSuppliers(r.id).length+' teklif':'Fiyat toplanıyor'}</small></div></div>
    <div class="step ${hasFullComparison(r)?'done':requestSuppliers(r.id).length?'active':''}"><div class="stepno">${hasFullComparison(r)?'✓':'3'}</div><div><b>Kıyaslama</b><small>${hasFullComparison(r)?'Onaylandı':requestSuppliers(r.id).length?'Hazır':'Bekliyor'}</small></div></div>
    <div class="step ${Array.isArray(r.costLines)&&r.costLines.length?'done':hasFullComparison(r)?'active':''}"><div class="stepno">${Array.isArray(r.costLines)&&r.costLines.length?'✓':'4'}</div><div><b>Maliyet</b><small>${Array.isArray(r.costLines)&&r.costLines.length?r.costLines.length+' gider kalemi':'Giderler'}</small></div></div>
    <div class="step ${r.guaranteeConfig?.enabled?'done':hasFullComparison(r)?'active':''}"><div class="stepno">${r.guaranteeConfig?.enabled?'✓':'5'}</div><div><b>Teminat</b><small>${r.guaranteeConfig?.enabled?'Var':'Var / Yok seçin'}</small></div></div>
    <div class="step ${r.customerQuote?'done':hasFullComparison(r)?'active':''}"><div class="stepno">${r.customerQuote?'✓':'6'}</div><div><b>Müşteri Teklifi</b><small>${r.customerQuote?r.customerQuote.no:'Bekliyor'}</small></div></div>
    <div class="step ${requestOrder(r)?'done':r.customerQuote?'active':''}"><div class="stepno">${requestOrder(r)?'✓':'7'}</div><div><b>Sipariş</b><small>${requestOrder(r)?requestOrder(r).no:'Bekliyor'}</small></div></div>
    <div class="step ${dc.complete?'done':requestOrder(r)?'active':''}"><div class="stepno">${dc.complete?'✓':'8'}</div><div><b>Evraklar</b><small>${dc.count}/4 hazır</small></div></div>
   </div>
   <div class="tabs">
    <button class="tab active" onclick="reqTab(${r.id},'items',this)">Talep Kalemleri</button>
    <button class="tab" onclick="reqTab(${r.id},'suppliers',this)">Tedarikçi Teklifleri</button>
    <button class="tab" onclick="reqTab(${r.id},'comparison',this)">Kıyaslama</button>
    <button class="tab" onclick="reqTab(${r.id},'cost',this)">Maliyet</button>
    <button class="tab" onclick="reqTab(${r.id},'guarantee',this)">Teminat</button>
    <button class="tab" onclick="reqTab(${r.id},'customer',this)">Müşteri Teklifi</button>
    <button class="tab" onclick="reqTab(${r.id},'order',this)">Sipariş</button>
    <button class="tab" onclick="reqTab(${r.id},'docs',this)">Evraklar <span class="badge">${requestDocuments(r.id).length}</span></button>
   </div>
   <div class="tabpane active" data-pane="${r.id}-items">${requestItemsHtml(r)}</div>
   <div class="tabpane" data-pane="${r.id}-suppliers">${supplierCardsHtml(r)}</div>
   <div class="tabpane" data-pane="${r.id}-comparison">${requestComparisonHtml(r)}</div>
   <div class="tabpane" data-pane="${r.id}-cost">${requestCostHtml(r)}</div>
   <div class="tabpane" data-pane="${r.id}-guarantee">${requestGuaranteeHtml(r)}</div>
   <div class="tabpane" data-pane="${r.id}-customer">${requestCustomerQuoteHtml(r)}</div>
   <div class="tabpane" data-pane="${r.id}-order">${requestOrderHtml(r)}</div>
   <div class="tabpane" data-pane="${r.id}-docs">${requestDocumentsHtml(r)}</div>
  </div></div></div>
 </article>`}
function normalizeRequestForRender(r){
 if(!r||typeof r!=='object')return null;
 if(!Array.isArray(r.items))r.items=[];
 if(!Array.isArray(r.tags))r.tags=[];
 if(!Array.isArray(r.paymentPlan))r.paymentPlan=[];
 r.id=Number(r.id)||0;r.no=String(r.no||'Talep');r.title=String(r.title||'Konu başlığı girilmedi');r.customer=String(r.customer||'—');r.country=String(r.country||r.deliveryPlace||'—');r.currency=String(r.currency||'EUR');r.status=String(r.status||'collecting');r.payment=String(r.payment||'—');r.bank=String(r.bank||'—');r.priority=String(r.priority||'normal');r.deadline=String(r.deadline||'—');
 return r
}
function renderRequestCardSafe(r){
 try{return renderRequestCardHtml(r)}catch(e){console.error('ASAY request card render error',r?.id,e);return `<article class="request-card status-draft" data-rid="${Number(r?.id)||0}"><div class="request-summary"><div class="request-title"><b>${esc(r?.no||'Talep')} · ${esc(r?.title||'Kayıt')}</b><small>${esc(r?.customer||'—')}</small><div class="inline-note">Kart ayrıntıları yüklenemedi. Düzenle ile kaydı kontrol edin.</div></div><div class="data-cell"><span>Durum</span><b>${esc(statusLabel(r?.status||'collecting'))}</b></div><div class="request-card-actions"><button class="btn sm" onclick="openRequestModal(${Number(r?.id)||0})">Düzenle</button></div></div></article>`}
}
function renderRequests(){normalizeStateSchema();sanitizeClientStateInPlace();
 updateRequestMetrics();const box=document.getElementById('requestList');if(!box)return;const ui=captureRequestUi();
 const term=(document.getElementById('requestSearch')?.value||'').trim().toLocaleLowerCase('tr-TR'),filter=document.getElementById('requestCompletionFilter')?.value||'all',tagCat=document.getElementById('requestTagCategoryFilter')?.value||'',tagFilter=document.getElementById('requestTagFilter')?.value||'';
 const normalized=(Array.isArray(state.requests)?state.requests:[]).map(normalizeRequestForRender).filter(Boolean);
 const rows=normalized.filter(r=>{const dc=requestDocCompletion(r.id),del=requestDelivered(r),tagObjs=entityTagObjects(r.tags),hay=[r.no,r.title,r.customer,r.country,r.description||'',...tagObjs.map(t=>t.name)].join(' ').toLocaleLowerCase('tr-TR');if(term&&!hay.includes(term))return false;if(tagCat&&!tagObjs.some(t=>t.category===tagCat))return false;if(tagFilter&&!tagObjs.some(t=>t.name===tagFilter))return false;if(filter==='cancelled')return r.status==='cancelled';if(filter==='lost')return r.status==='lost';if(filter!=='all'&&['cancelled','lost'].includes(r.status))return false;if(filter==='docsComplete'&&!dc.complete)return false;if(filter==='delivered'&&!del)return false;if(filter==='active'&&(dc.complete||(del&&!deliveredDocsPending(r))))return false;return true});
 box.innerHTML=rows.map(renderRequestCardSafe).join('');
 if(!rows.length)box.innerHTML='<div class="panel empty">Filtreye uyan talep bulunamadı.</div>';updateDashboard();restoreRequestUi(ui);if(pendingRequestContinue)consumePendingRequestContinue();
}
function statusLabel(s){return {draft:'Taslak',collecting:'Fiyat Toplanıyor',ready:'Kıyaslamaya Hazır',quote:'Müşteri Teklifi Hazır',sent:'Teklif Gönderildi',won:'Kazanıldı',lost:'Kaybedildi',cancelled:'İptal Edildi'}[s]||s}
function toggleRequest(id,e){if(e.target.closest('button'))return;document.querySelector(`.request-card[data-rid="${id}"]`).classList.toggle('open')}
function reqTab(id,name,el){const card=el.closest('.request-card');card.querySelectorAll('.tab').forEach(x=>x.classList.remove('active'));el.classList.add('active');card.querySelectorAll('.tabpane').forEach(x=>x.classList.remove('active'));card.querySelector(`[data-pane="${id}-${name}"]`).classList.add('active')}
function requestItemsHtml(r){const items=Array.isArray(r?.items)?r.items:[];return `<div class="panel-head" style="margin-bottom:9px"><div><h3>Talep Kalemleri</h3><div class="sub">Ürün, Marka/Model, miktar ve teknik bilgileri yönetin.</div></div><button class="btn primary sm" onclick="editRequestItems(${r.id})">Kalem Ekle / Düzenle</button></div><div class="table-wrap"><table class="table responsive"><thead><tr><th>Ürün</th><th>Marka / Model</th><th>Teknik</th><th>Miktar</th><th>Birim</th><th></th></tr></thead><tbody>${items.length?items.map(i=>`<tr><td data-label="Ürün"><b>${esc(i?.name||'—')}</b></td><td data-label="Marka / Model">${esc(i?.brandModel||'—')}</td><td data-label="Teknik">${esc(i?.spec||'—')}</td><td data-label="Miktar">${formatQuantity(i?.qty||0)}</td><td data-label="Birim">${esc(i?.unit||'—')}</td><td><button class="btn sm" onclick="editRequestItems(${r.id})">Düzenle</button></td></tr>`).join(''):'<tr><td colspan="6" class="empty">Talep kalemi yok.</td></tr>'}</tbody></table></div>`}
let editingSupplierId=null,editingSupplierRequestId=null,activeQuoteRequestId=null;
let supplierReturnTabRequestId=null;
function requestSuppliers(rid){return (Array.isArray(state.suppliers)?state.suppliers:[]).filter(x=>Number(x?.requestId)===Number(rid))}
function requestDocuments(rid){return (Array.isArray(state.documents)?state.documents:[]).filter(x=>Number(x?.requestId)===Number(rid))}
function requestOrder(r){
 if(!r)return null;
 return (Array.isArray(state.orders)?state.orders:[]).find(o=>Number(o?.requestId)===Number(r.id)&&isActiveOperationalOrder(o))||null
}
function hasFullComparison(r){const items=Array.isArray(r?.items)?r.items:[],sups=requestSuppliers(r?.id);return !!items.length&&!!sups.length&&items.every(it=>{const sid=selectedSupplierId(r.id,it.id);return !!sid&&sups.some(s=>Number(s.id)===Number(sid)&&Number(supplierVatInfo(s,it.id).effective||0)>0&&supplierTechnicalStatus(s,it.id)!=='unsuitable')})}
function fxSnapshotNow(){return {usdTry:Number(state.fx?.usdTry||0),eurTry:Number(state.fx?.eurTry||0),eurUsd:Number(state.fx?.eurUsd||0),source:state.fx?.source||'TCMB',updated:state.fx?.updated||'—',capturedAt:state.fx?.capturedAt||null,rateDate:state.fx?.rateDate||null,fetchedAt:state.fx?.fetchedAt||null}}
function fxIsUsable(maxHours=24){const usd=Number(state.fx?.usdTry||0),eur=Number(state.fx?.eurTry||0),ts=Date.parse(state.fx?.capturedAt||state.fx?.fetchedAt||'');return usd>0&&eur>0&&Number.isFinite(ts)&&Math.abs(Date.now()-ts)<=maxHours*3600000}
function restoreFreshFxCache(){try{const cached=JSON.parse(localStorage.getItem('asay_tcmb_fx')||'null');if(!cached?.usdTry||!cached?.eurTry)return false;const ct=Date.parse(cached.capturedAt||cached.fetchedAt||''),st=Date.parse(state.fx?.capturedAt||state.fx?.fetchedAt||'');if(Number.isFinite(ct)&&(!Number.isFinite(st)||ct>st)){state.fx={...cached};return true}}catch(_e){}return false}
function fxRateBetween(from,to){from=String(from||'').toUpperCase();to=String(to||'').toUpperCase();if(!from||!to)return 0;if(from===to)return 1;if(!fxIsUsable())return 0;const tryRate={TRY:1,USD:Number(state.fx.usdTry||0),EUR:Number(state.fx.eurTry||0)};return tryRate[from]>0&&tryRate[to]>0?tryRate[from]/tryRate[to]:0}
function convertCurrency(amount,from,to,fx=state.fx){
 amount=Number(amount||0);from=String(from||'EUR').toUpperCase();to=String(to||'EUR').toUpperCase();
 if(from===to)return amount;
 const usdTry=Number(fx?.usdTry||0),eurTry=Number(fx?.eurTry||0);
 if(!(usdTry>0)||!(eurTry>0))return 0;
 let tryValue=from==='TRY'?amount:from==='USD'?amount*usdTry:from==='EUR'?amount*eurTry:0;
 if(to==='TRY')return tryValue;
 if(to==='USD')return tryValue/usdTry;
 if(to==='EUR')return tryValue/eurTry;
 return 0
}
function supplierQuoteCurrency(sup,r=null){return String(sup?.currency||r?.currency||'EUR').toUpperCase()}
function supplierSnapshot(sup){return sup?.fxSnapshot?.usdTry&&sup?.fxSnapshot?.eurTry?sup.fxSnapshot:fxSnapshotNow()}
function supplierTechnicalStatus(sup,itemId){
 const raw=String(sup?.technical?.[itemId]??sup?.technical?.[String(itemId)]??'suitable');
 return ['suitable','conditional','unsuitable'].includes(raw)?raw:'suitable'
}
function supplierTechnicalLabel(v){
 return {suitable:'Uygun',conditional:'Şartlı',unsuitable:'Uygun Değil'}[v]||'Uygun'
}
function supplierVatInfo(sup,itemId){
 const v=sup?.vat?.[itemId]||sup?.vat?.[String(itemId)]||{};
 const mode=v.mode==='included'?'included':'excluded';
 const rate=Math.max(0,Number(v.rate||0));
 const enteredRaw=String(sup?.offerBase?.[itemId]??sup?.offerBase?.[String(itemId)]??sup?.offers?.[itemId]??sup?.offers?.[String(itemId)]??'0');
 const entered=Number(enteredRaw||0);
 const net=mode==='included'&&rate>0?entered/(1+rate/100):entered;
 const gross=mode==='included'?entered:entered*(1+rate/100);
 // V3.11.1: Kıyaslama ve otomatik seçim için ortak baz KDV Hariç (net) fiyattır.
 // Girilen fiyatın KDV Dahil/Hariç oluşu ayrıca arayüzde gösterilir.
 const effective=net;
 return {mode,rate,entered:enteredRaw,effective,net,gross,vatAmount:Math.max(0,gross-net),comparisonBasis:'net'}
}
function supplierVatGrossFromInput(base,mode,rate){
 const b=Number(normalizeExactDecimalInput(base)||0),r=Math.max(0,Number(rate||0));
 return mode==='included'?b:b*(1+r/100)
}
function supplierVatNetFromInput(base,mode,rate){
 const b=Number(normalizeExactDecimalInput(base)||0),r=Math.max(0,Number(rate||0));
 return mode==='included'&&r>0?b/(1+r/100):b
}
function updateSupplierVatRow(itemId){
 const price=document.querySelector(`[data-sq-item="${itemId}"]`),
       mode=document.querySelector(`[data-sq-vat-mode="${itemId}"]`),
       rate=document.querySelector(`[data-sq-vat-rate="${itemId}"]`),
       out=document.querySelector(`[data-sq-vat-result="${itemId}"]`);
 if(!price||!mode||!rate||!out)return;
 rate.disabled=Number(rate.value||0)===0&&false;
 const base=price.value,m=mode.value,r=Math.max(0,Number(parseMoneyInput(rate.value)||0)),
       gross=supplierVatGrossFromInput(base,m,r),net=supplierVatNetFromInput(base,m,r),vat=Math.max(0,gross-net),
       cur=document.getElementById('sqCurrency')?.value||'EUR';
 out.innerHTML=`<span>Net: <b>${formatUnitPriceDisplay(String(net))} ${cur}</b></span><span>KDV: <b>${formatMoneyNumber(vat)} ${cur}</b></span><span>KDV Dahil: <strong>${formatUnitPriceDisplay(String(gross))} ${cur}</strong></span>`
}
function updateAllSupplierVatRows(){
 document.querySelectorAll('[data-sq-item]').forEach(x=>updateSupplierVatRow(x.dataset.sqItem))
}
function supplierOriginalTotal(r,sup){
 return (r.items||[]).reduce((sum,it)=>sum+Number(supplierVatInfo(sup,it.id).effective||0)*Number(it.qty||0),0)+Number(sup?.freight||0)
}
function supplierConvertedAmount(sup,amount,to,current=false,r=null){
 return convertCurrency(amount,supplierQuoteCurrency(sup,r),to,current?state.fx:supplierSnapshot(sup))
}
function supplierConvertedTotal(r,sup,to,current=false){return supplierConvertedAmount(sup,supplierOriginalTotal(r,sup),to,current,r)}
function supplierComparisonTotals(r,sup,to){
 const cur=supplierQuoteCurrency(sup,r);let net=0,gross=0,purchase=0,priced=0;
 (r.items||[]).forEach(it=>{const vi=supplierVatInfo(sup,it.id),qty=Number(it.qty||0);if(!(Number(vi.effective||0)>0)||!(qty>0))return;priced++;net+=Number(vi.net||0)*qty;gross+=Number(vi.gross||0)*qty;purchase+=Number(vi.effective||0)*qty});
 const cv=v=>cur===to?v:supplierConvertedAmount(sup,v,to,false,r);
 return {net:cv(net),gross:cv(gross),purchase:cv(purchase),priced,totalItems:(r.items||[]).length}
}
function syncSupplierDirectoryFromAccounts(){
 if(!Array.isArray(state.supplierDirectory))state.supplierDirectory=[];
 const byName=new Map();
 state.supplierDirectory.forEach(s=>{
   const key=normalizedPartyName(s?.name||'');if(key&&!byName.has(key))byName.set(key,{...s})
 });
 (state.accounts||[]).filter(a=>a.type==='supplier').forEach(a=>{
   const key=normalizedPartyName(a.name);if(!key)return;
   const old=byName.get(key)||{};
   byName.set(key,{
     id:old.id||a.id||Date.now(),
     name:a.name,
     contact:old.contact||a.contact||'',
     email:old.email||a.email||'',
     phone:old.phone||a.phone||'',
     country:old.country||a.country||'',
     note:old.note||a.note||''
   })
 });
 state.supplierDirectory=[...byName.values()].sort((a,b)=>String(a.name).localeCompare(String(b.name),'tr'))
}
function renderSupplierDirectoryOptions(selected=''){sanitizeClientStateInPlace();
 syncSupplierDirectoryFromAccounts();
 const sel=document.getElementById('sqSupplier');if(!sel)return;
 const current=selected||sel.value||'';
 const names=[...new Set([
   ...(state.supplierDirectory||[]).map(s=>String(s.name||'').trim()),
   ...(state.accounts||[]).filter(a=>a.type==='supplier').map(a=>String(a.name||'').trim()),
   ...(state.suppliers||[]).map(s=>String(s.name||'').trim())
 ].filter(Boolean))].sort((a,b)=>a.localeCompare(b,'tr'));
 sel.innerHTML='<option value="">Tedarikçi seçin…</option>'+names.map(n=>`<option value="${escAttr(n)}">${n}</option>`).join('');
 if(current&&names.some(n=>normalizedPartyName(n)===normalizedPartyName(current))){
   const exact=names.find(n=>normalizedPartyName(n)===normalizedPartyName(current));sel.value=exact
 }
}
let supplierQuickAddSaving=false;
function resetSupplierQuickAddForm(){
 ['sqNewSupplierName','sqNewSupplierContact','sqNewSupplierEmail','sqNewSupplierPhone','sqNewSupplierNote'].forEach(id=>{const el=document.getElementById(id);if(el)el.value=''});
 const country=document.getElementById('sqNewSupplierCountry');if(country)country.value='Türkiye';const ca=document.getElementById('sqNewSupplierCreateAccount');if(ca){ca.checked=false;ca.disabled=!currentCanWrite('finance')}
}
function toggleSupplierQuickAdd(force=null){
 const panel=document.getElementById('supplierQuickAddPanel');if(!panel)return toast('Hızlı tedarikçi alanı bulunamadı.','warn');
 const show=force===null?panel.style.display==='none':Boolean(force);
 panel.style.display=show?'block':'none';
 const btn=document.getElementById('sqQuickSupplierToggleBtn');if(btn)btn.textContent=show?'Tedarikçi Ekleme Açık':'+ Yeni Tedarikçi Ekle';
 if(show){resetSupplierQuickAddForm();setTimeout(()=>document.getElementById('sqNewSupplierName')?.focus(),0)}
}
async function saveSupplierDirectoryServer(row,createAccount=false,currency='EUR'){
 const execute=async()=>{const res=await fetch('api/supplier_directory.php',{method:'POST',headers:{'Content-Type':'application/json','Accept':'application/json'},body:JSON.stringify({...row,createAccount,currency})}),raw=await res.text();let j=null;try{j=JSON.parse(raw)}catch(_e){}if(!res.ok||!j?.ok)throw new Error(j?.error||'Tedarikçi sunucuya kaydedilemedi.');if(window.acceptServerState&&j.state)window.acceptServerState(j.state,j.revision,j.keyHashes||{});else{if(j.state?.supplierDirectory)state.supplierDirectory=j.state.supplierDirectory;if(j.state?.accounts)state.accounts=j.state.accounts;dbRevision=Number(j.revision||dbRevision)}window.rerenderAll?.();return j};
 return window.runAuthoritativeDbMutation?await window.runAuthoritativeDbMutation(execute):await execute()
}
async function saveSupplierQuickAdd(){
 if(supplierQuickAddSaving)return;const name=(document.getElementById('sqNewSupplierName')?.value||'').trim();if(!name)return toast('Tedarikçi firma adı zorunlu.','warn');
 const row={name,contact:(document.getElementById('sqNewSupplierContact')?.value||'').trim(),email:(document.getElementById('sqNewSupplierEmail')?.value||'').trim(),phone:(document.getElementById('sqNewSupplierPhone')?.value||'').trim(),country:(document.getElementById('sqNewSupplierCountry')?.value||'').trim(),note:(document.getElementById('sqNewSupplierNote')?.value||'').trim()};
 const createAccount=!!document.getElementById('sqNewSupplierCreateAccount')?.checked,currency=document.getElementById('sqCurrency')?.value||'EUR';supplierQuickAddSaving=true;const btn=document.getElementById('sqQuickSupplierSaveBtn');if(btn){btn.disabled=true;btn.textContent='Kaydediliyor…'}
 try{const j=await saveSupplierDirectoryServer(row,createAccount,currency);renderSupplierDirectoryOptions(name);const sel=document.getElementById('sqSupplier'),exact=[...sel.options].find(o=>normalizedPartyName(o.value)===normalizedPartyName(name));sel.value=exact?.value||name;syncSupplierQuoteCurrencyFromAccount(sel.value||name);toggleSupplierQuickAdd(false);toast((j.accountCreated?'Tedarikçi ve cari oluşturuldu':'Tedarikçi eklendi')+' ve teklif için seçildi.','good')}catch(e){toast('Tedarikçi kaydedilemedi: '+(e?.message||e),'warn')}finally{supplierQuickAddSaving=false;if(btn){btn.disabled=false;btn.textContent='Tedarikçiyi Ekle ve Seç'}}
}
function openNestedModal(childId,parentId=''){
 const child=document.getElementById(childId);
 if(!child){toast('Açılacak pencere bulunamadı: '+childId,'warn');return false}
 const parent=parentId?document.getElementById(parentId):null;
 child.dataset.parentModal=parent?.classList.contains('show')?parentId:'';
 child.classList.add('nested-modal');
 child.style.setProperty('z-index','1200','important');
 openModal(childId);
 requestAnimationFrame(()=>child.style.setProperty('z-index','1200','important'));
 return true
}
function openSupplierDirectoryModal(ev=null){
 ev?.preventDefault?.();ev?.stopPropagation?.();
 ['sdName','sdContact','sdEmail','sdPhone','sdNote'].forEach(id=>{const el=document.getElementById(id);if(el)el.value=''});
 const c=document.getElementById('sdCountry');if(c)c.value='Türkiye';
 if(!openNestedModal('supplierDirectoryModal','supplierModal'))return;
 setTimeout(()=>document.getElementById('sdName')?.focus(),60)
}
async function saveSupplierDirectory(){
 const name=document.getElementById('sdName')?.value.trim()||'';if(!name)return toast('Tedarikçi firma adı zorunlu.','warn');const row={name,contact:document.getElementById('sdContact')?.value.trim()||'',email:document.getElementById('sdEmail')?.value.trim()||'',phone:document.getElementById('sdPhone')?.value.trim()||'',country:document.getElementById('sdCountry')?.value.trim()||'',note:document.getElementById('sdNote')?.value.trim()||''};
 try{await saveSupplierDirectoryServer(row,false,document.getElementById('sqCurrency')?.value||'EUR');closeModal('supplierDirectoryModal');renderSupplierDirectoryOptions(name);const sel=document.getElementById('sqSupplier');if(sel){const exact=[...sel.options].find(o=>normalizedPartyName(o.value)===normalizedPartyName(name));sel.value=exact?.value||name;syncSupplierQuoteCurrencyFromAccount(sel.value)}toast('Tedarikçi bilgileri kaydedildi.','good')}catch(e){toast('Tedarikçi kaydedilemedi: '+e.message,'warn')}
}
function supplierAccountCurrency(name){
 const rows=(state.accounts||[]).filter(a=>a.type==='supplier'&&normalizedPartyName(a.name)===normalizedPartyName(name));
 if(!rows.length)return null;
 const currencies=[...new Set(rows.map(a=>String(a.currency||'EUR').toUpperCase()))];
 return currencies.length===1?currencies[0]:null
}
function syncSupplierQuoteCurrencyFromAccount(name){
 if(editingSupplierId)return;
 const c=supplierAccountCurrency(name);if(c&&['TRY','EUR','USD'].includes(c))document.getElementById('sqCurrency').value=c;
 renderSupplierQuoteFxPreview()
}
function renderSupplierQuoteFxPreview(){
 const cur=document.getElementById('sqCurrency')?.value||'EUR',r=state.requests.find(x=>x.id===editingSupplierRequestId);
 const lab=document.getElementById('sqFreightLabel');if(lab)lab.textContent='Navlun '+cur;
 document.querySelectorAll('[data-sq-price-label]').forEach(x=>x.textContent='Birim Fiyat '+cur);
 const box=document.getElementById('sqFxPreview');if(!box)return;
 const snap=fxSnapshotNow();
 box.innerHTML=cur==='TRY'
  ?`1 EUR = <b>${formatMoneyNumber(snap.eurTry)} TRY</b> · 1 USD = <b>${formatMoneyNumber(snap.usdTry)} TRY</b><br><small>Kaydedildiğinde bu kur teklif snapshot’ı olarak sabitlenir. Güncel karşılık ayrıca gösterilir.</small>`
  :cur==='EUR'
   ?`1 EUR = <b>${formatMoneyNumber(snap.eurTry)} TRY</b> · EUR/USD <b>${Number(snap.eurUsd||0).toFixed(4)}</b><br><small>Teklif EUR olarak saklanır.</small>`
   :`1 USD = <b>${formatMoneyNumber(snap.usdTry)} TRY</b> · EUR/USD <b>${Number(snap.eurUsd||0).toFixed(4)}</b><br><small>Teklif USD olarak saklanır.</small>`;
 updateAllSupplierVatRows()
}
async function setRequestComparisonCurrency(rid,currency){
 if(await runLockedServerOperation('quote',rid,{action:'comparison_currency',requestId:rid,currency,idempotencyKey:makeIdempotencyKey()}))setTimeout(()=>openRequestTab(rid,'comparison'),30)
}
function supplierCardsHtml(r){
 const sups=requestSuppliers(r.id),cmp=r.comparisonCurrency||r.currency||'EUR',
 toolbar=`<div class="supplier-toolbar"><div><b>Gelen Tedarikçi Teklifleri</b><div class="sub">Orijinal teklif para birimi korunur; teklif tarihi kuru sabitlenir, güncel TCMB karşılığı ayrıca gösterilir.</div></div><div class="actions"><span class="badge b-gray">Kıyas: ${cmp}</span><button class="btn primary sm" type="button" data-action="supplier-quote-new" onclick="openSupplierQuote(null,${r.id});event.stopPropagation()">+ Yeni Tedarikçi Teklifi</button></div></div>`;
 if(!sups.length)return toolbar+`<div class="empty">Henüz teklif yok. Yeni teklif açıldığında ${r.items.length} talep kalemi otomatik listelenecek.</div>`;
 return toolbar+`<div class="supplier-grid">${sups.map(sup=>{
   const cur=supplierQuoteCurrency(sup,r),orig=supplierOriginalTotal(r,sup),snapCmp=supplierConvertedTotal(r,sup,cmp,false),liveEur=supplierConvertedTotal(r,sup,'EUR',true),liveUsd=supplierConvertedTotal(r,sup,'USD',true);
   const snap=supplierSnapshot(sup);
   return `<div class="supplier-card"><h4>${sup.name}</h4>
    <div class="supplier-total-label">Toplam Tutar · Kıyas Bazı: KDV Hariç</div>
    <div class="supplier-money-stack"><span class="original">${money(orig,cur)}</span>${cur!==cmp?`<span class="converted">Teklif kuru · ${money(snapCmp,cmp)}</span>`:''}<span class="current">Güncel TCMB · ${money(liveEur,'EUR')} · ${money(liveUsd,'USD')}</span></div>
    <div class="refline"><span>Ref: ${sup.ref||'—'}</span><span>Termin: ${sup.delivery} gün</span></div>
    <small>${sup.payment} · Navlun ${money(sup.freight||0,cur)}</small>
    <div class="sub" style="margin-top:4px">Kur snapshot: ${snap.updated||'—'} · EUR/TRY ${Number(snap.eurTry||0).toFixed(4)} · USD/TRY ${Number(snap.usdTry||0).toFixed(4)}</div>
    ${sup.description?`<div class="description-box"><span>Açıklama</span><div class="description-clamp">${sup.description}</div></div>`:''}
    <div class="supplier-lines">${r.items.map(it=>{const vi=supplierVatInfo(sup,it.id),p=Number(vi.effective||0),qty=Number(it.qty||0);if(!(Number(vi.entered||0)>0))return '';const lineTotal=p*qty;return `<div class="supplier-line supplier-line-total"><span><b>${it.name}</b><small>${formatQuantity(qty)} ${it.unit} · Girilen: ${vi.mode==='included'?'KDV Dahil':'KDV Hariç'} %${formatUnitPriceDisplay(String(vi.rate))} · Kıyas bazı: KDV Hariç ${formatUnitPriceDisplay(String(vi.net))} ${cur}</small></span><span class="supplier-line-money"><small>Girilen Birim</small><b>${formatUnitPriceDisplay(vi.entered)} ${cur}</b><small>KDV Hariç Kalem Toplamı</small><strong>${money(lineTotal,cur)}</strong></span></div>`}).join('')}</div>
    <div class="supplier-actions"><button class="btn sm" type="button" data-action="supplier-quote-edit" onclick="event.stopPropagation();openSupplierQuote(${sup.id},${r.id})">Düzenle</button><button class="btn red sm" type="button" data-action="supplier-quote-delete" onclick="event.stopPropagation();deleteSupplierQuote(${sup.id},${r.id})">Sil</button><button class="btn sm" onclick="openRequestComparison(${r.id})">Kıyaslama</button></div>
   </div>`
 }).join('')}</div>`;
}
async function openSupplierQuote(id,requestId=null){
 try{
 const rid=Number(requestId||0);if(!rid)return toast('Talep seçilemedi.','warn');
 const r=state.requests.find(x=>Number(x.id)===rid);if(!r)return toast('Talep bulunamadı. Sayfayı yenileyip tekrar deneyin.','warn');
 if(requestOrder(r))return toast('Siparişe dönüşmüş talepte tedarikçi teklifi doğrudan değiştirilemez. Sipariş revizyon/iptal akışını kullanın.','warn');
 const sup=id?state.suppliers.find(x=>Number(x.id)===Number(id)&&Number(x.requestId)===rid):null;
 if(id&&!sup)return toast('Düzenlenecek tedarikçi teklifi bulunamadı.','warn');

 await releaseEntityLock('supplierModal');
 editingSupplierId=sup?.id||null;editingSupplierRequestId=rid;
 const quickPanel=document.getElementById('supplierQuickAddPanel');if(quickPanel)quickPanel.style.display='none';
 const quickToggle=document.getElementById('sqQuickSupplierToggleBtn');if(quickToggle)quickToggle.textContent='+ Yeni Tedarikçi Ekle';
 renderSupplierDirectoryOptions(sup?.name||'');
 document.getElementById('supplierModalTitle').textContent=sup?'Tedarikçi Teklifini Düzenle':'Yeni Tedarikçi Teklifi';
 document.getElementById('sqSupplier').value=sup?.name||'';
 document.getElementById('sqRef').value=sup?.ref||'';
 document.getElementById('sqValidity').value=sup?.validity||'15 gün';
 document.getElementById('sqPayment').value=sup?.payment||'T/T 30/70';
 document.getElementById('sqDelivery').value=sup?.delivery||30;
 document.getElementById('sqCurrency').value=sup?.currency||supplierAccountCurrency(sup?.name||'')||r.currency||'EUR';
 document.getElementById('sqFreight').value=formatMoneyNumber(sup?.freight||0);
 document.getElementById('sqDescription').value=sup?.description||'';
 document.getElementById('supplierOfferEditor').innerHTML=(r.items||[]).map(it=>{
  const vi=supplierVatInfo(sup,it.id),base=sup?vi.entered:'0',mode=sup?vi.mode:'excluded',rate=sup?vi.rate:20;
  return `<div class="supplier-edit-row supplier-vat-row">
  <label><span class="sub">Ürün</span><input value="${escAttr(it.name)}" disabled></label>
  <label><span class="sub">Miktar</span><input value="${formatQuantity(it.qty)} ${it.unit}" disabled></label>
  <label><span class="sub" data-sq-price-label>Girilen Birim Fiyat ${sup?.currency||r.currency}</span><input type="text" inputmode="decimal" data-sq-item="${it.id}" value="${formatUnitPriceDisplay(base)}" oninput="updateSupplierVatRow(${it.id})"></label>
  <label><span class="sub">KDV Durumu</span><select data-sq-vat-mode="${it.id}" onchange="updateSupplierVatRow(${it.id})"><option value="excluded" ${mode==='excluded'?'selected':''}>KDV Hariç</option><option value="included" ${mode==='included'?'selected':''}>KDV Dahil</option></select></label>
  <label><span class="sub">KDV Oranı (%)</span><input type="text" inputmode="decimal" data-sq-vat-rate="${it.id}" value="${formatUnitPriceDisplay(String(rate))}" oninput="updateSupplierVatRow(${it.id})" placeholder="20"></label>
  <div class="supplier-vat-result" data-sq-vat-result="${it.id}"></div>
  <label><span class="sub">Teknik Uygunluk</span><select data-sq-technical="${it.id}"><option value="suitable" ${supplierTechnicalStatus(sup,it.id)==='suitable'?'selected':''}>Uygun</option><option value="conditional" ${supplierTechnicalStatus(sup,it.id)==='conditional'?'selected':''}>Şartlı</option><option value="unsuitable" ${supplierTechnicalStatus(sup,it.id)==='unsuitable'?'selected':''}>Uygun Değil</option></select></label>
  <label><span class="sub">Bağlantı</span><span class="badge b-green">Talep Kalemine Bağlı</span></label>
 </div>`}).join('');
 renderSupplierQuoteFxPreview();updateAllSupplierVatRows();openModal('supplierModal')

 }catch(err){
   console.error('Tedarikçi teklif modalı açılamadı:',err);
   toast('Tedarikçi teklif ekranı açılamadı: '+String(err?.message||err),'warn')
 }
}
async function saveSupplierQuote(){
 const rid=Number(editingSupplierRequestId||0),r=state.requests.find(x=>Number(x.id)===rid),name=document.getElementById('sqSupplier').value.trim();
 if(!r)return toast('Talep bulunamadı.','warn');
 if(!name)return toast('Tedarikçi seçin.','warn');
 if(requestOrder(r))return toast('Siparişe dönüşmüş talepte tedarikçi fiyatı değiştirilemez.','warn');

 const offerBase={},vat={},technical={};
 document.querySelectorAll('[data-sq-item]').forEach(x=>{
   const iid=x.dataset.sqItem,base=normalizeExactDecimalInput(x.value),
         mode=document.querySelector(`[data-sq-vat-mode="${iid}"]`)?.value==='included'?'included':'excluded',
         rate=Math.max(0,parseMoneyInput(document.querySelector(`[data-sq-vat-rate="${iid}"]`)?.value||0));
   offerBase[iid]=base;vat[iid]={mode,rate};
   technical[iid]=document.querySelector(`[data-sq-technical="${iid}"]`)?.value||'suitable'
 });
 if(!Object.values(offerBase).some(v=>Number(v)>0))return toast('En az bir kalem için fiyat girin.','warn');
 if(!fxIsUsable(24))return toast('Tedarikçi teklifini kaydetmeden önce güncel TCMB kurunu yenileyin. Kur verisi 24 saatten eski veya geçersiz.','warn');

 const payload={action:'supplier_quote_save',requestId:rid,supplierId:editingSupplierId||0,name,ref:document.getElementById('sqRef').value||'SUP-'+Date.now(),validity:document.getElementById('sqValidity').value||'15 gün',payment:document.getElementById('sqPayment').value,delivery:+document.getElementById('sqDelivery').value||0,currency:document.getElementById('sqCurrency').value||r.currency||'EUR',freight:parseMoneyInput(document.getElementById('sqFreight').value),description:document.getElementById('sqDescription').value.trim(),offerBase,vat,technical,fxSnapshot:fxSnapshotNow(),idempotencyKey:makeIdempotencyKey()};
 supplierReturnTabRequestId=rid;
 const ok=await runServerOperation(payload);
 if(ok){
  editingSupplierId=null;editingSupplierRequestId=null;
  closeModal('supplierModal');
  toast('Tedarikçi teklifi kaydedildi; kıyaslama güncellendi.','good');
  setTimeout(()=>{openRequestTab(rid,'suppliers');supplierReturnTabRequestId=null},70)
 }
}
async function deleteSupplierQuote(supplierId,requestId){
 try{
 const rid=Number(requestId||0),sid=Number(supplierId||0),r=state.requests.find(x=>Number(x.id)===rid),sup=state.suppliers.find(x=>Number(x.id)===sid&&Number(x.requestId)===rid);
 if(!r||!sup)return toast('Silinecek tedarikçi teklifi bulunamadı.','warn');
 if(requestOrder(r))return toast('Siparişe dönüşmüş talepte tedarikçi teklifi silinemez. Önce sipariş iptal/revizyon akışını kullanın.','warn');
 if(!await appConfirm(`${sup.name} tedarikçi teklifi silinsin mi?\n\nBu teklife bağlı kıyaslama seçimleri temizlenecek. İşlem geri alınamaz.`,'Tedarikçi Teklifi Silme'))return;
 supplierReturnTabRequestId=rid;
 const ok=await runServerOperation({action:'supplier_quote_delete',requestId:rid,supplierId:sid,idempotencyKey:makeIdempotencyKey()});
 if(ok){toast(`${sup.name} tedarikçi teklifi silindi.`,'good');setTimeout(()=>{openRequestTab(rid,'suppliers');supplierReturnTabRequestId=null},70)}

 }catch(err){
   console.error('Tedarikçi teklifi silinemedi:',err);
   toast('Tedarikçi teklifi silinemedi: '+String(err?.message||err),'warn')
 }
}
const comparisonExpenseLabels={
 freight:'Navlun',
 customs:'Gümrükçü',
 unloading:'İndirme',
 inland:'İç Nakliye',
 other:'Diğer'
};
function comparisonExpenseRealizedRows(r){
 return Array.isArray(r?.realizedComparisonExpenses)?r.realizedComparisonExpenses:[]
}
function comparisonExpenseRealizedTotal(r,cmp){
 return comparisonExpenseRealizedRows(r).reduce((sum,x)=>{
   if(x.reversed)return sum;
   const reportCur=String(x.reportCurrency||'').toUpperCase(),reportAmount=Number(x.reportAmount||0);
   if(reportCur===String(cmp||'').toUpperCase()&&reportAmount>=0)return sum+reportAmount;
   const cur=String(x.currency||cmp).toUpperCase(),amount=Number(x.amount||0),fx=x.fxSnapshot||state.fx;
   return sum+(cur===cmp?amount:convertCurrency(amount,cur,cmp,fx))
 },0)
}
function operationalExpensePartyCandidates(currency=''){
 const cur=String(currency||'').toUpperCase();
 return groupedAccounts().filter(g=>g.type!=='customer').map(g=>{
   const exact=Object.values(g.accounts||{}).find(a=>String(a.currency||'').toUpperCase()===cur)||null;
   const currencies=Object.keys(g.accounts||{}).filter(Boolean);
   return {...g,exactAccount:exact,availableCurrencies:currencies}
 }).sort((x,y)=>String(x.name||'').localeCompare(String(y.name||''),'tr'))
}
function refreshComparisonExpensePartySelect(preselect=''){
 const rid=Number(document.getElementById('cerRequestId')?.value||0),r=state.requests.find(x=>Number(x.id)===rid);if(!r)return;
 const cur=String(document.getElementById('cerCurrency')?.value||r.comparisonCurrency||r.currency||'EUR').toUpperCase();
 const party=document.getElementById('cerParty'),candidates=operationalExpensePartyCandidates(cur);
 if(!party)return;
 party.innerHTML='<option value="">Cari / hizmet sağlayıcı seçin…</option>'+candidates.map(g=>{const v=g.exactAccount?String(g.exactAccount.id):('pk:'+g.partyKey),missing=!g.exactAccount,sub=missing?` · ${cur} hesabı ödeme sırasında otomatik açılır`:` · ${cur}`;return `<option value="${escAttr(v)}" data-party-key="${escAttr(g.partyKey||'')}" ${String(v)===String(preselect)?'selected':''}>${accountTypeLabel(g.type)} · ${esc(g.name)}${sub}</option>`}).join('');
 if(!candidates.length)party.innerHTML+='<option value="" disabled>Uygun cari hesap bulunamadı</option>'
}
function openComparisonExpenseRealize(rid,category='freight'){
 const r=state.requests.find(x=>Number(x.id)===Number(rid));if(!r)return toast('Talep bulunamadı.','warn');
 if(!(r.status==='won'||requestOrder(r)))return toast('Gerçek gider kaydı yalnız kazanılmış veya siparişe dönüşmüş taleplerde açılabilir.','warn');
 const cmp=r.comparisonCurrency||r.currency||'EUR',reportCur=String(r.currency||cmp||'EUR').toUpperCase(),vals=comparisonExpenseValues(r,cmp);
 document.getElementById('cerRequestId').value=rid;
 const cat=document.getElementById('cerCategory');
 cat.innerHTML=Object.entries(comparisonExpenseLabels).map(([k,v])=>`<option value="${k}" ${k===category?'selected':''}>${v}</option>`).join('');
 document.getElementById('cerCurrency').value=cmp;
 document.getElementById('cerReportCurrency').value=reportCur;
 document.getElementById('cerFxMode').value='tcmb';
 document.getElementById('cerManualRate').value='';
 document.getElementById('cerDate').value=todayISO();document.getElementById('cerInvoiceNo').value='';document.getElementById('cerInvoiceDate').value='';document.getElementById('cerDueDate').value='';document.getElementById('cerAttachment').innerHTML=expenseAttachmentOptions(r.id,0);
 document.getElementById('cerRef').value=(r.no||'TALEP')+'-'+String(category).toUpperCase();
 document.getElementById('cerCustomCategory').value='';
 refreshComparisonExpensePartySelect();
 syncComparisonExpenseRealizeForm();
 document.getElementById('comparisonExpenseRealizeModal')?.classList.add('nested-modal');
 openModal('comparisonExpenseRealizeModal')
}
function syncComparisonExpenseRealizeForm(){
 const rid=Number(document.getElementById('cerRequestId')?.value||0),r=state.requests.find(x=>Number(x.id)===rid);if(!r)return;
 const cmp=r.comparisonCurrency||r.currency||'EUR',cat=document.getElementById('cerCategory')?.value||'freight',
       vals=comparisonExpenseValues(r,cmp),amount=cat==='other'?0:Number(vals[cat]||0),customWrap=document.getElementById('cerCustomCategoryWrap'),
       custom=String(document.getElementById('cerCustomCategory')?.value||'').trim(),label=cat==='other'?(custom||'Diğer'):comparisonExpenseLabels[cat]||cat;
 if(customWrap)customWrap.style.display=cat==='other'?'grid':'none';
 document.getElementById('cerAmount').value=formatUnitPriceDisplay(String(amount||0));
 document.getElementById('cerDescription').value=`${label} gerçek gideri`;
 document.getElementById('cerRef').value=(r.no||'TALEP')+'-'+String(cat==='other'?(custom||'DIGER'):cat).toUpperCase().replace(/[^A-Z0-9ÇĞİÖŞÜ_-]+/g,'-');
 const realized=comparisonExpenseRealizedRows(r).filter(x=>x.category===cat&&(cat!=='other'||!custom||String(x.customCategory||'').toLocaleLowerCase('tr-TR')===custom.toLocaleLowerCase('tr-TR'))).reduce((n,x)=>n+Number(x.amount||0),0);
 document.getElementById('cerInfo').innerHTML=`Tahmini ${esc(label)}: <b>${money(amount,cmp)}</b> · Bu gider türünde daha önce gerçekleşen: <b>${money(realized,cmp)}</b>. Gerçek tutar tahminden farklı olabilir.`;
 syncComparisonExpenseRealizeBanks()
}
let comparisonExpensePaymentFx=null;
function syncComparisonExpenseFxMode(){
 const expenseCur=String(document.getElementById('cerCurrency')?.value||'EUR').toUpperCase(),bankId=Number(document.getElementById('cerBank')?.value||0),bank=(state.cashAccounts||[]).find(b=>Number(b.id)===bankId),bankCur=String(bank?.currency||expenseCur).toUpperCase(),mode=document.getElementById('cerFxMode')?.value||'tcmb',wrap=document.getElementById('cerManualRateWrap'),label=document.getElementById('cerManualRateLabel');
 const cross=!!bank&&bankCur!==expenseCur;
 if(wrap)wrap.style.display=(mode==='manual'&&cross)?'grid':'none';
 if(label)label.textContent=`Manuel Kur · 1 ${expenseCur} = ? ${bankCur}`;
 if(!cross&&document.getElementById('cerFxMode'))document.getElementById('cerFxMode').value='tcmb';
 syncComparisonExpensePaymentPreview()
}
async function syncComparisonExpenseRealizeBanks(){
 const rid=Number(document.getElementById('cerRequestId')?.value||0),r=state.requests.find(x=>Number(x.id)===rid);if(!r)return;
 const bank=document.getElementById('cerBank'),prev=String(bank?.value||'');
 const banks=(state.cashAccounts||[]);
 bank.innerHTML='<option value="">Ödeme yapılacak Kasa/Banka hesabını seçin…</option>'+banks.map(b=>`<option value="${b.id}">${esc(b.name)} · ${b.currency} · ${fmtMoneyCur(b.balance,b.currency)}</option>`).join('');
 if(prev&&banks.some(b=>String(b.id)===prev))bank.value=prev;
 else {const same=banks.find(b=>String(b.currency||'').toUpperCase()===String(document.getElementById('cerCurrency')?.value||'').toUpperCase());if(same)bank.value=String(same.id)}
 await syncComparisonExpenseFxMode()
}
async function syncComparisonExpensePaymentPreview(forceHistorical=false){
 const rid=Number(document.getElementById('cerRequestId')?.value||0),r=state.requests.find(x=>Number(x.id)===rid);if(!r)return;
 const expenseCur=String(document.getElementById('cerCurrency')?.value||r.currency||'EUR').toUpperCase(),reportCur=String(r.currency||expenseCur).toUpperCase(),amount=parseMoneyInput(document.getElementById('cerAmount')?.value||0),bankId=Number(document.getElementById('cerBank')?.value||0),bank=(state.cashAccounts||[]).find(b=>Number(b.id)===bankId),box=document.getElementById('cerPaymentPreview'),date=document.getElementById('cerDate')?.value||todayISO(),mode=document.getElementById('cerFxMode')?.value||'tcmb',manualRate=Number(document.getElementById('cerManualRate')?.value||0);
 const reportEl=document.getElementById('cerReportCurrency');if(reportEl)reportEl.value=reportCur;
 if(!box)return;if(!bank){box.textContent='Kasa/Banka seçildiğinde gerçek ödeme ve operasyon karşılığı hesaplanır.';comparisonExpensePaymentFx=null;return}
 let fx=normalizedFxSnapshot(state.fx);if((forceHistorical||date<todayISO())&&date){const hist=await fetchHistoricalTcmb(date);if(hist)fx=hist}
 comparisonExpensePaymentFx=fx;
 const bankCur=String(bank.currency||expenseCur).toUpperCase(),cross=bankCur!==expenseCur;
 if(cross&&!fx&&mode!=='manual'){box.innerHTML='<b>Kur bulunamadı.</b> TCMB kurunu yenileyin veya Manuel / Banka Kuru seçin.';return}
 const tcmbPaymentRate=cross&&fx?convertCurrency(1,expenseCur,bankCur,fx):1;
 const usedPaymentRate=cross?(mode==='manual'&&manualRate>0?manualRate:tcmbPaymentRate):1;
 if(!(usedPaymentRate>0)){box.innerHTML='<b>Geçerli manuel kur girin.</b>';return}
 const bankAmount=amount*usedPaymentRate;
 let reportAmount=amount;
 if(reportCur!==expenseCur){
   if(reportCur===bankCur&&mode==='manual'&&cross&&manualRate>0)reportAmount=bankAmount;
   else if(fx)reportAmount=convertCurrency(amount,expenseCur,reportCur,fx);
   else {box.innerHTML='<b>Operasyon karşılığı için TCMB referans kuru gerekli.</b>';return}
 }
 const rateDate=fx?.rateDate?` · Kur tarihi ${esc(fx.rateDate)}`:'',source=cross?(mode==='manual'?'Manuel / Banka':'TCMB'):'Kur dönüşümü yok';
 const tcmbText=cross&&fx?`<br><small>TCMB referans: 1 ${expenseCur} = ${reportFmtNum(tcmbPaymentRate)} ${bankCur}${rateDate}</small>`:'';
 box.innerHTML=`Gerçek gider: <b>${fmtMoneyCur(amount,expenseCur)}</b><br>Kasa çıkışı: <b>${fmtMoneyCur(bankAmount,bankCur)}</b><br>Operasyon karşılığı: <b>${fmtMoneyCur(reportAmount,reportCur)}</b><br><small>Kullanılan kur: ${source}${cross?` · 1 ${expenseCur} = ${reportFmtNum(usedPaymentRate)} ${bankCur}`:''}</small>${tcmbText}`
}
let costQuickAccountContext=null;
let realizingCostLineId='';
function openCostServiceAccountCreateFromModal(){openCostServiceAccountCreate(Number(document.getElementById('cerRequestId')?.value||0))}
function openCostServiceAccountCreate(rid){
 const r=state.requests.find(x=>Number(x.id)===Number(rid));if(!r)return;
 const cur=String(document.getElementById('cerCurrency')?.value||r.comparisonCurrency||r.currency||'EUR').toUpperCase();
 costQuickAccountContext={rid,returnTo:'comparisonExpenseRealizeModal'};
 openAccountCreateModal('',cur);
 const m=document.getElementById('accountCreateModal');m?.classList.add('nested-modal');
 document.getElementById('accNewType').value='service';
 document.getElementById('accNewCurrency').value=cur;
 document.getElementById('accNewRequest').value=r.no||'';
 document.getElementById('accountCreateTitle').textContent='Yeni Hizmet Sağlayıcı / Cari Ekle';
 document.getElementById('accountCreateSub').textContent='Bu cari, Maliyet > Gerçekleşen Operasyon Giderleri ekranında doğrudan seçilebilir.'
}
async function saveRealizedComparisonExpense(){
 const rid=Number(document.getElementById('cerRequestId')?.value||0),r=state.requests.find(x=>Number(x.id)===rid);if(!r)return toast('Talep bulunamadı.','warn');
 const category=document.getElementById('cerCategory').value,customCategory=String(document.getElementById('cerCustomCategory')?.value||'').trim();
 if(category==='other'&&!customCategory)return toast('Diğer gider türü için manuel gider adını yazın.','warn');
 const payload={
   action:'comparison_expense_realize',
   requestId:rid,
   category,
   customCategory,
   amount:parseMoneyInput(document.getElementById('cerAmount').value),
   currency:document.getElementById('cerCurrency').value,
   partyId:(()=>{const v=String(document.getElementById('cerParty').value||'');return v.startsWith('pk:')?0:Number(v||0)})(),
   partyKey:(()=>{const s=document.getElementById('cerParty'),v=String(s?.value||'');return v.startsWith('pk:')?v.slice(3):String(s?.selectedOptions?.[0]?.dataset?.partyKey||'')})(),
   bankId:Number(document.getElementById('cerBank').value||0),
   date:document.getElementById('cerDate').value||todayISO(),
   ref:document.getElementById('cerRef').value.trim(),
   description:document.getElementById('cerDescription').value.trim(),
   invoiceNo:document.getElementById('cerInvoiceNo')?.value.trim()||'',invoiceDate:document.getElementById('cerInvoiceDate')?.value||'',dueDate:document.getElementById('cerDueDate')?.value||'',attachmentId:Number(document.getElementById('cerAttachment')?.value||0),
   reportCurrency:String(r.currency||document.getElementById('cerCurrency').value||'EUR').toUpperCase(),
   fxMode:document.getElementById('cerFxMode')?.value||'tcmb',
   manualRate:Number(document.getElementById('cerManualRate')?.value||0),
   fxSnapshot:comparisonExpensePaymentFx||fxSnapshotNow(),
   plannedCostLineId:realizingCostLineId||'',
   idempotencyKey:makeIdempotencyKey()
 };
 if(!payload.partyId&&!payload.partyKey)return toast('Ödeme yapılacak cari / hizmet sağlayıcıyı seçin.','warn');
 if(!payload.bankId)return toast('Ödeme yapılacak Kasa / Banka hesabını seçin. Finans hareketi henüz oluşturulmadı.','warn');
 if(!(payload.amount>0))return toast('Geçerli ödeme tutarı girin.','warn');if(realizingCostLineId){const line=(r.costLines||[]).find(x=>String(x.id||'')===String(realizingCostLineId)),remaining=costLineRemaining(r,line);if(payload.amount>remaining+0.001)return toast('Ödeme kalan gider tutarını aşamaz. Kalan: '+fmtMoneyCur(remaining,line?.currency||payload.currency),'warn')}
 const selectedBank=(state.cashAccounts||[]).find(b=>Number(b.id)===Number(payload.bankId)),bankCur=String(selectedBank?.currency||payload.currency).toUpperCase(),expenseCur=String(payload.currency||'EUR').toUpperCase(),reportCur=String(payload.reportCurrency||expenseCur).toUpperCase();if(bankCur!==expenseCur&&payload.fxMode==='manual'&&!(payload.manualRate>0))return toast('Manuel / Banka kuru seçildi. Geçerli manuel kur girin.','warn');if((bankCur!==expenseCur||reportCur!==expenseCur)&&!normalizedFxSnapshot(payload.fxSnapshot)&&!(payload.fxMode==='manual'&&payload.manualRate>0&&reportCur===bankCur))return toast('Çapraz döviz veya operasyon karşılığı için geçerli kur gerekli.','warn');
 if(await runServerOperation(payload)){
   realizingCostLineId='';
   closeModal('comparisonExpenseRealizeModal');
   toast('Ödeme onaylandı; gerçek gider seçilen Kasa/Banka ve cariye işlendi.','good');
   setTimeout(()=>openRequestTab(rid,'cost'),60)
 }
}
function comparisonExpenseValues(r,cmp){
 const src=r?.comparisonExpenses||{},from=String(src.currency||cmp||'EUR').toUpperCase(),
       fx=src.fxSnapshot?.usdTry&&src.fxSnapshot?.eurTry?src.fxSnapshot:state.fx;
 const read=k=>Math.max(0,Number(src[k]||0));
 const cv=k=>from===cmp?read(k):convertCurrency(read(k),from,cmp,fx);
 return {
   currency:cmp,
   freight:cv('freight'),
   customs:cv('customs'),
   unloading:cv('unloading'),
   inland:cv('inland')
 }
}
function comparisonExpenseTotal(v){
 return Number(v.freight||0)+Number(v.customs||0)+Number(v.unloading||0)+Number(v.inland||0)
}
function updateComparisonExpensePreview(rid){
 const r=state.requests.find(x=>Number(x.id)===Number(rid));if(!r)return;
 const cmp=r.comparisonCurrency||r.currency||'EUR',summary=selectedComparisonSummary(r,cmp);
 const vals={};
 ['freight','customs','unloading','inland'].forEach(k=>{
   vals[k]=Math.max(0,parseMoneyInput(document.querySelector(`[data-comparison-expense="${k}"][data-rid="${rid}"]`)?.value||0))
 });
 const fixedTotal=comparisonExpenseTotal(vals),manualTotal=requestManualCostLinesTotal(r,cmp),expTotal=fixedTotal+manualTotal,grand=summary.itemSubtotal+expTotal;
 const expEl=document.getElementById(`comparisonExpenseTotal-${rid}`),grandEl=document.getElementById(`comparisonGrandTotal-${rid}`);
 if(expEl)expEl.textContent=money(expTotal,cmp);
 if(grandEl)grandEl.textContent=money(grand,cmp)
}
async function saveComparisonExpenses(rid){return toast('Legacy Ek Maliyetler kaydı kapatıldı. Maliyet > Diğer Giderler bölümünü kullanın.','warn');/* legacy */
 const r=state.requests.find(x=>Number(x.id)===Number(rid));if(!r)return toast('Talep bulunamadı.','warn');
 if(requestOrder(r))return toast('Siparişe dönüşmüş talepte kıyaslama giderleri değiştirilemez.','warn');
 const cmp=r.comparisonCurrency||r.currency||'EUR',expenses={};
 ['freight','customs','unloading','inland'].forEach(k=>{
   expenses[k]=normalizeExactDecimalInput(document.querySelector(`[data-comparison-expense="${k}"][data-rid="${rid}"]`)?.value||'0')
 });
 if(await runLockedServerOperation('quote',rid,{
   action:'comparison_expenses_save',
   requestId:rid,
   currency:cmp,
   expenses,
   fxSnapshot:fxSnapshotNow(),
   idempotencyKey:makeIdempotencyKey()
 })){
   toast('Ek maliyetler kaydedildi.','good');
   setTimeout(()=>openRequestTab(rid,'cost'),50)
 }
}
function selectedComparisonSummary(r,cmp){
 const sups=requestSuppliers(r.id),selectedSupplierIds=new Set();
 let itemSubtotal=0,selectedItems=0,explicitSelected=0;
 (r.items||[]).forEach(it=>{
   const explicitSid=selectedSupplierId(r.id,it.id);
   let sup=explicitSid?sups.find(s=>Number(s.id)===Number(explicitSid)&&supplierTechnicalStatus(s,it.id)!=='unsuitable'):null;
   let usedExplicit=!!sup;
   if(!sup){
     const priced=sups.map(s=>{
       const raw=Number(supplierVatInfo(s,it.id).effective||0),tech=supplierTechnicalStatus(s,it.id);
       return raw>0&&tech!=='unsuitable'?{s,converted:supplierConvertedAmount(s,raw,cmp,false,r)}:null
     }).filter(Boolean).filter(x=>Number.isFinite(x.converted)&&x.converted>0);
     if(priced.length){
       priced.sort((a,b)=>a.converted-b.converted);
       sup=priced[0].s
     }
   }
   if(!sup)return;
   const raw=Number(supplierVatInfo(sup,it.id).effective||0);if(!(raw>0))return;
   const unit=supplierConvertedAmount(sup,raw,cmp,false,r);if(!(unit>0))return;
   itemSubtotal+=unit*Number(it.qty||0);
   selectedItems++;
   if(usedExplicit)explicitSelected++;
   selectedSupplierIds.add(Number(sup.id))
 });
 let supplierQuotedFreight=0;
 selectedSupplierIds.forEach(sid=>{
   const sup=sups.find(s=>Number(s.id)===Number(sid));if(!sup)return;
   const freight=Number(sup.freight||0);
   if(freight>0)supplierQuotedFreight+=supplierConvertedAmount(sup,freight,cmp,false,r)
 });
 return {
   selectedItems,
   explicitSelected,
   totalItems:(r.items||[]).length,
   supplierCount:selectedSupplierIds.size,
   itemSubtotal,
   supplierQuotedFreight,
   grandTotal:itemSubtotal
 }
}
function requestComparisonHtml(r){
 const sups=requestSuppliers(r.id);if(!sups.length)return `<div class="empty">Kıyaslama için önce tedarikçi teklifi girin.</div>`;
 const cmp=r.comparisonCurrency||r.currency||'EUR',summary=selectedComparisonSummary(r,cmp),supplierTotals=new Map(sups.map(s=>[Number(s.id),supplierComparisonTotals(r,s,cmp)]));
 const rows=r.items.map(it=>{
   const priced=sups.map(s=>{const vi=supplierVatInfo(s,it.id),tech=supplierTechnicalStatus(s,it.id);return {s,p:vi.effective,tech,cmp:supplierConvertedAmount(s,vi.effective,cmp,false,r)}}).filter(x=>x.p>0&&x.tech!=='unsuitable');
   const best=priced.length?Math.min(...priced.map(x=>x.cmp)):0;
   return `<tr><td class="compare-item-cell"><b>${it.name}</b><div class="sub">${formatQuantity(it.qty)} ${it.unit}</div></td>${sups.map(sup=>{
     const vi=supplierVatInfo(sup,it.id),p=Number(vi.effective||0),tech=supplierTechnicalStatus(sup,it.id),net=p?supplierConvertedAmount(sup,vi.net,cmp,false,r):0,gross=p?supplierConvertedAmount(sup,vi.gross,cmp,false,r):0,line=p?supplierConvertedAmount(sup,p*Number(it.qty||0),cmp,false,r):0,pc=p?supplierConvertedAmount(sup,p,cmp,false,r):0,sel=Number(selectedSupplierId(r.id,it.id))===Number(sup.id),isBest=p&&tech!=='unsuitable'&&Math.abs(pc-best)<0.000001;
     return `<td class="offer-cell compact-offer ${!p?'no-price':''} ${tech==='unsuitable'?'tech-unsuitable':tech==='conditional'?'tech-conditional':'tech-suitable'} ${isBest?'best':''} ${sel?'selected':''}" onclick="selectSupplierInRequest(${r.id},${it.id},${sup.id})">${p?`<div class="compare-basis-row"><span class="badge b-gray">Girilen: ${vi.mode==='included'?'KDV Dahil':'KDV Hariç'}</span><span class="badge b-blue">Kıyas: KDV Hariç</span></div><div class="compare-price-grid"><div><span>KDV Hariç</span><b>${money(net,cmp)}</b></div><div><span>KDV Dahil</span><b>${money(gross,cmp)}</b></div><div class="line-total"><span>KDV Hariç Kalem Toplamı</span><strong>${money(line,cmp)}</strong></div></div><div class="compare-cell-foot"><span class="technical-badge technical-${tech}">${supplierTechnicalLabel(tech)}</span><span class="comparison-state-badge ${sel?'is-selected':(isBest?'is-best':'')}">${sel&&isBest?'SEÇİLİ · EN UYGUN':sel?'SEÇİLİ':isBest?'EN UYGUN':''}</span></div>`:'<span class="no-price-label">Fiyat Yok</span>'}</td>`
   }).join('')}</tr>`
 }).join('');
 const supplierSummary=`<div class="supplier-total-strip">${sups.map(s=>{const t=supplierTotals.get(Number(s.id))||{net:0,gross:0,purchase:0,priced:0,totalItems:0};return `<div class="supplier-total-card"><div class="supplier-total-name"><b>${esc(s.name)}</b><small>${t.priced}/${t.totalItems} kalem fiyatlı</small></div><div class="supplier-total-values"><span>KDV Hariç Toplam <b>${money(t.net,cmp)}</b></span><span>KDV Dahil Toplam <b>${money(t.gross,cmp)}</b></span><span class="purchase-total">Toplam Alış <strong>${money(t.purchase,cmp)}</strong></span></div></div>`}).join('')}</div>`;
 return `<div class="integrated-compare-head"><div><h3>Kalem Bazlı Teklif Kıyaslama</h3><div class="sub">Girilen fiyatın KDV Dahil/Hariç durumu hücrede gösterilir. Otomatik “En Ucuz” seçimi ve Toplam Alış hesabı, adil karşılaştırma için <b>KDV Hariç (net)</b> fiyatı ve teklif tarihindeki kur snapshot’ını esas alır.</div></div><div class="integrated-compare-actions"><div class="compare-fx-toolbar"><span class="sub">Kıyas Para Birimi</span><select onchange="setRequestComparisonCurrency(${r.id},this.value)"><option ${cmp==='EUR'?'selected':''}>EUR</option><option ${cmp==='USD'?'selected':''}>USD</option><option ${cmp==='TRY'?'selected':''}>TRY</option></select></div><button class="btn" type="button" onclick="exportItemComparisonExcel(${r.id})">Excel İndir</button><button class="btn" onclick="autoBestInRequest(${r.id})">En Ucuzları Seç</button><button class="btn primary" onclick="openRequestTab(${r.id},'cost')">Kıyaslamayı Onayla → Maliyet</button></div></div>
 <div class="selected-best-summary compact-summary">
   <div class="selected-best-main"><span>Seçilen Genel Toplam</span><strong>${money(summary.itemSubtotal,cmp)}</strong><small>${summary.selectedItems}/${summary.totalItems} kalem · ${summary.supplierCount} tedarikçi</small></div>
   <div><span>Manuel Seçim</span><b>${summary.explicitSelected}</b><small>Seçili kalem sayısı</small></div>
   <div><span>Teklif Navlunu</span><b>${money(summary.supplierQuotedFreight,cmp)}</b><small>Bilgi amaçlı</small></div>
   <div><span>Para Birimi</span><b>${cmp}</b><small>Teklif kuru</small></div>
 </div>
 <details class="comparison-workspace"><summary><b>Detaylı Kıyaslama Tablosu</b><span class="sub">${sups.length} tedarikçi · ${r.items.length} kalem · Açmak için tıklayın</span></summary>${supplierSummary}<div class="compare-scroll"><table class="compare"><thead><tr><th>Talep Kalemi</th>${sups.map(s=>`<th>${esc(s.name)}<br><small>${supplierQuoteCurrency(s,r)} · ${esc(s.payment||'—')}</small></th>`).join('')}</tr></thead><tbody>${rows}</tbody></table></div></details>${hasFullComparison(r)?'<div class="workflow-complete" style="margin-top:10px">Tüm kalemlerde tedarikçi seçimi tamamlandı.</div>':'<div class="inline-note" style="margin-top:10px">Müşteri teklifi oluşturmak için her kalemde bir tedarikçi seçin.</div>'}`;
}
async function selectSupplierInRequest(rid,item,supplier){
 const r=state.requests.find(x=>x.id===rid);if(!r)return;if(requestOrder(r))return toast('Siparişe dönüşmüş bir talepte kıyaslama doğrudan değiştirilemez.','warn');
 const candidate=requestSuppliers(rid).find(s=>Number(s.id)===Number(supplier));
 if(candidate&&supplierTechnicalStatus(candidate,item)==='unsuitable')return toast('Teknik olarak Uygun Değil işaretlenen teklif seçilemez.','warn');
 const current=selectedSupplierId(rid,item),next=current===supplier?0:supplier;
 const y=window.scrollY,card=document.querySelector(`.request-card[data-rid="${rid}"]`),open=card?.classList.contains('open');
 if(await runLockedServerOperation('quote',rid,{action:'comparison_select',requestId:rid,itemId:item,supplierId:next,idempotencyKey:makeIdempotencyKey()})){requestAnimationFrame(()=>{const c=document.querySelector(`.request-card[data-rid="${rid}"]`);if(open)c?.classList.add('open');const btn=[...c?.querySelectorAll('.tab')||[]].find(x=>x.textContent.includes('Kıyaslama'));if(btn)reqTab(rid,'comparison',btn);window.scrollTo({top:y,behavior:'instant'})})}
}
async function autoBestInRequest(rid){
 const r=state.requests.find(x=>Number(x.id)===Number(rid));if(!r)return;
 if(requestOrder(r))return toast('Siparişe dönüşmüş talepte kıyaslama değiştirilemez.','warn');
 const card=document.querySelector(`.request-card[data-rid="${rid}"]`);
 const y=window.scrollY,open=card?.classList.contains('open');
 if(await runLockedServerOperation('quote',rid,{action:'comparison_auto_best',requestId:rid,idempotencyKey:makeIdempotencyKey()})){
   requestAnimationFrame(()=>{
     const c=document.querySelector(`.request-card[data-rid="${rid}"]`);if(open)c?.classList.add('open');
     const btn=[...c?.querySelectorAll('.tab')||[]].find(x=>x.textContent.includes('Kıyaslama'));if(btn)reqTab(rid,'comparison',btn);
     window.scrollTo({top:y,behavior:'instant'})
   });
   toast('En uygun tedarikçiler KDV Hariç net fiyat ve teklif kuru ile seçildi.','good')
 }
}
function costServiceAccounts(){
 return groupedAccounts().filter(g=>['supplier','customs','freight','service'].includes(g.type))
}
function requestCostLines(r){if(!Array.isArray(r.costLines))r.costLines=[];return r.costLines}
function costLinePayments(r,line){return comparisonExpenseRealizedRows(r).filter(x=>String(x.plannedCostLineId||'')===String(line?.id||'')&&!x.reversed)}
function costLinePaidAmount(r,line){return costLinePayments(r,line).reduce((n,x)=>{const cur=String(x.currency||line.currency||'EUR').toUpperCase(),target=String(line.currency||cur).toUpperCase(),v=cur===target?Number(x.amount||0):convertCurrency(Number(x.amount||0),cur,target,x.fxSnapshot||state.fx);return n+(Number.isFinite(v)?v:0)},0)}
function costLineRemaining(r,line){return Math.max(0,Number(line?.amount||0)-costLinePaidAmount(r,line))}
function expenseAttachmentOptions(rid,selected=0){const rows=(state.requestAttachments||[]).filter(x=>Number(x.requestId)===Number(rid));return '<option value="">Evrak seçilmedi</option>'+rows.map(x=>`<option value="${x.id}" ${Number(selected)===Number(x.id)?'selected':''}>${esc(x.name||'Evrak')} · ${esc(x.categoryLabel||x.category||'Diğer')}</option>`).join('')}
function openExpenseAttachmentUpload(){const rid=Number(document.getElementById('cerRequestId')?.value||0);if(!rid)return;openAttachmentUpload(rid)}

function requestRawCost(r,target=r.currency||'EUR'){
 const cmp=r.comparisonCurrency||r.currency||'EUR',sum=selectedComparisonSummary(r,cmp).itemSubtotal;
 if(cmp===target)return sum;const v=convertCurrency(sum,cmp,target);return Number.isFinite(v)?v:0
}
function requestPlannedFixedCost(r,target=r.currency||'EUR'){
 // V3.11.1: Eski EK MALİYETLER/Operasyon Giderleri alanı kaldırıldı.
 // Legacy comparisonExpenses verisi arşiv uyumluluğu için state'te kalabilir fakat maliyete dahil edilmez.
 return 0
}
function requestManualCostLinesTotal(r,target=r.currency||'EUR'){
 return requestCostLines(r).reduce((n,x)=>{const amount=Number(x.amount||0),cur=String(x.currency||r.comparisonCurrency||r.currency||'EUR').toUpperCase();if(!(amount>0))return n;const v=cur===target?amount:convertCurrency(amount,cur,target,x.fxSnapshot||r.costFxSnapshot||state.fx);return n+(Number.isFinite(v)?v:0)},0)
}
function requestExtraCost(r,target=r.currency||'EUR'){return requestManualCostLinesTotal(r,target)}
function requestLandedCost(r,target=r.currency||'EUR'){return requestRawCost(r,target)+requestExtraCost(r,target)}
function requestCostHtml(r){
 const cmp=r.comparisonCurrency||r.currency||'EUR',lines=requestCostLines(r),raw=requestRawCost(r,cmp),
       manualTotal=requestManualCostLinesTotal(r,cmp),extra=manualTotal,landed=raw+extra,
       parties=costServiceAccounts(),realized=comparisonExpenseRealizedRows(r),realizedTotal=comparisonExpenseRealizedTotal(r,cmp),
       canRealize=r.status==='won'||!!requestOrder(r);
 const expenseRows=lines.length?lines.map((x,i)=>{
   const pays=costLinePayments(r,x),rx=pays[pays.length-1],paid=costLinePaidAmount(r,x),remaining=costLineRemaining(r,x),isDone=remaining<=0.005,isPartial=paid>0&&!isDone;
   const party=parties.find(g=>String(g.partyKey)===String(x.partyId||''));
   return `<tr class="${isDone?'is-realized':isPartial?'is-partial':''}">
    <td>${esc(rx?.date||'—')}</td>
    <td><b>${esc(x.category||'Diğer')}</b><div class="sub">${esc(x.invoiceNo||'')}</div></td>
    <td>${esc(rx?.partyName||party?.name||'—')}</td>
    <td>${esc(rx?.bankName||'—')}</td>
    <td><b>${money(Number(x.amount||0),x.currency||cmp)}</b><div class="sub">Ödenen ${money(paid,x.currency||cmp)} · Kalan ${money(remaining,x.currency||cmp)}</div></td>
    <td>${rx?`<b>${money(Number(rx.paymentAmount||0),rx.paymentCurrency||rx.currency||cmp)}</b>`:'—'}</td>
    <td>${rx?`<b>${money(Number(rx.reportAmount ?? rx.amount ?? 0),rx.reportCurrency||r.currency||cmp)}</b>`:'—'}</td>
    <td>${rx?`<small>${esc(rx.fxRateSource==='manual'?'Manuel/Banka':'TCMB')}<br>1 ${esc(rx.currency||'')} = ${reportFmtNum(Number(rx.usedPaymentRate||1))} ${esc(rx.paymentCurrency||rx.currency||'')}</small>`:'—'}</td>
    <td>${esc(x.invoiceDate||'—')}<div class="sub">Vade ${esc(x.dueDate||'—')}</div></td>
    <td>${x.attachmentId?`<button class="btn sm" onclick="viewRequestAttachment(${Number(x.attachmentId)})">Evrak</button>`:'—'}</td>
    <td>${esc(rx?.ref||'—')}</td>
    <td>${esc(x.description||rx?.description||'')}</td>
    <td><span class="badge ${isDone?'b-green':isPartial?'b-yellow':'b-gray'}">${isDone?'Ödendi':isPartial?'Kısmi':'Bekliyor'}</span><div class="actions" style="margin-top:4px">${canRealize&&remaining>0.005?`<button class="btn primary sm" onclick="openCostLineRealization(${r.id},${i})">Ödeme Yap</button>`:''}${pays.length?`<button class="btn red sm" onclick="reverseCostLinePayments(${r.id},${i})">Tersle</button>`:''}</div></td>
   </tr>`
 }).join(''):'<tr><td colspan="13"><div class="empty">Henüz gider kaydı yok. “Diğer Giderler” bölümünden gider ekleyin.</div></td></tr>';
 return `<div class="cost-workspace">
  <div class="panel-head"><div><h3>Maliyet</h3><div class="sub">Planlanan giderleri tek listede yönetin. Teminat ayrı Teminat bölümündedir; eski “Ek Maliyetler / Operasyon Giderleri” alanı kaldırılmıştır.</div></div></div>
  <div class="selected-best-summary cost-summary">
   <div><span>Ham Alış</span><b>${money(raw,cmp)}</b><small>KDV Hariç seçili tedarikçi maliyeti</small></div>
   <div><span>Diğer Giderler</span><b id="comparisonExpenseTotal-${r.id}">${money(extra,cmp)}</b><small>Manuel gider kalemleri</small></div>
   <div class="selected-best-main"><span>Giderler Dahil Maliyet</span><strong id="comparisonGrandTotal-${r.id}">${money(landed,cmp)}</strong><small>Müşteri teklifinde kullanılabilir</small></div>
  </div>

  <section class="cost-section">
   <div class="cost-section-head"><div><span class="eyebrow">DİĞER GİDERLER</span><h4>Manuel Gider Kalemleri</h4><p>Gider türünü, cari/hizmet sağlayıcıyı, açıklamayı ve tutarı kaydedin. Kaydedilen satırlar aşağıdaki Gerçek Ödemeler tablosunda bekleyen gider olarak görünür; kısmi veya tam ödeme yapılabilir.</p></div><button class="btn primary sm" type="button" onclick="addCostLine(${r.id})">+ Gider Kalemi Ekle</button></div>
   <div class="cost-lines">${lines.length?lines.map((x,i)=>{const hasPay=costLinePayments(r,x).length>0;return `<div class="cost-line-row cost-line-rich"><input value="${escAttr(x.category||'')}" placeholder="Gider türü" onchange="updateCostLine(${r.id},${i},'category',this.value)"><select onchange="updateCostLine(${r.id},${i},'partyId',this.value)"><option value="">Cari / Hizmet Sağlayıcı</option>${parties.map(g=>`<option value="${escAttr(g.partyKey)}" ${String(x.partyId||'')===String(g.partyKey)?'selected':''}>${g.name} · ${accountTypeLabel(g.type)}</option>`).join('')}</select><input value="${escAttr(x.invoiceNo||'')}" placeholder="Fatura / Belge No" onchange="updateCostLine(${r.id},${i},'invoiceNo',this.value)"><input type="date" value="${escAttr(x.invoiceDate||'')}" title="Fatura tarihi" onchange="updateCostLine(${r.id},${i},'invoiceDate',this.value)"><input type="date" value="${escAttr(x.dueDate||'')}" title="Vade tarihi" onchange="updateCostLine(${r.id},${i},'dueDate',this.value)"><select onchange="updateCostLine(${r.id},${i},'attachmentId',this.value)">${expenseAttachmentOptions(r.id,x.attachmentId)}</select><input value="${escAttr(x.description||'')}" placeholder="Açıklama" onchange="updateCostLine(${r.id},${i},'description',this.value)"><input type="text" inputmode="decimal" value="${formatMoneyNumber(x.amount||0)}" placeholder="Tutar" onchange="updateCostLine(${r.id},${i},'amount',this.value)"><select onchange="updateCostLine(${r.id},${i},'currency',this.value)">${['EUR','USD','TRY'].map(c=>`<option ${c===(x.currency||cmp)?'selected':''}>${c}</option>`).join('')}</select><button class="btn red sm" type="button" onclick="removeCostLine(${r.id},${i})" ${hasPay?'disabled title="Ödeme geçmişi olan gider silinemez; önce ödemeleri tersleyin."':''}>Sil</button></div>`}).join(''):'<div class="empty">Henüz manuel gider kalemi yok.</div>'}</div>
   <div class="cost-section-actions"><button class="btn" type="button" onclick="openCostStandaloneAccountCreate(${r.id})">+ Cari Hesap / Hizmet Sağlayıcı Ekle</button><button class="btn primary" type="button" onclick="saveCostLines(${r.id},false)">Giderleri Kaydet</button></div>
  </section>

  <section class="cost-section realized-cost-section">
   <div class="cost-section-head"><div><span class="eyebrow">GERÇEKLEŞEN OPERASYON GİDERLERİ</span><h4>Gerçek Ödemeler</h4><p>Kaydedilen giderler burada izlenir. Ödeme Yap ile kısmi veya tam ödeme yapabilirsiniz. Cari ve Kasa/Banka seçilmeden finans hareketi oluşmaz; Tersle ile ilgili giderin aktif ödemeleri ters kayıtla geri alınır.</p></div>${canRealize?'<span class="badge b-green">Finans işlemleri aktif</span>':'<span class="badge b-gray">Kazanılan / siparişe dönüşen işte finans işlemi aktif olur</span>'}</div>
   <div class="cost-section-total"><span>Gerçekleşen Gider Toplamı (${cmp})</span><b>${money(realizedTotal,cmp)}</b></div>
   <div class="table-wrap"><table class="compact-table cost-realized-table"><thead><tr><th>Son Ödeme</th><th>Gider / Fatura No</th><th>Cari / Hizmet Sağlayıcı</th><th>Kasa / Banka</th><th>Gerçek Gider</th><th>Son Kasa Çıkışı</th><th>Son Operasyon Karşılığı</th><th>Kur</th><th>Fatura / Vade</th><th>Evrak</th><th>Referans</th><th>Açıklama</th><th>Durum</th></tr></thead><tbody>${expenseRows}</tbody></table></div>
  </section>
  <div class="actions"><button class="btn" type="button" onclick="openRequestTab(${r.id},'comparison')">← Kıyaslamaya Dön</button><button class="btn primary" type="button" onclick="saveCostWorkspace(${r.id},true)">Maliyeti Kaydet → Müşteri Teklifi</button></div>
 </div>`
}
function openCostStandaloneAccountCreate(rid){
 const r=state.requests.find(x=>Number(x.id)===Number(rid));if(!r)return;
 const cur=String(r.comparisonCurrency||r.currency||'EUR').toUpperCase();
 costQuickAccountContext={rid,returnTo:'cost'};
 openAccountCreateModal('',cur);
 document.getElementById('accNewType').value='service';
 document.getElementById('accNewCurrency').value=cur;
 document.getElementById('accNewRequest').value=r.no||'';
 document.getElementById('accountCreateTitle').textContent='Yeni Cari Hesap / Hizmet Sağlayıcı Ekle';
 document.getElementById('accountCreateSub').textContent='Kaydettiğiniz cari Maliyet sayfasındaki gider kalemlerinde hemen seçilebilir.'
}
function addCostLine(rid){const r=state.requests.find(x=>Number(x.id)===Number(rid));if(!r)return;const cmp=r.comparisonCurrency||r.currency||'EUR';requestCostLines(r).push({id:'cost-'+Date.now()+'-'+Math.random().toString(36).slice(2,6),category:'',partyId:'',description:'',invoiceNo:'',invoiceDate:'',dueDate:'',attachmentId:0,amount:0,currency:cmp});renderRequests();setTimeout(()=>openRequestTab(rid,'cost'),20)}
function updateCostLine(rid,index,key,value){const r=state.requests.find(x=>Number(x.id)===Number(rid)),line=r?.costLines?.[index];if(!line)return;line[key]=key==='amount'?parseMoneyInput(value):key==='attachmentId'?Number(value||0):value}
function removeCostLine(rid,index){const r=state.requests.find(x=>Number(x.id)===Number(rid));if(!r)return;const line=r.costLines?.[index];if(costLinePayments(r,line).length)return toast('Ödeme geçmişi olan gider silinemez. Önce tüm ödemeleri tersleyin.','warn');r.costLines.splice(index,1);renderRequests();setTimeout(()=>openRequestTab(rid,'cost'),20)}
async function saveCostLines(rid,continueToQuote=false){
 const r=state.requests.find(x=>Number(x.id)===Number(rid));if(!r)return;
 const oldById=new Map((r.costLines||[]).map(x=>[String(x.id||''),x]));
 const lines=requestCostLines(r).map(x=>({id:x.id||'',category:String(x.category||'').trim(),partyId:String(x.partyId||''),description:String(x.description||'').trim(),amount:Math.max(0,Number(x.amount||0)),currency:String(x.currency||r.comparisonCurrency||r.currency||'EUR').toUpperCase(),realizedJournalId:Number(x.realizedJournalId||0)||0,invoiceNo:String(x.invoiceNo||''),invoiceDate:String(x.invoiceDate||''),dueDate:String(x.dueDate||''),attachmentId:Number(x.attachmentId||0)})).filter(x=>x.category||x.description||x.amount>0);
 if(await runLockedServerOperation('quote',rid,{action:'cost_lines_save',requestId:rid,lines,fxSnapshot:fxSnapshotNow(),idempotencyKey:makeIdempotencyKey()})){
   toast('Giderler kaydedildi ve Gerçek Ödemeler tablosu güncellendi.','good');
   setTimeout(()=>openRequestTab(rid,continueToQuote?'customer':'cost'),50)
 }
}
function openCostLineRealization(rid,index){
 const r=state.requests.find(x=>Number(x.id)===Number(rid)),line=r?.costLines?.[index];if(!r||!line)return;
 if(!(r.status==='won'||requestOrder(r)))return toast('Gideri Gerçekleşti olarak işaretlemek için talep kazanılmış veya siparişe dönüşmüş olmalıdır.','warn');
 realizingCostLineId=String(line.id||'');
 openComparisonExpenseRealize(rid,'other');
 const cur=String(line.currency||r.comparisonCurrency||r.currency||'EUR').toUpperCase();
 document.getElementById('cerCurrency').value=cur;
 document.getElementById('cerReportCurrency').value=String(r.currency||cur).toUpperCase();
 document.getElementById('cerFxMode').value='tcmb';
 document.getElementById('cerManualRate').value='';
 document.getElementById('cerCustomCategory').value=line.category||'Diğer';
 document.getElementById('cerAmount').value=formatUnitPriceDisplay(String(costLineRemaining(r,line)||0));document.getElementById('cerInvoiceNo').value=line.invoiceNo||'';document.getElementById('cerInvoiceDate').value=line.invoiceDate||'';document.getElementById('cerDueDate').value=line.dueDate||'';document.getElementById('cerAttachment').innerHTML=expenseAttachmentOptions(r.id,line.attachmentId);document.getElementById('cerAttachment').value=String(line.attachmentId||'');
 document.getElementById('cerDescription').value=line.description||`${line.category||'Diğer'} gerçek gideri`;
 document.getElementById('cerRef').value=(r.no||'TALEP')+'-'+String(line.category||'DIGER').toUpperCase().replace(/[^A-Z0-9ÇĞİÖŞÜ_-]+/g,'-');
 const acc=(state.accounts||[]).find(a=>String(a.partyKey||'')===String(line.partyId||'')&&String(a.currency||'').toUpperCase()===cur);
 refreshComparisonExpensePartySelect(acc?.id||('pk:'+String(line.partyId||'')));
 syncComparisonExpenseRealizeBanks();
}
async function reverseCostLinePayments(rid,index){const r=state.requests.find(x=>Number(x.id)===Number(rid)),line=r?.costLines?.[index];if(!r||!line)return;const pays=costLinePayments(r,line);if(!pays.length)return;if(!await appConfirm(`${pays.length} ödeme kaydı ters kayıt ile geri alınsın mı?`,'Gider Ödemelerini Tersle'))return;for(const rx of pays.slice().reverse()){if(rx.journalId){const ok=await runServerOperation({action:'reverse_finance',journalId:Number(rx.journalId),idempotencyKey:makeIdempotencyKey()});if(!ok)return}}toast('Gider ödemeleri ters kayıtla kapatıldı.','good');setTimeout(()=>openRequestTab(rid,'cost'),80)}
async function setCostLineRealizationStatus(rid,index,status){const r=state.requests.find(x=>Number(x.id)===Number(rid)),line=r?.costLines?.[index];if(!r||!line)return;if(status==='realized')return openCostLineRealization(rid,index);if(status==='pending')return reverseCostLinePayments(rid,index)}
async function saveCostWorkspace(rid,continueToQuote=false){
 const r=state.requests.find(x=>Number(x.id)===Number(rid));if(!r)return;
 if(requestOrder(r))return toast('Siparişe dönüşmüş talepte planlanan maliyetler değiştirilemez. Gerçekleşen gider durumunu kullanın.','warn');
 const cmp=r.comparisonCurrency||r.currency||'EUR';
 const lines=requestCostLines(r).map(x=>({id:x.id||'',category:String(x.category||'').trim(),partyId:String(x.partyId||''),description:String(x.description||'').trim(),amount:Math.max(0,Number(x.amount||0)),currency:String(x.currency||cmp).toUpperCase(),realizedJournalId:Number(x.realizedJournalId||0)||0,invoiceNo:String(x.invoiceNo||''),invoiceDate:String(x.invoiceDate||''),dueDate:String(x.dueDate||''),attachmentId:Number(x.attachmentId||0)})).filter(x=>x.category||x.description||x.amount>0);
 const okLines=await runLockedServerOperation('quote',rid,{action:'cost_lines_save',requestId:rid,lines,fxSnapshot:fxSnapshotNow(),idempotencyKey:makeIdempotencyKey()});
 if(!okLines)return;
 toast('Maliyet bilgileri kaydedildi.','good');
 setTimeout(()=>openRequestTab(rid,continueToQuote?'customer':'cost'),60)
}
async function setQuoteCostMode(rid,mode){if(await runLockedServerOperation('quote',rid,{action:'quote_cost_mode',requestId:rid,mode,idempotencyKey:makeIdempotencyKey()}))setTimeout(()=>openRequestTab(rid,'customer'),30)}

function downloadComparisonExcelFallback(headers,rows,title){
 const esc=v=>String(v??'').replaceAll('&','&amp;').replaceAll('<','&lt;').replaceAll('>','&gt;');
 const html=`<html><head><meta charset="UTF-8"></head><body><table border="1"><thead><tr>${headers.map(h=>`<th>${esc(h)}</th>`).join('')}</tr></thead><tbody>${rows.map(row=>`<tr>${headers.map((_,i)=>`<td>${esc(row?.[i]??'')}</td>`).join('')}</tr>`).join('')}</tbody></table></body></html>`;
 const blob=new Blob(['\ufeff',html],{type:'application/vnd.ms-excel;charset=utf-8'}),url=URL.createObjectURL(blob),a=document.createElement('a');a.href=url;a.download=title+'_'+todayISO()+'.xls';document.body.appendChild(a);a.click();a.remove();setTimeout(()=>URL.revokeObjectURL(url),1500);toast('XLSX üretilemediği için Excel uyumlu XLS dosyası indirildi.','warn')
}
async function exportItemComparisonExcel(rid){
 const r=state.requests.find(x=>Number(x.id)===Number(rid));
 if(!r)return toast('Talep bulunamadı.','warn');
 const sups=requestSuppliers(r.id);
 if(!sups.length)return toast('Excel kıyaslama için en az bir tedarikçi teklifi gerekli.','warn');
 const cmp=String(r.comparisonCurrency||r.currency||'EUR').toUpperCase();
 const headers=[
   'Sıra','Talep No','Konu Başlığı','Müşteri / Kurum','Kalem','Miktar','Birim',
   'Tedarikçi','Teklif Ref.','Teklif Para Birimi','Girilen Birim Fiyat',
   'KDV Durumu','KDV %','KDV Dahil Karşılık','Kıyas Para Birimi',
   'Kıyaslanan Birim Fiyat','Kalem Toplamı','Teknik Uygunluk','Kıyaslama Durumu',
   'Termin (Gün)','Ödeme','Tedarikçi Navlunu','Kur Snapshot Tarihi'
 ];
 const rows=[];let seq=1;
 (r.items||[]).forEach(it=>{
   const priced=sups.map(s=>{
     const vi=supplierVatInfo(s,it.id),tech=supplierTechnicalStatus(s,it.id);
     const converted=Number(vi.effective||0)>0&&tech!=='unsuitable'?supplierConvertedAmount(s,vi.effective,cmp,false,r):0;
     return {s,vi,tech,converted}
   }).filter(x=>Number(x.vi.effective||0)>0&&x.tech!=='unsuitable'&&Number.isFinite(x.converted)&&x.converted>0);
   const best=priced.length?Math.min(...priced.map(x=>x.converted)):0;
   sups.forEach(sup=>{
     const vi=supplierVatInfo(sup,it.id),raw=Number(vi.effective||0),tech=supplierTechnicalStatus(sup,it.id),
           cur=supplierQuoteCurrency(sup,r),pc=raw>0?supplierConvertedAmount(sup,raw,cmp,false,r):0,
           selected=Number(selectedSupplierId(r.id,it.id))===Number(sup.id),
           isBest=raw>0&&tech!=='unsuitable'&&best>0&&Math.abs(pc-best)<0.000001,
           status=selected&&isBest?'SEÇİLİ · EN UYGUN':selected?'SEÇİLİ':isBest?'EN UYGUN':'',
           snap=supplierSnapshot(sup);
     rows.push([
       seq++,r.no||'',r.title||'',r.customer||'',it.name||'',Number(it.qty||0),it.unit||'',
       sup.name||'',sup.ref||'',cur,raw>0?Number(vi.entered||0):'',
       raw>0?(vi.mode==='included'?'KDV Dahil':'KDV Hariç'):'',raw>0?Number(vi.rate||0):'',
       raw>0?Number(vi.gross||0):'',cmp,raw>0?Number(pc||0):'',
       raw>0?Number(pc||0)*Number(it.qty||0):'',supplierTechnicalLabel(tech),status,
       Number(sup.delivery||0),sup.payment||'',Number(sup.freight||0),snap.updated||''
     ])
   })
 });
 const summary=selectedComparisonSummary(r,cmp),expTotal=requestExtraCost(r,cmp),grand=summary.itemSubtotal+expTotal;
 rows.push([]);
 rows.push(['ÖZET','','','','Seçilen Kalemler Toplamı','','','','','','','','','','',Number(summary.itemSubtotal||0),'','','','','','','']);
 rows.push(['ÖZET','','','','Tedarikçi Teklif Navlunu (Bilgi)','','','','','','','','','','',Number(summary.supplierQuotedFreight||0),'','','','','','','']);
 requestCostLines(r).forEach(x=>rows.push(['ÖZET','','','',`Gider · ${x.category||x.description||'Diğer'}`,'','','','','','','','','','',Number((String(x.currency||cmp).toUpperCase()===cmp?Number(x.amount||0):convertCurrency(Number(x.amount||0),x.currency,cmp))||0),'','','','','','','']));
 rows.push(['ÖZET','','','','Giderler Toplamı','','','','','','','','','','',Number(expTotal||0),'','','','','','','']);
 rows.push(['ÖZET','','','','GENEL TUTAR','','','','','','','','','','',Number(grand||0),'','','','','','','']);
 const title=`${r.no||'RFQ'}_Kalem_Bazli_Teklif_Kiyaslama`;
 try{
   const res=await fetch('api/export_xlsx.php',{
     method:'POST',
     headers:{'Content-Type':'application/json','Accept':'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'},
     body:JSON.stringify({title,headers,rows,columnWidths:[7,16,30,24,30,11,10,26,18,14,17,14,10,17,14,18,18,16,18,13,22,16,20]})
   });
   if(!res.ok){
     const raw=await res.text();let msg='Excel oluşturulamadı.';
     try{const j=JSON.parse(raw);msg=j.error||msg}catch(_e){if(raw)msg=raw.replace(/<[^>]+>/g,' ').replace(/\s+/g,' ').trim().slice(0,220)||msg}
     throw new Error(msg)
   }
   const blob=await res.blob(),cd=res.headers.get('Content-Disposition')||'',ct=(res.headers.get('Content-Type')||'').toLowerCase();
   if(blob.size<700||(!ct.includes('spreadsheet')&&!ct.includes('octet-stream')&&!ct.includes('zip'))){downloadComparisonExcelFallback(headers,rows,title);return}
   const sig=new Uint8Array(await blob.slice(0,2).arrayBuffer());if(sig[0]!==0x50||sig[1]!==0x4b){downloadComparisonExcelFallback(headers,rows,title);return}
   const m=cd.match(/filename="?([^";]+)"?/i),name=m?.[1]||`${title}.xlsx`;
   const url=URL.createObjectURL(blob),a=document.createElement('a');
   a.href=url;a.download=name;document.body.appendChild(a);a.click();a.remove();setTimeout(()=>URL.revokeObjectURL(url),1500);
   toast(`Kalem bazlı teklif kıyaslama Excel olarak indirildi (${rows.length} satır).`,'good')
 }catch(e){toast(e?.message||String(e),'warn')}
}
function openRequestComparison(rid){go('requests');setTimeout(()=>openRequestTab(rid,'comparison'),70)}
function openRequestTab(rid,tab){
 if(!document.getElementById('view-requests').classList.contains('active'))go('requests');
 const card=document.querySelector(`.request-card[data-rid="${rid}"]`);if(!card)return;
 card.classList.add('open');
 const names={items:'Talep Kalemleri',suppliers:'Tedarikçi Teklifleri',comparison:'Kıyaslama',cost:'Maliyet',guarantee:'Teminat',customer:'Müşteri Teklifi',order:'Sipariş',docs:'Evraklar'};
 const btn=[...card.querySelectorAll('.tab')].find(x=>x.textContent.includes(names[tab]||tab));
 if(btn)reqTab(rid,tab,btn);
 card.scrollIntoView({behavior:'smooth',block:'center'})
}
async function approveComparisonAndCreateQuote(rid,mode='raw'){
 const r=state.requests.find(x=>x.id===rid);if(requestOrder(r))return toast('Siparişe dönüşmüş talepte yeni kıyaslama onayı verilemez.','warn');if(!hasFullComparison(r))return toast('Her talep kaleminde tedarikçi seçimi tamamlanmalı.','warn');
 if(await runLockedServerOperation('quote',rid,{action:'customer_quote_create',requestId:rid,costMode:mode,idempotencyKey:makeIdempotencyKey()})){activeQuoteRequestId=rid;const nr=state.requests.find(x=>x.id===rid);toast((nr?.customerQuote?.no||'Müşteri teklifi')+' transaction ile oluşturuldu/güncellendi.','good');setTimeout(()=>openRequestTab(rid,'customer'),70)}
}
async function clientAudit(action,entityType,entityId,payload={}){try{await fetch('api/client_audit.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action,entity_type:entityType,entity_id:entityId,payload})})}catch(e){}}
window.addEventListener('error',e=>{clientAudit('client_js_error','ui','window',{message:e.message,source:e.filename,line:e.lineno,col:e.colno}).catch(()=>{})});
window.addEventListener('unhandledrejection',e=>{clientAudit('client_unhandled_rejection','ui','promise',{reason:String(e.reason?.message||e.reason||'unknown')}).catch(()=>{})});
async function runLockedServerOperation(type,id,payload){
 const slot='inline-'+type+'-'+id+'-'+Date.now();
 if(!await acquireEntityLock(type,id,slot))return false;
 try{return await runServerOperation(payload)}finally{await releaseEntityLock(slot)}
}
async function reconcileUncertainServerOperation(payload,requestId=''){
 try{
  const key=String(payload?.idempotencyKey||'').trim();
  if(key){
   const r=await fetch('api/operation_status.php?key='+encodeURIComponent(key),{cache:'no-store',headers:{'Accept':'application/json'}}),raw=await r.text();let j=null;try{j=JSON.parse(raw)}catch(_e){}
   if(r.ok&&j?.ok&&j.committed){
    if(window.acceptServerState&&j.state)window.acceptServerState(j.state,j.revision,j.keyHashes||{});else await window.loadDbState?.();
    window.rerenderAll?.();toast('İşlem sunucuda kaydedilmiş. Ekran güncel sunucu bakiyesiyle uzlaştırıldı.','good');
    clientAudit('uncertain_operation_reconciled','operation',payload?.action||'',{requestId,key}).catch(()=>{});return true
   }
  }
  await window.loadDbState?.();return false
 }catch(e){console.warn('Belirsiz işlem uzlaştırması başarısız',e);try{await window.loadDbState?.()}catch(_e){}return false}
}
async function runServerOperation(payload){
 const execute=async()=>{let res=null,raw='';try{
  const requestId='op-'+Date.now().toString(36)+'-'+Math.random().toString(36).slice(2,7);
  res=await fetch('api/operations.php',{method:'POST',headers:{'Content-Type':'application/json','Accept':'application/json','X-ASAY-Request-ID':requestId},body:JSON.stringify(payload)});raw=await res.text();let j=null;
  try{j=raw?JSON.parse(raw):null}catch(parseErr){
   const plain=String(raw||'').replace(/<script[\s\S]*?<\/script>/gi,' ').replace(/<style[\s\S]*?<\/style>/gi,' ').replace(/<[^>]+>/g,' ').replace(/\s+/g,' ').trim();
   const loginHtml=/ASAY ERP Giriş|name=["']email["']/i.test(raw||'');
   const msg=loginHtml?'Oturum süresi dolmuş olabilir. Sayfayı yenileyip tekrar giriş yapın.':`Sunucu geçerli JSON döndürmedi (HTTP ${res.status}).`;
   const detail=`İşlem: ${payload?.action||'bilinmiyor'}\nHTTP: ${res.status} ${res.statusText||''}\nContent-Type: ${res.headers.get('content-type')||'—'}\nİstek ID: ${requestId}\nYanıt özeti: ${plain.slice(0,1200)||'Boş yanıt'}`;
   showServerError('Sunucu / JSON Hatası',msg,detail);clientAudit('server_json_parse_error','operation',payload?.action||'',{http:res.status,requestId,excerpt:plain.slice(0,500)}).catch(()=>{});const reconciled=await reconcileUncertainServerOperation(payload,requestId);return reconciled
  }
  if(!res.ok||!j?.ok){const msg=j?.error||`İşlem başarısız (HTTP ${res.status})`;if(/bakiye|hesap kimliği|Kasa\/Banka/i.test(msg)){try{await window.loadDbState?.();window.rerenderAll?.()}catch(_e){}}if(res.status>=500)showServerError('Sunucu İşlem Hatası',msg,`İşlem: ${payload?.action||'bilinmiyor'}\nHTTP: ${res.status}\nYanıt: ${String(raw||'').slice(0,1200)}`);throw new Error(msg)}
  if(window.acceptServerState)window.acceptServerState(j.state||{},j.revision,j.keyHashes||{});else{Object.keys(state).forEach(k=>delete state[k]);Object.assign(state,j.state||{});ensureRequestUnits();dbRevision=Number(j.revision||dbRevision)}window.rerenderAll?.();return true
 }catch(e){console.error('Sunucu operasyon hatası',payload?.action,e);if(!res){const reconciled=await reconcileUncertainServerOperation(payload);if(reconciled)return true}toast(e?.message||String(e),'warn');return false}};
 return window.runAuthoritativeDbMutation?await window.runAuthoritativeDbMutation(execute):await execute()
}
async function updateQuoteSale(rid,itemId,value,itemUid=''){const key=String(itemUid||`item-${itemId}`),input=document.querySelector(`.quote-sale-input[data-uid=\"${CSS.escape(key)}\"]`);if(input)input.disabled=true;try{if(await runLockedServerOperation('quote',rid,{action:'quote_item',requestId:rid,itemId,itemUid:key.startsWith('item-')?'':key,value:parseMoneyInput(value),idempotencyKey:makeIdempotencyKey()}))openRequestTab(rid,'customer')}finally{if(input&&document.body.contains(input))input.disabled=false}}
async function updateQuoteTotal(rid,value){if(await runLockedServerOperation('quote',rid,{action:'quote_total',requestId:rid,value:parseMoneyInput(value),idempotencyKey:makeIdempotencyKey()}))setTimeout(()=>openRequestTab(rid,'customer'),30)}
function requestCustomerQuoteHtml(r){const q=r.customerQuote;if(!q){const cmp=r.comparisonCurrency||r.currency||'EUR',raw=requestRawCost(r,cmp),landed=requestLandedCost(r,cmp);return `<div class="request-quote-card"><div class="panel-head"><div><h3>Müşteri Teklifi Hazırlama</h3><div class="sub">Teklif maliyet tabanını seçin; sonra müşteri teklifini oluşturun.</div></div></div><div class="selected-best-summary"><div><span>Ham Alış</span><b>${money(raw,cmp)}</b></div><div><span>Giderler Dahil</span><b>${money(landed,cmp)}</b></div></div><div class="actions" style="margin-top:12px"><button class="btn primary" onclick="approveComparisonAndCreateQuote(${r.id},'raw')">Ham Alış ile Teklif Oluştur</button><button class="btn primary" onclick="approveComparisonAndCreateQuote(${r.id},'landed')">Giderler Dahil Teklif Oluştur</button><button class="btn" onclick="openRequestTab(${r.id},'cost')">← Maliyete Dön</button></div></div>`;} const margin=q.total?q.profit/q.total*100:0,raw=Number(q.rawCost??q.cost??0),extra=Number(q.expenseTotal??0),landed=Number(q.landedCost??(raw+extra)),mode=q.costMode||'raw';return `<div class="request-quote-card"><div class="panel-head"><div><h3>${q.no} · REV-${String(q.revision).padStart(2,'0')}</h3><div class="sub">${r.customer} · ${r.currency} · ${q.status}</div></div><span class="request-status-chip quote">${q.status}</span></div><div class="quote-cost-mode"><span>Maliyet Bazı</span><button class="btn sm ${mode==='raw'?'primary':''}" onclick="setQuoteCostMode(${r.id},'raw')">Ham Alış · ${money(raw,r.currency)}</button><button class="btn sm ${mode==='landed'?'primary':''}" onclick="setQuoteCostMode(${r.id},'landed')">Giderler Dahil · ${money(landed,r.currency)}</button></div><div class="request-commercial-summary"><div class="commercial-mini"><span>Ham Alış</span><b>${money(raw,r.currency)}</b></div><div class="commercial-mini"><span>Ek Giderler</span><b>${money(extra,r.currency)}</b></div><div class="commercial-mini"><span>Aktif Maliyet</span><b>${money(q.cost,r.currency)}</b></div><div class="commercial-mini"><span>Satış Toplamı</span><b>${money(q.total,r.currency)}</b></div><div class="commercial-mini"><span>Brüt Kâr</span><b>${money(q.profit,r.currency)}</b></div><div class="commercial-mini"><span>Marj</span><b>% ${margin.toFixed(1)}</b></div><div class="commercial-mini"><span>USD/TRY</span><b>${state.fx.usdTry.toFixed(4)}</b></div><div class="commercial-mini"><span>EUR/TRY</span><b>${state.fx.eurTry.toFixed(4)}</b></div></div><div class="quote-sale-editor"><div class="field"><label>Genel Satış Toplamı</label><input type="text" inputmode="decimal" value="${formatMoneyNumber(q.total||0)}" onchange="updateQuoteTotal(${r.id},this.value)"></div><div class="inline-note">Toplamı değiştirirseniz kalem satış fiyatları mevcut dağılıma göre otomatik paylaştırılır.</div></div><div class="table-wrap"><table class="table responsive"><thead><tr><th>Ürün</th><th>Miktar</th><th>Seçili Tedarikçi</th><th>Alış</th><th>Satış</th><th>Toplam</th></tr></thead><tbody>${q.items.map(x=>`<tr><td data-label="Ürün"><b>${x.name}</b></td><td data-label="Miktar">${formatQuantity(x.qty)} ${x.unit}</td><td data-label="Tedarikçi">${x.supplier}</td><td data-label="Alış">${money(x.cost,r.currency)}</td><td data-label="Satış"><input type="text" inputmode="decimal" class="quote-sale-input" data-item-id="${x.itemId}" data-uid="${escAttr(x.itemUid||'')}" value="${formatMoneyNumber(x.sale||0)}" onchange="updateQuoteSale(${r.id},${x.itemId},this.value,this.dataset.uid)" style="width:110px"></td><td data-label="Toplam"><b>${money(x.total,r.currency)}</b></td></tr>`).join('')}</tbody></table></div><div class="section-title"><b>Uygun Tedarikçi Özeti</b></div><div class="supplier-fit-list">${requestSuppliers(r.id).map(sup=>{const chosen=q.items.filter(x=>x.supplierId===sup.id),sum=chosen.reduce((n,x)=>n+x.cost*x.qty,0);return `<div class="supplier-fit-row"><div><b>${sup.name}</b><div class="sub">${sup.payment} · Termin ${sup.delivery} gün · ${chosen.length} seçili kalem</div></div><b>${money(sum,r.currency)}</b></div>`}).join('')}</div><div class="actions" style="margin-top:10px"><button class="btn" onclick="newRevisionForRequest(${r.id})">Yeni Revizyon</button><button class="btn" onclick="markQuoteSent(${r.id})">Gönderildi</button><button class="btn" onclick="go('docs');setTimeout(()=>selectDocRequest(${r.id}),80)">PDF / Evrak Oluştur</button><button class="btn green" onclick="winRequestQuote(${r.id})">Kazanıldı → Sipariş Oluştur</button></div></div>`;}
async function newRevisionForRequest(rid){if(await runLockedServerOperation('quote',rid,{action:'quote_revision',requestId:rid,idempotencyKey:makeIdempotencyKey()}))toast('Yeni teklif revizyonu oluşturuldu.','good')}
async function markQuoteSent(rid){if(await runLockedServerOperation('quote',rid,{action:'quote_mark_sent',requestId:rid,idempotencyKey:makeIdempotencyKey()}))toast('Teklif Gönderildi olarak güncellendi.','good')}

let prepayOrderId=null;
function openCustomerPrepayment(orderId){
 const o=state.orders.find(x=>x.id===orderId);if(!o)return;const r=state.requests.find(x=>x.id===o.requestId);prepayOrderId=orderId;
 const banks=state.cashAccounts;document.getElementById('prepayBank').innerHTML=banks.map(x=>`<option value="${x.id}">${x.name} · ${fmtMoneyCur(x.balance,x.currency)}</option>`).join('');
 const nextStage=(o.paymentSchedule||[]).find(x=>Number(x.due||0)>0);document.getElementById('prepayPercent').value=nextStage?.percent||30;document.getElementById('prepayBasis').value='total';document.getElementById('prepayFxRate').value='';document.getElementById('prepayDescription').value=nextStage?('Ödeme planı: '+nextStage.label):'Müşteri tahsilatı';const bs=document.getElementById('prepayBatch');if(bs)bs.innerHTML='<option value="">Genel Sipariş Tahsilatı</option>'+((o.deliveryBatches||[]).filter(b=>b.status!=='İptal Edildi').map(b=>`<option value="${b.id}">${esc(b.batchNo)} · ${esc(b.status)}</option>`).join(''));syncPrepayFromPercent();syncPrepayFxVisibility();openModal('customerPrepayModal')
}
function syncPrepayDisplay(){const o=state.orders.find(x=>x.id===prepayOrderId);if(!o)return;document.getElementById('prepayOrderTotal').textContent=fmtMoneyCur(o.total,o.currency);document.getElementById('prepayPaid').textContent=fmtMoneyCur(o.customerPaid||0,o.currency);document.getElementById('prepayRemaining').textContent=fmtMoneyCur(o.customerDue??Math.max(0,o.total-(o.customerPaid||0)),o.currency)}
function syncPrepayFromPercent(){const o=state.orders.find(x=>x.id===prepayOrderId);if(!o)return;const pct=Math.max(0,Math.min(100,Number(document.getElementById('prepayPercent').value)||0)),remaining=Number(o.customerDue??o.total),basis=document.getElementById('prepayBasis')?.value==='remaining'?remaining:Number(o.total||0);document.getElementById('prepayAmount').value=Math.min(remaining,basis*pct/100).toFixed(2);syncPrepayDisplay()}
function syncPrepayFromAmount(){const o=state.orders.find(x=>x.id===prepayOrderId);if(!o)return;const amount=Math.max(0,Number(document.getElementById('prepayAmount').value)||0);document.getElementById('prepayPercent').value=o.total?((amount/o.total)*100).toFixed(2):0;syncPrepayDisplay()}
function syncPrepayFxVisibility(){const o=state.orders.find(x=>x.id===prepayOrderId),b=state.cashAccounts.find(x=>x.id===+document.getElementById('prepayBank').value),f=document.getElementById('prepayFxField'),inp=document.getElementById('prepayFxRate');const cross=!!(o&&b&&b.currency!==o.currency);if(f)f.style.display=cross?'block':'none';if(inp){const auto=cross?fxRateBetween(o.currency,b.currency):1;inp.value=auto>0?Number(auto).toFixed(6):(cross?'':'1')}}
async function completeCustomerPrepayment(){const o=state.orders.find(x=>x.id===prepayOrderId);if(!o)return;const amount=Math.max(0,Number(document.getElementById('prepayAmount').value)||0),bankId=+document.getElementById('prepayBank').value,bank=state.cashAccounts.find(x=>x.id===bankId),cross=!!(bank&&bank.currency!==o.currency),fxRate=cross?Number(document.getElementById('prepayFxRate').value||0):1,description=document.getElementById('prepayDescription').value.trim()||'Müşteri tahsilatı',batchId=+document.getElementById('prepayBatch')?.value||0;if(cross&&!(fxRate>0))return toast('Farklı para birimi için geçerli kur/parite zorunlu. TCMB kurunu yenileyin veya manuel kur girin.','warn');if(await runServerOperation({action:'prepayment',orderId:o.id,amount,bankId,bankName:bank?.name||'',bankCurrency:bank?.currency||'',fxRate,fxRateConfirmed:!cross||fxRate>0,description,batchId,idempotencyKey:makeIdempotencyKey()})){closeModal('customerPrepayModal');toast('Müşteri tahsilatı kaydedildi.','good')}}


async function winRequestQuote(rid){const r=state.requests.find(x=>x.id===rid);if(!r?.customerQuote)return;if(await runServerOperation({action:'status_change',requestId:rid,status:'won',reason:''})){toast('Kazanıldı: sipariş, PO ve cari kayıtları transaction ile oluşturuldu.','good');setTimeout(()=>openRequestTab(rid,'order'),70)}}

const SUPPLIER_IDENTITY_COLORS=['#2563eb','#7c3aed','#db2777','#ea580c','#0891b2','#16a34a','#ca8a04','#475569','#dc2626','#4f46e5','#0f766e','#9333ea'];
function supplierIdentityColor(o,supplier){
 const names=[...new Set((o?.pos||[]).map(p=>normalizedPartyName(p.supplier)).filter(Boolean))];
 const key=normalizedPartyName(supplier),idx=Math.max(0,names.indexOf(key));
 return SUPPLIER_IDENTITY_COLORS[idx%SUPPLIER_IDENTITY_COLORS.length]
}
function supplierIdentityKey(supplier,color){return `<span class="supplier-color-key" style="--supplier-color:${escAttr(color)}" title="${escAttr(supplier||'Tedarikçi')}"></span>`}

function requestOrderHtml(r){const o=requestOrder(r);if(!o)return `<div class="empty">Henüz sipariş yok. Müşteri teklifi Kazanıldı olduğunda otomatik oluşur.</div>`;const statuses=['Hazırlanıyor','Üretimde','Sevkiyata Hazır','Kısmi Teslimat','Yolda','Teslim Edildi'],batchLocked=(o.deliveryBatches||[]).some(b=>b.status!=='İptal Edildi');return `<div class="request-order-card"><div class="panel-head"><div><h3>${o.no}</h3><div class="sub">${o.customer} · ${r.no} · Sipariş & Operasyon ile canlı senkron</div></div><div class="big">${money(o.total,r.currency)}</div></div>${orderFinanceSummaryHtml(o)}<div class="order-grid">${o.pos.map(po=>`<div class="po-card supplier-identity-card" style="--supplier-color:${supplierIdentityColor(o,po.supplier)}"><div class="po-head"><div><b>${supplierIdentityKey(po.supplier,supplierIdentityColor(o,po.supplier))}${po.no} · ${esc(po.supplier)}</b><div class="sub">${po.payment}</div></div><span class="badge ${po.status==='Teslim Edildi'?'b-green':'b-blue'}">${po.status}</span></div><div class="status-row">${statuses.map(s=>{const locked=batchLocked&&['Kısmi Teslimat','Teslim Edildi'].includes(s);return `<button class="status-pill ${po.status===s?'active':''}" data-status="${s}" ${locked?'disabled title="Partili teslimat aktif"':''} onclick="${locked?'return false':`setPoStatus(${po.id},'${s}',${Number(o?.id||0)})`}">${s}${locked?' 🔒':''}</button>`}).join('')}</div><div class="sub">Operasyon ilerleme %${po.production}</div><div class="progress" style="margin:6px 0 10px"><i style="width:${po.production}%"></i></div><div class="quote-kpis" style="grid-template-columns:1fr 1fr"><div class="quote-kpi"><span>PO Toplam</span><b>${money(po.total,po.currency||o.currency||r.currency)}</b></div><div class="quote-kpi"><span>Kalan Borç</span><b>${money(po.due,po.currency||o?.currency||'EUR')}</b></div></div><div class="actions" style="margin-top:8px"><button class="btn sm" onclick="go('accounts')">Tedarikçi Carisi</button><button class="btn sm" onclick="revisePo(${po.id},${Number(o?.id||0)})">PO Revizyonu</button></div></div>`).join('')}</div>${paymentScheduleHtml(o)}<div class="actions" style="margin-top:10px"><button class="btn primary" onclick="go('orders')">Sipariş & Operasyon'a Git</button><button class="btn" onclick="go('orders');setTimeout(()=>openDeliveryBatchModal(${o.id}),100)">+ Teslimat Partisi</button><button class="btn" onclick="openDocsForRequest(${r.id})">Evraklar Bölümüne Git</button></div></div>`}
function requestDocumentsHtml(r){const docs=requestDocuments(r.id),dc=requestDocCompletion(r.id),atts=(state.requestAttachments||[]).filter(x=>x.requestId===r.id);const head=`<div class="panel-head" style="margin-bottom:9px"><div><h3>Talep Evrakları</h3><div class="sub">Commercial Offer, Proforma, Packing List, Commercial Invoice + Check List</div></div><div class="actions"><span class="badge ${dc.complete?'b-green':'b-blue'}">${dc.count}/4 Hazır</span><button class="btn primary sm" onclick="openDocsForRequest(${r.id})">+ Evrak Ekle</button></div></div>`;const attHtml=atts.length?`<div class="section-title"><b>Yüklenen Talep Dokümanları</b><button class="btn sm" onclick="openAttachmentUpload(${r.id})">+ Doküman</button></div><div class="doc-library-list">${atts.map(a=>`<div class="doc-library-card"><div><b>${a.name}</b><small>${a.categoryLabel||a.category||'Diğer'} · Rev.${a.version||1} · ${a.uploadedBy||''} · ${a.description||''}</small></div><a class="btn sm" href="${a.url}" target="_blank">Aç</a></div>`).join('')}</div>`:`<div class="inline-note">Yüklenmiş ihale/şartname dokümanı yok. <button class="btn sm" onclick="openAttachmentUpload(${r.id})">+ Doküman Ekle</button></div>`;if(!docs.length)return head+attHtml+`<div class="empty">Bu talebe bağlı kaydedilmiş ticari evrak yok.</div>`;return head+attHtml+`<div class="saved-doc-list">${docs.slice().reverse().map(d=>`<div class="saved-doc-card ${d.type}"><div><div><span class="doc-type-badge ${d.type}">${docTypeShort(d.type)}</span><b>${d.no}</b></div><small>${d.typeLabel} · ${d.orientation==='portrait'?'A4 Dikey':'A4 Yatay'} · ${d.created}</small></div><div class="actions"><button class="btn sm" onclick="viewSavedDocument(${d.id})">Görüntüle</button><button class="btn sm primary" onclick="editSavedDocument(${d.id})">Düzenle</button><button class="btn sm" onclick="printSavedDocument(${d.id})">PDF</button></div></div>`).join('')}</div>`}
function orderSupplierFinanceByCurrency(o){const map={};(o?.pos||[]).forEach(po=>{const cur=po.currency||o.currency||'EUR',actualRows=orderSupplierPaymentRows(o,po.id),paid=actualRows.length?actualRows.reduce((n,c)=>n+Number(c.orderAmount??c.amountValue??c.amount??0),0):Number(po.paid||0),total=Number(po.total||0);if(!map[cur])map[cur]={total:0,paid:0,due:0};map[cur].total+=total;map[cur].paid+=paid;map[cur].due+=Math.max(0,total-paid)});return map}
function financeCurrencyGroupText(map,key){const rows=Object.entries(map||{});return rows.length?rows.map(([cur,v])=>fmtMoneyCur(Number(v?.[key]||0),cur)).join(' · '):'—'}
function orderFinanceSummary(o){const customerTotal=Number(o?.total||0),receiptRows=orderCustomerReceiptRows(o),customerPaid=receiptRows.length?receiptRows.reduce((n,c)=>n+Number(c.orderAmount??c.amountValue??c.amount??0),0):Number(o?.customerPaid||0),customerDue=Math.max(0,customerTotal-customerPaid),supplierByCurrency=orderSupplierFinanceByCurrency(o),supplierAnyDue=Object.values(supplierByCurrency).some(x=>Number(x.due||0)>.001),supplierPoCount=(o?.pos||[]).length,supplierClosedCount=(o?.pos||[]).filter(po=>{const rr=orderSupplierPaymentRows(o,po.id),paid=rr.length?rr.reduce((n,c)=>n+Number(c.orderAmount??c.amountValue??c.amount??0),0):Number(po.paid||0);return Number(po.total||0)-paid<=.001}).length,supplierPct=supplierPoCount?Math.min(100,supplierClosedCount/supplierPoCount*100):100;return {customerTotal,customerPaid,customerDue,supplierByCurrency,supplierAnyDue,supplierPoCount,supplierClosedCount,supplierPct,customerPct:customerTotal?Math.min(100,customerPaid/customerTotal*100):0}}
function orderFinanceSummaryHtml(o){const f=orderFinanceSummary(o),cur=o.currency||'EUR',supplierTotal=financeCurrencyGroupText(f.supplierByCurrency,'total'),supplierPaid=financeCurrencyGroupText(f.supplierByCurrency,'paid'),supplierDue=financeCurrencyGroupText(f.supplierByCurrency,'due');return `<div class="order-finance-summary"><div class="order-finance-line"><div><span>Müşteri Tahsilatı</span><b>${fmtMoneyCur(f.customerPaid,cur)} / ${fmtMoneyCur(f.customerTotal,cur)}</b><small>Kalan ${fmtMoneyCur(f.customerDue,cur)}</small></div><div class="progress"><i style="width:${f.customerPct}%"></i></div><strong>%${f.customerPct.toFixed(1)}</strong></div><div class="order-finance-line"><div><span>Tedarikçi Ödemeleri</span><b>${supplierPaid} / ${supplierTotal}</b><small>Kalan ${supplierDue}</small></div><div class="progress"><i style="width:${f.supplierPct}%"></i></div><strong>${f.supplierClosedCount}/${f.supplierPoCount} PO</strong></div><div class="actions order-finance-actions"><button class="btn green" ${f.customerDue<=0?'disabled':''} onclick="openCustomerPrepayment(${o.id})">+ Müşteriden Tahsil Et</button><button class="btn primary" ${!f.supplierAnyDue?'disabled':''} onclick="openOrderSupplierPayment(${o.id})">+ Tedarikçiye Ödeme Yap</button></div></div>`}
function orderCustomerReceiptRows(o){return (state.cash||[]).filter(c=>Number(c.orderId)===Number(o?.id)&&!c.reversed&&['customer_prepayment','generic_customer_receipt','delivery_batch_collection'].includes(c.kind))}
function orderSupplierPaymentRows(o,poId=0){return (state.cash||[]).filter(c=>Number(c.orderId)===Number(o?.id)&&!c.reversed&&['generic_supplier_payment','delivery_batch_supplier_payment'].includes(c.kind)&&(!poId||Number(c.poId)===Number(poId)))}
function customerPaymentScheduleDisplay(o){
 const rows=(o?.paymentSchedule||[]).map(x=>({...x,paid:0,due:Number(x.amount||0),status:'Bekliyor'}));if(!rows.length)return rows;
 const cashRows=orderCustomerReceiptRows(o),actual=cashRows.length?cashRows.reduce((n,c)=>n+Number(c.orderAmount??c.amountValue??c.amount??0),0):Number(o.customerPaid||0);let left=Math.max(0,actual);
 rows.forEach(st=>{const amount=Math.max(0,Number(st.amount||0)),x=Math.min(amount,left);st.paid=x;st.due=Math.max(0,amount-x);st.status=st.due<=.001?'Tamamlandı':x>0?'Kısmi':'Bekliyor';left=Math.max(0,left-x)});return rows
}
function supplierPaymentTrackingHtml(o){
 const pos=o?.pos||[];if(!pos.length)return '<div class="empty">Bu siparişte tedarikçi PO bulunmuyor.</div>';
 return `<div class="table-wrap"><table class="table responsive"><thead><tr><th>Tedarikçi / PO</th><th>Ödeme Koşulu</th><th>PO Toplam</th><th>Gerçek Ödeme</th><th>Kalan</th><th>İlerleme</th><th></th></tr></thead><tbody>${pos.map(po=>{const actualRows=orderSupplierPaymentRows(o,po.id),paid=actualRows.length?actualRows.reduce((n,c)=>n+Number(c.orderAmount??c.amountValue??c.amount??0),0):Number(po.paid||0),total=Number(po.total||0),due=Math.max(0,total-paid),pct=total?Math.min(100,paid/total*100):0,cur=po.currency||o.currency||'EUR',status=due<=.001?'Tamamlandı':paid>0?'Kısmi':'Bekliyor';return `<tr><td><b>${esc(po.supplier||'')}</b><div class="sub">${esc(po.no||'')}</div></td><td>${esc(po.payment||'—')}</td><td><b>${fmtMoneyCur(total,cur)}</b></td><td><b>${fmtMoneyCur(paid,cur)}</b><div class="sub">${actualRows.length} gerçek ödeme</div></td><td><b>${fmtMoneyCur(due,cur)}</b></td><td><span class="badge ${status==='Tamamlandı'?'b-green':status==='Kısmi'?'b-yellow':'b-blue'}">${status}</span><div class="stage-progress"><i style="width:${pct}%"></i></div><small>%${pct.toFixed(1)}</small></td><td>${due>.001?`<button class="btn primary sm" onclick="payPo(${Number(po.id)},${Number(o.id)},'${escAttr(po.no||'')}')">Ödeme Yap</button>`:'<span class="badge b-green">Kapalı</span>'}</td></tr>`}).join('')}</tbody></table></div>`
}
function paymentScheduleHtml(o){
 const rows=customerPaymentScheduleDisplay(o),receipts=orderCustomerReceiptRows(o),customerPaid=receipts.length?receipts.reduce((n,c)=>n+Number(c.orderAmount??c.amountValue??c.amount??0),0):Number(o.customerPaid||0),customerDue=Math.max(0,Number(o.total||0)-customerPaid),supplierRows=orderSupplierPaymentRows(o),supplierByCurrency=orderSupplierFinanceByCurrency(o),supplierPaidText=financeCurrencyGroupText(supplierByCurrency,'paid'),supplierDueText=financeCurrencyGroupText(supplierByCurrency,'due'),supplierAnyDue=Object.values(supplierByCurrency).some(x=>Number(x.due||0)>.001);
 return `<div class="section-title"><b>Müşteri & Tedarikçi Ödeme Takibi</b><span class="badge b-blue">Canlı finans hareketlerinden mutabakat</span></div><div class="quote-kpis" style="margin-bottom:10px"><div class="quote-kpi"><span>Müşteriden Tahsil</span><b>${fmtMoneyCur(customerPaid,o.currency||'EUR')}</b><small>${receipts.length} gerçek hareket</small></div><div class="quote-kpi"><span>Müşteri Kalan</span><b>${fmtMoneyCur(customerDue,o.currency||'EUR')}</b></div><div class="quote-kpi"><span>Tedarikçiye Ödenen</span><b>${supplierPaidText}</b><small>${supplierRows.length} gerçek hareket</small></div><div class="quote-kpi"><span>Tedarikçi Kalan</span><b>${supplierDueText}</b></div></div><div class="section-title"><b>Müşteri Ödeme Planı</b><span class="badge b-blue">${rows.filter(x=>x.status==='Tamamlandı').length}/${rows.length||0} tamamlandı</span></div>${rows.length?`<div class="payment-schedule">${rows.map(st=>{const pct=Number(st.amount||0)>0?Math.min(100,Number(st.paid||0)/Number(st.amount)*100):0;return `<div class="payment-stage"><div><b>${esc(st.label||'Ödeme Aşaması')}</b><div class="sub">%${Number(st.percent||0).toLocaleString('tr-TR')} ödeme aşaması</div></div><div><span class="sub">Tutar</span><b>${fmtMoneyCur(st.amount||0,o.currency)}</b></div><div><span class="sub">Tahsil</span><b>${fmtMoneyCur(st.paid||0,o.currency)}</b></div><div><span class="sub">Kalan</span><b>${fmtMoneyCur(st.due||0,o.currency)}</b></div><div><span class="badge ${st.status==='Tamamlandı'?'b-green':st.status==='Kısmi'?'b-yellow':'b-blue'}">${st.status||'Bekliyor'}</span><div class="stage-progress"><i style="width:${pct}%"></i></div></div></div>`}).join('')}</div>`:'<div class="empty">Müşteri ödeme planı tanımlı değil.</div>'}<div class="section-title"><b>Tedarikçi Ödeme Takibi</b><span class="badge ${supplierAnyDue?'b-yellow':'b-green'}">${supplierAnyDue?'Açık borç var':'Tümü ödendi'}</span></div>${supplierPaymentTrackingHtml(o)}`
}
function orderSupplierPaymentHistoryHtml(o){const rows=orderSupplierPaymentRows(o);return `<div class="section-title"><b>Gerçek Tedarikçi Ödemeleri</b></div>${rows.length?`<div class="table-wrap"><table class="table responsive"><thead><tr><th>Tarih</th><th>Tedarikçi</th><th>PO</th><th>Banka/Kasa</th><th>Tutar</th><th>Durum</th></tr></thead><tbody>${rows.map(c=>{const po=(o.pos||[]).find(p=>Number(p.id)===Number(c.poId)),cur=po?.currency||c.currency||o.currency||'EUR';return `<tr><td>${esc(c.date||'')}</td><td><b>${esc(c.party||po?.supplier||'')}</b></td><td>${esc(c.poNo||po?.no||'—')}</td><td>${esc(c.account||'')}</td><td><b>${fmtMoneyCur(c.orderAmount??c.amountValue??c.amount??0,cur)}</b></td><td><span class="badge b-green">Ödendi</span></td></tr>`}).join('')}</tbody></table></div>`:'<div class="empty">Henüz tedarikçi ödemesi yok.</div>'}`}
let customerReceiptEditState=null;
function orderPaymentHistoryHtml(o){const rows=(state.cash||[]).filter(c=>Number(c.orderId)===Number(o.id)&&!c.reversed&&['customer_prepayment','generic_customer_receipt','delivery_batch_collection'].includes(c.kind));return `<div class="section-title"><b>Gerçek Müşteri Tahsilatları</b></div>${rows.length?`<div class="table-wrap"><table class="table responsive"><thead><tr><th>Tarih</th><th>Banka/Kasa</th><th>Tutar</th><th>Kur</th><th>Durum</th><th>İşlem</th></tr></thead><tbody>${rows.map(c=>`<tr><td>${c.date||''}</td><td>${esc(c.account||'')}</td><td>${fmtMoneyCur(c.orderAmount??c.amountValue,o.currency)}</td><td>${Number(c.fxRate||1).toLocaleString('tr-TR',{maximumFractionDigits:6})}</td><td><span class="badge b-green">Tahsil</span></td><td>${c.journalId?`<div class="actions"><button class="btn sm" onclick="openCustomerReceiptEdit(${Number(c.journalId)})">Düzenle</button><button class="btn red sm" onclick="deleteCustomerReceipt(${Number(c.journalId)})">Sil</button></div>`:''}</td></tr>`).join('')}</tbody></table></div>`:'<div class="empty">Henüz müşteri tahsilatı yok.</div>'}`}
async function deleteCustomerReceipt(journalId){if(!await appConfirm('Bu müşteri tahsilatı tamamen silinsin mi? Kasa/Banka, müşteri carisi, sipariş tahsilatı ve ödeme planı birlikte geri alınacaktır.','Müşteri Tahsilatını Sil'))return;if(await runServerOperation({action:'finance_delete',journalId:Number(journalId),idempotencyKey:makeIdempotencyKey()}))toast('Müşteri tahsilatı tamamen silindi.','good')}
function openCustomerReceiptEdit(journalId){const jid=Number(journalId||0),c=(state.cash||[]).find(x=>Number(x.journalId||0)===jid&&!x.reversed&&['customer_prepayment','generic_customer_receipt','delivery_batch_collection'].includes(x.kind));if(!c)return toast('Düzenlenecek müşteri tahsilatı bulunamadı.','warn');const o=state.orders.find(x=>Number(x.id)===Number(c.orderId));if(!o)return toast('Tahsilata bağlı sipariş bulunamadı.','warn');const cur=o.currency||c.currency||'EUR',amount=Number(c.orderAmount??c.amountValue??0),max=Math.max(0,Number(o.customerDue||0)+amount);customerReceiptEditState={journalId:jid,orderId:o.id,currency:cur,originalBankId:Number(c.accountId||0),originalFx:Number(c.fxRate||1),kind:c.kind};document.getElementById('customerReceiptEditSub').textContent=`${o.no||''} · ${o.customer||c.party||''}`;document.getElementById('creCurrentAmount').textContent=fmtMoneyCur(amount,cur);document.getElementById('creMaxAmount').textContent=fmtMoneyCur(max,cur);document.getElementById('creCurrency').textContent=cur;document.getElementById('creAmount').value=amount.toFixed(2);const parts=String(c.date||'').split('.');document.getElementById('creDate').value=parts.length===3?`${parts[2]}-${parts[1]}-${parts[0]}`:todayISO();document.getElementById('creDescription').value=c.note||'Müşteri tahsilatı';const banks=state.cashAccounts||[],bankSel=document.getElementById('creBank');bankSel.innerHTML=banks.length?banks.map(b=>`<option value="${b.id}" ${Number(b.id)===Number(c.accountId)?'selected':''}>${esc(b.name)} · ${fmtMoneyCur(b.balance,b.currency)}</option>`).join(''):'<option value="">Kasa/Banka hesabı yok</option>';const onlyDelivered=c.kind==='delivery_batch_collection',batchSel=document.getElementById('creBatch');batchSel.innerHTML='<option value="">Genel Sipariş Tahsilatı</option>'+((o.deliveryBatches||[]).filter(b=>b.status!=='İptal Edildi'&&(!onlyDelivered||b.status==='Teslim Edildi')).map(b=>`<option value="${b.id}" ${Number(b.id)===Number(c.batchId||0)?'selected':''}>${esc(b.batchNo)} · ${esc(b.status)}</option>`).join(''));document.getElementById('creFxRate').value=Number(c.fxRate||1).toFixed(6);syncCustomerReceiptEditFx(true);openModal('customerReceiptEditModal')}
function syncCustomerReceiptEditFx(initial=false){const st=customerReceiptEditState;if(!st)return;const bank=state.cashAccounts.find(x=>Number(x.id)===Number(document.getElementById('creBank')?.value)),field=document.getElementById('creFxField'),fx=document.getElementById('creFxRate');if(!bank||!field||!fx)return;const cross=bank.currency!==st.currency;field.style.display=cross?'grid':'none';if(!cross){fx.value='1';return}if(initial&&Number(bank.id)===Number(st.originalBankId)&&st.originalFx>0){fx.value=Number(st.originalFx).toFixed(6);return}const suggested=suggestManualFxRate(st.currency,bank.currency);fx.value=Number(suggested||0).toFixed(6)}
async function saveCustomerReceiptEdit(){const st=customerReceiptEditState;if(!st)return;const amount=Number(document.getElementById('creAmount').value||0),bankId=Number(document.getElementById('creBank').value||0),bank=state.cashAccounts.find(x=>Number(x.id)===bankId),date=document.getElementById('creDate').value||todayISO(),description=document.getElementById('creDescription').value.trim(),batchId=Number(document.getElementById('creBatch').value||0),cross=!!(bank&&bank.currency!==st.currency),fxRate=cross?Number(document.getElementById('creFxRate').value||0):1;if(!(amount>0))return toast('Tahsilat tutarı sıfırdan büyük olmalı.','warn');if(!bank)return toast('Kasa/Banka seçin.','warn');if(!description)return toast('Açıklama zorunlu.','warn');if(cross&&!(fxRate>0))return toast('Geçerli kur/parite girin.','warn');const btn=document.getElementById('saveCustomerReceiptEditBtn');btn.disabled=true;const ok=await runServerOperation({action:'finance_customer_receipt_update',journalId:st.journalId,amount,bankId,date,description,batchId,fxRate,fxRateConfirmed:!cross||fxRate>0,idempotencyKey:makeIdempotencyKey()});btn.disabled=false;if(ok){closeModal('customerReceiptEditModal');customerReceiptEditState=null;toast('Müşteri tahsilatı güncellendi.','good')}}
async function reverseFinance(journalId){if(!await appConfirm('Bu finans hareketi için ters kayıt oluşturulsun mu? Geçmiş kayıt silinmeyecek.','Finans Ters Kayıt'))return;if(await runServerOperation({action:'reverse_finance',journalId,idempotencyKey:makeIdempotencyKey()}))toast('Ters finans kaydı oluşturuldu.','good')}

function makeIdempotencyKey(){try{return crypto.randomUUID()}catch(_e){return 'op-'+Date.now()+'-'+Math.random().toString(36).slice(2)}}
function orderQuote(o){return state.requests.find(r=>r.id===o.requestId)?.customerQuote||null}
function orderItemCatalog(o){
 const q=orderQuote(o);return (q?.items||[]).map(x=>({itemId:x.itemId,name:x.name,qty:Number(x.qty||0),unit:x.unit||'',sale:Number(x.sale||0),cost:Number(x.cost||0),supplierId:x.supplierId,supplier:x.supplier||''}))
}
function orderBatchAllocated(o,itemId,deliveredOnly=false){
 return (o.deliveryBatches||[]).filter(b=>b.status!=='İptal Edildi'&&(!deliveredOnly||b.status==='Teslim Edildi')).reduce((n,b)=>n+(b.items||[]).filter(x=>Number(x.itemId)===Number(itemId)).reduce((s,x)=>s+Number(x.qty||0),0),0)
}
function orderDeliveryStats(o){
 const cat=orderItemCatalog(o),ordered=cat.reduce((n,x)=>n+x.qty,0),allocated=cat.reduce((n,x)=>n+orderBatchAllocated(o,x.itemId,false),0),delivered=cat.reduce((n,x)=>n+orderBatchAllocated(o,x.itemId,true),0);
 return {ordered,allocated,delivered,remaining:Math.max(0,ordered-allocated),deliveryRemaining:Math.max(0,ordered-delivered),pct:ordered?Math.min(100,delivered/ordered*100):0}
}
function batchStatusClass(s){return s==='Teslim Edildi'?'batch-status-delivered':s==='İptal Edildi'?'batch-status-cancelled':'batch-status-planned'}
let currentBatchOrderId=null,currentBatchEditId=null,currentBatchFinance=null,currentBatchFinanceToken=null;
function openDeliveryBatchModal(orderId){
 currentBatchEditId=null;document.getElementById('saveDeliveryBatchBtn').textContent='Partiyi Oluştur';
 const o=state.orders.find(x=>x.id===orderId);if(!o||o.open===false)return toast('Kapalı siparişte yeni teslimat partisi oluşturulamaz.','warn');
 currentBatchOrderId=orderId;const cat=orderItemCatalog(o);
 document.getElementById('deliveryBatchTitle').textContent=o.no+' · Yeni Teslimat Partisi';
 const d=new Date();d.setDate(d.getDate()+1);document.getElementById('batchPlannedDate').value=d.toISOString().slice(0,10);document.getElementById('batchDescription').value='';document.getElementById('batchShipmentRef').value='';document.getElementById('batchCarrier').value='';document.getElementById('batchVehicleContainer').value='';document.getElementById('batchReceiver').value='';
 document.getElementById('batchCreateItems').innerHTML=cat.map(x=>{const allocated=orderBatchAllocated(o,x.itemId,false),remaining=Math.max(0,x.qty-allocated);return `<div class="batch-create-row"><div><b>${x.name}</b><div class="sub">${x.unit} · Sipariş ${formatQuantity(x.qty)} · Ayrılmış ${formatQuantity(allocated)}</div></div><div><span class="sub">Kalan</span><b>${formatQuantity(remaining)}</b></div><div><span class="sub">Bu Parti</span><input id="batchQty_${x.itemId}" type="number" min="0" max="${remaining}" step="0.0001" value="0" ${remaining<=0?'disabled':''}></div><div><span class="sub">Satış Birim</span><b>${fmtMoneyCur(x.sale,o.currency)}</b></div></div>`}).join('')||'<div class="empty">Sipariş kalemi bulunamadı.</div>';
 const st=orderDeliveryStats(o);document.getElementById('batchCreateInfo').textContent=`Sipariş toplam ${formatQuantity(st.ordered)} adet · Partilere ayrılmış ${formatQuantity(st.allocated)} · Ayrılabilir ${formatQuantity(st.remaining)}`;
 openModal('deliveryBatchModal')
}
async function saveDeliveryBatch(){
 const o=state.orders.find(x=>x.id===currentBatchOrderId);if(!o)return;
 const items=orderItemCatalog(o).map(x=>({itemId:x.itemId,qty:Number(document.getElementById('batchQty_'+x.itemId)?.value||0)})).filter(x=>x.qty>0);
 if(!items.length)return toast('Parti için en az bir kalemde teslim adedi girin.','warn');
 const btn=document.getElementById('saveDeliveryBatchBtn');btn.disabled=true;
 const payload={action:currentBatchEditId?'delivery_batch_update':'delivery_batch_create',orderId:o.id,batchId:currentBatchEditId||undefined,plannedDate:document.getElementById('batchPlannedDate').value,description:document.getElementById('batchDescription').value.trim(),shipmentRef:document.getElementById('batchShipmentRef').value.trim(),carrier:document.getElementById('batchCarrier').value.trim(),vehicleContainer:document.getElementById('batchVehicleContainer').value.trim(),receiver:document.getElementById('batchReceiver').value.trim(),items,idempotencyKey:makeIdempotencyKey()};
 const ok=await runServerOperation(payload);btn.disabled=false;if(ok){closeModal('deliveryBatchModal');toast(currentBatchEditId?'Teslimat partisi güncellendi.':'Teslimat partisi oluşturuldu.','good');currentBatchEditId=null}
}
function editDeliveryBatch(orderId,batchId){
 const o=state.orders.find(x=>x.id===orderId),b=(o?.deliveryBatches||[]).find(x=>x.id===batchId);if(!o||!b)return;
 if(b.status!=='Planlandı')return toast('Yalnız Planlandı durumundaki parti düzenlenebilir.','warn');
 const hasFinance=(state.cash||[]).some(c=>Number(c.batchId)===Number(batchId)&&!c.reversed);if(hasFinance)return toast('Finans hareketi bulunan parti düzenlenemez. Önce ilgili hareketi ters kaydedin.','warn');
 currentBatchOrderId=orderId;currentBatchEditId=batchId;document.getElementById('deliveryBatchTitle').textContent=b.batchNo+' · Teslimat Partisini Düzenle';document.getElementById('saveDeliveryBatchBtn').textContent='Değişiklikleri Kaydet';document.getElementById('batchPlannedDate').value=b.plannedDate||todayISO();document.getElementById('batchDescription').value=b.description||'';document.getElementById('batchShipmentRef').value=b.shipmentRef||'';document.getElementById('batchCarrier').value=b.carrier||'';document.getElementById('batchVehicleContainer').value=b.vehicleContainer||'';document.getElementById('batchReceiver').value=b.receiver||'';
 const cat=orderItemCatalog(o);document.getElementById('batchCreateItems').innerHTML=cat.map(x=>{const otherAllocated=(o.deliveryBatches||[]).filter(bb=>bb.id!==batchId&&bb.status!=='İptal Edildi').reduce((n,bb)=>n+(bb.items||[]).filter(it=>Number(it.itemId)===Number(x.itemId)).reduce((s,it)=>s+Number(it.qty||0),0),0),current=(b.items||[]).filter(it=>Number(it.itemId)===Number(x.itemId)).reduce((n,it)=>n+Number(it.qty||0),0),max=Math.max(0,x.qty-otherAllocated);return `<div class="batch-create-row"><div><b>${x.name}</b><div class="sub">${x.unit} · Sipariş ${formatQuantity(x.qty)} · Diğer partiler ${formatQuantity(otherAllocated)}</div></div><div><span class="sub">Maks.</span><b>${formatQuantity(max)}</b></div><div><span class="sub">Bu Parti</span><input id="batchQty_${x.itemId}" type="number" min="0" max="${max}" step="0.0001" value="${current}"></div><div><span class="sub">Satış Birim</span><b>${fmtMoneyCur(x.sale,o.currency)}</b></div></div>`}).join('');
 document.getElementById('batchCreateInfo').textContent='Bu parti düzenlenirken kendi mevcut adedi hariç tutulur; diğer partilerde ayrılmış miktarlar tekrar kullanılamaz.';openModal('deliveryBatchModal')
}
async function deleteDeliveryBatch(orderId,batchId){
 const o=state.orders.find(x=>x.id===orderId),b=(o?.deliveryBatches||[]).find(x=>x.id===batchId);if(!o||!b)return;
 if(b.status!=='Planlandı')return toast('Yalnız Planlandı durumundaki parti silinebilir. Teslim edilen parti geçmişten silinmez.','warn');
 const hasFinance=(state.cash||[]).some(c=>Number(c.batchId)===Number(batchId)&&!c.reversed);if(hasFinance)return toast('Finans hareketi bulunan parti silinemez. Önce ilgili hareketi ters kaydedin.','warn');
 if(!await appConfirm(b.batchNo+' kalıcı olarak silinsin mi? Ayrılan adetler tekrar kullanılabilir hale gelecek.','Teslimat Partisi'))return;
 if(await runServerOperation({action:'delivery_batch_delete',orderId,batchId,idempotencyKey:makeIdempotencyKey()}))toast('Teslimat partisi silindi.','good')
}
async function markDeliveryBatchDelivered(orderId,batchId){
 if(!await appConfirm('Bu parti gerçekten teslim edildi olarak işaretlensin mi? Teslim edilen adetler kesinleşecektir.','Teslimat Onayı'))return;
 if(await runServerOperation({action:'delivery_batch_deliver',orderId,batchId,deliveredDate:todayISO(),idempotencyKey:makeIdempotencyKey()}))toast('Parti teslim edildi olarak işlendi.','good')
}
async function undoDeliveryBatchDelivered(orderId,batchId){
 const o=state.orders.find(x=>Number(x.id)===Number(orderId)),b=(o?.deliveryBatches||[]).find(x=>Number(x.id)===Number(batchId));if(!o||!b)return;if(b.status!=='Teslim Edildi')return toast('Yalnız teslim edilmiş parti geri alınabilir.','warn');
 if(!await appConfirm(`${b.batchNo} için "Teslim Edildi" işlemi geri alınsın mı? Parti tekrar Planlandı durumuna dönecek; normal müşteri tahsilatları silinmeyecektir.`,'Teslimatı Geri Al'))return;
 if(await runServerOperation({action:'delivery_batch_undo',orderId,batchId,idempotencyKey:makeIdempotencyKey()}))toast('Teslimat geri alındı; parti tekrar Planlandı durumunda.','good')
}
async function cancelDeliveryBatch(orderId,batchId){
 if(!await appConfirm('Planlanan parti iptal edilsin mi? Ayrılan adetler yeniden kullanılabilir hale gelecek.','Parti İptali'))return;
 if(await runServerOperation({action:'delivery_batch_cancel',orderId,batchId,idempotencyKey:makeIdempotencyKey()}))toast('Teslimat partisi iptal edildi.','good')
}
function openBatchCustomerCollection(orderId,batchId){
 const o=state.orders.find(x=>x.id===orderId),b=(o?.deliveryBatches||[]).find(x=>x.id===batchId);if(!o||!b)return;
 const max=Math.max(0,Math.min(Number(b.customerDue||0),Number(o.customerDue??0)));if(max<=0)return toast('Bu parti için tahsil edilebilir açık tutar yok.','warn');
 currentBatchFinance={type:'customer',orderId,batchId,max,currency:o.currency,party:o.customer};currentBatchFinanceToken=makeIdempotencyKey();
 document.getElementById('deliveryFinanceTitle').textContent=b.batchNo+' · Müşteriden Tahsil Et';document.getElementById('deliveryFinanceSub').textContent='Bu teslimat partisine bağlı müşteri tahsilatı.';
 document.getElementById('deliveryFinanceTotal').textContent=fmtMoneyCur(b.customerAmount||0,o.currency);document.getElementById('deliveryFinancePaid').textContent=fmtMoneyCur(b.customerCollected||0,o.currency);document.getElementById('deliveryFinanceDue').textContent=fmtMoneyCur(max,o.currency);document.getElementById('deliveryFinanceParty').textContent=o.customer;
 document.getElementById('deliveryFinanceAmount').value=max.toFixed(2);document.getElementById('deliveryFinanceDescription').value=b.batchNo+' müşteri tahsilatı';fillDeliveryFinanceBanks();openModal('deliveryFinanceModal')
}
function openBatchSupplierPayment(orderId,batchId,poId){
 const o=state.orders.find(x=>x.id===orderId),b=(o?.deliveryBatches||[]).find(x=>x.id===batchId),sp=(b?.supplierPayments||[]).find(x=>Number(x.poId)===Number(poId)),po=(o?.pos||[]).find(x=>Number(x.id)===Number(poId));if(!o||!b||!sp||!po)return;
 const max=Math.max(0,Math.min(Number(sp.due||0),Number(po.due||0)));if(max<=0)return toast('Bu parti/tedarikçi için ödenecek açık tutar yok.','warn');
 currentBatchFinance={type:'supplier',orderId,batchId,poId,max,currency:po.currency||sp.currency||o.currency,party:sp.supplier};currentBatchFinanceToken=makeIdempotencyKey();
 document.getElementById('deliveryFinanceTitle').textContent=b.batchNo+' · Tedarikçiye Öde';document.getElementById('deliveryFinanceSub').textContent=sp.supplier+' için bu partiye düşen ödeme.';
 document.getElementById('deliveryFinanceTotal').textContent=fmtMoneyCur(sp.total||0,currentBatchFinance.currency);document.getElementById('deliveryFinancePaid').textContent=fmtMoneyCur(sp.paid||0,currentBatchFinance.currency);document.getElementById('deliveryFinanceDue').textContent=fmtMoneyCur(max,currentBatchFinance.currency);document.getElementById('deliveryFinanceParty').textContent=sp.supplier;
 document.getElementById('deliveryFinanceAmount').value=max.toFixed(2);document.getElementById('deliveryFinanceDescription').value=b.batchNo+' tedarikçi ödemesi';fillDeliveryFinanceBanks();openModal('deliveryFinanceModal')
}
function fillDeliveryFinanceBanks(){const sel=document.getElementById('deliveryFinanceBank');sel.innerHTML=state.cashAccounts.map(x=>`<option value="${x.id}">${x.name} · ${x.currency} · ${fmtMoneyCur(x.balance,x.currency)}</option>`).join('');document.getElementById('deliveryFinanceFxRate').value='';syncDeliveryFinanceFx()}
function syncDeliveryFinanceFx(){const bank=state.cashAccounts.find(x=>x.id===+document.getElementById('deliveryFinanceBank').value),field=document.getElementById('deliveryFinanceFxField'),inp=document.getElementById('deliveryFinanceFxRate'),source=currentBatchFinance?.currency||'';const cross=!!(bank&&source&&bank.currency!==source);if(field)field.style.display=cross?'block':'none';if(inp){const auto=cross?fxRateBetween(source,bank.currency):1;inp.value=auto>0?Number(auto).toFixed(6):(cross?'':'1')}}
async function completeDeliveryFinance(){return toast('Parti bazlı yeni finans işlemi kapatıldı. Ana Sipariş finans butonlarını kullanın.','warn');/* legacy read-only */
 const f=currentBatchFinance;if(!f)return;const amount=Math.max(0,Number(document.getElementById('deliveryFinanceAmount').value)||0);if(!amount||amount>f.max+0.001)return toast('Tutar parti için izin verilen açık tutarı aşamaz.','warn');
 const btn=document.getElementById('deliveryFinanceSaveBtn');btn.disabled=true;
 const bankId=+document.getElementById('deliveryFinanceBank').value,bank=state.cashAccounts.find(x=>x.id===bankId),source=f.currency||state.orders.find(x=>x.id===f.orderId)?.currency||'EUR',cross=!!(bank&&bank.currency!==source),fxRate=cross?Number(document.getElementById('deliveryFinanceFxRate').value||0):1;if(cross&&!(fxRate>0)){btn.disabled=false;return toast('Farklı para birimi için geçerli kur/parite zorunlu.','warn')}const payload={action:f.type==='customer'?'delivery_batch_collect':'delivery_batch_supplier_pay',orderId:f.orderId,batchId:f.batchId,amount,bankId,fxRate,fxRateConfirmed:!cross||fxRate>0,description:document.getElementById('deliveryFinanceDescription').value.trim(),idempotencyKey:currentBatchFinanceToken};
 if(f.poId)payload.poId=f.poId;const ok=await runServerOperation(payload);btn.disabled=false;if(ok){closeModal('deliveryFinanceModal');currentBatchFinance=null;toast('Parti finans işlemi kaydedildi.','good')}
}
function deliveryBatchFinanceHistory(o,b){
 const rows=(state.cash||[]).filter(c=>Number(c.batchId)===Number(b.id));if(!rows.length)return '';
 return `<details style="margin-top:8px"><summary class="sub" style="cursor:pointer">Parti finans hareketleri (${rows.length})</summary><div class="table-wrap" style="margin-top:6px"><table class="table responsive"><thead><tr><th>Tarih</th><th>Taraf</th><th>Tür</th><th>Tutar</th><th>Durum</th><th></th></tr></thead><tbody>${rows.map(c=>`<tr><td>${c.date||''}</td><td>${c.party||''}</td><td>${c.kind==='delivery_batch_supplier_payment'?'Tedarikçi Ödeme':'Müşteri Tahsilat'}</td><td>${fmtMoneyCur(c.orderAmount??c.amountValue,o.currency)}</td><td>${c.reversed?'<span class="badge b-red">Ters Kayıt</span>':'<span class="badge b-green">Aktif</span>'}</td><td>${!c.reversed&&c.journalId?`<button class="btn red sm" onclick="reverseFinance(${c.journalId})">Ters Kayıt</button>`:''}</td></tr>`).join('')}</tbody></table></div></details>`
}
function deliveryBatchHtml(o,b){
 const cls=b.status==='Teslim Edildi'?'delivered':b.status==='İptal Edildi'?'cancelled':'planned';
 const supplierRows=(b.supplierPayments||[]).map(sp=>`<div class="batch-supplier-row"><div><b>${sp.supplier}</b><div class="sub">${sp.poNo||''} · Parti payı ${fmtMoneyCur(sp.total,o.currency)}</div></div><div><span class="sub">Kalan</span><b>${fmtMoneyCur(sp.due,o.currency)}</b></div><div></div></div>`).join('');
 return `<div class="delivery-batch-card ${cls}"><div class="delivery-batch-head"><div><b>${b.batchNo}</b><div class="sub">Plan: ${b.plannedDate||'—'}${b.deliveredDate?' · Teslim: '+b.deliveredDate:''}${b.description?' · '+b.description:''}</div></div><span class="badge ${batchStatusClass(b.status)}">${b.status}</span></div><div class="delivery-batch-items">${(b.items||[]).map(x=>`<div class="delivery-batch-item"><div><b>${x.name}</b></div><div>${formatQuantity(x.qty||0)} ${x.unit||''}</div><div>${fmtMoneyCur(x.saleTotal||0,o.currency)}</div><div>${x.supplier||''}</div></div>`).join('')}</div><div class="delivery-finance-grid"><div class="delivery-finance-box"><span>Müşteri Tahsilatı</span><b>${fmtMoneyCur(b.customerCollected||0,o.currency)} / ${fmtMoneyCur(b.customerAmount||0,o.currency)}</b><div class="sub">Parti kalan: ${fmtMoneyCur(b.customerDue||0,o.currency)}</div></div><div class="delivery-finance-box"><span>Tedarikçi Ödemeleri</span>${supplierRows||'<div class="sub">Tedarikçi ödeme payı yok.</div>'}</div></div><div class="inline-note" style="margin-top:8px">Bu parti fiziksel teslimat takibidir. Finans işlemleri ana siparişten yapılır.</div><div class="batch-actions">${b.status==='Planlandı'?`<button class="btn sm" onclick="editDeliveryBatch(${o.id},${b.id})">Düzenle</button><button class="btn red sm" onclick="deleteDeliveryBatch(${o.id},${b.id})">Sil</button><button class="btn green sm" onclick="markDeliveryBatchDelivered(${o.id},${b.id})">Partiyi Teslim Et</button><button class="btn red sm" onclick="cancelDeliveryBatch(${o.id},${b.id})">Partiyi İptal Et</button>`:b.status==='Teslim Edildi'?`<button class="btn sm" onclick="undoDeliveryBatchDelivered(${o.id},${b.id})">Teslimatı Geri Al</button>`:''}</div>${b.status==='Teslim Edildi'?'<div class="batch-edit-note">Yanlışlıkla teslim edildi işaretlendiyse “Teslimatı Geri Al” ile parti tekrar Planlandı durumuna döndürülebilir.</div>':b.status==='İptal Edildi'?'<div class="batch-edit-note">İptal edilmiş parti geçmiş bütünlüğü için doğrudan düzenlenemez.</div>':''}${deliveryBatchFinanceHistory(o,b)}</div>`
}
function orderDeliveryBatchesHtml(o){
 const st=orderDeliveryStats(o),batches=o.deliveryBatches||[];
 return `<div class="delivery-batch-section"><div class="section-title"><b>Partili Teslimat Yönetimi</b><div class="actions"><span class="badge b-blue">${batches.filter(x=>x.status!=='İptal Edildi').length} Parti</span>${o.open!==false&&st.remaining>0?`<button class="btn primary sm" onclick="openDeliveryBatchModal(${o.id})">+ Yeni Teslimat Partisi</button>`:''}</div></div><div class="delivery-progress-grid"><div><span>Sipariş Adedi</span><b>${formatQuantity(st.ordered)}</b></div><div><span>Partilere Ayrılan</span><b>${formatQuantity(st.allocated)}</b></div><div><span>Teslim Edilen</span><b>${formatQuantity(st.delivered)} · %${st.pct.toFixed(1)}</b></div><div><span>Henüz Teslim Edilmemiş</span><b>${formatQuantity(st.deliveryRemaining)}</b></div></div><div class="progress" style="margin-bottom:10px"><i style="width:${st.pct}%"></i></div><div class="delivery-batch-list">${batches.length?batches.slice().sort((a,b)=>a.id-b.id).map(b=>deliveryBatchHtml(o,b)).join(''):'<div class="empty">Henüz teslimat partisi oluşturulmadı. Sipariş miktarını parti parti planlayabilirsiniz.</div>'}</div></div>`
}
function orderMiniItemsHtml(o){
 const items=orderItemCatalog(o),visible=items.slice(0,4),more=Math.max(0,items.length-visible.length);
 return `<div class="order-mini-items">${visible.map(x=>`<span class="order-mini-item"><b>${x.name}</b><span>${formatQuantity(x.qty||0)} ${x.unit||''}</span></span>`).join('')}${more?`<span class="order-mini-more">+${more} kalem daha</span>`:''}</div>`
}
let orderViewFilter='active';
function setOrderViewFilter(v){orderViewFilter=v;['active','completed','closed','all'].forEach(x=>document.getElementById('orderFilter'+x.charAt(0).toUpperCase()+x.slice(1))?.classList.toggle('active',x===v));renderOrders()}
function orderMatchesViewFilter(o){const r=dashboardOrderRequest(o),agg=orderAggregateStatus(o);if(orderViewFilter==='all')return true;if(orderViewFilter==='active')return isActiveOperationalOrder(o);if(orderViewFilter==='completed')return isValidOperationalOrder(o)&&agg.key==='delivered';return o.open===false||o.financialCancelled||!r||['lost','cancelled'].includes(r.status)}
function openOrderCustomerQuote(orderId){const o=state.orders.find(x=>x.id===orderId);if(!o)return toast('Sipariş bulunamadı.','warn');go('requests');setTimeout(()=>openRequestTab(o.requestId,'customer'),60)}
function renderOrders(){normalizeStateSchema();sanitizeClientStateInPlace();
 const list=document.getElementById('orderList');if(!list)return;const rows=state.orders.filter(orderMatchesViewFilter);list.innerHTML=rows.map(o=>{const agg=orderAggregateStatus(o),st=orderDeliveryStats(o);return `<article class="request-card order-operation-card ${agg.cls} ${o.open===false?'status-cancelled':''}" data-order="${o.id}"><div class="request-summary" onclick="event.currentTarget.closest('.order-operation-card').classList.toggle('open')" ondblclick="event.preventDefault();event.stopPropagation();return false;"><div class="request-title"><b>${o.no} · ${o.customer}</b><small>${o.request} · Müşteri Siparişi · ${money(o.total,o.currency)}${o.open===false?' · KAPALI/İPTAL':''}</small></div><div class="data-cell"><span>PO Sayısı</span><b>${o.pos.length} Tedarikçi</b></div><div class="data-cell"><span>Operasyon Durumu</span><span class="order-state-label ${agg.key}">${agg.label}</span></div><div class="data-cell hide-md"><span>Teslimat</span><b>${formatQuantity(st.delivered)} / ${formatQuantity(st.ordered)}</b></div><div class="data-cell hide-md"><span>Müşteri Finans</span><b>${Number(o.refundDue||0)>0?'İade '+fmtMoneyCur(o.refundDue,o.currency):Number(o.customerDue??0)>0?'Kalan '+fmtMoneyCur(o.customerDue,o.currency):'Tahsilat Kapalı'}</b></div><div class="chev">⌄</div>${orderMiniItemsHtml(o)}</div><div class="request-body"><div class="inner"><div class="request-content"><div class="actions" style="margin-bottom:12px"><button class="btn sm" onclick="go('requests')">Talebi Aç</button><button class="btn sm" onclick="openOrderCustomerQuote(${o.id})">Müşteri Teklifi</button><button class="btn sm" onclick="go('accounts')">Müşteri Carisi</button><button class="btn sm" onclick="go('cash')">Kasa & Banka</button><button class="btn sm" onclick="go('docs')">Ticari Evraklar</button>${o.open!==false&&st.remaining>0?`<button class="btn primary sm" onclick="openDeliveryBatchModal(${o.id})">+ Teslimat Partisi</button>`:''}</div>${orderFinanceSummaryHtml(o)}<div class="order-grid">${o.pos.map(po=>poHtml(po,o)).join('')}</div>${paymentScheduleHtml(o)}${orderDeliveryBatchesHtml(o)}${orderPaymentHistoryHtml(o)}${orderSupplierPaymentHistoryHtml(o)}</div></div></div></article>`}).join('')||`<div class="panel empty">${orderViewFilter==='active'?'Aktif sipariş bulunmuyor.':'Bu filtrede sipariş bulunmuyor.'}</div>`
}
function normalizedPartyName(v){return String(v||'').trim().toLocaleUpperCase('tr-TR').replace(/\s+/g,' ')}
function poStatusClass(s){return {'Hazırlanıyor':'status-preparing','Üretimde':'status-production','Sevkiyata Hazır':'status-ready','Kısmi Teslimat':'status-ready','Yolda':'status-road','Teslim Edildi':'status-delivered'}[s]||'status-preparing'}
function orderAggregateStatus(o){
 const ds=orderDeliveryStats(o);
 if(ds.ordered>0&&ds.delivered>=ds.ordered)return {key:'delivered',cls:'order-delivered',label:'Teslim Edildi'};
 if(ds.delivered>0)return {key:'partial',cls:'order-ready',label:'Kısmi Teslimat'};
 const sts=(o.pos||[]).map(p=>p.status);
 if(sts.length&&sts.every(s=>s==='Teslim Edildi'))return {key:'delivered',cls:'order-delivered',label:'Teslim Edildi'};
 if(sts.some(s=>s==='Kısmi Teslimat'))return {key:'partial',cls:'order-ready',label:'Kısmi Teslimat'};
 if(sts.some(s=>s==='Yolda'))return {key:'road',cls:'order-road',label:'Yolda'};
 if(sts.some(s=>s==='Sevkiyata Hazır'))return {key:'ready',cls:'order-ready',label:'Sevkiyata Hazır'};
 if(sts.some(s=>s==='Üretimde'))return {key:'production',cls:'order-production',label:'Üretimde'};
 return {key:'preparing',cls:'order-preparing',label:'Hazırlanıyor'}
}
function supplierAccountDebt(name,currency=null){
 const matches=state.accounts.filter(a=>a.type==='supplier'&&normalizedPartyName(a.name)===normalizedPartyName(name)&&(!currency||a.currency===currency));
 return matches.reduce((sum,a)=>sum+Math.max(0,-Number(a.requestOpen||0)),0)
}
function supplierPoOutstanding(supplier){
 const norm=normalizedPartyName(supplier),byCurrency={};state.orders.filter(isValidOperationalOrder).forEach(o=>(o.pos||[]).forEach(po=>{if(normalizedPartyName(po.supplier)!==norm)return;const cur=po.currency||o.currency||dashboardOrderRequest(o)?.currency||'EUR';byCurrency[cur]=(byCurrency[cur]||0)+Math.max(0,Number(po.due||0))}));return byCurrency
}
function poHtml(po,o=null){const statuses=['Hazırlanıyor','Üretimde','Sevkiyata Hazır','Kısmi Teslimat','Yolda','Teslim Edildi'];const orderLocked=!o||!isValidOperationalOrder(o),batchLocked=!!(o?.deliveryBatches||[]).some(b=>b.status!=='İptal Edildi');const supplierColor=supplierIdentityColor(o,po.supplier);return `<div class="po-card supplier-identity-card ${poStatusClass(po.status)}" style="--supplier-color:${supplierColor}"><div class="po-head"><div><h3>${supplierIdentityKey(po.supplier,supplierColor)}${po.no} · ${esc(po.supplier)}</h3><div class="sub">Rev-${String(po.revision||0).padStart(2,'0')}</div><div class="sub">${po.payment} · ${po.currency||o?.currency||'EUR'} · Varsayılan: ${po.bank}</div></div><div><div class="po-total">${money(po.total,po.currency||o?.currency||'EUR')}</div><span class="badge ${po.status==='Teslim Edildi'?'b-green':po.status==='Sevkiyata Hazır'?'b-yellow':po.status==='Yolda'?'b-purple':'b-blue'}">${po.status}</span></div></div><div class="status-row">${statuses.map(s=>{const locked=orderLocked||(batchLocked&&['Kısmi Teslimat','Teslim Edildi'].includes(s));return `<button class="status-pill ${po.status===s?'active':''}" data-status="${s}" ${locked?'disabled title="Partili teslimat aktif; durum partilerden hesaplanır"':''} onclick="${locked?'return false':`setPoStatus(${po.id},'${s}',${Number(o?.id||0)})`}">${s}${locked?' 🔒':''}</button>`}).join('')}</div><div class="sub">Üretim %${po.production}</div><div class="progress" style="margin:6px 0 10px"><i style="width:${po.production}%"></i></div><div class="quote-kpis" style="grid-template-columns:1fr 1fr"><div class="quote-kpi"><span>Ödenen</span><b>${money(po.paid,po.currency||o?.currency||'EUR')}</b></div><div class="quote-kpi"><span>Kalan Borç</span><b>${money(po.due,po.currency||o?.currency||'EUR')}</b></div></div><div class="actions"><button class="btn sm" onclick="go('accounts')">Tedarikçi Carisi</button>${orderLocked?'<span class="badge b-red">Salt Okunur</span>':`<button class="btn sm" onclick="revisePo(${po.id},${Number(o?.id||0)})">PO Revizyonu</button>`}</div></div>`}
async function revisePo(id,orderId=0,poNo=''){
 const owner=state.orders.find(o=>Number(o.id)===Number(orderId))||state.orders.find(o=>(o.pos||[]).some(p=>Number(p.id)===Number(id)||String(p.no||'')===String(poNo||'')));
 const po=(owner?.pos||[]).find(p=>Number(p.id)===Number(id)||String(p.no||'')===String(poNo||''));orderId=Number(orderId||owner?.id||0);poNo=String(poNo||po?.no||'');
 const note=await appPrompt('PO revizyon açıklaması','Termin / fiyat / operasyon revizyonu','PO Revizyonu');if(note===null)return;
 if(await runServerOperation({action:'po_revision',poId:Number(id),orderId,poNo,note:note.trim(),idempotencyKey:makeIdempotencyKey()}))toast('PO revizyonu oluşturuldu.','good')
}
function captureOrderUi(){return {scrollY:window.scrollY,opened:[...document.querySelectorAll('.order-operation-card.open')].map(x=>Number(x.dataset.order||0)).filter(Boolean)}}
function restoreOrderUi(ui){if(!ui)return;requestAnimationFrame(()=>{(ui.opened||[]).forEach(id=>document.querySelector(`.order-operation-card[data-order="${id}"]`)?.classList.add('open'));window.scrollTo({top:Number(ui.scrollY||0),behavior:'auto'})})}
async function setPoStatus(id,s,orderId=0,poNo=''){
 const ui=captureOrderUi();
 const localOwner=state.orders.find(o=>Number(o.id)===Number(orderId))||state.orders.find(o=>(o.pos||[]).some(p=>Number(p.id)===Number(id)));
 const localPo=(localOwner?.pos||[]).find(p=>Number(p.id)===Number(id));
 poNo=String(poNo||localPo?.no||'');orderId=Number(orderId||localOwner?.id||0);
 if(await runServerOperation({action:'po_status',poId:Number(id),orderId,poNo,status:s,idempotencyKey:makeIdempotencyKey()})){
  const owner=state.orders.find(o=>Number(o.id)===Number(orderId))||state.orders.find(o=>(o.pos||[]).some(p=>Number(p.id)===Number(id)||String(p.no||'')===poNo));
  const po=(owner?.pos||[]).find(x=>Number(x.id)===Number(id)||String(x.no||'')===poNo);restoreOrderUi(ui);toast((po?.no||poNo||'PO')+' durumu: '+s,'good')
 }else restoreOrderUi(ui)
}
let currentPo=null,currentPoOrderId=0,currentPoNo='';
let currentSupplierAccount=null;
function payFirstSupplierPo(name){const norm=normalizedPartyName(name),openPos=[];state.orders.filter(isValidOperationalOrder).forEach(o=>(o.pos||[]).forEach(po=>{if(normalizedPartyName(po.supplier)===norm&&Number(po.due||0)>0)openPos.push({po,o})}));openPos.sort((a,b)=>((a.po.currency||a.o.currency)===activeAccountCurrency?-1:1)-((b.po.currency||b.o.currency)===activeAccountCurrency?-1:1));if(openPos.length){currentSupplierAccount=null;return payPo(openPos[0].po.id,openPos[0].o.id,openPos[0].po.no||'')}const account=state.accounts.find(a=>a.type==='supplier'&&normalizedPartyName(a.name)===norm&&Number(a.requestOpen||0)<0&&(a.currency===activeAccountCurrency||!activeAccountCurrency));if(account){currentSupplierAccount=account;currentPoOrderId=0;currentPoNo='';currentPo={id:null,__accountOnly:true,no:'CARİ ÖDEME',supplier:account.name,total:Math.abs(Number(account.total||account.requestOpen||0)),paid:Math.max(0,Math.abs(Number(account.total||0))-Math.abs(Number(account.requestOpen||0))),due:Math.abs(Number(account.requestOpen||0)),currency:account.currency,payment:'Cari ödeme'};fillPaymentModal(currentPo,account.currency);openModal('paymentModal');return}return toast('Bu tedarikçi için açık sipariş veya cari borcu bulunamadı.','warn')}
function fillPaymentModal(po,currency){document.getElementById('payTitle').textContent=po.no+' · '+po.supplier;document.getElementById('payTotal').textContent=fmtMoneyCur(po.total,currency);document.getElementById('payPaid').textContent=fmtMoneyCur(po.paid,currency);document.getElementById('payDue').textContent=fmtMoneyCur(po.due,currency);document.getElementById('payTerms').textContent=po.payment||'—';document.getElementById('payBasis').value='total';document.getElementById('payPercent').value='50';document.getElementById('payRef').value=po.no||'';document.getElementById('payDesc').value='Tedarikçiye ödeme';const sel=document.getElementById('payBank'),accounts=state.cashAccounts.filter(x=>x.currency===currency);sel.innerHTML=accounts.length?accounts.map(x=>`<option value="${x.id}">${x.name} · ${fmtMoneyCur(x.balance,x.currency)}</option>`).join(''):`<option value="">${currency} para biriminde Kasa/Banka hesabı yok</option>`;sel.disabled=!accounts.length;const owner=state.orders.find(o=>Number(o.id)===Number(currentPoOrderId)),bs=document.getElementById('payBatch');if(bs)bs.innerHTML='<option value="">Genel Sipariş Ödemesi</option>'+((owner?.deliveryBatches||[]).filter(b=>b.status!=='İptal Edildi').map(b=>`<option value="${b.id}">${esc(b.batchNo)} · ${esc(b.status)}</option>`).join(''));syncSupplierPayFromPercent()}
function syncSupplierPayFromPercent(){if(!currentPo)return;const pct=Math.max(0,Math.min(100,Number(document.getElementById('payPercent')?.value)||0)),base=document.getElementById('payBasis')?.value==='remaining'?Number(currentPo.due||0):Number(currentPo.total||0),amount=Math.min(Number(currentPo.due||0),base*pct/100);document.getElementById('payAmount').value=amount.toFixed(2)}
function syncSupplierPayFromAmount(){if(!currentPo)return;const amount=Math.max(0,Number(document.getElementById('payAmount')?.value)||0),base=document.getElementById('payBasis')?.value==='remaining'?Number(currentPo.due||0):Number(currentPo.total||0);document.getElementById('payPercent').value=base?Math.min(100,amount/base*100).toFixed(2):'0'}
function selectOrderPaymentSupplier(v){const [oid,pid]=String(v||'').split(':').map(Number),owner=state.orders.find(o=>Number(o.id)===oid),po=(owner?.pos||[]).find(p=>Number(p.id)===pid);if(!owner||!po)return;currentSupplierAccount=null;currentPo=po;currentPoOrderId=owner.id;currentPoNo=po.no||'';fillPaymentModal(po,po.currency||owner.currency||'EUR')}
function openOrderSupplierPayment(orderId){const owner=state.orders.find(o=>Number(o.id)===Number(orderId));if(!owner||!isValidOperationalOrder(owner))return toast('Geçerli açık sipariş bulunamadı.','warn');const rows=(owner.pos||[]).filter(p=>Number(p.due||0)>0);if(!rows.length)return toast('Açık tedarikçi borcu bulunmuyor.','warn');document.getElementById('paySupplierField').style.display='block';document.getElementById('paySupplierSelect').innerHTML=rows.map(p=>`<option value="${owner.id}:${p.id}">${esc(p.supplier)} · ${esc(p.no)} · Kalan ${fmtMoneyCur(p.due,p.currency||owner.currency||'EUR')}</option>`).join('');currentSupplierAccount=null;currentPo=rows[0];currentPoOrderId=owner.id;currentPoNo=rows[0].no||'';fillPaymentModal(currentPo,currentPo.currency||owner.currency||'EUR');openModal('paymentModal')}
function payPo(id,orderId=0,poNo=''){const owner0=state.orders.find(o=>Number(o.id)===Number(orderId))||state.orders.find(o=>(o.pos||[]).some(p=>Number(p.id)===Number(id)||String(p.no||'')===String(poNo||'')));if(!owner0||!isValidOperationalOrder(owner0))return toast('Kapalı, iptal, kayıp veya geçersiz sipariş PO’suna ödeme yapılamaz.','warn');currentSupplierAccount=null;currentPo=(owner0.pos||[]).find(x=>Number(x.id)===Number(id)||String(x.no||'')===String(poNo||''));if(!currentPo)return toast('PO bulunamadı.','warn');currentPoOrderId=Number(owner0.id||0);currentPoNo=String(currentPo.no||poNo||'');document.getElementById('paySupplierField').style.display='none';const cur=currentPo.currency||owner0.currency||'EUR';fillPaymentModal(currentPo,cur);openModal('paymentModal')}
async function completePayment(){const amount=Math.max(0,+document.getElementById('payAmount').value||0);if(!currentPo||amount<=0)return toast('Geçerli bir ödeme tutarı girin.','warn');const supplier=currentPo.supplier||currentSupplierAccount?.name,owner=state.orders.find(o=>Number(o.id)===Number(currentPoOrderId))||state.orders.find(o=>(o.pos||[]).some(p=>Number(p.id)===Number(currentPo.id)||String(p.no||'')===String(currentPoNo||''))),r=state.requests.find(x=>x.id===owner?.requestId),cur=currentSupplierAccount?.currency||currentPo.currency||owner?.currency||r?.currency||'EUR',bankId=+document.getElementById('payBank').value,batchId=+document.getElementById('payBatch')?.value||0;const party=currentSupplierAccount||state.accounts.find(x=>x.type==='supplier'&&normalizedPartyName(x.name)===normalizedPartyName(supplier)&&x.currency===cur);if(!party)return toast(`${supplier||'Tedarikçi'} için ${cur} cari hesabı bulunamadı. Cari Hesaplar bölümünü kontrol edin.`,'warn');const bank=state.cashAccounts.find(x=>Number(x.id)===Number(bankId));if(!bank)return toast(`${cur} para biriminde ödeme yapılacak Kasa/Banka hesabı seçin.`,'warn');if(bank.currency!==cur)return toast(`PO ${cur}, seçilen hesap ${bank.currency}. Aynı para birimindeki hesabı seçin veya önce Virman yapın.`,'warn');if(amount>Number(currentPo.due||0)+0.001)return toast('Ödeme PO kalan borcundan büyük olamaz.','warn');const ok=await runServerOperation({action:'cash_movement',mode:'payment',bankId,bankName:bank.name,bankCurrency:bank.currency,partyId:party.id,amount,poId:currentPo.__accountOnly?0:(currentPo.id||0),orderId:currentPo.__accountOnly?0:Number(owner?.id||currentPoOrderId||0),poNo:currentPo.__accountOnly?'':String(currentPo.no||currentPoNo||''),request:owner?.request||r?.no||party.request||'',ref:document.getElementById('payRef').value.trim()||currentPo.no||'ÖDEME',description:document.getElementById('payDesc').value.trim()||'Tedarikçiye ödeme',batchId,idempotencyKey:makeIdempotencyKey()});if(ok){closeModal('paymentModal');toast('Tedarikçi ödemesi kaydedildi.','good');currentSupplierAccount=null;currentPo=null;currentPoOrderId=0;currentPoNo=''}}
let activeEntityName='',activeEntityKey='',activeAccountCurrency='EUR';
function persistAccounts(){/* V3.3: iş verisi yalnız MySQL/state üzerinden kalıcıdır. */}
function restoreAccounts(){/* V3.3: stale local cari verisi geri yüklenmez. */}
function ensurePartyKeys(){
 const canonical=new Map();
 // Same legal party is grouped across EUR/USD/TRY. Tax no is strongest key;
 // otherwise exact normalized name + type is used for legacy records.
 (state.accounts||[]).forEach(a=>{
   const type=a.type||'customer',tax=String(a.taxNo||'').trim(),name=normalizedPartyName(a.name);
   const sig=tax?`${type}|TAX|${tax}`:`${type}|NAME|${name}`;
   if(!canonical.has(sig))canonical.set(sig,a.partyKey||('pty-'+String(a.id||Date.now())));
 });
 (state.accounts||[]).forEach(a=>{
   const type=a.type||'customer',tax=String(a.taxNo||'').trim(),name=normalizedPartyName(a.name);
   const sig=tax?`${type}|TAX|${tax}`:`${type}|NAME|${name}`;
   a.partyKey=canonical.get(sig)||a.partyKey||('pty-'+String(a.id||Date.now()))
 });
 (state.requests||[]).forEach(r=>{
   const matches=(state.accounts||[]).filter(x=>x.type!=='supplier'&&normalizedPartyName(x.name)===normalizedPartyName(r.customer));
   if(matches.length)r.customerPartyKey=matches[0].partyKey
 })
}
function groupedAccounts(){
 ensurePartyKeys();const map={};
 state.accounts.forEach(a=>{const key=a.partyKey||((a.type||'customer')+'|'+normalizedPartyName(a.name));if(!map[key])map[key]={partyKey:key,name:a.name,type:a.type,accounts:{},profile:{taxNo:a.taxNo||'',contact:a.contact||'',phone:a.phone||'',email:a.email||'',website:a.website||'',address:a.address||'',country:a.country||'',note:a.note||'',serviceRole:a.serviceRole||'',tags:entityTagNames(a.tags)}};const p=map[key].profile;['taxNo','contact','phone','email','website','address','country','dueDate','description','note','serviceRole'].forEach(k=>{if(!p[k]&&a[k])p[k]=a[k]});p.tags=[...new Set([...(p.tags||[]),...entityTagNames(a.tags)])];map[key].accounts[a.currency]=a});
 return Object.values(map)
}
function fmtMoneyCur(n,c){const sym={EUR:'€',USD:'$',TRY:'₺'}[c]||c,value=Number(n||0),sign=value<0?'-':'';return sign+sym+' '+formatMoneyNumber(Math.abs(value))}
let accountFilter='all',accountBalanceFilter='all';
function setAccountFilter(f){accountFilter=f;const map={all:'All',customer:'Customer',supplier:'Supplier',customs:'Customs',freight:'Freight',service:'Service',public:'Public',private:'Private'};Object.entries(map).forEach(([key,suffix])=>document.getElementById('accFilter'+suffix)?.classList.toggle('active',f===key));renderAccounts()} function accountBalancesByCurrency(g){
 const out={EUR:0,USD:0,TRY:0};Object.values(g?.accounts||{}).forEach(a=>{const c=String(a.currency||'EUR').toUpperCase();out[c]=(out[c]||0)+Number(a.requestOpen||0)});return out
}
function accountHasDebtorBalance(g){return Object.values(accountBalancesByCurrency(g)).some(v=>v>0.001)}
function accountHasCreditorBalance(g){return Object.values(accountBalancesByCurrency(g)).some(v=>v<-0.001)}
function accountHasAnyBalance(g){return Object.values(accountBalancesByCurrency(g)).some(v=>Math.abs(v)>0.001)}
function accountNetBalance(g){const b=accountBalancesByCurrency(g);return convertCurrency(b.EUR,'EUR','EUR')+convertCurrency(b.USD,'USD','EUR')+convertCurrency(b.TRY,'TRY','EUR')} function setAccountBalanceFilter(f){accountBalanceFilter=f;['All','Debtor','Creditor'].forEach(s=>document.getElementById('accBalance'+s)?.classList.toggle('active',f===s.toLowerCase()));renderAccounts()}
function accountTypeLabel(t){return {customer:'Müşteri',supplier:'Tedarikçi',public:'Resmi Kurum',private:'Özel Kurum',customs:'Gümrükçü',freight:'Nakliyeci',service:'Hizmet Sağlayıcı'}[t]||t}
function accountTypeBadge(t){return {customer:'b-green',supplier:'b-yellow',public:'b-blue',private:'b-purple',customs:'b-purple',freight:'b-blue',service:'b-gray'}[t]||'b-blue'}
let newAccountLinkPartyKey='';
function openAccountCreateModal(partyKey='',preferredCurrency='EUR'){
 newAccountLinkPartyKey=partyKey||'';
 const g=partyKey?accountGroupByKey(partyKey):null;
 document.getElementById('accountCreateTitle').textContent=g?'Döviz Hesabı Ekle':'Yeni Cari Ekle';
 document.getElementById('accountCreateSub').textContent=g?`${g.name} ana cari kartına yeni para birimi alt hesabı ekleyin. Mevcut hesaplar: ${Object.keys(g.accounts).join(', ')||'—'}`:'Müşteri, tedarikçi, gümrükçü, nakliyeci, hizmet sağlayıcı, resmi veya özel kurum cari kartı oluşturun.';
 document.getElementById('accNewName').value=g?.name||'';
 document.getElementById('accNewType').value=g?.type||'customer';document.getElementById('accNewRole').value=g?.profile?.serviceRole||'';
 document.getElementById('accNewCurrency').value=preferredCurrency||'EUR';
 document.getElementById('accNewBalance').value='0';
 document.getElementById('accNewRequest').value='';
 document.getElementById('accNewTaxNo').value=g?.profile?.taxNo||'';
 document.getElementById('accNewContact').value=g?.profile?.contact||'';
 document.getElementById('accNewPhone').value=g?.profile?.phone||'';
 document.getElementById('accNewEmail').value=g?.profile?.email||'';
 document.getElementById('accNewWebsite').value=g?.profile?.website||'';
 document.getElementById('accNewAddress').value=g?.profile?.address||'';
 document.getElementById('accNewCountry').value=g?.profile?.country||'';
 document.getElementById('accNewDueDate').value=g?.profile?.dueDate||'';
 document.getElementById('accNewDescription').value=g?.profile?.description||'';
 document.getElementById('accNewNote').value=g?.profile?.note||'';
 document.getElementById('accNewName').readOnly=!!g;
 document.getElementById('accNewType').disabled=!!g;
 openModal('accountCreateModal')
}
function existingPartyForNewAccount(name,type,taxNo){
 if(newAccountLinkPartyKey)return accountGroupByKey(newAccountLinkPartyKey);
 const norm=normalizedPartyName(name);
 if(taxNo){
  const a=state.accounts.find(x=>String(x.taxNo||'').trim()===taxNo&&x.type===type);
  if(a)return accountGroupByKey(a.partyKey)
 }
 return groupedAccounts().find(g=>g.type===type&&normalizedPartyName(g.name)===norm)||null
}
async function saveNewAccount(){
 const name=document.getElementById('accNewName').value.trim(),type=document.getElementById('accNewType').value,serviceRole=document.getElementById('accNewRole')?.value||'',currency=document.getElementById('accNewCurrency').value,balance=parseMoneyInput(document.getElementById('accNewBalance').value),request=document.getElementById('accNewRequest').value.trim(),taxNo=document.getElementById('accNewTaxNo').value.trim(),contact=document.getElementById('accNewContact').value.trim(),phone=document.getElementById('accNewPhone').value.trim(),email=document.getElementById('accNewEmail').value.trim(),website=document.getElementById('accNewWebsite').value.trim(),address=document.getElementById('accNewAddress').value.trim(),country=document.getElementById('accNewCountry').value.trim(),dueDate=document.getElementById('accNewDueDate').value,description=document.getElementById('accNewDescription').value.trim(),note=document.getElementById('accNewNote').value.trim();
 if(!name)return toast('Firma / kurum adı zorunlu.','warn');
 const existingGroup=existingPartyForNewAccount(name,type,taxNo);
 if(existingGroup?.accounts?.[currency])return toast(`${existingGroup.name} için ${currency} cari hesabı zaten mevcut.`,'warn');
 const newId=Math.max(0,...state.accounts.map(a=>a.id||0))+1;
 const partyKey=existingGroup?.partyKey||('pty-'+newId+'-'+Date.now().toString(36));
 const canonicalName=existingGroup?.name||name;
 const profile=existingGroup?.profile||{};
 const account={id:newId,partyKey,name:canonicalName,type,serviceRole,currency,requestOpen:balance,request:request||'Manuel Cari',taxNo:taxNo||profile.taxNo||'',contact:contact||profile.contact||'',phone:phone||profile.phone||'',email:email||profile.email||'',website:website||profile.website||'',address:address||profile.address||'',country:country||profile.country||'',dueDate:dueDate||profile.dueDate||'',description:description||profile.description||'',note:note||profile.note||'',entries:[]};
 if(Math.abs(balance)>0.001){const fx=normalizedFxSnapshot(state.fx);account.entries.push({txnId:'txn-'+Date.now().toString(36)+'-'+Math.random().toString(36).slice(2,7),date:todayISO(),createdAt:new Date().toISOString(),createdBy:currentUser?.name||'Kullanıcı',ref:request||'AÇILIŞ',desc:'Açılış bakiyesi',debit:balance>0?balance:0,credit:balance<0?Math.abs(balance):0,...(fx?{fxSnapshot:fx}:{})});}
 state.accounts.push(account);
 activeEntityKey=partyKey;activeEntityName=canonicalName;activeAccountCurrency=currency;accountFilter='all';newAccountLinkPartyKey='';
 document.getElementById('accNewName').readOnly=false;document.getElementById('accNewType').disabled=false;
 closeModal('accountCreateModal');persistAccounts();
 if(type==='supplier'){syncSupplierDirectoryFromAccounts();renderSupplierDirectoryOptions()}
 setAccountFilter('all');refreshTagFilters();updateDashboard();
 const saved=await saveDbState(true);
 if(!saved)return toast('Cari alt hesabı veritabanına kaydedilemedi: '+(window.lastDbSaveError||'DB hatası'),'warn');
 if(costQuickAccountContext){
   const ctx=costQuickAccountContext;costQuickAccountContext=null;
   if(ctx.returnTo==='comparisonExpenseRealizeModal'){
     refreshComparisonExpensePartySelect(newId);
     document.getElementById('comparisonExpenseRealizeModal')?.classList.add('show','nested-modal');
     document.body.style.overflow='hidden'
   }else{
     normalizeStateSchema();renderRequests();
     setTimeout(()=>{openRequestTab(ctx.rid,'cost');toast(canonicalName+' maliyet sağlayıcı listesine eklendi.','good')},40)
   }
 }else{
   renderAccounts();showAccountGroup(partyKey)
 }
 toast(existingGroup?`${canonicalName} için ${currency} alt cari hesabı eklendi.`:`${canonicalName} cari kartı oluşturuldu.`,'good')
}
function clearAccountSearch(){const e=document.getElementById('accountNameSearch');if(e)e.value='';renderAccounts()}
function accountGroupKey(g){return g?.partyKey||String(Object.values(g?.accounts||{})[0]?.id||'')}
function accountGroupByKey(key){return groupedAccounts().find(g=>String(accountGroupKey(g))===String(key))||null}
function renderAccounts(){normalizeStateSchema();sanitizeClientStateInPlace();
 const allGroups=groupedAccounts(),search=String(document.getElementById('accountNameSearch')?.value||'').trim().toLocaleLowerCase('tr-TR'),tagCat=document.getElementById('accountTagCategoryFilter')?.value||'',tagFilter=document.getElementById('accountTagFilter')?.value||'',groups=allGroups.filter(g=>(accountFilter==='all'||g.type===accountFilter)&&(!search||String(g.name||'').toLocaleLowerCase('tr-TR').includes(search)||String(g.profile?.contact||'').toLocaleLowerCase('tr-TR').includes(search)||String(g.profile?.taxNo||'').toLocaleLowerCase('tr-TR').includes(search))&&(!tagCat||entityTagObjects(g.profile.tags).some(t=>t.category===tagCat))&&(!tagFilter||entityTagNames(g.profile.tags).includes(tagFilter))&&(accountBalanceFilter==='all'||(accountBalanceFilter==='debtor'&&accountHasDebtorBalance(g))||(accountBalanceFilter==='creditor'&&accountHasCreditorBalance(g)))),cards=document.getElementById('accountCards');
 const countEl=document.getElementById('accountSearchCount');if(countEl)countEl.textContent=search?`${groups.length} sonuç`:`${groups.length} cari`;
 if(activeEntityName&&!groups.some(g=>g.name===activeEntityName))activeEntityName='';
 cards.innerHTML=groups.length?groups.map(g=>{
   const groupKey=accountGroupKey(g),isOpen=activeEntityKey?String(activeEntityKey)===String(groupKey):activeEntityName===g.name;
   return `<div class="entity-card account-entity-card compact ${isOpen?'active open':''}" data-account-key="${escAttr(groupKey)}" onclick="toggleAccountCardByKey('${groupKey}',event)" ondblclick="event.preventDefault();event.stopPropagation();return false;">
     <div class="entity-head account-compact-head">
       <div class="account-compact-title"><b>${g.name}</b><small>${accountTypeLabel(g.type)}${g.profile?.serviceRole?' · '+esc(g.profile.serviceRole):''}${g.profile?.country?' · '+esc(g.profile.country):''}</small></div>
       <div class="account-compact-meta"><span class="badge ${accountTypeBadge(g.type)}">${accountTypeLabel(g.type)}</span><span class="account-card-chevron">›</span></div>
     </div>
     <div class="account-compact-balances">${['EUR','USD','TRY'].map(c=>{const ac=g.accounts[c],val=ac?(ac.type==='supplier'?-Math.max(supplierAccountDebt(g.name,c),Math.max(0,-Number(ac.requestOpen||0))):Number(ac.requestOpen||0)):null;return `<div class="account-mini-balance ${ac?'':'zero'}"><span>${c}</span><b>${ac?fmtMoneyCur(val,c):'—'}</b></div>`}).join('')}</div>
     <div class="account-compact-foot"><span>${entityTagObjects(g.profile.tags).map(t=>`<em><i style="background:${t.color}"></i>${t.name}</em>`).join('')||'<em>Etiket yok</em>'}</span><small>Detay / Ekstre →</small></div>
   </div>`
 }).join(''):`<div class="empty">${accountFilter==='all'?'Cari':accountTypeLabel(accountFilter)} kartı bulunamadı.</div>`;
 const detail=document.getElementById('accountDetail');
 if(activeEntityKey||activeEntityName)showAccountGroup(activeEntityKey||activeEntityName);
 else if(detail)detail.innerHTML='<div class="empty">Cari kart detayını görmek için bir karta tıklayın.</div>'
}
let editingAccountName='',editingAccountId=null;
async function openAccountEdit(name){
 const g=groupedAccounts().find(x=>String(x.partyKey)===String(name))||groupedAccounts().find(x=>x.name===name);if(!g)return;
 if(!await acquireEntityLock('account',g.partyKey||accountGroupKey(g),'accountEditModal'))return;
 editingAccountName=name;
 const account=g.accounts[activeAccountCurrency]||Object.values(g.accounts)[0],p=g.profile||{};
 editingAccountId=account?.id??null;
 document.getElementById('accEditName').value=g.name;
 document.getElementById('accEditType').value=g.type;document.getElementById('accEditRole').value=g.profile?.serviceRole||Object.values(g.accounts||{})[0]?.serviceRole||'';
 document.getElementById('accEditCurrency').value=account?.currency||activeAccountCurrency||'EUR';
 document.getElementById('accEditRequest').value=account?.request||'';
 document.getElementById('accEditTaxNo').value=p.taxNo||'';
 document.getElementById('accEditContact').value=p.contact||'';
 document.getElementById('accEditPhone').value=p.phone||'';
 document.getElementById('accEditEmail').value=p.email||'';
 document.getElementById('accEditWebsite').value=p.website||'';
 document.getElementById('accEditAddress').value=p.address||'';
 document.getElementById('accEditCountry').value=p.country||'';
 document.getElementById('accEditDueDate').value=p.dueDate||'';
 document.getElementById('accEditDescription').value=p.description||'';
 document.getElementById('accEditNote').value=p.note||'';
 openModal('accountEditModal')
}
async function saveAccountEdit(){
 const name=document.getElementById('accEditName').value.trim(),type=document.getElementById('accEditType').value,serviceRole=document.getElementById('accEditRole')?.value||'',currency=document.getElementById('accEditCurrency').value,request=document.getElementById('accEditRequest').value.trim(),taxNo=document.getElementById('accEditTaxNo').value.trim(),contact=document.getElementById('accEditContact').value.trim(),phone=document.getElementById('accEditPhone').value.trim(),email=document.getElementById('accEditEmail').value.trim(),website=document.getElementById('accEditWebsite').value.trim(),address=document.getElementById('accEditAddress').value.trim(),country=document.getElementById('accEditCountry').value.trim(),dueDate=document.getElementById('accEditDueDate').value,description=document.getElementById('accEditDescription').value.trim(),note=document.getElementById('accEditNote').value.trim();
 if(!name)return toast('Cari adı zorunlu.','warn');
 const edited=state.accounts.find(a=>a.id===editingAccountId);
 if(!edited)return toast('Düzenlenecek cari hesabı bulunamadı.','warn');
 const currencyConflict=state.accounts.find(a=>a.id!==edited.id&&a.partyKey===edited.partyKey&&a.currency===currency);
 if(currencyConflict)return toast(editingAccountName+' için '+currency+' cari hesabı zaten mevcut. Önce mevcut hesabı kullanın veya silin.','warn');
 const oldName=editingAccountName,oldCurrency=edited.currency,partyKey=edited.partyKey;
 state.accounts.filter(a=>(partyKey&&a.partyKey===partyKey)||(!partyKey&&a.name===oldName)).forEach(a=>{
   a.name=name;a.type=type;a.serviceRole=serviceRole;
   Object.assign(a,{taxNo,contact,phone,email,website,address,country,dueDate,description,note})
 });
 edited.currency=currency;
 if(request)edited.request=request;
 if(activeEntityName===oldName)activeEntityName=name;
 if(activeAccountCurrency===oldCurrency)activeAccountCurrency=currency;
 editingAccountName='';editingAccountId=null;
 closeModal('accountEditModal');
 persistAccounts();syncSupplierDirectoryFromAccounts();renderSupplierDirectoryOptions();renderAccounts();refreshTagFilters();updateDashboard();if(!await saveDbState(true))return toast('Cari kart güncellemesi kaydedilemedi: '+(window.lastDbSaveError||'DB hatası'),'warn');
 toast('Cari kartı ve seçili '+oldCurrency+' hesabı güncellendi.','good')
}
async function deleteAccountGroup(ref){const g=groupedAccounts().find(x=>String(x.partyKey)===String(ref))||groupedAccounts().find(x=>x.name===ref);if(!g)return;const hasEntries=Object.values(g.accounts).some(a=>(a.entries||[]).length>0);if(accountHasAnyBalance(g)||hasEntries)return toast('Herhangi bir EUR/USD/TRY bakiyesi veya hareket geçmişi olan cari silinemez. Önce tüm döviz hesaplarını kapatın; geçmiş hareketler korunmalıdır.','warn');if(!await appConfirm(g.name+' cari kartı silinsin mi?','Cari Kart Silme'))return;state.accounts=state.accounts.filter(a=>a.partyKey!==g.partyKey);state.supplierDirectory=(state.supplierDirectory||[]).filter(s=>normalizedPartyName(s.name)!==normalizedPartyName(g.name));if(activeEntityKey===g.partyKey){activeEntityKey='';activeEntityName=''}persistAccounts();renderAccounts();renderSupplierDirectoryOptions();if(!await saveDbState(true))return toast('Cari kart silme kaydedilemedi: '+(window.lastDbSaveError||'DB hatası'),'warn');toast(g.name+' cari kartı silindi.','good')}



function normalizedFxSnapshot(src){
 const fx=src&&typeof src==='object'?src:{},usd=Number(fx.usdTry||0),eur=Number(fx.eurTry||0);
 if(!(usd>0)||!(eur>0))return null;
 return {usdTry:usd,eurTry:eur,eurUsd:eur/usd,source:fx.source||'TCMB',updated:fx.updated||'',capturedAt:fx.capturedAt||'',rateDate:fx.rateDate||fx.effectiveDate||fx.effective_date||''}
}
const historicalFxPending=new Map(),historicalFxFailed=new Set();
function entryIsoDate(entry){const s=String(entry?.date||entry?.createdAt||'').trim();if(/^\d{4}-\d{2}-\d{2}/.test(s))return s.slice(0,10);if(/^\d{2}\.\d{2}\.\d{4}$/.test(s)){const [d,m,y]=s.split('.');return `${y}-${m}-${d}`}return ''}
async function fetchHistoricalTcmb(date){
 if(!date)return null;if(historicalFxPending.has(date))return historicalFxPending.get(date);if(historicalFxFailed.has(date))return null;
 const task=(async()=>{try{const res=await fetch('tcmb_proxy.php?date='+encodeURIComponent(date)+'&ts='+Date.now(),{cache:'no-store',headers:{Accept:'application/json'}}),j=await res.json();if(!res.ok||!j?.ok||!j?.usd?.selling||!j?.eur?.selling)throw new Error(j?.error||'Tarihsel kur alınamadı');const usd=Number(j.usd.selling),eur=Number(j.eur.selling);return {usdTry:usd,eurTry:eur,eurUsd:eur/usd,source:'TCMB tarihsel gösterge kuru',updated:j.fetched_at||'',capturedAt:new Date().toISOString(),rateDate:j.effective_date||j.date||date,requestedDate:date}}catch(e){historicalFxFailed.add(date);console.warn('TCMB tarihsel kur:',date,e);return null}finally{historicalFxPending.delete(date)}})();historicalFxPending.set(date,task);return task
}
function entryNeedsHistoricalFx(e){const d=entryIsoDate(e),fx=normalizedFxSnapshot(e?.fxSnapshot);if(!d)return false;if(!fx)return true;const rd=String(fx.rateDate||'').slice(0,10);return d<todayISO()&&rd!==d}
async function hydrateMissingTryFx(onDone){
 const missing=[];(state.accounts||[]).filter(a=>String(a.currency||'').toUpperCase()==='TRY').forEach(a=>{ensureAccountEntries(a);(a.entries||[]).forEach(e=>{if(entryNeedsHistoricalFx(e)){const d=entryIsoDate(e);if(d)missing.push({e,d})}})});if(!missing.length)return false;
 const dates=[...new Set(missing.map(x=>x.d))],rates={};for(const d of dates){const fx=await fetchHistoricalTcmb(d);if(fx)rates[d]=fx}
 let changed=false;missing.forEach(({e,d})=>{if(rates[d]){e.fxSnapshot={...rates[d]};changed=true}});if(changed&&window.saveDbState)await saveDbState(true);if(changed&&typeof onDone==='function')onDone();return changed
}
function tryEntryEquivalents(entry,currency){
 if(String(currency||'').toUpperCase()!=='TRY')return {eur:null,usd:null,eurTry:null,usdTry:null,label:''};
 const fx=normalizedFxSnapshot(entry?.fxSnapshot);
 if(!fx){const d=entryIsoDate(entry);return {eur:null,usd:null,eurTry:null,usdTry:null,label:d?'Tarihsel kur yükleniyor…':'İşlem tarihi yok'}};
 const amount=Math.max(Number(entry?.debit||0),Number(entry?.credit||0)),dateLabel=fx.rateDate?` · Kur tarihi ${fx.rateDate}`:'';
 return {eur:amount/fx.eurTry,usd:amount/fx.usdTry,eurTry:fx.eurTry,usdTry:fx.usdTry,label:`EUR/TRY ${reportFmtNum(fx.eurTry)} · USD/TRY ${reportFmtNum(fx.usdTry)}${dateLabel}`}
}
function fxEquivCell(entry,currency,target){
 const eq=tryEntryEquivalents(entry,currency);if(String(currency||'').toUpperCase()!=='TRY')return '—';if(eq[target]===null)return 'Hesaplanıyor…';return fmtMoneyCur(eq[target],target.toUpperCase())
}
const statementFilters={};
function ensureAccountEntries(a){
 if(!Array.isArray(a.entries))a.entries=[];
 if(!a.entries.length&&Number(a.requestOpen||0)!==0){
   const v=Math.abs(Number(a.requestOpen||0));
   a.entries.push({date:todayISO(),ref:a.request||'DEVİR',desc:'Devreden Açılış Bakiyesi',debit:a.type==='supplier'?0:v,credit:a.type==='supplier'?v:0})
 }
}
function statementRows(a){
 ensureAccountEntries(a);
 return a.entries.map((x,i)=>({...x,_entryIndex:i})).filter(x=>!/^Ters kayıt:/iu.test(String(x.desc||''))&&!x.uiHidden).sort((x,y)=>String(x.date).localeCompare(String(y.date))||Number(x._entryIndex)-Number(y._entryIndex))
}
function getStatementData(a,from='',to=''){
 const all=statementRows(a);let opening=0;
 all.filter(x=>from&&x.date<from).forEach(x=>opening+=Number(x.debit||0)-Number(x.credit||0));
 const rows=all.filter(x=>(!from||x.date>=from)&&(!to||x.date<=to));
 let running=opening;
 return {opening,rows:rows.map(x=>{running+=Number(x.debit||0)-Number(x.credit||0);return {...x,running}})}
}
function computeStatement(a){
 const f=statementFilters[a.id]||{};
 return getStatementData(a,f.from||'',f.to||'').rows
}

function applyStatementFilter(name){
 const g=groupedAccounts().find(x=>x.name===name),a=g?.accounts[activeAccountCurrency];
 if(!a)return;
 statementFilters[a.id]={from:document.getElementById('statementFrom')?.value||'',to:document.getElementById('statementTo')?.value||''};
 showAccountGroup(accountGroupKey(g))
}
function clearStatementFilter(name){
 const g=groupedAccounts().find(x=>x.name===name),a=g?.accounts[activeAccountCurrency];
 if(a)delete statementFilters[a.id];
 showAccountGroup(name)
}

function showAccountGroup(name){
 const detail=document.getElementById('accountDetail');
 if(!detail)return;
 if(!name){detail.innerHTML='<div class="empty">Cari kart detayını görmek için bir karta tıklayın.</div>';return}
 const g=groupedAccounts().find(x=>String(x.partyKey)===String(name))||groupedAccounts().find(x=>x.name===name);
 if(!g){detail.innerHTML='<div class="empty">Cari kart bulunamadı.</div>';return}
 if(!g.accounts[activeAccountCurrency])activeAccountCurrency=Object.keys(g.accounts)[0];
 const a=g.accounts[activeAccountCurrency];
 if(!a){detail.innerHTML='<div class="empty">Bu cari için hesap bulunamadı.</div>';return}
 ensureAccountEntries(a);
 const f=statementFilters[a.id]||{};
 const data=getStatementData(a,f.from||'',f.to||''),rows=data.rows;
 const totalD=rows.reduce((n,x)=>n+Number(x.debit||0),0),totalC=rows.reduce((n,x)=>n+Number(x.credit||0),0);
 const closing=rows.length?rows[rows.length-1].running:data.opening;
 const poRecon=g.type==='supplier'?Number(supplierPoOutstanding(g.name)[a.currency]||0):null;
 const safeName=g.name.replaceAll("'","\\'");
 detail.innerHTML=`<div class="statement-head">
   <div class="statement-company"><div class="statement-logo">AS</div><div><span class="eyebrow">CARİ HESAP EKSTRESİ</span><h3>${g.name}</h3><div class="sub">${accountTypeLabel(g.type)} · Çoklu döviz cari kartı</div></div></div>
   <div class="statement-tools"><button class="btn sm" onclick="openAccountEdit('${accountGroupKey(g)}')">Düzenle</button><button class="btn sm" onclick="openAccountTagManager('${accountGroupKey(g)}')">Etiketler</button><button class="btn sm" onclick="openAccountCreateModal('${accountGroupKey(g)}','${['EUR','USD','TRY'].find(c=>!g.accounts[c])||activeAccountCurrency}')">+ Döviz Hesabı</button><button class="btn sm" onclick="printStatementByKey('${accountGroupKey(g)}','${activeAccountCurrency}')">PDF / Yazdır</button><button class="btn sm" onclick="downloadStatementCsvByKey('${accountGroupKey(g)}','${activeAccountCurrency}')">Excel / CSV</button><button class="btn sm" onclick="openCashModal('${safeName}','${g.type==='supplier'?'payment':'receipt'}')">${g.type==='supplier'?'Ödeme Yap':'Tahsilat Al'}</button><button class="btn red sm" onclick="deleteAccountGroup('${accountGroupKey(g)}')">Sil</button></div>
 </div>
 <div class="account-profile-panel">
   <div class="account-profile-item"><span>Yetkili / İlgili</span><b>${g.profile.contact||'—'}</b></div>
   <div class="account-profile-item"><span>Telefon</span><b>${g.profile.phone||'—'}</b></div>
   <div class="account-profile-item"><span>E-posta</span>${g.profile.email?`<a href="mailto:${g.profile.email}">${g.profile.email}</a>`:'<b>—</b>'}</div>
   <div class="account-profile-item"><span>Web Sitesi</span>${g.profile.website?`<a href="${g.profile.website.startsWith('http')?g.profile.website:'https://'+g.profile.website}" target="_blank" rel="noopener">${g.profile.website}</a>`:'<b>—</b>'}</div>
   <div class="account-profile-item"><span>Vergi / Kurum No</span><b>${g.profile.taxNo||'—'}</b></div>
   <div class="account-profile-item"><span>Ülke</span><b>${g.profile.country||'—'}</b></div>
   <div class="account-profile-item"><span>Cari Vade Tarihi</span><b>${g.profile.dueDate||'—'}</b></div>
   <div class="account-profile-item" style="grid-column:1/-1"><span>Adres</span><b>${g.profile.address||'—'}</b></div>
   <div class="account-profile-item" style="grid-column:1/-1"><span>Açıklama</span><b>${g.profile.description||'—'}</b></div>
   <div class="account-profile-item" style="grid-column:1/-1"><span>Etiketler</span><div class="account-tags">${entityTagObjects(g.profile.tags).map(t=>`<span class="tag-chip"><i style="background:${t.color}"></i>${t.name}</span>`).join('')||'—'}</div></div>
 </div>
 <div class="account-multi-fx-summary">
   <div><span>EUR Hesap</span><b>${g.accounts.EUR?fmtMoneyCur(g.accounts.EUR.requestOpen,'EUR'):'—'}</b></div>
   <div><span>USD Hesap</span><b>${g.accounts.USD?fmtMoneyCur(g.accounts.USD.requestOpen,'USD'):'—'}</b></div>
   <div><span>TRY Hesap</span><b>${g.accounts.TRY?fmtMoneyCur(g.accounts.TRY.requestOpen,'TRY'):'—'}</b></div>
   <div><span>Güncel EUR Karşılığı</span><b>${fmtMoneyCur(accountNetBalance(g),'EUR')}</b><small>Yalnız analiz · cari kayıtlarını değiştirmez</small></div>
 </div>
 <div class="statement-currency-selector"><div><span class="eyebrow">EKSTRE PARA BİRİMİ</span><b>${activeAccountCurrency} Cari Ekstresi</b></div><select onchange="switchAccountCurrency(this.value,'${accountGroupKey(g)}')">${['EUR','USD','TRY'].map(c=>`<option value="${c}" ${c===activeAccountCurrency?'selected':''} ${g.accounts[c]?'':'disabled'}>${c}${g.accounts[c]?' · '+fmtMoneyCur(g.accounts[c].requestOpen,c):' · Hesap yok'}</option>`).join('')}</select></div>
 <div class="account-currency-tabs">${['EUR','USD','TRY'].map(c=>`<button type="button" class="currency-tab ${c===activeAccountCurrency?'active':''}" ${g.accounts[c]?'':'disabled'} onclick="switchAccountCurrency('${c}','${accountGroupKey(g)}')">${c}${g.accounts[c]?' · '+fmtMoneyCur(g.accounts[c].requestOpen,c):''}</button>`).join('')}</div>
 <div class="statement-filter"><input id="statementFrom" type="date" value="${f.from||''}"><input id="statementTo" type="date" value="${f.to||todayISO()}"><button class="btn" onclick="applyStatementFilter('${safeName}')">Dönemi Uygula</button><button class="btn" onclick="clearStatementFilter('${safeName}')">Temizle</button></div>
 <div class="statement-summary"><div><span>Dönem Açılış</span><b>${fmtMoneyCur(data.opening,a.currency)}</b></div><div><span>Toplam Borç</span><b>${fmtMoneyCur(totalD,a.currency)}</b></div><div><span>Toplam Alacak</span><b>${fmtMoneyCur(totalC,a.currency)}</b></div><div><span>Güncel Bakiye</span><b>${fmtMoneyCur(closing,a.currency)}</b></div></div>
 <div class="inline-note">Talep: <b>${a.request||'—'}</b> · Cari açık bakiye: <b>${fmtMoneyCur(a.requestOpen||0,a.currency)}</b>${poRecon!==null?` · Aktif PO kalan toplamı (salt okunur mutabakat): <b>${fmtMoneyCur(poRecon,a.currency)}</b>`:''}. EUR / USD / TRY alt hesapları bağımsızdır; bakiye ve ekstreler birbirine karıştırılmaz. Kur karşılığı yalnız analiz amaçlıdır.</div>
 <div class="table-wrap" style="margin-top:12px"><table class="table responsive"><thead><tr><th>Tarih</th><th>Referans</th><th>İşlem</th><th>Borç</th><th>Alacak</th><th>EUR Karşılığı</th><th>USD Karşılığı</th><th>İşlem Kuru</th><th>Yürüyen Bakiye</th><th>İşlem</th></tr></thead><tbody>${rows.length?rows.map(x=>{const editable=accountEntryEditable(x),jid=accountEntryLinkedJournalId(x,a),eq=tryEntryEquivalents(x,a.currency);return `<tr><td data-label="Tarih">${x.date}</td><td data-label="Referans"><b>${x.ref||''}</b></td><td data-label="İşlem">${x.desc}</td><td data-label="Borç">${x.debit?fmtMoneyCur(x.debit,a.currency):''}</td><td data-label="Alacak">${x.credit?fmtMoneyCur(x.credit,a.currency):''}</td><td data-label="EUR Karşılığı">${fxEquivCell(x,a.currency,'eur')}</td><td data-label="USD Karşılığı">${fxEquivCell(x,a.currency,'usd')}</td><td data-label="İşlem Kuru"><small>${a.currency==='TRY'?(eq.label||'Hesaplanıyor…'):'—'}</small></td><td data-label="Yürüyen Bakiye" class="${x.running>=0?'running-pos':'running-neg'}">${fmtMoneyCur(x.running,a.currency)}</td><td data-label="İşlem"><div class="statement-row-actions"><button type="button" class="btn sm" onclick="${editable?`openAccountEntryEdit(${a.id},${x._entryIndex},'${accountGroupKey(g)}')`:(jid?`openFinanceMovementEdit(${jid})`:`toast('Bu sistem kaydının düzenlenebilir finans hareketi bulunamadı.','warn')`)}">Düzenle</button><button type="button" class="btn red sm" onclick="deleteOrCancelAccountEntry(${a.id},${x._entryIndex},'${accountGroupKey(g)}',${jid||0})">Sil</button></div></td></tr>`}).join(''):'<tr><td colspan="10" class="empty">Bu dönem için cari hareketi yok.</td></tr>'}</tbody></table></div>`;
 if(String(a.currency||'').toUpperCase()==='TRY'&&rows.some(x=>entryNeedsHistoricalFx(x)))setTimeout(()=>hydrateMissingTryFx(()=>showAccountGroup(accountGroupKey(g))),0);
}

function accountEntryEditable(e){if(!e)return false;if(e.systemGenerated||e.journalId||e.groupUuid||e.kind)return false;return !/(tahsilat|ödeme|sipariş|teslimat|peşinat|gider|ters kayıt|alacağı|borcu|bakiye düzeltme)/iu.test(String(e.desc||''))}
function accountEntryLinkedJournalId(e,a){
 if(!e||!a)return 0;
 const direct=Number(e.journalId||0);if(direct>0)return direct;
 const amount=Math.max(Number(e.debit||0),Number(e.credit||0)),ref=String(e.ref||'').trim(),desc=String(e.desc||'').trim(),party=normalizedPartyName(a.name||'');
 const candidates=(state.cash||[]).filter(c=>Number(c.journalId||0)>0&&normalizedPartyName(c.party||'')===party&&(!ref||String(c.request||'').trim()===ref)&&Math.abs(Number(c.orderAmount??c.amountValue??c.amount??0)-amount)<0.011);
 if(!candidates.length)return 0;
 const exact=candidates.filter(c=>desc&&String(c.note||'').trim()===desc);
 const row=(exact.length===1?exact[0]:(candidates.length===1?candidates[0]:null));
 return Number(row?.journalId||0)
}
async function deleteOrCancelAccountEntry(accountId,entryIndex,groupKey='',journalId=0){
 const a=state.accounts.find(x=>Number(x.id)===Number(accountId));if(!a)return toast('Cari alt hesabı bulunamadı.','warn');
 ensureAccountEntries(a);const e=a.entries?.[Number(entryIndex)];if(!e)return toast('Cari hareketi bulunamadı.','warn');
 if(accountEntryEditable(e)){
   if(!await appConfirm('Bu manuel cari hareketi silinsin mi? Bakiye otomatik yeniden hesaplanacaktır.','Cari Hareketini Sil'))return;
   const ok=await runServerOperation({action:'account_entry_delete',accountId:Number(accountId),entryIndex:Number(entryIndex),txnId:e.txnId||'',idempotencyKey:makeIdempotencyKey()});
   if(ok){activeEntityKey=groupKey||a.partyKey||a.name;setTimeout(()=>showAccountGroup(activeEntityKey),40);toast('Cari hareketi silindi ve bakiye güncellendi.','good')}return
 }
 const jid=Number(journalId||accountEntryLinkedJournalId(e,a)||0);
 if(jid>0){
   if(!await appConfirm('Bu bağlı finans hareketi silinsin mi? Bakiye, cari ve bağlı sipariş tutarları otomatik düzeltilecektir.','Finans Hareketini Sil'))return;
   if(await runServerOperation({action:'finance_delete',journalId:jid,accountId:Number(accountId),txnId:e.txnId||'',idempotencyKey:makeIdempotencyKey()})){activeEntityKey=groupKey||a.partyKey||a.name;setTimeout(()=>showAccountGroup(activeEntityKey),40);toast('Finans hareketi ve bağlı ekstre kaydı tamamen silindi.','good')}return
 }
 toast('Bu kayıt sipariş/teklif gibi sistem kaynağından oluşturulmuş. Finans bütünlüğü için doğrudan silinemez; ilgili sipariş veya teklif kaynağından iptal/düzeltme yapın.','warn')
}
let accountEntryEditContext=null;
function openAccountEntryEdit(accountId,entryIndex,groupKey=''){
 const a=state.accounts.find(x=>Number(x.id)===Number(accountId));if(!a)return toast('Cari alt hesabı bulunamadı.','warn');
 ensureAccountEntries(a);const e=a.entries?.[Number(entryIndex)];if(!e)return toast('Cari hareketi bulunamadı.','warn');if(!accountEntryEditable(e))return toast('Bu hareket finansal kayıtla bağlantılıdır. Düzenlemek için kaynak ödeme/tahsilat işlemini iptal edin.','warn');
 accountEntryEditContext={accountId:Number(accountId),entryIndex:Number(entryIndex),txnId:e.txnId||'',groupKey:groupKey||a.partyKey||a.name};
 const debit=Number(e.debit||0),credit=Number(e.credit||0),direction=debit>0?'debit':'credit',amount=direction==='debit'?debit:credit;
 document.getElementById('accountEntryEditDate').value=e.date||todayISO();
 document.getElementById('accountEntryEditDirection').value=direction;
 document.getElementById('accountEntryEditAmount').value=formatMoneyNumber(amount||0);
 document.getElementById('accountEntryEditRef').value=e.ref||'';
 document.getElementById('accountEntryEditDesc').value=e.desc||'';
 document.getElementById('accountEntryEditSub').textContent=`${a.name} · ${a.currency} · ${e.txnId||'Eski kayıt'}`;
 openModal('accountEntryEditModal')
}
async function saveAccountEntryEdit(){
 const c=accountEntryEditContext;if(!c)return toast('Düzenlenecek hareket seçilmedi.','warn');
 const date=document.getElementById('accountEntryEditDate').value||todayISO(),direction=document.getElementById('accountEntryEditDirection').value,
       amount=Math.max(0,parseMoneyInput(document.getElementById('accountEntryEditAmount').value)),ref=document.getElementById('accountEntryEditRef').value.trim(),desc=document.getElementById('accountEntryEditDesc').value.trim();
 if(!(amount>0))return toast('Tutar sıfırdan büyük olmalı.','warn');
 if(!desc)return toast('Açıklama zorunlu.','warn');
 const ok=await runServerOperation({action:'account_entry_update',accountId:c.accountId,entryIndex:c.entryIndex,txnId:c.txnId,date,direction,amount,ref,description:desc,idempotencyKey:makeIdempotencyKey()});
 if(ok){const key=c.groupKey;accountEntryEditContext=null;closeModal('accountEntryEditModal');activeEntityKey=key;setTimeout(()=>showAccountGroup(key),40);toast('Cari hareketi ve açık bakiye güncellendi.','good')}
}
function toggleAccountCardByKey(key,event){
 if(event?.target?.closest('button,a,input,select,textarea'))return;
 event?.preventDefault();event?.stopPropagation();
 const g=accountGroupByKey(key);if(!g)return;
 const card=event?.currentTarget;
 const opening=String(activeEntityKey)!==String(key) || !card?.classList.contains('open');
 document.querySelectorAll('.account-entity-card').forEach(c=>c.classList.remove('open','active'));
 if(!opening){
   activeEntityName='';activeEntityKey='';
   const detail=document.getElementById('accountDetail');
   if(detail)detail.innerHTML='<div class="empty">Cari kart detayını görmek için bir karta tıklayın.</div>';
   return
 }
 activeEntityName=g.name;activeEntityKey=accountGroupKey(g);
 activeAccountCurrency=g.accounts[activeAccountCurrency]?activeAccountCurrency:Object.keys(g.accounts||{})[0]||'EUR';
 if(card)card.classList.add('open','active');
 try{showAccountGroup(accountGroupKey(g))}catch(err){
   console.error('Cari detay render hatası:',err);
   const detail=document.getElementById('accountDetail');
   if(detail)detail.innerHTML='<div class="empty" style="color:var(--danger)">Cari detay yüklenemedi: '+String(err?.message||err)+'</div>';
   toast('Cari detay yüklenirken hata oluştu: '+String(err?.message||err),'warn')
 }
}
function toggleAccountCard(name,event){
 const g=groupedAccounts().find(x=>x.name===name);if(!g)return;
 toggleAccountCardByKey(accountGroupKey(g),event)
}
function selectEntity(name){
 const g=groupedAccounts().find(x=>x.name===name);if(!g)return;
 activeEntityName=g.name;activeEntityKey=accountGroupKey(g);
 activeAccountCurrency=g.accounts[activeAccountCurrency]?activeAccountCurrency:Object.keys(g.accounts||{})[0]||'EUR';
 document.querySelectorAll('.account-entity-card').forEach(c=>{
   const same=String(c.dataset.accountKey)===String(accountGroupKey(g));
   c.classList.toggle('open',same);c.classList.toggle('active',same)
 });
 showAccountGroup(name)
}
function switchAccountCurrency(c,groupRef=null){
 const ref=groupRef||activeEntityKey||activeEntityName;
 const g=groupedAccounts().find(x=>String(x.partyKey)===String(ref))||groupedAccounts().find(x=>x.name===ref);
 if(!g)return toast('Cari kart bulunamadı.','warn');
 if(!g.accounts[c])return toast(`${g.name} için ${c} cari hesabı bulunmuyor. + Döviz Hesabı ile ekleyebilirsiniz.`,'warn');
 activeEntityName=g.name;activeEntityKey=accountGroupKey(g);activeAccountCurrency=c;
 showAccountGroup(accountGroupKey(g))
}
function printEsc(v){return String(v??'').replaceAll('&','&amp;').replaceAll('<','&lt;').replaceAll('>','&gt;').replaceAll('"','&quot;')}
function printHtmlInFrame(title,bodyHtml,css=''){
 const f=document.createElement('iframe');f.setAttribute('aria-hidden','true');Object.assign(f.style,{position:'fixed',right:'0',bottom:'0',width:'1px',height:'1px',border:'0',opacity:'0'});document.body.appendChild(f);
 const d=f.contentDocument||f.contentWindow.document;
 d.open();
 d.write(`<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>${printEsc(title)}</title><style>${css||''}</style></head><body>${bodyHtml}</body></html>`);
 d.close();
 const doPrint=()=>{try{f.contentWindow.focus();f.contentWindow.print()}finally{setTimeout(()=>f.remove(),1600)}};
 if(d.fonts&&d.fonts.ready){d.fonts.ready.then(()=>setTimeout(doPrint,120)).catch(()=>setTimeout(doPrint,220))}else setTimeout(doPrint,220)
}

function printStatementByKey(key,currency){
 const g=accountGroupByKey(key);if(!g||!g.accounts[currency])return toast('Seçilen döviz ekstresi bulunamadı.','warn');
 return printStatement(g.name,currency,key)
}
function downloadStatementCsvByKey(key,currency){
 const g=accountGroupByKey(key);if(!g||!g.accounts[currency])return toast('Seçilen döviz ekstresi bulunamadı.','warn');
 const a=g.accounts[currency],rows=computeStatement(a),csv=['Tarih;Referans;İşlem;Borç;Alacak;EUR Karşılığı;USD Karşılığı;İşlem Kuru;Yürüyen Bakiye',...rows.map(x=>{const eq=tryEntryEquivalents(x,currency);return [x.date,x.ref,x.desc,x.debit||'',x.credit||'',currency==='TRY'&&eq.eur!==null?eq.eur.toFixed(2):currency==='TRY'?'Kur kaydı yok':'',currency==='TRY'&&eq.usd!==null?eq.usd.toFixed(2):currency==='TRY'?'Kur kaydı yok':'',currency==='TRY'?(eq.label||'Hesaplanıyor…'):'',x.running].join(';')})].join('\n');
 const b=new Blob(['\ufeff'+csv],{type:'text/csv;charset=utf-8'}),u=URL.createObjectURL(b),link=document.createElement('a');link.href=u;link.download=`${g.name.replace(/[^a-z0-9]+/gi,'_')}_${currency}_ekstre.csv`;link.click();URL.revokeObjectURL(u)
}
function printStatement(name,currency){
 const g=groupedAccounts().find(x=>x.name===name),a=g?.accounts[currency];if(!a)return;const rows=computeStatement(a),totalD=rows.reduce((n,x)=>n+Number(x.debit||0),0),totalC=rows.reduce((n,x)=>n+Number(x.credit||0),0),closing=rows.length?rows[rows.length-1].running:0;
 const body=`<div class="head"><div><h1>${printEsc(g.name)}</h1><div>${printEsc(accountTypeLabel(g.type))} · ${currency}</div></div><div>Cari Hesap Ekstresi<br>${new Date().toLocaleString('tr-TR')}</div></div><div class="sum"><div><span>Toplam Borç</span><b>${fmtMoneyCur(totalD,currency)}</b></div><div><span>Toplam Alacak</span><b>${fmtMoneyCur(totalC,currency)}</b></div><div><span>Güncel Bakiye</span><b>${fmtMoneyCur(closing,currency)}</b></div></div><table><thead><tr><th>Tarih</th><th>Referans</th><th>Açıklama</th><th>Borç</th><th>Alacak</th><th>EUR Karşılığı</th><th>USD Karşılığı</th><th>İşlem Kuru</th><th>Bakiye</th></tr></thead><tbody>${rows.map(x=>{const eq=tryEntryEquivalents(x,currency);return `<tr><td>${printEsc(x.date)}</td><td>${printEsc(x.ref||'')}</td><td>${printEsc(x.desc||'')}</td><td>${x.debit?fmtMoneyCur(x.debit,currency):''}</td><td>${x.credit?fmtMoneyCur(x.credit,currency):''}</td><td>${currency==='TRY'?(eq.eur===null?'Kur kaydı yok':fmtMoneyCur(eq.eur,'EUR')):'—'}</td><td>${currency==='TRY'?(eq.usd===null?'Kur kaydı yok':fmtMoneyCur(eq.usd,'USD')):'—'}</td><td>${currency==='TRY'?(eq.label||'Hesaplanıyor…'):'—'}</td><td>${fmtMoneyCur(x.running,currency)}</td></tr>`}).join('')}</tbody></table>`;
 printHtmlInFrame('Cari Ekstre - '+g.name,body,'@page{size:A4 landscape;margin:12mm}body{font-family:Arial,sans-serif;color:#111;font-size:10pt}.head{display:flex;justify-content:space-between;border-bottom:2px solid #163d73;padding-bottom:8px;margin-bottom:12px}.sum{display:flex;gap:8px;margin:10px 0}.sum div{border:1px solid #ddd;padding:8px;min-width:150px}.sum span{display:block;font-size:8pt;color:#666}table{width:100%;border-collapse:collapse}th,td{border:1px solid #d8dce3;padding:5px;text-align:left}th{background:#eef3f8}')
}
function downloadStatementCsv(name,currency){const g=groupedAccounts().find(x=>x.name===name),a=g?.accounts[currency];if(!a)return;const rows=computeStatement(a),csv=['Tarih;Referans;İşlem;Borç;Alacak;EUR Karşılığı;USD Karşılığı;İşlem Kuru;Yürüyen Bakiye',...rows.map(x=>{const eq=tryEntryEquivalents(x,currency);return [x.date,x.ref,x.desc,x.debit||'',x.credit||'',currency==='TRY'&&eq.eur!==null?eq.eur.toFixed(2):currency==='TRY'?'Kur kaydı yok':'',currency==='TRY'&&eq.usd!==null?eq.usd.toFixed(2):currency==='TRY'?'Kur kaydı yok':'',currency==='TRY'?(eq.label||'Hesaplanıyor…'):'',x.running].join(';')})].join('\\n');const b=new Blob(['\\ufeff'+csv],{type:'text/csv;charset=utf-8'}),u=URL.createObjectURL(b),link=document.createElement('a');link.href=u;link.download=`${name.replace(/[^a-z0-9]+/gi,'_')}_${currency}_ekstre.csv`;link.click();URL.revokeObjectURL(u)}

let editingCashAccountId=null,editingBalanceAccountId=null,activeCashAccountId=0,financeMovementEditJournalId=0;
function renderCashAccounts(){
 const box=document.getElementById('cashAccountCards');if(!box)return;
 if(!activeCashAccountId&&state.cashAccounts.length)activeCashAccountId=Number(state.cashAccounts[0].id);
 box.innerHTML=state.cashAccounts.map(a=>`<div class="cash-pro-card ${Number(a.id)===Number(activeCashAccountId)?'active':''}" onclick="selectCashAccount(${a.id},event)"><div class="entity-head"><div><span class="badge b-blue">${a.currency} · ${a.type}</span><div class="money">${fmtMoneyCur(a.balance,a.currency)}</div><b>${a.name}</b><small>${a.type==='Banka'?`${a.bankName||'Banka adı girilmemiş'}${a.branch?' · '+a.branch:''}${a.swift?' · SWIFT: '+a.swift:''}<br>${a.iban||'IBAN girilmemiş'}`:'Nakit hesap'}</small></div></div><div class="cash-meta"><span class="badge">${a.currency}</span><span class="badge">${a.type}</span></div><div class="actions"><button class="btn sm" onclick="openCashAccountStatement(${a.id})">Ekstre</button><button class="btn sm" onclick="openCashAccountModal(${a.id})">Düzenle</button><button class="btn sm" onclick="adjustCashBalance(${a.id})">Bakiye Düzelt</button><button class="btn red sm" onclick="deleteCashAccount(${a.id})">Sil</button></div></div>`).join('')
}
function selectCashAccount(id,event){if(event?.target?.closest('button,a,input,select,textarea'))return;activeCashAccountId=Number(id||0);renderCash()}
function openCashAccountStatement(id){
 const a=state.cashAccounts.find(x=>Number(x.id)===Number(id));if(!a)return toast('Kasa/Banka hesabı bulunamadı.','warn');
 openGeneralReport('cash');const sec=document.getElementById('reportSecondaryFilter'),arch=document.getElementById('reportIncludeArchived');if(sec)sec.value=String(a.id);if(arch)arch.checked=true;renderGeneralReport();
 const title=document.getElementById('generalReportTitle'),sub=document.getElementById('generalReportSub');if(title)title.textContent=`${a.name} · Hesap Ekstresi`;if(sub)sub.textContent=`${a.type} · ${a.currency} · Güncel bakiye ${fmtMoneyCur(a.balance,a.currency)}`
}
function toggleBankFields(){const isBank=document.getElementById('caType')?.value==='Banka';document.querySelectorAll('#cashAccountModal .bank-only').forEach(x=>x.style.display=isBank?'grid':'none')}
function openCashAccountModal(id=null){editingCashAccountId=id;const a=id?state.cashAccounts.find(x=>x.id===id):null;document.getElementById('cashAccountModalTitle').textContent=a?'Kasa / Banka Düzenle':'Yeni Kasa / Banka';document.getElementById('caName').value=a?.name||'';document.getElementById('caType').value=a?.type||'Banka';document.getElementById('caCurrency').value=a?.currency||'EUR';document.getElementById('caBankName').value=a?.bankName||'';document.getElementById('caBranch').value=a?.branch||'';document.getElementById('caSwift').value=a?.swift||'';document.getElementById('caIban').value=a?.iban||'';const bal=document.getElementById('caBalance');bal.value=a?.balance||0;bal.readOnly=!!a;bal.title=a?'Mevcut hesap bakiyesi burada değiştirilemez. Bakiye Düzelt işlemini kullanın.':'Yeni hesap için açılış bakiyesi';document.getElementById('caCurrency').disabled=!!a&&((Math.abs(Number(a.balance||0))>0.001)||(state.cash||[]).some(x=>Number(x.accountId)===Number(a.id)));toggleBankFields();openModal('cashAccountModal')}
async function saveCashAccount(){const name=document.getElementById('caName').value.trim();if(!name)return toast('Hesap adı gerekli.','warn');const type=document.getElementById('caType').value,payload={action:'cash_account_upsert',id:editingCashAccountId||0,name,type,currency:document.getElementById('caCurrency').value,bankName:type==='Banka'?document.getElementById('caBankName').value.trim():'',branch:type==='Banka'?document.getElementById('caBranch').value.trim():'',swift:type==='Banka'?document.getElementById('caSwift').value.trim().toUpperCase():'',iban:document.getElementById('caIban').value.trim(),openingBalance:editingCashAccountId?0:parseMoneyInput(document.getElementById('caBalance').value),idempotencyKey:makeIdempotencyKey()};if(await runServerOperation(payload)){closeModal('cashAccountModal');toast(editingCashAccountId?'Kasa/Banka bilgileri güncellendi.':'Yeni Kasa/Banka transaction ile oluşturuldu.','good');editingCashAccountId=null;document.getElementById('caCurrency').disabled=false}}
function adjustCashBalance(id){
 const a=state.cashAccounts.find(x=>Number(x.id)===Number(id));if(!a)return toast('Kasa/Banka hesabı bulunamadı.','warn');
 editingBalanceAccountId=Number(id);
 document.getElementById('cbaAccount').value=`${a.name} · ${a.type} · ${a.currency}`;
 document.getElementById('cbaCurrent').value=fmtMoneyCur(Number(a.balance||0),a.currency);
 document.getElementById('cbaTarget').value=formatMoneyNumber(Number(a.balance||0));
 document.getElementById('cbaReason').value='Sayım / banka mutabakatı düzeltmesi';
 document.getElementById('cbaInfo').textContent=`${a.name} hesabının ${a.currency} bakiyesi journal kaydıyla düzeltilecektir. Yeni gerçek bakiyeyi girin.`;
 openModal('cashBalanceAdjustModal');
 setTimeout(()=>{const el=document.getElementById('cbaTarget');el?.focus();el?.select?.()},60)
}
async function saveCashBalanceAdjustment(){
 const id=Number(editingBalanceAccountId||0),a=state.cashAccounts.find(x=>Number(x.id)===id);if(!a)return toast('Kasa/Banka hesabı bulunamadı.','warn');
 const raw=document.getElementById('cbaTarget')?.value||'',target=parseMoneyInput(raw),reason=(document.getElementById('cbaReason')?.value||'').trim();
 if(!Number.isFinite(target))return toast('Geçerli bir yeni bakiye girin.','warn');
 if(!reason)return toast('Bakiye düzeltme açıklaması zorunludur.','warn');
 if(Math.abs(target-Number(a.balance||0))<0.001)return toast('Yeni bakiye mevcut bakiye ile aynı.','warn');
 const btn=document.querySelector('#cashBalanceAdjustModal .modal-foot .btn.primary');if(btn){btn.disabled=true;btn.textContent='Kaydediliyor…'}
 try{
  const ok=await runServerOperation({action:'cash_balance_adjustment',accountId:id,targetBalance:target,reason,idempotencyKey:makeIdempotencyKey()});
  if(ok){closeModal('cashBalanceAdjustModal');editingBalanceAccountId=null;toast('Bakiye düzeltmesi transaction ve journal ile kaydedildi.','good')}
 }finally{if(btn){btn.disabled=false;btn.textContent='Bakiyeyi Düzelt'}}
}
async function deleteCashAccount(id){const a=state.cashAccounts.find(x=>x.id===id);if(!a)return;const hasMoves=(state.cash||[]).some(x=>x.account===a.name||x.accountId===id);if(Math.abs(Number(a.balance||0))>0.001||hasMoves)return toast('Bakiyesi veya finans hareketi bulunan Kasa/Banka hesabı silinemez. Önce bakiyeyi sıfırlayın; hareket geçmişi arşivde korunur.','warn');if(!await appConfirm(a.name+' hesabı silinsin mi?','Kasa/Banka Hesabı'))return;state.cashAccounts=state.cashAccounts.filter(x=>x.id!==id);state.requests.forEach(r=>{if(Number(r.bankAccountId||0)===Number(id)||r.bank===a.name){r.bank='';r.bankAccountId=null}});if(state.company?.eurBankAccountId===id){state.company.eurBankAccountId=null;state.company.eurBank='';state.company.eurIban=''}if(state.company?.usdBankAccountId===id){state.company.usdBankAccountId=null;state.company.usdBank='';state.company.usdIban=''}renderCash();updateDashboard();renderRequests();renderDocument();loadCompanyToForm();if(!await saveDbState(true))return toast('Hesap silme kaydedilemedi: '+(window.lastDbSaveError||'DB hatası'),'warn');toast(a.name+' silindi.','good')}
function openTransferModal(){
 const opts=state.cashAccounts.map(a=>`<option value="${a.id}">${a.name} · ${a.currency} · ${fmtMoneyCur(a.balance,a.currency)}</option>`).join('');
 const from=document.getElementById('transferFrom'),to=document.getElementById('transferTo');
 from.innerHTML=opts;to.innerHTML=opts;if(state.cashAccounts.length>1)to.selectedIndex=1;
 document.getElementById('transferAmount').value='';
 document.getElementById('transferFxRate').value='1';
 document.getElementById('transferTargetAmount').value='';
 document.getElementById('transferNote').value='Hesaplar arası virman';
 syncTransferInfo();openModal('transferModal')
}
function suggestManualFxRate(fromCur,toCur){
 const usd=Number(state.fx?.usdTry||0),eur=Number(state.fx?.eurTry||0);
 if(fromCur===toCur)return 1;
 if(fromCur==='USD'&&toCur==='TRY')return usd||1;
 if(fromCur==='EUR'&&toCur==='TRY')return eur||1;
 if(fromCur==='TRY'&&toCur==='USD')return usd?1/usd:1;
 if(fromCur==='TRY'&&toCur==='EUR')return eur?1/eur:1;
 if(fromCur==='USD'&&toCur==='EUR')return usd&&eur?usd/eur:1;
 if(fromCur==='EUR'&&toCur==='USD')return usd&&eur?eur/usd:1;
 return 1
}
function transferRateLabel(fromCur,toCur){
 if(fromCur===toCur)return `${fromCur} → ${toCur}`;
 return `1 ${fromCur} = ? ${toCur}`
}
function syncTransferInfo(){
 const from=state.cashAccounts.find(a=>a.id===+document.getElementById('transferFrom')?.value),
       to=state.cashAccounts.find(a=>a.id===+document.getElementById('transferTo')?.value),
       box=document.getElementById('transferInfo'),
       fx=document.getElementById('transferFxRate'),
       hint=document.getElementById('transferFxHint');
 if(!box)return;
 if(from&&to){
   const suggested=suggestManualFxRate(from.currency,to.currency);
   if(fx){
     fx.value=from.currency===to.currency?'1':Number(suggested).toFixed(6);
     fx.readOnly=from.currency===to.currency;
   }
   if(hint)hint.textContent=from.currency===to.currency?'Aynı para birimi · kur 1,000000':`${transferRateLabel(from.currency,to.currency)} · öneri: ${Number(suggested).toFixed(6)} (isterseniz manuel değiştirin)`;
   box.innerHTML=`Kaynak: <b>${from.name}</b> · ${fmtMoneyCur(from.balance,from.currency)}<br>Hedef: <b>${to.name}</b> · ${fmtMoneyCur(to.balance,to.currency)}${from.currency!==to.currency?'<br><b style="color:var(--warning)">Farklı döviz virmanı: hedef tutar manuel kur/parite ile hesaplanır.</b>':''}`;
 }
 calculateTransferTarget()
}
function calculateTransferTarget(){
 const from=state.cashAccounts.find(a=>a.id===+document.getElementById('transferFrom')?.value),
       to=state.cashAccounts.find(a=>a.id===+document.getElementById('transferTo')?.value),
       amount=+document.getElementById('transferAmount')?.value||0,
       rate=+document.getElementById('transferFxRate')?.value||0,
       target=document.getElementById('transferTargetAmount');
 if(!target)return;
 if(!from||!to){target.value='';return}
 const finalRate=from.currency===to.currency?1:rate;
 target.value=finalRate>0?(amount*finalRate).toFixed(2):''
}
async function completeTransfer(){
 const from=state.cashAccounts.find(a=>a.id===+document.getElementById('transferFrom').value),to=state.cashAccounts.find(a=>a.id===+document.getElementById('transferTo').value),amount=parseMoneyInput(document.getElementById('transferAmount').value),rate=Number(document.getElementById('transferFxRate').value)||0,note=document.getElementById('transferNote').value.trim()||'Hesaplar arası virman';
 if(!from||!to)return toast('Kaynak ve hedef hesap seçin.','warn');if(from.id===to.id)return toast('Kaynak ve hedef hesap aynı olamaz.','warn');if(amount<=0)return toast('Geçerli bir kaynak tutar girin.','warn');if(amount>Number(from.balance||0))return toast('Kaynak hesap bakiyesi yetersiz.','warn');const finalRate=from.currency===to.currency?1:rate;if(finalRate<=0)return toast('Geçerli bir kur / parite girin.','warn');const targetAmount=amount*finalRate;if(!await appConfirm(`${fmtMoneyCur(amount,from.currency)} → ${fmtMoneyCur(targetAmount,to.currency)} virmanı transaction ile kaydedilecek. Onaylıyor musunuz?`,'Virman Onayı'))return;
 if(await runServerOperation({action:'cash_transfer',fromId:from.id,toId:to.id,amount,rate:finalRate,note,idempotencyKey:makeIdempotencyKey()})){closeModal('transferModal');toast(`${fmtMoneyCur(amount,from.currency)} → ${fmtMoneyCur(targetAmount,to.currency)} virmanı transaction ile tamamlandı.`,'good')}
}
function ensureCashMovementIds(){state.cash=Array.isArray(state.cash)?state.cash:[];state.cash.forEach((x,i)=>{if(!x._id)x._id='cash_'+Date.now()+'_'+i+'_'+Math.random().toString(36).slice(2,7)})}
async function deleteCashMovement(id){ensureCashMovementIds();const x=state.cash.find(m=>m._id===id);if(!x)return;if(!await appConfirm('Bu kasa/banka hareketi silinsin mi? Bağlı cari ve sipariş bakiyeleri otomatik düzeltilecektir.','Finans Hareketini Sil'))return;const jid=Number(x.journalId||0);if(jid){if(await runServerOperation({action:'finance_delete',journalId:jid,idempotencyKey:makeIdempotencyKey()})){renderCash();toast('Finans hareketi ve bağlı cari kaydı tamamen silindi.','good')}return}x.archived=true;x.uiDeleted=true;renderCash();if(!await saveDbState(true))return toast('Silme kaydedilemedi: '+(window.lastDbSaveError||'DB hatası'),'warn');toast('Hareket silindi.','good')}
function openFinanceMovementEdit(journalId){const jid=Number(journalId||0),x=(state.cash||[]).find(m=>Number(m.journalId||0)===jid&&!m.reversed&&m.kind!=='reversal');if(!x)return toast('Düzenlenecek finans hareketi bulunamadı.','warn');financeMovementEditJournalId=jid;const parts=String(x.date||'').split('.');document.getElementById('fmeDate').value=parts.length===3?`${parts[2]}-${parts[1]}-${parts[0]}`:todayISO();document.getElementById('fmeRef').value=x.request||'';document.getElementById('fmeDesc').value=x.note||'';document.getElementById('fmeAmount').value=fmtMoneyCur(Number((x.amountValue??x.amount)||0),x.currency||'EUR');document.getElementById('financeMovementEditSub').textContent=`${x.account||'Kasa/Banka'} · ${x.party||'Cari'} · ${x.currency||''}`;openModal('financeMovementEditModal')}
async function saveFinanceMovementEdit(){const jid=Number(financeMovementEditJournalId||0);if(!jid)return toast('Düzenlenecek hareket bulunamadı.','warn');const date=document.getElementById('fmeDate').value||todayISO(),ref=document.getElementById('fmeRef').value.trim(),description=document.getElementById('fmeDesc').value.trim();if(!description)return toast('Açıklama zorunlu.','warn');if(await runServerOperation({action:'finance_journal_meta_update',journalId:jid,date,ref,description,idempotencyKey:makeIdempotencyKey()})){closeModal('financeMovementEditModal');financeMovementEditJournalId=0;renderCash();if(activeEntityKey)setTimeout(()=>showAccountGroup(activeEntityKey),20);toast('Finans hareketi güncellendi.','good')}}
async function clearCashMovements(){const active=(state.cash||[]).filter(x=>!x.archived);if(!active.length)return toast('Arşivlenecek hareket yok.','warn');if(!await appConfirm('Görünen tüm Kasa/Banka hareketleri arşivlensin mi? Bakiye etkileri korunacaktır.','Toplu Arşivleme'))return;const now=new Date().toISOString();active.forEach(x=>{x.archived=true;x.archivedAt=now});renderCash();if(!await saveDbState(true))return toast('Arşivleme kaydedilemedi: '+(window.lastDbSaveError||'DB hatası'),'warn');toast('Kasa/Banka hareketleri arşivlendi.','good')}

let generalReportType='cash',generalReportCache={title:'',headers:[],rows:[],summaryHtml:'',html:''};

function reportEsc(v){return String(v??'').replace(/[&<>"']/g,m=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]))}
function reportDateValue(v){
 if(!v)return null;const s=String(v).trim();
 let d;if(/^\d{2}\.\d{2}\.\d{4}$/.test(s)){const [dd,mm,yy]=s.split('.');d=new Date(`${yy}-${mm}-${dd}T12:00:00`)}
 else d=new Date(s.slice(0,10)+'T12:00:00');
 return isNaN(d)?null:d
}
function reportDateInRange(v){
 const d=reportDateValue(v);if(!d)return true;
 const f=document.getElementById('reportDateFrom')?.value,t=document.getElementById('reportDateTo')?.value;
 const fd=f?new Date(f+'T00:00:00'):null,td=t?new Date(t+'T23:59:59'):null;
 return (!fd||d>=fd)&&(!td||d<=td)
}
function reportSearchMatch(values){
 const q=(document.getElementById('reportSearch')?.value||'').trim().toLocaleLowerCase('tr-TR');
 return !q||values.join(' ').toLocaleLowerCase('tr-TR').includes(q)
}
function reportFmtNum(n){return Number(n||0).toLocaleString('tr-TR',{minimumFractionDigits:2,maximumFractionDigits:2})}
function reportBalanceClass(n){return Number(n)>0?'b-green':Number(n)<0?'b-red':'b-gray'}
function currentReportSecondary(){return document.getElementById('reportSecondaryFilter')?.value||'all'}

function openGeneralReport(type){
 generalReportType=type;const title=document.getElementById('generalReportTitle'),sub=document.getElementById('generalReportSub'),sec=document.getElementById('reportSecondaryFilter'),lab=document.getElementById('reportSecondaryLabel'),from=document.getElementById('reportDateFromField'),to=document.getElementById('reportDateToField'),extra=document.getElementById('reportExtraOptions');
 document.getElementById('reportSearch').value='';document.getElementById('reportDateFrom').value='';document.getElementById('reportDateTo').value='';
 if(type==='cash'){
  title.textContent='Kasa & Banka Ekstresi';sub.textContent='Tüm veya seçili Kasa/Banka hesaplarının hareket ekstresi.';lab.textContent='Hesap';
  sec.innerHTML='<option value="all">Tüm Kasa / Bankalar</option>'+state.cashAccounts.map(a=>`<option value="${a.id}">${reportEsc(a.name)} · ${a.currency}</option>`).join('');
  from.style.display='';to.style.display='';extra.innerHTML='<label class="checkline"><input type="checkbox" id="reportIncludeArchived" onchange="renderGeneralReport()"> Arşivlenmiş finans hareketlerini de dahil et</label>'
 }else if(type==='accounts'){
  title.textContent='Tüm Cariler Genel Ekstresi';sub.textContent='Tüm müşteri, tedarikçi ve kurum carilerinin gerçek hareket satırlarını tek ekstrede gösterir.';lab.textContent='Cari Tipi';
  sec.innerHTML='<option value="all">Tüm Cariler</option><option value="customer">Müşteriler</option><option value="supplier">Tedarikçiler</option><option value="public">Resmi Kurum</option><option value="private">Özel Kurum</option>';
  from.style.display='';to.style.display='';extra.innerHTML='<div class="inline-note">TL hareketlerinde EUR/USD karşılığı işlem tarihindeki TCMB kuru ile hesaplanır. Eski hareketlerde kur snapshot’ı yoksa TCMB tarihsel arşivinden otomatik tamamlanır; hafta sonu/tatilde önceki yayımlanmış iş günü kullanılır.</div>'
 }else{
  title.textContent='İhale & Talepler Genel Ekstresi';sub.textContent='Tüm ihale ve müşteri taleplerinin operasyonel ve finansal özeti.';lab.textContent='Durum';
  sec.innerHTML='<option value="all">Tüm Durumlar</option>'+[['draft','Taslak'],['collecting','Fiyat Toplanıyor'],['ready','Kıyaslamaya Hazır'],['quote','Müşteri Teklifi Hazır'],['sent','Teklif Gönderildi'],['won','Kazanıldı'],['lost','Kaybedildi'],['cancelled','İptal Edildi']].map(x=>`<option value="${x[0]}">${x[1]}</option>`).join('');
  from.style.display='';to.style.display='';extra.textContent='Tarih filtresi Son Teklif / İhale tarihine göre uygulanır.'
 }
 renderGeneralReport();openModal('generalReportModal')
}

function cashMoveSignedValue(x){const v=Number(x.amountValue??x.amount??0)||0;return x.type==='Giriş'?v:-v}
function buildCashReport(){
 ensureCashMovementIds();const sec=currentReportSecondary(),includeArchived=!!document.getElementById('reportIncludeArchived')?.checked;
 const accounts=state.cashAccounts.filter(a=>sec==='all'||String(a.id)===sec);
 const ids=new Set(accounts.map(a=>Number(a.id))),names=new Set(accounts.map(a=>a.name));
 const accountMatch=x=>sec==='all'||ids.has(Number(x.accountId))||names.has(x.account);
 const allAccountMoves=(state.cash||[]).filter(accountMatch);
 const moves=allAccountMoves.filter(x=>(includeArchived||!x.archived)&&reportDateInRange(x.date)&&reportSearchMatch([x.account,x.request,x.party,x.type,x.note,x.amount]));
 if(sec!=='all'&&accounts.length===1){
   const a=accounts[0],from=document.getElementById('reportDateFrom')?.value||'',fromDate=from?new Date(from+'T00:00:00'):null;
   const netAll=allAccountMoves.reduce((sum,x)=>sum+cashMoveSignedValue(x),0),baseOpening=Number(a.balance||0)-netAll;
   let opening=baseOpening;
   allAccountMoves.forEach(x=>{const d=reportDateValue(x.date);if(fromDate&&d&&d<fromDate)opening+=cashMoveSignedValue(x)});
   let running=opening;
   const ordered=moves.slice().sort((x,y)=>{const dx=reportDateValue(x.date),dy=reportDateValue(y.date);return Number(dx||0)-Number(dy||0)||(Number(x.id||0)-Number(y.id||0))});
   const headers=['Tarih','Talep','Cari','Tür','Giriş','Çıkış','Yürüyen Bakiye','Açıklama','Durum'];
   const rows=ordered.map(x=>{const v=Number(x.amountValue??x.amount??0)||0;running+=cashMoveSignedValue(x);return [x.date||'',x.request||'',x.party||'',x.type||'',x.type==='Giriş'?reportFmtNum(v):'',x.type==='Çıkış'?reportFmtNum(v):'',reportFmtNum(running),x.note||'',x.reversed?'Ters Kayıt':x.archived?'Arşiv':'Aktif']});
   const inTotal=ordered.filter(x=>x.type==='Giriş').reduce((t,x)=>t+(Number(x.amountValue??x.amount??0)||0),0),outTotal=ordered.filter(x=>x.type==='Çıkış').reduce((t,x)=>t+(Number(x.amountValue??x.amount??0)||0),0);
   const summary=`<div class="mini"><span>Dönem Açılış</span><b>${reportEsc(fmtMoneyCur(opening,a.currency))}</b></div><div class="mini"><span>Toplam Giriş</span><b>${reportEsc(fmtMoneyCur(inTotal,a.currency))}</b></div><div class="mini"><span>Toplam Çıkış</span><b>${reportEsc(fmtMoneyCur(outTotal,a.currency))}</b></div><div class="mini"><span>Güncel Bakiye</span><b>${reportEsc(fmtMoneyCur(a.balance,a.currency))}</b></div>`;
   return {title:`${a.name} Hesap Ekstresi`,headers,rows,summary}
 }
 const headers=['Tarih','Hesap','Döviz','Talep','Cari','Tür','Tutar','Açıklama'];
 const rows=moves.map(x=>[x.date||'',x.account||'',x.currency||'',x.request||'',x.party||'',x.type||'',reportFmtNum(x.amountValue??String(x.amount||'').replace(/[^\d,.-]/g,'').replace('.','').replace(',','.')),x.note||'']);
 const totals={};
 moves.forEach(x=>{const c=x.currency||'';if(!totals[c])totals[c]={in:0,out:0};const v=Number(x.amountValue||0);if(x.type==='Giriş')totals[c].in+=v;else totals[c].out+=v});
 const balanceParts=accounts.map(a=>`${reportEsc(a.name)}: <b>${reportEsc(fmtMoneyCur(a.balance,a.currency))}</b>`).join('<br>')||'—';
 const flow=Object.entries(totals).map(([c,v])=>`${c}: +${reportFmtNum(v.in)} / -${reportFmtNum(v.out)}`).join('<br>')||'—';
 const summary=`<div class="mini"><span>Hareket</span><b>${moves.length}</b></div><div class="mini"><span>Hesap</span><b>${accounts.length}</b></div><div class="mini"><span>Giriş / Çıkış</span><b style="font-size:10px">${flow}</b></div><div class="mini"><span>Güncel Bakiye</span><b style="font-size:10px">${balanceParts}</b></div>`;
 return {title:'Kasa & Banka Ekstresi',headers,rows,summary}
}

function effectiveAccountBalance(a){return a.type==='supplier'?-Math.max(supplierAccountDebt(a.name,a.currency),Math.max(0,-Number(a.requestOpen||0))):Number(a.requestOpen||0)}
function buildAccountsReport(){
 const sec=currentReportSecondary(),groups=groupedAccounts(),movementRows=[];
 groups.forEach(g=>{
   const typeOk=sec==='all'||g.type===sec;if(!typeOk)return;
   Object.values(g.accounts||{}).forEach(a=>{
     ensureAccountEntries(a);
     const currentBalance=Number(a.requestOpen||0),weOwe=currentBalance<-0.001;
     const data=getStatementData(a,document.getElementById('reportDateFrom')?.value||'',document.getElementById('reportDateTo')?.value||'');
     data.rows.forEach(x=>{
       if(!reportSearchMatch([g.name,g.type,a.currency,x.date,x.ref,x.desc]))return;
       const eq=tryEntryEquivalents(x,a.currency);
       movementRows.push({g,a,x,eq,weOwe,currentBalance});
     })
   })
 });
 movementRows.sort((p,q)=>String(p.x.date||'').localeCompare(String(q.x.date||''))||String(p.g.name||'').localeCompare(String(q.g.name||''),'tr'));
 const headers=['Tarih','Cari Tipi','Firma / Kurum','Döviz','Referans','Açıklama','Borç','Alacak','EUR Karşılığı','USD Karşılığı','İşlem Kuru','Bakiye','Durum'];
 const rows=movementRows.map(({g,a,x,eq,weOwe})=>[
   x.date||'',accountTypeLabel(g.type),g.name,a.currency,x.ref||'',x.desc||'',
   x.debit?reportFmtNum(x.debit):'',x.credit?reportFmtNum(x.credit):'',
   a.currency==='TRY'?(eq.eur===null?'Hesaplanıyor…':reportFmtNum(eq.eur)):'',
   a.currency==='TRY'?(eq.usd===null?'Hesaplanıyor…':reportFmtNum(eq.usd)):'',
   a.currency==='TRY'?(eq.label||'Hesaplanıyor…'):'',
   reportFmtNum(x.running),weOwe?'BORÇLUYUZ':Number(a.requestOpen||0)>0.001?'ALACAKLIYIZ':'DENGEDE'
 ]);
 const rowClasses=movementRows.map(y=>y.weOwe?'report-we-owe':'');
 const totals={};
 movementRows.forEach(({a,x})=>{const c=a.currency||'EUR';totals[c]=totals[c]||{d:0,c:0};totals[c].d+=Number(x.debit||0);totals[c].c+=Number(x.credit||0)});
 const flow=Object.entries(totals).map(([c,v])=>`${c}: B ${reportFmtNum(v.d)} / A ${reportFmtNum(v.c)}`).join('<br>')||'—';
 const parties=new Set(movementRows.map(y=>accountGroupKey(y.g))).size;
 const oweParties=new Set(movementRows.filter(y=>y.weOwe).map(y=>accountGroupKey(y.g))).size;
 const summary=`<div class="mini"><span>Hareket</span><b>${movementRows.length}</b></div><div class="mini"><span>Cari</span><b>${parties}</b></div><div class="mini report-debt-mini"><span>Borçlu Olduklarımız</span><b>${oweParties}</b></div><div class="mini"><span>Borç / Alacak</span><b style="font-size:10px">${flow}</b></div>`;
 return {title:'Tüm Cariler Genel Ekstresi',headers,rows,rowClasses,summary}
}

function buildRequestsReport(){
 const sec=currentReportSecondary();const list=state.requests.filter(r=>(sec==='all'||r.status===sec)&&reportDateInRange(r.deadline)&&reportSearchMatch([r.no,r.title,r.customer,r.country,r.description,statusLabel(r.status),entityTagObjects(r.tags).map(t=>t.name).join(' ')]));
 const headers=['Talep No','Talep / İhale','Müşteri','Ülke','Son Tarih','Durum','Tedarikçi Teklifi','Kıyaslanan Tutar','Müşteri Teklifi','Beklenen Kâr','Evrak','Etiketler','Açıklama'];
 const rows=list.map(r=>{const comp=Number(r.customerQuote?.cost||selectedComparisonCost(r)||0),sale=Number(r.customerQuote?.total||0),profit=sale?Number(r.customerQuote?.profit??sale-comp):0,dc=requestDocCompletion(r.id),cur=r.currency||'EUR';return [r.no,r.title,r.customer,r.country,r.deadline,statusLabel(r.status),requestSuppliers(r.id).length,comp?fmtMoneyCur(comp,cur):'',sale?fmtMoneyCur(sale,cur):'',sale?fmtMoneyCur(profit,cur):'',`${dc.count}/4`,entityTagObjects(r.tags).map(t=>t.name).join(', '),r.description||'']});
 const active=list.filter(r=>!['lost','cancelled','won'].includes(r.status)).length,won=list.filter(r=>r.status==='won').length;
 const supplierOffers=list.reduce((n,r)=>n+requestSuppliers(r.id).length,0);
 const summary=`<div class="mini"><span>Kayıt</span><b>${list.length}</b></div><div class="mini"><span>Aktif</span><b>${active}</b></div><div class="mini"><span>Kazanıldı</span><b>${won}</b></div><div class="mini"><span>Tedarikçi Teklifi</span><b>${supplierOffers}</b></div>`;
 return {title:'İhale & Talepler Genel Ekstresi',headers,rows,summary}
}

function renderReportTable(headers,rows,rowClasses=[]){
 return `<table><thead><tr>${headers.map(h=>`<th>${reportEsc(h)}</th>`).join('')}</tr></thead><tbody>${rows.length?rows.map((r,ri)=>`<tr class="${rowClasses[ri]||''}">${r.map((c,i)=>`<td class="${i===r.length-1?'report-note':''}">${reportEsc(c)}</td>`).join('')}</tr>`).join(''):`<tr><td colspan="${headers.length}" class="empty">Filtreye uygun kayıt bulunamadı.</td></tr>`}</tbody></table>`
}
function reportSelectedIndexes(){const checks=[...document.querySelectorAll('#reportColumnChooser input[data-col]')];return checks.length?checks.filter(x=>x.checked).map(x=>+x.dataset.col):null}
function renderReportColumnChooser(headers){const box=document.getElementById('reportColumnChooser');if(!box)return;const old={};box.querySelectorAll('input[data-col]').forEach(x=>old[x.dataset.name]=x.checked);box.innerHTML=headers.map((h,i)=>`<label class="tag" style="cursor:pointer"><input type="checkbox" data-col="${i}" data-name="${reportEsc(h)}" ${old[h]===false?'':'checked'} onchange="renderGeneralReport()"> ${reportEsc(h)}</label>`).join('')}
function filteredReport(r){renderReportColumnChooser(r.headers);const idx=reportSelectedIndexes()||r.headers.map((_,i)=>i);return {...r,headers:idx.map(i=>r.headers[i]),rows:r.rows.map(row=>idx.map(i=>row[i])),rowClasses:Array.isArray(r.rowClasses)?r.rowClasses:[]}}
async function exportGeneralReportXlsx(){renderGeneralReport();const r=generalReportCache;try{const res=await fetch('api/export_xlsx.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({title:r.title,headers:r.headers,rows:r.rows})});if(!res.ok){const j=await res.json().catch(()=>({}));throw new Error(j.error||'Excel oluşturulamadı')}const blob=await res.blob(),a=document.createElement('a');a.href=URL.createObjectURL(blob);a.download=r.title.replace(/[^a-zA-Z0-9ğüşöçıİĞÜŞÖÇ_-]+/g,'_')+'_'+todayISO()+'.xlsx';document.body.appendChild(a);a.click();setTimeout(()=>{URL.revokeObjectURL(a.href);a.remove()},100)}catch(e){toast(e.message,'warn')}}
function renderGeneralReport(){
 let r=generalReportType==='cash'?buildCashReport():generalReportType==='accounts'?buildAccountsReport():buildRequestsReport();r=filteredReport(r);
 const table=renderReportTable(r.headers,r.rows,r.rowClasses||[]);generalReportCache={...r,html:table};
 document.getElementById('generalReportSummary').innerHTML=r.summary;document.getElementById('generalReportPreview').innerHTML=table;
 if(generalReportType==='accounts'){const missing=(state.accounts||[]).some(a=>String(a.currency||'').toUpperCase()==='TRY'&&(a.entries||[]).some(e=>entryNeedsHistoricalFx(e)));if(missing)setTimeout(()=>hydrateMissingTryFx(()=>renderGeneralReport()),0)}
}
function reportCompanyHeader(title){
 return `<div style="display:flex;justify-content:space-between;gap:20px;border-bottom:2px solid #163d73;padding-bottom:12px;margin-bottom:14px"><div><h2 style="margin:0">${reportEsc(state.company?.name||'ASAY')}</h2><div style="font-size:11px;margin-top:4px">${reportEsc(state.company?.address||'')}</div></div><div style="text-align:right"><h1 style="margin:0;font-size:20px">${reportEsc(title)}</h1><div style="font-size:11px;margin-top:4px">Rapor Tarihi: ${new Date().toLocaleString('tr-TR')}</div></div></div>`
}
function printGeneralReport(){
 renderGeneralReport();const r=generalReportCache,summary=document.getElementById('generalReportSummary').innerHTML;
 printHtmlInFrame(r.title,`${reportCompanyHeader(r.title)}<div class="summary">${summary}</div>${r.html}`,'@page{size:A4 landscape;margin:10mm}body{font-family:Arial,sans-serif;color:#111;font-size:10px}table{width:100%;border-collapse:collapse}th,td{border:1px solid #bbb;padding:5px;vertical-align:top}th{background:#eef2f7;text-align:left}.summary{display:flex;gap:8px;margin:10px 0 14px}.mini{border:1px solid #ccc;padding:7px 10px;min-width:120px}.mini span{display:block;font-size:8px;text-transform:uppercase;color:#666}.mini b{display:block;margin-top:3px}button{display:none}')
}
function csvCell(v){const s=String(v??'').replaceAll('"','""');return `"${s}"`}
function exportGeneralReportCsv(){
 renderGeneralReport();const r=generalReportCache,lines=[r.headers.map(csvCell).join(';'),...r.rows.map(row=>row.map(csvCell).join(';'))],blob=new Blob(['\ufeff'+lines.join('\r\n')],{type:'text/csv;charset=utf-8'}),a=document.createElement('a');
 a.href=URL.createObjectURL(blob);a.download=r.title.replace(/[^a-zA-Z0-9ğüşöçıİĞÜŞÖÇ_-]+/g,'_')+'_'+todayISO()+'.csv';document.body.appendChild(a);a.click();setTimeout(()=>{URL.revokeObjectURL(a.href);a.remove()},100)
}

function ensureAttachmentState(){if(!Array.isArray(state.requestAttachments))state.requestAttachments=[]}
function syncAttachmentRequestOptions(){
 ensureAttachmentState();const sels=[document.getElementById('attachmentRequestFilter'),document.getElementById('attachmentUploadRequest')];sels.forEach((s,i)=>{if(!s)return;const current=s.value;s.innerHTML=(i===0?'<option value="all">Tüm Talepler</option>':'')+state.requests.map(r=>`<option value="${r.id}">${r.no} · ${reportEsc(r.title||'Konu Yok')} · ${reportEsc(r.customer||'')}</option>`).join('');if([...s.options].some(o=>o.value===current))s.value=current});
}
let attachmentLibraryLoading=false;
async function refreshAttachmentLibraryFromServer(render=true){
 if(attachmentLibraryLoading)return false;
 attachmentLibraryLoading=true;
 try{
   const res=await fetch('api/request_documents.php',{method:'GET',cache:'no-store'});
   let j={};try{j=await res.json()}catch(_e){}
   if(!res.ok||!j.ok)throw new Error(j.error||'Doküman listesi alınamadı');
   ensureAttachmentState();
   const serverFiles=Array.isArray(j.files)?j.files:[];
   const localOnly=(state.requestAttachments||[]).filter(x=>!x.serverId&&!serverFiles.some(s=>Number(s.id)===Number(x.id)));
   state.requestAttachments=[...serverFiles,...localOnly];
   if(render)renderAttachmentLibrary();
   return true
 }catch(e){
   console.error('Doküman kütüphanesi yenilenemedi:',e);
   if(render)renderAttachmentLibrary();
   return false
 }finally{attachmentLibraryLoading=false}
}
let attachmentLibraryTrash=false,attachmentTrashRows=[];
function attachmentCategoryLabel(cat){return {tender:'İhale Dokümanı',technical:'Teknik Şartname',contract:'Sözleşme',drawing:'Çizim',customer:'Müşteri Evrakı',supplier:'Tedarikçi Evrakı',other:'Diğer'}[cat]||cat||'Diğer'}
async function toggleAttachmentTrash(){
 attachmentLibraryTrash=!attachmentLibraryTrash;const b=document.getElementById('attachmentTrashToggle');if(b)b.textContent=attachmentLibraryTrash?'Aktif Dokümanlar':'Çöp Kutusu';
 if(attachmentLibraryTrash){try{const res=await fetch('api/request_documents.php?trash=1',{cache:'no-store'}),raw=await res.text();let j={};try{j=JSON.parse(raw)}catch(_e){}if(!res.ok||!j.ok)throw new Error(j.error||'Çöp kutusu alınamadı');attachmentTrashRows=j.files||[]}catch(e){attachmentLibraryTrash=false;if(b)b.textContent='Çöp Kutusu';toast(e.message,'warn')}}renderAttachmentLibrary()
}
function renderAttachmentLibrary(){sanitizeClientStateInPlace();
 ensureAttachmentState();syncAttachmentRequestOptions();const f=document.getElementById('attachmentRequestFilter')?.value||'all',source=attachmentLibraryTrash?attachmentTrashRows:state.requestAttachments,rows=source.filter(x=>f==='all'||String(x.requestId)===f),box=document.getElementById('attachmentLibrary');document.getElementById('attachmentCount').textContent=rows.length+(attachmentLibraryTrash?' çöpte':' dosya');
 box.innerHTML=rows.length?rows.slice().reverse().map(x=>{const r=state.requests.find(q=>Number(q.id)===Number(x.requestId));const props=`<div class="doc-library-main"><b>${reportEsc(x.name)}</b><div class="doc-property-grid"><span><em>Talep / İhale</em><strong>${reportEsc(r?.no||'—')} · ${reportEsc(r?.title||'Konu belirtilmemiş')}</strong></span><span><em>Müşteri / Kurum</em><strong>${reportEsc(r?.customer||'—')}</strong></span><span><em>Kategori / Revizyon</em><strong>${reportEsc(x.categoryLabel||attachmentCategoryLabel(x.category))} · Rev.${Number(x.version||1)}</strong></span><span><em>Yükleyen / Tarih</em><strong>${reportEsc(x.uploadedBy||'—')} · ${reportEsc(x.created||'—')}</strong></span></div><div class="doc-description-property"><em>Açıklama</em><p>${reportEsc(x.description||'Açıklama girilmemiş.')}</p></div></div>`;return attachmentLibraryTrash?`<div class="doc-library-card doc-trash-card">${props}<div class="actions"><button class="btn green sm" onclick="restoreRequestAttachment(${x.id})">Geri Yükle</button></div></div>`:`<div class="doc-library-card">${props}<div class="actions"><button class="btn sm" onclick="viewRequestAttachment(${x.id})">Görüntüle</button><a class="btn sm" href="${escAttr(x.url||'#')}" target="_blank" rel="noopener">Aç</a><button class="btn sm" onclick="openAttachmentProperties(${x.id})">Özellikler</button><button class="btn red sm" onclick="deleteRequestAttachment(${x.id})">Çöpe Taşı</button></div></div>`}).join(''):`<div class="empty">${attachmentLibraryTrash?'Çöp kutusunda doküman yok.':'Yüklenmiş talep dokümanı yok.'}</div>`
}
function attachmentExtension(name=''){const clean=String(name||'').split('?')[0].split('#')[0],i=clean.lastIndexOf('.');return i>=0?clean.slice(i+1).toLowerCase():''}
function attachmentPreviewKind(x){const ext=attachmentExtension(x?.name||x?.url||'');if(ext==='pdf')return 'pdf';if(['jpg','jpeg','png','webp','gif','bmp','svg'].includes(ext))return 'image';if(['txt','csv','json','xml','md','log'].includes(ext))return 'text';return 'other'}
function closeAttachmentViewer(){const frame=document.querySelector('#attachmentViewerBody iframe');if(frame)frame.src='about:blank';closeModal('attachmentViewerModal')}
async function viewRequestAttachment(id){
 ensureAttachmentState();const x=state.requestAttachments.find(a=>Number(a.id)===Number(id));if(!x)return toast('Doküman bulunamadı.','warn');const r=state.requests.find(q=>Number(q.id)===Number(x.requestId)),kind=attachmentPreviewKind(x),body=document.getElementById('attachmentViewerBody'),open=document.getElementById('attachmentViewerOpen');document.getElementById('attachmentViewerTitle').textContent=x.name||'Doküman';document.getElementById('attachmentViewerMeta').textContent=`${r?.no||''} · ${r?.title||'Konu belirtilmemiş'} · ${r?.customer||''} · ${x.categoryLabel||x.category||'Diğer'} · Rev.${x.version||1}`;open.href=x.url||'#';const properties=`<div class="attachment-properties"><div><span>Talep / İhale</span><b>${reportEsc(r?.no||'—')} · ${reportEsc(r?.title||'Konu belirtilmemiş')}</b></div><div><span>Müşteri / Kurum</span><b>${reportEsc(r?.customer||'—')}</b></div><div class="full"><span>Doküman Açıklaması</span><b>${reportEsc(x.description||'Açıklama girilmemiş.')}</b></div></div>`;body.innerHTML=properties+'<div class="empty">Doküman hazırlanıyor…</div>';openModal('attachmentViewerModal');if(!x.url){body.innerHTML=properties+'<div class="empty">Bu doküman için dosya adresi bulunamadı.</div>';return}if(kind==='pdf'){body.innerHTML=properties+`<iframe class="attachment-pdf-frame" src="${escAttr(x.url)}" title="${escAttr(x.name||'PDF')}"></iframe>`;return}if(kind==='image'){body.innerHTML=properties+`<div class="attachment-image-stage"><img src="${escAttr(x.url)}" alt="${escAttr(x.name||'Doküman')}"></div>`;return}if(kind==='text'){try{const res=await fetch(x.url,{cache:'no-store'});if(!res.ok)throw new Error('HTTP '+res.status);const text=await res.text();body.innerHTML=properties+`<pre class="attachment-text-preview">${reportEsc(text.slice(0,250000))}</pre>${text.length>250000?'<div class="inline-note">Önizleme ilk 250.000 karakterle sınırlandı.</div>':''}`}catch(e){body.innerHTML=properties+`<div class="empty">Metin önizlemesi alınamadı.<br><small>${reportEsc(e.message)}</small></div>`}return}body.innerHTML=properties+`<div class="attachment-unsupported"><div class="attachment-file-icon">📄</div><h3>${reportEsc(x.name||'Doküman')}</h3><p>Bu dosya türü tarayıcı içinde güvenilir biçimde önizlenemiyor.</p><a class="btn primary" href="${escAttr(x.url)}" target="_blank" rel="noopener">Dosyayı Aç</a></div>`
}
function openAttachmentUpload(rid){syncAttachmentRequestOptions();if(rid)document.getElementById('attachmentUploadRequest').value=rid;document.getElementById('attachmentUploadDescription').value='';document.getElementById('attachmentUploadFiles').value='';document.getElementById('attachmentUploadFileDescriptions').innerHTML='';openModal('attachmentUploadModal')}
function renderAttachmentFileDescriptions(){const files=[...document.getElementById('attachmentUploadFiles').files],box=document.getElementById('attachmentUploadFileDescriptions');if(!box)return;box.innerHTML=files.length>1?files.map((f,i)=>`<div class="doc-upload-file-row"><b title="${escAttr(f.name)}">${reportEsc(f.name)}</b><input data-doc-file-desc="${i}" placeholder="Bu dosyaya özel açıklama"></div>`).join(''):''}
async function uploadFilesForRequest(rid,files,description='',category='other',version=1){
 if(!files?.length)return true;if(!state.requests.some(r=>Number(r.id)===Number(rid)))return toast('Doküman bağlanacak talep tarayıcıda bulunamadı.','warn'),false;const dbReady=await saveDbState(false);if(!dbReady)return toast('Talep veritabanı kaydı doğrulanamadığı için dosya yükleme başlatılmadı.','warn'),false;const send=async()=>{const fd=new FormData();fd.append('request_id',rid);fd.append('description',description);fd.append('category',category);fd.append('version_no',version);[...files].forEach(f=>fd.append('files[]',f));const res=await fetch('api/request_documents.php',{method:'POST',body:fd,headers:{'Accept':'application/json'}}),raw=await res.text();let j={};try{j=JSON.parse(raw)}catch(_e){}return {res,j}};try{let {res,j}=await send();if(res.status===404){const saved=await saveDbState(true);if(saved)({res,j}=await send())}if(!res.ok||!j.ok)throw new Error(j.error||'Yükleme başarısız');ensureAttachmentState();state.requestAttachments.push(...j.files);renderAttachmentLibrary();await refreshAttachmentLibraryFromServer(true);if(j.rejected?.length)toast(j.rejected.map(x=>x.name+': '+x.reason).join(' | '),'warn');return true}catch(e){toast('Doküman yüklenemedi: '+e.message,'warn');return false}
}
async function uploadAttachmentModalFiles(){const rid=+document.getElementById('attachmentUploadRequest').value,files=[...document.getElementById('attachmentUploadFiles').files],common=document.getElementById('attachmentUploadDescription').value.trim(),cat=document.getElementById('attachmentUploadCategory').value,ver=+document.getElementById('attachmentUploadVersion').value||1;if(!rid||!files.length)return toast('Talep ve dosya seçin.','warn');let ok=true;for(let i=0;i<files.length;i++){const per=(document.querySelector(`[data-doc-file-desc="${i}"]`)?.value||'').trim(),desc=per||common;if(!await uploadFilesForRequest(rid,[files[i]],desc,cat,ver)){ok=false;break}}if(ok){closeModal('attachmentUploadModal');toast('Dokümanlar yüklendi.','good')}}
function openAttachmentProperties(id){const x=state.requestAttachments.find(a=>Number(a.id)===Number(id));if(!x)return toast('Doküman bulunamadı.','warn');document.getElementById('attachmentPropertiesId').value=id;document.getElementById('attachmentPropertiesName').textContent=x.name||'';document.getElementById('attachmentPropertiesDescription').value=x.description||'';document.getElementById('attachmentPropertiesCategory').value=x.category||'other';document.getElementById('attachmentPropertiesVersion').value=Number(x.version||1);openModal('attachmentPropertiesModal')}
async function saveAttachmentProperties(){const id=+document.getElementById('attachmentPropertiesId').value,payload={id,description:document.getElementById('attachmentPropertiesDescription').value.trim(),category:document.getElementById('attachmentPropertiesCategory').value,version:+document.getElementById('attachmentPropertiesVersion').value||1};try{const res=await fetch('api/request_documents.php',{method:'PATCH',headers:{'Content-Type':'application/json','Accept':'application/json'},body:JSON.stringify(payload)}),j=await res.json();if(!res.ok||!j.ok)throw new Error(j.error||'Özellikler kaydedilemedi');const x=state.requestAttachments.find(a=>Number(a.id)===id);if(x){x.description=payload.description;x.category=payload.category;x.categoryLabel=attachmentCategoryLabel(payload.category);x.version=payload.version}closeModal('attachmentPropertiesModal');renderAttachmentLibrary();toast('Doküman özellikleri güncellendi.','good')}catch(e){toast(e.message,'warn')}}
async function deleteRequestAttachment(id){const x=state.requestAttachments.find(a=>Number(a.id)===Number(id));if(!x||!await appConfirm(x.name+' çöp kutusuna taşınsın mı?','Doküman Silme'))return;try{const res=await fetch('api/request_documents.php?id='+encodeURIComponent(x.serverId||x.id),{method:'DELETE',headers:{'Accept':'application/json'}}),j=await res.json();if(!res.ok||!j.ok)throw new Error(j.error||'Dosya çöpe taşınamadı');state.requestAttachments=state.requestAttachments.filter(a=>Number(a.id)!==Number(id));renderAttachmentLibrary();toast('Doküman çöp kutusuna taşındı.','good')}catch(e){toast('Dosya taşınamadı: '+e.message,'warn')}}
async function restoreRequestAttachment(id){try{const fd=new FormData();fd.append('action','restore');fd.append('id',id);const res=await fetch('api/request_documents.php',{method:'POST',body:fd,headers:{'Accept':'application/json'}}),j=await res.json();if(!res.ok||!j.ok)throw new Error(j.error||'Doküman geri yüklenemedi');attachmentTrashRows=attachmentTrashRows.filter(x=>Number(x.id)!==Number(id));await refreshAttachmentLibraryFromServer(false);renderAttachmentLibrary();toast('Doküman geri yüklendi.','good')}catch(e){toast(e.message,'warn')}}
function renderCash(){normalizeStateSchema();sanitizeClientStateInPlace();renderCashAccounts();ensureCashMovementIds();const body=document.getElementById('cashRows');if(!body)return;const account=state.cashAccounts.find(x=>Number(x.id)===Number(activeCashAccountId));const title=document.getElementById('cashMovementPanelTitle'),sub=document.getElementById('cashMovementPanelSub');if(!account){if(title)title.textContent='Kasa / Banka Hesap Hareketleri';if(sub)sub.textContent='Yukarıdan bir kasa veya banka hesabı seçin.';body.innerHTML='<tr><td colspan="6" class="empty">Bir hesap seçin.</td></tr>';return}if(title)title.textContent=`${account.name} · Hesap Hareketleri`;if(sub)sub.textContent=`${account.type} · ${account.currency} · Güncel bakiye ${fmtMoneyCur(account.balance,account.currency)}`;const visible=(state.cash||[]).filter(x=>Number(x.accountId)===Number(account.id)&&!x.archived&&!x.reversed&&x.kind!=='reversal'&&!x.uiDeleted).sort((x,y)=>String(y.date||'').localeCompare(String(x.date||'')));body.innerHTML=visible.length?visible.map(x=>{const v=Number((x.amountValue??x.amount)||0),signed=x.type==='Giriş'?v:-v;return `<tr><td data-label="Tarih">${x.date||'—'}</td><td data-label="Talep">${x.request||'—'}</td><td data-label="Cari">${x.party||'—'}</td><td data-label="Tutar" class="${signed>=0?'running-pos':'running-neg'}"><b>${signed>=0?'+':'−'}${fmtMoneyCur(Math.abs(signed),x.currency||account.currency)}</b></td><td data-label="Açıklama">${esc(x.note||'—')}</td><td data-label="İşlem"><div class="actions"><button class="btn sm" ${x.journalId?'':'disabled'} onclick="openFinanceMovementEdit(${Number(x.journalId||0)})">Düzenle</button><button class="btn red sm" onclick="deleteCashMovement('${x._id}')">Sil</button></div></td></tr>`}).join(''):'<tr><td colspan="6" class="empty">Bu hesapta hareket bulunmuyor.</td></tr>'}
async function saveCash(){
 const bankId=+document.getElementById('cashBank').value,bank=state.cashAccounts.find(x=>Number(x.id)===Number(bankId)),partyId=+document.getElementById('cashParty').value,mode=document.getElementById('cashType').value,amount=+document.getElementById('cashAmount').value||0,date=document.getElementById('cashDate').value||todayISO(),request=document.getElementById('cashRequest').value||'',ref=document.getElementById('cashRef').value.trim()||request||'FİNANS',description=document.getElementById('cashDesc').value.trim()||(mode==='receipt'?'Müşteri Tahsilatı':'Tedarikçiye Ödeme'),poId=+document.getElementById('cashPo')?.value||0;
 if(await runServerOperation({action:'cash_movement',bankId,bankName:bank?.name||'',bankCurrency:bank?.currency||'',partyId,mode,amount,date,request,ref,description,poId,idempotencyKey:makeIdempotencyKey()})){closeModal('cashModal');toast((mode==='receipt'?'Tahsilat':'Ödeme')+' transaction ile kaydedildi.','good')}
}

function documentEditableItems(rid){const r=state.requests.find(x=>Number(x.id)===Number(rid));return r?Array.isArray(r.items)?r.items:[]:[]}
function renderDocumentItemEditor(){
 const box=document.getElementById('documentItemEditor'),wrap=document.getElementById('documentItemControls');if(!box||!wrap)return;const type=document.getElementById('docType')?.value||'offer',rid=+document.getElementById('docRequestSelect')?.value||0,items=documentEditableItems(rid);wrap.style.display=type==='checklist'?'none':'block';if(type==='checklist')return;
 box.innerHTML=items.length?`<div class="table-wrap"><table class="table responsive compact-table"><thead><tr><th>Ürün</th><th>GTİP / HS Code</th><th>Menşe / Origin</th></tr></thead><tbody>${items.map((it,i)=>`<tr><td data-label="Ürün"><b>${esc(it.name||'Kalem '+(i+1))}</b></td><td data-label="GTİP / HS Code"><input value="${escAttr(it.hs||it.gtip||it.hsCode||'')}" placeholder="Örn. 8416.20.80.00.00" onchange="updateDocumentItemTradeData(${rid},${i},'hs',this.value)"></td><td data-label="Menşe"><input value="${escAttr(it.origin||it.countryOfOrigin||'Türkiye')}" placeholder="Türkiye" onchange="updateDocumentItemTradeData(${rid},${i},'origin',this.value)"></td></tr>`).join('')}</tbody></table></div>`:'<div class="empty">Seçili talepte ürün kalemi bulunmuyor.</div>'
}
async function updateDocumentItemTradeData(rid,index,key,value){
 const r=state.requests.find(x=>Number(x.id)===Number(rid)),it=r?.items?.[index];if(!r||!it)return;const v=String(value||'').trim();if(key==='hs'){it.hs=v;it.gtip=v;it.hsCode=v}else{it.origin=v;it.countryOfOrigin=v}
 const qi=(r.customerQuote?.items||[]).find(x=>Number(x.itemId||0)===Number(it.id||0)||(it.uid&&x.itemUid===it.uid)||String(x.name||'').trim()===String(it.name||'').trim());if(qi){if(key==='hs'){qi.hs=v;qi.gtip=v;qi.hsCode=v}else{qi.origin=v;qi.countryOfOrigin=v}}
 packingRows.forEach(x=>{if(Number(x.itemId||0)===Number(it.id||0)||(it.uid&&x.itemUid===it.uid)){if(key==='hs')x.hs=v;else x.origin=v}});renderPackingEditor();renderDocument();if(window.saveDbState)await saveDbState(true);toast((key==='hs'?'GTİP / HS Code':'Menşe')+' kaydedildi.','good')
}
let packingRows=[],packingRequestId=0;
function packingUnitLabel(unit=''){const u=String(unit||'').toLowerCase();if(u.includes('kg'))return 'Kg / Kg';if(u.includes('koli')||u.includes('carton'))return 'Koli / Carton';if(u.includes('palet')||u.includes('pallet'))return 'Palet / Pallet';if(u.includes('set'))return 'Set / Set';return 'Adet / Pcs'}
function packingRowsFromRequest(rid){
 const r=state.requests.find(x=>Number(x.id)===Number(rid));if(!r)return[];
 return (r.items||[]).map((it,i)=>({itemId:Number(it.id)||0,itemUid:it.uid||'',packageNo:'PKG-'+String(i+1).padStart(2,'0'),type:'Koli / Carton',product:it.name||'',hs:it.hs||it.gtip||it.hsCode||'',origin:it.origin||it.countryOfOrigin||'Türkiye',qty:Number(it.qty||0),unit:packingUnitLabel(it.unit),net:Number(it.netWeight||it.net||0),gross:Number(it.grossWeight||it.gross||0)}))
}
function syncPackingRowsFromRequest(force=false){
 const rid=+document.getElementById('docRequestSelect')?.value||0;if(!rid)return;
 if(force||packingRequestId!==rid||!packingRows.length){packingRows=packingRowsFromRequest(rid);packingRequestId=rid}
 renderPackingEditor()
}
function packLineTotal(r,key){return Math.max(0,Number(r?.qty||0))*Math.max(0,Number(r?.[key]||0))}
function renderPackingEditor(){
 const box=document.getElementById('packingEditor');if(!box)return;
 if(!packingRows.length){box.innerHTML='<div class="empty">Seçili talepte Packing List kalemi bulunmuyor.</div>';updatePackingTotals();return}
 box.innerHTML=`<div class="packing-grid-head"><span>Paket</span><span>Tip</span><span>Ürün</span><span>Miktar</span><span>Birim</span><span>Birim Net kg</span><span>Birim Brüt kg</span><span>Toplam Net</span><span>Toplam Brüt</span><span></span></div>`+packingRows.map((r,i)=>`<div class="pack-row checklist-style" data-pack-index="${i}"><label><span>Paket</span><input value="${escAttr(r.packageNo||'')}" oninput="packingRows[${i}].packageNo=this.value;renderDocument()"></label><label><span>Tip</span><select onchange="packingRows[${i}].type=this.value;renderDocument()">${['Koli / Carton','Palet / Pallet','Sandık / Crate','Paket / Package','Adet / Piece','Rulo / Roll','Set / Set'].map(x=>`<option ${x.startsWith(String(r.type||'').split(' / ')[0])?'selected':''}>${x}</option>`).join('')}</select></label><label><span>Ürün</span><input value="${escAttr(r.product||'')}" oninput="packingRows[${i}].product=this.value;renderDocument()"><small>${r.hs?'GTİP '+esc(r.hs):''}${r.origin?' · '+esc(r.origin):''}</small></label><label><span>Miktar</span><input type="text" inputmode="decimal" data-pack-field="qty" value="${escAttr(String(r.qty??0).replace('.',','))}" oninput="updatePackingNumber(${i},'qty',this.value)"></label><label><span>Birim</span><select onchange="packingRows[${i}].unit=this.value;renderDocument()">${['Adet / Pcs','Koli / Carton','Set / Set','Palet / Pallet','Kg / Kg'].map(x=>`<option ${x.startsWith(String(r.unit||'').split(' / ')[0])?'selected':''}>${x}</option>`).join('')}</select></label><label><span>Birim Net kg</span><input type="text" inputmode="decimal" data-pack-field="net" value="${escAttr(String(r.net??0).replace('.',','))}" oninput="updatePackingNumber(${i},'net',this.value)"></label><label><span>Birim Brüt kg</span><input type="text" inputmode="decimal" data-pack-field="gross" value="${escAttr(String(r.gross??0).replace('.',','))}" oninput="updatePackingNumber(${i},'gross',this.value)"></label><div class="pack-calc"><span>Toplam Net</span><b data-pack-total="net">${formatQuantity(packLineTotal(r,'net'))} kg</b></div><div class="pack-calc"><span>Toplam Brüt</span><b data-pack-total="gross">${formatQuantity(packLineTotal(r,'gross'))} kg</b></div><button class="btn red sm" type="button" onclick="packingRows.splice(${i},1);renderPackingEditor();renderDocument()">Sil</button></div>`).join('');
 updatePackingTotals();
}
function addPackRow(){packingRows.push({itemId:0,itemUid:'',packageNo:'PKG-'+String(packingRows.length+1).padStart(2,'0'),type:'Koli / Carton',product:'',hs:'',origin:'Türkiye',qty:1,unit:'Adet / Pcs',net:0,gross:0});renderPackingEditor();renderDocument()}
function updatePackingTotals(){const packages=packingRows.reduce((n,x)=>n+(+x.qty||0),0),net=packingRows.reduce((n,x)=>n+packLineTotal(x,'net'),0),gross=packingRows.reduce((n,x)=>n+packLineTotal(x,'gross'),0);if(document.getElementById('packCountTotal'))document.getElementById('packCountTotal').textContent=formatQuantity(packages);if(document.getElementById('packNetTotal'))document.getElementById('packNetTotal').textContent=formatQuantity(net)+' kg';if(document.getElementById('packGrossTotal'))document.getElementById('packGrossTotal').textContent=formatQuantity(gross)+' kg'}
function loadCompanyInfoIntoDocument(){
 const vals={docCompanyName:state.company.name||state.company.brand||'',docCompanyAddress:state.company.address||'',docCompanyPhone:state.company.phone||'',docCompanyWeb:state.company.web||'',docCompanyEmail:state.company.email||''};
 Object.entries(vals).forEach(([id,v])=>{const e=document.getElementById(id);if(e)e.value=v});
 renderDocument()
}
function ensureDocumentCompanyFields(){
 const vals={docCompanyName:state.company.name||state.company.brand||'',docCompanyAddress:state.company.address||'',docCompanyPhone:state.company.phone||'',docCompanyWeb:state.company.web||'',docCompanyEmail:state.company.email||''};
 Object.entries(vals).forEach(([id,v])=>{const e=document.getElementById(id);if(e&&!e.value)e.value=v})
}
function documentCompanyInfoHtml(){
 const val=id=>(document.getElementById(id)?.value||'').trim(),show=id=>document.getElementById(id)?.value!=='0',rows=[];
 if(show('docShowCompanyName')&&val('docCompanyName'))rows.push(`<b>${val('docCompanyName')}</b>`);
 if(show('docShowCompanyAddress')&&val('docCompanyAddress'))rows.push(`<span class="pdf-company-line">${val('docCompanyAddress')}</span>`);
 if(show('docShowCompanyPhone')&&val('docCompanyPhone'))rows.push(`<span class="pdf-company-line"><b>Tel:</b> ${val('docCompanyPhone')}</span>`);
 if(show('docShowCompanyWeb')&&val('docCompanyWeb'))rows.push(`<span class="pdf-company-line"><b>Web:</b> ${val('docCompanyWeb')}</span>`);
 if(show('docShowCompanyEmail')&&val('docCompanyEmail'))rows.push(`<span class="pdf-company-line"><b>E-mail:</b> ${val('docCompanyEmail')}</span>`);
 return `<div class="pdf-company-block">${rows.join('')}</div>`
}
function docHeader(title){
 const accent=document.getElementById('pdfAccent')?.value||'#163d73';ensureDocumentCompanyFields();const rid=+document.getElementById('docRequestSelect')?.value||0,req=state.requests.find(x=>x.id===rid),date=todayTR(),docNo=req?.customerQuote?.no||req?.no||'ASAY';
 return `<div class="doc-title-row" style="border-color:${accent}">${documentCompanyInfoHtml()}<div class="pdf-title"><b>${title}</b><div>${title==='PACKING LIST'?'PKL-'+docNo:docNo}</div><small>${date}</small></div></div><div class="doc-meta-zone" id="pdfMetaZone"><div class="doc-info-block customer" id="pdfCustomerInfo"><b>Customer / Müşteri</b><br>${req?.customer||'—'}<br>${req?.country||''}</div><div class="doc-info-block trade" id="pdfTradeInfo"><b>Delivery / Teslim</b><br>${req?.delivery||'—'} ${req?.deliveryPlace||req?.country||''}<br><b>Payment / Ödeme</b><br>${req?.payment||'—'}</div></div>`
}
function ensureChecklistState(){if(!state.checklistNotes||typeof state.checklistNotes!=='object')state.checklistNotes={}}
function checklistRowsForRequest(rid){
 ensureChecklistState();const r=state.requests.find(x=>x.id===rid);if(!r)return[];const sups=requestSuppliers(rid),rows=[];
 (r.items||[]).forEach(it=>sups.forEach(sup=>{
   const vi=supplierVatInfo(sup,it.id),price=Number(vi.effective||0),qty=Number(it.qty||0);if(!(price>0)||!(qty>0))return;
   const originalCurrency=supplierQuoteCurrency(sup,r),originalTotal=price*qty,eurUnit=originalCurrency==='EUR'?price:supplierConvertedAmount(sup,price,'EUR',false,r),eurTotal=originalCurrency==='EUR'?originalTotal:supplierConvertedAmount(sup,originalTotal,'EUR',false,r),key=`${rid}-${it.id}-${sup.id}`,selected=Number(selectedSupplierId(rid,it.id))===Number(sup.id);
   rows.push({key,item:it.name,qty,unit:it.unit||'',unitPrice:price,total:originalTotal,originalCurrency,eurUnit:Number(eurUnit||0),eurTotal:Number(eurTotal||0),supplier:sup.name,supplierId:sup.id,selected,description:(state.checklistNotes[key]??sup.description??'')})
 }));
 return rows
}
function checklistSupplierTotals(rows){
 const map=new Map();(rows||[]).forEach(x=>{const k=String(x.supplierId||x.supplier),o=map.get(k)||{supplier:x.supplier,currency:x.originalCurrency,original:0,eur:0};o.original+=Number(x.total||0);o.eur+=Number(x.eurTotal||0);map.set(k,o)});return [...map.values()]
}
function checklistSupplierTotalsHtml(rows){
 const totals=checklistSupplierTotals(rows);if(!totals.length)return'';return `<div class="checklist-supplier-totals">${totals.map(x=>`<div class="checklist-supplier-total"><b>${esc(x.supplier)}</b><span>Orijinal Toplam: <strong>${fmtMoneyCur(x.original,x.currency)}</strong></span><span>EUR Karşılığı: <strong>${fmtMoneyCur(x.eur,'EUR')}</strong></span></div>`).join('')}</div>`
}
function renderChecklistEditor(){
 ensureChecklistState();const box=document.getElementById('checklistEditor');if(!box)return;const rid=+document.getElementById('docRequestSelect')?.value||0,rows=checklistRowsForRequest(rid);box.dataset.rid=String(rid);
 box.innerHTML=rows.length?rows.map(x=>`<div class="checklist-edit-row dual-currency"><div><b>${x.item}</b><div class="sub">${x.selected?'✓ Seçili tedarikçi':'Kıyaslama satırı'} · ${esc(x.supplier)}</div></div><div>${formatQuantity(x.qty)} ${x.unit}</div><div><b>${fmtMoneyCur(x.unitPrice,x.originalCurrency)}</b><small>EUR: ${fmtMoneyCur(x.eurUnit,'EUR')}</small></div><div><b>${fmtMoneyCur(x.total,x.originalCurrency)}</b><small>EUR: ${fmtMoneyCur(x.eurTotal,'EUR')}</small></div><input class="check-desc" value="${String(x.description||'').replaceAll('"','&quot;')}" placeholder="Açıklama" oninput="setChecklistNote('${x.key}',this.value)"></div>`).join('')+checklistSupplierTotalsHtml(rows):'<div class="empty">Check List için tedarikçi fiyatı bulunmuyor. Önce ürün kıyaslamasına fiyat girin.</div>'
}
function setChecklistNote(key,value){ensureChecklistState();state.checklistNotes[key]=value;renderDocument()}

function defaultPdfColumns(type='offer'){const maps={offer:[['product','Ürün / Product'],['hs','GTİP / HS'],['origin','Menşe / Origin'],['qty','Miktar / Qty'],['unitPrice','Birim Fiyat / Unit Price'],['total','Toplam / Total']],proforma:[['product','Ürün / Product'],['hs','GTİP / HS'],['origin','Menşe / Origin'],['qty','Miktar / Qty'],['unitPrice','Birim Fiyat / Unit Price'],['total','Toplam / Total']],invoice:[['product','Ürün / Product'],['hs','GTİP / HS'],['origin','Menşe / Origin'],['qty','Miktar / Qty'],['unitPrice','Birim Fiyat / Unit Price'],['total','Toplam / Total']],packing:[['package','Paket No'],['type','Paket Tipi'],['product','Ürün'],['hs','GTİP / HS'],['origin','Menşe'],['qty','Adet/Koli'],['net','Net'],['gross','Brüt'],['totalNet','Toplam Net'],['totalGross','Toplam Brüt']],checklist:[['item','Talep Kalemi'],['qty','Talep Ad.'],['unitPrice','Birim Fiyat (Orijinal / EUR)'],['total','Toplam (Orijinal / EUR)'],['supplier','Firma'],['description','Açıklama']]};return (maps[type]||maps.offer).map(([key,label])=>({key,label,show:true,custom:false,value:''}))}
function ensurePdfColumns(type=document.getElementById('docType')?.value||'offer'){state.pdfColumnsByType=state.pdfColumnsByType||{};if(!Array.isArray(state.pdfColumnsByType[type])||!state.pdfColumnsByType[type].length)state.pdfColumnsByType[type]=defaultPdfColumns(type);if(type==='checklist'){const a=state.pdfColumnsByType[type];state.pdfColumnsByType[type]=a.filter(c=>!['unitPriceEur','totalEur'].includes(c.key));const labels={unitPrice:'Birim Fiyat (Orijinal / EUR)',total:'Toplam (Orijinal / EUR)'};state.pdfColumnsByType[type].forEach(c=>{if(labels[c.key]&&['Birim Fiyat','Toplam Tutar','Orijinal Birim Fiyat','Orijinal Toplam'].includes(c.label))c.label=labels[c.key]})}return state.pdfColumnsByType[type]}
function renderPdfColumnManager(){const b=document.getElementById('pdfColumnManager');if(!b)return;const type=document.getElementById('docType')?.value||'offer',cols=ensurePdfColumns(type);b.innerHTML=cols.map((c,i)=>`<div class="pdf-column-row" draggable="true" ondragstart="pdfColumnDragStart(event,${i})" ondragover="event.preventDefault()" ondrop="pdfColumnDrop(event,${i})"><input type="checkbox" ${c.show?'checked':''} onchange="togglePdfColumn('${c.key}',this.checked)"><input value="${escAttr(c.label)}" onchange="renamePdfColumn('${c.key}',this.value)" style="width:100%;border:0;background:transparent;font-weight:700;color:var(--text)">${c.custom?`<input value="${escAttr(c.value||'')}" placeholder="Sabit değer" onchange="setPdfCustomValue('${c.key}',this.value)" style="width:100px">`:''}<button class="btn sm" onclick="movePdfColumn(${i},-1)">↑</button><button class="btn sm" onclick="movePdfColumn(${i},1)">↓</button>${c.custom?`<button class="btn red sm" onclick="deletePdfColumn('${c.key}')">Sil</button>`:''}</div>`).join('')+`<button class="btn sm" onclick="addPdfCustomColumn()">+ Özel Sütun Ekle</button>`}
let pdfDragIndex=null;function pdfColumnDragStart(e,i){pdfDragIndex=i;e.dataTransfer.effectAllowed='move'}function pdfColumnDrop(e,i){e.preventDefault();if(pdfDragIndex===null||pdfDragIndex===i)return;const a=ensurePdfColumns(),[x]=a.splice(pdfDragIndex,1);a.splice(i,0,x);pdfDragIndex=null;renderPdfColumnManager();renderDocument();queueDbSave('PDF düzeni')}
function togglePdfColumn(key,show){const c=ensurePdfColumns().find(x=>x.key===key);if(c)c.show=show;renderDocument({refreshEditors:false});queueDbSave('PDF düzeni')}function renamePdfColumn(key,label){const c=ensurePdfColumns().find(x=>x.key===key);if(c)c.label=label;renderDocument({refreshEditors:false});queueDbSave('PDF düzeni')}function setPdfCustomValue(key,value){const c=ensurePdfColumns().find(x=>x.key===key);if(c)c.value=value;renderDocument({refreshEditors:false});queueDbSave('PDF düzeni')}
function addPdfCustomColumn(){const a=ensurePdfColumns(),key='custom_'+Date.now();a.push({key,label:'Yeni Sütun',show:true,custom:true,value:''});renderPdfColumnManager();renderDocument();queueDbSave('PDF düzeni')}
function movePdfColumn(i,d){const a=ensurePdfColumns(),j=i+d;if(j<0||j>=a.length)return;[a[i],a[j]]=[a[j],a[i]];renderPdfColumnManager();renderDocument();queueDbSave('PDF düzeni')}
function ensurePdfRowCustom(type=document.getElementById('docType')?.value||'offer'){state.pdfRowCustom=state.pdfRowCustom||{};return state.pdfRowCustom[type]??={}}
function setPdfRowCustom(type,row,key,value){const a=ensurePdfRowCustom(type);a[row]=a[row]||{};a[row][key]=value;queueDbSave('PDF satır ayarı')}
function pdfCustomCell(type,row,c){const v=ensurePdfRowCustom(type)?.[row]?.[c.key]??c.value??'';return `<span contenteditable="true" oninput="setPdfRowCustom('${type}',${row},'${c.key}',this.textContent)">${String(v).replaceAll('&','&amp;').replaceAll('<','&lt;').replaceAll('>','&gt;')}</span>`}
function commercialTableHtml(items,cur,type=document.getElementById('docType')?.value||'offer'){const showHs=document.getElementById('docShowHs')?.value!=='0',showOrigin=document.getElementById('docShowOrigin')?.value!=='0',cols=ensurePdfColumns(type).filter(c=>c.show&&(c.key!=='hs'||showHs)&&(c.key!=='origin'||showOrigin)),heads=cols.map(c=>`<th>${c.label}</th>`).join(''),cell=(c,x,ri)=>{if(c.custom)return pdfCustomCell(type,ri,c);const unit=Number(x.sale??x.unitPrice??0),total=Number(x.total??unit*Number(x.qty||0));return {product:x.name||'',hs:x.hs||x.gtip||'',origin:x.origin||'Türkiye',qty:`${formatQuantity(x.qty||0)} ${x.unit||''}`,unitPrice:fmtMoneyCur(unit,cur),total:fmtMoneyCur(total,cur)}[c.key]||''};return `<table><thead><tr>${heads}</tr></thead><tbody>${items.map((x,ri)=>`<tr>${cols.map(c=>`<td>${cell(c,x,ri)}</td>`).join('')}</tr>`).join('')||`<tr><td colspan="${cols.length||1}">Kalem bulunamadı.</td></tr>`}</tbody></table>`}
/* Document Studio functions moved to documents.js in V3.7.1 */
function setAccent(c){document.documentElement.style.setProperty('--accent',c);document.documentElement.style.setProperty('--accent-2',c);const l=document.getElementById('accentHexLabel');if(l)l.textContent=String(c||'').toUpperCase()}
const settingsCategoryMeta={tags:['Etiket Yönetimi','Cari ve talepler için ortak etiket kataloğu, renkler ve kullanım bilgileri.'],appearance:['Genel & Görünüm','Uygulama kimliği, tema, yoğunluk ve talep numaralandırması.'],actions:['Aksiyon Merkezi','Dashboard uyarı türleri, tarih kapsamı, eşikler ve kritik renkler.'],colors:['Durum Renkleri','Talep durumları ve kıyaslama hücreleri için görsel durum dili.'],company:['Firma & Belgeler','Firma kimliği, varsayılan banka hesapları, Google Drive ve PDF tercihleri.'],access:['Kullanıcı & Yetki','Kullanıcılar, rol matrisi ve kritik işlem geçmişi.'],system:['Sistem & Yedek','Çalışan build, veri katmanı, veritabanı yedekleme ve geri yükleme araçları.']};
function settingsCategoryNodes(){
 const requestNo=document.getElementById('requestNoPrefix')?.closest('.panel'),audit=document.getElementById('auditRows')?.closest('.panel'),roles=document.getElementById('permissionsTable')?.closest('.panel');
 return {tags:[document.getElementById('settingsTags')],appearance:[document.getElementById('settingsAppearance'),document.querySelector('.settings-theme-panel'),requestNo],actions:[document.getElementById('settingsActions')],colors:[document.getElementById('settingsStatusColors')],company:[document.querySelector('.settings-company-tools')],access:[audit,document.getElementById('settingsAccess'),roles],system:[document.getElementById('settingsSystemInfo'),document.getElementById('settingsDatabase')]}
}
function showSettingsCategory(key='appearance',opts={}){
 if(!settingsCategoryMeta[key])key='appearance';
 if(key==='tags'){renderSettingsTags();loadSettingsTags()};const map=settingsCategoryNodes();Object.entries(map).forEach(([k,nodes])=>(nodes||[]).filter(Boolean).forEach(n=>n.classList.toggle('settings-category-hidden',k!==key)));
 document.querySelectorAll('[data-settings-category]').forEach(b=>{const selected=b.dataset.settingsCategory===key;b.classList.toggle('active',selected);b.setAttribute('role','tab');b.setAttribute('aria-selected',String(selected))});
 const hint=document.querySelector('.settings-category-state span:last-child');if(hint)hint.textContent=key==='tags'?'Etiket Ekle / Güncelle ve Sil işlemleri anında kaydedilir.':'Değişiklikler Ayarları Kaydet ile kalıcı olur.';
 const meta=settingsCategoryMeta[key],t=document.getElementById('settingsCategoryTitle'),d=document.getElementById('settingsCategoryDesc');if(t)t.textContent=meta[0];if(d)d.textContent=meta[1];
 try{sessionStorage.setItem('asay_settings_category',key)}catch(_e){}
 if(!opts.silent)document.getElementById('settingsCategoryIntro')?.scrollIntoView({behavior:'smooth',block:'start'});
 return key
}
function initSettingsWorkspace(){let key='appearance';try{key=sessionStorage.getItem('asay_settings_category')||'appearance'}catch(_e){};showSettingsCategory(key,{silent:true})}
function scrollSettingsTo(id){const category={settingsTags:'tags',settingsAppearance:'appearance',settingsActions:'actions',settingsStatusColors:'colors',settingsCompany:'company',settingsAccess:'access',settingsSystemInfo:'system',settingsDatabase:'system'}[id]||'appearance';showSettingsCategory(category,{silent:true});setTimeout(()=>document.getElementById(id)?.scrollIntoView({behavior:'smooth',block:'start'}),20)}
function syncSettingsLabels(){const r=document.getElementById('uiRadius'),f=document.getElementById('uiFontScale'),rh=document.getElementById('radiusValueLabel'),fh=document.getElementById('fontScaleValueLabel'),ac=document.getElementById('appAccent'),ah=document.getElementById('accentHexLabel'),mt=document.getElementById('uiMenuText'),mh=document.getElementById('menuTextHexLabel'),mi=document.getElementById('uiMenuIcon'),mih=document.getElementById('menuIconHexLabel');if(r&&rh)rh.textContent=r.value+' px';if(f&&fh)fh.textContent=f.value+'%';if(ac&&ah)ah.textContent=ac.value.toUpperCase();if(mt&&mh)mh.textContent=mt.value.toUpperCase();if(mi&&mih)mih.textContent=mi.value.toUpperCase()}
function resetThemeTuning(){const vals={uiDensity:'comfortable',uiSidebar:'standard',uiCardStyle:'soft',uiRadius:'14',uiFontScale:'100'};Object.entries(vals).forEach(([id,v])=>{const e=document.getElementById(id);if(e)e.value=v});const a=document.getElementById('appAccent');if(a){a.value='#246bfd';setAccent(a.value)}const native=getComputedStyle(document.documentElement).getPropertyValue('--sidebar-text').trim()||'#c9d8ed',mt=document.getElementById('uiMenuText'),mi=document.getElementById('uiMenuIcon');if(mt)mt.value=native;if(mi)mi.value=native;applyUiSettings();syncSettingsLabels();toast('Görünüm ince ayarları varsayılana döndürüldü. Kaydet ile kalıcı hale getirin.','good')}
function resetActionSettingsToDefault(){state.company.actionSettings=defaultActionSettings();loadActionSettings();toast('Aksiyon ayarları varsayılan değerlere döndürüldü. Kaydet ile kalıcı hale getirin.','good')}
function statusColorHexId(k){return 'statusColor'+k[0].toUpperCase()+k.slice(1)+'Hex'}
function previewStatusColor(k,v){document.documentElement.style.setProperty('--status-'+k,v);const h=document.getElementById(statusColorHexId(k));if(h)h.textContent=String(v||'').toUpperCase()}
function resetStatusColorsToDefault(){const d=defaultStatusColors();Object.entries(d).forEach(([k,v])=>{const e=document.getElementById('statusColor'+k[0].toUpperCase()+k.slice(1));if(e)e.value=v;previewStatusColor(k,v)});toast('Durum ve kıyaslama renkleri varsayılana döndürüldü. Kaydet ile kalıcı hale getirin.','good')}
/* Admin/settings management functions moved to admin.js in V3.7.1 */
function applyUiSettings(){const density=document.getElementById('uiDensity')?.value||state.ui.density,sidebar=document.getElementById('uiSidebar')?.value||state.ui.sidebar,cardStyle=document.getElementById('uiCardStyle')?.value||state.ui.cardStyle,radius=+(document.getElementById('uiRadius')?.value||state.ui.radius),fontScale=+(document.getElementById('uiFontScale')?.value||state.ui.fontScale),menuText=document.getElementById('uiMenuText')?.value||state.ui.menuText||'#c9d8ed',menuIcon=document.getElementById('uiMenuIcon')?.value||state.ui.menuIcon||menuText;state.ui={...state.ui,density,sidebar,cardStyle,radius,fontScale,menuText,menuIcon};document.documentElement.dataset.density=density;document.documentElement.style.setProperty('--radius',radius+'px');document.documentElement.style.setProperty('--sidebar-menu-text',menuText);document.documentElement.style.setProperty('--sidebar-menu-icon',menuIcon);document.body.style.fontSize=(14*fontScale/100).toFixed(2)+'px';document.body.classList.toggle('ui-compact-sidebar',sidebar==='compact');document.body.classList.toggle('ui-flat',cardStyle==='flat');document.body.classList.toggle('ui-elevated',cardStyle==='elevated');syncSettingsLabels()}
function loadUiSettings(){const u=state.ui;[['uiDensity',u.density],['uiSidebar',u.sidebar],['uiCardStyle',u.cardStyle],['uiRadius',u.radius],['uiFontScale',u.fontScale],['uiMenuText',u.menuText||'#c9d8ed'],['uiMenuIcon',u.menuIcon||u.menuText||'#c9d8ed']].forEach(([id,v])=>{const e=document.getElementById(id);if(e&&v)e.value=v});const a=document.getElementById('appAccent');if(a&&u.accent)a.value=u.accent;document.querySelectorAll('[data-theme-pick]').forEach(x=>x.classList.toggle('active',x.dataset.themePick===(document.documentElement.dataset.theme||u.theme||'light')));applyUiSettings();syncSettingsLabels()}
function saveUiSettings(){state.ui.theme=document.documentElement.dataset.theme||state.ui.theme;state.ui.accent=getComputedStyle(document.documentElement).getPropertyValue('--accent').trim()||state.ui.accent;try{localStorage.setItem('asay_ui_settings',JSON.stringify(state.ui))}catch(_e){}}
function restoreUiSettings(){try{const u=JSON.parse(localStorage.getItem('asay_ui_settings')||'null');if(u)state.ui={...state.ui,...u}}catch(_e){}if(state.ui.theme)document.documentElement.dataset.theme=state.ui.theme;if(state.ui.accent)setAccent(state.ui.accent);if(state.ui.menuText)document.documentElement.style.setProperty('--sidebar-menu-text',state.ui.menuText);if(state.ui.menuIcon)document.documentElement.style.setProperty('--sidebar-menu-icon',state.ui.menuIcon)}
function selectDocRequest(rid){const e=document.getElementById('docRequestSelect');if(e){e.value=rid;syncDocQuoteSelect();renderDocument()}}
function updateFxUi(){document.getElementById('fxUsdTry').textContent=state.fx.usdTry.toLocaleString('tr-TR',{minimumFractionDigits:4,maximumFractionDigits:4});document.getElementById('fxEurTry').textContent=state.fx.eurTry.toLocaleString('tr-TR',{minimumFractionDigits:4,maximumFractionDigits:4});document.getElementById('fxEurUsd').textContent=state.fx.eurUsd.toLocaleString('tr-TR',{minimumFractionDigits:4,maximumFractionDigits:4});const st=document.getElementById('fxStatus');if(st)st.textContent=`Kur: ${state.fx.source} · ${state.fx.updated}`}
async function refreshTcmbFx(manual=false){const btn=document.getElementById('fxRefreshBtn'),st=document.getElementById('fxStatus');if(btn){btn.disabled=true;btn.textContent='TCMB…'}try{const res=await fetch('tcmb_proxy.php?ts='+Date.now(),{cache:'no-store',headers:{'Accept':'application/json'}});if(!res.ok)throw new Error('Proxy HTTP '+res.status);const p=await res.json();if(!p?.ok||!p?.usd?.selling||!p?.eur?.selling)throw new Error(p?.error||'TCMB verisi eksik');const usd=Number(p.usd.selling),eur=Number(p.eur.selling),nowIso=new Date().toISOString();state.fx={usdTry:usd,eurTry:eur,eurUsd:eur/usd,source:'TCMB resmi gösterge kuru'+(p.date?' · '+p.date:'')+(p.stale?' · son başarılı sunucu kaydı':p.cached?' · sunucu önbelleği':''),updated:new Date().toLocaleString('tr-TR'),capturedAt:nowIso,rateDate:p.date||null,fetchedAt:p.fetched_at||nowIso};try{localStorage.setItem('asay_tcmb_fx',JSON.stringify(state.fx))}catch(_e){}updateFxUi();if(st)st.innerHTML=`<span class="fx-live-dot"></span>Kur: ${state.fx.source} · ${state.fx.updated}`;renderRequests();let saved=true;if(window.saveDbState)saved=await saveDbState(true);if(manual)toast(saved?'TCMB kurları güncellendi ve MySQL’e kaydedildi.':'TCMB kuru alındı ancak MySQL’e kaydedilemedi. Sayfayı yenilemeden tekrar deneyin.',saved?'good':'warn');return saved}catch(e){const restored=restoreFreshFxCache();updateFxUi();const localFile=location.protocol==='file:';if(st)st.innerHTML=`<span class="fx-live-dot off"></span>${restored?'Son başarılı TCMB kaydı kullanılıyor':localFile?'Canlı TCMB için XAMPP / PHP üzerinden açın':'TCMB proxy bağlantısı kurulamadı'} · ${e.message}`;if(manual)toast(restored?'TCMB erişilemedi; son başarılı kur kullanılmaya devam ediyor.':localFile?'HTML dosyasını tcmb_proxy.php ile aynı klasörde XAMPP üzerinden açın.':'TCMB proxy servisine erişilemedi.',restored?'good':'warn');return restored}finally{if(btn){btn.disabled=false;btn.textContent='TCMB ↻'}}}
document.querySelectorAll('[data-theme-pick]').forEach(x=>x.onclick=()=>{document.documentElement.dataset.theme=x.dataset.themePick;state.ui.theme=x.dataset.themePick;document.querySelectorAll('[data-theme-pick]').forEach(y=>y.classList.toggle('active',y===x));requestAnimationFrame(()=>{const c=getComputedStyle(document.documentElement).getPropertyValue('--accent').trim(),a=document.getElementById('appAccent');if(a&&c){a.value=c;state.ui.accent=c}syncSettingsLabels()});toast(x.querySelector('b').textContent+' tema uygulandı. PDF görünümü değişmedi.')});
document.querySelectorAll('[data-density]').forEach(x=>x.onclick=()=>{document.documentElement.dataset.density=x.dataset.density;document.querySelectorAll('[data-density]').forEach(y=>y.classList.toggle('active',y===x))});
/* Application boot moved to boot.js in V3.7.1 */

// Catalog changes are saved separately so theme edits remain independent.
let settingsTagUsage={},settingsTagsLoading=false,settingsTagsError='';
function tagUsage(name){return settingsTagUsage[name]??((state.requests||[]).filter(r=>entityTagNames(r.tags).includes(name)).length+groupedAccounts().filter(g=>entityTagNames(g.profile.tags).includes(name)).length)}
async function loadSettingsTags(){if(settingsTagsLoading)return;settingsTagsLoading=true;settingsTagsError='';try{const res=await fetch('api/tags.php',{headers:{Accept:'application/json'},cache:'no-store'}),j=await res.json();if(!res.ok||!j.ok)throw new Error(j.error||'Etiket listesi yüklenemedi.');state.tagCatalog=j.catalog;settingsTagUsage=j.usage||{};}catch(e){settingsTagsError=e.message}finally{settingsTagsLoading=false;renderSettingsTags()}}
function renderSettingsTags(){
 const box=document.getElementById('settingsTagList');if(!box)return;const term=(document.getElementById('settingsTagSearch')?.value||'').toLocaleLowerCase('tr-TR');box.replaceChildren();
 normalizeTagCatalog().forEach(t=>{if(![t.name,t.category].join(' ').toLocaleLowerCase('tr-TR').includes(term))return;const row=document.createElement('div');row.className='settings-tag-row';const chip=document.createElement('span');chip.className='tag';chip.textContent=t.name;chip.style.color=t.color;chip.style.borderColor=t.color;row.append(chip);const cat=document.createElement('span');cat.textContent=t.category;row.append(cat);const count=document.createElement('small');count.textContent=tagUsage(t.name)+' kayıt';row.append(count);
  const edit=document.createElement('button');edit.className='btn sm';edit.textContent='Düzenle';edit.onclick=()=>editCatalogTag(t.name);row.append(edit);const del=document.createElement('button');del.className='btn red sm';del.textContent='Sil';del.onclick=()=>deleteCatalogTag(t.name);row.append(del);box.append(row);
 });if(settingsTagsError||!box.childElementCount){const e=document.createElement('div');e.className='empty';e.textContent=settingsTagsError||(settingsTagsLoading?'Etiketler yükleniyor…':'Etiket bulunamadı.');box.append(e)}
}
let editingCatalogTagName=null;
function editCatalogTag(ref){const catalog=normalizeTagCatalog(),t=typeof ref==='number'?catalog[ref]:catalog.find(t=>t.name===ref);if(!t)return;editingCatalogTagName=t.name;document.getElementById('catalogTagName').value=t.name;document.getElementById('catalogTagCategory').value=t.category;document.getElementById('catalogTagColor').value=t.color;document.getElementById('catalogTagSave').textContent='Etiketi Güncelle';document.getElementById('catalogTagName').focus()}
function resetCatalogTagForm(){editingCatalogTagName=null;document.getElementById('catalogTagName').value='';document.getElementById('catalogTagCategory').value='';document.getElementById('catalogTagColor').value='#246bfd';document.getElementById('catalogTagSave').textContent='Etiket Ekle'}
async function runCatalogOperation(payload){const execute=async()=>{try{const res=await fetch('api/tags.php',{method:'POST',headers:{'Content-Type':'application/json',Accept:'application/json'},body:JSON.stringify(payload)}),j=await res.json();if(!res.ok||!j.ok)throw new Error(j.error||'Etiket işlemi başarısız.');acceptServerState(j.state,j.revision,j.keyHashes);settingsTagUsage=j.usage||{};rerenderAll();renderSettingsTags();resetCatalogTagForm();return true}catch(e){toast(e.message,'warn');return false}};return await runAuthoritativeDbMutation(execute)}
async function saveCatalogTag(){const name=document.getElementById('catalogTagName').value.trim(),category=document.getElementById('catalogTagCategory').value.trim()||'Genel',color=document.getElementById('catalogTagColor').value;if(!name)return toast('Etiket adı gerekli.','warn');const btn=document.getElementById('catalogTagSave');btn.disabled=true;try{if(await runCatalogOperation({action:'upsert',oldName:editingCatalogTagName||'',name,category,color}))toast('Etiket ve bağlı kayıtlar güncellendi.','good')}finally{btn.disabled=false}}
async function deleteCatalogTag(ref){const catalog=normalizeTagCatalog(),t=typeof ref==='number'?catalog[ref]:catalog.find(t=>t.name===ref);if(!t)return;const usage=tagUsage(t.name);if(!await appConfirm(t.name+' etiketi silinsin mi?'+(usage?' '+usage+' kayıttaki etiket bağlantısı da kaldırılacak; kayıtlar korunacak.':''),'Etiket Sil'))return;if(await runCatalogOperation({action:'delete',oldName:t.name}))toast('Etiket ve kayıt bağlantıları silindi.','good')}
function updatePackingNumber(i,key,value){if(!packingRows[i]||!['qty','net','gross'].includes(key))return;packingRows[i][key]=Math.max(0,parseMoneyInput(value));const row=document.querySelector(`[data-pack-index="${i}"]`);for(const kind of ['net','gross']){const total=row?.querySelector(`[data-pack-total="${kind}"]`);if(total)total.textContent=formatQuantity(packLineTotal(packingRows[i],kind))+' kg'}renderDocument({refreshEditors:false})}

async function deletePdfColumn(key){const type=document.getElementById('docType')?.value||'offer',column=ensurePdfColumns(type).find(c=>c.key===key);if(!column?.custom)return;if(!await appConfirm(column.label+' sütunu silinsin mi?','PDF Sütunu Sil'))return;state.pdfColumnsByType[type]=ensurePdfColumns(type).filter(c=>c.key!==key);for(const row of Object.values(ensurePdfRowCustom(type)))delete row[key];renderDocument();if(!await saveDbState(true))return toast('Sütun silinemedi.','warn');toast('Özel sütun silindi.','good')}
