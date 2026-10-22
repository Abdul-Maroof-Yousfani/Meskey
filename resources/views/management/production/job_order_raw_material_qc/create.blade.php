<form action="{{ route('job-order-rm-qc.store') }}" method="POST" id="ajaxSubmit" autocomplete="off">
    @csrf
    <input type="hidden" id="listRefresh" value="{{ route('get.job_order_rm_qc') }}" />

    <div class="row form-mar">
        <!-- Basic Information -->
        <div class="col-md-12">
            <h6 class="header-heading-sepration">Basic Information</h6>
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label>QC No:</label>
                        <input type="text" name="qc_no" id="qc_no" class="form-control"
                            value="{{ $qcNo ?? '' }}" readonly>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>QC Date:</label>
                        <input type="date" name="qc_date" id="qc_date" class="form-control" value="{{ $selectedDate ?? date('Y-m-d') }}" required>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        <label>Job Order No:</label>
                        <select name="job_order_id" class="form-control select2"
                            id="jobOrderSelect" required>
                            <option value="">Select Job Order</option>
                            @foreach($jobOrders as $jobOrder)
                                <option value="{{ $jobOrder->id }}"
                                    data-location-id="{{ $jobOrder->company_location_id }}"
                                    data-locations="{{ json_encode($jobOrder->company_locations->pluck('id')->toArray()) }}">
                                    {{ $jobOrder->job_order_no }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-12">
                    <h6 class="header-heading-sepration">Job Order Detail</h6>
                </div>
            </div>
            <div id="JobOrderDetail">
                <div class="alert bg-light-warning mb-2 alert-light-warning" role="alert">
                    <i class="ft-info mr-1"></i>
                    <strong>Select Job Order!</strong> Please select a Job Order to fetch the details.
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            </div>

            <div class="row">
            </div>
        </div>

        <!-- QC Commodities Section -->
        <div class="col-md-12" id="qcCommoditiesSection">
            <!-- Partial will be loaded here via AJAX -->
        </div>
    </div>

    <div class="row bottom-button-bar">
        <div class="col-12">
            <a type="button" class="btn btn-danger modal-sidebar-close position-relative top-1 closebutton">Close</a>
            <button type="submit" class="btn btn-primary submitbutton">Save QC</button>
        </div>
    </div>
</form>

<script>
    $(document).ready(function () {
        // Initialize Select2 for all multi-selects
        $('.select2').select2();
    });

    function loadJobOrders(date, selectedJobOrderId = null) {
        var $select = $('#jobOrderSelect');
        $select.empty().append('<option value="">Select Job Order</option>');

        if (!date) {
            $select.trigger('change');
            return;
        }

        $.ajax({
            url: "{{ route('job-order-rm-qc.get-job-orders-by-date') }}",
            type: "GET",
            data: { date: date },
            success: function(response) {
                if (response.job_orders && response.job_orders.length > 0) {
                    $.each(response.job_orders, function(index, jo) {
                        var isSelected = (selectedJobOrderId && selectedJobOrderId == jo.id) ? 'selected' : '';
                        $select.append(`<option value="${jo.id}" data-location-id="${jo.company_location_id || ''}" data-locations='${JSON.stringify(jo.location_ids || [])}' ${isSelected}>${jo.job_order_no}</option>`);
                    });
                }
                $select.trigger('change');
            },
            error: function(xhr) {
                console.error('Error loading job orders:', xhr);
                toastr.error('Error loading job orders for selected date');
            }
        });
    }

    function fetchQcNumber() {
        let locationId = $('#locationSelect').val()
                      || $('select[name="company_location_id"]').val()
                      || $('#jobOrderSelect option:selected').data('location-id')
                      || '';
        let selectedDate = $('input[name="qc_date"]').val();

        $.ajax({
            url: "{{ route('job-order-rm-qc.get-number') }}",
            type: "GET",
            data: {
                company_location_id: locationId,
                qc_date: selectedDate
            },
            success: function(response) {
                if (response.success && response.qc_no) {
                    $('input[name="qc_no"]').val(response.qc_no);
                } else if (typeof response === 'string') {
                    $('input[name="qc_no"]').val(response);
                }
            }
        });
    }

    function loadData() {
        const jobOrderId = $('#jobOrderSelect').val();
        const company_location_id = $('[name="company_location_id"]').val();

        if (jobOrderId) {
            fetchDynamicHTML('{{ route('get.job_order_details') }}', 'JobOrderDetail', {
                job_order_id: jobOrderId,
                company_location_id: company_location_id
            }, { 
                method: 'POST',
                onSuccess: function (response, target) {
                    target.html(response);
                    target.find('.select2').select2();
                    updateJobOrderQty();
                    fetchQcNumber();
                }
            });
        } else {
            $('#JobOrderDetail').html(`
                <div class="alert bg-light-warning mb-2 alert-light-warning" role="alert">
                    <i class="ft-info mr-1"></i>
                    <strong>Select Job Order!</strong> Please select a Job Order to fetch the details.
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            `);
            $('#qcCommoditiesSection').empty();
            fetchQcNumber();
        }
    }

    function updateJobOrderQty() {
        var selected = $('#locationSelect').find(':selected');
        var totalKg = selected.data('totalkg');

        if (totalKg) {
            $('#jobOrderQty').val(totalKg + ' Kgs');
        } else {
            $('#jobOrderQty').val('');
        }
    }

    // Run on date change (loads job orders for that date & updates QC number)
    $(document).off('change.qc_date', 'input[name="qc_date"]').on('change.qc_date', 'input[name="qc_date"]', function () {
        var selectedDate = $(this).val();
        loadJobOrders(selectedDate);
        fetchQcNumber();
    });

    // Run on job order change
    $(document).off('change.qc_jo', '#jobOrderSelect').on('change.qc_jo', '#jobOrderSelect', function () {
        loadData();
        fetchQcNumber();
    });

    // Run on location change
    $(document).off('change.qc_loc', '#locationSelect, select[name="company_location_id"]').on('change.qc_loc', '#locationSelect, select[name="company_location_id"]', function () {
        updateJobOrderQty();
        fetchQcNumber();
    });

    // Initial check
    updateJobOrderQty();
    if (!$('input[name="qc_no"]').val()) {
        fetchQcNumber();
    }
</script>