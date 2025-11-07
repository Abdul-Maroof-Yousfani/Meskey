 <input type="hidden" id="listRefresh" value="{{ route('store.get.purchase-quotation') }}" />
 {{-- <input type="hidden" name="data_id" value="{{ $purchaseRequest->purchase_quotation->id }}"> --}}
{{-- <input type="hidden" name="purchase_request_data_id"
    value="{{ optional($purchaseRequest->quotation_data->first())->purchase_request_data_id }}"> --}}

 <div class="row form-mar">
     <div class="col-md-3">
         <div class="form-group">
             <label>Purchase Request:</label>
             <select readonly class="form-control" onchange="get_purchase(this.value)" name="purchase_request_id">
                 <option value="{{ optional($purchaseRequest)->id }}<">
                     {{ optional($purchaseRequest)->purchase_request_no }}
                 </option>
             </select>
         </div>
     </div>
     {{-- <div class="col-md-3">
         <div class="form-group">
             <label>Location:</label>
             <select disabled name="company_location" id="company_location_id" class="form-control select2">
                 <option value="">Select Location</option>
                 @foreach (get_locations() as $loc)
                     <option
                         {{ optional($purchaseRequest)->location_id == $loc->id ? 'selected' : '' }}
                         value="{{ $loc->id }}">{{ $loc->name }}</option>
                 @endforeach
                 <input type="hidden" name="location_id"
                     value="{{ optional($purchaseRequest)->location_id }}" id="location_id">
             </select>
         </div>
     </div> --}}
     <div class="col-md-3">
         <div class="form-group">
             <label>PR Date:</label>
             <input readonly type="date" id="purchase_date"
                 value="{{ $purchaseRequest->purchase_date }}" name="purchase_date"
                 class="form-control">
         </div>
     </div>
 
     {{-- <div class="col-md-3">
            <div class="form-group">
                <label class="form-label">Supplier:</label>
                <select disabled id="supplier_id" name="supplier_id" class="form-control item-select select2">
                    <option value="">Select Vendor</option>
                    @foreach (get_supplier() as $supplier)
                        <option value="{{ $supplier->id }}"
                        {{ $supplier->id == $purchaseRequestData->supplier_id ? 'selected' : '' }}>
                            {{ $supplier->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div> --}}
     <div class="col-xs-12 col-sm-12 col-md-12">
         <div class="form-group">
             <label>Description (Optional):</label>
             <textarea readonly name="description" id="description" placeholder="Description" class="form-control">{{ $purchaseRequest->description }}</textarea>
         </div>
     </div>
 </div>
 <div class="row form-mar">
     <div class="col-md-12">
         <table class="table table-bordered" id="purchaseRequestTable">
             <thead>
                 <tr>
                     <th class="col-sm-4">PQ No.</th>
                     <th class="col-sm-3">Supplier</th>
                     <th class="col-sm-3">Item</th>
                     <th class="col-sm-3">Min Weight</th>
                     <th class="col-sm-3">Color</th>
                     <th class="col-sm-3">Cons./sq. in.</th>   
                     <th class="col-sm-3">Size</th>   
                     <th class="col-sm-3">Stitching</th>
                     {{-- <th>Item UOM</th> --}}
                     <th>Qty</th>
                     <th>Rate</th>
                     <th>Total Amount</th>
                     <th>Remarks</th>
                     <th>Action</th>
                 </tr>
             </thead>
        <tbody id="purchaseRequestBody">
    @forelse ($PurchaseQuotationData ?? [] as $key => $data)
        
        <tr id="row_{{ $key }}">
            <td style="width: 20%">
                <input type="hidden" name="data_id[]" value="{{ $data->id }}">
                <input style="width: 170px" type="text" readonly
                    value="{{ $data->purchase_quotation->purchase_quotation_no ?? '-' }}"
                    id="purchase_quotation_no_{{ $key }}" class="form-control">
                <input type="hidden" name="purchase_quotation_no[]"
                    value="{{ $data->purchase_quotation->purchase_quotation_no ?? '' }}">
            </td> 
            <td style="width: 20%">
                <select id="supplier_id_{{ $key }}" name="supplier_id[]" disabled
                    class="form-control item-select select2" data-index="{{ $key }}">
                    <option value="">Select Vendor</option>
                    @foreach (get_supplier() as $supplier)
                        <option value="{{ $supplier->id }}" @selected($data->supplier_id == $supplier->id)>
                            {{ $supplier->name }}
                        </option>
                    @endforeach
                </select>
            </td>
            <td style="width: 5%">
                <select id="item_id_{{ $key }}" onchange="get_uom({{ $key }})" disabled
                    class="form-control item-select select2" data-index="{{ $key }}">
                    @foreach (get_product_by_category($data->category_id) as $item)
                        <option data-uom="{{ $item->unitOfMeasure->name ?? '' }}" value="{{ $item->id }}"
                            {{ $item->id == $data->item_id ? 'selected' : '' }}>
                            {{ $item->name }}
                        </option>
                    @endforeach
                </select>
                <input type="hidden" name="item_id[]" value="{{ $data->item_id }}">
            </td>
            <td style="width: 5%">
                <input style="width: 85px" type="number" onkeyup="calc({{ $key }})" disabled
                    onblur="calc({{ $key }})" value="{{ $purchaseRequest->PurchaseData[$key]->min_weight }}"
                    id="qty_{{ $key }}" class="form-control" step="0.01" min="0">
                <input type="hidden" name="min_weight[]" value="{{ $purchaseRequest->PurchaseData[$key]->min_weight }}">
            </td>
            <td style="width: 5%">
                <input style="width: 85px" type="text" onkeyup="calc({{ $key }})" disabled
                    onblur="calc({{ $key }})" value="{{ $purchaseRequest->PurchaseData[$key]->color }}"
                    id="qty_{{ $key }}" class="form-control" step="0.01" min="0">
                <input type="hidden" name="color[]" value="{{ $purchaseRequest->PurchaseData[$key]->color }}">
            </td>
            <td style="width: 5%">
                <input style="width: 85px" type="number" onkeyup="calc({{ $key }})" disabled
                    onblur="calc({{ $key }})" value="{{ $purchaseRequest->PurchaseData[$key]->construction_per_square_inch }}"
                    id="qty_{{ $key }}" class="form-control" step="0.01" min="0">
                <input type="hidden" name="construction_per_square_inch[]" value="{{ $purchaseRequest->PurchaseData[$key]->construction_per_square_inch }}">
            </td>
            <td style="width: 5%">
                <input style="width: 85px" type="number" onkeyup="calc({{ $key }})" disabled
                    onblur="calc({{ $key }})" value="{{ $purchaseRequest->PurchaseData[$key]->size }}"
                    id="qty_{{ $key }}" class="form-control" step="0.01" min="0">
                <input type="hidden" name="size[]" value="{{ $purchaseRequest->PurchaseData[$key]->size }}">
            </td>
            <td style="width: 5%">
                <input style="width: 85px" type="text" onkeyup="calc({{ $key }})" disabled
                    onblur="calc({{ $key }})" value="{{ $purchaseRequest->PurchaseData[$key]->stitching }}"
                    id="qty_{{ $key }}" class="form-control" step="0.01" min="0">
                <input type="hidden" name="stitch[]" value="{{ $purchaseRequest->PurchaseData[$key]->stitching }}">
            </td>
            <td style="width: 5%">
                <input style="width: 85px" type="number" onkeyup="calc({{ $key }})" disabled
                    onblur="calc({{ $key }})" value="{{ $data->qty }}"
                    id="qty_{{ $key }}" class="form-control" step="0.01" min="0">
                <input type="hidden" name="qty[]" value="{{ $data->qty }}">
            </td>
            <td style="width: 5%">
                <input style="width: 85px" type="number" onkeyup="calc({{ $key }})"
                    onblur="calc({{ $key }})" name="rate[]" value="{{ $data->rate }}" disabled
                    id="rate_{{ $key }}" class="form-control" step="0.01" min="{{ $key }}">
            </td>



            <td style="width: 5%">
                <input style="width: 85px" type="number" onkeyup="calc({{ $key }})"
                    onblur="calc({{ $key }})" name="amount[]" value="{{ (int)$data->rate * (int)$data->qty }}" readonly
                    id="rate_{{ $key }}" class="form-control" step="0.01" min="{{ $key }}">
            </td>

            <td style="width: 5%">
                <input style="width: 85px" type="text" readonly value="{{ $data->remarks }}"
                    id="remark_{{ $key }}" class="form-control">
                <input type="hidden" name="remarks[]" value="{{ $data->remarks }}">
            </td>
            
            <td>
                <button type="button" class="btn btn-danger btn-sm removeRowBtn"
                    onclick="remove({{ $key }})" data-id="{{ $key }}">Remove</button>
            </td>
        </tr>
    @empty
        <tr>
            <td colspan="7" class="text-center text-muted">No pending quotations against {{ $purchaseRequest->purchase_request_no }}.</td>
        </tr>
    @endforelse
</tbody>


         </table>
     </div>
 </div>
 <input type="hidden" id="rowCount" value="0">
@if ($PurchaseQuotationData->isNotEmpty())
    <div class="row">
        <div class="col-12">
            <x-approval-status :model="$data1" />
        </div>
    </div>
@endif <div class="row bottom-button-bar">
     <div class="col-12">
         <a type="button" class="btn btn-danger modal-sidebar-close position-relative top-1 closebutton">Close</a>
         {{-- <button type="submit" class="btn btn-primary submitbutton">Save</button> --}}
     </div>
 </div>

 <script>
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
             url: "{{ route('store.purchase-quotation.approve-item') }}",
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
