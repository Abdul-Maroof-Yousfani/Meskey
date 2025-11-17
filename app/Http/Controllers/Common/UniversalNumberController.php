<?php

namespace App\Http\Controllers\Common;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UniversalNumberController extends Controller
{
    public function generate(Request $request)
    {
        $validated = $request->validate([
            'table'        => 'required|string',
            'prefix'       => 'nullable|string',
            'location'     => 'nullable|string',
            'with_date'    => 'nullable|boolean',
            'column'       => 'nullable|string',
            'pad'          => 'nullable|integer',
            'company_id'   => 'nullable|integer',
            'custom_date'   => 'nullable|string',
            'date_format'   => 'nullable|string',
            'serial_at_end'   => 'nullable|boolean',
        ]);

        $uniqueNo = generateUniversalUniqueNo(
            $validated['table'],
            [
                'prefix'     => $validated['prefix'] ?? null,
                'location'   => $validated['location'] ?? null,
                'with_date'  => $validated['with_date'] ?? false,
                'column'     => $validated['column'] ?? 'unique_no',
                'pad'        => $validated['pad'] ?? 3,
                'company_id' => $validated['company_id'] ?? null,
                'date_format' => $validated['date_format'] ?? null,
                'serial_at_end' => $validated['serial_at_end'] ?? false,
                'custom_date' => $validated['custom_date'] ?? null,
            ]
        );

        return response()->json([
            'unique_no' => $uniqueNo
        ]);
    }
}
