@extends('management.layouts.master')
@section('title')
   Create Journal Voucher
@endsection
@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">Create Journal Voucher</h4>
                        <a href="{{ route('journal-voucher.index') }}" class="btn btn-sm btn-primary">Back</a>
                    </div>
                    <div class="card-body">
                        <form id="ajaxSubmit" action="{{ route('journal-voucher.store') }}">
                            @csrf

                            <input type="hidden" id="url" value="{{ route('journal-voucher.index') }}">
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="jv_date">Date</label>
                                        <input type="date" name="jv_date" id="jv_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="jv_no">JV Number</label>
                                        <input type="text" name="jv_no" id="jv_no" class="form-control" readonly>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label for="description">Description</label>
                                        <textarea name="description" id="description" class="form-control" rows="3"></textarea>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-12">
                                    <div class="custom-control custom-switch mb-2">
                                        <input type="checkbox" class="custom-control-input" id="receivingToggle" name="is_receiving">
                                        <label class="custom-control-label" for="receivingToggle">Receiving</label>
                                    </div>
                                    <div class="form-group">
                                        <label>Journal Entries</label>
                                        <div class="table-responsive">
                                            <table class="table table-bordered" id="journalEntriesTable">
                                                <thead>
                                                    <tr>
                                                        <th>Account</th>
                                                        <th class="receiving-col" style="display: none; width: 200px;">Receipt Voucher</th>
                                                        <th class="receiving-col" style="display: none; width: 200px;">Sales order</th>
                                                        <th>Description</th>
                                                        <th>Debit</th>
                                                        <th>Credit</th>
                                                        <th>Action</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="journalEntriesBody">
                                                     <tr>
                                                        <td>
                                                            <select name="details[0][acc_id]" class="form-control select2 account-select" required>
                                                                <option value="">Select Account</option>
                                                                @foreach ($accounts as $account)
                                                                    <option value="{{ $account->id }}">{{ $account->name }} ({{ $account->unique_no }})</option>
                                                                @endforeach
                                                            </select>
                                                        </td>
                                                        <td class="receiving-col" style="display: none;">
                                                            <select name="details[0][receipt_voucher_id]" class="form-control select2 receipt-voucher-select" style="width: 200px;">
                                                                <option value="">Select Receipt Voucher</option>
                                                                @foreach ($receiptVouchers as $rv)
                                                                    <option value="{{ $rv->id }}" data-remaining-amount="{{ $rv->remaining_amount }}">{{ $rv->unique_no }} (Rem: {{ number_format($rv->remaining_amount, 2) }})</option>
                                                                @endforeach
                                                            </select>
                                                        </td>
                                                        <td class="receiving-col" style="display: none;">
                                                            {{-- Empty for first row --}}
                                                        </td>
                                                        <td>
                                                            <input type="text" name="details[0][description]" class="form-control description-input" placeholder="Line description">
                                                        </td>
                                                        <td>
                                                            <input type="number" name="details[0][debit_amount]" class="form-control debit-input" step="0.01" min="0" placeholder="0.00">
                                                        </td>
                                                        <td>
                                                            <input type="number" name="details[0][credit_amount]" class="form-control credit-input" step="0.01" min="0" placeholder="0.00">
                                                        </td>
                                                        <td>
                                                            <button type="button" class="btn btn-sm btn-danger remove-row" style="display: none;">
                                                                <i class="ft-trash-2"></i>
                                                            </button>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td>
                                                            <select name="details[1][acc_id]" class="form-control select2 account-select" required>
                                                                <option value="">Select Account</option>
                                                                @foreach ($accounts as $account)
                                                                    <option value="{{ $account->id }}">{{ $account->name }} ({{ $account->unique_no }})</option>
                                                                @endforeach
                                                            </select>
                                                        </td>
                                                        <td class="receiving-col" style="display: none;">
                                                            {{-- Empty for second row --}}
                                                        </td>
                                                        <td class="receiving-col" style="display: none;">
                                                            <select name="details[1][sales_order_id]" class="form-control select2 sales-order-select" style="width: 200px;">
                                                                <option value="">Select Sales Order</option>
                                                                @foreach ($salesOrders as $so)
                                                                    <option value="{{ $so->id }}">{{ $so->reference_no }}</option>
                                                                @endforeach
                                                            </select>
                                                        </td>
                                                        <td>
                                                            <input type="text" name="details[1][description]" class="form-control description-input" placeholder="Line description">
                                                        </td>
                                                        <td>
                                                            <input type="number" name="details[1][debit_amount]" class="form-control debit-input" step="0.01" min="0" placeholder="0.00">
                                                        </td>
                                                        <td>
                                                            <input type="number" name="details[1][credit_amount]" class="form-control credit-input" step="0.01" min="0" placeholder="0.00">
                                                        </td>
                                                        <td>
                                                            <button type="button" class="btn btn-sm btn-danger remove-row" style="display: none;">
                                                                <i class="ft-trash-2"></i>
                                                            </button>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                                <tfoot>
                                                    <tr>
                                                        <td colspan="4" class="text-right"><strong>Total Debits:</strong></td>
                                                        <td><strong id="totalDebits">0.00</strong></td>
                                                        <td></td>
                                                        <td></td>
                                                    </tr>
                                                    <tr>
                                                        <td colspan="4" class="text-right"><strong>Total Credits:</strong></td>
                                                        <td></td>
                                                        <td><strong id="totalCredits">0.00</strong></td>
                                                        <td></td>
                                                    </tr>
                                                    <tr>
                                                        <td colspan="4" class="text-right"><strong>Difference (Debit - Credit):</strong></td>
                                                        <td><strong id="difference">0.00</strong></td>
                                                        <td></td>
                                                        <td></td>
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
                            </div>

                            <div class="form-group text-right">
                                <button type="submit" class="btn btn-primary">Create Journal Voucher</button>
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
            let rowCount = $('#journalEntriesBody tr').length;

            // Initialize select2 for existing selects
            $('.select2').select2();

            // Toggle receiving columns
            function toggleReceivingColumns() {
                if ($('#receivingToggle').is(':checked')) {
                    $('.receiving-col').show();
                    $('#journalEntriesTable tfoot td:first-child').attr('colspan', 4);
                    $('#addRow').closest('td').attr('colspan', 7);
                } else {
                    $('.receiving-col').hide();
                    $('#journalEntriesTable tfoot td:first-child').attr('colspan', 2);
                    $('#addRow').closest('td').attr('colspan', 5);
                }
            }

            $('#receivingToggle').change(function () {
                toggleReceivingColumns();
            });

            // Auto-fill debit amount on RV selection
            $(document).on('change', '.receipt-voucher-select', function () {
                const $option = $(this).find('option:selected');
                const remainingAmount = $option.data('remaining-amount');
                if (remainingAmount) {
                    const $row = $(this).closest('tr');
                    $row.find('.debit-input').val(remainingAmount).trigger('input');
                }
            });

            // Set initial state
            toggleReceivingColumns();

            // Generate JV number function
            function generateJvNumber() {
                const jvDate = $('#jv_date').val();
                if (jvDate) {
                    $.ajax({
                        url: '{{ route('journal-voucher.generate-jv-number') }}',
                        type: 'POST',
                        data: {
                            _token: '{{ csrf_token() }}',
                            jv_date: jvDate
                        },
                        success: function (response) {
                            if (response.success) {
                                $('#jv_no').val(response.jv_number);
                            }
                        }
                    });
                }
            }

            // Generate JV number on page load if date is set
            generateJvNumber();

            // Generate JV number on date change
            $('#jv_date').change(function () {
                generateJvNumber();
            });

            // Add new row
            $('#addRow').click(function () {
                const isReceiving = $('#receivingToggle').is(':checked');
                const displayStyle = isReceiving ? '' : 'display: none;';

                const newRow = `
                    <tr>
                        <td>
                            <select name="details[${rowCount}][acc_id]" class="form-control select2 account-select" required>
                                <option value="">Select Account</option>
                                @foreach ($accounts as $account)
                                    <option value="{{ $account->id }}">{{ $account->name }} ({{ $account->unique_no }})</option>
                                @endforeach
                            </select>
                        </td>
                        <td class="receiving-col" style="${displayStyle}">
                            <select name="details[${rowCount}][receipt_voucher_id]" class="form-control select2 receipt-voucher-select" style="width: 200px;">
                                <option value="">Select Receipt Voucher</option>
                                @foreach ($receiptVouchers as $rv)
                                    <option value="{{ $rv->id }}" data-remaining-amount="{{ $rv->remaining_amount }}">{{ $rv->unique_no }} (Rem: {{ number_format($rv->remaining_amount, 2) }})</option>
                                @endforeach
                            </select>
                        </td>
                        <td class="receiving-col" style="${displayStyle}">
                            <select name="details[${rowCount}][sales_order_id]" class="form-control select2 sales-order-select" style="width: 200px;">
                                <option value="">Select Sales Order</option>
                                @foreach ($salesOrders as $so)
                                    <option value="{{ $so->id }}">{{ $so->reference_no }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td>
                            <input type="text" name="details[${rowCount}][description]" class="form-control description-input" placeholder="Line description">
                        </td>
                        <td>
                            <input type="number" name="details[${rowCount}][debit_amount]" class="form-control debit-input" step="0.01" min="0" placeholder="0.00">
                        </td>
                        <td>
                            <input type="number" name="details[${rowCount}][credit_amount]" class="form-control credit-input" step="0.01" min="0" placeholder="0.00">
                        </td>
                        <td>
                            <button type="button" class="btn btn-sm btn-danger remove-row">
                                <i class="ft-trash-2"></i>
                            </button>
                        </td>
                    </tr>
                `;
                $('#journalEntriesBody').append(newRow);
                $('#journalEntriesBody tr:last .select2').select2();
                rowCount++;
                updateRemoveButtons();
                calculateTotals();
            });

            // Remove row
            $(document).on('click', '.remove-row', function () {
                if ($('#journalEntriesBody tr').length > 2) {
                    $(this).closest('tr').remove();
                    updateRemoveButtons();
                    calculateTotals();
                }
            });

            // Force only one of debit/credit to have value
            $(document).on('input', '.debit-input', function () {
                const $row = $(this).closest('tr');
                const debitValue = parseFloat($(this).val()) || 0;
                if (debitValue > 0) {
                    $row.find('.credit-input').val('');
                }
                calculateTotals();
            });

            $(document).on('input', '.credit-input', function () {
                const $row = $(this).closest('tr');
                const creditValue = parseFloat($(this).val()) || 0;
                if (creditValue > 0) {
                    $row.find('.debit-input').val('');
                }
                calculateTotals();
            });

            // Update remove buttons visibility
            function updateRemoveButtons() {
                const rowCount = $('#journalEntriesBody tr').length;
                if (rowCount > 2) {
                    $('.remove-row').show();
                } else {
                    $('.remove-row').hide();
                }
            }

            // Calculate totals
            function calculateTotals() {
                let totalDebits = 0;
                let totalCredits = 0;

                $('#journalEntriesBody tr').each(function () {
                    const debitAmount = parseFloat($(this).find('.debit-input').val()) || 0;
                    const creditAmount = parseFloat($(this).find('.credit-input').val()) || 0;

                    totalDebits += debitAmount;
                    totalCredits += creditAmount;
                });

                $('#totalDebits').text(totalDebits.toFixed(2));
                $('#totalCredits').text(totalCredits.toFixed(2));

                const difference = totalDebits - totalCredits;
                $('#difference').text(difference.toFixed(2));

                if (Math.abs(difference) > 0.01) {
                    $('#difference').css('color', 'red');
                } else {
                    $('#difference').css('color', 'green');
                }
            }

            // Form submission validation
            $('#ajaxSubmit').on('submit', function (e) {
                calculateTotals();

                const totalDebits = parseFloat($('#totalDebits').text()) || 0;
                const totalCredits = parseFloat($('#totalCredits').text()) || 0;

                let invalidLine = null;

                $('#journalEntriesBody tr').each(function (index) {
                    const debitAmount = parseFloat($(this).find('.debit-input').val()) || 0;
                    const creditAmount = parseFloat($(this).find('.credit-input').val()) || 0;

                    if ((debitAmount <= 0 && creditAmount <= 0) || (debitAmount > 0 && creditAmount > 0)) {
                        invalidLine = index + 1;
                        return false;
                    }
                });

                if (invalidLine !== null) {
                    e.preventDefault();
                    Swal.fire({
                        icon: 'error',
                        title: 'Validation Error',
                        text: 'Line ' + invalidLine + ' must contain either a debit or a credit amount greater than zero (but not both).',
                        confirmButtonColor: '#D95000'
                    });
                    return false;
                }

                if (Math.abs(totalDebits - totalCredits) > 0.01) {
                    e.preventDefault();
                    Swal.fire({
                        icon: 'error',
                        title: 'Validation Error',
                        text: 'Total debits must equal total credits. Current difference: ' + (totalDebits - totalCredits).toFixed(2),
                        confirmButtonColor: '#D95000'
                    });
                    return false;
                }
            });

            // Initialize
            updateRemoveButtons();
            calculateTotals();
        });
    </script>
@endsection

