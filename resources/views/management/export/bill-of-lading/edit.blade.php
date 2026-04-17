@include('management.export.bill-of-lading.form', [
    'mode' => 'edit',
    'billOfLading' => $billOfLading,
    'exportOrders' => $exportOrders,
    'preview' => $preview,
    'goodsSummary' => $goodsSummary,
])
