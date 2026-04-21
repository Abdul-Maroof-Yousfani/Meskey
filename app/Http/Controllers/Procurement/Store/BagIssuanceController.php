<?php

namespace App\Http\Controllers\Procurement\Store;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Procurement\Store\BagIssuance;
use App\Models\Procurement\Store\BagIssuanceItem;
use App\Models\Production\BagRequest;
use App\Models\Master\Account\Stock;
use Illuminate\Support\Facades\DB;

use Carbon\Carbon;

class BagIssuanceController extends Controller
{
    public function index()
    {
        $issuances = BagIssuance::with(['bagRequest', 'gala'])->latest()->paginate(25);
        return view('management.procurement.store.bag_issuance.index', compact('issuances'));
    }

    public function getList(Request $request)
    {
        $query = BagIssuance::with(['bagRequest', 'gala', 'items.item', 'items.unit']);
        
        if ($request->search) {
            $query->where('issuance_number', 'like', '%' . $request->search . '%')
                  ->orWhereHas('bagRequest', function($q) use ($request) {
                      $q->where('request_number', 'like', '%' . $request->search . '%');
                  });
        }

        $issuances = $query->latest()->paginate($request->per_page ?? 25);
        return view('management.procurement.store.bag_issuance.getList', compact('issuances'));
    }

    public function create()
    {
        $data['bag_requests'] = \App\Models\Production\BagRequest::with('items')->latest()->get()->filter(function($br) {
            foreach($br->items as $item) {
                $issued = BagIssuanceItem::whereHas('bagIssuance', function($q) use ($br) {
                        $q->where('bag_request_id', $br->id);
                    })
                    ->where('item_id', $item->item_id)
                    ->where('brand_id', $item->brand_id)
                    ->where('job_order_id', $item->job_order_id)
                    ->sum('quantity');
                
                if ((float)$item->quantity - (float)$issued > 0) return true;
            }
            return false;
        });
        $data['company_locations'] = \App\Models\Master\CompanyLocation::where('status', 'active')->get();
        $data['arrival_locations'] = \App\Models\Master\ArrivalLocation::all();
        $data['galas'] = \App\Models\Master\ArrivalSubLocation::all();
        $data['jobOrders'] = \App\Models\Production\JobOrder\JobOrder::all();
        $data['products'] = \App\Models\Product::all();
        $data['brands'] = \App\Models\Master\Brands::all();
        $data['units'] = \App\Models\UnitOfMeasure::all();
        $data['issuance_number'] = '';
        
        return view('management.procurement.store.bag_issuance.create', $data);
    }

    public function getBagRequestDetails($id)
    {
        $request = BagRequest::with(['items.item', 'items.brand', 'items.unit', 'gala', 'arrivalLocation'])->findOrFail($id);
        
        foreach($request->items as $item) {
            $packing = \App\Models\Production\JobOrder\JobOrderPackingItem::where('job_order_id', $item->job_order_id)
                ->where('bag_product_id', $item->item_id)
                ->where('brand_id', $item->brand_id)
                ->first();
            $item->display_size = $packing ? (string)$packing->bag_size : '';
            if (!$item->display_size) {
                $sub = \App\Models\Production\JobOrder\JobOrderPackingSubItem::whereHas('packingItem', function($q) use ($item) {
                    $q->where('job_order_id', $item->job_order_id);
                })->where('bag_product_id', $item->item_id)
                  ->where('brand_id', $item->brand_id)
                  ->with('bagSize')->first();
                $item->display_size = $sub ? (string)($sub->bagSize->size ?? '') : '';
            }

            // Balancing Logic: Calculate total already issued for this request item
            $issued = BagIssuanceItem::whereHas('bagIssuance', function($q) use ($id) {
                    $q->where('bag_request_id', $id);
                })
                ->where('item_id', $item->item_id)
                ->where('brand_id', $item->brand_id)
                ->where('job_order_id', $item->job_order_id)
                ->sum('quantity');

            $item->balance_qty = (float)$item->quantity - (float)$issued;
        }

        // Filter out items that are fully issued
        $request->setRelation('items', $request->items->filter(function($item) {
            return $item->balance_qty > 0;
        })->values());

        return response()->json($request);
    }

    public function store(Request $request)
    {
        DB::beginTransaction();
        try {
            // Lock the bag request to prevent concurrent issuances
            $bagRequest = BagRequest::where('id', $request->bag_request_id)->lockForUpdate()->firstOrFail();

            $issuance = BagIssuance::create([
                'issuance_number' => $this->getNumber($request->issuance_date),
                'issuance_date' => $request->issuance_date,
                'bag_request_id' => $request->bag_request_id,
                'arrival_location_id' => $request->arrival_location_id,
                'gala_id' => $request->gala_id,
                'job_order_ids' => $request->job_order_ids,
                'remarks' => $request->remarks,
                'company_id' => $request->company_id,
                'company_location_id' => $request->company_location_id,
                'created_by' => auth()->user()->id,
            ]);

            if ($request->items && is_array($request->items)) {
                foreach ($request->items as $itemData) {
                    if (isset($itemData['quantity']) && $itemData['quantity'] > 0) {
                        
                        // Server-side balance validation
                        $itemRequest = \App\Models\Production\BagRequestItem::where('bag_request_id', $bagRequest->id)
                            ->where('item_id', $itemData['item_id'])
                            ->where('brand_id', $itemData['brand_id'])
                            ->where('job_order_id', $itemData['job_order_id'] ?? null)
                            ->first();
                        
                        if (!$itemRequest) {
                            throw new \Exception("Item not found in the associated bag request.");
                        }

                        $alreadyIssued = BagIssuanceItem::whereHas('bagIssuance', function($q) use ($bagRequest) {
                                $q->where('bag_request_id', $bagRequest->id);
                            })
                            ->where('item_id', $itemData['item_id'])
                            ->where('brand_id', $itemData['brand_id'])
                            ->where('job_order_id', $itemData['job_order_id'] ?? null)
                            ->sum('quantity');
                        
                        $remaining = (float)$itemRequest->quantity - (float)$alreadyIssued;

                        if ((float)$itemData['quantity'] > $remaining) {
                            throw new \Exception("Quantity exceeds the remaining balance for item: " . ($itemRequest->item->name ?? 'Unknown'));
                        }

                        $item = BagIssuanceItem::create([
                            'bag_issuance_id' => $issuance->id,
                            'job_order_id' => $itemData['job_order_id'] ?? null,
                            'item_id' => $itemData['item_id'],
                            'brand_id' => $itemData['brand_id'],
                            'unit_id' => $itemData['unit_id'],
                            'quantity' => $itemData['quantity'],
                        ]);

                        // Stock Out
                        Stock::create([
                            'product_id' => $itemData['item_id'],
                            'brand_id' => $itemData['brand_id'],
                            'voucher_type' => 'bag_issuance',
                            'voucher_no' => $issuance->issuance_number,
                            'qty' => $itemData['quantity'],
                            'type' => 'stock-out',
                            'company_location_id' => $request->company_location_id,
                            'arrival_id' => $request->arrival_location_id,
                            'subarrival_id' => $request->gala_id,
                            'parentable_id' => $item->id,
                            'parentable_type' => BagIssuanceItem::class,
                        ]);
                    }
                }
            }

            DB::commit();
            return response()->json(['success' => 'Bag Issuance created successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()]);
        }
    }

    public function getNumber($date = null)
    {
        $date = $date ?? request('contract_date') ?? request('date');
        $date = $date ? Carbon::parse($date)->format('Y-m-d') : now()->format('Y-m-d');
        $prefix = 'BI-' . $date;

        $latestIssuance = BagIssuance::where('issuance_number', 'like', "$prefix-%")
            ->latest('id')
            ->first();

        if ($latestIssuance) {
            $parts = explode('-', $latestIssuance->issuance_number);
            $lastNumber = (int) end($parts);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        $issuance_number = $prefix . '-' . str_pad($newNumber, 3, '0', STR_PAD_LEFT);

        if (request()->ajax() && !request()->isMethod('post')) {
            return response()->json(['success' => true, 'issuance_number' => $issuance_number]);
        }

        return $issuance_number;
    }

    public function show($id)
    {
        $issuance = BagIssuance::with(['items.item', 'items.brand', 'items.unit', 'bagRequest', 'gala', 'arrivalLocation'])->findOrFail($id);
        return view('management.procurement.store.bag_issuance.show', compact('issuance'));
    }

    public function update(Request $request, $id)
    {
        // Placeholder
    }

    public function destroy($id)
    {
        // Placeholder
    }
}
