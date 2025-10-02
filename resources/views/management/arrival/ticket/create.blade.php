<form action="{{ route('ticket.store') }}" method="POST" id="ajaxSubmit" class="valid-screen" autocomplete="off">
    @csrf
    <input type="hidden" id="listRefresh" value="{{ route('get.ticket') }}" />
    <div class="row form-mar">

        <?php
$authUser = auth()->user();
$isRegularUser = $authUser->user_type === 'user';
$userLocation = $authUser->companyLocation ?? null;

$unique_no = $isRegularUser ? generateTicketNoWithDateFormat('arrival_tickets', $userLocation->code) : '';
         ?>

        @if ($isRegularUser)
            <div class="col-xs-12 col-sm-12 col-md-12">
                <div class="form-group">
                    <label>Location:</label>
                    <input type="text" class="form-control" value="{{ $userLocation->name ?? 'N/A' }}" disabled>
                    <input type="hidden" name="company_location_id" value="{{ $userLocation->id ?? null }}" id="">
                </div>
            </div>
        @else
            <div class="col-xs-12 col-sm-12 col-md-12">
                <div class="form-group">
                    <label>Location:</label>
                    <select name="company_location_id" id="company_location_id" class="form-control select2">
                        <option value="">Select Location</option>
                        @foreach ($companyLocations as $location)
                            <option value="{{ $location->id }}">{{ $location->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        @endif

        <div class="col-xs-12 col-sm-12 col-md-12">
            <fieldset>
                <div class="input-group">
                    <div class="input-group-prepend">
                        <button class="btn btn-primary" type="button">Ticket No#</button>
                    </div>
                    <input type="text" disabled class="form-control" name="unique_no" value="{{ $unique_no }}"
                        placeholder="Select Location">
                </div>
            </fieldset>
        </div>
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label class="d-block">Contract Detail:</label>
                <select name="arrival_purchase_order_id" id="arrival_purchase_order_id" class="form-control select2">
                    <option value="">N/A</option>
                </select>
            </div>
        </div>
        <div class="col-xs-6 col-sm-6 col-md-6 d-none">
            <div class="form-group">
                <label>Sauda Type:</label>
                <input type="text" name="sauda_type_display" id="sauda_type" class="form-control" readonly />
                <input type="hidden" name="sauda_type_id" id="sauda_type_id">
            </div>
        </div>
        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group ">
                <label>Product:</label>
                <select name="product_id_display" id="product_id" class="form-control select2">
                    <option value="">Product Name</option>
                    @foreach ($products as $product)
                        <option value="{{ $product->id }}">{{ $product->name }}</option>
                    @endforeach
                </select>
                <input type="hidden" name="product_id" id="product_id_hidden">
            </div>
        </div>
        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group ">
                <label>Millers:</label>
                <select name="miller_name" id="miller_id" class="form-control select2">
                    <option value="">Select Miller</option>
                </select>
                {{-- <input type="hidden" name="miller_id" id="miller_id_submit"> --}}
            </div>
        </div>
        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group ">
                <label>Broker:</label>
                <select name="broker_name" id="broker_name" class="form-control select2">
                    <option value="">Broker Name</option>
                </select>
            </div>
        </div>


        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group ">
                <label>Decision Of:</label>
                <select name="decision_id" id="decision_id" class="form-control select2">
                    <option value="" hidden>Decision Of</option>
                    @foreach ($accountsOf as $account)
                        <option value="{{ $account->id }}">{{ $account->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group">
                <label>Accounts Of:</label>
                <select name="accounts_of_display" id="accounts_of" class="form-control select2">
                    <option value="" hidden>Accounts Of</option>
                </select>
                <input type="hidden" name="accounts_of" id="accounts_of_hidden">
            </div>
        </div>
        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group ">
                <label>Station:</label>
                <select name="station" id="station_id" class="form-control select2">
                    <option value="" hidden>Station</option>
                </select>
            </div>
        </div>
        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group ">
                <label>Truck No:</label>
                <input type="text" name="truck_no" placeholder="Truck No" class="form-control text-uppercase"
                    autocomplete="off" />
            </div>
        </div>
        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group ">
                <label>Bilty No: </label>
                <input type="text" name="bilty_no" placeholder="Bilty No" class="form-control" autocomplete="off" />
            </div>
        </div>
        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group ">
                <label>Truck Type:</label>
                <select name="arrival_truck_type_id" id="" class="form-control select2">
                    <option value="">Truck Type</option>

                    @foreach (getTableData('arrival_truck_types', ['id', 'name', 'sample_money']) as $arrival_truck_types)
                        <option data-samplemoney="{{ $arrival_truck_types->sample_money ?? 0 }}"
                            value="{{ $arrival_truck_types->id }}">{{ $arrival_truck_types->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group ">
                <label>Sample Money Type:</label>
                <select name="sample_money_type" class="form-control">
                    <option value="n/a" selected>N/A</option>
                    <option value="single">Single</option>
                    <option value="double">Double</option>
                </select>
            </div>
        </div>
        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group ">
                <label>Sample Money: </label>
                <input type="text" readonly name="sample_money" placeholder="Sample Money" class="form-control"
                    autocomplete="off" />
            </div>
        </div>
        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group ">
                <label>No of bags: </label>
                <input type="text" name="bags" placeholder="No of bags" class="form-control" autocomplete="off" />
            </div>
        </div>
        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group ">
                <label>Loading Date: (Optional)</label>
                <input type="date" name="loading_date" placeholder="Bilty No" class="form-control" autocomplete="off"
                    max="{{ date('Y-m-d') }}" />
            </div>
        </div>
        {{-- <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group ">
                <label>Loading Weight:</label>
                <input type="text" name="loading_weight" placeholder="Loading Weight" class="form-control"
                    autocomplete="off" />
            </div>
        </div> --}}

        {{-- <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group ">
                <label>Status:</label>
                <select name="status" class="form-control">
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
        </div> --}}
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
                    autocomplete="off" />
            </div>
        </div>
        <div class="col-xs-4 col-sm-4 col-md-4">
            <div class="form-group">
                <label>Second Weight:</label>
                <input type="text" name="second_weight" id="second_weight" placeholder="Second Weight"
                    class="form-control" autocomplete="off" />
            </div>
        </div>
        <div class="col-xs-4 col-sm-4 col-md-4">
            <div class="form-group">
                <label>Net Weight:</label>
                <input type="text" name="net_weight" id="net_weight" placeholder="Net Weight" class="form-control"
                    readonly autocomplete="off" />
                <div class="error-message text-danger" style="display: none;">Please check your values. Net weight
                    cannot be negative.</div>
            </div>
        </div>
    </div>

    <div class="row ">
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group ">
                <label>Remarks (Optional):</label>
                <textarea name="remarks" row="4" class="form-control" placeholder="Description"></textarea>
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

    $(document).ready(function () {
        $('.select2').select2();

        if (IS_LOCAL) {
            function populateRandomValues() {
                if ($('#product_id option').length > 1) {
                    const productOptions = $('#product_id option:not(:first)');
                    const randomProduct = productOptions.eq(Math.floor(Math.random() * productOptions.length))
                        .val();
                    $('#product_id').val(randomProduct).trigger('change');
                }

                if ($('#arrival_purchase_order_id option').length > 1) {
                    const contractOptions = $('#arrival_purchase_order_id option:not(:first)');
                    const randomContract = contractOptions.eq(Math.floor(Math.random() * contractOptions
                        .length)).val();
                    $('#arrival_purchase_order_id').val(randomContract).trigger('change');
                }

                if ($('#miller_id option').length > 1) {
                    const millerOptions = $('#miller_id option:not(:first)');
                    const randomMiller = millerOptions.eq(Math.floor(Math.random() * millerOptions.length))
                        .val();
                    $('#miller_id').val(randomMiller).trigger('change');
                }

                if ($('#broker_name option').length > 1) {
                    const brokerOptions = $('#broker_name option:not(:first)');
                    const randomBroker = brokerOptions.eq(Math.floor(Math.random() * brokerOptions.length))
                        .val();
                    $('#broker_name').val(randomBroker).trigger('change');
                }

                if ($('#decision_id option').length > 1) {
                    const decisionOptions = $('#decision_id option:not(:first)');
                    const randomDecision = decisionOptions.eq(Math.floor(Math.random() * decisionOptions
                        .length)).val();
                    $('#decision_id').val(randomDecision).trigger('change');
                }

                if ($('#accounts_of option').length > 1) {
                    const accountsOptions = $('#accounts_of option:not(:first)');
                    const randomAccount = accountsOptions.eq(Math.floor(Math.random() * accountsOptions.length))
                        .val();
                    $('#accounts_of').val(randomAccount).trigger('change');
                }

                if ($('#station_id option').length > 1) {
                    const stationOptions = $('#station_id option:not(:first)');
                    const randomStation = stationOptions.eq(Math.floor(Math.random() * stationOptions.length))
                        .val();
                    $('#station_id').val(randomStation).trigger('change');
                }

                const letters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
                const randomLetters = letters.charAt(Math.floor(Math.random() * letters.length)) +
                    letters.charAt(Math.floor(Math.random() * letters.length));
                const randomNumbers = Math.floor(1000 + Math.random() * 9000);
                $('input[name="truck_no"]').val(randomLetters + '-' + randomNumbers);

                $('input[name="bilty_no"]').val('BL-' + Math.floor(10000 + Math.random() * 90000));

                if ($('[name="arrival_truck_type_id"] option').length > 1) {
                    const truckTypeOptions = $('[name="arrival_truck_type_id"] option:not(:first)');
                    const randomTruckType = truckTypeOptions.eq(Math.floor(Math.random() * truckTypeOptions
                        .length)).val();
                    $('[name="arrival_truck_type_id"]').val(randomTruckType).trigger('change');
                }

                const sampleMoneyTypes = ['n/a', 'single', 'double'];
                $('[name="sample_money_type"]').val(sampleMoneyTypes[Math.floor(Math.random() * sampleMoneyTypes
                    .length)]).trigger('change');

                $('input[name="bags"]').val(Math.floor(10 + Math.random() * 100));

                const randomDate = new Date();
                randomDate.setDate(randomDate.getDate() - Math.floor(Math.random() * 30));
                $('input[name="loading_date"]').val(randomDate.toISOString().split('T')[0]);

                const firstWeight = Math.floor(5000 + Math.random() * 10000);
                const secondWeight = firstWeight + Math.floor(1000 + Math.random() * 5000);
                $('#first_weight').val(firstWeight);
                $('#second_weight').val(secondWeight);
                calculateNetWeight();

                const remarks = ['Good condition', 'Normal delivery', 'Urgent delivery', 'Standard shipment'];
                $('textarea[name="remarks"]').val(remarks[Math.floor(Math.random() * remarks.length)]);
            }

            //  populateRandomValues();

            $(document).on('select2:open', function () {
                //  setTimeout(populateRandomValues, 500);
            });
        }

        calculateSampleMoney();

        $(document).on('change', '[name="arrival_truck_type_id"]', calculateSampleMoney);

        $(document).on('change', '[name="sample_money_type"]', calculateSampleMoney);

        initializeDynamicSelect2('#miller_id', 'millers', 'name', 'name', true, false);
        //  initializeDynamicSelect2('#product_id', 'products', 'name', 'id', false, false);
        //  initializeDynamicSelect2('#supplier_name', 'suppliers', 'name', 'name', true, false);
        //  initializeDynamicSelect2('#accounts_of', 'suppliers', 'name', 'name', true, false);
        //  initializeDynamicSelect2('#broker_name', 'suppliers', 'name', 'name', true, false);
        initializeDynamicSelect2('#station_id', 'stations', 'name', 'name', true, false);

        $('[name="arrival_truck_type_id"], [name="decision_id"], [name="accounts_of_display"], [name="broker_name"], [name="arrival_purchase_order_id"], [name="product_id_display"], #company_location_id')
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

        //   $(document).on('change', '[name="arrival_truck_type_id"]', function () {
        //  let sampleMoney = $(this).find(':selected').data('samplemoney');
        //   $('input[name="sample_money"]').val(sampleMoney ?? '');
        //});

        $(document).on('change', '[name="arrival_purchase_order_id"]', function () {
            // First reset all form fields
            resetAllFormFields();

            var selectedOption = $(this).find('option:selected');
            if (selectedOption.val() === "") {
                return; // If N/A is selected, just keep all fields reset
            }

            // Now populate fields from the selected contract
            var supplierId = selectedOption.data('supplier-id');
            var productId = selectedOption.data('product-id');
            var createdById = selectedOption.data('created-by-id');
            var saudaTypeName = selectedOption.data('sauda-type-name');
            var saudaTypeId = selectedOption.data('sauda-type-id');
            var createdAt = selectedOption.data('created-at');

            // Set product selection
            if (productId) {
                $('#product_id').val(productId).trigger('change');
                $('#product_id_hidden').val(productId);
                $('#product_id').prop('disabled', true).addClass('disabled-field');
            }

            // Set broker/supplier selection
            if (supplierId) {
                $('#broker_name').val(supplierId).trigger('change');
                $('#accounts_of').val(supplierId).trigger('change');
                $('#accounts_of_hidden').val(supplierId);
                $('#accounts_of').prop('disabled', true).addClass('disabled-field');
            }

            // Set decision maker selection
            if (createdById) {
                $('#decision_id').val(createdById).trigger('change');
            }

            console.log({
                saudaTypeName
            });

            // Set Sauda Type
            if (saudaTypeName) {
                $('#sauda_type').val(saudaTypeName);
                // If you need to store the sauda_type_id, you would need to add it to the data attributes
                $('#sauda_type_id').val(saudaTypeId);
            }

            // Set loading date
            if (createdAt) {
                //  $('input[name="loading_date"]').val(createdAt.split(' ')[0]);
            }
        });

        function resetAllFormFields() {
            // Reset product fields
            $('#product_id').val('').trigger('change');
            $('#product_id_hidden').val('');
            $('#product_id').prop('disabled', false).removeClass('disabled-field');

            $('#sauda_type').val('');
            $('#sauda_type_id').val('');

            // Reset broker/supplier fields
            $('#broker_name').val('').trigger('change');
            $('#accounts_of').val('').trigger('change');
            $('#accounts_of_hidden').val('');
            $('#accounts_of').prop('disabled', false).removeClass('disabled-field');

            // Reset decision field
            $('#decision_id').val('').trigger('change');

            // Reset loading date
            $('input[name="loading_date"]').val('');

            // Reset other fields you want to clear
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

            // Reset any validation errors
            $('#net_weight').removeClass('is-invalid');
            $('#net_weight').siblings('.error-message').hide();
        }

        $(document).on('change', '#product_id', function () {
            $('#product_id_hidden').val($(this).val());
        });

        // Sync values on any change (just in case)
        $(document).on('change', '#supplier_name, #broker_name', function () {
            if ($(this).attr('id') === 'supplier_name') {
                $('#supplier_name_submit').val($(this).val());
            } else {
                $('#broker_name_submit').val($(this).val());
            }
        });


        function loadLocationData(locationId) {
            if (!locationId) {
                $('#arrival_purchase_order_id').empty().append('<option value="">N/A</option>');
                $('#broker_name').empty().append('<option value="">Broker Name</option>');
                $('#accounts_of').empty().append('<option value="">Accounts Of</option>');
                return;
            }
             $.get(`/arrival/get-contracts/${locationId}`, function(data) {
                 $('#arrival_purchase_order_id').empty().append('<option value="">N/A</option>');
                 $.each(data.contracts, function(index, contract) {
                     $('#arrival_purchase_order_id').append(
                         `<option value="${contract.id}"
                        data-product-id="${contract.product_id}"
                        data-supplier-id="${contract.supplier.name}"
                        data-sauda-type-id="${contract.sauda_type_id}"
                        data-sauda-type-name="${contract.sauda_type.name}"
                        >
                        #${contract.contract_no} - Type: ${contract.sauda_type.name}
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

        $('#company_location_id').on('change', function () {
                const locationId = $(this).val();

                if (locationId) {
                    $.get(`/arrival/get-ticket-number/${locationId}`, function (data) {
                        $('input[name="unique_no"]').val(data.ticket_no);
                    });
                }

                loadLocationData(locationId);
            });

            @if ($isRegularUser)
                loadLocationData('{{ $userLocation->id ?? null }}');
            @endif

            @if (auth()->user()->user_type === 'super-admin')
                //      $('#company_location_id').on('change', function() {
                //          const locationId = $(this).val();
                //          if (locationId) {
                //              $.get(`/arrival/get-ticket-number/${locationId}`, function(data) {
                //                  $('input[name="unique_no"]').val(data.ticket_no);
                //              });
                //          }
                //      });
            @endif
     });
</script>