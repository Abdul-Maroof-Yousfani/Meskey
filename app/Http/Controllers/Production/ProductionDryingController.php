<?php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ProdctionAttribute;
use App\Models\Product;
use App\Models\Master\CropYear;
use App\Models\User;

class ProductionDryingController extends Controller
{
    public function index()
    {
        return view("management.production.drying.index");
    }

    public function create()
    {
        $users = User::get(); // Users for attention_to
        $products = Product::where('status', 'active')->get();
        $attributes = ProdctionAttribute::where('status', 'active')->orderBy('key')->get();
        $cropYears = CropYear::where('status', 'active')->get();
        return view("management.production.drying.create",compact('attributes','products', 'users', 'cropYears'));
    }
}
