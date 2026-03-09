<style>
    html, body {
        overflow-x: hidden;
    }

    #purchaseRequestTable .select2-container {
        width: 100% !important;
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
                <label class="form-label">Category:</label>
                <select name="category_id_header" id="category_id_header" class="form-control select2" required>
                    <option value="">Select Category</option>
                    @foreach ($categories ?? [] as $category)
                        <option value="{{ $category->id }}" @selected($purchaseRequest->category_id == $category->id)>
                            {{ $category->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="col-md-4 header-conditional job-order-section" {!! $purchaseRequest->category_id == 38 ? '' : 'style="display: none;"' !!}>
            <div class="form-group">
                <label class="form-label">Job Orders:</label>
                <select class="form-control select2 job_orders" name="master_job_orders[]" id="job_orders" multiple data-placeholder="Select Job Orders" style="width: 100%;">
                    @foreach($job_orders as $job_order)
                        <option value="{{ $job_order->id }}" @selected(in_array($job_order->id, is_array(json_decode($purchaseRequest->job_orders)) ? json_decode($purchaseRequest->job_orders) : []))>{{ $job_order->job_order_no }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="col-md-4 header-conditional other-category-section" {!! $purchaseRequest->category_id != 38 ? '' : 'style="display: none;"' !!}>
            <div class="form-group">
                <label class="form-label">DEPARTMENT:</label>
                <select name="department_id" id="department_id" class="form-control select2" style="width: 100%;">
                    <option value="">Select Department</option>
                    @foreach ($departments ?? [] as $department)
                        <option value="{{ $department->id }}" @selected($purchaseRequest->department_id == $department->id)>
                            {{ $department->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="col-md-4 header-conditional other-category-section" {!! $purchaseRequest->category_id != 38 ? '' : 'style="display: none;"' !!}>
            <div class="form-group">
                <label class="form-label">REQUEST BY:</label>
                <select name="request_by_id" id="request_by_id" class="form-control select2" style="width: 100%;">
                    <option value="">Select Request By</option>
                    @foreach ($request_bies ?? [] as $request_by)
                        @if($purchaseRequest->department_id == $request_by->department_id)
                            <option value="{{ $request_by->id }}" @selected($purchaseRequest->request_by_id == $request_by->id)>
                                {{ $request_by->name }}
                            </option>
                        @endif
                    @endforeach
                </select>
            </div>
        </div>

        <div class="col-xs-12 col-sm-12 col-md-12 mt-3">
            <div class="form-group">
                <label class="form-label">Remarks (Optional):</label>
                <textarea name="description" placeholder="Remarks" class="form-control" rows="2">{{ $purchaseRequest->description }}</textarea>
            </div>
        </div>
    </div>

    <div class="row form-mar">
        <div class="col-12 text-right mb-2">
            <button type="button" style="float: right; " class="btn btn-sm btn-primary" onclick="addRow()"
                id="addRowBtn">
                <i class="fa fa-plus"></i> &nbsp; Add New Item
            </button>
        </div>

        <div class="col-md-12">
            <div class="table-responsive">
                <table class="table table-bordered" id="purchaseRequestTable" style="width: 100%; min-width: 100%;">
                    <thead>
            <tr>
                <th style="min-width: 250px;">Item</th>
                <th style="min-width: 100px;">Item UOM</th>
                <th style="min-width: 100px;">Qty</th>
                <th class="bag-only" style="min-width: 250px;">Job Orders</th>
                <th class="bag-only" style="min-width: 150px;">Brands</th>
                <th class="bag-only" style="min-width: 120px;">Min Weight</th>
                <th class="bag-only" style="min-width: 150px;">Color</th>
                <th class="bag-only" style="min-width: 150px;">Cons./sq. in.</th>
                <th class="bag-only" style="min-width: 150px;">Size</th>
                <th class="bag-only" style="min-width: 200px;">Stitching</th>
                <th class="bag-only" style="min-width: 120px;">Micron</th>
                <th class="bag-only" style="min-width: 250px;">Printing Sample</th>
                <th style="min-width: 200px;">line desc</th>
                <th style="min-width: 80px;">Action</th>
            </tr>
        </thead>
        <tbody id="purchaseRequestBody">
            @foreach ($purchaseRequest->PurchaseData as $loopIndex => $item)
            @php
                $rowId = $item->is_single_job_order == 1 ? "pre_" . $item->JobOrder->pluck("job_order_id")->toArray()[0] . "-" . $loopIndex : $loopIndex;
            @endphp
            <tr id="row_{{ $rowId }}" class="{{ $item->is_single_job_order ? 'jo-' . $item->JobOrder->pluck("job_order_id")->toArray()[0] : '' }}">
                <input type="hidden" name="item_row_id[]" value="{{ $item->id }}">

                <td style="min-width: 250px;">
                        <select id="item_id_{{ $rowId }}" name="item_id[]" onchange="get_uom('{{ $rowId }}')"
                            class="form-control item-select select2Dropdown" data-index="{{ $rowId }}" style="width: 100%;">
                            <option value="">Select Item</option>
                            @foreach($items as $product)
                                <option value="{{ $product->id }}" data-uom="{{ $product->unitOfMeasure->name }}" @selected($product->id == $item->item->id)>{{ $product->name }}</option>
                            @endforeach
                        </select>


                <input type="hidden" name="current_qty[]" value="{{ $item->qty }}" />
                <input type="hidden" name="module_type[]" value="{{ $item->module_type }}" />
                <input type="hidden" name="packing_id[]" value="{{ $item->packing_id }}" />
                    <input type="hidden" name="index[]" value="{{ $rowId }}" />
                    <input type="hidden" name="is_single_job_order[]" value="{{ $item->is_single_job_order }}" />
                </td>

                <td style="min-width: 100px;"><input type="text" name="uom[]" id="uom_{{ $rowId }}" class="form-control uom" readonly
                        value="{{ $item->item->unitOfMeasure->name ?? '' }}"></td>

                <td style="min-width: 100px;"><input type="number" name="qty[]" id="qty_{{ $rowId }}" class="form-control bg-white"
                        step="0.01" min="0" placeholder="Qty" value="{{ $item->qty }}"></td>

                
                <td class="bag-only" style="min-width: 250px;">
                    @if($item->is_single_job_order)
                        <input type="hidden" name="job_order_id[{{ $rowId }}][]" value="{{ $item->JobOrder->pluck("job_order_id")->toArray()[0] }}" />
                    @endif
                    <select name="job_order_id[{{ $rowId }}][]" id="job_order_id_{{ $rowId }}" multiple
                        class="form-control item-select select2" data-index="{{ $rowId }}" @disabled($item->is_single_job_order != 1)>
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

                 <td class="bag-only" style="min-width: 150px;">
                    <select id="brands_{{ $rowId }}" name="brands[]" class="form-control item-select color-select">
                        <option value="">Select Brand</option>
                        @foreach(getAllBrands() ?? [] as $brand)
                        <option @selected($brand->id == $item->brand_id) value="{{ $brand->id }}">
                            {{ $brand->name }}</option>
                        @endforeach
                    </select>
                </td>
               
                <td class="bag-only" style="min-width: 120px;"><input type="number" name="min_weight[]" id="min_weight_{{ $rowId }}" class="form-control"
                        step="0.01" min="0" value="{{ $item->min_weight }}" placeholder="Min Weight"></td>

           
                <td class="bag-only" style="min-width: 150px;">
                    <select id="color_{{ $rowId }}" name="color[]" class="form-control item-select color-select">
                        <option value="">Select Color</option>
                        @foreach(getAllColors() ?? [] as $color)
                        <option @selected($color->id == $item->color) value="{{ $color->id }}">
                            {{ $color->color }}</option>
                        @endforeach
                    </select>
                </td>

                <td class="bag-only" style="min-width: 150px;"><input type="text" name="construction_per_square_inch[]" id="construction_per_square_inch_{{ $rowId }}"
                        class="form-control" step="0.01" min="0" value="{{ $item->construction_per_square_inch }}"
                        placeholder="Cons./sq. in."></td>

                <td class="bag-only" style="min-width: 150px;">
                    <select id="size_{{ $rowId }}" name="size[]" class="form-control item-select size-select">
                        <option value="">Select Size</option>
                        @foreach(getAllSizes() ?? [] as $size)
                        <option @selected($size->id == $item->size) value="{{ $size->id }}">
                            {{ $size->size }}</option>
                        @endforeach
                    </select>
                </td>

                <td class="bag-only" style="min-width: 200px;">
                    @php
                        $selectedStitchings = $item->stitching ? array_filter(array_map('trim', explode(',', $item->stitching))) : [];
                    @endphp
                    <select id="stitching_{{ $rowId }}" name="stitching[{{ $rowId }}][]" class="form-control item-select stitching-select select2" multiple>
                        <option value="">Select Stitching</option>
                        @foreach(getAllStitchings() ?? [] as $stitching)
                            <option value="{{ $stitching->id }}" @selected(in_array($stitching->id, $selectedStitchings))>
                                {{ $stitching->name }}
                            </option>
                        @endforeach
                    </select>
                </td>


                <td class="bag-only" style="min-width: 120px;"><input type="text" name="micron[]" id="micron_{{ $rowId }}" class="form-control" 
                        min="0" value="{{ $item->micron }}" placeholder="Micron"></td>

                <td class="bag-only" style="min-width: 250px;">
                    <input type="file" name="printing_sample[]" id="printing_sample_{{ $rowId }}"
                        class="form-control" accept="image/*,application/pdf">
                    @if (!empty($item->printing_sample))
                    <small>
                        <a href="{{ asset('storage/' . $item->printing_sample) }}" target="_blank">View existing file</a>
                    </small>
                    @endif
                </td>

                <td style="min-width: 200px;"><input type="text" name="remarks[]" id="remark_{{ $rowId }}" class="form-control bg-white"
                        placeholder="line desc" value="{{ $item->remarks }}"></td>

                <td style="min-width: 80px;"><button type="button" class="btn btn-danger btn-sm removeRowBtn"
                        onclick="removeRow('{{ $rowId }}')"><i class="fa fa-trash"></i></button></td>
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
        @foreach ($purchaseRequest->PurchaseData as $loopIndex => $item)
            @php
                $jsIndex = $item->is_single_job_order == 1 ? "pre_" . $item->JobOrder->pluck("job_order_id")->toArray()[0] . "-" . $loopIndex : $loopIndex;
            @endphp
            $('#category_id_{{ $jsIndex }}').select2();
            $('#item_id_{{ $jsIndex }}').select2();

            $("#color_{{ $jsIndex }}").select2();
            $("#brands_{{ $jsIndex }}").select2();
            $("#size_{{ $jsIndex }}").select2();
            $("#stitching_{{ $jsIndex }}").select2();
            $('#job_order_id_{{ $jsIndex }}').select2({
                placeholder: 'Please Select Job Order',
                width: '100%'
            });
            @if ($item->category_id)
                filter_items({{ $item->category_id }}, '{{ $jsIndex }}', {{ $item->item_id }});
            @endif
        @endforeach
        toggleVisibility($('#category_id_header').val());
    });

    $('#category_id_header').on('change', function() {
        let category_id = $(this).val();
        // Clear items and job orders if category changes
        $("#purchaseRequestBody").empty();
        $(".job_orders").val(null).trigger('change');
        toggleVisibility(category_id);
    });

    $('#department_id').on('change', function() {
        let departmentId = $(this).val();
        let $requestBy = $('#request_by_id');
        $requestBy.empty();
        $requestBy.append('<option value="">Select Request By</option>');
        if (departmentId) {
            $.ajax({
                url: '/master/get-request-by-department/' + departmentId,
                type: 'GET',
                success: function (response) {
                    if (response.success) {
                        $.each(response.data, function(index, item) {
                            $requestBy.append('<option value="' + item.id + '">' + item.name + '</option>');
                        });
                    }
                },
                error: function (xhr, status, error) {
                    console.error('AJAX Error:', status, error);
                }
            });
        }
    });

    function toggleVisibility(categoryId) {
        $('.header-conditional').hide();
        if (categoryId == 38) { // 38 is "Bags"
            $('.bag-only').show();
            $('.job-order-section').show();
            $('#purchaseRequestTable').css('min-width', '2200px');
        } else {
            $('.bag-only').hide();
            if (categoryId) {
                $('.other-category-section').show();
            }
            $('#purchaseRequestTable').css('min-width', '100%');
        }
    }

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
        if (!id) return;
     
        $.ajax({
            url: '{{ route('store.get.jobOrdersDataForPurchaseRequest') }}',
            type: 'GET',
            data: {
                job_order: id,
                category_id: $('#category_id_header').val()
            },
            success: function (response) {
                if($('#category_id_header').val() == "") {
                    alert("Please select category first.");
                    $(".job_orders").val(null).trigger('change');
                    return;
                }
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
        $(`.jo-${id}`).remove();
    
    });


    function addRow() {
        if($('#category_id_header').val() == "") {
            alert("Please select category first.");
            return;
        }

        let index = `${purchaseRequestRowIndex++}0`;
        let row = `<tr id="row_${index}">
                    <td style="min-width: 250px;">
                        <div class="loop-fields">
                            <div class="form-group mb-0">
                                <select name="item_id[]" id="item_id_${index}"  onchange="get_uom(${index})"
                                    class="form-control item-select item-list" data-index="0" style="width: 100%;">
                                </select>
                                <input type="hidden" name="packing_id[]" value="" />
                                <input type="hidden" name="module_type[]" value="" />
                                <input type="hidden" name="index[]" value="${index}" />
         
                            </div>
                        </div>
                    </td>
                    <td style="min-width: 100px;">
                        <input type="text" name="uom[]" id="uom_${index}" class="form-control uom" readonly>
                    </td>
                    <td style="min-width: 100px;">
                        <div class="loop-fields">
                            <div class="form-group mb-0">
                                <input type="number" name="qty[]" id="qty_${index}" class="form-control" step="0.01"
                                    min="0" placeholder="Qty">
                            </div>
                        </div>
                    </td>
                    <td style="min-width: 250px;" class="bag-only">
                        <div class="loop-fields">
                            <div class="form-group mb-0">
                                <select name="job_order_id[${index}][]" id="job_order_id_${index}" multiple
                                    class="form-control item-select" data-index="0" disabled>
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
                    <td class="bag-only" style="min-width: 150px;"><select name="brands[]" id="brands_${index}" class="form-control item-select brand-select">
                        <option value="">Select Brand</option>
                        @foreach(getAllBrands() ?? [] as $brand)
                            <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                        @endforeach
                    </select></td>
                    <td style="min-width: 120px;" class="bag-only">
                        <div class="loop-fields">
                            <div class="form-group mb-0">
                                <input type="number" name="min_weight[]" id="min_weight_${index}" class="form-control"
                                    step="0.01" min="0" placeholder="Min Weight">
                            </div>
                        </div>
                    </td>
                    <td class="bag-only" style="min-width: 150px;"><select name="color[]" id="color_${index}" class="form-control item-select color-select">
                        <option value="">Select Color</option>
                        @foreach(getAllColors() ?? [] as $color)
                            <option value="{{ $color->id }}">{{ $color->color }}</option>
                        @endforeach
                    </select></td>
                    

                    <td class="bag-only" style="min-width: 200px;">
                        <div class="loop-fields">
                            <div class="form-group mb-0">
                                <input type="text" name="construction_per_square_inch[]"
                                    id="construction_per_square_inch_${index}" class="form-control" step="0.01" min="0"
                                    placeholder="Cons./sq. in.">
                            </div>
                        </div>
                    </td>
                    
                    <td class="bag-only" style="min-width: 150px;"><select name="size[]" id="size_${index}" class="form-control item-select size-select">
                        <option value="">Select Size</option>
                        @foreach(getAllSizes() ?? [] as $size)
                            <option value="{{ $size->id }}">{{ $size->size }}</option>
                        @endforeach
                    </select></td>

                   <td class="bag-only" style="min-width: 150px;">
                        <div class="loop-fields">
                            <div class="form-group mb-0">
                                <select name="stitching[${index}][]" id="stitching_${index}" class="form-control item-select stitching-select" style="width:100%;" multiple>
                                    <option value="">Select Stitching</option>
                                    @foreach(getAllStitchings() ?? [] as $stitching)
                                        <option value="{{ $stitching->id }}">{{ $stitching->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </td>
                    <td style="min-width: 120px;" class="bag-only">
                        <div class="loop-fields">
                            <div class="form-group mb-0">
                                <input type="text" name="micron[]" id="micron_${index}" class="form-control"
                                    step="0.01" min="0" placeholder="Micron">
                            </div>
                        </div>
                    </td>
                    <td style="min-width: 250px;" class="bag-only">
                        <div class="loop-fields">
                            <div class="form-group mb-0">
                                <input type="file" name="printing_sample[]" id="printing_sample_${index}"
                                    class="form-control" accept="image/*,application/pdf"
                                    placeholder="Printing Sample">
                            </div>
                        </div>
                    </td>
                    <td style="min-width: 200px;">
                        <input type="text" name="remarks[]" id="remark_${index}" class="form-control"
                            placeholder="line desc">
                    </td>
                    <td style="min-width: 80px;">
                        <button type="button" class="btn btn-danger btn-sm removeRowBtn" onclick="removeRow(${index})">
                            <i class="fa fa-trash"></i>
                        </button>
                    </td>
                </tr>`;

        $('#purchaseRequestBody').append(row);

        $('#color_' + index).select2();
        $('#size_' + index).select2();
        $('#brands_' + index).select2();
        $('#stitching_' + index).select2();
        $('#category_id_' + index).select2();
        $('#job_order_id_' + index).select2({
            placeholder: 'Please Select Job Order',
            width: '100%'
        });

        filter_items($('#category_id_header').val(), index);
        toggleVisibility($('#category_id_header').val());
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
