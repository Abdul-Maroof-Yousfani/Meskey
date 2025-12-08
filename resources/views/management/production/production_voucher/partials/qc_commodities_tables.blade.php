@if(isset($commodities) && count($commodities) > 0)
<div id="qcCommoditiesContainer">
    @foreach($commodities as $commodityId)
        @php
            $commodity = $products->where('id', $commodityId)->first();
            $commodityParams = $commodityParameters[$commodityId] ?? [];
            $qcParameters = $commodityParams['parameters'] ?? [];
            $specsData = $commodityParams['specs_data'] ?? [];
        @endphp
        
        @if($commodity && count($qcParameters) > 0)
        <div class="commodity-table mb-4 border p-3 rounded" data-commodity-id="{{ $commodityId }}">
            <!-- Commodity Header -->
            <h6 class="text-primary mb-3">
                <i class="ft-box mr-1"></i> {{ $commodity->name }}
            </h6>
            
            <!-- QC Table -->
            <div class="table-responsive">
                <table class="table table-bordered table-sm commodity-data-table">
                    <thead class="thead-dark">
                        <tr>
                            <th width="20%">Location</th>
                            <th width="15%">Sugg. QTY (kgs)</th>
                            @foreach($qcParameters as $parameter)
                                @php
                                    $paramSpec = $specsData->where('spec_name', $parameter)->first();
                                    $uom = $paramSpec['uom'] ?? '%';
                                @endphp
                                <th width="{{ 65 / count($qcParameters) }}%">
                                    {{ $parameter }} 
                                    @if($uom)
                                        <br><small class="text-muted">({{ $uom }})</small>
                                    @endif
                                </th>
                            @endforeach
                            <th width="10%">Action</th>
                        </tr>
                    </thead>
                    <tbody id="commodity-{{ $commodityId }}-tbody">
                        <!-- First row -->
                        <tr class="location-row">
                            <td>
                                <select name="qc_data[{{ $commodityId }}][locations][0][sublocation_id]" 
                                        class="form-control form-control-sm sublocation-select" required>
                                    <option value="">Select Location</option>
                                    @foreach($sublocations as $sublocation)
                                        <option value="{{ $sublocation->id }}">{{ $sublocation->name }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td>
                                <input type="number" 
                                       name="qc_data[{{ $commodityId }}][locations][0][suggested_quantity]" 
                                       class="form-control form-control-sm suggested-quantity" 
                                       step="0.01" 
                                       min="0"
                                       required>
                            </td>
                            @foreach($qcParameters as $parameter)
                                @php
                                    $paramField = Illuminate\Support\Str::slug($parameter, '_');
                                    $paramSpec = $specsData->where('spec_name', $parameter)->first();
                                    $defaultValue = $paramSpec['spec_value'] ?? 0;
                                    $slab_id = $paramSpec['id'] ?? 0;
                                    $uom = $paramSpec['uom'] ?? '%';
                                @endphp
                                <td>
                                    <input type="number" 
                                           name="qc_data[{{ $commodityId }}][locations][0][parameters][{{ $slab_id }}]" 
                                           class="form-control form-control-sm param-input" 
                                           data-param="{{ $paramField }}"
                                           step="0.01" 
                                           min="0" 
                                           max="100"
                                           value="{{ $defaultValue }}"
                                           placeholder="0.00">
                                    @if($uom)
                                        <small class="text-muted">{{ $uom }}</small>
                                    @endif
                                </td>
                            @endforeach
                            <td>
                                <button type="button" class="btn btn-sm btn-danger remove-row" disabled>
                                    <i class="ft-x"></i>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                    <tfoot class="bg-light">
                        <tr class="weighted-average-row">
                            <td><strong>Weighted Avg.</strong></td>
                            <td class="total-quantity"><strong>0</strong></td>
                            @foreach($qcParameters as $parameter)
                                @php
                                    $paramField = Illuminate\Support\Str::slug($parameter, '_');
                                @endphp
                                <td class="avg-{{ $paramField }}"><strong>0.0000</strong></td>
                            @endforeach
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            
            <!-- Add More Button -->
            <div class="text-right mt-2">
                <button type="button" 
                        class="btn btn-sm btn-success add-location-row" 
                        data-commodity-id="{{ $commodityId }}"
                        data-parameters='@json($qcParameters)'
                        data-specs='@json($specsData)'>
                    <i class="ft-plus mr-1"></i> Add More Location
                </button>
            </div>
        </div>
        @endif
    @endforeach
</div>

<!-- Combined Weighted Average Section - Only show common parameters -->
@php
    // Find common parameters across all commodities for combined average
    $allParameters = [];
    foreach ($commodities as $commodityId) {
        $commodityParams = $commodityParameters[$commodityId] ?? [];
        $params = $commodityParams['parameters'] ?? [];
        $allParameters[] = $params;
    }
    
    $commonParameters = [];
    if (!empty($allParameters)) {
        $commonParameters = call_user_func_array('array_intersect', $allParameters);
    }
@endphp

@if(count($commonParameters) > 0)
<!-- <div class="row mt-4" id="combinedAverageSection">
    <div class="col-md-12">
        <h6 class="header-heading-sepration">Combined Weighted Average</h6>
        <div class="table-responsive">
            <table class="table table-bordered table-sm">
                <thead class="thead-dark">
                    <tr>
                        <th width="20%">Parameter</th>
                        @foreach($commonParameters as $parameter)
                            <th width="{{ 80 / count($commonParameters) }}%">{{ $parameter }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    <tr class="bg-light">
                        <td><strong>Combined Weighted Avg.</strong></td>
                        @foreach($commonParameters as $parameter)
                            @php
                                $paramField = Illuminate\Support\Str::slug($parameter, '_');
                            @endphp
                            <td class="combined-avg-{{ $paramField }}"><strong>0.0000</strong></td>
                        @endforeach
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div> -->
@endif

@else
<div class="alert alert-warning">
    <i class="ft-info mr-1"></i>
    Please select at least one commodity to display QC tables.
</div>
@endif

<style>
.commodity-table {
    background: #f8f9fa;
    border-radius: 5px;
}
.commodity-table table {
    margin-bottom: 0;
}
.commodity-table .table th {
    font-size: 12px;
    font-weight: 600;
}
.commodity-table .table td {
    font-size: 12px;
    vertical-align: middle;
}
.param-input {
    text-align: center;
    font-weight: 500;
}
.weighted-average-row td {
    background-color: #e9ecef !important;
    font-weight: 600;
}
</style>