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
use App\Models\Export\Quotation;
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
        $quotations = Quotation::where('am_approval_status', 'approved')->latest()->get();

        return view('management.export.export-soda-field.create', compact(
            'users',
            'products',
            'bagPackings',
            'incoterms',
            'modeofterms',
            'bagTypes',
            'quotations'
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
                'additional_info',
                'quotation_id'
            ]);

            $data['shipment_date_from'] = $request->shipment_date_from ?: null;
            $data['shipment_date_to'] = $request->shipment_date_to ?: null;

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

            $data['shipment_date_from'] = $request->shipment_date_from ?: null;
            $data['shipment_date_to'] = $request->shipment_date_to ?: null;

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

    public function updateStatus(Request $request, $id)
    {
        $exportSodaField = ExportSodaField::findOrFail($id);

        if (auth()->user()->id != $exportSodaField->created_by) {
            return response()->json([
                'success' => false,
                'message' => 'Only the creator can perform this action.',
            ], 403);
        }

        $exportSodaField->update(['status' => $request->status]);

        $message = $request->status == 'rejected' ? 'Sauda rejected successfully' : 'Sauda reverted to approved';

        return response()->json([
            'success' => true,
            'message' => $message,
        ], 200);
    }

    public function getQuotationDetails($id = null)
    {
        $id = $id ?: request('id', request('quotation_id'));
        $q = Quotation::with(['packingItems', 'buyer', 'product', 'specifications', 'exportSoda'])->findOrFail($id);

        $shipmentDateFrom = $q->exportSoda?->shipment_date_from;
        $shipmentDateTo = $q->exportSoda?->shipment_date_to;
        $dateFromFormatted = $shipmentDateFrom ? ($shipmentDateFrom instanceof \Carbon\Carbon ? $shipmentDateFrom->format('Y-m-d') : (string)$shipmentDateFrom) : null;
        $dateToFormatted = $shipmentDateTo ? ($shipmentDateTo instanceof \Carbon\Carbon ? $shipmentDateTo->format('Y-m-d') : (string)$shipmentDateTo) : null;

        return response()->json([
            'company_id' => $q->company_id,
            'buyer_id' => $q->buyer_id,
            'product_id' => $q->product_id,
            'visual_name' => $q->product->name ?? null,
            'currency_id' => $q->currency_id,
            'currency_rate' => $q->currency_rate,
            'payment_days' => $q->payment_days,
            'advance_payment' => $q->advance_payment,
            'incoterm_id' => $q->incoterm_id,
            'packing_type' => $q->packing_type,
            'mode_of_term_id' => $q->mode_of_term_id,
            'mode_of_transport_id' => $q->mode_of_transport_id,
            'origin_country_id' => $q->origin_country_id,
            'port_of_discharge_id' => $q->port_of_discharge_id,
            'port_of_loading_id' => $q->port_of_loading_id,
            'hs_code_id' => $q->hs_code_id,
            'partial_payment' => $q->partial_payment,
            'transhipment' => $q->transhipment,
            'part_shipment' => $q->part_shipment,
            'insurance_covered_by' => $q->insurance_covered_by,
            'shipment_date_from' => $dateFromFormatted,
            'shipment_delivery_date_from' => $dateFromFormatted,
            'shipment_date_to' => $dateToFormatted,
            'shipment_delivery_date_to' => $dateToFormatted,
            'commission_percentage' => $q->commission_percentage,
            'commission_amount_per_ton' => $q->commission_amount_per_ton,
            'commission' => $q->commission,
            'additional_info' => $q->additional_info,
            'packing_items' => $q->packingItems,
            'specifications' => $q->specifications,
        ]);
    }
    
}
