@php $rowIndex = 0; @endphp
@if(isset($receiving_requests))
    @foreach($receiving_requests as $rr)
        @foreach($rr->items as $data)
            @php
                $dcData = $data->deliveryChallanData;
                $packing = $dcData?->bag_size ?? 50;
                $noOfBags = $data->no_of_bags > 0 ? $data->no_of_bags : ($dcData?->no_of_bags ?? 0);
                $qty = $data->receiving_weight > 0 ? $data->receiving_weight : ($data->dispatch_weight > 0 ? $data->dispatch_weight : ($dcData?->qty ?? 0));
                $dcDataId = $dcData?->id ?? $data->delivery_challan_data_id;

                // Lookup matching Sales Invoice Data for this delivery item
                $siData = \App\Models\Sales\SalesInvoiceData::where('dc_data_id', $dcDataId)
                    ->whereHas('sales_invoice', function($q) {
                        $q->where('am_approval_status', '!=', 'rejected');
                    })
                    ->latest('id')
                    ->first();

                $rate = (float)($siData?->rate > 0 ? $siData->rate : ($dcData?->rate ?? 0));
                $discountPercent = (float)($siData?->discount_percent ?? 0);
                $gstPercent = (float)($siData?->gst_percent ?? 0);

                if ($discountPercent <= 0 && (float)($siData?->discount_amount ?? 0) > 0 && (float)($siData?->gross_amount ?? 0) > 0) {
                    $discountPercent = round(((float)$siData->discount_amount / (float)$siData->gross_amount) * 100, 4);
                }
                if ($gstPercent <= 0 && (float)($siData?->gst_amount ?? 0) > 0 && (float)($siData?->amount ?? 0) > 0) {
                    $gstPercent = round(((float)$siData->gst_amount / (float)$siData->amount) * 100, 4);
                }

                $grossAmount = round($qty * $rate, 2);
                $discountAmount = round(($discountPercent / 100) * $grossAmount, 2);
                $amount = round($grossAmount - $discountAmount, 2);
                $gstAmount = round(($gstPercent / 100) * $amount, 2);
                $netAmount = round($amount + $gstAmount, 2);

                $lineDesc = $dcData?->description ?? '';
                $truckNo = $dcData?->truck_no ?? $rr->truck_number ?? '';
                $dataId = $data->delivery_challan_data_id ?? $data->id;
            @endphp
            <tr id="row_{{ $rowIndex }}">
                <td style="min-width: 200px;">
                    <input type="text" class="form-control" value="{{ getItem($data->item_id)?->name ?? $data->item_name ?? '' }}" readonly />
                    <input type="hidden" name="item_id[]" id="item_id_{{ $rowIndex }}" class="form-control" value="{{ $data->item_id }}" readonly />
                    <input type="hidden" name="si_data_id[]" value="{{ $dataId }}">
                    <input type="hidden" name="si_id[]" value="{{ $rr->id }}">
                </td>
                <td style="min-width: 100px;">
                    <input readonly type="number" name="packing[]" id="packing_{{ $rowIndex }}" class="form-control packing" step="0.01" min="0" value="{{ $packing }}" readonly>
                </td>
                <td style="min-width: 100px;">
                    <input readonly type="number" name="no_of_bags[]" id="no_of_bags_{{ $rowIndex }}" class="form-control no_of_bags" step="0.01" min="0" value="{{ $noOfBags }}">
                    <span style="font-size: 13px;">Used: {{ sale_return_bags_used($dataId) }}</span>
                    <br />
                    <span style="font-size: 13px;">Balance: {{ sale_return_balance($dataId) }}</span>
                </td>
                <td style="min-width: 100px;">
                    <input 
                        type="number" 
                        name="qty[]" 
                        id="qty_{{ $rowIndex }}" 
                        class="form-control qty" 
                        step="0.01" 
                        min="0"  
                        data-balance="{{ sale_return_balance($dataId) }}"
                        onkeyup="calc(this); check_balance(this, 'no_of_bags_{{ $rowIndex }}')" 
                        value="{{ round($qty, 2) }}">
                </td>
                <td style="min-width: 100px;">
                    <input readonly type="number" name="rate[]" id="rate_{{ $rowIndex }}" class="form-control rate" step="0.01" min="0" value="{{ $rate }}">
                </td>
                <td style="min-width: 120px;">
                    <input readonly type="number" name="gross_amount[]" id="gross_amount_{{ $rowIndex }}" class="form-control gross_amount" readonly value="{{ $grossAmount }}">
                </td>
                <td style="min-width: 100px;">
                    <input readonly type="number" name="discount_percent[]" id="discount_percent_{{ $rowIndex }}" class="form-control discount_percent" step="0.01" min="0" max="100" value="{{ $discountPercent }}">
                </td>
                <td style="min-width: 120px;">
                    <input readonly type="number" name="discount_amount[]" id="discount_amount_{{ $rowIndex }}" class="form-control discount_amount" step="0.01" min="0" value="{{ $discountAmount }}">
                </td>
                <td style="min-width: 120px;">
                    <input readonly type="number" name="amount[]" id="amount_{{ $rowIndex }}" class="form-control amount" readonly value="{{ $amount }}">
                </td>
                <td style="min-width: 100px;">
                    <input readonly type="number" name="gst_percent[]" id="gst_percent_{{ $rowIndex }}" class="form-control gst_percent" step="0.01" min="0" max="100" value="{{ $gstPercent }}">
                </td>
                <td style="min-width: 120px;">
                    <input readonly type="number" name="gst_amount[]" id="gst_amount_{{ $rowIndex }}" class="form-control gst_amount" step="0.01" min="0" value="{{ $gstAmount }}">
                </td>
                <td style="min-width: 120px;">
                    <input readonly type="number" name="net_amount[]" id="net_amount_{{ $rowIndex }}" class="form-control net_amount" readonly value="{{ $netAmount }}">
                </td>
                <td style="min-width: 150px;">
                    <input readonly type="text" name="line_desc[]" id="line_desc_{{ $rowIndex }}" class="form-control line_desc" value="{{ $lineDesc }}">
                </td>
                <td style="min-width: 120px;">
                    <input readonly type="text" name="truck_no[]" id="truck_no_{{ $rowIndex }}" class="form-control truck_no" value="{{ $truckNo }}">
                </td>
                <td style="min-width: 80px;">
                    <button type="button" class="btn btn-danger btn-sm removeRowBtn" onclick="removeRow({{ $rowIndex }})" style="width:60px;">
                        <i class="fa fa-trash"></i>
                    </button>
                </td>
            </tr>
            @php $rowIndex++; @endphp
        @endforeach
    @endforeach
@elseif(isset($sale_invoices))
    @foreach($sale_invoices as $sale_invoice)
        @foreach($sale_invoice->sales_invoice_data as $data)
            @php
                $packing = $data->packing ?? 0;
                $noOfBags = $data->no_of_bags;
                $qty = $data->qty;
                $rate = $data->rate ?? 0;
                $grossAmount = $data->gross_amount;
                $discountPercent = $data->discount_percent;
                $discountAmount = $data->discount_amount;
                $amount = $grossAmount - $discountAmount;
                $gstPercent = $data->gst_percent;
                $gstAmount = $data->gst_amount;
                $netAmount = $amount + $gstAmount;
                $lineDesc = $data->line_desc ?? '';
                $truckNo = $data->truck_no ?? '';
            @endphp
            <tr id="row_{{ $rowIndex }}">
                <td style="min-width: 200px;">
                    <input type="text" class="form-control" value="{{ getItem($data->item_id)?->name ?? '' }}" readonly />
                    <input type="hidden" name="item_id[]" id="item_id_{{ $rowIndex }}" class="form-control" value="{{ $data->item_id }}" readonly />
                    <input type="hidden" name="si_data_id[]" value="{{ $data->id }}">
                    <input type="hidden" name="si_id[]" value="{{ $sale_invoice->id }}">
                </td>
                <td style="min-width: 100px;">
                    <input readonly type="number" name="packing[]" id="packing_{{ $rowIndex }}" class="form-control packing" step="0.01" min="0" value="{{ $packing }}" readonly>
                </td>
                <td style="min-width: 100px;">
                    <input readonly type="number" name="no_of_bags[]" id="no_of_bags_{{ $rowIndex }}" class="form-control no_of_bags" step="0.01" min="0" value="{{ $noOfBags }}">
                    <span style="font-size: 13px;">Used: {{ sale_return_bags_used($data->id) }}</span>
                    <br />
                    <span style="font-size: 13px;">Balance: {{ sale_return_balance($data->id) }}</span>
                </td>
                <td style="min-width: 100px;">
                    <input 
                        type="number" 
                        name="qty[]" 
                        id="qty_{{ $rowIndex }}" 
                        class="form-control qty" 
                        step="0.01" 
                        min="0"  
                        data-balance="{{ sale_return_balance($data->id) }}"
                        onkeyup="calc(this); check_balance(this, 'no_of_bags_{{ $rowIndex }}')" 
                        value="{{ round($qty, 2) }}">
                </td>
                <td style="min-width: 100px;">
                    <input readonly type="number" name="rate[]" id="rate_{{ $rowIndex }}" class="form-control rate" step="0.01" min="0" value="{{ $rate }}">
                </td>
                <td style="min-width: 120px;">
                    <input readonly type="number" name="gross_amount[]" id="gross_amount_{{ $rowIndex }}" class="form-control gross_amount" readonly value="{{ $grossAmount }}">
                </td>
                <td style="min-width: 100px;">
                    <input readonly type="number" name="discount_percent[]" id="discount_percent_{{ $rowIndex }}" class="form-control discount_percent" step="0.01" min="0" max="100" value="{{ $discountPercent }}">
                </td>
                <td style="min-width: 120px;">
                    <input readonly type="number" name="discount_amount[]" id="discount_amount_{{ $rowIndex }}" class="form-control discount_amount" step="0.01" min="0" value="{{ $discountAmount }}">
                </td>
                <td style="min-width: 120px;">
                    <input readonly type="number" name="amount[]" id="amount_{{ $rowIndex }}" class="form-control amount" readonly value="{{ $amount }}">
                </td>
                <td style="min-width: 100px;">
                    <input readonly type="number" name="gst_percent[]" id="gst_percent_{{ $rowIndex }}" class="form-control gst_percent" step="0.01" min="0" max="100" value="{{ $gstPercent }}">
                </td>
                <td style="min-width: 120px;">
                    <input readonly type="number" name="gst_amount[]" id="gst_amount_{{ $rowIndex }}" class="form-control gst_amount" step="0.01" min="0" value="{{ $gstAmount }}">
                </td>
                <td style="min-width: 120px;">
                    <input readonly type="number" name="net_amount[]" id="net_amount_{{ $rowIndex }}" class="form-control net_amount" readonly value="{{ $netAmount }}">
                </td>
                <td style="min-width: 150px;">
                    <input readonly type="text" name="line_desc[]" id="line_desc_{{ $rowIndex }}" class="form-control line_desc" value="{{ $lineDesc }}">
                </td>
                <td style="min-width: 120px;">
                    <input readonly type="text" name="truck_no[]" id="truck_no_{{ $rowIndex }}" class="form-control truck_no" value="{{ $truckNo }}">
                </td>
                <td style="min-width: 80px;">
                    <button type="button" class="btn btn-danger btn-sm removeRowBtn" onclick="removeRow({{ $rowIndex }})" style="width:60px;">
                        <i class="fa fa-trash"></i>
                    </button>
                </td>
            </tr>
            @php $rowIndex++; @endphp
        @endforeach
    @endforeach
@endif

<script>

     function check_balance(el, target) {
        // Validation removed to allow excess returns for weight gain
    }

    function change_qty(el) {
        const packing = $(el).closest("tr").find(".packing");
        const no_of_bags = $(el).closest("tr").find(".no_of_bags");
        const qty = $(el).closest('tr').find(".qty");

        const result = parseFloat( parseFloat(packing.val() / qty.val()));
        no_of_bags.val(result);
    }
    
</script>
