<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\ProductionRecipeRequest;
use App\Models\Master\CropYear;
use App\Models\Master\ProductionRecipe;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductionRecipeController extends Controller
{
    /**
     * Display a listing of production recipes.
     */
    public function index()
    {
        return view('management.master.production_recipe.index');
    }

    /**
     * Get paginated production recipes list for AJAX table.
     */
    public function getList(Request $request)
    {
        $production_recipes = ProductionRecipe::with(['commodity', 'cropYear', 'items'])
            ->when($request->filled('search'), function ($q) use ($request) {
                $searchTerm = '%' . $request->search . '%';
                $q->where(function ($sq) use ($searchTerm) {
                    $sq->where('name', 'like', $searchTerm)
                        ->orWhere('description', 'like', $searchTerm)
                        ->orWhereHas('commodity', function ($cq) use ($searchTerm) {
                            $cq->where('name', 'like', $searchTerm);
                        })
                        ->orWhereHas('items', function ($iq) use ($searchTerm) {
                            $iq->where('key', 'like', $searchTerm)
                                ->orWhere('slug', 'like', $searchTerm)
                                ->orWhere('value', 'like', $searchTerm);
                        });
                });
            })
            ->when($request->filled('company_id'), function ($q) use ($request) {
                $q->where('company_id', $request->company_id);
            })
            ->latest()
            ->paginate($request->input('per_page', 25));

        return view('management.master.production_recipe.getList', compact('production_recipes'));
    }

    /**
     * Show the form for creating a new production recipe.
     */
    public function create()
    {
        $commodities = Product::where('status', 1)
            ->where('product_type', 'raw_material')
            ->orderBy('name')
            ->get();

        if ($commodities->isEmpty()) {
            $commodities = Product::where('status', 1)->orderBy('name')->get();
        }

        $cropYears = CropYear::where('status', 'active')->orderBy('name', 'desc')->get();

        return view('management.master.production_recipe.create', compact('commodities', 'cropYears'));
    }

    /**
     * Store a newly created production recipe in storage.
     */
    public function store(ProductionRecipeRequest $request)
    {
        $data = $request->validated();
        $items = $this->formatItems($request);
        unset($data['items'], $data['parameters']);

        $production_recipe = ProductionRecipe::create($data);

        // Store parameter items into production_recipes_items table
        foreach ($items as $item) {
            $production_recipe->items()->create([
                'key' => $item['key'],
                'value' => $item['value'] ?? null,
                'type' => $item['type'] ?? 'text',
                // slug is automatically generated unique on create
            ]);
        }

        return response()->json([
            'success' => 'Production Recipe created successfully.',
            'data' => $production_recipe->load('items')
        ], 201);
    }

    /**
     * Show the form for editing the specified production recipe.
     */
    public function edit($id)
    {
        $production_recipe = ProductionRecipe::with('items')->findOrFail($id);

        $commodities = Product::where('status', 1)
            ->where('product_type', 'raw_material')
            ->orderBy('name')
            ->get();

        if ($commodities->isEmpty()) {
            $commodities = Product::where('status', 1)->orderBy('name')->get();
        }

        $cropYears = CropYear::where('status', 'active')->orderBy('name', 'desc')->get();

        return view('management.master.production_recipe.edit', compact('production_recipe', 'commodities', 'cropYears'));
    }

    /**
     * Update the specified production recipe in storage.
     */
    public function update(ProductionRecipeRequest $request, ProductionRecipe $production_recipe)
    {
        $data = $request->validated();
        $items = $this->formatItems($request);
        unset($data['items'], $data['parameters']);

        $production_recipe->update($data);

        // Sync items in production_recipes_items table
        $existingItemIds = [];
        foreach ($items as $itemData) {
            if (!empty($itemData['id'])) {
                $item = $production_recipe->items()->find($itemData['id']);
                if ($item) {
                    $item->update([
                        'key' => $itemData['key'],
                        'value' => $itemData['value'] ?? null,
                        'type' => $itemData['type'] ?? 'text',
                    ]);
                    $existingItemIds[] = $item->id;
                    continue;
                }
            }

            // New item - auto unique slug generated on create
            $newItem = $production_recipe->items()->create([
                'key' => $itemData['key'],
                'value' => $itemData['value'] ?? null,
                'type' => $itemData['type'] ?? 'text',
            ]);
            $existingItemIds[] = $newItem->id;
        }

        // Delete items that were removed in the form
        $production_recipe->items()->whereNotIn('id', $existingItemIds)->delete();

        return response()->json([
            'success' => 'Production Recipe updated successfully.',
            'data' => $production_recipe->load('items')
        ], 200);
    }

    /**
     * Remove the specified production recipe from storage.
     */
    public function destroy($id)
    {
        $production_recipe = ProductionRecipe::findOrFail($id);
        $production_recipe->items()->delete();
        $production_recipe->delete();

        return response()->json([
            'success' => 'Production Recipe deleted successfully.'
        ], 200);
    }

    /**
     * Format and clean item parameters from request.
     */
    protected function formatItems(Request $request): array
    {
        $rawItems = $request->input('items', $request->input('parameters', []));
        if (!is_array($rawItems)) {
            return [];
        }

        $cleaned = [];
        foreach ($rawItems as $item) {
            $key = trim($item['key'] ?? '');
            $val = trim($item['value'] ?? '');
            $type = trim($item['type'] ?? 'text');
            if (empty($type)) {
                $type = 'text';
            }

            if (!empty($key)) {
                $cleaned[] = [
                    'id' => !empty($item['id']) ? (int) $item['id'] : null,
                    'key' => $key,
                    'value' => $val,
                    'type' => $type,
                ];
            }
        }

        return $cleaned;
    }
}
