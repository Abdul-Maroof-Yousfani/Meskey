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
                           class="btn btn-sm btn-primary mr-1">
                            <i class="ft-edit mr-1"></i>Edit
                        </a>
                        <a href="{{ route('production.job-orders.index') }}" 
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
                        <div class="col-md-4 mb-2">
                            <label class="text-muted small d-block">Current Stage</label>
                            <span class="badge badge-info px-2 py-1 font-weight-bold" style="font-size: 0.9rem;">
                                Stage {{ $jobOrder->current_stage ?? '1' }}
                            </span>
                        </div>
                        <div class="col-md-12 mb-2">
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
                                @if($jobOrder->productionPhase1->isEmpty() && (!$jobOrder->phase1Parameters || $jobOrder->phase1Parameters->isEmpty()))
                                    <p class="text-muted">No Phase 1 records logged yet for this job order.</p>
                                @else
                                    @if(!$jobOrder->productionPhase1->isEmpty())
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

                                    @if($jobOrder->phase1Parameters && $jobOrder->phase1Parameters->count() > 0)
                                        <h6 class="font-weight-bold text-dark {{ !$jobOrder->productionPhase1->isEmpty() ? 'mt-3' : '' }} mb-2">
                                            <i class="ft-sliders mr-1 text-primary"></i> Phase 1 Parameters
                                        </h6>
                                        <div class="table-responsive">
                                            <table class="table table-hover table-bordered m-0">
                                                <thead class="thead-light">
                                                    <tr>
                                                        <th style="width: 40%;">Parameter Key</th>
                                                        <th style="width: 25%;">Type</th>
                                                        <th style="width: 35%;">Parameter Value</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($jobOrder->phase1Parameters as $param)
                                                        <tr>
                                                            <td class="font-weight-bold">{{ $param->key }}</td>
                                                            <td><span class="badge badge-info">{{ ucfirst($param->type) }}</span></td>
                                                            <td>{{ $param->value !== null && $param->value !== '' ? $param->value : '-' }}</td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @endif
                                @endif
                            </div>
                            @endif

                            <!-- TAB: PHASE 2 COOKING -->
                            @if(in_array(2, $phaseIds))
                            <div class="tab-pane fade {{ $activePhases->first()?->id == 2 ? 'show active' : '' }}" id="v-tab-phase2" role="tabpanel">
                                @if($jobOrder->productionPhase2->isEmpty() && (!$jobOrder->phase2Parameters || $jobOrder->phase2Parameters->isEmpty()))
                                    <p class="text-muted">No Phase 2 records logged yet for this job order.</p>
                                @else
                                    @if(!$jobOrder->productionPhase2->isEmpty())
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

                                    @if($jobOrder->phase2Parameters && $jobOrder->phase2Parameters->count() > 0)
                                        <h6 class="font-weight-bold text-dark {{ !$jobOrder->productionPhase2->isEmpty() ? 'mt-3' : '' }} mb-2">
                                            <i class="ft-sliders mr-1 text-primary"></i> Phase 2 Parameters
                                        </h6>
                                        <div class="table-responsive">
                                            <table class="table table-hover table-bordered m-0">
                                                <thead class="thead-light">
                                                    <tr>
                                                        <th style="width: 40%;">Parameter Key</th>
                                                        <th style="width: 25%;">Type</th>
                                                        <th style="width: 35%;">Parameter Value</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($jobOrder->phase2Parameters as $param)
                                                        <tr>
                                                            <td class="font-weight-bold">{{ $param->key }}</td>
                                                            <td><span class="badge badge-info">{{ ucfirst($param->type) }}</span></td>
                                                            <td>{{ $param->value !== null && $param->value !== '' ? $param->value : '-' }}</td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @endif
                                @endif
                            </div>
                            @endif

                            <!-- TAB: PHASE 3 MILLING & PACKING (OLD PRODUCTION FLOW) -->
                            @if(in_array(3, $phaseIds))
                            <div class="tab-pane fade {{ $activePhases->first()?->id == 3 ? 'show active' : '' }}" id="v-tab-phase3" role="tabpanel">
                                <!-- Specifications & Crop Year -->
                                <div class="row mb-3">
                                    <div class="col-md-6 mb-2">
                                        <label class="text-muted small d-block">Crop Year</label>
                                        <strong>{{ $jobOrder->cropYear->name ?? '-' }}</strong>
                                    </div>
                                    <div class="col-md-6 mb-2">
                                        <label class="text-muted small d-block">Other Specifications</label>
                                        <p class="mb-0">{{ $jobOrder->other_specifications ?: '-' }}</p>
                                    </div>
                                </div>

                                @if($jobOrder->specifications && $jobOrder->specifications->count() > 0)
                                    <h6 class="font-weight-bold mb-2">Product Specifications</h6>
                                    <div class="table-responsive mb-4">
                                        <table class="table table-sm table-bordered table-striped">
                                            <thead class="thead-light">
                                                <tr>
                                                    <th>Specification Name</th>
                                                    <th>Value</th>
                                                    <th>UOM</th>
                                                    <th>Type</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($jobOrder->specifications as $spec)
                                                    <tr>
                                                        <td><strong>{{ $spec->spec_name }}</strong></td>
                                                        <td>{{ $spec->spec_value }}</td>
                                                        <td>{{ $spec->productSlabType->qc_symbol ?? ($spec->uom ?? '-') }}</td>
                                                        <td><span class="badge badge-light border">{{ strtoupper($spec->value_type) }}</span></td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @endif

                                <!-- Packing Details -->
                                @if($jobOrder->packingItems && $jobOrder->packingItems->count() > 0)
                                    <h6 class="font-weight-bold mb-2">Packing Items</h6>
                                    @foreach($jobOrder->packingItems as $pIdx => $pItem)
                                        <div class="border rounded p-3 mb-3 bg-light">
                                            <div class="row">
                                                <div class="col-md-3 mb-1">
                                                    <small class="text-muted d-block">Location</small>
                                                    <span>{{ $pItem->location->name ?? '-' }}</span>
                                                </div>
                                                <div class="col-md-3 mb-1">
                                                    <small class="text-muted d-block">Brand</small>
                                                    <span>{{ $pItem->brand->name ?? '-' }}</span>
                                                </div>
                                                <div class="col-md-3 mb-1">
                                                    <small class="text-muted d-block">Bag Product</small>
                                                    <span>{{ $pItem->bagProduct->name ?? '-' }}</span>
                                                </div>
                                                <div class="col-md-3 mb-1">
                                                    <small class="text-muted d-block">Condition / Color</small>
                                                    <span>{{ $pItem->bagCondition->name ?? '-' }} / {{ $pItem->bagColor->color ?? '-' }}</span>
                                                </div>
                                                <div class="col-md-2 mb-1">
                                                    <small class="text-muted d-block">Bag Size (kg)</small>
                                                    <span>{{ $pItem->bag_size }}</span>
                                                </div>
                                                <div class="col-md-2 mb-1">
                                                    <small class="text-muted d-block">No. of Bags</small>
                                                    <span>{{ $pItem->no_of_bags }}</span>
                                                </div>
                                                <div class="col-md-2 mb-1">
                                                    <small class="text-muted d-block">Total Bags</small>
                                                    <strong>{{ $pItem->total_bags }}</strong>
                                                </div>
                                                <div class="col-md-2 mb-1">
                                                    <small class="text-muted d-block">Total KGs</small>
                                                    <span>{{ $pItem->total_kgs }}</span>
                                                </div>
                                                <div class="col-md-2 mb-1">
                                                    <small class="text-muted d-block">Metric Tons</small>
                                                    <strong class="text-primary">{{ $pItem->metric_tons }} MT</strong>
                                                </div>
                                                <div class="col-md-2 mb-1">
                                                    <small class="text-muted d-block">Containers / Stuffing</small>
                                                    <span>{{ $pItem->no_of_containers }} / {{ $pItem->stuffing_in_container }} MT</span>
                                                </div>
                                                <div class="col-md-3 mb-1">
                                                    <small class="text-muted d-block">Delivery Date</small>
                                                    <span>{{ $pItem->delivery_date ? $pItem->delivery_date->format('d/m/Y') : '-' }}</span>
                                                </div>
                                            </div>

                                            @if($pItem->subItems && $pItem->subItems->count() > 0)
                                                <div class="mt-2">
                                                    <small class="font-weight-bold text-dark d-block mb-1">Master Packing Sub-Items:</small>
                                                    <div class="table-responsive">
                                                        <table class="table table-sm table-bordered bg-white mb-0">
                                                            <thead class="thead-light">
                                                                <tr>
                                                                    <th>Bag Product</th>
                                                                    <th>Primary Bags</th>
                                                                    <th>Packing Size</th>
                                                                    <th>No. of Bags</th>
                                                                    <th>Total Bags</th>
                                                                    <th>Attachment</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                @foreach($pItem->subItems as $sItem)
                                                                    <tr>
                                                                        <td>{{ $sItem->bagProduct->name ?? '-' }}</td>
                                                                        <td>{{ $sItem->no_of_primary_bags }}</td>
                                                                        <td>{{ $sItem->packing_size }} kg</td>
                                                                        <td>{{ $sItem->no_of_bags }}</td>
                                                                        <td>{{ $sItem->total_bags }}</td>
                                                                        <td>
                                                                            @if($sItem->attachment)
                                                                                <a href="{{ asset('storage/' . $sItem->attachment) }}" target="_blank" class="badge badge-info">View Attachment</a>
                                                                            @else
                                                                                -
                                                                            @endif
                                                                        </td>
                                                                    </tr>
                                                                @endforeach
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    @endforeach
                                @endif

                                <!-- Operational Details -->
                                <div class="card border mb-3">
                                    <div class="card-header bg-light py-2">
                                        <h6 class="mb-0 font-weight-bold">Operational Details</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-4 mb-2">
                                                <label class="text-muted small d-block">Inspection By</label>
                                                @forelse($jobOrder->inspectionCompanies() as $insp)
                                                    <span class="badge badge-light border mr-1">{{ $insp->name }}</span>
                                                @empty
                                                    <span>-</span>
                                                @endforelse
                                            </div>
                                            <div class="col-md-4 mb-2">
                                                <label class="text-muted small d-block">Arrival Locations</label>
                                                @forelse($jobOrder->arrivalLocationRecords() as $arrLoc)
                                                    <span class="badge badge-light border mr-1">{{ $arrLoc->name }}</span>
                                                @empty
                                                    <span>-</span>
                                                @endforelse
                                            </div>
                                            <div class="col-md-4 mb-2">
                                                <label class="text-muted small d-block">Loading Date</label>
                                                <span>{{ $jobOrder->loading_date ? $jobOrder->loading_date->format('d/m/Y') : '-' }}</span>
                                            </div>
                                            <div class="col-md-12 mb-0">
                                                <label class="text-muted small d-block">Packing Description</label>
                                                <p class="mb-0">{{ $jobOrder->packing_description ?: '-' }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Container Protection -->
                                @if($jobOrder->containerProtectionItems && $jobOrder->containerProtectionItems->count() > 0)
                                    <h6 class="font-weight-bold mb-2">Container Protection & Packing Materials</h6>
                                    <div class="table-responsive mb-3">
                                        <table class="table table-sm table-bordered">
                                            <thead class="thead-light">
                                                <tr>
                                                    <th>Product</th>
                                                    <th>Quantity Per Container</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($jobOrder->containerProtectionItems as $cp)
                                                    <tr>
                                                        <td>{{ $cp->name }}</td>
                                                        <td>{{ $cp->pivot->quantity_per_container ?? 0 }}</td>
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
