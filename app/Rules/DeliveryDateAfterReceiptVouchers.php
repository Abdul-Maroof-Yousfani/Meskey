<?php

namespace App\Rules;

use App\Models\ReceiptVoucher;
use App\Models\ReceiptVoucherAdvance;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Carbon\Carbon;

class DeliveryDateAfterReceiptVouchers implements ValidationRule
{
    protected $receiptVouchers;

    public function __construct($receiptVouchers)
    {
        $this->receiptVouchers = $receiptVouchers;
    }

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (empty($this->receiptVouchers) || !is_array($this->receiptVouchers)) {
            return;
        }

        $maxDate = null;

        foreach ($this->receiptVouchers as $rv_val) {
            $rv_date = null;
            if (str_starts_with($rv_val, 'adv_')) {
                $adv_id = str_replace('adv_', '', $rv_val);
                $adv = ReceiptVoucherAdvance::with('receiptVoucher')->find($adv_id);
                if ($adv && $adv->receiptVoucher) {
                    $rv_date = $adv->receiptVoucher->rv_date;
                }
            } else {
                $rv_id = str_replace('rv_', '', $rv_val);
                $rv = ReceiptVoucher::find($rv_id);
                if ($rv) {
                    $rv_date = $rv->rv_date;
                }
            }

            if ($rv_date) {
                $rv_date = Carbon::parse($rv_date);
                if (!$maxDate || $rv_date->gt($maxDate)) {
                    $maxDate = $rv_date;
                }
            }
        }

        if ($maxDate && Carbon::parse($value)->lt($maxDate->startOfDay())) {
            $fail("The delivery date must be greater than or equal to the latest receipt voucher date (" . $maxDate->format('Y-m-d') . ").");
        }
    }
}
