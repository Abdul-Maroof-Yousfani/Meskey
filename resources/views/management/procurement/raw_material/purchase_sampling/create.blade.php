<form
    action="{{ route($isResampling ? 'raw-material.purchase-resampling.store' : 'raw-material.purchase-sampling.store') }}"
    method="POST" id="ajaxSubmit" autocomplete="off">
    @csrf
    <input type="hidden" id="listRefresh"
        value="{{ route($isResampling ? 'raw-material.get.purchase-resampling' : 'raw-material.get.purchase-sampling') }}" />

    <input type="hidden" value="{{ $PurchaseSamplingRequest->id }}" name="purchase_sampling_request_id" />
    @dd($samplingRequest, $samplingRequest->purchaseOrder ?? '1')
    <div class="row form-mar">
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>{{ $samplingRequest?->is_custom_qc == 'yes' ? 'Ticket' : 'Contract' }}:</label>
                <input type="text" readonly name="purchase_contract" placeholder="Sample Analysis By"
                    class="form-control" autocomplete="off"
                    value="{{ $samplingRequest->purchaseOrder->contract_no ?? ($samplingRequest->purchaseTicket->unique_no ?? 'N/A') }}" />
            </div>
        </div>
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>QC Product:</label>
                <input type="hidden" name="{{ $isResampling ? 'arrival_product_id' : 'arrival_product_id_display' }}"
                    id="product_id" value="{{ $samplingRequest?->qc_product_id }}" />

                <select @disabled($isResampling)
                    name="{{ $isResampling ? 'arrival_product_id_display' : 'arrival_product_id' }}"
                    id="{{ $isResampling ? 'arrival_product_id_display' : 'arrival_product_id' }}"
                    class="form-control select2">
                    <option value="">Select Product</option>
                    @foreach ($products as $product)
                        <option value="{{ $product->id }}" @selected($samplingRequest?->qc_product_id == $product->id)>
                            {{ $product->name ?? '' }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <div id="slabsContainer">
    </div>

    @if ($isResamplingReq)
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
                        <div class="form-group row slab-row" data-max-range="{{ $slab->max_range }}">
                            <input type="hidden" name="product_slab_type_id[]" value="{{ $slab->slabType->id }}">
                            <label class="col-md-3 label-control font-weight-bold"
                                for="slab-input-{{ $loop->index }}">
                                {{ $slab->slabType->name }}
                            </label>
                            <div class="col-md-9">
                                <div class="input-group">
                                    <input type="number" id="slab-input-{{ $loop->index }}"
                                        value="{{ $slab->checklist_value }}" class="form-control slab-input"
                                        data-max-range="{{ $slab->max_range }}" name="checklist_value[]"
                                        placeholder="%" min="0" step="0.01">
                                    <div class="input-group-append">
                                        <span class="input-group-text">%</span>
                                    </div>
                                </div>
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
                {{-- @dd($compulsuryResults) --}}
                @if (count($compulsuryResults) != 0)
                    @foreach ($compulsuryResults as $slab)
                        <div class="form-group row">
                            <input type="hidden" name="arrival_compulsory_qc_param_id[]"
                                value="{{ $slab->qcParam->id }}">

                            <label class="col-md-3 label-control font-weight-bold"
                                for="striped-form-1">{{ $slab->qcParam->name }}</label>
                            <div class="col-md-9">
                                {{-- @if ($slab->qcParam->type == 'dropdown')
                                    <input type="text" id="striped-form-1" readonly class="form-control"
                                        name="initial_compulsory_checklist_value[]"
                                        value="{{ $slab->compulsory_checklist_value }}" placeholder="%">
                                @else
                                    <textarea type="text" id="striped-form-1" readonly class="form-control" name="initial_compulsory_checklist_value[]"
                                        placeholder="%"> {{ $slab->compulsory_checklist_value }}</textarea>
                                @endif --}}
                                @if ($slab->qcParam->type == 'dropdown')
                                    <select name="compulsory_checklist_value[]" id="qc-param-{{ $loop->index }}"
                                        class="form-control qc-dropdown"
                                        data-default-value="{{ json_decode($slab->qcParam->options, true)[0] ?? '' }}">
                                        <option value="">Select Option</option>
                                        @foreach (json_decode($slab->qcParam->options, true) ?? [] as $key => $option)
                                            <option value="{{ $option }}" @selected($slab->compulsory_checklist_value == $option || $key == 0)>
                                                {{ $option }}</option>
                                        @endforeach
                                    </select>
                                @else
                                    <input type="text" id="qc-param-{{ $loop->index }}"
                                        class="form-control qc-input" value="{{ $slab->compulsory_checklist_value }}"
                                        name="compulsory_checklist_value[]" placeholder="Enter value">
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
    @endif

    <div class="row">
        <div class="col-12">
            <h6 class="header-heading-sepration">
                Other Details
            </h6>
        </div>
        <div class="col-12 px-3">
            <div class="form-group ">
                <label>Sample Taken By:</label>
                <select name="sample_taken_by" id="sample_taken_by" class="form-control select2">
                    <option value="">Sample Taken By</option>
                    @foreach ($sampleTakenByUsers as $sampleTakenUser)
                        <option value="{{ $sampleTakenUser->id }}" @selected($samplingRequest?->sample_taken_by == $sampleTakenUser->id)>
                            {{ $sampleTakenUser->name }}</option>
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
                <select name="party_ref_no" id="party_ref_no" class="form-control select2">
                    <option value="N/A">
                        N/A</option>
                </select>
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

        var slabInputs = document.querySelectorAll('.slab-input');
        var dropdownInputs = document.querySelectorAll('.qc-dropdown');
        var textInputs = document.querySelectorAll('.qc-input');

        slabInputs.forEach(input => {
            validateSlabInput(input);
            input.addEventListener('input', function() {
                validateSlabInput(this);
            });
            input.addEventListener('blur', function() {
                validateSlabInput(this);
            });
        });

        dropdownInputs.forEach(dropdown => {
            validateDropdown(dropdown);
            dropdown.addEventListener('change', function() {
                validateDropdown(this);
            });
        });

        textInputs.forEach(input => {
            validateInput(input);
            input.addEventListener('input', validateInput.bind(null, input));
            input.addEventListener('blur', validateInput.bind(null, input));
        });

        $('#arrival_product_id').change(function() {
            var samplingRequestId = $(this).val();

            if (samplingRequestId) {
                $.ajax({
                    url: '{{ route('getSlabsByProduct') }}',
                    type: 'GET',
                    data: {
                        product_id: samplingRequestId
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

        function loadSlabs(productId, ticketId, samplingRequestId) {
            if (productId && samplingRequestId) {
                $.ajax({
                    url: '{{ route('getSlabsByProduct') }}',
                    type: 'GET',
                    data: {
                        product_id: productId,
                        ticket_id: ticketId,
                        sampling_request_id: samplingRequestId,
                        isInner: true
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
                            $('#slabsContainer').html(response.html);
                        } else {
                            Swal.fire("No Data", "No slabs found for this product.", "info");
                            $('#slabsContainer').empty();
                        }
                    },
                    error: function() {
                        Swal.close();
                        Swal.fire("Error", "Something went wrong. Please try again.", "error");
                    }
                });
            }
        }

        $('.select2').select2();

        initializeDynamicSelect2('#party_ref_no', 'arrival_custom_sampling', 'party_ref_no', 'party_ref_no',
            true,
            false);
    });
</script>
