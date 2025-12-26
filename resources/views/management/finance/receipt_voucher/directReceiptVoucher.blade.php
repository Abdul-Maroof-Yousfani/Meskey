@extends('management.layouts.master')
@section('title')
    Create Direct Receipt Voucher
@endsection
@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">Create Direct Receipt Voucher</h4>
                    </div>
                    <div class="card-body">
                        <form id="ajaxSubmit" action="{{ route('direct.receipt-voucher.store') }}" method="POST">
                        @csrf

                            <input type="hidden" id="url" value="{{ route('receipt-voucher.index') }}">
 
 
                            <!-- New Fields -->
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
                                        <input type="date" name="rv_date" id="rv_date" class="form-control"
                                            value="{{ date('Y-m-d') }}" required>
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
                                            <!-- Options will be populated dynamically via AJAX -->
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
                                                    <tr>
                                                        <td>
                                                            <select name="account[]"
                                                                class="form-control select2 account-select" required>
                                                                <option value="">Select Account</option>
                                                                @foreach ($accounts as $account)
                                                                    <option value="{{ $account->id }}">{{ $account->name }}
                                                                        ({{ $account->unique_no }})
                                                                    </option>
                                                                @endforeach
                                                            </select>
                                                        </td>
                                                        <td>
                                                            <input type="number" name="amount[]"
                                                                class="form-control amount-input" step="0.01"
                                                                min="0" placeholder="0.00" required>
                                                        </td>
                                                        <td>
                                                            <select name="tax_id[]"
                                                                class="form-control select2 tax-select">
                                                                <option value="">No Tax</option>
                                                                @foreach ($taxes as $tax)
                                                                    <option value="{{ $tax->id }}"
                                                                        data-rate="{{ $tax->percentage }}">{{ $tax->name }} ({{ $tax->percentage }})
                                                                    </option>
                                                                @endforeach
                                                            </select>
                                                        </td>
                                                        <td>
                                                            <input type="number" name="tax_amount[]"
                                                                class="form-control tax-amount-input" step="0.01"
                                                                min="0" placeholder="0.00" readonly>
                                                        </td>
                                                        <td>
                                                            <input type="number" name="net_amount[]"
                                                                class="form-control net-amount-input" step="0.01"
                                                                min="0" placeholder="0.00" readonly>
                                                        </td>
                                                        <td>
                                                            <input type="text" name="description[]"
                                                                class="form-control " value="">
                                                        </td>
                                                        <td>
                                                            <button type="button" class="btn btn-sm btn-danger remove-row"
                                                                style="display: none;">
                                                                <i class="ft-trash-2"></i>
                                                            </button>
                                                        </td>
                                                    </tr>
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
                                <button type="submit" class="btn btn-primary">Create Direct Receipt Voucher</button>
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
            let rowCount = $('#voucherEntriesBody tr').length;

            // Initialize select2
            $('.select2').select2();

            // Load RV Number and filtered accounts based on voucher type and date
            function loadData() {
                const voucherType = $('#voucher_type').val();
                const rvDate = $('#rv_date').val();

                if (!voucherType) {
                    $('#unique_no').val('');
                    $('#account_id').empty().append('<option value="">Select Account</option>').trigger('change');
                    return;
                }

                $.post('{{ route('receipt-voucher.generate-rv-number') }}', {
                    _token: '{{ csrf_token() }}',
                    voucher_type: voucherType,
                    rv_date: rvDate || null
                }, function(resp) {
                    if (resp.success) {
                        if (rvDate) {
                            $('#unique_no').val(resp.rv_number || '');
                        } else {
                            $('#unique_no').val('');
                        }

                        const $accountSelect = $('#account_id');
                        $accountSelect.empty().append('<option value="">Select Account</option>');

                        if (resp.accounts && Array.isArray(resp.accounts)) {
                            resp.accounts.forEach(function(acc) {
                                const label = acc.hierarchy_path || acc.unique_no || '';
                                $accountSelect.append(
                                    `<option value="${acc.id}">${acc.name} (${label})</option>`
                                );
                            });
                        }

                        $accountSelect.trigger('change');
                    } else {
                        $('#unique_no').val('');
                        $('#account_id').empty().append('<option value="">Select Account</option>').trigger('change');
                        console.error('Failed to load data:', resp);
                    }
                }).fail(function() {
                    $('#unique_no').val('');
                    $('#account_id').empty().append('<option value="">Select Account</option>').trigger('change');
                    alert('Error communicating with server.');
                });
            }

            $('#voucher_type, #rv_date').on('change', loadData);
            loadData(); // Initial load

            // ==================== Table Row Management ====================

            function calculateRow($row) {
                const amount = parseFloat($row.find('.amount-input').val()) || 0;
                const taxRate = parseFloat($row.find('.tax-select option:selected').data('rate')) || 0;

                const taxAmount = (amount * taxRate) / 100;
                const netAmount = amount + taxAmount;

                $row.find('.tax-amount-input').val(taxAmount.toFixed(2));
                $row.find('.net-amount-input').val(netAmount.toFixed(2));
            }

        

            $(document).on('click', '.remove-row', function() {
                if ($('#voucherEntriesBody tr').length > 1) {
                    $(this).closest('tr').remove();
                    updateRemoveButtons();
                    calculateTotals();
                }
            });

            $(document).on('input change', '.amount-input, .tax-select', function() {
                const $row = $(this).closest('tr');
                calculateRow($row);
                calculateTotals();
            });

            function updateRemoveButtons() {
                const rows = $('#voucherEntriesBody tr').length;
                $('.remove-row').toggle(rows > 1);
            }

            function calculateTotals() {
                let totalNet = 0;
                $('#voucherEntriesBody tr').each(function() {
                    const netAmount = parseFloat($(this).find('.net-amount-input').val()) || 0;
                    totalNet += netAmount;
                });
                $('#totalNetAmount').text(totalNet.toFixed(2));
            }

            $('#voucher_type').on('submit', function(e) {
                let hasValidEntry = false;
                $('#voucherEntriesBody tr').each(function() {
                    const amount = parseFloat($(this).find('.amount-input').val()) || 0;
                    if (amount > 0) {
                        hasValidEntry = true;
                        return false;
                    }
                });

                if (!hasValidEntry) {
                    e.preventDefault();
                    Swal.fire({
                        icon: 'error',
                        title: 'Validation Error',
                        text: 'At least one entry must have an amount greater than zero.',
                        confirmButtonColor: '#D95000'
                    });
                }
            });

            



            // Initial setup
            updateRemoveButtons();
            calculateRow($('#voucherEntriesBody tr:first'));
            calculateTotals();
        });


        


        rowCount = 0;
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
                                    <option value="{{ $tax->id }}" data-rate="{{ $tax->percentage }}">{{ $tax->name }} ({{ $tax->percentage }})</option>
                                @endforeach
                            </select>
                        </td>
                        <td>
                            <input type="number" name="tax_amount[]" class="form-control tax-amount-input" step="0.01" min="0" placeholder="0.00" readonly>
                        </td>
                        <td>
                            <input type="number" name="net_amount[]" class="form-control net-amount-input" step="0.01" min="0" placeholder="0.00" readonly>
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
                $('#voucherEntriesBody tr:last .select2').select2();
                rowCount++;
                updateRemoveButtons();
                calculateTotals();
        }
        $("#addRow").on("click", addRow);


        function submitForm() {
            e.preventDefault(); // Prevent default form submission

                console.log("Form submitted via AJAX");

                let form = $(this);
                let url = form.attr('action'); // Get the action URL from the form
                let formData = form.serialize(); // Serialize all inputs (including arrays like amount[], tax_id[], etc.)

                // Optional: Add loading state
                let submitBtn = form.find('button[type="submit"]');
                submitBtn.prop('disabled', true).text('Submitting...');

                $.ajax({
                    url: url,
                    type: 'POST',
                    data: formData,
                    dataType: 'json',
                    success: function(response) {
                        console.log('Success:', response);

                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Success!',
                                text: response.message || 'Direct Receipt Voucher created successfully.',
                                confirmButtonColor: '#28a745'
                            }).then(() => {
                                // Optional: Redirect or refresh list
                                if (response.redirect) {
                                    window.location.href = response.redirect;
                                } else {
                                    // Or reload the list page
                                    window.location.href = $('#listRefresh').attr('action');
                                }
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: response.message || 'Something went wrong.',
                                confirmButtonColor: '#dc3545'
                            });
                        }
                    },
                    error: function(xhr) {
                        console.log('Error:', xhr);

                        let errors = xhr.responseJSON?.errors || {};
                        let errorMsg = 'Please fix the errors below.';

                        if (xhr.responseJSON?.message) {
                            errorMsg = xhr.responseJSON.message;
                        }

                        Swal.fire({
                            icon: 'error',
                            title: 'Validation Error',
                            text: errorMsg,
                            confirmButtonColor: '#dc3545'
                        });

                        // Optional: Show field-specific errors (advanced)
                    },
                    complete: function() {
                        // Re-enable submit button
                        submitBtn.prop('disabled', false).text('Create Direct Receipt Voucher');
                    }
                });
        }

    </script>
@endsection