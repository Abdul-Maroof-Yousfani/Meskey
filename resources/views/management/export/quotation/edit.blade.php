<form action="{{ route('quotation.update', $quotation->id) }}" id="ajaxSubmit" method="POST" autocomplete="off">
    @csrf
    @method('PUT')
    <input type="hidden" id="listRefresh" value="{{ route('get.quotation') }}" />

    <input type="hidden" name="company_id" value="{{ auth()->user()->current_company_id }}">

    <style>
        .is-invalid {
            border: 1px solid #ff0000 !important;
        }
    </style>

    <div class="row form-mar">
        <div class="col-8">
            {{-- ====== BUYER & INFO ====== --}}
            <div class="col-md-12">
                <h6 class="header-heading-sepration">Buyer & Information</h6>
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Export Sauda</label>
                            <input type="text" class="form-control" value="{{ $quotation->exportSoda ? $quotation->exportSoda->reference : '-' }}" readonly>
                            <input type="hidden" name="export_soda_id" value="{{ $quotation->export_soda_id }}">
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
                            <input type="text" id="buyer_phone" class="form-control" value="{{ $quotation->buyer->phone ?? $quotation->buyer->owner_mobile_no ?? '-' }}" readonly>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Email Address</label>
                            <input type="text" id="buyer_email" class="form-control" value="{{ $quotation->buyer->email ?? '-' }}" readonly>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <div class="form-group">
                            <label>Buyer Address</label>
                            <input type="text" id="buyer_address" class="form-control" value="{{ $quotation->buyer->address ?? '-' }}" readonly>
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
                <div id="specificationsSection" style="display: {{ $quotation->specifications->count() ? 'block' : 'none' }};" class="mt-2">
                    <h6 class="header-heading-sepration">Specifications</h6>
                    <div id="productSpecs">
                        @if($quotation->specifications->count())
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped">
                                    <thead class="thead-dark">
                                        <tr>
                                            <th width="40%">Specification Name</th>
                                            <th width="30%">Value</th>
                                            <th width="30%">UOM</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($quotation->specifications as $index => $spec)
                                            <tr>
                                                <td>
                                                    <strong>{{ $spec->spec_name }}</strong>
                                                    <input type="hidden" name="specifications[{{ $index }}][product_slab_type_id]" value="{{ $spec->product_slab_type_id }}">
                                                    <input type="hidden" name="specifications[{{ $index }}][spec_name]" value="{{ $spec->spec_name }}">
                                                    <input type="hidden" name="specifications[{{ $index }}][uom]" value="{{ $spec->uom }}">
                                                </td>
                                                <td>
                                                    <div class="input-group">
                                                        <input type="text" name="specifications[{{ $index }}][spec_value]" value="{{ $spec->spec_value ?? 0 }}" class="form-control form-control-sm">
                                                        <div class="input-group-prepend">
                                                            <button class="btn btn-secondary" type="button">{{ $spec->slabType->qc_symbol ?? 'N/A' }}</button>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <select name="specifications[{{ $index }}][value_type]" class="form-control">
                                                        <option value="min" {{ $spec->value_type == 'min' ? 'selected' : '' }}>Minimum</option>
                                                        <option value="max" {{ $spec->value_type == 'max' ? 'selected' : '' }}>Maximum</option>
                                                    </select>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Commission Section --}}
                <div class="mt-4">
                    <h6 class="header-heading-sepration">Commission</h6>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Commission (%):</label>
                                <input type="number" id="commission_percentage" name="commission_percentage" class="form-control" step="0.01" min="0" value="{{ $quotation->commission_percentage }}">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Amt/Ton:</label>
                                <input type="number" id="commission_amount_per_ton" name="commission_amount_per_ton" class="form-control" step="0.01" min="0" value="{{ $quotation->commission_amount_per_ton }}">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Total Commission:</label>
                                <input type="number" id="commission" name="commission" class="form-control" step="0.01" readonly value="{{ $quotation->commission }}">
                            </div>
                        </div>
                    </div>
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
                    <!-- <tr>
                        <td style="font-weight:bold;vertical-align:middle;">RATE</td>
                        <td>
                            <input type="text" name="currency_rate" id="currencyRate" class="form-control" value="{{ old('currency_rate', $quotation->currency_rate) }}" readonly>
                        </td>
                    </tr> -->
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
                            <th style="min-width:100px;">Size (kg)</th>
                            <th style="min-width:100px;">Qty (MT)</th>
                            <th style="min-width:100px; display: none;">Maunds</th>
                            <th style="min-width:100px;">Bags</th>
                            <th style="min-width:100px; display: none;">Total KGs</th>
                            <th style="min-width:100px;">Stuffing/Cont</th>
                            <th style="min-width:100px;">Containers</th>
                            <th style="min-width:110px;">Rate/Ton</th>
                            <th style="min-width:110px; display: none;">Rate/Mnd</th>
                            <th style="min-width:130px;">Amount</th>
                            <th style="min-width:130px; display: none;">Amount (PKR)</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="packingItems">
                        @forelse ($quotation->packingItems as $i => $item)
                        <tr class="packing-item">
                            <input type="hidden" name="packing_items[{{ $i }}][id]" value="{{ $item->id }}">
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
                                <input type="number" name="packing_items[{{ $i }}][bag_size]" class="form-control bag-size" step="0.01" value="{{ $item->bag_size }}" min="0">
                            </td>
                            <td class="p-2">
                                <input type="number" name="packing_items[{{ $i }}][metric_tons]" class="form-control metric-tons" value="{{ $item->metric_tons }}" step="0.001" min="0">
                            </td>
                            <td class="p-2" style="display: none;">
                                <input type="number" name="packing_items[{{ $i }}][maunds]" class="form-control maunds" value="{{ $item->maunds }}" step="0.01" min="0">
                            </td>
                            <td class="p-2">
                                <input type="number" name="packing_items[{{ $i }}][no_of_bags]" class="form-control no_of_bags" value="{{ $item->no_of_bags }}" readonly>
                            </td>
                            <td class="p-2" style="display: none;">
                                <input type="number" name="packing_items[{{ $i }}][total_kgs]" class="form-control total-kgs" value="{{ $item->total_kgs }}" readonly>
                            </td>
                            <td class="p-2">
                                <input type="number" name="packing_items[{{ $i }}][stuffing_in_container]" class="form-control stuffing-in-container" value="{{ $item->stuffing_in_container }}" step="0.001" min="0">
                            </td>
                            <td class="p-2">
                                <input type="number" name="packing_items[{{ $i }}][no_of_containers]" class="form-control no-of-containers" value="{{ $item->no_of_containers }}" min="0">
                            </td>
                            <td class="p-2">
                                <input type="number" name="packing_items[{{ $i }}][rate]" class="form-control rates" value="{{ $item->rate }}" step="0.01" min="0">
                            </td>
                            <td class="p-2" style="display: none;">
                                <input type="number" name="packing_items[{{ $i }}][rate_per_maund]" class="form-control rates_mnd" value="{{ $item->rate_per_maund }}" step="0.01" min="0">
                            </td>
                            <td class="p-2">
                                <input type="number" name="packing_items[{{ $i }}][amount]" class="form-control amount" value="{{ $item->amount }}" min="0" readonly>
                            </td>
                            <td class="p-2" style="display: none;">
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
                                <select name="packing_items[0][bag_type_id]" class="form-control select2">
                                    <option value="">Bag Type</option>
                                    @foreach ($bagTypes as $bagType)
                                        <option value="{{ $bagType->id }}">Bag Type</option>
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
                                <input type="number" name="packing_items[0][bag_size]" class="form-control bag-size" step="0.01" value="0" min="0">
                            </td>
                            <td class="p-2">
                                <input type="number" name="packing_items[0][metric_tons]" class="form-control metric-tons" value="0" step="0.001" min="0">
                            </td>
                            <td class="p-2" style="display: none;">
                                <input type="number" name="packing_items[0][maunds]" class="form-control maunds" value="0" step="0.01" min="0">
                            </td>
                            <td class="p-2">
                                <input type="number" name="packing_items[0][no_of_bags]" class="form-control no_of_bags" value="0" readonly>
                            </td>
                            <td class="p-2" style="display: none;">
                                <input type="number" name="packing_items[0][total_kgs]" class="form-control total-kgs" value="0" readonly>
                            </td>
                            <td class="p-2">
                                <input type="number" name="packing_items[0][stuffing_in_container]" class="form-control stuffing-in-container" value="0" step="0.001" min="0">
                            </td>
                            <td class="p-2">
                                <input type="number" name="packing_items[0][no_of_containers]" class="form-control no-of-containers" value="0" min="0">
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
                            <td class="p-2" style="display: none;">
                                <input type="number" name="packing_items[0][amount_pkr]" class="form-control amount_pkr" value="0" min="0" readonly>
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
                    <strong>Total Packing MT: <span id="display_total_mt">0.000</span></strong>
                    <div id="sauda_qty_warning" class="text-danger" style="display:none; font-weight:bold;">
                        Warning: Quotation Qty (<span id="warn_quot_qty">0</span> MT) exceeds Sauda Qty (<span id="warn_sauda_qty">0</span> MT).
                    </div>
                </div>
            </div>

        <div class="col-md-12 mt-3">
            <h6 class="header-heading-sepration">Additional Information</h6>
            <div class="form-group">
                <textarea name="additional_info" class="form-control" rows="4" placeholder="Enter any additional details here...">{{ old('additional_info', $quotation->additional_info) }}</textarea>
            </div>
        </div>
    </div>

    <div class="row bottom-button-bar">
        <div class="col-12 mb-3 text-right">
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

    $('#productSelect').on('change', function() {
        var productId = $(this).val();
        if (productId) {
            $.get('{{ route('get.product_specs.quotation', '') }}/' + productId, function(data) {
                $('#productSpecs').html(data);
                $('#specificationsSection').show();
            });
        } else {
            $('#productSpecs').html('');
            $('#specificationsSection').hide();
        }
    });

    // ---- ROW LEVEL CALCULATIONS ----
    $(document).on('input', '.metric-tons, .bag-size', function() {
        let row = $(this).closest('tr');
        calculateRowFromMT(row);
    });

    $(document).on('input', '.maunds', function() {
        let row = $(this).closest('tr');
        calculateRowFromMaunds(row);
    });

    $(document).on('input', '.rates', function() {
        let row = $(this).closest('tr');
        calculateRowFromRate(row);
    });

    $(document).on('input', '.rates_mnd', function() {
        let row = $(this).closest('tr');
        calculateRowFromRateMnd(row);
    });

    $(document).on('input', '.stuffing-in-container', function() {
        let row = $(this).closest('tr');
        let mt = parseFloat(row.find('.metric-tons').val()) || 0;
        let stuffing = parseFloat($(this).val()) || 0;
        if (stuffing > 0) {
            row.find('.no-of-containers').val(Math.ceil(mt / stuffing));
        }
    });

    $(document).on('input', '.no-of-containers', function() {
        let row = $(this).closest('tr');
        let mt = parseFloat(row.find('.metric-tons').val()) || 0;
        let containers = parseInt($(this).val()) || 0;
        if (containers > 0) {
            row.find('.stuffing-in-container').val((mt / containers).toFixed(3));
        }
    });

    function calculateRowFromMT(row) {
        let mt = parseFloat(row.find('.metric-tons').val()) || 0;
        let totalKgs = mt * 1000;
        let bagSize = parseFloat(row.find('.bag-size').val()) || 0;
        let bags = bagSize > 0 ? (totalKgs / bagSize) : 0;
        row.find('.maunds').val((mt * 25).toFixed(2));
        row.find('.total-kgs').val(totalKgs.toFixed(2));
        row.find('.no_of_bags').val(Math.ceil(bags));
        
        // Auto-calculate containers if stuffing is set
        let stuffing = parseFloat(row.find('.stuffing-in-container').val()) || 0;
        if (stuffing > 0) {
            row.find('.no-of-containers').val(Math.ceil(mt / stuffing));
        }
        
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
        
        // Auto-calculate containers if stuffing is set
        let stuffing = parseFloat(row.find('.stuffing-in-container').val()) || 0;
        if (stuffing > 0) {
            row.find('.no-of-containers').val(Math.ceil(mt / stuffing));
        }

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
        calculateOverallTotals();
    }

    let selectedSaudaQty = {{ $quotation->exportSoda->total_qty_mt ?? 0 }};

    function calculateOverallTotals() {
        let totalMt = 0;
        let totalAmount = 0;
        $('#packingItems tr.packing-item').each(function() {
            totalMt += parseFloat($(this).find('.metric-tons').val()) || 0;
            totalAmount += parseFloat($(this).find('.amount').val()) || 0;
        });
        $('#display_total_mt').text(totalMt.toFixed(3));
        
        // Sauda Quantity Validation
        if (selectedSaudaQty > 0 && totalMt > selectedSaudaQty) {
            $('#sauda_qty_warning').show();
            $('#warn_quot_qty').text(totalMt.toFixed(3));
            $('#warn_sauda_qty').text(selectedSaudaQty.toFixed(3));
            $('.submitbutton').attr('disabled', true);
        } else {
            $('#sauda_qty_warning').hide();
            $('.submitbutton').attr('disabled', false);
        }
        
        calculateCommissionFields(totalAmount, totalMt);
    }

    // ---- COMMISSION CALCULATIONS ----
    $(document).on('input', '#commission_percentage', function() {
        let totalAmount = 0;
        let totalMt = 0;
        $('#packingItems tr.packing-item').each(function() {
            totalAmount += parseFloat($(this).find('.amount').val()) || 0;
            totalMt += parseFloat($(this).find('.metric-tons').val()) || 0;
        });
        
        let percentage = parseFloat($(this).val()) || 0;
        let commission = (totalAmount * percentage) / 100;
        let amtPerTon = totalMt > 0 ? (commission / totalMt) : 0;
        
        $('#commission').val(commission.toFixed(2));
        $('#commission_amount_per_ton').val(amtPerTon.toFixed(2));
    });

    $(document).on('input', '#commission_amount_per_ton', function() {
        let totalAmount = 0;
        let totalMt = 0;
        $('#packingItems tr.packing-item').each(function() {
            totalAmount += parseFloat($(this).find('.amount').val()) || 0;
            totalMt += parseFloat($(this).find('.metric-tons').val()) || 0;
        });
        
        let amtPerTon = parseFloat($(this).val()) || 0;
        let commission = totalMt * amtPerTon;
        let percentage = totalAmount > 0 ? (commission / totalAmount) * 100 : 0;
        
        $('#commission').val(commission.toFixed(2));
        $('#commission_percentage').val(percentage.toFixed(2));
    });

    function calculateCommissionFields(totalAmount, totalMt) {
        let percentage = parseFloat($('#commission_percentage').val());
        let amtPerTon = parseFloat($('#commission_amount_per_ton').val());
        
        if (!isNaN(percentage) && percentage > 0) {
            let commission = (totalAmount * percentage) / 100;
            let calculatedAmtPerTon = totalMt > 0 ? (commission / totalMt) : 0;
            $('#commission').val(commission.toFixed(2));
            $('#commission_amount_per_ton').val(calculatedAmtPerTon.toFixed(2));
        } else if (!isNaN(amtPerTon) && amtPerTon > 0) {
            let commission = totalMt * amtPerTon;
            let calculatedPercentage = totalAmount > 0 ? (commission / totalAmount) * 100 : 0;
            $('#commission').val(commission.toFixed(2));
            $('#commission_percentage').val(calculatedPercentage.toFixed(2));
        }
    }



    // Initialization on load
    calculateOverallTotals();

    if ($('input[name="export_soda_id"]').val()) {
        $('#productSelect').prop('disabled', true);
    }

    $('#ajaxSubmit').on('submit', function() {
        $('#productSelect').prop('disabled', false);
    });
    
    // Initial reverse-calculation of commission on page load
    let totalMt_load = 0;
    let totalAmount_load = 0;
    $('#packingItems tr.packing-item').each(function() {
        totalMt_load += parseFloat($(this).find('.metric-tons').val()) || 0;
        totalAmount_load += parseFloat($(this).find('.amount').val()) || 0;
    });

    let initialCommission = parseFloat($('#commission').val()) || 0;
    if (initialCommission > 0) {
        if (totalAmount_load > 0) {
            $('#commission_percentage').val(((initialCommission / totalAmount_load) * 100).toFixed(2));
        }
        if (totalMt_load > 0) {
            $('#commission_amount_per_ton').val((initialCommission / totalMt_load).toFixed(2));
        }
    }


    // Export Sauda Autofill
    $('select[name="export_soda_id"]').on('change', function() {
        var saudaId = $(this).val();
        if (saudaId) {
            $.get('{{ route('quotation.get-sauda-details', '') }}/' + saudaId, function(data) {
                selectedSaudaQty = parseFloat(data.total_qty_mt) || 0;
                // Basic Info
                $('#buyerSelect').val(data.buyer_id).trigger('change');
                $('#productSelect').val(data.product_id).trigger('change');
                
                // Direct fill buyer details if available
                if (data.buyer) {
                    $('#buyer_phone').val(data.buyer.phone || data.buyer.owner_mobile_no || '');
                    $('#buyer_email').val(data.buyer.email || '');
                    $('#buyer_address').val(data.buyer.address || '');
                }
                
                // Export Details
                $('select[name="incoterm_id"]').val(data.incoterm_id).trigger('change');
                $('select[name="mode_of_term_id"]').val(data.mode_of_term_id).trigger('change');
                
                // Commission
                $('#commission_percentage').val(data.commission_percentage);
                $('#commission_amount_per_ton').val(data.commission_amount_per_ton);
                $('#commission').val(data.commission);
                
                // Additional Info
                $('textarea[name="additional_info"]').val(data.additional_info);
                
                // Packing Items
                if (data.packing_items && data.packing_items.length > 0) {
                    $('#packingItems').empty();
                    data.packing_items.forEach(function(item, index) {
                        addNewPackingItemWithData(item, index);
                    });
                }
            });
        }
    });

    function addNewPackingItemWithData(item, index) {
        var firstRowTemplate = `
            <tr class="packing-item">
                <td class="p-2">
                    <select name="packing_items[${index}][bag_type_id]" class="form-control select2">
                        <option value="">Select Bag Type</option>
                        @foreach ($bagTypes as $bagType)
                            <option value="{{ $bagType->id }}">{{ $bagType->name }}</option>
                        @endforeach
                    </select>
                </td>
                <td class="p-2">
                    <select name="packing_items[${index}][bag_packing_id]" class="form-control select2">
                        <option value="">Packing</option>
                        @foreach ($bagPackings as $packing)
                            <option value="{{ $packing->id }}">{{ $packing->name }}</option>
                        @endforeach
                    </select>
                </td>
                <td class="p-2">
                    <input type="number" name="packing_items[${index}][bag_size]" class="form-control bag-size" step="0.01" value="${item.bag_size || 0}" min="0">
                </td>
                <td class="p-2">
                    <input type="number" name="packing_items[${index}][metric_tons]" class="form-control metric-tons" value="${item.metric_tons || 0}" step="0.001" min="0">
                </td>
                <td class="p-2">
                    <input type="number" name="packing_items[${index}][no_of_bags]" class="form-control no_of_bags" value="${item.no_of_bags || 0}" readonly>
                </td>
                <td class="p-2">
                    <input type="number" name="packing_items[${index}][stuffing_in_container]" class="form-control stuffing-in-container" value="${item.stuffing_in_container || 0}" step="0.001" min="0">
                </td>
                <td class="p-2">
                    <input type="number" name="packing_items[${index}][no_of_containers]" class="form-control no-of-containers" value="${item.no_of_containers || 0}" min="0">
                </td>
                <td class="p-2">
                    <input type="number" name="packing_items[${index}][rate]" class="form-control rates" value="${item.rate || 0}" step="0.01" min="0">
                </td>
                <td class="p-2">
                    <input type="number" name="packing_items[${index}][amount]" class="form-control amount" value="${item.amount || 0}" min="0" readonly>
                </td>
                <td class="text-center p-2">
                    <button type="button" class="btn btn-sm btn-danger remove-packing-item">
                        <i class="ft-trash-2"></i>
                    </button>
                </td>
            </tr>`;
        
        var $newRow = $(firstRowTemplate);
        
        // Set dropdown values
        $newRow.find('select[name*="bag_type_id"]').val(item.bag_type_id);
        $newRow.find('select[name*="bag_packing_id"]').val(item.bag_packing_id);
        
        $('#packingItems').append($newRow);
        $newRow.find('.select2').select2({ width: '100%' });
        
        // Trigger calculation for the row
        calculateRowAmount($newRow);
    }

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

    // Global AJAX Error Handler to Highlight Red Fields for Array Validation
    $(document).ajaxComplete(function(event, jqXHR, settings) {
        if (jqXHR.status === 422) {
            let response = jqXHR.responseJSON;
            if (response && response.errors) {
                $.each(response.errors, function(key, value) {
                    let fieldName = key;
                    if (key.indexOf('.') !== -1) {
                        let parts = key.split('.');
                        fieldName = parts[0];
                        for (let i = 1; i < parts.length; i++) {
                            fieldName += '[' + parts[i] + ']';
                        }
                    }
                    
                    let $input = $('[name="' + fieldName + '"]');
                    if ($input.length) {
                        $input.addClass('is-invalid');
                        if ($input.hasClass('select2-hidden-accessible')) {
                            $input.next('.select2-container').find('.select2-selection').css('border-color', '#ff0000');
                        }
                    }
                });
            }
        }
    });

    $(document).on('input change', 'input, select', function() {
        $(this).removeClass('is-invalid');
        if ($(this).hasClass('select2-hidden-accessible')) {
            $(this).next('.select2-container').find('.select2-selection').css('border-color', '');
        }
    });
});
</script>
