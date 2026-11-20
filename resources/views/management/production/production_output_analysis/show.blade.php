@php
    $request = $item->analysisRequest;
    $requestAlert = $request?->outputAlert;
@endphp
<div class="row form-mar">
    <!-- Header Information (same layout as the create form) -->
    <div class="col-md-12">
        <h6 class="header-heading-sepration">Output Analysis Information</h6>
        <div class="row">
            <div class="col-md-3">
                <div class="form-group">
                    <label>Date:</label>
                    <input type="text" class="form-control" value="{{ \Carbon\Carbon::parse($item->analysis_date)->format('d-m-Y') }}" readonly>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>Analysis Request:</label>
                    <input type="text" class="form-control" value="{{ $request?->request_no ?? '-' }}" readonly>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>Job Order:</label>
                    <input type="text" class="form-control" value="{{ $item->jobOrders->pluck('job_order_no')->implode(', ') ?: ($request?->voucher_display ?? '-') }}" readonly>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>Product (Commodity):</label>
                    <input type="text" class="form-control" value="{{ $item->product->name ?? 'N/A' }}" readonly>
                </div>
            </div>

            <!-- Plant / location summary -->
            <div class="col-12 mt-1 mb-3">
                <div class="request-details-card">
                    <h5 class="request-plant-heading">
                        <i class="ft-cpu mr-1"></i> Plant: <span>{{ $item->plant->name ?? 'N/A' }}</span>
                    </h5>
                    <div class="request-details-line">
                        <i class="ft-map-pin text-primary mr-1"></i> Location: <strong>{{ $item->location->name ?? 'N/A' }}</strong> &bull;
                        <i class="ft-navigation text-info mr-1 ml-2"></i> Arrival: <span>{{ $item->arrivalLocation->name ?? 'N/A' }}</span>
                        @if($request?->voucher_display)
                            &bull; <i class="ft-file-text text-warning mr-1 ml-2"></i> Voucher: <strong>{{ $request->voucher_display }}</strong>
                        @endif
                    </div>
                    @if($requestAlert?->remarks)
                        <div class="request-alert-remarks mt-2">
                            <div class="font-weight-bold text-warning">
                                <i class="ft-alert-triangle mr-1"></i> Re-check for QC Alert {{ $requestAlert->alert_no }}
                            </div>
                            <div class="request-alert-remarks-text">{{ $requestAlert->remarks }}</div>
                            <small class="text-muted">{{ trim(($requestAlert->remarkedBy?->name ?? '') . ' ' . ($requestAlert->remarked_at?->format('d-m-Y h:i A') ?? '')) }}</small>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Extra Fields for Output Analysis -->
        <div class="row">
            <div class="col-md-4">
                <div class="form-group">
                    <label>Milling Degree:</label>
                    <input type="text" class="form-control text-capitalize" value="{{ $item->milling_degree ?? 'N/A' }}" readonly>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label>Stitching:</label>
                    <input type="text" class="form-control text-capitalize" value="{{ $item->inner_stitching ?? 'N/A' }}" readonly>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label>Outer Stitching:</label>
                    <input type="text" class="form-control text-capitalize" value="{{ $item->outer_stitching ?? 'N/A' }}" readonly>
                </div>
            </div>
        </div>
    </div>

    <!-- QC Checklist -->
    <div class="col-md-12 mt-3">
        <h6 class="header-heading-sepration font-weight-bold mb-3">
            QC Checklist
            @if($alert)
                <span class="badge {{ $alert->isPending() ? 'badge-danger' : 'badge-success' }} ml-1"
                      title="{{ $alert->isPending() ? 'Waiting for plant remarks' : 'Remarks submitted' }}">
                    <i class="ft-alert-triangle"></i> {{ $alert->alert_no }}
                </span>
            @endif
        </h6>
        @include('management.production.production_output_analysis.partials.qc_checklist_readonly', ['checklistRows' => $checklistRows, 'showSpec' => $hasSpecs])
    </div>

    <!-- Remarks -->
    <div class="col-md-12 mt-4">
        <div class="form-group">
            <label>Remarks:</label>
            <textarea class="form-control" rows="3" readonly>{{ $item->remarks ?: 'No remarks provided.' }}</textarea>
        </div>
    </div>
</div>

<div class="row bottom-button-bar">
    <div class="col-12 text-right">
        <button type="button" class="btn btn-danger modal-sidebar-close closebutton">Close</button>
    </div>
</div>

<style>
    .request-details-card { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 10px 14px; }
    .request-details-card .request-plant-heading { font-weight: 700; margin: 0 0 4px; }
    .request-details-card .request-details-line { font-size: 0.9rem; color: #475569; }
    .request-alert-remarks { border-top: 1px dashed #e2e8f0; padding-top: 8px; }
    .request-alert-remarks .request-alert-remarks-text { white-space: pre-line; margin: 2px 0; }
    .layout-dark .request-details-card { background: #1a1a1a; border-color: #404040; }
    .layout-dark .request-details-card .request-details-line { color: #cbd5e1; }
    .layout-dark .request-alert-remarks { border-top-color: #404040; }
</style>
