<form action="{{ route('quotation.update', $quotation->id) }}" id="ajaxSubmit" method="POST" autocomplete="off">
    @csrf
    @method('PUT')
    <input type="hidden" id="listRefresh" value="{{ route('get.quotation') }}" />

    <input type="hidden" name="company_id" value="{{ auth()->user()->current_company_id }}">
    <input type="hidden" name="export_soda_id" value="{{ $quotation->export_soda_id }}">

    <div class="row form-mar">
        <div class="col-8">
            {{-- ====== BUYER & LOCATION INFO ====== --}}
            <div class="col-md-12">
                <h6 class="header-heading-sepration">Buyer & Location Information</h6>
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Export Soda</label>
                            <input type="text" class="form-control" value="{{ $quotation->exportSoda ? '#' . $quotation->exportSoda->id . ' - ' . ($quotation->exportSoda->product->name ?? '') : 'N/A' }}" readonly>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Buyer's Name <span class="text-danger">*</span></label>
                            <select name="buyer_id" id="buyerSelect" class="form-control select2">
                                <option value="">Select Buyer</option>
                                @foreach ($buyers as $buyer)
                                    <option value="{{ $buyer->id }}" {{ old('buyer_id', $quotation->buyer_id) == $buyer->id ? 'selected' : '' }}>{{ $buyer->name }}</option>
                                @endforeach
                            </select>
                            @error('buyer_id') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Contact No</label>
                            <input type="text" id="buyer_phone" class="form-control" value="{{ $quotation->buyer->phone ?? $quotation->buyer->owner_mobile_no ?? '' }}" readonly>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Email Address</label>
                            <input type="text" id="buyer_email" class="form-control" value="{{ $quotation->buyer->email ?? '' }}" readonly>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="form-group">
                            <label>Buyer Address</label>
                            <input type="text" id="buyer_address" class="form-control" value="{{ $quotation->buyer->address ?? '' }}" readonly>
                        </div>
                    </div>

                    <div class="col-md-4 mt-2">
                        <div class="form-group">
                            <label>Company Locations</label>
                            <select name="company_location_ids[]" id="companyLocationSelect" class="form-control select2" multiple>
                                @foreach ($companyLocations as $location)
                                    <option value="{{ $location->id }}" {{ in_array($location->id, old('company_location_ids', $quotation->company_location_ids ?? [])) ? 'selected' : '' }}>{{ $location->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4 mt-2">
                        <div class="form-group">
                            <label>Arrival Locations</label>
                            <select name="arrival_location_ids[]" id="arrivalLocationSelect" class="form-control select2" multiple>
                                @foreach ($arrivalLocations as $arrival)
                                    <option value="{{ $arrival->id }}" {{ in_array($arrival->id, old('arrival_location_ids', $quotation->arrival_location_ids ?? [])) ? 'selected' : '' }}>{{ $arrival->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4 mt-2">
                        <div class="form-group">
                            <label>Arrival Sub Locations</label>
                            <select name="arrival_sub_location_ids[]" id="arrivalSubLocationSelect" class="form-control select2" multiple>
                                @foreach ($arrivalSubLocations as $sub)
                                    <option value="{{ $sub->id }}" {{ in_array($sub->id, old('arrival_sub_location_ids', $quotation->arrival_sub_location_ids ?? [])) ? 'selected' : '' }}>{{ $sub->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ====== PRODUCT ====== --}}
            <div class="col-md-12 mt-4">
                <h6 class="header-heading-sepration">Product / Commodity</h6>
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label>Product <span class="text-danger">*</span></label>
                            <select name="product_id" id="productSelect" class="form-control select2">
                                <option value="">Select Product</option>
                                @foreach ($products as $product)
                                    <option value="{{ $product->id }}" {{ old('product_id', $quotation->product_id) == $product->id ? 'selected' : '' }}>{{ $product->name }}</option>
                                @endforeach
                            </select>
                            @error('product_id') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>
                    </div>
                </div>
                <div id="specificationsSection" style="display:none;" class="mt-2">
                    <h6 class="header-heading-sepration">Specifications</h6>
                    <div id="productSpecs"></div>
                </div>
            </div>
        </div>

        <div class="col-4">
            {{-- ====== EXPORT DETAILS SIDEBAR ====== --}}
            <h6 class="header-heading-sepration">Export Details</h6>
            <div class="table-responsive">
                <table class="table table-bordered spacing-table" style="margin-bottom:0;">
                    <tr>
                        <td style="width:40%;font-weight:bold;vertical-align:middle;">INCOTERMS</td>
                        <td>
                            <select name="incoterm_id" class="form-control select2">
                                <option value="">Select</option>
                                @foreach ($incoterms as $incoterm)
                                    <option value="{{ $incoterm->id }}" {{ old('incoterm_id', $quotation->incoterm_id) == $incoterm->id ? 'selected' : '' }}>{{ $incoterm->name }}</option>
                                @endforeach
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td style="font-weight:bold;vertical-align:middle;">PACKING TYPE</td>
                        <td>
                            <select name="packing_type" class="form-control select2">
                                <option value="">Select</option>
                                <option value="In Conatiner" {{ old('packing_type', $quotation->packing_type) == 'In Conatiner' ? 'selected' : '' }}>IN CONTAINER</option>
                                <option value="In Bulk" {{ old('packing_type', $quotation->packing_type) == 'In Bulk' ? 'selected' : '' }}>IN BULK</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td style="font-weight:bold;vertical-align:middle;">MODE OF TERM</td>
                        <td>
                            <select name="mode_of_term_id" class="form-control select2">
                                <option value="">Select</option>
                                @foreach ($modeofterms as $term)
                                    <option value="{{ $term->id }}" {{ old('mode_of_term_id', $quotation->mode_of_term_id) == $term->id ? 'selected' : '' }}>{{ $term->name }}</option>
                                @endforeach
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td style="font-weight:bold;vertical-align:middle;">MODE OF TRANSPORT</td>
                        <td>
                            <select name="mode_of_transport_id" class="form-control select2">
                                <option value="">Select</option>
                                @foreach ($modeoftransport as $transport)
                                    <option value="{{ $transport->id }}" {{ old('mode_of_transport_id', $quotation->mode_of_transport_id) == $transport->id ? 'selected' : '' }}>{{ $transport->name }}</option>
                                @endforeach
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td style="font-weight:bold;vertical-align:middle;">ORIGIN</td>
                        <td>
                            <select name="origin_country_id" class="form-control select2">
                                <option value="">Select</option>
                                @foreach ($countries as $country)
                                    <option value="{{ $country->id }}" {{ old('origin_country_id', $quotation->origin_country_id) == $country->id ? 'selected' : '' }}>{{ $country->name }}</option>
                                @endforeach
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td style="font-weight:bold;vertical-align:middle;">PORT OF DISCHARGE</td>
                        <td>
                            <select name="port_of_discharge_id" class="form-control select2">
                                <option value="">Select</option>
                                @foreach ($ports as $port)
                                    <option value="{{ $port->id }}" {{ old('port_of_discharge_id', $quotation->port_of_discharge_id) == $port->id ? 'selected' : '' }}>{{ $port->name }}, {{ $port->country?->name ?? '' }}</option>
                                @endforeach
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td style="font-weight:bold;vertical-align:middle;">PORT OF LOADING</td>
                        <td>
                            <select name="port_of_loading_id" class="form-control select2">
                                <option value="">Select</option>
                                @foreach ($ports as $port)
                                    <option value="{{ $port->id }}" {{ old('port_of_loading_id', $quotation->port_of_loading_id) == $port->id ? 'selected' : '' }}>{{ $port->name }}, {{ $port->country?->name ?? '' }}</option>
                                @endforeach
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td style="font-weight:bold;vertical-align:middle;">ADVANCE PAYMENT(%)</td>
                        <td>
                            <input type="number" name="advance_payment" class="form-control no-spin" max="100" min="0" step="0.01" value="{{ old('advance_payment', $quotation->advance_payment) }}">
                        </td>
                    </tr>
                    <tr>
                        <td style="font-weight:bold;vertical-align:middle;">PAYMENT DAYS</td>
                        <td>
                            <input type="text" name="payment_days" class="form-control" value="{{ old('payment_days', $quotation->payment_days) }}">
                        </td>
                    </tr>
                    <tr>
                        <td style="font-weight:bold;vertical-align:middle;">CURRENCY</td>
                        <td>
                            <select name="currency_id" id="currencySelect" class="form-control select2">
                                <option value="">Select</option>
                                @foreach ($currencies as $currency)
                                    <option value="{{ $currency->id }}" data-rate="{{ $currency->rate }}" {{ old('currency_id', $quotation->currency_id) == $currency->id ? 'selected' : '' }}>{{ $currency->currency_name }}</option>
                                @endforeach
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td style="font-weight:bold;vertical-align:middle;">RATE</td>
                        <td>
                            <input type="text" name="currency_rate" id="currencyRate" class="form-control" value="{{ old('currency_rate', $quotation->currency_rate) }}" readonly>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        {{-- ====== PACKING DETAILS (Full Width) ====== --}}
        <div class="col-12 mt-4">
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
                            <th style="min-width:100px;">Size (kg)</th>
                            <th style="min-width:100px;">Qty (MT)</th>
                            <th style="min-width:100px;">Maunds</th>
                            <th style="min-width:100px;">Bags</th>
                            <th style="min-width:110px;">Total KGs</th>
                            <th style="min-width:110px;">Rate/Ton</th>
                            <th style="min-width:110px;">Rate/Mnd</th>
                            <th style="min-width:130px;">Amount</th>
                            <th style="min-width:130px;">Amount (PKR)</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="packingItems">
                        @forelse ($quotation->packingItems as $i => $item)
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
                                <input type="number" name="packing_items[{{ $i }}][bag_size]" class="form-control bag-size" step="0.01" value="{{ $item->bag_size }}" min="0">
                            </td>
                            <td class="p-2">
                                <input type="number" name="packing_items[{{ $i }}][metric_tons]" class="form-control metric-tons" value="{{ $item->metric_tons }}" step="0.001" min="0">
                            </td>
                            <td class="p-2">
                                <input type="number" name="packing_items[{{ $i }}][maunds]" class="form-control maunds" value="{{ $item->maunds }}" step="0.01" min="0">
                            </td>
                            <td class="p-2">
                                <input type="number" name="packing_items[{{ $i }}][no_of_bags]" class="form-control no_of_bags" value="{{ $item->no_of_bags }}" readonly>
                            </td>
                            <td class="p-2">
                                <input type="number" name="packing_items[{{ $i }}][total_kgs]" class="form-control total-kgs" value="{{ $item->total_kgs }}" readonly>
                            </td>
                            <td class="p-2">
                                <input type="number" name="packing_items[{{ $i }}][rate]" class="form-control rates" value="{{ $item->rate }}" step="0.01" min="0">
                            </td>
                            <td class="p-2">
                                <input type="number" name="packing_items[{{ $i }}][rate_per_maund]" class="form-control rates_mnd" value="{{ $item->rate_per_maund }}" step="0.01" min="0">
                            </td>
                            <td class="p-2">
                                <input type="number" name="packing_items[{{ $i }}][amount]" class="form-control amount" value="{{ $item->amount }}" min="0" readonly>
                            </td>
                            <td class="p-2">
                                <input type="number" name="packing_items[{{ $i }}][amount_pkr]" class="form-control amount_pkr" value="{{ $item->amount_pkr }}" min="0" readonly>
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
                                <input type="number" name="packing_items[0][metric_tons]" class="form-control metric-tons" value="0">
                            </td>
                            {{-- ... more fields ... --}}
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="row mt-2 pr-2">
                <div class="col-md-12 text-right">
                    <strong>Total Packing MT: <span id="display_total_mt">0.000</span></strong>
                </div>
            </div>

            {{-- ====== GLOBAL CONTAINERS & STUFFING ====== --}}
            <div class="mt-4">
                <h6 class="header-heading-sepration">Containers & Stuffing</h6>
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Stuffing (MT)</label>
                            <input type="number" name="stuffing_in_container" id="qty_stuffing" class="form-control" step="0.001" value="{{ old('stuffing_in_container', $quotation->stuffing_in_container) }}" min="0">
                            <small class="text-danger" id="stuffing_error" style="display:none;">Stuffing cannot exceed Total Packing MT.</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Stuffing (Maunds)</label>
                            <input type="number" name="stuffing_maunds" id="qty_stuffing_mnd" class="form-control" step="0.01" value="{{ old('stuffing_maunds', $quotation->stuffing_maunds) }}" min="0">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Containers</label>
                            <input type="number" name="no_of_containers" id="qty_containers" class="form-control" step="1" value="{{ old('no_of_containers', $quotation->no_of_containers) }}" min="0">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row bottom-button-bar">
        <div class="col-12 mb-3">
            <a type="button" class="btn btn-danger modal-sidebar-close position-relative top-1 closebutton">Close</a>
            <button type="submit" class="btn btn-primary submitbutton">Update Quotation</button>
        </div>
    </div>

</form>

<script>
$(document).ready(function() {
    $('.select2').select2({ width: '100%' });

    // Buyer details auto-fill
    $('#buyerSelect').on('change', function() {
        var buyerId = $(this).val();
        if (buyerId) {
            $.get('{{ route('get.buyer_details.quotation', '') }}/' + buyerId, function(data) {
                $('#buyer_phone').val(data.phone || data.owner_mobile_no || '');
                $('#buyer_email').val(data.email || '');
                $('#buyer_address').val(data.address || '');
            });
        }
    });

    $('#currencySelect').on('change', function() {
        var rate = $(this).find(':selected').data('rate');
        $('#currencyRate').val(rate ?? '');
        $('#packingItems tr.packing-item').each(function() {
            calculateRowAmount($(this));
        });
    });

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
        let currencyRate = parseFloat($('#currencyRate').val()) || 1;
        let amount = mt * rateMt;
        row.find('.amount').val(amount.toFixed(2));
        row.find('.amount_pkr').val((amount * currencyRate).toFixed(2));
    }

    // ---- GLOBAL TOTALS & STUFFING VALIDATION ----
    function calculateOverallTotals() {
        let totalMt = 0;
        $('#packingItems tr.packing-item').each(function() {
            totalMt += parseFloat($(this).find('.metric-tons').val()) || 0;
        });
        $('#display_total_mt').text(totalMt.toFixed(3));
        validateStuffing(totalMt);
    }

    function validateStuffing(totalMt) {
        let s = parseFloat($('#qty_stuffing').val()) || 0;
        if (s > totalMt) {
            $('#stuffing_error').show();
            $('#qty_stuffing, #qty_stuffing_mnd').addClass('is-invalid');
            $('.submitbutton').attr('disabled', true);
        } else {
            $('#stuffing_error').hide();
            $('#qty_stuffing, #qty_stuffing_mnd').removeClass('is-invalid');
            $('.submitbutton').attr('disabled', false);
        }
    }

    $('#qty_stuffing').on('input', function() {
        let s = parseFloat($(this).val()) || 0;
        $('#qty_stuffing_mnd').val((s * 25).toFixed(2));
        let totalMt = parseFloat($('#display_total_mt').text()) || 0;
        validateStuffing(totalMt);
        recalcContainersFromStuffing(totalMt);
    });

    $('#qty_stuffing_mnd').on('input', function() {
        let sm = parseFloat($(this).val()) || 0;
        let s = sm / 25;
        $('#qty_stuffing').val(s.toFixed(3));
        let totalMt = parseFloat($('#display_total_mt').text()) || 0;
        validateStuffing(totalMt);
        recalcContainersFromStuffing(totalMt);
    });

    $('#qty_containers').on('input', function() {
        let c = parseInt($(this).val()) || 0;
        let totalMt = parseFloat($('#display_total_mt').text()) || 0;
        if (c > 0 && totalMt > 0) {
            let s = totalMt / c;
            $('#qty_stuffing').val(s.toFixed(3));
            $('#qty_stuffing_mnd').val((s * 25).toFixed(2));
            validateStuffing(totalMt);
        }
    });

    function recalcContainersFromStuffing(totalMt) {
        let s = parseFloat($('#qty_stuffing').val()) || 0;
        if (s > 0 && totalMt > 0) {
            $('#qty_containers').val(Math.ceil(totalMt / s));
        }
    }

    // Initialization on load
    calculateOverallTotals();

    // Packing items buttons
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
});
</script>