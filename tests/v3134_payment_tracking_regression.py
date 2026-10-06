from pathlib import Path
root=Path(__file__).resolve().parents[1]
ops=(root/'api/operations.php').read_text(encoding='utf-8')
app=(root/'assets/js/app.js').read_text(encoding='utf-8')
up=(root/'upgrade.php').read_text(encoding='utf-8')
boot=(root/'app/bootstrap.php').read_text(encoding='utf-8')
checks={
 'version': "const ASAY_APP_VERSION = '3.13.4'" in boot and 'V3.13.4' in boot,
 'prepayment_allocates_schedule': "$scheduleAlloc=allocate_schedule_payment($o,$amount)" in ops,
 'prepayment_journal_has_allocation': "'scheduleAlloc'=>$scheduleAlloc" in ops,
 'upgrade_reconciles_existing_orders': 'function reconcile_order_payment_tracking' in up and '$paymentRepair=reconcile_order_payment_tracking($pdo,$st)' in up,
 'upgrade_reads_real_customer_cash': all(x in up for x in ['customer_prepayment','generic_customer_receipt','delivery_batch_collection']),
 'upgrade_reads_real_supplier_cash': all(x in up for x in ['generic_supplier_payment','delivery_batch_supplier_payment']),
 'customer_schedule_uses_real_cash': 'function customerPaymentScheduleDisplay' in app and 'orderCustomerReceiptRows(o)' in app,
 'supplier_tracking_table': 'function supplierPaymentTrackingHtml' in app and 'Tedarikçi Ödeme Takibi' in app,
 'supplier_real_history': 'function orderSupplierPaymentHistoryHtml' in app and 'Gerçek Tedarikçi Ödemeleri' in app,
 'supplier_currency_grouping': 'function orderSupplierFinanceByCurrency' in app and 'financeCurrencyGroupText' in app,
 'combined_tracking_header': 'Müşteri & Tedarikçi Ödeme Takibi' in app,
 'supplier_pay_action': 'onclick="payPo(' in app,
 'render_in_order_screen': '${orderSupplierPaymentHistoryHtml(o)}' in app,
}
for k,v in checks.items(): print(('PASS' if v else 'FAIL')+': '+k)
raise SystemExit(0 if all(checks.values()) else 1)
