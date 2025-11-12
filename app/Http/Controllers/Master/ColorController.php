<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\ColorRequest;
use Illuminate\Http\Request;
use App\Models\Color;

class ColorController extends Controller
{
    public function index() {
        return view('management.master.color.index');
    }

    public function getList(Request $request) {
        $colors = Color::when($request->filled('search'), function ($q) use ($request) {
            $searchTerm = '%' . strtolower($request->search) . '%';
            return $q->where(function ($sq) use ($searchTerm) {
                $sq->whereRaw('LOWER(color) LIKE ?', [strtolower($searchTerm)]);
            });
        })
        ->latest()
        ->paginate(25);


        return view('management.master.color.getList', compact('colors'));
   
    }

    public function create() {
        return view('management.master.color.create');
    }

    public function store(ColorRequest $request) {
        $data = $request->validated();
        $color = Color::create($request->all());

        return response()->json(['success' => 'Color created successfully.', 'data' => $color], 201);
  
    }
    public function destroy(Color $color) {
        $color->delete();
        return response()->json(['success' => 'Color deleted successfully.'], 200);
    }
    public function edit(int $id) {
        $color = Color::findOrFail($id);
        $colors = Color::where('id', '!=', $id)->get(); // Exclude current category from parent list
        return view('management.master.color.edit', compact('color', 'colors'));
    }
    public function update(ColorRequest $request, Color $color)
    {
        $data = $request->validated();
        $color->update($data);

        return response()->json(['success' => 'Category updated successfully.', 'data' => $color], 200);
    }
}
