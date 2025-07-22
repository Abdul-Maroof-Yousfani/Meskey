<?php

namespace App\Http\Controllers\Procurement\Store;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Master\CompanyLocation;
use App\Models\Procurement\Store\PurchaseAgainstJobOrder;
use Illuminate\Http\Request;
use App\Models\Procurement\Store\PurchaseRequest;
use App\Models\Procurement\Store\PurchaseRequestData;
use App\Models\Sales\JobOrder;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PurchaseRequestController extends Controller
{
    public function index()
    {
        return view('management.procurement.store.purchase_request.index');
    }

     /**
     * Get list of categories.
     */
    public function getList(Request $request)
    {
        $PurchaseRequests = PurchaseRequest::when($request->filled('search'), function ($q) use ($request) {
            $searchTerm = '%' . $request->search . '%';
            return $q->where(function ($sq) use ($searchTerm) {
                $sq->where('purchase_request_no', 'like', $searchTerm);
            });
        })
        ->latest()
        ->paginate(request('per_page', 25));

        return view('management.procurement.store.purchase_request.getList', compact('PurchaseRequests'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $categories = Category::select('id', 'name')->where('category_type','general_items')->get();
        $job_orders = JobOrder::select('id', 'name')->get();
        return view('management.procurement.store.create',compact('categories','job_orders'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'purchase_date'    => 'required|date',
            'company_location_id'      => 'required|exists:company_locations,id',
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

            'job_order_id'     => 'nullable|array',
            'job_order_id.*'   => 'nullable|exists:job_orders,id',

            'remarks'          => 'nullable|array',
            'remarks.*'        => 'nullable|string|max:1000',
        ]);


        DB::beginTransaction();
        try {

            $datePrefix = date('m-d-Y') . '-';
            $purchaseRequest = PurchaseRequest::create([
                'purchase_request_no' => self::getNumber($request, $request->company_location_id, $request->purchase_date),
                'purchase_date' => $request->purchase_date,
                'location_id' => $request->company_location_id,
                'company_id' => $request->company_id,
                'reference_no' => $request->reference_no,
                'description' => $request->description,
            ]);

            foreach ($request->item_id as $index => $itemId) {
                // Save purchase_request_data
                $requestData = PurchaseRequestData::create([
                    'purchase_request_id' => $purchaseRequest->id,
                    'category_id' => $request->category_id[$index],
                    'item_id' => $itemId,
                    'qty' => $request->qty[$index],
                    'remarks' => $request->remarks[$index] ?? null,
                ]);

                // Insert related job orders if any
                if (!empty($request->job_order_id[$index]) && is_array($request->job_order_id[$index])) {
                    foreach ($request->job_order_id[$index] as $jobOrderId) {
                        PurchaseAgainstJobOrder::create([
                            'purchase_request_id' => $purchaseRequest->id,
                            'purchase_request_data_id' => $requestData->id,
                            'job_order_id' => $jobOrderId,
                        ]);
                    }
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Purchase request created successfully.',
                'data' => $purchaseRequest,
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
        $categories = Category::select('id', 'name')->where('category_type','general_items')->get();
        $locations = CompanyLocation::select('id', 'name')->get();
        $job_orders = JobOrder::select('id', 'name')->get();
        $purchase_request = PurchaseRequest::with(['PurchaseData'])
            ->findOrFail($id);
        return view('management.procurement.store.edit',compact('purchase_request','categories','locations','job_orders'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'purchase_date'    => 'required|date',
            'company_location_id' => 'required|exists:company_locations,id',
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

            'job_order_id'     => 'nullable|array',
            'job_order_id.*'   => 'nullable|exists:job_orders,id',

            'remarks'          => 'nullable|array',
            'remarks.*'        => 'nullable|string|max:1000',
        ]);



        
        DB::beginTransaction();
        try {
            // Find existing purchase request by ID
            $purchaseRequest = PurchaseRequest::findOrFail($id);

            // Update purchase request fields (do NOT update purchase_request_no)
            $purchaseRequest->update([
                'purchase_date' => $request->purchase_date,
                'location_id' => $request->company_location_id,
                'company_id' => $request->company_id,
                'reference_no' => $request->reference_no,
                'description' => $request->description,
            ]);

            // Delete existing related purchase_request_data and their job orders to avoid duplicates
            foreach ($purchaseRequest->PurchaseData as $existingData) {
                PurchaseAgainstJobOrder::where('purchase_request_data_id', $existingData->id)->delete();
                $existingData->delete();
            }

            // Insert new purchase_request_data and job orders
            foreach ($request->item_id as $index => $itemId) {

                $requestData = PurchaseRequestData::create([
                    'purchase_request_id' => $purchaseRequest->id,
                    'category_id' => $request->category_id[$index],
                    'item_id' => $itemId,
                    'qty' => $request->qty[$index],
                    'remarks' => $request->remarks[$index] ?? null,
                ]);

                if (!empty($request->job_order_id[$index]) && is_array($request->job_order_id[$index])) {
                    foreach ($request->job_order_id[$index] as $jobOrderId) {
                        PurchaseAgainstJobOrder::create([
                            'purchase_request_id' => $purchaseRequest->id,
                            'purchase_request_data_id' => $requestData->id,
                            'job_order_id' => $jobOrderId,
                        ]);
                    }
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Purchase request updated successfully.',
                'data' => $purchaseRequest,
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
        $PurchaseRequest = PurchaseRequest::findOrFail($id);
        $PurchaseRequestData = PurchaseRequestData::where('purchase_request_id',$id)->delete();
        $PurchaseRequest->delete();
        return response()->json(['success' => 'Purchase Request deleted successfully.'], 200);
    }

    public function getNumber(Request $request, $locationId = null, $contractDate = null)
    {
        $location = CompanyLocation::find($locationId ?? $request->company_location_id);
        $date = Carbon::parse($contractDate ?? $request->contract_date)->format('Y-m-d');

        $prefix = $location->code . '-' . Carbon::parse($contractDate ?? $request->contract_date)->format('Y-m-d');

        $latestContract = PurchaseRequest::where('purchase_request_no', 'like', "$prefix-%")
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

        $purchase_request_no = $locationCode . '-' . $datePart . '-' . str_pad($newNumber, 3, '0', STR_PAD_LEFT);

        if (!$locationId && !$contractDate) {
            return response()->json([
                'success' => true,
                'purchase_request_no' => $purchase_request_no
            ]);
        }

        return $purchase_request_no;
    }

}
