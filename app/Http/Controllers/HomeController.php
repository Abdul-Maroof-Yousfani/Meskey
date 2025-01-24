<?php

namespace App\Http\Controllers;

use App\Models\Cities;
use App\Models\Order;
use App\Models\Page;
use App\Models\States;
use Illuminate\Http\Request;

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
}
