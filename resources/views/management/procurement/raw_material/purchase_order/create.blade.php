<form action="{{ route('raw-material.purchase-order.create') }}" method="POST" id="ajaxSubmit" autocomplete="off">
    @csrf
    <input type="hidden" id="listRefresh" value="{{ route('raw-material.get.purchase-request') }}" />
    <div class="row form-mar">
        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group">
                <label>Contract No:</label>
                <input type="text" readonly name="contract_no" placeholder="Contract No" class="form-control" />
            </div>
        </div>
        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group">
                <label>Contract Date:</label>
                <input type="date" name="contract_date" placeholder="Contract Date" class="form-control" />
            </div>
        </div>


        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group ">
                <label>Location:</label>
                <select name="company_location_id" id="company_location_id" class="form-control select2">
                    <option value="">Location</option>
                </select>
            </div>
        </div>
        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group ">
                <label>Sauda Type:</label>
                <select name="sauda_type_id" id="sauda_type_id" class="form-control select2">
                    <option value="">Sauda Type Name</option>
                </select>
            </div>
        </div>


    </div>



    <div class="row">
        <div class="col-12">
            <h6 class="header-heading-sepration">
                Supplier
            </h6>
        </div>
        {{-- Supplier & Borkers --}}
        <div class="col-xs-8 col-sm-8 col-md-8">
            <div class="form-group ">
                <label>Supplier:</label>
                <select name="supplier_id" id="supplier_id" class="form-control select2">
                    <option value="">Supplier</option>
                </select>
            </div>
        </div>
        <div class="col-xs-4 col-sm-4 col-md-4">
            <div class="form-group ">
                <label>Commission:</label>
                <input type="number" name="supplier_commission" placeholder="Commission" class="form-control" />

            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <h6 class="header-heading-sepration">
                Broker 1
            </h6>
        </div>
        <div class="col-xs-8 col-sm-8 col-md-8">
            <div class="form-group ">
                <label>Broker:</label>
                <select name="broker_one_id" id="broker_one_id" class="form-control select2">
                    <option value="">Broker </option>
                </select>
            </div>
        </div>

        <div class="col-xs-4 col-sm-4 col-md-4">
            <div class="form-group ">
                <label>Commission:</label>
                <input type="number" name="supplier_commission" placeholder="Commission" class="form-control" />

            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-12">
            <h6 class="header-heading-sepration">
                Broker 2
            </h6>
        </div>
        <div class="col-xs-8 col-sm-8 col-md-8">
            <div class="form-group ">
                <label>Broker:</label>
                <select name="broker_two_id" id="broker_two_id" class="form-control select2">
                    <option value="">Broker</option>
                </select>
            </div>
        </div>
        <div class="col-xs-4 col-sm-4 col-md-4">
            <div class="form-group ">
                <label>Commission:</label>
                <input type="number" name="supplier_commission" placeholder="Commission" class="form-control" />

            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-12">
            <h6 class="header-heading-sepration">
                Broker 3
            </h6>
        </div>
        <div class="col-xs-8 col-sm-8 col-md-8">
            <div class="form-group ">
                <label>Broker:</label>
                <select name="broker_three_id" id="broker_three_id" class="form-control select2">
                    <option value="">Broker</option>
                </select>
            </div>
        </div>
        <div class="col-xs-4 col-sm-4 col-md-4">
            <div class="form-group ">
                <label>Commission:</label>
                <input type="number" name="supplier_commission" placeholder="Commission" class="form-control" />

            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group ">
                <label>Commodity:</label>
                <select name="product_id" id="product_id" class="form-control select2">
                    <option value="">Commodity</option>
                </select>
            </div>
        </div>
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group ">
                <label>Line:</label>
                <select name="line_type" id="line_type" class="form-control select2">
                    <option value="">Select line</option>
                    <option value="bari">Bari</option>
                    <option value="choti">Choti</option>
                </select>
            </div>
        </div>
        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group ">
                <label>Bags Weight:</label>
                <select name="bag_weight" id="bag_weight" class="form-control select2">
                    <option value="">Bags Weight</option>
                    <option value="5">5Kg</option>
                    <option value="10">10Kg</option>
                    <option value="15">15Kg</option>
                    <option value="20">20Kg</option>
                    <option value="25">25Kg</option>
                    <option value="30">30Kg</option>
                </select>
            </div>
        </div>
        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group ">
                <label>Bags Rate:</label>
                <select name="line_type" id="line_type" class="form-control select2">
                    <option value="">Bags Rate</option>
                    <option value="5">5Kg</option>
                    <option value="10">10Kg</option>
                    <option value="15">15Kg</option>
                    <option value="20">20Kg</option>
                    <option value="25">25Kg</option>
                    <option value="30">30Kg</option>
                </select>
            </div>
        </div>
        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group">
                <label>Delivery Date:</label>
                <input type="date" name="delivery_date" placeholder="Delivery Date" class="form-control" />
            </div>
        </div>
        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group ">
                <label>Credit Days:</label>
                <select name="line_type" id="line_type" class="form-control select2">
                    <option value="">Credit Days</option>
                    <option value="7">7 Days</option>
                    <option value="15">15 Days</option>
                    <option value="30">30 Days</option>
                </select>
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





    <div class="row">
        <div class="col-12">
            <h6 class="header-heading-sepration">
                Quantity Calculation
            </h6>
        </div>

        <div class="col-xs-4 col-sm-4 col-md-4">
            <div class="form-group">
                <label>Type:</label>
                <select name="calculation_type" id="calculation_type" class="form-control select2">
                    <option value="trucks">Trucks Wise</option>
                    <option value="quantity">Quantity Wise</option>
                </select>
            </div>
        </div>

        <div class="col-xs-4 col-sm-4 col-md-4" id="trucks-field">
            <div class="form-group">
                <label>No of Trucks:</label>
                <input type="number" name="no_of_trucks" id="no_of_trucks" placeholder="Number of Trucks"
                    class="form-control" min="1" />
                <small class="text-muted">Each truck carries 25,000-30,000 kg</small>
            </div>
        </div>

        <div class="col-xs-4 col-sm-4 col-md-4" id="quantity-field" style="display:none;">
            <div class="form-group">
                <label>Total Quantity (kg):</label>
                <input type="number" name="total_quantity" id="total_quantity" placeholder="Total Quantity"
                    class="form-control" min="25000" />
            </div>
        </div>

        <div class="col-xs-4 col-sm-4 col-md-4">
            <div class="form-group">
                <label>Quantity Range:</label>
                <input type="text" name="quantity_range" id="quantity_range" placeholder="Quantity Range"
                    class="form-control" readonly />
            </div>
        </div>

        <div class="col-xs-4 col-sm-4 col-md-4">
            <div class="form-group">
                <label>No of Bags Range:</label>
                <input type="text" name="bags_range" id="bags_range" placeholder="Bags Range" class="form-control"
                    readonly />
            </div>
        </div>


        {{-- <div class="col-xs-4 col-sm-4 col-md-4">
            <div class="form-group">
                <label>Bags Weight:</label>
                <select name="bag_weight" id="bag_weight" class="form-control select2">
                    <option value="100">100 kg</option>
                    <option value="50">50 kg</option>
                    <option value="25">25 kg</option>
                </select>
            </div>
        </div> --}}
    </div>

    <div class="row">
        <div class="col-12">
            <h6 class="header-heading-sepration">
                Others
            </h6>
        </div>
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label class=" label-control font-weight-bold" for="lumpsum-toggle-initial">Replacement / No Replacement
                </label>
                <div class="custom-control custom-switch">
                    <input type="checkbox" name="is_lumpsum_deduction_initial" class="custom-control-input"
                        id="lumpsum-toggle-initial">
                    <label class="custom-control-label" for="lumpsum-toggle-initial"></label>
                </div>
            </div>
        </div>
         <div class="col-xs-4 col-sm-4 col-md-4">
            <div class="form-group ">
                <label>Weighbridge only From:</label>
                <input type="number" name="rate_per_100kg" placeholder="Rate Per 100KG" class="form-control" />

            </div>
        </div>

        <!-- Description -->
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Delivery Address:</label>
                <textarea name="remarks" placeholder="Remarks" class="form-control"></textarea>
            </div>
        </div>
        <!-- Description -->
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Remarks (Optional):</label>
                <textarea name="remarks" placeholder="Remarks" class="form-control"></textarea>
            </div>
        </div>
        <!-- Status -->
        {{-- <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Status:</label>
                <select class="form-control" name="status">
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
        </div> --}}
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

        $('#product_id').change(function() {
            var selectedOption = $(this).find('option:selected');


            var product_id = $(this).val();
            if (product_id) {
                $.ajax({
                    url: '{{ route('raw-material.getMainSlabByProduct') }}',
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
                // Clear commodity field if no ticket selected
                $('#commodity_name').val('');
            }
        });
    });
</script>


<script>
    $(document).ready(function () {
        // Conversion factors
        const KG_PER_MOUND = 40; // 1 mound = 40 kg
        const KG_PER_100KG = 100; // self-explanatory

        // Function to calculate rates based on the changed field
        function calculateRates(changedField) {
            const ratePerKg = parseFloat($('[name="rate_per_kg"]').val()) || 0;
            const ratePerMound = parseFloat($('[name="rate_per_mound"]').val()) || 0;
            const ratePer100kg = parseFloat($('[name="rate_per_100kg"]').val()) || 0;

            switch (changedField) {
                case 'rate_per_kg':
                    // Calculate mound and 100kg rates based on kg rate
                    $('[name="rate_per_mound"]').val((ratePerKg * KG_PER_MOUND).toFixed(2));
                    $('[name="rate_per_100kg"]').val((ratePerKg * KG_PER_100KG).toFixed(2));
                    break;

                case 'rate_per_mound':
                    // Calculate kg and 100kg rates based on mound rate
                    $('[name="rate_per_kg"]').val((ratePerMound / KG_PER_MOUND).toFixed(2));
                    $('[name="rate_per_100kg"]').val((ratePerMound / KG_PER_MOUND * KG_PER_100KG).toFixed(2));
                    break;

                case 'rate_per_100kg':
                    // Calculate kg and mound rates based on 100kg rate
                    $('[name="rate_per_kg"]').val((ratePer100kg / KG_PER_100KG).toFixed(2));
                    $('[name="rate_per_mound"]').val((ratePer100kg / KG_PER_100KG * KG_PER_MOUND).toFixed(2));
                    break;
            }
        }

        // Set up event listeners for all rate fields
        $('[name="rate_per_kg"]').on('input', function () {
            calculateRates('rate_per_kg');
        });

        $('[name="rate_per_mound"]').on('input', function () {
            calculateRates('rate_per_mound');
        });

        $('[name="rate_per_100kg"]').on('input', function () {
            calculateRates('rate_per_100kg');
        });






        $(document).ready(function () {
            const TRUCK_MIN = 25000; // 25,000 kg per truck
            const TRUCK_MAX = 30000; // 30,000 kg per truck

            // Toggle between trucks and quantity input
            $('#calculation_type').change(function () {
                if ($(this).val() === 'trucks') {
                    $('#trucks-field').show();
                    $('#quantity-field').hide();
                } else {
                    $('#trucks-field').hide();
                    $('#quantity-field').show();
                }
            });

            // Calculate ranges when values change
            $('#no_of_trucks, #total_quantity, #bag_weight').on('input change', function () {
                calculateQuantityAndBags();
            });

            function calculateQuantityAndBags() {
                const bagWeight = parseInt($('#bag_weight').val()) || 100;
                let minQuantity, maxQuantity;

                if ($('#calculation_type').val() === 'trucks') {
                    const trucks = parseInt($('#no_of_trucks').val()) || 0;
                    minQuantity = trucks * TRUCK_MIN;
                    maxQuantity = trucks * TRUCK_MAX;
                } else {
                    const quantity = parseInt($('#total_quantity').val()) || 0;
                    minQuantity = quantity;
                    maxQuantity = quantity;
                }

                // Calculate bags
                const minBags = Math.ceil(minQuantity / bagWeight);
                const maxBags = Math.ceil(maxQuantity / bagWeight);

                // Update display fields
                if ($('#calculation_type').val() === 'trucks') {
                    $('#quantity_range').val(minQuantity.toLocaleString() + ' - ' + maxQuantity.toLocaleString() + ' kg');
                    $('#bags_range').val(minBags.toLocaleString() + ' - ' + maxBags.toLocaleString() + ' bags');
                } else {
                    $('#quantity_range').val(minQuantity.toLocaleString() + ' kg');
                    $('#bags_range').val(minBags.toLocaleString() + ' - ' + maxBags.toLocaleString() + ' bags');
                }
            }

            // Initialize calculation
            calculateQuantityAndBags();
        });



        // Your existing select2 initialization code
        initializeDynamicSelect2('#product_id', 'products', 'name', 'id', false, false);
        initializeDynamicSelect2('#company_location_id', 'company_locations', 'name', 'id', true, false);
        initializeDynamicSelect2('#sauda_type_id', 'sauda_types', 'name', 'id', true, false);
        initializeDynamicSelect2('#supplier_id', 'brokers', 'name', 'id', true, false);
        initializeDynamicSelect2('#broker_one_id', 'brokers', 'name', 'id', true, false);
        initializeDynamicSelect2('#broker_two_id', 'brokers', 'name', 'id', true, false);
        initializeDynamicSelect2('#broker_three_id', 'brokers', 'name', 'id', true, false);
    });
</script>