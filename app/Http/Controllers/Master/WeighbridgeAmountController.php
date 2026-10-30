<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Master\WeighbridgeAmount;
use App\Models\Master\CompanyLocation;
use App\Models\Master\ArrivalTruckType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class WeighbridgeAmountController extends Controller
{
    static $i = 0;
    function __construct()
    {
        // $this->middleware('check.company:master-weighbridge-amount', ['only' => ['index']]);
        // $this->middleware('check.company:master-weighbridge-amount', ['only' => ['edit']]);
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $data = [
            'CompanyLocations' => CompanyLocation::where('status', 'active')->get(),
            'TruckTypes' => ArrivalTruckType::where('status', 'active')->get()
        ];

        return view('management.master.weighbridge-amount.index', $data);
    }

    /**
     * Get list of weighbridge amounts.
     */
    public function getList(Request $request)
    {
      
        $WeighbridgeAmounts = WeighbridgeAmount::with(['companyLocation', 'truckType'])
            ->when($request->filled('company_location_id'), function ($q) use ($request) {
                return $q->where('company_location_id', $request->company_location_id);
            })
            ->when($request->filled('truck_type_id'), function ($q) use ($request) {
                return $q->where('truck_type_id', $request->truck_type_id);
            })
            ->when($request->filled('search'), function ($q) use ($request) {
                $searchTerm = '%' . $request->search . '%';
                return $q->where(function ($sq) use ($searchTerm) {
                    $sq->whereHas('companyLocation', function ($query) use ($searchTerm) {
                        $query->where('name', 'like', $searchTerm);
                    })->orWhereHas('truckType', function ($query) use ($searchTerm) {
                        $query->where('name', 'like', $searchTerm);
                    })->orWhere('weighbridge_amount', 'like', $searchTerm);
                });
            })
            ->latest()
            ->paginate($request->get('per_page', 25));

        return view('management.master.weighbridge-amount.getList', compact('WeighbridgeAmounts'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        
        $data = [
            'CompanyLocations' => CompanyLocation::where('status', 'active')->get(),
            'TruckTypes' => ArrivalTruckType::where('status', 'active')->get(),
            'UsedTruckTypes' => $this->getUsedTruckTypesByLocation()
        ];

        return view('management.master.weighbridge-amount.create', $data);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'company_location_id' => 'required|exists:company_locations,id',
            'truck_type_id' => [
                'required',
                'exists:arrival_truck_types,id',
                Rule::unique('weighbridge_amounts')->where('company_location_id', $request->company_location_id)
            ],
            'weighbridge_amount' => 'required|numeric|min:0',
            'description' => 'nullable|string|max:1000'
        ], [
            'truck_type_id.unique' => 'Weighbridge amount for this truck type already exists on the selected location.'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $request['created_by'] = auth()->user()->id;
        $request['company_id'] = $request->company_id;

        $weighbridgeAmount = WeighbridgeAmount::create($request->all());

        return response()->json(['success' => 'Weighbridge Amount created successfully.', 'data' => $weighbridgeAmount], 201);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $data['WeighbridgeAmount'] = WeighbridgeAmount::findOrFail($id);
        $data['CompanyLocations'] = CompanyLocation::where('status', 'active')->get();
        $data['TruckTypes'] = ArrivalTruckType::where('status', 'active')->get();
        $data['UsedTruckTypes'] = $this->getUsedTruckTypesByLocation($id);

        return view('management.master.weighbridge-amount.edit', $data);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'company_location_id' => 'required|exists:company_locations,id',
            'truck_type_id' => [
                'required',
                'exists:arrival_truck_types,id',
                Rule::unique('weighbridge_amounts')->where('company_location_id', $request->company_location_id)->ignore($id)
            ],
            'weighbridge_amount' => 'required|numeric|min:0',
            'description' => 'nullable|string|max:1000'
        ], [
            'truck_type_id.unique' => 'Weighbridge amount for this truck type already exists on the selected location.'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $weighbridgeAmount = WeighbridgeAmount::findOrFail($id);

        $weighbridgeAmount->update($request->all());

        return response()->json(['success' => 'Weighbridge Amount updated successfully.', 'data' => $weighbridgeAmount], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $weighbridgeAmount = WeighbridgeAmount::findOrFail($id);
        $weighbridgeAmount->delete();
        return response()->json(['success' => 'Weighbridge Amount deleted successfully.'], 200);
    }

    /**
     * Truck type ids already assigned per location, optionally excluding a record.
     */
    private function getUsedTruckTypesByLocation($exceptId = null)
    {
        return WeighbridgeAmount::when($exceptId, fn($q) => $q->where('id', '!=', $exceptId))
            ->get(['company_location_id', 'truck_type_id'])
            ->groupBy('company_location_id')
            ->map(fn($rows) => $rows->pluck('truck_type_id')->unique()->values());
    }
}
