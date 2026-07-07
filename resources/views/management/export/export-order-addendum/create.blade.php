<form action="{{ route('export-order-addendum.store') }}" method="POST" id="ajaxSubmit" autocomplete="off">
    @csrf
    <input type="hidden" id="listRefresh" value="{{ route('export-order-addendum.getList') }}" />

    <div class="row form-mar">
        <div class="col-12">
            <div class="form-group">
                <label>Export Order <span class="text-danger">*</span></label>
                <select name="export_order_id" class="form-control" required>
                    <option value="">Select Export Order</option>
                    @foreach($exportOrders as $eo)
                        <option value="{{ $eo->id }}">{{ $eo->voucher_no }} ({{ $eo->buyer->name ?? 'No Buyer' }})</option>
                    @endforeach
                </select>
            </div>
            
            <div class="form-group mt-2">
                <label>Remarks</label>
                <textarea name="remarks" class="form-control" rows="4" placeholder="Enter reason for addendum..."></textarea>
            </div>
        </div>
        <div class="bottom-button-bar mt-2">
            <div class="col-12 text-right">
                <a type="button" class="btn btn-danger modal-sidebar-close position-relative top-1 closebutton">Close</a>
                <button type="submit" class="btn btn-primary submitbutton">Save</button>
            </div>
        </div>
    </div>
</form>


