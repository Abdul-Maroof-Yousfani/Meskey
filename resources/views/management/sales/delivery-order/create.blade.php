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
            <div class="row">

                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label">Do No:</label>
                        <input type="text" name="reference_no" id="reference_no" class="form-control" readonly>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label">Do Date:</label>
                        <input type="date" name="dispatch_date" onchange="getNumber()" id="dispatch_date"
                            class="form-control">
                    </div>
                </div>

                <div class="col-md-4 advanced">
                    <div class="form-group">
                        <label class="form-label">Contract Type:</label>
                        <input type="hidden" name="sauda_type" id="sauda_type_hidden">
                        <select name="sauda_type" id="sauda_type" class="form-control select2" disabled>
                            <option value="">Select Contract Type</option>
                            <option value="pohanch">Pohanch</option>
                            <option value="x-mill">X-mill</option>
                        </select>
                    </div>
                </div>
            </div>

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
                        <select name="withhold_for_rv" id="withhold_for_rv" class="form-control select2" disabled>
                            <option value="">Select Receipt Vouchers</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">Reference Number:</label>
                        <input type="text" name="line_desc" id="line_desc" class="form-control">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">Delivery Date:</label>
                        <input type="date" name="delivery_date" id="delivery_date" class="form-control">
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
                                <option value="{{ $location->id }}">{{ $location->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label">Factory:</label>
                        <select name="arrival_id" id="arrivals" onchange="selectStorage(this)"
                            class="form-control select2" disabled>
                            <option value="">Select Factory </option>
                            @foreach (get_locations() as $location)
                                <option value="{{ $location->id }}">{{ $location->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label">Section:</label>
                        <select name="storage_id" id="storages" class="form-control select2" disabled>
                            <option value="">Select Section</option>
                            @foreach (get_locations() as $location)
                                <option value="{{ $location->id }}">{{ $location->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <div class="form-group">
                        <label class="form-label">Remarks:</label>
                        <textarea name="remarks" id="remarks" class="form-control"></textarea>
                    </div>
                </div>
            </div>
        </div>



        <!-- Row 3: Customer, Contract Terms, Locations -->






    </div>

    <div class="row form-mar">

        {{-- <div class="col-12 text-right mb-2">
            <button type="button" style="float: right" class="btn btn-sm btn-primary" onclick="addRow()"
                id="addRowBtn" disabled>
                <i class="fa fa-plus"></i>&nbsp; Add New Item
            </button>
        </div> --}}

        <div class="col-md-12">
            <div class="table-responsive" style="overflow-x: auto; white-space: nowrap;">
                <table class="table table-bordered" id="salesInquiryTable" style="min-width:2000px;">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Bag Type</th>
                            <th>Packing</th>
                            <th>No of Bags</th>
                            <th>Quantity (Kg)</th>
                            <th>Rate per Kg</th>
                            <th>Amount</th>
                            <th>Brand</th>
                            <th>Desc</th>
                            <th style="display: none">Pack Size</th>
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
        $('#sauda_type').prop('disabled', true);
    });


    sum = 0;
    so_amount = 0;
    remaining_amount = 0;
    soFactoryMap = {};
    soSectionMap = {};

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

        if (current && locations.find?.(l => String(l.id) === String(current))) {
            select.val(current);
        } else {
            select.val('');
        }

        select.prop('disabled', false).trigger('change');
        select.select2();

        // Reset dependent dropdowns
        $("#arrivals").empty().append('<option value=\"\">Select Factory</option>').prop('disabled', true).trigger('change.select2');
        $("#storages").empty().append('<option value=\"\">Select Section</option>').prop('disabled', true).trigger('change.select2');
    }

    function check_so_type() {
        const type = $("#sale_order").find("option:selected").data("type");
        console.log(type);
        if (type == 10) {
            $(".advanced").css("display", "block");
        } else {
            $(".advanced").css("display", "none");
        }
    }

    function selectLocation(el) {
        const company = $(el).val();
        const allowedFactories = soFactoryMap[String(company)] || [];

        if (!company) {
            $("#arrivals").prop("disabled", true);
            $("#arrivals").empty();
            $("#storages").prop("disabled", true);
            $("#storages").empty();
            return;
        }

        // Prefer SO-selected factories; fallback to all factories for the location
        if (allowedFactories.length > 0) {
            $("#arrivals").prop("disabled", false).empty().append('<option value=\"\">Select Factory</option>');
            allowedFactories.forEach(loc => {
                $("#arrivals").append(`<option value="${loc.id}">${loc.text}</option>`);
            });
            $("#arrivals").val('').select2();

            // Auto-select the first factory for the chosen location (per sale order mapping)
            const firstFactoryId = allowedFactories?.[0]?.id;
            if (firstFactoryId) {
                $("#arrivals").val(String(firstFactoryId)).trigger('change.select2');
                // Populate sections for that factory immediately
                selectStorage(document.getElementById("arrivals"));
            } else {
                // Clear sections whenever factory list refreshes
                $("#storages").prop("disabled", true).empty().append('<option value=\"\">Select Section</option>').trigger('change.select2');
            }
        } else {
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

            // Auto-select the first section for the chosen factory (per sale order mapping)
            const firstSectionId = allowedSections?.[0]?.id;
            if (firstSectionId) {
                $("#storages").val(String(firstSectionId)).trigger('change.select2');
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



        const withhold = parseFloat($("#withhold_amount").val()) || 0;
        const advance = parseFloat($("#advance_amount").val()) || 0;
        remaining_amount = advance - withhold;
        receipt_vouchers = $("#receipt_vouchers");
       
        bag_size = $("#bag_size_0").val();
        rate = $("#rate_0").val();
        const qtyVal = ((remaining_amount / rate)).toFixed(2);
        $("#qty_0").val(qtyVal);
        no_of_bags = Math.round(parseFloat(qtyVal) / parseFloat(bag_size));
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
        const bag_size = $(element).find(".bag_size");
        const no_of_bags = $(element).find(".no_of_bags");
        const qty = $(element).find(".qty");
        const rate = $(element).find(".rate");
        const amount = $(element).find(".amount");

        const balance = parseFloat(no_of_bags.data("balance")) || parseFloat($(element).find(".allowed_value").val()) || null;

        // Calculate no_of_bags from bag_size * qty
        if (bag_size.val() && qty.val()) {
            let bagsResult = Math.round(parseFloat(bag_size.val()) * parseFloat(qty.val()));

            if (balance && bagsResult > balance) {
                // Swal.fire({
                //     icon: 'warning',
                //     title: 'Limit Exceeded',
                //     text: 'No of bags cannot exceed available balance (' + balance + ').',
                // });
                // bagsResult = balance;
                // adjust qty to match the capped bags
                // const limitedQty = parseFloat(bagsResult) / parseFloat(bag_size.val() || 1);
                // qty.val(limitedQty.toFixed(2));
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
                // Ensure latest calc
                if (bag_size.val() && qty.val()) {
                    const bagsResult = Math.round(parseFloat(bag_size.val()) * parseFloat(qty.val()));
                    if (bagsResult > balance) {
                        // valid = false;
                        // Swal.fire({
                        //     icon: 'warning',
                        //     title: 'Limit Exceeded',
                        //     text: 'No of bags cannot exceed available balance (' + balance + ').',
                        // });
                        // return false; // break .each
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
                const data = res.processedData;
                const rawData = res.rawData;
        
                $("#sale_order").empty();

                // Add default "Select Sale Order" option first
                $("#sale_order").append('<option value="" selected>Select Sale Order</option>');
                
                data.forEach(item => {
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
                updateLocations(res.locations || []);
                soFactoryMap = res.factory_map || {};
                soSectionMap = res.section_map || {};

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
