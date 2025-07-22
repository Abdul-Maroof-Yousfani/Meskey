<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Product\{StoreProductRequest, UpdateProductRequest};
use App\Models\{Product, UnitOfMeasure, Category};
use App\Models\Master\Account\Account;
use Illuminate\Http\Request;


class ProductController extends Controller
{



    public function index()
    {
        return view('management.master.product.index');
    }

    /**
     * Get list of categories.
     */
    public function getList(Request $request)
    {
        $UnitOfMeasures = Product::when($request->filled('search'), function ($q) use ($request) {
            $searchTerm = '%' . $request->search . '%';
            return $q->where(function ($sq) use ($searchTerm) {
                $sq->where('name', 'like', $searchTerm);
            });
        })
            ->where('company_id', $request->company_id)

            ->latest()
            ->paginate(request('per_page', 25));

        return view('management.master.product.getList', compact('UnitOfMeasures'));
    }

    public function getItems(Request $request)
    {
        try {
            $category_id = $request->query('category_id');

            // Fetch categories based on category_type
            $products = Product::with('unitOfMeasure')->where('category_id', $category_id)->get();

            return response()->json([
                'success' => true,
                'products' => $products
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
    public function create(Request $request)
    {
        $categories = Category::where('company_id', $request->company_id)->get();
        $units = UnitOfMeasure::where('company_id', $request->company_id)->get();

        return view('management.master.product.create', [
            'categories' => $categories,
            'units' => $units,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProductRequest $request)
    {
        $data = $request->all();
        $account = Account::create(getParamsForAccountCreation($request->company_id, $request->name, 'Inventory'));

        $data['account_id'] = $account->id;
        $UnitOfMeasure = Product::create($data);

        return response()->json(['success' => 'Product created successfully.', 'data' => $UnitOfMeasure], 201);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, $id)
    {


        $product = Product::findOrFail($id);


        $categories = Category::where('company_id', $request->company_id)->get();
        $units = UnitOfMeasure::where('company_id', $request->company_id)->get();


        return view('management.master.product.edit', [
            'categories' => $categories,
            'units' => $units,
            'product' => $product,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProductRequest $request, Product $product)
    {
        $data = $request->validated();

        if (empty($product->account_id)) {
            $account = Account::create(getParamsForAccountCreation(
                $request->company_id,
                $request->name,
                'Inventory'
            ));
            $data['account_id'] = $account->id;
        } else {
            if ($product->name !== $request->name) {
                Account::where('id', $product->account_id)
                    ->update(['name' => $request->name]);
            }
        }

        $product->update($data);

        return response()->json([
            'success' => 'Product updated successfully.',
            'data' => $product
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product)
    {

        $product->delete();
        return response()->json(['success' => 'Product deleted successfully.', 'data' => $product], 200);
    }
}
