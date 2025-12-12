@extends('management.layouts.master')
@section('title')
    Edit Receipt Voucher
@endsection
@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">Edit Receipt Voucher</h4>
                        <a href="{{ route('receipt-voucher.index') }}" class="btn btn-sm btn-primary">Back</a>
                    </div>
                    <div class="card-body">
                        <form id="ajaxSubmit" action="{{ route('receipt-voucher.update', $receiptVoucher->id) }}" method="POST">
                            @csrf
                            @method('PUT')
                            <input type="hidden" id="redirectUrl" value="{{ route('receipt-voucher.index') }}">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="voucher_type">Voucher Type</label>
                                        <select name="voucher_type" id="voucher_type" class="form-control select2" required>
                                            <option value="">Select Type</option>
                                            <option value="bank_payment_voucher" {{ $receiptVoucher->voucher_type == 'bank_payment_voucher' ? 'selected' : '' }}>Bank Receipt Voucher</option>
                                            <option value="cash_payment_voucher" {{ $receiptVoucher->voucher_type == 'cash_payment_voucher' ? 'selected' : '' }}>Cash Receipt Voucher</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="rv_date">Date</label>
                                        <input type="date" name="rv_date" id="rv_date" class="form-control" value="{{ optional($receiptVoucher->rv_date)->format('Y-m-d') }}" required>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="unique_no">RV Number</label>
                                        <input type="text" name="unique_no" id="unique_no" class="form-control" value="{{ $receiptVoucher->unique_no }}" readonly>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="account_id">Account</label>
                                        <select name="account_id" id="account_id" class="form-control select2" required>
                                            <option value="">Select Account</option>
                                            @foreach($accounts ?? [] as $acc)
                                                <option value="{{ $acc->id }}" {{ $receiptVoucher->account_id == $acc->id ? 'selected' : '' }}>{{ $acc->name }} ({{ $acc->hierarchy_path ?? $acc->unique_no }})</option>
                                            @endforeach
                                            @if(!isset($accounts))
                                                <option value="{{ $receiptVoucher->account_id }}" selected>{{ $receiptVoucher->account->name ?? 'Account' }}</option>
                                            @endif
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="ref_bill_no">Receipt Ref No</label>
                                        <input type="text" name="ref_bill_no" id="ref_bill_no" class="form-control" value="{{ $receiptVoucher->ref_bill_no }}">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="bill_date">Receipt Date</label>
                                        <input type="date" name="bill_date" id="bill_date" class="form-control" value="{{ optional($receiptVoucher->bill_date)->format('Y-m-d') }}">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="customer_id">Customer Account</label>
                                        <select name="customer_id" id="customer_id" class="form-control select2" required>
                                            <option value="">Select Customer</option>
                                            @foreach ($customers as $customer)
                                                <option value="{{ $customer->id }}" @selected($receiptVoucher->customer_id == $customer->id)>{{ $customer->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label id="reference_label">Invoices (approved, receiving pending)</label>
                                        <select id="reference_ids" class="form-control select2" multiple style="width: 100%;">
                                            @foreach ($salesInvoices as $inv)
                                                <option value="{{ $inv->id }}" data-type="sales_invoice" @selected(in_array((string)$inv->id, $selectedReferences))>
                                                    {{ $inv->si_no ?? 'INV-'.$inv->id }} - {{ optional($inv->customer)->name }} {{ $inv->reference_number ? ' | Ref: '.$inv->reference_number : '' }}
                                                </option>
                                            @endforeach
                                            @foreach ($saleOrders as $so)
                                                <option value="{{ $so->id }}" data-type="sale_order" class="{{ $isAdvance ? '' : 'd-none' }}" @selected(in_array((string)$so->id, $selectedReferences))>
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
                                        <input class="form-check-input" type="checkbox" id="is_advance" {{ $isAdvance ? 'checked' : '' }}>
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
                                                <tbody>
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
                                        <textarea name="remarks" id="remarks" class="form-control" rows="3">{{ $receiptVoucher->remarks }}</textarea>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group text-right">
                                <button type="submit" class="btn btn-primary">Update Receipt Voucher</button>
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
        const referenceSelect = $('#reference_ids');
        const referenceLabel = $('#reference_label');
        const referencesTableBody = $('#referencesTable tbody');
        const selectAll = $('#select_all');
        const listContainer = $('.selected-docs-list');
        const emptyMessage = $('.selected-docs-container p');
        const taxes = @json($taxes ?? []);
        const initialItems = @json($initialItems ?? []);
        const selectedReferences = @json($selectedReferences ?? []);

        function toggleReferenceOptions() {
            const isAdvance = $('#is_advance').is(':checked');
            referenceSelect.find('option').each(function() {
                const type = $(this).data('type');
                if (isAdvance && type === 'sale_order') {
                    $(this).removeClass('d-none');
                } else if (!isAdvance && type === 'sales_invoice') {
                    $(this).removeClass('d-none');
                } else {
                    $(this).addClass('d-none').prop('selected', false);
                }
            });
            referenceLabel.text(isAdvance ? 'Sale Orders (approved)' : 'Invoices (approved, receiving pending)');
            referenceSelect.trigger('change.select2');
        }

        $('#is_advance').on('change', toggleReferenceOptions);
        toggleReferenceOptions();

        function loadRvNumber() {
            if (!$('#voucher_type').val()) return;
            $.post(`{{ route('receipt-voucher.generate-rv-number') }}`, {
                _token: '{{ csrf_token() }}',
                voucher_type: $('#voucher_type').val(),
                rv_date: $('#rv_date').val() || null
            }, function (resp) {
                if (resp.success) {
                    const $accountSelect = $('#account_id');
                    $accountSelect.empty().append('<option value="">Select Account</option>');
                    resp.accounts.forEach(function (acc) {
                        $accountSelect.append(`<option value="${acc.id}" ${acc.id == '{{ $receiptVoucher->account_id }}' ? 'selected' : ''}>${acc.name} (${acc.hierarchy_path ?? acc.unique_no ?? ''})</option>`);
                    });
                    $accountSelect.trigger('change');
                }
            });
        }

        $('#voucher_type, #rv_date').on('change', loadRvNumber);

        function buildRows(items) {
            referencesTableBody.empty();
            if (!items.length) {
                referencesTableBody.html('<tr><td colspan="6" class="text-center text-muted">Select references to load details.</td></tr>');
                selectAll.prop('checked', false);
                updateSelectedDocsList();
                return;
            }

            items.forEach(function (item, idx) {
                referencesTableBody.append(`
                    <tr>
                        <td class="text-center">
                            <input type="checkbox" class="row-select" data-row="${idx}" checked>
                            <input type="hidden" class="hidden-amount" name="items[${idx}][amount]" value="${item.quantity ?? item.amount}">
                            <input type="hidden" name="items[${idx}][reference_id]" value="${item.reference_id}">
                            <input type="hidden" name="items[${idx}][reference_type]" value="${item.reference_type}">
                        </td>
                        <td>${item.reference_type === 'sale_order' ? 'Sale Order' : 'Sales Invoice'}</td>
                        <td>${item.number}</td>
                        <td>${item.date}</td>
                        <td>${item.customer_name || item.customer || ''}</td>
                        <td><input type="number" step="0.01" class="form-control amount-input" name="items[${idx}][amount_display]" value="${parseFloat(item.quantity ?? item.amount).toFixed(2)}"></td>
                        <td>
                            <select class="form-control tax-select" name="items[${idx}][tax_id]">
                                <option value="">No Tax</option>
                                ${taxes.map(t => `<option value="${t.id}" data-percent="${t.percentage}" ${item.tax_id == t.id ? 'selected' : ''}>${t.name} (${t.percentage}%)</option>`).join('')}
                            </select>
                        </td>
                        <td>
                            <input type="number" step="0.01" readonly class="form-control tax-amount" name="items[${idx}][tax_amount]" value="${parseFloat(item.tax_amount ?? 0).toFixed(2)}">
                        </td>
                        <td>
                            <input type="number" step="0.01" readonly class="form-control net-amount" name="items[${idx}][net_amount]" value="${parseFloat(item.net_amount ?? item.quantity ?? item.amount).toFixed(2)}">
                        </td>
                        <td>
                            <input type="text" class="form-control line-desc" name="items[${idx}][line_desc]" value="${item.line_desc ?? ''}" placeholder="Line description">
                        </td>
                    </tr>
                `);
            });
            selectAll.prop('checked', true);
            bindRowEvents();
            updateSelectedDocsList();
        }

        function bindRowEvents() {
            referencesTableBody.find('.amount-input').off('input').on('input', function() {
                const row = $(this).closest('tr');
                recalcRow(row);
            });
            referencesTableBody.find('.tax-select').off('change').on('change', function() {
                const row = $(this).closest('tr');
                recalcRow(row);
            });
            referencesTableBody.find('.row-select').off('change').on('change', function() {
                updateSelectedDocsList();
            });
            selectAll.off('change').on('change', function() {
                const checked = $(this).is(':checked');
                referencesTableBody.find('.row-select').prop('checked', checked);
                updateSelectedDocsList();
            });
        }

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

        function updateSelectedDocsList() {
            const selected = [];
            let total = 0;

            referencesTableBody.find('tr').each(function() {
                const row = $(this);
                const checkbox = row.find('.row-select');
                if (checkbox.length && checkbox.is(':checked')) {
                    const type = row.find('td').eq(1).text();
                    const number = row.find('td').eq(2).text();
                    const date = row.find('td').eq(3).text();
                    const customer = row.find('td').eq(4).text();
                    const netAmount = parseFloat(row.find('.net-amount').val()) || 0;
                    selected.push({ type, number, date, customer, amount: netAmount, idx: checkbox.data('row') });
                    total += netAmount;
                }
            });

            listContainer.empty();
            if (!selected.length) {
                emptyMessage.show();
                listContainer.hide();
                return;
            }

            emptyMessage.hide();
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

            listContainer.append(`
                <li class="list-group-item list-group-item-primary d-flex justify-content-between align-items-center">
                    <strong>Total Amount</strong>
                    <strong>${total.toFixed(2)}</strong>
                </li>
            `);

            listContainer.show();
        }

        // initial setup
        buildRows(initialItems);
        referenceSelect.val(selectedReferences).trigger('change.select2');

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
                }
            });
        });

        $('#ajaxSubmit').on('submit', function (e) {
            e.preventDefault();
            referencesTableBody.find('tr').each(function() {
                const checkbox = $(this).find('.row-select');
                if (checkbox.length && !checkbox.is(':checked')) {
                    $(this).remove();
                }
            });

            if (!referencesTableBody.find('input[name*="items"]').length) {
                Swal.fire('Validation', 'Please select at least one reference.', 'warning');
                return;
            }

            const form = $(this);
            $.ajax({
                url: form.attr('action'),
                method: 'POST',
                data: form.serialize(),
                success: function (resp) {
                    if (resp.success) {
                        Swal.fire({
                            icon: 'success',
                            title: resp.success,
                            timer: 1200,
                            showConfirmButton: false
                        }).then(() => {
                            window.location.href = resp.redirect || $('#redirectUrl').val();
                        });
                    }
                },
                error: function (xhr) {
                    let msg = 'Something went wrong.';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        msg = xhr.responseJSON.message;
                    }
                    Swal.fire('Error', msg, 'error');
                }
            });
        });
    });
</script>
@endsection


