@foreach ($loading_programs as $index => $loading_program_item)
        @php
            $delivery_order = $loading_program_item->loadingSlip?->deliveryOrder;
            
            if (!$delivery_order || !$delivery_order->delivery_order_data || $delivery_order->delivery_order_data->isEmpty()) {
                continue;
            }

            $loading_slip = $loading_program_item->loadingSlip;
            $second_weighbridge = $loading_slip?->secondWeighbridge;
            
            if (!$second_weighbridge) {
                continue;
            }
            
            $delivery_order_data = $delivery_order->delivery_order_data->first();
            $item_id = $delivery_order_data->item_id;
            $bag_type = $delivery_order_data->bag_type;
            $no_of_bags = $delivery_order_data->no_of_bags;
            
            $truck_no = $loading_program_item->truck_number;
            $brand_id = $loading_program_item->brand_id;
            $index = "TICKET-" . $loading_program_item->id;
            
            $net_weight = $second_weighbridge->net_weight ?? 0;
            $packing = $loading_program_item->packing ?: 1; // Prevent division by zero
        @endphp
        <tr id="row_{{ $index }}">
            <td>

                <input type="text" name="" id="item_id_read_only{{ $index }}"
                    value="{{ getItem($item_id)?->name }}" onkeyup="calc(this)" class="form-control bag_type"
                    step="0.01" min="0" readonly>

                <input type="hidden" name="item_id[]" id="item_id_{{ $index }}" value="{{ $item_id }}"
                    onkeyup="calc(this)" class="form-control item_id" step="0.01" min="0">


                <input type="hidden" name="ticket_id[]" id="ticket_id_{{ $index }}" value="{{ $loading_program_item->id }}"
                    onkeyup="calc(this)" class="form-control ticket_id" step="0.01" min="0">

                <input type="hidden" name="do_data_id[]" id="do_data_id_{{ $index }}"
                    value="{{ $delivery_order_data->id }}" onkeyup="calc(this)" class="form-control do_data_id" step="0.01"
                    min="0">
            </td>
            <td>

                <input type="text" name="" id="bag_type_{{ $index }}"
                    value="{{ bag_type_name($bag_type) }}" onkeyup="calc(this)" class="form-control bag_type"
                    step="0.01" min="0" readonly>

                <input type="hidden" name="bag_type[]" id="bag_type_{{ $index }}" value="{{ $bag_type }}"
                    onkeyup="calc(this)" class="form-control bag_type" step="0.01" min="0">

                <input type="hidden" name="so_data_id[]" id="so_data_id_{{ $index }}"
                    value="{{ $delivery_order_data->so_data_id }}" onkeyup="calc(this)" class="form-control so_data_id" step="0.01"
                    min="0">
            </td>
            <td>
                <input type="text" name="bag_size[]" id="bag_size_{{ $index }}"
                    value="{{ $loading_program_item->packing }}" class="form-control bag_size" step="0.01"
                    min="0" readonly>
            </td>
            <td>
                <input type="text" name="no_of_bags[]" id="no_of_bags_{{ $index }}"
                    value="{{ round($net_weight / $packing) }}"
                    class="form-control no_of_bags" step="0.01" min="0" readonly>
                
                    {{-- <span style="font-size: 14px;;">Used Quantity:
                        {{ delivery_challan_bags_used($data->id) }}</span>
                    <br />
                    <span style="font-size: 14px;">Balance:
                        {{ delivery_challan_balance($data->id) }}</span> --}}

            </td>
            <td>
                <input type="text" name="qty[]" id="qty_{{ $index }}"
                    value="{{ round($net_weight) }}"
                    {{-- onkeyup="check_balance(this, 'no_of_bags_{{ $index }}')" --}}
                    {{-- data-balance="{{ delivery_challan_balance($data->id) }}" --}}
                    class="form-control qty" step="0.01" min="0" oninput="calc(this)" readonly>
            </td>
            <td>
                <input type="text" name="rate[]" id="rate_{{ $index }}" value="{{ $delivery_order_data->rate ?? 0 }}"
                    class="form-control rate" step="0.01" min="0" readonly>
            </td>
            <td>
                <input type="text" name="rate[]" id="rate_{{ $index }}" value="{{ $delivery_order_data->salesOrderData->rate_per_mond ?? 0 }}"
                    class="form-control rate" step="0.01" min="0" readonly>
            </td>
            <td>
                <input type="text" name="amount[]" id="amount_{{ $index }}"
                    value="{{ ($delivery_order_data->rate ?? 0) * $net_weight }}" class="form-control amount" readonly>
            </td>
            <td>
                <input type="text" name="" id="brand_id_read_only{{ $index }}"
                    value="{{ getBrandById($brand_id)?->name }}" onkeyup="calc(this)"
                    class="form-control brand_id" step="0.01" min="0" readonly>

                <input type="hidden" name="brand_id[]" id="brand_id_{{ $index }}"
                    value="{{ $brand_id }}" onkeyup="calc(this)" class="form-control item_id" step="0.01"
                    min="0">
            </td>
            <td>
                <input type="text" name="truck_no[]" id="truck_no_{{ $index }}" value="{{ $truck_no }}"
                    class="form-control truck_no" readonly>
            </td>
            <td>
                <input type="text" name="bilty_no[]" id="bilty_no_{{ $index }}" value=""
                    class="form-control bilty_no">
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

    function check_balance(el, target) {
      const balance = $(el).data("balance");
      const value = $("#" + target).val();
      
      if(value > balance) {
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

    $(".select2").select2();
</script>
