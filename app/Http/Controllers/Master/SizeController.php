<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\SizeRequest;
use App\Models\Size;
use Illuminate\Http\Request;

class SizeController extends Controller
{
    public function index() {
        return view('management.master.size.index');
    }

    public function getList(Request $request) {
        $sizes = Size::when($request->filled('search'), function ($q) use ($request) {
            $searchTerm = '%' . strtolower($request->search) . '%';
            return $q->where(function ($sq) use ($searchTerm) {
                $sq->whereRaw('LOWER(size) LIKE ?', [strtolower($searchTerm)]);
            });
        })
        ->latest()
        ->paginate(25);


        return view('management.master.size.getList', compact('sizes'));
   
    }
    public function create() {
        return view('management.master.size.create');
    }

     public function store(SizeRequest $request) {
        $data = $request->validated();
        // dd($request->all());
        $size = Size::create($request->all());

        return response()->json(['success' => 'Size created successfully.', 'data' => $size], 201);
  
    }
    public function destroy(Size $size) {
        $size->delete();
        return response()->json(['success' => 'Size deleted successfully.'], 200);
    }
    public function edit(int $id) {
        $size = Size::findOrFail($id);
        $sizes = Size::where('id', '!=', $id)->get(); // Exclude current category from parent list
        return view('management.master.size.edit', compact('size', 'sizes'));
    }
    public function update(SizeRequest $request, Size $size)
    {
        $data = $request->validated();
        $size->update($data);

        return response()->json(['success' => 'Size updated successfully.', 'data' => $size], 200);
    }
}
