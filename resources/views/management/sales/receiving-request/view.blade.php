<div>
    <!-- DC Information Section -->
    <div class="row">
        <div class="col-12">
            <h6 class="header-heading-sepration">
                DC Information
            </h6>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label class="font-weight-bold">DC No</label>
                <input type="text" class="form-control bg-light" value="{{ $receivingRequest->dc_no }}" readonly placeholder="DC No">
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label class="font-weight-bold">Date</label>
                <input type="text" class="form-control bg-light" value="{{ $receivingRequest->dc_date?->format('d-M-Y') ?? 'N/A' }}" readonly placeholder="Date">
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label class="font-weight-bold">Party (Customer)</label>
                <input type="text" class="form-control bg-light" value="{{ $receivingRequest->deliveryChallan?->customer?->name ?? 'N/A' }}" readonly>
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label class="font-weight-bold">Truck No</label>
                <input type="text" class="form-control bg-light" value="{{ $receivingRequest->truck_number ?? 'N/A' }}" readonly>
            </div>
        </div>
    </div>

    <!-- Details Section -->
    <div class="row">
        <div class="col-12">
            <h6 class="header-heading-sepration">
                Other Details
            </h6>
        </div>
        
        <div class="col-md-3">
            <div class="form-group">
                <label class="font-weight-bold">Total Dispatch Weight</label>
                <input type="text" class="form-control bg-light font-weight-bold" value="{{ number_format($receivingRequest->items->sum('dispatch_weight'), 2, '.', '') }}" readonly>
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label class="font-weight-bold">Receiving Weight (Total)</label>
                <input type="number" class="form-control bg-light" value="{{ $receivingRequest->arrived_weight }}" readonly>
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label class="font-weight-bold">Weight Difference</label>
                <input type="text" class="form-control bg-light font-weight-bold text-danger" value="{{ number_format(floatval($receivingRequest->items->sum('dispatch_weight')) - floatval($receivingRequest->arrived_weight), 2) }}" readonly>
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label class="font-weight-bold">Exempted Weight</label>
                <input type="number" class="form-control bg-light" value="{{ $receivingRequest->exempted_weight }}" readonly>
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label class="font-weight-bold">Payment Weight</label>
                <input type="number" class="form-control bg-light font-weight-bold" value="{{ $receivingRequest->payment_weight }}" readonly>
            </div>
        </div>
    </div>

    <!-- Labour Details Section -->
    <div class="row mt-3">
        <div class="col-12">
            <h6 class="header-heading-sepration">
                Labour Details
            </h6>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label class="font-weight-bold">Labour</label>
                @php
                    $labourName = \App\Models\Master\Vendor::find($receivingRequest->labour)?->name ?? 'N/A';
                @endphp
                <input type="text" class="form-control bg-light" value="{{ $labourName }}" readonly>
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label class="font-weight-bold">Labour Amount</label>
                <input type="number" class="form-control bg-light" value="{{ $receivingRequest->deliveryChallan?->labour_amount ?? $receivingRequest->labour_amount }}" readonly>
            </div>
        </div>
    </div>

    <!-- Transporter Details Section -->
    <div class="row mt-3">
        <div class="col-12">
            <h6 class="header-heading-sepration">
                Transporter Details
            </h6>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label class="font-weight-bold">Transporter</label>
                @php
                    $transporterName = \App\Models\Master\Transporter::find($receivingRequest->transporter)?->name ?? 'N/A';
                @endphp
                <input type="text" class="form-control bg-light" value="{{ $transporterName }}" readonly>
            </div>
        </div>
        @php
            $salesOrder = $receivingRequest->deliveryChallan->delivery_order->first()?->salesOrder;
            $logistics = $salesOrder?->logistics->first();
            $logisticsItem = $logistics ? $logistics->items()->where('transporter_id', $receivingRequest->transporter)->first() : null;
            $transporterRate = $logisticsItem?->rate ?? 'N/A';
            $transporterRateType = $logisticsItem?->rate_type ? ucfirst(str_replace('_', ' ', $logisticsItem->rate_type)) : 'N/A';
        @endphp
        <div class="col-md-3">
            <div class="form-group">
                <label class="font-weight-bold">Transporter Rate</label>
                <input type="text" class="form-control bg-light" value="{{ $transporterRate }}" readonly>
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label class="font-weight-bold">Transporter Rate Type</label>
                <input type="text" class="form-control bg-light" value="{{ $transporterRateType }}" readonly>
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label class="font-weight-bold">Deduction</label>
                <input type="number" class="form-control bg-light" value="{{ $receivingRequest->transporter_deduction ?? 0 }}" readonly>
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label class="font-weight-bold">Transporter Amount</label>
                <input type="number" class="form-control bg-light" value="{{ $receivingRequest->transporter_amount }}" readonly>
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label class="font-weight-bold">Total Amount</label>
                @php
                    $netTransporterAmount = floatval($receivingRequest->transporter_amount ?? 0) - floatval($receivingRequest->transporter_deduction ?? 0);
                @endphp
                <input type="number" class="form-control bg-light font-weight-bold" value="{{ number_format($netTransporterAmount, 2, '.', '') }}" readonly>
            </div>
        </div>
    </div>

    <!-- Unloading Labour Section -->
    <div class="row mt-3">
        <div class="col-12">
            <h6 class="header-heading-sepration">
                Unloading Labour
            </h6>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-bordered table-sm">
            <thead class="bg-light">
                <tr>
                    <th>Item Name</th>
                    <th>Bag Size</th>
                    <th>Dispatch Weight</th>
                    <th>No. of Bags</th>
                    <th>Unloading Labour Rate</th>
                    <th>Total Labour Amt</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $groupedItems = [];
                    foreach($receivingRequest->items as $item) {
                        $bagSize = $item->deliveryChallanData?->bag_size ?? 'N/A';
                        $itemName = $item->item_name;
                        $key = $itemName . '_' . $bagSize;
                        if(!isset($groupedItems[$key])) {
                            $groupedItems[$key] = [
                                'item_name' => $itemName,
                                'bag_size' => $bagSize,
                                'dispatch_weight' => 0,
                                'no_of_bags' => 0,
                                'unloading_labour_rate' => $item->unloading_labour_rate,
                            ];
                        }
                        $groupedItems[$key]['dispatch_weight'] += floatval($item->dispatch_weight);
                        $itemBags = floatval($item->deliveryChallanData?->no_of_bags ?: ($item->no_of_bags ?: 0));
                        $groupedItems[$key]['no_of_bags'] += $itemBags;
                    }
                @endphp

                @foreach($groupedItems as $index => $group)
                    @php
                        $rowLabourTotal = floatval($group['no_of_bags']) * floatval($group['unloading_labour_rate']);
                    @endphp
                    <tr>
                        <td>
                            <input type="text" value="{{ $group['item_name'] }}" class="form-control form-control-sm bg-light" readonly>
                        </td>
                        <td>
                            <input type="text" value="{{ $group['bag_size'] }}" class="form-control form-control-sm bg-light" readonly>
                        </td>
                        <td>
                            <input type="text" value="{{ number_format($group['dispatch_weight'], 2) }}" class="form-control form-control-sm bg-light" readonly>
                        </td>
                        <td>
                            <input type="text" value="{{ $group['no_of_bags'] }}" class="form-control form-control-sm bg-light" readonly>
                        </td>
                        <td>
                            <input type="text" value="{{ number_format($group['unloading_labour_rate'], 2) }}" class="form-control form-control-sm bg-light" readonly>
                        </td>
                        <td>
                            <input type="text" value="{{ number_format($rowLabourTotal, 2) }}" class="form-control form-control-sm bg-light font-weight-bold" readonly>
                        </td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="bg-light font-weight-bold">
                    <td colspan="5" class="text-right text-end font-weight-bold">Total:</td>
                    <td>
                        @php
                            $unloadingLabourTotal = 0;
                            foreach ($receivingRequest->items as $item) {
                                $bags = floatval($item->deliveryChallanData?->no_of_bags ?: ($item->no_of_bags ?: 0));
                                $rate = floatval($item->unloading_labour_rate ?? 0);
                                $unloadingLabourTotal += ($bags * $rate);
                            }
                        @endphp
                        <input type="text" value="{{ number_format($unloadingLabourTotal, 2) }}" class="form-control form-control-sm bg-light font-weight-bold" readonly>
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>

    <div class="row mt-2">
        <div class="col-md-3">
            <div class="form-group">
                <label class="font-weight-bold">Paid By</label>
                <input type="text" class="form-control bg-light" value="{{ $receivingRequest->unloading_paid_by ?? 'N/A' }}" readonly>
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label class="font-weight-bold">Total Labour Amt</label>
                <input type="text" class="form-control bg-light font-weight-bold" value="{{ number_format($unloadingLabourTotal, 2) }}" readonly>
            </div>
        </div>
    </div>

    <!-- Weighbridges Section -->
    <div class="row mt-3">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="header-heading-sepration mb-0" style="flex:1;">Weighbridges</h6>
            </div>
        </div>
    </div>
    <div>
        @if($receivingRequest->weighbridges->count() > 0)
            @foreach($receivingRequest->weighbridges as $index => $wb)
            <div class="row mb-2">
                <div class="col-md-6">
                    <label class="font-weight-bold">Weighbridge Name</label>
                    <input type="text" value="{{ $wb->name }}" class="form-control bg-light" readonly>
                </div>
                <div class="col-md-6">
                    <label class="font-weight-bold">Amount</label>
                    <input type="text" value="{{ number_format($wb->amount, 2) }}" class="form-control bg-light" readonly>
                </div>
            </div>
            @endforeach
        @else
            <div class="row">
                <div class="col-12">
                    <p class="text-muted">No Weighbridges recorded.</p>
                </div>
            </div>
        @endif
    </div>

    @if($receivingRequest->weighbridges->count() > 0)
    <div class="row mt-2">
        <div class="col-md-3">
            <div class="form-group">
                <label class="font-weight-bold">Paid By</label>
                <input type="text" class="form-control bg-light" value="{{ $receivingRequest->weighbridge_paid_by ?? 'N/A' }}" readonly>
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label class="font-weight-bold">Total Weighbridge Amt</label>
                <input type="text" class="form-control bg-light font-weight-bold" value="{{ number_format($receivingRequest->weighbridge_amount, 2) }}" readonly>
            </div>
        </div>
    </div>
    @endif

@php
    $rrModule = $receivingRequest->getApprovalModule();
    $rrApprovalLogs = $rrModule ? \App\Models\ApprovalsModule\ApprovalLog::where('record_id', $receivingRequest->id)->where('module_id', $rrModule->id)->with(['user', 'role'])->orderBy('created_at', 'desc')->get() : collect();
@endphp

<div class="approval-view-wrapper">
    <div class="bottom-button-bar mt-3">
        <x-approval-status :model="$receivingRequest" :list-refresh="route('sales.get.receiving-request.list')"/>
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

@if ($rrApprovalLogs->isNotEmpty())
    <div class="approval-table-wrapper" style="margin-top: 25px; padding-bottom: 10px !important;">
        <div class="card border" style="box-shadow: none; margin-bottom: 0 !important;">
            <div class="card-header bg-light py-2 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 text-dark fw-bold" style="font-size: 14px;">
                    Approval History & Comments
                </h6>
                <span class="badge badge-info">{{ $rrApprovalLogs->count() }} {{ \Illuminate\Support\Str::plural('Action', $rrApprovalLogs->count()) }}</span>
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
                            @foreach ($rrApprovalLogs as $index => $log)
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
</div>
