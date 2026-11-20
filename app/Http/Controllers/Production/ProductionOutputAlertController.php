<?php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use App\Models\Master\Plant;
use App\Models\Production\ProductionOutputAlert;
use App\Services\Production\OutputQcAlertService;
use Illuminate\Http\Request;

/**
 * Output QC Alerts: an Output Analysis with a QC difference. The plant's users add remarks,
 * which creates a new Output Analysis request for a re-check.
 */
class ProductionOutputAlertController extends Controller
{
    public function __construct(private OutputQcAlertService $service)
    {
    }

    public function index()
    {
        $plants = Plant::whereIn('company_location_id', getUserCurrentCompanyLocations())->get(['id', 'name']);

        return view('management.production.production_output_alert.index', compact('plants'));
    }

    public function getList(Request $request)
    {
        $items = ProductionOutputAlert::with(['plant', 'jobOrder', 'product', 'analysisRequest', 'newAnalysisRequest', 'remarkedBy'])
            ->whereIn('company_location_id', getUserCurrentCompanyLocations())
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->plant_ids, fn($q) => $q->whereIn('plant_id', $request->plant_ids))
            ->when($request->date_range, function ($q) use ($request) {
                $dates = explode(' - ', $request->date_range);
                if (count($dates) === 2) {
                    $q->whereBetween('created_at', [trim($dates[0]) . ' 00:00:00', trim($dates[1]) . ' 23:59:59']);
                }
            })
            ->orderByRaw("status = 'pending' DESC")
            ->latest('id')
            ->paginate($request->per_page ?? 25);

        return view('management.production.production_output_alert.getList', compact('items'));
    }

    public function show($id)
    {
        $alert = ProductionOutputAlert::with([
            'plant', 'companyLocation', 'arrivalLocation', 'jobOrder', 'product',
            'analysis', 'analysisRequest', 'newAnalysisRequest', 'remarkedBy', 'parentAlert',
        ])->findOrFail($id);

        $canRemark = $alert->isPending() && $this->service->canRemark($alert);

        return view('management.production.production_output_alert.show', compact('alert', 'canRemark'));
    }

    public function resolve(Request $request, $id)
    {
        $request->validate(
            ['remarks' => 'required|string|max:2000'],
            ['remarks.required' => 'Please enter remarks.']
        );

        $alert = ProductionOutputAlert::findOrFail($id);

        if (!$alert->isPending()) {
            return response()->json(['status' => 'error', 'message' => 'Remarks have already been submitted for this alert.'], 422);
        }
        if (!$this->service->canRemark($alert)) {
            return response()->json(['status' => 'error', 'message' => 'Only users assigned to this plant can submit remarks.'], 403);
        }

        $newRequest = $this->service->resolve($alert, trim($request->remarks));

        $message = 'Remarks saved. Output Analysis request ' . $newRequest->request_no . ' created for re-check.';

        return response()->json(['status' => 'success', 'success' => $message, 'message' => $message]);
    }
}
