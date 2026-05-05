

<style>
     html, body {
        overflow-x: hidden;
    }
      #purchaseRequestTable input,
#purchaseRequestTable select {
    width: 100% !important;
    min-width: 120px; /* optional: prevents inputs from being too narrow */
    box-sizing: border-box; /* ensures padding/border don't break width */
}
</style>

<form style="overflow-x: hidden;" action="{{ route('store.purchase-bill.store') }}" method="POST" id="ajaxSubmit" autocomplete="off">
    @csrf
    <input type="hidden" id="listRefresh" value="{{ route('store.get.purchase-bill') }}" />
    
    <div class="row form-mar">
        <div class="col-md-3">
            <div class="form-group">
                <label class="form-label">Supplier:</label>
                <select id="supplier_id" name="supplier_id" class="form-control item-select select2">
                    <option value="">Select Vendor</option>
                    @foreach (get_supplier() as $supplier)
                        <option value="{{ $supplier->id }}">
                            {{ $supplier->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label>GRN:</label>
                <select class="form-control select2" name="grn_no" id="grn_no">
                    <option value="">Select GRN</option>
                    
                </select>
            </div>
        </div>
        
        
        <div class="col-md-3">
            <div class="form-group">
                <label>Bill Date:</label>
                <input type="date" id="purchase_date" min="{{ date('Y-m-d') }}" name="purchase_bill_date" class="form-control" value="{{ date('Y-m-d') }}">
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label class="form-label">Reference No:</label>
                <input type="text" name="reference_no" placeholder="Please select location and date." readonly
                    id="reference_no" class="form-control">
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label>Location:</label>
                <select disabled name="company_location[]" id="company_location_id" class="form-control select2" multiple>
                    @foreach (get_locations() as $value)
                        <option value="{{ $value->id }}">{{ $value->name }}</option>
                    @endforeach
                    <input type="hidden" name="location_id" id="location_id">
                </select>
                <input type="hidden" name="company_location" value="1"/>
            </div>
        </div>
        <div class="col-xs-12 col-sm-12 col-md-12 row">
            <div class="form-group col-6">
                <label>Description (Optional):</label>
                <textarea name="purchase_bill_description" id="description" placeholder="Description" class="form-control"></textarea>
            </div>
        </div>
    </div>
    <div class="row form-mar">
        <div class="col-md-12">
            <div style="overflow-x: auto; white-space: nowrap; width: 100%;">
                <table class="table table-bordered" id="purchaseRequestTable" style="min-width: 3500px;">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Description</th>
                            <th>Total Qty</th>
                            <th>Accepted Quantity</th>
                            <th>Rejected Quantity</th>
                            <th>Rate</th>
                            <th>Gross Amount</th>
                            <th>Discount %</th>
                            <th>Discount Amount</th>
                            <th class="deduction-header">Deduction Per Piece</th>
                            <th class="deduction-header">Deduction</th>
                            <th>Amount</th>
                            <th>Printing Samples</th>
                            <th>GST %</th>
                            <th>GST Amount</th>
                            <th>Net Amount</th>
                            <th>Action</th>
                        </tr>
                    </thead>

                    <tbody id="billBody"></tbody>
                </table>
            </div>
        </div>

    </div>

    <div class="row form-mar">
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Other Terms:</label>
                <textarea name="other_term" id="other_term" placeholder="Other Terms" class="form-control">1. EVERY BILL MUST SHOW OUR CONTRACT NUMBER 
2. SELLER HAS TO REPLACE THE REJECTED ITEMS (IF ANY) WITHIN THE STIPULATED TIME</textarea>
            </div>
        </div>
    </div>

    <input type="hidden" id="rowCount" value="0">
    <div class="row bottom-button-bar">
        <div class="col-12">
            <a type="button" class="btn btn-danger modal-sidebar-close position-relative top-1 closebutton">Close</a>
            <button type="submit" class="btn btn-primary submitbutton">Save</button>
        </div>
    </div>
</form>




<script>
    $(document).ready(function() {
        // Initial setup for existing data if any (mostly for edit)
        getGrns();

        $(document).on('change', '#purchase_date', function() {
            fetchUniqueNumber();
        });

        // When Supplier changes: Refresh GRN dropdown and clear table
        $(document).on('change', '#supplier_id', function() {
            const $grnSelect = $('#grn_no');
            $grnSelect.val(null).trigger('change');
            $('#billBody').empty();
            getGrns();
        });

        // When GRN changes: Refresh items table
        $(document).on('change', '#grn_no', function() {
            const grnId = $(this).val();
            if (grnId) {
                get_purchase(grnId);
            } else {
                $('#billBody').empty();
            }
        });

    function get_purchase(purchaseOrderReceivingId) {
        if (!purchaseOrderReceivingId) return;
        const supplierId = $('#supplier_id').val();
        $.ajax({
            url: "{{ route('store.purchase-bill.approve-item') }}",
            type: "GET",
            data: { id: purchaseOrderReceivingId, supplier_id: supplierId },
            cache: false,
            beforeSend: function () {
                $('#billBody').html('<p>Loading...</p>');
            },
            success: function (response) {
                $('#company_location_id').val(response.location_ids).trigger('change');
                $('#billBody').html(response.html);
                const firstRow = $('#billBody').find('tr').first();
                const categoryId = firstRow.data('category-id');
                if (categoryId != 38) {
                    $('.deduction-header').hide();
                } else {
                    $('.deduction-header').show();
                }
            },
            error: function () {
                $('#purchaseRequestBody').html('<p>Error loading data.</p>');
            }
        });
    }

    function getGrns() {
        let url = '/procurement/store/get-grns/';
        const $targetEl = $("#grn_no");
        $targetEl.select2({
            ajax: {
                url: url,
                dataType: "json",
                delay: 250,
                data: function (params) {
                    return {
                        supplier_id: $("#supplier_id").val(),
                        q: params.term
                    };
                },
                processResults: function (data) {
                    return {
                        results: data,
                    };
                },
            },
            minimumInputLength: 0,
            allowClear: true,
            placeholder: "Select GRN",
        });
    }


    });
    $(".select2").select2();
    rowIndex = 1;



    function fetchUniqueNumber() {
        let locationId = $('#company_location_id').val();
        let contractDate = $('#purchase_date').val();
        if (locationId && contractDate) {
            let url = '/procurement/store/get-unique-number-purchase-bill/' + locationId + '/' + contractDate;
            $.ajax({
                url: url,
                type: 'GET',
                success: function(response) {
                    if (typeof response === 'string') {
                        $('#reference_no').val(response);
                    } else {
                        $('#reference_no').val('');
                    }
                },
                error: function(xhr, status, error) {
                    $('#reference_no').val('');
                }
            });
        } else {
            $('#reference_no').val('');
        }
    }
    $('#company_location_id, #purchase_date').on('change', fetchUniqueNumber);




    function calc(num) {
        var excise_duty = parseFloat($('#excise_duty_' + num).val()) || 0;
        var qty = parseFloat($('#qty_' + num).val()) || 0;
        var rate = parseFloat($('#rate_' + num).val()) || 0;

        // get selected option and its data attribute
        var selectedOption = $('#tax_id_' + num + ' option:selected');
        var tax_percentage = parseFloat(selectedOption.data('percentage')) || 0;

        var subtotal = qty * rate;
        var tax_amount = subtotal * (tax_percentage / 100);
        var total = subtotal + tax_amount + excise_duty;

        $('#total_' + num).val(total.toFixed(2));
    }
</script>
