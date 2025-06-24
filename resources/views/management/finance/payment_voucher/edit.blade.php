 @extends('management.layouts.master')
 @section('title')
     Loading Management
 @endsection
 @section('content')
     <div class="container-fluid">
         <div class="row">
             <div class="col-md-12">
                 <div class="card">
                     <div class="card-header d-flex justify-content-between align-items-center">
                         <h4 class="mb-0">Edit Payment Voucher: {{ $paymentVoucher->unique_no }}</h4>
                         <a href="{{ route('payment-voucher.index') }}" class="btn btn-sm btn-primary">Back</a>
                     </div>
                     <div class="card-body">
                         <form id="paymentVoucherForm">
                             @csrf
                             @method('PUT')

                             <div class="row">
                                 <div class="col-md-6">
                                     <div class="form-group">
                                         <label for="voucher_type">Voucher Type</label>
                                         <select name="voucher_type" id="voucher_type" class="form-control" required
                                             disabled>
                                             <option value="bank_payment_voucher"
                                                 {{ $paymentVoucher->voucher_type === 'bank_payment_voucher' ? 'selected' : '' }}>
                                                 Bank Payment Voucher</option>
                                             <option value="cash_payment_voucher"
                                                 {{ $paymentVoucher->voucher_type === 'cash_payment_voucher' ? 'selected' : '' }}>
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
                                         <select name="account_id" id="account_id" class="form-control" required>
                                             <option value="">Select Account</option>
                                             @foreach ($accounts as $account)
                                                 <option value="{{ $account->account_id }}"
                                                     {{ $paymentVoucher->account_id == $account->account_id ? 'selected' : '' }}>
                                                     {{ $account->account_name }} ({{ $account->account_number }})
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
                                 style="display: {{ $paymentVoucher->voucher_type === 'bank_payment_voucher' ? 'block' : 'none' }};">
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
                                         <select name="module_id" id="module_id" class="form-control" required>
                                             <option value="">Select Purchase Order</option>
                                             @foreach ($purchaseOrders as $order)
                                                 <option value="{{ $order->id }}"
                                                     {{ $paymentVoucher->module_id == $order->id ? 'selected' : '' }}>
                                                     {{ $order->contract_no }} - {{ $order->product->name ?? 'N/A' }}
                                                     ({{ $order->company->name ?? 'N/A' }})
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
                                                             <th>Status</th>
                                                         </tr>
                                                     </thead>
                                                     <tbody>
                                                         <!-- Will be populated via AJAX -->
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
                                             @if (count($selectedRequests) > 0)
                                                 <ul class="list-group selected-requests-list">
                                                     @php $totalAmount = 0; @endphp
                                                     @foreach ($paymentVoucher->paymentVoucherData as $data)
                                                         @php $totalAmount += $data->amount; @endphp
                                                         <li
                                                             class="list-group-item d-flex justify-content-between align-items-center">
                                                             <span>Request #{{ $data->paymentRequest->id }} -
                                                                 {{ $data->paymentRequest->paymentRequestData->notes ?? 'No description' }}</span>
                                                             <span
                                                                 class="badge badge-primary badge-pill">{{ $data->amount }}</span>
                                                             <input type="hidden" name="payment_requests[]"
                                                                 value="{{ $data->payment_request_id }}">
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
                                                 <ul class="list-group selected-requests-list" style="display: none;">
                                                 </ul>
                                             @endif
                                         </div>
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
             // Show/hide bank fields based on voucher type
             $('#voucher_type').change(function() {
                 if ($(this).val() === 'bank_payment_voucher') {
                     $('.bank-fields').show();
                     $('#cheque_no, #cheque_date').prop('required', true);
                 } else {
                     $('.bank-fields').hide();
                     $('#cheque_no, #cheque_date').prop('required', false);
                 }
             });

             // Load payment requests when purchase order is selected
             $('#module_id').change(function() {
                 const poId = $(this).val();
                 if (poId) {
                     loadPaymentRequests(poId);
                 }
             });

             // Initial load if module_id is already selected
             @if ($paymentVoucher->module_id)
                 loadPaymentRequests({{ $paymentVoucher->module_id }});
             @endif

             function loadPaymentRequests(poId) {
                 $.ajax({
                     url: `/payment-voucher/payment-requests/${poId}`,
                     type: 'GET',
                     success: function(response) {
                         if (response.success) {
                             const tbody = $('#paymentRequestsTable tbody');
                             tbody.empty();

                             if (response.payment_requests.length > 0) {
                                 const selectedRequests = $('input[name="payment_requests[]"]').map(
                                     function() {
                                         return $(this).val();
                                     }).get();

                                 $.each(response.payment_requests, function(index, request) {
                                     const isChecked = selectedRequests.includes(request.id
                                         .toString());
                                     tbody.append(`
                                <tr>
                                    <td>
                                        <input type="checkbox" class="request-checkbox" value="${request.id}" 
                                            data-amount="${request.amount}" data-purpose="${request.purpose}"
                                            ${isChecked ? 'checked' : ''}>
                                    </td>
                                    <td>${request.request_no}</td>
                                    <td>${request.request_date}</td>
                                    <td>${request.amount}</td>
                                    <td>${request.purpose}</td>
                                    <td>
                                        <span class="badge badge-${request.status === 'approved' ? 'success' : 'warning'}">
                                            ${request.status}
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
                         }
                     }
                 });
             }

             // Handle selection of payment requests
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
                         purpose: $(this).data('purpose')
                     });
                     totalAmount += parseFloat($(this).data('amount'));
                 });

                 const listContainer = $('.selected-requests-list');
                 const emptyMessage = $('.selected-requests-container p');

                 if (selectedRequests.length > 0) {
                     listContainer.empty();
                     emptyMessage.hide();

                     $.each(selectedRequests, function(index, request) {
                         listContainer.append(`
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span>Request #${request.id} - ${request.purpose}</span>
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

             // Form submission
             $('#paymentVoucherForm').submit(function(e) {
                 e.preventDefault();

                 const formData = $(this).serialize();

                 $.ajax({
                     url: '{{ route('payment-voucher.update', $paymentVoucher->id) }}',
                     type: 'POST',
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
 @endsection
