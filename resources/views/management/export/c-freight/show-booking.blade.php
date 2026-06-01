<div class="row">
    @if($cFreight->status != 'Booked')
        <div class="col-md-12 mt-2">
            <div class="alert alert-warning text-center">
                <strong><i class="ft-alert-circle"></i> No Booking Record!</strong> This freight request has not been booked yet.
            </div>
        </div>
    @else
        <!-- Row 1 -->
        <div class="col-md-3">
            <div class="form-group">
                <label>Booking No</label>
                <input type="text" class="form-control" value="{{ $cFreight->booking_no }}" readonly>
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label>Bill of Lading Number</label>
                <input type="text" class="form-control" value="{{ $cFreight->bl_number }}" readonly>
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label>Quantity</label>
                <input type="text" class="form-control" value="{{ $cFreight->quantity }}" readonly>
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label>Shipping Line</label>
                <input type="text" class="form-control" value="{{ $cFreight->shipping_line }}" readonly>
            </div>
        </div>

        <!-- Row 2 -->
        <div class="col-md-3">
            <div class="form-group">
                <label>Through Logistic</label>
                <input type="text" class="form-control" value="{{ $cFreight->through_logistic }}" readonly>
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label>T/S</label>
                <input type="text" class="form-control" value="{{ $cFreight->t_s }}" readonly>
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label>POD (Port of Discharge)</label>
                <input type="text" class="form-control" value="{{ $cFreight->exportOrder->portOfDischarge->name ?? '' }}" readonly>
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label>Return Port</label>
                <input type="text" class="form-control" value="{{ $cFreight->return_port }}" readonly>
            </div>
        </div>

        <!-- Row 3 -->
        <div class="col-md-3">
            <div class="form-group">
                <label>Vessel Name</label>
                <input type="text" class="form-control" value="{{ $cFreight->vessel_name }}" readonly>
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label>Cutoff SI</label>
                <input type="text" class="form-control" value="{{ $cFreight->cutoff_si ? \Carbon\Carbon::parse($cFreight->cutoff_si)->format('d-M-Y') : '' }}" readonly>
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label>Cutoff CY</label>
                <input type="text" class="form-control" value="{{ $cFreight->cutoff_cy ? \Carbon\Carbon::parse($cFreight->cutoff_cy)->format('d-M-Y') : '' }}" readonly>
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label>ETD</label>
                <input type="text" class="form-control" value="{{ $cFreight->etd ? \Carbon\Carbon::parse($cFreight->etd)->format('d-M-Y') : '' }}" readonly>
            </div>
        </div>

        <!-- Row 4 -->
        <div class="col-md-3">
            <div class="form-group">
                <label>ETA</label>
                <input type="text" class="form-control" value="{{ $cFreight->eta ? \Carbon\Carbon::parse($cFreight->eta)->format('d-M-Y') : '' }}" readonly>
            </div>
        </div>
    @endif
    
    <div class="col-md-12 text-right mt-2">
        <button type="button" class="btn btn-secondary modal-sidebar-close">Close</button>
    </div>
</div>
