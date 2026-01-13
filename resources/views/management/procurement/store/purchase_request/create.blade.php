<style>
    html, body {
        overflow-x: hidden;
    }
</style>
<form action="{{ route('store.purchase-request.store') }}" method="POST" id="ajaxSubmit" autocomplete="off">
    @csrf
    <input type="hidden" id="listRefresh" value="{{ route('store.get.purchase-request') }}" />

    <div class="row form-mar">
        <div class="col-md-4">
            <div class="form-group">
                <label class="form-label">Locations:</label>
                <select name="company_location_id[]" id="company_location_id" class="form-control">
                </select>
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label class="form-label">Purchase Date:</label>
                <input type="date" name="purchase_date" class="form-control" id="purchase_date">
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
                <label class="form-label">Job Orders:</label>
                <select class="form-control select2 job_orders" name="master_job_orders[]" multiple>
                    <option value="">Select Job Order</option>
                    @foreach($job_orders as $job_order)
                        <option value="{{ $job_order->id }}">{{ $job_order->job_order_no }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="col-xs-12 col-sm-12 col-md-12 mt-3">
            <div class="form-group">
                <label class="form-label">Description (Optional):</label>
                <textarea name="description" placeholder="Description" class="form-control" rows="2"></textarea>
            </div>
        </div>
    </div>

    <div class="row form-mar">
        <div class="col-12 text-right mb-2">
            <button type="button" style="float: right" class="btn btn-sm btn-primary" onclick="addRow()" id="addRowBtn">
                <i class="fa fa-plus"></i>&nbsp; Add New Item
            </button>
        </div>

        <div class="col-md-12">
   <div style="overflow-x: auto; width: 100%;">
    <table class="table table-bordered" id="purchaseRequestTable" style="min-width:2000px; width:100%;">
        <thead>
            <tr>
                <th>Category</th>
                <th>Item</th>
                <th>Item UOM</th>
                <th>Qty</th>
                <th>Job Orders</th>
                <th>Brands</th>
                <th>Min Weight</th>
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
        </tbody>
    </table>
</div>

</div>


        </div>
    </div>

    <input type="hidden" id="rowCount" value="0">

    <div class="row bottom-button-bar">
        <div class="col-12 text-end">
            <a type="button"
                class="btn btn-danger modal-sidebar-close position-relative top-1 closebutton me-2">Close</a>
            <button type="submit" class="btn btn-primary submitbutton">Save</button>
        </div>
    </div>
</form>

<script>
    $(document).ready(function () {
        $('#category_id_0').select2();
        $(".color-select").select2();
        $(".brand-select").select2();
        $(".size-select").select2();
        $(".stitching-select").select2();
        $(".select2").select2();
        $('#job_order_id_0').select2({
            placeholder: 'Please Select Job Order',
            width: '100%'
        });

        initializeDynamicSelect2('#company_location_id', 'company_locations', 'name', 'id', true, true);

        
        function fetchUniqueNumber() {
            let locationId = $('#company_location_id').val();
            let contractDate = $('#purchase_date').val();

            if (locationId && contractDate) {
                let url = '/procurement/store/get-unique-number/' + locationId + '/' + contractDate;
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

        $('#company_location_id, #purchase_date').on('change', fetchUniqueNumber);
    });

    $(".job_orders").on("change", function() {
        console.log($(this).val());
    })

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
        $(`.jo-${id}`).remove();
    });


    function addRow() {
        let index = `${purchaseRequestRowIndex++}0`;
   
        let row = `
                <tr id="row_${index}">
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
                    <td><select name="brands[]" id="brands_${index}" class="form-control item-select brand-select" style="width:150px;">
                        <option value="">Select Brand</option>
                        @foreach(getAllBrands() ?? [] as $brand)
                            <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                        @endforeach
                    </select></td>
                    <td style="width: 7%">
                        <div class="loop-fields">
                            <div class="form-group mb-0">
                                <input type="number" name="min_weight[]" id="min_weight_${index}" class="form-control"
                                    step="0.01" min="0" placeholder="Min Weight">
                            </div>
                        </div>
                    </td>
                    <td><select name="color[]" id="color_${index}" class="form-control item-select color-select" style="width:150px;">
                        <option value="">Select Color</option>
                        @foreach(getAllColors() ?? [] as $color)
                            <option value="{{ $color->id }}">{{ $color->color }}</option>
                        @endforeach
                    </select></td>
                    

                    <td style="width: 7%">
                        <div class="loop-fields">
                            <div class="form-group mb-0">
                                <input type="text" name="construction_per_square_inch[]"
                                    id="construction_per_square_inch_${index}" class="form-control" step="0.01" min="0"
                                    placeholder="Cons./sq. in.">
                            </div>
                        </div>
                    </td>
                    
                    <td><select name="size[]" id="size_${index}" class="form-control item-select size-select" style="width:150px;">
                        <option value="">Select Size</option>
                        @foreach(getAllSizes() ?? [] as $size)
                            <option value="{{ $size->id }}">{{ $size->size }}</option>
                        @endforeach
                    </select></td>

                    <td style="width: 6%">
                        <div class="loop-fields">
                            <div class="form-group mb-0">
                                <select name="stitching[${index}][]" id="stitching_${index}" class="form-control item-select stitching-select" style="width:150px;" multiple>
                                    <option value="">Select Stitching</option>
                                    @foreach(getAllStitchings() ?? [] as $stitching)
                                        <option value="{{ $stitching->id }}">{{ $stitching->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </td>
                    <td style="width: 6%">
                        <div class="loop-fields">
                            <div class="form-group mb-0">
                                <input type="text" name="micron[]" id="micron_${index}" class="form-control"
                                    step="0.01" min="0" placeholder="Micron">
                            </div>
                        </div>
                    </td>
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


        $("#brands_" + index).select2();
        $("#color_" + index).select2();
        $("#size_" + index).select2();
        $("#stitching_" + index).select2();


        $('.removeRowBtn').prop('disabled', false);
        $('#row_0 .removeRowBtn').prop('disabled', true);
    }


    function removeRow(index) {
        $('#row_' + index).remove();


        if ($('#purchaseRequestBody tr').length === 1) {
            $('#row_0 .removeRowBtn').prop('disabled', true);
        }
    }


    function get_uom(index) {
        console.log(index);
        let uom = $('#item_id_' + index).find(':selected').data('uom');
        console.log($('#item_id_' + index).find(':selected'));
        $('#uom_' + index).val(uom);
    }

    function filter_items(category_id, count) {
        $.ajax({
            url: '{{ route('get.items') }}',
            type: 'GET',
            data: {
                category_id: category_id
            },
            dataType: 'json',
            success: function (response) {
                if (response.success && response.products) {
                    $('#item_id_' + count).empty();
                    $('#item_id_' + count).append('<option value="">Select a Item</option>');

                    $.each(response.products, function (index, product) {
                        $('#item_id_' + count).append(
                            `<option data-uom="${product.unit_of_measure?.name ?? ''}" value="${product.id}">${product.name}</option>`
                        );
                    });


                    $('#item_id_' + count).select2();
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
</script>