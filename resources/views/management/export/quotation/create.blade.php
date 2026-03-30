<form action="{{ route('quotation.store') }}" id="ajaxSubmit" method="POST" autocomplete="off">
    @csrf

    <input type="hidden" id="listRefresh" value="{{ route('get.quotation') }}" />
    <input type="hidden" name="company_id" value="{{ auth()->user()->current_company_id }}">

    <div class="row form-mar">
        <div class="col-8">
            {{-- ====== BUYER & LOCATION INFO ====== --}}
            <div class="col-md-12">
                <h6 class="header-heading-sepration">Buyer & Information</h6>
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Export Sauda</label>
                            <select name="export_soda_id" class="form-control select2">
                                <option value="">Select Sauda</option>
                                @foreach ($exportSodas as $soda)
                                    <option value="{{ $soda->id }}">#{{ $soda->id }} - {{ $soda->product->name ?? '' }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Buyer's Name <span class="text-danger">*</span></label>
                            <select name="buyer_id" id="buyerSelect" class="form-control select2">
                                <option value="">Select Buyer</option>
                                @foreach ($buyers as $buyer)
                                    <option value="{{ $buyer->id }}">{{ $buyer->name }}</option>
                                @endforeach
                            </select>
                            @error('buyer_id') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Contact No</label>
                            <input type="text" id="buyer_phone" class="form-control" readonly>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Email Address</label>
                            <input type="text" id="buyer_email" class="form-control" readonly>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <div class="form-group">
                            <label>Buyer Address</label>
                            <input type="text" id="buyer_address" class="form-control" readonly>
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
                                    <option value="{{ $product->id }}">{{ $product->name }}</option>
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
                                    <option value="{{ $incoterm->id }}">{{ $incoterm->name }}</option>
                                @endforeach
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td style="font-weight:bold;vertical-align:middle;">PACKING TYPE</td>
                        <td>
                            <select name="packing_type" class="form-control select2">
                                <option value="">Select</option>
                                <option value="In Conatiner">IN CONTAINER</option>
                                <option value="In Bulk">IN BULK</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td style="font-weight:bold;vertical-align:middle;">MODE OF TERM</td>
                        <td>
                            <select name="mode_of_term_id" class="form-control select2">
                                <option value="">Select</option>
                                @foreach ($modeofterms as $term)
                                    <option value="{{ $term->id }}">{{ $term->name }}</option>
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
                                    <option value="{{ $transport->id }}">{{ $transport->name }}</option>
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
                                    <option value="{{ $country->id }}">{{ $country->name }}</option>
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
                                    <option value="{{ $port->id }}">{{ $port->name }}, {{ $port->country?->name ?? '' }}</option>
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
                                    <option value="{{ $port->id }}">{{ $port->name }}, {{ $port->country?->name ?? '' }}</option>
                                @endforeach
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td style="font-weight:bold;vertical-align:middle;">ADVANCE PAYMENT(%)</td>
                        <td>
                            <input type="number" name="advance_payment" class="form-control no-spin" max="100" min="0" step="0.01">
                        </td>
                    </tr>
                    <tr>
                        <td style="font-weight:bold;vertical-align:middle;">PAYMENT DAYS</td>
                        <td>
                            <input type="text" name="payment_days" class="form-control">
                        </td>
                    </tr>
                    <tr>
                        <td style="font-weight:bold;vertical-align:middle;">CURRENCY</td>
                        <td>
                            <select name="currency_id" id="currencySelect" class="form-control select2">
                                <option value="">Select</option>
                                @foreach ($currencies as $currency)
                                    <option value="{{ $currency->id }}" data-rate="{{ $currency->rate }}">{{ $currency->currency_name }}</option>
                                @endforeach
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td style="font-weight:bold;vertical-align:middle;">RATE</td>
                        <td>
                            <input type="text" name="currency_rate" id="currencyRate" class="form-control" readonly>
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
                            <th style="min-width:150px;">Bag Type</th>
                            <th style="min-width:130px;">Packing</th>
                            <th style="min-width:110px;">Color</th>
                            <th style="min-width:100px;">Packing Size (kg)</th>
                            <th style="min-width:100px;">Qty (MT)</th>
                            <th style="min-width:100px; display: none;">Qty (Mnds)</th>
                            <th style="min-width:110px;">Qty (KGs)</th>
                            <th style="min-width:100px;">Bags</th>
                            <th style="min-width:110px;">Rate/Ton</th>
                            <th style="min-width:110px; display: none;">Rate/Mnd</th>
                            <th style="min-width:130px;">Amount</th>
                            <th style="min-width:130px;">Amount (PKR)</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="packingItems">
                        <tr class="packing-item">
                            <td class="p-2">
                                <select name="packing_items[0][bag_type_id]" class="form-control select2">
                                    <option value="">Select Bag Type</option>
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
                            <td class="p-2" style="display: none;">
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
                            <td class="p-2" style="display: none;">
                                <input type="number" name="packing_items[0][rate_per_maund]" class="form-control rates_mnd" value="0" step="0.01" min="0">
                            </td>
                            <td class="p-2">
                                <input type="number" name="packing_items[0][amount]" class="form-control amount" value="0" min="0" readonly>
                            </td>
                            <td class="p-2">
                                <input type="number" name="packing_items[0][amount_pkr]" class="form-control amount_pkr" value="0" min="0" readonly>
                            </td>
                            <td class="text-center p-2">
                                <button type="button" class="btn btn-sm btn-danger remove-packing-item">
                                    <i class="ft-trash-2"></i>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            {{-- Global Total MT display (optional, but helpful for validation context) --}}
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
                            <input type="number" name="stuffing_in_container" id="qty_stuffing" class="form-control" step="0.001" value="0" min="0">
                            <small class="text-danger" id="stuffing_error" style="display:none;">Stuffing cannot exceed Total Packing MT.</small>
                        </div>
                    </div>
                    <div class="col-md-4" style="display: none;">
                        <div class="form-group">
                            <label>Stuffing (Maunds)</label>
                            <input type="number" name="stuffing_maunds" id="qty_stuffing_mnd" class="form-control" step="0.01" value="0" min="0">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Containers</label>
                            <input type="number" name="no_of_containers" id="qty_containers" class="form-control" step="1" value="0" min="0">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row bottom-button-bar">
        <div class="col-12 mb-3">
            <a type="button" class="btn btn-danger modal-sidebar-close position-relative top-1 closebutton">Close</a>
            <button type="submit" class="btn btn-primary submitbutton">Create Quotation</button>
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
        } else {
            $('#buyer_phone, #buyer_email, #buyer_address').val('');
        }
    });

    $('#currencySelect').on('change', function() {
        var rate = $(this).find(':selected').data('rate');
        $('#currencyRate').val(rate ?? '');
        $('#packingItems tr.packing-item').each(function() {
            calculateRowAmount($(this));
        });
    });

    $('#productSelect').on('change', function() {
        var productId = $(this).val();
        if (productId) {
            $.get('{{ route('get.product_specs.quotation', '') }}/' + productId, function(data) {
                $('#productSpecs').html(data);
                $('#specificationsSection').show();
            });
        } else {
            $('#specificationsSection').hide();
        }
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
        recalcContainersFromStuffing(totalMt);
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

    // Packing items
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
        } else {
            alert('At least one packing item is required.');
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
