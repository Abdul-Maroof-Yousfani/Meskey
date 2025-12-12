<style>
    html,
    body {
        overflow-x: hidden;
    }

    .amount-info-box {
        border: 1px solid #dee2e6;
        border-radius: 8px;
        padding: 15px;
        background-color: #f8f9fa;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .amount-info-box .form-group {
        margin-bottom: 10px;
    }

    .amount-info-box .form-group:last-child {
        margin-bottom: 0;
    }

    .amount-info-box .form-label {
        font-weight: 600;
        font-size: 13px;
    }
</style>

<form action="{{ route('sales.delivery-order.update', ['delivery_order' => $delivery_order->id]) }}" method="POST"
    id="ajaxSubmit" autocomplete="off">
    @csrf
    {{ method_field('PUT') }}
    <input type="hidden" id="listRefresh" value="{{ route('sales.get.delivery-order.list') }}" />

    <div class="row form-mar">
        <!-- Left side fields (2 columns) -->
        <div class="col-md-12">
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label">Do No:</label>
                        <input type="text" name="reference_no" id="reference_no" value="{{ $delivery_order->reference_no }}" class="form-control" readonly>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label">Do Date:</label>
                        <input type="date" name="dispatch_date" onchange="getNumber()" id="dispatch_date"
                            value="{{ $delivery_order->dispatch_date }}" class="form-control">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label">Delivery Date:</label>
                        <input type="date" name="delivery_date" id="delivery_date" value="{{ $delivery_order->delivery_date }}" class="form-control">
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label">Customer:</label>
                        <select name="customer_id" id="customer_id" onchange="get_sale_orders()"
                            class="form-control select2">
                            <option value="">Select Customer</option>
                            @foreach ($customers ?? [] as $customer)
                                <option value="{{ $customer->id }}" @selected($delivery_order->customer_id == $customer->id)>{{ $customer->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label">Sale Orders:</label>
                        <select name="sale_order_id" id="sale_order"
                            onchange="get_so_detail(), get_receipt_vouchers(), get_so_items()"
                            class="form-control select2">
                            <option value="">Select SO</option>
                            @foreach ($sale_orders as $sale_order)
                                <option value="{{ $sale_order->id }}" @selected($delivery_order->so_id == $sale_order->id)>
                                    {{ $sale_order->reference_no }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                @if ($sale_order_of_delivery_order->pay_type_id == 3)
                    <div class="col-md-4 advanced">
                        <div class="form-group">
                            <label class="form-label">Receipt Vouchers:</label>
                            <select name="receipt_vouchers[]" id="receipt_vouchers"
                                onchange="add_advance_amount(); change_withhold_amount()" class="form-control select2"
                                multiple>
                                <option value="">Select Receipt Vouchers</option>

                                @foreach ($receipt_vouchers as $receipt_voucher)
                                    <option value="{{ $receipt_voucher->id }}"
                                        data-amount="{{ $receipt_voucher->remaining_amount ?? $receipt_voucher->withhold_amount ?? 0 }}" @selected(in_array($receipt_voucher->id, $delivery_order->receipt_vouchers->pluck('id')->toArray()))>
                                        {{ $receipt_voucher->unique_no }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                @endif
            </div>

            <div class="row">
                @if ($sale_order_of_delivery_order->pay_type_id == 3)
                
                    <div class="col-md-4 advanced">
                        <div class="form-group">
                            <label class="form-label">Advance Amount:</label>
                            <input type="number" name="advance_amount" onchange=""
                                value="{{ $delivery_order->advance_amount }}" id="advance_amount" class="form-control"
                                readonly>
                        </div>
                    </div>

                    <div class="col-md-4 advanced">
                        <div class="form-group">
                            <label class="form-label">Withhold Amount:</label>
                            <input type="number" name="withhold_amount" value="{{ $delivery_order->withhold_amount }}"
                                value="0" onkeyup="change_withhold_amount()" id="withhold_amount" class="form-control">

                        </div>
                    </div>

                    <div class="col-md-4 advanced">
                        <div class="form-group">
                            <label class="form-label">Withhold for RV:</label>
                            <select name="withhold_for_rv" id="withhold_for_rv" class="form-control select2">
                                <option value="">Select Receipt Vouchers</option>
                                @foreach ($receipt_vouchers as $receipt_voucher)
                                    <option value="{{ $receipt_voucher->id }}" @selected($receipt_voucher->id == $delivery_order->withhold_for_rv_id)
                                        data-amount="{{ $receipt_voucher->remaining_amount ?? $receipt_voucher->withhold_amount ?? 0 }}">
                                        {{ $receipt_voucher->unique_no }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                @endif

                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">Dispatch Date:</label>
                        <input type="date" name="dispatch_date" onchange="getNumber()"
                            value="{{ $delivery_order->dispatch_date }}" id="dispatch_date" class="form-control">
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">Do No:</label>
                        <input type="text" name="reference_no" id="reference_no"
                            value="{{ $delivery_order->reference_no }}" class="form-control" readonly>
                    </div>
                </div>


            </div>

            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label">Locations:</label>
                        <select name="location_id" id="locations" onchange="selectLocation(this)"
                            class="form-control select2">
                            <option value="">Select Locations</option>
                            @foreach (get_locations() as $location)
                                <option value="{{ $location->id }}" @selected($location->id == $delivery_order->location_id)>{{ $location->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label">Factory:</label>
                        <select name="arrival_id" id="arrivals" onchange="selectStorage(this)"
                            class="form-control select2" @if (!$delivery_order->arrival_location_id) disabled @endif>
                            <option value="">Select Factory</option>
                            @if($delivery_order->arrival_location_id)
                                <option value="{{ $delivery_order->arrival_location_id }}" selected>
                                    {{ arrival_name_by_id($delivery_order->arrival_location_id) }}</option>
                            @else
                                @foreach (get_arrivals_by($delivery_order->location_id) as $location)
                                    <option value="{{ $location->id }}" @selected($location->id == $delivery_order->arrival_location_id)>
                                        {{ $location->name }}</option>
                                @endforeach
                            @endif
                           
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label">Section:</label>
                        <select name="storage_id" id="storages" class="form-control select2" >
                            <option value="">Select Section</option>
                            @if($delivery_order->sub_arrival_location_id)
                                <option value="{{ $delivery_order->sub_arrival_location_id }}" selected>
                                    {{ sub_arrival_name_by_id($delivery_order->sub_arrival_location_id) }}</option>
                            @else
                                @foreach (get_sub_arrivals_by($delivery_order->arrival_location_id) as $location)
                                    <option value="{{ $location->id }}" @selected($location->id == $delivery_order->sub_arrival_location_id)>
                                        {{ $location->name }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                </div>
            </div>

            <!-- Row 2: Sale Orders, Sauda Type -->
            <div class="row">
                {{-- <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">Payment Terms:</label>
                        <select name="payment_term_id" id="payment_term_id" class="form-control select2">
                            <option value="">Select Payment Term</option>
                            @foreach ($payment_terms as $payment_term)
                                <option value="{{ $payment_term->id }}" @selected($payment_term->id == $delivery_order->payment_term_id)>
                                    {{ $payment_term->desc }}</option>
                            @endforeach
                        </select>
                    </div>
                </div> --}}
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">Sauda Type:</label>
                        <input type="hidden" name="sauda_type" id="sauda_type_hidden"
                            value="{{ $delivery_order->sauda_type }}">
                        <select name="sauda_type" id="sauda_type" class="form-control select2" disabled>
                            <option value="">Select Sauda Type</option>
                            <option value="pohanch" @selected($delivery_order->sauda_type == 'pohanch')>Pohanch</option>
                            <option value="x-mill" @selected($delivery_order->sauda_type == 'x-mill')>X-mill</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">Line Description:</label>
                        <textarea name="line_desc" id="line_desc" class="form-control">{{ $delivery_order->line_desc }}</textarea>
                    </div>
                </div>
            </div>
        </div>



        <!-- Row 3: Customer, Contract Terms, Locations -->






        

    </div>

    <div class="row form-mar">

        <div class="col-12 text-right mb-2">
            <button type="button" style="float: right" class="btn btn-sm btn-primary" onclick="addRow()"
                id="addRowBtn" disabled>
                <i class="fa fa-plus"></i>&nbsp; Add New Item
            </button>
        </div>
        <div class="col-md-12">
            <div class="table-responsive" style="overflow-x: auto; white-space: nowrap;">
                <table class="table table-bordered" id="salesInquiryTable" style="min-width:2000px;">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Bag Type</th>
                            <th>Pack Size</th>
                            <th>No of Bags</th>
                            <th>Quantity (Kg)</th>
                            <th>Rate</th>
                            <th>Amount</th>
                            <th>Brand</th>
                            <th>Desc</th>
                            <th style="display: none;">Pack Size</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="soTableBody">
                        @foreach ($delivery_order->delivery_order_data as $index => $data)
                            @php
                                $balance = delivery_order_balance($data->so_data_id);
                                $allowed_value = (int)$balance;
                            @endphp

                            <tr id="row_{{ $index }}">
                                <td>
                                    <select name="item_id[]" id="item_id_{{ $index }}" class="form-control select2">
                                        <option value="">Select Item</option>
                                        @foreach ($items as $item)
                                            <option value="{{ $item->id }}" @selected($item->id == $data->item_id)>
                                                {{ $item->name }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <input type="text" id="bag_type_display_{{ $index }}"
                                        value="{{ bag_type_name($data->bag_type) }}"
                                        class="form-control" readonly>

                                    <input type="hidden" name="bag_type[]" value="{{ $data->bag_type }}">

                                        <input type="hidden" name="so_data_id[]" id="so_data_id_{{ $index }}"
                                        value="{{ $data->so_data_id }}">
                                </td>
                                <td>
                                    <input type="text" name="bag_size[]" id="bag_size_{{ $index }}"
                                        value="{{ $data->bag_size }}" oninput="calc(this)"
                                        class="form-control bag_size" step="0.01" min="0">
                                </td>
                                <td>
                                    <input type="hidden" class="allowed_value" value="{{ $allowed_value }}" />
                                    <input type="text" style="margin-bottom: 10px;" name="no_of_bags[]" id="no_of_bags_{{ $index }}"
                                        value="{{ round($data->bag_size * ($data->qty ?? 0)) }}" readonly
                                        class="form-control no_of_bags" step="0.01" min="0">
                                    <span style="font-size: 14px;">Available: {{ $allowed_value }}</span>
                                </td>
                                <td>
                                    <input type="text" name="qty[]" id="qty_{{ $index }}"
                                        value="{{ $data->qty }}" class="form-control qty"
                                        step="0.01" min="0" oninput="calc(this)">
                                </td>
                                <td>
                                    <input type="text" name="rate[]" id="rate_{{ $index }}"
                                        value="{{ $data->rate }}" onkeyup="calc(this)" class="form-control rate"
                                        step="0.01" min="0">
                                </td>
                                <td>
                                    <input type="text" name="amount[]" id="amount_{{ $index }}"
                                        value="{{ $data->rate * ($data->qty ?? 0) }}"
                                        class="form-control amount" readonly>
                                </td>
                                <td>
                                    <select name="brand_id[]" id="brand_id_{{ $index }}" class="form-control select2">
                                        <option value="">Select Brand</option>
                                        @foreach (getAllBrands() as $brand)
                                            <option value="{{ $brand->id }}" @selected($brand->id == $data->brand_id)>
                                                {{ $brand->name }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <input type="text" name="desc[]" id="desc_{{ $index }}"
                                        value="{{ $data->description }}" class="form-control">
                                </td>
                                <td style="display: none;">
                                    <input type="text" name="pack_size[]" id="pack_size_{{ $index }}"
                                        class="form-control pack_size" value="{{ $data->pack_size ?? 0 }}" readonly>
                                </td>
                                <td>
                                    <button type="button" disabled class="btn btn-danger btn-sm removeRowBtn"
                                        style="width:60px;">
                                        <i class="fa fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <input type="hidden" id="rowCount" value="0">

    <div class="row bottom-button-bar">
        <div class="col-12 text-end">
            <a type="button"
                class="btn btn-danger modal-sidebar-close position-relative top-1 closebutton me-2">Close</a>
            <button type="submit" class="btn btn-primary submitbutton">Save</button>
        </div>
    </div>
</form>

<script>
    salesInquiryRowIndex = 1;

    $(document).ready(function() {
        $('.select2').select2();
        applySaudaType(`{{ strtolower($delivery_order->sauda_type) }}`);
        // Initialize location options based on current sale order locations
        get_so_detail();

        
    });

    function applySaudaType(saudaType) {
        const normalized = (saudaType || '').toLowerCase();
        $('#sauda_type').val(normalized).trigger('change');
        $('#sauda_type_hidden').val(normalized);
        $('#sauda_type').prop('disabled', true);
    }

    function updateLocations(locations) {
        const select = $("#locations");
        const current = select.val();
        select.empty();
        select.append('<option value="">Select Locations</option>');

        (locations || []).forEach(loc => {
            select.append(`<option value="${loc.id}">${loc.text}</option>`);
        });

        const desired = current || initialLocationId || '';
        if (desired && (locations || []).find?.(l => String(l.id) === String(desired))) {
            select.val(String(desired));
        } else {
            select.val(desired ? String(desired) : '');
        }

        select.prop('disabled', false); // no change trigger on load
        select.select2();

        // Reset dependent dropdowns; will be rehydrated by selectLocation when data exists
        $("#arrivals").empty().append('<option value=\"\">Select Factory</option>').prop('disabled', true).trigger('change.select2');
        $("#storages").empty().append('<option value=\"\">Select Section</option>').prop('disabled', true).trigger('change.select2');
    }

    function is_allowed(el) {
        const allowed_value = $(el).closest("tr").find(".allowed_value").val();
        const written_value = $(el).val();
        if(parseFloat(written_value) > parseFloat(allowed_value)) {
            Swal.fire({
                icon: 'warning',
                title: 'Limit Exceeded',
                text: 'Cannot proceed more than ' + allowed_value,
            });

            $(el).val(allowed_value);
        }
    }

    function selectLocation(el, isInit = false) {
        const company = $(el).val();
        const allowedFactories = soFactoryMap[String(company)] || [];

        if (!company) {
            $("#arrivals").prop("disabled", true);
            $("#arrivals").empty();
            $("#storages").prop("disabled", true);
            $("#storages").empty();
            return;
        }

        if (allowedFactories.length > 0) {
            $("#arrivals").prop("disabled", false).empty().append('<option value=\"\">Select Factory</option>');
            allowedFactories.forEach(loc => {
                $("#arrivals").append(`<option value="${loc.id}">${loc.text}</option>`);
            });
            $("#arrivals").val('').select2();

            // Apply initial arrival/section if present
            if (!initialArrivalApplied && initialArrivalId) {
                $("#arrivals").val(String(initialArrivalId));
                initialArrivalApplied = true;
                selectStorage(document.getElementById("arrivals"), true);
            } else {
                $("#storages").prop("disabled", true).empty().append('<option value=\"\">Select Section</option>');
            }
        } else {
            // get.arrival-locations; send request to this url
            $("#arrivals").prop("disabled", false);
            $.ajax({
                url: "{{ route('sales.get.arrival-locations') }}",
                method: "GET",
                data: {
                    location_id: company
                },
                dataType: "json",
                success: function(res) {
                    $("#arrivals").empty();
                    $("#arrivals").append(`<option value=''>Select Arrivals</option>`)

                    res.forEach(loc => {
                        $("#arrivals").append(`
                        <option value="${loc.id}" >
                            ${loc.text}
                        </option>
                    `);
                    });

                    $("#arrivals").select2();

                    if (!initialArrivalApplied && initialArrivalId) {
                        $("#arrivals").val(String(initialArrivalId));
                        initialArrivalApplied = true;
                        selectStorage(document.getElementById("arrivals"), true);
                    }
                },
                error: function(error) {

                }
            });
        }
    }

    function selectStorage(el, isInit = false) {
        const arrival = $(el).val();
        const allowedSections = soSectionMap[String(arrival)] || [];
        console.log(arrival);
        if (!arrival) {
            $("#storages").prop("disabled", true);
            $("#storages").empty();
            return;
        }

        if (allowedSections.length > 0) {
            $("#storages").prop("disabled", false).empty().append('<option value=\"\">Select Section</option>');
            allowedSections.forEach(loc => {
                $("#storages").append(`<option value="${loc.id}">${loc.text}</option>`);
            });
            $("#storages").val('').select2();

            if (!initialStorageApplied && initialStorageId) {
                $("#storages").val(String(initialStorageId));
                initialStorageApplied = true;
            }
        } else {
            // get.arrival-locations; send request to this url
            $("#storages").prop("disabled", false);
            $.ajax({
                url: "{{ route('sales.get.storage-locations') }}",
                method: "GET",
                data: {
                    arrival_id: arrival
                },
                dataType: "json",
                success: function(res) {
                    console.log(res);
                    $("#storages").empty();
                    $("#storages").append(`<option value=''>Select Storage</option>`)
                    res.forEach(loc => {
                        $("#storages").append(`
                        <option value="${loc.id}">
                            ${loc.text}
                        </option>
                    `);
                    });

                    $("#storages").select2();

                    if (!initialStorageApplied && initialStorageId) {
                        $("#storages").val(String(initialStorageId));
                        initialStorageApplied = true;
                    }
                },
                error: function(error) {

                }
            });
        }
    }

    const isEdit = true;
    sum = 0;
    so_amount = 0;
    remaining_amount = 0;
    soFactoryMap = {};
    soSectionMap = {};
    initialLocationId = "{{ $delivery_order->location_id }}";
    initialArrivalId = "{{ $delivery_order->arrival_location_id }}";
    initialStorageId = "{{ $delivery_order->sub_arrival_location_id }}";
    initialArrivalApplied = false;
    initialStorageApplied = false;

    function add_advance_amount() {
        let selectedAmounts = $("#receipt_vouchers option:selected")
            .map(function() {
                return $(this).data("amount");
            }).get();


        sum = 0;
        selectedAmounts.forEach(selectedAmount => {
            sum += parseFloat(selectedAmount);
        });



        if (sum > 0) {
            $("#advance_amount").val(sum.toFixed(2));
        } else {
            $("#advance_amount").val("");
        }

    }

    function change_withhold_amount() {



        const withhold = parseFloat($("#withhold_amount").val()) || 0;
        const advance = parseFloat($("#advance_amount").val()) || 0;
        remaining_amount = advance - withhold;
        receipt_vouchers = $("#receipt_vouchers");
       
        bag_size = $("#bag_size_0").val();
        rate = $("#rate_0").val();
        const qtyVal = ((remaining_amount / rate)).toFixed(2);
        $("#qty_0").val(qtyVal);
        no_of_bags = Math.round(parseFloat(bag_size) * parseFloat(qtyVal));

        if(isNaN(no_of_bags)) {
            $("#no_of_bags_0").val(0);
        } else {
            $("#no_of_bags_0").val(no_of_bags);
        }


        if($("#withhold_amount").val() > 0 && receipt_vouchers.val()) {
            $("#withhold_for_rv").prop("disabled", false);
        } else {

            $("#withhold_for_rv").prop("disabled", true);
        }





        $("#withhold_for_rv").val("").trigger("change");
        $('#withhold_for_rv').select2({
            templateResult: function(data) {

                if (!data.id) return data.text;

                let amount = $(data.element).data('amount');


                if (parseFloat($("#withhold_amount").val()) > parseFloat(amount)) {
                    return null; // Hides this option
                }

                let $item = $(`
                    <span>
                        ${data.text}
                        <strong style="color: green; margin-left: 6px;">(${amount})</strong>
                    </span>
                `);

                return $item;
            }
        });

    }

    function addRow() {
        let index = salesInquiryRowIndex++;
        let row = `
        <tr id="row_${index}">
            <td>
                <select name="item_id[]" id="item_id_${index}" class="form-control select2">
                    <option value="">Select Item</option>
                    @foreach ($items ?? [] as $item)
                        <option value="{{ $item->id }}">{{ $item->name }}</option>
                    @endforeach
                </select>
            </td>
            <td>
                <select name="bag_type[]" id="bag_type_${index}" class="form-control select2">
                    <option value="">Select Bag Type</option>
                    @foreach ($bag_types ?? [] as $bag_type)
                        <option value="{{ $bag_type->id }}">{{ $bag_type->name }}</option>
                    @endforeach
                </select>
                <input type="hidden" name="so_data_id[]" value="">
            </td>
            <td>
                <input type="text" name="bag_size[]" id="bag_size_${index}" class="form-control bag_size" onkeyup="calc(this)" step="0.01" min="0">
            </td>
            <td>
                <input type="text" name="no_of_bags[]" id="no_of_bags_${index}" class="form-control no_of_bags" step="0.01" min="0" readonly>
            </td>
            <td>
                <input type="text" name="qty[]" id="qty_${index}" class="form-control qty" step="0.01" min="0" oninput="calc(this)">
            </td>
            <td>
                <input type="text" name="rate[]" id="rate_${index}" onkeyup="calc(this)" class="form-control rate" step="0.01" min="0">
            </td>
            <td>
                <input type="text" name="amount[]" id="amount_${index}" class="form-control amount" readonly>
            </td>
            <td>
                <select name="brand_id[]" id="brand_id_${index}" class="form-control select2">
                    <option value="">Select Brand</option>
                    @foreach (getAllBrands() ?? [] as $brand)
                        <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                    @endforeach
                </select>
            </td>
            <td>
                <input type="text" name="desc[]" id="desc_${index}" class="form-control">
            </td>
            <td style="display: none;">
                <input type="text" name="pack_size[]" id="pack_size_${index}" value="0" class="form-control pack_size" readonly>
            </td>
            <td>
                <button type="button" class="btn btn-danger btn-sm removeRowBtn" onclick="removeRow(${index})" style="width:60px;">
                    <i class="fa fa-trash"></i>
                </button>
            </td>
        </tr>
    `;
        $('#soTableBody').append(row);
        $(`#item_id_${index}`).select2();
        $(`#bag_type_${index}`).select2();
        $(`#brand_id_${index}`).select2();
    }

    function removeRow(index) {
        $('#row_' + index).remove();
    }

    function calc(el) {
        const element = $(el).closest("tr");
        const bag_size = $(element).find(".bag_size");
        const no_of_bags = $(element).find(".no_of_bags");
        const qty = $(element).find(".qty");
        const rate = $(element).find(".rate");
        const amount = $(element).find(".amount");

        // Calculate no_of_bags from bag_size * qty
        const balance = parseFloat(no_of_bags.data("balance")) || parseFloat($(element).find(".allowed_value").val()) || null;

        if (bag_size.val() && qty.val()) {
            let bagsResult = Math.round(parseFloat(bag_size.val()) * parseFloat(qty.val()));

            if (balance && bagsResult > balance) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Limit Exceeded',
                    text: 'No of bags cannot exceed available balance (' + balance + ').',
                });
                bagsResult = balance;
                const limitedQty = parseFloat(bagsResult) / parseFloat(bag_size.val() || 1);
                qty.val(limitedQty.toFixed(2));
            }

            no_of_bags.val(bagsResult);
        } else {
            no_of_bags.val('');
        }

        // Calculate amount from qty * rate
        const qtyVal = parseFloat(qty.val()) || 0;
        const rateVal = parseFloat(rate.val()) || 0;
        amount.val((qtyVal * rateVal).toFixed(2));
    }

    function validateBagsBeforeSubmit() {
        let valid = true;
        $("#soTableBody tr").each(function() {
            const row = $(this);
            const no_of_bags = row.find(".no_of_bags");
            const bag_size = row.find(".bag_size");
            const qty = row.find(".qty");
            const balance = parseFloat(no_of_bags.data("balance")) || parseFloat(row.find(".allowed_value").val()) || null;

            if (balance) {
                if (bag_size.val() && qty.val()) {
                    const bagsResult = Math.round(parseFloat(bag_size.val()) * parseFloat(qty.val()));
                    if (bagsResult > balance) {
                        valid = false;
                        Swal.fire({
                            icon: 'warning',
                            title: 'Limit Exceeded',
                            text: 'No of bags cannot exceed available balance (' + balance + ').',
                        });
                        return false;
                    }
                }
            }
        });
        return valid;
    }

    $("#ajaxSubmit").on("submit", function(e) {
        if (!validateBagsBeforeSubmit()) {
            e.preventDefault();
        }
    });

    function get_sale_orders() {
        const customer_id = $("#customer_id").val();
        // get-sale-inquiries-against-customer

        $.ajax({
            url: "{{ route('sales.get.delivery-order.getSoAgainstCustomer') }}",
            method: "GET",
            data: {
                customer_id: customer_id
            },
            dataType: "json",
            success: function(res) {
                $("#sale_order").empty();

                // Add default "Select Sale Order" option first
                $("#sale_order").append('<option value="" selected>Select Sale Order</option>');

                res.forEach(item => {
                    $("#sale_order").append(`
                        <option value="${item.id}" 
                                data-type="${item.type || ''}">
                            ${item.text}
                        </option>
                    `);
                });

                $("#sale_order").select2();
            },
            error: function(error) {

            }
        });

        // get-sale-inquiry-data
    }

    function get_inquiry_data() {
        const inquiry_id = $("#inquiry_id").val();

        $.ajax({
            url: "{{ route('sales.get-sale-inquiry-data') }}",
            method: "GET",
            data: {
                inquiry_id: inquiry_id
            },
            dataType: "html",
            success: function(res) {
                console.log("success");
                $("#alesInquiryBody").empty();
                $("#salesInquiryBody").html(res);
            },
            error: function(error) {
                console.log(error);
            }
        });

    }

    function getNumber() {
        $.ajax({
            url: "{{ route('sales.get.delivery-order.getnumber') }}",
            method: "GET",
            data: {
                contract_date: $("#dispatch_date").val()
            },
            dataType: "json",
            success: function(res) {
                $("#reference_no").val(res.so_no)
            },
            error: function(error) {
                // Handle errors here
                $('.loader-container').hide();
                console.error("Error:", error);
            }
        });
    }


    function calculate_percentage(el) {
        const percentage = parseFloat($(el).val()) || 0;
        const unused_amount = $("#unused_amount").val();
        const err_message = $(".advance-amount-err-message");

        if (!percentage) {
            $("#advance_amount").val("");
            $("#advance_amount").prop("disabled", false);
            return;
        }
        const so_amount = parseFloat($("#so_amount").val()) || 0;


        const result = (so_amount * percentage) / 100;

        if (result > unused_amount) {
            $(".submitbutton").prop("disabled", true);
            $("#advance_amount").addClass("is-invalid");
            err_message.css("display", "block");
        } else {
            $(".submitbutton").prop("disabled", false);
            $("#advance_amount").removeClass("is-invalid");
            err_message.css("display", "none");
        }

        $("#advance_amount").prop("disabled", true);
        $("#advance_amount").val(result);

    }

    function manualChecking() {
        const advance_amount = $("#advance_amount").val();
        const unused_amount = $("#unused_amount").val();
        const err_message = $(".advance-amount-err-message");

        if (parseFloat(advance_amount) > parseFloat(unused_amount)) {
            $(".submitbutton").prop("disabled", true);
            $("#advance_amount").addClass("is-invalid");
            err_message.css("display", "block");
        } else {
            $(".submitbutton").prop("disabled", false);
            $("#advance_amount").removeClass("is-invalid");
            err_message.css("display", "none");
        }
    }


    function get_so_detail() {
        const soId = $("#sale_order").val();
        if (!soId) {
            applySaudaType('');
            updateLocations([]);
            return;
        }

        $.ajax({
            url: "{{ route('sales.get.delivery-order.details') }}",
            method: "GET",
            data: {
                so_id: soId,
            },
            dataType: "json",
            success: function(res) {
                // $("#amount_received").val(res.amount_received)
                // $("#so_amount").val(res.so_amount)
                // $("#unused_amount").val(res.unused_amount)

                applySaudaType(res.sauda_type);
                if (!isEdit) {
                    updateLocations(res.locations || []);
                }
                soFactoryMap = res.factory_map || {};
                soSectionMap = res.section_map || {};

                // $("#payment_term_id").val(res.payment_term_id);
                // $("#payment_term_id").trigger("change");

                so_amount = res.so_amount;

                // Re-apply saved location/arrival/section after refreshing maps
                $("#locations").val(String(initialLocationId || ''));
                // selectLocation(document.getElementById("locations"), true);

                // $("#locations").val(res.locations).trigger("change");
            },
            error: function(error) {
                // Handle errors here
                $('.loader-container').hide();
                console.error("Error:", error);
            }
        });
    }

    // get.delivery-order.getRvAgainstSo

    function get_receipt_vouchers() {
        $.ajax({
            url: "{{ route('sales.get.delivery-order.getRvAgainstSo') }}",
            method: "GET",
            data: {
                so_no: $("#sale_order option:selected").text(),
            },
            dataType: "json",
            success: function(res) {
                // withhold_for_rv

                let select = $("#receipt_vouchers");
                select.empty();
                select.append(
                    `<option value='' data-amount="0">Select Receipt Voucher</option>`
                );

                res.forEach(item => {
                    select.append(
                        `<option value="${item.id}"
                                data-amount="${item.amount}">
                            ${item.text}
                        </option>`
                    );
                });

                select.select2();


                select = $("#withhold_for_rv");
                select.empty();

                select.append(
                    `<option value='' data-amount="0">Select Receipt Voucher</option>`
                );
                res.forEach(item => {
                    select.append(
                        `<option value="${item.id}"
                                data-amount="${item.amount}">
                            ${item.text}
                        </option>`
                    );
                });

                select.select2();


            },
            error: function(error) {
                // Handle errors here
                $('.loader-container').hide();
                console.error("Error:", error);
            }
        });
    }

    function get_so_items() {
        $.ajax({
            url: "{{ route('sales.get.delivery-order.getSoItems') }}",
            method: "GET",
            data: {
                so_id: $("#sale_order").val(),
            },
            dataType: "html",
            success: function(res) {
                $('#soTableBody').empty();

                $('#soTableBody').html(res);

            },
            error: function(error) {
                // Handle errors here
                $('.loader-container').hide();
                console.error("Error:", error);
            }
        });
    }
</script>
