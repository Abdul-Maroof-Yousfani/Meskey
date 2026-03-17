<?php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use App\Http\Requests\Production\ProductionVoucherRequest;
use App\Models\Master\ArrivalLocation;
use App\Models\Production\JobOrder\JobOrder;
use App\Models\Production\JobOrder\JobOrderPackingItem;
use App\Models\Production\JobOrder\JobOrderRawMaterialQc;
use App\Models\Production\ProductionVoucher;
use App\Models\Production\ProductionInput;
use App\Models\Production\ProductionOutput;
use App\Models\Production\ProductionSlot;
use App\Models\Production\ProductionSlotBreak;
use App\Models\Product;
use App\Models\Master\CompanyLocation;
use App\Models\Master\ArrivalSubLocation;
use App\Models\Master\Brands;
use App\Models\Master\Plant;
use App\Models\Master\ProductionMachine;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProductionVoucherController extends Controller
{
    public function index()
    {
        return view('management.production.production_voucher.index');
    }

    public function getByProductTable(Request $request)
    {
        $byProductOutputs = [];
        $byProductId = $request->by_product_id;




        $Product = Product::where(function ($query) use ($request) {
            $query->where('id', $request->by_product_id)
                ->orWhere('parent_id', $request->by_product_id);
        })
            // ->where('is_by_product', 'yes');
            ->where('product_category_flags', 'by');


        $productIds = $Product->pluck('id')->toArray();
        if ($request->job_order_ids && count($request->job_order_ids) != 0) {
            $jobOrderPackings = JobOrderPackingItem::with([
                'jobOrder:id,job_order_no,product_id', // product_id zaroor add karein
                'jobOrder.product:id,name'

            ])

                ->whereIn('job_order_id', $request->job_order_ids)
                ->whereHas('jobOrder', function ($query) use ($productIds) {
                    $query->whereIn('product_id', $productIds);
                });
        } else {
            $jobOrderPackings = collect();
        }



        // $byProduct = $byProductId ? Product::find($byProductId) : null;
        $byProduct = $Product;
        $locationId = $request->location_id;
        $jobOrderIds = $request->job_order_ids ?? [];
        $headProductId = $request->head_product_id;
        if ($request->filled('production_voucher_id')) {
            $productionVoucherId = $request->production_voucher_id;
            $productionVoucher = ProductionVoucher::find($productionVoucherId);
            $byProductOutputs = $productionVoucher->outputs;
        }

        // Filter products based on parent_id logic
        // $productsQuery = Product::where('status', 1);

        // if ($byProductId) {
        //     if ($byProduct->parent_id) {
        //         // Head product has a parent - show all products with same parent_id (including head product if it's a child)
        //         $productsQuery->where(function ($q) use ($byProduct) {
        //             $q->where('parent_id', $byProduct->parent_id)
        //                 ->orWhere('id', $byProduct->parent_id); // Include parent itself
        //         });
        //     } else {
        //         // Head product is itself a parent (parent_id is null) - show all its children + itself
        //         $productsQuery->where(function ($q) use ($byProduct) {
        //             $q->where('parent_id', $byProduct->id)
        //                 ->orWhere('id', $byProduct->id); // Include head product itself
        //         });
        //     }
        // }
        // if ($headProductId) {
        //     $productsQuery->where('id', '!=', $headProductId);
        // }
        $byProducts = $byProduct->orderBy('name')->get();
        // dd($byProducts);
        $arrivalSubLocations = ArrivalSubLocation::where('arrival_location_id', $locationId)
            ->where('company_id', $request->company_id)->where('status', 'active')->get();

        $brands = Brands::where('company_id', $request->company_id)->get();
        $jobOrders = JobOrder::where('company_id', $request->company_id)->whereIn('id', $jobOrderIds)->get();
        // dd('ssss');
        return view('management.production.production_voucher.partials.by_product_table', compact(
            'byProducts',
            'arrivalSubLocations',
            'brands',
            'jobOrders',
            'byProductOutputs',
            'jobOrderPackings'
        ));
    }
    public function getHeadProductsDatdddda(Request $request)
    {


        $productId = $request->product_id;
        $locationId = $request->location_id;
        $jobOrderIds = $request->job_order_ids ?? [];
        $productIds = Product::where('id', $request->product_id)->orWhere('parent_id', $request->product_id)
            ->whereIn('product_category_flags', ['head', 'b2'])
            ->pluck('id')->toArray();

        $headProduct = Product::where('status', 1)->where('id', $productId)->first();

        // dd($request);
        if ($request->has('job_order_ids') && count($request->job_order_ids) != 0) {
            $jobOrderPackings = JobOrderPackingItem::with([
                'jobOrder:id,job_order_no,product_id', // product_id zaroor add karein
                'jobOrder.product:id,name'

            ])

                ->whereIn('job_order_id', $request->job_order_ids)
                ->whereHas('jobOrder', function ($query) use ($productIds) {
                    $query->whereIn('product_id', $productIds);
                })
                ->get();
        } else {
            $jobOrderPackings = collect();
        }


        // $Otherproduct
        // dd($jobOrderPackings);

        $headProductOutputs = [];
        if ($request->filled('production_voucher_id')) {
            $productionVoucherId = $request->production_voucher_id;
            $productionVoucher = ProductionVoucher::find($productionVoucherId);
            $headProductOutputs = $productionVoucher->outputs->where('product_id', $productionVoucher->product_id);
        }
        // dd($byProductOutputs);
        $arrivalSubLocations = ArrivalSubLocation::where('arrival_location_id', $locationId)
            ->where('company_id', $request->company_id)->where('status', 'active')->get();

        $brands = Brands::where('company_id', $request->company_id)->get();
        $jobOrders = JobOrder::where('company_id', $request->company_id)->whereIn('id', $jobOrderIds)->get();
        // dd('ssss');
        return view('management.production.production_voucher.partials.head_products_table', compact(
            'headProduct',
            'arrivalSubLocations',
            'brands',
            'jobOrders',
            'headProductOutputs',
            'jobOrderPackings',
        ));
    }

    public function getHeadProductsData(Request $request)
    {
        // Optional validation
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'location_id' => 'required|exists:arrival_locations,id',
            'company_id' => 'required|exists:companies,id',
        ]);

        $productId = $request->product_id;
        $locationId = $request->location_id;
        $jobOrderIds = $request->job_order_ids ?? [];

        $product = Product::find($request->product_id);

        if (!$product) {
            return [];
        }

        $parentId = $product->parent_id ? $product->parent_id : $product->id;


        // Get all related product IDs (head and b2)
        $productIdddds = Product::where('id', $request->product_id)
            ->orWhere('parent_id', $request->product_id)
            ->whereIn('product_category_flags', ['head', 'b2'])
            ->pluck('id')
            ->toArray();



        $productIds = Product::where(function ($q) use ($parentId) {
            $q->where('id', $parentId)         // parent
                ->orWhere('parent_id', $parentId); // children
        })
            ->whereIn('product_category_flags', ['head', 'b2'])
            ->pluck('id')
            ->toArray();
        // dd($productIds);
        $headProduct = Product::where('status', 1)->where('id', $productId)->first();

        // Initialize collections
        $packingItemsProducts = collect();
        $nonPackingItemsProducts = collect();
        $jobOrderPackings = collect();

        if ($request->has('job_order_ids') && count($request->job_order_ids) > 0) {
            $jobOrderPackings = JobOrderPackingItem::with([
                'jobOrder:id,job_order_no,product_id',
                'jobOrder.product:id,name'  // product relation jobOrder ke through hai
            ])
                ->whereIn('job_order_id', $request->job_order_ids)
                ->whereHas('jobOrder', function ($query) use ($productIds) {
                    $query->whereIn('product_id', $productIds);
                })
                ->get();

            // Packing items wale products - jobOrder ke through product nikalenge
            $packingItemsProducts = $jobOrderPackings->map(function ($packingItem) {
                // Product jobOrder ke through available hai
                if ($packingItem->jobOrder && $packingItem->jobOrder->product) {
                    return $packingItem->jobOrder->product;
                }
                return null;
            })->filter()->unique('id');

            // Debug: Check packing items products count
            // \Log::info('Packing Items Products Count: ' . $packingItemsProducts->count());

            // Non-packing items wale products
            $productIdsWithPackingItems = $packingItemsProducts->pluck('id')->toArray();

            $nonPackingItemsProducts = Product::whereIn('id', $productIds)
                ->whereNotIn('id', $productIdsWithPackingItems)
                ->where('status', 1)
                ->get();
        }

        $headProductOutputs = [];
        if ($request->filled('production_voucher_id')) {
            $productionVoucher = ProductionVoucher::with('outputs')->find($request->production_voucher_id);
            if ($productionVoucher) {
                $headProductOutputs = $productionVoucher->outputs->where('product_id', $productionVoucher->product_id);
            }
        }

        $arrivalSubLocations = ArrivalSubLocation::where('arrival_location_id', $locationId)
            ->where('company_id', $request->company_id)
            ->where('status', 'active')
            ->get();

        $brands = Brands::where('company_id', $request->company_id)->get();

        $jobOrders = collect();
        if (!empty($jobOrderIds)) {
            $jobOrders = JobOrder::where('company_id', $request->company_id)
                ->whereIn('id', $jobOrderIds)
                ->get();
        }

        return view('management.production.production_voucher.partials.head_products_table', compact(
            'headProduct',
            'arrivalSubLocations',
            'brands',
            'jobOrders',
            'headProductOutputs',
            'jobOrderPackings',
            'packingItemsProducts',
            'nonPackingItemsProducts'
        ));
    }


    public function getList(Request $request)
    {
        $query = ProductionVoucher::with([
            'jobOrder',
            'jobOrders.packingItems.companyLocation',
            'jobOrders.product',
            'location',
            'supervisor',
            'outputs.jobOrder'
        ]);

        // Apply search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('prod_no', 'like', '%' . $search . '%')
                    ->orWhereHas('jobOrder', function ($q) use ($search) {
                        $q->where('job_order_no', 'like', '%' . $search . '%')
                            ->orWhere('ref_no', 'like', '%' . $search . '%');
                    })
                    ->orWhereHas('location', function ($q) use ($search) {
                        $q->where('name', 'like', '%' . $search . '%');
                    })
                    ->orWhereHas('supervisor', function ($q) use ($search) {
                        $q->where('name', 'like', '%' . $search . '%');
                    });
            });
        }

        // Filter by job order (check both old job_order_id and pivot table)
        if ($request->filled('job_order_id')) {
            $query->where(function ($q) use ($request) {
                $q->where('job_order_id', $request->job_order_id)
                    ->orWhereHas('jobOrders', function ($q) use ($request) {
                        $q->where('job_orders.id', $request->job_order_id);
                    });
            });
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('prod_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('prod_date', '<=', $request->date_to);
        }

        // Filter by location
        if ($request->filled('location_id')) {
            $query->where('location_id', $request->location_id);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Apply sorting
        $sortField = $request->get('sort', 'prod_date');
        $sortDirection = $request->get('direction', 'desc');

        if (in_array($sortField, ['prod_no', 'prod_date', 'status'])) {
            $query->orderBy($sortField, $sortDirection);
        } else {
            $query->orderBy('prod_date', 'desc')->orderBy('created_at', 'desc');
        }

        // Paginate results
        $productionVouchers = $query->paginate(request('per_page', 25));

        // Calculate job order-wise allocated and produced quantities for each voucher
        foreach ($productionVouchers as $voucher) {
            $jobOrderData = [];
            $voucher->producedByJobOrder = []; // Initialize as empty array

            // Get all job orders for this voucher
            $jobOrders = $voucher->jobOrders->count() > 0 ? $voucher->jobOrders : collect([$voucher->jobOrder])->filter();

            // Get allocated quantities from packing items (only for this voucher's location)
            foreach ($jobOrders as $jobOrder) {
                if (!$jobOrder)
                    continue;

                $allocatedQty = 0;
                // Only count packing items for this voucher's location
                foreach ($jobOrder->packingItems as $packingItem) {
                    if ($packingItem->company_location_id == $voucher->location_id) {
                        $allocatedQty += $packingItem->total_kgs ?? 0;
                    }
                }

                $jobOrderData[$jobOrder->id] = [
                    'job_order_no' => $jobOrder->job_order_no,
                    'job_order_ref_no' => $jobOrder->ref_no ?? null,
                    'allocated_qty' => $allocatedQty,
                    'produced_qty' => 0
                ];
            }

            // Get produced quantities from production outputs (grouped by job order)
            foreach ($voucher->outputs as $output) {
                if ($output->job_order_id && isset($jobOrderData[$output->job_order_id])) {
                    $jobOrderData[$output->job_order_id]['produced_qty'] += $output->qty ?? 0;
                }
            }

            // Calculate remaining for each job order
            foreach ($jobOrderData as &$data) {
                $data['remaining_qty'] = $data['allocated_qty'] - $data['produced_qty'];
            }

            $voucher->producedByJobOrder = $jobOrderData;
        }

        // Get job orders for filter dropdown
        $jobOrders = JobOrder::where('status', 1)
            ->orderBy('job_order_no', 'desc')
            ->get();

        // Return view with data
        return view('management.production.production_voucher.getList', compact(
            'productionVouchers',
            'jobOrders'
        ));
    }

    public function create()
    {
        $companyLocations = CompanyLocation::where('status', 'active')->get();
        $products = Product::where('status', 1)->get();
        $byheadsproducts = Product::where('status', 1)->where('parent_id', null)->get();
        $sublocations = ArrivalSubLocation::with('arrivalLocation')->get();

        return view('management.production.production_voucher.create', compact(
            'companyLocations',
            'products',
            'byheadsproducts',
            'sublocations'
        ));
    }

    public function getHeadProducts()
    {
        // dd('ddd');
        $headProducts = Product::where('status', 1)->where('parent_id', null)->get();
        return view('management.production.production_voucher.partials.head_products_table', compact(
            'headProducts'
        ));
    }

    public function getJobOrdersByLocation(Request $request)
    {
        // dd($request);

        $productIds = Product::where('id', $request->product_id)->orWhere('parent_id', $request->product_id)->pluck('id')->toArray();

        $locationId = $request->location_id;
        $productId = $request->product_id; // Optional commodity filter

        if (!$locationId) {
            return response()->json(['jobOrders' => []]);
        }

        $user = auth()->user();

        $jobOrders = JobOrder::with('product')
            ->where('status', 1)
            ->whereHas('packingItems', function ($q) use ($locationId) {
                $q->where('company_location_id', $locationId);
            })
            ->when($productId, function ($query) use ($productId, $productIds) {
                return $query->whereIn('product_id', $productIds);
            })
            ->when($user->user_type !== 'super-admin', function ($query) use ($user) {
                return $query->whereHas('packingItems', function ($q) use ($user) {
                    $q->where('company_location_id', $user->company_location_id);
                });
            })
            ->get()
            ->map(function ($jobOrder) {
                return [
                    'id' => $jobOrder->id,
                    'job_order_no' => $jobOrder->job_order_no,
                    'ref_no' => $jobOrder->ref_no,
                    'product_name' => $jobOrder->product->name ?? 'N/A',
                    'product_id' => $jobOrder->product_id
                ];
            });

        return response()->json(['jobOrders' => $jobOrders]);
    }

    public function getPackingItemsByJobOrder(Request $request)
    {
        $jobOrderIds = $request->job_order_ids; // Array of job order IDs
        $locationId = $request->location_id;

        if (!$jobOrderIds || !$locationId) {
            return response()->json(['packingItems' => []]);
        }

        $packingItems = \App\Models\Production\JobOrder\JobOrderPackingItem::with([
            'jobOrder',
            'bagProduct',
            'bagCondition',
            'companyLocation',
            'brand',
            'jobOrder.product'
        ])
            ->whereIn('job_order_id', is_array($jobOrderIds) ? $jobOrderIds : [$jobOrderIds])
            ->where('company_location_id', $locationId)
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'job_order_no' => $item->jobOrder->job_order_no ?? 'N/A',
                    'product_name' => $item->jobOrder->product->name ?? 'N/A',
                    'bag_type' => $item->bagType->name ?? 'N/A',
                    'bag_condition' => $item->bagCondition->name ?? 'N/A',
                    'bag_size' => $item->bag_size,
                    'no_of_bags' => $item->no_of_bags,
                    'total_bags' => $item->total_bags,
                    'total_kgs' => $item->total_kgs,
                    'metric_tons' => $item->metric_tons,
                    'brand' => $item->brand->name ?? 'N/A',
                    'delivery_date' => $item->delivery_date ? $item->delivery_date->format('Y-m-d') : null,
                ];
            });

        return response()->json(['packingItems' => $packingItems]);
    }

    public function getPackingItemsWithProduced(Request $request)
    {
        $jobOrderIds = $request->job_order_ids; // Array of job order IDs
        $locationId = $request->location_id;
        $currentProductionVoucherId = $request->current_production_voucher_id ?? null;

        if (!$jobOrderIds || !$locationId) {
            return view('management.production.production_voucher.partials.packing_items_table', [
                'packingItems' => [],
                'producedByJobOrder' => [],
                'producedDetailsByJobOrder' => [],
                'locationId' => $locationId ?? null,
                'currentProductionVoucherId' => $currentProductionVoucherId
            ]);
        }

        // Get packing items
        $packingItems = \App\Models\Production\JobOrder\JobOrderPackingItem::with([
            'jobOrder',
            // 'bagType',
            'bagCondition',
            'companyLocation',
            'brand',
            'jobOrder.product'
        ])
            ->whereIn('job_order_id', is_array($jobOrderIds) ? $jobOrderIds : [$jobOrderIds])
            ->where('company_location_id', $locationId)
            ->get();

        // Get production outputs with details for each job order (location-wise)
        $producedByJobOrder = [];
        $producedDetailsByJobOrder = [];

        foreach ($jobOrderIds as $jobOrderId) {
            $outputs = \App\Models\Production\ProductionOutput::with([
                'productionVoucher',
                'productionVoucher.location',
                'storageLocation',
                'storageLocation.arrivalLocation',
                'product',
                'brand'
            ])
                ->where('job_order_id', $jobOrderId)
                ->whereHas('productionVoucher', function ($q) use ($locationId) {
                    $q->where('location_id', $locationId);
                })
                ->get();

            $producedQty = $outputs->sum('qty');
            $producedByJobOrder[$jobOrderId] = $producedQty ?? 0;
            $producedDetailsByJobOrder[$jobOrderId] = $outputs;
        }

        return view('management.production.production_voucher.partials.packing_items_table', [
            'packingItems' => $packingItems,
            'producedByJobOrder' => $producedByJobOrder,
            'producedDetailsByJobOrder' => $producedDetailsByJobOrder,
            'locationId' => $locationId,
            'currentProductionVoucherId' => $currentProductionVoucherId
        ]);
    }

    public function getBrandsByJobOrders(Request $request)
    {
        $jobOrderIds = $request->job_order_ids; // Array of job order IDs

        // if (!$jobOrderIds || (is_array($jobOrderIds) && count($jobOrderIds) == 0)) {
        //     return response()->json(['brands' => []]);
        // }



        $brandIds = \App\Models\Production\JobOrder\JobOrderPackingItem::
            when($jobOrderIds != null, function ($query) use ($jobOrderIds) {
                $query->whereIn('job_order_id', is_array($jobOrderIds) ? $jobOrderIds : [$jobOrderIds]);
            })
            ->whereNotNull('brand_id')
            ->where('company_location_id', $request->location_id)
            ->distinct()
            ->pluck('brand_id')
            ->toArray();

        $brands = Brands::where('status', 1)
            ->whereIn('id', $brandIds)
            ->orderBy('name')
            ->get()
            ->map(function ($brand) {
                return [
                    'id' => $brand->id,
                    'name' => $brand->name
                ];
            });

        return response()->json(['brands' => $brands]);
    }

    public function getCommoditiesByLocation(Request $request)
    {
        $locationId = $request->location_id;

        if (!$locationId) {
            return response()->json(['commodities' => []]);
        }

        $user = auth()->user();

        // Get unique products from job orders that have packing items for this location
        $commodities = JobOrder::with('product')
            ->where('status', 1)
            ->whereHas('packingItems', function ($q) use ($locationId) {
                $q->where('company_location_id', $locationId);
            })
            ->when($user->user_type !== 'super-admin', function ($query) use ($user) {
                return $query->whereHas('packingItems', function ($q) use ($user) {
                    $q->where('company_location_id', $user->company_location_id);
                });
            })
            ->get()
            ->pluck('product_id')
            ->unique()
            ->filter()
            ->map(function ($productId) {
                $product = \App\Models\Product::find($productId);
                return $product ? [
                    'id' => $product->id,
                    'name' => $product->name
                ] : null;
            })
            ->filter()
            ->values();

        return response()->json(['commodities' => $commodities]);
    }

    public function getPlantsByLocation(Request $request)
    {
        $locationId = $request->subLocationId;

        if (!$locationId) {
            return response()->json(['plants' => []]);
        }

        $user = auth()->user();

        $plants = Plant::where('arrival_location_id', $locationId)
            ->where('status', 'active')
            ->when($user->user_type !== 'super-admin', function ($query) use ($user) {
                return $query->where('company_id', $user->company_id);
            })
            ->get()
            ->map(function ($plant) {
                return [
                    'id' => $plant->id,
                    'name' => $plant->name,
                    'production_labour_charges_per_kg' => $plant->production_labour_charges_per_kg
                ];
            });

        return response()->json(['plants' => $plants]);
    }
    public function getSublocationsByLocation(Request $request)
    {
        $locationId = $request->location_id;

        if (!$locationId) {
            return response()->json(['sublocations' => []]);
        }

        $user = auth()->user();

        $sublocations = ArrivalLocation::where('company_location_id', $locationId)
            ->where('status', 'active')
            ->when($user->user_type !== 'super-admin', function ($query) use ($user) {
                return $query->where('company_id', $user->company_id);
            })
            ->get()
            ->map(function ($sublocation) {
                return [
                    'id' => $sublocation->id,
                    'name' => $sublocation->name
                ];
            });

        return response()->json(['sublocations' => $sublocations]);
    }

    public function getHeadProductsByCommodity(Request $request)
    {
        $commodityId = $request->commodity_id;

        if (!$commodityId) {
            return response()->json(['headProducts' => []]);
        }

        $commodity = Product::find($commodityId);

        if (!$commodity) {
            return response()->json(['headProducts' => []]);
        }

        // Filter products based on parent_id logic (same as output form)
        $productsQuery = Product::where('status', 1);

        if ($commodity->parent_id) {
            // Commodity has a parent - show all products with same parent_id (including commodity if it's a child)
            $productsQuery->where(function ($q) use ($commodity) {
                $q->where('parent_id', $commodity->parent_id)
                    ->orWhere('id', $commodity->parent_id); // Include parent itself
            });
        } else {
            // Commodity is itself a parent (parent_id is null) - show all its children + itself
            $productsQuery->where(function ($q) use ($commodityId) {
                $q->where('parent_id', $commodityId)
                    ->orWhere('id', $commodityId); // Include commodity itself
            });
        }

        $headProducts = $productsQuery->orderBy('name')->get()->map(function ($product) {
            return [
                'id' => $product->id,
                'name' => $product->name
            ];
        });

        return response()->json(['headProducts' => $headProducts]);
    }

    public function getMachinesByPlant(Request $request)
    {
        $plantId = $request->plant_id;
        if (!$plantId) {
            return response()->json(['machines' => []]);
        }

        $machines = ProductionMachine::where('plant_id', $plantId)
            ->where('status', 'active')
            ->get();

        return response()->json(['machines' => $machines]);
    }

    public function store(ProductionVoucherRequest $request)
    {
        // dd($request);
        DB::beginTransaction();

        try {
            $uniqueProdNo = generateUniversalUniqueNo('production_vouchers', [
                'prefix' => 'PRO',
                'column' => 'prod_no',
                'with_date' => 1,
                'custom_date' => $request->prod_date,
                'date_format' => 'm-Y',
                'serial_at_end' => 1,
            ]);

            // Extract single values for main production voucher fields
            $productionVoucherData = [
                'prod_date' => $request->input('prod_date'),
                'location_id' => $request->input('location_id'), // Single value from main form
                'product_id' => $request->input('product_id'), // Single value from main form
                'plant_id' => $request->input('plant_id'),
                'by_product_id' => $request->input('by_product_id'),
                'remarks' => $request->input('remarks'),
                'sub_location_id' => $request->input('sub_location_id'),
                'net_total_input' => $request->input('net_total_input'),
                'net_total_output' => $request->input('net_total_output'),
                'labour_charges_per_kg' => $request->input('labour_charges_per_kg'),
                'total_labour_charges' => $request->input('total_labour_charges'),
                'labour_deduction' => $request->input('labour_deduction') ?? 0,
                'labour_deduction_remarks' => $request->input('labour_deduction_remarks'),
                'labour_net_amount' => $request->input('labour_net_amount'),
                // 'status' => 'active',
            ];

            // Handle multiple job orders - store first one in job_order_id for backward compatibility
            $jobOrderIds = is_array($request->job_order_id) ? $request->job_order_id : ($request->job_order_id ? [$request->job_order_id] : []);
            $productionVoucherData['job_order_id'] = !empty($jobOrderIds) ? $jobOrderIds[0] : null;

            $productionVoucherData['company_id'] = auth()->user()->company_id ?? $request->company_id;
            $productionVoucherData['prod_no'] = $uniqueProdNo;
            $productionVoucherData['user_id'] = auth()->user()->id;
            $productionVoucherData['supervisor_id'] = auth()->user()->id;
            $productionVoucherData['status'] = 'active';

            $productionVoucher = ProductionVoucher::create($productionVoucherData);

            // Sync job orders to pivot table
            if (!empty($jobOrderIds)) {
                $productionVoucher->jobOrders()->sync($jobOrderIds);
            }

            // Sync production machines
            if ($request->has('production_machine_id')) {
                $productionVoucher->productionMachines()->sync($request->production_machine_id);
            }

            // Save Production Inputs
            // Production inputs use input_product_id[] and input_location_id[] to avoid conflict with main form fields
            $inputProductIds = $request->input('input_product_id', []);
            if (is_array($inputProductIds) && !empty($inputProductIds)) {
                $inputLocationIds = $request->input('input_location_id', []);
                $inputQtys = $request->input('input_qty', []);
                $inputRemarks = $request->input('input_remarks', []);

                foreach ($inputProductIds as $index => $productId) {
                    if (!empty($productId) && !empty($inputLocationIds[$index]) && !empty($inputQtys[$index])) {
                        ProductionInput::create([
                            'production_voucher_id' => $productionVoucher->id,
                            'product_id' => $productId,
                            'location_id' => $inputLocationIds[$index],
                            'qty' => $inputQtys[$index],
                            'remarks' => $inputRemarks[$index] ?? null,
                        ]);
                    }
                }
            }

            // Save Production Outputs (Head Products and By Products)
            // Now using output_ prefixed field names to differentiate from inputs
            if ($request->has('output_qty') && is_array($request->output_qty)) {
                $outputQtys = $request->output_qty;
                $outputProductIds = $request->input('output_product_id', []);
                $outputNoOfBags = $request->input('output_no_of_bags', []);
                $outputBagSizes = $request->input('output_bag_size', []);
                $outputAvgWeights = $request->input('output_avg_weight_per_bag', []);
                $outputStorageLocations = $request->input('output_arrival_sub_location_id', []);
                $outputBrandIds = $request->input('output_brand_id', []);
                $outputJobOrderIds = $request->input('output_job_order_id', []);
                $outputRemarks = $request->input('output_remarks', []);
                $job_order_packing_item_id = $request->input('job_order_packing_item_id', []);

                foreach ($outputQtys as $index => $qty) {
                    if (!empty($qty) && $qty > 0 && !empty($outputProductIds[$index])) {
                        // Get job_order_id for this row
                        $jobOrderIdForOutput = null;
                        if (!empty($outputJobOrderIds[$index])) {
                            if (is_array($outputJobOrderIds[$index])) {
                                $jobOrderIdForOutput = !empty($outputJobOrderIds[$index]) ? $outputJobOrderIds[$index][0] : null;
                            } else {
                                $jobOrderIdForOutput = $outputJobOrderIds[$index];
                            }
                        } else {
                            $jobOrderIdForOutput = null;
                        }

                        ProductionOutput::create([
                            'job_order_packing_item_id' => $job_order_packing_item_id[$index] ?? null,
                            'production_voucher_id' => $productionVoucher->id,
                            'job_order_id' => $jobOrderIdForOutput,
                            'product_id' => $outputProductIds[$index],
                            'qty' => $qty,
                            'no_of_bags' => !empty($outputNoOfBags[$index]) ? (int) $outputNoOfBags[$index] : null,
                            'bag_size' => !empty($outputBagSizes[$index]) ? $outputBagSizes[$index] : null,
                            'avg_weight_per_bag' => !empty($outputAvgWeights[$index]) ? $outputAvgWeights[$index] : null,
                            'arrival_sub_location_id' => !empty($outputStorageLocations[$index]) ? $outputStorageLocations[$index] : null,
                            'brand_id' => !empty($outputBrandIds[$index]) ? $outputBrandIds[$index] : null,
                            'remarks' => $outputRemarks[$index] ?? null,
                        ]);
                    }
                }
            }

            DB::commit();

            return response()->json([
                'success' => 'Production Voucher created successfully.',
                'redirect' => route('production-voucher.edit', $productionVoucher->id),
                'data' => $productionVoucher
            ], 201);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Something went wrong: ' . $e->getMessage()
            ], 500);
        }
    }

    public function editbkk($id)
    {
        $productionVoucher = ProductionVoucher::with([
            'jobOrder.product',
            'jobOrders.product',
            'location',
            'product',
            'supervisor',
            'inputs.product',
            'inputs.location',
            'outputs.product',
            'outputs.storageLocation',
            'outputs.brand',
            'slots.breaks'
        ])->findOrFail($id);


        $productIds = Product::where('id', $productionVoucher->product_id)->orWhere('parent_id', $productionVoucher->product_id)->pluck('id')->toArray();



        $jobOrderIds = $productionVoucher->jobOrders->pluck('id')->toArray();
        $productIds = Product::where('id', $productionVoucher->product_id)->orWhere('parent_id', $productionVoucher->product_id)->pluck('id')->toArray();


        $byProductIds = Product::where(function ($query) use ($productionVoucher) {
            $query->where('id', $productionVoucher->by_product_id)
                ->orWhere('parent_id', $productionVoucher->by_product_id);
        })
            ->where('is_by_product', 'yes')->pluck('id')->toArray();



        // dd($jobOrderIds);
        $jobOrderPackings = JobOrderPackingItem::with([
            'jobOrder:id,job_order_no,product_id', // product_id zaroor add karein
            'jobOrder.product:id,name'

        ])

            ->whereIn('job_order_id', $jobOrderIds)
            ->whereHas('jobOrder', function ($query) use ($productIds) {
                $query->whereIn('product_id', $productIds);
            })
            ->get();

        $jobOrderPackingsByProduct = JobOrderPackingItem::with([
            'jobOrder:id,job_order_no,product_id', // product_id zaroor add karein
            'jobOrder.product:id,name'

        ])

            ->whereIn('job_order_id', $jobOrderIds)
            ->whereHas('jobOrder', function ($query) use ($byProductIds) {
                $query->whereIn('product_id', $byProductIds);
            });
        // dd($jobOrderIds,$jobOrderPackingsByProduct->get(),$byProductIds);

        // dd($jobOrderPackings);

        $jobOrderRawMaterialQcs = JobOrderRawMaterialQc::whereIn('job_order_id', $productionVoucher->jobOrders->pluck('id'))->get();

        $jobOrders = JobOrder::where('status', 1)->get();

        $companyLocations = CompanyLocation::where('status', 'active')->get();
        $supervisors = User::where('status', 'active')->get();
        $products = Product::where('status', 1)->get();
        $sublocations = ArrivalSubLocation::where('status', 1)->get();
        $brands = Brands::where('status', 1)->get();
        $plants = \App\Models\Master\Plant::where('status', 'active')->get();

        return view('management.production.production_voucher.edit', compact(
            'productionVoucher',
            'jobOrders',
            'companyLocations',
            'supervisors',
            'products',
            'sublocations',
            'jobOrderPackings',
            'jobOrderPackingsByProduct',
            'jobOrderIds',
            'brands',
            'plants'
        ));
    }


    public function edit($id)
    {
        $productionVoucher = ProductionVoucher::with([
            'jobOrder.product',
            'jobOrders.product',
            'location',
            'product',
            'supervisor',
            'inputs.product',
            'inputs.location',
            'outputs.product',
            'outputs.storageLocation',
            'outputs.brand',
            'slots.breaks'
        ])->findOrFail($id);

        $productIds = Product::where('id', $productionVoucher->product_id)
            ->orWhere('parent_id', $productionVoucher->product_id)
            ->pluck('id')
            ->toArray();


        $jobOrderIds = $productionVoucher->jobOrders->pluck('id')->toArray();

        $byProductIds = Product::where(function ($query) use ($productionVoucher) {
            $query->where('id', $productionVoucher->by_product_id)
                ->orWhere('parent_id', $productionVoucher->by_product_id);
        })
            // ->where('is_by_product', 'yes')
            ->where('product_category_flags', 'by')

            ->pluck('id')
            ->toArray();



        $product = Product::find($productionVoucher->product_id);

        $parentId = $product->parent_id ? $product->parent_id : $product->id;

        $allHeadProductIds = Product::where(function ($q) use ($parentId) {
            $q->where('id', $parentId)         // parent
                ->orWhere('parent_id', $parentId); // children
        })
            ->whereIn('product_category_flags', ['head', 'b2'])
            ->pluck('id')
            ->toArray();
        // dd($productIds);   
        // Get all related product IDs for head products (including children)
        $allHeadProductIdsbk = Product::where('id', $productionVoucher->product_id)
            ->orWhere('parent_id', $productionVoucher->product_id)
            ->whereIn('product_category_flags', ['head', 'b2'])
            ->pluck('id')
            ->toArray();




        // Initialize collections for head products
        $packingItemsProducts = collect();
        $nonPackingItemsProducts = collect();
        $jobOrderPackings = collect();

        if (!empty($jobOrderIds)) {
            // Get job order packings
            $jobOrderPackings = JobOrderPackingItem::with([
                'jobOrder:id,job_order_no,product_id',
                'jobOrder.product:id,name,product_category_flags'
            ])
                ->whereIn('job_order_id', $jobOrderIds)
                ->whereHas('jobOrder', function ($query) use ($allHeadProductIds) {
                    $query->whereIn('product_id', $allHeadProductIds);
                })
                ->whereHas('jobOrder.product', function ($query) {
                    $query->whereIn('product_category_flags', ['head', 'b2']);
                })
                ->get();

            // Get products that have packing items
            $packingItemsProducts = $jobOrderPackings->map(function ($packingItem) {
                if ($packingItem->jobOrder && $packingItem->jobOrder->product) {
                    return $packingItem->jobOrder->product;
                }
                return null;
            })->filter()->unique('id');

            // Get products that don't have packing items
            $productIdsWithPackingItems = $packingItemsProducts->pluck('id')->toArray();

            $nonPackingItemsProducts = Product::whereIn('id', $allHeadProductIds)
                ->whereNotIn('id', $productIdsWithPackingItems)
                ->where('status', 1)
                ->get();
        } else {
            // If no job orders, all products are non-packing items
            $nonPackingItemsProducts = Product::whereIn('id', $allHeadProductIds)
                ->where('status', 1)
                ->get();
        }

        $jobOrderPackingsByProduct = JobOrderPackingItem::with([
            'jobOrder:id,job_order_no,product_id',
            'jobOrder.product:id,name,product_category_flags'
        ])
            ->whereIn('job_order_id', $jobOrderIds)
            ->whereHas('jobOrder', function ($query) use ($byProductIds) {
                $query->whereIn('product_id', $byProductIds);
            })
            ->whereHas('jobOrder.product', function ($query) {
                $query->whereIn('product_category_flags', ['by']);
            })
        ;

        $jobOrderRawMaterialQcs = JobOrderRawMaterialQc::whereIn('job_order_id', $productionVoucher->jobOrders->pluck('id'))->get();

        $jobOrders = JobOrder::where('status', 1)->get();
        $companyLocations = CompanyLocation::where('status', 'active')->get();
        $supervisors = User::where('status', 'active')->get();
        $products = Product::where('status', 1)->get();
        $sublocations = ArrivalSubLocation::where('status', 1)->get();
        $brands = Brands::where('status', 1)->get();
        $plants = \App\Models\Master\Plant::where('status', 'active')->get();

        // IMPORTANT: Convert headProductOutputs to collection
        $headProductOutputs = collect($productionVoucher->outputs->where('product_id', $productionVoucher->product_id));

        return view('management.production.production_voucher.edit', compact(
            'productionVoucher',
            'jobOrders',
            'companyLocations',
            'supervisors',
            'products',
            'sublocations',
            'jobOrderPackings',
            'jobOrderPackingsByProduct',
            'jobOrderIds',
            'brands',
            'plants',
            'packingItemsProducts',
            'nonPackingItemsProducts',
            'allHeadProductIds',
            'headProductOutputs' // Ab ye collection hai
        ));
    }

    public function update(ProductionVoucherRequest $request, $id)
    {
        $productionVoucher = ProductionVoucher::findOrFail($id);

        DB::beginTransaction();

        try {
            // Extract single values for main production voucher fields (same as store)
            $productionVoucherData = [
                'prod_date' => $request->input('prod_date'),
                'location_id' => $request->input('location_id'),
                'sub_location_id' => $request->input('sub_location_id'),
                'product_id' => $request->input('product_id'),
                'plant_id' => $request->input('plant_id'),
                'by_product_id' => $request->input('by_product_id'),
                'net_total_input' => $request->input('net_total_input'),
                'net_total_output' => $request->input('net_total_output'),
                'labour_charges_per_kg' => $request->input('labour_charges_per_kg'),
                'total_labour_charges' => $request->input('total_labour_charges'),
                'labour_deduction' => $request->input('labour_deduction') ?? 0,
                'labour_deduction_remarks' => $request->input('labour_deduction_remarks'),
                'labour_net_amount' => $request->input('labour_net_amount'),
                'remarks' => $request->input('remarks')
            ];

            // Handle multiple job orders
            $jobOrderIds = is_array($request->job_order_id) ? $request->job_order_id : ($request->job_order_id ? [$request->job_order_id] : []);
            $productionVoucherData['job_order_id'] = !empty($jobOrderIds) ? $jobOrderIds[0] : null;

            $productionVoucher->update($productionVoucherData);

            // Sync job orders to pivot table
            if (!empty($jobOrderIds)) {
                $productionVoucher->jobOrders()->sync($jobOrderIds);
            } else {
                $productionVoucher->jobOrders()->sync([]);
            }

            // Sync production machines
            if ($request->has('production_machine_id')) {
                $productionVoucher->productionMachines()->sync($request->production_machine_id);
            } else {
                $productionVoucher->productionMachines()->sync([]);
            }

            // Delete existing inputs and outputs (always delete and recreate from form data)
            $productionVoucher->inputs()->delete();
            $productionVoucher->outputs()->delete();

            // Save Production Inputs (same as store - using input_ prefix)
            $inputProductIds = $request->input('input_product_id', []);
            if (is_array($inputProductIds) && !empty($inputProductIds)) {
                $inputLocationIds = $request->input('input_location_id', []);
                $inputQtys = $request->input('input_qty', []);
                $inputRemarks = $request->input('input_remarks', []);

                foreach ($inputProductIds as $index => $productId) {
                    if (!empty($productId) && !empty($inputLocationIds[$index]) && !empty($inputQtys[$index])) {
                        ProductionInput::create([
                            'production_voucher_id' => $productionVoucher->id,
                            'product_id' => $productId,
                            'location_id' => $inputLocationIds[$index],
                            'qty' => $inputQtys[$index],
                            'remarks' => $inputRemarks[$index] ?? null,
                        ]);
                    }
                }
            }

            // Save Production Outputs (same as store - using output_ prefix)
            if ($request->has('output_qty') && is_array($request->output_qty)) {
                $outputQtys = $request->output_qty;
                $outputProductIds = $request->input('output_product_id', []);
                $outputNoOfBags = $request->input('output_no_of_bags', []);
                $outputBagSizes = $request->input('output_bag_size', []);
                $outputAvgWeights = $request->input('output_avg_weight_per_bag', []);
                $outputStorageLocations = $request->input('output_arrival_sub_location_id', []);
                $outputBrandIds = $request->input('output_brand_id', []);
                $outputJobOrderIds = $request->input('output_job_order_id', []);
                $outputRemarks = $request->input('output_remarks', []);
                $job_order_packing_item_id = $request->input('job_order_packing_item_id', []);

                foreach ($outputQtys as $index => $qty) {
                    if (!empty($qty) && $qty > 0 && !empty($outputProductIds[$index])) {
                        $jobOrderIdForOutput = null;
                        if (!empty($outputJobOrderIds[$index])) {
                            if (is_array($outputJobOrderIds[$index])) {
                                $jobOrderIdForOutput = !empty($outputJobOrderIds[$index]) ? $outputJobOrderIds[$index][0] : null;
                            } else {
                                $jobOrderIdForOutput = $outputJobOrderIds[$index];
                            }
                        } else {
                            $jobOrderIdForOutput = null;
                        }

                        ProductionOutput::create([
                            'job_order_packing_item_id' => $job_order_packing_item_id[$index] ?? null,

                            'production_voucher_id' => $productionVoucher->id,
                            'job_order_id' => $jobOrderIdForOutput,
                            'product_id' => $outputProductIds[$index],
                            'qty' => $qty,
                            'no_of_bags' => !empty($outputNoOfBags[$index]) ? (int) $outputNoOfBags[$index] : null,
                            'bag_size' => !empty($outputBagSizes[$index]) ? $outputBagSizes[$index] : null,
                            'avg_weight_per_bag' => !empty($outputAvgWeights[$index]) ? $outputAvgWeights[$index] : null,
                            'arrival_sub_location_id' => !empty($outputStorageLocations[$index]) ? $outputStorageLocations[$index] : null,
                            'brand_id' => !empty($outputBrandIds[$index]) ? $outputBrandIds[$index] : null,
                            'remarks' => $outputRemarks[$index] ?? null,
                        ]);
                    }
                }
            }

            DB::commit();

            return response()->json([
                'success' => 'Production Voucher updated successfully.',
                'data' => $productionVoucher->fresh(['inputs', 'outputs'])
            ], 200);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Something went wrong: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        $productionVoucher = ProductionVoucher::findOrFail($id);
        $productionVoucher->delete();

        return response()->json([
            'success' => 'Production Voucher deleted successfully.'
        ], 200);
    }

    // Production Input Form
    public function getInputForm($id, $inputId = null)
    {
        $productionVoucher = ProductionVoucher::findOrFail($id);
        $products = Product::where('status', 1)->get();

        // Get arrival sub locations filtered by production voucher's location
        $sublocationsQuery = ArrivalSubLocation::where('status', 'active');
        if ($productionVoucher->location_id) {
            // Filter by company location through arrival locations
            $sublocationsQuery->whereHas('arrivalLocation', function ($q) use ($productionVoucher) {
                $q->where('company_location_id', $productionVoucher->location_id);
            });
        }
        $sublocations = $sublocationsQuery->get();

        // Get slots for this production voucher
        $slots = ProductionSlot::where('production_voucher_id', $id)
            ->where('status', '!=', 'cancelled')
            ->orderBy('date', 'desc')
            ->orderBy('start_time', 'desc')
            ->get();

        $productionInput = null;
        $view = 'management.production.production_voucher.input.create';

        if ($inputId) {
            $productionInput = ProductionInput::where('production_voucher_id', $id)
                ->findOrFail($inputId);
            $view = 'management.production.production_voucher.input.edit';
        }

        return view($view, compact(
            'productionVoucher',
            'products',
            'sublocations',
            'slots',
            'productionInput'
        ));
    }

    // Production Output Form
    public function getOutputForm($id, $outputId = null)
    {
        $productionVoucher = ProductionVoucher::with(['jobOrder.product', 'jobOrders.product'])->findOrFail($id);

        // Get head product (from first job order - backward compatibility)
        $headProductId = $productionVoucher->jobOrder->product_id ?? ($productionVoucher->jobOrders->first()->product_id ?? null);
        $headProduct = $headProductId ? Product::find($headProductId) : null;

        // Filter products based on parent_id logic
        $productsQuery = Product::where('status', 1);

        if ($headProduct) {
            if ($headProduct->parent_id) {
                // Head product has a parent - show all products with same parent_id (including head product if it's a child)
                $productsQuery->where(function ($q) use ($headProduct) {
                    $q->where('parent_id', $headProduct->parent_id)
                        ->orWhere('id', $headProduct->parent_id); // Include parent itself
                });
            } else {
                // Head product is itself a parent (parent_id is null) - show all its children + itself
                $productsQuery->where(function ($q) use ($headProductId) {
                    $q->where('parent_id', $headProductId)
                        ->orWhere('id', $headProductId); // Include head product itself
                });
            }
        }

        $products = $productsQuery->orderBy('name')->get();

        // Get arrival sub locations filtered by production voucher's location
        $arrivalSubLocationsQuery = \App\Models\Master\ArrivalSubLocation::with('arrivalLocation')
            ->where('status', 'active');

        if ($productionVoucher->location_id) {
            // Filter by company location through arrival locations
            $arrivalSubLocationsQuery->whereHas('arrivalLocation', function ($q) use ($productionVoucher) {
                $q->where('company_location_id', $productionVoucher->location_id);
            });
        }

        $arrivalSubLocations = $arrivalSubLocationsQuery->orderBy('name')->get();

        // Get all brands (will be filtered dynamically by job order via JavaScript)
        $brands = Brands::where('status', 1)->orderBy('name')->get();

        // Get slots for this production voucher
        $slots = ProductionSlot::where('production_voucher_id', $id)
            ->where('status', '!=', 'cancelled')
            ->orderBy('date', 'desc')
            ->orderBy('start_time', 'desc')
            ->get();

        // Get all job orders for this production voucher
        $jobOrders = $productionVoucher->jobOrders;

        $productionOutput = null;
        $view = 'management.production.production_voucher.output.create';

        if ($outputId) {
            $productionOutput = ProductionOutput::where('production_voucher_id', $id)
                ->findOrFail($outputId);
            $view = 'management.production.production_voucher.output.edit';
        }

        return view($view, compact(
            'productionVoucher',
            'products',
            'arrivalSubLocations',
            'brands',
            'slots',
            'jobOrders',
            'productionOutput'
        ));
    }

    // Production Input Methods
    public function storeInput(Request $request, $id)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'location_id' => 'required|exists:arrival_sub_locations,id',
            'slot_id' => 'required|exists:production_slots,id',
            'qty' => 'required|numeric|min:0.01',
            'remarks' => 'nullable|string|max:1000'
        ]);

        $productionVoucher = ProductionVoucher::findOrFail($id);

        $input = ProductionInput::create([
            'production_voucher_id' => $productionVoucher->id,
            'product_id' => $request->product_id,
            'location_id' => $request->location_id,
            'slot_id' => $request->slot_id,
            'qty' => $request->qty,
            'remarks' => $request->remarks
        ]);

        return response()->json([
            'success' => 'Production Input added successfully.',
            'data' => $input->load('product', 'location')
        ], 201);
    }

    public function updateInput(Request $request, $id, $inputId)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'location_id' => 'required|exists:arrival_sub_locations,id',
            'slot_id' => 'required|exists:production_slots,id',
            'qty' => 'required|numeric|min:0.01',
            'remarks' => 'nullable|string|max:1000'
        ]);

        $input = ProductionInput::where('production_voucher_id', $id)
            ->findOrFail($inputId);

        $input->update($request->only(['product_id', 'location_id', 'slot_id', 'qty', 'remarks']));

        return response()->json([
            'success' => 'Production Input updated successfully.',
            'data' => $input->load('product', 'location')
        ], 200);
    }

    public function destroyInput($id, $inputId)
    {
        $input = ProductionInput::where('production_voucher_id', $id)
            ->findOrFail($inputId);
        $input->delete();

        return response()->json([
            'success' => 'Production Input deleted successfully.'
        ], 200);
    }

    // Production Output Methods
    public function storeOutput(Request $request, $id)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'qty' => 'required|numeric|min:0.01',
            'no_of_bags' => 'nullable|integer|min:0',
            'bag_size' => 'nullable|string|in:100g,1kg,5kg,10kg,15kg,25kg,50kg',
            'avg_weight_per_bag' => 'nullable|numeric|min:0',
            'arrival_sub_location_id' => 'required|exists:arrival_sub_locations,id',
            'brand_id' => 'nullable|exists:brands,id',
            'slot_id' => 'required|exists:production_slots,id',
            'job_order_id' => 'nullable|exists:job_orders,id',
            'remarks' => 'nullable|string|max:1000'
        ]);

        $productionVoucher = ProductionVoucher::findOrFail($id);

        $output = ProductionOutput::create([
            'production_voucher_id' => $productionVoucher->id,
            'product_id' => $request->product_id,
            'qty' => $request->qty,
            'no_of_bags' => $request->no_of_bags,
            'bag_size' => $request->bag_size,
            'avg_weight_per_bag' => $request->avg_weight_per_bag,
            'arrival_sub_location_id' => $request->arrival_sub_location_id,
            'brand_id' => $request->brand_id,
            'slot_id' => $request->slot_id,
            'job_order_id' => $request->job_order_id,
            'remarks' => $request->remarks
        ]);

        return response()->json([
            'success' => 'Production Output added successfully.',
            'data' => $output->load('product', 'storageLocation', 'brand')
        ], 201);
    }

    public function updateOutput(Request $request, $id, $outputId)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'qty' => 'required|numeric|min:0.01',
            'no_of_bags' => 'nullable|integer|min:0',
            'bag_size' => 'nullable|string|in:100g,1kg,5kg,10kg,15kg,25kg,50kg',
            'avg_weight_per_bag' => 'nullable|numeric|min:0',
            'arrival_sub_location_id' => 'required|exists:arrival_sub_locations,id',
            'brand_id' => 'nullable|exists:brands,id',
            'slot_id' => 'required|exists:production_slots,id',
            'job_order_id' => 'nullable|exists:job_orders,id',
            'remarks' => 'nullable|string|max:1000'
        ]);

        $output = ProductionOutput::where('production_voucher_id', $id)
            ->findOrFail($outputId);

        $output->update($request->only(['product_id', 'qty', 'no_of_bags', 'bag_size', 'avg_weight_per_bag', 'arrival_sub_location_id', 'brand_id', 'slot_id', 'job_order_id', 'remarks']));

        return response()->json([
            'success' => 'Production Output updated successfully.',
            'data' => $output->load('product', 'storageLocation', 'brand')
        ], 200);
    }

    public function destroyOutput($id, $outputId)
    {
        $output = ProductionOutput::where('production_voucher_id', $id)
            ->findOrFail($outputId);
        $output->delete();

        return response()->json([
            'success' => 'Production Output deleted successfully.'
        ], 200);
    }

    // Get Inputs List (arrival_location pattern)
    public function getInputsList(Request $request, $id)
    {
        $productionVoucher = ProductionVoucher::findOrFail($id);
        $inputs = ProductionInput::with(['product', 'location', 'slot'])
            ->where('production_voucher_id', $id)
            ->get()
            ->sortByDesc(function ($input) {
                if ($input->slot) {
                    return $input->slot->date . ' ' . ($input->slot->start_time ?? '00:00:00');
                }
                return '1900-01-01 00:00:00';
            })
            ->values();

        // Load outputs for yield calculation
        $outputs = ProductionOutput::where('production_voucher_id', $id)->get();

        return view('management.production.production_voucher.input.getList', compact('inputs', 'outputs', 'productionVoucher'));
    }

    // Get Outputs List (arrival_location pattern)
    public function getOutputsList(Request $request, $id)
    {
        $productionVoucher = ProductionVoucher::with(['jobOrder.product', 'jobOrders.product'])->findOrFail($id);

        // Get head product from first job order (for backward compatibility)
        $headProductId = $productionVoucher->jobOrder->product_id ?? ($productionVoucher->jobOrders->first()->product_id ?? null);

        $allOutputs = ProductionOutput::with(['product', 'storageLocation.arrivalLocation', 'brand', 'slot', 'jobOrder'])
            ->where('production_voucher_id', $id)
            ->get()
            ->sortByDesc(function ($output) {
                if ($output->slot) {
                    return $output->slot->date . ' ' . ($output->slot->start_time ?? '00:00:00');
                }
                return '1900-01-01 00:00:00';
            })
            ->values();

        // Separate outputs by head product
        $headProductOutputs = $allOutputs->where('product_id', $headProductId);
        $otherProductOutputs = $allOutputs->where('product_id', '!=', $headProductId);

        // Load inputs for yield calculation
        $inputs = ProductionInput::where('production_voucher_id', $id)->get();

        return view('management.production.production_voucher.output.getList', compact(
            'headProductOutputs',
            'otherProductOutputs',
            'productionVoucher',
            'headProductId',
            'inputs'
        ));
    }

    // Production Slot Form
    public function getSlotForm($id, $slotId = null)
    {
        $productionVoucher = ProductionVoucher::findOrFail($id);

        $productionSlot = null;
        $view = 'management.production.production_voucher.slot.create';

        if ($slotId) {
            $productionSlot = ProductionSlot::with('breaks')
                ->where('production_voucher_id', $id)
                ->findOrFail($slotId);
            $view = 'management.production.production_voucher.slot.edit';
        }

        return view($view, compact('productionVoucher', 'productionSlot'));
    }

    // Production Slot Methods
    public function storeSlot(Request $request, $id)
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'start_time' => 'required',
            'end_time' => 'nullable|after:start_time',
            'status' => 'nullable|in:active,completed,cancelled',
            'description' => 'nullable|string|max:5000',
            'remarks' => 'nullable|string|max:1000',
            'attachment' => 'nullable|file|mimes:jpeg,jpg,png,pdf,doc,docx|max:10240',
            'breaks' => 'nullable|array',
            'breaks.*.break_in' => 'nullable|after:start_time',
            'breaks.*.break_out' => 'nullable|after:break_in',
            'breaks.*.reason' => 'nullable|string|max:500'
        ], [
            'end_time.after' => 'The end time must be after the start time',
            'breaks.*.break_in.after' => 'The break in time must be after the start time',
            'breaks.*.break_out.after' => 'The break out time must be after the break in time',
            'attachment.max' => 'Attachment size should not exceed 10MB'
        ]);

        DB::beginTransaction();

        try {
            $slotData = $request->only([
                'date',
                'start_time',
                'end_time',
                'status',
                'description',
                'remarks'
            ]);

            // Handle file upload
            if ($request->hasFile('attachment')) {
                $file = $request->file('attachment');
                $fileName = 'production_slots/' . time() . '_' . $file->getClientOriginalName();
                $file->storeAs('public', $fileName);
                $slotData['attachment'] = $fileName;
            }

            $slotData['production_voucher_id'] = $id;
            $slotData['status'] = $slotData['status'] ?? 'active';

            $productionSlot = ProductionSlot::create($slotData);

            // Save breaks if provided and not empty
            if ($request->has('breaks') && is_array($request->breaks) && count($request->breaks) > 0) {
                foreach ($request->breaks as $breakData) {
                    if (!empty($breakData['break_in']) && trim($breakData['break_in']) !== '') {
                        ProductionSlotBreak::create([
                            'production_slot_id' => $productionSlot->id,
                            'break_in' => trim($breakData['break_in']),
                            'break_out' => !empty($breakData['break_out']) ? trim($breakData['break_out']) : null,
                            'reason' => !empty($breakData['reason']) ? trim($breakData['reason']) : null
                        ]);
                    }
                }
            }

            DB::commit();

            return response()->json([
                'success' => 'Production Slot created successfully.',
                'data' => $productionSlot->load('breaks')
            ], 201);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Something went wrong: ' . $e->getMessage()
            ], 500);
        }
    }

    public function updateSlot(Request $request, $id, $slotId)
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'start_time' => 'required|after:date',
            'end_time' => 'nullable|after:start_time',
            'status' => 'nullable|in:active,completed,cancelled',
            'description' => 'nullable|string|max:5000',
            'remarks' => 'nullable|string|max:1000',
            'attachment' => 'nullable|file|mimes:jpeg,jpg,png,pdf,doc,docx|max:10240',
            'breaks' => 'nullable|array',
            'breaks.*.id' => 'nullable|exists:production_slot_breaks,id',
            'breaks.*.break_in' => 'required_with:breaks|after:start_time',
            'breaks.*.break_out' => 'nullable|after:break_in',
            'breaks.*.reason' => 'nullable|string|max:500'
        ], [
            'end_time.after' => 'The end time must be after the start time',
            'breaks.*.break_in.after' => 'The break in time must be after the start time',
            'breaks.*.break_out.after' => 'The break out time must be after the break in time',
            'attachment.max' => 'Attachment size should not exceed 10MB'
        ]);

        $productionSlot = ProductionSlot::where('production_voucher_id', $id)
            ->findOrFail($slotId);

        DB::beginTransaction();

        try {
            $slotData = $request->only([
                'date',
                'start_time',
                'end_time',
                'status',
                'description',
                'remarks'
            ]);

            // Handle file upload - delete old file if new one is uploaded
            if ($request->hasFile('attachment')) {
                // Delete old attachment if exists
                if ($productionSlot->attachment && Storage::exists('public/' . $productionSlot->attachment)) {
                    Storage::delete('public/' . $productionSlot->attachment);
                }

                $file = $request->file('attachment');
                $fileName = 'production_slots/' . time() . '_' . $file->getClientOriginalName();
                $file->storeAs('public', $fileName);
                $slotData['attachment'] = $fileName;
            }

            $productionSlot->update($slotData);

            // Handle breaks - update existing, create new, delete removed
            if ($request->has('breaks') && is_array($request->breaks)) {
                $existingBreakIds = [];

                foreach ($request->breaks as $breakData) {
                    if (!empty($breakData['break_in']) && trim($breakData['break_in']) !== '') {
                        if (isset($breakData['id']) && $breakData['id']) {
                            // Update existing break
                            $break = ProductionSlotBreak::where('production_slot_id', $productionSlot->id)
                                ->find($breakData['id']);
                            if ($break) {
                                $break->update([
                                    'break_in' => trim($breakData['break_in']),
                                    'break_out' => !empty($breakData['break_out']) ? trim($breakData['break_out']) : null,
                                    'reason' => !empty($breakData['reason']) ? trim($breakData['reason']) : null
                                ]);
                                $existingBreakIds[] = $break->id;
                            }
                        } else {
                            // Create new break
                            $newBreak = ProductionSlotBreak::create([
                                'production_slot_id' => $productionSlot->id,
                                'break_in' => trim($breakData['break_in']),
                                'break_out' => !empty($breakData['break_out']) ? trim($breakData['break_out']) : null,
                                'reason' => !empty($breakData['reason']) ? trim($breakData['reason']) : null
                            ]);
                            $existingBreakIds[] = $newBreak->id;
                        }
                    }
                }

                // Delete breaks that are not in the request
                ProductionSlotBreak::where('production_slot_id', $productionSlot->id)
                    ->whereNotIn('id', $existingBreakIds)
                    ->delete();
            } else {
                // If no breaks array, delete all breaks
                ProductionSlotBreak::where('production_slot_id', $productionSlot->id)->delete();
            }

            DB::commit();

            return response()->json([
                'success' => 'Production Slot updated successfully.',
                'data' => $productionSlot->load('breaks')
            ], 200);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Something went wrong: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroySlot($id, $slotId)
    {
        $productionSlot = ProductionSlot::where('production_voucher_id', $id)
            ->findOrFail($slotId);
        $productionSlot->delete();

        return response()->json([
            'success' => 'Production Slot deleted successfully.'
        ], 200);
    }

    // Get Slots List (arrival_location pattern)
    public function getSlotsList(Request $request, $id)
    {
        $productionVoucher = ProductionVoucher::findOrFail($id);
        $slots = ProductionSlot::with('breaks')
            ->where('production_voucher_id', $id)
            ->latest()
            ->get();

        return view('management.production.production_voucher.slot.getList', compact('slots', 'productionVoucher'));
    }
}
