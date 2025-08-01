<form action="{{ route($isResampling ? 'initial-resampling.store' : 'initialsampling.store') }}" method="POST"
    id="ajaxSubmit" autocomplete="off">
    @csrf
    <input type="hidden" id="listRefresh"
        value="{{ route($isResampling ? 'get.initial-resampling' : 'get.initialsampling') }}" />

    <div class="row form-mar">
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Ticket:</label>
                <select name="arrival_sampling_request_id"
                    id="{{ $isResampling ? 'arrival_sampling_request_id' : 'arrival_sampling_request_id_display' }}"
                    class="form-control select2">
                    <option value="">Select Ticket</option>
                    @foreach ($samplingRequests as $samplingRequest)
                        <option value="{{ $samplingRequest->id }}"
                            data-product-id="{{ optional($samplingRequest->arrivalTicket)->qc_product }}"
                            data-ticket-id="{{ optional($samplingRequest->arrivalTicket)->id }}">
                            Ticket No: {{ $samplingRequest->arrivalTicket->unique_no }} 
                            {{-- Truck No: {{ $samplingRequest->arrivalTicket->truck_no ?? '-' }} --}}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="col-12">
            <div class="form-group ">
                <label>Party Ref. No: </label>
                <select name="party_ref_no" id="party_ref_no" class="form-control select2">
                    <option value="N/A">
                        N/A</option>
                </select>
            </div>
        </div>
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>QC Product:</label>
                <input type="hidden" name="{{ $isResampling ? 'arrival_product_id' : 'arrival_product_id_display' }}"
                    id="product_id" />

                <select @disabled($isResampling)
                    name="{{ $isResampling ? 'arrival_product_id_display' : 'arrival_product_id' }}"
                    id="{{ $isResampling ? 'arrival_product_id_display' : 'arrival_product_id' }}"
                    class="form-control select2">
                    <option value="">Select Product</option>
                    @foreach ($products as $product)
                        <option value="{{ $product->id }}">
                            {{ $product->name ?? '' }}
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
                <select name="sample_taken_by" id="sample_taken_by" class="form-control select2">
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

        $('#arrival_sampling_request_id').change(function() {
            var selectedTicket = $(this).find(':selected');
            var productId = selectedTicket.data('product-id');
            var ticketId = selectedTicket.data('ticket-id');
            var samplingRequestId = $(this).val();

            if (samplingRequestId) {
                $('#arrival_product_id_display').val(productId).trigger('change');
                $('#product_id').val(productId);

                loadSlabs(productId, ticketId, samplingRequestId);
            } else {
                $('#arrival_product_id_display').val('').trigger('change');
                $('#product_id').val('');
                $('#slabsContainer').empty();
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
