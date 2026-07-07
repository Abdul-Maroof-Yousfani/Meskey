<?php

namespace App\Http\Controllers\Export;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Export\CFreight;
use App\Models\Export\CFreightRate;
use App\Models\Export\ExportOrder;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;

class CFreightController extends Controller
{
    public function index(Request $request): View
    {
        return view('management.export.c-freight.index');
    }

    public function getList(Request $request)
    {
        $freights = CFreight::with(['exportOrder.buyer', 'exportOrder.portOfDischarge', 'rates'])
            ->when($request->filled('search'), function ($q) use ($request) {
                $searchTerm = '%' . $request->search . '%';
                $q->whereHas('exportOrder', function ($sq) use ($searchTerm) {
                    $sq->where('voucher_no', 'like', $searchTerm)
                        ->orWhere('contract_no', 'like', $searchTerm);
                })->orWhere('booking_no', 'like', $searchTerm);
            })
            ->latest()
            ->paginate(request('per_page', 25));

        return view('management.export.c-freight.getList', compact('freights'));
    }

    public function create(): View
    {
        $exportOrders = ExportOrder::where('am_approval_status', 'approved')
            ->whereNotIn('id', function($q) {
                $q->select('export_order_id')->from('export_order_addendums');
            })->latest()->get();
        return view('management.export.c-freight.create', compact('exportOrders'));
    }

    public function getExportOrderDetails($id)
    {
        $exportOrder = ExportOrder::with(['buyer', 'product', 'portOfDischarge', 'portOfLoading', 'packingItems'])->findOrFail($id);
        
        $data = [
            'buyer' => $exportOrder->buyer->name ?? '',
            'contract_no' => $exportOrder->contract_no ?? '',
            'no_of_containers' => $exportOrder->packingItems->sum('no_of_containers'),
            'commodity' => $exportOrder->product->name ?? '',
            'port' => $exportOrder->portOfDischarge->name ?? '',
            'shipment_period' => ($exportOrder->shipment_delivery_date_from ? date('d-M-y', strtotime($exportOrder->shipment_delivery_date_from)) : '') . ' TO ' . ($exportOrder->shipment_delivery_date_to ? date('d-M-y', strtotime($exportOrder->shipment_delivery_date_to)) : ''),
        ];

        return response()->json($data);
    }

    public function store(Request $request)
    {
        $request->validate([
            'export_order_id' => 'required',
            'requested_containers' => 'required|numeric',
            'free_days' => 'required',
            'etr' => 'required',
        ]);

        try {
            DB::beginTransaction();

            CFreight::create([
                'export_order_id' => $request->export_order_id,
                'requested_containers' => $request->requested_containers,
                'free_days' => $request->free_days,
                'etr' => $request->etr,
                'status' => 'Pending Rates'
            ]);

            DB::commit();
            return redirect()->route('c-freight.index')->with('success', 'C Freight Request created successfully.');
        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function edit($id): View
    {
        $cFreight = CFreight::with(['exportOrder', 'rates'])->findOrFail($id);
        $shipmentCompanies = \App\Models\Export\ShipmentCompany::latest()->get();
        return view('management.export.c-freight.edit', compact('cFreight', 'shipmentCompanies'));
    }

    public function update(Request $request, $id)
    {
        $cFreight = CFreight::findOrFail($id);

        if ($request->has('update_type') && $request->update_type == 'request') {
            $request->validate([
                'export_order_id' => 'required',
                'requested_containers' => 'required|numeric',
                'free_days' => 'required',
                'etr' => 'required',
            ]);
            try {
                $cFreight->update([
                    'export_order_id' => $request->export_order_id,
                    'requested_containers' => $request->requested_containers,
                    'free_days' => $request->free_days,
                    'etr' => $request->etr,
                ]);
                return redirect()->route('c-freight.index')->with('success', 'C Freight Request updated successfully.');
            } catch (\Exception $e) {
                return redirect()->back()->with('error', $e->getMessage());
            }
        }

        $request->validate([
            'booking_no' => 'required|string',
            'quantity' => 'required|string',
            'shipping_line' => 'required|string',
            't_s' => 'required|string',
            'vessel_name' => 'required|string',
            'through_logistic' => 'required|string',
            'return_port' => 'required|string',
            'cutoff_si' => 'required|date',
            'cutoff_cy' => 'required|date',
            'etd' => 'required|date',
            'eta' => 'required|date',
        ]);

        try {
            DB::beginTransaction();

            $cFreight->update([
                'booking_no' => $request->booking_no,
                'bl_number' => $request->bl_number ?: '-',
                'quantity' => $request->quantity,
                'shipping_line' => $request->shipping_line,
                't_s' => $request->t_s,
                'vessel_name' => $request->vessel_name,
                'cutoff_si' => $request->cutoff_si,
                'cutoff_cy' => $request->cutoff_cy,
                'etd' => $request->etd,
                'eta' => $request->eta,
                'through_logistic' => $request->through_logistic,
                'return_port' => $request->return_port,
                'status' => 'Booked'
            ]);

            DB::commit();
            return redirect()->route('c-freight.index')->with('success', 'Booking Details updated successfully.');
        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function editRequest($id)
    {
        $cFreight = CFreight::findOrFail($id);
        $exportOrders = ExportOrder::latest()->get();
        return view('management.export.c-freight.edit-request', compact('cFreight', 'exportOrders'));
    }

    public function showBooking($id)
    {
        $cFreight = CFreight::with('exportOrder.portOfDischarge')->findOrFail($id);
        return view('management.export.c-freight.show-booking', compact('cFreight'));
    }

    public function show($id)
    {
        $cFreight = CFreight::findOrFail($id);
        $exportOrders = ExportOrder::latest()->get();
        return view('management.export.c-freight.edit-request', compact('cFreight', 'exportOrders'));
    }

    public function deleteRate($id)
    {
        try {
            $rate = CFreightRate::findOrFail($id);
            $rate->delete();
            return response()->json(['success' => true, 'message' => 'Rate removed successfully']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function addRate(Request $request, $id)
    {
        $request->validate([
            'rates' => 'required|array',
            'rates.*.shipping_line' => 'required',
            'rates.*.container_size' => 'required',
            'rates.*.port' => 'required',
            'rates.*.price' => 'required'
        ]);

        try {
            DB::beginTransaction();
            foreach($request->rates as $rate) {
                CFreightRate::create([
                    'c_freight_id' => $id,
                    'third_party' => $rate['third_party'] ?? null,
                    'shipping_line' => $rate['shipping_line'],
                    'container_size' => $rate['container_size'],
                    'port' => $rate['port'],
                    'price' => $rate['price'],
                ]);
            }
            DB::commit();
            return response()->json(['success' => true, 'message' => 'Rates added successfully']);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function approveRate(Request $request, $id)
    {
        $request->validate([
            'rate_id' => 'required'
        ]);

        try {
            DB::beginTransaction();
            
            // Mark all rates as not approved
            CFreightRate::where('c_freight_id', $id)->update(['is_approved' => false]);
            
            // Mark selected rate as approved
            $rate = CFreightRate::findOrFail($request->rate_id);
            $rate->update(['is_approved' => true]);

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Rate approved successfully']);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}
