@extends('management.layouts.master')
@section('title')
    Create Payment Voucher
@endsection
@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">Create Payment Voucher</h4>
                        <a href="{{ route('payment-voucher.index') }}" class="btn btn-sm btn-primary">Back</a>
                    </div>
                    <div class="card-body">
                        <form id="ajaxSubmit" action="{{ route('payment-voucher.store') }}">
                            @csrf

                            <input type="hidden" id="url" value="{{ route('payment-voucher.index') }}">
                            <input type="hidden" name="supplier_id" id="supplier_id_d">
                            <input type="hidden" name="bank_account_number" id="bank_account_number">
                            <input type="hidden" name="bank_account_type" id="bank_account_type">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="voucher_type">Voucher Type</label>
                                        <select name="voucher_type" id="voucher_type" class="form-control select2" required>
                                            <option value="">Select Type</option>
                                            @canAccess('bank-payment-voucher')
                                            <option value="bank_payment_voucher">Bank Payment Voucher</option>
                                            @endcanAccess
                                            @canAccess('cash-payment-voucher')
                                            <option value="cash_payment_voucher">Cash Payment Voucher</option>
                                            @endcanAccess
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="pv_date">Date</label>
                                        <input type="date" name="pv_date" id="pv_date" class="form-control" required>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="unique_no">PV Number</label>
                                        <input type="text" name="unique_no" id="unique_no" class="form-control" readonly>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="account_id">Account</label>
                                        <select name="account_id" id="account_id" class="form-control select2" required>
                                            <option value="">Select Account</option>
                                            {{-- @foreach ($accounts as $account)
                                            <option value="{{ $account->id }}">{{ $account->name }}
                                                ({{ $account->unique_no }})
                                            </option>
                                            @endforeach --}}
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="ref_bill_no">Bill/Ref No.</label>
                                        <input type="text" name="ref_bill_no" id="ref_bill_no" class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="bill_date">Bill Date</label>
                                        <input type="date" name="bill_date" id="bill_date" class="form-control">
                                    </div>
                                </div>
                            </div>

                            <div class="row bank-fields" style="display: none;">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="cheque_no">Cheque No.</label>
                                        <input type="text" name="cheque_no" id="cheque_no" class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="cheque_date">Cheque Date</label>
                                        <input type="date" name="cheque_date" id="cheque_date" class="form-control">
                                    </div>
                                </div>
                            </div>

                            {{-- <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label for="module_id">Purchase Order</label>
                                        <select name="module_id" id="module_id" class="form-control select2" required>
                                            <option value="">Select Purchase Order</option>
                                            @foreach ($purchaseOrders as $order)
                                            <option value="{{ $order->id }}">{{ $order->contract_no }} -
                                                {{ $order->product->name ?? 'N/A' }}
                                                ({{ $order->supplier_name ?? ($order->supplier->name ?? 'N/A') }})
                                            </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div> --}}

                            <div class="row">
                                <input type="hidden" name="model_id" id="model_id">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label for="req_account_id">Account</label>
                                        <select name="request_account_id" id="req_account_id" class="form-control select2">
                                            <option value="">Select Account</option>
                                            @foreach ($accounts as $account)
                                                <option value="{{ $account->id }}">{{ $account->name }}
                                                    ({{ $account->hierarchy_path }})
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    {{-- <div class="form-group">
                                        <label for="supplier_id">Supplier</label>
                                        <select name="supplier_id" id="supplier_id" class="form-control select2" required>
                                            <option value="">Select Supplier</option>
                                            @foreach ($suppliers as $supplier)
                                            <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                                            @endforeach
                                        </select>
                                    </div> --}}
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
                                                            <th>GRN No</th>
                                                            <th>Purpose</th>
                                                            <th>Sauda Type</th>
                                                            <th>Date</th>
                                                            <th>Type</th>
                                                            <th>Truck No</th>
                                                            <th>Bilty No</th>
                                                            <th>Loading Date</th>
                                                            <th>Weight</th>
                                                            <th>Gross Amount</th>
                                                            <th>JV Adjustment</th>
                                                            <th>Net Payable</th>
                                                            <th>Attachment</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr>
                                                            <td colspan="15" class="text-center">No payment requests
                                                                found please select account first.</td>
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

                            <div class="row bank-account-section" style="display: none;">
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
                                        <textarea name="remarks" id="remarks" class="form-control" rows="3"></textarea>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group text-right">
                                <button type="submit" class="btn btn-primary">Create Payment Voucher</button>
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
            $('#voucher_type, #pv_date').change(function () {
                if ($('#voucher_type').val()) {
                    $.ajax({
                        url: '{{ route('payment-voucher.generate-pv-number') }}',
                        type: 'POST',
                        data: {
                            _token: '{{ csrf_token() }}',
                            voucher_type: $('#voucher_type').val(),
                            pv_date: $('#pv_date').val() || null
                        },
                        success: function (response) {
                            if (response.success) {
                                if ($('#pv_date').val()) {
                                    $('#unique_no').val(response.pv_number);
                                } else {
                                    let $accountSelect = $('#account_id');
                                    $accountSelect.empty();
                                    $accountSelect.append(
                                        '<option value="">Select Account</option>');

                                    response.accounts.forEach(function (account) {
                                        $accountSelect.append(
                                            `<option value="${account.id}">${account.name} (${account.hierarchy_path})</option>`
                                        );
                                    });

                                    $accountSelect.trigger('change');
                                }
                            }
                        }
                    });
                }
            });

            $('#bank_account_id').change(function () {
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

            $('#voucher_type').change(function () {
                if ($(this).val() === 'bank_payment_voucher') {
                    $('.bank-fields, .bank-account-section').show();
                    // $('#cheque_no, #cheque_date, #bank_account_id').prop('required', true);
                } else {
                    $('.bank-fields, .bank-account-section').hide();
                    // $('#cheque_no, #cheque_date, #bank_account_id').prop('required', false);
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

            // Replace the supplier change event with account change event
            $('#req_account_id').change(function () {
                const accountId = $(this).val();
                const tbody = $('#paymentRequestsTable tbody');

                if (accountId) {
                    tbody.html(`
                                                                        <tr>
                                                                            <td colspan="15" class="text-center">
                                                                                <div class="d-flex justify-content-center align-items-center">
                                                                                    <div class="spinner-border spinner-border-sm mr-2" role="status"></div>
                                                                                    Loading payment requests...
                                                                                </div>
                                                                            </td>
                                                                        </tr>
                                                                    `);

                    $.ajax({
                        url: `/finance/payment-voucher/account-payment-requests/${accountId}`,
                        type: 'GET',
                        success: function (response) {
                            if (response.success) {
                                tbody.empty();

                                if (response.model_id) {
                                    $('#model_id').val(response.model_id);
                                }

                                if (response.payment_requests.length > 0) {
                                    $.each(response.payment_requests, function (index, request) {
                                        const isSaleOrder = (request.module_type === 'delivery_challan' || request.module_type === 'sale_order' || request.module_type === 'sales_order');
                                        const moduleLabel = request.module_type === 'purchase_order' ? 'Contract' : (isSaleOrder ? 'Sale Order' : (request.module_type === 'freight_payment' ? 'Advance Freight' : 'Ticket'));
                                        const moduleBadgeClass = request.module_type === 'purchase_order' ? 'primary' : (isSaleOrder ? 'success' : (request.module_type === 'freight_payment' ? 'secondary' : 'info'));

                                        const typeMap = {
                                            'payment': 'Payment',
                                            'advance_payment': 'Advance Payment',
                                            'freight_payment': 'Freight Payment',
                                            'freight_labour_payment': 'Labour Payment',
                                            'labour_payment': 'Labour Payment',
                                            'broker_commission_payment': 'Broker Commission',
                                            'seller_commission_payment': 'Seller Commission'
                                        };
                                        const typeLabel = typeMap[request.type] || (request.type ? request.type.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase()) : 'Payment');
                                        const typeBadgeClass = request.type === 'payment' ? 'success' : 
                                                              (request.type === 'freight_payment' ? 'warning' : 
                                                              (request.type === 'freight_labour_payment' || request.type === 'labour_payment' ? 'info' : 
                                                              (request.type === 'broker_commission_payment' ? 'secondary' : 
                                                              (request.type === 'seller_commission_payment' ? 'dark' : 'warning'))));

                                        const grnDisplay = request.grn_no ? `<span class="badge badge-info">${request.grn_no}</span>` : '-';
                                        const grossAmount = parseFloat(request.amount) || 0;
                                        const jvAdjAmount = parseFloat(request.jv_adjustment_amount) || 0;
                                        const initialAdj = Math.min(grossAmount, jvAdjAmount);
                                        const netAmount = Math.max(0, grossAmount - initialAdj);
                                        const jvNo = request.jv_no || '';
                                        const matchingJvs = request.matching_jvs || [];

                                        let jvAdjHtml = '-';
                                        if (initialAdj > 0) {
                                            let jvBadgesHtml = '';
                                            if (matchingJvs && matchingJvs.length > 0) {
                                                jvBadgesHtml = matchingJvs.map(function (mj) {
                                                    const formattedAmt = parseFloat(mj.allocated).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                                                    return `
                                                        <div class="mt-1">
                                                            <span class="badge badge-warning text-dark text-left" style="font-size: 11px; padding: 4px 6px; display: inline-block;">
                                                                ${mj.jv_no}: ${formattedAmt}
                                                            </span>
                                                        </div>
                                                    `;
                                                }).join('');
                                            } else if (jvNo) {
                                                jvBadgesHtml = `
                                                    <div class="mt-1">
                                                        <span class="badge badge-warning text-dark text-left" style="font-size: 11px; padding: 4px 6px; display: inline-block;">
                                                            ${jvNo}
                                                        </span>
                                                    </div>
                                                `;
                                            }

                                            jvAdjHtml = `
                                                <div class="input-group input-group-sm" style="max-width: 140px;">
                                                    <input type="number" step="0.01" min="0" max="${initialAdj}" 
                                                        class="form-control form-control-sm jv-adjustment-input" 
                                                        data-request-id="${request.id}" 
                                                        data-max-adj="${initialAdj}" 
                                                        data-gross="${grossAmount}"
                                                        value="${initialAdj.toFixed(2)}">
                                                </div>
                                                ${jvBadgesHtml}
                                            `;
                                        }

                                        tbody.append(`
                                                                        <tr>
                                                                            <td>
                                                                                <input type="checkbox" class="request-checkbox" 
                                                                                    value="${request.id}" 
                                                                                    data-supplier-id="${request.supplier_id || ''}" 
                                                                                    data-amount="${grossAmount}" 
                                                                                    data-grn-no="${request.grn_no || ''}"
                                                                                    data-jv-adj="${initialAdj}"
                                                                                    data-jv-no="${jvNo}"
                                                                                    data-matching-jvs='${JSON.stringify(matchingJvs)}'
                                                                                    data-net-amount="${netAmount}"
                                                                                    data-purpose="${request.purpose}" 
                                                                                    data-request-no="${request.contract_no}" 
                                                                                    data-truck-no="${request.truck_no}"
                                                                                    data-bilty-no="${request.bilty_no}"
                                                                                    data-module-type="${moduleLabel}"
                                                                                    data-loading-date="${request.loading_date}"
                                                                                    data-loading-weight="${request.loading_weight}">
                                                                            </td>
                                                                            <td>${request.contract_no}</td>
                                                                            <td>${grnDisplay}</td>
                                                                            <td>${request.purpose}</td> 
                                                                            <td>${request.saudaType}</td> 
                                                                            <td>${request.request_date}</td>
                                                                            <td>
                                                                                <span class="badge" style="display: inline-flex; padding: 0; overflow: hidden;">
                                                                                    <span
                                                                                    class="badge badge-${moduleBadgeClass}"
                                                                                        style="border-radius: 3px 0 0 3px;">
                                                                                        ${moduleLabel}
                                                                                    </span>
                                                                                    <span class="badge badge-${typeBadgeClass}"
                                                                                        style="border-radius: 0 3px 3px 0;">
                                                                                        ${typeLabel}
                                                                                    </span>
                                                                                </span>
                                                                            </td>
                                                                            <td>${request.truck_no}</td>
                                                                            <td>${request.bilty_no}</td>
                                                                            <td>${request.loading_date}</td>
                                                                            <td>${request.loading_weight}</td>
                                                                            <td>${grossAmount.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                                                                            <td>${jvAdjHtml}</td>
                                                                            <td class="text-right">
                                                                                <strong class="net-payable-text text-primary">
                                                                                    ${netAmount.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}
                                                                                </strong>
                                                                            </td>
                                                                            <td>
                                                                            ${request.file
                                                                                ? `<a href="/${request.file}" target="_blank" class="btn btn-sm btn-outline-primary mt-1">
                                                                                        <i class="fa fa-download"></i> Download File
                                                                                   </a>`
                                                                                : `<button class="btn btn-sm btn-outline-secondary mt-1" disabled>
                                                                                        <i class="fa fa-ban mr-1"></i> No File
                                                                                   </button>`
                                                                            }
                                                                            </td>
                                                                        </tr>
                                                                    `);
                                    });
                                } else {
                                    tbody.append(
                                        '<tr><td colspan="15" class="text-center">No payment requests found for this account</td></tr>'
                                    );
                                }

                                // Update bank accounts dropdown
                                bankAccountSelect.empty().append(
                                    '<option value="">Select Bank Account</option>');
                                if (response.bank_accounts.length > 0) {
                                    $.each(response.bank_accounts, function (index, account) {
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

                                // Reset selected requests
                                listContainer.hide();
                                emptyMessage.show();
                            }
                        },
                        error: function (xhr) {
                            tbody.html(`
                                                            <tr>
                                                                <td colspan="15" class="text-center text-danger">
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
                                                        <td colspan="15" class="text-center">Please select an account first.</td>
                                                    </tr>
                                                `);
                }
            });

            // $('#supplier_id').change(function() {
            //     const supplierId = $(this).val();
            //     const tbody = $('#paymentRequestsTable tbody');

            //     if (supplierId) {
            //         tbody.html(`
            //                     <tr>
            //                         <td colspan="11" class="text-center">
            //                             <div class="d-flex justify-content-center align-items-center">
            //                                 <div class="spinner-border spinner-border-sm mr-2" role="status"></div>
            //                                 Loading payment requests...
            //                             </div>
            //                         </td>
            //                     </tr>
            //                 `);

            //         $.ajax({
            //             url: `/finance/payment-voucher/payment-requests/${supplierId}`,
            //             type: 'GET',
            //             success: function(response) {
            //                 if (response.success) {
            //                     tbody.empty();

            //                     if (response.payment_requests.length > 0) {
            //                         $.each(response.payment_requests, function(index, request) {
            //                             tbody.append(`
            //                         <tr>
            //                             <td>
            //                                 <input type="checkbox" class="request-checkbox" 
            //                                     value="${request.id}" 
            //                                     data-supplier-id="${request.supplier_id || ''}" 
            //                                     data-amount="${request.amount}" 
            //                                     data-purpose="${request.purpose}" 
            //                                     data-request-no="${request.contract_no}" 
            //                                     data-truck-no="${request.truck_no}"
            //                                     data-bilty-no="${request.bilty_no}"
            //                                     data-module-type="${request.module_type == 'purchase_order' ? 'Contract' : (request.module_type == 'freight_payment' ? 'Advance Freight' :'Ticket')} "
            //                                     data-loading-date="${request.loading_date}"
            //                                     data-loading-weight="${request.loading_weight}">
            //                             </td>
            //                             <td>${request.contract_no}</td>
            //                             <td>${request.purpose}</td> 
            //                             <td>${request.saudaType}</td> 
            //                             <td>${request.request_date}</td>
            //                             <td>
            //                                 <span class="badge" style="display: inline-flex; padding: 0; overflow: hidden;">
            //                                     <span
            //                                     class="badge badge-${request.module_type == 'purchase_order' ? 'primary' : 'info'}"
            //                                         style="border-radius: 3px 0 0 3px;">
            //                                         ${request.module_type == 'purchase_order' ? 'Contract' : (request.module_type == 'freight_payment' ? 'Advance Freight' :'Ticket')} 
            //                                     </span>
            //                                     <span class="badge badge-${request.type == 'payment' ? 'success' : 'warning'}"
            //                                         style="border-radius: 0 3px 3px 0;">
            //                                         ${request.type == 'payment' ? 'Payment' : 'Freight Payment'}
            //                                     </span>
            //                                 </span>
            //                                 </td>
            //                                 <td>${request.truck_no}</td>
            //                                 <td>${request.bilty_no}</td>
            //                                 <td>${request.loading_date}</td>
            //                                 <td>${request.loading_weight}</td>
            //                                 <td>${request.amount}</td>
            //                         </tr>
            //                     `);
            //                         });
            //                     } else {
            //                         tbody.append(
            //                             '<tr><td colspan="11" class="text-center">No payment requests found for this supplier</td></tr>'
            //                         );
            //                     }

            //                     // Update bank accounts dropdown
            //                     bankAccountSelect.empty().append(
            //                         '<option value="">Select Bank Account</option>');
            //                     if (response.bank_accounts.length > 0) {
            //                         $.each(response.bank_accounts, function(index, account) {
            //                             const option = new Option(
            //                                 `${account.type === 'company' ? 'Company' : 'Owner'} - ${account.title || 'No Title'} (${account.account_number || 'N/A'})`,
            //                                 account.id,
            //                                 false,
            //                                 false
            //                             );

            //                             $(option).attr('data-title', account.title ||
            //                                 'No Title');
            //                             $(option).attr('data-account-title', account
            //                                 .account_title || 'N/A');
            //                             $(option).attr('data-account-no', account
            //                                 .account_number || 'N/A');
            //                             $(option).attr('data-bank-name', account
            //                                 .bank_name || 'N/A');
            //                             $(option).attr('data-branch-name', account
            //                                 .branch_name || 'N/A');
            //                             $(option).attr('data-type', account.type ||
            //                                 'N/A');

            //                             bankAccountSelect.append(option);
            //                         });
            //                         bankAccountSelect.trigger('change');
            //                     }

            //                     // Reset selected requests
            //                     listContainer.hide();
            //                     emptyMessage.show();
            //                     $('#supplier_id_d').val(supplierId);
            //                 }
            //             },
            //             error: function(xhr) {
            //                 tbody.html(`
            //             <tr>
            //                 <td colspan="11" class="text-center text-danger">
            //                     Error loading payment requests. Please try again.
            //                 </td>
            //             </tr>
            //         `);
            //                 console.error('Error:', xhr.responseText);
            //             }
            //         });
            //     } else {
            //         tbody.html(`
            //     <tr>
            //         <td colspan="11" class="text-center">No payment requests found please select supplier first.</td>
            //     </tr>
            // `);
            //     }
            // });

            $('#module_id').change(function () {
                const poId = $(this).val();

                if (poId) {
                    $.ajax({
                        url: `/finance/payment-voucher/payment-requests/${poId}`,
                        type: 'GET',
                        success: function (response) {
                            if (response.success) {
                                const tbody = $('#paymentRequestsTable tbody');
                                tbody.empty();

                                if (response.payment_requests.length > 0) {
                                    $.each(response.payment_requests, function (index, request) {
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

                                        tbody.append(`
                                                                                        <tr>
                                                                                            <td>
                                                                                                <input type="checkbox" class="request-checkbox" value="${id}" data-supplier-id="${supplier_id || ''}" data-amount="${amount}" data-purpose="${purpose}" data-request-no="${request_no}" data-truck-no="${truck_no}">
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
                                    $.each(response.bank_accounts, function (index, account) {
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
                                listContainer.hide();
                                emptyMessage.show();
                            }
                        }
                    });
                }
            });

            $(document).on('input change', '.jv-adjustment-input', function () {
                const $input = $(this);
                const maxAdj = parseFloat($input.data('max-adj')) || 0;
                const gross = parseFloat($input.data('gross')) || 0;
                let val = parseFloat($input.val());

                if (isNaN(val) || val < 0) {
                    val = 0;
                } else if (val > maxAdj) {
                    val = maxAdj;
                    $input.val(val.toFixed(2));
                }

                if (val > gross) {
                    val = gross;
                    $input.val(val.toFixed(2));
                }

                const net = Math.max(0, gross - val);
                const $row = $input.closest('tr');
                $row.find('.net-payable-text').text(net.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}));

                const $cb = $row.find('.request-checkbox');
                $cb.attr('data-jv-adj', val);
                $cb.data('jv-adj', val);
                $cb.attr('data-net-amount', net);
                $cb.data('net-amount', net);

                if ($cb.is(':checked')) {
                    updateSelectedRequestsList();
                }
            });

            $(document).on('change', '.request-checkbox', function () {
                updateSelectedRequestsList();
            });

            function updateSelectedRequestsList() {
                const selectedRequests = [];
                let totalGross = 0;
                let totalJvAdj = 0;
                let totalNet = 0;

                $('.request-checkbox:checked').each(function () {
                    const gross = parseFloat($(this).data('amount')) || 0;
                    const jvAdj = parseFloat($(this).data('jv-adj')) || 0;
                    const net = $(this).data('net-amount') !== undefined && !isNaN(parseFloat($(this).data('net-amount')))
                        ? parseFloat($(this).data('net-amount'))
                        : Math.max(0, gross - jvAdj);

                    let matchingJvs = $(this).data('matching-jvs') || [];
                    if (typeof matchingJvs === 'string') {
                        try { matchingJvs = JSON.parse(matchingJvs); } catch(e) { matchingJvs = []; }
                    }

                    selectedRequests.push({
                        id: $(this).val(),
                        amount: gross,
                        jvAdj: jvAdj,
                        netAmount: net,
                        grnNo: $(this).data('grn-no') || '',
                        jvNo: $(this).data('jv-no') || '',
                        matchingJvs: matchingJvs,
                        purpose: $(this).data('purpose'),
                        supplierId: $(this).data('supplier-id'),
                        requestNo: $(this).data('request-no'),
                        truckNo: $(this).data('truck-no'),
                        biltyNo: $(this).data('bilty-no'),
                        moduleType: $(this).data('module-type')
                    });
                    totalGross += gross;
                    totalJvAdj += jvAdj;
                    totalNet += net;
                });

                if (selectedRequests.length > 0) {
                    listContainer.empty();
                    emptyMessage.hide();

                    $.each(selectedRequests, function (index, request) {
                        $('#supplier_id').val(request.supplierId);

                        let amountBadgeHtml = '';
                        if (request.jvAdj > 0) {
                            amountBadgeHtml = `
                                <div class="text-right">
                                    <span class="text-muted small mr-1" style="text-decoration: line-through;">${request.amount.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</span>
                                    <span class="badge badge-warning text-dark mr-1" title="JV Adjustment: ${request.jvNo}">-${request.jvAdj.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</span>
                                    <span class="badge badge-primary badge-pill">${request.netAmount.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</span>
                                </div>
                            `;
                        } else {
                            amountBadgeHtml = `<span class="badge badge-primary badge-pill">${request.amount.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</span>`;
                        }

                        let extraInfo = `Truck: ${request.truckNo || '-'} | Bilty: ${request.biltyNo || '-'} | Type: ${request.moduleType || '-'}`;
                        if (request.grnNo) {
                            extraInfo += ` | GRN: <span class="badge badge-info">${request.grnNo}</span>`;
                        }
                        if (request.matchingJvs && request.matchingJvs.length > 0 && request.jvAdj > 0) {
                            const jvListText = request.matchingJvs.map(mj => `${mj.jv_no} (${parseFloat(mj.allocated).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})})`).join(', ');
                            extraInfo += ` | JV: <span class="badge badge-warning text-dark">${jvListText}</span>`;
                        } else if (request.jvNo && request.jvAdj > 0) {
                            extraInfo += ` | JV: <span class="badge badge-warning text-dark">${request.jvNo}</span>`;
                        }

                        listContainer.append(`
                            <li class="list-group-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span><strong>#${request.requestNo}</strong></span>
                                    ${amountBadgeHtml}
                                </div>
                                <div class="small text-muted mt-1">
                                    ${extraInfo}
                                </div>
                                <input type="hidden" name="payment_requests[]" value="${request.id}">
                                <input type="hidden" name="adjustment_amounts[${request.id}]" value="${request.jvAdj.toFixed(2)}">
                            </li>
                        `);
                    });

                    if (totalJvAdj > 0) {
                        listContainer.append(`
                            <li class="list-group-item list-group-item-secondary d-flex justify-content-between align-items-center py-2">
                                <span>Gross Amount</span>
                                <span>${totalGross.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</span>
                            </li>
                            <li class="list-group-item list-group-item-warning d-flex justify-content-between align-items-center py-2">
                                <span>Total JV Adjustment</span>
                                <span>-${totalJvAdj.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</span>
                            </li>
                            <li class="list-group-item list-group-item-primary d-flex justify-content-between align-items-center py-2">
                                <strong>Net Payable Total</strong>
                                <strong class="h5 mb-0">${totalNet.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</strong>
                            </li>
                        `);
                    } else {
                        listContainer.append(`
                            <li class="list-group-item list-group-item-primary d-flex justify-content-between align-items-center py-2">
                                <strong>Total Amount</strong>
                                <strong class="h5 mb-0">${totalGross.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</strong>
                            </li>
                        `);
                    }

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