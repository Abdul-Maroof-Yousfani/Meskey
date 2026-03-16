@foreach ($dataItems ?? [] as $key => $data)
    @php
   

    $currentRate = $data->rate ?? 0;
    $currentQty = $data->qty ?? 0;
    $currentTotal = ($currentRate !== '' && $currentQty > 0) ? (float)$currentRate * (float)$currentQty : '';

   // $currentSupplierId = $quotedSupplierId ?: '';
    //$currentSupplierName = $quotedSupplierName ?: '';
@endphp


@php
    if (isset($data->purchase_order_data)) {
        // PurchaseQuotationData or PurchaseRequestData with loaded POs
        $totalOrdered = $data->purchase_order_data->sum('qty');
        $totalQty = $data->qty;
        $remainingQty = $totalQty - $totalOrdered;
    } elseif (isset($data->purchase_request_data)) {
        // PurchaseOrderData (Edit mode)
        $totalOrdered = optional($data->purchase_request_data->purchase_order_data)->sum('qty') ?? 0;
        $totalQty = $data->purchase_request_data->qty ?? 0;
        $remainingQty = $totalQty - $totalOrdered;
    } else {
        // PurchaseRequestData (Create mode, via controller adjustment)
        $totalOrdered = $data->total_quoted_qty ?? 0;
        $totalQty = $totalOrdered + $data->qty;
        $remainingQty = $data->qty;
    }
    $isQuotationAvailable = ($data->rate) > 0 ? true : false;
@endphp
@if($remainingQty <= 0) @continue @endif;

   

    <tr id="row_{{ $key }}">
      

        <td style="min-width: 150px;">
            <select id="category_id_{{ $key }}" onchange="filter_items(this.value,{{ $key }})"
                class="form-control item-select select2" data-index="{{ $key }}" disabled>
                <option value="">Select Category</option>
                @foreach ($categories ?? [] as $category)
                    <option {{ $category->id == $data->category_id ? 'selected' : '' }} value="{{ $category->id }}">
                        {{ $category->name }}</option>
                @endforeach
            </select>
            <input type="hidden" name="category_id[]" value="{{ $data->category_id }}">
            <input type="hidden" name="purchase_request_data_id[]" value="{{ $data->purchase_request_data_id ? $data->purchase_request_data_id : $data->id }}">
            <input type="hidden" name="purchase_quotation_data_id[]" value="{{ isset($data->rate) ? $data->id : '' }}">

        </td>

        <td style="min-width: 150px;">
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
        </td>

        <td style="min-width: 120px;">
            <input type="text"  name="uom[]" value="{{ get_uom($data->item_id) }}" id="uom_{{ $key }}"
                class="form-control uom" readonly>
        </td>

      

      
        <td style="min-width: 120px;">
    <input
        
        type="number"
        onkeyup="calc({{ $key }})"
        onblur="calc({{ $key }})"
        name="qty[]"
        value="{{ $remainingQty }}"
        id="qty_{{ $key }}"
        class="form-control qty"
        step="0.01"
        min="0"
        max="{{ $remainingQty }}"
        {{-- {{ $isQuotationAvailable ? 'readonly' : '' }} --}}
    >

    <div class="d-flex align-items-center">
        balance: {{ $remainingQty }}
    </div>

    <div class="d-flex align-items-center">
        total qty: {{ $totalQty }}
    </div>
</td>
        <td style="min-width: 120px;">
            <input 
                 
                type="number"
                onkeyup="calc({{ $key }}); calculatePercentage(this)"
                onblur="calc({{ $key }})"
                name="rate[]" 
                value="{{ $data->rate }}"
                id="rate_{{ $key }}" 
                class="form-control rate" 
                step="0.01" 
                min="0"
                {{ $isQuotationAvailable && $data->am_approval_status == 'approved' ? 'readonly' : '' }}>
        </td>
          <td style="min-width: 120px;">
            <input type="text"  name="gross_amount[]" value="{{ ($data->qty) * $data->rate }}" id="gross_amount{{ $key }}"
                class="form-control gross_amount" readonly>
        </td>
        <td style="min-width: 100px;">
            <select  onchange="calculatePercentage(this)" id="tax_id_{{ $key }}" name="tax_id[]" 
                onchange="calc({{ $key }})" class="form-control item-select select2 taxes">
                <option value="" selected data-percentage="0">Select Tax</option>
                @foreach ($taxes as $tax)
                    <option value="{{ $tax->id }}" data-percentage="{{ $tax->percentage }}">
                        {{ $tax->name . ' (' . $tax->percentage . ')%' }}
                    </option>
                @endforeach
            </select>
        </td>
        <td style="min-width: 120px;">
            <input type="text"  name="tax_amount[]" value="{{ (getTaxPercentageById($data->tax_id) / 100) * (($data->qty) * $data->rate) }}" id="tax_amount{{ $key }}"
                class="form-control tax_amount percent_amount" readonly>
        </td>
        

        <td style="min-width: 120px;">
            <input  type="number" oninput="calc({{ $key }})" name="excise_duty[]" value=""
                id="excise_duty_{{ $key }}" class="form-control" step="0.01" min="0">
        </td>

        <td style="min-width: 120px;">
            <input  type="number" readonly name="total[]" value="{{ (($data->qty) * $data->rate) + ((0 / 100) * (($data->qty) * $data->rate)) }}"
                id="total_{{ $key }}" class="form-control net_amount" step="0.01" min="0">
        </td>



        <td style="min-width: 150px;" class="bag-only">
            <input  type="number" readonly name="min_weight[]" value="{{ $data->min_weight ? $data->min_weight : $data->purchase_request->min_weight }}"
                id="min_weight_{{ $key }}" class="form-control" step="0.01" min="0">
        </td>
        <td style="min-width: 150px;" class="bag-only">
            <input  type="text" readonly name="brand[]" value="{{ getBrandById($data->brand_id ? $data->brand_id : $data->purchase_request->brand_id)?->name ?? null }}"
                id="brand_{{ $key }}" class="form-control" step="0.01" min="0">
        </td>
         <td style="min-width: 150px;" class="bag-only">
            <input  type="text" readonly name="color[]" value="{{ getColorById($data->color ? $data->color : $data->purchase_request->color)?->color ?? null }}"
                id="color_{{ $key }}" class="form-control" step="0.01" min="0">
        </td>
         <td style="min-width: 150px;" class="bag-only">
            <input  type="text" readonly name="construction_per_square_inch[]" value="{{ $data->construction_per_square_inch ? $data->construction_per_square_inch : $data->purchase_request->construction_per_square_inch }}"
                id="construction_per_square_inch_{{ $key }}" class="form-control" step="0.01" min="0">
        </td>
         <td style="min-width: 150px;" class="bag-only">
            <input  type="text" readonly name="size[]" value="{{ getSizeById($data->size ? $data->size : $data->purchase_request->size)?->size ?? null }}"
                id="size_{{ $key }}" class="form-control" step="0.01" min="0">
        </td>
         <td style="min-width: 150px;" class="bag-only">
                <select class="form-control select2" multiple disabled>
                    @foreach(getStitchingsByIds($data->stitching ? $data->stitching : $data->purchase_request->stitching) as $stitching)
                        <option value="{{ $stitching->id }}" selected>{{ $stitching->name }}</option>
                    @endforeach
                </select>
            <input  type="hidden" readonly name="stitching[]"
                value="{{ $data->stitching }}" id="stitching_{{ $key }}"
                class="form-control" step="0.01" min="0">
        </td>
         <td style="min-width: 150px;" class="bag-only">
            <input  type="text" readonly name="micron[]" value="{{ $data->micron ? $data->micron : $data->purchase_request->micron }}"
                id="micron_{{ $key }}" class="form-control" step="0.01" min="0">
        </td>
        <td style="min-width: 150px;" class="bag-only">
            <div class="loop-fields">
                <div class="form-group mb-0">
                    @php
                        $printingSample = $data->printing_sample ?: $data->purchase_request->printing_sample;
                    @endphp
                     <input type="hidden" name="printing_sample[]" id="printing_sample_{{ $key }}" value="{{ is_array($printingSample) ? json_encode($printingSample) : $printingSample }}">

                    @if (!empty($printingSample))
                        @foreach((array)$printingSample as $sample)
                            <small class="d-block">
                                <a href="{{ asset('storage/' . $sample) }}" target="_blank">
                                    View file
                                </a>
                            </small>
                        @endforeach
                    @else
                        <span>No Attach.</span>
                    @endif
                </div>
            </div>
        </td>


        {{-- <td style="min-width: 100px;">
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


        <td style="min-width: 250px;">
            <input  type="text" name="remarks[]" value=""
                id="remark_{{ $key }}" class="form-control">
        </td>

        <td>
            <button type="button" class="btn btn-danger btn-sm removeRowBtn" {{ $isQuotationAvailable ? 'disabled' : '' }} onclick="remove({{ $key }})"
                data-id="{{ $key }}">Remove</button>
        </td>
    </tr>
@endforeach


<script>


function remove(id) {
    $("#row_" + id).remove();
}

function calculatePercentage(el) {
    const row = $(el).closest("tr");
    const gross_amount = row.find(".gross_amount");
    const qtyInput = row.find(".qty");
    const rateInput = row.find(".rate");
    
    const maxQty = parseFloat(qtyInput.attr("max")) || 0;
    let qty = parseFloat(qtyInput.val()) || 0;
    const rate = parseFloat(rateInput.val()) || 0;

    // Check max quantity
    if (qty > maxQty) {
        alert('Maximum allowed quantity is ' + maxQty);
        qty = maxQty;
        qtyInput.val(maxQty);
    }
    
    gross_amount.val((rate * qty).toFixed(2));
    
    const tax_percent = row.find(".taxes option:selected").data("percentage") || 0;
    const percent_amount = row.find(".percent_amount");
    const net_amount = row.find(".net_amount");

    const percent_amount_of_gross = (parseFloat(tax_percent) / 100) * parseFloat(gross_amount.val());
    const net_amount_value = parseFloat(gross_amount.val()) + parseFloat(percent_amount_of_gross);

    percent_amount.val(percent_amount_of_gross.toFixed(2));
    net_amount.val(net_amount_value.toFixed(2));
}
</script>

