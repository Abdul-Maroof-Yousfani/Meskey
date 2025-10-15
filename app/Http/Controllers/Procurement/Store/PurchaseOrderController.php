<?php

namespace App\Http\Controllers\Procurement\Store;

use App\Http\Controllers\Controller;
use App\Http\Requests\Procurement\Store\PurchaseOrderRequest;
use App\Models\Category;
use App\Models\Master\CompanyLocation;
use App\Models\Procurement\Store\PurchaseOrder;
use App\Models\Procurement\Store\PurchaseOrderData;
use App\Models\Procurement\Store\PurchaseQuotationData;
use App\Models\Procurement\Store\PurchaseRequest;
use App\Models\Procurement\Store\PurchaseRequestData;
use App\Models\Sales\JobOrder;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseOrderController extends Controller
{
    public function index()
    {
        return view('management.procurement.store.purchase_order.index');
    }

    /**
     * Get list of categories.
     */
    public function getList(Request $request)
    {
        $PurchaseOrder = PurchaseOrderData::with('purchase_order', 'category', 'item')
            ->whereStatus(true)->latest()
            ->paginate(request('per_page', 25));

        return view('management.procurement.store.purchase_order.getList', compact('PurchaseOrder'));
    }

    public function approve_item(Request $request)
    {
        $requestId = $request->id;

        $master = PurchaseRequest::find($requestId);
        $dataItems = PurchaseRequestData::with(['purchase_request', 'item', 'category', 'approved_purchase_quotation'])
            ->where('purchase_request_id', $requestId)
            // ->where('am_approval_status', 'approved')
            ->get();

        $categories = Category::select('id', 'name')->where('category_type', 'general_items')->get();
        $job_orders = JobOrder::select('id', 'name')->get();

        $html = view('management.procurement.store.purchase_order.purchase_data', compact('dataItems', 'categories', 'job_orders'))->render();

        return response()->json(
            ['html' => $html, 'master' => $master]
        );
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $approvedRequests = PurchaseRequest::where('am_approval_status', 'approved')->with(['PurchaseData' => function ($query) {
            // $query->where('am_approval_status', 'approved');
        }])
            ->whereHas('PurchaseData', function ($q) {
                $q->whereRaw('qty > (SELECT COALESCE(SUM(qty), 0) FROM purchase_order_data WHERE purchase_request_data_id = purchase_request_data.id)');
            })
            ->get();

        $categories = Category::select('id', 'name')->where('category_type', 'general_items')->get();

        return view('management.procurement.store.purchase_order.create', compact('categories', 'approvedRequests'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(PurchaseOrderRequest $request)
    {
        DB::beginTransaction();

        try {
            $PurchaseOrder = PurchaseOrder::create([
                'purchase_order_no' => self::getNumber($request, $request->location_id, $request->purchase_date),
                'purchase_request_id' => $request->purchase_request_id,
                'order_date' => $request->purchase_date,
                'location_id' => $request->location_id,
                'company_id' => $request->company_id,
                'reference_no' => $request->reference_no,
                'description' => $request->description,
            ]);

            foreach ($request->item_id as $index => $itemId) {
                $requestData = PurchaseOrderData::create([
                    'purchase_order_id' => $PurchaseOrder->id,
                    'category_id' => $request->category_id[$index],
                    'purchase_request_data_id' => $request->purchase_request_data_id[$index] ?? null,
                    'purchase_quotation_data_id' => isset($request->purchase_quotation_data_id[$index]) ? $request->purchase_quotation_data_id[$index] : null,
                    'item_id' => $itemId,
                    'qty' => $request->qty[$index],
                    'rate' => $request->rate[$index],
                    'total' => $request->total[$index],
                    'supplier_id' => $request->supplier_id[$index],
                    'remarks' => $request->remarks[$index] ?? null,
                ]);

                if ($request->purchase_request_data_id[$index] != 0) {
                    $data =  PurchaseRequestData::find($request->purchase_request_data_id[$index])->update([
                        'po_status' => 2,
                    ]);
                }

                if ($request->purchase_quotation_data_id[$index] != 0) {
                    $data =  PurchaseQuotationData::find($request->purchase_quotation_data_id[$index])->update([
                        'quotation_status' => 2,
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => 'Purchase order created successfully.',
                'data' => $PurchaseOrder,
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
        return view('management.procurement.store.purchase_order.edit', compact('data', 'categories', 'locations', 'job_orders'));
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
