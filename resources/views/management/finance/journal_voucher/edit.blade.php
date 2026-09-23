@extends('management.layouts.master')
@section('title')
    Edit Journal Voucher
@endsection
@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">Edit Journal Voucher #{{ $journalVoucher->jv_no }}</h4>
                        <a href="{{ route('journal-voucher.index') }}" class="btn btn-sm btn-primary">Back</a>
                    </div>
                    <div class="card-body">
                        @if(strtolower($journalVoucher->am_approval_status ?? $journalVoucher->jv_status ?? '') === 'reverted')
                            @php
                                $latestLog = $journalVoucher->approvalLogs()->latest()->first();
                            @endphp
                            <div class="alert alert-warning mb-3">
                                <i class="fa fa-undo me-2"></i>
                                <strong>Status: Reverted</strong> - This Journal Voucher has been reverted. Please make the necessary changes and save to resubmit for approval.
                                @if($latestLog && !empty(trim($latestLog->comments ?? '')))
                                    <div class="mt-1 small">
                                        <strong>Revert Comment ({{ $latestLog->user->name ?? 'Approver' }}):</strong> {{ $latestLog->comments }}
                                    </div>
                                @endif
                            </div>
                        @endif

                        <form id="ajaxSubmit" action="{{ route('journal-voucher.update', $journalVoucher->id) }}">
                            @csrf
                            @method('PUT')

                            <input type="hidden" id="url" value="{{ route('journal-voucher.index') }}">
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="jv_date">Date</label>
                                        <input type="date" name="jv_date" id="jv_date" class="form-control" 
                                            value="{{ $journalVoucher->jv_date->format('Y-m-d') }}" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="jv_no">JV Number</label>
                                        <input type="text" name="jv_no" id="jv_no" class="form-control" 
                                            value="{{ $journalVoucher->jv_no }}" readonly>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label for="description">Description</label>
                                        <textarea name="description" id="description" class="form-control" rows="3">{{ $journalVoucher->description }}</textarea>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <label class="mb-0">Journal Entries</label>
                                            <div class="custom-control custom-switch">
                                                @php
                                                    $isReceiving = false;
                                                    foreach($journalVoucher->journalVoucherDetails as $detail) {
                                                        if(!empty($detail->receipt_voucher_id)) {
                                                            $isReceiving = true;
                                                            break;
                                                        }
                                                        if(!empty($detail->sales_order_id) && empty($detail->voucher_type)) {
                                                            $isReceiving = true;
                                                            break;
                                                        }
                                                    }
                                                @endphp
                                                <input type="checkbox" class="custom-control-input" id="receivingToggle" {{ $isReceiving ? 'checked' : '' }}>
                                                <label class="custom-control-label" for="receivingToggle">Receiving</label>
                                            </div>
                                        </div>
                                        <div class="table-responsive">
                                            <table class="table table-bordered" id="journalEntriesTable">
                                                <thead>
                                                    <tr>
                                                        <th>Account</th>
                                                        <th class="receiving-col" style="{{ $isReceiving ? '' : 'display: none;' }}; width: 200px;">Receipt Voucher</th>
                                                        <th class="receiving-col" style="{{ $isReceiving ? '' : 'display: none;' }}; width: 200px;">Sales order</th>
                                                        <th class="order-col" style="{{ $isReceiving ? 'display: none;' : '' }}; width: 200px;">Orders</th>
                                                        <th>Description</th>
                                                        <th>Debit</th>
                                                        <th>Credit</th>
                                                        <th>Action</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="journalEntriesBody">
                                                    @foreach ($journalVoucher->journalVoucherDetails as $index => $detail)
                                                        <tr>
                                                            <td>
                                                                <select name="details[{{ $index }}][acc_id]" class="form-control select2 account-select" required>
                                                                    <option value="">Select Account</option>
                                                                    @foreach ($accounts as $account)
                                                                        <option value="{{ $account->id }}" 
                                                                            data-table-name="{{ strtolower($account->table_name ?? '') }}"
                                                                            {{ $detail->acc_id == $account->id ? 'selected' : '' }}>
                                                                            {{ $account->name }} ({{ $account->unique_no }})
                                                                        </option>
                                                                    @endforeach
                                                                </select>
                                                            </td>
                                                            <td class="receiving-col" style="{{ $isReceiving ? '' : 'display: none;' }}">
                                                                @if($index == 0)
                                                                    <select name="details[{{ $index }}][receipt_voucher_id]" class="form-control select2 receipt-voucher-select" style="width: 100%;">
                                                                        <option value="">Select Receipt Voucher</option>
                                                                        @if(isset($rowRvs[$index]))
                                                                            @foreach ($rowRvs[$index] as $rv)
                                                                                <option value="{{ $rv['id'] }}" 
                                                                                    data-remaining-amount="{{ $rv['remaining_amount'] }}"
                                                                                    @selected($detail->receipt_voucher_id == $rv['id'])>
                                                                                    {{ $rv['text'] }}
                                                                                </option>
                                                                            @endforeach
                                                                        @endif
                                                                    </select>
                                                                @else
                                                                    {{-- Empty for other rows --}}
                                                                @endif
                                                            </td>
                                                            <td class="receiving-col" style="{{ $isReceiving ? '' : 'display: none;' }}">
                                                                @if($index == 1)
                                                                    <select name="details[{{ $index }}][sales_order_id]" class="form-control select2 sales-order-select" style="width: 100%;">
                                                                        <option value="">Select Sales Order</option>
                                                                        @if(isset($rowSos[$index]))
                                                                            @foreach ($rowSos[$index] as $so)
                                                                                <option value="{{ $so['id'] }}" 
                                                                                    @selected($detail->sales_order_id == $so['id'])>
                                                                                    {{ $so['text'] }}
                                                                                </option>
                                                                            @endforeach
                                                                        @endif
                                                                    </select>
                                                                @else
                                                                    {{-- Empty for other rows --}}
                                                                @endif
                                                            </td>
                                                            <td class="order-col" style="{{ $isReceiving ? 'display: none;' : '' }}">
                                                                <select name="details[{{ $index }}][order_id]" class="form-control select2 order-select" style="width: 100%;">
                                                                    @php
                                                                        $acc = $accounts->firstWhere('id', $detail->acc_id) ?? $detail->account;
                                                                        $tableName = strtolower($acc->table_name ?? '');
                                                                        $orderCount = isset($rowOrders[$index]) ? count($rowOrders[$index]) : 0;
                                                                        if ($tableName === 'customers') {
                                                                            $orderPlaceholder = $orderCount > 0 ? 'Select Sale Order' : 'No Sale Orders Available';
                                                                        } elseif ($tableName === 'suppliers') {
                                                                            $orderPlaceholder = $orderCount > 0 ? 'Select GRN' : 'No GRNs Available';
                                                                        } elseif (!empty($detail->acc_id)) {
                                                                            $orderPlaceholder = 'No Orders Available';
                                                                        } else {
                                                                            $orderPlaceholder = 'Select Order (Select Account First)';
                                                                        }
                                                                    @endphp
                                                                    <option value="">{{ $orderPlaceholder }}</option>
                                                                    @if(isset($rowOrders[$index]))
                                                                        @foreach ($rowOrders[$index] as $order)
                                                                            @php
                                                                                $isSelected = ($detail->voucher_id && $detail->voucher_id == $order['id'])
                                                                                    || ($detail->sales_order_id && $detail->sales_order_id == $order['id'])
                                                                                    || ($detail->voucher_no && $detail->voucher_no == ($order['unique_no'] ?? ''));
                                                                            @endphp
                                                                            <option value="{{ $order['id'] }}" 
                                                                                data-unique-no="{{ $order['unique_no'] ?? ($order['reference_no'] ?? '') }}"
                                                                                data-type="{{ $order['type'] ?? '' }}"
                                                                                data-remaining-amount="{{ $order['remaining_amount'] ?? '' }}"
                                                                                @selected($isSelected)>
                                                                                {{ $order['text'] }}
                                                                            </option>
                                                                        @endforeach
                                                                    @endif
                                                                </select>
                                                                <input type="hidden" name="details[{{ $index }}][voucher_id]" class="voucher-id-input" value="{{ $detail->voucher_id }}">
                                                                <input type="hidden" name="details[{{ $index }}][voucher_no]" class="voucher-no-input" value="{{ $detail->voucher_no }}">
                                                                <input type="hidden" name="details[{{ $index }}][voucher_type]" class="voucher-type-input" value="{{ $detail->voucher_type }}">
                                                            </td>
                                                            <td>
                                                                <input type="text" name="details[{{ $index }}][description]" class="form-control description-input" placeholder="Line description" value="{{ $detail->description }}">
                                                            </td>
                                                            <td>
                                                                <input type="number" name="details[{{ $index }}][debit_amount]" 
                                                                    class="form-control debit-input" step="0.01" min="0" 
                                                                    value="{{ $detail->debit_amount > 0 ? $detail->debit_amount : '' }}" placeholder="0.00">
                                                            </td>
                                                            <td>
                                                                <input type="number" name="details[{{ $index }}][credit_amount]" 
                                                                    class="form-control credit-input" step="0.01" min="0" 
                                                                    value="{{ $detail->credit_amount > 0 ? $detail->credit_amount : '' }}" placeholder="0.00">
                                                            </td>
                                                            <td>
                                                                <button type="button" class="btn btn-sm btn-danger remove-row" 
                                                                    style="display: {{ count($journalVoucher->journalVoucherDetails) > 2 ? 'block' : 'none' }};">
                                                                    <i class="ft-trash-2"></i>
                                                                </button>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                                <tfoot>
                                                    <tr>
                                                        <td colspan="{{ $isReceiving ? 4 : 3 }}" class="text-right"><strong>Total Debits:</strong></td>
                                                        <td><strong id="totalDebits">0.00</strong></td>
                                                        <td></td>
                                                        <td></td>
                                                    </tr>
                                                    <tr>
                                                        <td colspan="{{ $isReceiving ? 4 : 3 }}" class="text-right"><strong>Total Credits:</strong></td>
                                                        <td></td>
                                                        <td><strong id="totalCredits">0.00</strong></td>
                                                        <td></td>
                                                    </tr>
                                                    <tr>
                                                        <td colspan="{{ $isReceiving ? 4 : 3 }}" class="text-right"><strong>Difference (Debit - Credit):</strong></td>
                                                        <td><strong id="difference">0.00</strong></td>
                                                        <td></td>
                                                        <td></td>
                                                    </tr>
                                                    <tr>
                                                        <td colspan="{{ $isReceiving ? 7 : 6 }}">
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
                                <button type="submit" class="btn btn-primary">Update Journal Voucher</button>
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
            @if(isset($rowRvs))
                @foreach($rowRvs as $rvList)
                    @foreach($rvList as $rvItem)
                        window.rvMap[{{ $rvItem['id'] }}] = {
                            id: {{ $rvItem['id'] }},
                            remaining_amount: {{ $rvItem['remaining_amount'] }},
                            text: "{{ addslashes($rvItem['text']) }}"
                        };
                    @endforeach
                @endforeach
            @endif

            // Global map to store loaded GRN limits by GRN ID and unique number
            window.grnMap = window.grnMap || {};
            @if(isset($rowOrders))
                @foreach($rowOrders as $orderList)
                    @foreach($orderList as $orderItem)
                        @if(($orderItem['type'] ?? '') === 'grn')
                            window.grnMap[{{ $orderItem['id'] }}] = {
                                id: {{ $orderItem['id'] }},
                                unique_no: "{{ addslashes($orderItem['unique_no'] ?? '') }}",
                                remaining_amount: {{ $orderItem['remaining_amount'] ?? 0 }},
                                text: "{{ addslashes($orderItem['text'] ?? '') }}"
                            };
                            @if(!empty($orderItem['unique_no']))
                                window.grnMap["{{ addslashes($orderItem['unique_no']) }}"] = window.grnMap[{{ $orderItem['id'] }}];
                            @endif
                        @endif
                    @endforeach
                @endforeach
            @endif

            const currentJvId = '{{ $journalVoucher->id }}';

            // Initialize select2 with 100% width
            $('.select2').select2({ width: '100%' });

            // Toggle receiving columns
            function toggleReceivingColumns() {
                if ($('#receivingToggle').is(':checked')) {
                    $('.receiving-col').show();
                    $('.order-col').hide();
                    $('#journalEntriesTable tfoot td.text-right').attr('colspan', 4);
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
                    $('.order-col').show();
                    $('#journalEntriesTable tfoot td.text-right').attr('colspan', 3);
                    $('#addRow').closest('td').attr('colspan', 6);
                    // Refresh select2 inside order columns so width is 100%
                    $('.order-col .select2').each(function() {
                        if ($(this).hasClass("select2-hidden-accessible")) {
                            $(this).select2('destroy');
                        }
                        $(this).select2({ width: '100%' });
                    });
                }
            }

            $('#receivingToggle').change(function () {
                if ($(this).is(':checked')) {
                    $('.order-select').val('').trigger('change');
                    $('.voucher-id-input, .voucher-no-input, .voucher-type-input').val('');
                } else {
                    $('.receipt-voucher-select').val('').trigger('change');
                    $('.sales-order-select').val('').trigger('change');
                }
                toggleReceivingColumns();
            });

            // Helper to get RV remaining amount reliably
            function getRvRemainingForSelect($rvSelect) {
                if (!$rvSelect || !$rvSelect.length) return null;
                const rvId = $rvSelect.val();
                if (!rvId) return null;

                if (window.rvMap && window.rvMap[rvId] && window.rvMap[rvId].remaining_amount !== undefined) {
                    const num = parseFloat(window.rvMap[rvId].remaining_amount);
                    if (!isNaN(num)) return num;
                }

                const $opt = $rvSelect.find('option:selected');
                if ($opt.length) {
                    const attr = $opt.attr('data-remaining-amount') || $opt.data('remaining-amount');
                    if (attr !== null && attr !== undefined && attr !== '') {
                        const num = parseFloat(attr);
                        if (!isNaN(num)) return num;
                    }
                    const text = $opt.text();
                    const match = text.match(/(?:Rem|Current):\s*([\d,]+(?:\.\d+)?)/i);
                    if (match && match[1]) {
                        const num = parseFloat(match[1].replace(/,/g, ''));
                        if (!isNaN(num)) return num;
                    }
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

            // Standard SweetAlert Warning Popup for RV
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

            // Standard SweetAlert Warning Popup for GRN
            let grnWarningTimeout = null;
            function showGrnLimitWarning(limit, grnNo) {
                if (typeof Swal !== 'undefined' && Swal.isVisible()) {
                    return; // Avoid multiple overlapping popups
                }
                if (grnWarningTimeout) clearTimeout(warningPopupTimeout);
                grnWarningTimeout = setTimeout(function() {
                    if (typeof Swal !== 'undefined' && !Swal.isVisible()) {
                        const formatted = Number(limit).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                        const title = grnNo ? 'Debit Exceeds GRN Limit (' + grnNo + ')' : 'Debit Exceeds GRN Limit';
                        Swal.fire({
                            icon: 'warning',
                            title: title,
                            text: 'Debit amount cannot exceed approved GRN available balance of ' + formatted,
                            confirmButtonColor: '#D95000'
                        });
                    }
                }, 250);
            }

            // Helper to get GRN info and limit reliably from order select
            function getGrnInfoForSelect($orderSelect) {
                if (!$orderSelect || !$orderSelect.length) return null;
                const orderId = $orderSelect.val();
                if (!orderId) return null;

                const $opt = $orderSelect.find('option:selected');
                const type = $opt.attr('data-type') || $orderSelect.closest('tr').find('.voucher-type-input').val();
                if (type !== 'grn') return null;

                let remaining = null;
                if (window.grnMap && window.grnMap[orderId] && window.grnMap[orderId].remaining_amount !== undefined) {
                    const num = parseFloat(window.grnMap[orderId].remaining_amount);
                    if (!isNaN(num)) remaining = num;
                }
                if (remaining === null) {
                    const uniqueNo = $opt.attr('data-unique-no');
                    if (uniqueNo && window.grnMap && window.grnMap[uniqueNo] && window.grnMap[uniqueNo].remaining_amount !== undefined) {
                        const num = parseFloat(window.grnMap[uniqueNo].remaining_amount);
                        if (!isNaN(num)) remaining = num;
                    }
                }
                if (remaining === null) {
                    const attr = $opt.attr('data-remaining-amount') || $opt.data('remaining-amount');
                    if (attr !== null && attr !== undefined && attr !== '') {
                        const num = parseFloat(attr);
                        if (!isNaN(num)) remaining = num;
                    }
                }
                if (remaining === null) {
                    const text = $opt.text();
                    const match = text.match(/Rem:\s*([\d,]+(?:\.\d+)?)/i);
                    if (match && match[1]) {
                        const num = parseFloat(match[1].replace(/,/g, ''));
                        if (!isNaN(num)) remaining = num;
                    }
                }

                const uniqueNo = $opt.attr('data-unique-no') || $opt.text();
                return {
                    id: orderId,
                    unique_no: uniqueNo,
                    remaining_amount: remaining
                };
            }

            // Real-time amount validator & clamper for debit/credit inputs
            function validateAndClampInput($input) {
                const $row = $input.closest('tr');
                const isReceiving = $('#receivingToggle').is(':checked');

                if (isReceiving) {
                    const remainingAmount = getActiveRvRemainingAmount($row);
                    if (remainingAmount !== null && remainingAmount > 0) {
                        $input.attr('max', remainingAmount.toFixed(2));
                        const enteredVal = parseFloat($input.val()) || 0;
                        if (enteredVal > (remainingAmount + 0.001)) {
                            $input.val(remainingAmount.toFixed(2));
                            showRvLimitWarning(remainingAmount);
                        }
                    }
                } else if ($input.hasClass('debit-input')) {
                    const grnInfo = getGrnInfoForSelect($row.find('.order-select'));
                    if (grnInfo && grnInfo.id) {
                        const remaining = grnInfo.remaining_amount;
                        if (remaining !== null && !isNaN(remaining)) {
                            $input.attr('max', Number(remaining).toFixed(2));
                            const enteredVal = parseFloat($input.val()) || 0;
                            if (enteredVal > (remaining + 0.001)) {
                                $input.val(remaining > 0 ? Number(remaining).toFixed(2) : '');
                                showGrnLimitWarning(remaining, grnInfo.unique_no);
                            }
                        }
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
                        if (item.unique_no !== undefined) {
                            $(opt).attr('data-unique-no', item.unique_no);
                        } else if (item.reference_no !== undefined) {
                            $(opt).attr('data-unique-no', item.reference_no);
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

            // Function to load account-specific data for a row
            function loadAccountData($row, accId, selectedRvId, selectedSoId, selectedOrderId) {
                const $rvSelect = $row.find('.receipt-voucher-select');
                const $soSelect = $row.find('.sales-order-select');
                const $orderSelect = $row.find('.order-select');

                if (!accId) {
                    if ($rvSelect.length) {
                        updateSelect2Dropdown($rvSelect, [], 'Select Receipt Voucher (Select Account First)', null);
                    }
                    if ($soSelect.length) {
                        updateSelect2Dropdown($soSelect, [], 'Select Sales Order (Select Account First)', null);
                    }
                    if ($orderSelect.length) {
                        updateSelect2Dropdown($orderSelect, [], 'Select Order (Select Account First)', null);
                    }
                    return;
                }

                if ($rvSelect.length) {
                    updateSelect2Dropdown($rvSelect, [], 'Loading Receipt Vouchers...', null, true);
                }
                if ($soSelect.length) {
                    updateSelect2Dropdown($soSelect, [], 'Loading Sales Orders...', null, true);
                }
                if ($orderSelect.length) {
                    const selectedOpt = $row.find('.account-select option:selected');
                    const tbl = (selectedOpt.attr('data-table-name') || '').toLowerCase();
                    let loadingMsg = 'Loading Orders...';
                    if (tbl === 'suppliers') {
                        loadingMsg = 'Loading GRNs...';
                    } else if (tbl === 'customers') {
                        loadingMsg = 'Loading Sale Orders...';
                    }
                    updateSelect2Dropdown($orderSelect, [], loadingMsg, null, true);
                }

                $.ajax({
                    url: '{{ route("journal-voucher.get-account-related-data") }}',
                    type: 'GET',
                    data: {
                        acc_id: accId,
                        jv_id: '{{ $journalVoucher->id }}'
                    },
                    success: function (res) {
                        if (res.receipt_vouchers) {
                            res.receipt_vouchers.forEach(function (rv) {
                                window.rvMap[rv.id] = rv;
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

                        if ($orderSelect.length) {
                            const tableName = (res.table_name || '').toLowerCase();
                            if (tableName === 'customers') {
                                const orders = res.sales_orders || [];
                                const orderPlaceholder = orders.length > 0
                                    ? 'Select Sale Order'
                                    : 'No Sale Orders Available';
                                updateSelect2Dropdown($orderSelect, orders, orderPlaceholder, selectedOrderId);
                            } else if (tableName === 'suppliers') {
                                const grns = res.grns || [];
                                const grnPlaceholder = grns.length > 0
                                    ? 'Select GRN'
                                    : 'No GRNs Available';
                                updateSelect2Dropdown($orderSelect, grns, grnPlaceholder, selectedOrderId);
                            } else {
                                updateSelect2Dropdown($orderSelect, [], 'No Orders Available', null);
                            }
                        }
                    },
                    error: function () {
                        if ($rvSelect.length) {
                            updateSelect2Dropdown($rvSelect, [], 'Error loading Receipt Vouchers', null);
                        }
                        if ($soSelect.length) {
                            updateSelect2Dropdown($soSelect, [], 'Error loading Sales Orders', null);
                        }
                        if ($orderSelect.length) {
                            updateSelect2Dropdown($orderSelect, [], 'Error loading Orders', null);
                        }
                    }
                });
            }

            // Handle Account selection change
            $(document).on('change', '.account-select', function () {
                const $row = $(this).closest('tr');
                const accId = $(this).val();
                loadAccountData($row, accId, null, null, null);
            });

            // Listen for order selection change to populate voucher columns & check GRN limit
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
                                jv_id: typeof currentJvId !== 'undefined' ? currentJvId : null,
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
                                        showGrnLimitWarning(res.available_amount, res.unique_no);
                                        calculateTotals();
                                    }
                                }
                            }
                        });
                    } else {
                        $row.find('.debit-input').removeAttr('max');
                    }
                } else {
                    $row.find('.voucher-id-input').val('');
                    $row.find('.voucher-no-input').val('');
                    $row.find('.voucher-type-input').val('');
                    $row.find('.debit-input').removeAttr('max');
                }
            });

            // Receipt Voucher selection handler: auto-fill and enforce max across all rows
            $(document).on('change', '.receipt-voucher-select', function () {
                const $rvSelect = $(this);
                const $row = $rvSelect.closest('tr');
                const rvId = $rvSelect.val();
                const remainingAmount = getRvRemainingForSelect($rvSelect);

                if (rvId && remainingAmount !== null && !isNaN(remainingAmount)) {
                    if (remainingAmount > 0) {
                        // Set max attribute on all entry rows
                        $('#journalEntriesBody tr').each(function() {
                            $(this).find('.debit-input, .credit-input').attr('max', remainingAmount.toFixed(2));
                        });
                    }

                    // Update this row's debit amount to the selected RV remaining amount
                    $row.find('.debit-input').val(remainingAmount.toFixed(2));
                    $row.find('.credit-input').val('');

                    // In a standard 2-row Receiving voucher (Row 0: RV debit, Row 1: SO credit),
                    // also auto-update Row 1's credit amount to match the new RV amount
                    const $allRows = $('#journalEntriesBody tr');
                    if ($allRows.length === 2) {
                        const $otherRow = $allRows.not($row);
                        const otherDebit = parseFloat($otherRow.find('.debit-input').val()) || 0;
                        const otherCredit = parseFloat($otherRow.find('.credit-input').val()) || 0;
                        if (otherCredit > 0 || $otherRow.find('.sales-order-select').length > 0 || otherDebit === 0) {
                            $otherRow.find('.credit-input').val(remainingAmount.toFixed(2));
                            $otherRow.find('.debit-input').val('');
                        }
                    } else {
                        // If more than 2 rows, check other rows if their amount exceeds RV limit
                        $allRows.each(function() {
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
                    }
                } else if (!rvId) {
                    // If cleared/deselected, reset max and clear row inputs
                    $('.debit-input, .credit-input').removeAttr('max');
                    $row.find('.debit-input').val('');
                    $row.find('.credit-input').val('');
                } else {
                    $('.debit-input, .credit-input').removeAttr('max');
                }
                calculateTotals();
            });

            // Set initial state
            toggleReceivingColumns();

            // Add new row
            $('#addRow').click(function () {
                const receivingDisplayStyle = $('#receivingToggle').is(':checked') ? '' : 'display: none;';
                const orderDisplayStyle = $('#receivingToggle').is(':checked') ? 'display: none;' : '';

                const newRow = `
                    <tr>
                        <td>
                            <select name="details[${rowCount}][acc_id]" class="form-control select2 account-select" required>
                                <option value="">Select Account</option>
                                @foreach ($accounts as $account)
                                    <option value="{{ $account->id }}" data-table-name="{{ strtolower($account->table_name ?? '') }}">{{ $account->name }} ({{ $account->unique_no }})</option>
                                @endforeach
                            </select>
                        </td>
                        <td class="receiving-col" style="${receivingDisplayStyle}"></td>
                        <td class="receiving-col" style="${receivingDisplayStyle}"></td>
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

                // If debit was changed on a GRN row, do server-side verification check
                const $input = $(this);
                if ($input.hasClass('debit-input') && !$('#receivingToggle').is(':checked')) {
                    const $row = $input.closest('tr');
                    const grnInfo = getGrnInfoForSelect($row.find('.order-select'));
                    const debitVal = parseFloat($input.val()) || 0;
                    if (grnInfo && grnInfo.id && debitVal > 0) {
                        $.ajax({
                            url: '{{ route("journal-voucher.check-grn-limit") }}',
                            type: 'POST',
                            data: {
                                _token: '{{ csrf_token() }}',
                                grn_id: grnInfo.id,
                                jv_id: typeof currentJvId !== 'undefined' ? currentJvId : null,
                                debit_amount: debitVal
                            },
                            success: function (res) {
                                if (res.success) {
                                    window.grnMap[res.grn_id] = res;
                                    window.grnMap[res.unique_no] = res;
                                    $input.attr('max', res.available_amount.toFixed(2));
                                    if (res.is_exceeded) {
                                        $input.val(res.available_amount > 0 ? res.available_amount.toFixed(2) : '');
                                        showGrnLimitWarning(res.available_amount, res.unique_no);
                                        calculateTotals();
                                    }
                                }
                            }
                        });
                    }
                }
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

                // Check that no RV amount exceeds its remaining balance (when Receiving is active)
                if ($('#receivingToggle').is(':checked')) {
                    let rvExceeded = false;
                    let rvExceededMsg = '';
                    $('#journalEntriesBody tr').each(function (index) {
                        const $rvSelect = $(this).find('.receipt-voucher-select');
                        const remainingAmount = getRvRemainingForSelect($rvSelect);
                        if (remainingAmount !== null && remainingAmount > 0) {
                            const debitAmount = parseFloat($(this).find('.debit-input').val()) || 0;
                            const creditAmount = parseFloat($(this).find('.credit-input').val()) || 0;
                            const enteredAmount = Math.max(debitAmount, creditAmount);
                            if (enteredAmount > (remainingAmount + 0.01)) {
                                rvExceeded = true;
                                const rvText = $rvSelect.find('option:selected').text();
                                rvExceededMsg = `Line ${index + 1}: Entered amount (${enteredAmount.toFixed(2)}) exceeds Receipt Voucher (${rvText}) remaining balance of ${remainingAmount.toFixed(2)}.`;
                                return false;
                            }
                        }
                    });

                    if (rvExceeded) {
                        e.preventDefault();
                        e.stopImmediatePropagation();
                        Swal.fire({
                            icon: 'error',
                            title: 'Validation Error',
                            text: rvExceededMsg,
                            confirmButtonColor: '#D95000'
                        });
                        return false;
                    }
                } else {
                    // Check that no GRN debit exceeds its approved limit
                    let grnExceeded = false;
                    let grnExceededMsg = '';
                    const grnDebitsEntered = {};

                    $('#journalEntriesBody tr').each(function (index) {
                        const $orderSelect = $(this).find('.order-select');
                        const grnInfo = getGrnInfoForSelect($orderSelect);
                        if (grnInfo && grnInfo.id) {
                            const debitAmount = parseFloat($(this).find('.debit-input').val()) || 0;
                            if (debitAmount > 0) {
                                grnDebitsEntered[grnInfo.id] = grnDebitsEntered[grnInfo.id] || {
                                    unique_no: grnInfo.unique_no,
                                    remaining_amount: grnInfo.remaining_amount,
                                    totalDebit: 0,
                                    line: index + 1
                                };
                                grnDebitsEntered[grnInfo.id].totalDebit += debitAmount;
                            }
                        }
                    });

                    for (const gid in grnDebitsEntered) {
                        const item = grnDebitsEntered[gid];
                        if (item.remaining_amount !== null && !isNaN(item.remaining_amount)) {
                            if (item.totalDebit > (item.remaining_amount + 0.01)) {
                                grnExceeded = true;
                                grnExceededMsg = `Line ${item.line}: Entered debit amount (${item.totalDebit.toFixed(2)}) for GRN ${item.unique_no} exceeds approved balance of ${Number(item.remaining_amount).toFixed(2)}.`;
                                break;
                            }
                        }
                    }

                    if (grnExceeded) {
                        e.preventDefault();
                        e.stopImmediatePropagation();
                        Swal.fire({
                            icon: 'error',
                            title: 'Validation Error',
                            text: grnExceededMsg,
                            confirmButtonColor: '#D95000'
                        });
                        return false;
                    }
                }
            });

            // Initialize
            updateRemoveButtons();
            calculateTotals();
        });
    </script>
@endsection

