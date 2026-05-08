
@foreach ($dataItems ?? [] as $key => $data)
    @php
        $hasQc = !is_null($data->qc);
        $remainingQty = $hasQc ? ($data->qc->accepted_quantity ?? 0) : ($data->qty ?? 0);
        $rejectedQty = $hasQc ? ($data->qc->rejected_quantity ?? 0) : 0;
        $deductionPerBag = $hasQc ? ($data->qc->deduction_per_bag ?? 0) : 0;
        $deduction_type = $hasQc ? ($data->qc->deduction_type ?? '') : '';
        $deduction = 0;
        $acceptedQty = $hasQc ? ($data->qc->accepted_quantity ?? 0) : ($data->qty ?? 0);

        if($hasQc && $deduction_type != '') {
            if($deduction_type == 'full_deduction') {
                $remainingQty += $rejectedQty;
                $deduction = $remainingQty * $deductionPerBag;
            } else if($deduction_type == 'half_deduction') {
                $deduction = $rejectedQty * $deductionPerBag;
                $remainingQty += $rejectedQty;
            }
        }
    @endphp


<tr id="row_{{ $key }}" data-category-id="{{ $data->category_id }}">
        <td style="min-width: 250px;">
            <select name="category_id[]" id="category_id_{{ $key }}" class="form-control category-select select2" disabled>
                <option value="">Select Category</option>
                @foreach ($categories ?? [] as $category)
                    <option value="{{ $category->id }}" @selected($data->category_id == $category->id)>
                        {{ $category->name }}
                    </option>
                @endforeach
            </select>
            <input type="hidden" name="category_id[]" value="{{ $data->category_id }}">
        </td>

        <td style="min-width: 350px;">
            <select id="item_id_{{ $key }}" onchange="get_uom({{ $key }})"
                class="form-control item-select select2" data-index="{{ $key }}" disabled>
                @foreach (get_product_by_id($data->item_id) as $item)
                    <option data-uom="{{ $item->unitOfMeasure->name ?? '' }}" value="{{ $item->id }}"
                        {{ $item->id == $data->item_id ? 'selected' : '' }}>
                        {{ $item->name }}
                    </option>
                @endforeach
            </select>

            <input type="hidden" name="item_id[]" value="{{ $data->item_id }}">
            <input type="hidden" name="purchase_order_receiving_data_id[]" value="{{ $data->id }}">
        </td>

        <td style="min-width: 450px;">
            <input type="text" style="width: 100%;" name="description[]" value=""
                id="description_{{ $key }}" class="form-control uom">
        </td>

        <td style="min-width: 150px;">
            <input style="width: 100%" type="number" onkeyup=""
                onblur="" name="qty[]" value="{{ $remainingQty }}"
                id="qty_{{ $key }}" class="form-control qty" step="0.01" readonly {{-- {{ $isQuotationAvailable ? 'readonly' : '' }} --}}>
        </td>

        <td style="min-width: 150px;">
            <input style="width: 100%" type="number" onkeyup=""
                onblur="" name="accepted_qty[]" value="{{ $acceptedQty }}"
                id="accepted_qty_{{ $key }}" class="form-control accepted_qty" step="0.01" readonly {{-- {{ $isQuotationAvailable ? 'readonly' : '' }} --}}>
        </td>

        <td style="min-width: 150px;">
            <input style="width: 100%" type="number" onkeyup=""
                onblur="" name="rejected_qty[]" value="{{ $rejectedQty }}"
                id="rejected_qty_{{ $key }}" class="form-control rejected_qty" step="0.01" readonly {{-- {{ $isQuotationAvailable ? 'readonly' : '' }} --}}>
        </td>

        <td style="min-width: 150px;">
            <input style="width: 100%" type="number" onkeyup=""
                onblur="" name="rate[]" value="{{ $data->purchase_order_data->rate }}"
                id="rate_{{ $key }}" class="form-control rate" step="0.01" readonly>
        </td>

        <td style="min-width: 200px;">
            <input type="text" style="width: 100%;" name="gross_amount[]"
                value="{{ $remainingQty * $data->purchase_order_data->rate }}" id="gross_amount{{ $key }}"
                class="form-control gross_amount" readonly>
        </td>

        <td style="min-width: 150px;">


            <input style="width: 100%" type="number" name="discount_id[]" value="{{ 0 }}"
                id="total_{{ $key }}" class="form-control discounts" onkeyup="calculatePercentage(this)"
                step="0.01" min="0" max="100">
        </td>

        <td style="min-width: 200px;">
            <input style="width: 100%" type="number" readonly name="discount_amount[]" value="0"
                id="discount_amount_{{ $key }}" class="form-control discount_amount" step="0.01"
                min="0" readonly>
        </td>
        <td style="min-width: 200px;" class="deduction-col">
            <input style="width: 100%" type="number" readonly name="deduction_per_piece[]" readonly
                id="deduction_per_piece_{{ $key }}" value="{{ $data->qc?->deduction_per_bag ?? 0 }}"
                class="form-control deduction_per_piece" step="0.01" min="0">
        </td>

        <td style="min-width: 200px;" class="deduction-col">
            <input style="width: 100%" type="number" readonly name="deduction[]"
                value="{{ $deduction }}" id="deduction_{{ $key }}"
                class="form-control deduction" step="0.01" min="0" readonly>
        </td>

        @php
            // $deduction = ($data->category_id == 38) ? (($data->qc?->deduction_per_bag ?? 0) * $remainingQty) : 0;
            $net_amount = ($remainingQty * $data->purchase_order_data->rate) - $deduction;
        @endphp

        <td style="min-width: 200px;">
            <input style="width: 100%" type="number" readonly name="net_amount[]"
                value="{{ $net_amount }}" id="total_{{ $key }}"
                class="form-control net_amount" step="0.01" min="0" readonly>
        </td>

        <td style="min-width:250px;">
            <input style="width: 100%" type="file" name="printing_sample[]" id="printing_sample_{{ $key }}" disabled class="form-control" accept="image/*,application/pdf">
            @if (!empty($data->purchase_order_data->printing_sample))
                @foreach((array)$data->purchase_order_data->printing_sample as $sample)
                    <small class="d-block">
                        <a href="{{ asset('storage/' . $sample) }}" target="_blank">
                            View file
                        </a>
                    </small>
                @endforeach
            @endif
        </td>

        <td style="min-width: 150px;">
            <input style="width: 100%" type="number" onkeyup="calculatePercentage(this)" name="tax_id[]"
                value="{{ getTaxPercentageById($data->sales_tax) }}" id="tax_id_{{ $key }}"
                class="form-control tax_id" step="0.01" min="0" max="100">
        </td>
        @php
            $gst_amount = (getTaxPercentageById($data->sales_tax) / 100) * ($net_amount);
        @endphp
        <td style="min-width: 200px;">
            <input style="width: 100%" type="number" readonly onkeyup="calculatePercentage(this)" name="tax_amount[]"
                value="{{ $gst_amount }}"
                id="tax_id_{{ $key }}" class="form-control tax_amount" step="0.01" min="0"
                readonly>
        </td>


        @php
            $final_amount = ($remainingQty * $data->purchase_order_data->rate + (getTaxPercentageById($data->sales_tax) / 100) * ($remainingQty * $data->purchase_order_data->rate));
        @endphp


        <td style="min-width: 250px;">
            <input style="width: 100%" type="number" readonly name="final_amount[]"
                value="{{  $net_amount + $gst_amount }}"
                id="final_amount_{{ $key }}" class="form-control final_amount" step="0.01"
                min="0" readonly>
        </td>


        <td style="min-width: 150px;">
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
    const deduction_input = row.find(".deduction");
    const categoryId = row.data("category-id");
    const deduction_amount = parseFloat(deduction_input.val()) || 0;

    const rateVal = parseFloat(rate.val()) || 0;
    const qtyVal = parseFloat(qty.val()) || 0;
    let discountPercentVal = parseFloat(discount_percent.val()) || 0;
    let taxPercentVal = parseFloat(tax_percent.val()) || 0;

    if (discountPercentVal > 100) {
        alert("Discount Percentage cannot exceed 100");
        discount_percent.val(100);
        discountPercentVal = 100;
    }

    if (taxPercentVal > 100) {
        alert("Tax Percentage cannot exceed 100");
        tax_percent.val(100);
        taxPercentVal = 100;
    }

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
