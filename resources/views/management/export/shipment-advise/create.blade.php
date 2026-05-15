@include('management.export.shipment-advise.form', [
    'mode' => 'create',
    'shipmentAdvise' => $shipmentAdvise,
    'exportOrders' => $exportOrders,
    'preview' => $preview,
    'goodsSummary' => $goodsSummary,
])
