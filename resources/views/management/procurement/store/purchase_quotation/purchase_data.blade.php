 
 @foreach ($dataItems ?? [] as $key => $data)
     <tr id="row_{{ $key }}">
         <td style="width: 30%">
             <div style="width: 100px" class="form-group mb-0">
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
         <td style="width: 30%">
             <select style="width: 100px" id="item_id_{{ $key }}" onchange="get_uom({{ $key }})" disabled
                 class="form-control item-select select2" data-index="{{ $key }}">
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
             <input style="width: 100px" type="text" id="uom_{{ $key }}" class="form-control uom"
                 value="{{ get_uom($data->item_id) }}" disabled readonly>
             <input type="hidden" name="uom[]" value="{{ get_uom($data->item_id) }}">
         </td>
         <td style="width: 30%">
            
            <input style="width: 100px" type="text" id="min_weight_{{ $key }}" class="form-control min_weight"
                value="{{ $data->min_weight }}" disabled readonly>
            
            <input type="hidden" name="min_weight[]" value="{{ $data->min_weight }}">
         </td>
           <td style="width: 30%">
            
            <input style="width: 100px" type="text" id="brands_{{ $key }}" class="form-control brands"
                value="{{ getBrandById($data->brand_id)?->name ?? null }}" disabled readonly>
            
            <input type="hidden" name="brand[]" value="{{ $data->brand_id }}">
         </td>
         <td style="width: 30%">
            
            <input style="width: 100px" type="text" id="color_{{ $key }}" class="form-control color"
                value="{{ getColorById($data->color)?->color ?? null }}" disabled readonly>
            
            <input type="hidden" name="color[]" value="{{ $data->color }}">
         </td>
         <td style="width: 30%">
            
            <input style="width: 100px" type="text" id="construction_per_square_inch{{ $key }}" class="form-control construction_per_square_inch"
                value="{{ $data->construction_per_square_inch }}" disabled readonly>
            
            <input type="hidden" name="construction_per_square_inch[]" value="{{ $data->construction_per_square_inch }}">
         </td>
         <td style="width: 30%">
            
            <input style="width: 100px" type="text" id="size{{ $key }}" class="form-control size"
                value="{{ getSizeById($data->size)?->size ?? null }}" disabled readonly>
            
            <input type="hidden" name="size[]" value="{{ $data->size }}">
         </td>
         <td style="width: 30%">
            
            <input style="width: 100px" type="text" id="stitching{{ $key }}" class="form-control size"
                value="{{ $data->stitching }}" disabled readonly>
            
            <input type="hidden" name="stitching[]" value="{{ $data->stitching }}">
         </td>
         <td style="width: 30%">
            
            <input style="width: 100px" type="text" id="micron{{ $key }}" class="form-control size"
                value="{{ $data->micron }}" disabled readonly>
            
            <input type="hidden" name="micron[]" value="{{ $data->micron }}">
         </td>
         <td style="width:150px;">
                <input type="file" name="printing_sample[]" id="printing_sample_{{ $key }}" disabled class="form-control" accept="image/*,application/pdf">
                @if (!empty($data->printing_sample))
                    <small>
                        <a href="{{ asset('storage/' . $data->printing_sample) }}" target="_blank">
                            View existing file
                        </a>
                    </small>
                @endif
            </td>
        
         {{-- <td style="width: 20%">
             <div class="loop-fields">
                 <div class="form-group mb-0">
                     <select id="supplier_id_{{ $key }}" name="supplier_id[]"
                class="form-control item-select select2" data-index="{{ $key }}">
                <option value="">Select Vendor</option>
                @foreach (get_supplier() as $supplier)
                    <option value="{{ $supplier->id }}"
                        {{ $supplier->id == $data->supplier_id ? 'selected' : '' }}>
                        {{ $supplier->name }}
                    </option>
                @endforeach
            </select>
                 </div>
             </div>
         </td> --}}
         <td style="width: 30%">
             <input style="width: 100px" type="number" onkeyup="calc({{ $key }})"
                 onblur="calc({{ $key }})" name="qty[]" value="{{ $data->qty }}" id="qty_{{ $key }}"
                 class="form-control" step="0.01" min="0" max="{{ $data->qty }}">
             {{-- <input type="hidden"  value="{{ $data->qty }}"> --}}
         </td>
         <td style="width: 20%">
             <div class="loop-fields">
                 <div class="form-group mb-0">
                     <input style="width: 100px" type="number" onkeyup="calc({{ $key }})"
                         onblur="calc({{ $key }})" name="rate[]" value="{{ $data->rate }}"
                         id="rate_{{ $key }}" class="form-control" step="0.01" min="{{ $key }}">
                 </div>
             </div>
         </td>
         <td style="width: 20%">
             <div class="loop-fields">
                 <div class="form-group mb-0">
                     <input style="width: 100px" type="number" readonly value="" id="total_{{ $key }}"
                         class="form-control" step="0.01" min="0" name="total[]">
                 </div>
             </div>
         </td>
         <td style="width: 25%">
             <input style="width: 100px" type="text" name="remarks[]" value="" id="remark_{{ $key }}"
                 class="form-control">
             {{-- <input type="hidden" name="remarks[]" value=""> --}}
         </td>
         <td>
             <button type="button" class="btn btn-danger btn-sm removeRowBtn" onclick="remove({{ $key }})"
                 data-id="{{ $key }}">Remove</button>
         </td>
     </tr>
 @endforeach
