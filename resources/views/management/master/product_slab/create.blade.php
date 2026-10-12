<form action="{{ route('product-slab.store-multiple') }}" method="POST" id="ajaxSubmit" autocomplete="off">
    @csrf
    <input type="hidden" id="listRefresh" value="{{ route('get.product-slab') }}" />
    <div class="alert alert-info mb-3">
        <ul class="mb-0">
            <li><strong>Is Tiered:</strong> When enabled, calculations will be performed cumulatively across ranges.
            </li>
            <li><strong>Is PO Field:</strong> When enabled, these fields will be shown during purchase contract creation
                for this commodity.</li>
        </ul>
    </div>
    <div class="row form-mar">
        <div class="col-md-6">
            <div class="form-group">
                <label>Product: <span class="text-danger">*</span></label>
                <select class="form-control select2" name="product_id" id="product_id_c" required>
                    <option value="">Select Product</option>
                    @foreach ($products as $product)
                        <option value="{{ $product->id }}">{{ $product->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label>Reference Slab: <small class="text-muted">(Optional - Details clone karne ke liye)</small></label>
                <select class="form-control select2" id="reference_slab_id">
                    <option value="">Select Reference Slab</option>
                    @foreach ($referenceSlabs as $slab)
                        <option value="{{ $slab->product_id }}">{{ $slab->product->name ?? 'N/A' }} (Slab)</option>
                    @endforeach
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
        @include('management.master.product_slab.partials.slabs_container', [
            'slab_types' => $slab_types,
            'productSlabs' => collect()
        ])
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
        $('.select2').select2();

        function toggleSlabRanges($switch) {
            const slabTypeId = $switch.attr('id').replace('enable_', '');
            const $container = $(`.slab-ranges-container[data-slab-type="${slabTypeId}"]`);
            const $slabTypeGroup = $container.closest('.slab-type-group');

            if ($switch.is(':checked')) {
                $container.find('input, select, button').prop('disabled', false);
                $container.find('.slab-range-template input, .slab-range-template select').prop('disabled', true);
                $slabTypeGroup.find('select, button, .tiered').prop('disabled', false);
                $container.show();
            } else {
                $container.find('input, select').prop('disabled', true);
                $slabTypeGroup.find('select, button, .tiered').prop('disabled', true);
                $container.hide();
            }
        }

        function initAllSwitches() {
            $('#slabs-container .slab-enable-switch').each(function() {
                toggleSlabRanges($(this));
            });
        }

        initAllSwitches();

        // Delegated change listener for switches so dynamic slabs work seamlessly
        $(document).on('change', '.slab-enable-switch', function() {
            toggleSlabRanges($(this));
        });

        // AJAX handler for Reference Slab cloning
        $('#reference_slab_id').on('change', function() {
            var refSlabId = $(this).val();
            var url = "{{ route('product-slab.get-reference-slabs') }}" + (refSlabId ? '/' + refSlabId : '');

            $('#slabs-container').css('opacity', '0.5');

            $.ajax({
                url: url,
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.success && response.html) {
                        $('#slabs-container').html(response.html);
                        initAllSwitches();
                    }
                },
                error: function(xhr) {
                    console.error('Error fetching reference slab details:', xhr);
                },
                complete: function() {
                    $('#slabs-container').css('opacity', '1');
                }
            });
        });
    });
</script>
