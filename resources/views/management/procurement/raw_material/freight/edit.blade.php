 <form action="{{ route('raw-material.freight.update', $freight->id) }}" method="POST" id="ajaxSubmit" autocomplete="off"
     enctype="multipart/form-data">
     @csrf
     @method('PUT')
     <input type="hidden" name="arrival_purchase_order_id" value="{{ $freight->arrival_purchase_order_id }}" />
     <input type="hidden" id="listRefresh" value="{{ route('raw-material.get.freight') }}" />

     <div class="row form-mar">
         <div class="col-md-6">
             <div class="form-group">
                 <label>Contract #</label>
                 <input type="text" class="form-control" value="{{ $freight->purchaseOrder->contract_no ?? 'N/A' }}"
                     readonly />
             </div>
         </div>
         <div class="col-md-6">
             <div class="form-group">
                 <label>Loading Date</label>
                 <input type="date" name="loading_date" class="form-control"
                     value="{{ $freight->loading_date ? $freight->loading_date->format('Y-m-d') : '' }}" />
             </div>
         </div>

         <div class="col-md-6">
             <div class="form-group">
                 <label>Supplier Name</label>
                 <input type="text" name="supplier_name" class="form-control" value="{{ $freight->supplier_name }}"
                     readonly />
             </div>
         </div>
         <div class="col-md-6">
             <div class="form-group">
                 <label>Broker</label>
                 <input type="text" name="broker" class="form-control" value="{{ $freight->broker }}" />
             </div>
         </div>

         <div class="col-md-6">
             <div class="form-group">
                 <label>Truck No</label>
                 <input type="text" name="truck_no" class="form-control" value="{{ $freight->truck_no }}" />
             </div>
         </div>
         <div class="col-md-6">
             <div class="form-group">
                 <label>Bilty No</label>
                 <input type="text" name="bilty_no" class="form-control" value="{{ $freight->bilty_no }}" />
             </div>
         </div>

         <div class="col-md-6">
             <div class="form-group">
                 <label>Station</label>
                 <select name="station_id" id="station_id" class="form-control select2">
                     <option value="" hidden>Select Station</option>
                     @foreach ($stations as $station)
                         <option value="{{ $station->id }}"
                             {{ $freight->station_id == $station->id ? 'selected' : '' }}>
                             {{ $station->name }}
                         </option>
                     @endforeach
                 </select>
             </div>
         </div>

         <div class="col-md-6">
             <div class="form-group">
                 <label>No of Bags</label>
                 <input type="number" name="no_of_bags" class="form-control" value="{{ $freight->no_of_bags }}" />
             </div>
         </div>

         <div class="col-md-6">
             <div class="form-group">
                 <label>Bag Condition</label>
                 <select class="form-control" name="bag_condition_id">
                     <option value="">Select Bag Condition</option>
                     @foreach ($bagTypes as $bagType)
                         <option value="{{ $bagType->id }}"
                             {{ $freight->bag_condition_id == $bagType->id ? 'selected' : '' }}>
                             {{ $bagType->name }}
                         </option>
                     @endforeach
                 </select>
             </div>
         </div>

         <div class="col-md-6">
             <div class="form-group">
                 <label>Commodity</label>
                 <input type="text" name="commodity" class="form-control" value="{{ $freight->commodity }}"
                     readonly />
             </div>
         </div>

         <div class="col-md-6">
             <div class="form-group">
                 <label>Loading Weight (kg)</label>
                 <input type="number" step="0.01" name="loading_weight" class="form-control"
                     value="{{ $freight->loading_weight }}" />
             </div>
         </div>

         <div class="col-md-6">
             <div class="form-group">
                 <label>Kanta Charges</label>
                 <input type="number" step="0.01" name="kanta_charges" class="form-control"
                     value="{{ $freight->kanta_charges }}" />
             </div>
         </div>

         <div class="col-md-6">
             <div class="form-group">
                 <label>Freight on Bilty</label>
                 <input type="number" step="0.01" name="freight_on_bilty" class="form-control"
                     value="{{ $freight->freight_on_bilty }}" />
             </div>
         </div>

         <div class="col-md-6">
             <div class="form-group">
                 <label>Advance Freight</label>
                 <input type="number" step="0.01" name="advance_freight" class="form-control"
                     value="{{ $freight->advance_freight }}" />
             </div>
         </div>

         <div class="col-12">
             <h6 class="header-heading-sepration">
                 Document Attachments
             </h6>
             <div class="alert alert-info">
                 <strong>Attachment Guidelines:</strong>
                 <ul class="mb-0">
                     <li>Allowed formats: JPEG, PNG, JPG, PDF</li>
                     <li>Images will be automatically compressed</li>
                     <li>Maximum file size: 5MB per file</li>
                 </ul>
             </div>
         </div>

         <div class="col-md-4">
             <div class="form-group">
                 <label>Bilty Slip</label>
                 <input type="file" name="bilty_slip" class="form-control-file" />
                 @if ($freight->bilty_slip)
                     <div class="mt-2">
                         <a href="{{ asset($freight->bilty_slip) }}" target="_blank"
                             class="btn btn-sm btn-info">View
                             Current File</a>
                     </div>
                 @endif
             </div>
         </div>

         <div class="col-md-4">
             <div class="form-group">
                 <label>Weighbridge Slip</label>
                 <input type="file" name="weighbridge_slip" class="form-control-file" />
                 @if ($freight->weighbridge_slip)
                     <div class="mt-2">
                         <a href="{{ asset($freight->weighbridge_slip) }}" target="_blank"
                             class="btn btn-sm btn-info">View Current File</a>
                     </div>
                 @endif
             </div>
         </div>

         <div class="col-md-4">
             <div class="form-group">
                 <label>Supplier Bill</label>
                 <input type="file" name="supplier_bill" class="form-control-file" />
                 @if ($freight->supplier_bill)
                     <div class="mt-2">
                         <a href="{{ asset($freight->supplier_bill) }}" target="_blank"
                             class="btn btn-sm btn-info">View Current File</a>
                     </div>
                 @endif
             </div>
         </div>
     </div>

     <div class="row bottom-button-bar">
         <div class="col-12">
             <a type="button"
                 class="btn btn-danger modal-sidebar-close position-relative top-1 closebutton">Close</a>
             <button type="submit" class="btn btn-primary submitbutton">Update</button>
         </div>
     </div>
 </form>

 <script>
     $(document).ready(function() {
         $('.select2').select2();
     });
 </script>
