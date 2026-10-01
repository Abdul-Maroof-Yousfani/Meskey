<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\ProductionRecipeRequest;
use App\Models\Master\CropYear;
use App\Models\Master\ProductionRecipe;
use App\Models\Product;
use App\Models\ProdctionAttribute;
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
        $production_recipes = ProductionRecipe::with(['commodity', 'cropYear', 'items.attribute'])
            ->when($request->filled('search'), function ($q) use ($request) {
                $searchTerm = '%' . $request->search . '%';
                $q->where(function ($sq) use ($searchTerm) {
                    $sq->where('name', 'like', $searchTerm)
                        ->orWhere('description', 'like', $searchTerm)
                        ->orWhereHas('commodity', function ($cq) use ($searchTerm) {
                            $cq->where('name', 'like', $searchTerm);
                        })
                        ->orWhereHas('items', function ($iq) use ($searchTerm) {
                            $iq->where('value', 'like', $searchTerm)
                                ->orWhereHas('attribute', function ($aq) use ($searchTerm) {
                                    $aq->where('key', 'like', $searchTerm)
                                        ->orWhere('slug', 'like', $searchTerm);
                                });
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

        $attributes = ProdctionAttribute::where('status', 'active')->orderBy('key')->get();

        if ($commodities->isEmpty()) {
            $commodities = Product::where('status', 1)->orderBy('name')->get();
        }

        $cropYears = CropYear::where('status', 'active')->orderBy('name', 'desc')->get();

        return view('management.master.production_recipe.create', compact('commodities', 'cropYears', 'attributes'));
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
            $attrId = $this->resolveAttributeId($item);
            if (!$attrId) continue;

            $production_recipe->items()->create([
                'production_attribute_id' => $attrId,
                'value' => $item['value'] ?? null,
            ]);
        }

        return response()->json([
            'success' => 'Production Recipe created successfully.',
            'data' => $production_recipe->load('items.attribute')
        ], 201);
    }

    /**
     * Show the form for editing the specified production recipe.
     */
    public function edit($id)
    {
        $production_recipe = ProductionRecipe::with('items.attribute')->findOrFail($id);

        $commodities = Product::where('status', 1)
            ->where('product_type', 'raw_material')
            ->orderBy('name')
            ->get();

        if ($commodities->isEmpty()) {
            $commodities = Product::where('status', 1)->orderBy('name')->get();
        }

        $cropYears = CropYear::where('status', 'active')->orderBy('name', 'desc')->get();
        $attributes = ProdctionAttribute::where('status', 'active')->orderBy('key')->get();

        return view('management.master.production_recipe.edit', compact('production_recipe', 'commodities', 'cropYears', 'attributes'));
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
            $attrId = $this->resolveAttributeId($itemData);
            if (!$attrId) continue;

            if (!empty($itemData['id'])) {
                $item = $production_recipe->items()->find($itemData['id']);
                if ($item) {
                    $item->update([
                        'production_attribute_id' => $attrId,
                        'value' => $itemData['value'] ?? null,
                    ]);
                    $existingItemIds[] = $item->id;
                    continue;
                }
            }

            // New item
            $newItem = $production_recipe->items()->create([
                'production_attribute_id' => $attrId,
                'value' => $itemData['value'] ?? null,
            ]);
            $existingItemIds[] = $newItem->id;
        }

        // Delete items that were removed in the form
        $production_recipe->items()->whereNotIn('id', $existingItemIds)->delete();

        return response()->json([
            'success' => 'Production Recipe updated successfully.',
            'data' => $production_recipe->load('items.attribute')
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
            $attrId = !empty($item['production_attribute_id']) ? (int) $item['production_attribute_id'] : null;
            $key    = trim($item['key'] ?? '');
            $type   = trim($item['type'] ?? 'text');
            $val    = trim($item['value'] ?? '');

            // Include row if it has an attribute ID, a custom key, or a value
            if (!empty($attrId) || !empty($key) || !empty($val)) {
                $cleaned[] = [
                    'id'                     => !empty($item['id']) ? (int) $item['id'] : null,
                    'production_attribute_id' => $attrId,
                    'key'                    => $key,
                    'type'                   => $type,
                    'value'                  => $val,
                ];
            }
        }

        return $cleaned;
    }

    /**
     * Resolve (or auto-create) a ProdctionAttribute record for the given item.
     * - If the item already has a production_attribute_id, return it directly.
     * - If the item has a custom key (no attribute id), firstOrCreate the attribute
     *   in prodction_attribute and return the new/existing id.
     * Returns null if neither an id nor a key is present.
     */
    protected function resolveAttributeId(array $item): ?int
    {
        // Already linked to an existing attribute
        if (!empty($item['production_attribute_id'])) {
            return (int) $item['production_attribute_id'];
        }

        // Custom key supplied — create the attribute if it doesn't exist yet
        $key = trim($item['key'] ?? '');
        if (empty($key)) {
            return null;
        }

        $type = trim($item['type'] ?? 'text');
        $slug = \Illuminate\Support\Str::slug($key, '_');

        $attribute = ProdctionAttribute::firstOrCreate(
            ['slug' => $slug],
            [
                'key'         => $key,
                'type'        => $type,
                'for_general' => 0,
                'status'      => 'active',
                'created_by'  => is_numeric(auth()->id()) ? (int) auth()->id() : null,
            ]
        );

        return $attribute->id;
    }
}
