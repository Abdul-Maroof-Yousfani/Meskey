<div class="row form-mar p-2">
    <div class="col-md-12">
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

            <div class="col-md-6">
                <div class="form-group">
                    <label>Inco Term:</label>
                    <input type="text" value="{{ $exportSodaField->incoterm->name ?? 'N/A' }}" class="form-control" readonly>
                </div>
            </div>

            <div class="col-md-6">
                <div class="form-group">
                    <label>Payment Term:</label>
                    <input type="text" value="{{ $exportSodaField->modeOfTerm->name ?? 'N/A' }}" class="form-control" readonly>
                </div>
            </div>

            <div class="col-md-6">
                <div class="form-group">
                    <label>Shipment Period:</label>
                    <input type="text" value="{{ $exportSodaField->shipment_period }}" class="form-control" readonly>
                </div>
            </div>

            <div class="col-md-6">
                <div class="form-group">
                    <label>Commission:</label>
                    <input type="text" value="{{ $exportSodaField->commission }}" class="form-control" readonly>
                </div>
            </div>
        </div>

        {{-- ====== PACKING DETAILS ====== --}}
        <div class="col-12 mt-4 px-0">
            <h6 class="header-heading-sepration">Packing Details</h6>
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr class="bg-light">
                            <th>Brand</th>
                            <th>Bag Type</th>
                            <th>Packing</th>
                            <th>Condition</th>
                            <th>Color</th>
                            <th>Size (kg)</th>
                            <th>Qty (MT)</th>
                            <th>Qty (Mnds)</th>
                            <th>Qty (KGs)</th>
                            <th>Bags</th>
                            <th>Rate/Ton</th>
                            <th>Rate/Mnd</th>
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
                            <td>{{ number_format($item->bag_size, 2) }}</td>
                            <td>{{ number_format($item->metric_tons, 3) }}</td>
                            <td>{{ number_format($item->maunds, 2) }}</td>
                            <td>{{ number_format($item->total_kgs, 2) }}</td>
                            <td>{{ number_format($item->no_of_bags) }}</td>
                            <td>{{ number_format($item->rate, 2) }}</td>
                            <td>{{ number_format($item->rate_per_maund, 2) }}</td>
                            <td>{{ number_format($item->amount, 2) }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="13" class="text-center">No packing details available yet.</td>
                        </tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        @if($items->count() > 0)
                        <tr class="font-weight-bold bg-light">
                            <td colspan="6" class="text-right">Totals:</td>
                            <td>{{ number_format($items->sum('metric_tons'), 3) }}</td>
                            <td>{{ number_format($items->sum('maunds'), 2) }}</td>
                            <td>{{ number_format($items->sum('total_kgs'), 2) }}</td>
                            <td>{{ number_format($items->sum('no_of_bags')) }}</td>
                            <td colspan="2"></td>
                            <td>{{ number_format($items->sum('amount'), 2) }}</td>
                        </tr>
                        @endif
                    </tfoot>
                </table>
            </div>
        </div>

        <div class="row mt-2">
            <div class="col-md-12">
                <div class="form-group">
                    <label>Additional Info:</label>
                    <textarea class="form-control" rows="4" readonly>{{ $exportSodaField->additional_info }}</textarea>
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
