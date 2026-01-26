<div class="col-12">
    <h6 class="header-heading-sepration">
        Loading Slip Details
    </h6>
</div>

{{-- Delivery Order Selection (only shown if loading slip doesn't have one) --}}
@if(isset($needsDeliveryOrder) && $needsDeliveryOrder)
<div class="col-xs-12 col-sm-12 col-md-12">
    <div class="form-group">
        <label>Delivery Order: <span class="text-danger">*</span></label>
        <select class="form-control select2" onchange="get_balance(this)" name="delivery_order_id" id="delivery_order_id_second_wb" required style="width: 100%;">
            <option value="">Select Delivery Order</option>
            @foreach($deliveryOrders as $deliveryOrder)
                <option value="{{ $deliveryOrder->id }}">
                    {{ $deliveryOrder->reference_no }} - {{ $deliveryOrder->customer->name ?? 'N/A' }}
                </option>
            @endforeach
        </select>
        <small class="text-muted">This loading slip does not have a Delivery Order. Please select one.</small>
    </div>
</div>
@endif

{{-- Loading Slip Details Section --}}
@php
    $deliveryOrder = \App\Models\Sales\DeliveryOrder::find($LoadingSlip->loadingProgramItem->delivery_order_id);
    $saleOrder = $LoadingSlip->loadingProgramItem->loadingProgram->saleOrder ?? null;

    // Get customer name
    $customerName = $deliveryOrder && $deliveryOrder->customer
        ? $deliveryOrder->customer->name
        : ($saleOrder && $saleOrder->customer
            ? $saleOrder->customer->name
            : ($LoadingSlip->customer ?? 'N/A'));

            
            // Get commodity name
            $commodityName = $deliveryOrder && $deliveryOrder->delivery_order_data && $deliveryOrder->delivery_order_data->first() && $deliveryOrder->delivery_order_data->first()->item
            ? $deliveryOrder->delivery_order_data->first()->item->name
            : ($saleOrder && $saleOrder->sales_order_data && $saleOrder->sales_order_data->first() && $saleOrder->sales_order_data->first()->item
            ? $saleOrder->sales_order_data->first()->item->name
            : ($LoadingSlip->commodity ?? 'N/A'));
            
            // Get SO qty
            $soQty = $deliveryOrder && $deliveryOrder->delivery_order_data && $deliveryOrder->delivery_order_data->first() && $deliveryOrder->delivery_order_data->first()->salesOrderData
            ? $deliveryOrder->delivery_order_data->first()->salesOrderData->qty
            : ($saleOrder && $saleOrder->sales_order_data && $saleOrder->sales_order_data->first()
            ? $saleOrder->sales_order_data->first()->qty
            : ($LoadingSlip->so_qty ?? 'N/A'));
            
            // Get DO qty
            $doQty = $deliveryOrder && $deliveryOrder->delivery_order_data && $deliveryOrder->delivery_order_data->first()
        ? $deliveryOrder->delivery_order_data->first()->qty
        : ($LoadingSlip->do_qty ?? 'N/A');

@endphp

<div class="col-xs-12 col-sm-6 col-md-6">
    <div class="form-group">
        <label>Customer:</label>
        <input type="text" value="{{ $customerName }}"
            disabled class="form-control" autocomplete="off" readonly />
    </div>
</div>

<div class="col-xs-12 col-sm-6 col-md-6">
    <div class="form-group">
        <label>Commodity:</label>
        <input type="text" value="{{ $commodityName }}"
            disabled class="form-control" autocomplete="off" readonly />
    </div>
</div>

<div class="col-xs-12 col-sm-6 col-md-6">
    <div class="form-group">
        <label>SO Qty:</label>
        <input type="text" value="{{ $soQty }}"
            disabled class="form-control" autocomplete="off" readonly />
    </div>
</div>

<div class="col-xs-12 col-sm-6 col-md-6">
    <div class="form-group">
        <label>DO Qty:</label>
        <input type="text" value="{{ $doQty }}"
            disabled class="form-control" autocomplete="off" readonly />
    </div>
</div>


<div class="col-xs-12 col-sm-6 col-md-6">
    <div class="form-group">
        <label>Factory:</label>
        <select class="form-control select2 w-100" id="factory_display" multiple disabled style="width: 100% !important;">
            
            <option value="" selected>{{ $LoadingSlip->factory }}</option>
        
        </select>
    </div>
</div>



<div class="col-xs-12 col-sm-6 col-md-6">
    <div class="form-group">
        <label>Gala:</label>
        <select class="form-control select2 w-100" id="gala_display" multiple disabled style="width: 100% !important;">
            <option value="" selected>{{ $LoadingSlip->gala }}</option>
        </select>
    </div>
</div>



<div class="col-xs-12 col-sm-4 col-md-4" style="display: none">
    <div class="form-group">
        <label>Loaded Weight:</label>
        <input type="text" name="loaded_weight" id="loaded_weight"
            value="{{ $LoadingSlip->kilogram }}"
            readonly class="form-control" autocomplete="off" />
        <input type="hidden" name="first_weight" value="{{ $LoadingSlip->kilogram }}" />
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
    <fieldset>
        <div class="input-group">
            <div class="input-group-prepend">
                <button class="btn btn-primary" type="button">Balance (KG)</button>
            </div>
            @if($LoadingSlip->deliveryOrder)
                <input type="text" id="weight_difference" value="{{ get_second_weighbridge_balance($LoadingSlip) }}" name="weight_difference" placeholder="Weight Difference" readonly="" class="form-control" autocomplete="off">
            @else
                <input type="text" id="weight_difference" name="weight_difference" placeholder="Weight Difference" readonly="" class="form-control" autocomplete="off" value="Select Delivery Order to see balance">
            @endif
        </div>
    </fieldset>
</div>

<div class="col-xs-12 col-sm-12 col-md-12">
    <div class="form-group">
        <label>Remark:</label>
        <textarea name="remark" placeholder="Remarks" class="form-control">{{ isset($SecondWeighbridge) ? $SecondWeighbridge->remark : '' }}</textarea>
    </div>
</div>

<script>
     function get_balance(el) {
             $.ajax({
                url: '{{ route("sales.balance-against-second-weighbridge") }}',
                type: 'get', // or 'GET'
                data: {
                    delivery_order_id: $(el).val()
                },
                dataType: 'json', // expected response type
                success: function (response) {
                    $("#weight_difference").val(response);
                }
            });
        }
    $(document).ready(function() {
        $('.select2').select2();

       

        // Calculate net weight when second weight changes
        $('#second_weight').on('input', function() {
            const firstWeight = parseFloat($('#first_weight_display').val()) || 0;
            const secondWeight = parseFloat($(this).val()) || 0;

            const loadedWeight = parseFloat($('#loaded_weight').val()) || 0;
            const netWeight = secondWeight - firstWeight;
            $('#net_weight').val(netWeight.toFixed(2));
            // $("#weight_difference").val(netWeight - loadedWeight);
        });
    });
</script>
