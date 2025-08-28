@if ($module)
    @php
        $user = auth()->user();
        $userAlreadyApproved = false;
        $userAlreadyRejected = false;
        $userActions = $model->approvalLogs()->where('user_id', $user->id)->get();
        $userAlreadyApproved = $userActions->where('action', 'approved')->isNotEmpty();
        $userAlreadyRejected = $userActions->where('action', 'rejected')->isNotEmpty();
        $userAlreadyActed = $userAlreadyApproved;
        $changesRequired = $model->am_change_made == 0;
        $currentApprovals = $model->getCurrentApprovals();
    @endphp

    @if ($model->{$module->approval_column} === 'rejected' || $model->am_change_made == 0)
        <div class="alert alert-warning border-start border-warning border-3 mb-4">
            <div class="d-flex align-items-center">
                <i class="fa  fa-exclamation-triangle me-3 text-warning"></i>
                <div>
                    <strong>Administrative Notice</strong><br>
                    @if ($model->am_change_made == 0)
                        This document requires modifications before it can be resubmitted for approval
                        consideration.
                    @else
                        This document has been declined. All previous approvals have been reset and the
                        workflow must restart.
                    @endif
                </div>
            </div>
        </div>
    @endif

    <div class="d-flex w-100 mb-3" style="gap:12px;">
        @foreach ($module->roles as $index => $moduleRole)
            @php
                $role = $moduleRole->role;
                $currentCount = $currentApprovals[$role->id] ?? 0;
                $requiredCount = $moduleRole->approval_count;
                $isComplete = $currentCount >= $requiredCount;

                $approvers = $model
                    ->approvalLogs()
                    ->where('role_id', $role->id)
                    ->where('action', 'approved')
                    ->with('user')
                    ->get();
            @endphp

            @if (isset($model->createdBy))
                <div class="dash-item flex-fill">
                    <div class="dash-value d-flex flex-column text-uppercase text-center">
                        <strong>{{ $model->createdBy->name }}</strong>
                        <small style="font-size: 10px;">&nbsp;</small>
                    </div>
                    <div class="dash-line">______________________</div>
                    <div class="approver-name mt-1">
                        Prepared By
                    </div>
                </div>
            @endif

            @for ($i = 1; $i <= $requiredCount; $i++)
                <div class="dash-item flex-fill">
                    <div class="dash-value d-flex flex-column text-uppercase text-center">
                        @if ($i <= $currentCount && isset($approvers[$i - 1]))
                            <strong>{{ $approvers[$i - 1]->user->name }}</strong>
                        @else
                            <span>&nbsp;</span>
                        @endif
                        <small style="font-size: 10px;">({{ $role->name }})</small>
                    </div>
                    <div class="dash-line">______________________</div>
                    <div class="approver-name mt-1">
                        Approved By
                    </div>
                </div>
            @endfor
        @endforeach
    </div>


    @if ($model->canApprove() && !$userAlreadyActed && !$changesRequired)
        <div class="row g-3">
            <div class="col-md-6">
                <div class="action-form">
                    <form id="ajaxSubmit"
                        action="{{ route('approval.approve', ['modelType' => class_basename($model), 'id' => $model->id]) }}"
                        method="POST">
                        @csrf
                        <input type="hidden" name="class" value="{{ class_basename($model) }}">
                        <input type="hidden" name="mc" value="{{ $module->id }}">
                        <input type="hidden" name="id" value="{{ $model->id }}">

                        <div class="mb-3">
                            <label for="approval_comments" class="form-label fw-medium">Approval Comments
                                <span class="text-muted">(Optional)</span></label>
                            <textarea name="comments" id="approval_comments" class="form-control" rows="3"
                                placeholder="Enter your approval remarks or observations..."></textarea>
                        </div>

                        <button type="submit" class="btn btn-success w-100 fw-semibold">
                            <i class="fa  fa-check me-2"></i>
                            Grant Approval
                        </button>
                    </form>
                </div>
            </div>

            <div class="col-md-6">
                <div class="action-form">
                    <form id="ajaxSubmit"
                        action="{{ route('approval.reject', ['modelType' => class_basename($model), 'id' => $model->id]) }}"
                        method="POST">
                        @csrf
                        <input type="hidden" name="class" value="{{ class_basename($model) }}">
                        <input type="hidden" name="mc" value="{{ $module->id }}">
                        <input type="hidden" name="id" value="{{ $model->id }}">

                        <div class="mb-3">
                            <label for="rejection_comments" class="form-label fw-medium">Decline Reason
                                <span class="text-danger">*</span></label>
                            <textarea name="comments" id="rejection_comments" class="form-control" rows="3"
                                placeholder="Please provide detailed reasons for declining this request..." required></textarea>
                        </div>

                        <button type="submit" class="btn btn-danger w-100 fw-semibold">
                            <i class="fa fa-times me-2"></i>
                            Decline Request
                        </button>
                    </form>
                </div>
            </div>
        </div>
    @elseif($model->getApprovalStatus() === 'pending')
        <div class="alert alert-info border-start border-info border-3">
            <div class="d-flex align-items-center">
                <i class="fa  fa-info-circle me-3 text-info"></i>
                <div>
                    @if ($changesRequired)
                        <strong>Document Status:</strong> This document requires revisions. Please update
                        the record and save your changes before resubmitting for approval.
                    @elseif ($userAlreadyActed)
                        <strong>Action Completed:</strong> You have already
                        {{ $userAlreadyApproved ? 'granted approval for' : 'declined' }} this document.
                    @elseif ($module->requires_sequential_approval)
                        <strong>Workflow Status:</strong> This document is awaiting completion of
                        prerequisite approval steps before your review.
                    @else
                        <strong>Access Restricted:</strong> You do not have the necessary permissions to
                        approve this document.
                    @endif
                </div>
            </div>
        </div>
    @endif

    <div class="approval-history mt-4" style="display: none;">
        @if ($model->approvalLogs->isNotEmpty())
            <h6 class="mb-3 text-dark fw-semibold">
                <i class="fa  fa-history me-2 text-primary"></i>
                Approval Audit Trail
            </h6>

            <div class="history-list">
                @foreach ($model->approvalLogs->sortByDesc('created_at') as $log)
                    <div class="history-item {{ $log->user_id === auth()->id() ? 'user-action' : '' }}">
                        <div class="history-icon">
                            <i
                                class="fa  fa-{{ $log->action === 'approved' ? 'check' : 'times' }} text-{{ $log->action === 'approved' ? 'success' : 'danger' }}"></i>
                        </div>

                        <div class="history-details">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <strong>{{ $log->user->name }}</strong>
                                    @if ($log->user_id === auth()->id())
                                        <span class="badge bg-primary ms-1">You</span>
                                    @endif
                                    <span class="badge bg-light text-dark ms-1">{{ $log->role->name }}</span>
                                </div>
                                <small class="text-muted">{{ $log->created_at->format('M d, Y H:i') }}</small>
                            </div>

                            <div class="mt-1">
                                <span class="badge bg-{{ $log->action === 'approved' ? 'success' : 'danger' }}">
                                    {{ $log->action === 'approved' ? 'Approved' : 'Declined' }}
                                </span>
                            </div>

                            @if ($log->comments)
                                <div class="mt-2">
                                    <small class="text-muted fst-italic">"{{ $log->comments }}"</small>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <style>
        .header-heading-sepration {
            border-bottom: 2px solid #e9ecef;
            padding-bottom: 10px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .role-container {
            margin-bottom: 25px;
        }

        .role-title {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 15px;
            font-size: 1.1rem;
        }

        .dash-container {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 10px;
        }

        .dash-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            min-width: 150px;
        }

        .dash-line {
            font-size: 16px;
            color: #6c757d;
            margin-bottom: 5px;
            font-family: monospace;
            line-height: 0px
        }

        .approver-name {
            font-size: 14px;
            color: #495057;
            text-align: center;
            min-height: 20px;
        }

        .action-form {
            background: #ffffff;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 24px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
        }

        @media (max-width: 768px) {
            .dash-container {
                flex-direction: column;
                gap: 10px;
            }

            .dash-item {
                width: 100%;
            }

            .action-form {
                padding: 18px;
            }
        }
    </style>
@endif
