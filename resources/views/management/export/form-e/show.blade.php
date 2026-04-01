<style>
    /* Chrome, Safari, Edge, Opera */
    input[type=number]::-webkit-outer-spin-button,
    input[type=number]::-webkit-inner-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }

    /* Firefox */
    input[type=number] {
        -moz-appearance: textfield;
    }

    .spacing-table td {
        padding-top: 15px !important;
        padding-bottom: 15px !important;
    }
    .snapshot-area {
        opacity: 0.9;
    }
</style>

<div class="row form-mar">
    <div class="col-md-12">
        <h6 class="header-heading-sepration">Form-E Details (View Mode)</h6>
        <div class="row">
            <div class="col-md-4">
                <div class="form-group">
                    <label>Buyer:</label>
                    <input type="text" class="form-control" value="{{ $formE->buyer->name ?? 'N/A' }}" readonly>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label>Export Order:</label>
                    <input type="text" class="form-control" value="#{{ $formE->exportOrder->voucher_no ?? 'N/A' }}" readonly>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label>Job Order:</label>
                    <input type="text" class="form-control" value="{{ $formE->jobOrder->job_order_no ?? 'N/A' }}" readonly>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>Form-E No:</label>
                    <input type="text" class="form-control" value="{{ $formE->form_e_no ?? 'N/A' }}" readonly>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label>Form-E Date:</label>
                    <input type="text" class="form-control" value="{{ $formE->form_e_date ?? 'N/A' }}" readonly>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label>Attachment:</label>
                    <div class="mt-1">
                        @if($formE->attachment)
                            <a href="{{ asset('storage/'.$formE->attachment) }}" target="_blank" class="btn btn-xs btn-outline-info">View File</a>
                        @else
                            <span class="text-muted">No Attachment</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Export Order Snapshot Area (Read-Only) -->
<div id="exportOrderSnapshotShow" class="snapshot-area" style="pointer-events: none; opacity: 0.9;">
    <div class="row form-mar">
        <div class="col-12">
            <h6 class="header-heading-sepration">Product Details</h6>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Commodity/Product:</label>
                        <input type="text" id="snap_product_name_show" class="form-control" value="{{ $exportOrderData['product']['name'] ?? 'N/A' }}" readonly>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Visual Name:</label>
                        <input type="text" id="snap_visual_name_show" class="form-control" value="{{ $exportOrderData['visual_name'] ?? 'N/A' }}" readonly>
                    </div>
                </div>
            </div>

            <!-- Specifications Section -->
            @if(!empty($exportOrderData['specifications']))
            <div class="mt-2" id="snap_specificationsSection_show">
                <h6 class="header-heading-sepration">Specifications</h6>
                <div id="snap_productSpecs_show">
                    <table class="table table-bordered table-sm">
                        @foreach($exportOrderData['specifications'] as $spec)
                            <tr>
                                <td width="50%"><strong>{{ $spec['spec_name'] }}</strong></td>
                                <td>{{ $spec['spec_value'] }} {{ $spec['uom'] ?? '' }}</td>
                            </tr>
                        @endforeach
                    </table>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

<!-- Quantity Details -->
<div class="row form-mar mt-3">
    <div class="col-md-12">
        <h6 class="header-heading-sepration">Quantity Details</h6>
        <div class="row">
            <div class="col-md-4">
                <div class="form-group">
                    <label>Total Quantity (MT):</label>
                    <input type="text" class="form-control" value="{{ $formE->total_quantity ?? 0 }}" readonly>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label>Remaining After This (MT):</label>
                    <input type="text" class="form-control" value="{{ $formE->remaining_quantity ?? 0 }}" readonly>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label>Input Quantity (MT):</label>
                    <input type="text" class="form-control" value="{{ $formE->input_quantity ?? 0 }}" readonly>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row bottom-button-bar mt-3">
    <div class="col-12 mb-3">
        <a type="button" class="btn btn-danger modal-sidebar-close position-relative top-1 closebutton">Close</a>
    </div>
</div>
