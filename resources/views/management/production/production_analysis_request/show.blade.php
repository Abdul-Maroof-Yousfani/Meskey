<div class="row form-mar">
    <div class="col-md-12">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="m-0 font-weight-bold text-primary">
                <i class="ft-file-text mr-1"></i> Analysis Request: {{ $item->request_no }}
            </h5>
            <div>
                <span class="badge {{ $item->status_badge_class }} p-2 font-medium-1">
                    {{ ucfirst($item->status) }}
                </span>
            </div>
        </div>

        <div class="card border mb-3">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 mb-2">
                        <label class="text-muted d-block mb-1">Request Date</label>
                        <h6>{{ $item->request_date->format('d-m-Y') }}</h6>
                    </div>
                    <div class="col-md-4 mb-2">
                        <label class="text-muted d-block mb-1">Analysis Type</label>
                        <span class="badge {{ $item->type_badge_class }} p-1 font-small-3">
                            {{ $item->type_name }}
                        </span>
                    </div>
                    <div class="col-md-4 mb-2">
                        <label class="text-muted d-block mb-1">Job Order</label>
                        <h6>
                            @if($item->jobOrder)
                                <span class="badge badge-light text-dark font-weight-bold">
                                    {{ $item->jobOrder->job_order_no }}
                                </span>
                            @else
                                <span class="text-muted">Not specified</span>
                            @endif
                        </h6>
                    </div>

                    <div class="col-md-4 mb-2">
                        <label class="text-muted d-block mb-1">Company Location</label>
                        <h6>{{ $item->companyLocation->name ?? 'N/A' }}</h6>
                    </div>
                    <div class="col-md-4 mb-2">
                        <label class="text-muted d-block mb-1">Arrival Location</label>
                        <h6>{{ $item->arrivalLocation->name ?? 'N/A' }}</h6>
                    </div>
                    <div class="col-md-4 mb-2">
                        <label class="text-muted d-block mb-1">Plant</label>
                        <h6>{{ $item->plant->name ?? 'N/A' }}</h6>
                    </div>

                    <div class="col-md-12 mt-2">
                        <label class="text-muted d-block mb-1">Remarks</label>
                        <p class="bg-light p-2 rounded">{{ $item->remarks ?: 'No remarks provided.' }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Linked Analysis Information -->
        @if($item->status === 'completed')
            <div class="alert alert-success d-flex align-items-center">
                <i class="ft-check-circle font-large-1 mr-2"></i>
                <div>
                    <strong>Analysis Completed!</strong>
                    @if($item->type === \App\Models\Production\ProductionAnalysisRequest::TYPE_MACHINE && $item->machineAnalysis)
                        <div class="mt-1">
                            Linked Machine Analysis ID: #{{ $item->machineAnalysis->id }} 
                            (Date: {{ \Carbon\Carbon::parse($item->machineAnalysis->analysis_date)->format('d-m-Y') }})
                        </div>
                    @elseif($item->productionAnalysis)
                        <div class="mt-1">
                            Linked {{ $item->type_name }} ID: #{{ $item->productionAnalysis->id }} 
                            (Date: {{ \Carbon\Carbon::parse($item->productionAnalysis->analysis_date)->format('d-m-Y') }})
                        </div>
                    @endif
                </div>
            </div>
        @endif

        <div class="text-muted small">
            <span>Created by: {{ $item->creator->name ?? 'System' }}</span>
            <span class="mx-2">•</span>
            <span>Created at: {{ $item->created_at->format('d-m-Y H:i') }}</span>
        </div>
    </div>
</div>

<div class="row bottom-button-bar mt-3">
    <div class="col-12 text-right">
        <a type="button" class="btn btn-secondary modal-sidebar-close closebutton">Close</a>
    </div>
</div>
