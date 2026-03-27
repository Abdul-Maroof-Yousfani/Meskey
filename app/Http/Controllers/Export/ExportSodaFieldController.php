<?php

namespace App\Http\Controllers\Export;

use App\Http\Controllers\Controller;
use App\Models\BagCondition;
use App\Models\BagPacking;
use App\Models\BagType;
use App\Models\Export\ExportSodaField;
use App\Models\Export\IncoTerm;
use App\Models\Export\ModeOfTerm;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Illuminate\Database\QueryException;
use App\Models\Master\Customer;
use App\Models\Master\Brands;
use App\Models\Master\Color;

class ExportSodaFieldController extends Controller
{
    public function index(Request $request): View
    {
        try {
            $export_soda_fields = ExportSodaField::orderBy('id', 'ASC')->paginate(0);
        } catch (QueryException $e) {
            $export_soda_fields = collect();
        }

        return view('management.export.export-soda-field.index', compact('export_soda_fields'))->with('i', ($request->input('page', 1) - 1) * 5);
    }

    public function getExportSodaFieldTable(Request $request)
    {
        $export_soda_fields = ExportSodaField::with(['product', 'buyer'])
            ->when($request->filled('search'), function ($q) use ($request) {
                $searchTerm = '%' . $request->search . '%';
                return $q->where(function ($sq) use ($searchTerm) {
                    $sq->where('reference', 'like', $searchTerm);
                });
            })
            ->latest()
            ->paginate(request('per_page', 25));

        return view('management.export.export-soda-field.getList', compact('export_soda_fields'));
    }

    public function create(): View
    {
        try {
            $users = Customer::get(); // buyer
            $products = Product::where('status', 1)->get(); // commodity
            $bagPackings = BagPacking::where('status', 1)->get(); // packing
            $incoterms = IncoTerm::where('status', 1)->get(); // price 
            $modeofterms = ModeOfTerm::where('status', 1)->get(); // optional payment term

            $bagTypes = BagType::where('status', 1)->get();
            $bagConditions = BagCondition::where('status', 1)->get();
            $brands = Brands::where('status', 1)->get();
            $bagColors = Color::where('status', 1)->get();
        } catch (QueryException $e) {
            $users = $products = $bagPackings = $incoterms = $modeofterms = collect();
            $bagTypes = $bagConditions = $brands = $bagColors = collect();
        }

        return view('management.export.export-soda-field.create', compact(
            'users',
            'products',
            'bagPackings',
            'incoterms',
            'modeofterms',
            'bagTypes',
            'bagConditions',
            'brands',
            'bagColors'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'reference' => 'required',
            'buyer_id'  => 'required',
            'product_id' => 'required',
        ]);

        DB::beginTransaction();

        try {
            $data = $request->only([
                'reference', 'buyer_id', 'product_id', 'bag_packing_id',
                'incoterm_id', 'price_per_kg', 'price_per_mound', 'price_per_100_kg',
                'quantity_in_kg', 'quantity_in_ton', 'mode_of_term_id', 'shipment_period',
                'commission', 'additional_info'
            ]);

            $company_id = session('company_id') ?? auth()->user()->company_id ?? 1; // Fallback to 1 if not found

            ExportSodaField::create(array_merge($data, [
                'created_by' => auth()->user()->id,
                'company_id' => $company_id, // assuming company attached
            ]));

            DB::commit();

            return response()->json([
                'success' => 'Export Soda Field created successfully',
            ], 201);

        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'success' => 'Something went wrong',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function show($id): View
    {
        try {
            $exportSodaField = ExportSodaField::with(['product', 'buyer', 'packing', 'incoterm', 'modeOfTerm'])->findOrFail($id);
            $users = Customer::get(); 
            $products = Product::where('status', 1)->get(); 
            $bagPackings = BagPacking::where('status', 1)->get(); 
            $incoterms = IncoTerm::where('status', 1)->get();  
            $modeofterms = ModeOfTerm::where('status', 1)->get(); 

            $bagTypes = BagType::where('status', 1)->get();
            $bagConditions = BagCondition::where('status', 1)->get();
            $brands = Brands::where('status', 1)->get();
            $bagColors = Color::where('status', 1)->get();
        } catch (QueryException $e) {
            $exportSodaField = new ExportSodaField();
            $users = $products = $bagPackings = $incoterms = $modeofterms = collect();
            $bagTypes = $bagConditions = $brands = $bagColors = collect();
        }

        return view('management.export.export-soda-field.show', compact('exportSodaField', 'users', 'products', 'bagPackings', 'incoterms', 'modeofterms', 'bagTypes', 'bagConditions', 'brands', 'bagColors'));
    }

    public function edit($id): View
    {
        try {
            $exportSodaField = ExportSodaField::findOrFail($id);
            $users = Customer::get(); 
            $products = Product::where('status', 1)->get(); 
            $bagPackings = BagPacking::where('status', 1)->get(); 
            $incoterms = IncoTerm::where('status', 1)->get();  
            $modeofterms = ModeOfTerm::where('status', 1)->get(); 

            $bagTypes = BagType::where('status', 1)->get();
            $bagConditions = BagCondition::where('status', 1)->get();
            $brands = Brands::where('status', 1)->get();
            $bagColors = Color::where('status', 1)->get();
        } catch (QueryException $e) {
            $exportSodaField = new ExportSodaField();
            $users = $products = $bagPackings = $incoterms = $modeofterms = collect();
            $bagTypes = $bagConditions = $brands = $bagColors = collect();
        }

        return view('management.export.export-soda-field.edit', compact(
            'exportSodaField',
            'users',
            'products',
            'bagPackings',
            'incoterms',
            'modeofterms',
            'bagTypes',
            'bagConditions',
            'brands',
            'bagColors'
        ));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'reference' => 'required',
            'buyer_id'  => 'required',
            'product_id' => 'required',
        ]);

        DB::beginTransaction();

        try {
            $exportSodaField = ExportSodaField::findOrFail($id);

            $data = $request->only([
                'reference', 'buyer_id', 'product_id', 'bag_packing_id',
                'incoterm_id', 'price_per_kg', 'price_per_mound', 'price_per_100_kg',
                'quantity_in_kg', 'quantity_in_ton', 'mode_of_term_id', 'shipment_period',
                'commission', 'additional_info'
            ]);

            $exportSodaField->update($data);

            DB::commit();

            return response()->json([
                'success' => 'Export Soda Field updated successfully',
            ], 200);

        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'success' => 'Something went wrong',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy($id)
    {
        DB::beginTransaction();

        try {
            $exportSodaField = ExportSodaField::findOrFail($id);
            $exportSodaField->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Export Soda Field deleted successfully.',
            ], 200);

        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete Export Soda Field',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
