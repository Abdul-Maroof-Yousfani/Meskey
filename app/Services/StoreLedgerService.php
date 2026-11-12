<?php

namespace App\Services;

use App\Models\Master\Account\Account;
use App\Models\Master\Account\Stock;
use App\Models\Master\Account\Transaction;
use App\Models\Master\Supplier;
use App\Models\Procurement\Store\DebitNote;
use App\Models\Procurement\Store\PurchaseBagQC;
use App\Models\Procurement\Store\PurchaseBill;
use App\Models\Procurement\Store\PurchaseBillData;
use App\Models\Procurement\Store\PurchaseOrderReceiving;
use App\Models\Procurement\Store\PurchaseOrderReceivingData;
use App\Models\Procurement\Store\PurchaseReturn;
use App\Models\Procurement\Store\PurchaseReturnData;
use App\Models\Procurement\Store\RejectionReturn;
use App\Models\Procurement\Store\RejectionReturnItem;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

/**
 * Store (procurement) stock + ledger postings. Everything here runs on approval only.
 *
 * Every ledger posting is a pair: one debit row and one credit row of the same amount,
 * each pointing at the other through counter_account_id, so a voucher can never be
 * unbalanced. All rows of one document carry action = "<document>:<id>", which makes
 * re-posting idempotent (old rows are removed first) and lets edit/delete reverse them.
 *
 * Accounting (GRN accrual, "Option B"):
 *   QC approval       Dr Inventory          / Cr GRN Clearing   (full received qty x PO rate)
 *   Purchase Bill     Dr GRN Clearing       / Cr Supplier       (billed qty x PO rate, if accrued)
 *                     Dr/Cr Inventory       / Supplier          (rate difference, or full gross if not accrued)
 *                     Dr Supplier           / Cr Discount       (discount)
 *                     Dr Supplier           / Cr Allowance      (QC deduction)
 *                     Dr Tax                / Cr Supplier       (tax)
 *   Rejection Return  Dr GRN Clearing       / Cr Inventory      (returned qty x PO rate)
 *   Debit Note        Dr Supplier / Cr Inventory, Dr Discount / Cr Supplier, Dr Supplier / Cr Tax
 *   Purchase Return   Dr Supplier / Cr Inventory, Dr Discount / Cr Supplier,
 *                     Dr Allowance / Cr Supplier, Dr Supplier / Cr Tax
 */
class StoreLedgerService
{
    public const VT_PURCHASE_BILL = 5;
    public const VT_PURCHASE_RETURN = 6;
    public const VT_DEBIT_NOTE = 7;
    public const VT_GRN = 8;
    public const VT_QC = 9;

    private const TAX_PATH = '2-6';
    private const DISCOUNT_PATH = '6-1';
    private const ALLOWANCE_PATH = '5-2';
    private const LIABILITIES_PATH = '2';
    private const GRN_CLEARING_TABLE = 'grn_clearing';
    private const GRN_CLEARING_NAME = 'GRN Clearing (Goods Received Not Invoiced)';

    // ------------------------------------------------------------------
    // Source keys
    // ------------------------------------------------------------------

    public static function key(string $document, int $id): string
    {
        return "{$document}:{$id}";
    }

    public static function qcKey(PurchaseBagQC $qc): string
    {
        return self::key('purchase_bag_qc', $qc->id);
    }

    public static function billKey(PurchaseBill $bill): string
    {
        return self::key('purchase_bill', $bill->id);
    }

    public static function returnKey(PurchaseReturn $return): string
    {
        return self::key('purchase_return', $return->id);
    }

    public static function debitNoteKey(DebitNote $note): string
    {
        return self::key('debit_note', $note->id);
    }

    public static function rejectionReturnKey(RejectionReturn $return): string
    {
        return self::key('rejection_return', $return->id);
    }

    /** Remove every ledger row a document posted. */
    public function reverse(string $sourceKey): void
    {
        Transaction::where('action', $sourceKey)->delete();
    }

    // ------------------------------------------------------------------
    // Amount calculations (server side, never trusted from the browser)
    // ------------------------------------------------------------------

    /**
     * Bill line amounts, same formula as purchase-bill/bills.blade.php:
     * qty and deduction come from the QC, rate from the PO.
     */
    public static function calculateBillLine(PurchaseOrderReceivingData $grnLine, float $discountPercent, float $taxPercent, ?float $fallbackQty = null): array
    {
        $qc = $grnLine->qc;
        $rate = (float) ($grnLine->purchase_order_data->rate ?? 0);

        $acceptedQty = $qc ? (float) $qc->accepted_quantity : (float) ($fallbackQty ?? $grnLine->qty);
        $rejectedQty = $qc ? (float) $qc->rejected_quantity : 0.0;
        $deductionPerPiece = $qc ? (float) $qc->deduction_per_bag : 0.0;
        $qty = $acceptedQty;
        $deduction = 0.0;

        $deductionType = $qc ? (string) $qc->deduction_type : '';
        if ($deductionType === 'full_deduction') {
            $qty += $rejectedQty;
            $deduction = $qty * $deductionPerPiece;
        } elseif ($deductionType === 'half_deduction') {
            $deduction = $rejectedQty * $deductionPerPiece;
            $qty += $rejectedQty;
        }

        $discountPercent = min(max($discountPercent, 0), 100);
        $taxPercent = min(max($taxPercent, 0), 100);

        $gross = round($qty * $rate, 2);
        $discount = round($gross * $discountPercent / 100, 2);
        $deduction = round($deduction, 2);
        $taxable = $gross - $discount - $deduction;
        $tax = round($taxable * $taxPercent / 100, 2);
        $net = round($taxable, 2);

        return [
            'qty' => $qty,
            'accepted_qty' => $acceptedQty,
            'rejected_qty' => $rejectedQty,
            'rate' => $rate,
            'gross_amount' => $gross,
            'discount_percent' => $discountPercent,
            'discount_amount' => $discount,
            'deduction_per_piece' => $deductionPerPiece,
            'deduction' => $deduction,
            'net_amount' => $net,
            'tax_percent' => $taxPercent,
            'tax_amount' => $tax,
            'final_amount' => round($net + $tax, 2),
        ];
    }

    /** Purchase return line amounts, same formula as purchase-return/create.blade.php. */
    public static function calculateReturnLine(float $qty, float $rate, float $discountPercent, float $taxPercent, float $deductionPerPiece): array
    {
        $discountPercent = min(max($discountPercent, 0), 100);
        $taxPercent = min(max($taxPercent, 0), 100);
        $deductionPerPiece = max($deductionPerPiece, 0);

        $gross = round($qty * $rate, 2);
        $discount = round($gross * $discountPercent / 100, 2);
        $deduction = round($qty * $deductionPerPiece, 2);
        $amount = $gross - $discount - $deduction;
        $tax = round($amount * $taxPercent / 100, 2);

        return [
            'gross_amount' => $gross,
            'discount_percent' => $discountPercent,
            'discount_amount' => $discount,
            'deduction_per_piece' => $deductionPerPiece,
            'deduction' => $deduction,
            'tax_percent' => $taxPercent,
            'tax_amount' => $tax,
            'net_amount' => round($amount + $tax, 2),
        ];
    }

    /** Debit note line: qty x bill rate, with the bill line's discount and tax share. */
    public static function calculateDebitNoteLine(PurchaseBillData $billLine, float $qty): array
    {
        $gross = round($qty * (float) $billLine->rate, 2);
        $discount = round($gross * (float) $billLine->discount_percent / 100, 2);
        $tax = round(($gross - $discount) * (float) $billLine->tax_percent / 100, 2);

        return [
            'rate' => (float) $billLine->rate,
            'gross_amount' => $gross,
            'discount_amount' => $discount,
            'tax_amount' => $tax,
            'net_amount' => round($gross - $discount + $tax, 2),
        ];
    }

    // ------------------------------------------------------------------
    // QC approval: stock-in + GRN accrual
    // ------------------------------------------------------------------

    public function buildQcEntries(PurchaseBagQC $qc): array
    {
        $grnLine = $qc->grn;
        $grn = $grnLine?->purchase_order_receiving;
        if (!$grnLine || !$grn) {
            throw new \RuntimeException('QC is not linked to a GRN line.');
        }

        // Already billed (items billed without QC): the bill debits inventory itself, so no accrual.
        if ($grnLine->bill()->exists()) {
            return [];
        }

        $qty = (float) $qc->accepted_quantity + (float) $qc->rejected_quantity + (float) ($qc->rejection_return ?? 0);
        $amount = round($qty * (float) ($grnLine->purchase_order_data->rate ?? 0), 2);

        $inventory = $this->productAccount($grnLine->item_id);
        $clearing = $this->grnClearingAccount($grn->company_id);

        return [
            $this->pair($amount, $inventory, $clearing, 'store-grn-inventory',
                "Inventory received against GRN {$grn->purchase_order_receiving_no} ({$qty} x {$grnLine->purchase_order_data?->rate}). Not yet invoiced."),
        ];
    }

    /**
     * Everything a QC approval posts (manual approval and auto-approval):
     * stock-in of the full received qty and its GRN accrual. Safe to call more than once.
     */
    public function postQcApproval(PurchaseBagQC $qc): void
    {
        $grn = $qc->grn->purchase_order_receiving;

        DB::transaction(function () use ($qc, $grn) {
            $this->postQcStockIn($qc);

            $this->post(self::qcKey($qc), self::VT_GRN, $grn->purchase_order_receiving_no, $this->buildQcEntries($qc), [
                'voucher_date' => $grn->order_receiving_date,
                'company_id' => $grn->company_id,
                'grn_no' => $grn->purchase_order_receiving_no,
                'payment_against' => 'Purchase QC',
                'created_by' => $grn->created_by,
            ]);
        });
    }

    /**
     * Stock-in for the QC'd GRN line: the full received qty
     * (accepted + rejected with deduction + rejected without deduction).
     * Bags rejected without deduction physically stay in store until the supplier
     * collects them; the Rejection Return then posts the matching stock-out.
     * Does nothing when the line's stock-in already exists.
     */
    public function postQcStockIn(PurchaseBagQC $qc): ?Stock
    {
        $grnLine = $qc->grn;
        $grn = $grnLine?->purchase_order_receiving;
        if (!$grnLine || !$grn) {
            return null;
        }

        $alreadyPosted = Stock::where('voucher_type', 'grn')
            ->where('type', 'stock-in')
            ->where(function ($q) use ($grnLine, $grn) {
                $q->where(function ($q) use ($grnLine) {
                    $q->where('parentable_type', PurchaseOrderReceivingData::class)
                        ->where('parentable_id', $grnLine->id);
                })->orWhere(function ($q) use ($grnLine, $grn) {
                    // Rows posted by the old code were linked to the GRN header.
                    $q->where('parentable_type', PurchaseOrderReceiving::class)
                        ->where('parentable_id', $grn->id)
                        ->where('product_id', $grnLine->item_id);
                });
            })
            ->first();

        if ($alreadyPosted) {
            return $alreadyPosted;
        }

        $qty = (float) $qc->accepted_quantity + (float) $qc->rejected_quantity + (float) ($qc->rejection_return ?? 0);
        if ($qty <= 0) {
            return null;
        }

        $price = round($qty * (float) ($grnLine->purchase_order_data->rate ?? 0), 2);

        return Stock::create([
            'company_id' => $grn->company_id,
            'product_id' => $grnLine->item_id,
            'voucher_type' => 'grn',
            'voucher_no' => $grn->purchase_order_receiving_no,
            'qty' => $qty,
            'type' => 'stock-in',
            'narration' => 'Goods Received Note',
            'price' => $price,
            'avg_price_per_kg' => $price,
            'company_location_id' => $grn->location_id,
            'parent_id' => $grn->id,
            'parentable_type' => PurchaseOrderReceivingData::class,
            'parentable_id' => $grnLine->id,
        ]);
    }

    /** True when this GRN line's value was accrued in GRN Clearing at QC approval. */
    public function isAccrued(PurchaseOrderReceivingData $grnLine): bool
    {
        $qc = $grnLine->qc;

        return $qc && Transaction::where('action', self::qcKey($qc))->exists();
    }

    /** Reverse a QC: stock-in and accrual. */
    public function reverseQc(PurchaseBagQC $qc): void
    {
        $this->reverse(self::qcKey($qc));

        Stock::where('voucher_type', 'grn')
            ->where('type', 'stock-in')
            ->where('parentable_type', PurchaseOrderReceivingData::class)
            ->where('parentable_id', $qc->purchase_order_receiving_data_id)
            ->delete();
    }

    // ------------------------------------------------------------------
    // Purchase Bill
    // ------------------------------------------------------------------

    public function buildBillEntries(PurchaseBill $bill): array
    {
        $bill->loadMissing('bill_data.PurchaseOrderReceivingData.purchase_order_data', 'bill_data.PurchaseOrderReceivingData.qc');

        $supplier = $this->supplierAccount($bill->supplier_id);
        $clearing = $this->grnClearingAccount($bill->company_id);
        $entries = [];

        foreach ($bill->bill_data as $line) {
            $inventory = $this->productAccount($line->item_id);
            $grnLine = $line->PurchaseOrderReceivingData;
            $gross = round((float) $line->gross_amount, 2);
            $label = "Bill {$bill->bill_no}, item #{$line->item_id}";

            // Clear what QC accrued for the billed qty; any rate difference adjusts inventory.
            $accrued = ($grnLine && $this->isAccrued($grnLine))
                ? round((float) $line->qty * (float) ($grnLine->purchase_order_data->rate ?? 0), 2)
                : 0.0;
            $variance = round($gross - $accrued, 2);

            $entries[] = $this->pair($accrued, $clearing, $supplier, 'store-bill-grn-clearing',
                "{$label}: GRN clearing settled, payable to supplier.");
            if ($variance > 0) {
                $entries[] = $this->pair($variance, $inventory, $supplier, 'store-bill-inventory',
                    "{$label}: inventory cost not accrued at QC.");
            } elseif ($variance < 0) {
                $entries[] = $this->pair(-$variance, $supplier, $inventory, 'store-bill-inventory',
                    "{$label}: bill value below accrued QC value.");
            }

            $entries[] = $this->pair((float) $line->discount_amount, $supplier, $this->hierarchyAccount(self::DISCOUNT_PATH), 'store-bill-discount',
                "{$label}: discount received from supplier.");
            $entries[] = $this->pair((float) $line->deduction, $supplier, $this->hierarchyAccount(self::ALLOWANCE_PATH), 'store-bill-deduction',
                "{$label}: QC deduction on rejected bags.");
            $entries[] = $this->pair((float) $line->tax_amount, $this->hierarchyAccount(self::TAX_PATH), $supplier, 'store-bill-tax',
                "{$label}: input tax on purchase.");
        }

        return $entries;
    }

    public function postPurchaseBill(PurchaseBill $bill): void
    {
        $this->clearLegacyBillEntries($bill);

        $this->post(self::billKey($bill), self::VT_PURCHASE_BILL, (string) $bill->bill_no, $this->buildBillEntries($bill), [
            'voucher_date' => $bill->bill_date,
            'company_id' => $bill->company_id,
            'grn_no' => $bill->grn?->purchase_order_receiving_no,
            'payment_against' => 'Purchase Bill',
            'created_by' => $bill->created_by,
        ]);
    }

    /** Rows the old code posted on save (no action key). */
    public function clearLegacyBillEntries(PurchaseBill $bill): void
    {
        Transaction::whereNull('action')
            ->where('transaction_voucher_type_id', self::VT_PURCHASE_BILL)
            ->where('voucher_no', (string) $bill->bill_no)
            ->where('grn_no', $bill->grn?->purchase_order_receiving_no)
            ->delete();
    }

    // ------------------------------------------------------------------
    // Purchase Return: stock-out + ledger
    // ------------------------------------------------------------------

    public function buildPurchaseReturnEntries(PurchaseReturn $return): array
    {
        $return->loadMissing('purchase_return_data');

        $supplier = $this->supplierAccount($return->supplier_id);
        $entries = [];

        foreach ($return->purchase_return_data as $line) {
            $inventory = $this->productAccount($line->item_id);
            $label = "Purchase Return {$return->pr_no}, item #{$line->item_id}";

            $entries[] = $this->pair((float) $line->gross_amount, $supplier, $inventory, 'store-return-inventory',
                "{$label}: goods returned to supplier.");
            $entries[] = $this->pair((float) $line->discount_amount, $this->hierarchyAccount(self::DISCOUNT_PATH), $supplier, 'store-return-discount',
                "{$label}: discount reversed.");
            $entries[] = $this->pair((float) $line->deduction, $this->hierarchyAccount(self::ALLOWANCE_PATH), $supplier, 'store-return-deduction',
                "{$label}: deduction reversed.");
            $entries[] = $this->pair((float) $line->tax_amount, $supplier, $this->hierarchyAccount(self::TAX_PATH), 'store-return-tax',
                "{$label}: input tax reversed.");
        }

        return $entries;
    }

    public function postPurchaseReturn(PurchaseReturn $return): void
    {
        $this->clearLegacyReturnEntries($return);

        $this->post(self::returnKey($return), self::VT_PURCHASE_RETURN, (string) $return->pr_no, $this->buildPurchaseReturnEntries($return), [
            'voucher_date' => $return->date,
            'company_id' => auth()->user()?->current_company_id,
            'payment_against' => 'Purchase Return',
            'created_by' => $return->created_by,
        ]);

        $this->postPurchaseReturnStock($return);
    }

    private function postPurchaseReturnStock(PurchaseReturn $return): void
    {
        $return->loadMissing('purchase_return_data.purchase_bill_data.PurchaseOrderReceivingData.purchase_order_receiving');
        $this->clearPurchaseReturnStock($return);

        foreach ($return->purchase_return_data as $line) {
            $grn = $line->purchase_bill_data?->PurchaseOrderReceivingData?->purchase_order_receiving;

            Stock::create([
                'company_id' => $grn?->company_id,
                'product_id' => $line->item_id,
                'voucher_type' => 'purchase_return',
                'voucher_no' => $return->pr_no,
                'qty' => $line->quantity,
                'type' => 'stock-out',
                'narration' => 'Purchase Return',
                'price' => $line->gross_amount,
                'avg_price_per_kg' => $line->gross_amount,
                'company_location_id' => $grn?->location_id,
                'parent_id' => $grn?->id,
                'parentable_type' => PurchaseReturnData::class,
                'parentable_id' => $line->id,
            ]);
        }
    }

    public function clearPurchaseReturnStock(PurchaseReturn $return): void
    {
        $lineIds = $return->purchase_return_data()->pluck('id');
        Stock::where('voucher_type', 'purchase_return')
            ->where(function ($q) use ($lineIds, $return) {
                $q->where(function ($q) use ($lineIds) {
                    $q->where('parentable_type', PurchaseReturnData::class)->whereIn('parentable_id', $lineIds);
                })->orWhere(function ($q) use ($return) {
                    // rows posted by the old code had no parentable link
                    $q->whereNull('parentable_type')->where('voucher_no', $return->pr_no);
                });
            })
            ->delete();
    }

    /** Rows the old code posted on save (no action key). */
    public function clearLegacyReturnEntries(PurchaseReturn $return): void
    {
        Transaction::whereNull('action')
            ->where('transaction_voucher_type_id', self::VT_PURCHASE_RETURN)
            ->where('voucher_no', (string) $return->pr_no)
            ->delete();
    }

    // ------------------------------------------------------------------
    // Debit Note: ledger only (damaged bags stay in store)
    // ------------------------------------------------------------------

    public function buildDebitNoteEntries(DebitNote $note): array
    {
        $note->loadMissing('debit_note_data', 'grn');

        $supplier = $this->supplierAccount($note->grn?->supplier_id);
        $entries = [];

        foreach ($note->debit_note_data as $line) {
            $billLine = PurchaseBillData::find($line->purchase_bill_data_id);
            if (!$billLine) {
                throw new \RuntimeException("Debit Note {$note->reference_number}: bill line not found.");
            }

            $amounts = self::calculateDebitNoteLine($billLine, (float) $line->debit_note_quantity);
            $inventory = $this->productAccount($line->item_id);
            $label = "Debit Note {$note->reference_number}, item #{$line->item_id}";

            $entries[] = $this->pair($amounts['gross_amount'], $supplier, $inventory, 'store-debit-note-inventory',
                "{$label}: value of damaged/short goods recovered from supplier.");
            $entries[] = $this->pair($amounts['discount_amount'], $this->hierarchyAccount(self::DISCOUNT_PATH), $supplier, 'store-debit-note-discount',
                "{$label}: discount share reversed.");
            $entries[] = $this->pair($amounts['tax_amount'], $supplier, $this->hierarchyAccount(self::TAX_PATH), 'store-debit-note-tax',
                "{$label}: input tax share reversed.");
        }

        return $entries;
    }

    public function postDebitNote(DebitNote $note): void
    {
        $this->post(self::debitNoteKey($note), self::VT_DEBIT_NOTE, (string) $note->reference_number, $this->buildDebitNoteEntries($note), [
            'voucher_date' => $note->transaction_date,
            'company_id' => $note->grn?->company_id,
            'grn_no' => $note->grn?->purchase_order_receiving_no,
            'payment_against' => 'Debit Note',
            'created_by' => $note->created_by,
        ]);
    }

    // ------------------------------------------------------------------
    // Rejection Return: stock-out + clear the accrual of the returned bags
    // ------------------------------------------------------------------

    /** GRN line of this GRN that holds the "rejected without deduction" qty of an item. */
    private function rejectionGrnLine(int $grnId, int $itemId): ?PurchaseOrderReceivingData
    {
        return PurchaseOrderReceivingData::with('qc', 'purchase_order_data')
            ->where('purchase_order_receiving_id', $grnId)
            ->where('item_id', $itemId)
            ->whereHas('qc', fn ($q) => $q->where('am_approval_status', 'approved')->where('rejection_return', '>', 0))
            ->first();
    }

    public function buildRejectionReturnEntries(RejectionReturn $return): array
    {
        $return->loadMissing('items', 'grn');
        $clearing = $this->grnClearingAccount($return->grn?->company_id);
        $entries = [];

        foreach ($return->items as $item) {
            $grnLine = $this->rejectionGrnLine((int) $return->grn_id, (int) $item->item_id);
            // QCs approved before the accrual existed never debited inventory: stock-out only.
            if (!$grnLine || !$this->isAccrued($grnLine)) {
                continue;
            }

            $amount = round((float) $item->quantity * (float) ($grnLine->purchase_order_data->rate ?? 0), 2);
            $entries[] = $this->pair($amount, $clearing, $this->productAccount($item->item_id), 'store-rejection-return',
                "Rejection Return {$return->return_no}: {$item->quantity} bags returned to supplier, accrual reversed.");
        }

        return $entries;
    }

    public function postRejectionReturn(RejectionReturn $return): void
    {
        $return->loadMissing('items', 'grn');
        $grn = $return->grn;

        $this->post(self::rejectionReturnKey($return), self::VT_QC, (string) $return->return_no, $this->buildRejectionReturnEntries($return), [
            'voucher_date' => $return->date,
            'company_id' => $return->company_id ?? $grn?->company_id,
            'grn_no' => $grn?->purchase_order_receiving_no,
            'payment_against' => 'Rejection Return',
            'created_by' => $return->created_by,
        ]);

        $this->clearRejectionReturnStock($return);
        foreach ($return->items as $item) {
            $grnLine = $this->rejectionGrnLine((int) $return->grn_id, (int) $item->item_id);
            $amount = round((float) $item->quantity * (float) ($grnLine?->purchase_order_data->rate ?? $item->rate), 2);

            Stock::create([
                'company_id' => $grn?->company_id,
                'product_id' => $item->item_id,
                'voucher_type' => 'qc',
                'voucher_no' => $return->return_no,
                'qty' => $item->quantity,
                'type' => 'stock-out',
                'narration' => 'Rejection Return Item',
                'price' => $amount,
                'avg_price_per_kg' => $amount,
                'company_location_id' => $grn?->location_id,
                'parent_id' => $grn?->id,
                'parentable_type' => RejectionReturnItem::class,
                'parentable_id' => $item->id,
            ]);
        }
    }

    public function reverseRejectionReturn(RejectionReturn $return): void
    {
        $this->reverse(self::rejectionReturnKey($return));
        $this->clearRejectionReturnStock($return);

        // rows the old per-item hook posted
        if ($return->return_no) {
            Transaction::whereNull('action')
                ->where('transaction_voucher_type_id', self::VT_QC)
                ->where('voucher_no', $return->return_no)
                ->delete();
        }
    }

    private function clearRejectionReturnStock(RejectionReturn $return): void
    {
        $itemIds = $return->items()->pluck('id');
        if ($itemIds->isNotEmpty()) {
            Stock::where('parentable_type', RejectionReturnItem::class)->whereIn('parentable_id', $itemIds)->delete();
        }
    }

    // ------------------------------------------------------------------
    // Validation before approval
    // ------------------------------------------------------------------

    /** Returns an error message when the document cannot be posted, otherwise null. */
    public function validate(callable $build): ?string
    {
        try {
            $build();
            return null;
        } catch (\Throwable $e) {
            return $e->getMessage();
        }
    }

    // ------------------------------------------------------------------
    // Core posting
    // ------------------------------------------------------------------

    /**
     * One balanced pair. Zero amounts are dropped; a missing account throws,
     * so no half-posted voucher can ever be written.
     */
    private function pair(float $amount, ?Account $debit, ?Account $credit, string $purpose, string $remarks): ?array
    {
        $amount = round($amount, 2);
        if ($amount <= 0) {
            return null;
        }
        if (!$debit || !$credit) {
            throw new \RuntimeException("Ledger account missing for '{$purpose}'. Please set up the account and try again.");
        }

        return compact('amount', 'debit', 'credit', 'purpose', 'remarks');
    }

    private function post(string $sourceKey, int $voucherTypeId, string $voucherNo, array $entries, array $common): void
    {
        // Every entry is a debit row and a credit row of the same amount, so the voucher balances by construction.
        $entries = array_values(array_filter($entries));

        DB::transaction(function () use ($sourceKey, $voucherTypeId, $voucherNo, $entries, $common) {
            $this->reverse($sourceKey);

            $base = [
                'action' => $sourceKey,
                'reference_no' => $voucherNo,
                'against_reference_no' => $common['grn_no'] ?? $voucherNo,
                'grn_no' => $common['grn_no'] ?? null,
                'payment_against' => $common['payment_against'] ?? null,
                'voucher_date' => !empty($common['voucher_date']) ? date('Y-m-d', strtotime($common['voucher_date'])) : now()->format('Y-m-d'),
                'company_id' => $common['company_id'] ?? auth()->user()?->current_company_id ?? 1,
                'created_by' => $common['created_by'] ?? auth()->user()?->id ?? 1,
            ];

            foreach ($entries as $e) {
                createTransaction($e['amount'], $e['debit']->id, $voucherTypeId, $voucherNo, 'debit', 'no', $base + [
                    'counter_account_id' => $e['credit']->id,
                    'purpose' => $e['purpose'],
                    'remarks' => $e['remarks'],
                ]);
                createTransaction($e['amount'], $e['credit']->id, $voucherTypeId, $voucherNo, 'credit', 'no', $base + [
                    'counter_account_id' => $e['debit']->id,
                    'purpose' => $e['purpose'],
                    'remarks' => $e['remarks'],
                ]);
            }
        });
    }

    // ------------------------------------------------------------------
    // Accounts
    // ------------------------------------------------------------------

    private function supplierAccount($supplierId): Account
    {
        $supplier = Supplier::find($supplierId);
        $account = $supplier?->account_id ? Account::find($supplier->account_id) : null;
        if (!$account) {
            throw new \RuntimeException('Supplier ' . ($supplier->name ?? "#{$supplierId}") . ' has no ledger account.');
        }

        return $account;
    }

    private function productAccount($productId): Account
    {
        $product = Product::find($productId);
        $account = $product?->account_id ? Account::find($product->account_id) : null;
        if (!$account) {
            throw new \RuntimeException('Item ' . ($product->name ?? "#{$productId}") . ' has no inventory ledger account.');
        }

        return $account;
    }

    private function hierarchyAccount(string $path): Account
    {
        $account = Account::where('hierarchy_path', $path)->first();
        if (!$account) {
            throw new \RuntimeException("Ledger account {$path} not found in the chart of accounts.");
        }

        return $account;
    }

    /** Liability that holds received-but-not-billed goods; created under Liabilities on first use. */
    public function grnClearingAccount($companyId = null): Account
    {
        $account = Account::where('table_name', self::GRN_CLEARING_TABLE)->first();
        if ($account) {
            return $account;
        }

        $parent = $this->hierarchyAccount(self::LIABILITIES_PATH);

        return Account::create([
            'name' => self::GRN_CLEARING_NAME,
            'company_id' => $companyId ?? $parent->company_id ?? 1,
            'account_type' => 'credit',
            'table_name' => self::GRN_CLEARING_TABLE,
            'is_operational' => 'yes',
            'parent_id' => $parent->id,
            'request_account_id' => 0,
        ]);
    }
}
