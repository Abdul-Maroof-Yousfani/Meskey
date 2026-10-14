<div class="qc-checklist-modal-content">
    <!-- Header Bar -->
    <div class="qc-header-bar d-flex justify-content-between align-items-center mb-3">
        <div>
            <h5 class="m-0 font-weight-bold text-dark">
                <i class="ft-check-square mr-1 text-primary"></i> QC Checklist
            </h5>
            <small class="text-muted">Production Voucher Performed Quality Analysis</small>
        </div>
        <div class="header-badges">
            <span class="badge badge-primary mr-1">Voucher # {{ $productionVoucher->prod_no }}</span>
            <span class="badge badge-secondary mr-1">Date: {{ \Carbon\Carbon::parse($productionVoucher->prod_date)->format('M d, Y') }}</span>
            @if($productionVoucher->plant)
                <span class="badge badge-info mr-1">Plant: {{ $productionVoucher->plant->name }}</span>
            @endif
            @if($productionVoucher->location)
                <span class="badge badge-light border">Location: {{ $productionVoucher->location->name }}</span>
            @endif
        </div>
    </div>

    @if($qcGroups->isNotEmpty())
        <!-- LEVEL 1: Parent Tabs (Input QC / Output QC / Machine QC) -->
        <!-- Only rendered if records exist for that category -->
        <ul class="nav nav-pills parent-qc-nav-pills mb-3 p-1 rounded bg-light border" id="parentQcTabs" role="tablist">
            @php $pFirst = true; @endphp
            @foreach($qcGroups as $groupKey => $group)
                <li class="nav-item flex-fill text-center">
                    <a class="nav-link font-weight-bold py-2 {{ $pFirst ? 'active' : '' }}" 
                       id="parent-tab-{{ $groupKey }}" 
                       data-toggle="pill" 
                       href="#parent-pane-{{ $groupKey }}" 
                       role="tab" 
                       aria-controls="parent-pane-{{ $groupKey }}" 
                       aria-selected="{{ $pFirst ? 'true' : 'false' }}">
                        <i class="{{ $group['icon'] }} mr-1"></i> {{ $group['title'] }}
                        <span class="badge {{ $group['badge'] }} ml-1 px-2">{{ $group['items']->count() }}</span>
                    </a>
                </li>
                @php $pFirst = false; @endphp
            @endforeach
        </ul>

        <!-- LEVEL 1 Tab Content Panes -->
        <div class="tab-content" id="parentQcTabsContent">
            @php $pPaneFirst = true; @endphp
            @foreach($qcGroups as $groupKey => $group)
                <div class="tab-pane fade {{ $pPaneFirst ? 'show active' : '' }}" 
                     id="parent-pane-{{ $groupKey }}" 
                     role="tabpanel" 
                     aria-labelledby="parent-tab-{{ $groupKey }}">

                    <!-- LEVEL 2: Child Sub-Tabs (Time & Date Wise QCs) -->
                    <ul class="nav nav-tabs child-qc-tabs border-bottom mb-3" id="childTabs-{{ $groupKey }}" role="tablist">
                        @foreach($group['items'] as $cIndex => $item)
                            <li class="nav-item">
                                <a class="nav-link {{ $cIndex === 0 ? 'active' : '' }}" 
                                   id="subtab-btn-{{ $item['id'] }}" 
                                   data-toggle="tab" 
                                   href="#subtab-pane-{{ $item['id'] }}" 
                                   role="tab" 
                                   aria-controls="subtab-pane-{{ $item['id'] }}" 
                                   aria-selected="{{ $cIndex === 0 ? 'true' : 'false' }}">
                                    <div class="child-tab-title font-weight-bold">{{ $item['tab_title'] }}</div>
                                    <small class="text-muted d-block">{{ $item['time_formatted'] }}</small>
                                </a>
                            </li>
                        @endforeach
                    </ul>

                    <!-- LEVEL 2 Content Panes (Each QC Session) -->
                    <div class="tab-content" id="childTabsContent-{{ $groupKey }}">
                        @foreach($group['items'] as $cIndex => $item)
                            <div class="tab-pane fade {{ $cIndex === 0 ? 'show active' : '' }}" 
                                 id="subtab-pane-{{ $item['id'] }}" 
                                 role="tabpanel" 
                                 aria-labelledby="subtab-btn-{{ $item['id'] }}">

                                <!-- QC Summary Bar -->
                                <div class="d-flex justify-content-between align-items-center bg-white p-2 px-3 rounded mb-3 border">
                                    <div>
                                        <span class="font-weight-bold text-primary">{{ $item['full_title'] }}</span>
                                        @if(isset($item['model']->product) && $item['model']->product)
                                            <span class="badge badge-outline-secondary ml-2">Product: {{ $item['model']->product->name }}</span>
                                        @endif
                                        @if(isset($item['model']->machine) && $item['model']->machine)
                                            <span class="badge badge-outline-info ml-2">Machine: {{ $item['model']->machine->name }}</span>
                                        @endif
                                    </div>
                                    <div>
                                        <small class="text-muted font-weight-bold">
                                            <i class="ft-clock mr-1"></i> {{ $item['time_formatted'] }}
                                        </small>
                                    </div>
                                </div>

                                <!-- QC Checklist Table (Parameter & Result) -->
                                <div class="qc-checklist-table-wrapper border rounded overflow-hidden mb-3">
                                    <div class="row m-0 py-2 px-3 table-header-row text-white font-weight-bold align-items-center">
                                        <div class="col-7 col-md-8">
                                            <span class="text-uppercase tracking-wider">QC Parameter</span>
                                        </div>
                                        <div class="col-5 col-md-4 text-center">
                                            <span class="text-uppercase tracking-wider">Result</span>
                                        </div>
                                    </div>
                                    <div class="striped-rows bg-white">
                                        @forelse($item['slabs'] as $slab)
                                            <div class="row m-0 py-2 px-3 align-items-center checklist-row border-top">
                                                <div class="col-7 col-md-8">
                                                    <span class="font-weight-bold text-dark text-uppercase param-name">
                                                        {{ $slab['name'] }}
                                                    </span>
                                                </div>
                                                <div class="col-5 col-md-4">
                                                    <div class="input-group input-group-sm mb-0">
                                                        <input type="text" readonly 
                                                               class="form-control text-center font-weight-bold bg-white" 
                                                               value="{{ $slab['value'] !== null && $slab['value'] !== '' ? $slab['value'] : '-' }}" 
                                                               disabled>
                                                        <div class="input-group-append">
                                                            <span class="input-group-text font-weight-bold px-3">
                                                                {{ $slab['symbol'] }}
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @empty
                                            <div class="p-4 text-center text-muted">
                                                <i class="ft-alert-circle mr-1"></i> No parameter readings recorded for this analysis.
                                            </div>
                                        @endforelse
                                    </div>
                                </div>

                                <!-- Extra Details & Remarks if present -->
                                @php $m = $item['model']; @endphp
                                @if(!empty($item['remarks']) || !empty($m->variety) || !empty($m->milling_degree) || !empty($m->crop_year_id) || !empty($m->inner_stitching) || !empty($m->outer_stitching))
                                    <div class="card bg-light border p-3 mb-3">
                                        <div class="row">
                                            @if(!empty($m->variety))
                                                <div class="col-md-3 mb-2">
                                                    <small class="text-muted d-block font-weight-bold">Variety</small>
                                                    <span class="text-dark">{{ $m->variety }}</span>
                                                </div>
                                            @endif
                                            @if(!empty($m->cropYear))
                                                <div class="col-md-3 mb-2">
                                                    <small class="text-muted d-block font-weight-bold">Crop Year</small>
                                                    <span class="text-dark">{{ $m->cropYear->name ?? $m->crop_year_id }}</span>
                                                </div>
                                            @endif
                                            @if(!empty($m->milling_degree))
                                                <div class="col-md-3 mb-2">
                                                    <small class="text-muted d-block font-weight-bold">Milling Degree</small>
                                                    <span class="text-dark">{{ ucwords($m->milling_degree) }}</span>
                                                </div>
                                            @endif
                                            @if(!empty($m->inner_stitching))
                                                <div class="col-md-3 mb-2">
                                                    <small class="text-muted d-block font-weight-bold">Inner Stitching</small>
                                                    <span class="text-dark">{{ ucwords($m->inner_stitching) }}</span>
                                                </div>
                                            @endif
                                            @if(!empty($m->outer_stitching))
                                                <div class="col-md-3 mb-2">
                                                    <small class="text-muted d-block font-weight-bold">Outer Stitching</small>
                                                    <span class="text-dark">{{ ucwords($m->outer_stitching) }}</span>
                                                </div>
                                            @endif
                                            @if(!empty($item['remarks']))
                                                <div class="col-12 mt-2">
                                                    <small class="text-muted d-block font-weight-bold">Remarks</small>
                                                    <div class="text-dark bg-white p-2 rounded border">{{ $item['remarks'] }}</div>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @endif

                            </div>
                        @endforeach
                    </div>

                </div>
                @php $pPaneFirst = false; @endphp
            @endforeach
        </div>
    @else
        <div class="alert alert-info text-center p-4 my-4">
            <i class="ft-info font-medium-3 d-block mb-2"></i>
            <h5>No Performed Analysis Found</h5>
            <p class="mb-0 text-muted">
                There are no performed Analysis records assigned or recorded for this Production Voucher date ({{ \Carbon\Carbon::parse($productionVoucher->prod_date)->format('M d, Y') }}).
            </p>
        </div>
    @endif

    <div class="row mt-4">
        <div class="col-12 text-right">
            <button type="button" class="btn btn-secondary modal-sidebar-close closebutton" data-close="model">Close</button>
        </div>
    </div>
</div>

<style>
    .qc-header-bar {
        background: #f4f5fa;
        padding: 12px 16px;
        border-radius: 6px;
        border-left: 4px solid #2b4380;
    }

    /* Level 1: Parent Pills */
    .parent-qc-nav-pills {
        background: #f1f3f8 !important;
        gap: 6px;
    }
    .parent-qc-nav-pills .nav-link {
        color: #4a5568;
        border-radius: 4px;
        transition: all 0.2s ease-in-out;
        font-size: 0.95rem;
    }
    .parent-qc-nav-pills .nav-link:hover {
        background: rgba(43, 67, 128, 0.08);
        color: #2b4380;
    }
    .parent-qc-nav-pills .nav-link.active {
        background: #2b4380 !important;
        color: #ffffff !important;
        box-shadow: 0 2px 6px rgba(43, 67, 128, 0.25);
    }
    .parent-qc-nav-pills .nav-link.active .badge {
        background-color: #ffffff !important;
        color: #2b4380 !important;
    }

    /* Level 2: Child Sub-Tabs */
    .child-qc-tabs {
        border-bottom: 2px solid #e2e8f0;
    }
    .child-qc-tabs .nav-link {
        padding: 8px 16px;
        color: #64748b;
        border: 1px solid transparent;
        border-top-left-radius: 4px;
        border-top-right-radius: 4px;
        text-align: center;
        background: #f8fafc;
        margin-right: 4px;
        transition: all 0.2s ease;
    }
    .child-qc-tabs .nav-link:hover {
        background: #edf2f7;
        color: #1e293b;
    }
    .child-qc-tabs .nav-link.active {
        color: #2563eb !important;
        font-weight: 700;
        background-color: #ffffff;
        border-color: #cbd5e1 #cbd5e1 #ffffff;
        border-bottom: 3px solid #2563eb !important;
    }

    /* Table & Rows */
    .table-header-row {
        background: #2b4380;
        font-size: 0.85rem;
        letter-spacing: 0.5px;
    }
    .checklist-row:nth-child(even) {
        background-color: #f8fafc;
    }
    .checklist-row:nth-child(odd) {
        background-color: #ffffff;
    }
    .checklist-row:hover {
        background-color: #f1f5f9;
    }
    .param-name {
        font-size: 0.85rem;
        letter-spacing: 0.5px;
        color: #1e293b;
    }
    .checklist-row .input-group-text {
        background-color: #f1f5f9;
        border: 1px solid #cbd5e1;
        min-width: 52px;
        justify-content: center;
        color: #334155;
    }
    .checklist-row .form-control {
        border-color: #cbd5e1;
    }
</style>
