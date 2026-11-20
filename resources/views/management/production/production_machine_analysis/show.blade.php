<div class="row">
    <!-- Header Information -->
    <div class="col-md-12">
        <h6 class="header-heading-sepration">Machine Analysis Information</h6>
        <div class="row">
            <div class="col-md-3">
                <div class="form-group">
                    <label>Date:</label>
                    <p class="form-control-static">{{ \Carbon\Carbon::parse($item->analysis_date)->format('d-m-Y') }}</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>Company Location:</label>
                    <p class="form-control-static">{{ $item->companyLocation->name ?? 'N/A' }}</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>Factory Location:</label>
                    <p class="form-control-static">{{ $item->arrivalLocation->name ?? 'N/A' }}</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>Plant:</label>
                    <p class="form-control-static">{{ $item->plant->name ?? 'N/A' }}</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>Job Order:</label>
                    <p class="form-control-static">{{ $item->jobOrders->pluck('job_order_no')->implode(', ') ?: ($item->analysisRequest?->voucher_display ?? 'N/A') }}</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>Product (Commodity):</label>
                    <p class="form-control-static">{{ $item->product->name ?? 'N/A' }}</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>Machine:</label>
                    <p class="form-control-static">{{ $item->machine->name ?? 'N/A' }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Input / Output Analysis Tabs -->
    <div class="col-md-12 mt-4">
        @php
            $showTabs = ['input' => 'Input Analysis', 'output' => 'Output Analysis'];
        @endphp
        <ul class="nav nav-tabs" role="tablist">
            @foreach($showTabs as $type => $label)
                <li class="nav-item">
                    <a class="nav-link {{ $loop->first ? 'active' : '' }}" data-toggle="tab" href="#machineQcShow_{{ $type }}" role="tab">
                        <i class="{{ $type === 'input' ? 'ft-log-in' : 'ft-log-out' }} mr-1"></i>{{ $label }}
                    </a>
                </li>
            @endforeach
        </ul>
        <div class="tab-content">
            @foreach($showTabs as $type => $label)
                @php
                    $typeItems = $item->items->filter(fn($it) => ($it->analysis_type ?: 'input') === $type);
                @endphp
                <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}" id="machineQcShow_{{ $type }}" role="tabpanel">
                    <p class="mt-3 mb-2"><strong>Product:</strong> {{ $typeItems->first()?->product?->name ?? ($typeItems->isNotEmpty() ? ($item->product->name ?? 'N/A') : '-') }}</p>
                    <h6 class="header-heading-sepration">QC Checklist ({{ $label }})</h6>
                    @if($typeItems->isEmpty())
                        <div class="alert alert-light border m-0">No {{ strtolower($label) }} recorded.</div>
                    @else
                        <div class="table-responsive" style="overflow-x: auto;">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th style="min-width: 150px;">Time</th>
                                        <th style="min-width: 150px;">Unit</th>
                                        @foreach($productSlabTypes as $productSlabType)
                                            <th style="min-width: 150px;">{{ $productSlabType->name }} {{ $productSlabType->qc_symbol }}</th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($typeItems as $analysisItem)
                                        <tr>
                                            <td>{{ \Carbon\Carbon::parse($analysisItem->analysis_time)->format('h:i A') }}</td>
                                            <td>{{ $analysisItem->unit->name ?? '-' }}</td>
                                            @foreach($productSlabTypes as $productSlabType)
                                                <td>
                                                    {{ $analysisItem->slabs->where('slab_type_id', $productSlabType->id)->first()->analysis_value ?? '-' }}
                                                </td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>

    <!-- Remarks Section -->
    <div class="col-md-12 mt-4">
        <div class="form-group">
            <label>Remarks:</label>
            <p class="form-control-static text-justify">{{ $item->remarks ?? 'No remarks provided.' }}</p>
        </div>
    </div>
</div>

<div class="row bottom-button-bar mt-4">
    <div class="col-12 text-right">
        <button type="button" class="btn btn-danger modal-sidebar-close closebutton">Close</button>
    </div>
</div>
