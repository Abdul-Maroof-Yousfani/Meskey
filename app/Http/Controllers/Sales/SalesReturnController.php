<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Http\Requests\Sales\SaleReturnRequest;
use App\Models\Master\Customer;
use App\Models\Product;
use App\Models\Sales\ReceivingRequest;
use App\Models\Sales\SalesInvoice;
use App\Models\Sales\SalesReturn;
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
        abort_if(!canAccess('sales-return') && !auth()->user()->can('sales-return'), 403);
        return view('management.sales.sales-return.index');
    }

    public function create() {
        abort_if(!canAccess('sales-return') && !auth()->user()->can('sales-return'), 403);
        $customers = Customer::where("type", "local")->get();
        $items = Product::all();

        return view("management.sales.sales-return.create", compact("customers", "items"));
    }

    public function view(int $id) {
        abort_if(!canAccess('sales-return') && !auth()->user()->can('sales-return'), 403);
        $saleReturn = SalesReturn::with(["sale_return_data", "receiving_requests.deliveryChallan.delivery_challan_data.loadingProgramItem", "sale_invoices"])->findOrFail($id);
        abort_if(!$this->canUserAccessSalesReturn($saleReturn), 403, 'Unauthorized access to this Sales Return.');

        $customers = Customer::where("type", "local")->get();
        $items = Product::all();

        return view("management.sales.sales-return.view", compact("customers", "items", "saleReturn"));
    }

    public function edit(int $id) {
        abort_if(!canAccess('sales-return') && !auth()->user()->can('sales-return'), 403);
        $saleReturn = SalesReturn::with(["sale_return_data", "receiving_requests.deliveryChallan.delivery_challan_data.loadingProgramItem", "sale_invoices"])->findOrFail($id);
        abort_if(!$this->canUserAccessSalesReturn($saleReturn), 403, 'Unauthorized access to this Sales Return.');

        $customers = Customer::where("type", "local")->get();
        $items = Product::all();

        return view("management.sales.sales-return.edit", compact("customers", "items", "saleReturn"));
    }

    public function update(SaleReturnRequest $request, int $id) {
        abort_if(!canAccess('sales-return') && !auth()->user()->can('sales-return'), 403);
        DB::beginTransaction();
        $saleReturn = SalesReturn::with(["receiving_requests.deliveryChallan.delivery_challan_data.loadingProgramItem"])->findOrFail($id);
        abort_if(!$this->canUserAccessSalesReturn($saleReturn), 403, 'Unauthorized access to this Sales Return.');

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

            DB::commit();
            return response()->json("Sale Return has been updated");
        } catch(\Exception $e) {
            DB::rollBack();

            return response()->json($e->getMessage(), 500);
        }
    }

    public function get_sale_invoices(Request $request) {
        abort_if(!canAccess('sales-return') && !auth()->user()->can('sales-return'), 403);
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
        abort_if(!canAccess('sales-return') && !auth()->user()->can('sales-return'), 403);
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
        abort_if(!canAccess('sales-return') && !auth()->user()->can('sales-return'), 403);
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
        abort_if(!canAccess('sales-return') && !auth()->user()->can('sales-return'), 403);
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
        abort_if(!canAccess('sales-return') && !auth()->user()->can('sales-return'), 403);
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

            DB::commit();
            return response()->json("Sale Return has been created");
        } catch(\Exception $e) {
            DB::rollBack();

            return response()->json($e->getMessage(), 500);
        }
    }

    public function destroy(SalesReturn $sales_return) {
        abort_if(!canAccess('sales-return') && !auth()->user()->can('sales-return'), 403);
        abort_if(!$this->canUserAccessSalesReturn($sales_return), 403, 'Unauthorized access to this Sales Return.');

        if(in_array(strtolower($sales_return->am_approval_status ?? ''), ['approved', 'rejected'])) {
            return response()->json([
                'error' => "Sales Return has been {$sales_return->am_approval_status} and cannot be deleted.",
                'message' => "This Sales Return has already been {$sales_return->am_approval_status}. Changes or deletion are no longer allowed."
            ], 422);
        }

        $sales_return->delete();
        $sales_return->sale_return_data()->delete();

        return response()->json("Sale return has been deleted!");
    }
}
