@foreach ($sale_order->sales_order_data as $index => $data)
    @php
        $balance = delivery_order_balance($data->id);
        if(!$balance) continue;
    @endphp
    <tr id="row_{{ $index }}">
        <td>
            <input type="text" name="" id="" value="{{ getItem($data->item_id)?->name }}"
                onkeyup="calc(this)" class="form-control bag_type" step="0.01" min="0" readonly>
            <input type="hidden" name="item_id[]" id="item_id_{{ $index }}" value="{{ $data->item_id }}"
                onkeyup="calc(this)" class="form-control bag_type" step="0.01" min="0" readonly>
       
        </td>
        <td>
            
            <input type="text" name="" id="bag_type_{{ $index }}" value="{{ bag_type_name($data->bag_type) }}"
                onkeyup="calc(this)" class="form-control bag_type" step="0.01" min="0" readonly>

                  <input type="hidden" name="bag_type[]" id="bag_type_{{ $index }}" value="{{ $data->bag_type }}"
                onkeyup="calc(this)" class="form-control bag_type" step="0.01" min="0">

                <input type="hidden" name="so_data_id[]" id="so_data_id_{{ $index }}" value="{{ $data->id }}"
                onkeyup="calc(this)" class="form-control so_data_id" step="0.01" min="0">
        </td>
        <td>
            <input type="text" name="bag_size[]" id="bag_size_{{ $index }}" value="{{ $data->bag_size }}"
                onkeyup="calc(this)" class="form-control bag_size" step="0.01" min="0" readonly>
        </td>
        @php
            $used_quantity = $data->no_of_bags - $balance;
            $total_quantity = $data->no_of_bags;
        @endphp
        <td>
            <input type="text" style="margin-bottom: 10px;" name="no_of_bags[]" id="no_of_bags_{{ $index }}" data-balance="{{ $balance }}" value="{{ $total_quantity - $used_quantity }}" class="form-control no_of_bags" step="0.01" min="0" readonly>
            <span style="font-size: 14px;;">Used Quantity: {{ $used_quantity }}</span>
            <br />
            <span style="font-size: 14px;">Total Quantity: {{ $total_quantity }}</span>
        </td>
        <td>
            <input type="text" name="qty[]" id="qty_{{ $index }}" value="{{ round($data->bag_size / ($total_quantity - $used_quantity)) }}" class="form-control qty" step="0.01" min="0" onchange="calc(this)" oninput="calc(this)">
        </td>
        <td>
            <input type="text" name="rate[]" id="rate_{{ $index }}" value="{{ $data->rate }}" class="form-control rate" step="0.01" min="0" readonly>
        </td>
        <td>
            <input type="text" name="amount[]" id="amount_{{ $index }}" value="{{ $data->rate * ($data->qty ?? 0) }}"
                class="form-control amount" readonly>
        </td>
        <td>
            <select name="brand_id[]" id="brand_id_{{ $index }}" class="form-control select2">
                <option value="">Select Brand</option>
                @foreach (getAllBrands() ?? [] as $brand)
                    <option value="{{ $brand->id }}" @selected($brand->id == $data->brand_id)>{{ $brand->name }}</option>
                @endforeach
            </select>
        </td>
        <td>
            <input type="text" name="desc[]" id="desc_{{ $index }}"
                class="form-control" value="{{ $data->description }}" readonly>
        </td>
        <td style="display: none">
            <input type="text" name="pack_size[]" id="pack_size_{{ $index }}" value="{{ $data->pack_size }}"
                class="form-control pack_size" readonly>
        </td>
    </tr>
@endforeach

<script>
    $(".select2").select2();
</script>

<script>
    function is_able_to_submit(el) {
        const quantity = $(el).val();
        const threshold = $(el).data("balance");
        
        if(parseFloat(quantity) > parseFloat(threshold)) {
            Swal.fire({
                icon: 'warning',
                title: 'Limit Exceeded',
                text: 'Cannot proceed more than ' + threshold,
            });
            $(el).val(threshold);
        }
    }

    function calcAmount(el) {
        const element  = $(el).closest("tr");
        const qty = $(element).find(".qty");
        const rate = $(element).find(".rate");
        const amount = $(element).find(".amount");

        if(!qty.val() || !rate.val()) {
            amount.val("");
            return;
        }
        const result = parseFloat(qty.val()) * parseFloat(rate.val());
        amount.val(result);

    }

    function calc(el) {
        const element  = $(el).closest("tr");
        const bag_size = $(element).find(".bag_size");
        const no_of_bags = $(element).find(".no_of_bags");
        const qty = $(element).find(".qty");
        const balance = parseFloat(no_of_bags.data("balance")) || null;

        const bagSizeVal = parseFloat(bag_size.val());
        const qtyVal = parseFloat(qty.val());

        if(!(bagSizeVal && qtyVal)) {
            no_of_bags.val("");
            calcAmount(el);
            return;
        }

        let bagsResult = Math.round(qtyVal / bagSizeVal);

        if (balance && bagsResult > balance) {
            // Swal.fire({
            //     icon: 'warning',
            //     title: 'Limit Exceeded',
            //     text: 'No of bags cannot exceed available balance (' + balance + ').',
            // });
            // bagsResult = balance;
            // const limitedQty = parseFloat(bagsResult) / parseFloat(bag_size.val() || 1);
            // qty.val(limitedQty.toFixed(2));
        }
  
        no_of_bags.val(bagsResult);
        calcAmount(el);
    }

</script>
