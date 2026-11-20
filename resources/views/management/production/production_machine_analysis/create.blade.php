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
                                    data-job-order-id="{{ $req->job_order_id ?? ($req->jobOrderIdsForAnalysis()[0] ?? '') }}"
                                    data-job-order-no="{{ $req->voucher_display }}"
                                    data-product-id="{{ $req->suggestedProductId() }}"
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
                        <label>Job Order: <span class="text-danger">*</span></label>
                        <select name="job_order_id" id="job_order_id" class="form-control select2" required>
                            <option value="">Select Job Order</option>
                            @foreach($jobOrders as $jo)
                                <option value="{{ $jo->id }}" @selected(isset($selectedJobOrderId) && $selectedJobOrderId == $jo->id)>
                                    {{ $jo->job_order_no }}
                                </option>
                            @endforeach
                        </select>
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
                            <span id="req_disp_jo_wrapper" class="ml-2 {{ isset($analysisRequest) && $analysisRequest->voucher_display ? '' : 'd-none' }}">
                                &bull; <i class="ft-file-text text-warning mr-1"></i> Voucher: <strong id="req_disp_jo">{{ $analysisRequest?->voucher_display ?? '' }}</strong>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Hidden Location, Factory Location, Plant dropdowns -->
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
                        <option value="">Select Factory Location</option>
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

        <!-- Input / Output QC Checklist Tabs -->
        <div class="col-md-12 mt-1">
            @include('management.production.production_machine_analysis.partials.qc_tabs')
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

<script>
    $(document).ready(function() {
        function initSelect2(el) {
            if ($.fn.select2) {
                $(el).select2({ width: '100%' });
            }
        }
        initSelect2('.select2');

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
                let jobOrderId = $opt.data('job-order-id');
                let jobOrderNo = $opt.data('job-order-no');
                let compLocName = $opt.data('company-location-name');
                let arrivalLocName = $opt.data('arrival-location-name');
                let plantName = $opt.data('plant-name');

                $('#company_location_id').val(compLocId);
                $('#arrival_location_id').val(arrivalLocId);
                $('#plant_id').val(plantId);

                $('#req_disp_location').text(compLocName || '');
                $('#req_disp_arrival').text(arrivalLocName || '');
                $('#req_disp_plant').text(plantName || '');
                if (jobOrderId) {
                    $('#job_order_id').val(jobOrderId).trigger('change.select2');
                }
                if (jobOrderNo) {
                    $('#req_disp_jo').text(jobOrderNo);
                    $('#req_disp_jo_wrapper').removeClass('d-none');
                } else {
                    $('#req_disp_jo_wrapper').addClass('d-none');
                }
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
                        // Input tab: RM QC products (or all); Output tab: job order product (fixed)
                        window.applyMachineTabProducts(res);
                    }
                });
            } else {
                $('#company_location_id').val('');
                $('#arrival_location_id').val('');
                $('#plant_id').val('');
                $('#request_details_box').addClass('d-none');
                $('#production_machine_id').html('<option value="">Select Machine</option>').prop('disabled', true).trigger('change');
            }
        });


        if ($('#analysis_request_id').val()) {
            $('#analysis_request_id').trigger('change');
        }
    });
</script>
