@php
    $i = 0;
@endphp
@foreach ($inquiry->sales_inquiry_data as $data)
    @php
        $i++;
    @endphp
    <tr id="row_{{ $i }}">
        <td>


            <input type="text" value="{{ getItem($data->item_id)?->name ?? '' }}" value="{{ $data->qty }}"
                class="form-control qty" min="0" readonly>


            <input type="hidden" name="item_id[]" id="item_id_{{ $i }}" value="{{ $data->item_id }}"
                class="form-control" min="0" readonly>
        </td>

        <td>


            <input type="text" value="{{ bag_type_name($data->bag_type) ?? '' }}" class="form-control qty"
                min="0" readonly>


            <input type="hidden" name="bag_type[]" id="bag_type_{{ $i }}" value="{{ $data->bag_type }}"
                class="form-control" min="0" readonly>
        </td>


        <td>


            <input type="text" value="{{ $data->bag_size }}" class="form-control qty" min="0" readonly>


            <input type="hidden" name="bag_size[]" id="bag_size_{{ $i }}" value="{{ $data->bag_size }}"
                class="form-control" min="0" readonly>


            <input type="hidden" name="sales_inquiry_id[]" id="sales_inquiry_id_0" value="{{ $data->id }}"
                class="form-control sales_inquiry_id" onkeyup="calc(this)" step="0.01" min="0">
        </td>

        <td>


            <input type="text" value="{{ $data->no_of_bags }}" class="form-control qty" min="0" readonly>


            <input type="hidden" name="no_of_bags[]" id="bag_size_{{ $i }}"
                value="{{ $data->no_of_bags }}" class="form-control" min="0" readonly>
        </td>

        <td>
            <input type="number" name="qty[]" id="qty_{{ $i }}" onkeyup="calc(this)"
                value="{{ $data->qty }}" class="form-control qty" step="0.01" min="0" readonly>
        </td>
        <td>
            <input type="number" name="rate[]" id="rate_{{ $i }}" onkeyup="calc(this)"
                value="{{ $data->rate }}" class="form-control rate" step="0.01" min="0" readonly>
        </td>

        <td>


            <input type="text" value="{{ getBrandById($data->brand_id)?->name }}" class="form-control rate"
                min="0" readonly>


            <input type="hidden" name="brand_id[]" id="rate_{{ $i }}" value="{{ $data->brand_id }}"
                class="form-control" min="0" readonly>
        </td>



        <td style="display: none;">
            <input type="text" name="pack_size[]" id="pack_size_{{ $i }}" value="{{ 0 }}"
                class="form-control amount" readonly>
        </td>

        <td>
            <input type="text" name="amount[]" id="pack_size_{{ $i }}"
                value="{{ $data->qty * $data->rate }}" class="form-control amount" readonly>
        </td>

         <td>
            <input type="text" name="description[]" id="pack_size_{{ $i }}"
                value="{{ $data->description }}" class="form-control amount" readonly>
        </td>

        <td>
            <button type="button" disabled class="btn btn-danger btn-sm removeRowBtn" style="width:60px;">
                <i class="fa fa-trash"></i>
            </button>
        </td>
    </tr>
@endforeach
