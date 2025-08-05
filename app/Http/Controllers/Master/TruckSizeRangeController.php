<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\TruckSizeRange;
use Illuminate\Http\Request;

class TruckSizeRangeController extends Controller
{
    function __construct()
    {
        $this->middleware('check.company:truck-size-range', ['only' => ['index', 'edit', 'getList', 'store', 'update']]);
    }
    public function index(Request $request)
    {
        return view('management.master.truck-size-ranges.index',);
    }

    public function create()
    {
        return view('management.master.truck-size-ranges.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'min_number' => 'required|numeric|min:0',
            'max_number' => 'required|numeric|gt:min_number',
            'status' => 'required|in:active,inactive'
        ]);

        TruckSizeRange::create($request->only(['min_number', 'max_number', 'status']));

        return response()->json([
            'success' => 'Truck size range created successfully'
        ]);
    }

    public function edit(TruckSizeRange $truckSizeRange)
    {
        return view('management.master.truck-size-ranges.edit', compact('truckSizeRange'));
    }

    public function update(Request $request, TruckSizeRange $truckSizeRange)
    {
        $request->validate([
            'min_number' => 'required|numeric|min:0',
            'max_number' => 'required|numeric|gt:min_number',
            'status' => 'required|in:active,inactive'
        ]);

        $truckSizeRange->update($request->only(['min_number', 'max_number', 'status']));

        return response()->json([
            'success' => 'Truck size range updated successfully'
        ]);
    }

    public function destroy(TruckSizeRange $truckSizeRange)
    {
        $truckSizeRange->delete();

        return response()->json([
            'success' => 'Truck size range deleted successfully'
        ]);
    }

    public function getList(Request $request)
    {
        $perPage = $request->input('per_page', 25);
        $search = $request->input('search', '');

        $truckSizeRanges = TruckSizeRange::when($search, function ($query) use ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('min_number', 'like', '%' . $search . '%')
                    ->orWhere('max_number', 'like', '%' . $search . '%');
            });
        })
            ->paginate($perPage);

        return view('management.master.truck-size-ranges.getList', compact('truckSizeRanges'));
    }
}
