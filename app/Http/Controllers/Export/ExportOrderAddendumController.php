<?php

namespace App\Http\Controllers\Export;

use App\Http\Controllers\Controller;
use App\Models\Export\ExportOrder;
use App\Models\Export\ExportOrderAddendum;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ExportOrderAddendumController extends Controller
{
    public function index(Request $request): View
    {
        return view('management.export.export-order-addendum.index');
    }

    public function getList(Request $request): View
    {
        $search = trim((string) $request->get('search'));
        
        $addendums = ExportOrderAddendum::with(['exportOrder.buyer', 'exportOrder.product', 'creator'])
            ->when($search !== '', function ($query) use ($search) {
                $query->whereHas('exportOrder', function ($q) use ($search) {
                    $q->where('voucher_no', 'like', "%{$search}%")
                      ->orWhere('contract_no', 'like', "%{$search}%")
                      ->orWhereHas('buyer', function ($q2) use ($search) {
                          $q2->where('name', 'like', "%{$search}%");
                      });
                })->orWhere('remarks', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate(request('per_page', 25));

        return view('management.export.export-order-addendum.getList', compact('addendums'));
    }

    public function create(): View
    {
        $exportOrders = ExportOrder::with(['buyer', 'product'])
            ->where('am_approval_status', 'approved')
            ->whereNotIn('id', function($q) {
                $q->select('export_order_id')->from('export_order_addendums');
            })
            ->latest()
            ->get();

        return view('management.export.export-order-addendum.create', compact('exportOrders'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'export_order_id' => 'required|exists:export_orders,id',
            'remarks' => 'nullable|string',
        ]);

        $exists = ExportOrderAddendum::where('export_order_id', $request->export_order_id)->exists();
        if ($exists) {
            return response()->json(['success' => false, 'message' => 'Addendum for this Export Order already exists.']);
        }

        DB::beginTransaction();
        try {
            ExportOrderAddendum::create([
                'export_order_id' => $request->export_order_id,
                'remarks' => $request->remarks,
                'am_approval_status' => 'approved',
                'am_change_made' => 1,
                'created_by' => auth()->user()->id,
            ]);

            DB::commit();
            return response()->json(['success' => 'Addendum created successfully.']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'catchError' => $e->getMessage()]);
        }
    }

    public function show($id): View
    {
        $addendum = ExportOrderAddendum::with(['exportOrder.buyer', 'exportOrder.product', 'exportOrder.broker', 'exportOrder.currency', 'creator'])->findOrFail($id);
        
        return view('management.export.export-order-addendum.show', compact('addendum'));
    }
}
