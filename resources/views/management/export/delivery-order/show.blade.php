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
        <h6 class="header-heading-sepration">Delivery Order Details (View Mode)</h6>
        <div class="row">
            <div class="col-md-4">
                <div class="form-group">
                    <label>Buyer:</label>
                    <input type="text" class="form-control" value="{{ $deliveryOrder->buyer->name ?? 'N/A' }}" readonly>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label>Export Order:</label>
                    <input type="text" class="form-control" value="#{{ $deliveryOrder->exportOrder->voucher_no ?? 'N/A' }}" readonly>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label>Linked Form-E:</label>
                    <input type="text" class="form-control" value="{{ $deliveryOrder->exportFormE->form_e_no ?? ($deliveryOrder->exportFormE ? 'FE-'.$deliveryOrder->exportFormE->id : 'N/A') }}" readonly>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Export Order Snapshot Area (Read-Only) -->
<div id="exportOrderSnapshotShow" class="snapshot-area" style="pointer-events: none; opacity: 0.9;">
    <div class="row form-mar">
        <div class="col-8">
            <h6 class="header-heading-sepration">Basic Information</h6>
            <div class="row">
                <div class="col-md-6">
                    <label>Sauda# / Contract No#:</label>
                    <input type="text" class="form-control" value="{{ $exportOrderData['voucher_no'] ?? '' }}" readonly>
                </div>
                <div class="col-md-6">
                    <label>Contract Date:</label>
                    <input type="text" class="form-control" value="{{ isset($exportOrderData['voucher_date']) ? \Carbon\Carbon::parse($exportOrderData['voucher_date'])->format('Y-m-d') : '' }}" readonly>
                </div>
            </div>
            <!-- ... more fields from snapshot ... -->
        </div>
        <div class="col-4">
            <h6 class="header-heading-sepration">Export Details</h6>
            <table class="table table-bordered table-sm">
                <tr><td><strong>Incoterm</strong></td><td>{{ $exportOrderData['incoterm']['name'] ?? 'N/A' }}</td></tr>
                <tr><td><strong>Packing Type</strong></td><td>{{ $exportOrderData['packing_type'] ?? 'N/A' }}</td></tr>
                <!-- Add more as needed -->
            </table>
        </div>
    </div>
</div>

<!-- Editable Remarks -->
<div class="row form-mar mt-3">
    <div class="col-md-12">
        <div class="form-group">
            <label>Remarks:</label>
            <textarea class="form-control" rows="3" readonly>{{ $deliveryOrder->remarks }}</textarea>
        </div>
    </div>
</div>

<div class="row bottom-button-bar mt-3">
    <div class="col-12 mb-3">
        <a type="button" class="btn btn-danger modal-sidebar-close position-relative top-1 closebutton">Close</a>
    </div>
</div>
