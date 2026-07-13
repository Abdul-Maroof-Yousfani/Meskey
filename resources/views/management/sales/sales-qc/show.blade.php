<div class="modal-body">
    <div class="row form-mar">
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Ticket:</label>
                <input type="text"
                    value="{{ $SalesQc->loadingProgramItem->transaction_number ?? 'N/A' }} -- {{ $SalesQc->loadingProgramItem->truck_number ?? 'N/A' }}"
                    disabled class="form-control" autocomplete="off" readonly />
            </div>
        </div>
    </div>

    @php
        $item = $SalesQc->loadingProgramItem;
        $orders = [];

        // Handle Delivery Orders from many-to-many relationship
        if ($item->deliveryOrders->isNotEmpty()) {
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
                    'is_auto' => $do->is_auto_created_from_so,
                    'customer' => $do->customer->name ?? '',
                    'commodity' => $do->delivery_order_data->first()->item->name ?? '',
                    'so_qty' => $do->delivery_order_data->sum(function ($d) {
                        return $d->salesOrderData->qty ?? 0;
                    }),
                    'do_qty' => $do->delivery_order_data->sum('qty'),
                    'factory_names' => $factoryNames,
                    'gala_names' => $galaNames
                ];
            }
        }
        // Fallback to single delivery order if exists
        elseif ($item->loadingProgram && $item->loadingProgram->deliveryOrder) {
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
                'is_auto' => $do->is_auto_created_from_so,
                'customer' => $do->customer->name ?? '',
                'commodity' => $do->delivery_order_data->first()->item->name ?? '',
                'so_qty' => $do->delivery_order_data->sum(function ($d) {
                    return $d->salesOrderData->qty ?? 0;
                }),
                'do_qty' => $do->delivery_order_data->sum('qty'),
                'factory_names' => $factoryNames,
                'gala_names' => $galaNames
            ];
        }

        // Handle Sale Orders if no DOs or as additional info
        if ($item->saleOrders->isNotEmpty()) {
            foreach ($item->saleOrders as $so) {
                if (empty($orders)) {
                    $orders[] = [
                        'type' => 'SO',
                        'number' => $so->reference_no,
                        'is_auto' => false,
                        'customer' => $so->customer->name ?? '',
                        'commodity' => $so->sales_order_data->first()->item->name ?? '',
                        'so_qty' => $so->sales_order_data->sum('qty'),
                        'do_qty' => 0,
                        'factory_names' => $item->arrivalLocation ? [$item->arrivalLocation->name] : [],
                        'gala_names' => $item->subArrivalLocation ? [$item->subArrivalLocation->name] : []
                    ];
                }
            }
        } elseif (empty($orders) && $item->loadingProgram && $item->loadingProgram->saleOrder) {
            $so = $item->loadingProgram->saleOrder;
            $orders[] = [
                'type' => 'SO',
                'number' => $so->reference_no,
                'is_auto' => false,
                'customer' => $so->customer->name ?? '',
                'commodity' => $so->sales_order_data->first()->item->name ?? '',
                'so_qty' => $so->sales_order_data->sum('qty'),
                'do_qty' => 0,
                'factory_names' => $item->arrivalLocation ? [$item->arrivalLocation->name] : [],
                'gala_names' => $item->subArrivalLocation ? [$item->subArrivalLocation->name] : []
            ];
        }
    @endphp

    @if(count($orders) > 0)
        <ul class="nav nav-tabs nav-justified" id="orderTabs" role="tablist">
            @foreach($orders as $index => $order)
                <li class="nav-item">
                    <a class="nav-link {{ $index === 0 ? 'active' : '' }}" id="order-tab-{{ $index }}" data-toggle="tab"
                        href="#order-content-{{ $index }}" role="tab" aria-controls="order-content-{{ $index }}"
                        aria-selected="{{ $index === 0 ? 'true' : 'false' }}">
                        {{ $order['type'] }}: {{ $order['number'] }}
                        @if(!empty($order['is_auto']))
                            <span class="badge badge-warning" style="font-size: 0.6rem;">Dummy DO</span>
                        @endif
                    </a>
                </li>
            @endforeach
        </ul>
        <div class="tab-content pt-1" id="orderTabsContent">
            @foreach($orders as $index => $order)
                <div class="tab-pane fade show {{ $index === 0 ? 'active' : '' }}" id="order-content-{{ $index }}"
                    role="tabpanel" aria-labelledby="order-tab-{{ $index }}">
                    <div class="row">
                        <!-- <div class="col-xs-12 col-sm-6 col-md-3">
                                            <div class="form-group">
                                                <label>Customer:</label>
                                                <input type="text" value="{{ $order['customer'] }}" disabled class="form-control" readonly />
                                            </div>
                                        </div> -->
                        <div class="col-xs-12 col-sm-6 col-md-4">
                            <div class="form-group">
                                <label>Commodity:</label>
                                <input type="text" value="{{ $order['commodity'] }}" disabled class="form-control" readonly />
                            </div>
                        </div>
                        <div class="col-xs-12 col-sm-6 col-md-4">
                            <div class="form-group">
                                <label>SO Qty:</label>
                                <input type="text" value="{{ round($order['so_qty']) }}" disabled class="form-control" readonly />
                            </div>
                        </div>
                        <div class="col-xs-12 col-sm-6 col-md-4">
                            <div class="form-group">
                                <label>DO Qty:</label>
                                <input type="text" value="{{ round($order['do_qty']) }}" disabled class="form-control" readonly />
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-xs-12 col-sm-6 col-md-6">
                            <div class="form-group">
                                <label>Factory:</label>
                                <input type="text" value="{{ implode(', ', $order['factory_names']) }}" disabled
                                    class="form-control" readonly />
                            </div>
                        </div>
                        <div class="col-xs-12 col-sm-6 col-md-6">
                            <div class="form-group">
                                <label>Gala:</label>
                                <input type="text" value="{{ implode(', ', $order['gala_names']) }}" disabled
                                    class="form-control" readonly />
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="row">
            <div class="col-12 text-center">No order data found.</div>
        </div>
    @endif

    <div class="row">
        <div class="col-xs-12 col-sm-6 col-md-6">
            <div class="form-group">
                <label>QC Remarks:</label>
                <textarea class="form-control" readonly>{{ $SalesQc->qc_remarks }}</textarea>
            </div>
        </div>
        <div class="col-xs-12 col-sm-6 col-md-6">
            <div class="form-group">
                <label>Status:</label>
                <input type="text" value="{{ ucfirst($SalesQc->status) }}" disabled class="form-control"
                    autocomplete="off" readonly />
            </div>
        </div>
    </div>

    @if($SalesQc->attachments->count() > 0)
        <div class="row">
            <div class="col-xs-12 col-sm-12 col-md-12">
                <div class="form-group">
                    <label>Attachments:</label>
                    <div class="row">
                        @foreach($SalesQc->attachments as $attachment)
                            <div class="col-md-4 mb-2">
                                <div class="card">
                                    <div class="card-body text-center">
                                        @if(Str::contains($attachment->file_type, ['image']))
                                            <img src="{{ asset($attachment->file_path) }}" alt="{{ $attachment->file_name }}"
                                                class="img-fluid rounded" style="max-height: 100px;">
                                        @else
                                            <i class="ft-file-text font-large-2"></i>
                                        @endif
                                        <p class="mt-1 mb-1">{{ Str::limit($attachment->file_name, 20) }}</p>
                                        <a href="{{ asset($attachment->file_path) }}" target="_blank"
                                            class="btn btn-sm btn-primary">View</a>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

@if($SalesQc->status === 'reject')
    <x-approval-without-revert :model="$SalesQc" />
@endif