 <form action="{{ route('raw-material.purchase-order.update', $arrivalPurchaseOrder->id) }}" method="POST" id="ajaxSubmit"
     autocomplete="off">
     @csrf
     @method('PUT')
     <input type="hidden" id="listRefresh" value="{{ route('raw-material.get.purchase-order') }}" />
     <input type="hidden" name="id" value="{{ $arrivalPurchaseOrder->id }}">
     <input type="hidden" name="company_id" value="{{ $arrivalPurchaseOrder->company_id }}">

     <div class="row form-mar">
         <div class="col-xs-6 col-sm-6 col-md-6">
             <div class="form-group">
                 <label>Location:</label>
                 <input type="hidden" name="company_location_id"
                     value="{{ $arrivalPurchaseOrder->company_location_id }}">
                 <select name="company_location_id_for_display" id="company_location_id" class="form-control" disabled>
                     <option value="{{ $arrivalPurchaseOrder->company_location_id }}" selected>
                         {{ $arrivalPurchaseOrder->location->name ?? 'N/A' }}</option>
                 </select>
             </div>
         </div>
         <div class="col-xs-6 col-sm-6 col-md-6">
             <div class="form-group">
                 <label>Contract Date:</label>
                 <input type="date" name="contract_date"
                     value="{{ isset($arrivalPurchaseOrder->contract_date) ? $arrivalPurchaseOrder->contract_date->format('Y-m-d') : null }}"
                     class="form-control" readonly />
             </div>
         </div>

         <div class="col-xs-6 col-sm-6 col-md-6">
             <div class="form-group">
                 <label>Contract No:</label>
                 <input type="text" readonly name="contract_no" value="{{ $arrivalPurchaseOrder->contract_no }}"
                     class="form-control" />
             </div>
         </div>
         <div class="col-xs-6 col-sm-6 col-md-6">
             <div class="form-group">
                 <label>Sauda Type:</label>
                 <select name="sauda_type_id" id="sauda_type_id" class="form-control ">
                     <option value="{{ $arrivalPurchaseOrder->saudaType->id ?? null }}">
                         {{ $arrivalPurchaseOrder->saudaType?->name ?? 'Sauda Type Name' }}</option>
                 </select>
             </div>
         </div>
     </div>

     <div class="row">
         <div class="col-12">
             <h6 class="header-heading-sepration">
                 Supplier
             </h6>
         </div>
         <div class="col-xs-8 col-sm-8 col-md-8">
             <div class="form-group">
                 <label>Supplier:</label>
                 <select name="supplier_id" id="supplier_id" class="form-control ">

                     <option value="{{ $arrivalPurchaseOrder->supplier->id ?? null }}" selected>
                         {{ $arrivalPurchaseOrder->supplier->name ?? 'Supplier' }}</option>
                 </select>
             </div>
         </div>
         <div class="col-xs-4 col-sm-4 col-md-4">
             <div class="form-group">
                 <label>Commission:</label>
                 <input type="number" name="supplier_commission"
                     value="{{ $arrivalPurchaseOrder->supplier_commission }}" placeholder="Commission"
                     class="form-control" />
             </div>
         </div>
     </div>

     <div class="row">
         <div class="col-12">
             <h6 class="header-heading-sepration">
                 Broker 1
             </h6>
         </div>
         <div class="col-xs-8 col-sm-8 col-md-8">
             <div class="form-group">
                 <label>Broker:</label>
                 <select name="broker_one_id" id="broker_one_id" class="form-control ">
                     <option value="{{ $arrivalPurchaseOrder->broker->id ?? null }}" selected>
                         {{ $arrivalPurchaseOrder->broker->name ?? 'Broker' }}</option>
                 </select>
             </div>
         </div>
         <div class="col-xs-4 col-sm-4 col-md-4">
             <div class="form-group">
                 <label>Commission:</label>
                 <input type="number" name="broker_one_commission"
                     value="{{ $arrivalPurchaseOrder->broker_one_commission }}" placeholder="Commission"
                     class="form-control" />
             </div>
         </div>
     </div>

     <div class="row">
         <div class="col-12">
             <h6 class="header-heading-sepration">
                 Broker 2
             </h6>
         </div>
         <div class="col-xs-8 col-sm-8 col-md-8">
             <div class="form-group">
                 <label>Broker:</label>
                 <select name="broker_two_id" id="broker_two_id" class="form-control ">
                     <option value="{{ $arrivalPurchaseOrder->brokerTwo->id ?? null }}" selected>
                         {{ $arrivalPurchaseOrder->brokerTwo->name ?? 'Broker' }}</option>
                 </select>
             </div>
         </div>
         <div class="col-xs-4 col-sm-4 col-md-4">
             <div class="form-group">
                 <label>Commission:</label>
                 <input type="number" name="broker_two_commission"
                     value="{{ $arrivalPurchaseOrder->broker_two_commission }}" placeholder="Commission"
                     class="form-control" />
             </div>
         </div>
     </div>

     <div class="row">
         <div class="col-12">
             <h6 class="header-heading-sepration">
                 Broker 3
             </h6>
         </div>
         <div class="col-xs-8 col-sm-8 col-md-8">
             <div class="form-group">
                 <label>Broker:</label>
                 <select name="broker_three_id" id="broker_three_id" class="form-control ">
                     <option value="{{ $arrivalPurchaseOrder->brokerThree->id ?? null }}" selected>
                         {{ $arrivalPurchaseOrder->brokerThree->name ?? 'Broker' }}</option>
                 </select>
             </div>
         </div>
         <div class="col-xs-4 col-sm-4 col-md-4">
             <div class="form-group">
                 <label>Commission:</label>
                 <input type="number" name="broker_three_commission"
                     value="{{ $arrivalPurchaseOrder->broker_three_commission }}" placeholder="Commission"
                     class="form-control" />
             </div>
         </div>
     </div>

     <div class="row">
         <div class="col-xs-12 col-sm-12 col-md-12">
             <div class="form-group">
                 <label>Commodity:</label>
                 <select name="product_id" id="product_id" class="form-control select2">
                     <option value="">Commodity</option>
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
         <div class="col-xs-12 col-sm-12 col-md-12">
             <div class="form-group">
                 <label>Line:</label>
                 <select name="line_type" id="line_type" class="form-control select2">
                     <option value="">Select line</option>
                     <option value="bari" {{ $arrivalPurchaseOrder->line_type == 'bari' ? 'selected' : '' }}>Bari
                     </option>
                     <option value="choti" {{ $arrivalPurchaseOrder->line_type == 'choti' ? 'selected' : '' }}>Choti
                     </option>
                 </select>
             </div>
         </div>
         <div class="col-xs-6 col-sm-6 col-md-6">
             <div class="form-group">
                 <label>Bags Weight (kg):</label>
                 <input type="number" name="bag_weight" value="{{ $arrivalPurchaseOrder->bag_weight }}"
                     placeholder="Bags Weight (kg)" class="form-control" />
             </div>
         </div>
         <div class="col-xs-6 col-sm-6 col-md-6">
             <div class="form-group">
                 <label>Bags Rate:</label>
                 <input type="number" name="bag_rate" value="{{ $arrivalPurchaseOrder->bag_rate }}"
                     placeholder="Bags Rate" class="form-control" />
             </div>
         </div>
         <div class="col-xs-6 col-sm-6 col-md-6">
             <div class="form-group">
                 <label>Delivery Date:</label>
                 <input type="date" name="delivery_date"
                     value="{{ isset($arrivalPurchaseOrder->delivery_date) ? $arrivalPurchaseOrder->delivery_date->format('Y-m-d') : null }}"
                     placeholder="Delivery Date" class="form-control" />
             </div>
         </div>
         <div class="col-xs-6 col-sm-6 col-md-6">
             <div class="form-group">
                 <label>Credit Days:</label>
                 <input type="number" name="credit_days" value="{{ $arrivalPurchaseOrder->credit_days }}"
                     placeholder="Credit Days" class="form-control" />
             </div>
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
                     placeholder="Rate Per KG" class="form-control" />
             </div>
         </div>
         <div class="col-xs-4 col-sm-4 col-md-4">
             <div class="form-group">
                 <label>Rate Per Mound:</label>
                 <input type="number" name="rate_per_mound" value="{{ $arrivalPurchaseOrder->rate_per_mound }}"
                     placeholder="Rate Per Mound" class="form-control" />
             </div>
         </div>
         <div class="col-xs-4 col-sm-4 col-md-4">
             <div class="form-group">
                 <label>Rate Per 100KG:</label>
                 <input type="number" name="rate_per_100kg" value="{{ $arrivalPurchaseOrder->rate_per_100kg }}"
                     placeholder="Rate Per 100KG" class="form-control" />
             </div>
         </div>
     </div>

     <div class="row">
         <div class="col-12">
             <h6 class="header-heading-sepration">
                 Quantity Calculation
             </h6>
         </div>

         <div class="col-xs-4 col-sm-4 col-md-4">
             <div class="form-group">
                 <label>Type:</label>
                 <select name="calculation_type" id="calculation_type" class="form-control select2">
                     <option value="trucks"
                         {{ $arrivalPurchaseOrder->calculation_type == 'trucks' ? 'selected' : '' }}>
                         Trucks Wise</option>
                     <option value="quantity"
                         {{ $arrivalPurchaseOrder->calculation_type == 'quantity' ? 'selected' : '' }}>
                         Quantity Wise</option>
                 </select>
             </div>
         </div>
         <div class="col-xs-4 col-sm-4 col-md-4 fields-hidable"
             style="{{ $arrivalPurchaseOrder->calculation_type == 'quantity' ? 'display:none;' : '' }}">
             <div class="form-group">
                 <label for="truck_size_range">Truck Size Ranges:</label>
                 <select name="truck_size_range" id="truck_size_range" class="form-control select2">
                     @foreach ($truckSizeRanges as $range)
                         <option value="{{ $range->id }}" data-min="{{ $range->min_number }}"
                             data-max="{{ $range->max_number }}"
                             {{ $arrivalPurchaseOrder->truck_size_range_id == $range->id ? 'selected' : '' }}>
                             {{ $range->name }}
                         </option>
                     @endforeach
                 </select>
             </div>
         </div>

         <div class="col-xs-4 col-sm-4 col-md-4 fields-hidable"
             style="{{ $arrivalPurchaseOrder->calculation_type == 'quantity' ? 'display:none;' : '' }}">
             <div class="form-group">
                 <label>No of Trucks:</label>
                 <input type="number" name="no_of_trucks" id="no_of_trucks"
                     value="{{ $arrivalPurchaseOrder->no_of_trucks }}" placeholder="Number of Trucks"
                     class="form-control" min="1" />
                 <small class="text-muted">Each truck carries <span id="minMax">
                         @if ($arrivalPurchaseOrder->truckSizeRange)
                             {{ $arrivalPurchaseOrder->truckSizeRange->min_number }}-{{ $arrivalPurchaseOrder->truckSizeRange->max_number }}
                         @else
                             {{ $truckSizeRanges->first()->min_number }}-{{ $truckSizeRanges->first()->max_number }}
                         @endif
                     </span> kg</small>
             </div>
         </div>
         <div class="col-xs-4 col-sm-4 col-md-4" id="quantity-field"
             style="{{ $arrivalPurchaseOrder->calculation_type == 'trucks' ? 'display:none;' : '' }}">
             <div class="form-group">
                 <label>Total Quantity (kg):</label>
                 <input type="number" name="total_quantity" id="total_quantity"
                     value="{{ $arrivalPurchaseOrder->total_quantity }}" placeholder="Total Quantity"
                     class="form-control" min="25000" />
             </div>
         </div>

         <div class="col-xs-4 col-sm-4 col-md-4">
             <div class="form-group">
                 <label>Quantity Range:</label>
                 <input type="text" name="quantity_range" id="quantity_range"
                     value="{{ $arrivalPurchaseOrder->min_quantity }} - {{ $arrivalPurchaseOrder->max_quantity }} kg"
                     placeholder="Quantity Range" class="form-control" readonly />
             </div>
         </div>

         <div class="col-xs-4 col-sm-4 col-md-4">
             <div class="form-group">
                 <label>No of Bags Range:</label>
                 <input type="text" name="bags_range" id="bags_range"
                     value="{{ $arrivalPurchaseOrder->min_bags }} - {{ $arrivalPurchaseOrder->max_bags }} bags"
                     placeholder="Bags Range" class="form-control" readonly />
             </div>
         </div>
     </div>

     <div class="row">
         <div class="col-12">
             <h6 class="header-heading-sepration">
                 Others
             </h6>
         </div>
         <div class="col-xs-12 col-sm-12 col-md-12">
             <div class="form-group">
                 <label class="label-control font-weight-bold" for="lumpsum-toggle-initial">Replacement</label>
                 <div class="custom-control custom-switch">
                     <input type="checkbox" name="is_replacement" class="custom-control-input"
                         id="lumpsum-toggle-initial" {{ $arrivalPurchaseOrder->is_replacement ? 'checked' : '' }}>
                     <label class="custom-control-label" for="lumpsum-toggle-initial"></label>
                 </div>
             </div>
         </div>
         <div class="col-xs-6 col-sm-6 col-md-6">
             <div class="form-group">
                 <label>Weighbridge only From:</label>
                 <input type="text" name="weighbridge_from" value="{{ $arrivalPurchaseOrder->weighbridge_from }}"
                     placeholder="Weighbridge From" class="form-control" />
             </div>
         </div>
         <div class="col-xs-6 col-sm-6 col-md-6">

             <div class="form-group">
                 <label>Delivery Address:</label>
                 <input type="text" name="delivery_address" value="{{ $arrivalPurchaseOrder->delivery_address }}"
                     placeholder="Delivery Address" class="form-control" />
             </div>
         </div>
         <div class="col-xs-12 col-sm-12 col-md-12">
             <div class="form-group">
                 <label>Remarks (Optional):</label>
                 <textarea name="remarks" placeholder="Remarks" class="form-control">{{ $arrivalPurchaseOrder->remarks }}</textarea>
             </div>
         </div>
         <div class="col-xs-12 col-sm-12 col-md-12 d-none">
             <div class="form-group">
                 <input type="text" name="min_bags" id="minBags" value="{{ $arrivalPurchaseOrder->min_bags }}"
                     class="form-control" />
                 <input type="text" name="max_bags" id="maxBags" value="{{ $arrivalPurchaseOrder->max_bags }}"
                     class="form-control" />
                 <input name="min_quantity" id="minQty" value="{{ $arrivalPurchaseOrder->min_quantity }}"
                     class="form-control">
                 <input name="max_quantity" id="maxQty" value="{{ $arrivalPurchaseOrder->max_quantity }}"
                     class="form-control">
             </div>
         </div>
     </div>

     <div class="row bottom-button-bar">
         <div class="col-12">
             <a href="{{ route('raw-material.purchase-order.index') }}" class="btn btn-danger">Close</a>
             <button type="submit" class="btn btn-primary submitbutton">Update</button>
         </div>
     </div>
 </form>

 <script>
     $(document).ready(function() {
         $('.select2').select2();

         let TRUCK_MIN =
             {{ $arrivalPurchaseOrder->truckSizeRange ? $arrivalPurchaseOrder->truckSizeRange->min_number : $truckSizeRanges->first()->min_number }};
         let TRUCK_MAX =
             {{ $arrivalPurchaseOrder->truckSizeRange ? $arrivalPurchaseOrder->truckSizeRange->max_number : $truckSizeRanges->first()->max_number }};

         $('#product_id').change(function() {
             var product_id = $(this).val();
             if (product_id) {
                 $.ajax({
                     url: '{{ route('raw-material.getMainSlabByProduct') }}',
                     type: 'GET',
                     data: {
                         product_id: product_id,
                         company_id: '{{ $arrivalPurchaseOrder->company_id }}'
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
                             $('#slabsContainer').html('');
                         }
                     },
                     error: function() {
                         Swal.close();
                         Swal.fire("Error", "Something went wrong. Please try again.",
                             "error");
                     }
                 });
             } else {
                 $('#slabsContainer').html('');
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

         $('#truck_size_range').on('change', function() {
             const selectedOption = $(this).find('option:selected');
             const min = selectedOption.data('min');
             const max = selectedOption.data('max');

             if (min && max) {
                 TRUCK_MIN = min;
                 TRUCK_MAX = max;
                 $('#minMax').text(min.toLocaleString() + '-' + max.toLocaleString());
             } else {
                 TRUCK_MIN = 0;
                 TRUCK_MAX = 0;
                 $('#minMax').text('0-0');
             }
             calculateQuantityAndBags();
         });

         $('#calculation_type').change(function() {
             if ($(this).val() === 'trucks') {
                 $('.fields-hidable').show();
                 $('#quantity-field').hide();
             } else {
                 $('.fields-hidable').hide();
                 $('#quantity-field').show();
             }
             calculateQuantityAndBags();
         });

         $('#no_of_trucks, #total_quantity, #bag_weight, #product_id').on('input change', function() {
             calculateQuantityAndBags();
         });

         function calculateQuantityAndBags() {
             const bagWeight = $('#product_id option:selected').data('bag-weight') || 0;
             let minQuantity, maxQuantity;

             if ($('#calculation_type').val() === 'trucks') {
                 const trucks = parseInt($('#no_of_trucks').val()) || 0;
                 minQuantity = trucks * TRUCK_MIN;
                 maxQuantity = trucks * TRUCK_MAX;
             } else {
                 const quantity = parseInt($('#total_quantity').val()) || 0;
                 minQuantity = quantity;
                 maxQuantity = quantity;
             }

             const minBags = Math.ceil(minQuantity / bagWeight);
             const maxBags = Math.ceil(maxQuantity / bagWeight);

             if ($('#calculation_type').val() === 'trucks') {
                 $('#quantity_range').val(minQuantity.toLocaleString() + ' - ' + maxQuantity.toLocaleString() +
                     ' kg');
                 $('#bags_range').val(minBags.toLocaleString() + ' - ' + maxBags.toLocaleString() + ' bags');
             } else {
                 $('#quantity_range').val(minQuantity.toLocaleString() + ' kg');
                 $('#bags_range').val(minBags.toLocaleString() + ' - ' + maxBags.toLocaleString() + ' bags');
             }

             $('#minQty').val(minQuantity);
             $('#maxQty').val(maxQuantity);
             $('#minBags').val(minBags);
             $('#maxBags').val(maxBags);
         }

         initializeDynamicSelect2('#company_location_id', 'company_locations', 'name', 'id', true, false);
         initializeDynamicSelect2('#sauda_type_id', 'sauda_types', 'name', 'id', true, false);
         initializeDynamicSelect2('#supplier_id', 'suppliers', 'name', 'id', true, false);
         initializeDynamicSelect2('#broker_one_id', 'brokers', 'name', 'id', true, false);
         initializeDynamicSelect2('#broker_two_id', 'brokers', 'name', 'id', true, false);
         initializeDynamicSelect2('#broker_three_id', 'brokers', 'name', 'id', true, false);
     });
 </script>
