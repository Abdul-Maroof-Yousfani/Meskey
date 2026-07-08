@php
    $hasDeliveryOrders = $DeliveryOrders && $DeliveryOrders->count() > 0;
    $hasSaleOrders = $SalesOrders && $SalesOrders->count() > 0;
@endphp

<div class="col-12">
    <div class="card">
        <div class="card-header p-0 pt-1">
            <ul class="nav nav-tabs" id="so-do-details-tabs" role="tablist">
                @if($hasDeliveryOrders)
                    @foreach($DeliveryOrders as $index => $do)
                        <li class="nav-item">
                            <a class="nav-link {{ $index === 0 ? 'active' : '' }}" id="do-tab-{{ $do->id }}" data-toggle="pill" href="#do-content-{{ $do->id }}" role="tab" aria-controls="do-content-{{ $do->id }}" aria-selected="{{ $index === 0 ? 'true' : 'false' }}">
                                DO: {{ $do->reference_no }}
                                @if($do->is_auto_created_from_so)
                                    <span class="badge badge-warning" style="font-size: 0.6rem;">Auto</span>
                                @endif
                            </a>
                        </li>
                    @endforeach
                @elseif($hasSaleOrders)
                    @foreach($SalesOrders as $index => $so)
                        <li class="nav-item">
                            <a class="nav-link {{ $index === 0 ? 'active' : '' }}" id="so-tab-{{ $so->id }}" data-toggle="pill" href="#so-content-{{ $so->id }}" role="tab" aria-controls="so-content-{{ $so->id }}" aria-selected="{{ $index === 0 ? 'true' : 'false' }}">
                                SO: {{ $so->reference_no }}
                            </a>
                        </li>
                    @endforeach
                @endif
            </ul>
        </div>
        <div class="card-body">
            <div class="tab-content" id="so-do-details-tabs-content">
                @if($hasDeliveryOrders)
                    @foreach($DeliveryOrders as $index => $do)
                        <div class="tab-pane fade {{ $index === 0 ? 'show active' : '' }}" id="do-content-{{ $do->id }}" role="tabpanel" aria-labelledby="do-tab-{{ $do->id }}">
                            <div class="row">
                                <div class="col-xs-12 col-sm-6 col-md-4">
                                    <div class="form-group">
                                        <label>Customer:</label>
                                        <input type="text" value="{{ $do->customer->name ?? 'N/A' }}" disabled class="form-control" readonly />
                                    </div>
                                </div>
                                <div class="col-xs-12 col-sm-6 col-md-4">
                                    <div class="form-group">
                                        <label>Commodity:</label>
                                        <input type="text" value="{{ $do->delivery_order_data->first()->item->name ?? 'N/A' }}" disabled class="form-control" readonly />
                                    </div>
                                </div>
                                <div class="col-xs-12 col-sm-6 col-md-4">
                                    <div class="form-group">
                                        <label>SO Qty:</label>
                                        <input type="text" value="{{ round($do->delivery_order_data->first()->salesOrderData->qty ?? 'N/A' ) }}" disabled class="form-control" readonly />
                                    </div>
                                </div>
                                <div class="col-xs-12 col-sm-6 col-md-4">
                                    <div class="form-group">
                                        <label>DO Qty:</label>
                                        <input type="text" value="{{ round($do->delivery_order_data->first()->qty ?? 'N/A' ) }}" disabled class="form-control" readonly />
                                    </div>
                                </div>
                                <div class="col-xs-12 col-sm-6 col-md-4">
                                    <div class="form-group">
                                        <label>DO Balance (kg):</label>
                                        <input type="text" value="{{ number_format(get_second_weighbridge_balance_by_delivery_order_kg($do->id), 2) }}" disabled class="form-control" readonly />
                                    </div>
                                </div>
                                <div class="col-xs-12 col-sm-6 col-md-4">
                                    <div class="form-group">
                                        <label>Arrival Location:</label>
                                        <input type="text" value="{{ get_location_name_by_id($do->location_id) }}" disabled class="form-control" readonly />
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                @elseif($hasSaleOrders)
                    @foreach($SalesOrders as $index => $so)
                        <div class="tab-pane fade {{ $index === 0 ? 'show active' : '' }}" id="so-content-{{ $so->id }}" role="tabpanel" aria-labelledby="so-tab-{{ $so->id }}">
                            <div class="row">
                                <!-- <div class="col-xs-12 col-sm-6 col-md-6">
                                    <div class="form-group">
                                        <label>Customer:</label>
                                        <input type="text" value="{{ $so->customer->name ?? 'N/A' }}" disabled class="form-control" readonly />
                                    </div>
                                </div> -->
                                <div class="col-xs-12 col-sm-6 col-md-6">
                                    <div class="form-group">
                                        <label>Commodity:</label>
                                        <input type="text" value="{{ $so->sales_order_data->first()->item->name ?? 'N/A' }}" disabled class="form-control" readonly />
                                    </div>
                                </div>
                                <div class="col-xs-12 col-sm-6 col-md-6">
                                    <div class="form-group">
                                        <label>SO Qty:</label>
                                        <input type="text" value="{{ round($so->sales_order_data->first()->qty ?? 'N/A' ) }}" disabled class="form-control" readonly />
                                    </div>
                                </div>
                                <div class="col-xs-12 col-sm-6 col-md-6">
                                    <div class="form-group">
                                        <label>Arrival Location:</label>
                                        @php
                                            $companyLocationIds = $LoadingProgramItem->loadingProgram->company_locations ?? [];
                                            $companyLocationId = is_array($companyLocationIds) ? ($companyLocationIds[0] ?? null) : $companyLocationIds;
                                            $companyLocationName = $companyLocationId ? get_location_name_by_id($companyLocationId) : 'N/A';
                                        @endphp
                                        <input type="text" value="{{ $companyLocationName }}" disabled class="form-control" readonly />
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                @endif
            </div>

            {{-- Location Fields Moved Inside Card Body --}}
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
        <label>First Weight:</label>
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
                <option value="{{ $truckType->id }}" data-weighbridge-amount="{{ $truckType->weighbridge_amount ?? '' }}"
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
            $('.select2').select2({ width: '100%' });
        }

        // Update weighbridge amount when truck type changes
        $('#truck_type_id').on('change', function() {
            var truckTypeId = $(this).val();
            var loadingProgramItemId = '{{ $LoadingProgramItem->id }}';

            if (truckTypeId && loadingProgramItemId) {
                $.ajax({
                    url: '{{ route('sales.getWeighbridgeAmount') }}',
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
                    error: function(xhr) {
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

        // Trigger change event on page load if truck type is already selected
        if ($('#truck_type_id').val()) {
            $('#truck_type_id').trigger('change');
        }
    });
</script>
