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
                                        <input type="date" name="jv_date" id="jv_date" class="form-control" required>
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
                                    <div class="form-group">
                                        <label>Journal Entries</label>
                                        <div class="table-responsive">
                                            <table class="table table-bordered" id="journalEntriesTable">
                                                <thead>
                                                    <tr>
                                                        <th>Account</th>
                                                        <th>Debit/Credit</th>
                                                        <th>Amount</th>
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
                                                        <td>
                                                            <select name="details[0][debit_credit]" class="form-control debit-credit-select" required>
                                                                <option value="">Select</option>
                                                                <option value="debit">Debit</option>
                                                                <option value="credit">Credit</option>
                                                            </select>
                                                        </td>
                                                        <td>
                                                            <input type="number" name="details[0][amount]" class="form-control amount-input" step="0.01" min="0.01" required>
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
                                                        <td>
                                                            <select name="details[1][debit_credit]" class="form-control debit-credit-select" required>
                                                                <option value="">Select</option>
                                                                <option value="debit">Debit</option>
                                                                <option value="credit">Credit</option>
                                                            </select>
                                                        </td>
                                                        <td>
                                                            <input type="number" name="details[1][amount]" class="form-control amount-input" step="0.01" min="0.01" required>
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
                                                        <td colspan="2" class="text-right"><strong>Total Debits:</strong></td>
                                                        <td><strong id="totalDebits">0.00</strong></td>
                                                        <td></td>
                                                    </tr>
                                                    <tr>
                                                        <td colspan="2" class="text-right"><strong>Total Credits:</strong></td>
                                                        <td><strong id="totalCredits">0.00</strong></td>
                                                        <td></td>
                                                    </tr>
                                                    <tr>
                                                        <td colspan="2" class="text-right"><strong>Difference:</strong></td>
                                                        <td><strong id="difference">0.00</strong></td>
                                                        <td></td>
                                                    </tr>
                                                    <tr>
                                                        <td colspan="4">
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
            let rowCount = 2;

            // Initialize select2 for existing selects
            $('.select2').select2();

            // Generate JV number on date change
            $('#jv_date').change(function () {
                if ($(this).val()) {
                    $.ajax({
                        url: '{{ route('journal-voucher.generate-jv-number') }}',
                        type: 'POST',
                        data: {
                            _token: '{{ csrf_token() }}',
                            jv_date: $(this).val()
                        },
                        success: function (response) {
                            if (response.success) {
                                $('#jv_no').val(response.jv_number);
                            }
                        }
                    });
                }
            });

            // Add new row
            $('#addRow').click(function () {
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
                        <td>
                            <select name="details[${rowCount}][debit_credit]" class="form-control debit-credit-select" required>
                                <option value="">Select</option>
                                <option value="debit">Debit</option>
                                <option value="credit">Credit</option>
                            </select>
                        </td>
                        <td>
                            <input type="number" name="details[${rowCount}][amount]" class="form-control amount-input" step="0.01" min="0.01" required>
                        </td>
                        <td>
                            <button type="button" class="btn btn-sm btn-danger remove-row">
                                <i class="ft-trash-2"></i>
                            </button>
                        </td>
                    </tr>
                `;
                $('#journalEntriesBody').append(newRow);
                $('.select2').select2();
                rowCount++;
                updateRemoveButtons();
            });

            // Remove row
            $(document).on('click', '.remove-row', function () {
                if ($('#journalEntriesBody tr').length > 2) {
                    $(this).closest('tr').remove();
                    updateRemoveButtons();
                    calculateTotals();
                }
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
                    const debitCredit = $(this).find('.debit-credit-select').val();
                    const amount = parseFloat($(this).find('.amount-input').val()) || 0;

                    if (debitCredit === 'debit') {
                        totalDebits += amount;
                    } else if (debitCredit === 'credit') {
                        totalCredits += amount;
                    }
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

            // Calculate on amount or debit/credit change
            $(document).on('change input', '.amount-input, .debit-credit-select', function () {
                calculateTotals();
            });

            // Form submission validation
            $('#ajaxSubmit').on('submit', function (e) {
                const totalDebits = parseFloat($('#totalDebits').text()) || 0;
                const totalCredits = parseFloat($('#totalCredits').text()) || 0;
                
                if (Math.abs(totalDebits - totalCredits) > 0.01) {
                    e.preventDefault();
                    alert('Total debits must equal total credits. Current difference: ' + (totalDebits - totalCredits).toFixed(2));
                    return false;
                }
            });

            // Initialize
            updateRemoveButtons();
            calculateTotals();
        });
    </script>
@endsection

