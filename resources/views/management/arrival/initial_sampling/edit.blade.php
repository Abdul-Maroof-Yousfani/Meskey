<form action="{{ route('initialsampling.store') }}" method="POST" id="ajaxSubmit" autocomplete="off">
    @csrf
    <input type="hidden" id="listRefresh" value="{{ route('get.ticket') }}" />
    <div class="row form-mar">
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Product:</label>
                <select name="arrival_sampling_request_id" id="arrival_sampling_request_id"
                    class="form-control select2">
                    <option value="">Select Ticket</option>
                    @foreach ($samplingRequests as $samplingRequest)
                        <option {{ $samplingRequest->id == $arrivalSamplingRequest->id ? 'selected' : '' }}
                            value="{{ $samplingRequest->id }}">
                            Ticket No: {{ optional($samplingRequest->arrivalTicket)->unique_no }} --
                            ITEM: {{ optional(optional($samplingRequest->arrivalTicket)->product)->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <div id="slabsContainer">
        <div class="row">
            <div class="col-12">
                <h6 class="header-heading-sepration">
                    QC Checklist
                </h6>
            </div>
        </div>
        <div class="striped-rows">
            @if (count($results) != 0)
                @foreach ($results as $slab)
                    <div class="form-group row">
                        <input type="hidden" name="product_slab_type_id[]" value="{{ $slab->slabType->id }}">
                        <label class="col-md-3 label-control font-weight-bold"
                            for="striped-form-1">{{ $slab->slabType->name }}</label>
                        <div class="col-md-9">
                            <input type="text" id="striped-form-1" class="form-control" name="checklist_value[]"
                                value="{{ $slab->checklist_value }}" placeholder="%">
                        </div>
                    </div>
                @endforeach
            @else
                <div class="alert alert-warning">
                    No Slabs Found
                </div>
            @endif
        </div>
        <div class="striped-rows">
            @if (count($compulsuryResults) != 0)
                @foreach ($compulsuryResults as $slab)
                    <div class="form-group row">
                        <input type="hidden" name="initial_compulsory_param_id[]" value="{{ $slab->qcParam->id }}">
                        <label class="col-md-3 label-control font-weight-bold"
                            for="striped-form-1">{{ $slab->qcParam->name }}</label>
                        <div class="col-md-9">
                            @if ($slab->qcParam->type == 'dropdown')
                                <input type="text" id="striped-form-1" readonly class="form-control"
                                    name="initial_compulsory_checklist_value[]"
                                    value="{{ $slab->compulsory_checklist_value }}" placeholder="%">
                            @else
                                <textarea type="text" id="striped-form-1" readonly class="form-control" name="initial_compulsory_checklist_value[]"
                                    placeholder="%"> {{ $slab->compulsory_checklist_value }}</textarea>
                            @endif
                        </div>
                    </div>
                @endforeach
            @else
                <div class="alert alert-warning">
                    No Compulsory Slabs Found
                </div>
            @endif
        </div>
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

    <div class="row ">
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group ">
                <label>Remarks (Optional):</label>
                <textarea name="remarks" row="4" class="form-control" placeholder="Description">{{ $arrivalSamplingRequest->remark }}</textarea>
            </div>
        </div>
    </div>


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
    });


    $(document).ready(function() {

        //initializeDynamicSelect2('#sampling_request_id', 'arrival_sampling_requests', 'name', 'id', false, false);
        //initializeDynamicSelect2('#supplier_name', 'suppliers', 'name', 'name', true, false);
        //initializeDynamicSelect2('#broker_name', 'brokers', 'name', 'name', true, false);
        //  function initializeDynamicSelect2(selector, tableName, columnName, idColumn = 'id', enableTags = false, isMultiple = true) {
        $('.select2').select2();
    });
</script>
