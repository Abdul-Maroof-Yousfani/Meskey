@extends('management.layouts.master')
@section('title')
    Create Receipt Voucher
@endsection
@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">Create Receipt Voucher</h4>
                        <a href="{{ route('receipt-voucher.index') }}" class="btn btn-sm btn-primary">Back</a>
                    </div>
                    <div class="card-body">
                        <form>
                            @csrf
                            {{-- <input type="hidden" id="redirectUrl" value="{{ route('receipt-voucher.index') }}"> --}}
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="voucher_type">Voucher Type</label>
                                        <select name="voucher_type" id="voucher_type" class="form-control select2" required>
                                            <option value="">Select Type</option>
                                            <option value="bank_payment_voucher">Bank Receipt Voucher</option>
                                            <option value="cash_payment_voucher">Cash Receipt Voucher</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="rv_date">Date</label>
                                        <input type="date" name="rv_date" id="rv_date" class="form-control" required>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="unique_no">RV Number</label>
                                        <input type="text" name="unique_no" id="unique_no" class="form-control" readonly>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="account_id">Account</label>
                                        <select name="account_id" id="account_id" class="form-control select2" required>
                                            <option value="">Select Account</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="ref_bill_no">Receipt Ref No</label>
                                        <input type="text" name="ref_bill_no" id="ref_bill_no" class="form-control" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="bill_date">Receipt Date</label>
                                        <input type="date" name="bill_date" id="bill_date" class="form-control" required>
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
                                                <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label id="reference_label">Invoices (approved, receiving pending)</label>
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
                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="checkbox" id="is_advance" onchange="select_customer()" checked>
                                        <label class="form-check-label" for="is_advance">Advance</label>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <label class="mb-0">Selected References</label>
                                            <button type="button" class="btn btn-sm btn-info" id="add-advance-btn" onclick="addAdvanceRow()">
                                                <i class="fa fa-plus"></i> Add Advance
                                            </button>
                                        </div>
                                        <div class="table-responsive">
                                            <table class="table table-bordered" id="referencesTable">
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

                            <div id="bank-details-section" class="row" style="display: none;">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <label class="mb-0">Line Items (Bank/Account Details)</label>
                                            <button type="button" class="btn btn-sm btn-success" onclick="addBankDetailRow()">
                                                <i class="fa fa-plus"></i> Add More
                                            </button>
                                        </div>
                                        <div class="table-responsive">
                                            <table class="table table-bordered" id="bankDetailsTable">
                                                <thead>
                                                    <tr>
                                                        <th>Account</th>
                                                        <th width="20%">Amount</th>
                                                        <th width="25%">Cheque No</th>
                                                        <th width="5%">Action</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="bank-details-data">
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
                                        <textarea name="remarks" id="remarks" class="form-control" rows="3"></textarea>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group text-right">
                                <button type="submit" class="btn btn-primary">Create Receipt Voucher</button>
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
            <tr class="advance-row">
                <td class="text-center">
                    <input type="checkbox" class="row-select" checked data-row="${idx}">
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
                    <input type="number" step="0.01" class="form-control amount-input" name="items[${idx}][amount_display]" value="0.00">
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
                <td class="text-center">
                    <button type="button" class="btn btn-sm btn-danger remove-advance-row">
                        <i class="fa fa-trash"></i>
                    </button>
                </td>
            </tr>
        `;

        const tbody = $("#rv-data");
        if (tbody.find('td[colspan]').length) {
            tbody.empty();
        }
        tbody.append(rowHtml);
        
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
            $('#bank-details-section').hide();
            return;
        }

        emptyMessage.hide();
        listContainer.show();
        $('#bank-details-section').show();

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

    function addBankDetailRow() {
        const filteredAccounts = getFilteredAccounts();
        if ($('#voucher_type').val() === '') {
            Swal.fire('Warning', 'Please select a Voucher Type first.', 'warning');
            return;
        }

        bankDetailCount++;
        const idx = bankDetailCount;
        
        let accountOptions = '<option value="">Select Account</option>';
        filteredAccounts.forEach(function(acc) {
            accountOptions += `<option value="${acc.id}">${acc.name} (${acc.hierarchy_path ?? ''})</option>`;
        });

        const rowHtml = `
            <tr>
                <td>
                    <select name="bank_details[${idx}][account_id]" class="form-control select2-bank" required>
                        ${accountOptions}
                    </select>
                </td>
                <td>
                    <input type="number" step="0.01" name="bank_details[${idx}][amount]" class="form-control bank-amount" required>
                </td>
                <td>
                    <input type="text" name="bank_details[${idx}][cheque_no]" class="form-control">
                </td>
                <td class="text-center">
                    <button type="button" class="btn btn-sm btn-danger remove-bank-row">
                        <i class="fa fa-trash"></i>
                    </button>
                </td>
            </tr>
        `;
        
        $("#bank-details-data").append(rowHtml);
        $('.select2-bank').last().select2({
            dropdownParent: $('#bankDetailsTable')
        });
    }

    $(document).ready(function () {
        // ... existing code ...
        
        $(document).on('click', '.remove-bank-row', function() {
            $(this).closest('tr').remove();
        });

        // Clear and add initial row when voucher type changes
        $('#voucher_type').on('change', function() {
            $("#bank-details-data").empty();
            if ($(this).val() !== '') {
                addBankDetailRow();
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
            selectAll.prop('checked', false);
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
                        $accountSelect.empty().append('<option value="">Select Account</option>');
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
                selectAll.prop('checked', false);
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
                selectAll.prop('checked', false);
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
            row.remove();
            
            // If table empty, show message
            if (referencesTableBody.find('tr').length === 0) {
                referencesTableBody.html('<tr><td colspan="12" class="text-center text-muted">Select references to load details.</td></tr>');
            }
            
            updateSelectedDocsList();
        });

        function getBankDetailsTotal() {
            let total = 0;
            $('.bank-amount').each(function() {
                total += parseFloat($(this).val()) || 0;
            });
            return total;
        }

        function validateBankTotal() {
            const referencesTotal = parseFloat($('#total_amount').text()) || 0;
            const bankDetailsTotal = getBankDetailsTotal();
            
            if (bankDetailsTotal > referencesTotal) {
                $('.bank-amount').css('border-color', '#dc3545');
                $('#bank-details-total-error').remove();
                $('#bank-details-data').after(`<div id="bank-details-total-error" class="text-danger small mt-1 pl-2">Total amount (${bankDetailsTotal.toFixed(2)}) exceeds the references total (${referencesTotal.toFixed(2)})</div>`);
            } else {
                $('.bank-amount').css('border-color', '');
                $('#bank-details-total-error').remove();
            }
        }

        // Listen for bank amount changes
        $(document).on('input', '.bank-amount', validateBankTotal);

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

            if (bankDetailsTotal > referencesTotal) {
                Swal.fire({
                    icon: 'error',
                    title: 'Validation Error',
                    text: `Total Bank/Account Detail amount (${bankDetailsTotal.toFixed(2)}) cannot exceed the total Selected References amount (${referencesTotal.toFixed(2)}).`
                });
                return false;
            }
            // ------------------------

            // Remove unselected rows from DOM before submit
            referencesTableBody.find('tr').each(function () {
                const checkbox = $(this).find('.row-select');
                console.log($(this).find(".row-select").is(":checked"))
            
                if (checkbox.length && !checkbox.is(':checked')) {
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
                url: "{{ route('receipt-voucher.store') }}",
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
</script>
@endsection

