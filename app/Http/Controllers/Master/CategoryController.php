<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\{Category, Product, UnitOfMeasure, ProductHeadType, CategoryHead};
use App\Models\Master\Account\Account;
use Illuminate\Http\{Request, JsonResponse};
use App\Http\Requests\Category\CategoryRequest;
use Illuminate\Support\Facades\DB;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('management.master.category.index');
    }

    /**
     * Get list of categories.
     */
    public function getList(Request $request)
    {
        $categories = Category::when($request->filled('search'), function ($q) use ($request) {
            $searchTerm = '%' . $request->search . '%';
            return $q->where(function ($sq) use ($searchTerm) {
                $sq->where('name', 'like', $searchTerm);
            });
        })
        ->with('parent') // Eager load parent category
        ->latest()
        ->paginate(request('per_page', 25));

        return view('management.master.category.getList', compact('categories'));
    }

    public function getCategories(Request $request)
    {
        try {
            $categoryType = $request->query('category_type');
            
            // Fetch categories based on category_type
            $query = Category::select('id', 'name');
            
            if ($categoryType) {
                $query->where('category_type', $categoryType);
            }
            
            $categories = $query->get();
            
            return response()->json([
                'success' => true,
                'categories' => $categories
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching categories'
            ], 500);
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $categories = Category::all(); // Fetch all categories for parent dropdown
        return view('management.master.category.create', compact('categories'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CategoryRequest $request)
    {
       
        $data = $request->validated();
    //dd($data);
        $category = Category::create($request->all());

        return response()->json(['success' => 'Category created successfully.', 'data' => $category], 201);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $category = Category::findOrFail($id);
        $categories = Category::where('id', '!=', $id)->get(); // Exclude current category from parent list
        return view('management.master.category.edit', compact('category', 'categories'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(CategoryRequest $request, Category $category): JsonResponse
    {
        $data = $request->validated();
        $category->update($data);

        return response()->json(['success' => 'Category updated successfully.', 'data' => $category], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Category $category): JsonResponse
    {
        $category->delete();
        return response()->json(['success' => 'Category deleted successfully.'], 200);
    }

    /**
     * Show UI for configuring Head, B2, and By products for the given category.
     */
    public function heads($id)
    {
        $category = Category::with('parent')->findOrFail($id);
        $companyId = auth()->check() ? auth()->user()->current_company_id : ($category->company_id ?? null);

        // Fetch dynamic head types from schema
        $headTypes = ProductHeadType::getActiveTypes($companyId);

        // Fetch blueprint heads for this category from category_heads table
        $categoryHeads = CategoryHead::where('category_id', $category->id)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        // Build dynamic sections map
        $sections = [];
        foreach ($headTypes as $type) {
            $sections[$type->slug] = [
                'type_id' => $type->id,
                'slug'    => $type->slug,
                'title'   => $type->name,
                'items'   => $categoryHeads->where('head_type_slug', $type->slug)->values(),
            ];
        }

        return view('management.master.category.heads', compact('category', 'sections'));
    }

    /**
     * Store or update blueprint heads for the given category in category_heads table.
     */
    public function storeHeads(Request $request, $id): JsonResponse
    {
        $category = Category::findOrFail($id);
        $companyId = auth()->check() ? auth()->user()->current_company_id : ($category->company_id ?? 1);

        $flagsData = $request->input('flags', []);
        $deletedIds = $request->input('deleted_ids', []);

        // Fetch dynamic head types for human-readable error titles
        $headTypes = ProductHeadType::getActiveTypes($companyId);
        $headTypeMap = $headTypes->pluck('name', 'slug')->toArray();

        // 1. Validation: Type-Wise Uniqueness & Required Name Check
        foreach ($flagsData as $flag => $group) {
            $isEnabled = !empty($group['enabled']) && ($group['enabled'] == '1' || $group['enabled'] === true);
            if (!$isEnabled || empty($group['items']) || !is_array($group['items'])) {
                continue;
            }

            $typeName = $headTypeMap[$flag] ?? ucfirst($flag);
            $seenNames = [];

            foreach ($group['items'] as $item) {
                $name = trim($item['name'] ?? '');
                if ($name === '') {
                    return response()->json([
                        'catchError' => "Please enter head name for all rows in {$typeName} or remove empty rows."
                    ]);
                }

                $lowerName = mb_strtolower($name);
                if (isset($seenNames[$lowerName])) {
                    return response()->json([
                        'catchError' => "Duplicate head name '{$name}' in {$typeName}. Each head name must be unique within its type."
                    ]);
                }
                $seenNames[$lowerName] = true;

                $headId = $item['id'] ?? null;
                // Check if another head already exists with this name under this category and flag in DB
                $existsInDb = CategoryHead::where('category_id', $category->id)
                    ->where('head_type_slug', $flag)
                    ->whereRaw('LOWER(name) = ?', [$lowerName])
                    ->when($headId, fn($q) => $q->where('id', '!=', $headId))
                    ->when(!empty($deletedIds), fn($q) => $q->whereNotIn('id', (array)$deletedIds))
                    ->exists();

                if ($existsInDb) {
                    return response()->json([
                        'catchError' => "Head name '{$name}' already exists in {$typeName} for this category."
                    ]);
                }
            }
        }

        DB::beginTransaction();
        try {
            // Delete any category heads marked for removal
            if (!empty($deletedIds) && is_array($deletedIds)) {
                CategoryHead::whereIn('id', $deletedIds)
                    ->where('category_id', $category->id)
                    ->delete();
            }

            // Loop through each submitted flag dynamically
            foreach ($flagsData as $flag => $group) {
                $isEnabled = !empty($group['enabled']) && ($group['enabled'] == '1' || $group['enabled'] === true);

                if ($isEnabled && !empty($group['items']) && is_array($group['items'])) {
                    $order = 1;
                    foreach ($group['items'] as $item) {
                        $name = trim($item['name'] ?? '');
                        if (empty($name)) {
                            continue;
                        }

                        $headId = $item['id'] ?? null;
                        if ($headId) {
                            $head = CategoryHead::where('category_id', $category->id)->find($headId);
                            if ($head) {
                                $head->update([
                                    'name'           => $name,
                                    'head_type_slug' => $flag,
                                    'sort_order'     => $order++,
                                ]);
                                continue;
                            }
                        }

                        // Create new blueprint CategoryHead
                        CategoryHead::create([
                            'company_id'     => $companyId,
                            'category_id'    => $category->id,
                            'head_type_slug' => $flag,
                            'name'           => $name,
                            'sort_order'     => $order++,
                            'status'         => 'active',
                        ]);
                    }
                }
            }

            DB::commit();
            return response()->json([
                'success' => 'Category heads (' . $category->name . ') updated successfully.'
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'catchError' => 'Failed to save category heads: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get active blueprint heads for the given category grouped by head type.
     */
    public function getCategoryHeads(Request $request, $id): JsonResponse
    {
        $category = Category::findOrFail($id);
        $companyId = auth()->check() ? auth()->user()->current_company_id : ($category->company_id ?? 1);

        $headTypes = ProductHeadType::getActiveTypes($companyId);
        $categoryHeads = CategoryHead::where('category_id', $category->id)
            ->active()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $groups = [];
        foreach ($headTypes as $type) {
            $items = $categoryHeads->where('head_type_slug', $type->slug)->values();
            if ($items->count() > 0) {
                $groups[] = [
                    'type_id' => $type->id,
                    'title'   => $type->name,
                    'slug'    => $type->slug,
                    'items'   => $items->map(fn($h) => [
                        'id'         => $h->id,
                        'name'       => $h->name,
                        'flag'       => $h->head_type_slug,
                        'sort_order' => $h->sort_order,
                    ]),
                ];
            }
        }

        return response()->json([
            'success'     => true,
            'category'    => [
                'id'   => $category->id,
                'name' => $category->name,
            ],
            'total_items' => $categoryHeads->count(),
            'groups'      => $groups,
        ]);
    }
}