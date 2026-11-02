<style>
    /* Chrome, Safari, Edge, Opera */
    input[type=number]::-webkit-outer-spin-button,
    input[type=number]::-webkit-inner-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }

    /* Firefox */
    input[type=number] {
        -moz-appearance: textfield;
    }

    .spacing-table td {
        padding-top: 15px !important;
        padding-bottom: 15px !important;
    }
</style>

<input type="hidden" id="listRefresh" value="{{ route('get.export-soda-field') }}" />

@if(in_array(strtolower($exportSodaField->am_approval_status ?? ''), ['approved', 'rejected']))
    @php
        $isApproved = strtolower($exportSodaField->am_approval_status) === 'approved';
        $alertClass = $isApproved ? 'alert-success' : 'alert-danger';
        $iconClass = $isApproved ? 'fa-check-circle' : 'fa-times-circle';
    @endphp
    <div class="alert {{ $alertClass }} px-3 py-2 mt-2 d-flex align-items-center justify-content-between" style="border-radius: 6px;">
        <div>
            <i class="fa {{ $iconClass }} me-2"></i>
            <strong>Status: {{ ucfirst($exportSodaField->am_approval_status) }}</strong> - This Export Sauda has been finalized. Its status cannot be changed.
        </div>
    </div>
@elseif(strtolower($exportSodaField->am_approval_status ?? '') === 'reverted')
    <div class="alert alert-warning px-3 py-2 mt-2 d-flex align-items-center justify-content-between" style="border-radius: 6px;">
        <div>
            <i class="fa fa-undo me-2"></i>
            <strong>Status: Reverted</strong> - This Export Sauda has been reverted. Please edit and update it to return to pending status.
        </div>
    </div>
@elseif(str_starts_with(strtolower($exportSodaField->am_approval_status ?? ''), 'pending stage'))
    @php
        $stageNum = trim(str_ireplace('pending stage', '', $exportSodaField->am_approval_status));
        $prevStage = is_numeric($stageNum) && (int)$stageNum > 1 ? ((int)$stageNum - 1) : 1;
    @endphp
    <div class="alert alert-warning px-3 py-2 mt-2 d-flex align-items-center justify-content-between" style="border-radius: 6px;">
        <div>
            <i class="fa fa-clock-o me-2"></i>
            <strong>Status: {{ ucwords($exportSodaField->am_approval_status) }}</strong> - Stage {{ $prevStage }} approved. Awaiting {{ ucwords($exportSodaField->am_approval_status) }} review.
        </div>
    </div>
@endif

<div class="row form-mar">
    <div class="col-8">
        {{-- ====== BUYER & INFO ====== --}}
        <div class="col-md-12">
            <h6 class="header-heading-sepration">Buyer & Information</h6>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Reference #:</label>
                        <input type="text" class="form-control" value="{{ $exportSodaField->reference }}" readonly>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Buyer's Name:</label>
                        <select class="form-control select2" disabled>
                            <option value="">Select Buyer</option>
                            @foreach ($users as $user)
                                <option value="{{ $user->id }}" {{ $exportSodaField->buyer_id == $user->id ? 'selected' : '' }}>
                                    {{ $user->name }} ({{ ucfirst($user->type) }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Product / Commodity:</label>
                        <select class="form-control select2" disabled>
                            <option value="">Select Product</option>
                            @foreach ($products as $product)
                                <option value="{{ $product->id }}" {{ $exportSodaField->product_id == $product->id ? 'selected' : '' }}>{{ $product->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Shipment Period:</label>
                        <input type="text" class="form-control" value="{{ ($exportSodaField->shipment_date_from ? $exportSodaField->shipment_date_from->format('d-M-Y') : 'N/A') . ' to ' . ($exportSodaField->shipment_date_to ? $exportSodaField->shipment_date_to->format('d-M-Y') : 'N/A') }}" readonly>
                    </div>
                </div>
            </div>
        </div>

        {{-- ====== COMMISSION ====== --}}
        <div class="col-md-12 mt-4">
            <h6 class="header-heading-sepration">Commission</h6>
            @php
                $totalMt = $exportSodaField->packingItems->sum('metric_tons');
                $totalAmount = $exportSodaField->packingItems->sum('amount');
                $commission = $exportSodaField->commission ?? 0;
                $commissionPercentage = $totalAmount > 0 ? ($commission / $totalAmount) * 100 : 0;
                $amtPerTon = $totalMt > 0 ? ($commission / $totalMt) : 0;
            @endphp
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Commission (%):</label>
                        <input type="text" class="form-control" value="{{ number_format($commissionPercentage, 2) }}%" readonly>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Amt/Ton:</label>
                        <input type="text" class="form-control" value="{{ number_format($amtPerTon, 2) }}" readonly>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Total Commission:</label>
                        <input type="text" class="form-control" value="{{ number_format($commission, 2) }}" readonly>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-4">
        {{-- ====== EXPORT DETAILS SIDEBAR ====== --}}
        <h6 class="header-heading-sepration">Export Details</h6>
        <div class="table-responsive">
            <table class="table table-bordered spacing-table" style="margin-bottom:0;">
                <tr>
                    <td style="width:40%;font-weight:bold;vertical-align:middle;">INCO TERM</td>
                    <td>
                        <select class="form-control select2" disabled>
                            @foreach ($incoterms as $incoterm)
                                <option value="{{ $incoterm->id }}" {{ $exportSodaField->incoterm_id == $incoterm->id ? 'selected' : '' }}>{{ $incoterm->name }}</option>
                            @endforeach
                        </select>
                    </td>
                </tr>
                <tr>
                    <td style="font-weight:bold;vertical-align:middle;">PAYMENT TERM</td>
                    <td>
                        <select class="form-control select2" disabled>
                            @foreach ($modeofterms as $term)
                                <option value="{{ $term->id }}" {{ $exportSodaField->mode_of_term_id == $term->id ? 'selected' : '' }}>{{ $term->name }}</option>
                            @endforeach
                        </select>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    {{-- ====== PACKING DETAILS (Full Width) ====== --}}
    <div class="col-12 mt-4">
        <h6 class="header-heading-sepration">Packing Details</h6>
        <div class="table-responsive">
            <table class="table table-bordered mb-0">
                <thead>
                    <tr>
                        <th>Bag Type</th>
                        <th>Packing</th>
                        <th>Packing Size (kg)</th>
                        <th>Qty (MT)</th>
                        <th style="display: none;">Qty (Mnds)</th>
                        <th>Bags</th>
                        <th>Rate/Ton</th>
                        <th style="display: none;">Rate/Mnd</th>
                        <th>Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($exportSodaField->packingItems as $item)
                        <tr>
                            <td class="p-2"><input type="text" class="form-control" value="{{ $item->bagType->name ?? 'N/A' }}" readonly></td>
                            <td class="p-2"><input type="text" class="form-control" value="{{ $item->bagPacking->name ?? 'N/A' }}" readonly></td>
                            <td class="p-2"><input type="text" class="form-control" value="{{ $item->bag_size }}" readonly></td>
                            <td class="p-2"><input type="text" class="form-control" value="{{ number_format($item->metric_tons, 3) }}" readonly></td>
                            <td class="p-2" style="display: none;"><input type="text" class="form-control" value="{{ number_format($item->maunds, 2) }}" readonly></td>
                            <td class="p-2"><input type="text" class="form-control" value="{{ number_format($item->no_of_bags, 0) }}" readonly></td>
                            <td class="p-2"><input type="text" class="form-control" value="{{ number_format($item->rate, 2) }}" readonly></td>
                            <td class="p-2" style="display: none;"><input type="text" class="form-control" value="{{ number_format($item->rate_per_maund, 2) }}" readonly></td>
                            <td class="p-2"><input type="text" class="form-control" value="{{ number_format($item->amount, 2) }}" readonly></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            <h6 class="header-heading-sepration">Additional Information</h6>
            <textarea class="form-control" rows="4" readonly>{{ $exportSodaField->additional_info }}</textarea>
        </div>
    </div>
</div>

<div class="approval-view-wrapper mt-3">
    <div class="row">
        <div class="col-12">
            <x-approval-status :model="$exportSodaField" :list-refresh="route('get.export-soda-field')" />
        </div>
    </div>
</div>

@php
    $module = $exportSodaField->getApprovalModule();
    $approvalLogs = $module ? \App\Models\ApprovalsModule\ApprovalLog::where('record_id', $exportSodaField->id)->where('module_id', $module->id)->with(['user', 'role'])->orderBy('created_at', 'desc')->get() : collect();
@endphp

@if ($approvalLogs->isNotEmpty())
    <div class="approval-table-wrapper" style="margin-top: 25px; padding-bottom: 10px !important;">
        <div class="card border" style="box-shadow: none; margin-bottom: 0 !important;">
            <div class="card-header bg-light py-2 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 text-dark fw-bold" style="font-size: 14px;">
                    Approval History & Comments
                </h6>
                <span class="badge badge-info">{{ $approvalLogs->count() }} {{ \Illuminate\Support\Str::plural('Action', $approvalLogs->count()) }}</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped mb-0" style="font-size: 13px;">
                        <thead class="thead-light">
                            <tr>
                                <th style="width: 5%;">#</th>
                                <th style="width: 20%;">User</th>
                                <th style="width: 20%;">Role</th>
                                <th style="width: 15%;">Action</th>
                                <th style="width: 25%;">Comments</th>
                                <th style="width: 15%;">Date & Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($approvalLogs as $index => $log)
                                @php
                                    $badgeClass = match($log->action) {
                                        'approved' => 'badge-success',
                                        'rejected' => 'badge-danger',
                                        'reverted' => 'badge-warning',
                                        default => 'badge-secondary'
                                    };
                                @endphp
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $log->user->name ?? 'N/A' }}</td>
                                    <td>{{ $log->role->name ?? 'N/A' }}</td>
                                    <td>
                                        <span class="badge {{ $badgeClass }} text-uppercase">
                                            {{ $log->action }}
                                        </span>
                                    </td>
                                    <td>{{ $log->comments ?: '-' }}</td>
                                    <td>{{ $log->created_at ? $log->created_at->format('d M, Y h:i A') : 'N/A' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endif

<div class="row bottom-button-bar">
    <div class="col-12 mb-3 text-right">
        <a type="button" class="btn btn-danger modal-sidebar-close position-relative top-1 closebutton">Close</a>
    </div>
</div>

<script>
    $(document).ready(function() {
        $('.select2').select2({ width: '100%' });
    });
</script>
