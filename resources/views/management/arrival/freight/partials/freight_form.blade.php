 <form action="{{ route('freight.store') }}" method="POST" id="ajaxSubmit" autocomplete="off" enctype="multipart/form-data">
     @csrf
     <input type="hidden" name="ticket_id" value="{{ $ticket->id }}" />
     <input type="hidden" id="listRefresh" value="{{ route('get.freight') }}" />

     <div class="row form-mar">
         <div class="col-md-6">
             <div class="form-group">
                 <label>Ticket #</label>
                 <input type="text" name="ticket_number" class="form-control" value="{{ $ticket->unique_no }}"
                     readonly />
             </div>
         </div>
         <div class="col-md-6">
             <div class="form-group">
                 <label>Supplier</label>
                 <input type="text" name="supplier" class="form-control" value="{{ $ticket->supplier_name }}"
                     readonly />
             </div>
         </div>

         <div class="col-md-6">
             <div class="form-group">
                 <label>Commodity</label>
                 <input type="text" name="commodity" class="form-control" value="{{ $ticket->product->name ?? '' }}"
                     readonly />
             </div>
         </div>
         <div class="col-md-6">
             <div class="form-group">
                 <label>Truck #</label>
                 <input type="text" name="truck_number" class="form-control" value="{{ $ticket->truck_no }}"
                     readonly />
             </div>
         </div>

         <div class="col-md-6">
             <div class="form-group">
                 <label>Billy #</label>
                 <input type="text" name="billy_number" class="form-control" value="{{ $ticket->bilty_no }}"
                     readonly />
             </div>
         </div>
         <div class="col-md-6">
             <div class="form-group">
                 <label>Estimated Freight</label>
                 <input type="number" step="0.01" name="estimated_freight" class="form-control" />
             </div>
         </div>

         <div class="col-md-4">
             <div class="form-group">
                 <label>Loaded Weight</label>
                 <input type="number" name="loaded_weight" class="form-control"
                     value="{{ $ticket->loading_weight }}" />
             </div>
         </div>
         <div class="col-md-4">
             <div class="form-group">
                 <label>Arrived Weight</label>
                 <input type="number" name="arrived_weight" class="form-control" required />
             </div>
         </div>
         <div class="col-md-4">
             <div class="form-group">
                 <label>Difference</label>
                 <input type="number" name="difference" class="form-control" />
             </div>
         </div>

         <div class="col-md-4">
             <div class="form-group">
                 <label>Freight per Ton</label>
                 <input type="number" step="0.01" name="freight_per_ton" class="form-control" required />
             </div>
         </div>
         <div class="col-md-4">
             <div class="form-group">
                 <label>Kanta - Golarchi Charges</label>
                 <input type="number" step="0.01" name="kanta_golarchi_charges" class="form-control"
                     value="0" />
             </div>
         </div>
         <div class="col-md-4">
             <div class="form-group">
                 <label>Other/Labour Charges</label>
                 <input type="number" step="0.01" name="other_labour_charges" class="form-control" />
             </div>
         </div>

         <div class="col-md-4">
             <div class="form-group">
                 <label>Other Deduction</label>
                 <input type="number" step="0.01" name="other_deduction" class="form-control" />
             </div>
         </div>
         <div class="col-md-4">
             <div class="form-group">
                 <label>Unpaid Labor Charges</label>
                 <input type="number" step="0.01" name="unpaid_labor_charges" class="form-control" />
             </div>
         </div>
         <div class="col-md-4">
             <div class="form-group">
                 <label>Freight Written on Billy</label>
                 <input type="number" step="0.01" name="freight_written_on_billy" class="form-control"
                     value="0" />
             </div>
         </div>

         <div class="col-md-6">
             <div class="form-group">
                 <label>Gross Freight Amount</label>
                 <input type="number" step="0.01" name="gross_freight_amount" class="form-control"
                     value="0" />
             </div>
         </div>
         <div class="col-md-6">
             <div class="form-group">
                 <label>Net Freight</label>
                 <input type="number" step="0.01" name="net_freight" class="form-control" value="0" />
             </div>
         </div>

         <div class="col-md-6">
             <div class="form-group">
                 <label>Attach Billy</label>
                 <input type="file" name="billy_document" class="form-control-file" />
             </div>
         </div>
         <div class="col-md-6">
             <div class="form-group">
                 <label>Attach Loading Weight</label>
                 <input type="file" name="loading_weight_document" class="form-control-file" />
             </div>
         </div>

         <div class="col-md-6">
             <div class="form-group">
                 <label>Other Document (Optional)</label>
                 <input type="file" name="other_document" class="form-control-file" />
             </div>
         </div>
         <div class="col-md-6">
             <div class="form-group">
                 <label>Other Document 2 (Optional)</label>
                 <input type="file" name="other_document_2" class="form-control-file" />
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
     //  $('input[name="arrived_weight"]').on('change', function() {
     //      const loaded = parseFloat($('input[name="loaded_weight"]').val()) || 0;
     //      const arrived = parseFloat($('input[name="arrived_weight"]').val()) || 0;
     //      $('input[name="difference"]').val(loaded - arrived);
     //  });

     //  $('input[name="freight_per_ton"], input[name="loaded_weight"]').on('change', function() {
     //      const freightPerTon = parseFloat($('input[name="freight_per_ton"]').val()) || 0;
     //      const loadedWeight = parseFloat($('input[name="loaded_weight"]').val()) || 0;
     //      const netFreight = freightPerTon * (loadedWeight / 1000);
     //      $('input[name="net_freight"]').val(netFreight.toFixed(2));
     //  });
 </script>
