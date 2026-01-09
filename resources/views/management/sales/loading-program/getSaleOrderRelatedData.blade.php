
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

        @if($DeliveryOrders->count() > 0)
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
            {{-- If no delivery orders, populate from Sale Order --}}
            @php
                // Get company location from Sale Order's locations relationship
                $soCompanyLocationId = $SalesOrder->locations->first()?->location_id;
                $soArrivalLocationId = $SalesOrder->arrival_location_id;
                $soSubArrivalLocationId = $SalesOrder->arrival_sub_location_id;
                
                $soCompanyLocation = $soCompanyLocationId ? \App\Models\Master\CompanyLocation::find($soCompanyLocationId) : null;
                $allArrivalLocations = \App\Models\Master\ArrivalLocation::all();
                $allSubArrivalLocations = \App\Models\Master\ArrivalSubLocation::all();
                
                $soArrivalLocation = $allArrivalLocations->where('id', $soArrivalLocationId)->first();
                $soSubArrivalLocation = $allSubArrivalLocations->where('id', $soSubArrivalLocationId)->first();
            @endphp

            @if($soCompanyLocation)
                var option = new Option('{{ $soCompanyLocation->name }}', '{{ $soCompanyLocation->id }}', true, true);
                companyLocationsSelect.append(option);
            @endif

            @if($soArrivalLocation)
                var option = new Option('{{ $soArrivalLocation->name }}', '{{ $soArrivalLocation->id }}', true, true);
                arrivalLocationsSelect.append(option);
            @endif

            @if($soSubArrivalLocation)
                var option = new Option('{{ $soSubArrivalLocation->name }}', '{{ $soSubArrivalLocation->id }}', true, true);
                subArrivalLocationsSelect.append(option);
            @endif
        @endif

        // Trigger change to refresh select2
        companyLocationsSelect.trigger('change');
        arrivalLocationsSelect.trigger('change');
        subArrivalLocationsSelect.trigger('change');

        // Note: Packing and brand will be set when delivery order is selected or from sale order if no DO
    });
</script>

