@extends('management.layouts.master')
@section('title')
    Create Direct Payment Voucher
@endsection
@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Create Direct Payment Voucher</h4>
                </div>
                <div class="card-body">
                    <form id="ajaxSubmit" action="{{ route('direct.payment-voucher.store') }}" method="POST">
                        @csrf

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="voucher_type">Voucher Type <span class="text-danger">*</span></label>
                                    <select name="voucher_type" id="voucher_type" class="form-control select2" required>
                                        <option value="">Select Type</option>
                                        <option value="bank_payment_voucher">Bank Payment Voucher</option>
                                        <option value="cash_payment_voucher">Cash Payment Voucher</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="pv_date">Date <span class="text-danger">*</span></label>
                                    <input type="date" name="pv_date" id="pv_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="unique_no">PV Number</label>
                                    <input type="text" name="unique_no" id="unique_no" class="form-control" readonly>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="account_id">Payment From (Cash/Bank) <span class="text-danger">*</span></label>
                                    <select name="account_id" id="account_id" class="form-control select2" required>
                                        <option value="">Select Account</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="ref_bill_no">Ref Bill No</label>
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

                        <!-- Bank fields -->
                        <div id="bankFields" style="display: none;">
                            <div class="row mt-3">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="cheque_no">Cheque No</label>
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
                        </div>

                        <!-- Voucher Entries Table -->
                        <div class="row mt-4">
                            <div class="col-md-12">
                                <label>Payment Entries</label>
                                <div class="table-responsive">
                                    <table class="table table-bordered" id="voucherEntriesTable">
                                        <thead>
                                            <tr>
                                                <th>Account (To)</th>
                                                <th>Amount</th>
                                                <th>Tax</th>
                                                <th>Tax Amount</th>
                                                <th>Net Amount</th>
                                                <th>Description</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody id="voucherEntriesBody">
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
                                                <td><input type="number" name="tax_amount[]" class="form-control tax-amount-input" step="0.01" readonly></td>
                                                <td><input type="number" name="net_amount[]" class="form-control net-amount-input" step="0.01" readonly></td>
                                                <td><input type="text" name="description[]" class="form-control"></td>
                                                <td><button type="button" class="btn btn-sm btn-danger remove-row" style="display:none;"><i class="ft-trash-2"></i></button></td>
                                            </tr>
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <td colspan="4" class="text-right"><strong>Total Net Amount:</strong></td>
                                                <td><strong id="totalNetAmount">0.00</strong></td>
                                                <td colspan="2"></td>
                                            </tr>
                                            <tr>
                                                <td colspan="7">
                                                    <button type="button" class="btn btn-sm btn-primary" id="addRow">
                                                        <i class="ft-plus"></i> Add Row
                                                    </button>
                                                </td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div class="form-group text-right mt-4">
                            <button type="submit" class="btn btn-primary">Create Direct Payment Voucher</button>
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

    // Toggle bank fields
    $('#voucher_type').on('change', function() {
        if ($(this).val() === 'bank_payment_voucher') {
            $('#bankFields').show();
            $('#cheque_no, #cheque_date').prop('required', true);
        } else {
            $('#bankFields').hide();
            $('#cheque_no, #cheque_date').prop('required', false);
        }
        loadPvNumberAndAccounts();
    });

    $('#pv_date').on('change', loadPvNumberAndAccounts);

    function loadPvNumberAndAccounts() {
        const voucherType = $('#voucher_type').val();
        const pvDate = $('#pv_date').val();

        if (!voucherType || !pvDate) {
            $('#unique_no').val('');
            $('#account_id').empty().append('<option value="">Select Account</option>').trigger('change');
            return;
        }

        $.post('{{ route("payment-voucher.generate-pv-number") }}', {
            _token: '{{ csrf_token() }}',
            voucher_type: voucherType,
            pv_date: pvDate
        }, function(resp) {
            if (resp.success) {
                $('#unique_no').val(resp.pv_number || '');

                const $accountSelect = $('#account_id');
                $accountSelect.empty().append('<option value="">Select Account</option>');

                if (resp.accounts && Array.isArray(resp.accounts)) {
                    resp.accounts.forEach(function(acc) {
                        const label = acc.hierarchy_path || acc.unique_no || acc.id;
                        $accountSelect.append(`<option value="${acc.id}">${acc.name} (${label})</option>`);
                    });
                }
                $accountSelect.trigger('change');
            }
        }).fail(function() {
            alert('Error loading data.');
            $('#unique_no').val('');
            $('#account_id').empty().append('<option value="">Select Account</option>').trigger('change');
        });
    }

    loadPvNumberAndAccounts(); // Initial load

    // ==================== Calculations ====================
    function calculateRow($row) {
        const amount = parseFloat($row.find('.amount-input').val()) || 0;
        const taxRate = parseFloat($row.find('.tax-select option:selected').data('rate')) || 0;

        const taxAmount = (amount * taxRate) / 100;
        const netAmount = amount + taxAmount;

        $row.find('.tax-amount-input').val(taxAmount.toFixed(2));
        $row.find('.net-amount-input').val(netAmount.toFixed(2));
    }

    function calculateTotals() {
        let total = 0;
        $('#voucherEntriesBody tr').each(function() {
            total += parseFloat($(this).find('.net-amount-input').val()) || 0;
        });
        $('#totalNetAmount').text(total.toFixed(2));
    }

    $(document).on('input change', '.amount-input, .tax-select', function() {
        calculateRow($(this).closest('tr'));
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

    // ==================== Add Row - Only ONE per click ====================
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
                <td><input type="number" name="tax_amount[]" class="form-control tax-amount-input" step="0.01" readonly></td>
                <td><input type="number" name="net_amount[]" class="form-control net-amount-input" step="0.01" readonly></td>
                <td><input type="text" name="description[]" class="form-control"></td>
                <td><button type="button" class="btn btn-sm btn-danger remove-row"><i class="ft-trash-2"></i></button></td>
            </tr>
        `;

        $('#voucherEntriesBody').append(newRow);

        // Initialize select2 only on the new row
        $('#voucherEntriesBody tr:last .select2').select2();

        updateRemoveButtons();
        calculateTotals();
    }

    // Bind click event ONLY ONCE using .off() + .on()
    $('#addRow').off('click').on('click', addRow);

    // Initial setup
    updateRemoveButtons();
    calculateRow($('#voucherEntriesBody tr:first'));
    calculateTotals();
});
</script>
@endsection