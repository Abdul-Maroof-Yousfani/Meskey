<form action="{{ route('production-machine-analysis.update', $item->id) }}" method="POST" id="ajaxSubmit" autocomplete="off">
    @csrf
    @method('PUT')
    <input type="hidden" id="listRefresh" value="{{ route('get.production-machine-analysis') }}">
    <div class="row form-mar">
        <!-- Header Information -->
        <div class="col-md-12">
            <h6 class="header-heading-sepration">Edit Machine Analysis Information</h6>
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Date:</label>
                        <input type="date" name="date" class="form-control" value="{{ \Carbon\Carbon::parse($item->analysis_date)->format('Y-m-d') }}" readonly>
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
                                    data-product-id="{{ $req->jobOrder?->product_id }}"
                                    data-request-date="{{ $req->request_date?->format('Y-m-d') }}"
                                    @selected($item->analysis_request_id == $req->id)>
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
                            @foreach($machines as $m)
                                <option value="{{ $m->id }}" @selected($item->production_machine_id == $m->id)>
                                    {{ $m->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- Selected Request Details Summary -->
                <div id="request_details_box" class="col-12 mb-2 {{ $item->analysisRequest ? '' : 'd-none' }}">
                    <div class="alert alert-light border py-2 px-3 mb-0 d-flex flex-wrap align-items-center justify-content-between" style="background: #f8fafc; border-radius: 6px;">
                        <div>
                            <i class="ft-map-pin text-primary mr-1"></i> Location: <strong id="req_disp_location">{{ $item->analysisRequest?->companyLocation?->name ?? $item->companyLocation?->name ?? '' }}</strong> &bull;
                            <i class="ft-navigation text-info mr-1 ml-2"></i> Arrival: <span id="req_disp_arrival">{{ $item->analysisRequest?->arrivalLocation?->name ?? $item->arrivalLocation?->name ?? '' }}</span> &bull;
                            <i class="ft-cpu text-success mr-1 ml-2"></i> Plant: <span id="req_disp_plant">{{ $item->analysisRequest?->plant?->name ?? $item->plant?->name ?? '' }}</span>
                            <span id="req_disp_jo_wrapper" class="ml-2 {{ $item->analysisRequest?->voucher_display ? '' : 'd-none' }}">
                                &bull; <i class="ft-file-text text-warning mr-1"></i> Voucher: <strong id="req_disp_jo">{{ $item->analysisRequest?->voucher_display ?? '' }}</strong>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Hidden Location, Factory Location, Plant dropdowns -->
                <div class="d-none">
                    <select name="company_location_id" id="company_location_id">
                        <option value="">Select Location</option>
                        @foreach($companyLocations as $location)
                            <option value="{{ $location->id }}" @selected($item->company_location_id == $location->id)>
                                {{ $location->name }}
                            </option>
                        @endforeach
                    </select>
                    <select name="arrival_location_id" id="arrival_location_id">
                        <option value="">Select Factory Location</option>
                        @foreach($arrivalLocations as $loc)
                            <option value="{{ $loc->id }}" @selected($item->arrival_location_id == $loc->id)>
                                {{ $loc->name }}
                            </option>
                        @endforeach
                    </select>
                    <select name="plant_id" id="plant_id">
                        <option value="">Select Plant</option>
                        @foreach($plants as $pl)
                            <option value="{{ $pl->id }}" @selected($item->plant_id == $pl->id)>
                                {{ $pl->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <!-- Input / Output QC Checklist Tabs -->
        <div class="col-md-12 mt-1">
            @include('management.production.production_machine_analysis.partials.qc_tabs', ['tabItems' => $tabItems])
        </div>

        <!-- Remarks Section -->
        <div class="col-md-12 mt-4">
            <div class="form-group">
                <label>Remarks:</label>
                <textarea name="remarks" class="form-control" rows="4" placeholder="Enter Remarks">{{ $item->remarks }}</textarea>
            </div>
        </div>
    </div>

    <div class="row bottom-button-bar">
        <div class="col-12 text-right">
            <a type="button" class="btn btn-danger modal-sidebar-close closebutton">Close</a>
            <button type="submit" class="btn btn-primary submitbutton">Update Analysis</button>
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

                let currentMachineId = '{{ $item->production_machine_id }}';
                loadMachines(arrivalLocId, plantId, currentMachineId);

                $.ajax({
                    url: "{{ route('production-machine-analysis.check-fixed-product') }}",
                    type: "GET",
                    data: {
                        analysis_request_id: reqId,
                        arrival_location_id: arrivalLocId,
                        plant_id: plantId,
                        date: dateVal,
                        exclude_id: "{{ $item->id }}",
                        include_product_id: $('.machine-tab-product[data-type="input"]').val(),
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

    });
</script>
