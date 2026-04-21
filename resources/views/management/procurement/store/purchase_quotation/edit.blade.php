<style>
    html,
    body {
        overflow-x: hidden;
    }
</style>

<form style="overflow-x: hidden;" action="{{ route('store.purchase-quotation.update', optional($purchaseQuotation->purchase_request)->id) }}" method="POST"
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
                <table class="table table-bordered" id="purchaseRequestTable" style="min-width: 3000px;">
                    <thead>
                        <tr>
                            <th>Category</th>
                            <th>Status</th>
                            <th>Supplier</th>
                            <th>Item</th>
                            <th>Job Order</th>
                            <th>Qty</th>

                            <th>Rate</th>
                            <th>Total Amount</th>
                            <th>Item UOM</th>
                            <th>Delivery Date <span class="text-danger">*</span></th>
                            <th class="bag-only">Min Weight (gm)</th>
                            <th class="bag-only">Tolerance</th>
                            <th class="bag-only">Tolerance %</th>
                            <th class="bag-only">Brands</th>
                            <th class="bag-only">Color</th>
                            <th class="bag-only">Cons./sq. in.</th>
                            <th class="bag-only">Size</th>
                            <th class="bag-only">Stitching</th>
                            <th class="bag-only">Micron</th>
                            <th class="bag-only">Printing Sample</th>
                            <th>Remarks</th>
                            <th>Action</th>
                        </tr>
                    </thead>

                    <tbody id="purchaseRequestBody">

                        @foreach ($PurchaseQuotationData ?? [] as $key => $data)
                            <tr id="row_{{ $key }}">
                                <td style="min-width: 250px;">
                                    <select  id="category_id_{{ $key }}"
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

                                <td style="min-width: 150px; vertical-align: middle;">
                                    @php
                                        $status = $data->am_approval_status ?? 'pending';
                                        $badgeClass = match (strtolower($status)) {
                                            'approved' => 'badge-success',
                                            'rejected' => 'badge-danger',
                                            'reverted' => 'badge-info',
                                            'pending' => 'badge-warning',
                                            default => 'badge-secondary',
                                        };
                                    @endphp
                                    <span style="width: 100%;" class="badge {{ $badgeClass }}">{{ ucwords($status) }}</span>
                                </td>

                                 <td style="min-width: 300px;">
                                    <select  id="supplier_id_{{ $key }}"
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

                                <td style="min-width: 400px;">
                                    <select id="item_id_{{ $key }}" onchange="get_uom({{ $key }})" disabled
                                        class="form-control item-select select2" data-index="{{ $key }}">
                                        @foreach (get_product_by_id($data->item_id) as $item)
                                            <option data-uom="{{ $item->unitOfMeasure->name ?? '' }}" value="{{ $item->id }}"
                                                {{ $item->id == $data->item_id ? 'selected' : '' }}>
                                                {{ $item->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <input type="hidden" name="item_id[]" value="{{ $data->item_id }}">
                                </td>

                                <td style="min-width: 250px;">
                                    <select class="form-control select2" multiple disabled style="width: 100%">
                                        @foreach($data->purchase_request?->JobOrder ?? [] as $jo)
                                            <option selected>{{ $jo->job_order_data->job_order_no ?? '' }}</option>
                                        @endforeach
                                    </select>
                                </td>




                                    <td style="min-width: 150px;">
                                        @php
                                            $totalQuotedForThisItem = $all_quoted[$data->purchase_request_data_id] ?? 0;
                                            $quotedByOthers = $totalQuotedForThisItem - $data->qty;
                                            $remainingFromPR = ($data->purchase_request->qty ?? 0) - $quotedByOthers;
                                            $maxQty = max($remainingFromPR, $data->qty);
                                        @endphp
                                        <input  name="qty[{{ $data->id }}]" type="number"
                                            value="{{ $data->qty }}" id="qty_{{ $key }}"
                                            onkeyup="calc('{{ $key }}')"
                                            class="form-control" step="0.01" min="0"
                                            max="{{ $maxQty }}">
                                    </td>

                                    <td style="min-width: 150px;">
                                        <input  type="number" name="rate[{{ $data->id }}]"
                                            value="{{ $data->rate }}" id="rate_{{ $key }}"
                                            onkeyup="calc('{{ $key }}')"
                                            class="form-control" step="0.01" min="0">
                                    </td>

                                    <td style="min-width: 150px;">
                                        <input  type="number" value="{{ $data->total }}"
                                            id="total_{{ $key }}" class="form-control" step="0.01"
                                            min="0" name="total[]" readonly>
                                    </td>

                                <td style="min-width: 150px;">
                                    <input  type="text" value="{{ get_uom($data->item_id) }}"
                                        id="uom_{{ $key }}" class="form-control" readonly>
                                    <input type="hidden" name="uom[]" value="{{ get_uom($data->item_id) }}">
                                </td>
                                <td style="min-width: 180px;">
                                    <input type="date" name="delivery_date[{{ $data->id }}]" 
                                        value="{{ $data->delivery_date }}" id="delivery_date_{{ $key }}" 
                                        class="form-control" min="{{ date('Y-m-d') }}" required>
                                </td>

                                <td style="min-width: 200px;" class="bag-only">
                                    <input  type="number"
                                        value="{{ $data->purchase_request?->min_weight ?? null }}"
                                        id="min_weight_{{ $key }}" class="form-control" step="0.01"
                                        min="0" readonly>
                                    <input type="hidden" name="min_weight[]"
                                        value="{{ $data->purchase_request?->min_weight ?? null }}">
                                </td>
                                <td style="min-width: 150px;" class="bag-only">
                                    <input type="text"
                                        value="{{ $data->purchase_request?->tolerance ?? null }}"
                                        id="tolerance_{{ $key }}" class="form-control" readonly>
                                    <input type="hidden" name="tolerance[]"
                                        value="{{ $data->purchase_request?->tolerance ?? null }}">
                                </td>
                                <td style="min-width: 150px;" class="bag-only">
                                    <input type="text"
                                        value="{{ $data->purchase_request?->tolerance_percentage ?? null }}"
                                        id="tolerance_percentage_{{ $key }}" class="form-control" readonly>
                                    <input type="hidden" name="tolerance_percentage[]"
                                        value="{{ $data->purchase_request?->tolerance_percentage ?? null }}">
                                </td>
                                <td style="min-width: 200px;" class="bag-only">
                                    <input  type="text"
                                        value="{{ getBrandById($data->purchase_request?->brand_id ?? null)?->name ?? null }}"
                                        id="color_{{ $key }}" class="form-control" readonly>
                                    <input type="hidden" name="brand[]"
                                        value="{{ $data->purchase_request?->brand_id ?? null }}">
                                </td>
                                <td style="min-width: 200px;" class="bag-only">
                                    <input  type="text"
                                        value="{{ getColorById($data->purchase_request?->color ?? null)?->color ?? null }}"
                                        id="color_{{ $key }}" class="form-control" readonly>
                                    <input type="hidden" name="color[]"
                                        value="{{ $data->purchase_request?->color ?? null }}">
                                </td>

                                <td style="min-width: 200px;" class="bag-only">
                                    <input  type="number"
                                        value="{{ $data->purchase_request?->construction_per_square_inch ?? null }}"
                                        id="construction_{{ $key }}" class="form-control" step="0.01"
                                        min="0" readonly>
                                    <input type="hidden" name="construction_per_square_inch[]"
                                        value="{{ $data->purchase_request?->construction_per_square_inch ?? null }}">
                                </td>

                                <td style="min-width: 200px;" class="bag-only">
                                    <input  type="text"
                                        value="{{ $data->purchase_request?->size ?? null }}"
                                        id="size_{{ $key }}" class="form-control size-input-check" readonly>
                                    <input type="hidden" name="size[]"
                                        value="{{ $data->purchase_request?->size ?? null }}">
                                </td>

                                <td style="min-width: 200px;" class="bag-only">
                                    <select class="form-control select2" multiple disabled >
                                    @foreach(getStitchingsByIds($data?->purchase_request->stitching ?? "") as $stitching)
                                        <option value="{{ $stitching->id }}" selected>{{ $stitching->name }}</option>
                                    @endforeach
                                </select>
                                    <input type="hidden" name="stitch[]"
                                        value="{{ $data->purchase_request?->stitching ?? null }}">
                                </td>

                                <td style="min-width: 200px;" class="bag-only">
                                    <input  type="text"
                                        value="{{ $data->purchase_request?->micron ?? null }}"
                                        id="micron_{{ $key }}" class="form-control" readonly>
                                    <input type="hidden" name="stitch[]"
                                        value="{{ $data->purchase_request?->micron ?? null }}">
                                </td>

                                <td style="min-width: 200px;" class="bag-only">
                                    <input disabled type="file" class="form-control" accept="image/*,application/pdf" multiple>
                                    @if (!empty($data->purchase_request->printing_sample))
                                        @foreach((array)$data->purchase_request->printing_sample as $sample)
                                            <small class="d-block">
                                                <a href="{{ asset('storage/' . $sample) }}" target="_blank">
                                                    View file
                                                </a>
                                            </small>
                                        @endforeach
                                    @endif
                                </td>

                                <td style="min-width: 400px;">
                                    <input style="width: 100%" name="remarks[{{ $data->id }}]" type="text" value="{{ $data->remarks }}"
                                        id="remark_{{ $key }}" class="form-control">
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

        // ✅ Initialize visibility on load for Edit
        const initialCategoryId = "{{ $purchaseQuotation->purchase_request->category_id ?? 0 }}";
        if (initialCategoryId) {
            toggleVisibility(initialCategoryId);
        }
    });

    $(document).on('change', 'input[name*="delivery_date"]', function() {
        let quotationDate = $('#purchase_date').val();
        let deliveryDate = $(this).val();
        if (quotationDate && deliveryDate && deliveryDate < quotationDate) {
            Swal.fire({
                title: 'Invalid Date',
                text: 'Delivery date cannot be before quotation date.',
                icon: 'error',
                confirmButtonText: 'OK'
            });
            $(this).val(quotationDate);
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
                <td style="min-width: 250px;">
                    <select name="category_id[]" onchange="filter_items(this.value,${index})" id="category_id_${index}" class="form-control item-select select2" data-index="0">
                        <option value="">Select Category</option>
                        @foreach ($categories ?? [] as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </select>
                </td>
                <td style="min-width: 150px; vertical-align: middle; text-align: center;">
                    <span class="badge badge-warning">New</span>
                </td>
                <td style="min-width: 300px;">
                    <select name="supplier_id[]" id="supplier_id_${index}" onchange="get_uom(${index})" class="form-control item-select select2" data-index="0">
                        <option value="">Select Vendor</option>
                        @foreach (get_supplier() as $supplier)
                            <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                        @endforeach
                    </select>
                </td>
                <td style="min-width: 400px;">
                    <select name="item_id[]" id="item_id_${index}" onchange="get_uom(${index})" class="form-control item-select select2" data-index="0">
                        <option value="">Select Item</option>
                    
                        @foreach ($items ?? [] as $item)
                            <option value="{{ $item->id }}">{{ $item->name }}</option>
                        @endforeach
                    </select>
                    <input type="hidden" name="data_id[]" value="0">
                </td>
                <td style="min-width: 250px;">
                    <select class="form-control select2" multiple disabled style="width: 100%"></select>
                </td>


                <td style="min-width: 150px;"><input  onkeyup="calc(${index})" onblur="calc(${index})"  type="number" name="qty[]" id="qty_${index}" class="form-control" step="0.01" min="0"></td>
                <td style="min-width: 150px;"><input  onkeyup="calc(${index})" onblur="calc(${index})"  type="number" name="rate[]" id="rate_${index}" class="form-control" step="0.01" min="0"></td>
                <td style="min-width: 150px;"><input  type="number" readonly name="total[]" id="total_${index}" class="form-control" step="0.01" min="0"></td>
                <td style="min-width: 150px;"><input  type="text" name="uom[]" id="uom_${index}" class="form-control uom" readonly></td>
                <td style="min-width: 180px;"><input type="date" name="delivery_date[]" id="delivery_date_\${index}" class="form-control" min="{{ date('Y-m-d') }}" value="{{ date('Y-m-d') }}" required></td>
                
                <td class="bag-only" style="min-width: 200px;"></td>
                <td class="bag-only" style="min-width: 150px;"></td>
                <td class="bag-only" style="min-width: 150px;"></td>
                <td class="bag-only" style="min-width: 200px;"></td>
                <td class="bag-only" style="min-width: 200px;"></td>
                <td class="bag-only" style="min-width: 200px;"></td>
                <td class="bag-only" style="min-width: 200px;"></td>
                <td class="bag-only" style="min-width: 200px;"></td>
                <td class="bag-only" style="min-width: 200px;"></td>
                <td class="bag-only" style="min-width: 200px;"></td>
                
                <td><button type="button" class="btn btn-danger btn-sm removeRowBtn" onclick="remove(${index})">Remove</button></td>
            </tr>`;
        $('#purchaseRequestBody').append(row);
        
        let qDate = $('#purchase_date').val();
        if (qDate) {
            $('#delivery_date_' + (rowIndex-1)).attr('min', qDate);
        }
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

                // ✅ Toggle visibility based on category
                if (master && master.category_id) {
                    toggleVisibility(master.category_id);
                }

                let qDate = $('#purchase_date').val();
                if (qDate) {
                    $('input[name*="delivery_date"]').attr('min', qDate);
                }
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

    // ✅ Toggle visibility for Bag-specific columns
    function toggleVisibility(categoryId) {
        const bagCategoryIds = [11, 38]; // "Bags" category IDs are 11 and 38
        const isBag = bagCategoryIds.includes(parseInt(categoryId));

        if (isBag) {
            $('.bag-only').show();
        } else {
            $('.bag-only').hide();
        }
    }

    // Disable mousewheel on number inputs to prevent accidental changes and scroll issues
    $(document).on('wheel', 'input[type=number]', function (e) {
        $(this).blur();
    });

    $(document).on('input', '.size-input-check', function() {
        this.value = this.value.replace(/[^0-9.]/g, '');
        if ((this.value.match(/\./g) || []).length > 1) {
            this.value = this.value.replace(/\.+$/, "");
        }
    });

    $(document).on('select2:open', function (e) {
        // Remove all Select2 scroll blockers from window & parents
        $(document).off('scroll.select2');
        $(window).off('scroll.select2');
        $('*').off('scroll.select2');           // aggressive but often works
    });
</script>
