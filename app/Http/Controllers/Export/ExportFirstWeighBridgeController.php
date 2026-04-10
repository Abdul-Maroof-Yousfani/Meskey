<?php

namespace App\Http\Controllers\Export;

use App\Http\Controllers\Controller;
use App\Models\Export\ExportFirstWeighbridge;
use App\Models\Master\ArrivalTruckType;
use App\Models\Master\WeighbridgeAmount;
use App\Models\Sales\LoadingProgramItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ExportFirstWeighBridgeController extends Controller
{
    public function index()
    {
        return view('management.export.first-weighbridge.index');
    }

    public function getList(Request $request)
    {
        $FirstWeighbridges = ExportFirstWeighbridge::with([
            'loadingProgramItem.deliveryOrders.customer',
            'loadingProgramItem.deliveryOrders.exportOrder.product',
            'loadingProgramItem.exportOrders.product',
            'loadingProgramItem',
        ])
            ->whereHas('loadingProgramItem.loadingProgram', function ($query) {
                $query->where('type', 'export_order');
            })
            ->when($request->filled('search'), function ($q) use ($request) {
                $searchTerm = '%' . $request->search . '%';
                return $q->where(function ($sq) use ($searchTerm) {
                    $sq->whereHas('loadingProgramItem', function ($query) use ($searchTerm) {
                        $query->where('transaction_number', 'like', $searchTerm)
                            ->orWhere('truck_number', 'like', $searchTerm);
                    })->orWhereHas('loadingProgramItem.deliveryOrders', function ($query) use ($searchTerm) {
                        $query->where('reference_no', 'like', $searchTerm);
                    })->orWhereHas('loadingProgramItem.exportOrders', function ($query) use ($searchTerm) {
                        $query->where('voucher_no', 'like', $searchTerm)
                            ->orWhere('contract_no', 'like', $searchTerm);
                    });
                });
            })
            ->latest()
            ->paginate($request->get('per_page', 25));

        return view('management.export.first-weighbridge.getList', compact('FirstWeighbridges'));
    }

    public function create()
    {
        $data = [
            'ArrivalTruckTypes' => ArrivalTruckType::where('status', 'active')->get(),
            'Tickets' => LoadingProgramItem::whereHas('loadingProgram', function ($query) {
                $query->where('type', 'export_order');
            })
                ->whereDoesntHave('firstWeighbridge')
                ->with(['deliveryOrders.customer', 'deliveryOrders.exportOrder.product', 'exportOrders.product'])
                ->get(),
        ];

        return view('management.export.first-weighbridge.create', $data);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'loading_program_item_id' => 'required|exists:loading_program_items,id',
            'first_weight' => 'required|numeric',
            'truck_type_id' => 'required|exists:arrival_truck_types,id',
            'remark' => 'nullable|string',
            'weighbridge_amount' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $loadingProgramItem = LoadingProgramItem::whereHas('loadingProgram', function ($query) {
            $query->where('type', 'export_order');
        })->findOrFail($request->loading_program_item_id);

        $existingFirstWeighbridge = ExportFirstWeighbridge::where('loading_program_item_id', $request->loading_program_item_id)->first();
        if ($existingFirstWeighbridge) {
            return response()->json(['errors' => ['loading_program_item_id' => 'This ticket already has a first weighbridge.']], 422);
        }

        $loadingProgramItem->load(['deliveryOrders', 'loadingProgram']);
        $deliveryOrders = $loadingProgramItem->deliveryOrders;

        $request['created_by'] = auth()->user()->id;
        $request['company_id'] = $request->company_id;

        $companyLocationId = null;

        if ($deliveryOrders->isNotEmpty()) {
            $companyLocationId = $deliveryOrders->first()->location_id;
            $request['delivery_order_id'] = $deliveryOrders->first()->id;
        } else {
            $companyLocationIds = $loadingProgramItem->loadingProgram->company_locations ?? [];
            $companyLocationId = is_array($companyLocationIds) ? ($companyLocationIds[0] ?? null) : $companyLocationIds;
            $request['delivery_order_id'] = null;
        }

        if ($companyLocationId) {
            $weighbridgeAmount = WeighbridgeAmount::where('truck_type_id', $request->truck_type_id)
                ->where('company_location_id', $companyLocationId)
                ->first();

            if (!$weighbridgeAmount) {
                return response()->json(['errors' => ['truck_type_id' => 'Weighbridge amount not found for selected truck type and arrival location.']], 422);
            }

            $request['weighbridge_amount'] = $weighbridgeAmount->weighbridge_amount;
        } else {
            return response()->json(['errors' => ['truck_type_id' => 'Company location not found to fetch weighbridge amount.']], 422);
        }

        $firstWeighbridge = ExportFirstWeighbridge::create($request->all());

        return response()->json(['success' => 'Export First Weighbridge created successfully.', 'data' => $firstWeighbridge], 201);
    }

    public function edit($id)
    {
        $data['FirstWeighbridge'] = ExportFirstWeighbridge::with([
            'loadingProgramItem.deliveryOrders.customer',
            'loadingProgramItem.deliveryOrders.exportOrder.product',
            'loadingProgramItem.deliveryOrders.arrivalLocation',
            'loadingProgramItem.deliveryOrders.subArrivalLocation',
            'loadingProgramItem.exportOrders.product',
        ])->findOrFail($id);

        $data['ArrivalTruckTypes'] = ArrivalTruckType::where('status', 'active')->get();
        $data['DeliveryOrders'] = $data['FirstWeighbridge']->loadingProgramItem->deliveryOrders;
        $data['ExportOrders'] = $data['FirstWeighbridge']->loadingProgramItem->exportOrders;

        return view('management.export.first-weighbridge.edit', $data);
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'loading_program_item_id' => 'required|exists:loading_program_items,id',
            'first_weight' => 'required|numeric',
            'truck_type_id' => 'required|exists:arrival_truck_types,id',
            'remark' => 'nullable|string',
            'weighbridge_amount' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $loadingProgramItem = LoadingProgramItem::whereHas('loadingProgram', function ($query) {
            $query->where('type', 'export_order');
        })->findOrFail($request->loading_program_item_id);

        $existingFirstWeighbridge = ExportFirstWeighbridge::where('loading_program_item_id', $request->loading_program_item_id)
            ->where('id', '!=', $id)
            ->first();
        if ($existingFirstWeighbridge) {
            return response()->json(['errors' => ['loading_program_item_id' => 'This ticket already has a first weighbridge.']], 422);
        }

        $firstWeighbridge = ExportFirstWeighbridge::findOrFail($id);
        $loadingProgramItem->load(['deliveryOrders', 'loadingProgram']);
        $deliveryOrders = $loadingProgramItem->deliveryOrders;
        $request['company_id'] = $request->company_id;

        $companyLocationId = null;

        if ($deliveryOrders->isNotEmpty()) {
            $companyLocationId = $deliveryOrders->first()->location_id;
            $request['delivery_order_id'] = $deliveryOrders->first()->id;
        } else {
            $companyLocationIds = $loadingProgramItem->loadingProgram->company_locations ?? [];
            $companyLocationId = is_array($companyLocationIds) ? ($companyLocationIds[0] ?? null) : $companyLocationIds;
            $request['delivery_order_id'] = null;
        }

        if ($companyLocationId) {
            $weighbridgeAmount = WeighbridgeAmount::where('truck_type_id', $request->truck_type_id)
                ->where('company_location_id', $companyLocationId)
                ->first();

            if (!$weighbridgeAmount) {
                return response()->json(['errors' => ['truck_type_id' => 'Weighbridge amount not found for selected truck type and arrival location.']], 422);
            }

            $request['weighbridge_amount'] = $weighbridgeAmount->weighbridge_amount;
        } else {
            return response()->json(['errors' => ['truck_type_id' => 'Company location not found to fetch weighbridge amount.']], 422);
        }

        $firstWeighbridge->update($request->all());

        return response()->json(['success' => 'Export First Weighbridge updated successfully.', 'data' => $firstWeighbridge], 200);
    }

    public function destroy($id)
    {
        $firstWeighbridge = ExportFirstWeighbridge::findOrFail($id);
        $firstWeighbridge->delete();

        return response()->json(['success' => 'Export First Weighbridge deleted successfully.'], 200);
    }

    public function getFirstWeighbridgeRelatedData(Request $request)
    {
        $LoadingProgramItem = LoadingProgramItem::with([
            'deliveryOrders.customer',
            'deliveryOrders.exportOrder.product',
            'deliveryOrders.exportPackingItems',
            'deliveryOrders.arrivalLocation',
            'deliveryOrders.subArrivalLocation',
            'exportOrders.product',
        ])
            ->whereHas('loadingProgram', function ($query) {
                $query->where('type', 'export_order');
            })
            ->findOrFail($request->loading_program_item_id);

        $DeliveryOrders = $LoadingProgramItem->deliveryOrders;
        $ExportOrders = $LoadingProgramItem->exportOrders;
        $ArrivalTruckTypes = ArrivalTruckType::where('status', 'active')->get();

        $html = view('management.export.first-weighbridge.getFirstWeighbridgeRelatedData', compact('DeliveryOrders', 'ExportOrders', 'ArrivalTruckTypes', 'LoadingProgramItem'))
            ->with('FirstWeighbridge', null)
            ->render();

        return response()->json(['success' => true, 'html' => $html]);
    }

    public function getWeighbridgeAmount(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'truck_type_id' => 'required|exists:arrival_truck_types,id',
            'loading_program_item_id' => 'required|exists:loading_program_items,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $loadingProgramItem = LoadingProgramItem::whereHas('loadingProgram', function ($query) {
            $query->where('type', 'export_order');
        })
            ->with(['deliveryOrders', 'loadingProgram'])
            ->findOrFail($request->loading_program_item_id);

        $deliveryOrders = $loadingProgramItem->deliveryOrders;
        $companyLocationId = null;

        if ($deliveryOrders->isNotEmpty()) {
            $companyLocationId = $deliveryOrders->first()->location_id;
        } else {
            $companyLocationIds = $loadingProgramItem->loadingProgram->company_locations ?? [];
            $companyLocationId = is_array($companyLocationIds) ? ($companyLocationIds[0] ?? null) : $companyLocationIds;
        }

        if (!$companyLocationId) {
            return response()->json([
                'success' => false,
                'message' => 'Company location not found to fetch weighbridge amount.',
            ]);
        }

        $weighbridgeAmount = WeighbridgeAmount::where('truck_type_id', $request->truck_type_id)
            ->where('company_location_id', $companyLocationId)
            ->first();

        if ($weighbridgeAmount) {
            return response()->json([
                'success' => true,
                'weighbridge_amount' => $weighbridgeAmount->weighbridge_amount,
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Weighbridge amount not found for selected truck type and arrival location.',
        ]);
    }
}
