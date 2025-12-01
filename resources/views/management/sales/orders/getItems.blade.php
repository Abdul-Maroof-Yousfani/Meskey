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
            <input type="number" name="qty[]" id="qty_{{ $i }}" onkeyup="calc(this)"
                value="{{ $data->qty }}" class="form-control qty" step="0.01" min="0" readonly>
        </td>
        <td>
            <input type="number" name="rate[]" id="rate_{{ $i }}" onkeyup="calc(this)"
                value="{{ $data->rate }}" class="form-control rate" step="0.01" min="0" readonly>
        </td>
        <td>
            <input type="text" name="amount[]" id="amount_{{ $i }}" value="{{ $data->qty * $data->rate }}"
                class="form-control amount" readonly>
        </td>
        <td>
            <button type="button" disabled class="btn btn-danger btn-sm removeRowBtn" style="width:60px;">
                <i class="fa fa-trash"></i>
            </button>
        </td>
    </tr>
@endforeach
