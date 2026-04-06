<style>
    .modal-footer {
        background: white;
    }
</style>
<div class="modal-body">
    <div class="row form-mar">
        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group">
                <label>Sale Order:</label>
                <select class="form-control select2" multiple disabled style="width: 100% !important;">
                    @foreach ($SalesOrders as $so)
                        <option value="{{ $so->id }}" selected>{{ $so->reference_no }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group">
                <label>Delivery Order:</label>
                <select class="form-control select2" multiple disabled style="width: 100% !important;">
                    @foreach ($DeliveryOrders as $do)
                        <option value="{{ $do->id }}" selected>{{ $do->reference_no }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    {{-- Sale Order Details Tabs --}}
    <div class="row">
        <div class="col-12">
            <h6 class="header-heading-sepration">Sale Order Details</h6>
            <div class="card card-outline-info">
                <div class="card-header p-0 pt-1">
                    <ul class="nav nav-tabs horizontal-scrollable-tabs" id="so-details-tabs" role="tablist" style="overflow-x: auto; white-space: nowrap; display: flex; flex-wrap: nowrap;">
                        @foreach($SalesOrders as $index => $so)
                            <li class="nav-item" style="flex: 0 0 auto;">
                                <a class="nav-link {{ $index == 0 ? 'active' : '' }}" id="so-tab-{{ $so->id }}" data-toggle="pill" href="#so-content-{{ $so->id }}" role="tab">
                                    {{ $so->reference_no }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
                <div class="card-body">
                    <div class="tab-content">
                        @foreach($SalesOrders as $index => $so)
                            <div class="tab-pane fade {{ $index == 0 ? 'show active' : '' }}" id="so-content-{{ $so->id }}" role="tabpanel">
                                <div class="row">
                                    <div class="col-xs-12 col-sm-6 col-md-3">
                                        <div class="form-group">
                                            <label>Buyer:</label>
                                            <input type="text" value="{{ $so->customer->name ?? 'N/A' }}" disabled class="form-control" />
                                        </div>
                                    </div>
                                    <div class="col-xs-12 col-sm-6 col-md-3">
                                        <div class="form-group">
                                            <label>Commodity:</label>
                                            <input type="text" value="{{ $so->sales_order_data->first()->item->name ?? 'N/A' }}" disabled class="form-control" />
                                        </div>
                                    </div>
                                    <div class="col-xs-12 col-sm-6 col-md-3">
                                        <div class="form-group">
                                            <label>SO Date:</label>
                                            <input type="text" value="{{ $so->order_date ? $so->order_date : 'N/A' }}" disabled class="form-control" />
                                        </div>
                                    </div>
                                    <div class="col-xs-12 col-sm-6 col-md-3">
                                        <div class="form-group">
                                            <label>SO Qty:</label>
                                            <input type="text" value="{{ $so->sales_order_data->first()->qty ?? 'N/A' }}" disabled class="form-control" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Delivery Order Details Tabs --}}
    @if(!$DeliveryOrders->isEmpty())
    <div class="row mt-3">
        <div class="col-12">
            <h6 class="header-heading-sepration">Delivery Order Details</h6>
            <div class="card card-outline-info">
                <div class="card-header p-0 pt-1">
                    <ul class="nav nav-tabs horizontal-scrollable-tabs" id="do-details-tabs" role="tablist" style="overflow-x: auto; white-space: nowrap; display: flex; flex-wrap: nowrap;">
                        @foreach($DeliveryOrders as $index => $do)
                            <li class="nav-item" style="flex: 0 0 auto;">
                                <a class="nav-link {{ $index == 0 ? 'active' : '' }}" id="do-tab-{{ $do->id }}" data-toggle="pill" href="#do-content-{{ $do->id }}" role="tab">
                                    {{ $do->reference_no }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
                <div class="card-body">
                    <div class="tab-content">
                        @foreach($DeliveryOrders as $index => $do)
                            <div class="tab-pane fade {{ $index == 0 ? 'show active' : '' }}" id="do-content-{{ $do->id }}" role="tabpanel">
                                <div class="row">
                                    <div class="col-xs-12 col-sm-6 col-md-4">
                                        <div class="form-group">
                                            <label>Delivery Order No:</label>
                                            <input type="text" value="{{ $do->reference_no }}" disabled class="form-control" />
                                        </div>
                                    </div>
                                    <div class="col-xs-12 col-sm-6 col-md-4">
                                        <div class="form-group">
                                            <label>Total Qty:</label>
                                            <input type="text" value="{{ $do->delivery_order_data->sum('qty') }}" disabled class="form-control" />
                                        </div>
                                    </div>
                                    <div class="col-xs-12 col-sm-6 col-md-4">
                                        <div class="form-group">
                                            <label>Balance Qty:</label>
                                            @php
                                                $used_qty = \App\Models\Sales\SecondWeighbridge::where('delivery_order_id', $do->id)->sum('net_weight');
                                            @endphp
                                            <input type="text" value="{{ $do->delivery_order_data->sum('qty') - $used_qty }}" disabled class="form-control" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <div class="row mt-3" id="locationContainer">
        <style>
            .select2-container { width: 100% !important; }
            .select2-container .select2-selection--multiple { width: 100% !important; }
        </style>
        <div class="col-xs-12 col-sm-4 col-md-4">
            <div class="form-group">
                <label>Company Location</label>
                <select class="form-control select2" multiple disabled style="width: 100% !important;">
                    @php
                        $selectedCompanyIds = is_array($LoadingProgram->company_locations) ? $LoadingProgram->company_locations : explode(',', $LoadingProgram->company_locations);
                        $companyLocations = \App\Models\Master\CompanyLocation::whereIn('id', array_filter($selectedCompanyIds))->get();
                    @endphp
                    @foreach($companyLocations as $loc)
                        <option value="{{ $loc->id }}" selected>{{ $loc->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="col-xs-12 col-sm-4 col-md-4">
            <div class="form-group">
                <label>Arrival Location</label>
                <select class="form-control select2" multiple disabled style="width: 100% !important;">
                    @php
                        $selectedArrivalIds = is_array($LoadingProgram->arrival_locations) ? $LoadingProgram->arrival_locations : explode(',', $LoadingProgram->arrival_locations);
                        $arrivalLocations = \App\Models\Master\ArrivalLocation::whereIn('id', array_filter($selectedArrivalIds))->get();
                    @endphp
                    @foreach($arrivalLocations as $loc)
                        <option value="{{ $loc->id }}" selected>{{ $loc->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="col-xs-12 col-sm-4 col-md-4">
            <div class="form-group">
                <label>Sub Arrival Location</label>
                <select class="form-control select2" multiple disabled style="width: 100% !important;">
                    @php
                        $selectedSubArrivalIds = is_array($LoadingProgram->sub_arrival_locations) ? $LoadingProgram->sub_arrival_locations : explode(',', $LoadingProgram->sub_arrival_locations);
                        $subArrivalLocations = \App\Models\Master\ArrivalSubLocation::whereIn('id', array_filter($selectedSubArrivalIds))->get();
                    @endphp
                    @foreach($subArrivalLocations as $loc)
                        <option value="{{ $loc->id }}" selected>{{ $loc->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <div class="row mt-3" id="lineItemsContainer">
        <style>
            .items-table {
                table-layout: fixed !important;
                min-width: 2700px !important;
                width: 2700px !important;
            }
        </style>
        <div class="col-12">
            <h6 class="header-heading-sepration">Loading Program Items</h6>
            <div class="table-responsive">
                <table class="table table-bordered table-striped items-table">
                    <thead class="thead-light">
                        <tr>
                            <th style="width: 300px">Sale Order</th>
                            <th style="width: 300px">Delivery Order</th>
                            <th style="width: 200px">Truck Number</th>
                            <th style="width: 200px">Container Number</th>
                            <th style="width: 180px">Packing</th>
                            <th style="width: 250px">Brand</th>
                            <th style="width: 280px">Factory/Arrival Location</th>
                            <th style="width: 280px">Gala/Sub Arrival Location</th>
                            <th style="width: 220px">Driver Name</th>
                            <th style="width: 220px">Contact Details</th>
                            <th style="width: 250px">Transporter</th>
                            <th style="width: 120px">Sug. Qty</th>
                        </tr>
                    </thead>
                    <tbody id="itemsList">
                        @foreach($LoadingProgram->loadingProgramItems as $item)
                        <tr>
                            <td>
                                <select class="form-control form-control-sm select2" multiple disabled>
                                    @foreach($item->saleOrders as $so)
                                        <option value="{{ $so->id }}" selected>{{ $so->reference_no }}</option>
                                    @endforeach
                                    @if($item->saleOrders->isEmpty() && $LoadingProgram->saleOrder)
                                        <option value="{{ $LoadingProgram->saleOrder->id }}" selected>{{ $LoadingProgram->saleOrder->reference_no }}</option>
                                    @endif
                                </select>
                            </td>
                            <td>
                                <select class="form-control form-control-sm select2" multiple disabled>
                                    @foreach($item->deliveryOrders as $do)
                                        <option value="{{ $do->id }}" selected>{{ $do->reference_no }}</option>
                                    @endforeach
                                    @if($item->deliveryOrders->isEmpty() && $LoadingProgram->deliveryOrder)
                                        <option value="{{ $LoadingProgram->deliveryOrder->id }}" selected>{{ $LoadingProgram->deliveryOrder->reference_no }}</option>
                                    @endif
                                </select>
                            </td>
                            <td><input type="text" value="{{ $item->truck_number }}" class="form-control form-control-sm" disabled></td>
                            <td><input type="text" value="{{ $item->container_number }}" class="form-control form-control-sm" disabled></td>
                            <td>
                                @php
                                    $selectedPackings = array_filter(explode(', ', $item->packing));
                                @endphp
                                <select class="form-control form-control-sm select2" multiple disabled>
                                    @foreach($selectedPackings as $p)
                                        <option value="{{ $p }}" selected>{{ $p }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td>
                                @php
                                    $itemBrandIds = $item->deliveryOrders->flatMap(function($do) {
                                        return $do->delivery_order_data->pluck('brand_id');
                                    })->unique()->toArray();
                                    if (empty($itemBrandIds) && $item->brand_id) {
                                        $itemBrandIds = [$item->brand_id];
                                    }
                                @endphp
                                <select class="form-control form-control-sm select2" multiple disabled>
                                    @foreach($Brands as $brand)
                                        <option value="{{ $brand->id }}" @selected(in_array($brand->id, $itemBrandIds))>{{ $brand->name }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td>
                                <select class="form-control form-control-sm select2" disabled>
                                    <option selected>{{ $item->arrivalLocation->name ?? 'N/A' }}</option>
                                </select>
                            </td>
                            <td>
                                <select class="form-control form-control-sm select2" disabled>
                                    <option selected>{{ $item->subArrivalLocation->name ?? 'N/A' }}</option>
                                </select>
                            </td>
                            <td><input type="text" value="{{ $item->driver_name }}" class="form-control form-control-sm" disabled></td>
                             <td><input type="text" value="{{ $item->contact_details }}" class="form-control form-control-sm" disabled></td>
                             <td>
                                 <select class="form-control form-control-sm select2" disabled>
                                     <option selected>{{ $item->transporter->name ?? '-' }}</option>
                                 </select>
                             </td>
                             <td><input type="text" value="{{ number_format($item->qty, 2) }}" class="form-control form-control-sm" disabled></td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="form-group">
                <label>Remark:</label>
                <textarea class="form-control" disabled rows="3">{{ $LoadingProgram->remark }}</textarea>
            </div>
        </div>
    </div>
</div>

<div>
    <button type="button" class="btn btn-secondary" style="float: right; margin-bottom: 20px;" data-dismiss="modal">Close</button>
</div>

<script>
    if (typeof jQuery !== 'undefined') {
        $('.select2').each(function() {
            $(this).select2({
                width: '100%',
                dropdownParent: $(this).closest('.modal-body')
            });
        });
    }
</script>
