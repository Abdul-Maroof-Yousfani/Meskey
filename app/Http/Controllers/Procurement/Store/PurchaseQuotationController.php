<?php

namespace App\Http\Controllers\Procurement\Store;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Category;
use App\Models\Master\CompanyLocation;
use App\Models\Procurement\Store\PurchaseAgainstJobOrder;
use App\Models\Procurement\Store\PurchaseItemApprove;
use App\Models\Procurement\Store\PurchaseQuotation;
use App\Models\Procurement\Store\PurchaseQuotationData;
use App\Models\Procurement\Store\PurchaseRequest;
use App\Models\Procurement\Store\PurchaseRequestData;
use App\Models\Sales\JobOrder;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
class PurchaseQuotationController extends Controller
{
    public function index()
    {
        return view('management.procurement.store.purchase_quotation.index');
    }

     /**
     * Get list of categories.
     */
    public function getList(Request $request)
    {
        $PurchaseQuotation = PurchaseQuotationData::with('purchase_quotation','category','item','approval')
        // ->when($request->filled('search'), function ($q) use ($request) {
        //     $searchTerm = '%' . $request->search . '%';
        //     return $q->where(function ($sq) use ($searchTerm) {
        //         $sq->where('purchase_quotation_no', 'like', $searchTerm);
        //     });
        // })
        ->whereStatus(true)->latest()
        ->paginate(request('per_page', 25));

        return view('management.procurement.store.purchase_quotation.getList', compact('PurchaseQuotation'));
    }

    public function approve_item(Request $request){
        
        $requestId = $request->id;

        $master = PurchaseRequest::find($requestId);
        $dataItems = PurchaseRequestData::with(['approval', 'purchase_request', 'item', 'category'])
        ->where('purchase_request_id', $requestId)
        ->where('status', 1)
        ->where('quotation_status',1)
        ->whereHas('approval') // sirf existence check
        ->get();
        
        $categories = Category::select('id', 'name')->where('category_type','general_items')->get();
        $job_orders = JobOrder::select('id', 'name')->get();


        $html = view('management.procurement.store.purchase_quotation.purchase_data', compact('dataItems','categories','job_orders'))->render();

        return response()->json(
            ['html' => $html, 'master' => $master]
        );
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {

        $approvedRequests = PurchaseRequestData::with(['approval','purchase_request'])
        ->where('status', 1)
        ->where('quotation_status',1)
        ->whereHas('approval') // sirf existence check
        ->select('purchase_request_id')
        ->groupBy('purchase_request_id')
        ->get();
        
        $categories = Category::select('id', 'name')->where('category_type','general_items')->get();



        return view('management.procurement.store.purchase_quotation.create',compact('categories','approvedRequests'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
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

            $datePrefix = date('m-d-Y') . '-';
            $PurchaseQuotation = PurchaseQuotation::create([
                'purchase_quotation_no' => self::getNumber($request, $request->location_id, $request->purchase_date),
                'purchase_request_id' => $request->purchase_request_id,
                'quotation_date' => $request->purchase_date,
                'location_id' => $request->location_id,
                'company_id' => $request->company_id,
                'reference_no' => $request->reference_no,
                'description' => $request->description,
            ]);

            foreach ($request->item_id as $index => $itemId) {
                // Save purchase_quotation_data
                $requestData = PurchaseQuotationData::create([
                    'purchase_quotation_id' => $PurchaseQuotation->id,
                    'category_id' => $request->category_id[$index],
                    'item_id' => $itemId,
                    'qty' => $request->qty[$index],
                    'rate' => $request->rate[$index],
                    'total' => $request->total[$index],
                    'supplier_id' => $request->supplier_id[$index],
                    'remarks' => $request->remarks[$index] ?? null,
                ]);

                if($request->data_id[$index] != 0){
                   $data =  PurchaseRequestData::find($request->data_id[$index])->update([
                      'quotation_status' => 2,
                   ]);
                }
                
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Purchase request created successfully.',
                'data' => $PurchaseQuotation,
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
        $data = PurchaseQuotationData::with('purchase_quotation','category','item')
            ->findOrFail($id);
        return view('management.procurement.store.purchase_quotation.edit',compact('data','categories','locations','job_orders'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'purchase_date'    => 'required|date',
            'location_id' => 'required|exists:company_locations,id',
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
            $PurchaseQuotation = PurchaseQuotation::findOrFail($id);

            // Update purchase request fields (do NOT update purchase_quotation_no)
            $PurchaseQuotation->update([
                'purchase_date' => $request->purchase_date,
                'location_id' => $request->location_id,
                'company_id' => $request->company_id,
                'reference_no' => $request->reference_no,
                'description' => $request->description,
            ]);

            // Delete existing related purchase_quotation_data and their job orders to avoid duplicates
            foreach ($PurchaseQuotation->PurchaseData as $existingData) {
                PurchaseAgainstJobOrder::where('purchase_quotation_data_id', $existingData->id)->delete();
                $existingData->delete();
            }

            // Insert new purchase_quotation_data and job orders
            foreach ($request->item_id as $index => $itemId) {

                $requestData = PurchaseQuotationData::create([
                    'purchase_quotation_id' => $PurchaseQuotation->id,
                    'category_id' => $request->category_id[$index],
                    'item_id' => $itemId,
                    'qty' => $request->qty[$index],
                    'remarks' => $request->remarks[$index] ?? null,
                ]);

                if (!empty($request->job_order_id[$index]) && is_array($request->job_order_id[$index])) {
                    foreach ($request->job_order_id[$index] as $jobOrderId) {
                        PurchaseAgainstJobOrder::create([
                            'purchase_quotation_id' => $PurchaseQuotation->id,
                            'purchase_quotation_data_id' => $requestData->id,
                            'job_order_id' => $jobOrderId,
                        ]);
                    }
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Purchase request updated successfully.',
                'data' => $PurchaseQuotation,
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
        // $PurchaseQuotation = PurchaseQuotation::findOrFail($id);
        $PurchaseQuotationData = PurchaseQuotationData::where('id',$id)->delete();
        // $PurchaseQuotation->delete();
        return response()->json(['success' => 'Purchase Request deleted successfully.'], 200);
    }

    public function getNumber(Request $request, $locationId = null, $contractDate = null)
    {
        $location = CompanyLocation::find($locationId ?? $request->location_id);
        $date = Carbon::parse($contractDate ?? $request->contract_date)->format('Y-m-d');

        $prefix = $location->code . '-' . Carbon::parse($contractDate ?? $request->contract_date)->format('Y-m-d');

        $latestContract = PurchaseQuotation::where('purchase_quotation_no', 'like', "$prefix-%")
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

        $purchase_quotation_no = $locationCode . '-' . $datePart . '-' . str_pad($newNumber, 3, '0', STR_PAD_LEFT);

        if (!$locationId && !$contractDate) {
            return response()->json([
                'success' => true,
                'purchase_quotation_no' => $purchase_quotation_no
            ]);
        }

        return $purchase_quotation_no;
    }

}
