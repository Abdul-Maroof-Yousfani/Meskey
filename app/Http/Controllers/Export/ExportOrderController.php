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
use App\Models\Master\InspectionCompany;
use App\Models\Master\Port;
use App\Models\Master\ProductSlab;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Illuminate\Database\QueryException;
use App\Models\Master\Customer;
use App\Models\Master\FumigationCompany;
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
        $export_orders = ExportOrder::with(['product', 'broker', 'currency'])
            ->when($request->filled('search'), function ($q) use ($request) {
                $searchTerm = '%' . $request->search . '%';

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
        $bagTypes = BagType::where('status', 1)->get();
        $bagPackings = BagPacking::where('status', 1)->get();
        $brands = Brands::where('status', 1)->get();
        $bagColors = Color::where('status', 1)->get();
        $users = Customer::get();
        $banks = Bank::where('status', 1)->get();
        $brokers = Broker::where('status', 1)->get();
        $incoterms = IncoTerm::where('status', 1)->get();
        $modeofterms = ModeOfTerm::where('status', 1)->get();
        $modeoftransport = ModeOfTransport::where('status', 1)->get();
        $countries = Country::get();
        $ports = Port::where('status', 1)->get();
        $hscodes = HsCode::where('status', 1)->get();
        $currencies = Currency::where('status', 1)->get();
        $exportSodas = ExportSodaField::latest()->get();
        $quotations = Quotation::latest()->get();
        $companyLocations = CompanyLocation::where('status', 'active')->get();
        $bagConditions = BagCondition::where('status', 1)->get();
        $bagSizes = BagPacking::where('status', 1)->get();
        $stitchings = \App\Models\Master\Stitching::where('status', 'active')->get();
        $threadColors = Color::where('status', 1)->get();
        $fumigationCompanies = FumigationCompany::where('status', 'active')->get();
        $inspectionCompanies = InspectionCompany::where('status', 'active')->get();

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
            'fumigationCompanies',
            'inspectionCompanies'
        ));
    }

    public function store(ExportOrderRequest $request)
    {
        DB::beginTransaction();

        try {
            $exportOrderData = $request->except(['bank_id', 'specifications', 'packing_items']);

            // Re-generate voucher_no to ensure uniqueness and prevent race conditions
            $exportOrderData['voucher_no'] = generateUniversalUniqueNo('export_orders', [
                'prefix' => 'EXPORT',
                'column' => 'voucher_no',
                'with_date' => true,
                'custom_date' => $request->voucher_date,
                'date_format' => 'm-Y',
                'serial_at_end' => true,
            ]);

            // Parse bank_id (e.g., owner_1, company_2)
            if ($request->bank_id) {
                $bankParts = explode('_', $request->bank_id);
                if (count($bankParts) == 2) {
                    $exportOrderData['customer_bank_type'] = $bankParts[0];
                    $exportOrderData['customer_bank_id'] = $bankParts[1];
                }
            }

            if ($request->filled('quotation_id') && empty($exportOrderData['export_soda_id'])) {
                $exportOrderData['export_soda_id'] = Quotation::whereKey($request->quotation_id)->value('export_soda_id');
            }

            $exportOrder = null;
            $saved = false;
            $tries = 0;
            $maxTries = 5;

            while (!$saved && $tries < $maxTries) {
                try {
                    // Re-generate voucher_no to ensure uniqueness and prevent race conditions
                    $exportOrderData['voucher_no'] = generateUniversalUniqueNo('export_orders', [
                        'prefix' => 'EXPORT',
                        'column' => 'voucher_no',
                        'with_date' => true,
                        'custom_date' => $request->voucher_date,
                        'date_format' => 'm-Y',
                        'serial_at_end' => true,
                    ]);

                    $exportOrder = ExportOrder::create(array_merge(
                        $exportOrderData,
                        [
                            'created_by' => auth()->user()->id,
                            'additional_info' => $request->additional_info,
                            'consignee_id' => $request->consignee_id,
                        ]
                    ));
                    $saved = true;
                } catch (QueryException $e) {
                    // Check for duplicate entry error (MySQL code 1062)
                    if ($e->errorInfo[1] == 1062 && str_contains($e->getMessage(), 'voucher_no')) {
                        $tries++;
                        if ($tries >= $maxTries)
                            throw $e;
                        // Continue loop to try again with a fresh number
                    } else {
                        throw $e;
                    }
                }
            }

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
                    $item['extra_bags_percentage'] = $item['extra_bags_percentage'] ?? 0;
                    $item['empty_bags_percentage'] = $item['empty_bags_percentage'] ?? 0;
                    $item['inspection_by'] = isset($item['inspection_by']) ? array_values((array) $item['inspection_by']) : null;

                    // Calculate totals from sub-items if they exist and have actual data
                    $hasValidSubItems = collect($subItems)->contains(function ($sub) {
                        return ($sub['no_of_bags'] ?? 0) > 0;
                    });

                    if ($hasValidSubItems) {
                        // NO summation from sub-items in controller to match top-down flow of JobOrder
                        // Values from main row (passed in $item) are the source of truth
                    }

                    $packingItem = $exportOrder->packingItems()->create($item);

                    if (!empty($subItems)) {
                        foreach ($subItems as $sIdx => $subItem) {
                            $subItem['extra_bags_percentage'] = $subItem['extra_bags_percentage'] ?? 0;
                            $subItem['empty_bags_percentage'] = $subItem['empty_bags_percentage'] ?? 0;
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

        $products = Product::where('status', 1)->get();
        $bagTypes = BagType::where('status', 1)->get();
        $bagPackings = BagPacking::where('status', 1)->get();
        $brands = Brands::where('status', 1)->get();
        $bagColors = Color::where('status', 1)->get();
        $users = Customer::get();
        $banks = Bank::where('status', 1)->get();
        $brokers = Broker::where('status', 1)->get();
        $incoterms = IncoTerm::where('status', 1)->get();
        $modeofterms = ModeOfTerm::where('status', 1)->get();
        $modeoftransport = ModeOfTransport::where('status', 1)->get();
        $countries = Country::get();
        $ports = Port::where('status', 1)->get();
        $hscodes = HsCode::where('status', 1)->get();
        $currencies = Currency::where('status', 1)->get();
        $exportSodas = ExportSodaField::latest()->get();
        $quotations = Quotation::latest()->get();
        $companyLocations = CompanyLocation::where('status', 'active')->get();
        $bagConditions = BagCondition::where('status', 1)->get();
        $bagSizes = BagPacking::where('status', 1)->get();
        $stitchings = \App\Models\Master\Stitching::where('status', 'active')->get();
        $threadColors = Color::where('status', 1)->get();
        $fumigationCompanies = FumigationCompany::where('status', 'active')->get();
        $inspectionCompanies = InspectionCompany::where('status', 'active')->get();

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
            'fumigationCompanies',
            'inspectionCompanies'
        ));
    }

    public function edit($id): View
    {
        $exportOrder = ExportOrder::with(['specifications', 'packingItems.subItems', 'product'])->findOrFail($id);

        $products = Product::where('status', 1)->get();
        $bagTypes = BagType::where('status', 1)->get();
        $bagPackings = BagPacking::where('status', 1)->get();
        $brands = Brands::where('status', 1)->get();
        $bagColors = Color::where('status', 1)->get();
        $users = Customer::get();
        $banks = Bank::where('status', 1)->get();
        $brokers = Broker::where('status', 1)->get();
        $incoterms = IncoTerm::where('status', 1)->get();
        $modeofterms = ModeOfTerm::where('status', 1)->get();
        $modeoftransport = ModeOfTransport::where('status', 1)->get();
        $countries = Country::get();
        $ports = Port::where('status', 1)->get();
        $hscodes = HsCode::where('status', 1)->get();
        $currencies = Currency::where('status', 1)->get();
        $exportSodas = ExportSodaField::latest()->get();
        $quotations = Quotation::latest()->get();
        $companyLocations = CompanyLocation::where('status', 'active')->get();
        $bagConditions = BagCondition::where('status', 1)->get();
        $bagSizes = BagPacking::where('status', 1)->get();
        $stitchings = \App\Models\Master\Stitching::where('status', 'active')->get();
        $threadColors = Color::where('status', 1)->get();
        $fumigationCompanies = FumigationCompany::where('status', 'active')->get();
        $inspectionCompanies = InspectionCompany::where('status', 'active')->get();

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
            'fumigationCompanies',
            'inspectionCompanies'
        ));
    }

    public function update(ExportOrderRequest $request, ExportOrder $exportOrder)
    {
        DB::beginTransaction();

        try {
            $exportOrder = ExportOrder::with([
                'packingItems.subItems',
                'specifications'
            ])
                ->lockForUpdate()
                ->find($exportOrder->id);

            if (!$exportOrder) {
                DB::rollBack();

                return response()->json([
                    'success' => 'Export Order already deleted or not found.',
                ], 404);
            }

            if (
                $exportOrder->am_approval_status === "approved" ||
                $exportOrder->am_approval_status === "rejected"
            ) {
                DB::rollBack();

                return response()->json([
                    'success' => 'Export Order has been approved/rejected and cannot be updated.',
                ], 400);
            }

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

            if ($request->filled('quotation_id') && empty($exportOrderData['export_soda_id'])) {
                $exportOrderData['export_soda_id'] = Quotation::whereKey($request->quotation_id)->value('export_soda_id');
            }

            $updateData = array_merge($exportOrderData, [
                'am_change_made' => 1,
                'additional_info' => $request->additional_info,
                'consignee_id' => $request->consignee_id,
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
                
                foreach ($exportOrder->packingItems as $oldPackingItem) {
                    $oldPackingItem->subItems()->delete();
                }
                $exportOrder->packingItems()->delete();
                foreach ($request->packing_items as $pIdx => $item) {
                    $subItems = $item['sub_items'] ?? [];
                    unset($item['sub_items']);
                    $item['extra_bags_percentage'] = $item['extra_bags_percentage'] ?? 0;
                    $item['empty_bags_percentage'] = $item['empty_bags_percentage'] ?? 0;
                    $item['inspection_by'] = isset($item['inspection_by']) ? array_values((array) $item['inspection_by']) : null;

                    $hasValidSubItems = collect($subItems)->contains(function ($sub) {
                        return ($sub['no_of_bags'] ?? 0) > 0;
                    });

                    if ($hasValidSubItems) {
                        // NO summation from sub-items in controller to match top-down flow of JobOrder
                        // Values from main row (passed in $item) are the source of truth
                    }

                    $packingItem = $exportOrder->packingItems()->create($item);

                    if (!empty($subItems)) {
                        foreach ($subItems as $sIdx => $subItem) {
                            $subItem['extra_bags_percentage'] = $subItem['extra_bags_percentage'] ?? 0;
                            $subItem['empty_bags_percentage'] = $subItem['empty_bags_percentage'] ?? 0;
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

            $exportOrder = ExportOrder::with(['specifications', 'packingItems.subItems'])
                ->lockForUpdate()
                ->find($id);

            if (!$exportOrder) {
                DB::rollBack();

                return response()->json([
                    'success' => 'Export Order already deleted or not found.',
                ], 404);
            }

            if (
                $exportOrder->am_approval_status === "approved" ||
                $exportOrder->am_approval_status === "rejected"
            ) {
                DB::rollBack();

                return response()->json([
                    'success' => 'Export Order has been approved/rejected and cannot be deleted.',
                ], 400);
            }

            $exportOrder->specifications()->delete();

            foreach ($exportOrder->packingItems as $packingItem) {
                $packingItem->subItems()->delete();
            }

            $exportOrder->packingItems()->delete();
            $exportOrder->delete();

            DB::commit();

            return response()->json([
                'success' => 'Export Order deleted successfully.',
            ], 200);

        } catch (\Throwable $e) {

            DB::rollBack();

            return response()->json([
                'success' => 'Failed to delete Export Order',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function print($id)
    {
        $exportOrder = ExportOrder::with([
            'company',
            'buyer',
            'consignee',
            'product',
            'broker',
            'currency',
            'quotation.product',
            'originCountry',
            'portOfLoading',
            'portOfDischarge',
            'modeOfTransport',
            'modeOfTerm',
            'incoterm',
            'hsCode',
            'correspondentBank',
            'packingItems.brand',
            'packingItems.bagType',
            'packingItems.bagPacking',
            'packingItems.bagCondition',
            'packingItems.bagColor',
            'packingItems.threadColor',
            'packingItems.stitching',
            'packingItems.subItems.bagType',
            'packingItems.subItems.bagSize',
            'packingItems.subItems.stitching',
            'packingItems.subItems.bagColor',
            'packingItems.subItems.brand',
            'packingItems.subItems.threadColor',
            'specifications.productSlabType',
        ])->findOrFail($id);

        $totalAmount = $exportOrder->packingItems->sum('amount');
        $totalMetricTons = $exportOrder->packingItems->sum('metric_tons');
        $amountInWords = $this->numberToWords($totalAmount);
        $fumigationCompanies = FumigationCompany::where('status', 'active')->get()->keyBy('id');
        $inspectionCompanies = InspectionCompany::where('status', 'active')->get()->keyBy('id');

        return view('management.export.export-order.print', compact(
            'exportOrder',
            'totalAmount',
            'totalMetricTons',
            'amountInWords',
            'fumigationCompanies',
            'inspectionCompanies'
        ));
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
        $q = Quotation::with(['packingItems', 'buyer', 'product', 'specifications', 'exportSoda'])->findOrFail($id);

        $shipmentDateFrom = $q->exportSoda?->shipment_date_from ?? $q->shipment_delivery_date_from;
        $shipmentDateTo = $q->exportSoda?->shipment_date_to ?? $q->shipment_delivery_date_to;

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
            'shipment_delivery_date_from' => optional($shipmentDateFrom)->format('Y-m-d') ?? $shipmentDateFrom,
            'shipment_delivery_date_to' => optional($shipmentDateTo)->format('Y-m-d') ?? $shipmentDateTo,
            'commission_percentage' => $q->commission_percentage,
            'commission_amount_per_ton' => $q->commission_amount_per_ton,
            'commission' => $q->commission,
            'packing_items' => $q->packingItems,
            'specifications' => $q->specifications,
        ]);
    }

    public function getCompanyBanks($companyId)
    {
        $banks = Bank::where('company_id', $companyId)
            ->where('status', 1)
            ->get()
            ->map(function ($bank) {
                return [
                    'id' => $bank->id,
                    'account_title' => $bank->account_title,
                    'bank_name' => $bank->bank_name,
                    'branch_name' => $bank->branch_name,
                    'branch_code' => $bank->branch_code,
                    'account_number' => $bank->account_no,
                    'iban' => $bank->iban,
                    'swift_code' => $bank->swift_code,
                    'bank_address' => $bank->bank_address,
                    'description' => $bank->description,
                ];
            });

        return response()->json($banks);
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

    public function getCustomerConsignees($customerId)
    {
        $customer = Customer::with(['consignees'])->findOrFail($customerId);
        return response()->json($customer->consignees);
    }

    private function numberToWords($number, $appendCurrency = true)
    {
        $hyphen = '-';
        $conjunction = ' and ';
        $separator = ', ';
        $negative = 'negative ';
        $dictionary = [
            0 => 'zero',
            1 => 'one',
            2 => 'two',
            3 => 'three',
            4 => 'four',
            5 => 'five',
            6 => 'six',
            7 => 'seven',
            8 => 'eight',
            9 => 'nine',
            10 => 'ten',
            11 => 'eleven',
            12 => 'twelve',
            13 => 'thirteen',
            14 => 'fourteen',
            15 => 'fifteen',
            16 => 'sixteen',
            17 => 'seventeen',
            18 => 'eighteen',
            19 => 'nineteen',
            20 => 'twenty',
            30 => 'thirty',
            40 => 'fourty',
            50 => 'fifty',
            60 => 'sixty',
            70 => 'seventy',
            80 => 'eighty',
            90 => 'ninety',
            100 => 'hundred',
            1000 => 'thousand',
            1000000 => 'million',
            1000000000 => 'billion',
            1000000000000 => 'trillion',
            1000000000000000 => 'quadrillion',
            1000000000000000000 => 'quintillion',
        ];

        if (!is_numeric($number)) {
            return false;
        }

        if ($number < 0) {
            return $negative . $this->numberToWords(abs($number), false);
        }

        $string = $fraction = null;
        if (strpos((string) $number, '.') !== false) {
            [$number, $fraction] = explode('.', (string) $number);
        }

        switch (true) {
            case $number < 21:
                $string = $dictionary[$number];
                break;
            case $number < 100:
                $tens = ((int) ($number / 10)) * 10;
                $units = $number % 10;
                $string = $dictionary[$tens];
                if ($units) {
                    $string .= $hyphen . $dictionary[$units];
                }
                break;
            case $number < 1000:
                $hundreds = $number / 100;
                $remainder = $number % 100;
                $string = $dictionary[(int) $hundreds] . ' ' . $dictionary[100];
                if ($remainder) {
                    $string .= $conjunction . $this->numberToWords($remainder, false);
                }
                break;
            default:
                $baseUnit = pow(1000, floor(log($number, 1000)));
                $numBaseUnits = (int) ($number / $baseUnit);
                $remainder = $number % $baseUnit;
                $string = $this->numberToWords($numBaseUnits, false) . ' ' . $dictionary[$baseUnit];
                if ($remainder) {
                    $string .= $remainder < 100 ? $conjunction : $separator;
                    $string .= $this->numberToWords($remainder, false);
                }
                break;
        }

        if ($appendCurrency) {
            if (null !== $fraction && is_numeric($fraction)) {
                $string .= ' Rupees';
                $fraction = (int) substr($fraction, 0, 2);
                if ($fraction > 0) {
                    $string .= $conjunction . $this->numberToWords($fraction, false) . ' Paise';
                }
            } else {
                $string .= ' Rupees';
            }

            return ucfirst($string) . ' Only';
        }

        return $string;
    }
}
