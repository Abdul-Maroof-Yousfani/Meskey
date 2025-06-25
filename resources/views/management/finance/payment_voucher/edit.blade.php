@extends('management.layouts.master')
@section('title')
    Payment Voucher Edit
@endsection
@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">Edit Payment Voucher</h4>
                        <a href="{{ route('payment-voucher.index') }}" class="btn btn-sm btn-primary">Back</a>
                    </div>
                    <div class="card-body">
                        <form id="ajaxSubmit" action="{{ route('payment-voucher.update', $paymentVoucher->id) }}" disabled>
                            @csrf
                            @method('PUT')

                            <input type="hidden" name="supplier_id" id="supplier_id"
                                value="{{ $paymentVoucher->supplier_id }}">
                            <input type="hidden" name="bank_account_number" id="bank_account_number">
                            <input type="hidden" name="bank_account_type" id="bank_account_type"
                                value="{{ $paymentVoucher->bank_account_type }}">

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="voucher_type">Voucher Type</label>
                                        <select name="voucher_type" id="voucher_type" class="form-control select2" required
                                            disabled>
                                            <option value="">Select Type</option>
                                            <option value="bank_payment_voucher"
                                                {{ $paymentVoucher->voucher_type == 'bank_payment_voucher' ? 'selected' : '' }}>
                                                Bank Payment Voucher</option>
                                            <option value="cash_payment_voucher"
                                                {{ $paymentVoucher->voucher_type == 'cash_payment_voucher' ? 'selected' : '' }}>
                                                Cash Payment Voucher</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="pv_date">Date</label>
                                        <input type="date" name="pv_date" id="pv_date" class="form-control"
                                            value="{{ $paymentVoucher->pv_date->format('Y-m-d') }}" required>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="unique_no">PV Number</label>
                                        <input type="text" name="unique_no" id="unique_no" class="form-control"
                                            value="{{ $paymentVoucher->unique_no }}" readonly>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="account_id">Account</label>
                                        <select name="account_id" id="account_id" class="form-control select2" required>
                                            <option value="">Select Account</option>
                                            @foreach ($accounts as $account)
                                                <option value="{{ $account->id }}"
                                                    {{ $paymentVoucher->account_id == $account->id ? 'selected' : '' }}>
                                                    {{ $account->name }} ({{ $account->unique_no }})
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="ref_bill_no">Bill/Ref No.</label>
                                        <input type="text" name="ref_bill_no" id="ref_bill_no" class="form-control"
                                            value="{{ $paymentVoucher->ref_bill_no }}">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="bill_date">Bill Date</label>
                                        <input type="date" name="bill_date" id="bill_date" class="form-control"
                                            value="{{ $paymentVoucher->bill_date ? $paymentVoucher->bill_date->format('Y-m-d') : '' }}">
                                    </div>
                                </div>
                            </div>

                            <div class=" bank-fields"
                                style="display: {{ $paymentVoucher->voucher_type == 'bank_payment_voucher' ? 'block' : 'none' }};">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="cheque_no">Cheque No.</label>
                                            <input type="text" name="cheque_no" id="cheque_no" class="form-control"
                                                value="{{ $paymentVoucher->cheque_no }}">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="cheque_date">Cheque Date</label>
                                            <input type="date" name="cheque_date" id="cheque_date" class="form-control"
                                                value="{{ $paymentVoucher->cheque_date ? $paymentVoucher->cheque_date->format('Y-m-d') : '' }}">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label for="module_id">Purchase Order</label>
                                        <select name="module_id" id="module_id" class="form-control select2" required>
                                            <option value="">Select Purchase Order</option>
                                            @foreach ($purchaseOrders as $order)
                                                <option value="{{ $order->id }}"
                                                    {{ $paymentVoucher->module_id == $order->id ? 'selected' : '' }}>
                                                    {{ $order->contract_no }} - {{ $order->product->name ?? 'N/A' }}
                                                    ({{ $order->supplier_name ?? ($order->supplier->name ?? 'N/A') }})
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label>Payment Requests</label>
                                        <div class="payment-requests-container">
                                            <div class="table-responsive">
                                                <table class="table table-bordered" id="paymentRequestsTable">
                                                    <thead>
                                                        <tr>
                                                            <th width="5%">Select</th>
                                                            <th>Request No</th>
                                                            <th>Date</th>
                                                            <th>Amount</th>
                                                            <th>Purpose</th>
                                                            <th>Type</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @if ($paymentVoucher->module_id)
                                                            @php
                                                                $paymentRequests = \App\Models\Procurement\PaymentRequest::with(
                                                                    ['paymentRequestData', 'approvals'],
                                                                )
                                                                    ->whereHas('paymentRequestData', function ($q) use (
                                                                        $paymentVoucher,
                                                                    ) {
                                                                        $q->where(
                                                                            'purchase_order_id',
                                                                            $paymentVoucher->module_id,
                                                                        );
                                                                    })
                                                                    ->where('status', 'approved')
                                                                    ->get();
                                                            @endphp

                                                            @if ($paymentRequests->count() > 0)
                                                                @foreach ($paymentRequests as $request)
                                                                    @php
                                                                        $isChecked = $paymentVoucher->paymentVoucherData->contains(
                                                                            'payment_request_id',
                                                                            $request->id,
                                                                        );
                                                                        $purchaseOrder =
                                                                            $request->paymentRequestData
                                                                                ->purchaseOrder ?? null;
                                                                    @endphp
                                                                    <tr>
                                                                        <td>
                                                                            <input type="checkbox"
                                                                                class="request-checkbox"
                                                                                value="{{ $request->id }}"
                                                                                data-supplier-id="{{ $request->paymentRequestData->purchaseOrder->supplier_id ?? '' }}"
                                                                                data-amount="{{ $request->amount }}"
                                                                                data-purpose="{{ $request->paymentRequestData->notes ?? 'No description' }}"
                                                                                data-request-no="{{ $request->paymentRequestData->purchaseOrder->contract_no ?? 'N/A' }}"
                                                                                data-truck-no="{{ $purchaseOrder->truck_no ?? '' }}"
                                                                                {{ $isChecked ? 'checked' : '' }}>
                                                                        </td>
                                                                        <td>{{ $request->paymentRequestData->purchaseOrder->contract_no ?? 'N/A' }}
                                                                        </td>
                                                                        <td>{{ $request->created_at->format('Y-m-d') }}
                                                                        </td>
                                                                        <td>{{ $request->amount }}</td>
                                                                        <td>{{ $request->paymentRequestData->notes ?? 'No description' }}
                                                                        </td>
                                                                        <td>
                                                                            <span
                                                                                class="badge badge-{{ $request->request_type == 'Payment' ? 'success' : 'warning' }}">
                                                                                {{ formatEnumValue($request->request_type) }}
                                                                            </span>
                                                                        </td>
                                                                    </tr>
                                                                @endforeach
                                                            @else
                                                                <tr>
                                                                    <td colspan="6" class="text-center">No payment
                                                                        requests found for this purchase order</td>
                                                                </tr>
                                                            @endif
                                                        @else
                                                            <tr>
                                                                <td colspan="6" class="text-center">Please select a
                                                                    purchase order first</td>
                                                            </tr>
                                                        @endif
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label>Selected Payment Requests</label>
                                        <div class="selected-requests-container bg-light p-3">
                                            @if ($paymentVoucher->paymentVoucherData->count() > 0)
                                                <ul class="list-group selected-requests-list">
                                                    @php $totalAmount = 0; @endphp
                                                    @foreach ($paymentVoucher->paymentVoucherData as $voucherData)
                                                        @php
                                                            $request = $voucherData->paymentRequest;
                                                            $purchaseOrder =
                                                                $request->paymentRequestData->purchaseOrder ?? null;
                                                            $totalAmount += $request->amount;
                                                        @endphp
                                                        <li
                                                            class="list-group-item d-flex justify-content-between align-items-center">
                                                            <span>#{{ $request->paymentRequestData->purchaseOrder->contract_no ?? 'N/A' }}
                                                                {{ $purchaseOrder && $purchaseOrder->truck_no ? '- ' . $purchaseOrder->truck_no : '' }}</span>
                                                            <span
                                                                class="badge badge-primary badge-pill">{{ $request->amount }}</span>
                                                            <input type="hidden" name="payment_requests[]"
                                                                value="{{ $request->id }}">
                                                        </li>
                                                    @endforeach
                                                    <li
                                                        class="list-group-item list-group-item-primary d-flex justify-content-between align-items-center">
                                                        <strong>Total Amount</strong>
                                                        <strong>{{ number_format($totalAmount, 2) }}</strong>
                                                    </li>
                                                </ul>
                                            @else
                                                <p class="text-muted">No payment requests selected yet</p>
                                                <ul class="list-group selected-requests-list" style="display: none;"></ul>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row bank-account-section"
                                style="display: {{ $paymentVoucher->voucher_type == 'bank_payment_voucher' ? 'block' : 'none' }};">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label for="bank_account_id">Select Bank Account</label>
                                        <select name="bank_account_id" id="bank_account_id" class="form-control select2"
                                            style="width: 100%">
                                            <option value="">Select Bank Account</option>
                                            @if ($paymentVoucher->supplier_id)
                                                @php
                                                    $supplier = \App\Models\Master\Supplier::with([
                                                        'companyBankDetails',
                                                        'ownerBankDetails',
                                                    ])->find($paymentVoucher->supplier_id);
                                                    $companyBankAccounts = $supplier
                                                        ? $supplier->companyBankDetails
                                                        : collect();
                                                    $ownerBankAccounts = $supplier
                                                        ? $supplier->ownerBankDetails
                                                        : collect();
                                                @endphp

                                                @foreach ($companyBankAccounts as $bank)
                                                    <option value="{{ $bank->id }}"
                                                        data-title="{{ $bank->supplier->name ?? '' }}"
                                                        data-account-title="{{ $bank->account_title ?? '' }}"
                                                        data-account-no="{{ $bank->account_number ?? '' }}"
                                                        data-bank-name="{{ $bank->bank_name ?? '' }}"
                                                        data-branch-name="{{ $bank->branch_name ?? '' }}"
                                                        data-type="company"
                                                        {{ $paymentVoucher->bank_account_id == $bank->id ? 'selected' : '' }}>
                                                        Company - {{ $bank->supplier->name ?? '' }}
                                                        ({{ $bank->account_number ?? '' }})
                                                    </option>
                                                @endforeach

                                                @foreach ($ownerBankAccounts as $bank)
                                                    <option value="{{ $bank->id }}"
                                                        data-title="{{ $bank->supplier->name ?? '' }}"
                                                        data-account-title="{{ $bank->account_title ?? '' }}"
                                                        data-account-no="{{ $bank->account_number ?? '' }}"
                                                        data-bank-name="{{ $bank->bank_name ?? '' }}"
                                                        data-branch-name="{{ $bank->branch_name ?? '' }}"
                                                        data-type="owner"
                                                        {{ $paymentVoucher->bank_account_id == $bank->id ? 'selected' : '' }}>
                                                        Owner - {{ $bank->supplier->name ?? '' }}
                                                        ({{ $bank->account_number ?? '' }})
                                                    </option>
                                                @endforeach
                                            @endif
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label for="remarks">Remarks</label>
                                        <textarea name="remarks" id="remarks" class="form-control" rows="3">{{ $paymentVoucher->remarks }}</textarea>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group text-right">
                                <button type="submit" class="btn btn-primary">Update Payment Voucher</button>
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
        $(document).ready(function() {
            if ($('#voucher_type').val() === 'bank_payment_voucher') {
                $('.bank-fields, .bank-account-section').show();
                $('#cheque_no, #cheque_date, #bank_account_id').prop('required', true);
            }

            const bankAccountSelect = $('#bank_account_id');
            bankAccountSelect.select2({
                templateResult: formatBankAccount,
                templateSelection: formatBankAccountSelection
            });

            function formatBankAccount(account) {
                if (!account.id) {
                    return account.text;
                }

                const title = account.element && account.element.getAttribute('data-title') ?
                    account.element.getAttribute('data-title') : 'No Title';
                const accountTitle = account.element && account.element.getAttribute('data-account-title') ?
                    account.element.getAttribute('data-account-title') : 'N/A';
                const accountNo = account.element && account.element.getAttribute('data-account-no') ?
                    account.element.getAttribute('data-account-no') : 'N/A';
                const bankName = account.element && account.element.getAttribute('data-bank-name') ?
                    account.element.getAttribute('data-bank-name') : 'N/A';
                const branchName = account.element && account.element.getAttribute('data-branch-name') ?
                    account.element.getAttribute('data-branch-name') : 'N/A';
                const type = account.element && account.element.getAttribute('data-type') ?
                    (account.element.getAttribute('data-type') === 'company' ? 'Company Account' :
                        'Owner Account') : 'N/A';

                const $container = $(
                    `<div class="bank-account-option">
                        <strong>${type} - ${title}</strong>
                        <div class="text-muted small">${accountTitle} - (${accountNo})</div>
                        <div class="text-muted small">${bankName} - ${branchName}</div>
                    </div>`
                );
                return $container;
            }

            function formatBankAccountSelection(account) {
                if (!account.id) {
                    return account.text;
                }

                const title = account.element && account.element.getAttribute('data-title') ?
                    account.element.getAttribute('data-title') : 'No Title';
                const accountNo = account.element && account.element.getAttribute('data-account-no') ?
                    account.element.getAttribute('data-account-no') : 'N/A';
                const type = account.element && account.element.getAttribute('data-type') ?
                    (account.element.getAttribute('data-type') === 'company' ? 'Company' : 'Owner') : 'Account';

                return `${type} - ${title} (${accountNo})`;
            }

            $('#module_id').change(function() {
                const poId = $(this).val();

                if (poId) {
                    $.ajax({
                        url: `/finance/payment-voucher/payment-requests/${poId}`,
                        type: 'GET',
                        success: function(response) {
                            if (response.success) {
                                const tbody = $('#paymentRequestsTable tbody');
                                tbody.empty();

                                if (response.payment_requests.length > 0) {
                                    $.each(response.payment_requests, function(index, request) {
                                        let {
                                            id,
                                            amount,
                                            purpose,
                                            request_date,
                                            status,
                                            request_no,
                                            supplier_id,
                                            type,
                                            purchaseOrder
                                        } = request;

                                        let {
                                            truck_no
                                        } = purchaseOrder;

                                        const isChecked = $(
                                            'input[name="payment_requests[]"][value="' +
                                            id + '"]').length > 0;

                                        tbody.append(`
                                            <tr>
                                                <td>
                                                    <input type="checkbox" class="request-checkbox" value="${id}" 
                                                        data-supplier-id="${supplier_id || ''}" 
                                                        data-amount="${amount}" 
                                                        data-purpose="${purpose}" 
                                                        data-request-no="${request_no}" 
                                                        data-truck-no="${truck_no}"
                                                        ${isChecked ? 'checked' : ''}>
                                                </td>
                                                <td>${request_no}</td>
                                                <td>${request_date}</td>
                                                <td>${amount}</td>
                                                <td>${purpose}</td>
                                                <td>
                                                    <span class="badge badge-${type === 'Payment' ? 'success' : 'warning'}">
                                                        ${type}
                                                    </span>
                                                </td>
                                            </tr>
                                        `);
                                    });
                                } else {
                                    tbody.append(
                                        '<tr><td colspan="6" class="text-center">No payment requests found for this purchase order</td></tr>'
                                    );
                                }

                                bankAccountSelect.empty().append(
                                    '<option value="">Select Bank Account</option>');
                                if (response.bank_accounts.length > 0) {
                                    $.each(response.bank_accounts, function(index, account) {
                                        const option = new Option(
                                            `${account.type === 'company' ? 'Company' : 'Owner'} - ${account.title || 'No Title'} (${account.account_number || 'N/A'})`,
                                            account.id,
                                            false,
                                            false
                                        );

                                        $(option).attr('data-title', account.title ||
                                            'No Title');
                                        $(option).attr('data-account-title', account
                                            .account_title || 'N/A');
                                        $(option).attr('data-account-no', account
                                            .account_number || 'N/A');
                                        $(option).attr('data-bank-name', account
                                            .bank_name || 'N/A');
                                        $(option).attr('data-branch-name', account
                                            .branch_name || 'N/A');
                                        $(option).attr('data-type', account.type ||
                                            'N/A');

                                        bankAccountSelect.append(option);

                                        // Select the previously selected bank account
                                        if (account.id ==
                                            @json($paymentVoucher->bank_account_id)) {
                                            bankAccountSelect.val(account.id).trigger(
                                                'change');
                                        }
                                    });
                                }

                                updateSelectedRequestsList();
                            }
                        }
                    });
                }
            });

            $(document).on('change', '.request-checkbox', function() {
                updateSelectedRequestsList();
            });

            function updateSelectedRequestsList() {
                const selectedRequests = [];
                let totalAmount = 0;

                $('.request-checkbox:checked').each(function() {
                    selectedRequests.push({
                        id: $(this).val(),
                        amount: $(this).data('amount'),
                        purpose: $(this).data('purpose'),
                        supplierId: $(this).data('supplier-id'),
                        requestNo: $(this).data('request-no'),
                        truckNo: $(this).data('truck-no')
                    });
                    totalAmount += parseFloat($(this).data('amount'));
                });

                const listContainer = $('.selected-requests-list');
                const emptyMessage = $('.selected-requests-container p');

                if (selectedRequests.length > 0) {
                    listContainer.empty();
                    emptyMessage.hide();

                    $.each(selectedRequests, function(index, request) {
                        $('#supplier_id').val(request.supplierId);

                        listContainer.append(`
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span>#${request.requestNo} ${request.truckNo ? '- ' + request.truckNo : ''}</span>
                                <span class="badge badge-primary badge-pill">${request.amount}</span>
                                <input type="hidden" name="payment_requests[]" value="${request.id}">
                            </li>
                        `);
                    });

                    listContainer.append(`
                        <li class="list-group-item list-group-item-primary d-flex justify-content-between align-items-center">
                            <strong>Total Amount</strong>
                            <strong>${totalAmount.toFixed(2)}</strong>
                        </li>
                    `);

                    listContainer.show();
                } else {
                    listContainer.hide();
                    emptyMessage.show();
                }
            }

            $('#ajaxSubmit').submit(function(e) {
                e.preventDefault();

                if ($('#voucher_type').val() === 'bank_payment_voucher' && !$('#bank_account_id').val()) {
                    toastr.error('Please select a bank account');
                    return;
                }

                const formData = $(this).serialize();

                $.ajax({
                    url: $(this).attr('action'),
                    type: 'PUT',
                    data: formData,
                    success: function(response) {
                        if (response.success) {
                            toastr.success(response.success);
                            window.location.href = response.redirect;
                        }
                    },
                    error: function(xhr) {
                        const errors = xhr.responseJSON.errors;
                        $.each(errors, function(key, value) {
                            toastr.error(value[0]);
                        });
                    }
                });
            });
        });
    </script>
    <style>
        .bank-account-section .bank-account-option {
            padding: 5px;
        }

        .bank-account-section .bank-account-option strong {
            display: block;
            margin-bottom: 2px;
        }

        .bank-account-section .select2-container--default .select2-results__option--highlighted[aria-selected] {
            background-color: #f8f9fa;
            color: #333;
        }

        .bank-account-section .select2-container--default .select2-results__option[aria-selected=true] {
            background-color: #e9ecef;
        }

        .bank-account-section .select2-container--default .select2-results__option[aria-selected=true] .bank-account-option {
            opacity: 0.7;
        }
    </style>
@endsection
