<form id="ajaxSubmit" class="form" method="POST" action="{{ route('sales.payment-intimation.update', $payment_intimation->id) }}" autocomplete="off" enctype="multipart/form-data">
    @csrf
    @method('PUT')
    <input type="hidden" id="listRefresh" value="{{ route('sales.get.payment-intimation.list') }}" />
    <div class="row form-mar">
        <div class="col-md-12">
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label" for="customer_id">Customer <span class="text-danger">*</span></label>
                        <select name="customer_id" id="customer_id" class="form-control select2" style="width: 100%" required>
                            <option value="">Select Customer</option>
                            @foreach ($customers as $customer)
                                <option value="{{ $customer->id }}" {{ $payment_intimation->customer_id == $customer->id ? 'selected' : '' }}>{{ $customer->name }} ({{ $customer->unique_no }})</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label" for="sale_order_id">Sale Order <span class="text-danger">*</span></label>
                        <select name="sale_order_id" id="sale_order_id" class="form-control select2" style="width: 100%" required>
                            <option value="{{ $payment_intimation->sale_order_id }}">{{ $payment_intimation->sale_order->reference_no ?? '' }}</option>
                        </select>
                    </div>
                </div>

                {{-- Bank & Payment Deposit Dynamic Rows Section --}}
                <div class="col-md-12 mt-2 mb-2">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label class="form-label font-weight-bold mb-0">Bank & Payment Deposit <span class="text-danger">*</span></label>
                        <button type="button" class="btn btn-primary btn-sm" id="addDepositBtn">
                            <i class="ft-plus"></i> Add
                        </button>
                    </div>
                    <div id="depositContainer">
                        @php
                            $existingDeposits = $payment_intimation->deposits;
                            $hasDeposits = $existingDeposits && $existingDeposits->count() > 0;
                        @endphp

                        @if($hasDeposits)
                            @foreach($existingDeposits as $index => $deposit)
                                <div class="row deposit-row align-items-center mb-2">
                                    <div class="col-md-6">
                                        <div class="form-group mb-0">
                                            <label class="form-label">Bank <span class="text-danger">*</span></label>
                                            <select name="deposits[{{ $index }}][bank_id]" class="form-control select2 deposit-bank" style="width: 100%" required>
                                                <option value="">Select Bank</option>
                                                @foreach ($banks as $bank)
                                                    <option value="{{ $bank->id }}" {{ $deposit->bank_id == $bank->id ? 'selected' : '' }}>{{ $bank->bank_name }} - {{ $bank->account_no }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group mb-0">
                                            <label class="form-label">Payment Deposit <span class="text-danger">*</span></label>
                                            <input type="number" step="0.01" class="form-control deposit-amount" name="deposits[{{ $index }}][payment_deposit]" value="{{ $deposit->payment_deposit }}" required placeholder="Enter amount">
                                        </div>
                                    </div>
                                    <div class="col-md-2 text-center" style="padding-top: 24px;">
                                        <button type="button" class="btn btn-danger btn-sm remove-deposit-btn {{ $existingDeposits->count() <= 1 ? 'd-none' : '' }}" title="Remove Row">
                                            <i class="ft-trash-2"></i>
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <div class="row deposit-row align-items-center mb-2">
                                <div class="col-md-5">
                                    <div class="form-group mb-0">
                                        <label class="form-label">Bank <span class="text-danger">*</span></label>
                                        <select name="deposits[0][bank_id]" class="form-control select2 deposit-bank" style="width: 100%" required>
                                            <option value="">Select Bank</option>
                                            @foreach ($banks as $bank)
                                                <option value="{{ $bank->id }}" {{ $payment_intimation->bank_id == $bank->id ? 'selected' : '' }}>{{ $bank->bank_name }} - {{ $bank->account_no }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-5">
                                    <div class="form-group mb-0">
                                        <label class="form-label">Payment Deposit <span class="text-danger">*</span></label>
                                        <input type="number" step="0.01" class="form-control deposit-amount" name="deposits[0][payment_deposit]" value="{{ $payment_intimation->payment_deposit }}" required placeholder="Enter amount">
                                    </div>
                                </div>
                                <div class="col-md-2 text-center" style="padding-top: 24px;">
                                    <button type="button" class="btn btn-danger btn-sm remove-deposit-btn d-none" title="Remove Row">
                                        <i class="ft-trash-2"></i>
                                    </button>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label" for="attachment">Attachment</label>
                        <input type="file" class="form-control" id="attachment" name="attachment">
                        @if($payment_intimation->attachment)
                            <small class="mt-1 d-block"><a href="{{ asset($payment_intimation->attachment) }}" target="_blank">View Current Attachment</a></small>
                        @endif
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">Total Payment Deposit</label>
                        <input type="text" class="form-control" id="total_payment_deposit_display" readonly value="{{ number_format($payment_intimation->payment_deposit, 2, '.', '') }}" style="background-color: #f8f9fa; font-weight: bold;">
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row bottom-button-bar mt-2">
        <div class="col-12 text-right">
            <a type="button" class="btn btn-danger modal-sidebar-close position-relative top-1 closebutton me-2">Close</a>
            <button type="submit" class="btn btn-primary submitbutton">Update</button>
        </div>
    </div>
</form>

<script>
    $(document).ready(function() {
        $('.select2').select2({
            width: '100%'
        });

        $('#customer_id').on('change', function() {
            let customer_id = $(this).val();
            let sale_order_dropdown = $('#sale_order_id');
            sale_order_dropdown.empty().append('<option value="">Select Sale Order</option>');
            
            if (customer_id) {
                $.ajax({
                    url: '{{ route("sales.get-customer-sale-orders") }}',
                    type: 'GET',
                    data: { customer_id: customer_id },
                    success: function(response) {
                        response.forEach(function(order) {
                            sale_order_dropdown.append('<option value="' + order.id + '">' + order.text + '</option>');
                        });
                    },
                    error: function(xhr) {
                        console.log(xhr.responseText);
                    }
                });
            }
        });

        let depositIndex = {{ $hasDeposits ? $existingDeposits->count() : 1 }};

        $('#addDepositBtn').on('click', function() {
            let rowHtml = `
                <div class="row deposit-row align-items-center mb-2">
                    <div class="col-md-5">
                        <div class="form-group mb-0">
                            <label class="form-label">Bank <span class="text-danger">*</span></label>
                            <select name="deposits[${depositIndex}][bank_id]" class="form-control select2 deposit-bank" style="width: 100%" required>
                                <option value="">Select Bank</option>
                                @foreach ($banks as $bank)
                                    <option value="{{ $bank->id }}">{{ $bank->bank_name }} - {{ $bank->account_no }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-5">
                        <div class="form-group mb-0">
                            <label class="form-label">Payment Deposit <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" class="form-control deposit-amount" name="deposits[${depositIndex}][payment_deposit]" required placeholder="Enter amount">
                        </div>
                    </div>
                    <div class="col-md-2 text-center" style="padding-top: 24px;">
                        <button type="button" class="btn btn-danger btn-sm remove-deposit-btn" title="Remove Row">
                            <i class="ft-trash-2"></i>
                        </button>
                    </div>
                </div>
            `;

            let $row = $(rowHtml);
            $('#depositContainer').append($row);
            $row.find('.select2').select2({ width: '100%' });
            depositIndex++;
            updateRemoveButtons();
            calculateTotal();
        });

        $(document).on('click', '.remove-deposit-btn', function() {
            $(this).closest('.deposit-row').remove();
            updateRemoveButtons();
            calculateTotal();
        });

        $(document).on('input', '.deposit-amount', function() {
            calculateTotal();
        });

        function updateRemoveButtons() {
            let rows = $('#depositContainer .deposit-row');
            if (rows.length <= 1) {
                rows.find('.remove-deposit-btn').addClass('d-none');
            } else {
                rows.find('.remove-deposit-btn').removeClass('d-none');
            }
        }

        function calculateTotal() {
            let total = 0;
            $('.deposit-amount').each(function() {
                let val = parseFloat($(this).val()) || 0;
                total += val;
            });
            $('#total_payment_deposit_display').val(total.toFixed(2));
        }
    });
</script>
