{{-- <div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
        <span aria-hidden="true">&times;</span>
    </button>
</div> --}}

<div class="modal-body">
    <div class="row form-mar">
        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group">
                <label>Sale Order:</label>
                <input type="text" value="{{ $LoadingProgram->saleOrder->reference_no ?? 'N/A' }}"
                    disabled class="form-control" autocomplete="off" readonly />
            </div>
        </div>
        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group">
                <label>Delivery Order:</label>
                <input type="text" value="{{ $LoadingProgram->deliveryOrder->reference_no ?? 'N/A' }}"
                    disabled class="form-control" autocomplete="off" readonly />
            </div>
        </div>
    </div>

    <div class="row" id="saleOrderDataContainer">
        @if($LoadingProgram->saleOrder)
            <div class="col-12">
                <h6 class="header-heading-sepration">
                    Sale Order Details
                </h6>
            </div>

            {{-- Sale Order Details Section --}}
            <div class="col-xs-12 col-sm-6 col-md-3">
                <div class="form-group">
                    <label>Buyer:</label>
                    <input type="text" value="{{ $LoadingProgram->saleOrder->customer->name ?? 'N/A' }}"
                        disabled class="form-control" autocomplete="off" readonly />
                </div>
            </div>

            <div class="col-xs-12 col-sm-6 col-md-3">
                <div class="form-group">
                    <label>Commodity:</label>
                    <input type="text" value="{{ $LoadingProgram->saleOrder->sales_order_data->first()->item->name ?? 'N/A' }}"
                        disabled class="form-control" autocomplete="off" readonly />
                </div>
            </div>

            <div class="col-xs-12 col-sm-6 col-md-3">
                <div class="form-group">
                    <label>SO Date:</label>
                    <input type="text" value="{{ $LoadingProgram->saleOrder->order_date ? $LoadingProgram->saleOrder->order_date : 'N/A' }}"
                        disabled class="form-control" autocomplete="off" readonly />
                </div>
            </div>

            <div class="col-xs-12 col-sm-6 col-md-3">
                <div class="form-group">
                    <label>SO Qty:</label>
                    <input type="text" value="{{ $LoadingProgram->saleOrder->sales_order_data->first()->qty ?? 'N/A' }}"
                        disabled class="form-control" autocomplete="off" readonly />
                </div>
            </div>
        @endif
    </div>

    <div class="row" id="locationContainer">
        <style>
            .select2-container {
                width: 100% !important;
            }
            .select2-container .select2-selection--multiple {
                width: 100% !important;
            }
        </style>
        <div class="col-xs-12 col-sm-4 col-md-4">
            <div class="form-group">
                <label>Company Location</label>
                <select class="form-control select2 w-100" id="company_locations" disabled style="width: 100% !important;">
                    <!-- Options will be populated dynamically -->
                </select>
            </div>
        </div>
        <div class="col-xs-12 col-sm-4 col-md-4">
            <div class="form-group">
                <label>Arrival Location</label>
                <select class="form-control select2 w-100" id="arrival_locations" multiple disabled style="width: 100% !important;">
                    <!-- Options will be populated dynamically -->
                </select>
            </div>
        </div>
        <div class="col-xs-12 col-sm-4 col-md-4">
            <div class="form-group">
                <label>Sub Arrival Location</label>
                <select class="form-control select2 w-100" id="sub_arrival_locations" multiple disabled style="width: 100% !important;">
                    <!-- Options will be populated dynamically -->
                </select>
            </div>
        </div>
    </div>

    <div class="row" id="lineItemsContainer">
        <div class="col-12">
            <h6 class="header-heading-sepration">
                Loading Program Items
            </h6>
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead class="thead-light">
                        <tr>
                            <th width="12%">Truck Number</th>
                            <th width="12%">Container Number</th>
                            <th width="10%">Packing</th>
                            <th width="10%">Brand</th>
                            <th width="15%">Factory/Arrival Location</th>
                            <th width="15%">Gala/Sub Arrival Location</th>
                            <th width="10%">Driver Name</th>
                            <th width="10%">Contact Details</th>
                            <th width="8%">Suggested Qty</th>
                        </tr>
                    </thead>
                    <tbody id="itemsList">
                        @forelse($LoadingProgram->loadingProgramItems as $item)
                            <tr class="item-row">
                                <td>
                                    <input type="text" value="{{ $item->truck_number }}" class="form-control form-control-sm" readonly style="min-width: 100px;">
                                </td>
                                <td>
                                    <input type="text" value="{{ $item->container_number }}" class="form-control form-control-sm" readonly style="min-width: 100px;">
                                </td>
                                <td>
                                    <input type="text" value="{{ $item->packing }}" class="form-control form-control-sm" readonly style="min-width: 80px;">
                                </td>
                                <td>
                                    <input type="text" value="{{ $item?->brand?->name ?? '' }}" class="form-control form-control-sm" readonly style="min-width: 80px;">
                                </td>
                                <td>
                                    <input type="text" value="{{ $item->arrivalLocation->name ?? 'N/A' }}" class="form-control form-control-sm" readonly style="min-width: 120px;">
                                </td>
                                <td>
                                    <input type="text" value="{{ $item->subArrivalLocation->name ?? 'N/A' }}" class="form-control form-control-sm" readonly style="min-width: 120px;">
                                </td>
                                <td>
                                    <input type="text" value="{{ $item->driver_name }}" class="form-control form-control-sm" readonly style="min-width: 100px;">
                                </td>
                                <td>
                                    <input type="text" value="{{ $item->contact_details }}" class="form-control form-control-sm" readonly style="min-width: 100px;">
                                </td>
                                <td>
                                    <input type="number" value="{{ $item->qty }}" class="form-control form-control-sm" readonly step="0.01" style="min-width: 70px;">
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted py-3">
                                    No items found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Remark:</label>
                <textarea class="form-control" readonly>{{ $LoadingProgram->remark }}</textarea>
            </div>
        </div>
    </div>
</div>

<div>
    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
</div>

<script>
    $(document).ready(function() {
        $('.select2').select2();

        // Populate and pre-select company locations
        var companyLocationsSelect = $('#company_locations');
        companyLocationsSelect.empty();

        // Populate and pre-select arrival locations
        var arrivalLocationsSelect = $('#arrival_locations');
        arrivalLocationsSelect.empty();

        // Populate and pre-select sub arrival locations
        var subArrivalLocationsSelect = $('#sub_arrival_locations');
        subArrivalLocationsSelect.empty();

        @if($LoadingProgram && $LoadingProgram->deliveryOrder)
            @php
                // Get locations from delivery order's comma-separated values
                $companyLocationIds = [$LoadingProgram->deliveryOrder->location_id];
                $arrivalLocationIds = $LoadingProgram->deliveryOrder->arrival_location_id ? explode(',', $LoadingProgram->deliveryOrder->arrival_location_id) : [];
                $subArrivalLocationIds = $LoadingProgram->deliveryOrder->sub_arrival_location_id ? explode(',', $LoadingProgram->deliveryOrder->sub_arrival_location_id) : [];

                $companyLocations = \App\Models\Master\CompanyLocation::whereIn('id', $companyLocationIds)->get();
                $arrivalLocations = \App\Models\Master\ArrivalLocation::whereIn('id', $arrivalLocationIds)->get();
                $subArrivalLocations = \App\Models\Master\ArrivalSubLocation::whereIn('id', $subArrivalLocationIds)->get();
            @endphp

            @foreach($companyLocations as $location)
                var option = new Option('{{ $location->name }}', '{{ $location->id }}', true, true);
                companyLocationsSelect.append(option);
            @endforeach

            @foreach($arrivalLocations as $location)
                var option = new Option('{{ $location->name }}', '{{ $location->id }}', true, true);
                arrivalLocationsSelect.append(option);
            @endforeach

            @foreach($subArrivalLocations as $location)
                var option = new Option('{{ $location->name }}', '{{ $location->id }}', true, true);
                subArrivalLocationsSelect.append(option);
            @endforeach
        @endif

        // Trigger change to refresh select2
        companyLocationsSelect.trigger('change');
        arrivalLocationsSelect.trigger('change');
        subArrivalLocationsSelect.trigger('change');
    });
</script>
