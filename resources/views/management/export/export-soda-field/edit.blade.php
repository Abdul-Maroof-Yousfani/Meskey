<form action="{{ route('export-soda-field.update', $exportSodaField->id) }}" method="POST" id="ajaxSubmit" autocomplete="off">
    @csrf
    @method('PUT')
    <input type="hidden" id="listRefresh" value="{{ route('get.export-soda-field') }}" />

    <div class="row form-mar p-2">
        <div class="col-md-12">
            <h6 class="header-heading-sepration">Basic Information</h6>
            <div class="row mt-2">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Reference #:</label>
                        <input type="text" name="reference" class="form-control" value="{{ $exportSodaField->reference }}" required>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        <label>Party Name (Buyer):</label>
                        <select name="buyer_id" class="form-control select2" required>
                            <option value="">Select Buyer</option>
                            @foreach ($users as $user)
                                <option value="{{ $user->id }}" {{ $exportSodaField->buyer_id == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="col-md-12">
                    <div class="form-group">
                        <label>Commodity:</label>
                        <select name="product_id" class="form-control select2" required>
                            <option value="">Select Product</option>
                            @foreach ($products as $product)
                                <option value="{{ $product->id }}" {{ $exportSodaField->product_id == $product->id ? 'selected' : '' }}>{{ $product->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        <label>Inco Term:</label>
                        <select name="incoterm_id" class="form-control select2">
                            <option value="">Select IncoTerm</option>
                            @foreach ($incoterms as $incoterm)
                                <option value="{{ $incoterm->id }}" {{ $exportSodaField->incoterm_id == $incoterm->id ? 'selected' : '' }}>{{ $incoterm->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        <label>Payment Term (Optional):</label>
                        <select name="mode_of_term_id" class="form-control select2">
                            <option value="">Select Payment Term</option>
                            @foreach ($modeofterms as $term)
                                <option value="{{ $term->id }}" {{ $exportSodaField->mode_of_term_id == $term->id ? 'selected' : '' }}>{{ $term->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        <label>Shipment Period:</label>
                        <input type="date" name="shipment_period" class="form-control" value="{{ $exportSodaField->shipment_period }}">
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        <label>Commission:</label>
                        <input type="number" name="commission" class="form-control" step="0.01" value="{{ $exportSodaField->commission }}">
                    </div>
                </div>
            </div>

            {{-- ====== PACKING DETAILS ====== --}}
            <div class="col-12 mt-4 px-0">
                <h6 class="header-heading-sepration d-flex justify-content-between align-items-center">
                    Packing Details
                    <button type="button" class="btn btn-sm btn-success" id="addPackingItem">
                        <i class="ft-plus"></i> Add Item
                    </button>
                </h6>
                <div class="table-responsive">
                    <table class="table table-bordered mb-0" id="packingTable">
                        <thead>
                            <tr>
                                <th style="min-width:150px;">Brand</th>
                                <th style="min-width:150px;">Bag Type</th>
                                <th style="min-width:130px;">Packing</th>
                                <th style="min-width:130px;">Condition</th>
                                <th style="min-width:110px;">Color</th>
                                <th style="min-width:100px;">Packing Size (kg)</th>
                                <th style="min-width:100px;">Qty (MT)</th>
                                <th style="min-width:100px;">Qty (Mnds)</th>
                                <th style="min-width:110px;">Qty (KGs)</th>
                                <th style="min-width:100px;">Bags</th>
                                <th style="min-width:110px;">Rate/Ton</th>
                                <th style="min-width:110px;">Rate/Mnd</th>
                                <th style="min-width:130px;">Amount</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="packingItems">
                            @php 
                                $items = method_exists($exportSodaField, 'packingItems') ? $exportSodaField->packingItems : collect();
                            @endphp
                            @forelse ($items as $i => $item)
                            <tr class="packing-item">
                                <td class="p-2">
                                    <select name="packing_items[{{ $i }}][brand_id]" class="form-control select2">
                                        <option value="">Brand</option>
                                        @foreach ($brands as $brand)
                                            <option value="{{ $brand->id }}" {{ $item->brand_id == $brand->id ? 'selected' : '' }}>{{ $brand->name }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="p-2">
                                    <select name="packing_items[{{ $i }}][bag_type_id]" class="form-control select2">
                                        <option value="">Bag Type</option>
                                        @foreach ($bagTypes as $bagType)
                                            <option value="{{ $bagType->id }}" {{ $item->bag_type_id == $bagType->id ? 'selected' : '' }}>{{ $bagType->name }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="p-2">
                                    <select name="packing_items[{{ $i }}][bag_packing_id]" class="form-control select2">
                                        <option value="">Packing</option>
                                        @foreach ($bagPackings as $packing)
                                            <option value="{{ $packing->id }}" {{ $item->bag_packing_id == $packing->id ? 'selected' : '' }}>{{ $packing->name }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="p-2">
                                    <select name="packing_items[{{ $i }}][bag_condition_id]" class="form-control select2">
                                        <option value="">Condition</option>
                                        @foreach ($bagConditions as $condition)
                                            <option value="{{ $condition->id }}" {{ $item->bag_condition_id == $condition->id ? 'selected' : '' }}>{{ $condition->name }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="p-2">
                                    <select name="packing_items[{{ $i }}][bag_color_id]" class="form-control select2">
                                        <option value="">Color</option>
                                        @foreach ($bagColors as $color)
                                            <option value="{{ $color->id }}" {{ $item->bag_color_id == $color->id ? 'selected' : '' }}>{{ $color->color }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="p-2">
                                    <input type="number" name="packing_items[{{ $i }}][bag_size]" class="form-control bag-size" step="0.01" value="{{ $item->bag_size ?? 0 }}" min="0">
                                </td>
                                <td class="p-2">
                                    <input type="number" name="packing_items[{{ $i }}][metric_tons]" class="form-control metric-tons" value="{{ $item->metric_tons ?? 0 }}" step="0.001" min="0">
                                </td>
                                <td class="p-2">
                                    <input type="number" name="packing_items[{{ $i }}][maunds]" class="form-control maunds" value="{{ $item->maunds ?? 0 }}" step="0.01" min="0">
                                </td>
                                <td class="p-2">
                                    <input type="number" name="packing_items[{{ $i }}][total_kgs]" class="form-control total-kgs" value="{{ $item->total_kgs ?? 0 }}" readonly>
                                </td>
                                <td class="p-2">
                                    <input type="number" name="packing_items[{{ $i }}][no_of_bags]" class="form-control no_of_bags" value="{{ $item->no_of_bags ?? 0 }}" readonly>
                                </td>
                                <td class="p-2">
                                    <input type="number" name="packing_items[{{ $i }}][rate]" class="form-control rates" value="{{ $item->rate ?? 0 }}" step="0.01" min="0">
                                </td>
                                <td class="p-2">
                                    <input type="number" name="packing_items[{{ $i }}][rate_per_maund]" class="form-control rates_mnd" value="{{ $item->rate_per_maund ?? 0 }}" step="0.01" min="0">
                                </td>
                                <td class="p-2">
                                    <input type="number" name="packing_items[{{ $i }}][amount]" class="form-control amount" value="{{ $item->amount ?? 0 }}" min="0" readonly>
                                </td>
                                <td class="text-center p-2">
                                    <button type="button" class="btn btn-sm btn-danger remove-packing-item">
                                        <i class="ft-trash-2"></i>
                                    </button>
                                </td>
                            </tr>
                            @empty
                            <tr class="packing-item">
                                <td class="p-2">
                                    <select name="packing_items[0][brand_id]" class="form-control select2">
                                        <option value="">Brand</option>
                                        @foreach ($brands as $brand)
                                            <option value="{{ $brand->id }}">Brand</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="p-2">
                                    <select name="packing_items[0][bag_type_id]" class="form-control select2">
                                        <option value="">Bag Type</option>
                                        @foreach ($bagTypes as $bagType)
                                            <option value="{{ $bagType->id }}">{{ $bagType->name }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="p-2">
                                    <select name="packing_items[0][bag_packing_id]" class="form-control select2">
                                        <option value="">Packing</option>
                                        @foreach ($bagPackings as $packing)
                                            <option value="{{ $packing->id }}">{{ $packing->name }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="p-2">
                                    <select name="packing_items[0][bag_condition_id]" class="form-control select2">
                                        <option value="">Condition</option>
                                        @foreach ($bagConditions as $condition)
                                            <option value="{{ $condition->id }}">{{ $condition->name }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="p-2">
                                    <select name="packing_items[0][bag_color_id]" class="form-control select2">
                                        <option value="">Color</option>
                                        @foreach ($bagColors as $color)
                                            <option value="{{ $color->id }}">{{ $color->color }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="p-2">
                                    <input type="number" name="packing_items[0][bag_size]" class="form-control bag-size" step="0.01" value="0" min="0">
                                </td>
                                <td class="p-2">
                                    <input type="number" name="packing_items[0][metric_tons]" class="form-control metric-tons" value="0" step="0.001" min="0">
                                </td>
                                <td class="p-2">
                                    <input type="number" name="packing_items[0][maunds]" class="form-control maunds" value="0" step="0.01" min="0">
                                </td>
                                <td class="p-2">
                                    <input type="number" name="packing_items[0][total_kgs]" class="form-control total-kgs" value="0" readonly>
                                </td>
                                <td class="p-2">
                                    <input type="number" name="packing_items[0][no_of_bags]" class="form-control no_of_bags" value="0" readonly>
                                </td>
                                <td class="p-2">
                                    <input type="number" name="packing_items[0][rate]" class="form-control rates" value="0" step="0.01" min="0">
                                </td>
                                <td class="p-2">
                                    <input type="number" name="packing_items[0][rate_per_maund]" class="form-control rates_mnd" value="0" step="0.01" min="0">
                                </td>
                                <td class="p-2">
                                    <input type="number" name="packing_items[0][amount]" class="form-control amount" value="0" min="0" readonly>
                                </td>
                                <td class="text-center p-2">
                                    <button type="button" class="btn btn-sm btn-danger remove-packing-item">
                                        <i class="ft-trash-2"></i>
                                    </button>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="row mt-2 pr-2">
                    <div class="col-md-12 text-right">
                        <strong>Total Quantity (MT): <span id="display_total_mt">0.000</span></strong>
                    </div>
                </div>
            </div>

            <div class="row mt-2">
                <div class="col-md-12">
                    <div class="form-group">
                        <label>Additional Info:</label>
                        <textarea name="additional_info" class="form-control" rows="4">{{ $exportSodaField->additional_info }}</textarea>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row bottom-button-bar">
        <div class="col-12 mb-3 text-right">
            <a type="button" class="btn btn-danger modal-sidebar-close position-relative top-1 closebutton">Close</a>
            <button type="submit" class="btn btn-primary submitbutton">Update Export Soda Field</button>
        </div>
    </div>
</form>

<script>
$(document).ready(function() {
    $('.select2').select2({ width: '100%' });

    // ---- ROW LEVEL CALCULATIONS ----
    $(document).on('input', '.metric-tons, .bag-size', function() {
        let row = $(this).closest('tr');
        calculateRowFromMT(row);
        calculateOverallTotals();
    });

    $(document).on('input', '.maunds', function() {
        let row = $(this).closest('tr');
        calculateRowFromMaunds(row);
        calculateOverallTotals();
    });

    $(document).on('input', '.rates', function() {
        let row = $(this).closest('tr');
        calculateRowFromRate(row);
    });

    $(document).on('input', '.rates_mnd', function() {
        let row = $(this).closest('tr');
        calculateRowFromRateMnd(row);
    });

    function calculateRowFromMT(row) {
        let mt = parseFloat(row.find('.metric-tons').val()) || 0;
        let totalKgs = mt * 1000;
        let bagSize = parseFloat(row.find('.bag-size').val()) || 0;
        let bags = bagSize > 0 ? (totalKgs / bagSize) : 0;
        row.find('.maunds').val((mt * 25).toFixed(2));
        row.find('.total-kgs').val(totalKgs.toFixed(2));
        row.find('.no_of_bags').val(Math.ceil(bags));
        calculateRowAmount(row);
    }

    function calculateRowFromMaunds(row) {
        let maunds = parseFloat(row.find('.maunds').val()) || 0;
        let mt = maunds / 25;
        let totalKgs = mt * 1000;
        let bagSize = parseFloat(row.find('.bag-size').val()) || 0;
        let bags = bagSize > 0 ? (totalKgs / bagSize) : 0;
        row.find('.metric-tons').val(mt.toFixed(3));
        row.find('.total-kgs').val(totalKgs.toFixed(2));
        row.find('.no_of_bags').val(Math.ceil(bags));
        calculateRowAmount(row);
    }

    function calculateRowFromRate(row) {
        let rateMt = parseFloat(row.find('.rates').val()) || 0;
        row.find('.rates_mnd').val((rateMt / 25).toFixed(2));
        calculateRowAmount(row);
    }

    function calculateRowFromRateMnd(row) {
        let rateMnd = parseFloat(row.find('.rates_mnd').val()) || 0;
        row.find('.rates').val((rateMnd * 25).toFixed(2));
        calculateRowAmount(row);
    }

    function calculateRowAmount(row) {
        let mt = parseFloat(row.find('.metric-tons').val()) || 0;
        let rateMt = parseFloat(row.find('.rates').val()) || 0;
        let amount = mt * rateMt;
        row.find('.amount').val(amount.toFixed(2));
    }

    function calculateOverallTotals() {
        let totalMt = 0;
        $('#packingItems tr.packing-item').each(function() {
            totalMt += parseFloat($(this).find('.metric-tons').val()) || 0;
        });
        $('#display_total_mt').text(totalMt.toFixed(3));
    }

    // Packing items dynamic rows
    $('#addPackingItem').click(function() { addNewPackingItem(); });
    function addNewPackingItem() {
        var firstRow = $('#packingItems tr.packing-item').first();
        var newRow = firstRow.clone();
        newRow.find('.select2-container').remove();
        newRow.find('select').removeClass('select2-hidden-accessible').removeAttr('data-select2-id').show();
        newRow.find('input').val('0');
        newRow.find('select').val('').trigger('change');
        $('#packingItems').append(newRow);
        $('.select2').select2({ width: '100%' });
        reindexPackingItems();
    }
    $(document).on('click', '.remove-packing-item', function() {
        if ($('#packingItems tr.packing-item').length > 1) {
            $(this).closest('tr').remove();
            reindexPackingItems();
            calculateOverallTotals();
        }
    });
    function reindexPackingItems() {
        $('#packingItems tr.packing-item').each(function(i) {
            $(this).find('select, input').each(function() {
                var name = $(this).attr('name');
                if (name) { $(this).attr('name', name.replace(/\[\d+\]/, '[' + i + ']')); }
            });
        });
    }

    // Initialize totals on load
    calculateOverallTotals();
});
</script>
