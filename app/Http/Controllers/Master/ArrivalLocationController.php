<?php

namespace App\Http\Controllers\Master;


use App\Http\Controllers\Controller;
use App\Models\Arrival\ArrivalSamplingResult;
use App\Models\Arrival\ArrivalSamplingResultForCompulsury;
use App\Models\Master\ArrivalLocation;
use App\Models\Arrival\ArrivalSamplingRequest;
use Illuminate\Http\Request;
use App\Http\Requests\Master\ArrivalLocationRequest;
use App\Models\Master\CompanyLocation;
use App\Models\User;
use App\Models\Master\Account\Account;

class ArrivalLocationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('management.master.arrival_location.index');
    }

    /**
     * Get list of categories.
     */
    public function getList(Request $request)
    {
        $arrival_locations = ArrivalLocation::with('companyLocation')
        ->when($request->filled('search'), function ($q) use ($request) {
            $searchTerm = '%' . $request->search . '%';
            return $q->where(function ($sq) use ($searchTerm) {
                $sq->where('name', 'like', $searchTerm);
            });
        })
            ->where('company_id', $request->company_id)

            ->latest()
            ->orderBy('company_location_id')
            ->paginate(request('per_page', 25));

        return view('management.master.arrival_location.getList', compact('arrival_locations'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $companyLocations = CompanyLocation::where('status', 'active')->get();
        return view('management.master.arrival_location.create', compact('companyLocations'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ArrivalLocationRequest $request)
    {
        $data = $request->validated();
        $arrival_locations = ArrivalLocation::create($request->all());

        // Create Account under 1-7
        $account1 = Account::create(getParamsForAccountCreationByPath($request->company_id, $request->name . ' Weighbridge', '1-7', 'arrival_locations'));
        $account1->update(['model_id' => $arrival_locations->id]);

        // Create Account under 4-4
        $account2 = Account::create(getParamsForAccountCreationByPath($request->company_id, $request->name . ' Weighbridge', '4-4', 'arrival_locations'));
        $account2->update(['model_id' => $arrival_locations->id]);

        return response()->json(['success' => 'Arrival Location created successfully.', 'data' => $arrival_locations], 201);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $arrivalLocation = ArrivalLocation::findOrFail($id);
        $companyLocations = CompanyLocation::where('status', 'active')->get();
        return view('management.master.arrival_location.edit', compact('arrivalLocation', 'companyLocations'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ArrivalLocationRequest $request, ArrivalLocation $arrival_location)
    {
        $oldName = $arrival_location->name;
        
        $data = $request->validated();
        $data = $request->all();
        $arrival_location->update($data);
        
        $account1 = Account::where('table_name', 'arrival_locations')
            ->where('model_id', $arrival_location->id)
            ->where('hierarchy_path', 'like', '1-7-%')
            ->first();
            
        if ($account1) {
            if ($oldName !== $request->name) {
                $account1->update(['name' => $request->name . ' Weighbridge']);
            }
        } else {
            $newAccount1 = Account::create(getParamsForAccountCreationByPath($request->company_id, $request->name . ' Weighbridge', '1-7', 'arrival_locations'));
            $newAccount1->update(['model_id' => $arrival_location->id]);
        }
        
        $account2 = Account::where('table_name', 'arrival_locations')
            ->where('model_id', $arrival_location->id)
            ->where('hierarchy_path', 'like', '4-4-%')
            ->first();
            
        if ($account2) {
            if ($oldName !== $request->name) {
                $account2->update(['name' => $request->name . ' Weighbridge']);
            }
        } else {
            $newAccount2 = Account::create(getParamsForAccountCreationByPath($request->company_id, $request->name . ' Weighbridge', '4-4', 'arrival_locations'));
            $newAccount2->update(['model_id' => $arrival_location->id]);
        }
        
        return response()->json(['success' => 'Arrival Location updated successfully.', 'data' => $arrival_location], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $arrival_location = ArrivalLocation::findOrFail($id);
        $arrival_location->delete();
        return response()->json(['success' => 'Arrival Location deleted successfully.'], 200);
    }


    public function getInitialSamplingResultByTicketId(Request $request)
    {
        $initialRequestForInnerReq = ArrivalSamplingRequest::where('arrival_ticket_id', $request->arrival_ticket_id)
            ->where('sampling_type', 'initial')
            ->where('approved_status', 'approved')
            ->get()->last();

        // Check if related arrivalTicket exists
        if (!$initialRequestForInnerReq) {
            return response()->json(['success' => false, 'message' => 'Arrival ticket not found.'], 404);
        }

        $initialRequestCompulsuryResults  = ArrivalSamplingResultForCompulsury::where('arrival_sampling_request_id', $initialRequestForInnerReq->id)->get();
        $initialRequestResults  = ArrivalSamplingResult::where('arrival_sampling_request_id', $initialRequestForInnerReq->id)->get();
        $sampleTakenByUsers = User::all();

        // Render view with the slabs wrapped inside a div
        $html = view('management.arrival.location_transfer.getInitialQcDetail', compact('initialRequestCompulsuryResults', 'sampleTakenByUsers', 'initialRequestForInnerReq', 'initialRequestResults', 'initialRequestCompulsuryResults', 'initialRequestResults'))->render();
        // dd($html);

        return response()->json(['success' => true, 'html' => $html]);
    }
}
