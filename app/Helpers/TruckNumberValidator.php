<?php

namespace App\Helpers;

use Illuminate\Validation\ValidationException;

class TruckNumberValidator
{
    public static function validate($truckNo, $format)
    {
        if ($format === 0 || empty($truckNo)) {
            return;
        }

        if ($format === 1 && !preg_match('/^[A-Za-z]+-\d+$/', $truckNo)) {
            throw ValidationException::withMessages([
                'truck_no' => ["Truck number must contain alphabets followed by a dash and then numbers (e.g., ABC-123)"],
            ]);
        }
    }
}
