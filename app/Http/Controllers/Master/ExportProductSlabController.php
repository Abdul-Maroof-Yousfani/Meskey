<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Master\ProductSlab;
use App\Models\Master\ProductSlabType;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExportProductSlabController extends Controller
{
    public function index()
    {
        return view('management.master.export_product_slab.index');
    }

    public function getList(Request $request)
    {
        $productIds = ProductSlab::exportEnabled()
            ->select('product_id')
            ->when($request->filled('product_id'), function ($q) use ($request) {
                return $q->where('product_id', $request->product_id);
            })
            ->when($request->filled('product_slab_type_id'), function ($q) use ($request) {
                return $q->where('product_slab_type_id', $request->product_slab_type_id);
            })
            ->where('company_id', $request->company_id)
            ->groupBy(['product_id', 'created_at'])
            ->latest()
            ->paginate($request->get('per_page', 25));

        $productSlabs = ProductSlab::exportEnabled()
            ->with(['product', 'slabType'])
            ->whereIn('product_id', $productIds->pluck('product_id'))
            ->get()
            ->groupBy('product_id');

        return view('management.master.export_product_slab.getList', [
            'productSlabs' => $productSlabs,
            'paginator' => $productIds,
        ]);
    }

    public function create()
    {
        $products = Product::where('status', 1)->get();
        $slab_types = ProductSlabType::where('status', 'active')->get();

        return view('management.master.export_product_slab.create', compact('products', 'slab_types'));
    }

    public function storeMultiple(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'slabs' => 'required|array',
        ]);

        DB::beginTransaction();
        try {
            $this->syncExportSlabs($request);
            DB::commit();

            return response()->json(['success' => 'Export product slabs created successfully.'], 201);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json(['error' => 'Failed to create export product slabs: ' . $e->getMessage()], 500);
        }
    }

    public function edit($productId)
    {
        $product = Product::findOrFail($productId);
        $slab_types = ProductSlabType::where('status', 'active')->get();
        $productSlabs = ProductSlab::where('product_id', $productId)->get();

        return view('management.master.export_product_slab.edit', compact('product', 'slab_types', 'productSlabs'));
    }

    public function updateMultiple(Request $request, $productId)
    {
        $request->merge(['product_id' => $productId]);
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'slabs' => 'required|array',
        ]);

        DB::beginTransaction();
        try {
            $this->syncExportSlabs($request);
            DB::commit();

            return response()->json(['success' => 'Export product slabs updated successfully.'], 200);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json(['error' => 'Failed to update export product slabs: ' . $e->getMessage()], 500);
        }
    }

    private function syncExportSlabs(Request $request): void
    {
        $companyId = $request->company_id ?? auth()->user()->current_company_id;

        foreach ($request->slabs as $slabTypeId => $slabData) {
            $isExportEnabled = isset($slabData['is_export_enable']) && (int) $slabData['is_export_enable'] === 1;
            $prefillSpecValue = $slabData['prefill_spec_value'] ?? null;

            $exists = ProductSlab::where('product_id', $request->product_id)
                ->where('product_slab_type_id', $slabTypeId)
                ->exists();

            if ($exists) {
                ProductSlab::where('product_id', $request->product_id)
                    ->where('product_slab_type_id', $slabTypeId)
                    ->update([
                        'prefill_spec_value' => $prefillSpecValue,
                        'is_export_enable' => $isExportEnabled,
                    ]);
            } elseif ($isExportEnabled || !is_null($prefillSpecValue)) {
                ProductSlab::create([
                    'company_id' => $companyId,
                    'product_id' => $request->product_id,
                    'product_slab_type_id' => $slabTypeId,
                    'prefill_spec_value' => $prefillSpecValue,
                    'is_export_enable' => $isExportEnabled,
                ]);
            }
        }
    }
}
