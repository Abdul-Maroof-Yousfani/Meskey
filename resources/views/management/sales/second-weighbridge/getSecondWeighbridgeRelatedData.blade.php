<div class="col-12">
    <h6 class="header-heading-sepration">
        Delivery Order Details
    </h6>
</div>

{{-- Delivery Order Details Section --}}
<div class="col-xs-12 col-sm-6 col-md-6">
    <div class="form-group">
        <label>Customer:</label>
        <input type="text" value="{{ $DeliveryOrder->customer->name ?? 'N/A' }}"
            disabled class="form-control" autocomplete="off" readonly />
    </div>
</div>

<div class="col-xs-12 col-sm-6 col-md-6">
    <div class="form-group">
        <label>Commodity:</label>
        <input type="text" value="{{ $DeliveryOrder->delivery_order_data->first()->item->name ?? 'N/A' }}"
            disabled class="form-control" autocomplete="off" readonly />
    </div>
</div>

<div class="col-xs-12 col-sm-6 col-md-6">
    <div class="form-group">
        <label>SO Qty:</label>
        <input type="text" value="{{ $DeliveryOrder->delivery_order_data->first()->salesOrderData->qty ?? 'N/A' }}"
            disabled class="form-control" autocomplete="off" readonly />
    </div>
</div>

<div class="col-xs-12 col-sm-6 col-md-6">
    <div class="form-group">
        <label>DO Qty:</label>
        <input type="text" value="{{ $DeliveryOrder->delivery_order_data->first()->qty ?? 'N/A' }}"
            disabled class="form-control" autocomplete="off" readonly />
    </div>
</div>

<div class="col-xs-12 col-sm-6 col-md-6">
    <div class="form-group">
        <label>Arrival Location:</label>
        <input type="text" value="{{ get_location_name_by_id($DeliveryOrder->location_id) }}"
            disabled class="form-control" autocomplete="off" readonly />
    </div>
</div>

<div class="col-xs-12 col-sm-6 col-md-6">
    <div class="form-group">
        <label>Factory:</label>
        <input type="text" value="{{ get_arrival_name_by_id($DeliveryOrder->arrival_location_id) }}"
            disabled class="form-control" autocomplete="off" readonly />
    </div>
</div>

<div class="col-xs-12 col-sm-6 col-md-6">
    <div class="form-group">
        <label>Sub Arrival Location ID:</label>
        <input type="text" value="{{ get_storage_name_by_id($DeliveryOrder->sub_arrival_location_id) }}"
            disabled class="form-control" autocomplete="off" readonly />
    </div>
</div>

<div class="col-xs-12 col-sm-6 col-md-6">
    <div class="form-group">
        <label>Transporter:</label>
        <input type="text" value=""
            disabled class="form-control" autocomplete="off" readonly />
    </div>
</div>

{{-- Before Loading Section --}}
<div class="col-12">
    <h6 class="header-heading-sepration">
        After Loading
    </h6>
</div>

<div class="col-xs-12 col-sm-4 col-md-4">
    <div class="form-group">
        <label>First Weight:</label>
        <input type="text" name="first_weight_display" id="first_weight_display"
            value="{{ $DeliveryOrder->firstWeighbridge->first_weight ?? 'N/A' }}"
            readonly class="form-control" autocomplete="off" />
        <input type="hidden" name="first_weight" value="{{ $DeliveryOrder->firstWeighbridge->first_weight ?? 0 }}" />
    </div>
</div>

<div class="col-xs-12 col-sm-4 col-md-4">
    <div class="form-group">
        <label>Second Weight:</label>
        <input type="number" name="second_weight" id="second_weight" placeholder="Enter Second Weight"
            value="{{ isset($SecondWeighbridge) ? $SecondWeighbridge->second_weight : '' }}"
            class="form-control" autocomplete="off" step="0.01" />
    </div>
</div>

<div class="col-xs-12 col-sm-4 col-md-4">
    <div class="form-group">
        <label>Net Weight:</label>
        <input type="text" name="net_weight" id="net_weight" placeholder="Net Weight"
            value="{{ isset($SecondWeighbridge) ? $SecondWeighbridge->net_weight : '' }}"
            readonly class="form-control" autocomplete="off" />
    </div>
</div>

<div class="col-xs-12 col-sm-6 col-md-6">
    <div class="form-group">
        <label><i class="ft-truck"></i> Truck Type:</label>
        <select class="form-control select2" name="truck_type_id" id="truck_type_id">
            <option value="">Select Truck Type</option>
            @foreach ($ArrivalTruckTypes ?? [] as $truckType)
                <option value="{{ $truckType->id }}" data-weighbridge-amount="{{ $truckType->weighbridge_amount ?? '' }}"
                    {{ isset($SecondWeighbridge) && ($SecondWeighbridge->truck_type_id ?? null) == $truckType->id ? 'selected' : '' }}>
                    {{ $truckType->name ?? '' }}
                </option>
            @endforeach
        </select>
    </div>
</div>

<div class="col-xs-12 col-sm-6 col-md-6">
    <div class="form-group">
        <label>Weighbridge Amount:</label>
        <input type="text" name="weighbridge_amount" id="weighbridge_amount" placeholder="Weighbridge Amount"
            value="{{ isset($SecondWeighbridge) ? $SecondWeighbridge->weighbridge_amount : '' }}"
            readonly class="form-control" autocomplete="off" />
    </div>
</div>

<div class="col-xs-12 col-sm-12 col-md-12">
    <div class="form-group">
        <label>Remark:</label>
        <textarea name="remark" placeholder="Remarks" class="form-control">{{ isset($SecondWeighbridge) ? $SecondWeighbridge->remark : '' }}</textarea>
    </div>
</div>

<script>
    $(document).ready(function() {
        $('.select2').select2();

        // Update weighbridge amount when truck type changes
        $('#truck_type_id').change(function() {
            var selectedOption = $(this).find('option:selected');
            var weighbridgeAmount = selectedOption.data('weighbridge-amount') || '';
            $('#weighbridge_amount').val(weighbridgeAmount);
        });

        // Calculate net weight when second weight changes
        $('#second_weight').on('input', function() {
            const firstWeight = parseFloat($('#first_weight_display').val()) || 0;
            const secondWeight = parseFloat($(this).val()) || 0;
            const netWeight = secondWeight - firstWeight;
            $('#net_weight').val(netWeight.toFixed(2));
        });
    });
</script>
