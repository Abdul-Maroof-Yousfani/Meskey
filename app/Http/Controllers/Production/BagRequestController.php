<?php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Master\ArrivalSubLocation;
use App\Models\Product;
use App\Models\Master\Brands;
use App\Models\UnitOfMeasure;
use App\Models\Production\BagRequest;
use App\Models\Production\BagRequestItem;
use Illuminate\Support\Facades\DB;
use App\Models\Master\CompanyLocation;
use App\Http\Requests\Production\BagRequestRequest;
use App\Models\Production\JobOrder\JobOrder;
use App\Models\Production\JobOrder\JobOrderPackingItem;
use App\Models\Production\JobOrder\JobOrderPackingSubItem;
use App\Models\Master\ArrivalLocation;

use Carbon\Carbon;

class BagRequestController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('management.production.bag_request.index');
    }

    /**
     * Get list of bag requests.
     */
    public function getList(Request $request)
    {
        $requests = BagRequest::with(['gala', 'items.item', 'issuances'])
            ->when($request->search, function ($query) use ($request) {
                $query->where('request_number', 'like', '%' . $request->search . '%')
                    ->orWhereHas('gala', function ($q) use ($request) {
                        $q->where('name', 'like', '%' . $request->search . '%');
                    });
            })
            ->latest()
            ->paginate($request->per_page ?? 25);

        return view('management.production.bag_request.getList', compact('requests'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $data['galas'] = ArrivalSubLocation::where('status', 'active')->get();
        $data['products'] = Product::all();
        $data['brands'] = Brands::where('status', 'active')->get();
        $data['units'] = UnitOfMeasure::all();
        $data['company_locations'] = CompanyLocation::where('status', 'active')->get();
        // Initialize with empty collections, they will be populated via AJAX
        $data['arrival_locations'] = collect();
        $data['galas'] = collect();
        $data['jobOrders'] = collect();
        $data['request_number'] = $this->getNumber(date('Y-m-d'));
        
        return view('management.production.bag_request.create', $data);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(BagRequestRequest $request)
    {
        DB::beginTransaction();
        try {
            $products = \App\Models\Product::whereIn("id", collect($request->items)->pluck("item_id"))->get();

            $bagRequest = BagRequest::create([
                'request_number' => $this->getNumber($request->request_date),
                'request_date' => $request->request_date,
                'arrival_location_id' => $request->arrival_location_id,
                'gala_id' => $request->gala_id,
                'job_order_ids' => $request->job_order_ids,
                'remarks' => $request->remarks,
                'company_id' => $request->company_id,
                'company_location_id' => $request->company_location_id,
                'created_by' => auth()->user()->id,
            ]);

            if ($request->has('items')) {
                // Aggregate quantities by item and brand to check total stock for this request
                $aggregatedItems = collect($request->items)->groupBy(function($item) {
                    return $item['item_id'] . '-' . $item['brand_id'];
                });

                foreach ($aggregatedItems as $key => $group) {
                    $first = $group->first();
                    $totalRequested = $group->sum('quantity');
                    
                    // We are checking that we have stock of the item in the gala
                    $available_stock = getStockByItem($first['item_id'], $first['brand_id'], $request->gala_id);
                    
                    if ($available_stock < $totalRequested) {
                        DB::rollBack();
                        return response()->json([
                            'status' => 'error',
                            'message' => 'Insufficient stock for item ' . ($products->where("id", $first["item_id"])->first())->name . "\n Total Requested: " . $totalRequested . "\n Total Stock Available: " . $available_stock
                        ], 500);
                    }
                }

                foreach ($request->items as $item) {
                    $remainingToDistribute = $item['quantity'];
                    
                    if (!empty($request->job_order_ids)) {
                        // Find all packing items for the selected Job Orders that match this item and brand
                        $matchingPackingItems = JobOrderPackingItem::whereIn('job_order_id', $request->job_order_ids)
                            ->where('bag_product_id', $item['item_id'])
                            ->where('brand_id', $item['brand_id'])
                            ->get();

                        foreach ($matchingPackingItems as $pi) {
                            if ($remainingToDistribute <= 0) break;

                            $consumed = \App\Models\Production\BagRequestItem::where('job_order_id', $pi->job_order_id)
                                ->where('item_id', $pi->bag_product_id)
                                ->sum('quantity');
                            
                            $availableInJO = $pi->total_bags - $consumed;
                            if ($availableInJO <= 0) continue;

                            $take = min($remainingToDistribute, $availableInJO);
                            
                            $bagRequest->items()->create([
                                'job_order_id' => $pi->job_order_id,
                                'item_id' => $item['item_id'],
                                'brand_id' => $item['brand_id'],
                                'unit_id' => $item['unit_id'],
                                'quantity' => $take,
                                'remarks' => $item['remarks'],
                            ]);

                            $remainingToDistribute -= $take;
                        }

                        // Also check sub-items if still remaining
                        if ($remainingToDistribute > 0) {
                            $matchingSubItems = JobOrderPackingSubItem::whereHas('packingItem', function($q) use ($request) {
                                    $q->whereIn('job_order_id', $request->job_order_ids);
                                })
                                ->where('bag_product_id', $item['item_id'])
                                ->where('brand_id', $item['brand_id'])
                                ->get();

                            foreach ($matchingSubItems as $si) {
                                if ($remainingToDistribute <= 0) break;

                                $consumed = \App\Models\Production\BagRequestItem::where('job_order_id', $si->packingItem->job_order_id)
                                    ->where('item_id', $si->bag_product_id)
                                    ->sum('quantity');

                                $availableInJO = $si->total_bags - $consumed;
                                if ($availableInJO <= 0) continue;

                                $take = min($remainingToDistribute, $availableInJO);

                                $bagRequest->items()->create([
                                    'job_order_id' => $si->packingItem->job_order_id,
                                    'item_id' => $item['item_id'],
                                    'brand_id' => $item['brand_id'],
                                    'unit_id' => $item['unit_id'],
                                    'quantity' => $take,
                                    'remarks' => $item['remarks'],
                                ]);

                                $remainingToDistribute -= $take;
                            }
                        }
                    }

                    // If still remaining (or manual add), save without JO or with first one
                    if ($remainingToDistribute > 0) {
                        $bagRequest->items()->create([
                            'job_order_id' => !empty($request->job_order_ids) ? $request->job_order_ids[0] : null,
                            'item_id' => $item['item_id'],
                            'brand_id' => $item['brand_id'],
                            'unit_id' => $item['unit_id'],
                            'quantity' => $remainingToDistribute,
                            'remarks' => $item['remarks'],
                        ]);
                    }
                }
            }

            DB::commit();
            return response()->json([
                'status' => 'success',
                'message' => 'Bag Request stored successfully',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        $bagRequest = BagRequest::with(['items.item', 'items.brand', 'items.unit', 'gala', 'arrivalLocation', 'issuances'])->findOrFail($id);
        
        foreach ($bagRequest->items as $item) {
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
        }

        return view('management.production.bag_request.show', compact('bagRequest'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $bagRequest = BagRequest::with(['items.item', 'items.brand', 'items.unit'])->findOrFail($id);
        
        // Calculate issued quantity for each item
        foreach($bagRequest->items as $item) {
            $item->issued_qty = \App\Models\Procurement\Store\BagIssuanceItem::whereHas('bagIssuance', function($q) use ($id) {
                    $q->where('bag_request_id', $id);
                })
                ->where('item_id', $item->item_id)
                ->where('brand_id', $item->brand_id)
                ->where('job_order_id', $item->job_order_id)
                ->sum('quantity');
            
            // Re-calculate display size
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
        }

        $data['bagRequest'] = $bagRequest;
        $data['galas'] = ArrivalSubLocation::where('status', 'active')->get();
        $data['products'] = Product::all();
        $data['brands'] = Brands::where('status', 'active')->get();
        $data['units'] = UnitOfMeasure::all();
        $data['company_locations'] = CompanyLocation::where('status', 'active')->get();
        
        $data['arrival_locations'] = ArrivalLocation::where('company_location_id', $bagRequest->company_location_id)->where('status', 'active')->get();
        $data['galas'] = ArrivalSubLocation::where('arrival_location_id', $bagRequest->arrival_location_id)->where('status', 'active')->get();
        
        $jobOrders = JobOrder::with(['packingItems', 'packingItems.subItems'])
            ->whereHas('packingItems', function($q) use ($bagRequest) {
                $q->where('company_location_id', $bagRequest->company_location_id);
            })
            ->latest()->get();

        $data['jobOrders'] = $jobOrders->filter(function($jo) use ($id) {
            $totalNeeded = $jo->packingItems->sum('total_bags') + $jo->packingItems->flatMap->subItems->sum('total_bags');
            $requested = \App\Models\Production\BagRequestItem::where('job_order_id', $jo->id)
                ->where('bag_request_id', '!=', $id)
                ->sum('quantity');
            return $totalNeeded > $requested;
        });

        // Add display size for each item
        foreach ($bagRequest->items as $item) {
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
        }

        return view('management.production.bag_request.edit', $data);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(BagRequestRequest $request, $id)
    {
        DB::beginTransaction();
        try {
            $products = \App\Models\Product::whereIn("id", collect($request->items)->pluck("item_id"))->get();

        $bagRequest = BagRequest::findOrFail($id);
        
        // Validation: Ensure we don't reduce quantity below what's already issued
        if ($request->items) {
            foreach($request->items as $itemData) {
                // This is complex because the items are mapped to JOs. 
                // For now, I'll let the user edit but ideally we should validate.
            }
        }

        $bagRequest->update([
                'request_date' => $request->request_date,
                'arrival_location_id' => $request->arrival_location_id,
                'gala_id' => $request->gala_id,
                'job_order_ids' => $request->job_order_ids,
                'remarks' => $request->remarks,
                'company_location_id' => $request->company_location_id,
                'updated_by' => auth()->user()->id,
            ]);

            // Sync items
            $bagRequest->items()->delete();
            if ($request->has('items')) {
                // Aggregate quantities by item and brand to check total stock for this request
                $aggregatedItems = collect($request->items)->groupBy(function($item) {
                    return $item['item_id'] . '-' . $item['brand_id'];
                });

                foreach ($aggregatedItems as $key => $group) {
                    $first = $group->first();
                    $totalRequested = $group->sum('quantity');
                    
                    // We are checking that we have stock of the item in the gala
                    $available_stock = getStockByItem($first['item_id'], $first['brand_id'], $request->gala_id);
                    
                    if ($available_stock < $totalRequested) {
                        DB::rollBack();
                        return response()->json([
                            'status' => 'error',
                            'message' => 'Insufficient stock for item ' . ($products->where("id", $first["item_id"])->first())->name . "\n Total Requested: " . $totalRequested . "\n Total Stock Available: " . $available_stock
                        ], 500);
                    }
                }

                foreach ($request->items as $item) {
                    $remainingToDistribute = $item['quantity'];
                    
                    if (!empty($request->job_order_ids)) {
                        // Find all packing items for the selected Job Orders that match this item and brand
                        $matchingPackingItems = JobOrderPackingItem::whereIn('job_order_id', $request->job_order_ids)
                            ->where('bag_product_id', $item['item_id'])
                            ->where('brand_id', $item['brand_id'])
                            ->get();

                        foreach ($matchingPackingItems as $pi) {
                            if ($remainingToDistribute <= 0) break;

                            $consumed = \App\Models\Production\BagRequestItem::where('job_order_id', $pi->job_order_id)
                                ->where('item_id', $pi->bag_product_id)
                                ->where('bag_request_id', '!=', $id) // Exclude current request
                                ->sum('quantity');
                            
                            $availableInJO = $pi->total_bags - $consumed;
                            if ($availableInJO <= 0) continue;

                            $take = min($remainingToDistribute, $availableInJO);
                            
                            $bagRequest->items()->create([
                                'job_order_id' => $pi->job_order_id,
                                'item_id' => $item['item_id'],
                                'brand_id' => $item['brand_id'],
                                'unit_id' => $item['unit_id'],
                                'quantity' => $take,
                                'remarks' => $item['remarks'],
                            ]);

                            $remainingToDistribute -= $take;
                        }

                        // Also check sub-items if still remaining
                        if ($remainingToDistribute > 0) {
                            $matchingSubItems = JobOrderPackingSubItem::whereHas('packingItem', function($q) use ($request) {
                                    $q->whereIn('job_order_id', $request->job_order_ids);
                                })
                                ->where('bag_product_id', $item['item_id'])
                                ->where('brand_id', $item['brand_id'])
                                ->get();

                            foreach ($matchingSubItems as $si) {
                                if ($remainingToDistribute <= 0) break;

                                $consumed = \App\Models\Production\BagRequestItem::where('job_order_id', $si->packingItem->job_order_id)
                                    ->where('item_id', $si->bag_product_id)
                                    ->where('bag_request_id', '!=', $id)
                                    ->sum('quantity');

                                $availableInJO = $si->total_bags - $consumed;
                                if ($availableInJO <= 0) continue;

                                $take = min($remainingToDistribute, $availableInJO);

                                $bagRequest->items()->create([
                                    'job_order_id' => $si->packingItem->job_order_id,
                                    'item_id' => $item['item_id'],
                                    'brand_id' => $item['brand_id'],
                                    'unit_id' => $item['unit_id'],
                                    'quantity' => $take,
                                    'remarks' => $item['remarks'],
                                ]);

                                $remainingToDistribute -= $take;
                            }
                        }
                    }

                    // If still remaining, save without JO or with first one
                    if ($remainingToDistribute > 0) {
                        $bagRequest->items()->create([
                            'job_order_id' => !empty($request->job_order_ids) ? $request->job_order_ids[0] : null,
                            'item_id' => $item['item_id'],
                            'brand_id' => $item['brand_id'],
                            'unit_id' => $item['unit_id'],
                            'quantity' => $remainingToDistribute,
                            'remarks' => $item['remarks'],
                        ]);
                    }
                }
            }

            DB::commit();
            return response()->json([
                'status' => 'success',
                'message' => 'Bag Request updated successfully',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        try {
            $bagRequest = BagRequest::findOrFail($id);
            if ($bagRequest->issuances()->count() > 0) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot delete a Bag Request that has already been issued.'
                ], 403);
            }
            $bagRequest->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Bag Request deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function getJobOrderItems(Request $request)
    {
        $jobOrderIds = $request->job_order_ids ?? [];
        $companyLocationId = $request->company_location_id;
        $bagRequestId = $request->bag_request_id;

        if (empty($jobOrderIds) || !$companyLocationId) {
            return response()->json([]);
        }

        $items = [];

        // 1. Get all Packing Items for these Job Orders at this Location
        $packingItems = JobOrderPackingItem::whereIn('job_order_id', $jobOrderIds)
            ->where('company_location_id', $companyLocationId)
            ->with(['bagProduct', 'brand', 'bagProduct.unitOfMeasure'])
            ->get();

        foreach ($packingItems as $pi) {
            // "Packing Size (Kg)" from Parent: numeric bag_size input field
            $packingSize = (string)$pi->bag_size;
            $key = ($pi->bag_product_id ?? 0) . '-' . ($pi->brand_id ?? 0) . '-' . $packingSize;
            if (!isset($items[$key])) {
                $items[$key] = [
                    'item_id' => $pi->bag_product_id,
                    'item_name' => $pi->bagProduct->name ?? 'N/A',
                    'brand_id' => $pi->brand_id,
                    'brand_name' => $pi->brand->name ?? 'N/A',
                    'packing_size_kg' => $packingSize,
                    'total_needed' => 0,
                    'unit_id' => $pi->bagProduct->unit_of_measure_id ?? null,
                    'unit_name' => $pi->bagProduct->unitOfMeasure->name ?? '',
                ];
            }
            $items[$key]['total_needed'] += $pi->total_bags;
        }

        // 2. Get all Sub-items (Master Packing) for these Packing Items
        $subItems = JobOrderPackingSubItem::whereIn('job_order_packing_item_id', $packingItems->pluck('id'))
            ->with(['bagProduct', 'brand', 'bagProduct.unitOfMeasure', 'bagSize'])
            ->get();

        foreach ($subItems as $si) {
            // For Sub-items, we use the bagSize record's name/size
            $packingSize = (string)($si->bagSize->size ?? '');
            $key = ($si->bag_product_id ?? 0) . '-' . ($si->brand_id ?? 0) . '-' . $packingSize;
            if (!isset($items[$key])) {
                $items[$key] = [
                    'item_id' => $si->bag_product_id,
                    'item_name' => $si->bagProduct->name ?? 'N/A',
                    'brand_id' => $si->brand_id,
                    'brand_name' => $si->brand->name ?? 'N/A',
                    'packing_size_kg' => $packingSize,
                    'total_needed' => 0,
                    'unit_id' => $si->bagProduct->unit_of_measure_id ?? null,
                    'unit_name' => $si->bagProduct->unitOfMeasure->name ?? '',
                ];
            }
            $items[$key]['total_needed'] += $si->total_bags;
        }

        // 3. Calculate consumed for each item-brand across these JOs and this location
        foreach ($items as $key => &$item) {
            $consumed = \App\Models\Production\BagRequestItem::whereIn('job_order_id', $jobOrderIds)
                ->where('item_id', $item['item_id'])
                ->where('brand_id', $item['brand_id'])
                ->whereHas('bagRequest', function($q) use ($companyLocationId) {
                    $q->where('company_location_id', $companyLocationId);
                })
                ->when($bagRequestId, function($q) use ($bagRequestId) {
                    $q->where('bag_request_id', '!=', $bagRequestId);
                })
                ->sum('quantity');

            $item['quantity'] = max(0, $item['total_needed'] - $consumed);
        }

        // 4. Filter out items with 0 balance
        $finalItems = array_filter($items, function($item) {
            return $item['quantity'] > 0;
        });

        return response()->json(array_values($finalItems));
    }

    public function getArrivalLocations(Request $request)
    {
        $locations = ArrivalLocation::where('company_location_id', $request->company_location_id)
            ->where('status', 'active')
            ->get();
        return response()->json($locations);
    }

    public function getGalas(Request $request)
    {
        $galas = ArrivalSubLocation::where('arrival_location_id', $request->arrival_location_id)
            ->where('status', 'active')
            ->get();
        return response()->json($galas);
    }

    public function getJobOrders(Request $request)
    {
        $companyLocationId = $request->company_location_id;
        $bagRequestId = $request->bag_request_id;

        $jobOrders = JobOrder::with(['packingItems', 'packingItems.subItems'])
            ->whereHas('packingItems', function($q) use ($companyLocationId) {
                $q->where('company_location_id', $companyLocationId);
            })
            ->latest()->get();

        $filtered = $jobOrders->filter(function($jo) use ($companyLocationId, $bagRequestId) {
            // Find total bags needed FOR THIS LOCATION only
            $packingItems = $jo->packingItems->where('company_location_id', $companyLocationId);
            $totalNeeded = $packingItems->sum('total_bags');
            
            $subItems = JobOrderPackingSubItem::whereIn('job_order_packing_item_id', $packingItems->pluck('id'))->get();
            $totalNeeded += $subItems->sum('total_bags');

            // Find total bags requested FOR THIS LOCATION only
            $requested = \App\Models\Production\BagRequestItem::where('job_order_id', $jo->id)
                ->whereHas('bagRequest', function($q) use ($companyLocationId) {
                    $q->where('company_location_id', $companyLocationId);
                })
                ->when($bagRequestId, function($q) use ($bagRequestId) {
                    $q->where('bag_request_id', '!=', $bagRequestId);
                })
                ->sum('quantity');

            return $totalNeeded > $requested;
        });

        return response()->json($filtered->values());
    }

    public function getNumber($date = null)
    {
        $date = $date ?? request('contract_date') ?? request('date');
        $date = $date ? Carbon::parse($date)->format('Y-m-d') : now()->format('Y-m-d');
        $prefix = 'BR-' . Carbon::parse($date)->format('Ymd');

        $latestRequest = BagRequest::where('request_number', 'like', "$prefix-%")
            ->latest('id')
            ->first();

        if ($latestRequest) {
            $parts = explode('-', $latestRequest->request_number);
            $lastNumber = (int) end($parts);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        $request_number = $prefix . '-' . str_pad($newNumber, 3, '0', STR_PAD_LEFT);

        if (request()->ajax() && !request()->isMethod('post')) {
            return response()->json(['success' => true, 'request_number' => $request_number]);
        }

        return $request_number;
    }
}
