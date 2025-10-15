 @foreach ($dataItems ?? [] as $key => $data)
     <tr id="row_{{ $key }}">
         <td style="width: 25%">
             <div class="form-group mb-0">
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
             </div>
         </td>
         <td style="width: 25%">
             <select id="item_id_{{ $key }}" onchange="get_uom({{ $key }})" disabled
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
         <td style="width: 15%">
             <input type="text" id="uom_{{ $key }}" class="form-control uom"
                 value="{{ get_uom($data->item_id) }}" disabled readonly>
             <input type="hidden" name="uom[]" value="{{ get_uom($data->item_id) }}">
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
         <td style="width: 10%">
             <input style="width: 100px" type="number" onkeyup="calc({{ $key }})"
                 onblur="calc({{ $key }})" value="{{ $data->qty }}" id="qty_{{ $key }}"
                 class="form-control" step="0.01" min="0" max="{{ $data->qty }}">
             <input type="hidden" name="qty[]" value="{{ $data->qty }}">
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
             <input style="width: 100px" type="text" value="{{ $data->remarks }}" id="remark_{{ $key }}"
                 class="form-control">
             <input type="hidden" name="remarks[]" value="{{ $data->remarks }}">
         </td>
         <td>
             <button type="button" class="btn btn-danger btn-sm removeRowBtn" onclick="remove({{ $key }})"
                 data-id="{{ $key }}">Remove</button>
         </td>
     </tr>
 @endforeach
