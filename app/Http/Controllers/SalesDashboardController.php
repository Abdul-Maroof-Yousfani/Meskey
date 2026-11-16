<?php

namespace App\Http\Controllers;

use App\Services\SalesDashboardService;
use Illuminate\Http\Request;

class SalesDashboardController extends Controller
{
    /** List behind a sales dashboard box (opens in the modal). */
    public function listData(Request $request, SalesDashboardService $service)
    {
        abort_if(!canAccess('sales-stats'), 403);

        $list = $service->listData((string) $request->get('type'), $request);

        return view('management.dashboard.snippets.sales_list_data', $list);
    }
}
