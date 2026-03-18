<?php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use App\Http\Requests\Production\PlantBreakdownRequest;
use App\Models\Production\PlantBreakdown;
use App\Models\Production\PlantBreakdownItem;
use App\Models\Master\Plant;
use App\Models\Master\PlantBreakdownType;
use App\Models\Production\ProductionVoucher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PlantBreakdownController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('management.production.plant_breakdown.index');
    }

    /**
     * Get list of plant breakdowns.
     */
    public function getList(Request $request)
    {
        $plantBreakdowns = PlantBreakdown::with(['plant', 'productionVoucher', 'user', 'items.breakdownType'])
            ->when($request->filled('search'), function ($q) use ($request) {
                $searchTerm = '%' . $request->search . '%';
                return $q->where(function ($sq) use ($searchTerm) {
                    $sq->whereHas('plant', function ($q) use ($searchTerm) {
                        $q->where('name', 'like', $searchTerm);
                    })
                        ->orWhereHas('productionVoucher', function ($q) use ($searchTerm) {
                            $q->where('prod_no', 'like', $searchTerm);
                        });
                });
            })
            ->when($request->filled('company_id'), function ($q) use ($request) {
                return $q->where('company_id', $request->company_id);
            })
            ->when($request->filled('plant_id'), function ($q) use ($request) {
                return $q->where('plant_id', $request->plant_id);
            })
            ->when($request->filled('date_from'), function ($q) use ($request) {
                return $q->whereDate('date', '>=', $request->date_from);
            })
            ->when($request->filled('date_to'), function ($q) use ($request) {
                return $q->whereDate('date', '<=', $request->date_to);
            })
            ->latest('date')
            ->latest('created_at')
            ->paginate(request('per_page', 25));

        return view('management.production.plant_breakdown.getList', compact('plantBreakdowns'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $plants = Plant::where('status', 'active')->get();

        $productionVouchers = ProductionVoucher::latest()->get();

        $breakdownTypes = PlantBreakdownType::where('status', 'active')->get();

        return view('management.production.plant_breakdown.create', compact('plants', 'productionVouchers', 'breakdownTypes'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(PlantBreakdownRequest $request)
    {
        DB::beginTransaction();

        try {
            $plantBreakdownData = $request->only([
                'company_id',
                'date',
                'plant_id',
                'production_voucher_id',
            ]);
            $plantBreakdownData['user_id'] = auth()->user()->id;
            $plantBreakdown = PlantBreakdown::create($plantBreakdownData);

            // Save breakdown items
            if ($request->has('breakdown_type_id') && is_array($request->breakdown_type_id)) {
                foreach ($request->breakdown_type_id as $index => $breakdownTypeId) {
                    if (!empty($breakdownTypeId) && !empty($request->from[$index]) && !empty($request->to[$index])) {
                        // Calculate hours if not provided
                        $fromTime = \Carbon\Carbon::parse($request->from[$index]);
                        $toTime = \Carbon\Carbon::parse($request->to[$index]);
                        $calculatedHours = $fromTime->diffInHours($toTime, false) + ($fromTime->diffInMinutes($toTime, false) % 60) / 60;

                        PlantBreakdownItem::create([
                            'company_id' => $request->company_id,
                            'plant_breakdown_id' => $plantBreakdown->id,
                            'breakdown_type_id' => $breakdownTypeId,
                            'from' => $request->from[$index],
                            'to' => $request->to[$index],
                            'hours' => $request->hours[$index] ?? $calculatedHours,
                            'remarks' => $request->remarks[$index] ?? null,
                        ]);
                    }
                }
            }

            DB::commit();

            return response()->json([
                'success' => 'Plant Breakdown created successfully.',
                'data' => $plantBreakdown->load('items.breakdownType')
            ], 201);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Something went wrong: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, $id)
    {
        $plantBreakdown = PlantBreakdown::with('items.breakdownType')->findOrFail($id);

        $plants = Plant::where('status', 'active')
            ->when($request->filled('company_id'), function ($q) use ($request) {
                return $q->where('company_id', $request->company_id);
            })
            ->get();

        $productionVouchers = ProductionVoucher::when($request->filled('company_id'), function ($q) use ($request) {
            return $q->where('company_id', $request->company_id);
        })
            ->latest()
            ->get();

        $breakdownTypes = PlantBreakdownType::where('status', 'active')
            ->when($request->filled('company_id'), function ($q) use ($request) {
                return $q->where('company_id', $request->company_id);
            })
            ->get();

        return view('management.production.plant_breakdown.edit', compact('plantBreakdown', 'plants', 'productionVouchers', 'breakdownTypes'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(PlantBreakdownRequest $request, $id)
    {
        
        $plantBreakdown = PlantBreakdown::findOrFail($id);

        DB::beginTransaction();

        try {
            $plantBreakdownData = $request->only([
                'company_id',
                'date',
                'plant_id',
                'production_voucher_id',
                // 'user_id',
            ]);

            $plantBreakdownData['user_id'] = auth()->user()->id;
            $plantBreakdown->update($plantBreakdownData);

            // Delete existing items
            $plantBreakdown->items()->delete();

            // Save new breakdown items
            if ($request->has('breakdown_type_id') && is_array($request->breakdown_type_id)) {
                foreach ($request->breakdown_type_id as $index => $breakdownTypeId) {
                    if (!empty($breakdownTypeId) && !empty($request->from[$index]) && !empty($request->to[$index])) {
                        // Calculate hours if not provided
                        $fromTime = \Carbon\Carbon::parse($request->from[$index]);
                        $toTime = \Carbon\Carbon::parse($request->to[$index]);
                        $calculatedHours = $fromTime->diffInHours($toTime, false) + ($fromTime->diffInMinutes($toTime, false) % 60) / 60;

                        PlantBreakdownItem::create([
                            'company_id' => $request->company_id,
                            'plant_breakdown_id' => $plantBreakdown->id,
                            'breakdown_type_id' => $breakdownTypeId,
                            'from' => $request->from[$index],
                            'to' => $request->to[$index],
                            'hours' => $request->hours[$index] ?? $calculatedHours,
                            'remarks' => $request->remarks[$index] ?? null,
                        ]);
                    }
                }
            }

            DB::commit();

            return response()->json([
                'success' => 'Plant Breakdown updated successfully.',
                'data' => $plantBreakdown->load('items.breakdownType')
            ], 200);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Something went wrong: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $plantBreakdown = PlantBreakdown::findOrFail($id);
        $plantBreakdown->delete();

        return response()->json([
            'success' => 'Plant Breakdown deleted successfully.'
        ], 200);
    }
}
