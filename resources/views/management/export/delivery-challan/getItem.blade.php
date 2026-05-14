@php
    $existingRows = $existingRows ?? collect();
@endphp

@foreach ($loading_programs as $loading_program_item)
    @php
        $loading_slip = $loading_program_item->exportLoadingSlip;
        $second_wb = $loading_slip?->secondWeighbridge;
        
        $deliveryOrders = collect();
        $ticketDOs = $loading_program_item->deliveryOrders()->withoutGlobalScopes()->get();
        if ($ticketDOs->isNotEmpty()) {
            $deliveryOrders = $deliveryOrders->merge($ticketDOs);
        }
        if ($loading_program_item->exportLoadingProgram) {
            $lpDOs = $loading_program_item->exportLoadingProgram->deliveryOrders()->withoutGlobalScopes()->get();
            if ($lpDOs->isNotEmpty()) {
                $deliveryOrders = $deliveryOrders->merge($lpDOs);
            }
            if ($loading_program_item->exportLoadingProgram->deliveryOrder) {
                $deliveryOrders->push($loading_program_item->exportLoadingProgram->deliveryOrder);
            }
        }
        $deliveryOrders = $deliveryOrders->filter()->where('type', 'export_order')->unique('id')->values();

        if ($deliveryOrders->isEmpty() || !$second_wb) {
            continue;
        }

        $grand_total_mt = 0;
        $grand_total_items_count = 0;
        foreach ($deliveryOrders as $do) {
            $grand_total_mt += (float) $do->exportPackingItems->sum('metric_tons');
            $grand_total_items_count += $do->exportPackingItems->count();
        }

        $ticket_id = $loading_program_item->id;
        $sw_mt = round(((float) ($second_wb->net_weight ?? 0)) / 1000, 3);
        $truck_no = $loading_program_item->truck_number ?? 'N/A';
        $container_no = $loading_program_item->container_number ?? 'N/A';
    @endphp

    @foreach ($deliveryOrders as $delivery_order)
        @php
            $packing_items = $delivery_order->exportPackingItems;
            if ($packing_items->isEmpty()) {
                continue;
            }

            $item_id = $delivery_order->exportOrder?->product_id;
            $eo_packings = $delivery_order->exportOrder?->packingItems ?? collect();
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
            $proportion = ($grand_total_mt > 0 && (float) $packingItem->metric_tons > 0)
                ? ((float) $packingItem->metric_tons / $grand_total_mt)
                : (1 / max($grand_total_items_count, 1));
            $initial_qty = $existingRow
                ? round((float) $existingRow->qty, 3)
                : round($sw_mt * $proportion, 3);
            $initial_bags = $existingRow
                ? (int) $existingRow->no_of_bags
                : ($bag_size > 0 ? (int) round(($initial_qty * 1000) / $bag_size) : 0);

            // Fetch Labour Rate
            $category_id = $delivery_order->exportOrder?->product?->category_id;
            
            $factory_id = null;
            $factoryNamesStr = $loading_slip->factory ?? '';
            if ($factoryNamesStr) {
                $factoryNames = array_map('trim', explode(',', $factoryNamesStr));
                $firstFactoryName = $factoryNames[0] ?? '';
                if ($firstFactoryName) {
                    $factoryObj = \App\Models\Master\ArrivalLocation::where('name', $firstFactoryName)->first();
                    $factory_id = $factoryObj?->id;
                }
            }
            if (!$factory_id) {
                $factory_id = $loading_program_item->arrival_location_id;
            }

            $labour_rate_val = 0;
            
            if ($category_id && $factory_id && $bag_size > 0) {
                $clean_packing = (int) $bag_size;
                $bag_packing = \App\Models\BagPacking::where(function ($q) use ($clean_packing) {
                        $q->where('name', $clean_packing . ' kg')
                          ->orWhere('name', $clean_packing . 'KG')
                          ->orWhere('name', $clean_packing . ' KG')
                          ->orWhere('name', $clean_packing . ' kg')
                          ->orWhere('name', 'like', $clean_packing . '%');
                    })
                    ->where('status', 1)
                    ->first();

                if ($bag_packing) {
                    $labour_rate_obj = \App\Models\Master\LabourRate::where('category_id', $category_id)
                        ->where('factory_id', $factory_id)
                        ->where('bag_packing_id', $bag_packing->id)
                        ->where('status', 'active')
                        ->first();
                    $labour_rate_val = $labour_rate_obj ? (float) $labour_rate_obj->rate : 0;
                }
            }

            $labour_rate_val = ($existingRow && $existingRow->labour_rate > 0) 
                                ? (float) $existingRow->labour_rate 
                                : $labour_rate_val;
            $labour_amount_val = ($existingRow && $existingRow->labour_amount > 0)
                                ? (float) $existingRow->labour_amount
                                : ($labour_rate_val * $initial_bags);
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
                <input type="number" name="item_labour_rate[]" value="{{ $labour_rate_val }}" class="form-control item_labour_rate" readonly>
            </td>
            <td>
                <input type="number" name="item_labour_amount[]" value="{{ number_format($labour_amount_val, 2, '.', '') }}" class="form-control item_labour_amount" readonly>
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
@endforeach
