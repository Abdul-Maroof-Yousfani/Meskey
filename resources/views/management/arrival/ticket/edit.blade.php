<form action="{{ route('ticket.update', $arrivalTicket->id) }}" method="POST" id="ajaxSubmit" autocomplete="off">
    @csrf
    @method('PUT')
    <input type="hidden" id="listRefresh" value="{{ route('get.ticket') }}" />
    <div class="row form-mar">


        <div class="col-xs-6 col-sm-6 col-md-6">
            <fieldset>
                <div class="input-group">
                    <div class="input-group-prepend">
                        <button class="btn btn-primary" type="button">Ticket No#</button>
                    </div>
                    <input type="text" disabled class="form-control" value="{{ $arrivalTicket->unique_no }}">
                </div>
            </fieldset>
        </div>

        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group ">
                <label>Product:</label>
                <select name="product_id" id="product_id" class="form-control select2">
                    <option value="{{ $arrivalTicket->product->id }}">{{ $arrivalTicket->product->name }}</option>
                    <option value="">Product Name</option>
                </select>
            </div>
        </div>
        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group ">
                <label>Supplier:</label>
                <select name="supplier_name" id="supplier_name" class="form-control select2">
                    <option value="{{ $arrivalTicket->supplier_name }}">{{ $arrivalTicket->supplier_name }}</option>
                </select>
            </div>
        </div>

        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group ">
                <label>Broker:</label>
                <select name="broker_name" id="broker_name" class="form-control select2">
                    <option value="{{ $arrivalTicket->broker_name }}">{{ $arrivalTicket->broker_name }}</option>
                </select>
            </div>
        </div>
        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group ">
                <label>Accounts Of:</label>
                <select name="accounts_of" id="accounts_of" class="form-control select2">
                    <option value="{{ $arrivalTicket->accounts_of_id }}">{{ $arrivalTicket->accounts_of_id }}</option>
                </select>
            </div>
        </div>
        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group ">
                <label>Station:</label>
                <input type="text" name="station_id" placeholder="Station" class="form-control" autocomplete="off"
                    value="{{ $arrivalTicket->station->name ?? 'N/A' }}" />
            </div>
        </div>
        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group ">
                <label>Truck Type:</label>
                <select name="arrival_truck_type_id" id="arrival_truck_type_id" class="form-control select2">
                    <option value="">Truck Type</option>

                    @foreach (getTableData('arrival_truck_types', ['id', 'name', 'sample_money']) as $arrival_truck_types)
                        <option data-samplemoney="{{ $arrival_truck_types->sample_money ?? 0 }}"
                            @selected($arrivalTicket->truck_type_id == $arrival_truck_types->id) value="{{ $arrival_truck_types->id }}">
                            {{ $arrival_truck_types->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>


        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group ">
                <label>Sample Money Type :</label>
                <select name="sample_money_type" class="form-control">
                    <option value="">Select Type</option>
                    <option {{ $arrivalTicket->sample_money_type == 'n/a' ? 'selected' : '' }} value="n/a">
                        N/A</option>
                    <option {{ $arrivalTicket->sample_money_type == 'single' ? 'selected' : '' }} value="single">
                        Single</option>
                    <option {{ $arrivalTicket->sample_money_type == 'double' ? 'selected' : '' }} value="double">
                        Double</option>
                </select>
            </div>
        </div>
        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group ">
                <label>Sample Money:</label>
                <input type="text" name="sample_money" readonly value="{{ $arrivalTicket->sample_money }}"
                    placeholder="Sample money" class="form-control" autocomplete="off" />
            </div>
        </div>
        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group ">
                <label>Truck No:</label>
                <input type="text" name="truck_no" value="{{ $arrivalTicket->truck_no }}" placeholder="Truck No"
                    class="form-control" autocomplete="off" />
            </div>
        </div>
        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group ">
                <label>Bilty No: </label>
                <input type="text" name="bilty_no" value="{{ $arrivalTicket->bilty_no }}" placeholder="Bilty No"
                    class="form-control" autocomplete="off" />
            </div>
        </div>
        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group ">
                <label>No of bags: </label>
                <input type="text" name="bags" placeholder="No of bags" class="form-control" autocomplete="off"
                    value="{{ $arrivalTicket->bags }}" />
            </div>
        </div>

        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group ">
                <label>LOading Date: (Optional)</label>
                <input type="date" name="loading_date" value="{{ $arrivalTicket->loading_date }}"
                    placeholder="Bilty No" class="form-control" autocomplete="off" />
            </div>
        </div>
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group ">
                <label>Remarks (Optional):</label>
                <textarea name="remarks" row="2" class="form-control" placeholder="Remarks">{{ $arrivalTicket->remarks }}</textarea>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-12">
            <h6 class="header-heading-sepration">
                Weight Detail
            </h6>
        </div>
        <div class="col-xs-4 col-sm-4 col-md-4">
            <div class="form-group ">
                <label>1st Weight:</label>
                <input type="text" name="first_weight" placeholder="First Weight" class="form-control"
                    autocomplete="off" value="{{ $arrivalTicket->first_weight }}" />
            </div>
        </div>
        <div class="col-xs-4 col-sm-4 col-md-4">
            <div class="form-group ">
                <label>Second Weight: </label>
                <input type="text" name="second_weight" placeholder="Second Weight" class="form-control"
                    autocomplete="off" value="{{ $arrivalTicket->second_weight }}" />
            </div>
        </div>
        <div class="col-xs-4 col-sm-4 col-md-4">
            <div class="form-group ">
                <label>Net Weight: </label>
                <input type="text" name="net_weight" placeholder="Net Weight" class="form-control"
                    autocomplete="off" value="{{ $arrivalTicket->net_weight }}" />
            </div>
        </div>
    </div>


    <div class="row bottom-button-bar">
        <div class="col-12">
            <a type="button" class="btn btn-danger modal-sidebar-close position-relative top-1 closebutton">Close</a>
            <button type="submit" class="btn btn-primary submitbutton">Save</button>
        </div>
    </div>
</form>



<script>
    function calculateSampleMoney() {
        let truckTypeSelect = $('[name="arrival_truck_type_id"]');
        let sampleMoney = truckTypeSelect.find(':selected').data('samplemoney') || 0;

        let holidayType = $('[name="sample_money_type"]').val();

        if (holidayType === 'double') {
            sampleMoney = sampleMoney * 2;
        }

        $('input[name="sample_money"]').val(sampleMoney || 0);
    }

    $(document).ready(function() {
        calculateSampleMoney();

        $(document).on('change', '[name="arrival_truck_type_id"]', calculateSampleMoney);

        $(document).on('change', '[name="sample_money_type"]', calculateSampleMoney);
    });




    $(document).ready(function() {
        initializeDynamicSelect2('#product_id', 'products', 'name', 'id', false, false);
        initializeDynamicSelect2('#supplier_name', 'suppliers', 'name', 'name', true, false);
        initializeDynamicSelect2('#broker_name', 'brokers', 'name', 'name', true, false);
        initializeDynamicSelect2('#accounts_of', 'suppliers', 'name', 'name', true, false);

        //  function initializeDynamicSelect2(selector, tableName, columnName, idColumn = 'id', enableTags = false, isMultiple = true) {

        $('[name="arrival_truck_type_id"]').select2();

        $(document).on('change', '[name="arrival_truck_type_id"]', function() {
            let sampleMoney = $(this).find(':selected').data('samplemoney');
            $('input[name="sample_money"]').val(sampleMoney ?? '');
        });
    });
</script>
