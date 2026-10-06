from pathlib import Path
root=Path(__file__).resolve().parents[1]
ops=(root/'api/operations.php').read_text(encoding='utf-8')
app=(root/'assets/js/app.js').read_text(encoding='utf-8')
idx=(root/'index.php').read_text(encoding='utf-8')
boot=(root/'app/bootstrap.php').read_text(encoding='utf-8')
checks={
 'version': "const ASAY_APP_VERSION = '3.13.4'" in boot and 'V3.13.4' in boot,
 'delivery_undo_permission': "'delivery_batch_undo'=>['request']" in ops,
 'delivery_undo_backend': "elseif($action==='delivery_batch_undo')" in ops and "audit('delivery_batch_undo'" in ops,
 'delivery_undo_status': "$b['status']='Planlandı'" in ops and "recompute_delivery_po_status($o,$fallback)" in ops,
 'delivery_undo_finance_guard': "delivery_batch_collection','delivery_batch_supplier_payment" in ops and 'Önce ilgili finans hareketini Sil ile kaldırın.' in ops,
 'delivery_snapshot': "deliveryUndoSnapshot" in ops,
 'delivery_undo_ui': 'Teslimatı Geri Al' in app and 'undoDeliveryBatchDelivered' in app,
 'receipt_update_permission': "'finance_customer_receipt_update'=>['finance','cash']" in ops,
 'receipt_update_backend': "elseif($action==='finance_customer_receipt_update')" in ops and "audit('finance_customer_receipt_update'" in ops,
 'receipt_supported_types': all(x in ops for x in ['customer_prepayment','generic_customer_receipt','delivery_batch_collection']),
 'receipt_updates_bank': "$oldBank['balance']" in ops and "$newBank['balance']" in ops,
 'receipt_updates_order': "reverse_schedule_payment($o,$oldAmount)" in ops and "allocate_schedule_payment($o,$newAmount)" in ops,
 'receipt_updates_account': 'remove_account_entry_signature_date' in ops and "add_entry($a,$r['no'],$newDesc,0,$newAmount,$newDate)" in ops,
 'receipt_hard_delete_ui': 'deleteCustomerReceipt' in app and "action:'finance_delete'" in app,
 'receipt_edit_ui': 'openCustomerReceiptEdit' in app and 'saveCustomerReceiptEdit' in app and "action:'finance_customer_receipt_update'" in app,
 'no_reverse_button_in_order_receipts': 'Gerçek Müşteri Tahsilatları' in app and 'Müşteri Tahsilatını Sil' in app,
 'receipt_edit_modal': 'customerReceiptEditModal' in idx and 'saveCustomerReceiptEditBtn' in idx and 'creAmount' in idx and 'creBank' in idx,
}
for k,v in checks.items(): print(('PASS' if v else 'FAIL')+': '+k)
raise SystemExit(0 if all(checks.values()) else 1)
