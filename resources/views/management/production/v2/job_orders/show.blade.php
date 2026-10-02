@extends('management.layouts.master')
@section('title')
    Job Order #{{ $jobOrder->job_order_no }}
@endsection

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="card-title mb-0">Job Order: {{ $jobOrder->job_order_no }}</h4>
                    <div>
                        <a href="{{ route('production.job-orders.edit', $jobOrder->id) }}" 
                           onclick="loadPageContent('{{ route('production.job-orders.edit', $jobOrder->id) }}')" 
                           class="btn btn-sm btn-primary mr-1">
                            <i class="ft-edit mr-1"></i>Edit
                        </a>
                        <a href="{{ route('production.job-orders.index') }}" 
                           onclick="loadPageContent('{{ route('production.job-orders.index') }}')" 
                           class="btn btn-sm btn-secondary">
                            <i class="ft-arrow-left mr-1"></i>Back to List
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    <!-- Basic Information Section -->
                    <h6 class="header-heading-sepration">Basic Information</h6>
                    <div class="row mb-3">
                        <div class="col-md-4 mb-2">
                            <label class="text-muted small d-block">Job Order No#</label>
                            <strong>{{ $jobOrder->job_order_no }}</strong>
                        </div>
                        <div class="col-md-4 mb-2">
                            <label class="text-muted small d-block">Job Order Date</label>
                            <span>{{ $jobOrder->job_order_date ? \Carbon\Carbon::parse($jobOrder->job_order_date)->format('d/m/Y') : 'N/A' }}</span>
                        </div>
                        <div class="col-md-4 mb-2">
                            <label class="text-muted small d-block">Plant / Factory Location</label>
                            <span>{{ $jobOrder->companyLocation->name ?? 'N/A' }}</span>
                        </div>
                        <div class="col-md-4 mb-2">
                            <label class="text-muted small d-block">Commodity / Product</label>
                            <span>{{ $jobOrder->product->name ?? '-' }}</span>
                        </div>
                        <div class="col-md-4 mb-2">
                            <label class="text-muted small d-block">Export Order</label>
                            <span>{{ $jobOrder->exportOrder->voucher_no ?? ($jobOrder->export_order_id ? '#' . $jobOrder->export_order_id : 'Domestic / None') }}</span>
                        </div>
                        <div class="col-md-4 mb-2">
                            <label class="text-muted small d-block">Reference No</label>
                            <span>{{ $jobOrder->ref_no ?: '-' }}</span>
                        </div>
                        <div class="col-md-4 mb-2">
                            <label class="text-muted small d-block">Status</label>
                            <span class="badge badge-secondary">{{ strtoupper(str_replace('_', ' ', $jobOrder->status)) }}</span>
                        </div>
                        <div class="col-md-8 mb-2">
                            <label class="text-muted small d-block">Attention To</label>
                            <div>
                                @forelse($jobOrder->attentionUsers() as $user)
                                    <span class="badge badge-light mr-1">{{ $user->name }}</span>
                                @empty
                                    <span class="text-muted">-</span>
                                @endforelse
                            </div>
                        </div>
                        <div class="col-md-6 mb-2">
                            <label class="text-muted small d-block">Order Description</label>
                            <p class="mb-0 text-dark">{{ $jobOrder->order_description ?: '-' }}</p>
                        </div>
                        <div class="col-md-6 mb-2">
                            <label class="text-muted small d-block">Remarks</label>
                            <p class="mb-0 text-dark">{{ $jobOrder->remarks ?: '-' }}</p>
                        </div>
                    </div>

                    <!-- Production Phases Section -->
                    @php
                        $phaseIds = (array)($jobOrder->active_phases ?? ($jobOrder->companyLocation->production_phases ?? []));
                    @endphp
                    <h6 class="header-heading-sepration mt-3">Production Phases</h6>
                    <div class="mb-3">
                        @if(empty($phaseIds))
                            <span class="text-muted">No phases active for this job order.</span>
                        @else
                            @foreach($activePhases as $actPhase)
                                <span class="badge badge-light border mr-1">{{ $actPhase->name }}</span>
                            @endforeach
                        @endif
                    </div>

                    @if(!empty($phaseIds))
                        <ul class="nav nav-tabs" role="tablist">
                            @foreach($activePhases as $index => $actPhase)
                                <li class="nav-item">
                                    <a class="nav-link {{ $index === 0 ? 'active' : '' }} font-weight-bold" data-toggle="tab" href="#v-tab-phase{{ $actPhase->id }}" role="tab">
                                        {{ $actPhase->name }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>

                        <div class="tab-content pt-3">
                            <!-- TAB: PHASE 1 DRYINGS -->
                            @if(in_array(1, $phaseIds))
                            <div class="tab-pane fade {{ $activePhases->first()?->id == 1 ? 'show active' : '' }}" id="v-tab-phase1" role="tabpanel">
                                @if($jobOrder->productionPhase1->isEmpty())
                                    <p class="text-muted">No Phase 1 records logged yet for this job order.</p>
                                @else
                                    <div class="table-responsive">
                                        <table class="table table-hover table-bordered m-0">
                                            <thead class="thead-light">
                                                <tr>
                                                    <th>Mode</th>
                                                    <th>Temperature</th>
                                                    <th>Moisture Level</th>
                                                    <th>Status</th>
                                                    <th>Remarks</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($jobOrder->productionPhase1 as $p1)
                                                    <tr>
                                                        <td>{{ ucfirst($p1->drying_mode) }}</td>
                                                        <td>{{ $p1->temperature ? $p1->temperature . '°C' : '-' }}</td>
                                                        <td>{{ ucfirst(str_replace('_', ' ', $p1->moisture_level ?? 'half_dried')) }}</td>
                                                        <td><span class="badge badge-secondary">{{ ucfirst($p1->status) }}</span></td>
                                                        <td>{{ $p1->remarks ?: '-' }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @endif
                            </div>
                            @endif

                            <!-- TAB: PHASE 2 COOKING -->
                            @if(in_array(2, $phaseIds))
                            <div class="tab-pane fade {{ $activePhases->first()?->id == 2 ? 'show active' : '' }}" id="v-tab-phase2" role="tabpanel">
                                @if($jobOrder->productionPhase2->isEmpty())
                                    <p class="text-muted">No Phase 2 records logged yet for this job order.</p>
                                @else
                                    <div class="table-responsive">
                                        <table class="table table-hover table-bordered m-0">
                                            <thead class="thead-light">
                                                <tr>
                                                    <th>Process</th>
                                                    <th>Steam Type</th>
                                                    <th>Soak Hours</th>
                                                    <th>Cook Minutes</th>
                                                    <th>Grade</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($jobOrder->productionPhase2 as $p2)
                                                    <tr>
                                                        <td>{{ ucfirst($p2->process_type) }}</td>
                                                        <td>{{ $p2->steam_type ? ucfirst(str_replace('_', ' ', $p2->steam_type)) : '-' }}</td>
                                                        <td>{{ $p2->parboil_soak_hours ? $p2->parboil_soak_hours . ' hrs' : '-' }}</td>
                                                        <td>{{ $p2->parboil_cook_minutes ? $p2->parboil_cook_minutes . ' mins' : '-' }}</td>
                                                        <td>{{ $p2->parboiled_grade ? ucfirst(str_replace('_', ' ', $p2->parboiled_grade)) : '-' }}</td>
                                                        <td><span class="badge badge-secondary">{{ ucfirst($p2->status) }}</span></td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @endif
                            </div>
                            @endif

                            <!-- TAB: PHASE 3 MILLING -->
                            @if(in_array(3, $phaseIds))
                            <div class="tab-pane fade {{ $activePhases->first()?->id == 3 ? 'show active' : '' }}" id="v-tab-phase3" role="tabpanel">
                                @if($jobOrder->productionPhase3->isEmpty())
                                    <p class="text-muted">No Phase 3 records logged yet for this job order.</p>
                                @else
                                    <div class="table-responsive">
                                        <table class="table table-hover table-bordered m-0">
                                            <thead class="thead-light">
                                                <tr>
                                                    <th>Milling Feed</th>
                                                    <th>Storage Location</th>
                                                    <th>Status</th>
                                                    <th>Remarks</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($jobOrder->productionPhase3 as $p3)
                                                    <tr>
                                                        <td>{{ ucfirst(str_replace('_', ' ', $p3->milling_type)) }}</td>
                                                        <td>Flat Storage</td>
                                                        <td><span class="badge badge-secondary">{{ ucfirst($p3->status) }}</span></td>
                                                        <td>{{ $p3->remarks ?: '-' }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @endif
                            </div>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
