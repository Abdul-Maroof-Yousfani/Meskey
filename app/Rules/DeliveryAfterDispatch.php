<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class DeliveryAfterDispatch implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */

    protected string $deliveryDate;
    protected string $dispatchDate;
    public function __construct(?string $deliveryDate, ?string $dispatchDate) {
        $this->deliveryDate = $deliveryDate;
        $this->dispatchDate = $dispatchDate;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if(!$this->dispatchDate && !$this->deliveryDate) return;
        if(strtotime($this->dispatchDate) > strtotime($this->deliveryDate)) {
            $fail("Expired, DO date can not be greater than delivery date");
        }
    }
}
