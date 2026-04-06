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
        padding-top: 10px !important;
        padding-bottom: 10px !important;
    }
    
    .header-heading-sepration {
        border-bottom: 2px solid #e3e3e3;
        padding-bottom: 8px;
        margin-bottom: 15px;
        font-weight: bold;
        color: #444;
        text-transform: uppercase;
        font-size: 0.9rem;
    }
    
    .view-label {
        font-weight: 600;
        color: #777;
        font-size: 0.8rem;
        margin-bottom: 2px;
        display: block;
    }
    
    .view-value {
        font-weight: 500;
        color: #333;
        word-break: break-all;
    }
</style>

<div class="row form-mar">
    <div class="col-8">
        {{-- ====== BUYER & INFO ====== --}}
        <div class="col-md-12">
            <h6 class="header-heading-sepration">Buyer & Information</h6>
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group mb-2">
                        <span class="view-label">Voucher No</span>
                        <div class="view-value">{{ $exportOrder->voucher_no }}</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group mb-2">
                        <span class="view-label">Contract No</span>
                        <div class="view-value">{{ $exportOrder->contract_no }}</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group mb-2">
                        <span class="view-label">Voucher Date</span>
                        <div class="view-value">
                            @if($exportOrder->voucher_date)
                                {{ $exportOrder->voucher_date->format('d-M-Y') }}
                            @else
                                -
                            @endif
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group mb-2">
                        <span class="view-label">Buyer's Name</span>
                        <div class="view-value">{{ $exportOrder->buyer->name ?? '-' }}</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group mb-2">
                        <span class="view-label">Contact No</span>
                        <div class="view-value">{{ $exportOrder->buyer->phone ?? $exportOrder->buyer->owner_mobile_no ?? '-' }}</div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group mb-2">
                        <span class="view-label">Buyer Address</span>
                        <div class="view-value">{{ $exportOrder->buyer->address ?? '-' }}</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ====== PRODUCT ====== --}}
        <div class="col-md-12 mt-4">
            <h6 class="header-heading-sepration">Product / Commodity</h6>
            <div class="row">
                <div class="col-md-12">
                    <div class="form-group mb-3">
                        <span class="view-label">Product</span>
                        <div class="view-value font-weight-bold" style="font-size: 1.1rem;">{{ $exportOrder->product->name ?? '-' }}</div>
                    </div>
                </div>
            </div>

            {{-- Specifications Section --}}
            @if($exportOrder->specifications->count())
            <div class="mt-2">
                <h6 class="header-heading-sepration">Specifications</h6>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-sm">
                        <thead class="thead-light">
                            <tr>
                                <th width="45%">Specification</th>
                                <th width="30%">Value</th>
                                <th width="25%">Type</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($exportOrder->specifications as $spec)
                                <tr>
                                    <td><strong>{{ $spec->spec_name }}</strong></td>
                                    <td>
                                        {{ $spec->spec_value ?? 0 }} <span class="badge badge-secondary ml-1">{{ $spec->slabType->qc_symbol ?? '' }}</span>
                                    </td>
                                    <td>{{ ucfirst($spec->value_type ?? 'min') }}</td>
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
                    $totalMt = $exportOrder->packingItems->sum('metric_tons');
                    $totalAmount = $exportOrder->packingItems->sum('amount');
                    $commission = $exportOrder->commission ?? 0;
                    $commissionPercentage = $totalAmount > 0 ? ($commission / $totalAmount) * 100 : 0;
                    $amtPerTon = $totalMt > 0 ? ($commission / $totalMt) : 0;
                @endphp
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group mb-2">
                            <span class="view-label">Commission (%)</span>
                            <div class="view-value">{{ number_format($commissionPercentage, 2) }}%</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group mb-2">
                            <span class="view-label">Amt/Ton</span>
                            <div class="view-value">{{ number_format($amtPerTon, 2) }}</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group mb-2">
                            <span class="view-label">Total Commission</span>
                            <div class="view-value">{{ number_format($commission, 2) }} {{ $exportOrder->currency->currency_name ?? '' }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-4">
        {{-- ====== EXPORT DETAILS SIDEBAR ====== --}}
        <h6 class="header-heading-sepration">Export Details</h6>
        <div class="card border-light shadow-none bg-light-grey mb-0">
            <div class="card-body p-0">
                <table class="table table-bordered table-sm spacing-table mb-0 bg-white">
                    <tr>
                        <td style="width:45%;font-weight:bold; color: #666;">INCOTERMS</td>
                        <td class="view-value font-weight-bold">{{ $exportOrder->incoterm->name ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td style="font-weight:bold; color: #666;">TERM MODE</td>
                        <td class="view-value">{{ $exportOrder->modeOfTerm->name ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td style="font-weight:bold; color: #666;">TRANSPORT</td>
                        <td class="view-value">{{ $exportOrder->modeOfTransport->name ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td style="font-weight:bold; color: #666;">ORIGIN</td>
                        <td class="view-value">{{ $exportOrder->originCountry->name ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td style="font-weight:bold; color: #666;">DISCHARGE PORT</td>
                        <td class="view-value">{{ $exportOrder->portOfDischarge->name ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td style="font-weight:bold; color: #666;">LOADING PORT</td>
                        <td class="view-value">{{ $exportOrder->portOfLoading->name ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td style="font-weight:bold; color: #666;">ADVANCE (%)</td>
                        <td class="view-value">{{ $exportOrder->advance_payment ?? 0 }}%</td>
                    </tr>
                    <tr>
                        <td style="font-weight:bold; color: #666;">PAYMENT DAYS</td>
                        <td class="view-value">{{ $exportOrder->payment_days ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td style="font-weight:bold; color: #666;">CURRENCY</td>
                        <td class="view-value font-weight-bold text-success">{{ $exportOrder->currency->currency_name ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td style="font-weight:bold; color: #666;">EX. RATE</td>
                        <td class="view-value">{{ number_format($exportOrder->currency_rate, 2) }}</td>
                    </tr>
                    <tr>
                        <td style="font-weight:bold; color: #666;">DESTINATION</td>
                        <td class="view-value">{{ $exportOrder->final_destination ?? '-' }}</td>
                    </tr>
                </table>
            </div>
        </div>

        @if($exportOrder->marking_labeling)
        <div class="mt-4">
            <h6 class="header-heading-sepration">Marking / Labeling</h6>
            <div class="p-2 bg-light border rounded" style="white-space: pre-line;">
                {{ $exportOrder->marking_labeling }}
            </div>
        </div>
        @endif
    </div>

    {{-- ====== PACKING DETAILS (Full Width) ====== --}}
    <div class="col-12 mt-4">
        <h6 class="header-heading-sepration">Packing Details</h6>
        <div class="table-responsive">
            <table class="table table-bordered table-sm mb-0 text-nowrap">
                <thead class="thead-dark">
                    <tr>
                        <th>Brand</th>
                        <th>Bag Type</th>
                        <th>Packing Size</th>
                        <th>Bags</th>
                        <th>MTs</th>
                        <th>Stuffing</th>
                        <th>Cont.</th>
                        <th>Rate/Ton</th>
                        <th>Amount</th>
                        <th>Amount (PKR)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($exportOrder->packingItems as $item)
                        <tr class="bg-light">
                            <td class="font-weight-bold">{{ $item->brand->name ?? 'N/A' }}</td>
                            <td>{{ $item->bagType->name ?? 'N/A' }}</td>
                            <td>{{ number_format($item->bag_size, 2) }} kg</td>
                            <td class="font-weight-bold">{{ number_format($item->no_of_bags, 0) }}</td>
                            <td class="text-primary font-weight-bold">{{ number_format($item->metric_tons, 3) }}</td>
                            <td>{{ number_format($item->stuffing_in_container, 3) }}</td>
                            <td>{{ $item->no_of_containers }}</td>
                            <td>{{ number_format($item->rate, 2) }}</td>
                            <td class="font-weight-bold">{{ number_format($item->amount, 2) }}</td>
                            <td class="font-weight-bold text-success">{{ number_format($item->amount_pkr, 2) }}</td>
                        </tr>
                        @if($item->subItems->count() > 0)
                            <tr>
                                <td colspan="10" class="p-0">
                                    <div class="p-2 bg-white">
                                        <h6 class="view-label mb-2 px-2" style="font-style: italic;">Master Packing Breakdown:</h6>
                                        <table class="table table-sm table-bordered mb-0 bg-light-grey mx-auto" style="width: 95%;">
                                            <thead class="bg-info text-white">
                                                <tr>
                                                    <th>Sub Bag Type</th>
                                                    <th>Size</th>
                                                    <th>Primary/Master</th>
                                                    <th>Bags</th>
                                                    <th>Total Bags</th>
                                                    <th>Brand</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($item->subItems as $sub)
                                                <tr>
                                                    <td>{{ $sub->bagType->name ?? 'N/A' }}</td>
                                                    <td>{{ $sub->bagSize->size ?? 'N/A' }} kg</td>
                                                    <td>{{ $sub->no_of_primary_bags }}</td>
                                                    <td>{{ $sub->no_of_bags }}</td>
                                                    <td class="font-weight-bold">{{ $sub->total_bags }}</td>
                                                    <td>{{ $sub->brand->name ?? 'N/A' }}</td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </td>
                            </tr>
                        @endif
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="row mt-4">
            <div class="col-md-6">
                <div class="card border-secondary shadow-none">
                    <div class="card-header bg-light py-1">
                        <h6 class="mb-0 font-weight-bold">Additional Information</h6>
                    </div>
                    <div class="card-body p-2" style="min-height: 100px; white-space: pre-line;">
                        {{ $exportOrder->additional_info ?? 'No additional information.' }}
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card border-secondary shadow-none">
                    <div class="card-header bg-light py-1">
                        <h6 class="mb-0 font-weight-bold">Other Specifications</h6>
                    </div>
                    <div class="card-body p-2" style="min-height: 100px; white-space: pre-line;">
                        {{ $exportOrder->other_specifications ?? 'No other specifications.' }}
                    </div>
                </div>
            </div>
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
