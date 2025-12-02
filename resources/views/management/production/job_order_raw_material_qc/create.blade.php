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
                        <input type="text" name="qc_no" class="form-control"
                            value="RMQC-{{ \Carbon\Carbon::now()->format('YmdHis') }}" readonly>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>QC Date:</label>
                        <input type="date" name="qc_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        <label>Job Order No:</label>
                        <select name="job_order_id" onchange="loadData()" class="form-control" id="jobOrderSelect"
                            required>
                            <option value="">Select Job Order</option>
                            @foreach($jobOrders as $jobOrder)
                                <option {{ json_encode($jobOrder->company_locations->pluck('id')->toArray()) }}
                                    value="{{ $jobOrder->id }}">{{ $jobOrder->job_order_no }}</option>
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
                <!-- <div class="col-md-6">
                    <div class="form-group">
                        <label>Mill:</label>
                        <input type="text" name="mill" class="form-control" placeholder="e.g., A-45" required>
                    </div>
                </div> -->
                <!-- <div class="col-md-6">
                    <div class="form-group">
                        <label>Commodities:</label>
                        <select name="commodities[]" class="form-control select2" multiple id="commoditiesSelect"
                            required>
                            <option value="">Select Commodities</option>
                            @foreach($products as $product)
                                <option value="{{ $product->id }}">{{ $product->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div> -->
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
    function loadData() {
        const jobOrderId = $('[name="job_order_id"]').val();
        const company_location_id = $('[name="company_location_id"]').val();

        if (jobOrderId) {
            fetchDynamicHTML('{{ route('get.job_order_details') }}', 'JobOrderDetail', {
                job_order_id: jobOrderId,
                company_location_id: company_location_id
            }, { method: 'POST' });
        }
             // Run on page load
      
    }

</script>
<script>


        function updateJobOrderQty() {
            var selected = $('#locationSelect').find(':selected');
            var totalKg = selected.data('totalkg');

            if (totalKg) {
                $('#jobOrderQty').val(totalKg + ' Kgs');
            } else {
                $('#jobOrderQty').val('');
            }
        }

        // Run on page load
        updateJobOrderQty();

        // Run on location change
        $('body').on('change', '#locationSelect', function () {
            updateJobOrderQty();
        });


</script>