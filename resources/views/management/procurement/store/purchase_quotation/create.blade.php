<form action="{{ route('store.purchase-quotation.store') }}" method="POST" id="ajaxSubmit" autocomplete="off">
    @csrf
    <input type="hidden" id="listRefresh" value="{{ route('store.get.purchase-quotation') }}" />
    <div class="alert alert-danger" id="message" 
     style="display:none; position:fixed; top:20px; right:20px; z-index:9999; min-width:300px;">
    No Pending quantity to quote where quantity is zero
</div>
    <div class="row form-mar">
        <div class="col-md-3">
            <div class="form-group">
                <label>Purchase Request:</label>
                <select class="form-control select2" onchange="get_purchase(this.value)" name="purchase_request_id">
                    <option value="">Select Purchase Request</option>
                    @foreach ($approvedRequests ?? [] as $value)
                        <option value="{{ $value->id }}">
                            {{ $value->purchase_request_no }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="col-md-3">
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
        <div class="col-md-3">
            <div class="form-group">
                <label>Quatation Date:</label>
                <input type="date" id="purchase_date" name="purchase_date" class="form-control">
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label class="form-label">Reference No:</label>
                <input type="text" name="reference_no" placeholder="Please select location and date." readonly
                    id="reference_no" class="form-control">
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label class="form-label">Supplier:</label>
                <select id="supplier_id" name="supplier_id" class="form-control item-select select2">
                    <option value="">Select Vendor</option>
                    @foreach (get_supplier() as $supplier)
                        <option value="{{ $supplier->id }}">
                            {{ $supplier->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Description (Optional):</label>
                <textarea name="description" id="description" placeholder="Description" class="form-control"></textarea>
            </div>
        </div>
    </div>
    <div class="row form-mar">
        {{-- <div class="col-12 text-right mb-2">
            <button type="button" style="float: right" class="btn btn-sm btn-primary" onclick="addRow()" id="addRowBtn">
                <i class="fa fa-plus"></i>&nbsp; Add New Item
            </button>
        </div> --}}
        <div class="col-md-12">
            <table class="table table-bordered" id="purchaseRequestTable">
                <thead>
                    <tr>
                        <th>Category</th>
                        <th>Item</th>
                        <th>Item UOM</th>
                        <th class="col-sm-2">Min Weight</th>
                        <th class="col-sm-2">Color</th>
                        <th class="col-sm-2">Cons./sq. in.</th>
                        <th class="col-sm-2">Size</th>
                        <th class="col-sm-2">Stitching</th>
                        {{-- <th>Vendor</th> --}}
                        <th>Qty</th>
                        <th>Rate</th>
                        <th>Total Amount</th>
                        <th>Remarks</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="purchaseRequestBody"> </tbody>
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
    $(document).ready(function () {
        $('.select2').select2({
            placeholder: 'Please Select',
            width: '100%'
        });
        $(document).on('change', 'select[name="purchase_request_id"]', function () {
            const purchaseRequestId = $(this).val();
            if (purchaseRequestId) {
                get_purchase(purchaseRequestId);
            }
        });

        $(document).on('change', '#purchase_date', function () {
            fetchUniqueNumber();
        });

        $(document).on('change', '#supplier_id', function () {
            const purchaseRequestId = $('select[name="purchase_request_id"]').val();
            if (purchaseRequestId) {
                get_purchase(purchaseRequestId);
            }
        });
        function fetchUniqueNumber() {
            let locationId = $('#company_location_id').val();
            let contractDate = $('#purchase_date').val();

            if (locationId && contractDate) {
                let url = '/procurement/store/get-unique-number-quotation/' + locationId + '/' + contractDate;

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


    });





    window.rowIndex = 1;



    function addRow() {
        let index = rowIndex++;
        let categoryOptions = '<option value="">Select Category</option>';

        // If allowedCategories are set (after purchase selection)
        if (allowedCategories.length > 0) {
            // Filter existing categories by allowed IDs
            @foreach ($categories ?? [] as $category)
                if (allowedCategories.includes({{ $category->id }})) {
                    categoryOptions += `<option value="{{ $category->id }}">{{ $category->name }}</option>`;
                }
            @endforeach
    } else {
            // No purchase request selected → show all
            @foreach ($categories ?? [] as $category)
                categoryOptions += `<option value="{{ $category->id }}">{{ $category->name }}</option>`;
            @endforeach
    }
        let row = `
            <tr id="row_${index}">
                <td style="width: 25%">
                <select name="category_id[]" onchange="filter_items(this.value, ${index})"
                        id="category_id_${index}" class="form-control item-select select2">
                    ${categoryOptions}
                </select>
            </td>
            
                <td style="width: 25%">
                <select name="item_id[]" id="item_id_${index}" onchange="get_uom(${index})"
                        class="form-control item-select select2"></select>
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
        $('#purchaseRequestBody').append(row);
    }

    function remove(id) {
        $('#row_' + id).remove();
    }

    function filter_items(category_id, count) {
        $.ajax({
            url: '{{ route('get.items') }}',
            type: 'GET',
            data: { category_id: category_id },
            dataType: 'json',
            success: function (response) {
                if (response.success && response.products) {
                    let $itemDropdown = $('#item_id_' + count);
                    $itemDropdown.empty();

                    // Default option
                    $itemDropdown.append('<option value="">Select an Item</option>');

                    $.each(response.products, function (index, product) {
                        // ✅ Only include items that exist in the selected Purchase Request
                        if (allowedItems.length > 0 && !allowedItems.includes(product.id)) {
                            return; // skip items not in allowed list
                        }

                        $itemDropdown.append(
                            `<option data-uom="${product.unit_of_measure?.name ?? ''}" 
                                 value="${product.id}">
                                 ${product.name}
                         </option>`
                        );
                    });

                    // If no valid items remain
                    if ($itemDropdown.children('option').length === 1) {
                        $itemDropdown.html('<option value="">No valid items in this Purchase Request</option>');
                    }
                } else {
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

    // function get_purchase(purchaseRequestId) {
    //     if (!purchaseRequestId) return;
    //
    //     $.ajax({
    //        url: "{{ route('store.purchase-quotation.approve-item') }}",
    //          type: "GET",
    //          data: {
    //              id: purchaseRequestId
    //          },
    //          beforeSend: function() {
    //              $('#purchaseRequestBody').html('<p>Loading...</p>');
    //        },
    // success: function(response) {
    //              let html = response.html;
    //             let master = response.master;
    //
    //
    //             $('#company_location_id').val(master.location_id);
    //              $('#location_id').val(master.location_id);
    //              $('#purchase_date').val(master.purchase_date);
    //             // $('#reference_no').val(master.reference_no);
    //              $('#description').val(master.description);
    //             $('#company_location_id').val(master.location_id).trigger('change');
    //             $('#purchaseRequestBody').html('').html(html);
    //             $('.select2').select2({
    //                 placeholder: 'Please Select', // or 'resolve', '300px', etc.
    //                  width: '100%' // or 'resolve', '300px', etc.
    //             });
    //          },
    //          error: function() {
    //             $('#purchaseRequestBody').html('<p>Error loading data.</p>');
    //    //        }
    //     });
    // }
    // $('#supplier_id').on('change', function () {
    //    const purchaseRequestId = $('select[name="purchase_request_id"]').val();
    //    if (purchaseRequestId) {
    //        get_purchase(purchaseRequestId);
    //     }
    //  });
    let allowedCategories = [];
    let allowedItems = [];

    function get_purchase(purchaseRequestId) {
        if (!purchaseRequestId) return;
        const supplierId = $('#supplier_id').val();
        $.ajax({
            url: "{{ route('store.purchase-quotation.approve-item') }}",
            type: "GET",
            data: { id: purchaseRequestId, supplier_id: supplierId },
            beforeSend: function () {
                $('#purchaseRequestBody').html('<p>Loading...</p>');
            },
            success: function (response) {
                const items = response.allowed_items;
                const quantities = response.quantities;

                if (items.length != quantities.length) {
                    $("#message")
                        .stop(true, true)          // stop previous animations if running
                        .fadeIn(300)               // show smoothly
                        .delay(3000)               // keep visible for 3 seconds
                        .fadeOut(400);             // hide smoothly
                } else {
                    $("#message").fadeOut(200);    // hide quickly if already visible
                }


                console.log(response.allowed_items);
                let html = response.html;
                let master = response.master;
                let purchaseRequestDataCount = response.purchaseRequestDataCount ?? 1;
                allowedCategories = response.allowed_categories || [];
                allowedItems = response.allowed_items || [];

                // Fill in master data
                $('#company_location_id').val(master.location_id);
                $('#location_id').val(master.location_id);
                // $('#purchase_date').val(master.purchase_date);
                // $('#description').val(master.description);
                $('#company_location_id').val(master.location_id).trigger('change');

                // Load table HTML
                $('#purchaseRequestBody').html(html);
                window.rowIndex = purchaseRequestDataCount;
                // Reinitialize select2
                $('.select2').select2({
                    placeholder: 'Please Select',
                    width: '100%'
                });
            },
            error: function () {
                $('#purchaseRequestBody').html('<p>Error loading data.</p>');
            }
        });
    }


    function calc(num) {
        var qtyInput = $('#qty_' + num);
        var maxQty = parseFloat(qtyInput.attr('max'));
        var qty = parseFloat(qtyInput.val());
        var rate = parseFloat($('#rate_' + num).val());

        if (qty > maxQty) {
            alert('Maximum allowed quantity is ' + maxQty);
            qty = maxQty;
            qtyInput.val(maxQty);
        }

        var total = qty * rate;
        $('#total_' + num).val(total);
    }

</script>