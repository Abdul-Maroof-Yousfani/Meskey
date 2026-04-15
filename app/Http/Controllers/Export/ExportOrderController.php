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
use Illuminate\Database\QueryException;
use App\Models\Master\Customer;
use App\Models\Export\ExportSodaField;
use App\Models\Export\Quotation;

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
        $products            = Product::where('status', 1)->get();
        $bagTypes            = BagType::where('status', 1)->get();
        $bagPackings         = BagPacking::where('status', 1)->get();
        $brands              = Brands::where('status', 1)->get();
        $bagColors           = Color::where('status', 1)->get();
        $users               = Customer::get();
        $banks               = Bank::where('status', 1)->get();
        $brokers             = Broker::where('status', 1)->get();
        $incoterms           = IncoTerm::where('status', 1)->get();
        $modeofterms         = ModeOfTerm::where('status', 1)->get();
        $modeoftransport     = ModeOfTransport::where('status', 1)->get();
        $countries           = Country::get();
        $ports               = Port::where('status', 1)->get();
        $hscodes             = HsCode::where('status', 1)->get();
        $currencies          = Currency::where('status', 1)->get();
        $exportSodas         = ExportSodaField::latest()->get();
        $quotations          = Quotation::latest()->get();
        $companyLocations    = CompanyLocation::where('status', 'active')->get();
        $bagConditions       = BagCondition::where('status', 1)->get();
        $bagSizes            = \App\Models\Master\Size::where('status', 'active')->get();
        $stitchings          = \App\Models\Master\Stitching::where('status', 'active')->get();
        $threadColors        = Color::where('status', 1)->get();
        $inspectionCompanies = \App\Models\Master\FumigationCompany::where('status', 'active')->get();

        return view('management.export.export-order.create', compact(
            'products',
            'bagTypes',
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
            'exportSodas',
            'quotations',
            'companyLocations',
            'bagConditions',
            'bagSizes',
            'stitchings',
            'threadColors',
            'inspectionCompanies'
        ));
    }

    public function store(ExportOrderRequest $request)
    {
        DB::beginTransaction();

        try {
            $exportOrderData = $request->except(['bank_id', 'specifications', 'packing_items']);

            // Parse bank_id (e.g., owner_1, company_2)
            if ($request->bank_id) {
                $bankParts = explode('_', $request->bank_id);
                if (count($bankParts) == 2) {
                    $exportOrderData['customer_bank_type'] = $bankParts[0];
                    $exportOrderData['customer_bank_id'] = $bankParts[1];
                }
            }

            $exportOrder = ExportOrder::create(array_merge(
                $exportOrderData,
                [
                    'created_by' => auth()->user()->id,
                    'additional_info' => $request->additional_info,
                ]
            ));

            // CLEANUP orphaned approval rows (if record ID 1 is being reused)
            \App\Models\ApprovalsModule\ApprovalRow::where('module_id', 31)->where('record_id', $exportOrder->id)->delete();
            $exportOrder->createApprovalRows(); // Manually trigger to be safe if event was weird, or let HasApproval handle it.
            // Actually, HasApproval trait already calls it on 'created' event.
            // But deleting HERE is fine because it's after create() and inside transaction.

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
                foreach ($request->packing_items as $pIdx => $item) {
                    $subItems = $item['sub_items'] ?? [];
                    unset($item['sub_items']);

                    // Calculate totals from sub-items if they exist and have actual data
                    $hasValidSubItems = collect($subItems)->contains(function($sub) {
                        return ($sub['no_of_bags'] ?? 0) > 0;
                    });

                    if ($hasValidSubItems) {
                    // NO summation from sub-items in controller to match top-down flow of JobOrder
                    // Values from main row (passed in $item) are the source of truth
                    }

                    $packingItem = $exportOrder->packingItems()->create($item);

                    if (!empty($subItems)) {
                        foreach ($subItems as $sIdx => $subItem) {
                            // Handle file upload
                            if ($request->hasFile("packing_items.$pIdx.sub_items.$sIdx.attachment")) {
                                $file = $request->file("packing_items.$pIdx.sub_items.$sIdx.attachment");
                                $path = $file->store('export-orders/attachments', 'public');
                                $subItem['attachment'] = $path;
                            }
                            $packingItem->subItems()->create($subItem);
                        }
                    }
                }
            }

            DB::commit();

            return response()->json([
                'success' => 'Export Order created successfully',
                'data' => $exportOrder->load(['product', 'company', 'specifications', 'packingItems.subItems']),
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
        $exportOrder = ExportOrder::with(['specifications', 'packingItems.subItems', 'product'])->findOrFail($id);

        $products            = Product::where('status', 1)->get();
        $bagTypes            = BagType::where('status', 1)->get();
        $bagPackings         = BagPacking::where('status', 1)->get();
        $brands              = Brands::where('status', 1)->get();
        $bagColors           = Color::where('status', 1)->get();
        $users               = Customer::get();
        $banks               = Bank::where('status', 1)->get();
        $brokers             = Broker::where('status', 1)->get();
        $incoterms           = IncoTerm::where('status', 1)->get();
        $modeofterms         = ModeOfTerm::where('status', 1)->get();
        $modeoftransport     = ModeOfTransport::where('status', 1)->get();
        $countries           = Country::get();
        $ports               = Port::where('status', 1)->get();
        $hscodes             = HsCode::where('status', 1)->get();
        $currencies          = Currency::where('status', 1)->get();
        $exportSodas         = ExportSodaField::latest()->get();
        $quotations          = Quotation::latest()->get();
        $companyLocations    = CompanyLocation::where('status', 'active')->get();
        $bagConditions       = BagCondition::where('status', 1)->get();
        $bagSizes            = \App\Models\Master\Size::where('status', 'active')->get();
        $stitchings          = \App\Models\Master\Stitching::where('status', 'active')->get();
        $threadColors        = Color::where('status', 1)->get();
        $inspectionCompanies = \App\Models\Master\FumigationCompany::where('status', 'active')->get();

        return view('management.export.export-order.show', compact(
            'exportOrder',
            'products',
            'bagTypes',
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
            'exportSodas',
            'quotations',
            'companyLocations',
            'bagConditions',
            'bagSizes',
            'stitchings',
            'threadColors',
            'inspectionCompanies'
        ));
    }

    public function edit($id): View
    {
        $exportOrder = ExportOrder::with(['specifications', 'packingItems.subItems', 'product'])->findOrFail($id);

        $products            = Product::where('status', 1)->get();
        $bagTypes            = BagType::where('status', 1)->get();
        $bagPackings         = BagPacking::where('status', 1)->get();
        $brands              = Brands::where('status', 1)->get();
        $bagColors           = Color::where('status', 1)->get();
        $users               = Customer::get();
        $banks               = Bank::where('status', 1)->get();
        $brokers             = Broker::where('status', 1)->get();
        $incoterms           = IncoTerm::where('status', 1)->get();
        $modeofterms         = ModeOfTerm::where('status', 1)->get();
        $modeoftransport     = ModeOfTransport::where('status', 1)->get();
        $countries           = Country::get();
        $ports               = Port::where('status', 1)->get();
        $hscodes             = HsCode::where('status', 1)->get();
        $currencies          = Currency::where('status', 1)->get();
        $exportSodas         = ExportSodaField::latest()->get();
        $quotations          = Quotation::latest()->get();
        $companyLocations    = CompanyLocation::where('status', 'active')->get();
        $bagConditions       = BagCondition::where('status', 1)->get();
        $bagSizes            = \App\Models\Master\Size::where('status', 'active')->get();
        $stitchings          = \App\Models\Master\Stitching::where('status', 'active')->get();
        $threadColors        = Color::where('status', 1)->get();
        $inspectionCompanies = \App\Models\Master\FumigationCompany::where('status', 'active')->get();

        return view('management.export.export-order.edit', compact(
            'exportOrder',
            'products',
            'bagTypes',
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
            'exportSodas',
            'quotations',
            'companyLocations',
            'bagConditions',
            'bagSizes',
            'stitchings',
            'threadColors',
            'inspectionCompanies'
        ));
    }

    public function update(ExportOrderRequest $request, ExportOrder $exportOrder)
    {
        DB::beginTransaction();

        try {
            $exportOrderData = $request->except(['bank_id', 'specifications', 'packing_items']);

            // Parse bank_id (e.g., owner_1, company_2)
            if ($request->bank_id) {
                $bankParts = explode('_', $request->bank_id);
                if (count($bankParts) == 2) {
                    $exportOrderData['customer_bank_type'] = $bankParts[0];
                    $exportOrderData['customer_bank_id'] = $bankParts[1];
                }
            } else {
                $exportOrderData['customer_bank_type'] = null;
                $exportOrderData['customer_bank_id'] = null;
            }

            $updateData = array_merge($exportOrderData, [
                'am_change_made' => 1,
                'additional_info' => $request->additional_info,
            ]);

            if ($exportOrder->am_approval_status === 'reverted') {
                $updateData['am_approval_status'] = 'pending';
            }

            $exportOrder->update($updateData);

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

            // Update packing items
            if ($request->filled('packing_items')) {
                // Delete existing sub-items first to avoid orphaned records
                foreach ($exportOrder->packingItems as $oldPackingItem) {
                    $oldPackingItem->subItems()->delete();
                }
                $exportOrder->packingItems()->delete();
                foreach ($request->packing_items as $pIdx => $item) {
                    $subItems = $item['sub_items'] ?? [];
                    unset($item['sub_items']);

                    // Calculate totals from sub-items if they exist and have actual data
                    $hasValidSubItems = collect($subItems)->contains(function($sub) {
                        return ($sub['no_of_bags'] ?? 0) > 0;
                    });

                    if ($hasValidSubItems) {
                    // NO summation from sub-items in controller to match top-down flow of JobOrder
                    // Values from main row (passed in $item) are the source of truth
                    }

                    $packingItem = $exportOrder->packingItems()->create($item);

                    if (!empty($subItems)) {
                        foreach ($subItems as $sIdx => $subItem) {
                            // Handle file upload
                            if ($request->hasFile("packing_items.$pIdx.sub_items.$sIdx.attachment")) {
                                $file = $request->file("packing_items.$pIdx.sub_items.$sIdx.attachment");
                                $path = $file->store('export-orders/attachments', 'public');
                                $subItem['attachment'] = $path;
                            } elseif (isset($subItem['old_attachment'])) {
                                $subItem['attachment'] = $subItem['old_attachment'];
                            }
                            unset($subItem['old_attachment']);
                            
                            $packingItem->subItems()->create($subItem);
                        }
                    }
                }
            }

            DB::commit();

            return response()->json([
                'success' => 'Export Order updated successfully',
                'data' => $exportOrder->load(['product', 'company', 'specifications', 'packingItems.subItems']),
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

            foreach ($exportOrder->packingItems as $packingItem) {
                $packingItem->subItems()->delete();
            }
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

    public function getQuotationDetails($id)
    {
        $q = Quotation::with(['packingItems', 'buyer', 'product', 'specifications'])->findOrFail($id);

        return response()->json([
            'buyer_id'                   => $q->buyer_id,
            'product_id'                 => $q->product_id,
            'visual_name'                => $q->product->name ?? null,
            'currency_id'                => $q->currency_id,
            'currency_rate'              => $q->currency_rate,
            'payment_days'               => $q->payment_days,
            'advance_payment'            => $q->advance_payment,
            'incoterm_id'                => $q->incoterm_id,
            'packing_type'               => $q->packing_type,
            'mode_of_term_id'            => $q->mode_of_term_id,
            'mode_of_transport_id'       => $q->mode_of_transport_id,
            'origin_country_id'          => $q->origin_country_id,
            'port_of_discharge_id'       => $q->port_of_discharge_id,
            'port_of_loading_id'         => $q->port_of_loading_id,
            'hs_code_id'                 => $q->hs_code_id,
            'partial_payment'            => $q->partial_payment,
            'transhipment'               => $q->transhipment,
            'part_shipment'              => $q->part_shipment,
            'insurance_covered_by'       => $q->insurance_covered_by,
            'shipment_delivery_date_from'=> $q->shipment_delivery_date_from,
            'shipment_delivery_date_to'  => $q->shipment_delivery_date_to,
            'packing_items'              => $q->packingItems,
            'specifications'             => $q->specifications,
        ]);
    }

    public function getCustomerBanks($customerId)
    {
        $customer = Customer::with(['ownerBankDetails', 'companyBankDetails'])->findOrFail($customerId);

        $banks = [];

        foreach ($customer->ownerBankDetails as $bank) {
            $banks[] = [
                'id' => 'owner_' . $bank->id,
                'type' => 'Owner',
                'bank_name' => $bank->bank_name,
                'branch_name' => $bank->branch_name,
                'branch_code' => $bank->branch_code,
                'account_title' => $bank->account_title,
                'account_number' => $bank->account_number,
            ];
        }

        foreach ($customer->companyBankDetails as $bank) {
            $banks[] = [
                'id' => 'company_' . $bank->id,
                'type' => 'Company',
                'bank_name' => $bank->bank_name,
                'branch_name' => $bank->branch_name,
                'branch_code' => $bank->branch_code,
                'account_title' => $bank->account_title,
                'account_number' => $bank->account_number,
            ];
        }

        return response()->json($banks);
    }
}
