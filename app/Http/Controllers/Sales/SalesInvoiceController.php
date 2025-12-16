<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Http\Requests\Sales\SalesInvoiceRequest;
use App\Models\Master\Customer;
use App\Models\PaymentTerm;
use App\Models\Product;
use App\Models\Sales\DeliveryChallan;
use App\Models\Sales\SalesInvoice;
use App\Models\Sales\SalesInvoiceData;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SalesInvoiceController extends Controller
{
    public function index()
    {
        return view('management.sales.sales-invoice.index');
    }

    public function create()
    {
        $customers = Customer::all();
        $items = Product::all();

        return view("management.sales.sales-invoice.create", compact("customers", "items"));
    }

    public function store(SalesInvoiceRequest $request)
    {
        DB::beginTransaction();
        $dc_ids = $request->dc_no;
        
        try {
            // Validate that no_of_bags doesn't exceed available balance
            if ($request->dc_data_id) {
                foreach ($request->dc_data_id as $index => $dc_data_id) {
                    if ($dc_data_id) {
                        $requestedBags = $request->no_of_bags[$index] ?? 0;
                        $availableBalance = $this->getAvailableBalance($dc_data_id);
                        
                        // if ($requestedBags > $availableBalance) {
                        //     return response()->json([
                        //         "error" => "Requested bags ({$requestedBags}) exceeds available balance ({$availableBalance}) for item at row " . ($index + 1)
                        //     ], 422);
                        // }
                    }
                }
            }

            $sales_invoice = SalesInvoice::create([
                "customer_id" => $request->customer_id,
                "invoice_address" => $request->invoice_address,
                "location_id" => $request->locations,
                "arrival_id" => $request->arrival_locations,
                "si_no" => $request->si_no,
                "invoice_date" => $request->invoice_date,
                "reference_number" => $request->reference_number,
                "sauda_type" => $request->sauda_type,
                "remarks" => $request->remarks,
                "company_id" => request()->company_id,
                "created_by_id" => auth()->user()->id,
            ]);

            // Sync delivery challans (many-to-many)
            if ($dc_ids) {
                $sales_invoice->delivery_challans()->sync($dc_ids);
            }

            // Store line items if provided
            if ($request->item_id) {
                foreach ($request->item_id as $index => $item) {
                    $sales_invoice->sales_invoice_data()->create([
                        "item_id" => $request->item_id[$index],
                        "packing" => $request->packing[$index] ?? 0,
                        "no_of_bags" => $request->no_of_bags[$index] ?? 0,
                        "qty" => $request->qty[$index] ?? 0,
                        "rate" => $request->rate[$index] ?? 0,
                        "gross_amount" => $request->gross_amount[$index] ?? 0,
                        "discount_percent" => $request->discount_percent[$index] ?? 0,
                        "discount_amount" => $request->discount_amount[$index] ?? 0,
                        "amount" => $request->amount[$index] ?? 0,
                        "gst_percent" => $request->gst_percent[$index] ?? 0,
                        "gst_amount" => $request->gst_amount[$index] ?? 0,
                        "net_amount" => $request->net_amount[$index] ?? 0,
                        "dc_data_id" => $request->dc_data_id[$index] ?? null,
                        "line_desc" => $request->line_desc[$index] ?? null,
                        "truck_no" => $request->truck_no[$index] ?? null,
                        "description" => $request->desc[$index] ?? null,
                    ]);
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(["error" => $e->getMessage()], 500);
        }

        return response()->json(["message" => "Sales Invoice has been created"]);
    }

    public function edit(SalesInvoice $sales_invoice)
    {
        $sales_invoice->load("delivery_challans.delivery_challan_data", "sales_invoice_data");
        $customers = Customer::all();
        $items = Product::all();

        // Get delivery challans with available balance or already selected
        $delivery_challans = $this->getDeliveryChallansWithBalance(
            $sales_invoice->customer_id,
            $sales_invoice->location_id,
            $sales_invoice->arrival_id,
            $sales_invoice->id
        );

        // Calculate balances for existing line items
        // For edit, max balance = available_balance_excluding_this_invoice + current_item_no_of_bags
        $balances = [];
        foreach ($sales_invoice->sales_invoice_data as $data) {
            if ($data->dc_data_id) {
                $availableBalance = $this->getAvailableBalance($data->dc_data_id, $sales_invoice->id);
                // Total allowed = available + what this item currently has
                $balances[$data->dc_data_id] = $availableBalance + $data->no_of_bags;
            }
        }

        return view("management.sales.sales-invoice.edit", compact("customers", "delivery_challans", "sales_invoice", "items", "balances"));
    }

    public function update(SalesInvoiceRequest $request, SalesInvoice $sales_invoice)
    {
        DB::beginTransaction();
        $dc_ids = $request->dc_no;
        
        try {
            // Validate that no_of_bags doesn't exceed available balance
            if ($request->dc_data_id) {
                foreach ($request->dc_data_id as $index => $dc_data_id) {
                    if ($dc_data_id) {
                        $requestedBags = $request->no_of_bags[$index] ?? 0;
                        // Exclude current invoice when calculating balance
                        $availableBalance = $this->getAvailableBalance($dc_data_id, $sales_invoice->id);
                        
                        // if ($requestedBags > $availableBalance) {
                        //     return response()->json([
                        //         "error" => "Requested bags ({$requestedBags}) exceeds available balance ({$availableBalance}) for item at row " . ($index + 1)
                        //     ], 422);
                        // }
                    }
                }
            }

            $sales_invoice->update([
                "customer_id" => $request->customer_id,
                "invoice_address" => $request->invoice_address,
                "location_id" => $request->locations,
                "arrival_id" => $request->arrival_locations,
                "si_no" => $request->si_no,
                "invoice_date" => $request->invoice_date,
                "reference_number" => $request->reference_number,
                "sauda_type" => $request->sauda_type,
                "remarks" => $request->remarks,
                'am_approval_status' => "pending",
                "am_change_made" => 1
            ]);

            // Sync delivery challans
            if ($dc_ids) {
                $sales_invoice->delivery_challans()->sync($dc_ids);
            }

            // Delete existing line items and re-create
            $sales_invoice->sales_invoice_data()->delete();

            if ($request->item_id) {
                foreach ($request->item_id as $index => $item) {
                    $sales_invoice->sales_invoice_data()->create([
                        "item_id" => $request->item_id[$index],
                        "packing" => $request->packing[$index] ?? 0,
                        "no_of_bags" => $request->no_of_bags[$index] ?? 0,
                        "qty" => $request->qty[$index] ?? 0,
                        "rate" => $request->rate[$index] ?? 0,
                        "gross_amount" => $request->gross_amount[$index] ?? 0,
                        "discount_percent" => $request->discount_percent[$index] ?? 0,
                        "discount_amount" => $request->discount_amount[$index] ?? 0,
                        "amount" => $request->amount[$index] ?? 0,
                        "gst_percent" => $request->gst_percent[$index] ?? 0,
                        "gst_amount" => $request->gst_amount[$index] ?? 0,
                        "net_amount" => $request->net_amount[$index] ?? 0,
                        "dc_data_id" => $request->dc_data_id[$index] ?? null,
                        "line_desc" => $request->line_desc[$index] ?? null,
                        "truck_no" => $request->truck_no[$index] ?? null,
                        "description" => $request->desc[$index] ?? null,
                    ]);
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(["error" => $e->getMessage()], 500);
        }

        return response()->json(["message" => "Sales Invoice has been updated"]);
    }

    public function destroy(SalesInvoice $sales_invoice)
    {
        $sales_invoice->delete();

        return response()->json(["message" => "Sales Invoice has been deleted!"]);
    }

    public function view(SalesInvoice $sales_invoice)
    {
        $sales_invoice->load("delivery_challans.delivery_challan_data", "sales_invoice_data");
        $customers = Customer::all();
        $items = Product::all();
        
        $delivery_challans = DeliveryChallan::select("id", "dc_no")
            ->where("customer_id", $sales_invoice->customer_id)
            ->where("location_id", $sales_invoice->location_id)
            ->where("arrival_id", $sales_invoice->arrival_id)
            ->where("am_approval_status", "approved")
            ->get();
       
        return view("management.sales.sales-invoice.view", compact("customers", "delivery_challans", "sales_invoice", "items"));
    }

    public function getList(Request $request)
    {
        $perPage = $request->get('per_page', 25);

        $sales_invoices = SalesInvoice::with(['customer', 'sales_invoice_data'])
            ->when($request->filled('search'), function ($q) use ($request) {
                $searchTerm = '%' . strtolower($request->search) . '%';
                return $q->where(function ($sq) use ($searchTerm) {
                    $sq->whereRaw('LOWER(`si_no`) LIKE ?', [$searchTerm])
                        ->orWhereRaw('LOWER(`reference_number`) LIKE ?', [$searchTerm]);
                });
            })
            ->latest()
            ->paginate($perPage);

        $groupedData = [];

        foreach ($sales_invoices as $sales_invoice) {
            $si_no = $sales_invoice->si_no;
            $items = $sales_invoice->sales_invoice_data;

            $itemRows = [];
            foreach ($items as $itemData) {
                $itemRows[] = [
                    'item_data' => $itemData,
                    'item' => $itemData->item,
                ];
            }

            $groupedData[] = [
                'sales_invoice' => $sales_invoice,
                'si_no' => $si_no,
                'created_by_id' => $sales_invoice->created_by_id,
                'invoice_date' => $sales_invoice->invoice_date,
                'id' => $sales_invoice->id,
                'customer_id' => $sales_invoice->customer_id,
                'status' => $sales_invoice->am_approval_status,
                'created_at' => $sales_invoice->created_at,
                'customer' => $sales_invoice->customer,
                'rowspan' => max(count($itemRows), 1),
                'items' => $itemRows,
            ];
        }

        return view('management.sales.sales-invoice.getList', [
            'salesInvoices' => $sales_invoices,
            'groupedSalesInvoices' => $groupedData,
        ]);
    }

    public function getNumber(Request $request, $locationId = null, $invoiceDate = null)
    {
        $date = Carbon::parse($invoiceDate ?? $request->invoice_date)->format('Y-m-d');

        $prefix = 'SI-' . Carbon::parse($invoiceDate ?? $request->invoice_date)->format('Y-m-d');

        $latestInvoice = SalesInvoice::where('si_no', 'like', "$prefix-%")
            ->latest()
            ->first();

        $datePart = Carbon::parse($date)->format('Y-m-d');

        if ($latestInvoice) {
            $parts = explode('-', $latestInvoice->si_no);
            $lastNumber = (int) end($parts);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        $si_no = 'SI-' . $datePart . '-' . str_pad($newNumber, 3, '0', STR_PAD_LEFT);

        if (!$locationId && !$invoiceDate) {
            return response()->json([
                'success' => true,
                'si_no' => $si_no,
            ]);
        }

        return $si_no;
    }

    /**
     * Get the available balance (remaining bags) for a DC data item
     */
    private function getAvailableBalance($dcDataId, $excludeSalesInvoiceId = null)
    {
        // Get the original no_of_bags from delivery_challan_data
        $dcData = DB::table('delivery_challan_data')->where('id', $dcDataId)->first();
        
        if (!$dcData) {
            return 0;
        }

        $originalBags = $dcData->no_of_bags;

        // Get the sum of bags already used in sales invoices
        $usedBagsQuery = SalesInvoiceData::where('dc_data_id', $dcDataId);
        
        if ($excludeSalesInvoiceId) {
            $usedBagsQuery->where('sales_invoice_id', '!=', $excludeSalesInvoiceId);
        }
        
        $usedBags = $usedBagsQuery->sum('no_of_bags');

        return max(0, $originalBags - $usedBags);
    }

    /**
     * Get delivery challans that have items with available balance
     */
    private function getDeliveryChallansWithBalance($customerId, $locationId, $arrivalId, $excludeSalesInvoiceId = null)
    {
        $delivery_challans = DeliveryChallan::with("delivery_challan_data")
            ->where("customer_id", $customerId)
            ->where("location_id", $locationId)
            ->where("arrival_id", $arrivalId)
            ->where("am_approval_status", "approved")
            ->get();

        $result = [];

        foreach ($delivery_challans as $dc) {
            $hasAvailableItems = false;
            
            foreach ($dc->delivery_challan_data as $dcData) {
                $balance = $this->getAvailableBalance($dcData->id, $excludeSalesInvoiceId);
                if ($balance > 0) {
                    $hasAvailableItems = true;
                    break;
                }
            }

            if ($hasAvailableItems) {
                $result[] = $dc;
            }
        }

        return collect($result);
    }

    public function get_delivery_challans(Request $request)
    {
        $customer_id = $request->customer_id;
        $location_id = $request->company_location_id;
        $arrival_location_id = $request->arrival_location_id;
        $exclude_sales_invoice_id = $request->exclude_sales_invoice_id;

        $delivery_challans = DeliveryChallan::whereHas("receivingRequest", function($query) {
                $query->where("am_approval_status", "approved");
            })->with("delivery_challan_data")
            ->where("customer_id", $customer_id)
            // ->where("location_id", $location_id)
            // ->where("arrival_id", $arrival_location_id)
            ->where("am_approval_status", "approved")
            ->get();

        $data = [];

        foreach ($delivery_challans as $delivery_challan) {
            // Check if DC has any items with available balance
            $hasAvailableItems = false;
            
            foreach ($delivery_challan->delivery_challan_data as $dcData) {
                $balance = $this->getAvailableBalance($dcData->id, $exclude_sales_invoice_id);
                if ($balance > 0) {
                    $hasAvailableItems = true;
                    break;
                }
            }

            // Only include DCs with available items
            if ($hasAvailableItems) {
                $data[] = [
                    "id" => $delivery_challan->id,
                    "text" => $delivery_challan->dc_no
                ];
            }
        }


        return $data;
    }

    public function getItems(Request $request)
    {
        $delivery_challan_ids = $request->delivery_challan_ids;
        $exclude_sales_invoice_id = $request->exclude_sales_invoice_id;
        
        $delivery_challans = DeliveryChallan::with("delivery_challan_data")->whereIn("id", $delivery_challan_ids)->get();
        $items = Product::select("id", "name")->get();

        // Calculate balance for each DC data item
        $balances = [];
        foreach ($delivery_challans as $dc) {
            foreach ($dc->delivery_challan_data as $dcData) {
                $balances[$dcData->id] = $this->getAvailableBalance($dcData->id, $exclude_sales_invoice_id);
            }
        }

        return view("management.sales.sales-invoice.getItem", compact("delivery_challans", "items", "balances"));
    }
}
