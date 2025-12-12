<form action="{{ route('production-voucher.store') }}" method="POST" id="ajaxSubmit" autocomplete="off">
    @csrf
    <input type="hidden" id="listRefresh" value="{{ route('get.production-voucher') }}" />

    <div class="row form-mar">
        <!-- Basic Information -->
        <div class="col-md-12">
            <h6 class="header-heading-sepration">Production Voucher</h6>
            <div class="row">
                <div class="col-md-3">
                    <fieldset>
                        <label>Prod. No:</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <button class="btn btn-primary" type="button">Prod. No</button>
                            </div>
                            <input type="text" readonly name="prod_no" class="form-control">
                        </div>
                    </fieldset>
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        <label>Prod. Date:</label>
                        <input type="date" name="prod_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        <label>Location:</label>
                        <select name="location_id" id="location_id" class="form-control select2" required onchange="loadJobOrdersByLocation()">
                            <option value="">Select Location</option>
                            @foreach($companyLocations as $location)
                                <option value="{{ $location->id }}">{{ $location->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        <label>Job Ord. No:</label>
                        <select name="job_order_id[]" id="job_order_id" class="form-control select2" multiple required>
                            <option value="">Select Job Order</option>
                        </select>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        <label>Produced QTY (kg):</label>
                        <input type="number" name="produced_qty_kg" class="form-control" step="0.01" min="0.01" required>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        <label>Supervisor:</label>
                        <select name="supervisor_id" id="supervisor_id" class="form-control select2">
                            <option value="">Select Supervisor</option>
                            @foreach($supervisors as $supervisor)
                                <option value="{{ $supervisor->id }}">{{ $supervisor->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        <label>Labor (per kg):</label>
                        <input type="number" name="labor_cost_per_kg" class="form-control" step="0.0001" min="0" value="0.4">
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        <label>Overhead (per kg):</label>
                        <input type="number" name="overhead_cost_per_kg" class="form-control" step="0.0001" min="0" value="0.2">
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        <label>Status:</label>
                        <select name="status" id="status" class="form-control select2" required>
                            <option value="draft">Draft</option>
                            <option value="completed">Completed</option>
                            <option value="approved">Approved</option>
                        </select>
                    </div>
                </div>

                <div class="col-md-12">
                    <div class="form-group">
                        <label>Remarks:</label>
                        <textarea name="remarks" class="form-control" rows="3"></textarea>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row bottom-button-bar">
        <div class="col-12">
            <a type="button" class="btn btn-danger modal-sidebar-close position-relative top-1 closebutton">Close</a>
            <button type="submit" class="btn btn-primary submitbutton">Save Production Voucher</button>
        </div>
    </div>
</form>

<script>
    $(document).ready(function () {
        // Initialize Select2 for all selects
        $('.select2').select2();

        // Generate production number on date change
        $('input[name="prod_date"]').on('change', function () {
            let selectedDate = $(this).val();

            getUniversalNumber({
                table: 'production_vouchers',
                prefix: 'PRO',
                with_date: 1,
                column: 'prod_no',
                custom_date: selectedDate,
                date_format: 'm-Y',
                serial_at_end: 1,
            }, function (no) {
                $('input[name="prod_no"]').val(no);
            });
        });

        // Generate production number on page load
        if ($('input[name="prod_date"]').val()) {
            $('input[name="prod_date"]').trigger('change');
        }
    });

    function loadJobOrdersByLocation() {
        const locationId = $('#location_id').val();
        const jobOrderSelect = $('#job_order_id');

        // Clear existing options
        jobOrderSelect.empty().append('<option value="">Select Job Order</option>');

        if (!locationId) {
            jobOrderSelect.trigger('change');
            return;
        }

        // Show loading
        jobOrderSelect.prop('disabled', true);

        $.ajax({
            url: '{{ route("production-voucher.get-job-orders-by-location") }}',
            method: 'POST',
            data: {
                location_id: locationId,
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.jobOrders && response.jobOrders.length > 0) {
                    $.each(response.jobOrders, function(index, jobOrder) {
                        jobOrderSelect.append(
                            $('<option></option>')
                                .attr('value', jobOrder.id)
                                .text(jobOrder.job_order_no + (jobOrder.ref_no ? ' (' + jobOrder.ref_no + ')' : ''))
                        );
                    });
                } else {
                    jobOrderSelect.append('<option value="">No Job Orders Found</option>');
                }
                jobOrderSelect.trigger('change');
            },
            error: function(xhr) {
                console.error('Error loading job orders:', xhr);
                jobOrderSelect.append('<option value="">Error loading job orders</option>');
            },
            complete: function() {
                jobOrderSelect.prop('disabled', false);
            }
        });
    }
</script>
