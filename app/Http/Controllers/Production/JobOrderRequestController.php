<?php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use App\Http\Requests\Production\JobOrderRequestFormRequest;
use App\Models\Master\CropYear;
use App\Models\Master\Size;
use App\Models\Master\Stitching;
use App\Models\Production\JobOrderRequest\{
    JobOrderRequest,
    JobOrderRequestPackingItem,
    JobOrderRequestPackingSubItem,
    JobOrderRequestSpecification
};
use App\Models\Master\{InspectionCompany, FumigationCompany, CompanyLocation, ProductSlab, ArrivalLocation, Brands, Color};
use App\Models\{Product, BagCondition, BagType};
use Illuminate\Http\Request;
use App\Models\User;
use DB;
use Carbon\Carbon;

class JobOrderRequestController extends Controller
{
    public function index()
    {
        return view('management.production.job_order_requests.index');
    }

    public function getList(Request $request)
    {
        $requests = JobOrderRequest::with(['product', 'companyLocation', 'packingItems', 'creator'])
            ->when($request->filled('search'), function ($q) use ($request) {
                $searchTerm = '%' . $request->search . '%';
                return $q->where(function ($sq) use ($searchTerm) {
                    $sq->where('request_no', 'like', $searchTerm)
                        ->orWhere('ref_no', 'like', $searchTerm);
                });
            })
            ->when($request->filled('status'), function ($q) use ($request) {
                return $q->where('status', $request->status);
            })
            ->latest()
            ->paginate(request('per_page', 25));

        return view('management.production.job_order_requests.getList', compact('requests'));
    }

    public function create()
    {
        $exportOrders = \App\Models\Export\ExportOrder::with(['packingItems'])
            ->where('am_approval_status', 'approved')
            ->latest()
            ->get();

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
        $users = User::get();
        $sizes = Size::get();
        $stitchings = Stitching::where('status', 'active')->get();
        $selectedLocation = $companyLocations->first() ?: null;
        $requestNo = $selectedLocation ? self::generateUniqueNumber(date('Y-m-d'), $selectedLocation->id) : '';
        $fumigationTypes = getFumigationTypes();

        return view('management.production.job_order_requests.create', compact(
            'exportOrders',
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
            'requestNo',
            'selectedLocation'
        ));
    }

    public function store(JobOrderRequestFormRequest $request)
    {
        DB::beginTransaction();

        try {
            $firstPackingLocation = $request->packing_items[0]['company_location_id'] ?? $request->company_location_id;
            $uniqueRequestNo = self::generateUniqueNumber($request->request_date, $firstPackingLocation);

            $requestData = $request->only([
                'request_date',
                'company_location_id',
                'export_order_id',
                'ref_no',
                'product_id',
                'remarks',
                'order_description',
                'loading_date',
                'packing_description',
                'crop_year_id',
                'other_specifications',
            ]);

            $requestData['company_id'] = $request->company_id ?? (auth()->user()?->current_company_id ?? 1);
            $requestData['company_location_id'] = $firstPackingLocation;
            $requestData['request_no'] = $uniqueRequestNo;
            $requestData['attention_to'] = $request->attention_to ?? [];
            $requestData['inspection_company_id'] = $request->inspection_company_id ?? [];
            $requestData['arrival_locations'] = $request->arrival_locations ?? [];
            $requestData['fumigation_type'] = $request->fumigation_type ?? ($request->packing_items[0]['fumigation_type'] ?? null);
            $requestData['status'] = 'pending';
            $requestData['created_by'] = auth()->user()->id;

            $jobOrderRequest = JobOrderRequest::create($requestData);

            // Packing items
            foreach ($request->packing_items as $pIndex => $item) {
                $item['company_location_id'] = $item['company_location_id'] ?? $request->company_location_id;
                $item['fumigation_type'] = $item['fumigation_type'] ?? null;

                $subItems = $item['sub_items'] ?? [];
                unset($item['sub_items']);

                $item['extra_bags'] = $item['extra_bags'] ?? 0;
                $item['empty_bags'] = $item['empty_bags'] ?? 0;
                $item['empty_bags_percentage'] = $item['empty_bags_percentage'] ?? 0;
                $item['extra_bags_percentage'] = $item['extra_bags_percentage'] ?? 0;
                $item['min_weight_empty_bags'] = $item['min_weight_empty_bags'] ?? 0;
                $item['total_bags'] = ($item['no_of_bags'] ?? 0) + ($item['extra_bags'] ?? 0) + ($item['empty_bags'] ?? 0);
                $item['total_kgs'] = (float) ($item['bag_size'] ?? 0) * (float) ($item['no_of_bags'] ?? 0);
                $item['metric_tons'] = $item['total_kgs'] / 1000;
                $item['no_of_containers'] = $item['no_of_containers'] ?? 0;
                $item['stuffing_in_container'] = $item['stuffing_in_container'] ?? 0;

                if (!empty($subItems)) {
                    $totalBagsFromSubItems = collect($subItems)->sum('no_of_bags');
                    $item['total_bags'] = $totalBagsFromSubItems + ($item['extra_bags'] ?? 0) + ($item['empty_bags'] ?? 0);
                }

                $attachmentFile = $request->file("packing_items.{$pIndex}.attachment") ?? (isset($item['attachment']) && $item['attachment'] instanceof \Illuminate\Http\UploadedFile ? $item['attachment'] : null);
                if ($attachmentFile) {
                    $item['attachment'] = $attachmentFile->store('job_order_request_attachments', 'public');
                } elseif (!empty($item['old_attachment'])) {
                    $item['attachment'] = $item['old_attachment'];
                }
                unset($item['old_attachment']);

                $packingItem = $jobOrderRequest->packingItems()->create($item);

                if (!empty($subItems)) {
                    foreach ($subItems as $sIdx => $subItem) {
                        $subItem['empty_bags'] = $subItem['empty_bags'] ?? 0;
                        $subItem['empty_bags_percentage'] = $subItem['empty_bags_percentage'] ?? 0;
                        $subItem['extra_bags'] = $subItem['extra_bags'] ?? 0;
                        $subItem['empty_bag_weight'] = $subItem['empty_bag_weight'] ?? 0;
                        $subItem['extra_bags_percentage'] = $subItem['extra_bags_percentage'] ?? 0;
                        $subItem['total_bags'] = ($subItem['no_of_bags'] ?? 0) + $subItem['extra_bags'] + $subItem['empty_bags'];

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
                            $subItem['attachment'] = $subAttachmentFile->store('job_order_request_attachments', 'public');
                        } elseif (!empty($subItem['old_attachment'])) {
                            $subItem['attachment'] = $subItem['old_attachment'];
                        }
                        unset($subItem['old_attachment']);

                        $packingItem->subItems()->create($subItem);
                    }
                }
            }

            // Specifications
            if ($request->has('specifications') && is_array($request->specifications)) {
                foreach ($request->specifications as $spec) {
                    $jobOrderRequest->specifications()->create([
                        'product_slab_type_id' => $spec['product_slab_type_id'],
                        'spec_name' => $spec['spec_name'],
                        'spec_value' => $spec['spec_value'],
                        'uom' => $spec['uom'] ?? null,
                        'value_type' => $spec['value_type'] ?? 'min'
                    ]);
                }
            }

            // Container Protection items
            if ($request->has('container_protection_items') && is_array($request->container_protection_items)) {
                $containerProtectionData = [];
                foreach ($request->container_protection_items as $cpItem) {
                    if (!empty($cpItem['product_id']) && isset($cpItem['quantity_per_container'])) {
                        $containerProtectionData[$cpItem['product_id']] = [
                            'quantity_per_container' => (float) $cpItem['quantity_per_container']
                        ];
                    }
                }
                if (!empty($containerProtectionData)) {
                    $jobOrderRequest->containerProtectionItems()->sync($containerProtectionData);
                }
            }

            DB::commit();

            return response()->json([
                'success' => 'Job Order Request created successfully.',
                'data' => $jobOrderRequest
            ], 201);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Something went wrong: ' . $e->getMessage()
            ], 500);
        }
    }

    public function edit($id)
    {
        $jobOrderRequest = JobOrderRequest::with([
            'packingItems.subItems',
            'packingItems.companyLocation',
            'specifications',
            'product',
            'containerProtectionItems'
        ])->findOrFail($id);

        $exportOrders = \App\Models\Export\ExportOrder::with(['packingItems'])
            ->where('am_approval_status', 'approved')
            ->latest()
            ->get();

        $products = getFinishGoodProducts($jobOrderRequest->product_id);
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
        $users = User::get();
        $bagProducts = Product::where('status', 'active')->where('product_type', 'general_items')
            ->with('category')
            ->whereHas('category', function ($query) {
                $query->whereIn(strtolower('name'), ['bag', 'bags']);
            })
            ->get();
        $sizes = Size::get();
        $stitchings = Stitching::where('status', 'active')->get();
        $containerProtectionProducts = Product::where('status', 1)->where('product_type', 'general_items')
            ->with('category')
            ->whereHas('category', function ($query) {
                $query->whereIn(strtolower('name'), ['store & spare']);
            })
            ->get();
        $fumigationTypes = getFumigationTypes();

        return view('management.production.job_order_requests.edit', compact(
            'exportOrders',
            'jobOrderRequest',
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

    public function update($id, JobOrderRequestFormRequest $request)
    {
        DB::beginTransaction();

        try {
            $jobOrderRequest = JobOrderRequest::findOrFail($id);

            $requestData = $request->only([
                'request_date',
                'company_location_id',
                'export_order_id',
                'ref_no',
                'product_id',
                'remarks',
                'order_description',
                'loading_date',
                'packing_description',
                'crop_year_id',
                'other_specifications',
            ]);

            $firstPackingLocation = $request->packing_items[0]['company_location_id'] ?? $request->company_location_id;
            $requestData['company_location_id'] = $firstPackingLocation;
            $requestData['attention_to'] = $request->attention_to ?? [];
            $requestData['inspection_company_id'] = $request->inspection_company_id ?? [];
            $requestData['arrival_locations'] = $request->arrival_locations ?? [];
            $requestData['fumigation_type'] = $request->fumigation_type ?? ($request->packing_items[0]['fumigation_type'] ?? null);
            $requestData['updated_by'] = auth()->user()->id;

            $jobOrderRequest->update($requestData);

            // Replace packing items
            $jobOrderRequest->packingItems()->delete();

            foreach ($request->packing_items as $pIndex => $item) {
                $item['company_location_id'] = $item['company_location_id'] ?? $request->company_location_id;
                $item['fumigation_type'] = $item['fumigation_type'] ?? null;

                $subItems = $item['sub_items'] ?? [];
                unset($item['sub_items']);

                $item['extra_bags'] = $item['extra_bags'] ?? 0;
                $item['empty_bags'] = $item['empty_bags'] ?? 0;
                $item['empty_bags_percentage'] = $item['empty_bags_percentage'] ?? 0;
                $item['extra_bags_percentage'] = $item['extra_bags_percentage'] ?? 0;
                $item['min_weight_empty_bags'] = $item['min_weight_empty_bags'] ?? 0;
                $item['total_bags'] = ($item['no_of_bags'] ?? 0) + ($item['extra_bags'] ?? 0) + ($item['empty_bags'] ?? 0);
                $item['total_kgs'] = (float) ($item['bag_size'] ?? 0) * (float) ($item['no_of_bags'] ?? 0);
                $item['metric_tons'] = $item['total_kgs'] / 1000;
                $item['no_of_containers'] = $item['no_of_containers'] ?? 0;
                $item['stuffing_in_container'] = $item['stuffing_in_container'] ?? 0;

                if (!empty($subItems)) {
                    $totalBagsFromSubItems = collect($subItems)->sum('no_of_bags');
                    $item['total_bags'] = $totalBagsFromSubItems + ($item['extra_bags'] ?? 0) + ($item['empty_bags'] ?? 0);
                }

                $attachmentFile = $request->file("packing_items.{$pIndex}.attachment") ?? (isset($item['attachment']) && $item['attachment'] instanceof \Illuminate\Http\UploadedFile ? $item['attachment'] : null);
                if ($attachmentFile) {
                    $item['attachment'] = $attachmentFile->store('job_order_request_attachments', 'public');
                } elseif (!empty($item['old_attachment'])) {
                    $item['attachment'] = $item['old_attachment'];
                }
                unset($item['old_attachment']);

                $packingItem = $jobOrderRequest->packingItems()->create($item);

                if (!empty($subItems)) {
                    foreach ($subItems as $sIdx => $subItem) {
                        $subItem['empty_bags'] = $subItem['empty_bags'] ?? 0;
                        $subItem['empty_bags_percentage'] = $subItem['empty_bags_percentage'] ?? 0;
                        $subItem['extra_bags'] = $subItem['extra_bags'] ?? 0;
                        $subItem['empty_bag_weight'] = $subItem['empty_bag_weight'] ?? 0;
                        $subItem['extra_bags_percentage'] = $subItem['extra_bags_percentage'] ?? 0;
                        $subItem['total_bags'] = ($subItem['no_of_bags'] ?? 0) + $subItem['extra_bags'] + $subItem['empty_bags'];

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
                            $subItem['attachment'] = $subAttachmentFile->store('job_order_request_attachments', 'public');
                        } elseif (!empty($subItem['old_attachment'])) {
                            $subItem['attachment'] = $subItem['old_attachment'];
                        }
                        unset($subItem['old_attachment']);

                        $packingItem->subItems()->create($subItem);
                    }
                }
            }

            // Replace specifications
            $jobOrderRequest->specifications()->delete();
            if ($request->has('specifications') && is_array($request->specifications)) {
                foreach ($request->specifications as $spec) {
                    $jobOrderRequest->specifications()->create([
                        'product_slab_type_id' => $spec['product_slab_type_id'],
                        'spec_name' => $spec['spec_name'],
                        'spec_value' => $spec['spec_value'],
                        'uom' => $spec['uom'] ?? null,
                        'value_type' => $spec['value_type'] ?? 'min'
                    ]);
                }
            }

            // Sync container protection items
            $containerProtectionData = [];
            if ($request->has('container_protection_items') && is_array($request->container_protection_items)) {
                foreach ($request->container_protection_items as $cpItem) {
                    if (!empty($cpItem['product_id']) && isset($cpItem['quantity_per_container'])) {
                        $containerProtectionData[$cpItem['product_id']] = [
                            'quantity_per_container' => (float) $cpItem['quantity_per_container']
                        ];
                    }
                }
            }
            $jobOrderRequest->containerProtectionItems()->sync($containerProtectionData);

            DB::commit();

            return response()->json([
                'success' => 'Job Order Request updated successfully.',
                'data' => $jobOrderRequest
            ], 200);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Something went wrong: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        $request = JobOrderRequest::with([
            'companyLocation',
            'product',
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
            'specifications.productSlabType',
            'containerProtectionItems'
        ])->findOrFail($id);

        $attentionToIds = [];
        if (is_string($request->attention_to)) {
            $attentionToIds = json_decode($request->attention_to, true) ?? [];
        } elseif (is_array($request->attention_to)) {
            $attentionToIds = $request->attention_to;
        }

        $users = User::whereIn('id', $attentionToIds)->get();

        return view('management.production.job_order_requests.view_job_order_request', compact('request', 'users'));
    }

    public function destroy($id)
    {
        try {
            $jobOrderRequest = JobOrderRequest::findOrFail($id);
            if ($jobOrderRequest->status === 'converted') {
                return response()->json([
                    'error' => 'Cannot delete request as it has already been converted to a Job Order.'
                ], 422);
            }

            $jobOrderRequest->delete();

            return response()->json([
                'success' => 'Job Order Request deleted successfully.'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Something went wrong: ' . $e->getMessage()
            ], 500);
        }
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
        $prefix = $locationCode . '-JOR-MIL';

        $searchPattern = $prefix . '-%-' . $datePart;

        $existing = JobOrderRequest::withTrashed()
            ->where('request_no', 'like', $searchPattern)
            ->lockForUpdate()
            ->pluck('request_no')
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

        while (in_array($candidate, $existing) || JobOrderRequest::withTrashed()->where('request_no', $candidate)->exists()) {
            $newNumber++;
            $candidate = $prefix . '-' . str_pad($newNumber, 3, '0', STR_PAD_LEFT) . '-' . $datePart;
        }

        return $candidate;
    }

    public function getNumber(Request $request)
    {
        $dateParam = $request->request_date ?? $request->date ?? date('Y-m-d');
        $locationParam = $request->company_location_id ?? $request->location_id ?? null;
        $requestNo = self::generateUniqueNumber($dateParam, $locationParam);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'request_no' => $requestNo
            ]);
        }

        return $requestNo;
    }

    public function getRequestDetails($id)
    {
        $jobOrderRequest = JobOrderRequest::with([
            'exportOrder',
            'packingItems.subItems',
            'specifications.productSlabType',
            'containerProtectionItems',
            'jobOrders.packingItems'
        ])->findOrFail($id);

        $attentionTo = $jobOrderRequest->attention_to;
        if (is_string($attentionTo)) {
            $decoded = json_decode($attentionTo, true);
            $attentionTo = is_string($decoded) ? json_decode($decoded, true) : $decoded;
        }
        $attentionTo = collect($attentionTo ?? [])->flatten()->map(fn($v) => (string)$v)->values()->all();

        $inspectionCompanies = $jobOrderRequest->inspection_company_id;
        if (is_string($inspectionCompanies)) {
            $decoded = json_decode($inspectionCompanies, true);
            $inspectionCompanies = is_string($decoded) ? json_decode($decoded, true) : $decoded;
        }
        $inspectionCompanies = collect($inspectionCompanies ?? [])->flatten()->map(fn($v) => (string)$v)->values()->all();

        $arrivalLocs = $jobOrderRequest->arrival_locations;
        if (is_string($arrivalLocs)) {
            $decoded = json_decode($arrivalLocs, true);
            $arrivalLocs = is_string($decoded) ? json_decode($decoded, true) : $decoded;
        }
        $arrivalLocations = collect($arrivalLocs ?? [])->flatten()->map(fn($v) => (string)$v)->values()->all();

        $consumedKg = (float) $jobOrderRequest->consumed_kgs;
        $tempConsumed = $consumedKg;

        $packingItems = $jobOrderRequest->packingItems->sortBy('id')->map(function ($pi) use (&$tempConsumed, $jobOrderRequest) {
            $bagSize = (float) $pi->bag_size;
            $originalTotalKg = (float) $pi->total_kgs ?: ($bagSize * (int) $pi->no_of_bags);
            $originalBags = (int) $pi->no_of_bags;

            $consumedFromThis = min($originalTotalKg, $tempConsumed);
            $tempConsumed -= $consumedFromThis;

            $remainingKgInItem = max(0, $originalTotalKg - $consumedFromThis);
            $remainingBagsInItem = $bagSize > 0 ? round($remainingKgInItem / $bagSize) : 0;
            $remainingTotalKgs = $bagSize * $remainingBagsInItem;

            $fumigationCompanies = is_string($pi->fumigation_company_id)
                ? (json_decode($pi->fumigation_company_id, true) ?? [])
                : ($pi->fumigation_company_id ?? []);

            $subItems = $pi->subItems->map(function ($sub) use ($originalTotalKg, $remainingKgInItem) {
                $subOriginalBags = (int) $sub->no_of_bags;
                $subRemainingBags = $originalTotalKg > 0 ? round(($remainingKgInItem / $originalTotalKg) * $subOriginalBags) : 0;

                return [
                    'bag_type_id'             => $sub->bag_type_id,
                    'bag_product_id'          => $sub->bag_product_id,
                    'bag_size_id'             => $sub->bag_size_id,
                    'no_of_primary_bags'      => $sub->no_of_primary_bags,
                    'packing_size'            => $sub->packing_size,
                    'no_of_bags'              => $subRemainingBags,
                    'empty_bags'              => $sub->empty_bags,
                    'extra_bags'              => $sub->extra_bags,
                    'extra_bags_percentage'   => $sub->extra_bags_percentage,
                    'empty_bag_weight'        => $sub->empty_bag_weight,
                    'total_bags'              => $subRemainingBags + ($sub->extra_bags ?? 0) + ($sub->empty_bags ?? 0),
                    'stitching_id'            => $sub->stitching_id,
                    'bag_color_id'            => $sub->bag_color_id,
                    'brand_id'                => $sub->brand_id,
                    'thread_color_id'         => $sub->thread_color_id,
                    'attachment'              => $sub->attachment ? asset('storage/' . $sub->attachment) : null,
                    'attachment_path'         => $sub->attachment,
                ];
            })->values();

            return [
                'company_location_id'   => $pi->company_location_id,
                'bag_product_id'        => $pi->bag_product_id,
                'bag_condition_id'      => $pi->bag_condition_id,
                'bag_size'              => $bagSize,
                'no_of_bags'            => $remainingBagsInItem,
                'extra_bags'            => $pi->extra_bags,
                'extra_bags_percentage' => $pi->extra_bags_percentage,
                'empty_bags'            => $pi->empty_bags,
                'total_bags'            => $remainingBagsInItem + ($pi->extra_bags ?? 0) + ($pi->empty_bags ?? 0),
                'total_kgs'             => $remainingTotalKgs,
                'metric_tons'           => round($remainingTotalKgs / 1000, 3),
                'stuffing_in_container' => $pi->stuffing_in_container,
                'no_of_containers'      => $pi->no_of_containers,
                'brand_id'              => $pi->brand_id,
                'bag_color_id'          => $pi->bag_color_id,
                'thread_color_id'       => $pi->thread_color_id,
                'stitching_id'          => $pi->stitching_id,
                'min_weight_empty_bags' => $pi->min_weight_empty_bags,
                'delivery_date'         => $pi->delivery_date ? Carbon::parse($pi->delivery_date)->format('Y-m-d') : null,
                'fumigation_company_id' => $fumigationCompanies,
                'fumigation_type'       => $pi->fumigation_type ?: $jobOrderRequest->fumigation_type,
                'attachment'            => $pi->attachment ? asset('storage/' . $pi->attachment) : null,
                'attachment_path'         => $pi->attachment,
                'sub_items'             => $subItems,
            ];
        })->values();

        $activePackingItems = $packingItems->filter(function ($item) {
            return $item['no_of_bags'] > 0 || $item['total_kgs'] > 0;
        })->values();

        if ($activePackingItems->isNotEmpty()) {
            $packingItems = $activePackingItems;
        }

        $containerProtectionItems = $jobOrderRequest->containerProtectionItems->map(function ($cp) {
            return [
                'product_id'             => $cp->id,
                'quantity_per_container' => $cp->pivot->quantity_per_container ?? 0,
            ];
        })->values();

        return response()->json([
            'id'                     => $jobOrderRequest->id,
            'request_no'             => $jobOrderRequest->request_no,
            'total_request_kgs'      => (float) $jobOrderRequest->total_kgs,
            'total_request_mt'       => (float) $jobOrderRequest->total_metric_tons,
            'consumed_kgs'           => (float) $jobOrderRequest->consumed_kgs,
            'remaining_kgs'          => (float) $jobOrderRequest->remaining_kgs,
            'remaining_mt'           => round((float) $jobOrderRequest->remaining_kgs / 1000, 3),
            'company_location_id'    => $jobOrderRequest->packingItems->first()?->company_location_id ?? $jobOrderRequest->company_location_id,
            'export_order_id'        => $jobOrderRequest->export_order_id,
            'export_order_voucher_no' => $jobOrderRequest->exportOrder->voucher_no ?? '',
            'ref_no'                 => $jobOrderRequest->ref_no,
            'attention_to'           => $attentionTo,
            'product_id'             => $jobOrderRequest->product_id,
            'crop_year_id'           => $jobOrderRequest->crop_year_id,
            'remarks'                => $jobOrderRequest->remarks,
            'order_description'      => $jobOrderRequest->order_description,
            'other_specifications'   => $jobOrderRequest->other_specifications,
            'inspection_company_id'  => $inspectionCompanies,
            'loading_date'           => $jobOrderRequest->loading_date ? Carbon::parse($jobOrderRequest->loading_date)->format('Y-m-d') : null,
            'arrival_locations'      => $arrivalLocations,
            'packing_description'    => $jobOrderRequest->packing_description,
            'specifications'         => $jobOrderRequest->specifications->map(function ($s) {
                return [
                    'product_slab_type_id' => $s->product_slab_type_id,
                    'spec_name'            => $s->spec_name,
                    'spec_value'           => $s->spec_value,
                    'uom'                  => $s->uom,
                    'value_type'           => $s->value_type,
                    'product_slab_type'    => $s->productSlabType ? ['name' => $s->productSlabType->name, 'qc_symbol' => $s->productSlabType->qc_symbol] : null,
                ];
            })->values(),
            'packing_items'          => $packingItems,
            'container_protection_items' => $containerProtectionItems,
        ]);
    }
}
