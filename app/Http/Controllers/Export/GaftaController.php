<?php

namespace App\Http\Controllers\Export;

use App\Http\Controllers\Controller;
use App\Models\Export\Gafta;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class GaftaController extends Controller
{
    public function __construct()
    {
        $this->middleware('check.company:gafta-list', ['only' => ['index', 'getTable']]);
        $this->middleware('check.company:gafta-create', ['only' => ['create', 'store']]);
        $this->middleware('check.company:gafta-edit', ['only' => ['edit', 'update']]);
        $this->middleware('check.company:gafta-delete', ['only' => ['destroy']]);
    }

    public function index(Request $request): View
    {
        $gaftas = Gafta::orderBy('id', 'ASC')->paginate(10);

        return view('management.export.gafta.index', compact('gaftas'))
            ->with('i', ($request->input('page', 1) - 1) * 10);
    }

    public function create(): View
    {
        return view('management.export.gafta.create');
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'status' => 'required|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $gafta = Gafta::create($validator->validated());

        return response()->json([
            'success' => 'Gafta successfully saved.',
            'data' => $gafta,
        ]);
    }

    public function show(int $id): View
    {
        $gafta = Gafta::findOrFail($id);

        return view('management.export.gafta.show', compact('gafta'));
    }

    public function edit(int $id): View
    {
        $gafta = Gafta::findOrFail($id);

        return view('management.export.gafta.edit', compact('gafta'));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $gafta = Gafta::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'status' => 'required|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $gafta->update($validator->validated());

        return response()->json([
            'success' => 'Gafta successfully updated.',
            'data' => $gafta,
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        DB::beginTransaction();

        try {
            $gafta = Gafta::find($id);

            if (! $gafta) {
                return response()->json([
                    'error' => 'Gafta not found.',
                ], 404);
            }

            $gafta->delete();

            DB::commit();

            return response()->json([
                'success' => 'Gafta deleted successfully.',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'error' => 'Something went wrong: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function getTable(Request $request): View
    {
        $gaftas = Gafta::when($request->filled('search'), function ($q) use ($request) {
            $searchTerm = '%' . $request->search . '%';

            return $q->where('name', 'like', $searchTerm);
        })
            ->latest()
            ->paginate(10);

        return view('management.export.gafta.getList', compact('gaftas'));
    }
}
