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

<form action="{{ route('store.purchase-return.store') }}" method="POST" id="ajaxSubmit" autocomplete="off">
    @csrf

    <input type="hidden" id="listRefresh" value="{{ route('store.get.purchase-return') }}" />

    <div class="row form-mar">
        <div class="col-md-12">
            <!-- Row 1: Supplier, Purchase Bills, PR No -->
            <div class="row" style="margin-top: 10px">
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label">Supplier:<span class="text-danger">*</span></label>
                        <select name="supplier_id" id="supplier_id" onchange="get_purchase_bills()"
                            class="form-control select2">
                            <option value="">Select Supplier</option>
                            @forelse (get_supplier() as $supplier)
                                <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                            @empty
                                <option value="">No suppliers available</option>
                            @endforelse
                        </select>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label">Purchase Bills:<span class="text-danger">*</span></label>
                        <select name="purchase_bill_ids[]" id="purchase_bill_ids" onchange="get_items(this)"
                            class="form-control select2" multiple>
                            <option value="">Select Purchase Bills</option>
                        </select>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label">PR No:<span class="text-danger">*</span></label>
                        <input type="text" name="pr_no" id="pr_no" class="form-control" readonly>
                    </div>
                </div>
            </div>

            <!-- Row 2: Date, Reference Number, Company Location -->
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label">Date:<span class="text-danger">*</span></label>
                        <input
                            type="date"
                            name="date"
                            onchange="getNumber()"
                            id="date"
                            class="form-control"
                            value="{{ date('Y-m-d') }}"
                        >
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label">Reference Number:</label>
                        <input type="text" name="reference_no" id="reference_no" class="form-control" placeholder="Enter reference number">
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label">Company Location:<span class="text-danger">*</span></label>
                        <select name="company_location_id" id="company_location_id" class="form-control select2">
                            <option value="">Select Company Location</option>
                            @foreach (get_locations() ?? [] as $location)
                                <option value="{{ $location->id }}">{{ $location->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <!-- Row 3: Remarks -->
            <div class="row">
                <div class="col-md-12">
                    <div class="form-group">
                        <label class="form-label">Remarks:</label>
                        <textarea name="remarks" id="remarks" class="form-control" rows="2" placeholder="Enter remarks"></textarea>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row form-mar">
        <div class="col-12 text-right mb-2">
            <button type="button" style="float: right" class="btn btn-sm btn-primary" onclick="addRow()"
                id="addRowBtn">
                <i class="fa fa-plus"></i>&nbsp; Add New Item
            </button>
        </div>

        <div class="col-md-12">
            <div class="table-responsive" style="overflow-x: auto; white-space: nowrap;">
                <table class="table table-bordered" id="purchaseBillTable" style="min-width:2000px;">
                    <thead>
                        <tr>

                            <th>Item</th>
                            <th>Qty</th>
                            <th>Rate</th>
                            <th>Gross Amount</th>
                            <th>Disc %</th>
                            <th>Disc Amount</th>
                            <th>Deduction</th>
                            <th>Amount</th>
                            <th>Tax %</th>
                            <th>Tax Amount</th>
                            <th>Net Amount</th>
                            <th>Description</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="pbTableBody">

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
    purchaseReturnRowIndex = 1;

    $(document).ready(function() {
        $('.select2').select2();
        getNumber();
        $("#addRowBtn").prop("disabled", false);
    });

    // Legacy function for backward compatibility
    function calc(el) {
        calculateRow(el);
    }

    function get_purchase_bills() {
        const supplier_id = $("#supplier_id").val();

        if (!supplier_id) {
            $("#purchase_bill_ids").empty();
            $("#purchase_bill_ids").append(`<option value=''>Select Purchase Bills</option>`);
            $("#purchase_bill_ids").select2();
            $("#pbTableBody").empty();
            $("#addRowBtn").prop("disabled", true);
            return;
        }

        $.ajax({
            url: "{{ route('store.purchase-return.get-bills-by-supplier') }}",
            method: "GET",
            data: {
                supplier_id: supplier_id
            },
            dataType: "json",
            headers: {
                'X-CSRF-TOKEN': "{{ csrf_token() }}",
                'X-Requested-With': 'XMLHttpRequest'
            },
            success: function(res) {
                console.log(res);

                $("#purchase_bill_ids").empty();
                $("#purchase_bill_ids").append(`<option value=''>Select Purchase Bills</option>`);

                res.forEach(bill => {
                    $("#purchase_bill_ids").append(`
                        <option value="${bill.id}" data-bill-date="${bill.bill_date}">
                            ${bill.text}
                        </option>
                    `);
                });

                // Reinitialize select2 to show new options
                $("#purchase_bill_ids").select2('destroy').select2();

                $("#pbTableBody").empty();
            },
            error: function(error) {
                console.error("Error loading purchase bills:", error);
            }
        });
    }

    function get_items(el) {
        const purchase_bill_ids = $(el).val();

        if (!purchase_bill_ids || purchase_bill_ids.length === 0) {
            $("#pbTableBody").empty();
            $("#addRowBtn").prop("disabled", true);
            return;
        }

        // Enable add row button
        $("#addRowBtn").prop("disabled", false);

        $.ajax({
            url: "{{ route('store.purchase-return.get-items') }}",
            method: "POST",
            data: {
                purchase_bill_ids: purchase_bill_ids,
                _token: '{{ csrf_token() }}'
            },
            dataType: "html",
            success: function(res) {
                $("#pbTableBody").empty();
                $("#pbTableBody").html(res);
                console.log(res);
            },
            error: function(error) {
                console.error("Error:", error);
            }
        });
    }

    function addRow() {
        let index = purchaseReturnRowIndex++;
        let row = `
        <tr id="row_${index}">
            <td style="min-width: 200px;">
                <select name="item_id[]" id="item_id_${index}" class="form-control select2">
                    <option value="">Select Item</option>
                    @foreach (\App\Models\Product::all() ?? [] as $item)
                        <option value="{{ $item->id }}">{{ $item->name }}</option>
                    @endforeach
                </select>
                <input type="hidden" name="bill_data_id[]" value="">
            </td>
            <td style="min-width: 80px; text-align: center;">
                <input type="number" name="quantity[]" id="quantity_${index}" class="form-control quantity" step="0.01" min="0" readonly style="text-align: center;">
            </td>
            <td style="min-width: 80px; text-align: center;">
                <input type="number" name="rate[]" id="rate_${index}" onkeyup="calculateRow(this)" class="form-control rate" step="0.01" min="0" style="text-align: center;">
            </td>
            <td style="min-width: 110px; text-align: center;">
                <input type="number" name="gross_amount[]" id="gross_amount_${index}" class="form-control gross_amount" readonly style="text-align: center;">
            </td>
            <td style="min-width: 80px; text-align: center;">
                <input type="number" name="tax_percent[]" id="tax_percent_${index}" onkeyup="calculateRow(this)" class="form-control tax_percent" step="0.01" min="0" value="0" style="text-align: center;">
            </td>
            <td style="min-width: 110px; text-align: center;">
                <input type="number" name="tax_amount[]" id="tax_amount_${index}" class="form-control tax_amount" readonly style="text-align: center;">
            </td>
            <td style="min-width: 80px; text-align: center;">
                <input type="number" name="discount_percent[]" id="discount_percent_${index}" onkeyup="calculateRow(this)" class="form-control discount_percent" step="0.01" min="0" value="0" readonly style="text-align: center;">
            </td>
            <td style="min-width: 110px; text-align: center;">
                <input type="number" name="discount_amount[]" id="discount_amount_${index}" class="form-control discount_amount" readonly style="text-align: center;">
            </td>
            <td style="min-width: 110px; text-align: center;">
                <input type="number" name="net_amount[]" id="net_amount_${index}" class="form-control net_amount" readonly style="text-align: center;">
            </td>
            <td style="min-width: 150px;">
                <input type="text" name="description[]" id="description_${index}" class="form-control description">
            </td>
            <td style="min-width: 80px;">
                <button type="button" class="btn btn-danger btn-sm removeRowBtn" onclick="removeRow(${index})" style="width:60px;">
                    <i class="fa fa-trash"></i>
                </button>
            </td>
        </tr>
    `;
        $('#pbTableBody').append(row);
        $(`#item_id_${index}`).select2();
    }

    function removeRow(index) {
        $('#row_' + index).remove();
    }

    function round(num, decimals = 2) {
        return Number(Math.round(num + "e" + decimals) + "e-" + decimals);
    }

      function calculateRow(el) {
       const row = $(el).closest("tr");

        // Get input elements
        const quantityInput = row.find(".quantity");
        const rateInput = row.find(".rate");
        const grossAmountInput = row.find(".gross_amount");
        const taxPercentInput = row.find(".tax_percent");
        const taxAmountInput = row.find(".tax_amount");
        const discountPercentInput = row.find(".discount_percent");
        const discountAmountInput = row.find(".discount_amount");
        const deductionInput = row.find(".deduction");
        const amountInput = row.find(".amount");
        const netAmountInput = row.find(".net_amount");

        // Get values
        const quantity = parseFloat(quantityInput.val()) || 0;
        const rate = parseFloat(rateInput.val()) || 0;
        const taxPercent = parseFloat(taxPercentInput.val()) || 0;
        const discountPercent = parseFloat(discountPercentInput.val()) || 0;
        const deduction = parseFloat(deductionInput.val()) || 0;

        // Calculate Gross Amount = Quantity * Rate
        const grossAmount = quantity * rate;
        grossAmountInput.val(round(grossAmount));
        

        // Calculate Discount Amount = (Discount % / 100) * Gross Amount
        const discountAmount = (discountPercent / 100) * grossAmount;
        discountAmountInput.val(round(discountAmount));
        
        // Calculate Amount after discount and deduction
        const amount = grossAmount - discountAmount - deduction;
        
        // Calculate Tax Amount = (Tax % / 100) * Amount
        const taxAmount = (taxPercent / 100) * amount;
        taxAmountInput.val(round(taxAmount));
        amountInput.val(round(amount));
        
        // Calculate Net Amount = Amount + Tax Amount
        const netAmount = taxAmount;
        netAmountInput.val(round(taxAmount + amount));
    }

    function getNumber() {
        $.ajax({
            url: "{{ route('store.purchase-return.getNumber') }}",
            method: "GET",
            data: {
                date: $("#date").val()
            },
            dataType: "json",
            success: function(res) {
                $("#pr_no").val(res.pr_no)
            },
            error: function(error) {
                $('.loader-container').hide();
                console.error("Error:", error);
            }
        });
    }

    function validateBalance(el) {
        const row = $(el).closest("tr");
        const maxBalance = parseFloat(row.find(".max_balance").val()) || 0;
        const quantity = parseFloat($(el).val()) || 0;

        if (quantity > maxBalance) {
            $(el).val(maxBalance);
            toastr.warning(`Cannot exceed available balance of ${maxBalance}`);
            calculateRow(el);
        }

        if (quantity < 0) {
            $(el).val(0);
            calculateRow(el);
        }
    }
</script>
