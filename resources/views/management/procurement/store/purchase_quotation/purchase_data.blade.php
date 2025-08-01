@foreach ($dataItems ?? [] as $key => $data)
    <tr id="row_{{$key}}">
        <td style="width: 25%">
            <select name="category_id[]" id="category_id_{{$key}}" onchange="filter_items(this.value,{{$key}})" class="form-control item-select select2" data-index="{{$key}}">
                <option value="">Select Category</option>
                @foreach ($categories ?? [] as $category)
                    <option {{$category->id == $data->category_id ? 'selected' : ''}} value="{{$category->id}}">{{$category->name}}</option>
                @endforeach
            </select>
            <input type="hidden" name="data_id[]" value="{{$data->id}}">
        </td>
        <td style="width: 25%">
            <select name="item_id[]" id="item_id_{{$key}}" onchange="get_uom({{$key}})" class="form-control item-select select2" data-index="{{$key}}">
                @foreach (get_product_by_category($data->category_id) as $item)
                    <option 
                        data-uom="{{ $item->unitOfMeasure->name ?? '' }}" 
                        value="{{ $item->id }}"
                        {{ $item->id == $data->item_id ? 'selected' : '' }}
                    >
                        {{ $item->name }}
                    </option>
                @endforeach
            </select>
        </td>
        <td style="width: 15%"><input type="text" name="uom[]" value="{{get_uom($data->item_id)}}" id="uom_{{$key}}" class="form-control uom" readonly></td>
        <td style="width: 20%">
            <select name="supplier_id[]" id="supplier_id_{{$key}}" class="form-control item-select select2" data-index="{{$key}}">
                <option value="">Select Vendor</option>
                @foreach (get_supplier() as $supplier)
                    <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                @endforeach
            </select>
        </td>
        <td style="width: 10%"><input style="width: 100px" type="number" onkeyup="calc({{$key}})" onblur="calc({{$key}})"  name="qty[]" value="{{$data->qty}}" id="qty_{{$key}}" class="form-control" step="0.01" min="0"></td>
        <td style="width: 20%"><input style="width: 100px" type="number" onkeyup="calc({{$key}})" onblur="calc({{$key}})"  name="rate[]" value="" id="rate_{{$key}}" class="form-control" step="0.01" min="{{$key}}"></td>
        <td style="width: 20%"><input style="width: 100px" type="number" readonly name="total[]" value="" id="total_{{$key}}" class="form-control" step="0.01" min="0"></td>
        <td style="width: 25%"><input style="width: 100px" type="text" name="remarks[]" value="{{$data->remarks}}" id="remark_{{$key}}" class="form-control"></td>
        <td><button type="button" class="btn btn-danger btn-sm removeRowBtn" onclick="remove({{$key}})" data-id="{{$key}}">Remove</button></td>
    </tr>
@endforeach