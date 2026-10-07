<form action="{{ route('production-analysis-request.update', $item->id) }}" method="POST" id="ajaxSubmit" autocomplete="off">
    @csrf
    @method('PUT')
    <input type="hidden" id="listRefresh" value="{{ route('get.production-analysis-request') }}">
    <div class="row form-mar">
        <!-- Request Information -->
        <div class="col-md-12">
            <h6 class="header-heading-sepration">Edit Analysis Request ({{ $item->request_no }})</h6>
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Date: <span class="text-danger">*</span></label>
                        <input type="date" name="request_date" id="edit_request_date" class="form-control" value="{{ $item->request_date->format('Y-m-d') }}" required>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Request #: <span class="text-danger">*</span></label>
                        <input type="text" name="request_no" id="edit_request_no" class="form-control" value="{{ $item->request_no }}" readonly>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Analysis Type: <span class="text-danger">*</span></label>
                        <select name="type" id="edit_analysis_type" class="form-control select2" required>
                            <option value="">Select Analysis Type</option>
                            @foreach($types as $key => $label)
                                <option value="{{ $key }}" @selected($item->type === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label>Company Location: <span class="text-danger">*</span></label>
                        <select name="company_location_id" id="edit_company_location_id" class="form-control select2" required>
                            @foreach($companyLocations as $location)
                                <option value="{{ $location->id }}" @selected($item->company_location_id == $location->id)>
                                    {{ $location->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Arrival Location: <span class="text-danger">*</span></label>
                        <select name="arrival_location_id" id="edit_arrival_location_id" class="form-control select2" required>
                            <option value="">Select Arrival Location</option>
                            @foreach($arrivalLocations as $arrival)
                                <option value="{{ $arrival->id }}" @selected($item->arrival_location_id == $arrival->id)>
                                    {{ $arrival->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Plant: <span class="text-danger">*</span></label>
                        <select name="plant_id" id="edit_plant_id" class="form-control select2" required>
                            <option value="">Select Plant</option>
                            @foreach($plants as $plant)
                                <option value="{{ $plant->id }}" @selected($item->plant_id == $plant->id)>
                                    {{ $plant->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        <label>Job Order: <small class="text-muted">(Optional)</small></label>
                        <select name="job_order_id" id="edit_job_order_id" class="form-control select2">
                            <option value="">Select Job Order (Optional)</option>
                            @foreach($jobOrders as $jo)
                                <option value="{{ $jo->id }}" @selected($item->job_order_id == $jo->id)>
                                    {{ $jo->job_order_no }} {{ $jo->ref_no ? '(' . $jo->ref_no . ')' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="col-md-12">
                    <div class="form-group">
                        <label>Remarks: <small class="text-muted">(Optional)</small></label>
                        <textarea name="remarks" class="form-control" rows="3">{{ $item->remarks }}</textarea>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row bottom-button-bar">
        <div class="col-12 text-right">
            <a type="button" class="btn btn-danger modal-sidebar-close closebutton">Close</a>
            <button type="submit" class="btn btn-primary submitbutton">Update Request</button>
        </div>
    </div>
</form>

<script>
    $(document).ready(function() {
        if ($.fn.select2) {
            $('.select2').select2({ width: '100%' });
        }

        let originalLocationId = "{{ $item->company_location_id }}";
        let originalRequestNo = "{{ $item->request_no }}";

        function refreshRequestNumber() {
            let date = $('#edit_request_date').val();
            let locationId = $('#edit_company_location_id').val();
            if (locationId == originalLocationId) {
                $('#edit_request_no').val(originalRequestNo);
                return;
            }
            if (date && locationId) {
                $.get("{{ route('production-analysis-request.get-number') }}", {
                    date: date,
                    company_location_id: locationId
                }, function(res) {
                    if (res.status === 'success') {
                        $('#edit_request_no').val(res.request_no);
                    }
                });
            }
        }

        $('#edit_request_date').on('change', refreshRequestNumber);

        // Company Location Change -> Load Arrival Locations & Job Orders
        $('#edit_company_location_id').on('change', function() {
            let companyLocationId = $(this).val();
            let $arrivalSelect = $('#edit_arrival_location_id');
            let $plantSelect = $('#edit_plant_id');
            let $joSelect = $('#edit_job_order_id');

            refreshRequestNumber();

            $arrivalSelect.html('<option value="">Select Arrival Location</option>');
            $plantSelect.html('<option value="">Select Plant</option>');
            $joSelect.html('<option value="">Select Job Order (Optional)</option>');

            if (!companyLocationId) return;

            // Load Arrival Locations
            let arrivalUrl = "{{ route('production-analysis-request.get-arrival-locations', ':id') }}".replace(':id', companyLocationId);
            $.get(arrivalUrl, function(data) {
                $.each(data, function(i, item) {
                    $arrivalSelect.append(`<option value="${item.id}">${item.name}</option>`);
                });
                $arrivalSelect.trigger('change');
            });

            // Load Job Orders
            let joUrl = "{{ route('production-analysis-request.get-job-orders', ':id') }}".replace(':id', companyLocationId);
            $.get(joUrl, function(data) {
                $.each(data, function(i, item) {
                    let label = item.job_order_no + (item.ref_no ? ` (${item.ref_no})` : '');
                    $joSelect.append(`<option value="${item.id}">${label}</option>`);
                });
                $joSelect.trigger('change');
            });
        });

        // Arrival Location Change -> Load Plants
        $('#edit_arrival_location_id').on('change', function() {
            let companyLocationId = $('#edit_company_location_id').val();
            let arrivalId = $(this).val();
            let $plantSelect = $('#edit_plant_id');

            $plantSelect.html('<option value="">Select Plant</option>');

            if (companyLocationId && arrivalId) {
                let plantUrl = "{{ route('production-analysis-request.get-plants', [':companyId', ':arrivalId']) }}"
                    .replace(':companyId', companyLocationId)
                    .replace(':arrivalId', arrivalId);

                $.get(plantUrl, function(data) {
                    $.each(data, function(i, item) {
                        $plantSelect.append(`<option value="${item.id}">${item.name}</option>`);
                    });
                    $plantSelect.trigger('change');
                });
            }
        });
    });
</script>
