<style>
    html,
    body {
        overflow-x: hidden;
    }
</style>

<form action="{{ route('sales.sale-order.store') }}" method="POST" id="ajaxSubmit2" autocomplete="off">
    @csrf

    <input type="hidden" id="listRefresh" value="{{ route('sales.get.sales-order.list') }}" />
    <div class="row form-mar">
        <!-- Left side fields (2 columns) -->
        <div class="col-md-12">
            <!-- Row 1: Dispatch Date, Do No -->
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label">Do No:</label>
                        <input type="text" name="reference_no" id="reference_no"
                            value="{{ $delivery_order->reference_no }}" class="form-control" readonly>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label">DO Date:</label>
                        <input type="date" name="dispatch_date" 
                            value="{{ date('Y-m-d') }}" 
                            onchange="getNumber()" id="dispatch_date" class="form-control" readonly>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label">Sauda Type:</label>
                        <select name="sauda_type" id="sauda_type" class="form-control select2" readonly>
                            <option value="">Select Sauda Type</option>
                            <option value="pohanch" @selected($delivery_order->sauda_type == 'pohanch')>Pohanch</option>
                            <option value="x-mill" @selected($delivery_order->sauda_type == 'x-mill')>X-mill</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label">Customer:</label>
                        <select name="customer_id" id="customer_id" onchange="get_sale_orders()"
                            class="form-control select2" readonly>
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
                        <label class="form-label">Sale Order:</label>
                        <select name="sale_order[]" id="sale_order"
                            onchange="add_advance_amount(); change_withhold_amount()" class="form-control select2"
                            disabled>
                            <option value="">Select Sales Order</option>
                            @foreach ($sales_orders as $sale_order)
                                <option value="{{ $sale_order->id }}" @selected($delivery_order->so_id == $sale_order->id)>
                                    {{ $sale_order->reference_no }}</option>
                            @endforeach
                        </select>

                    </div>
                </div>


                @if ($sale_order_of_delivery_order->pay_type_id == 10)
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label">Receipt Vouchers:</label>
                            <select name="receipt_vouchers[]" id="receipt_vouchers"
                                onchange="add_advance_amount(); change_withhold_amount()" class="form-control select2"
                                multiple disabled>
                                @foreach ($receipt_vouchers as $receipt_voucher)
                                    <option value="{{ $receipt_voucher->id }}" selected>
                                        {{ $receipt_voucher->unique_no }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                @endif
            </div>

        

        <div class="row">
            @if ($sale_order_of_delivery_order->pay_type_id == 10)
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label">Advance Amount:</label>
                        <input type="number" name="advance_amount" value="{{ $delivery_order->advance_amount }}"
                            onchange="" id="advance_amount" class="form-control" readonly>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label">Withhold Amount:</label>
                        <input type="number" name="withhold_amount" value="{{ $delivery_order->withhold_amount }}"
                            value="0" onkeyup="change_withhold_amount()" id="withhold_amount" class="form-control"
                            readonly>

                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label">Withhold for RV:</label>
                        <input type="text" name="withhold_for_rv"
                            value="{{ $delivery_order->withheld_receipt_voucher?->unique_no }}" value="0"
                            onkeyup="change_withhold_amount()" id="withhold_amount" class="form-control" readonly>

                    </div>
                </div>
            @endif






        </div>

        <div class="row">
            <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">Reference Number:</label>
                        <input type="text"
                            value="{{ $delivery_order->line_desc }}" class="form-control" readonly>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">Delivery Date:</label>
                        <input type="date" name="delivery_date"
                            value="{{ $delivery_order->salesOrder->delivery_date }}" class="form-control" readonly>
                    </div>
                </div>
        </div>

        <div class="row">
            <div class="col-md-4">
                <div class="form-group">
                    <label class="form-label">Location:</label>
                    <input type="text" class="form-control"
                        value="{{ get_location_name_by_id($delivery_order->location_id) }}" readonly>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label class="form-label">Factory:</label>
                    <select class="form-control select2" multiple disabled>
                        @php
                            $selectedArrivalIds = $delivery_order->arrival_location_id ? explode(',', $delivery_order->arrival_location_id) : [];
                        @endphp
                        @foreach (get_arrivals_by($delivery_order->location_id) as $location)
                            <option value="{{ $location->id }}" @selected(in_array($location->id, $selectedArrivalIds))>
                                {{ $location->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label class="form-label">Section:</label>
                    <select class="form-control select2" multiple disabled>
                        @php
                            $selectedSubArrivalIds = $delivery_order->sub_arrival_location_id ? explode(',', $delivery_order->sub_arrival_location_id) : [];
                            $arrivalIds = $delivery_order->arrival_location_id ? explode(',', $delivery_order->arrival_location_id) : [$delivery_order->arrival_location_id];
                        @endphp
                        @foreach (get_sub_arrivals_by_multiple($arrivalIds) as $location)
                            <option value="{{ $location->id }}" @selected(in_array($location->id, $selectedSubArrivalIds))>
                                {{ $location->name }} ({{ $location->arrivalLocation->name }})
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <!-- Row 2: Sale Orders, Sauda Type -->
        <div class="row">
            {{-- <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">Payment Terms:</label>
                        <select name="payment_term_id" id="payment_term_id" class="form-control select2" disabled>
                            <option value="">Select Payment Term</option>
                            @foreach ($payment_terms as $payment_term)
                                <option value="{{ $payment_term->id }}" @selected($delivery_order->payment_term_id == $payment_term->id)>
                                    {{ $payment_term->desc }}</option>
                            @endforeach
                        </select>
                    </div>
                </div> --}}

            <div class="col-md-6">
                <div class="form-group">
                    <label class="form-label">Remarks:</label>
                    <textarea name="line_desc" id="line_desc" class="form-control" readonly>{{ $delivery_order->line_desc }}</textarea>
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
                            <th>Bag type</th>
                            <th>Packing</th>
                            <th>No of Bags</th>
                            <th>Quantity (kg)</th>
                            <th>Rate per Kg</th>
                            <th>Rate per Mond</th>
                            <th>Amount</th>
                            <th>Brand</th>
                            <th style="display: none">Pack Size</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="salesInquiryBody">
                        @foreach ($delivery_order->delivery_order_data as $index => $data)
                            <tr id="row_{{ $index }}">
                                <td>
                                    <input type="text" name="item_id[]" id="item_id_{{ $index }}"
                                        value="{{ getItem($data->item_id)?->name }}" class="form-control item_id"
                                        step="0.01" min="0" readonly>
                                </td>
                                <td>

                                    <input type="text" name="" id="bag_type_{{ $index }}"
                                        value="{{ bag_type_name($data->bag_type) }}" onkeyup="calc(this)"
                                        class="form-control bag_type" step="0.01" min="0" readonly>

                                    <input type="hidden" name="bag_type[]" id="bag_type_{{ $index }}"
                                        value="{{ $data->bag_type }}" onkeyup="calc(this)"
                                        class="form-control bag_type" step="0.01" min="0">
                                </td>
                                <td>
                                    <input type="text" name="bag_size[]" id="bag_size_{{ $index }}"
                                        value="{{ $data->bag_size }}" onkeyup="calc(this)"
                                        class="form-control bag_size" step="0.01" min="0" readonly>
                                </td>
                                <td>
                                    <input type="text" name="no_of_bags[]" id="no_of_bags_{{ $index }}"
                                        value="{{ $data->no_of_bags }}" onkeyup="calc(this)"
                                        class="form-control no_of_bags" step="0.01" min="0" readonly>

                                    <span style="font-size: 14px;;">Used Quantity:
                                        {{ delivery_order_bags_used($data->so_data_id) }}</span>
                                    <br />
                                    <span style="font-size: 14px;">Balance:
                                        {{ delivery_order_balance($data->so_data_id) }}</span>
                                </td>
                                <td>
                                    <input type="number" name="qty[]" id="qty_{{ $index }}"
                                        onkeyup="calc(this)" value="{{ $data->qty }}" class="form-control qty"
                                        step="0.01" min="0" readonly>
                                </td>
                                 <td>
                                    <input type="number" name="amount[]" id="amount_{{ $index }}"
                                        onkeyup="calc(this)" value="{{ $data->rate }}" class="form-control qty"
                                        step="0.01" min="0" readonly>
                                </td>
                                <td>
                                    <input type="text" name="rate_per_mond[]" id="rate_per_mond_{{ $index }}"
                                        value="{{ $data->salesOrderData->rate_per_mond }}" onkeyup="calc(this)" class="form-control rate_per_mond"
                                        step="0.01" min="0" readonly>
                                </td>
                                <td>
                                    <input type="number" name="rate[]" id="rate_{{ $index }}"
                                        value="{{ $data->rate * $data->qty }}" onkeyup="calc(this)" class="form-control rate"
                                        step="0.01" min="0" readonly>
                                </td>
                                <td>
                                    <input type="text" name="brand_id[]" id="brand_id_{{ $index }}"
                                        class="form-control brand_id"
                                        value="{{ getBrandById($data->brand_id)?->name }}" readonly>
                                </td>
                                <td style="display: none;">
                                    <input type="text" name="pack_size[]" id="pack_size_{{ $index }}"
                                        class="form-control pack_size" value="{{ 0 }}" readonly>
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
<div class="row">
    <div class="col-12">
        <x-approval-status :model="$delivery_order" />
    </div>
</div>

<script>
    salesInquiryRowIndex = 1;

    $(document).ready(function() {
        $('.select2').select2();
    });

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

    function get_inquiries() {
        const customer_id = $("#customer_id").val();
        // get-sale-inquiries-against-customer

        $.ajax({
            url: "{{ route('sales.get-sale-inquiries-against-customer') }}",
            method: "GET",
            data: {
                customer_id: customer_id
            },
            dataType: "json",
            success: function(res) {
                $("#inquiry_id").select2({
                    data: res
                });
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
            url: "{{ route('sales.get.sales-order.getnumber') }}",
            method: "GET",
            data: {
                contract_date: $("#delivery_date").val()
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
</script>
