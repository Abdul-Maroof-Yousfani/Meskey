<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\LabourRateRequest;
use App\Models\BagPacking;
use App\Models\Category;
use App\Models\Master\ArrivalLocation;
use App\Models\Master\LabourRate;
use Illuminate\Http\Request;

class LabourRateController extends Controller
{
    public function index() {
        return view("management.master.labour-rate.index");
    }

    public function getList(Request $request) {
        $labourRates = LabourRate::when($request->filled('search'), function ($q) use ($request) {
            $searchTerm = '%' . $request->search . '%';
            return $q->where(function ($sq) use ($searchTerm) {
                $sq->where('rate', 'like', $searchTerm);
            });
        })
            ->latest()
            ->paginate(request('per_page', 25));

        return view('management.master.labour-rate.getList', compact('labourRates'));
    }

    public function edit(LabourRate $labourRate) {

        $bag_packings = BagPacking::all();
        $categories = Category::where("category_type", "raw_finish")->get();
        $factories = ArrivalLocation::all();

        return view("management.master.labour-rate.edit", compact("labourRate", "bag_packings", "categories", "factories"));
    }

    public function create() {
        $bag_packings = BagPacking::all();
        $categories = Category::where("category_type", "raw_finish")->get();
        $factories = ArrivalLocation::all();

        return view("management.master.labour-rate.create", compact("bag_packings", "categories", "factories"));
    }

    public function store(LabourRateRequest $request) {
        try {
            $labour_rate_exists = LabourRate::where("bag_packing_id", $request->bag_packing)
                                        ->where("category_id", $request->category_id)
                                        ->where("factory_id", $request->factory_id)
                                        ->where("rate", $request->rate)
                                        ->exists();
            if($labour_rate_exists) {
                return response()->json("This combination is already created", 403);
            }
            
            LabourRate::create([
                "rate" => $request->rate,
                "bag_packing_id" => $request->bag_packing,
                "category_id" => $request->category_id,
                "factory_id" => $request->factory_id,
                "company_id" => $request->company_id,
                "status" => "active"
            ]);
        } catch(\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }

        return response()->json("Labour rate has been created!");
    }

    public function update(LabourRateRequest $request, LabourRate $labourRate) {
        try {
            $labour_rate_exists = LabourRate::where("bag_packing_id", $request->bag_packing)
                                        ->where("category_id", $request->category_id)
                                        ->where("factory_id", $request->factory_id)
                                        ->where("rate", $request->rate)
                                        ->where("id", "!=", $labourRate->id)
                                        ->exists();
            if($labour_rate_exists) {
                return response()->json("This combination is already created", 403);
            }
            
            $labourRate->update([
                "rate" => $request->rate,
                "bag_packing_id" => $request->bag_packing,
                "category_id" => $request->category_id,
                "factory_id" => $request->factory_id,
                "company_id" => $request->company_id,
                "status" => "active"
            ]);

            return response()->json("Labour Rate has been created");
        } catch(\Exception $e) {
            return response()->json($e->getMessage());
        }
    }

    public function destroy(LabourRate $labourRate) {
        $labourRate->delete();
        return response()->json("Labour rate has been deleted!");
    }
}
