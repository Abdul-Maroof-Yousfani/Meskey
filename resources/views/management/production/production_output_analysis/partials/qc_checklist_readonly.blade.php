{{--
    Read-only QC Checklist: Slab | Job Order spec | QC Result (green = within spec, yellow = difference).
    Expects: $checklistRows = rows from OutputQcAlertService::checklist() (+ rows without a spec, status 'none')
    Optional: $showSpec (default true) - hide the Job Order column when there are no specs
--}}
@php $showSpec = $showSpec ?? true; @endphp
<div class="striped-rows qc-checklist-container qc-readonly-checklist">
    <div class="form-group row qc-spec-heading">
        <div class="{{ $showSpec ? 'col-md-4' : 'col-md-6' }}">Slabs</div>
        @if($showSpec)
            <div class="col-md-4">Job Order</div>
        @endif
        <div class="{{ $showSpec ? 'col-md-4' : 'col-md-6' }}">QC Result</div>
    </div>
    @forelse($checklistRows as $row)
        <div class="form-group row qc-slab-row">
            <label class="{{ $showSpec ? 'col-md-4' : 'col-md-6' }} label-control">{{ $row['name'] }}</label>
            @if($showSpec)
                <div class="col-md-4">
                    <input type="text" class="form-control job-order-spec" value="{{ $row['spec_text'] ?? 'No spec' }}" readonly tabindex="-1">
                </div>
            @endif
            <div class="{{ $showSpec ? 'col-md-4' : 'col-md-6' }}">
                <div class="input-group">
                    <input type="text" readonly tabindex="-1"
                           class="form-control qc-spec-result {{ ($row['status'] ?? '') === 'ok' ? 'qc-result-ok' : (($row['status'] ?? '') === 'warn' ? 'qc-result-warn' : '') }}"
                           value="{{ $row['result'] ?? '-' }}">
                    <div class="input-group-append">
                        <span class="input-group-text">{{ $row['qc_symbol'] ?? '' }}</span>
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="alert alert-warning m-2">No QC Checklist found.</div>
    @endforelse
</div>

<style>
    .qc-readonly-checklist .form-group { margin: 0; padding: 6px 15px; display: flex; align-items: center; }
    .qc-readonly-checklist .form-group:nth-child(even) { background-color: #f7f7f8; }
    .qc-readonly-checklist .form-group:nth-child(odd) { background-color: #ffffff; }
    .qc-readonly-checklist .form-group.qc-spec-heading { background: transparent; padding: 0 15px 6px; font-weight: 700; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px; color: #6c757d; border-bottom: 1px solid #dcdce0; }
    .qc-readonly-checklist label.label-control { margin-bottom: 0; font-weight: 700; text-transform: uppercase; color: #2c3e50; letter-spacing: 0.5px; font-size: 0.85rem; }
    .qc-readonly-checklist .input-group-text { background-color: #f7f7f8; border: 1px solid #dcdce0; color: #6c757d; min-width: 45px; justify-content: center; }
    .qc-readonly-checklist .job-order-spec { background-color: #eef2f7; font-weight: 600; }
    .qc-readonly-checklist input.qc-spec-result:not(.qc-result-ok):not(.qc-result-warn) { background-color: #ffffff; }
    .qc-readonly-checklist input.qc-result-ok { background-color: #d4edda; border-color: #28a745; color: #155724; }
    .qc-readonly-checklist input.qc-result-warn { background-color: #fff3cd; border-color: #ffc107; color: #856404; }
    .layout-dark .qc-readonly-checklist .form-group:nth-child(even) { background-color: #101010; }
    .layout-dark .qc-readonly-checklist .form-group:nth-child(odd) { background-color: #1a1a1a; }
    .layout-dark .qc-readonly-checklist .form-group.qc-spec-heading { background: transparent; color: #adb5bd; border-bottom-color: #404040; }
    .layout-dark .qc-readonly-checklist label.label-control { color: #e2e8f0; }
    .layout-dark .qc-readonly-checklist .input-group-text { background-color: #262626; border-color: #404040; color: #ffffff; }
    .layout-dark .qc-readonly-checklist .job-order-spec { background-color: #1f2937; }
    .layout-dark .qc-readonly-checklist input.qc-spec-result:not(.qc-result-ok):not(.qc-result-warn) { background-color: #262626; }
</style>
