<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Sales\Logistics;
use App\Models\Sales\LogisticsItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LogisticsController extends Controller
{
    public function index()
    {
        $logistics = LogisticsItem::with('logistics')
            ->orderBy('id', 'desc')
            ->get();
            
        return view('management.sales.logistics.index', compact('logistics'));
    }

    public function create()
    {
        // Fetch Sale Orders where transporter_used is 'yes' and they are approved
        $saleOrders = \App\Models\Sales\SalesOrder::with('logistics')
            ->where('transporter_used', 'yes')
            ->where('am_approval_status', 'approved')
            ->get()
            ->filter(function ($so) {
                // If any associated logistic record is approved, skip this Sale Order
                // Using collection methods on eager-loaded relationship to avoid extra queries
                $hasApprovedLogistic = $so->logistics
                    ->where('am_approval_status', 'approved')
                    ->isNotEmpty();
                
                return !$hasApprovedLogistic;
            });

        $transporters = \App\Models\Master\Transporter::where('status', 'active')->get();

        return view('management.sales.logistics.create', compact('saleOrders', 'transporters'));
    }

    public function getOrderDetails($id)
    {
        $order = \App\Models\Sales\SalesOrder::with([
            'customer', 
            'sales_order_data.item', 
            'factories.factory', 
            'sections.section',
            'locations.companyLocation'
        ])->find($id);
        
        if (!$order) {
            return response()->json(['error' => 'Order not found'], 404);
        }

        // Aggregate data from sales_order_data
        $totalQty = $order->sales_order_data->sum('qty');
        $commodities = $order->sales_order_data->map(function($item) {
            return $item->item->name ?? 'N/A';
        })->unique()->implode(', ');

        // Get Location, Factory, Section safer
        $location = $order->locations->first();
        $locationName = $location && $location->companyLocation ? $location->companyLocation->name : 'N/A';
        
        $factory = $order->factories->first();
        $factoryName = $factory && $factory->factory ? $factory->factory->name : 'N/A';
        
        $section = $order->sections->first();
        $sectionName = $section && $section->section ? $section->section->name : 'N/A';

        $logistics = Logistics::with('items')->where('sale_order_id', $id)->first();

        return response()->json([
            'date' => $order->order_date ?? date('Y-m-d'),
            'so_no' => $order->reference_no,
            'so_qty' => $totalQty,
            'commodity' => $commodities,
            'sauda_type' => $order->sauda_type,
            'customer' => $order->customer->name ?? 'N/A',
            'delivery_address' => 'Static Delivery Address 123', 
            'location' => $locationName,
            'factory' => $factoryName,
            'section' => $sectionName,
            'logistics' => $logistics
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'type' => 'required|string',
            'sale_order_id' => 'required|exists:sales_orders,id',
            'items' => 'required|array|min:1',
            'items.*.rate_type' => 'required|string',
            'items.*.rate' => 'required|numeric',
            'items.*.transporter' => 'required|string',
            'items.*.qty' => 'required|numeric',
        ]);

        DB::beginTransaction();
        try {
            $logistics = Logistics::firstOrNew(['sale_order_id' => $request->sale_order_id]);
            
            if (!$logistics->exists) {
                $logistics->created_by = auth()->user()->id;
            }
            $logistics->am_approval_status = 'pending';
            $logistics->am_change_made = 1;
            
            $logistics->fill([
                'date' => $request->date,
                'type' => $request->type,
                'loading_request' => $request->loading_request,
                'so_no' => $request->so_no,
                'so_qty' => $request->so_qty,
                'commodity' => $request->commodity,
                'sauda_type' => $request->sauda_type,
                'delivery_address' => $request->delivery_address,
                'location' => $request->location,
                'factory' => $request->factory,
                'section' => $request->section,
                'customer' => $request->customer,
            ]);
            $logistics->save();

            // Clear existing items and replace with new ones
            $logistics->items()->delete();

            foreach ($request->items as $item) {
                $transporterRaw = $item['transporter'];
                $transporterId = null;
                $transporterName = $transporterRaw;

                // Check if the submitted transporter is an existing ID
                if (is_numeric($transporterRaw)) {
                    $existingTransporter = \App\Models\Master\Transporter::find($transporterRaw);
                    if ($existingTransporter) {
                        $transporterId = $existingTransporter->id;
                        $transporterName = $existingTransporter->company_name;
                    }
                }

                LogisticsItem::create([
                    'logistics_id' => $logistics->id,
                    'rate_type' => $item['rate_type'],
                    'rate' => $item['rate'],
                    'transporter_id' => $transporterId,
                    'transporter_name' => $transporterName,
                    'qty' => $item['qty'],
                ]);
            }

            DB::commit();
            return response()->json(['success' => 'Logistics record processed successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function getList(Request $request)
    {
        $search = $request->search;
        
        $logistics = Logistics::with('items')
            ->when($search, function($query) use ($search) {
                $query->where('so_no', 'like', "%$search%")
                    ->orWhere('customer', 'like', "%$search%")
                    ->orWhere('commodity', 'like', "%$search%")
                    ->orWhere('type', 'like', "%$search%")
                    ->orWhereHas('items', function($q) use ($search) {
                        $q->where('transporter_name', 'like', "%$search%");
                    });
            })
            ->orderBy('id', 'desc')
            ->get();
            
        return view('management.sales.logistics.getList', compact('logistics'));
    }

    public function show($id)
    {
        $logistics = Logistics::with('items')->find($id);
        if (!$logistics) {
            return response()->json(['error' => 'Logistics not found'], 404);
        }
        return view('management.sales.logistics.view', compact('logistics'));
    }
}
