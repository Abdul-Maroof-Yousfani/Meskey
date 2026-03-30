<div class="row">
    <div class="col-8">
        {{-- ====== BUYER & INFO ====== --}}
        <div class="col-md-12">
            <h6 class="header-heading-sepration">Buyer & Information</h6>
            <div class="table-responsive">
                <table class="table table-bordered">
                    <tr>
                        <th style="width:30%;">Export Sauda</th>
                        <td>{{ $quotation->exportSoda ? '#' . $quotation->exportSoda->id : 'N/A' }}</td>
                    </tr>
                    <tr>
                        <th>Buyer's Name</th>
                        <td>{{ $quotation->buyer->name ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <th>Contact No</th>
                        <td>{{ $quotation->buyer->phone ?? $quotation->buyer->owner_mobile_no ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <th>Email Address</th>
                        <td>{{ $quotation->buyer->email ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <th>Buyer Address</th>
                        <td>{{ $quotation->buyer->address ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <th>Product / Commodity</th>
                        <td>{{ $quotation->product->name ?? 'N/A' }}</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <div class="col-4">
        {{-- ====== EXPORT DETAILS SIDEBAR ====== --}}
        <h6 class="header-heading-sepration">Export Details</h6>
        <div class="table-responsive">
            <table class="table table-bordered">
                <tr>
                    <th style="width:50%;">INCOTERMS</th>
                    <td>{{ $quotation->incoterm->name ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <th>PACKING TYPE</th>
                    <td>{{ $quotation->packing_type ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <th>MODE OF TERM</th>
                    <td>{{ $quotation->modeOfTerm->name ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <th>MODE OF TRANSPORT</th>
                    <td>{{ $quotation->modeOfTransport->name ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <th>ORIGIN</th>
                    <td>{{ $quotation->originCountry->name ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <th>PORT OF DISCHARGE</th>
                    <td>{{ $quotation->portOfDischarge->name ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <th>PORT OF LOADING</th>
                    <td>{{ $quotation->portOfLoading->name ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <th>ADVANCE PAYMENT</th>
                    <td>{{ $quotation->advance_payment }}%</td>
                </tr>
                <tr>
                    <th>PAYMENT DAYS</th>
                    <td>{{ $quotation->payment_days ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <th>CURRENCY</th>
                    <td>{{ $quotation->currency->currency_name ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <th>EXCHANGE RATE</th>
                    <td>{{ number_format($quotation->currency_rate, 2) }}</td>
                </tr>
            </table>
        </div>
    </div>

    {{-- ====== PACKING DETAILS (Full Width) ====== --}}
    <div class="col-12 mt-4">
        <h6 class="header-heading-sepration">Packing Details</h6>
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr class="bg-light">
                        <th>Bag Type</th>
                        <th>Packing</th>
                        <th>Color</th>
                        <th>Size (kg)</th>
                        <th>Qty (MT)</th>
                        <th style="display: none;">Maunds</th>
                        <th>Bags</th>
                        <th>Total KGs</th>
                        <th>Rate/Ton</th>
                        <th style="display: none;">Rate/Mnd</th>
                        <th>Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @php $totalAmount = 0; @endphp
                    @foreach ($quotation->packingItems as $item)
                        @php $totalAmount += $item->amount; @endphp
                        <tr>
                            <td>{{ $item->bagType->name ?? 'N/A' }}</td>
                            <td>{{ $item->bagPacking->name ?? 'N/A' }}</td>
                            <td>{{ $item->bagColor->color ?? 'N/A' }}</td>
                            <td>{{ number_format($item->bag_size, 2) }}</td>
                            <td>{{ number_format($item->metric_tons, 3) }}</td>
                            <td style="display: none;">{{ number_format($item->maunds, 2) }}</td>
                            <td>{{ number_format($item->no_of_bags) }}</td>
                            <td>{{ number_format($item->total_kgs, 2) }}</td>
                            <td>{{ number_format($item->rate, 2) }}</td>
                            <td style="display: none;">{{ number_format($item->rate_per_maund, 2) }}</td>
                            <td>{{ number_format($item->amount, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="font-weight-bold bg-light">
                        <td colspan="4" class="text-right">Totals:</td>
                        <td>{{ number_format($quotation->packingItems->sum('metric_tons'), 3) }}</td>
                        <td style="display: none;">{{ number_format($quotation->packingItems->sum('maunds'), 2) }}</td>
                        <td>{{ number_format($quotation->packingItems->sum('no_of_bags')) }}</td>
                        <td>{{ number_format($quotation->packingItems->sum('total_kgs'), 2) }}</td>
                        <td></td>
                        <td>{{ number_format($totalAmount, 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
        
        {{-- ====== STUFFING & CONTAINERS ====== --}}
        <div class="mt-4">
            <h6 class="header-heading-sepration">Containers & Stuffing</h6>
            <div class="row">
                <div class="col-md-4">
                    <strong>Stuffing (MT):</strong> {{ number_format($quotation->stuffing_in_container, 3) }}
                </div>
                <div class="col-md-4" style="display: none;">
                    <strong>Stuffing (Maunds):</strong> {{ number_format($quotation->stuffing_maunds, 2) }}
                </div>
                <div class="col-md-4">
                    <strong>No of Containers:</strong> {{ $quotation->no_of_containers }}
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
