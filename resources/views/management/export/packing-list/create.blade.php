@include('management.export.packing-list.form', [
    'mode' => 'create',
    'packingList' => $packingList,
    'exportOrders' => $exportOrders,
    'preview' => $preview,
    'goodsSummary' => $goodsSummary,
])
