<form action="{{ route('raw-material.gate-buying.store') }}" method="POST" id="ajaxSubmit" autocomplete="off">
    @csrf
    <input type="hidden" id="listRefresh" value="{{ route('raw-material.get.gate-buying') }}" />
    <input type="hidden" name="purchase_type" value="gate_buying" />

    <div class="row form-mar">
        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group ">
                <label>Location:</label>
                <select name="company_location_id" id="company_location_id" class="form-control  ">
                    <option value="">Location</option>
                </select>
            </div>
        </div>
        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group">
                <label>Contract Date:</label>
                <input type="date" name="contract_date" placeholder="Contract Date" class="form-control"
                    max="{{ date('Y-m-d') }}" />
            </div>
        </div>
    </div>

    <div class="row form-mar">
        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group">
                <label>Ref No:</label>
                <input type="text" name="ref_no" placeholder="Ref No" class="form-control" />
            </div>
        </div>
        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group">
                <label>S No:</label>
                <input type="text" name="contract_no" readonly placeholder="S No." class="form-control" />
            </div>
        </div>
    </div>

    <div class="row form-mar">

        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group ">
                <label>Supplier:</label>
                <select name="supplier_id" id="supplier_id" class="form-control select22">
                    <option value="">Supplier</option>
                    {{-- @foreach ($suppliers as $supplier)
                    <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                    @endforeach --}}
                </select>
            </div>
        </div>
        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group ">
                <label>Accounts of:</label>
                <select name="decision_of_id" id="decision_of_id" class="form-control select22">
                    <option value="">Accounts Of</option>
                    @foreach ($accountsOf as $account)
                    <option value="{{ $account->id }}">{{ $account->name }}</option>
                    @endforeach
                </select>
                </select>
            </div>
        </div>


        {{-- <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group">
                <label>Supplier Name:</label>
                <input type="text" name="supplier_name" placeholder="Supplier Name" class="form-control" />
            </div>
        </div> --}}
        {{-- <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group">
                <label>Purchaser Name:</label>
                <input type="text" name="purchaser_name" placeholder="Purchaser Name" class="form-control" />
            </div>
        </div> --}}
    </div>
    <div class="row form-mar">
        <div class="col-xs-4 col-sm-4 col-md-4">
            <div class="form-group">
                <label>Contact Person Name:</label>
                <input type="text" name="contact_person_name" placeholder="Contact Person Name" class="form-control" />
            </div>
        </div>
        <div class="col-xs-4 col-sm-4 col-md-4">
            <div class="form-group">
                <label>Mobile No:</label>
                <input type="text" name="mobile_no" placeholder="Mobile No" class="form-control" />
            </div>
        </div>
        <div class="col-xs-4 col-sm-4 col-md-4">
            <div class="form-group">
                <label>CNIC No:</label>
                <input type="text" name="cnic_no" placeholder="CNIC No" class="form-control" />
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <h6 class="header-heading-sepration">
                Broker
            </h6>
        </div>
        <div class="col-xs-8 col-sm-8 col-md-8">
            <div class="form-group">
                <label>Broker:</label>
                <select name="broker_one" id="broker_id" class="form-control">
                    <option value="">Select Broker</option>
                </select>
            </div>
        </div>
        <div class="col-xs-4 col-sm-4 col-md-4">
            <div class="form-group">
                <label>Commission:</label>
                <input type="number" name="broker_one_commission" step="0.01" value="" placeholder="Commission"
                    class="form-control" />
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-12">
            <h6 class="header-heading-sepration">
                Rate
            </h6>
        </div>

        <div class="col-xs-4 col-sm-4 col-md-4">
            <div class="form-group ">
                <label>Rate Per KG:</label>
                <input type="text" name="rate_per_kg" placeholder="Rate Per KG" class="form-control" />
            </div>
        </div>
        <div class="col-xs-4 col-sm-4 col-md-4">
            <div class="form-group ">
                <label>Rate Per Mound:</label>
                <input type="text" name="rate_per_mound" placeholder="Rate Per Mound" class="form-control" />

            </div>
        </div>
        <div class="col-xs-4 col-sm-4 col-md-4">
            <div class="form-group ">
                <label>Rate Per 100KG:</label>
                <input type="text" name="rate_per_100kg" placeholder="Rate Per 100KG" class="form-control" />
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <h6 class="header-heading-sepration">
                Product
            </h6>
        </div>
        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group">
                <label>Commodity:</label>
                <select name="product_id" id="product_id" class="form-control select2">
                    <option value="">Select Commodity</option>
                    @foreach ($products as $product)
                        <option value="{{ $product->id }}" data-bag-weight="{{ $product->bag_weight_for_purchasing }}">
                            {{ $product->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group">
                <label>Moisture:</label>
                <input type="number" name="moisture" value="0" placeholder="Moisture" class="form-control" />
            </div>
        </div>
        <div class="col-xs-4 col-sm-4 col-md-4">
            <div class="form-group">
                <label>Chalky:</label>
                <input type="number" name="chalky" value="0" placeholder="Chalky" class="form-control" />
            </div>
        </div>
        <div class="col-xs-4 col-sm-4 col-md-4">
            <div class="form-group">
                <label>Mixing:</label>
                <input type="number" name="mixing" value="0" placeholder="Mixing" class="form-control" />
            </div>
        </div>
        <div class="col-xs-4 col-sm-4 col-md-4">
            <div class="form-group">
                <label>Red Rice:</label>
                <input type="number" name="red_rice" value="0" placeholder="Red Rice" class="form-control" />
            </div>
        </div>
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Other Params:</label>
                <input type="text" name="other_params" placeholder="Other Params" class="form-control" />
            </div>
        </div>
    </div>

    <div class="row form-mar">
        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group">
                <label>Truck No:</label>
                <input type="text" name="truck_no" placeholder="Truck #" class="form-control" />
            </div>
        </div>
        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group">
                <label>Payment Term:</label>
                <select name="payment_term" class="form-control select2">
                    <option value="">Select payment term</option>
                    <option value="Cash Payment">Cash Payment</option>
                    <option value="Cheque">Cheque</option>
                    <option value="Online">Online</option>
                </select>
            </div>
        </div>
    </div>

    <div class="row form-mar">
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Remarks:</label>
                <textarea name="remarks" placeholder="Remarks" class="form-control"></textarea>
            </div>
        </div>
    </div>

    <div class="row form-mar">
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Prepared By:</label>
                <input type="text" name="created_by_display" value="{{ auth()->user()->name }}" disabled
                    placeholder="Prepared By" class="form-control" />
                <input type="hidden" name="created_by" value="{{ auth()->user()->id }}" readonly
                    placeholder="Prepared By" class="form-control" />
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





    $(document).ready(function () {
        $('.select2').select2();

        $('[name="company_location_id"], [name="contract_date"]').change(function () {
            generateContractNumber();
        });


        $('#company_location_id').change(function () {
            var locationId = $(this).val();

            if (locationId) {
                $.ajax({
                    url: '{{ route('raw-material.get.suppliers.by.location_for_gate_buying') }}',
                    type: 'GET',
                    data: {
                        location_id: locationId
                    },
                    beforeSend: function () {
                        $('#supplier_id').html('<option value="">Loading...</option>');
                    },
                    success: function (response) {
                        if (response.success && response.suppliers.length > 0) {
                            var options = '';
                            if (response.suppliers.length === 1) {
                                // only one supplier → auto select
                                var supplier = response.suppliers[0];
                                options = '<option value="' + supplier.id + '" selected>' + supplier.name + '</option>';
                            } else {
                                // multiple suppliers
                                options = '<option value="">Select Supplier</option>';
                                $.each(response.suppliers, function (key, supplier) {
                                    options += '<option value="' + supplier.id + '">' + supplier.name + '</option>';
                                });
                            }
                            $('#supplier_id').html(options).trigger('change');
                        } else {
                            $('#supplier_id').html('<option value="">No suppliers found</option>');
                        }
                    },
                    error: function () {
                        $('#supplier_id').html('<option value="">Error loading suppliers</option>');
                    }
                });
            } else {
                $('#supplier_id').html('<option value="">Select Supplier</option>');
            }
        });
        // Function to generate contract number



        function generateContractNumber() {
            const locationId = $('[name="company_location_id"]').val();
            const contractDate = $('[name="contract_date"]').val();

            if (locationId && contractDate) {
                $.ajax({
                    url: '{{ route('raw-material.generate.contract.number') }}',
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        location_id: locationId,
                        contract_date: contractDate
                    },
                    beforeSend: function () { },
                    success: function (response) {
                        if (response.success) {
                            $('[name="contract_no"]').val(response.contract_no);
                        }
                    },
                    error: function (xhr) {
                        console.error(xhr.responseText);
                    }
                });
            }
        }

        const KG_PER_MOUND = 40;
        const KG_PER_100KG = 100;

        function calculateRates(changedField) {
            const ratePerKg = parseFloat($('[name="rate_per_kg"]').val()) || 0;
            const ratePerMound = parseFloat($('[name="rate_per_mound"]').val()) || 0;
            const ratePer100kg = parseFloat($('[name="rate_per_100kg"]').val()) || 0;

            switch (changedField) {
                case 'rate_per_kg':
                    $('[name="rate_per_mound"]').val((ratePerKg * KG_PER_MOUND).toFixed(2));
                    $('[name="rate_per_100kg"]').val((ratePerKg * KG_PER_100KG).toFixed(2));
                    break;

                case 'rate_per_mound':
                    $('[name="rate_per_kg"]').val((ratePerMound / KG_PER_MOUND).toFixed(2));
                    $('[name="rate_per_100kg"]').val((ratePerMound / KG_PER_MOUND * KG_PER_100KG).toFixed(2));
                    break;

                case 'rate_per_100kg':
                    $('[name="rate_per_kg"]').val((ratePer100kg / KG_PER_100KG).toFixed(2));
                    $('[name="rate_per_mound"]').val((ratePer100kg / KG_PER_100KG * KG_PER_MOUND).toFixed(2));
                    break;
            }
        }

        $('[name="rate_per_kg"]').on('input', function () {
            calculateRates('rate_per_kg');
        });

        $('[name="rate_per_mound"]').on('input', function () {
            calculateRates('rate_per_mound');
        });

        $('[name="rate_per_100kg"]').on('input', function () {
            calculateRates('rate_per_100kg');
        });

        initializeDynamicSelect2('#broker_id', 'brokers', 'name', 'name', true, false);
        initializeDynamicSelect2('#company_location_id', 'company_locations', 'name', 'id', false, false);
    });
</script>