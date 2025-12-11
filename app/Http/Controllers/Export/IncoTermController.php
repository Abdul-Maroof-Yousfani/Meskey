<?php

namespace App\Http\Controllers\Export;

use App\Http\Controllers\Controller;
use App\Models\Acl\Company;
use App\Models\Export\IncoTerm;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class IncoTermController extends Controller
{
    public function __construct()
    {
        $this->middleware('check.company:incoterm-list', ['only' => ['index']]);
        $this->middleware('check.company:incoterm-list', ['only' => ['getTable']]);
        $this->middleware('check.company:incoterm-create', ['only' => ['create', 'store']]);
        $this->middleware('check.company:incoterm-edit', ['only' => ['edit', 'update']]);
        $this->middleware('check.company:incoterm-delete', ['only' => ['destroy']]);
    }

    public function index(Request $request): View
    {
        $terms = IncoTerm::orderBy('id', 'ASC')->paginate(0);

        return view('management.export.incoterm.index', compact('terms'))->with('i', ($request->input('page', 1) - 1) * 5);
    }

    public function create(): View
    {
        $companies = Company::get();

        return view('management.export.incoterm.create', compact('companies'));
    }

    public function store(Request $request): JsonResponse
    {
        $rules = [
            'company' => 'required|exists:companies,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'status' => 'required|in:active,inactive',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $mode = IncoTerm::create([
            'company_id' => $request->input('company'),
            'name' => $request->input('name'),
            'description' => $request->input('description'),
            'status' => $request->input('status'),
        ]);

        return response()->json([
            'success' => 'Successfully Saved.',
            'data' => $mode,
        ]);
    }

    public function show(int $id)
    {
        $term = IncoTerm::findOrFail($id);

        $companies = Company::get();

        return view('management.export.incoterm.show', compact('term', 'companies'));
    }

    public function edit(int $id)
    {
        $term = IncoTerm::findOrFail($id);
        $companies = Company::get();

        return view('management.export.incoterm.edit', compact('term', 'companies'));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $mode = IncoTerm::findOrFail($id);

        $rules = [
            'company' => 'required|exists:companies,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'status' => 'required|in:active,inactive',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $mode->update([
            'company_id' => $request->input('company'),
            'name' => $request->input('name'),
            'description' => $request->input('description'),
            'status' => $request->input('status'),
        ]);

        return response()->json([
            'success' => 'Successfully Updated.',
            'data' => $mode,
        ], 200);
    }

    public function destroy(int $id): JsonResponse
    {
        DB::beginTransaction();

        try {
            $term = IncoTerm::find($id);

            if (! $term) {
                return response()->json([
                    'error' => 'IncoTerm not found.',
                ], 404);
            }

            $term->delete();

            DB::commit();

            return response()->json([
                'success' => 'IncoTerm deleted successfully.',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'error' => 'Something went wrong: '.$e->getMessage(),
            ], 500);
        }
    }

    public function getIncotermTable(Request $request)
    {
        $terms = IncoTerm::when($request->filled('search'), function ($q) use ($request) {
            $searchTerm = '%'.$request->search.'%';

            return $q->where(function ($sq) use ($searchTerm) {
                $sq->where('name', 'like', $searchTerm);
            });
        })
            ->latest()
            ->paginate(10);

        return view('management.export.incoterm.getList', compact('terms'));
    }
}
