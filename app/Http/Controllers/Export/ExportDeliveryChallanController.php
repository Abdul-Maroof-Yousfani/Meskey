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
        $delivery_orders = DeliveryOrder::select('delivery_order.id', 'delivery_order.reference_no')
            ->join('loading_slips', 'delivery_order.id', '=', 'loading_slips.delivery_order_id')
            ->join('sales_second_weighbridges', 'loading_slips.id', '=', 'sales_second_weighbridges.loading_slip_id')
            ->where('sales_second_weighbridges.type', 'export_order')
            ->distinct()
            ->get();

        return view('management.export.delivery-challan.create', compact('customers', 'delivery_orders'));
    }

    public function store(DeliveryChallanRequest $request)
    {
        DB::beginTransaction();
        $do_id = $request->delivery_order_id;

        $delivery_order = DeliveryOrder::find($do_id);
        if (!$delivery_order) {
            return response()->json('Selected Delivery order not found.', 422);
        }

        if (strtotime($delivery_order->dispatch_date) <= strtotime($request->date)) {
            return response()->json('Selected Delivery order is expired. Please select a different Delivery order', 422);
        }

        try {
            $arrival_location_csv = $request->arrival_location_csv;
            $storage_location_csv = $request->storage_location_csv;

            $delivery_challan = ExportDeliveryChallan::create([
                'customer_id' => $request->customer_id,
                'reference_number' => $request->reference_number,
                'location_id' => $request->locations[0] ?? null,
                'arrival_id' => $arrival_location_csv,
                'section_id' => $storage_location_csv,
                'dispatch_date' => $request->date,
                'dc_no' => $request->dc_no,
                'sauda_type' => $request->sauda_type,
                'labour_status' => $request->labour_status ?? 'paid',
                'company_id' => $request->company_id,
                'labour' => $request->labour,
                'labour_amount' => $request->labour_amount,
                'transporter' => $request->transporter,
                'transporter_amount' => $request->transporter_amount,
                'inhouse-weighbridge' => $request->weighbridge,
                'weighbridge-amount' => $request->weighbridge_amount,
                'remarks' => $request->remarks,
                'labour_rate' => ($request->labour_rate === 'N/A' || $request->labour_rate === null) ? 0 : $request->labour_rate,
                'created_by_id' => auth()->user()->id,
            ]);

            $delivery_challan->delivery_order()->sync([
                $do_id => ['qty' => $request->qty[0] ?? 0],
            ]);

            $createdItems = [];
            foreach ($request->item_id as $index => $item) {
                $dcData = $delivery_challan->delivery_challan_data()->create([
                    'item_id' => $request->item_id[$index],
                    'qty' => $request->qty[$index],
                    'rate' => $request->rate[$index],
                    'brand_id' => $request->brand_id[$index],
                    'no_of_bags' => $request->no_of_bags[$index],
                    'bag_size' => $request->bag_size[$index],
                    'description' => $request->desc[$index] ?? '',
                    'truck_no' => $request->truck_no[$index],
                    'container_number' => $request->container_number[$index],
                    'do_data_id' => $request->do_data_id[$index],
                    'bag_type' => $request->bag_type[$index],
                    'ticket_id' => $request->ticket_id[$index],
                ]);
                $createdItems[] = $dcData;
            }

            $receivingRequest = ReceivingRequest::create([
                'delivery_challan_id' => $delivery_challan->id,
                'dc_no' => $delivery_challan->dc_no,
                'dc_date' => $delivery_challan->dispatch_date,
                'truck_number' => $request->truck_no[0] ?? null,
                'bilty' => $request->bilty_no[0] ?? null,
                'labour' => $delivery_challan->labour,
                'transporter' => $delivery_challan->transporter,
                'inhouse_weighbridge' => $delivery_challan->{'inhouse-weighbridge'} ?? null,
                'labour_amount' => $delivery_challan->labour_amount ?? 0,
                'transporter_amount' => $delivery_challan->transporter_amount ?? 0,
                'inhouse_weighbridge_amount' => $delivery_challan->{'weighbridge-amount'} ?? 0,
                'company_id' => $delivery_challan->company_id,
                'created_by_id' => $delivery_challan->created_by_id,
            ]);

            foreach ($createdItems as $dcData) {
                $product = Product::find($dcData->item_id);
                ReceivingRequestItem::create([
                    'receiving_request_id' => $receivingRequest->id,
                    'delivery_challan_data_id' => $dcData->id,
                    'item_id' => $dcData->item_id,
                    'item_name' => $product?->name ?? 'N/A',
                    'dispatch_weight' => $dcData->qty ?? 0,
                    'receiving_weight' => 0,
                    'difference_weight' => $dcData->qty ?? 0,
                    'seller_portion' => 0,
                    'remaining_amount' => $dcData->qty ?? 0,
                ]);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }

        return response()->json(['message' => 'Export Delivery Challan has been created']);
    }

    public function destroy($id)
    {
        $delivery_challan = ExportDeliveryChallan::findOrFail($id);
        $delivery_challan->receivingRequest()?->delete();
        $delivery_challan->delete();

        return response()->json(['message' => 'Export Delivery Challan has been deleted!']);
    }

    public function update(DeliveryChallanRequest $request, $id)
    {
        DB::beginTransaction();
        $do_id = $request->delivery_order_id;

        $delivery_challan = ExportDeliveryChallan::findOrFail($id);
        $delivery_order = DeliveryOrder::find($do_id);
        if (!$delivery_order) {
            return response()->json('Selected Delivery order not found.', 422);
        }

        if (strtotime($delivery_order->dispatch_date) < strtotime($request->date)) {
            return response()->json('Selected Delivery order is expired. Please select a different Delivery order', 422);
        }

        try {
            $arrival_location_csv = $request->arrival_location_csv;
            $storage_location_csv = $request->storage_location_csv;

            $delivery_challan->update([
                'customer_id' => $request->customer_id,
                'reference_number' => $request->reference_number,
                'dispatch_date' => $request->date,
                'dc_no' => $request->dc_no,
                'sauda_type' => $request->sauda_type,
                'labour_status' => $request->labour_status ?? 'paid',
                'company_id' => $request->company_id,
                'labour' => $request->labour,
                'labour_amount' => $request->labour_amount,
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

            $delivery_challan->delivery_order()->sync([$do_id]);
            $delivery_challan->delivery_challan_data()->delete();

            $createdItems = [];
            foreach ($request->item_id as $index => $item) {
                $dcData = $delivery_challan->delivery_challan_data()->create([
                    'item_id' => $request->item_id[$index],
                    'qty' => $request->qty[$index],
                    'rate' => $request->rate[$index],
                    'brand_id' => $request->brand_id[$index],
                    'no_of_bags' => $request->no_of_bags[$index],
                    'bag_size' => $request->bag_size[$index],
                    'description' => $request->desc[$index] ?? '',
                    'truck_no' => $request->truck_no[$index],
                    'container_number' => $request->container_number[$index],
                    'ticket_id' => $request->ticket_id[$index],
                    'do_data_id' => $request->do_data_id[$index],
                    'bag_type' => $request->bag_type[$index],
                ]);
                $createdItems[] = $dcData;
            }

            $receivingRequest = $delivery_challan->receivingRequest;
            if ($receivingRequest) {
                $receivingRequest->update([
                    'dc_no' => $delivery_challan->dc_no,
                    'dc_date' => $delivery_challan->dispatch_date,
                    'truck_number' => $request->truck_no[0] ?? null,
                    'bilty' => $request->bilty_no[0] ?? null,
                    'labour' => $delivery_challan->labour,
                    'transporter' => $delivery_challan->transporter,
                    'inhouse_weighbridge' => $delivery_challan->{'inhouse-weighbridge'} ?? null,
                    'labour_amount' => $delivery_challan->labour_amount ?? 0,
                    'transporter_amount' => $delivery_challan->transporter_amount ?? 0,
                    'inhouse_weighbridge_amount' => $delivery_challan->{'weighbridge-amount'} ?? 0,
                ]);
            } else {
                $receivingRequest = ReceivingRequest::create([
                    'delivery_challan_id' => $delivery_challan->id,
                    'dc_no' => $delivery_challan->dc_no,
                    'dc_date' => $delivery_challan->dispatch_date,
                    'truck_number' => $request->truck_no[0] ?? null,
                    'bilty' => $request->bilty_no[0] ?? null,
                    'labour' => $delivery_challan->labour,
                    'transporter' => $delivery_challan->transporter,
                    'inhouse_weighbridge' => $delivery_challan->{'inhouse-weighbridge'} ?? null,
                    'labour_amount' => $delivery_challan->labour_amount ?? 0,
                    'transporter_amount' => $delivery_challan->transporter_amount ?? 0,
                    'inhouse_weighbridge_amount' => $delivery_challan->{'weighbridge-amount'} ?? 0,
                    'company_id' => $delivery_challan->company_id,
                    'created_by_id' => $delivery_challan->created_by_id,
                ]);
            }

            $receivingRequest->items()->delete();
            foreach ($createdItems as $dcData) {
                $product = Product::find($dcData->item_id);
                ReceivingRequestItem::create([
                    'receiving_request_id' => $receivingRequest->id,
                    'delivery_challan_data_id' => $dcData->id,
                    'item_id' => $dcData->item_id,
                    'item_name' => $product?->name ?? 'N/A',
                    'dispatch_weight' => $dcData->qty ?? 0,
                    'receiving_weight' => 0,
                    'difference_weight' => $dcData->qty ?? 0,
                    'seller_portion' => 0,
                    'remaining_amount' => $dcData->qty ?? 0,
                ]);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }

        return response()->json(['message' => 'Export Delivery Challan has been updated']);
    }

    public function edit($id)
    {
        $delivery_challan = ExportDeliveryChallan::with(['delivery_order.exportPackingItems', 'delivery_challan_data'])->findOrFail($id);
        $customers = Customer::all();
        $delivery_orders = $delivery_challan->delivery_order;
        $locationIds = $delivery_orders->pluck('location_id')->filter()->unique();
        $arrivalLocationIds = $delivery_orders->pluck('arrival_location_id')->filter()->unique();
        $sectionIds = $delivery_orders->pluck('sub_arrival_location_id')->filter()->unique();

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
            'sectionIds'
        ));
    }

    public function view($id)
    {
        $delivery_challan = ExportDeliveryChallan::with(['delivery_order.exportPackingItems', 'delivery_challan_data'])->findOrFail($id);
        $customers = Customer::all();
        $delivery_orders = $delivery_challan->delivery_order;
        $locationIds = $delivery_orders->pluck('location_id')->filter()->unique();
        $arrivalLocationIds = $delivery_orders->pluck('arrival_location_id')->filter()->unique();
        $sectionIds = $delivery_orders->pluck('sub_arrival_location_id')->filter()->unique();

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
            'sectionIds'
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
            foreach ($items as $itemData) {
                $itemRows[] = [
                    'item_data' => $itemData,
                    'accepted_qc_id' => $itemData->loadingProgramItem->acceptedExportDispatchQc->id ?? null,
                ];
            }

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

    public function getItemsByTickets(Request $request)
    {
        $ticket_id = $request->ticket_id;
        $loading_programs = LoadingProgramItem::with([
            'exportLoadingProgram.deliveryOrder.exportOrder.packingItems',
            'exportLoadingProgram.exportOrder.packingItems',
            'exportLoadingSlip.deliveryOrder.exportPackingItems',
            'exportLoadingSlip.deliveryOrder.exportOrder.packingItems',
            'exportLoadingSlip.secondWeighbridge',
        ])->where('id', $ticket_id)->get();
        $items = Product::select('id', 'name')->get();

        return view('management.export.delivery-challan.getItem', compact('loading_programs', 'items'));
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
        $companyLocations = [];
        if (!empty($companyLocationIds)) {
            $companyLocations = CompanyLocation::whereIn('id', $companyLocationIds)
                ->get()
                ->map(fn($loc) => ['id' => $loc->id, 'text' => $loc->name])
                ->toArray();
        }

        $arrivalLocations = [];
        $arrivalLocationIds = [];
        if ($ticket->arrival_location_id) {
            $arrivalLocationIds = [$ticket->arrival_location_id];
            $arrivalLoc = $ticket->arrivalLocation;
            if ($arrivalLoc) {
                $arrivalLocations = [['id' => $arrivalLoc->id, 'text' => $arrivalLoc->name]];
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
        if ($ticket->sub_arrival_location_id) {
            $subArrivalLocationIds = [$ticket->sub_arrival_location_id];
            $subArrivalLoc = $ticket->subArrivalLocation;
            if ($subArrivalLoc) {
                $subArrivalLocations = [['id' => $subArrivalLoc->id, 'text' => $subArrivalLoc->name]];
            }
        }

        $loadingSlipLabour = $ticket->exportLoadingSlip?->labour ?? null;

        return response()->json([
            'success' => true,
            'rate' => $labour_rate ? $labour_rate->rate : 'N/A',
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
            'is_labour_editable' => (strtolower($deliveryOrder->sauda_type ?? '') == 'x-mill' || strtolower($deliveryOrder->sauda_type ?? '') == 'xmill'),
        ]);
    }
}
