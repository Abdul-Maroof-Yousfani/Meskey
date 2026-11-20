<div class="row">
    <!-- Header Information -->
    <div class="col-md-12">
        <h6 class="header-heading-sepration">Input Analysis Information</h6>
        <div class="row">
            <div class="col-md-3">
                <div class="form-group">
                    <label>Date:</label>
                    <p class="form-control-static">{{ \Carbon\Carbon::parse($item->analysis_date)->format('d-m-Y') }}</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>Company Location:</label>
                    <p class="form-control-static">{{ $item->location->name ?? 'N/A' }}</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>Factory Location:</label>
                    <p class="form-control-static">{{ $item->arrivalLocation->name ?? 'N/A' }}</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>Plant:</label>
                    <p class="form-control-static">{{ $item->plant->name ?? 'N/A' }}</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>Job Order:</label>
                    <p class="form-control-static">{{ $item->jobOrders->pluck('job_order_no')->implode(', ') ?: ($item->analysisRequest?->voucher_display ?? 'N/A') }}</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>Product (Commodity):</label>
                    <p class="form-control-static">{{ $item->product->name ?? 'N/A' }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Line Items Section -->
    <div class="col-md-12 mt-4">
        <h6 class="header-heading-sepration">Line Items</h6>
        <div class="table-responsive" style="overflow-x: auto;">
            <table class="table table-bordered table-striped" style="min-width: 1200px;">
                <thead>
                    <tr>
                        <th style="min-width: 150px;">Time</th>
                        <th style="min-width: 150px;">Unit</th>
                        @foreach($productSlabTypes as $productSlabType)
                            <th style="min-width: 150px;">{{ $productSlabType->name }} {{ $productSlabType->qc_symbol }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($item->items as $analysisItem)
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($analysisItem->analysis_time)->format('h:i A') }}</td>
                            <td>{{ $analysisItem->unit->name ?? '-' }}</td>
                            @foreach($productSlabTypes as $productSlabType)
                                <td>
                                    {{ $analysisItem->slabs->where('slab_type_id', $productSlabType->id)->first()->production_analysis_value ?? '-' }}
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Other Products Section -->
    @if($item->otherProducts->isNotEmpty())
        <div class="col-md-12 mt-4">
            <h6 class="header-heading-sepration">Other Products</h6>
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th style="width: 60px;">#</th>
                        <th>Product</th>
                        <th style="width: 200px;">Percent</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($item->otherProducts as $other)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $other->product->name ?? 'N/A' }}</td>
                            <td>{{ rtrim(rtrim(number_format($other->percentage, 2), '0'), '.') }}%</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="2" class="text-right">Total</th>
                        <th>{{ rtrim(rtrim(number_format($item->otherProducts->sum('percentage'), 2), '0'), '.') }}%</th>
                    </tr>
                </tfoot>
            </table>
        </div>
    @endif

    <!-- Remarks Section -->
    <div class="col-md-12 mt-4">
        <div class="form-group">
            <label>Remarks:</label>
            <p class="form-control-static text-justify">{{ $item->remarks ?? 'No remarks provided.' }}</p>
        </div>
    </div>
</div>

<div class="row bottom-button-bar mt-4">
    <div class="col-12 text-right">
        <button type="button" class="btn btn-danger modal-sidebar-close closebutton">Close</button>
    </div>
</div>
