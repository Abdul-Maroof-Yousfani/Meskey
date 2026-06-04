<form id="ajaxSubmit" class="form" method="POST" action="{{ route('sales.payment-intimation.store') }}" autocomplete="off">
    @csrf
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
                                <option value="{{ $customer->id }}">{{ $customer->name }} ({{ $customer->unique_no }})</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label" for="sale_order_id">Sale Order <span class="text-danger">*</span></label>
                        <select name="sale_order_id" id="sale_order_id" class="form-control select2" style="width: 100%" required>
                            <option value="">Select Sale Order</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label" for="bank_id">Bank <span class="text-danger">*</span></label>
                        <select name="bank_id" id="bank_id" class="form-control select2" style="width: 100%" required>
                            <option value="">Select Bank</option>
                            @foreach ($banks as $bank)
                                <option value="{{ $bank->id }}">{{ $bank->bank_name }} - {{ $bank->account_no }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label" for="payment_deposit">Payment Deposit <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" class="form-control" id="payment_deposit" name="payment_deposit" required placeholder="Enter amount">
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row bottom-button-bar mt-2">
        <div class="col-12 text-right">
            <a type="button" class="btn btn-danger modal-sidebar-close position-relative top-1 closebutton me-2">Close</a>
            <button type="submit" class="btn btn-primary submitbutton">Save</button>
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
    });
</script>
