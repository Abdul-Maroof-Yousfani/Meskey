<form action="{{ route('inner-sampling.store') }}" method="POST" id="ajaxSubmit" autocomplete="off">
    @csrf
    <input type="hidden" id="listRefresh" value="{{ route('get.inner-sampling') }}" />
    <div class="row form-mar">
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Ticket:</label>
                <select name="arrival_sampling_request_id" id="arrival_sampling_request_id"
                    class="form-control select2">
                    <option value="">Select Ticket</option>
                    @foreach ($samplingRequests as $samplingRequest)
                        <option value="{{ $samplingRequest->id }}"
                            data-product-id="{{ optional($samplingRequest->arrivalTicket)->qc_product }}"
                            data-ticket-id="{{ optional($samplingRequest->arrivalTicket)->id }}">
                            {{ optional($samplingRequest->arrivalTicket)->unique_no }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <input type="hidden" name="arrival_product_id" id="arrival_product_id">
                <label>Product:</label>
                <select name="arrival_product_id_display" id="arrival_product_id_display" class="form-control select2"
                    disabled readonly>
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

    <div class="row ">
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group ">
                <label>Remarks (Optional):</label>
                <textarea name="remarks" row="4" class="form-control" placeholder="Description"></textarea>
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
        $('.select2').select2();

        $('#arrival_sampling_request_id').change(function() {
            var selectedTicket = $(this).find(':selected');
            var productId = selectedTicket.data('product-id');
            var ticketId = selectedTicket.data('ticket-id');
            var samplingRequestId = $(this).val();

            if (samplingRequestId) {
                $('#arrival_product_id_display').val(productId).trigger('change');
                $('#arrival_product_id').val(productId);

                loadSlabs(productId, ticketId, samplingRequestId);
            } else {
                $('#arrival_product_id_display').val('');
                $('#arrival_product_id').val('');
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
    });
</script>
