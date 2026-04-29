<style>
    .modal-footer {
        background: white;
    }
</style>
<div class="modal-body">
    <div class="row form-mar">
        <div class="col-xs-4 col-sm-4 col-md-4">
            <div class="form-group">
                <label>Export Order:</label>
                <select class="form-control select2" multiple disabled style="width: 100% !important;">
                    @foreach ($loadingProgram->exportOrders as $eo)
                        <option value="{{ $eo->id }}" selected>{{ $eo->voucher_no ?? $eo->contract_no ?? 'EO-' . $eo->id }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="col-xs-4 col-sm-4 col-md-4">
            <div class="form-group">
                <label>Delivery Order:</label>
                <select class="form-control select2" multiple disabled style="width: 100% !important;">
                    @foreach ($loadingProgram->deliveryOrders as $do)
                        <option value="{{ $do->id }}" selected>{{ $do->reference_no }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="col-xs-4 col-sm-4 col-md-4">
            <div class="form-group">
                <label>Vessel Name:</label>
                <input type="text" value="{{ $loadingProgram->vessel_name ?? 'N/A' }}" disabled class="form-control" />
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
                                            <input type="text" value="{{ $eo->packingItems ? $eo->packingItems->sum('metric_tons') : 0 }}" disabled class="form-control" />
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
                        $selectedCompanyIds = is_array($loadingProgram->company_locations) ? $loadingProgram->company_locations : explode(',', (string)$loadingProgram->company_locations);
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
                        $selectedArrivalIds = is_array($loadingProgram->arrival_locations) ? $loadingProgram->arrival_locations : explode(',', (string)$loadingProgram->arrival_locations);
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
                        $selectedSubArrivalIds = is_array($loadingProgram->sub_arrival_locations) ? $loadingProgram->sub_arrival_locations : explode(',', (string)$loadingProgram->sub_arrival_locations);
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
        <div class="col-12">
            <h6 class="header-heading-sepration">Loading Program Logistics Details</h6>
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead class="thead-light">
                        <tr>
                            <th>Truck Number</th>
                            <th>Container Number</th>
                            <th>Driver Name</th>
                            <th>Contact Details</th>
                            <th>Transporter</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($loadingProgram->loadingProgramItems as $item)
                        <tr>
                            <td>{{ $item->truck_number }}</td>
                            <td>{{ $item->container_number ?? '-' }}</td>
                            <td>{{ $item->driver_name ?? '-' }}</td>
                            <td>{{ $item->contact_details ?? '-' }}</td>
                            <td>{{ $item->transporter->name ?? '-' }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center">No items added yet.</td>
                        </tr>
                        @endforelse
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
