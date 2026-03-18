<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Master\RequestBy;
use Illuminate\Http\Request;
use App\Http\Requests\Master\RequestByRequest;

class RequestByController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('management.master.request_by.index');
    }

    /**
     * Get list of request by via AJAX.
     */
    public function getList(Request $request)
    {
        $request_bies = RequestBy::with('department')->when($request->filled('search'), function ($q) use ($request) {
            $searchTerm = '%' . $request->search . '%';
            return $q->where(function ($sq) use ($searchTerm) {
                $sq->where('name', 'like', $searchTerm)
                   ->orWhere('description', 'like', $searchTerm)
                   ->orWhereHas('department', function ($dq) use ($searchTerm) {
                       $dq->where('name', 'like', $searchTerm);
                   });
            });
        })
        ->latest()
        ->paginate($request->input('per_page', 25));

        return view('management.master.request_by.getList', compact('request_bies'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $departments = \App\Models\Master\Department::where('status', 'active')->get();
        return view('management.master.request_by.create', compact('departments'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(RequestByRequest $request)
    {
        $request_by = RequestBy::create($request->validated());

        return response()->json([
            'success' => 'Request By created successfully.',
            'data' => $request_by
        ], 201);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $request_by = RequestBy::findOrFail($id);
        $departments = \App\Models\Master\Department::where('status', 'active')->get();
        return view('management.master.request_by.edit', compact('request_by', 'departments'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(RequestByRequest $request, $id)
    {
        $request_by = RequestBy::findOrFail($id);
        $request_by->update($request->validated());

        return response()->json([
            'success' => 'Request By updated successfully.',
            'data' => $request_by
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $request_by = RequestBy::findOrFail($id);
        $request_by->delete();

        return response()->json([
            'success' => 'Request By deleted successfully.'
        ], 200);
    }

    /**
     * Get list of request by for a specific department.
     */
    public function getByDepartment($department_id)
    {
        $request_bies = RequestBy::where('department_id', $department_id)
                                 ->where('status', 'active')
                                 ->select('id', 'name')
                                 ->get();

        return response()->json([
            'success' => true,
            'data' => $request_bies
        ], 200);
    }
}
