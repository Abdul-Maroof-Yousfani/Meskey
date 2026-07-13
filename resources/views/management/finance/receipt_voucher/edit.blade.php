@extends('management.layouts.master')
@section('title')
    Edit Receipt Voucher
@endsection
@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">Edit Receipt Voucher</h4>
                        <a href="{{ route('receipt-voucher.index') }}" class="btn btn-sm btn-primary">Back</a>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('receipt-voucher.update', $receiptVoucher->id) }}" method="POST">
                                @method('PUT')
                            @csrf
                            {{-- <input type="hidden" id="redirectUrl" value="{{ route('receipt-voucher.index') }}"> --}}
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="voucher_type">Voucher Type</label>
                                        <select name="voucher_type" id="voucher_type" class="form-control select2" required>
                                            <option value="">Select Type</option>
                                            <option value="bank_payment_voucher" {{ $receiptVoucher->voucher_type == 'bank_payment_voucher' ? 'selected' : '' }}>Bank Receipt Voucher</option>
                                            <option value="cash_payment_voucher" {{ $receiptVoucher->voucher_type == 'cash_payment_voucher' ? 'selected' : '' }}>Cash Receipt Voucher</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="rv_date">Date</label>
                                        <input type="date" name="rv_date" id="rv_date" class="form-control" value="{{ $receiptVoucher->rv_date ? \Carbon\Carbon::parse($receiptVoucher->rv_date)->format('Y-m-d') : date('Y-m-d') }}" required>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="unique_no">RV Number</label>
                                        <input type="text" name="unique_no" id="unique_no" class="form-control" value="{{ $receiptVoucher->unique_no }}" readonly>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="account_id">Account</label>
                                        <select name="account_id" id="account_id" class="form-control select2" required>
                                            <option value="">Select Account</option>
                                            @foreach ($accounts ?? [] as $acc)
                                                <option value="{{ $acc->id }}" {{ $receiptVoucher->account_id == $acc->id ? 'selected' : '' }}>{{ $acc->name }} ({{ $acc->hierarchy_path ?? $acc->unique_no }})</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="ref_bill_no">Receipt Ref No</label>
                                        <input type="text" name="ref_bill_no" id="ref_bill_no" class="form-control" value="{{ $receiptVoucher->ref_bill_no }}" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="bill_date">Receipt Date</label>
                                        <input type="date" name="bill_date" id="bill_date" class="form-control" value="{{ $receiptVoucher->bill_date ? \Carbon\Carbon::parse($receiptVoucher->bill_date)->format('Y-m-d') : '' }}" required>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="customer_id">Customer Account</label>
                                        <select name="customer_id" id="customer_id" class="form-control select2" onchange="select_customer()">
                                            <option value="">Select Customer</option>
                                            @foreach ($customers as $customer)
                                                <option value="{{ $customer->id }}" {{ $receiptVoucher->customer_id == $customer->id ? 'selected' : '' }}>{{ $customer->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label id="reference_label">{{ $isAdvance ? 'Sale Orders (approved, receiving pending)' : 'Invoices (approved, receiving pending)' }}</label>
                                        <select id="reference_ids" class="form-control select2" multiple style="width: 100%;">
                                            @foreach ($salesInvoices as $inv)
                                                <option value="{{ $inv->id }}" data-type="sales_invoice">
                                                    {{ $inv->si_no ?? 'INV-'.$inv->id }} - {{ optional($inv->customer)->name }} {{ $inv->reference_number ? ' | Ref: '.$inv->reference_number : '' }}
                                                </option>
                                            @endforeach
                                            @foreach ($saleOrders as $so)
                                                <option value="{{ $so->id }}" data-type="sale_order" class="d-none">
                                                    {{ $so->so_reference_no ?? $so->reference_no ?? ($so->so_no ?? 'SO-'.$so->id) }} - {{ optional($so->customer)->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <small class="text-muted d-block mt-1">Toggle "Advance" to switch between sale orders and invoices.</small>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" id="is_advance" onchange="select_customer()" checked>
                                        <label class="form-check-label" for="is_advance">Advance</label>
                                    </div>
                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="checkbox" name="allow_excess" id="allow_excess" value="1" {{ \App\Models\CustomerAdvance::where('voucher_no', $receiptVoucher->unique_no)->where('source_type', 'excess_payment')->exists() ? 'checked' : '' }}>
                                        <label class="form-check-label font-weight-bold text-primary" for="allow_excess">
                                            Allow Excess Amount (Advances)
                                        </label>
                                        <div class="mt-2 p-3 bg-soft-primary border-left border-primary small text-dark">
                                            <b>Note:</b> This checkbox is used to allow flexibility in payment handling when receiving amounts against a Sales Order. Normally, the system restricts the received amount to the exact outstanding balance of the document, preventing any overpayment. However, when this checkbox is enabled, the user is allowed to receive an excess amount beyond the payable limit. Instead of directly assigning this extra amount to any specific Sales Order, the system creates a virtual Receipt Voucher Item for the excess value, which remains unlinked to any document at the time of creation. This unallocated amount is stored as a customer credit and can later be explicitly allocated to any Sales Order or invoice when required. In this way, it provides a controlled mechanism to manage advance payments, overpayments, or customer credit balances within the system.
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <label class="mb-0">Selected References</label>
                                            <!-- <button type="button" class="btn btn-sm btn-info" id="add-advance-btn" onclick="addAdvanceRow()">
                                                <i class="fa fa-plus"></i> Add Advance
                                            </button> -->
                                        </div>
                                        <div style="overflow: visible;">
                                            <table class="table table-bordered" id="referencesTable" style="overflow: visible;">
                                                <thead>
                                                    <tr>
                                                        <th width="5%"><input type="checkbox" id="select_all"></th>
                                                        <th>Type</th>
                                                        <th>Document No</th>
                                                        <th>Date</th>
                                                        <th>Customer</th>
                                                        <th width="12%">Amount</th>
                                                        <th width="12%">Tax</th>
                                                        <th width="12%">Tax Amount</th>
                                                        <th width="12%">Net Amount</th>
                                                        <th width="18%">Line Desc</th>
                                                        <th width="5%">Action</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="rv-data">
                                                    <tr>
                                                        <td colspan="11" class="text-center text-muted">Select references to load details.</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label>Selected Documents</label>
                                        <div class="selected-docs-container bg-light p-3">
                                            <p class="text-muted">No documents selected yet</p>
                                            <ul class="list-group selected-docs-list" style="display: none;"></ul>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label for="remarks">Remarks</label>
                                        <textarea name="remarks" id="remarks" class="form-control" rows="3">{{ $receiptVoucher->remarks }}</textarea>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group text-right">
                                <button type="submit" class="btn btn-primary">Edit Receipt Voucher</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
<script>
    let advanceCount = 0;
    let nextAdvNo = null;

    function addAdvanceRow() {
        const customerId = $("#customer_id").val();
        if (!customerId) {
            Swal.fire('Warning', 'Please select a customer first.', 'warning');
            return;
        }

        if (nextAdvNo === null) {
            $.ajax({
                url: '{{ route("receipt-voucher.generate-advance-number") }}',
                method: 'GET',
                async: false, // Using sync for simplicity to ensure nextAdvNo is set before proceeding
                success: function(resp) {
                    if (resp.success) {
                        nextAdvNo = resp.next_number;
                    }
                }
            });
        } else {
            // Increment local number
            let numPart = parseInt(nextAdvNo.replace('ADV-', '')) + 1;
            nextAdvNo = 'ADV-' + String(numPart).padStart(3, '0');
        }

        const customerName = $("#customer_id option:selected").text();
        const date = new Date().toISOString().split('T')[0];
        advanceCount++;
        const advNo = nextAdvNo;
        const idx = `adv_${advanceCount}`;

        const rowHtml = `
            <tr class="advance-row reference-main-row" id="reference-row-${idx}">
                <td class="text-center">
                    <input type="checkbox" class="row-select" checked checked data-row="${idx}">
                    <input type="hidden" name="items[${idx}][reference_id]" value="0">
                    <input type="hidden" name="items[${idx}][reference_type]" value="advance">
                    <input type="hidden" name="items[${idx}][adv_no]" value="${advNo}">
                    <input type="hidden" class="hidden-amount" name="items[${idx}][amount]" value="0">
                </td>
                <td>Advance</td>
                <td>${advNo}</td>
                <td>${date}</td>
                <td>${customerName}</td>
                <td>
                    <input type="number" step="0.01" readonly class="form-control amount-input" name="items[${idx}][amount_display]" value="0.00" data-balance="999999999">
                </td>
                <td>
                    <select class="form-control tax-select" name="items[${idx}][tax_id]">
                        <option value="">No Tax</option>
                        @foreach($taxes as $tax)
                            <option value="{{ $tax->id }}" data-percent="{{ $tax->percentage }}">{{ $tax->name }} ({{ $tax->percentage }}%)</option>
                        @endforeach
                    </select>
                </td>
                <td>
                    <input type="number" step="0.01" readonly class="form-control tax-amount" name="items[${idx}][tax_amount]" value="0.00">
                </td>
                <td>
                    <input type="number" step="0.01" readonly class="form-control net-amount" name="items[${idx}][net_amount]" value="0.00">
                </td>
                <td>
                    <input type="text" class="form-control line-desc" name="items[${idx}][line_desc]" placeholder="Line description">
                </td>
                <td class="text-center d-flex justify-content-center align-items-center">
                    <button type="button" class="btn btn-xs btn-outline-info toggle-bank-subrow mr-1" data-row-idx="${idx}" title="Toggle Bank Details">
                        <i class="fa fa-chevron-down"></i> Banks
                    </button>
                    <button type="button" class="btn btn-xs btn-danger remove-advance-row">
                        <i class="fa fa-trash"></i>
                    </button>
                </td>
            </tr>
            <tr class="bank-details-subrow" id="bank-subrow-${idx}" style="display: none; background-color: #f9fbfd;">
                <td></td>
                <td colspan="10">
                    <div class="card my-2 border-info shadow-sm" style="border: 1px solid #17a2b8 !important;">
                        <div class="card-header py-2 d-flex justify-content-between align-items-center" style="background-color: #eef7fc; border-bottom: 1px solid #17a2b8;">
                            <h6 class="mb-0 text-info font-weight-bold" style="font-size: 0.9rem;">
                                <i class="fa fa-university mr-1"></i> Bank/Account Details for ${advNo}
                            </h6>
                            <button type="button" class="btn btn-xs btn-success add-nested-bank-btn" data-row-idx="${idx}" style="padding: .2rem .4rem; font-size: .75rem;">
                                <i class="fa fa-plus"></i> Add Account
                            </button>
                        </div>
                        <div class="card-body p-2" style="background-color: #ffffff;">
                            <table class="table table-bordered table-sm mb-0">
                                <thead>
                                    <tr class="bg-light">
                                        <th>Account</th>
                                        <th width="20%">Amount</th>
                                        <th width="30%">Cheque No</th>
                                        <th width="8%">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="nested-bank-data" id="nested-bank-data-${idx}">
                                </tbody>
                            </table>
                        </div>
                    </div>
                </td>
            </tr>
        `;

        const tbody = $("#rv-data");
        if (tbody.find('td[colspan]').length) {
            tbody.empty();
        }
        tbody.append(rowHtml);
        
        // If a voucher type is already selected, automatically add a bank row
        if ($('#voucher_type').val() !== '') {
            $(`#bank-subrow-${idx}`).show();
            $(`.toggle-bank-subrow[data-row-idx="${idx}"]`).find('i').removeClass('fa-chevron-down').addClass('fa-chevron-up');
            addNestedBankDetailRow(idx);
        }

        // Final check for UI update
        if (typeof updateSelectedDocsList === "function") {
            updateSelectedDocsList();
        }
    }

    // ==================== CORE FUNCTION: Update Selected Documents List with TOTAL ====================
    function updateSelectedDocsList() {
        const selected = [];
        let total = 0;
        const referencesTableBody = $('#referencesTable tbody');
        const listContainer = $('.selected-docs-list');
        const emptyMessage = $('.selected-docs-container p');

        referencesTableBody.find('tr').each(function () {
            const row = $(this);
            const checkbox = row.find('.row-select');
            if (checkbox.length && checkbox.is(':checked')) {
                const type = row.find('td').eq(1).text().trim() || '';
                const number = row.find('td').eq(2).text().trim() || '';
                const date = row.find('td').eq(3).text().trim() || '';
                const customer = row.find('td').eq(4).text().trim() || '';
                const netAmount = parseFloat(row.find('.net-amount').val()) || 0;

                selected.push({
                    type,
                    number,
                    date,
                    customer,
                    amount: netAmount,
                    idx: checkbox.data('row')
                });
                total += netAmount;
            }
        });

        listContainer.empty();

        if (!selected.length) {
            emptyMessage.show();
            listContainer.hide();
            $('#selected-total-amount').hide();
            return;
        }

        emptyMessage.hide();
        listContainer.show();

        selected.forEach(function (item) {
            listContainer.append(`
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <div>
                        <strong>${item.number}</strong> <span class="text-muted">(${item.type})</span>
                        <div class="small text-muted">${item.date} • ${item.customer}</div>
                    </div>
                    <span class="badge badge-primary badge-pill">${item.amount.toFixed(2)}</span>
                </li>
            `);
        });

        $('#selected-total-row').remove();
        listContainer.append(`
            <li id="selected-total-row" class="list-group-item active d-flex justify-content-between align-items-center font-weight-bold" style="background-color: #e9ecef; border-top: 3px double #ccc;">
                <div class="text-dark">
                    <strong>Total Amount</strong>
                </div>
                <span class="badge badge-dark badge-pill" id="total_amount" style="font-size: 1.1em;">
                    ${total.toFixed(2)}
                </span>
            </li>
        `);

        if ($('#total_receipt_amount').length) {
            $('#total_receipt_amount').val(total.toFixed(2));
        }
        if ($('#display_total_amount').length) {
            $('#display_total_amount').text(total.toFixed(2));
        }
    }

    let customerAdvancesOptions = '<option value="">Select Advance</option>';

    // This function can stay outside if it's called from elsewhere (e.g., onchange of customer)
    function select_customer() {
        // Reset advances when customer changes
        advanceCount = 0;
        $("#rv-data").html('<tr><td colspan="12" class="text-center text-muted">Select references to load details.</td></tr>');
        if (typeof updateSelectedDocsList === "function") {
            updateSelectedDocsList();
        }

        $.ajax({
            url: '{{ route("receipt.voucher.get-documents") }}',
            data: {
                customer_id: $("#customer_id").val(),
                is_advance: $("#is_advance").is(":checked")
            },
            success: function (response) {
                console.log(response);
                $("#reference_ids").empty();
                $("#reference_ids").select2({ data: response });
            },
            error: function (xhr, status, error) {
                console.error(error);
                console.error(xhr.responseText);
            },
        });

        // Fetch customer advances
        $.ajax({
            url: '{{ route("receipt-voucher.get-customer-advances") }}',
            type: 'POST',
            data: {
                customer_id: $("#customer_id").val(),
                _token: '{{ csrf_token() }}'
            },
            success: function (response) {
                customerAdvancesOptions = '<option value="">Select Advance</option>';
                response.forEach(function(adv) {
                    customerAdvancesOptions += `<option value="${adv.id}" data-max="${adv.remaining_amount}">${adv.text}</option>`;
                });
            }
        });
    }

    let bankDetailCount = 0;
    const allAccounts = @json($accounts);
    
    function getFilteredAccounts() {
        const voucherType = $('#voucher_type').val();
        if (voucherType === 'bank_payment_voucher') {
            return allAccounts.filter(acc => acc.hierarchy_path && acc.hierarchy_path.startsWith('1-1'));
        } else if (voucherType === 'cash_payment_voucher') {
            return allAccounts.filter(acc => acc.hierarchy_path && acc.hierarchy_path.startsWith('1-4'));
        }
        return [];
    }

    function addNestedBankDetailRow(rowIdx) {
        const filteredAccounts = getFilteredAccounts();
        if ($('#voucher_type').val() === '') {
            Swal.fire('Warning', 'Please select a Voucher Type first.', 'warning');
            return;
        }

        bankDetailCount++;
        const bankIdx = bankDetailCount;
        
        let accountOptions = '<option value="">Select Account</option>';
        filteredAccounts.forEach(function(acc) {
            accountOptions += `<option value="${acc.id}">${acc.name} (${acc.hierarchy_path ?? ''})</option>`;
        });

        const rowHtml = `
            <tr class="nested-bank-row">
                <td>
                    <select name="bank_details[${bankIdx}][account_id]" class="form-control select2-nested-bank account-select" style="width: 100%;">
                        ${accountOptions}
                    </select>
                </td>
                <td>
                    <input type="number" step="0.01" name="bank_details[${bankIdx}][amount]" class="form-control bank-amount" required>
                </td>
                <td>
                    <input type="text" name="bank_details[${bankIdx}][cheque_no]" class="form-control" placeholder="Cheque No">
                </td>
                <td class="text-center">
                    <button type="button" class="btn btn-xs btn-danger remove-nested-bank-row" style="padding: .2rem .4rem; font-size: .75rem;">
                        <i class="fa fa-trash"></i>
                    </button>
                </td>
            </tr>
        `;
        
        const container = $(`#nested-bank-data-${rowIdx}`);
        container.append(rowHtml);
        
        // Initialize select2 on the newly added selects
        container.find('.select2-nested-bank').select2({
            width: '100%'
        });
    }

    function addNestedAdvanceRow(rowIdx) {
        let bankIdx = Date.now() + Math.floor(Math.random() * 1000);

        const rowHtml = `
            <tr class="nested-bank-row advance-row">
                <td>
                    <select name="bank_details[${bankIdx}][customer_advance_id]" class="form-control select2-nested-bank advance-select" style="width: 100%;">
                        ${customerAdvancesOptions}
                    </select>
                </td>
                <td>
                    <input type="number" step="0.01" name="bank_details[${bankIdx}][amount]" class="form-control bank-amount" required>
                </td>
                <td>
                    <span class="text-muted">N/A</span>
                </td>
                <td class="text-center">
                    <button type="button" class="btn btn-xs btn-danger remove-nested-bank-row" style="padding: .2rem .4rem; font-size: .75rem;">
                        <i class="fa fa-trash"></i>
                    </button>
                </td>
            </tr>
        `;
        
        const container = $(`#nested-bank-data-${rowIdx}`);
        container.append(rowHtml);
        
        // Initialize select2 on the newly added selects
        container.find('.select2-nested-bank').select2({
            width: '100%'
        });
    }

    $(document).ready(function () {
        // Toggle subrow bank details
        $(document).on('click', '.toggle-bank-subrow', function() {
            const rowIdx = $(this).data('row-idx');
            const subrow = $(`#bank-subrow-${rowIdx}`);
            subrow.toggle();
            
            const icon = $(this).find('i');
            if (subrow.is(':visible')) {
                icon.removeClass('fa-chevron-down').addClass('fa-chevron-up');
                // If empty and voucher type is selected, auto-add a bank row
                if ($(`#nested-bank-data-${rowIdx}`).children().length === 0 && $('#voucher_type').val() !== '') {
                    addNestedBankDetailRow(rowIdx);
                }
            } else {
                icon.removeClass('fa-chevron-up').addClass('fa-chevron-down');
            }
        });

        // Add nested bank row click
        $(document).on('click', '.add-nested-bank-btn', function() {
            const rowIdx = $(this).data('row-idx');
            addNestedBankDetailRow(rowIdx);
        });

        // Add nested advance row click
        $(document).on('click', '.add-nested-advance-btn', function() {
            const rowIdx = $(this).data('row-idx');
            addNestedAdvanceRow(rowIdx);
        });

        // Remove nested bank row click
        $(document).on('click', '.remove-nested-bank-row', function() {
            const subrow = $(this).closest('tr.bank-details-subrow');
            $(this).closest('tr').remove();
            
            // Re-calculate the sum for this subrow
            const rowIdx = subrow.attr('id').replace('bank-subrow-', '');
            let sum = 0;
            subrow.find('.bank-amount').each(function() {
                sum += parseFloat($(this).val()) || 0;
            });
            
            const mainRow = $(`#reference-row-${rowIdx}`);
            
            validateBankTotal();
        });

        // Clear and add initial row when voucher type changes
        $('#voucher_type').on('change', function() {
            $('.nested-bank-data').empty();
            if ($(this).val() !== '') {
                const firstChecked = $('#referencesTable tbody').find('.row-select:checked').first();
                if (firstChecked.length) {
                    const rowIdx = firstChecked.data('row');
                    $(`#bank-subrow-${rowIdx}`).show();
                    $(`.toggle-bank-subrow[data-row-idx="${rowIdx}"]`).find('i').removeClass('fa-chevron-down').addClass('fa-chevron-up');
                    addNestedBankDetailRow(rowIdx);
                }
            }
        });
        const referenceSelect = $('#reference_ids');
        const referenceLabel = $('#reference_label');
        const referencesTableBody = $('#referencesTable tbody');
        const selectAll = $('#select_all');
        const listContainer = $('.selected-docs-list');
        const emptyMessage = $('.selected-docs-container p');
        const taxes = @json($taxes ?? []);


        // ==================== Toggle Reference Options Based on Advance Checkbox ====================
        function toggleReferenceOptions() {
            const isAdvance = $('#is_advance').is(':checked');
            referenceSelect.find('option').each(function () {
                const type = $(this).data('type');
                if (isAdvance && type === 'sale_order') {
                    $(this).removeClass('d-none');
                } else if (!isAdvance && type === 'sales_invoice') {
                    $(this).removeClass('d-none');
                } else {
                    $(this).addClass('d-none').prop('selected', false);
                }
            });

            referenceSelect.trigger('change.select2');
            referenceLabel.text(isAdvance ? 'Sale Orders (approved)' : 'Invoices (approved, receiving pending)');
            referencesTableBody.html('<tr><td colspan="12" class="text-center text-muted">Select references to load details.</td></tr>');
            selectAll.prop('checked', $('#referencesTable tbody').find('.row-select:not(:checked)').length === 0 && $('#referencesTable tbody').find('.row-select').length > 0);
            updateSelectedDocsList();
            select_customer();
        }

        // ==================== Load RV Number or Accounts ====================
        function loadRvNumber() {
            if (!$('#voucher_type').val()) return;

            $.post(`{{ route('receipt-voucher.generate-rv-number') }}`, {
                _token: '{{ csrf_token() }}',
                voucher_type: $('#voucher_type').val(),
                rv_date: $('#rv_date').val() || null
            }, function (resp) {
                if (resp.success) {
                    if ($('#rv_date').val()) {
                        $('#unique_no').val(resp.rv_number);
                    } else {
                        const $accountSelect = $('#account_id');
                        $accountSelect.empty().append('<option value="">Select Account</option>
                                            @foreach ($accounts ?? [] as $acc)
                                                <option value="{{ $acc->id }}" {{ $receiptVoucher->account_id == $acc->id ? 'selected' : '' }}>{{ $acc->name }} ({{ $acc->hierarchy_path ?? $acc->unique_no }})</option>
                                            @endforeach');
                        resp.accounts.forEach(function (acc) {
                            $accountSelect.append(`<option value="${acc.id}">${acc.name} (${acc.hierarchy_path ?? acc.unique_no ?? ''})</option>`);
                        });
                        $accountSelect.trigger('change');
                    }
                }
            });
        }

        // ==================== Recalculate Row Amounts (Amount + Tax) ====================
        function recalcRow(row) {
            const amountInput = row.find('.amount-input');
            const taxSelect = row.find('.tax-select');
            const taxAmountInput = row.find('.tax-amount');
            const netAmountInput = row.find('.net-amount');
            const hiddenAmount = row.find('.hidden-amount');

            const amount = parseFloat(amountInput.val()) || 0;
            const taxPercent = parseFloat(taxSelect.find('option:selected').data('percent')) || 0;
            const taxAmount = amount * taxPercent / 100;
            const netAmount = amount + taxAmount;

            taxAmountInput.val(taxAmount.toFixed(2));
            netAmountInput.val(netAmount.toFixed(2));
            hiddenAmount.val(amount.toFixed(2));

            updateSelectedDocsList();
        }

        // ==================== Bind Events to Dynamic Rows ====================
        function bindRowEvents() {
            // Using delegation now, so this is mostly for initial rows if needed
            // but delegation on #referencesTable is better.
        }

        // Event delegation for calculations
        $('#referencesTable').on('input', '.amount-input', function () {
            recalcRow($(this).closest('tr'));
        });

        $('#referencesTable').on('change', '.tax-select', function () {
            recalcRow($(this).closest('tr'));
        });

        // ==================== Build Table Rows from Selected References ====================
        
        function buildRows(items) {
            referencesTableBody.empty();

            if (!items.length) {
                referencesTableBody.html('<tr><td colspan="12" class="text-center text-muted">No data found for selected references.</td></tr>');
                selectAll.prop('checked', $('#referencesTable tbody').find('.row-select:not(:checked)').length === 0 && $('#referencesTable tbody').find('.row-select').length > 0);
                updateSelectedDocsList();
                return;
            }

            referencesTableBody.html(`
                <tr>
                    <td colspan="12" class="text-center">
                        <div class="d-flex justify-content-center align-items-center">
                            <div class="spinner-border spinner-border-sm mr-2" role="status"></div>
                            Loading items...
                        </div>
                    </td>
                </tr>
            `);

            console.log(items);
            $.ajax({
                url: "{{ route('receipt-voucher.get.rows') }}",
                type: "POST",
                data: {
                    _token: '{{ csrf_token() }}',
                    items: JSON.stringify(items)
                },
                success: function (response) {
                    $("#rv-data").html(response);

                    // Automatically add bank row to first selected reference if voucher type is selected
                    if ($('#voucher_type').val() !== '') {
                        const firstChecked = $('#referencesTable tbody').find('.row-select:checked').first();
                        if (firstChecked.length) {
                            const rowIdx = firstChecked.data('row');
                            $(`#bank-subrow-${rowIdx}`).show();
                            $(`.toggle-bank-subrow[data-row-idx="${rowIdx}"]`).find('i').removeClass('fa-chevron-down').addClass('fa-chevron-up');
                            addNestedBankDetailRow(rowIdx);
                        }
                    }

                    // Important: Re-bind events after injecting new HTML
                    bindRowEvents();

                    // Now update the selected list
                    updateSelectedDocsList();
                },
                error: function (xhr, status, error) {
                    console.error(error);
                    console.error(xhr.responseText);
                    referencesTableBody.html('<tr><td colspan="12" class="text-center text-danger">Error loading rows.</td></tr>');
                }
            });
        }

        // ==================== Event Listeners ====================

        // Advance checkbox toggle
        $('#is_advance').on('change', toggleReferenceOptions);
        toggleReferenceOptions(); // Initial call

        // Voucher type or date change → reload RV number
        $('#voucher_type, #rv_date').on('change', loadRvNumber);

        // Reference select change → load details
        referenceSelect.on('change', function () {
            const ids = $(this).val() || [];
            const isAdvance = $('#is_advance').is(':checked');
            const refType = isAdvance ? 'sale_order' : 'sales_invoice';

            if (!ids.length) {
                referencesTableBody.html('<tr><td colspan="12" class="text-center text-muted">Select references to load details.</td></tr>');
                selectAll.prop('checked', $('#referencesTable tbody').find('.row-select:not(:checked)').length === 0 && $('#referencesTable tbody').find('.row-select').length > 0);
                updateSelectedDocsList();
                return;
            }

            referencesTableBody.html(`
                <tr>
                    <td colspan="12" class="text-center">
                        <div class="d-flex justify-content-center align-items-center">
                            <div class="spinner-border spinner-border-sm mr-2" role="status"></div>
                            Loading references...
                        </div>
                    </td>
                </tr>
            `);

            $.post(`{{ route('receipt-voucher.reference-details') }}`, {
                _token: '{{ csrf_token() }}',
                reference_type: refType,
                reference_ids: ids
            }, function (resp) {
                if (resp.success) {
                    buildRows(resp.items || []);
                } else {
                    referencesTableBody.html('<tr><td colspan="12" class="text-center text-danger">No data returned.</td></tr>');
                }
            }).fail(function () {
                referencesTableBody.html('<tr><td colspan="12" class="text-center text-danger">Failed to load details.</td></tr>');
            });
        });

        // Select All checkbox
        selectAll.on('change', function () {
            const checked = $(this).is(':checked');
            referencesTableBody.find('.row-select').prop('checked', checked);
            updateSelectedDocsList();
        });

        // Individual row checkboxes
        $(document).on('change', '.row-select', function () {
            // Update select all if needed
            const totalRows = referencesTableBody.find('.row-select').length;
            const checkedRows = referencesTableBody.find('.row-select:checked').length;
            selectAll.prop('checked', totalRows > 0 && totalRows === checkedRows);

            updateSelectedDocsList();
        });

        // Remove advance row
        $(document).on('click', '.remove-advance-row', function() {
            const row = $(this).closest('tr');
            const rowIdx = row.find('.row-select').data('row');
            $(`#bank-subrow-${rowIdx}`).remove();
            row.remove();
            
            // If table empty, show message
            if (referencesTableBody.find('tr').length === 0) {
                referencesTableBody.html('<tr><td colspan="12" class="text-center text-muted">Select references to load details.</td></tr>');
            }
            
            updateSelectedDocsList();
            validateBankTotal();
        });

        function getBankDetailsTotal() {
            let total = 0;
            $('.bank-amount').each(function() {
                total += parseFloat($(this).val()) || 0;
            });
            return total;
        }

        function validateBankTotal() {
            const allowExcess = $('#allow_excess').is(':checked');
            $('.nested-bank-row').each(function() {
                const subrow = $(this).closest('tr.bank-details-subrow');
                const rowIdx = subrow.attr('id').replace('bank-subrow-', '');
                const mainRow = $(`#reference-row-${rowIdx}`);
                const balanceVal = parseFloat(mainRow.find('.amount-input').attr('data-balance')) || 0;
                
                let sum = 0;
                subrow.find('.bank-amount').each(function() {
                    sum += parseFloat($(this).val()) || 0;
                });
                
                const inputs = subrow.find('.bank-amount');
                if (!allowExcess && sum > balanceVal && !mainRow.hasClass('advance-row')) {
                    inputs.css('border-color', '#dc3545');
                } else {
                    inputs.css('border-color', '');
                }
            });
            $('#bank-details-total-error').remove();
        }

        // Listen for bank amount changes and update parent reference's amount reactively
        $(document).on('input', '.bank-amount', function() {
            // Find the parent subrow container to get its row index
            const subrow = $(this).closest('tr.bank-details-subrow');
            const rowIdx = subrow.attr('id').replace('bank-subrow-', '');
            
            // Find the main reference row
            const mainRow = $(`#reference-row-${rowIdx}`);
            
            // Find outstanding balance for this row (stored in amount-input's data-balance)
            const isAdvance = mainRow.hasClass('advance-row');
            const balanceVal = parseFloat(mainRow.find('.amount-input').attr('data-balance')) || 0;
            const allowExcess = $('#allow_excess').is(':checked');
            
            // Sum all bank amounts in this subrow
            let sum = 0;
            subrow.find('.bank-amount').each(function() {
                sum += parseFloat($(this).val()) || 0;
            });
            
            // Advance validation: if an advance is selected, check its remaining amount limit
            const advanceSelect = $(this).closest('tr').find('.advance-select');
            if (advanceSelect.length && advanceSelect.val()) {
                const selectedOpt = advanceSelect.find('option:selected');
                const maxAdvanceAmount = parseFloat(selectedOpt.data('max')) || 0;
                const currentVal = parseFloat($(this).val()) || 0;
                
                if (currentVal > maxAdvanceAmount) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Amount Exceeds Advance Balance',
                        text: `The amount cannot exceed the selected advance's remaining balance of ${maxAdvanceAmount.toFixed(2)}.`,
                        confirmButtonText: 'OK'
                    });
                    
                    $(this).val(maxAdvanceAmount.toFixed(2));
                    
                    // Recalculate sum
                    sum = 0;
                    subrow.find('.bank-amount').each(function() {
                        sum += parseFloat($(this).val()) || 0;
                    });
                }
            }

            // If not an advance, allow_excess is not checked, and the sum exceeds the outstanding balance, auto-cap it
            if (!isAdvance && !allowExcess && sum > balanceVal) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Amount Exceeds Balance',
                    text: `The total bank amount for this document cannot exceed its outstanding balance of ${balanceVal.toFixed(2)}.`,
                    confirmButtonText: 'OK'
                });
                
                // Adjust the current input to cap the sum at the balance value
                const otherAmountsSum = sum - (parseFloat($(this).val()) || 0);
                const allowedCurrentAmount = Math.max(0, balanceVal - otherAmountsSum);
                $(this).val(allowedCurrentAmount.toFixed(2));
                
                // Recalculate sum with the capped amount
                sum = 0;
                subrow.find('.bank-amount').each(function() {
                    sum += parseFloat($(this).val()) || 0;
                });
            }
            // Validate bank details total
            validateBankTotal();
            
            // Recalculate amount if allowExcess is checked and sum > balanceVal
            if (allowExcess && sum > balanceVal) {
                mainRow.find('.amount-input').val(sum.toFixed(2));
            } else {
                mainRow.find('.amount-input').val(balanceVal.toFixed(2));
            }
            recalcRow(mainRow);
        });

        // Auto-fill bank amount when advance is selected
        $(document).on('change', '.advance-select', function() {
            const val = $(this).val();
            if (val) {
                const maxAdvanceAmount = parseFloat($(this).find('option:selected').data('max')) || 0;
                
                const subrow = $(this).closest('tr.bank-details-subrow');
                const rowIdx = subrow.attr('id').replace('bank-subrow-', '');
                const mainRow = $(`#reference-row-${rowIdx}`);
                const balanceVal = parseFloat(mainRow.find('.amount-input').attr('data-balance')) || 0;
                
                const bankAmountInput = $(this).closest('tr').find('.bank-amount');
                
                // Calculate other bank amounts in the same subrow
                let otherSum = 0;
                subrow.find('.bank-amount').not(bankAmountInput).each(function() {
                    otherSum += parseFloat($(this).val()) || 0;
                });
                
                // Remaining SO balance to cover
                const remainingToCover = Math.max(0, balanceVal - otherSum);
                
                // Use the minimum of maxAdvanceAmount and remainingToCover
                const amountToSet = Math.min(maxAdvanceAmount, remainingToCover);
                
                if (amountToSet > 0) {
                    bankAmountInput.val(amountToSet.toFixed(2)).trigger('input');
                } else {
                    bankAmountInput.val('').trigger('input');
                }
            } else {
                $(this).closest('tr').find('.bank-amount').val('').trigger('input');
            }
        });

        // Also re-validate when references change (which updates #total_receipt_amount)
        $(document).ajaxStop(function() {
            // After any AJAX (like loading references), validate
            validateBankTotal();
        });

        // ==================== Form Submission ====================
        $('form').off('submit').on('submit', function (e) {
            e.preventDefault();
            const totalChecked = referencesTableBody.find('.row-select:checked').length;
            if(!totalChecked) {
                Swal.fire('Validation', 'Please select at least one reference.', 'warning');
                return false;
            }

            // --- Added Validation ---
            const referencesTotal = parseFloat($('#total_amount').text()) || 0;
            const bankDetailsTotal = getBankDetailsTotal();
            const allowExcess = $('#allow_excess').is(':checked');

            if (!allowExcess && bankDetailsTotal > referencesTotal) {
                Swal.fire({
                    icon: 'error',
                    title: 'Validation Error',
                    text: `Total Bank/Account Detail amount (${bankDetailsTotal.toFixed(2)}) exceeds the total Selected References amount (${referencesTotal.toFixed(2)}). Enable "Allow Excess Amount" to permit advances.`
                });
                return false;
            }

            if (bankDetailsTotal < referencesTotal) {
                Swal.fire({
                    icon: 'error',
                    title: 'Validation Error',
                    text: `Total Bank/Account Detail amount (${bankDetailsTotal.toFixed(2)}) is less than the total Selected References amount (${referencesTotal.toFixed(2)}). You must pay the full balance of the selected documents.`
                });
                return false;
            }

            let validRows = true;
            $('.nested-bank-row').each(function() {
                const account = $(this).find('.account-select').val();
                const advance = $(this).find('.advance-select').val();
                if (!account && !advance) {
                    validRows = false;
                }
            });

            if (!validRows) {
                Swal.fire('Validation', 'Please select either an Account or an Advance for all Bank Details.', 'warning');
                return false;
            }
            // ------------------------

            // Remove unselected rows and their subrows from DOM before submit
            referencesTableBody.find('tr.reference-main-row').each(function () {
                const checkbox = $(this).find('.row-select');
                if (checkbox.length && !checkbox.is(':checked')) {
                    const rowIdx = checkbox.data('row');
                    $(`#bank-subrow-${rowIdx}`).remove();
                    $(this).remove();
                }
            });
            // Also cleanup any orphan bank subrows
            referencesTableBody.find('tr.bank-details-subrow').each(function () {
                const rowIdx = $(this).attr('id').replace('bank-subrow-', '');
                const mainRow = referencesTableBody.find(`[data-row="${rowIdx}"]`).closest('tr');
                if (!mainRow.length || !mainRow.find('.row-select').is(':checked')) {
                    $(this).remove();
                }
            });

            // Validation: at least one item selected
            if (!referencesTableBody.find('input[name*="items"]').length) {
                Swal.fire('Validation', 'Please select at least one reference.', 'warning');
                return false;
            }

            const form = $(this);

            $.ajax({
                url: "{{ route('receipt-voucher.update', $receiptVoucher->id) }}",
                method: "POST",
                data: form.serialize(),

                beforeSend: function () {
                    Swal.fire({
                        title: 'Processing...',
                        text: 'Please wait',
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                },

                success: function (resp) {
                    if (resp.success) {
                        Swal.fire({
                            icon: 'success',
                            title: resp.success,
                            confirmButtonText: 'OK',
                            allowOutsideClick: false,
                            allowEscapeKey: false
                        }).then((result) => {
                            if (result.isConfirmed) {
                                window.location.href = resp.redirect || $('#redirectUrl').val() || '/';
                            }
                        });
                    }
                },

                error: function (xhr) {
                    let msg = 'Something went wrong.';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        msg = xhr.responseJSON.message;
                    } else if (xhr.responseText) {
                        msg = xhr.responseText;
                    }

                    Swal.fire('Error', msg, 'error');
                }
            });

        });
    });

$(document).ready(function () {
            const initialItems = @json($initialItems);
            const initialBankDetails = @json($bankDetails);
            const initialAdvanceAdjustments = @json($advanceAdjustments);
            const selectedReferences = @json($selectedReferences);

            if (initialItems && initialItems.length > 0) {
                $('#referencesTable tbody').empty();
                buildRows(initialItems);

                if ($('#referencesTable tbody').find(".reference-main-row").length > 0) {
                    const firstRow = $('#referencesTable tbody').find(".reference-main-row").first();
                    const firstRowIdx = firstRow.attr("id").replace("reference-row-", "");
                    
                    if (initialBankDetails && initialBankDetails.length > 0) {
                        $(`.toggle-bank-subrow[data-row-idx="${firstRowIdx}"]`).find("i").removeClass("fa-chevron-down").addClass("fa-chevron-up");
                        
                        initialBankDetails.forEach((bd, i) => {
                            bankDetailCount++;
                            const bankIdx = `bank_${bankDetailCount}`;
                            const bdAmt = bd.amount ? parseFloat(bd.amount).toFixed(2) : "0.00";
                            const newRow = `
                                <tr class="nested-bank-row advance-row" id="bank-subrow-${firstRowIdx}">
                                    <td colspan="10"></td>
                                    <td>
                                        <select name="bank_details[${bankIdx}][account_id]" class="form-control select2-nested-bank bd-acc" required style="width: 100%;">
                                            <option value="">Select Account</option>
                                        </select>
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" class="form-control bank-amount" name="bank_details[${bankIdx}][amount]" value="${bdAmt}" required placeholder="Amount">
                                    </td>
                                    <td>
                                        <input type="text" class="form-control" name="bank_details[${bankIdx}][cheque_no]" value="${bd.cheque_no || ''}" placeholder="Cheque No (Optional)">
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-xs btn-danger remove-nested-bank-row" style="padding: .2rem .4rem; font-size: .75rem;">
                                            <i class="fa fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            `;
                            firstRow.after(newRow);
                            
                            // Initialize options
                            const newSelect = $(`select[name="bank_details[${bankIdx}][account_id]"]`);
                            let optsHtml = "";
                            @foreach ($accounts ?? [] as $acc)
                                optsHtml += `<option value="{{ $acc->id }}" ${bd.account_id == "{{ $acc->id }}" ? "selected" : ""}>{{ $acc->name }} ({{ $acc->hierarchy_path ?? $acc->unique_no }})</option>`;
                            @endforeach
                            newSelect.append(optsHtml);
                            
                        });
                        $(".select2-nested-bank").select2();
                    }
                }
                updateTotals();
            }

            // Always load the options for the selected customer on load
            if ($("#customer_id").val()) {
                $("#reference_ids").empty(); // clear immediately so all options don't show
                $.ajax({
                    url: '{{ route("receipt.voucher.get-documents") }}',
                    data: {
                        customer_id: $("#customer_id").val(),
                        is_advance: $("#is_advance").is(":checked")
                    },
                    success: function (response) {
                        $("#reference_ids").empty();
                        $("#reference_ids").select2({ data: response });
                        
                        if (selectedReferences && selectedReferences.length > 0) {
                            $("#reference_ids").val(selectedReferences).trigger('change.select2');
                        }
                    }
                });
            }
});
</script>
@endsection

