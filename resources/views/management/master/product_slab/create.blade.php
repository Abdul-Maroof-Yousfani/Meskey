<form action="{{ route('product-slab.store-multiple') }}" method="POST" id="ajaxSubmit" autocomplete="off">
    @csrf
    <input type="hidden" id="listRefresh" value="{{ route('get.product-slab') }}" />

    <div class="row form-mar">
        <div class="col-md-12">
            <div class="form-group">
                <label>Product:</label>
                <select class="form-control" name="product_id" id="product_id_c">
                    <option value="">Select Product</option>
                </select>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <h6 class="header-heading-sepration">Slab Types Configuration</h6>
        </div>
    </div>

    <div id="slabs-container">
        @foreach ($slab_types as $slab_type)
            <div class="slab-type-group mb-4 p-3 border rounded">
                <div class="row align-items-center">
                    <div class="col-md-1">
                        <div class="form-group">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input slab-enable-switch"
                                    id="enable_{{ $slab_type->id }}" name="slabs[{{ $slab_type->id }}][is_enabled]"
                                    value="1">
                                <label class="custom-control-label" for="enable_{{ $slab_type->id }}"></label>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-5">
                        <h5>{{ $slab_type->name }}</h5>
                        <input type="hidden" name="slabs[{{ $slab_type->id }}][product_slab_type_id]"
                            value="{{ $slab_type->id }}">
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Deduction Type:</label>
                            <select class="form-control deduction-type"
                                name="slabs[{{ $slab_type->id }}][deduction_type]">
                                <option value="kg">Kg</option>
                                <option value="amount">Amount</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="slab-ranges-container" data-slab-type="{{ $slab_type->id }}">
                    <div class="slab-range-template" style="display: none;">
                        <div class="row slab-range-row mb-2">

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Range (From - To):</label>
                                    <div class="input-group">
                                        <input type="number" step="0.01" class="form-control range-from"
                                            name="slabs[{{ $slab_type->id }}][ranges][0][from]" placeholder="From"
                                            disabled>
                                        <div class="input-group-prepend input-group-append">
                                            <span class="input-group-text">-</span>
                                        </div>
                                        <input type="number" step="0.01" class="form-control range-to"
                                            name="slabs[{{ $slab_type->id }}][ranges][0][to]" placeholder="To"
                                            disabled>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Deduction Value:</label>
                                    <input type="number" step="0.01" class="form-control deduction-value"
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

                    <div class="row slab-range-row mb-2">

                        <div class="col-md-6">
                            <div class="form-group">
                                <div class="input-group">
                                    <input type="number" step="0.01" class="form-control range-from"
                                        name="slabs[{{ $slab_type->id }}][ranges][0][from]" placeholder="From">
                                    <div class="input-group-prepend input-group-append">
                                        <span class="input-group-text">-</span>
                                    </div>
                                    <input type="number" step="0.01" class="form-control range-to"
                                        name="slabs[{{ $slab_type->id }}][ranges][0][to]" placeholder="To">
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <input type="number" step="0.01" class="form-control deduction-value"
                                    name="slabs[{{ $slab_type->id }}][ranges][0][deduction_value]" placeholder="Value">
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
    </div>

    <div class="row bottom-button-bar mt-3">
        <div class="col-12">
            <a type="button" class="btn btn-danger modal-sidebar-close position-relative top-1 closebutton">Close</a>
            <button type="submit" class="btn btn-primary submitbutton">Save All</button>
        </div>
    </div>
</form>

<script>
    $(document).ready(function() {

        function toggleSlabRanges($switch) {
            const slabTypeId = $switch.attr('id').replace('enable_', '');
            const $container = $(`.slab-ranges-container[data-slab-type="${slabTypeId}"]`);
            const $slabTypeGroup = $container.closest('.slab-type-group');

            if ($switch.is(':checked')) {
                $container.find('input, select, button').prop('disabled', false);
                $slabTypeGroup.find('select, button').prop('disabled', false);
                $container.show();
            } else {
                $container.find('input, select').prop('disabled', true);
                $slabTypeGroup.find('select, button').prop('disabled', true);
                $container.hide();
            }
        }

        $('.slab-enable-switch').each(function() {
            toggleSlabRanges($(this));
        });

        $('.slab-enable-switch').change(function() {
            toggleSlabRanges($(this));
        });

        initializeDynamicSelect2('#product_id_c', 'products', 'name', 'id', false, false);
    });
</script>
