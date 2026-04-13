<div id="modal_purchase_comparison_main">
 <input type="hidden" id="listRefresh" value="{{ route('store.get.purchase-quotation') }}" />
 {{-- <input type="hidden" name="data_id" value="{{ $purchaseRequest->purchase_quotation->id }}"> --}}
 {{-- <input type="hidden" name="purchase_request_data_id"
    value="{{ optional($purchaseRequest->quotation_data->first())->purchase_request_data_id }}"> --}}

<div class="row form-mar">
    <div class="col-md-3">
        <div class="form-group">
            <label>Purchase Request:</label>
            <input type="text" readonly class="form-control" value="{{ $purchaseRequest->purchase_request_no }}">
            <input type="hidden" id="filter_pr_id" value="{{ $purchaseRequest->id }}">
        </div>
    </div>
    <div class="col-md-3">
        <div class="form-group">
            <label>Filter by Supplier:</label>
            <select id="filter_supplier" class="form-control select2" multiple onchange="applyFilters()">
                @php
                    $allSuppliers = \App\Models\Master\Supplier::whereIn('id', 
                        \App\Models\Procurement\Store\PurchaseQuotationData::whereIn('purchase_request_data_id', 
                            $purchaseRequest->PurchaseData->pluck('id')
                        )->pluck('supplier_id')
                    )->get();
                @endphp
                @foreach ($allSuppliers as $supplier)
                    <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="col-md-3">
        <div class="form-group">
            <label>Filter by Category:</label>
            <select id="filter_category" class="form-control select2" multiple onchange="applyFilters()">
                @foreach ($categories as $cat)
                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="col-md-3">
        <div class="form-group">
            <label>Filter by Location:</label>
            <select id="filter_location" class="form-control select2" multiple onchange="applyFilters()">
                @foreach (get_locations() as $loc)
                    <option value="{{ $loc->id }}">{{ $loc->name }}</option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="col-md-3">
        <div class="form-group">
            <label>Status Filter:</label>
            <select id="filter_status" class="form-control select2" multiple onchange="applyFilters()">
                <option value="pending">Pending</option>
                <option value="approved">Approved</option>
                <option value="rejected">Rejected</option>
                <option value="neglected">Neglected</option>
                <option value="returned">Returned</option>
            </select>
        </div>
    </div>
</div>
<div id="comparison_table_container">
 <div class="row form-mar">
     <div class="col-md-12">
         <div style="overflow-x: auto; white-space: nowrap; width: 100%;">
            <table class="table table-bordered" id="purchaseRequestTable">
                <thead>
                    <tr>
                        <th style="min-width: 250px;">Item</th>
                        <th style="min-width: 100px;">UOM</th>
                        <th style="min-width: 100px;">Req. Qty</th>
                        <th style="min-width: 250px;">Supplier</th>
                        <th style="min-width: 150px;">PQ No</th>
                        <th style="min-width: 100px;">Quoted Qty</th>
                        <th style="min-width: 120px;">Rate</th>
                        <th style="min-width: 120px;">Amount</th>
                        <th style="min-width: 150px;">Delivery Date</th>
                        <th style="min-width: 200px;">Job Order</th>
                        <th style="min-width: 120px;">Status</th>
                        <th style="min-width: 150px;">Tolerance</th>
                        <th style="min-width: 150px;">Tolerance %</th>
                        <th style="min-width: 200px;">Remarks</th>
                    </tr>
                </thead>
                <tbody id="purchaseRequestBody">
                    @forelse ($groupedItems ?? [] as $prDataId => $group)
                        @foreach ($group['quotations'] as $index => $data)
                            <tr id="row_{{ $prDataId }}_{{ $index }}" 
                                class="quotation-row"
                                data-pr-data-id="{{ $prDataId }}"
                                data-category-id="{{ $data->category_id }}"
                                data-supplier-id="{{ $data->supplier_id }}"
                                data-status="{{ strtolower($data->am_approval_status) }}"
                                data-pr-qty="{{ $group['pr_data']->qty ?? 0 }}"
                                data-already-approved="{{ $group['pr_data']->purchase_quotation_data()->where('am_approval_status', 'approved')->sum('qty') }}"
                                data-item-name="{{ $data->item->name ?? 'Item' }}"
                                data-row-qty="{{ $data->qty }}">

                                @if ($index === 0)
                                    <td rowspan="{{ $group['rowspan'] }}" style="vertical-align: middle;">
                                        {{ $data->item->name ?? '-' }}
                                    </td>
                                    <td rowspan="{{ $group['rowspan'] }}" style="vertical-align: middle;">
                                        {{ get_uom($data->item_id) }}
                                    </td>
                                    <td rowspan="{{ $group['rowspan'] }}" style="vertical-align: middle;">
                                        {{ $group['pr_data']->qty ?? 0 }}
                                    </td>
                                @endif

                                <td class="{{ $index === 0 ? 'table-success' : '' }}" style="border-right: 1px solid #ddd;">{{ $data->supplier->name ?? '-' }}</td>
                                <td class="{{ $index === 0 ? 'table-success' : '' }}">{{ $data->purchase_quotation->purchase_quotation_no ?? '-' }}</td>
                                <td class="{{ $index === 0 ? 'table-success' : '' }}">{{ $data->qty }}</td>
                                <td class="{{ $index === 0 ? 'table-success' : '' }}"><strong>{{ $data->rate }}</strong></td>
                                <td class="{{ $index === 0 ? 'table-success' : '' }}">{{ number_format($data->qty * $data->rate, 2) }}</td>
                                <td class="{{ $index === 0 ? 'table-success' : '' }}">{{ $data->delivery_date ?? '-' }}</td>

                                @if ($index === 0)
                                    <td rowspan="{{ $group['rowspan'] }}" style="vertical-align: middle;">
                                        @foreach($group['pr_data']->JobOrder ?? [] as $jo)
                                            <span class="badge badge-light border">{{ $jo->job_order_data->job_order_no ?? '' }}</span><br>
                                        @endforeach
                                    </td>
                                @endif

                                <td>
                                    @php
                                        $badgeClass = match (strtolower($data->am_approval_status)) {
                                            'approved' => 'badge-success',
                                            'rejected' => 'badge-danger',
                                            'pending' => 'badge-warning',
                                            default => 'badge-secondary',
                                        };
                                    @endphp
                                    <span class="badge {{ $badgeClass }}">{{ $data->am_approval_status }}</span>
                                </td>
                                <td>{{ $data->purchase_request->tolerance ?? '-' }}</td>
                                <td>{{ $data->purchase_request->tolerance_percentage ?? '-' }}</td>
                                <td>{{ $data->remarks ?? '-' }}</td>
                            </tr>
                        @endforeach
                    @empty
                        <tr>
                            <td colspan="13" class="text-center text-muted">No quotation data available.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
         </div>
     </div>
 </div>
 </div>
 <input type="hidden" id="rowCount" value="0">
 @if (isset($PurchaseQuotationData) && $PurchaseQuotationData->isNotEmpty() && request()->routeIs('store.purchase-quotation.comparison-approvals'))
     <div class="row">
         <div class="col-12">
             <x-approval-status :model="$data1" />
         </div>
     </div>
 @endif

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

     // ✅ Initialize visibility on load for Data For Comparison
     $(document).ready(function() {
         const initialCategoryId = "{{ optional($purchaseRequest)->category_id ?? 0 }}";
         if (initialCategoryId) {
             toggleVisibility(initialCategoryId);
         }

          $('#check-all').on('change', function() {
              $('.item-checkbox').prop('checked', $(this).prop('checked'));
          });
     });

     function applyFilters() {
          let prId = $('#filter_pr_id').val();
          let suppliers = $('#filter_supplier').val();
          let categories = $('#filter_category').val();
          let statuses = $('#filter_status').val();
          let locations = $('#filter_location').val();

          $.ajax({
              url: '{{ route('store.purchase-quotation.dataForComparison', '') }}/' + prId,
              type: 'GET',
              data: {
                  supplier_ids: suppliers,
                  category_ids: categories,
                  statuses: statuses,
                  location_ids: locations
              },
              beforeSend: function() {
                  $('#comparison_table_container').css('opacity', '0.5');
              },
              success: function(response) {
                  // Resilient way to find the container in the response
                  let $newDom = $('<div>').append($.parseHTML(response));
                  let newContent = $newDom.find('#comparison_table_container').html();
                  
                  if (newContent) {
                      $('#comparison_table_container').html(newContent).css('opacity', '1');
                  } else {
                      console.error('Failed to find comparison_table_container in response');
                      $('#comparison_table_container').css('opacity', '1');
                  }
                  
                  // Re-initialize select2 if any was in the container (usually not, but safe)
                  $('.select2').select2({
                      placeholder: 'Please Select',
                      width: '100%'
                  });
              },
              error: function() {
                  $('#comparison_table_container').css('opacity', '1');
              }
          });
      }

    rowIndex = 1;

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
                <td style="width: 20%">
                    <select class="form-control select2" multiple disabled style="width: 100%"></select>
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

     // ✅ Toggle visibility for Bag-specific columns
     function toggleVisibility(categoryId) {
         const bagCategoryIds = [11, 38]; // "Bags" category IDs are 11 and 38
         const isBag = bagCategoryIds.includes(parseInt(categoryId));

         if (isBag) {
             $('.bag-only').show();
         } else {
             $('.bag-only').hide();
         }
     }
  </script>
</div>
