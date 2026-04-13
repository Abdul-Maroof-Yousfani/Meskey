
@foreach ($dataItems ?? [] as $key => $data)
    <tr id="row_{{ $key }}">
        <td style="min-width: 250px;">
            <div  class="form-group mb-0">
                <select id="category_id_{{ $key }}" disabled
                    onchange="filter_items(this.value,{{ $key }})" class="form-control item-select select2"
                    data-index="{{ $key }}">
                    <option value="">Select Category</option>
                    @foreach ($categories ?? [] as $category)
                        <option {{ $category->id == $data->category_id ? 'selected' : '' }} value="{{ $category->id }}">
                            {{ $category->name }}</option>
                    @endforeach
                </select>
                <input type="hidden" name="category_id[]" value="{{ $data->category_id }}">
                <input type="hidden" name="data_id[]" value="{{ $data->id }}">
               {{-- <input type="hidden" name="purchase_request_data_id[]" value="{{ $data->purchase_request_data_id  }}"> --}}

            </div>
        </td>
        <td style="min-width: 400px;">
            <select  id="item_id_{{ $key }}" onchange="get_uom({{ $key }})" disabled
                class="form-control item-select select2" data-index="{{ $key }}">
                @foreach (get_product_by_id($data->item_id) as $item)
                    <option data-uom="{{ $item->unitOfMeasure->name ?? '' }}" value="{{ $item->id }}"
                        {{ $item->id == $data->item_id ? 'selected' : '' }}>
                        {{ $item->name }}
                    </option>
                @endforeach
            </select>
            <input type="hidden" name="item_id[]" value="{{ $data->item_id }}">
        </td>

        <td style="min-width: 250px;">
            <select class="form-control select2" multiple disabled style="width: 100%">
                @foreach($data->JobOrder ?? [] as $jo)
                    <option selected>{{ $jo->job_order_data->job_order_no ?? '' }}</option>
                @endforeach
            </select>
        </td>



        <td style="min-width: 150px;">
            <input  type="number" onkeyup="calc({{ $key }})"
                onblur="calc({{ $key }})" name="qty[]" value="{{ $data->qty }}" id="qty_{{ $key }}"
                class="form-control" step="0.01" min="0" max="{{ $data->qty }}">
        </td>
        <td style="min-width: 150px;">
            <div class="loop-fields">
                <div class="form-group mb-0">
                    <input  type="number" onkeyup="calc({{ $key }})"
                        onblur="calc({{ $key }})" name="rate[]" value="{{ $data->rate }}"
                        id="rate_{{ $key }}" class="form-control" step="0.01" min="{{ $key }}">
                </div>
            </div>
        </td>
        <td style="min-width: 150px;">
            <div class="loop-fields">
                <div class="form-group mb-0">
                    <input  type="number" readonly value="" id="total_{{ $key }}"
                        class="form-control" step="0.01" min="0" name="total[]">
                </div>
            </div>
        </td>
        <td style="min-width: 150px;">
            <input  type="text" id="uom_{{ $key }}" class="form-control uom"
                value="{{ get_uom($data->item_id) }}" disabled readonly>
            <input type="hidden" name="uom[]" value="{{ get_uom($data->item_id) }}">
        </td>
        <td style="min-width: 180px;">
            <input type="date" name="delivery_date[]" id="delivery_date_{{ $key }}" 
                class="form-control" min="{{ date('Y-m-d') }}" value="" required>
        </td>
        <td style="min-width: 200px;" class="bag-only">
           
           <input  type="text" id="min_weight_{{ $key }}" class="form-control min_weight"
               value="{{ $data->min_weight }}" disabled readonly>
           
           <input type="hidden" name="min_weight[]" value="{{ $data->min_weight }}">
        </td>
        <td style="min-width: 150px;" class="bag-only">
           <input type="text" id="tolerance_{{ $key }}" class="form-control tolerance"
               value="{{ $data->tolerance }}" disabled readonly>
           <input type="hidden" name="tolerance[]" value="{{ $data->tolerance }}">
        </td>
        <td style="min-width: 150px;" class="bag-only">
           <input type="text" id="tolerance_percentage_{{ $key }}" class="form-control tolerance_percentage"
               value="{{ $data->tolerance_percentage }}" disabled readonly>
           <input type="hidden" name="tolerance_percentage[]" value="{{ $data->tolerance_percentage }}">
        </td>
          <td style="min-width: 200px;" class="bag-only">
           
           <input  type="text" id="brands_{{ $key }}" class="form-control brands"
               value="{{ getBrandById($data->brand_id)?->name ?? null }}" disabled readonly>
           
           <input type="hidden" name="brand[]" value="{{ $data->brand_id }}">
        </td>
        <td style="min-width: 200px;" class="bag-only">
           
           <input  type="text" id="color_{{ $key }}" class="form-control color"
               value="{{ getColorById($data->color)?->color ?? null }}" disabled readonly>
           
           <input type="hidden" name="color[]" value="{{ $data->color }}">
        </td>
        <td style="min-width: 200px;" class="bag-only">
           
           <input  type="text" id="construction_per_square_inch{{ $key }}" class="form-control construction_per_square_inch"
               value="{{ $data->construction_per_square_inch }}" disabled readonly>
           
           <input type="hidden" name="construction_per_square_inch[]" value="{{ $data->construction_per_square_inch }}">
        </td>
        <td style="min-width: 200px;" class="bag-only">
           <input  type="text" id="size{{ $key }}" class="form-control size-input-check"
               value="{{ $data->size }}" readonly>
           <input type="hidden" name="size[]" value="{{ $data->size }}">
        </td>
        <td style="min-width: 200px;" class="bag-only">
           
             <select class="form-control select2" multiple disabled>
               @foreach(getStitchingsByIds($data?->stitching ?? "") as $stitching)
                   <option value="{{ $stitching->id }}" selected>{{ $stitching->name }}</option>
               @endforeach
           </select>

           <input  type="hidden" id="stitching{{ $key }}" class="form-control size"
               value="{{ $data->stitching }}" disabled readonly>
           
           <input type="hidden" name="stitching[]" value="{{ $data->stitching }}">
        </td>
        <td style="min-width: 200px;" class="bag-only">
           
           <input  type="text" id="size{{ $key }}" class="form-control size"
               value="{{ $data->micron }}" disabled readonly>
           
           <input type="hidden" name="micron[]" value="{{ $data->micron }}">
        </td>
        <td style="min-width: 200px;" class="bag-only">
               <input type="file" disabled class="form-control" accept="image/*,application/pdf" multiple>
               @if (!empty($data->printing_sample))
                   @foreach((array)$data->printing_sample as $sample)
                       <small class="d-block">
                           <a href="{{ asset('storage/' . $sample) }}" target="_blank">
                               View file
                           </a>
                       </small>
                   @endforeach
               @endif
           </td>
       
        <td style="min-width: 400px;">
            <input  type="text" name="remarks[]" value="" id="remark_{{ $key }}"
                class="form-control">
        </td>
        <td>
            <button type="button" class="btn btn-danger btn-sm removeRowBtn" onclick="remove({{ $key }})"
                data-id="{{ $key }}">Remove</button>
        </td>
    </tr>
@endforeach
