<style>
    html, body {
        overflow-x: hidden;
    }

    #purchaseRequestTable .select2-container {
        width: 100% !important;
    }
</style>
<form style="overflow-x: hidden;" action="{{ route('store.purchase-request.store') }}" method="POST" id="ajaxSubmit" autocomplete="off">
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
                <input type="date" name="purchase_date" class="form-control" min="{{ date('Y-m-d') }}" id="purchase_date" value="{{ date('Y-m-d') }}">
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
                <label class="form-label">Category:</label>
                <select name="category_id_header" id="category_id_header" class="form-control select2" required>
                    <option value="">Select Category</option>
                    @foreach ($categories ?? [] as $category)
                        <option value="{{ $category->id }}">
                            {{ $category->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="col-md-4 header-conditional job-order-section" style="display: none;">
            <div class="form-group">
                <label class="form-label">Job Orders:</label>
                <select class="form-control select2 job_orders" name="master_job_orders[]" multiple data-placeholder="Select Job Orders" style="width: 100%;">
                    @foreach($job_orders as $job_order)
                        <option value="{{ $job_order->id }}">{{ $job_order->job_order_no }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="col-md-4 header-conditional other-category-section" style="display: none;">
            <div class="form-group">
                <label class="form-label">DEPARTMENT:</label>
                <select name="department_id" id="department_id" class="form-control select2" style="width: 100%;">
                    <option value="">Select Department</option>
                    @foreach ($departments ?? [] as $department)
                        <option value="{{ $department->id }}">
                            {{ $department->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="col-md-4 header-conditional other-category-section" style="display: none;">
            <div class="form-group">
                <label class="form-label">REQUEST BY:</label>
                <select name="request_by_id" id="request_by_id" class="form-control select2" style="width: 100%;">
                    <option value="">Select Request By</option>
                </select>
            </div>
        </div>

        <div class="col-xs-12 col-sm-12 col-md-12 mt-3">
            <div class="form-group">
                <label class="form-label">Remarks (Optional):</label>
                <textarea name="description" placeholder="Remarks" class="form-control" rows="2"></textarea>
            </div>
        </div>
    </div>

    <div class="row form-mar" id="itemSection" style="display: none;">
        <div class="col-12 text-right mb-2">
            <button type="button" style="float: right;" class="btn btn-sm btn-primary" onclick="addRow()" id="addRowBtn">
                <i class="fa fa-plus"></i>&nbsp; Add New Item
            </button>
        </div>

        <div class="col-md-12">
   <div style="overflow-x: auto; width: 100%;">
    <table class="table table-bordered" id="purchaseRequestTable" style="width:100%;">
        <thead>
            <tr>
                <th style="min-width: 450px;">Item</th>
                <th style="min-width: 200px;">Item UOM</th>
                <th style="min-width: 150px;">Qty</th>
                <th class="bag-only" style="min-width: 450px;">Job Orders</th>
                <th class="bag-only" style="min-width: 300px;">Brands</th>
                <th class="bag-only" style="min-width: 200px;">Min Weight (gm)</th>
                <th class="bag-only" style="min-width: 150px;">Tolerance</th>
                <th class="bag-only" style="min-width: 150px;">Tolerance %</th>
                <th class="bag-only" style="min-width: 300px;">Color</th>
                <th class="bag-only" style="min-width: 300px;">Cons./sq. in.</th>
                <th class="bag-only" style="width: 300px; min-width: 300px; max-width: 300px;">Size</th>
                <th class="bag-only" style="min-width: 350px;">Stitching</th>
                <th class="bag-only" style="min-width: 200px;">Micron</th>
                <th class="bag-only" style="min-width: 450px;">Printing Sample</th>
                <th style="min-width: 400px;">line desc</th>
                <th style="min-width: 150px;">Action</th>
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
    purchaseRequestRowIndex = 0;
    $(document).ready(function () {
        $('#category_id_0').select2();
        $(".color-select").select2();
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

        $('#category_id_header').on('change', function() {
            let category_id = $(this).val();
            // Clear items and job orders if category changes
            $("#purchaseRequestBody").empty();
            $(".job_orders").val(null).trigger('change');
            toggleVisibility(category_id);

            if (category_id) {
                $('#itemSection').show();
            } else {
                $('#itemSection').hide();
            }
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
    });

    function toggleVisibility(categoryId) {
        $('.header-conditional').hide();
        if (categoryId == 38) { // 38 is "Bags"
            $('.bag-only').show();
            $('.job-order-section').show();
            $('#purchaseRequestTable').css('min-width', '4000px');
        } else {
            $('.bag-only').hide();
            if (categoryId) {
                $('.other-category-section').show();
            }
            $('#purchaseRequestTable').css('min-width', '100%');
        }
    }

    $(".job_orders").on("change", function() {
        console.log($(this).val());
    })

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
        $(`.jo-${id}`).remove();
    });


    function addRow() {
        if($('#category_id_header').val() == "") {
            alert("Please select category first.");
            return;
        }
        let index = `${purchaseRequestRowIndex++}0`;
   
        let row = `
                <tr id="row_${index}">
                    <td style="min-width: 450px;">
                        <div class="loop-fields">
                            <div class="form-group mb-0">
                                <select name="item_id[]" id="item_id_${index}"  onchange="get_uom('${index}')"
                                    class="form-control item-select item-list" data-index="0" style="width: 100%;">
                                </select>
                                <input type="hidden" name="packing_id[]" value="" />
                                <input type="hidden" name="module_type[]" value="" />
                                <input type="hidden" name="index[]" value="${index}" />
                                <input type="hidden" name="is_single_job_order[]" value="0" />
                            </div>
                        </div>
                    </td>
                    <td style="min-width: 200px;">
                        <input type="text" name="uom[]" id="uom_${index}" class="form-control uom" readonly>
                    </td>
                    <td style="min-width: 150px;">
                        <input type="number" name="qty[]" id="qty_${index}" class="form-control bg-white" step="0.01"
                            min="0" placeholder="Qty">
                    </td>
                    <td class="bag-only" style="min-width: 450px;">
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
                    <td class="bag-only" style="min-width: 300px;">
                        <select name="brands[]" id="brands_${index}" class="form-control item-select brand-select">
                            <option value="">Select Brand</option>
                            @foreach(getAllBrands() ?? [] as $brand)
                                <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                            @endforeach
                        </select>
                    </td>
                    <td class="bag-only" style="min-width: 200px;">
                        <div class="loop-fields">
                            <div class="form-group mb-0">
                                <input type="number" name="min_weight[]" id="min_weight_${index}" class="form-control min-weight-input"
                                    step="0.01" min="0" placeholder="Min Weight">
                            </div>
                        </div>
                    </td>
                    <td class="bag-only" style="min-width: 150px;">
                        <div class="loop-fields">
                            <div class="form-group mb-0">
                                <input type="number" name="tolerance[]" id="tolerance_${index}" class="form-control tolerance-input"
                                    step="0.01" placeholder="Tolerance" readonly>
                            </div>
                        </div>
                    </td>
                    <td class="bag-only" style="min-width: 150px;">
                        <div class="loop-fields">
                            <div class="form-group mb-0">
                                <input type="number" name="tolerance_percentage[]" id="tolerance_percentage_${index}" class="form-control tolerance-percentage-input"
                                    step="0.01" min="0" max="100" placeholder="Tol. %">
                            </div>
                        </div>
                    </td>
                    <td class="bag-only" style="min-width: 300px;">
                        <select name="color[]" id="color_${index}" class="form-control item-select color-select">
                            <option value="">Select Color</option>
                            @foreach(getAllColors() ?? [] as $color)
                                <option value="{{ $color->id }}">{{ $color->color }}</option>
                            @endforeach
                        </select>
                    </td>
                    <td class="bag-only" style="min-width: 300px;">
                        <div class="loop-fields">
                            <div class="form-group mb-0">
                                <input type="text" name="construction_per_square_inch[]"
                                    id="construction_per_square_inch_${index}" class="form-control" step="0.01" min="0"
                                    placeholder="Cons./sq. in.">
                            </div>
                        </div>
                    </td>
                    <td class="bag-only" style="width: 300px; min-width: 300px; max-width: 300px;">
                        <input type="text" name="size[]" id="size_${index}" class="form-control size-input-check" placeholder="Size">
                    </td>
                    <td class="bag-only" style="min-width: 350px;">
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
                    <td style="min-width: 200px;" class="bag-only">
                        <div class="loop-fields">
                            <div class="form-group mb-0">
                                <input type="text" name="micron[]" id="micron_${index}" class="form-control"
                                    step="0.01" min="0" placeholder="Micron">
                            </div>
                        </div>
                    </td>
                    <td class="bag-only" style="min-width: 450px;">
                        <div class="loop-fields">
                            <div class="form-group mb-0">
                                <input type="file" name="printing_sample[${index}][]" id="printing_sample_${index}"
                                    class="form-control" accept="image/*,application/pdf"
                                    placeholder="Printing Sample" multiple>
                            </div>
                        </div>
                    </td>
                    <td style="min-width: 400px;">
                        <input type="text" name="remarks[]" id="remark_${index}" class="form-control"
                            placeholder="line desc">
                    </td>
                    <td style="min-width: 150px;">
                        <button type="button" class="btn btn-danger btn-sm removeRowBtn" onclick="removeRow('${index}')" style="width:120px;">
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

        filter_items($('#category_id_header').val(), index);
        toggleVisibility($('#category_id_header').val());


        $("#brands_" + index).select2();
        $("#color_" + index).select2();
        $("#stitching_" + index).select2();


        $('.removeRowBtn').prop('disabled', false);
        $(".item-list").select2();
        $(`#item_id_${index}`).trigger("change");
    }


    function removeRow(index) {
        $('#row_' + index).remove();
        if ($('#purchaseRequestBody tr').length === 1) {
            $('#purchaseRequestBody tr .removeRowBtn').prop('disabled', true);
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
    $(document).on('input', '.qty-input-check', function () {
        let input = $(this);
        let val = parseFloat(input.val()) || 0;
        let balance = parseFloat(input.data('balance')) || 0;

        if (val > balance) {
            alert("Quantity cannot exceed available Job Order balance (" + balance + ")");
            input.val(balance);
        }
        
        // Update live balance display
        let remaining = (balance - input.val()).toFixed(2);
        input.closest('td').find('.balance-span').text(remaining);
    });

    $(document).on('input', '.tolerance-percentage-input, .min-weight-input', function() {
        let row = $(this).closest('tr');
        let minWeight = parseFloat(row.find('.min-weight-input').val()) || 0;
        let percentage = parseFloat(row.find('.tolerance-percentage-input').val()) || 0;
        
        if (percentage > 100) {
            alert('Tolerance percentage cannot exceed 100%.');
            row.find('.tolerance-percentage-input').val(100);
            percentage = 100;
        }

        let tolerance = (minWeight * percentage / 100).toFixed(2);
        row.find('.tolerance-input').val(tolerance);
    });

    $(document).on('input', '.size-input-check', function() {
        this.value = this.value.replace(/[^0-9.]/g, '');
        if ((this.value.match(/\./g) || []).length > 1) {
            this.value = this.value.replace(/\.+$/, "");
        }
    });
</script>