@php
    $receivedQty = $data->stocks()->get()->sum('qty');
@endphp
<form action="{{ route('store.purchase-order-receiving.store', $data->id) }}" method="POST" id="ajaxSubmit"
    autocomplete="off">
    @csrf
    @method('POST')
    <input type="hidden" id="listRefresh" value="{{ route('store.get.purchase-order-receiving') }}" />
    <input type="hidden" name="data_id" value="{{ $data->id }}">
    <input type="hidden" name="item_id" value="{{ $data->item_id }}">
    <input type="hidden" name="total_amount" value="{{ $data->total }}">
    <input type="hidden" name="purchase_order_data_id" value="{{ $data->id }}">
    <input type="hidden" name="purchase_order_id" value="{{ $data->purchase_order->id ?? null }}">
    <input type="hidden" name="supplier_id" value="{{ $data->supplier_id }}">
    <input type="hidden" name="location_id"
        value="{{ optional($data->purchase_request_data->purchase_request)->location_id }}">
    <input type="hidden" name="location_code"
        value="{{ optional($data->purchase_request_data->purchase_request)->location->code }}">
    <div class="row form-mar">
        <div class="col-md-6">
            <div class="form-group">
                <label>Purchase Request:</label>
                <select disabled class="form-control" name="purchase_request_id">
                    <option value="{{ optional($data->purchase_request_data->purchase_request)->id }}">
                        {{ optional($data->purchase_request_data->purchase_request)->purchase_request_no }}</option>
                </select>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label>Reference No:</label>
                <input readonly type="text" name="reference_no" id="reference_no"
                    value="{{ optional($data->purchase_request_data->purchase_request)->purchase_request_no }}"
                    class="form-control">
            </div>
        </div>
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Description (Optional):</label>
                <textarea readonly name="description" id="description" placeholder="Description" class="form-control">{{ optional($data->purchase_quotation)->description }}</textarea>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <h6 class="header-heading-sepration">
                Item Details
            </h6>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label>Category:</label>
                <select disabled class="form-control">
                    <option value="{{ $data->category_id }}">
                        {{ optional($data->category)->name ?? 'N/A' }}
                    </option>
                </select>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label>Item:</label>
                <select disabled class="form-control">
                    <option value="{{ $data->item_id }}">
                        {{ optional($data->item)->name ?? 'N/A' }}
                    </option>
                </select>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                <label>Vendor:</label>
                <select disabled class="form-control">
                    <option value="{{ $data->supplier_id }}">
                        {{ optional($data->supplier)->name ?? 'N/A' }}
                    </option>
                </select>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label>Item UOM:</label>
                <input readonly type="text" value="{{ get_uom($data->item_id) }}" class="form-control">
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-4">
            <div class="form-group">
                <label>Total Qty:</label>
                <input readonly type="number" value="{{ $data->qty }}" class="form-control">
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label>Ordered Qty:</label>
                <input readonly type="number" value="{{ $receivedQty }}" class="form-control">
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label>Remaining Qty:</label>
                <input readonly type="number" value="{{ $data->qty - $receivedQty }}" class="form-control">
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label>Ordered Qty:</label>
                <input readonly type="number" value="{{ $data->qty }}" class="form-control">
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label>Rate:</label>
                <input readonly type="number" value="{{ $data->rate }}" class="form-control">
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label>Total Amount:</label>
                <input readonly type="number" value="{{ $data->total }}" class="form-control">
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                <label>Receiving Qty: <span class="text-danger">*</span></label>
                <input type="number" name="receiving_qty" id="receiving_qty" class="form-control" step="0.01"
                    min="0" required
                    placeholder="Enter quantity received (max: {{ $data->qty - $receivedQty }})">
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label>Remarks:</label>
                <input readonly type="text" value="{{ $data->remarks }}" class="form-control">
            </div>
        </div>
    </div>

    <div class="row bottom-button-bar mt-3">
        <div class="col-12">
            <a type="button" class="btn btn-danger modal-sidebar-close position-relative top-1 closebutton">Close</a>
            <button type="submit" class="btn btn-primary submitbutton">Save</button>
        </div>
    </div>
</form>
