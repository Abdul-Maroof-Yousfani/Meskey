@foreach ($dataItems ?? [] as $key => $data)
    @php
   

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


   

    <tr id="row_{{ $key }}">
      

        <td style="width: 20%">
            <select id="category_id_{{ $key }}" onchange="filter_items(this.value,{{ $key }})"
                class="form-control item-select select2" data-index="{{ $key }}" disabled>
                <option value="">Select Category</option>
                @foreach ($categories ?? [] as $category)
                    <option {{ $category->id == $data->category_id ? 'selected' : '' }} value="{{ $category->id }}">
                        {{ $category->name }}</option>
                @endforeach
            </select>
            <input type="hidden" name="category_id[]" value="{{ $data->category_id }}">
            <input type="hidden" name="purchase_request_data_id[]" value="{{ !isset($data->rate) ? $data->id : '' }}">
            <input type="hidden" name="purchase_quotation_data_id[]" value="{{ isset($data->rate) ? $data->id : '' }}">

        </td>

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

        <td style="width: 15%">
            <input type="text" name="uom[]" value="{{ get_uom($data->item_id) }}" id="uom_{{ $key }}"
                class="form-control uom" readonly>
        </td>

      

      
        <td style="width: 10%">
    <input
        style="width: 100px"
        type="number"
        onkeyup="calc({{ $key }})"
        onblur="calc({{ $key }})"
        name="qty[]"
        value="{{ $remainingQty }}"
        id="qty_{{ $key }}"
        class="form-control"
        step="0.01"
        min="0"
        max="{{ $remainingQty }}"
        {{-- {{ $isQuotationAvailable ? 'readonly' : '' }} --}}
    >

    <div class="d-flex align-items-center">
        Total Qty: {{ $data->total_quoted_qty+$data->qty }}
    </div>

    <div class="d-flex align-items-center">
        Ordered Qty: {{ $data->total_quoted_qty }}
    </div>
</td>
        <td style="width: 10%">
            <input 
                style="width: 70px" 
                type="number"
                onkeyup="calc({{ $key }})"
                onblur="calc({{ $key }})"
                name="rate[]" 
                value="{{ $data->rate }}"
                id="rate_{{ $key }}" 
                class="form-control" 
                step="0.01" 
                min="0"
                {{ $isQuotationAvailable ? 'readonly' : '' }}>
        </td>
          <td style="width: 15%">
            <input type="text" name="gross_amount[]" value="{{ ($data->qty) * $data->rate }}" id="gross_amount{{ $key }}"
                class="form-control gross_amount" readonly>
        </td>
        <td style="width: 5%">
            <select style="width: 50px" onchange="calculatePercentage(this)" id="tax_id_{{ $key }}" name="tax_id[]" 
                onchange="calc({{ $key }})" class="form-control item-select select2">
                <option value="">Select Tax</option>
                @foreach ($taxes as $tax)
                    <option selected value="{{ $tax->id }}" data-percentage="{{ $tax->percentage }}">
                        {{ $tax->name . ' (' . $tax->percentage . ')%' }}
                    </option>
                @endforeach
            </select>
        </td>
        <td style="width: 15%">
            <input type="text" name="tax_amount[]" value="{{ ($tax->percentage / 100) * (($data->qty) * $data->rate) }}" id="tax_amount{{ $key }}"
                class="form-control tax_amount percent_amount" readonly>
        </td>
        

        <td style="width: 10%">
            <input style="width: 70px" type="number" oninput="calc({{ $key }})" name="excise_duty[]" value=""
                id="excise_duty_{{ $key }}" class="form-control" step="0.01" min="0">
        </td>

        <td style="width: 10%">
            <input style="width: 100px" type="number" readonly name="total[]" value="{{ (($data->qty) * $data->rate) + (($tax->percentage / 100) * (($data->qty) * $data->rate)) }}"
                id="total_{{ $key }}" class="form-control net_amount" step="0.01" min="0">
        </td>

         <td style="width: 5%">
            <input style="width: 50px" type="number" readonly name="min_weight[]" value="{{ $data->purchase_request->min_weight }}"
                id="min_weight_{{ $key }}" class="form-control" step="0.01" min="0">
        </td>
         <td style="width: 5%">
            <input style="width: 50px" type="text" readonly name="color[]" value="{{ $data->purchase_request->color }}"
                id="color_{{ $key }}" class="form-control" step="0.01" min="0">
        </td>
         <td style="width: 5%">
            <input style="width: 50px" type="text" readonly name="construction_per_square_inch[]" value="{{ $data->purchase_request->construction_per_square_inch }}"
                id="construction_per_square_inch_{{ $key }}" class="form-control" step="0.01" min="0">
        </td>
         <td style="width: 5%">
            <input style="width: 50px" type="text" readonly name="size[]" value="{{ $data->purchase_request->size }}"
                id="size_{{ $key }}" class="form-control" step="0.01" min="0">
        </td>
         <td style="width: 5%">
            <input style="width: 50px" type="text" readonly name="stitching[]" value="{{ $data->purchase_request->stitching }}"
                id="stitching_{{ $key }}" class="form-control" step="0.01" min="0">
        </td>
        <td style="width: 5%">
            <div class="loop-fields">
                <div class="form-group mb-0">
                    {{-- <input type="file" name="printing_sample[]" id="printing_sample_{{ $key }}"
                        class="form-control" accept="image/*,application/pdf" placeholder="Printing Sample"> --}}
                     <input type="hidden" name="printing_sample[]" id="printing_sample_{{ $key }}" value="{{ $data->printing_sample }}"
                                            class="form-control" accept="image/*,application/pdf" placeholder="Printing Sample">

                    @if (!empty($data->printing_sample))
                        <small>
                            <a href="{{ asset('storage/' . $data->printing_sample) }}" target="_blank">
                                View existing file
                            </a>
                        </small>
                        @else
                        <span>No Attach.</span>
                    @endif
                </div>
            </div>
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


        <td style="width: 25%">
            <input style="width: 100px" type="text" name="remarks[]" value=""
                id="remark_{{ $key }}" class="form-control">
        </td>

        <td>
            <button type="button" class="btn btn-danger btn-sm removeRowBtn" {{ $isQuotationAvailable ? 'disabled' : '' }} onclick="remove({{ $key }})"
                data-id="{{ $key }}">Remove</button>
        </td>
    </tr>
@endforeach


<script>
function calculatePercentage(el) {

    const gross_amount = $(el).closest("tr").find(".gross_amount");
    const tax_percent = $(el).find(":selected").data("percentage");
    const percent_amount = $(el).closest("tr").find(".percent_amount");
    const net_amount = $(el).closest("tr").find(".net_amount");

    const percent_amount_of_gross = (parseFloat(tax_percent) / 100) * parseFloat(gross_amount.val());
    const net_amount_value = parseFloat(gross_amount.val()) + parseFloat(percent_amount_of_gross);
    percent_amount.val(percent_amount_of_gross);
    net_amount.val(net_amount_value)


}
</script>

