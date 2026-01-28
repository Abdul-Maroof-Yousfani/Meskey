@php $rowIndex = 0; @endphp
@foreach($purchase_bills as $bill)
    @foreach($bill->bill_data as $billData)
        @php
            $availableBalance = \App\Models\Procurement\Store\PurchaseReturnData::where('purchase_bill_data_id', $billData->id)->sum('quantity');
            $remainingQty = $billData->qty - $availableBalance;

            // Skip items with no remaining balance
            if ($remainingQty <= 0) {
                continue;
            }

            $packing = $billData->packing ?? 0;
            // $noOfBags = $remainingQty; // Use remaining quantity
            $quantity = purchaseBillDistribution($billData->id);
            $rate = $billData->rate ?? 0;
            $grossAmount = $quantity * $rate;
            $discountPercent = $billData->discount_percent ?? 0;
            $discountAmount = $billData->discount_amount  ?? 0;

            $taxPercent = $billData->tax_percent ?? 0;
            $taxAmount = $billData->tax_amount ?? 0;

            $deduction = $billData->deduction  ?? 0;
            $amount = $grossAmount - $discountAmount - $deduction;
            $netAmount = $amount + $taxAmount;
            $description = $billData->description ?? '';
        @endphp
        <tr id="row_{{ $rowIndex }}">
            <td style="min-width: 200px;">
                <select name="item_id[]" id="item_id_{{ $rowIndex }}" class="form-control select2">
                    <option value="">Select Item</option>
                    @foreach (\App\Models\Product::all() ?? [] as $item)
                        <option value="{{ $item->id }}" {{ $billData->item_id == $item->id ? 'selected' : '' }}>{{ $item->name }}</option>
                    @endforeach
                </select>
                <input type="hidden" class="form-control" value="{{ $billData->item->name ?? '' }}"  readonly/>
                <input type="hidden" class="form-control" value="{{ $billData->item_id }}"  readonly/>
                <input type="hidden" name="bill_data_id[]" value="{{ $billData->id }}">
            </td>
           
            <td style="min-width: 100px;">
                <input
                    type="number"
                    name="quantity[]"
                    id="quantity_{{ $rowIndex }}"
                    class="form-control quantity"
                    step="0.01"
                    min="0"
                    data-balance="{{ $remainingQty }}"
                    onkeyup="calc(this); check_balance(this, 'no_of_bags_{{ $rowIndex }}')"
                    value="{{ $quantity }}">
            </td>
            <td style="min-width: 100px;">
                <input readonly type="number" name="rate[]" id="rate_{{ $rowIndex }}" onkeyup="" class="form-control rate" step="0.01" min="0" value="{{ $rate }}">
            </td>
            <td style="min-width: 120px;">
                <input readonly type="number" name="gross_amount[]" id="gross_amount_{{ $rowIndex }}" class="form-control gross_amount" readonly value="{{ $grossAmount }}">
            </td>
            <td style="min-width: 100px;">
                <input readonly type="number" name="discount_percent[]" id="discount_percent_{{ $rowIndex }}" onkeyup="" class="form-control discount_percent" step="0.01" min="0" value="{{ $discountPercent }}">
            </td>
            <td style="min-width: 120px;">
                <input readonly type="number" name="discount_amount[]" id="discount_amount_{{ $rowIndex }}" class="form-control discount_amount" readonly value="{{ $discountAmount }}">
            </td>
            <td style="min-width: 120px;">
                <input readonly type="number" name="deduction[]" id="deduction_{{ $rowIndex }}" class="form-control deduction" readonly value="{{ $deduction }}">
            </td>
            <td style="min-width: 120px;">
                <input readonly type="number" name="amount[]" id="amount_{{ $rowIndex }}" class="form-control amount" readonly value="{{ $amount }}">
            </td>
            <td style="min-width: 100px;">
                <input readonly type="number" name="tax_percent[]" id="tax_percent_{{ $rowIndex }}" onkeyup="" class="form-control tax_percent" step="0.01" min="0" value="{{ $taxPercent }}">
            </td>
            <td style="min-width: 120px;">
                <input readonly type="number" name="tax_amount[]" id="tax_amount_{{ $rowIndex }}" class="form-control tax_amount" readonly value="{{ $taxAmount }}">
            </td>
            <td style="min-width: 120px;">
                <input readonly type="number" name="net_amount[]" id="net_amount_{{ $rowIndex }}" class="form-control net_amount" readonly value="{{ $netAmount }}">
            </td>
            <td style="min-width: 150px;">
                <input type="text" name="description[]" id="description_{{ $rowIndex }}" class="form-control description" value="{{ $description }}">
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

<script>

     function check_balance(el, target) {
        const balance = $(el).data("balance");
        const value = $("#" + target).val();

        if(value > balance) {
            Swal.fire({
                icon: 'warning',
                title: 'Limit Exceeded',
                text: 'Cannot proceed more than ' + balance,
            });

            $("#" + target).addClass("is-invalid");
        } else {
            $("#" + target).removeClass("is-invalid");
        }
    }

    function change_qty(el) {
        const packing = $(el).closest("tr").find(".packing");
        const no_of_bags = $(el).closest("tr").find(".no_of_bags");
        const quantity = $(el).closest('tr').find(".quantity");

        const result = parseFloat( parseFloat(packing.val() / quantity.val()));
        no_of_bags.val(result);
    }

</script>



















