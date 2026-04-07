<style>
    /* Chrome, Safari, Edge, Opera */
    input[type=number]::-webkit-outer-spin-button,
    input[type=number]::-webkit-inner-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }

    /* Firefox */
    input[type=number] {
        -moz-appearance: textfield;
    }

    .spacing-table td {
        padding-top: 15px !important;
        padding-bottom: 15px !important;
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
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Buyer's Name</label>
                        <input type="text" class="form-control" value="{{ $quotation->buyer->name ?? '-' }}" readonly>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Contact No</label>
                        <input type="text" class="form-control" value="{{ $quotation->buyer->phone ?? $quotation->buyer->owner_mobile_no ?? '-' }}" readonly>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="text" class="form-control" value="{{ $quotation->buyer->email ?? '-' }}" readonly>
                    </div>
                </div>
                <div class="col-md-8">
                    <div class="form-group">
                        <label>Buyer Address</label>
                        <input type="text" class="form-control" value="{{ $quotation->buyer->address ?? '-' }}" readonly>
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
                        <label>Product</label>
                        <select class="form-control select2" disabled>
                            <option value="">Select Product</option>
                            @foreach ($products as $product)
                                <option value="{{ $product->id }}" {{ $quotation->product_id == $product->id ? 'selected' : '' }}>{{ $product->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            {{-- Specifications Section --}}
            @if($quotation->specifications->count())
            <div class="mt-2">
                <h6 class="header-heading-sepration">Specifications</h6>
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
                            @foreach ($quotation->specifications as $spec)
                                <tr>
                                    <td><strong>{{ $spec->spec_name }}</strong></td>
                                    <td>
                                        <div class="input-group">
                                            <input type="text" value="{{ $spec->spec_value ?? 0 }}" class="form-control form-control-sm" readonly>
                                            <div class="input-group-prepend">
                                                <button class="btn btn-secondary" type="button">{{ $spec->slabType->qc_symbol ?? 'N/A' }}</button>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <input type="text" class="form-control" value="{{ ucfirst($spec->value_type ?? 'min') }}" readonly>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            {{-- Commission Section --}}
            <div class="mt-4">
                <h6 class="header-heading-sepration">Commission</h6>
                @php
                    $totalMt = $quotation->packingItems->sum('metric_tons');
                    $totalAmount = $quotation->packingItems->sum('amount');
                    $commission = $quotation->commission ?? 0;
                    $commissionPercentage = $totalAmount > 0 ? ($commission / $totalAmount) * 100 : 0;
                    $amtPerTon = $totalMt > 0 ? ($commission / $totalMt) : 0;
                @endphp
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Commission (%):</label>
                            <input type="text" class="form-control" value="{{ number_format($commissionPercentage, 2) }}%" readonly>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Amt/Ton:</label>
                            <input type="text" class="form-control" value="{{ number_format($amtPerTon, 2) }}" readonly>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Total Commission:</label>
                            <input type="text" class="form-control" value="{{ number_format($commission, 2) }}" readonly>
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
                        <select class="form-control select2" disabled>
                            @foreach ($incoterms as $incoterm)
                                <option value="{{ $incoterm->id }}" {{ $quotation->incoterm_id == $incoterm->id ? 'selected' : '' }}>{{ $incoterm->name }}</option>
                            @endforeach
                        </select>
                    </td>
                </tr>
                <tr>
                    <td style="font-weight:bold;vertical-align:middle;">PACKING TYPE</td>
                    <td>
                        <select class="form-control select2" disabled>
                            <option value="In Conatiner" {{ $quotation->packing_type == 'In Conatiner' ? 'selected' : '' }}>IN CONTAINER</option>
                            <option value="In Bulk" {{ $quotation->packing_type == 'In Bulk' ? 'selected' : '' }}>IN BULK</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td style="font-weight:bold;vertical-align:middle;">MODE OF TERM</td>
                    <td>
                        <select class="form-control select2" disabled>
                            @foreach ($modeofterms as $term)
                                <option value="{{ $term->id }}" {{ $quotation->mode_of_term_id == $term->id ? 'selected' : '' }}>{{ $term->name }}</option>
                            @endforeach
                        </select>
                    </td>
                </tr>
                <tr>
                    <td style="font-weight:bold;vertical-align:middle;">MODE OF TRANSPORT</td>
                    <td>
                        <select class="form-control select2" disabled>
                            @foreach ($modeoftransport as $transport)
                                <option value="{{ $transport->id }}" {{ $quotation->mode_of_transport_id == $transport->id ? 'selected' : '' }}>{{ $transport->name }}</option>
                            @endforeach
                        </select>
                    </td>
                </tr>
                <tr>
                    <td style="font-weight:bold;vertical-align:middle;">ORIGIN</td>
                    <td>
                        <select class="form-control select2" disabled>
                            @foreach ($countries as $country)
                                <option value="{{ $country->id }}" {{ $quotation->origin_country_id == $country->id ? 'selected' : '' }}>{{ $country->name }}</option>
                            @endforeach
                        </select>
                    </td>
                </tr>
                <tr>
                    <td style="font-weight:bold;vertical-align:middle;">PORT OF DISCHARGE</td>
                    <td>
                        <select class="form-control select2" disabled>
                            @foreach ($ports as $port)
                                <option value="{{ $port->id }}" {{ $quotation->port_of_discharge_id == $port->id ? 'selected' : '' }}>{{ $port->name }}</option>
                            @endforeach
                        </select>
                    </td>
                </tr>
                <tr>
                    <td style="font-weight:bold;vertical-align:middle;">PORT OF LOADING</td>
                    <td>
                        <select class="form-control select2" disabled>
                            @foreach ($ports as $port)
                                <option value="{{ $port->id }}" {{ $quotation->port_of_loading_id == $port->id ? 'selected' : '' }}>{{ $port->name }}</option>
                            @endforeach
                        </select>
                    </td>
                </tr>
                <tr>
                    <td style="font-weight:bold;vertical-align:middle;">ADVANCE PAYMENT (%)</td>
                    <td>
                        <input type="text" class="form-control" value="{{ $quotation->advance_payment }}%" readonly>
                    </td>
                </tr>
                <tr>
                    <td style="font-weight:bold;vertical-align:middle;">PAYMENT DAYS</td>
                    <td>
                        <input type="text" class="form-control" value="{{ $quotation->payment_days }}" readonly>
                    </td>
                </tr>
                <tr>
                    <td style="font-weight:bold;vertical-align:middle;">CURRENCY</td>
                    <td>
                        <select class="form-control select2" disabled>
                            @foreach ($currencies as $currency)
                                <option value="{{ $currency->id }}" {{ $quotation->currency_id == $currency->id ? 'selected' : '' }}>{{ $currency->currency_name }}</option>
                            @endforeach
                        </select>
                    </td>
                </tr>
                <tr>
                    <td style="font-weight:bold;vertical-align:middle;">EXCHANGE RATE</td>
                    <td>
                        <input type="text" class="form-control" value="{{ number_format($quotation->currency_rate, 2) }}" readonly>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    {{-- ====== PACKING DETAILS (Full Width) ====== --}}
    <div class="col-12 mt-4">
        <h6 class="header-heading-sepration">Packing Details</h6>
        <div class="table-responsive">
            <table class="table table-bordered mb-0">
                <thead>
                    <tr>
                        <th>Bag Type</th>
                        <th>Packing</th>
                        <th>Packing Size (kg)</th>
                        <th>Qty (MT)</th>
                        <th style="display: none;">Qty (Mnds)</th>
                        <th>Bags</th>
                        <th>Stuffing (MT)</th>
                        <th>Containers</th>
                        <th>Rate/Ton</th>
                        <th style="display: none;">Rate/Mnd</th>
                        <th>Amount</th>
                        <th>Amount (PKR)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($quotation->packingItems as $item)
                        <tr>
                            <td class="p-2"><input type="text" class="form-control" value="{{ $item->bagType->name ?? 'N/A' }}" readonly></td>
                            <td class="p-2"><input type="text" class="form-control" value="{{ $item->bagPacking->name ?? 'N/A' }}" readonly></td>
                            <td class="p-2"><input type="text" class="form-control" value="{{ number_format($item->bag_size, 2) }}" readonly></td>
                            <td class="p-2"><input type="text" class="form-control" value="{{ number_format($item->metric_tons, 3) }}" readonly></td>
                            <td class="p-2" style="display: none;"><input type="text" class="form-control" value="{{ number_format($item->maunds, 2) }}" readonly></td>
                            <td class="p-2"><input type="text" class="form-control" value="{{ number_format($item->no_of_bags, 0) }}" readonly></td>
                            <td class="p-2"><input type="text" class="form-control" value="{{ number_format($quotation->stuffing_in_container, 3) }}" readonly></td>
                            <td class="p-2"><input type="text" class="form-control" value="{{ $quotation->no_of_containers }}" readonly></td>
                            <td class="p-2"><input type="text" class="form-control" value="{{ number_format($item->rate, 2) }}" readonly></td>
                            <td class="p-2" style="display: none;"><input type="text" class="form-control" value="{{ number_format($item->rate_per_maund, 2) }}" readonly></td>
                            <td class="p-2"><input type="text" class="form-control" value="{{ number_format($item->amount, 2) }}" readonly></td>
                            <td class="p-2"><input type="text" class="form-control" value="{{ number_format($item->amount_pkr, 2) }}" readonly></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            <h6 class="header-heading-sepration">Additional Information</h6>
            <textarea class="form-control" rows="4" readonly>{{ $quotation->additional_info }}</textarea>
        </div>
    </div>
</div>

<div class="row bottom-button-bar">
    <div class="col-12 mb-3">
        <a type="button" class="btn btn-danger modal-sidebar-close position-relative top-1 closebutton">Close</a>
    </div>
</div>

<script>
    $(document).ready(function() {
        $('.select2').select2({ width: '100%' });
    });
</script>
