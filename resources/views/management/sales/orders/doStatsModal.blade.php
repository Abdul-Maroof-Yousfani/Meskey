<style>
    .do-stats-container {
        font-family: inherit;
    }
    .do-stat-card {
        border-radius: 8px;
        padding: 14px 18px;
        background: #fff;
        border: 1px solid #e2e8f0;
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        display: flex;
        align-items: center;
        margin-bottom: 15px;
    }
    .do-stat-card .icon-box {
        width: 44px;
        height: 44px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        margin-right: 14px;
        flex-shrink: 0;
    }
    .do-stat-card .stat-title {
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #64748b;
        font-weight: 600;
        margin-bottom: 2px;
    }
    .do-stat-card .stat-val {
        font-size: 18px;
        font-weight: 700;
        color: #1e293b;
        line-height: 1.2;
    }
    .table-stats th {
        background-color: #f8fafc;
        color: #334155;
        font-weight: 600;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        border-top: 1px solid #e2e8f0 !important;
        vertical-align: middle;
    }
    .table-stats td {
        vertical-align: middle;
        font-size: 13px;
    }
    .table-stats tfoot tr {
        background-color: #f1f5f9;
        font-weight: 700;
        border-top: 2px solid #cbd5e1;
    }
    .dc-sub-badge {
        display: inline-block;
        font-size: 11px;
        padding: 2px 6px;
        border-radius: 4px;
        background: #e0f2fe;
        color: #0369a1;
        margin: 2px 2px 0 0;
        border: 1px solid #bae6fd;
    }
</style>

<div class="do-stats-container p-2">
    {{-- Header SO Info Box --}}
    <div class="card mb-3 border" style="border-radius: 8px; background: #fdfdfd;">
        <div class="card-body py-2 px-3">
            <div class="row align-items-center">
                <div class="col-md-3">
                    <small class="text-muted text-uppercase d-block" style="font-size: 10px; font-weight: 600;">Sale Order No</small>
                    <span class="h6 mb-0 font-weight-bold text-primary">#{{ $sale_order->reference_no }}</span>
                </div>
                <div class="col-md-3">
                    <small class="text-muted text-uppercase d-block" style="font-size: 10px; font-weight: 600;">Customer</small>
                    <span class="font-weight-bold text-dark">{{ $sale_order->customer->name ?? 'N/A' }}</span>
                </div>
                <div class="col-md-2">
                    <small class="text-muted text-uppercase d-block" style="font-size: 10px; font-weight: 600;">Order Date</small>
                    <span>{{ $sale_order->order_date ? \Carbon\Carbon::parse($sale_order->order_date)->format('d M, Y') : 'N/A' }}</span>
                </div>
                <div class="col-md-2">
                    <small class="text-muted text-uppercase d-block" style="font-size: 10px; font-weight: 600;">Total SO Qty</small>
                    <span class="font-weight-bold">{{ number_format($sale_order->sales_order_data->sum('qty'), 2) }}</span>
                </div>
                <div class="col-md-2 text-md-end">
                    @php
                        $status = strtolower($sale_order->am_approval_status ?? '');
                        $badge = match ($status) {
                            'approved' => 'badge-success',
                            'rejected' => 'badge-danger',
                            'pending' => 'badge-warning',
                            default => 'badge-secondary',
                        };
                    @endphp
                    <small class="text-muted text-uppercase d-block" style="font-size: 10px; font-weight: 600;">SO Status</small>
                    <span class="badge {{ $badge }} px-2 py-1 text-uppercase">{{ $sale_order->am_approval_status ?? 'Pending' }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- 4 Stat Metric Cards --}}
    <div class="row">
        <div class="col-md-3 col-sm-6">
            <div class="do-stat-card">
                {{-- <div class="icon-box" style="background-color: #ede9fe; color: #6d28d9;">
                    <i class="ft-file-text"></i>
                </div> --}}
                <div>
                    <div class="stat-title">Total DOs</div>
                    <div class="stat-val">{{ count($doStats) }}</div>
                </div>
            </div>
        </div>

        <div class="col-md-3 col-sm-6">
            <div class="do-stat-card">
                {{-- <div class="icon-box" style="background-color: #e0f2fe; color: #0284c7;">
                    <i class="ft-layers"></i>
                </div> --}}
                <div>
                    <div class="stat-title">Total DO Qty</div>
                    <div class="stat-val text-primary">{{ number_format($totalDoQty, 2) }}</div>
                </div>
            </div>
        </div>

        <div class="col-md-3 col-sm-6">
            <div class="do-stat-card">
                {{-- <div class="icon-box" style="background-color: #dcfce7; color: #15803d;">
                    <i class="ft-check-circle"></i>
                </div> --}}
                <div>
                    <div class="stat-title">Total DC Qty (Done)</div>
                    <div class="stat-val text-success">{{ number_format($totalDcQty, 2) }}</div>
                </div>
            </div>
        </div>

        <div class="col-md-3 col-sm-6">
            <div class="do-stat-card">
                {{-- <div class="icon-box" style="background-color: #fef3c7; color: #b45309;">
                    <i class="ft-clock"></i>
                </div> --}}
                <div>
                    <div class="stat-title">Remaining Qty</div>
                    <div class="stat-val text-warning">{{ number_format($totalRemainingQty, 2) }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Overall Progress Bar --}}
    @if($totalDoQty > 0)
        @php
            $overallPct = min(100, round(($totalDcQty / $totalDoQty) * 100, 1));
            $barColor = $overallPct >= 100 ? 'bg-success' : ($overallPct >= 50 ? 'bg-info' : 'bg-warning');
        @endphp
        <div class="mb-3">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <span style="font-size: 12px; font-weight: 600; color: #475569;">
                    Delivery Challan Fulfillment: {{ $overallPct }}%
                </span>
                <span style="font-size: 11px; color: #64748b;">
                    {{ number_format($totalDcQty, 2) }} of {{ number_format($totalDoQty, 2) }} Fulfilled
                </span>
            </div>
            <div class="progress" style="height: 8px; border-radius: 4px; background: #e2e8f0;">
                <div class="progress-bar {{ $barColor }}" role="progressbar" style="width: {{ $overallPct }}%;" aria-valuenow="{{ $overallPct }}" aria-valuemin="0" aria-valuemax="100"></div>
            </div>
        </div>
    @endif

    {{-- Delivery Orders Table --}}
    <div class="table-responsive border rounded" style="background: #fff;">
        <table class="table table-stats table-hover mb-0">
            <thead>
                <tr>
                    <th width="4%" class="text-center">#</th>
                    <th width="15%">DO Number</th>
                    <th width="12%">Date</th>
                    <th width="18%">Item</th>
                    <th width="10%" class="text-center">Status</th>
                    <th width="13%" class="text-end text-right">DO Qty</th>
                    <th width="14%" class="text-end text-right">DC Qty</th>
                    <th width="14%" class="text-end text-right">Remaining Qty</th>
                </tr>
            </thead>
            <tbody>
                @forelse($doStats as $index => $stat)
                    @php
                        $statStatus = strtolower($stat['status'] ?? '');
                        $statusBadge = match ($statStatus) {
                            'approved' => 'badge-success',
                            'rejected' => 'badge-danger',
                            'pending' => 'badge-warning',
                            default => 'badge-secondary',
                        };
                        $pct = $stat['do_qty'] > 0 ? min(100, round(($stat['dc_qty'] / $stat['do_qty']) * 100, 1)) : 0;
                    @endphp
                    <tr>
                        <td class="text-center font-weight-bold text-muted">{{ $index + 1 }}</td>
                        <td>
                            <span class="font-weight-bold text-primary">{{ $stat['reference_no'] }}</span>
                            @if(!empty($stat['is_dummy']))
                                <span class="badge badge-primary ml-1" style="font-size: 0.65rem; vertical-align: middle;">Dummy DO</span>
                            @endif
                            @if(!empty($stat['dispatch_date']))
                                <br><small class="text-muted">Dispatch: {{ \Carbon\Carbon::parse($stat['dispatch_date'])->format('d M Y') }}</small>
                            @endif
                        </td>
                        <td>
                            <span>{{ $stat['do_date'] ? \Carbon\Carbon::parse($stat['do_date'])->format('d M, Y') : 'N/A' }}</span>
                        </td>
                        <td>
                            <span class="text-dark">{{ $stat['items'] }}</span>
                        </td>
                        <td class="text-center">
                            <span class="badge {{ $statusBadge }} px-2 py-1 text-uppercase" style="font-size: 10px;">
                                {{ ucfirst($stat['status']) }}
                            </span>
                        </td>
                        <td class="text-end text-right font-weight-bold" style="font-size: 14px;">
                            {{ number_format($stat['do_qty'], 2) }}
                        </td>
                        <td class="text-end text-right font-weight-bold text-success" style="font-size: 14px;">
                            {{ number_format($stat['dc_qty'], 2) }}
                            @if(count($stat['dcs']) > 0)
                                <div class="mt-1">
                                    @foreach($stat['dcs'] as $dcItem)
                                        <span class="dc-sub-badge" title="Dispatch: {{ $dcItem['dispatch_date'] ?? 'N/A' }}">
                                            {{ $dcItem['reference_number'] }}: {{ number_format($dcItem['qty'], 2) }}
                                        </span>
                                    @endforeach
                                </div>
                            @endif
                        </td>
                        <td class="text-end text-right font-weight-bold {{ $stat['remaining_qty'] > 0 ? 'text-warning' : 'text-muted' }}" style="font-size: 14px;">
                            {{ number_format($stat['remaining_qty'], 2) }}
                            @if($stat['do_qty'] > 0)
                                <div class="mt-1">
                                    <small class="text-muted">{{ $pct }}% done</small>
                                </div>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center py-5">
                            <div class="my-3">
                                <i class="ft-inbox text-muted" style="font-size: 42px;"></i>
                                <h6 class="mt-2 text-muted">No Delivery Orders Found</h6>
                                <p class="text-muted mb-0 font-small-3">No Delivery Orders have been created against this Sale Order yet.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
            @if(count($doStats) > 0)
                <tfoot>
                    <tr>
                        <td colspan="5" class="text-end text-right font-weight-bold text-uppercase py-2">
                            Grand Totals:
                        </td>
                        <td class="text-end text-right font-weight-bold text-primary py-2" style="font-size: 15px;">
                            {{ number_format($totalDoQty, 2) }}
                        </td>
                        <td class="text-end text-right font-weight-bold text-success py-2" style="font-size: 15px;">
                            {{ number_format($totalDcQty, 2) }}
                        </td>
                        <td class="text-end text-right font-weight-bold text-warning py-2" style="font-size: 15px;">
                            {{ number_format($totalRemainingQty, 2) }}
                        </td>
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>

    {{-- Bottom Bar with Close Button --}}
    <div class="row mt-4 bottom-button-bar">
        <div class="col-12 text-end text-right">
            <a type="button" class="btn btn-danger modal-sidebar-close position-relative top-1 closebutton me-2">Close</a>
        </div>
    </div>
</div>
