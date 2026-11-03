<div class="auto-history-container">
    {{-- Modern Light Filter Bar --}}
    <div class="auto-history-filter-card mb-4">
        <form id="autoHistoryFilterForm" class="row align-items-end g-3">
            <div class="col-lg-3 col-md-6 mb-2 mb-lg-0">
                <label class="form-label">
                    <i class="ft-calendar mr-1 text-primary"></i> Date Range
                </label>
                <input type="text"
                       class="form-control"
                       id="historyDateRange"
                       name="date_range"
                       placeholder="YYYY-MM-DD - YYYY-MM-DD"
                       value="{{ request('date_range') ?: (now()->subDays(6)->format('Y-m-d') . ' - ' . now()->format('Y-m-d')) }}">
            </div>

            <div class="col-lg-2 col-md-6 mb-2 mb-lg-0">
                <label class="form-label">
                    <i class="ft-filter mr-1 text-info"></i> Analysis Type
                </label>
                <select name="type" id="historyTypeFilter" class="form-control">
                    <option value="">All Types</option>
                    <option value="production-input-analysis" {{ request('type') == 'production-input-analysis' ? 'selected' : '' }}>Input Analysis</option>
                    <option value="production-output-analysis" {{ request('type') == 'production-output-analysis' ? 'selected' : '' }}>Output Analysis</option>
                </select>
            </div>

            <div class="col-lg-2 col-md-6 mb-2 mb-lg-0">
                <label class="form-label">
                    <i class="ft-check-circle mr-1 text-success"></i> Status
                </label>
                <select name="status" id="historyStatusFilter" class="form-control">
                    <option value="">All Statuses</option>
                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="in_progress" {{ request('status') == 'in_progress' ? 'selected' : '' }}>In Progress</option>
                    <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                    <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                </select>
            </div>

            <div class="col-lg-3 col-md-6 mb-2 mb-lg-0">
                <label class="form-label">
                    <i class="ft-search mr-1 text-secondary"></i> Search
                </label>
                <input type="text"
                       class="form-control"
                       id="historySearch"
                       name="search"
                       value="{{ request('search') }}"
                       placeholder="Request #, Plant, Job Order...">
            </div>

            <input type="hidden" name="plant_id" id="historyPlantId" value="{{ request('plant_id') }}">

            <div class="col-lg-2 col-md-12 d-flex align-items-center">
                <button type="submit" class="btn btn-filter-submit flex-grow-1 mr-2" id="filterAutoHistoryBtn">
                    <i class="ft-filter mr-1"></i> Filter
                </button>
                <button type="button" class="btn btn-filter-reset" id="resetAutoHistoryBtn" title="Reset Filters">
                    <i class="ft-rotate-ccw"></i>
                </button>
            </div>
        </form>

        @if(request('plant_id'))
            <div class="mt-2 pt-2 border-top d-flex align-items-center justify-content-between">
                <span class="font-small-2 text-info">
                    <i class="ft-info mr-1"></i> Filtering exclusively by Plant ID: <strong>#{{ request('plant_id') }}</strong>
                </span>
                <button type="button" class="btn btn-link btn-sm text-danger p-0 font-small-2" onclick="$('#historyPlantId').val(''); reloadAutoHistory();">
                    <i class="ft-x mr-1"></i> Clear Plant Filter
                </button>
            </div>
        @endif
    </div>

    {{-- History Table --}}
    @if($historyRequests->isEmpty())
        <div class="alert alert-light-secondary text-center p-4 border rounded" style="background: #ffffff; border: 1px solid #e2e8f0;">
            <i class="ft-clock font-large-1 mb-2 text-muted"></i>
            <h5 class="text-muted font-weight-bold">No auto-generated analysis requests found.</h5>
            <p class="font-small-3 text-muted mb-0">
                Requests will appear here as soon as they are auto-generated based on running machine plans and configured intervals.
            </p>
        </div>
    @else
        <div class="table-responsive bg-white rounded border" style="box-shadow: 0 1px 3px rgba(0,0,0,0.04); border-color: #e2e8f0 !important;">
            <table class="table auto-history-table mb-0 align-middle">
                <thead>
                    <tr>
                        <th style="width: 48px;" class="text-center">#</th>
                        <th style="width: 210px;">Request No</th>
                        <th style="width: 190px;">Auto Generated At</th>
                        <th style="width: 160px;">Analysis Type</th>
                        <th style="width: 220px;">Plant &amp; Location</th>
                        <th style="width: 190px;">Machine Plan Slot</th>
                        <th style="width: 130px;" class="text-center">Interval Picked</th>
                        <th style="width: 120px;" class="text-center">Status</th>
                        <th style="width: 100px;" class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($historyRequests as $idx => $req)
                        @php
                            $genTime = $req->auto_generated_at ?: $req->created_at;
                            $isInput = ($req->type === \App\Models\Production\ProductionAnalysisRequest::TYPE_INPUT);
                            $isOutput = ($req->type === \App\Models\Production\ProductionAnalysisRequest::TYPE_OUTPUT);
                        @endphp
                        <tr>
                            <td class="text-center font-small-2 text-muted align-middle font-weight-bold">
                                {{ $historyRequests->firstItem() + $idx }}
                            </td>

                            <td class="align-middle">
                                <div class="req-wrapper">
                                    <a href="javascript:void(0);"
                                       onclick="openModal(this, '{{ route('production-analysis-request.show', $req->id) }}', 'View Analysis Request #{{ $req->request_no }}', false, '80%')"
                                       class="req-link"
                                       title="Click to view details">
                                        <i class="ft-file-text mr-1"></i>{{ $req->request_no }}
                                    </a>
                                    @if($req->jobOrder)
                                        <div class="req-sub">
                                            <i class="ft-briefcase mr-1 text-secondary"></i>JO: <span>{{ $req->jobOrder->job_order_no }}</span>
                                        </div>
                                    @elseif($req->productionVoucher)
                                        <div class="req-sub">
                                            <i class="ft-layers mr-1 text-secondary"></i>PV: <span>{{ $req->productionVoucher->voucher_no }}</span>
                                        </div>
                                    @endif
                                </div>
                            </td>

                            <td class="align-middle">
                                <div class="datetime-wrapper">
                                    <span class="datetime-date">
                                        <i class="ft-calendar mr-1"></i>{{ $genTime ? $genTime->format('Y-m-d') : '-' }}
                                    </span>
                                    <span class="datetime-time">
                                        <i class="ft-clock mr-1"></i>{{ $genTime ? $genTime->format('h:i:s A') : '-' }}
                                    </span>
                                    @if($genTime)
                                        <span class="datetime-ago">
                                            ({{ $genTime->diffForHumans() }})
                                        </span>
                                    @endif
                                </div>
                            </td>

                            <td class="align-middle">
                                @if($isInput)
                                    <span class="type-badge type-badge-input">
                                        <i class="ft-arrow-down mr-1"></i>Input Analysis
                                    </span>
                                @elseif($isOutput)
                                    <span class="type-badge type-badge-output">
                                        <i class="ft-arrow-up mr-1"></i>Output Analysis
                                    </span>
                                @else
                                    <span class="badge badge-secondary px-2 py-1 font-small-2 font-weight-bold">
                                        {{ $req->type_name }}
                                    </span>
                                @endif
                            </td>

                            <td class="align-middle">
                                <div class="font-weight-bold text-dark font-small-3" style="color: #1e293b;">
                                    <i class="ft-cpu mr-1 text-info"></i>{{ $req->plant?->name ?? 'Plant #' . $req->plant_id }}
                                </div>
                                <div class="font-small-1 text-muted mt-1" style="line-height: 1.3;">
                                    <i class="ft-map-pin mr-1 text-secondary"></i>{{ $req->companyLocation?->name ?? '-' }}
                                    @if($req->companyLocation?->city)
                                        <span class="text-secondary">({{ $req->companyLocation->city->name }})</span>
                                    @endif
                                </div>
                            </td>

                            <td class="align-middle">
                                @if($req->machinePlanSetting)
                                    <div class="font-small-2 font-weight-bold" style="color: #334155;">
                                        <i class="ft-calendar mr-1 text-warning"></i>Plan #{{ $req->machinePlanSetting->id }}
                                    </div>
                                    @php
                                        $enabledItems = $req->machinePlanSetting->items?->where('is_enabled', true);
                                        $activeSlot = $enabledItems?->first();
                                    @endphp
                                    @if($activeSlot && $activeSlot->start_time && $activeSlot->end_time)
                                        <div class="font-small-1 text-muted mt-1" style="line-height: 1.3;">
                                            <i class="ft-clock mr-1 text-secondary"></i>Slot: <strong>{{ $activeSlot->start_time }} &ndash; {{ $activeSlot->end_time }}</strong>
                                        </div>
                                    @elseif($activeSlot && $activeSlot->start_time)
                                        <div class="font-small-1 text-muted mt-1" style="line-height: 1.3;">
                                            <i class="ft-clock mr-1 text-secondary"></i>From: <strong>{{ $activeSlot->start_time }}</strong>
                                        </div>
                                    @else
                                        <div class="font-small-1 text-muted mt-1">Active Machine Plan</div>
                                    @endif
                                @else
                                    <span class="font-small-2 text-muted">&ndash;</span>
                                @endif
                            </td>

                            <td class="text-center align-middle">
                                @if($req->auto_scheduled_interval)
                                    <span class="interval-pill" title="Interval duration chosen by random generator">
                                        <i class="ft-zap mr-1"></i>+{{ $req->auto_scheduled_interval }} min
                                    </span>
                                @else
                                    <span class="text-muted font-small-2">&ndash;</span>
                                @endif
                            </td>

                            <td class="text-center align-middle">
                                @if($req->status === 'pending')
                                    <span class="badge-status-pending">
                                        <i class="ft-clock mr-1"></i>Pending
                                    </span>
                                @elseif($req->status === 'completed')
                                    <span class="badge-status-completed">
                                        <i class="ft-check mr-1"></i>Completed
                                    </span>
                                @elseif($req->status === 'in_progress')
                                    <span class="badge badge-info px-2 py-1 font-small-1 font-weight-bold text-uppercase" style="background-color: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; border-radius: 20px;">
                                        <i class="ft-loader mr-1"></i>In Progress
                                    </span>
                                @else
                                    <span class="badge badge-secondary px-2 py-1 font-small-1 font-weight-bold text-uppercase" style="border-radius: 20px;">
                                        {{ ucfirst($req->status) }}
                                    </span>
                                @endif
                            </td>

                            <td class="text-center align-middle">
                                <div class="d-inline-flex align-items-center">
                                    <button type="button"
                                            class="btn-action-view mr-1"
                                            onclick="openModal(this, '{{ route('production-analysis-request.show', $req->id) }}', 'View Analysis Request #{{ $req->request_no }}', false, '80%')"
                                            title="View Request Details">
                                        <i class="ft-eye"></i>
                                    </button>

                                    @if($req->status === 'pending')
                                        @if($isInput)
                                            <a href="{{ route('production-input-analysis.index') }}"
                                               class="btn-action-perform"
                                               title="Perform Input Analysis">
                                                <i class="ft-edit-2"></i>
                                            </a>
                                        @elseif($isOutput)
                                            <a href="{{ route('production-output-analysis.index') }}"
                                               class="btn-action-perform"
                                               title="Perform Output Analysis">
                                                <i class="ft-edit-2"></i>
                                            </a>
                                        @endif
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap">
            <div class="font-small-2 text-muted mb-2 mb-md-0">
                <i class="ft-list mr-1"></i>Showing <strong>{{ $historyRequests->firstItem() }}</strong> to <strong>{{ $historyRequests->lastItem() }}</strong> of <strong>{{ $historyRequests->total() }}</strong> auto-generated requests
            </div>
            <div class="auto-history-pagination">
                {!! $historyRequests->links('pagination::bootstrap-4') !!}
            </div>
        </div>
    @endif
</div>

<style>
    /* Filter Card */
    .auto-history-filter-card {
        background: #ffffff !important;
        border: 1px solid #e2e8f0 !important;
        border-radius: 10px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        padding: 18px 20px;
    }
    .auto-history-filter-card .form-label {
        color: #475569 !important;
        font-size: 11px !important;
        font-weight: 700 !important;
        text-transform: uppercase !important;
        letter-spacing: 0.5px;
        margin-bottom: 6px;
        display: flex;
        align-items: center;
    }
    .auto-history-filter-card .form-control {
        background-color: #ffffff !important;
        border: 1px solid #cbd5e1 !important;
        color: #1e293b !important;
        border-radius: 6px !important;
        font-size: 13px !important;
        height: 38px !important;
        box-shadow: none !important;
        transition: all 0.15s ease;
    }
    .auto-history-filter-card .form-control:focus {
        border-color: #0284c7 !important;
        box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.15) !important;
    }
    .btn-filter-submit {
        background: #0284c7 !important;
        border-color: #0284c7 !important;
        color: #ffffff !important;
        height: 38px;
        font-weight: 600;
        font-size: 13px;
        border-radius: 6px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition: all 0.15s ease;
    }
    .btn-filter-submit:hover {
        background: #0369a1 !important;
        border-color: #0369a1 !important;
        color: #ffffff !important;
    }
    .btn-filter-reset {
        height: 38px;
        width: 38px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 6px;
        border: 1px solid #cbd5e1 !important;
        color: #64748b !important;
        background: #ffffff !important;
        transition: all 0.15s ease;
    }
    .btn-filter-reset:hover {
        background: #f1f5f9 !important;
        color: #1e293b !important;
    }

    /* Table & Row Hover */
    .auto-history-table {
        border-collapse: separate;
        border-spacing: 0;
        width: 100%;
        margin-bottom: 0;
    }
    .auto-history-table th {
        background-color: #f8fafc !important;
        color: #475569 !important;
        font-size: 11px !important;
        font-weight: 700 !important;
        text-transform: uppercase !important;
        letter-spacing: 0.5px;
        padding: 12px 14px;
        border-top: none !important;
        border-bottom: 2px solid #e2e8f0 !important;
        vertical-align: middle;
    }
    .auto-history-table td {
        padding: 13px 14px;
        vertical-align: middle !important;
        border-top: 1px solid #f1f5f9;
        background: #ffffff;
    }
    .auto-history-table tbody tr:hover td {
        background-color: #f8fafc !important;
    }

    /* Datetime Cell */
    .datetime-wrapper {
        display: flex;
        flex-direction: column;
        gap: 2px;
        line-height: 1.35;
    }
    .datetime-date {
        color: #1e293b;
        font-weight: 700;
        font-size: 13px;
        display: flex;
        align-items: center;
    }
    .datetime-date i {
        color: #0284c7;
        font-size: 12px;
    }
    .datetime-time {
        color: #0284c7;
        font-weight: 600;
        font-size: 12px;
        display: flex;
        align-items: center;
    }
    .datetime-time i {
        color: #0ea5e9;
        font-size: 11px;
    }
    .datetime-ago {
        color: #94a3b8;
        font-size: 11px;
        font-weight: 500;
    }

    /* Request Cell */
    .req-wrapper {
        display: flex;
        flex-direction: column;
        gap: 2px;
        line-height: 1.35;
    }
    .req-link {
        color: #0284c7;
        font-size: 13px;
        font-weight: 700;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
    }
    .req-link:hover {
        color: #0369a1;
        text-decoration: underline;
    }
    .req-sub {
        color: #64748b;
        font-size: 11px;
        font-weight: 500;
    }
    .req-sub span {
        color: #1e293b;
        font-weight: 600;
    }

    /* Type Badges */
    .type-badge {
        display: inline-flex;
        align-items: center;
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 0.2px;
        line-height: 1.2;
    }
    .type-badge-input {
        background-color: #e0f2fe !important;
        color: #0369a1 !important;
        border: 1px solid #bae6fd !important;
    }
    .type-badge-output {
        background-color: #dcfce7 !important;
        color: #15803d !important;
        border: 1px solid #bbf7d0 !important;
    }

    /* Interval Pill */
    .interval-pill {
        display: inline-flex;
        align-items: center;
        background-color: #f3e8ff !important;
        color: #7e22ce !important;
        border: 1px solid #e9d5ff !important;
        font-weight: 700;
        font-size: 11px;
        padding: 4px 10px;
        border-radius: 20px;
    }

    /* Status Badges */
    .badge-status-pending {
        display: inline-flex;
        align-items: center;
        background-color: #fef3c7 !important;
        color: #b45309 !important;
        border: 1px solid #fde68a !important;
        font-weight: 700;
        font-size: 11px;
        padding: 4px 12px;
        border-radius: 20px;
        text-transform: uppercase;
        letter-spacing: 0.4px;
    }
    .badge-status-completed {
        display: inline-flex;
        align-items: center;
        background-color: #d1fae5 !important;
        color: #047857 !important;
        border: 1px solid #a7f3d0 !important;
        font-weight: 700;
        font-size: 11px;
        padding: 4px 12px;
        border-radius: 20px;
        text-transform: uppercase;
        letter-spacing: 0.4px;
    }

    /* Action Buttons */
    .btn-action-view {
        background: #f0f9ff !important;
        color: #0284c7 !important;
        border: 1px solid #bae6fd !important;
        width: 30px;
        height: 30px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 6px;
        cursor: pointer;
        transition: all 0.15s ease;
    }
    .btn-action-view:hover {
        background: #0284c7 !important;
        color: #ffffff !important;
        border-color: #0284c7 !important;
    }
    .btn-action-perform {
        background: #f0fdf4 !important;
        color: #16a34a !important;
        border: 1px solid #bbf7d0 !important;
        width: 30px;
        height: 30px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 6px;
        text-decoration: none;
        cursor: pointer;
        transition: all 0.15s ease;
    }
    .btn-action-perform:hover {
        background: #16a34a !important;
        color: #ffffff !important;
        border-color: #16a34a !important;
    }
</style>
