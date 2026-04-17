@include('management.export.bill-of-lading.form', [
    'mode' => 'create',
    'billOfLading' => $billOfLading,
    'exportOrders' => $exportOrders,
    'preview' => $preview,
    'goodsSummary' => $goodsSummary,
])
