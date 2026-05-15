@php
    $packingType = strtolower((string) ($preview['packing_type'] ?? 'bulk'));
@endphp

@if ($packingType === 'container')
    @include('management.export.bill-of-lading.partials.preview-container', [
        'preview' => $preview,
        'goodsSummary' => $goodsSummary,
    ])
@else
    @include('management.export.bill-of-lading.partials.preview-bulk', [
        'preview' => $preview,
        'goodsSummary' => $goodsSummary,
    ])
@endif
