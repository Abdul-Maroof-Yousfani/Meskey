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
         <div class="col-12" bis_skin_checked="1">
             <h6 class="header-heading-sepration">
                 Estimated Freight
             </h6>
         </div>
         <div class="col-md-4">
             <div class="form-group">
                 <label>Loaded Weight</label>
                 <input type="number" name="loaded_weight" class="form-control" value="{{ $ticket->net_weight }}"
                     disabled />
             </div>
         </div>
         <div class="col-md-4">
             <div class="form-group">
                 <label>Arrived Weight</label>
                 <input type="number" name="arrived_weight" class="form-control"
                     value="{{ $ticket->arrived_net_weight }}" disabled />
             </div>
         </div>
         <div class="col-md-4">
             <div class="form-group">
                 <label>Difference</label>
                 <input type="number" name="difference" class="form-control"
                     value="{{ ($ticket->net_weight ?? 0) - ($ticket->arrived_net_weight ?? 0) }}" disabled />
             </div>
         </div>

         <div class="col-md-4">
             <div class="form-group">
                 <label>Exempted Weight</label>
                 <input type="number" name="exempted_weight" class="form-control" value="0" />
             </div>
         </div>

         <div class="col-md-4">
             <div class="form-group">
                 <label>PO Rate</label>
                 <input type="number" step="0.01" name="po_rate" class="form-control" />
             </div>
         </div>

         <div class="col-md-4">
             <div class="form-group">
                 <label>Net Shortage</label>
                 <input type="number" name="net_shortage" class="form-control" value="0" />
             </div>
         </div>

         <div class="col-md-4">
             <div class="form-group">
                 <label>Shortage Weight Freight Deduction</label>
                 <input type="number" step="0.01" name="shortage_weight_freight_deduction" class="form-control"
                     value="0" />
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
                 <label>Other (+)/Labour Charges</label>
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
         <div class="col-md-6">
             <div class="form-group">
                 <label>Freight Written on Billy</label>
                 <input type="number" step="0.01" name="freight_written_on_bilty" class="form-control"
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
                 <label>Status</label>
                 <select name="status" class="form-control">
                     <option value="pending">Pending</option>
                     <option value="approved">Approved</option>
                     <option value="rejected">Rejected</option>
                 </select>
             </div>
         </div>

         <div class="col-md-6">
             <div class="form-group">
                 <label>Attach Billy</label>
                 <input type="file" name="bilty_document" class="form-control-file" />
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
