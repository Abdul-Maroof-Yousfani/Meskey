<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Master\ByProductRate;
use App\Models\Master\ByProductRateItem;
use App\Models\Master\CompanyLocation;
use App\Models\Master\ArrivalLocation;
use App\Models\Master\Plant;
use App\Models\Product;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;

class ByProductRateController extends Controller
{
    public function index()
    {
        return view('management.master.by_product_rate.index');
    }

    public function getList(Request $request)
    {
        $companyId = $request->company_id ?: (auth()->check() ? auth()->user()->current_company_id : null);

        $by_product_rates = ByProductRate::with(['location', 'subLocation', 'plant', 'product', 'items.product'])
            ->when($request->filled('search'), function ($q) use ($request) {
                $searchTerm = '%' . $request->search . '%';
                return $q->where(function ($sq) use ($searchTerm) {
                    $sq->where('title', 'like', $searchTerm)
                       ->orWhereHas('product', fn($pq) => $pq->where('name', 'like', $searchTerm))
                       ->orWhereHas('location', fn($lq) => $lq->where('name', 'like', $searchTerm));
                });
            })
            ->when($request->filled('location_id'), fn($q) => $q->where('location_id', $request->location_id))
            ->when($companyId, function ($q) use ($companyId) {
                return $q->where('company_id', $companyId);
            })
            ->latest('id')
            ->paginate($request->get('per_page', 25));

        return view('management.master.by_product_rate.getList', compact('by_product_rates'));
    }

    public function create()
    {
        $companyLocations = CompanyLocation::where('status', 'active')->orderBy('name')->get();

        // Main Commodities (Only products where parent_id IS NULL)
        $products = Product::where(function ($q) {
                $q->where('status', 'active')->orWhere('status', 1);
            })
            ->whereNull('parent_id')
            ->orderBy('name')
            ->get();

        return view('management.master.by_product_rate.create', compact('companyLocations', 'products'));
    }

    /**
     * AJAX endpoint to fetch B2 and By-Products belonging to the selected main commodity family section-wise.
     */
    public function getByProducts(Request $request, int $productId)
    {
        $locationId = $request->get('location_id');
        $rateId = $request->get('rate_id');

        // Check if rate schedule already exists for this (location_id, product_id)
        $existingRateSchedule = null;
        if ($locationId) {
            $existingRateSchedule = ByProductRate::with(['location', 'product'])
                ->where('location_id', $locationId)
                ->where('product_id', $productId)
                ->when($rateId, fn($q) => $q->where('id', '!=', $rateId))
                ->first();
        }

        // Section 1: B2 Products (Broken / B2 Category)
        $b2Products = Product::where(function ($q) use ($productId) {
                $q->where('parent_id', $productId);
                $childIds = Product::where('parent_id', $productId)->pluck('id');
                if ($childIds->isNotEmpty()) {
                    $q->orWhereIn('parent_id', $childIds);
                }
            })
            ->where('product_category_flags', 'b2')
            ->where(function ($q) {
                $q->where('status', 'active')->orWhere('status', 1);
            })
            ->orderBy('name')
            ->get();

        // Section 2: By-Products (B3, CSR, Bran, Husk, Powder, Choba, etc.)
        $byProducts = Product::where(function ($q) use ($productId) {
                $q->where('parent_id', $productId);
                $childIds = Product::where('parent_id', $productId)->pluck('id');
                if ($childIds->isNotEmpty()) {
                    $q->orWhereIn('parent_id', $childIds);
                }
            })
            ->where('product_category_flags', 'by')
            ->where(function ($q) {
                $q->where('status', 'active')->orWhere('status', 1);
            })
            ->orderBy('name')
            ->get();

        // Fallback: If both empty and chosen product has a parent_id, check sibling products
        if ($b2Products->isEmpty() && $byProducts->isEmpty()) {
            $selectedProduct = Product::find($productId);
            if ($selectedProduct && $selectedProduct->parent_id) {
                $b2Products = Product::where('parent_id', $selectedProduct->parent_id)
                    ->where('product_category_flags', 'b2')
                    ->where(function ($q) {
                        $q->where('status', 'active')->orWhere('status', 1);
                    })
                    ->orderBy('name')
                    ->get();

                $byProducts = Product::where('parent_id', $selectedProduct->parent_id)
                    ->where('product_category_flags', 'by')
                    ->where(function ($q) {
                        $q->where('status', 'active')->orWhere('status', 1);
                    })
                    ->orderBy('name')
                    ->get();
            }
        }

        // Existing rates if editing
        $existingRates = [];
        if ($rateId) {
            $existingRates = ByProductRateItem::where('by_product_rate_id', $rateId)
                ->pluck('rate', 'by_product_id')
                ->toArray();
        }

        return view('management.master.by_product_rate.partials.by_products_rows', compact(
            'b2Products',
            'byProducts',
            'existingRates',
            'existingRateSchedule'
        ));
    }

    /**
     * Check if a ByProductRate schedule already exists for (location_id, product_id)
     */
    public function checkDuplicate(Request $request)
    {
        $locationId = $request->location_id;
        $productId = $request->product_id;
        $rateId = $request->rate_id;

        if (!$locationId || !$productId) {
            return response()->json(['exists' => false]);
        }

        $duplicate = ByProductRate::with(['location', 'product'])
            ->where('location_id', $locationId)
            ->where('product_id', $productId)
            ->when($rateId, fn($q) => $q->where('id', '!=', $rateId))
            ->first();

        if ($duplicate) {
            $productName = $duplicate->product?->name ?? 'Commodity';
            $locationName = $duplicate->location?->name ?? 'Location';

            return response()->json([
                'exists' => true,
                'id' => $duplicate->id,
                'edit_url' => route('by-product-rate.edit', $duplicate->id),
                'message' => "This product ({$productName})'s rates are already created at this location ({$locationName}), please edit them.",
                'product_name' => $productName,
                'location_name' => $locationName,
            ]);
        }

        return response()->json(['exists' => false]);
    }

    public function getSubLocations($locationId)
    {
        $subLocations = ArrivalLocation::where('company_location_id', $locationId)
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name']);
        return response()->json($subLocations);
    }

    public function getPlants($subLocationId)
    {
        $plants = Plant::where('arrival_location_id', $subLocationId)
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name']);
        return response()->json($plants);
    }

    public function store(Request $request)
    {
        $messages = [
            'location_id.required' => 'Please select a Location (Location is mandatory).',
            'location_id.exists' => 'The selected Location is invalid.',
            'product_id.required' => 'Please select a Main Commodity / Product.',
            'product_id.exists' => 'The selected Commodity / Product is invalid.',
            'rates.required' => 'Please provide rates for at least one product.',
        ];

        $data = $request->validate([
            'location_id' => 'required|integer|exists:company_locations,id',
            'sublocation_id' => 'nullable|integer',
            'plant_id' => 'nullable|integer',
            'product_id' => 'required|integer|exists:products,id',
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'status' => 'nullable|integer',
            'rates' => 'required|array|min:1',
            'rates.*' => 'nullable|numeric|min:0',
            'company_id' => 'nullable|integer',
        ], $messages);

        if (!isset($data['company_id'])) {
            $data['company_id'] = auth()->check() ? auth()->user()->current_company_id : null;
        }
        $data['status'] = $data['status'] ?? 1;
        $data['created_by'] = auth()->check() ? (int)(auth()->user()->getAttributes()['id'] ?? 1) : null;

        // Auto generate title if blank
        if (empty($data['title'])) {
            $loc = CompanyLocation::find($data['location_id']);
            $prod = Product::find($data['product_id']);
            $data['title'] = ($prod?->name ?? 'Commodity') . ' Rates - ' . ($loc?->name ?? 'Location');
        }

        // Duplicate check for same Location and Product
        $duplicate = ByProductRate::where('product_id', $data['product_id'])
            ->where('location_id', $data['location_id'])
            ->first();

        if ($duplicate) {
            $product = Product::find($data['product_id']);
            $location = CompanyLocation::find($data['location_id']);
            $pName = $product?->name ?? 'Commodity';
            $lName = $location?->name ?? 'Location';
            $editUrl = route('by-product-rate.edit', $duplicate->id);
            $plainMsg = "This Product ({$pName}) rates on location ({$lName}) are already created. Please edit them.";
            $htmlMsg = "This Product (<b>{$pName}</b>) rates on location (<b>{$lName}</b>) are already created.<div class='mt-2'><a href='{$editUrl}' onclick='window.location.href=\"{$editUrl}\";' class='btn btn-warning font-weight-bold text-dark shadow-sm px-3 py-1' style='text-decoration:none;'><i class='ft-edit mr-1'></i> Edit Existing Rates</a></div>";

            return response()->json([
                'error' => $plainMsg,
                'is_duplicate' => true,
                'duplicate_id' => $duplicate->id,
                'edit_url' => $editUrl,
                'errors' => [
                    'product_id' => [$htmlMsg]
                ]
            ], 422);
        }

        DB::beginTransaction();
        try {
            $rateRecord = ByProductRate::create($data);

            foreach ($data['rates'] as $byProductId => $rateVal) {
                if ($rateVal !== null && $rateVal !== '') {
                    ByProductRateItem::create([
                        'by_product_rate_id' => $rateRecord->id,
                        'by_product_id' => $byProductId,
                        'rate' => (float)$rateVal,
                    ]);
                }
            }

            DB::commit();
            return response()->json([
                'success' => 'Product Rate created successfully.',
                'redirect' => route('by-product-rate.index'),
                'data' => $rateRecord
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to save Product Rate: ' . $e->getMessage()], 500);
        }
    }

    public function edit(int $id)
    {
        $byProductRate = ByProductRate::with(['items.product', 'product', 'location', 'subLocation', 'plant'])->findOrFail($id);
        $companyLocations = CompanyLocation::where('status', 'active')->orderBy('name')->get();
        $arrivalLocations = ArrivalLocation::where('company_location_id', $byProductRate->location_id)->where('status', 'active')->orderBy('name')->get();
        $plants = Plant::where('arrival_location_id', $byProductRate->sublocation_id)->where('status', 'active')->orderBy('name')->get();
        
        $products = Product::where(function ($q) {
                $q->where('status', 'active')->orWhere('status', 1);
            })
            ->where(function ($q) use ($byProductRate) {
                $q->whereNull('parent_id');
                if ($byProductRate->product_id) {
                    $q->orWhere('id', $byProductRate->product_id);
                }
            })
            ->orderBy('name')
            ->get();

        return view('management.master.by_product_rate.edit', compact('byProductRate', 'companyLocations', 'arrivalLocations', 'plants', 'products'));
    }

    public function update(Request $request, int $id)
    {
        $byProductRate = ByProductRate::findOrFail($id);
        $companyId = $byProductRate->company_id ?? (auth()->check() ? auth()->user()->current_company_id : null);

        $messages = [
            'location_id.required' => 'Please select a Location (Location is mandatory).',
            'location_id.exists' => 'The selected Location is invalid.',
            'product_id.required' => 'Please select a Main Commodity / Product.',
            'product_id.exists' => 'The selected Commodity / Product is invalid.',
            'rates.required' => 'Please provide rates for at least one Product.',
        ];

        $data = $request->validate([
            'location_id' => 'required|integer|exists:company_locations,id',
            'sublocation_id' => 'nullable|integer',
            'plant_id' => 'nullable|integer',
            'product_id' => 'required|integer|exists:products,id',
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'status' => 'nullable|integer',
            'rates' => 'required|array|min:1',
            'rates.*' => 'nullable|numeric|min:0',
        ], $messages);

        $data['status'] = $data['status'] ?? 0;
        $data['updated_by'] = auth()->check() ? (int)(auth()->user()->getAttributes()['id'] ?? 1) : null;

        // Auto generate title if blank
        if (empty($data['title'])) {
            $loc = CompanyLocation::find($data['location_id']);
            $prod = Product::find($data['product_id']);
            $data['title'] = ($prod?->name ?? 'Commodity') . ' Rates - ' . ($loc?->name ?? 'Location');
        }

        // Duplicate check excluding current id
        $duplicate = ByProductRate::where('product_id', $data['product_id'])
            ->where('location_id', $data['location_id'])
            ->where('id', '!=', $id)
            ->first();

        if ($duplicate) {
            $product = Product::find($data['product_id']);
            $location = CompanyLocation::find($data['location_id']);
            $pName = $product?->name ?? 'Commodity';
            $lName = $location?->name ?? 'Location';
            $editUrl = route('by-product-rate.edit', $duplicate->id);
            $plainMsg = "This Product ({$pName}) rates on location ({$lName}) are already created. Please edit them.";
            $htmlMsg = "This Product (<b>{$pName}</b>) rates on location (<b>{$lName}</b>) are already created.<div class='mt-2'><a href='{$editUrl}' onclick='window.location.href=\"{$editUrl}\";' class='btn btn-warning font-weight-bold text-dark shadow-sm px-3 py-1' style='text-decoration:none;'><i class='ft-edit mr-1'></i> Edit Existing Rates</a></div>";

            return response()->json([
                'error' => $plainMsg,
                'is_duplicate' => true,
                'duplicate_id' => $duplicate->id,
                'edit_url' => $editUrl,
                'errors' => [
                    'product_id' => [$htmlMsg]
                ]
            ], 422);
        }

        DB::beginTransaction();
        try {
            $byProductRate->update($data);

            // Sync item rates
            ByProductRateItem::where('by_product_rate_id', $id)->delete();
            foreach ($data['rates'] as $byProductId => $rateVal) {
                if ($rateVal !== null && $rateVal !== '') {
                    ByProductRateItem::create([
                        'by_product_rate_id' => $id,
                        'by_product_id' => $byProductId,
                        'rate' => (float)$rateVal,
                    ]);
                }
            }

            DB::commit();
            return response()->json([
                'success' => 'Product Rate updated successfully.',
                'redirect' => route('by-product-rate.index'),
                'data' => $byProductRate
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to update Product Rate: ' . $e->getMessage()], 500);
        }
    }

    public function show(int $id)
    {
        $byProductRate = ByProductRate::with(['location', 'subLocation', 'plant', 'product', 'items.product.unitOfMeasure'])->findOrFail($id);
        
        $b2Items = $byProductRate->items->filter(function ($item) {
            return ($item->product?->product_category_flags ?? '') === 'b2';
        });

        $byProductItems = $byProductRate->items->filter(function ($item) {
            return ($item->product?->product_category_flags ?? '') !== 'b2';
        });

        return view('management.master.by_product_rate.show', compact('byProductRate', 'b2Items', 'byProductItems'));
    }

    public function destroy(int $id)
    {
        $rate = ByProductRate::findOrFail($id);
        $rate->delete();
        return response()->json(['success' => 'Product Rate deleted successfully.']);
    }
}
