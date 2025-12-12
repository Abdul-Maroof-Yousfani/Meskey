<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SalesReturnController extends Controller
{
    public function index() {
        return view('management.sales.sales-return.index');
    }

}
