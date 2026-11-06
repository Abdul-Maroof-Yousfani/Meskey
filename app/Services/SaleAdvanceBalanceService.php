<?php

namespace App\Services;

use App\Models\Sales\SalesOrder;
use Illuminate\Support\Facades\DB;

/**
 * Single source of truth for the money a customer has against a Sale Order.
 *
 *   net   = received (approved RVs + approved JV credits) - dispatched goods - refunds (DPV) - JV debits
 *   held  = advance still attached to DOs that are open (attached - dispatched - DO refunds - DO JV debits)
 *   free  = net - held
 *
 * A pending voucher already reserves money: pending/approved debits and refunds count, only rejected ones don't.
 * Money coming in (JV credit) only counts once approved.
 *
 * Rules built on these numbers:
 *  - DPV: SO row needs a closed SO; DO row needs a closed DO (or a closed SO). Excess payments are not tied to an order.
 *  - JV debit: allowed on open orders too, but only from free money (or the DO's own remaining advance on a DO line).
 *
 * Exclusions ($x): jv, pv, pv_no, do (ignore that voucher/DO, used while editing it),
 * pending_credits (true also counts unapproved JV credits, used for credit capacity),
 * approved_only (true ignores unapproved debits and refunds, used to judge money that has really gone).
 */
class SaleAdvanceBalanceService
{
    private const EPS = 0.01;

    /**
     * JVs older than this could debit an RV for its excess part (above what is linked to SOs) without touching the
     * customer_advances row. Later JVs use the separate Excess Payment line, so an RV line only means SO money.
     */
    private const RV_LINE_EXCESS_CUTOFF = '2026-10-01';

    public function isSoClosed(int $soId): bool
    {
        $so = SalesOrder::withoutGlobalScopes()->find($soId);

        return $so ? $so->isClosed() : false;
    }

    public function isDoReleased($doId): bool
    {
        $do = DB::table('delivery_order')->where('id', $doId)->first(['do_status', 'so_id']);
        if (!$do) {
            return false;
        }

        return $do->do_status === 'closed' || ($do->so_id && $this->isSoClosed((int) $do->so_id));
    }

    public function summary(int $soId, array $x = []): array
    {
        $soClosed = $this->isSoClosed($soId);
        $excludeDoId = $x['do'] ?? null;

        $dos = DB::table('delivery_order')
            ->where('so_id', $soId)
            ->where('am_approval_status', '!=', 'rejected')
            ->get(['id', 'reference_no', 'do_status']);
        $doIds = $dos->pluck('id')->all();

        // Money in: RVs
        $rvLinks = $this->rvLinksForSo($soId);
        $attachedRows = empty($doIds) ? collect() : DB::table('delivery_order_receipt_voucher as dorv')
            ->join('receipt_vouchers as rv', 'rv.id', '=', 'dorv.receipt_voucher_id')
            ->whereNull('rv.deleted_at')
            ->where('rv.am_approval_status', 'approved')
            ->whereIn('dorv.delivery_order_id', $doIds)
            ->orderBy('dorv.id')
            ->get(['dorv.delivery_order_id', 'dorv.receipt_voucher_id', 'dorv.amount']);

        $received = array_sum($rvLinks);
        foreach ($attachedRows->groupBy('receipt_voucher_id') as $rvId => $rows) {
            if (!isset($rvLinks[$rvId])) {
                $received += (float) $rows->filter(fn ($r) => $r->delivery_order_id != $excludeDoId)->sum('amount');
            }
        }

        // Money in: JV credits
        $creditLines = $this->jvLines($x)
            ->where('d.credit_amount', '>', 0)
            ->when(empty($x['pending_credits']), fn ($q) => $q->where('j.am_approval_status', 'approved'))
            ->where(fn ($q) => $this->whereOrder($q, $soId, $doIds))
            ->get(['d.id', 'd.delivery_order_id', 'd.credit_amount']);
        $received += (float) $creditLines->sum('credit_amount');

        // Per DO figures
        $dispatchedByDo = $this->dispatchedValueByDo($doIds);
        $goodsByDo = empty($doIds) ? [] : DB::table('delivery_order_data')
            ->whereIn('delivery_order_id', $doIds)
            ->groupBy('delivery_order_id')
            ->select('delivery_order_id', DB::raw('SUM(qty * rate) as total'))
            ->pluck('total', 'delivery_order_id')->all();
        $refundedByDo = empty($doIds) ? [] : $this->dpvLines($x)
            ->whereIn('d.delivery_order_id', $doIds)
            ->groupBy('d.delivery_order_id')
            ->select('d.delivery_order_id', DB::raw($this->dpvAmountSql() . ' as total'))
            ->pluck('total', 'delivery_order_id')->all();
        $jvDebitByDo = empty($doIds) ? [] : $this->jvLines($x)
            ->where('d.debit_amount', '>', 0)
            ->whereIn('d.delivery_order_id', $doIds)
            ->groupBy('d.delivery_order_id')
            ->select('d.delivery_order_id', DB::raw('SUM(d.debit_amount) as total'))
            ->pluck('total', 'delivery_order_id')->all();
        $settledByRef = $this->settlementByDoRef($dos->pluck('reference_no')->filter()->all(), $x);

        $doRows = [];
        $held = 0.0;
        foreach ($dos as $do) {
            if ($excludeDoId && $do->id == $excludeDoId) {
                continue;
            }
            $attached = (float) $attachedRows->where('delivery_order_id', $do->id)->sum('amount')
                + (float) ($settledByRef[$do->reference_no] ?? 0)
                + (float) $creditLines->where('delivery_order_id', $do->id)->sum('credit_amount');
            $dispatched = (float) ($dispatchedByDo[$do->id] ?? 0);
            $refunded = (float) ($refundedByDo[$do->id] ?? 0);
            $jvDebit = (float) ($jvDebitByDo[$do->id] ?? 0);
            $attachedRemaining = max(0, $attached - $dispatched - $refunded - $jvDebit);
            // A DO made without selecting an RV still needs the SO's money for its goods
            $goodsRemaining = max(0, (float) ($goodsByDo[$do->id] ?? 0) - $dispatched - $refunded - $jvDebit);
            $remaining = max($attachedRemaining, $goodsRemaining);
            $released = $soClosed || $do->do_status === 'closed';

            $doRows[$do->id] = compact('attached', 'dispatched', 'refunded', 'jvDebit', 'attachedRemaining', 'remaining', 'released');
            $doRows[$do->id]['reference_no'] = $do->reference_no;
            if (!$released) {
                $held += $remaining;
            }
        }

        // Money out
        $refunded = (float) $this->dpvLines($x)
            ->where(fn ($q) => $this->whereOrder($q, $soId, $doIds))
            ->sum(DB::raw($this->dpvAmountSql(false)));

        $creditIds = $creditLines->pluck('id')->all();
        if (!empty($creditIds)) {
            // DPV "Journal Voucher" rows refund a credit counted above but carry no SO/DO on the row
            $refunded += (float) $this->dpvLines($x)
                ->whereNull('d.sales_order_id')
                ->whereNull('d.delivery_order_id')
                ->where(function ($q) use ($creditIds) {
                    $q->whereIn('d.journal_voucher_detail_id', $creditIds)
                        ->orWhere(function ($sq) use ($creditIds) {
                            $sq->where('d.reference_type', 'journal_voucher')->whereIn('d.reference_id', $creditIds);
                        });
                })
                ->sum(DB::raw($this->dpvAmountSql(false)));
        }

        $jvDebits = (float) $this->jvLines($x)
            ->where('d.debit_amount', '>', 0)
            ->where(fn ($q) => $this->whereOrder($q, $soId, $doIds))
            ->sum('d.debit_amount');
        $jvDebits += array_sum($this->rvLevelDebitAttributed($rvLinks, $x));
        if (!empty($creditIds)) {
            // Another JV consuming a credit of this SO through its "Journal Voucher" line
            $jvDebits += (float) $this->jvLines($x)
                ->where('d.debit_amount', '>', 0)
                ->whereNull('d.sales_order_id')
                ->whereNull('d.delivery_order_id')
                ->whereNull('d.receipt_voucher_id')
                ->whereIn('d.reference_jv_detail_id', $creditIds)
                ->sum('d.debit_amount');
        }

        $dispatched = (float) array_sum($dispatchedByDo);
        $net = $received - $dispatched - $refunded - $jvDebits;

        return [
            'so_closed' => $soClosed,
            'received' => round($received, 2),
            'dispatched' => round($dispatched, 2),
            'refunded' => round($refunded, 2),
            'jv_debits' => round($jvDebits, 2),
            'net' => round(max(0, $net), 2),
            'held' => round($held, 2),
            'free' => round(max(0, $net - $held), 2),
            'dos' => $doRows,
        ];
    }

    public function netAdvance(int $soId, array $x = []): float
    {
        return $this->summary($soId, $x)['net'];
    }

    public function freeAdvance(int $soId, array $x = []): float
    {
        return $this->summary($soId, $x)['free'];
    }

    /** Sale Order row of a DPV: only a closed SO can be refunded. */
    public function soRefundable(int $soId, array $x = []): float
    {
        $s = $this->summary($soId, $x);

        return $s['so_closed'] ? $s['free'] : 0.0;
    }

    /** Delivery Order row of a DPV: only a closed DO (or a DO of a closed SO) can be refunded. */
    public function doRefundable($doId, array $x = []): float
    {
        $soId = (int) DB::table('delivery_order')->where('id', $doId)->value('so_id');
        if (!$soId) {
            return 0.0;
        }
        $s = $this->summary($soId, $x);
        $row = $s['dos'][$doId] ?? null;
        if (!$row || !$row['released']) {
            return 0.0;
        }

        return round(min($row['remaining'], $s['free']), 2);
    }

    /** Delivery Order line of a JV: open orders allowed, except an open DO that already has a Loading Program. */
    public function doJvAvailable($doId, array $x = []): float
    {
        $soId = (int) DB::table('delivery_order')->where('id', $doId)->value('so_id');
        if (!$soId) {
            return 0.0;
        }
        $s = $this->summary($soId, $x);
        $row = $s['dos'][$doId] ?? null;
        if (!$row) {
            return 0.0;
        }
        if (!$row['released'] && delivery_order_has_loading_program($doId)) {
            return 0.0;
        }
        $withoutThisHold = $row['released'] ? $s['held'] : $s['held'] - $row['remaining'];
        $available = max(0, $s['net'] - $withoutThisHold);

        return round(min($row['remaining'], $available), 2);
    }

    /**
     * What each approved RV of the SO still has, after DO consumption and JV/DPV adjustments.
     *
     * @return array<int, array{linked: float, do_consumed: float, available: float}>
     */
    public function rvLedger(int $soId, array $x = []): array
    {
        $links = $this->rvLinksForSo($soId);
        $s = $this->summary($soId, $x);
        $doIds = array_keys($s['dos']);

        $ledger = [];
        foreach ($links as $rvId => $linked) {
            $ledger[$rvId] = ['linked' => $linked, 'do_consumed' => 0.0, 'available' => 0.0];
        }

        if (!empty($doIds)) {
            $rows = DB::table('delivery_order_receipt_voucher as dorv')
                ->join('receipt_vouchers as rv', 'rv.id', '=', 'dorv.receipt_voucher_id')
                ->whereNull('rv.deleted_at')
                ->where('rv.am_approval_status', 'approved')
                ->whereIn('dorv.delivery_order_id', $doIds)
                ->orderBy('dorv.id')
                ->get(['dorv.delivery_order_id', 'dorv.receipt_voucher_id', 'dorv.amount']);

            foreach ($rows->groupBy('delivery_order_id') as $doId => $doRvRows) {
                $do = $s['dos'][$doId];
                // An open DO keeps everything attached to it. A closed DO only keeps what it really used.
                $budget = $do['released']
                    ? min((float) $doRvRows->sum('amount'), $do['dispatched'] + $do['refunded'] + $do['jvDebit'])
                    : (float) $doRvRows->sum('amount');
                foreach ($doRvRows as $row) {
                    $used = min((float) $row->amount, $budget);
                    $budget -= $used;
                    if (isset($ledger[$row->receipt_voucher_id])) {
                        $ledger[$row->receipt_voucher_id]['do_consumed'] += $used;
                    }
                }
            }
        }

        $rvDebits = $this->rvLevelDebitAttributed($links, $x);
        foreach ($ledger as $rvId => &$row) {
            $row['available'] = max(0, $row['linked'] - $row['do_consumed'] - ($rvDebits[$rvId] ?? 0));
        }
        unset($row);

        // SO level adjustments don't name an RV: spread them oldest RV first
        $left = $this->soLevelOutflow($soId, $x);
        foreach ($ledger as $rvId => &$row) {
            if ($left <= 0) {
                break;
            }
            $take = min($row['available'], $left);
            $row['available'] -= $take;
            $left -= $take;
        }
        unset($row);

        foreach ($ledger as &$row) {
            $row['available'] = round($row['available'], 2);
        }
        unset($row);

        return $ledger;
    }

    /** Balance of a single RV available for a JV adjustment. */
    public function rvRemaining($rvId, array $x = []): float
    {
        $rv = DB::table('receipt_vouchers')->where('id', $rvId)->first(['id', 'total_amount', 'am_approval_status', 'deleted_at']);
        if (!$rv || $rv->deleted_at || $rv->am_approval_status !== 'approved') {
            return 0.0;
        }

        $items = DB::table('receipt_voucher_items')
            ->where('receipt_voucher_id', $rvId)
            ->whereIn('reference_type', ['sale_order', 'sales_invoice'])
            ->get(['reference_type', 'reference_id', 'net_amount']);
        $base = (float) $items->sum('net_amount');
        if ($base <= 0) {
            $base = (float) $rv->total_amount;
        }

        $attachedTotal = (float) DB::table('delivery_order_receipt_voucher as dorv')
            ->join('delivery_order as do_tbl', 'do_tbl.id', '=', 'dorv.delivery_order_id')
            ->where('do_tbl.am_approval_status', '!=', 'rejected')
            ->where('dorv.receipt_voucher_id', $rvId)
            ->sum('dorv.amount');

        $soIds = $items->where('reference_type', 'sale_order')->pluck('reference_id')->unique();
        if ($soIds->isEmpty()) {
            $directDebit = (float) $this->rvLevelDebitQuery($rvId, $x)->sum('d.debit_amount');

            return round(max(0, $base - $directDebit - $attachedTotal), 2);
        }

        $directDebit = $this->rvDebitOnOrders($rvId, $x);
        $fromOrders = (float) $items->where('reference_type', 'sales_invoice')->sum('net_amount');
        $consumed = 0.0;
        foreach ($soIds as $soId) {
            $entry = $this->rvLedger((int) $soId, $x)[$rvId] ?? null;
            if ($entry) {
                $fromOrders += min($entry['available'], $this->freeAdvance((int) $soId, $x));
                $consumed += $entry['do_consumed'];
            }
        }

        return round(max(0, min($fromOrders, $base - $directDebit - $consumed)), 2);
    }

    /** What a DO being created/edited may take from this RV. */
    public function rvAvailableForDo($rvId, int $soId, $excludeDoId = null): float
    {
        $x = $excludeDoId ? ['do' => $excludeDoId] : [];

        return (float) ($this->rvLedger($soId, $x)[$rvId]['available'] ?? 0);
    }

    /** Room for more advance on the SO. A debit lowers both order value and money, so it doesn't change this. */
    public function soCreditCapacity(int $soId, $excludeJvId = null): float
    {
        $value = (float) DB::table('sales_order_data')->where('sale_order_id', $soId)->sum(DB::raw('qty * rate'));
        $received = $this->summary($soId, ['jv' => $excludeJvId, 'pending_credits' => true])['received'];

        return round(max(0, $value - $received), 2);
    }

    /** Amount that has left the SO through JV/DPV, which takes the same value off the SO's quantity. */
    public function soAdjustedAmount(int $soId): float
    {
        $s = $this->summary($soId);

        return $s['refunded'] + $s['jv_debits'];
    }

    /**
     * Advance missing behind an open DO when more money left the SO than was free.
     * Newest DO loses first since older DOs are the ones already moving.
     */
    public function doMoneyShortfall($doId): float
    {
        $soId = (int) DB::table('delivery_order')->where('id', $doId)->value('so_id');
        if (!$soId) {
            return 0.0;
        }
        if (!$this->soHasOutflow($soId)) {
            return 0.0;
        }

        // Only money that has really gone counts: an unapproved JV must not stop a truck
        $s = $this->summary($soId, ['approved_only' => true]);
        $open = array_filter($s['dos'], fn ($row) => !$row['released']);
        $missing = array_sum(array_column($open, 'attachedRemaining')) - $s['net'];
        if ($missing <= self::EPS) {
            return 0.0;
        }

        krsort($open);
        foreach ($open as $id => $row) {
            $take = min($row['attachedRemaining'], $missing);
            if ($id == $doId) {
                return round($take, 2);
            }
            $missing -= $take;
        }

        return 0.0;
    }

    /**
     * Debit lines of one JV against the shared balances. All lines of the JV are checked together,
     * so one free amount can't be taken once through the SO and again through its RV.
     *
     * @param array<int, array> $lines  keys: debit_amount, sales_order_id, delivery_order_id, receipt_voucher_id, reference_jv_id, reference_jv_detail_id
     */
    public function validateJvDebits(array $lines, $excludeJvId = null): ?string
    {
        $x = ['jv' => $excludeJvId];
        $soDebit = [];
        $doDebit = [];
        $rvDebit = [];

        foreach ($lines as $line) {
            $debit = (float) ($line['debit_amount'] ?? 0);
            if ($debit <= 0) {
                continue;
            }
            if (!empty($line['delivery_order_id'])) {
                $doDebit[$line['delivery_order_id']] = ($doDebit[$line['delivery_order_id']] ?? 0) + $debit;
            } elseif (!empty($line['receipt_voucher_id'])) {
                $rvDebit[$line['receipt_voucher_id']] = ($rvDebit[$line['receipt_voucher_id']] ?? 0) + $debit;
            } elseif (!empty($line['sales_order_id'])) {
                $soDebit[$line['sales_order_id']] = ($soDebit[$line['sales_order_id']] ?? 0) + $debit;
            } elseif (!empty($line['reference_jv_id']) || !empty($line['reference_jv_detail_id'])) {
                // A JV credit tied to an order is that order's advance
                $ref = $this->referencedCreditLine($line);
                if ($ref && $ref->delivery_order_id) {
                    $doDebit[$ref->delivery_order_id] = ($doDebit[$ref->delivery_order_id] ?? 0) + $debit;
                } elseif ($ref && $ref->sales_order_id) {
                    $soDebit[$ref->sales_order_id] = ($soDebit[$ref->sales_order_id] ?? 0) + $debit;
                }
            }
        }

        $freeDraw = [];
        $totalDraw = [];

        foreach ($soDebit as $soId => $amount) {
            $free = $this->freeAdvance((int) $soId, $x);
            if ($amount > $free + self::EPS) {
                return 'The entered amount (' . number_format($amount, 2) . ") for Sales Order {$this->soRef($soId)} exceeds its free advance balance of " . number_format($free, 2) . '.';
            }
            $freeDraw[$soId] = ($freeDraw[$soId] ?? 0) + $amount;
            $totalDraw[$soId] = ($totalDraw[$soId] ?? 0) + $amount;
        }

        foreach ($doDebit as $doId => $amount) {
            $do = DB::table('delivery_order')->where('id', $doId)->first(['id', 'so_id', 'reference_no']);
            if (!$do) {
                continue;
            }
            $released = $this->isDoReleased($doId);
            if (!$released && delivery_order_has_loading_program($doId)) {
                return "Adjustment cannot be made for Delivery Order ({$do->reference_no}) because it is active and has an associated Loading Program. Please close the DO first if you wish to adjust.";
            }
            $available = $this->doJvAvailable($doId, $x);
            if ($amount > $available + self::EPS) {
                return 'The entered amount (' . number_format($amount, 2) . ") for Delivery Order {$do->reference_no} exceeds its remaining advance balance of " . number_format($available, 2) . '.';
            }
            if ($do->so_id) {
                if ($released) {
                    $freeDraw[$do->so_id] = ($freeDraw[$do->so_id] ?? 0) + $amount;
                }
                $totalDraw[$do->so_id] = ($totalDraw[$do->so_id] ?? 0) + $amount;
            }
        }

        foreach ($rvDebit as $rvId => $amount) {
            $available = $this->rvRemaining($rvId, $x);
            if ($amount > $available + self::EPS) {
                $rvNo = DB::table('receipt_vouchers')->where('id', $rvId)->value('unique_no');

                return 'The entered amount (' . number_format($amount, 2) . ") for Receipt Voucher {$rvNo} exceeds its remaining balance of " . number_format($available, 2) . '.';
            }
            $items = DB::table('receipt_voucher_items')
                ->where('receipt_voucher_id', $rvId)
                ->whereIn('reference_type', ['sale_order', 'sales_invoice'])
                ->get(['reference_type', 'reference_id', 'net_amount']);
            $base = (float) $items->sum('net_amount');
            foreach ($items->where('reference_type', 'sale_order')->groupBy('reference_id') as $soId => $soItems) {
                // A part of the RV that belongs to another order isn't drawn from this one
                $share = max(0, $amount - max(0, $base - (float) $soItems->sum('net_amount')));
                $freeDraw[$soId] = ($freeDraw[$soId] ?? 0) + $share;
                $totalDraw[$soId] = ($totalDraw[$soId] ?? 0) + $share;
            }
        }

        foreach ($freeDraw as $soId => $amount) {
            $free = $this->freeAdvance((int) $soId, $x);
            if ($amount > $free + self::EPS) {
                return 'The combined amount (' . number_format($amount, 2) . ") entered against Sales Order {$this->soRef($soId)} and its Receipt Voucher(s) exceeds the free advance balance of " . number_format($free, 2) . '.';
            }
        }

        foreach ($totalDraw as $soId => $amount) {
            $net = $this->netAdvance((int) $soId, $x);
            if ($amount > $net + self::EPS) {
                return 'The total amount (' . number_format($amount, 2) . ") entered against Sales Order {$this->soRef($soId)}, its Delivery Order(s) and Receipt Voucher(s) exceeds the net advance balance of " . number_format($net, 2) . '.';
            }
        }

        return null;
    }

    /** @return array<int, float> receipt_voucher_id => net amount linked to this SO, oldest RV first */
    private function rvLinksForSo(int $soId): array
    {
        return DB::table('receipt_voucher_items as i')
            ->join('receipt_vouchers as rv', 'rv.id', '=', 'i.receipt_voucher_id')
            ->whereNull('rv.deleted_at')
            ->where('rv.am_approval_status', 'approved')
            ->where('i.reference_type', 'sale_order')
            ->where('i.reference_id', $soId)
            ->groupBy('rv.id', 'rv.created_at')
            ->orderBy('rv.created_at')
            ->orderBy('rv.id')
            ->select('rv.id', DB::raw('SUM(i.net_amount) as linked'))
            ->get()
            ->mapWithKeys(fn ($r) => [$r->id => (float) $r->linked])
            ->all();
    }

    /** @return array<int, float> receipt_voucher_id => part of the RV's own JV debits that falls on this SO */
    private function rvLevelDebitAttributed(array $rvLinks, array $x): array
    {
        $result = [];
        foreach ($rvLinks as $rvId => $linked) {
            $debit = $this->rvDebitOnOrders($rvId, $x);
            if ($debit <= 0) {
                continue;
            }
            $allSoLinked = (float) DB::table('receipt_voucher_items')
                ->where('receipt_voucher_id', $rvId)
                ->where('reference_type', 'sale_order')
                ->sum('net_amount');
            $share = max(0, $debit - max(0, $allSoLinked - $linked));
            if ($share > 0) {
                $result[$rvId] = $share;
            }
        }

        return $result;
    }

    /** The RV's JV debits that came out of money linked to orders, leaving out older debits of its excess part. */
    private function rvDebitOnOrders($rvId, array $x): float
    {
        $older = (float) $this->rvLevelDebitQuery($rvId, $x)->where('j.created_at', '<', self::RV_LINE_EXCESS_CUTOFF)->sum('d.debit_amount');
        $newer = (float) $this->rvLevelDebitQuery($rvId, $x)->where('j.created_at', '>=', self::RV_LINE_EXCESS_CUTOFF)->sum('d.debit_amount');
        if ($older <= 0) {
            return $newer;
        }

        $total = (float) DB::table('receipt_vouchers')->where('id', $rvId)->value('total_amount');
        $linked = (float) DB::table('receipt_voucher_items')->where('receipt_voucher_id', $rvId)->where('reference_type', 'sale_order')->sum('net_amount');

        return max(0, $older - max(0, $total - $linked)) + $newer;
    }

    private function soHasOutflow(int $soId): bool
    {
        $doIds = DB::table('delivery_order')->where('so_id', $soId)->pluck('id')->all();
        $rvIds = array_keys($this->rvLinksForSo($soId));

        return $this->jvLines([])
            ->where('d.debit_amount', '>', 0)
            ->where(function ($q) use ($soId, $doIds, $rvIds) {
                $this->whereOrder($q, $soId, $doIds);
                if (!empty($rvIds)) {
                    $q->orWhereIn('d.receipt_voucher_id', $rvIds);
                }
            })
            ->exists()
            || $this->dpvLines([])->where(fn ($q) => $this->whereOrder($q, $soId, $doIds))->exists()
            || $this->jvLines([])->where('d.credit_amount', '>', 0)->where(fn ($q) => $this->whereOrder($q, $soId, $doIds))->exists();
    }

    private function rvLevelDebitQuery($rvId, array $x)
    {
        return $this->jvLines($x)
            ->where('d.debit_amount', '>', 0)
            ->where('d.receipt_voucher_id', $rvId)
            ->whereNull('d.sales_order_id')
            ->whereNull('d.delivery_order_id');
    }

    /** Debits and refunds on the SO that name neither an RV nor a DO (plus those of a DO left out of the calculation). */
    private function soLevelOutflow(int $soId, array $x): float
    {
        $total = (float) $this->jvLines($x)
            ->where('d.debit_amount', '>', 0)
            ->where('d.sales_order_id', $soId)
            ->whereNull('d.delivery_order_id')
            ->whereNull('d.receipt_voucher_id')
            ->sum('d.debit_amount');

        $total += (float) $this->dpvLines($x)
            ->where('d.sales_order_id', $soId)
            ->whereNull('d.delivery_order_id')
            ->sum(DB::raw($this->dpvAmountSql(false)));

        if (!empty($x['do'])) {
            $total += (float) $this->jvLines($x)->where('d.debit_amount', '>', 0)->where('d.delivery_order_id', $x['do'])->sum('d.debit_amount');
            $total += (float) $this->dpvLines($x)->where('d.delivery_order_id', $x['do'])->sum(DB::raw($this->dpvAmountSql(false)));
        }

        return $total;
    }

    /** @return array<int, float> delivery_order_id => value of goods dispatched */
    private function dispatchedValueByDo(array $doIds): array
    {
        if (empty($doIds)) {
            return [];
        }

        $result = array_fill_keys($doIds, 0.0);
        $doData = DB::table('delivery_order_data')->whereIn('delivery_order_id', $doIds)->get(['id', 'delivery_order_id', 'rate']);

        $dcQty = DB::table('delivery_challan_data as dcd')
            ->join('delivery_challans as dc', 'dc.id', '=', 'dcd.delivery_challan_id')
            ->where('dc.am_approval_status', '!=', 'rejected')
            ->whereIn('dcd.do_data_id', $doData->pluck('id')->all())
            ->groupBy('dcd.do_data_id')
            ->select('dcd.do_data_id', DB::raw('SUM(dcd.qty) as qty'))
            ->pluck('qty', 'do_data_id');

        foreach ($doData as $row) {
            $result[$row->delivery_order_id] += (float) ($dcQty[$row->id] ?? 0) * (float) $row->rate;
        }

        // Older challans only carry the qty on the pivot
        foreach ($doIds as $doId) {
            if ($result[$doId] > 0) {
                continue;
            }
            $pivotQty = (float) DB::table('delivery_challan_delivery_order as p')
                ->join('delivery_challans as dc', 'dc.id', '=', 'p.delivery_challan_id')
                ->where('dc.am_approval_status', '!=', 'rejected')
                ->where('p.delivery_order_id', $doId)
                ->sum('p.qty');
            if ($pivotQty > 0) {
                $result[$doId] = $pivotQty * (float) ($doData->firstWhere('delivery_order_id', $doId)->rate ?? 0);
            }
        }

        // A truck past Second Weighbridge goes even if its DO is closed, so its money is used
        foreach ($this->weighedQtyWithoutChallan($doIds) as $doId => $qty) {
            $result[$doId] += $qty * (float) ($doData->firstWhere('delivery_order_id', $doId)->rate ?? 0);
        }

        return $result;
    }

    /**
     * Qty weighed at Second Weighbridge whose truck has no pending/approved Delivery Challan yet.
     *
     * @return array<int, float> delivery_order_id => qty
     */
    public function weighedQtyWithoutChallan(array $doIds): array
    {
        if (empty($doIds)) {
            return [];
        }

        $noChallan = function ($q) {
            $q->select(DB::raw(1))
                ->from('delivery_challan_data as x')
                ->join('delivery_challans as c', 'c.id', '=', 'x.delivery_challan_id')
                ->whereColumn('x.ticket_id', 's.loading_program_item_id')
                ->where('c.am_approval_status', '!=', 'rejected');
        };

        $result = DB::table('sales_second_weighbridge_items as i')
            ->join('sales_second_weighbridges as w', 'w.id', '=', 'i.second_weighbridge_id')
            ->join('loading_slips as s', 's.id', '=', 'w.loading_slip_id')
            ->whereIn('i.delivery_order_id', $doIds)
            ->whereNotExists($noChallan)
            ->groupBy('i.delivery_order_id')
            ->select('i.delivery_order_id', DB::raw('SUM(i.net_weight) as qty'))
            ->pluck('qty', 'delivery_order_id')
            ->map(fn ($qty) => (float) $qty)
            ->all();

        // Older weighbridges have no DO split: the whole weight is on their own DO
        $unsplit = DB::table('sales_second_weighbridges as w')
            ->join('loading_slips as s', 's.id', '=', 'w.loading_slip_id')
            ->whereIn('w.delivery_order_id', $doIds)
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))->from('sales_second_weighbridge_items as i')->whereColumn('i.second_weighbridge_id', 'w.id');
            })
            ->whereNotExists($noChallan)
            ->groupBy('w.delivery_order_id')
            ->select('w.delivery_order_id', DB::raw('SUM(w.net_weight) as qty'))
            ->pluck('qty', 'delivery_order_id');

        foreach ($unsplit as $doId => $qty) {
            $result[$doId] = ($result[$doId] ?? 0) + (float) $qty;
        }

        return $result;
    }

    /** @return array<string, float> DO reference_no => JV credit consumed by that DO */
    private function settlementByDoRef(array $refs, array $x): array
    {
        if (empty($refs)) {
            return [];
        }

        return DB::table('settlement_adjustments')
            ->where('reference_type', 'journal_voucher')
            ->whereIn('voucher_no', $refs)
            ->when(!empty($x['jv']), fn ($q) => $q->where('reference_id', '!=', $x['jv']))
            ->groupBy('voucher_no')
            ->select('voucher_no', DB::raw('SUM(amount) as total'))
            ->pluck('total', 'voucher_no')
            ->all();
    }

    private function referencedCreditLine(array $line)
    {
        $query = DB::table('journal_voucher_details')->whereNull('deleted_at')->where('credit_amount', '>', 0);
        if (!empty($line['reference_jv_detail_id'])) {
            $query->where('id', $line['reference_jv_detail_id']);
        } else {
            $query->where('journal_voucher_id', $line['reference_jv_id']);
        }

        return $query->first(['sales_order_id', 'delivery_order_id']);
    }

    private function whereOrder($query, int $soId, array $doIds): void
    {
        $query->where('d.sales_order_id', $soId);
        if (!empty($doIds)) {
            $query->orWhereIn('d.delivery_order_id', $doIds);
        }
    }

    private function jvLines(array $x)
    {
        return DB::table('journal_voucher_details as d')
            ->join('journal_vouchers as j', 'j.id', '=', 'd.journal_voucher_id')
            ->whereNull('d.deleted_at')
            ->whereNull('j.deleted_at')
            ->where(fn ($q) => $q->whereNull('j.am_approval_status')->orWhere('j.am_approval_status', '!=', 'rejected'))
            ->when(!empty($x['approved_only']), fn ($q) => $q->where('j.am_approval_status', 'approved'))
            ->when(!empty($x['jv']), fn ($q) => $q->where('j.id', '!=', $x['jv']));
    }

    private function dpvLines(array $x)
    {
        return DB::table('payment_voucher_data as d')
            ->join('payment_vouchers as p', 'p.id', '=', 'd.payment_voucher_id')
            ->whereNull('d.deleted_at')
            ->whereNull('p.deleted_at')
            ->where(fn ($q) => $q->whereNull('p.am_approval_status')->orWhere('p.am_approval_status', '!=', 'rejected'))
            ->when(!empty($x['approved_only']), fn ($q) => $q->where('p.am_approval_status', 'approved'))
            ->when(!empty($x['pv']), fn ($q) => $q->where('p.id', '!=', $x['pv']))
            ->when(!empty($x['pv_no']), fn ($q) => $q->where('p.unique_no', '!=', $x['pv_no']));
    }

    private function dpvAmountSql(bool $sum = true): string
    {
        $amount = 'COALESCE(NULLIF(d.net_amount, 0), d.amount)';

        return $sum ? "SUM($amount)" : $amount;
    }

    private function soRef($soId): string
    {
        return DB::table('sales_orders')->where('id', $soId)->value('reference_no') ?: "SO #{$soId}";
    }
}
