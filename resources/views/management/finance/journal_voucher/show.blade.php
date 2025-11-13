@section('title')
    View Journal Voucher
@endsection
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4 class="mb-0">Journal Voucher #{{ $journalVoucher->jv_no }}</h4>
                <a href="{{ route('journal-voucher.index') }}" class="btn btn-sm btn-primary">Back</a>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label><strong>JV Number:</strong></label>
                            <p>{{ $journalVoucher->jv_no }}</p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label><strong>Date:</strong></label>
                            <p>{{ $journalVoucher->jv_date->format('d-m-Y') }}</p>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label><strong>Description:</strong></label>
                            <p>{{ $journalVoucher->description ?? 'N/A' }}</p>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label><strong>Status:</strong></label>
                            <p>
                                <span
                                    class="badge badge-{{ $journalVoucher->jv_status == 'approved' ? 'success' : ($journalVoucher->jv_status == 'pending' ? 'warning' : 'danger') }}">
                                    {{ ucfirst($journalVoucher->jv_status) }}
                                </span>
                            </p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label><strong>Created By:</strong></label>
                            <p>{{ $journalVoucher->username ?? 'N/A' }}</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label><strong>Last Action By:</strong></label>
                            <p>{{ optional($journalVoucher->approveUser)->name ?? 'N/A' }}</p>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label><strong>Journal Entries:</strong></label>
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Account</th>
                                            <th>Description</th>
                                            <th>Debit</th>
                                            <th>Credit</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php
                                            $totalDebits = 0;
                                            $totalCredits = 0;
                                        @endphp
                                        @foreach ($journalVoucher->journalVoucherDetails as $detail)
                                            @php
                                                $totalDebits += $detail->debit_amount;
                                                $totalCredits += $detail->credit_amount;
                                            @endphp
                                            <tr>
                                                <td>{{ $detail->account->name ?? 'N/A' }}
                                                    ({{ $detail->account->unique_no ?? 'N/A' }})</td>
                                                <td>{{ $detail->description ?? '—' }}</td>
                                                <td>{{ $detail->debit_amount > 0 ? number_format($detail->debit_amount, 2) : '—' }}</td>
                                                <td>{{ $detail->credit_amount > 0 ? number_format($detail->credit_amount, 2) : '—' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <td colspan="2" class="text-right"><strong>Total Debits:</strong></td>
                                            <td><strong>{{ number_format($totalDebits, 2) }}</strong></td>
                                            <td></td>
                                        </tr>
                                        <tr>
                                            <td colspan="2" class="text-right"><strong>Total Credits:</strong></td>
                                            <td></td>
                                            <td><strong>{{ number_format($totalCredits, 2) }}</strong></td>
                                        </tr>
                                        <tr>
                                            <td colspan="2" class="text-right"><strong>Difference (Debit - Credit):</strong></td>
                                            <td><strong style="color: {{ abs($totalDebits - $totalCredits) > 0.01 ? 'red' : 'green' }}">{{ number_format($totalDebits - $totalCredits, 2) }}</strong></td>
                                            <td></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                @if ($journalVoucher->jv_status == 'pending')
                    <div class="row mt-3">
                        <div class="col-md-12">
                            <div class="form-group text-right">
                                <button type="button" class="btn btn-success" id="approveBtn">
                                    <i class="ft-check"></i> Approve
                                </button>
                                <button type="button" class="btn btn-danger" id="rejectBtn">
                                    <i class="ft-x"></i> Reject
                                </button>
                            </div>
                        </div>
                    </div>
                @elseif ($journalVoucher->jv_status == 'approved')
                    <div class="row mt-3">
                        <div class="col-md-12">
                            <div class="alert alert-success">
                                <strong>Approved</strong> by {{ optional($journalVoucher->approveUser)->name ?? 'N/A' }}
                            </div>
                        </div>
                    </div>
                @elseif ($journalVoucher->jv_status == 'rejected')
                    <div class="row mt-3">
                        <div class="col-md-12">
                            <div class="alert alert-danger">
                                <strong>Rejected</strong> by {{ optional($journalVoucher->approveUser)->name ?? 'N/A' }}
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

@if ($journalVoucher->jv_status == 'pending')
    <script>
        $(document).ready(function() {
            $('#approveBtn').click(function() {
                $.ajax({
                    url: '{{ route('journal-voucher.approve', $journalVoucher->id) }}',
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.success) {
                            $('.modal-sidebar').removeClass('open');
                            $('.main-content').css('cursor', 'auto');
                            
                            filterationCommon('{{ route('get.journal-vouchers') }}');
                        }
                    },
                    error: function(xhr) {
                        const error = xhr.responseJSON?.error || 'An error occurred while approving the journal voucher.';
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: error,
                            confirmButtonColor: '#D95000'
                        });
                    }
                });
            });

            $('#rejectBtn').click(function() {
                Swal.fire({
                    title: 'Are you sure?',
                    text: 'Do you want to reject this journal voucher?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Yes, reject it!',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: '{{ route('journal-voucher.reject', $journalVoucher->id) }}',
                            type: 'POST',
                            data: {
                                _token: '{{ csrf_token() }}'
                            },
                            success: function(response) {
                                if (response.success) {
                                    $('.modal-sidebar').removeClass('open');
                                    $('.main-content').css('cursor', 'auto');
                                    
                                    filterationCommon('{{ route('get.journal-vouchers') }}');
                                }
                            },
                            error: function(xhr) {
                                const error = xhr.responseJSON?.error || 'An error occurred while rejecting the journal voucher.';
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: error,
                                    confirmButtonColor: '#D95000'
                                });
                            }
                        });
                    }
                });
            });
        });
    </script>
@endif