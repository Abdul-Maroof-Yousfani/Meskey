<script>
$(document).ready(function() {
    let saleAvailableAdvances = [];
    let saleAvailableSalesOrders = [];
    let saleAvailableBankAccounts = [];
    let saleRowCounter = 0;

    function notifyAlert(type, message) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: type === 'warning' ? 'warning' : (type === 'success' ? 'success' : 'error'),
                title: type === 'success' ? 'Success' : (type === 'warning' ? 'Warning' : 'Error'),
                text: message,
                timer: type === 'success' ? 1500 : undefined,
                showConfirmButton: type !== 'success',
                confirmButtonColor: '#3085d6',
            });
        } else if (typeof toastr !== 'undefined') {
            if (type === 'success') toastr.success(message);
            else if (type === 'warning') toastr.warning(message);
            else toastr.error(message);
        } else {
            alert(message);
        }
    }

    // Full exact URL templates using Laravel route() helper (guarantees correct base URL & prefix in XAMPP)
    const advancesUrlTemplate = "{{ route('direct.payment-voucher.customer-advances', ['accountId' => '__ID__']) }}";
    const sosUrlTemplate = "{{ route('direct.payment-voucher.customer-sos', ['accountId' => '__ID__']) }}";
    const banksUrlTemplate = "{{ route('direct.payment-voucher.customer-banks', ['accountId' => '__ID__']) }}";
    const soDosUrlTemplate = "{{ route('direct.payment-voucher.so-dos', ['soId' => '__ID__']) }}";

    const taxOptionsHtml = `
        <option value="">No Tax</option>
        @foreach ($taxes ?? [] as $tax)
            <option value="{{ $tax->id }}" data-rate="{{ $tax->percentage }}">
                {{ $tax->name }} ({{ $tax->percentage }}%)
            </option>
        @endforeach
    `;

    // Initialize Select2 on top-level static dropdowns
    $('#sale_voucher_type, #sale_account_id, #sale_customer_account_id').select2({
        width: '100%'
    });

    const $saleBankAccountSelect = $('#sale_bank_account_id');
    $saleBankAccountSelect.select2({
        templateResult: formatSaleBankAccount,
        templateSelection: formatSaleBankAccountSelection,
        width: '100%'
    });

    function formatSaleBankAccount(account) {
        if (!account.id) {
            return account.text;
        }
        const title = account.element && account.element.getAttribute('data-title') ? account.element.getAttribute('data-title') : 'No Title';
        const accountTitle = account.element && account.element.getAttribute('data-account-title') ? account.element.getAttribute('data-account-title') : 'N/A';
        const accountNo = account.element && account.element.getAttribute('data-account-no') ? account.element.getAttribute('data-account-no') : 'N/A';
        const bankName = account.element && account.element.getAttribute('data-bank-name') ? account.element.getAttribute('data-bank-name') : 'N/A';
        const branchName = account.element && account.element.getAttribute('data-branch-name') ? account.element.getAttribute('data-branch-name') : 'N/A';
        const type = account.element && account.element.getAttribute('data-type') ? (account.element.getAttribute('data-type') === 'company' ? 'Company Account' : 'Owner Account') : 'N/A';

        return $(`
            <div class="bank-account-option">
                <strong>${type} - ${title}</strong>
                <div class="text-muted small">${accountTitle} - (${accountNo})</div>
                <div class="text-muted small">${bankName} - ${branchName}</div>
            </div>
        `);
    }

    function formatSaleBankAccountSelection(account) {
        if (!account.id) {
            return account.text;
        }
        const title = account.element && account.element.getAttribute('data-title') ? account.element.getAttribute('data-title') : 'No Title';
        const accountNo = account.element && account.element.getAttribute('data-account-no') ? account.element.getAttribute('data-account-no') : 'N/A';
        const type = account.element && account.element.getAttribute('data-type') ? (account.element.getAttribute('data-type') === 'company' ? 'Company' : 'Owner') : 'Account';

        return `${type} - ${title} (${accountNo})`;
    }

    $saleBankAccountSelect.on('change', function() {
        const selectedOption = $(this).find('option:selected');
        if (selectedOption.val()) {
            const accountNo = selectedOption.data('account-no') || '';
            const accountType = selectedOption.data('type') || '';
            $('#sale_bank_account_number').val(accountNo);
            $('#sale_bank_account_type').val(accountType);
        } else {
            $('#sale_bank_account_number').val('');
            $('#sale_bank_account_type').val('');
        }
    });

    // Toggle Bank Fields for Cheque
    $('#sale_voucher_type').on('change', function() {
        if ($(this).val() === 'bank_payment_voucher') {
            $('#sale_bankFields').show();
            $('#sale_cheque_no, #sale_cheque_date').prop('required', true);
        } else {
            $('#sale_bankFields').hide();
            $('#sale_cheque_no, #sale_cheque_date').prop('required', false);
        }
        loadSalePvNumberAndAccounts();
    });

    $('#sale_pv_date').on('change', loadSalePvNumberAndAccounts);

    function loadSalePvNumberAndAccounts() {
        const voucherType = $('#sale_voucher_type').val();
        const pvDate = $('#sale_pv_date').val();

        if (!voucherType || !pvDate) {
            $('#sale_unique_no').val('');
            $('#sale_account_id').empty().append('<option value="">Select Account</option>').trigger('change');
            return;
        }

        $.post('{{ route("payment-voucher.generate-pv-number") }}', {
            _token: '{{ csrf_token() }}',
            voucher_type: voucherType,
            pv_date: pvDate
        }, function(resp) {
            if (resp.success) {
                $('#sale_unique_no').val(resp.pv_number || '');

                const $accountSelect = $('#sale_account_id');
                $accountSelect.empty().append('<option value="">Select Account</option>');
                if (resp.accounts && resp.accounts.length) {
                    resp.accounts.forEach(acc => {
                        $accountSelect.append(new Option(`${acc.name} (${acc.unique_no})`, acc.id));
                    });
                }
                $accountSelect.trigger('change');
            }
        });
    }

    // Customer Account Change: Fetch Advances, Sales Orders, AND Bank Accounts in Parallel using absolute URLs
    $('#sale_customer_account_id').on('change', function() {
        const custAccId = $(this).val();
        if (!custAccId) {
            saleAvailableAdvances = [];
            saleAvailableSalesOrders = [];
            saleAvailableBankAccounts = [];
            $saleBankAccountSelect.empty().append('<option value="">Select Bank Account</option>').trigger('change');
            $('#saleVoucherEntriesBody').html(`
                <tr>
                    <td colspan="11" class="text-center text-muted py-3" id="saleEmptyRow">
                        Please select a Customer Account first to add adjustment items.
                    </td>
                </tr>
            `);
            $('#sale_customer_selected_badge').hide();
            calculateSaleTotals();
            return;
        }

        $('#sale_customer_selected_badge').show().html('<i class="ft-loader spinner mr-1"></i> Loading customer details...');

        let advData = null, soData = null, bankData = null;

        const p1 = $.get(advancesUrlTemplate.replace('__ID__', custAccId)).done(r => { advData = r; });
        const p2 = $.get(sosUrlTemplate.replace('__ID__', custAccId)).done(r => { soData = r; });
        const p3 = $.get(banksUrlTemplate.replace('__ID__', custAccId)).done(r => { bankData = r; });

        $.when(p1, p2, p3).always(function() {
            saleAvailableAdvances = (advData && advData.customer_advances) ? advData.customer_advances : [];
            saleAvailableSalesOrders = (soData && soData.sales_orders) ? soData.sales_orders : [];
            saleAvailableBankAccounts = (bankData && bankData.bank_accounts) ? bankData.bank_accounts : [];

            const customerName = (advData && advData.customer_name) ? advData.customer_name : 
                                 ((soData && soData.customer_name) ? soData.customer_name : 'Customer');
            $('#sale_customer_selected_badge').html(`<strong>${customerName}</strong> | Advances: ${saleAvailableAdvances.length} | Sales Orders: ${saleAvailableSalesOrders.length} | Banks: ${saleAvailableBankAccounts.length}`).show();

            // Populate Customer Bank Accounts Dropdown
            $saleBankAccountSelect.empty().append('<option value="">Select Bank Account</option>');
            if (saleAvailableBankAccounts.length > 0) {
                saleAvailableBankAccounts.forEach(function(account) {
                    const opt = new Option(
                        `${account.type === 'company' ? 'Company' : 'Owner'} - ${account.title || 'No Title'} (${account.account_number || 'N/A'})`,
                        account.id,
                        false,
                        false
                    );
                    $(opt).attr('data-title', account.title || 'No Title');
                    $(opt).attr('data-account-title', account.account_title || 'N/A');
                    $(opt).attr('data-account-no', account.account_number || 'N/A');
                    $(opt).attr('data-bank-name', account.bank_name || 'N/A');
                    $(opt).attr('data-branch-name', account.branch_name || 'N/A');
                    $(opt).attr('data-type', account.type || 'N/A');
                    $saleBankAccountSelect.append(opt);
                });
            } else {
                $saleBankAccountSelect.append('<option value="" disabled>No bank accounts found for this customer</option>');
            }
            $saleBankAccountSelect.trigger('change');

            // Reset lines and add first row
            $('#saleVoucherEntriesBody').empty();
            saleRowCounter = 0;
            addSaleEntryRow();
        });
    });

    // Add Line Item click
    $('#saleAddRowBtn').on('click', function() {
        if (!$('#sale_customer_account_id').val()) {
            notifyAlert('warning', 'Please select a Customer Account first.');
            $('#sale_customer_account_id').select2('open');
            return;
        }
        addSaleEntryRow();
    });

    function addSaleEntryRow(initialType = 'excess_amount') {
        $('#saleEmptyRow').remove();
        const rIndex = saleRowCounter++;

        let advanceOptionsHtml = '<option value="">Select Excess Payment</option>';
        saleAvailableAdvances.forEach(adv => {
            advanceOptionsHtml += `<option value="${adv.id}" data-remaining="${adv.remaining_amount}">
                ${adv.voucher_no} (Rem: Rs. ${adv.formatted_remaining})
            </option>`;
        });

        let soOptionsHtml = '<option value="">Select Sales Order</option>';
        saleAvailableSalesOrders.forEach(so => {
            soOptionsHtml += `<option value="${so.id}">${so.text}</option>`;
        });

        const isExcess = (initialType === 'excess_amount');

        const rowHtml = `
            <tr class="sale-entry-row" data-row-index="${rIndex}">
                <!-- Column 1: Adjustment Type -->
                <td>
                    <select name="row_type[${rIndex}]" class="form-control form-control-sm sale-row-type-select">
                        <option value="excess_amount" ${isExcess ? 'selected' : ''}>Excess Amount</option>
                        <option value="delivery_order" ${!isExcess ? 'selected' : ''}>Delivery Order (DO)</option>
                    </select>
                </td>

                <!-- Column 2: Sales Order (Separate Column) -->
                <td class="cell-so">
                    <div class="so-active-box" style="${!isExcess ? '' : 'display: none;'}">
                        <select name="sales_order_id[${rIndex}]" class="form-control form-control-sm sale-so-select select2-sale-row" style="width: 100%;">
                            ${soOptionsHtml}
                        </select>
                    </div>
                    <div class="so-disabled-box" style="${isExcess ? '' : 'display: none;'}">
                        <input type="text" class="form-control form-control-sm bg-light text-center text-muted" readonly value="—">
                    </div>
                </td>

                <!-- Column 3: Delivery Order (Separate Column) -->
                <td class="cell-do">
                    <div class="do-active-box" style="${!isExcess ? '' : 'display: none;'}">
                        <select name="delivery_order_id[${rIndex}]" class="form-control form-control-sm sale-do-select select2-sale-row" style="width: 100%;">
                            <option value="">Select DO (or SO direct)</option>
                        </select>
                        <div class="do-status-note small text-muted mt-1" style="display: none;"></div>
                    </div>
                    <div class="do-disabled-box" style="${isExcess ? '' : 'display: none;'}">
                        <input type="text" class="form-control form-control-sm bg-light text-center text-muted" readonly value="—">
                    </div>
                </td>

                <!-- Column 4: Excess Advance (Separate Column) -->
                <td class="cell-advance">
                    <div class="adv-active-box" style="${isExcess ? '' : 'display: none;'}">
                        <select name="customer_advance_id[${rIndex}]" class="form-control form-control-sm sale-advance-select select2-sale-row" style="width: 100%;">
                            ${advanceOptionsHtml}
                        </select>
                    </div>
                    <div class="adv-disabled-box" style="${!isExcess ? '' : 'display: none;'}">
                        <input type="text" class="form-control form-control-sm bg-light text-center text-muted" readonly value="—">
                    </div>
                </td>

                <!-- Column 5: Available Bal -->
                <td>
                    <input type="text" class="form-control form-control-sm sale-rem-bal text-right bg-light font-weight-bold" readonly placeholder="0.00">
                    <input type="hidden" class="sale-raw-rem-bal" value="0">
                </td>

                <!-- Column 6: Amount -->
                <td>
                    <input type="number" step="0.01" min="0.01" name="amount[${rIndex}]" class="form-control form-control-sm sale-amount-input text-right" required placeholder="0.00">
                </td>

                <!-- Column 7: Tax -->
                <td>
                    <select name="tax_id[${rIndex}]" class="form-control form-control-sm sale-tax-select select2-sale-row" style="width: 100%;">
                        ${taxOptionsHtml}
                    </select>
                </td>

                <!-- Column 8: Tax Amount -->
                <td>
                    <input type="number" step="0.01" name="tax_amount[${rIndex}]" class="form-control form-control-sm sale-tax-amount text-right bg-light" readonly value="0.00">
                </td>

                <!-- Column 9: Net Amount -->
                <td>
                    <input type="number" step="0.01" name="net_amount[${rIndex}]" class="form-control form-control-sm sale-net-amount text-right bg-light font-weight-bold" readonly value="0.00">
                </td>

                <!-- Column 10: Description -->
                <td>
                    <input type="text" name="description[${rIndex}]" class="form-control form-control-sm sale-desc-input" placeholder="Notes / reason">
                </td>

                <!-- Column 11: Action -->
                <td class="text-center">
                    <button type="button" class="btn btn-sm btn-outline-danger sale-remove-row-btn" title="Remove row">
                        <i class="ft-trash-2"></i>
                    </button>
                </td>
            </tr>
        `;

        const $newRow = $(rowHtml);
        $('#saleVoucherEntriesBody').append($newRow);

        // Initialize Select2 on selects within this row
        $newRow.find('.select2-sale-row').select2({
            width: '100%'
        });

        calculateSaleTotals();
    }

    // Toggle Row Type (Excess Amount vs Delivery Order) within a row
    $(document).on('change', '.sale-row-type-select', function() {
        const $row = $(this).closest('tr');
        const selectedType = $(this).val();

        // Reset amounts in this row
        $row.find('.sale-rem-bal').val('');
        $row.find('.sale-raw-rem-bal').val('0');
        $row.find('.sale-amount-input').val('').removeAttr('max');
        $row.find('.sale-tax-amount').val('0.00');
        $row.find('.sale-net-amount').val('0.00');

        if (selectedType === 'excess_amount') {
            // Show Advance, hide SO and DO
            $row.find('.cell-so .so-active-box').hide();
            $row.find('.cell-so .so-disabled-box').show();
            $row.find('.cell-so .sale-so-select').val('').trigger('change.select2');

            $row.find('.cell-do .do-active-box').hide();
            $row.find('.cell-do .do-disabled-box').show();
            $row.find('.cell-do .sale-do-select').empty().append('<option value="">Select DO (or SO direct)</option>');
            $row.find('.cell-do .do-status-note').hide().empty();

            $row.find('.cell-advance .adv-active-box').show();
            $row.find('.cell-advance .adv-disabled-box').hide();
        } else { // delivery_order
            // Show SO and DO, hide Advance
            $row.find('.cell-advance .adv-active-box').hide();
            $row.find('.cell-advance .adv-disabled-box').show();
            $row.find('.cell-advance .sale-advance-select').val('').trigger('change.select2');

            $row.find('.cell-so .so-active-box').show();
            $row.find('.cell-so .so-disabled-box').hide();

            $row.find('.cell-do .do-active-box').show();
            $row.find('.cell-do .do-disabled-box').hide();
        }

        calculateSaleTotals();
    });

    // Advance selection change in row
    $(document).on('change', '.sale-advance-select', function() {
        const $row = $(this).closest('tr');
        const $selected = $(this).find('option:selected');
        const remaining = parseFloat($selected.data('remaining')) || 0;

        $row.find('.sale-raw-rem-bal').val(remaining);
        $row.find('.sale-rem-bal').val(remaining > 0 ? remaining.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : '0.00');
        $row.find('.sale-amount-input').attr('max', remaining.toFixed(2));

        if (remaining > 0 && !$row.find('.sale-amount-input').val()) {
            $row.find('.sale-amount-input').val(remaining.toFixed(2));
        }
        calculateRowAmounts($row);
    });

    // Sales Order selection change in row -> fetches DOs using absolute URL
    $(document).on('change', '.sale-so-select', function() {
        const $row = $(this).closest('tr');
        const soId = $(this).val();
        const $doSelect = $row.find('.sale-do-select');
        const $doNote = $row.find('.do-status-note');

        $doSelect.empty().append('<option value="">Loading DOs...</option>');
        $doNote.hide().empty();
        $row.find('.sale-raw-rem-bal').val('0');
        $row.find('.sale-rem-bal').val('');
        $row.find('.sale-amount-input').val('').removeAttr('max');
        calculateRowAmounts($row);

        if (!soId) {
            $doSelect.empty().append('<option value="">Select DO (or SO direct)</option>');
            return;
        }

        $.get(soDosUrlTemplate.replace('__ID__', soId), function(resp) {
            $doSelect.empty().append('<option value="">Select Delivery Order</option>');

            if (resp.has_dos && resp.delivery_orders && resp.delivery_orders.length > 0) {
                const soRemAmt = parseFloat(resp.so_remaining_amount) || 0;
                if (soRemAmt > 0) {
                    const soOpt = new Option('Direct SO Advance (Rs. ' + resp.so_formatted_remaining + ')', '');
                    $(soOpt).attr('data-rem-amt', soRemAmt);
                    $(soOpt).attr('data-rem-qty', resp.so_remaining_qty || 0);
                    $(soOpt).attr('data-status', 'so_direct');
                    $doSelect.append(soOpt);
                }
                resp.delivery_orders.forEach(doItem => {
                    const opt = new Option(doItem.text, doItem.id);
                    $(opt).attr('data-rem-amt', doItem.remaining_amount);
                    $(opt).attr('data-rem-qty', doItem.remaining_qty);
                    $(opt).attr('data-status', doItem.do_status);
                    $doSelect.append(opt);
                });
                $doSelect.trigger('change.select2');
                $doNote.html('<i class="ft-info text-info"></i> Delivery orders available for this SO.').show();
            } else {
                // No DOs created yet - direct SO sale return allowed
                const soRemAmt = parseFloat(resp.so_remaining_amount) || 0;
                $doSelect.empty().append(new Option('No DO - Full SO Return (Rs. ' + resp.so_formatted_remaining + ')', ''));
                $doSelect.trigger('change.select2');

                $row.find('.sale-raw-rem-bal').val(soRemAmt);
                $row.find('.sale-rem-bal').val(soRemAmt > 0 ? soRemAmt.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : '0.00');
                $row.find('.sale-amount-input').attr('max', soRemAmt.toFixed(2));
                $doNote.html('<i class="ft-check text-success"></i> No DOs created. Full SO returnable: Rs. ' + resp.so_formatted_remaining).show();

                if (soRemAmt > 0 && !$row.find('.sale-amount-input').val()) {
                    $row.find('.sale-amount-input').val(soRemAmt.toFixed(2));
                }
                calculateRowAmounts($row);
            }
        }).fail(function(xhr) {
            notifyAlert('error', 'Failed to load DOs for selected Sales Order. Status: ' + xhr.status);
        });
    });

    // DO selection change in row
    $(document).on('change', '.sale-do-select', function() {
        const $row = $(this).closest('tr');
        const $selected = $(this).find('option:selected');
        const remAmt = parseFloat($selected.data('rem-amt')) || 0;
        const remQty = parseFloat($selected.data('rem-qty')) || 0;
        const doStatus = $selected.data('status') || '';
        const $doNote = $row.find('.do-status-note');

        if ($selected.val() || remAmt > 0) {
            $row.find('.sale-raw-rem-bal').val(remAmt);
            $row.find('.sale-rem-bal').val(remAmt > 0 ? remAmt.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : '0.00');
            $row.find('.sale-amount-input').attr('max', remAmt.toFixed(2));

            let statusBadge = '';
            if (doStatus === 'closed') {
                statusBadge = '<span class="badge badge-warning text-dark ml-1">DO Closed</span>';
            } else if (doStatus === 'so_direct') {
                statusBadge = '<span class="badge badge-primary ml-1">SO Advance</span>';
            } else if (doStatus) {
                statusBadge = '<span class="badge badge-success ml-1">DO Active</span>';
            }
            $doNote.html(`Remaining: <strong>${remQty} Qty</strong> | <strong>Rs. ${remAmt.toLocaleString('en-US', { minimumFractionDigits: 2 })}</strong> ${statusBadge}`).show();

            if (remAmt > 0 && !$row.find('.sale-amount-input').val()) {
                $row.find('.sale-amount-input').val(remAmt.toFixed(2));
            }
        } else {
            $row.find('.sale-raw-rem-bal').val('0');
            $row.find('.sale-rem-bal').val('');
            $row.find('.sale-amount-input').val('').removeAttr('max');
            $doNote.hide();
        }

        calculateRowAmounts($row);
    });

    // Amount & Tax input handling
    $(document).on('input change', '.sale-amount-input, .sale-tax-select', function() {
        const $row = $(this).closest('tr');
        calculateRowAmounts($row);
    });

    function calculateRowAmounts($row) {
        const rawMax = parseFloat($row.find('.sale-raw-rem-bal').val()) || 0;
        let amount = parseFloat($row.find('.sale-amount-input').val()) || 0;

        // Strict capping check against remaining balance
        if (rawMax > 0 && amount > rawMax + 0.001) {
            amount = rawMax;
            $row.find('.sale-amount-input').val(amount.toFixed(2));
            notifyAlert('warning', 'Amount cannot exceed the remaining balance of Rs. ' + rawMax.toLocaleString('en-US', { minimumFractionDigits: 2 }));
        }

        const taxRate = parseFloat($row.find('.sale-tax-select option:selected').data('rate')) || 0;
        const taxAmount = (amount * taxRate) / 100;
        const netAmount = amount + taxAmount;

        $row.find('.sale-tax-amount').val(taxAmount.toFixed(2));
        $row.find('.sale-net-amount').val(netAmount.toFixed(2));

        calculateSaleTotals();
    }

    function calculateSaleTotals() {
        let totalNet = 0;
        $('.sale-net-amount').each(function() {
            totalNet += parseFloat($(this).val()) || 0;
        });
        $('#saleTotalNetAmount').text(totalNet.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
    }

    // Remove line item
    $(document).on('click', '.sale-remove-row-btn', function() {
        const $row = $(this).closest('tr');
        $row.remove();

        if ($('#saleVoucherEntriesBody tr.sale-entry-row').length === 0) {
            $('#saleVoucherEntriesBody').html(`
                <tr>
                    <td colspan="11" class="text-center text-muted py-3" id="saleEmptyRow">
                        No items added. Click "Add Line Item" to add an adjustment item.
                    </td>
                </tr>
            `);
        }

        calculateSaleTotals();
    });

    // Form Submission
    $('#ajaxSubmitSale').on('submit', function(e) {
        e.preventDefault();

        const custAccId = $('#sale_customer_account_id').val();
        if (!custAccId) {
            notifyAlert('error', 'Please select a Customer Account.');
            $('#sale_customer_account_id').select2('open');
            return;
        }

        const rows = $('#saleVoucherEntriesBody tr.sale-entry-row');
        if (rows.length === 0) {
            notifyAlert('error', 'Please add at least one line item.');
            return;
        }

        let hasError = false;
        rows.each(function(idx) {
            const rowType = $(this).find('.sale-row-type-select').val();
            const amount = parseFloat($(this).find('.sale-amount-input').val()) || 0;
            const maxAllowed = parseFloat($(this).find('.sale-raw-rem-bal').val()) || 0;

            if (rowType === 'excess_amount') {
                const advId = $(this).find('.sale-advance-select').val();
                if (!advId) {
                    notifyAlert('error', `Row #${idx + 1}: Please select an Excess Payment voucher.`);
                    hasError = true;
                    return false;
                }
            } else {
                const soId = $(this).find('.sale-so-select').val();
                if (!soId) {
                    notifyAlert('error', `Row #${idx + 1}: Please select a Sales Order.`);
                    hasError = true;
                    return false;
                }
            }

            if (amount <= 0) {
                notifyAlert('error', `Row #${idx + 1}: Amount must be greater than zero.`);
                hasError = true;
                return false;
            }

            if (maxAllowed > 0 && amount > maxAllowed + 0.01) {
                notifyAlert('error', `Row #${idx + 1}: Amount exceeds available remaining balance (Rs. ${maxAllowed.toFixed(2)}).`);
                hasError = true;
                return false;
            }
        });

        if (hasError) {
            return;
        }

        const $submitBtn = $('#saleSubmitBtn');
        $submitBtn.prop('disabled', true).html('<i class="ft-loader spinner mr-1"></i> Saving Voucher...');

        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: $(this).serialize(),
            success: function(resp) {
                const successMsg = resp.success || 'Direct Payment Voucher (Sale) created successfully!';
                const targetUrl = resp.redirect || window.location.href;
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: successMsg,
                        timer: 1500,
                        showConfirmButton: false,
                    }).then(function() {
                        window.location.href = targetUrl;
                    });
                    setTimeout(function() {
                        window.location.href = targetUrl;
                    }, 1600);
                } else {
                    alert(successMsg);
                    window.location.href = targetUrl;
                }
            },
            error: function(xhr) {
                $submitBtn.prop('disabled', false).html('<i class="ft-check mr-1"></i> Create DPV For Sale');
                if (xhr.status === 422) {
                    const errors = xhr.responseJSON.errors || {};
                    let msg = xhr.responseJSON.message || 'Validation error:';
                    for (const key in errors) {
                        msg += '\n- ' + errors[key].join(', ');
                    }
                    notifyAlert('error', msg);
                } else {
                    const msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Server error occurred.';
                    notifyAlert('error', msg);
                }
            }
        });
    });
});
</script>
