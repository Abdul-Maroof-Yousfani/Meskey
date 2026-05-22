<?php

namespace App\Http\Controllers\Export;

use App\Http\Controllers\Controller;
use App\Models\Export\WorkingDay;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class WorkingDayController extends Controller
{
    public function __construct()
    {
        $this->middleware('check.company:workingday-list', ['only' => ['index', 'getTable']]);
        $this->middleware('check.company:workingday-create', ['only' => ['create', 'store']]);
        $this->middleware('check.company:workingday-edit', ['only' => ['edit', 'update']]);
        $this->middleware('check.company:workingday-delete', ['only' => ['destroy']]);
    }

    public function index(Request $request): View
    {
        $days = WorkingDay::orderBy('id', 'ASC')->paginate(10);

        return view('management.export.working-days.index', compact('days'))
            ->with('i', ($request->input('page', 1) - 1) * 10);
    }

    public function create(): View
    {
        return view('management.export.working-days.create');
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

        $workingDay = WorkingDay::create($validator->validated());

        return response()->json([
            'success' => 'Working Day successfully saved.',
            'data' => $workingDay,
        ]);
    }

    public function show(int $id): View
    {
        $workingDay = WorkingDay::findOrFail($id);

        return view('management.export.working-days.show', compact('workingDay'));
    }

    public function edit(int $id): View
    {
        $workingDay = WorkingDay::findOrFail($id);

        return view('management.export.working-days.edit', compact('workingDay'));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $workingDay = WorkingDay::findOrFail($id);

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

        $workingDay->update($validator->validated());

        return response()->json([
            'success' => 'Working Day successfully updated.',
            'data' => $workingDay,
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        DB::beginTransaction();

        try {
            $workingDay = WorkingDay::find($id);

            if (! $workingDay) {
                return response()->json([
                    'error' => 'Working Day not found.',
                ], 404);
            }

            $workingDay->delete();

            DB::commit();

            return response()->json([
                'success' => 'Working Day deleted successfully.',
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
        $days = WorkingDay::when($request->filled('search'), function ($q) use ($request) {
            $searchTerm = '%' . $request->search . '%';

            return $q->where('name', 'like', $searchTerm);
        })
            ->latest()
            ->paginate(10);

        return view('management.export.working-days.getList', compact('days'));
    }
}
