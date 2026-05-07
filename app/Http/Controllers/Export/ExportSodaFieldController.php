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
// use App\Models\Master\Color;

class ExportSodaFieldController extends Controller
{
    public function index(Request $request): View
    {
        $export_soda_fields = ExportSodaField::orderBy('id', 'ASC')->paginate(0);

        return view('management.export.export-soda-field.index', compact('export_soda_fields'))->with('i', ($request->input('page', 1) - 1) * 5);
    }

    public function getExportSodaFieldTable(Request $request)
    {
        $export_soda_fields = ExportSodaField::with(['product', 'buyer', 'packingItems'])
            ->when($request->filled('search'), function ($q) use ($request) {
                $searchTerm = '%' . $request->search . '%';
                return $q->where(
                    function ($sq) use ($searchTerm) {
                        $sq->where('reference', 'like', $searchTerm);
                    }
                );
            })
            ->latest()
            ->paginate(request('per_page', 25));

        return view('management.export.export-soda-field.getList', compact('export_soda_fields'));
    }

    public function create(): View
    {
        $users = Customer::get();
        $products = Product::where('status', 1)->get();
        $bagPackings = BagPacking::where('status', 1)->get();
        $incoterms = IncoTerm::where('status', 1)->get();
        $modeofterms = ModeOfTerm::where('status', 1)->get();
        $bagTypes = BagType::where('status', 1)->get();

        return view('management.export.export-soda-field.create', compact(
            'users',
            'products',
            'bagPackings',
            'incoterms',
            'modeofterms',
            'bagTypes'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'buyer_id' => 'required',
            'product_id' => 'required',
        ]);

        DB::beginTransaction();

        try {
            $data = $request->only([
                'buyer_id',
                'product_id',
                'incoterm_id',
                'mode_of_term_id',
                'shipment_date_from',
                'shipment_date_to',
                'commission_percentage',
                'commission_amount_per_ton',
                'commission',
                'additional_info'
            ]);

            $data['reference'] = generateUniversalUniqueNo('export_soda_fields', [
                'prefix' => 'SAUDA',
                'column' => 'reference',
                'with_date' => true,
                'custom_date' => $request->voucher_date ?? date('Y-m-d'),
                'date_format' => 'm-Y',
                'serial_at_end' => true,
            ]);

            $company_id = session('company_id') ?? auth()->user()->company_id ?? 1;

            $exportSodaField = ExportSodaField::create(array_merge($data, [
                'created_by' => auth()->user()->id,
                'company_id' => $company_id,
            ]));

            if ($request->has('packing_items')) {
                foreach ($request->packing_items as $item) {
                    $exportSodaField->packingItems()->create($item);
                }
            }

            DB::commit();

            return response()->json([
                'success' => 'Sauda created successfully',
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
        $exportSodaField = ExportSodaField::with(['product', 'buyer', 'packingItems', 'incoterm', 'modeOfTerm'])->findOrFail($id);

        $users = Customer::get();
        $products = Product::where('status', 1)->get();
        $bagPackings = BagPacking::where('status', 1)->get();
        $incoterms = IncoTerm::where('status', 1)->get();
        $modeofterms = ModeOfTerm::where('status', 1)->get();
        $bagTypes = BagType::where('status', 1)->get();

        return view('management.export.export-soda-field.show', compact('exportSodaField', 'users', 'products', 'bagPackings', 'incoterms', 'modeofterms', 'bagTypes'));
    }

    public function edit($id): View
    {
        $exportSodaField = ExportSodaField::with('packingItems')->findOrFail($id);

        $users = Customer::get();
        $products = Product::where('status', 1)->get();
        $bagPackings = BagPacking::where('status', 1)->get();
        $incoterms = IncoTerm::where('status', 1)->get();
        $modeofterms = ModeOfTerm::where('status', 1)->get();
        $bagTypes = BagType::where('status', 1)->get();

        return view('management.export.export-soda-field.edit', compact(
            'exportSodaField',
            'users',
            'products',
            'bagPackings',
            'incoterms',
            'modeofterms',
            'bagTypes'
        ));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'buyer_id' => 'required',
            'product_id' => 'required',
        ]);

        DB::beginTransaction();

        try {
            $exportSodaField = ExportSodaField::lockForUpdate()->find($id);

            if (!$exportSodaField) {
                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => 'Record already deleted or not found.',
                ], 404);
            }

            $data = $request->only([
                'buyer_id',
                'product_id',
                'incoterm_id',
                'mode_of_term_id',
                'shipment_date_from',
                'shipment_date_to',
                'commission_percentage',
                'commission_amount_per_ton',
                'commission',
                'additional_info'
            ]);

            $exportSodaField->update($data);

            if ($request->has('packing_items')) {
                $exportSodaField->packingItems()->delete();
                foreach ($request->packing_items as $item) {
                    $exportSodaField->packingItems()->create($item);
                }
            }

            DB::commit();

            return response()->json([
                'success' => 'Sauda updated successfully',
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
            $exportSodaField = ExportSodaField::lockForUpdate()->find($id);

            if (!$exportSodaField) {
                DB::rollBack();

                return response()->json([
                    'success' => 'Record already deleted or not found.',
                ], 404);
            }

            $exportSodaField->delete();

            DB::commit();

            return response()->json([
                'success' => 'Sauda deleted successfully.',
            ], 200);

        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'success' => 'Failed to delete Sauda',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
