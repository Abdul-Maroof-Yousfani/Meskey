<style>
    .produced-qty-badge {
        cursor: pointer;
        transition: all 0.3s ease;
    }
    .produced-qty-badge:hover {
        transform: scale(1.05);
        box-shadow: 0 2px 5px rgba(0,0,0,0.2);
    }
    .chevron-icon {
        transition: transform 0.3s ease;
        display: inline-block;
        margin-left: 5px;
    }
    .chevron-icon.rotated {
        transform: rotate(180deg);
    }
    .produced-detail-row {
        background-color: #f8f9fa;
    }
    .produced-detail-container {
        padding: 15px;
    }
</style>

<div class="table-responsive">
    <table class="table table-bordered table-sm">
        <thead class="thead-light">
            <tr>
                <th>Job Order</th>
                <th>Product</th>
                <th>Bag Type</th>
                <th>Bag Condition</th>
                <th>Bag Size</th>
                <th>No of Bags</th>
                <th>Total Bags</th>
                <th>Total Kgs</th>
                <th>Metric Tons</th>
                <th>Brand</th>
                <th>Delivery Date</th>
                <th>Produced Qty (kg)</th>
            </tr>
        </thead>
        <tbody>
            @if($packingItems && $packingItems->count() > 0)
                @foreach($packingItems as $index => $item)
                    @php
                        $jobOrderId = $item->job_order_id;
                        $producedQty = $producedByJobOrder[$jobOrderId] ?? 0;
                        $producedDetails = $producedDetailsByJobOrder[$jobOrderId] ?? collect();
                        $detailRowId = 'produced-detail-' . $jobOrderId . '-' . $index;
                        $hasProduced = $producedQty > 0 && $producedDetails->count() > 0;
                    @endphp
                    <tr>
                        <td>{{ $item->jobOrder->job_order_no ?? 'N/A' }}</td>
                        <td>{{ $item->jobOrder->product->name ?? 'N/A' }}</td>
                        <td>{{ $item->bagType->name ?? 'N/A' }}</td>
                        <td>{{ $item->bagCondition->name ?? 'N/A' }}</td>
                        <td>{{ $item->bag_size ?? '0' }}</td>
                        <td>{{ $item->no_of_bags ?? '0' }}</td>
                        <td>{{ $item->total_bags ?? '0' }}</td>
                        <td>{{ number_format($item->total_kgs ?? 0, 2) }}</td>
                        <td>{{ number_format($item->metric_tons ?? 0, 3) }}</td>
                        <td>{{ $item->brand->name ?? 'N/A' }}</td>
                        <td>{{ $item->delivery_date ? $item->delivery_date->format('Y-m-d') : 'N/A' }}</td>
                        <td>
                            @if($hasProduced)
                                <span class="badge badge-info produced-qty-badge" 
                                      onclick="toggleProducedDetails('{{ $detailRowId }}', this)"
                                      style="cursor: pointer;">
                                    {{ number_format($producedQty, 2) }} kg
                                    <i class="ft-chevron-down chevron-icon"></i>
                                </span>
                            @else
                                <span class="badge badge-secondary">{{ number_format($producedQty, 2) }} kg</span>
                            @endif
                        </td>
                    </tr>
                    @if($hasProduced)
                        <tr id="{{ $detailRowId }}" class="produced-detail-row" style="display: none;">
                            <td colspan="12">
                                <div class="produced-detail-container">
                                    <table class="table table-bordered table-sm table-hover" style="margin-bottom: 0;">
                                        <thead class="thead-light">
                                            <tr>
                                                <th>Prod. Voucher</th>
                                                <th>Location</th>
                                                <th>Storage Location</th>
                                                <th>Product</th>
                                                <th>No of Bags</th>
                                                <th>Bag Size</th>
                                                <th>Avg Weight/Bag</th>
                                                <th>Brand</th>
                                                <th>Prod. Date</th>
                                                <th>Qty (kg)</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($producedDetails as $output)
                                                <tr>
                                                    <td>{{ $output->productionVoucher->prod_no ?? 'N/A' }}</td>
                                                    <td>{{ $output->productionVoucher->location->name ?? 'N/A' }}</td>
                                                    <td>
                                                        {{ $output->storageLocation->name ?? 'N/A' }}
                                                        @if($output->storageLocation && $output->storageLocation->arrivalLocation)
                                                            <br><small class="text-muted">({{ $output->storageLocation->arrivalLocation->name ?? '' }})</small>
                                                        @endif
                                                    </td>
                                                    <td>{{ $output->product->name ?? 'N/A' }}</td>
                                                    <td>{{ $output->no_of_bags ?? '0' }}</td>
                                                    <td>{{ $output->bag_size ?? 'N/A' }}</td>
                                                    <td>
                                                        @if($output->avg_weight_per_bag)
                                                            {{ number_format($output->avg_weight_per_bag, 3) }} kg
                                                        @else
                                                            N/A
                                                        @endif
                                                    </td>
                                                    <td>{{ $output->brand->name ?? 'N/A' }}</td>
                                                    <td>
                                                        @if($output->productionVoucher->prod_date)
                                                            {{ $output->productionVoucher->prod_date->format('Y-m-d') }}
                                                        @else
                                                            N/A
                                                        @endif
                                                    </td>
                                                    <td>
                                                        <span class="badge badge-success">
                                                            {{ number_format($output->qty ?? 0, 2) }} kg
                                                        </span>
                                                        @if(isset($currentProductionVoucherId) && $output->production_voucher_id == $currentProductionVoucherId)
                                                            <br><small><span class="badge badge-primary mt-1">This Voucher</span></small>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </td>
                        </tr>
                    @endif
                @endforeach
            @else
                <tr>
                    <td colspan="12" class="text-center text-muted">No packing items found</td>
                </tr>
            @endif
        </tbody>
    </table>
</div>

<script>
    function toggleProducedDetails(detailRowId, badgeElement) {
        const detailRow = document.getElementById(detailRowId);
        const chevron = badgeElement.querySelector('.chevron-icon');
        
        if (detailRow.style.display === 'none' || !detailRow.style.display) {
            detailRow.style.display = 'table-row';
            if (chevron) {
                chevron.classList.add('rotated');
            }
        } else {
            detailRow.style.display = 'none';
            if (chevron) {
                chevron.classList.remove('rotated');
            }
        }
    }
</script>

