<form action="{{ route('export-filing.store') }}" method="POST" id="ajaxSubmit" autocomplete="off"
    enctype="multipart/form-data">
    @csrf
    <input type="hidden" id="listRefresh" value="{{ route('get.export-filing') }}" />

    <div class="row form-mar">
        <div class="col-12">
            <div class="form-group">
                <label>Export Order:</label>
                <select class="form-control select2" name="export_order_id" id="filing_export_order_id">
                    <option value="">Select Export Order</option>
                    @foreach ($exportOrders as $order)
                        <option value="{{ $order->id }}">
                            {{ $order->voucher_no }}
                            @if ($order->contract_no) -- {{ $order->contract_no }} @endif
                            @if ($order->buyer) -- {{ $order->buyer->name }} @endif
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <div id="filingDocumentsSection" style="display: none;">
        <div class="row">
            <div class="col-12 d-flex justify-content-between align-items-center mb-1">
                <h6 class="mb-0">Documents</h6>
                <button type="button" class="btn btn-sm btn-success" id="addFilingDocument">
                    <i class="ft-plus"></i> Add Document
                </button>
            </div>
        </div>

        <div id="filingDocumentRows"></div>

        <div class="row">
            <div class="col-12">
                <div class="form-group">
                    <label>Remarks:</label>
                    <textarea name="remarks" class="form-control" rows="2" placeholder="Optional remarks"></textarea>
                </div>
            </div>
        </div>
    </div>

    <div class="row bottom-button-bar">
        <div class="col-12">
            <a type="button" class="btn btn-danger modal-sidebar-close position-relative top-1 closebutton">Close</a>
            <button type="submit" class="btn btn-primary submitbutton">Save</button>
        </div>
    </div>
</form>

<script>
    $(document).ready(function() {
        $('.select2').select2();

        var filingRowIndex = 0;

        function addFilingRow() {
            var i = filingRowIndex++;
            var row = `
                <div class="row filing-document-row align-items-end border rounded mx-0 mb-1 pt-1">
                    <div class="col-md-5">
                        <div class="form-group">
                            <label>Document Name:</label>
                            <input type="text" name="documents[${i}][name]" class="form-control" placeholder="e.g. Bill of Lading">
                        </div>
                    </div>
                    <div class="col-md-5">
                        <div class="form-group">
                            <label>File:</label>
                            <input type="file" name="documents[${i}][file]" class="form-control">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <button type="button" class="btn btn-danger btn-block remove-filing-document" title="Remove">
                                <i class="ft-trash-2"></i>
                            </button>
                        </div>
                    </div>
                </div>`;
            $('#filingDocumentRows').append(row);
        }

        // Show the documents section only after an export order is chosen
        $('#filing_export_order_id').on('change', function() {
            if ($(this).val()) {
                $('#filingDocumentsSection').show();
                if ($('#filingDocumentRows .filing-document-row').length === 0) {
                    addFilingRow();
                }
            } else {
                $('#filingDocumentsSection').hide();
            }
        });

        $('#addFilingDocument').on('click', addFilingRow);

        $('#filingDocumentRows').on('click', '.remove-filing-document', function() {
            if ($('#filingDocumentRows .filing-document-row').length === 1) {
                Swal.fire('Info', 'At least one document is required.', 'info');
                return;
            }
            $(this).closest('.filing-document-row').remove();
        });
    });
</script>
