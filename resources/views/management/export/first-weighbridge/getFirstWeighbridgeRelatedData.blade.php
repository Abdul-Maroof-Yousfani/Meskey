@php
    $hasDeliveryOrders = $DeliveryOrders && $DeliveryOrders->count() > 0;
    $hasExportOrders = $ExportOrders && $ExportOrders->count() > 0;
@endphp

<div class="col-12">
    <div class="card">
        <div class="card-header p-0 pt-1">
            <ul class="nav nav-tabs" id="eo-do-details-tabs" role="tablist">
                @if($hasDeliveryOrders)
                    @foreach($DeliveryOrders as $index => $do)
                        <li class="nav-item">
                            <a class="nav-link {{ $index === 0 ? 'active' : '' }}" id="do-tab-{{ $do->id }}" data-toggle="pill" href="#do-content-{{ $do->id }}" role="tab" aria-controls="do-content-{{ $do->id }}" aria-selected="{{ $index === 0 ? 'true' : 'false' }}">
                                DO: {{ $do->reference_no }}
                            </a>
                        </li>
                    @endforeach
                @elseif($hasExportOrders)
                    @foreach($ExportOrders as $index => $eo)
                        <li class="nav-item">
                            <a class="nav-link {{ $index === 0 ? 'active' : '' }}" id="eo-tab-{{ $eo->id }}" data-toggle="pill" href="#eo-content-{{ $eo->id }}" role="tab" aria-controls="eo-content-{{ $eo->id }}" aria-selected="{{ $index === 0 ? 'true' : 'false' }}">
                                EO: {{ $eo->voucher_no ?? $eo->contract_no ?? 'EO-'.$eo->id }}
                            </a>
                        </li>
                    @endforeach
                @endif
            </ul>
        </div>
        <div class="card-body">
            <div class="tab-content" id="eo-do-details-tabs-content">
                @if($hasDeliveryOrders)
                    @foreach($DeliveryOrders as $index => $do)
                        <div class="tab-pane fade {{ $index === 0 ? 'show active' : '' }}" id="do-content-{{ $do->id }}" role="tabpanel" aria-labelledby="do-tab-{{ $do->id }}">
                            <div class="row">
                                <div class="col-xs-12 col-sm-6 col-md-6">
                                    <div class="form-group">
                                        <label>Buyer:</label>
                                        <input type="text" value="{{ $do->customer->name ?? 'N/A' }}" disabled class="form-control" readonly />
                                    </div>
                                </div>
                                <div class="col-xs-12 col-sm-6 col-md-6">
                                    <div class="form-group">
                                        <label>Commodity:</label>
                                        <input type="text" value="{{ $do->exportOrder->product->name ?? 'N/A' }}" disabled class="form-control" readonly />
                                    </div>
                                </div>
                                <div class="col-xs-12 col-sm-6 col-md-6">
                                    <div class="form-group">
                                        <label>EO No:</label>
                                        <input type="text" value="{{ $do->exportOrder->voucher_no ?? $do->exportOrder->contract_no ?? 'N/A' }}" disabled class="form-control" readonly />
                                    </div>
                                </div>
                                <div class="col-xs-12 col-sm-6 col-md-6">
                                    <div class="form-group">
                                        <label>DO Qty(MT):</label>
                                        <input type="text" value="{{ $do->exportPackingItems->sum('metric_tons') }}" disabled class="form-control" readonly />
                                    </div>
                                </div>
                                <div class="col-xs-12 col-sm-6 col-md-6">
                                    <div class="form-group">
                                        <label>Arrival Location:</label>
                                        <input type="text" value="{{ get_location_name_by_id($do->location_id) }}" disabled class="form-control" readonly />
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                @elseif($hasExportOrders)
                    @foreach($ExportOrders as $index => $eo)
                        <div class="tab-pane fade {{ $index === 0 ? 'show active' : '' }}" id="eo-content-{{ $eo->id }}" role="tabpanel" aria-labelledby="eo-tab-{{ $eo->id }}">
                            <div class="row">
                                <div class="col-xs-12 col-sm-6 col-md-6">
                                    <div class="form-group">
                                        <label>Buyer:</label>
                                        <input type="text" value="{{ $eo->buyer->name ?? 'N/A' }}" disabled class="form-control" readonly />
                                    </div>
                                </div>
                                <div class="col-xs-12 col-sm-6 col-md-6">
                                    <div class="form-group">
                                        <label>Commodity:</label>
                                        <input type="text" value="{{ $eo->product->name ?? 'N/A' }}" disabled class="form-control" readonly />
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                @endif
            </div>

            <div class="row mt-2 border-top pt-2">
                <div class="col-xs-12 col-sm-6 col-md-6">
                    <div class="form-group">
                        <label>Factory:</label>
                        <select class="form-control select2 w-100" name="arrival_locations[]" id="arrival_locations" multiple disabled style="width: 100% !important;">
                            @php
                                $arrivalLocations = \App\Models\Master\ArrivalLocation::where('id', $LoadingProgramItem->arrival_location_id)->get();
                            @endphp
                            @foreach($arrivalLocations as $location)
                                <option value="{{ $location->id }}" selected>{{ $location->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="col-xs-12 col-sm-6 col-md-6">
                    <div class="form-group">
                        <label>Sub Arrival Location:</label>
                        <select class="form-control select2 w-100" name="sub_arrival_locations[]" id="sub_arrival_locations" multiple disabled style="width: 100% !important;">
                            @php
                                $subArrivalLocations = \App\Models\Master\ArrivalSubLocation::where('id', $LoadingProgramItem->sub_arrival_location_id)->get();
                            @endphp
                            @foreach($subArrivalLocations as $location)
                                <option value="{{ $location->id }}" selected>{{ $location->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="col-12">
    <h6 class="header-heading-sepration">
        Before Loading
    </h6>
</div>

<div class="col-xs-12 col-sm-4 col-md-4">
    <div class="form-group">
        <label>First Weight(KG):</label>
        <input type="number" name="first_weight" placeholder="Enter First Weight"
            value="{{ isset($FirstWeighbridge) ? $FirstWeighbridge->first_weight : '' }}"
            class="form-control" autocomplete="off" step="0.01" />
    </div>
</div>

<div class="col-xs-12 col-sm-4 col-md-4">
    <div class="form-group">
        <label><i class="ft-truck"></i> Truck Type:</label>
        <select class="form-control select2" name="truck_type_id" id="truck_type_id">
            <option value="">Select Truck Type</option>
            @foreach ($ArrivalTruckTypes ?? [] as $truckType)
                <option value="{{ $truckType->id }}"
                    {{ isset($FirstWeighbridge) && ($FirstWeighbridge->truck_type_id ?? null) == $truckType->id ? 'selected' : '' }}>
                    {{ $truckType->name ?? '' }}
                </option>
            @endforeach
        </select>
    </div>
</div>

<div class="col-xs-12 col-sm-4 col-md-4">
    <div class="form-group">
        <label>Weighbridge Amount:</label>
        <input type="text" name="weighbridge_amount" id="weighbridge_amount" placeholder="Weighbridge Amount"
            value="{{ isset($FirstWeighbridge) ? $FirstWeighbridge->weighbridge_amount : '' }}"
            readonly class="form-control" autocomplete="off" />
    </div>
</div>

<div class="col-xs-12 col-sm-12 col-md-12">
    <div class="form-group">
        <label>Remark:</label>
        <textarea name="remark" placeholder="Remarks" class="form-control">{{ isset($FirstWeighbridge) ? $FirstWeighbridge->remark : '' }}</textarea>
    </div>
</div>

<script>
    $(document).ready(function() {
        if (typeof $.fn.select2 !== 'undefined') {
            $('.select2').select2({
                width: '100%'
            });
        }

        $('#truck_type_id').on('change', function() {
            var truckTypeId = $(this).val();
            var loadingProgramItemId = '{{ $LoadingProgramItem->id }}';

            if (truckTypeId && loadingProgramItemId) {
                $.ajax({
                    url: '{{ route('export.getWeighbridgeAmount') }}',
                    type: 'GET',
                    data: {
                        truck_type_id: truckTypeId,
                        loading_program_item_id: loadingProgramItemId
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#weighbridge_amount').val(response.weighbridge_amount);
                        } else {
                            $('#weighbridge_amount').val('');
                            if (typeof Swal !== 'undefined') {
                                Swal.fire({
                                    icon: 'warning',
                                    title: 'Not Found',
                                    text: response.message
                                });
                            }
                        }
                    },
                    error: function() {
                        $('#weighbridge_amount').val('');
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: 'Failed to fetch weighbridge amount.'
                            });
                        }
                    }
                });
            } else {
                $('#weighbridge_amount').val('');
            }
        });

        if ($('#truck_type_id').val()) {
            $('#truck_type_id').trigger('change');
        }
    });
</script>
