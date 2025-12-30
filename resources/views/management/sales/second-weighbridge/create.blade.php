<form action="{{ route('sales.second-weighbridge.store') }}" method="POST" id="ajaxSubmit" autocomplete="off">
    @csrf
    <input type="hidden" id="listRefresh" value="{{ route('sales.get.second-weighbridge') }}" />
    <div class="row form-mar">

        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group">
                <label>Sale Order:</label>
                <select class="form-control select2" name="sale_order_id" id="sale_order_id">
                    <option value="">Select Sale Order</option>
                    @foreach ($SaleOrders as $SaleOrder)
                        <option value="{{ $SaleOrder->id }}">
                            {{ $SaleOrder->reference_no }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group">
                <label>Delivery Order:</label>
                <select class="form-control select2" name="delivery_order_id" id="delivery_order_id">
                    <option value="">Select Delivery Order</option>
                    @foreach ($DeliveryOrders as $deliveryOrder)
                        <option value="{{ $deliveryOrder->id }}">
                            {{ $deliveryOrder->reference_no }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>
    <div class="row" id="slabsContainer">
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
    });

    $(document).ready(function() {
        // Handle sale order change
        $('#sale_order_id').change(function() {
            var sale_order_id = $(this).val();

            if (sale_order_id) {
                $.ajax({
                    url: '{{ route('sales.getDeliveryOrdersBySaleOrderSecond') }}',
                    type: 'GET',
                    data: {
                        sale_order_id: sale_order_id
                    },
                    dataType: 'json',
                    beforeSend: function() {
                        Swal.fire({
                            title: "Processing...",
                            text: "Please wait while fetching delivery orders.",
                            allowOutsideClick: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });
                    },
                    success: function(response) {
                        Swal.close();
                        if (response.success) {
                            // Clear existing delivery orders and slabs container
                            $('#delivery_order_id').empty().append('<option value="">Select Delivery Order</option>');
                            $('#slabsContainer').html('');

                            // Populate delivery orders
                            $.each(response.delivery_orders, function(index, deliveryOrder) {
                                $('#delivery_order_id').append('<option value="' + deliveryOrder.id + '">' + deliveryOrder.reference_no + '</option>');
                            });
                        } else {
                            Swal.fire("No Data", "No delivery orders found for selected sale order.",
                                "info");
                        }
                    },
                    error: function() {
                        Swal.close();
                        Swal.fire("Error", "Something went wrong. Please try again.",
                            "error");
                    }
                });
            } else {
                // Clear delivery orders and slabs container if no sale order selected
                $('#delivery_order_id').empty().append('<option value="">Select Delivery Order</option>');
                $('#slabsContainer').html('');
            }
        });

        // Handle delivery order change
        $('#delivery_order_id').change(function() {
            var delivery_order_id = $(this).val();

            if (delivery_order_id) {
                $.ajax({
                    url: '{{ route('sales.getSecondWeighbridgeRelatedData') }}',
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
            } else {
                // Clear slabs container if no delivery order selected
                $('#slabsContainer').html('');
            }
        });
    });
</script>
