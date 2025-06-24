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
                         <h4 class="mb-0">Create Payment Voucher</h4>
                         <a href="{{ route('payment-voucher.index') }}" class="btn btn-sm btn-primary">Back</a>
                     </div>
                     <div class="card-body">
                         <form id="paymentVoucherForm">
                             @csrf

                             <div class="row">
                                 <div class="col-md-6">
                                     <div class="form-group">
                                         <label for="voucher_type">Voucher Type</label>
                                         <select name="voucher_type" id="voucher_type" class="form-control select2"
                                             required>
                                             <option value="">Select Type</option>
                                             <option value="bank_payment_voucher">Bank Payment Voucher</option>
                                             <option value="cash_payment_voucher">Cash Payment Voucher</option>
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
                                         <input type="text" name="unique_no" id="unique_no" class="form-control"
                                             readonly>
                                     </div>
                                 </div>
                                 <div class="col-md-6">
                                     <div class="form-group">
                                         <label for="account_id">Account</label>
                                         <select name="account_id" id="account_id" class="form-control select2" required>
                                             <option value="">Select Account</option>
                                             @foreach ($accounts as $account)
                                                 <option value="{{ $account->account_id }}">{{ $account->name }}
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

                             <div class="row">
                                 <div class="col-md-12">
                                     <div class="form-group">
                                         <label for="module_id">Purchase Order</label>
                                         <select name="module_id" id="module_id" class="form-control select2" required>
                                             <option value="">Select Purchase Order</option>
                                             @foreach ($purchaseOrders as $order)
                                                 <option value="{{ $order->id }}">{{ $order->contract_no }} -
                                                     {{ $order->product->name ?? 'N/A' }}
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
                                                         <tr>
                                                             <td colspan="6" class="text-center">No payment requests
                                                                 found please select purchase order first.</td>
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
                                             <ul class="list-group selected-requests-list" style="display: none;">
                                                 <!-- Will be populated via JS -->
                                             </ul>
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
         $(document).ready(function() {
             // Generate PV number when type and date are selected
             $('#voucher_type, #pv_date').change(function() {
                 if ($('#voucher_type').val() && $('#pv_date').val()) {
                     $.ajax({
                         url: '{{ route('payment-voucher.generate-pv-number') }}',
                         type: 'POST',
                         data: {
                             _token: '{{ csrf_token() }}',
                             voucher_type: $('#voucher_type').val(),
                             pv_date: $('#pv_date').val()
                         },
                         success: function(response) {
                             if (response.success) {
                                 $('#unique_no').val(response.pv_number);
                             }
                         }
                     });
                 }
             });

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
                     $.ajax({
                         url: `/finance/payment-voucher/payment-requests/${poId}`,
                         type: 'GET',
                         success: function(response) {
                             if (response.success) {
                                 const tbody = $('#paymentRequestsTable tbody');
                                 tbody.empty();

                                 if (response.payment_requests.length > 0) {
                                     $.each(response.payment_requests, function(index, request) {
                                         tbody.append(`
                                    <tr>
                                        <td>
                                            <input type="checkbox" class="request-checkbox" value="${request.id}" data-amount="${request.amount}" data-purpose="${request.purpose}">
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
             });

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
                     url: '{{ route('payment-voucher.store') }}',
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
