<table class="table table-hover m-0">
    <thead class="thead-light">
        <tr>
            <th width="12%">Job Order</th>
            <th width="8%">Date</th>
            <th width="12%">Location</th>
            <th width="12%">Product</th>
            <th width="10%">Quantity</th>
            <th width="12%">Produced Qty (Location-wise)</th>
            <th width="8%">Containers</th>
            <th width="6%">Status</th>
            <th width="10%">Actions</th>
        </tr>
    </thead>
    <tbody>
        @if (count($job_orders) != 0)
            @foreach ($job_orders as $job_order)
                {{-- Main Row --}}
                <tr>
                    <td>
                        <div>
                            <strong class="d-block">{{ $job_order->job_order_no }}</strong>
                            @if($job_order->ref_no)
                                <small class="text-muted">Ref: {{ $job_order->ref_no }}</small>
                            @endif
                        </div>
                    </td>
                    <td>{{ \Carbon\Carbon::parse($job_order->job_order_date)->format('d/m/Y') }}</td>
                    <td>
                        <span class="badge badge-light">{{ $job_order->company_locations_string ?? 'N/A' }}</span>
                    </td>
                    <td>
                        <span class="text-primary">{{ Str::limit($job_order->product->name ?? 'N/A', 20) }}</span>
                    </td>
                    <td>
                        <div>
                            <strong>{{ number_format($job_order->total_kgs) }} KG</strong>
                            <small class="d-block text-muted">{{ number_format($job_order->total_metric_tons, 1) }} MT</small>
                        </div>
                    </td>
                    <td>
                        @if(isset($job_order->producedByLocation) && count($job_order->producedByLocation) > 0)
                            <div class="location-toggle-header" 
                                 onclick="toggleLocationDetails('location-details-row-{{ $job_order->id }}', this)">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <small class="text-muted">Click to view breakdown</small>
                                    <i class="ft-chevron-down chevron-icon" id="chevron-{{ $job_order->id }}"></i>
                                </div>
                                @foreach($job_order->producedByLocation as $locationId => $data)
                                    <div class="location-summary-item">
                                        <strong class="location-summary-name">{{ $data['location_name'] }}</strong>
                                        <span class="badge badge-success location-summary-qty">{{ number_format($data['produced_qty'], 2) }} KG</span>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <span class="text-muted">No production yet</span>
                        @endif
                    </td>
                    <td>
                        <span class="badge badge-secondary">{{ $job_order->total_containers }}</span>
                    </td>
                    <td>
                        <!-- @php
                            $status = 'primary';
                            if($job_order->loading_date && now()->gt($job_order->loading_date)) {
                                $status = 'success';
                            } elseif($job_order->delivery_date && now()->gt($job_order->delivery_date)) {
                                $status = 'warning';
                            }
                        @endphp
                        <span class="badge badge-{{ $status }}">
                            {{ $status == 'success' ? 'Completed' : ($status == 'warning' ? 'In Progress' : 'Active') }}
                        </span> -->
                    </td>
                    <td>
                        <div class="" role="group">
                            <button type="button" 
                                onclick="openModal(this,'{{ route('job-orders.edit', $job_order->id) }}','Edit Job Order',false,'95%')"
                                class="btn btn-outline-primary  position-relative" title="Edit">
                                <i class="ft-edit"></i>
                            </button>
                            <button type="button" 
                                onclick="deletemodal('{{ route('job-orders.destroy', $job_order->id) }}','{{ route('get.job_orders') }}')"
                                class="btn btn-outline-danger position-relative" title="Delete">
                                <i class="ft-trash"></i>
                            </button>
                            <button type="button" 
                                onclick="openModal(this,'{{ route('job-orders.edit', $job_order->id) }}','Edit Job Order',true,'90%')"
                                class="btn btn-outline-info position-relative" title="View">
                                <i class="ft-eye"></i>
                            </button>
                           
                        </div>
                    </td>
                </tr>

                {{-- Location Details Row (Toggle-able) --}}
                @if(isset($job_order->producedByLocation) && count($job_order->producedByLocation) > 0)
                    <tr id="location-details-row-{{ $job_order->id }}" class="location-details-row" style="display: none;">
                        <td colspan="9" class="p-0">
                            <div class="location-details-container">
                                @foreach($job_order->producedByLocation as $locationId => $data)
                                    <div class="location-box mb-3">
                                        <div class="location-box-header">
                                            <div>
                                                <strong class="location-name">{{ $data['location_name'] }}</strong>
                                            </div>
                                            <div class="location-quantity-badges">
                                                <span class="badge badge-primary">Total: {{ number_format($data['allocated_qty'], 2) }} KG</span>
                                                <span class="badge badge-success">Produced: {{ number_format($data['produced_qty'], 2) }} KG</span>
                                                <span class="badge badge-{{ $data['remaining_qty'] > 0 ? 'warning' : ($data['remaining_qty'] < 0 ? 'danger' : 'secondary') }}">Remaining: {{ number_format($data['remaining_qty'], 2) }} KG</span>
                                            </div>
                                        </div>
                                        @php
                                            $locationOutputs = $job_order->productionOutputs->where('product_id', $job_order->product_id)
                                                ->filter(function($output) use ($locationId) {
                                                    return $output->productionVoucher && 
                                                           $output->productionVoucher->location_id == $locationId;
                                                });
                                        @endphp
                                        @if($locationOutputs->count() > 0)
                                            <div class="location-details-table">
                                                <table class="table table-sm table-bordered mb-0">
                                                    <thead>
                                                        <tr>
                                                            <th>Production Voucher</th>
                                                            <th>Date</th>
                                                            <th>Qty (KG)</th>
                                                            <th>Bags</th>
                                                            <th>Brand</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach($locationOutputs as $output)
                                                            <tr>
                                                                <td>{{ $output->productionVoucher->prod_no ?? 'N/A' }}</td>
                                                                <td>{{ $output->productionVoucher->prod_date ? $output->productionVoucher->prod_date->format('d/m/Y') : 'N/A' }}</td>
                                                                <td>{{ number_format($output->qty, 2) }}</td>
                                                                <td>{{ $output->no_of_bags ?? '-' }}</td>
                                                                <td>{{ $output->brand->name ?? '-' }}</td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        @endif
                                    </div>
                                @endforeach

                                {{-- Summary Row --}}
                                @php
                                    $overallTotalAllocated = array_sum(array_column($job_order->producedByLocation, 'allocated_qty'));
                                    $overallTotalProduced = array_sum(array_column($job_order->producedByLocation, 'produced_qty'));
                                    $overallRemaining = $overallTotalAllocated - $overallTotalProduced;
                                @endphp
                                <div class="location-summary-box mt-3">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="summary-item">
                                                <strong>Total Allocated:</strong>
                                                <span class="badge badge-primary">{{ number_format($overallTotalAllocated, 2) }} KG</span>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="summary-item">
                                                <strong>Total Produced:</strong>
                                                <span class="badge badge-success">{{ number_format($overallTotalProduced, 2) }} KG</span>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="summary-item">
                                                <strong>Remaining:</strong>
                                                <span class="badge badge-{{ $overallRemaining > 0 ? 'warning' : ($overallRemaining < 0 ? 'danger' : 'secondary') }}">{{ number_format($overallRemaining, 2) }} KG</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                @endif
            @endforeach
        @else
            <tr>
                <td colspan="9" class="text-center py-4">
                    <div class="empty-state">
                        <i class="ft-briefcase ft-3x text-muted mb-3"></i>
                        <h5 class="text-muted">No Job Orders Found</h5>
                        <p class="text-muted mb-3">Get started by creating your first job order</p>
                        <button onclick="openModal(this,'{{ route('job-orders.create') }}','Create Job Order',false,'90%')" 
                                class="btn btn-primary">
                            <i class="ft-plus mr-1"></i> Create Job Order
                        </button>
                    </div>
                </td>
            </tr>
        @endif
    </tbody>
</table>

@if (count($job_orders) != 0)
<div class="row mt-3">
    <div class="col-md-12">
        <div class="float-right" id="paginationLinks">
            {{ $job_orders->links() }}
        </div>
    </div>
</div>
@endif

<style>
    .location-toggle-header {
        cursor: pointer;
        padding: 10px;
        border-radius: 4px;
        border: 1px solid #dee2e6;
        background-color: #fff;
        transition: background-color 0.2s, box-shadow 0.2s;
        user-select: none;
    }
    .location-toggle-header:hover {
        background-color: #f8f9fa;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    .location-summary-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 6px 0;
        border-bottom: 1px solid #f0f0f0;
    }
    .location-summary-item:last-child {
        border-bottom: none;
    }
    .location-summary-name {
        font-size: 13px;
        color: #495057;
    }
    .location-summary-qty {
        font-size: 12px;
    }
    .chevron-icon {
        transition: transform 0.3s ease;
        color: #6c757d;
        font-size: 16px;
    }
    .chevron-icon.rotated {
        transform: rotate(180deg);
    }
    .location-details-row {
        background-color: #f8f9fa;
    }
    .location-details-container {
        padding: 15px;
    }
    .location-box {
        background-color: #fff;
        border: 1px solid #dee2e6;
        border-radius: 4px;
        padding: 15px;
    }
    .location-box-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        padding-bottom: 10px;
        margin-bottom: 10px;
        border-bottom: 1px solid #dee2e6;
    }
    .location-name {
        font-size: 14px;
        color: #495057;
        font-weight: 600;
        margin-bottom: 5px;
    }
    .location-quantity-badges {
        display: flex;
        flex-direction: row;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
    }
    .location-details-table {
        margin-top: 10px;
    }
    .location-details-table table {
        font-size: 12px;
        margin-bottom: 0;
    }
    .location-details-table table th {
        background-color: #f8f9fa;
        font-weight: 600;
        padding: 8px;
    }
    .location-details-table table td {
        padding: 8px;
    }
    .location-summary-box {
        background-color: #fff;
        border: 2px solid #007bff;
        border-radius: 4px;
        padding: 15px;
        background-color: #f8f9fa;
    }
    .summary-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 8px 0;
    }
    .summary-item strong {
        font-size: 14px;
        color: #495057;
    }
</style>

<script>
    function toggleLocationDetails(detailsRowId, headerElement) {
        const detailsRow = document.getElementById(detailsRowId);
        const chevron = headerElement.querySelector('.chevron-icon');
        
        if (detailsRow.style.display === 'none' || !detailsRow.style.display) {
            detailsRow.style.display = 'table-row';
            if (chevron) {
                chevron.classList.add('rotated');
            }
        } else {
            detailsRow.style.display = 'none';
            if (chevron) {
                chevron.classList.remove('rotated');
            }
        }
    }
</script>