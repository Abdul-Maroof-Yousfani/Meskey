<div class="row form-mar">
    <div class="col-12">
        <h6 class="header-heading-sepration">Addendum Details</h6>
        
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label>Export Order:</label>
                    <input type="text" class="form-control" value="{{ $addendum->exportOrder?->voucher_no ?? '-' }}" readonly>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label>Buyer Name:</label>
                    <input type="text" class="form-control" value="{{ $addendum->exportOrder?->buyer?->name ?? '-' }}" readonly>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label>Product:</label>
                    <input type="text" class="form-control" value="{{ $addendum->exportOrder?->product?->name ?? '-' }}" readonly>
                </div>
            </div>
            {{-- <div class="col-md-6">
                <div class="form-group">
                    <label>Status:</label>
                    <input type="text" class="form-control" value="{{ ucfirst($addendum->am_approval_status) }}" readonly>
                </div>
            </div> --}}
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label>Remarks:</label>
                    <textarea class="form-control" rows="4" readonly>{{ $addendum->remarks }}</textarea>
                </div>
            </div>
        </div>
    </div>
</div>
