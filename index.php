<?php
require_once __DIR__ . '/app/bootstrap.php';
require_login();
$pageUser=current_user();
$isPageAdmin=($pageUser&&(user_is_admin($pageUser)||has_app_write_permission('settings',$pageUser)));
$dbAdminCsrf=csrf_token();
?>
<!doctype html>
<html lang="tr" data-theme="light" data-density="comfortable">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>ASAY İhale & Teklif OS — <?=htmlspecialchars(ASAY_APP_BUILD)?></title>
<link rel="stylesheet" href="assets/css/app.css?v=<?=rawurlencode(ASAY_APP_ASSET_VERSION)?>">
<link rel="stylesheet" href="assets/css/components.css?v=<?=rawurlencode(ASAY_APP_ASSET_VERSION)?>">
</head>
<body>
<div class="app">
  <aside class="sidebar" id="sidebar">
    <div class="brand"><div class="brandmark" id="appBrandmark">AS</div><div><b id="appBrandName">ASAY İhale & Teklif OS</b><small id="appBrandSubtitle">Connected Commerce Workspace</small></div></div>
    <nav class="nav" id="nav">
      <div class="nav-section">Operasyon</div>
      <button class="navbtn active" data-view="dashboard"><span class="ico"><svg><path d="M3 13h7V3H3zM14 21h7V11h-7zM3 21h7v-5H3zM14 8h7V3h-7z"/></svg></span>Dashboard</button>
      <button class="navbtn" data-view="requests"><span class="ico"><svg><path d="M4 4h16v16H4z"/><path d="M8 8h8M8 12h8M8 16h5"/></svg></span>Talep Merkezi</button><button class="navbtn" data-view="attachments"><span class="ico"><svg><path d="M8 12.5l5.5-5.5a3 3 0 1 1 4.2 4.2l-7 7a5 5 0 0 1-7.1-7.1l7-7"/></svg></span>Dokümanlar</button>
      <button class="navbtn" data-view="orders"><span class="ico"><svg><path d="M3 7h18v12H3z"/><path d="M7 7V4h10v3M7 12h10"/></svg></span>Sipariş & Operasyon</button>
      <div class="nav-section">Finans</div>
      <button class="navbtn" data-view="accounts"><span class="ico"><svg><circle cx="12" cy="8" r="4"/><path d="M4 21c1-5 4-7 8-7s7 2 8 7"/></svg></span>Cari Hesaplar</button>
      <button class="navbtn" data-view="cash"><span class="ico"><svg><path d="M3 6h18v12H3z"/><path d="M7 12h.01M17 12h.01"/><circle cx="12" cy="12" r="3"/></svg></span>Kasa & Banka</button>
      <button class="navbtn" data-view="guarantees"><span class="ico"><svg><path d="M12 3l7 3v5c0 5-3 8-7 10-4-2-7-5-7-10V6z"/><path d="M9 12l2 2 4-5"/></svg></span>Teminat Takip</button>
      <button class="navbtn" data-view="docs"><span class="ico"><svg><path d="M6 3h9l3 3v15H6z"/><path d="M15 3v4h4M9 11h6M9 15h6"/></svg></span>Ticari Evraklar</button>
      <div class="nav-section">Sistem</div>
      <button class="navbtn" data-view="settings"><span class="ico"><svg><circle cx="12" cy="12" r="3"/><path d="M19 12a7 7 0 0 0-.1-1l2-1-2-4-2 1a7 7 0 0 0-2-1l-.3-2h-5L9 6a7 7 0 0 0-2 1L5 6 3 10l2 1a7 7 0 0 0 0 2l-2 1 2 4 2-1a7 7 0 0 0 2 1l.4 2h5l.4-2a7 7 0 0 0 2-1l2 1 2-4-2-1a7 7 0 0 0 .2-1z"/></svg></span>Ayarlar</button>
    </nav>
    <div class="sidebar-foot"><div class="userbox"><div class="avatar" id="sidebarUserAvatar">EA</div><div><b id="sidebarUserName">Admin Kullanıcı</b><small id="sidebarUserMeta">Yönetici · Tam Yetki</small></div></div></div>
  </aside>
  <div class="scrim" id="scrim"></div>
  <main class="main">
    <header class="topbar">
      <button class="top-action mobile-toggle" id="mobileToggle">☰</button>
      <div class="top-title"><b id="pageTitle">Dashboard</b><small id="pageSubtitle">Bugünün operasyon ve finans görünümü</small><small id="fxStatus" style="display:block;margin-top:1px">Kur: TCMB bağlantısı hazırlanıyor…</small></div>
      <div class="spacer"></div>
      <div class="rate-strip">
        <div class="rate"><span>USD / TRY</span><b id="fxUsdTry">41,2600</b></div>
        <div class="rate"><span>EUR / TRY</span><b id="fxEurTry">48,4100</b></div>
        <div class="rate"><span>EUR / USD</span><b id="fxEurUsd">1,1730</b></div>
        <button class="top-action" id="fxRefreshBtn" onclick="refreshTcmbFx(true)">TCMB ↻</button>
      </div>
      <button class="top-action" onclick="openQuick()">＋ Hızlı İşlem</button>
    </header>
    <div class="content">

      <section class="view active" id="view-dashboard">
        <div class="page-head"><div><span class="eyebrow">YÖNETİM PANELİ</span><h1>Operasyon Merkezi</h1><p>Teklif, sipariş, finans ve üretim akışını tek ekrandan yönetin.</p></div><div class="actions"><button class="btn" onclick="go('requests')">Talep Merkezi</button><button class="btn primary" onclick="openRequestModal()">+ Yeni Talep</button></div></div>
        <div class="grid6">
          <div class="panel metric"><div class="label">Aktif Talep</div><div class="value" id="mRequests">0</div><div class="delta" id="mRequestsCollecting">0 tanesi fiyat topluyor</div></div>
          <div class="panel metric"><div class="label">Açık Müşteri Teklifi</div><div class="value" id="mOpenQuotes">0</div><div class="delta" id="mOpenQuoteVolume">Açık teklif hacmi yok</div></div>
          <div class="panel metric"><div class="label">Kazanılan</div><div class="value" id="mWon">0</div><div class="delta" id="mWonSub">Siparişe dönüşen iş yok</div></div>
          <div class="panel metric"><div class="label">Aktif Üretim</div><div class="value" id="mActiveOps">0</div><div class="delta" id="mActiveOpsSub">Sevkiyata hazır sipariş yok</div></div>
          <div class="panel metric"><div class="label">Müşteriden Alacak</div><div class="value" id="dashReceivable">€ 0</div><div class="delta" id="dashReceivableSub">Talep bazlı açık bakiye</div></div>
          <div class="panel metric"><div class="label">Tedarikçiye Borç</div><div class="value" id="dashPayable">€ 0</div><div class="delta" id="dashPayableSub">Açık tedarikçi bakiyesi</div></div>
        </div>
        <div class="dashboard-trade-grid">
          <div class="trade-metric"><span>Toplam Satış · EUR</span><b id="dashSalesEur">€ 0</b><small>Müşteri teklif/sipariş toplamı</small></div>
          <div class="trade-metric"><span>Toplam Alış · EUR</span><b id="dashPurchaseEur">€ 0</b><small>Seçili tedarikçi maliyeti</small></div>
          <div class="trade-metric"><span>Toplam Satış · USD</span><b id="dashSalesUsd">$ 0</b><small>Müşteri teklif/sipariş toplamı</small></div>
          <div class="trade-metric"><span>Toplam Alış · USD</span><b id="dashPurchaseUsd">$ 0</b><small>Seçili tedarikçi maliyeti</small></div>
        </div>
        <div class="grid2" style="margin-top:12px">
          <div class="panel">
            <div class="panel-head"><div><h3>Bugün Aksiyon Gerekenler</h3><div class="sub">Gerçek kayda bağlı otomatik aksiyonlar · manuel Tamamlandı yok</div></div><div class="actions"><span class="badge b-red" id="dashboardActionCount">0 aksiyon</span><button class="btn sm" onclick="openAllActions()">Tüm Aksiyonlar</button></div></div>
            <div class="deadline-list" id="dashboardActions"></div>
          </div>
          <div class="panel">
            <div class="panel-head"><div><h3>Finans & Nakit Görünümü</h3><div class="sub">Kasa, alacak, borç ve yaklaşan finans yükü</div></div><button class="btn sm" onclick="go('cash')">Finansa Git</button></div>
            <div class="dashboard-finance">
              <div class="quote-kpi"><span>EUR Kasa / Banka</span><b id="dashCashEur">€ 0</b><small class="sub">Tüm EUR hesapları</small></div>
              <div class="quote-kpi"><span>USD Kasa / Banka</span><b id="dashCashUsd">$ 0</b><small class="sub">Tüm USD hesapları</small></div>
              <div class="quote-kpi"><span>Açık Tedarikçi Borcu · EUR</span><b id="dashSupplierDueEur">€ 0</b><small class="sub" id="dashSupplierDueCount">0 açık sipariş</small></div>
              <div class="quote-kpi"><span>Açık Müşteri Alacağı · EUR</span><b id="dashCustomerDueEur">€ 0</b><small class="sub" id="dashCustomerDueCount">0 açık cari</small></div>
            </div>
            <div class="inline-note" style="margin-top:10px">Dashboard süreç diyagramı kaldırıldı; günlük karar vermeyi hızlandıran finans özeti gösteriliyor.</div>
          </div>
        </div>
        <div class="panel dashboard-guarantee-panel" style="margin-top:12px">
          <div class="panel-head"><div><h3>Teminat Takip</h3><div class="sub">Aktif teminatlar, yaklaşan vadeler ve finansal risk özeti</div></div><button class="btn sm" onclick="go('guarantees')">Teminat Takip’e Git</button></div>
          <div class="dashboard-guarantee-metrics">
            <div class="quote-kpi"><span>Aktif Teminat</span><b id="dashGuaranteeActive">0</b><small class="sub">Açık yükümlülük</small></div>
            <div class="quote-kpi"><span>EUR Karşılığı</span><b id="dashGuaranteeTotal">€ 0</b><small class="sub">Aktif teminat toplamı</small></div>
            <div class="quote-kpi"><span>30 Gün İçinde</span><b id="dashGuaranteeSoon">0</b><small class="sub">Vadesi yaklaşan</small></div>
            <div class="quote-kpi"><span>Süresi Geçen</span><b id="dashGuaranteeExpired">0</b><small class="sub">Aksiyon gerekli</small></div>
          </div>
          <div class="dashboard-guarantee-list" id="dashboardGuaranteeList"><div class="empty">Aktif teminat bulunmuyor.</div></div>
        </div>
        <div class="panel" style="margin-top:12px">
          <div class="panel-head"><div><h3>Aktif Üretim & Teslimat</h3><div class="sub">Yalnız kazanılmış, geçerli talebe bağlı ve teslimatı tamamlanmamış siparişler</div></div><button class="btn sm" onclick="go('orders')">Tümünü Gör</button></div>
          <div class="table-wrap"><table class="table responsive"><thead><tr><th>Sipariş</th><th>Tedarikçi</th><th>Müşteri</th><th>Durum</th><th>Üretim</th><th>Termin</th><th>Tutar</th></tr></thead><tbody id="dashboardOrderRows"><tr><td colspan="7" class="empty">Aktif sipariş bulunmuyor.</td></tr></tbody></table></div>
        </div>
      </section>

      <section class="view" id="view-requests">
        <div class="page-head"><div><span class="eyebrow">TALEP MERKEZİ</span><h1>Tüm İhale & Müşteri Talepleri</h1><p>Talep kalemleri, tedarikçi teklifleri, kıyaslama, sipariş ve evrakları tek kayıt altında yönetin.</p></div><div class="request-filterbar"><input class="search" id="requestSearch" placeholder="Talep / müşteri / ülke / etiket ara…" oninput="renderRequests()"><select id="requestCompletionFilter" onchange="renderRequests()"><option value="active">Aktif / Tamamlanmamış</option><option value="all">Tümü</option><option value="docsComplete">4 Evrakı Tamamlananlar</option><option value="delivered">Teslim Edilenler</option><option value="cancelled">İptal Edilenler</option><option value="lost">Kaybedilenler</option></select><select id="requestTagCategoryFilter" onchange="syncTagFilterOptions('request');renderRequests()"><option value="">Tüm Etiket Kategorileri</option></select><select id="requestTagFilter" onchange="renderRequests()"><option value="">Tüm Etiketler</option></select><button class="btn" onclick="openGeneralReport('requests')">Genel Ekstre</button><button class="btn" onclick="openExcelImport('requests')">Excel’den Tek Talep Yükle</button><button class="btn primary" onclick="openRequestModal()">+ Yeni Talep / İhale</button></div></div>
        <div class="grid5" style="margin-bottom:12px">
          <div class="panel metric"><div class="label">Toplam Aktif</div><div class="value" id="reqMetricActive">0</div></div>
          <div class="panel metric"><div class="label">Fiyat Toplanıyor</div><div class="value" id="reqMetricCollecting">0</div></div>
          <div class="panel metric"><div class="label">Kıyaslamaya Hazır</div><div class="value" id="reqMetricReady">0</div></div>
          <div class="panel metric"><div class="label">Teklif Gönderildi</div><div class="value" id="reqMetricSent">0</div></div>
          <div class="panel metric"><div class="label">Kazanıldı</div><div class="value" id="reqMetricWon">0</div></div>
        </div>
        <details class="panel" style="margin-bottom:10px"><summary style="cursor:pointer;font-weight:850">Durum Renkleri Açıklaması</summary><div class="tags" style="margin-top:9px;gap:7px"><span class="tag" style="background:#f8fafc;color:#475569;border-color:#94a3b8">Taslak</span><span class="tag" style="background:#eff6ff;color:#1d4ed8;border-color:#93c5fd">Fiyat Toplanıyor</span><span class="tag" style="background:#ecfeff;color:#0e7490;border-color:#67e8f9">Kıyaslamaya Hazır</span><span class="tag" style="background:#eef2ff;color:#4338ca;border-color:#a5b4fc">Müşteri Teklifi Hazır</span><span class="tag" style="background:#f5f3ff;color:#7c3aed;border-color:#c4b5fd">Teklif Gönderildi</span><span class="tag" style="background:#f0fdf4;color:#15803d;border-color:#86efac">Kazanıldı</span><span class="tag" style="background:#f8fafc;color:#475569;border-color:#cbd5e1">Kaybedildi</span><span class="tag" style="background:#fef2f2;color:#b91c1c;border-color:#fca5a5">İptal Edildi</span><span class="tag" style="background:#fff7ed;color:#c2410c;border-color:#fdba74">Kazanıldı · Evrak Eksik</span></div><div class="sub" style="margin-top:8px">Kart zemini işlem durumunu, sol renk şeridi son tarih kritikliğini gösterir.</div></details>
        <div class="request-list" id="requestList"></div>
      </section>

      <section class="view" id="view-attachments">
        <div class="page-head"><div><span class="eyebrow">TALEP DOKÜMANLARI</span><h1>Dokümanlar</h1><p>İhale dokümanı, şartname, teknik çizim, müşteri dosyası ve benzeri talep evraklarını yönetin.</p></div><div class="actions"><select id="attachmentRequestFilter" onchange="renderAttachmentLibrary()"></select><button class="btn" id="attachmentTrashToggle" onclick="toggleAttachmentTrash()">Çöp Kutusu</button><button class="btn primary" onclick="openAttachmentUpload()">+ Doküman Ekle</button></div></div>
        <div class="panel"><div class="panel-head"><div><h3>Talep Doküman Kütüphanesi</h3><div class="sub">Yüklenen dosyalar ilgili Talep No ile saklanır.</div></div><span class="badge b-blue" id="attachmentCount">0 dosya</span></div><div class="doc-library-list" id="attachmentLibrary"></div></div>
      </section>

      <section class="view" id="view-orders">
        <div class="page-head"><div><span class="eyebrow">OPERASYON</span><h1>Sipariş & Operasyon</h1><p>Kazanılan müşteri teklifi ve seçilmiş tedarikçi satın alma siparişleri.</p></div><div class="actions"><button class="btn" onclick="go('accounts')">Cari Hesaplar</button><button class="btn" onclick="go('cash')">Kasa & Banka</button></div></div>
        <div class="account-filterbar" style="margin-bottom:12px"><button class="btn sm active" id="orderFilterActive" onclick="setOrderViewFilter('active')">Aktif</button><button class="btn sm" id="orderFilterCompleted" onclick="setOrderViewFilter('completed')">Tamamlanan</button><button class="btn sm" id="orderFilterClosed" onclick="setOrderViewFilter('closed')">Kapalı / İptal</button><button class="btn sm" id="orderFilterAll" onclick="setOrderViewFilter('all')">Tümü</button></div>
        <div class="request-list" id="orderList"></div>
      </section>

      <section class="view" id="view-accounts">
        <div class="page-head"><div><span class="eyebrow">FİNANS</span><h1>Müşteri & Tedarikçi Cari</h1><p>Tek cari kartta EUR / USD / TRY bakiyelerini ayrı izleyin; seçilen döviz için profesyonel ekstre alın.</p></div><div class="actions"><button class="btn" onclick="openGeneralReport('accounts')">Tüm Cariler Ekstresi</button><button class="btn" onclick="openExcelImport('accounts')">Excel’den Cari Kart Yükle</button><button class="btn" onclick="go('cash')">Kasa & Banka</button><button class="btn primary" onclick="openAccountCreateModal()">+ Cari</button></div></div>
        <div class="account-searchbar"><span class="account-search-icon">⌕</span><input id="accountNameSearch" type="search" autocomplete="off" placeholder="Cari adı, yetkili veya vergi no ile ara…" oninput="renderAccounts()"><span class="account-search-count" id="accountSearchCount"></span><button class="btn sm" type="button" onclick="clearAccountSearch()">Temizle</button></div>
        <div class="account-filterbar">
          <button class="btn sm active" id="accFilterAll" onclick="setAccountFilter('all')">Tümü</button>
          <button class="btn sm" id="accFilterCustomer" onclick="setAccountFilter('customer')">Müşteriler</button>
          <button class="btn sm" id="accFilterSupplier" onclick="setAccountFilter('supplier')">Tedarikçiler</button>
          <button class="btn sm" id="accFilterPublic" onclick="setAccountFilter('public')">Resmi Kurum</button>
          <button class="btn sm" id="accFilterPrivate" onclick="setAccountFilter('private')">Özel Kurum</button>
        </div>
        <div class="tag-filterbar"><select id="accountTagCategoryFilter" onchange="syncTagFilterOptions('account');renderAccounts()"><option value="">Tüm Etiket Kategorileri</option></select><select id="accountTagFilter" onchange="renderAccounts()"><option value="">Tüm Etiketler</option></select><button class="btn sm" onclick="clearAccountTagFilter()">Etiket Filtresini Temizle</button></div>
        <div class="account-balance-filterbar"><button class="btn sm active" id="accBalanceAll" onclick="setAccountBalanceFilter('all')">Tüm Bakiyeler</button><button class="btn sm" id="accBalanceDebtor" onclick="setAccountBalanceFilter('debtor')">Borçlular</button><button class="btn sm" id="accBalanceCreditor" onclick="setAccountBalanceFilter('creditor')">Alacaklılar</button></div>
        <div class="grid2 accounts-workspace">
          <div class="panel account-list-panel"><div class="panel-head"><div><h3>Cari Kartlar</h3><div class="sub">Kompakt liste · ayrıntı için karta tıklayın</div></div><span class="badge b-blue" id="accountListModeBadge">Özet</span></div><div id="accountCards" class="account-card-list"></div></div>
          <div class="panel account-detail-panel" id="accountDetail"></div>
        </div>
      </section>

      <section class="view" id="view-cash">
        <div class="page-head"><div><span class="eyebrow">FİNANS</span><h1>Kasa & Banka</h1><p>Her hareket Talep No, cari ve kaynak işlemle ilişkilendirilebilir.</p></div><div class="actions"><button class="btn" onclick="openGeneralReport('cash')">Kasa/Banka Ekstresi</button><button class="btn" onclick="openTransferModal()">⇄ Virman</button><button class="btn" onclick="openCashAccountModal()">+ Kasa / Banka</button><button class="btn primary" onclick="openCashModal()">+ Tahsilat / Ödeme</button></div></div>
        <div class="cash-account-grid" id="cashAccountCards"></div>
        <div class="panel" style="margin-top:12px"><div class="panel-head"><div><h3 id="cashMovementPanelTitle">Kasa / Banka Hesap Hareketleri</h3><div class="sub" id="cashMovementPanelSub">Yukarıdan bir kasa veya banka hesabı seçin.</div></div></div><div class="table-wrap"><table class="table responsive"><thead><tr><th>Tarih</th><th>Talep</th><th>Cari</th><th>Tutar</th><th>Açıklama</th><th></th></tr></thead><tbody id="cashRows"></tbody></table></div></div>
      </section>

      <section class="view" id="view-guarantees">
        <div class="page-head">
          <div><span class="eyebrow">FİNANS & RİSK</span><h1>Teminat Takip</h1><p>Nakit teminat, geçici/kesin teminat ve banka teminat mektuplarını vade ve banka bazında izleyin.</p></div>
          <div class="actions"><button class="btn" id="guaranteeReturnBtn" style="display:none" onclick="returnToGuaranteeRequest()">← Talebe Geri Dön</button><button class="btn" onclick="exportGuaranteesExcel()">Excel</button><button class="btn" onclick="printGuaranteesPdf()">PDF</button><button class="btn primary" onclick="openGuaranteeModal()">+ Yeni Teminat</button></div>
        </div>
        <div class="grid4 guarantee-metrics">
          <div class="panel metric"><div class="label">Aktif Teminat</div><div class="value" id="guaranteeMetricActive">0</div><div class="delta">Açık kayıt</div></div>
          <div class="panel metric"><div class="label">Toplam Teminat · EUR</div><div class="value" id="guaranteeMetricTotal">€ 0</div><div class="delta">Kur karşılığı</div></div>
          <div class="panel metric"><div class="label">30 Gün İçinde Bitecek</div><div class="value" id="guaranteeMetricSoon">0</div><div class="delta">Vade takibi</div></div>
          <div class="panel metric"><div class="label">Süresi Geçen</div><div class="value" id="guaranteeMetricExpired">0</div><div class="delta">Aksiyon gerekli</div></div>
        </div>
        <div class="panel" style="margin-top:12px">
          <div class="panel-head">
            <div><h3>Teminatlarım</h3><div class="sub">Müşteri/kurum, teminat türü, banka/kasa ve vade bazında takip</div></div>
            <div class="actions"><input class="search" id="guaranteeSearch" placeholder="Konu başlığı, müşteri, talep, referans ara…" oninput="renderGuarantees()"><select id="guaranteeStatusFilter" onchange="renderGuarantees()"><option value="active">Aktif</option><option value="all">Tümü</option><option value="soon">Yaklaşan Vade</option><option value="expired">Süresi Geçen</option><option value="released">Çözülen / İade</option></select></div>
          </div>
          <div class="table-wrap"><table class="table responsive guarantee-table"><thead><tr><th>Talep / Konu Başlığı / Kurum</th><th>Teminat Türü</th><th>Enstrüman</th><th>Satış Bazı</th><th>Oran</th><th>Teminat</th><th>Banka / Kasa</th><th>Yatırılma</th><th>Vade</th><th>Kalan</th><th>Durum</th><th>İşlem</th></tr></thead><tbody id="guaranteeRows"></tbody></table></div>
        </div>
      </section>

      <section class="view" id="view-docs">
        <div class="page-head"><div><span class="eyebrow">TİCARİ EVRAKLAR</span><h1>PDF & Evrak Stüdyosu</h1><p>Logo/kaşe yükleyin, A4 üzerinde taşıyın; Packing List fiyatlardan tamamen bağımsızdır.</p></div><div class="actions"><select class="top-action" id="docRequestSelect" onchange="syncDocQuoteSelect()"></select><select class="top-action" id="docQuoteSelect"></select><button class="btn green" id="backToRequestBtn" style="display:none" onclick="returnToSelectedRequest()">← Teklife Geri Dön</button><button class="btn orientation-btn active" id="portraitBtn" onclick="setOrientation('portrait')">A4 Dikey</button><button class="btn orientation-btn" id="landscapeBtn" onclick="setOrientation('landscape')">A4 Yatay</button><button class="btn" onclick="togglePdfFit()">Sayfaya Sığdır</button><button class="btn" onclick="printDocument()">Yazdır / PDF</button><button class="btn" onclick="resetDocument()">Sıfırla</button><button class="btn" onclick="startNewDocument()">+ Yeni Belge</button><button class="btn primary" onclick="saveCurrentDocument()">Belgeyi Kaydet</button></div></div>
        <div class="doc-studio-grid">
          <div class="panel">
            <div class="panel-head"><div><h3>Belge Ayarları</h3><div class="sub">Commercial Offer / Proforma / Packing / Commercial Invoice / Check List</div></div></div>
            <div class="form-grid">
              <div class="field col2"><label>Belge Tipi</label><select id="docType" onchange="changeDocumentType()"><option value="offer">Commercial Offer</option><option value="proforma">Proforma Invoice</option><option value="packing">Packing List</option><option value="invoice">Commercial Invoice</option><option value="checklist">Check List / Teklif Kıyaslama</option></select></div>
              <div class="field col2"><label>Dil</label><select><option>TR / EN Bilingual</option><option>English</option><option>Türkçe</option></select></div>
              <div class="field"><label>PDF Vurgu</label><input type="color" id="pdfAccent" value="#163d73" oninput="renderDocument()"></div>
              <div class="field"><label>GTİP / HS Code</label><select id="docShowHs" onchange="renderDocument()"><option value="1">Göster</option><option value="0">Gizle</option></select></div>
              <div class="field"><label>Menşe / Origin</label><select id="docShowOrigin" onchange="renderDocument()"><option value="1">Göster</option><option value="0">Gizle</option></select></div>
              <div class="field"><label>Banka Bilgisi / Bank Details</label><select id="docShowBank" onchange="renderDocument()"><option value="1">Göster</option><option value="0">Gizle</option></select></div>
            </div>
            <div class="doc-company-editor">
              <div class="section-title" style="margin:0 0 6px"><b>Logo Altı Şirket Bilgileri</b><button class="btn sm" type="button" onclick="loadCompanyInfoIntoDocument()">Ayarlar'dan Getir</button></div>
              <div class="sub">Belge bazında düzenleyin; her satırı ayrı ayrı gösterip gizleyebilirsiniz.</div>
              <div class="form-grid">
                <div class="field col2"><label>Firma Adı</label><input id="docCompanyName" oninput="renderDocument()"></div>
                <div class="field"><label>Firma Adı</label><select id="docShowCompanyName" onchange="renderDocument()"><option value="1">Göster</option><option value="0">Gizle</option></select></div>
                <div></div>
                <div class="field col2"><label>Adres</label><input id="docCompanyAddress" oninput="renderDocument()"></div>
                <div class="field"><label>Adres</label><select id="docShowCompanyAddress" onchange="renderDocument()"><option value="1">Göster</option><option value="0">Gizle</option></select></div>
                <div></div>
                <div class="field col2"><label>Telefon</label><input id="docCompanyPhone" oninput="renderDocument()"></div>
                <div class="field"><label>Telefon</label><select id="docShowCompanyPhone" onchange="renderDocument()"><option value="1">Göster</option><option value="0">Gizle</option></select></div>
                <div></div>
                <div class="field col2"><label>Web Sitesi</label><input id="docCompanyWeb" oninput="renderDocument()"></div>
                <div class="field"><label>Web Sitesi</label><select id="docShowCompanyWeb" onchange="renderDocument()"><option value="1">Göster</option><option value="0">Gizle</option></select></div>
                <div></div>
                <div class="field col2"><label>E-posta</label><input id="docCompanyEmail" oninput="renderDocument()"></div>
                <div class="field"><label>E-posta</label><select id="docShowCompanyEmail" onchange="renderDocument()"><option value="1">Göster</option><option value="0">Gizle</option></select></div>
                <div></div>
              </div>
            </div>
            <div class="pdf-layout-tools-grid">
              <div class="pdf-layout-tool-card">
                <div class="section-title"><b>PDF Tablo Sütunları</b><span class="badge b-blue">Ticari Evraklar</span></div>
                <div class="pdf-column-manager" id="pdfColumnManager"></div>
              </div>
              <div class="pdf-layout-tool-card">
                <div class="section-title"><b>PDF Blok Sırası</b><span class="badge b-blue">Sürükle-Bırak</span></div>
                <div class="pdf-column-manager" id="pdfBlockManager"></div>
                <div class="actions pdf-free-text-action"><button class="btn sm" type="button" onclick="addPdfFreeText()">+ Serbest Metin Kutusu</button></div>
              </div>
            </div>
            <div class="asset-controls">
              <div class="asset-box"><b>Logo</b><input type="file" accept="image/*" onchange="loadPdfAsset(event,'pdfLogo')"><div class="asset-actions"><label for="pdfLogoWidth" class="sub">Boyut</label><input id="pdfLogoWidth" type="range" min="20" max="500" value="72" oninput="resizePdfAsset('pdfLogo',this.value)"><output id="pdfLogoWidthValue">72 px</output><button class="btn sm red" onclick="removePdfAsset('pdfLogo')">Sil</button></div><div class="grid2 asset-position-fields"><div class="field"><label for="pdfLogoX">Yatay konum (px)</label><input id="pdfLogoX" type="number" min="0" step="1" onchange="movePdfAsset('pdfLogo','x',this.value)"></div><div class="field"><label for="pdfLogoY">Dikey konum (px)</label><input id="pdfLogoY" type="number" min="0" step="1" onchange="movePdfAsset('pdfLogo','y',this.value)"></div></div></div>
              <div class="asset-box"><b>Kaşe / İmza</b><input type="file" accept="image/*" onchange="loadPdfAsset(event,'pdfStamp')"><div class="asset-actions"><label for="pdfStampWidth" class="sub">Boyut</label><input id="pdfStampWidth" type="range" min="20" max="500" value="80" oninput="resizePdfAsset('pdfStamp',this.value)"><output id="pdfStampWidthValue">80 px</output><button class="btn sm red" onclick="removePdfAsset('pdfStamp')">Sil</button></div><div class="grid2 asset-position-fields"><div class="field"><label for="pdfStampX">Yatay konum (px)</label><input id="pdfStampX" type="number" min="0" step="1" onchange="movePdfAsset('pdfStamp','x',this.value)"></div><div class="field"><label for="pdfStampY">Dikey konum (px)</label><input id="pdfStampY" type="number" min="0" step="1" onchange="movePdfAsset('pdfStamp','y',this.value)"></div></div></div>
            </div>
            <div class="inline-note" style="margin-top:10px">Logo ve kaşeyi yükledikten sonra A4 üzerinde sürükleyerek konumlandırabilirsiniz.</div>

            <div id="documentItemControls" class="doc-item-controls">
              <div class="section-title"><div><b>Ürün Kalemleri · GTİP / HS Code</b><div class="sub">Seçili talebin ürün kalemlerinde GTİP / HS Code ve menşe bilgisini düzenleyin. Değerler talep kalemine kaydedilir ve Commercial Offer / Proforma / Commercial Invoice / Packing List belgelerine otomatik aktarılır.</div></div></div>
              <div id="documentItemEditor"></div>
            </div>

            <div id="packingControls" style="display:none">
              <div class="section-title"><div><b>Packing List Kalemleri</b><div class="sub">Talep kalemleri otomatik gelir. Birim net/brüt kg girildiğinde satır ve genel toplamlar otomatik hesaplanır.</div></div><div class="actions"><button class="btn sm" onclick="syncPackingRowsFromRequest(true)">↻ Talep Kalemlerini Yenile</button><button class="btn sm" onclick="addPackRow()">+ Paket Satırı</button></div></div>
              <div class="packing-editor" id="packingEditor"></div>
              <div class="pack-totals">
                <div class="pack-total"><span>Toplam Paket / Total Packages</span><b id="packCountTotal">0</b></div>
                <div class="pack-total"><span>Net Toplam / Total Net Weight</span><b id="packNetTotal">0 kg</b></div>
                <div class="pack-total"><span>Brüt Toplam / Total Gross Weight</span><b id="packGrossTotal">0 kg</b></div>
              </div>
            </div>
<div id="checklistControls" style="display:none">
              <div class="section-title"><b>Check List / Teklif Kıyaslama</b><span class="badge b-blue">PDF</span></div>
              <div class="field full"><label>Genel Açıklama</label><textarea id="checklistGeneralDescription" rows="2" oninput="renderDocument()" placeholder="Bu kıyaslama evrakına özel genel açıklama..."></textarea></div>
              <div class="sub" style="margin:7px 0">Satırlar ürün kıyaslamasındaki tedarikçi fiyatlarından otomatik gelir. Açıklama sütunu belgeye özel düzenlenebilir.</div>
              <div class="checklist-editor" id="checklistEditor"></div>
              
            </div>
            <div class="doc-completion-bar" id="docCompletionStatus">0/4 evrak tamamlandı.</div>
            <div class="section-title"><b>Kaydedilmiş Evraklar</b><span class="badge b-blue" id="savedDocCount">0</span></div>
            <div class="deadline-list" id="savedDocsList"><div class="empty">Henüz kayıtlı belge yok.</div></div>
          </div>
          <div class="panel soft doc-preview-panel"><div class="pdf-preview-head"><div><b>A4 Önizleme</b><small>Ekran önizlemesi küçültülmüştür; baskı/PDF ölçüsü değişmez.</small></div><span class="badge b-blue">Canlı</span></div><div class="pdf-stage" id="pdfStage"><div id="pdfViewport">
            <div class="pdf-demo portrait" id="pdfDemo">
              <img id="pdfLogo" class="pdf-asset" alt="" style="display:none">
              <img id="pdfStamp" class="pdf-asset" alt="" style="display:none">
              <div id="documentPreview"></div>
            </div></div></div>
          </div>
        </div>
      </section>
      <section class="view" id="view-settings">
        <div class="page-head settings-page-head"><div><span class="eyebrow">SİSTEM</span><h1>Ayarlar & Tasarım Sistemi</h1><p>Görünüm, aksiyon kuralları, durum renkleri, firma bilgileri ve sistem yetkilerini daha düzenli bir yapıdan yönetin.</p></div><div class="actions"><span class="settings-unsaved-hint">Değişikliklerden sonra kaydedin</span><button class="btn primary" onclick="saveSettings()">Ayarları Kaydet</button></div></div>
        <div class="settings-nav-shell" role="tablist" aria-label="Ayar kategorileri">
          <button class="settings-nav-card active" data-settings-category="appearance" onclick="showSettingsCategory('appearance')"><span class="settings-nav-icon">◐</span><div><b>Genel & Görünüm</b><small>Marka, tema ve numaralandırma</small></div></button>
          <button class="settings-nav-card" data-settings-category="tags" onclick="showSettingsCategory('tags')"><span class="settings-nav-icon">◆</span><div><b>Etiket Yönetimi</b><small>Katalog, renkler ve kullanım</small></div></button>
          <button class="settings-nav-card" data-settings-category="actions" onclick="showSettingsCategory('actions')"><span class="settings-nav-icon">⚡</span><div><b>Aksiyon Merkezi</b><small>Dashboard uyarıları ve eşikler</small></div></button>
          <button class="settings-nav-card" data-settings-category="colors" onclick="showSettingsCategory('colors')"><span class="settings-nav-icon">●</span><div><b>Durum Renkleri</b><small>Talep ve kıyaslama görsel dili</small></div></button>
          <button class="settings-nav-card" data-settings-category="company" onclick="showSettingsCategory('company')"><span class="settings-nav-icon">▦</span><div><b>Firma & Belgeler</b><small>Firma, banka, Drive ve PDF</small></div></button>
          <button class="settings-nav-card" data-settings-category="access" onclick="showSettingsCategory('access')"><span class="settings-nav-icon">⌘</span><div><b>Kullanıcı & Yetki</b><small>Üyeler, roller ve audit</small></div></button>
          <button class="settings-nav-card" data-settings-category="system" onclick="showSettingsCategory('system')"><span class="settings-nav-icon">DB</span><div><b>Sistem & Yedek</b><small>Build, veritabanı ve geri yükleme</small></div></button>
        </div>
        <div class="settings-category-intro" id="settingsCategoryIntro"><div><span class="eyebrow">AYAR KATEGORİSİ</span><b id="settingsCategoryTitle">Genel & Görünüm</b><small id="settingsCategoryDesc">Uygulama kimliği, tema ve numaralandırma ayarları.</small></div><div class="settings-category-state"><span class="status-dot"></span><span>Değişiklikler Ayarları Kaydet ile kalıcı olur.</span></div></div>
        <div class="panel settings-section-card settings-category-hidden" id="settingsTags">
 <div class="panel-head"><div><h3>Merkezi Etiket Yönetimi</h3><div class="sub">Cari ve taleplerde ortak etiketler kullanın. Eski etiketler de listelenir; silme yalnız etiket bağlantılarını kaldırır.</div></div></div>
 <div class="tag-create-grid"><div class="field"><label for="catalogTagName">Etiket adı</label><input id="catalogTagName" maxlength="80" placeholder="Örn. Öncelikli müşteri"></div><div class="field"><label for="catalogTagCategory">Kategori</label><input id="catalogTagCategory" maxlength="80" placeholder="Örn. Müşteri"></div><div class="field"><label for="catalogTagColor">Renk</label><input id="catalogTagColor" type="color" value="#246bfd"></div><button class="btn primary" id="catalogTagSave" onclick="saveCatalogTag()">Etiket Ekle</button><button class="btn" onclick="resetCatalogTagForm()">Temizle</button></div>
 <div class="field" style="margin-top:16px"><label for="settingsTagSearch">Etiket veya kategori ara</label><input id="settingsTagSearch" oninput="renderSettingsTags()" placeholder="Etiket ara…"></div><div id="settingsTagList" aria-live="polite"></div>
</div>
        <div class="panel settings-system-panel" id="settingsSystemInfo" style="margin-bottom:12px"><div class="panel-head"><div><h3>Sistem Bilgisi</h3><div class="sub">Çalışan paket ve veri katmanı bilgisi</div></div><span class="badge b-blue"><?=htmlspecialchars(ASAY_APP_BUILD)?></span></div><div class="grid4"><div class="quote-kpi"><span>Build</span><b><?=htmlspecialchars(ASAY_APP_BUILD)?></b></div><div class="quote-kpi"><span>Veri Kaynağı</span><b>MySQL / MariaDB</b></div><div class="quote-kpi"><span>Finans Kritik İşlemler</span><b>Server Transaction</b></div><div class="quote-kpi"><span>Durum</span><b id="settingsDbStatus">Aktif</b></div></div></div>
        <div class="panel settings-section-card db-manager-panel" id="settingsDatabase" style="margin-bottom:12px;<?= $isPageAdmin?'':'display:none' ?>">
          <div class="panel-head">
            <div><h3>Veritabanı Yedekleme & Geri Yükleme</h3><div class="sub">ASAY ERP verilerini phpMyAdmin'e ihtiyaç duymadan güvenli şekilde taşıyın, yedekleyin ve geri yükleyin.</div></div>
            <div class="actions"><span class="badge b-green">Admin · Güvenli Restore</span><button class="btn sm" type="button" onclick="loadDatabaseManagerStatus()">Durumu Yenile</button></div>
          </div>
          <div class="db-manager-status">
            <div><span>Aktif Veritabanı</span><b id="dbManagerDatabase">—</b></div>
            <div><span>ASAY Tablo Sayısı</span><b id="dbManagerTableCount">—</b></div>
            <div><span>Son Güvenlik Yedeği</span><b id="dbManagerLastSafety">—</b></div>
            <div><span>Yükleme Limiti</span><b id="dbManagerUploadLimit">—</b></div>
          </div>
          <div class="db-manager-grid">
            <div class="db-manager-card">
              <span class="eyebrow">1 · YEDEKLE</span>
              <h4>Mevcut DB Yedeğini İndir</h4>
              <p>Canlı ASAY ERP tablolarını şema + veri ile birlikte tek bir güvenli <b>.asaydb.json</b> dosyasına dönüştürür.</p>
              <div class="actions db-backup-actions">
                <button class="btn primary" type="button" onclick="downloadDatabaseBackup()">Güvenli Yedek (.asaydb.json)</button>
                <button class="btn" type="button" onclick="downloadDatabaseSqlBackup()">SQL Yedeği (.sql)</button>
                <button class="btn" type="button" onclick="downloadFullSystemBackup()">Tam Sistem (.asayfull.zip)</button>
              </div>
              <small>ASAY yedeği sistem içi güvenli restore içindir. SQL phpMyAdmin içindir. Tam Sistem yedeği DB + storage altındaki fiziksel dokümanları birlikte paketler.</small>
            </div>
            <div class="db-manager-card">
              <span class="eyebrow">2 · DOSYA SEÇ</span>
              <h4>Yedek Yükle / Kontrol Et</h4>
              <p>ASAY güvenli yedeği veya phpMyAdmin'den alınmış yalnız ASAY ERP tablolarını içeren SQL dosyası seçebilirsiniz.</p>
              <input id="dbRestoreFile" class="db-file-input" type="file" accept=".json,.asaydb,.sql,.zip,application/json,text/plain,application/zip" onchange="resetDatabaseRestoreValidation()">
              <button class="btn" type="button" onclick="validateDatabaseBackup()">Yedeği Kontrol Et</button>
              <div class="db-validation-result" id="dbValidationResult">Henüz dosya kontrol edilmedi.</div>
            </div>
            <div class="db-manager-card danger-zone">
              <span class="eyebrow">3 · GERİ YÜKLE</span>
              <h4>Güvenli Geri Yükleme</h4>
              <p>Restore başlamadan önce mevcut canlı DB otomatik olarak sunucuda güvenlik yedeğine alınır. Hata olursa otomatik geri dönüş denenir.</p>
              <button class="btn red" id="dbRestoreBtn" type="button" onclick="restoreDatabaseBackup()" disabled>Kontrol Edilen Yedeği Geri Yükle</button>
              <button class="btn" id="dbRollbackBtn" type="button" onclick="rollbackDatabaseRestore()" disabled>Son Güvenlik Yedeğine Geri Dön</button>
              <small>Geri yükleme iş verilerini yedekten geri getirir; işlemi yapan mevcut Admin hesabı kilitlenmeyi önlemek için korunur. İşlem sırasında sayfayı kapatmayın.</small>
            </div>
            <div class="db-manager-card danger-zone business-reset-card">
              <span class="eyebrow">4 · İŞ VERİLERİNİ SIFIRLA</span>
              <h4>Ana Kartları Koru, Hareketleri Temizle</h4>
              <p><b>Cari kartlar</b> ile <b>Kasa/Banka hesapları</b> korunur. Talepler, teklifler, siparişler, cari hareketleri, finans journal, kasa/banka hareketleri, teminatlar ve ticari evrak kayıtları temizlenir.</p>
              <div class="inline-note"><b>Korunur:</b> Cari firma bilgileri, vergi/iletişim/adres alanları, banka/kasa adı, IBAN, SWIFT, şube, para birimi, kullanıcılar ve sistem ayarları.<br><b>Sıfırlanır:</b> Cari bakiyeler = 0, Kasa/Banka bakiyeleri = 0, tüm iş/operasyon hareketleri = 0.</div>
              <label class="sub" for="businessResetConfirm"><b>Onay için SIFIRLA yazın</b></label>
              <input id="businessResetConfirm" class="db-file-input" type="text" autocomplete="off" placeholder="SIFIRLA" oninput="syncBusinessResetButton()">
              <button class="btn red" id="businessResetBtn" type="button" onclick="resetBusinessData()" disabled>İş Verilerini Sıfırla</button>
              <small>İşlem başlamadan önce sunucuda otomatik güvenlik yedeği alınır. Son Güvenlik Yedeğine Geri Dön ile geri alınabilir.</small>
            </div>
          </div>
          <div class="inline-note db-manager-note">
            <b>Koruma:</b> `mysql`, `information_schema`, `global_priv`, `columns_priv` gibi sistem tablolarını içeren SQL dosyaları otomatik reddedilir. Yalnız ASAY ERP tablo listesi kabul edilir.
            <br><b>Not:</b> `.asaydb.json` ve `.sql` yalnız veritabanını yedekler. Fiziksel PDF/Excel/görselleri de taşımak için <b>Tam Sistem (.asayfull.zip)</b> yedeğini kullanın.
          </div>
        </div>

        <div class="setting-grid">
          <div class="panel full-settings settings-section-card" id="settingsAppearance"><div class="panel-head"><div><h3>Uygulama Kimliği</h3><div class="sub">Uygulama adı, logo, favicon ve kullanıcı görsellerini yönetin.</div></div><span class="badge b-blue">Marka Ayarları</span></div>
            <div class="form-grid" style="margin-bottom:12px">
              <div class="field col2"><label>Uygulama Adı</label><input id="appNameSetting" value="ASAY İhale & Teklif OS"></div>
              <div class="field col2"><label>Alt Başlık</label><input id="appSubtitleSetting" value="Connected Commerce Workspace"></div>
            </div>
            <div class="identity-grid">
              <div class="identity-upload"><b>Uygulama Logosu</b><div class="identity-preview" id="appLogoPreview">AS</div><input type="file" accept="image/*" onchange="loadIdentityImage(this,'logo')"><div class="sub">Sidebar ve uygulama markasında kullanılır.</div></div>
              <div class="identity-upload"><b>Favicon</b><div class="identity-preview" id="faviconPreview">ICO</div><input type="file" accept="image/png,image/jpeg,image/webp,image/x-icon,image/svg+xml" onchange="loadIdentityImage(this,'favicon')"><div class="sub">Tarayıcı sekme ikonu.</div></div>
              <div class="identity-upload"><b>Aktif Kullanıcı Fotoğrafı</b><div class="identity-preview round" id="userPhotoPreview">EA</div><input type="file" accept="image/*" onchange="loadIdentityImage(this,'userPhoto')"><div class="sub">Sidebar kullanıcı profilinde gösterilir.</div></div>
              <div class="identity-upload"><b>PDF Varsayılan Logo</b><div class="identity-preview" id="pdfIdentityLogoPreview">PDF</div><input type="file" accept="image/*" onchange="loadIdentityImage(this,'pdfLogo')"><div class="sub">Yeni evraklarda varsayılan logo olarak kullanılır.</div></div>
            </div>
          </div>
          <div class="panel settings-theme-panel">
            <div class="panel-head"><div><h3>Uygulama Teması</h3><div class="sub">Hazır kurumsal temalardan birini seçin; ardından ince ayarlarla çalışma alanını kişiselleştirin.</div></div><div class="actions"><span class="badge b-blue">Canlı Önizleme</span><button class="btn sm" type="button" onclick="resetThemeTuning()">İnce Ayarları Sıfırla</button></div></div>
            <div class="theme-options theme-options-pro">
              <div class="theme-card active" data-theme-pick="light"><div class="swatch theme-swatch-ui" style="--ts-bg:#ffffff;--ts-side:#0d1b34;--ts-accent:#246bfd"><i></i><em></em><span></span></div><div class="theme-card-copy"><b>ASAY Light</b><small>Temiz, aydınlık ve günlük operasyon için dengeli.</small><span class="theme-kind">Önerilen</span></div></div>
              <div class="theme-card" data-theme-pick="navy"><div class="swatch theme-swatch-ui" style="--ts-bg:#f7fbff;--ts-side:#071f36;--ts-accent:#006fba"><i></i><em></em><span></span></div><div class="theme-card-copy"><b>Corporate Navy</b><small>İhracat, teklif ve finans ekranları için kurumsal lacivert.</small><span class="theme-kind">Kurumsal</span></div></div>
              <div class="theme-card" data-theme-pick="slate"><div class="swatch theme-swatch-ui" style="--ts-bg:#f6f8fa;--ts-side:#263746;--ts-accent:#3f6f8f"><i></i><em></em><span></span></div><div class="theme-card-copy"><b>Slate Office</b><small>Nötr, sakin ve uzun süreli kullanım için düşük kontrast.</small><span class="theme-kind">Ofis</span></div></div>
              <div class="theme-card" data-theme-pick="executive"><div class="swatch theme-swatch-ui" style="--ts-bg:#f4f6f9;--ts-side:#111827;--ts-accent:#b58a3c"><i></i><em></em><span></span></div><div class="theme-card-copy"><b>Executive</b><small>Antrasit, sıcak altın vurgu ve yönetici odaklı görünüm.</small><span class="theme-kind">Yeni</span></div></div>
              <div class="theme-card" data-theme-pick="ocean"><div class="swatch theme-swatch-ui" style="--ts-bg:#f2fbfb;--ts-side:#07343b;--ts-accent:#0f8d8d"><i></i><em></em><span></span></div><div class="theme-card-copy"><b>Ocean Trade</b><small>Turkuaz vurgu ile modern dış ticaret çalışma alanı.</small><span class="theme-kind">Yeni</span></div></div>
              <div class="theme-card" data-theme-pick="graphite"><div class="swatch theme-swatch-ui" style="--ts-bg:#191c23;--ts-side:#0b0d11;--ts-accent:#79a8ff"><i></i><em></em><span></span></div><div class="theme-card-copy"><b>Graphite Pro</b><small>Yoğun veri ekranları için profesyonel koyu çalışma modu.</small><span class="theme-kind">Koyu</span></div></div>
              <div class="theme-card" data-theme-pick="midnight"><div class="swatch theme-swatch-ui" style="--ts-bg:#0f1b2e;--ts-side:#050b14;--ts-accent:#5b9cff"><i></i><em></em><span></span></div><div class="theme-card-copy"><b>Midnight</b><small>Derin lacivert ile gece kullanımına uygun yüksek kontrast.</small><span class="theme-kind">Koyu</span></div></div>
            </div>
            <div class="settings-subhead"><div><b>Görünüm İnce Ayarları</b><small>Hazır temayı bozmadan kontrol yoğunluğu ve yüzey karakterini ayarlayın.</small></div></div>
            <div class="theme-pro-grid theme-pro-grid-v2">
              <div class="theme-control accent-control"><label>Vurgu Rengi</label><div class="theme-color-line"><input type="color" id="appAccent" value="#246bfd" oninput="setAccent(this.value)"><span id="accentHexLabel">#246BFD</span></div></div>
              <div class="theme-control accent-control"><label>Menü Yazı Rengi</label><div class="theme-color-line"><input type="color" id="uiMenuText" value="#c9d8ed" oninput="applyUiSettings()"><span id="menuTextHexLabel">#C9D8ED</span></div></div>
              <div class="theme-control accent-control"><label>Menü İkon Rengi</label><div class="theme-color-line"><input type="color" id="uiMenuIcon" value="#c9d8ed" oninput="applyUiSettings()"><span id="menuIconHexLabel">#C9D8ED</span></div></div>
              <div class="theme-control"><label>Arayüz Yoğunluğu</label><select id="uiDensity" onchange="applyUiSettings()"><option value="comfortable">Rahat</option><option value="compact">Kompakt</option></select></div>
              <div class="theme-control"><label>Sidebar Genişliği</label><select id="uiSidebar" onchange="applyUiSettings()"><option value="standard">Standart</option><option value="compact">Dar</option></select></div>
              <div class="theme-control"><label>Kart Stili</label><select id="uiCardStyle" onchange="applyUiSettings()"><option value="soft">Dengeli</option><option value="flat">Düz</option><option value="elevated">Yükseltilmiş</option></select></div>
              <div class="theme-control range-control"><label>Köşe Yuvarlaklığı <span id="radiusValueLabel">14 px</span></label><input id="uiRadius" type="range" min="6" max="22" value="14" oninput="applyUiSettings()"></div>
              <div class="theme-control range-control"><label>Yazı Ölçeği <span id="fontScaleValueLabel">100%</span></label><input id="uiFontScale" type="range" min="90" max="110" value="100" oninput="applyUiSettings()"></div>
            </div>
            <div class="theme-live-sample"><div class="theme-sample-sidebar"><span></span><i></i><i></i><i></i></div><div class="theme-sample-main"><div class="theme-sample-top"></div><div class="theme-sample-cards"><b></b><b></b><b></b></div><div class="theme-sample-row"></div></div><div class="theme-live-copy"><b>Canlı Arayüz Örneği</b><small>Seçtiğiniz tema ve vurgu rengi uygulamanın gerçek CSS değişkenlerini kullanır.</small></div></div>
          </div><div class="panel"><div class="panel-head"><div><h3>Talep No Ayarları</h3><div class="sub">Yeni talep numarasının başlangıcını ve formatını belirleyin.</div></div><span class="badge b-blue">Otomatik Numara</span></div><div class="form-grid"><div class="field col2"><label>Başlangıç / Prefix</label><input id="requestNoPrefix" value="RFQ" placeholder="RFQ, ASAY, TLP"></div><div class="field col2"><label>Format</label><select id="requestNoFormat" onchange="toggleCustomRequestPattern()"><option value="prefix-year-seq">PREFIX-YIL-0001</option><option value="prefix-seq">PREFIX-0001</option><option value="custom">Özel Şablon</option></select></div><div class="field col2"><label>Sıradaki Numara</label><input id="requestNoNext" type="number" min="1" value="44"></div><div class="field col2" id="requestPatternField" style="display:none"><label>Özel Şablon</label><input id="requestNoPattern" value="{PREFIX}-{YEAR}-{SEQ4}" placeholder="{PREFIX}-{YEAR}-{SEQ4}"></div><div class="field full"><div class="inline-note">Kullanılabilir alanlar: <b>{PREFIX}</b>, <b>{YEAR}</b>, <b>{SEQ}</b>, <b>{SEQ4}</b>. Örnek: ASAY-{YEAR}-{SEQ4}</div></div></div></div>
          <div class="panel full-settings settings-section-card" id="settingsActions">
            <div class="panel-head settings-action-head">
              <div><h3>Bugün Aksiyon Gerekenler Ayarları</h3><div class="sub">Dashboard uyarılarının hangi kayıtlar için, ne zaman ve hangi kritik seviyede görüneceğini tek merkezden yönetin.</div></div>
              <div class="actions"><button class="btn sm" type="button" onclick="resetActionSettingsToDefault()">Varsayılanlar</button><button class="btn sm" type="button" onclick="setAllActionSettings(true)">Hepsini Aç</button><button class="btn sm" type="button" onclick="setAllActionSettings(false)">Hepsini Kapat</button></div>
            </div>
            <div class="action-settings-summary">
              <div><span>1</span><b>Uyarı Türünü Aç/Kapat</b><small>Dashboard'da gösterilecek aksiyonları seçin.</small></div>
              <div><span>2</span><b>Zaman Kapsamı</b><small>Bugün, geçmiş ve yaklaşan kayıt davranışını belirleyin.</small></div>
              <div><span>3</span><b>Kritiklik & Renk</b><small>Yaklaşıyor / kritik / çok kritik seviyelerini tanımlayın.</small></div>
            </div>
            <div class="inline-note action-settings-note"><b>Nasıl çalışır?</b> “Kaç gün kala” yalnız gelecekteki kayıtlar içindir. <b>Bugün</b> ve <b>Geçmiş</b> seçenekleri bağımsızdır. Aksiyonlar gerçek veri durumuna göre otomatik kapanır.</div>
            <div class="action-settings-table">
              <div class="action-setting-row">
                <div class="action-name"><div class="action-name-head"><span class="action-index">01</span><div><b>Son Teklif / İhale Tarihleri</b><em>İhale</em></div></div><small>Yaklaşan, bugün son günü olan ve gecikmiş talepler. Kritik renk eşikleri aşağıdan ayarlanır.</small></div>
                <div><label>Göster</label><select id="actReqEnabled"><option value="1">Aktif</option><option value="0">Pasif</option></select></div>
                <div><label>Bugün</label><select id="actReqToday"><option value="1">Göster</option><option value="0">Gizle</option></select></div>
                <div><label>Geçmiş</label><select id="actReqOverdue"><option value="1">Göster</option><option value="0">Gizle</option></select></div>
                <div></div><div></div>
                <div class="action-thresholds">
                  <div class="action-threshold"><b>Yaklaşıyor ≤</b><div class="threshold-controls"><input id="actReqL1Days" type="number" min="1" max="365" value="5"><input id="actReqL1Color" type="color" value="#f59e0b"></div></div>
                  <div class="action-threshold"><b>Kritik ≤</b><div class="threshold-controls"><input id="actReqL2Days" type="number" min="1" max="365" value="3"><input id="actReqL2Color" type="color" value="#ef4444"></div></div>
                  <div class="action-threshold"><b>Çok Kritik ≤</b><div class="threshold-controls"><input id="actReqL3Days" type="number" min="1" max="365" value="1"><input id="actReqL3Color" type="color" value="#7c3aed"></div></div>
                  <div class="action-threshold"><b>Bugün</b><div class="threshold-controls"><input value="0" disabled><input id="actReqTodayColor" type="color" value="#be123c"></div></div>
                  <div class="action-threshold"><b>Geçmiş</b><div class="threshold-controls"><input value="—" disabled><input id="actReqOverdueColor" type="color" value="#111827"></div></div>
                </div>
              </div>
              <div class="action-setting-row">
                <div class="action-name"><div class="action-name-head"><span class="action-index">02</span><div><b>Sevkiyata Hazır Operasyonlar</b><em>Operasyon</em></div></div><small>PO durumu “Sevkiyata Hazır” olan operasyonlar. Tarih eşiği olmadığı için tek vurgu rengi kullanır.</small></div>
                <div><label>Göster</label><select id="actReadyEnabled"><option value="1">Aktif</option><option value="0">Pasif</option></select></div>
                <div><label>Listeleme</label><select disabled><option>Duruma göre</option></select></div>
                <div><label>Bugün</label><select disabled><option>—</option></select></div>
                <div><label>Geçmiş</label><select disabled><option>—</option></select></div>
                <div><label>Renk</label><div class="action-color-wrap"><input id="actReadyColor" type="color" value="#d97706"></div></div>
              </div>
              <div class="action-setting-row">
                <div class="action-name"><div class="action-name-head"><span class="action-index">03</span><div><b>Kazanıldı · Evrak Eksik</b><em>Evrak</em></div></div><small>4/4 ticari evrak seti tamamlanmayan kazanılmış talepler.</small></div>
                <div><label>Göster</label><select id="actDocsPendingEnabled"><option value="1">Aktif</option><option value="0">Pasif</option></select></div>
                <div><label>Renk</label><div class="action-color-wrap"><input id="actDocsPendingColor" type="color" value="#f59e0b"></div></div>
                <div></div><div></div><div></div>
              </div>
              <div class="action-setting-row">
                <div class="action-name"><div class="action-name-head"><span class="action-index">04</span><div><b>Müşteri Tahsilat Vadeleri</b><em>Tahsilat</em></div></div><small>Müşteri ve kurum carilerinin yaklaşan tahsilat vadeleri. Her kritik seviye ayrı renklendirilebilir.</small></div>
                <div><label>Göster</label><select id="actCustomerEnabled"><option value="1">Aktif</option><option value="0">Pasif</option></select></div>
                <div><label>Bugün</label><select id="actCustomerToday"><option value="1">Göster</option><option value="0">Gizle</option></select></div>
                <div><label>Geçmiş</label><select id="actCustomerOverdue"><option value="1">Göster</option><option value="0">Gizle</option></select></div>
                <div></div><div></div>
                <div class="action-thresholds">
                  <div class="action-threshold"><b>Yaklaşıyor ≤</b><div class="threshold-controls"><input id="actCustomerL1Days" type="number" min="1" max="365" value="5"><input id="actCustomerL1Color" type="color" value="#f59e0b"></div></div>
                  <div class="action-threshold"><b>Kritik ≤</b><div class="threshold-controls"><input id="actCustomerL2Days" type="number" min="1" max="365" value="3"><input id="actCustomerL2Color" type="color" value="#ef4444"></div></div>
                  <div class="action-threshold"><b>Çok Kritik ≤</b><div class="threshold-controls"><input id="actCustomerL3Days" type="number" min="1" max="365" value="1"><input id="actCustomerL3Color" type="color" value="#7c3aed"></div></div>
                  <div class="action-threshold"><b>Bugün</b><div class="threshold-controls"><input value="0" disabled><input id="actCustomerTodayColor" type="color" value="#be123c"></div></div>
                  <div class="action-threshold"><b>Geçmiş</b><div class="threshold-controls"><input value="—" disabled><input id="actCustomerOverdueColor" type="color" value="#111827"></div></div>
                </div>
              </div>
              <div class="action-setting-row">
                <div class="action-name"><div class="action-name-head"><span class="action-index">05</span><div><b>Tedarikçi Ödeme Vadeleri</b><em>Ödeme</em></div></div><small>Tedarikçi carilerinin yaklaşan ödeme vadeleri. Her kritik seviye ayrı renklendirilebilir.</small></div>
                <div><label>Göster</label><select id="actSupplierEnabled"><option value="1">Aktif</option><option value="0">Pasif</option></select></div>
                <div><label>Bugün</label><select id="actSupplierToday"><option value="1">Göster</option><option value="0">Gizle</option></select></div>
                <div><label>Geçmiş</label><select id="actSupplierOverdue"><option value="1">Göster</option><option value="0">Gizle</option></select></div>
                <div></div><div></div>
                <div class="action-thresholds">
                  <div class="action-threshold"><b>Yaklaşıyor ≤</b><div class="threshold-controls"><input id="actSupplierL1Days" type="number" min="1" max="365" value="5"><input id="actSupplierL1Color" type="color" value="#f59e0b"></div></div>
                  <div class="action-threshold"><b>Kritik ≤</b><div class="threshold-controls"><input id="actSupplierL2Days" type="number" min="1" max="365" value="3"><input id="actSupplierL2Color" type="color" value="#ef4444"></div></div>
                  <div class="action-threshold"><b>Çok Kritik ≤</b><div class="threshold-controls"><input id="actSupplierL3Days" type="number" min="1" max="365" value="1"><input id="actSupplierL3Color" type="color" value="#7c3aed"></div></div>
                  <div class="action-threshold"><b>Bugün</b><div class="threshold-controls"><input value="0" disabled><input id="actSupplierTodayColor" type="color" value="#be123c"></div></div>
                  <div class="action-threshold"><b>Geçmiş</b><div class="threshold-controls"><input value="—" disabled><input id="actSupplierOverdueColor" type="color" value="#111827"></div></div>
                </div>
              </div>
            </div>
          </div><div class="panel full-settings settings-section-card" id="settingsStatusColors">
            <div class="panel-head"><div><h3>Durum & Kıyaslama Renkleri</h3><div class="sub">Talep Merkezi durumlarını ve Kıyaslama ekranındaki teknik uygunluk renklerini yönetin. Değişiklikleri kaydetmeden önce canlı önizleyebilirsiniz.</div></div><div class="actions"><button class="btn sm" type="button" onclick="resetStatusColorsToDefault()">Varsayılan Renkler</button></div></div>
            <div class="status-color-preview-flow" id="statusColorPreviewFlow">
              <span style="--c:var(--status-draft)">Taslak</span><i>→</i><span style="--c:var(--status-collecting)">Fiyat Toplanıyor</span><i>→</i><span style="--c:var(--status-ready)">Kıyaslama</span><i>→</i><span style="--c:var(--status-quote)">Teklif Hazır</span><i>→</i><span style="--c:var(--status-sent)">Gönderildi</span><i>→</i><span style="--c:var(--status-won)">Kazanıldı</span>
            </div>
            <div class="status-color-grid">
              <div class="status-color-card" style="--status-preview:var(--status-draft)"><div class="status-color-sample"></div><div class="status-color-copy"><b>Taslak</b><small>Henüz işleme alınmamış yeni talepler</small></div><div class="status-color-control"><input id="statusColorDraft" type="color" oninput="previewStatusColor('draft',this.value)"><span id="statusColorDraftHex">—</span></div></div>
              <div class="status-color-card" style="--status-preview:var(--status-collecting)"><div class="status-color-sample"></div><div class="status-color-copy"><b>Fiyat Toplanıyor</b><small>Tedarikçi teklifleri beklenen talepler</small></div><div class="status-color-control"><input id="statusColorCollecting" type="color" oninput="previewStatusColor('collecting',this.value)"><span id="statusColorCollectingHex">—</span></div></div>
              <div class="status-color-card" style="--status-preview:var(--status-ready)"><div class="status-color-sample"></div><div class="status-color-copy"><b>Kıyaslamaya Hazır</b><small>Teklif kıyaslaması yapılabilecek talepler</small></div><div class="status-color-control"><input id="statusColorReady" type="color" oninput="previewStatusColor('ready',this.value)"><span id="statusColorReadyHex">—</span></div></div>
              <div class="status-color-card" style="--status-preview:var(--status-quote)"><div class="status-color-sample"></div><div class="status-color-copy"><b>Müşteri Teklifi Hazır</b><small>Satış fiyatı ve müşteri teklifi hazırlanmış kayıtlar</small></div><div class="status-color-control"><input id="statusColorQuote" type="color" oninput="previewStatusColor('quote',this.value)"><span id="statusColorQuoteHex">—</span></div></div>
              <div class="status-color-card" style="--status-preview:var(--status-sent)"><div class="status-color-sample"></div><div class="status-color-copy"><b>Teklif Gönderildi</b><small>Müşteriye gönderilmiş ve sonuç bekleyen teklifler</small></div><div class="status-color-control"><input id="statusColorSent" type="color" oninput="previewStatusColor('sent',this.value)"><span id="statusColorSentHex">—</span></div></div>
              <div class="status-color-card" style="--status-preview:var(--status-won)"><div class="status-color-sample"></div><div class="status-color-copy"><b>Kazanıldı</b><small>Siparişe dönüşmüş başarılı teklifler</small></div><div class="status-color-control"><input id="statusColorWon" type="color" oninput="previewStatusColor('won',this.value)"><span id="statusColorWonHex">—</span></div></div>
              <div class="status-color-card" style="--status-preview:var(--status-lost)"><div class="status-color-sample"></div><div class="status-color-copy"><b>Kaybedildi</b><small>Sonuçlanmış ancak kazanılmamış teklifler</small></div><div class="status-color-control"><input id="statusColorLost" type="color" oninput="previewStatusColor('lost',this.value)"><span id="statusColorLostHex">—</span></div></div>
              <div class="status-color-card" style="--status-preview:var(--status-cancelled)"><div class="status-color-sample"></div><div class="status-color-copy"><b>İptal Edildi</b><small>İşlemden kaldırılmış veya iptal edilmiş kayıtlar</small></div><div class="status-color-control"><input id="statusColorCancelled" type="color" oninput="previewStatusColor('cancelled',this.value)"><span id="statusColorCancelledHex">—</span></div></div>
              <div class="status-color-card" style="--status-preview:var(--status-comparisonSuitable)"><div class="status-color-sample"></div><div class="status-color-copy"><b>Kıyaslama · Uygun</b><small>Teknik olarak uygun tedarikçi teklif hücreleri</small></div><div class="status-color-control"><input id="statusColorComparisonSuitable" type="color" oninput="previewStatusColor('comparisonSuitable',this.value)"><span id="statusColorComparisonSuitableHex">—</span></div></div>
              <div class="status-color-card" style="--status-preview:var(--status-comparisonConditional)"><div class="status-color-sample"></div><div class="status-color-copy"><b>Kıyaslama · Şartlı</b><small>Şartlı teknik uygunluk verilen teklifler</small></div><div class="status-color-control"><input id="statusColorComparisonConditional" type="color" oninput="previewStatusColor('comparisonConditional',this.value)"><span id="statusColorComparisonConditionalHex">—</span></div></div>
              <div class="status-color-card" style="--status-preview:var(--status-comparisonUnsuitable)"><div class="status-color-sample"></div><div class="status-color-copy"><b>Kıyaslama · Uygun Değil</b><small>Teknik olarak uygun olmayan ve seçilemeyen teklifler</small></div><div class="status-color-control"><input id="statusColorComparisonUnsuitable" type="color" oninput="previewStatusColor('comparisonUnsuitable',this.value)"><span id="statusColorComparisonUnsuitableHex">—</span></div></div>
              <div class="status-color-card" style="--status-preview:var(--status-comparisonNoPrice)"><div class="status-color-sample"></div><div class="status-color-copy"><b>Kıyaslama · Fiyat Yok</b><small>Fiyat girilmemiş tedarikçi-kalem hücreleri</small></div><div class="status-color-control"><input id="statusColorComparisonNoPrice" type="color" oninput="previewStatusColor('comparisonNoPrice',this.value)"><span id="statusColorComparisonNoPriceHex">—</span></div></div>
              <div class="status-color-card" style="--status-preview:var(--status-comparisonSelected)"><div class="status-color-sample"></div><div class="status-color-copy"><b>Kıyaslama · Seçili</b><small>Kullanıcı tarafından seçilen tedarikçi teklif hücreleri</small></div><div class="status-color-control"><input id="statusColorComparisonSelected" type="color" oninput="previewStatusColor('comparisonSelected',this.value)"><span id="statusColorComparisonSelectedHex">—</span></div></div>
            </div>
          </div><div class="settings-company-tools"><div class="panel settings-section-card" id="settingsCompany"><div class="panel-head"><div><h3>Firma Bilgileri</h3><div class="sub">Teklif ve ticari evraklarda kullanılır</div></div></div><div class="form-grid">
            <div class="field full"><label>Ticari Ünvan</label><input id="coName"></div><div class="field col2"><label>Kısa Ünvan / Marka</label><input id="coBrand"></div><div class="field col2"><label>Web Sitesi</label><input id="coWeb"></div>
            <div class="field col2"><label>E-posta</label><input id="coEmail"></div><div class="field col2"><label>Telefon</label><input id="coPhone"></div>
            <div class="field col2"><label>Vergi Dairesi</label><input id="coTaxOffice"></div><div class="field col2"><label>Vergi No</label><input id="coTaxNo"></div><div class="field col2"><label>MERSİS No</label><input id="coMersis"></div><div class="field col2"><label>Ticaret Sicil No</label><input id="coRegistry"></div>
            <div class="field full"><label>Adres</label><textarea id="coAddress"></textarea></div>
            <div class="field col2"><label>Varsayılan EUR Banka Hesabı</label><select id="coEurBankAccount" onchange="renderCompanyBankInfo('EUR')"></select></div><div class="field col2"><label>EUR Hesap Bilgisi</label><div class="bank-default-card" id="coEurBankInfo"></div></div><div class="field col2"><label>Varsayılan USD Banka Hesabı</label><select id="coUsdBankAccount" onchange="renderCompanyBankInfo('USD')"></select></div><div class="field col2"><label>USD Hesap Bilgisi</label><div class="bank-default-card" id="coUsdBankInfo"></div></div>
            <div class="field full"><label>Belge Alt Bilgisi</label><input id="coFooter"></div>
          </div></div>
          <div class="panel settings-compact-tool"><div class="panel-head"><div><h3>Google Drive Yedekleme</h3><div class="sub">MySQL SQL yedeğini Google Drive’a güvenli şekilde aktarın.</div></div><span class="badge b-green">Opsiyonel</span></div><div class="inline-note">Google Drive canlı veritabanı olarak kullanılmaz; burada otomatik/manuel SQL yedek deposu olarak bağlanır.</div><div class="actions" style="margin-top:10px"><a class="btn primary" href="google-drive/backup.php" target="_blank">Google Drive Yedekleme Aç</a></div></div>
          <div class="panel settings-compact-tool"><div class="panel-head"><div><h3>PDF / Evrak Varsayılanları</h3><div class="sub">Uygulama temasını etkilemez</div></div></div><div class="form-grid"><div class="field col2"><label>PDF Vurgu</label><input type="color" value="#163d73"></div><div class="field col2"><label>Varsayılan Sayfa</label><select><option>A4 Dikey</option><option>A4 Yatay</option></select></div><div class="field"><label>GTİP</label><select><option>Göster</option><option>Gizle</option></select></div><div class="field"><label>Menşe</label><select><option>Göster</option><option>Gizle</option></select></div><div class="field"><label>Banka</label><select><option>Göster</option><option>Gizle</option></select></div><div class="field"><label>Garanti</label><select><option>Göster</option><option>Gizle</option></select></div></div></div>
          </div>
          <div class="panel full-settings"><div class="panel-head"><div><h3>Audit / İşlem Geçmişi</h3><div class="sub">Kritik değişiklikleri kullanıcı ve zaman bilgisiyle izleyin.</div></div><button class="btn sm" onclick="loadAuditLog()">Yenile</button></div><div class="table-wrap"><table class="table"><thead><tr><th>Tarih</th><th>Kullanıcı</th><th>İşlem</th><th>Varlık</th><th>Detay</th></tr></thead><tbody id="auditRows"><tr><td colspan="5" class="empty">Yenile ile audit kayıtlarını yükleyin.</td></tr></tbody></table></div></div><div class="panel full-settings settings-section-card" id="settingsAccess"><div class="panel-head"><div><h3>Kullanıcılar & Üyeler</h3><div class="sub">Kullanıcı ekleyin, rol/departman atayın, düzenleyin veya silin.</div></div><button class="btn primary sm" onclick="openUserModal()">+ Kullanıcı Ekle</button></div><div class="user-list" id="userList"></div></div><div class="panel full-settings"><div class="panel-head"><div><h3>Rol & Yetki Matrisi</h3><div class="sub">Her modül için Görüntüleme ve Yazma yetkisi ayrı yönetilir.</div></div><button class="btn sm" onclick="addRole()">+ Rol Ekle</button></div><div class="table-wrap"><table class="table" id="permissionsTable"><thead><tr><th rowspan="2">Rol</th><th colspan="2">Talep</th><th colspan="2">Tedarikçi</th><th colspan="2">Maliyet</th><th colspan="2">Cari</th><th colspan="2">Kasa</th><th colspan="2">Evrak</th><th colspan="2">Ayarlar</th></tr><tr><th>Gör</th><th>Yaz</th><th>Gör</th><th>Yaz</th><th>Gör</th><th>Yaz</th><th>Gör</th><th>Yaz</th><th>Gör</th><th>Yaz</th><th>Gör</th><th>Yaz</th><th>Gör</th><th>Yaz</th></tr></thead><tbody></tbody></table></div></div>
          </div>
        </div>
      </section>

    </div>
  </main>
</div>

<div class="modal-backdrop" id="requestModal">
 <div class="modal">
  <div class="modal-head"><div><span class="eyebrow">YENİ KAYIT</span><h2>Yeni Talep / İhale</h2><div class="sub">Kaydet ve Devam Et ile kaydı veritabanına yazıp aynı talebin Tedarikçi Teklifleri aşamasına geçin.</div></div><button class="close" onclick="closeModal('requestModal')">×</button></div>
  <div class="modal-body">
   <div class="section-title"><b>Müşteri & Talep Bilgileri</b><span class="badge b-blue">Talep No otomatik</span></div>
   <div class="form-grid">
    <div class="field col2"><label>Müşteri / Kurum</label><select id="newCustomerSelect"><option value="">Müşteri / kurum seçin</option></select></div>
    <div class="field col2"><label>Talep Türü</label><select id="newType"><option>Yurtdışı Müşteri Talebi</option><option>Yurtiçi Müşteri Talebi</option><option>Resmî / Özel İhale</option></select></div>
    <div class="field col2"><button class="btn" type="button" onclick="toggleNewCustomer()">+ Yeni Müşteri / Kurum Ekle</button></div>
    <div class="field"><label>Para Birimi</label><select id="newCurrency" onchange="syncBank()"><option>EUR</option><option>USD</option><option>TRY</option></select></div>
    <div class="field"><label>Son Teklif Tarihi</label><input type="date" id="newDeadline" value="2026-10-10"></div>
    <div class="field"><label>Öncelik</label><select id="newPriority"><option value="normal">Normal</option><option value="priority">Öncelikli</option><option value="urgent">Acil</option></select></div>
   </div>
   <div class="new-customer" id="newCustomerBox"><div class="form-grid"><div class="field col2"><label>Firma / Kurum</label><input id="inlineCustomerName" placeholder="Yeni müşteri / kurum adı"></div><div class="field"><label>Ülke</label><input id="inlineCustomerCountry" value="Türkiye"></div><div class="field"><label>Yetkili</label><input id="inlineCustomerContact"></div><div class="field col2"><label>Telefon</label><input id="inlineCustomerPhone"></div><div class="field col2"><label>E-posta</label><input id="inlineCustomerEmail" type="email"></div></div><div class="actions" style="margin-top:10px"><button class="btn primary" onclick="saveInlineCustomer()">Kaydet ve Kapat</button><button class="btn" onclick="toggleNewCustomer(false)">Kapat</button></div></div>
   <div class="section-title"><b>Ticari Şartlar</b></div>
   <div class="form-grid">
    <div class="field"><label>Teslim Şekli</label><select id="newDelivery"><option>EXW</option><option>FCA</option><option>FOB</option><option selected>CIF</option><option>CFR</option><option>CPT</option><option>CIP</option><option>DAP</option><option>DPU</option><option>DDP</option></select></div>
    <div class="field"><label>Teslim Yeri</label><input id="newDeliveryPlace" value=""></div>
    <div class="field col2"><label>Ödeme Seçeneği</label><select id="newPayment" onchange="syncRequestPaymentPlan()"><option value="cash">Peşin / T/T</option><option value="tt30_70" selected>T/T %30 Peşin / %70 Sevkiyat Öncesi</option><option value="tt50_50">%50 Peşin / %50 Nakliye Öncesi</option><option value="net30">Vadeli 30 Gün</option><option value="lc">Akreditif (L/C)</option><option value="cad">Vesaik Mukabili (CAD)</option><option value="open_account">Mal Mukabili</option><option value="manual">Özel / Manuel</option></select></div>
    <div class="manual-payment-plan" id="manualPaymentPlan">
      <div class="section-title" style="margin:0"><b>Manuel Ödeme Oranları</b><button class="btn sm" type="button" onclick="addManualPaymentRow()">+ Aşama Ekle</button></div>
      <div class="sub">Her ödeme aşamasının oranını ve açıklamasını girin. Toplam oran %100 olmalıdır.</div>
      <div class="payment-plan-rows" id="manualPaymentRows"></div>
      <div class="payment-plan-total"><span>Toplam Ödeme Oranı</span><b id="manualPaymentTotal">%0</b></div>
    </div>
    <div class="field col2"><label>Operasyon Bankası / Kasa</label><select id="newBank"><option value="">Kasa / banka hesabı bulunmuyor</option></select><small id="newBankHelp">Kasa & Banka bölümünde açılmış gerçek hesaplar listelenir.</small></div>
    <div class="field vat-field" style="display:none"><label>KDV Şekli</label><select><option>KDV Hariç</option><option>KDV Dahil</option><option>KDV Yok</option></select></div>
    <div class="field vat-field" style="display:none"><label>KDV Oranı %</label><input type="number" value="20"></div>
    <div class="field full"><label>Konu Başlığı</label><input id="newSubject" maxlength="255" placeholder="Örn. 20 gr Poşet Fındık Ezmesi Tedarik İhalesi"><small>Kartlarda ve raporlarda ürünün ilk kalemi yerine bu başlık gösterilir.</small></div>
    <div class="field full"><label>Talep Açıklaması</label><textarea id="newDescription" rows="3" placeholder="Talep, ihale veya müşteri hakkında açıklama..."></textarea></div>
   </div>
   <div class="section-title"><b>Talep Dokümanları</b><span class="badge b-blue">İhale / Şartname / Teknik Dosya</span></div>
   <div class="upload-zone"><div class="form-grid"><div class="field col2"><label>Doküman Kategorisi</label><select id="requestAttachmentCategory"><option value="tender">İhale Dokümanı</option><option value="technical">Teknik Şartname</option><option value="contract">Sözleşme</option><option value="drawing">Çizim</option><option value="customer">Müşteri Evrakı</option><option value="supplier">Tedarikçi Evrakı</option><option value="other">Diğer</option></select></div><div class="field col2"><label>Versiyon</label><input id="requestAttachmentVersion" type="number" min="1" value="1"></div><div class="field full"><label>Doküman Açıklaması</label><textarea id="requestAttachmentDescription" rows="2" placeholder="Örn. RFQ teknik şartnamesi / müşteri revizyonu / fiyat talep eki..."></textarea></div><div class="field full"><input id="requestAttachmentFiles" type="file" multiple></div></div><div class="sub" style="margin-top:5px">İzinli: PDF, Word, Excel, CSV, görsel, ZIP, DWG/DXF. Dosya başına en fazla 25 MB.</div><div id="requestAttachmentPending" class="sub"></div></div>
   <div class="section-title request-items-title">
    <div><b>Talep Edilen Ürünler</b><span class="badge b-blue" id="requestItemCount">0 kalem</span></div>
    <div class="actions request-item-actions">
      <button class="btn sm" type="button" onclick="addRequestItem()">+ Kalem Ekle</button>
      <button class="btn sm" type="button" onclick="triggerRequestItemsExcelUpload()">Excel’den Kalem Yükle</button>
      <a class="btn sm" href="templates/Talep_Kalemleri_Excel_Sablonu.xlsx" download>Excel Şablonu İndir</a>
      <input id="requestItemsExcelFile" type="file" accept=".xlsx,.csv" style="display:none" onchange="importRequestItemsExcel(this)">
    </div>
   </div>
   <div class="inline-note request-excel-note">Excel kolonları: <b>S/N · Talep Edilen Ürünler · Miktar · BİRİM · Marka / Model · Teknik Özellik</b>. Yüklenen kalemler bu listede anında görünür ve kaydetmeden önce düzenlenebilir.</div>
   <div class="item-editor" id="requestItemEditor"></div>
  </div>
  <div class="modal-foot"><button class="btn" type="button" onclick="closeModal('requestModal')">Kapat</button><button class="btn" id="requestSaveBtn" type="button" onclick="saveRequest(false)">Kaydet</button><button class="btn primary" id="requestSaveContinueBtn" type="button" onclick="saveRequest(true)">Kaydet ve Devam Et →</button></div>
 </div>
</div>




<div class="modal-backdrop" id="attachmentUploadModal">
 <div class="modal" style="max-width:680px">
  <div class="modal-head"><div><span class="eyebrow">DOKÜMAN</span><h2>Talep Dokümanı Ekle</h2><div class="sub">İhale dokümanı, şartname, çizim veya benzeri dosyaları yükleyin.</div></div><button class="close" onclick="closeModal('attachmentUploadModal')">×</button></div>
  <div class="modal-body"><div class="form-grid"><div class="field full"><label>Talep / İhale</label><select id="attachmentUploadRequest"></select></div><div class="field col2"><label>Kategori</label><select id="attachmentUploadCategory"><option value="tender">İhale Dokümanı</option><option value="technical">Teknik Şartname</option><option value="contract">Sözleşme</option><option value="drawing">Çizim</option><option value="customer">Müşteri Evrakı</option><option value="supplier">Tedarikçi Evrakı</option><option value="other">Diğer</option></select></div><div class="field col2"><label>Versiyon / Revizyon</label><input id="attachmentUploadVersion" type="number" min="1" value="1"></div><div class="field full"><label>Dosyalar</label><input id="attachmentUploadFiles" type="file" multiple onchange="renderAttachmentFileDescriptions()"><div id="attachmentUploadFileDescriptions" class="doc-upload-file-descriptions"></div></div><div class="field full"><label>Doküman Açıklaması</label><textarea id="attachmentUploadDescription" rows="3" placeholder="Bu dokümanın hangi ihale/talep aşamasına ait olduğunu ve içeriğini kısaca yazın."></textarea><small>Bu açıklama doküman özelliklerinde ve görüntüleyicide gösterilir.</small></div></div></div>
  <div class="modal-foot"><button class="btn" onclick="closeModal('attachmentUploadModal')">Kapat</button><button class="btn primary" onclick="uploadAttachmentModalFiles()">Yükle</button></div>
 </div>
</div>

<div class="modal-backdrop" id="attachmentPropertiesModal">
 <div class="modal" style="max-width:620px" role="dialog" aria-modal="true" aria-labelledby="attachmentPropertiesTitle">
  <div class="modal-head"><div><span class="eyebrow">DOKÜMAN ÖZELLİKLERİ</span><h2 id="attachmentPropertiesTitle">Doküman Özelliklerini Düzenle</h2><div class="sub" id="attachmentPropertiesName"></div></div><button class="close" onclick="closeModal('attachmentPropertiesModal')">×</button></div>
  <div class="modal-body"><input type="hidden" id="attachmentPropertiesId"><div class="form-grid"><div class="field col2"><label>Kategori</label><select id="attachmentPropertiesCategory"><option value="tender">İhale Dokümanı</option><option value="technical">Teknik Şartname</option><option value="contract">Sözleşme</option><option value="drawing">Çizim</option><option value="customer">Müşteri Evrakı</option><option value="supplier">Tedarikçi Evrakı</option><option value="other">Diğer</option></select></div><div class="field col2"><label>Revizyon</label><input id="attachmentPropertiesVersion" type="number" min="1" value="1"></div><div class="field full"><label>Açıklama</label><textarea id="attachmentPropertiesDescription" rows="5"></textarea></div></div></div>
  <div class="modal-foot"><button class="btn" onclick="closeModal('attachmentPropertiesModal')">Vazgeç</button><button class="btn primary" onclick="saveAttachmentProperties()">Özellikleri Kaydet</button></div>
 </div>
</div>

<div class="modal-backdrop" id="customerPrepayModal">
 <div class="modal" style="max-width:680px">
  <div class="modal-head"><div><span class="eyebrow">MÜŞTERİ TAHSİLATI</span><h2>Müşteriden Tahsil Et</h2><div class="sub">Operasyon durumundan bağımsız olarak yüzde veya sabit tutarda tahsilat alın.</div></div><button class="close" onclick="closeModal('customerPrepayModal')">×</button></div>
  <div class="modal-body">
   <div class="prepay-summary"><div><span>Sipariş Toplamı</span><b id="prepayOrderTotal">—</b></div><div><span>Şimdiye Kadar Tahsil</span><b id="prepayPaid">—</b></div><div><span>Kalan Alacak</span><b id="prepayRemaining">—</b></div></div>
   <div class="form-grid"><div class="field"><label>Yüzde Bazı</label><select id="prepayBasis" onchange="syncPrepayFromPercent()"><option value="total">Sipariş Toplamının %X’i</option><option value="remaining">Kalan Alacağın %X’i</option></select></div><div class="field"><label>Tahsilat %</label><input id="prepayPercent" type="number" min="0" max="100" step=".01" value="30" oninput="syncPrepayFromPercent()"></div><div class="field"><label>Tahsilat Tutarı (Sipariş Dövizi)</label><input id="prepayAmount" type="number" min="0" step=".01" oninput="syncPrepayFromAmount()"></div><div class="field col2"><label>Kasa / Banka</label><select id="prepayBank" onchange="syncPrepayFxVisibility()"></select></div><div class="field col2"><label>İlgili Teslimat Partisi <small>(opsiyonel)</small></label><select id="prepayBatch"><option value="">Genel Sipariş Tahsilatı</option></select></div><div class="field col2" id="prepayFxField" style="display:none"><label>Kur / Parite (1 sipariş dövizi = ? banka dövizi)</label><input id="prepayFxRate" type="number" min="0.000001" step="0.000001" value="1"></div><div class="field col2"><label>Açıklama</label><input id="prepayDescription" value="Müşteri ön ödeme tahsilatı"></div></div>
   <div class="inline-note">Tahsilat operasyon/sevkiyat durumundan bağımsızdır. Parti seçimi yalnız raporlama ilişkisi kurar.</div>
  </div>
  <div class="modal-foot"><button class="btn" onclick="closeModal('customerPrepayModal')">Kapat</button><button class="btn green" onclick="completeCustomerPrepayment()">Tahsilatı Onayla</button></div>
 </div>
</div>

<div class="modal-backdrop" id="customerReceiptEditModal">
 <div class="modal" style="max-width:720px">
  <div class="modal-head"><div><span class="eyebrow">GERÇEK MÜŞTERİ TAHSİLATI</span><h2>Tahsilatı Düzenle</h2><div class="sub" id="customerReceiptEditSub">Kasa/Banka, tutar, tarih ve açıklamayı güvenli şekilde güncelleyin.</div></div><button class="close" onclick="closeModal('customerReceiptEditModal')">×</button></div>
  <div class="modal-body">
   <div class="prepay-summary"><div><span>Mevcut Tahsilat</span><b id="creCurrentAmount">—</b></div><div><span>Kalan + Mevcut</span><b id="creMaxAmount">—</b></div><div><span>Sipariş Dövizi</span><b id="creCurrency">—</b></div></div>
   <div class="form-grid">
    <div class="field col2"><label>Kasa / Banka</label><select id="creBank" onchange="syncCustomerReceiptEditFx()"></select></div>
    <div class="field col2"><label>Tahsilat Tutarı</label><input id="creAmount" type="number" min="0.01" step="0.01"></div>
    <div class="field col2"><label>Tahsilat Tarihi</label><input id="creDate" type="date"></div>
    <div class="field col2"><label>İlgili Teslimat Partisi <small>(opsiyonel)</small></label><select id="creBatch"><option value="">Genel Sipariş Tahsilatı</option></select></div>
    <div class="field col2" id="creFxField" style="display:none"><label>Kur / Parite <small>(1 sipariş dövizi = ? banka dövizi)</small></label><input id="creFxRate" type="number" min="0.000001" step="0.000001" value="1"></div>
    <div class="field col2"><label>Açıklama</label><input id="creDescription"></div>
   </div>
   <div class="inline-note"><b>Veri bütünlüğü:</b> Değişiklik aynı transaction içinde sipariş tahsilatını, ödeme planını, müşteri carisini ve Kasa/Banka bakiyesini birlikte günceller.</div>
  </div>
  <div class="modal-foot"><button class="btn" onclick="closeModal('customerReceiptEditModal')">Vazgeç</button><button class="btn primary" id="saveCustomerReceiptEditBtn" onclick="saveCustomerReceiptEdit()">Değişiklikleri Kaydet</button></div>
 </div>
</div>

<div class="modal-backdrop" id="statusReasonModal"><div class="modal" style="max-width:620px"><div class="modal-head"><div><span class="eyebrow">DURUM DEĞİŞİKLİĞİ</span><h2 id="statusReasonTitle">Durum Nedeni</h2><div class="sub">Kaybedildi ve İptal Edildi durumlarında neden kaydı zorunludur.</div></div><button class="close" onclick="closeModal('statusReasonModal')">×</button></div><div class="modal-body"><div class="field"><label>Neden</label><select id="statusReasonPreset"><option value="Fiyat yüksek">Fiyat yüksek</option><option value="Termin uygun değil">Termin uygun değil</option><option value="Rakip kazandı">Rakip kazandı</option><option value="Müşteri iptal etti">Müşteri iptal etti</option><option value="Teknik şartlar uygun değil">Teknik şartlar uygun değil</option><option value="Diğer">Diğer</option></select></div><div class="field"><label>Açıklama</label><textarea id="statusReasonText" rows="3"></textarea></div></div><div class="modal-foot"><button class="btn" onclick="closeModal('statusReasonModal')">Vazgeç</button><button class="btn primary" onclick="confirmPendingStatusChange()">Durumu Güncelle</button></div></div></div>
<div class="modal-backdrop" id="accountCreateModal">
 <div class="modal" style="max-width:780px">
  <div class="modal-head"><div><span class="eyebrow">CARİ HESAP</span><h2 id="accountCreateTitle">Yeni Cari Ekle</h2><div class="sub" id="accountCreateSub">Müşteri, tedarikçi, resmi kurum veya özel kurum cari kartı oluşturun.</div></div><button class="close" onclick="closeModal('accountCreateModal')">×</button></div>
  <div class="modal-body">
   <div class="form-grid">
    <div class="field col2"><label>Firma / Kurum Adı</label><input id="accNewName"></div>
    <div class="field col2"><label>Cari Tipi</label><select id="accNewType"><option value="customer">Müşteri</option><option value="supplier">Tedarikçi</option><option value="customs">Gümrükçü</option><option value="freight">Nakliyeci</option><option value="service">Hizmet Sağlayıcı</option><option value="public">Resmi Kurum</option><option value="private">Özel Kurum</option></select></div>
    <div class="field col2"><label>Cari Rolü</label><select id="accNewRole"><option value="">Genel</option><option>Gümrük Müşaviri</option><option>Nakliyeci</option><option>Lojistik</option><option>Depo / Antrepo</option><option>Sigorta</option><option>Hizmet Sağlayıcı</option><option>Resmi Kurum</option><option>Diğer</option></select></div>
    <div class="field"><label>Para Birimi</label><select id="accNewCurrency"><option>EUR</option><option>USD</option><option>TRY</option></select></div>
    <div class="field"><label>Açılış Bakiyesi</label><input id="accNewBalance" type="number" step=".01" value="0"></div>
    <div class="field col2"><label>Talep / Referans</label><input id="accNewRequest"></div>
    <div class="field col2"><label>Vergi / Kurum No</label><input id="accNewTaxNo"></div>
    <div class="field col2"><label>Yetkili / İlgili Kişi</label><input id="accNewContact"></div>
    <div class="field col2"><label>Telefon</label><input id="accNewPhone"></div>
    <div class="field col2"><label>E-posta</label><input id="accNewEmail" type="email"></div>
    <div class="field col2"><label>Web Sitesi</label><input id="accNewWebsite" placeholder="www.firma.com"></div>
    <div class="field full"><label>Adres</label><textarea id="accNewAddress" rows="2"></textarea></div>
    <div class="field col2"><label>Ülke</label><input id="accNewCountry"></div>
    <div class="field col2"><label>Cari Vade Tarihi</label><input id="accNewDueDate" type="date"><small class="sub">Dashboard yaklaşan tahsilat/ödeme aksiyonlarında kullanılır.</small></div>
    <div class="field full"><label>Cari Açıklaması</label><textarea id="accNewDescription" rows="3" placeholder="Firma/kurum hakkında açıklama..."></textarea></div>
    <div class="field full"><label>Not</label><textarea id="accNewNote"></textarea></div>
   </div>
  </div>
  <div class="modal-foot"><button class="btn" onclick="closeModal('accountCreateModal')">Kapat</button><button class="btn primary" onclick="saveNewAccount()">Cariyi Kaydet</button></div>
 </div>
</div>

<div class="modal-backdrop" id="accountEditModal">
 <div class="modal" style="max-width:760px">
  <div class="modal-head"><div><span class="eyebrow">CARİ KART</span><h2>Cari Kartı Düzenle</h2><div class="sub">Firma bilgileri ortak güncellenir; para birimi yalnız seçili alt cari hesabı etkiler.</div></div><button class="close" onclick="closeModal('accountEditModal')">×</button></div>
  <div class="modal-body"><div class="form-grid">
    <div class="field col2"><label>Firma / Cari Adı</label><input id="accEditName"></div>
    <div class="field col2"><label>Cari Tipi</label><select id="accEditType"><option value="customer">Müşteri</option><option value="supplier">Tedarikçi</option><option value="customs">Gümrükçü</option><option value="freight">Nakliyeci</option><option value="service">Hizmet Sağlayıcı</option><option value="public">Resmi Kurum</option><option value="private">Özel Kurum</option></select></div>
    <div class="field col2"><label>Cari Rolü</label><select id="accEditRole"><option value="">Genel</option><option>Gümrük Müşaviri</option><option>Nakliyeci</option><option>Lojistik</option><option>Depo / Antrepo</option><option>Sigorta</option><option>Hizmet Sağlayıcı</option><option>Resmi Kurum</option><option>Diğer</option></select></div>
    <div class="field col2"><label>Para Birimi</label><select id="accEditCurrency"><option value="EUR">EUR</option><option value="USD">USD</option><option value="TRY">TRY</option></select><small class="sub">Seçili alt cari hesabın para birimini değiştirir; bakiye otomatik kur çevrimine tabi tutulmaz.</small></div>
    <div class="field col2"><label>Talep / Referans</label><input id="accEditRequest"></div>
    <div class="field col2"><label>Vergi / Kurum No</label><input id="accEditTaxNo"></div>
    <div class="field col2"><label>Yetkili / İlgili Kişi</label><input id="accEditContact"></div>
    <div class="field col2"><label>Telefon</label><input id="accEditPhone"></div>
    <div class="field col2"><label>E-posta</label><input id="accEditEmail" type="email"></div>
    <div class="field col2"><label>Web Sitesi</label><input id="accEditWebsite"></div>
    <div class="field full"><label>Adres</label><textarea id="accEditAddress" rows="2"></textarea></div>
    <div class="field col2"><label>Ülke</label><input id="accEditCountry"></div>
    <div class="field col2"><label>Cari Vade Tarihi</label><input id="accEditDueDate" type="date"><small class="sub">Yaklaşan cari ödeme/tahsilat uyarısı.</small></div>
    <div class="field full"><label>Cari Açıklaması</label><textarea id="accEditDescription" rows="3"></textarea></div>
    <div class="field full"><label>Not</label><input id="accEditNote" placeholder="Opsiyonel"></div>
  </div></div>
  <div class="modal-foot"><button class="btn" onclick="closeModal('accountEditModal')">Kapat</button><button class="btn primary" onclick="saveAccountEdit()">Güncelle</button></div>
 </div>
</div>

<div class="modal-backdrop" id="transferModal">
 <div class="modal" style="max-width:760px">
  <div class="modal-head"><div><span class="eyebrow">KASA & BANKA</span><h2>Hesaplar Arası Virman</h2><div class="sub">Kaynak hesaptan hedef hesaba bakiye aktarın.</div></div><button class="close" onclick="closeModal('transferModal')">×</button></div>
  <div class="modal-body">
    <div class="form-grid">
      <div class="field col2"><label>Kaynak Hesap</label><select id="transferFrom" onchange="syncTransferInfo()"></select></div>
      <div class="field col2"><label>Hedef Hesap</label><select id="transferTo" onchange="syncTransferInfo()"></select></div>
      <div class="field col2"><label>Kaynak Tutar</label><input id="transferAmount" type="number" min="0" step=".01" oninput="calculateTransferTarget()"></div>
      <div class="field col2" id="transferFxField"><label>Manuel Kur / Parite</label><input id="transferFxRate" type="number" min="0" step="0.000001" value="1" oninput="calculateTransferTarget()"><small class="sub" id="transferFxHint">Aynı para biriminde 1,000000</small></div>
      <div class="field col2"><label>Hedef Hesaba Geçecek Tutar</label><input id="transferTargetAmount" type="number" step=".01" readonly></div>
      <div class="field col2"><label>Açıklama</label><input id="transferNote" value="Hesaplar arası virman"></div>
    </div>
    <div class="inline-note" id="transferInfo" style="margin-top:12px"></div>
  </div>
  <div class="modal-foot"><button class="btn" onclick="closeModal('transferModal')">Kapat</button><button class="btn primary" onclick="completeTransfer()">Virmanı Gerçekleştir</button></div>
 </div>
</div>

<div class="modal-backdrop" id="cashAccountModal">
 <div class="modal" style="max-width:720px">
  <div class="modal-head"><div><span class="eyebrow">KASA / BANKA</span><h2 id="cashAccountModalTitle">Yeni Kasa / Banka</h2><div class="sub">Hesap bilgilerini, dövizi ve IBAN'ı yönetin.</div></div><button class="close" onclick="closeModal('cashAccountModal')">×</button></div>
  <div class="modal-body"><div class="form-grid"><div class="field col2"><label>Hesap Adı</label><input id="caName"></div><div class="field"><label>Tür</label><select id="caType" onchange="toggleBankFields()"><option>Banka</option><option>Kasa</option></select></div><div class="field"><label>Para Birimi</label><select id="caCurrency"><option>EUR</option><option>USD</option><option>TRY</option></select></div><div class="field col2 bank-only"><label>Banka Adı</label><input id="caBankName" placeholder="Örn. Türkiye İş Bankası A.Ş."></div><div class="field col2 bank-only"><label>Şube / Branch</label><input id="caBranch" placeholder="Örn. Levent Ticari Şube"></div><div class="field col2 bank-only"><label>SWIFT / BIC Code</label><input id="caSwift" maxlength="11" placeholder="Örn. ISBKTRIS" oninput="this.value=this.value.toUpperCase()"></div><div class="field col2"><label>IBAN / Hesap No</label><input id="caIban"></div><div class="field col2"><label>Açılış / Güncel Bakiye</label><input id="caBalance" type="text" inputmode="decimal"><small class="sub">Mevcut hesapta salt okunur; değişiklik için Bakiye Düzelt kullanılır.</small></div></div></div>
  <div class="modal-foot"><button class="btn" onclick="closeModal('cashAccountModal')">Kapat</button><button class="btn primary" onclick="saveCashAccount()">Kaydet</button></div>
 </div>
</div>

<div class="modal-backdrop" id="cashBalanceAdjustModal">
 <div class="modal" style="max-width:620px">
  <div class="modal-head"><div><span class="eyebrow">KASA / BANKA</span><h2>Bakiye Düzelt</h2><div class="sub" id="cashBalanceAdjustSub">Hesabın gerçek bakiyesini journal kaydıyla düzeltin.</div></div><button class="close" onclick="closeModal('cashBalanceAdjustModal')">×</button></div>
  <div class="modal-body">
   <div class="form-grid">
    <div class="field col2"><label>Hesap</label><input id="cbaAccount" readonly></div>
    <div class="field col2"><label>Mevcut Bakiye</label><input id="cbaCurrent" readonly></div>
    <div class="field col2"><label>Yeni Gerçek Bakiye</label><input id="cbaTarget" type="text" inputmode="decimal" autocomplete="off" placeholder="0,00"></div>
    <div class="field col2"><label>Düzeltme Açıklaması</label><input id="cbaReason" value="Sayım / banka mutabakatı düzeltmesi" placeholder="Açıklama zorunlu"></div>
   </div>
   <div class="inline-note" id="cbaInfo" style="margin-top:12px">Düzeltme, seçili hesapta giriş/çıkış hareketi ve finans journal kaydı oluşturur.</div>
  </div>
  <div class="modal-foot"><button class="btn" onclick="closeModal('cashBalanceAdjustModal')">Vazgeç</button><button class="btn primary" onclick="saveCashBalanceAdjustment()">Bakiyeyi Düzelt</button></div>
 </div>
</div>

<div class="modal-backdrop" id="tagModal">
 <div class="modal" style="max-width:640px">
  <div class="modal-head"><div><span class="eyebrow">ETİKET YÖNETİMİ</span><h2 id="tagModalTitle">Etiket Yönetimi</h2><div class="sub" id="tagModalSub">Merkezi etiket kataloğundan seçim yapın veya yeni etiket oluşturun.</div></div><button class="close" onclick="closeModal('tagModal')">×</button></div>
  <div class="modal-body"><div class="tag-manager-groups" id="tagManagerList"></div><div class="doc-company-editor"><b>Yeni / Mevcut Etiket</b><div class="tag-create-grid" style="margin-top:8px"><div class="field"><label>Etiket Adı</label><input id="newTagName" list="existingTagNames" placeholder="Etiket adı"><datalist id="existingTagNames"></datalist></div><div class="field"><label>Kategori</label><input id="newTagCategory" list="existingTagCategories" placeholder="Örn. Öncelik"><datalist id="existingTagCategories"></datalist></div><div class="field"><label>Renk</label><input id="newTagColor" type="color" value="#246bfd"></div><button class="btn primary" onclick="createTag()">Etiketi Oluştur ve Seç</button></div></div></div>
  <div class="modal-foot"><button class="btn" onclick="closeModal('tagModal')">Kapat</button><button class="btn primary" onclick="saveTags()">Uygula</button></div>
 </div>
</div>

<div class="modal-backdrop" id="supplierDirectoryModal">
 <div class="modal" style="max-width:720px">
  <div class="modal-head"><div><span class="eyebrow">TEDARİKÇİ KARTI</span><h2>Yeni Tedarikçi Ekle</h2><div class="sub">Kaydedilen tedarikçi teklif formunda seçilebilir hale gelir.</div></div><button class="close" onclick="closeModal('supplierDirectoryModal')">×</button></div>
  <div class="modal-body"><div class="form-grid">
   <div class="field col2"><label>Firma Adı</label><input id="sdName"></div>
   <div class="field col2"><label>Yetkili Kişi</label><input id="sdContact"></div>
   <div class="field col2"><label>E-posta</label><input id="sdEmail" type="email"></div>
   <div class="field col2"><label>Telefon</label><input id="sdPhone"></div>
   <div class="field col2"><label>Ülke</label><input id="sdCountry" value="Türkiye"></div>
   <div class="field col2"><label>Not</label><input id="sdNote"></div>
  </div></div>
  <div class="modal-foot"><button class="btn" onclick="closeModal('supplierDirectoryModal')">Kapat</button><button class="btn primary" onclick="saveSupplierDirectory()">Tedarikçiyi Kaydet</button></div>
 </div>
</div>

<div class="modal-backdrop" id="documentViewModal">
 <div class="modal" style="width:min(1040px,100%)">
  <div class="modal-head"><div><span class="eyebrow">EVRAK ÖNİZLEME</span><h2 id="documentViewTitle">Belge</h2><div class="sub" id="documentViewSub"></div></div><button class="close" onclick="closeModal('documentViewModal')">×</button></div>
  <div class="modal-body" style="padding:0"><div class="doc-view-shell"><div class="doc-view-page" id="documentViewBody"></div></div></div>
  <div class="modal-foot"><button class="btn" onclick="closeModal('documentViewModal')">Kapat</button><button class="btn" onclick="editViewedDocument()">Düzenle</button><button class="btn primary" onclick="printViewedDocument()">Yazdır / PDF</button></div>
 </div>
</div>

<div class="modal-backdrop" id="supplierModal">
 <div class="modal" style="max-width:980px">
  <div class="modal-head"><div><span class="eyebrow">TEDARİKÇİ TEKLİFİ</span><h2 id="supplierModalTitle">Yeni Tedarikçi Teklifi</h2><div class="sub">Talep kalemleri otomatik listelenir; ürün adını ayrıca “serbest kalem” olarak eklemeniz gerekmez.</div></div><button class="close" onclick="closeModal('supplierModal')">×</button></div>
  <div class="modal-body">
    <div class="form-grid">
      <div class="field col2"><label>Tedarikçi</label><div class="supplier-picker"><select id="sqSupplier" onchange="syncSupplierQuoteCurrencyFromAccount(this.value)"><option value="">Tedarikçi seçin…</option></select><button type="button" class="btn sm" id="sqQuickSupplierToggleBtn" onclick="toggleSupplierQuickAdd()">+ Yeni Tedarikçi Ekle</button></div></div>
      <div class="field full supplier-quick-add-wrap" id="supplierQuickAddPanel" style="display:none">
        <div class="supplier-quick-add-card">
          <div class="supplier-quick-add-head"><div><b>Hızlı Tedarikçi Ekle</b><small>Teklif penceresinden çıkmadan tedarikçi kartını oluşturun.</small></div><button type="button" class="btn sm" onclick="toggleSupplierQuickAdd(false)">Kapat</button></div>
          <div class="form-grid">
            <div class="field col2"><label>Firma Adı *</label><input id="sqNewSupplierName" autocomplete="organization" placeholder="Tedarikçi firma adı"></div>
            <div class="field col2"><label>Yetkili Kişi</label><input id="sqNewSupplierContact" autocomplete="name"></div>
            <div class="field col2"><label>E-posta</label><input id="sqNewSupplierEmail" type="email" autocomplete="email"></div>
            <div class="field col2"><label>Telefon</label><input id="sqNewSupplierPhone" autocomplete="tel"></div>
            <div class="field col2"><label>Ülke</label><input id="sqNewSupplierCountry" value="Türkiye"></div>
            <div class="field col2"><label>Not</label><input id="sqNewSupplierNote"></div><div class="field col2"><label><input id="sqNewSupplierCreateAccount" type="checkbox"> Tedarikçi carisini de oluştur</label><small>Cari/Finans yazma yetkisi gerektirir. Para birimi teklif para biriminden alınır.</small></div>
          </div>
          <div class="supplier-quick-add-actions"><button type="button" class="btn primary" id="sqQuickSupplierSaveBtn" onclick="saveSupplierQuickAdd()">Tedarikçiyi Ekle ve Seç</button></div>
        </div>
      </div>
      <div class="field"><label>Teklif Ref.</label><input id="sqRef" placeholder="SUP-REF-001"></div>
      <div class="field"><label>Geçerlilik</label><input id="sqValidity" value="15 gün"></div>
      <div class="field col2"><label>Ödeme</label><select id="sqPayment"><option>T/T 30/70</option><option>T/T 50/50</option><option>Peşin / T/T</option><option>L/C</option><option>30 Gün Vadeli</option></select></div>
      <div class="field"><label>Teklif Para Birimi</label><select id="sqCurrency" onchange="renderSupplierQuoteFxPreview()"><option>TRY</option><option>EUR</option><option>USD</option></select><small>Tedarikçi carisi mevcutsa para birimi otomatik önerilir.</small></div>
      <div class="field"><label>Termin (gün)</label><input type="number" id="sqDelivery" value="30"></div>
      <div class="field"><label id="sqFreightLabel">Navlun</label><input type="text" inputmode="decimal" id="sqFreight" value="0" oninput="renderSupplierQuoteFxPreview()"></div>
      <div class="field col2"><label>Kur Bilgisi</label><div class="supplier-fx-preview" id="sqFxPreview"></div></div>
      <div class="field full"><label>Teklif Açıklaması</label><textarea id="sqDescription" rows="3" placeholder="Teklife özel teknik/ticari açıklamalar..."></textarea></div>
    </div>
    <div class="section-title"><b>Talep Kalemleri</b><span class="badge b-blue">Seçili Talep</span></div>
    <div class="supplier-editor-lines" id="supplierOfferEditor"></div>
  </div>
  <div class="modal-foot"><button class="btn" onclick="closeModal('supplierModal')">Kapat</button><button class="btn primary" onclick="saveSupplierQuote()">Teklifi Kaydet</button></div>
 </div>
</div>




<div class="modal-backdrop" id="comparisonExpenseRealizeModal">
 <div class="modal" style="max-width:760px">
  <div class="modal-head">
   <div><span class="eyebrow">GERÇEK GİDER KAYDI</span><h2>Operasyon Giderini Gerçekleştir</h2><div class="sub">“Ödeme Yap” yalnız bu ekranı açar. Cari ve ödeme yapılacak Kasa/Banka hesabını seçip ayrıca onayladığınızda finans hareketi oluşur.</div></div>
   <button class="close" onclick="closeModal('comparisonExpenseRealizeModal')">×</button>
  </div>
  <div class="modal-body">
   <input type="hidden" id="cerRequestId">
   <div class="form-grid">
    <div class="field col2"><label>Gider Türü</label><select id="cerCategory" onchange="syncComparisonExpenseRealizeForm()"></select></div>
    <div class="field col2" id="cerCustomCategoryWrap" style="display:none"><label>Diğer Gider Türü</label><input id="cerCustomCategory" placeholder="Örn. Liman masrafı, sigorta, antrepo..." oninput="syncComparisonExpenseRealizeForm()"><small class="sub">Gider adını manuel olarak yazabilirsiniz.</small></div>
    <div class="field"><label>Gerçek Gider Tutarı</label><input id="cerAmount" type="text" inputmode="decimal" oninput="syncComparisonExpensePaymentPreview()"></div>
    <div class="field"><label>Gerçek Gider Para Birimi</label><select id="cerCurrency" onchange="refreshComparisonExpensePartySelect();syncComparisonExpenseRealizeBanks();syncComparisonExpensePaymentPreview()"><option value="TRY">TL / TRY</option><option value="EUR">EUR</option><option value="USD">USD</option></select></div>
    <div class="field"><label>Operasyon Raporlama Para Birimi</label><input id="cerReportCurrency" disabled></div>
    <div class="field"><label>Kur Kullanımı</label><select id="cerFxMode" onchange="syncComparisonExpenseFxMode()"><option value="tcmb">TCMB / Tarihsel Kur</option><option value="manual">Manuel / Banka Kuru</option></select></div>
    <div class="field col2" id="cerManualRateWrap" style="display:none"><label id="cerManualRateLabel">Manuel Kur</label><input id="cerManualRate" type="number" min="0" step="0.000001" oninput="syncComparisonExpensePaymentPreview()"><small class="sub">Gerçek banka/efektif işlem kuru farklıysa kullanın.</small></div>
    <div class="field col2"><label>Cari Hesap / Hizmet Sağlayıcı</label><div class="inline-select-add"><select id="cerParty" onchange="syncComparisonExpenseRealizeBanks()"></select><button class="btn sm" type="button" onclick="openCostServiceAccountCreateFromModal()">+ Ekle</button></div><small class="sub">Cari, gerçek giderin para biriminde otomatik alt hesap açabilir.</small></div>
    <div class="field col2"><label>Ödeme Yapılacak Kasa / Banka</label><select id="cerBank" onchange="syncComparisonExpenseFxMode()"></select><small class="sub">TL / EUR / USD hesaplardan istediğinizi seçebilirsiniz.</small></div>
    <div class="field col2"><label>Finans Özeti</label><div class="inline-note" id="cerPaymentPreview">Kasa/Banka seçildiğinde gerçek ödeme ve operasyon karşılığı hesaplanır.</div></div>
    <div class="field"><label>Ödeme Tarihi</label><input id="cerDate" type="date" onchange="syncComparisonExpensePaymentPreview(true)"></div>
    <div class="field"><label>Fatura / Belge No</label><input id="cerInvoiceNo"></div>
    <div class="field"><label>Fatura / Belge Tarihi</label><input id="cerInvoiceDate" type="date"></div>
    <div class="field"><label>Son Ödeme / Vade Tarihi</label><input id="cerDueDate" type="date"></div>
    <div class="field col2"><label>Bağlı Evrak</label><div class="inline-select-add"><select id="cerAttachment"><option value="">Evrak seçilmedi</option></select><button class="btn sm" type="button" onclick="openExpenseAttachmentUpload()">+ Evrak Yükle</button></div></div>
    <div class="field col2"><label>Referans</label><input id="cerRef"></div>
    <div class="field full"><label>Açıklama</label><textarea id="cerDescription" rows="3"></textarea></div>
    <div class="field full"><div class="inline-note" id="cerInfo"></div></div>
   </div>
  </div>
  <div class="modal-foot">
   <button class="btn" onclick="closeModal('comparisonExpenseRealizeModal')">İptal</button>
   <button class="btn primary" onclick="saveRealizedComparisonExpense()">Ödemeyi Onayla ve Gerçekleştir</button>
  </div>
 </div>
</div>

<div class="modal-backdrop" id="attachmentViewerModal">
 <div class="modal attachment-viewer-modal">
  <div class="modal-head">
   <div><span class="eyebrow">DOKÜMAN GÖRÜNTÜLE</span><h2 id="attachmentViewerTitle">Doküman</h2><div class="sub" id="attachmentViewerMeta"></div></div>
   <button class="close" onclick="closeAttachmentViewer()">×</button>
  </div>
  <div class="modal-body attachment-viewer-body" id="attachmentViewerBody">
   <div class="empty">Doküman seçilmedi.</div>
  </div>
  <div class="modal-foot">
   <a class="btn" id="attachmentViewerOpen" href="#" target="_blank" rel="noopener">Yeni Sekmede Aç</a>
   <button class="btn" onclick="closeAttachmentViewer()">Kapat</button>
  </div>
 </div>
</div>

<div class="modal-backdrop" id="deliveryBatchModal">
 <div class="modal" style="max-width:900px">
  <div class="modal-head"><div><span class="eyebrow">PARTİLİ TESLİMAT</span><h2 id="deliveryBatchTitle">Yeni Teslimat Partisi</h2><div class="sub">Kalan sipariş adetlerinden yeni bir teslimat partisi planlayın. Ayrılan adetler başka partide tekrar kullanılamaz.</div></div><button class="close" onclick="closeModal('deliveryBatchModal')">×</button></div>
  <div class="modal-body">
   <div class="form-grid">
 <div class="field col2"><label>Planlanan Teslim Tarihi</label><input id="batchPlannedDate" type="date"></div>
 <div class="field col2"><label>Açıklama</label><input id="batchDescription" placeholder="1. parti / parsiyel sevkiyat vb."></div>
 <div class="field col2"><label>İrsaliye / Sevk Referansı</label><input id="batchShipmentRef" placeholder="İrsaliye, CMR, AWB, BL no"></div>
 <div class="field col2"><label>Taşıyıcı / Lojistik</label><input id="batchCarrier" placeholder="Nakliyeci / forwarder"></div>
 <div class="field col2"><label>Araç / Konteyner</label><input id="batchVehicleContainer" placeholder="Plaka veya konteyner no"></div>
 <div class="field col2"><label>Teslim Alan</label><input id="batchReceiver" placeholder="Yetkili / teslim alan"></div>
</div>
   <div class="section-title"><b>Teslim Edilecek Kalemler</b><span class="badge b-blue">Kalan adet üzerinden</span></div>
   <div class="batch-create-items" id="batchCreateItems"></div>
   <div class="inline-note" id="batchCreateInfo" style="margin-top:10px"></div>
  </div>
  <div class="modal-foot"><button class="btn" onclick="closeModal('deliveryBatchModal')">Vazgeç</button><button class="btn primary" id="saveDeliveryBatchBtn" onclick="saveDeliveryBatch()">Partiyi Oluştur</button></div>
 </div>
</div>

<div class="modal-backdrop" id="deliveryFinanceModal">
 <div class="modal" style="max-width:700px">
  <div class="modal-head"><div><span class="eyebrow">PARTİ FİNANSI</span><h2 id="deliveryFinanceTitle">Parti Tahsilat / Ödeme</h2><div class="sub" id="deliveryFinanceSub">Partiye bağlı finans hareketi.</div></div><button class="close" onclick="closeModal('deliveryFinanceModal')">×</button></div>
  <div class="modal-body">
   <div class="quote-kpis"><div class="quote-kpi"><span>Parti Tutarı</span><b id="deliveryFinanceTotal">—</b></div><div class="quote-kpi"><span>İşlenen</span><b id="deliveryFinancePaid">—</b></div><div class="quote-kpi"><span>Kalan</span><b id="deliveryFinanceDue">—</b></div><div class="quote-kpi"><span>Taraf</span><b id="deliveryFinanceParty">—</b></div></div>
   <div class="form-grid">
    <div class="field col2"><label>Kasa / Banka</label><select id="deliveryFinanceBank" onchange="syncDeliveryFinanceFx()"></select></div>
    <div class="field col2"><label>Tutar</label><input id="deliveryFinanceAmount" type="number" min="0" step=".01"></div>
    <div class="field col2" id="deliveryFinanceFxField" style="display:none"><label>Kur / Parite <small>(hedef banka para birimi / sipariş para birimi)</small></label><input id="deliveryFinanceFxRate" type="number" min=".000001" step=".000001" value="1"></div>
    <div class="field col2"><label>Açıklama</label><input id="deliveryFinanceDescription"></div>
   </div>
   <div class="inline-note">Aynı işlem ağ nedeniyle tekrar gönderilse bile idempotency kontrolü ikinci kez finans kaydı oluşturmaz.</div>
  </div>
  <div class="modal-foot"><button class="btn" onclick="closeModal('deliveryFinanceModal')">Vazgeç</button><button class="btn green" id="deliveryFinanceSaveBtn" onclick="completeDeliveryFinance()">Kaydet</button></div>
 </div>
</div>

<div class="modal-backdrop" id="paymentModal">
 <div class="modal" style="max-width:680px">
  <div class="modal-head"><div><span class="eyebrow">TEDARİKÇİ ÖDEMESİ</span><h2 id="payTitle">Tedarikçi Ödemesi</h2><div class="sub" id="paySub">Açık sipariş seçildiğinde bilgiler gelir.</div></div><button class="close" onclick="closeModal('paymentModal')">×</button></div>
  <div class="modal-body"><div class="field" id="paySupplierField" style="display:none;margin-bottom:12px"><label>Tedarikçi / PO</label><select id="paySupplierSelect" onchange="selectOrderPaymentSupplier(this.value)"></select></div><div class="quote-kpis"><div class="quote-kpi"><span>Sipariş Toplamı</span><b id="payTotal">—</b></div><div class="quote-kpi"><span>Daha Önce Ödenen</span><b id="payPaid">—</b></div><div class="quote-kpi"><span>Kalan Borç</span><b id="payDue">—</b></div><div class="quote-kpi"><span>Ödeme Şartı</span><b id="payTerms" style="font-size:12px">—</b></div></div><div class="form-grid"><div class="field"><label>Yüzde Bazı</label><select id="payBasis" onchange="syncSupplierPayFromPercent()"><option value="total">PO Toplamının %X’i</option><option value="remaining">Kalan Borcun %X’i</option></select></div><div class="field"><label>Ödeme %</label><input id="payPercent" type="number" min="0" max="100" step=".01" value="50" oninput="syncSupplierPayFromPercent()"></div><div class="field col2"><label>Kasa / Banka</label><select id="payBank"></select></div><div class="field col2"><label>Ödenecek Tutar</label><input id="payAmount" type="number" min="0" step="0.01" oninput="syncSupplierPayFromAmount()"></div><div class="field col2"><label>İlgili Teslimat Partisi <small>(opsiyonel)</small></label><select id="payBatch"><option value="">Genel Sipariş Ödemesi</option></select></div><div class="field col2"><label>Referans</label><input id="payRef"></div><div class="field col2"><label>Açıklama</label><input id="payDesc" value="Tedarikçiye ödeme"></div></div><div class="inline-note" style="margin-top:12px">Ödeme operasyon/sevkiyat durumundan bağımsızdır. Parti seçimi yalnız raporlama ilişkisi kurar.</div></div>
  <div class="modal-foot"><button class="btn" onclick="closeModal('paymentModal')">Vazgeç</button><button class="btn green" onclick="completePayment()">Ödemeyi Yap ve Cariye İşle</button></div>
 </div>
</div>

<div class="modal-backdrop" id="financeMovementEditModal"><div class="modal"><div class="modal-head"><div><span class="eyebrow">FİNANS HAREKETİ</span><h2>Hareketi Düzenle</h2><div class="sub" id="financeMovementEditSub">Finansal tutarı değiştirmeden tarih, referans ve açıklamayı düzenleyin.</div></div><button class="close" onclick="closeModal('financeMovementEditModal')">×</button></div><div class="modal-body"><div class="form-grid"><div class="field"><label>Tarih</label><input id="fmeDate" type="date"></div><div class="field"><label>Referans / Talep</label><input id="fmeRef"></div><div class="field col2"><label>Açıklama</label><input id="fmeDesc"></div><div class="field col2"><label>Finansal Tutar</label><input id="fmeAmount" disabled></div></div><div class="inline-note">Bağlı ödeme/tahsilat tutarı ve dövizi finans bütünlüğü için burada değiştirilemez. Yanlış tutarlı kayıt Sil ile kaldırılıp doğru tutarla yeniden girilmelidir.</div></div><div class="modal-foot"><button class="btn" onclick="closeModal('financeMovementEditModal')">Vazgeç</button><button class="btn primary" onclick="saveFinanceMovementEdit()">Değişikliği Kaydet</button></div></div></div>
<div class="modal-backdrop" id="accountEntryEditModal">
 <div class="modal" style="max-width:720px">
  <div class="modal-head"><div><span class="eyebrow">CARİ HESAP EKSTRESİ</span><h2>Cari Hareketi Düzenle</h2><div class="sub" id="accountEntryEditSub">Yanlış girilmiş borç / alacak hareketini düzeltin.</div></div><button class="close" onclick="closeModal('accountEntryEditModal')">×</button></div>
  <div class="form-grid">
   <div class="field"><label>Tarih</label><input id="accountEntryEditDate" type="date"></div>
   <div class="field"><label>İşlem Türü</label><select id="accountEntryEditDirection"><option value="debit">Borç</option><option value="credit">Alacak</option></select></div>
   <div class="field"><label>Tutar</label><input id="accountEntryEditAmount" type="text" inputmode="decimal" placeholder="0,00"></div>
   <div class="field"><label>Referans</label><input id="accountEntryEditRef" placeholder="RFQ / Fatura / Ödeme No"></div>
   <div class="field full"><label>Açıklama</label><textarea id="accountEntryEditDesc" rows="3"></textarea></div>
  </div>
  <div class="inline-note" style="margin-top:10px"><b>Finans bütünlüğü:</b> Manuel/bağımsız cari hareketleri düzenlenebilir veya silinebilir. Kasa/Banka, sipariş, tahsilat veya ödeme ile bağlantılı kayıtlar doğrudan değiştirilmez; ilgili kaynak işlem güvenli şekilde iptal edilir.</div>
  <div class="modal-foot"><button class="btn" onclick="closeModal('accountEntryEditModal')">İptal</button><button class="btn primary" onclick="saveAccountEntryEdit()">Değişikliği Kaydet</button></div>
 </div>
</div>

<div class="modal-backdrop" id="cashModal">
 <div class="modal">
  <div class="modal-head"><div><span class="eyebrow">FİNANS HAREKETİ</span><h2 id="cashModalTitle">Tahsilat / Ödeme</h2><div class="sub">Talep, cari ve kasa aynı işlemde bağlanır.</div></div><button class="close" onclick="closeModal('cashModal')">×</button></div>
  <div class="modal-body"><div class="form-grid">
   <div class="field col2"><label>Kasa / Banka</label><select id="cashBank" onchange="syncCashFormCurrency()"></select></div>
   <div class="field col2"><label>Talep / İhale</label><select id="cashRequest" onchange="syncCashPoOptions()"></select></div>
   <div class="field col2"><label>Cari</label><select id="cashParty" onchange="syncCashFormCurrency();syncCashPoOptions()"></select></div>
   <div class="field col2"><label>İşlem</label><select id="cashType" onchange="syncCashPoOptions()"><option value="receipt">Para Girişi / Tahsilat</option><option value="payment">Para Çıkışı / Ödeme</option></select></div>
   <div class="field col2" id="cashPoField" style="display:none"><label>Bağlı Tedarikçi Siparişi (PO)</label><select id="cashPo"><option value="">Genel Cari Ödemesi</option></select></div>
   <div class="field col2"><label>Tutar</label><input id="cashAmount" type="number" min="0" step="0.01"></div>
   <div class="field col2"><label>İşlem Tarihi</label><input id="cashDate" type="date"></div>
   <div class="field col2"><label>Referans</label><input id="cashRef"></div>
   <div class="field col2"><label>Açıklama</label><input id="cashDesc" placeholder="Tahsilat / ödeme açıklaması"></div>
  </div><div class="inline-note" id="cashFormInfo" style="margin-top:10px"></div></div>
  <div class="modal-foot"><button class="btn" onclick="closeModal('cashModal')">Kapat</button><button class="btn primary" onclick="saveCash()">Kasaya ve Cariye Birlikte İşle</button></div>
 </div>
</div>


<div class="modal-backdrop" id="generalReportModal">
 <div class="modal" style="width:min(1180px,100%)">
  <div class="modal-head">
   <div><span class="eyebrow">RAPOR / EKSTRE</span><h2 id="generalReportTitle">Genel Ekstre</h2><div class="sub" id="generalReportSub">Filtreleyin, yazdırın veya CSV dışa aktarın.</div></div>
   <button class="close" onclick="closeModal('generalReportModal')">×</button>
  </div>
  <div class="modal-body">
   <div class="report-toolbar">
    <div class="field" id="reportDateFromField"><label>Başlangıç Tarihi</label><input id="reportDateFrom" type="date" onchange="renderGeneralReport()"></div>
    <div class="field" id="reportDateToField"><label>Bitiş Tarihi</label><input id="reportDateTo" type="date" onchange="renderGeneralReport()"></div>
    <div class="field"><label>Ara</label><input id="reportSearch" placeholder="Firma, talep no, ülke, açıklama…" oninput="renderGeneralReport()"></div>
    <div class="field"><label id="reportSecondaryLabel">Filtre</label><select id="reportSecondaryFilter" onchange="renderGeneralReport()"></select></div>
   </div>
   <div class="inline-note" id="reportExtraOptions"></div><details class="panel" style="margin:8px 0"><summary style="cursor:pointer;font-weight:800">Rapor Sütunlarını Seç</summary><div class="tags" id="reportColumnChooser" style="margin-top:8px"></div></details>
   <div class="report-summary" id="generalReportSummary"></div>
   <div class="report-preview" id="generalReportPreview"></div>
  </div>
  <div class="modal-foot">
   <button class="btn" onclick="closeModal('generalReportModal')">Kapat</button>
   <button class="btn" onclick="exportGeneralReportCsv()">CSV</button><button class="btn" onclick="exportGeneralReportXlsx()">Excel (.xlsx)</button>
   <button class="btn primary" onclick="printGeneralReport()">Yazdır / PDF</button>
  </div>
 </div>
</div>

<div class="modal-backdrop" id="userModal"><div class="modal"><div class="modal-head"><div><span class="eyebrow">KULLANICI YÖNETİMİ</span><h2 id="userModalTitle">Yeni Kullanıcı</h2><div class="sub">Rol, departman ve hesap durumunu yönetin.</div></div><button class="close" onclick="closeModal('userModal')">×</button></div><div class="modal-body"><div class="form-grid"><div class="field col2"><label>Ad Soyad</label><input id="usrName"></div><div class="field col2"><label>E-posta</label><input id="usrEmail" type="email"></div><div class="field col2"><label>Rol</label><select id="usrRole"></select></div><div class="field col2"><label>Departman</label><select id="usrDepartment"><option>Yönetim</option><option>Satış</option><option>Satın Alma</option><option>Finans</option><option>Operasyon</option><option>Görüntüleyici</option></select></div><div class="field col2"><label>Durum</label><select id="usrStatus"><option value="active">Aktif</option><option value="passive">Pasif</option></select></div><div class="field col2"><label>Kullanıcı Fotoğrafı</label><input id="usrPhoto" type="file" accept="image/*" onchange="loadUserPhoto(this)"></div><div class="field col2"><label>Yetki Notu</label><input id="usrNote"></div><div class="field col2"><label id="usrPasswordLabel">Şifre (yeni kullanıcıda zorunlu)</label><input id="usrPassword" type="password" minlength="8" autocomplete="new-password"></div></div></div><div class="modal-foot"><button class="btn" onclick="closeModal('userModal')">Kapat</button><button class="btn primary" onclick="saveUser()">Kaydet</button></div></div></div><div class="modal-backdrop" id="quickModal">
 <div class="modal" style="max-width:560px">
  <div class="modal-head"><div><span class="eyebrow">HIZLI İŞLEM</span><h2>Ne yapmak istiyorsunuz?</h2></div><button class="close" onclick="closeModal('quickModal')">×</button></div>
  <div class="modal-body"><div class="grid2"><button class="btn primary" onclick="closeModal('quickModal');openRequestModal()">+ Yeni Talep</button><button class="btn" onclick="closeModal('quickModal');go('cash');openCashModal()">Tahsilat / Ödeme</button><button class="btn" onclick="closeModal('quickModal');openLatestComparison()">Teklif Kıyasla</button><button class="btn" onclick="closeModal('quickModal');go('docs')">Ticari Evrak</button></div></div>
 </div>
</div>

<div class="modal-backdrop" id="guaranteeModal">
 <div class="modal guarantee-modalbox">
  <div class="modal-head"><div><span class="eyebrow">TEMİNAT KAYDI</span><h2 id="guaranteeModalTitle">Yeni Teminat</h2><div class="sub">Teminat, banka/kasa ve vade bilgilerini tek kayıtta yönetin.</div></div><button class="close" onclick="closeModal('guaranteeModal')">×</button></div>
  <div class="modal-body">
   <input type="hidden" id="guaranteeId" value="0">
   <div class="form-grid guarantee-formgrid">
    <div class="field"><label>Talep / İhale</label><select id="guaranteeRequest" onchange="guaranteeRequestChanged()"><option value="">Talep seçin</option></select></div>
    <div class="field"><label>Müşteri / Kurum</label><select id="guaranteeParty"><option value="">Kurum seçin</option></select></div>
    <div class="field"><label>Teminat Türü</label><select id="guaranteeType"><option>Geçici Teminat</option><option>Kesin Teminat</option><option>Ek Kesin Teminat</option><option>Avans Teminat</option><option>Banka Teminat Mektubu</option><option>Diğer Teminat</option></select></div>
    <div class="field"><label>Enstrüman</label><select id="guaranteeInstrument" onchange="guaranteeInstrumentChanged()"><option value="cash">Nakit / Blokaj</option><option value="letter">Banka Teminat Mektubu</option></select></div>
    <div class="field"><label>Para Birimi</label><select id="guaranteeCurrency" onchange="guaranteeCurrencyChanged()"><option>EUR</option><option>USD</option><option>TRY</option></select></div>
    <div class="field"><label>Satış / Sözleşme Bedeli</label><input id="guaranteeSalesAmount" inputmode="decimal" value="0" oninput="renderGuaranteeCalc()"></div>
    <div class="field"><label>Teminat Oranı %</label><input id="guaranteeRate" inputmode="decimal" value="0" oninput="renderGuaranteeCalc()"></div>
    <div class="field"><label>Hesaplanan Teminat</label><input id="guaranteeAmount" readonly value="0"></div>
    <div class="field"><label>Banka / Kasa</label><select id="guaranteeBank"><option value="">Hesap seçin</option></select></div>
    <div class="field"><label>Yatırılma / Düzenlenme Tarihi</label><input type="date" id="guaranteeDepositDate" oninput="renderGuaranteeCalc()"></div>
    <div class="field"><label>Süre</label><input id="guaranteeDurationValue" type="number" min="0" value="12" oninput="renderGuaranteeCalc()"></div>
    <div class="field"><label>Süre Birimi</label><select id="guaranteeDurationUnit" onchange="renderGuaranteeCalc()"><option value="month">Ay</option><option value="year">Yıl</option><option value="day">Gün</option></select></div>
    <div class="field"><label>Bitiş / Vade Tarihi</label><input type="date" id="guaranteeExpiryDate"></div>
    <div class="field"><label>Referans / Mektup No</label><input id="guaranteeReference" placeholder="Teminat / mektup no"></div>
    <div class="field full"><label>Açıklama</label><textarea id="guaranteeNote" rows="2"></textarea></div>
   </div>
   <div class="guarantee-calc-card"><span>Otomatik Hesap</span><strong id="guaranteeCalcPreview">€ 0</strong><small id="guaranteeCalcFormula">Satış bedeli × teminat oranı</small></div>
   <div class="check-row"><label><input type="checkbox" id="guaranteeFundNow"> Bu kayıtla birlikte seçilen kasa/bankadan finansal çıkış oluştur</label></div>
   <div class="inline-note">Nakit teminat/blokaj finansal çıkış yaratabilir. Banka teminat mektubu ana tutarı kasa bakiyesinden düşmez; risk/yükümlülük olarak takip edilir.</div>
  </div>
  <div class="modal-foot"><button class="btn" onclick="closeModal('guaranteeModal')">Vazgeç</button><button class="btn primary" onclick="saveGuarantee()">Teminatı Kaydet</button></div>
 </div>
</div>

<div class="toast-wrap" id="toasts"></div>


<div class="modal-backdrop" id="allActionsModal">
 <div class="modal" style="max-width:980px">
  <div class="modal-head"><div><span class="eyebrow">AKSİYON MERKEZİ</span><h2>Tüm Aksiyonlar</h2><div class="sub">Kritik tarihler, finans, evrak ve operasyon görevları.</div></div><button class="close" onclick="closeModal('allActionsModal')">×</button></div>
  <div class="modal-body">
 <div class="section-title"><b>Açık Aksiyonlar</b><span class="badge b-red">Gerçek veriye bağlı</span></div>
 <div id="allActionsOpenList"></div>
 <details class="panel" style="margin-top:14px">
   <summary style="cursor:pointer;font-weight:850">Geçmiş / Otomatik Kapanan Aksiyonlar</summary>
   <div id="allActionsHistoryList" style="margin-top:10px"></div>
 </details>
</div>
  <div class="modal-foot"><button class="btn" onclick="closeModal('allActionsModal')">Kapat</button></div>
 </div>
</div>

<div class="modal-backdrop" id="excelImportModal">
 <div class="modal" style="max-width:1080px">
  <div class="modal-head"><div><span class="eyebrow">EXCEL İÇE AKTARMA</span><h2 id="excelImportTitle">Excel İçe Aktarma</h2><div class="sub" id="excelImportSub">Önizleyin, sütunları eşleştirin ve tekrar kontrolünden sonra aktarın.</div></div><button class="close" onclick="closeModal('excelImportModal')">×</button></div>
  <div class="modal-body">
   <div class="excel-import-step">
    <div class="section-title"><b>1. Dosya</b><div class="actions"><button class="btn sm" type="button" onclick="downloadImportTemplate()">Excel Şablonu İndir</button></div></div>
    <div class="form-grid"><div class="field full"><label>.XLSX veya .CSV</label><input id="excelImportFile" type="file" accept=".xlsx,.csv" onchange="previewExcelImport()"></div></div>
    <div id="excelImportRule" class="inline-note" style="margin-top:8px"></div>
   </div>
   <div class="excel-import-step" id="excelSingleRequestMeta" style="display:none">
    <div class="section-title"><b>2. Talep Üst Bilgileri</b><span class="badge b-green">Tek Talep</span></div>
    <div class="form-grid">
      <div class="field col2"><label>Müşteri / Kurum *</label><select id="excelReqCustomer"></select><small>Yalnız mevcut cari kartlardan seçilir.</small></div>
      <div class="field col2"><label>Konu Başlığı *</label><input id="excelReqTitle" maxlength="255" placeholder="Örn. Otel Mutfak Ekipmanları Tedarik İhalesi"></div>
      <div class="field col2"><label>Dış Referans / Talep No</label><input id="excelReqSourceRef" placeholder="Opsiyonel · örn. KFOR-2026-014"></div>
      <div class="field col2"><label>Para Birimi</label><select id="excelReqCurrency"><option>EUR</option><option>USD</option><option>TRY</option></select></div>
      <div class="field col2"><label>Son Teklif Tarihi</label><input id="excelReqDeadline" type="date"></div>
      <div class="field col2"><label>Teslim Şekli</label><select id="excelReqDelivery"><option>EXW</option><option>FCA</option><option>FOB</option><option>CFR</option><option selected>CIF</option><option>DAP</option><option>DDP</option></select></div>
      <div class="field col2"><label>Teslim Yeri</label><input id="excelReqDeliveryPlace" placeholder="Örn. Pristina / Kosovo"></div>
      <div class="field col2"><label>Öncelik</label><select id="excelReqPriority"><option value="normal">Normal</option><option value="priority">Öncelikli</option><option value="urgent">Acil</option></select></div>
      <div class="field col2"><label>Talep Açıklaması</label><input id="excelReqDescription" placeholder="Opsiyonel açıklama"></div>
    </div>
    <div class="import-status-lock" style="margin-top:8px"><b>🔒 Müşteri güvenliği:</b> Excel içinde müşteri adı yoktur. Talep, yukarıda seçtiğiniz mevcut cari kartın <b>partyKey</b> kaydına bağlanır; ikinci cari oluşturulmaz.</div>
   </div>
   <div class="excel-import-step">
    <div class="section-title"><b id="excelMappingTitle">2. Excel Sütunlarını Sistem Alanlarıyla Eşleştir</b><span class="badge b-blue" id="excelImportFileBadge">Dosya bekleniyor</span></div>
    <div id="excelImportMapping" class="excel-mapping-grid"><div class="empty">Önce Excel dosyasını seçin.</div></div>
   </div>
   <div class="excel-import-step">
    <div class="section-title"><b>3. Önizleme</b><span class="badge" id="excelImportCount">0 satır</span></div>
    <div class="table-wrap" id="excelImportPreview"><div class="empty">Veri önizlemesi burada gösterilecek.</div></div>
   </div>
  </div>
  <div class="modal-foot"><button class="btn" onclick="closeModal('excelImportModal')">Kapat</button><button class="btn primary" id="excelImportCommit" onclick="commitExcelImport()" disabled>Kontrollü İçe Aktar</button></div>
 </div>
</div>

<script>window.DB_ADMIN_CSRF=window.ASAY_CSRF=<?=json_encode($dbAdminCsrf,JSON_UNESCAPED_SLASHES)?>;window.ASAY_APP_VERSION=<?=json_encode(ASAY_APP_VERSION)?>;window.ASAY_APP_BUILD=<?=json_encode(ASAY_APP_BUILD)?>;</script>
<script src="assets/js/runtime.js?v=<?=rawurlencode(ASAY_APP_ASSET_VERSION)?>"></script>
<script src="assets/js/app.js?v=<?=rawurlencode(ASAY_APP_ASSET_VERSION)?>"></script>
<script src="assets/js/documents.js?v=<?=rawurlencode(ASAY_APP_ASSET_VERSION)?>"></script>
<script src="assets/js/guarantees.js?v=<?=rawurlencode(ASAY_APP_ASSET_VERSION)?>"></script>
<script src="assets/js/admin.js?v=<?=rawurlencode(ASAY_APP_ASSET_VERSION)?>"></script>
<script src="assets/js/boot.js?v=<?=rawurlencode(ASAY_APP_ASSET_VERSION)?>"></script>
<script>
let currentSessionUser=null,dbRevision=0,dbConflict=false;
(function(){
  let hydrated=false,saving=false,saveQueue=Promise.resolve(true),stateKeyHashes={},savedKeyJson={};window.lastDbSaveError='';
  const statusEl=()=>document.getElementById('dbStatus');
  function setDbStatus(t,ok=true){const e=statusEl();if(e){e.textContent='DB: '+t;e.style.color=ok?'var(--success)':'var(--danger)'}}
  function rerenderAll(){
 const jobs=[
  ['dashboard',()=>updateDashboard()],
  ['requestCustomer',()=>refreshRequestCustomerSelect()],
  ['supplierDirectory',()=>renderSupplierDirectoryOptions()],
  ['tagFilters',()=>refreshTagFilters()],
  ['settingsTags',()=>renderSettingsTags()],
  ['requests',()=>renderRequests()],
  ['orders',()=>renderOrders()],
  ['accounts',()=>renderAccounts()],
  ['cash',()=>renderCash()],
  ['guarantees',()=>renderGuarantees()],
  ['docRequestSelect',()=>syncDocRequestSelect()],
  ['savedDocs',()=>renderSavedDocs()],
  ['permissions',()=>renderPermissions()],
  ['company',()=>loadCompanyToForm()],
  ['users',()=>renderUsers()],
  ['uiSettings',()=>loadUiSettings()],
  ['identity',()=>applyAppIdentity()],
  ['fx',()=>updateFxUi()]
 ];
 jobs.forEach(([name,fn])=>{
  try{fn()}
  catch(e){
   console.error('Render hatası ['+name+']',e);
   try{clientAudit('render_error','ui',name,{message:String(e?.message||e)}).catch(()=>{})}catch(_e){}
  }
 })
}
  function currentRoleConfig(){return String(currentSessionUser?.role||'').toLowerCase()==='admin'?{name:'Admin',request:true,requestWrite:true,supplier:true,supplierWrite:true,cost:true,costWrite:true,finance:true,financeWrite:true,cash:true,cashWrite:true,docs:true,docsWrite:true,settings:true,settingsWrite:true}:state.roles?.find(r=>r.name===currentSessionUser?.role)||null}function legacyWriteDefault(roleName,p){const m={'Satış':{request:true,docs:true},'Satın Alma':{request:true,supplier:true,cost:true},'Finans':{finance:true,cash:true,docs:true}};return !!m[roleName]?.[p]}function currentCanWrite(p){const r=currentRoleConfig();if(!r)return false;if(r.name==='Admin')return true;if(r.name==='Görüntüleyici')return false;const k=p+'Write';return k in r?!!r[k]:legacyWriteDefault(r.name,p)}window.currentCanWrite=currentCanWrite;function applyCurrentUserPermissions(){if(!currentSessionUser)return;const role=currentRoleConfig(),map={requests:'request',attachments:'docs',orders:'request',accounts:'finance',cash:'cash',guarantees:'finance',docs:'docs',settings:'settings'};document.querySelectorAll('.navbtn[data-view]').forEach(b=>{const k=map[b.dataset.view];b.style.display=(!k||(role&&role[k]))?'flex':'none'});const active=document.querySelector('.view.active')?.id?.replace('view-',''),ak=map[active];if(ak&&(!role||!role[ak]))go('dashboard');const n=document.getElementById('sidebarUserName'),m=document.getElementById('sidebarUserMeta');if(n)n.textContent=currentSessionUser.name||'Kullanıcı';if(m)m.textContent=(currentSessionUser.role||'')+' · '+(currentSessionUser.status==='active'?'Aktif':'Pasif')}
  function captureStateBaseline(hashes={}){savedKeyJson={};Object.keys(state).forEach(k=>{try{savedKeyJson[k]=JSON.stringify(state[k])}catch(_e){savedKeyJson[k]='null'}});stateKeyHashes={...(hashes||{})}}
  function acceptServerState(serverState,revision,hashes={}){if(serverState&&typeof serverState==='object'){Object.keys(state).forEach(k=>delete state[k]);Object.assign(state,serverState);normalizeStateSchema();restoreFreshFxCache();ensureRequestUnits()}dbRevision=Number(revision||dbRevision);captureStateBaseline(hashes);dbConflict=false}
  window.acceptServerState=acceptServerState;
  async function loadDbState(){try{const r=await fetch('api/state.php?action=load',{cache:'no-store',headers:{'Accept':'application/json'}});const raw=await r.text();let j={};try{j=JSON.parse(raw)}catch(_e){}if(!r.ok||!j.ok)throw new Error(j.error||('HTTP '+r.status));currentSessionUser=j.user||currentSessionUser;if(j.state&&typeof j.state==='object'){acceptServerState(j.state,j.revision,j.keyHashes||{});rerenderAll();setDbStatus('MySQL bağlı')}else{clearOperationalStateForFreshInstall();captureStateBaseline({});rerenderAll();setDbStatus('boş veritabanı · ilk kayıt hazırlanıyor')}hydrated=true;dbConflict=false;applyCurrentUserPermissions();if(currentCanWrite('settings'))await loadDbUsers();if(!fxIsUsable(24))await refreshTcmbFx(false);else if(restoreFreshFxCache())await saveDbState(true)}catch(e){hydrated=true;setDbStatus('bağlantı hatası',false);console.error(e)}}
  function clientStateKeyCanWrite(k){const map={requests:'request',orders:'request',suppliers:'supplier',supplierDirectory:'supplier',selected:'cost',quoteMarkup:'cost',customerQuotes:'cost',won:'cost',accounts:'finance',guarantees:'finance',cash:'cash',cashAccounts:'cash',documents:'docs',requestAttachments:'docs',pdfColumnsByType:'docs',pdfBlocksByType:'docs',pdfBlockLabelsByType:'docs',pdfFreeTextsByType:'docs',pdfRowCustom:'docs',checklistNotes:'docs',company:'settings',roles:'settings',ui:'settings',appIdentity:'settings',requestNumber:'settings',requestUnits:'settings',pdfColumns:'settings'};if(k==='tagCatalog')return currentCanWrite('request')||currentCanWrite('finance')||currentCanWrite('settings');if(k==='fx')return currentCanWrite('request')||currentCanWrite('supplier')||currentCanWrite('cost')||currentCanWrite('settings');return map[k]?currentCanWrite(map[k]):false}function pendingStatePatch(){const patch={},baseHashes={};Object.keys(state).forEach(k=>{if(!clientStateKeyCanWrite(k))return;let now='null';try{now=JSON.stringify(state[k])}catch(_e){}if(savedKeyJson[k]!==now){patch[k]=state[k];if(stateKeyHashes[k])baseHashes[k]=stateKeyHashes[k]}});return {patch,baseHashes}}
  function enqueueDbMutation(fn){const task=saveQueue.then(()=>fn(),()=>fn());saveQueue=task.catch(()=>false);return task}
  async function savePendingStatePatchNow(){
   if(!hydrated||dbConflict)return false;
   if(window.sanitizeClientStateInPlace)window.sanitizeClientStateInPlace();
   const pending=pendingStatePatch(),keys=Object.keys(pending.patch);if(!keys.length)return true;
   saving=true;
   try{
    const r=await fetch('api/state.php?action=patch',{method:'POST',headers:{'Content-Type':'application/json','Accept':'application/json','X-ASAY-REVISION':String(dbRevision)},body:JSON.stringify(pending)});
    const raw=await r.text();let j={};try{j=JSON.parse(raw)}catch(_e){}
    if(r.status===409){dbConflict=true;setDbStatus('gerçek veri çakışması · yenileme gerekli',false);const names=(j.conflictKeys||[]).join(', ');if(await appConfirm('Aynı veri bölümünde başka bir oturum/kullanıcı gerçekten değişiklik yaptı'+(names?' ('+names+')':'')+'. Sunucudaki güncel veriler yüklensin mi?','Veri Çakışması'))await loadDbState();return false}
    if(!r.ok||!j.ok)throw new Error(j.error||('DB save error · HTTP '+r.status));
    dbRevision=Number(j.revision||dbRevision+1);Object.assign(stateKeyHashes,j.keyHashes||{});keys.forEach(k=>{try{savedKeyJson[k]=JSON.stringify(state[k])}catch(_e){savedKeyJson[k]='null'}});window.lastDbSaveError='';
    setDbStatus('kaydedildi · '+new Date().toLocaleTimeString('tr-TR',{hour:'2-digit',minute:'2-digit'}));return true
   }catch(e){window.lastDbSaveError=String(e?.message||e||'Bilinmeyen veritabanı hatası');setDbStatus('kayıt hatası',false);console.error(e);return false}finally{saving=false}
  }
  async function saveDbState(force=false){if(!hydrated||dbConflict)return false;return await enqueueDbMutation(()=>savePendingStatePatchNow())}
  async function runAuthoritativeDbMutation(fn){
   if(!hydrated||dbConflict)return false;
   return await enqueueDbMutation(async()=>{
    // Server-side finans/operasyon state'i değiştirmeden önce tarayıcıdaki bekleyen değişiklikleri aynı seri kuyrukta flush et.
    const flushed=await savePendingStatePatchNow();if(!flushed)return false;
    return await fn()
   })
  }
  async function queueDbSave(label='Değişiklik'){const ok=await saveDbState(true);if(!ok)toast(label+' veritabanına kaydedilemedi: '+(window.lastDbSaveError||'DB hatası'),'warn');return ok}
  window.saveDbState=saveDbState;window.queueDbSave=queueDbSave;window.enqueueDbMutation=enqueueDbMutation;window.runAuthoritativeDbMutation=runAuthoritativeDbMutation;window.rerenderAll=rerenderAll;window.loadDbState=loadDbState;
  window.addEventListener('beforeunload',()=>{if(!hydrated||dbConflict)return;try{const pending=pendingStatePatch();if(Object.keys(pending.patch).length)navigator.sendBeacon('api/state.php?action=patch_beacon&_csrf='+encodeURIComponent(window.ASAY_CSRF||''),new Blob([JSON.stringify(pending)],{type:'application/json'}))}catch(_e){}});
  document.addEventListener('DOMContentLoaded',()=>{loadDbState().then(()=>setTimeout(()=>saveDbState(false),700));setInterval(()=>{if(document.visibilityState==='visible'&&!document.querySelector('.modal-backdrop.show'))saveDbState(false)},60000)});
})();

document.addEventListener('DOMContentLoaded',()=>{
 const cards=document.getElementById('accountCards');
 if(cards)cards.addEventListener('dblclick',e=>{e.preventDefault();e.stopPropagation()},true);
});

if(<?= $isPageAdmin?'true':'false' ?>)setTimeout(()=>loadDatabaseManagerStatus(),250);
</script>
</body>
</html>
