
@foreach ($dataItems ?? [] as $key => $data)
    @php
        $remainingQty = $data->qc?->accepted_quantity;

    @endphp
    {{-- @php
   

    $currentRate = $data->rate ?? 0;
    $currentQty = $data->qty ?? 0;
    $currentTotal = ($currentRate !== '' && $currentQty > 0) ? (float)$currentRate * (float)$currentQty : '';

   // $currentSupplierId = $quotedSupplierId ?: '';
    //$currentSupplierName = $quoted
    <p>test</p>SupplierName ?: '';
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
@if ($remainingQty <= 0) @continue @endif; --}}

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
            <input type="hidden" name="purchase_order_receiving_data_id[]" value="{{ $data->id }}">
        </td>

        <td style="width: 30%">
            <input type="text" style="width: 100%;" name="description[]" value=""
                id="description_{{ $key }}" class="form-control uom">
        </td>

        <td style="width: 30%">
            <input style="width: 100%" type="number" onkeyup=""
                onblur="" name="qty[]" value="{{ $remainingQty }}"
                id="qty_{{ $key }}" class="form-control qty" step="0.01" readonly {{-- {{ $isQuotationAvailable ? 'readonly' : '' }} --}}>
        </td>

        <td style="width: 30%">
            <input style="width: 100px" type="number" onkeyup=""
                onblur="" name="rate[]" value="{{ $data->purchase_order_data->rate }}"
                id="rate_{{ $key }}" class="form-control rate" step="0.01" readonly>
        </td>

        <td style="width: 30%">
            <input type="text" style="width: 100px;" name="gross_amount[]"
                value="{{ $remainingQty * $data->purchase_order_data->rate }}" id="gross_amount{{ $key }}"
                class="form-control gross_amount" readonly>
        </td>

        <td style="width: 30%">


            <input style="width: 100px" type="number" name="discount_id[]" value="{{ 0 }}"
                id="total_{{ $key }}" class="form-control discounts" onkeyup="calculatePercentage(this)"
                step="0.01" min="0">
        </td>

        <td style="width: 30%">
            <input style="width: 100px" type="number" readonly name="discount_amount[]" value="0"
                id="discount_amount_{{ $key }}" class="form-control discount_amount" step="0.01"
                min="0" readonly>
        </td>
        <td style="width: 30%">
            <input style="width: 100px" type="number" readonly name="deduction_per_piece[]" readonly
                id="deduction_per_piece_{{ $key }}" value="{{ $data->qc?->deduction_per_bag ?? 0 }}"
                class="form-control deduction_per_piece" step="0.01" min="0">
        </td>

        <td style="width: 30%">
            <input style="width: 100px" type="number" readonly name="deduction[]"
                value="{{ ($data->qc?->deduction_per_bag ?? 0) * $remainingQty }}" id="deduction_{{ $key }}"
                class="form-control deduction" step="0.01" min="0" readonly>
        </td>

        @php
            $net_amount = ($remainingQty * $data->purchase_order_data->rate) - (($data->qc?->deduction_per_bag ?? 0) * $remainingQty);
        @endphp

        <td style="width: 30%">
            <input style="width: 100px" type="number" readonly name="net_amount[]"
                value="{{ $net_amount }}" id="total_{{ $key }}"
                class="form-control net_amount" step="0.01" min="0" readonly>
        </td>

        <td style="width:150px;">
            <input type="file" name="printing_sample[]" id="printing_sample_{{ $key }}" disabled class="form-control" accept="image/*,application/pdf">
            @if (!empty($data->purchase_order_data->printing_sample))
                <small>
                    <a href="{{ asset('storage/' . $data->purchase_order_data->printing_sample) }}" target="_blank">
                        View existing file
                    </a>
                </small>
            @endif
        </td>

        <td style="width: 30%">
            <input style="width: 100px" type="number" onkeyup="calculatePercentage(this)" name="tax_id[]"
                value="{{ getTaxPercentageById($data->sales_tax) }}" id="tax_id_{{ $key }}"
                class="form-control tax_id" step="0.01" min="0" readonly>
        </td>
        @php
            $gst_amount = (getTaxPercentageById($data->sales_tax) / 100) * ($net_amount);
        @endphp
        <td style="width: 30%">
            <input style="width: 100px" type="number" readonly onkeyup="calculatePercentage(this)" name="tax_amount[]"
                value="{{ $gst_amount }}"
                id="tax_id_{{ $key }}" class="form-control tax_amount" step="0.01" min="0"
                readonly>
        </td>


        @php
            $final_amount = ($remainingQty * $data->purchase_order_data->rate + (getTaxPercentageById($data->sales_tax) / 100) * ($remainingQty * $data->purchase_order_data->rate));
        @endphp


        <td style="width: 30%">
            <input style="width: 100px" type="number" readonly name="final_amount[]"
                value="{{  $net_amount + $gst_amount }}"
                id="final_amount_{{ $key }}" class="form-control final_amount" step="0.01"
                min="0" readonly>
        </td>


        <td>
            <button type="button" class="btn btn-danger btn-sm removeRowBtn" onclick="remove({{ $key }})"
                data-id="{{ $key }}" disabled>Remove</button>
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

     function round(num, decimals = 2) {
        return Number(Math.round(num + "e" + decimals) + "e-" + decimals);
    }

    function calculatePercentage(el) {
       const row = $(el).closest("tr");

    const gross_amount = row.find(".gross_amount");
    const rate = row.find(".rate");
    const qty = row.find(".qty");
    const discount_percent = row.find(".discounts");
    const final_amount = row.find(".final_amount");
    const tax_amount_input = row.find(".tax_amount");
    const discount_amount = row.find(".discount_amount");
    const tax_percent = row.find(".tax_id");
    const percent_amount = row.find(".percent_amount");
    const net_amount = row.find(".net_amount");
    const deduction_amount = row.find(".deduction").val();

    const rateVal = parseFloat(rate.val()) || 0;
    const qtyVal = parseFloat(qty.val()) || 0;
    const discountPercentVal = parseFloat(discount_percent.val()) || 0;
    const taxPercentVal = parseFloat(tax_percent.val()) || 0;

    // const percent_amount_of_gross = 1;

    // Clean values
    const gross = rateVal * qtyVal;
    gross_amount.val(gross);

    const net_amount_value = gross;
    const discount_amount_value =
        (discountPercentVal / 100) * gross;

    // Tax calculation
    const tax_amount =
        (taxPercentVal / 100) * ((net_amount_value - discount_amount_value) - deduction_amount);

    const tax_amount_rounded = round(tax_amount);
    const net_amount_rounded = round(gross - discount_amount_value);

    // Set values
    tax_amount_input.val(tax_amount_rounded);
    net_amount.val((net_amount_rounded - deduction_amount));
    discount_amount.val((discountPercentVal / 100) * net_amount_value);
    console.log(net_amount_value);
    // IMPORTANT: Use rounded tax value
    final_amount.val(round((net_amount_rounded - deduction_amount) + tax_amount_rounded));
    }

</script>
