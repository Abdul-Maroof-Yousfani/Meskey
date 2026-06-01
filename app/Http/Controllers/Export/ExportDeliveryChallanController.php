<?php

namespace App\Http\Controllers\Export;

use App\Http\Controllers\Controller;
use App\Http\Requests\Sales\DeliveryChallanRequest;
use App\Models\Export\ExportDeliveryChallan;
use App\Models\Export\ExportDeliveryOrder as DeliveryOrder;
use App\Models\Export\ExportLoadingSlip;
use App\Models\Master\ArrivalLocation;
use App\Models\Master\ArrivalSubLocation;
use App\Models\Master\CompanyLocation;
use App\Models\Master\Customer;
use App\Models\Product;
use App\Models\Master\Transporter;
use App\Models\Sales\LoadingProgramItem;
use App\Models\Sales\ReceivingRequest;
use App\Models\Sales\ReceivingRequestItem;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExportDeliveryChallanController extends Controller
{
    public function index()
    {
        return view('management.export.delivery-challan.index');
    }

    public function create()
    {
        $customers = Customer::all();
        $Transporters = Transporter::where('status', 'active')->get();
        $delivery_orders = collect();

        return view('management.export.delivery-challan.create', compact('customers', 'delivery_orders', 'Transporters'));
    }

    public function store(DeliveryChallanRequest $request)
    {
        DB::beginTransaction();
        $do_id = $request->delivery_order_id;

        $delivery_order = DeliveryOrder::find($do_id);
        if (!$delivery_order) {
            return response()->json('Selected Delivery order not found.', 422);
        }

        // Check customer account
        $customer = Customer::find($request->customer_id);
        if (!$customer || !$customer->account_id) {
            return response()->json('The selected customer does not have an account assigned. Please set the customer account first.', 422);
        }

        $preparedItems = $this->prepareDeliveryChallanItems($request);
        if ($preparedItems['error']) {
            DB::rollBack();
            return response()->json(['error' => $preparedItems['error']], 422);
        }

        // if (strtotime($delivery_order->dispatch_date) <= strtotime($request->date)) {
        //     return response()->json('Selected Delivery order is expired. Please select a different Delivery order', 422);
        // }

        try {
            $arrival_location_csv = $request->arrival_location_csv;
            $storage_location_csv = $request->storage_location_csv;

            $dispatch_date = $request->date;

            $delivery_challan = \Illuminate\Support\Facades\Cache::lock('export_dc_generation', 10)->block(5, function () use ($request, $arrival_location_csv, $storage_location_csv, $dispatch_date, $do_id, $preparedItems) {
                // Re-generate dc_no server-side to ensure uniqueness
                $dc_no = $this->getNumber($request, null, $dispatch_date);

                return ExportDeliveryChallan::create([
                    'customer_id' => $request->customer_id,
                    'reference_number' => $request->reference_number,
                    'location_id' => $request->locations[0] ?? null,
                    'arrival_id' => $arrival_location_csv,
                    'section_id' => $storage_location_csv,
                    'dispatch_date' => $dispatch_date,
                    'dc_no' => $dc_no,
                    'gp_no' => $this->getGPNumber($request, $dispatch_date),
                    'loader_name' => auth()->user()->name,
                    'labour_status' => $request->labour_status ?? 'paid',
                    'company_id' => $request->company_id,
                    'labour' => $request->labour,
                    'labour_amount' => $preparedItems['total_labour_amount'] ?? 0,
                    'transporter' => $request->transporter,
                    'transporter_amount' => $request->transporter_amount,
                    'inhouse-weighbridge' => $request->weighbridge,
                    'weighbridge-amount' => $request->weighbridge_amount,
                    'remarks' => $request->remarks,
                    'labour_rate' => ($request->labour_rate === 'N/A' || $request->labour_rate === null) ? 0 : $request->labour_rate,
                    'created_by_id' => auth()->user()->id,
                ]);
            });

            // Build delivery orders sync array from line items
            $doSyncData = [];
            foreach ($preparedItems['items'] as $itemData) {
                // Find DO ID from do_data_id
                $packingItem = \App\Models\Export\ExportDeliveryOrderPackingItem::find($itemData['do_data_id']);
                if ($packingItem && $packingItem->delivery_order_id) {
                    $dId = $packingItem->delivery_order_id;
                    if (!isset($doSyncData[$dId])) {
                        $doSyncData[$dId] = ['qty' => 0];
                    }
                    $doSyncData[$dId]['qty'] += $itemData['qty'];
                }
            }

            // Fallback if no DOs matched
            if (empty($doSyncData)) {
                $doSyncData[$do_id] = ['qty' => $preparedItems['total_qty']];
            }

            $delivery_challan->delivery_order()->sync($doSyncData);

            $createdItems = [];
            foreach ($preparedItems['items'] as $itemData) {
                $dcData = $delivery_challan->delivery_challan_data()->create([
                    'item_id' => $itemData['item_id'],
                    'qty' => $itemData['qty'],
                    'rate' => $itemData['rate'],
                    'brand_id' => $itemData['brand_id'],
                    'no_of_bags' => $itemData['no_of_bags'],
                    'bag_size' => $itemData['bag_size'],
                    'description' => $itemData['description'],
                    'truck_no' => $itemData['truck_no'],
                    'container_number' => $itemData['container_number'],
                    'do_data_id' => $itemData['do_data_id'],
                    'bag_type' => $itemData['bag_type'],
                    'ticket_id' => $itemData['ticket_id'],
                    'labour_rate' => $itemData['item_labour_rate'],
                    'labour_amount' => $itemData['item_labour_amount'],
                ]);
                $createdItems[] = $dcData;
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }

        return response()->json([
            'success' => 'Export Delivery Challan has been created successfully.',
        ]);
    }

    public function destroy($id)
    {
        DB::beginTransaction();

        try {
            $delivery_challan = ExportDeliveryChallan::lockForUpdate()->find($id);

            if (!$delivery_challan) {
                DB::rollBack();
                return response()->json([
                    'error' => 'Export Delivery Challan already deleted or not found.'
                ], 404);
            }

            if (
                $delivery_challan->am_approval_status === "approved" ||
                $delivery_challan->am_approval_status === "rejected"
            ) {
                DB::rollBack();

                return response()->json([
                    'success' => 'Delivery Challan has been approved/rejected and cannot be deleted.',
                ], 400);
            }

            // $delivery_challan->receivingRequest()?->delete();
            $delivery_challan->delete();

            DB::commit();

            return response()->json([
                'success' => 'Export Delivery Challan has been deleted successfully.',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function update(DeliveryChallanRequest $request, $id)
    {
        DB::beginTransaction();

        try {
            $delivery_challan = ExportDeliveryChallan::lockForUpdate()->find($id);

            if (!$delivery_challan) {
                DB::rollBack();
                return response()->json([
                    'error' => 'Export Delivery Challan already deleted or not found.'
                ], 404);
            }

            if (
                $delivery_challan->am_approval_status === "approved" ||
                $delivery_challan->am_approval_status === "rejected"
            ) {
                DB::rollBack();

                return response()->json([
                    'success' => 'Delivery Challan has been approved/rejected and cannot be deleted.',
                ], 400);
            }


            $do_id = $request->delivery_order_id;

            $delivery_order = DeliveryOrder::find($do_id);

            if (!$delivery_order) {
                DB::rollBack();
                return response()->json('Selected Delivery order not found.', 422);
            }

            // Check customer account
            $customer = Customer::find($request->customer_id);
            if (!$customer || !$customer->account_id) {
                DB::rollBack();
                return response()->json('The selected customer does not have an account assigned. Please set the customer account first.', 422);
            }

            $preparedItems = $this->prepareDeliveryChallanItems($request);
            if ($preparedItems['error']) {
                DB::rollBack();
                return response()->json(['error' => $preparedItems['error']], 422);
            }

            $arrival_location_csv = $request->arrival_location_csv;
            $storage_location_csv = $request->storage_location_csv;

            $delivery_challan->update([
                'customer_id' => $request->customer_id,
                'reference_number' => $request->reference_number,
                'dispatch_date' => $request->date,
                // 'dc_no' => $request->dc_no,
                // 'sauda_type' => $request->sauda_type,
                'labour_status' => $request->labour_status ?? 'paid',
                'company_id' => $request->company_id,
                'labour' => $request->labour,
                'labour_amount' => $preparedItems['total_labour_amount'] ?? 0,
                'transporter' => $request->transporter,
                'transporter_amount' => $request->transporter_amount,
                'inhouse-weighbridge' => $request->weighbridge,
                'weighbridge-amount' => $request->weighbridge_amount,
                'remarks' => $request->remarks,
                'arrival_id' => $arrival_location_csv,
                'section_id' => $storage_location_csv,
                'labour_rate' => ($request->labour_rate === 'N/A' || $request->labour_rate === null) ? 0 : $request->labour_rate,
                'created_by_id' => auth()->user()->id,
                'am_approval_status' => 'pending',
                'am_change_made' => 1,
            ]);

            // Build delivery orders sync array from line items
            $doSyncData = [];
            foreach ($preparedItems['items'] as $itemData) {
                // Find DO ID from do_data_id
                $packingItem = \App\Models\Export\ExportDeliveryOrderPackingItem::find($itemData['do_data_id']);
                if ($packingItem && $packingItem->delivery_order_id) {
                    $dId = $packingItem->delivery_order_id;
                    if (!isset($doSyncData[$dId])) {
                        $doSyncData[$dId] = ['qty' => 0];
                    }
                    $doSyncData[$dId]['qty'] += $itemData['qty'];
                }
            }

            // Fallback if no DOs matched
            if (empty($doSyncData)) {
                $doSyncData[$do_id] = ['qty' => $preparedItems['total_qty']];
            }

            $delivery_challan->delivery_order()->sync($doSyncData);
            $delivery_challan->delivery_challan_data()->delete();

            $createdItems = [];
            foreach ($preparedItems['items'] as $itemData) {
                $dcData = $delivery_challan->delivery_challan_data()->create([
                    'item_id' => $itemData['item_id'],
                    'qty' => $itemData['qty'],
                    'rate' => $itemData['rate'],
                    'brand_id' => $itemData['brand_id'],
                    'no_of_bags' => $itemData['no_of_bags'],
                    'bag_size' => $itemData['bag_size'],
                    'description' => $itemData['description'],
                    'truck_no' => $itemData['truck_no'],
                    'container_number' => $itemData['container_number'],
                    'ticket_id' => $itemData['ticket_id'],
                    'do_data_id' => $itemData['do_data_id'],
                    'bag_type' => $itemData['bag_type'],
                    'labour_rate' => $itemData['item_labour_rate'],
                    'labour_amount' => $itemData['item_labour_amount'],
                ]);
                $createdItems[] = $dcData;
            }

            DB::commit();

            return response()->json([
                'success' => 'Export Delivery Challan has been updated successfully.',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }

    }

    public function edit($id)
    {
        $delivery_challan = ExportDeliveryChallan::with(['delivery_order.exportPackingItems', 'delivery_challan_data'])->findOrFail($id);
        $customers = Customer::all();
        $Transporters = Transporter::where('status', 'active')->get();
        $delivery_orders = $delivery_challan->delivery_order;
        $locationIds = $delivery_orders->flatMap(fn($order) => explode(',', (string) $order->location_id))->filter()->unique()->values();
        $arrivalLocationIds = $delivery_orders->flatMap(fn($order) => explode(',', (string) $order->arrival_location_id))->filter()->unique()->values();
        $sectionIds = $delivery_orders->flatMap(fn($order) => explode(',', (string) $order->sub_arrival_location_id))->filter()->unique()->values();

        $locations = CompanyLocation::whereIn('id', $locationIds)->get();
        $arrivalLocations = ArrivalLocation::whereIn('id', explode(',', (string) $delivery_challan->arrival_id))->get();
        $sections = ArrivalSubLocation::whereIn('id', explode(',', (string) $delivery_challan->section_id))->get();

        return view('management.export.delivery-challan.edit', compact(
            'customers',
            'delivery_orders',
            'delivery_challan',
            'locations',
            'arrivalLocations',
            'sections',
            'locationIds',
            'arrivalLocationIds',
            'sectionIds',
            'Transporters'
        ));
    }

    public function view($id)
    {
        $delivery_challan = ExportDeliveryChallan::with(['delivery_order.exportPackingItems', 'delivery_challan_data'])->findOrFail($id);
        $customers = Customer::all();
        $Transporters = Transporter::where('status', 'active')->get();
        $delivery_orders = $delivery_challan->delivery_order;
        $locationIds = $delivery_orders->flatMap(fn($order) => explode(',', (string) $order->location_id))->filter()->unique()->values();
        $arrivalLocationIds = $delivery_orders->flatMap(fn($order) => explode(',', (string) $order->arrival_location_id))->filter()->unique()->values();
        $sectionIds = $delivery_orders->flatMap(fn($order) => explode(',', (string) $order->sub_arrival_location_id))->filter()->unique()->values();

        $locations = CompanyLocation::whereIn('id', $locationIds)->get();
        $arrivalLocations = ArrivalLocation::whereIn('id', explode(',', (string) $delivery_challan->arrival_id))->get();
        $sections = ArrivalSubLocation::whereIn('id', explode(',', (string) $delivery_challan->section_id))->get();

        return view('management.export.delivery-challan.view', compact(
            'customers',
            'delivery_orders',
            'delivery_challan',
            'locations',
            'arrivalLocations',
            'sections',
            'locationIds',
            'arrivalLocationIds',
            'sectionIds',
            'Transporters'
        ));
    }

    public function getList(Request $request)
    {
        $perPage = $request->get('per_page', 25);
        $delivery_challans = ExportDeliveryChallan::with(['delivery_challan_data.loadingProgramItem.acceptedExportDispatchQc'])
            ->latest()
            ->paginate($perPage);

        $groupedData = [];

        foreach ($delivery_challans as $delivery_challan) {
            $so_no = $delivery_challan->dc_no;
            $items = $delivery_challan->delivery_challan_data;

            if ($items->isEmpty()) {
                continue;
            }

            $itemRows = [];
            $groupedItems = [];
            foreach ($items as $itemData) {
                $itemId = $itemData->item_id;
                
                if (!isset($groupedItems[$itemId])) {
                    $groupedItems[$itemId] = [
                        'item_id' => $itemId,
                        'name' => getItem($itemId)?->name ?? 'N/A',
                        'qty' => 0,
                        'amount' => 0,
                        'accepted_qc_id' => $itemData->loadingProgramItem->acceptedExportDispatchQc->id ?? null,
                    ];
                }
                
                $groupedItems[$itemId]['qty'] += (float) $itemData->qty;
                $groupedItems[$itemId]['amount'] += ((float) $itemData->qty * (float) $itemData->rate);
            }
            
            $itemRows = array_values($groupedItems);

            $groupedData[] = [
                'sale_order' => $delivery_challan,
                'so_no' => $so_no,
                'created_by_id' => $delivery_challan->created_by_id,
                'delivery_date' => $delivery_challan->delivery_date,
                'id' => $delivery_challan->id,
                'customer_id' => $delivery_challan->customer_id,
                'status' => $delivery_challan->am_approval_status,
                'created_at' => $delivery_challan->created_at,
                'customer' => $delivery_challan->customer,
                'rowspan' => count($itemRows),
                'items' => $itemRows,
            ];
        }

        return view('management.export.delivery-challan.getList', [
            'DeliveryChallans' => $delivery_challans,
            'groupedDeliveryChallans' => $groupedData,
        ]);
    }

    public function getNumber(Request $request, $locationId = null, $contractDate = null)
    {
        $date = Carbon::parse($contractDate ?? $request->contract_date)->format('Y-m-d');
        $prefix = 'EDC-' . Carbon::parse($contractDate ?? $request->contract_date)->format('Y-m-d');

        $latestContract = ExportDeliveryChallan::where('dc_no', 'like', "$prefix-%")
            ->latest()
            ->first();

        $datePart = Carbon::parse($date)->format('Y-m-d');

        if ($latestContract) {
            $parts = explode('-', $latestContract->dc_no);
            $lastNumber = (int) end($parts);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        $dc_no = 'EDC-' . $datePart . '-' . str_pad($newNumber, 3, '0', STR_PAD_LEFT);

        if (!$locationId && !$contractDate) {
            return response()->json([
                'success' => true,
                'dc_no' => $dc_no,
            ]);
        }

        return $dc_no;
    }

    public function getGPNumber(Request $request, $dispatchDate = null)
    {
        $date = Carbon::parse($dispatchDate ?? $request->date)->format('Y-m-d');
        $prefix = 'GP-' . Carbon::parse($date)->format('Y-m-d');

        $latest = ExportDeliveryChallan::where('gp_no', 'like', "$prefix-%")
            ->latest()
            ->first();

        if ($latest) {
            $parts = explode('-', $latest->gp_no);
            $lastNumber = (int) end($parts);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return 'GP-' . $date . '-' . str_pad($newNumber, 3, '0', STR_PAD_LEFT);
    }

    public function getItemsByTickets(Request $request)
    {
        $ticket_id = $request->ticket_id;
        $delivery_challan_id = $request->delivery_challan_id;
        $loading_programs = LoadingProgramItem::with([
            'exportLoadingProgram.deliveryOrder.exportOrder.packingItems.bagType',
            'exportLoadingProgram.deliveryOrder.exportOrder.packingItems.brand',
            'exportLoadingSlip.deliveryOrder.exportPackingItems.bagType',
            'exportLoadingSlip.deliveryOrder.exportPackingItems.brand',
            'exportLoadingSlip.deliveryOrder.exportOrder.packingItems',
            'exportLoadingSlip.secondWeighbridge',
        ])->where('id', $ticket_id)->get();
        $items = Product::select('id', 'name')->get();
        $existingRows = collect();

        if ($delivery_challan_id) {
            $existingRows = ExportDeliveryChallan::with('delivery_challan_data')
                ->find($delivery_challan_id)?->delivery_challan_data?->keyBy('do_data_id') ?? collect();
        }

        return view('management.export.delivery-challan.getItem', compact('loading_programs', 'items', 'existingRows'));
    }

    public function getTickets(Request $request)
    {
        $delivery_order_ids = $request->delivery_order_ids;
        $delivery_challan_id = $request->delivery_challan_id;

        if (empty($delivery_order_ids)) {
            return response()->json(['tickets' => []]);
        }

        $query = LoadingProgramItem::with([
            'exportLoadingProgram.deliveryOrder',
            'exportDispatchQc',
            'exportLoadingSlip.secondWeighbridge',
        ])
            ->whereHas('exportLoadingProgram')
            ->whereHas('exportLoadingSlip.secondWeighbridge')
            ->whereHas('exportLoadingSlip.deliveryOrder', function ($q) use ($delivery_order_ids) {
                $q->whereIn('delivery_order.id', $delivery_order_ids);
            });

        if ($delivery_challan_id) {
            $query->where(function ($q) use ($delivery_challan_id) {
                $q->whereDoesntHave('delivery_challan_data')
                    ->orWhereHas('delivery_challan_data', function ($subQ) use ($delivery_challan_id) {
                        $subQ->where('delivery_challan_id', $delivery_challan_id);
                    });
            });
        } else {
            $query->whereDoesntHave('delivery_challan_data');
        }

        $tickets = $query->get()->map(function ($ticket) {
            return [
                'id' => $ticket->id,
                'text' => $ticket->transaction_number . ' -- ' . $ticket->truck_number,
            ];
        });

        return response()->json(['tickets' => $tickets]);
    }

    public function getTicketsWithDispatchQc(Request $request)
    {
        $delivery_challan_id = $request->delivery_challan_id;

        $query = LoadingProgramItem::with([
            'exportLoadingProgram.deliveryOrder.customer',
            'exportLoadingProgram.deliveryOrder',
            'exportLoadingProgram.exportOrder',
            'exportDispatchQc',
            'arrivalLocation',
            'subArrivalLocation',
            'exportLoadingSlip.secondWeighbridge',
        ])
            ->whereHas('exportLoadingProgram')
            ->whereHas('exportLoadingSlip.secondWeighbridge');

        if ($delivery_challan_id) {
            $query->where(function ($q) use ($delivery_challan_id) {
                $q->whereDoesntHave('delivery_challan_data')
                    ->orWhereHas('delivery_challan_data', function ($subQ) use ($delivery_challan_id) {
                        $subQ->where('delivery_challan_id', $delivery_challan_id);
                    });
            });
        } else {
            $query->whereDoesntHave('delivery_challan_data');
        }

        $tickets = $query->get()->map(function ($ticket) {
            return [
                'id' => $ticket->id,
                'text' => $ticket->transaction_number . ' -- ' . $ticket->truck_number,
                'transaction_number' => $ticket->transaction_number,
                'truck_number' => $ticket->truck_number,
            ];
        });

        return response()->json(['tickets' => $tickets]);
    }

    public function getTicketDataForDC(Request $request)
    {
        $ticket_id = $request->ticket_id;

        if (!$ticket_id) {
            return response()->json(['error' => 'No ticket selected'], 400);
        }

        $ticket = LoadingProgramItem::with([
            'exportLoadingProgram.deliveryOrder.customer',
            'exportLoadingProgram.deliveryOrder.exportOrder.product',
            'exportLoadingProgram.exportOrder.product',
            'exportLoadingProgram',
            'exportDispatchQc',
            'arrivalLocation',
            'subArrivalLocation',
            'exportLoadingSlip.secondWeighbridge',
        ])->findOrFail($ticket_id);

        $loadingSlip = ExportLoadingSlip::where('loading_program_item_id', $ticket_id)->first();
        if (!$loadingSlip) {
            return response()->json(['error' => 'Loading slip not found for this ticket'], 404);
        }

        $deliveryOrder = $loadingSlip->deliveryOrder;
        if (!$deliveryOrder) {
            return response()->json(['error' => 'Delivery order not found for this loading slip'], 404);
        }

        $loadingProgram = $ticket->exportLoadingProgram;
        if (!$loadingProgram) {
            return response()->json(['error' => 'Loading program not found for this ticket'], 404);
        }

        $companyLocationIds = $loadingProgram->company_locations ?? [];
        if (!is_array($companyLocationIds)) {
            $companyLocationIds = array_values(array_filter(explode(',', (string) $companyLocationIds)));
        }
        $companyLocations = [];
        if (!empty($companyLocationIds)) {
            $companyLocations = CompanyLocation::whereIn('id', $companyLocationIds)
                ->get()
                ->map(fn($loc) => ['id' => $loc->id, 'text' => $loc->name])
                ->toArray();
        }

        $arrivalLocations = [];
        $arrivalLocationIds = [];
        
        $factoryNamesStr = $loadingSlip->factory ?? '';
        if ($factoryNamesStr) {
            $factoryNames = array_map('trim', explode(',', $factoryNamesStr));
            $arrivalLocs = ArrivalLocation::whereIn('name', $factoryNames)->get();
            if ($arrivalLocs->isNotEmpty()) {
                $arrivalLocationIds = $arrivalLocs->pluck('id')->toArray();
                $arrivalLocations = $arrivalLocs->map(fn($loc) => ['id' => $loc->id, 'text' => $loc->name])->toArray();
            }
        }

        $packing = $ticket->packing;
        $clean_packing = is_numeric($packing) ? $packing : trim(explode(',', (string) $packing)[0]);
        $bag_packing = \App\Models\BagPacking::select('id')
            ->where(function ($q) use ($clean_packing) {
                $q->where('name', $clean_packing . ' kg')
                    ->orWhere('name', $clean_packing . 'KG');
            })
            ->where('status', 1)
            ->first();

        $arrival_location_id = $ticket->arrival_location_id;
        $category_id = $deliveryOrder?->exportOrder?->product?->category_id;

        $labour_rate = null;
        if ($category_id && $arrival_location_id && $bag_packing) {
            $labour_rate = \App\Models\Master\LabourRate::select('id', 'rate')
                ->where('category_id', $category_id)
                ->where('factory_id', $arrival_location_id)
                ->where('bag_packing_id', $bag_packing->id)
                ->where('status', 1)
                ->first();
        }

        $subArrivalLocations = [];
        $subArrivalLocationIds = [];

        $galaNames = $this->decodeStoredMultiValue($loadingSlip->gala ?? '');
        if (!empty($galaNames)) {
            $subArrivalLocs = ArrivalSubLocation::whereIn('name', $galaNames)->get();
            if ($subArrivalLocs->isNotEmpty()) {
                $subArrivalLocationIds = $subArrivalLocs->pluck('id')->toArray();
                $subArrivalLocations = $subArrivalLocs->map(fn($loc) => ['id' => $loc->id, 'text' => $loc->name])->toArray();
            }
        }

        $loadingSlipLabour = $ticket->exportLoadingSlip?->labour ?? null;
        $transporterId = $deliveryOrder->transporter_id ?? $ticket->transporter_id ?? $loadingProgram->transporter_id ?? null;

        return response()->json([
            'success' => true,
            // 'rate' => $labour_rate->rate,
            'rate' => $labour_rate ? $labour_rate->rate : 'N/A',
            'second_weighbridge_qty_mt' => round(($loadingSlip->secondWeighbridge->net_weight ?? 0) / 1000, 3),
            'ticket' => [
                'id' => $ticket->id,
                'transaction_number' => $ticket->transaction_number,
                'truck_number' => $ticket->truck_number,
            ],
            'delivery_order' => [
                'id' => $deliveryOrder->id,
                'reference_no' => $deliveryOrder->reference_no,
                'sauda_type' => strtolower($deliveryOrder->sauda_type ?? ''),
            ],
            'customer' => [
                'id' => $deliveryOrder->customer->id ?? null,
                'name' => $deliveryOrder->customer->name ?? 'N/A',
            ],
            'locations' => [
                'company_locations' => $companyLocations,
                'company_location_ids' => $companyLocationIds,
                'arrival_locations' => $arrivalLocations,
                'arrival_location_ids' => $arrivalLocationIds,
                'sub_arrival_locations' => $subArrivalLocations,
                'sub_arrival_location_ids' => $subArrivalLocationIds,
            ],
            'loading_slip_labour' => $loadingSlipLabour,
            'transporter_id' => $transporterId,
            'is_labour_editable' => false,
        ]);
    }

    protected function normalizeQtyToMt($qty, $bagSize = 0, $noOfBags = 0): float
    {
        $qty = (float) $qty;
        $bagSize = (float) $bagSize;
        $noOfBags = (float) $noOfBags;

        if ($qty <= 0) {
            return 0.0;
        }

        $calculatedMt = ($bagSize > 0 && $noOfBags > 0) ? (($bagSize * $noOfBags) / 1000) : 0;
        if ($calculatedMt > 0 && $qty > ($calculatedMt * 10)) {
            $qty = $qty / 1000;
        } elseif ($calculatedMt <= 0 && $qty > 500) {
            // Fallback guard for legacy forms posting KG.
            $qty = $qty / 1000;
        }

        return round($qty, 3);
    }

    protected function prepareDeliveryChallanItems(Request $request): array
    {
        $itemIds = $request->item_id ?? [];
        $ticketIds = array_values(array_filter(array_map('intval', $request->ticket_id ?? [])));
        $uniqueTicketIds = array_values(array_unique($ticketIds));

        if (count($uniqueTicketIds) !== 1) {
            return ['error' => 'Exactly one ticket is required for export delivery challan.', 'items' => [], 'total_qty' => 0];
        }

        $ticketId = $uniqueTicketIds[0];
        $availableQtyMt = $this->getSecondWeighbridgeQtyMt($ticketId);

        if ($availableQtyMt <= 0) {
            return ['error' => 'Selected ticket does not have a valid second weighbridge quantity.', 'items' => [], 'total_qty' => 0];
        }

        $items = [];
        $totalQty = 0;
        $totalLabourAmount = 0;

        foreach ($itemIds as $index => $itemId) {
            $itemId = (int) $itemId;
            $doDataId = (int) ($request->do_data_id[$index] ?? 0);

            if (!$itemId || !$doDataId) {
                continue;
            }

            $qty = round((float) ($request->qty[$index] ?? 0), 3);
            $noOfBags = (int) ($request->no_of_bags[$index] ?? 0);
            $bagSize = round((float) ($request->bag_size[$index] ?? 0), 3);

            if ($qty < 0) {
                return ['error' => 'Line item quantity cannot be negative.', 'items' => [], 'total_qty' => 0];
            }

            $items[] = [
                'item_id' => $itemId,
                'qty' => $qty,
                'rate' => round((float) ($request->rate[$index] ?? 0), 2),
                'brand_id' => $request->brand_id[$index] ?? null,
                'no_of_bags' => $noOfBags,
                'bag_size' => $bagSize,
                'description' => $request->desc[$index] ?? '',
                'truck_no' => $request->truck_no[$index] ?? null,
                'container_number' => $request->container_number[$index] ?? null,
                'do_data_id' => $doDataId,
                'bag_type' => $request->bag_type[$index] ?? null,
                'ticket_id' => (int) ($request->ticket_id[$index] ?? 0),
                'item_labour_rate' => round((float) ($request->item_labour_rate[$index] ?? 0), 2),
                'item_labour_amount' => round((float) ($request->item_labour_amount[$index] ?? 0), 2),
            ];

            $totalQty += $qty;
            $totalLabourAmount += (float) ($request->item_labour_amount[$index] ?? 0);
        }

        $totalQty = round($totalQty, 3);

        if (empty($items)) {
            return ['error' => 'At least one delivery challan line item is required.', 'items' => [], 'total_qty' => 0];
        }

        if ($totalQty > $availableQtyMt + 0.001) {
            return [
                'error' => "Total QTY ({$totalQty} MT) 2nd Weighbridge quantity ({$availableQtyMt} MT) se zyada hai.",
                'items' => [],
                'total_qty' => 0,
            ];
        }

        return [
            'error' => null,
            'items' => $items,
            'total_qty' => $totalQty,
            'total_labour_amount' => round($totalLabourAmount, 2),
            'available_qty_mt' => $availableQtyMt,
        ];
    }

    protected function getSecondWeighbridgeQtyMt(int $ticketId): float
    {
        $slip = ExportLoadingSlip::with('secondWeighbridge')
            ->where('loading_program_item_id', $ticketId)
            ->first();

        if (!$slip || !$slip->secondWeighbridge) {
            return 0;
        }

        return round(($slip->secondWeighbridge->net_weight ?? 0) / 1000, 3);
    }

    private function decodeStoredMultiValue($value): array
    {
        if (blank($value)) {
            return [];
        }

        $decoded = json_decode((string) $value, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return collect($decoded)->map(fn($item) => trim((string) $item))->filter()->values()->all();
        }

        return collect(explode(',', (string) $value))
            ->map(fn($item) => trim($item))
            ->filter()
            ->values()
            ->all();
    }

    public function resolveDeliveryOrders($item): \Illuminate\Support\Collection
    {
        if (!$item) {
            return collect();
        }

        $deliveryOrders = collect();

        // Get DOs from ticket
        $ticketDOs = $item->deliveryOrders()->withoutGlobalScopes()->get();
        if ($ticketDOs->isNotEmpty()) {
            $deliveryOrders = $deliveryOrders->merge($ticketDOs);
        }

        // Get DOs from LP
        if ($item->exportLoadingProgram) {
            $lpDOs = $item->exportLoadingProgram->deliveryOrders()->withoutGlobalScopes()->get();
            if ($lpDOs->isNotEmpty()) {
                $deliveryOrders = $deliveryOrders->merge($lpDOs);
            }
            if ($item->exportLoadingProgram->deliveryOrder) {
                $deliveryOrders->push($item->exportLoadingProgram->deliveryOrder);
            }
        }

        return $deliveryOrders->filter()
            ->where('type', 'export_order')
            ->unique('id')
            ->sortBy('id')
            ->values();
    }

    public function getLaboursByLocations(Request $request)
    {
        $arrivalLocationIds = $request->arrival_location_ids;
        $companyLocationIds = $request->company_location_ids;

        if (empty($arrivalLocationIds) && empty($companyLocationIds)) {
            return response()->json(\App\Models\Master\Vendor::all(['id', 'name']));
        }

        if (!empty($arrivalLocationIds) && !is_array($arrivalLocationIds)) {
            $arrivalLocationIds = [$arrivalLocationIds];
        }
        if (!empty($companyLocationIds) && !is_array($companyLocationIds)) {
            $companyLocationIds = [$companyLocationIds];
        }

        $query = \App\Models\Master\Vendor::query();

        if (!empty($companyLocationIds)) {
            $query->where(function($q) use ($companyLocationIds) {
                foreach ($companyLocationIds as $id) {
                    if ($id) {
                        $q->orWhereJsonContains('company_location_ids', (string)$id)
                          ->orWhereJsonContains('company_location_ids', (int)$id);
                    }
                }
            });
        }

        if (!empty($arrivalLocationIds)) {
            $query->where(function($q) use ($arrivalLocationIds) {
                foreach ($arrivalLocationIds as $id) {
                    if ($id) {
                        $q->orWhereJsonContains('arrival_location_ids', (string)$id)
                          ->orWhereJsonContains('arrival_location_ids', (int)$id);
                    }
                }
            });
        }

        $vendors = $query->get(['id', 'name']);

        if ($vendors->isEmpty()) {
            $vendors = \App\Models\Master\Vendor::all(['id', 'name']);
        }

        return response()->json($vendors);
    }

    public function dailyDispatch()
    {
        $delivery_orders = \App\Models\Export\ExportDeliveryOrder::whereHas('delivery_challans', function($q) {
            $q->where('am_approval_status', 'approved');
        })->get();
        return view('management.export.delivery-challan.daily-dispatch', compact('delivery_orders'));
    }

    public function getDailyDispatchFilters(Request $request)
    {
        $do_id = $request->do_id;
        
        $dc_ids = \App\Models\Export\ExportDeliveryChallan::where('am_approval_status', 'approved')
        ->whereHas('delivery_order', function($q) use ($do_id) {
            $q->where('delivery_order.id', $do_id);
        })->pluck('id');

        $dates = \App\Models\Export\ExportDeliveryChallan::whereIn('id', $dc_ids)
            ->selectRaw('MIN(dispatch_date) as min_date, MAX(dispatch_date) as max_date')
            ->first();

        $data = \App\Models\Export\ExportDeliveryChallanData::whereIn('delivery_challan_id', $dc_ids)
            ->with(['loadingProgramItem.exportLoadingSlip'])
            ->get();

        $delivery_order = \App\Models\Export\ExportDeliveryOrder::find($do_id);

        $containers = [];
        $trucks = [];
        $packings = [];
        $locations = [];

        if ($delivery_order && $delivery_order->arrival_location_id) {
            $factory_ids = explode(',', $delivery_order->arrival_location_id);
            $factory_names = \App\Models\Master\ArrivalLocation::whereIn('id', $factory_ids)->pluck('name')->toArray();
            $locations = array_merge($locations, $factory_names);
        }

        foreach ($data as $item) {
            $lpItem = $item->loadingProgramItem;
            
            $c = ($lpItem && $lpItem->container_number) ? $lpItem->container_number : '';
            if ($c) $containers[] = $c;
            
            $t = ($lpItem && $lpItem->truck_number) ? $lpItem->truck_number : $item->truck_no;
            if ($t) $trucks[] = $t;
            
            $p = ($lpItem && $lpItem->packing) ? $lpItem->packing : $item->bag_size;
            if ($p) $packings[] = $p;
            
            if ($lpItem && $lpItem->exportLoadingSlip && $lpItem->exportLoadingSlip->factory) {
                $item_factories = array_map('trim', explode(',', $lpItem->exportLoadingSlip->factory));
                $locations = array_merge($locations, $item_factories);
            }
        }

        return response()->json([
            'containers' => array_values(array_unique(array_filter($containers))),
            'trucks' => array_values(array_unique(array_filter($trucks))),
            'packings' => array_values(array_unique(array_filter($packings))),
            'locations' => array_values(array_unique(array_filter($locations))),
            'min_date' => $dates->min_date ?? '',
            'max_date' => $dates->max_date ?? '',
        ]);
    }

    public function generateDailyDispatchReport(Request $request)
    {
        $do_id = $request->do_id;
        $start_date = $request->start_date;
        $end_date = $request->end_date;
        $location = array_filter((array) ($request->location ?? []));
        $container = array_filter((array) ($request->container ?? []));
        $truck = array_filter((array) ($request->truck ?? []));
        $packing = array_filter((array) ($request->packing ?? []));

        $delivery_order = \App\Models\Export\ExportDeliveryOrder::with([
            'customer', 
            'exportOrder.jobOrders',
            'exportFormE',
            'exportPackingItems.brand'
        ])->findOrFail($do_id);

        // Fetch DO Factory name(s) (arrival_location_id)
        $do_factories = [];
        if ($delivery_order->arrival_location_id) {
            $factory_ids = explode(',', $delivery_order->arrival_location_id);
            $do_factories = \App\Models\Master\ArrivalLocation::whereIn('id', $factory_ids)->pluck('name')->toArray();
        }
        $delivery_order->do_factories_string = implode(', ', $do_factories);

        // Fetch DO Station name (location_id)
        $do_station = '';
        if ($delivery_order->location_id) {
            $stationModel = \App\Models\Master\CompanyLocation::find($delivery_order->location_id);
            $do_station = $stationModel ? $stationModel->name : '';
        }
        $delivery_order->do_station_string = $do_station;

        $dc_query = \App\Models\Export\ExportDeliveryChallan::with([
            'delivery_challan_data.loadingProgramItem.exportLoadingSlip',
            'delivery_challan_data.loadingProgramItem.outerItems'
        ])
            ->where('am_approval_status', 'approved')
            ->whereHas('delivery_order', function($q) use ($do_id) {
                $q->where('delivery_order.id', $do_id);
            });

        if ($start_date && $end_date) {
            $dc_query->whereBetween('dispatch_date', [$start_date, $end_date]);
        }

        $delivery_challans = $dc_query->get();

        $report_data = [];
        $seq = 1;
        $total_bags = 0;
        $total_weight = 0;

        foreach ($delivery_challans as $dc) {
            $data_factory = [];
            
            // Collect factory from all items in this DC to see if it matches the filter
            foreach ($dc->delivery_challan_data as $data) {
                $lpItem = $data->loadingProgramItem;
                if ($lpItem && $lpItem->exportLoadingSlip && $lpItem->exportLoadingSlip->factory) {
                    $item_factories = array_map('trim', explode(',', $lpItem->exportLoadingSlip->factory));
                    $data_factory = array_merge($data_factory, $item_factories);
                }
            }
            $data_factory = array_unique($data_factory);

            if (!empty($location) && empty(array_intersect($location, $data_factory))) {
                continue; // Skip entire DC if none of its items have the selected factory
            }

            $dc_container = [];
            $dc_truck = [];
            $dc_packing = [];
            $dc_bags = 0;
            $dc_weight = 0;
            $dc_dosage = 0;
            $dc_dry_bags = 0;
            $dc_craft_paper = 0;
            $dc_empty_bags = 0;
            $dc_seal = '';
            $dc_galla = '';
            
            $include_dc = false;
            $processed_tickets = [];

            foreach ($dc->delivery_challan_data as $data) {
                $lpItem = $data->loadingProgramItem;
                $slip = $lpItem ? $lpItem->exportLoadingSlip : null;

                $data_container = ($lpItem && $lpItem->container_number) ? $lpItem->container_number : '';
                $data_truck = ($lpItem && $lpItem->truck_number) ? $lpItem->truck_number : $data->truck_no;
                $data_packing = ($lpItem && $lpItem->packing) ? $lpItem->packing : $data->bag_size;

                if (!empty($container) && !in_array($data_container, $container)) continue;
                if (!empty($truck) && !in_array($data_truck, $truck)) continue;
                if (!empty($packing) && !in_array($data_packing, $packing)) continue;

                $include_dc = true;
                $weight = $data->qty * 1000; // Qty is MT, we need KG
                
                if ($data_container) $dc_container[] = $data_container;
                if ($data_truck) $dc_truck[] = $data_truck;
                if ($data_packing) $dc_packing[] = $data_packing;
                
                $dc_bags += $data->no_of_bags;
                $dc_weight += $weight;

                if ($lpItem && !in_array($lpItem->id, $processed_tickets)) {
                    $processed_tickets[] = $lpItem->id;
                    
                    if (!$dc_seal) $dc_seal = $lpItem->exportLoadingSlip->seal_no ?? '';
                    if (!$dc_galla) {
                        $gallaData = $lpItem->exportLoadingSlip->gala ?? '';
                        if (!empty($gallaData)) {
                            $decodedGalla = json_decode($gallaData, true);
                            if (is_array($decodedGalla) && count($decodedGalla) > 0) {
                                $dc_galla = $decodedGalla[0];
                            } else {
                                $dc_galla = $gallaData;
                            }
                        }
                    }

                    if ($lpItem->outerItems) {
                        foreach ($lpItem->outerItems as $outerItem) {
                            $name = strtolower($outerItem->item_name);
                            if (str_contains($name, 'dosage')) $dc_dosage += $outerItem->qty;
                            elseif (str_contains($name, 'craft')) $dc_craft_paper += $outerItem->qty;
                            elseif (str_contains($name, 'dry')) $dc_dry_bags += $outerItem->qty;
                            elseif (str_contains($name, 'empty') || str_contains($name, 'naubahar')) $dc_empty_bags += $outerItem->qty;
                        }
                    }
                }
            }

            if ($include_dc) {
                $report_data[] = [
                    'seq' => $seq++,
                    'container' => implode(', ', array_unique($dc_container)),
                    'truck' => implode(', ', array_unique($dc_truck)),
                    'packing' => implode('/', array_unique($dc_packing)),
                    'bags' => $dc_bags,
                    'weight' => $dc_weight,
                    'seal' => $dc_seal,
                    'galla' => $dc_galla,
                    'dosage' => $dc_dosage ?: '',
                    'dry_bags' => $dc_dry_bags ?: '',
                    'craft_paper' => $dc_craft_paper ?: '',
                    'empty_bags' => $dc_empty_bags ?: '',
                    'gp' => $dc->gp_no,
                ];
                $total_bags += $dc_bags;
                $total_weight += $dc_weight;
            }
        }

        $iso_code = $request->iso_code ?? 'MFT/QR/037';
        $issue_date = $request->issue_date ?? '18-07-14';
        $issue_no = $request->issue_no ?? '1';

        return view('management.export.delivery-challan.print-daily-dispatch', compact(
            'delivery_order',
            'report_data',
            'total_bags',
            'total_weight',
            'start_date',
            'end_date',
            'iso_code',
            'issue_date',
            'issue_no'
        ));
    }
}
