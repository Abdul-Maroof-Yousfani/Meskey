@if($qc->items->count() > 0)
<div id="qcCommoditiesContainer">
    @php
        $commodityGroups = $qc->items->groupBy('product_id');
    @endphp
    
    @foreach($commodityGroups as $commodityId => $items)
        @php
            $commodity = $products->where('id', $commodityId)->first();
            $specs = \App\Models\Master\ProductSlab::with('slabType')
                ->where('product_id', $commodityId)
                ->where('status', 1)
                ->get()
                ->groupBy('product_slab_type_id')
                ->map(function ($slabs) {
                    $firstSlab = $slabs->first();
                    return [
                        'id' => $firstSlab->slabType->id,
                        'spec_name' => $firstSlab->slabType->name ?? '',
                        'spec_value' => $firstSlab->deduction_value ?? 0,
                        'uom' => $firstSlab->slabType->qc_symbol ?? ''
                    ];
                })
                ->values();
            
            $qcParameters = $specs->pluck('spec_name')->toArray();
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
                                    $paramSpec = $specs->where('spec_name', $parameter)->first();
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
                        @foreach($items as $index => $item)
                        <tr class="location-row">
                            <td>
                                <select name="qc_data[{{ $commodityId }}][locations][{{ $index }}][sublocation_id]" 
                                        class="form-control form-control-sm sublocation-select" required>
                                    <option value="">Select Location</option>
                                    @foreach($sublocations as $sublocation)
                                        <option value="{{ $sublocation->id }}" {{ $item->arrival_sub_location_id == $sublocation->id ? 'selected' : '' }}>
                                            {{ $sublocation->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </td>
                            <td>
                                <input type="number" 
                                       name="qc_data[{{ $commodityId }}][locations][{{ $index }}][suggested_quantity]" 
                                       class="form-control form-control-sm suggested-quantity" 
                                       step="0.01" 
                                       min="0"
                                       value="{{ $item->suggested_quantity }}"
                                       required>
                            </td>
                            @foreach($qcParameters as $parameter)
                                @php
                                    $paramField = Illuminate\Support\Str::slug($parameter, '_');
                                    
                                    $paramSpec = $specs->where('spec_name', $parameter)->first();
                                    $slab_id = $paramSpec['id'] ?? 0;
                                    $paramValue = $item->parameters->where('product_slab_type_id',  $slab_id)->first()->parameter_value ?? 0;
                                    $uom = $paramSpec['uom'] ?? '%';
                                @endphp
                                <td>
                                    <input type="number" 
                                           name="qc_data[{{ $commodityId }}][locations][{{ $index }}][parameters][{{ $slab_id }}]" 
                                           class="form-control form-control-sm param-input" 
                                           data-param="{{ $paramField }}"
                                           step="0.01" 
                                           min="0" 
                                           max="100"
                                           value="{{ $paramValue }}"
                                           placeholder="0.00">
                                    @if($uom)
                                        <!-- <small class="text-muted d-block">{{ $uom }}</small> -->
                                    @endif
                                </td>
                            @endforeach
                            <td>
                                @if($index === 0)
                                    <button type="button" class="btn btn-sm btn-danger remove-row" disabled>
                                        <i class="ft-x"></i>
                                    </button>
                                @else
                                    <button type="button" class="btn btn-sm btn-danger remove-row">
                                        <i class="ft-x"></i>
                                    </button>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-light">
                        <tr class="weighted-average-row">
                            <td><strong>Weighted Avg.</strong></td>
                            <td class="total-quantity"><strong>{{ $items->sum('suggested_quantity') }}</strong></td>
                            @foreach($qcParameters as $parameter)
                                @php
                                    $paramField = Illuminate\Support\Str::slug($parameter, '_');
                                    $weightedSum = 0;
                                    $totalQty = $items->sum('suggested_quantity');
                                    
                                    foreach ($items as $item) {
                                        $paramValue = $item->parameters->where('parameter_name', $parameter)->first()->parameter_value ?? 0;
                                        $weightedSum += $paramValue * $item->suggested_quantity;
                                    }
                                    $avg = $totalQty > 0 ? $weightedSum / $totalQty : 0;
                                @endphp
                                <td class="avg-{{ $paramField }}"><strong>{{ number_format($avg, 4) }}</strong></td>
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
                        data-specs='@json($specs)'>
                    <i class="ft-plus mr-1"></i> Add More Location
                </button>
            </div>
        </div>
        @endif
    @endforeach
</div>

<!-- Combined Weighted Average Section -->
<div class="row mt-4 d-none" id="combinedAverageSection">
    <div class="col-md-12">
        <h6 class="header-heading-sepration">Combined Weighted Average</h6>
        <div class="table-responsive">
            <table class="table table-bordered table-sm">
                <thead class="thead-dark">
                    <tr>
                        <th width="20%">Parameter</th>
                        <!-- Common parameters will be dynamically added -->
                    </tr>
                </thead>
                <tbody>
                    <tr class="bg-light">
                        <td><strong>Combined Weighted Avg.</strong></td>
                        <!-- Average values will be dynamically added -->
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

@else
<div class="alert alert-warning">
    <i class="ft-info mr-1"></i>
    No QC items found for this record.
</div>
@endif