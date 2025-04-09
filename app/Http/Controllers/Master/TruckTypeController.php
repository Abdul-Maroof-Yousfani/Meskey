<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Master\ArrivalTruckType;
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
        return view('management.master.truck_type.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(TruckTypeRequest $request)
    {
        $data = $request->validated();
        $request = $request->all();

        $request['unique_no'] = generateUniqueNumber(null, 'brokers', null, 'unique_no');
        $ArrivalTruckType = ArrivalTruckType::create($request);

        return response()->json(['success' => 'Station created successfully.', 'data' => $ArrivalTruckType], 201);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $ArrivalTruckType = ArrivalTruckType::findOrFail($id);
        return view('management.master.truck_type.edit', compact('ArrivalTruckType'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(TruckTypeRequest $request, $id)
    {
        $data = $request->validated();
        $broker = ArrivalTruckType::findOrFail($id);
        $broker->update($request->all());

        return response()->json(['success' => 'Truck Type updated successfully.', 'data' => $broker], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $broker = ArrivalTruckType::findOrFail($id);

        $broker->delete();
        return response()->json(['success' => 'Station deleted successfully.'], 200);
    }
}
