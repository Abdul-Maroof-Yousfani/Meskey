<!-- Print Header (Visible during Print) -->
<div class="print-header mb-4 text-center">
    <h4 class="font-weight-bold mb-1">STOCK MOVEMENT & INVENTORY REPORT</h4>
    <p class="text-muted mb-0 small">
        Period: <strong>{{ $dateLabel ?? 'All Time' }}</strong> | Generated On: {{ now()->format('d M Y, h:i A') }}
    </p>
    <hr>
</div>

<!-- ========================================== -->
<!-- 1. COMPACT KPI SUMMARY CARDS               -->
<!-- ========================================== -->
<div class="row mb-3">
    <!-- Inward Stock Card -->
    <div class="col-xl-3 col-md-6 mb-2">
        <div class="card border-0 shadow-sm h-100" style="border-radius: 8px; background: #f0fdf4; border-left: 3px solid #16a34a !important;">
            <div class="card-body p-2 px-3">
                <div class="text-uppercase text-muted font-weight-bold" style="font-size: 10.5px; letter-spacing: 0.5px;">Total Stock-In (Inward)</div>
                <div class="d-flex align-items-baseline justify-content-between mt-1">
                    <span class="font-weight-bold text-success" style="font-size: 16.5px;">
                        {{ number_format($totalInQty, 2) }} <small class="text-muted" style="font-size: 11px;">KG</small>
                    </span>
                    <span class="badge badge-success px-2 py-1" style="font-size: 11px;">{{ number_format($totalInQty / 1000, 2) }} MT</span>
                </div>
                <div class="text-muted mt-1 small" style="font-size: 11px;">
                    Amount: Rs. {{ number_format($totalInValue, 2) }}
                </div>
            </div>
        </div>
    </div>

    <!-- Outward Stock Card -->
    <div class="col-xl-3 col-md-6 mb-2">
        <div class="card border-0 shadow-sm h-100" style="border-radius: 8px; background: #fef2f2; border-left: 3px solid #dc2626 !important;">
            <div class="card-body p-2 px-3">
                <div class="text-uppercase text-muted font-weight-bold" style="font-size: 10.5px; letter-spacing: 0.5px;">Total Stock-Out (Outward)</div>
                <div class="d-flex align-items-baseline justify-content-between mt-1">
                    <span class="font-weight-bold text-danger" style="font-size: 16.5px;">
                        {{ number_format($totalOutQty, 2) }} <small class="text-muted" style="font-size: 11px;">KG</small>
                    </span>
                    <span class="badge badge-danger px-2 py-1" style="font-size: 11px;">{{ number_format($totalOutQty / 1000, 2) }} MT</span>
                </div>
                <div class="text-muted mt-1 small" style="font-size: 11px;">
                    Amount: Rs. {{ number_format($totalOutValue, 2) }}
                </div>
            </div>
        </div>
    </div>

    <!-- Net Closing Stock Balance Card -->
    <div class="col-xl-3 col-md-6 mb-2">
        <div class="card border-0 shadow-sm h-100" style="border-radius: 8px; background: #eff6ff; border-left: 3px solid #2563eb !important;">
            <div class="card-body p-2 px-3">
                <div class="text-uppercase text-muted font-weight-bold" style="font-size: 10.5px; letter-spacing: 0.5px;">Net Closing Balance</div>
                <div class="d-flex align-items-baseline justify-content-between mt-1">
                    <span class="font-weight-bold {{ $netBalanceQty >= 0 ? 'text-primary' : 'text-danger' }}" style="font-size: 16.5px;">
                        {{ number_format($netBalanceQty, 2) }} <small class="text-muted" style="font-size: 11px;">KG</small>
                    </span>
                    <span class="badge badge-primary px-2 py-1" style="font-size: 11px;">{{ number_format($netBalanceQty / 1000, 2) }} MT</span>
                </div>
                <div class="text-muted mt-1 small" style="font-size: 11px;">
                    Total Mounds: {{ number_format($netBalanceQty / 40, 1) }} Mnds
                </div>
            </div>
        </div>
    </div>

    <!-- Valuation / Avg Rate Card -->
    <div class="col-xl-3 col-md-6 mb-2">
        <div class="card border-0 shadow-sm h-100" style="border-radius: 8px; background: #f8fafc; border-left: 3px solid #64748b !important;">
            <div class="card-body p-2 px-3">
                <div class="text-uppercase text-muted font-weight-bold" style="font-size: 10.5px; letter-spacing: 0.5px;">Average Inward Rate</div>
                <div class="d-flex align-items-baseline justify-content-between mt-1">
                    <span class="font-weight-bold text-dark" style="font-size: 16.5px;">
                        Rs. {{ number_format($avgInRate, 2) }} <small class="text-muted" style="font-size: 11px;">/ KG</small>
                    </span>
                    <span class="badge badge-secondary px-2 py-1" style="font-size: 11px;">{{ number_format($totalTransactions) }} Records</span>
                </div>
                <div class="text-muted mt-1 small" style="font-size: 11px;">
                    Rate / Mound: Rs. {{ number_format($avgInRate * 40, 2) }}
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ========================================== -->
<!-- 2. TABBED REPORT VIEWS                     -->
<!-- ========================================== -->
<div class="card border-0 shadow-sm" style="border-radius: 8px;">
    <div class="card-header bg-white border-bottom p-0">
        <ul class="nav nav-tabs card-header-tabs m-0 border-0" id="stockReportTabs" role="tablist">
            <li class="nav-item">
                <a class="nav-link active font-weight-bold py-2 px-3 border-0" id="ledger-tab" data-toggle="tab" href="#ledgerPane" role="tab">
                    Stock Ledger
                    <span class="badge badge-light ml-1 font-weight-bold">{{ number_format(count($stocks)) }}</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link font-weight-bold py-2 px-3 border-0" id="product-tab" data-toggle="tab" href="#productPane" role="tab">
                    Commodity-Wise Summary
                    <span class="badge badge-light ml-1 font-weight-bold">{{ count($productSummary) }}</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link font-weight-bold py-2 px-3 border-0" id="factory-tab" data-toggle="tab" href="#factoryPane" role="tab">
                    Factory / Unit Breakdown
                    <span class="badge badge-light ml-1 font-weight-bold">{{ count($factorySummary) }}</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link font-weight-bold py-2 px-3 border-0" id="location-tab" data-toggle="tab" href="#locationPane" role="tab">
                    Warehouse / Location Breakdown
                    <span class="badge badge-light ml-1 font-weight-bold">{{ count($locationSummary) }}</span>
                </a>
            </li>
        </ul>
    </div>

    <div class="card-body p-0">
        <div class="tab-content" id="stockReportTabContent">

            <!-- --------------------------------------- -->
            <!-- TAB 1: DETAILED MOVEMENT LEDGER         -->
            <!-- --------------------------------------- -->
            <div class="tab-pane fade show active" id="ledgerPane" role="tabpanel">
                <div class="table-responsive">
                    <table class="table table-hover table-striped table-bordered m-0" id="stockReportTable" style="font-size: 12px;">
                        <thead class="bg-light text-dark font-weight-bold">
                            <tr>
                                <th style="width: 35px;" class="text-center">#</th>
                                <th style="width: 115px;">Date & Time</th>
                                <th style="width: 85px;">Warehouse</th>
                                <th style="width: 85px;">Factory</th>
                                <th style="width: 80px;">Brand</th>
                                <th style="width: 95px;">Voucher Type</th>
                                <th style="width: 115px;">Voucher No</th>
                                <th>Product / Commodity</th>
                                <th style="width: 85px;">Packaging</th>
                                <th style="width: 65px;" class="text-center">Type</th>
                                <th style="width: 100px;" class="text-right text-success">Inward (KG)</th>
                                <th style="width: 100px;" class="text-right text-danger">Outward (KG)</th>
                                <th style="width: 80px;" class="text-right">Rate / KG</th>
                                <th style="width: 105px;" class="text-right">Total Price</th>
                                <th style="width: 80px;" class="text-right">Cost Price</th>
                                <th>Narration</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($stocks as $index => $row)
                                @php
                                    $isStockIn = ($row->type === 'stock-in');
                                    $vTypeClass = match($row->voucher_type) {
                                        'grn'              => 'badge-success',
                                        'delivery_challan' => 'badge-primary',
                                        'qc'               => 'badge-warning',
                                        'purchase_return'  => 'badge-danger',
                                        'sale_return'      => 'badge-info',
                                        'gdn'              => 'badge-secondary',
                                        default            => 'badge-dark'
                                    };
                                    $vTypeLabel = match($row->voucher_type) {
                                        'grn'              => 'GRN',
                                        'delivery_challan' => 'Del. Challan',
                                        'qc'               => 'QC Deduction',
                                        'purchase_return'  => 'Purchase Return',
                                        'sale_return'      => 'Sale Return',
                                        'gdn'              => 'GDN',
                                        default            => ucwords(str_replace('_', ' ', $row->voucher_type ?? 'N/A'))
                                    };
                                @endphp
                                <tr>
                                    <td class="text-center font-weight-bold text-muted">{{ $index + 1 }}</td>
                                    <td>{{ $row->created_at ? $row->created_at->format('d M Y, h:i A') : 'N/A' }}</td>
                                    <td>
                                        <span class="badge badge-light border">
                                            {{ $row->companyLocation->code ?? ($row->companyLocation->name ?? 'Central') }}
                                        </span>
                                    </td>
                                    <td>
                                        @if(!empty($row->factory->name))
                                            <span class="badge badge-info px-2 py-1 font-weight-bold">
                                                {{ $row->factory->name }}
                                            </span>
                                        @else
                                            <span class="text-muted small">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if(!empty($row->brand->name))
                                            <span class="badge badge-secondary px-2 py-1">
                                                {{ $row->brand->name }}
                                            </span>
                                        @else
                                            <span class="text-muted small">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge {{ $vTypeClass }} px-2 py-1">
                                            {{ $vTypeLabel }}
                                        </span>
                                    </td>
                                    <td class="font-weight-bold text-primary">{{ $row->voucher_no ?? '-' }}</td>
                                    <td>
                                        <strong>{{ $row->product->name ?? 'N/A' }}</strong>
                                        @if(!empty($row->product->unique_no))
                                            <span class="text-muted small">({{ $row->product->unique_no }})</span>
                                        @endif
                                    </td>
                                    <td>
                                        <small class="text-muted">{{ $row->bagPacking->name ?? '-' }}</small>
                                    </td>
                                    <td class="text-center">
                                        @if ($isStockIn)
                                            <span class="badge badge-success px-2 py-1">IN</span>
                                        @else
                                            <span class="badge badge-danger px-2 py-1">OUT</span>
                                        @endif
                                    </td>
                                    <td class="text-right font-weight-bold text-success">
                                        {{ $isStockIn ? number_format($row->qty, 2) : '-' }}
                                    </td>
                                    <td class="text-right font-weight-bold text-danger">
                                        {{ !$isStockIn ? number_format($row->qty, 2) : '-' }}
                                    </td>
                                    <td class="text-right">
                                        {{ $row->avg_price_per_kg ? number_format($row->avg_price_per_kg, 2) : '-' }}
                                    </td>
                                    <td class="text-right font-weight-bold">
                                        {{ $row->price ? number_format($row->price, 2) : '-' }}
                                    </td>
                                    <td class="text-right text-muted small">
                                        {{ $row->avg_cost_price ? number_format($row->avg_cost_price, 2) : '-' }}
                                    </td>
                                    <td class="text-muted small">
                                        {{ $row->narration ?? '-' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="16" class="text-center py-5 text-muted">
                                        <strong>No stock transactions found matching the selected filters.</strong>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if (count($stocks) > 0)
                            <tfoot class="bg-light font-weight-bold">
                                <tr style="border-top: 2px solid #cbd5e1;">
                                    <td colspan="10" class="text-right">GRAND TOTALS:</td>
                                    <td class="text-right text-success font-weight-bold">
                                        {{ number_format($totalInQty, 2) }}
                                    </td>
                                    <td class="text-right text-danger font-weight-bold">
                                        {{ number_format($totalOutQty, 2) }}
                                    </td>
                                    <td class="text-right">
                                        Rs. {{ number_format($avgInRate, 2) }}
                                    </td>
                                    <td class="text-right font-weight-bold text-dark">
                                        Rs. {{ number_format($totalInValue, 2) }}
                                    </td>
                                    <td colspan="2"></td>
                                </tr>
                                <tr class="bg-white">
                                    <td colspan="10" class="text-right">NET STOCK BALANCE:</td>
                                    <td colspan="2" class="text-center font-weight-bold {{ $netBalanceQty >= 0 ? 'text-primary' : 'text-danger' }}">
                                        {{ number_format($netBalanceQty, 2) }} KG ({{ number_format($netBalanceQty / 1000, 2) }} MT)
                                    </td>
                                    <td colspan="4"></td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
            </div>

            <!-- --------------------------------------- -->
            <!-- TAB 2: COMMODITY-WISE SUMMARY           -->
            <!-- --------------------------------------- -->
            <div class="tab-pane fade" id="productPane" role="tabpanel">
                <div class="p-2 px-3 bg-light border-bottom d-flex justify-content-between align-items-center">
                    <span class="font-weight-bold text-dark small">
                        Commodity-Wise Stock Balances ({{ count($productSummary) }} Commodities)
                    </span>
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="exportToExcel('productSummaryTable', 'Commodity_Stock_Summary')">
                        Export Summary
                    </button>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover table-striped table-bordered m-0" id="productSummaryTable" style="font-size: 12.5px;">
                        <thead class="bg-light text-dark font-weight-bold">
                            <tr>
                                <th style="width: 45px;" class="text-center">#</th>
                                <th style="width: 110px;">Product Code</th>
                                <th>Product / Commodity Name</th>
                                <th style="width: 130px;" class="text-right text-success">Total Inward (KG)</th>
                                <th style="width: 130px;" class="text-right text-danger">Total Outward (KG)</th>
                                <th style="width: 140px;" class="text-right text-primary">Closing Balance (KG)</th>
                                <th style="width: 120px;" class="text-right">Closing (M.Tons)</th>
                                <th style="width: 120px;" class="text-right">Closing (Mounds)</th>
                                <th style="width: 100px;" class="text-right">Avg Rate (Rs)</th>
                                <th style="width: 140px;" class="text-right">Est. Valuation (Rs)</th>
                                <th style="width: 80px;" class="text-center">Vouchers</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $sumIn = 0;
                                $sumOut = 0;
                                $sumBal = 0;
                                $sumValuation = 0;
                                $sumTx = 0;
                            @endphp
                            @forelse ($productSummary as $pIdx => $pItem)
                                @php
                                    $pIn = (float) $pItem['total_in_qty'];
                                    $pOut = (float) $pItem['total_out_qty'];
                                    $pBal = (float) $pItem['closing_balance'];
                                    $pAvgRate = $pIn > 0 ? ($pItem['total_price'] / $pIn) : 0;
                                    $pEstVal = $pBal * $pAvgRate;

                                    $sumIn += $pIn;
                                    $sumOut += $pOut;
                                    $sumBal += $pBal;
                                    $sumValuation += $pEstVal;
                                    $sumTx += $pItem['tx_count'];
                                @endphp
                                <tr>
                                    <td class="text-center font-weight-bold text-muted">{{ $loop->iteration }}</td>
                                    <td class="font-weight-bold">{{ $pItem['product_code'] }}</td>
                                    <td><strong>{{ $pItem['product_name'] }}</strong></td>
                                    <td class="text-right font-weight-bold text-success">{{ number_format($pIn, 2) }}</td>
                                    <td class="text-right font-weight-bold text-danger">{{ number_format($pOut, 2) }}</td>
                                    <td class="text-right font-weight-bold {{ $pBal >= 0 ? 'text-primary' : 'text-danger' }}">
                                        {{ number_format($pBal, 2) }}
                                    </td>
                                    <td class="text-right font-weight-bold text-dark">{{ number_format($pBal / 1000, 2) }}</td>
                                    <td class="text-right text-muted">{{ number_format($pBal / 40, 1) }}</td>
                                    <td class="text-right">Rs. {{ number_format($pAvgRate, 2) }}</td>
                                    <td class="text-right font-weight-bold">Rs. {{ number_format($pEstVal, 2) }}</td>
                                    <td class="text-center"><span class="badge badge-light border">{{ $pItem['tx_count'] }}</span></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="11" class="text-center py-4 text-muted">No product summary available.</td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if (count($productSummary) > 0)
                            <tfoot class="bg-light font-weight-bold" style="border-top: 2px solid #cbd5e1;">
                                <tr>
                                    <td colspan="3" class="text-right">TOTAL:</td>
                                    <td class="text-right text-success">{{ number_format($sumIn, 2) }}</td>
                                    <td class="text-right text-danger">{{ number_format($sumOut, 2) }}</td>
                                    <td class="text-right text-primary font-weight-bold">{{ number_format($sumBal, 2) }}</td>
                                    <td class="text-right text-dark">{{ number_format($sumBal / 1000, 2) }}</td>
                                    <td class="text-right text-muted">{{ number_format($sumBal / 40, 1) }}</td>
                                    <td class="text-right">-</td>
                                    <td class="text-right font-weight-bold">Rs. {{ number_format($sumValuation, 2) }}</td>
                                    <td class="text-center">{{ number_format($sumTx) }}</td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
            </div>

            <!-- --------------------------------------- -->
            <!-- TAB 3: FACTORY / UNIT BREAKDOWN         -->
            <!-- --------------------------------------- -->
            <div class="tab-pane fade" id="factoryPane" role="tabpanel">
                <div class="p-2 px-3 bg-light border-bottom d-flex justify-content-between align-items-center">
                    <span class="font-weight-bold text-dark small">
                        Factory / Unit Closing Balances (e.g. A-43, A-44, A-45) ({{ count($factorySummary) }} Factories)
                    </span>
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="exportToExcel('factorySummaryTable', 'Factory_Stock_Summary')">
                        Export Summary
                    </button>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover table-striped table-bordered m-0" id="factorySummaryTable" style="font-size: 12.5px;">
                        <thead class="bg-light text-dark font-weight-bold">
                            <tr>
                                <th style="width: 45px;" class="text-center">#</th>
                                <th>Factory / Unit Name</th>
                                <th>Warehouse / Location</th>
                                <th style="width: 150px;" class="text-right text-success">Total Inward (KG)</th>
                                <th style="width: 150px;" class="text-right text-danger">Total Outward (KG)</th>
                                <th style="width: 160px;" class="text-right text-primary">Closing Balance (KG)</th>
                                <th style="width: 140px;" class="text-right">Closing (M.Tons)</th>
                                <th style="width: 100px;" class="text-center">Movements</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $fSumIn = 0;
                                $fSumOut = 0;
                                $fSumBal = 0;
                                $fSumTx = 0;
                            @endphp
                            @forelse ($factorySummary as $fIdx => $fItem)
                                @php
                                    $fIn = (float) $fItem['total_in_qty'];
                                    $fOut = (float) $fItem['total_out_qty'];
                                    $fBal = (float) $fItem['closing_balance'];
                                    $fSumIn += $fIn;
                                    $fSumOut += $fOut;
                                    $fSumBal += $fBal;
                                    $fSumTx += $fItem['tx_count'];
                                @endphp
                                <tr>
                                    <td class="text-center font-weight-bold text-muted">{{ $loop->iteration }}</td>
                                    <td>
                                        <strong class="text-dark">{{ $fItem['factory_name'] }}</strong>
                                    </td>
                                    <td>
                                        <span class="badge badge-light border">{{ $fItem['location_name'] }}</span>
                                    </td>
                                    <td class="text-right font-weight-bold text-success">{{ number_format($fIn, 2) }}</td>
                                    <td class="text-right font-weight-bold text-danger">{{ number_format($fOut, 2) }}</td>
                                    <td class="text-right font-weight-bold {{ $fBal >= 0 ? 'text-primary' : 'text-danger' }}">
                                        {{ number_format($fBal, 2) }}
                                    </td>
                                    <td class="text-right font-weight-bold text-dark">{{ number_format($fBal / 1000, 2) }}</td>
                                    <td class="text-center"><span class="badge badge-light border">{{ $fItem['tx_count'] }}</span></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center py-4 text-muted">No factory summary available.</td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if (count($factorySummary) > 0)
                            <tfoot class="bg-light font-weight-bold" style="border-top: 2px solid #cbd5e1;">
                                <tr>
                                    <td colspan="3" class="text-right">TOTAL:</td>
                                    <td class="text-right text-success">{{ number_format($fSumIn, 2) }}</td>
                                    <td class="text-right text-danger">{{ number_format($fSumOut, 2) }}</td>
                                    <td class="text-right text-primary font-weight-bold">{{ number_format($fSumBal, 2) }}</td>
                                    <td class="text-right text-dark">{{ number_format($fSumBal / 1000, 2) }}</td>
                                    <td class="text-center">{{ number_format($fSumTx) }}</td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
            </div>

            <!-- --------------------------------------- -->
            <!-- TAB 4: WAREHOUSE / LOCATION BREAKDOWN   -->
            <!-- --------------------------------------- -->
            <div class="tab-pane fade" id="locationPane" role="tabpanel">
                <div class="p-2 px-3 bg-light border-bottom d-flex justify-content-between align-items-center">
                    <span class="font-weight-bold text-dark small">
                        Warehouse / Location Distribution ({{ count($locationSummary) }} Locations)
                    </span>
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="exportToExcel('locationSummaryTable', 'Location_Stock_Summary')">
                        Export Summary
                    </button>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover table-striped table-bordered m-0" id="locationSummaryTable" style="font-size: 12.5px;">
                        <thead class="bg-light text-dark font-weight-bold">
                            <tr>
                                <th style="width: 45px;" class="text-center">#</th>
                                <th>Warehouse / Location Name</th>
                                <th style="width: 100px;" class="text-center">Code</th>
                                <th style="width: 150px;" class="text-right text-success">Total Inward (KG)</th>
                                <th style="width: 150px;" class="text-right text-danger">Total Outward (KG)</th>
                                <th style="width: 160px;" class="text-right text-primary">Closing Balance (KG)</th>
                                <th style="width: 140px;" class="text-right">Closing (M.Tons)</th>
                                <th style="width: 100px;" class="text-center">Movements</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $locSumIn = 0;
                                $locSumOut = 0;
                                $locSumBal = 0;
                                $locSumTx = 0;
                            @endphp
                            @forelse ($locationSummary as $locIdx => $locItem)
                                @php
                                    $lIn = (float) $locItem['total_in_qty'];
                                    $lOut = (float) $locItem['total_out_qty'];
                                    $lBal = (float) $locItem['closing_balance'];
                                    $locSumIn += $lIn;
                                    $locSumOut += $lOut;
                                    $locSumBal += $lBal;
                                    $locSumTx += $locItem['tx_count'];
                                @endphp
                                <tr>
                                    <td class="text-center font-weight-bold text-muted">{{ $loop->iteration }}</td>
                                    <td><strong>{{ $locItem['location_name'] }}</strong></td>
                                    <td class="text-center"><span class="badge badge-light border">{{ $locItem['location_code'] ?: '-' }}</span></td>
                                    <td class="text-right font-weight-bold text-success">{{ number_format($lIn, 2) }}</td>
                                    <td class="text-right font-weight-bold text-danger">{{ number_format($lOut, 2) }}</td>
                                    <td class="text-right font-weight-bold {{ $lBal >= 0 ? 'text-primary' : 'text-danger' }}">
                                        {{ number_format($lBal, 2) }}
                                    </td>
                                    <td class="text-right font-weight-bold text-dark">{{ number_format($lBal / 1000, 2) }}</td>
                                    <td class="text-center"><span class="badge badge-light border">{{ $locItem['tx_count'] }}</span></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center py-4 text-muted">No location summary available.</td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if (count($locationSummary) > 0)
                            <tfoot class="bg-light font-weight-bold" style="border-top: 2px solid #cbd5e1;">
                                <tr>
                                    <td colspan="3" class="text-right">TOTAL:</td>
                                    <td class="text-right text-success">{{ number_format($locSumIn, 2) }}</td>
                                    <td class="text-right text-danger">{{ number_format($locSumOut, 2) }}</td>
                                    <td class="text-right text-primary font-weight-bold">{{ number_format($locSumBal, 2) }}</td>
                                    <td class="text-right text-dark">{{ number_format($locSumBal / 1000, 2) }}</td>
                                    <td class="text-center">{{ number_format($locSumTx) }}</td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>
