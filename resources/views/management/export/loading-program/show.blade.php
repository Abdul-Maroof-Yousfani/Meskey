<style>
    .modal-footer {
        background: white;
    }
</style>
<div class="modal-body">
    <div class="row form-mar">
        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group">
                <label>Export Order:</label>
                <select class="form-control select2" multiple disabled style="width: 100% !important;">
                    @foreach ($loadingProgram->exportOrders as $eo)
                        <option value="{{ $eo->id }}" selected>{{ $eo->voucher_no ?? $eo->contract_no ?? 'EO-' . $eo->id }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group">
                <label>Delivery Order:</label>
                <select class="form-control select2" multiple disabled style="width: 100% !important;">
                    @foreach ($loadingProgram->deliveryOrders as $do)
                        <option value="{{ $do->id }}" selected>{{ $do->reference_no }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    {{-- Export Order Details Tabs --}}
    <div class="row">
        <div class="col-12">
            <h6 class="header-heading-sepration">Export Order Details</h6>
            <div class="card card-outline-info">
                <div class="card-header p-0 pt-1">
                    <ul class="nav nav-tabs horizontal-scrollable-tabs" id="eo-details-tabs" role="tablist" style="overflow-x: auto; white-space: nowrap; display: flex; flex-wrap: nowrap;">
                        @foreach($loadingProgram->exportOrders as $index => $eo)
                            <li class="nav-item" style="flex: 0 0 auto;">
                                <a class="nav-link {{ $index == 0 ? 'active' : '' }}" id="eo-tab-{{ $eo->id }}" data-toggle="pill" href="#eo-content-{{ $eo->id }}" role="tab">
                                    {{ $eo->voucher_no ?? $eo->contract_no ?? 'EO-' . $eo->id }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
                <div class="card-body">
                    <div class="tab-content">
                        @foreach($loadingProgram->exportOrders as $index => $eo)
                            <div class="tab-pane fade {{ $index == 0 ? 'show active' : '' }}" id="eo-content-{{ $eo->id }}" role="tabpanel">
                                <div class="row">
                                    <div class="col-xs-12 col-sm-6 col-md-3">
                                        <div class="form-group">
                                            <label>Buyer:</label>
                                            <input type="text" value="{{ $eo->buyer->name ?? 'N/A' }}" disabled class="form-control" />
                                        </div>
                                    </div>
                                    <div class="col-xs-12 col-sm-6 col-md-3">
                                        <div class="form-group">
                                            <label>Commodity:</label>
                                            <input type="text" value="{{ $eo->product->name ?? 'N/A' }}" disabled class="form-control" />
                                        </div>
                                    </div>
                                    <div class="col-xs-12 col-sm-6 col-md-3">
                                        <div class="form-group">
                                            <label>EO Date:</label>
                                            <input type="text" value="{{ $eo->created_at ? $eo->created_at->format('Y-m-d') : 'N/A' }}" disabled class="form-control" />
                                        </div>
                                    </div>
                                    <div class="col-xs-12 col-sm-6 col-md-3">
                                        <div class="form-group">
                                            <label>Total Qty:</label>
                                            <input type="text" value="{{ $eo->packingItems->sum('metric_tons') }}" disabled class="form-control" />
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
    @if(!$loadingProgram->deliveryOrders->isEmpty())
    <div class="row mt-3">
        <div class="col-12">
            <h6 class="header-heading-sepration">Delivery Order Details</h6>
            <div class="card card-outline-info">
                <div class="card-header p-0 pt-1">
                    <ul class="nav nav-tabs horizontal-scrollable-tabs" id="do-details-tabs" role="tablist" style="overflow-x: auto; white-space: nowrap; display: flex; flex-wrap: nowrap;">
                        @foreach($loadingProgram->deliveryOrders as $index => $do)
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
                        @foreach($loadingProgram->deliveryOrders as $index => $do)
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
                                            @php
                                                $total_qty = $do->type == 'export_order' 
                                                    ? ($do->exportPackingItems ? $do->exportPackingItems->sum('metric_tons') : 0)
                                                    : ($do->delivery_order_data ? $do->delivery_order_data->sum('qty') : 0);
                                            @endphp
                                            <input type="text" value="{{ number_format($total_qty, 3) }}" disabled class="form-control" />
                                        </div>
                                    </div>
                                    <div class="col-xs-12 col-sm-6 col-md-4">
                                        <div class="form-group">
                                            <label>Balance Qty:</label>
                                            <input type="text" value="{{ number_format(get_second_weighbridge_balance_by_delivery_order($do->id), 3) }}" disabled class="form-control" />
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
                        $selectedCompanyIds = is_array($loadingProgram->company_locations) ? $loadingProgram->company_locations : explode(',', $loadingProgram->company_locations);
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
                        $selectedArrivalIds = is_array($loadingProgram->arrival_locations) ? $loadingProgram->arrival_locations : explode(',', $loadingProgram->arrival_locations);
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
                        $selectedSubArrivalIds = is_array($loadingProgram->sub_arrival_locations) ? $loadingProgram->sub_arrival_locations : explode(',', $loadingProgram->sub_arrival_locations);
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
                            <th style="width: 300px">Export Order</th>
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
                        @foreach($loadingProgram->loadingProgramItems as $item)
                        <tr>
                            <td>
                                <select class="form-control form-control-sm select2" multiple disabled>
                                    @foreach($item->exportOrders as $eo)
                                        <option value="{{ $eo->id }}" selected>{{ $eo->voucher_no ?? $eo->contract_no ?? 'EO-' . $eo->id }}</option>
                                    @endforeach
                                    @if($item->exportOrders->isEmpty() && $loadingProgram->exportOrder)
                                        <option value="{{ $loadingProgram->exportOrder->id }}" selected>{{ $loadingProgram->exportOrder->voucher_no ?? $loadingProgram->exportOrder->contract_no ?? 'EO-' . $loadingProgram->exportOrder->id }}</option>
                                    @endif
                                </select>
                            </td>
                            <td>
                                <select class="form-control form-control-sm select2" multiple disabled>
                                    @foreach($item->deliveryOrders as $do)
                                        <option value="{{ $do->id }}" selected>{{ $do->reference_no }}</option>
                                    @endforeach
                                    @if($item->deliveryOrders->isEmpty() && $loadingProgram->deliveryOrder)
                                        <option value="{{ $loadingProgram->deliveryOrder->id }}" selected>{{ $loadingProgram->deliveryOrder->reference_no }}</option>
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
                                <select class="form-control form-control-sm select2" multiple disabled>
                                    @if($item->brand)
                                        <option value="{{ $item->brand_id }}" selected>{{ $item->brand->name }}</option>
                                    @endif
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
                <textarea class="form-control" disabled rows="3">{{ $loadingProgram->remark }}</textarea>
            </div>
        </div>
    </div>
</div>

<div class="modal-footer">
    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
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
