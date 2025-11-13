<div>
    @csrf
    @method('PUT')
    <input type="hidden" id="listRefresh" value="{{ route('store.get.purchase-request') }}" />

    <div class="row form-mar">
        <div class="col-md-4">
            <div class="form-group">
                <label class="form-label">Location:</label>
                <input type="text" name="company_location_display" disabled class="form-control"
                    id="company_location_id" readonly value="{{ $purchaseRequest->location->name }}">
                <input type="hidden" name="company_location_id" class="form-control" readonly
                    value="{{ $purchaseRequest->location_id }}">
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label class="form-label">Purchase Date:</label>
                <input type="date" name="purchase_date" disabled class="form-control" id="purchase_date" readonly
                    value="{{ $purchaseRequest->purchase_date }}">
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label class="form-label">Reference No:</label>
                <input type="text" name="reference_no" disabled value="{{ $purchaseRequest->reference_no }}"
                    id="reference_no" readonly class="form-control">
            </div>
        </div>

        <div class="col-xs-12 col-sm-12 col-md-12 mt-3">
            <div class="form-group">
                <label class="form-label">Description (Optional):</label>
                <textarea name="description" placeholder="Description" class="form-control" disabled rows="2">{{ $purchaseRequest->description }}</textarea>
            </div>
        </div>
    </div>

    <div class="row form-mar">
        <div class="col-12 text-right mb-2">
            <button type="button" style="float: right" class="btn btn-sm btn-primary" onclick="addRow()" disabled
                id="addRowBtn">
                <i class="fa fa-plus"></i> &nbsp; Add New Item
            </button>
        </div>

        <div class="col-md-12">
            <div class="table-responsive" style="overflow-x:auto; white-space:nowrap;">
    <table class="table table-bordered" id="purchaseRequestTable" style="min-width:2000px;">
        <thead>
            <tr>
                <th>Category</th>
                <th>Item</th>
                <th>Item UOM</th>
                <th>Qty</th>
                <th>Job Orders</th>
                <th>Min Weight</th>
                <th>Brands</th>
                <th>Color</th>
                <th>Cons./sq. in.</th>
                <th>Size</th>
                <th>Stitching</th>
                <th>Printing Sample</th>
                <th>Remarks</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody id="purchaseRequestBody">
            @foreach ($purchaseRequest->PurchaseData as $index => $item)
                <tr id="row_{{ $index }}">
                    <input type="hidden" name="item_row_id[]" value="{{ $item->id }}">

                    <td style="width:150px;">
                        <select name="category_id[]" id="category_id_{{ $index }}" disabled class="form-control item-select" data-index="{{ $index }}">
                            <option value="">Select Category</option>
                            @foreach ($categories ?? [] as $category)
                                <option value="{{ $category->id }}" {{ $item->category_id == $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                    </td>

                    <td style="width:150px;">
                        <select name="item_id[]" id="item_id_{{ $index }}" disabled class="form-control item-select" data-index="{{ $index }}">
                            <option value="">Select Item</option>
                            @if ($item->item)
                                <option value="{{ $item->item->id }}" selected>{{ $item->item->name }}</option>
                            @endif
                        </select>
                    </td>

                    <td style="width:120px;">
                        <input type="text" name="uom[]" id="uom_{{ $index }}" class="form-control" readonly value="{{ $item->item->unitOfMeasure->name ?? '' }}">
                    </td>

                    <td style="width:100px;">
                        <input type="number" name="qty[]" id="qty_{{ $index }}" disabled class="form-control" step="0.01" min="0" value="{{ $item->qty }}" placeholder="Qty">
                    </td>

                    <td style="width:180px;">
                        <select name="job_order_id[{{ $index }}][]" id="job_order_id_{{ $index }}" multiple disabled class="form-control item-select">
                            <option value="">Select Job Order</option>
                            @foreach ($job_orders ?? [] as $job_order)
                                <option value="{{ $job_order->id }}" 
                                    @foreach ($item->JobOrder as $assignedJobOrder)
                                        {{ $assignedJobOrder->job_order_id == $job_order->id ? 'selected' : '' }}
                                    @endforeach>
                                    {{ $job_order->name }}
                                </option>
                            @endforeach
                        </select>
                    </td>

                    <td style="width:120px;">
                        <input type="number" name="min_weight[]" id="min_weight_{{ $index }}" disabled class="form-control" step="0.01" min="0" value="{{ $item->min_weight }}" placeholder="Min Weight">
                    </td>
                    <td style="width:150px;">
                        <input type="text" name="brands[]" id="brands_{{ $index }}" disabled class="form-control" value="{{ getBrandById($item->brand_id)?->name ?? '' }}" placeholder="Color">
                    </td>
                    <td style="width:150px;">
                        <input type="text" name="color[]" id="color_{{ $index }}" disabled class="form-control" value="{{ getColorById($item->color)?->color ?? '' }}" placeholder="Color">
                    </td>

                    <td style="width:150px;">
                        <input type="text" name="construction_per_square_inch[]" id="construction_per_square_inch_{{ $index }}" disabled class="form-control" value="{{ $item->construction_per_square_inch }}" placeholder="Cons./sq. in.">
                    </td>

                    <td style="width:150px;">
                        <input type="text" name="size[]" id="size_{{ $index }}" disabled class="form-control" value="{{ getSizeById($item->size)?->size ?? '' }}" placeholder="Size">
                    </td>

                    <td style="width:120px;">
                        <input type="text" name="stitching[]" id="stitching_{{ $index }}" disabled class="form-control" value="{{ $item->stitching }}" placeholder="Stitching">
                    </td>

                    <td style="width:150px;">
                        <input type="file" name="printing_sample[]" id="printing_sample_{{ $index }}" disabled class="form-control" accept="image/*,application/pdf">
                        @if (!empty($item->printing_sample))
                            <small>
                                <a href="{{ asset('storage/' . $item->printing_sample) }}" target="_blank">
                                    View existing file
                                </a>
                            </small>
                        @endif
                    </td>

                    <td style="width:150px;">
                        <input type="text" name="remarks[]" id="remark_{{ $index }}" disabled class="form-control" value="{{ $item->remarks }}" placeholder="Remarks">
                    </td>

                    <td style="width:70px;">
                        <button type="button" class="btn btn-danger btn-sm removeRowBtn" disabled>
                            <i class="fa fa-trash"></i>
                        </button>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

        </div>
    </div>

    <input type="hidden" id="rowCount" value="{{ count($purchaseRequest->PurchaseData) }}">

    <div class="row">
        <div class="col-12">
            <x-approval-status :model="$data" />
        </div>
    </div>

    <div class="row bottom-button-bar">
        <div class="col-12 text-end">
            <a type="button"
                class="btn btn-danger modal-sidebar-close position-relative top-1 closebutton me-2">Close</a>
        </div>
    </div>
</div>

<script>
    purchaseRequestRowIndex = {{ count($purchaseRequest->PurchaseData) }};

    $(document).ready(function() {
        @foreach ($purchaseRequest->PurchaseData as $index => $item)
            $('#category_id_{{ $index }}').select2();
            $('#item_id_{{ $index }}').select2();
            $('#job_order_id_{{ $index }}').select2({
                placeholder: 'Please Select Job Order',
                width: '100%'
            });
            console.log({
                d: '#category_id_{{ $index }}'
            })
            @if ($item->category_id)
                filter_items({{ $item->category_id }}, {{ $index }}, {{ $item->item_id }});
            @endif
        @endforeach
    });

    function addRow() {
        let index = purchaseRequestRowIndex++;
        let row = `
            <tr id="row_${index}">
                <input type="hidden" name="item_row_id[]" value="">
                <td style="width: 25%">
                    <div class="loop-fields">
                        <div class="form-group mb-0">
                            <select name="category_id[]" id="category_id_${index}" onchange="filter_items(this.value,${index})" class="form-control item-select" data-index="${index}">
                                <option value="">Select Category</option>
                                @foreach ($categories ?? [] as $category)
                                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </td>
                <td style="width: 25%">
                    <div class="loop-fields">
                        <div class="form-group mb-0">
                            <select name="item_id[]" id="item_id_${index}" onchange="get_uom(${index})" class="form-control item-select" data-index="${index}">
                                <option value="">Select Item</option>
                            </select>
                        </div>
                    </div>
                </td>
                <td style="width: 10%">
                    <input type="text" name="uom[]" id="uom_${index}" class="form-control uom" readonly>
                </td>
                <td style="width: 10%">
                    <div class="loop-fields">
                        <div class="form-group mb-0">
                            <input type="number" name="qty[]" id="qty_${index}" class="form-control bg-white" step="0.01" min="0" placeholder="Qty">
                        </div>
                    </div>
                </td>
                {{-- <td style="width: 10%">
                    <div class="loop-fields">
                        <div class="form-group mb-0">
                            <input type="number" name="approved_qty[]" id="approved_${index}" class="form-control bg-white" step="0.01" min="0" placeholder="Approved Qty">
                        </div>
                    </div>
                </td> --}}
                <td style="width: 20%">
                    <div class="loop-fields">
                        <div class="form-group mb-0">
                            <select name="job_order_id[${index}][]" id="job_order_id_${index}" multiple class="form-control item-select" data-index="${index}">
                                <option value="">Select Job Order</option>
                                @foreach ($job_orders ?? [] as $job_order)
                                    <option value="{{ $job_order->id }}">{{ $job_order->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </td>
                <td style="width: 25%">
                    <input type="text" name="remarks[]" id="remark_${index}" class="form-control bg-white" placeholder="Remarks">
                </td>
                <td>
                    <button type="button" class="btn btn-danger btn-sm removeRowBtn" onclick="removeRow(${index})">
                        <i class="fa fa-trash"></i>
                    </button>
                </td>
            </tr>`;

        $('#purchaseRequestBody').append(row);

        $('#category_id_' + index).select2();
        $('#job_order_id_' + index).select2({
            placeholder: 'Please Select Job Order',
            width: '100%'
        });
    }

    function removeRow(index) {
        $('#row_' + index).remove();
    }

    function get_uom(index) {
        let uom = $('#item_id_' + index).find(':selected').data('uom');
        $('#uom_' + index).val(uom);
    }

    function filter_items(category_id, count, selectedItemId = null) {
        $.ajax({
            url: '{{ route('get.items') }}',
            type: 'GET',
            data: {
                category_id: category_id
            },
            dataType: 'json',
            success: function(response) {
                if (response.success && response.products) {
                    $('#item_id_' + count).empty();
                    $('#item_id_' + count).append('<option value="">Select a Item</option>');

                    $.each(response.products, function(index, product) {
                        let selected = (selectedItemId && product.id == selectedItemId) ?
                            'selected' : '';
                        $('#item_id_' + count).append(
                            `<option data-uom="${product.unit_of_measure?.name ?? ''}" value="${product.id}" ${selected}>${product.name}</option>`
                        );
                    });

                    $('#item_id_' + count).select2();

                    if (selectedItemId) {
                        let selectedOption = $('#item_id_' + count).find('option[value="' + selectedItemId +
                            '"]');
                        if (selectedOption.length) {
                            $('#uom_' + count).val(selectedOption.data('uom'));
                        }
                    }
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
</script>
