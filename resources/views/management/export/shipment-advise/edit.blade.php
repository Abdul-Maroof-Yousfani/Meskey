@include('management.export.shipment-advise.form', [
    'mode' => 'edit',
    'shipmentAdvise' => $shipmentAdvise,
    'exportOrders' => $exportOrders,
    'preview' => $preview,
    'goodsSummary' => $goodsSummary,
])
