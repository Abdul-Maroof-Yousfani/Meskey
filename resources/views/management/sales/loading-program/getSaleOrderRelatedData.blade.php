<div class="col-12">
    <h6 class="header-heading-sepration">
        Sale Order Details
    </h6>
    <div class="card card-outline-info">
        <div class="card-header p-0 pt-1">
            <ul class="nav nav-tabs horizontal-scrollable-tabs" id="so-details-tabs" role="tablist" style="overflow-x: auto; white-space: nowrap; display: flex; flex-wrap: nowrap;">
                @foreach($SalesOrders as $index => $so)

                    <li class="nav-item" style="flex: 0 0 auto;">
                        <a class="nav-link {{ $index == 0 ? 'active' : '' }}" id="so-tab-{{ $so->id }}" data-toggle="pill" href="#so-content-{{ $so->id }}" role="tab" aria-controls="so-content-{{ $so->id }}" aria-selected="{{ $index == 0 ? 'true' : 'false' }}">
                            {{ $so->reference_no }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
        <div class="card-body">
            <div class="tab-content" id="so-details-tabs-content">
                @foreach($SalesOrders as $index => $so)
                    <div class="tab-pane fade {{ $index == 0 ? 'show active' : '' }}" id="so-content-{{ $so->id }}" role="tabpanel" aria-labelledby="so-tab-{{ $so->id }}">
                        <div class="row">
                            <div class="col-xs-12 col-sm-6 col-md-3">
                                <div class="form-group">
                                    <label>Buyer:</label>
                                    <input type="text" value="{{ $so->customer->name ?? 'N/A' }}" disabled class="form-control" readonly />
                                </div>
                            </div>
                            <div class="col-xs-12 col-sm-6 col-md-3">
                                <div class="form-group">
                                    <label>Commodity:</label>
                                    <input type="text" value="{{ $so->sales_order_data->first()->item->name ?? 'N/A' }}" disabled class="form-control" readonly />
                                </div>
                            </div>
                            <div class="col-xs-12 col-sm-6 col-md-2">
                                <div class="form-group">
                                    <label>SO Date:</label>
                                    <input type="text" value="{{ $so->order_date ? $so->order_date : 'N/A' }}" disabled class="form-control" readonly />
                                </div>
                            </div>
                            <div class="col-xs-12 col-sm-6 col-md-2">
                                <div class="form-group">
                                    <label>SO Qty:</label>
                                    <input type="text" value="{{ round($so->sales_order_data->first()->qty ?? 'N/A' ) }}" disabled class="form-control" readonly />
                                </div>
                            </div>
                            <div class="col-xs-12 col-sm-6 col-md-2">
                                <div class="form-group">
                                    <label>SO Balance Qty:</label>
                                    <input type="text" value="{{ getSaleOrderBalanceAgainstDC($so->id) }}" disabled class="form-control" readonly />
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

<div class="col-12 mt-3" id="delivery_order_details_wrapper" style="display: none;">
    <h6 class="header-heading-sepration">
        Delivery Order Details
    </h6>
    <div class="card card-outline-info">
        <div class="card-header p-0 pt-1">
            <ul class="nav nav-tabs horizontal-scrollable-tabs" id="do-details-tabs" role="tablist" style="overflow-x: auto; white-space: nowrap; display: flex; flex-wrap: nowrap;">
                @foreach($DeliveryOrders as $index => $do)
                    <li class="nav-item do-tab-item" data-do-id="{{ $do->id }}" style="flex: 0 0 auto; display: none;">
                        <a class="nav-link" id="do-tab-{{ $do->id }}" data-toggle="pill" href="#do-content-{{ $do->id }}" role="tab" aria-controls="do-content-{{ $do->id }}">
                            {{ $do->reference_no }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
        <div class="card-body">
            <div class="tab-content" id="do-details-tabs-content">
                @if($DeliveryOrders->isEmpty())
                    <p class="text-center text-muted">No delivery orders available for selection.</p>
                @endif
                @foreach($DeliveryOrders as $index => $do)
                    <div class="tab-pane fade do-pane" id="do-content-{{ $do->id }}" data-do-id="{{ $do->id }}" role="tabpanel" aria-labelledby="do-tab-{{ $do->id }}" style="display: none;">
                        <div class="row">
                            <div class="col-xs-12 col-sm-6 col-md-4">
                                <div class="form-group">
                                    <label>Delivery Order No:</label>
                                    <input type="text" value="{{ $do->reference_no }}" disabled class="form-control" readonly />
                                </div>
                            </div>
                            <div class="col-xs-12 col-sm-6 col-md-4">
                                <div class="form-group">
                                    <label>Total Qty:</label>
                                    <input type="text" value="{{ round($do->delivery_order_data_sum_qty) }}" disabled class="form-control" readonly />
                                </div>
                            </div>
                            <div class="col-xs-12 col-sm-6 col-md-4">
                                <div class="form-group">
                                    <label>Balance Qty:</label>
                                    <input type="text" value="{{ min(getLoadingProgramBalance($do->id), get_second_weighbridge_balance_by_delivery_order($do->id)) }}" disabled class="form-control" readonly />
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

<script>
    (function() {
        // Update delivery order dropdown
        var currentSelectedDeliveryOrders = $('#delivery_order_id').val() || [];
        $('#delivery_order_id').empty();
        
        @foreach($DeliveryOrders as $deliveryOrder)
            var selected = currentSelectedDeliveryOrders.includes('{{ $deliveryOrder->id }}') ? 'selected' : '';
            $('#delivery_order_id').append('<option value="{{ $deliveryOrder->id }}" ' + selected + '>{{ $deliveryOrder->reference_no }}</option>');
        @endforeach
        $('#delivery_order_id').trigger('change');

        // Populate and pre-select locations
        var companyLocationsSelect = $('#company_locations');
        var arrivalLocationsSelect = $('#arrival_locations');
        var subArrivalLocationsSelect = $('#sub_arrival_locations');

        companyLocationsSelect.empty();
        arrivalLocationsSelect.empty();
        subArrivalLocationsSelect.empty();

        @php
            $isAllType11 = $SalesOrders->every(function($so) { return $so->pay_type_id == 11; });
        @endphp

        @if(!$isAllType11)
            @php
                $companyLocationIds = $DeliveryOrders->pluck('location_id')->unique()->toArray();
                $arrivalLocationIds = $DeliveryOrders->pluck('arrival_location_id')->unique()->toArray();
                $subArrivalLocationIds = $DeliveryOrders->pluck('sub_arrival_location_id')->unique()->toArray();

                $companyLocations = \App\Models\Master\CompanyLocation::whereIn('id', $companyLocationIds)->get();
                $arrivalLocations = \App\Models\Master\ArrivalLocation::whereIn('id', $arrivalLocationIds)->get();
                $subArrivalLocations = \App\Models\Master\ArrivalSubLocation::whereIn('id', $subArrivalLocationIds)->get();
            @endphp

            @foreach($companyLocations as $location)
                companyLocationsSelect.append(new Option('{{ $location->name }}', '{{ $location->id }}', true, true));
            @endforeach
            // Added multiple and disabled back
            companyLocationsSelect.attr('multiple', 'multiple').prop('disabled', true);
            arrivalLocationsSelect.attr('multiple', 'multiple').prop('disabled', true);
            subArrivalLocationsSelect.attr('multiple', 'multiple').prop('disabled', true);

            @foreach($arrivalLocations as $location)
                arrivalLocationsSelect.append(new Option('{{ $location->name }}', '{{ $location->id }}', true, true));
            @endforeach

            @foreach($subArrivalLocations as $location)
                subArrivalLocationsSelect.append(new Option('{{ $location->name }}', '{{ $location->id }}', true, true));
            @endforeach
        @else
            @php
                $soCompanyLocationIds = $SalesOrders->flatMap(function($so) { return $so->locations->pluck('location_id'); })->unique()->toArray();
                $soArrivalLocationIds = $SalesOrders->flatMap(function($so) { return $so->factories->pluck('arrival_location_id'); })->unique()->toArray();
                $soSectionLocationIds = $SalesOrders->flatMap(function($so) { return $so->sections->pluck('arrival_sub_location_id'); })->unique()->toArray();
                
                $soCompanyLocations = \App\Models\Master\CompanyLocation::whereIn('id', $soCompanyLocationIds)->get();
                $soArrivalLocations = \App\Models\Master\ArrivalLocation::whereIn('id', $soArrivalLocationIds)->get();
                $soSubArrivalLocations = \App\Models\Master\ArrivalSubLocation::whereIn('id', $soSectionLocationIds)->get();
            @endphp
            
            @foreach($soCompanyLocations as $location)
                companyLocationsSelect.append(new Option('{{ $location->name }}', '{{ $location->id }}', true, true));
            @endforeach

            @foreach($soArrivalLocations as $location)
                arrivalLocationsSelect.append(new Option('{{ $location->name }}', '{{ $location->id }}', true, true));
            @endforeach

            @foreach($soSubArrivalLocations as $location)
                subArrivalLocationsSelect.append(new Option('{{ $location->name }}', '{{ $location->id }}', true, true));
            @endforeach
        @endif


        companyLocationsSelect.trigger('change');
        arrivalLocationsSelect.trigger('change');
        subArrivalLocationsSelect.trigger('change');
        
        // Define tab visibility logic
        window.updateTabsVisibility = function() {
            var selectedSOIds = $('#sale_order_id').val() || [];
            var selectedDOIds = $('#delivery_order_id').val() || [];

            // Filter SO Tabs
            $('#so-details-tabs .nav-item').each(function() {
                var soId = $(this).find('a').attr('id').replace('so-tab-', '');
                if (selectedSOIds.includes(soId)) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });

            // Filter DO Tabs
            if (selectedDOIds.length === 0) {
                $('#delivery_order_details_wrapper').hide();
                $('.do-tab-item, .do-pane').hide().removeClass('show active');
            } else {
                $('#delivery_order_details_wrapper').show();
                $('.do-tab-item, .do-pane').hide().removeClass('show active');
                $('.do-tab-item').each(function() {
                    var doId = $(this).data('do-id').toString();
                    if (selectedDOIds.includes(doId)) {
                        $(this).show();
                    }
                });
                $('.do-pane').each(function() {
                    var doId = $(this).data('do-id').toString();
                    if (selectedDOIds.includes(doId)) {
                        $(this).show();
                    }
                });

                var firstVisibleDoTab = $('#do-details-tabs .do-tab-item:visible').first().find('a');
                var firstVisibleDoPane = $('#do-details-tabs-content .do-pane:visible').first();
                if (firstVisibleDoTab.length) {
                    $('#do-details-tabs .nav-link').removeClass('active');
                    firstVisibleDoTab.addClass('active');
                }
                if (firstVisibleDoPane.length) {
                    $('#do-details-tabs-content .do-pane').removeClass('show active');
                    firstVisibleDoPane.addClass('show active');
                }
            }

            // Ensure active tab is visible
            if ($('#so-details-tabs .nav-link.active:visible').length === 0) {
                $('#so-details-tabs .nav-link:visible:first').tab('show');
            }
            if (selectedDOIds.length > 0 && $('#do-details-tabs .nav-link.active:visible').length === 0) {
                $('#do-details-tabs .nav-link:visible:first').tab('show');
            }
        };

        // Initialize visibility
        window.updateTabsVisibility();

        // Listen for changes locally if needed
        $('#delivery_order_id').on('change.tabs', function() {
            window.updateTabsVisibility();
        });
    })();
</script>
