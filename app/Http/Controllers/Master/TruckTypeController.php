<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Master\ArrivalTruckType;
use App\Models\Master\CompanyLocation;
use Illuminate\Http\Request;
use App\Http\Requests\Master\TruckTypeRequest;

class TruckTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('management.master.truck_type.index');
    }

    /**
     * Get list of categories.
     */
    public function getList(Request $request)
    {
        $trucktypes = ArrivalTruckType::when($request->filled('search'), function ($q) use ($request) {
            $searchTerm = '%' . $request->search . '%';
            return $q->where(function ($sq) use ($searchTerm) {
                $sq->where('name', 'like', $searchTerm);
            });
        })
            ->latest()
            ->paginate(request('per_page', 25));

        return view('management.master.truck_type.getList', compact('trucktypes'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $locations = CompanyLocation::all();
        return view('management.master.truck_type.create', compact('locations'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(TruckTypeRequest $request)
    {
        $data = $request->all();
        $data['weighbridge_amount'] = $data['weighbridge_amount'] ?: 0;
        $data['sample_money'] = $data['sample_money'] ?: 0;

        $ArrivalTruckType = ArrivalTruckType::create($data);

        if ($request->has('location_amounts')) {
            foreach ($request->location_amounts as $location_id => $amount) {
                if ($amount !== null && $amount !== '' && $amount > 0) {
                    $ArrivalTruckType->locationAmounts()->attach($location_id, ['amount' => $amount]);
                }
            }
        }

        return response()->json(['success' => 'Truck Type created successfully.', 'data' => $ArrivalTruckType], 201);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $ArrivalTruckType = ArrivalTruckType::with('locationAmounts')->findOrFail($id);
        $locations = CompanyLocation::all();
        return view('management.master.truck_type.edit', compact('ArrivalTruckType', 'locations'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(TruckTypeRequest $request, $id)
    {
        $ArrivalTruckType = ArrivalTruckType::findOrFail($id);
        
        $data = $request->all();
        $data['weighbridge_amount'] = $data['weighbridge_amount'] ?: 0;
        $data['sample_money'] = $data['sample_money'] ?: 0;
        
        $ArrivalTruckType->update($data);

        if ($request->has('location_amounts')) {
            $syncData = [];
            foreach ($request->location_amounts as $location_id => $amount) {
                if ($amount !== null && $amount !== '' && $amount > 0) {
                    $syncData[$location_id] = ['amount' => $amount];
                }
            }
            $ArrivalTruckType->locationAmounts()->sync($syncData);
        }

        return response()->json(['success' => 'Truck Type updated successfully.', 'data' => $ArrivalTruckType], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $ArrivalTruckType = ArrivalTruckType::findOrFail($id);
        $ArrivalTruckType->delete();
        return response()->json(['success' => 'Truck Type deleted successfully.'], 200);
    }
}
