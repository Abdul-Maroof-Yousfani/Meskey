<form action="{{ route('initialsampling.store') }}" method="POST" id="ajaxSubmit" autocomplete="off">
    @csrf
    <input type="hidden" id="listRefresh" value="{{ route('get.initialsampling') }}" />
    <div class="row form-mar">
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Product:</label>
                <select name="arrival_sampling_request_id" id="arrival_sampling_request_id"
                    class="form-control select2">
                    <option value="">Select Ticket</option>
                    @foreach ($samplingRequests as $samplingRequest)
                        <option value="{{ $samplingRequest->id }}">
                            Ticket No: {{ optional($samplingRequest->arrivalTicket)->unique_no }} --
                            ITEM: {{ optional(optional($samplingRequest->arrivalTicket)->product)->name }}
                            {{ $samplingRequest->is_re_sampling == 'yes' ? '- Resampling' : '' }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <div id="slabsContainer">
    </div>
    <div class="row">
        <div class="col-12">
            <h6 class="header-heading-sepration">
                Other Details
            </h6>
        </div>
        <div class="col-12 px-3">
            <div class="form-group ">
                <label>Sample Taken By:</label>
                <select name="sample_taken_by" id="sample_taken_by" class="form-control select2 ">
                    <option value="">Sample Taken By</option>

                    @foreach ($sampleTakenByUsers as $sampleTakenUser)
                        <option value="{{ $sampleTakenUser->id }}">{{ $sampleTakenUser->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="col-12 px-3">
            <div class="form-group ">
                <label>Sample Analysis By: </label>
                <input type="text" readonly name="sample_analysis_by" placeholder="Sample Analysis By"
                    class="form-control" autocomplete="off" value="{{ auth()->user()->name }}" />
            </div>
        </div>
        <div class="col-12 px-3">
            <div class="form-group ">
                <label>Party Ref. No: </label>
                <select name="party_ref_no" id="party_ref_no" class="form-control select2 "></select>
            </div>
        </div>
    </div>

    {{-- <div class="row ">
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group ">
                <label>QC Remarks (Optional):</label>
                <textarea name="remarks" row="4" class="form-control" placeholder="QC Remarks"></textarea>
            </div>
        </div>
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group ">
                <label>Unloading Instructions (Optional):</label>
                <textarea name="unloading_instruction" row="4" class="form-control" placeholder="Unloading Instructions"></textarea>
            </div>
        </div>
    </div> --}}

    <div class="row bottom-button-bar">
        <div class="col-12">
            <a type="button" class="btn btn-danger modal-sidebar-close position-relative top-1 closebutton">Close</a>
            <button type="submit" class="btn btn-primary submitbutton">Save</button>
        </div>
    </div>
</form>



<script>
    $(document).ready(function() {
        $('#arrival_sampling_request_id').change(function() {
            var samplingRequestId = $(this).val();

            if (samplingRequestId) {
                $.ajax({
                    url: '{{ route('getSlabsByProduct') }}',
                    type: 'GET',
                    data: {
                        sampling_request_id: samplingRequestId
                    },
                    dataType: 'json',
                    beforeSend: function() {
                        Swal.fire({
                            title: "Processing...",
                            text: "Please wait while fetching slabs.",
                            allowOutsideClick: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });
                    },
                    success: function(response) {
                        Swal.close();
                        if (response.success) {
                            // Append the rendered HTML to a container element
                            $('#slabsContainer').html(response.html);
                        } else {
                            Swal.fire("No Data", "No slabs found for this product.",
                                "info");
                        }
                    },
                    error: function() {
                        Swal.close();
                        Swal.fire("Error", "Something went wrong. Please try again.",
                            "error");
                    }
                });
            }
        });

        initializeDynamicSelect2('#party_ref_no', 'arrival_custom_sampling', 'party_ref_no', 'party_ref_no',
            true,
            false);
        //initializeDynamicSelect2('#sampling_request_id', 'arrival_sampling_requests', 'name', 'id', false, false);
        //initializeDynamicSelect2('#supplier_name', 'suppliers', 'name', 'name', true, false);
        //initializeDynamicSelect2('#broker_name', 'brokers', 'name', 'name', true, false);
        //  function initializeDynamicSelect2(selector, tableName, columnName, idColumn = 'id', enableTags = false, isMultiple = true) {
        // $('.select2').select2();
    });
</script>
