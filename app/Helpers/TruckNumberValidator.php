<?php

namespace App\Helpers;

use Illuminate\Validation\ValidationException;

class TruckNumberValidator
{
    public static function validate($truckNo, $format)
    {
        if (!$format || !$truckNo) return;

        $formatPatterns = [
            'ABC-1234' => '/^[A-Z]{3}-\d{4}$/',
            '1234-ABC' => '/^\d{4}-[A-Z]{3}$/',
            'AB-1234' => '/^[A-Z]{2}-\d{4}$/',
            '1234-AB' => '/^\d{4}-[A-Z]{2}$/',
        ];

        if (isset($formatPatterns[$format]) && !preg_match($formatPatterns[$format], $truckNo)) {
            throw ValidationException::withMessages([
                'truck_no' => ["Truck number must be in the format: $format"],
            ]);
        }
    }
}
