<?php


namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Master\Station;
use App\Http\Requests\Master\StationRequest;
use Illuminate\Http\Request;

class StationController extends Controller
{
   /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('management.master.station.index');
    }

    /**
     * Get list of categories.
     */
    public function getList(Request $request)
    {
        $brokers = Station::when($request->filled('search'), function ($q) use ($request) {
            $searchTerm = '%' . $request->search . '%';
            return $q->where(function ($sq) use ($searchTerm) {
                $sq->where('name', 'like', $searchTerm);
            });
        })
            ->latest()
            ->paginate(request('per_page', 25));

        return view('management.master.station.getList', compact('brokers'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('management.master.station.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StationRequest $request)
    {
        $data = $request->validated();
        $request = $request->all();

        $request['unique_no'] = generateUniqueNumber(null, 'brokers', null, 'unique_no');
        $broker = Station::create($request);

        return response()->json(['success' => 'Station created successfully.', 'data' => $broker], 201);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $broker = Broker::findOrFail($id);
        return view('management.master.station.edit', compact('broker'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(StationRequest $request, $id)
    {
        $data = $request->validated();
        $broker = Station::findOrFail($id);
        $broker->update($data);

        return response()->json(['success' => 'Station updated successfully.', 'data' => $broker], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $broker = Station::findOrFail($id);

        $broker->delete();
        return response()->json(['success' => 'Station deleted successfully.'], 200);
    }
}
