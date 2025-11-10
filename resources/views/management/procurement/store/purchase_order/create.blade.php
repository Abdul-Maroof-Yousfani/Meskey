<form action="{{ route('store.purchase-order.store') }}" method="POST" id="ajaxSubmit" autocomplete="off">
    @csrf
    <input type="hidden" id="listRefresh" value="{{ route('store.get.purchase-order') }}" />
    <div class="row form-mar">
        <div class="col-md-3">
            <div class="form-group">
                <label>Purchase Request:</label>
                <select class="form-control select2" name="purchase_request_id">
                    <option value="">Select Purchase Request</option>
                    @foreach ($approvedRequests ?? [] as $value)
                        <option value="{{ $value->id }}">
                            {{ $value->purchase_request_no }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
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
                <label class="form-label">Quotation</label>
                <select id="quotation_no" name="quotation_no" class="form-control select2">
                    <option value="">Select Quotation</option>

                </select>
                {{-- <input type="text" name="quotation_no" id="quotation_no" class="form-control"
                    placeholder="Quotation number will appear here" value="" readonly> --}}
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label>Purchase Order Date:</label>
                <input type="date" id="purchase_date" name="purchase_date" class="form-control">
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
                <select disabled name="company_location" id="company_location_id" class="form-control select2">
                    <option value="">Select Location</option>
                    @foreach (get_locations() as $value)
                        <option value="{{ $value->id }}">{{ $value->name }}</option>
                    @endforeach
                    <input type="hidden" name="location_id" id="location_id">
                </select>
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label>Payment Term:</label>
                <select name="payment_term_id" id="payment_term_id" class="form-control select2">
                    <option value="">Select Payment Term</option>
                    @foreach ($payment_terms as $payment_term)
                        <option value="{{ $payment_term->id }}">{{ $payment_term->desc }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Description (Optional):</label>
                <textarea readonly name="description" id="description" placeholder="Description"
                    class="form-control"></textarea>
            </div>
        </div>
    </div>
    <div class="row form-mar">
        <div class="col-md-12">
            <table class="table table-bordered" id="purchaseRequestTable">
                <thead>
                    <tr>
                        {{-- <th></th> --}}
                        <th>Category</th>
                        <th>Item</th>
                        <th>Item UOM</th>

                        {{-- <th>Vendor</th> --}}
                        <th>Qty</th>
                        <th>Rate</th>
                        <th>Gross Amount</th>
                        <th>Tax</th>
                        <th>Tax Amount</th>
                        <th>Duty</th>
                        <th>Amount</th>
                        <th>Min Weight</th>
                        <th>Color</th>
                        <th>Cons./sq. in.</th>
                        <th>Size</th>
                        <th>Stitching</th>
                        <th>Printing Sample</th>
                        <th>Remarks</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="purchaseOrderBody"></tbody>
            </table>
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
    $(document).ready(function () {

        $(document).on('change', '#purchase_date', function () {
            fetchUniqueNumber();
        });

        $(document).on('change', 'select[name="purchase_request_id"]', function () {
            const purchaseRequestId = $(this).val();
            if (purchaseRequestId) {
                get_purchase(purchaseRequestId);
            }
        });

        $(document).on('change', '#quotation_no', function () {
            const purchaseRequestId = $('select[name="purchase_request_id"]').val();
            if (purchaseRequestId) {
                get_purchase(purchaseRequestId);
            }
        });

        $(document).on('change', '#supplier_id, [name="purchase_request_id"]', function () {
            const supplierId = $('#supplier_id').val();
            const purchaseRequestId = $('[name="purchase_request_id"]').val();
            $('#quotation_no').empty();
            if (supplierId && purchaseRequestId) {
                initializeDynamicDependentCall1Select2(
                    '#supplier_id',
                    '#quotation_no',
                    'suppliers',
                    'purchase_quotation_no',
                    'id',
                    'purchase_quotations',
                    'supplier_id',
                    'purchase_quotation_no',
                    true,
                    false,
                    true,
                    true,
                    { purchase_request_id: purchaseRequestId }
                );
            }
        });

    });
    $(".select2").select2();
    let rowIndex = 1;
    function fetchUniqueNumber() {
        let locationId = $('#company_location_id').val();
        let contractDate = $('#purchase_date').val();
        if (locationId && contractDate) {
            let url = '/procurement/store/get-unique-number-order/' + locationId + '/' + contractDate;
            $.ajax({
                url: url,
                type: 'GET',
                success: function (response) {
                    if (typeof response === 'string') {
                        $('#reference_no').val(response);
                    } else {
                        $('#reference_no').val('');
                    }
                },
                error: function (xhr, status, error) {
                    $('#reference_no').val('');
                }
            });
        } else {
            $('#reference_no').val('');
        }
    }
    // $('#company_location_id, #purchase_date').on('change', fetchUniqueNumber);

    function get_purchase(purchaseRequestId = null) {
        const quotationNo = $('#quotation_no').val();
        const supplierId = $('#supplier_id').val();

        if (!purchaseRequestId && !quotationNo) return;

        if (!purchaseRequestId) {
            purchaseRequestId = $('select[name="purchase_request_id"]').val();
        }

        $.ajax({
            url: "{{ route('store.purchase-order.approve-item') }}",
            type: "GET",
            data: {
                id: purchaseRequestId,
                quotation_no: quotationNo,
                supplier_id: supplierId
            },
            beforeSend: function () {
                $('#purchaseOrderBody').html('<p>Loading...</p>');
            },
            success: function (response) {
                let html = response.html;
                let master = response.master;
                $('#company_location_id').val(master.location_id);
                $('#location_id').val(master.location_id);
                $('#description').val(master.description);
                $('#company_location_id').val(master.location_id).trigger('change');
                $('#purchaseOrderBody').html(html);
                $('.select2').select2({
                    placeholder: 'Please Select',
                    width: '100%'
                });
            },
            error: function () {
                $('#purchaseOrderBody').html('<p>Error loading data.</p>');
            }
        });
    }

    // $('#quotation_no, select[name="purchase_request_id"]').on('change', function () {
    //     const purchaseRequestId = $('select[name="purchase_request_id"]').val();
    //     $('input[name="qty[]"], input[name="rate[]"], input[name="total[]"]').each(function () {
    //         $(this).val(''); // set to empty
    //     });
    //     get_purchase(purchaseRequestId);
    // });


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