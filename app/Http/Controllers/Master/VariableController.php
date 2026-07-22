<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Master\Variable;

class VariableController extends Controller
{
    public function index() {
        return view('management.master.variable.index');
    }

    public function getList(Request $request) {
        $variables = Variable::when($request->filled('search'), function ($q) use ($request) {
            $searchTerm = '%' . strtolower($request->search) . '%';
            return $q->whereRaw('LOWER(title) LIKE ?', [$searchTerm]);
        })
        ->latest()
        ->paginate(25);

        return view('management.master.variable.getList', compact('variables'));
    }

    public function create() {
        return view('management.master.variable.create');
    }

    public function store(Request $request) {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'nullable|integer',
            'company_id' => 'nullable|integer'
        ]);

        if (!isset($data['company_id'])) {
            $data['company_id'] = auth()->check() ? auth()->user()->current_company_id : null;
        }
        $data['status'] = $data['status'] ?? 1;
        $variable = Variable::create($data);
        return response()->json(['success' => 'Variable created successfully.', 'data' => $variable], 201);
    }

    public function edit(int $id) {
        $variable = Variable::findOrFail($id);
        return view('management.master.variable.edit', compact('variable'));
    }

    public function update(Request $request, int $id) {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'nullable|integer',
            'company_id' => 'nullable|integer'
        ]);
        
        $variable = Variable::findOrFail($id);
        $variable->update($data);
        return response()->json(['success' => 'Variable updated successfully.', 'data' => $variable], 200);
    }
    
    public function show(int $id) {
        $variable = Variable::findOrFail($id);
        return view('management.master.variable.show', compact('variable'));
    }
}
