
@php
    $i = 0;
@endphp
@foreach ($job_orders as $job_order)
       
    @foreach ($job_order->packing_items as $packing_item)
        @php
            $i = $job_order->id . '-' . $packing_item->id;
        @endphp
        <tr id="row_pre_{{ $i }}" class="jo-{{ $job_order->id }}">
            <td>
                <select name="category_id[]" id="category_id_{{ $i }}"
                    onchange="filter_items(this.value,'{{ $i }}')" class="form-control item-select select2Dropdown jo-{{ $job_order->id }}"
                    data-index="{{ $i }}" style="width:120px;">
                    <option value="">Select Category</option>
                    @foreach ($categories ?? [] as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}
                            </option>
                    @endforeach
                </select>
            </td>

            <td>
                <select name="item_id[]" id="item_id_{{ $i }}" onchange="get_uom('{{ $i }}')"
                    class="form-control item-select select2Dropdown" data-index="{{ $i }}" style="width:120px;">
                    <option value="">Select Item</option>
                </select>
                <input type="hidden" name="index[]" value="{{ $i }}" />
                <input type="hidden" name="is_single_job_order[]" value="1" />
            </td>

            <td>
                <input type="text" name="uom[]" id="uom_{{ $i }}" class="form-control" readonly
                    style="width:120px;">
            </td>

            <td>
                <input type="number" name="qty[]" id="qty_{{ $i }}" class="form-control" step="0.01"
                    min="0" placeholder="Qty" style="width:120px;">
            </td>

            <td>
                <select class="form-control select2Dropdown" style="width: 250px;" multiple disabled>
                    <option selected value="{{ $job_order->id }}">{{ $job_order->job_order_no }}</option>
                </select>
                <input type="hidden" name="job_order_id[{{ $i }}][]" value="{{ $job_order->id }}" />
            </td>

            <td>
                <select name="brands[]" id="brands_{{ $i }}" class="form-control item-select select2Dropdown"
                    style="width:120px;">
                    <option value="">Select Brand</option>
                    @foreach (getAllBrands() ?? [] as $brand)
                        <option value="{{ $brand->id }}" @selected($packing_item->brand_id == $brand->id)>
                            {{ $brand->name }}
                        </option>
                    @endforeach
                </select>
            </td>

            <td>
                <input type="number" name="min_weight[]" id="min_weight_{{ $i }}" class="form-control"
                    step="0.01" min="0" value="{{ $packing_item->min_weight_empty_bags }}"
                    placeholder="Min Weight" style="width:120px;">
            </td>

            <td>
                <select name="color[]" id="colors_{{ $i }}" class="form-control item-select select2Dropdown"
                    style="width:120px;">
                    <option value="">Select Color</option>
                    @foreach (getAllColors() ?? [] as $color)
                        <option value="{{ $color->id }}" @selected($packing_item->bag_color_id == $color->id)>
                            {{ $color->color }}
                        </option>
                    @endforeach
                </select>
            </td>

            <td>
                <input type="text" name="construction_per_square_inch[]"
                    id="construction_per_square_inch_{{ $i }}" class="form-control" step="0.01"
                    min="0" placeholder="Cons./sq. in." style="width:120px;">
            </td>

            <td>
                <select name="size[]" id="size_{{ $i }}"
                    class="form-control item-select size-select select2Dropdown" style="width:120px;">
                    <option value="">Select Size</option>
                    @foreach (getAllSizes() ?? [] as $size)
                        <option value="{{ $size->id }}">{{ $size->size }}</option>
                    @endforeach
                </select>
            </td>

            <td>
                {{-- <input type="text" name="stitching[]" id="stitching_{{ $i }}" class="form-control"
                    placeholder="Stitching" style="width:120px;"> --}}

               <select name="stitching[{{ $i }}][]" id="stitching_{{ $i }}"
                    class="form-control item-select stitching-select select2Dropdown" style="width:200px;" multiple>
                    <option value="">Select Stitching</option>
                    @foreach (getAllStitchings() ?? [] as $stitching)
                        <option value="{{ $stitching->id }}">{{ $stitching->name }}</option>
                    @endforeach
                </select>
            </td>

            <td>
                <input type="text" name="micron[]" id="micron_{{ $i }}" class="form-control"
                    placeholder="Micron" style="width:120px;">
            </td>

            <td>
                <input type="file" name="printing_sample[]" id="printing_sample_{{ $i }}"
                    class="form-control" accept="image/*,application/pdf" style="width:120px;">
            </td>

            <td>
                <input type="text" name="remarks[]" id="remark_{{ $i }}" class="form-control"
                    placeholder="Remarks" style="width:120px;">
            </td>

            <td>
                <button type="button" onclick="removeRow('pre_{{ $i }}')" class="btn btn-danger btn-sm removeRowBtn"
                    data-id="{{ $i }}" style="width:120px;">
                    <i class="fa fa-trash"></i>
                </button>
            </td>
        </tr>
    @endforeach
 
@endforeach





<script>
    $(document).ready(function() {
        $(".select2Dropdown").select2();
        $(".stitching-select").select2();
    

    });

    $(".job_orders").on("change", function() {
        console.log($(this).val());
    })





   

</script>
