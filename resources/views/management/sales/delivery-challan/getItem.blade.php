@foreach ($loading_programs as $base_index => $loading_program_item)
    @php
        $loading_slip = $loading_program_item->loadingSlip;
        $second_weighbridge = $loading_slip?->secondWeighbridge;

        if (!$second_weighbridge) {
            continue;
        }

        $swb_items = $second_weighbridge->items;
        if ($swb_items->isEmpty()) {
            // Fallback for older records
            if ($second_weighbridge->delivery_order_id) {
                $swb_items = collect([(object)[
                    'deliveryOrder' => $loading_program_item->loadingSlip?->deliveryOrder,
                    'net_weight' => $second_weighbridge->net_weight
                ]]);
            } else {
                continue;
            }
        }

        $truck_no = $loading_program_item->truck_number;
        $brand_id = $loading_program_item->brand_id;
        
        $packing_raw = $loading_program_item->packing ?: 1;
        $packing = is_numeric($packing_raw) ? $packing_raw : (float) explode(',', (string) $packing_raw)[0];
        $packing = $packing ?: 1; // Prevent division by zero
    @endphp

    @foreach($swb_items as $do_loop_index => $swb_item)
        @php
            $delivery_order = $swb_item->deliveryOrder;
            if (!$delivery_order) continue;
            
            $delivery_order_data = $delivery_order->delivery_order_data->first();
            if (!$delivery_order_data) continue;
            
            $item_id = $delivery_order_data->item_id;
            $bag_type = $delivery_order_data->bag_type;
            
            $assigned_qty = round($swb_item->net_weight);
            if ($assigned_qty <= 0) continue;
            
            // Calculate bags for this portion
            $assigned_bags = ($packing > 0) ? round($assigned_qty / $packing) : 0;
            
            $index = "TICKET-" . $loading_program_item->id . "-" . $delivery_order->id;
        @endphp
        <tr id="row_{{ $index }}">
            <td>
                <input type="text" class="form-control" value="{{ $delivery_order->reference_no }}" readonly>
            </td>
            <td>

                <input type="text" name="" id="item_id_read_only{{ $index }}" value="{{ getItem($item_id)?->name }}"
                    onkeyup="calc(this)" class="form-control bag_type" step="0.01" min="0" readonly>

                <input type="hidden" name="item_id[]" id="item_id_{{ $index }}" value="{{ $item_id }}" onkeyup="calc(this)"
                    class="form-control item_id" step="0.01" min="0">


                <input type="hidden" name="ticket_id[]" id="ticket_id_{{ $index }}" value="{{ $loading_program_item->id }}"
                    onkeyup="calc(this)" class="form-control ticket_id" step="0.01" min="0">

                <input type="hidden" name="do_data_id[]" id="do_data_id_{{ $index }}" value="{{ $delivery_order_data->id }}"
                    onkeyup="calc(this)" class="form-control do_data_id" step="0.01" min="0">
            </td>
            <td>

                <input type="text" name="" id="bag_type_{{ $index }}" value="{{ bag_type_name($bag_type) }}"
                    onkeyup="calc(this)" class="form-control bag_type" step="0.01" min="0" readonly>

                <input type="hidden" name="bag_type[]" id="bag_type_{{ $index }}" value="{{ $bag_type }}" onkeyup="calc(this)"
                    class="form-control bag_type" step="0.01" min="0">

                <input type="hidden" name="so_data_id[]" id="so_data_id_{{ $index }}"
                    value="{{ $delivery_order_data->so_data_id }}" onkeyup="calc(this)" class="form-control so_data_id"
                    step="0.01" min="0">
            </td>
            <td>
                <input type="hidden" name="bag_size[]" id="bag_size_{{ $index }}" value="{{ $loading_program_item->packing }}"
                    class="form-control bag_size">
                <select class="form-select select2 packing-select" multiple disabled>
                    @php
                        $packings = explode(',', $loading_program_item->packing);
                    @endphp
                    @foreach($packings as $p)
                        @if(trim($p))
                            <option value="{{ trim($p) }}" selected>{{ trim($p) }}</option>
                        @endif
                    @endforeach
                </select>
            </td>
            <td>
                <input type="text" name="no_of_bags[]" id="no_of_bags_{{ $index }}" value="{{ $assigned_bags }}"
                    class="form-control no_of_bags" step="0.01" min="0" readonly>
            </td>
            <td>
                <input type="text" name="qty[]" id="qty_{{ $index }}" value="{{ $assigned_qty }}" 
                    class="form-control qty" step="0.01" min="0"
                    oninput="calc(this)" readonly>
            </td>
            <td class="d-none">
                <input type="text" name="rate[]" id="rate_{{ $index }}" value="{{ $delivery_order_data->rate ?? 0 }}"
                    class="form-control rate" step="0.01" min="0" readonly>
            </td>
            <td class="d-none">
                <input type="text" name="rate[]" id="rate_{{ $index }}"
                    value="{{ $delivery_order_data->salesOrderData->rate_per_mond ?? 0 }}" class="form-control rate" step="0.01"
                    min="0" readonly>
            </td>
            <td class="d-none">
                <input type="text" name="amount[]" id="amount_{{ $index }}"
                    value="{{ ($delivery_order_data->rate ?? 0) * $assigned_qty }}" class="form-control amount" readonly>
            </td>
            <td>
                <input type="text" name="" id="brand_id_read_only{{ $index }}" value="{{ getBrandById($brand_id)?->name }}"
                    onkeyup="calc(this)" class="form-control brand_id" step="0.01" min="0" readonly>

                <input type="hidden" name="brand_id[]" id="brand_id_{{ $index }}" value="{{ $brand_id }}" onkeyup="calc(this)"
                    class="form-control item_id" step="0.01" min="0">
            </td>
            <td>
                <input type="text" name="truck_no[]" id="truck_no_{{ $index }}" value="{{ $truck_no }}"
                    class="form-control truck_no" readonly>
            </td>
            <td>
                <input type="text" name="container_number[]" id="container_number_{{ $index }}"
                    value="{{ $loading_program_item->container_number }}" class="form-control container_number" readonly>
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

    function check_balance(el, target) {
        const balance = $(el).data("balance");
        const value = $("#" + target).val();

        if (value > balance) {
            Swal.fire({
                icon: 'warning',
                title: 'Limit Exceeded',
                text: 'Cannot proceed more than ' + balance,
            });

            $("#" + target).addClass("is-invalid");
        } else {
            $("#" + target).removeClass("is-invalid");
        }
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

        const bagsResult = (qtyVal / bagSizeVal).toFixed();

        no_of_bags.val(bagsResult);
        calcAmount(el);
    }

    $(".select2").select2({ width: '100%' });
</script>