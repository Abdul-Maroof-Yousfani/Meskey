{{--
    Trucks (arrival tickets) of a purchase order: summary boxes + ticket table.
    Opened in the side modal from the purchase order listing and the ticket contracts table.

    To use it somewhere else:
        @include('management.procurement.raw_material.purchase_order.trucks', ['tickets' => $tickets])

    $tickets: collection of ArrivalTicket with
        unique_no, truck_no, bilty_no, net_weight, arrived_net_weight,
        first_qc_status, second_qc_status, arrival_slip_status
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
        border-radius: 6px;
        padding: 12px 14px;
        background: #fff;
        height: 100%;
    }

    .ats-box .ats-label {
        font-size: 12px;
        color: #6b6f82;
        text-transform: uppercase;
        letter-spacing: .3px;
    }

    .ats-box .ats-value {
        font-size: 22px;
        font-weight: 600;
        line-height: 1.3;
    }

    .ats-box.ats-danger .ats-value {
        color: #ff4961;
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
            </tr>
        </thead>
        <tbody>
            @forelse ($tickets as $ticket)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $ticket->unique_no ?? 'N/A' }}</td>
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
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center text-muted">No trucks found</td>
                </tr>
            @endforelse
        </tbody>
        @if ($tickets->isNotEmpty())
            <tfoot>
                <tr>
                    <th colspan="4" class="text-right">Total</th>
                    <th class="text-right">{{ number_format((float) $tickets->sum('net_weight'), 2) }}</th>
                    <th class="text-right">{{ number_format($totalKgs, 2) }}</th>
                    <th></th>
                </tr>
            </tfoot>
        @endif
    </table>
</div>
