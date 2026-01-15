<?php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use App\Models\Production\JobOrder\JobOrder;
use App\Models\Production\ProductionVoucher;
use Illuminate\Http\Request;

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
        $productionVoucher = ProductionVoucher::find($id);
        return view('management.production.production_quality_check.edit', compact('productionVoucher'));
    }
    public function update(Request $request, $id)
    {
        $productionVoucher = ProductionVoucher::find($id);
        $productionVoucher->update($request->all());
        return redirect()->route('production-quality-check.index')->with('success', 'Production Quality Check updated successfully');
    }
}