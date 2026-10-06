/* ASAY ERP V3.13.4 — deterministic application boot */
document.addEventListener('DOMContentLoaded',()=>{
 restorePersistentSettings();restoreUiSettings();restoreAppIdentity();restoreAccounts();ensureAllRequestItemIds();renderRequests();renderOrders();updateDashboard();renderAccounts();renderCash();renderRequestItemEditor();renderPackingEditor();renderDocument();enableAssetDrag('pdfLogo');enableAssetDrag('pdfStamp');syncDocRequestSelect();renderSavedDocs();renderPermissions();loadCompanyToForm();renderUsers();loadUiSettings();renderSupplierDirectoryOptions();refreshTagFilters();updateFxUi();setOrientation('portrait');setTimeout(applyPdfScale,80);
});
