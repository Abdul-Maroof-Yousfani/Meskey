<?php

namespace App\Http\Controllers\Export;

use App\Http\Controllers\Controller;
use App\Models\Export\DocumentList;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class DocumentListController extends Controller
{
    public function __construct()
    {
        // Adjust permissions if you have specific ones for document list, otherwise using general setup access or similar.
        // For now we assume typical roles.
    }

    public function index(Request $request): View
    {
        $documents = DocumentList::orderBy('id', 'ASC')->paginate(0);

        return view('management.export.document_list.index', compact('documents'))->with('i', ($request->input('page', 1) - 1) * 5);
    }

    public function create(): View
    {
        return view('management.export.document_list.create');
    }

    public function store(Request $request): JsonResponse
    {
        $rules = [
            'name' => 'required|string|max:255',
            'feature' => 'nullable|string|max:255',
            'is_required' => 'nullable|boolean',
            'status' => 'required|in:active,inactive',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $document = DocumentList::create([
            'name' => $request->input('name'),
            'feature' => $request->input('feature'),
            'is_required' => $request->has('is_required') ? $request->input('is_required') : false,
            'status' => $request->input('status'),
        ]);

        return response()->json([
            'success' => 'Successfully Saved.',
            'data' => $document,
        ]);
    }

    public function show(int $id)
    {
        $document = DocumentList::findOrFail($id);

        return view('management.export.document_list.show', compact('document'));
    }

    public function edit(int $id)
    {
        $document = DocumentList::findOrFail($id);

        return view('management.export.document_list.edit', compact('document'));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $document = DocumentList::findOrFail($id);

        $rules = [
            'name' => 'required|string|max:255',
            'feature' => 'nullable|string|max:255',
            'is_required' => 'nullable|boolean',
            'status' => 'required|in:active,inactive',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $document->update([
            'name' => $request->input('name'),
            'feature' => $request->input('feature'),
            'is_required' => $request->has('is_required') ? $request->input('is_required') : false,
            'status' => $request->input('status'),
        ]);

        return response()->json([
            'success' => 'Successfully Updated.',
            'data' => $document,
        ], 200);
    }

    public function destroy(int $id): JsonResponse
    {
        DB::beginTransaction();

        try {
            $document = DocumentList::find($id);

            if (! $document) {
                return response()->json([
                    'error' => 'Document List not found.',
                ], 404);
            }

            $document->delete();

            DB::commit();

            return response()->json([
                'success' => 'Document List deleted successfully.',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'error' => 'Something went wrong: '.$e->getMessage(),
            ], 500);
        }
    }

    public function getList(Request $request)
    {
        $documents = DocumentList::when($request->filled('search'), function ($q) use ($request) {
            $searchTerm = '%'.$request->search.'%';

            return $q->where(function ($sq) use ($searchTerm) {
                $sq->where('name', 'like', $searchTerm)
                   ->orWhere('feature', 'like', $searchTerm);
            });
        })
            ->latest()
            ->paginate(10);

        return view('management.export.document_list.getList', compact('documents'));
    }
}
