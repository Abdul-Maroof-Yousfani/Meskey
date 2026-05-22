<?php

namespace App\Http\Controllers\Export;

use App\Http\Controllers\Controller;
use App\Models\Export\ShipmentCompany;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class ShipmentCompanyController extends Controller
{
    public function __construct()
    {
        $this->middleware('check.company:shipmentcompany-list', ['only' => ['index', 'getTable']]);
        $this->middleware('check.company:shipmentcompany-create', ['only' => ['create', 'store']]);
        $this->middleware('check.company:shipmentcompany-edit', ['only' => ['edit', 'update']]);
        $this->middleware('check.company:shipmentcompany-delete', ['only' => ['destroy']]);
    }

    public function index(Request $request): View
    {
        $companies = ShipmentCompany::orderBy('id', 'ASC')->paginate(10);

        return view('management.export.shipment-company.index', compact('companies'))
            ->with('i', ($request->input('page', 1) - 1) * 10);
    }

    public function create(): View
    {
        return view('management.export.shipment-company.create');
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

        $shipmentCompany = ShipmentCompany::create($validator->validated());

        return response()->json([
            'success' => 'Shipment Company successfully saved.',
            'data' => $shipmentCompany,
        ]);
    }

    public function show(int $id): View
    {
        $shipmentCompany = ShipmentCompany::findOrFail($id);

        return view('management.export.shipment-company.show', compact('shipmentCompany'));
    }

    public function edit(int $id): View
    {
        $shipmentCompany = ShipmentCompany::findOrFail($id);

        return view('management.export.shipment-company.edit', compact('shipmentCompany'));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $shipmentCompany = ShipmentCompany::findOrFail($id);

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

        $shipmentCompany->update($validator->validated());

        return response()->json([
            'success' => 'Shipment Company successfully updated.',
            'data' => $shipmentCompany,
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        DB::beginTransaction();

        try {
            $shipmentCompany = ShipmentCompany::find($id);

            if (! $shipmentCompany) {
                return response()->json([
                    'error' => 'Shipment Company not found.',
                ], 404);
            }

            $shipmentCompany->delete();

            DB::commit();

            return response()->json([
                'success' => 'Shipment Company deleted successfully.',
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
        $companies = ShipmentCompany::when($request->filled('search'), function ($q) use ($request) {
            $searchTerm = '%' . $request->search . '%';

            return $q->where('name', 'like', $searchTerm);
        })
            ->latest()
            ->paginate(10);

        return view('management.export.shipment-company.getList', compact('companies'));
    }
}
