<form action="{{ route('ticket.update', $arrivalTicket->id) }}" method="POST" id="ajaxSubmit" class="valid-screen"
    autocomplete="off">
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
            <div class="form-group">
                <label class="d-block">Contract Detail:</label>
                <select name="arrival_purchase_order_id" id="arrival_purchase_order_id" class="form-control select2">
                    <option value="">N/A</option>
                    @foreach ($arrivalPurchaseOrders as $order)
                        <option value="{{ $order->id }}" data-product-id="{{ $order->product->id ?? '' }}"
                            data-product-name="{{ $order->product->name ?? '' }}"
                            data-supplier-id="{{ $order->supplier->name ?? '' }}"
                            data-supplier-name="{{ $order->supplier->name ?? '' }}"
                            data-created-by-id="{{ $order->created_by ?? '' }}"
                            data-created-by-name="{{ $order->createdByUser->name ?? '' }}"
                            data-sauda-type-name="{{ $order->saudaType->name ?? '' }}"
                            data-created-at="{{ $order->created_at ?? '' }}" @selected($arrivalTicket->arrival_purchase_order_id == $order->id)>
                            #{{ $order->contract_no }} - Type: {{ $order->saudaType->name ?? 'N/A' }} - Purchase Type: {{ formatEnumValue($order->purchase_type ?? 'N/A') }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group ">
                <label>Product:</label>
                <select name="product_id_display" id="product_id" class="form-control select2">
                    <option value="">Product Name</option>
                    @foreach ($products as $product)
                        <option value="{{ $product->id }}" @selected($arrivalTicket->product_id == $product->id)>
                            {{ $product->name }}
                        </option>
                    @endforeach
                </select>
                <input type="hidden" name="product_id" id="product_id_hidden"
                    value="{{ $arrivalTicket->product_id }}">
            </div>
        </div>
        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group ">
                <label>Millers:</label>
                <select name="miller_name" id="miller_id" class="form-control select2">
                    <option value="">Select Miller</option>
                    @if ($arrivalTicket->miller)
                        <option value="{{ $arrivalTicket->miller->name }}" selected>{{ $arrivalTicket->miller->name }}
                        </option>
                    @endif
                </select>
            </div>
        </div>
        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group ">
                <label>Broker:</label>
                <select name="broker_name" id="broker_name" class="form-control select2">
                    <option value="">Broker Name</option>
                    @foreach ($suppliers as $supplier)
                        <option value="{{ $supplier->name }}" @selected($arrivalTicket->broker_name == $supplier->name)>
                            {{ $supplier->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group ">
                <label>Decision Of:</label>
                <select name="decision_id" id="decision_id" class="form-control select2">
                    <option value="" hidden>Decision Of</option>
                    @foreach ($accountsOf as $account)
                        <option value="{{ $account->id }}" @selected($arrivalTicket->decision_id == $account->id)>
                            {{ $account->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group">
                <label>Accounts Of:</label>
                <select name="accounts_of_display" id="accounts_of" class="form-control select2">
                    <option value="" hidden>Accounts Of</option>
                    @foreach ($suppliers as $supplier)
                        <option value="{{ $supplier->name }}" @selected($arrivalTicket->accounts_of_name == $supplier->name)>
                            {{ $supplier->name }}
                        </option>
                    @endforeach
                </select>
                <input type="hidden" name="accounts_of" id="accounts_of_hidden"
                    value="{{ $arrivalTicket->accounts_of_name }}">
            </div>
        </div>
        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group ">
                <label>Station:</label>
                <select name="station" id="station_id" class="form-control select2">
                    <option value="" hidden>Station</option>
                    @if ($arrivalTicket->station)
                        <option value="{{ $arrivalTicket->station->name }}" selected>
                            {{ $arrivalTicket->station->name }}</option>
                    @endif
                </select>
            </div>
        </div>
        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group ">
                <label>Truck No:</label>
                <input type="text" name="truck_no" placeholder="Truck No" class="form-control text-uppercase"
                    autocomplete="off" value="{{ $arrivalTicket->truck_no }}" />
            </div>
        </div>
        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group ">
                <label>Bilty No: </label>
                <input type="text" name="bilty_no" placeholder="Bilty No" class="form-control" autocomplete="off"
                    value="{{ $arrivalTicket->bilty_no }}" />
            </div>
        </div>
        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group ">
                <label>Truck Type:</label>
                <select name="arrival_truck_type_id" id="arrival_truck_type_id" class="form-control select2">
                    <option value="">Truck Type</option>
                    @foreach (getTableData('arrival_truck_types', ['id', 'name', 'sample_money']) as $arrival_truck_types)
                        <option data-samplemoney="{{ $arrival_truck_types->sample_money ?? 0 }}"
                            value="{{ $arrival_truck_types->id }}" @selected($arrivalTicket->truck_type_id == $arrival_truck_types->id)>
                            {{ $arrival_truck_types->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group ">
                <label>Sample Money Type:</label>
                <select name="sample_money_type" class="form-control">
                    <option value="n/a" @selected($arrivalTicket->sample_money_type == 'n/a')>N/A</option>
                    <option value="single" @selected($arrivalTicket->sample_money_type == 'single')>Single</option>
                    <option value="double" @selected($arrivalTicket->sample_money_type == 'double')>Double</option>
                </select>
            </div>
        </div>
        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group ">
                <label>Sample Money: </label>
                <input type="text" readonly name="sample_money" placeholder="Sample Money" class="form-control"
                    autocomplete="off" value="{{ $arrivalTicket->sample_money }}" />
            </div>
        </div>
        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group ">
                <label>No of bags: </label>
                <input type="text" name="bags" placeholder="No of bags" class="form-control"
                    autocomplete="off" value="{{ $arrivalTicket->bags }}" />
            </div>
        </div>
        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group ">
                <label>Loading Date: (Optional)</label>
                <input type="date" name="loading_date" placeholder="Bilty No" class="form-control"
                    autocomplete="off"
                    value="{{ optional($arrivalTicket)->loading_date ? \Carbon\Carbon::parse($arrivalTicket->loading_date)->format('Y-m-d') : '' }}" />
            </div>
        </div>
    </div>
    <div class="row ">
        <div class="col-12">
            <h6 class="header-heading-sepration">
                Weight Detail
            </h6>
        </div>
        <div class="col-xs-4 col-sm-4 col-md-4">
            <div class="form-group">
                <label>First Weight:</label>
                <input type="text" name="first_weight" id="first_weight" placeholder="First Weight"
                    class="form-control" autocomplete="off" value="{{ $arrivalTicket->first_weight }}" />
            </div>
        </div>
        <div class="col-xs-4 col-sm-4 col-md-4">
            <div class="form-group">
                <label>Second Weight:</label>
                <input type="text" name="second_weight" id="second_weight" placeholder="Second Weight"
                    class="form-control" autocomplete="off" value="{{ $arrivalTicket->second_weight }}" />
            </div>
        </div>
        <div class="col-xs-4 col-sm-4 col-md-4">
            <div class="form-group">
                <label>Net Weight:</label>
                <input type="text" name="net_weight" id="net_weight" placeholder="Net Weight"
                    class="form-control" readonly autocomplete="off" value="{{ $arrivalTicket->net_weight }}" />
                <div class="error-message text-danger" style="display: none;">Please check your values. Net weight
                    cannot be negative.</div>
            </div>
        </div>
    </div>

    <div class="row ">
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group ">
                <label>Remarks (Optional):</label>
                <textarea name="remarks" row="4" class="form-control" placeholder="Description">{{ $arrivalTicket->remarks }}</textarea>
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

        if (holidayType === 'n/a') {
            sampleMoney = 0;
        }

        $('input[name="sample_money"]').val(sampleMoney || 0);
    }

    $(document).ready(function() {
        calculateSampleMoney();

        $(document).on('change', '[name="arrival_truck_type_id"]', calculateSampleMoney);
        $(document).on('change', '[name="sample_money_type"]', calculateSampleMoney);

        initializeDynamicSelect2('#miller_id', 'millers', 'name', 'name', true, false);
        initializeDynamicSelect2('#station_id', 'stations', 'name', 'name', true, false);

        $('[name="arrival_truck_type_id"], [name="decision_id"], [name="accounts_of_display"], [name="broker_name"], [name="arrival_purchase_order_id"], [name="product_id_display"]')
            .select2();

        function calculateNetWeight() {
            const firstWeight = parseFloat($('#first_weight').val()) || 0;
            const secondWeight = parseFloat($('#second_weight').val()) || 0;
            const netWeight = secondWeight - firstWeight;

            $('#net_weight').val(netWeight || 0);

            if (firstWeight && secondWeight) {
                if (netWeight < 0) {
                    $('#net_weight').addClass('is-invalid');
                    $('#net_weight').siblings('.error-message').show();
                } else {
                    $('#net_weight').removeClass('is-invalid');
                    $('#net_weight').siblings('.error-message').hide();
                }
            }
        }

        $('#first_weight, #second_weight').on('input', function() {
            calculateNetWeight();
        });

        $(document).on('change', '#accounts_of', function() {
            $('#accounts_of_hidden').val($(this).val());
        });

        $(document).on('change', '[name="arrival_purchase_order_id"]', function() {
            resetAllFormFields();

            var selectedOption = $(this).find('option:selected');
            if (selectedOption.val() === "") {
                return;
            }

            var supplierId = selectedOption.data('supplier-id');
            var productId = selectedOption.data('product-id');
            var createdById = selectedOption.data('created-by-id');
            var createdAt = selectedOption.data('created-at');

            if (productId) {
                $('#product_id').val(productId).trigger('change');
                $('#product_id_hidden').val(productId);
                $('#product_id').prop('disabled', true).addClass('disabled-field');
            }

            if (supplierId) {
                $('#broker_name').val(supplierId).trigger('change');
                $('#accounts_of').val(supplierId).trigger('change');
                $('#accounts_of_hidden').val(supplierId);
                $('#accounts_of').prop('disabled', true).addClass('disabled-field');
            }

            if (createdById) {
                $('#decision_id').val(createdById).trigger('change');
            }

            if (createdAt) {
                $('input[name="loading_date"]').val(createdAt.split(' ')[0]);
            }
        });

        function resetAllFormFields() {
            $('#product_id').val('').trigger('change');
            $('#product_id_hidden').val('');
            $('#product_id').prop('disabled', false).removeClass('disabled-field');

            $('#broker_name').val('').trigger('change');
            $('#accounts_of').val('').trigger('change');
            $('#accounts_of_hidden').val('');
            $('#accounts_of').prop('disabled', false).removeClass('disabled-field');

            $('#decision_id').val('').trigger('change');
            $('input[name="loading_date"]').val('');

            $('#miller_id').val('').trigger('change');
            $('#station_id').val('').trigger('change');
            $('input[name="truck_no"]').val('');
            $('input[name="bilty_no"]').val('');
            $('[name="arrival_truck_type_id"]').val('').trigger('change');
            $('[name="sample_money_type"]').val('n/a').trigger('change');
            $('input[name="sample_money"]').val('');
            $('input[name="bags"]').val('');
            $('#first_weight').val('');
            $('#second_weight').val('');
            $('#net_weight').val('');
            $('textarea[name="remarks"]').val('');

            $('#net_weight').removeClass('is-invalid');
            $('#net_weight').siblings('.error-message').hide();
        }

        $(document).on('change', '#product_id', function() {
            $('#product_id_hidden').val($(this).val());
        });
    });
</script>
