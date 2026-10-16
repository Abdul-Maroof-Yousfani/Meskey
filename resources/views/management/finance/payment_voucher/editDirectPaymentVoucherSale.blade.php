@extends('management.layouts.master')

@section('title')
    Edit Direct Payment Voucher (Sale) - {{ $payment_voucher->unique_no }}
@endsection

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Edit Direct Payment Voucher (Sale) - <span class="text-primary">{{ $payment_voucher->unique_no }}</span></h4>
                    <a href="{{ route('payment-voucher.index') }}" class="btn btn-secondary btn-sm">
                        <i class="ft-arrow-left mr-1"></i> Back to List
                    </a>
                </div>
                <div class="card-body">
                    <form id="ajaxSubmitSaleEdit" action="{{ route('direct.payment-voucher-sale.update', $payment_voucher) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <!-- Customer Bank Account Hidden Values -->
                        <input type="hidden" name="bank_account_number" id="sale_bank_account_number" value="{{ $payment_voucher->bank_account_number ?? '' }}">
                        <input type="hidden" name="bank_account_type" id="sale_bank_account_type" value="{{ $payment_voucher->bank_account_type ?? '' }}">

                        <!-- Top Fields -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="sale_voucher_type">Voucher Type <span class="text-danger">*</span></label>
                                    <select name="voucher_type" id="sale_voucher_type" class="form-control select2" required>
                                        <option value="">Select Type</option>
                                        <option value="bank_payment_voucher" {{ $payment_voucher->voucher_type === 'bank_payment_voucher' ? 'selected' : '' }}>Bank Payment Voucher</option>
                                        <option value="cash_payment_voucher" {{ $payment_voucher->voucher_type === 'cash_payment_voucher' ? 'selected' : '' }}>Cash Payment Voucher</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="sale_pv_date">Date <span class="text-danger">*</span></label>
                                    <input type="date" name="pv_date" id="sale_pv_date" class="form-control" value="{{ $payment_voucher->pv_date ? $payment_voucher->pv_date->format('Y-m-d') : date('Y-m-d') }}" required>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="sale_unique_no">PV Number</label>
                                    <input type="text" name="unique_no" id="sale_unique_no" class="form-control" value="{{ $payment_voucher->unique_no }}" readonly>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="sale_account_id">Payment From (Cash/Bank) <span class="text-danger">*</span></label>
                                    <select name="account_id" id="sale_account_id" class="form-control select2" required>
                                        <option value="">Select Account</option>
                                        @foreach ($accounts ?? [] as $acc)
                                            <option value="{{ $acc->id }}" {{ $payment_voucher->account_id == $acc->id ? 'selected' : '' }}>
                                                {{ $acc->name }} ({{ $acc->unique_no }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="sale_customer_account_id">Account (Payment To) <span class="text-danger">*</span></label>
                                    <select name="customer_account_id" id="sale_customer_account_id" class="form-control select2" required>
                                        <option value="">Select Account</option>
                                        @foreach ($customerAccounts ?? [] as $custAcc)
                                            @php
                                                $typeLabel = $custAcc->type_label ?? (!empty($custAcc->table_name) ? ucfirst(rtrim($custAcc->table_name, 's')) : '');
                                            @endphp
                                            <option value="{{ $custAcc->id }}" data-table-name="{{ strtolower($custAcc->table_name ?? '') }}" {{ $customerAccountId == $custAcc->id ? 'selected' : '' }}>
                                                {{ $custAcc->name }} ({{ $custAcc->unique_no }}){{ $typeLabel ? " - [{$typeLabel}]" : "" }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="sale_ref_bill_no">Ref Bill / Cheque Ref</label>
                                    <input type="text" name="ref_bill_no" id="sale_ref_bill_no" class="form-control" value="{{ $payment_voucher->ref_bill_no }}">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="sale_bill_date">Bill Date</label>
                                    <input type="date" name="bill_date" id="sale_bill_date" class="form-control" value="{{ $payment_voucher->bill_date ? $payment_voucher->bill_date->format('Y-m-d') : '' }}">
                                </div>
                            </div>
                        </div>

                        <!-- Bank fields -->
                        <div id="sale_bankFields" style="{{ $payment_voucher->voucher_type === 'bank_payment_voucher' ? '' : 'display: none;' }}">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="sale_cheque_no">Cheque No <span class="text-danger">*</span></label>
                                        <input type="text" name="cheque_no" id="sale_cheque_no" class="form-control" value="{{ $payment_voucher->cheque_no }}">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="sale_cheque_date">Cheque Date <span class="text-danger">*</span></label>
                                        <input type="date" name="cheque_date" id="sale_cheque_date" class="form-control" value="{{ $payment_voucher->cheque_date ? $payment_voucher->cheque_date->format('Y-m-d') : '' }}">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Table of Line Items (Separate fields for SO, DO, Excess Advance, and JV) -->
                        <div class="row mt-4">
                            <div class="col-md-12">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <label class="font-weight-bold mb-0">Sales Adjustment Details (Excess Amount / Delivery Order / Journal Voucher)</label>
                                    <span class="badge badge-light" id="sale_customer_selected_badge"></span>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped" id="saleVoucherEntriesTable" style="min-width: 1200px;">
                                        <thead class="thead-light">
                                            <tr>
                                                <th style="width: 13%;">Adjustment Type <span class="text-danger">*</span></th>
                                                <th class="col-so" style="width: 14%;">Sales Order</th>
                                                <th class="col-do" style="width: 14%;">Delivery Order</th>
                                                <th class="col-advance" style="width: 14%;">Excess Advance</th>
                                                <th class="col-jv" style="width: 25%; display: none;">Journal Voucher (JV)</th>
                                                <th style="width: 9%;" class="text-right">Available Bal</th>
                                                <th style="width: 9%;" class="text-right">Amount <span class="text-danger">*</span></th>
                                                <th style="width: 7%;">Tax</th>
                                                <th style="width: 6%;" class="text-right">Tax Amt</th>
                                                <th style="width: 8%;" class="text-right">Net Amount</th>
                                                <th style="width: 10%;">Description</th>
                                                <th style="width: 4%;" class="text-center">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody id="saleVoucherEntriesBody">
                                            @foreach ($items as $index => $item)
                                                @php
                                                    if ($item->journal_voucher_id || $item->reference_type === 'journal_voucher') {
                                                        $rowType = 'journal_voucher';
                                                    } elseif ($item->reference_type === 'customer_advance') {
                                                        $rowType = 'excess_amount';
                                                    } else {
                                                        $rowType = 'delivery_order';
                                                    }
                                                    $isJv = ($rowType === 'journal_voucher');
                                                    $isExcess = ($rowType === 'excess_amount');
                                                    $isDo = ($rowType === 'delivery_order');
                                                @endphp
                                                <tr class="sale-entry-row" data-row-index="{{ $index }}">
                                                    <!-- Column 1: Adjustment Type -->
                                                    <td>
                                                        <select name="row_type[{{ $index }}]" class="form-control form-control-sm sale-row-type-select">
                                                            <option value="excess_amount" {{ $isExcess ? 'selected' : '' }}>Excess Amount</option>
                                                            <option value="delivery_order" {{ $isDo ? 'selected' : '' }}>Delivery Order (DO)</option>
                                                            <option value="journal_voucher" {{ $isJv ? 'selected' : '' }}>Journal Voucher (JV)</option>
                                                        </select>
                                                    </td>

                                                    <!-- Column 2: Sales Order -->
                                                    <td class="cell-so col-so">
                                                        <div class="so-active-box" style="{{ $isDo ? '' : 'display: none;' }}">
                                                            <select name="sales_order_id[{{ $index }}]" class="form-control form-control-sm sale-so-select select2-sale-row" style="width: 100%;">
                                                                <option value="">Select Sales Order</option>
                                                                @if ($isDo && $item->salesOrder)
                                                                    <option value="{{ $item->sales_order_id }}" selected>
                                                                        {{ $item->salesOrder->reference_no }}
                                                                    </option>
                                                                @endif
                                                            </select>
                                                        </div>
                                                        <div class="so-disabled-box" style="{{ !$isDo ? '' : 'display: none;' }}">
                                                            <input type="text" class="form-control form-control-sm bg-light text-center text-muted" readonly value="—">
                                                        </div>
                                                    </td>

                                                    <!-- Column 3: Delivery Order -->
                                                    <td class="cell-do col-do">
                                                        <div class="do-active-box" style="{{ $isDo ? '' : 'display: none;' }}">
                                                            <select name="delivery_order_id[{{ $index }}]" class="form-control form-control-sm sale-do-select select2-sale-row" style="width: 100%;">
                                                                <option value="">Select DO (or SO direct)</option>
                                                                @if ($isDo && $item->deliveryOrder)
                                                                    <option value="{{ $item->delivery_order_id }}" selected>
                                                                        {{ $item->deliveryOrder->reference_no ?: ('DO #' . $item->delivery_order_id) }}
                                                                    </option>
                                                                @endif
                                                            </select>
                                                            <div class="do-status-note small text-muted mt-1" style="display: none;"></div>
                                                        </div>
                                                        <div class="do-disabled-box" style="{{ !$isDo ? '' : 'display: none;' }}">
                                                            <input type="text" class="form-control form-control-sm bg-light text-center text-muted" readonly value="—">
                                                        </div>
                                                    </td>

                                                    <!-- Column 4: Excess Advance -->
                                                    <td class="cell-advance col-advance">
                                                        <div class="adv-active-box" style="{{ $isExcess ? '' : 'display: none;' }}">
                                                            <select name="customer_advance_id[{{ $index }}]" class="form-control form-control-sm sale-advance-select select2-sale-row" style="width: 100%;">
                                                                <option value="">Select Excess Payment</option>
                                                                @if ($isExcess && $item->customerAdvance)
                                                                    <option value="{{ $item->customer_advance_id }}" selected data-remaining="{{ $item->customerAdvance->remaining_amount + $item->net_amount }}">
                                                                        {{ $item->customerAdvance->voucher_no }} (Rem: Rs. {{ number_format($item->customerAdvance->remaining_amount + $item->net_amount, 2) }})
                                                                    </option>
                                                                @endif
                                                            </select>
                                                        </div>
                                                        <div class="adv-disabled-box" style="{{ !$isExcess ? '' : 'display: none;' }}">
                                                            <input type="text" class="form-control form-control-sm bg-light text-center text-muted" readonly value="—">
                                                        </div>
                                                    </td>

                                                    <!-- Column 5: Journal Voucher -->
                                                    <td class="cell-jv col-jv" style="{{ $isJv ? '' : 'display: none;' }}">
                                                        <div class="jv-active-box" style="{{ $isJv ? '' : 'display: none;' }}">
                                                            <input type="hidden" name="journal_voucher_id[{{ $index }}]" class="sale-jv-id-input" value="{{ $item->journal_voucher_id }}">
                                                            <select name="journal_voucher_detail_id[{{ $index }}]" class="form-control form-control-sm sale-jv-select select2-sale-row" style="width: 100%;">
                                                                <option value="">Select Journal Voucher</option>
                                                                @if ($isJv && ($item->journalVoucherDetail || $item->journalVoucher))
                                                                    @php
                                                                        $jv = $item->journalVoucher;
                                                                        $jvd = $item->journalVoucherDetail;
                                                                        $remVal = $jvd ? (float)$jvd->credit_amount : (float)$item->amount;
                                                                    @endphp
                                                                    <option value="{{ $item->journal_voucher_detail_id ?: ($jvd->id ?? '') }}" selected data-jv-id="{{ $item->journal_voucher_id }}" data-remaining="{{ $remVal }}" data-jv-no="{{ $jv?->jv_no }}">
                                                                        {{ $jv?->jv_no }} ({{ $jv?->jv_date ? $jv->jv_date->format('d-M-Y') : 'N/A' }}) - Rem: Rs. {{ number_format($remVal, 2) }}
                                                                    </option>
                                                                @endif
                                                            </select>
                                                        </div>
                                                        <div class="jv-disabled-box" style="{{ !$isJv ? '' : 'display: none;' }}">
                                                            <input type="text" class="form-control form-control-sm bg-light text-center text-muted" readonly value="—">
                                                        </div>
                                                    </td>

                                                    <!-- Column 6: Available Bal -->
                                                    <td>
                                                        <input type="text" class="form-control form-control-sm sale-rem-bal text-right bg-light font-weight-bold" readonly placeholder="0.00">
                                                        <input type="hidden" class="sale-raw-rem-bal" value="0">
                                                    </td>

                                                    <!-- Column 7: Amount -->
                                                    <td>
                                                        <input type="number" step="0.01" min="0.01" name="amount[{{ $index }}]" class="form-control form-control-sm sale-amount-input text-right" required value="{{ $item->amount }}" placeholder="0.00">
                                                    </td>

                                                    <!-- Column 8: Tax -->
                                                    <td>
                                                        <select name="tax_id[{{ $index }}]" class="form-control form-control-sm sale-tax-select select2-sale-row" style="width: 100%;">
                                                            <option value="">No Tax</option>
                                                            @foreach ($taxes ?? [] as $tax)
                                                                <option value="{{ $tax->id }}" data-rate="{{ $tax->percentage }}" {{ $item->tax_id == $tax->id ? 'selected' : '' }}>
                                                                    {{ $tax->name }} ({{ $tax->percentage }}%)
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    </td>

                                                    <!-- Column 9: Tax Amount -->
                                                    <td>
                                                        <input type="number" step="0.01" name="tax_amount[{{ $index }}]" class="form-control form-control-sm sale-tax-amount text-right bg-light" readonly value="{{ number_format($item->tax_amount, 2, '.', '') }}">
                                                    </td>

                                                    <!-- Column 10: Net Amount -->
                                                    <td>
                                                        <input type="number" step="0.01" name="net_amount[{{ $index }}]" class="form-control form-control-sm sale-net-amount text-right bg-light font-weight-bold" readonly value="{{ number_format($item->net_amount, 2, '.', '') }}">
                                                    </td>

                                                    <!-- Column 11: Description -->
                                                    <td>
                                                        <input type="text" name="description[{{ $index }}]" class="form-control form-control-sm sale-desc-input" value="{{ $item->description }}" placeholder="Notes / reason">
                                                    </td>

                                                    <!-- Column 12: Action -->
                                                    <td class="text-center">
                                                        <button type="button" class="btn btn-sm btn-outline-danger sale-remove-row-btn" title="Remove row">
                                                            <i class="ft-trash-2"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <td colspan="8" class="text-right" id="saleTotalColspan"><strong>Total Net Amount:</strong></td>
                                                <td class="text-right"><strong id="saleTotalNetAmount" class="text-primary font-medium-2">{{ number_format($payment_voucher->total_amount, 2) }}</strong></td>
                                                <td colspan="2"></td>
                                            </tr>
                                            <tr>
                                                <td colspan="12" id="saleAddBtnColspan">
                                                    <button type="button" class="btn btn-sm btn-primary" id="saleAddRowBtn">
                                                        <i class="ft-plus"></i> Add Line Item
                                                    </button>
                                                </td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Customer Bank Account Selection at the end (Matches create.blade.php) -->
                        <div class="row sale-bank-account-section mt-3">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="sale_bank_account_id"><strong>Select Customer Bank Account (Transfer To)</strong></label>
                                    <select name="bank_account_id" id="sale_bank_account_id" class="form-control select2" style="width: 100%">
                                        <option value="">Select Bank Account</option>
                                        @foreach ($customerBankAccounts ?? [] as $bank)
                                            <option value="{{ $bank['id'] }}"
                                                data-title="{{ $bank['title'] }}"
                                                data-account-title="{{ $bank['account_title'] }}"
                                                data-account-no="{{ $bank['account_number'] }}"
                                                data-bank-name="{{ $bank['bank_name'] }}"
                                                data-branch-name="{{ $bank['branch_name'] }}"
                                                data-type="{{ $bank['type'] }}"
                                                {{ $payment_voucher->bank_account_id == $bank['id'] ? 'selected' : '' }}>
                                                {{ $bank['type'] === 'company' ? 'Company' : 'Owner' }} - {{ $bank['title'] }} ({{ $bank['account_number'] }})
                                            </option>
                                        @endforeach
                                    </select>
                                    <small class="text-muted">Select the customer's bank account where the refunded amount is transferred.</small>
                                </div>
                            </div>
                        </div>

                        <!-- Remarks -->
                        <div class="row mt-2">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="sale_remarks">Remarks</label>
                                    <textarea name="remarks" id="sale_remarks" rows="2" class="form-control" placeholder="Optional notes">{{ $payment_voucher->remarks }}</textarea>
                                </div>
                            </div>
                        </div>

                        <div class="form-group text-right mt-4">
                            <button type="submit" class="btn btn-primary btn-lg" id="saleSubmitBtn">
                                <i class="ft-check mr-1"></i> Update DPV For Sale
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.sale-bank-account-section .bank-account-option {
    padding: 5px;
}
.sale-bank-account-section .bank-account-option strong {
    display: block;
    margin-bottom: 2px;
}
.sale-bank-account-section .select2-container--default .select2-results__option--highlighted[aria-selected] {
    background-color: #f8f9fa;
    color: #333;
}
.sale-bank-account-section .select2-container--default .select2-results__option[aria-selected=true] {
    background-color: #e9ecef;
}
.sale-bank-account-section .select2-container--default .select2-results__option[aria-selected=true] .bank-account-option {
    opacity: 0.7;
}
</style>
@endsection

@section('script')
<script>
$(document).ready(function() {
    let saleAvailableAdvances = [];
    let saleAvailableSalesOrders = [];
    let saleAvailableBankAccounts = [];
    let saleAvailableJvs = [];
    let saleRowCounter = {{ count($items) }};
    const initialBankAccountId = "{{ $payment_voucher->bank_account_id }}";
    const currentVoucherNo = "{{ $payment_voucher->unique_no }}";

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
    const jvsUrlTemplate = "{{ route('direct.payment-voucher.customer-jvs', ['accountId' => '__ID__']) }}";
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

    // Toggle Bank Fields & Customer Bank Section
    $('#sale_voucher_type').on('change', function() {
        if ($(this).val() === 'bank_payment_voucher') {
            $('#sale_bankFields').show();
            $('#sale_cheque_no, #sale_cheque_date').prop('required', true);
        } else {
            $('#sale_bankFields').hide();
            $('#sale_cheque_no, #sale_cheque_date').prop('required', false);
        }
    });

    // Dynamic Column Visibility based on active row types
    function updateSaleTableColumns() {
        const rows = $('#saleVoucherEntriesBody tr.sale-entry-row');
        if (rows.length === 0) {
            $('.col-so, .col-do, .col-advance').show();
            $('.col-jv').hide();
            updateFooterColspan();
            return;
        }

        let hasJv = false;
        let hasDo = false;
        let hasAdv = false;

        rows.each(function() {
            const type = $(this).find('.sale-row-type-select').val();
            if (type === 'journal_voucher') hasJv = true;
            else if (type === 'delivery_order') hasDo = true;
            else if (type === 'excess_amount') hasAdv = true;
        });

        // Toggle Journal Voucher column
        if (hasJv) {
            $('.col-jv').show();
        } else {
            $('.col-jv').hide();
        }

        // Toggle Sales Order & Delivery Order columns
        if (hasDo) {
            $('.col-so, .col-do').show();
        } else if (hasJv || hasAdv) {
            $('.col-so, .col-do').hide();
        } else {
            $('.col-so, .col-do').show();
        }

        // Toggle Excess Advance column
        if (hasAdv) {
            $('.col-advance').show();
        } else if (hasJv || hasDo) {
            $('.col-advance').hide();
        } else {
            $('.col-advance').show();
        }

        updateFooterColspan();
    }

    function updateFooterColspan() {
        const visibleCols = $('#saleVoucherEntriesTable thead th:visible').length;
        const visibleColsBeforeNet = $('#saleVoucherEntriesTable thead th:not(:nth-last-child(-n+3)):visible').length;
        $('#saleTotalColspan').attr('colspan', visibleColsBeforeNet || 7);
        $('#saleAddBtnColspan').attr('colspan', visibleCols || 11);
    }

    // Initialize Select2 on existing row inputs
    $('.select2-sale-row').select2({
        width: '100%'
    });

    // Load initial customer details
    const initialCustAccId = $('#sale_customer_account_id').val();
    if (initialCustAccId) {
        loadCustomerData(initialCustAccId, true);
    }

    $('#sale_customer_account_id').on('change', function() {
        const custAccId = $(this).val();
        if (custAccId) {
            loadCustomerData(custAccId, false);
        } else {
            saleAvailableAdvances = [];
            saleAvailableSalesOrders = [];
            saleAvailableBankAccounts = [];
            saleAvailableJvs = [];
            $saleBankAccountSelect.empty().append('<option value="">Select Bank Account</option>').trigger('change');
            $('#saleVoucherEntriesBody').empty();
            calculateSaleTotals();
            updateSaleTableColumns();
        }
    });

    function loadCustomerData(custAccId, isInitialLoad = false) {
        $('#sale_customer_selected_badge').show().html('<i class="ft-loader spinner mr-1"></i> Loading customer details...');

        let advData = null, soData = null, bankData = null, jvData = null;

        const p1 = $.get(`${advancesUrlTemplate.replace('__ID__', custAccId)}?exclude_voucher_no=${currentVoucherNo}`).done(r => { advData = r; });
        const p2 = $.get(sosUrlTemplate.replace('__ID__', custAccId)).done(r => { soData = r; });
        const p3 = $.get(banksUrlTemplate.replace('__ID__', custAccId)).done(r => { bankData = r; });
        const p4 = $.get(`${jvsUrlTemplate.replace('__ID__', custAccId)}?exclude_pv_id={{ $payment_voucher->id }}`).done(r => { jvData = r; });

        $.when(p1, p2, p3, p4).always(function() {
            saleAvailableAdvances = (advData && advData.customer_advances) ? advData.customer_advances : [];
            saleAvailableSalesOrders = (soData && soData.sales_orders) ? soData.sales_orders : [];
            saleAvailableBankAccounts = (bankData && bankData.bank_accounts) ? bankData.bank_accounts : [];
            saleAvailableJvs = (jvData && jvData.journal_vouchers) ? jvData.journal_vouchers : [];

            const customerName = (advData && advData.customer_name) ? advData.customer_name : 
                                 ((soData && soData.customer_name) ? soData.customer_name : 
                                 ((jvData && jvData.customer_name) ? jvData.customer_name : 'Customer'));
            
            $('#sale_customer_selected_badge').html(
                `<strong>${customerName}</strong> | JVs: ${saleAvailableJvs.length} | Advances: ${saleAvailableAdvances.length} | Sales Orders: ${saleAvailableSalesOrders.length} | Banks: ${saleAvailableBankAccounts.length}`
            ).show();

            // Populate Customer Bank Accounts
            $saleBankAccountSelect.empty().append('<option value="">Select Bank Account</option>');
            if (saleAvailableBankAccounts.length > 0) {
                saleAvailableBankAccounts.forEach(function(account) {
                    const isSelected = isInitialLoad && String(account.id) === String(initialBankAccountId);
                    const opt = new Option(
                        `${account.type === 'company' ? 'Company' : 'Owner'} - ${account.title || 'No Title'} (${account.account_number || 'N/A'})`,
                        account.id,
                        isSelected,
                        isSelected
                    );
                    $(opt).attr('data-title', account.title || 'No Title');
                    $(opt).attr('data-account-title', account.account_title || 'N/A');
                    $(opt).attr('data-account-no', account.account_number || 'N/A');
                    $(opt).attr('data-bank-name', account.bank_name || 'N/A');
                    $(opt).attr('data-branch-name', account.branch_name || 'N/A');
                    $(opt).attr('data-type', account.type || 'N/A');
                    $saleBankAccountSelect.append(opt);
                });
            }
            $saleBankAccountSelect.trigger('change');

            if (isInitialLoad) {
                $('#saleVoucherEntriesBody tr.sale-entry-row').each(function() {
                    const $row = $(this);
                    const rowType = $row.find('.sale-row-type-select').val();

                    if (rowType === 'journal_voucher') {
                        const jvDetailId = $row.find('.sale-jv-select').val();
                        refreshJvSelectInRow($row, jvDetailId);
                    } else if (rowType === 'excess_amount') {
                        const advId = $row.find('.sale-advance-select').val();
                        refreshAdvanceSelectInRow($row, advId);
                    } else {
                        const soId = $row.find('.sale-so-select').val();
                        const doId = $row.find('.sale-do-select').val();
                        refreshSoSelectInRow($row, soId, doId);
                    }
                });
                updateSaleTableColumns();
            } else {
                $('#saleVoucherEntriesBody').empty();
                saleRowCounter = 0;

                let initType = 'excess_amount';
                if (saleAvailableJvs.length > 0 && saleAvailableAdvances.length === 0 && saleAvailableSalesOrders.length === 0) {
                    initType = 'journal_voucher';
                } else if (saleAvailableAdvances.length > 0) {
                    initType = 'excess_amount';
                } else if (saleAvailableSalesOrders.length > 0) {
                    initType = 'delivery_order';
                } else if (saleAvailableJvs.length > 0) {
                    initType = 'journal_voucher';
                }

                addSaleEntryRow(initType);
            }
        }).fail(function(xhr) {
            $('#sale_customer_selected_badge').html('<span class="text-danger">Failed to load customer details.</span>');
            notifyAlert('error', 'Failed to fetch details for selected customer. Status: ' + xhr.status);
        });
    }

    function refreshJvSelectInRow($row, selectedJvDetailId) {
        const $jvSelect = $row.find('.sale-jv-select');
        const currentSelected = $jvSelect.find('option:selected');
        const currentText = currentSelected.text();
        const currentRemaining = parseFloat(currentSelected.data('remaining')) || 0;
        const currentJvId = currentSelected.data('jv-id') || $row.find('.sale-jv-id-input').val();
        const currentJvNo = currentSelected.data('jv-no') || '';

        $jvSelect.empty().append('<option value="">Select Journal Voucher</option>');

        let found = false;
        saleAvailableJvs.forEach(jv => {
            const isSel = String(jv.jv_detail_id) === String(selectedJvDetailId);
            if (isSel) found = true;
            const opt = new Option(jv.text, jv.jv_detail_id, isSel, isSel);
            $(opt).attr('data-jv-id', jv.jv_id);
            $(opt).attr('data-remaining', jv.remaining_amount);
            $(opt).attr('data-jv-no', jv.jv_no);
            $(opt).attr('data-desc', jv.description || '');
            $jvSelect.append(opt);
        });

        // Retain original option if not in new list
        if (!found && selectedJvDetailId && currentText && currentText !== 'Select Journal Voucher') {
            const opt = new Option(currentText, selectedJvDetailId, true, true);
            $(opt).attr('data-jv-id', currentJvId);
            $(opt).attr('data-remaining', currentRemaining);
            $(opt).attr('data-jv-no', currentJvNo);
            $jvSelect.append(opt);
        }

        $jvSelect.trigger('change.select2');

        const rem = parseFloat($jvSelect.find('option:selected').data('remaining')) || currentRemaining;
        $row.find('.sale-raw-rem-bal').val(rem);
        $row.find('.sale-rem-bal').val(rem > 0 ? rem.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : '0.00');
        $row.find('.sale-amount-input').attr('max', rem.toFixed(2));
    }

    function refreshAdvanceSelectInRow($row, selectedAdvId) {
        const $advSelect = $row.find('.sale-advance-select');
        const currentSelected = $advSelect.find('option:selected');
        const currentText = currentSelected.text();
        const currentRemaining = parseFloat(currentSelected.data('remaining')) || 0;

        $advSelect.empty().append('<option value="">Select Excess Payment</option>');

        let found = false;
        saleAvailableAdvances.forEach(adv => {
            const isSel = String(adv.id) === String(selectedAdvId);
            if (isSel) found = true;
            const opt = new Option(`${adv.voucher_no} (Rem: Rs. ${adv.formatted_remaining})`, adv.id, isSel, isSel);
            $(opt).attr('data-remaining', adv.remaining_amount);
            $advSelect.append(opt);
        });

        if (!found && selectedAdvId && currentText && currentText !== 'Select Excess Payment') {
            const opt = new Option(currentText, selectedAdvId, true, true);
            $(opt).attr('data-remaining', currentRemaining);
            $advSelect.append(opt);
        }

        $advSelect.trigger('change.select2');

        const rem = parseFloat($advSelect.find('option:selected').data('remaining')) || currentRemaining;
        $row.find('.sale-raw-rem-bal').val(rem);
        $row.find('.sale-rem-bal').val(rem > 0 ? rem.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : '0.00');
        $row.find('.sale-amount-input').attr('max', rem.toFixed(2));
    }

    function refreshSoSelectInRow($row, selectedSoId, selectedDoId) {
        const $soSelect = $row.find('.sale-so-select');
        $soSelect.empty().append('<option value="">Select Sales Order</option>');

        saleAvailableSalesOrders.forEach(so => {
            const isSel = String(so.id) === String(selectedSoId);
            const opt = new Option(so.text, so.id, isSel, isSel);
            $soSelect.append(opt);
        });

        $soSelect.trigger('change.select2');

        if (selectedSoId) {
            $.get(`${soDosUrlTemplate.replace('__ID__', selectedSoId)}?exclude_voucher_no=${currentVoucherNo}`, function(resp) {
                const $doSelect = $row.find('.sale-do-select');
                const $doNote = $row.find('.do-status-note');
                $doSelect.empty().append('<option value="">Select Delivery Order</option>');

                if (resp.has_dos && resp.delivery_orders && resp.delivery_orders.length > 0) {
                    const soRemAmt = parseFloat(resp.so_remaining_amount) || 0;
                    if (soRemAmt > 0) {
                        const isSel = !selectedDoId;
                        const soOpt = new Option('Direct SO Advance (Rs. ' + resp.so_formatted_remaining + ')', '', isSel, isSel);
                        $(soOpt).attr('data-rem-amt', soRemAmt);
                        $(soOpt).attr('data-rem-qty', resp.so_remaining_qty || 0);
                        $(soOpt).attr('data-status', 'so_direct');
                        $doSelect.append(soOpt);
                    }

                    resp.delivery_orders.forEach(doItem => {
                        const isSel = String(doItem.id) === String(selectedDoId);
                        const opt = new Option(doItem.text, doItem.id, isSel, isSel);
                        $(opt).attr('data-rem-amt', doItem.remaining_amount);
                        $(opt).attr('data-rem-qty', doItem.remaining_qty);
                        $(opt).attr('data-status', doItem.do_status);
                        $doSelect.append(opt);
                    });
                    $doSelect.trigger('change.select2');

                    const $selDo = $doSelect.find('option:selected');
                    const remAmt = parseFloat($selDo.data('rem-amt')) || 0;
                    const remQty = parseFloat($selDo.data('rem-qty')) || 0;
                    const doStatus = $selDo.data('status') || '';
                    if ($selDo.val() || remAmt > 0) {
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
                    }
                } else {
                    const soRemAmt = parseFloat(resp.so_remaining_amount) || 0;
                    $doSelect.empty().append(new Option('No DO - Full SO Return (Rs. ' + resp.so_formatted_remaining + ')', ''));
                    $doSelect.trigger('change.select2');

                    $row.find('.sale-raw-rem-bal').val(soRemAmt);
                    $row.find('.sale-rem-bal').val(soRemAmt > 0 ? soRemAmt.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : '0.00');
                    $row.find('.sale-amount-input').attr('max', soRemAmt.toFixed(2));
                    $doNote.html('<i class="ft-check text-success"></i> No DOs created. Full SO returnable: Rs. ' + resp.so_formatted_remaining).show();
                }
            });
        }
    }

    // Add Line Item click
    $('#saleAddRowBtn').on('click', function() {
        if (!$('#sale_customer_account_id').val()) {
            notifyAlert('warning', 'Please select a Customer Account first.');
            $('#sale_customer_account_id').select2('open');
            return;
        }

        const lastRowType = $('#saleVoucherEntriesBody tr.sale-entry-row:last').find('.sale-row-type-select').val();
        addSaleEntryRow(lastRowType || 'excess_amount');
    });

    function addSaleEntryRow(initialType = 'excess_amount') {
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

        let jvOptionsHtml = '<option value="">Select Journal Voucher</option>';
        saleAvailableJvs.forEach(jv => {
            jvOptionsHtml += `<option value="${jv.jv_detail_id}" data-jv-id="${jv.jv_id}" data-remaining="${jv.remaining_amount}" data-jv-no="${jv.jv_no}" data-desc="${jv.description || ''}">
                ${jv.text}
            </option>`;
        });

        const isJv = (initialType === 'journal_voucher');
        const isExcess = (initialType === 'excess_amount');
        const isDo = (initialType === 'delivery_order');

        const rowHtml = `
            <tr class="sale-entry-row" data-row-index="${rIndex}">
                <td>
                    <select name="row_type[${rIndex}]" class="form-control form-control-sm sale-row-type-select">
                        <option value="excess_amount" ${isExcess ? 'selected' : ''}>Excess Amount</option>
                        <option value="delivery_order" ${isDo ? 'selected' : ''}>Delivery Order (DO)</option>
                        <option value="journal_voucher" ${isJv ? 'selected' : ''}>Journal Voucher (JV)</option>
                    </select>
                </td>
                <td class="cell-so col-so">
                    <div class="so-active-box" style="${isDo ? '' : 'display: none;'}">
                        <select name="sales_order_id[${rIndex}]" class="form-control form-control-sm sale-so-select select2-sale-row" style="width: 100%;">
                            ${soOptionsHtml}
                        </select>
                    </div>
                    <div class="so-disabled-box" style="${!isDo ? '' : 'display: none;'}">
                        <input type="text" class="form-control form-control-sm bg-light text-center text-muted" readonly value="—">
                    </div>
                </td>
                <td class="cell-do col-do">
                    <div class="do-active-box" style="${isDo ? '' : 'display: none;'}">
                        <select name="delivery_order_id[${rIndex}]" class="form-control form-control-sm sale-do-select select2-sale-row" style="width: 100%;">
                            <option value="">Select DO (or SO direct)</option>
                        </select>
                        <div class="do-status-note small text-muted mt-1" style="display: none;"></div>
                    </div>
                    <div class="do-disabled-box" style="${!isDo ? '' : 'display: none;'}">
                        <input type="text" class="form-control form-control-sm bg-light text-center text-muted" readonly value="—">
                    </div>
                </td>
                <td class="cell-advance col-advance">
                    <div class="adv-active-box" style="${isExcess ? '' : 'display: none;'}">
                        <select name="customer_advance_id[${rIndex}]" class="form-control form-control-sm sale-advance-select select2-sale-row" style="width: 100%;">
                            ${advanceOptionsHtml}
                        </select>
                    </div>
                    <div class="adv-disabled-box" style="${!isExcess ? '' : 'display: none;'}">
                        <input type="text" class="form-control form-control-sm bg-light text-center text-muted" readonly value="—">
                    </div>
                </td>
                <td class="cell-jv col-jv" style="${isJv ? '' : 'display: none;'}">
                    <div class="jv-active-box" style="${isJv ? '' : 'display: none;'}">
                        <input type="hidden" name="journal_voucher_id[${rIndex}]" class="sale-jv-id-input">
                        <select name="journal_voucher_detail_id[${rIndex}]" class="form-control form-control-sm sale-jv-select select2-sale-row" style="width: 100%;">
                            ${jvOptionsHtml}
                        </select>
                    </div>
                    <div class="jv-disabled-box" style="${!isJv ? '' : 'display: none;'}">
                        <input type="text" class="form-control form-control-sm bg-light text-center text-muted" readonly value="—">
                    </div>
                </td>
                <td>
                    <input type="text" class="form-control form-control-sm sale-rem-bal text-right bg-light font-weight-bold" readonly placeholder="0.00">
                    <input type="hidden" class="sale-raw-rem-bal" value="0">
                </td>
                <td>
                    <input type="number" step="0.01" min="0.01" name="amount[${rIndex}]" class="form-control form-control-sm sale-amount-input text-right" required placeholder="0.00">
                </td>
                <td>
                    <select name="tax_id[${rIndex}]" class="form-control form-control-sm sale-tax-select select2-sale-row" style="width: 100%;">
                        ${taxOptionsHtml}
                    </select>
                </td>
                <td>
                    <input type="number" step="0.01" name="tax_amount[${rIndex}]" class="form-control form-control-sm sale-tax-amount text-right bg-light" readonly value="0.00">
                </td>
                <td>
                    <input type="number" step="0.01" name="net_amount[${rIndex}]" class="form-control form-control-sm sale-net-amount text-right bg-light font-weight-bold" readonly value="0.00">
                </td>
                <td>
                    <input type="text" name="description[${rIndex}]" class="form-control form-control-sm sale-desc-input" placeholder="Notes / reason">
                </td>
                <td class="text-center">
                    <button type="button" class="btn btn-sm btn-outline-danger sale-remove-row-btn" title="Remove row">
                        <i class="ft-trash-2"></i>
                    </button>
                </td>
            </tr>
        `;

        const $newRow = $(rowHtml);
        $('#saleVoucherEntriesBody').append($newRow);

        $newRow.find('.select2-sale-row').select2({
            width: '100%'
        });

        updateSaleTableColumns();
        calculateSaleTotals();
    }

    // Toggle Row Type (Excess Amount vs Delivery Order vs Journal Voucher) within a row
    $(document).on('change', '.sale-row-type-select', function() {
        const $row = $(this).closest('tr');
        const selectedType = $(this).val();

        $row.find('.sale-rem-bal').val('');
        $row.find('.sale-raw-rem-bal').val('0');
        $row.find('.sale-amount-input').val('').removeAttr('max');
        $row.find('.sale-tax-amount').val('0.00');
        $row.find('.sale-net-amount').val('0.00');

        if (selectedType === 'journal_voucher') {
            $row.find('.cell-so .so-active-box').hide();
            $row.find('.cell-so .so-disabled-box').show();
            $row.find('.cell-so .sale-so-select').val('').trigger('change.select2');

            $row.find('.cell-do .do-active-box').hide();
            $row.find('.cell-do .do-disabled-box').show();
            $row.find('.cell-do .sale-do-select').empty().append('<option value="">Select DO (or SO direct)</option>');
            $row.find('.cell-do .do-status-note').hide().empty();

            $row.find('.cell-advance .adv-active-box').hide();
            $row.find('.cell-advance .adv-disabled-box').show();
            $row.find('.cell-advance .sale-advance-select').val('').trigger('change.select2');

            $row.find('.cell-jv .jv-active-box').show();
            $row.find('.cell-jv .jv-disabled-box').hide();
        } else if (selectedType === 'excess_amount') {
            $row.find('.cell-so .so-active-box').hide();
            $row.find('.cell-so .so-disabled-box').show();
            $row.find('.cell-so .sale-so-select').val('').trigger('change.select2');

            $row.find('.cell-do .do-active-box').hide();
            $row.find('.cell-do .do-disabled-box').show();
            $row.find('.cell-do .sale-do-select').empty().append('<option value="">Select DO (or SO direct)</option>');
            $row.find('.cell-do .do-status-note').hide().empty();

            $row.find('.cell-jv .jv-active-box').hide();
            $row.find('.cell-jv .jv-disabled-box').show();
            $row.find('.cell-jv .sale-jv-select').val('').trigger('change.select2');
            $row.find('.sale-jv-id-input').val('');

            $row.find('.cell-advance .adv-active-box').show();
            $row.find('.cell-advance .adv-disabled-box').hide();
        } else {
            $row.find('.cell-advance .adv-active-box').hide();
            $row.find('.cell-advance .adv-disabled-box').show();
            $row.find('.cell-advance .sale-advance-select').val('').trigger('change.select2');

            $row.find('.cell-jv .jv-active-box').hide();
            $row.find('.cell-jv .jv-disabled-box').show();
            $row.find('.cell-jv .sale-jv-select').val('').trigger('change.select2');
            $row.find('.sale-jv-id-input').val('');

            $row.find('.cell-so .so-active-box').show();
            $row.find('.cell-so .so-disabled-box').hide();

            $row.find('.cell-do .do-active-box').show();
            $row.find('.cell-do .do-disabled-box').hide();
        }

        updateSaleTableColumns();
        calculateSaleTotals();
    });

    // Journal Voucher selection change in row
    $(document).on('change', '.sale-jv-select', function() {
        const $row = $(this).closest('tr');
        const $selected = $(this).find('option:selected');
        const jvId = $selected.data('jv-id') || '';
        const jvNo = $selected.data('jv-no') || '';
        const remaining = parseFloat($selected.data('remaining')) || 0;

        $row.find('.sale-jv-id-input').val(jvId);
        $row.find('.sale-raw-rem-bal').val(remaining);
        $row.find('.sale-rem-bal').val(remaining > 0 ? remaining.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : '0.00');
        $row.find('.sale-amount-input').attr('max', remaining.toFixed(2));

        if (remaining > 0 && !$row.find('.sale-amount-input').val()) {
            $row.find('.sale-amount-input').val(remaining.toFixed(2));
        }

        if (jvNo && !$row.find('.sale-desc-input').val()) {
            $row.find('.sale-desc-input').val('Refund against JV #' + jvNo);
        }

        calculateRowAmounts($row);
    });

    // Advance selection change in row
    $(document).on('change', '.sale-advance-select', function() {
        const $row = $(this).closest('tr');
        const $selected = $(this).find('option:selected');
        const remaining = parseFloat($selected.data('remaining')) || 0;

        $row.find('.sale-raw-rem-bal').val(remaining);
        $row.find('.sale-rem-bal').val(remaining > 0 ? remaining.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : '0.00');
        $row.find('.sale-amount-input').attr('max', remaining.toFixed(2));

        calculateRowAmounts($row);
    });

    // Sales Order selection change in row
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

        $.get(`${soDosUrlTemplate.replace('__ID__', soId)}?exclude_voucher_no=${currentVoucherNo}`, function(resp) {
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
                const soRemAmt = parseFloat(resp.so_remaining_amount) || 0;
                $doSelect.empty().append(new Option('No DO - Full SO Return (Rs. ' + resp.so_formatted_remaining + ')', ''));
                $doSelect.trigger('change.select2');

                $row.find('.sale-raw-rem-bal').val(soRemAmt);
                $row.find('.sale-rem-bal').val(soRemAmt > 0 ? soRemAmt.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : '0.00');
                $row.find('.sale-amount-input').attr('max', soRemAmt.toFixed(2));
                $doNote.html('<i class="ft-check text-success"></i> No DOs created. Full SO returnable: Rs. ' + resp.so_formatted_remaining).show();

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
        updateSaleTableColumns();
        calculateSaleTotals();
    });

    // Form Submission
    $('#ajaxSubmitSaleEdit').on('submit', function(e) {
        e.preventDefault();

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

            if (rowType === 'journal_voucher') {
                const jvDetailId = $(this).find('.sale-jv-select').val();
                if (!jvDetailId) {
                    notifyAlert('error', `Row #${idx + 1}: Please select a Journal Voucher.`);
                    hasError = true;
                    return false;
                }
            } else if (rowType === 'excess_amount') {
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
        $submitBtn.prop('disabled', true).html('<i class="ft-loader spinner mr-1"></i> Updating Voucher...');

        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: $(this).serialize(),
            success: function(resp) {
                const successMsg = resp.success || 'Direct Payment Voucher (Sale) updated successfully!';
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: successMsg,
                        timer: 1500,
                        showConfirmButton: false,
                    }).then(function() {
                        window.location.href = resp.redirect || "{{ route('payment-voucher.index') }}";
                    });
                    setTimeout(function() {
                        window.location.href = resp.redirect || "{{ route('payment-voucher.index') }}";
                    }, 1600);
                } else {
                    alert(successMsg);
                    window.location.href = resp.redirect || "{{ route('payment-voucher.index') }}";
                }
            },
            error: function(xhr) {
                $submitBtn.prop('disabled', false).html('<i class="ft-check mr-1"></i> Update DPV For Sale');
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
@endsection
