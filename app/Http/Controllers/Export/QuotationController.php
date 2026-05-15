<?php

namespace App\Http\Controllers\Export;

use App\Http\Controllers\Controller;
use App\Http\Requests\Export\QuotationRequest;
use App\Models\BagCondition;
use App\Models\BagPacking;
use App\Models\BagType;
use App\Models\Export\Currency;
use App\Models\Export\IncoTerm;
use App\Models\Export\ModeOfTerm;
use App\Models\Export\ModeOfTransport;
use App\Models\Export\Quotation;
use App\Models\Acl\Company;
use App\Models\Export\QuotationPackingItem;
use App\Models\Export\QuotationSpecification;
use App\Models\Master\Brands;
// use App\Models\Master\Color;
use App\Models\Master\Country;
use App\Models\Master\HsCode;
use App\Models\Master\Port;
use App\Models\Master\ProductSlab;
use App\Models\Product;
use App\Models\Master\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Illuminate\Database\QueryException;
use App\Models\Export\ExportSodaField;
use App\Models\Master\CompanyLocation;
use App\Models\Master\ArrivalLocation;
use App\Models\Master\ArrivalSubLocation;

class QuotationController extends Controller
{
    public function index(Request $request): View
    {
        $quotations = Quotation::with('buyer')->orderBy('id', 'DESC')->get();

        return view('management.export.quotation.index', compact('quotations'))
            ->with('i', ($request->input('page', 1) - 1) * 5);
    }

    public function getQuotationTable(Request $request)
    {
        $quotations = Quotation::with(['product', 'buyer'])
            ->when($request->filled('search'), function ($q) use ($request) {
                $searchTerm = '%' . $request->search . '%';
                return $q->whereHas('buyer', function ($cq) use ($searchTerm) {
                    $cq->where('name', 'like', $searchTerm)
                        ->orWhere('company_name', 'like', $searchTerm);
                });
            })
            ->latest()
            ->paginate(request('per_page', 25));

        return view('management.export.quotation.getList', compact('quotations'));
    }

    public function create(): View
    {
        $products = Product::where('status', 1)->get();
        $buyers = Customer::where('status', 'active')->get();
        $companies = Company::get();
        $bagTypes = BagType::where('status', 1)->get();
        $bagPackings = BagPacking::where('status', 1)->get();
        $incoterms = IncoTerm::where('status', 1)->get();
        $modeofterms = ModeOfTerm::where('status', 1)->get();
        $modeoftransport = ModeOfTransport::where('status', 1)->get();
        $countries = Country::get();
        $ports = Port::where('status', 1)->get();
        $currencies = Currency::where('status', 1)->get();
        $exportSodas = ExportSodaField::where('status', '!=', 'rejected')->latest()->get();

        return view('management.export.quotation.create', compact(
            'exportSodas',
            'products',
            'buyers',
            'companies',
            'bagTypes',
            'bagPackings',
            'incoterms',
            'modeofterms',
            'modeoftransport',
            'countries',
            'ports',
            'currencies',
        ));
    }

    public function store(QuotationRequest $request)
    {
        DB::beginTransaction();

        try {
            $quotationData = $request->except(['specifications', 'packing_items']);

            $quotation = Quotation::create(array_merge($quotationData, [
                'created_by' => auth()->user()->id,
            ]));

            // Cleanup orphaned approval rows and manually trigger to be safe
            $module = $quotation->getApprovalModule();
            if ($module) {
                \App\Models\ApprovalsModule\ApprovalRow::where('module_id', $module->id)
                    ->where('record_id', $quotation->id)
                    ->delete();
                $quotation->createApprovalRows();
            }

            // Product specifications
            if ($request->has('specifications')) {
                foreach ($request->specifications as $spec) {
                    $quotation->specifications()->create([
                        'product_slab_type_id' => $spec['product_slab_type_id'],
                        'spec_name' => $spec['spec_name'],
                        'spec_value' => $spec['spec_value'],
                        'uom' => $spec['uom'] ?? null,
                        'value_type' => $spec['value_type'] ?? null,
                    ]);
                }
            }

            // Packing Items
            $totalAmount = 0;
            $totalMt = 0;
            if ($request->filled('packing_items')) {
                foreach ($request->packing_items as $item) {
                    $totalAmount += $item['amount'] ?? 0;
                    $totalMt += $item['metric_tons'] ?? 0;
                    $quotation->packingItems()->create([
                        'bag_type_id' => $item['bag_type_id'] ?? null,
                        'bag_packing_id' => $item['bag_packing_id'] ?? null,
                        'bag_size' => $item['bag_size'] ?? 0,
                        'metric_tons' => $item['metric_tons'] ?? 0,
                        'maunds' => $item['maunds'] ?? 0,
                        'no_of_bags' => $item['no_of_bags'] ?? 0,
                        'total_kgs' => $item['total_kgs'] ?? 0,
                        'stuffing_in_container' => $item['stuffing_in_container'] ?? 0,
                        'no_of_containers' => $item['no_of_containers'] ?? 0,
                        'rate' => $item['rate'] ?? 0,
                        'rate_per_maund' => $item['rate_per_maund'] ?? 0,
                        'amount' => $item['amount'] ?? 0,
                        'amount_pkr' => $item['amount_pkr'] ?? 0,
                    ]);
                }
            }

            // Sauda Quantity Validation
            if ($request->export_soda_id) {
                $sauda = ExportSodaField::find($request->export_soda_id);
                if ($sauda && $totalMt > $sauda->total_qty_mt) {
                    DB::rollBack();
                    return response()->json(['error' => 'Quotation quantity ('.$totalMt.') MT cannot exceed Sauda quantity ('.$sauda->total_qty_mt.') MT.'], 422);
                }
            }

            // Update quotation total amount
            $quotation->update(['total_amount' => $totalAmount]);

            DB::commit();

            return response()->json([
                'success' => 'Quotation created successfully',
                'data' => $quotation->load(['product']),
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
        $quotation = Quotation::with(['packingItems.bagType', 'packingItems.bagPacking', 'specifications.slabType', 'product', 'buyer', 'company', 'incoterm', 'modeOfTerm', 'modeOfTransport', 'currency', 'originCountry', 'portOfLoading', 'portOfDischarge'])->findOrFail($id);

        $products = Product::where('status', 1)->get();
        $bagTypes = BagType::where('status', 1)->get();
        $bagPackings = BagPacking::where('status', 1)->get();
        $incoterms = IncoTerm::where('status', 1)->get();
        $modeofterms = ModeOfTerm::where('status', 1)->get();
        $modeoftransport = ModeOfTransport::where('status', 1)->get();
        $countries = Country::get();
        $ports = Port::where('status', 1)->get();
        $currencies = Currency::where('status', 1)->get();
        $exportSodas = ExportSodaField::where('status', '!=', 'rejected')->latest()->get();

        return view('management.export.quotation.show', compact(
            'quotation',
            'exportSodas',
            'products',
            'bagTypes',
            'bagPackings',
            'incoterms',
            'modeofterms',
            'modeoftransport',
            'countries',
            'ports',
            'currencies',
        ));
    }

    public function edit($id): View
    {
        $quotation = Quotation::with(['packingItems', 'specifications.slabType', 'product', 'buyer', 'company', 'incoterm', 'modeOfTerm', 'modeOfTransport', 'currency', 'originCountry', 'portOfLoading', 'portOfDischarge'])->findOrFail($id);

        $products = Product::where('status', 1)->get();
        $buyers = Customer::where('status', 'active')->get();
        $companies = Company::get();
        $bagTypes = BagType::where('status', 1)->get();
        $bagPackings = BagPacking::where('status', 1)->get();
        $incoterms = IncoTerm::where('status', 1)->get();
        $modeofterms = ModeOfTerm::where('status', 1)->get();
        $modeoftransport = ModeOfTransport::where('status', 1)->get();
        $countries = Country::get();
        $ports = Port::where('status', 1)->get();
        $currencies = Currency::where('status', 1)->get();
        $exportSodas = ExportSodaField::where('status', '!=', 'rejected')->latest()->get();

        return view('management.export.quotation.edit', compact(
            'quotation',
            'exportSodas',
            'products',
            'buyers',
            'companies',
            'bagTypes',
            'bagPackings',
            'incoterms',
            'modeofterms',
            'modeoftransport',
            'countries',
            'ports',
            'currencies',
        ));
    }

    public function getBuyerDetails($id)
    {
        return Customer::findOrFail($id);
    }

    public function getSaudaDetails($id)
    {
        return ExportSodaField::with(['packingItems', 'buyer', 'product'])->findOrFail($id);
    }

    public function update(QuotationRequest $request, Quotation $quotation)
    {
        DB::beginTransaction();

        try {
            $quotation = Quotation::lockForUpdate()->find($quotation->id);

            if (!$quotation) {
                DB::rollBack();

                return response()->json([
                    'success' => 'Quotation already deleted or not found.',
                ], 404);
            }

            if (
                $quotation->am_approval_status === "approved" ||
                $quotation->am_approval_status === "rejected"
            ) {
                DB::rollBack();

                return response()->json([
                    'success' => 'Quotation has been approved/rejected and cannot be updated.',
                ], 400);
            }

            $quotationData = $request->except(['specifications', 'packing_items']);

            $updateData = array_merge($quotationData, [
                'am_change_made' => 1,
            ]);

            if ($quotation->am_approval_status === 'reverted') {
                $updateData['am_approval_status'] = 'pending';
            }

            $quotation->update($updateData);

            // Recreate approval rows to handle conditional role changes (with/without sauda)
            $module = $quotation->getApprovalModule();
            if ($module) {
                \App\Models\ApprovalsModule\ApprovalRow::where('module_id', $module->id)
                    ->where('record_id', $quotation->id)
                    ->where('approval_cycle', $quotation->getCurrentApprovalCycle())
                    ->delete();
                $quotation->createApprovalRows();
            }

            // Update specifications
            if ($quotation->specifications()->exists()) {
                $quotation->specifications()->delete();
            }
            if ($request->has('specifications')) {
                foreach ($request->specifications as $spec) {
                    $quotation->specifications()->create([
                        'product_slab_type_id' => $spec['product_slab_type_id'],
                        'spec_name' => $spec['spec_name'],
                        'spec_value' => $spec['spec_value'],
                        'uom' => $spec['uom'] ?? null,
                        'value_type' => $spec['value_type'] ?? null,
                    ]);
                }
            }

            // Update packing items
            $quotation->packingItems()->delete();
            $totalAmount = 0;
            $totalMt = 0;
            if ($request->filled('packing_items')) {
                foreach ($request->packing_items as $item) {
                    $totalAmount += $item['amount'] ?? 0;
                    $totalMt += $item['metric_tons'] ?? 0;
                    $quotation->packingItems()->create([
                        'bag_type_id' => $item['bag_type_id'] ?? null,
                        'bag_packing_id' => $item['bag_packing_id'] ?? null,
                        'bag_size' => $item['bag_size'] ?? 0,
                        'metric_tons' => $item['metric_tons'] ?? 0,
                        'maunds' => $item['maunds'] ?? 0,
                        'no_of_bags' => $item['no_of_bags'] ?? 0,
                        'total_kgs' => $item['total_kgs'] ?? 0,
                        'stuffing_in_container' => $item['stuffing_in_container'] ?? 0,
                        'no_of_containers' => $item['no_of_containers'] ?? 0,
                        'rate' => $item['rate'] ?? 0,
                        'rate_per_maund' => $item['rate_per_maund'] ?? 0,
                        'amount' => $item['amount'] ?? 0,
                        'amount_pkr' => $item['amount_pkr'] ?? 0,
                    ]);
                }
            }

            // Sauda Quantity Validation
            if ($quotation->export_soda_id) {
                if ($quotation->exportSoda && $totalMt > $quotation->exportSoda->total_qty_mt) {
                    DB::rollBack();
                    return response()->json(['error' => 'Quotation quantity ('.$totalMt.') MT cannot exceed Sauda quantity ('.$quotation->exportSoda->total_qty_mt.') MT.'], 422);
                }
            }

            // Update quotation total amount
            $quotation->update(['total_amount' => $totalAmount]);

            DB::commit();

            return response()->json([
                'success' => 'Quotation updated successfully',
                'data' => $quotation->load(['product', 'packingItems']),
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
            $quotation = Quotation::with(['packingItems'])
                ->lockForUpdate()
                ->find($id);

            if (!$quotation) {
                DB::rollBack();

                return response()->json([
                    'success' => 'Quotation already deleted or not found.',
                ], 404);
            }

            // delete children first
            $quotation->packingItems()->delete();

            // delete parent
            $quotation->delete();

            DB::commit();

            return response()->json([
                'success' => 'Quotation deleted successfully.',
            ], 200);

        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'success' => 'Failed to delete Quotation',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getProductSpecs(Request $request, $productId)
    {
        $specs = ProductSlab::exportEnabled()
            ->with('slabType')
            ->where('product_id', $productId)
            ->get()
            ->groupBy('product_slab_type_id')
            ->map(function ($slabs) {
                $firstSlab = $slabs->first();
                return [
                    'id' => $firstSlab->slabType->id,
                    'spec_name' => $firstSlab->slabType->name ?? '',
                    'spec_value' => $firstSlab->prefill_spec_value ?? 0,
                    'uom' => $firstSlab->slabType->qc_symbol ?? '',
                ];
            })
            ->values();

        return view('management.export.quotation.partials.product_specs', compact('specs'));
    }
}
