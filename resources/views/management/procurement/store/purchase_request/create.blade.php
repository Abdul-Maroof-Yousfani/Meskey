<form action="{{ route('store.purchase-request.store') }}" method="POST" id="ajaxSubmit" autocomplete="off">
    @csrf
    <input type="hidden" id="listRefresh" value="{{ route('store.get.purchase-request') }}" />
    <div class="row form-mar">        
        <div class="col-md-6">
            <div class="form-group">
                <label>Location:</label>
                <select name="company_location_id" id="company_location_id" class="form-control">
                    <option value="">Select Location</option>
                    
                </select>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label>Purchase Date:</label>
                <input type="date" name="purchase_date" class="form-control">
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label>Reference No:</label>
                <input type="text" name="reference_no" class="form-control">
            </div>
        </div>

        <!-- Description -->
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Description (Optional):</label>
                <textarea name="description" placeholder="Description" class="form-control"></textarea>
            </div>
        </div>
       
    </div>
    <!-- Purchase Request Detail Section -->
    <div class="row form-mar">
        <div class="col-md-12">
            <table class="table table-bordered" id="purchaseRequestTable">
                <thead>
                    <tr>
                        <th colspan="5"></th>
                        <th colspan="2"><button type="button" style="float: right" class="btn btn-sm btn-primary" onclick="addRow()" id="addRowBtn">Add New Row</button></th>
                    </tr>
                </thead>
                <thead>
                    <tr>
                        <th>Category</th>
                        <th>Item</th>
                        <th>Item UOM</th>
                        <th>Qty</th>
                        <th>Job Orders</th>
                        <th>Remarks</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="purchaseRequestBody">
                     <tr id="row_0">
                        <td style="width: 25%">
                            <select name="category_id[]" id="category_id_0" onchange="filter_items(this.value,0)" class="form-control item-select" data-index="0">
                                <option value="">Select Category</option>
                                 @foreach ($categories ?? [] as $category)
                                     <option value="{{$category->id}}">{{$category->name}}</option>
                                 @endforeach
                            </select>
                        </td>
                        <td style="width: 25%">
                            <select name="item_id[]" id="item_id_0" onchange="get_uom(0)" class="form-control item-select" data-index="0">
                                
                            </select>
                        </td>
                        <td style="width: 10%"><input type="text" name="uom[]" id="uom_0" class="form-control uom" readonly></td>
                        <td style="width: 10%"><input type="number" name="qty[]" id="qty_0" class="form-control" step="0.01" min="0"></td>
                        <td style="width: 20%">
                            <select name="job_order_id[0][]" id="job_order_id_0" multiple class="form-control item-select" data-index="0">
                                <option value="">Select Job Order</option>
                                 @foreach ($job_orders ?? [] as $job_order)
                                     <option value="{{$job_order->id}}">{{$job_order->name}}</option>
                                 @endforeach
                            </select>
                        </td>
                        <td style="width: 25%"><input type="text" name="remarks[]" id="remark_0" class="form-control"></td>
                        <td><button type="button" disabled class="btn btn-danger btn-sm removeRowBtn" data-id="0">Remove</button></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Hidden for row counter -->
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
        initializeDynamicSelect2('#company_location_id', 'company_locations', 'name', 'id', true, false);
        $('#category_id_0').select2();
        $('#job_order_id_0').select2({
            placeholder: 'Please Select Job Order', // or 'resolve', '300px', etc.
            width: '100%' // or 'resolve', '300px', etc.
        });


    });
    let rowIndex = 1;
    function addRow() {
        let index = rowIndex++;
        let row = `
            <tr id="row_${index}">
                <td style="width: 25%">
                    <select name="category_id[]" onchange="filter_items(this.value,${index})" id="category_id_${index}" class="form-control item-select" data-index="0">
                        <option value="">Select Category</option>
                        @foreach ($categories ?? [] as $category)
                            <option value="{{$category->id}}">{{$category->name}}</option>
                        @endforeach
                    </select>
                </td>
                <td style="width: 25%">
                    <select name="item_id[]" id="item_id_${index}" onchange="get_uom(${index})" class="form-control item-select" data-index="0">
                        
                    </select>
                </td>
                <td style="width: 10%"><input type="text" name="uom[]" id="uom_${index}" class="form-control uom" readonly></td>
                <td style="width: 10%"><input type="number" name="qty[]" id="qty_${index}" class="form-control" step="0.01" min="0"></td>
                <td style="width: 20%">
                    <select name="job_order_id[${index}][]" id="job_order_id_${index}" multiple class="form-control item-select">
                        <option value="">Select Job Order</option>
                            @foreach ($job_orders ?? [] as $job_order)
                                <option value="{{$job_order->id}}">{{$job_order->name}}</option>
                            @endforeach
                    </select>
                </td>
                <td style="width: 25%"><input type="text" name="remarks[]" id="remark_${index}" class="form-control"></td>
                
                <td><button type="button" class="btn btn-danger btn-sm removeRowBtn" onclick="remove(${index})">Remove</button></td>
            </tr>`;
        $('#purchaseRequestBody').append(row);
         $('#job_order_id_'+index).select2({
            placeholder: 'Please Select Job Order', // or 'resolve', '300px', etc.
            width: '100%' // or 'resolve', '300px', etc.
        });
        $('#category_id_'+index).select2();
    }

    function remove(id) {
        $('#row_'+id).remove();
    }

     function filter_items(category_id,count) {
            $.ajax({
                url: '{{route('get.items')}}', // Replace with your actual API endpoint
                type: 'GET',
                data: { category_id: category_id },
                dataType: 'json',
                success: function(response) {
                    // Assuming response contains an array of categories
                    if (response.success && response.products) {
                        // Clear existing options
                        $('#item_id_'+count).empty();
                        
                        // Add default option
                         $('#item_id_'+count).append('<option value="">Select a Item</option>');
                        
                        // Append new category options to the select element
                        $.each(response.products, function(index, product) {
                             $('#item_id_'+count).append(
                                `<option data-uom="${product.unit_of_measure?.name ?? ''}" value="${product.id}">${product.name}</option>`
                            );
                        });
                        $('#item_id_'+count).select2();
                    } else {
                        console.error('No products found or request failed');
                         $('#item_id_'+count).html('<option value="">No products available</option>');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', status, error);
                     $('#item_id_'+count).html('<option value="">Error loading products</option>');
                }
            });
        }

        function get_uom(index) {
            let uom = $('#item_id_'+index).find(':selected').data('uom');
            $('#uom_'+index).val(uom);
        }
    </script>