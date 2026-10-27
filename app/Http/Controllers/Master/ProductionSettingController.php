<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ProductionSettingController extends Controller
{
    /**
     * Display the production settings dashboard / tabs.
     */
    public function index(Request $request)
    {
        $fumigationTypes = Setting::getFumigationTypes();
        return view('management.master.production_settings.index', compact('fumigationTypes'));
    }

    /**
     * Get the HTML table / list for fumigation types (for AJAX refreshes).
     */
    public function getFumigationTypesList(Request $request)
    {
        $fumigationTypes = Setting::getFumigationTypes();
        return view('management.master.production_settings.partials.fumigation_types_table', compact('fumigationTypes'));
    }

    /**
     * Show modal to create a new fumigation type.
     */
    public function createFumigationType()
    {
        return view('management.master.production_settings.modals.add_fumigation_type');
    }

    /**
     * Store a newly created fumigation type.
     */
    public function storeFumigationType(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:100',
        ]);

        $name = trim($request->name);
        $types = Setting::getFumigationTypes();

        foreach ($types as $existing) {
            if (strcasecmp($existing, $name) === 0) {
                return response()->json([
                    'catchError' => 'The fumigation type "' . $name . '" already exists.'
                ], 422);
            }
        }

        $types[] = $name;
        Setting::setFumigationTypes($types);

        return response()->json([
            'success' => 'Fumigation type added successfully.',
            'data' => [
                'name' => $name,
                'types' => $types
            ]
        ], 201);
    }

    /**
     * Show modal to edit an existing fumigation type.
     */
    public function editFumigationType($index)
    {
        $types = Setting::getFumigationTypes();
        $index = (int) $index;

        if (!isset($types[$index])) {
            abort(404, 'Fumigation type not found.');
        }

        $type = $types[$index];
        return view('management.master.production_settings.modals.edit_fumigation_type', compact('type', 'index'));
    }

    /**
     * Update an existing fumigation type.
     */
    public function updateFumigationType(Request $request, $index): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:100',
        ]);

        $index = (int) $index;
        $name = trim($request->name);
        $types = Setting::getFumigationTypes();

        if (!isset($types[$index])) {
            return response()->json([
                'catchError' => 'Fumigation type not found.'
            ], 404);
        }

        foreach ($types as $idx => $existing) {
            if ($idx !== $index && strcasecmp($existing, $name) === 0) {
                return response()->json([
                    'catchError' => 'Another fumigation type with the name "' . $name . '" already exists.'
                ], 422);
            }
        }

        $types[$index] = $name;
        Setting::setFumigationTypes($types);

        return response()->json([
            'success' => 'Fumigation type updated successfully.',
            'data' => [
                'name' => $name,
                'types' => $types
            ]
        ], 200);
    }

    /**
     * Delete a fumigation type.
     */
    public function destroyFumigationType($index): JsonResponse
    {
        $index = (int) $index;
        $types = Setting::getFumigationTypes();

        if (!isset($types[$index])) {
            return response()->json([
                'catchError' => 'Fumigation type not found.'
            ], 404);
        }

        $deletedName = $types[$index];
        unset($types[$index]);
        $types = array_values($types);

        Setting::setFumigationTypes($types);

        return response()->json([
            'success' => 'Fumigation type "' . $deletedName . '" deleted successfully.',
            'data' => [
                'types' => $types
            ]
        ], 200);
    }
}
