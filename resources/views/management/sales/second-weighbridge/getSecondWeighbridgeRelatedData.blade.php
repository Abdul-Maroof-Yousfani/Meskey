<div class="col-12">
    <h6 class="header-heading-sepration">
        Loading Slip Details
    </h6>
</div>

{{-- Loading Slip Details Section --}}
<div class="col-xs-12 col-sm-6 col-md-6">
    <div class="form-group">
        <label>Customer:</label>
        <input type="text" value="{{ $LoadingSlip->loadingProgramItem->loadingProgram->deliveryOrder->customer->name ?? 'N/A' }}"
            disabled class="form-control" autocomplete="off" readonly />
    </div>
</div>

<div class="col-xs-12 col-sm-6 col-md-6">
    <div class="form-group">
        <label>Commodity:</label>
        <input type="text" value="{{ $LoadingSlip->loadingProgramItem->loadingProgram->deliveryOrder->delivery_order_data->first()->item->name ?? 'N/A' }}"
            disabled class="form-control" autocomplete="off" readonly />
    </div>
</div>

<div class="col-xs-12 col-sm-6 col-md-6">
    <div class="form-group">
        <label>SO Qty:</label>
        <input type="text" value="{{ $LoadingSlip->loadingProgramItem->loadingProgram->deliveryOrder->delivery_order_data->first()->salesOrderData->qty ?? 'N/A' }}"
            disabled class="form-control" autocomplete="off" readonly />
    </div>
</div>

<div class="col-xs-12 col-sm-6 col-md-6">
    <div class="form-group">
        <label>DO Qty:</label>
        <input type="text" value="{{ $LoadingSlip->loadingProgramItem->loadingProgram->deliveryOrder->delivery_order_data->first()->qty ?? 'N/A' }}"
            disabled class="form-control" autocomplete="off" readonly />
    </div>
</div>

<div class="col-xs-12 col-sm-6 col-md-6">
    <div class="form-group">
        <label>Factory:</label>
        <select class="form-control select2 w-100" id="factory_display" multiple disabled style="width: 100% !important;">
            @php
                $deliveryOrder = $LoadingSlip->loadingProgramItem->loadingProgram->deliveryOrder ?? null;
                if ($deliveryOrder && $deliveryOrder->arrival_location_id) {
                    $arrivalLocationIds = explode(',', $deliveryOrder->arrival_location_id);
                    $arrivalLocations = \App\Models\Master\ArrivalLocation::whereIn('id', $arrivalLocationIds)->get();
                    foreach($arrivalLocations as $location) {
                        echo '<option value="' . $location->id . '" selected>' . $location->name . '</option>';
                    }
                }
            @endphp
        </select>
    </div>
</div>

<div class="col-xs-12 col-sm-6 col-md-6">
    <div class="form-group">
        <label>Gala:</label>
        <select class="form-control select2 w-100" id="gala_display" multiple disabled style="width: 100% !important;">
            @php
                if ($deliveryOrder && $deliveryOrder->sub_arrival_location_id) {
                    $subArrivalLocationIds = explode(',', $deliveryOrder->sub_arrival_location_id);
                    $subArrivalLocations = \App\Models\Master\ArrivalSubLocation::whereIn('id', $subArrivalLocationIds)->get();
                    foreach($subArrivalLocations as $location) {
                        echo '<option value="' . $location->id . '" selected>' . $location->name . '</option>';
                    }
                }
            @endphp
        </select>
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
            value="{{ $LoadingSlip->loadingProgramItem->firstWeighbridge->first_weight ?? 'N/A' }}"
            readonly class="form-control" autocomplete="off" />
        <input type="hidden" name="first_weight" value="{{ $LoadingSlip->loadingProgramItem->firstWeighbridge->first_weight ?? 0 }}" />
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


<div class="col-xs-12 col-sm-12 col-md-12">
    <div class="form-group">
        <label>Remark:</label>
        <textarea name="remark" placeholder="Remarks" class="form-control">{{ isset($SecondWeighbridge) ? $SecondWeighbridge->remark : '' }}</textarea>
    </div>
</div>

<script>
    $(document).ready(function() {
        $('.select2').select2();

        // Calculate net weight when second weight changes
        $('#second_weight').on('input', function() {
            const firstWeight = parseFloat($('#first_weight_display').val()) || 0;
            const secondWeight = parseFloat($(this).val()) || 0;
            const netWeight = secondWeight - firstWeight;
            $('#net_weight').val(netWeight.toFixed(2));
        });
    });
</script>
