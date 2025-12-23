<style>
    html,
    body {
        overflow-x: hidden;
    }
</style>

<form action="{{ route('store.purchase-quotation.update', optional($purchaseQuotation->purchase_request)->id) }}" method="POST"
    id="ajaxSubmit" autocomplete="off">
    @csrf
    @method('PUT')
    <input type="hidden" id="listRefresh" value="{{ route('store.get.purchase-quotation') }}" />
    {{-- <input type="hidden" name="data_id" value="{{ $purchaseQuotation->id }}"> --}}
    {{-- <input type="hidden" name="purchase_request_data_id" value="{{ $purchaseQuotation->quotation_data()->purchase_request_data_id }}"> --}}
    <div class="row form-mar">
        <div class="col-md-3">
            <div class="form-group">
                <label>Purchase Request:</label>
                <select readonly class="form-control" name="purchase_request_id">
                    <option value="{{ optional($purchaseQuotation->purchase_request)->id }}">
                        {{ optional($purchaseQuotation->purchase_request)->purchase_request_no }}</option>
                </select>
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label>Location:</label>
                <select disabled name="company_location[]" id="company_location_id" class="form-control select2" multiple>
                    <option value="">Select Location</option>
                    @foreach (get_locations() as $value)
                        <option value="{{ $value->id }}" @selected(in_array($value->id, $locations_id))>{{ $value->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label>Quotation Date:</label>
                <input readonly type="date" id="purchase_date"
                    value="{{ optional($purchaseQuotation)->quotation_date }}" name="purchase_date"
                    class="form-control">
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label>Reference No:</label>
                <select readonly class="form-control" name="purchase_quotation_id">
                    <option value="{{ $purchaseQuotation->id }}">
                        {{ optional($purchaseQuotation)->purchase_quotation_no }}</option>
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
                            {{ $purchaseQuotation->supplier_id == $supplier->id ? 'selected' : '' }}>
                            {{ $supplier->name }}
                        </option>
                    @endforeach
                    <input type="hidden" name="supplier_id_master" value="{{ optional($purchaseQuotation)->supplier_id }}"
                        id="supplier_id">
                </select>
            </div>
        </div>

        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Description (Optional):</label>
                <textarea name="description" id="description" placeholder="Description" class="form-control">{{ optional($purchaseQuotation)->description }}</textarea>
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

            <div style="overflow-x: auto; white-space: nowrap; width: 100%;">
                <table class="table table-bordered" id="purchaseRequestTable">
                    <thead>
                        <tr>
                            <th class="col-sm-4">PQ No.</th>
                            <th class="col-sm-3">Supplier</th>
                            <th class="col-sm-3">Item</th>
                            <th class="col-sm-3">Item UOM</th>
                            <th class="col-sm-3">Min Weight</th>
                            <th class="col-sm-3">Brands</th>
                            <th class="col-sm-3">Color</th>
                            <th class="col-sm-3">Cons./sq. in.</th>
                            <th class="col-sm-3">Size</th>
                            <th class="col-sm-3">Stitching</th>
                            <th class="col-sm-3">Micron</th>
                            <th class="col-sm-3">Printing Sample</th>
                            <th>Qty</th>
                            <th>Rate</th>
                            <th>Total Amount</th>
                            <th>Remarks</th>
                            <th>Action</th>
                        </tr>
                    </thead>

                    <tbody id="purchaseRequestBody">

                        @foreach ($PurchaseQuotationData ?? [] as $key => $data)
                            <tr id="row_{{ $key }}">
                                <td style="width: 30%">
                                    <select style="width: 100px" id="category_id_{{ $key }}"
                                        onchange="filter_items(this.value,{{ $key }})"
                                        disabled
                                        class="form-control item-select select2" data-index="{{ $key }}">
                                        <option value="">Select Category</option>
                                        @foreach ($categories ?? [] as $category)
                                            <option {{ $category->id == $data->category_id ? 'selected' : '' }}
                                                value="{{ $category->id }}">{{ $category->name }}</option>
                                        @endforeach
                                    </select>
                                    <input type="hidden" name="category_id[]" value="{{ $data->category_id }}">
                                    <input type="hidden" name="data_id[]"
                                        value="{{ $data->purchase_request?->id ?? null }}">
                                    <input type="hidden" name="purchase_request_data_id[]"
                                        value="{{ $data->purchase_request_data_id }}">
                                </td>

                                <td style="width: 30%">
                                    <select style="width: 100px" id="supplier_id_{{ $key }}"
                                        name="supplier_id[]" class="form-control item-select select2"
                                        disabled
                                        data-index="{{ $key }}">
                                        <option value="">Select Vendor</option>
                                        @foreach (get_supplier() as $supplier)
                                            <option value="{{ $supplier->id }}" @selected($data->supplier_id == $supplier->id)>
                                                {{ $supplier->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </td>

                                <td style="width: 30%">
                                    <select style="width: 100px;" id="item_id_{{ $key }}"
                                        onchange="get_uom({{ $key }})"
                                        disabled
                                        class="form-control item-select select2" data-index="{{ $key }}">
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

                                <td style="width: 30%">
                                    <input style="width: 100px" type="text" value="{{ get_uom($data->item_id) }}"
                                        id="uom_{{ $key }}" class="form-control" readonly>
                                    <input type="hidden" name="uom[]" value="{{ get_uom($data->item_id) }}">
                                </td>

                                <td style="width: 30%">
                                    <input style="width: 100px" type="number"
                                        value="{{ $data->purchase_request?->min_weight ?? null }}"
                                        id="min_weight_{{ $key }}" class="form-control" step="0.01"
                                        min="0" readonly>
                                    <input type="hidden" name="min_weight[]"
                                        value="{{ $data->purchase_request?->min_weight ?? null }}">
                                </td>
                                <td style="width: 30%">
                                    <input style="width: 100px" type="text"
                                        value="{{ getBrandById($data->purchase_request?->brand_id ?? null)?->name ?? null }}"
                                        id="color_{{ $key }}" class="form-control" readonly>
                                    <input type="hidden" name="brand[]"
                                        value="{{ $data->purchase_request?->brand_id ?? null }}">
                                </td>
                                <td style="width: 30%">
                                    <input style="width: 100px" type="text"
                                        value="{{ getColorById($data->purchase_request?->color ?? null)?->color ?? null }}"
                                        id="color_{{ $key }}" class="form-control" readonly>
                                    <input type="hidden" name="color[]"
                                        value="{{ $data->purchase_request?->color ?? null }}">
                                </td>

                                <td style="width: 30%">
                                    <input style="width: 100px" type="number"
                                        value="{{ $data->purchase_request?->construction_per_square_inch ?? null }}"
                                        id="construction_{{ $key }}" class="form-control" step="0.01"
                                        min="0" readonly>
                                    <input type="hidden" name="construction_per_square_inch[]"
                                        value="{{ $data->purchase_request?->construction_per_square_inch ?? null }}">
                                </td>

                                <td style="width: 30%">
                                    <input style="width: 100px" type="text"
                                        value="{{ getSizeById($data->purchase_request?->size ?? null)?->size ?? null }}"
                                        id="size_{{ $key }}" class="form-control" readonly>
                                    <input type="hidden" name="size[]"
                                        value="{{ $data->purchase_request?->size ?? null }}">
                                </td>

                                <td style="width: 30%">
                                    <input style="width: 100px" type="text"
                                        value="{{ $data->purchase_request?->stitching ?? null }}"
                                        id="stitching_{{ $key }}" class="form-control" readonly>
                                    <input type="hidden" name="stitch[]"
                                        value="{{ $data->purchase_request?->stitching ?? null }}">
                                </td>

                                <td style="width: 30%">
                                    <input style="width: 100px" type="text"
                                        value="{{ $data->purchase_request?->micron ?? null }}"
                                        id="micron_{{ $key }}" class="form-control" readonly>
                                    <input type="hidden" name="stitch[]"
                                        value="{{ $data->purchase_request?->micron ?? null }}">
                                </td>

                                <td style="width:150px;">
                                    <input disabled type="file" name="printing_sample[]" id="printing_sample_{{ $key }}" class="form-control" accept="image/*,application/pdf">
                                    @if (!empty($data->purchase_request->printing_sample))
                                        <small>
                                            <a href="{{ asset('storage/' . $data->purchase_request->printing_sample) }}" target="_blank">
                                                View existing file
                                            </a>
                                        </small>
                                    @endif
                                </td>

                                <td style="width: 30%">
                                    <input style="width: 100px" name="qty[{{ $data->id }}]" type="number"
                                        value="{{ $data->qty }}" id="qty_{{ $key }}"
                                        onkeyup="calc('{{ $key }}')"
                                        class="form-control" step="0.01" min="0"
                                        max="{{ $data->qty }}">
                                </td>

                                <td style="width: 30%">
                                    <input style="width: 100px" type="number" name="rate[{{ $data->id }}]"
                                        value="{{ $data->rate }}" id="rate_{{ $key }}"
                                        onkeyup="calc('{{ $key }}')"
                                        class="form-control" step="0.01" min="0">
                                </td>

                                <td style="width: 30%">
                                    <input style="width: 100px" type="number" value="{{ $data->total }}"
                                        id="total_{{ $key }}" class="form-control" step="0.01"
                                        min="0" name="total[]" readonly>
                                </td>

                                <td style="width: 30%">
                                    <input style="width: 100px" name="remarks[{{ $data->id }}]" type="text" value="{{ $data->remarks }}"
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
    $(document).ready(function() {
        // Get the selected purchase request ID from the dropdown
        let purchaseQuotationId = $('select[name="purchase_quotation_id"]').val();

        // Call your function if an ID exists
        if (purchaseQuotationId) {
            //get_purchase(purchaseQuotationId);
        }
    });
    $('.select2').select2({
        placeholder: 'Please Select',
        width: '100%'
    });

    rowIndex = {{ $purchaseQuotationDataCount ?? 1 }};

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
            data: {
                category_id: category_id
            },
            dataType: 'json',
            success: function(response) {
                if (response.success && response.products) {
                    let $itemDropdown = $('#item_id_' + count);
                    $itemDropdown.empty();

                    // Default option
                    $itemDropdown.append('<option value="">Select an Item</option>');

                    $.each(response.products, function(index, product) {
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
                        $itemDropdown.html(
                            '<option value="">No valid items in this Purchase Request</option>');
                    }
                } else {
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

    allowedCategories = [];
    allowedItems = [];

    function get_purchase(purchaseQuotationId) {
        if (!purchaseQuotationId) return;

        $.ajax({
            url: "{{ route('store.purchase-quotation.get_quotation_item') }}",
            type: "GET",
            data: {
                id: purchaseQuotationId
            },
            beforeSend: function() {
                $('#purchaseRequestBody').html('<p>Loading...</p>');
            },
            success: function(response) {
                let html = response.html;
                let master = response.master;

                allowedCategories = response.allowed_categories || [];
                allowedItems = response.allowed_items || [];

                // Fill in master data
                $('#company_location_id').val(master.location_id);
                $('#location_id').val(master.location_id);
                $('#supplier_id').val(master.supplier_id);
                $('#vendor_id').val(master.supplier_id);
                $('#purchase_date').val(master.quotation_date);
                $('#description').val(master.description);

                console.log(response.locations_id)
                $('#company_location_id').val(response.locations_id).trigger('change');
                $('#vendor_id').val(master.supplier_id).trigger('change');

                // Load table HTML
                $('#purchaseRequestBody').html(html);

                // Reinitialize select2
                $('.select2').select2({
                    placeholder: 'Please Select',
                    width: '100%'
                });
            },
            error: function() {
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
        $('#total_' + num).val(parseFloat(total));
    }
</script>
