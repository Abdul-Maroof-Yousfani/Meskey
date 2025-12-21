
@php
    $i = 0;
@endphp
@foreach ($job_orders as $job_order)
       
    @foreach ($job_order->packing_items as $packing_item)
        @php
            $i = $job_order->id;
        @endphp
        <tr id="row_pre_{{ $i }}" class="jo-{{ $job_order->id }}">
            <td>
                <select name="category_id[]" id="category_id_{{ $i }}"
                    onchange="filter_items(this.value,{{ $i }})" class="form-control item-select select2Dropdown"
                    data-index="{{ $i }}" style="width:120px;">
                    <option value="">Select Category</option>
                    @foreach ($categories ?? [] as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}
                            </option>
                    @endforeach
                </select>
            </td>

            <td>
                <select name="item_id[]" id="item_id_{{ $i }}" onchange="get_uom({{ $i }})"
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
                <input type="text" name="stitching[]" id="stitching_{{ $i }}" class="form-control"
                    placeholder="Stitching" style="width:120px;">
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
    

    });

    $(".job_orders").on("change", function() {
        console.log($(this).val());
    })




    function get_uom(index) {
        let uom = $('#item_id_' + index).find(':selected').data('uom');
        $('#uom_' + index).val(uom);
    }

     function filter_items(category_id, count) {
        $.ajax({
            url: '{{ route('get.items') }}',
            type: 'GET',
            data: {
                category_id: category_id
            },
            dataType: 'json',
            success: function (response) {
                if (response.success && response.products) {
                    $('#item_id_' + count).empty();
                    $('#item_id_' + count).append('<option value="">Select a Item</option>');

                    $.each(response.products, function (index, product) {
                        $('#item_id_' + count).append(
                            `<option data-uom="${product.unit_of_measure?.name ?? ''}" value="${product.id}">${product.name}</option>`
                        );
                    });


                    $('#item_id_' + count).select2();
                } else {
                    console.error('No products found or request failed');
                    $('#item_id_' + count).html('<option value="">No products available</option>');
                }
            },
            error: function (xhr, status, error) {
                console.error('AJAX Error:', status, error);
                $('#item_id_' + count).html('<option value="">Error loading products</option>');
            }
        });
    }

</script>
