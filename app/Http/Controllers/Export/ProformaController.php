<?php

namespace App\Http\Controllers\Export;

use App\Http\Controllers\Controller;
use App\Models\BagCondition;
use App\Models\BagPacking;
use App\Models\BagType;
use App\Models\Export\Bank;
use App\Models\Export\Currency;
use App\Models\Export\ExportOrder;
use App\Models\Export\ExportSodaField;
use App\Models\Export\IncoTerm;
use App\Models\Export\ModeOfTerm;
use App\Models\Export\ModeOfTransport;
use App\Models\Export\Proforma;
use App\Models\Export\Quotation;
use App\Models\Master\Brands;
use App\Models\Master\Broker;
use App\Models\Master\Color;
use App\Models\Master\CompanyLocation;
use App\Models\Master\Country;
use App\Models\Master\HsCode;
use App\Models\Master\Port;
use App\Models\Master\ProductSlab;
use App\Models\Master\Stitching;
use App\Models\Master\FumigationCompany;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use App\Models\Master\Customer;

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
                $searchTerm = '%' . $request->search . '%';

                return $q->where(function ($sq) use ($searchTerm) {
                    $sq->where('proforma_no', 'like', $searchTerm)
                        ->orWhereHas('exportOrder', function ($oq) use ($searchTerm) {
                            $oq->where('voucher_no', 'like', $searchTerm)
                                ->orWhere('contract_no', 'like', $searchTerm);
                        });
                });
            })
            ->latest()
            ->paginate(request('per_page', 25));

        return view('management.export.proforma.getList', compact('proformas'));
    }

    public function selectExportOrder(): View
    {
        $export_orders = ExportOrder::with(['specifications', 'packingItems.subItems', 'product', 'modeOfTerm', 'buyer'])
            ->where('am_approval_status', 'approved')
            ->whereDoesntHave('proforma')
            ->orderBy('id', 'ASC')
            ->paginate(10);

        return view('management.export.proforma.select-export-order', compact('export_orders'));
    }

    public function create($exportOrderId): View
    {
        $exportOrder = ExportOrder::with(['specifications', 'packingItems.subItems', 'product'])->findOrFail($exportOrderId);
        $formData = $this->getExportOrderFormData();

        return view('management.export.proforma.create', array_merge($formData, [
            'exportOrder' => $exportOrder,
        ]));
    }

    public function store(Request $request, $exportOrderId)
    {
        DB::beginTransaction();

        try {
            $exportOrder = ExportOrder::findOrFail($exportOrderId);
            $request->validate([
                'consigned_details' => 'nullable|string',
            ]);

            if ($exportOrder->proforma()->exists()) {
                return response()->json([
                    'error' => 'Proforma already exists against this Export Order',
                ], 422);
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
        $exportOrder = ExportOrder::with(['specifications', 'packingItems.subItems', 'product'])->findOrFail($proforma->export_order_id);
        $formData = $this->getExportOrderFormData();

        return view('management.export.proforma.show', array_merge($formData, [
            'proforma' => $proforma,
            'exportOrder' => $exportOrder,
        ]));
    }

    public function edit($id): View
    {
        $proforma = Proforma::findOrFail($id);
        $exportOrder = ExportOrder::with(['specifications', 'packingItems.subItems', 'product'])->findOrFail($proforma->export_order_id);
        $formData = $this->getExportOrderFormData();

        return view('management.export.proforma.edit', array_merge($formData, [
            'proforma' => $proforma,
            'exportOrder' => $exportOrder,
        ]));
    }

    public function update(Request $request, $id)
    {
        DB::beginTransaction();

        try {
            $request->validate([
                'consigned_details' => 'nullable|string',
            ]);

            $proforma = Proforma::lockForUpdate()->find($id);

            if (!$proforma) {
                DB::rollBack();

                return response()->json([
                    'success' => 'Proforma already deleted or not found.',
                ], 404);
            }

            $proforma->update([
                'consigned_details' => $request->consigned_details,
            ]);

            DB::commit();

            return response()->json([
                'success' => 'Proforma updated successfully',
                'data' => $proforma->load(['exportOrder.product', 'exportOrder.company', 'exportOrder.specifications', 'exportOrder.packingItems.subItems']),
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
            $proforma = Proforma::lockForUpdate()->find($id);

            if (!$proforma) {
                DB::rollBack();

                return response()->json([
                    'success' => 'Proforma already deleted or not found.',
                ], 404);
            }

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
        $proforma = Proforma::with([
            'exportOrder.product',
            'exportOrder.specifications',
            'exportOrder.packingItems.brand',
            'exportOrder.packingItems.bagType',
            'exportOrder.packingItems.bagPacking',
            'exportOrder.packingItems.bagCondition',
            'exportOrder.company',
            'exportOrder.buyer',
            'exportOrder.consignee',
            'exportOrder.modeOfTerm',
            'exportOrder.currency',
            'exportOrder.portOfLoading',
            'exportOrder.portOfDischarge',
            'exportOrder.modeOfTransport',
            'exportOrder.hsCode',
            'exportOrder.incoterm',
        ])->findOrFail($id);
        $exportOrder = $proforma->exportOrder;

        $totalAmount = $exportOrder->packingItems->sum('amount');
        $amountInWords = $this->numberToWords($totalAmount);

        return view('management.export.proforma.invoice', compact('proforma', 'exportOrder', 'amountInWords'));
    }

    private function numberToWords($number)
    {
        $hyphen = '-';
        $conjunction = ' and ';
        $separator = ', ';
        $negative = 'negative ';
        $dictionary = array(
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
            1000000000000000000 => 'quintillion'
        );

        if (!is_numeric($number))
            return false;
        if ($number < 0)
            return $negative . $this->numberToWords(abs($number));

        $string = $fraction = null;
        if (strpos($number, '.') !== false) {
            list($number, $fraction) = explode('.', $number);
        }

        switch (true) {
            case $number < 21:
                $string = $dictionary[$number];
                break;
            case $number < 100:
                $tens = ((int) ($number / 10)) * 10;
                $units = $number % 10;
                $string = $dictionary[$tens];
                if ($units)
                    $string .= $hyphen . $dictionary[$units];
                break;
            case $number < 1000:
                $hundreds = $number / 100;
                $remainder = $number % 100;
                $string = $dictionary[(int) $hundreds] . ' ' . $dictionary[100];
                if ($remainder)
                    $string .= $conjunction . $this->numberToWords($remainder);
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
            $fraction = (int) substr($fraction, 0, 2);
            if ($fraction > 0) {
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

    private function getExportOrderFormData(): array
    {
        return [
            'products' => Product::where('status', 1)->get(),
            'bagTypes' => BagType::where('status', 1)->get(),
            'bagPackings' => BagPacking::where('status', 1)->get(),
            'brands' => Brands::where('status', 1)->get(),
            'bagColors' => Color::where('status', 1)->get(),
            'users' => Customer::get(),
            'banks' => Bank::where('status', 1)->get(),
            'brokers' => Broker::where('status', 1)->get(),
            'incoterms' => IncoTerm::where('status', 1)->get(),
            'modeofterms' => ModeOfTerm::where('status', 1)->get(),
            'modeoftransport' => ModeOfTransport::where('status', 1)->get(),
            'countries' => Country::get(),
            'ports' => Port::where('status', 1)->get(),
            'hscodes' => HsCode::where('status', 1)->get(),
            'currencies' => Currency::where('status', 1)->get(),
            'exportSodas' => ExportSodaField::latest()->get(),
            'quotations' => Quotation::latest()->get(),
            'companyLocations' => CompanyLocation::where('status', 'active')->get(),
            'bagConditions' => BagCondition::where('status', 1)->get(),
            'bagSizes' => BagPacking::where('status', 1)->get(),
            'stitchings' => Stitching::where('status', 'active')->get(),
            'threadColors' => Color::where('status', 1)->get(),
            'inspectionCompanies' => FumigationCompany::where('status', 'active')->get(),
        ];
    }
}
