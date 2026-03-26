<div class="row form-mar p-2">
    <div class="col-8">
        <h6 class="header-heading-sepration">Basic Information</h6>
        <div class="row mt-2">
            <div class="col-md-6">
                <div class="form-group">
                    <label>Reference #:</label>
                    <input type="text" value="{{ $exportSodaField->reference }}" class="form-control" readonly>
                </div>
            </div>

            <div class="col-md-6">
                <div class="form-group">
                    <label>Party Name (Buyer):</label>
                    <input type="text" value="{{ $exportSodaField->buyer->name ?? 'N/A' }}" class="form-control" readonly>
                </div>
            </div>

            <div class="col-md-12">
                <div class="form-group">
                    <label>Commodity:</label>
                    <input type="text" value="{{ $exportSodaField->product->name ?? 'N/A' }}" class="form-control" readonly>
                </div>
            </div>

            <div class="col-md-6 mt-2">
                <div class="form-group">
                    <label>Inco Term:</label>
                    <input type="text" value="{{ $exportSodaField->incoterm->name ?? 'N/A' }}" class="form-control" readonly>
                </div>
            </div>

            <div class="col-md-6 mt-2">
                <div class="form-group">
                    <label>Payment Term:</label>
                    <input type="text" value="{{ $exportSodaField->modeOfTerm->name ?? 'N/A' }}" class="form-control" readonly>
                </div>
            </div>

            <div class="col-md-6 mt-2">
                <div class="form-group">
                    <label>Shipment Period:</label>
                    <input type="text" value="{{ $exportSodaField->shipment_period ?? 'N/A' }}" class="form-control" readonly>
                </div>
            </div>

            <div class="col-md-6 mt-2">
                <div class="form-group">
                    <label>Commission:</label>
                    <input type="text" value="{{ $exportSodaField->commission ?? '0.00' }}" class="form-control" readonly>
                </div>
            </div>
        </div>
    </div>

    <div class="col-4">
        <h6 class="header-heading-sepration">Additional Information</h6>
        <div class="form-group mt-2">
            <textarea class="form-control" rows="12" readonly>{{ $exportSodaField->additional_info }}</textarea>
        </div>
    </div>

    {{-- ====== PACKING DETAILS ====== --}}
    <div class="col-12 mt-4">
        <h6 class="header-heading-sepration">Packing Details</h6>
        <div class="table-responsive">
            <table class="table table-bordered mb-0">
                <thead>
                    <tr>
                        <th>Brand</th>
                        <th>Bag Type</th>
                        <th>Packing</th>
                        <th>Condition</th>
                        <th>Color</th>
                        <th>Size (kg)</th>
                        <th>Qty (MT)</th>
                        <th>Qty (Mnds)</th>
                        <th>Bags</th>
                        <th>Rate/Ton</th>
                        <th>Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @php 
                        $items = method_exists($exportSodaField, 'packingItems') ? $exportSodaField->packingItems : collect();
                    @endphp
                    @forelse ($items as $item)
                    <tr>
                        <td>{{ $item->brand->name ?? 'N/A' }}</td>
                        <td>{{ $item->bagType->name ?? 'N/A' }}</td>
                        <td>{{ $item->bagPacking->name ?? 'N/A' }}</td>
                        <td>{{ $item->bagCondition->name ?? 'N/A' }}</td>
                        <td>{{ $item->bagColor->color ?? 'N/A' }}</td>
                        <td>{{ $item->bag_size }}</td>
                        <td>{{ number_format($item->metric_tons, 3) }}</td>
                        <td>{{ number_format($item->maunds, 2) }}</td>
                        <td>{{ number_format($item->no_of_bags) }}</td>
                        <td>{{ number_format($item->rate, 2) }}</td>
                        <td>{{ number_format($item->amount, 2) }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="11" class="text-center">No packing items found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="row mt-2 pr-2">
            <div class="col-md-12 text-right">
                <strong>Total Quantity (MT): {{ number_format($items->sum('metric_tons'), 3) }}</strong>
            </div>
        </div>
    </div>
</div>

<div class="row bottom-button-bar">
    <div class="col-12 mb-3 text-right">
        <a type="button" class="btn btn-danger modal-sidebar-close position-relative top-1 closebutton">Close</a>
    </div>
</div>
