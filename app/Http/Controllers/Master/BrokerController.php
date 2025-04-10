<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Master\Broker;
use Illuminate\Http\Request;
use App\Http\Requests\Master\BrokerRequest;

class BrokerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('management.master.broker.index');
    }

    /**
     * Get list of categories.
     */
    public function getList(Request $request)
    {
        $brokers = Broker::when($request->filled('search'), function ($q) use ($request) {
            $searchTerm = '%' . $request->search . '%';
            return $q->where(function ($sq) use ($searchTerm) {
                $sq->where('name', 'like', $searchTerm);
            });
        })
            ->latest()
            ->paginate(request('per_page', 25));

        return view('management.master.broker.getList', compact('brokers'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('management.master.broker.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(BrokerRequest $request)
    {
        $data = $request->validated();
        $request = $request->all();

        $request['unique_no'] = generateUniqueNumber('brokers', null, null, 'unique_no');
        $broker = Broker::create($request);

        return response()->json(['success' => 'Broker created successfully.', 'data' => $broker], 201);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $broker = Broker::findOrFail($id);
        return view('management.master.broker.edit', compact('broker'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(BrokerRequest $request, $id)
    {
        $data = $request->validated();
        $broker = Broker::findOrFail($id);
        $broker->update($data);

        return response()->json(['success' => 'Broker updated successfully.', 'data' => $broker], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $broker = Broker::findOrFail($id);

        $broker->delete();
        return response()->json(['success' => 'Broker deleted successfully.'], 200);
    }
}
