@foreach ($dataItems ?? [] as $key => $data)
    @php
   // $quotedData = $data?->approved_purchase_quotation;
//
//    if ($quotedData && $quotedData->supplier_id != ($quotation->supplier_id ?? null)) {
//        $quotedData = null;
//    }
//
//    $quotedRate = $quotedData->rate ?? '';
//    $quotedQty = $quotedData->qty ?? 0;
//    $quotedTotal = ($quotedRate !== '' && $quotedQty > 0) ? (float)$quotedRate * (float)$quotedQty : '';
//
//    $quotedSupplierId = $quotedData->supplier_id ?? '';
//    $quotedSupplierName = $quotedData->supplier->name ?? '';

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
        {{-- <td style="width: 5%">
            <input type="checkbox" name="use_quotation[]" id="use_quotation_{{ $key }}" value="{{ $key }}"
                class="quotation-checkbox" {{ $hasApprovedQuotation ? 'checked' : '' }}
                {{ !$hasApprovedQuotation ? 'disabled' : '' }} onchange="toggleQuotationFields({{ $key }})">

            <input type="hidden" id="quoted_supplier_id_{{ $key }}" value="{{ $quotedSupplierId }}">
            <input type="hidden" id="quoted_supplier_name_{{ $key }}" value="{{ $quotedSupplierName }}">
            <input type="hidden" id="quoted_qty_{{ $key }}" value="{{ $quotedQty }}">
            <input type="hidden" id="quoted_rate_{{ $key }}" value="{{ $quotedRate }}">
            <input type="hidden" id="quoted_total_{{ $key }}" value="{{ $quotedTotal }}">

            @if ($hasApprovedQuotation)
                <input type="hidden" name="quotation_ids[]" value="{{ $quotedData->id }}">
            @else
                <input type="hidden" name="quotation_ids[]" value="">
            @endif
        </td> --}}

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
            <input type="hidden" name="purchase_request_data_id[]" value="{{ $data->purchase_request_data_id }}">
            <input type="hidden" name="purchase_quotation_data_id[]" value="{{ $data->id ?? '' }}">
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

        {{-- <td style="width: 15%" id="vendor_dropdown_{{ $key }}">
            <select name="supplier_id_dropdown[]" id="supplier_id_{{ $key }}"
                class="form-control item-select select2" data-index="{{ $key }}"
                {{ $hasApprovedQuotation ? 'disabled' : '' }} onchange="updateVendorInput({{ $key }})">
                <option value="">Select Vendor</option>
                @foreach (get_supplier() as $supplier)
                    <option value="{{ $supplier->id }}" {{ $supplier->id == $currentSupplierId ? 'selected' : '' }}>
                        {{ $supplier->name }}
                    </option>
                @endforeach
            </select>

            <input type="hidden" name="supplier_id[]" id="supplier_id_hidden_{{ $key }}"
                value="{{ $currentSupplierId }}">
        </td> --}}

        {{-- <td style="width: 15%; display: none;" id="vendor_input_{{ $key }}">
            <input type="text" name="supplier_input[]" id="supplier_input_{{ $key }}"
                value="{{ $currentSupplierName }}" class="form-control" readonly>
        </td> --}}

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
        {{ $isQuotationAvailable ? 'readonly' : '' }}
    >

    <div class="d-flex align-items-center">
        Total Qty: {{ $data->qty }}
    </div>

    <div class="d-flex align-items-center">
        Ordered Qty: {{ $totalOrdered }}
    </div>
</td>

      <td style="width: 20%">
    <input 
        style="width: 100px" 
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


        <td style="width: 20%">
            <input style="width: 100px" type="number" readonly name="total[]" value="{{ $data->total ?? $currentTotal }}"
                id="total_{{ $key }}" class="form-control" step="0.01" min="0">
        </td>


        <td style="width: 25%">
            <input style="width: 100px" type="text" name="remarks[]" value="{{ $data->remarks }}"
                id="remark_{{ $key }}" class="form-control">
        </td>

        <td>
            <button type="button" class="btn btn-danger btn-sm removeRowBtn" {{ $isQuotationAvailable ? 'disabled' : '' }} onclick="remove({{ $key }})"
                data-id="{{ $key }}">Remove</button>
        </td>
    </tr>
@endforeach

