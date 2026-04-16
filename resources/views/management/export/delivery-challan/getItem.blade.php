@foreach ($loading_programs as $index => $loading_program_item)
    @php
        $loading_slip = $loading_program_item->exportLoadingSlip;
        $delivery_order = $loading_slip?->deliveryOrder;
        $second_weighbridge = $loading_slip?->secondWeighbridge;

        if (!$delivery_order || !$second_weighbridge) {
            continue;
        }

        $delivery_order_data = $delivery_order->exportPackingItems->first();
        if (!$delivery_order_data) {
            continue;
        }

        $export_order_packing = $delivery_order->exportOrder?->packingItems
            ?->firstWhere('brand_id', $delivery_order_data->brand_id)
            ?? $delivery_order->exportOrder?->packingItems?->firstWhere('bag_size', $delivery_order_data->bag_size)
            ?? $delivery_order->exportOrder?->packingItems?->first();

        $item_id = $delivery_order->exportOrder?->product_id;
        $bag_type = $delivery_order_data->bag_type_id;
        $truck_no = $loading_program_item->truck_number;
        $brand_id = $delivery_order_data->brand_id ?? $loading_program_item->brand_id;
        $index = 'TICKET-' . $loading_program_item->id;

        $net_weight = $second_weighbridge->net_weight ?? 0;
        $packing_raw = $loading_program_item->packing ?: ($delivery_order_data->bag_size ?: 1);
        $packing = is_numeric($packing_raw) ? $packing_raw : (float) explode(',', (string) $packing_raw)[0];
        $packing = $packing ?: 1;
        $rate = $export_order_packing->rate ?? 0;
        $rate_per_maund = $export_order_packing->rate_per_maund ?? 0;
        $bags = $packing > 0 ? round(($net_weight * 1000) / $packing) : 0;
    @endphp
    <tr id="row_{{ $index }}">
        <td>
            <input type="text" value="{{ getItem($item_id)?->name }}" class="form-control bag_type" readonly>
            <input type="hidden" name="item_id[]" id="item_id_{{ $index }}" value="{{ $item_id }}" class="form-control item_id">
            <input type="hidden" name="ticket_id[]" id="ticket_id_{{ $index }}" value="{{ $loading_program_item->id }}" class="form-control ticket_id">
            <input type="hidden" name="do_data_id[]" id="do_data_id_{{ $index }}" value="{{ $delivery_order_data->id }}" class="form-control do_data_id">
        </td>
        <td>
            <input type="text" value="{{ bag_type_name($bag_type) }}" class="form-control bag_type" readonly>
            <input type="hidden" name="bag_type[]" id="bag_type_{{ $index }}" value="{{ $bag_type }}" class="form-control bag_type">
            <input type="hidden" name="so_data_id[]" id="so_data_id_{{ $index }}" value="{{ $delivery_order->export_order_id }}" class="form-control so_data_id">
        </td>
        <td>
            <input type="hidden" name="bag_size[]" id="bag_size_{{ $index }}" value="{{ $loading_program_item->packing }}" class="form-control bag_size">
            <select class="form-select select2 packing-select" multiple disabled>
                @php
                    $packings = explode(',', (string) $loading_program_item->packing);
                @endphp
                @foreach($packings as $p)
                    @if(trim($p))
                        <option value="{{ trim($p) }}" selected>{{ trim($p) }}</option>
                    @endif
                @endforeach
            </select>
        </td>
        <td>
            <input type="text" name="no_of_bags[]" id="no_of_bags_{{ $index }}" value="{{ $bags }}" class="form-control no_of_bags" readonly>
        </td>
        <td>
            <input type="text" name="qty[]" id="qty_{{ $index }}" value="{{ round($net_weight, 3) }}" class="form-control qty" step="0.01" min="0" oninput="calc(this)" readonly>
        </td>
        <td>
            <input type="text" name="rate[]" id="rate_{{ $index }}" value="{{ $rate }}" class="form-control rate" step="0.01" min="0" readonly>
        </td>
        <td style="display:none;">
            <input type="text" name="rate_per_mond[]" id="rate_per_mond_{{ $index }}" value="{{ $rate_per_maund }}" class="form-control rate" step="0.01" min="0" readonly>
        </td>
        <td>
            <input type="text" name="amount[]" id="amount_{{ $index }}" value="{{ $rate * $net_weight }}" class="form-control amount" readonly>
        </td>
        <td>
            <input type="text" value="{{ getBrandById($brand_id)?->name }}" class="form-control brand_id" readonly>
            <input type="hidden" name="brand_id[]" id="brand_id_{{ $index }}" value="{{ $brand_id }}" class="form-control item_id">
        </td>
        <td>
            <input type="text" name="truck_no[]" id="truck_no_{{ $index }}" value="{{ $truck_no }}" class="form-control truck_no" readonly>
        </td>
        <td>
            <input type="text" name="container_number[]" id="container_number_{{ $index }}" value="{{ $loading_program_item->container_number }}" class="form-control container_number" readonly>
        </td>
        <td>
            <input type="text" name="desc[]" id="desc_{{ $index }}" class="form-control">
        </td>
        <td>
            <button type="button" class="btn btn-danger btn-sm removeRowBtn"
                data-ticket-id="{{ $loading_program_item->id }}"
                data-ticket-text="{{ $loading_program_item->transaction_number }} -- {{ $loading_program_item->truck_number }}"
                onclick="removeTicketRow(this)" style="width:60px;">
                <i class="fa fa-trash"></i>
            </button>
        </td>
    </tr>
@endforeach

<script>
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

        const bagSizeVal = parseFloat(bag_size.val());
        const qtyVal = parseFloat(qty.val());

        if (!bagSizeVal || !qtyVal) {
            no_of_bags.val("");
            calcAmount(el);
            return;
        }

        const bagsResult = ((qtyVal * 1000) / bagSizeVal).toFixed();
        no_of_bags.val(bagsResult);
        calcAmount(el);
    }

    $(".select2").select2({ width: '100%' });
</script>
