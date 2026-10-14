<form action="{{ route('production-machine-analysis.store') }}" method="POST" id="ajaxSubmit" autocomplete="off">
    @csrf
    <input type="hidden" id="listRefresh" value="{{ route('get.production-machine-analysis') }}">
    <div class="row form-mar">
        <!-- Header Information -->
        <div class="col-md-12">
            <h6 class="header-heading-sepration">Machine Analysis Information</h6>
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Date:</label>
                        <input type="date" name="date" class="form-control" value="{{ date('Y-m-d') }}" readonly>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Analysis Request: <span class="text-danger">*</span></label>
                        <select name="analysis_request_id" id="analysis_request_id" class="form-control select2" required>
                            <option value="">Select Analysis Request</option>
                            @foreach($analysisRequests as $req)
                                <option value="{{ $req->id }}"
                                    data-company-location-id="{{ $req->company_location_id }}"
                                    data-company-location-name="{{ $req->companyLocation?->name }}"
                                    data-arrival-location-id="{{ $req->arrival_location_id }}"
                                    data-arrival-location-name="{{ $req->arrivalLocation?->name }}"
                                    data-plant-id="{{ $req->plant_id }}"
                                    data-plant-name="{{ $req->plant?->name }}"
                                    data-product-id="{{ $req->jobOrder?->product_id }}"
                                    data-request-date="{{ $req->request_date?->format('Y-m-d') }}"
                                    @selected(isset($analysisRequest) && $analysisRequest->id == $req->id)>
                                    {{ $req->request_no }} - {{ $req->companyLocation?->name }} (Plant: {{ $req->plant?->name ?? 'N/A' }}) - {{ $req->request_date->format('d-m-Y') }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Product (Commodity): <span class="text-danger">*</span>
                            <span id="product_fixed_badge" class="badge badge-warning ml-1 {{ isset($fixedProduct) && $fixedProduct ? '' : 'd-none' }}">
                                <i class="ft-lock"></i> Fixed for this Run
                            </span>
                        </label>
                        <select name="product_id" id="product_id" class="form-control select2" required {{ isset($fixedProduct) && $fixedProduct ? 'disabled' : '' }}>
                            <option value="">Select Product</option>
                            @foreach($products as $product)
                                @php
                                    $isSelected = false;
                                    if (isset($fixedProduct) && $fixedProduct) {
                                        $isSelected = ($fixedProduct->id == $product->id);
                                    } elseif (isset($analysisRequest) && $analysisRequest->jobOrder?->product_id == $product->id) {
                                        $isSelected = true;
                                    }
                                @endphp
                                <option value="{{ $product->id }}" @selected($isSelected)>
                                    {{ $product->name }}
                                </option>
                            @endforeach
                        </select>
                        <input type="hidden" name="hidden_product_id" id="hidden_product_id" value="{{ $fixedProduct?->id ?? '' }}" {{ isset($fixedProduct) && $fixedProduct ? '' : 'disabled' }}>
                        <small id="product_fixed_help" class="form-text text-danger font-weight-bold {{ isset($fixedProduct) && $fixedProduct ? '' : 'd-none' }}">
                            <i class="ft-info mr-1"></i> Product is locked to match first analysis record for this Arrival, Plant & Date.
                        </small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Machine: <span class="text-danger">*</span></label>
                        <select name="production_machine_id" id="production_machine_id" class="form-control select2" required>
                            <option value="">Select Machine</option>
                        </select>
                    </div>
                </div>

                <!-- Selected Request Details Summary -->
                <div id="request_details_box" class="col-12 mb-2 {{ isset($analysisRequest) && $analysisRequest ? '' : 'd-none' }}">
                    <div class="alert alert-light border py-2 px-3 mb-0 d-flex flex-wrap align-items-center justify-content-between" style="background: #f8fafc; border-radius: 6px;">
                        <div>
                            <i class="ft-map-pin text-primary mr-1"></i> Location: <strong id="req_disp_location">{{ $analysisRequest->companyLocation?->name ?? '' }}</strong> &bull;
                            <i class="ft-navigation text-info mr-1 ml-2"></i> Arrival: <span id="req_disp_arrival">{{ $analysisRequest->arrivalLocation?->name ?? '' }}</span> &bull;
                            <i class="ft-cpu text-success mr-1 ml-2"></i> Plant: <span id="req_disp_plant">{{ $analysisRequest->plant?->name ?? '' }}</span>
                        </div>
                    </div>
                </div>

                <!-- Hidden Location, Arrival Location, Plant dropdowns -->
                <div class="d-none">
                    <select name="company_location_id" id="company_location_id">
                        <option value="">Select Location</option>
                        @foreach($companyLocations as $location)
                            <option value="{{ $location->id }}" @selected($preSelectedLocationId == $location->id)>
                                {{ $location->name }}
                            </option>
                        @endforeach
                    </select>
                    <select name="arrival_location_id" id="arrival_location_id">
                        <option value="">Select Arrival Location</option>
                        @if(isset($initialArrivalLocations))
                            @foreach($initialArrivalLocations as $arr)
                                <option value="{{ $arr->id }}" @selected(isset($analysisRequest) && $analysisRequest->arrival_location_id == $arr->id)>
                                    {{ $arr->name }}
                                </option>
                            @endforeach
                        @endif
                    </select>
                    <select name="plant_id" id="plant_id">
                        <option value="">Select Plant</option>
                        @if(isset($initialPlants))
                            @foreach($initialPlants as $pl)
                                <option value="{{ $pl->id }}" @selected(isset($analysisRequest) && $analysisRequest->plant_id == $pl->id)>
                                    {{ $pl->name }}
                                </option>
                            @endforeach
                        @endif
                    </select>
                </div>
            </div>
        </div>

        <!-- QC Checklist Section -->
        <div class="col-md-12 mt-4">
            <h6 class="header-heading-sepration font-weight-bold mb-3">
                QC Checklist
            </h6>

            <input type="hidden" name="items[0][time]" value="{{ date('H:i:s') }}">
            <input type="hidden" name="items[0][unit_id]" value="{{ $units->first()?->id ?? '' }}">

            <div class="striped-rows qc-checklist-container" id="qcChecklistContainer">
                @forelse($productSlabTypes as $productSlabType)
                    <div class="form-group row qc-slab-row">
                        <label class="col-md-3 label-control">
                            {{ $productSlabType->name }}
                        </label>
                        <div class="col-md-9">
                            <div class="input-group">
                                <input type="text" 
                                       name="items[0][params][{{ $productSlabType->id }}]" 
                                       class="form-control" 
                                       placeholder="{{ $productSlabType->qc_symbol }}">
                                <div class="input-group-append">
                                    <span class="input-group-text">{{ $productSlabType->qc_symbol }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="alert alert-warning m-2">
                        No Slabs Found
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Remarks Section -->
        <div class="col-md-12 mt-4">
            <div class="form-group">
                <label>Remarks:</label>
                <textarea name="remarks" class="form-control" rows="4" placeholder="Enter Remarks"></textarea>
            </div>
        </div>
    </div>

    <div class="row bottom-button-bar">
        <div class="col-12 text-right">
            <a type="button" class="btn btn-danger modal-sidebar-close closebutton">Close</a>
            <button type="submit" class="btn btn-primary submitbutton">Save Analysis</button>
        </div>
    </div>
</form>

<style>
    .qc-checklist-container .form-group {
        margin: 0;
        padding: 10px 15px;
        display: flex;
        align-items: center;
    }
    .qc-checklist-container .form-group:nth-child(even) {
        background-color: #f7f7f8;
    }
    .qc-checklist-container .form-group:nth-child(odd) {
        background-color: #ffffff;
    }
    .qc-checklist-container label.label-control {
        margin-bottom: 0;
        font-weight: 700;
        text-transform: uppercase;
        color: #2c3e50;
        letter-spacing: 0.5px;
        font-size: 0.85rem;
    }
    .qc-checklist-container .input-group-text {
        background-color: #f7f7f8;
        border: 1px solid #dcdce0;
        color: #6c757d;
        min-width: 45px;
        justify-content: center;
    }
    .layout-dark .qc-checklist-container .form-group:nth-child(even) {
        background-color: #101010;
    }
    .layout-dark .qc-checklist-container .form-group:nth-child(odd) {
        background-color: #1a1a1a;
    }
    .layout-dark .qc-checklist-container label.label-control {
        color: #e2e8f0;
    }
    .layout-dark .qc-checklist-container .input-group-text {
        background-color: #262626;
        border-color: #404040;
        color: #ffffff;
    }
</style>

<script>
    $(document).ready(function() {
        function initSelect2(el) {
            if ($.fn.select2) {
                $(el).select2({ width: '100%' });
            }
        }
        initSelect2('.select2');

        function renderQcChecklist(slabs) {
            let container = $('#qcChecklistContainer');
            container.empty();
            if (!slabs || slabs.length === 0) {
                container.html('<div class="alert alert-warning m-2">No Slabs Found</div>');
                return;
            }

            let html = '';
            slabs.forEach(function(slab) {
                let symbol = slab.qc_symbol ? slab.qc_symbol : '%';
                html += `
                    <div class="form-group row qc-slab-row">
                        <label class="col-md-3 label-control">
                            ${slab.name}
                        </label>
                        <div class="col-md-9">
                            <div class="input-group">
                                <input type="text" 
                                       name="items[0][params][${slab.id}]" 
                                       class="form-control" 
                                       placeholder="${symbol}">
                                <div class="input-group-append">
                                    <span class="input-group-text">${symbol}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            });
            container.html(html);
        }

        function loadSlabsForProduct(productId) {
            if(!productId) return;
            $.ajax({
                url: "{{ route('production-machine-analysis.get-slabs') }}",
                type: "GET",
                data: { product_id: productId },
                success: function(response) {
                    renderQcChecklist(response);
                }
            });
        }

        $('#product_id').on('change', function() {
            let productId = $(this).val();
            loadSlabsForProduct(productId);
        });

        function loadMachines(arrivalId, plantId, selectedMachineId = null) {
            let $machSelect = $('#production_machine_id');
            $machSelect.html('<option value="">Select Machine</option>').prop('disabled', true);
            if (arrivalId && plantId) {
                $machSelect.html('<option value="">Loading...</option>');
                let url = '{{ route("production-machine-analysis.get-machines", [":arrivalId", ":plantId"]) }}';
                url = url.replace(':arrivalId', arrivalId).replace(':plantId', plantId);
                $.get(url, function(data) {
                    $machSelect.html('<option value="">Select Machine</option>').prop('disabled', false);
                    $.each(data, function(i, item) {
                        let isSel = (selectedMachineId && selectedMachineId == item.id) ? 'selected' : '';
                        $machSelect.append(`<option value="${item.id}" ${isSel}>${item.name}</option>`);
                    });
                    $machSelect.trigger('change');
                });
            }
        }

        // Handle Analysis Request Selection
        $('#analysis_request_id').on('change', function() {
            let reqId = $(this).val();
            let dateVal = $('input[name="date"]').val();

            if (reqId) {
                let $opt = $(this).find(':selected');
                let compLocId = $opt.data('company-location-id');
                let arrivalLocId = $opt.data('arrival-location-id');
                let plantId = $opt.data('plant-id');
                let compLocName = $opt.data('company-location-name');
                let arrivalLocName = $opt.data('arrival-location-name');
                let plantName = $opt.data('plant-name');
                let defaultProductId = $opt.data('product-id');

                $('#company_location_id').val(compLocId);
                $('#arrival_location_id').val(arrivalLocId);
                $('#plant_id').val(plantId);

                $('#req_disp_location').text(compLocName || '');
                $('#req_disp_arrival').text(arrivalLocName || '');
                $('#req_disp_plant').text(plantName || '');
                $('#request_details_box').removeClass('d-none');

                loadMachines(arrivalLocId, plantId);

                $.ajax({
                    url: "{{ route('production-machine-analysis.check-fixed-product') }}",
                    type: "GET",
                    data: {
                        analysis_request_id: reqId,
                        arrival_location_id: arrivalLocId,
                        plant_id: plantId,
                        date: dateVal,
                        exclude_id: "",
                        exclude_type: 'machine'
                    },
                    success: function(res) {
                        if (res.is_fixed && res.product_id) {
                            $('#product_id').val(res.product_id).trigger('change.select2');
                            $('#product_id').prop('disabled', true);
                            $('#hidden_product_id').val(res.product_id).prop('disabled', false);
                            $('#product_fixed_badge').removeClass('d-none');
                            $('#product_fixed_help').removeClass('d-none');
                            loadSlabsForProduct(res.product_id);
                        } else {
                            $('#product_id').prop('disabled', false);
                            $('#hidden_product_id').val('').prop('disabled', true);
                            $('#product_fixed_badge').addClass('d-none');
                            $('#product_fixed_help').addClass('d-none');

                            let targetProd = res.suggested_product_id || defaultProductId;
                            if (targetProd) {
                                $('#product_id').val(targetProd).trigger('change');
                            }
                        }
                    }
                });
            } else {
                $('#company_location_id').val('');
                $('#arrival_location_id').val('');
                $('#plant_id').val('');
                $('#request_details_box').addClass('d-none');
                $('#production_machine_id').html('<option value="">Select Machine</option>').prop('disabled', true).trigger('change');
                $('#product_id').prop('disabled', false);
                $('#hidden_product_id').val('').prop('disabled', true);
                $('#product_fixed_badge').addClass('d-none');
                $('#product_fixed_help').addClass('d-none');
            }
        });

        // Ensure product_id is sent on form submit even if disabled
        $('#ajaxSubmit').on('submit', function() {
            $('#product_id').prop('disabled', false);
            $('#hidden_product_id').prop('disabled', true);
        });

        if ($('#analysis_request_id').val()) {
            $('#analysis_request_id').trigger('change');
        } else if ($('#product_id').val()) {
            $('#product_id').trigger('change');
        }
    });
</script>
