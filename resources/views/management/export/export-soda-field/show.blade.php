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
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Reference #:</label>
                        <input type="text" class="form-control" value="{{ $exportSodaField->reference }}" readonly>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Buyer's Name:</label>
                        <select class="form-control select2" disabled>
                            <option value="">Select Buyer</option>
                            @foreach ($users as $user)
                                <option value="{{ $user->id }}" {{ $exportSodaField->buyer_id == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Product / Commodity:</label>
                        <select class="form-control select2" disabled>
                            <option value="">Select Product</option>
                            @foreach ($products as $product)
                                <option value="{{ $product->id }}" {{ $exportSodaField->product_id == $product->id ? 'selected' : '' }}>{{ $product->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Shipment Period:</label>
                        <input type="text" class="form-control" value="{{ ($exportSodaField->shipment_date_from ? $exportSodaField->shipment_date_from->format('d-M-Y') : 'N/A') . ' to ' . ($exportSodaField->shipment_date_to ? $exportSodaField->shipment_date_to->format('d-M-Y') : 'N/A') }}" readonly>
                    </div>
                </div>
            </div>
        </div>

        {{-- ====== COMMISSION ====== --}}
        <div class="col-md-12 mt-4">
            <h6 class="header-heading-sepration">Commission</h6>
            @php
                $totalMt = $exportSodaField->packingItems->sum('metric_tons');
                $totalAmount = $exportSodaField->packingItems->sum('amount');
                $commission = $exportSodaField->commission ?? 0;
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

    <div class="col-4">
        {{-- ====== EXPORT DETAILS SIDEBAR ====== --}}
        <h6 class="header-heading-sepration">Export Details</h6>
        <div class="table-responsive">
            <table class="table table-bordered spacing-table" style="margin-bottom:0;">
                <tr>
                    <td style="width:40%;font-weight:bold;vertical-align:middle;">INCO TERM</td>
                    <td>
                        <select class="form-control select2" disabled>
                            @foreach ($incoterms as $incoterm)
                                <option value="{{ $incoterm->id }}" {{ $exportSodaField->incoterm_id == $incoterm->id ? 'selected' : '' }}>{{ $incoterm->name }}</option>
                            @endforeach
                        </select>
                    </td>
                </tr>
                <tr>
                    <td style="font-weight:bold;vertical-align:middle;">PAYMENT TERM</td>
                    <td>
                        <select class="form-control select2" disabled>
                            @foreach ($modeofterms as $term)
                                <option value="{{ $term->id }}" {{ $exportSodaField->mode_of_term_id == $term->id ? 'selected' : '' }}>{{ $term->name }}</option>
                            @endforeach
                        </select>
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
                        <th>Rate/Ton</th>
                        <th style="display: none;">Rate/Mnd</th>
                        <th>Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($exportSodaField->packingItems as $item)
                        <tr>
                            <td class="p-2"><input type="text" class="form-control" value="{{ $item->bagType->name ?? 'N/A' }}" readonly></td>
                            <td class="p-2"><input type="text" class="form-control" value="{{ $item->bagPacking->name ?? 'N/A' }}" readonly></td>
                            <td class="p-2"><input type="text" class="form-control" value="{{ $item->bag_size }}" readonly></td>
                            <td class="p-2"><input type="text" class="form-control" value="{{ number_format($item->metric_tons, 3) }}" readonly></td>
                            <td class="p-2" style="display: none;"><input type="text" class="form-control" value="{{ number_format($item->maunds, 2) }}" readonly></td>
                            <td class="p-2"><input type="text" class="form-control" value="{{ number_format($item->no_of_bags, 0) }}" readonly></td>
                            <td class="p-2"><input type="text" class="form-control" value="{{ number_format($item->rate, 2) }}" readonly></td>
                            <td class="p-2" style="display: none;"><input type="text" class="form-control" value="{{ number_format($item->rate_per_maund, 2) }}" readonly></td>
                            <td class="p-2"><input type="text" class="form-control" value="{{ number_format($item->amount, 2) }}" readonly></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            <h6 class="header-heading-sepration">Additional Information</h6>
            <textarea class="form-control" rows="4" readonly>{{ $exportSodaField->additional_info }}</textarea>
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
        $('.select2').select2({ width: '100%' });
    });
</script>
