{{--
    "Other Products" section of Input Analysis: add-more rows, each with a product and its percent.
    Uses getFinishGoodProducts() directly to ensure finished good products are always loaded.
--}}
@php
    $otherProductRows = collect($otherProductRows ?? [])
        ->map(fn($row) => ['product_id' => $row->product_id ?? null, 'percentage' => $row->percentage ?? null])
        ->values();
    if ($otherProductRows->isEmpty()) {
        $otherProductRows = collect([['product_id' => null, 'percentage' => null]]);
    }
    // Unique id per render, so a second open form never picks this section by mistake
    $otherSectionId = 'otherProducts_' . uniqid();
    $existingOtherProductIds = $otherProductRows->pluck('product_id')->filter()->all();
    $finishGoodProducts = getFinishGoodProducts(!empty($existingOtherProductIds) ? $existingOtherProductIds : null);
@endphp

<div class="col-md-12 mt-4" id="{{ $otherSectionId }}">
    <h6 class="header-heading-sepration font-weight-bold mb-3 d-flex align-items-center justify-content-between">
        <span>Other Products</span>
        <small class="text-muted font-weight-normal">Total: <strong class="other-products-total">0</strong>%</small>
    </h6>

    {{-- Source list for "Add More" rows (not submitted: no name) --}}
    <select class="other-product-source d-none" disabled>
        <option value="">Select Product</option>
        @foreach($finishGoodProducts as $product)
            <option value="{{ $product->id }}">{{ $product->name }}</option>
        @endforeach
    </select>

    <table class="table table-bordered mb-2">
        <thead>
            <tr>
                <th>Product</th>
                <th style="width: 220px;">Percent</th>
                <th style="width: 60px;" class="text-center">Action</th>
            </tr>
        </thead>
        <tbody class="other-products-body">
            @foreach($otherProductRows as $i => $row)
                <tr class="other-product-row">
                    <td>
                        <select name="other_products[{{ $i }}][product_id]" class="form-control select2 other-product-select">
                            <option value="">Select Product</option>
                            @foreach($finishGoodProducts as $product)
                                <option value="{{ $product->id }}" @selected($row['product_id'] == $product->id)>{{ $product->name }}</option>
                            @endforeach
                        </select>
                    </td>
                    <td>
                        <div class="input-group">
                            <input type="number" step="any" min="0" max="100"
                                   name="other_products[{{ $i }}][percentage]"
                                   value="{{ $row['percentage'] }}"
                                   class="form-control other-product-percent" placeholder="%">
                            <div class="input-group-append">
                                <span class="input-group-text">%</span>
                            </div>
                        </div>
                    </td>
                    <td class="text-center align-middle">
                        <button type="button" class="btn btn-sm btn-danger remove-other-product" title="Remove">
                            <i class="ft-trash-2"></i>
                        </button>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <button type="button" class="btn btn-sm btn-primary add-other-product">
        <i class="ft-plus"></i> Add More
    </button>
</div>

<script>
    $(function () {
        const $section = $('#{{ $otherSectionId }}');
        const $body = $section.find('.other-products-body');
        const optionsHtml = $section.find('.other-product-source').html();
        let nextIndex = {{ $otherProductRows->count() }};

        function initRowSelect($row) {
            if ($.fn.select2) {
                const $sel = $row.find('.other-product-select');
                if (!$sel.hasClass('select2-hidden-accessible')) {
                    $sel.select2({ width: '100%' });
                }
            }
        }

        function updateTotal() {
            let total = 0;
            $body.find('.other-product-percent').each(function () {
                total += parseFloat($(this).val()) || 0;
            });
            total = Math.round(total * 100) / 100;
            $section.find('.other-products-total').text(total).toggleClass('text-danger', total > 100);
        }

        $body.find('.other-product-row').each(function () {
            initRowSelect($(this));
        });
        updateTotal();

        $section.on('click', '.add-other-product', function () {
            const i = nextIndex++;
            const $row = $(
                '<tr class="other-product-row">' +
                    '<td><select name="other_products[' + i + '][product_id]" class="form-control select2 other-product-select"></select></td>' +
                    '<td><div class="input-group">' +
                        '<input type="number" step="any" min="0" max="100" name="other_products[' + i + '][percentage]" class="form-control other-product-percent" placeholder="%">' +
                        '<div class="input-group-append"><span class="input-group-text">%</span></div>' +
                    '</div></td>' +
                    '<td class="text-center align-middle">' +
                        '<button type="button" class="btn btn-sm btn-danger remove-other-product" title="Remove"><i class="ft-trash-2"></i></button>' +
                    '</td>' +
                '</tr>'
            );
            $row.find('.other-product-select').html(optionsHtml);
            $body.append($row);
            initRowSelect($row);
        });

        $section.on('click', '.remove-other-product', function () {
            const $row = $(this).closest('.other-product-row');
            const $select = $row.find('.other-product-select');
            if ($body.find('.other-product-row').length > 1) {
                if ($select.hasClass('select2-hidden-accessible')) {
                    $select.select2('destroy');
                }
                $row.remove();
            } else {
                // Keep one row; just clear it
                $select.val('').trigger('change');
                $row.find('.other-product-percent').val('');
            }
            updateTotal();
        });

        $section.on('input', '.other-product-percent', updateTotal);
    });
</script>
