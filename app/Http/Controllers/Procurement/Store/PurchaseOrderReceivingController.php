<?php

namespace App\Http\Controllers\Procurement\Store;

use App\Http\Controllers\Controller;
use App\Http\Requests\Procurement\Store\PurchaseOrderReceivingRequest;
use App\Http\Requests\Procurement\Store\PurchaseOrderRequest;
use App\Models\Category;
use App\Models\Master\Account\GoodReceiveNote;
use App\Models\Master\Account\Stock;
use App\Models\Master\CompanyLocation;
use App\Models\Master\GrnNumber;
use App\Models\Procurement\Store\PurchaseOrder;
use App\Models\Procurement\Store\PurchaseOrderData;
use App\Models\Procurement\Store\PurchaseQuotationData;
use App\Models\Procurement\Store\PurchaseRequest;
use App\Models\Procurement\Store\PurchaseRequestData;
use App\Models\Sales\JobOrder;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PurchaseOrderReceivingController extends Controller
{
    public function index()
    {
        return view('management.procurement.store.purchase_order_recieving.index');
    }

    /**
     * Get list of categories.
     */
    public function getList(Request $request)
    {
        $authUser = auth()->user();
        $isSuperAdmin = $authUser->user_type === 'super-admin';

        $PurchaseOrder = PurchaseOrderData::with('purchase_order', 'category', 'item')
            ->when(!$isSuperAdmin, function ($q) use ($authUser) {
                return $q->whereHas('purchase_order.location', function ($query) use ($authUser) {
                    $query->where('location_id', $authUser->company_location_id);
                });
            })
            ->whereStatus(true)->latest()
            ->paginate(request('per_page', 25));

        return view('management.procurement.store.purchase_order_recieving.getList', compact('PurchaseOrder'));
    }

    public function approve_item(Request $request)
    {
        $requestId = $request->id;

        $master = PurchaseRequest::find($requestId);
        $dataItems = PurchaseRequestData::with(['purchase_request', 'item', 'category', 'approved_purchase_quotation'])
            ->where('purchase_request_id', $requestId)
            ->where('am_approval_status', 'approved')
            ->get();

        $categories = Category::select('id', 'name')->where('category_type', 'general_items')->get();
        $job_orders = JobOrder::select('id', 'name')->get();

        $html = view('management.procurement.store.purchase_order_recieving.purchase_data', compact('dataItems', 'categories', 'job_orders'))->render();

        return response()->json(
            ['html' => $html, 'master' => $master]
        );
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $approvedRequests = PurchaseRequest::with(['PurchaseData' => function ($query) {
            $query->where('am_approval_status', 'approved');
        }])
            ->whereHas('PurchaseData', function ($q) {
                $q->where('am_approval_status', 'approved')
                    ->whereRaw('qty > (SELECT COALESCE(SUM(qty), 0) FROM purchase_order_data WHERE purchase_request_data_id = purchase_request_data.id)');
            })
            ->get();

        $categories = Category::select('id', 'name')->where('category_type', 'general_items')->get();

        return view('management.procurement.store.purchase_order_recieving.create', compact('categories', 'approvedRequests'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(PurchaseOrderReceivingRequest $request)
    {
        DB::beginTransaction();

        try {
            $grnNo = generateLocationBasedCode('grn_numbers', $request->location_code ?? 'KHI');

            $grnNumber = GrnNumber::create([
                'model_id' => $request->data_id,
                'model_type' => 'purchase-order-data',
                'unique_no' => $grnNo,
                'location_id' => $request->location_id,
                'product_id' => $request->item_id,
            ]);

            $stock = Stock::create([
                'avg_price_per_kg' => $request->total_amount / $request->receiving_qty,
                'price' => $request->total_amount,
                'qty' => $request->receiving_qty,
                'voucher_no' => $grnNo,
                'type' => 'stock-in',
                'voucher_type' => 'grn',
                'product_id' => $request->item_id,
            ]);

            $goodReceiveNote = GoodReceiveNote::create([
                'grn_id' => $grnNumber->id,
                'stock_id' => $stock->id,
                'grn_number' => $grnNo,
                'reference_number' => $request->reference_number ?? null,
                'supplier_id' => $request->supplier_id ?? null,
                'location_id' => $request->location_id,
                'purchase_order_id' => $request->purchase_order_id ?? null,
                'product_id' => $request->item_id,
                'model_id' => $request->data_id,
                'model_type' => 'purchase-order-data',
                'voucher_type' => 'grn',
                'voucher_no' => $grnNo,
                'qty' => $request->receiving_qty,
                'type' => 'stock-in',
                'price' => $request->total_amount,
                'avg_price_per_kg' => $request->total_amount / $request->receiving_qty,
                'narration' => $request->narration ?? null,
                'status' => 'received',
                'received_at' => now(),
                'received_by' => Auth::user()->id,
                'notes' => $request->notes ?? null,
                'batch_number' => $request->batch_number ?? null,
                'expiry_date' => $request->expiry_date ?? null,
                'quality_status' => $request->quality_status ?? 'pending',
                'accepted_quantity' => $request->receiving_qty,
                'rejected_quantity' => 0,
            ]);

            DB::commit();

            return response()->json([
                'success' => 'Grn created successfully.',
                'data' => $grnNumber,
            ], 201);
        } catch (\Exception $e) {
            DB::rollback();

            return response()->json([
                'success' => false,
                'message' => 'Failed to create purchase request.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $categories = Category::select('id', 'name')->where('category_type', 'general_items')->get();
        $locations = CompanyLocation::select('id', 'name')->get();
        $job_orders = JobOrder::select('id', 'name')->get();
        $data = PurchaseOrderData::with('purchase_order', 'category', 'item')
            ->findOrFail($id);

        return view('management.procurement.store.purchase_order_recieving.edit', compact('data', 'categories', 'locations', 'job_orders'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'purchase_date'    => 'required|date',
            'purchase_request_id'      => 'required|exists:purchase_requests,id',
            'location_id'      => 'required|exists:company_locations,id',
            'reference_no'     => 'nullable|string|max:255',
            'description'      => 'nullable|string',

            'category_id'      => 'required|array|min:1',
            'category_id.*'    => 'required|exists:categories,id',

            'item_id'          => 'required|array|min:1',
            'item_id.*'        => 'required|exists:products,id',

            'uom'              => 'nullable|array',
            'uom.*'            => 'nullable|string|max:255',

            'qty'              => 'required|array|min:1',
            'qty.*'            => 'required|numeric|min:0.01',

            'rate'              => 'required|array|min:1',
            'rate.*'            => 'required|numeric|min:0.01',

            'remarks'          => 'nullable|array',
            'remarks.*'        => 'nullable|string|max:1000',
        ]);




        DB::beginTransaction();
        try {
            // Find existing purchase request by ID
            $PurchaseOrder = PurchaseOrder::findOrFail($id);

            // Update purchase request fields (do NOT update purchase_order_no)

            // Delete existing related purchase_order_data and their job orders to avoid duplicates
            $data = PurchaseOrderData::find($request->data_id)->delete();

            // Insert new purchase_order_data and job orders
            foreach ($request->item_id as $index => $itemId) {
                // Save purchase_order_data
                $requestData = PurchaseOrderData::create([
                    'purchase_order_id' => $PurchaseOrder->id,
                    'category_id' => $request->category_id[$index],
                    'item_id' => $itemId,
                    'qty' => $request->qty[$index],
                    'rate' => $request->rate[$index],
                    'total' => $request->total[$index],
                    'supplier_id' => $request->supplier_id[$index],
                    'remarks' => $request->remarks[$index] ?? null,
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Purchase Quotation updated successfully.',
                'data' => $PurchaseOrder,
            ], 200);
        } catch (\Exception $e) {
            DB::rollback();

            return response()->json([
                'success' => false,
                'message' => 'Failed to update purchase request.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $PurchaseOrderData = PurchaseOrderData::where('id', $id)->delete();
        return response()->json(['success' => 'Purchase Request deleted successfully.'], 200);
    }

    public function getNumber(Request $request, $locationId = null, $contractDate = null)
    {
        $location = CompanyLocation::find($locationId ?? $request->location_id);
        $date = Carbon::parse($contractDate ?? $request->contract_date)->format('Y-m-d');

        $prefix = $location->code . '-' . Carbon::parse($contractDate ?? $request->contract_date)->format('Y-m-d');

        $latestContract = PurchaseOrder::where('purchase_order_no', 'like', "$prefix-%")
            ->latest()
            ->first();

        $locationCode = $location->code ?? 'LOC';
        $datePart = Carbon::parse($date)->format('Y-m-d');

        if ($latestContract) {
            $parts = explode('-', $latestContract->contract_no);
            $lastNumber = (int) end($parts);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        $purchase_order_no = $locationCode . '-' . $datePart . '-' . str_pad($newNumber, 3, '0', STR_PAD_LEFT);

        if (!$locationId && !$contractDate) {
            return response()->json([
                'success' => true,
                'purchase_order_no' => $purchase_order_no
            ]);
        }

        return $purchase_order_no;
    }
}
