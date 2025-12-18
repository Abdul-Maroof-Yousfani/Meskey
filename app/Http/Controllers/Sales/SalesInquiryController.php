<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Http\Requests\Sales\SalesInquiryRequest;
use App\Models\BagType;
use App\Models\Master\Customer;
use App\Models\Master\ArrivalLocation;
use App\Models\Master\ArrivalSubLocation;
use App\Models\Product;
use App\Models\Sales\SalesInquiry;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;

class SalesInquiryController extends Controller
{
    public function index() {
        return view("management.sales.inquiry.index");
    }

    public function create() {
        $customers = Customer::all();
        $items = Product::all();
        $bag_types = BagType::select("id", "name")->where("status", 1)->get();
        $arrivalLocations = ArrivalLocation::select('id', 'name', 'company_location_id')->where('status', 'active')->get();
        $arrivalSubLocations = ArrivalSubLocation::select('id', 'name', 'arrival_location_id')->where('status', 'active')->get();

        return view("management.sales.inquiry.create", compact("customers", "items", "bag_types", "arrivalLocations", "arrivalSubLocations"));
    }

    public function getList(Request $request)
    {
        $perPage = $request->get('per_page', 25);

        // Eager load the inquiry + all its items + related product
        $inquiries = SalesInquiry::with(['sales_inquiry_data.item'])
            ->when($request->filled('search'), function ($q) use ($request) {
                $searchTerm = '%' . strtolower($request->search) . '%';
                return $q->where(function ($sq) use ($searchTerm) {
                    $sq->whereRaw('LOWER(`inquiry_no`) LIKE ?', [$searchTerm])
                        ->orWhereRaw('LOWER(`reference_number`) LIKE ?', [$searchTerm]);
                });
            })
            ->latest()
            ->paginate($perPage);

        $groupedData = [];

        foreach ($inquiries as $inquiry) {
            $inquiryNo = $inquiry->inquiry_no;
            $items = $inquiry->sales_inquiry_data;

            $itemRows = [];
            foreach ($items as $itemData) {
                $itemRows[] = [
                    'item_data' => $itemData,
                    'item' => $itemData->item,
                ];
            }

            $groupedData[] = [
                'inquiry' => $inquiry,
                'inquiry_no' => $inquiryNo,
                'created_by_id' => $inquiry->created_by,
                'date' => $inquiry->date,
                'id' => $inquiry->id,
                'customer' => $inquiry->customer,
                'status' => $inquiry->am_approval_status,
                'contact_person' => $inquiry->contact_person,
                'contract_type' => $inquiry->contract_type,
                'remarks' => $inquiry->remarks,
                'created_at' => $inquiry->created_at,
                'rowspan' => max(count($itemRows), 1),
                'items' => $itemRows,
            ];
        }

        return view('management.sales.inquiry.getList', [
            'inquiries' => $inquiries,
            'groupedInquiries' => $groupedData,
        ]);
    }

    public function getNumber(Request $request, $locationId = null, $contractDate = null)
    {

        $date = Carbon::parse($contractDate ?? $request->contract_date)->format('Y-m-d');

        $prefix = 'SINQ-' . Carbon::parse($contractDate ?? $request->contract_date)->format('Y-m-d');

        $latestContract = SalesInquiry::where('inquiry_no', 'like', "$prefix-%")
            ->latest()
            ->first();

        $datePart = Carbon::parse($date)->format('Y-m-d');

        if ($latestContract) {
            $parts = explode('-', $latestContract->inquiry_no);
            $lastNumber = (int) end($parts);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        $inquiry_no = 'SINQ-' . $datePart . '-' . str_pad($newNumber, 3, '0', STR_PAD_LEFT);

        if (!$locationId && !$contractDate) {
            return response()->json([
                'success' => true,
                'inquiry_no' => $inquiry_no
            ]);
        }

        return $inquiry_no;
    }

    public function store(SalesInquiryRequest $request) {
        
        try {
            DB::beginTransaction();
        
            $factoryIds = $request->arrival_location_id ?? [];
            $sectionIds = $request->arrival_sub_location_id ?? [];
            $sales_inquiry = SalesInquiry::create([
                "inquiry_no" => $request->reference_no,
                "date" => $request->inquiry_date,
                "customer" => $request->customer,
                "contract_type" => $request->contract_type,
                "status" => "pending",
                "contact_person" => $request->contact_person ?? "",
                "remarks" => $request->remarks ?? "",
                "created_by" => auth()->user()->id,
                "company_id" => $request->company_id,
                "required_date" => $request->required_date,
                "reference_number" => $request->reference_number ?? "",
                'token_money' => $request->token_money,
                'arrival_location_id' => $factoryIds[0] ?? null,
                'arrival_sub_location_id' => $sectionIds[0] ?? null,
                "am_approval_status" => "pending",
                "am_change_made" => 1
            ]);
            foreach($request->item_id as $index => $item) {
                $sales_inquiry->sales_inquiry_data()->create([
                    "item_id" => $request->item_id[$index],
                    "qty" => $request->qty[$index],
                    "rate" => $request->rate[$index],
                    "description" => $request->desc[$index] ?? "",
                    "bag_size" => $request->bag_size[$index],
                    "no_of_bags" => $request->no_of_bags[$index],
                    "bag_type" => $request->bag_type[$index],
                    "brand_id" => $request->brand_id[$index],
                    "pack_size" => $request->pack_size[$index]
                ]);
            }

            $locations = $request->locations;

            foreach($locations as $location) {
                $sales_inquiry->locations()->create([
                    "location_id" => $location
                ]);
            }
            foreach ($factoryIds as $factoryId) {
                $sales_inquiry->factories()->create([
                    'arrival_location_id' => $factoryId,
                ]);
            }
            foreach ($sectionIds as $sectionId) {
                $sales_inquiry->sections()->create([
                    'arrival_sub_location_id' => $sectionId,
                ]);
            }

            DB::commit();
        } catch(\Exception $e) {
            DB::rollBack();
            return response()->json(["error" => $e->getMessage()], 500);
        }
        


        return response()->json("Sales Inquiry has been added");

    }

    public function update(SalesInquiryRequest $request, SalesInquiry $sales_inquiry) {
        $factoryIds = $request->arrival_location_id ?? [];
        $sectionIds = $request->arrival_sub_location_id ?? [];

        DB::beginTransaction();
        try {
            $data = [
                "inquiry_no" => $request->reference_no,
                "date" => $request->inquiry_date,
                "customer" => $request->customer,
                "contract_type" => $request->contract_type,
                "status" => "pending",
                "contact_person" => $request->contact_person ?? "",
                "remarks" => $request->remarks ?? "",
                "reference_number" => $request->reference_number ?? "",
                "required_date" => $request->required_date,
                'arrival_location_id' => $factoryIds[0] ?? null,
                'arrival_sub_location_id' => $sectionIds[0] ?? null,

                'am_change_made' => 1
            ];

            if($sales_inquiry->am_approval_status === 'reverted') {
                $data["am_approval_status"] = "pending";
            }

            $sales_inquiry->update($data);

            $sales_inquiry->sales_inquiry_data()->delete();
            $sales_inquiry->locations()->delete();
            $sales_inquiry->factories()->delete();
            $sales_inquiry->sections()->delete();
        

            foreach($request->item_id as $index => $item) {
                $sales_inquiry->sales_inquiry_data()->create([
                    "item_id" => $request->item_id[$index],
                    "qty" => $request->qty[$index],
                    "rate" => $request->rate[$index],
                    "description" => $request->desc[$index] ?? "",
                    "bag_type" => $request->bag_type[$index],
                    "bag_size" => $request->bag_size[$index],
                    "no_of_bags" => $request->no_of_bags[$index],
                    "brand_id" => $request->brand_id[$index],
                    "pack_size" => $request->pack_size[$index]
                ]);
            }

            $locations = $request->locations;

            foreach($locations as $location) {
                $sales_inquiry->locations()->create([
                    "location_id" => $location
                ]);
            }
            foreach ($factoryIds as $factoryId) {
                $sales_inquiry->factories()->create([
                    'arrival_location_id' => $factoryId,
                ]);
            }
            foreach ($sectionIds as $sectionId) {
                $sales_inquiry->sections()->create([
                    'arrival_sub_location_id' => $sectionId,
                ]);
            }

            DB::commit();
        } catch(\Exception $e) {
            DB::rollBack();
            return response()->json($e->getMessage(), 500);
        }


        return response()->json("Sales Inquiry has been updated");
    }

    public function view(SalesInquiry $sales_inquiry) {
        $sales_inquiry->load("sales_inquiry_data");
        $customers = Customer::all();
        $items = Product::all();
        $bag_types = BagType::select("id", "name")->where("status", 1)->get();
        $arrivalLocations = ArrivalLocation::select('id', 'name')->where('status', 'active')->get();
        $arrivalSubLocations = ArrivalSubLocation::select('id', 'name', 'arrival_location_id')->where('status', 'active')->get();


        return view("management.sales.inquiry.view", compact("sales_inquiry", "customers", "items", "bag_types", "arrivalLocations", "arrivalSubLocations"));
    }

    public function edit(SalesInquiry $sales_inquiry) {
        $sales_inquiry->load("sales_inquiry_data", "locations", "factories", "sections");
        $customers = Customer::all();
        $items = Product::all();
        $bag_types = BagType::select("id", "name")->where("status", 1)->get(); 
        $arrivalLocations = ArrivalLocation::select('id', 'name', 'company_location_id')->where('status', 'active')->get();
        $arrivalSubLocations = ArrivalSubLocation::select('id', 'name', 'arrival_location_id')->where('status', 'active')->get();

        return view("management.sales.inquiry.edit", compact("customers", "items", "sales_inquiry", "bag_types", "arrivalLocations", "arrivalSubLocations"));
    }

    public function destroy(SalesInquiry $sales_inquiry) {
        $sales_inquiry->sales_inquiry_data()->delete();
        $sales_inquiry->delete();

        return response()->json(['success' => 'Sales Inquiry deleted successfully.'], 200);
    }
}
