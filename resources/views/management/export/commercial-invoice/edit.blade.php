@include('management.export.commercial-invoice.form', [
    'mode' => 'edit',
    'commercialInvoice' => $commercialInvoice,
    'exportOrders' => $exportOrders,
    'preview' => $preview,
    'goodsSummary' => $goodsSummary,
])
