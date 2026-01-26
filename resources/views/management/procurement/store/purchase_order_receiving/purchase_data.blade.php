@foreach ($dataItems ?? [] as $key => $data)
            
@if (isset($data->purchase_order_data))
    @php
        $totalOrdered = $data->purchase_order_data->sum('qty') - $data->purchase_order_data->qc->rejected_quantity;
    @endphp
@else
    @php
        $totalOrdered = 0;
    @endphp
@endif

@php
    $remainingQty = getPODataQty($data->id) - getStockByGrnDataId($data->id);
@endphp

@if($remainingQty <= 0) @continue @endif


   

   <tr id="row_{{ $key }}">

    <td style="width: 220px; min-width: 220px;">
        <select style="width: 100%;" id="category_id_{{ $key }}" onchange="filter_items(this.value,{{ $key }})"
            class="form-control item-select select2" data-index="{{ $key }}" disabled>
            <option value="">Select Category</option>
            @foreach ($categories ?? [] as $category)
                <option {{ $category->id == $data->category_id ? 'selected' : '' }} value="{{ $category->id }}">
                    {{ $category->name }}</option>
            @endforeach
        </select>
        <input type="hidden" name="category_id[]" value="{{ $data->category_id }}">
        <input type="hidden" name="purchase_order_data_id[]" value="{{ $data->id }}">
    </td>

    <td style="width: 280px; min-width: 280px;">
        <select style="width: 100%;" id="item_id_{{ $key }}" onchange="get_uom({{ $key }})"
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

    <td style="width: 140px; min-width: 140px;">
        <input type="text" name="uom[]" value="{{ get_uom($data->item_id) }}" id="uom_{{ $key }}"
            class="form-control uom" readonly style="width: 100%;">
    </td>

    <td style="width: 180px; min-width: 180px;">
        <input type="number" style="width: 100%;" 
            onkeyup="calc({{ $key }})" onblur="calc({{ $key }})"
            name="qty[]" value="{{ $remainingQty }}" id="qty_{{ $key }}"
            class="form-control" step="0.01" min="0" max="{{ $remainingQty }}">

        <div class="d-flex align-items-center small mt-1">
            Balance: {{ $remainingQty }}
        </div>
        <div class="d-flex align-items-center small">
            Used Qty: {{ getStockByGrnDataId($data->id) }}
        </div>
    </td>

    <td style="width: 160px; min-width: 160px;">
        <div class="loop-fields">
            <div class="form-group mb-0">
                <input type="number" style="width: 100%;" name="receive_weight[]" id="receive_weight_{{ $key }}"
                    class="form-control" step="0.01" min="0" value="" placeholder="Receive Weight">
            </div>
        </div>
    </td>

    <td style="width: 140px; min-width: 140px;">
        <div class="loop-fields">
            <div class="form-group mb-0">
                <input type="number" style="width: 100%;" name="min_weight[]" id="min_weight_{{ $key }}"
                    class="form-control" step="0.01" min="0" value="{{ $data->min_weight }}" placeholder="Min Weight" readonly>
            </div>
        </div>
    </td>

    <td style="width: 180px; min-width: 180px;">
        <div class="loop-fields">
            <div class="form-group mb-0">
                <input type="text" style="width: 100%;" name="brand[]" id="brand_{{ $key }}"
                    value="{{ $data->brand }}" class="form-control" placeholder="Brand" readonly>
            </div>
        </div>
    </td>

    <td style="width: 160px; min-width: 160px;">
        <div class="loop-fields">
            <div class="form-group mb-0">
                <input type="text" style="width: 100%;" name="color[]" id="color_{{ $key }}"
                    value="{{ $data->color }}" class="form-control" placeholder="Color" readonly>
            </div>
        </div>
    </td>

    <td style="width: 170px; min-width: 170px;">
        <div class="loop-fields">
            <div class="form-group mb-0">
                <input type="text" style="width: 100%;" name="construction_per_square_inch[]"
                    id="construction_per_square_inch_{{ $key }}" value="{{ $data->construction_per_square_inch }}"
                    class="form-control" placeholder="Cons./sq. in." readonly>
            </div>
        </div>
    </td>

    <td style="width: 140px; min-width: 140px;">
        <div class="loop-fields">
            <div class="form-group mb-0">
                <input type="text" style="width: 100%;" name="size[]" id="size_{{ $key }}"
                    value="{{ $data->size }}" class="form-control" placeholder="Size" readonly>
            </div>
        </div>
    </td>

    <td style="width: 220px; min-width: 220px;">
        <div class="loop-fields">
            <div class="form-group mb-0">
                <select class="form-control select2" multiple disabled>
                    @foreach(getStitchingsByIds($data?->stitching ?? "") as $stitching)
                        <option value="{{ $stitching->id }}" selected>{{ $stitching->name }}</option>
                    @endforeach
                </select>
                <input type="hidden" name="stitching[]" id="stitching_{{ $key }}"
                    value="{{ $data->stitching }}" class="form-control" readonly>
            </div>
        </div>
    </td>

    <td style="width: 140px; min-width: 140px;">
        <div class="loop-fields">
            <div class="form-group mb-0">
                <input type="text" style="width: 100%;" name="micron[]" id="micron_{{ $key }}"
                    value="{{ $data->micron }}" class="form-control" placeholder="Micron" readonly>
            </div>
        </div>
    </td>

    <td style="width: 220px; min-width: 220px;">
        <input type="file" name="printing_sample[]" id="printing_sample_{{ $key }}" disabled
            class="form-control" accept="image/*,application/pdf">
        @if (!empty($data->printing_sample))
            <small class="d-block mt-1">
                <a href="{{ asset('storage/' . $data->printing_sample) }}" target="_blank">
                    View existing file
                </a>
            </small>
        @endif
    </td>

    <td style="width: 260px; min-width: 260px;">
        <input style="width: 100%; resize: vertical; min-height: 38px;" name="remarks[]"
            id="remark_{{ $key }}" class="form-control" placeholder="Remarks">
    </td>

    <td style="width: 110px; min-width: 110px; text-align: center; vertical-align: middle;">
        <button type="button" class="btn btn-danger btn-sm removeRowBtn" onclick="remove({{ $key }})"
            data-id="{{ $key }}">Remove</button>
    </td>

</tr>
@endforeach

