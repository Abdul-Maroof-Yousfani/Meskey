@section('title')
    View Journal Voucher
@endsection

<input type="hidden" id="listRefresh" value="{{ route('get.journal-vouchers') }}" />

@if(in_array(strtolower($journalVoucher->am_approval_status ?? ''), ['approved', 'rejected']))
    @php
        $isApproved = strtolower($journalVoucher->am_approval_status ?? '') === 'approved';
        $alertClass = $isApproved ? 'alert-success' : 'alert-danger';
        $iconClass = $isApproved ? 'fa-check-circle' : 'fa-times-circle';
        $statusText = ucfirst($journalVoucher->am_approval_status ?? 'pending');
    @endphp
    <div class="alert {{ $alertClass }} px-3 py-2 mt-2 d-flex align-items-center justify-content-between" style="border-radius: 6px;">
        <div>
            <i class="fa {{ $iconClass }} me-2"></i>
            <strong>Status: {{ $statusText }}</strong> - This Journal Voucher has been finalized. Its status cannot be changed.
        </div>
    </div>
@elseif(strtolower($journalVoucher->am_approval_status ?? '') === 'reverted')
    <div class="alert alert-warning px-3 py-2 mt-2 d-flex align-items-center justify-content-between" style="border-radius: 6px;">
        <div>
            <i class="fa fa-undo me-2"></i>
            <strong>Status: Reverted</strong> - This Journal Voucher has been reverted. It cannot be approved or rejected until changes are made and resubmitted to pending.
        </div>
        <span class="badge badge-warning text-dark px-2 py-1 text-uppercase">Reverted</span>
    </div>
@endif

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
                            <p>{{ $journalVoucher->jv_date ? $journalVoucher->jv_date->format('d-m-Y') : 'N/A' }}</p>
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
                                @php
                                    $currentStatus = strtolower($journalVoucher->am_approval_status ?? 'pending');
                                    $badge = match ($currentStatus) {
                                        'approved' => 'badge-success',
                                        'rejected' => 'badge-danger',
                                        'pending' => 'badge-warning',
                                        'reverted' => 'badge-secondary',
                                        default => 'badge-secondary'
                                    };
                                @endphp
                                <span class="badge {{ $badge }}">
                                    {{ ucfirst($currentStatus) }}
                                </span>
                            </p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label><strong>Created By:</strong></label>
                            <p>{{ optional($journalVoucher->createdBy)->name ?? ($journalVoucher->username ?? 'N/A') }}</p>
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
                            @php
                                $isReceiving = false;
                                $hasOrders = false;
                                foreach($journalVoucher->journalVoucherDetails as $detail) {
                                    if(!empty($detail->receipt_voucher_id) || (!empty($detail->sales_order_id) && empty($detail->voucher_type))) {
                                        $isReceiving = true;
                                    }
                                    if(!empty($detail->voucher_no)) {
                                        $hasOrders = true;
                                    }
                                }
                            @endphp
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Account</th>
                                            @if($isReceiving)
                                                <th>Receipt Voucher</th>
                                                <th>Sales order</th>
                                            @elseif($hasOrders)
                                                <th>Order / GRN</th>
                                            @endif
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
                                                @if($isReceiving)
                                                    <td>{{ optional($detail->receiptVoucher)->unique_no ?? '—' }}</td>
                                                    <td>{{ optional($detail->salesOrder)->reference_no ?? '—' }}</td>
                                                @elseif($hasOrders)
                                                    <td>{{ $detail->voucher_no ?? '—' }}</td>
                                                @endif
                                                <td>{{ $detail->description ?? '—' }}</td>
                                                <td>{{ $detail->debit_amount > 0 ? number_format($detail->debit_amount, 2) : '—' }}</td>
                                                <td>{{ $detail->credit_amount > 0 ? number_format($detail->credit_amount, 2) : '—' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <td colspan="{{ $isReceiving ? 4 : ($hasOrders ? 3 : 2) }}" class="text-right"><strong>Total Debits:</strong></td>
                                            <td><strong>{{ number_format($totalDebits, 2) }}</strong></td>
                                            <td></td>
                                        </tr>
                                        <tr>
                                            <td colspan="{{ $isReceiving ? 4 : ($hasOrders ? 3 : 2) }}" class="text-right"><strong>Total Credits:</strong></td>
                                            <td></td>
                                            <td><strong>{{ number_format($totalCredits, 2) }}</strong></td>
                                        </tr>
                                        <tr>
                                            <td colspan="{{ $isReceiving ? 4 : ($hasOrders ? 3 : 2) }}" class="text-right"><strong>Difference (Debit - Credit):</strong></td>
                                            <td><strong style="color: {{ abs($totalDebits - $totalCredits) > 0.01 ? 'red' : 'green' }}">{{ number_format($totalDebits - $totalCredits, 2) }}</strong></td>
                                            <td></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<div class="approval-view-wrapper">
    <div class="row">
        <div class="col-12">
            <x-approval-status :model="$journalVoucher" :list-refresh="route('get.journal-vouchers')" />
        </div>
    </div>
</div>

<style>
    .approval-view-wrapper .alert-primary,
    .approval-view-wrapper .alert-warning {
        display: none !important;
    }
    .current-status-tag {
        display: inline-flex;
        align-items: center;
        gap: 3px;
        font-size: 9px;
        font-weight: 700;
        color: #047857;
        background-color: #ecfdf5;
        border: 1px solid #a7f3d0;
        padding: 1px 7px;
        border-radius: 10px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        line-height: 1.4;
    }
    .current-status-dot {
        width: 5px;
        height: 5px;
        background-color: #10b981;
        border-radius: 50%;
        display: inline-block;
    }
</style>

@if ($jvApprovalLogs->isNotEmpty())
    <div class="approval-table-wrapper" style="margin-top: 25px; padding-bottom: 10px !important;">
        <div class="card border" style="box-shadow: none; margin-bottom: 0 !important;">
            <div class="card-header bg-light py-2 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 text-dark fw-bold" style="font-size: 14px;">
                    Approval History & Comments
                </h6>
                <span class="badge badge-info">{{ $jvApprovalLogs->count() }} {{ \Illuminate\Support\Str::plural('Action', $jvApprovalLogs->count()) }}</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-hover mb-0" style="font-size: 13px;">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 50px;" class="text-center">#</th>
                                <th style="min-width: 160px; width: 22%;">User</th>
                                <th style="min-width: 150px; width: 18%;" class="text-center">Action</th>
                                <th style="min-width: 160px; width: 20%;">Date & Time</th>
                                <th style="min-width: 300px; width: 40%;">Comments</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($jvApprovalLogs as $index => $log)
                                @php
                                    $badgeClass = match($log->action) {
                                        'approved' => 'badge-success',
                                        'rejected' => 'badge-danger',
                                        'reverted' => 'badge-warning',
                                        'partial_approved' => 'badge-info',
                                        default => 'badge-secondary'
                                    };
                                @endphp
                                <tr>
                                    <td class="text-center align-middle">{{ $index + 1 }}</td>
                                    <td class="align-middle">
                                        <strong>{{ $log->user->name ?? 'N/A' }}</strong>
                                        @if ($log->user_id === auth()->id())
                                            <span class="badge badge-primary ms-1" style="font-size: 10px;">You</span>
                                        @endif
                                    </td>
                                    <td class="text-center align-middle">
                                        <span class="badge {{ $badgeClass }} text-uppercase px-2 py-1" style="font-size: 11px;">
                                            {{ str_replace('_', ' ', $log->action) }}
                                        </span>
                                        @if ($loop->first)
                                            <div class="mt-1">
                                                <span class="current-status-tag">
                                                    <span class="current-status-dot"></span> Current
                                                </span>
                                            </div>
                                        @endif
                                    </td>
                                    <td class="align-middle">
                                        {{ $log->created_at ? $log->created_at->format('d M, Y h:i A') : 'N/A' }}
                                    </td>
                                    <td class="align-middle">
                                        @if (!empty(trim($log->comments ?? '')))
                                            <span class="text-dark">{{ $log->comments }}</span>
                                        @else
                                            <span class="text-muted fst-italic">No comments</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div style="height: 60px; width: 100%; clear: both;"></div>
    </div>
@endif