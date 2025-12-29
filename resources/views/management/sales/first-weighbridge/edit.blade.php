<form action="{{ route('sales.first-weighbridge.update', $FirstWeighbridge->id) }}" method="POST" id="ajaxSubmit" autocomplete="off">
    @csrf
    @method('PUT')
    <input type="hidden" id="listRefresh" value="{{ route('sales.get.first-weighbridge') }}" />
    <div class="row form-mar">

        <div class="col-xs-12 col-sm-12 col-md-12">
            {!! getUserMissingInfoAlert() !!}
            <div class="form-group">
                <label>Delivery Order:</label>
                <select class="form-control select2" name="delivery_order_id" id="delivery_order_id">
                    <option value="">Select Delivery Order</option>
                    @foreach ($DeliveryOrders as $deliveryOrder)
                        <option value="{{ $deliveryOrder->id }}" {{ $deliveryOrder->id == $FirstWeighbridge->delivery_order_id ? 'selected' : '' }}>
                            DO No: {{ $deliveryOrder->reference_no }} --
                            Customer: {{ $deliveryOrder->customer->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>
    <div class="row" id="slabsContainer">
        @if($DeliveryOrder)
            @include('management.sales.first-weighbridge.getFirstWeighbridgeRelatedData', ['DeliveryOrder' => $DeliveryOrder, 'FirstWeighbridge' => $FirstWeighbridge, 'ArrivalTruckTypes' => $ArrivalTruckTypes])
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
        $('#delivery_order_id').change(function() {
            var delivery_order_id = $(this).val();

            if (delivery_order_id) {
                $.ajax({
                    url: '{{ route('sales.getFirstWeighbridgeRelatedData') }}',
                    type: 'GET',
                    data: {
                        delivery_order_id: delivery_order_id
                    },
                    dataType: 'json',
                    beforeSend: function() {
                        Swal.fire({
                            title: "Processing...",
                            text: "Please wait while fetching delivery order details.",
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
