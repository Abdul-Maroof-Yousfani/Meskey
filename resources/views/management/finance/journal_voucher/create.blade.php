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
                                                        <th class="receiving-col" style="display: none; width: 170px;">Adjustment Type</th>
                                                        <th class="receiving-col" style="display: none; width: 220px;">Reference / SO</th>
                                                        <th class="receiving-col" style="display: none; width: 200px;">Delivery Order</th>
                                                        <th class="order-col" style="width: 200px;">Orders</th>
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
                                                                    <option value="{{ $account->id }}" data-table-name="{{ strtolower($account->table_name ?? '') }}">{{ $account->name }} ({{ $account->unique_no }}){{ $account->type_label ? " - [" . $account->type_label . "]" : "" }}</option>
                                                                @endforeach
                                                            </select>
                                                        </td>
                                                        <td class="receiving-col" style="display: none;">
                                                            <select class="form-control select2 adjustment-type-select" style="width: 100%;">
                                                                <option value="">Select Type</option>
                                                                <option value="receipt_voucher">Receipt Voucher (RV)</option>
                                                                <option value="excess_amount">Excess Amount</option>
                                                                <option value="so_do">SO / DO</option>
                                                                <option value="journal_voucher">Journal Voucher (JV)</option>
                                                            </select>
                                                        </td>
                                                        <td class="receiving-col" style="display: none;">
                                                            <select class="form-control select2 ref-doc-select" style="width: 100%;" disabled>
                                                                <option value="">Select Type First</option>
                                                            </select>
                                                        </td>
                                                        <td class="receiving-col" style="display: none;">
                                                            <select class="form-control select2 do-select" style="width: 100%;" disabled>
                                                                <option value="">N/A</option>
                                                            </select>
                                                        </td>
                                                        <td class="order-col">
                                                            <select name="details[0][order_id]" class="form-control select2 order-select" style="width: 100%;">
                                                                <option value="">Select Order (Select Account First)</option>
                                                            </select>
                                                            <input type="hidden" name="details[0][voucher_id]" class="voucher-id-input">
                                                            <input type="hidden" name="details[0][voucher_no]" class="voucher-no-input">
                                                            <input type="hidden" name="details[0][voucher_type]" class="voucher-type-input">
                                                        </td>
                                                        <td>
                                                            <input type="text" name="details[0][description]" class="form-control description-input" placeholder="Line description">
                                                            <input type="hidden" name="details[0][receipt_voucher_id]" class="receipt-voucher-id-input">
                                                            <input type="hidden" name="details[0][customer_advance_id]" class="customer-advance-id-input">
                                                            <input type="hidden" name="details[0][sales_order_id]" class="sales-order-id-input">
                                                            <input type="hidden" name="details[0][delivery_order_id]" class="delivery-order-id-input">
                                                            <input type="hidden" name="details[0][reference_jv_id]" class="reference-jv-id-input">
                                                            <input type="hidden" name="details[0][reference_jv_detail_id]" class="reference-jv-detail-id-input">
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
                                                                    <option value="{{ $account->id }}" data-table-name="{{ strtolower($account->table_name ?? '') }}">{{ $account->name }} ({{ $account->unique_no }}){{ $account->type_label ? " - [" . $account->type_label . "]" : "" }}</option>
                                                                @endforeach
                                                            </select>
                                                        </td>
                                                        <td class="receiving-col" style="display: none;">
                                                            <select class="form-control select2 adjustment-type-select" style="width: 100%;">
                                                                <option value="">Select Type</option>
                                                                <option value="receipt_voucher">Receipt Voucher (RV)</option>
                                                                <option value="excess_amount">Excess Amount</option>
                                                                <option value="so_do">SO / DO</option>
                                                                <option value="journal_voucher">Journal Voucher (JV)</option>
                                                            </select>
                                                        </td>
                                                        <td class="receiving-col" style="display: none;">
                                                            <select class="form-control select2 ref-doc-select" style="width: 100%;" disabled>
                                                                <option value="">Select Type First</option>
                                                            </select>
                                                        </td>
                                                        <td class="receiving-col" style="display: none;">
                                                            <select class="form-control select2 do-select" style="width: 100%;" disabled>
                                                                <option value="">N/A</option>
                                                            </select>
                                                        </td>
                                                        <td class="order-col">
                                                            <select name="details[1][order_id]" class="form-control select2 order-select" style="width: 100%;">
                                                                <option value="">Select Order (Select Account First)</option>
                                                            </select>
                                                            <input type="hidden" name="details[1][voucher_id]" class="voucher-id-input">
                                                            <input type="hidden" name="details[1][voucher_no]" class="voucher-no-input">
                                                            <input type="hidden" name="details[1][voucher_type]" class="voucher-type-input">
                                                        </td>
                                                        <td>
                                                            <input type="text" name="details[1][description]" class="form-control description-input" placeholder="Line description">
                                                            <input type="hidden" name="details[1][receipt_voucher_id]" class="receipt-voucher-id-input">
                                                            <input type="hidden" name="details[1][customer_advance_id]" class="customer-advance-id-input">
                                                            <input type="hidden" name="details[1][sales_order_id]" class="sales-order-id-input">
                                                            <input type="hidden" name="details[1][delivery_order_id]" class="delivery-order-id-input">
                                                            <input type="hidden" name="details[1][reference_jv_id]" class="reference-jv-id-input">
                                                            <input type="hidden" name="details[1][reference_jv_detail_id]" class="reference-jv-detail-id-input">
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
                                                        <td colspan="3" class="text-right"><strong>Total Debits:</strong></td>
                                                        <td><strong id="totalDebits">0.00</strong></td>
                                                        <td></td>
                                                        <td></td>
                                                    </tr>
                                                    <tr>
                                                        <td colspan="3" class="text-right"><strong>Total Credits:</strong></td>
                                                        <td></td>
                                                        <td><strong id="totalCredits">0.00</strong></td>
                                                        <td></td>
                                                    </tr>
                                                    <tr>
                                                        <td colspan="3" class="text-right"><strong>Difference (Debit - Credit):</strong></td>
                                                        <td><strong id="difference">0.00</strong></td>
                                                        <td></td>
                                                        <td></td>
                                                    </tr>
                                                    <tr>
                                                        <td colspan="6">
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

            window.rvMap = window.rvMap || {};
            window.advMap = window.advMap || {};
            window.soMap = window.soMap || {};
            window.jvMap = window.jvMap || {};
            window.grnMap = window.grnMap || {};
            const currentJvId = null;

            $('.select2').select2({ width: '100%' });

            function toggleReceivingColumns() {
                if ($('#receivingToggle').is(':checked')) {
                    $('.receiving-col').show();
                    $('.order-col').hide();
                    $('.order-select').val('').trigger('change.select2');
                    $('.voucher-id-input, .voucher-no-input, .voucher-type-input').val('');
                    $('#journalEntriesTable tfoot tr td:first-child').attr('colspan', 5);
                    $('#addRow').closest('td').attr('colspan', 8);
                    $('.receiving-col .select2').each(function() {
                        if ($(this).hasClass("select2-hidden-accessible")) {
                            $(this).select2('destroy');
                        }
                        $(this).select2({ width: '100%' });
                    });
                } else {
                    $('.receiving-col').hide();
                    $('.order-col').show();
                    $('.adjustment-type-select').val('').trigger('change.select2');
                    $('.ref-doc-select').empty().append(new Option('Select Type First', '')).prop('disabled', true).trigger('change.select2');
                    $('.do-select').empty().append(new Option('N/A', '')).prop('disabled', true).trigger('change.select2');
                    $('.receipt-voucher-id-input, .customer-advance-id-input, .sales-order-id-input, .delivery-order-id-input, .reference-jv-id-input, .reference-jv-detail-id-input').val('');
                    $('#journalEntriesTable tfoot tr td:first-child').attr('colspan', 3);
                    $('#addRow').closest('td').attr('colspan', 6);
                    $('.order-col .select2').each(function() {
                        if ($(this).hasClass("select2-hidden-accessible")) {
                            $(this).select2('destroy');
                        }
                        $(this).select2({ width: '100%' });
                    });
                }
            }

            $('#receivingToggle').change(function () {
                toggleReceivingColumns();
            });

            function showWarningPopup(title, text) {
                if (typeof Swal !== 'undefined' && !Swal.isVisible()) {
                    Swal.fire({
                        icon: 'warning',
                        title: title,
                        text: text,
                        confirmButtonColor: '#D95000'
                    });
                }
            }

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
                        if (item.unique_no !== undefined) {
                            $(opt).attr('data-unique-no', item.unique_no);
                        } else if (item.reference_no !== undefined) {
                            $(opt).attr('data-unique-no', item.reference_no);
                        } else if (item.jv_no !== undefined) {
                            $(opt).attr('data-unique-no', item.jv_no);
                        } else if (item.voucher_no !== undefined) {
                            $(opt).attr('data-unique-no', item.voucher_no);
                        }
                        if (item.voucher_no !== undefined) {
                            $(opt).attr('data-voucher-no', item.voucher_no);
                        }
                        if (item.detail_id !== undefined) {
                            $(opt).attr('data-detail-id', item.detail_id);
                        }
                        if (item.type !== undefined) {
                            $(opt).attr('data-type', item.type);
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

            function loadAccountData($row, accId) {
                const $orderSelect = $row.find('.order-select');

                if (!accId) {
                    $row.removeData('accountData');
                    if ($orderSelect.length) {
                        updateSelect2Dropdown($orderSelect, [], 'Select Order (Select Account First)', null);
                    }
                    populateRefDocDropdown($row);
                    return;
                }

                if ($orderSelect.length) {
                    updateSelect2Dropdown($orderSelect, [], 'Loading Orders...', null, true);
                }

                $.ajax({
                    url: '{{ route("journal-voucher.get-account-related-data") }}',
                    type: 'GET',
                    data: {
                        acc_id: accId
                    },
                    success: function (res) {
                        $row.data('accountData', res);

                        if (res.receipt_vouchers) {
                            res.receipt_vouchers.forEach(function (rv) {
                                window.rvMap[rv.id] = rv;
                            });
                        }
                        if (res.customer_advances) {
                            res.customer_advances.forEach(function (adv) {
                                window.advMap[adv.id] = adv;
                            });
                        }
                        if (res.sales_orders) {
                            res.sales_orders.forEach(function (so) {
                                window.soMap[so.id] = so;
                            });
                        }
                        if (res.journal_vouchers) {
                            res.journal_vouchers.forEach(function (jv) {
                                window.jvMap[jv.id] = jv;
                            });
                        }
                        if (res.grns) {
                            res.grns.forEach(function (grn) {
                                window.grnMap[grn.id] = grn;
                                if (grn.unique_no) {
                                    window.grnMap[grn.unique_no] = grn;
                                }
                            });
                        }

                        // Populate Order dropdown for Receiving Toggle OFF mode
                        if ($orderSelect.length) {
                            const tableName = (res.table_name || '').toLowerCase();
                            if (tableName === 'customers') {
                                const orders = res.sales_orders || [];
                                const orderPlaceholder = orders.length > 0 ? 'Select Sale Order' : 'No Sale Orders Available';
                                updateSelect2Dropdown($orderSelect, orders, orderPlaceholder, null);
                            } else if (tableName === 'suppliers') {
                                const grns = res.grns || [];
                                const grnPlaceholder = grns.length > 0 ? 'Select GRN' : 'No GRNs Available';
                                updateSelect2Dropdown($orderSelect, grns, grnPlaceholder, null);
                            } else {
                                updateSelect2Dropdown($orderSelect, [], 'No Orders Available', null);
                            }
                        }

                        // If adjustment type is already selected, populate reference dropdown
                        populateRefDocDropdown($row);
                    },
                    error: function () {
                        if ($orderSelect.length) {
                            updateSelect2Dropdown($orderSelect, [], 'Error loading Orders', null);
                        }
                    }
                });
            }

            function populateRefDocDropdown($row) {
                const type = $row.find('.adjustment-type-select').val();
                const $refSelect = $row.find('.ref-doc-select');
                const $doSelect = $row.find('.do-select');
                const accData = $row.data('accountData') || {};

                if (!type) {
                    updateSelect2Dropdown($refSelect, [], 'Select Type First', null, true);
                    $doSelect.empty().append(new Option('N/A', '')).prop('disabled', true).trigger('change.select2');
                    return;
                }

                if (type === 'receipt_voucher') {
                    const items = accData.receipt_vouchers || [];
                    const placeholder = items.length ? 'Select Receipt Voucher' : 'No Receipt Vouchers Available';
                    updateSelect2Dropdown($refSelect, items, placeholder, null);
                    $doSelect.empty().append(new Option('N/A', '')).prop('disabled', true).trigger('change.select2');
                } else if (type === 'excess_amount') {
                    const items = accData.customer_advances || [];
                    const placeholder = items.length ? 'Select Excess Amount' : 'No Excess Amounts Available';
                    updateSelect2Dropdown($refSelect, items, placeholder, null);
                    $doSelect.empty().append(new Option('N/A', '')).prop('disabled', true).trigger('change.select2');
                } else if (type === 'so_do') {
                    const items = accData.sales_orders || [];
                    const placeholder = items.length ? 'Select Sales Order' : 'No Sales Orders Available';
                    updateSelect2Dropdown($refSelect, items, placeholder, null);
                    $doSelect.empty().append(new Option('Select SO First', '')).prop('disabled', true).trigger('change.select2');
                } else if (type === 'journal_voucher') {
                    const items = accData.journal_vouchers || [];
                    const placeholder = items.length ? 'Select Journal Voucher' : 'No Credit JVs Available';
                    updateSelect2Dropdown($refSelect, items, placeholder, null);
                    $doSelect.empty().append(new Option('N/A', '')).prop('disabled', true).trigger('change.select2');
                }
            }

            function setRowDebitAmount($row, amount) {
                const num = parseFloat(amount);
                const currentCredit = parseFloat($row.find('.credit-input').val()) || 0;
                const currentDebit = parseFloat($row.find('.debit-input').val()) || 0;

                // 1. If this row already has a credit amount and no debit, it's explicitly a CREDIT row: preserve credit!
                if (currentCredit > 0 && currentDebit === 0) {
                    $row.find('.debit-input').removeAttr('max').val('');
                    calculateTotals();
                    return;
                }

                // 2. Check if other rows in the voucher have excess debit (meaning this row is needed as CREDIT)
                const $allRows = $('#journalEntriesBody tr');
                let totalOtherDebit = 0;
                let totalOtherCredit = 0;
                $allRows.not($row).each(function () {
                    totalOtherDebit += parseFloat($(this).find('.debit-input').val()) || 0;
                    totalOtherCredit += parseFloat($(this).find('.credit-input').val()) || 0;
                });

                if (totalOtherDebit > totalOtherCredit) {
                    // Counter/receiving row (CREDIT side)
                    const balanceNeeded = totalOtherDebit - totalOtherCredit;
                    $row.find('.debit-input').removeAttr('max').val('');
                    if (!$row.find('.credit-input').val() || parseFloat($row.find('.credit-input').val()) === 0) {
                        $row.find('.credit-input').val(balanceNeeded.toFixed(2));
                    }
                } else {
                    // This row is a DEBIT row (withdrawing advance)
                    if (!isNaN(num) && num > 0) {
                        $row.find('.debit-input').attr('max', num.toFixed(2)).val(num.toFixed(2));
                        $row.find('.credit-input').val('').removeAttr('max');

                        if ($allRows.length === 2) {
                            const $otherRow = $allRows.not($row);
                            const otherCredit = parseFloat($otherRow.find('.credit-input').val()) || 0;
                            const otherDebit = parseFloat($otherRow.find('.debit-input').val()) || 0;
                            if (otherCredit > 0 || otherDebit === 0) {
                                $otherRow.find('.credit-input').val(num.toFixed(2));
                                $otherRow.find('.debit-input').val('');
                            }
                        }
                    } else {
                        $row.find('.debit-input').removeAttr('max').val('');
                        if (currentCredit <= 0) {
                            $row.find('.credit-input').val('');
                        }
                    }
                }
                calculateTotals();
            }

            $(document).on('change', '.account-select', function () {
                const $row = $(this).closest('tr');
                const accId = $(this).val();
                loadAccountData($row, accId);
            });

            $(document).on('change', '.adjustment-type-select', function () {
                const $row = $(this).closest('tr');
                $row.find('.receipt-voucher-id-input, .customer-advance-id-input, .sales-order-id-input, .delivery-order-id-input, .reference-jv-id-input, .reference-jv-detail-id-input').val('');
                $row.find('.voucher-id-input, .voucher-no-input, .voucher-type-input').val('');
                $row.find('.debit-input').removeAttr('max').val('');
                $row.find('.credit-input').removeAttr('max').val('');
                populateRefDocDropdown($row);
                calculateTotals();
            });

            $(document).on('change', '.ref-doc-select', function () {
                const $refSelect = $(this);
                const $row = $refSelect.closest('tr');
                const type = $row.find('.adjustment-type-select').val();
                const val = $refSelect.val();
                const $doSelect = $row.find('.do-select');

                if (!val) {
                    $row.find('.receipt-voucher-id-input, .customer-advance-id-input, .sales-order-id-input, .delivery-order-id-input, .reference-jv-id-input, .reference-jv-detail-id-input').val('');
                    $row.find('.voucher-id-input, .voucher-no-input, .voucher-type-input').val('');
                    $row.find('.debit-input').removeAttr('max').val('');
                    if (type === 'so_do') {
                        $doSelect.empty().append(new Option('Select SO First', '')).prop('disabled', true).trigger('change.select2');
                    }
                    calculateTotals();
                    return;
                }

                const $selectedOpt = $refSelect.find('option:selected');
                const remAmountAttr = $selectedOpt.attr('data-remaining-amount');
                const remAmount = remAmountAttr !== undefined ? parseFloat(remAmountAttr) : 0;

                if (type === 'receipt_voucher') {
                    $row.find('.receipt-voucher-id-input').val(val);
                    $row.find('.voucher-id-input').val(val);
                    $row.find('.voucher-no-input').val($selectedOpt.attr('data-unique-no') || $selectedOpt.text());
                    $row.find('.voucher-type-input').val('receipt_voucher');
                    setRowDebitAmount($row, remAmount);
                } else if (type === 'excess_amount') {
                    $row.find('.customer-advance-id-input').val(val);
                    $row.find('.voucher-id-input').val(val);
                    $row.find('.voucher-no-input').val($selectedOpt.attr('data-voucher-no') || $selectedOpt.text());
                    $row.find('.voucher-type-input').val('customer_advance');
                    setRowDebitAmount($row, remAmount);
                } else if (type === 'journal_voucher') {
                    const detailId = $selectedOpt.attr('data-detail-id') || (window.jvMap && window.jvMap[val] ? window.jvMap[val].detail_id : null);
                    $row.find('.reference-jv-id-input').val(val);
                    $row.find('.reference-jv-detail-id-input').val(detailId || '');
                    $row.find('.voucher-id-input').val(val);
                    $row.find('.voucher-no-input').val($selectedOpt.attr('data-unique-no') || $selectedOpt.text());
                    $row.find('.voucher-type-input').val('journal_voucher');
                    setRowDebitAmount($row, remAmount);
                } else if (type === 'so_do') {
                    $row.find('.sales-order-id-input').val(val);
                    $doSelect.empty().append(new Option('Loading Delivery Orders...', '')).prop('disabled', true).trigger('change.select2');

                    const soDosUrl = '{{ route("journal-voucher.so-dos", ":soId") }}'.replace(':soId', val);
                    $.ajax({
                        url: soDosUrl,
                        type: 'GET',
                        data: {
                            exclude_jv_id: currentJvId
                        },
                        success: function (res) {
                            if (res.success) {
                                $doSelect.empty();
                                if (res.delivery_orders && res.delivery_orders.length > 0) {
                                    $doSelect.append(new Option('Select Delivery Order', ''));
                                    res.delivery_orders.forEach(function (doItem) {
                                        const opt = new Option(doItem.text, doItem.id);
                                        $(opt).attr('data-remaining-amount', doItem.remaining_amount);
                                        $(opt).attr('data-reference-no', doItem.reference_no);
                                        $doSelect.append(opt);
                                    });

                                    const directLabel = res.so_remaining_amount > 0.01
                                        ? `Direct SO Advance (Rem: Rs. ${res.so_formatted_remaining})`
                                        : `Direct SO (Order: ${res.so_reference_no})`;
                                    const optDirect = new Option(directLabel, 'direct_so');
                                    $(optDirect).attr('data-remaining-amount', res.so_remaining_amount);
                                    $(optDirect).attr('data-reference-no', res.so_reference_no);
                                    $doSelect.append(optDirect);

                                    $doSelect.prop('disabled', false).trigger('change.select2');
                                } else {
                                    // No DOs created for this SO -> Auto adjust direct SO advance
                                    const directText = res.so_remaining_amount > 0.01
                                        ? `No DOs (Direct SO Rem: Rs. ${res.so_formatted_remaining})`
                                        : `No DOs (Direct SO: ${res.so_reference_no})`;
                                    const opt = new Option(directText, 'direct_so', true, true);
                                    $(opt).attr('data-remaining-amount', res.so_remaining_amount);
                                    $(opt).attr('data-reference-no', res.so_reference_no);
                                    $doSelect.append(opt).prop('disabled', true).trigger('change.select2');

                                    $row.find('.delivery-order-id-input').val('');
                                    $row.find('.voucher-id-input').val(val);
                                    $row.find('.voucher-no-input').val(res.so_reference_no);
                                    $row.find('.voucher-type-input').val('sales_order');

                                    setRowDebitAmount($row, res.so_remaining_amount);
                                }
                            }
                        },
                        error: function () {
                            $doSelect.empty().append(new Option('Error loading DOs', '')).prop('disabled', true).trigger('change.select2');
                        }
                    });
                }
            });

            $(document).on('change', '.do-select', function () {
                const $doSelect = $(this);
                const $row = $doSelect.closest('tr');
                const val = $doSelect.val();

                if (!val) {
                    $row.find('.delivery-order-id-input').val('');
                    $row.find('.voucher-id-input, .voucher-no-input, .voucher-type-input').val('');
                    $row.find('.debit-input').removeAttr('max').val('');
                    calculateTotals();
                    return;
                }

                const $selectedOpt = $doSelect.find('option:selected');
                const remAmountAttr = $selectedOpt.attr('data-remaining-amount');
                const remAmount = remAmountAttr !== undefined ? parseFloat(remAmountAttr) : 0;
                const refNo = $selectedOpt.attr('data-reference-no') || $selectedOpt.text();

                if (val === 'direct_so') {
                    const soId = $row.find('.sales-order-id-input').val();
                    $row.find('.delivery-order-id-input').val('');
                    $row.find('.voucher-id-input').val(soId);
                    $row.find('.voucher-no-input').val(refNo);
                    $row.find('.voucher-type-input').val('sales_order');
                    setRowDebitAmount($row, remAmount);
                } else {
                    $row.find('.delivery-order-id-input').val(val);
                    $row.find('.voucher-id-input').val(val);
                    $row.find('.voucher-no-input').val(refNo);
                    $row.find('.voucher-type-input').val('delivery_order');
                    setRowDebitAmount($row, remAmount);
                }
            });

            // Listen for order selection change (Toggle OFF mode)
            $(document).on('change', '.order-select', function () {
                const $row = $(this).closest('tr');
                const $selected = $(this).find('option:selected');
                const val = $(this).val();

                if (val) {
                    const uniqueNo = $selected.attr('data-unique-no') || $selected.text() || '';
                    const type = $selected.attr('data-type') || '';
                    $row.find('.voucher-id-input').val(val);
                    $row.find('.voucher-no-input').val(uniqueNo);
                    $row.find('.voucher-type-input').val(type);

                    if (type === 'grn') {
                        const currentDebit = parseFloat($row.find('.debit-input').val()) || null;
                        $.ajax({
                            url: '{{ route("journal-voucher.check-grn-limit") }}',
                            type: 'POST',
                            data: {
                                _token: '{{ csrf_token() }}',
                                grn_id: val,
                                jv_id: currentJvId,
                                debit_amount: currentDebit
                            },
                            success: function (res) {
                                if (res.success) {
                                    window.grnMap[res.grn_id] = res;
                                    window.grnMap[res.unique_no] = res;
                                    $selected.attr('data-remaining-amount', res.available_amount);
                                    $row.find('.debit-input').attr('max', res.available_amount.toFixed(2));
                                    const enteredDebit = parseFloat($row.find('.debit-input').val()) || 0;
                                    if (enteredDebit > (res.available_amount + 0.001)) {
                                        $row.find('.debit-input').val(res.available_amount > 0 ? res.available_amount.toFixed(2) : '');
                                        showWarningPopup('Debit Exceeds GRN Limit (' + res.unique_no + ')', 'Debit amount cannot exceed approved GRN available balance of ' + Number(res.available_amount).toLocaleString('en-US', { minimumFractionDigits: 2 }));
                                        calculateTotals();
                                    }
                                }
                            }
                        });
                    } else {
                        $row.find('.debit-input').removeAttr('max');
                    }
                } else {
                    $row.find('.voucher-id-input, .voucher-no-input, .voucher-type-input').val('');
                    $row.find('.debit-input').removeAttr('max');
                }
            });

            function getRowLimit($row) {
                const isReceiving = $('#receivingToggle').is(':checked');
                if (isReceiving) {
                    const type = $row.find('.adjustment-type-select').val();
                    if (type === 'so_do') {
                        const doVal = $row.find('.do-select').val();
                        if (doVal) {
                            const opt = $row.find('.do-select option:selected');
                            const rem = parseFloat(opt.attr('data-remaining-amount'));
                            if (!isNaN(rem)) return { limit: rem, label: doVal === 'direct_so' ? 'Sales Order' : 'Delivery Order' };
                        }
                    } else if (type) {
                        const opt = $row.find('.ref-doc-select option:selected');
                        const rem = parseFloat(opt.attr('data-remaining-amount'));
                        let label = 'Document';
                        if (type === 'receipt_voucher') label = 'Receipt Voucher';
                        else if (type === 'excess_amount') label = 'Excess Amount';
                        else if (type === 'journal_voucher') label = 'Journal Voucher';
                        if (!isNaN(rem)) return { limit: rem, label: label };
                    }
                }
                return null;
            }

            function validateAndClampInput($input) {
                const $row = $input.closest('tr');
                const limitInfo = getRowLimit($row);

                if (limitInfo && limitInfo.limit !== null && limitInfo.limit > 0) {
                    $input.attr('max', limitInfo.limit.toFixed(2));
                    const enteredVal = parseFloat($input.val()) || 0;
                    if (enteredVal > (limitInfo.limit + 0.001)) {
                        $input.val(limitInfo.limit.toFixed(2));
                        const formatted = Number(limitInfo.limit).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                        showWarningPopup(`Amount Exceeds ${limitInfo.label} Balance`, `Amount cannot exceed ${limitInfo.label} remaining balance of ${formatted}`);
                    }
                }
            }

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
            generateJvNumber();

            $('#jv_date').change(function () {
                generateJvNumber();
            });

            // Add new row
            $('#addRow').click(function () {
                const isReceiving = $('#receivingToggle').is(':checked');
                const receivingDisplayStyle = isReceiving ? '' : 'display: none;';
                const orderDisplayStyle = isReceiving ? 'display: none;' : '';

                const newRow = `
                    <tr>
                        <td>
                            <select name="details[${rowCount}][acc_id]" class="form-control select2 account-select" required>
                                <option value="">Select Account</option>
                                @foreach ($accounts as $account)
                                    <option value="{{ $account->id }}" data-table-name="{{ strtolower($account->table_name ?? '') }}">{{ $account->name }} ({{ $account->unique_no }}){{ $account->type_label ? " - [" . $account->type_label . "]" : "" }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td class="receiving-col" style="${receivingDisplayStyle}">
                            <select class="form-control select2 adjustment-type-select" style="width: 100%;">
                                <option value="">Select Type</option>
                                <option value="receipt_voucher">Receipt Voucher (RV)</option>
                                <option value="excess_amount">Excess Amount</option>
                                <option value="so_do">SO / DO</option>
                                <option value="journal_voucher">Journal Voucher (JV)</option>
                            </select>
                        </td>
                        <td class="receiving-col" style="${receivingDisplayStyle}">
                            <select class="form-control select2 ref-doc-select" style="width: 100%;" disabled>
                                <option value="">Select Type First</option>
                            </select>
                        </td>
                        <td class="receiving-col" style="${receivingDisplayStyle}">
                            <select class="form-control select2 do-select" style="width: 100%;" disabled>
                                <option value="">N/A</option>
                            </select>
                        </td>
                        <td class="order-col" style="${orderDisplayStyle}">
                            <select name="details[${rowCount}][order_id]" class="form-control select2 order-select" style="width: 100%;">
                                <option value="">Select Order (Select Account First)</option>
                            </select>
                            <input type="hidden" name="details[${rowCount}][voucher_id]" class="voucher-id-input">
                            <input type="hidden" name="details[${rowCount}][voucher_no]" class="voucher-no-input">
                            <input type="hidden" name="details[${rowCount}][voucher_type]" class="voucher-type-input">
                        </td>
                        <td>
                            <input type="text" name="details[${rowCount}][description]" class="form-control description-input" placeholder="Line description">
                            <input type="hidden" name="details[${rowCount}][receipt_voucher_id]" class="receipt-voucher-id-input">
                            <input type="hidden" name="details[${rowCount}][customer_advance_id]" class="customer-advance-id-input">
                            <input type="hidden" name="details[${rowCount}][sales_order_id]" class="sales-order-id-input">
                            <input type="hidden" name="details[${rowCount}][delivery_order_id]" class="delivery-order-id-input">
                            <input type="hidden" name="details[${rowCount}][reference_jv_id]" class="reference-jv-id-input">
                            <input type="hidden" name="details[${rowCount}][reference_jv_detail_id]" class="reference-jv-detail-id-input">
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

            function updateRemoveButtons() {
                const count = $('#journalEntriesBody tr').length;
                if (count > 2) {
                    $('.remove-row').show();
                } else {
                    $('.remove-row').hide();
                }
            }

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
                    e.stopImmediatePropagation();
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
                    e.stopImmediatePropagation();
                    Swal.fire({
                        icon: 'error',
                        title: 'Validation Error',
                        text: 'Total debits must equal total credits. Current difference: ' + (totalDebits - totalCredits).toFixed(2),
                        confirmButtonColor: '#D95000'
                    });
                    return false;
                }
            });

            // Set initial state
            toggleReceivingColumns();
            updateRemoveButtons();
            calculateTotals();
        });
    </script>
@endsection

