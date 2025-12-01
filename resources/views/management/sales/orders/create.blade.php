<style>
    html,
    body {
        overflow-x: hidden;
    }
</style>

<form action="{{ route('sales.sale-order.store') }}" method="POST" id="ajaxSubmit" autocomplete="off">
    @csrf

    <input type="hidden" id="listRefresh" value="{{ route('sales.get.sales-order.list') }}" />
    <div class="row form-mar">
        
        <div class="col-md-4">
            <div class="form-group">
                <label class="form-label">Delivery Date:</label>
                <input type="date" name="delivery_date"  onchange="getNumber()" id="delivery_date" class="form-control">
            </div>
        </div>

        <div class="col-md-4">
            <div class="form-group">
                <label class="form-label">Expiry Date:</label>
                <input type="date" name="expiry_date" id="expiry_date" class="form-control">
            </div>
        </div>

        <div class="col-md-4">
            <div class="form-group">
                <label class="form-label">So No:</label>
                <input type="text" name="reference_no" id="reference_no" class="form-control" readonly>
            </div>
        </div>

        <div class="col-md-4">
            <div class="form-group">
                <label class="form-label">Customer:</label>
                <select name="customer_id" id="customer_id" onchange="get_inquiries()" class="form-control select2">
                    <option value="">Select Customer</option>
                    @foreach ($customers ?? [] as $customer)
                        <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="col-md-4">
            <div class="form-group">
                <label class="form-label">Inquiries:</label>
                <select name="inquiry_id" id="inquiry_id" onchange="get_inquiry_data()" class="form-control select2">
                    <option value="">Select Inquiry</option>
                </select>
            </div>
        </div>

        <div class="col-md-4">
            <div class="form-group">
                <label class="form-label">Sauda Type:</label>
                <select name="sauda_type" id="sauda_type" class="form-control select2">
                    <option value="">Select Sauda Type</option>
                    <option value="pohanch">Pohanch</option>
                    <option value="x-mill">X-mill</option>
                </select>
            </div>
        </div>

        <div class="col-md-4">
            <div class="form-group">
                <label class="form-label">Contract Terms:</label>
                <select name="payment_term_id" id="payment_term_id" class="form-control select2">
                    <option value="">Select Contract Term</option>
                    @foreach($payment_terms as $payment_term)
                        <option value="{{ $payment_term->id }}">{{ $payment_term->desc }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="col-md-4">
            <div class="form-group">
                <label class="form-label">Locations:</label>
                <select name="locations[]" id="locations" class="form-control select2" multiple>
                    <option value="">Select Locations</option>
                    @foreach(get_locations() as $location)
                        <option value="{{ $location->id }}">{{ $location->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

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
                            <th>Quantity</th>
                            <th>Rate</th>
                            <th>Amount</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="salesInquiryBody">
                        <tr id="row_0">
                            <td>
                                <select name="item_id[]" id="item_id_0" class="form-control select2">
                                    <option value="">Select Item</option>
                                    @foreach ($items ?? [] as $item)
                                        <option value="{{ $item->id }}">{{ $item->name }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td>
                                <input type="number" name="qty[]" id="qty_0" onkeyup="calc(this)" class="form-control qty" step="0.01"
                                    min="0">
                            </td>
                            <td>
                                <input type="number" name="rate[]" id="rate_0" onkeyup="calc(this)" class="form-control rate" step="0.01"
                                    min="0">
                            </td>
                            <td>
                                <input type="text" name="amount[]" id="amount_0" class="form-control amount" readonly>
                            </td>
                            <td>
                                <button type="button" disabled class="btn btn-danger btn-sm removeRowBtn"
                                    style="width:60px;">
                                    <i class="fa fa-trash"></i>
                                </button>
                            </td>
                        </tr>
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
    let salesInquiryRowIndex = 1;

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
