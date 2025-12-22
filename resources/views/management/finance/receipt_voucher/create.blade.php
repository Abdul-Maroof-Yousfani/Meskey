@extends('management.layouts.master')
@section('title')
    Create Receipt Voucher
@endsection
@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">Create Receipt Voucher</h4>
                        <a href="{{ route('receipt-voucher.index') }}" class="btn btn-sm btn-primary">Back</a>
                    </div>
                    <div class="card-body">
                        <form>
                            @csrf
                            {{-- <input type="hidden" id="redirectUrl" value="{{ route('receipt-voucher.index') }}"> --}}
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="voucher_type">Voucher Type</label>
                                        <select name="voucher_type" id="voucher_type" class="form-control select2" required>
                                            <option value="">Select Type</option>
                                            <option value="bank_payment_voucher">Bank Receipt Voucher</option>
                                            <option value="cash_payment_voucher">Cash Receipt Voucher</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="rv_date">Date</label>
                                        <input type="date" name="rv_date" id="rv_date" class="form-control" required>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="unique_no">RV Number</label>
                                        <input type="text" name="unique_no" id="unique_no" class="form-control" readonly>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="account_id">Account</label>
                                        <select name="account_id" id="account_id" class="form-control select2" required>
                                            <option value="">Select Account</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="ref_bill_no">Receipt Ref No</label>
                                        <input type="text" name="ref_bill_no" id="ref_bill_no" class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="bill_date">Receipt Date</label>
                                        <input type="date" name="bill_date" id="bill_date" class="form-control">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="customer_id">Customer Account</label>
                                        <select name="customer_id" id="customer_id" class="form-control select2" onchange="select_customer()">
                                            <option value="">Select Customer</option>
                                            @foreach ($customers as $customer)
                                                <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label id="reference_label">Invoices (approved, receiving pending)</label>
                                        <select id="reference_ids" class="form-control select2" multiple style="width: 100%;">
                                            @foreach ($salesInvoices as $inv)
                                                <option value="{{ $inv->id }}" data-type="sales_invoice">
                                                    {{ $inv->si_no ?? 'INV-'.$inv->id }} - {{ optional($inv->customer)->name }} {{ $inv->reference_number ? ' | Ref: '.$inv->reference_number : '' }}
                                                </option>
                                            @endforeach
                                            @foreach ($saleOrders as $so)
                                                <option value="{{ $so->id }}" data-type="sale_order" class="d-none">
                                                    {{ $so->so_reference_no ?? $so->reference_no ?? ($so->so_no ?? 'SO-'.$so->id) }} - {{ optional($so->customer)->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <small class="text-muted d-block mt-1">Toggle "Advance" to switch between sale orders and invoices.</small>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="checkbox" id="is_advance" checked>
                                        <label class="form-check-label" for="is_advance">Advance</label>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label>Selected References</label>
                                        <div class="table-responsive">
                                            <table class="table table-bordered" id="referencesTable">
                                                <thead>
                                                    <tr>
                                                        <th width="5%"><input type="checkbox" id="select_all"></th>
                                                        <th>Type</th>
                                                        <th>Document No</th>
                                                        <th>Date</th>
                                                        <th>Customer</th>
                                                        <th width="12%">Amount</th>
                                                        <th width="12%">Tax</th>
                                                        <th width="12%">Tax Amount</th>
                                                        <th width="12%">Net Amount</th>
                                                        <th width="18%">Line Desc</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="rv-data">
                                                    <tr>
                                                        <td colspan="6" class="text-center text-muted">Select references to load details.</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label>Selected Documents</label>
                                        <div class="selected-docs-container bg-light p-3">
                                            <p class="text-muted">No documents selected yet</p>
                                            <ul class="list-group selected-docs-list" style="display: none;"></ul>
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
                                <button type="submit" class="btn btn-primary">Create Receipt Voucher</button>
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
    // This function can stay outside if it's called from elsewhere (e.g., onchange of customer)
    function select_customer() {
        $.ajax({
            url: '{{ route("receipt.voucher.get-documents") }}',
            data: {
                customer_id: $("#customer_id").val(),
                is_advance: $("#is_advance").is(":checked")
            },
            success: function (response) {
                console.log(response);
                $("#reference_ids").empty();
                $("#reference_ids").select2({ data: response });
            },
            error: function (xhr, status, error) {
                console.error(error);
                console.error(xhr.responseText);
            },
        });
    }

    $(document).ready(function () {
        const referenceSelect = $('#reference_ids');
        const referenceLabel = $('#reference_label');
        const referencesTableBody = $('#referencesTable tbody');
        const selectAll = $('#select_all');
        const listContainer = $('.selected-docs-list');
        const emptyMessage = $('.selected-docs-container p');
        const taxes = @json($taxes ?? []);

        // ==================== CORE FUNCTION: Update Selected Documents List ====================
                // ==================== CORE FUNCTION: Update Selected Documents List with TOTAL ====================
        function updateSelectedDocsList() {
            const selected = [];
            let total = 0;

            referencesTableBody.find('tr').each(function () {
                const row = $(this);
                const checkbox = row.find('.row-select');
                if (checkbox.length && checkbox.is(':checked')) {
                    const type = row.find('td').eq(1).text().trim() || '';
                    const number = row.find('td').eq(2).text().trim() || '';
                    const date = row.find('td').eq(3).text().trim() || '';
                    const customer = row.find('td').eq(4).text().trim() || '';
                    const netAmount = parseFloat(row.find('.net-amount').val()) || 0;

                    selected.push({
                        type,
                        number,
                        date,
                        customer,
                        amount: netAmount,
                        idx: checkbox.data('row')
                    });
                    total += netAmount;
                }
            });

            listContainer.empty();

            if (!selected.length) {
                emptyMessage.show();
                listContainer.hide();
                // Also hide/clear total if exists
                $('#selected-total-amount').hide();
                return;
            }

            emptyMessage.hide();
            listContainer.show();

            // Add each selected document
            selected.forEach(function (item) {
                listContainer.append(`
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <strong>${item.number}</strong> <span class="text-muted">(${item.type})</span>
                            <div class="small text-muted">${item.date} • ${item.customer}</div>
                        </div>
                        <span class="badge badge-primary badge-pill">${item.amount.toFixed(2)}</span>
                    </li>
                `);
            });

            // =============== ADD TOTAL AMOUNT AT BOTTOM ===============
            // First, remove any existing total row
            $('#selected-total-row').remove();

            // Append a strong total row
            listContainer.append(`
                <li id="selected-total-row" class="list-group-item active d-flex justify-content-between align-items-center font-weight-bold" style="background-color: #e9ecef; border-top: 3px double #ccc;">
                    <div class="text-dark">
                        <strong>Total Amount</strong>
                    </div>
                    <span class="badge badge-dark badge-pill" style="font-size: 1.1em;">
                        ${total.toFixed(2)}
                    </span>
                </li>
            `);

            // Optional: Also update a dedicated total field if you have one (e.g., for form submission display)
            if ($('#total_receipt_amount').length) {
                $('#total_receipt_amount').val(total.toFixed(2));
            }
            if ($('#display_total_amount').length) {
                $('#display_total_amount').text(total.toFixed(2));
            }
        }

        // ==================== Toggle Reference Options Based on Advance Checkbox ====================
        function toggleReferenceOptions() {
            const isAdvance = $('#is_advance').is(':checked');
            referenceSelect.find('option').each(function () {
                const type = $(this).data('type');
                if (isAdvance && type === 'sale_order') {
                    $(this).removeClass('d-none');
                } else if (!isAdvance && type === 'sales_invoice') {
                    $(this).removeClass('d-none');
                } else {
                    $(this).addClass('d-none').prop('selected', false);
                }
            });

            referenceSelect.trigger('change.select2');
            referenceLabel.text(isAdvance ? 'Sale Orders (approved)' : 'Invoices (approved, receiving pending)');
            referencesTableBody.html('<tr><td colspan="6" class="text-center text-muted">Select references to load details.</td></tr>');
            selectAll.prop('checked', false);
            updateSelectedDocsList();
        }

        // ==================== Load RV Number or Accounts ====================
        function loadRvNumber() {
            if (!$('#voucher_type').val()) return;

            $.post(`{{ route('receipt-voucher.generate-rv-number') }}`, {
                _token: '{{ csrf_token() }}',
                voucher_type: $('#voucher_type').val(),
                rv_date: $('#rv_date').val() || null
            }, function (resp) {
                if (resp.success) {
                    if ($('#rv_date').val()) {
                        $('#unique_no').val(resp.rv_number);
                    } else {
                        const $accountSelect = $('#account_id');
                        $accountSelect.empty().append('<option value="">Select Account</option>');
                        resp.accounts.forEach(function (acc) {
                            $accountSelect.append(`<option value="${acc.id}">${acc.name} (${acc.hierarchy_path ?? acc.unique_no ?? ''})</option>`);
                        });
                        $accountSelect.trigger('change');
                    }
                }
            });
        }

        // ==================== Recalculate Row Amounts (Amount + Tax) ====================
        function recalcRow(row) {
            const amountInput = row.find('.amount-input');
            const taxSelect = row.find('.tax-select');
            const taxAmountInput = row.find('.tax-amount');
            const netAmountInput = row.find('.net-amount');
            const hiddenAmount = row.find('.hidden-amount');

            const amount = parseFloat(amountInput.val()) || 0;
            const taxPercent = parseFloat(taxSelect.find('option:selected').data('percent')) || 0;
            const taxAmount = amount * taxPercent / 100;
            const netAmount = amount + taxAmount;

            taxAmountInput.val(taxAmount.toFixed(2));
            netAmountInput.val(netAmount.toFixed(2));
            hiddenAmount.val(amount.toFixed(2));

            updateSelectedDocsList();
        }

        // ==================== Bind Events to Dynamic Rows ====================
        function bindRowEvents() {
            referencesTableBody.find('.amount-input').off('input').on('input', function () {
                const row = $(this).closest('tr');
                recalcRow(row);
            });

            referencesTableBody.find('.tax-select').off('change').on('change', function () {
                const row = $(this).closest('tr');
                recalcRow(row);
            });
        }

        // ==================== Build Table Rows from Selected References ====================
        function buildRows(items) {
            referencesTableBody.empty();

            if (!items.length) {
                referencesTableBody.html('<tr><td colspan="6" class="text-center text-muted">No data found for selected references.</td></tr>');
                selectAll.prop('checked', false);
                updateSelectedDocsList();
                return;
            }
            console.log(items);
            $.ajax({
                url: "{{ route('receipt-voucher.get.rows') }}",
                type: "POST",
                data: {
                    _token: '{{ csrf_token() }}',
                    items: JSON.stringify(items)
                },
                success: function (response) {
                    $("#rv-data").html(response);

                    // Important: Re-bind events after injecting new HTML
                    bindRowEvents();

                    // Now update the selected list
                    updateSelectedDocsList();
                },
                error: function (xhr, status, error) {
                    console.error(error);
                    console.error(xhr.responseText);
                    referencesTableBody.html('<tr><td colspan="6" class="text-center text-danger">Error loading rows.</td></tr>');
                }
            });
        }

        // ==================== Event Listeners ====================

        // Advance checkbox toggle
        $('#is_advance').on('change', toggleReferenceOptions);
        toggleReferenceOptions(); // Initial call

        // Voucher type or date change → reload RV number
        $('#voucher_type, #rv_date').on('change', loadRvNumber);

        // Reference select change → load details
        referenceSelect.on('change', function () {
            const ids = $(this).val() || [];
            const isAdvance = $('#is_advance').is(':checked');
            const refType = isAdvance ? 'sale_order' : 'sales_invoice';

            if (!ids.length) {
                referencesTableBody.html('<tr><td colspan="6" class="text-center text-muted">Select references to load details.</td></tr>');
                selectAll.prop('checked', false);
                updateSelectedDocsList();
                return;
            }

            $.post(`{{ route('receipt-voucher.reference-details') }}`, {
                _token: '{{ csrf_token() }}',
                reference_type: refType,
                reference_ids: ids
            }, function (resp) {
                if (resp.success) {
                    buildRows(resp.items || []);
                } else {
                    referencesTableBody.html('<tr><td colspan="6" class="text-center text-danger">No data returned.</td></tr>');
                }
            }).fail(function () {
                referencesTableBody.html('<tr><td colspan="6" class="text-center text-danger">Failed to load details.</td></tr>');
            });
        });

        // Select All checkbox
        selectAll.on('change', function () {
            const checked = $(this).is(':checked');
            referencesTableBody.find('.row-select').prop('checked', checked);
            updateSelectedDocsList();
        });

        // Individual row checkboxes
        $(document).on('change', '.row-select', function () {
            // Update select all if needed
            const totalRows = referencesTableBody.find('.row-select').length;
            const checkedRows = referencesTableBody.find('.row-select:checked').length;
            selectAll.prop('checked', totalRows > 0 && totalRows === checkedRows);

            updateSelectedDocsList();
        });

        // ==================== Form Submission ====================
        $('form').off('submit').on('submit', function (e) {
            e.preventDefault();
            const totalChecked = referencesTableBody.find('.row-select:checked').length;
            if(!totalChecked) {
                Swal.fire('Validation', 'Please select at least one reference.', 'warning');
                return false;
            }
            // Remove unselected rows from DOM before submit
            referencesTableBody.find('tr').each(function () {
                const checkbox = $(this).find('.row-select');
                console.log($(this).find(".row-select").is(":checked"))
            
                if (checkbox.length && !checkbox.is(':checked')) {
                    $(this).remove();
                }
            });

            // Validation: at least one item selected
            if (!referencesTableBody.find('input[name*="items"]').length) {
                Swal.fire('Validation', 'Please select at least one reference.', 'warning');
                return false;
            }

            const form = $(this);

            $.ajax({
    url: "{{ route('receipt-voucher.store') }}",
    method: "POST",
    data: form.serialize(),

    beforeSend: function () {
        Swal.fire({
            title: 'Processing...',
            text: 'Please wait',
            allowOutsideClick: false,
            allowEscapeKey: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
    },

    success: function (resp) {
        if (resp.success) {
            Swal.fire({
                icon: 'success',
                title: resp.success,
                confirmButtonText: 'OK',
                allowOutsideClick: false,
                allowEscapeKey: false
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = resp.redirect || $('#redirectUrl').val() || '/';
                }
            });
        }
    },

    error: function (xhr) {
        let msg = 'Something went wrong.';
        if (xhr.responseJSON && xhr.responseJSON.message) {
            msg = xhr.responseJSON.message;
        } else if (xhr.responseText) {
            msg = xhr.responseText;
        }

        Swal.fire('Error', msg, 'error');
    }
});

        });
    });
</script>
@endsection

