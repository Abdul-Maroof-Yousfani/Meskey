<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DeliveryChallanController extends Controller
{
    public function index() {
        return view('management.sales.delivery-challan.index');
    }
}
