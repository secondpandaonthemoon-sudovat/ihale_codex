(()=>{
 'use strict';
 const nativeFetch=window.fetch.bind(window);
 const csrf=()=>String(window.ASAY_CSRF||window.DB_ADMIN_CSRF||'');
 window.fetch=function(input,init={}){
   const method=String(init.method||'GET').toUpperCase();
   let sameOrigin=true;
   try{const u=new URL(typeof input==='string'?input:input.url,location.href);sameOrigin=u.origin===location.origin}catch(_e){}
   if(sameOrigin&&['POST','PUT','PATCH','DELETE'].includes(method)){
     const h=new Headers(init.headers||{});if(!h.has('X-ASAY-CSRF'))h.set('X-ASAY-CSRF',csrf());init={...init,headers:h};
   }
   return nativeFetch(input,init);
 };
 function ensureDialog(){
   let host=document.getElementById('appSystemDialog');if(host){if(host.parentElement!==document.body)document.body.appendChild(host);return host;}
   host=document.createElement('div');host.id='appSystemDialog';host.className='app-dialog-backdrop';host.hidden=true;
   host.innerHTML='<div class="app-dialog" role="dialog" aria-modal="true" aria-labelledby="appDialogTitle"><h3 id="appDialogTitle"></h3><p id="appDialogMessage"></p><input id="appDialogInput" class="app-dialog-input" hidden><div class="app-dialog-actions"><button type="button" class="btn" id="appDialogCancel">İptal</button><button type="button" class="btn primary" id="appDialogOk">Onayla</button></div></div>';
   document.body.appendChild(host);return host;
 }
 function ask({title='Onay',message='',value=null,ok='Onayla',cancel='İptal'}){
   const h=ensureDialog(),titleEl=h.querySelector('#appDialogTitle'),msg=h.querySelector('#appDialogMessage'),input=h.querySelector('#appDialogInput'),okBtn=h.querySelector('#appDialogOk'),cancelBtn=h.querySelector('#appDialogCancel');
   titleEl.textContent=title;msg.textContent=message;okBtn.textContent=ok;cancelBtn.textContent=cancel;input.hidden=value===null;input.value=value===null?'':String(value);h.hidden=false;document.body.classList.add('ui-system-dialog-open');
   return new Promise(resolve=>{const done=v=>{h.hidden=true;document.body.classList.remove('ui-system-dialog-open');okBtn.onclick=cancelBtn.onclick=null;document.removeEventListener('keydown',key);resolve(v)};const key=e=>{if(e.key==='Escape')done(null);if(e.key==='Enter'&&!e.shiftKey){e.preventDefault();done(input.hidden?true:input.value)}};okBtn.onclick=()=>done(input.hidden?true:input.value);cancelBtn.onclick=()=>done(input.hidden?false:null);document.addEventListener('keydown',key);setTimeout(()=>input.hidden?okBtn.focus():input.focus(),0)});
 }
 window.appConfirm=async(message,title='Onay')=>Boolean(await ask({title,message,value:null,ok:'Onayla',cancel:'Vazgeç'}));
 window.appPrompt=async(message,value='',title='Bilgi Girişi')=>await ask({title,message,value,ok:'Kaydet',cancel:'Vazgeç'});

 function labelIconButtons(root=document){
   const map={'×':'Kapat','☰':'Menüyü aç','＋':'Ekle','+':'Ekle','↻':'Yenile'};
   root.querySelectorAll?.('button').forEach(b=>{if(b.hasAttribute('aria-label'))return;const txt=(b.textContent||'').trim(),title=b.getAttribute('title');if(title)b.setAttribute('aria-label',title);else if(map[txt])b.setAttribute('aria-label',map[txt]);});
 }
 document.addEventListener('DOMContentLoaded',()=>{labelIconButtons();new MutationObserver(ms=>ms.forEach(m=>m.addedNodes.forEach(n=>{if(n.nodeType===1)labelIconButtons(n)}))).observe(document.body,{childList:true,subtree:true});});
})();
