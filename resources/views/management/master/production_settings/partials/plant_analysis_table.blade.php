@if($locationPlants->isEmpty() || $locationPlants->every(fn($l) => $l->plants->isEmpty()))
    <div class="alert alert-light-secondary text-center p-4">
        <i class="ft-info font-large-1 mb-2 text-muted"></i>
        <h5 class="text-muted">No plants configured yet.</h5>
        <p class="font-small-3 text-muted">Please configure Company Locations and Plants first in Master Management.</p>
    </div>
@else
    <form id="bulkPlantSettingsForm" action="{{ route('production-settings.plant-analysis.bulk-update') }}" method="POST">
        @csrf

        @foreach($locationPlants as $location)
            @if($location->plants->isNotEmpty())
                <div class="card location-card mb-4">
                    <div class="location-card-header d-flex justify-content-between align-items-center flex-wrap">
                        <div class="d-flex align-items-center flex-wrap py-1">
                            <span class="location-title-badge mr-2">
                                <i class="ft-map-pin mr-1"></i> {{ $location->name }}
                            </span>
                            <span class="location-meta-tag mr-2">
                                <i class="ft-hash mr-1 text-primary"></i> Code: <strong class="ml-1 text-dark">{{ $location->code ?: '-' }}</strong>
                            </span>
                            <span class="location-meta-tag mr-2">
                                <i class="ft-navigation mr-1 text-info"></i> City: <strong class="ml-1 text-dark">{{ $location->city?->name ?? 'Default' }}</strong>
                            </span>
                            <span class="location-meta-tag mr-2">
                                <i class="ft-activity mr-1 text-success"></i> <strong class="mr-1 text-dark">{{ $location->plants->count() }}</strong> {{ Str::plural('Plant', $location->plants->count()) }}
                            </span>
                        </div>
                        <div class="py-1">
                            <span class="location-id-pill">
                                <i class="ft-shield mr-1"></i> Location ID: #{{ $location->id }}
                            </span>
                        </div>
                    </div>

                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table plant-table table-hover mb-0 align-middle">
                                <thead>
                                    <tr>
                                        <th style="width: 48px;" class="text-center">#</th>
                                        <th style="width: 190px;">Plant Name</th>
                                        <th style="width: 160px;" class="text-center">Machine Status Today</th>
                                        <th style="width: 120px;" class="text-center">Auto Request</th>
                                        <th style="width: 150px;" class="text-center">
                                            Create New Request
                                            <div class="font-small-1 text-muted">Only if last completed</div>
                                        </th>
                                        <th style="width: 220px;" class="text-center col-header-input">
                                            <span class="text-primary font-weight-bold"><i class="ft-arrow-down mr-1"></i>Input Analysis</span>
                                            <div class="font-small-1 text-muted">Min &ndash; Max (Minutes)</div>
                                        </th>
                                        <th style="width: 220px;" class="text-center col-header-output">
                                            <span class="text-success font-weight-bold"><i class="ft-arrow-up mr-1"></i>Output Analysis</span>
                                            <div class="font-small-1 text-muted">Min &ndash; Max (Minutes)</div>
                                        </th>
                                        <th style="width: 220px;">Next Scheduled Request</th>
                                        <th style="width: 80px;" class="text-center">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($location->plants as $pIndex => $plant)
                                        @php
                                            $live = $plantStatuses[$plant->id] ?? [
                                                'is_running' => false,
                                                'has_breakdown' => false,
                                                'status_badge' => 'idle',
                                                'status_label' => 'No Plan',
                                                'badge_class' => 'badge-secondary',
                                                'active_slot' => null,
                                                'reason' => 'No plan for today',
                                                'pending_input' => null,
                                                'pending_output' => null,
                                                'next_input_due' => null,
                                                'next_output_due' => null,
                                            ];
                                        @endphp
                                        <tr id="plant-row-{{ $plant->id }}" class="plant-setting-row" data-plant-id="{{ $plant->id }}">
                                            <td class="text-center font-small-2 text-muted align-middle">
                                                {{ $pIndex + 1 }}
                                            </td>

                                            <td class="align-middle">
                                                <div class="font-weight-bold text-dark font-small-3 plant-title">
                                                    {{ $plant->name }}
                                                </div>
                                                <small class="text-muted font-small-1 d-block mt-1">
                                                    <i class="ft-compass mr-1 text-secondary"></i>{{ $plant->arrivalLocation?->name ?? 'Default Arrival Location' }}
                                                </small>
                                            </td>

                                            <td class="text-center align-middle">
                                                @if($live['has_breakdown'])
                                                    <span class="status-pill status-pill-breakdown" title="Plant under active breakdown">
                                                        <i class="ft-alert-triangle mr-1"></i>Breakdown
                                                    </span>
                                                @elseif($live['is_running'])
                                                    <span class="status-pill status-pill-running" title="Currently running in Machine Plan">
                                                        <span class="live-dot mr-1"></span>Running
                                                    </span>
                                                    @if($live['active_slot'])
                                                        <div class="font-small-1 text-muted mt-1">{{ $live['active_slot'] }}</div>
                                                    @endif
                                                @elseif($live['status_badge'] === 'scheduled')
                                                    <span class="status-pill status-pill-idle" title="{{ $live['reason'] }}">
                                                        <i class="ft-clock mr-1"></i>Plan Idle
                                                    </span>
                                                    <div class="font-small-1 text-muted mt-1">Outside Slot</div>
                                                @else
                                                    <span class="status-pill status-pill-noplan" title="{{ $live['reason'] }}">
                                                        <i class="ft-pause mr-1"></i>No Plan
                                                    </span>
                                                @endif
                                            </td>

                                            <td class="text-center align-middle">
                                                <label class="toggle-switch-custom mb-0" title="Toggle Auto Analysis Generation">
                                                    <input type="checkbox"
                                                           name="plants[{{ $plant->id }}][auto_analysis_enabled]"
                                                           value="1"
                                                           class="plant-toggle-enabled"
                                                           data-plant-id="{{ $plant->id }}"
                                                           {{ $plant->auto_analysis_enabled ? 'checked' : '' }}>
                                                    <span class="toggle-slider-custom"></span>
                                                </label>
                                            </td>

                                            <td class="text-center align-middle">
                                                <label class="toggle-switch-custom mb-0"
                                                       title="ON: create a new request only when the last request of the same type is Completed. OFF: create on schedule even if the last one is pending.">
                                                    <input type="checkbox"
                                                           name="plants[{{ $plant->id }}][require_last_completed]"
                                                           value="1"
                                                           class="plant-require-completed"
                                                           data-plant-id="{{ $plant->id }}"
                                                           {{ $plant->require_last_completed ? 'checked' : '' }}>
                                                    <span class="toggle-slider-custom"></span>
                                                </label>
                                            </td>

                                            <td class="align-middle text-center col-cell-input">
                                                <div class="range-input-box input-box">
                                                    <input type="number"
                                                           min="1"
                                                           max="1440"
                                                           name="plants[{{ $plant->id }}][input_min]"
                                                           value="{{ $plant->getInputMinInterval() }}"
                                                           class="range-num-input plant-input-min"
                                                           placeholder="Min"
                                                           title="Minimum minutes interval for Input Analysis (e.g. 20)">
                                                    <span class="range-sep">&ndash;</span>
                                                    <input type="number"
                                                           min="1"
                                                           max="1440"
                                                           name="plants[{{ $plant->id }}][input_max]"
                                                           value="{{ $plant->getInputMaxInterval() }}"
                                                           class="range-num-input plant-input-max"
                                                           placeholder="Max"
                                                           title="Maximum minutes interval for Input Analysis (e.g. 60)">
                                                    <span class="range-unit">min</span>
                                                </div>
                                            </td>

                                            <td class="align-middle text-center col-cell-output">
                                                <div class="range-input-box output-box">
                                                    <input type="number"
                                                           min="1"
                                                           max="1440"
                                                           name="plants[{{ $plant->id }}][output_min]"
                                                           value="{{ $plant->getOutputMinInterval() }}"
                                                           class="range-num-input plant-output-min"
                                                           placeholder="Min"
                                                           title="Minimum minutes interval for Output Analysis (e.g. 20)">
                                                    <span class="range-sep">&ndash;</span>
                                                    <input type="number"
                                                           min="1"
                                                           max="1440"
                                                           name="plants[{{ $plant->id }}][output_max]"
                                                           value="{{ $plant->getOutputMaxInterval() }}"
                                                           class="range-num-input plant-output-max"
                                                           placeholder="Max"
                                                           title="Maximum minutes interval for Output Analysis (e.g. 60)">
                                                    <span class="range-unit">min</span>
                                                </div>
                                            </td>

                                            <td class="align-middle">
                                                <div class="sched-row mb-1 d-flex align-items-center flex-wrap">
                                                    <span class="tag-analysis-type tag-analysis-input mr-1">Input</span>
                                                    @if($live['pending_input'])
                                                        <span class="sched-badge-pending mr-1 mb-1" title="Pending Analysis Request ({{ $live['pending_input_count'] ?? 1 }} total pending)">
                                                            <i class="ft-alert-circle mr-1"></i>Req #{{ $live['pending_input']->request_no }}
                                                            @if(($live['pending_input_count'] ?? 1) > 1)
                                                                <span class="badge badge-pill badge-warning ml-1" title="{{ $live['pending_input_count'] }} pending requests">{{ $live['pending_input_count'] }}</span>
                                                            @endif
                                                        </span>
                                                    @endif
                                                    @if(!empty($live['is_input_due_now']))
                                                        <span class="sched-badge-due mb-1" style="background-color: #fef3c7; color: #92400e; border: 1px solid #fde68a;" title="Interval reached &ndash; ready to generate on next check">
                                                            <i class="ft-zap mr-1 text-warning"></i>Due Now
                                                        </span>
                                                    @elseif($live['next_input_due'])
                                                        <span class="sched-badge-due mb-1" title="Next scheduled auto request run">
                                                            <i class="ft-clock mr-1"></i>Next: {{ $live['next_input_due'] }}
                                                        </span>
                                                    @elseif(!$live['pending_input'])
                                                        <span class="sched-text-range">
                                                            Between {{ $plant->getInputMinInterval() }}&ndash;{{ $plant->getInputMaxInterval() }}m
                                                        </span>
                                                    @endif
                                                </div>
                                                <div class="sched-row d-flex align-items-center flex-wrap">
                                                    <span class="tag-analysis-type tag-analysis-output mr-1">Output</span>
                                                    @if($live['pending_output'])
                                                        <span class="sched-badge-pending mr-1 mb-1" title="Pending Analysis Request ({{ $live['pending_output_count'] ?? 1 }} total pending)">
                                                            <i class="ft-alert-circle mr-1"></i>Req #{{ $live['pending_output']->request_no }}
                                                            @if(($live['pending_output_count'] ?? 1) > 1)
                                                                <span class="badge badge-pill badge-warning ml-1" title="{{ $live['pending_output_count'] }} pending requests">{{ $live['pending_output_count'] }}</span>
                                                            @endif
                                                        </span>
                                                    @endif
                                                    @if(!empty($live['is_output_due_now']))
                                                        <span class="sched-badge-due mb-1" style="background-color: #fef3c7; color: #92400e; border: 1px solid #fde68a;" title="Interval reached &ndash; ready to generate on next check">
                                                            <i class="ft-zap mr-1 text-warning"></i>Due Now
                                                        </span>
                                                    @elseif($live['next_output_due'])
                                                        <span class="sched-badge-due mb-1" title="Next scheduled auto request run">
                                                            <i class="ft-clock mr-1"></i>Next: {{ $live['next_output_due'] }}
                                                        </span>
                                                    @elseif(!$live['pending_output'])
                                                        <span class="sched-text-range">
                                                            Between {{ $plant->getOutputMinInterval() }}&ndash;{{ $plant->getOutputMaxInterval() }}m
                                                        </span>
                                                    @endif
                                                </div>
                                            </td>

                                            <td class="text-center align-middle">
                                                <div class="d-inline-flex align-items-center">
                                                    <button type="button"
                                                            class="btn-save-row save-single-plant-btn mr-1"
                                                            data-plant-id="{{ $plant->id }}"
                                                            data-url="{{ route('production-settings.plant-analysis.update', $plant->id) }}"
                                                            title="Save settings for {{ $plant->name }}">
                                                        <i class="ft-save font-medium-1"></i>
                                                    </button>
                                                    <button type="button"
                                                            class="btn btn-outline-info btn-sm view-plant-auto-history-btn"
                                                            data-plant-id="{{ $plant->id }}"
                                                            data-plant-name="{{ $plant->name }}"
                                                            title="View auto-generated request history for {{ $plant->name }}">
                                                        <i class="ft-clock font-medium-1"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif
        @endforeach

        <div class="d-flex justify-content-end align-items-center mt-3 pt-3 border-top">
            <button type="submit" class="btn btn-primary px-4 py-2 font-medium-1 shadow-sm" id="bulkSavePlantSettingsBtn" style="border-radius: 6px;">
                <i class="ft-check-circle mr-1"></i> Save All Plant Settings
            </button>
        </div>
    </form>
@endif
