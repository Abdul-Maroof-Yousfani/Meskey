<form action="{{ route('export-product-slab.store-multiple') }}" method="POST" id="ajaxSubmit" autocomplete="off">
    @csrf
    <input type="hidden" id="listRefresh" value="{{ route('get.export-product-slab') }}" />
    <input type="hidden" name="company_id" value="{{ auth()->user()->current_company_id }}" />

    <div class="row form-mar">
        <div class="col-md-12">
            <div class="form-group">
                <label>Product:</label>
                <select class="form-control select2" name="product_id" id="product_id_c">
                    <option value="">Select Product</option>
                    @foreach ($products as $product)
                        <option value="{{ $product->id }}">{{ $product->name }}</option>
                    @endforeach
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
            <div class="slab-type-group mb-4 p-3 border rounded">
                <div class="row align-items-center">
                    <div class="col-md-1">
                        <div class="form-group">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input slab-enable-switch" id="enable_{{ $slab_type->id }}" name="slabs[{{ $slab_type->id }}][is_export_enable]" value="1">
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
                            <input type="number" step="0.01" class="form-control" name="slabs[{{ $slab_type->id }}][prefill_spec_value]" placeholder="Optional prefill value" readonly>
                        </div>
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
        $('.select2').select2();

        $(document).on('change', '.slab-enable-switch', function() {
            var isChecked = $(this).is(':checked');
            var $input = $(this).closest('.slab-type-group').find('input[name*="[prefill_spec_value]"]');
            $input.prop('readonly', !isChecked);
        });

        $('#product_id_c').on('change', function() {
            var productId = $(this).val();

            // Reset all switches and inputs first
            $('.slab-enable-switch').prop('checked', false);
            $('input[name*="[prefill_spec_value]"]').val('').prop('readonly', true);

            if (!productId) {
                return;
            }

            var url = "{{ route('export-product-slab.by-product', ':id') }}".replace(':id', productId);
            $.ajax({
                url: url,
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.success && response.slabs && response.slabs.length > 0) {
                        response.slabs.forEach(function(slab) {
                            var slabTypeId = slab.product_slab_type_id;
                            var isEnabled = Boolean(slab.is_export_enable);
                            var prefillValue = slab.prefill_spec_value;

                            var $switch = $('#enable_' + slabTypeId);
                            var $input = $('input[name="slabs[' + slabTypeId + '][prefill_spec_value]"]');

                            $switch.prop('checked', isEnabled);
                            $input.prop('readonly', !isEnabled);
                            if (prefillValue !== null && prefillValue !== undefined) {
                                $input.val(prefillValue);
                            }
                        });
                    }
                },
                error: function(xhr) {
                    console.error('Error fetching export slabs for product:', xhr);
                }
            });
        });
    });
</script>
