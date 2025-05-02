<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Master\ArrivalCompulsoryQcParam;
use App\Models\Master\ProductSlab;
use App\Models\Master\QcReliefParameter;
use App\Models\Product;
use Illuminate\Http\Request;

class QcReliefController extends Controller
{
    public function index()
    {
        $products = Product::with('reliefParameters')->get();
        return view('management.master.qc-relief.index', compact('products'));
    }

    public function getParameters(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id'
        ]);

        $product = Product::with('reliefParameters')->find($request->product_id);
        $slabs = ProductSlab::with('slabType')
            ->where('product_id', $request->product_id)
            ->get()
            ->unique('product_slab_type_id');

        // $compulsoryParams = ArrivalCompulsoryQcParam::get();
        $compulsoryParams = collect();

        $compulsoryParameters = $compulsoryParams->map(function ($param) use ($product) {
            $relief = $product->reliefParameters
                ->where('parameter_name', $param->name)
                ->where('parameter_type', 'compulsory')
                ->first();

            return [
                'type' => 'compulsory',
                'name' => $param->name,
                'relief_percentage' => $relief ? $relief->relief_percentage : 0,
                'is_active' => $relief ? $relief->is_active : true
            ];
        });

        $slabParameters = $slabs->map(function ($slab) use ($product) {
            $relief = $product->reliefParameters
                ->where('parameter_name', $slab->slabType->name)
                ->where('parameter_type', 'slab')
                ->first();

            return [
                'type' => 'slab',
                'name' => $slab->slabType->name,
                'max_range' => $slab->max_range,
                'slab_type_id' => $slab->product_slab_type_id,
                'relief_percentage' => $relief ? $relief->relief_percentage : 0,
                'is_active' => $relief ? $relief->is_active : true
            ];
        });

        $parameters = $compulsoryParameters->merge($slabParameters);

        $html = view('management.master.qc-relief.parameters-form', [
            'parameters' => $parameters,
            'product' => $product,
            'hasSlabs' => $slabs->isNotEmpty()
        ])->render();

        return response()->json([
            'success' => true,
            'html' => $html
        ]);
    }

    public function saveParameters(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'parameters' => 'required|array',
            'parameters.*.name' => 'required|string',
            'parameters.*.type' => 'required|in:compulsory,slab',
            'parameters.*.relief_percentage' => 'required|numeric|min:0|max:100',
            'parameters.*.is_active' => 'boolean',
            'parameters.*.slab_type_id' => 'nullable|exists:product_slab_types,id'
        ]);

        foreach ($request->parameters as $param) {
            QcReliefParameter::updateOrCreate(
                [
                    'product_id' => $request->product_id,
                    'parameter_name' => $param['name'],
                    'parameter_type' => $param['type']
                ],
                [
                    'slab_type_id' => $param['slab_type_id'] ?? null,
                    'relief_percentage' => $param['relief_percentage'],
                    'is_active' => $param['is_active'] ?? false
                ]
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'All parameters saved successfully'
        ]);
    }

    function applyQcRelief($productId, $parameterName, $parameterType, $originalValue)
    {
        $relief = QcReliefParameter::where('product_id', $productId)
            ->where('parameter_name', $parameterName)
            ->where('parameter_type', $parameterType)
            ->where('is_active', true)
            ->first();

        if ($relief) {
            return max(0, $originalValue - $relief->relief_percentage);
        }

        return $originalValue;
    }
}
