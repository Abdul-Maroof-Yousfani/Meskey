<form action="{{ route('ticket.update', $arrivalTicket->id) }}" method="POST" id="ajaxSubmit" class="valid-screen"
    autocomplete="off">
    @csrf
    @method('PUT')
    <input type="hidden" id="listRefresh" value="{{ route('get.ticket') }}" />
    <div class="row form-mar">

        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Location:</label>
                <select name="company_location_id" id="company_location_id" class="form-control select2">
                    <option value="">Select Location</option>
                    @foreach ($companyLocations as $location)
                        <option value="{{ $location->id }}" @selected(($arrivalTicket->location_id ?? null) == $location->id)>
                            {{ $location->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="col-xs-12 col-sm-12 col-md-12">
            <fieldset>
                <div class="input-group">
                    <div class="input-group-prepend">
                        <button class="btn btn-primary" type="button">Ticket No#</button>
                    </div>
                    <input type="text" disabled class="form-control" name="unique_no" value="{{ $arrivalTicket->unique_no }}"
                        placeholder="Select Location">
                </div>
            </fieldset>
        </div>

        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label class="d-block">Contract Detail:</label>
                <select name="arrival_purchase_order_id" id="arrival_purchase_order_id" class="form-control select2">
                    <option value="">N/A</option>
                    @foreach ($arrivalPurchaseOrders as $order)
                        <option value="{{ $order->id }}"
                            data-product-id="{{ $order->product_id }}"
                            data-product-name="{{ $order->product->name ?? '' }}"
                            data-supplier-id="{{ $order->supplier->company_name ?? '' }}"
                            data-supplier-name="{{ $order->supplier->company_name ?? '' }}"
                            data-created-by-id="{{ $order->created_by ?? '' }}"
                            data-created-by-name="{{ $order->createdByUser->name ?? '' }}"
                            data-decision-id="{{ $order->decision_of_id ?? $order->created_by }}"
                            data-sauda-type-id="{{ $order->sauda_type_id }}"
                            data-sauda-type-name="{{ $order->saudaType->name ?? 'N/A' }}"
                            data-created-at="{{ $order->created_at ?? '' }}"
                            @selected($arrivalTicket->arrival_purchase_order_id == $order->id)>
                            #{{ $order->contract_no }} - Type: {{ $order->saudaType->name ?? 'N/A' }} - Purchase Type:
                            {{ formatEnumValue($order->purchase_type ?? 'N/A') }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="col-xs-6 col-sm-6 col-md-6 d-none">
            <div class="form-group">
                <label>Sauda Type:</label>
                <input type="text" name="sauda_type_display" id="sauda_type" class="form-control"
                    value="{{ $arrivalTicket->saudaType->name ?? ($arrivalTicket->purchaseOrder->saudaType->name ?? '') }}" readonly />
                <input type="hidden" name="sauda_type_id" id="sauda_type_id"
                    value="{{ $arrivalTicket->sauda_type_id ?? $arrivalTicket->purchaseOrder?->sauda_type_id }}">
            </div>
        </div>

        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group ">
                <label>Product:</label>
                <select name="product_id_display" id="product_id"
                    class="form-control select2 {{ $arrivalTicket->arrival_purchase_order_id ? 'disabled-field' : '' }}"
                    {{ $arrivalTicket->arrival_purchase_order_id ? 'disabled' : '' }}>
                    <option value="">Product Name</option>
                    @foreach ($products as $product)
                        <option value="{{ $product->id }}" @selected($arrivalTicket->product_id == $product->id)>
                            {{ $product->name }}
                        </option>
                    @endforeach
                </select>
                <input type="hidden" name="product_id" id="product_id_hidden" value="{{ $arrivalTicket->product_id }}">
            </div>
        </div>

        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group ">
                <label>Millers:</label>
                <select name="miller_name" id="miller_id" class="form-control select2">
                    <option value="">Select Miller</option>
                    @php
                        $millerName = $arrivalTicket->miller?->name ?? $arrivalTicket->miller_name;
                    @endphp
                    @if ($millerName)
                        <option value="{{ $millerName }}" selected>{{ $millerName }}</option>
                    @endif
                </select>
            </div>
        </div>

        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group ">
                <label>Broker:</label>
                <select name="broker_name" id="broker_name" class="form-control select2">
                    <option value="">Broker Name</option>
                    @php
                        $brokerName = $arrivalTicket->broker_name ?? ($arrivalTicket->broker?->company_name ?? '');
                    @endphp
                    @foreach ($suppliers as $supplier)
                        <option value="{{ $supplier->company_name }}" @selected($brokerName == $supplier->company_name)>
                            {{ $supplier->company_name }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group ">
                <label>Decision Of:</label>
                <select name="decision_id_display" id="decision_id"
                    class="form-control select2 {{ $arrivalTicket->arrival_purchase_order_id ? 'disabled-field' : '' }}"
                    {{ $arrivalTicket->arrival_purchase_order_id ? 'disabled' : '' }}>
                    <option value="" hidden>Decision Of</option>
                    @foreach ($accountsOf as $account)
                        <option value="{{ $account->id }}" @selected($arrivalTicket->decision_id == $account->id)>
                            {{ $account->name }}
                        </option>
                    @endforeach
                </select>
                <input type="hidden" name="decision_id" id="decision_id_hidden" value="{{ $arrivalTicket->decision_id }}">
            </div>
        </div>

        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group">
                <label>Accounts Of:</label>
                @php
                    $accountsOfName = $arrivalTicket->accounts_of_name ?? ($arrivalTicket->accountsOf?->company_name ?? '');
                @endphp
                <select name="accounts_of_display" id="accounts_of"
                    class="form-control select2 {{ $arrivalTicket->arrival_purchase_order_id ? 'disabled-field' : '' }}"
                    {{ $arrivalTicket->arrival_purchase_order_id ? 'disabled' : '' }}>
                    <option value="" hidden>Accounts Of</option>
                    @foreach ($suppliers as $supplier)
                        <option value="{{ $supplier->company_name }}" @selected($accountsOfName == $supplier->company_name)>
                            {{ $supplier->company_name }}
                        </option>
                    @endforeach
                </select>
                <input type="hidden" name="accounts_of" id="accounts_of_hidden" value="{{ $accountsOfName }}">
            </div>
        </div>

        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group ">
                <label>Station:</label>
                <select name="station" id="station_id" class="form-control select2">
                    <option value="" hidden>Station</option>
                    @php
                        $stationName = $arrivalTicket->station_name ?? $arrivalTicket->station?->name;
                    @endphp
                    @if ($stationName)
                        <option value="{{ $stationName }}" selected>{{ $stationName }}</option>
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
                            value="{{ $arrival_truck_types->id }}"
                            @selected(($arrivalTicket->truck_type_id ?? $arrivalTicket->arrival_truck_type_id) == $arrival_truck_types->id)>
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
                <input type="number" name="bags" placeholder="No of bags" class="form-control" autocomplete="off"
                    value="{{ $arrivalTicket->bags }}" />
            </div>
        </div>

        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group ">
                <label>Loading Date: (Optional)</label>
                <input type="date" name="loading_date" placeholder="Loading Date" class="form-control" autocomplete="off"
                    max="{{ date('Y-m-d') }}"
                    value="{{ $arrivalTicket->loading_date ? \Carbon\Carbon::parse($arrivalTicket->loading_date)->format('Y-m-d') : '' }}" />
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
                <input type="text" name="first_weight" id="first_weight" placeholder="First Weight" class="form-control"
                    autocomplete="off" value="{{ $arrivalTicket->first_weight }}" />
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
                <input type="text" name="net_weight" id="net_weight" placeholder="Net Weight" class="form-control"
                    readonly autocomplete="off" value="{{ $arrivalTicket->net_weight }}" />
                <div class="error-message text-danger" style="display: none;">Please check your values. Net weight
                    cannot be negative.</div>
            </div>
        </div>
    </div>

    <div class="row ">
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group ">
                <label>Remarks (Optional):</label>
                <textarea name="remarks" row="4" class="form-control"
                    placeholder="Description">{{ $arrivalTicket->remarks }}</textarea>
            </div>
        </div>
    </div>

    <div class="row bottom-button-bar">
        <div class="col-12">
            <a type="button" class="btn btn-danger modal-sidebar-close position-relative top-1 closebutton">Close</a>
            <button type="submit" class="btn btn-primary submitbutton">Update</button>
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

    $(document).ready(function () {
        $('.select2').select2();

        $(document).on('change', '[name="arrival_truck_type_id"]', calculateSampleMoney);
        $(document).on('change', '[name="sample_money_type"]', calculateSampleMoney);

        initializeDynamicSelect2('#miller_id', 'millers', 'name', 'name', true, false);
        initializeDynamicSelect2('#station_id', 'stations', 'name', 'name', true, false);

        $('[name="arrival_truck_type_id"], [name="decision_id_display"], [name="accounts_of_display"], [name="broker_name"], [name="arrival_purchase_order_id"], [name="product_id_display"], #company_location_id')
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

        $('#first_weight, #second_weight').on('input', function () {
            calculateNetWeight();
        });

        $(document).on('change', '#accounts_of', function () {
            $('#accounts_of_hidden').val($(this).val());
        });
        $(document).on('change', '#decision_id', function () {
            $('#decision_id_hidden').val($(this).val());
        });
        $(document).on('change', '#product_id', function () {
            $('#product_id_hidden').val($(this).val());
        });

        $(document).on('change', '[name="arrival_purchase_order_id"]', function () {
            var selectedOption = $(this).find('option:selected');
            if (selectedOption.val() === "") {
                $('#product_id').prop('disabled', false).removeClass('disabled-field');
                $('#accounts_of').prop('disabled', false).removeClass('disabled-field');
                $('#decision_id').prop('disabled', false).removeClass('disabled-field');
                $('#sauda_type').val('');
                $('#sauda_type_id').val('');
                return;
            }

            var supplierId = selectedOption.data('supplier-id');
            var productId = selectedOption.data('product-id');
            var createdById = selectedOption.data('created-by-id');
            var saudaTypeName = selectedOption.data('sauda-type-name');
            var saudaTypeId = selectedOption.data('sauda-type-id');
            var decisionId = selectedOption.data('decision-id');

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

            if (decisionId) {
                $('#decision_id').val(decisionId).trigger('change');
                $('#decision_id').prop('disabled', true).addClass('disabled-field');
                $('#decision_id_hidden').val(decisionId);
            } else if (createdById) {
                $('#decision_id').val(createdById).trigger('change');
                $('#decision_id_hidden').val(createdById);
            }

            if (saudaTypeName) {
                $('#sauda_type').val(saudaTypeName);
                $('#sauda_type_id').val(saudaTypeId);
            }
        });

        function loadLocationData(locationId) {
            if (!locationId) {
                $('#arrival_purchase_order_id').empty().append('<option value="">N/A</option>');
                $('#broker_name').empty().append('<option value="">Broker Name</option>');
                $('#accounts_of').empty().append('<option value="">Accounts Of</option>');
                return;
            }
            $.get(`/arrival/get-contracts/${locationId}`, function (data) {
                $('#arrival_purchase_order_id').empty().append('<option value="">N/A</option>');
                $.each(data.contracts, function (index, contract) {
                    $('#arrival_purchase_order_id').append(
                        `<option value="${contract.id}"
                        data-product-id="${contract.product_id}"
                        data-supplier-id="${contract.supplier ? contract.supplier.company_name : ''}"
                        data-decision-id="${contract.decision_of_id}"
                        data-sauda-type-id="${contract.sauda_type_id}"
                        data-sauda-type-name="${contract.sauda_type ? contract.sauda_type.name : 'N/A'}"
                        >
                        #${contract.contract_no} - Sauda Type: ${contract.sauda_type ? contract.sauda_type.name : 'N/A'} - Purchase Type: ${(contract.purchase_type || 'N/A').toUpperCase()}
                    </option>`
                    );
                });
            });

            $.get(`/arrival/get-suppliers/${locationId}`, function (data) {
                $('#broker_name').empty().append('<option value="">Broker Name</option>');
                $('#accounts_of').empty().append('<option value="">Accounts Of</option>');

                $.each(data.suppliers, function (index, supplier) {
                    $('#broker_name').append(
                        `<option value="${supplier.name}">${supplier.name}</option>`
                    );
                    $('#accounts_of').append(
                        `<option value="${supplier.name}">${supplier.name}</option>`
                    );
                });
            });
        }

        $(document).on('change', '#company_location_id', function () {
            const locationId = $(this).val();
            loadLocationData(locationId);
        });
    });
</script>