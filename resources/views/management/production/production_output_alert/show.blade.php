{{-- Output QC Alert: read-only QC Checklist (colored against the Job Order spec) + plant user's remarks --}}
<form action="{{ route('production-output-alert.resolve', $alert->id) }}" method="POST" id="ajaxSubmit" autocomplete="off">
    @csrf
    <input type="hidden" id="listRefresh" value="{{ route('get.production-output-alert') }}">

    <div class="row form-mar">
        <div class="col-md-12">
            <h6 class="header-heading-sepration">
                QC Alert {{ $alert->alert_no }}
                @if($alert->attempt > 1)
                    <span class="badge badge-secondary ml-1">Re-check #{{ $alert->attempt - 1 }}</span>
                @endif
                @if($alert->isPending())
                    <span class="badge badge-danger ml-1">Pending Remarks</span>
                @else
                    <span class="badge badge-success ml-1">Remarks Submitted</span>
                @endif
            </h6>

            <div class="row">
                <div class="col-md-3">
                    <label>Date:</label>
                    <p class="form-control-static">{{ $alert->created_at->format('d-m-Y h:i A') }}</p>
                </div>
                <div class="col-md-3">
                    <label>Plant:</label>
                    <p class="form-control-static">{{ $alert->plant->name ?? 'N/A' }}</p>
                </div>
                <div class="col-md-3">
                    <label>Location:</label>
                    <p class="form-control-static">{{ $alert->companyLocation->name ?? 'N/A' }} / {{ $alert->arrivalLocation->name ?? 'N/A' }}</p>
                </div>
                <div class="col-md-3">
                    <label>Analysis Request:</label>
                    <p class="form-control-static">{{ $alert->analysisRequest->request_no ?? '-' }}</p>
                </div>
                <div class="col-md-3">
                    <label>Job Order:</label>
                    <p class="form-control-static">{{ $alert->jobOrder->job_order_no ?? '-' }}</p>
                </div>
                <div class="col-md-3">
                    <label>Product:</label>
                    <p class="form-control-static">{{ $alert->product->name ?? 'N/A' }}</p>
                </div>
                <div class="col-md-3">
                    <label>Differences:</label>
                    <p class="form-control-static"><span class="badge badge-warning">{{ $alert->difference_count }}</span></p>
                </div>
                @if($alert->parentAlert)
                    <div class="col-md-3">
                        <label>Previous Alert:</label>
                        <p class="form-control-static">{{ $alert->parentAlert->alert_no }}</p>
                    </div>
                @endif
            </div>
        </div>

        <div class="col-md-12 mt-3">
            <h6 class="header-heading-sepration font-weight-bold mb-3">QC Checklist</h6>
            @include('management.production.production_output_analysis.partials.qc_checklist_readonly', ['checklistRows' => $alert->checklist ?? []])
        </div>

        <div class="col-md-12 mt-4">
            <div class="form-group">
                <label>Output Analysis Remarks:</label>
                <p class="form-control-static text-justify" style="white-space: pre-line;">{{ $alert->analysis?->remarks ?: 'No remarks provided.' }}</p>
            </div>
        </div>

        <div class="col-md-12 mt-2">
            <div class="form-group">
                <label>Alert Remarks: @if($canRemark)<span class="text-danger">*</span>@endif</label>
                @if($alert->isPending())
                    @if($canRemark)
                        <textarea name="remarks" class="form-control" rows="4" required placeholder="Enter remarks for this QC difference"></textarea>
                        <small class="text-muted">On submit, a new Output Analysis request is created for a re-check.</small>
                    @else
                        <p class="form-control-static text-muted">Waiting for remarks from the users assigned to this plant.</p>
                    @endif
                @else
                    <p class="form-control-static text-justify">{{ $alert->remarks }}</p>
                    <small class="text-muted">
                        By {{ $alert->remarkedBy->name ?? 'N/A' }} on {{ $alert->remarked_at?->format('d-m-Y h:i A') }}
                        @if($alert->newAnalysisRequest)
                            &bull; Re-check request: <strong>{{ $alert->newAnalysisRequest->request_no }}</strong>
                            <span class="badge {{ $alert->newAnalysisRequest->status_badge_class }}">{{ ucfirst(str_replace('_', ' ', $alert->newAnalysisRequest->status)) }}</span>
                        @endif
                    </small>
                @endif
            </div>
        </div>
    </div>

    <div class="row bottom-button-bar">
        <div class="col-12 text-right">
            <a type="button" class="btn btn-danger modal-sidebar-close closebutton">Close</a>
            @if($canRemark)
                <button type="submit" class="btn btn-primary submitbutton">Submit Remarks</button>
            @endif
        </div>
    </div>
</form>

