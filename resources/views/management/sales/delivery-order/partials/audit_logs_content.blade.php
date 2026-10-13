<div class="audit-timeline-container p-3">
    <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
        <div>
            <h6 class="font-weight-bold mb-0 text-dark">
                {{ $title }}
            </h6>
            <small class="text-muted">
                DO #{{ $deliveryOrder->reference_no }}
                @if(isset($deliveryOrder->customer))
                    | Customer: {{ $deliveryOrder->customer->name ?? 'N/A' }}
                @endif
                @if(isset($deliveryOrder->salesOrder))
                    | SO #{{ $deliveryOrder->salesOrder->reference_no }}
                @endif
                @if(isset($deliveryOrder->dispatch_date))
                    | Date: {{ \Carbon\Carbon::parse($deliveryOrder->dispatch_date)->format('d M Y') }}
                @endif
                @if(isset($deliveryOrder->do_status) || isset($deliveryOrder->am_approval_status))
                    | Status: {{ ucfirst($deliveryOrder->do_status ?? $deliveryOrder->am_approval_status) }}
                @endif
            </small>
        </div>
        <div>
            <span class="badge badge-pill badge-primary px-2 py-1">
                {{ count($logs) }} {{ count($logs) == 1 ? 'Record' : 'Records' }}
            </span>
        </div>
    </div>

    @if (count($logs) == 0)
        <div class="text-center py-5">
            <div class="mb-3">
                <i class="fa fa-history fa-3x text-muted opacity-50"></i>
            </div>
            <h6 class="text-muted font-weight-bold">No Audit Logs Found</h6>
            <p class="text-muted small mb-0">
                No edits or updates have been recorded for this delivery order yet.
            </p>
        </div>
    @else
        <div class="audit-timeline">
            @foreach ($logs as $log)
                @php
                    $isDelete = str_contains($log->action, 'deleted');
                    $isCreate = str_contains($log->action, 'create') || str_contains($log->action, 'store');
                    $borderClass = $isDelete ? 'border-danger' : ($isCreate ? 'border-success' : 'border-primary');
                    $badgeClass = $isDelete ? 'badge-danger' : ($isCreate ? 'badge-success' : 'badge-primary');
                    $iconClass = $isDelete ? 'fa-trash' : ($isCreate ? 'fa-plus' : 'fa-pencil');
                @endphp
                <div class="card mb-3 shadow-sm border-left-4 {{ $borderClass }}" style="border-radius: 6px;">
                    <div class="card-header bg-light py-2 px-3 d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center">
                            <span class="avatar-sm rounded-circle bg-white text-dark border p-1 mr-2 d-inline-flex align-items-center justify-content-center" style="width: 28px; height: 28px;">
                                <i class="fa {{ $iconClass }} font-small-2"></i>
                            </span>
                            <div>
                                <span class="font-weight-bold text-dark font-small-3">
                                    {{ $log->user->name ?? 'System' }}
                                </span>
                                <span class="badge {{ $badgeClass }} ml-1 text-uppercase font-small-1">
                                    {{ str_replace('_', ' ', $log->action) }}
                                </span>
                            </div>
                        </div>
                        <div class="text-right">
                            <small class="text-secondary d-block font-small-1">
                                {{ $log->created_at ? $log->created_at->format('d M Y, h:i A') : '' }}
                            </small>
                        </div>
                    </div>
                    <div class="card-body py-2 px-3">
                        @if ($log->description)
                            <p class="text-secondary font-small-3 mb-2 font-italic">
                                <i class="fa fa-info-circle mr-1 text-muted"></i> {{ $log->description }}
                            </p>
                        @endif

                        @if (!empty($log->parsed_changes) && count($log->parsed_changes) > 0)
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered mb-0 font-small-2">
                                    <thead class="bg-light text-muted">
                                        <tr>
                                            <th style="width: {{ $isCreate ? '40%' : '32%' }};">Field</th>
                                            @if (!$isCreate)
                                                <th style="width: 34%;">Old Value</th>
                                                <th style="width: 34%;">New Value</th>
                                            @else
                                                <th style="width: 60%;">Value</th>
                                            @endif
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($log->parsed_changes as $change)
                                            <tr>
                                                <td class="font-weight-bold text-dark bg-white">
                                                    {{ $change['label'] }}
                                                </td>
                                                @if (!$isCreate)
                                                    <td class="text-danger" style="background-color: #fff5f5; word-break: break-all;">
                                                        @if ($change['old'] !== null && $change['old'] !== '')
                                                            <span style="text-decoration: line-through;">{{ $change['old'] }}</span>
                                                        @else
                                                            <span class="text-muted font-italic">null</span>
                                                        @endif
                                                    </td>
                                                @endif
                                                <td class="text-success font-weight-bold" style="background-color: #f0fff4; word-break: break-all;">
                                                    @if ($change['new'] !== null && $change['new'] !== '')
                                                        <span>{{ $change['new'] }}</span>
                                                    @else
                                                        <span class="text-muted font-italic">null</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>

<style>
    .border-left-4 {
        border-left: 4px solid !important;
    }
</style>
