<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Master\Division;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DivisionController extends Controller
{
    public function index(Request $request)
    {
        // $perPage = $request->input('per_page', 25);
        // $search = $request->input('search', '');

        // $divisions = Division::with('addedBy')
        //     ->when($search, function ($query) use ($search) {
        //         $query->where('name', 'like', '%' . $search . '%');
        //     })
        //     ->paginate($perPage);

        return view('management.master.divisions.index',);
    }

    public function create()
    {
        return view('management.master.divisions.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:divisions',
            'hours' => 'nullable|integer',
            'status' => 'required|in:active,inactive',
        ]);

        Division::create([
            'name' => $request->name,
            'hours' => $request->hours,
            'status' => $request->status,
            'added_by' => auth()->user()->id,
        ]);

        return response()->json(['success'  => 'Division created successfully']);
    }

    public function edit(Division $division)
    {
        return view('management.master.divisions.edit', compact('division'));
    }

    public function update(Request $request, Division $division)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:divisions,name,' . $division->id,
            'hours' => 'nullable|integer',
            'status' => 'required|in:active,inactive',
        ]);

        $division->update($request->only(['name', 'hours', 'status']));

        return response()->json(['success'  => 'Division updated successfully']);
    }

    public function destroy(Division $division)
    {
        $division->delete();
        return response()->json(['success'  => 'Division deleted successfully']);
    }

    public function getList(Request $request)
    {
        $search = $request->input('search', '');

        $divisions = Division::with('addedBy')
            ->when($search, function ($query) use ($search) {
                $query->where('name', 'like', '%' . $search . '%');
            })
            ->latest()
            ->paginate(request('per_page', 25));

        return view('management.master.divisions.getList', compact('divisions'));
    }
}
