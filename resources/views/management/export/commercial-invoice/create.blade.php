@include('management.export.commercial-invoice.form', [
    'mode' => 'create',
    'commercialInvoice' => $commercialInvoice,
    'exportOrders' => $exportOrders,
    'preview' => $preview,
    'goodsSummary' => $goodsSummary,
])
