<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Sales\Logistics;
use App\Models\Sales\LogisticsItem;
use App\Models\Export\ExportOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Master\CompanyLocation;
use App\Models\Master\Port;
use Illuminate\Validation\ValidationException;

class LogisticsController extends Controller
{
    public function index()
    {
        $logistics = Logistics::with(['items.transporter'])
            ->orderBy('id', 'desc')
            ->get();
            
        return view('management.sales.logistics.index', compact('logistics'));
    }

    public function create()
    {
        $saleOrders = \App\Models\Sales\SalesOrder::with('logistics')
            ->where('transporter_used', 'yes')
            ->where('am_approval_status', 'approved')
            ->orderBy('id', 'desc')
            ->get()
            ->filter(function ($so) {
                // If any associated logistic record is approved, skip this Sale Order
                // Using collection methods on eager-loaded relationship to avoid extra queries
                $hasApprovedLogistic = $so->logistics
                    ->where('am_approval_status', 'approved')
                    ->isNotEmpty();
                
                return !$hasApprovedLogistic;
            });

        $exportOrders = ExportOrder::with('logistics')
            ->where('am_approval_status', 'approved')
            ->whereNotIn('id', function($q) {
                $q->select('export_order_id')->from('export_order_addendums');
            })
            ->orderBy('id', 'desc')
            ->get()
            ->filter(function ($eo) {
                $hasApprovedLogistic = $eo->logistics
                    ->where('am_approval_status', 'approved')
                    ->isNotEmpty();

                return !$hasApprovedLogistic;
            });
        
        $companyLocations = CompanyLocation::where('status', 'active')->get();
        $arrivalLocations = \App\Models\Master\ArrivalLocation::where('status', 'active')->get();

        return view('management.sales.logistics.create', compact('saleOrders', 'exportOrders', 'companyLocations', 'arrivalLocations'));
    }

    public function getOrderDetails(Request $request, $id)
    {
        $type = $request->get('type', 'sale_order');

        if ($type === 'export_order') {
            return $this->getExportOrderDetails($id);
        }

        return $this->getSaleOrderDetails($id);
    }

    protected function getSaleOrderDetails($id)
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

        $totalQty = $order->sales_order_data->sum('qty');
        $commodities = $order->sales_order_data->map(function($item) {
            return $item->item->name ?? 'N/A';
        })->unique()->implode(', ');

        $fromLocationOptions = $order->locations
            ->pluck('companyLocation')
            ->filter()
            ->map(fn ($location) => ['id' => $location->id, 'name' => $location->name])
            ->unique('id')
            ->values()
            ->toArray();
        $fromLocation = !empty($fromLocationOptions) ? $fromLocationOptions[0] : null;

        $toLocationOptions = CompanyLocation::where('status', 'active')
            ->get(['id', 'name'])
            ->map(fn ($location) => ['id' => $location->id, 'name' => $location->name])
            ->values()
            ->toArray();

        $logistics = Logistics::with(['items.transporter'])
            ->where('type', 'sale_order')
            ->where('sale_order_id', $id)
            ->first();

        return response()->json([
            'type' => 'sale_order',
            'date' => $order->order_date ?? date('Y-m-d'),
            'so_no' => $order->reference_no,
            'so_qty' => $totalQty,
            'commodity' => $commodities,
            'sauda_type' => $order->sauda_type,
            'customer' => $order->customer->name ?? 'N/A',
            'delivery_address' => 'Static Delivery Address 123', 
            'from_location_id' => $fromLocation['id'] ?? '',
            'from_location_options' => $fromLocationOptions,
            'to_location_id' => '',
            'to_location_options' => $toLocationOptions,
            'logistics' => $logistics
        ]);
    }

    protected function getExportOrderDetails($id)
    {
        $order = ExportOrder::with([
            'buyer',
            'product',
            'incoterm',
            'packingItems.brand',
            'packingItems.bagPacking',
            'portOfLoading'
        ])->find($id);

        if (!$order) {
            return response()->json(['error' => 'Order not found'], 404);
        }

        $companyLocationIds = collect($order->company_location_ids ?? [])->filter()->map(fn($id) => (int) $id)->values();
        $locationName = CompanyLocation::whereIn('id', $companyLocationIds)->pluck('name')->implode(', ');
        $fromLocationOptions = CompanyLocation::where('status', 'active')
            ->get(['id', 'name'])
            ->map(fn ($location) => ['id' => $location->id, 'name' => $location->name])
            ->values()
            ->toArray();

        $toLocationOptions = $order->portOfLoading
            ? [['id' => $order->portOfLoading->id, 'name' => $order->portOfLoading->name]]
            : [];

        $logistics = Logistics::with(['items.transporter'])
            ->where('type', 'export_order')
            ->where('export_order_id', $id)
            ->first();

        $cFreight = \App\Models\Export\CFreight::with('rates')->where('export_order_id', $id)->first();
        $approvedRate = $cFreight ? $cFreight->rates->where('is_approved', 1)->first() : null;
        $shippingLine = $approvedRate ? $approvedRate->shipping_line : ($cFreight->shipping_line ?? '');
        
        $jobOrder = \App\Models\Production\JobOrder\JobOrder::where('export_order_id', $id)->first();

        $brands = $order->packingItems->map(fn($item) => $item->brand->name ?? null)->filter()->unique()->values()->toArray();
        $packingSizes = $order->packingItems->map(fn($item) => $item->bagPacking->name ?? null)->filter()->unique()->values()->toArray();

        return response()->json([
            'type' => 'export_order',
            'date' => optional($order->voucher_date)->format('Y-m-d') ?? date('Y-m-d'),
            'so_no' => $order->voucher_no,
            'so_qty' => (float) $order->packingItems->sum('metric_tons'),
            'commodity' => $order->product->name ?? 'N/A',
            'sauda_type' => $order->incoterm?->name ?? 'N/A',
            'customer' => $order->buyer->name ?? 'N/A',
            'delivery_address' => 'Static Delivery Address 123',
            'location' => $locationName ?: 'N/A',
            'from_location_id' => $companyLocationIds->first() ?: '',
            'from_location_options' => $fromLocationOptions,
            'to_location_id' => $order->port_of_loading_id ?: '',
            'to_location_options' => $toLocationOptions,
            'logistics' => $logistics,
            'job_order' => $jobOrder->job_order_no ?? '',
            'return_port' => $cFreight->return_port ?? '',
            'booking_no' => $cFreight->booking_no ?? '',
            'shipping_line' => $shippingLine,
            'brands' => $brands,
            'packing_sizes' => $packingSizes,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'type' => 'required|in:sale_order,export_order',
            'sale_order_id' => 'nullable|required_if:type,sale_order|exists:sales_orders,id',
            'export_order_id' => 'nullable|required_if:type,export_order|exists:export_orders,id',
            'location' => 'required',
            'items' => 'required|array|min:1',
            'items.*.rate_type' => 'required|string',
            'items.*.rate' => 'required|numeric',
            'items.*.transporter' => 'required|string',
            'items.*.qty' => 'required|numeric',
            'items.*.brand' => 'nullable|string',
            'items.*.packing_size' => 'nullable|string',
            'to_location' => 'required',
            'job_order' => 'nullable|string',
            'return_port' => 'nullable|string',
            'booking_no' => 'nullable|string',
            'shipping_line' => 'nullable|string',
            'factory' => 'nullable|string',
        ]);

        if ($request->type === 'export_order') {
            if (!CompanyLocation::whereKey($request->location)->exists()) {
                throw ValidationException::withMessages(['location' => 'Selected from location is invalid.']);
            }

            if (!Port::whereKey($request->to_location)->exists()) {
                throw ValidationException::withMessages(['to_location' => 'Selected port of loading is invalid.']);
            }
        } else {
            if (!CompanyLocation::whereKey($request->location)->exists()) {
                throw ValidationException::withMessages(['location' => 'Selected from location is invalid.']);
            }

            if (!CompanyLocation::whereKey($request->to_location)->exists()) {
                throw ValidationException::withMessages(['to_location' => 'Selected to location is invalid.']);
            }
        }

        DB::beginTransaction();
        try {
            $lookup = $request->type === 'export_order'
                ? ['type' => 'export_order', 'export_order_id' => $request->export_order_id]
                : ['type' => 'sale_order', 'sale_order_id' => $request->sale_order_id];

            $logistics = Logistics::firstOrNew($lookup);
            
            if (!$logistics->exists) {
                $logistics->created_by = auth()->user()->id;
            }

            $logistics->sale_order_id = $request->type === 'sale_order' ? $request->sale_order_id : null;
            $logistics->export_order_id = $request->type === 'export_order' ? $request->export_order_id : null;
            $logistics->to_location = $request->to_location;
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
                'customer' => $request->customer,
                'job_order' => $request->job_order,
                'return_port' => $request->return_port,
                'booking_no' => $request->booking_no,
                'shipping_line' => $request->shipping_line,
                'factory' => $request->factory,
            ]);
            $logistics->save();

            // Clear existing items and replace with new ones
            $logistics->items()->delete();

            foreach ($request->items as $item) {
                $transporterRaw = $item['transporter'];
                $transporterId = null;
                $transporterName = $transporterRaw;

                if (is_numeric($transporterRaw)) {
                    $existingTransporter = \App\Models\Master\Transporter::find($transporterRaw);
                    if ($existingTransporter) {
                        $transporterId = $existingTransporter->id;
                        $transporterName = $existingTransporter->company_name ?: $existingTransporter->name;
                    }
                }

                LogisticsItem::create([
                    'logistics_id' => $logistics->id,
                    'rate_type' => $item['rate_type'],
                    'rate' => $item['rate'],
                    'transporter_id' => $transporterId,
                    'transporter_name' => $transporterName,
                    'qty' => $item['qty'],
                    'brand' => $item['brand'] ?? null,
                    'packing_size' => $item['packing_size'] ?? null,
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
        
        $logistics = Logistics::with(['items.transporter'])
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
        $logistics = Logistics::with(['items.transporter'])->find($id);
        if (!$logistics) {
            return response()->json(['error' => 'Logistics not found'], 404);
        }
        return view('management.sales.logistics.view', compact('logistics'));
    }
}
