<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Master\Stitching;
use Illuminate\Http\Request;
use App\Http\Requests\Master\StitchingRequest;

class StitchingController extends Controller
{
    public function index()
    {
        return view('management.master.stitching.index');
    }

    public function getList(Request $request)
    {
        $stitchings = Stitching::when($request->filled('search'), function ($q) use ($request) {
            $searchTerm = '%' . $request->search . '%';
            return $q->where(function ($sq) use ($searchTerm) {
                $sq->where('name', 'like', $searchTerm);
            });
        })
            ->latest()
            ->paginate(request('per_page', 25));

        return view('management.master.stitching.getList', compact('stitchings'));
    }

    public function create()
    {
        return view('management.master.stitching.create');
    }

    public function store(StitchingRequest $request)
    {
        $data = $request->validated();
        $request = $request->all();
        $stitching = Stitching::create($request);
        return response()->json(['success' => 'Stitching created successfully.', 'data' => $stitching], 201);
    }

    public function edit($id)
    {
        $stitching = Stitching::findOrFail($id);
        return view('management.master.stitching.edit', compact('stitching'));
    }

    public function update(StitchingRequest $request, $id)
    {
        $data = $request->validated();
        $stitching = Stitching::findOrFail($id);
        $stitching->update($data);

        return response()->json(['success' => 'Stitching updated successfully.', 'data' => $stitching], 200);
    }

    public function destroy($id)
    {
        $stitching = Stitching::findOrFail($id);

        $stitching->delete();
        return response()->json(['success' => 'Stitching deleted successfully.'], 200);
    }
    
}
