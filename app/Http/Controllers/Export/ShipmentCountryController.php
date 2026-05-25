<?php

namespace App\Http\Controllers\Export;

use App\Http\Controllers\Controller;
use App\Models\Export\ShipmentCountry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class ShipmentCountryController extends Controller
{
    public function __construct()
    {
        $this->middleware('check.company:shipmentcountry-list', ['only' => ['index', 'getTable']]);
        $this->middleware('check.company:shipmentcountry-create', ['only' => ['create', 'store']]);
        $this->middleware('check.company:shipmentcountry-edit', ['only' => ['edit', 'update']]);
        $this->middleware('check.company:shipmentcountry-delete', ['only' => ['destroy']]);
    }

    public function index(Request $request): View
    {
        $countries = ShipmentCountry::orderBy('id', 'ASC')->paginate(10);

        return view('management.export.shipment-country.index', compact('countries'))
            ->with('i', ($request->input('page', 1) - 1) * 10);
    }

    public function create(): View
    {
        return view('management.export.shipment-country.create');
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'status' => 'required|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $shipmentCountry = ShipmentCountry::create($validator->validated());

        return response()->json([
            'success' => 'Shipment Country successfully saved.',
            'data' => $shipmentCountry,
        ]);
    }

    public function show(int $id): View
    {
        $shipmentCountry = ShipmentCountry::findOrFail($id);

        return view('management.export.shipment-country.show', compact('shipmentCountry'));
    }

    public function edit(int $id): View
    {
        $shipmentCountry = ShipmentCountry::findOrFail($id);

        return view('management.export.shipment-country.edit', compact('shipmentCountry'));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $shipmentCountry = ShipmentCountry::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'status' => 'required|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $shipmentCountry->update($validator->validated());

        return response()->json([
            'success' => 'Shipment Country successfully updated.',
            'data' => $shipmentCountry,
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        DB::beginTransaction();

        try {
            $shipmentCountry = ShipmentCountry::find($id);

            if (! $shipmentCountry) {
                return response()->json([
                    'error' => 'Shipment Country not found.',
                ], 404);
            }

            $shipmentCountry->delete();

            DB::commit();

            return response()->json([
                'success' => 'Shipment Country deleted successfully.',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'error' => 'Something went wrong: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function getTable(Request $request): View
    {
        $countries = ShipmentCountry::when($request->filled('search'), function ($q) use ($request) {
            $searchTerm = '%' . $request->search . '%';

            return $q->where('name', 'like', $searchTerm);
        })
            ->latest()
            ->paginate(10);

        return view('management.export.shipment-country.getList', compact('countries'));
    }
}
