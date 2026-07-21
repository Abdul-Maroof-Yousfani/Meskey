@if(isset($isAutoDo) && $isAutoDo)
    <div class="col-12 mb-3">
        <div class="alert alert-danger mb-0">
            <strong>Not Allowed:</strong> This ticket is linked to an Dummy Delivery Order. Only actual DO can be passed to Second Weighbridge.
        </div>
    </div>
@endif

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
            <select class="form-control select2" onchange="get_balance(this)" name="delivery_order_id"
                id="delivery_order_id_second_wb" required style="width: 100%;">
                <option value="">Select Delivery Order</option>
                @foreach($deliveryOrders as $deliveryOrder)
                    <option value="{{ $deliveryOrder->id }}">
                        {{ $deliveryOrder->reference_no }}{{ $deliveryOrder->is_auto_created_from_so ? " (Dummy DO)" : "" }} - {{ $deliveryOrder->customer->name ?? 'N/A' }}
                    </option>
                @endforeach
            </select>
            <small class="text-muted">This loading slip does not have a Delivery Order. Please select one.</small>
        </div>
    </div>
@endif

{{-- Loading Slip Details Section --}}
<div id="ticketDataContainer" class="w-100">
    @php
        $item = $LoadingSlip->loadingProgramItem;
        $all_delivery_orders = collect();
        if ($item && $item->deliveryOrders->isNotEmpty()) {
            foreach ($item->deliveryOrders as $do) {
                $all_delivery_orders->push($do);
            }
        }
        if ($LoadingSlip->deliveryOrder) {
            $all_delivery_orders->push($LoadingSlip->deliveryOrder);
        }

        // Filter out any nulls just in case and strictly deduplicate by ID
        $unique_dos = $all_delivery_orders->filter()->unique('id')->values();

        $orders = [];
        foreach ($unique_dos as $do) {
            $factoryNames = [];
            $galaNames = [];
            if ($do->arrival_location_id) {
                $factoryNames = \App\Models\Master\ArrivalLocation::whereIn('id', explode(',', $do->arrival_location_id))->pluck('name')->toArray();
            }
            if ($do->sub_arrival_location_id) {
                $galaNames = \App\Models\Master\ArrivalSubLocation::whereIn('id', explode(',', $do->sub_arrival_location_id))->pluck('name')->toArray();
            }

            $total_qty = $do->delivery_order_data->sum('qty');
            $current_balance = get_second_weighbridge_balance_by_delivery_order($do->id);

            $orders[] = [
                'id' => $do->id,
                'type' => 'DO',
                'number' => $do->reference_no,
                'is_auto' => $do->is_auto_created_from_so,
                'customer' => $do->customer->name ?? '',
                'commodity' => $do->delivery_order_data->first()->item->name ?? '',
                'so_qty' => $do->delivery_order_data->sum(function ($d) {
                    return $d->salesOrderData->qty ?? 0;
                }),
                'do_qty' => $current_balance,
                'balance' => $current_balance,
                'total_qty' => $total_qty,
                'factory_names' => $factoryNames,
                'gala_names' => $galaNames
            ];
        }

        // Handle Sale Orders only if no DOs found
        if (empty($orders)) {
            if ($item && $item->saleOrders->isNotEmpty()) {
                foreach ($item->saleOrders as $so) {
                    $orders[] = [
                        'type' => 'SO',
                        'number' => $so->reference_no,
                        'is_auto' => false,
                        'customer' => $so->customer->name ?? '',
                        'commodity' => $so->sales_order_data->first()->item->name ?? '',
                        'so_qty' => $so->sales_order_data->sum('qty'),
                        'do_qty' => 0,
                        'balance' => 0,
                        'factory_names' => $item->arrivalLocation ? [$item->arrivalLocation->name] : [],
                        'gala_names' => $item->subArrivalLocation ? [$item->subArrivalLocation->name] : []
                    ];
                }
            } elseif ($item && $item->loadingProgram && $item->loadingProgram->saleOrder) {
                $so = $item->loadingProgram->saleOrder;
                $orders[] = [
                    'type' => 'SO',
                    'number' => $so->reference_no,
                    'is_auto' => false,
                    'customer' => $so->customer->name ?? '',
                    'commodity' => $so->sales_order_data->first()->item->name ?? '',
                    'so_qty' => $so->sales_order_data->sum('qty'),
                    'do_qty' => 0,
                    'balance' => 0,
                    'factory_names' => $item->arrivalLocation ? [$item->arrivalLocation->name] : [],
                    'gala_names' => $item->subArrivalLocation ? [$item->subArrivalLocation->name] : []
                ];
            }
        }

        // Apply FIFO Reverse if editing
        if (isset($SecondWeighbridge) && $SecondWeighbridge->net_weight > 0) {
            $weightToRestore = $SecondWeighbridge->net_weight;
            foreach ($orders as &$order) {
                if ($order['type'] === 'DO' && $weightToRestore > 0) {
                    $room = $order['total_qty'] - $order['balance'];
                    if ($room > 0) {
                        $restore = min($room, $weightToRestore);
                        $order['balance'] += $restore;
                        $weightToRestore -= $restore;
                    }
                }
            }
        }
    @endphp

    @if(count($orders) > 0)
        @php
            $modalIdentifier = (isset($SecondWeighbridge) ? $SecondWeighbridge->id : 'new') . '-' . $LoadingSlip->id;
        @endphp
        <ul class="nav nav-tabs nav-justified" id="orderTabs-{{ $modalIdentifier }}" role="tablist">
            @foreach($orders as $index => $order)
                <li class="nav-item">
                    <a class="nav-link {{ $index === 0 ? 'active' : '' }}" id="wb-tab-{{ $modalIdentifier }}-{{ $index }}"
                        data-toggle="tab" href="#wb-pane-{{ $modalIdentifier }}-{{ $index }}" role="tab"
                        aria-controls="wb-pane-{{ $modalIdentifier }}-{{ $index }}"
                        aria-selected="{{ $index === 0 ? 'true' : 'false' }}">
                        {{ $order['type'] }}: {{ $order['number'] }}
                        @if($order['is_auto'])
                            <span class="badge badge-primary" style="font-size: 0.6rem;">Dummy DO</span>
                        @endif
                    </a>
                </li>
            @endforeach
        </ul>
        <div class="tab-content pt-1" id="orderContent-{{ $modalIdentifier }}">
            @foreach($orders as $index => $order)
                <div class="tab-pane fade show {{ $index === 0 ? 'active' : '' }} p-2"
                    id="wb-pane-{{ $modalIdentifier }}-{{ $index }}" role="tabpanel"
                    aria-labelledby="wb-tab-{{ $modalIdentifier }}-{{ $index }}">
                    <div class="row mt-1">
                        <!-- <div class="col-xs-12 col-sm-6 col-md-3">
                                                            <div class="form-group">
                                                                <label>Customer:</label>
                                                                <input type="text" value="{{ $order['customer'] }}" class="form-control" readonly disabled />
                                                            </div>
                                                        </div> -->
                        <div class="col-xs-12 col-sm-6 col-md-3">
                            <div class="form-group">
                                <label>Commodity:</label>
                                <input type="text" value="{{ $order['commodity'] }}" class="form-control" readonly disabled />
                            </div>
                        </div>
                        <div class="col-xs-12 col-sm-6 col-md-3">
                            <div class="form-group">
                                <label>SO Qty:</label>
                                <input type="number" value="{{ round($order['so_qty']) }}" class="form-control" readonly disabled />
                            </div>
                        </div>
                        <div class="col-xs-12 col-sm-6 col-md-3">
                            <div class="form-group">
                                <label>Total DO Qty:</label>
                                <input type="number"
                                    value="{{ isset($order['total_qty']) ? $order['total_qty'] : $order['do_qty'] }}"
                                    class="form-control" readonly disabled />
                            </div>
                        </div>
                        <div class="col-xs-12 col-sm-6 col-md-3">
                            <div class="form-group">
                                <label>Balance Qty:</label>
                                <input type="number" value="{{ $order['balance'] }}" class="form-control" readonly disabled />
                            </div>
                        </div>
                    </div>
                    <div class="row mt-1">
                        <div class="col-xs-12 col-sm-6 col-md-6">
                            <div class="form-group">
                                <label>Factory:</label>
                                <select class="form-control select2 w-100" multiple disabled style="width: 100% !important;">
                                    @foreach($order['factory_names'] as $name)
                                        <option selected>{{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-xs-12 col-sm-6 col-md-6">
                            <div class="form-group">
                                <label>Gala:</label>
                                <select class="form-control select2 w-100" multiple disabled style="width: 100% !important;">
                                    @foreach($order['gala_names'] as $name)
                                        <option selected>{{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="col-12 text-center">No order data found.</div>
    @endif
</div>


<div class="col-xs-12 col-sm-4 col-md-4" style="display: none">
    <div class="form-group">
        <label>Loaded Weight:</label>
        <input type="text" name="loaded_weight" id="loaded_weight" value="{{ $LoadingSlip->kilogram }}" readonly
            class="form-control" autocomplete="off" />
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
            value="{{ $LoadingSlip->loadingProgramItem->firstWeighbridge->first_weight ?? 'N/A' }}" readonly
            class="form-control" autocomplete="off" />
        <input type="hidden" name="first_weight"
            value="{{ $LoadingSlip->loadingProgramItem->firstWeighbridge->first_weight ?? 0 }}" />
    </div>
</div>

<div class="col-xs-12 col-sm-4 col-md-4">
    <div class="form-group">
        <label>Second Weight:</label>
        <input type="number" name="second_weight" id="second_weight" placeholder="Enter Second Weight"
            value="{{ isset($SecondWeighbridge) ? $SecondWeighbridge->second_weight : '' }}" class="form-control"
            autocomplete="off" step="0.01" />
    </div>
</div>

<div class="col-xs-12 col-sm-4 col-md-4">
    <div class="form-group">
        <label>Net Weight:</label>
        <input type="text" name="net_weight" id="net_weight" placeholder="Net Weight"
            value="{{ isset($SecondWeighbridge) ? $SecondWeighbridge->net_weight : '' }}" readonly class="form-control"
            autocomplete="off" />
    </div>
</div>


<div class="col-xs-12 col-sm-12 col-md-12">
    <fieldset>
        <div class="input-group">
            <div class="input-group-prepend">
                <button class="btn btn-primary" type="button">Balance (KG)</button>
            </div>
            @php
                $balance = (isset($SecondWeighbridge) && isset($SecondWeighbridge->balance_kg))
                    ? $SecondWeighbridge->balance_kg
                    : get_second_weighbridge_balance($LoadingSlip);
            @endphp
            <input type="text" id="weight_difference" value="{{ number_format($balance, 2, '.', '') }}"
                name="weight_difference" placeholder="Weight Difference" readonly="" class="form-control"
                autocomplete="off">
        </div>
    </fieldset>
</div>

<div class="col-xs-12 col-sm-12 col-md-12">
    <div class="form-group">
        <label>Remark:</label>
        <textarea name="remark" placeholder="Remarks"
            class="form-control">{{ isset($SecondWeighbridge) ? $SecondWeighbridge->remark : '' }}</textarea>
    </div>
</div>

<script>
    function get_balance(el) {
        $.ajax({
            url: '{{ route("sales.balance-against-second-weighbridge") }}',
            type: 'get',
            data: {
                delivery_order_id: $(el).val()
            },
            dataType: 'json',
            success: function (response) {
                $("#weight_difference").val(response);
            }
        });
    }

    $(document).ready(function () {
        $('.select2').select2();

        @if(isset($isAutoDo) && $isAutoDo)
            $('.submitbutton').prop('disabled', true);
            $('#second_weight').prop('readonly', true);
        @else
            $('.submitbutton').prop('disabled', false);
            $('#second_weight').prop('readonly', false);
        @endif

        // Calculate net weight when second weight changes
        $('#second_weight').on('input', function () {
            const firstWeight = parseFloat($('#first_weight_display').val()) || 0;
            const secondWeight = parseFloat($(this).val()) || 0;
            const balance = parseFloat($('#weight_difference').val()) || 0;

            const netWeight = secondWeight - firstWeight;
            $('#net_weight').val(netWeight.toFixed(2));

            if (netWeight > balance) {
                $(this).addClass('is-invalid');
                if (!$(this).next('.invalid-feedback').length) {
                    $(this).after('<div class="invalid-feedback">Net weight (' + netWeight.toFixed(2) + ') exceeds balance (' + balance.toFixed(2) + ')</div>');
                } else {
                    $(this).next('.invalid-feedback').text('Net weight (' + netWeight.toFixed(2) + ') exceeds balance (' + balance.toFixed(2) + ')');
                }
                $('.submitbutton').prop('disabled', true);
            } else {
                $(this).removeClass('is-invalid');
                $(this).next('.invalid-feedback').remove();
                @if(!isset($isAutoDo) || !$isAutoDo)
                    $('.submitbutton').prop('disabled', false);
                @endif
            }
        });
    });
</script>