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
        $sortField = $request->get('sort', 'prod_date');
        $sortDirection = $request->get('direction', 'desc');

        if (in_array($sortField, ['prod_no', 'prod_date', 'status'])) {
            $query->orderBy($sortField, $sortDirection);
        } else {
            $query->orderBy('prod_date', 'desc')->orderBy('created_at', 'desc');
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
                if ($output->job_order_id && isset($jobOrderData[$output->job_order_id])) {
                    $jobOrderData[$output->job_order_id]['produced_qty'] += $output->qty ?? 0;
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

        $headProductOutputs = $productionVoucher->outputs->where('product_id', $productionVoucher->headProduct->id);
        $byProductOutputs = $productionVoucher->outputs->where('product_id','!=', $productionVoucher->headProduct->id);
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
            
            // Update qc_status for all outputs
            if ($request->has('qc_status') && is_array($request->qc_status)) {
                foreach ($request->qc_status as $outputId => $qcStatus) {
                    $output = ProductionOutput::where('id', $outputId)
                        ->where('production_voucher_id', $id)
                        ->first();
                    
                    if ($output) {
                        $output->update(['qc_status' => $qcStatus]);
                        if($qcStatus == 're_milling') {
                            $nameProduct = $output->product->name. '-Re-milling';
                           
                            $product = Product::where('name', $nameProduct)->firstOrCreate([
                                'name' => $nameProduct,
                                'status' => 'active',
                                'company_id' => $productionVoucher->company_id,
                                'product_type' =>  'raw_material',
                                'parent_id' => $output->product->id,
                                'category_id' => $output->product->category_id,
                                'unit_of_measure_id' => $output->product->unit_of_measure_id,
                
                            ]);
                            dd($product);
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