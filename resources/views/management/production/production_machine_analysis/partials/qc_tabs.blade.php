{{--
    Input / Output tabs, each with its own product and QC Checklist.
    Input product: RM QC products of the request (or all products), changeable.
    Output product: fixed to the job order product of the request.
    Expects: $units, $tabData (from the controller's tabProductData())
    Optional (edit): $tabItems = ['input' => item|null, 'output' => item|null]
--}}
@php
    $tabs = ['input' => 'Input Analysis', 'output' => 'Output Analysis'];
    $tabItems = $tabItems ?? [];
@endphp

@include('management.production.partials.analysis_product_options_script')

<ul class="nav nav-tabs machine-qc-tabs" role="tablist">
    @foreach($tabs as $type => $label)
        <li class="nav-item">
            <a class="nav-link {{ $loop->first ? 'active' : '' }}" data-toggle="tab" href="#machineQcTab_{{ $type }}" role="tab">
                <i class="{{ $type === 'input' ? 'ft-log-in' : 'ft-log-out' }} mr-1"></i>{{ $label }}
            </a>
        </li>
    @endforeach
</ul>

<div class="tab-content machine-qc-tab-content">
    @foreach($tabs as $type => $label)
        @php
            $index = $loop->index;
            $tabItem = $tabItems[$type] ?? null;
            $savedValues = $tabItem ? $tabItem->slabs->pluck('analysis_value', 'slab_type_id') : collect();
            $time = $tabItem ? \Carbon\Carbon::parse($tabItem->analysis_time)->format('H:i:s') : date('H:i:s');
            $unitId = $tabItem?->unit_id ?? $units->first()?->id ?? '';
            $tabProducts = $type === 'input' ? $tabData['inputProducts'] : $tabData['outputProducts'];
            $selectedProductId = $tabData['productIds'][$type] ?? null;
            $isFixed = $type === 'output' && $tabData['outputFixed'];
        @endphp
        <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}" id="machineQcTab_{{ $type }}" role="tabpanel">
            <input type="hidden" name="items[{{ $index }}][analysis_type]" value="{{ $type }}">
            <input type="hidden" name="items[{{ $index }}][time]" value="{{ $time }}">
            <input type="hidden" name="items[{{ $index }}][unit_id]" value="{{ $unitId }}">

            <div class="row mt-3">
                <div class="col-md-4">
                    <div class="form-group mb-2">
                        <label>Product (Commodity): @if($type === 'input')<span class="text-danger">*</span>@endif
                            @if($type === 'input')
                                <span class="badge badge-info ml-1 machine-rmqc-badge {{ $tabData['fromRmQc'] ? '' : 'd-none' }}" title="Products of this job order's RM QC">
                                    <i class="ft-check-square"></i> From RM QC
                                </span>
                            @else
                                <span class="badge badge-warning ml-1 machine-output-fixed-badge {{ $isFixed ? '' : 'd-none' }}">
                                    <i class="ft-lock"></i> Job Order Product
                                </span>
                            @endif
                        </label>
                        <select name="items[{{ $index }}][product_id]" class="form-control select2 machine-tab-product"
                                data-type="{{ $type }}" {{ $type === 'input' ? 'required' : '' }} {{ $isFixed ? 'disabled' : '' }}>
                            <option value="">Select Product</option>
                            @foreach($tabProducts as $product)
                                <option value="{{ $product->id }}" @selected($selectedProductId == $product->id)>{{ $product->name }}</option>
                            @endforeach
                        </select>
                        @if($type === 'output')
                            <input type="hidden" name="items[{{ $index }}][product_id]" class="machine-output-product-hidden"
                                   value="{{ $isFixed ? $selectedProductId : '' }}" {{ $isFixed ? '' : 'disabled' }}>
                        @endif
                    </div>
                </div>
            </div>

            <h6 class="header-heading-sepration font-weight-bold mb-3">QC Checklist ({{ $label }})</h6>

            <div class="striped-rows qc-checklist-container" data-type="{{ $type }}" data-index="{{ $index }}">
                @forelse($tabData['slabTypes'][$type] as $productSlabType)
                    <div class="form-group row qc-slab-row">
                        <label class="col-md-3 label-control">{{ $productSlabType->name }}</label>
                        <div class="col-md-9">
                            <div class="input-group">
                                <input type="text"
                                       name="items[{{ $index }}][params][{{ $productSlabType->id }}]"
                                       data-slab-id="{{ $productSlabType->id }}"
                                       class="form-control qc-slab-input"
                                       value="{{ $savedValues[$productSlabType->id] ?? '' }}"
                                       placeholder="{{ $productSlabType->qc_symbol }}">
                                <div class="input-group-append">
                                    <span class="input-group-text">{{ $productSlabType->qc_symbol }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="alert alert-warning m-2">No Slabs Found</div>
                @endforelse
            </div>
        </div>
    @endforeach
</div>

<style>
    .machine-qc-tabs .nav-link { font-weight: 600; }
    .qc-checklist-container .form-group { margin: 0; padding: 10px 15px; display: flex; align-items: center; }
    .qc-checklist-container .form-group:nth-child(even) { background-color: #f7f7f8; }
    .qc-checklist-container .form-group:nth-child(odd) { background-color: #ffffff; }
    .qc-checklist-container label.label-control { margin-bottom: 0; font-weight: 700; text-transform: uppercase; color: #2c3e50; letter-spacing: 0.5px; font-size: 0.85rem; }
    .qc-checklist-container .input-group-text { background-color: #f7f7f8; border: 1px solid #dcdce0; color: #6c757d; min-width: 45px; justify-content: center; }
    .layout-dark .qc-checklist-container .form-group:nth-child(even) { background-color: #101010; }
    .layout-dark .qc-checklist-container .form-group:nth-child(odd) { background-color: #1a1a1a; }
    .layout-dark .qc-checklist-container label.label-control { color: #e2e8f0; }
    .layout-dark .qc-checklist-container .input-group-text { background-color: #262626; border-color: #404040; color: #ffffff; }
</style>

<script>
    (function() {
        var slabsUrl = "{{ route('production-machine-analysis.get-slabs') }}";

        function escapeHtml(text) {
            return $('<div>').text(text == null ? '' : text).html();
        }

        // Rebuild one tab's checklist; values already typed for the same slab are kept
        window.renderMachineQcChecklist = function(type, slabs) {
            var $container = $('.qc-checklist-container[data-type="' + type + '"]');
            var index = $container.data('index');
            var kept = {};
            $container.find('.qc-slab-input').each(function() {
                kept[$(this).data('slab-id')] = $(this).val();
            });

            if (!slabs || slabs.length === 0) {
                $container.html('<div class="alert alert-warning m-2">No Slabs Found</div>');
                return;
            }

            var html = '';
            slabs.forEach(function(slab) {
                var symbol = escapeHtml(slab.qc_symbol ? slab.qc_symbol : '%');
                var value = escapeHtml(kept[slab.id] || '');
                html += '<div class="form-group row qc-slab-row">' +
                    '<label class="col-md-3 label-control">' + escapeHtml(slab.name) + '</label>' +
                    '<div class="col-md-9"><div class="input-group">' +
                    '<input type="text" name="items[' + index + '][params][' + slab.id + ']" data-slab-id="' + slab.id + '" class="form-control qc-slab-input" value="' + value + '" placeholder="' + symbol + '">' +
                    '<div class="input-group-append"><span class="input-group-text">' + symbol + '</span></div>' +
                    '</div></div></div>';
            });
            $container.html(html);
        };

        window.loadMachineTabSlabs = function(type, productId) {
            if (!productId) return;
            $.get(slabsUrl, { product_id: productId }, function(slabs) {
                window.renderMachineQcChecklist(type, slabs);
            });
        };

        // Apply the products of a newly selected request (response of check-fixed-product)
        window.applyMachineTabProducts = function(res) {
            var $input = $('.machine-tab-product[data-type="input"]');
            window.fillAnalysisProductOptions($input, res.input_products || []);
            if (!$input.val() && res.suggested_input_product_id) {
                $input.val(res.suggested_input_product_id).trigger('change.select2');
            }
            $('.machine-rmqc-badge').toggleClass('d-none', !res.from_rm_qc);
            window.loadMachineTabSlabs('input', $input.val());

            var $output = $('.machine-tab-product[data-type="output"]');
            var $hidden = $('.machine-output-product-hidden');
            if (res.output_product_id) {
                window.ensureAnalysisProductOption($output, res.output_product_id, res.output_product_name);
                $output.val(res.output_product_id).prop('disabled', true).trigger('change.select2');
                $hidden.val(res.output_product_id).prop('disabled', false);
                $('.machine-output-fixed-badge').removeClass('d-none');
                window.loadMachineTabSlabs('output', res.output_product_id);
            } else {
                $output.prop('disabled', false).trigger('change.select2');
                $hidden.val('').prop('disabled', true);
                $('.machine-output-fixed-badge').addClass('d-none');
            }
        };

        $(document).off('change.machineTabProduct').on('change.machineTabProduct', '.machine-tab-product', function() {
            window.loadMachineTabSlabs($(this).data('type'), $(this).val());
        });
    })();
</script>
