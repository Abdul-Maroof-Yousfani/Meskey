
@php
    $i = 0;
@endphp
@foreach ($job_orders as $job_order)
    @foreach ($job_order->packing_items as $index => $packing_item)
        @php
            $i = $job_order->id . '-' . $index;
            $balance = jobOrderPackingBalanceAgainstPurchaseRequest($packing_item->id);
        @endphp

        @if ($balance > 0)
        <tr id="row_pre_{{ $i }}" class="jo-{{ $job_order->id }}">

            <td style="min-width: 250px;">
                <select id="item_id_{{ $i }}" onchange="get_uom('{{ $i }}')"
                    class="form-control item-select select2Dropdown" data-index="{{ $i }}" style="width: 100%;" disabled>
                    <option value="">Select Item</option>
                    @foreach($items as $item)
                        <option value="{{ $item->id }}" data-uom="{{ $item->unitOfMeasure->name }}" @selected($packing_item->bag_product_id == $item->id)>{{ $item->name }}</option>
                    @endforeach
                </select>

                <input type="hidden" name="module_type[]" value="packing" />
                <input type="hidden" name="packing_id[]" value="{{ $packing_item->id }}" />
                <input type="hidden" name="item_id[]" value="{{ $packing_item->bag_product_id }}" />
                <input type="hidden" name="index[]" value="{{ $i }}" />
                <input type="hidden" name="is_single_job_order[]" value="1" />
            </td>

            <td style="min-width: 100px;">
                <input type="text" name="uom[]" value="{{ get_uom($packing_item->bag_product_id) }}" id="uom_{{ $i }}" class="form-control" readonly>
            </td>

            <td style="min-width: 100px;">
                <input type="number" name="qty[]" id="qty_{{ $i }}" class="form-control" step="0.01"
                    min="0" placeholder="Qty" value="{{ $balance }}" readonly>
            </td>

            <td class="bag-only" style="min-width: 250px;">
                <select class="form-control select2Dropdown" multiple disabled>
                    <option selected value="{{ $job_order->id }}">{{ $job_order->job_order_no }}</option>
                </select>
                <input type="hidden" name="job_order_id[{{ $i }}][]" value="{{ $job_order->id }}" />
            </td>

            <td class="bag-only" style="min-width: 150px;">
                <select id="brands_{{ $i }}" class="form-control item-select select2Dropdown"
                    disabled>
                    <option value="">Select Brand</option>
                    @foreach (getAllBrands() ?? [] as $brand)
                        <option value="{{ $brand->id }}" @selected($packing_item->brand_id == $brand->id)>
                            {{ $brand->name }}
                        </option>
                    @endforeach
                </select>
                <input type="hidden" name="brands[]" value="{{ $packing_item->brand_id }}" />
            </td>

            <td class="bag-only" style="min-width: 120px;">
                <input type="number" name="min_weight[]" id="min_weight_{{ $i }}" class="form-control"
                    step="0.01" min="0" value="{{ $packing_item->min_weight_empty_bags }}"
                    placeholder="Min Weight" readonly>
            </td>

            <td class="bag-only" style="min-width: 150px;">
                <select id="colors_{{ $i }}" class="form-control item-select select2Dropdown"
                    disabled>
                    <option value="">Select Color</option>
                    @foreach (getAllColors() ?? [] as $color)
                        <option value="{{ $color->id }}" @selected($packing_item->bag_color_id == $color->id)>
                            {{ $color->color }}
                        </option>
                    @endforeach
                </select>
                <input type="hidden"  name="color[]"  value="{{ $packing_item->bag_color_id }}"/>
            </td>

            <td class="bag-only" style="min-width: 150px;">
                <input type="text" name="construction_per_square_inch[]"
                    id="construction_per_square_inch_{{ $i }}" class="form-control" step="0.01"
                    min="0" placeholder="Cons./sq. in.">
            </td>

            <td class="bag-only" style="width: 150px; min-width: 150px; max-width: 150px;">
                <select name="size[]" id="size_{{ $i }}"
                    class="form-control item-select size-select select2Dropdown" style="width: 100%;">
                    <option value="">Select Size</option>
                    @foreach (getAllSizes() ?? [] as $size)
                        <option value="{{ $size->id }}">{{ $size->size }}</option>
                    @endforeach
                </select>
            </td>

            <td class="bag-only" style="min-width: 200px;">
                {{-- <input type="text" name="stitching[]" id="stitching_{{ $i }}" class="form-control"
                    placeholder="Stitching"> --}}

               <select name="stitching[{{ $i }}][]" id="stitching_{{ $i }}"
                    class="form-control item-select stitching-select select2Dropdown" multiple>
                    <option value="">Select Stitching</option>
                    @foreach (getAllStitchings() ?? [] as $stitching)
                        <option value="{{ $stitching->id }}">{{ $stitching->name }}</option>
                    @endforeach
                </select>
            </td>

            <td class="bag-only" style="min-width: 120px;">
                <input type="text" name="micron[]" id="micron_{{ $i }}" class="form-control"
                    placeholder="Micron">
            </td>

            <td class="bag-only" style="min-width: 250px;">
                <input type="file" name="printing_sample[{{ $i }}][]" id="printing_sample_{{ $i }}"
                    class="form-control" accept="image/*,application/pdf" multiple>
            </td>

            <td style="min-width: 200px;">
                <input type="text" name="remarks[]" id="remark_{{ $i }}" class="form-control"
                    placeholder="line desc">
            </td>

            <td style="min-width: 80px;">
                <button type="button" onclick="removeRow('pre_{{ $i }}')" class="btn btn-danger btn-sm removeRowBtn"
                    data-id="{{ $i }}" style="width:120px;">
                    <i class="fa fa-trash"></i>
                </button>
            </td>
        </tr>
        @endif

        {{-- Sub Packing Item --}}
        @foreach($packing_item->subItems as $sub_packing_item)
        @php
            $i = $job_order->id . '-' . $sub_packing_item->id;
            $balance = jobOrderSubPackingBalanceAgainstPurchaseRequest($sub_packing_item->id);
            if($balance <= 0) {
                continue;
            }
        @endphp
        <tr id="row_pre_{{ $i }}" class="jo-{{ $job_order->id }}">

            <td style="min-width: 250px;">
                <select id="item_id_{{ $i }}" onchange="get_uom('{{ $i }}')"
                    class="form-control item-select select2Dropdown" data-index="{{ $i }}" style="width: 100%;" disabled>
                    <option value="">Select Item</option>
                    @foreach($items as $item)
                        <option value="{{ $item->id }}" data-uom="{{ $item->unitOfMeasure->name }}" @selected($sub_packing_item->bag_product_id == $item->id)>{{ $item->name }}</option>
                    @endforeach
                </select>

                <input type="hidden" name="module_type[]" value="subpacking" />
                <input type="hidden" name="packing_id[]" value="{{ $sub_packing_item->id }}" />
                <input type="hidden" name="item_id[]" value="{{ $sub_packing_item->bag_product_id }}" />
                <input type="hidden" name="index[]" value="{{ $i }}" />
                <input type="hidden" name="is_single_job_order[]" value="1" />
            </td>

            <td>
                <input type="text" name="uom[]" value="{{ get_uom($sub_packing_item->bag_product_id) }}" id="uom_{{ $i }}" class="form-control" readonly
                    style="width:120px;">
            </td>

            <td>
                <input type="number" name="qty[]" id="qty_{{ $i }}" class="form-control" step="0.01"
                    min="0" placeholder="Qty" style="width:120px;" value="{{ $balance }}" readonly>
            </td>

            <td class="bag-only">
                <select class="form-control select2Dropdown" multiple disabled>
                    <option selected value="{{ $job_order->id }}">{{ $job_order->job_order_no }}</option>
                </select>
                <input type="hidden" name="job_order_id[{{ $i }}][]" value="{{ $job_order->id }}" />
            </td>

            <td class="bag-only">
                <select id="brands_{{ $i }}" class="form-control item-select select2Dropdown"
                    style="width:120px;" disabled>
                    <option value="">Select Brand</option>
                    @foreach (getAllBrands() ?? [] as $brand)
                        <option value="{{ $brand->id }}" @selected($sub_packing_item->brand_id == $brand->id)>
                            {{ $brand->name }}
                        </option>
                    @endforeach
                </select>
                <input type="hidden" name="brands[]" value="{{ $sub_packing_item->brand_id }}" />
            </td>

            <td class="bag-only">
                <input type="number" name="min_weight[]" id="min_weight_{{ $i }}" class="form-control"
                    step="0.01" min="0" value="{{ $sub_packing_item->empty_bag_weight }}"
                    placeholder="Min Weight" style="width:120px;" readonly>
            </td>

            <td class="bag-only">
                <select id="colors_{{ $i }}" class="form-control item-select select2Dropdown"
                    style="width:120px;" disabled>
                    <option value="">Select Color</option>
                    @foreach (getAllColors() ?? [] as $color)
                        <option value="{{ $color->id }}" @selected($sub_packing_item->bag_color_id == $color->id)>
                            {{ $color->color }}
                        </option>
                    @endforeach
                </select>
                <input type="hidden"  name="color[]"  value="{{ $sub_packing_item->bag_color_id }}"/>
            </td>

            <td class="bag-only" style="min-width: 150px;">
                <input type="text" name="construction_per_square_inch[]"
                    id="construction_per_square_inch_{{ $i }}" class="form-control" step="0.01"
                    min="0" placeholder="Cons./sq. in.">
            </td>

            <td class="bag-only" style="width: 150px; min-width: 150px; max-width: 150px;">
                <select name="size[]" id="size_{{ $i }}"
                    class="form-control item-select size-select select2Dropdown" style="width: 100%;">
                    <option value="">Select Size</option>
                    @foreach (getAllSizes() ?? [] as $size)
                        <option value="{{ $size->id }}">{{ $size->size }}</option>
                    @endforeach
                </select>
            </td>

            <td class="bag-only">
                {{-- <input type="text" name="stitching[]" id="stitching_{{ $i }}" class="form-control"
                    placeholder="Stitching" style="width:120px;"> --}}

               <select name="stitching[{{ $i }}][]" id="stitching_{{ $i }}"
                    class="form-control item-select stitching-select select2Dropdown" style="width:200px;" multiple disabled>
                    <option value="">Select Stitching</option>
                    @foreach (getAllStitchings() ?? [] as $stitching)
                        <option value="{{ $stitching->id }}" @selected($stitching->id == $sub_packing_item->stitching_id)>{{ $stitching->name }}</option>
                    @endforeach
                </select>
                <input type="hidden" name="stitching[{{ $i }}][]" value="{{ $sub_packing_item->stitching_id }}" />
            </td>

            <td class="bag-only" style="min-width: 120px;">
                <input type="text" name="micron[]" id="micron_{{ $i }}" class="form-control"
                    placeholder="Micron">
            </td>

            <td class="bag-only" style="min-width: 250px;">
                <input type="file" name="printing_sample[{{ $i }}][]" id="printing_sample_{{ $i }}"
                    class="form-control" accept="image/*,application/pdf" multiple>
            </td>

            <td style="min-width: 200px;">
                <input type="text" name="remarks[]" id="remark_{{ $i }}" class="form-control"
                    placeholder="Remarks">
            </td>

            <td style="min-width: 80px;">
                <button type="button" onclick="removeRow('pre_{{ $i }}')" class="btn btn-danger btn-sm removeRowBtn"
                    data-id="{{ $i }}" style="width:120px;">
                    <i class="fa fa-trash"></i>
                </button>
            </td>
        </tr>
        @endforeach
    @endforeach
 
@endforeach





<script>
    $(document).ready(function() {
        $(".select2Dropdown").select2();
        $(".stitching-select").select2();
        $(".size-select").select2({
            tags: true,
            placeholder: "Select or add size",
            allowClear: true
        });
    });

    $(".job_orders").on("change", function() {
        console.log($(this).val());
    })





   

</script>
