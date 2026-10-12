@foreach ($slab_types as $slab_type)
    @php
        $slabsForType = isset($productSlabs) ? $productSlabs->where('product_slab_type_id', $slab_type->id) : collect();
        $isEnabled = $slabsForType->count() > 0;
        $deductionType = $slabsForType->first() ? $slabsForType->first()->deduction_type : 'amount';
        $isTiered = $slabsForType->first() ? $slabsForType->first()->is_tiered : false;
        $isPurchaseField = $slabsForType->first() ? $slabsForType->first()->is_purchase_field : false;
    @endphp
    <div class="slab-type-group mb-4 p-3 border rounded">
        <div class="row align-items-center">
            <div class="col-md-1">
                <div class="form-group">
                    <div class="custom-control custom-switch">
                        <input type="checkbox" class="custom-control-input slab-enable-switch"
                            id="enable_{{ $slab_type->id }}" name="slabs[{{ $slab_type->id }}][is_enabled]"
                            value="1" {{ $isEnabled ? 'checked' : '' }}>
                        <label class="custom-control-label" for="enable_{{ $slab_type->id }}"></label>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <h5>{{ $slab_type->name }}</h5>
                <input type="hidden" name="slabs[{{ $slab_type->id }}][product_slab_type_id]"
                    value="{{ $slab_type->id }}">
            </div>
            <div class="col-md-8">
                <div class="row w-100 mx-auto">
                    <div class="col-4">
                        <div class="form-group">
                            <label>Deduction Type:</label>
                            <select class="form-control deduction-type"
                                name="slabs[{{ $slab_type->id }}][deduction_type]">
                                <option value="amount" {{ $deductionType == 'amount' ? 'selected' : '' }}>Amount</option>
                                <option value="kg" {{ $deductionType == 'kg' ? 'selected' : '' }}>Kg</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="form-group">
                            <label>Is Tiered:</label>
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input tiered"
                                    id="is_tiered_{{ $slab_type->id }}"
                                    name="slabs[{{ $slab_type->id }}][is_tiered]" {{ $isTiered ? 'checked' : '' }}>
                                <label class="custom-control-label"
                                    for="is_tiered_{{ $slab_type->id }}"></label>
                            </div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="form-group">
                            <label>Is PO Field:</label>
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input tiered"
                                    id="is_purchase_{{ $slab_type->id }}"
                                    name="slabs[{{ $slab_type->id }}][is_purchase_field]" {{ $isPurchaseField ? 'checked' : '' }}>
                                <label class="custom-control-label"
                                    for="is_purchase_{{ $slab_type->id }}"></label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="slab-ranges-container" data-slab-type="{{ $slab_type->id }}" {{ !$isEnabled ? 'style="display: none;"' : '' }}>
            <div class="slab-range-template" style="display: none;">
                <div class="row slab-range-row mb-2">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Range (From - To):</label>
                            <div class="input-group">
                                <input type="number" step="0.00001" class="form-control range-from"
                                    name="slabs[{{ $slab_type->id }}][ranges][0][from]" placeholder="From">
                                <div class="input-group-prepend input-group-append">
                                    <span class="input-group-text">-</span>
                                </div>
                                <input type="number" step="0.00001" class="form-control range-to"
                                    name="slabs[{{ $slab_type->id }}][ranges][0][to]" placeholder="To">
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Deduction Value:</label>
                            <input type="number" step="0.00001" class="form-control deduction-value"
                                name="slabs[{{ $slab_type->id }}][ranges][0][deduction_value]"
                                placeholder="Value">
                        </div>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="button" class="btn btn-danger btn-sm remove-range"
                            style="margin-bottom: 15px;">
                            <i class="ft-x"></i>
                        </button>
                    </div>
                </div>
            </div>

            @if ($slabsForType->count() > 0)
                @foreach ($slabsForType as $index => $slab)
                    <div class="row slab-range-row mb-2">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Range (From - To):</label>
                                <div class="input-group">
                                    <input type="number" step="0.00001" class="form-control range-from"
                                        name="slabs[{{ $slab_type->id }}][ranges][{{ $index }}][from]"
                                        placeholder="From" value="{{ $slab->from }}">
                                    <div class="input-group-prepend input-group-append">
                                        <span class="input-group-text">-</span>
                                    </div>
                                    <input type="number" step="0.00001" class="form-control range-to"
                                        name="slabs[{{ $slab_type->id }}][ranges][{{ $index }}][to]"
                                        placeholder="To" value="{{ $slab->to }}">
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Deduction Value:</label>
                                <input type="number" step="0.00001" class="form-control deduction-value"
                                    name="slabs[{{ $slab_type->id }}][ranges][{{ $index }}][deduction_value]"
                                    placeholder="Value" value="{{ $slab->deduction_value }}">
                            </div>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="button" class="btn btn-danger btn-sm remove-range"
                                style="margin-bottom: 15px;">
                                <i class="ft-x"></i>
                            </button>
                        </div>
                    </div>
                @endforeach
            @else
                <div class="row slab-range-row mb-2">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Range (From - To):</label>
                            <div class="input-group">
                                <input type="number" step="0.00001" class="form-control range-from"
                                    name="slabs[{{ $slab_type->id }}][ranges][0][from]" placeholder="From">
                                <div class="input-group-prepend input-group-append">
                                    <span class="input-group-text">-</span>
                                </div>
                                <input type="number" step="0.00001" class="form-control range-to"
                                    name="slabs[{{ $slab_type->id }}][ranges][0][to]" placeholder="To">
                                </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Deduction Value:</label>
                            <input type="number" step="0.00001" class="form-control deduction-value"
                                name="slabs[{{ $slab_type->id }}][ranges][0][deduction_value]"
                                placeholder="Value">
                        </div>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="button" class="btn btn-danger btn-sm remove-range"
                            style="margin-bottom: 15px;">
                            <i class="ft-x"></i>
                        </button>
                    </div>
                </div>
            @endif
        </div>

        <div class="row">
            <div class="col-md-12 text-right">
                <button type="button" class="btn btn-primary btn-sm add-range"
                    data-slab-type="{{ $slab_type->id }}">
                    <i class="ft-plus"></i> Add Range
                </button>
            </div>
        </div>
    </div>
@endforeach
