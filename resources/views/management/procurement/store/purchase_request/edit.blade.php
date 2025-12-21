<style>
    html, body {
        overflow-x: hidden;
    }
</style>
<form action="{{ route('store.purchase-request.update', $purchaseRequest->id) }}" method="POST" id="ajaxSubmit"
    autocomplete="off">
    @csrf
    @method('PUT')
    <input type="hidden" id="listRefresh" value="{{ route('store.get.purchase-request') }}" />
    <div class="row form-mar">
        <div class="col-md-4">
            <div class="form-group">
                <label class="form-label">Locations:</label>
                <select name="company_location_id[]" id="company_location_i" class="form-control select2" multiple readonly>
                    <option value="">Select Location</option>
                    @foreach(get_locations() as $loc)
                        <option value="{{ $loc->id }}" @selected(in_array($loc->id, $purchaseRequest->locations->pluck("location_id")->toArray()))>{{ $loc->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label class="form-label">Purchase Date:</label>
                <input type="date" name="purchase_date" class="form-control" id="purchase_date" readonly
                    value="{{ $purchaseRequest->purchase_date }}">
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label class="form-label">Reference No:</label>
                <input type="text" name="reference_no" value="{{ $purchaseRequest->reference_no }}" id="reference_no"
                    readonly class="form-control">
            </div>
        </div>

        <div class="col-md-4">
            <div class="form-group">
                <label class="form-label">Job Orders:</label>
                <select class="form-control select2 job_orders" name="master_job_orders[]" id="job_orders" multiple>
                    <option value="">Select Job Order</option>
                    @foreach($job_orders as $job_order)
                        <option value="{{ $job_order->id }}" @selected(in_array($job_order->id, json_decode($purchaseRequest->job_orders)))>{{ $job_order->job_order_no }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="col-xs-12 col-sm-12 col-md-12 mt-3">
            <div class="form-group">
                <label class="form-label">Description (Optional):</label>
                <textarea name="description" placeholder="Description" class="form-control" rows="2">{{ $purchaseRequest->description }}</textarea>
            </div>
        </div>
    </div>

    <div class="row form-mar">
        <div class="col-12 text-right mb-2">
            <button type="button" style="float: right" class="btn btn-sm btn-primary" onclick="addRow()"
                id="addRowBtn">
                <i class="fa fa-plus"></i> &nbsp; Add New Item
            </button>
        </div>

        <div class="col-md-12">
            <div class="table-responsive">
    <table class="table table-bordered" id="purchaseRequestTable">
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
                <th>Micron</th>
                <th>Printing Sample</th>
                <th>Remarks</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody id="purchaseRequestBody">
            @foreach ($purchaseRequest->PurchaseData as $index => $item)
            @php
                $index = $item->is_single_job_order == 1 ? "pre_" . $item->JobOrder->pluck("job_order_id")->toArray()[0] . "-" . $index : $index;
            @endphp
            <tr id="row_{{ $index }}" class="{{ $item->is_single_job_order ? 'jo-' . $item->JobOrder->pluck("job_order_id")->toArray()[0] : '' }}">
                <input type="hidden" name="item_row_id[]" value="{{ $item->id }}">

                <td>
                    <select name="category_id[]" id="category_id_{{ $index }}" onchange="filter_items(this.value,{{ $index }})"
                        class="form-control item-select select2" data-index="{{ $index }}" style="width:150px;">
                        <option value="">Select Category</option>
                        @foreach ($categories ?? [] as $category)
                        <option value="{{ $category->id }}" {{ $item->category_id == $category->id ? 'selected' : '' }}>
                            {{ $category->name }}</option>
                        @endforeach
                    </select>
                </td>

                <td>
                    <select name="item_id[]" id="item_id_{{ $index }}" onchange="get_uom({{ $index }})"
                        class="form-control item-select select2" data-index="{{ $index }}" style="width:150px;">
                        <option value="">Select Item</option>
                        @if ($item->item)
                        <option value="{{ $item->item->id }}" selected data-uom="{{ $item->item->unitOfMeasure->name ?? '' }}">
                            {{ $item->item->name }}</option>
                        @endif
                    </select>
                    <input type="hidden" name="index[]" value="{{ $index }}" />
                    <input type="hidden" name="is_single_job_order[]" value="{{ $item->is_single_job_order }}" />
                </td>

                <td><input type="text" name="uom[]" id="uom_{{ $index }}" class="form-control uom" readonly
                        value="{{ $item->item->unitOfMeasure->name ?? '' }}" style="width:100px;"></td>

                <td><input type="number" name="qty[]" id="qty_{{ $index }}" class="form-control bg-white"
                        step="0.01" min="0" placeholder="Qty" value="{{ $item->qty }}" style="width:100px;"></td>

                <td>
                    @if($item->is_single_job_order)
                        <input type="hidden" name="job_order_id[{{ $index }}][]" value="{{ $item->JobOrder->pluck("job_order_id")->toArray()[0] }}" />
                    @endif
                    <select name="job_order_id[{{ $index }}][]" id="job_order_id_{{ $index }}" multiple
                        class="form-control item-select select2" data-index="{{ $index }}"  style="width:180px;" @disabled($item->is_single_job_order)>
                        <option value="">Select Job Order</option>
                        @foreach ($job_orders ?? [] as $job_order)
                        <option value="{{ $job_order->id }}"
                            @foreach ($item->JobOrder as $assignedJobOrder)
                            {{ $assignedJobOrder->job_order_id == $job_order->id ? 'selected' : '' }}
                            @endforeach>
                            {{ $job_order->job_order_no }}</option>
                        @endforeach
                    </select>
                </td>

                <td><input type="number" name="min_weight[]" id="min_weight_0" class="form-control"
                        step="0.01" min="0" value="{{ $item->min_weight }}" placeholder="Min Weight" style="width:120px;"></td>
                <td>
                    <select name="brands[]" id="brands_{{ $index }}" class="form-control item-select color-select"
                        style="width:150px;">
                        <option value="">Select Brand</option>
                        @foreach(getAllBrands() ?? [] as $brand)
                        <option @selected($brand->id == getBrandById($item->brand_id)->id) value="{{ $brand->id }}">
                            {{ $brand->name }}</option>
                        @endforeach
                    </select>
                </td>
                <td>
                    <select name="color[]" id="color_{{ $index }}" class="form-control item-select color-select"
                        style="width:150px;">
                        <option value="">Select Color</option>
                        @foreach(getAllColors() ?? [] as $color)
                        <option @selected($color->id == getColorById($item->color)->id) value="{{ $color->id }}">
                            {{ $color->color }}</option>
                        @endforeach
                    </select>
                </td>

                <td><input type="text" name="construction_per_square_inch[]" id="construction_per_square_inch_0"
                        class="form-control" step="0.01" min="0" value="{{ $item->construction_per_square_inch }}"
                        placeholder="Cons./sq. in." style="width:120px;"></td>

                <td>
                    <select name="size[]" id="size_{{ $index }}" class="form-control item-select size-select"
                        style="width:150px;">
                        <option value="">Select Size</option>
                        @foreach(getAllSizes() ?? [] as $size)
                        <option @selected($size->id == getSizeById($item->size)->id) value="{{ $size->id }}">
                            {{ $size->size }}</option>
                        @endforeach
                    </select>
                </td>

                <td><input type="text" name="stitching[]" id="stitching_0" class="form-control" step="0.01"
                        min="0" value="{{ $item->stitching }}" placeholder="Stitching" style="width:120px;"></td>


                <td><input type="text" name="micron[]" id="micron_0" class="form-control" 
                        min="0" value="{{ $item->micron }}" placeholder="Micron" style="width:120px;"></td>

                <td>
                    <input type="file" name="printing_sample[]" id="printing_sample_{{ $loop->index }}"
                        class="form-control" accept="image/*,application/pdf" style="width:170px;">
                    @if (!empty($item->printing_sample))
                    <small>
                        <a href="{{ asset('storage/' . $item->printing_sample) }}" target="_blank">View existing file</a>
                    </small>
                    @endif
                </td>

                <td><input type="text" name="remarks[]" id="remark_{{ $index }}" class="form-control bg-white"
                        placeholder="Remarks" value="{{ $item->remarks }}" style="width:150px;"></td>

                <td><button type="button" class="btn btn-danger btn-sm removeRowBtn"
                        onclick="removeRow('{{ $index }}')"><i class="fa fa-trash"></i></button></td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

        </div>
    </div>

    <input type="hidden" id="rowCount" value="{{ count($purchaseRequest->PurchaseData) }}">

    <div class="row bottom-button-bar">
        <div class="col-12 text-end">
            <a type="button"
                class="btn btn-danger modal-sidebar-close position-relative top-1 closebutton me-2">Close</a>
            <button type="submit" class="btn btn-primary submitbutton">Save</button>
        </div>
    </div>
</form>

<script>

    var selectedLocations = @json($locations_id);
    var locationNames = @json($location_names);
    purchaseRequestRowIndex = {{ count($purchaseRequest->PurchaseData) }};

    $(document).ready(function() {
        $(".select2").select2();
        @foreach ($purchaseRequest->PurchaseData as $index => $item)
            $('#category_id_{{ $index }}').select2();
            $('#item_id_{{ $index }}').select2();

            $("#color_{{ $index }}").select2();
            $("#brands_{{ $index }}").select2();
            $("#size_{{ $index }}").select2();
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

    initializeDynamicSelect2('#company_location_id', 'company_locations', 'name', 'id', true, true);

    // WAIT a bit for AJAX load then set values
    setTimeout(() => {
        selectedLocations.forEach(function (id, index) {
            let option = new Option(locationNames[index], id, true, true);
            $('#company_location_id').append(option);
        });

        $('#company_location_id').trigger('change');
    }, 0);

    $('.job_orders').on('select2:select', function (e) {
        let id = e.params.data.id;
     
        $.ajax({
            url: '{{ route('store.get.jobOrdersDataForPurchaseRequest') }}',
            type: 'GET',
            data: {
                job_order: id,
            },
            success: function (response) {
                $("#purchaseRequestBody").append(response);
            },
            error: function (xhr, status, error) {
                console.log(error);
            }
        });
    });

    $('.job_orders').on('select2:unselect', function (e) {
        let id = e.params.data.id;
        // alert(id);
        // console.log($(`#row_${id}`));
        $(`#row_pre_${id}`).remove();
    });


    function addRow() {

        let index = `${purchaseRequestRowIndex++}0`;
        let row = `
            <tr id="row_${index}">
                <input type="hidden" name="item_row_id[]" value="">
                <td style="width: 10%">
                        <div class="loop-fields">
                            <div class="form-group mb-0">
                                <select name="category_id[]" id="category_id_${index}"
                                    onchange="filter_items(this.value,${index})" class="form-control item-select"
                                    data-index="0">
                                    <option value="">Select Category</option>
                                    @foreach ($categories ?? [] as $category)
                                        <option value="{{ $category->id }}">
                                            {{ $category->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </td>
                    <td style="width: 15%">
                        <div class="loop-fields">
                            <div class="form-group mb-0">
                                <select name="item_id[]" id="item_id_${index}" onchange="get_uom(${index})"
                                    class="form-control item-select" data-index="0">
                                    <option value="">Select Item</option>
                                </select>
                                <input type="hidden" name="index[]" value="${index}" />
                            </div>
                        </div>
                    </td>
                    <td style="width: 8%">
                        <input type="text" name="uom[]" id="uom_${index}" class="form-control uom" readonly>
                    </td>
                    <td style="width: 8%">
                        <div class="loop-fields">
                            <div class="form-group mb-0">
                                <input type="number" name="qty[]" id="qty_${index}" class="form-control" step="0.01"
                                    min="0" placeholder="Qty">
                            </div>
                        </div>
                    </td>
                    <td style="width: 8%">
                        <div class="loop-fields">
                            <div class="form-group mb-0">
                                <select name="job_order_id[${index}][]" id="job_order_id_${index}" multiple
                                    class="form-control item-select" data-index="0">
                                    <option value="">Select Job Order</option>
                                    @foreach ($job_orders ?? [] as $job_order)
                                        <option value="{{ $job_order->id }}">
                                            {{ $job_order->job_order_no }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </td>
                    <td style="width: 7%">
                        <div class="loop-fields">
                            <div class="form-group mb-0">
                                <input type="number" name="min_weight[]" id="min_weight_${index}" class="form-control"
                                    step="0.01" min="0" placeholder="Min Weight">
                            </div>
                        </div>
                    </td>

                <td>
                    <select name="brands[]" id="brands_${index}" class="form-control item-select color-select"
                        style="width:150px;">
                        <option value="">Select Brand</option>
                        @foreach(getAllBrands() ?? [] as $brand)
                        <option @selected($brand->id == getBrandById($item->brand_id)->id) value="{{ $brand->id }}">
                            {{ $brand->name }}</option>
                        @endforeach
                    </select>
                </td>

                    <td>
                        <select name="color[]" id="color_${index}" class="form-control item-select color-select"
                            style="width:150px;">
                            <option value="">Select Color</option>
                            @foreach(getAllColors() ?? [] as $color)
                            <option @selected($color->id == getColorById($item->color)->id) value="{{ $color->id }}">
                                {{ $color->color }}</option>
                            @endforeach
                        </select>
                    </td>
                    

                    <td style="width: 7%">
                        <div class="loop-fields">
                            <div class="form-group mb-0">
                                <input type="text" name="construction_per_square_inch[]"
                                    id="construction_per_square_inch_${index}" class="form-control" step="0.01" min="0"
                                    placeholder="Cons./sq. in.">
                            </div>
                        </div>
                    </td>
                    <td>
                    <select name="size[]" id="size_${index}" class="form-control item-select size-select"
                            style="width:150px;">
                            <option value="">Select Size</option>
                            @foreach(getAllSizes() ?? [] as $size)
                            <option @selected($size->id == getSizeById($item->size)->id) value="{{ $size->id }}">
                                {{ $size->size }}</option>
                            @endforeach
                        </select>
                    </td>
                    <td style="width: 6%">
                        <div class="loop-fields">
                            <div class="form-group mb-0">
                                <input type="text" name="stitching[]" id="stitching_${index}" class="form-control"
                                    step="0.01" min="0" placeholder="Stitching">
                            </div>
                        </div>
                    </td>

                <td><input type="text" name="micron[]" id="micron_${index}" class="form-control" placeholder="Micron" style="width:120px;"></td>
                    <td style="width: 8%">
                        <div class="loop-fields">
                            <div class="form-group mb-0">
                                <input type="file" name="printing_sample[]" id="printing_sample_${index}"
                                    class="form-control" accept="image/*,application/pdf"
                                    placeholder="Printing Sample">
                            </div>
                        </div>
                    </td>
                    <td style="width: 8%">
                        <input type="text" name="remarks[]" id="remark_${index}" class="form-control"
                            placeholder="Remarks">
                    </td>
                    <td>
                        <button type="button" class="btn btn-danger btn-sm removeRowBtn" onclick="removeRow('${index}')">
                            <i class="fa fa-trash"></i>
                        </button>
                    </td>
            </tr>`;

        $('#purchaseRequestBody').append(row);

        $('#color_' + index).select2();
        $('#size_' + index).select2();
        $('#brands_' + index).select2();
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
