<form action="{{ route('store.purchase-order-receiving.store') }}" method="POST" id="ajaxSubmit" autocomplete="off">
    @csrf
    <input type="hidden" id="listRefresh" value="{{ route('store.get.purchase-order-receiving') }}" />
    <div class="row form-mar">
        <div class="col-md-4">
            <div class="form-group">
                <label>Purchase Order:</label>
                <select class="form-control select2" name="purchase_order_id" id="purchase_order_id">
                    <option value="">Select Purchase Order</option>
                    @foreach ($approvedPurchaseOrders ?? [] as $value)
                        <option value="{{ $value->id }}">
                            {{ $value->purchase_order_no }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label class="form-label">Supplier:</label>
                <input type="hidden" name="supplier_id" id="supplier_id">
                <input type="text" name="supplier_name" id="supplier_name" class="form-control" readonly>

            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label class="form-label">Purchase Request</label>
                <input type="hidden" name="purchase_request_id" id="purchase_request_id">
                <input type="text" name="purchase_request_no" id="purchase_request_no" class="form-control" readonly>

            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label>Receiving Date:</label>
                <input type="date" id="receiving_date" name="receiving_date" value="{{ now()->toDateString() }}" class="form-control" readonly>
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label class="form-label">Reference No:</label>
                <input type="text" name="reference_no" placeholder="Please select location and date." readonly
                    id="reference_no" class="form-control">
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label>Location:</label>
                <select disabled name="company_location[]" id="company_location_id" class="form-control select2" multiple>
                    <option value="">Select Location</option>
                    @foreach (get_locations() as $value)
                        <option value="{{ $value->id }}">{{ $value->name }}</option>
                    @endforeach
                    <input type="hidden" name="location_id" id="location_id">
                </select>
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label>Truck No:</label>
                <input type="text" name="truck_no" id="truck_no" class="form-control" placeholder="Truck No">
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label>DC No:</label>
                <input type="text" name="dc_no" id="dc_no" class="form-control" placeholder="DC NO">
            </div>
        </div>
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Description (Optional):</label>
                <textarea readonly name="description" id="description" placeholder="Description"
                    class="form-control"></textarea>
            </div>
        </div>
    </div>
    <div class="row form-mar">
        <div class="col-md-12">
            <table class="table table-bordered" id="purchaseRequestTable">
                <thead>
                    <tr>
                        <th>Category</th>
                        <th>Item</th>
                        <th>Item UOM</th>
                        <th>Qty</th>
                        <th>Receive Weight</th>
                        <th>Min Weight</th>
                        <th>Brands</th>
                        <th>Color</th>
                        <th>Cons./sq. in.</th>
                        <th>Size</th>
                        <th>Stitching</th>
                        <th>Micron</th>
                        <th>Printing Sample</th>
                        {{-- <th>Rate</th>
                        <th>Amount</th> --}}
                        <th>Remarks</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="purchaseOrderBody"></tbody>
            </table>
        </div>
    </div>
    <input type="hidden" id="rowCount" value="0">
    <div class="row bottom-button-bar">
        <div class="col-12">
            <a type="button" class="btn btn-danger modal-sidebar-close position-relative top-1 closebutton">Close</a>
            <button type="submit" class="btn btn-primary submitbutton">Save</button>
        </div>
    </div>
</form>




<script>
    $(".select2").select2();
    

    rowIndex = 1;

    function addRow() {
        let index = rowIndex++;
        let row = `
            <tr id="row_${index}">
                <td style="width: 25%">
                    <select name="category_id[]" onchange="filter_items(this.value,${index})" id="category_id_${index}" class="form-control item-select" data-index="0">
                        <option value="">Select Category</option>
                        @foreach ($categories ?? [] as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </select>
                </td>
                <td style="width: 25%">
                    <select name="item_id[]" id="item_id_${index}" onchange="get_uom(${index})" class="form-control item-select" data-index="0">
                        
                    </select>
                    <input type="hidden" name="data_id[]" value="0">
                </td>
                <td style="width: 15%"><input type="text" name="uom[]" id="uom_${index}" class="form-control uom" readonly></td>
                 <td style="width: 20%">
                    <select name="supplier_id[]" id="supplier_id_${index}" class="form-control item-select" data-index="0">
                        <option value="">Select Vendor</option>
                        @foreach (get_supplier() as $supplier)
                            <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                        @endforeach
                    </select>
                </td>
                <td style="width: 10%"><input  onkeyup="calc(${index})" onblur="calc(${index})" style="width: 100px" type="number" name="qty[]" id="qty_${index}" class="form-control" step="0.01" min="0"></td>
                <td style="width: 20%"><input  onkeyup="calc(${index})" onblur="calc(${index})" style="width: 100px" type="number" name="rate[]" id="rate_${index}" class="form-control" step="0.01" min="0"></td>
                <td style="width: 20%"><input style="width: 100px" type="number" readonly name="total[]" id="total_${index}" class="form-control" step="0.01" min="0"></td>
                <td style="width: 25%"><input style="width: 100px" type="text" name="remarks[]" id="remark_${index}" class="form-control"></td>
                
                <td><button type="button" class="btn btn-danger btn-sm removeRowBtn" onclick="remove(${index})">Remove</button></td>
            </tr>`;
        $('#purchaseOrderBody').append(row);
    }

    function remove(id) {
        $('#row_' + id).remove();
    }

    function filter_items(category_id, count) {
        $.ajax({
            url: '{{ route('get.items') }}', // Replace with your actual API endpoint
            type: 'GET',
            data: {
                category_id: category_id
            },
            dataType: 'json',
            success: function (response) {
                // Assuming response contains an array of categories
                if (response.success && response.products) {
                    // Clear existing options
                    $('#item_id_' + count).empty();

                    // Add default option
                    $('#item_id_' + count).append('<option value="">Select a Item</option>');

                    // Append new category options to the select element
                    $.each(response.products, function (index, product) {
                        $('#item_id_' + count).append(
                            `<option data-uom="${product.unit_of_measure?.name ?? ''}" value="${product.id}">${product.name}</option>`
                        );
                    });
                } else {
                    console.error('No products found or request failed');
                    $('#item_id_' + count).html('<option value="">No products available</option>');
                }
            },
            error: function (xhr, status, error) {
                console.error('AJAX Error:', status, error);
                $('#item_id_' + count).html('<option value="">Error loading products</option>');
            }
        });
    }

    function get_uom(index) {
        let uom = $('#item_id_' + index).find(':selected').data('uom');
        $('#uom_' + index).val(uom);
    }

    function fetchUniqueNumber() {
        let locationId = $('#location_id').val();
        let contractDate = $('#receiving_date').val();


        if (locationId && contractDate) {
            let url = '/procurement/store/get-unique-number-order-receiving/' + locationId + '/' + contractDate;

            $.ajax({
                url: url,
                type: 'GET',
                success: function (response) {
                    if (typeof response === 'string') {
                        $('#reference_no').val(response);
                    } else {
                        $('#reference_no').val('');
                    }
                },
                error: function (xhr, status, error) {
                    $('#reference_no').val('');
                }
            });
        } else {
            $('#reference_no').val('');
        }
    }
    
    // $('#purchase_order_id').on('change', function () {
    //     fetchUniqueNumber()
    //     $("#location_name").trigger("change");
    // });

    $('#location_name').on('change', function () {
        fetchUniqueNumber()
    });

    function get_purchase(purchaseOrderId = null) {
        const supplierId = $('#supplier_id').val();

        // If both are empty, don’t fetch
        if (!purchaseOrderId) return;

        // If supplier changed but no purchase request selected yet
        if (!purchaseOrderId) {
            purchaseOrderId = $('select[name="purchase_order_id"]').val();
        }

        $.ajax({
            url: "{{ route('store.purchase-order-receiving.approve-item') }}",
            type: "GET",
            data: {
                id: purchaseOrderId,
                supplier_id: supplierId
            },
            beforeSend: function () {
                $('#purchaseOrderBody').html('<p>Loading...</p>');
            },
            success: function (response) {
                let html = response.html;
                let master = response.master;

                $('#supplier_id').val(master.supplier?.id || '');
                $('#supplier_name').val(master.supplier?.name || '');
                $('#purchase_request_id').val(master.purchase_request_id || '');
                $('#purchase_request_no').val(master.purchase_request?.purchase_request_no || '');

                $('#location_id').val(master.location_id);
                $('#location_name').val(master.location?.name);
                $("#location_name").trigger("change");

                // $('#reference_no').val(master.reference_no);
                $('#description').val(master.description);
                $('#company_location_id').val(response.locations_id).trigger('change');
                fetchUniqueNumber();



                $('#purchaseOrderBody').html(html);

                $('.select2').select2({
                    placeholder: 'Please Select',
                    width: '100%'
                });
            },
            error: function () {
                $('#purchaseOrderBody').html('<p>Error loading data.</p>');
            }
        });
    }

    $('#supplier_id, select[name="purchase_order_id"]').on('change', function () {
        const purchaseOrderId = $('select[name="purchase_order_id"]').val();
        $('input[name="qty[]"], input[name="rate[]"], input[name="total[]"]').each(function () {
            $(this).val(''); // set to empty
        });
        get_purchase(purchaseOrderId);
    });


    function calc(num) {
        var qty = parseFloat($('#qty_' + num).val());
        var rate = parseFloat($('#rate_' + num).val());

        var total = qty * rate;

        $('#total_' + num).val(total);

    }
</script>