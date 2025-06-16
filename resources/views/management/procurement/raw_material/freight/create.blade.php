<div id="freightFormContainer">
    <form action="{{ route('raw-material.freight.store') }}" method="POST" id="ajaxSubmit" autocomplete="off"
        enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="arrival_purchase_order_id" value="{{ $purchaseOrder->id }}" />
        <input type="hidden" id="listRefresh" value="{{ route('raw-material.get.freight') }}" />

        <div class="row form-mar">
            <div class="col-md-6">
                <div class="form-group">
                    <label>Contract #</label>
                    <input type="text" class="form-control" value="{{ $purchaseOrder->contract_no }}" readonly />
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label>Loading Date</label>
                    <input type="date" name="loading_date" class="form-control" max="{{ date('Y-m-d') }}" />
                </div>
            </div>

            <div class="col-md-6">
                <div class="form-group">
                    <label>Supplier Name</label>
                    <input type="text" name="supplier_name" class="form-control"
                        value="{{ $purchaseOrder->supplier->name ?? '' }}" readonly />
                </div>
            </div>
            @if ($purchaseOrder->broker_one_id)
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Broker One</label>
                        <input type="text" name="broker_one" class="form-control"
                            value="{{ $purchaseOrder->broker_one_name ?? '' }}" readonly />
                    </div>
                </div>
            @endif

            @if ($purchaseOrder->broker_two_id)
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Broker Two</label>
                        <input type="text" name="broker_two" class="form-control"
                            value="{{ $purchaseOrder->broker_two_name ?? '' }}" readonly />
                    </div>
                </div>
            @endif

            @if ($purchaseOrder->broker_three_id)
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Broker Three</label>
                        <input type="text" name="broker_three" class="form-control"
                            value="{{ $purchaseOrder->broker_three_name ?? '' }}" readonly />
                    </div>
                </div>
            @endif

            <div class="col-md-6">
                <div class="form-group">
                    <label>Truck No</label>
                    <input type="text" name="truck_no" class="form-control" />
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label>Bilty No</label>
                    <input type="text" name="bilty_no" class="form-control" />
                </div>
            </div>

            <div class="col-md-6">
                <div class="form-group">
                    <label>Station</label>
                    <select name="station" id="station_id" class="form-control">
                        <option value="" hidden>Select Station</option>
                    </select>
                </div>
            </div>

            <div class="col-md-6">
                <div class="form-group">
                    <label>No of Bags</label>
                    <input type="number" name="no_of_bags" class="form-control" />
                </div>
            </div>

            <div class="col-md-6">
                <div class="form-group">
                    <label>Bag Condition</label>
                    <select class="form-control" name="bag_condition_id">
                        <option value="">Select Bag Condition</option>
                        @foreach ($bagTypes as $bagType)
                            <option value="{{ $bagType->id }}">{{ $bagType->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="col-md-6">
                <div class="form-group">
                    <label>Commodity</label>
                    <input type="text" name="commodity" class="form-control"
                        value="{{ $purchaseOrder->product->name ?? '' }}" readonly />
                </div>
            </div>

            <div class="col-md-6">
                <div class="form-group">
                    <label>Loading Weight (kg)</label>
                    <input type="number" step="0.01" name="loading_weight" class="form-control" />
                </div>
            </div>

            <div class="col-md-6">
                <div class="form-group">
                    <label>Kanta Charges</label>
                    <input type="number" step="0.01" name="kanta_charges" class="form-control" value="0" />
                </div>
            </div>

            <div class="col-md-6">
                <div class="form-group">
                    <label>Freight on Bilty</label>
                    <input type="number" step="0.01" name="freight_on_bilty" class="form-control" value="0" />
                </div>
            </div>

            <div class="col-md-6">
                <div class="form-group">
                    <label>Advance Freight</label>
                    <input type="number" step="0.01" name="advance_freight" class="form-control"
                        value="0" />
                </div>
            </div>

            <div class="col-12">
                <h6 class="header-heading-sepration">
                    Document Attachments
                </h6>
                <div class="alert alert-info">
                    <strong>Attachment Guidelines:</strong>
                    <ul class="mb-0">
                        <li>Allowed formats: JPEG, PNG, JPG, PDF</li>
                        <li>Images will be automatically compressed</li>
                        <li>Maximum file size: 5MB per file</li>
                    </ul>
                </div>
            </div>

            <div class="col-md-4">
                <div class="form-group">
                    <label>Bilty Slip</label>
                    <input type="file" name="bilty_slip" class="form-control-file" />
                </div>
            </div>

            <div class="col-md-4">
                <div class="form-group">
                    <label>Weighbridge Slip</label>
                    <input type="file" name="weighbridge_slip" class="form-control-file" />
                </div>
            </div>

            <div class="col-md-4">
                <div class="form-group">
                    <label>Supplier Bill</label>
                    <input type="file" name="supplier_bill" class="form-control-file" />
                </div>
            </div>
        </div>

        <div class="row bottom-button-bar">
            <div class="col-12">
                <a type="button"
                    class="btn btn-danger modal-sidebar-close position-relative top-1 closebutton">Close</a>
                <button type="submit" class="btn btn-primary submitbutton">Save</button>
            </div>
        </div>
    </form>

</div>

<script>
    $(document).ready(function() {
        $('.select2').select2();

        initializeDynamicSelect2('#station_id', 'stations', 'name', 'name', true, false);
    });
</script>
