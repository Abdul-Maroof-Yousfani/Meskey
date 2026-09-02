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
    public function index() {
        return view('management.sales.sales-return.index');
    }

    public function create() {
        $customers = Customer::where("type", "local")->get();
        $items = Product::all();

        return view("management.sales.sales-return.create", compact("customers", "items"));
    }

    public function view(int $id) {
        $saleReturn = SalesReturn::with(["sale_return_data", "receiving_requests.deliveryChallan", "sale_invoices"])->findOrFail($id);
        $customers = Customer::where("type", "local")->get();
        $items = Product::all();

        return view("management.sales.sales-return.view", compact("customers", "items", "saleReturn"));
    }

    public function edit(int $id) {
        $saleReturn = SalesReturn::with(["sale_return_data", "receiving_requests.deliveryChallan", "sale_invoices"])->findOrFail($id);
        $customers = Customer::where("type", "local")->get();
        $items = Product::all();

        return view("management.sales.sales-return.edit", compact("customers", "items", "saleReturn"));
    }

    public function update(SaleReturnRequest $request, int $id) {
        DB::beginTransaction();
        $saleReturn = SalesReturn::find($id);


        if($saleReturn->am_approval_status == "approved" || $saleReturn->am_approval_status == 'rejected') {
            return response()->json("Sales Return has been approved/rejected and cannot be updated.", 400);
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
        $customer_id = $request->customer_id;
        $locations_id = $request->location_id;
        $arrival_location_id = $request->arrival_location_id;
        $storage_id = $request->storage_id;
        $sales_return_id = $request->sales_return_id;

        // If Customer, Company Location, or Arrival Location is missing, return empty
        if (!$customer_id || !$locations_id || !$arrival_location_id) {
            return [];
        }

        // Get RR IDs that already have an active/approved Sales Return (excluding current SR if on edit)
        $usedRrIds = DB::table('sale_return_sale_invoice')
            ->join('sales_return', 'sale_return_sale_invoice.sale_return_id', '=', 'sales_return.id')
            ->where('sales_return.am_approval_status', '!=', 'rejected')
            ->when($sales_return_id, function ($q) use ($sales_return_id) {
                $q->where('sales_return.id', '!=', $sales_return_id);
            })
            ->pluck('sale_return_sale_invoice.sale_invoice_id')
            ->toArray();

        $receiving_requests = ReceivingRequest::with(['deliveryChallan', 'items.deliveryChallanData'])
            ->where('am_approval_status', 'approved')
            ->whereNotIn('id', $usedRrIds)
            ->whereHas('deliveryChallan', function($q) use ($customer_id, $locations_id, $arrival_location_id) {
                $q->where('sauda_type', 'pohanch')
                  ->where('customer_id', $customer_id)
                  ->where('location_id', $locations_id)
                  ->where('arrival_id', $arrival_location_id);
            })
            ->latest()
            ->get();

        $data = [];

        foreach ($receiving_requests as $rr) {
            $truckInfo = $rr->truck_number ? " ({$rr->truck_number})" : "";
            $dateInfo = $rr->dc_date ? " - " . Carbon::parse($rr->dc_date)->format('d M Y') : "";
            $data[] = [
                "id" => $rr->id,
                "text" => "{$rr->dc_no}{$truckInfo}{$dateInfo}"
            ];
        }

        return $data;
    }

    public function getList(Request $request) {
        $perPage = $request->get('per_page', 25);

        // Eager load the inquiry + all its items + related product
        $SaleReturns = SalesReturn::with("sale_return_data.sale_invoice_data")->when($request->filled('search'), function ($q) use ($request) {
                $searchTerm = '%' . strtolower($request->search) . '%';
                return $q->where(function ($sq) use ($searchTerm) {
                    $sq->whereRaw('LOWER(`reference_no`) LIKE ?', [$searchTerm]);
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

            // Check receiving request date
            $receiving_requests = ReceivingRequest::whereIn("id", $sale_invoices)->get();
            foreach ($receiving_requests as $rr) {
                if($rr->dc_date && strtotime($rr->dc_date) > strtotime($request->date)) {
                    return response()->json("Backward date is not allowed. DC: " . $rr->dc_no . " Date: " . $rr->dc_date->format('Y-m-d'), 422);
                }
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

                if($balance > 0 && $request->no_of_bags[$index] > $balance) {
                    return response()->json("Total balance is $balance. You cannot exceed this balance.", 422);
                }
                
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

        if($sales_return->am_approval_status == "approved" || $sales_return->am_approval_status == 'rejected') {
            return response()->json("Sales Return has been approved/rejected and cannot be updated.", 400);
        }

        $sales_return->delete();
        $sales_return->sale_return_data()->delete();

        return response()->json("Sale return has been deleted!");
    }
}
