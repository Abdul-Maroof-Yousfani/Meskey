<form action="{{ route('export-product-slab.update-multiple', $product->id) }}" method="POST" id="ajaxSubmit" autocomplete="off">
    @csrf
    @method('PUT')
    <input type="hidden" id="listRefresh" value="{{ route('get.export-product-slab') }}" />
    <input type="hidden" name="product_id" value="{{ $product->id }}" />
    <input type="hidden" name="company_id" value="{{ auth()->user()->current_company_id }}" />

    <div class="row form-mar">
        <div class="col-md-12">
            <div class="form-group">
                <label>Product:</label>
                <select class="form-control" disabled>
                    <option value="{{ $product->id }}" selected>{{ $product->name }}</option>
                </select>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <h6 class="header-heading-sepration">Export Slab Configuration</h6>
        </div>
    </div>

    <div id="slabs-container">
        @foreach ($slab_types as $slab_type)
            @php
                $slabsForType = $productSlabs->where('product_slab_type_id', $slab_type->id);
                $isEnabled = $slabsForType->contains(fn($slab) => (bool) $slab->is_export_enable);
                $prefillSpecValue = optional($slabsForType->firstWhere('is_export_enable', true) ?? $slabsForType->first())->prefill_spec_value;
            @endphp
            <div class="slab-type-group mb-4 p-3 border rounded">
                <div class="row align-items-center">
                    <div class="col-md-1">
                        <div class="form-group">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input slab-enable-switch" id="enable_{{ $slab_type->id }}" name="slabs[{{ $slab_type->id }}][is_export_enable]" value="1" @checked($isEnabled)>
                                <label class="custom-control-label" for="enable_{{ $slab_type->id }}"></label>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-5">
                        <h5>{{ $slab_type->name }}</h5>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group mb-0">
                            <label>Prefill Value:</label>
                            <input type="number" step="0.01" class="form-control" name="slabs[{{ $slab_type->id }}][prefill_spec_value]" value="{{ $prefillSpecValue }}" placeholder="Optional prefill value">
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row bottom-button-bar mt-3">
        <div class="col-12">
            <a type="button" class="btn btn-danger modal-sidebar-close position-relative top-1 closebutton">Close</a>
            <button type="submit" class="btn btn-primary submitbutton">Update All</button>
        </div>
    </div>
</form>
