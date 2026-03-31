<div class="row form-mar p-2">
    <div class="col-8">
        <h6 class="header-heading-sepration">Basic Information</h6>
        <div class="table-responsive">
            <table class="table table-bordered">
                <tr>
                    <th style="width:30%;">Reference #</th>
                    <td>{{ $exportSodaField->reference }}</td>
                </tr>
                <tr>
                    <th>Party Name (Buyer)</th>
                    <td>{{ $exportSodaField->buyer->name ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <th>Commodity</th>
                    <td>{{ $exportSodaField->product->name ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <th>Shipment Period</th>
                    <td>{{ $exportSodaField->shipment_period ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <th>Commission</th>
                    <td>{{ $exportSodaField->commission ?? '0.00' }}</td>
                </tr>
            </table>
        </div>
    </div>

    <div class="col-4">
        <h6 class="header-heading-sepration">Export Details</h6>
        <div class="table-responsive">
            <table class="table table-bordered">
                <tr>
                    <th style="width:50%;">INCO TERM</th>
                    <td>{{ $exportSodaField->incoterm->name ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <th>PAYMENT TERM</th>
                    <td>{{ $exportSodaField->modeOfTerm->name ?? 'N/A' }}</td>
                </tr>
            </table>
        </div>
    </div>

    {{-- ====== PACKING DETAILS ====== --}}
    <div class="col-12 mt-4">
        <h6 class="header-heading-sepration">Packing Details</h6>
        <div class="table-responsive">
            <table class="table table-bordered mb-0">
                <thead>
                    <tr class="bg-light">
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
                    @php 
                        $items = method_exists($exportSodaField, 'packingItems') ? $exportSodaField->packingItems : collect();
                    @endphp
                    @forelse ($items as $item)
                    <tr>
                        <td>{{ $item->bagType->name ?? 'N/A' }}</td>
                        <td>{{ $item->bagPacking->name ?? 'N/A' }}</td>

                        <td>{{ $item->bag_size }}</td>
                        <td>{{ number_format($item->metric_tons, 3) }}</td>
                        <td style="display: none;">{{ number_format($item->maunds, 2) }}</td>
                        <td>{{ number_format($item->no_of_bags) }}</td>
                        <td>{{ number_format($item->rate, 2) }}</td>
                        <td style="display: none;">{{ number_format($item->rate_per_maund, 2) }}</td>
                        <td>{{ number_format($item->amount, 2) }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center">No packing items found.</td>
                    </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr class="font-weight-bold bg-light">
                        <td colspan="4" class="text-right">Totals:</td>
                        <td>{{ number_format($items->sum('metric_tons'), 3) }}</td>
                        <td style="display: none;">{{ number_format($items->sum('maunds'), 2) }}</td>
                        <td>{{ number_format($items->sum('no_of_bags')) }}</td>
                        <td></td>
                        <td style="display: none;">{{ number_format($items->sum('rate_per_maund'), 2) }}</td>
                        <td>{{ number_format($items->sum('amount'), 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <div class="col-md-12 mt-3">
        <h6 class="header-heading-sepration">Additional Information</h6>
        <div class="card p-2 bg-light">
            {!! nl2br(e($exportSodaField->additional_info)) !!}
        </div>
    </div>
</div>

<div class="row bottom-button-bar">
    <div class="col-12 mb-3 text-right">
        <a type="button" class="btn btn-danger modal-sidebar-close position-relative top-1 closebutton">Close</a>
    </div>
</div>
