<form action="{{ route('store.purchase-order.update', optional($purchaseOrder)->id) }}" method="POST"
    id="ajaxSubmit" autocomplete="off">
    @csrf
    @method('PUT')
    <input type="hidden" id="listRefresh" value="{{ route('store.get.purchase-order') }}" />
    {{-- <input type="hidden" name="data_id" value="{{ $purchaseOrder->id }}"> --}}
    {{-- <input type="hidden" name="purchase_request_id" value="{{ $purchaseOrder->purchase_request_id }}"> --}}
    <input type="hidden" name="purchase_order_id" value="{{ $purchaseOrder->id }}">
    <div class="row form-mar">
        <div class="col-md-3">
            <div class="form-group">
                <label>Purchase Request:</label>
                <select readonly class="form-control" name="purchase_request_id">
                    <option value="{{ optional($purchaseOrder->purchase_request)->id }}">
                        {{ optional($purchaseOrder->purchase_request)->purchase_request_no }}</option>
                </select>
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label>Location:</label>
                <select disabled name="company_location" id="company_location_id" class="form-control select2">
                    <option value="">Select Location</option>
                    @foreach (get_locations() as $loc)
                        <option {{ optional($purchaseOrder)->location_id == $loc->id ? 'selected' : '' }}
                            value="{{ $loc->id }}">{{ $loc->name }}</option>
                    @endforeach
                    <input type="hidden" name="location_id" value="{{ optional($purchaseOrder)->location_id }}"
                        id="location_id">
                </select>
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label>Order Date:</label>
                <input readonly type="date" id="purchase_date"
                    value="{{ optional($purchaseOrder)->order_date }}" name="purchase_date"
                    class="form-control">
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label>Reference No:</label>
                <select readonly class="form-control" name="purchase_order_id">
                    <option value="{{ $purchaseOrder->id }}">
                        {{ optional($purchaseOrder)->purchase_order_no }}</option>
                </select>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="form-group">
                <label class="form-label">Supplier:</label>
                <select disabled id="vendor_id" name="vendor_id" class="form-control item-select select2">
                    <option value="">Select Vendor</option>
                    @foreach (get_supplier() as $supplier)
                        <option value="{{ $supplier->id }}"
                        {{ $purchaseOrder->supplier_id == $supplier->id ? 'selected' : '' }}>
                            {{ $supplier->name }}
                        </option>
                    @endforeach
                    <input type="hidden" name="supplier_id" value="{{ optional($purchaseOrder)->supplier_id }}"
                        id="supplier_id">
                </select>
            </div>
        </div>
        
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Description (Optional):</label>
                <textarea readonly name="description" id="description" placeholder="Description" class="form-control">{{ optional($purchaseOrder)->description }}</textarea>
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
                        {{-- <th>Vendor</th> --}}
                        <th>Qty</th>
                        <th>Rate</th>
                        <th>Total Amount</th>
                        <th>Remarks</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="purchaseRequestBody">
                    @foreach ($purchaseOrder->purchaseOrderData ?? [] as $key => $data)
                        <tr id="row_{{ $key }}">
                            <td style="width: 25%">
                                <select id="category_id_{{ $key }}" disabled
                                    onchange="filter_items(this.value,{{ $key }})"
                                    class="form-control item-select select2" data-index="{{ $key }}">
                                    <option value="">Select Category</option>
                                    @foreach ($categories ?? [] as $category)
                                        <option {{ $category->id == $data->category_id ? 'selected' : '' }}
                                            value="{{ $category->id }}">
                                            {{ $category->name }}</option>
                                    @endforeach
                                </select>
                                <input type="hidden" name="category_id[]" value="{{ $data->category_id }}">
                                <input type="hidden" name="data_id[]" value="{{ $data->id }}">
                            </td>
                            <td style="width: 25%">
                                <select id="item_id_{{ $key }}" onchange="get_uom({{ $key }})"
                                    disabled class="form-control item-select select2" data-index="{{ $key }}">
                                    @foreach (get_product_by_category($data->category_id) as $item)
                                        <option data-uom="{{ $item->unitOfMeasure->name ?? '' }}"
                                            value="{{ $item->id }}"
                                            {{ $item->id == $data->item_id ? 'selected' : '' }}>
                                            {{ $item->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <input type="hidden" name="item_id[]" value="{{ $data->item_id }}">
                            </td>
                            <td style="width: 15%">
                                <input type="text" id="uom_{{ $key }}" class="form-control uom"
                                    value="{{ get_uom($data->item_id) }}" disabled readonly>
                                <input type="hidden" name="uom[]" value="{{ get_uom($data->item_id) }}">
                            </td>
                            {{-- <td style="width: 20%">
                                <select id="supplier_id_{{ $key }}" name="supplier_id[]"
                                    class="form-control item-select select2" data-index="{{ $key }}">
                                    <option value="">Select Vendor</option>
                                    @foreach (get_supplier() as $supplier)
                                        <option value="{{ $supplier->id }}" @selected($data->supplier_id == $supplier->id)>
                                            {{ $supplier->name }}</option>
                                    @endforeach
                                </select>
                            </td> --}}
                            <td style="width: 10%">
                                <input style="width: 100px" type="number" onkeyup="calc({{ $key }})"
                                    onblur="calc({{ $key }})" name="qty[]" value="{{ $data->qty }}"
                                    id="qty_{{ $key }}" class="form-control" step="0.01" min="0" max="{{ $data->qty }}"
                                    @if(isset($data->purchase_quotation_data_id)) readonly @endif>
                                {{-- <input type="hidden" name="qty[]" value="{{ $data->qty }}"> --}}
                            </td>
                            <td style="width: 20%">
                                <input style="width: 100px" type="number" onkeyup="calc({{ $key }})"
                                    onblur="calc({{ $key }})" name="rate[]" value="{{ $data->rate }}"
                                    id="rate_{{ $key }}" class="form-control" step="0.01"
                                    min="{{ $key }}"
                                    @if(isset($data->purchase_quotation_data_id)) readonly @endif>
                            </td>
                            <td style="width: 20%">
                                <input style="width: 100px" type="number" readonly value="{{ $data->total }}"
                                    id="total_{{ $key }}" class="form-control" step="0.01"
                                    min="0" readonly name="total[]">
                            </td>
                            <td style="width: 25%">
                                <input style="width: 100px" name="remarks[]" type="text" value="{{ $data->remarks }}"
                                    id="remark_{{ $key }}" class="form-control">
                                {{-- <input type="hidden" name="remarks[]" value="{{ $data->remarks }}"> --}}
                            </td>
                            <td>
                                <button type="button" class="btn btn-danger btn-sm removeRowBtn"
                                    onclick="remove({{ $key }})"
                                    data-id="{{ $key }}">Remove</button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
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
        // Get the selected purchase request ID from the dropdown
        let purchaseOrderId = $('select[name="purchase_order_id"]').val();

        // Call your function if an ID exists
       // if (purchaseOrderId) {
       //     get_purchase(purchaseOrderId);
      //  }
    });
    $('.select2').select2({
        placeholder: 'Please Select',
        width: '100%'
    });

    let rowIndex = {{ $purchaseOrderDataCount ?? 1 }};

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
                        <option value="">Select Item</option>
                    
                        @foreach ($items ?? [] as $item)
                            <option value="{{ $item->id }}">{{ $item->name }}</option>
                        @endforeach
                    </select>
                    <input type="hidden" name="data_id[]" value="0">
                </td>
                <td style="width: 15%"><input type="text" name="uom[]" id="uom_${index}" class="form-control uom" readonly></td>
                 <td style="width: 20%">
                    <select name="supplier_id[]" id="supplier_id_${index}" onchange="get_uom(${index})" class="form-control item-select" data-index="0">
                        <option value="">Select Vendor</option>
                        @foreach (get_supplier() as $supplier)
                            <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                        @endforeach
                    </select>
                </td>
                {{-- <td style="width: 10%"><input  onkeyup="calc(${index})" onblur="calc(${index})" style="width: 100px" type="number" name="qty[]" id="qty_${index}" class="form-control" step="0.01" min="0"></td> --}}
                <td style="width: 20%"><input  onkeyup="calc(${index})" onblur="calc(${index})" style="width: 100px" type="number" name="rate[]" id="rate_${index}" class="form-control" step="0.01" min="0"></td>
                {{-- <td style="width: 20%"><input style="width: 100px" type="number" readonly name="total[]" id="total_${index}" class="form-control" step="0.01" min="0"></td> --}}
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

    let allowedCategories = [];
    let allowedItems = [];

    function get_purchase(purchaseOrderId) {
        if (!purchaseOrderId) return;

        $.ajax({
            url: "{{ route('store.purchase-order.get_order_item') }}",
            type: "GET",
            data: { id: purchaseOrderId },
            beforeSend: function () {
                $('#purchaseRequestBody').html('<p>Loading...</p>');
            },
            success: function (response) {
                let html = response.html;
                let master = response.master;

                allowedCategories = response.allowed_categories || [];
                allowedItems = response.allowed_items || [];

                // Fill in master data
                $('#company_location_id').val(master.location_id);
                $('#location_id').val(master.location_id);
                $('#supplier_id').val(master.supplier_id);
                $('#vendor_id').val(master.supplier_id);
                $('#purchase_date').val(master.order_date);
                $('#description').val(master.description);
                $('#company_location_id').val(master.location_id).trigger('change');
                $('#vendor_id').val(master.supplier_id).trigger('change');

                // Load table HTML
                $('#purchaseRequestBody').html(html);

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
