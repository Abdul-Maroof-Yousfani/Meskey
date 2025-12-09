@php $rowIndex = 0; @endphp
@foreach($delivery_challans as $delivery_challan)
    @foreach($delivery_challan->delivery_challan_data as $data)
        @php
            $balance = $balances[$data->id] ?? 0;
            
            // Skip items with 0 balance
            if ($balance <= 0) {
                continue;
            }
            
            $packing = $data->bag_size ?? 0;
            $noOfBags = $balance; // Use available balance as default
            $qty = $packing * $noOfBags;
            $rate = $data->rate ?? 0;
            $grossAmount = $qty * $rate;
            $discountPercent = 0;
            $discountAmount = 0;
            $amount = $grossAmount - $discountAmount;
            $gstPercent = 0;
            $gstAmount = 0;
            $netAmount = $amount + $gstAmount;
            $lineDesc = $data->description ?? '';
            $truckNo = $data->truck_no ?? '';
        @endphp
        <tr id="row_{{ $rowIndex }}">
            <td style="min-width: 200px;">
                <select name="item_id[]" id="item_id_{{ $rowIndex }}" class="form-control select2">
                    <option value="">Select Item</option>
                    @foreach ($items ?? [] as $item)
                        <option value="{{ $item->id }}" {{ $data->item_id == $item->id ? 'selected' : '' }}>{{ $item->name }}</option>
                    @endforeach
                </select>
                <input type="hidden" name="dc_data_id[]" value="{{ $data->id }}">
                <input type="hidden" class="max_balance" value="{{ $balance }}">
            </td>
            <td style="min-width: 100px;">
                <input type="number" name="packing[]" id="packing_{{ $rowIndex }}" onkeyup="calculateRow(this)" class="form-control packing" step="0.01" min="0" value="{{ $packing }}" readonly>
            </td>
            <td style="min-width: 100px;">
                <input type="number" name="no_of_bags[]" id="no_of_bags_{{ $rowIndex }}" onkeyup="calculateRow(this); validateBalance(this)" class="form-control no_of_bags" step="0.01" min="0" max="{{ $balance }}" value="{{ $noOfBags }}">
            </td>
            <td style="min-width: 100px;">
                <input type="number" name="qty[]" id="qty_{{ $rowIndex }}" class="form-control qty" step="0.01" min="0" readonly value="{{ $qty }}">
            </td>
            <td style="min-width: 100px;">
                <input type="number" name="rate[]" id="rate_{{ $rowIndex }}" onkeyup="calculateRow(this)" class="form-control rate" step="0.01" min="0" value="{{ $rate }}">
            </td>
            <td style="min-width: 120px;">
                <input type="number" name="gross_amount[]" id="gross_amount_{{ $rowIndex }}" class="form-control gross_amount" readonly value="{{ $grossAmount }}">
            </td>
            <td style="min-width: 100px;">
                <input type="number" name="discount_percent[]" id="discount_percent_{{ $rowIndex }}" onkeyup="calculateRow(this)" class="form-control discount_percent" step="0.01" min="0" max="100" value="{{ $discountPercent }}">
            </td>
            <td style="min-width: 120px;">
                <input type="number" name="discount_amount[]" id="discount_amount_{{ $rowIndex }}" class="form-control discount_amount" readonly value="{{ $discountAmount }}">
            </td>
            <td style="min-width: 120px;">
                <input type="number" name="amount[]" id="amount_{{ $rowIndex }}" class="form-control amount" readonly value="{{ $amount }}">
            </td>
            <td style="min-width: 100px;">
                <input type="number" name="gst_percent[]" id="gst_percent_{{ $rowIndex }}" onkeyup="calculateRow(this)" class="form-control gst_percent" step="0.01" min="0" value="{{ $gstPercent }}">
            </td>
            <td style="min-width: 120px;">
                <input type="number" name="gst_amount[]" id="gst_amount_{{ $rowIndex }}" class="form-control gst_amount" readonly value="{{ $gstAmount }}">
            </td>
            <td style="min-width: 120px;">
                <input type="number" name="net_amount[]" id="net_amount_{{ $rowIndex }}" class="form-control net_amount" readonly value="{{ $netAmount }}">
            </td>
            <td style="min-width: 150px;">
                <input type="text" name="line_desc[]" id="line_desc_{{ $rowIndex }}" class="form-control line_desc" value="{{ $lineDesc }}">
            </td>
            <td style="min-width: 120px;">
                <input type="text" name="truck_no[]" id="truck_no_{{ $rowIndex }}" class="form-control truck_no" value="{{ $truckNo }}">
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
