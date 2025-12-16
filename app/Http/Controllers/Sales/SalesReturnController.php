<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Http\Requests\Sales\SaleReturnRequest;
use App\Models\Master\Customer;
use App\Models\Product;
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
        $customers = Customer::all();
        $items = Product::all();

        return view("management.sales.sales-return.create", compact("customers", "items"));
    }

    public function view(int $id) {
        $saleReturn = SalesReturn::with("sale_return_data", "sale_invoices:id,si_no")->find($id);
        

        $customers = Customer::all();
        $items = Product::all();

        return view("management.sales.sales-return.view", compact("customers", "items", "saleReturn"));
    }

    public function edit(int $id) {
        $saleReturn  = SalesReturn::with("sale_invoices:id,si_no")->find($id);
        $customers = Customer::all();
        $items = Product::all();

        return view("management.sales.sales-return.edit", compact("customers", "items", "saleReturn"));
    }

    public function update(SaleReturnRequest $request, int $id) {
        DB::beginTransaction();
        $saleReturn = SalesReturn::find($id);
        try {

            $saleReturn->update([
                ...$request->validated(),
                "am_approval_status" => "pending",
                "am_change_made" => 1
            ]);
            $saleReturn->sale_return_data()->delete();

            foreach($request->item_id as $index => $item_id) {
                $saleReturn->sale_return_data()->create([
                    "quantity" => $request->qty[$index],
                    "sale_invoice_data_id" => $request->si_data_id[$index],
                    "sale_return_id" => $saleReturn->id
                ]);
            }

            
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

        $sale_invoices = SalesInvoice::
                                approved()
                                ->select("id", "si_no")
                                ->where("location_id", $locations_id)
                                ->get();

        $data = [];

        foreach($sale_invoices as $sale_invoice) {
            $data[] = [
                "text" => $sale_invoice->si_no,
                "id" => $sale_invoice->id
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
                    $itemRows[] = [
                        'item_data' => $itemData,
                        "si_data" => $itemData->sale_invoice_data,
                        'item' => $itemData->sale_invoice_data->item,
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
        $sale_invoice_ids = $request->sale_invoice_ids;
        
        $sale_invoices = SalesInvoice::with("sales_invoice_data")
                            ->whereIn("id", $sale_invoice_ids)
                            ->get();
        $items = Product::select("id", "name")->get();

        $balances = [];
        // foreach ($sales_invoices as $si) {
        //     foreach ($si->delivery_challan_data as $dcData) {
        //         $balances[$dcData->id] = $this->getAvailableBalance($dcData->id, $exclude_sales_invoice_id);
        //     }
        // }

        return view("management.sales.sales-return.getItem", compact("sale_invoices", "items", "balances"));
    
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

            $sale_invoices = $request->si_no;
           
            $sale_return = SalesReturn::create([
                ...$request->validated(),
                "created_by" => auth()->user()->id
            ]);

            

            foreach($request->item_id as $index => $item_id) {
                $sale_return->sale_return_data()->create([
                    "quantity" => $request->qty[$index],
                    "sale_invoice_data_id" => $request->si_data_id[$index],
                    "packing" => $request->packing[$index],
                    "no_of_bags" => $request->no_of_bags[$index],
                    "rate" => $request->rate[$index],
                    "gross_amount" => $request->gross_amount[$index],
                    "discount_percent" => $request->gross_amount[$index],
                    "discount_amount" => $request->discount_amount[$index],
                    "amount" => $request->amount[$index],
                    "gst_percentage" => $request->gst_percent[$index],
                    "gst_amount" => $request->gst_amount[$index],
                    "net_amount"  => $request->net_amount[$index],
                    "line_desc" => $request->line_desc[$index],
                    "truck_no" => $request->truck_no[$index]
                ]);
            }

            foreach($sale_invoices as $sale_invoice) {
                $sale_return->sale_invoices()->sync([
                    $sale_invoice => [
                        "qty" => $request->qty[$index]
                    ]
                ]);
            }

            // $sale_return->sale_invoices()->sync([
            //     $request->
            // ]);

            
            DB::commit();
            return response()->json("Sale Return has been created");
        } catch(\Exception $e) {
            DB::rollBack();

            return response()->json($e->getMessage(), 500);
        }
    }

    public function destroy(SalesReturn $sales_return) {
        $sales_return->delete();

        return response()->json("Sale return has been deleted!");
    }
}
