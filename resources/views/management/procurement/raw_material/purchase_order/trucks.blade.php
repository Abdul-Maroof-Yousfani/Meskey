{{--
    Trucks (arrival tickets) of a purchase order: summary boxes + ticket table.
    Opened in the side modal from the purchase order listing and the ticket contracts table.

    To use it somewhere else:
        @include('management.procurement.raw_material.purchase_order.trucks', ['tickets' => $tickets])

    $tickets: collection of ArrivalTicket with
        unique_no, truck_no, bilty_no, net_weight, arrived_net_weight,
        first_qc_status, second_qc_status, arrival_slip_status, is_ticket_verified, created_at
    $purchaseOrder (optional): shows the contract # and commodity on top
--}}
@php
    $tickets = collect($tickets ?? []);

    // Rejected in 1st or 2nd QC wins over everything else
    $truckStatus = function ($ticket) {
        if ($ticket->first_qc_status === 'rejected' || $ticket->second_qc_status === 'rejected') {
            return 'rejected';
        }
        return $ticket->arrival_slip_status === 'generated' ? 'completed' : 'in_process';
    };

    $totalTrucks = $tickets->count();
    $rejectedTrucks = $tickets->filter(fn($ticket) => $truckStatus($ticket) === 'rejected')->count();
    $totalKgs = (float) $tickets->sum('arrived_net_weight');
@endphp

<style>
    .ats-box {
        border: 1px solid #e3e6ef;
        border-radius: 8px;
        padding: 10px 12px;
        background: #fff;
        height: 100%;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }

    .ats-box .ats-label {
        font-size: 11px;
        color: #6b6f82;
        text-transform: uppercase;
        letter-spacing: .3px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .ats-box .ats-value {
        font-size: 18px;
        font-weight: 700;
        line-height: 1.3;
        color: #2b2a35;
    }

    .ats-box.ats-danger .ats-value {
        color: #ff4961;
    }

    /* Instagram style blue tick for verified tickets */
    .ats-verified {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 15px;
        height: 15px;
        margin-left: 4px;
        border-radius: 50%;
        background: #1d9bf0;
        color: #fff;
        font-size: 9px;
        vertical-align: middle;
    }

    .tkc-truck-cards {
        display: none;
    }

    @media (max-width: 767.98px) {
        .tkc-truck-cards {
            display: block !important;
        }
        .tkc-truck-cards .tkc {
            margin-bottom: 10px;
        }
        .truck-table-wrap {
            display: none !important;
        }
        .ats-box {
            padding: 8px 10px;
        }
        .ats-box .ats-value {
            font-size: 15px;
        }
    }
</style>

@isset($purchaseOrder)
    <div class="mb-2">
        <strong>Contract #:</strong> {{ $purchaseOrder->contract_no }}
        <span class="mx-1">|</span>
        <strong>Commodity:</strong> {{ $purchaseOrder->product->name ?? 'N/A' }}
    </div>
@endisset

<div class="row mb-2">
    <div class="col-4 pr-1">
        <div class="ats-box">
            <div class="ats-label">Total Trucks</div>
            <div class="ats-value">{{ $totalTrucks }}</div>
        </div>
    </div>
    <div class="col-4 px-1">
        <div class="ats-box ats-danger">
            <div class="ats-label">Rejected Trucks</div>
            <div class="ats-value">{{ $rejectedTrucks }}</div>
        </div>
    </div>
    <div class="col-4 pl-1">
        <div class="ats-box">
            <div class="ats-label">Total KGs</div>
            <div class="ats-value">{{ number_format($totalKgs, 2) }}</div>
        </div>
    </div>
</div>

{{-- ===== MOBILE: Truck Cards (sirf mobile par dikhenge) ===== --}}
<div class="tkc-list tkc-truck-cards">
    @forelse ($tickets as $ticket)
        @php
            $status = $truckStatus($ticket);
            $isRej = ($status === 'rejected');
            $statusClass = match ($status) {
                'rejected' => 'is-rejected',
                'completed' => 'is-approved',
                default => 'is-pending',
            };
            $statusLabel = match ($status) {
                'rejected' => 'Rejected',
                'completed' => 'Completed',
                default => 'In Process',
            };
            $diffWeight = (float) $ticket->arrived_net_weight - (float) $ticket->net_weight;
        @endphp
        <div class="tkc {{ $isRej ? 'tkc-tint-red is-rejected' : '' }}">
            <div class="tkc-main" style="padding: 12px 14px;">
                <div class="tkc-top">
                    <div class="tkc-no">
                        #{{ $ticket->unique_no ?? 'N/A' }}
                        @if ($ticket->is_ticket_verified == 1)
                            <span class="ats-verified" title="Verified"><i class="fa fa-check"></i></span>
                        @endif
                    </div>
                    <span class="tkc-qc {{ $statusClass }}">{{ $statusLabel }}</span>
                </div>

                <div class="tkc-date">
                    <i class="ft-clock"></i>
                    {{ $ticket->created_at ? $ticket->created_at->format('d M Y, h:i A') : 'N/A' }}
                </div>

                <div class="tkc-chips mb-2">
                    <span class="tkc-chip"><i class="ft-truck"></i> {{ $ticket->truck_no ?? 'N/A' }}</span>
                    <span class="tkc-chip"><i class="ft-file-text"></i> {{ $ticket->bilty_no ?? 'N/A' }}</span>
                </div>

                <div class="tkc-grid">
                    <div>
                        <span class="tkc-label">Loaded Weight</span>
                        <span class="tkc-val">{{ number_format((float) $ticket->net_weight, 2) }} kg</span>
                    </div>
                    <div>
                        <span class="tkc-label">Arrived Weight</span>
                        <span class="tkc-val font-weight-bold">{{ number_format((float) $ticket->arrived_net_weight, 2) }} kg</span>
                    </div>
                    <div>
                        <span class="tkc-label">Weight Diff</span>
                        <span class="tkc-val {{ $diffWeight < 0 ? 'text-danger' : ($diffWeight > 0 ? 'text-success' : 'text-muted') }}">
                            {{ ($diffWeight > 0 ? '+' : '') . number_format($diffWeight, 2) }} kg
                        </span>
                    </div>
                    <div>
                        <span class="tkc-label">Status</span>
                        <span class="tkc-val">{{ $statusLabel }}</span>
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="tkc-empty"><i class="ft-inbox"></i> No trucks found</div>
    @endforelse
</div>

{{-- ===== DESKTOP: Table (mobile par cards dikhte hain) ===== --}}
<div class="tk-table-wrap truck-table-wrap">
<div class="table-responsive">
    <table class="table table-bordered table-sm mb-0">
        <thead>
            <tr>
                <th>#</th>
                <th>Arrival Ticket No</th>
                <th>Truck No</th>
                <th>Bilty No</th>
                <th class="text-right">Loaded Weight</th>
                <th class="text-right">Arrived Weight</th>
                <th>Status</th>
                <th>Created At</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($tickets as $ticket)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td class="text-nowrap">
                        {{ $ticket->unique_no ?? 'N/A' }}
                        @if ($ticket->is_ticket_verified == 1)
                            <span class="ats-verified" title="Verified"><i class="fa fa-check"></i></span>
                        @endif
                    </td>
                    <td>{{ $ticket->truck_no ?? 'N/A' }}</td>
                    <td>{{ $ticket->bilty_no ?? 'N/A' }}</td>
                    <td class="text-right">{{ number_format((float) $ticket->net_weight, 2) }}</td>
                    <td class="text-right">{{ number_format((float) $ticket->arrived_net_weight, 2) }}</td>
                    <td>
                        @switch($truckStatus($ticket))
                            @case('rejected')
                                <span class="badge badge-danger">Rejected</span>
                                @break
                            @case('completed')
                                <span class="badge badge-success">Completed</span>
                                @break
                            @default
                                <span class="badge badge-warning">In Process</span>
                        @endswitch
                    </td>
                    <td class="text-nowrap">{{ $ticket->created_at ? $ticket->created_at->format('d-m-Y h:i A') : 'N/A' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center text-muted">No trucks found</td>
                </tr>
            @endforelse
        </tbody>
        @if ($tickets->isNotEmpty())
            <tfoot>
                <tr>
                    <th colspan="4" class="text-right">Total</th>
                    <th class="text-right">{{ number_format((float) $tickets->sum('net_weight'), 2) }}</th>
                    <th class="text-right">{{ number_format($totalKgs, 2) }}</th>
                    <th colspan="2"></th>
                </tr>
            </tfoot>
        @endif
    </table>
</div>
</div>
