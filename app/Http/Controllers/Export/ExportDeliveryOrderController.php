<?php

namespace App\Http\Controllers\Export;

use App\Http\Controllers\Controller;
use App\Models\Export\ExportDeliveryOrder;
use App\Models\Export\ExportFormE;
use App\Models\Export\ExportOrder;
use App\Models\Master\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Illuminate\Database\QueryException;

class ExportDeliveryOrderController extends Controller
{
    public function index(Request $request): View
    {
        try {
            $delivery_orders = ExportDeliveryOrder::orderBy('id', 'ASC')->paginate(0);
        } catch (QueryException $e) {
            $delivery_orders = collect(); // Or use a paginator placeholder if needed
        }

        return view('management.export.delivery-order.index', compact('delivery_orders'))->with('i', ($request->input('page', 1) - 1) * 5);
    }

    public function getExportDeliveryOrderTable(Request $request)
    {
        try {
            $delivery_orders = ExportDeliveryOrder::with(['exportOrder', 'buyer', 'exportFormE'])
                ->when($request->filled('search'), function ($q) use ($request) {
                    $searchTerm = '%'.$request->search.'%';
                    return $q->whereHas('exportOrder', function ($sq) use ($searchTerm) {
                        $sq->where('voucher_no', 'like', $searchTerm)
                            ->orWhere('contract_no', 'like', $searchTerm);
                    });
                })
                ->latest()
                ->paginate(request('per_page', 25));
        } catch (QueryException $e) {
            $delivery_orders = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 25);
        }

        return view('management.export.delivery-order.getList', compact('delivery_orders'));
    }

    public function create(): View
    {
        $buyers = collect();
        $export_orders = collect();
        $products = $bagTypes = $bagPackings = $brands = $bagColors = $users = $banks = $brokers = $incoterms = $modeofterms = $modeoftransport = $countries = $ports = $hscodes = $currencies = $exportSodas = $quotations = collect();

        try {
            $buyers = Customer::get();
        } catch (QueryException $e) {}

        return view('management.export.delivery-order.create', compact(
            'buyers', 'export_orders', 'products', 'bagTypes', 'bagPackings', 'brands', 'bagColors', 'users', 'banks', 'brokers', 'incoterms', 'modeofterms', 'modeoftransport', 'countries', 'ports', 'hscodes', 'currencies', 'exportSodas', 'quotations'
        ));
    }

    public function getExportOrderDetails($id)
    {
        try {
            $exportOrder = ExportOrder::with([
                'product', 
                'specifications', 
                'packingItems.bagType', 
                'packingItems.bagPacking', 
                'packingItems.brand', 
                'packingItems.bagColor',
                'broker', 
                'currency',
                'incoterm',
                'originCountry',
                'portOfDischarge',
                'portOfLoading',
                'hsCode',
                'modeOfTerm',
                'modeOfTransport'
            ])->findOrFail($id);

            // Send all required banks based on the buyer/customer selection as well (if needed).
            // It relies on API call.
            return response()->json([
                'success' => true,
                'data' => $exportOrder,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 404);
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'export_order_id' => 'required|exists:export_orders,id',
            'buyer_id' => 'required|exists:customers,id',
            'export_form_e_id' => 'required|exists:export_form_es,id',
        ]);

        DB::beginTransaction();

        try {
            $exportOrder = ExportOrder::with([
                'product', 
                'specifications', 
                'packingItems.bagType', 
                'packingItems.bagPacking', 
                'packingItems.brand', 
                'packingItems.bagColor',
                'broker', 
                'currency',
                'incoterm',
                'originCountry',
                'portOfDischarge',
                'portOfLoading',
                'hsCode',
                'modeOfTerm',
                'modeOfTransport'
            ])->findOrFail($request->export_order_id);
            
            $deliveryOrder = ExportDeliveryOrder::create([
                'export_order_id' => $request->export_order_id,
                'buyer_id' => $request->buyer_id,
                'export_form_e_id' => $request->export_form_e_id,
                'remarks' => $request->remarks,
                'export_snapshot' => $exportOrder->toArray(),
                'created_by' => auth()->user() ? auth()->user()->id : null,
            ]);

            DB::commit();

            return response()->json([
                'success' => 'Export Delivery Order created successfully',
                'data' => $deliveryOrder,
            ], 201);

        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'error' => 'Something went wrong: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function show($id): View
    {
        try {
            $deliveryOrder = ExportDeliveryOrder::with(['exportFormE'])->findOrFail($id);
            $exportOrderData = $deliveryOrder->export_snapshot;
        } catch (QueryException $e) {
            $deliveryOrder = new ExportDeliveryOrder();
            $exportOrderData = [];
        }

        return view('management.export.delivery-order.show', compact('deliveryOrder', 'exportOrderData'));
    }

    public function edit($id): View
    {
        $products = $bagTypes = $bagPackings = $brands = $bagColors = $users = $banks = $brokers = $incoterms = $modeofterms = $modeoftransport = $countries = $ports = $hscodes = $currencies = $exportSodas = $quotations = collect();

        try {
            $deliveryOrder = ExportDeliveryOrder::findOrFail($id);
            $buyers = Customer::get();
            $export_orders = ExportOrder::latest()->get();
        } catch (QueryException $e) {
            $deliveryOrder = new ExportDeliveryOrder();
            $buyers = collect();
            $export_orders = collect();
        }

        return view('management.export.delivery-order.edit', compact(
            'deliveryOrder', 'buyers', 'export_orders', 'products', 'bagTypes', 'bagPackings', 'brands', 'bagColors', 'users', 'banks', 'brokers', 'incoterms', 'modeofterms', 'modeoftransport', 'countries', 'ports', 'hscodes', 'currencies', 'exportSodas', 'quotations'
        ));
    }

    public function update(Request $request, $id)
    {
        try {
            $deliveryOrder = ExportDeliveryOrder::findOrFail($id);
            $request->validate([
                'export_form_e_id' => 'required|exists:export_form_es,id',
            ]);
            $deliveryOrder->update($request->only([
                'remarks', 'export_form_e_id'
            ]));

            return response()->json([
                'success' => 'Export Delivery Order updated successfully',
                'data' => $deliveryOrder
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $deliveryOrder = ExportDeliveryOrder::findOrFail($id);
            $deliveryOrder->delete();

            return response()->json([
                'success' => true,
                'message' => 'Export Delivery Order deleted successfully.'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete Delivery Order'
            ], 500);
        }
    }

    public function getOrdersByBuyer($buyer_id)
    {
        try {
            $export_orders = ExportOrder::where('buyer_id', $buyer_id)->latest()->get();
            return response()->json([
                'success' => true,
                'data' => $export_orders
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
