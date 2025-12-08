@foreach ($delivery_orders as $delivery_order)
    @foreach ($delivery_order->delivery_order_data as $index => $data)
        @php
            $balance = delivery_challan_balance($data->id);
            if (!$balance) {
                continue;
            }
        @endphp
        @php
            // $data->bag_type = $balance;
        @endphp
        <tr id="row_{{ $index }}">
            <td>

                <input type="text" name="" id="item_id_read_only{{ $index }}"
                    value="{{ getItem($data->item_id)?->name }}" onkeyup="calc(this)" class="form-control bag_type"
                    step="0.01" min="0" readonly>

                <input type="hidden" name="item_id[]" id="item_id_{{ $index }}" value="{{ $data->item_id }}"
                    onkeyup="calc(this)" class="form-control item_id" step="0.01" min="0">

                <input type="hidden" name="do_data_id[]" id="do_data_id_{{ $index }}"
                    value="{{ $data->id }}" onkeyup="calc(this)" class="form-control do_data_id" step="0.01"
                    min="0">
            </td>
            <td>

                <input type="text" name="" id="bag_type_{{ $index }}"
                    value="{{ bag_type_name($data->bag_type) }}" onkeyup="calc(this)" class="form-control bag_type"
                    step="0.01" min="0" readonly>

                <input type="hidden" name="bag_type[]" id="bag_type_{{ $index }}" value="{{ $data->bag_type }}"
                    onkeyup="calc(this)" class="form-control bag_type" step="0.01" min="0">

                <input type="hidden" name="so_data_id[]" id="so_data_id_{{ $index }}"
                    value="{{ $data->id }}" onkeyup="calc(this)" class="form-control so_data_id" step="0.01"
                    min="0">
            </td>
            <td>
                <input type="text" name="bag_size[]" id="bag_size_{{ $index }}"
                    value="{{ $data->bag_size }}" onkeyup="calc(this)" class="form-control bag_size" step="0.01"
                    min="0">
            </td>
            <td>
                <input type="text" name="no_of_bags[]" id="no_of_bags_{{ $index }}"
                    onkeyup="is_able_to_submit(this); calc(this)" value="{{ $balance }}"
                    class="form-control no_of_bags" step="0.01" min="0">
            </td>
            <td>
                <input type="text" name="qty[]" id="qty_{{ $index }}"
                    value="{{ $data->bag_size * $balance }}" class="form-control qty" step="0.01" min="0" readonly>
            </td>
            <td>
                <input type="text" name="rate[]" id="rate_{{ $index }}" value="{{ $data->rate }}"
                    class="form-control rate" step="0.01" min="0">
            </td>
            <td>
                <input type="text" name="amount[]" id="amount_{{ $index }}"
                    value="{{ $data->rate * ($data->bag_size * $balance) }}" class="form-control amount" readonly>
            </td>
            <td>
                <input type="text" name="" id="brand_id_read_only{{ $index }}"
                    value="{{ getBrandById($data->brand_id)?->name }}" onkeyup="calc(this)"
                    class="form-control brand_id" step="0.01" min="0" readonly>

                <input type="hidden" name="brand_id[]" id="brand_id_{{ $index }}"
                    value="{{ $data->brand_id }}" onkeyup="calc(this)" class="form-control item_id" step="0.01"
                    min="0">
            </td>
            <td>
                <input type="text" name="truck_no[]" id="truck_no_{{ $index }}" value=""
                    class="form-control truck_no">
            </td>
            <td>
                <input type="text" name="bilty_no[]" id="bilty_no_{{ $index }}" value=""
                    class="form-control bilty_no">
            </td>
            <td>
                <input type="text" name="desc[]" id="desc_{{ $index }}" class="form-control">
            </td>

            <td>
                <button type="button" disabled class="btn btn-danger btn-sm removeRowBtn" style="width:60px;">
                    <i class="fa fa-trash"></i>
                </button>
            </td>
        </tr>
    @endforeach
@endforeach


<script>
    function is_able_to_submit(el) {
        // const quantity = $(el).val();
        // const threshold = {{ $balance }};

        // if (parseFloat(quantity) > parseFloat(threshold)) {
        //     Swal.fire({
        //         icon: 'warning',
        //         title: 'Limit Exceeded',
        //         text: 'Cannot proceed more than ' + threshold,
        //     });
        //     $(el).val(threshold);
        // }
    }

    function calcAmount(el) {
        const element = $(el).closest("tr");
        const qty = $(element).find(".qty");
        const rate = $(element).find(".rate");
        const amount = $(element).find(".amount");

        if (!qty.val() || !rate.val()) {
            amount.val("");
            return;
        }
        const result = parseFloat(qty.val()) * parseFloat(rate.val());
        amount.val(result);

    }

    function calc(el) {
        const element = $(el).closest("tr");
        const bag_size = $(element).find(".bag_size");
        const no_of_bags = $(element).find(".no_of_bags");
        const qty = $(element).find(".qty");

        if (!(bag_size.val() && no_of_bags.val())) return;

        const result = parseFloat(bag_size.val()) * parseFloat(no_of_bags.val());

        qty.val(result);
        calcAmount(el);
    }

    $(".select2").select2();
</script>
