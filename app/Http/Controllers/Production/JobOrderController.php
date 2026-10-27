<?php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use App\Http\Requests\Production\JobOrderRequest;
use App\Models\Master\CropYear;
use App\Models\Master\Size;
use App\Models\Master\Stitching;
use App\Models\Production\JobOrder\{
    JobOrderPackingItem,
    JobOrderPackingSubItem,
    JobOrder,
    JobOrderSpecification
};
use App\Models\Master\{InspectionCompany, FumigationCompany, CompanyLocation, ProductSlab, ArrivalLocation, Brands, Color};
use App\Models\{Product, BagCondition, BagType};
use Illuminate\Http\Request;
use App\Models\User;
use DB;
use Carbon\Carbon;

class JobOrderController extends Controller
{
    public function index()
    {
        return view('management.production.job_orders.index');
    }

    public function getList(Request $request)
    {
        $job_orders = JobOrder::with(['product', 'companyLocation', 'productionOutputs.productionVoucher.location', 'packingItems.companyLocation'])
            ->when($request->filled('search'), function ($q) use ($request) {
                $searchTerm = '%' . $request->search . '%';
                return $q->where(function ($sq) use ($searchTerm) {
                    $sq->where('job_order_no', 'like', $searchTerm)
                        // ->orWhere('job_order_no', 'like', $searchTerm)
                        ->orWhere('ref_no', 'like', $searchTerm);
                });
            })
            ->where('status', 'active')
            ->latest()
            ->paginate(request('per_page', 25));

        // Calculate location-wise allocated and produced quantities for each job order
        foreach ($job_orders as $job_order) {
            $producedByLocation = [];

            // First, get allocated quantities from packing items (grouped by location)
            $allocatedByLocation = [];
            foreach ($job_order->packingItems as $packingItem) {
                $locationId = $packingItem->company_location_id ?? $job_order->company_location_id;
                if ($locationId) {
                    if (!isset($allocatedByLocation[$locationId])) {
                        $allocatedByLocation[$locationId] = [
                            'location_name' => $packingItem->companyLocation->name ?? $job_order->companyLocation->name ?? 'N/A',
                            'allocated_qty' => 0
                        ];
                    }
                    $allocatedByLocation[$locationId]['allocated_qty'] += $packingItem->total_kgs ?? 0;
                }
            }

            // Now, get produced quantities (only matching product outputs)
            foreach ($job_order->productionOutputs->where('product_id', $job_order->product_id) as $output) {
                if ($output->productionVoucher && $output->productionVoucher->location) {
                    $locationId = $output->productionVoucher->location->id;

                    if (!isset($producedByLocation[$locationId])) {
                        $producedByLocation[$locationId] = [
                            'location_name' => $output->productionVoucher->location->name,
                            'produced_qty' => 0
                        ];
                    }
                    $producedByLocation[$locationId]['produced_qty'] += $output->qty ?? 0;
                }
            }

            // Merge allocated and produced data
            $locationData = [];
            foreach ($allocatedByLocation as $locationId => $allocatedData) {
                $producedQty = $producedByLocation[$locationId]['produced_qty'] ?? 0;
                $locationData[$locationId] = [
                    'location_name' => $allocatedData['location_name'],
                    'allocated_qty' => $allocatedData['allocated_qty'],
                    'produced_qty' => $producedQty,
                    'remaining_qty' => $allocatedData['allocated_qty'] - $producedQty
                ];
            }

            // Also include locations that have production but no allocation (if any)
            foreach ($producedByLocation as $locationId => $producedData) {
                if (!isset($locationData[$locationId])) {
                    $locationData[$locationId] = [
                        'location_name' => $producedData['location_name'],
                        'allocated_qty' => 0,
                        'produced_qty' => $producedData['produced_qty'],
                        'remaining_qty' => -$producedData['produced_qty']
                    ];
                }
            }

            $job_order->producedByLocation = $locationData;
        }

        return view('management.production.job_orders.getList', compact('job_orders'));
    }

    public function printJobOrder($id)
    {
        $jobOrder = JobOrder::with([
            'companyLocation',
            'product', 
            'cropYear',
            'exportOrder',
            'jobOrderRequest',
            'packingItems.companyLocation',
            'packingItems.bagProduct',
            'packingItems.bagCondition',
            'packingItems.brand',
            'packingItems.bagColor',
            'packingItems.threadColor',
            'packingItems.stitching',
            'packingItems.subItems.bagType',
            'packingItems.subItems.bagProduct',
            'packingItems.subItems.bagSize',
            'packingItems.subItems.stitching',
            'packingItems.subItems.bagColor',
            'packingItems.subItems.brand',
            'packingItems.subItems.threadColor',
            'specifications'
        ])->findOrFail($id);
        
        $attentionToIds = [];
        if (is_string($jobOrder->attention_to)) {
            $attentionToIds = json_decode($jobOrder->attention_to, true) ?? [];
        } elseif (is_array($jobOrder->attention_to)) {
            $attentionToIds = $jobOrder->attention_to;
        }

        $users = User::whereIn('id', $attentionToIds)->get();
        return view('management.production.job_orders.view_job_order', compact('jobOrder', 'users'));
    }

    public function create()
    {
        $exportOrders = \App\Models\Export\ExportOrder::with(['packingItems', 'jobOrders.packingItems'])
            ->where('am_approval_status', 'approved')
            ->whereNotIn('id', function($q) {
                $q->select('export_order_id')->from('export_order_addendums');
            })
            ->latest()
            ->get()
            ->filter(function ($eo) {
                $totalMt = $eo->packingItems->sum('metric_tons');
                $consumedMt = $eo->jobOrders->sum(function ($jo) {
                    return $jo->packingItems->sum('metric_tons');
                });
                return ($totalMt - $consumedMt) > 0;
            });
            
        $products = getFinishGoodProducts();
        $bagProducts = Product::where('status', 'active')->where('product_type', 'general_items')
            ->with('category')
            ->whereHas('category', function ($query) {
                $query->whereIn(strtolower('name'), ['bag', 'bags']);
            })
            ->get();
        $containerProtectionProducts = Product::where('status', 1)->where('product_type', 'general_items')
            ->with('category')
            ->whereHas('category', function ($query) {
                $query->whereIn(strtolower('name'), ['store & spare']);
            })
            ->get();
        $inspectionCompanies = InspectionCompany::where('status', 'active')->get();
        $fumigationCompanies = FumigationCompany::where('status', 'active')->get();
        $user = auth()->user();
        $locationIds = getUserCurrentCompanyLocations();
        if ($user && ($user->user_type === 'super-admin' || empty($locationIds))) {
            $companyLocations = CompanyLocation::where('status', 'active')->get();
        } else {
            $companyLocations = CompanyLocation::whereIn('id', $locationIds)->where('status', 'active')->get();
        }
        $arrivalLocations = ArrivalLocation::where('status', 'active')->get();
        $cropYears = CropYear::where('status', 'active')->get();
        $bagTypes = BagType::where('status', 1)->get();
        $bagConditions = BagCondition::where('status', 1)->get();
        $brands = Brands::where('status', 1)->get();
        $bagColors = Color::where('status', 1)->get();
        $users = User::get(); // Users for attention_to
        $sizes = Size::get();
        $stitchings = Stitching::where('status', 'active')->get();
        $selectedLocation = $companyLocations->first() ?: null;
        $jobOrderNo = $selectedLocation ? self::generateUniqueNumber(date('Y-m-d'), $selectedLocation->id) : '';
        $jobOrderRequests = \App\Models\Production\JobOrderRequest\JobOrderRequest::with(['packingItems', 'jobOrders.packingItems', 'exportOrder'])
            ->whereIn('status', ['pending', 'partial'])
            ->latest()
            ->get()
            ->filter(function ($jor) {
                return $jor->remaining_kgs > 0.001;
            });

        $requestedJorId = request('job_order_request_id', request('request_id'));
        if ($requestedJorId && !$jobOrderRequests->contains('id', $requestedJorId)) {
            $extraJor = \App\Models\Production\JobOrderRequest\JobOrderRequest::with(['packingItems', 'jobOrders.packingItems', 'exportOrder'])->find($requestedJorId);
            if ($extraJor) {
                $jobOrderRequests->push($extraJor);
            }
        }

        foreach ($jobOrderRequests as $jor) {
            if ($jor->export_order_id && !$exportOrders->contains('id', $jor->export_order_id)) {
                $extraEo = $jor->exportOrder ?? \App\Models\Export\ExportOrder::find($jor->export_order_id);
                if ($extraEo) {
                    $exportOrders->push($extraEo);
                }
            }
        }

        $fumigationTypes = getFumigationTypes();

        return view('management.production.job_orders.create', compact(
            'exportOrders',
            'jobOrderRequests',
            'products',
            'bagProducts',
            'containerProtectionProducts',
            'inspectionCompanies',
            'fumigationCompanies',
            'fumigationTypes',
            'companyLocations',
            'arrivalLocations',
            'bagTypes',
            'bagConditions',
            'brands',
            'bagColors',
            'users',
            'cropYears',
            'sizes',
            'stitchings',
            'jobOrderNo',
            'selectedLocation'
        ));
    }

    public function store(JobOrderRequest $request)
    {
        DB::beginTransaction();

        try {
            $locationCode = CompanyLocation::where('id', $request->company_location_id)
                ->value('code');

            if ($request->export_order_id) {
                $eo = \App\Models\Export\ExportOrder::with(['jobOrders.packingItems', 'packingItems'])->findOrFail($request->export_order_id);
                $totalAllowedMt = $eo->packingItems->sum('metric_tons');
                $alreadyConsumedMt = $eo->jobOrders->sum(function ($jo) {
                    return $jo->packingItems->sum('metric_tons');
                });

                $currentRequestMt = collect($request->packing_items)->sum('metric_tons');

                if (($alreadyConsumedMt + $currentRequestMt) > ($totalAllowedMt + 0.001)) {
                    return response()->json([
                        'status' => 'error',
                        'message' => "Total Metric Tons ($currentRequestMt) exceeds the remaining capacity of Export Order (" . round($totalAllowedMt - $alreadyConsumedMt, 3) . " MT)."
                    ], 422);
                }
            }

            if ($request->job_order_request_id) {
                $jor = \App\Models\Production\JobOrderRequest\JobOrderRequest::with(['packingItems', 'jobOrders.packingItems'])->findOrFail($request->job_order_request_id);
                $totalAllowedKg = (float) $jor->total_kgs;
                $alreadyConsumedKg = (float) $jor->consumed_kgs;

                $currentJobOrderKg = 0;
                foreach ($request->packing_items as $item) {
                    $subItems = $item['sub_items'] ?? [];
                    if (!empty($subItems)) {
                        $sizeMap = Size::whereIn('id', collect($subItems)->pluck('bag_size_id')->filter()->unique()->values())->pluck('size', 'id');
                        $currentJobOrderKg += collect($subItems)->sum(function ($subItem) use ($sizeMap) {
                            $packingSize = $subItem['packing_size'] ?? ($sizeMap[$subItem['bag_size_id'] ?? null] ?? 0);
                            return ($subItem['no_of_bags'] ?? 0) * (float) $packingSize;
                        });
                    } else {
                        $currentJobOrderKg += (float)($item['total_kgs'] ?? ((float)($item['bag_size'] ?? 0) * (float)($item['no_of_bags'] ?? 0)));
                    }
                }

                if (($alreadyConsumedKg + $currentJobOrderKg) > ($totalAllowedKg + 0.01)) {
                    $remainingKg = max(0, $totalAllowedKg - $alreadyConsumedKg);
                    return response()->json([
                        'status' => 'error',
                        'message' => "Total Quantity (" . round($currentJobOrderKg, 2) . " KG) exceeds the remaining capacity of Job Order Request (" . round($remainingKg, 2) . " KG)."
                    ], 422);
                }
            }

            $firstPackingLocation = $request->packing_items[0]['company_location_id'] ?? $request->company_location_id;
            $uniqueJobNo = self::generateUniqueNumber($request->job_order_date, $firstPackingLocation);

            $jobOrderData = $request->only([
                'job_order_no',
                'job_order_date',
                'ref_no',
                'product_id',
                'remarks',
                'order_description',
                // 'delivery_date',
                'loading_date',
                'packing_description',
                'crop_year_id',
                'other_specifications',
                'export_order_id',
                'job_order_request_id',
            ]);

            if ($request->job_order_request_id && empty($jobOrderData['export_order_id'])) {
                $jorRecord = \App\Models\Production\JobOrderRequest\JobOrderRequest::find($request->job_order_request_id);
                if ($jorRecord && !empty($jorRecord->export_order_id)) {
                    $jobOrderData['export_order_id'] = $jorRecord->export_order_id;
                }
            }

            $jobOrderData['company_id'] = $request->company_id;
            $jobOrderData['job_order_no'] = $uniqueJobNo;
            $jobOrderData['company_location_id'] = $firstPackingLocation;
            $jobOrderData['attention_to'] = $request->attention_to ?? [];
            $jobOrderData['inspection_company_id'] = $request->inspection_company_id ?? [];
            // $jobOrderData['fumigation_company_id'] = json_encode($request->fumigation_company_id ?? []);
            $jobOrderData['arrival_locations'] = $request->arrival_locations ?? [];
            $jobOrderData['fumigation_type'] = $request->fumigation_type ?? ($request->packing_items[0]['fumigation_type'] ?? null);

            //  Reset am approval status and set am change made to 1
            $jobOrderData['am_approval_status'] = 'approved';
            $jobOrderData['am_change_made'] = 1;

            $jobOrder = JobOrder::create($jobOrderData);

            // Handle location details
            $locationDetails = $request->location_details ?? [];

            foreach ($request->packing_items as $pIndex => $item) {
                // Ensure packing item has company_location_id
                $item['company_location_id'] = $item['company_location_id'] ?? $request->company_location_id;
                $item['fumigation_type'] = $item['fumigation_type'] ?? null;

                // Extract sub-items if they exist
                $subItems = $item['sub_items'] ?? [];
                unset($item['sub_items']);

                // Fallback numeric fields to 0
                $item['extra_bags'] = $item['extra_bags'] ?? 0;
                $item['empty_bags'] = $item['empty_bags'] ?? 0;
                $item['empty_bags_percentage'] = $item['empty_bags_percentage'] ?? 0;
                $item['extra_bags_percentage'] = $item['extra_bags_percentage'] ?? 0;
                $item['min_weight_empty_bags'] = $item['min_weight_empty_bags'] ?? 0;
                $item['total_bags'] = $item['total_bags'] ?? 0;
                $item['total_kgs'] = $item['total_kgs'] ?? 0;
                $item['metric_tons'] = $item['metric_tons'] ?? 0;
                $item['no_of_containers'] = $item['no_of_containers'] ?? 0;
                $item['stuffing_in_container'] = $item['stuffing_in_container'] ?? 0;

                // Merge location details if exists
                $locationId = $item['company_location_id'] ?? null;
                if ($locationId && isset($locationDetails[$locationId])) {
                    $item['no_of_containers'] = $locationDetails[$locationId]['no_of_containers'] ?? 0;
                    $item['description'] = $locationDetails[$locationId]['description'] ?? null;
                    $item['location_instruction'] = $locationDetails[$locationId]['location_instruction'] ?? null;
                }

                // Calculate totals from sub-items
                if (!empty($subItems)) {
                    $totalBagsFromSubItems = collect($subItems)->sum('no_of_bags');
                    $sizeMap = Size::whereIn('id', collect($subItems)->pluck('bag_size_id')->filter()->unique()->values())->pluck('size', 'id');
                    $totalKgsFromSubItems = collect($subItems)->sum(function ($subItem) use ($sizeMap) {
                        $packingSize = $subItem['packing_size'] ?? ($sizeMap[$subItem['bag_size_id'] ?? null] ?? 0);
                        return ($subItem['no_of_bags'] ?? 0) * (float) $packingSize;
                    });

                    $item['total_bags'] = $totalBagsFromSubItems + ($item['extra_bags'] ?? 0) + ($item['empty_bags'] ?? 0);
                    $item['total_kgs'] = $totalKgsFromSubItems;
                    $item['metric_tons'] = $item['total_kgs'] / 1000;
                } else {
                    $item['total_bags'] = ($item['no_of_bags'] ?? 0) + ($item['extra_bags'] ?? 0) + ($item['empty_bags'] ?? 0);
                    $item['total_kgs'] = (float) ($item['bag_size'] ?? 0) * (float) ($item['no_of_bags'] ?? 0);
                    $item['metric_tons'] = $item['total_kgs'] / 1000;
                }

                $attachmentFile = $request->file("packing_items.{$pIndex}.attachment") ?? (isset($item['attachment']) && $item['attachment'] instanceof \Illuminate\Http\UploadedFile ? $item['attachment'] : null);
                if ($attachmentFile) {
                    $item['attachment'] = $attachmentFile->store('job_order_attachments', 'public');
                } elseif (!empty($item['old_attachment'])) {
                    $item['attachment'] = $item['old_attachment'];
                }
                unset($item['old_attachment']);

                // Create packing item
                $packingItem = $jobOrder->packingItems()->create($item);

                // Create sub-items
                if (!empty($subItems)) {
                    foreach ($subItems as $sIdx => $subItem) {
                        $subItem['empty_bags'] = $subItem['empty_bags'] ?? 0;
                        $subItem['empty_bags_percentage'] = $subItem['empty_bags_percentage'] ?? 0;
                        $subItem['extra_bags'] = $subItem['extra_bags'] ?? 0;
                        $subItem['extra_bags_percentage'] = $subItem['extra_bags_percentage'] ?? 0;
                        $subItem['empty_bag_weight'] = $subItem['empty_bag_weight'] ?? 0;
                        $subItem['no_of_primary_bags'] = $subItem['no_of_primary_bags'] ?? 0;
                        $subItem['total_bags'] = ($subItem['no_of_bags'] ?? 0) + ($subItem['extra_bags'] ?? 0) + ($subItem['empty_bags'] ?? 0);

                        if (!empty($subItem['bag_type_id']) && empty($subItem['bag_product_id'])) {
                            $bagType = BagType::find($subItem['bag_type_id']);
                            if ($bagType) {
                                $matchingProduct = Product::where('product_type', 'general_items')
                                    ->where(function ($q) use ($bagType) {
                                        $q->where('name', $bagType->name)
                                          ->orWhereRaw('LOWER(TRIM(name)) = ?', [strtolower(trim($bagType->name))]);
                                    })->first();
                                if ($matchingProduct) {
                                    $subItem['bag_product_id'] = $matchingProduct->id;
                                }
                            }
                        } elseif (!empty($subItem['bag_product_id']) && empty($subItem['bag_type_id'])) {
                            $product = Product::find($subItem['bag_product_id']);
                            if ($product) {
                                $matchingBagType = BagType::where('name', $product->name)
                                    ->orWhereRaw('LOWER(TRIM(name)) = ?', [strtolower(trim($product->name))])
                                    ->first();
                                if ($matchingBagType) {
                                    $subItem['bag_type_id'] = $matchingBagType->id;
                                }
                            }
                        }

                        if (empty($subItem['brand_id']) && !empty($packingItem->brand_id)) {
                            $subItem['brand_id'] = $packingItem->brand_id;
                        }

                        $subAttachmentFile = $request->file("packing_items.{$pIndex}.sub_items.{$sIdx}.attachment") ?? (isset($subItem['attachment']) && $subItem['attachment'] instanceof \Illuminate\Http\UploadedFile ? $subItem['attachment'] : null);
                        if ($subAttachmentFile) {
                            $subItem['attachment'] = $subAttachmentFile->store('job_order_attachments', 'public');
                        } elseif (!empty($subItem['old_attachment'])) {
                            $subItem['attachment'] = $subItem['old_attachment'];
                        }
                        unset($subItem['old_attachment']);

                        $packingItem->subItems()->create($subItem);
                    }
                }
            }

            foreach ($request->specifications as $spec) {
                $jobOrder->specifications()->create([
                    'product_slab_type_id' => $spec['product_slab_type_id'],
                    'spec_name' => $spec['spec_name'],
                    'spec_value' => $spec['spec_value'],
                    'uom' => $spec['uom'],
                    'value_type' => $spec['value_type']
                ]);
            }

            // Handle Container Protection & Packing Materials
            if ($request->has('container_protection_items') && !empty($request->container_protection_items)) {
                $containerProtectionData = [];
                foreach ($request->container_protection_items as $item) {
                    if (!empty($item['product_id']) && isset($item['quantity_per_container'])) {
                        $containerProtectionData[$item['product_id']] = [
                            'quantity_per_container' => $item['quantity_per_container'] ?? 0
                        ];
                    }
                }
                if (!empty($containerProtectionData)) {
                    $jobOrder->containerProtectionItems()->sync($containerProtectionData);
                }
            }

            if ($jobOrder->job_order_request_id) {
                $jor = \App\Models\Production\JobOrderRequest\JobOrderRequest::with(['packingItems', 'jobOrders.packingItems'])->find($jobOrder->job_order_request_id);
                if ($jor) {
                    $totalAllowedKg = (float) $jor->total_kgs;
                    $consumedKg = (float) $jor->consumed_kgs;
                    $newStatus = ($consumedKg >= ($totalAllowedKg - 0.01)) ? 'converted' : ($consumedKg > 0.001 ? 'partial' : 'pending');
                    $jor->update(['status' => $newStatus]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => 'Job Order created successfully.',
                'data' => $jobOrder
            ], 201);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Something went wrong: ' . $e->getMessage()
            ], 500);
        }
    }

    public function edit($id, Request $request)
    {
        // dd($request->all());
        // $jobOrder = JobOrder::with(['packingItems:where(company_location_id, $request->company_location_id)', 'specifications', 'product'])->findOrFail($id);
        $jobOrder = JobOrder::with([
            'exportOrder',
            'jobOrderRequest.exportOrder',
            'packingItems' => function ($query) use ($request) {
                $query->when($request->filled('company_location_id'), function ($q) use ($request) {
                    $q->where('company_location_id', $request->company_location_id);
                });
                $query->with(['subItems.bagType', 'subItems.bagProduct']);
            },
            'specifications',
            'product',
            'containerProtectionItems'
        ])->findOrFail($id);
        
        $exportOrders = \App\Models\Export\ExportOrder::with(['packingItems', 'jobOrders.packingItems'])
            ->where('am_approval_status', 'approved')
            ->whereNotIn('id', function($q) {
                $q->select('export_order_id')->from('export_order_addendums');
            })
            ->latest()
            ->get()
            ->filter(function ($eo) use ($jobOrder) {
                if ($jobOrder->export_order_id == $eo->id) {
                    return true;
                }
                $totalMt = $eo->packingItems->sum('metric_tons');
                $consumedMt = $eo->jobOrders->sum(function ($jo) {
                    return $jo->packingItems->sum('metric_tons');
                });
                return ($totalMt - $consumedMt) > 0;
            });

        $jobOrderRequests = \App\Models\Production\JobOrderRequest\JobOrderRequest::with(['packingItems', 'jobOrders.packingItems', 'exportOrder'])
            ->whereIn('status', ['pending', 'partial'])
            ->latest()
            ->get()
            ->filter(function ($jor) use ($jobOrder) {
                if ($jobOrder->job_order_request_id == $jor->id) {
                    return true;
                }
                return $jor->remaining_kgs > 0.001;
            });

        if ($jobOrder->job_order_request_id && !$jobOrderRequests->contains('id', $jobOrder->job_order_request_id)) {
            $currentJor = \App\Models\Production\JobOrderRequest\JobOrderRequest::with(['packingItems', 'jobOrders.packingItems', 'exportOrder'])->find($jobOrder->job_order_request_id);
            if ($currentJor) {
                $jobOrderRequests->push($currentJor);
            }
        }

        foreach ($jobOrderRequests as $jor) {
            if ($jor->export_order_id && !$exportOrders->contains('id', $jor->export_order_id)) {
                $extraEo = $jor->exportOrder ?? \App\Models\Export\ExportOrder::find($jor->export_order_id);
                if ($extraEo) {
                    $exportOrders->push($extraEo);
                }
            }
        }
            
        $products = getFinishGoodProducts($jobOrder->product_id);
        $inspectionCompanies = InspectionCompany::where('status', 'active')->get();
        $fumigationCompanies = FumigationCompany::where('status', 'active')->get();
        $user = auth()->user();
        $locationIds = getUserCurrentCompanyLocations();
        if ($user && ($user->user_type === 'super-admin' || empty($locationIds))) {
            $companyLocations = CompanyLocation::where('status', 'active')->get();
        } else {
            $companyLocations = CompanyLocation::whereIn('id', $locationIds)->where('status', 'active')->get();
        }
        $arrivalLocations = ArrivalLocation::where('status', 'active')->get();
        $cropYears = CropYear::where('status', 'active')->get();
        $bagTypes = BagType::where('status', 1)->get();
        $bagConditions = BagCondition::where('status', 1)->get();
        $brands = Brands::where('status', 1)->get();
        $bagColors = Color::get();
        $users = User::get();
        $bagProducts = Product::where('status', 1)->where('product_type', 'general_items')
            ->with('category')
            ->whereHas('category', function ($query) {
                $query->whereIn(strtolower('name'), ['bag', 'bags']);
            })
            ->get();
        // $sizes = Size::where('status', 'active')->get();
        $sizes = Size::get();
        $stitchings = Stitching::where('status', 'active')->get();
        $containerProtectionProducts = Product::where('status', 1)->where('product_type', 'general_items')
            ->with('category')
            ->whereHas('category', function ($query) {
                $query->whereIn(strtolower('name'), ['store & spare']);
            })
            ->get();
        // dd($bagColors);
        $fumigationTypes = getFumigationTypes();

        return view('management.production.job_orders.edit', compact(
            'exportOrders',
            'jobOrderRequests',
            'jobOrder',
            'products',
            'bagProducts',
            'containerProtectionProducts',
            'inspectionCompanies',
            'fumigationCompanies',
            'fumigationTypes',
            'companyLocations',
            'arrivalLocations',
            'bagTypes',
            'bagConditions',
            'brands',
            'bagColors',
            'users',
            'cropYears',
            'sizes',
            'stitchings'
        ));
    }


    public function update(JobOrderRequest $request, JobOrder $jobOrder)
    {
        DB::beginTransaction();

        try {
            $oldJobOrderRequestId = $jobOrder->job_order_request_id;

            $jobOrderData = $request->only([
                'job_order_no',
                'job_order_date',
                'ref_no',
                'product_id',
                'remarks',
                'order_description',
                // 'delivery_date',
                'loading_date',
                'packing_description',
                'crop_year_id',
                'other_specifications',
                'export_order_id',
                'job_order_request_id',
            ]);

            if ($request->filled('job_order_request_id') && empty($jobOrderData['export_order_id'])) {
                $jorRecord = \App\Models\Production\JobOrderRequest\JobOrderRequest::find($request->job_order_request_id);
                if ($jorRecord && !empty($jorRecord->export_order_id)) {
                    $jobOrderData['export_order_id'] = $jorRecord->export_order_id;
                }
            }

            // Ensure empty string foreign keys are saved as null
            $jobOrderData['job_order_request_id'] = $request->input('job_order_request_id') ?: null;
            $jobOrderData['export_order_id'] = $jobOrderData['export_order_id'] ?? ($request->input('export_order_id') ?: null);

            // $jobOrderData['location'] = $request->location;
            $firstPackingLocation = $request->packing_items[0]['company_location_id'] ?? $request->company_location_id;
            $jobOrderData['company_location_id'] = $firstPackingLocation;
            $jobOrderData['attention_to'] = $request->attention_to ?? [];
            $jobOrderData['inspection_company_id'] = $request->inspection_company_id ?? [];
            // $jobOrderData['fumigation_company_id'] = json_encode($request->fumigation_company_id ?? []);
            $jobOrderData['arrival_locations'] = $request->arrival_locations ?? [];
            $jobOrderData['fumigation_type'] = $request->fumigation_type ?? ($request->packing_items[0]['fumigation_type'] ?? null);
            //  Reset am approval status and set am change made to 1
            $jobOrderData['am_approval_status'] = 'approved';
            // $jobOrderData['am_approval_status'] = 'pending';
            $jobOrderData['am_change_made'] = 1;

            if ($request->export_order_id) {
                $eo = \App\Models\Export\ExportOrder::with(['jobOrders.packingItems', 'packingItems'])->findOrFail($request->export_order_id);
                $totalAllowedMt = $eo->packingItems->sum('metric_tons');
                // Exclude current job order from consumed calculation
                $alreadyConsumedMt = $eo->jobOrders->where('id', '!=', $jobOrder->id)->sum(function ($jo) {
                    return $jo->packingItems->sum('metric_tons');
                });

                $currentRequestMt = collect($request->packing_items)->sum('metric_tons');

                if (($alreadyConsumedMt + $currentRequestMt) > ($totalAllowedMt + 0.001)) {
                    return response()->json([
                        'status' => 'error',
                        'message' => "Total Metric Tons ($currentRequestMt) exceeds the remaining capacity of Export Order (" . round($totalAllowedMt - $alreadyConsumedMt, 3) . " MT)."
                    ], 422);
                }
            }

            $targetJobOrderRequestId = $request->job_order_request_id ?? $jobOrder->job_order_request_id;
            if ($targetJobOrderRequestId) {
                $jor = \App\Models\Production\JobOrderRequest\JobOrderRequest::with(['packingItems', 'jobOrders.packingItems'])->findOrFail($targetJobOrderRequestId);
                $totalAllowedKg = (float) $jor->total_kgs;
                $alreadyConsumedKg = (float) $jor->jobOrders->where('id', '!=', $jobOrder->id)->sum(function ($jo) {
                    return $jo->packingItems->sum('total_kgs');
                });

                $currentJobOrderKg = 0;
                foreach ($request->packing_items as $item) {
                    $subItems = $item['sub_items'] ?? [];
                    if (!empty($subItems)) {
                        $sizeMap = Size::whereIn('id', collect($subItems)->pluck('bag_size_id')->filter()->unique()->values())->pluck('size', 'id');
                        $currentJobOrderKg += collect($subItems)->sum(function ($subItem) use ($sizeMap) {
                            $packingSize = $subItem['packing_size'] ?? ($sizeMap[$subItem['bag_size_id'] ?? null] ?? 0);
                            return ($subItem['no_of_bags'] ?? 0) * (float) $packingSize;
                        });
                    } else {
                        $currentJobOrderKg += (float)($item['total_kgs'] ?? ((float)($item['bag_size'] ?? 0) * (float)($item['no_of_bags'] ?? 0)));
                    }
                }

                if (($alreadyConsumedKg + $currentJobOrderKg) > ($totalAllowedKg + 0.01)) {
                    $remainingKg = max(0, $totalAllowedKg - $alreadyConsumedKg);
                    return response()->json([
                        'status' => 'error',
                        'message' => "Total Quantity (" . round($currentJobOrderKg, 2) . " KG) exceeds the remaining capacity of Job Order Request (" . round($remainingKg, 2) . " KG)."
                    ], 422);
                }
            }

            $jobOrder->update($jobOrderData);

            // Delete existing packing items and their sub-items (cascade)
            $jobOrder->packingItems()->delete();

            // Handle location details
            $locationDetails = $request->location_details ?? [];

            foreach ($request->packing_items as $pIndex => $item) {
                // Ensure packing item has company_location_id
                $item['company_location_id'] = $item['company_location_id'] ?? $request->company_location_id;
                $item['fumigation_type'] = $item['fumigation_type'] ?? null;

                // Extract sub-items if they exist
                $subItems = $item['sub_items'] ?? [];
                unset($item['sub_items']);

                // Fallback numeric fields to 0
                $item['extra_bags'] = $item['extra_bags'] ?? 0;
                $item['empty_bags'] = $item['empty_bags'] ?? 0;
                $item['empty_bags_percentage'] = $item['empty_bags_percentage'] ?? 0;
                $item['extra_bags_percentage'] = $item['extra_bags_percentage'] ?? 0;
                $item['min_weight_empty_bags'] = $item['min_weight_empty_bags'] ?? 0;
                $item['total_bags'] = $item['total_bags'] ?? 0;
                $item['total_kgs'] = $item['total_kgs'] ?? 0;
                $item['metric_tons'] = $item['metric_tons'] ?? 0;
                $item['no_of_containers'] = $item['no_of_containers'] ?? 0;
                $item['stuffing_in_container'] = $item['stuffing_in_container'] ?? 0;

                // Merge location details if exists
                $locationId = $item['company_location_id'] ?? null;
                if ($locationId && isset($locationDetails[$locationId])) {
                    $item['no_of_containers'] = $locationDetails[$locationId]['no_of_containers'] ?? 0;
                    $item['description'] = $locationDetails[$locationId]['description'] ?? null;
                    $item['location_instruction'] = $locationDetails[$locationId]['location_instruction'] ?? null;
                }

                // Calculate totals from sub-items
                if (!empty($subItems)) {
                    $totalBagsFromSubItems = collect($subItems)->sum('no_of_bags');
                    $sizeMap = Size::whereIn('id', collect($subItems)->pluck('bag_size_id')->filter()->unique()->values())->pluck('size', 'id');
                    $totalKgsFromSubItems = collect($subItems)->sum(function ($subItem) use ($sizeMap) {
                        $packingSize = $subItem['packing_size'] ?? ($sizeMap[$subItem['bag_size_id'] ?? null] ?? 0);
                        return ($subItem['no_of_bags'] ?? 0) * (float) $packingSize;
                    });

                    $item['total_bags'] = $totalBagsFromSubItems + ($item['extra_bags'] ?? 0) + ($item['empty_bags'] ?? 0);
                    $item['total_kgs'] = $totalKgsFromSubItems;
                    $item['metric_tons'] = $item['total_kgs'] / 1000;
                } else {
                    $item['total_bags'] = ($item['no_of_bags'] ?? 0) + ($item['extra_bags'] ?? 0) + ($item['empty_bags'] ?? 0);
                    $item['total_kgs'] = (float) ($item['bag_size'] ?? 0) * (float) ($item['no_of_bags'] ?? 0);
                    $item['metric_tons'] = $item['total_kgs'] / 1000;
                }

                // Store bag_type_id as JSON array
                if (isset($item['bag_type_id']) && is_array($item['bag_type_id'])) {
                    $item['bag_type_id'] = json_encode($item['bag_type_id']);
                }

                $attachmentFile = $request->file("packing_items.{$pIndex}.attachment") ?? (isset($item['attachment']) && $item['attachment'] instanceof \Illuminate\Http\UploadedFile ? $item['attachment'] : null);
                if ($attachmentFile) {
                    $item['attachment'] = $attachmentFile->store('job_order_attachments', 'public');
                } elseif (!empty($item['old_attachment'])) {
                    $item['attachment'] = $item['old_attachment'];
                }
                unset($item['old_attachment']);

                // Create packing item
                $packingItem = $jobOrder->packingItems()->create($item);

                // Create sub-items
                if (!empty($subItems)) {
                    foreach ($subItems as $sIdx => $subItem) {
                        $subItem['empty_bags'] = $subItem['empty_bags'] ?? 0;
                        $subItem['empty_bags_percentage'] = $subItem['empty_bags_percentage'] ?? 0;
                        $subItem['extra_bags'] = $subItem['extra_bags'] ?? 0;
                        $subItem['extra_bags_percentage'] = $subItem['extra_bags_percentage'] ?? 0;
                        $subItem['empty_bag_weight'] = $subItem['empty_bag_weight'] ?? 0;
                        $subItem['no_of_primary_bags'] = $subItem['no_of_primary_bags'] ?? 0;
                        $subItem['total_bags'] = ($subItem['no_of_bags'] ?? 0) + ($subItem['extra_bags'] ?? 0) + ($subItem['empty_bags'] ?? 0);

                        if (!empty($subItem['bag_type_id']) && empty($subItem['bag_product_id'])) {
                            $bagType = BagType::find($subItem['bag_type_id']);
                            if ($bagType) {
                                $matchingProduct = Product::where('product_type', 'general_items')
                                    ->where(function ($q) use ($bagType) {
                                        $q->where('name', $bagType->name)
                                          ->orWhereRaw('LOWER(TRIM(name)) = ?', [strtolower(trim($bagType->name))]);
                                    })->first();
                                if ($matchingProduct) {
                                    $subItem['bag_product_id'] = $matchingProduct->id;
                                }
                            }
                        } elseif (!empty($subItem['bag_product_id']) && empty($subItem['bag_type_id'])) {
                            $product = Product::find($subItem['bag_product_id']);
                            if ($product) {
                                $matchingBagType = BagType::where('name', $product->name)
                                    ->orWhereRaw('LOWER(TRIM(name)) = ?', [strtolower(trim($product->name))])
                                    ->first();
                                if ($matchingBagType) {
                                    $subItem['bag_type_id'] = $matchingBagType->id;
                                }
                            }
                        }

                        if (empty($subItem['brand_id']) && !empty($packingItem->brand_id)) {
                            $subItem['brand_id'] = $packingItem->brand_id;
                        }

                        $subAttachmentFile = $request->file("packing_items.{$pIndex}.sub_items.{$sIdx}.attachment") ?? (isset($subItem['attachment']) && $subItem['attachment'] instanceof \Illuminate\Http\UploadedFile ? $subItem['attachment'] : null);
                        if ($subAttachmentFile) {
                            $subItem['attachment'] = $subAttachmentFile->store('job_order_attachments', 'public');
                        } elseif (!empty($subItem['old_attachment'])) {
                            $subItem['attachment'] = $subItem['old_attachment'];
                        }
                        unset($subItem['old_attachment']);

                        $packingItem->subItems()->create($subItem);
                    }
                }
            }

            $jobOrder->specifications()->delete();
            foreach ($request->specifications as $spec) {
                $jobOrder->specifications()->create([
                    'product_slab_type_id' => $spec['product_slab_type_id'],
                    'spec_name' => $spec['spec_name'],
                    'spec_value' => $spec['spec_value'],
                    'uom' => $spec['uom'],
                    'value_type' => $spec['value_type']
                ]);
            }

            // Handle Container Protection & Packing Materials
            if ($request->has('container_protection_items') && !empty($request->container_protection_items)) {
                $containerProtectionData = [];
                foreach ($request->container_protection_items as $item) {
                    if (!empty($item['product_id']) && isset($item['quantity_per_container'])) {
                        $containerProtectionData[$item['product_id']] = [
                            'quantity_per_container' => $item['quantity_per_container'] ?? 0
                        ];
                    }
                }
                // Sync will update existing or create new, and remove ones not in the array
                $jobOrder->containerProtectionItems()->sync($containerProtectionData);
            } else {
                // If no items provided, remove all
                $jobOrder->containerProtectionItems()->sync([]);
            }

            if ($jobOrder->job_order_request_id) {
                $jor = \App\Models\Production\JobOrderRequest\JobOrderRequest::with(['packingItems', 'jobOrders.packingItems'])->find($jobOrder->job_order_request_id);
                if ($jor) {
                    $totalAllowedKg = (float) $jor->total_kgs;
                    $consumedKg = (float) $jor->consumed_kgs;
                    $newStatus = ($consumedKg >= ($totalAllowedKg - 0.01)) ? 'converted' : ($consumedKg > 0.001 ? 'partial' : 'pending');
                    $jor->update(['status' => $newStatus]);
                }
            }

            if ($oldJobOrderRequestId && $oldJobOrderRequestId != $jobOrder->job_order_request_id) {
                $oldJor = \App\Models\Production\JobOrderRequest\JobOrderRequest::with(['packingItems', 'jobOrders.packingItems'])->find($oldJobOrderRequestId);
                if ($oldJor) {
                    $totalAllowedKg = (float) $oldJor->total_kgs;
                    $consumedKg = (float) $oldJor->consumed_kgs;
                    $oldStatus = ($consumedKg >= ($totalAllowedKg - 0.01)) ? 'converted' : ($consumedKg > 0.001 ? 'partial' : 'pending');
                    $oldJor->update(['status' => $oldStatus]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => 'Job Order updated successfully.',
                'data' => $jobOrder
            ], 200);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Something went wrong: ' . $e->getMessage()
            ], 500);
        }
    }

    public function updatebk17Nov(JobOrderRequest $request, JobOrder $jobOrder)
    {
        // Update main job order data
        $jobOrderData = $request->only([
            'job_order_no',
            'job_order_date',
            'ref_no',
            'product_id',
            'remarks',
            'order_description',
            'delivery_date',
            'loading_date',
            'packing_description',
            'crop_year_id',
            'other_specifications',
        ]);

        // JSON data update karein
        $jobOrderData['location'] = $request->location;
        $jobOrderData['attention_to'] = json_encode($request->attention_to ?? []);
        $jobOrderData['inspection_company_id'] = json_encode($request->inspection_company_id ?? []);
        $jobOrderData['fumigation_company_id'] = json_encode($request->fumigation_company_id ?? []);
        $jobOrderData['arrival_locations'] = json_encode($request->arrival_locations ?? []);

        $jobOrder->update($jobOrderData);

        // Update packing items - delete old and create new
        $jobOrder->packingItems()->delete();
        foreach ($request->packing_items as $item) {
            $jobOrder->packingItems()->create($item);
        }

        // Update specifications - delete old and create new
        $jobOrder->specifications()->delete();
        foreach ($request->specifications as $spec) {

            $jobOrder->specifications()->create([
                'product_slab_type_id' => $spec['product_slab_type_id'],
                'spec_name' => $spec['spec_name'],
                'spec_value' => $spec['spec_value'],
                'uom' => $spec['uom'],
                'value_type' => $spec['value_type']
            ]);
        }

        return response()->json([
            'success' => 'Job Order updated successfully.',
            'data' => $jobOrder
        ], 200);
    }

    public function updatebk(Request $request, JobOrder $jobOrder)
    {
        $request->validate([
            'job_order_no' => 'required|unique:job_orders,job_order_no,' . $jobOrder->id,
            'job_order_date' => 'required|date',
            'location' => 'required',
            'product_id' => 'required|exists:products,id',
            'packing_items' => 'required|array|min:1'
        ]);

        // Update main job order data
        $jobOrderData = $request->only([
            'job_order_no',
            'job_order_date',
            'location',
            'ref_no',
            'attention_to',
            'product_id',
            'remarks',
            'order_description',
            'inspection_company_id',
            'fumigation_company_id',
            'delivery_date',
            'loading_date',
            'packing_description'
        ]);

        $jobOrderData['arrival_locations'] = $request->arrival_locations;

        $jobOrder->update($jobOrderData);

        // Update packing items - delete old and create new
        $jobOrder->packingItems()->delete();
        foreach ($request->packing_items as $item) {
            $jobOrder->packingItems()->create($item);
        }

        return response()->json([
            'success' => 'Job Order updated successfully.',
            'data' => $jobOrder
        ], 200);
    }

    public function destroy($id)
    {
        $jobOrder = JobOrder::findOrFail($id);
        $jobOrderRequestId = $jobOrder->job_order_request_id;
        $jobOrder->delete();

        if ($jobOrderRequestId) {
            $jor = \App\Models\Production\JobOrderRequest\JobOrderRequest::with(['packingItems', 'jobOrders.packingItems'])->find($jobOrderRequestId);
            if ($jor) {
                $totalAllowedKg = (float) $jor->total_kgs;
                $consumedKg = (float) $jor->consumed_kgs;
                if ($consumedKg <= 0.001) {
                    $jor->update(['status' => 'pending']);
                } elseif ($consumedKg < ($totalAllowedKg - 0.01)) {
                    $jor->update(['status' => 'partial']);
                } else {
                    $jor->update(['status' => 'converted']);
                }
            }
        }

        return response()->json([
            'success' => 'Job Order deleted successfully.'
        ], 200);
    }

    // Get product specifications for selected product
    public function getProductSpecsbk($productId)
    {
        $specs = ProductSlab::with('slabType')
            ->where('product_id', $productId)
            ->where('status', 1)
            ->get()
            ->map(function ($slab) {
                return [
                    'spec_name' => $slab->slabType->name ?? '',
                    'spec_value' => $slab->deduction_value,
                    'uom' => $slab->slabType->uom ?? ''
                ];
            });

        return response()->json($specs);
    }



    public function getProductSpecs($productId)
    {
        $specs = ProductSlab::with('slabType')
            ->where('product_id', $productId)
            ->where('status', 1)
            ->get()
            ->groupBy('product_slab_type_id')
            ->map(function ($slabs) {
                // Pehla slab le rahe hain kyun ke har type ka ek hi slab hoga group mein
                $firstSlab = $slabs->first();
                return [
                    'id' => $firstSlab->slabType->id,
                    'spec_name' => $firstSlab->slabType->name ?? '',
                    'spec_value' => $firstSlab->deduction_value ?? 0,
                    'uom' => $firstSlab->slabType->qc_symbol ?? ''
                ];
            })
            ->values(); // Array keys reset karega

        return view('management.production.job_orders.partials.product_specs', compact('specs'));
    }

    public function getArrivalLocations(Request $request)
    {
        $locationIds = array_filter((array) $request->company_location_ids);
        $includeIds = array_filter((array) ($request->include_arrival_ids ?? $request->arrival_location_ids ?? []));

        $arrivalLocations = ArrivalLocation::where('status', 'active')
            ->when(!empty($locationIds) || !empty($includeIds), function ($query) use ($locationIds, $includeIds) {
                $query->where(function ($q) use ($locationIds, $includeIds) {
                    if (!empty($locationIds)) {
                        $q->whereIn('company_location_id', $locationIds);
                    }
                    if (!empty($includeIds)) {
                        $q->orWhereIn('id', $includeIds);
                    }
                });
            })
            ->get();

        return response()->json($arrivalLocations);
    }

    public function getExportOrderDetails($id)
    {
        $exportOrder = \App\Models\Export\ExportOrder::with([
            'specifications.productSlabType',
            'packingItems.subItems.bagType',
            'packingItems.bagType',
            'product',
            'jobOrders.packingItems'
        ])->findOrFail($id);

        $totalEoMt = $exportOrder->packingItems->sum('metric_tons');
        $consumedMt = $exportOrder->jobOrders->sum(function ($jo) {
            return $jo->packingItems->sum('metric_tons');
        });

        $remainingMt = max(0, $totalEoMt - $consumedMt);

        // Track how much "consumed" quantity is left to subtract from items sequentially
        $tempConsumed = $consumedMt;

        $packingItems = $exportOrder->packingItems->sortBy('id')->map(function ($item) use (&$tempConsumed) {
            $originalMt = (float) $item->metric_tons;
            $originalBags = (int) $item->no_of_bags;
            
            // Calculate how much was already taken from THIS packing item type area
            // Since we don't have direct links, we'll just subtract from the total sequentially
            // until tempConsumed is 0.
            $consumedFromThis = min($originalMt, $tempConsumed);
            $tempConsumed -= $consumedFromThis;

            $remainingMtInItem = max(0, $originalMt - $consumedFromThis);
            
            // Adjust no_of_bags proportionately if MT changed
            $remainingBagsInItem = $originalMt > 0 ? round(($remainingMtInItem / $originalMt) * $originalBags) : 0;

            $subItems = $item->subItems->sortBy('id')->map(function ($sub) use ($originalMt, $remainingMtInItem) {
                $subOriginalBags = (int) $sub->no_of_bags;
                $subRemainingBags = $originalMt > 0 ? round(($remainingMtInItem / $originalMt) * $subOriginalBags) : 0;

                return [
                    'bag_type_id'    => $sub->bag_type_id,
                    'bag_product_id' => $sub->bag_type_id,
                    'bag_type_name'  => $sub->bagType->name ?? '',
                    'bag_size_id'    => $sub->bag_size_id,
                    'stitching_id'   => $sub->stitching_id,
                    'bag_color_id'   => $sub->bag_color_id,
                    'brand_id'       => $sub->brand_id,
                    'thread_color_id' => $sub->thread_color_id,
                    'no_of_primary_bags' => $sub->no_of_primary_bags,
                    'no_of_bags'     => $subRemainingBags,
                    'empty_bags'     => $sub->empty_bags,
                    'extra_bags'     => $sub->extra_bags,
                    'extra_bags_percentage' => ($sub->no_of_bags > 0 && $sub->extra_bags > 0) ? round(($sub->extra_bags / $sub->no_of_bags) * 100, 2) : 0,
                    'empty_bag_weight' => $sub->empty_bag_weight,
                    'total_bags'     => $subRemainingBags + ($sub->extra_bags ?? 0) + ($sub->empty_bags ?? 0),
                ];
            })->values();

            return [
                'brand_id'          => $item->brand_id,
                'bag_product_id'    => $item->bag_type_id,
                'bag_type_name'     => $item->bagType->name ?? '',
                'bag_condition_id'  => $item->bag_condition_id,
                'bag_color_id'      => $item->bag_color_id,
                'thread_color_id'   => $item->thread_color_id,
                'stitching_id'      => $item->stitching_id,
                'bag_size'          => $item->bag_size,
                'no_of_bags'        => $remainingBagsInItem,
                'extra_bags'        => $item->extra_bags,
                'extra_bags_percentage' => $item->extra_bags_percentage ?? (($item->no_of_bags > 0 && $item->extra_bags > 0) ? round(($item->extra_bags / $item->no_of_bags) * 100, 2) : 0),
                'empty_bags'        => $item->empty_bags,
                'total_bags'        => $remainingBagsInItem + ($item->extra_bags ?? 0) + ($item->empty_bags ?? 0),
                'total_kgs'         => $remainingMtInItem * 1000,
                'metric_tons'       => $remainingMtInItem,
                'stuffing_in_container' => $item->stuffing_in_container,
                'no_of_containers'  => $item->no_of_containers,
                'min_weight_empty_bags' => $item->min_weight_empty_bags,
                'fumigation_company_id' => $item->fumigation_company_id,
                'sub_items'         => $subItems,
            ];
        })->values();

        return response()->json([
            'ref_no'              => $exportOrder->contract_no ?? $exportOrder->voucher_no,
            'product_id'          => $exportOrder->product_id,
            'other_specifications' => $exportOrder->other_specifications,
            'specifications'      => $exportOrder->specifications->map(function ($s) {
                return [
                    'product_slab_type_id' => $s->product_slab_type_id,
                    'spec_name'  => $s->spec_name,
                    'spec_value' => $s->spec_value,
                    'uom'        => $s->uom,
                    'value_type' => $s->value_type,
                    'product_slab_type' => $s->productSlabType ? ['name' => $s->productSlabType->name, 'qc_symbol' => $s->productSlabType->qc_symbol] : null,
                ];
            })->values(),
            'packing_items'       => $packingItems,
            'total_eo_mt'         => round($totalEoMt, 3),
            'consumed_mt'         => round($consumedMt, 3),
            'remaining_mt'        => round($remainingMt, 3),
        ]);
    }

    public static function generateUniqueNumber($dateParam = null, $locationParam = null)
    {
        try {
            $datePart = $dateParam ? Carbon::parse($dateParam)->format('m-d-Y') : date('m-d-Y');
        } catch (\Exception $e) {
            $datePart = date('m-d-Y');
        }

        if (empty($locationParam)) {
            $locationParam = request('company_location_id') ?? request('location_id') ?? auth()->user()?->company_location_id;
        }

        $locationCode = 'LOC';
        if ($locationParam) {
            if ($locationParam instanceof CompanyLocation) {
                $locationCode = $locationParam->code ?: 'LOC';
            } elseif (is_numeric($locationParam)) {
                $loc = CompanyLocation::find($locationParam);
                $locationCode = $loc?->code ?: 'LOC';
            } elseif (is_string($locationParam)) {
                $loc = CompanyLocation::where('code', $locationParam)->first();
                $locationCode = $loc ? $loc->code : $locationParam;
            }
        }

        $locationCode = strtoupper(trim($locationCode ?: 'LOC'));
        $prefix = $locationCode . '-JO-MIL';

        $searchPattern = $prefix . '-%-' . $datePart;

        $existing = JobOrder::withTrashed()
            ->where('job_order_no', 'like', $searchPattern)
            ->lockForUpdate()
            ->pluck('job_order_no')
            ->toArray();

        $maxNumber = 0;
        $regex = '/^' . preg_quote($prefix, '/') . '-(\d+)-' . preg_quote($datePart, '/') . '$/';
        foreach ($existing as $no) {
            if (preg_match($regex, $no, $matches)) {
                $num = (int) $matches[1];
                if ($num > $maxNumber) {
                    $maxNumber = $num;
                }
            }
        }

        $newNumber = $maxNumber + 1;
        $candidate = $prefix . '-' . str_pad($newNumber, 3, '0', STR_PAD_LEFT) . '-' . $datePart;

        while (in_array($candidate, $existing) || JobOrder::withTrashed()->where('job_order_no', $candidate)->exists()) {
            $newNumber++;
            $candidate = $prefix . '-' . str_pad($newNumber, 3, '0', STR_PAD_LEFT) . '-' . $datePart;
        }

        return $candidate;
    }

    public function getNumber(Request $request)
    {
        $dateParam = $request->job_order_date ?? $request->date ?? date('Y-m-d');
        $locationParam = $request->company_location_id ?? $request->location_id ?? null;
        $jobOrderNo = self::generateUniqueNumber($dateParam, $locationParam);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'job_order_no' => $jobOrderNo
            ]);
        }

        return $jobOrderNo;
    }
}
