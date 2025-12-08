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

<form action="{{ route('sales.delivery-challan.update', [ 'delivery_challan' => $delivery_challan->id ]) }}" method="POST" id="ajaxSubmit" autocomplete="off">
    @csrf
    {{ method_field("PUT") }}
    <input type="hidden" id="listRefresh" value="{{ route('sales.get.delivery-challan.list') }}" />

    <div class="row form-mar">
        <!-- Left side fields (2 columns) -->
        <div class="col-md-12">
            <!-- Row 1: Dispatch Date, Do No -->

            <div class="row" style="margin-top: 10px">
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label">DC NO:</label>
                        <input type="text" name="dc_no" value="{{ $delivery_challan->dc_no }}" id="dc_no"
                            id="text" class="form-control">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label">Date:</label>
                        <input type="date" name="date" onchange="getNumber()"
                            value="{{ $delivery_challan->dispatch_date }}" id="date" class="form-control">
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Customer:</label>
                    <select name="customer_id" id="customer_id" onchange="get_delivery_orders()"
                        class="form-control select2">
                        <option value="">Select Customer</option>
                        @foreach ($customers ?? [] as $customer)
                            <option value="{{ $customer->id }}" @selected($delivery_challan->customer_id == $customer->id)>{{ $customer->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

            </div>

            <div class="row">

                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label">Reference Number:</label>
                        <input type="text" name="reference_number" id="reference_number"
                            value="{{ $delivery_challan->reference_number }}" class="form-control">
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Locations:</label>
                    <select name="locations" id="locations" onchange="selectLocation(this); get_delivery_orders()"
                        class="form-control select2">
                        <option value="">Select Locations</option>
                        @foreach (get_locations() ?? [] as $location)
                            <option value="{{ $location->id }}" @selected($delivery_challan->location_id == $location->id)>{{ $location->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Arrival Location:</label>
                    <select name="arrival_locations" id="arrivals" onchange="get_delivery_orders()"
                        class="form-control select2">
                        <option value="">Select Arrivals Locations</option>
                        @foreach (get_arrivals_by($delivery_challan->location_id) ?? [] as $location)
                            <option value="{{ $location->id }}" @selected($delivery_challan->arrival_id == $location->id)>{{ $location->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label">Labour:</label>
                        <select name="labour" id="labour" onchange="" class="form-control select2">
                            <option value="">Select Labours</option>
                            <option value="1" @selected($delivery_challan->labour == 1)>Labour 1</option>
                            <option value="2" @selected($delivery_challan->labour == 2)>Labour 2</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label">Labour Amount:</label>
                        <input type="number" name="labour_amount" value="{{ $delivery_challan->labour_amount }}"
                            onchange="" id="labour_amount" class="form-control">
                    </div>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Sauda Types:</label>
                    <select name="sauda_type" id="sauda_type" class="form-control select2">
                        <option value="">Select Sauda types</option>
                        <option value="pohanch" @selected($delivery_challan->sauda_type == 'pohanch')>Pohanch</option>
                        <option value="x-mill" @selected($delivery_challan->sauda_type == 'x-mill')>X-mill</option>
                    </select>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label">Transporter:</label>
                        <select name="transporter" id="transporter" onchange="" class="form-control select2">
                            <option value="">Select Transporter</option>
                            <option value="1" @selected($delivery_challan->transporter == 1)>Transporter 1</option>
                            <option value="2" @selected($delivery_challan->transporter == 2)>Transporter 2</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label">Transporter Amount:</label>
                        <input type="number" name="transporter_amount" onchange=""
                            value="{{ $delivery_challan->transporter_amount }}" id="transporter_amount"
                            class="form-control">
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="form-label">DO Numbers:</label>
                    <select name="do_no[]" id="do_no" onchange="get_items(this)" class="form-control select2"
                        multiple>
                        <option value="">Select Delivery Orders</option>
                        @foreach ($delivery_orders as $delivery_order)
                            <option value="{{ $delivery_order->id }}" @selected(in_array($delivery_order->id, $delivery_challan->delivery_order->pluck('id')->toArray()))>
                                {{ $delivery_order->reference_no }}</option>
                        @endforeach

                    </select>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label">In-house Weighbridge:</label>
                        <select name="weighbridge" id="weighbridge" onchange="" class="form-control select2">
                            <option value="">Select Weighbridge</option>
                            <option value="1" @selected($delivery_challan->{'inhouse-weighbridge'} == 1)>Weighbridge 1</option>
                            <option value="2" @selected($delivery_challan->{"inhouse-weighbridge"} == 2)>Weighbridge 2</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label">Weighbridge Amount:</label>
                        <input type="number" name="weighbridge_amount"
                            value="{{ $delivery_challan->{"weighbridge-amount"} }}" onchange=""
                            id="weighbridge_amount" class="form-control">
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Remarks:</label>
                    <textarea name="remarks" id="remarks" class="form-control">{{ $delivery_challan->remarks }}</textarea>
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
                            <th>Truck No.</th>
                            <th>Bilty No.</th>
                            <th>Desc</th>
                            <th style="display: none">Pack Size</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="dcTableBody">
                        @foreach ($delivery_challan->delivery_challan_data as $index => $data)
                            @php
                                $balance = $data->no_of_bags;
                            @endphp
                            <tr id="row_{{ $index }}">
                                <td>

                                    <input type="text" name="" id="item_id_read_only{{ $index }}"
                                        value="{{ getItem($data->item_id)?->name }}" onkeyup="calc(this)"
                                        class="form-control bag_type" step="0.01" min="0" readonly>

                                    <input type="hidden" name="item_id[]" id="item_id_{{ $index }}"
                                        value="{{ $data->item_id }}" onkeyup="calc(this)"
                                        class="form-control item_id" step="0.01" min="0">

                                    <input type="hidden" name="do_data_id[]" id="do_data_id_{{ $index }}"
                                        value="{{ $data->do_data_id }}" onkeyup="calc(this)"
                                        class="form-control do_data_id" step="0.01" min="0">
                                </td>
                                
                                <td>

                                    <input type="text" name="" id="bag_type_{{ $index }}"
                                        value="{{ $data->bag_type ? bag_type_name($data->bag_type) : '' }}" onkeyup="calc(this)"
                                        class="form-control bag_type" step="0.01" min="0" readonly>

                                    <input type="hidden" name="bag_type[]" id="bag_type_{{ $index }}"
                                        value="{{ $data->bag_type }}" onkeyup="calc(this)"
                                        class="form-control bag_type" step="0.01" min="0">

                                    <input type="hidden" name="so_data_id[]" id="so_data_id_{{ $index }}"
                                        value="{{ $data->id }}" onkeyup="calc(this)"
                                        class="form-control so_data_id" step="0.01" min="0">
                                </td>
                              
                                <td>
                                    <input type="text" name="bag_size[]" id="bag_size_{{ $index }}"
                                        value="{{ $data->bag_size }}" onkeyup="calc(this)"
                                        class="form-control bag_size" step="0.01" min="0">
                                </td>
                                <td>
                                    <input type="text" name="no_of_bags[]" id="no_of_bags_{{ $index }}"
                                        onkeyup="calc(this)" value="{{ $balance }}"
                                        class="form-control no_of_bags" step="0.01" min="0">
                                </td>
                                <td>
                                    <input type="text" name="qty[]" id="qty_{{ $index }}"
                                        value="{{ $data->bag_size * $balance }}" class="form-control qty"
                                        step="0.01" min="0" readonly>
                                </td>
                                <td>
                                    <input type="text" name="rate[]" id="rate_{{ $index }}"
                                        value="{{ $data->rate }}" class="form-control rate" step="0.01"
                                        min="0">
                                </td>
                                <td>
                                    <input type="text" name="amount[]" id="amount_{{ $index }}"
                                        value="{{ $data->rate * ($data->bag_size * $balance) }}"
                                        class="form-control amount" readonly>
                                </td>
                                <td>
                                    <input type="text" name="" id="brand_id_read_only{{ $index }}"
                                        value="{{ getBrandById($data->brand_id)?->name }}" onkeyup="calc(this)"
                                        class="form-control brand_id" step="0.01" min="0" readonly>

                                    <input type="hidden" name="brand_id[]" id="brand_id_{{ $index }}"
                                        value="{{ $data->brand_id }}" onkeyup="calc(this)"
                                        class="form-control item_id" step="0.01" min="0">
                                </td>
                                <td>
                                    <input type="text" name="truck_no[]" id="truck_no_{{ $index }}"
                                        class="form-control truck_no" value="{{ $data->truck_no }}">
                                </td>
                                <td>
                                    <input type="text" name="bilty_no[]" id="bilty_no_{{ $index }}"
                                        value="{{ $data->bilty_no }}" class="form-control bilty_no">
                                </td>
                                <td>
                                    <input type="text" name="desc[]" id="desc_{{ $index }}"
                                        class="form-control" value="{{ $data->description }}">
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
    });


    sum = 0;
    so_amount = 0;
    remaining_amount = 0;

    function check_so_type() {
        const type = $("#sale_order").find("option:selected").data("type");
        if (type == 8) {
            $(".advanced").css("display", "block");
        } else {
            $(".advanced").css("display", "none");
        }
    }

    function selectLocation(el) {
        const company = $(el).val();

        if (!company) {
            alert("no");
            $("#arrivals").prop("disabled", true);
            $("#arrivals").empty();
            return;
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
                    $("#arrivals").append(`<option value=''>Select Arrival Locations</option>`)

                    res.forEach(delivery_order => {
                        $("#arrivals").append(`
                        <option value="${delivery_order.id}" >
                            ${delivery_order.text}
                        </option>
                    `);
                    });

                    $("#arrivals").select2();
                },
                error: function(error) {

                }
            });
        }
    }

    function get_items(el) {
        // get.delivery-challan.get-items
        const delivery_orders = $(el).val();

        $.ajax({
            url: "{{ route('sales.get.delivery-challan.get-items') }}",
            method: "GET",
            data: {
                delivery_order_ids: $(el).val(),
            },
            dataType: "html",
            success: function(res) {
                $("#dcTableBody").empty();
                $("#dcTableBody").html(res);
                $(".select2").select2();
            },
            error: function(error) {

            }
        });
    }

    function get_delivery_orders() {

        const customer_id = $("#customer_id").val();
        const location_id = $("#locations").val();
        const arrival_location_id = $("#arrivals").val();

        if (!customer_id || !location_id || !arrival_location_id) return;

        $.ajax({
            url: "{{ route('sales.get.delivery-challan.get-do') }}",
            method: "GET",
            data: {
                customer_id: $("#customer_id").val(),
                company_location_id: $("#locations").val(),
                arrival_location_id: $("#arrivals").val()
            },
            dataType: "json",
            success: function(res) {
                console.log(res);
                $("#do_no").empty();
                $("#do_no").append(`<option value=''>Select Delivery Order</option>`)

                res.forEach(delivery_order => {
                    $("#do_no").append(`
                    <option value="${delivery_order.id}" >
                        ${delivery_order.text}
                    </option>
                `);
                });

                $("#arrivals").select2();
            },
            error: function(error) {

            }
        });
    }

    function selectStorage(el) {
        const arrival = $(el).val();
        console.log(arrival);
        if (!arrival) {
            $("#storages").prop("disabled", true);
            $("#storages").empty();
            return;
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
                },
                error: function(error) {

                }
            });
        }
    }

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
        remaining_amount = parseFloat($("#advance_amount").val() ?? 0) - parseFloat($("#withhold_amount").val() ?? 0);
        rate = $("#rate_0").val();
        $("#qty_0").val((remaining_amount / rate).toFixed(2));

        $("withhold_for_rv").val("").trigger("change");
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
                <input type="number" name="qty[]" id="qty_${index}" onkeyup="calc(this)" class="form-control qty" step="0.01" min="0">
            </td>
            <td>
                <input type="number" name="rate[]" id="rate_${index}" onkeyup="calc(this)" class="form-control rate" step="0.01" min="0">
            </td>
            <td>
                <input type="text" name="amount[]" id="amount_${index}" class="form-control amount" readonly>
            </td>
            <td>
                <button type="button" class="btn btn-danger btn-sm removeRowBtn" onclick="removeRow(${index})" style="width:60px;">
                    <i class="fa fa-trash"></i>
                </button>
            </td>
        </tr>
    `;
        $('#salesInquiryBody').append(row);
        $(`#item_id_${index}`).select2();
        $('#row_0 .removeRowBtn').prop('disabled', true);
        $('.removeRowBtn').not('#row_0 .removeRowBtn').prop('disabled', false);
    }

    function removeRow(index) {
        $('#row_' + index).remove();
        if ($('#salesInquiryBody tr').length === 1) {
            $('#row_0 .removeRowBtn').prop('disabled', true);
        }
    }

    // function calc(el) {
    //     const element = $(el).closest("tr");

    //     const rate = parseFloat($(element).find(".rate").val()) || 0;
    //     const qty = parseFloat($(element).find(".qty").val()) || 0;

    //     const amount = $(element).find(".amount");

    //     amount.val(rate * qty);
    // }


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

                res.forEach(item => {
                    $("#sale_order").append(`
                        <option value="${item.id}" 
                                data-type="${item.type}">
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
            url: "{{ route('sales.get.delivery-challan.getNumber') }}",
            method: "GET",
            data: {
                contract_date: $("#date").val()
            },
            dataType: "json",
            success: function(res) {
                $("#dc_no").val(res.dc_no)
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
        $.ajax({
            url: "{{ route('sales.get.delivery-order.details') }}",
            method: "GET",
            data: {
                so_id: $("#sale_order").val(),
            },
            dataType: "json",
            success: function(res) {
                // $("#amount_received").val(res.amount_received)
                // $("#so_amount").val(res.so_amount)
                // $("#unused_amount").val(res.unused_amount)

                // $("#sauda_type").val(res.sauda_type)
                // $("#sauda_type").trigger("change");

                // $("#payment_term_id").val(res.payment_term_id);
                // $("#payment_term_id").trigger("change");

                so_amount = res.so_amount;

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
                customer_id: $("#customer_id").val(),
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

        $('#soTableBody').empty();
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
