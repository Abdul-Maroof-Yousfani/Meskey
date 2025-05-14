 <form action="{{ route('raw-material.gate-buying.update', $arrivalPurchaseOrder->id) }}" method="POST" id="ajaxSubmit"
     autocomplete="off">
     @csrf
     @method('PUT')
     <input type="hidden" id="listRefresh" value="{{ route('raw-material.get.gate-buying') }}" />
     <input type="hidden" name="purchase_type" value="gate_buying" />
     <input type="hidden" name="company_id" value="{{ $arrivalPurchaseOrder->company_id }}" />

     <div class="row form-mar">
         <div class="col-xs-6 col-sm-6 col-md-6">
             <div class="form-group">
                 <label>Location:</label>
                 <input type="hidden" name="company_location_id"
                     value="{{ $arrivalPurchaseOrder->company_location_id }}">
                 <select name="company_location_id_for_display" id="company_location_id" disabled class="form-control">
                     <option value="{{ $arrivalPurchaseOrder->company_location_id }}" selected>
                         {{ $arrivalPurchaseOrder->location->name ?? 'N/A' }}
                     </option>
                 </select>
             </div>
         </div>
         <div class="col-xs-6 col-sm-6 col-md-6">
             <div class="form-group">
                 <label>Contract Date:</label>
                 <input type="date" name="contract_date"
                     value="{{ $arrivalPurchaseOrder->contract_date->format('Y-m-d') }}" class="form-control"
                     readonly />
             </div>
         </div>
     </div>

     <div class="row form-mar">
         <div class="col-xs-6 col-sm-6 col-md-6">
             <div class="form-group">
                 <label>Ref No:</label>
                 <input type="text" name="ref_no" value="{{ $arrivalPurchaseOrder->ref_no }}"
                     class="form-control" />
             </div>
         </div>
         <div class="col-xs-6 col-sm-6 col-md-6">
             <div class="form-group">
                 <label>S No:</label>
                 <input type="text" name="contract_no" readonly value="{{ $arrivalPurchaseOrder->contract_no }}"
                     class="form-control" />
             </div>
         </div>
     </div>

     <div class="row form-mar">
         <div class="col-xs-12 col-sm-12 col-md-12">
             <div class="form-group">
                 <label>Supplier Name:</label>
                 <input type="text" name="supplier_name" value="{{ $arrivalPurchaseOrder->supplier_name }}"
                     class="form-control" />
             </div>
         </div>
     </div>

     <div class="row form-mar">
         <div class="col-xs-12 col-sm-12 col-md-12">
             <div class="form-group">
                 <label>Purchaser Name:</label>
                 <input type="text" name="purchaser_name" value="{{ $arrivalPurchaseOrder->purchaser_name }}"
                     class="form-control" />
             </div>
         </div>
     </div>

     <div class="row form-mar">
         <div class="col-xs-6 col-sm-6 col-md-6">
             <div class="form-group">
                 <label>Contact Person Name:</label>
                 <input type="text" name="contact_person_name"
                     value="{{ $arrivalPurchaseOrder->contact_person_name }}" class="form-control" />
             </div>
         </div>
         <div class="col-xs-6 col-sm-6 col-md-6">
             <div class="form-group">
                 <label>Mobile No:</label>
                 <input type="text" name="mobile_no" value="{{ $arrivalPurchaseOrder->mobile_no }}"
                     class="form-control" />
             </div>
         </div>
     </div>

     <div class="row">
         <div class="col-12">
             <h6 class="header-heading-sepration">
                 Broker
             </h6>
         </div>
         <div class="col-xs-8 col-sm-8 col-md-8">
             <div class="form-group">
                 <label>Broker:</label>
                 <select name="broker_one_id" id="broker_id" class="form-control">
                     <option value="">Select Broker</option>
                     <option value="{{ $arrivalPurchaseOrder->broker_one_id }}" selected>
                         {{ $arrivalPurchaseOrder->broker->name ?? 'N/A' }}
                     </option>
                 </select>
             </div>
         </div>
         <div class="col-xs-4 col-sm-4 col-md-4">
             <div class="form-group">
                 <label>Commission:</label>
                 <input type="number" name="broker_one_commission" step="0.01"
                     value="{{ $arrivalPurchaseOrder->broker_one_commission }}" class="form-control" />
             </div>
         </div>
     </div>

     <div class="row form-mar">
         <div class="col-12">
             <div class="form-group">
                 <label>Commodity:</label>
                 <select name="product_id" id="product_id" class="form-control select2">
                     <option value="">Select Commodity</option>
                     @foreach ($products as $product)
                         <option value="{{ $product->id }}"
                             data-bag-weight="{{ $product->bag_weight_for_purchasing }}"
                             {{ $arrivalPurchaseOrder->product_id == $product->id ? 'selected' : '' }}>
                             {{ $product->name }}
                         </option>
                     @endforeach
                 </select>
             </div>
         </div>
         <div id="slabsContainer" class="col-xs-12 col-sm-12 col-md-12">
             {!! $slabsHtml !!}
         </div>
     </div>

     <div class="row">
         <div class="col-12">
             <h6 class="header-heading-sepration">
                 Rate
             </h6>
         </div>
         <div class="col-xs-4 col-sm-4 col-md-4">
             <div class="form-group">
                 <label>Rate Per KG:</label>
                 <input type="number" name="rate_per_kg" value="{{ $arrivalPurchaseOrder->rate_per_kg }}"
                     class="form-control" />
             </div>
         </div>
         <div class="col-xs-4 col-sm-4 col-md-4">
             <div class="form-group">
                 <label>Rate Per Mound:</label>
                 <input type="number" name="rate_per_mound" value="{{ $arrivalPurchaseOrder->rate_per_mound }}"
                     class="form-control" />
             </div>
         </div>
         <div class="col-xs-4 col-sm-4 col-md-4">
             <div class="form-group">
                 <label>Rate Per 100KG:</label>
                 <input type="number" name="rate_per_100kg" value="{{ $arrivalPurchaseOrder->rate_per_100kg }}"
                     class="form-control" />
             </div>
         </div>
     </div>

     <div class="row form-mar">
         <div class="col-xs-6 col-sm-6 col-md-6">
             <div class="form-group">
                 <label>Truck No:</label>
                 <input type="text" name="truck_no" value="{{ $arrivalPurchaseOrder->truck_no }}"
                     class="form-control" />
             </div>
         </div>
         <div class="col-xs-6 col-sm-6 col-md-6">
             <div class="form-group">
                 <label>Payment Term:</label>
                 <select name="payment_term" class="form-control select2">
                     <option value="Cash Payment"
                         {{ $arrivalPurchaseOrder->payment_term == 'Cash Payment' ? 'selected' : '' }}>
                         Cash Payment</option>
                     <option value="Cheque" {{ $arrivalPurchaseOrder->payment_term == 'Cheque' ? 'selected' : '' }}>
                         Cheque</option>
                     <option value="Online" {{ $arrivalPurchaseOrder->payment_term == 'Online' ? 'selected' : '' }}>
                         Online</option>
                 </select>
             </div>
         </div>
     </div>

     <div class="row form-mar">
         <div class="col-xs-12 col-sm-12 col-md-12">
             <div class="form-group">
                 <label>Remarks:</label>
                 <textarea name="remarks" class="form-control">{{ $arrivalPurchaseOrder->remarks }}</textarea>
             </div>
         </div>
     </div>

     <div class="row form-mar">
         <div class="col-xs-12 col-sm-12 col-md-12">
             <div class="form-group">
                 <label>Prepared By:</label>
                 <input type="text" value="{{ auth()->user()->name }}" disabled class="form-control" />
                 <input type="hidden" name="created_by" value="{{ auth()->user()->id }}" readonly
                     class="form-control" />
             </div>
         </div>
     </div>

     <div class="row bottom-button-bar">
         <div class="col-12">
             <a href="{{ route('raw-material.gate-buying.index') }}" class="btn btn-danger">Close</a>
             <button type="submit" class="btn btn-primary">Update</button>
         </div>
     </div>
 </form>

 <script>
     $(document).ready(function() {
         $('.select2').select2();

         $('[name="company_location_id"], [name="contract_date"]').change(function() {
             generateContractNumber();
         });

         function generateContractNumber() {
             const locationId = $('[name="company_location_id"]').val();
             const contractDate = $('[name="contract_date"]').val();

             if (locationId && contractDate) {
                 $.ajax({
                     url: '{{ route('raw-material.generate.contract.number') }}',
                     type: 'POST',
                     data: {
                         _token: '{{ csrf_token() }}',
                         location_id: locationId,
                         contract_date: contractDate
                     },
                     success: function(response) {
                         if (response.success) {
                             $('[name="contract_no"]').val(response.contract_no);
                         }
                     },
                     error: function(xhr) {
                         console.error(xhr.responseText);
                     }
                 });
             }
         }

         $('#product_id').change(function() {
             var product_id = $(this).val();
             var company_id = $('[name="company_id"]').val();

             if (product_id && company_id) {
                 $.ajax({
                     url: '{{ route('raw-material.getGateBuyingMainSlabByProduct') }}',
                     type: 'GET',
                     data: {
                         product_id: product_id,
                         company_id: company_id
                     },
                     beforeSend: function() {
                         Swal.fire({
                             title: "Processing...",
                             text: "Please wait while fetching slabs.",
                             allowOutsideClick: false,
                             didOpen: () => {
                                 Swal.showLoading();
                             }
                         });
                     },
                     success: function(response) {
                         Swal.close();
                         if (response.success) {
                             $('#slabsContainer').html(response.html);
                         } else {
                             Swal.fire("No Data", "No slabs found for this product.",
                                 "info");
                         }
                     },
                     error: function() {
                         Swal.close();
                         Swal.fire("Error", "Something went wrong. Please try again.",
                             "error");
                     }
                 });
             }
         });

         const KG_PER_MOUND = 40;
         const KG_PER_100KG = 100;

         function calculateRates(changedField) {
             const ratePerKg = parseFloat($('[name="rate_per_kg"]').val()) || 0;
             const ratePerMound = parseFloat($('[name="rate_per_mound"]').val()) || 0;
             const ratePer100kg = parseFloat($('[name="rate_per_100kg"]').val()) || 0;

             switch (changedField) {
                 case 'rate_per_kg':
                     $('[name="rate_per_mound"]').val((ratePerKg * KG_PER_MOUND).toFixed(2));
                     $('[name="rate_per_100kg"]').val((ratePerKg * KG_PER_100KG).toFixed(2));
                     break;

                 case 'rate_per_mound':
                     $('[name="rate_per_kg"]').val((ratePerMound / KG_PER_MOUND).toFixed(2));
                     $('[name="rate_per_100kg"]').val((ratePerMound / KG_PER_MOUND * KG_PER_100KG).toFixed(2));
                     break;

                 case 'rate_per_100kg':
                     $('[name="rate_per_kg"]').val((ratePer100kg / KG_PER_100KG).toFixed(2));
                     $('[name="rate_per_mound"]').val((ratePer100kg / KG_PER_100KG * KG_PER_MOUND).toFixed(2));
                     break;
             }
         }

         $('[name="rate_per_kg"]').on('input', function() {
             calculateRates('rate_per_kg');
         });

         $('[name="rate_per_mound"]').on('input', function() {
             calculateRates('rate_per_mound');
         });

         $('[name="rate_per_100kg"]').on('input', function() {
             calculateRates('rate_per_100kg');
         });

         initializeDynamicSelect2('#broker_id', 'brokers', 'name', 'id', true, false);
         initializeDynamicSelect2('#company_location_id', 'company_locations', 'name', 'id', true, false);
     });
 </script>
