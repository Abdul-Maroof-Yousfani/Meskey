<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Master\Customer;
use App\Models\Sales\Logistics;
use App\Models\Sales\LogisticsItem;
use App\Models\Export\ExportOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\AuditLog;
use App\Models\Master\CompanyLocation;
use App\Models\Master\Port;
use App\Models\User;
use App\Services\AuditLogService;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class LogisticsController extends Controller
{
    public function index()
    {
        abort_if(!canAccess('logistics-list') && !auth()->user()->can('logistics-list'), 403);
        $logistics = Logistics::with(['items.transporter'])
            ->orderBy('id', 'desc')
            ->get();

        return view('management.sales.logistics.index', compact('logistics'));
    }

    public function create()
    {
        abort_if(!canAccess('logistics-create') && !auth()->user()->can('logistics-create'), 403);
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

        $customers = Customer::where("type", "local")->get();

        return view('management.sales.logistics.create', compact('exportOrders', 'companyLocations', 'arrivalLocations', 'customers'));
    }

    public function getSo(Request $request)
    {
        abort_if(!canAccess('logistics-create') && !auth()->user()->can('logistics-create'), 403);
        $customer_id = $request->customer_id;

        $saleOrders = \App\Models\Sales\SalesOrder::activeContract()
            ->with('logistics')
            ->whereNot(function($q) {
                $q->where('contract_status', 'x-mill')
                  ->where('transporter_used', 'no');
            })
            ->where('am_approval_status', 'approved')
            ->where('customer_id', $customer_id)
            ->orderBy('id', 'desc')
            ->get()
            ->filter(function ($so) {
                if ($so->isClosed()) {
                    return false;
                }
                $hasApprovedLogistic = $so->logistics
                    ->where('am_approval_status', 'approved')
                    ->isNotEmpty();
                
                return !$hasApprovedLogistic;
            });

        $data = [];
        foreach ($saleOrders as $saleOrder) {
            $data[] = [
                'text' => $saleOrder->reference_no,
                'id' => $saleOrder->id,
            ];
        }

        return [
            "rawData" => $saleOrders,
            "processedData" => $data
        ];
    }

    public function getOrderDetails(Request $request, $id)
    {
        abort_if(!canAccess('logistics-create') && !auth()->user()->can('logistics-create'), 403);
        $type = $request->get('type', 'sale_order');
        $isEdit = $request->get('is_edit', 0);

        if ($type === 'export_order') {
            return $this->getExportOrderDetails($id, $isEdit);
        }

        return $this->getSaleOrderDetails($id, $isEdit);
    }

    protected function getSaleOrderDetails($id, $isEdit = 0)
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

        if ($order->isClosed()) {
            return response()->json([
                'error' => 'The Sale Order has been closed. Logistics operations are locked.',
                'message' => 'The Sale Order has been closed. Logistics operations are locked.'
            ], 422);
        }

        $totalQty = $order->sales_order_data->sum('qty');
        $commodities = $order->sales_order_data->map(function ($item) {
            return $item->item->name ?? 'N/A';
        })->unique()->implode(', ');

        $fromLocationOptions = $order->locations
            ->pluck('companyLocation')
            ->filter()
            ->map(fn($location) => ['id' => $location->id, 'name' => $location->name])
            ->unique('id')
            ->values()
            ->toArray();
        $fromLocation = !empty($fromLocationOptions) ? $fromLocationOptions[0] : null;

        $toLocationOptions = CompanyLocation::where('status', 'active')
            ->get(['id', 'name'])
            ->map(fn($location) => ['id' => $location->id, 'name' => $location->name])
            ->values()
            ->toArray();

        $logistics = null;
        if ($isEdit) {
            $logistics = Logistics::with(['items.transporter'])
                ->where('type', 'sale_order')
                ->where('sale_order_id', $id)
                ->first();
        }

        $defaultFactoryNames = $order->factories
            ->map(fn($f) => $f->factory?->name)
            ->filter()
            ->unique()
            ->values()
            ->toArray();

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
            'default_factories' => $defaultFactoryNames,
            'logistics' => $logistics
        ]);
    }

    protected function getExportOrderDetails($id, $isEdit = 0)
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
            ->map(fn($location) => ['id' => $location->id, 'name' => $location->name])
            ->values()
            ->toArray();

        $toLocationOptions = $order->portOfLoading
            ? [['id' => $order->portOfLoading->id, 'name' => $order->portOfLoading->name]]
            : [];

        $logistics = null;
        if ($isEdit) {
            $logistics = Logistics::with(['items.transporter'])
                ->where('type', 'export_order')
                ->where('export_order_id', $id)
                ->first();
        }

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
            'to_location_name' => $order->portOfLoading?->name ?? '',
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
        abort_if(!canAccess('logistics-create') && !auth()->user()->can('logistics-create'), 403);
        $request->validate([
            'date' => 'required|date',
            'type' => 'required|in:sale_order,export_order',
            'sale_order_id' => 'nullable|required_if:type,sale_order|exists:sales_orders,id',
            'export_order_id' => 'nullable|required_if:type,export_order|exists:export_orders,id',
            // 'location' => 'required',
            'items' => 'required|array|min:1',
            'items.*.rate_type' => 'required|string',
            'items.*.rate' => 'required|numeric',
            'items.*.transporter' => 'required|string',
            'items.*.qty' => 'required|numeric',
            'items.*.brand' => 'nullable|string',
            'items.*.packing_size' => 'nullable|string',
            'to_location' => 'required|string|max:255',
            'job_order' => 'nullable|string',
            'return_port' => 'nullable|string',
            'booking_no' => 'nullable|string',
            'shipping_line' => 'nullable|string',
            'factory' => 'nullable',
            'factory.*' => 'nullable|string',
        ]);

        if ($request->type === 'export_order') {
            if (!CompanyLocation::whereKey($request->location)->exists()) {
                throw ValidationException::withMessages(['location' => 'Selected from location is invalid.']);
            }

            if (!Port::whereKey($request->to_location)->exists()) {
                throw ValidationException::withMessages(['to_location' => 'Selected port of loading is invalid.']);
            }
        }

        DB::beginTransaction();
        try {
            $lookup = $request->type === 'export_order'
                ? ['type' => 'export_order', 'export_order_id' => $request->export_order_id]
                : ['type' => 'sale_order', 'sale_order_id' => $request->sale_order_id];

            if ($request->type === 'sale_order' && $request->sale_order_id) {
                $so = \App\Models\Sales\SalesOrder::find($request->sale_order_id);
                if ($so && $so->isClosed()) {
                    return response()->json([
                        'error' => 'The Sale Order for this Logistics record has been closed. Operations are locked.',
                        'message' => 'The Sale Order for this Logistics record has been closed. Operations are locked.'
                    ], 422);
                }
            }

            $logistics = Logistics::with(['items.transporter', 'saleOrder', 'exportOrder'])->firstOrNew($lookup);

            if ($logistics->exists && in_array(strtolower($logistics->am_approval_status ?? ''), ['approved', 'rejected'])) {
                return response()->json([
                    'error' => "Logistics record has already been {$logistics->am_approval_status} and cannot be modified.",
                    'message' => "Logistics record has already been {$logistics->am_approval_status} and cannot be modified."
                ], 422);
            }

            $isNew = !$logistics->exists;
            $oldAuditValues = $logistics->exists ? $this->buildLogisticsAuditValues($logistics) : [];

            if (!$logistics->exists) {
                $logistics->created_by = auth()->user()->id;
            }

            $logistics->sale_order_id = $request->type === 'sale_order' ? $request->sale_order_id : null;
            $logistics->export_order_id = $request->type === 'export_order' ? $request->export_order_id : null;
            $logistics->to_location = $request->to_location;
            $logistics->am_approval_status = 'pending';
            $logistics->am_change_made = 1;

            $fromLocation = $request->location ?: $request->hidden_location;
            if (empty($fromLocation) && $request->type === 'sale_order' && $request->sale_order_id) {
                $so = \App\Models\Sales\SalesOrder::with('locations')->find($request->sale_order_id);
                $fromLocation = $so?->locations?->first()?->location_id;
            } elseif (empty($fromLocation) && $request->type === 'export_order' && $request->export_order_id) {
                $eo = ExportOrder::find($request->export_order_id);
                $fromLocation = collect($eo?->company_location_ids ?? [])->first();
            }

            $factoryValue = null;
            if ($request->has('factory')) {
                if (is_array($request->factory)) {
                    $factoryValue = implode(', ', array_filter($request->factory));
                } else {
                    $factoryValue = $request->factory;
                }
            }

            $logistics->fill([
                'date' => $request->date,
                'type' => $request->type,
                'loading_request' => $request->loading_request,
                'so_no' => $request->so_no,
                'so_qty' => $request->so_qty,
                'commodity' => $request->commodity,
                'sauda_type' => $request->sauda_type,
                'delivery_address' => $request->delivery_address,
                'location' => $fromLocation,
                'customer' => $request->customer,
                'job_order' => $request->job_order,
                'return_port' => $request->return_port,
                'booking_no' => $request->booking_no,
                'shipping_line' => $request->shipping_line,
                'factory' => $factoryValue,
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

            $logistics->refresh();
            $logistics->load(['items.transporter', 'saleOrder', 'exportOrder']);
            $newAuditValues = $this->buildLogisticsAuditValues($logistics);

            if ($isNew) {
                AuditLogService::log(
                    $logistics,
                    'logistics_created',
                    "Created Logistics record for #{$logistics->so_no}.",
                    null,
                    $newAuditValues
                );
            } else {
                $changedOld = [];
                $changedNew = [];
                foreach ($newAuditValues as $k => $v) {
                    $oldVal = $oldAuditValues[$k] ?? null;
                    if ($oldVal != $v) {
                        $changedOld[$k] = $oldVal;
                        $changedNew[$k] = $v;
                    }
                }

                if (!empty($changedNew)) {
                    AuditLogService::log(
                        $logistics,
                        'logistics_updated',
                        "Logistics record for #{$logistics->so_no} updated.",
                        $changedOld,
                        $changedNew
                    );
                }
            }

            DB::commit();
            return response()->json(['success' => 'Logistics record processed successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function edit($id)
    {
        abort_if(!canAccess('logistics-create') && !auth()->user()->can('logistics-create'), 403);
        $logistics = Logistics::with('saleOrder')->find($id);
        if (!$logistics) {
            return response()->json(['error' => 'Logistics not found'], 404);
        }

        $exportOrders = ExportOrder::with('logistics')
            ->where('am_approval_status', 'approved')
            ->whereNotIn('id', function($q) {
                $q->select('export_order_id')->from('export_order_addendums');
            })
            ->orderBy('id', 'desc')
            ->get()
            ->filter(function ($eo) use ($logistics) {
                if ($logistics->type === 'export_order' && $logistics->export_order_id == $eo->id) {
                    return true;
                }
                $hasApprovedLogistic = $eo->logistics
                    ->where('am_approval_status', 'approved')
                    ->isNotEmpty();

                return !$hasApprovedLogistic;
            });

        $companyLocations = CompanyLocation::where('status', 'active')->get();
        $arrivalLocations = \App\Models\Master\ArrivalLocation::where('status', 'active')->get();

        $customers = Customer::where("type", "local")->get();

        return view('management.sales.logistics.edit', compact('logistics', 'exportOrders', 'companyLocations', 'arrivalLocations', 'customers'));
    }

    public function update(Request $request, $id)
    {
        abort_if(!canAccess('logistics-create') && !auth()->user()->can('logistics-create'), 403);
        $logistics = Logistics::with(['items.transporter', 'saleOrder', 'exportOrder'])->find($id);
        if (!$logistics) {
            return response()->json(['error' => 'Logistics not found', 'message' => 'Logistics not found'], 404);
        }

        if (in_array(strtolower($logistics->am_approval_status ?? ''), ['approved', 'rejected'])) {
            return response()->json([
                'error' => "Logistics record has been {$logistics->am_approval_status} and cannot be updated.",
                'message' => "Logistics record has been {$logistics->am_approval_status} and cannot be updated."
            ], 422);
        }

        $oldAuditValues = $this->buildLogisticsAuditValues($logistics);
        
        $request->validate([
            'date' => 'required|date',
            'type' => 'required|in:sale_order,export_order',
            'sale_order_id' => 'nullable|required_if:type,sale_order|exists:sales_orders,id',
            'export_order_id' => 'nullable|required_if:type,export_order|exists:export_orders,id',
            'items' => 'required|array|min:1',
            'items.*.rate_type' => 'required|string',
            'items.*.rate' => 'required|numeric',
            'items.*.transporter' => 'required|string',
            'items.*.qty' => 'required|numeric',
            'to_location' => 'required|string|max:255',
            'job_order' => 'nullable|string',
            'return_port' => 'nullable|string',
            'booking_no' => 'nullable|string',
            'shipping_line' => 'nullable|string',
            'factory' => 'nullable',
            'factory.*' => 'nullable|string',
        ]);

        if ($request->type === 'export_order') {
            if (!CompanyLocation::whereKey($request->location)->exists()) {
                throw ValidationException::withMessages(['location' => 'Selected from location is invalid.']);
            }
            if (!Port::whereKey($request->to_location)->exists()) {
                throw ValidationException::withMessages(['to_location' => 'Selected port of loading is invalid.']);
            }
        }

        DB::beginTransaction();
        try {
            if ($request->type === 'sale_order' && $request->sale_order_id) {
                $so = \App\Models\Sales\SalesOrder::find($request->sale_order_id);
                if ($so && $so->isClosed()) {
                    return response()->json([
                        'error' => 'The Sale Order for this Logistics record has been closed. Operations are locked.',
                        'message' => 'The Sale Order for this Logistics record has been closed. Operations are locked.'
                    ], 422);
                }
            }

            $logistics->sale_order_id = $request->type === 'sale_order' ? $request->sale_order_id : null;
            $logistics->export_order_id = $request->type === 'export_order' ? $request->export_order_id : null;
            $logistics->to_location = $request->to_location;
            $logistics->am_approval_status = 'pending';
            $logistics->am_change_made = 1;

            $fromLocation = $request->location ?: $request->hidden_location;
            if (empty($fromLocation) && $request->type === 'sale_order' && $request->sale_order_id) {
                $so = \App\Models\Sales\SalesOrder::with('locations')->find($request->sale_order_id);
                $fromLocation = $so?->locations?->first()?->location_id;
            } elseif (empty($fromLocation) && $request->type === 'export_order' && $request->export_order_id) {
                $eo = ExportOrder::find($request->export_order_id);
                $fromLocation = collect($eo?->company_location_ids ?? [])->first();
            }

            $factoryValue = null;
            if ($request->has('factory')) {
                if (is_array($request->factory)) {
                    $factoryValue = implode(', ', array_filter($request->factory));
                } else {
                    $factoryValue = $request->factory;
                }
            }

            $logistics->fill([
                'date' => $request->date,
                'type' => $request->type,
                'loading_request' => $request->loading_request,
                'so_no' => $request->so_no,
                'so_qty' => $request->so_qty,
                'commodity' => $request->commodity,
                'sauda_type' => $request->sauda_type,
                'delivery_address' => $request->delivery_address,
                'location' => $fromLocation,
                'customer' => $request->customer,
                'job_order' => $request->job_order,
                'return_port' => $request->return_port,
                'booking_no' => $request->booking_no,
                'shipping_line' => $request->shipping_line,
                'factory' => $factoryValue,
            ]);
            $logistics->save();

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

            $logistics->refresh();
            $logistics->load(['items.transporter', 'saleOrder', 'exportOrder']);
            $newAuditValues = $this->buildLogisticsAuditValues($logistics);

            $changedOld = [];
            $changedNew = [];
            foreach ($newAuditValues as $k => $v) {
                $oldVal = $oldAuditValues[$k] ?? null;
                if ($oldVal != $v) {
                    $changedOld[$k] = $oldVal;
                    $changedNew[$k] = $v;
                }
            }

            if (!empty($changedNew)) {
                AuditLogService::log(
                    $logistics,
                    'logistics_updated',
                    "Logistics record for #{$logistics->so_no} updated.",
                    $changedOld,
                    $changedNew
                );
            }

            DB::commit();
            return response()->json(['success' => 'Logistics record updated successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function getList(Request $request)
    {
        abort_if(!canAccess('logistics-list') && !auth()->user()->can('logistics-list'), 403);
        $search = $request->search;

        $logistics = Logistics::with(['items.transporter'])
            ->when($search, function ($query) use ($search) {
                $query->where('so_no', 'like', "%$search%")
                    ->orWhere('customer', 'like', "%$search%")
                    ->orWhere('commodity', 'like', "%$search%")
                    ->orWhere('type', 'like', "%$search%")
                    ->orWhereHas('items', function ($q) use ($search) {
                        $q->where('transporter_name', 'like', "%$search%");
                    });
            })
            ->orderBy('id', 'desc')
            ->get();

        return view('management.sales.logistics.getList', compact('logistics'));
    }

    public function show($id)
    {
        abort_if(!canAccess('logistics-list') && !auth()->user()->can('logistics-list'), 403);
        $logistics = Logistics::with(['items.transporter'])->find($id);
        if (!$logistics) {
            return response()->json(['error' => 'Logistics not found'], 404);
        }
        return view('management.sales.logistics.view', compact('logistics'));
    }

    public function destroy($id)
    {
        abort_if(!canAccess('logistics-create') && !auth()->user()->can('logistics-create'), 403);
        $logistics = Logistics::with(['items.transporter', 'saleOrder', 'exportOrder'])->find($id);
        if (!$logistics) {
            return response()->json(['error' => 'Logistics not found', 'message' => 'Logistics not found'], 404);
        }

        if (in_array(strtolower($logistics->am_approval_status ?? ''), ['approved', 'rejected'])) {
            return response()->json([
                'error' => "Logistics record has been {$logistics->am_approval_status} and cannot be deleted.",
                'message' => "Logistics record has been {$logistics->am_approval_status} and cannot be deleted."
            ], 422);
        }

        $oldValues = $this->buildLogisticsAuditValues($logistics);

        AuditLogService::log(
            $logistics,
            'logistics_deleted',
            "Logistics record for #{$logistics->so_no} deleted.",
            $oldValues,
            null
        );

        $logistics->items()->delete();
        $logistics->delete();

        return response()->json(['data' => 'Logistics record has been deleted', 'success' => 'Logistics record has been deleted']);
    }

    /**
     * Build audit values array for Logistics.
     */
    protected function buildLogisticsAuditValues(Logistics $logistics): array
    {
        $items = $logistics->items ?? collect();

        $fromLocationName = null;
        if (!empty($logistics->location)) {
            $fromLocationName = is_numeric($logistics->location)
                ? CompanyLocation::find($logistics->location)?->name
                : $logistics->location;
        }
        if (!$fromLocationName && ($logistics->type ?? 'sale_order') === 'sale_order' && $logistics->saleOrder) {
            $fromLocationName = $logistics->saleOrder->locations
                ->map(fn($l) => $l->companyLocation?->name)
                ->filter()
                ->implode(', ');
        } elseif (!$fromLocationName && ($logistics->type ?? 'sale_order') === 'export_order' && $logistics->exportOrder) {
            $fromLocationName = CompanyLocation::whereIn('id', $logistics->exportOrder->company_location_ids ?? [])->pluck('name')->implode(', ');
        }

        $toLocationName = null;
        if (!empty($logistics->to_location)) {
            if (($logistics->type ?? 'sale_order') === 'export_order') {
                $toLocationName = Port::find($logistics->to_location)?->name ?? CompanyLocation::find($logistics->to_location)?->name ?? $logistics->to_location;
            } else {
                $toLocationName = CompanyLocation::find($logistics->to_location)?->name ?? $logistics->to_location;
            }
        }

        $transporters = [];
        $rates = [];
        $rateTypes = [];
        $qtys = [];
        $brands = [];
        $packings = [];

        foreach ($items as $item) {
            $tName = $item->transporter_name ?: ($item->transporter?->company_name ?? $item->transporter?->name);
            if ($tName) {
                $transporters[] = $tName;
            }
            if ($item->rate !== null) {
                $rates[] = $item->rate;
            }
            if ($item->rate_type) {
                $rateTypes[] = $item->rate_type;
            }
            if ($item->qty !== null) {
                $qtys[] = $item->qty;
            }
            if ($item->brand) {
                $brands[] = $item->brand;
            }
            if ($item->packing_size) {
                $packings[] = $item->packing_size;
            }
        }

        $totalItemQty = $items->sum('qty');

        return [
            'type'               => str_replace('_', ' ', ucwords($logistics->type ?? 'sale_order', '_')),
            'date'               => $logistics->date ? Carbon::parse($logistics->date)->format('Y-m-d') : null,
            'so_no'              => $logistics->so_no,
            'loading_request'    => $logistics->loading_request,
            'so_qty'             => $logistics->so_qty,
            'commodity'          => $logistics->commodity,
            'sauda_type'         => $logistics->sauda_type,
            'customer'           => $logistics->customer,
            'location'           => $fromLocationName ?: $logistics->location,
            'to_location'        => $toLocationName ?: $logistics->to_location,
            'delivery_address'   => $logistics->delivery_address,
            'factory'            => $logistics->factory,
            'job_order'          => $logistics->job_order,
            'return_port'        => $logistics->return_port,
            'booking_no'         => $logistics->booking_no,
            'shipping_line'      => $logistics->shipping_line,
            'am_approval_status' => $logistics->am_approval_status,
            'items_transporter'  => !empty($transporters) ? implode(', ', $transporters) : null,
            'items_rate'         => !empty($rates) ? implode(', ', $rates) : null,
            'items_rate_type'    => !empty($rateTypes) ? implode(', ', $rateTypes) : null,
            'items_qty'          => !empty($qtys) ? implode(', ', $qtys) : null,
            'items_brand'        => !empty($brands) ? implode(', ', $brands) : null,
            'items_packing'      => !empty($packings) ? implode(', ', $packings) : null,
            'total_item_qty'     => $totalItemQty,
            'items_count'        => $items->count(),
        ];
    }

    /**
     * Get audit logs for a logistics record.
     */
    public function getAuditLogs(Request $request, $id)
    {
        abort_if(!canAccess('logistics-list') && !auth()->user()->can('logistics-list'), 403);

        $logistics = Logistics::with(['items.transporter', 'saleOrder.locations.companyLocation', 'exportOrder'])->findOrFail($id);

        $logs = AuditLog::with('user')
            ->where(function ($q) use ($logistics) {
                $q->where(function ($sq) use ($logistics) {
                    $sq->whereIn('model_type', [Logistics::class, 'Logistics', 'App\Models\Sales\Logistics'])
                        ->where('model_id', $logistics->id);
                })
                ->orWhere('old_values', 'like', '%"logistics_id":' . $logistics->id . '%')
                ->orWhere('old_values', 'like', '%"logistics_id":"' . $logistics->id . '"%')
                ->orWhere('new_values', 'like', '%"logistics_id":' . $logistics->id . '%')
                ->orWhere('new_values', 'like', '%"logistics_id":"' . $logistics->id . '"%');
            })
            ->orderBy('created_at', 'desc')
            ->get();

        $hasCreateLog = $logs->contains(function ($l) {
            return str_contains($l->action, 'create') || str_contains($l->action, 'store');
        });

        if (!$hasCreateLog) {
            $virtualValues = $this->buildLogisticsAuditValues($logistics);
            $virtualCreateLog = new AuditLog([
                'action' => 'logistics_created',
                'description' => "Logistics record for #{$logistics->so_no} created.",
                'user_id' => $logistics->created_by,
                'created_at' => $logistics->created_at,
                'old_values' => null,
                'new_values' => $virtualValues,
            ]);
            $creator = User::find($logistics->created_by);
            $virtualCreateLog->setRelation('user', $creator);
            $logs->push($virtualCreateLog);
        }

        $fieldLabels = [
            'type'               => 'Document Type',
            'date'               => 'Date',
            'so_no'              => 'Document #',
            'loading_request'    => 'Loading Request',
            'so_qty'             => 'Order Qty',
            'commodity'          => 'Commodity',
            'sauda_type'         => 'Trade Term / Sauda Type',
            'customer'           => 'Customer / Buyer',
            'location'           => 'From Location',
            'to_location'        => 'To Location / Port',
            'delivery_address'   => 'Delivery Address',
            'factory'            => 'Factory',
            'job_order'          => 'Job Order',
            'return_port'        => 'Return Port',
            'booking_no'         => 'Booking No',
            'shipping_line'      => 'Shipping Line',
            'am_approval_status' => 'Approval Status',
            'items_transporter'  => 'Transporters',
            'items_rate'         => 'Rates',
            'items_rate_type'    => 'Rate Types',
            'items_qty'          => 'Qty',
            'items_brand'        => 'Brands',
            'items_packing'      => 'Packing Sizes',
            'total_item_qty'     => 'Total Qty',
            'items_count'        => 'Items Count',
        ];

        $relationCache = [];
        $resolveRelationValue = function ($key, $val) use (&$relationCache) {
            if ($val === null || $val === '') {
                return null;
            }
            if (is_array($val)) {
                return json_encode($val);
            }

            if ($key === 'location' && (is_numeric($val) || (is_string($val) && ctype_digit((string) $val)))) {
                $cacheKey = "loc_{$val}";
                if (isset($relationCache[$cacheKey])) return $relationCache[$cacheKey];
                $cl = CompanyLocation::find($val);
                $res = $cl ? $cl->name : $val;
                $relationCache[$cacheKey] = $res;
                return $res;
            }

            if ($key === 'to_location' && (is_numeric($val) || (is_string($val) && ctype_digit((string) $val)))) {
                $cacheKey = "to_loc_{$val}";
                if (isset($relationCache[$cacheKey])) return $relationCache[$cacheKey];
                $p = Port::find($val);
                if ($p) {
                    $relationCache[$cacheKey] = $p->name;
                    return $p->name;
                }
                $cl = CompanyLocation::find($val);
                $res = $cl ? $cl->name : $val;
                $relationCache[$cacheKey] = $res;
                return $res;
            }

            if ($key === 'am_approval_status') {
                return ucfirst($val);
            }

            if (in_array($key, ['so_qty', 'total_item_qty', 'items_qty']) && is_numeric($val)) {
                return number_format((float) $val, 2);
            }

            return $val;
        };

        $flattenArray = function ($arr, $prefix = '') use (&$flattenArray) {
            $result = [];
            if (!is_array($arr)) return $result;
            foreach ($arr as $key => $value) {
                $newKey = $prefix === '' ? $key : "{$prefix}.{$key}";
                if (is_array($value) && !empty($value) && !isset($value[0])) {
                    $result = array_merge($result, $flattenArray($value, $newKey));
                } else {
                    $result[$newKey] = $value;
                }
            }
            return $result;
        };

        $filteredLogs = [];
        $skipKeys = ['id', 'deleted_at', 'created_at', 'updated_at', 'company_id', 'am_change_made'];

        foreach ($logs as $log) {
            $rawOld = is_array($log->old_values) ? $log->old_values : (!empty($log->old_values) ? json_decode($log->old_values, true) : []);
            if (is_string($rawOld)) $rawOld = json_decode($rawOld, true);

            $rawNew = is_array($log->new_values) ? $log->new_values : (!empty($log->new_values) ? json_decode($log->new_values, true) : []);
            if (is_string($rawNew)) $rawNew = json_decode($rawNew, true);

            $old = $flattenArray($rawOld);
            $new = $flattenArray($rawNew);

            $parsedChanges = [];
            $allKeys = array_unique(array_merge(array_keys($old ?? []), array_keys($new ?? [])));

            foreach ($allKeys as $key) {
                if (in_array($key, $skipKeys)) continue;

                $oldVal = $old[$key] ?? null;
                $newVal = $new[$key] ?? null;

                if ($oldVal != $newVal) {
                    // Avoid duplicate rows for single item where total equals item value
                    if ($key === 'total_item_qty' && isset($new['items_qty']) && (float) $new['items_qty'] === (float) $newVal && (!isset($old['items_qty']) || (float) $old['items_qty'] === (float) $oldVal)) {
                        continue;
                    }

                    $parsedChanges[] = [
                        'key'   => $key,
                        'label' => $fieldLabels[$key] ?? ucwords(str_replace('_', ' ', $key)),
                        'old'   => $resolveRelationValue($key, $oldVal),
                        'new'   => $resolveRelationValue($key, $newVal),
                    ];
                }
            }

            $log->parsed_changes = $parsedChanges;
            $filteredLogs[] = $log;
        }

        return view('management.sales.logistics.partials.audit_logs_content', [
            'logistics' => $logistics,
            'logs' => $filteredLogs,
            'title' => "Audit Logs: Logistics #{$logistics->so_no}",
        ]);
    }
}
