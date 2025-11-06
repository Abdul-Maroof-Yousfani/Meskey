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
                    <div class="col-md-6">
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
                    <div class="col-md-6">
                        <div class="form-group">
                            <label><strong>Created By:</strong></label>
                            <p>{{ $journalVoucher->username ?? 'N/A' }}</p>
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
                                            <th>Debit/Credit</th>
                                            <th>Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php
                                            $totalDebits = 0;
                                            $totalCredits = 0;
                                        @endphp
                                        @foreach ($journalVoucher->journalVoucherDetails as $detail)
                                            @php
                                                if ($detail->debit_credit == 'debit') {
                                                    $totalDebits += $detail->amount;
                                                } else {
                                                    $totalCredits += $detail->amount;
                                                }
                                            @endphp
                                            <tr>
                                                <td>{{ $detail->account->name ?? 'N/A' }}
                                                    ({{ $detail->account->unique_no ?? 'N/A' }})</td>
                                                <td>
                                                    <span
                                                        class="badge badge-{{ $detail->debit_credit == 'debit' ? 'primary' : 'success' }}">
                                                        {{ ucfirst($detail->debit_credit) }}
                                                    </span>
                                                </td>
                                                <td>{{ number_format($detail->amount, 2) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <td colspan="2" class="text-right"><strong>Total Debits:</strong></td>
                                            <td><strong>{{ number_format($totalDebits, 2) }}</strong></td>
                                        </tr>
                                        <tr>
                                            <td colspan="2" class="text-right"><strong>Total Credits:</strong></td>
                                            <td><strong>{{ number_format($totalCredits, 2) }}</strong></td>
                                        </tr>
                                        <!-- <tr>
                                                    <td colspan="2" class="text-right"><strong>Difference:</strong></td>
                                                    <td>
                                                        <strong style="color: {{ abs($totalDebits - $totalCredits) > 0.01 ? 'red' : 'green' }}">
                                                            {{ number_format($totalDebits - $totalCredits, 2) }}
                                                        </strong>
                                                    </td>
                                                </tr> -->
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
                                <strong>Approved</strong> by {{ $journalVoucher->approve_username ?? 'N/A' }}
                            </div>
                        </div>
                    </div>
                @elseif ($journalVoucher->jv_status == 'rejected')
                    <div class="row mt-3">
                        <div class="col-md-12">
                            <div class="alert alert-danger">
                                <strong>Rejected</strong> by {{ $journalVoucher->approve_username ?? 'N/A' }}
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
                        alert(error);
                    }
                });
            });

            $('#rejectBtn').click(function() {
                if (confirm('Are you sure you want to reject this journal voucher?')) {
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
                            alert(error);
                        }
                    });
                }
            });
        });
    </script>
@endif