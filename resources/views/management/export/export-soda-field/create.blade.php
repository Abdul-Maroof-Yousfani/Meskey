<form action="{{ route('export-soda-field.store') }}" method="POST" id="ajaxSubmit" autocomplete="off">
    @csrf
    <input type="hidden" id="listRefresh" value="{{ route('get.export-soda-field') }}" />

    <div class="row form-mar p-2">
        <div class="col-md-12">
            <h6 class="header-heading-sepration">Basic Information</h6>
            <div class="row mt-2">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Reference #:</label>
                        <input type="text" name="reference" class="form-control" required>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        <label>Party Name (Buyer):</label>
                        <select name="buyer_id" class="form-control select2" required>
                            <option value="">Select Buyer</option>
                            @foreach ($users as $user)
                                <option value="{{ $user->id }}">{{ $user->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        <label>Commodity:</label>
                        <select name="product_id" class="form-control select2" required>
                            <option value="">Select Product</option>
                            @foreach ($products as $product)
                                <option value="{{ $product->id }}">{{ $product->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        <label>Packing:</label>
                        <select name="bag_packing_id" class="form-control select2">
                            <option value="">Select Packing</option>
                            @foreach ($bagPackings as $packing)
                                <option value="{{ $packing->id }}">{{ $packing->name }}</option>
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
                                <option value="{{ $incoterm->id }}">{{ $incoterm->name }}</option>
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
                                <option value="{{ $term->id }}">{{ $term->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        <label>Shipment Period:</label>
                        <input type="date" name="shipment_period" class="form-control">
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        <label>Commission:</label>
                        <input type="number" name="commission" class="form-control" step="0.01">
                    </div>
                </div>
            </div>

            <h6 class="header-heading-sepration mt-3">Rate Calculation</h6>
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Rate Per KG:</label>
                        <input type="number" step="0.01" name="price_per_kg" placeholder="Rate Per KG" class="form-control" />
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Rate Per Mound:</label>
                        <input type="number" step="0.01" name="price_per_mound" placeholder="Rate Per Mound" class="form-control" />
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Rate Per 100KG:</label>
                        <input type="number" step="0.01" name="price_per_100_kg" placeholder="Rate Per 100KG" class="form-control" />
                    </div>
                </div>
            </div>

            <h6 class="header-heading-sepration mt-3">Quantity Calculation</h6>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Quantity in KG:</label>
                        <input type="number" step="0.01" name="quantity_in_kg" id="quantity_in_kg" placeholder="Quantity in KG" class="form-control" />
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Quantity in Ton:</label>
                        <input type="number" step="0.01" name="quantity_in_ton" id="quantity_in_ton" placeholder="Quantity in Ton" class="form-control" />
                    </div>
                </div>
            </div>

            <div class="row mt-2">
                <div class="col-md-12">
                    <div class="form-group">
                        <label>Additional Info:</label>
                        <textarea name="additional_info" class="form-control" rows="4"></textarea>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row bottom-button-bar">
        <div class="col-12 mb-3 text-right">
            <a type="button" class="btn btn-danger modal-sidebar-close position-relative top-1 closebutton">Close</a>
            <button type="submit" class="btn btn-primary submitbutton">Save Export Soda Field</button>
        </div>
    </div>
</form>

<script>
    $(document).ready(function() {
        $('.select2').select2({
            width: '100%'
        });

        // --- Rate Calculations ---
        const KG_PER_MOUND = 40;
        const KG_PER_100KG = 100;

        function calculateRates(changedField) {
            const ratePerKg = parseFloat($('[name="price_per_kg"]').val()) || 0;
            const ratePerMound = parseFloat($('[name="price_per_mound"]').val()) || 0;
            const ratePer100kg = parseFloat($('[name="price_per_100_kg"]').val()) || 0;

            switch (changedField) {
                case 'price_per_kg':
                    $('[name="price_per_mound"]').val((ratePerKg * KG_PER_MOUND).toFixed(4));
                    $('[name="price_per_100_kg"]').val((ratePerKg * KG_PER_100KG).toFixed(4));
                    break;

                case 'price_per_mound':
                    $('[name="price_per_kg"]').val((ratePerMound / KG_PER_MOUND).toFixed(4));
                    $('[name="price_per_100_kg"]').val((ratePerMound / KG_PER_MOUND * KG_PER_100KG).toFixed(4));
                    break;

                case 'price_per_100_kg':
                    $('[name="price_per_kg"]').val((ratePer100kg / KG_PER_100KG).toFixed(4));
                    $('[name="price_per_mound"]').val((ratePer100kg / KG_PER_100KG * KG_PER_MOUND).toFixed(4));
                    break;
            }
        }

        $('[name="price_per_kg"]').on('input', function() {
            calculateRates('price_per_kg');
        });

        $('[name="price_per_mound"]').on('input', function() {
            calculateRates('price_per_mound');
        });

        $('[name="price_per_100_kg"]').on('input', function() {
            calculateRates('price_per_100_kg');
        });


        // --- Quantity Calculations ---
        const KG_PER_TON = 1000;

        $('#quantity_in_kg').on('input', function() {
            let kg = parseFloat($(this).val()) || 0;
            $('#quantity_in_ton').val((kg / KG_PER_TON).toFixed(4));
        });

        $('#quantity_in_ton').on('input', function() {
            let ton = parseFloat($(this).val()) || 0;
            $('#quantity_in_kg').val((ton * KG_PER_TON).toFixed(4));
        });
    });
</script>
