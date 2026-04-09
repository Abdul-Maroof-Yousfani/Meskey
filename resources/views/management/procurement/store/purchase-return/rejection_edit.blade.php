<style>
    html, body {
        overflow-x: hidden;
    }
    #rejectionReturnTable .select2-container {
        width: 100% !important;
    }
    input:disabled {
        background-color: white !important;
        color: #495057 !important;
        cursor: not-allowed;
    }
</style>

<form style="overflow-x: hidden;" action="{{ route('store.rejection-return.update', $rejectionReturn->id) }}" method="POST" id="ajaxSubmit" autocomplete="off">
    @csrf
    @method('PUT')
    <input type="hidden" id="listRefresh" value="{{ route('store.rejection-return.getList') }}" />
    
    <div class="row form-mar">
        <div class="col-md-4">
            <div class="form-group">
                <label class="form-label">Select GRN:<span class="text-danger">*</span></label>
                <select name="grn_id" id="grn_id" class="form-control select2" required disabled>
                    @foreach($approvedGRNs ?? [] as $grn)
                        <option value="{{ $grn->id }}" {{ $rejectionReturn->grn_id == $grn->id ? 'selected' : '' }}>
                            {{ $grn->purchase_order_receiving_no }} - {{ $grn->supplier->name ?? '' }}
                        </option>
                    @endforeach
                </select>
                <input type="hidden" name="grn_id" value="{{ $rejectionReturn->grn_id }}">
                <small class="text-info">GRN cannot be changed during edit.</small>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="form-group">
                <label class="form-label">Return Date:<span class="text-danger">*</span></label>
                <input type="date" name="purchase_date" class="form-control" id="purchase_date" value="{{ $rejectionReturn->date }}">
            </div>
        </div>

        <div class="col-md-4">
            <div class="form-group">
                <label class="form-label">Reference No:</label>
                <input type="text" name="reference_no" id="reference_no" class="form-control" value="{{ $rejectionReturn->reference_no }}" readonly>
            </div>
        </div>

        <div class="col-md-4">
            <div class="form-group">
                <label class="form-label">Truck No:</label>
                <input type="text" name="truck_no" id="truck_no" class="form-control" value="{{ $rejectionReturn->truck_no }}">
            </div>
        </div>

        <div class="col-xs-12 col-sm-12 col-md-12 mt-3">
            <div class="form-group">
                <label class="form-label">Remarks (Optional):</label>
                <textarea name="remarks" placeholder="Overall remarks" class="form-control" rows="2">{{ $rejectionReturn->remarks }}</textarea>
            </div>
        </div>
    </div>

    <div class="row form-mar" id="itemSection">
        <div class="col-md-12">
            <div style="overflow-x: auto; width: 100%;">
                <table class="table table-bordered text-left" id="rejectionReturnTable" style="width:100%;">
                    <thead>
                        <tr>
                            <th style="min-width: 400px;" class="text-left">Item</th>
                            <th style="min-width: 200px;" class="text-left">Rejected Qty</th>
                            <th style="min-width: 200px;" class="text-left">Weight (grams)</th>
                        </tr>
                    </thead>
                    <tbody id="rejectionReturnBody">
                        @foreach($rejectionReturn->items as $item)
                        <tr>
                            <td class="text-left">
                                <select class="form-control select2" disabled>
                                    <option value="{{ $item->item_id }}" selected>{{ $item->item->name ?? 'N/A' }}</option>
                                </select>
                                <input type="hidden" name="item_id[]" value="{{ $item->item_id }}">
                            </td>
                            <td class="text-left">
                                <input type="text" class="form-control text-left" value="{{ $item->quantity }}" disabled>
                                <input type="hidden" name="qty[]" value="{{ $item->quantity }}">
                            </td>
                            <td class="text-left">
                                <input type="number" name="weight[]" step="0.01" class="form-control text-left" value="{{ $item->weight }}" placeholder="Weight (grams)">
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="row bottom-button-bar">
        <div class="col-12 text-end">
            <a type="button" class="btn btn-danger modal-sidebar-close position-relative top-1 closebutton me-2">Close</a>
            <button type="submit" class="btn btn-primary submitbutton">Update</button>
        </div>
    </div>
</form>

<script>
    $(document).ready(function () {
        $('.select2').select2({ width: '100%' });
    });
</script>
