{{-- Production Costing & Live Financial Valuation Card --}}
<div class="col-12 mt-3" id="productionCostingSection">
    <div class="card border shadow-sm mb-3" style="border-radius: 10px; overflow: hidden;">
        <div class="card-header bg-gradient-directional-primary text-white d-flex justify-content-between align-items-center py-2" style="background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);">
            <h5 class="mb-0 text-white font-weight-bold">
                <i class="ft-trending-up mr-2"></i> Production Costing & Auto-JV Valuation
            </h5>
            <span class="badge badge-light text-dark font-weight-bold px-2 py-1" id="costingStatusBadge">
                <i class="fa fa-calculator mr-1"></i> Live Costing Calculated
            </span>
        </div>

        <div class="card-body p-3 bg-light">
            {{-- 4 Metric Summary Cards --}}
            <div class="row">
                {{-- 1. Raw Material Input Cost --}}
                <div class="col-md-3 col-sm-6 mb-2">
                    <div class="card bg-white border-left-primary shadow-xs h-100 p-2 mb-0" style="border-left: 4px solid #007bff; border-radius: 6px;">
                        <small class="text-uppercase font-weight-bold text-muted d-block">1. Raw Material Cost</small>
                        <h4 class="font-weight-bold text-primary mb-1" id="displayRawMaterialCost">
                            PKR {{ isset($productionVoucher) ? number_format($productionVoucher->raw_material_cost, 2) : '0.00' }}
                        </h4>
                        <small class="text-muted d-block" id="displayInputQtySub">
                            Total Input: {{ isset($productionVoucher) ? number_format($productionVoucher->net_total_input, 2) : '0.00' }} KG
                        </small>
                    </div>
                </div>

                {{-- 2. Milling Overhead (Variables) --}}
                <div class="col-md-3 col-sm-6 mb-2">
                    <div class="card bg-white border-left-warning shadow-xs h-100 p-2 mb-0" style="border-left: 4px solid #ffc107; border-radius: 6px;">
                        <div class="d-flex justify-content-between align-items-center">
                            <small class="text-uppercase font-weight-bold text-muted">2. Milling Overheads</small>
                            <span class="badge badge-warning text-dark font-weight-bold" id="displayMillingRateBadge">
                                +PKR {{ isset($productionVoucher) ? number_format($productionVoucher->overhead_cost_per_kg, 2) : '0.00' }}/KG
                            </span>
                        </div>
                        <h4 class="font-weight-bold text-dark mb-1" id="displayMillingCost">
                            +PKR {{ isset($productionVoucher) ? number_format($productionVoucher->milling_cost, 2) : '0.00' }}
                        </h4>
                        <small class="text-muted d-block" id="displayVariableCountSub">
                            Accrued Liabilities (COA 2-10)
                        </small>
                    </div>
                </div>

                {{-- 3. By-Products Recovery Value --}}
                <div class="col-md-3 col-sm-6 mb-2">
                    <div class="card bg-white border-left-danger shadow-xs h-100 p-2 mb-0" style="border-left: 4px solid #dc3545; border-radius: 6px;">
                        <small class="text-uppercase font-weight-bold text-muted d-block">3. By-Products Recovery</small>
                        <h4 class="font-weight-bold text-danger mb-1" id="displayByProductCost">
                            -PKR {{ isset($productionVoucher) ? number_format($productionVoucher->by_product_cost, 2) : '0.00' }}
                        </h4>
                        <small class="text-muted d-block" id="displayByProductSub">
                            Deducted from Total Cost Pool
                        </small>
                    </div>
                </div>

                {{-- 4. Net Finished Goods Cost & Unit Rate --}}
                <div class="col-md-3 col-sm-6 mb-2">
                    <div class="card bg-white border-left-success shadow-xs h-100 p-2 mb-0" style="border-left: 4px solid #28a745; border-radius: 6px; background: #eafaf1;">
                        <small class="text-uppercase font-weight-bold text-success d-block">4. Finished Goods Cost</small>
                        <h4 class="font-weight-bold text-success mb-1" id="displayNetFgCost">
                            PKR {{ isset($productionVoucher) ? number_format($productionVoucher->net_finished_good_cost, 2) : '0.00' }}
                        </h4>
                        <div class="d-flex justify-content-between align-items-center mt-1">
                            <span class="badge badge-success px-2 py-1 font-weight-bold text-white" style="font-size: 13px;" id="displayFgRateBadge">
                                Unit Rate: PKR {{ isset($productionVoucher) ? number_format($productionVoucher->finished_good_rate, 2) : '0.00' }} / KG
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            @php
                $hasVoucherVariablesTable = \Illuminate\Support\Facades\Schema::hasTable('production_voucher_variables');
                $voucherVars = ($hasVoucherVariablesTable && isset($productionVoucher) && $productionVoucher->voucherVariables) 
                    ? $productionVoucher->voucherVariables 
                    : collect();
            @endphp
            {{-- Variable Overheads Breakdown Drawer / Table --}}
            <div class="row mt-2" id="variablesBreakdownContainer" style="{{ $voucherVars->count() > 0 ? '' : 'display: none;' }}">
                <div class="col-12">
                    <div class="card border mb-0">
                        <div class="card-header bg-white py-1 px-3 d-flex justify-content-between align-items-center cursor-pointer" onclick="$('#variablesDetailCollapse').slideToggle(200);">
                            <span class="font-weight-bold text-dark" style="font-size: 12px;">
                                <i class="fa fa-sitemap text-warning mr-1"></i> Active Milling Rate Variables (Accrued Liabilities 2-10-x)
                            </span>
                            <small class="text-primary font-weight-bold">Toggle Breakdown <i class="fa fa-chevron-down ml-1"></i></small>
                        </div>
                        <div id="variablesDetailCollapse" class="p-2" style="{{ isset($productionVoucher) ? '' : 'display: none;' }}">
                            <table class="table table-sm table-bordered mb-0 text-center" id="variablesBreakdownTable" style="font-size: 11px;">
                                <thead class="thead-light">
                                    <tr>
                                        <th>Variable Name</th>
                                        <th>Rate / KG</th>
                                        <th>Total Milling Qty</th>
                                        <th>Accrued Amount (CR)</th>
                                        <th>Linked COA Account</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @if($voucherVars->count() > 0)
                                        @foreach($voucherVars as $vVar)
                                            <tr>
                                                <td class="font-weight-bold text-left">{{ $vVar->variable->title ?? 'Variable #' . $vVar->variable_id }}</td>
                                                <td>PKR {{ number_format($vVar->rate, 2) }}</td>
                                                <td>{{ number_format($productionVoucher->net_total_input, 2) }} KG</td>
                                                <td class="font-weight-bold text-danger">PKR {{ number_format($vVar->amount, 2) }}</td>
                                                <td>
                                                    <span class="badge badge-light border text-dark">
                                                        {{ $vVar->account->unique_no ?? 'ACC' }} ({{ $vVar->account->hierarchy_path ?? '2-10' }})
                                                    </span>
                                                </td>
                                            </tr>
                                        @endforeach
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Auto-JV Financial Balance Sheet Preview in Edit Mode --}}
            @if(isset($productionVoucher) && $productionVoucher->transactions && $productionVoucher->transactions->count() > 0)
                <div class="row mt-2">
                    <div class="col-12">
                        <div class="card border mb-0">
                            <div class="card-header bg-white py-1 px-3 d-flex justify-content-between align-items-center cursor-pointer" onclick="$('#jvTransactionsCollapse').slideToggle(200);">
                                <span class="font-weight-bold text-dark" style="font-size: 12px;">
                                    <i class="fa fa-balance-scale text-success mr-1"></i> Posted Financial Journal Voucher (PRO #{{ $productionVoucher->prod_no }})
                                </span>
                                <span class="badge badge-success text-white font-weight-bold">
                                    <i class="fa fa-check-circle mr-1"></i> Balanced (Diff: PKR 0.00)
                                </span>
                            </div>
                            <div id="jvTransactionsCollapse" class="p-2">
                                <table class="table table-sm table-bordered table-striped mb-0 text-center" style="font-size: 11px;">
                                    <thead class="thead-dark">
                                        <tr>
                                            <th>Type</th>
                                            <th>Account Code</th>
                                            <th>Account Title</th>
                                            <th>Debit (PKR)</th>
                                            <th>Credit (PKR)</th>
                                            <th>Narration</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php
                                            $totDr = 0;
                                            $totCr = 0;
                                        @endphp
                                        @foreach($productionVoucher->transactions as $txn)
                                            @php
                                                if ($txn->type === 'debit') $totDr += (float)$txn->amount;
                                                if ($txn->type === 'credit') $totCr += (float)$txn->amount;
                                            @endphp
                                            <tr>
                                                <td>
                                                    <span class="badge badge-{{ $txn->type == 'debit' ? 'success' : 'danger' }} text-uppercase">
                                                        {{ $txn->type }}
                                                    </span>
                                                </td>
                                                <td class="font-weight-bold">{{ $txn->account_unique_no }}</td>
                                                <td class="text-left">{{ $txn->account->name ?? 'Inventory / Expense Account' }}</td>
                                                <td class="font-weight-bold text-right text-success">
                                                    {{ $txn->type == 'debit' ? number_format($txn->amount, 2) : '-' }}
                                                </td>
                                                <td class="font-weight-bold text-right text-danger">
                                                    {{ $txn->type == 'credit' ? number_format($txn->amount, 2) : '-' }}
                                                </td>
                                                <td class="text-left text-muted">{{ $txn->remarks ?? $txn->jv_narration }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot class="bg-light font-weight-bold">
                                        <tr>
                                            <td colspan="3" class="text-right text-uppercase">Total:</td>
                                            <td class="text-right text-success">PKR {{ number_format($totDr, 2) }}</td>
                                            <td class="text-right text-danger">PKR {{ number_format($totCr, 2) }}</td>
                                            <td class="text-center font-weight-bold {{ abs($totDr - $totCr) < 0.05 ? 'text-success' : 'text-danger' }}">
                                                {{ abs($totDr - $totCr) < 0.05 ? 'Balanced (Δ = 0.00)' : 'Diff: ' . number_format(abs($totDr - $totCr), 2) }}
                                            </td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

        </div>
    </div>
</div>
