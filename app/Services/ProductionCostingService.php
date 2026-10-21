<?php

namespace App\Services;

use App\Models\Master\Account\Account;
use App\Models\Master\Account\Stock;
use App\Models\Master\Account\Transaction;
use App\Models\Master\Account\TransactionVoucherType;
use App\Models\Master\ByProductRate;
use App\Models\Master\MillingRate;
use App\Models\Master\Variable;
use App\Models\Product;
use App\Models\Production\ProductionInput;
use App\Models\Production\ProductionOutput;
use App\Models\Production\ProductionVoucher;
use App\Models\Production\ProductionVoucherVariable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProductionCostingService
{
    /**
     * Complete Synchronous Production Voucher Costing & Financial Sync:
     * 1. Calculates Raw Material Costs, Milling Overheads, By-Product Recoveries & Finished Goods Net Rate.
     * 2. Updates voucher, inputs, and outputs with exact figures.
     * 3. Posts balanced Financial Journal Voucher (Auto-JV) in General Ledger (transactions table).
     * 4. Posts inventory Stock-OUT (raw materials) & Stock-IN (FG & By-Products) in stocks table.
     *
     * @param ProductionVoucher $voucher
     * @param array $options Optional overrides (e.g. explicit input rates from request)
     * @return array Calculated summary results
     */
    public function syncProductionVoucher(ProductionVoucher $voucher, array $options = []): array
    {
        $voucher->loadMissing(['inputs.product', 'outputs.product', 'location', 'plant']);

        // 1. Calculate & Save Costing Breakdown
        $costing = $this->calculateAndSaveCosting($voucher, $options);

        // 2. Post Financial Balanced Journal Voucher (General Ledger)
        $this->postGeneralLedger($voucher, $costing);

        // 3. Post Inventory Stock Ledger (stocks table)
        $this->postStockLedger($voucher, $costing);

        return $costing;
    }

    /**
     * Calculate Costing and update database records for the voucher
     */
    public function calculateAndSaveCosting(ProductionVoucher $voucher, array $options = []): array
    {
        $inputRatesOverride = $options['input_rates'] ?? [];
        $totalInputQty = 0;
        $totalInputCost = 0;

        // 1. Calculate Raw Material Inputs Cost
        $voucherInputs = $voucher->inputs()->get();
        foreach ($voucherInputs as $idx => $input) {
            $qty = (float)$input->qty;
            $totalInputQty += $qty;

            // Determine rate: user override > latest stock-in avg cost > product price > 0
            $rate = null;
            if (isset($inputRatesOverride[$input->id])) {
                $rate = (float)$inputRatesOverride[$input->id];
            } elseif (isset($inputRatesOverride[$idx])) {
                $rate = (float)$inputRatesOverride[$idx];
            }

            if ($rate === null || $rate <= 0) {
                // Fetch latest stock-in weighted average cost for this product
                $latestStockIn = Stock::where('product_id', $input->product_id)
                    ->where('type', 'stock-in')
                    ->whereNotNull('avg_cost_price')
                    ->where('avg_cost_price', '>', 0)
                    ->latest('id')
                    ->first();

                if ($latestStockIn) {
                    $rate = (float)$latestStockIn->avg_cost_price;
                } else {
                    $product = $input->product ?? Product::find($input->product_id);
                    $rate = (float)($product?->price ?? 0);
                }
            }

            $amount = round($qty * $rate, 2);
            $totalInputCost += $amount;

            $input->update([
                'rate' => $rate,
                'amount' => $amount,
            ]);
        }

        // 2. Milling Overhead Costs (Variables)
        // Find active MillingRate for (location_id, plant_id, product_id)
        $targetProductId = $voucher->product_id ?? $voucher->head_product_id;
        $headProd = $targetProductId ? Product::find($targetProductId) : null;
        $parentProductId = $headProd?->parent_id;

        $millingRate = MillingRate::with('variables')
            ->where('location_id', $voucher->location_id)
            ->where('plant_id', $voucher->plant_id)
            ->where('status', 1)
            ->where(function ($q) use ($targetProductId, $parentProductId) {
                if ($targetProductId) {
                    $q->where('product_id', $targetProductId);
                }
                if ($parentProductId) {
                    $q->orWhere('product_id', $parentProductId);
                }
                $q->orWhereNull('product_id');
            })
            ->orderByRaw('product_id IS NULL ASC') // specific product first
            ->first();

        // Clear existing voucher variables before re-populating
        ProductionVoucherVariable::where('production_voucher_id', $voucher->id)->delete();

        $totalMillingRate = 0;
        $totalMillingCost = 0;
        $variableBreakdown = [];

        if ($millingRate && $millingRate->variables) {
            foreach ($millingRate->variables as $var) {
                $vRate = (float)($var->pivot->value ?? 0);
                $totalMillingRate += $vRate;

                // Overhead amount = Total Input Qty * Variable Rate per KG
                $vAmount = round($totalInputQty * $vRate, 2);
                $totalMillingCost += $vAmount;

                // Variable's linked COA account under 2-10 (Accrued Operational Liability)
                $varAccount = $var->account_id 
                    ?? Account::where('hierarchy_path', '2-10')->first()?->id 
                    ?? 872;

                ProductionVoucherVariable::create([
                    'production_voucher_id' => $voucher->id,
                    'variable_id' => $var->id,
                    'rate' => $vRate,
                    'amount' => $vAmount,
                    'account_id' => $varAccount,
                ]);

                $variableBreakdown[] = [
                    'variable_id' => $var->id,
                    'title' => $var->title,
                    'rate' => $vRate,
                    'amount' => $vAmount,
                    'account_id' => $varAccount,
                ];
            }
        }

        // 3. By-Products Recovery Cost
        // Find active ByProductRate for (location_id, head_product_id)
        $byProductRate = ByProductRate::with('items')
            ->where('location_id', $voucher->location_id)
            ->where('status', 1)
            ->where(function ($q) use ($targetProductId, $parentProductId, $voucher) {
                if ($targetProductId) {
                    $q->where('product_id', $targetProductId);
                }
                if ($parentProductId) {
                    $q->orWhere('product_id', $parentProductId);
                }
                if ($voucher->by_product_id) {
                    $q->orWhere('product_id', $voucher->by_product_id);
                }
            })
            ->latest('id')
            ->first();

        $byProductRatesMap = [];
        if ($byProductRate && $byProductRate->items) {
            foreach ($byProductRate->items as $item) {
                $byProductRatesMap[$item->by_product_id] = (float)$item->rate;
            }
        }

        $voucherOutputs = $voucher->outputs()->get();
        $headProductOutputs = [];
        $byProductOutputs = [];
        $totalByProductCost = 0;
        $totalFgQty = 0;

        foreach ($voucherOutputs as $output) {
            $prod = $output->product ?? Product::find($output->product_id);
            $isHead = ($output->product_id == $voucher->product_id) 
                      || ($prod && $prod->product_category_flags === 'head' && empty($output->cost_type));

            // Also check if product is explicitly a by-product
            if ($prod && in_array($prod->product_category_flags, ['by', 'b2']) && $output->product_id != $voucher->product_id) {
                $isHead = false;
            }

            if (!$isHead) {
                // By-Product Output
                $bRate = $byProductRatesMap[$output->product_id] ?? (float)($prod?->price ?? 0);
                $bAmount = round((float)$output->qty * $bRate, 2);
                $totalByProductCost += $bAmount;

                $output->update([
                    'rate' => $bRate,
                    'amount' => $bAmount,
                    'cost_type' => 'by_product',
                ]);

                $byProductOutputs[] = [
                    'output_id' => $output->id,
                    'product_id' => $output->product_id,
                    'product_name' => $prod?->name ?? 'By-Product',
                    'qty' => (float)$output->qty,
                    'rate' => $bRate,
                    'amount' => $bAmount,
                    'account_id' => $prod?->account_id,
                ];
            } else {
                // Head Product Output (Finished Goods)
                $totalFgQty += (float)$output->qty;
                $headProductOutputs[] = $output;
            }
        }

        // 4. Calculate Net Finished Goods Cost & Unit Rate
        // Total Cost Pool = Input Raw Material Cost + Total Milling Cost
        $totalCostPool = round($totalInputCost + $totalMillingCost, 2);

        // Net Finished Goods Cost = Total Cost Pool - By-Product Recovery Value
        $netFinishedGoodCost = round(max(0, $totalCostPool - $totalByProductCost), 2);

        // Unit Rate per KG for Finished Goods
        $fgRate = ($totalFgQty > 0) ? round($netFinishedGoodCost / $totalFgQty, 4) : 0;

        // Distribute Net Cost among Head Product Output rows
        $allocatedFgCost = 0;
        $countFg = count($headProductOutputs);
        foreach ($headProductOutputs as $idx => $fgOutput) {
            if ($idx === $countFg - 1) {
                // Last row gets remaining balance to eliminate fractional rounding discrepancies
                $fgRowAmount = round($netFinishedGoodCost - $allocatedFgCost, 2);
            } else {
                $fgRowAmount = round((float)$fgOutput->qty * $fgRate, 2);
                $allocatedFgCost += $fgRowAmount;
            }

            $fgOutput->update([
                'rate' => $fgRate,
                'amount' => $fgRowAmount,
                'cost_type' => 'head_product',
            ]);
        }

        // 5. Update Production Voucher Record
        $voucher->update([
            'raw_material_cost' => $totalInputCost,
            'milling_cost' => $totalMillingCost,
            'by_product_cost' => $totalByProductCost,
            'net_finished_good_cost' => $netFinishedGoodCost,
            'finished_good_rate' => $fgRate,
            'overhead_cost_per_kg' => $totalMillingRate,
            'milling_rate_id' => $millingRate?->id,
            'by_product_rate_id' => $byProductRate?->id,
        ]);

        return [
            'voucher_id' => $voucher->id,
            'prod_no' => $voucher->prod_no,
            'total_input_qty' => $totalInputQty,
            'raw_material_cost' => $totalInputCost,
            'total_milling_rate' => $totalMillingRate,
            'milling_cost' => $totalMillingCost,
            'total_cost_pool' => $totalCostPool,
            'by_product_cost' => $totalByProductCost,
            'net_finished_good_cost' => $netFinishedGoodCost,
            'total_fg_qty' => $totalFgQty,
            'finished_good_rate' => $fgRate,
            'variable_breakdown' => $variableBreakdown,
            'by_product_outputs' => $byProductOutputs,
        ];
    }

    /**
     * Post Balanced Financial Journal Voucher (Auto-JV) in General Ledger (transactions table)
     *
     * Financial Logic (Balanced to the Penny):
     * DR: Finished Goods Inventory (Head Product Account) -> Net Finished Goods Cost
     * DR: By-Product Inventories (Each By-Product Account) -> Recovery Value
     * CR: Raw Material Inventory (Paddy Account)           -> Input Raw Material Cost
     * CR: Accrued Operational Liabilities (Variables)     -> Milling Overheads (2-10-x)
     *
     * Total DR = Net FG Cost + By-Product Recovery = (Input Cost + Milling Cost - By-Product Recovery) + By-Product Recovery
     *          = Input Cost + Milling Cost = Total CR!
     */
    public function postGeneralLedger(ProductionVoucher $voucher, array $costing): void
    {
        $prodNo = $voucher->prod_no;
        $companyId = $voucher->company_id ?? 1;
        $voucherDate = $voucher->prod_date ? \Carbon\Carbon::parse($voucher->prod_date)->format('Y-m-d') : now()->format('Y-m-d');

        // Look up Transaction Voucher Type for Production Voucher (Code: 'PRO' or fallback 15/11)
        $pvVoucherType = TransactionVoucherType::where('code', 'PRO')->orWhere('code', 'PV')->first();
        $voucherTypeId = $pvVoucherType ? $pvVoucherType->id : 11; // 11 is standard JV

        // Default Inventory Master Account (1-2) if product has no account_id
        $defaultInventoryAccount = Account::where('hierarchy_path', '1-2')->first();
        $defaultInventoryAccountId = $defaultInventoryAccount?->id ?? 29;

        // Default Accrued Liability Master Account (2-10) if variable has no account_id
        $defaultAccruedAccount = Account::where('hierarchy_path', '2-10')->first();
        $defaultAccruedAccountId = $defaultAccruedAccount?->id ?? 872;

        // 1. Remove previous production ledger transactions for this voucher to prevent duplicate posting
        Transaction::where('against_reference_no', $prodNo)
            ->where('purpose', 'like', 'production-voucher%')
            ->delete();

        // If total cost pool is 0, do not post empty JV
        if (($costing['total_cost_pool'] ?? 0) <= 0) {
            return;
        }

        // Identify primary counter accounts for clear ledger tracking
        $headProd = $voucher->headProduct ?? Product::find($voucher->product_id);
        $headProdAccountId = $headProd?->account_id ?? $defaultInventoryAccountId;

        $firstInput = $voucher->inputs()->first();
        $rawMaterialAccountId = $firstInput?->product?->account_id ?? $defaultInventoryAccountId;

        $totalDr = 0;
        $totalCr = 0;

        // ─────────────────────────────────────────────────────────────
        // DEBIT 1: Finished Goods Inventory (Each Head Product Individually)
        // ─────────────────────────────────────────────────────────────
        $headProductOutputs = $voucher->outputs()->where(function ($q) use ($voucher) {
            $q->where('product_id', $voucher->product_id)
              ->orWhere('cost_type', 'head_product');
        })->get();

        if ($headProductOutputs->isNotEmpty()) {
            foreach ($headProductOutputs as $fgOut) {
                $fgAmount = (float)($fgOut->amount ?? 0);
                if ($fgAmount > 0) {
                    $fgAccountId = $fgOut->product?->account_id ?? $headProdAccountId;
                    $account = Account::find($fgAccountId);
                    $brandName = $fgOut->brand?->name ?? 'Default Brand';

                    Transaction::create([
                        'company_id' => $companyId,
                        'voucher_no' => $prodNo,
                        'voucher_date' => $voucherDate,
                        'transaction_voucher_type_id' => $voucherTypeId,
                        'account_id' => $fgAccountId,
                        'counter_account_id' => $rawMaterialAccountId,
                        'account_unique_no' => $account?->unique_no ?? 'ACC-000000',
                        'type' => 'debit',
                        'is_opening_balance' => 'no',
                        'amount' => $fgAmount,
                        'purpose' => 'production-voucher-fg',
                        'remarks' => "Finished Goods capitalization: {$fgOut->product?->name} [Brand: {$brandName}] ({$fgOut->qty} kg @ PKR {$fgOut->rate}/kg) against Production Voucher {$prodNo}",
                        'jv_narration' => "Finished Goods received: {$fgOut->product?->name}",
                        'reference_no' => $prodNo,
                        'payment_against' => 'production-inventory',
                        'against_reference_no' => $prodNo,
                        'status' => 'active',
                        'created_by' => auth()->id() ?? 1,
                    ]);
                    $totalDr += $fgAmount;
                }
            }
        } else {
            // Fallback if no output rows found
            $netFgCost = (float)($costing['net_finished_good_cost'] ?? 0);
            if ($netFgCost > 0) {
                $account = Account::find($headProdAccountId);
                Transaction::create([
                    'company_id' => $companyId,
                    'voucher_no' => $prodNo,
                    'voucher_date' => $voucherDate,
                    'transaction_voucher_type_id' => $voucherTypeId,
                    'account_id' => $headProdAccountId,
                    'counter_account_id' => $rawMaterialAccountId,
                    'account_unique_no' => $account?->unique_no ?? 'ACC-000000',
                    'type' => 'debit',
                    'is_opening_balance' => 'no',
                    'amount' => $netFgCost,
                    'purpose' => 'production-voucher-fg',
                    'remarks' => "Finished Goods Inventory capitalization: {$costing['total_fg_qty']} kg @ PKR {$costing['finished_good_rate']}/kg against Production Voucher {$prodNo}",
                    'jv_narration' => "Finished Goods received from milling",
                    'reference_no' => $prodNo,
                    'payment_against' => 'production-inventory',
                    'against_reference_no' => $prodNo,
                    'status' => 'active',
                    'created_by' => auth()->id() ?? 1,
                ]);
                $totalDr += $netFgCost;
            }
        }

        // ─────────────────────────────────────────────────────────────
        // DEBIT 2: By-Product Inventories (Recovery Values)
        // ─────────────────────────────────────────────────────────────
        foreach ($costing['by_product_outputs'] as $bp) {
            $bpAmount = (float)($bp['amount'] ?? 0);
            if ($bpAmount > 0) {
                $bpAccountId = $bp['account_id'] ?? $defaultInventoryAccountId;
                $account = Account::find($bpAccountId);

                Transaction::create([
                    'company_id' => $companyId,
                    'voucher_no' => $prodNo,
                    'voucher_date' => $voucherDate,
                    'transaction_voucher_type_id' => $voucherTypeId,
                    'account_id' => $bpAccountId,
                    'counter_account_id' => $rawMaterialAccountId,
                    'account_unique_no' => $account?->unique_no ?? 'ACC-000000',
                    'type' => 'debit',
                    'is_opening_balance' => 'no',
                    'amount' => $bpAmount,
                    'purpose' => 'production-voucher-byproduct',
                    'remarks' => "By-Product Inventory recovery: {$bp['product_name']} ({$bp['qty']} kg @ PKR {$bp['rate']}/kg) against Production Voucher {$prodNo}",
                    'jv_narration' => "By-Product recovery from milling",
                    'reference_no' => $prodNo,
                    'payment_against' => 'production-inventory',
                    'against_reference_no' => $prodNo,
                    'status' => 'active',
                    'created_by' => auth()->id() ?? 1,
                ]);
                $totalDr += $bpAmount;
            }
        }

        // ─────────────────────────────────────────────────────────────
        // CREDIT 1: Raw Material Inventory (Paddy Consumed)
        // ─────────────────────────────────────────────────────────────
        foreach ($voucher->inputs as $input) {
            $inAmount = (float)($input->amount ?? 0);
            if ($inAmount > 0) {
                $inAccountId = $input->product?->account_id ?? $defaultInventoryAccountId;
                $account = Account::find($inAccountId);

                Transaction::create([
                    'company_id' => $companyId,
                    'voucher_no' => $prodNo,
                    'voucher_date' => $voucherDate,
                    'transaction_voucher_type_id' => $voucherTypeId,
                    'account_id' => $inAccountId,
                    'counter_account_id' => $headProdAccountId,
                    'account_unique_no' => $account?->unique_no ?? 'ACC-000000',
                    'type' => 'credit',
                    'is_opening_balance' => 'no',
                    'amount' => $inAmount,
                    'purpose' => 'production-voucher-raw-material',
                    'remarks' => "Raw Material consumed: {$input->product?->name} ({$input->qty} kg @ PKR {$input->rate}/kg) in Production Voucher {$prodNo}",
                    'jv_narration' => "Raw Material consumed in milling",
                    'reference_no' => $prodNo,
                    'payment_against' => 'production-consumption',
                    'against_reference_no' => $prodNo,
                    'status' => 'active',
                    'created_by' => auth()->id() ?? 1,
                ]);
                $totalCr += $inAmount;
            }
        }

        // ─────────────────────────────────────────────────────────────
        // CREDIT 2: Accrued Operational Liabilities (Milling Variables 2-10-x)
        // ─────────────────────────────────────────────────────────────
        foreach ($costing['variable_breakdown'] as $varItem) {
            $vAmount = (float)($varItem['amount'] ?? 0);
            if ($vAmount > 0) {
                $vAccountId = $varItem['account_id'] ?? $defaultAccruedAccountId;
                $account = Account::find($vAccountId);

                Transaction::create([
                    'company_id' => $companyId,
                    'voucher_no' => $prodNo,
                    'voucher_date' => $voucherDate,
                    'transaction_voucher_type_id' => $voucherTypeId,
                    'account_id' => $vAccountId,
                    'counter_account_id' => $headProdAccountId,
                    'account_unique_no' => $account?->unique_no ?? 'ACC-000000',
                    'type' => 'credit',
                    'is_opening_balance' => 'no',
                    'amount' => $vAmount,
                    'purpose' => 'production-voucher-variable-overhead',
                    'remarks' => "Accrued Milling Overhead: {$varItem['title']} (PKR {$varItem['rate']}/kg for {$costing['total_input_qty']} kg) against Production Voucher {$prodNo}",
                    'jv_narration' => "Milling overhead accrued liability",
                    'reference_no' => $prodNo,
                    'payment_against' => 'production-accrual',
                    'against_reference_no' => $prodNo,
                    'status' => 'active',
                    'created_by' => auth()->id() ?? 1,
                ]);
                $totalCr += $vAmount;
            }
        }

        // Optional log check for double-entry verification
        $difference = round(abs($totalDr - $totalCr), 2);
        if ($difference > 0.05) {
            Log::warning("Production Voucher Auto-JV imbalance detected for {$prodNo}: Total DR = {$totalDr}, Total CR = {$totalCr}, Diff = {$difference}");
        }
    }

    /**
     * Post Inventory Stock-OUT and Stock-IN records into stocks table
     */
    public function postStockLedger(ProductionVoucher $voucher, array $costing): void
    {
        $prodNo = $voucher->prod_no;
        $companyId = $voucher->company_id ?? 1;
        $locationId = $voucher->location_id;
        $voucherId = $voucher->id;

        // 1. Remove previous stock records for this production voucher
        Stock::where(function ($q) use ($voucherId, $prodNo) {
            $q->where('production_voucher_id', $voucherId)
              ->orWhere(function ($sq) use ($prodNo) {
                  $sq->where('voucher_no', $prodNo)
                     ->where('voucher_type', 'production_voucher');
              });
        })->delete();

        // 2. Stock-OUT: Raw Material Inputs Consumed
        foreach ($voucher->inputs as $input) {
            $qty = (float)$input->qty;
            if ($qty > 0) {
                $rate = (float)($input->rate ?? 0);
                $amount = (float)($input->amount ?? ($qty * $rate));

                Stock::create([
                    'company_id' => $companyId,
                    'product_id' => $input->product_id,
                    'production_voucher_id' => $voucherId,
                    'voucher_type' => 'production_voucher',
                    'voucher_no' => $prodNo,
                    'qty' => $qty,
                    'type' => 'stock-out',
                    'price' => $amount,
                    'avg_price_per_kg' => $rate,
                    'avg_cost_price' => $rate,
                    'company_location_id' => $locationId,
                    'subarrival_id' => $input->location_id,
                    'parent_id' => $voucherId,
                    'parentable_id' => $voucherId,
                    'parentable_type' => ProductionVoucher::class,
                    'narration' => "Production Voucher Raw Material Consumed: {$prodNo}",
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // 3. Stock-IN: Finished Goods Produced
        $fgRate = (float)($costing['finished_good_rate'] ?? 0);
        $headProductOutputs = $voucher->outputs()->where(function ($q) use ($voucher) {
            $q->where('product_id', $voucher->product_id)
              ->orWhere('cost_type', 'head_product');
        })->get();

        foreach ($headProductOutputs as $fgOut) {
            $qty = (float)$fgOut->qty;
            if ($qty > 0) {
                $brandId = $fgOut->brand_id ?? $fgOut->jobOrderPackingItem?->brand_id ?? null;
                $amount = (float)($fgOut->amount ?? ($qty * $fgRate));
                $avgCostPrice = $this->calculateStockInWac($fgOut->product_id, $qty, $amount, $fgRate, $prodNo, $brandId);

                Stock::create([
                    'company_id' => $companyId,
                    'product_id' => $fgOut->product_id,
                    'production_voucher_id' => $voucherId,
                    'voucher_type' => 'production_voucher',
                    'voucher_no' => $prodNo,
                    'qty' => $qty,
                    'type' => 'stock-in',
                    'price' => $amount,
                    'avg_price_per_kg' => $fgRate,
                    'avg_cost_price' => $avgCostPrice,
                    'company_location_id' => $locationId,
                    'subarrival_id' => $fgOut->arrival_sub_location_id,
                    'brand_id' => $brandId,
                    'parent_id' => $voucherId,
                    'parentable_id' => $voucherId,
                    'parentable_type' => ProductionVoucher::class,
                    'narration' => "Production Voucher Finished Goods Capitalized: {$prodNo}",
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // 4. Stock-IN: By-Products Recovered
        $byProductOutputs = $voucher->outputs()->where(function ($q) use ($voucher) {
            $q->where('product_id', '!=', $voucher->product_id)
              ->where('cost_type', '!=', 'head_product');
        })->get();

        foreach ($byProductOutputs as $bpOut) {
            $qty = (float)$bpOut->qty;
            if ($qty > 0) {
                $bpBrandId = $bpOut->brand_id ?? $bpOut->jobOrderPackingItem?->brand_id ?? null;
                $rate = (float)($bpOut->rate ?? 0);
                $amount = (float)($bpOut->amount ?? ($qty * $rate));
                $avgCostPrice = $this->calculateStockInWac($bpOut->product_id, $qty, $amount, $rate, $prodNo, $bpBrandId);

                Stock::create([
                    'company_id' => $companyId,
                    'product_id' => $bpOut->product_id,
                    'production_voucher_id' => $voucherId,
                    'voucher_type' => 'production_voucher',
                    'voucher_no' => $prodNo,
                    'qty' => $qty,
                    'type' => 'stock-in',
                    'price' => $amount,
                    'avg_price_per_kg' => $rate,
                    'avg_cost_price' => $avgCostPrice,
                    'company_location_id' => $locationId,
                    'subarrival_id' => $bpOut->arrival_sub_location_id,
                    'brand_id' => $bpBrandId,
                    'parent_id' => $voucherId,
                    'parentable_id' => $voucherId,
                    'parentable_type' => ProductionVoucher::class,
                    'narration' => "Production Voucher By-Product Recovered: {$prodNo}",
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Calculate Weighted Average Cost (WAC) for incoming stock
     */
    protected function calculateStockInWac(
        int $productId,
        float $incomingQty,
        float $incomingAmount,
        float $unitRate,
        string $excludeVoucherNo,
        ?int $brandId = null
    ): float {
        $latestStockInQuery = Stock::where('product_id', $productId)
            ->where('type', 'stock-in')
            ->where('voucher_no', '!=', $excludeVoucherNo)
            ->whereNotNull('avg_cost_price')
            ->where('avg_cost_price', '>', 0);

        if ($brandId) {
            $latestStockInQuery->where('brand_id', $brandId);
        }

        $latestStockIn = $latestStockInQuery->latest('id')->first();

        // Fallback to product level if no previous stock for this brand
        if (!$latestStockIn && $brandId) {
            $latestStockIn = Stock::where('product_id', $productId)
                ->where('type', 'stock-in')
                ->where('voucher_no', '!=', $excludeVoucherNo)
                ->whereNotNull('avg_cost_price')
                ->where('avg_cost_price', '>', 0)
                ->latest('id')
                ->first();
        }

        $currentWac = $latestStockIn ? (float)$latestStockIn->avg_cost_price : $unitRate;

        $inQuery = Stock::where('product_id', $productId)
            ->where('type', 'stock-in')
            ->where('voucher_no', '!=', $excludeVoucherNo);
        $outQuery = Stock::where('product_id', $productId)
            ->where('type', 'stock-out')
            ->where('voucher_no', '!=', $excludeVoucherNo);

        if ($brandId) {
            $inQuery->where('brand_id', $brandId);
            $outQuery->where('brand_id', $brandId);
        }

        $totalStockInQty = $inQuery->sum('qty');
        $totalStockOutQty = $outQuery->sum('qty');

        $currentOnHandQty = max(0, (float)$totalStockInQty - (float)$totalStockOutQty);

        if ($currentOnHandQty > 0) {
            $currentTotalValue = $currentOnHandQty * $currentWac;
            $combinedTotalQty = $currentOnHandQty + $incomingQty;
            $combinedTotalValue = $currentTotalValue + $incomingAmount;

            return $combinedTotalQty > 0 ? round($combinedTotalValue / $combinedTotalQty, 4) : $unitRate;
        }

        return $unitRate;
    }

    /**
     * Delete Financial Transactions and Stock Entries when Voucher is deleted
     */
    public function deleteVoucherFinancials(ProductionVoucher $voucher): void
    {
        $prodNo = $voucher->prod_no;

        Transaction::where('against_reference_no', $prodNo)
            ->where('purpose', 'like', 'production-voucher%')
            ->delete();

        Stock::where(function ($q) use ($voucher, $prodNo) {
            $q->where('production_voucher_id', $voucher->id)
              ->orWhere(function ($sq) use ($prodNo) {
                  $sq->where('voucher_no', $prodNo)
                     ->where('voucher_type', 'production_voucher');
              });
        })->delete();

        ProductionVoucherVariable::where('production_voucher_id', $voucher->id)->delete();
    }
}
