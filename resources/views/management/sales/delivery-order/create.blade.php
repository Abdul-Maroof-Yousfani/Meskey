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

<form action="{{ route('sales.delivery-order.store') }}" method="POST" id="ajaxSubmit" autocomplete="off">
    @csrf

    <input type="hidden" id="listRefresh" value="{{ route('sales.get.delivery-order.list') }}" />

    <div class="row form-mar">
        <!-- Left side fields (2 columns) -->
        <div class="col-md-12">
            <!-- Row 1: Dispatch Date, Do No -->
            <div class="row">

        <div class="col-md-4">
            <div class="form-group">
                <label class="form-label">Customer:</label>
                        <select name="customer_id" id="customer_id" onchange="get_sale_orders(); get_receipt_vouchers()"
                            class="form-control select2">
                    <option value="">Select Customer</option>
                    @foreach ($customers ?? [] as $customer)
                        <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label class="form-label">Sale Orders:</label>
                        <select name="sale_order_id" id="sale_order"
                            onchange="get_so_detail(), get_so_items(), check_so_type()" class="form-control select2">
                    <option value="">Select SO</option>
                        </select>
                    </div>
                </div>

                <div class="col-md-4 advanced">
                    <div class="form-group">
                        <label class="form-label">Receipt Vouchers:</label>
                        <select name="receipt_vouchers[]" id="receipt_vouchers"
                            onchange="add_advance_amount(); change_withhold_amount()" class="form-control select2"
                            multiple>
                            <option value="">Select Receipt Vouchers</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4 advanced">
                    <div class="form-group">
                        <label class="form-label">Advance Amount:</label>
                        <input type="number" name="advance_amount" onchange="" id="advance_amount"
                            class="form-control" readonly>
                    </div>
                </div>

                <div class="col-md-4 advanced">
                    <div class="form-group">
                        <label class="form-label">Withhold Amount:</label>
                        <input type="number" name="withhold_amount" value="0" onkeyup="change_withhold_amount()"
                            id="withhold_amount" class="form-control">

                    </div>
                </div>

                <div class="col-md-4 advanced">
                    <div class="form-group">
                        <label class="form-label">Withhold for RV:</label>
                        <select name="withhold_for_rv" id="withhold_for_rv" class="form-control select2">
                            <option value="">Select Receipt Vouchers</option>
                        </select>
                    </div>
                </div>


            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">Dispatch Date:</label>
                        <input type="date" name="dispatch_date" onchange="getNumber()" id="dispatch_date"
                            class="form-control">
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">Do No:</label>
                        <input type="text" name="reference_no" id="reference_no" class="form-control" readonly>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">Locations:</label>
                        <select name="location_id" id="locations" onchange="selectLocation(this)"
                            class="form-control select2">
                            <option value="">Select Locations</option>
                            @foreach (get_locations() as $location)
                                <option value="{{ $location->id }}">{{ $location->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">Arrival:</label>
                        <select name="arrival_id" id="arrivals" onchange="selectStorage(this)" class="form-control select2" disabled>
                            <option value="">Select Arrival </option>
                            @foreach (get_locations() as $location)
                                <option value="{{ $location->id }}">{{ $location->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-4" style="display: none;">
                    <div class="form-group">
                        <label class="form-label">Storage:</label>
                        <select name="storage_id" id="storages" class="form-control select2" disabled>
                            <option value="">Select Storage</option>
                            @foreach (get_locations() as $location)
                                <option value="{{ $location->id }}">{{ $location->name }}</option>
                    @endforeach
                </select>
                    </div>
            </div>
        </div>

            <!-- Row 2: Sale Orders, Sauda Type -->
            <div class="row">
                <div class="col-md-6">
            <div class="form-group">
                <label class="form-label">Sauda Type:</label>
                <select name="sauda_type" id="sauda_type" class="form-control select2">
                    <option value="">Select Sauda Type</option>
                    <option value="pohanch">Pohanch</option>
                    <option value="x-mill">X-mill</option>
                </select>
            </div>
        </div>


                <div class="col-md-6">
            <div class="form-group">
                        <label class="form-label">Line Description:</label>
                        <textarea name="line_desc" id="line_desc" class="form-control"></textarea>
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
                            <th style="display: none">Pack Size</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="soTableBody">

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
                    $("#arrivals").append(`<option value=''>Select Arrivals</option>`)
                
                    res.forEach(loc => {
                        $("#arrivals").append(`
                        <option value="${loc.id}" >
                            ${loc.text}
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

function calc(el) {
    const element = $(el).closest("tr");

    const rate = parseFloat($(element).find(".rate").val()) || 0;
    const qty = parseFloat($(element).find(".qty").val()) || 0;

    const amount = $(element).find(".amount");

    amount.val(rate * qty);
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

                // Add default "Select Sale Order" option first
                $("#sale_order").append('<option value="" selected>Select Sale Order</option>');

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
