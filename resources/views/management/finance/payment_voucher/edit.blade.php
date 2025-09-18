@extends('management.layouts.master')
@section('title')
    Edit Payment Voucher
@endsection
@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">Edit Payment Voucher #{{ $paymentVoucher->unique_no }}</h4>
                        <a href="{{ route('payment-voucher.index') }}" class="btn btn-sm btn-primary">Back</a>
                    </div>
                    <div class="card-body">
                        <form id="ajaxSubmit" action="{{ route('payment-voucher.update', $paymentVoucher->id) }}">
                            @csrf
                            @method('PUT')

                            <input type="hidden" id="url" value="{{ route('payment-voucher.index') }}">
                            <input type="hidden" name="supplier_id" id="supplier_id_d">
                            <input type="hidden" name="bank_account_number" id="bank_account_number">
                            <input type="hidden" name="bank_account_type" id="bank_account_type">

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="voucher_type">Voucher Type</label>
                                        <select name="voucher_type" id="voucher_type" class="form-control select2" required
                                            disabled>
                                            <option value="">Select Type</option>
                                            @canAccess('bank-payment-voucher')
                                            <option value="bank_payment_voucher"
                                                {{ $paymentVoucher->voucher_type == 'bank_payment_voucher' ? 'selected' : '' }}>
                                                Bank Payment Voucher</option>
                                            @endcanAccess
                                            @canAccess('cash-payment-voucher')
                                            <option value="cash_payment_voucher"
                                                {{ $paymentVoucher->voucher_type == 'cash_payment_voucher' ? 'selected' : '' }}>
                                                Cash Payment Voucher</option>
                                            @endcanAccess
                                        </select>
                                        <small class="form-text text-muted">Voucher type cannot be changed after
                                            creation</small>
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
                                                    {{ $account->name }}
                                                    ({{ $account->unique_no }})
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

                            <div class="row bank-fields"
                                style="display: {{ $paymentVoucher->voucher_type == 'bank_payment_voucher' ? 'block' : 'none' }};">
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

                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label for="module_id">Purchase Order</label>
                                        <select name="module_id" id="module_id" class="form-control select2" required>
                                            <option value="">Select Purchase Order</option>
                                            @foreach ($purchaseOrders as $order)
                                                <option value="{{ $order->id }}"
                                                    {{ $paymentVoucher->module_id == $order->id ? 'selected' : '' }}>
                                                    {{ $order->contract_no }} -
                                                    {{ $order->product->name ?? 'N/A' }}
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
                                                            <th width="2%">*</th>
                                                            <th>Contract No</th>
                                                            <th>Purpose</th>
                                                            <th>Sauda Type</th>
                                                            <th>Date</th>
                                                            <th>Type</th>
                                                            <th>Truck No</th>
                                                            <th>Bilty No</th>
                                                            <th>Loading Date</th>
                                                            <th>Weight</th>
                                                            <th>Amount</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr>
                                                            <td colspan="11" class="text-center">No payment requests
                                                                found please select supplier first.</td>
                                                        </tr>
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
                                            <p class="text-muted">No payment requests selected yet</p>
                                            <ul class="list-group selected-requests-list" style="display: none;"></ul>
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
            // Initialize with existing selected requests
            const selectedRequests = @json($selectedRequests);
            updateSelectedRequestsList(selectedRequests);

            // Set up event handlers
            $('#voucher_type, #pv_date').change(function() {
                if ($('#voucher_type').val()) {
                    $.ajax({
                        url: '{{ route('payment-voucher.generate-pv-number') }}',
                        type: 'POST',
                        data: {
                            _token: '{{ csrf_token() }}',
                            voucher_type: $('#voucher_type').val(),
                            pv_date: $('#pv_date').val() || null
                        },
                        success: function(response) {
                            if (response.success) {
                                if ($('#pv_date').val()) {
                                    $('#unique_no').val(response.pv_number);
                                } else {
                                    let $accountSelect = $('#account_id');
                                    $accountSelect.empty();
                                    $accountSelect.append(
                                        '<option value="">Select Account</option>');

                                    response.accounts.forEach(function(account) {
                                        $accountSelect.append(
                                            `<option value="${account.id}">${account.name} (${account.unique_no})</option>`
                                        );
                                    });

                                    $accountSelect.trigger('change');
                                }
                            }
                        }
                    });
                }
            });

            $('#bank_account_id').change(function() {
                const selectedOption = $(this).find('option:selected');
                if (selectedOption.val()) {
                    const accountNo = selectedOption.data('account-no') || '';
                    const accountType = selectedOption.data('type') || '';

                    $('#bank_account_number').val(accountNo);
                    $('#bank_account_type').val(accountType);
                } else {
                    $('#bank_account_number').val('');
                    $('#bank_account_type').val('');
                }
            });

            $('#voucher_type').change(function() {
                if ($(this).val() === 'bank_payment_voucher') {
                    $('.bank-fields, .bank-account-section').show();
                } else {
                    $('.bank-fields, .bank-account-section').hide();
                }
            });

            const listContainer = $('.selected-requests-list');
            const emptyMessage = $('.selected-requests-container p');
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
                const tbody = $('#paymentRequestsTable tbody');

                if (poId) {
                    tbody.html(`
                        <tr>
                            <td colspan="11" class="text-center">
                                <div class="d-flex justify-content-center align-items-center">
                                    <div class="spinner-border spinner-border-sm mr-2" role="status"></div>
                                    Loading payment requests...
                                </div>
                            </td>
                        </tr>
                    `);

                    $.ajax({
                        url: `/finance/payment-voucher/payment-requests/${poId}`,
                        type: 'GET',
                        success: function(response) {
                            if (response.success) {
                                tbody.empty();

                                if (response.payment_requests.length > 0) {
                                    $.each(response.payment_requests, function(index, request) {
                                        const isChecked = selectedRequests.includes(
                                            request.id.toString());
                                        tbody.append(`
                                            <tr>
                                                <td>
                                                    <input type="checkbox" class="request-checkbox" 
                                                        value="${request.id}" 
                                                        data-supplier-id="${request.supplier_id || ''}" 
                                                        data-amount="${request.amount}" 
                                                        data-purpose="${request.purpose}" 
                                                        data-request-no="${request.contract_no}" 
                                                        data-truck-no="${request.truck_no}"
                                                        data-bilty-no="${request.bilty_no}"
                                                        data-module-type="${request.module_type == 'purchase_order' ? 'Contract' : (request.module_type == 'freight_payment' ? 'Advance Freight' :'Ticket')} "
                                                        data-loading-date="${request.loading_date}"
                                                        data-loading-weight="${request.loading_weight}"
                                                        ${isChecked ? 'checked' : ''}>
                                                </td>
                                                <td>${request.contract_no}</td>
                                                <td>${request.purpose}</td> 
                                                <td>${request.saudaType}</td> 
                                                <td>${request.request_date}</td>
                                                <td>
                                                    <span class="badge" style="display: inline-flex; padding: 0; overflow: hidden;">
                                                        <span
                                                        class="badge badge-${request.module_type == 'purchase_order' ? 'primary' : 'info'}"
                                                            style="border-radius: 3px 0 0 3px;">
                                                            ${request.module_type == 'purchase_order' ? 'Contract' : (request.module_type == 'freight_payment' ? 'Advance Freight' :'Ticket')} 
                                                        </span>
                                                        <span class="badge badge-${request.type == 'payment' ? 'success' : 'warning'}"
                                                            style="border-radius: 0 3px 3px 0;">
                                                            ${request.type == 'payment' ? 'Payment' : 'Freight Payment'}
                                                        </span>
                                                    </span>
                                                </td>
                                                <td>${request.truck_no}</td>
                                                <td>${request.bilty_no}</td>
                                                <td>${request.loading_date}</td>
                                                <td>${request.loading_weight}</td>
                                                <td>${request.amount}</td>
                                            </tr>
                                        `);
                                    });
                                } else {
                                    tbody.append(
                                        '<tr><td colspan="11" class="text-center">No payment requests found for this purchase order</td></tr>'
                                    );
                                }

                                // Update bank accounts dropdown
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
                                    });
                                    bankAccountSelect.trigger('change');
                                }

                                // Update selected requests list
                                updateSelectedRequestsList();
                            }
                        },
                        error: function(xhr) {
                            tbody.html(`
                                <tr>
                                    <td colspan="11" class="text-center text-danger">
                                        Error loading payment requests. Please try again.
                                    </td>
                                </tr>
                            `);
                            console.error('Error:', xhr.responseText);
                        }
                    });
                } else {
                    tbody.html(`
                        <tr>
                            <td colspan="11" class="text-center">Please select a purchase order first.</td>
                        </tr>
                    `);
                }
            });

            // Trigger change event on page load to load payment requests
            $('#module_id').trigger('change');

            $(document).on('change', '.request-checkbox', function() {
                updateSelectedRequestsList();
            });

            function updateSelectedRequestsList(initialRequests = null) {
                const selectedRequests = initialRequests || [];
                let totalAmount = 0;

                if (!initialRequests) {
                    $('.request-checkbox:checked').each(function() {
                        selectedRequests.push({
                            id: $(this).val(),
                            amount: $(this).data('amount'),
                            purpose: $(this).data('purpose'),
                            supplierId: $(this).data('supplier-id'),
                            requestNo: $(this).data('request-no'),
                            truckNo: $(this).data('truck-no'),
                            biltyNo: $(this).data('bilty-no'),
                            moduleType: $(this).data('module-type')
                        });
                        totalAmount += parseFloat($(this).data('amount'));
                    });
                } else {
                    // For initial load, we need to calculate total amount from the data
                    $('.request-checkbox:checked').each(function() {
                        totalAmount += parseFloat($(this).data('amount'));
                    });
                }

                if (selectedRequests.length > 0) {
                    listContainer.empty();
                    emptyMessage.hide();

                    $.each(selectedRequests, function(index, request) {
                        $('#supplier_id').val(request.supplierId);

                        listContainer.append(`
                            <li class="list-group-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span>#${request.requestNo || request.id}</span>
                                    <span class="badge badge-primary badge-pill">${request.amount}</span>
                                </div>
                                <div class="small text-muted">
                                    Truck: ${request.truckNo} | Bilty: ${request.biltyNo} | Type: ${request.moduleType}
                                </div>
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
