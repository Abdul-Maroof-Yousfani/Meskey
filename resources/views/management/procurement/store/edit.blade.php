<form action="{{ route('store.purchase-request.update', $purchase_request->id) }}" method="POST" id="ajaxSubmit" autocomplete="off">
    @csrf
     @method('PUT')
    <input type="hidden" id="listRefresh" value="{{ route('store.get.purchase-request') }}" />
    <div class="row form-mar">        
        <div class="col-md-6">
            <div class="form-group">
                <label>Location:</label>
                <select name="company_location_id" id="company_location_id" class="form-control">
                    <option value="">Select Location</option>
                    @foreach ($locations ?? [] as $loc)
                        <option {{$loc->id == $purchase_request->loction_id ? 'selected' : ''}} value="{{$loc->id}}">{{$loc->name}}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label>Purchase Date:</label>
                <input type="date" name="purchase_date" value="{{$purchase_request->purchase_date}}" class="form-control">
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label>Reference No:</label>
                <input type="text" name="reference_no" class="form-control" value="{{$purchase_request->reference_no}}">
            </div>
        </div>

        <!-- Description -->
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Description (Optional):</label>
                <textarea name="description" placeholder="Description" class="form-control">{{$purchase_request->description}}</textarea>
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
                    @foreach ($purchase_request->PurchaseData ?? [] as $key => $value)
                        <tr id="row_0">
                            <td style="width: 25%">
                                <select name="category_id[]" id="category_id_{{$key}}" onchange="filter_items(this.value,{{$key}})" class="form-control item-select" data-index="0">
                                    <option value="">Select Category</option>
                                    @foreach ($categories ?? [] as $category)
                                        <option {{$category->id == $value->category_id ? 'selected' : ''}} value="{{$category->id}}">{{$category->name}}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td style="width: 25%">
                               <select name="item_id[]" id="item_id_{{$key}}" onchange="get_uom({{$key}})" class="form-control item-select" data-index="{{$key}}">
                                    @foreach (get_product_by_category($value->category_id) as $item)
                                        <option 
                                            data-uom="{{ $item->unit_of_measure->name ?? '' }}" 
                                            value="{{ $item->id }}"
                                            {{ $item->id == $value->item_id ? 'selected' : '' }}
                                        >
                                            {{ $item->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </td>
                            <td style="width: 10%"><input type="text" name="uom[]" value="{{get_uom($value->item_id)}}" id="uom_{{$key}}" class="form-control uom" readonly></td>
                            <td style="width: 10%"><input type="number" name="qty[]" value="{{$value->qty}}" id="qty_{{$key}}" class="form-control" step="0.01" min="0"></td>
                            <td style="width: 20%">
                                <select name="job_order_id[{{$key}}][]" id="job_order_id_{{$key}}" multiple class="form-control item-select" data-index="0">
                                    <option value="">Select Job Order</option>
                                    @foreach ($job_orders ?? [] as $job_order)
                                        <option {{ (isset($value->JobOrder) && in_array($job_order->id, $value->JobOrder->pluck('job_order_id')->toArray())) ? 'selected' : '' }} value="{{$job_order->id}}">{{$job_order->name}}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td style="width: 25%"><input type="text" name="remarks[]" value="{{$value->remarks}}" id="remark_{{$key}}" class="form-control"></td>
                            <td><button type="button" disabled class="btn btn-danger btn-sm removeRowBtn" data-id="0">Remove</button></td>
                        </tr>
                    @endforeach
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
        // Set preselected value after a slight delay to ensure select2 has initialized
        setTimeout(function () {
            $('#company_location_id').val({{ $purchase_request->location_id }}).trigger('change');
        }, 500);
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

    function filter_items(category_id, count, selectedItemId = null) {
        
        $.ajax({
            url: '{{route('get.items')}}',
            type: 'GET',
            data: { category_id: category_id },
            dataType: 'json',
            success: function(response) {
                if (response.success && response.products) {
                    const $itemSelect = $('#item_id_' + count);
                    $itemSelect.empty();

                    $itemSelect.append('<option value="">Select an Item</option>');

                    $.each(response.products, function(index, product) {
                        $itemSelect.append(
                            `<option data-uom="${product.unit_of_measure?.name ?? ''}" value="${product.id}">${product.name}</option>`
                        );
                    });

                    $itemSelect.select2();

                    // If selectedItemId is provided, select it, else select first item
                    // if (selectedItemId && $itemSelect.find(`option[value='${selectedItemId}']`).length) {
                    //     $itemSelect.val(selectedItemId).trigger('change');
                    // } else {
                    //     const firstItemValue = $itemSelect.find('option:eq(1)').val();
                    //     if (firstItemValue) {
                    //         $itemSelect.val(firstItemValue).trigger('change');
                    //     } else {
                    //         $('#uom_' + count).val('');
                    //     }
                    // }

                    // get_uom(count);
                } else {
                    console.error('No products found or request failed');
                    $('#item_id_' + count).html('<option value="">No products available</option>');
                    $('#uom_' + count).val('');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', status, error);
                $('#item_id_' + count).html('<option value="">Error loading products</option>');
                $('#uom_' + count).val('');
            }
        });
    }


    function get_uom(index) {
        let uom = $('#item_id_'+index).find(':selected').data('uom');
        $('#uom_'+index).val(uom);
    }
    </script>