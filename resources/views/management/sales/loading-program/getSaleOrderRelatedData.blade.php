
<div class="col-12">
    <h6 class="header-heading-sepration">
        Sale Order Details
    </h6>
</div>

{{-- Sale Order Details Section --}}
    <div class="col-xs-12 col-sm-6 col-md-3">
        <div class="form-group">
            <label>Buyer:</label>
            <input type="text" value="{{ $SalesOrder->customer->name ?? 'N/A' }}"
                disabled class="form-control" autocomplete="off" readonly />
        </div>
    </div>

    <div class="col-xs-12 col-sm-6 col-md-3">
        <div class="form-group">
            <label>Commodity:</label>
            <input type="text" value="{{ $SalesOrder->sales_order_data->first()->item->name ?? 'N/A' }}"
                disabled class="form-control" autocomplete="off" readonly />
        </div>
    </div>

    <div class="col-xs-12 col-sm-6 col-md-3">
        <div class="form-group">
            <label>SO Date:</label>
            <input type="text" value="{{ $SalesOrder->order_date ? $SalesOrder->order_date : 'N/A' }}"
                disabled class="form-control" autocomplete="off" readonly />
        </div>
    </div>

    <div class="col-xs-12 col-sm-6 col-md-3">
        <div class="form-group">
            <label>SO Qty:</label>
            <input type="text" value="{{ $SalesOrder->sales_order_data->first()->qty ?? 'N/A' }}"
                disabled class="form-control" autocomplete="off" readonly />
        </div>
</div>


<script>
    $(document).ready(function() {
        // Update delivery order dropdown
        var currentSelectedDeliveryOrder = $('#delivery_order_id').val();
        $('#delivery_order_id').empty().append('<option value="">Select Delivery Order</option>');
        @foreach($DeliveryOrders as $deliveryOrder)
            var selected = (currentSelectedDeliveryOrder == '{{ $deliveryOrder->id }}') ? 'selected' : '';
            $('#delivery_order_id').append('<option value="{{ $deliveryOrder->id }}" ' + selected + '>{{ $deliveryOrder->reference_no }}</option>');
        @endforeach

        // Populate and pre-select company locations
        var companyLocationsSelect = $('#company_locations');
        companyLocationsSelect.empty();

        // Populate and pre-select arrival locations
        var arrivalLocationsSelect = $('#arrival_locations');
        arrivalLocationsSelect.empty();

        // Populate and pre-select sub arrival locations
        var subArrivalLocationsSelect = $('#sub_arrival_locations');
        subArrivalLocationsSelect.empty();

        @if($SalesOrder->pay_type_id != 11)
            @php
                $companyLocationIds = $DeliveryOrders->pluck('location_id')->unique()->toArray();
                $arrivalLocationIds = $DeliveryOrders->pluck('arrival_location_id')->unique()->toArray();
                $subArrivalLocationIds = $DeliveryOrders->pluck('sub_arrival_location_id')->unique()->toArray();

                $companyLocations = \App\Models\Master\CompanyLocation::whereIn('id', $companyLocationIds)->get();
                $arrivalLocations = \App\Models\Master\ArrivalLocation::whereIn('id', $arrivalLocationIds)->get();
                $subArrivalLocations = \App\Models\Master\ArrivalSubLocation::whereIn('id', $subArrivalLocationIds)->get();
            @endphp

            @foreach($companyLocations as $location)
                var option = new Option('{{ $location->name }}', '{{ $location->id }}', true, true);
                companyLocationsSelect.append(option);
                companyLocationsSelect.prop("multiple", true)
                companyLocationsSelect.prop("disabled", true)
            @endforeach

            @foreach($arrivalLocations as $location)
                var option = new Option('{{ $location->name }}', '{{ $location->id }}', true, true);
                arrivalLocationsSelect.append(option);
            @endforeach

            @foreach($subArrivalLocations as $location)
                var option = new Option('{{ $location->name }}', '{{ $location->id }}', true, true);
                subArrivalLocationsSelect.append(option);
            @endforeach
        @else
            @php
                // Get company location from Sale Order's locations relationship
                $soCompanyLocationIds = $SalesOrder->locations->pluck('location_id')->toArray();
                $soArrivalLocationId = $SalesOrder->factories->pluck('arrival_location_id')->toArray();
                $soSectionLocationId = $SalesOrder->sections->pluck('arrival_sub_location_id')->toArray();
                
                $soCompanyLocation = $soCompanyLocationIds ? \App\Models\Master\CompanyLocation::whereIn('id', $soCompanyLocationIds)->get() : null;
                
                $soArrivalLocation = \App\Models\Master\ArrivalLocation::whereIn('id', $soArrivalLocationId)->get();
                $soSubArrivalLocation = \App\Models\Master\ArrivalSubLocation::whereIn('id', $soSectionLocationId)->get();
            @endphp
            @if($soCompanyLocation)
                @foreach($soCompanyLocation as $location)
                    var option = new Option('{{ $location->name }}', '{{ $location->id }}', true, true);
                    companyLocationsSelect.append(option);
                    companyLocationsSelect.prop("multiple", false)
                    companyLocationsSelect.prop("disabled", false)
                @endforeach
            @endif

            @if($soArrivalLocation)
                @foreach($soArrivalLocation as $location)
                    var option = new Option('{{ $location->name }}', '{{ $location->id }}', true, true);
                    arrivalLocationsSelect.append(option);
                @endforeach
            @endif

            @if($soSubArrivalLocation)
                @foreach($soSubArrivalLocation as $location)
                    var option = new Option('{{ $location->name }}', '{{ $location->id }}', true, true);
                    subArrivalLocationsSelect.append(option);
                @endforeach
            @endif
        @endif

        // Trigger change to refresh select2
        companyLocationsSelect.trigger('change');
        arrivalLocationsSelect.trigger('change');
        subArrivalLocationsSelect.trigger('change');

        // Note: Packing and brand will be set when delivery order is selected or from sale order if no DO
    });
</script>
<script>
       function updateItemLocations() {
            const selectedArrivalLocations = $('#arrival_locations').val() || [];
            const selectedSubArrivalLocations = $('#sub_arrival_locations').val() || [];

            // Update arrival location options
            $('.arrival-location-select').each(function() {
                const $select = $(this);
                const currentValue = $select.val(); // Store current selected value
                $select.empty().append('<option value="">Select Location</option>');

                // Get location names from the main arrival_locations select
                $('#arrival_locations option').each(function() {
                    const value = $(this).val();
                    const text = $(this).text();
                    if (value && selectedArrivalLocations.includes(value)) {
                        const option = new Option(text, value, false, currentValue == value);
                        $select.append(option);
                    }
                });

                // Trigger change to update corresponding gala dropdown
                if ($select.val()) {
                    $select.trigger('change');
                }
            });

            // Update sub arrival location options based on selected factory
            updateGalaOptionsForAllRows();
        }
         function updateGalaOptionsForAllRows() {
            $('.arrival-location-select').each(function() {
                updateGalaOptions($(this));
            });
        }
        function updateGalaOptions($factorySelect) {
            const selectedFactoryId = $factorySelect.val();
            const $row = $factorySelect.closest('tr');
            const $galaSelect = $row.find('.sub-arrival-location-select');
            const currentGalaValue = $galaSelect.val();
            const selectedSubArrivalLocations = $('#sub_arrival_locations').val() || [];

            $galaSelect.empty().append('<option value="">Select Sub Location</option>');

            if (selectedFactoryId) {
                // Filter sub arrival locations that:
                // 1. Belong to the selected factory (arrival_location_id matches)
                // 2. Are in the delivery order's sub arrival locations
                allSubArrivalLocations.forEach(function(subLocation) {
                    if (subLocation.arrival_location_id == selectedFactoryId &&
                        selectedSubArrivalLocations.includes(subLocation.id.toString())) {
                        const option = new Option(subLocation.name, subLocation.id, false,
                            currentGalaValue == subLocation.id);
                        $galaSelect.append(option);
                    }
                });
            }

            // Reinitialize select2
            $galaSelect.select2();
        }
    $("#company_locations").change(function() {
        if(!$(this).prop("multiple")) {
            const company_location = $(this).val();
            const sale_order_id = $("#sale_order_id").val();
        
            $.ajax({
                url: "{{ route('sales.get.locations') }}",      
                type: 'GET',                
                data: {
                    sale_order_id,
                    company_location
                },
                dataType: 'json',           
                success: function(response) {
                    const [arrivalLocation, subArrivalLocation] = response;
                    // sub_arrival_locations
                    // Destroy old Select2 and empty
                    $('#arrival_locations').select2('destroy');
                    $('#arrival_locations').empty();

                    // Append all options
                    arrivalLocation.forEach(function(loc){
                        let option = new Option(loc.text, loc.id, true, true); // true = selected
                        $('#arrival_locations').append(option);
                    });

                    // Re-init Select2
                    $('#arrival_locations').select2();
                    // do something with response



                    $('#sub_arrival_locations').select2('destroy');
                    $('#sub_arrival_locations').empty();

                    // Append all options
                    subArrivalLocation.forEach(function(loc){
                        let option = new Option(loc.text, loc.id, true, true); // true = selected
                        $('#sub_arrival_locations').append(option);
                    });

                    // Re-init Select2
                    $('#sub_arrival_locations').select2();
                    // do something with response

                    updateItemLocations();

                },
                error: function(xhr, status, error) {
                    // handle errors
                }
            });
        }
    })
</script>

