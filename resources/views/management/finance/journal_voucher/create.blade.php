@extends('management.layouts.master')
@section('title')
   Create Journal Voucher
@endsection
@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">Create Journal Voucher</h4>
                        <a href="{{ route('journal-voucher.index') }}" class="btn btn-sm btn-primary">Back</a>
                    </div>
                    <div class="card-body">
                        <form id="ajaxSubmit" action="{{ route('journal-voucher.store') }}">
                            @csrf

                            <input type="hidden" id="url" value="{{ route('journal-voucher.index') }}">
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="jv_date">Date</label>
                                        <input type="date" name="jv_date" id="jv_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="jv_no">JV Number</label>
                                        <input type="text" name="jv_no" id="jv_no" class="form-control" readonly>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label for="description">Description</label>
                                        <textarea name="description" id="description" class="form-control" rows="3"></textarea>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-12">
                                    <div class="custom-control custom-switch mb-2">
                                        <input type="checkbox" class="custom-control-input" id="receivingToggle" name="is_receiving">
                                        <label class="custom-control-label" for="receivingToggle">Receiving</label>
                                    </div>
                                    <div class="form-group">
                                        <label>Journal Entries</label>
                                        <div class="table-responsive">
                                            <table class="table table-bordered" id="journalEntriesTable">
                                                <thead>
                                                    <tr>
                                                        <th>Account</th>
                                                        <th class="receiving-col" style="display: none; width: 200px;">Receipt Voucher</th>
                                                        <th class="receiving-col" style="display: none; width: 200px;">Sales order</th>
                                                        <th>Description</th>
                                                        <th>Debit</th>
                                                        <th>Credit</th>
                                                        <th>Action</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="journalEntriesBody">
                                                    <tr>
                                                        <td>
                                                            <select name="details[0][acc_id]" class="form-control select2 account-select" required>
                                                                <option value="">Select Account</option>
                                                                @foreach ($accounts as $account)
                                                                    <option value="{{ $account->id }}">{{ $account->name }} ({{ $account->unique_no }})</option>
                                                                @endforeach
                                                            </select>
                                                        </td>
                                                        <td class="receiving-col" style="display: none;">
                                                            <select name="details[0][receipt_voucher_id]" class="form-control select2 receipt-voucher-select" style="width: 100%;">
                                                                <option value="">Select Receipt Voucher (Select Account First)</option>
                                                            </select>
                                                        </td>
                                                        <td class="receiving-col" style="display: none;">
                                                            {{-- Empty for first row --}}
                                                        </td>
                                                        <td>
                                                            <input type="text" name="details[0][description]" class="form-control description-input" placeholder="Line description">
                                                        </td>
                                                        <td>
                                                            <input type="number" name="details[0][debit_amount]" class="form-control debit-input" step="0.01" min="0" placeholder="0.00">
                                                        </td>
                                                        <td>
                                                            <input type="number" name="details[0][credit_amount]" class="form-control credit-input" step="0.01" min="0" placeholder="0.00">
                                                        </td>
                                                        <td>
                                                            <button type="button" class="btn btn-sm btn-danger remove-row" style="display: none;">
                                                                <i class="ft-trash-2"></i>
                                                            </button>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td>
                                                            <select name="details[1][acc_id]" class="form-control select2 account-select" required>
                                                                <option value="">Select Account</option>
                                                                @foreach ($accounts as $account)
                                                                    <option value="{{ $account->id }}">{{ $account->name }} ({{ $account->unique_no }})</option>
                                                                @endforeach
                                                            </select>
                                                        </td>
                                                        <td class="receiving-col" style="display: none;">
                                                            {{-- Empty for second row --}}
                                                        </td>
                                                        <td class="receiving-col" style="display: none;">
                                                            <select name="details[1][sales_order_id]" class="form-control select2 sales-order-select" style="width: 100%;">
                                                                <option value="">Select Sales Order (Select Account First)</option>
                                                            </select>
                                                        </td>
                                                        <td>
                                                            <input type="text" name="details[1][description]" class="form-control description-input" placeholder="Line description">
                                                        </td>
                                                        <td>
                                                            <input type="number" name="details[1][debit_amount]" class="form-control debit-input" step="0.01" min="0" placeholder="0.00">
                                                        </td>
                                                        <td>
                                                            <input type="number" name="details[1][credit_amount]" class="form-control credit-input" step="0.01" min="0" placeholder="0.00">
                                                        </td>
                                                        <td>
                                                            <button type="button" class="btn btn-sm btn-danger remove-row" style="display: none;">
                                                                <i class="ft-trash-2"></i>
                                                            </button>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                                <tfoot>
                                                    <tr>
                                                        <td colspan="4" class="text-right"><strong>Total Debits:</strong></td>
                                                        <td><strong id="totalDebits">0.00</strong></td>
                                                        <td></td>
                                                        <td></td>
                                                    </tr>
                                                    <tr>
                                                        <td colspan="4" class="text-right"><strong>Total Credits:</strong></td>
                                                        <td></td>
                                                        <td><strong id="totalCredits">0.00</strong></td>
                                                        <td></td>
                                                    </tr>
                                                    <tr>
                                                        <td colspan="4" class="text-right"><strong>Difference (Debit - Credit):</strong></td>
                                                        <td><strong id="difference">0.00</strong></td>
                                                        <td></td>
                                                        <td></td>
                                                    </tr>
                                                    <tr>
                                                        <td colspan="7">
                                                            <button type="button" class="btn btn-sm btn-primary" id="addRow">
                                                                <i class="ft-plus"></i> Add Row
                                                            </button>
                                                        </td>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group text-right">
                                <button type="submit" class="btn btn-primary">Create Journal Voucher</button>
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
        $(document).ready(function () {
            let rowCount = $('#journalEntriesBody tr').length;

            // Global map to store loaded RV remaining balances by RV ID
            window.rvMap = window.rvMap || {};

            // Initialize select2 with 100% width
            $('.select2').select2({ width: '100%' });

            // Toggle receiving columns
            function toggleReceivingColumns() {
                if ($('#receivingToggle').is(':checked')) {
                    $('.receiving-col').show();
                    $('#journalEntriesTable tfoot td:first-child').attr('colspan', 4);
                    $('#addRow').closest('td').attr('colspan', 7);
                    // Refresh select2 inside receiving columns so width is 100%
                    $('.receiving-col .select2').each(function() {
                        if ($(this).hasClass("select2-hidden-accessible")) {
                            $(this).select2('destroy');
                        }
                        $(this).select2({ width: '100%' });
                    });
                } else {
                    $('.receiving-col').hide();
                    $('#journalEntriesTable tfoot td:first-child').attr('colspan', 2);
                    $('#addRow').closest('td').attr('colspan', 5);
                }
            }

            $('#receivingToggle').change(function () {
                toggleReceivingColumns();
            });

            // Helper to get RV remaining amount reliably from a select
            function getRvRemainingForSelect($rvSelect) {
                if (!$rvSelect || !$rvSelect.length) return null;
                const rvId = $rvSelect.val();
                if (!rvId) return null;

                if (window.rvMap && window.rvMap[rvId] && window.rvMap[rvId].remaining_amount !== undefined) {
                    return parseFloat(window.rvMap[rvId].remaining_amount);
                }

                const selectElem = $rvSelect[0];
                if (selectElem && selectElem.selectedIndex >= 0) {
                    const opt = selectElem.options[selectElem.selectedIndex];
                    if (opt) {
                        const attr = opt.getAttribute('data-remaining-amount');
                        if (attr !== null && attr !== undefined && attr !== '') {
                            const num = parseFloat(attr);
                            if (!isNaN(num)) return num;
                        }
                    }
                }
                return null;
            }

            // Function to get active RV remaining balance for the row, or across the voucher (for adjusting SO row)
            function getActiveRvRemainingAmount($row) {
                // 1. If this row has an RV select with a selected value
                const $thisRowRv = $row ? $row.find('.receipt-voucher-select') : null;
                if ($thisRowRv && $thisRowRv.length && $thisRowRv.val()) {
                    const val = getRvRemainingForSelect($thisRowRv);
                    if (val !== null && val > 0) return val;
                }

                // 2. If this row doesn't have an RV (e.g. Row 1 SO row), get the selected RV from the voucher
                let voucherRvRemaining = null;
                $('.receipt-voucher-select').each(function() {
                    if ($(this).val()) {
                        const val = getRvRemainingForSelect($(this));
                        if (val !== null && val > 0) {
                            voucherRvRemaining = val;
                            return false; // break loop
                        }
                    }
                });

                return voucherRvRemaining;
            }

            // Standard SweetAlert Warning Popup
            let warningPopupTimeout = null;
            function showRvLimitWarning(limit) {
                if (typeof Swal !== 'undefined' && Swal.isVisible()) {
                    return; // Avoid multiple overlapping popups
                }
                if (warningPopupTimeout) clearTimeout(warningPopupTimeout);
                warningPopupTimeout = setTimeout(function() {
                    if (typeof Swal !== 'undefined' && !Swal.isVisible()) {
                        const formatted = Number(limit).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                        Swal.fire({
                            icon: 'warning',
                            title: 'Amount Exceeds RV Balance',
                            text: 'Amount cannot exceed Receipt Voucher remaining balance of ' + formatted,
                            confirmButtonColor: '#D95000'
                        });
                    }
                }, 250);
            }

            // Real-time amount validator & clamper for debit/credit inputs
            function validateAndClampInput($input) {
                const $row = $input.closest('tr');
                const remainingAmount = getActiveRvRemainingAmount($row);

                if (remainingAmount !== null && remainingAmount > 0) {
                    $input.attr('max', remainingAmount.toFixed(2));
                    const enteredVal = parseFloat($input.val()) || 0;
                    if (enteredVal > (remainingAmount + 0.001)) {
                        $input.val(remainingAmount.toFixed(2));
                        showRvLimitWarning(remainingAmount);
                    }
                }
            }

            // Cleanly update Select2 options without breaking container
            function updateSelect2Dropdown($select, items, placeholder, selectedId, isLoading = false) {
                if ($select.hasClass("select2-hidden-accessible") || $select.data('select2')) {
                    $select.select2('destroy');
                }
                $select.empty();
                $select.append(new Option(placeholder, '', false, false));

                let hasSelected = false;
                if (items && items.length > 0) {
                    items.forEach(function (item) {
                        const isSel = Boolean(selectedId && selectedId == item.id);
                        if (isSel) hasSelected = true;
                        const opt = new Option(item.text, item.id, false, isSel);
                        if (item.remaining_amount !== undefined) {
                            $(opt).attr('data-remaining-amount', item.remaining_amount);
                        }
                        $select.append(opt);
                    });
                }

                if (hasSelected && selectedId) {
                    $select.val(selectedId);
                } else {
                    $select.val('');
                }

                $select.prop('disabled', isLoading);
                $select.select2({ width: '100%' });

                if (hasSelected) {
                    $select.trigger('change');
                }
            }

            // Function to load account-specific data for a row
            function loadAccountData($row, accId, selectedRvId, selectedSoId) {
                const $rvSelect = $row.find('.receipt-voucher-select');
                const $soSelect = $row.find('.sales-order-select');

                if (!accId) {
                    if ($rvSelect.length) {
                        updateSelect2Dropdown($rvSelect, [], 'Select Receipt Voucher (Select Account First)', null);
                    }
                    if ($soSelect.length) {
                        updateSelect2Dropdown($soSelect, [], 'Select Sales Order (Select Account First)', null);
                    }
                    return;
                }

                if ($rvSelect.length) {
                    updateSelect2Dropdown($rvSelect, [], 'Loading Receipt Vouchers...', null, true);
                }
                if ($soSelect.length) {
                    updateSelect2Dropdown($soSelect, [], 'Loading Sales Orders...', null, true);
                }

                $.ajax({
                    url: '{{ route("journal-voucher.get-account-related-data") }}',
                    type: 'GET',
                    data: {
                        acc_id: accId
                    },
                    success: function (res) {
                        if (res.receipt_vouchers) {
                            res.receipt_vouchers.forEach(function (rv) {
                                window.rvMap[rv.id] = rv;
                            });
                        }

                        if ($rvSelect.length) {
                            const rvPlaceholder = (res.receipt_vouchers && res.receipt_vouchers.length > 0)
                                ? 'Select Receipt Voucher'
                                : 'No Receipt Vouchers Available';
                            updateSelect2Dropdown($rvSelect, res.receipt_vouchers || [], rvPlaceholder, selectedRvId);
                        }

                        if ($soSelect.length) {
                            const soPlaceholder = (res.sales_orders && res.sales_orders.length > 0)
                                ? 'Select Sales Order'
                                : 'No Sales Orders Available';
                            updateSelect2Dropdown($soSelect, res.sales_orders || [], soPlaceholder, selectedSoId);
                        }
                    },
                    error: function () {
                        if ($rvSelect.length) {
                            updateSelect2Dropdown($rvSelect, [], 'Error loading Receipt Vouchers', null);
                        }
                        if ($soSelect.length) {
                            updateSelect2Dropdown($soSelect, [], 'Error loading Sales Orders', null);
                        }
                    }
                });
            }

            // Handle Account selection change
            $(document).on('change', '.account-select', function () {
                const $row = $(this).closest('tr');
                const accId = $(this).val();
                loadAccountData($row, accId, null, null);
            });

            // Receipt Voucher selection handler: auto-fill and enforce max across all rows
            $(document).on('change', '.receipt-voucher-select', function () {
                const $rvSelect = $(this);
                const $row = $rvSelect.closest('tr');
                const remainingAmount = getRvRemainingForSelect($rvSelect);

                if (remainingAmount !== null && remainingAmount > 0) {
                    // Set max attribute on all entry rows
                    $('#journalEntriesBody tr').each(function() {
                        $(this).find('.debit-input, .credit-input').attr('max', remainingAmount.toFixed(2));
                    });

                    const currentDebit = parseFloat($row.find('.debit-input').val()) || 0;
                    const currentCredit = parseFloat($row.find('.credit-input').val()) || 0;

                    if (currentCredit > 0) {
                        if (currentCredit > remainingAmount) {
                            $row.find('.credit-input').val(remainingAmount.toFixed(2));
                            showRvLimitWarning(remainingAmount);
                        }
                    } else if (currentDebit > 0) {
                        if (currentDebit > remainingAmount) {
                            $row.find('.debit-input').val(remainingAmount.toFixed(2));
                            showRvLimitWarning(remainingAmount);
                        }
                    } else {
                        // If empty, auto-fill debit with the RV remaining amount
                        $row.find('.debit-input').val(remainingAmount.toFixed(2));
                    }

                    // Check other rows (e.g. Row 1 SO credit) if they already have amount exceeding RV balance
                    $('#journalEntriesBody tr').each(function() {
                        const $r = $(this);
                        if ($r[0] !== $row[0]) {
                            const d = parseFloat($r.find('.debit-input').val()) || 0;
                            const c = parseFloat($r.find('.credit-input').val()) || 0;
                            if (d > remainingAmount) {
                                $r.find('.debit-input').val(remainingAmount.toFixed(2));
                                showRvLimitWarning(remainingAmount);
                            }
                            if (c > remainingAmount) {
                                $r.find('.credit-input').val(remainingAmount.toFixed(2));
                                showRvLimitWarning(remainingAmount);
                            }
                        }
                    });
                } else {
                    $('.debit-input, .credit-input').removeAttr('max');
                }
                calculateTotals();
            });

            // Set initial state
            toggleReceivingColumns();

            // Generate JV number function
            function generateJvNumber() {
                const jvDate = $('#jv_date').val();
                if (jvDate) {
                    $.ajax({
                        url: '{{ route('journal-voucher.generate-jv-number') }}',
                        type: 'POST',
                        data: {
                            _token: '{{ csrf_token() }}',
                            jv_date: jvDate
                        },
                        success: function (response) {
                            if (response.success) {
                                $('#jv_no').val(response.jv_number);
                            }
                        }
                    });
                }
            }

            // Generate JV number on page load if date is set
            generateJvNumber();

            // Generate JV number on date change
            $('#jv_date').change(function () {
                generateJvNumber();
            });

            // Add new row
            $('#addRow').click(function () {
                const isReceiving = $('#receivingToggle').is(':checked');
                const displayStyle = isReceiving ? '' : 'display: none;';

                const newRow = `
                    <tr>
                        <td>
                            <select name="details[${rowCount}][acc_id]" class="form-control select2 account-select" required>
                                <option value="">Select Account</option>
                                @foreach ($accounts as $account)
                                    <option value="{{ $account->id }}">{{ $account->name }} ({{ $account->unique_no }})</option>
                                @endforeach
                            </select>
                        </td>
                        <td class="receiving-col" style="${displayStyle}"></td>
                        <td class="receiving-col" style="${displayStyle}"></td>
                        <td>
                            <input type="text" name="details[${rowCount}][description]" class="form-control description-input" placeholder="Line description">
                        </td>
                        <td>
                            <input type="number" name="details[${rowCount}][debit_amount]" class="form-control debit-input" step="0.01" min="0" placeholder="0.00">
                        </td>
                        <td>
                            <input type="number" name="details[${rowCount}][credit_amount]" class="form-control credit-input" step="0.01" min="0" placeholder="0.00">
                        </td>
                        <td>
                            <button type="button" class="btn btn-sm btn-danger remove-row">
                                <i class="ft-trash-2"></i>
                            </button>
                        </td>
                    </tr>
                `;
                $('#journalEntriesBody').append(newRow);
                $('#journalEntriesBody tr:last .select2').select2({ width: '100%' });
                rowCount++;
                updateRemoveButtons();
                calculateTotals();
            });

            // Remove row
            $(document).on('click', '.remove-row', function () {
                if ($('#journalEntriesBody tr').length > 2) {
                    $(this).closest('tr').remove();
                    updateRemoveButtons();
                    calculateTotals();
                }
            });

            // Force only one of debit/credit to have value & clamp against RV limit in real-time
            $(document).on('input', '.debit-input', function () {
                const $row = $(this).closest('tr');
                const debitValue = parseFloat($(this).val()) || 0;
                if (debitValue > 0) {
                    $row.find('.credit-input').val('');
                }
                validateAndClampInput($(this));
                calculateTotals();
            });

            $(document).on('input', '.credit-input', function () {
                const $row = $(this).closest('tr');
                const creditValue = parseFloat($(this).val()) || 0;
                if (creditValue > 0) {
                    $row.find('.debit-input').val('');
                }
                validateAndClampInput($(this));
                calculateTotals();
            });

            $(document).on('blur change', '.debit-input, .credit-input', function () {
                validateAndClampInput($(this));
                calculateTotals();
            });

            // Update remove buttons visibility
            function updateRemoveButtons() {
                const rowCount = $('#journalEntriesBody tr').length;
                if (rowCount > 2) {
                    $('.remove-row').show();
                } else {
                    $('.remove-row').hide();
                }
            }

            // Calculate totals
            function calculateTotals() {
                let totalDebits = 0;
                let totalCredits = 0;

                $('#journalEntriesBody tr').each(function () {
                    const debitAmount = parseFloat($(this).find('.debit-input').val()) || 0;
                    const creditAmount = parseFloat($(this).find('.credit-input').val()) || 0;

                    totalDebits += debitAmount;
                    totalCredits += creditAmount;
                });

                $('#totalDebits').text(totalDebits.toFixed(2));
                $('#totalCredits').text(totalCredits.toFixed(2));

                const difference = totalDebits - totalCredits;
                $('#difference').text(difference.toFixed(2));

                if (Math.abs(difference) > 0.01) {
                    $('#difference').css('color', 'red');
                } else {
                    $('#difference').css('color', 'green');
                }
            }

            // Form submission validation
            $('#ajaxSubmit').on('submit', function (e) {
                calculateTotals();

                const totalDebits = parseFloat($('#totalDebits').text()) || 0;
                const totalCredits = parseFloat($('#totalCredits').text()) || 0;

                let invalidLine = null;

                $('#journalEntriesBody tr').each(function (index) {
                    const debitAmount = parseFloat($(this).find('.debit-input').val()) || 0;
                    const creditAmount = parseFloat($(this).find('.credit-input').val()) || 0;

                    if ((debitAmount <= 0 && creditAmount <= 0) || (debitAmount > 0 && creditAmount > 0)) {
                        invalidLine = index + 1;
                        return false;
                    }
                });

                if (invalidLine !== null) {
                    e.preventDefault();
                    Swal.fire({
                        icon: 'error',
                        title: 'Validation Error',
                        text: 'Line ' + invalidLine + ' must contain either a debit or a credit amount greater than zero (but not both).',
                        confirmButtonColor: '#D95000'
                    });
                    return false;
                }

                if (Math.abs(totalDebits - totalCredits) > 0.01) {
                    e.preventDefault();
                    Swal.fire({
                        icon: 'error',
                        title: 'Validation Error',
                        text: 'Total debits must equal total credits. Current difference: ' + (totalDebits - totalCredits).toFixed(2),
                        confirmButtonColor: '#D95000'
                    });
                    return false;
                }

                // Check that no RV amount exceeds its remaining balance
                let rvExceeded = false;
                let rvExceededMsg = '';
                $('#journalEntriesBody tr').each(function (index) {
                    const remainingAmount = getActiveRvRemainingAmount($(this));
                    if (remainingAmount !== null && remainingAmount > 0) {
                        const debitAmount = parseFloat($(this).find('.debit-input').val()) || 0;
                        const creditAmount = parseFloat($(this).find('.credit-input').val()) || 0;
                        const enteredAmount = Math.max(debitAmount, creditAmount);
                        if (enteredAmount > (remainingAmount + 0.01)) {
                            rvExceeded = true;
                            rvExceededMsg = `Line ${index + 1}: Entered amount (${enteredAmount.toFixed(2)}) exceeds Receipt Voucher remaining balance of ${remainingAmount.toFixed(2)}.`;
                            return false;
                        }
                    }
                });

                if (rvExceeded) {
                    e.preventDefault();
                    Swal.fire({
                        icon: 'error',
                        title: 'Validation Error',
                        text: rvExceededMsg,
                        confirmButtonColor: '#D95000'
                    });
                    return false;
                }
            });

            // Initialize
            updateRemoveButtons();
            calculateTotals();
        });
    </script>
@endsection

