@foreach ($dataItems ?? [] as $key => $data)
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
                onkeyup="calc({{ $key }})"
                onblur="calc({{ $key }})"
                name="qty[]"
                value="{{ $data->qty }}"
                id="qty_{{ $key }}"
                class="form-control qty"
                step="0.01"
                min="0"
                max="1"
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
                value="{{ $data->rate }}"
                id="rate_{{ $key }}" 
                class="form-control rate" 
                step="0.01" 
                min="0">
        </td>

        <td style="width: 30%">
            <input type="text" style="width: 100px;" name="gross_amount[]" value="{{ ($data->qty) * $data->rate }}" id="gross_amount{{ $key }}"
                class="form-control gross_amount" readonly>
        </td>


        <td style="width: 30%">
            <select style="width: 100%" onchange="calculatePercentage(this)" id="tax_id_{{ $key }}" name="tax_id[]" 
                onchange="calc({{ $key }})" class="form-control item-select select2 taxes">
                <option value="" selected data-percentage="0">Select Tax</option>
                @foreach ($taxes as $tax)
                    <option value="{{ $tax->id }}" data-percentage="{{ $tax->percentage }}">
                        {{ $tax->name . ' (' . $tax->percentage . ')%' }}
                    </option>
                @endforeach
            </select>
        </td>

        <td style="width: 30%">
            <input style="width: 100px" type="number" readonly name="total[]" value="{{ (($data->qty) * $data->rate) + ((0 / 100) * (($data->qty) * $data->rate)) }}"
                id="total_{{ $key }}" class="form-control net_amount" step="0.01" min="0">
        </td>


        
        <td style="width: 30%">
            <select style="width: 100%" onchange="calculatePercentage(this)" id="discount_id_{{ $key }}" name="discount_id[]" 
                onchange="calc({{ $key }})" class="form-control item-select select2 discounts">
                <option value="" selected data-percentage="0">Select Discount</option>
                <option value="1" data-percentage="10">Discount (10%)</option>
            </select>
        </td>

        <td style="width: 30%">
            <input style="width: 100px" type="number" readonly name="discount_amount[]" value="0"
                id="discount_amount_{{ $key }}" class="form-control discount_amount" step="0.01" min="0">
        </td>

        <td style="width: 30%">
            <input style="width: 100px" type="number" readonly name="deduction[]" value="{{ (($data->qty) * $data->rate) + ((0 / 100) * (($data->qty) * $data->rate)) }}"
                id="deduction_{{ $key }}" class="form-control deduction" step="0.01" min="0">
        </td>

        <td style="width: 30%">
            <input style="width: 100px" type="number" readonly name="final_amount[]"  value="{{ (($data->qty) * $data->rate) + ((0 / 100) * (($data->qty) * $data->rate)) }}"
                id="final_amount_{{ $key }}" class="form-control final_amount" step="0.01" min="0">
        </td>


        
        {{-- <td style="width: 5%">
                                    <div class="loop-fields">
                                        <div class="form-group mb-0">
                                        
                                            @if (!empty($item->printing_sample))
                                                <small>
                                                    <a href="{{ asset('storage/' . $item->printing_sample) }}" target="_blank">
                                                        View existing file
                                                    </a>
                                                </small>
                                                @else
                                                <span>No Attachment</span>
                                            @endif
                                        </div>
                                    </div>
                                </td> --}}


    

        <td>
            <button type="button" class="btn btn-danger btn-sm removeRowBtn" onclick="remove({{ $key }})"
                data-id="{{ $key }}">Remove</button>
        </td>
    </tr>
@endforeach


<script>

$(document).ready(function() {
    const taxes = $(".taxes");
    const discounts = $(".discounts");

    taxes.each((index, element) => {
        $(element).select2();
    });

    discounts.each((index, element) => {
        $(element).select2();
    });
});

function calculatePercentage(el) {
    const gross_amount = $(el).closest("tr").find(".gross_amount");
    const rate = $(el).closest("tr").find(".rate");
    const qty = $(el).closest("tr").find(".qty");
    const discount_percent = $(el).closest("tr").find(".discounts");
    const final_amount = $(el).closest("tr").find(".final_amount");

    const discount_percent_val = $(el).closest("tr").find(".discounts option:selected").data("percentage");
    const discount_amount = $(el).closest("tr").find(".discount_amount");

    gross_amount.val(rate.val() * qty.val());
    
    const tax_percent = $(el)
            .closest("tr")
            .find(".taxes option:selected")
            .data("percentage");

    const percent_amount = $(el).closest("tr").find(".percent_amount");
    const net_amount = $(el).closest("tr").find(".net_amount");

   

    const percent_amount_of_gross = (parseFloat(tax_percent) / 100) * parseFloat(gross_amount.val());
    const net_amount_value = parseFloat(gross_amount.val()) + parseFloat(percent_amount_of_gross);
    const discount_amount_value = (parseFloat(discount_percent_val) / 100) * parseFloat(net_amount_value);
   
    discount_amount.val(discount_amount_value);
    percent_amount.val(percent_amount_of_gross);
    net_amount.val(net_amount_value)
    final_amount.val(net_amount_value - discount_amount_value);


}
</script>

