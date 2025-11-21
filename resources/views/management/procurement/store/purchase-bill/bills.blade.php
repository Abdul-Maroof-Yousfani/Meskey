@foreach ($dataItems ?? [] as $key => $data)

 @php
    $remainingQty = $data->qty -  totalBillQuantityCreated($data->purchase_order_receiving_id, $data->item_id);
  
@endphp
    {{-- @php
   

    $currentRate = $data->rate ?? 0;
    $currentQty = $data->qty ?? 0;
    $currentTotal = ($currentRate !== '' && $currentQty > 0) ? (float)$currentRate * (float)$currentQty : '';

   // $currentSupplierId = $quotedSupplierId ?: '';
    //$currentSupplierName = $quotedSupplierName ?: '';
@endphp


@if (isset($data->purchase_order_data))
    @php
        $totalOrdered = $data->purchase_order_data->sum('qty');
    @endphp
@else
    @php
        $totalOrdered = 0;
    @endphp
@endif

@php
    $remainingQty = $data->qty - $totalOrdered;
    $isQuotationAvailable = ($data->rate) > 0 ? true : false;
@endphp
@if($remainingQty <= 0) @continue @endif; --}}
   
    <tr id="row_{{ $key }}">
      


        <td style="width: 20%">
            <select id="item_id_{{ $key }}" onchange="get_uom({{ $key }})"
                class="form-control item-select select2" data-index="{{ $key }}" disabled>
                @foreach (get_product_by_category($data->category_id) as $item)
                    <option data-uom="{{ $item->unitOfMeasure->name ?? '' }}" value="{{ $item->id }}"
                        {{ $item->id == $data->item_id ? 'selected' : '' }}>
                        {{ $item->name }}
                    </option>
                @endforeach
            </select>

            <input type="hidden" name="item_id[]" value="{{ $data->item_id }}">
        </td>

        <td style="width: 30%">
            <input type="text" style="width: 100%;" name="description[]" value="" id="description_{{ $key }}"
                class="form-control uom">
        </td>
       
        <td style="width: 30%">
            <input
                style="width: 100%"
                type="number"
                onkeyup="calc({{ $key }}); calculatePercentage(this)"
                onblur="calc({{ $key }})"
                name="qty[]"
                value="{{ $remainingQty }}"
                id="qty_{{ $key }}"
                class="form-control qty"
                step="0.01"
                {{-- {{ $isQuotationAvailable ? 'readonly' : '' }} --}}
            >
        </td>

          <td style="width: 30%">
            <input 
                style="width: 100px" 
                type="number"
                onkeyup="calc({{ $key }}); calculatePercentage(this)"
                onblur="calc({{ $key }})"
                name="rate[]" 
                value="{{ $data->purchase_order_data->rate }}"
                id="rate_{{ $key }}" 
                class="form-control rate" 
                step="0.01" 
                >
        </td>

        <td style="width: 30%">
            <input type="text" style="width: 100px;" name="gross_amount[]" value="{{ ($remainingQty) * $data->purchase_order_data->rate }}" id="gross_amount{{ $key }}"
                class="form-control gross_amount" readonly>
        </td>


        <td style="width: 30%">
            <input style="width: 100px" type="number" onkeyup="calculatePercentage(this)" name="tax_id[]" value="{{ getTaxPercentageById($data->sales_tax) }}"
                id="tax_id_{{ $key }}" class="form-control tax_id" step="0.01" min="0">
        </td>
        <td style="width: 30%">
            <input style="width: 100px" type="number"  readonly onkeyup="calculatePercentage(this)" name="tax_amount[]" value="{{ (getTaxPercentageById($data->sales_tax) / 100) * ($remainingQty * $data->purchase_order_data->rate) }}"
                id="tax_id_{{ $key }}" class="form-control tax_amount" step="0.01" min="0">
        </td>

        <td style="width: 30%">
            <input style="width: 100px" type="number" readonly name="net_amount[]" value="{{ (($remainingQty) * $data->purchase_order_data->rate) }}"
                id="total_{{ $key }}" class="form-control net_amount" step="0.01" min="0">
        </td>


        
        <td style="width: 30%">
           

            <input style="width: 100px" type="number" name="discount_id[]" value="{{ 0 }}"
                id="total_{{ $key }}" class="form-control discounts" onkeyup="calculatePercentage(this)" step="0.01" min="0">
        </td>

        <td style="width: 30%">
            <input style="width: 100px" type="number" readonly name="discount_amount[]" value="0"
                id="discount_amount_{{ $key }}" class="form-control discount_amount" step="0.01" min="0">
        </td>
        <td style="width: 30%">
            <input style="width: 100px" type="number" readonly name="deduction_per_piece[]"
                id="deduction_per_piece_{{ $key }}" value="{{ $data->qc?->deduction_per_bag ?? 0 }}" class="form-control deduction_per_piece" step="0.01" min="0">
        </td>

        <td style="width: 30%">
            <input style="width: 100px" type="number" readonly name="deduction[]" value="{{ ($data->qc?->deduction_per_bag ?? 0) * $remainingQty }}"
                id="deduction_{{ $key }}" class="form-control deduction" step="0.01" min="0">
        </td>

        <td style="width: 30%">
            <input style="width: 100px" type="number" readonly name="final_amount[]"  value="{{ (($remainingQty) * $data->purchase_order_data->rate) + ((getTaxPercentageById($data->sales_tax) / 100) * ($remainingQty * $data->purchase_order_data->rate)) }}"
                id="final_amount_{{ $key }}" class="form-control final_amount" step="0.01" min="0">
        </td>
    

        <td>
            <button type="button" class="btn btn-danger btn-sm removeRowBtn" onclick="remove({{ $key }})"
                data-id="{{ $key }}">Remove</button>
        </td>
    </tr>
@endforeach


<script>

$(document).ready(function() {
    const taxes = $(".taxes");

    taxes.each((index, element) => {
        $(element).select2();
    });
});

function calculatePercentage(el) {
    const gross_amount = $(el).closest("tr").find(".gross_amount");
    const rate = $(el).closest("tr").find(".rate");
    const qty = $(el).closest("tr").find(".qty");
    const discount_percent = $(el).closest("tr").find(".discounts");
    const final_amount = $(el).closest("tr").find(".final_amount");
    const tax_amount_input = $(el).closest("tr").find(".tax_amount");

    const discount_percent_val = discount_percent.val();
    const discount_amount = $(el).closest("tr").find(".discount_amount");
    
    gross_amount.val(rate.val() * qty.val());
    
    const tax_percent = $(el)
            .closest("tr")
            .find(".tax_id");

    const percent_amount = $(el).closest("tr").find(".percent_amount");
    const net_amount = $(el).closest("tr").find(".net_amount");

   

    const percent_amount_of_gross = 1;
    const net_amount_value = parseFloat(gross_amount.val()) + parseFloat(percent_amount_of_gross);
    const discount_amount_value = (parseFloat(discount_percent_val) / 100) * parseFloat(gross_amount.val());
    const tax_amount = (parseInt(tax_percent.val()) / 100) * (net_amount_value - discount_amount_value);
   

    tax_amount_input.val(tax_amount);
    net_amount.val(gross_amount.val() - discount_amount_value);
    percent_amount.val(percent_amount_of_gross);
    discount_amount.val((discount_percent_val / 100) * (net_amount_value));
    console.log(net_amount_value);
    final_amount.val(parseFloat(net_amount.val()) + parseFloat(tax_amount));


}
</script>

