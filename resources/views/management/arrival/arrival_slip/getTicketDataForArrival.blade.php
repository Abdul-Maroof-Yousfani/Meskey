 @php
     $isSlabs = false;
     $isCompulsury = false;
     $showLumpSum = false;

     if (
         isset($samplingRequest->is_lumpsum_deduction) &&
         $samplingRequest->is_lumpsum_deduction &&
         $samplingRequest->lumpsum_deduction > 0
     ) {
         $showLumpSum = true;
     }

     foreach ($samplingRequestCompulsuryResults as $slab) {
         if (!$slab->applied_deduction) {
             continue;
         }
         $isCompulsury = true;
     }

     foreach ($samplingRequestResults as $slab) {
         if (!$slab->applied_deduction) {
             continue;
         }
         $isSlabs = true;
     }
 @endphp

 <div class="row form-mar">
     <div class="col-12" bis_skin_checked="1">
         <h6 class="header-heading-sepration">
             Arrival Slip
         </h6>
     </div>
     <div class="col-md-6">
         <div class="form-group">
             <label class="font-weight-bold">Date</label>
             <input type="text" class="form-control bg-light" value="{{ now()->format('d-M-Y') }}" readonly>
         </div>
     </div>
     <div class="col-md-6">
         <div class="form-group">
             <label class="font-weight-bold">No. of Bags</label>
             <input type="text" class="form-control bg-light" value="{{ $arrivalTicket->approvals->total_bags }}"
                 readonly>
         </div>
     </div>
     <div class="col-md-6">
         <div class="form-group">
             <label class="font-weight-bold">Packing</label>
             <input type="text" class="form-control bg-light"
                 value="{{ $arrivalTicket->approvals->bagType->name ?? 'N/A' }} ⸺ {{ $arrivalTicket->approvals->bagPacking->name ?? 'N/A' }}"
                 readonly>
         </div>
     </div>

     <div class="col-md-6">
         <div class="form-group">
             <label class="font-weight-bold">Party Name</label>
             <input type="text" class="form-control bg-light" value="{{ $arrivalTicket->miller->name }}" readonly>
         </div>
     </div>
     <div class="col-md-6">
         <div class="form-group">
             <label class="font-weight-bold">Broker Name</label>
             <input type="text" class="form-control bg-light" value="{{ $arrivalTicket->broker_name ?? 'N/A' }}"
                 readonly>
         </div>
     </div>
     <div class="col-md-6">
         <div class="form-group">
             <label class="font-weight-bold">On A/C of</label>
             <input type="text" class="form-control bg-light" value="{{ $arrivalTicket->accounts_of_name ?? 'N/A' }}"
                 readonly>
         </div>
     </div>
     <div class="col-md-6">
         <div class="form-group">
             <label class="font-weight-bold">Station</label>
             <input type="text" class="form-control bg-light" value="{{ $arrivalTicket->station->name ?? 'N/A' }}"
                 readonly>
         </div>
     </div>
     <div class="col-md-6">
         <div class="form-group">
             <label class="font-weight-bold">Commodity</label>
             <input type="text" class="form-control bg-light" value="{{ $arrivalTicket->product->name }}" readonly>
         </div>
     </div>
     <div class="col-md-6">
         <div class="form-group">
             <label class="font-weight-bold">Deductions</label>
             <input type="text" class="form-control bg-light"
                 value="{{ $arrivalTicket->lumpsum_deduction ?? 'N/A' }}" readonly>
         </div>
     </div>
     <div class="col-md-6">
         <div class="form-group">
             <label class="font-weight-bold">Sauda Term</label>
             <input type="text" class="form-control bg-light" value="{{ $arrivalTicket->saudaType->name ?? 'N/A' }}"
                 readonly>
         </div>
     </div>
     <div class="col-md-6">
         <div class="form-group">
             <label class="font-weight-bold">Gala No.</label>
             <input type="text" class="form-control bg-light"
                 value="{{ $arrivalTicket->approvals->gala_name ?? 'N/A' }}" readonly>
         </div>
     </div>
     <div class="col-md-6">
         <div class="form-group">
             <label class="font-weight-bold">Godown</label>
             <input type="text" class="form-control bg-light"
                 value="{{ $arrivalTicket->unloadingLocation->arrivalLocation->name ?? 'N/A' }}" readonly>
         </div>
     </div>

     <div class="col-12" bis_skin_checked="1">
         <h6 class="header-heading-sepration">
             Weight Information
         </h6>
     </div>
     <div class="col-md-3">
         <div class="form-group">
             <label>Gross Weight</label>
             <input type="text" class="form-control bg-light"
                 value="{{ $arrivalTicket->firstWeighbridge->weight ?? 'N/A' }}" readonly>
         </div>
     </div>
     <div class="col-md-3">
         <div class="form-group">
             <label>Arrival Weight</label>
             <input type="text" class="form-control bg-light"
                 value="{{ $arrivalTicket->firstWeighbridge->weight - $arrivalTicket->secondWeighbridge->weight }}"
                 readonly>
         </div>
     </div>
     <div class="col-md-3">
         <div class="form-group">
             <label>Loading Weight</label>
             <input type="text" class="form-control bg-light" value="{{ $arrivalTicket->net_weight ?? 'N/A' }}"
                 readonly>
         </div>
     </div>
     <div class="col-md-3">
         <div class="form-group">
             <label>Avg. Weight</label>
             <input type="text" class="form-control bg-light"
                 value="{{ number_format(($arrivalTicket->firstWeighbridge->weight - $arrivalTicket->secondWeighbridge->weight) / $arrivalTicket->bags, 2) ?? 'N/A' }}"
                 readonly>
         </div>
     </div>

     <div class="col-12" bis_skin_checked="1">
         <h6 class="header-heading-sepration">
             Freight Information
         </h6>
     </div>
     <div class="col-md-4">
         <div class="form-group">
             <label>Filling:</label>
             <div class="row w-100 mx-auto">
                 <input type="text" class="col form-control bg-light"
                     value="{{ $arrivalTicket->approvals->filling_bags_no ?? '0' }}" readonly>
                 <div class="col">
                     <span class="input-group-text">x 10 =</span>
                 </div>
                 <input type="text" class="col form-control bg-light"
                     value="{{ isset($arrivalTicket->approvals->filling_bags_no) ? $arrivalTicket->approvals->filling_bags_no * 10 : '0' }}"
                     readonly>
             </div>
         </div>
     </div>
     <div class="col-md-4">
         <div class="form-group">
             <label>Freight (Rs.)</label>
             <input type="text" class="form-control bg-light mb-1"
                 value="{{ $arrivalTicket->freight->freight_written_on_bilty ?? '0.00' }}" readonly>
         </div>
     </div>
     <div class="col-md-4">
         <div class="form-group">
             <label>Freight per Ton</label>
             <input type="text" class="form-control bg-light mb-1"
                 value="{{ $arrivalTicket->freight->freight_per_ton ?? '0.00' }}" readonly>
         </div>
     </div>
     <div class="col-md-6">
         <div class="form-group">
             <label>Other (+)/ Labour Charges</label>
             <input type="text" class="form-control bg-light mb-1"
                 value="{{ $arrivalTicket->freight->other_labour_charges ?? '0.00' }}" readonly>
             <input type="text" class="form-control bg-light"
                 value="{{ numberToWords($arrivalTicket->freight->other_labour_charges ?? 0) }}" readonly>
         </div>
     </div>
     <div class="col-md-6">
         <div class="form-group">
             <label>Other Deduction</label>
             <input type="text" class="form-control bg-light mb-1"
                 value="{{ $arrivalTicket->freight->other_deduction ?? '0.00' }}" readonly>
             <input type="text" class="form-control bg-light"
                 value="{{ numberToWords($arrivalTicket->freight->other_deduction ?? 0) }}" readonly>
         </div>
     </div>
     <div class="col-md-6">
         <div class="form-group">
             <label>Total Freight Payable (Rs.)</label>
             <input type="text" class="form-control bg-light mb-1"
                 value="{{ $arrivalTicket->freight->gross_freight_amount ?? '0.00' }}" readonly>
             <input type="text" class="form-control bg-light"
                 value="{{ numberToWords($arrivalTicket->freight->gross_freight_amount ?? 0) }}" readonly>
         </div>
     </div>
     <div class="col-md-6">
         <div class="form-group">
             <label>Unpaid Labour Charge</label>
             <input type="text" class="form-control bg-light mb-1"
                 value="{{ $arrivalTicket->freight->unpaid_labor_charges ?? '0.00' }}" readonly>
             <input type="text" class="form-control bg-light"
                 value="{{ numberToWords($arrivalTicket->freight->unpaid_labor_charges ?? 0) }}" readonly>
         </div>
     </div>
     <div class="col">
         <div class="form-group">
             <label>Final Figure</label>
             <div class="d-flex">
                 <input type="text" class="form-control bg-light mb-1"
                     value="{{ $arrivalTicket->freight->net_freight ?? '0.00' }}" readonly>
                 <input type="text" class="form-control bg-light"
                     value="{{ numberToWords($arrivalTicket->freight->net_freight ?? 0) }}" readonly>
             </div>
         </div>
     </div>

     @if ($showLumpSum && !$isSlabs && !$isCompulsury)
         <div class="col-12" bis_skin_checked="1">
             <h6 class="header-heading-sepration">
                 Sampling Results
             </h6>
         </div>
         <div class="col-12">
             <div class="table-responsive">
                 <table class="table table-sm table-bordered table-hover">
                     <thead class="thead-light">
                         <tr>
                             <th width="60%">Parameter</th>
                             <th width="40%">Applied Deduction</th>
                         </tr>
                     </thead>
                     <tbody>
                         <tr>
                             <td>Lumpsum Deduction Rupees</td>
                             <td class="text-center">
                                 {{ $samplingRequest->lumpsum_deduction ?? '0.00' }}
                                 <span class="text-sm">(Applied as Lumpsum)</span>
                             </td>
                         </tr>
                         <tr>
                             <td>Lumpsum Deduction KG's</td>
                             <td class="text-center">
                                 {{ $samplingRequest->lumpsum_deduction_kgs ?? '0.00' }}
                                 <span class="text-sm">(Applied as Lumpsum)</span>
                             </td>
                         </tr>
                     </tbody>
                 </table>
             </div>
         </div>
     @else
         <div class="col-12" bis_skin_checked="1">
             <h6 class="header-heading-sepration">
                 Sampling Results
             </h6>
         </div>
         <div class="col-12">
             <div class="table-responsive">
                 <table class="table table-sm table-bordered table-hover">
                     <thead class="thead-light">
                         <tr>
                             <th width="60%">Parameter</th>
                             <th width="40%">Applied Deduction</th>
                         </tr>
                     </thead>
                     <tbody>
                         @if (count($samplingRequestResults) != 0)
                             @foreach ($samplingRequestResults as $slab)
                                 @php
                                     if (!$slab->applied_deduction) {
                                         continue;
                                     }
                                 @endphp
                                 <tr>
                                     <td>{{ $slab->slabType->name }}</td>
                                     <td class="text-center">{{ $slab->applied_deduction }}
                                         <span
                                             class="text-sm">{{ SLAB_TYPES_CALCULATED_ON[$slab->slabType->calculation_base_type ?? 1] }}</span>
                                     </td>
                                 </tr>
                             @endforeach
                         @else
                             <tr>
                                 <td colspan="2" class="text-center text-muted">No Initial Slabs Found</td>
                             </tr>
                         @endif

                         @if ($isCompulsury)
                             @if (count($samplingRequestCompulsuryResults) != 0)
                                 @foreach ($samplingRequestCompulsuryResults as $slab)
                                     @php
                                         if (!$slab->applied_deduction) {
                                             continue;
                                         }
                                     @endphp
                                     <tr>
                                         <td>{{ $slab->qcParam->name }}</td>
                                         <td class="text-center">{{ $slab->applied_deduction }}
                                             <span class="text-sm">{{ SLAB_TYPES_CALCULATED_ON[3] }}</span>
                                         </td>
                                     </tr>
                                 @endforeach
                             @else
                                 <tr>
                                     <td colspan="2" class="text-center text-muted">No Compulsory Slabs Found</td>
                                 </tr>
                             @endif
                         @endif
                     </tbody>
                 </table>
             </div>
         </div>
     @endif

     <div class="col-12 mt-4">
         <div class="row">
             <div class="col-md-4">
                 <div class="form-group">
                     <label class="font-weight-bold">Confirmed Form</label>
                     <input type="text" class="form-control bg-light"
                         value="{{ $arrivalTicket->purchaseOrder->unique_no ?? 'N/A' }}" readonly>
                 </div>
             </div>
             <div class="col-md-4">
                 <div class="form-group">
                     <label class="font-weight-bold">Contract Number</label>
                     <input type="text" class="form-control bg-light"
                         value="{{ $arrivalTicket->purchaseOrder->unique_no ?? 'N/A' }}" readonly>
                 </div>
             </div>
             <div class="col-md-4">
                 <div class="form-group">
                     <label class="font-weight-bold">Prepared By:</label>
                     <input type="text" class="form-control bg-light" value="{{ auth()->user()->name }}"
                         readonly>
                 </div>
             </div>
         </div>
     </div>
     @if ($isNotGeneratable)
         <div class="col-12 mt-4">
             <div class="alert alert-danger">
                 <strong>Important!</strong> Please apply deductions first before generating the arrival slip.
             </div>
         </div>
     @endif
 </div>

 @push('scripts')
     <script>
         function numberToWords(num) {
             const ones = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine'];
             const tens = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];
             const teens = ['Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen', 'Eighteen',
                 'Nineteen'
             ];

             function convertLessThanOneThousand(num) {
                 if (num === 0) return '';
                 if (num < 10) return ones[num];
                 if (num < 20) return teens[num - 10];
                 if (num < 100) {
                     return tens[Math.floor(num / 10)] + ' ' + ones[num % 10];
                 }
                 return ones[Math.floor(num / 100)] + ' Hundred ' + convertLessThanOneThousand(num % 100);
             }

             num = parseFloat(num) || 0;
             const rupees = Math.floor(num);
             const paise = Math.round((num - rupees) * 100);

             let words = '';
             if (rupees > 0) {
                 words = convertLessThanOneThousand(rupees) + ' Rupees';
             }
             if (paise > 0) {
                 if (words !== '') words += ' and ';
                 words += convertLessThanOneThousand(paise) + ' Paise';
             }
             return words || 'Zero Rupees';
         }

         $(document).ready(function() {
             $('.amount-field').each(function() {
                 const num = parseFloat($(this).val()) || 0;
                 const words = numberToWords(num);
                 $(this).next('.amount-words').val(words + ' Only');
             });
         });
     </script>
 @endpush
