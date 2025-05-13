<form action="{{ route('raw-material.gate-buying.store') }}" method="POST" id="ajaxSubmit" autocomplete="off">
    @csrf
    <input type="hidden" id="listRefresh" value="{{ route('raw-material.get.purchase-order') }}" />

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
                <input type="date" name="contract_date" placeholder="Contract Date" class="form-control" />
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
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Supplier Name:</label>
                <input type="text" name="supplier_name" placeholder="Supplier Name" class="form-control" />
            </div>
        </div>
    </div>

    <div class="row form-mar">
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Purchaser Name:</label>
                <input type="text" name="purchaser_name" placeholder="Purchaser Name" class="form-control" />
            </div>
        </div>
    </div>

    <div class="row form-mar">
        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group">
                <label>Contact Person Name:</label>
                <input type="text" name="contact_person" placeholder="Contact Person Name" class="form-control" />
            </div>
        </div>
        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group">
                <label>Mobile No:</label>
                <input type="text" name="mobile_number" placeholder="Mobile #" class="form-control" />
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
                <select name="broker_id" id="broker_id" class="form-control ">
                    <option value="">Select Broker</option>
                </select>
            </div>
        </div>
        <div class="col-xs-4 col-sm-4 col-md-4">
            <div class="form-group">
                <label>Commission:</label>
                <input type="number" name="broker_one_commission" value="" placeholder="Commission"
                    class="form-control" />
            </div>
        </div>
    </div>
    <div class="row form-mar">
        <div class="col-12">
            <div class="form-group">
                <label>Commodity:</label>
                <select name="commodity" id="product_id" class="form-control select2">
                    <option value="">Select Commodity</option>
                    @foreach ($products as $product)
                        <option value="{{ $product->id }}" data-bag-weight="{{ $product->bag_weight_for_purchasing }}">
                            {{ $product->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
        <div id="slabsContainer" class="col-xs-12 col-sm-12 col-md-12">
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
                <input type="number" name="rate_per_kg" placeholder="Rate Per KG" class="form-control" />
            </div>
        </div>
        <div class="col-xs-4 col-sm-4 col-md-4">
            <div class="form-group ">
                <label>Rate Per Mound:</label>
                <input type="number" name="rate_per_mound" placeholder="Rate Per Mound" class="form-control" />

            </div>
        </div>
        <div class="col-xs-4 col-sm-4 col-md-4">
            <div class="form-group ">
                <label>Rate Per 100KG:</label>
                <input type="number" name="rate_per_100kg" placeholder="Rate Per 100KG" class="form-control" />
            </div>
        </div>
    </div>
    <div class="row form-mar">
        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group">
                <label>Truck No:</label>
                <input type="text" name="truck_number" placeholder="Truck #" class="form-control" />
            </div>
        </div>
        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group">
                <label>Payment Term:</label>
                <select name="payment_term" class="form-control select2">
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
                <input type="text" name="prepared_by_display" value="{{ auth()->user()->name }}" disabled
                    placeholder="Prepared By" class="form-control" />
                <input type="hidden" name="prepared_by" value="{{ auth()->user()->id }}" readonly
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
    $(document).ready(function() {
        $('.select2').select2();

        $('[name="company_location_id"], [name="contract_date"]').change(function() {
            generateContractNumber();
        });

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
                    beforeSend: function() {},
                    success: function(response) {
                        if (response.success) {
                            $('[name="contract_no"]').val(response.contract_no);
                        }
                    },
                    error: function(xhr) {
                        console.error(xhr.responseText);
                    }
                });
            }
        }

        $('#product_id').change(function() {
            var selectedOption = $(this).find('option:selected');

            var product_id = $(this).val();
            if (product_id) {
                $.ajax({
                    url: '{{ route('raw-material.getGateBuyingMainSlabByProduct') }}',
                    type: 'GET',
                    data: {
                        product_id: product_id
                    },
                    dataType: 'json',
                    beforeSend: function() {
                        Swal.fire({
                            title: "Processing...",
                            text: "Please wait while fetching slabs.",
                            allowOutsideClick: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });
                    },
                    success: function(response) {
                        Swal.close();
                        if (response.success) {
                            $('#slabsContainer').html(response.html);
                        } else {
                            Swal.fire("No Data", "No slabs found for this product.",
                                "info");
                        }
                    },
                    error: function() {
                        Swal.close();
                        Swal.fire("Error", "Something went wrong. Please try again.",
                            "error");
                    }
                });
            } else {
                $('#commodity_name').val('');
            }
        });

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

        $('[name="rate_per_kg"]').on('input', function() {
            calculateRates('rate_per_kg');
        });

        $('[name="rate_per_mound"]').on('input', function() {
            calculateRates('rate_per_mound');
        });

        $('[name="rate_per_100kg"]').on('input', function() {
            calculateRates('rate_per_100kg');
        });

        initializeDynamicSelect2('#broker_id', 'brokers', 'name', 'id', true, false);
        initializeDynamicSelect2('#company_location_id', 'company_locations', 'name', 'id', true, false);
    });
</script>
