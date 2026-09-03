@php
    $isExport = ($logistics->type ?? 'sale_order') === 'export_order';
    $documentLabel = $isExport ? 'EO #' : 'SO #';
    $requestLabel = $isExport ? 'Loading Request (Export Order)' : 'Loading Request (Sale Order)';
    $qtyLabel = $isExport ? 'Export Order Qty (MT)' : 'Sales Order Qty (kg)';
    $tradeTermLabel = $isExport ? 'Inco Term EO' : 'Sauda Type';
    $partnerLabel = 'Transporter';
    $fromLocation = null;
    if (!empty($logistics->location)) {
        $fromLocation = is_numeric($logistics->location)
            ? \App\Models\Master\CompanyLocation::find($logistics->location)?->name
            : $logistics->location;
    }
    if (!$fromLocation && ($logistics->type ?? 'sale_order') === 'sale_order' && $logistics->saleOrder) {
        $fromLocation = $logistics->saleOrder->locations
            ->map(fn($l) => $l->companyLocation?->name)
            ->filter()
            ->implode(', ');
    } elseif (!$fromLocation && ($logistics->type ?? 'sale_order') === 'export_order' && $logistics->exportOrder) {
        $fromLocation = \App\Models\Master\CompanyLocation::whereIn('id', $logistics->exportOrder->company_location_ids ?? [])->pluck('name')->implode(', ');
    }
    $toLocation = $isExport
        ? (\App\Models\Master\Port::find($logistics->to_location)?->name ?? $logistics->to_location)
        : (\App\Models\Master\CompanyLocation::find($logistics->to_location)?->name ?? $logistics->to_location);
@endphp
<div class="row form-mar">
    <div class="col-md-12">
        <div class="row">
            <div class="col-12">
                <h6 class="header-heading-sepration text-uppercase">Selection Detail</h6>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label class="form-label text-uppercase">Type</label>
                    <input type="text" class="form-control" value="{{ str_replace('_', ' ', ucwords($logistics->type ?? 'sale_order', '_')) }}" readonly>
                </div>
            </div>
        </div>

        <div class="row pt-2">
            <div class="col-12">
                <h6 class="header-heading-sepration text-uppercase">Document Information</h6>
            </div>
            
            <div class="col-md-4">
                <div class="form-group">
                    <label class="form-label text-uppercase">{{ $requestLabel }}</label>
                    <input type="text" class="form-control" value="{{ $logistics->loading_request }}" readonly>
                </div>
            </div>

            <div class="col-md-4">
                <div class="form-group">
                    <label class="form-label text-uppercase">Date</label>
                    <input type="text" class="form-control" value="{{ $logistics->date }}" readonly>
                </div>
            </div>

            <div class="col-md-4">
                <div class="form-group">
                    <label class="form-label text-uppercase">{{ $documentLabel }}</label>
                    <input type="text" class="form-control" value="{{ $logistics->so_no }}" readonly>
                </div>
            </div>

            <div class="col-md-4">
                <div class="form-group">
                    <label class="form-label text-uppercase">{{ $qtyLabel }}</label>
                    <input type="text" class="form-control" value="{{ round($logistics->so_qty) }}" readonly>
                </div>
            </div>

            <div class="col-md-4">
                <div class="form-group">
                    <label class="form-label text-uppercase">Commodity</label>
                    <input type="text" class="form-control" value="{{ $logistics->commodity }}" readonly>
                </div>
            </div>

            <div class="col-md-4">
                <div class="form-group">
                    <label class="form-label text-uppercase">{{ $tradeTermLabel }}</label>
                    <input type="text" class="form-control" value="{{ $logistics->sauda_type }}" readonly>
                </div>
            </div>

            <div class="col-md-4">
                <div class="form-group">
                    <label class="form-label text-uppercase">From Location</label>
                    <input type="text" class="form-control" value="{{ $fromLocation ?: 'N/A' }}" readonly>
                </div>
            </div>

            <div class="col-md-4">
                <div class="form-group">
                    <label class="form-label text-uppercase">Factory</label>
                    <input type="text" class="form-control" value="{{ $logistics->factory ?: 'N/A' }}" readonly>
                </div>
            </div>

            <div class="col-md-4">
                <div class="form-group">
                    <label class="form-label text-uppercase">{{ $isExport ? 'Port of Loading' : 'To Location' }}</label>
                    <input type="text" class="form-control" value="{{ $toLocation ?: 'N/A' }}" readonly>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label class="form-label text-uppercase">Customer</label>
                    <input type="text" class="form-control" value="{{ $logistics->customer }}" readonly>
                </div>
            </div>

            @if($isExport)
            <div class="col-md-3">
                <div class="form-group">
                    <label class="form-label text-uppercase">Job Order</label>
                    <input type="text" class="form-control" value="{{ $logistics->job_order }}" readonly>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label class="form-label text-uppercase">Return Port</label>
                    <input type="text" class="form-control" value="{{ $logistics->return_port }}" readonly>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label class="form-label text-uppercase">Booking No</label>
                    <input type="text" class="form-control" value="{{ $logistics->booking_no }}" readonly>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label class="form-label text-uppercase">Shipping Line</label>
                    <input type="text" class="form-control" value="{{ $logistics->shipping_line }}" readonly>
                </div>
            </div>
            @endif
        </div>

        <div class="row mt-4">
            <div class="col-12">
                <h6 class="header-heading-sepration text-uppercase">Logistics Items</h6>
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead class="thead-light">
                            <tr class="text-uppercase">
                                <th>Rate Type</th>
                                <th>Rate</th>
                                <th>{{ $partnerLabel }}</th>
                                <th>{{ $isExport ? 'No. of containers' : 'Qty' }}</th>
                                @if($isExport)
                                <th>Brand</th>
                                <th>Packing Size</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($logistics->items as $item)
                                <tr>
                                    <td>{{ $item->rate_type }}</td>
                                    <td>{{ number_format($item->rate, 2) }}</td>
                                    <td>{{ $item->transporter_name ?? $item->transporter?->company_name ?? $item->transporter?->name ?? 'N/A' }}</td>
                                    <td>{{ round($item->qty) }}</td>
                                    @if($isExport)
                                    <td>{{ $item->brand }}</td>
                                    <td>{{ $item->packing_size }}</td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@php
    $logisticsModule = $logistics->getApprovalModule();
    $logisticsApprovalLogs = $logisticsModule ? \App\Models\ApprovalsModule\ApprovalLog::where('record_id', $logistics->id)->where('module_id', $logisticsModule->id)->with(['user', 'role'])->orderBy('created_at', 'desc')->get() : collect();
@endphp

<div class="approval-view-wrapper">
    <x-approval-status :model="$logistics" :list-refresh="route('sales.get.logistics.list')" />
</div>

<style>
    .approval-view-wrapper .alert-primary,
    .approval-view-wrapper .alert-warning {
        display: none !important;
    }
    .current-status-tag {
        display: inline-flex;
        align-items: center;
        gap: 3px;
        font-size: 9px;
        font-weight: 700;
        color: #047857;
        background-color: #ecfdf5;
        border: 1px solid #a7f3d0;
        padding: 1px 7px;
        border-radius: 10px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        line-height: 1.4;
    }
    .current-status-dot {
        width: 5px;
        height: 5px;
        background-color: #10b981;
        border-radius: 50%;
        display: inline-block;
    }
</style>

@if ($logisticsApprovalLogs->isNotEmpty())
    <div class="approval-table-wrapper" style="margin-top: 25px; padding-bottom: 10px !important;">
        <div class="card border" style="box-shadow: none; margin-bottom: 0 !important;">
            <div class="card-header bg-light py-2 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 text-dark fw-bold" style="font-size: 14px;">
                    Approval History & Comments
                </h6>
                <span class="badge badge-info">{{ $logisticsApprovalLogs->count() }} {{ \Illuminate\Support\Str::plural('Action', $logisticsApprovalLogs->count()) }}</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-hover mb-0" style="font-size: 13px;">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 50px;" class="text-center">#</th>
                                <th style="min-width: 160px; width: 22%;">User</th>
                                <th style="min-width: 150px; width: 18%;" class="text-center">Action</th>
                                <th style="min-width: 160px; width: 20%;">Date & Time</th>
                                <th style="min-width: 300px; width: 40%;">Comments</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($logisticsApprovalLogs as $index => $log)
                                @php
                                    $badgeClass = match($log->action) {
                                        'approved' => 'badge-success',
                                        'rejected' => 'badge-danger',
                                        'reverted' => 'badge-warning',
                                        'partial_approved' => 'badge-info',
                                        default => 'badge-secondary'
                                    };
                                @endphp
                                <tr>
                                    <td class="text-center align-middle">{{ $index + 1 }}</td>
                                    <td class="align-middle">
                                        <strong>{{ $log->user->name ?? 'N/A' }}</strong>
                                        @if ($log->user_id === auth()->id())
                                            <span class="badge badge-primary ms-1" style="font-size: 10px;">You</span>
                                        @endif
                                    </td>
                                    <td class="text-center align-middle">
                                        <span class="badge {{ $badgeClass }} text-uppercase px-2 py-1" style="font-size: 11px;">
                                            {{ str_replace('_', ' ', $log->action) }}
                                        </span>
                                        @if ($loop->first)
                                            <div class="mt-1">
                                                <span class="current-status-tag">
                                                    <span class="current-status-dot"></span> Current
                                                </span>
                                            </div>
                                        @endif
                                    </td>
                                    <td class="align-middle">
                                        {{ $log->created_at ? $log->created_at->format('d M, Y h:i A') : 'N/A' }}
                                    </td>
                                    <td class="align-middle">
                                        @if (!empty(trim($log->comments ?? '')))
                                            <span class="text-dark">{{ $log->comments }}</span>
                                        @else
                                            <span class="text-muted fst-italic">No comments</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div style="height: 60px; width: 100%; clear: both;"></div>
    </div>
@endif

<div class="row bottom-button-bar">
    <div class="col-12 text-right">
        <a type="button" class="btn btn-danger modal-sidebar-close position-relative top-1 closebutton">Close</a>
    </div>
</div>
