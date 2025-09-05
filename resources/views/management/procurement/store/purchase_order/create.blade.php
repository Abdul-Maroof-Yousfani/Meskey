<form action="{{ route('store.purchase-order.store') }}" method="POST" id="ajaxSubmit" autocomplete="off">
    @csrf
    <input type="hidden" id="listRefresh" value="{{ route('store.get.purchase-order') }}" />
    <div class="row form-mar">
        <div class="col-md-4">
            <div class="form-group">
                <label>Purchase Request:</label>
                <select class="form-control select2" onchange="get_purchase(this.value)" name="purchase_request_id">
                    <option value="">Select Purchase Request</option>
                    @foreach ($approvedRequests ?? [] as $value)
                        <option value="{{ $value->id }}">
                            {{ $value->purchase_request_no }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label>Location:</label>
                <select disabled name="company_location" id="company_location_id" class="form-control select2">
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
                <label>Purchase Date:</label>
                <input readonly type="date" id="purchase_date" name="purchase_date" class="form-control">
            </div>
        </div>
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Description (Optional):</label>
                <textarea readonly name="description" id="description" placeholder="Description" class="form-control"></textarea>
            </div>
        </div>
    </div>
    <div class="row form-mar">
        <div class="col-md-12">
            <table class="table table-bordered" id="purchaseRequestTable">
                <thead>
                    <tr>
                        <th></th>
                        <th>Category</th>
                        <th>Item</th>
                        <th>Item UOM</th>
                        <th>Vendor</th>
                        <th>Qty</th>
                        <th>Rate</th>
                        <th>Amount</th>
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

    let rowIndex = 1;

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
            success: function(response) {
                // Assuming response contains an array of categories
                if (response.success && response.products) {
                    // Clear existing options
                    $('#item_id_' + count).empty();

                    // Add default option
                    $('#item_id_' + count).append('<option value="">Select a Item</option>');

                    // Append new category options to the select element
                    $.each(response.products, function(index, product) {
                        $('#item_id_' + count).append(
                            `<option data-uom="${product.unit_of_measure?.name ?? ''}" value="${product.id}">${product.name}</option>`
                        );
                    });
                } else {
                    console.error('No products found or request failed');
                    $('#item_id_' + count).html('<option value="">No products available</option>');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', status, error);
                $('#item_id_' + count).html('<option value="">Error loading products</option>');
            }
        });
    }

    function get_uom(index) {
        let uom = $('#item_id_' + index).find(':selected').data('uom');
        $('#uom_' + index).val(uom);
    }

    function get_purchase(purchaseRequestId) {
        if (!purchaseRequestId) return;

        $.ajax({
            url: "{{ route('store.purchase-order.approve-item') }}",
            type: "GET",
            data: {
                id: purchaseRequestId
            },
            beforeSend: function() {
                $('#purchaseOrderBody').html('<p>Loading...</p>');
            },
            success: function(response) {
                let html = response.html;
                let master = response.master;
                console.log(master);


                $('#company_location_id').val(master.location_id);
                $('#location_id').val(master.location_id);
                $('#purchase_date').val(master.purchase_date);
                $('#reference_no').val(master.reference_no);
                $('#description').val(master.description);
                $('#company_location_id').val(master.location_id).trigger('change');
                $('#purchaseOrderBody').html('').html(html);
                $('.select2').select2({
                    placeholder: 'Please Select',
                    width: '100%'
                });
            },
            error: function() {
                $('#purchaseOrderBody').html('<p>Error loading data.</p>');
            }
        });
    }

    function calc(num) {
        var qty = parseFloat($('#qty_' + num).val());
        var rate = parseFloat($('#rate_' + num).val());

        var total = qty * rate;

        $('#total_' + num).val(total);

    }
</script>
