<form action="{{ route('sales.first-weighbridge.update', $FirstWeighbridge->id) }}" method="POST" id="ajaxSubmit" autocomplete="off">
    @csrf
    @method('PUT')
    <input type="hidden" id="listRefresh" value="{{ route('sales.get.first-weighbridge') }}" />
    <div class="row form-mar">

        <div class="col-xs-12 col-sm-12 col-md-12">
            {!! getUserMissingInfoAlert() !!}
            <div class="form-group">
                <label>Ticket:</label>
                <select class="form-control select2" name="loading_program_item_id" id="loading_program_item_id">
                    <option value="">Select Ticket</option>
                    @php
                        $availableTickets = \App\Models\Sales\LoadingProgramItem::whereDoesntHave('firstWeighbridge')
                            ->orWhere('id', $FirstWeighbridge->loading_program_item_id)
                            ->with(['loadingProgram.deliveryOrder.customer', 'loadingProgram.deliveryOrder.delivery_order_data.item'])
                            ->get();
                    @endphp
                    @foreach ($availableTickets as $ticket)
                        <option value="{{ $ticket->id }}" {{ $ticket->id == $FirstWeighbridge->loading_program_item_id ? 'selected' : '' }}>
                            {{ $ticket->transaction_number }} -- {{ $ticket->truck_number }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>
    <div class="row" id="slabsContainer">
        @if($DeliveryOrder)
            @include('management.sales.first-weighbridge.getFirstWeighbridgeRelatedData', ['DeliveryOrder' => $DeliveryOrder, 'FirstWeighbridge' => $FirstWeighbridge, 'ArrivalTruckTypes' => $ArrivalTruckTypes, 'LoadingProgramItem' => $FirstWeighbridge->loadingProgramItem])
        @endif
    </div>

    <div class="row bottom-button-bar">
        <div class="col-12">
            <a type="button" class="btn btn-danger modal-sidebar-close position-relative top-1 closebutton">Close</a>
            <button type="submit" class="btn btn-primary submitbutton">Update</button>
        </div>
    </div>
</form>

<script>
    $(document).ready(function() {
        $('.select2').select2();
    });

    $(document).ready(function() {
        $('#loading_program_item_id').change(function() {
            var loading_program_item_id = $(this).val();

            if (loading_program_item_id) {
                $.ajax({
                    url: '{{ route('sales.getFirstWeighbridgeRelatedData') }}',
                    type: 'GET',
                    data: {
                        loading_program_item_id: loading_program_item_id
                    },
                    dataType: 'json',
                    beforeSend: function() {
                        Swal.fire({
                            title: "Processing...",
                            text: "Please wait while fetching ticket details.",
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
                            Swal.fire("No Data", "No delivery order details found.",
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
</script>
