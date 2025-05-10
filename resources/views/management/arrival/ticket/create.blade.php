 <form action="{{ route('ticket.store') }}" method="POST" id="ajaxSubmit" class="valid-screen" autocomplete="off">
     @csrf
     <input type="hidden" id="listRefresh" value="{{ route('get.ticket') }}" />
     <div class="row form-mar">

         <?php
         $datePrefix = date('m-d-Y') . '-';
         $unique_no = generateUniqueNumber('arrival_tickets', $datePrefix, null, 'unique_no');
         ?>

         <div class="col-xs-6 col-sm-6 col-md-6">
             <fieldset>
                 <div class="input-group">
                     <div class="input-group-prepend">
                         <button class="btn btn-primary" type="button">Product Code#</button>
                     </div>
                     <input type="text" disabled class="form-control" value="{{ $unique_no }}"
                         placeholder="Button on left">
                 </div>
             </fieldset>
         </div>


         <div class="col-xs-12 col-sm-12 col-md-12">
             <div class="form-group ">
                 <label>Product:</label>
                 <select name="product_id" id="product_id" class="form-control select2">
                     <option value="">Product Name</option>
                 </select>
             </div>
         </div>
         <div class="col-xs-6 col-sm-6 col-md-6">
             <div class="form-group">
                 <label class="d-block">Contract Detail:</label>
                 <select name="arrival_purchase_order_id" id="arrival_purchase_order_id" class="form-control select2">
                     <option value="">N/A</option>
                     @foreach ($arrivalPurchaseOrders as $arrivalPurchaseOrder)
                         <option data-saudatypeid="{{ $arrivalPurchaseOrder->sauda_type_id }}"
                             data-brokerid="{{ $arrivalPurchaseOrder->broker->id ?? '' }}"
                             data-brokername="{{ $arrivalPurchaseOrder->broker->name ?? '' }}"
                             data-supplierid="{{ $arrivalPurchaseOrder->supplier->id ?? '' }}"
                             data-suppliername="{{ $arrivalPurchaseOrder->supplier->name ?? '' }}"
                             value="{{ $arrivalPurchaseOrder->id }}">
                             {{ $arrivalPurchaseOrder->unique_no }}
                         </option>
                     @endforeach
                 </select>
             </div>
         </div>
         <div class="col-xs-6 col-sm-6 col-md-6">
             <div class="form-group ">
                 <label>Supplier:</label>
                 <select name="supplier_name_display" id="supplier_name" class="form-control select2">
                     <option value="">Supplier Name</option>
                 </select>
                 <input type="hidden" name="supplier_name" id="supplier_name_submit">
             </div>
         </div>
         <div class="col-xs-6 col-sm-6 col-md-6">
             <div class="form-group ">
                 <label>Broker:</label>
                 <select name="broker_name_display" id="broker_name" class="form-control select2">
                     <option value="">Broker Name</option>
                 </select>
                 <input type="hidden" name="broker_name" id="broker_name_submit">
             </div>
         </div>
         <div class="col-xs-6 col-sm-6 col-md-6">
             <div class="form-group ">
                 <label>Decision Of:</label>
                 <select name="decision_id" id="decision_id" class="form-control select2">
                     <option value="" hidden>Decision Of</option>
                     @foreach ($accountsOf as $account)
                         <option value="{{ $account->id }}">{{ $account->name }}</option>
                     @endforeach
                 </select>
             </div>
         </div>
         <div class="col-xs-6 col-sm-6 col-md-6">
             <div class="form-group ">
                 <label>Accounts Of:</label>
                 <select name="accounts_of" id="accounts_of" class="form-control select2">
                     <option value="" hidden>Accounts Of</option>
                 </select>
             </div>
         </div>
         <div class="col-xs-6 col-sm-6 col-md-6">
             <div class="form-group ">
                 <label>Station:</label>
                 <select name="station_id" id="station_id" class="form-control select2">
                     <option value="" hidden>Station</option>
                 </select>
             </div>
         </div>
         <div class="col-xs-6 col-sm-6 col-md-6">
             <div class="form-group ">
                 <label>Truck No:</label>
                 <input type="text" name="truck_no" placeholder="Truck No" class="form-control" autocomplete="off" />
             </div>
         </div>
         <div class="col-xs-6 col-sm-6 col-md-6">
             <div class="form-group ">
                 <label>Bilty No: </label>
                 <input type="text" name="bilty_no" placeholder="Bilty No" class="form-control" autocomplete="off" />
             </div>
         </div>
         <div class="col-xs-6 col-sm-6 col-md-6">
             <div class="form-group ">
                 <label>Truck Type:</label>
                 <select name="arrival_truck_type_id" id="" class="form-control select2">
                     <option value="">Truck Type</option>

                     @foreach (getTableData('arrival_truck_types', ['id', 'name', 'sample_money']) as $arrival_truck_types)
                         <option data-samplemoney="{{ $arrival_truck_types->sample_money ?? 0 }}"
                             value="{{ $arrival_truck_types->id }}">{{ $arrival_truck_types->name }}</option>
                     @endforeach
                 </select>
             </div>
         </div>
         <div class="col-xs-6 col-sm-6 col-md-6">
             <div class="form-group ">
                 <label>Sample Money Type:</label>
                 <select name="sample_money_type" class="form-control">
                     <option value="n/a" selected>N/A</option>
                     <option value="single">Single</option>
                     <option value="double">Double</option>
                 </select>
             </div>
         </div>
         <div class="col-xs-6 col-sm-6 col-md-6">
             <div class="form-group ">
                 <label>Sample Money: </label>
                 <input type="text" readonly name="sample_money" placeholder="Sample Money" class="form-control"
                     autocomplete="off" />
             </div>
         </div>
         <div class="col-xs-6 col-sm-6 col-md-6">
             <div class="form-group ">
                 <label>No of bags: </label>
                 <input type="text" name="bags" placeholder="No of bags" class="form-control"
                     autocomplete="off" />
             </div>
         </div>
         <div class="col-xs-6 col-sm-6 col-md-6">
             <div class="form-group ">
                 <label>Loading Date: (Optional)</label>
                 <input type="date" name="loading_date" placeholder="Bilty No" class="form-control"
                     autocomplete="off" />
             </div>
         </div>
         {{-- <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group ">
                <label>Loading Weight:</label>
                <input type="text" name="loading_weight" placeholder="Loading Weight" class="form-control"
                    autocomplete="off" />
            </div>
        </div> --}}

         {{-- <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group ">
                <label>Status:</label>
                <select name="status" class="form-control">
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
        </div> --}}
     </div>
     <div class="row ">
         <div class="col-12">
             <h6 class="header-heading-sepration">
                 Weight Detail
             </h6>
         </div>
         <div class="col-xs-4 col-sm-4 col-md-4">
             <div class="form-group">
                 <label>1st Weight:</label>
                 <input type="text" name="first_weight" id="first_weight" placeholder="First Weight"
                     class="form-control" autocomplete="off" />
             </div>
         </div>
         <div class="col-xs-4 col-sm-4 col-md-4">
             <div class="form-group">
                 <label>Second Weight:</label>
                 <input type="text" name="second_weight" id="second_weight" placeholder="Second Weight"
                     class="form-control" autocomplete="off" />
             </div>
         </div>
         <div class="col-xs-4 col-sm-4 col-md-4">
             <div class="form-group">
                 <label>Net Weight:</label>
                 <input type="text" name="net_weight" id="net_weight" placeholder="Net Weight"
                     class="form-control" readonly autocomplete="off" />
                 <div class="error-message text-danger" style="display: none;">Please check your values. Net weight
                     cannot be negative.</div>
             </div>
         </div>
     </div>


     <div class="row ">
         <div class="col-xs-12 col-sm-12 col-md-12">
             <div class="form-group ">
                 <label>Remarks (Optional):</label>
                 <textarea name="remarks" row="4" class="form-control" placeholder="Description"></textarea>
             </div>
         </div>
     </div>


     <div class="row bottom-button-bar">
         <div class="col-12">
             <a type="button"
                 class="btn btn-danger modal-sidebar-close position-relative top-1 closebutton">Close</a>
             <button type="submit" class="btn btn-primary submitbutton">Save</button>
         </div>
     </div>
 </form>



 <script>
     function calculateSampleMoney() {
         let truckTypeSelect = $('[name="arrival_truck_type_id"]');
         let sampleMoney = truckTypeSelect.find(':selected').data('samplemoney') || 0;

         let holidayType = $('[name="sample_money_type"]').val();

         if (holidayType === 'double') {
             sampleMoney = sampleMoney * 2;
         }

         $('input[name="sample_money"]').val(sampleMoney || 0);
     }

     $(document).ready(function() {
         calculateSampleMoney();

         $(document).on('change', '[name="arrival_truck_type_id"]', calculateSampleMoney);

         $(document).on('change', '[name="sample_money_type"]', calculateSampleMoney);
     });

     $(document).ready(function() {
         initializeDynamicSelect2('#product_id', 'products', 'name', 'id', false, false);
         initializeDynamicSelect2('#supplier_name', 'suppliers', 'name', 'name', true, false);
         initializeDynamicSelect2('#accounts_of', 'suppliers', 'name', 'name', true, false);
         initializeDynamicSelect2('#broker_name', 'brokers', 'name', 'name', true, false);
         initializeDynamicSelect2('#station_id', 'stations', 'name', 'id', true, false);

         $('[name="arrival_truck_type_id"], [name="decision_id"]').select2();

         function calculateNetWeight() {
             const firstWeight = parseFloat($('#first_weight').val()) || 0;
             const secondWeight = parseFloat($('#second_weight').val()) || 0;

             const netWeight = secondWeight - firstWeight;

             $('#net_weight').val(netWeight || 0);

             if (firstWeight && secondWeight) {
                 if (netWeight < 0) {
                     $('#net_weight').addClass('is-invalid');
                     $('#net_weight').siblings('.error-message').show();
                 } else {
                     $('#net_weight').removeClass('is-invalid');
                     $('#net_weight').siblings('.error-message').hide();
                 }
             }
         }

         $('#first_weight, #second_weight').on('input', function() {
             calculateNetWeight();
         });

         //   $(document).on('change', '[name="arrival_truck_type_id"]', function () {
         //  let sampleMoney = $(this).find(':selected').data('samplemoney');
         //   $('input[name="sample_money"]').val(sampleMoney ?? '');
         //});

         $(document).on('change', '[name="arrival_purchase_order_id"]', function() {
             var selectedOption = $(this).find('option:selected');
             var brokerName = selectedOption.data('brokername');
             var supplierName = selectedOption.data('suppliername');

             // Handle supplier
             if (supplierName) {
                 $('#supplier_name').html('<option value="' + supplierName + '" selected>' +
                     supplierName + '</option>');
                 $('#supplier_name').prop('disabled', true);
                 $('#supplier_name_submit').val(supplierName); // Set hidden field
             } else {
                 $('#supplier_name').html('<option value="">Supplier Name</option>');
                 $('#supplier_name').prop('disabled', false);
                 $('#supplier_name_submit').val(''); // Clear hidden field
                 initializeDynamicSelect2('#supplier_name', 'suppliers', 'name', 'name', true, false);
             }

             // Handle broker
             if (brokerName) {
                 $('#broker_name').html('<option value="' + brokerName + '" selected>' + brokerName +
                     '</option>');
                 $('#broker_name').prop('disabled', true);
                 $('#broker_name_submit').val(brokerName); // Set hidden field
             } else {
                 $('#broker_name').html('<option value="">Broker Name</option>');
                 $('#broker_name').prop('disabled', false);
                 $('#broker_name_submit').val(''); // Clear hidden field
                 initializeDynamicSelect2('#broker_name', 'brokers', 'name', 'name', true, false);
             }
         });

         // Sync values on any change (just in case)
         $(document).on('change', '#supplier_name, #broker_name', function() {
             if ($(this).attr('id') === 'supplier_name') {
                 $('#supplier_name_submit').val($(this).val());
             } else {
                 $('#broker_name_submit').val($(this).val());
             }
         });
     });
 </script>
