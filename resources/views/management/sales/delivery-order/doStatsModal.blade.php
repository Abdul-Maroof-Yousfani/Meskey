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
        margin-bottom: 15px;
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
        font-size: 20px;
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
    .section-title {
        font-size: 13px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #334155;
        margin-bottom: 10px;
        border-bottom: 1px solid #e2e8f0;
        padding-bottom: 6px;
    }
</style>

<div class="do-stats-container p-2">
    {{-- Header DO Info Box --}}
    <div class="card mb-3 border" style="border-radius: 8px; background: #fdfdfd;">
        <div class="card-body py-2 px-3">
            <div class="row align-items-center">
                <div class="col-md-3">
                    <small class="text-muted text-uppercase d-block" style="font-size: 10px; font-weight: 600;">DO Number</small>
                    <span class="h6 mb-0 font-weight-bold text-primary">#{{ $delivery_order->reference_no }}</span>
                    @if($delivery_order->is_auto_created_from_so)
                        <span class="badge badge-primary ml-1" style="font-size: 0.65rem; vertical-align: middle;">Dummy DO</span>
                    @endif
                </div>
                <div class="col-md-3">
                    <small class="text-muted text-uppercase d-block" style="font-size: 10px; font-weight: 600;">Sale Order Ref</small>
                    <span class="font-weight-bold text-dark">#{{ $delivery_order->salesOrder->reference_no ?? 'N/A' }}</span>
                </div>
                <div class="col-md-2">
                    <small class="text-muted text-uppercase d-block" style="font-size: 10px; font-weight: 600;">Customer</small>
                    <span class="font-weight-bold text-dark">{{ $delivery_order->customer->name ?? 'N/A' }}</span>
                </div>
                <div class="col-md-2">
                    <small class="text-muted text-uppercase d-block" style="font-size: 10px; font-weight: 600;">Dispatch / DO Date</small>
                    <span>{{ $delivery_order->dispatch_date ? \Carbon\Carbon::parse($delivery_order->dispatch_date)->format('d M, Y') : 'N/A' }}</span>
                </div>
                <div class="col-md-2 text-md-end text-right">
                    @php
                        $status = strtolower($delivery_order->am_approval_status ?? '');
                        $badge = match ($status) {
                            'approved' => 'badge-success',
                            'rejected' => 'badge-danger',
                            'pending' => 'badge-warning',
                            default => 'badge-secondary',
                        };
                    @endphp
                    <small class="text-muted text-uppercase d-block" style="font-size: 10px; font-weight: 600;">DO Status</small>
                    <span class="badge {{ $badge }} px-2 py-1 text-uppercase">{{ $delivery_order->am_approval_status ?? 'Pending' }}</span>
                    @if($delivery_order->do_status === 'closed')
                        <span class="badge badge-danger px-2 py-1 text-uppercase ml-1">Closed</span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Stat Metric Cards --}}
    <div class="row">
        <div class="col-md-3 col-sm-6">
            <div class="do-stat-card">
                <div class="stat-title">Total DO Qty</div>
                <div class="stat-val text-primary">{{ number_format($doQty, 2) }}</div>
            </div>
        </div>

        <div class="col-md-3 col-sm-6">
            <div class="do-stat-card">
                <div class="stat-title">Total DC Qty (Done)</div>
                <div class="stat-val text-success">{{ number_format($dcQty, 2) }}</div>
            </div>
        </div>

        <div class="col-md-3 col-sm-6">
            <div class="do-stat-card">
                <div class="stat-title">Remaining Qty</div>
                <div class="stat-val text-warning">{{ number_format($remainingQty, 2) }}</div>
            </div>
        </div>

        <div class="col-md-3 col-sm-6">
            <div class="do-stat-card">
                <div class="stat-title">Total DCs Generated</div>
                <div class="stat-val">{{ count($challans) }}</div>
            </div>
        </div>
    </div>

    {{-- Overall Progress Bar --}}
    @if($doQty > 0)
        @php
            $overallPct = min(100, round(($dcQty / $doQty) * 100, 1));
            $barColor = $overallPct >= 100 ? 'bg-success' : ($overallPct >= 50 ? 'bg-info' : 'bg-warning');
        @endphp
        <div class="mb-4">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <span style="font-size: 12px; font-weight: 600; color: #475569;">
                    Delivery Challan Fulfillment: {{ $overallPct }}%
                </span>
                <span style="font-size: 11px; color: #64748b;">
                    {{ number_format($dcQty, 2) }} of {{ number_format($doQty, 2) }} Fulfilled
                </span>
            </div>
            <div class="progress" style="height: 8px; border-radius: 4px; background: #e2e8f0;">
                <div class="progress-bar {{ $barColor }}" role="progressbar" style="width: {{ $overallPct }}%;" aria-valuenow="{{ $overallPct }}" aria-valuemin="0" aria-valuemax="100"></div>
            </div>
        </div>
    @endif

    {{-- Section 1: DO Items Detail --}}
    <div class="mb-4">
        <div class="section-title">Delivery Order Items Breakdown</div>
        <div class="table-responsive border rounded" style="background: #fff;">
            <table class="table table-stats table-hover mb-0">
                <thead>
                    <tr>
                        <th width="5%" class="text-center">#</th>
                        <th width="40%">Item Name</th>
                        <th width="18%" class="text-end text-right">DO Qty</th>
                        <th width="18%" class="text-end text-right">DC Dispatched</th>
                        <th width="19%" class="text-end text-right">Remaining Qty</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($itemStats as $index => $item)
                        @php
                            $itemPct = $item['do_qty'] > 0 ? min(100, round(($item['dc_qty'] / $item['do_qty']) * 100, 1)) : 0;
                        @endphp
                        <tr>
                            <td class="text-center font-weight-bold text-muted">{{ $index + 1 }}</td>
                            <td>
                                <span class="font-weight-bold text-dark">{{ $item['item_name'] }}</span>
                            </td>
                            <td class="text-end text-right font-weight-bold text-primary" style="font-size: 14px;">
                                {{ number_format($item['do_qty'], 2) }}
                            </td>
                            <td class="text-end text-right font-weight-bold text-success" style="font-size: 14px;">
                                {{ number_format($item['dc_qty'], 2) }}
                            </td>
                            <td class="text-end text-right font-weight-bold {{ $item['remaining_qty'] > 0 ? 'text-warning' : 'text-muted' }}" style="font-size: 14px;">
                                {{ number_format($item['remaining_qty'], 2) }}
                                @if($item['do_qty'] > 0)
                                    <br><small class="text-muted">{{ $itemPct }}% done</small>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">No items found for this Delivery Order.</td>
                        </tr>
                    @endforelse
                </tbody>
                @if(count($itemStats) > 0)
                    <tfoot>
                        <tr>
                            <td colspan="2" class="text-end text-right font-weight-bold text-uppercase py-2">
                                Grand Totals:
                            </td>
                            <td class="text-end text-right font-weight-bold text-primary py-2" style="font-size: 15px;">
                                {{ number_format($totalItemDoQty, 2) }}
                            </td>
                            <td class="text-end text-right font-weight-bold text-success py-2" style="font-size: 15px;">
                                {{ number_format($totalItemDcQty, 2) }}
                            </td>
                            <td class="text-end text-right font-weight-bold text-warning py-2" style="font-size: 15px;">
                                {{ number_format($totalItemRemainingQty, 2) }}
                            </td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>

    {{-- Bottom Bar with Close Button --}}
    <div class="row mt-4 bottom-button-bar">
        <div class="col-12 text-end text-right">
            <a type="button" class="btn btn-danger modal-sidebar-close position-relative top-1 closebutton me-2">Close</a>
        </div>
    </div>
</div>

