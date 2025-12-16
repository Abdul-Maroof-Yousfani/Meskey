<?php

namespace App\Http\Controllers\Export;

use App\Http\Controllers\Controller;
use App\Http\Requests\Export\ExportOrderRequest;
use App\Models\BagCondition;
use App\Models\BagPacking;
use App\Models\BagType;
use App\Models\Export\Bank;
use App\Models\Export\Currency;
use App\Models\Export\ExportOrder;
use App\Models\Export\IncoTerm;
use App\Models\Export\ModeOfTerm;
use App\Models\Export\ModeOfTransport;
use App\Models\Master\ArrivalLocation;
use App\Models\Master\ArrivalSubLocation;
use App\Models\Master\Brands;
use App\Models\Master\Broker;
use App\Models\Master\Color;
use App\Models\Master\CompanyLocation;
use App\Models\Master\Country;
use App\Models\Master\HsCode;
use App\Models\Master\Port;
use App\Models\Master\ProductSlab;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ExportOrderController extends Controller
{
    public function index(Request $request): View
    {
        $export_orders = ExportOrder::orderBy('id', 'ASC')->paginate(0);

        return view('management.export.export-order.index', compact('export_orders'))->with('i', ($request->input('page', 1) - 1) * 5);
    }

    public function getExportOrderTable(Request $request)
    {
        $export_orders = ExportOrder::with(['product'])
            ->when($request->filled('search'), function ($q) use ($request) {
                $searchTerm = '%'.$request->search.'%';

                return $q->where(function ($sq) use ($searchTerm) {
                    $sq->where('voucher_no', 'like', $searchTerm)
                        ->orWhere('contract_no', 'like', $searchTerm);
                });
            })
            ->latest()
            ->paginate(request('per_page', 25));

        return view('management.export.export-order.getList', compact('export_orders'));
    }

    public function create(): View
    {
        $products = Product::where('status', 1)->get();
        $companyLocations = CompanyLocation::where('status', 'active')->get();
        $bagTypes = BagType::where('status', 1)->get();
        $bagConditions = BagCondition::where('status', 1)->get();
        $bagPackings = BagPacking::where('status', 1)->get();
        $brands = Brands::where('status', 1)->get();
        $bagColors = Color::where('status', 1)->get();
        $users = User::get(); // buyer
        $banks = Bank::where('status', 1)->get();
        $brokers = Broker::where('status', 1)->get();
        $incoterms = IncoTerm::where('status', 1)->get();
        $modeofterms = ModeOfTerm::where('status', 1)->get();
        $modeoftransport = ModeOfTransport::where('status', 1)->get();
        $countries = Country::get();
        $ports = Port::where('status', 1)->get();
        $hscodes = HsCode::where('status', 1)->get();
        $currencies = Currency::where('status', 1)->get();

        return view('management.export.export-order.create', compact(
            'products',
            'companyLocations',
            'bagTypes',
            'bagConditions',
            'bagPackings',
            'brands',
            'bagColors',
            'users',
            'banks',
            'brokers',
            'incoterms',
            'modeofterms',
            'modeoftransport',
            'countries',
            'ports',
            'hscodes',
            'currencies',
        ));
    }

    public function store(ExportOrderRequest $request)
    {
        DB::beginTransaction();

        try {
            $exportOrderData = $request->only([
                'company_id',
                'buyer_id',
                'product_id',
                'voucher_no',
                'contract_no',
                'voucher_date',
                'voucher_heading',
                'shipment_delivery_date_from',
                'shipment_delivery_date_to',
                'other_specifications',
                'bank_id',
                'correspondent_bank_id',
                'incoterm_id',
                'packing_type',
                'mode_of_term_id',
                'mode_of_transport_id',
                'origin_country_id',
                'port_of_discharge_id',
                'port_of_loading_id',
                'hs_code_id',
                'partial_payment',
                'transhipment',
                'part_shipment',
                'insurance_covered_by',
                'advance_payment',
                'payment_days',
                'currency_id',
                'currency_rate',
                'marking_labeling',
                'shipping_instructions',
                'documents_to_be_provided',
                'other_condition',
                'force_majure',
                'application_law',
                'broker_id',
            ]);

            $exportOrder = ExportOrder::create(array_merge(
                $exportOrderData,
                [
                    'company_location_ids' => $request->company_location_ids,
                    'arrival_location_ids' => $request->arrival_location_ids,
                    'arrival_sub_location_ids' => $request->arrival_sub_location_ids,
                ]
            ));

            // product specifications
            if ($request->has('specifications')) {
                foreach ($request->specifications as $spec) {
                    $exportOrder->specifications()->create([
                        'product_slab_type_id' => $spec['product_slab_type_id'],
                        'spec_name' => $spec['spec_name'],
                        'spec_value' => $spec['spec_value'],
                        'uom' => $spec['uom'] ?? null,
                        'value_type' => $spec['value_type'] ?? null,
                    ]);
                }
            }

            // PACKING ITEMS
            if ($request->filled('packing_items')) {
                foreach ($request->packing_items as $item) {
                    $exportOrder->packingItems()->create([
                        'brand_id' => $item['brand_id'],
                        'bag_type_id' => $item['bag_type_id'],
                        'bag_packing_id' => $item['bag_packing_id'],
                        'bag_condition_id' => $item['bag_condition_id'],
                        'bag_color_id' => $item['bag_color_id'],

                        'bag_size' => $item['bag_size'],
                        'metric_tons' => $item['metric_tons'],

                        'stuffing_in_container' => $item['stuffing_in_container'] ?? 0,
                        'no_of_containers' => $item['no_of_containers'] ?? 0,

                        'rate' => $item['rate'],
                        // 'amount_pkr' => $item['amount_pkr'] ?? 0,
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => 'Export Order created successfully',
                'data' => $exportOrder->load(['product', 'company', 'specifications']),
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
        $exportOrder = ExportOrder::with(['specifications', 'packingItems', 'product'])->findOrFail($id);

        $products = Product::where('status', 1)->get();
        $companyLocations = CompanyLocation::where('status', 'active')->get();
        $bagTypes = BagType::where('status', 1)->get();
        $bagConditions = BagCondition::where('status', 1)->get();
        $bagPackings = BagPacking::where('status', 1)->get();
        $brands = Brands::where('status', 1)->get();
        $bagColors = Color::where('status', 1)->get();
        $users = User::get(); // buyer
        $banks = Bank::where('status', 1)->get();
        $brokers = Broker::where('status', 1)->get();
        $incoterms = IncoTerm::where('status', 1)->get();
        $modeofterms = ModeOfTerm::where('status', 1)->get();
        $modeoftransport = ModeOfTransport::where('status', 1)->get();
        $countries = Country::get();
        $ports = Port::where('status', 1)->get();
        $hscodes = HsCode::where('status', 1)->get();
        $currencies = Currency::where('status', 1)->get();

        return view('management.export.export-order.show', compact(
            'exportOrder',
            'products',
            'companyLocations',
            'bagTypes',
            'bagConditions',
            'bagPackings',
            'brands',
            'bagColors',
            'users',
            'banks',
            'brokers',
            'incoterms',
            'modeofterms',
            'modeoftransport',
            'countries',
            'ports',
            'hscodes',
            'currencies',
        ));
    }

    public function edit($id): View
    {
        $exportOrder = ExportOrder::with(['specifications', 'packingItems', 'product'])->findOrFail($id);

        $products = Product::where('status', 1)->get();
        $companyLocations = CompanyLocation::where('status', 'active')->get();
        $bagTypes = BagType::where('status', 1)->get();
        $bagConditions = BagCondition::where('status', 1)->get();
        $bagPackings = BagPacking::where('status', 1)->get();
        $brands = Brands::where('status', 1)->get();
        $bagColors = Color::where('status', 1)->get();
        $users = User::get(); // buyer
        $banks = Bank::where('status', 1)->get();
        $brokers = Broker::where('status', 1)->get();
        $incoterms = IncoTerm::where('status', 1)->get();
        $modeofterms = ModeOfTerm::where('status', 1)->get();
        $modeoftransport = ModeOfTransport::where('status', 1)->get();
        $countries = Country::get();
        $ports = Port::where('status', 1)->get();
        $hscodes = HsCode::where('status', 1)->get();
        $currencies = Currency::where('status', 1)->get();

        return view('management.export.export-order.edit', compact(
            'exportOrder',
            'products',
            'companyLocations',
            'bagTypes',
            'bagConditions',
            'bagPackings',
            'brands',
            'bagColors',
            'users',
            'banks',
            'brokers',
            'incoterms',
            'modeofterms',
            'modeoftransport',
            'countries',
            'ports',
            'hscodes',
            'currencies',
        ));
    }

    public function update(ExportOrderRequest $request, ExportOrder $exportOrder)
    {
        DB::beginTransaction();

        try {
            // Update main export order
            $exportOrderData = $request->only([
                'company_id', 'buyer_id', 'product_id', 'voucher_no', 'contract_no',
                'voucher_date', 'voucher_heading', 'shipment_delivery_date_from',
                'shipment_delivery_date_to', 'other_specifications', 'bank_id',
                'correspondent_bank_id', 'incoterm_id', 'packing_type', 'mode_of_term_id',
                'mode_of_transport_id', 'origin_country_id', 'port_of_discharge_id',
                'port_of_loading_id', 'hs_code_id', 'partial_payment', 'transhipment',
                'part_shipment', 'insurance_covered_by', 'advance_payment', 'payment_days',
                'currency_id', 'currency_rate', 'marking_labeling', 'shipping_instructions',
                'documents_to_be_provided', 'other_condition', 'force_majure',
                'application_law', 'broker_id',
            ]);

            // Merge the location arrays
            $exportOrder->update(array_merge(
                $exportOrderData,
                [
                    'company_location_ids' => $request->company_location_ids,
                    'arrival_location_ids' => $request->arrival_location_ids,
                    'arrival_sub_location_ids' => $request->arrival_sub_location_ids,
                ]
            ));

            // Update specifications
            $exportOrder->specifications()->delete();
            if ($request->has('specifications')) {
                foreach ($request->specifications as $spec) {
                    $exportOrder->specifications()->create([
                        'product_slab_type_id' => $spec['product_slab_type_id'],
                        'spec_name' => $spec['spec_name'],
                        'spec_value' => $spec['spec_value'],
                        'uom' => $spec['uom'] ?? null,
                        'value_type' => $spec['value_type'] ?? null,
                    ]);
                }
            }

            // Optional: update packing items
            if ($request->filled('packing_items')) {
                $exportOrder->packingItems()->delete();
                foreach ($request->packing_items as $item) {
                    $exportOrder->packingItems()->create([
                        'brand_id' => $item['brand_id'],
                        'bag_type_id' => $item['bag_type_id'],
                        'bag_packing_id' => $item['bag_packing_id'] ?? null,
                        'bag_condition_id' => $item['bag_condition_id'],
                        'bag_color_id' => $item['bag_color_id'],
                        'bag_size' => $item['bag_size'],
                        'metric_tons' => $item['metric_tons'],
                        'stuffing_in_container' => $item['stuffing_in_container'] ?? 0,
                        'no_of_containers' => $item['no_of_containers'] ?? 0,
                        'rate' => $item['rate'],
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => 'Export Order updated successfully',
                'data' => $exportOrder->load(['product', 'company', 'specifications', 'packingItems']),
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
            $exportOrder = ExportOrder::with(['specifications', 'packingItems'])->findOrFail($id);

            $exportOrder->specifications()->delete();
            $exportOrder->packingItems()->delete();
            $exportOrder->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Export Order deleted successfully.',
            ], 200);

        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete Export Order',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getProductSpecs($productId)
    {
        $specs = ProductSlab::with('slabType')
            ->where('product_id', $productId)
            ->where('status', 1)
            ->get()
            ->groupBy('product_slab_type_id')
            ->map(function ($slabs) {
                // Pehla slab le rahe hain kyun ke har type ka ek hi slab hoga group mein
                $firstSlab = $slabs->first();

                return [
                    'id' => $firstSlab->slabType->id,
                    'spec_name' => $firstSlab->slabType->name ?? '',
                    'spec_value' => $firstSlab->deduction_value ?? 0,
                    'uom' => $firstSlab->slabType->qc_symbol ?? '',
                ];
            })
            ->values(); // Array keys reset karega

        return view('management.export.export-order.partials.product_specs', compact('specs'));
    }

    public function getArrivalLocationsByCompanyLocations(Request $request)
    {
        $locationIds = $request->company_location_ids ?? [];

        $arrivalLocations = ArrivalLocation::whereIn('company_location_id', $locationIds)
            ->where('status', 'active')
            ->get();

        return response()->json($arrivalLocations);
    }

    public function getArrivalSubLocationsByArrivalLocations(Request $request)
    {
        $arrivalLocationIds = $request->arrival_location_ids ?? [];

        $subLocations = ArrivalSubLocation::whereIn('arrival_location_id', $arrivalLocationIds)
            ->where('status', 'active')
            ->get();

        return response()->json($subLocations);
    }
}
