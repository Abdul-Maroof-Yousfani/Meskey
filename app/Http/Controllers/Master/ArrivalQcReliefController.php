<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Master\ArrivalQcReliefParameter;
use App\Models\Master\CompanyLocation;
use App\Models\Master\ProductSlabType;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ArrivalQcReliefController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $products = Product::orderBy('name')->get();
        $locations = CompanyLocation::where('status', 'active')->orderBy('name')->get();
        if ($locations->isEmpty()) {
            $locations = CompanyLocation::orderBy('name')->get();
        }
        $slab_types = ProductSlabType::ordered()->get();

        return view('management.master.arrival-qc-relief.index', compact('products', 'locations', 'slab_types'));
    }

    /**
     * Get list of Arrival QC Relief parameter records.
     */
    public function getList(Request $request)
    {
        $paginator = ArrivalQcReliefParameter::query()
            ->select(
                DB::raw('MIN(id) as id'),
                'product_id',
                'location_id',
                'start_date',
                'end_date'
            )
            ->when($request->filled('product_id'), function ($q) use ($request) {
                return $q->where('product_id', $request->product_id);
            })
            ->when($request->filled('location_id'), function ($q) use ($request) {
                if ($request->location_id === 'all') {
                    return $q->whereNull('location_id');
                }
                return $q->where('location_id', $request->location_id);
            })
            ->when($request->filled('search'), function ($q) use ($request) {
                return $q->where(function ($sub) use ($request) {
                    $sub->whereHas('product', function ($pq) use ($request) {
                        $pq->where('name', 'like', "%{$request->search}%");
                    })->orWhereHas('location', function ($lq) use ($request) {
                        $lq->where('name', 'like', "%{$request->search}%");
                    })->orWhere('parameter_name', 'like', "%{$request->search}%");
                });
            })
            ->when($request->filled('start_date'), function ($q) use ($request) {
                return $q->where('start_date', '>=', $request->start_date);
            })
            ->when($request->filled('end_date'), function ($q) use ($request) {
                return $q->where('end_date', '<=', $request->end_date);
            })
            ->groupBy(['product_id', 'location_id', 'start_date', 'end_date'])
            ->orderBy('start_date', 'desc')
            ->paginate($request->input('per_page', 25));

        $productIds = $paginator->pluck('product_id')->filter()->unique();

        if ($productIds->isNotEmpty()) {
            $allRules = ArrivalQcReliefParameter::with(['product', 'location', 'slabType'])
                ->whereIn('product_id', $productIds)
                ->orderBy('slab_type_id')
                ->orderBy('from_range')
                ->get();
        } else {
            $allRules = collect();
        }

        $groups = $paginator->getCollection()->map(function ($group) use ($allRules) {
            $matchingRules = $allRules->filter(function ($rule) use ($group) {
                $locMatch = ($group->location_id === null)
                    ? ($rule->location_id === null)
                    : ($rule->location_id == $group->location_id);

                $groupS = $group->start_date ? \Carbon\Carbon::parse($group->start_date)->format('Y-m-d') : null;
                $ruleS = $rule->start_date ? $rule->start_date->format('Y-m-d') : null;

                $groupE = $group->end_date ? \Carbon\Carbon::parse($group->end_date)->format('Y-m-d') : null;
                $ruleE = $rule->end_date ? $rule->end_date->format('Y-m-d') : null;

                return $rule->product_id == $group->product_id
                    && $locMatch
                    && ($groupS === $ruleS)
                    && ($groupE === $ruleE);
            });

            $firstRule = $matchingRules->first();

            return (object)[
                'id' => $group->id,
                'product_id' => $group->product_id,
                'location_id' => $group->location_id,
                'product' => $firstRule ? $firstRule->product : Product::find($group->product_id),
                'location' => $firstRule ? $firstRule->location : ($group->location_id ? CompanyLocation::find($group->location_id) : null),
                'start_date' => $group->start_date ? \Carbon\Carbon::parse($group->start_date) : null,
                'end_date' => $group->end_date ? \Carbon\Carbon::parse($group->end_date) : null,
                'is_active' => $firstRule ? $firstRule->is_active : true,
                'rules' => $matchingRules,
            ];
        });

        return view('management.master.arrival-qc-relief.getList', [
            'groups' => $groups,
            'paginator' => $paginator,
        ]);
    }

    /**
     * Show the form for creating a new Arrival QC Relief rule.
     */
    public function create()
    {
        $products = Product::orderBy('name')->get();
        $locations = CompanyLocation::where('status', 'active')->orderBy('name')->get();
        if ($locations->isEmpty()) {
            $locations = CompanyLocation::orderBy('name')->get();
        }
        $slab_types = ProductSlabType::ordered()->get();

        return view('management.master.arrival-qc-relief.create', compact('products', 'locations', 'slab_types'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'parameters' => 'required|array',
        ]);

        $locationId = ($request->location_id === 'all' || empty($request->location_id)) ? null : $request->location_id;

        // Check for overlapping active date range for the same product and location
        $hasOverlap = ArrivalQcReliefParameter::where('product_id', $request->product_id)
            ->when($locationId, fn($q) => $q->where('location_id', $locationId), fn($q) => $q->whereNull('location_id'))
            ->where(function ($q) use ($request) {
                $q->whereBetween('start_date', [$request->start_date, $request->end_date])
                  ->orWhereBetween('end_date', [$request->start_date, $request->end_date])
                  ->orWhere(function ($sq) use ($request) {
                      $sq->where('start_date', '<=', $request->start_date)
                         ->where('end_date', '>=', $request->end_date);
                  });
            })
            ->exists();

        if ($hasOverlap) {
            return response()->json([
                'catchError' => 'A QC Relief rule already exists that overlaps with the selected date range for this product and location.'
            ], 422);
        }

        DB::beginTransaction();
        try {
            $createdCount = 0;
            $companyId = $request->company_id ?? (auth()->check() ? auth()->user()->company_id : null);
            $userId = (auth()->check() && is_numeric(auth()->user()->id)) ? (int)auth()->user()->id : null;

            foreach ($request->parameters as $slabTypeId => $slabData) {
                if (!empty($slabData['is_enabled'])) {
                    $paramName = $slabData['name'] ?? 'Parameter';
                    $ranges = $slabData['ranges'] ?? [];

                    $validRanges = collect($ranges)->filter(function ($range) {
                        return isset($range['from'], $range['to']) &&
                               $range['from'] !== '' &&
                               $range['to'] !== '';
                    })->sortBy('from')->values();

                    foreach ($validRanges as $range) {
                        $from = (float)$range['from'];
                        $to = (float)$range['to'];

                        if ($from >= $to) {
                            throw new \Exception("From value ({$from}) must be strictly less than To value ({$to}) for {$paramName}.");
                        }

                        $reliefType = $range['relief_type'] ?? 'fixed';
                        $reliefValue = (float)($range['relief_value'] ?? 0);

                        ArrivalQcReliefParameter::create([
                            'company_id' => $companyId,
                            'product_id' => $request->product_id,
                            'location_id' => $locationId,
                            'start_date' => $request->start_date,
                            'end_date' => $request->end_date,
                            'slab_type_id' => $slabTypeId,
                            'parameter_name' => $paramName,
                            'relief_type' => $reliefType,
                            'from_range' => $from,
                            'to_range' => $to,
                            'fixed_value' => ($reliefType === 'fixed') ? $reliefValue : 0,
                            'relief_value' => $reliefValue,
                            'is_active' => true,
                            'created_by' => $userId,
                            'updated_by' => $userId,
                        ]);

                        $createdCount++;
                    }
                }
            }

            if ($createdCount === 0) {
                throw new \Exception('Please enable at least one QC parameter and configure its range.');
            }

            DB::commit();

            return response()->json([
                'success' => 'Arrival QC Relief Configuration saved successfully.'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'catchError' => 'Failed to save Arrival QC Relief: ' . $e->getMessage()
            ], 422);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $first = ArrivalQcReliefParameter::findOrFail($id);

        $products = Product::orderBy('name')->get();
        $locations = CompanyLocation::where('status', 'active')->orderBy('name')->get();
        if ($locations->isEmpty()) {
            $locations = CompanyLocation::orderBy('name')->get();
        }
        $slab_types = ProductSlabType::ordered()->get();

        // Retrieve all records belonging to the same (product, location, start_date, end_date) group
        $groupItems = ArrivalQcReliefParameter::where('product_id', $first->product_id)
            ->when($first->location_id, fn($q) => $q->where('location_id', $first->location_id), fn($q) => $q->whereNull('location_id'))
            ->whereDate('start_date', $first->start_date)
            ->whereDate('end_date', $first->end_date)
            ->get();

        $selectedProductId = $first->product_id;
        $selectedLocationId = $first->location_id;
        $startDate = $first->start_date ? $first->start_date->format('Y-m-d') : '';
        $endDate = $first->end_date ? $first->end_date->format('Y-m-d') : '';

        $configuredData = [];
        foreach ($groupItems->groupBy('parameter_name') as $paramName => $paramRecords) {
            $configuredData[$paramName] = [
                'is_enabled' => 1,
                'ranges' => $paramRecords->map(function ($r) {
                    return [
                        'from' => $r->from_range,
                        'to' => $r->to_range,
                        'relief_type' => $r->relief_type ?? 'fixed',
                        'relief_value' => $r->relief_value ?: $r->fixed_value,
                    ];
                })->toArray()
            ];
        }

        return view('management.master.arrival-qc-relief.edit', compact(
            'id',
            'products',
            'locations',
            'slab_types',
            'selectedProductId',
            'selectedLocationId',
            'startDate',
            'endDate',
            'configuredData'
        ));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'parameters' => 'required|array',
        ]);

        $first = ArrivalQcReliefParameter::findOrFail($id);
        $origProductId = $first->product_id;
        $origLocationId = $first->location_id;
        $origStartDate = $first->start_date ? $first->start_date->format('Y-m-d') : null;
        $origEndDate = $first->end_date ? $first->end_date->format('Y-m-d') : null;

        $newLocationId = ($request->location_id === 'all' || empty($request->location_id)) ? null : $request->location_id;

        // Check for overlapping records excluding the current group being edited
        $hasOverlap = ArrivalQcReliefParameter::where('product_id', $request->product_id)
            ->when($newLocationId, fn($q) => $q->where('location_id', $newLocationId), fn($q) => $q->whereNull('location_id'))
            ->where(function ($q) use ($origProductId, $origLocationId, $origStartDate, $origEndDate) {
                $q->where(function ($sub) use ($origProductId, $origLocationId, $origStartDate, $origEndDate) {
                    $sub->where('product_id', '!=', $origProductId)
                        ->orWhere('location_id', '!=', $origLocationId)
                        ->orWhereDate('start_date', '!=', $origStartDate)
                        ->orWhereDate('end_date', '!=', $origEndDate);
                });
            })
            ->where(function ($q) use ($request) {
                $q->whereBetween('start_date', [$request->start_date, $request->end_date])
                  ->orWhereBetween('end_date', [$request->start_date, $request->end_date])
                  ->orWhere(function ($sq) use ($request) {
                      $sq->where('start_date', '<=', $request->start_date)
                         ->where('end_date', '>=', $request->end_date);
                  });
            })
            ->exists();

        if ($hasOverlap) {
            return response()->json([
                'catchError' => 'The updated date range overlaps with another existing QC Relief rule for this product and location.'
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Delete original group records
            ArrivalQcReliefParameter::where('product_id', $origProductId)
                ->when($origLocationId, fn($q) => $q->where('location_id', $origLocationId), fn($q) => $q->whereNull('location_id'))
                ->whereDate('start_date', $origStartDate)
                ->whereDate('end_date', $origEndDate)
                ->delete();

            $createdCount = 0;
            $companyId = $request->company_id ?? (auth()->check() ? auth()->user()->company_id : null);
            $userId = (auth()->check() && is_numeric(auth()->user()->id)) ? (int)auth()->user()->id : null;

            foreach ($request->parameters as $slabTypeId => $slabData) {
                if (!empty($slabData['is_enabled'])) {
                    $paramName = $slabData['name'] ?? 'Parameter';
                    $ranges = $slabData['ranges'] ?? [];

                    $validRanges = collect($ranges)->filter(function ($range) {
                        return isset($range['from'], $range['to']) &&
                               $range['from'] !== '' &&
                               $range['to'] !== '';
                    })->sortBy('from')->values();

                    foreach ($validRanges as $range) {
                        $from = (float)$range['from'];
                        $to = (float)$range['to'];

                        if ($from >= $to) {
                            throw new \Exception("From value ({$from}) must be strictly less than To value ({$to}) for {$paramName}.");
                        }

                        $reliefType = $range['relief_type'] ?? 'fixed';
                        $reliefValue = (float)($range['relief_value'] ?? 0);

                        ArrivalQcReliefParameter::create([
                            'company_id' => $companyId,
                            'product_id' => $request->product_id,
                            'location_id' => $newLocationId,
                            'start_date' => $request->start_date,
                            'end_date' => $request->end_date,
                            'slab_type_id' => $slabTypeId,
                            'parameter_name' => $paramName,
                            'relief_type' => $reliefType,
                            'from_range' => $from,
                            'to_range' => $to,
                            'fixed_value' => ($reliefType === 'fixed') ? $reliefValue : 0,
                            'relief_value' => $reliefValue,
                            'is_active' => true,
                            'created_by' => $userId,
                            'updated_by' => $userId,
                        ]);

                        $createdCount++;
                    }
                }
            }

            if ($createdCount === 0) {
                throw new \Exception('Please enable at least one QC parameter and configure its range.');
            }

            DB::commit();

            return response()->json([
                'success' => 'Arrival QC Relief Configuration updated successfully.'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'catchError' => 'Failed to update Arrival QC Relief: ' . $e->getMessage()
            ], 422);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $first = ArrivalQcReliefParameter::findOrFail($id);

        DB::beginTransaction();
        try {
            ArrivalQcReliefParameter::where('product_id', $first->product_id)
                ->when($first->location_id, fn($q) => $q->where('location_id', $first->location_id), fn($q) => $q->whereNull('location_id'))
                ->whereDate('start_date', $first->start_date)
                ->whereDate('end_date', $first->end_date)
                ->delete();

            DB::commit();

            return response()->json([
                'success' => 'Arrival QC Relief configuration deleted successfully.'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'catchError' => 'Failed to delete Arrival QC Relief: ' . $e->getMessage()
            ], 422);
        }
    }
}
