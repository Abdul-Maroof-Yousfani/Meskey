<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Master\LocationType;
use App\Models\Acl\Company;
use App\Http\Requests\Master\LocationTypeRequest;
use Illuminate\Http\Request;

class LocationTypeController extends Controller
{
    public function index()
    {
        return view('management.master.location-type.index');
    }

    public function getList(Request $request)
    {
        $location_types = LocationType::with('company')
            ->when($request->filled('search'), function ($q) use ($request) {
                $searchTerm = '%' . $request->search . '%';
                return $q->where('name', 'like', $searchTerm);
            })
            ->where('company_id', $request->company_id)
            ->latest()
            ->paginate(request('per_page', 25));

        return view('management.master.location-type.getList', compact('location_types'));
    }

    public function create()
    {
        $companies = Company::where('status', '1')->get();
        return view('management.master.location-type.create', compact('companies'));
    }

    public function store(LocationTypeRequest $request)
    {
        $data = $request->validated();
        $data['created_by'] = auth()->user()->id;
        $location_type = LocationType::create($data);

        return response()->json(['success' => 'Location Type created successfully.', 'data' => $location_type], 201);
    }

    public function edit($id)
    {
        $locationType = LocationType::findOrFail($id);
        $companies = Company::where('status', '1')->get();
        return view('management.master.location-type.edit', compact('locationType', 'companies'));
    }

    public function update(LocationTypeRequest $request, LocationType $location_type)
    {
        $data = $request->validated();
        $location_type->update($data);
        return response()->json(['success' => 'Location Type updated successfully.', 'data' => $location_type], 200);
    }

    public function destroy($id)
    {
        $location_type = LocationType::findOrFail($id);
        $location_type->delete();
        return response()->json(['success' => 'Location Type deleted successfully.'], 200);
    }
}
