<div class="row form-mar p-2">
    <div class="col-md-12">
        <h6 class="header-heading-sepration">Basic Information</h6>
        <div class="row mt-2">
            <div class="col-md-6">
                <div class="form-group">
                    <label>Reference #:</label>
                    <input type="text" name="reference" class="form-control" value="{{ $exportSodaField->reference }}" readonly>
                </div>
            </div>

            <div class="col-md-6">
                <div class="form-group">
                    <label>Party Name (Buyer):</label>
                    <select name="buyer_id" class="form-control select2" disabled>
                        <option value="">Select Buyer</option>
                        @foreach ($users as $user)
                            <option value="{{ $user->id }}" {{ $exportSodaField->buyer_id == $user->id ? 'selected' : '' }}>
                                {{ $user->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="col-md-6">
                <div class="form-group">
                    <label>Commodity:</label>
                    <select name="product_id" class="form-control select2" disabled>
                        <option value="">Select Product</option>
                        @foreach ($products as $product)
                            <option value="{{ $product->id }}" {{ $exportSodaField->product_id == $product->id ? 'selected' : '' }}>
                                {{ $product->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="col-md-6">
                <div class="form-group">
                    <label>Packing:</label>
                    <select name="bag_packing_id" class="form-control select2" disabled>
                        <option value="">Select Packing</option>
                        @foreach ($bagPackings as $packing)
                            <option value="{{ $packing->id }}" {{ $exportSodaField->bag_packing_id == $packing->id ? 'selected' : '' }}>
                                {{ $packing->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="col-md-6">
                <div class="form-group">
                    <label>Inco Term:</label>
                    <select name="incoterm_id" class="form-control select2" disabled>
                        <option value="">Select IncoTerm</option>
                        @foreach ($incoterms as $incoterm)
                            <option value="{{ $incoterm->id }}" {{ $exportSodaField->incoterm_id == $incoterm->id ? 'selected' : '' }}>
                                {{ $incoterm->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="col-md-6">
                <div class="form-group">
                    <label>Payment Term (Optional):</label>
                    <select name="mode_of_term_id" class="form-control select2" disabled>
                        <option value="">Select Payment Term</option>
                        @foreach ($modeofterms as $term)
                            <option value="{{ $term->id }}" {{ $exportSodaField->mode_of_term_id == $term->id ? 'selected' : '' }}>
                                {{ $term->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="col-md-6">
                <div class="form-group">
                    <label>Shipment Period:</label>
                    <input type="date" name="shipment_period" class="form-control" value="{{ $exportSodaField->shipment_period }}" readonly>
                </div>
            </div>

            <div class="col-md-6">
                <div class="form-group">
                    <label>Commission:</label>
                    <input type="number" name="commission" class="form-control" step="0.01" value="{{ $exportSodaField->commission }}" readonly>
                </div>
            </div>
        </div>

        <h6 class="header-heading-sepration mt-3">Rate Details</h6>
        <div class="row">
            <div class="col-md-4">
                <div class="form-group">
                    <label>Rate Per KG:</label>
                    <input type="number" step="0.01" name="price_per_kg" placeholder="Rate Per KG" class="form-control" value="{{ number_format($exportSodaField->price_per_kg, 2, '.', '') }}" readonly />
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label>Rate Per Mound:</label>
                    <input type="number" step="0.01" name="price_per_mound" placeholder="Rate Per Mound" class="form-control" value="{{ number_format($exportSodaField->price_per_mound, 2, '.', '') }}" readonly />
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label>Rate Per 100KG:</label>
                    <input type="number" step="0.01" name="price_per_100_kg" placeholder="Rate Per 100KG" class="form-control" value="{{ number_format($exportSodaField->price_per_100_kg, 2, '.', '') }}" readonly />
                </div>
            </div>
        </div>

        <h6 class="header-heading-sepration mt-3">Quantity Details</h6>
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label>Quantity in KG:</label>
                    <input type="number" step="0.01" name="quantity_in_kg" id="quantity_in_kg" placeholder="Quantity in KG" class="form-control" value="{{ number_format($exportSodaField->quantity_in_kg, 2, '.', '') }}" readonly />
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label>Quantity in Ton:</label>
                    <input type="number" step="0.01" name="quantity_in_ton" id="quantity_in_ton" placeholder="Quantity in Ton" class="form-control" value="{{ number_format($exportSodaField->quantity_in_ton, 2, '.', '') }}" readonly />
                </div>
            </div>
        </div>

        <div class="row mt-2">
            <div class="col-md-12">
                <div class="form-group">
                    <label>Additional Info:</label>
                    <textarea name="additional_info" class="form-control" rows="4" readonly>{{ $exportSodaField->additional_info }}</textarea>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row bottom-button-bar">
    <div class="col-12 mb-3 text-right">
        <a type="button" class="btn btn-danger modal-sidebar-close position-relative top-1 closebutton">Close</a>
    </div>
</div>

<script>
    $(document).ready(function() {
        $('.select2').select2({
            width: '100%',
            disabled: true
        });
    });
</script>
