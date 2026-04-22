 <div class="view-only">
     <div class="row form-mar">
         <div class="col-xs-6 col-sm-6 col-md-6">
             <div class="form-group">
                 <label>Location:</label>
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
                 <select name="sauda_type_id" class="form-control" disabled>
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
                 <select name="supplier_id" id="supplier_id" class="form-control" disabled>
                     <option value="{{ $arrivalPurchaseOrder->supplier->id ?? null }}" selected>
                         {{ $arrivalPurchaseOrder->supplier->name ?? 'Supplier' }}</option>
                 </select>
             </div>
         </div>
         <div class="col-xs-4 col-sm-4 col-md-4">
             <div class="form-group">
                 <label>Commission:</label>
                 <input type="number" name="supplier_commission" placeholder="Commission (per KG)"
                     value="{{ $arrivalPurchaseOrder->supplier_commission }}" class="form-control" step="any" readonly />
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
             <div class="form-group ">
                 <label>Broker:</label>
                 <select name="broker_one_id" id="broker_one_id" class="form-control" disabled>
                     <option value="">{{ $arrivalPurchaseOrder->broker->name ?? 'N/A' }}</option>
                 </select>
             </div>
         </div>

         <div class="col-xs-4 col-sm-4 col-md-4">
             <div class="form-group ">
                 <label>Commission (per KG):</label>
                 <input type="number" value="{{ $arrivalPurchaseOrder->broker_one_commission }}"
                     id="broker_one_commission" name="broker_one_commission" placeholder="Commission (per KG)"
                     class="form-control" step="any" readonly />
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
             <div class="form-group ">
                 <label>Broker:</label>
                 <select name="broker_two_id" id="broker_two_id" class="form-control" disabled>
                     <option value="">{{ $arrivalPurchaseOrder->brokerTwo->name ?? 'N/A' }}</option>
                 </select>
             </div>
         </div>
         <div class="col-xs-4 col-sm-4 col-md-4">
             <div class="form-group ">
                 <label>Commission (per KG):</label>
                 <input type="number" value="{{ $arrivalPurchaseOrder->broker_two_commission }}"
                     id="broker_two_commission" name="broker_two_commission" placeholder="Commission (per KG)"
                     class="form-control" step="any" readonly />

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
             <div class="form-group ">
                 <label>Broker:</label>
                 <select name="broker_three_id" id="broker_three_id" class="form-control" disabled>
                     <option value="">{{ $arrivalPurchaseOrder->brokerThree->name ?? 'N/A' }}</option>
                 </select>
             </div>
         </div>
         <div class="col-xs-4 col-sm-4 col-md-4">
             <div class="form-group ">
                 <label>Commission (per KG):</label>
                 <input type="number" value="{{ $arrivalPurchaseOrder->broker_three_commission }}"
                     id="broker_three_commission" name="broker_three_commission" placeholder="Commission (per KG)"
                     class="form-control" step="any" readonly />

             </div>
         </div>
     </div>

     <div class="row">
         <div class="col-xs-12 col-sm-12 col-md-12">
             <div class="form-group">
                 <label>Commodity:</label>
                 <select name="product_id" id="product_id" class="form-control" disabled>
                     <option value="">{{ $arrivalPurchaseOrder->product->name ?? 'N/A' }}</option>
                 </select>
             </div>
         </div>
         <div id="slabsContainer" class="col-xs-12 col-sm-12 col-md-12">
             {!! $slabsHtml !!}
         </div>
         <div class="col-xs-12 col-sm-12 col-md-12">
             <div class="form-group">
                 <label>Line:</label>
                 <select name="line_type" id="line_type" class="form-control" disabled>
                     <option value="">{{ ucfirst($arrivalPurchaseOrder->line_type) }}</option>
                 </select>
             </div>
         </div>
         <div class="col-xs-6 col-sm-6 col-md-6">
             <div class="form-group">
                 <label>Bags Weight (kg):</label>
                 <input type="number" name="bag_weight" value="{{ $arrivalPurchaseOrder->bag_weight }}"
                     placeholder="Bags Weight (kg)" class="form-control" readonly />
             </div>
         </div>
         <div class="col-xs-6 col-sm-6 col-md-6">
             <div class="form-group">
                 <label>Bags Rate:</label>
                 <input type="number" name="bag_rate" value="{{ $arrivalPurchaseOrder->bag_rate }}"
                     placeholder="Bags Rate" class="form-control" readonly />
             </div>
         </div>
         <div class="col-xs-6 col-sm-6 col-md-6">
             <div class="form-group">
                 <label>Delivery Date:</label>
                 <input type="date" name="delivery_date"
                     value="{{ isset($arrivalPurchaseOrder->delivery_date) ? $arrivalPurchaseOrder->delivery_date->format('Y-m-d') : null }}"
                     placeholder="Delivery Date" class="form-control" readonly />
             </div>
         </div>
         <div class="col-xs-6 col-sm-6 col-md-6">
             <div class="form-group">
                 <label>Credit Days:</label>
                 <input type="number" name="credit_days" value="{{ $arrivalPurchaseOrder->credit_days }}"
                     placeholder="Credit Days" class="form-control" readonly />
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
                 <input type="number" step="0.01"name="rate_per_kg" value="{{ $arrivalPurchaseOrder->rate_per_kg }}"
                     placeholder="Rate Per KG" class="form-control" readonly />
             </div>
         </div>
         <div class="col-xs-4 col-sm-4 col-md-4">
             <div class="form-group">
                 <label>Rate Per Mound:</label>
                 <input type="number" step="0.01" name="rate_per_mound" value="{{ $arrivalPurchaseOrder->rate_per_mound }}"
                     placeholder="Rate Per Mound" class="form-control" readonly />
             </div>
         </div>
         <div class="col-xs-4 col-sm-4 col-md-4">
             <div class="form-group">
                 <label>Rate Per 100KG:</label>
                 <input type="number" step="0.01" name="rate_per_100kg" value="{{ $arrivalPurchaseOrder->rate_per_100kg }}"
                     placeholder="Rate Per 100KG" class="form-control" readonly />
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
                 <select name="calculation_type" id="calculation_type" class="form-control" disabled>
                     <option value="trucks">{{ $arrivalPurchaseOrder->calculation_type == 'trucks' ? 'Trucks Wise' : 'Quantity Wise' }}</option>
                 </select>
             </div>
         </div>
         <div class="col-xs-4 col-sm-4 col-md-4 fields-hidable"
             style="{{ $arrivalPurchaseOrder->calculation_type == 'quantity' ? 'display:none;' : '' }}">
             <div class="form-group">
                 <label for="truck_size_range">Truck Size Ranges:</label>
                 <select name="truck_size_range" id="truck_size_range" class="form-control" disabled>
                     <option value="">{{ $arrivalPurchaseOrder->truckSizeRange->name ?? 'N/A' }}</option>
                 </select>
             </div>
         </div>

         <div class="col-xs-4 col-sm-4 col-md-4 fields-hidable"
             style="{{ $arrivalPurchaseOrder->calculation_type == 'quantity' ? 'display:none;' : '' }}">
             <div class="form-group">
                 <label>No of Trucks:</label>
                 <input type="number" name="no_of_trucks" id="no_of_trucks"
                     value="{{ $arrivalPurchaseOrder->no_of_trucks }}" placeholder="Number of Trucks"
                     class="form-control" readonly />
             </div>
         </div>
         <div class="col-xs-4 col-sm-4 col-md-4" id="quantity-field"
             style="{{ $arrivalPurchaseOrder->calculation_type == 'trucks' ? 'display:none;' : '' }}">
             <div class="form-group">
                 <label>Total Quantity (kg):</label>
                 <input type="number" name="total_quantity" id="total_quantity"
                     value="{{ $arrivalPurchaseOrder->total_quantity }}" placeholder="Total Quantity"
                     class="form-control" readonly />
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
                 <label class="label-control font-weight-bold">Contract Condition:</label>
                 <div class="custom-control custom-radio">
                     <input type="radio" name="is_replacement" value="1" class="custom-control-input"
                         id="replacement-yes" {{ $arrivalPurchaseOrder->is_replacement == 1 ? 'checked' : '' }} disabled>
                     <label class="custom-control-label" for="replacement-yes">Replacement</label>
                 </div>
                 <div class="custom-control custom-radio">
                     <input type="radio" name="is_replacement" value="0" class="custom-control-input"
                         id="replacement-no" {{ $arrivalPurchaseOrder->is_replacement == 0 ? 'checked' : '' }} disabled>
                     <label class="custom-control-label" for="replacement-no">No Replacement</label>
                 </div>
             </div>
         </div>
         <div class="col-xs-4 col-sm-4 col-md-4">
             <div class="form-group">
                 <label>Weighbridge only From:</label>
                 <input type="text" name="weighbridge_from" value="{{ $arrivalPurchaseOrder->weighbridge_from }}"
                     placeholder="Weighbridge From" class="form-control" readonly />
             </div>
         </div>
         <div class="col-xs-4 col-sm-4 col-md-4">
             <div class="form-group">
                 <label>Delivery Address:</label>
                 <input type="text" name="delivery_address" value="{{ $arrivalPurchaseOrder->delivery_address }}"
                     placeholder="Delivery Address" class="form-control" readonly />
             </div>
         </div>
         <div class="col-xs-4 col-sm-4 col-md-4">
             <div class="form-group ">
                 <label>Division:</label>
                 <select name="division_id" id="division_id" class="form-control" disabled>
                     <option value="{{ $arrivalPurchaseOrder->division->id ?? '' }}" selected>
                         {{ $arrivalPurchaseOrder->division->name ?? 'Select Division' }} </option>
                 </select>
             </div>
         </div>
         <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group ">
                <label>Contract Status:</label>
                <select name="contract_status" id="contract_status" class="form-control" disabled>
                    <option value="">{{ ucfirst(str_replace('-', ' ', $arrivalPurchaseOrder->contract_status)) }}</option>
                </select>
            </div>
        </div>
         <div class="col-xs-12 col-sm-12 col-md-12">
             <div class="form-group">
                 <label>Remarks (Optional):</label>
                 <textarea name="remarks" placeholder="Remarks" class="form-control" readonly>{{ $arrivalPurchaseOrder->remarks }}</textarea>
             </div>
         </div>
     </div>

     <div class="row bottom-button-bar">
         <div class="col-12">
             <a type="button" class="btn btn-danger modal-sidebar-close closebutton">Close</a>
         </div>
     </div>
 </div>
<x-approval-status-special :model="$arrivalPurchaseOrder" :list-refresh="route('raw-material.get.purchase-order')" />

 <script>
     $(document).ready(function() {
         $('.view-only .form-control').attr('disabled', true);
         $('.view-only .custom-control-input').attr('disabled', true);
     });
 </script>
