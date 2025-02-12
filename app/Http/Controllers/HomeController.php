<?php

namespace App\Http\Controllers;

use App\Models\Cities;
use App\Models\Order;
use App\Models\Page;
use App\Models\States;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use DB;
class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('permission:dashboard', ['only' => ['index']]);
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index(Request $request)
    {

        return view('management.dashboard.index');
    }

    public function getCitiesByState(Request $request)
    {
        $countryId = $request->input('state_id');
        $cities = Cities::where('state_id', $countryId)->get();
        return response()->json($cities);
    }

    public function getStatesByCountry(Request $request)
    {
        $countryId = $request->input('country_id');
        $cities = States::where('country_id', $countryId)->get();
        return response()->json($cities);
    }

public function dynamicFetchData(Request $request)
{
    $search = $request->input('search');
    $tableName = $request->input('table');
    $columnName = $request->input('column');
    $idColumn = $request->input('idColumn', 'id');
    $enableTags = $request->input('enableTags', false);

    if (!Schema::hasTable($tableName) || !Schema::hasColumn($tableName, $columnName) || !Schema::hasColumn($tableName, $idColumn)) {
        return response()->json(['error' => 'Invalid table or column'], 400);
    }

    $query = DB::table($tableName);

    // Search condition
    if ($search) {
        $query->where($columnName, 'like', '%' . $search . '%');
    } else {
        // If no search, load the first 10 records
        $query->orderBy($columnName, 'asc')->limit(10);
    }

    $data = $query->get();

    $results = [];
    foreach ($data as $item) {
        $results[] = [
            'id' => $item->$idColumn,
            'text' => $item->$columnName
        ];
    }

    // Allow tag creation if enabled and no results found
    if ($search && count($results) === 0 && $enableTags == "true") {
        $results[] = [
            'id' => $search,
            'text' => $search,
            'newTag' => true
        ];
    }

    return response()->json(['items' => $results]);
}

}
