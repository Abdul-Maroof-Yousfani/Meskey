<div class="col-12">
    <h6 class="header-heading-sepration">
        {{ $DeliveryOrder ? 'Delivery Order Details' : 'Sale Order Details' }}
    </h6>
</div>

{{-- Details Section - Show from Delivery Order if available, otherwise from Sale Order --}}
<div class="col-xs-12 col-sm-6 col-md-6">
    <div class="form-group">
        <label>Customer:</label>
        @if($DeliveryOrder)
            <input type="text" value="{{ $DeliveryOrder->customer->name ?? 'N/A' }}"
                disabled class="form-control" autocomplete="off" readonly />
        @else
            <input type="text" value="{{ $SaleOrder->customer->name ?? 'N/A' }}"
                disabled class="form-control" autocomplete="off" readonly />
        @endif
    </div>
</div>

<div class="col-xs-12 col-sm-6 col-md-6">
    <div class="form-group">
        <label>Commodity:</label>
        @if($DeliveryOrder)
            <input type="text" value="{{ $DeliveryOrder->delivery_order_data->first()->item->name ?? 'N/A' }}"
                disabled class="form-control" autocomplete="off" readonly />
        @else
            <input type="text" value="{{ $SaleOrder->sales_order_data->first()->item->name ?? 'N/A' }}"
                disabled class="form-control" autocomplete="off" readonly />
        @endif
    </div>
</div>

<div class="col-xs-12 col-sm-6 col-md-6">
    <div class="form-group">
        <label>SO Qty:</label>
        @if($DeliveryOrder)
            <input type="text" value="{{ $DeliveryOrder->delivery_order_data->first()->salesOrderData->qty ?? 'N/A' }}"
                disabled class="form-control" autocomplete="off" readonly />
        @else
            <input type="text" value="{{ $SaleOrder->sales_order_data->first()->qty ?? 'N/A' }}"
                disabled class="form-control" autocomplete="off" readonly />
        @endif
    </div>
</div>

<div class="col-xs-12 col-sm-6 col-md-6">
    <div class="form-group">
        <label>DO Qty:</label>
        @if($DeliveryOrder)
            <input type="text" value="{{ $DeliveryOrder->delivery_order_data->first()->qty ?? 'N/A' }}"
                disabled class="form-control" autocomplete="off" readonly />
        @else
            <input type="text" value="N/A"
                disabled class="form-control" autocomplete="off" readonly />
        @endif
    </div>
</div>

<div class="col-xs-12 col-sm-6 col-md-6">
    <div class="form-group">
        <label>Arrival Location:</label>
        @if($DeliveryOrder)
            <input type="text" value="{{ get_location_name_by_id($DeliveryOrder->location_id) }}"
                disabled class="form-control" autocomplete="off" readonly />
        @else
            @php
                // Get company location from loading program when no delivery order
                $companyLocationIds = $LoadingProgramItem->loadingProgram->company_locations ?? [];
                $companyLocationId = is_array($companyLocationIds) ? ($companyLocationIds[0] ?? null) : $companyLocationIds;
                $companyLocationName = $companyLocationId ? get_location_name_by_id($companyLocationId) : 'N/A';
            @endphp
            <input type="text" value="{{ $companyLocationName }}"
                disabled class="form-control" autocomplete="off" readonly />
        @endif
    </div>
</div>

<div class="col-xs-12 col-sm-6 col-md-6">
    <div class="form-group">
        <label>Factory:</label>
        <select class="form-control select2 w-100" name="arrival_locations[]" id="arrival_locations" multiple disabled style="width: 100% !important;">
            @php
                $arrivalLocations = \App\Models\Master\ArrivalLocation::where('id', $LoadingProgramItem->arrival_location_id)->get();
            @endphp
            @foreach($arrivalLocations as $location)
                <option value="{{ $location->id }}" selected>{{ $location->name }}</option>
            @endforeach
        </select>
    </div>
</div>

<div class="col-xs-12 col-sm-6 col-md-6">
    <div class="form-group">
        <label>Sub Arrival Location:</label>
        <select class="form-control select2 w-100" name="sub_arrival_locations[]" id="sub_arrival_locations" multiple disabled style="width: 100% !important;">
            @php
                $subArrivalLocations = \App\Models\Master\ArrivalSubLocation::where('id', $LoadingProgramItem->sub_arrival_location_id)->get();
            @endphp
            @foreach($subArrivalLocations as $location)
                <option value="{{ $location->id }}" selected>{{ $location->name }}</option>
            @endforeach
        </select>
    </div>
</div>


{{-- Before Loading Section --}}
<div class="col-12">
    <h6 class="header-heading-sepration">
        Before Loading
    </h6>
</div>

<div class="col-xs-12 col-sm-4 col-md-4">
    <div class="form-group">
        <label>First Weight:</label>
        <input type="number" name="first_weight" placeholder="Enter First Weight"
            value="{{ isset($FirstWeighbridge) ? $FirstWeighbridge->first_weight : '' }}"
            class="form-control" autocomplete="off" step="0.01" />
    </div>
</div>

<div class="col-xs-12 col-sm-4 col-md-4">
    <div class="form-group">
        <label><i class="ft-truck"></i> Truck Type:</label>
        <select class="form-control select2" name="truck_type_id" id="truck_type_id">
            <option value="">Select Truck Type</option>
            @foreach ($ArrivalTruckTypes ?? [] as $truckType)
                <option value="{{ $truckType->id }}" data-weighbridge-amount="{{ $truckType->weighbridge_amount ?? '' }}"
                    {{ isset($FirstWeighbridge) && ($FirstWeighbridge->truck_type_id ?? null) == $truckType->id ? 'selected' : '' }}>
                    {{ $truckType->name ?? '' }}
                </option>
            @endforeach
        </select>
    </div>
</div>

<div class="col-xs-12 col-sm-4 col-md-4">
    <div class="form-group">
        <label>Weighbridge Amount:</label>
        <input type="text" name="weighbridge_amount" id="weighbridge_amount" placeholder="Weighbridge Amount"
            value="{{ isset($FirstWeighbridge) ? $FirstWeighbridge->weighbridge_amount : '' }}"
            readonly class="form-control" autocomplete="off" />
    </div>
</div>

<div class="col-xs-12 col-sm-12 col-md-12">
    <div class="form-group">
        <label>Remark:</label>
        <textarea name="remark" placeholder="Remarks" class="form-control">{{ isset($FirstWeighbridge) ? $FirstWeighbridge->remark : '' }}</textarea>
    </div>
</div>

<script>
    $(document).ready(function() {
        $('.select2').select2();

        // Update weighbridge amount when truck type changes
        $('#truck_type_id').change(function() {
            var truckTypeId = $(this).val();
            var loadingProgramItemId = '{{ $LoadingProgramItem->id }}';

            if (truckTypeId && loadingProgramItemId) {
                $.ajax({
                    url: '{{ route('sales.getWeighbridgeAmount') }}',
                    type: 'GET',
                    data: {
                        truck_type_id: truckTypeId,
                        loading_program_item_id: loadingProgramItemId
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#weighbridge_amount').val(response.weighbridge_amount);
                        } else {
                            $('#weighbridge_amount').val('');
                            Swal.fire({
                                icon: 'warning',
                                title: 'Not Found',
                                text: response.message
                            });
                        }
                    },
                    error: function(xhr) {
                        $('#weighbridge_amount').val('');
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Failed to fetch weighbridge amount.'
                        });
                    }
                });
            } else {
                $('#weighbridge_amount').val('');
            }
        });

        // Trigger change event on page load if truck type is already selected
        if ($('#truck_type_id').val()) {
            $('#truck_type_id').trigger('change');
        }
    });
</script>
