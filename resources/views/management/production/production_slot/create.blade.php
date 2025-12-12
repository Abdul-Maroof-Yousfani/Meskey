<form action="{{ route('production-slot.store') }}" method="POST" id="ajaxSubmit" autocomplete="off">
    @csrf
    <input type="hidden" id="listRefresh" value="{{ route('get.production-slot') }}" />

    <div class="row form-mar">
        <div class="col-md-12">
            <h6 class="header-heading-sepration">Production Slot</h6>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Production Voucher:</label>
                        <select name="production_voucher_id" id="production_voucher_id" class="form-control select2" required>
                            <option value="">Select Production Voucher</option>
                            @foreach($productionVouchers as $voucher)
                                <option value="{{ $voucher->id }}">{{ $voucher->prod_no }} - {{ $voucher->prod_date->format('Y-m-d') }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        <label>Date:</label>
                        <input type="date" name="date" class="form-control" value="{{ date('Y-m-d') }}" required>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        <label>Status:</label>
                        <select name="status" class="form-control">
                            <option value="active" selected>Active</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        <label>Start Time:</label>
                        <input type="time" name="start_time" class="form-control" required>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        <label>End Time:</label>
                        <input type="time" name="end_time" class="form-control">
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        <label>Remarks:</label>
                        <textarea name="remarks" class="form-control" rows="2"></textarea>
                    </div>
                </div>
            </div>
        </div>

        <!-- Breaks Section -->
        <div class="col-md-12 mt-4">
            <h6 class="header-heading-sepration">Breaks</h6>
            <div class="table-responsive">
                <table class="table table-bordered" id="breaksTable">
                    <thead>
                        <tr>
                            <th>Break In</th>
                            <th>Break Out</th>
                            <th>Reason</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="breaksTableBody">
                        <!-- Breaks will be added here dynamically -->
                    </tbody>
                </table>
            </div>
            <div class="mt-2">
                <button type="button" class="btn btn-success btn-sm" onclick="addBreakRow()">
                    <i class="ft-plus"></i> Add Break
                </button>
            </div>
        </div>
    </div>

    <div class="row bottom-button-bar">
        <div class="col-12">
            <a type="button" class="btn btn-danger modal-sidebar-close position-relative top-1 closebutton">Close</a>
            <button type="submit" class="btn btn-primary submitbutton">Save Production Slot</button>
        </div>
    </div>
</form>

<script>
    let breakRowIndex = 0;

    $(document).ready(function () {
        $('.select2').select2();
    });

    function addBreakRow(breakIn = '', breakOut = '', reason = '', breakId = null) {
        const row = `
            <tr data-break-index="${breakRowIndex}" data-break-id="${breakId || ''}">
                <td>
                    <input type="time" name="breaks[${breakRowIndex}][break_in]" class="form-control form-control-sm" value="${breakIn}" required>
                    ${breakId ? `<input type="hidden" name="breaks[${breakRowIndex}][id]" value="${breakId}">` : ''}
                </td>
                <td>
                    <input type="time" name="breaks[${breakRowIndex}][break_out]" class="form-control form-control-sm" value="${breakOut}">
                </td>
                <td>
                    <input type="text" name="breaks[${breakRowIndex}][reason]" class="form-control form-control-sm" value="${reason}" placeholder="Break reason">
                </td>
                <td>
                    <button type="button" class="btn btn-sm btn-danger" onclick="removeBreakRow(this)">
                        <i class="ft-trash"></i>
                    </button>
                </td>
            </tr>
        `;
        $('#breaksTableBody').append(row);
        breakRowIndex++;
    }

    function removeBreakRow(button) {
        $(button).closest('tr').remove();
    }

    // Handle form submission - serialize breaks properly
    $('#ajaxSubmit').on('submit', function(e) {
        // Breaks are already in the form, no need to modify
    });
</script>

