<?php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Production\Drying\ProductionDryingJobOrder;
use App\Models\Production\Drying\ProductionDryingJobOrderItem;
use App\Models\ProdctionAttribute;
use App\Models\Product;
use App\Models\Master\CropYear;
use App\Models\Master\CompanyLocation;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ProductionDryingController extends Controller
{
    public function index()
    {
        return view("management.production.drying.index");
    }

    public function getList(Request $request)
    {
        $dryingJobOrders = ProductionDryingJobOrder::with(['product', 'companyLocation', 'exportOrder', 'items', 'specifications', 'creator', 'productionRecipe'])
            ->when($request->filled('search'), function ($q) use ($request) {
                $searchTerm = '%' . $request->search . '%';
                return $q->where(function ($sq) use ($searchTerm) {
                    $sq->where('job_order_no', 'like', $searchTerm)
                        ->orWhere('ref_no', 'like', $searchTerm)
                        ->orWhere('crop_year', 'like', $searchTerm)
                        ->orWhere('recipe_name', 'like', $searchTerm)
                        ->orWhereHas('productionRecipe', function ($rq) use ($searchTerm) {
                            $rq->where('name', 'like', $searchTerm);
                        })
                        ->orWhereHas('exportOrder', function ($eq) use ($searchTerm) {
                            $eq->where('voucher_no', 'like', $searchTerm);
                        });
                });
            })
            ->when($request->filled('status'), function ($q) use ($request) {
                return $q->where('status', $request->status);
            })
            ->when($request->filled('company_location_id'), function ($q) use ($request) {
                return $q->where('company_location_id', $request->company_location_id);
            })
            ->when($request->filled('product_id'), function ($q) use ($request) {
                return $q->where('product_id', $request->product_id);
            })
            ->when($request->filled('moisture_level'), function ($q) use ($request) {
                return $q->where('moisture_level', $request->moisture_level);
            })
            ->latest()
            ->paginate(request('per_page', 25));

        return view("management.production.drying.getList", compact('dryingJobOrders'));
    }

    public function create()
    {
        $users = User::get();
        $products = Product::where('status', 'active')->get();
        $attributes = ProdctionAttribute::where('status', 'active')->orderBy('key')->get();
        $cropYears = CropYear::where('status', 'active')->get();
        $exportOrders = \App\Models\Export\ExportOrder::where('am_approval_status', 'approved')->latest()->get();
        $productionRecipes = \App\Models\Master\ProductionRecipe::where('status', 'active')->get();

        $user = auth()->user();
        $locationIds = function_exists('getUserCurrentCompanyLocations') ? getUserCurrentCompanyLocations() : [];
        if ($user && ($user->user_type === 'super-admin' || empty($locationIds))) {
            $companyLocations = CompanyLocation::where('status', 'active')->get();
        } else {
            $companyLocations = CompanyLocation::whereIn('id', $locationIds)->where('status', 'active')->get();
        }

        $selectedLocation = count($companyLocations) == 1 ? $companyLocations->first() : null;
        $jobOrderNo = $selectedLocation ? self::generateUniqueNumber(date('Y-m-d'), $selectedLocation->id) : '';

        return view("management.production.drying.create", compact(
            'attributes',
            'products',
            'users',
            'cropYears',
            'exportOrders',
            'productionRecipes',
            'companyLocations',
            'jobOrderNo'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'job_order_date' => 'required|date',
            'company_location_id' => 'required',
            'product_id' => 'required',
            'crop_year' => 'nullable|string',
            'moisture_level' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $uniqueJobOrderNo = self::generateUniqueNumber($request->job_order_date, $request->company_location_id);

            // Find crop_year_id if possible
            $cropYearId = null;
            if ($request->filled('crop_year')) {
                $cy = CropYear::where('name', $request->crop_year)->first();
                $cropYearId = $cy?->id;
            }

            $recipeId = $request->production_recipe_id;
            if ($recipeId === 'custom' || empty($recipeId)) {
                $recipeId = null;
            }
            $recipeName = $request->recipe_name;
            if (!empty($recipeId) && empty($recipeName)) {
                $recipeName = \App\Models\Master\ProductionRecipe::find($recipeId)?->name;
            }

            $order = ProductionDryingJobOrder::create([
                'company_id' => $request->company_id ?? (auth()->user()?->current_company_id ?? 1),
                'company_location_id' => $request->company_location_id,
                'export_order_id' => $request->export_order_id,
                'production_recipe_id' => $recipeId,
                'recipe_name' => $recipeName,
                'job_order_no' => $uniqueJobOrderNo,
                'job_order_date' => $request->job_order_date,
                'ref_no' => $request->ref_no,
                'attention_to' => $request->attention_to ?? [],
                'product_id' => $request->product_id,
                'crop_year' => $request->crop_year,
                'crop_year_id' => $cropYearId,
                'moisture_level' => $request->moisture_level,
                'moisture_value' => $request->moisture_value,
                'remarks' => $request->remarks,
                'order_description' => $request->order_description,
                'other_specifications' => $request->other_specifications,
                'status' => 'pending',
                'created_by' => auth()->user()?->id,
            ]);

            // Save items / recipe parameters
            if ($request->has('items') && is_array($request->items)) {
                foreach ($request->items as $item) {
                    if (empty($item['key'])) continue;
                    $order->items()->create([
                        'production_attribute_id' => !empty($item['production_attribute_id']) ? $item['production_attribute_id'] : null,
                        'key' => $item['key'],
                        'type' => $item['type'] ?? 'text',
                        'value' => $item['value'] ?? '',
                        'created_by' => auth()->user()?->id,
                    ]);
                }
            }

            // Save specifications (like milling job order)
            if ($request->has('specifications') && is_array($request->specifications)) {
                foreach ($request->specifications as $spec) {
                    if (empty($spec['spec_name'])) continue;
                    $order->specifications()->create([
                        'product_slab_type_id' => $spec['product_slab_type_id'] ?? null,
                        'spec_name' => $spec['spec_name'],
                        'spec_value' => $spec['spec_value'] ?? '0',
                        'uom' => $spec['uom'] ?? null,
                        'value_type' => $spec['value_type'] ?? 'min',
                        'created_by' => auth()->user()?->id,
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => 'Drying Job Order created successfully.',
                'order_id' => $order->id,
                'redirect_url' => route('production-drying-job-order.index')
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Failed to create Drying Job Order: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        $order = ProductionDryingJobOrder::with(['companyLocation', 'exportOrder', 'product', 'items', 'specifications', 'creator', 'productionRecipe'])->findOrFail($id);
        return view("management.production.drying.view_drying_job_order", compact('order'));
    }

    public function edit($id)
    {
        $order = ProductionDryingJobOrder::with(['items', 'specifications', 'exportOrder', 'product', 'productionRecipe'])->findOrFail($id);
        $users = User::get();
        $products = Product::where('status', 'active')->get();
        $attributes = ProdctionAttribute::where('status', 'active')->orderBy('key')->get();
        $cropYears = CropYear::where('status', 'active')->get();
        $exportOrders = \App\Models\Export\ExportOrder::where('am_approval_status', 'approved')->latest()->get();
        $productionRecipes = \App\Models\Master\ProductionRecipe::where('status', 'active')
            ->when($order->product_id, function($q) use ($order) {
                return $q->where('commodity_id', $order->product_id);
            })->get();

        $user = auth()->user();
        $locationIds = function_exists('getUserCurrentCompanyLocations') ? getUserCurrentCompanyLocations() : [];
        if ($user && ($user->user_type === 'super-admin' || empty($locationIds))) {
            $companyLocations = CompanyLocation::where('status', 'active')->get();
        } else {
            $companyLocations = CompanyLocation::whereIn('id', $locationIds)->where('status', 'active')->get();
        }

        return view("management.production.drying.edit", compact(
            'order',
            'attributes',
            'products',
            'users',
            'cropYears',
            'exportOrders',
            'productionRecipes',
            'companyLocations'
        ));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'job_order_date' => 'required|date',
            'company_location_id' => 'required',
            'product_id' => 'required',
            'crop_year' => 'nullable|string',
            'moisture_level' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $order = ProductionDryingJobOrder::findOrFail($id);

            $cropYearId = null;
            if ($request->filled('crop_year')) {
                $cy = CropYear::where('name', $request->crop_year)->first();
                $cropYearId = $cy?->id;
            }

            $recipeId = $request->production_recipe_id;
            if ($recipeId === 'custom' || empty($recipeId)) {
                $recipeId = null;
            }
            $recipeName = $request->recipe_name;
            if (!empty($recipeId) && empty($recipeName)) {
                $recipeName = \App\Models\Master\ProductionRecipe::find($recipeId)?->name;
            }

            $order->update([
                'company_location_id' => $request->company_location_id,
                'export_order_id' => $request->export_order_id,
                'production_recipe_id' => $recipeId,
                'recipe_name' => $recipeName,
                'job_order_date' => $request->job_order_date,
                'ref_no' => $request->ref_no,
                'attention_to' => $request->attention_to ?? [],
                'product_id' => $request->product_id,
                'crop_year' => $request->crop_year,
                'crop_year_id' => $cropYearId,
                'moisture_level' => $request->moisture_level,
                'moisture_value' => $request->moisture_value,
                'remarks' => $request->remarks,
                'order_description' => $request->order_description,
                'other_specifications' => $request->other_specifications,
                'status' => $request->status ?? $order->status,
                'updated_by' => auth()->user()?->id,
            ]);

            // Sync items
            $order->items()->delete();
            if ($request->has('items') && is_array($request->items)) {
                foreach ($request->items as $item) {
                    if (empty($item['key'])) continue;
                    $order->items()->create([
                        'production_attribute_id' => !empty($item['production_attribute_id']) ? $item['production_attribute_id'] : null,
                        'key' => $item['key'],
                        'type' => $item['type'] ?? 'text',
                        'value' => $item['value'] ?? '',
                        'created_by' => $order->created_by ?? auth()->user()?->id,
                        'updated_by' => auth()->user()?->id,
                    ]);
                }
            }

            // Sync specifications
            $order->specifications()->delete();
            if ($request->has('specifications') && is_array($request->specifications)) {
                foreach ($request->specifications as $spec) {
                    if (empty($spec['spec_name'])) continue;
                    $order->specifications()->create([
                        'product_slab_type_id' => $spec['product_slab_type_id'] ?? null,
                        'spec_name' => $spec['spec_name'],
                        'spec_value' => $spec['spec_value'] ?? '0',
                        'uom' => $spec['uom'] ?? null,
                        'value_type' => $spec['value_type'] ?? 'min',
                        'created_by' => $order->created_by ?? auth()->user()?->id,
                        'updated_by' => auth()->user()?->id,
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => 'Drying Job Order updated successfully.',
                'order_id' => $order->id,
                'redirect_url' => route('production-drying-job-order.index')
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Failed to update Drying Job Order: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $order = ProductionDryingJobOrder::findOrFail($id);
            $order->delete();

            return response()->json([
                'success' => 'Drying Job Order deleted successfully.'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to delete Drying Job Order: ' . $e->getMessage()
            ], 500);
        }
    }

    public static function generateUniqueNumber($dateParam = null, $locationParam = null)
    {
        try {
            $datePart = $dateParam ? Carbon::parse($dateParam)->format('m-d-Y') : date('m-d-Y');
        } catch (\Exception $e) {
            $datePart = date('m-d-Y');
        }

        if (empty($locationParam)) {
            $locationParam = request('company_location_id') ?? request('location_id') ?? auth()->user()?->company_location_id;
        }

        $locationCode = 'LOC';
        if ($locationParam) {
            if ($locationParam instanceof CompanyLocation) {
                $locationCode = $locationParam->code ?: 'LOC';
            } elseif (is_numeric($locationParam)) {
                $loc = CompanyLocation::find($locationParam);
                $locationCode = $loc?->code ?: 'LOC';
            } elseif (is_string($locationParam)) {
                $loc = CompanyLocation::where('code', $locationParam)->first();
                $locationCode = $loc ? $loc->code : $locationParam;
            }
        }

        $locationCode = strtoupper(trim($locationCode ?: 'LOC'));
        $prefix = $locationCode . '-JO-DRY';

        $searchPattern = $prefix . '-%-' . $datePart;

        $existing = ProductionDryingJobOrder::withTrashed()
            ->where('job_order_no', 'like', $searchPattern)
            ->lockForUpdate()
            ->pluck('job_order_no')
            ->toArray();

        $maxNumber = 0;
        $regex = '/^' . preg_quote($prefix, '/') . '-(\d+)-' . preg_quote($datePart, '/') . '$/';
        foreach ($existing as $no) {
            if (preg_match($regex, $no, $matches)) {
                $num = (int) $matches[1];
                if ($num > $maxNumber) {
                    $maxNumber = $num;
                }
            }
        }

        $newNumber = $maxNumber + 1;
        $candidate = $prefix . '-' . str_pad($newNumber, 3, '0', STR_PAD_LEFT) . '-' . $datePart;

        while (in_array($candidate, $existing) || ProductionDryingJobOrder::withTrashed()->where('job_order_no', $candidate)->exists()) {
            $newNumber++;
            $candidate = $prefix . '-' . str_pad($newNumber, 3, '0', STR_PAD_LEFT) . '-' . $datePart;
        }

        return $candidate;
    }

    public function getNumber(Request $request)
    {
        $dateParam = $request->job_order_date ?? $request->date ?? date('Y-m-d');
        $locationParam = $request->company_location_id ?? $request->location_id ?? null;
        $jobOrderNo = self::generateUniqueNumber($dateParam, $locationParam);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'job_order_no' => $jobOrderNo
            ]);
        }

        return $jobOrderNo;
    }

    public function getRecipesByProduct($productId)
    {
        $recipes = \App\Models\Master\ProductionRecipe::with(['items.attribute'])
            ->where('commodity_id', $productId)
            ->where('status', 'active')
            ->get()
            ->map(function ($recipe) {
                return [
                    'id' => $recipe->id,
                    'name' => $recipe->name,
                    'description' => $recipe->description,
                    'crop_year_id' => $recipe->crop_year_id,
                    'items' => $recipe->items->map(function ($item, $idx) {
                        $key = $item->attribute->key ?? $item->key ?? '';
                        if (empty($key) && !empty($item->production_attribute_id)) {
                            $attr = \App\Models\ProdctionAttribute::find($item->production_attribute_id);
                            $key = $attr?->key ?? '';
                        }
                        if (empty($key)) {
                            $key = 'Parameter #' . ($idx + 1);
                        }
                        return [
                            'production_attribute_id' => $item->production_attribute_id,
                            'key' => $key,
                            'type' => $item->attribute->type ?? $item->type ?? 'text',
                            'slug' => $item->attribute->slug ?? $item->slug ?? '',
                            'value' => $item->value ?? '',
                        ];
                    })->values(),
                ];
            });

        return response()->json([
            'success' => true,
            'recipes' => $recipes,
        ]);
    }

    public function getRecipeDetails($recipeId)
    {
        $recipe = \App\Models\Master\ProductionRecipe::with(['items.attribute'])->find($recipeId);
        if (!$recipe) {
            return response()->json([
                'success' => false,
                'message' => 'Recipe not found.'
            ], 404);
        }

        $items = $recipe->items->map(function ($item, $idx) {
            $key = $item->attribute->key ?? $item->key ?? '';
            if (empty($key) && !empty($item->production_attribute_id)) {
                $attr = \App\Models\ProdctionAttribute::find($item->production_attribute_id);
                $key = $attr?->key ?? '';
            }
            if (empty($key)) {
                $key = 'Parameter #' . ($idx + 1);
            }
            return [
                'production_attribute_id' => $item->production_attribute_id,
                'key' => $key,
                'type' => $item->attribute->type ?? $item->type ?? 'text',
                'slug' => $item->attribute->slug ?? $item->slug ?? '',
                'value' => $item->value ?? '',
            ];
        })->values();

        return response()->json([
            'success' => true,
            'recipe' => [
                'id' => $recipe->id,
                'name' => $recipe->name,
                'commodity_id' => $recipe->commodity_id,
                'items' => $items,
            ],
        ]);
    }
}
