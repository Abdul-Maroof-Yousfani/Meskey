<!-- View file: resources/views/management/finance/receipt_voucher/edit_directReceiptVoucher.blade.php -->

@extends('management.layouts.master')
@section('title')
    Edit Direct Receipt Voucher
@endsection
@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">Edit Direct Receipt Voucher</h4>
                    </div>
                    <div class="card-body">
                        <form id="ajaxSubmit" action="{{ route('direct.receipt-voucher.update', $receiptVoucher->id) }}" method="POST">
                            @csrf
                            @method('PUT')

                            <input type="hidden" id="url" value="{{ route('receipt-voucher.index') }}">

                            <!-- New Fields -->
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
                                        <input type="date" name="rv_date" id="rv_date" class="form-control"
                                            value="{{ old('rv_date', $receiptVoucher->rv_date?->format('Y-m-d')) }}" required>
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
                                            <!-- Options will be populated dynamically via AJAX, but pre-select -->
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
                                        <input type="date" name="bill_date" id="bill_date" class="form-control" value="{{ $receiptVoucher->bill_date?->format('Y-m-d') }}">
                                    </div>
                                </div>
                            </div>

                            <!-- Voucher Entries Table -->
                            <div class="row mt-4">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label>Voucher Entries</label>
                                        <div class="table-responsive">
                                            <table class="table table-bordered" id="voucherEntriesTable">
                                                <thead>
                                                    <tr>
                                                        <th>Account</th>
                                                        <th>Amount</th>
                                                        <th>Tax</th>
                                                        <th>Tax Amount</th>
                                                        <th>Net Amount</th>
                                                        <th>Description</th>
                                                        <th>Action</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="voucherEntriesBody">
                                                    @foreach ($receiptVoucher->items as $item)
                                                        <tr>
                                                            <td>
                                                                <select name="account[]"
                                                                    class="form-control select2 account-select" required>
                                                                    <option value="">Select Account</option>
                                                                    @foreach ($accounts as $account)
                                                                        <option value="{{ $account->id }}" {{ $item->account_id == $account->id ? 'selected' : '' }}>
                                                                            {{ $account->name }} ({{ $account->unique_no }})
                                                                        </option>
                                                                    @endforeach
                                                                </select>
                                                            </td>
                                                            <td>
                                                                <input type="number" name="amount[]"
                                                                    class="form-control amount-input" step="0.01"
                                                                    min="0" placeholder="0.00" value="{{ $item->amount }}" required>
                                                            </td>
                                                            <td>
                                                                <select name="tax_id[]"
                                                                    class="form-control select2 tax-select">
                                                                    <option value="">No Tax</option>
                                                                    @foreach ($taxes as $tax)
                                                                        <option value="{{ $tax->id }}"
                                                                            data-rate="{{ $tax->percentage }}" {{ $item->tax_id == $tax->id ? 'selected' : '' }}>
                                                                            {{ $tax->name }} ({{ $tax->percentage }})
                                                                        </option>
                                                                    @endforeach
                                                                </select>
                                                            </td>
                                                            <td>
                                                                <input type="number" name="tax_amount[]"
                                                                    class="form-control tax-amount-input" step="0.01"
                                                                    min="0" placeholder="0.00" value="{{ $item->tax_amount }}" readonly>
                                                            </td>
                                                            <td>
                                                                <input type="number" name="net_amount[]"
                                                                    class="form-control net-amount-input" step="0.01"
                                                                    min="0" placeholder="0.00" value="{{ $item->net_amount }}" readonly>
                                                            </td>
                                                            <td>
                                                                <input type="text" name="description[]"
                                                                    class="form-control" value="{{ $item->line_desc }}">
                                                            </td>
                                                            <td>
                                                                <button type="button" class="btn btn-sm btn-danger remove-row"
                                                                    style="display: none;">
                                                                    <i class="ft-trash-2"></i>
                                                                </button>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                                <tfoot>
                                                    <tr>
                                                        <td colspan="4" class="text-right"><strong>Total Net
                                                                Amount:</strong>
                                                        </td>
                                                        <td><strong id="totalNetAmount">0.00</strong></td>
                                                        <td></td>
                                                    </tr>
                                                    <tr>
                                                        <td colspan="6">
                                                            <button type="button" class="btn btn-sm btn-primary"
                                                                id="addRow">
                                                                <i class="ft-plus"></i> Add Row
                                                            </button>
                                                        </td>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group text-right mt-4">
                                <button type="submit" class="btn btn-primary">Update Direct Receipt Voucher</button>
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
    $('.select2').select2();

    // Load RV Number and filtered accounts
    function loadData() {
        const voucherType = $('#voucher_type').val();
        const rvDate = $('#rv_date').val();

        if (!voucherType) {
            $('#unique_no').val('{{ $receiptVoucher->unique_no }}');
            $('#account_id').empty().append('<option value="">Select Account</option>').trigger('change');
            return;
        }

        $.post('{{ route('receipt-voucher.generate-rv-number') }}', {
            _token: '{{ csrf_token() }}',
            voucher_type: voucherType,
            rv_date: rvDate || null
        }, function(resp) {
            if (resp.success) {
                $('#unique_no').val(resp.rv_number || '{{ $receiptVoucher->unique_no }}');

                const $accountSelect = $('#account_id');
                $accountSelect.empty().append('<option value="">Select Account</option>');

                if (resp.accounts && Array.isArray(resp.accounts)) {
                    resp.accounts.forEach(function(acc) {
                        const label = acc.hierarchy_path || acc.unique_no || '';
                        const selected = acc.id == '{{ $receiptVoucher->account_id }}' ? 'selected' : '';
                        $accountSelect.append(
                            `<option value="${acc.id}" ${selected}>${acc.name} (${label})</option>`
                        );
                    });
                }
                $accountSelect.trigger('change');
            }
        }).fail(function() {
            $('#unique_no').val('{{ $receiptVoucher->unique_no }}');
            $('#account_id').empty().append('<option value="">Select Account</option>').trigger('change');
        });
    }

    $('#voucher_type, #rv_date').on('change', loadData);
    loadData(); // Initial load

    // ==================== Row Calculations ====================
    function calculateRow($row) {
        const amount = parseFloat($row.find('.amount-input').val()) || 0;
        const taxRate = parseFloat($row.find('.tax-select option:selected').data('rate')) || 0;

        const taxAmount = (amount * taxRate) / 100;
        const netAmount = amount + taxAmount;

        $row.find('.tax-amount-input').val(taxAmount.toFixed(2));
        $row.find('.net-amount-input').val(netAmount.toFixed(2));
    }

    function calculateTotals() {
        let totalNet = 0;
        $('#voucherEntriesBody tr').each(function() {
            const netAmount = parseFloat($(this).find('.net-amount-input').val()) || 0;
            totalNet += netAmount;
        });
        $('#totalNetAmount').text(totalNet.toFixed(2));
    }

    // Recalculate on input/change
    $(document).on('input change', '.amount-input, .tax-select', function() {
        const $row = $(this).closest('tr');
        calculateRow($row);
        calculateTotals();
    });

    // Remove row
    $(document).on('click', '.remove-row', function() {
        if ($('#voucherEntriesBody tr').length > 1) {
            $(this).closest('tr').remove();
            updateRemoveButtons();
            calculateTotals();
        }
    });

    function updateRemoveButtons() {
        const rows = $('#voucherEntriesBody tr').length;
        $('.remove-row').toggle(rows > 1);
    }

    // ==================== Add Single Row ====================
    function addRow() {
        const newRow = `
            <tr>
                <td>
                    <select name="account[]" class="form-control select2 account-select" required>
                        <option value="">Select Account</option>
                        @foreach ($accounts as $account)
                            <option value="{{ $account->id }}">{{ $account->name }} ({{ $account->unique_no }})</option>
                        @endforeach
                    </select>
                </td>
                <td>
                    <input type="number" name="amount[]" class="form-control amount-input" step="0.01" min="0" placeholder="0.00" required>
                </td>
                <td>
                    <select name="tax_id[]" class="form-control select2 tax-select">
                        <option value="">No Tax</option>
                        @foreach ($taxes as $tax)
                            <option value="{{ $tax->id }}" data-rate="{{ $tax->percentage }}">
                                {{ $tax->name }} ({{ $tax->percentage }}%)
                            </option>
                        @endforeach
                    </select>
                </td>
                <td>
                    <input type="number" name="tax_amount[]" class="form-control tax-amount-input" step="0.01" readonly>
                </td>
                <td>
                    <input type="number" name="net_amount[]" class="form-control net-amount-input" step="0.01" readonly>
                </td>
                <td>
                    <input type="text" name="description[]" class="form-control">
                </td>
                <td>
                    <button type="button" class="btn btn-sm btn-danger remove-row">
                        <i class="ft-trash-2"></i>
                    </button>
                </td>
            </tr>
        `;
        $('#voucherEntriesBody').append(newRow);
        
        // Re-initialize select2 on the new row only
        $('#voucherEntriesBody tr:last .select2').select2();
        
        updateRemoveButtons();
        calculateTotals();
    }

    // Bind addRow only ONCE
    $('#addRow').off('click').on('click', addRow);

    // Initial calculations for existing rows
    $('#voucherEntriesBody tr').each(function() {
        calculateRow($(this));
    });
    updateRemoveButtons();
    calculateTotals();
});
</script>
@endsection