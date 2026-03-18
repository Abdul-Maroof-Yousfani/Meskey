<div class="modal-body">
    <div class="row form-mar">
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Ticket:</label>
                <input type="text" value="{{ $loadingSlip->loadingProgramItem->transaction_number ?? 'N/A' }} -- {{ $loadingSlip->loadingProgramItem->truck_number ?? 'N/A' }}"
                    disabled class="form-control" autocomplete="off" readonly />
            </div>
        </div>
    </div>

    <div id="ticketDataContainer" class="w-100">
        @php
            $item = $loadingSlip->loadingProgramItem;
            $orders = [];

            // Handle Delivery Orders from many-to-many relationship
            if ($item && $item->deliveryOrders->isNotEmpty()) {
                foreach ($item->deliveryOrders as $do) {
                    $factoryNames = [];
                    $galaNames = [];
                    if ($do->arrival_location_id) {
                        $factoryNames = \App\Models\Master\ArrivalLocation::whereIn('id', explode(',', $do->arrival_location_id))->pluck('name')->toArray();
                    }
                    if ($do->sub_arrival_location_id) {
                        $galaNames = \App\Models\Master\ArrivalSubLocation::whereIn('id', explode(',', $do->sub_arrival_location_id))->pluck('name')->toArray();
                    }

                    $orders[] = [
                        'type' => 'DO',
                        'number' => $do->reference_no,
                        'customer' => $do->customer->name ?? '',
                        'commodity' => $do->delivery_order_data->first()->item->name ?? '',
                        'so_qty' => $do->delivery_order_data->sum(function($d) { return $d->salesOrderData->qty ?? 0; }),
                        'do_qty' => $do->delivery_order_data->sum('qty'),
                        'factory_names' => $factoryNames,
                        'gala_names' => $galaNames,
                        'bag_size' => $do->delivery_order_data->first()->bag_size ?? 0
                    ];
                }
            } 
            // Fallback to single delivery order if exists
            elseif ($item && $item->loadingProgram && $item->loadingProgram->deliveryOrder) {
                $do = $item->loadingProgram->deliveryOrder;
                $factoryNames = [];
                $galaNames = [];
                if ($do->arrival_location_id) {
                    $factoryNames = \App\Models\Master\ArrivalLocation::whereIn('id', explode(',', $do->arrival_location_id))->pluck('name')->toArray();
                }
                if ($do->sub_arrival_location_id) {
                    $galaNames = \App\Models\Master\ArrivalSubLocation::whereIn('id', explode(',', $do->sub_arrival_location_id))->pluck('name')->toArray();
                }

                $orders[] = [
                    'type' => 'DO',
                    'number' => $do->reference_no,
                    'customer' => $do->customer->name ?? '',
                    'commodity' => $do->delivery_order_data->first()->item->name ?? '',
                    'so_qty' => $do->delivery_order_data->sum(function($d) { return $d->salesOrderData->qty ?? 0; }),
                    'do_qty' => $do->delivery_order_data->sum('qty'),
                    'factory_names' => $factoryNames,
                    'gala_names' => $galaNames,
                    'bag_size' => $do->delivery_order_data->first()->bag_size ?? 0
                ];
            }

            // Handle Sale Orders if no DOs or as additional info
            if ($item && $item->saleOrders->isNotEmpty()) {
                foreach ($item->saleOrders as $so) {
                    if (empty($orders)) {
                        $orders[] = [
                            'type' => 'SO',
                            'number' => $so->reference_no,
                            'customer' => $so->customer->name ?? '',
                            'commodity' => $so->sales_order_data->first()->item->name ?? '',
                            'so_qty' => $so->sales_order_data->sum('qty'),
                            'do_qty' => 0,
                            'factory_names' => $item->arrivalLocation ? [$item->arrivalLocation->name] : [],
                            'gala_names' => $item->subArrivalLocation ? [$item->subArrivalLocation->name] : [],
                            'bag_size' => $so->sales_order_data->first()->bag_size ?? 0
                        ];
                    }
                }
            } elseif (empty($orders) && $item && $item->loadingProgram && $item->loadingProgram->saleOrder) {
                $so = $item->loadingProgram->saleOrder;
                $orders[] = [
                    'type' => 'SO',
                    'number' => $so->reference_no,
                    'customer' => $so->customer->name ?? '',
                    'commodity' => $so->sales_order_data->first()->item->name ?? '',
                    'so_qty' => $so->sales_order_data->sum('qty'),
                    'do_qty' => 0,
                    'factory_names' => $item->arrivalLocation ? [$item->arrivalLocation->name] : [],
                    'gala_names' => $item->subArrivalLocation ? [$item->subArrivalLocation->name] : [],
                    'bag_size' => $so->sales_order_data->first()->bag_size ?? 0
                ];
            }
        @endphp

        @if(count($orders) > 0)
            <ul class="nav nav-tabs nav-justified" id="orderTabs" role="tablist">
                @foreach($orders as $index => $order)
                    <li class="nav-item">
                        <a class="nav-link {{ $index === 0 ? 'active' : '' }}" id="order-tab-{{ $index }}" data-toggle="tab" href="#order-content-{{ $index }}" role="tab" aria-controls="order-content-{{ $index }}" aria-selected="{{ $index === 0 ? 'true' : 'false' }}">
                            {{ $order['type'] }}: {{ $order['number'] }}
                        </a>
                    </li>
                @endforeach
            </ul>
            <div class="tab-content pt-1" id="orderTabsContent">
                @foreach($orders as $index => $order)
                    <div class="tab-pane fade show {{ $index === 0 ? 'active' : '' }}" id="order-content-{{ $index }}" role="tabpanel" aria-labelledby="order-tab-{{ $index }}">
                        <div class="row">
                            <div class="col-xs-12 col-sm-6 col-md-3">
                                <div class="form-group">
                                    <label>Customer:</label>
                                    <input type="text" value="{{ $order['customer'] }}" class="form-control" readonly disabled />
                                </div>
                            </div>
                            <div class="col-xs-12 col-sm-6 col-md-3">
                                <div class="form-group">
                                    <label>Commodity:</label>
                                    <input type="text" value="{{ $order['commodity'] }}" class="form-control" readonly disabled />
                                </div>
                            </div>
                            <div class="col-xs-12 col-sm-6 col-md-3">
                                <div class="form-group">
                                    <label>SO Qty:</label>
                                    <input type="number" value="{{ $order['so_qty'] }}" class="form-control" readonly disabled />
                                </div>
                            </div>
                            <div class="col-xs-12 col-sm-6 col-md-3">
                                <div class="form-group">
                                    <label>DO Qty:</label>
                                    <input type="number" value="{{ $order['do_qty'] }}" class="form-control" readonly disabled />
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-xs-12 col-sm-6 col-md-4">
                                <div class="form-group">
                                    <label>Factory:</label>
                                    <select class="form-control select2 w-100" multiple disabled style="width: 100% !important;">
                                        @foreach($order['factory_names'] as $name)
                                            <option selected>{{ $name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-xs-12 col-sm-6 col-md-4">
                                <div class="form-group">
                                    <label>Gala:</label>
                                    <select class="form-control select2 w-100" multiple disabled style="width: 100% !important;">
                                        @foreach($order['gala_names'] as $name)
                                            <option selected>{{ $name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-xs-12 col-sm-6 col-md-4">
                                <div class="form-group">
                                    <label>Bag Size:</label>
                                    <input type="number" value="{{ $order['bag_size'] }}" class="form-control" readonly disabled />
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

    <div class="row">
        <div class="col-xs-12 col-sm-6 col-md-4">
            <div class="form-group">
                <label>No. of Bags:</label>
                <input type="text" value="{{ $loadingSlip->no_of_bags ?? 'N/A' }}"
                    disabled class="form-control" autocomplete="off" readonly />
            </div>
        </div>
        <div class="col-xs-12 col-sm-6 col-md-4">
            <div class="form-group">
                <label>Kilogram:</label>
                <input type="text" value="{{ $loadingSlip->kilogram ?? 'N/A' }}"
                    disabled class="form-control" autocomplete="off" readonly />
            </div>
        </div>
        <div class="col-xs-4 col-sm-4 col-md-4">
            <div class="form-group">
                <label>Labour</label>
                <select name='labour' class='form-control select2'>
                    <option value='paid' @selected($loadingSlip->labour == 'paid')>Paid</option>
                    <option value='not_paid' @selected($loadingSlip->labour == 'not_paid')>Not Paid</option>    
                </select>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Remarks:</label>
                <textarea class="form-control" readonly>{{ $loadingSlip->remarks }}</textarea>
            </div>
        </div>
        {{-- <div class="col-xs-12 col-sm-6 col-md-6">
            <div class="form-group">
                <label>Created Date:</label>
                <input type="text" value="{{ $loadingSlip->created_at->format('d-m-Y H:i:s') }}"
                    disabled class="form-control" autocomplete="off" readonly />
            </div>
        </div> --}}
    </div>
</div>
<script>
    $(".select2").select2();
    </script>