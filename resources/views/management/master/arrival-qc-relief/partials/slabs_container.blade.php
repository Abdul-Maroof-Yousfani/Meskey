@foreach ($slab_types as $slab_type)
    @php
        $config = $configuredData[$slab_type->name] ?? null;
        $isEnabled = !empty($config['is_enabled']);
        $ranges = $config['ranges'] ?? [
            ['from' => '', 'to' => '', 'relief_type' => 'fixed', 'relief_value' => '']
        ];
    @endphp

    <div class="slab-type-group mb-4 p-3 border rounded shadow-sm bg-white" data-slab-id="{{ $slab_type->id }}">
        <!-- Header row of Slab Type -->
        <div class="row align-items-center">
            <div class="col-auto">
                <div class="custom-control custom-switch custom-switch-lg">
                    <input type="checkbox" class="custom-control-input slab-enable-switch"
                        id="enable_{{ $slab_type->id }}"
                        name="parameters[{{ $slab_type->id }}][is_enabled]"
                        value="1" {{ $isEnabled ? 'checked' : '' }}>
                    <label class="custom-control-label" for="enable_{{ $slab_type->id }}"></label>
                </div>
            </div>

            <div class="col">
                <div class="d-flex align-items-center">
                    <h5 class="mb-0 font-weight-bold text-dark">{{ $slab_type->name }}</h5>
                    <span class="badge {{ $isEnabled ? 'badge-success' : 'badge-secondary' }} ml-2 status-pill font-small-2">
                        {{ $isEnabled ? 'Active Relief' : 'Inactive' }}
                    </span>
                </div>
                <input type="hidden" name="parameters[{{ $slab_type->id }}][slab_type_id]" value="{{ $slab_type->id }}">
                <input type="hidden" name="parameters[{{ $slab_type->id }}][name]" value="{{ $slab_type->name }}">
            </div>
        </div>

        <!-- Range Rows Container -->
        <div class="slab-ranges-container mt-3" data-slab-type="{{ $slab_type->id }}"
            {{ !$isEnabled ? 'style="display: none;"' : '' }}>

            <!-- Header Row for Labels -->
            <div class="row slab-header-labels mb-2 text-uppercase font-weight-bold text-muted small d-none d-md-flex px-1">
                <div class="col-md-5">
                    <i class="ft-sliders mr-1 text-primary"></i> Range (From - To)
                </div>
                <div class="col-md-3">
                    <i class="ft-check-circle mr-1 text-info"></i> Type
                </div>
                <div class="col-md-3">
                    <i class="ft-target mr-1 text-success"></i> Value (Fixed To / Minus Amount)
                </div>
                <div class="col-md-1 text-center">
                    Action
                </div>
            </div>

            <!-- Hidden Template for Cloning -->
            <div class="slab-range-template" style="display: none;">
                <div class="row slab-range-row mb-2 align-items-center p-2 rounded border bg-light-neutral">
                    <div class="col-md-5 col-12 mb-2 mb-md-0">
                        <label class="d-md-none small font-weight-bold text-muted">Range (From - To):</label>
                        <div class="input-group">
                            <input type="number" step="0.01" class="form-control range-from"
                                name="parameters[{{ $slab_type->id }}][ranges][INDEX_HOLDER][from]"
                                placeholder="From (e.g. 36.00)" disabled>
                            <div class="input-group-prepend input-group-append">
                                <span class="input-group-text bg-white px-3 font-weight-bold">-</span>
                            </div>
                            <input type="number" step="0.01" class="form-control range-to"
                                name="parameters[{{ $slab_type->id }}][ranges][INDEX_HOLDER][to]"
                                placeholder="To (e.g. 39.00)" disabled>
                        </div>
                    </div>

                    <div class="col-md-3 col-6 mb-2 mb-md-0">
                        <label class="d-md-none small font-weight-bold text-muted">Type:</label>
                        <select class="form-control relief-type-select"
                            name="parameters[{{ $slab_type->id }}][ranges][INDEX_HOLDER][relief_type]" disabled>
                            <option value="fixed" selected>Fixed</option>
                            <option value="minus_amount">Minus Amount</option>
                        </select>
                    </div>

                    <div class="col-md-3 col-6 mb-2 mb-md-0">
                        <label class="d-md-none small font-weight-bold text-muted relief-val-label">Value (Fixed To):</label>
                        <div class="input-group">
                            <input type="number" step="0.01" class="form-control relief-val"
                                name="parameters[{{ $slab_type->id }}][ranges][INDEX_HOLDER][relief_value]"
                                placeholder="Fixed To (e.g. 35.00)" disabled>
                        </div>
                    </div>

                    <div class="col-md-1 col-12 text-center">
                        <button type="button" class="btn btn-outline-danger btn-sm remove-range-btn py-1 px-2" title="Delete range row">
                            <i class="ft-x"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Existing or Initial Range Rows -->
            <div class="slab-ranges-list">
                @foreach ($ranges as $index => $range)
                    @php
                        $rType = $range['relief_type'] ?? 'fixed';
                        $rVal = $range['relief_value'] ?? ($range['fixed_value'] ?? '');
                    @endphp
                    <div class="row slab-range-row mb-2 align-items-center p-2 rounded border bg-light-neutral">
                        <div class="col-md-5 col-12 mb-2 mb-md-0">
                            <label class="d-md-none small font-weight-bold text-muted">Range (From - To):</label>
                            <div class="input-group">
                                <input type="number" step="0.01" class="form-control range-from"
                                    name="parameters[{{ $slab_type->id }}][ranges][{{ $index }}][from]"
                                    placeholder="From (e.g. 36.00)" value="{{ $range['from'] }}"
                                    {{ !$isEnabled ? 'disabled' : '' }}>
                                <div class="input-group-prepend input-group-append">
                                    <span class="input-group-text bg-white px-3 font-weight-bold">-</span>
                                </div>
                                <input type="number" step="0.01" class="form-control range-to"
                                    name="parameters[{{ $slab_type->id }}][ranges][{{ $index }}][to]"
                                    placeholder="To (e.g. 39.00)" value="{{ $range['to'] }}"
                                    {{ !$isEnabled ? 'disabled' : '' }}>
                            </div>
                        </div>

                        <div class="col-md-3 col-6 mb-2 mb-md-0">
                            <label class="d-md-none small font-weight-bold text-muted">Type:</label>
                            <select class="form-control relief-type-select"
                                name="parameters[{{ $slab_type->id }}][ranges][{{ $index }}][relief_type]"
                                {{ !$isEnabled ? 'disabled' : '' }}>
                                <option value="fixed" {{ $rType === 'fixed' ? 'selected' : '' }}>Fixed</option>
                                <option value="minus_amount" {{ $rType === 'minus_amount' ? 'selected' : '' }}>Minus Amount</option>
                            </select>
                        </div>

                        <div class="col-md-3 col-6 mb-2 mb-md-0">
                            <label class="d-md-none small font-weight-bold text-muted relief-val-label">
                                {{ $rType === 'minus_amount' ? 'Minus Amount:' : 'Fixed To:' }}
                            </label>
                            <div class="input-group">
                                <input type="number" step="0.01" class="form-control relief-val"
                                    name="parameters[{{ $slab_type->id }}][ranges][{{ $index }}][relief_value]"
                                    placeholder="{{ $rType === 'minus_amount' ? 'Minus Amount (e.g. 2.00)' : 'Fixed To (e.g. 35.00)' }}"
                                    value="{{ $rVal }}"
                                    {{ !$isEnabled ? 'disabled' : '' }}>
                            </div>
                        </div>

                        <div class="col-md-1 col-12 text-center">
                            <button type="button" class="btn btn-outline-danger btn-sm remove-range-btn py-1 px-2"
                                title="Delete range row" {{ !$isEnabled ? 'disabled' : '' }}>
                                <i class="ft-x"></i>
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Bottom Add Range row for convenience -->
            <div class="row mt-2">
                <div class="col-12 text-right">
                    <button type="button" class="btn btn-sm btn-primary add-range-btn"
                        data-slab-type="{{ $slab_type->id }}">
                        <i class="ft-plus mr-1"></i> Add Range
                    </button>
                </div>
            </div>
        </div>
    </div>
@endforeach
