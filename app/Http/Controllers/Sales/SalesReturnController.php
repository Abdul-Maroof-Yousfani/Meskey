<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Http\Requests\Sales\SaleReturnRequest;
use App\Models\Master\Customer;
use App\Models\Product;
use App\Models\Sales\ReceivingRequest;
use App\Models\Sales\SalesInvoice;
use App\Models\AuditLog;
use App\Models\Sales\SalesReturn;
use App\Models\User;
use App\Services\AuditLogService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SalesReturnController extends Controller
{
    private function getUserArrivalLocations()
    {
        $authUser = auth()->user();
        if (!$authUser) {
            return [];
        }
        $locations = getUserCurrentCompanyArrivalLocations();
        if (empty($locations) && $authUser->arrival_location_id) {
            $locations = [$authUser->arrival_location_id];
        }
        return $locations ?? [];
    }

    private function canUserAccessSalesReturn(SalesReturn $salesReturn): bool
    {
        $authUser = auth()->user();
        if (!$authUser) {
            return false;
        }
        if ($authUser->user_type === 'super-admin') {
            return true;
        }

        $userLocations = $this->getUserArrivalLocations();
        if (empty($userLocations)) {
            return false;
        }

        // Direct arrival_location_id check
        if ($salesReturn->arrival_location_id && in_array($salesReturn->arrival_location_id, $userLocations)) {
            return true;
        }

        // Check through receiving requests -> delivery challans
        $receivingRequests = $salesReturn->receiving_requests;
        if ($receivingRequests->isEmpty()) {
            $receivingRequests = ReceivingRequest::whereHas('salesReturn', function($q) use ($salesReturn) {
                $q->where('id', $salesReturn->id);
            })->get();
        }

        foreach ($receivingRequests as $rr) {
            $dc = $rr->deliveryChallan;
            if (!$dc) continue;

            $dcArrivals = !empty($dc->arrival_id)
                ? array_map('trim', explode(',', (string)$dc->arrival_id))
                : [];

            $ticketArrivals = $dc->delivery_challan_data
                ->pluck('loadingProgramItem.arrival_location_id')
                ->filter()
                ->map(fn($id) => (string)$id)
                ->unique()
                ->toArray();

            $allDcLocations = array_unique(array_merge($dcArrivals, $ticketArrivals));

            if (!empty(array_intersect($allDcLocations, array_map('strval', $userLocations)))) {
                return true;
            }
        }

        return false;
    }

    public function index() {
        abort_if(!canAccess('sales-return-list') && !auth()->user()->can('sales-return-list'), 403);
        return view('management.sales.sales-return.index');
    }

    public function create() {
        abort_if(!canAccess('sales-return-create') && !auth()->user()->can('sales-return-create'), 403);
        $customers = Customer::where("type", "local")->get();
        $items = Product::all();

        return view("management.sales.sales-return.create", compact("customers", "items"));
    }

    public function view(int $id) {
        abort_if(!canAccess('sales-return-list') && !auth()->user()->can('sales-return-list'), 403);
        $saleReturn = SalesReturn::with(["sale_return_data", "receiving_requests.deliveryChallan.delivery_challan_data.loadingProgramItem", "sale_invoices"])->findOrFail($id);
        abort_if(!$this->canUserAccessSalesReturn($saleReturn), 403, 'Unauthorized access to this Sales Return.');

        $customers = Customer::where("type", "local")->get();
        $items = Product::all();

        return view("management.sales.sales-return.view", compact("customers", "items", "saleReturn"));
    }

    public function edit(int $id) {
        abort_if(!canAccess('sales-return-create') && !auth()->user()->can('sales-return-create'), 403);
        $saleReturn = SalesReturn::with(["sale_return_data", "receiving_requests.deliveryChallan.delivery_challan_data.loadingProgramItem", "sale_invoices"])->findOrFail($id);
        abort_if(!$this->canUserAccessSalesReturn($saleReturn), 403, 'Unauthorized access to this Sales Return.');

        $customers = Customer::where("type", "local")->get();
        $items = Product::all();

        return view("management.sales.sales-return.edit", compact("customers", "items", "saleReturn"));
    }

    public function update(SaleReturnRequest $request, int $id) {
        abort_if(!canAccess('sales-return-create') && !auth()->user()->can('sales-return-create'), 403);
        DB::beginTransaction();
        $saleReturn = SalesReturn::with([
            "receiving_requests.deliveryChallan.delivery_challan_data.loadingProgramItem",
            "customer",
            "companyLocation",
            "arrivalLocation",
            "storageLocation",
            "sale_return_data.sale_invoice_data"
        ])->findOrFail($id);
        abort_if(!$this->canUserAccessSalesReturn($saleReturn), 403, 'Unauthorized access to this Sales Return.');
        $oldAuditValues = $this->buildSalesReturnAuditValues($saleReturn);

        $authUser = auth()->user();
        $isSuperAdmin = $authUser && $authUser->user_type === 'super-admin';
        $locations = $this->getUserArrivalLocations();

        if(in_array(strtolower($saleReturn->am_approval_status ?? ''), ['approved', 'rejected'])) {
            return response()->json([
                'error' => "Sales Return has been {$saleReturn->am_approval_status} and cannot be updated.",
                'message' => "This Sales Return has already been {$saleReturn->am_approval_status}. Further updates are not allowed from any screen."
            ], 422);
        }

        if (!$isSuperAdmin && $request->filled('arrival_location_id') && !in_array($request->arrival_location_id, $locations)) {
            return response()->json("You are not authorized to update a Sales Return for this arrival location.", 422);
        }

        try {
            $sale_invoices = is_array($request->si_no) ? $request->si_no : [$request->si_no];
            $sale_invoices = array_filter($sale_invoices);

            if (!empty($sale_invoices)) {
                $alreadyUsed = DB::table('sale_return_sale_invoice')
                    ->join('sales_return', 'sale_return_sale_invoice.sale_return_id', '=', 'sales_return.id')
                    ->where('sales_return.am_approval_status', '!=', 'rejected')
                    ->where('sales_return.id', '!=', $saleReturn->id)
                    ->whereIn('sale_return_sale_invoice.sale_invoice_id', $sale_invoices)
                    ->exists();

                if ($alreadyUsed) {
                    return response()->json("A Sales Return has already been created for this Receiving Request.", 422);
                }

                // Verify receiving request vehicle location
                if (!$isSuperAdmin) {
                    $selectedRrs = ReceivingRequest::with('deliveryChallan.delivery_challan_data.loadingProgramItem')
                        ->whereIn('id', $sale_invoices)
                        ->get();

                    foreach ($selectedRrs as $rr) {
                        $dc = $rr->deliveryChallan;
                        $dcArrivals = !empty($dc?->arrival_id)
                            ? array_map('trim', explode(',', (string)$dc->arrival_id))
                            : [];
                        $ticketArrivals = $dc?->delivery_challan_data
                            ->pluck('loadingProgramItem.arrival_location_id')
                            ->filter()
                            ->map(fn($tId) => (string)$tId)
                            ->unique()
                            ->toArray() ?? [];
                        $allDcLocations = array_unique(array_merge($dcArrivals, $ticketArrivals));

                        if (!empty($allDcLocations) && empty(array_intersect($allDcLocations, array_map('strval', $locations)))) {
                            return response()->json("The selected vehicle/Receiving Request (DC: {$rr->dc_no}) does not belong to your assigned arrival location.", 422);
                        }
                    }
                }
            }

            $validatedData = $request->validated();
            unset($validatedData['si_no']);

            $saleReturn->update([
                ...$validatedData,
                "contract_type" => "pohanch",
                "am_approval_status" => "pending",
                "am_change_made" => 1
            ]);
            $saleReturn->sale_return_data()->delete();

            foreach($request->item_id as $index => $item_id) {
                $saleReturn->sale_return_data()->create([
                    "quantity" => $request->qty[$index],
                    "sale_return_id" => $saleReturn->id,
                    "sale_invoice_data_id" => $request->si_data_id[$index],
                    "packing" => $request->packing[$index] ?? 0,
                    "no_of_bags" => $request->no_of_bags[$index] ?? 0,
                    "rate" => $request->rate[$index] ?? 0,
                    "gross_amount" => $request->gross_amount[$index] ?? 0,
                    "discount_percent" => $request->discount_percent[$index] ?? 0,
                    "discount_amount" => $request->discount_amount[$index] ?? 0,
                    "amount" => $request->amount[$index] ?? 0,
                    "gst_percentage" => $request->gst_percent[$index] ?? 0,
                    "gst_amount" => $request->gst_amount[$index] ?? 0,
                    "net_amount"  => $request->net_amount[$index] ?? 0,
                    "line_desc" => $request->line_desc[$index] ?? null,
                    "truck_no" => $request->truck_no[$index] ?? null,
                ]);
            }

            $syncData = [];
            $mainRrId = is_array($request->si_no) ? ($request->si_no[0] ?? null) : $request->si_no;

            foreach($request->item_id as $index => $item_id) {
                $si_id = $request->si_id[$index] ?? null;
                if (empty($si_id)) {
                    $si_id = $mainRrId;
                }
                if (!empty($si_id)) {
                    if (!isset($syncData[$si_id])) {
                        $syncData[$si_id] = ["qty" => 0];
                    }
                    $syncData[$si_id]["qty"] += $request->qty[$index];
                }
            }

            if (empty($syncData) && !empty($mainRrId)) {
                $totalQty = array_sum($request->qty ?? [0]);
                $syncData[$mainRrId] = ["qty" => $totalQty];
            }

            $saleReturn->receiving_requests()->sync($syncData);

            $saleReturn->refresh();
            $saleReturn->load([
                'customer',
                'companyLocation',
                'arrivalLocation',
                'storageLocation',
                'sale_return_data.sale_invoice_data',
                'receiving_requests'
            ]);
            $newAuditValues = $this->buildSalesReturnAuditValues($saleReturn);

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
                    $saleReturn,
                    'sales_return_updated',
                    "Sales Return #{$saleReturn->sr_no} updated.",
                    $changedOld,
                    $changedNew
                );
            }

            DB::commit();
            return response()->json("Sale Return has been updated");
        } catch(\Exception $e) {
            DB::rollBack();

            return response()->json($e->getMessage(), 500);
        }
    }

    public function get_sale_invoices(Request $request) {
        abort_if(!canAccess('sales-return-create') && !auth()->user()->can('sales-return-create'), 403);
        $customer_id = $request->customer_id;
        $locations_id = $request->location_id;
        $storage_id = $request->storage_id;
        $sales_return_id = $request->sales_return_id;

        // Company Location and Customer are mandatory
        if (!$customer_id || !$locations_id) {
            return [];
        }

        $authUser = auth()->user();
        $isSuperAdmin = $authUser && $authUser->user_type === 'super-admin';
        $locations = $this->getUserArrivalLocations();

        // Get RR IDs that already have an active/approved Sales Return (excluding current SR if on edit)
        $usedRrIds = DB::table('sale_return_sale_invoice')
            ->join('sales_return', 'sale_return_sale_invoice.sale_return_id', '=', 'sales_return.id')
            ->where('sales_return.am_approval_status', '!=', 'rejected')
            ->when($sales_return_id, function ($q) use ($sales_return_id) {
                $q->where('sales_return.id', '!=', $sales_return_id);
            })
            ->pluck('sale_return_sale_invoice.sale_invoice_id')
            ->toArray();

        $receiving_requests = ReceivingRequest::with(['deliveryChallan.delivery_challan_data.loadingProgramItem', 'items.deliveryChallanData'])
            ->where('am_approval_status', 'approved')
            ->whereNotIn('id', $usedRrIds)
            ->whereHas('deliveryChallan', function($q) use ($customer_id, $locations_id, $isSuperAdmin, $locations) {
                $q->where('sauda_type', 'pohanch')
                  ->where('customer_id', $customer_id)
                  ->where('location_id', $locations_id);
                if (!$isSuperAdmin) {
                    $q->where(function($sq) use ($locations) {
                        foreach ($locations as $locId) {
                            $sq->orWhereRaw("FIND_IN_SET(?, arrival_id)", [$locId]);
                        }
                        $sq->orWhereHas('delivery_challan_data.loadingProgramItem', function($ssq) use ($locations) {
                            $ssq->whereIn('arrival_location_id', $locations);
                        });
                    });
                }
            })
            ->latest()
            ->get();

        $data = [];

        foreach ($receiving_requests as $rr) {
            $truckInfo = $rr->truck_number ? " ({$rr->truck_number})" : "";
            $dateInfo = $rr->dc_date ? " - " . Carbon::parse($rr->dc_date)->format('d M Y') : "";
            $data[] = [
                "id" => $rr->id,
                "text" => "{$rr->dc_no}{$truckInfo}{$dateInfo}",
                "arrival_id" => $rr->deliveryChallan?->arrival_id
            ];
        }

        return $data;
    }

    public function getList(Request $request) {
        abort_if(!canAccess('sales-return-list') && !auth()->user()->can('sales-return-list'), 403);
        $perPage = $request->get('per_page', 25);
        $authUser = auth()->user();
        $isSuperAdmin = $authUser && $authUser->user_type === 'super-admin';
        $locations = $this->getUserArrivalLocations();

        // Eager load the inquiry + all its items + related product
        $SaleReturns = SalesReturn::with([
                "sale_return_data.sale_invoice_data",
                "receiving_requests.deliveryChallan.delivery_challan_data.loadingProgramItem"
            ])
            ->when(!$isSuperAdmin, function ($q) use ($locations) {
                $q->where(function($sq) use ($locations) {
                    $sq->whereIn('arrival_location_id', $locations)
                       ->orWhereHas('receiving_requests.deliveryChallan', function($dcQ) use ($locations) {
                           $dcQ->where(function($ssq) use ($locations) {
                               foreach ($locations as $locId) {
                                   $ssq->orWhereRaw("FIND_IN_SET(?, arrival_id)", [$locId]);
                               }
                               $ssq->orWhereHas('delivery_challan_data.loadingProgramItem', function($tssq) use ($locations) {
                                   $tssq->whereIn('arrival_location_id', $locations);
                               });
                           });
                       });
                });
            })
            ->when($request->filled('search'), function ($q) use ($request) {
                $searchTerm = '%' . strtolower($request->search) . '%';
                return $q->where(function ($sq) use ($searchTerm) {
                    $sq->whereRaw('LOWER(`reference_no`) LIKE ?', [$searchTerm])
                       ->orWhereRaw('LOWER(`sr_no`) LIKE ?', [$searchTerm]);
                });
            })
            ->latest()
            ->paginate($perPage);
       
        $groupedData = [];

        foreach ($SaleReturns as $SaleReturn) {
            $sr_no = $SaleReturn->sr_no;
            $items = $SaleReturn->sale_return_data;

            $itemRows = [];
            if ($items->isEmpty()) {
                $itemRows[] = [
                    'item_data' => (object)['item_id' => null, 'qty' => 0, 'rate' => 0, 'description' => 'No items'],
                    'item' => (object)['name' => 'N/A', 'unitOfMeasure' => (object)['name' => '']],
                ];
            } else {
                foreach ($items as $itemData) {
                    $item = $itemData->item ?? (object)[
                        'name' => 'N/A',
                        'unitOfMeasure' => (object)['name' => '']
                    ];
                    $itemRows[] = [
                        'item_data' => $itemData,
                        'si_data' => $itemData->sale_invoice_data,
                        'item' => $item,
                    ];
                }
            }

            $groupedData[] = [
                'sale_order' => $SaleReturn,
                'sr_no' => $sr_no,
                'created_by_id' => $SaleReturn->created_by ?? 1,
                'id' => $SaleReturn->id,
                'customer_id' => $SaleReturn->customer_id,
                'status' => $SaleReturn->am_approval_status,
                'created_at' => $SaleReturn->created_at,
                'customer' => 2,
                'rowspan' => max(count($itemRows), 1),
                'items' => $itemRows,
            ];
        }

        return view('management.sales.sales-return.getList', [
            'SaleReturns' => $SaleReturns,           // for pagination
            'groupedSalesReturns' => $groupedData,  // our grouped data
        ]);
    }

    public function getitems(Request $request) {
        abort_if(!canAccess('sales-return-create') && !auth()->user()->can('sales-return-create'), 403);
        $rr_ids = is_array($request->sale_invoice_ids) ? $request->sale_invoice_ids : [$request->sale_invoice_ids];
        $rr_ids = array_filter($rr_ids);

        $receiving_requests = ReceivingRequest::with(['items.deliveryChallanData', 'items.product', 'deliveryChallan'])
            ->whereIn('id', $rr_ids)
            ->get();

        $items = Product::select("id", "name")->get();

        $balances = [];

        return view("management.sales.sales-return.getItem", compact("receiving_requests", "items", "balances"));
    }

    public function getNumber(Request $request, $locationId = null, $invoiceDate = null)
    {
        abort_if(!canAccess('sales-return-create') && !auth()->user()->can('sales-return-create'), 403);
        $date = Carbon::parse($invoiceDate ?? $request->invoice_date)->format('Y-m-d');

        $prefix = 'SR-' . Carbon::parse($invoiceDate ?? $request->invoice_date)->format('Y-m-d');

        $latestInvoice = SalesReturn::where('sr_no', 'like', "$prefix-%")
            ->latest()
            ->first();

        $datePart = Carbon::parse($date)->format('Y-m-d');

        if ($latestInvoice) {
            $parts = explode('-', $latestInvoice->sr_no);
            $lastNumber = (int) end($parts);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        $sr_no = 'SR-' . $datePart . '-' . str_pad($newNumber, 3, '0', STR_PAD_LEFT);

        if (!$locationId && !$invoiceDate) {
            return response()->json([
                'success' => true,
                'sr_no' => $sr_no,
            ]);
        }

        return $sr_no;
    }

    public function store(SaleReturnRequest $request) {
        abort_if(!canAccess('sales-return-create') && !auth()->user()->can('sales-return-create'), 403);
        $authUser = auth()->user();
        $isSuperAdmin = $authUser && $authUser->user_type === 'super-admin';
        $locations = $this->getUserArrivalLocations();

        DB::beginTransaction();
        try {
            if (strtolower($request->contract_type) !== 'pohanch') {
                return response()->json("Sales Return is only allowed for Pohanch contracts.", 422);
            }

            $sale_invoices = is_array($request->si_no) ? $request->si_no : [$request->si_no];
            $sale_invoices = array_filter($sale_invoices);

            if (empty($sale_invoices)) {
                return response()->json("Please select a Receiving Request.", 422);
            }
           
            // Check if a Sales Return has already been created for this Receiving Request
            $alreadyUsed = DB::table('sale_return_sale_invoice')
                ->join('sales_return', 'sale_return_sale_invoice.sale_return_id', '=', 'sales_return.id')
                ->where('sales_return.am_approval_status', '!=', 'rejected')
                ->whereIn('sale_return_sale_invoice.sale_invoice_id', $sale_invoices)
                ->exists();

            if ($alreadyUsed) {
                return response()->json("A Sales Return has already been created for this Receiving Request.", 422);
            }

            // Check receiving request date & location
            $receiving_requests = ReceivingRequest::with('deliveryChallan.delivery_challan_data.loadingProgramItem')->whereIn("id", $sale_invoices)->get();
            foreach ($receiving_requests as $rr) {
                if($rr->dc_date && strtotime($rr->dc_date) > strtotime($request->date)) {
                    return response()->json("Backward date is not allowed. DC: " . $rr->dc_no . " Date: " . $rr->dc_date->format('Y-m-d'), 422);
                }

                if (!$isSuperAdmin) {
                    $dc = $rr->deliveryChallan;
                    $dcArrivals = !empty($dc?->arrival_id)
                        ? array_map('trim', explode(',', (string)$dc->arrival_id))
                        : [];
                    $ticketArrivals = $dc?->delivery_challan_data
                        ->pluck('loadingProgramItem.arrival_location_id')
                        ->filter()
                        ->map(fn($tId) => (string)$tId)
                        ->unique()
                        ->toArray() ?? [];
                    $allDcLocations = array_unique(array_merge($dcArrivals, $ticketArrivals));

                    if (!empty($allDcLocations) && empty(array_intersect($allDcLocations, array_map('strval', $locations)))) {
                        return response()->json("The selected vehicle/Receiving Request (DC: {$rr->dc_no}) does not belong to your assigned arrival location.", 422);
                    }
                }
            }

            if (!$isSuperAdmin && $request->filled('arrival_location_id') && !in_array($request->arrival_location_id, $locations)) {
                return response()->json("You are not authorized to create a Sales Return for this arrival location.", 422);
            }

            $validatedData = $request->validated();
            unset($validatedData['si_no']);

            $sale_return = SalesReturn::create([
                ...$validatedData,
                "contract_type" => "pohanch",
                "created_by" => auth()->user()->id
            ]);

            foreach($request->item_id as $index => $item_id) {
                $balance = sale_return_balance($request->si_data_id[$index]);

                // Balance check removed to allow exceeding balance for weight gain scenario
                

                $sale_return->sale_return_data()->create([
                    "quantity" => $request->qty[$index],
                    "sale_invoice_data_id" => $request->si_data_id[$index],
                    "packing" => $request->packing[$index] ?? 0,
                    "no_of_bags" => $request->no_of_bags[$index] ?? 0,
                    "rate" => $request->rate[$index] ?? 0,
                    "gross_amount" => $request->gross_amount[$index] ?? 0,
                    "discount_percent" => $request->discount_percent[$index] ?? 0,
                    "discount_amount" => $request->discount_amount[$index] ?? 0,
                    "amount" => $request->amount[$index] ?? 0,
                    "gst_percentage" => $request->gst_percent[$index] ?? 0,
                    "gst_amount" => $request->gst_amount[$index] ?? 0,
                    "net_amount"  => $request->net_amount[$index] ?? 0,
                    "line_desc" => $request->line_desc[$index] ?? null,
                    "truck_no" => $request->truck_no[$index] ?? null
                ]);
            }

            $syncData = [];
            $mainRrId = is_array($request->si_no) ? ($request->si_no[0] ?? null) : $request->si_no;

            foreach($request->item_id as $index => $item_id) {
                $si_id = $request->si_id[$index] ?? null;
                if (empty($si_id)) {
                    $si_id = $mainRrId;
                }
                if (!empty($si_id)) {
                    if (!isset($syncData[$si_id])) {
                        $syncData[$si_id] = ["qty" => 0];
                    }
                    $syncData[$si_id]["qty"] += $request->qty[$index];
                }
            }

            if (empty($syncData) && !empty($mainRrId)) {
                $totalQty = array_sum($request->qty ?? [0]);
                $syncData[$mainRrId] = ["qty" => $totalQty];
            }

            $sale_return->receiving_requests()->sync($syncData);

            $sale_return->load([
                'customer',
                'companyLocation',
                'arrivalLocation',
                'storageLocation',
                'sale_return_data.sale_invoice_data',
                'receiving_requests'
            ]);
            $newAuditValues = $this->buildSalesReturnAuditValues($sale_return);

            AuditLogService::log(
                $sale_return,
                'sales_return_created',
                "Created Sales Return #{$sale_return->sr_no}.",
                null,
                $newAuditValues
            );

            DB::commit();
            return response()->json("Sale Return has been created");
        } catch(\Exception $e) {
            DB::rollBack();

            return response()->json($e->getMessage(), 500);
        }
    }

    public function destroy(SalesReturn $sales_return) {
        abort_if(!canAccess('sales-return-create') && !auth()->user()->can('sales-return-create'), 403);
        abort_if(!$this->canUserAccessSalesReturn($sales_return), 403, 'Unauthorized access to this Sales Return.');

        if(in_array(strtolower($sales_return->am_approval_status ?? ''), ['approved', 'rejected'])) {
            return response()->json([
                'error' => "Sales Return has been {$sales_return->am_approval_status} and cannot be deleted.",
                'message' => "This Sales Return has already been {$sales_return->am_approval_status}. Changes or deletion are no longer allowed."
            ], 422);
        }

        $sales_return->load([
            'customer',
            'companyLocation',
            'arrivalLocation',
            'storageLocation',
            'sale_return_data.sale_invoice_data',
            'receiving_requests'
        ]);
        $oldValues = $this->buildSalesReturnAuditValues($sales_return);

        AuditLogService::log(
            $sales_return,
            'sales_return_deleted',
            "Sales Return #{$sales_return->sr_no} deleted.",
            $oldValues,
            null
        );

        $sales_return->delete();
        $sales_return->sale_return_data()->delete();

        return response()->json("Sale return has been deleted!");
    }

    /**
     * Build audit values array for Sales Return.
     */
    protected function buildSalesReturnAuditValues(SalesReturn $saleReturn): array
    {
        $items = $saleReturn->sale_return_data ?? collect();
        $receivingRequests = $saleReturn->receiving_requests ?? collect();

        $productNames = [];
        $truckNos = [];
        $bags = [];
        $packings = [];
        $quantities = [];
        $rates = [];
        $grossAmounts = [];
        $discountPercents = [];
        $discountAmounts = [];
        $gstPercents = [];
        $gstAmounts = [];
        $netAmounts = [];
        $lineDescriptions = [];

        foreach ($items as $item) {
            $name = $item->item_name;
            if ($name && $name !== 'N/A') {
                $productNames[] = $name;
            } elseif ($item->item?->name) {
                $productNames[] = $item->item->name;
            }

            if ($item->truck_no) {
                $truckNos[] = $item->truck_no;
            }
            if ($item->no_of_bags !== null) {
                $bags[] = $item->no_of_bags;
            }
            if ($item->packing !== null) {
                $packings[] = $item->packing;
            }
            if ($item->quantity !== null) {
                $quantities[] = $item->quantity;
            }
            if ($item->rate !== null) {
                $rates[] = $item->rate;
            }
            if ($item->gross_amount !== null) {
                $grossAmounts[] = $item->gross_amount;
            }
            if ($item->discount_percent !== null) {
                $discountPercents[] = $item->discount_percent;
            }
            if ($item->discount_amount !== null) {
                $discountAmounts[] = $item->discount_amount;
            }
            if ($item->gst_percentage !== null) {
                $gstPercents[] = $item->gst_percentage;
            }
            if ($item->gst_amount !== null) {
                $gstAmounts[] = $item->gst_amount;
            }
            if ($item->net_amount !== null) {
                $netAmounts[] = $item->net_amount;
            }
            if ($item->line_desc) {
                $lineDescriptions[] = $item->line_desc;
            }
        }

        $totalBags = $items->sum('no_of_bags');
        $totalQuantity = $items->sum('quantity');
        $totalGrossAmount = $items->sum('gross_amount');
        $totalDiscountAmount = $items->sum('discount_amount');
        $totalGstAmount = $items->sum('gst_amount');
        $totalNetAmount = $items->sum('net_amount');

        $rrDcNos = $receivingRequests->pluck('dc_no')->filter()->unique()->values()->all();

        return [
            'sr_no'                  => $saleReturn->sr_no,
            'date'                   => $saleReturn->date ? Carbon::parse($saleReturn->date)->format('Y-m-d') : null,
            'customer'               => $saleReturn->customer?->name ?? $saleReturn->customer_id,
            'reference_number'       => $saleReturn->reference_number,
            'contract_type'          => $saleReturn->contract_type ? ucfirst($saleReturn->contract_type) : null,
            'company_location'       => $saleReturn->companyLocation?->name ?? $saleReturn->company_location_id,
            'arrival_location'       => $saleReturn->arrivalLocation?->name ?? $saleReturn->arrival_location_id,
            'storage_location'       => $saleReturn->storageLocation?->name ?? $saleReturn->storage_location_id,
            'receiving_requests'     => !empty($rrDcNos) ? implode(', ', $rrDcNos) : null,
            'remarks'                => $saleReturn->remarks,
            'am_approval_status'     => $saleReturn->am_approval_status,
            'items_product'          => !empty($productNames) ? implode(', ', $productNames) : null,
            'items_truck_no'         => !empty($truckNos) ? implode(', ', $truckNos) : null,
            'items_bags'             => !empty($bags) ? implode(', ', $bags) : null,
            'items_packing'          => !empty($packings) ? implode(', ', $packings) : null,
            'items_quantity'         => !empty($quantities) ? implode(', ', $quantities) : null,
            'items_rate'             => !empty($rates) ? implode(', ', $rates) : null,
            'items_gross_amount'     => !empty($grossAmounts) ? implode(', ', $grossAmounts) : null,
            'items_discount_percent' => !empty($discountPercents) ? implode(', ', $discountPercents) : null,
            'items_discount_amount'  => !empty($discountAmounts) ? implode(', ', $discountAmounts) : null,
            'items_gst_percent'      => !empty($gstPercents) ? implode(', ', $gstPercents) : null,
            'items_gst_amount'       => !empty($gstAmounts) ? implode(', ', $gstAmounts) : null,
            'items_net_amount'       => !empty($netAmounts) ? implode(', ', $netAmounts) : null,
            'items_line_desc'        => !empty($lineDescriptions) ? implode('; ', $lineDescriptions) : null,
            'total_bags'             => $totalBags,
            'total_quantity'         => $totalQuantity,
            'total_gross_amount'     => $totalGrossAmount,
            'total_discount_amount'  => $totalDiscountAmount,
            'total_gst_amount'       => $totalGstAmount,
            'total_net_amount'       => $totalNetAmount,
            'items_count'            => $items->count(),
        ];
    }

    /**
     * Get audit logs for a sales return.
     */
    public function getAuditLogs(Request $request, $id)
    {
        abort_if(!canAccess('sales-return-list') && !auth()->user()->can('sales-return-list'), 403);

        $saleReturn = SalesReturn::with([
            'customer',
            'companyLocation',
            'arrivalLocation',
            'storageLocation',
            'sale_return_data.sale_invoice_data',
            'receiving_requests'
        ])->findOrFail($id);

        $logs = AuditLog::with('user')
            ->where(function ($q) use ($saleReturn) {
                $q->where(function ($sq) use ($saleReturn) {
                    $sq->whereIn('model_type', [SalesReturn::class, 'SalesReturn', 'App\Models\Sales\SalesReturn'])
                        ->where('model_id', $saleReturn->id);
                })
                ->orWhere('old_values', 'like', '%"sales_return_id":' . $saleReturn->id . '%')
                ->orWhere('old_values', 'like', '%"sales_return_id":"' . $saleReturn->id . '"%')
                ->orWhere('new_values', 'like', '%"sales_return_id":' . $saleReturn->id . '%')
                ->orWhere('new_values', 'like', '%"sales_return_id":"' . $saleReturn->id . '"%')
                ->orWhere('old_values', 'like', '%"sale_return_id":' . $saleReturn->id . '%')
                ->orWhere('old_values', 'like', '%"sale_return_id":"' . $saleReturn->id . '"%')
                ->orWhere('new_values', 'like', '%"sale_return_id":' . $saleReturn->id . '%')
                ->orWhere('new_values', 'like', '%"sale_return_id":"' . $saleReturn->id . '"%');
            })
            ->orderBy('created_at', 'desc')
            ->get();

        $hasCreateLog = $logs->contains(function ($l) {
            return str_contains($l->action, 'create') || str_contains($l->action, 'store');
        });

        if (!$hasCreateLog) {
            $virtualValues = $this->buildSalesReturnAuditValues($saleReturn);
            $virtualCreateLog = new AuditLog([
                'action' => 'sales_return_created',
                'description' => "Sales Return #{$saleReturn->sr_no} created.",
                'user_id' => $saleReturn->created_by,
                'created_at' => $saleReturn->created_at,
                'old_values' => null,
                'new_values' => $virtualValues,
            ]);
            $creator = User::find($saleReturn->created_by);
            $virtualCreateLog->setRelation('user', $creator);
            $logs->push($virtualCreateLog);
        }

        $fieldLabels = [
            'sr_no'                  => 'Sales Return No',
            'date'                   => 'Date',
            'customer'               => 'Customer',
            'reference_number'       => 'Reference Number',
            'contract_type'          => 'Contract Type',
            'company_location'       => 'Company Location',
            'arrival_location'       => 'Arrival Location',
            'storage_location'       => 'Storage Location',
            'receiving_requests'     => 'Selected Receiving Requests (DC)',
            'remarks'                => 'Remarks',
            'am_approval_status'     => 'Approval Status',
            'items_product'          => 'Products',
            'items_truck_no'         => 'Truck Numbers',
            'items_bags'             => 'No of Bags',
            'items_packing'          => 'Packing',
            'items_quantity'         => 'Quantities',
            'items_rate'             => 'Rates',
            'items_gross_amount'     => 'Gross Amounts',
            'items_discount_percent' => 'Discount %',
            'items_discount_amount'  => 'Discount Amounts',
            'items_gst_percent'      => 'GST %',
            'items_gst_amount'       => 'GST Amounts',
            'items_net_amount'       => 'Net Amounts',
            'items_line_desc'        => 'Item Descriptions',
            'total_bags'             => 'Total Bags',
            'total_quantity'         => 'Total Quantity',
            'total_gross_amount'     => 'Total Gross Amount',
            'total_discount_amount'  => 'Total Discount Amount',
            'total_gst_amount'       => 'Total GST Amount',
            'total_net_amount'       => 'Total Net Amount',
            'items_count'            => 'Items Count',
        ];

        $relationCache = [];
        $resolveRelationValue = function ($key, $val) use (&$relationCache) {
            if ($val === null || $val === '') {
                return null;
            }
            if (is_array($val)) {
                return json_encode($val);
            }

            if ($key === 'customer' && (is_numeric($val) || (is_string($val) && ctype_digit((string) $val)))) {
                $cacheKey = "cust_{$val}";
                if (isset($relationCache[$cacheKey])) return $relationCache[$cacheKey];
                $c = Customer::find($val);
                $res = $c ? $c->name : $val;
                $relationCache[$cacheKey] = $res;
                return $res;
            }

            if ($key === 'company_location' && (is_numeric($val) || (is_string($val) && ctype_digit((string) $val)))) {
                $cacheKey = "comp_loc_{$val}";
                if (isset($relationCache[$cacheKey])) return $relationCache[$cacheKey];
                $cl = \App\Models\Master\CompanyLocation::find($val);
                $res = $cl ? $cl->name : $val;
                $relationCache[$cacheKey] = $res;
                return $res;
            }

            if ($key === 'arrival_location' && (is_numeric($val) || (is_string($val) && ctype_digit((string) $val)))) {
                $cacheKey = "arr_loc_{$val}";
                if (isset($relationCache[$cacheKey])) return $relationCache[$cacheKey];
                $al = \App\Models\Master\ArrivalLocation::find($val);
                $res = $al ? $al->name : $val;
                $relationCache[$cacheKey] = $res;
                return $res;
            }

            if ($key === 'storage_location' && (is_numeric($val) || (is_string($val) && ctype_digit((string) $val)))) {
                $cacheKey = "stor_loc_{$val}";
                if (isset($relationCache[$cacheKey])) return $relationCache[$cacheKey];
                $sl = \App\Models\Master\ArrivalSubLocation::find($val);
                $res = $sl ? $sl->name : $val;
                $relationCache[$cacheKey] = $res;
                return $res;
            }

            if ($key === 'am_approval_status') {
                return ucfirst($val);
            }

            if (in_array($key, ['total_quantity', 'items_quantity']) && is_numeric($val)) {
                return number_format((float) $val, 2);
            }

            if (in_array($key, ['total_gross_amount', 'total_discount_amount', 'total_gst_amount', 'total_net_amount', 'total_amount', 'rate', 'gross_amount', 'net_amount']) && is_numeric($val)) {
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
                    // Avoid duplicate rows for single item where totals equal item values
                    if ($key === 'total_bags' && isset($new['items_bags']) && (string) $new['items_bags'] === (string) $newVal && (!isset($old['items_bags']) || (string) $old['items_bags'] === (string) $oldVal)) {
                        continue;
                    }
                    if ($key === 'total_quantity' && isset($new['items_quantity']) && (float) $new['items_quantity'] === (float) $newVal && (!isset($old['items_quantity']) || (float) $old['items_quantity'] === (float) $oldVal)) {
                        continue;
                    }
                    if ($key === 'total_net_amount' && isset($new['items_net_amount']) && (float) $new['items_net_amount'] === (float) $newVal && (!isset($old['items_net_amount']) || (float) $old['items_net_amount'] === (float) $oldVal)) {
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

        return view('management.sales.sales-return.partials.audit_logs_content', [
            'saleReturn' => $saleReturn,
            'logs' => $filteredLogs,
            'title' => "Audit Logs: Sales Return #{$saleReturn->sr_no}",
        ]);
    }
}
