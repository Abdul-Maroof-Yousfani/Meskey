@php
    $existingRows = $existingRows ?? collect();
@endphp

@foreach ($loading_programs as $loading_program_item)
    @php
        $loading_slip = $loading_program_item->exportLoadingSlip;
        $delivery_order = $loading_slip?->deliveryOrder;
        $second_wb = $loading_slip?->secondWeighbridge;

        if (!$delivery_order || !$second_wb) {
            continue;
        }

        $packing_items = $delivery_order->exportPackingItems;
        if ($packing_items->isEmpty()) {
            continue;
        }

        $ticket_id = $loading_program_item->id;
        $sw_mt = round(((float) ($second_wb->net_weight ?? 0)) / 1000, 3);
        $item_id = $delivery_order->exportOrder?->product_id;
        $truck_no = $loading_program_item->truck_number ?? 'N/A';
        $container_no = $loading_program_item->container_number ?? 'N/A';
        $eo_packings = $delivery_order->exportOrder?->packingItems ?? collect();
        $total_mt_do = (float) $packing_items->sum('metric_tons');
    @endphp

    @foreach ($packing_items as $packingItem)
        @php
            $existingRow = $existingRows->get($packingItem->id);
            $bag_type_id = $packingItem->bag_type_id;
            $brand_id = $packingItem->brand_id;
            $bag_size = round((float) ($packingItem->bag_size ?? 0), 3);
            $eo_packing = $eo_packings->firstWhere('brand_id', $brand_id)
                ?? $eo_packings->firstWhere('bag_size', $bag_size)
                ?? $eo_packings->first();
            $rate = round((float) ($eo_packing->rate ?? 0), 2);
            $row_key = 'T' . $ticket_id . 'P' . $packingItem->id;
            $proportion = ($total_mt_do > 0 && (float) $packingItem->metric_tons > 0)
                ? ((float) $packingItem->metric_tons / $total_mt_do)
                : (1 / max($packing_items->count(), 1));
            $initial_qty = $existingRow
                ? round((float) $existingRow->qty, 3)
                : round($sw_mt * $proportion, 3);
            $initial_bags = $existingRow
                ? (int) $existingRow->no_of_bags
                : ($bag_size > 0 ? (int) round(($initial_qty * 1000) / $bag_size) : 0);
        @endphp
        <tr class="dc-item-row" data-ticket-id="{{ $ticket_id }}">
            <td>
                <input type="text" class="form-control" value="{{ getItem($item_id)?->name ?? 'N/A' }}" readonly>
                <input type="hidden" name="item_id[]" value="{{ $item_id }}">
                <input type="hidden" name="ticket_id[]" value="{{ $ticket_id }}">
                <input type="hidden" name="do_data_id[]" value="{{ $packingItem->id }}">
            </td>
            <td>
                <input type="text" class="form-control" value="{{ $packingItem->bagType?->name ?? bag_type_name($bag_type_id) ?? '-' }}" readonly>
                <input type="hidden" name="bag_type[]" value="{{ $bag_type_id }}">
            </td>
            <td>
                <input type="text" class="form-control" value="{{ $bag_size > 0 ? rtrim(rtrim(number_format($bag_size, 3, '.', ''), '0'), '.') . ' KG' : '-' }}" readonly>
                <input type="hidden" name="bag_size[]" value="{{ $bag_size }}" class="bag_size">
            </td>
            <td>
                <input type="number" name="no_of_bags[]" value="{{ $initial_bags }}" class="form-control no_of_bags" min="0" step="1" oninput="syncQtyFromBags(this)">
            </td>
            <td>
                <input type="number" name="qty[]" value="{{ $initial_qty }}" class="form-control qty" min="0" step="0.001" oninput="syncBagsFromQty(this)">
            </td>
            <td>
                <input type="number" name="rate[]" value="{{ $rate }}" class="form-control rate" readonly>
            </td>
            <td style="display: none;">
                <input type="text" value="{{ $eo_packing->rate_per_maund ?? '' }}" class="form-control" readonly>
            </td>
            <td>
                <input type="text" name="amount[]" value="{{ number_format($initial_qty * $rate, 2, '.', '') }}" class="form-control amount" readonly>
            </td>
            <td>
                <input type="text" class="form-control" value="{{ $packingItem->brand?->name ?? getBrandById($brand_id)?->name ?? '-' }}" readonly>
                <input type="hidden" name="brand_id[]" value="{{ $brand_id }}">
            </td>
            <td>
                <input type="text" name="truck_no[]" value="{{ $existingRow->truck_no ?? $truck_no }}" class="form-control" readonly>
            </td>
            <td>
                <input type="text" name="container_number[]" value="{{ $existingRow->container_number ?? $container_no }}" class="form-control" readonly>
            </td>
            <td>
                <input type="text" name="desc[]" value="{{ $existingRow->description ?? '' }}" class="form-control">
            </td>
        </tr>
    @endforeach
@endforeach
