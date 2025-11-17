 <input type="hidden" id="listRefresh" value="{{ route('store.get.purchase-order-receiving') }}" />
 {{-- <input type="hidden" name="data_id" value="{{ $purchaseOrderReceiving->purchase_orde->id }}"> --}}
 {{-- <input type="hidden" name="purchase_request_data_id"
    value="{{ optional($purchaseOrderReceiving->orde_data->first())->purchase_request_data_id }}"> --}}

 <div class="row form-mar">
     <div class="col-md-4">
         <div class="form-group">
             <label>Purchase Order:</label>
             <select disabled class="form-control select2" name="purchase_order_id">
                 <option value="">Select Purchase Order</option>
                 @foreach ($purchaseOrder ?? [] as $value)
                     <option {{ $purchaseOrderReceiving->purchase_order_id == $value->id ? 'selected' : '' }}
                         value="{{ $value->id }}">
                         {{ $value->purchase_order_no }}
                     </option>
                 @endforeach
             </select>
         </div>
     </div>

     <div class="col-md-4">
         <div class="form-group">
             <label class="form-label">Supplier:</label>
             <select disabled id="vendor_id" name="vendor_id" class="form-control item-select select2">
                 <option value="">Select Vendor</option>
                 @foreach (get_supplier() as $supplier)
                     <option value="{{ $supplier->id }}"
                         {{ $purchaseOrderReceiving->supplier_id == $supplier->id ? 'selected' : '' }}>
                         {{ $supplier->name }}
                     </option>
                 @endforeach
                 <input type="hidden" name="supplier_id" value="{{ optional($purchaseOrderReceiving)->supplier_id }}"
                     id="supplier_id">
             </select>
         </div>
     </div>

     <div class="col-md-4">
         <div class="form-group">
             <label>Purchase Request:</label>
             <select readonly class="form-control" name="purchase_request_id">
                 <option value="{{ optional($purchaseOrderReceiving->purchase_request)->id }}">
                     {{ optional($purchaseOrderReceiving->purchase_request)->purchase_request_no }}</option>
             </select>
         </div>
     </div>
     <div class="col-md-4">
         <div class="form-group">
             <label>Receiving Date:</label>
             <input readonly type="date" id="receiving_date"
                 value="{{ optional($purchaseOrderReceiving)->order_receiving_date }}" name="receiving_date"
                 class="form-control">
         </div>
     </div>
     <div class="col-md-4">
         <div class="form-group">
             <label>Location:</label>
             <select disabled name="company_location" id="company_location_id" class="form-control select2">
                 <option value="">Select Location</option>
                 @foreach (get_locations() as $loc)
                     <option {{ optional($purchaseOrderReceiving)->location_id == $loc->id ? 'selected' : '' }}
                         value="{{ $loc->id }}">{{ $loc->name }}</option>
                 @endforeach
                 <input type="hidden" name="location_id" value="{{ optional($purchaseOrderReceiving)->location_id }}"
                     id="location_id">
             </select>
         </div>
     </div>

     <div class="col-xs-12 col-sm-12 col-md-12">
         <div class="form-group">
             <label>Description (Optional):</label>
             <textarea readonly name="description" id="description" placeholder="Description" class="form-control">{{ $purchaseOrderReceiving->description }}</textarea>
         </div>
     </div>
 </div>
 <div class="row form-mar">
     <div class="col-md-12" style="overflow-x: auto; white-space: nowrap;">
         <table class="table table-bordered" id="purchaseRequestTable">
             <thead>
                 <tr>
                     <th>Category</th>
                     <th>Item</th>
                     <th>Item UOM</th>
                     <th>Qty</th>

                     <th>Accepted Quantity</th>
                     <th>Rejected Quantity</th>
                     <th>Deduction Per KG</th>
                     <th>Min Weight</th>
                     <th>Brand</th>
                     <th>Color</th>
                     <th>Cons./sq. in.</th>
                     <th>Size</th>
                     <th>Stitching</th>
                     {{-- <th>Rate</th>
                     <th>Total Amount</th> --}}
                     <th>Remarks</th>
                     <th>Action</th>
                 </tr>
             </thead>
             <tbody id="purchaseRequestBody">
               
                 @foreach ($purchaseOrderReceivingData ?? [] as $key => $data)
                      <button id="modalButton{{ $key }}" style="visibility: hidden;" onclick="openModal(this, '{{ route('store.qc.show-create', ['id' => $data->id]) }}', 'Add QC', false, '100%')">&nbsp;</button>
                      <button id="modalButtonQc{{ $key }}" style="visibility: hidden;" onclick="openModal(this, '{{ route('store.qc.edit', ['id' => $data->id]) }}', 'Edit QC', false, '100%')">&nbsp;</button>
                      <button id="modalButtonViewQc{{ $key }}" style="visibility: hidden;" onclick="openModal(this, '{{ route('store.qc.view', ['id' => $data->id]) }}', 'Edit QC', false, '100%')">&nbsp;</button>
                       
                     <tr id="row_{{ $key }}">
                         <td style="width: 50%">
                             <select id="category_id_{{ $key }}" disabled
                                 onchange="filter_items(this.value,{{ $key }})"
                                 class="form-control item-select select2" data-index="{{ $key }}">
                                 <option value="">Select Category</option>
                                 @foreach ($categories ?? [] as $category)
                                     <option {{ $category->id == $data->category_id ? 'selected' : '' }}
                                         value="{{ $category->id }}">
                                         {{ $category->name }}
                                     </option>
                                 @endforeach
                             </select>
                             <input type="hidden" name="category_id[]" value="{{ $data->category_id }}">
                             <input type="hidden" name="data_id[]" value="{{ $data->id }}">
                         </td>

                         <td style="width: 50%">
                             <select id="item_id_{{ $key }}"  onchange="get_uom({{ $key }})" disabled
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
                             <input type="text" style="width: 100px;" id="uom_{{ $key }}" class="form-control uom"
                                 value="{{ get_uom($data->item_id) }}" disabled readonly>
                             <input type="hidden" name="uom[]" value="{{ get_uom($data->item_id) }}">
                         </td>

                         
                        <td style="width: 30%">
                             <input style="width: 100px" type="number" onkeyup="calc({{ $key }})" disabled
                                 onblur="calc({{ $key }})" value="{{ $data->qty }}"
                                 id="qty_{{ $key }}" class="form-control" step="0.01" min="0">
                             <input type="hidden" name="qty[]" value="{{ $data->qty }}">
                         </td>

                          <td style="width: 10%">
                                <input style="width: 100px" type="number" onkeyup="calc({{ $key }})"
                                    onblur="calc({{ $key }})" name="accepted_qty[]" @readonly(isBag($data->item_id)) value="{{ $data->qc?->accepted_quantity ?? null }}"
                                    id="accepted_qty_{{ $key }}" class="form-control accepted_qty" placeholder="Accepted Quantity" step="0.01" min="0" max=""
                                   >
                            </td>

                            <td style="width: 10%">
                                <input style="width: 100px" type="number" onkeyup="calc({{ $key }})"
                                    onblur="calc({{ $key }})" name="rejected_qty[]" @readonly(isBag($data->item_id)) value="{{ $data->qc?->rejected_quantity ?? null }}"
                                    id="rejected_qty_{{ $key }}" class="form-control rejected_qty" step="0.01" placeholder="Rejected Quantity"  min="0" max=""
                                   >
                            </td>

                            <td style="width: 10%">
                                <input style="width: 100px" type="number" onkeyup="calc({{ $key }})"
                                    onblur="calc({{ $key }})" name="deduction_per_bag[]" @readonly(isBag($data->item_id)) value="{{ $data->qc?->deduction_per_bag ?? null }}"
                                    id="deduction_per_bag{{ $key }}" class="form-control deduction_per_bag" step="0.01" placeholder="Deduction Per Bag" min="0" max=""
                                   >
                            </td>

                         <td style="width: 30%">
                             <div class="loop-fields">
                                 <div class="form-group mb-0">
                                     <input type="number" style="width: 100px;" name="min_weight[]"
                                         id="min_weight_0" class="form-control" step="0.01" min="0"
                                         value="{{ $data->purchase_order_data->min_weight }}"
                                         placeholder="Min Weight">
                                 </div>
                             </div>
                         </td>
                         <td style="width: 30%">
                             <div class="loop-fields">
                                 <div class="form-group mb-0">
                                     <input type="text" name="brand[]" style="width: 100px;"
                                         value="{{ $data->purchase_order_data->brand }}" id="color_0"
                                         class="form-control" step="0.01" min="0" placeholder="Color">
                                 </div>
                             </div>
                         </td>
                         <td style="width: 30%">
                             <div class="loop-fields">
                                 <div class="form-group mb-0">
                                     <input type="text" name="color[]" style="width: 100px;"
                                         value="{{ $data->purchase_order_data->color }}" id="color_0"
                                         class="form-control" step="0.01" min="0" placeholder="Color">
                                 </div>
                             </div>
                         </td>


                         <td style="width: 30%">
                             <div class="loop-fields">
                                 <div class="form-group mb-0">
                                     <input type="text" style="width: 100px;"
                                         name="construction_per_square_inch[]" id="construction_per_square_inch_0"
                                         value="{{ $data->purchase_order_data->construction_per_square_inch }}"
                                         class="form-control" step="0.01" min="0"
                                         placeholder="Cons./sq. in.">
                                 </div>
                             </div>
                         </td>
                         <td style="width: 30%">
                             <div class="loop-fields">
                                 <div class="form-group mb-0">
                                     <input type="text" name="size[]" style="width: 100px;" id="size_0"
                                         value="{{ $data->purchase_order_data->size }}" class="form-control"
                                         step="0.01" min="0" placeholder="Size">
                                 </div>
                             </div>
                         </td>
                         <td style="width: 30%">
                             <div class="loop-fields">
                                 <div class="form-group mb-0">
                                     <input type="text" name="stitching[]" style="width: 100px;" id="stitching_0"
                                         value="{{ $data->purchase_order_data->stitching }}" class="form-control"
                                         step="0.01" min="0" placeholder="Stitching">
                                 </div>
                             </div>
                         </td>

                         {{-- <td style="width: 30%">
                             <input style="width: 100px" type="number" onkeyup="calc({{ $key }})"
                                 onblur="calc({{ $key }})" name="rate[]" value="{{ $data->rate }}"
                                 disabled id="rate_{{ $key }}" class="form-control" step="0.01"
                                 min="{{ $key }}">
                         </td>

                         <td style="width: 30%">
                             <input style="width: 100px" type="number" readonly value="{{ $data->total }}"
                                 id="total_{{ $key }}" class="form-control" step="0.01" min="0"
                                 readonly name="total[]">
                         </td> --}}

                         <td style="width: 30%">
                             <input style="width: 100px" type="text" readonly value="{{ $data->remarks }}"
                                 id="remark_{{ $key }}" class="form-control">
                             <input type="hidden" name="remarks[]" value="{{ $data->remarks }}">
                         </td>
                         <td>
                             <button type="button" class="btn btn-danger btn-sm removeRowBtn"
                                 onclick="remove({{ $key }})" disabled
                                 data-id="{{ $key }}">Remove</button>
                            <button onclick="createQc('{{ $data->id }}', '{{ $key }}')" @disabled(($data->qc?->exists())) style="width: 100px;" type="button" class="btn btn-success btn-sm createQc">Create QC</button>
                            <button onclick="editQc('{{ $data->id }}', '{{ $key }}')" @disabled($data->qc?->is_qc_approved || !$data->qc?->exists()) style="width: 100px;" type="button" class="btn btn-warning btn-sm createQc">Edit QC</button>
                            <button onclick="viewQc('{{ $data->id }}', '{{ $key }}')" style="width: 100px;" type="button" class="btn btn-primary btn-sm viewQc">View QC</button>
             
                         </td>
                     </tr>
                 @endforeach
             </tbody>

         </table>
     </div>
 </div>
 <input type="hidden" id="rowCount" value="0">
 <div class="row">
     <div class="col-12">
         <x-approval-status :model="$data1" />
     </div>
 </div>
 <div class="row bottom-button-bar">
     <div class="col-12">
         <a type="button" class="btn btn-danger modal-sidebar-close position-relative top-1 closebutton">Close</a>
         {{-- <button type="submit" class="btn btn-primary submitbutton">Save</button> --}}
     </div>
 </div>

 <script>

    function createQc(id, key) {
        $("#modalButton" + key).trigger("click");
    }
    function editQc(id, key) {
        $("#modalButtonQc" + key).trigger("click");
    }
    function viewQc(id, key) {
        $("#modalButtonViewQc" + key).trigger("click");
    }
    // function createQc(id, element) {
    //     const accepted_qty = $(element).closest("tr").find(".accepted_qty");
    //     const rejected_qty = $(element).closest("tr").find(".rejected_qty");
    //     const deduction_per_bag = $(element).closest("tr").find(".deduction_per_bag");
      
       
    //     Swal.fire({
    //         title: "Are you sure?",
    //         text: "A QC will be created for this item.",
    //         icon: "warning",
    //         showCancelButton: true,
    //         confirmButtonColor: "#3085d6",
    //         cancelButtonColor: "#d33",
    //         confirmButtonText: "Yes, create it!",
    //         cancelButtonText: "Cancel"
    //     }).then((result) => {
    //         if (result.isConfirmed) {
    //             // Proceed with QC creation
               
    //             // 👉 Show processing/loading swal
    //             Swal.fire({
    //                 title: "Processing...",
    //                 text: "Please wait while we create the QC.",
    //                 allowOutsideClick: false,
    //                 allowEscapeKey: false,
    //                 didOpen: () => {
    //                     Swal.showLoading();
    //                 }
    //             });


    //             $.ajax({
    //                 url: "{{ route('store.qc.create') }}",
    //                 type: 'POST',
    //                 dataType: "json",
    //                 processData: true,
    //                 contentType: "application/x-www-form-urlencoded; charset=UTF-8",
    //                 data: { 
    //                     id: id,
    //                     accepted_qty: accepted_qty.val(),
    //                     rej_qty: rejected_qty.val(),
    //                     deduction_per_bag: deduction_per_bag.val()
    //                 },
    //                 success: function (response) {
    //                     console.log(response);
    //                      Swal.fire({
    //                         title: "Created!",
    //                         text: "QC has been successfully created.",
    //                         icon: "success"
    //                     });
    //                 },
    //                 error: function (xhr, status, error) {
    //                     console.log(error);
    //                 }
    //             });

    //         }
    //     });
    // }
     $('.select2').select2({
         placeholder: 'Please Select',
         width: '100%'
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
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </select>
                </td>
                <td style="width: 25%">
                    <select name="item_id[]" id="item_id_${index}" onchange="get_uom(${index})" class="form-control item-select" data-index="0">
                        
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
             url: '{{ route('get.items') }}', // Replace with your actual API endpoint
             type: 'GET',
             data: {
                 category_id: category_id
             },
             dataType: 'json',
             success: function(response) {
                 // Assuming response contains an array of categories
                 if (response.success && response.products) {
                     // Clear existing options
                     $('#item_id_' + count).empty();

                     // Add default option
                     $('#item_id_' + count).append('<option value="">Select a Item</option>');

                     // Append new category options to the select element
                     $.each(response.products, function(index, product) {
                         $('#item_id_' + count).append(
                             `<option data-uom="${product.unit_of_measure?.name ?? ''}" value="${product.id}">${product.name}</option>`
                         );
                     });
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

     function get_uom(index) {
         let uom = $('#item_id_' + index).find(':selected').data('uom');
         $('#uom_' + index).val(uom);
     }

     function get_purchase(purchaseRequestId) {
         if (!purchaseRequestId) return;

         $.ajax({
             url: "{{ route('store.purchase-order-receiving.approve-item') }}",
             type: "GET",
             data: {
                 id: purchaseRequestId
             },
             beforeSend: function() {
                 $('#purchaseRequestBody').html('<p>Loading...</p>');
             },
             success: function(response) {
                 let html = response.html;
                 let master = response.master;
                 console.log(master);


                 $('#company_location_id').val(master.location_id);
                 $('#location_id').val(master.location_id);
                 $('#purchase_date').val(master.purchase_date);
                 $('#reference_no').val(master.reference_no);
                 $('#description').val(master.description);
                 $('#company_location_id').val(master.location_id).trigger('change');
                 $('#purchaseRequestBody').html('').html(html);
                 $('.select2').select2({
                     placeholder: 'Please Select', // or 'resolve', '300px', etc.
                     width: '100%' // or 'resolve', '300px', etc.
                 });
             },
             error: function() {
                 $('#purchaseRequestBody').html('<p>Error loading data.</p>');
             }
         });
     }

     function calc(num) {
         var qty = parseFloat($('#qty_' + num).val());
         var rate = parseFloat($('#rate_' + num).val());

         var total = qty * rate;

         $('#total_' + num).val(total);

     }
 </script>
