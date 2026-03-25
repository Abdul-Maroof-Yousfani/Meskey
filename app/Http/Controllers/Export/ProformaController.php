<?php

namespace App\Http\Controllers\Export;

use App\Http\Controllers\Controller;
use App\Models\BagCondition;
use App\Models\BagPacking;
use App\Models\BagType;
use App\Models\Country;
use App\Models\Export\Bank;
use App\Models\Export\Currency;
use App\Models\Export\ExportOrder;
use App\Models\Export\IncoTerm;
use App\Models\Export\ModeOfTerm;
use App\Models\Export\ModeOfTransport;
use App\Models\Export\Proforma;
use App\Models\Master\Brands;
use App\Models\Master\Broker;
use App\Models\Master\Color;
use App\Models\Master\CompanyLocation;
use App\Models\Master\HsCode;
use App\Models\Master\Port;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use App\Models\Master\Customer;
use App\Models\Export\ExportOrderPackingItem;
use App\Http\Requests\Export\ExportOrderRequest;
use App\Models\CustomerCompanyBankDetail;
use App\Models\CustomerOwnerBankDetail;
use App\Models\Master\ProductSlab;

class ProformaController extends Controller
{
    public function index(Request $request): View
    {
        $proformas = Proforma::orderBy('id', 'ASC')->paginate(0);

        return view('management.export.proforma.index', compact('proformas'))->with('i', ($request->input('page', 1) - 1) * 5);
    }

    public function getProformaTable(Request $request)
    {
        $proformas = Proforma::with(['exportOrder', 'exportOrder.modeOfTerm', 'exportOrder.buyer'])
            ->when($request->filled('search'), function ($q) use ($request) {
                $searchTerm = '%'.$request->search.'%';

                return $q->where(function ($sq) use ($searchTerm) {
                    $sq->where('voucher_no', 'like', $searchTerm)
                        ->orWhere('contract_no', 'like', $searchTerm);
                });
            })
            ->latest()
            ->paginate(request('per_page', 25));

        return view('management.export.proforma.getList', compact('proformas'));
    }

    public function selectExportOrder(): View
    {
        $export_orders = ExportOrder::with(['specifications', 'packingItems', 'product', 'modeOfTerm', 'buyer'])
            ->where('am_approval_status', 'approved')
            ->whereDoesntHave('proforma')
            ->orderBy('id', 'ASC')
            ->paginate(10);

        return view('management.export.proforma.select-export-order', compact('export_orders'));
    }

    public function create($exportOrderId): View
    {
        $exportOrder = ExportOrder::with(['specifications', 'packingItems', 'product'])->findOrFail($exportOrderId);

        $products = Product::where('status', 1)->get();
        $companyLocations = CompanyLocation::where('status', 'active')->get();
        $bagTypes = BagType::where('status', 1)->get();
        $bagConditions = BagCondition::where('status', 1)->get();
        $bagPackings = BagPacking::where('status', 1)->get();
        $brands = Brands::where('status', 1)->get();
        $bagColors = Color::where('status', 1)->get();
        $users = Customer::get(); // buyer
        $banks = Bank::where('status', 1)->get();
        $brokers = Broker::where('status', 1)->get();
        $incoterms = IncoTerm::where('status', 1)->get();
        $modeofterms = ModeOfTerm::where('status', 1)->get();
        $modeoftransport = ModeOfTransport::where('status', 1)->get();
        $countries = Country::get();
        $ports = Port::where('status', 1)->get();
        $hscodes = HsCode::where('status', 1)->get();
        $currencies = Currency::where('status', 1)->get();
        $companyBanks = CustomerCompanyBankDetail::where('customer_id', ($exportOrder->buyer_id ?? $proforma->exportOrder->buyer_id))->get();
        $ownerBanks = CustomerOwnerBankDetail::where('customer_id', ($exportOrder->buyer_id ?? $proforma->exportOrder->buyer_id))->get();

        return view('management.export.proforma.create', compact(
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
            'companyBanks',
            'ownerBanks',
        ));
    }

    public function store(Request $request, $exportOrderId)
    {
        DB::beginTransaction();

        try {
            $exportOrder = ExportOrder::findOrFail($exportOrderId);

            $request->validate([
                'consigned_details' => 'nullable|string|max:5000',
            ]);

            // Update Export Order details if provided (matching update logic)
            $exportOrderData = $request->only([
                'company_id', 'buyer_id', 'product_id', 'voucher_no', 'contract_no',
                'voucher_date', 'voucher_heading', 'shipment_delivery_date_from',
                'shipment_delivery_date_to', 'other_specifications', 'customer_bank_id',
                'customer_bank_type', 'correspondent_bank_id', 'incoterm_id', 'packing_type', 
                'mode_of_term_id', 'mode_of_transport_id', 'origin_country_id', 
                'port_of_discharge_id', 'port_of_loading_id', 'hs_code_id', 
                'partial_payment', 'transhipment', 'part_shipment', 
                'insurance_covered_by', 'advance_payment', 'payment_days',
                'currency_id', 'currency_rate', 'marking_labeling', 'shipping_instructions',
                'documents_to_be_provided', 'other_condition', 'force_majure',
                'application_law', 'broker_id',
            ]);

            $exportOrder->update($exportOrderData);

            // Update Packing Items
            if ($request->has('packing_items')) {
                foreach ($request->packing_items as $itemData) {
                    if (isset($itemData['id'])) {
                        $packingItem = ExportOrderPackingItem::find($itemData['id']);
                        if ($packingItem) {
                            $packingItem->update($itemData);
                        }
                    } else {
                        $exportOrder->packingItems()->create($itemData);
                    }
                }
            }

            // Update Specifications
            if ($request->has('specifications')) {
                $exportOrder->specifications()->delete();
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

            // Create Proforma
            $proforma = Proforma::create([
                'export_order_id' => $exportOrder->id,
                'proforma_date' => $exportOrder->voucher_date,
                'consigned_details' => $request->consigned_details,
            ]);

            $proforma->proforma_no = $exportOrder->contract_no . '(' . $proforma->id . ')';
            $proforma->save();

            DB::commit();

            return response()->json([
                'success' => 'Proforma created successfully',
                'data' => [
                    'proforma_id' => $proforma->id,
                    'proforma_no' => $proforma->proforma_no,
                    'export_order' => $exportOrder,
                ],
            ], 201);

        } catch (\Throwable $e) {

            DB::rollBack();

            return response()->json([
                'error' => 'Something went wrong',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function show($id): View
    {
        $proforma = Proforma::with(['exportOrder', 'exportOrder.modeOfTerm', 'exportOrder.buyer'])->findOrFail($id);
        $exportOrder = ExportOrder::with(['specifications', 'packingItems', 'product'])->findOrFail($proforma->export_order_id);

        $products = Product::where('status', 1)->get();
        $companyLocations = CompanyLocation::where('status', 'active')->get();
        $bagTypes = BagType::where('status', 1)->get();
        $bagConditions = BagCondition::where('status', 1)->get();
        $bagPackings = BagPacking::where('status', 1)->get();
        $brands = Brands::where('status', 1)->get();
        $bagColors = Color::where('status', 1)->get();
        $users = Customer::get(); // buyer
        $banks = Bank::where('status', 1)->get();
        $brokers = Broker::where('status', 1)->get();
        $incoterms = IncoTerm::where('status', 1)->get();
        $modeofterms = ModeOfTerm::where('status', 1)->get();
        $modeoftransport = ModeOfTransport::where('status', 1)->get();
        $countries = Country::get();
        $ports = Port::where('status', 1)->get();
        $hscodes = HsCode::where('status', 1)->get();
        $currencies = Currency::where('status', 1)->get();
        $companyBanks = CustomerCompanyBankDetail::where('customer_id', ($exportOrder->buyer_id ?? $proforma->exportOrder->buyer_id))->get();
        $ownerBanks = CustomerOwnerBankDetail::where('customer_id', ($exportOrder->buyer_id ?? $proforma->exportOrder->buyer_id))->get();

        return view('management.export.proforma.show', compact(
            'proforma',
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
            'companyBanks',
            'ownerBanks',
        ));
    }

    public function edit($id): View
    {
        $proforma = Proforma::findOrFail($id);
        $exportOrder = ExportOrder::with(['specifications', 'packingItems', 'product'])->findOrFail($proforma->export_order_id);

        $products = Product::where('status', 1)->get();
        $companyLocations = CompanyLocation::where('status', 'active')->get();
        $bagTypes = BagType::where('status', 1)->get();
        $bagConditions = BagCondition::where('status', 1)->get();
        $bagPackings = BagPacking::where('status', 1)->get();
        $brands = Brands::where('status', 1)->get();
        $bagColors = Color::where('status', 1)->get();
        $users = Customer::get(); // buyer
        $banks = Bank::where('status', 1)->get();
        $brokers = Broker::where('status', 1)->get();
        $incoterms = IncoTerm::where('status', 1)->get();
        $modeofterms = ModeOfTerm::where('status', 1)->get();
        $modeoftransport = ModeOfTransport::where('status', 1)->get();
        $countries = Country::get();
        $ports = Port::where('status', 1)->get();
        $hscodes = HsCode::where('status', 1)->get();
        $currencies = Currency::where('status', 1)->get();
        $companyBanks = CustomerCompanyBankDetail::where('customer_id', ($exportOrder->buyer_id ?? $proforma->exportOrder->buyer_id))->get();
        $ownerBanks = CustomerOwnerBankDetail::where('customer_id', ($exportOrder->buyer_id ?? $proforma->exportOrder->buyer_id))->get();

        return view('management.export.proforma.edit', compact(
            'proforma',
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
            'companyBanks',
            'ownerBanks',
        ));
    }

    public function update(ExportOrderRequest $request, $id)
    {
        DB::beginTransaction();

        try {
            $proforma = Proforma::findOrFail($id);
            $exportOrder = ExportOrder::findOrFail($proforma->export_order_id);

            // Update main export order
            $exportOrderData = $request->only([
                'company_id', 'buyer_id', 'product_id', 'voucher_no', 'contract_no',
                'voucher_date', 'voucher_heading', 'shipment_delivery_date_from',
                'shipment_delivery_date_to', 'other_specifications', 'customer_bank_id',
                'customer_bank_type', 'correspondent_bank_id', 'incoterm_id', 'packing_type',
                'mode_of_transport_id', 'origin_country_id', 'port_of_discharge_id',
                'port_of_loading_id', 'hs_code_id', 'partial_payment', 'transhipment',
                'part_shipment', 'insurance_covered_by', 'advance_payment', 'payment_days',
                'currency_id', 'currency_rate', 'marking_labeling', 'shipping_instructions',
                'documents_to_be_provided', 'other_condition', 'force_majure',
                'application_law', 'broker_id',
            ]);

            $updateData = [
                ...$exportOrderData,
                'company_location_ids' => $request->company_location_ids,
                'arrival_location_ids' => $request->arrival_location_ids,
                'arrival_sub_location_ids' => $request->arrival_sub_location_ids,
                'am_change_made' => 1,
            ];

            if ($exportOrder->am_approval_status === 'reverted') {
                $updateData['am_approval_status'] = 'pending';
            }

            $exportOrder->update($updateData);

            // // Merge the location arrays
            // $exportOrder->update(array_merge(
            //     $exportOrderData,
            //     [
            //         'company_location_ids' => $request->company_location_ids,
            //         'arrival_location_ids' => $request->arrival_location_ids,
            //         'arrival_sub_location_ids' => $request->arrival_sub_location_ids,
            //     ]
            // ));

            // $updateData = [
            //     'am_change_made' => 1,
            // ];

            // if ($exportOrder->am_approval_status == 'reverted') {
            //     $updateData['am_approval_status'] = 'pending';
            // }

            // $exportOrder->update($updateData);

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
                        'bag_size' => $item['bag_size'] ?? 0,
                        'metric_tons' => $item['metric_tons'] ?? 0,
                        'maunds' => $item['maunds'] ?? 0,
                        'no_of_bags' => $item['no_of_bags'] ?? 0,
                        'total_kgs' => $item['total_kgs'] ?? 0,
                        'stuffing_in_container' => $item['stuffing_in_container'] ?? 0,
                        'stuffing_maunds' => $item['stuffing_maunds'] ?? 0,
                        'no_of_containers' => $item['no_of_containers'] ?? 0,
                        'rate' => $item['rate'] ?? 0,
                        'rate_per_maund' => $item['rate_per_maund'] ?? 0,
                        'amount' => $item['amount'] ?? 0,
                        'amount_pkr' => $item['amount_pkr'] ?? 0,
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
            $proforma = Proforma::findOrFail($id);
            $proforma->delete();

            DB::commit();

            return response()->json([
                'success' => 'Proforma deleted successfully.',
            ], 200);

        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'error' => 'Failed to delete Proforma',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function print($id)
    {
        $proforma = Proforma::with(['exportOrder.product', 'exportOrder.specifications', 'exportOrder.packingItems', 'exportOrder.company', 'exportOrder.buyer', 'exportOrder.modeOfTerm', 'exportOrder.currency', 'exportOrder.portOfLoading', 'exportOrder.portOfDischarge', 'exportOrder.modeOfTransport', 'exportOrder.hsCode', 'exportOrder.incoterm'])->findOrFail($id);
        $exportOrder = $proforma->exportOrder;
        
        $totalAmount = $exportOrder->packingItems->sum('amount');
        $amountInWords = $this->numberToWords($totalAmount);
        
        return view('management.export.proforma.invoice', compact('proforma', 'exportOrder', 'amountInWords'));
    }

    private function numberToWords($number)
    {
        $hyphen      = '-';
        $conjunction = ' and ';
        $separator   = ', ';
        $negative    = 'negative ';
        $dictionary  = array(
            0                   => 'zero',
            1                   => 'one',
            2                   => 'two',
            3                   => 'three',
            4                   => 'four',
            5                   => 'five',
            6                   => 'six',
            7                   => 'seven',
            8                   => 'eight',
            9                   => 'nine',
            10                  => 'ten',
            11                  => 'eleven',
            12                  => 'twelve',
            13                  => 'thirteen',
            14                  => 'fourteen',
            15                  => 'fifteen',
            16                  => 'sixteen',
            17                  => 'seventeen',
            18                  => 'eighteen',
            19                  => 'nineteen',
            20                  => 'twenty',
            30                  => 'thirty',
            40                  => 'fourty',
            50                  => 'fifty',
            60                  => 'sixty',
            70                  => 'seventy',
            80                  => 'eighty',
            90                  => 'ninety',
            100                 => 'hundred',
            1000                => 'thousand',
            1000000             => 'million',
            1000000000          => 'billion',
            1000000000000       => 'trillion',
            1000000000000000    => 'quadrillion',
            1000000000000000000 => 'quintillion'
        );

        if (!is_numeric($number)) return false;
        if ($number < 0) return $negative . $this->numberToWords(abs($number));

        $string = $fraction = null;
        if (strpos($number, '.') !== false) {
            list($number, $fraction) = explode('.', $number);
        }

        switch (true) {
            case $number < 21:
                $string = $dictionary[$number];
                break;
            case $number < 100:
                $tens   = ((int) ($number / 10)) * 10;
                $units  = $number % 10;
                $string = $dictionary[$tens];
                if ($units) $string .= $hyphen . $dictionary[$units];
                break;
            case $number < 1000:
                $hundreds  = $number / 100;
                $remainder = $number % 100;
                $string = $dictionary[(int) $hundreds] . ' ' . $dictionary[100];
                if ($remainder) $string .= $conjunction . $this->numberToWords($remainder);
                break;
            default:
                $baseUnit = pow(1000, floor(log($number, 1000)));
                $numBaseUnits = (int) ($number / $baseUnit);
                $remainder = $number % $baseUnit;
                $string = $this->numberToWords($numBaseUnits) . ' ' . $dictionary[$baseUnit];
                if ($remainder) {
                    $string .= $remainder < 100 ? $conjunction : $separator;
                    $string .= $this->numberToWords($remainder);
                }
                break;
        }

        if (null !== $fraction && is_numeric($fraction)) {
            $string .= ' Rupees';
            $fraction = (int)substr($fraction, 0, 2);
            if($fraction > 0) {
                $string .= $conjunction . $this->numberToWords($fraction) . ' Paise';
            }
        } else {
            $string .= ' Rupees';
        }

        return ucfirst($string) . ' Only';
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

        return view('management.export.proforma.partials.product_specs', compact('specs'));
    }
}
