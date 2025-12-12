<table class="table table-hover m-0">
    <thead class="thead-light">
        <tr>
            <th>Prod. No</th>
            <th>Date</th>
            <th>Job Order</th>
            <th>Location</th>
            <th>Produced QTY (kg)</th>
            <th>Job Order-wise Details</th>
            <th>Supervisor</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        @if (count($productionVouchers) != 0)
            @foreach ($productionVouchers as $voucher)
                <tr>
                    <td>
                        <strong class="d-block">{{ $voucher->prod_no }}</strong>
                    </td>
                    <td>{{ \Carbon\Carbon::parse($voucher->prod_date)->format('M d, Y') }}</td>
                    <td>
                        @if($voucher->jobOrders && count($voucher->jobOrders) > 0)
                            @foreach($voucher->jobOrders as $jobOrder)
                                <span class="badge badge-primary mr-1">{{ $jobOrder->job_order_no }}</span>
                            @endforeach
                        @elseif($voucher->jobOrder)
                            <span class="badge badge-primary">{{ $voucher->jobOrder->job_order_no }}</span>
                        @else
                            <span class="text-muted">N/A</span>
                        @endif
                    </td>
                    <td>
                        {{ $voucher->location->name ?? 'N/A' }}
                    </td>
                    <td>
                        <strong>{{ number_format($voucher->produced_qty_kg, 2) }}</strong> kgs
                        <br>
                        <small class="text-muted">{{ number_format($voucher->produced_qty_kg / 1000, 2) }} MT</small>
                    </td>
                    <td>
                        @if(isset($voucher->producedByJobOrder) && count($voucher->producedByJobOrder) > 0)
                            <div class="job-order-toggle-header" 
                                 onclick="toggleJobOrderDetails('job-order-details-row-{{ $voucher->id }}', this)">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <small class="text-muted">Click to view breakdown</small>
                                    <i class="ft-chevron-down chevron-icon" id="chevron-{{ $voucher->id }}"></i>
                                </div>
                                @foreach($voucher->producedByJobOrder as $jobOrderId => $data)
                                    <div class="job-order-summary-item">
                                        <strong class="job-order-summary-name">{{ $data['job_order_no'] }}{{ $data['job_order_ref_no'] ? ' (' . $data['job_order_ref_no'] . ')' : '' }}</strong>
                                        <span class="badge badge-success job-order-summary-qty">{{ number_format($data['produced_qty'], 2) }} KG</span>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <span class="text-muted">No production data</span>
                        @endif
                    </td>
                    <td>
                        {{ $voucher->supervisor->name ?? '-' }}
                    </td>
                    <td>
                        @if($voucher->status == 'draft')
                            <span class="badge badge-warning">Draft</span>
                        @elseif($voucher->status == 'completed')
                            <span class="badge badge-success">Completed</span>
                        @elseif($voucher->status == 'approved')
                            <span class="badge badge-info">Approved</span>
                        @endif
                    </td>
                    <td>
                        <div class="btn-group btn-group-sm" role="group">
                            <button type="button" 
                                onclick="window.location.href='{{ route('production-voucher.edit', $voucher->id) }}'"
                                class="btn btn-outline-primary" title="Edit">
                                <i class="ft-edit"></i>
                            </button>
                            <button type="button" 
                                onclick="deletemodal('{{ route('production-voucher.destroy', $voucher->id) }}','{{ route('get.production-voucher') }}')"
                                class="btn btn-outline-danger" title="Delete">
                                <i class="ft-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>

                {{-- Job Order Details Row (Toggle-able) --}}
                @if(isset($voucher->producedByJobOrder) && count($voucher->producedByJobOrder) > 0)
                    <tr id="job-order-details-row-{{ $voucher->id }}" class="job-order-details-row" style="display: none;">
                        <td colspan="9" class="p-0">
                            <div class="job-order-details-container">
                                @foreach($voucher->producedByJobOrder as $jobOrderId => $data)
                                    <div class="job-order-box mb-3">
                                        <div class="job-order-box-header">
                                            <div>
                                                <strong class="job-order-name">{{ $data['job_order_no'] }}{{ $data['job_order_ref_no'] ? ' (' . $data['job_order_ref_no'] . ')' : '' }}</strong>
                                            </div>
                                            <div class="job-order-quantity-badges">
                                                <span class="badge badge-primary">Total: {{ number_format($data['allocated_qty'] ?? 0, 2) }} KG</span>
                                                <span class="badge badge-success">Produced: {{ number_format($data['produced_qty'] ?? 0, 2) }} KG</span>
                                                @php
                                                    $remainingQty = isset($data['remaining_qty']) ? $data['remaining_qty'] : (($data['allocated_qty'] ?? 0) - ($data['produced_qty'] ?? 0));
                                                @endphp
                                                <span class="badge badge-{{ $remainingQty > 0 ? 'warning' : ($remainingQty < 0 ? 'danger' : 'secondary') }}">Remaining: {{ number_format($remainingQty, 2) }} KG</span>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach

                                {{-- Summary Row --}}
                                @php
                                    $overallTotalAllocated = array_sum(array_column($voucher->producedByJobOrder, 'allocated_qty'));
                                    $overallTotalProduced = array_sum(array_column($voucher->producedByJobOrder, 'produced_qty'));
                                    $overallRemaining = $overallTotalAllocated - $overallTotalProduced;
                                @endphp
                                <div class="job-order-summary-box mt-3">
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
                        <i class="ft-clipboard ft-3x text-muted mb-3"></i>
                        <h5 class="text-muted">No Production Voucher Found</h5>
                        <p class="text-muted mb-3">Get started by creating your first production voucher</p>
                        <button onclick="openModal(this,'{{ route('production-voucher.create') }}','Create Production Voucher',false,'90%')" 
                                class="btn btn-primary">
                            <i class="ft-plus mr-1"></i> Create Production Voucher
                        </button>
                    </div>
                </td>
            </tr>
        @endif
    </tbody>
</table>

@if (count($productionVouchers) != 0)
<div class="row mt-3">
    <div class="col-md-12">
        <div class="float-right" id="paginationLinks">
            {{ $productionVouchers->links() }}
        </div>
    </div>
</div>
@endif

<style>
    .job-order-toggle-header {
        cursor: pointer;
        padding: 10px;
        border-radius: 4px;
        border: 1px solid #dee2e6;
        background-color: #fff;
        transition: background-color 0.2s, box-shadow 0.2s;
        user-select: none;
    }
    .job-order-toggle-header:hover {
        background-color: #f8f9fa;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    .job-order-summary-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 6px 0;
        border-bottom: 1px solid #f0f0f0;
    }
    .job-order-summary-item:last-child {
        border-bottom: none;
    }
    .job-order-summary-name {
        font-size: 13px;
        color: #495057;
    }
    .job-order-summary-qty {
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
    .job-order-details-row {
        background-color: #f8f9fa;
    }
    .job-order-details-container {
        padding: 15px;
    }
    .job-order-box {
        background-color: #fff;
        border: 1px solid #dee2e6;
        border-radius: 4px;
        padding: 15px;
    }
    .job-order-box-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        padding-bottom: 10px;
        margin-bottom: 10px;
        border-bottom: 1px solid #dee2e6;
    }
    .job-order-name {
        font-size: 14px;
        color: #495057;
        font-weight: 600;
        margin-bottom: 5px;
    }
    .job-order-quantity-badges {
        display: flex;
        flex-direction: row;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
    }
    .job-order-summary-box {
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
    function toggleJobOrderDetails(detailsRowId, headerElement) {
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
