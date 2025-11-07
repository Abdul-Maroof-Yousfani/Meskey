 <input type="hidden" id="listRefresh" value="{{ route('store.get.purchase-order') }}" />
 {{-- <input type="hidden" name="data_id" value="{{ $purchaseOrder->purchase_orde->id }}"> --}}
{{-- <input type="hidden" name="purchase_request_data_id"
    value="{{ optional($purchaseOrder->orde_data->first())->purchase_request_data_id }}"> --}}

 <div class="row form-mar">
     <div class="col-md-3">
         <div class="form-group">
             <label>Purchase Request:</label>
             <select readonly class="form-control" onchange="get_purchase(this.value)" name="purchase_request_id">
                 <option value="{{ optional($purchaseOrder->purchase_request)->id }}<">
                     {{ optional($purchaseOrder->purchase_request)->purchase_request_no }}
                 </option>
             </select>
         </div>
     </div>

     <div class="col-md-3">
            <div class="form-group">
                <label class="form-label">Supplier:</label>
                <select disabled id="supplier_id" name="supplier_id" class="form-control item-select select2">
                    <option value="">Select Vendor</option>
                    @foreach (get_supplier() as $supplier)
                        <option value="{{ $supplier->id }}"
                        {{ $supplier->id == $purchaseOrder->supplier_id ? 'selected' : '' }}>
                            {{ $supplier->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

<div class="col-md-3">
            <div class="form-group">
                <label class="form-label">Quotation</label>
                <input type="text" name="quotation_no" id="quotation_no" class="form-control"
                    placeholder="-" value="{{ $purchaseOrder->purchase_quotation->purchase_quotation_no ?? null }}" readonly>
            </div>
        </div>
     
     <div class="col-md-3">
         <div class="form-group">
             <label>Purchase Order Date:</label>
             <input readonly type="date" id="purchase_date"
                 value="{{ $purchaseOrder->order_date }}" name="purchase_date"
                 class="form-control">
         </div>
     </div>
     <div class="col-md-3">
            <div class="form-group">
                <label class="form-label">Reference No:</label>
                <input type="text" name="reference_no" value="{{ $purchaseOrder->reference_no }}" placeholder="Please select location and date." readonly
                    id="reference_no" class="form-control">
            </div>
        </div>
     
     <div class="col-md-3">
         <div class="form-group">
             <label>Location:</label>
             <select disabled name="company_location" id="company_location_id" class="form-control select2">
                 <option value="">Select Location</option>
                 @foreach (get_locations() as $loc)
                     <option
                         {{ optional($purchaseOrder)->location_id == $loc->id ? 'selected' : '' }}
                         value="{{ $loc->id }}">{{ $loc->name }}</option>
                 @endforeach
                 <input type="hidden" name="location_id"
                     value="{{ optional($purchaseOrder)->location_id }}" id="location_id">
             </select>
         </div>
     </div>
     
      <div class="col-md-3">
            <div class="form-group">
                <label>Payment Term:</label>
                <select disabled name="payment_term_id" id="payment_term_id" class="form-control select2">
                    <option value="">Select Payment Term</option>
                    @foreach ($payment_terms as $payment_term)
                        <option value="{{ $payment_term->id }}" {{ $purchaseOrder->payment_term_id == $payment_term->id ? 'selected' : '' }}>{{ $payment_term->desc }}</option>
                    @endforeach
                </select>
            </div>
        </div>

     <div class="col-xs-12 col-sm-12 col-md-12">
         <div class="form-group">
             <label>Description (Optional):</label>
             <textarea readonly name="description" id="description" placeholder="Description" class="form-control">{{ $purchaseOrder->description }}</textarea>
         </div>
     </div>
 </div>
 <div class="row form-mar">
     <div class="col-md-12">
         <table class="table table-bordered" id="purchaseRequestTable">
             <thead>
                 <tr>
                    <th>Category</th>
                    <th>Item</th>
                    <th>Item UOM</th>
                    {{-- <th>Vendor</th> --}}
                    <th>Qty</th>
                    <th>Rate</th>
                    <th>Gross Amount</th>
                    <th>Tax Amount</th>
                    <th>Tax</th>
                    <th>Duty</th>
                    <th>Amount</th>
                    <th>Min Weight</th>
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
    @foreach ($purchaseOrderData ?? [] as $key => $data)
        <tr id="row_{{ $key }}">
            <td style="width: 15%">
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

            <td style="width: 15%">
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

            <td style="width: 8%">
                <input type="text" id="uom_{{ $key }}" class="form-control uom"
                    value="{{ get_uom($data->item_id) }}" disabled readonly>
                <input type="hidden" name="uom[]" value="{{ get_uom($data->item_id) }}">
            </td>

            {{-- <td style="width: 10%">
                <select id="supplier_id_{{ $key }}" name="supplier_id[]" disabled
                    class="form-control item-select select2" data-index="{{ $key }}">
                    <option value="">Select Vendor</option>
                    @foreach (get_supplier() as $supplier)
                        <option value="{{ $supplier->id }}" @selected($data->supplier_id == $supplier->id)>
                            {{ $supplier->name }}
                        </option>
                    @endforeach
                </select>
            </td> --}}

            <td style="width: 7%">
                <input type="number" onkeyup="calc({{ $key }})" disabled
                    onblur="calc({{ $key }})" value="{{ $data->qty }}"
                    id="qty_{{ $key }}" class="form-control" step="0.01" min="0">
                <input type="hidden" name="qty[]" value="{{ $data->qty }}">
            </td>

            <td style="width: 7%">
                <input type="number" onkeyup="calc({{ $key }})"
                    onblur="calc({{ $key }})" name="rate[]" value="{{ $data->rate }}" disabled
                    id="rate_{{ $key }}" class="form-control" step="0.01" min="{{ $key }}">
            </td>

            <td style="width: 7%">
                <input type="number" onkeyup="calc({{ $key }})"
                    onblur="calc({{ $key }})" name="rate[]" value="{{ $data->rate * $data->qty }}" disabled
                    id="rate_{{ $key }}" class="form-control gross_amount" step="0.01" min="{{ $key }}">
            </td>
<td style="width: 10%">
                                <select id="tax_id_{{ $key }}" name="tax_id[]"
                                    onchange="calculatePercentage(this)" class="form-control item-select select2">
                                    <option value="">Select Tax</option>
                                    @foreach ($taxes as $tax)
                                        <option value="{{ $tax->id }}" data-percentage="{{ $tax->percentage }}" {{ $tax->id == $data->tax_id ? 'selected' : '' }}>
                                            {{ $tax->name . ' (' . $tax->percentage . ')%' }}
                                        </option>
                                    @endforeach
                                </select>
                            </td>

                            <td style="width: 7%">
                                <input type="number" onkeyup="calc({{ $key }})"
                                    onblur="calc({{ $key }})" name="rate[]" value="{{ ($tax->percentage / 100) * ($data->rate * $data->qty) }}" disabled
                                    id="rate_{{ $key }}" class="form-control percent_amount" step="0.01" min="{{ $key }}">
                            </td>

                            <td style="width: 7%">
                                <input type="number" oninput="calc({{ $key }})" name="excise_duty[]" disabled value="{{ $data->excise_duty }}"
                                    id="excise_duty_{{ $key }}" class="form-control" step="0.01" min="0">
                            </td>
            <td style="width: 10%">
                <input type="number" readonly value="{{ $data->total }}"
                    id="total_{{ $key }}" class="form-control net_amount" step="0.01" min="0" readonly name="total[]">
            </td>
 <td style="width: 5%">
                                <input type="number" readonly name="min_weight[]" value="{{ $data->min_weight }}"
                                    id="min_weight_{{ $key }}" class="form-control" step="0.01" min="0">
                            </td>
                            <td style="width: 5%">
                                <input type="text" readonly name="color[]" value="{{ $data->color }}"
                                    id="color_{{ $key }}" class="form-control" step="0.01" min="0">
                            </td>
                            <td style="width: 5%">
                                <input type="text" readonly name="construction_per_square_inch[]" value="{{ $data->construction_per_square_inch }}"
                                    id="construction_per_square_inch_{{ $key }}" class="form-control" step="0.01" min="0">
                            </td>
                            <td style="width: 7%">
                                <input type="text" readonly name="size[]" value="{{ $data->size }}"
                                    id="size_{{ $key }}" class="form-control" step="0.01" min="0">
                            </td>
                            <td style="width: 5%">
                                <input type="text" readonly name="stitching[]" value="{{ $data->stitching }}"
                                    id="stitching_{{ $key }}" class="form-control" step="0.01" min="0">
                            </td>
                            <td style="width: 5%">
                                <div class="loop-fields">
                                    <div class="form-group mb-0">
                                        {{-- <input type="file" name="printing_sample[]" id="printing_sample_{{ $key }}"
                                            class="form-control" accept="image/*,application/pdf" placeholder="Printing Sample"> --}}

                                        <input type="hidden" style="display: none;" name="printing_sample[]" id="printing_sample_{{ $key }}" value="{{ $data->printing_sample }}"
                                            class="form-control" accept="image/*,application/pdf" placeholder="Printing Sample">

                                        @if (!empty($data->printing_sample))
                                            <small>
                                                <a href="{{ asset('storage/' . $data->printing_sample) }}" target="_blank">
                                                    View existing file
                                                </a>
                                            </small>
                                            @else
                                            <span>No Attach.</span>
                                        @endif
                                    </div>
                                </div>
                            </td>
            <td style="width: 10%">
                <input type="text" readonly value="{{ $data->remarks }}"
                    id="remark_{{ $key }}" class="form-control">
                <input type="hidden" name="remarks[]" value="{{ $data->remarks }}">
            </td>

            <td>
                <button type="button" class="btn btn-danger btn-sm removeRowBtn"
                    onclick="remove({{ $key }})" disabled data-id="{{ $key }}">Remove</button>
            </td>
        </tr>
    @endforeach
</tbody>

         </table>
     </div>
 </div>

 <div class="row form-mar">
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Other Terms:</label>
                <textarea disabled name="other_term" id="other_term" placeholder="Other Terms" class="form-control">{{ $purchaseOrder->other_terms }}</textarea>
            </div>
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
             url: "{{ route('store.purchase-order.approve-item') }}",
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

     function calculatePercentage(el) {
        
        const gross_amount = $(el).closest("tr").find(".gross_amount");
        const tax_percent = $(el).find(":selected").data("percentage");
        const percent_amount = $(el).closest("tr").find(".percent_amount");
        const net_amount = $(el).closest("tr").find(".net_amount");

        const percent_amount_of_gross = (parseFloat(tax_percent) / 100) * parseFloat(gross_amount.val());
        const net_amount_value = parseFloat(gross_amount.val()) + parseFloat(percent_amount_of_gross);
        percent_amount.val(percent_amount_of_gross);
        net_amount.val(net_amount_value)


     }
 </script>
