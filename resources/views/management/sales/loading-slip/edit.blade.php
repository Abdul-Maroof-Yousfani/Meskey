<form action="{{ route('sales.loading-slip.update', $loadingSlip->id) }}" method="POST" id="ajaxSubmit" autocomplete="off" enctype="multipart/form-data">
    @csrf
    @method('PUT')
    <input type="hidden" id="listRefresh" value="{{ route('sales.get.loading-slip') }}" />

    @if(isset($rejectedDispatchQc) && $rejectedDispatchQc)
    <div class="row">
        <div class="col-12">
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <strong><i class="ft-alert-triangle"></i> Dispatch QC Rejected!</strong>
                <p class="mb-0 mt-1">This loading slip's Dispatch QC has been rejected. Please review and update the loading slip details.</p>
                @if($rejectedDispatchQc->qc_remarks)
                <hr>
                <strong>QC Remarks:</strong>
                <p class="mb-0">{{ $rejectedDispatchQc->qc_remarks }}</p>
                @endif
            </div>
        </div>
    </div>
    @endif

    @if(isset($canEdit) && !$canEdit)
    <div class="row">
        <div class="col-12">
            <div class="alert alert-info" role="alert">
                <strong><i class="ft-info"></i> Read Only</strong>
                <p class="mb-0">This loading slip cannot be edited because its Dispatch QC has been accepted.</p>
            </div>
        </div>
    </div>
    @endif

    <div class="row form-mar">
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Tickets:</label>
                <input type="text" class="form-control" value="{{ $loadingSlip->loadingProgramItem->transaction_number ?? '' }} -- {{ $loadingSlip->loadingProgramItem->truck_number ?? '' }}" readonly>
                <input type="hidden" name="loading_program_item_id" value="{{ $loadingSlip->loading_program_item_id }}">
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
                        'bag_size' => $do->delivery_order_data->first()->bag_size ?? 0,
                        'brand' => $item->brand->name ?? 'N/A'
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
                    'bag_size' => $do->delivery_order_data->first()->bag_size ?? 0,
                    'brand' => $item->brand->name ?? 'N/A'
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
                            'bag_size' => $so->sales_order_data->first()->bag_size ?? 0,
                            'brand' => $item->brand->name ?? 'N/A'
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
                    'bag_size' => $so->sales_order_data->first()->bag_size ?? 0,
                    'brand' => $item->brand->name ?? 'N/A'
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
                                    <input type="text" value="{{ $order['customer'] }}" class="form-control" readonly />
                                </div>
                            </div>
                            <div class="col-xs-12 col-sm-6 col-md-3">
                                <div class="form-group">
                                    <label>Commodity:</label>
                                    <input type="text" value="{{ $order['commodity'] }}" class="form-control" readonly />
                                </div>
                            </div>
                            <div class="col-xs-12 col-sm-6 col-md-3">
                                <div class="form-group">
                                    <label>SO Qty:</label>
                                    <input type="number" value="{{ $order['so_qty'] }}" class="form-control" readonly step="0.01" />
                                </div>
                            </div>
                            <div class="col-xs-12 col-sm-6 col-md-3">
                                <div class="form-group">
                                    <label>DO Qty:</label>
                                    <input type="number" value="{{ $order['do_qty'] }}" class="form-control" readonly step="0.01" />
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-xs-12 col-sm-6 col-md-3">
                                <div class="form-group">
                                    <label>Factory:</label>
                                    <select class="form-control select2 w-100" multiple disabled style="width: 100% !important;">
                                        @foreach($order['factory_names'] as $name)
                                            <option selected>{{ $name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-xs-12 col-sm-6 col-md-3">
                                <div class="form-group">
                                    <label>Gala:</label>
                                    <select class="form-control select2 w-100" multiple disabled style="width: 100% !important;">
                                        @foreach($order['gala_names'] as $name)
                                            <option selected>{{ $name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-xs-12 col-sm-6 col-md-3">
                                <div class="form-group">
                                    <label>Bag Size:</label>
                                    <input type="number" value="{{ $order['bag_size'] }}" class="form-control" readonly step="0.01" />
                                </div>
                            </div>
                            <div class="col-xs-12 col-sm-6 col-md-3">
                                <div class="form-group">
                                    <label>Brand:</label>
                                    <input type="text" value="{{ $order['brand'] }}" class="form-control" readonly />
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            
            {{-- Hidden inputs for backward compatibility --}}
            <input type="hidden" name="customer" value="{{ $loadingSlip->customer }}" />
            <input type="hidden" name="commodity" value="{{ $loadingSlip->commodity }}" />
            <input type="hidden" name="brand" value="{{ $loadingSlip->brand ?? $loadingSlip->loadingProgramItem->brand->name ?? '' }}" />
            <input type="hidden" name="so_qty" value="{{ $loadingSlip->so_qty }}" />
            <input type="hidden" name="do_qty" value="{{ $loadingSlip->do_qty }}" />
            <input type="hidden" name="factory" value="{{ $loadingSlip->factory }}" />
            <input type="hidden" name="gala" value="{{ $loadingSlip->gala }}" />
            <input type="hidden" name="bag_size" value="{{ $loadingSlip->bag_size }}" />
        @else
            <div class="col-12 text-center">No order data found.</div>
        @endif
    </div>
    <div class="row">
        <div class="col-xs-12 col-sm-6 col-md-6">
            <div class="form-group">
                <label>No. of Bags: <span class="text-danger">*</span></label>
                <input type="number" name="no_of_bags" id="no_of_bags" value="{{ $loadingSlip->no_of_bags }}" class="form-control" min="1" required {{ (isset($canEdit) && !$canEdit) ? 'readonly' : '' }}>
            </div>
        </div>
        @php
            $sauda_type = $loadingSlip->deliveryOrder->sauda_type ?? $loadingSlip->loadingProgramItem->loadingProgram->saleOrder->sauda_type ?? '';
            $is_pohanch = (strtolower($sauda_type) == 'pohanch');
            $is_xmill = (strtolower($sauda_type) == 'x-mill' || strtolower($sauda_type) == 'xmill');
            $labour_editable = $is_xmill && !(isset($canEdit) && !$canEdit);
        @endphp
        <div class="col-xs-12 col-sm-6 col-md-6">
            <div class="form-group">
                <label>Labour</label>
                <select name='labour' id='labour' class='form-control select2'>
                    <option value='paid' @selected($loadingSlip->labour == 'paid')>Paid</option>
                    <option value='not_paid' @selected($loadingSlip->labour == 'not_paid')>Not Paid</option>    
                </select>
            </div>
        </div>
        <div style="display: none;">
            <div class="form-group">
                <label>Kilogram:</label>
                <input type="number" name="kilogram" id="kilogram" value="{{ $loadingSlip->kilogram ?? '' }}" class="form-control" readonly step="0.01" />
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Remarks:</label>
                <textarea name="remarks" placeholder="Enter remarks" class="form-control" rows="3" {{ (isset($canEdit) && !$canEdit) ? 'readonly' : '' }}>{{ $loadingSlip->remarks }}</textarea>
            </div>
        </div>
    </div>

    <div class="row bottom-button-bar">
        <div class="col-12">
            <a type="button" class="btn btn-danger modal-sidebar-close position-relative top-1 closebutton">Close</a>
            @if(isset($canEdit) && $canEdit)
            <button type="submit" class="btn btn-primary submitbutton">Update</button>
            @else
            <button type="button" class="btn btn-secondary" disabled>Editing Disabled</button>
            @endif
        </div>
    </div>
</form>

@if(isset($loadingSlip) && $loadingSlip->logs->count() > 0)
<div class="card mt-3">
    <div class="card-header">
        <h5 class="card-title mb-0"><i class="ft-clock"></i> Edit History</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-sm">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>No. of Bags</th>
                        <th>Kilogram</th>
                        <th>QC Remarks</th>
                        <th>Edited By</th>
                        <th>Edited At</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($loadingSlip->logs->sortByDesc('created_at') as $index => $log)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $log->no_of_bags }}</td>
                        <td>{{ number_format($log->kilogram, 2) }}</td>
                        <td>{{ $log->qc_remarks ?? '-' }}</td>
                        <td>{{ $log->editedBy->name ?? 'N/A' }}</td>
                        <td>{{ $log->created_at->format('d M Y, h:i A') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif

<script>
    $(document).ready(function() {
        $(".select2").select2({
            dropdownParent: $('#modal-sidebar')
        });
        // Calculate kilogram when no_of_bags changes
        $('#no_of_bags').on('input', function() {
            calculateKilogram();
        });

        function calculateKilogram() {
            var noOfBags = parseFloat($('#no_of_bags').val()) || 0;
            var bagSize = parseFloat($('input[name="bag_size"]').val()) || 0;
            var kilogram = noOfBags * bagSize;
            $('#kilogram').val(kilogram.toFixed(2));
        }
    });
</script>
