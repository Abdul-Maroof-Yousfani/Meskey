<div class="col-12">
    <h6 class="header-heading-sepration">
        Ticket Detail
    </h6>
</div>
<div class="col-xs-12 col-sm-12 col-md-12">
    <div class="form-group ">
        <label>Commodity:</label>
        <input type="text" placeholder="First Weight" value="{{ optional($ArrivalTicket->qcProduct)->name ?? 'N/A' }}"
            disabled class="form-control" autocomplete="off" />
    </div>
</div>
<div class="col-xs-6 col-sm-6 col-md-6">
    <div class="form-group">
        <label><i class="ft-truck"></i> Truck Type:</label>
        <input type="text" placeholder="First Weight" value="{{ $ArrivalTicket->truckType->name }}" disabled
            class="form-control" autocomplete="off" />
    </div>
</div>

<div class="col-xs-6 col-sm-6 col-md-6">
    <div class="form-group ">
        <label>Weighbridge Money:</label>
        <input type="text" placeholder="First Weight" value="{{ $ArrivalTicket->truckType->weighbridge_amount }}"
            disabled class="form-control" autocomplete="off" />
    </div>
</div>

<div class="col-12">
    <h6 class="header-heading-sepration">
        Loaded Weight
    </h6>
</div>
<div class="col-xs-4 col-sm-4 col-md-4">
    <div class="form-group ">
        <label>1st Weight:</label>
        <input type="text" id="first_weight" placeholder="First Weight" value="{{ $ArrivalTicket->first_weight }}"
            readonly class="form-control" autocomplete="off" />
    </div>
</div>
<div class="col-xs-4 col-sm-4 col-md-4">
    <div class="form-group ">
        <label>2nd Weight:</label>
        <input type="text" id="second_weight" placeholder="Second Weight" value="{{ $ArrivalTicket->second_weight }}"
            disabled class="form-control" autocomplete="off" />
    </div>
</div>
<div class="col-xs-4 col-sm-4 col-md-4">
    <div class="form-group ">
        <label>Net Weight:</label>
        <input type="text" id="loaded_net_weight" placeholder="Net Weight" value="{{ $ArrivalTicket->net_weight }}"
            disabled class="form-control" autocomplete="off" />
    </div>
</div>

<div class="col-12">
    <h6 class="header-heading-sepration">
        Arrived Weighbridge
    </h6>
</div>
<div class="col-xs-4 col-sm-4 col-md-4">
    <div class="form-group ">
        <label>1st Weighbridge Weight:</label>
        <input type="text" id="first_weighbridge" value="{{ $ArrivalTicket->firstWeighbridge->weight }}"
            class="form-control" autocomplete="off" disabled />
    </div>
</div>
<div class="col-xs-4 col-sm-4 col-md-4">
    <div class="form-group">
        <label>2nd Weighbridge Weight:</label>
        <input type="number" id="second_weighbridge" name="second_weight" placeholder="Enter Second Weighbridge"
            class="form-control" autocomplete="off" max="{{ $ArrivalTicket->firstWeighbridge->weight }}" />
    </div>
</div>
<div class="col-xs-4 col-sm-4 col-md-4">
    <div class="form-group ">
        <label>Net Weighbridge Weight:</label>
        <input type="text" id="weighbridge_net_weight" name="weighbridge_net_weight" placeholder="Net Weighbridge"
            readonly class="form-control" autocomplete="off" />
    </div>
</div>

{{-- <div class="col-xs-12 col-sm-12 col-md-12">
    <div class="form-group">
        <label>Weight Difference:</label>
        <input type="text" id="weight_difference" name="weight_difference" placeholder="Weight Difference" readonly class="form-control" autocomplete="off" />
    </div>
</div> --}}

<div class="col-xs-12 col-sm-12 col-md-12">

    <fieldset>
        <div class="input-group">
            <div class="input-group-prepend">
                <button class="btn btn-primary" type="button">Weight Difference</button>
            </div>
            <input type="text" id="weight_difference" name="weight_difference" placeholder="Weight Difference"
                readonly class="form-control" autocomplete="off" />

        </div>
    </fieldset>
</div>

<div class="col-xs-12 col-sm-12 col-md-12">
    <div class="form-group">
        <label>Comment:</label>
        <textarea name="remark" placeholder="Remarks" class="form-control"></textarea>
    </div>
</div>

{{-- <script>
$(document).ready(function() {
    // Calculate weights when second weighbridge value changes
    $('#second_weighbridge').on('input', function() {
        const firstWB = parseFloat($('#first_weighbridge').val()) || 0;
        const secondWB = parseFloat($(this).val()) || 0;
        const errorElement = $('#weight_error');
        
        // Validate 2nd weight isn't greater than 1st weight
        if (secondWB > firstWB) {
            errorElement.removeClass('d-none');
            $('#weighbridge_net_weight').val('');
            return;
        } else {
            errorElement.addClass('d-none');
        }
        
        // Calculate weighbridge net weight
        const weighbridgeNet = firstWB - secondWB;
        $('#weighbridge_net_weight').val(weighbridgeNet.toFixed(2));
        
        // Calculate difference if loaded weight exists
        if ($('#loaded_net_weight').length) {
            const loadedNet = parseFloat($('#loaded_net_weight').val()) || 0;
            const difference = loadedNet - weighbridgeNet;
            $('#weight_difference').val(difference.toFixed(2));
        }
    });
});
</script> --}}


<script>
    $(document).ready(function() {
        $('#second_weighbridge').on('input', function() {
            const firstWB = parseFloat($('#first_weighbridge').val()) || 0;
            let secondWB = parseFloat($(this).val()) || 0;

            // Auto-correct if second weight is greater than first weight
            if (secondWB > firstWB) {
                secondWB = firstWB;
                $(this).val(firstWB.toFixed(2));
            }

            // Calculate weighbridge net weight
            const weighbridgeNet = firstWB - secondWB;
            $('#weighbridge_net_weight').val(weighbridgeNet.toFixed(2));

            // Calculate difference with loaded weight if exists
            if ($('#loaded_net_weight').length) {
                const loadedNet = parseFloat($('#loaded_net_weight').val()) || 0;
                const difference = loadedNet - weighbridgeNet;
                $('#weight_difference').val(difference.toFixed(2));
            }
        });
    });
</script>
