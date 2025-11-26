@if ($module)
    @php
        $user = auth()->user();
        $userAlreadyApproved = false;
        $userAlreadyRejected = false;
        $userActions = $model->approvalLogs()->where('user_id', $user->id)->where('module_id', $module->id)->get();
        // dd(class_basename($module->id));
        $userAlreadyApproved = $userActions->where('action', 'approved')->where('status', 'active')->isNotEmpty();
        $userAlreadyRejected = $userActions->where('action', 'rejected')->where('status', 'active')->isNotEmpty();
        $userAlreadyActed = $userAlreadyApproved;
        $changesRequired = $model->am_change_made == 0;
        $currentApprovals = $model->getCurrentApprovals();
        $approvalCycles = $model->approvalRows()->orderBy('approval_cycle', 'desc')->get()->groupBy('approval_cycle');

    @endphp


    @if ($model->{$module->approval_column} === 'reverted' || $model->am_change_made == 0)
        <div class="alert alert-primary border-start border-primary border-3 mb-4">
            <div class="d-flex align-items-center">
                <i class="fa fa-exclamation-triangle me-3 text-primary"></i>
                <div>
                    @php
                        $latestLog = $model->approvalLogs()->latest()->first();
                    @endphp
                    <strong>Approval Authority Comments</strong><br>
                    @if ($model->am_change_made == 0)
                        <div class="small mb-1">
                            <strong>{{ $latestLog->user->name ?? 'N/A' }}</strong>
                            <span class="">({{ $latestLog->role->name ?? 'Role N/A' }})</span>
                        </div>
                        {{ $latestLog->comments ?? 'No comments available' }}
                    @endif
                </div>
            </div>
        </div>
    @endif



    @if ($model->{$module->approval_column} === 'rejected' && $model->am_change_made == 0)
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

    @foreach ($approvalCycles as $cycle => $rows)

        <div class="approval-cycle-section mb-4 {{ $cycle !== $model->getCurrentApprovalCycle() ? 'd-none' : '' }}">
            @if ($approvalCycles->count() > 1)
                @if ($cycle == $model->getCurrentApprovalCycle())
                    <span class="badge bg-primary d-none">Current Approval Flow</span>
                @else
                    <div class="cycle-header mb-3">
                        <span class="badge bg-secondary">Previous Approval Flow #{{ $cycle }}</span>
                    </div>
                @endif
            @endif

            <div class="d-flex w-100 mb-3" style="gap:12px;">
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
                @php
                    // Group rows by role_id to avoid duplicate role displays
                    $rowsByRole = $rows->sortBy('id')->groupBy('role_id');
                @endphp

                @foreach ($rowsByRole as $role_id => $roleRows)
                    @php
                        $role = $roleRows->first()->role; // Get the role from the first row
                        $requiredCount = $roleRows->sum('required_count'); // Sum required counts for this role
                        $logs = $model
                            ->approvalLogs()
                            ->where('role_id', $role_id)
                            ->where('module_id', $module->id)
                            ->where('approval_cycle', $cycle)
                            ->with('user')
                            ->get();

                        $approvedLogs = $logs->whereIn('action', ['approved', 'partial_approved']);
                        $rejectedLogs = $logs->where('action', 'rejected');
                        $rejectedIDS = [];
                    @endphp

                    <div class="dash-item flex-fill">
                        <div class="dash-value d-flex flex-column text-uppercase text-center">
                            @if ($approvedLogs->isNotEmpty() && isset($approvedLogs->first()->user))
                                <strong>{{ $approvedLogs->first()->user->name }}</strong>
                            @elseif ($rejectedLogs->isNotEmpty() && !in_array($rejectedLogs->first()->user->id, $rejectedIDS))
                                <strong>{{ $rejectedLogs->first()->user->name }}</strong>
                                @php
                                    $rejectedIDS[] = $rejectedLogs->first()->user->id;
                                @endphp
                            @else
                                <span>&nbsp;</span>
                            @endif
                            <small style="font-size: 10px;">({{ $role->name }})</small>
                        </div>
                        <div class="dash-line">______________________</div>
                        <div class="approver-name mt-1">
                            @if ($approvedLogs->isNotEmpty())
                                Approved By
                            @elseif ($rejectedLogs->isNotEmpty())
                                <span class="text-danger">Rejected By</span>
                            @else
                                Approved By
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endforeach

    {{-- @php

    dd($rows->sortBy('id'));
    @endphp

    @foreach ($rows->sortBy('id') as $idx => $row)
    @php
    $role = $row->role;
    $requiredCount = $row->required_count;

    $logs = $model
    ->approvalLogs()
    ->where('role_id', $role->id)
    ->where('module_id', $module->id)
    ->where('approval_cycle', $cycle)
    ->with('user')
    ->get();

    $approvedLogs = $logs->whereIn('action', ['approved', 'partial_approved']);
    $rejectedLogs = $logs->where('action', 'rejected');
    $rejectedIDS = [];
    @endphp

    @for ($i = 1; $i <= $requiredCount; $i++) <div class="dash-item flex-fill">
        <div class="dash-value d-flex flex-column text-uppercase text-center">
            @if ($i <= $approvedLogs->count() && isset($approvedLogs->values()[$i - 1]))
                <strong>{{ $approvedLogs->values()[$i - 1]->user->name }}</strong>
                @elseif ($rejectedLogs->isNotEmpty() && !in_array($rejectedLogs->first()->user->id, $rejectedIDS))
                <strong>{{ $rejectedLogs->first()->user->name }}</strong>
                @else
                <span>&nbsp;</span>
                @endif
                <small style="font-size: 10px;">({{ $role->name }})</small>
        </div>
        <div class="dash-line">______________________</div>
        <div class="approver-name mt-1">
            @if ($i <= $approvedLogs->count() && isset($approvedLogs->values()[$i - 1]))
                Approved By
                @elseif ($rejectedLogs->isNotEmpty() && !in_array($rejectedLogs->first()->user->id, $rejectedIDS))
                <span class="text-danger">Rejected By</span>
                @php
                if ($rejectedLogs->isNotEmpty() && isset($rejectedLogs->first()->user->id)) {
                $rejectedIDS[] = $rejectedLogs->first()->user->id;
                }
                @endphp
                @else
                Approved By
                @endif
        </div>
        </div>
        @endfor
        @endforeach
        </div>
        </div>
        @endforeach --}}

        @if ($model->canApprove() && !$userAlreadyActed && !$changesRequired)
            <div class="row g-3 mx-auto">
                <div class="col-md-8 mx-auto">
                    <div class="action-form">
                        @php
                            $routeName = class_basename($model) === 'PurchaseQuotationData'
                                ? 'approval.bulk_quotation_approval'
                                : 'approval.approve';
                        @endphp

                        

                        <form id="ajaxSubmit" method="POST"
                            action="{{ route($routeName, ['modelType' => class_basename($model), 'id' => $model->id]) }}">

                            @csrf
                            <input type="hidden" name="class" value="{{ class_basename($model) }}">
                            <input type="hidden" name="mc" value="{{ $module->id }}">
                            <input type="hidden" name="id" value="{{ $model->id }}">
                            <input type="hidden" name="model_data_ids" id="model_data_ids">
                            <input type="hidden" name="type" id="approvalTypeInput" value="">

                            <div class="mb-3">
                                <label for="comment" class="form-label fw-medium">Comment
                                    <span class="text-danger">*</span></label>
                                <textarea name="comments" id="comment" class="form-control" rows="3"
                                    placeholder="Enter your remarks or observations..."></textarea>
                            </div>

                            <div class="d-flex" style="gap: 8px">
                                <button type="submit" id="approveSubmitBtn" style="display: none;"
                                    formaction="{{ route('approval.approve', ['modelType' => class_basename($model), 'id' => $model->id]) }}">
                                </button>

                                <button type="submit" id="rejectSubmitBtn" style="display: none;"
                                    formaction="{{ route('approval.reject', ['modelType' => class_basename($model), 'id' => $model->id]) }}">
                                </button>

                                <button type="button" class="btn btn-success w-50 fw-semibold"
                                    onclick="submitData('approve')">
                                    <i class="fa fa-check me-2"></i>
                                    Grant Approval
                                </button>
                                <button type="button" class="btn btn-primary w-50 fw-semibold"
                                    onclick="confirmApproval('revert')">
                                    <i class="fa fa-return me-2"></i>
                                    Revert Request
                                </button>
                                <button type="button" class="btn btn-danger w-50 fw-semibold"
                                    onclick="confirmApproval('reject')">
                                    <i class="fa fa-times me-2"></i>
                                    Decline Request
                                </button>
                            </div>
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

            .approval-cycle-section {
                /* border: 1px solid #e9ecef;
                                border-radius: 8px;
                                padding: 15px;
                                background-color: #f8f9fa; */
            }

            .cycle-header {
                padding-bottom: 10px;
                border-bottom: 1px solid #dee2e6;
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

    <script>
        
        $("#ajaxSubmit2").on("submit", function(e) {
            e.preventDefault();

            // Submit the first form with AJAX
            $.ajax({
                url: $("#ajaxSubmit").attr("action"),
                type: "POST",
                data: $("#ajaxSubmit").serialize(),

                success: function (response) {
                    console.log("First form success");

                    // 🔥 Only run this if success (status 200)
                    $("#ajaxSubmit2_actual").submit();  // or any action you want
                },

                error: function (xhr) {
                    if (xhr.status === 422) {
                        console.log("Validation failed (422)");
                        // ❌ Do NOT submit second form
                    } else {
                        console.log("Other error");
                    }
                }
            });
        });


        function submitData() {
            $("#ajaxSubmit2").submit();
        }

        function confirmApproval(type) {
            $("#ajaxSubmit2").submit();
            let msg =
                type === 'approve'
                    ? 'Are you sure you want to grant approval?'
                    : type === 'revert'
                        ? 'Are you sure you want to revert this request?'
                        : 'Are you sure you want to decline this request?';

            let confirmButtonText =
                type === 'approve'
                    ? 'Yes, Approve'
                    : type === 'revert'
                        ? 'Yes, Revert'
                        : 'Yes, Decline';

            let confirmButtonColor =
                type === 'approve'
                    ? '#28a745'
                    : type === 'revert'
                        ? '#27489a'
                        : '#d33';

            let icon =
                type === 'approve'
                    ? 'question'
                    : type === 'revert'
                        ? 'primary'
                        : 'warning';

            return Swal.fire({
                title: 'Please Confirm',
                text: msg,
                icon: icon,
                showCancelButton: true,
                confirmButtonColor: confirmButtonColor,
                cancelButtonColor: '#6c757d',
                confirmButtonText: confirmButtonText,
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    let approvedQtys = [];
                    $('input[name="data_id[]"]').each(function () {
                        approvedQtys.push($(this).val());
                    });
                    $('#model_data_ids').val(JSON.stringify(approvedQtys));

                    $('#approvalTypeInput').val(type);
                    $('#ajaxSubmit').submit();
                    // location.reload();
                }
            });
        }
    </script>