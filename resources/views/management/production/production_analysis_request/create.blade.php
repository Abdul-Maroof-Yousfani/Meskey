<form action="{{ route('production-analysis-request.store') }}" method="POST" id="ajaxSubmit" autocomplete="off">
    @csrf
    <input type="hidden" id="listRefresh" value="{{ route('get.production-analysis-request') }}">
    <div class="row form-mar">
        <!-- Request Information -->
        <div class="col-md-12">
            <h6 class="header-heading-sepration">Analysis Request Information</h6>
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Date: <span class="text-danger">*</span></label>
                        <input type="date" name="request_date" id="request_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Request #: <span class="text-danger">*</span></label>
                        <input type="text" name="request_no" id="request_no" class="form-control" value="{{ $requestNo }}" readonly>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Analysis Type: <span class="text-danger">*</span></label>
                        <select name="type" id="analysis_type" class="form-control select2" required>
                            <option value="">Select Analysis Type</option>
                            @foreach($types as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label>Company Location: <span class="text-danger">*</span></label>
                        <select name="company_location_id" id="req_company_location_id" class="form-control select2" required>
                            @foreach($companyLocations as $location)
                                <option value="{{ $location->id }}" @selected($preSelectedLocationId == $location->id)>
                                    {{ $location->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Arrival Location: <span class="text-danger">*</span></label>
                        <select name="arrival_location_id" id="req_arrival_location_id" class="form-control select2" required @disabled(!$preSelectedLocationId)>
                            <option value="">Select Arrival Location</option>
                            @foreach($arrivalLocations as $arrival)
                                <option value="{{ $arrival->id }}">{{ $arrival->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Plant: <span class="text-danger">*</span></label>
                        <select name="plant_id" id="req_plant_id" class="form-control select2" required disabled>
                            <option value="">Select Plant</option>
                        </select>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        <label>Production Voucher: <small class="text-muted">(Optional)</small></label>
                        <select name="production_voucher_id" id="req_production_voucher_id" class="form-control select2">
                            <option value="">Select Production Voucher (Optional)</option>
                            @foreach($productionVouchers as $pv)
                                <option value="{{ $pv->id }}">{{ app(\App\Http\Controllers\Production\ProductionAnalysisRequestController::class)->voucherLabel($pv) }}</option>
                            @endforeach
                        </select>
                        <small class="text-muted">Job orders are taken from the selected voucher.</small>
                    </div>
                </div>

                <div class="col-md-12">
                    <div class="form-group">
                        <label>Remarks: <small class="text-muted">(Optional)</small></label>
                        <textarea name="remarks" class="form-control" rows="3" placeholder="Enter remarks or specific instructions for the analysis..."></textarea>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row bottom-button-bar">
        <div class="col-12 text-right">
            <a type="button" class="btn btn-danger modal-sidebar-close closebutton">Close</a>
            <button type="submit" class="btn btn-primary submitbutton">Save Request</button>
        </div>
    </div>
</form>

<script>
    $(document).ready(function() {
        if ($.fn.select2) {
            $('.select2').select2({ width: '100%' });
        }

        // Location-wise Request Number update function
        function refreshRequestNumber() {
            let date = $('#request_date').val();
            let locationId = $('#req_company_location_id').val();
            if (date && locationId) {
                $.get("{{ route('production-analysis-request.get-number') }}", { 
                    date: date, 
                    company_location_id: locationId 
                }, function(res) {
                    if (res.status === 'success') {
                        $('#request_no').val(res.request_no);
                    }
                });
            }
        }

        // Production Vouchers of the selected location / plant / date
        function loadProductionVouchers() {
            let $pvSelect = $('#req_production_voucher_id');
            let current = $pvSelect.val();
            let companyLocationId = $('#req_company_location_id').val();

            $pvSelect.html('<option value="">Select Production Voucher (Optional)</option>');
            if (!companyLocationId) {
                $pvSelect.trigger('change');
                return;
            }

            $.get("{{ route('production-analysis-request.get-production-vouchers') }}", {
                company_location_id: companyLocationId,
                plant_id: $('#req_plant_id').val(),
                date: $('#request_date').val()
            }, function(data) {
                $.each(data, function(i, item) {
                    let selected = (current && current == item.id) ? 'selected' : '';
                    $pvSelect.append(`<option value="${item.id}" ${selected}>${item.text}</option>`);
                });
                $pvSelect.trigger('change');
            });
        }

        // Date change -> refresh request number and vouchers
        $('#request_date').on('change input', function() {
            refreshRequestNumber();
            loadProductionVouchers();
        });

        // Plant change -> vouchers of that plant
        $('#req_plant_id').on('change', function() {
            loadProductionVouchers();
        });

        // Company Location Change -> Update Request # and Load Arrival Locations & Production Vouchers
        $('#req_company_location_id').on('change', function() {
            let companyLocationId = $(this).val();
            let $arrivalSelect = $('#req_arrival_location_id');
            let $plantSelect = $('#req_plant_id');

            // Refresh Request # for the newly selected location
            refreshRequestNumber();

            $arrivalSelect.html('<option value="">Select Arrival Location</option>').prop('disabled', true);
            $plantSelect.html('<option value="">Select Plant</option>').prop('disabled', true);

            if (!companyLocationId) {
                $arrivalSelect.trigger('change');
                loadProductionVouchers();
                return;
            }

            // Load Arrival Locations
            $arrivalSelect.html('<option value="">Loading...</option>');
            let arrivalUrl = "{{ route('production-analysis-request.get-arrival-locations', ':id') }}".replace(':id', companyLocationId);
            $.get(arrivalUrl, function(data) {
                $arrivalSelect.html('<option value="">Select Arrival Location</option>').prop('disabled', false);
                $.each(data, function(i, item) {
                    $arrivalSelect.append(`<option value="${item.id}">${item.name}</option>`);
                });
                $arrivalSelect.trigger('change');
            });

            loadProductionVouchers();
        });

        // Arrival Location Change -> Load Plants
        $('#req_arrival_location_id').on('change', function() {
            let companyLocationId = $('#req_company_location_id').val();
            let arrivalId = $(this).val();
            let $plantSelect = $('#req_plant_id');

            $plantSelect.html('<option value="">Select Plant</option>').prop('disabled', true);

            if (companyLocationId && arrivalId) {
                $plantSelect.html('<option value="">Loading...</option>');
                let plantUrl = "{{ route('production-analysis-request.get-plants', [':companyId', ':arrivalId']) }}"
                    .replace(':companyId', companyLocationId)
                    .replace(':arrivalId', arrivalId);

                $.get(plantUrl, function(data) {
                    $plantSelect.html('<option value="">Select Plant</option>').prop('disabled', false);
                    $.each(data, function(i, item) {
                        $plantSelect.append(`<option value="${item.id}">${item.name}</option>`);
                    });
                    $plantSelect.trigger('change');
                });
            }
        });
    });
</script>
