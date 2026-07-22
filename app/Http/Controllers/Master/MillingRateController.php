<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Master\MillingRate;
use App\Models\Master\CompanyLocation;
use App\Models\Master\ArrivalLocation;
use App\Models\Master\Plant;
use App\Models\Master\Variable;

class MillingRateController extends Controller
{
    public function index()
    {
        return view('management.master.milling_rate.index');
    }

    public function getList(Request $request)
    {
        $milling_rates = MillingRate::with(['location', 'subLocation', 'plant'])
            ->when($request->filled('search'), function ($q) use ($request) {
                $searchTerm = '%' . $request->search . '%';
                return $q->where(function ($sq) use ($searchTerm) {
                    $sq->where('title', 'like', $searchTerm);
                });
            })
            ->where('company_id', $request->company_id)
            ->latest()
            ->paginate(request('per_page', 25));

        return view('management.master.milling_rate.getList', compact('milling_rates'));
    }

    public function create()
    {
        $companyLocations = CompanyLocation::where('status', 'active')->get();
        $variables = Variable::where('status', 1)->get();
        return view('management.master.milling_rate.create', compact('companyLocations', 'variables'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'location_id' => 'required|integer',
            'sublocation_id' => 'required|integer',
            'plant_id' => 'required|integer',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'nullable|integer',
            'variables' => 'nullable|array',
            'company_id' => 'nullable|integer'
        ]);

        if (!isset($data['company_id'])) {
            $data['company_id'] = auth()->check() ? auth()->user()->current_company_id : null;
        }
        $data['status'] = $data['status'] ?? 1;

        $millingRate = MillingRate::create($data);

        if (isset($data['variables']) && is_array($data['variables'])) {
            $syncData = [];
            foreach ($data['variables'] as $variableId => $value) {
                if ($value !== null && $value !== '') {
                    $syncData[$variableId] = ['value' => $value];
                }
            }
            $millingRate->variables()->sync($syncData);
        }

        return response()->json(['success' => 'Milling Rate created successfully.', 'data' => $millingRate], 201);
    }

    public function edit(int $id)
    {
        $millingRate = MillingRate::with('variables')->findOrFail($id);
        $companyLocations = CompanyLocation::where('status', 'active')->get();
        $arrivalLocations = ArrivalLocation::where('company_location_id', $millingRate->location_id)->where('status', 'active')->get();
        $plants = Plant::where('arrival_location_id', $millingRate->sublocation_id)->where('status', 'active')->get();
        $variables = Variable::where('status', 1)->get();

        return view('management.master.milling_rate.edit', compact('millingRate', 'companyLocations', 'arrivalLocations', 'plants', 'variables'));
    }

    public function update(Request $request, int $id)
    {
        $data = $request->validate([
            'location_id' => 'required|integer',
            'sublocation_id' => 'required|integer',
            'plant_id' => 'required|integer',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'nullable|integer',
            'variables' => 'nullable|array'
        ]);

        $millingRate = MillingRate::findOrFail($id);
        $data['status'] = $data['status'] ?? 1;
        $millingRate->update($data);

        if (isset($data['variables']) && is_array($data['variables'])) {
            $syncData = [];
            foreach ($data['variables'] as $variableId => $value) {
                if ($value !== null && $value !== '') {
                    $syncData[$variableId] = ['value' => $value];
                }
            }
            $millingRate->variables()->sync($syncData);
        } else {
            $millingRate->variables()->sync([]);
        }

        return response()->json(['success' => 'Milling Rate updated successfully.', 'data' => $millingRate], 200);
    }

    public function show(int $id)
    {
        $millingRate = MillingRate::with(['location', 'subLocation', 'plant', 'variables'])->findOrFail($id);
        return view('management.master.milling_rate.show', compact('millingRate'));
    }

    public function getSubLocations($locationId)
    {
        $arrivalLocations = ArrivalLocation::where('company_location_id', $locationId)->where('status', 'active')->get();
        return response()->json($arrivalLocations);
    }

    public function getPlants($subLocationId)
    {
        $plants = Plant::where('arrival_location_id', $subLocationId)->where('status', 'active')->get();
        return response()->json($plants);
    }
}
