<?php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use App\Models\Master\CompanyLocation;
use App\Models\Product;
use App\Models\Production\JobOrder\JobOrder;
use App\Models\Production\JobOrder\JobOrderRawMaterialQc;
use App\Models\Production\ProductionVoucher;
use App\Models\Production\ProductionOutput;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductionQualityCheckController extends Controller
{
    public function index()
    {
        return view('management.production.production_quality_check.index');
    }


    public function getList(Request $request)
    {
        $query = ProductionVoucher::with([
            'jobOrder',
            'jobOrders.packingItems.companyLocation',
            'jobOrders.product',
            'location',
            'supervisor',
            'outputs.jobOrder'
        ]);

        // Apply search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('prod_no', 'like', '%' . $search . '%')
                    ->orWhereHas('jobOrder', function ($q) use ($search) {
                        $q->where('job_order_no', 'like', '%' . $search . '%')
                            ->orWhere('ref_no', 'like', '%' . $search . '%');
                    })
                    ->orWhereHas('location', function ($q) use ($search) {
                        $q->where('name', 'like', '%' . $search . '%');
                    })
                    ->orWhereHas('supervisor', function ($q) use ($search) {
                        $q->where('name', 'like', '%' . $search . '%');
                    });
            });
        }

        // Filter by job order (check both old job_order_id and pivot table)
        if ($request->filled('job_order_id')) {
            $query->where(function ($q) use ($request) {
                $q->where('job_order_id', $request->job_order_id)
                    ->orWhereHas('jobOrders', function ($q) use ($request) {
                        $q->where('job_orders.id', $request->job_order_id);
                    });
            });
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('prod_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('prod_date', '<=', $request->date_to);
        }

        // Filter by location
        if ($request->filled('location_id')) {
            $query->where('location_id', $request->location_id);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Apply sorting
        $sortField = $request->get('sort', 'prod_no');
        $sortDirection = $request->get('direction', 'desc');

        if (in_array($sortField, ['prod_no', 'prod_date', 'status'])) {
            $query->orderBy($sortField, $sortDirection);
            if ($sortField !== 'id') {
                $query->orderBy('id', $sortDirection);
            }
        } else {
            $query->orderBy('prod_no', 'desc')->orderBy('id', 'desc');
        }

        // Paginate results
        $productionVouchers = $query->paginate(request('per_page', 25));

        // Calculate job order-wise allocated and produced quantities for each voucher
        foreach ($productionVouchers as $voucher) {
            $jobOrderData = [];
            $voucher->producedByJobOrder = []; // Initialize as empty array

            // Get all job orders for this voucher
            $jobOrders = $voucher->jobOrders->count() > 0 ? $voucher->jobOrders : collect([$voucher->jobOrder])->filter();

            // Get allocated quantities from packing items (only for this voucher's location)
            foreach ($jobOrders as $jobOrder) {
                if (!$jobOrder)
                    continue;

                $allocatedQty = 0;
                // Only count packing items for this voucher's location
                foreach ($jobOrder->packingItems as $packingItem) {
                    if ($packingItem->company_location_id == $voucher->location_id) {
                        $allocatedQty += $packingItem->total_kgs ?? 0;
                    }
                }

                $jobOrderData[$jobOrder->id] = [
                    'job_order_no' => $jobOrder->job_order_no,
                    'job_order_ref_no' => $jobOrder->ref_no ?? null,
                    'allocated_qty' => $allocatedQty,
                    'produced_qty' => 0
                ];
            }

            // Get produced quantities from production outputs (grouped by job order)
            foreach ($voucher->outputs as $output) {
                $targetJobOrderId = $output->job_order_id;
                if (!$targetJobOrderId && count($jobOrderData) === 1) {
                    $targetJobOrderId = array_key_first($jobOrderData);
                }
                if ($targetJobOrderId && isset($jobOrderData[$targetJobOrderId])) {
                    $jobOrderData[$targetJobOrderId]['produced_qty'] += $output->qty ?? 0;
                }
            }

            // Calculate remaining for each job order
            foreach ($jobOrderData as &$data) {
                $data['remaining_qty'] = $data['allocated_qty'] - $data['produced_qty'];
            }

            $voucher->producedByJobOrder = $jobOrderData;
        }

      

        // Return view with data
        return view('management.production.production_quality_check.getList', compact(
            'productionVouchers'
        ));
    }


    public function edit($id)
    {
        $productionVoucher = ProductionVoucher::with([
            'jobOrder.product',
            'jobOrders.product',
            'location',
            'product',
            'supervisor',
            'inputs.product',
            'inputs.location',
            'outputs.product',
            'outputs.storageLocation',
            'outputs.brand',
            'slots.breaks',
            'headProduct',
            'byProducts',
        ])->findOrFail($id);

        $jobOrderRawMaterialQcs = JobOrderRawMaterialQc::whereIn('job_order_id', $productionVoucher->jobOrders->pluck('id'))->get();

        $headProductId = $productionVoucher->product_id;
        $headProduct = \App\Models\Product::find($headProductId);
        $parentId = ($headProduct && $headProduct->parent_id) ? $headProduct->parent_id : $headProductId;
        $allHeadProductIds = \App\Models\Product::where(function ($q) use ($parentId) {
            $q->where('id', $parentId)->orWhere('parent_id', $parentId);
        })->whereIn('product_category_flags', ['head', 'b2'])->pluck('id')->toArray();

        $headProductOutputs = $productionVoucher->outputs->whereIn('product_id', $allHeadProductIds);
        $byProductOutputs = $productionVoucher->outputs->whereNotIn('product_id', $allHeadProductIds);
        $jobOrders = JobOrder::where('status', 1)->get();
        $companyLocations = CompanyLocation::where('status', 'active')->get();
        // $supervisors = User::where('status', 'active')->get();
        // $products = Product::where('status', 1)->get();
        // $sublocations = ArrivalSubLocation::where('status', 1)->get();
        // $brands = Brands::where('status', 1)->get();
        $plants = \App\Models\Master\Plant::where('status', 'active')->get();

        return view('management.production.production_quality_check.edit', compact(
            'productionVoucher',
            'jobOrders',
            'companyLocations',
            // 'supervisors',
            // 'products',
            // 'sublocations',
            // 'brands',
            'headProductOutputs',
            'byProductOutputs',
            'plants'
        )); 
    }
    public function update(Request $request, $id)
    {
        DB::beginTransaction();
        
        try { 
            $productionVoucher = ProductionVoucher::findOrFail($id);
            
            // Check if already qc_completed
            if ($productionVoucher->status === 'qc_completed') {
                DB::rollBack();
                return response()->json([
                    'error' => ['general' => 'This production voucher has already been quality checked and cannot be updated again.'],
                    'catchError' => 'This production voucher has already been quality checked and cannot be updated again.'
                ], 422);
            }
            
            // Validate request
            $request->validate([
                'qc_remarks' => 'nullable|string|max:1000',
                'qc_status' => 'nullable|array',
                'qc_status.*' => 'nullable|in:local_sales,export_sales,re_milling,other_consignment,sale_on_high_rate'
            ]);
            
            // Update production voucher if needed
            // $productionVoucher->update($request->only([
            //     'prod_date',
            //     'location_id',
            //     'product_id',
            //     'plant_id',
            //     'by_product_id',
            //     'remarks'
            // ]));
            
            $productionVoucher->update(['status' => 'qc_completed', 'qc_remarks' => $request->qc_remarks ?? null]);
            
            // Determine all head product IDs for this voucher (head and b2)
            $headProductId = $productionVoucher->product_id;
            $headProduct = Product::find($headProductId);
            $voucherParentId = ($headProduct && $headProduct->parent_id) ? $headProduct->parent_id : $headProductId;
            $allHeadProductIds = Product::where(function ($q) use ($voucherParentId) {
                $q->where('id', $voucherParentId)->orWhere('parent_id', $voucherParentId);
            })->whereIn('product_category_flags', ['head', 'b2'])->pluck('id')->toArray();

            // Update qc_status for all outputs
            if ($request->has('qc_status') && is_array($request->qc_status)) {
                foreach ($request->qc_status as $outputId => $qcStatus) {
                    $output = ProductionOutput::with(['product.parent'])
                        ->where('id', $outputId)
                        ->where('production_voucher_id', $id)
                        ->first();
                    
                    if ($output) {
                        $output->update(['qc_status' => $qcStatus]);
                        if ($qcStatus == 're_milling' && $output->product) {
                            $nameProduct = $output->product->name . '-Re-milling';

                            $isHeadProduct = in_array($output->product_id, $allHeadProductIds) 
                                || in_array($output->product->product_category_flags, ['head', 'b2']);

                            $parentProduct = $output->product->parent_id != null 
                                ? ($output->product->parent ?? Product::find($output->product->parent_id))
                                : $output->product;

                            $parentId = $parentProduct ? $parentProduct->id : $output->product->id;

                            // When setting Head Products Actions into re-milling, set product_category_flags to what that product's parent has
                            if ($isHeadProduct) {
                                $categoryFlag = $parentProduct ? $parentProduct->product_category_flags : ($output->product->product_category_flags ?? 'head');
                            } else {
                                $categoryFlag = $output->product->product_category_flags ?? 'by';
                            }

                            $product = Product::where('name', $nameProduct)
                                ->where('company_id', $productionVoucher->company_id)
                                ->first();

                            if (!$product) {
                                $product = Product::create([
                                    'name' => $nameProduct,
                                    'status' => 'active',
                                    'company_id' => $productionVoucher->company_id,
                                    'product_type' => 'raw_material',
                                    'parent_id' => $parentId,
                                    'category_id' => $output->product->category_id,
                                    'unit_of_measure_id' => $output->product->unit_of_measure_id,
                                    'product_category_flags' => $categoryFlag,
                                ]);
                            } else {
                                $product->update([
                                    'product_category_flags' => $categoryFlag,
                                    'parent_id' => $product->parent_id ?? $parentId,
                                ]);
                            }
                        }
                    }
                }
            }
            
            DB::commit();
            
            return response()->json([
                'success' => 'Production Quality Check updated successfully',
                'error' => (object)[]
            ], 200);
                
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => ['general' => 'Error updating quality check: ' . $e->getMessage()],
                'catchError' => 'Error updating quality check: ' . $e->getMessage()
            ], 500);
        }
    }
}