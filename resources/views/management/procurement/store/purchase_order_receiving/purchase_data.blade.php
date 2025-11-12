@foreach ($dataItems ?? [] as $key => $data)

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
@endphp


   

    <tr id="row_{{ $key }}">
      

        <td style="width: 30%">
            <select style="width: 100px;" id="category_id_{{ $key }}" onchange="filter_items(this.value,{{ $key }})"
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

        <td style="width: 30%">
            <select style="width: 100px;" id="item_id_{{ $key }}" onchange="get_uom({{ $key }})"
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
            <input  type="text" name="uom[]" value="{{ get_uom($data->item_id) }}" id="uom_{{ $key }}"
                class="form-control uom" readonly>
        </td>

      
        <td style="width: 30%">
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
    >

    <div class="d-flex align-items-center">
        Total Qty: {{ $data->total_quoted_qty+$data->qty  }}
    </div>

    <div class="d-flex align-items-center">
        Received Qty: {{ $data->total_quoted_qty }}
    </div>
</td>
<td style="width: 30%">
                                <div class="loop-fields">
                                    <div class="form-group mb-0">
                                        <input type="number" style="width: 100px;" name="min_weight[]" id="min_weight_0" class="form-control"
                                            step="0.01" min="0" value="{{ $data->purchase_request_data->min_weight }}" placeholder="Min Weight">
                                    </div>
                                </div>
                            </td>
                            <td style="width: 30%">
                                <div class="loop-fields">
                                    <div class="form-group mb-0">
                                        <input type="text" name="color[]"  style="width: 100px;" value="{{ getColorById($data->purchase_request_data->color)?->color ?? null }}" id="color_0" class="form-control" step="0.01"
                                            min="0" placeholder="Color">
                                    </div>
                                </div>
                            </td>
                            

                            <td style="width: 30%">
                                <div class="loop-fields">
                                    <div class="form-group mb-0">
                                        <input type="text" style="width: 100px;" name="construction_per_square_inch[]"
                                            id="construction_per_square_inch_0" value="{{ $data->purchase_request_data->construction_per_square_inch }}" class="form-control" step="0.01" min="0"
                                            placeholder="Cons./sq. in.">
                                    </div>
                                </div>
                            </td>
                            <td style="width: 30%">
                                <div class="loop-fields">
                                    <div class="form-group mb-0">
                                        <input type="text" name="size[]" style="width: 100px;" id="size_0" value="{{ getSizeById($data->purchase_request_data->size)?->size ?? null }}" class="form-control" step="0.01"
                                            min="0" placeholder="Size">
                                    </div>
                                </div>
                            </td>
                            <td style="width: 30%">
                                <div class="loop-fields">
                                    <div class="form-group mb-0">
                                        <input type="text" name="stitching[]" style="width: 100px;" id="stitching_0" value="{{ $data->purchase_request_data->stitching }}" class="form-control"
                                            step="0.01" min="0" placeholder="Stitching">
                                    </div>
                                </div>
                            </td>

      {{-- <td style="width: 20%">
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
        readonly
        >
</td> --}}


        {{-- <td style="width: 20%">
            <input style="width: 100px" type="number" readonly name="total[]" value="{{ $data->total }}"
                id="total_{{ $key }}" class="form-control" step="0.01" min="0">
        </td> --}}


        <td style="width: 30%">
    <input style="width: 100px; resize: none;" name="remarks[]" 
        id="remark_{{ $key }}" class="form-control">
</td>


        <td>
            <button type="button" class="btn btn-danger btn-sm removeRowBtn" onclick="remove({{ $key }})"
                data-id="{{ $key }}">Remove</button>
        </td>
    </tr>
@endforeach

