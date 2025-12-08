<?php

namespace App\Http\Controllers\MasterControl;

use App\Http\Controllers\Controller;
use App\Models\Master\Account\Transaction;
use App\Models\Master\GrnNumber;
use App\Models\Master\Supplier;
use App\Models\Product;
use Illuminate\Http\Request;
use App\Models\Arrival\{ArrivalTicket, ArrivalSamplingResult, ArrivalSamplingResultForCompulsury, ArrivalSamplingRequest};
use App\Models\{SaudaType, ArrivalPurchaseOrder, BagType, BagCondition, BagPacking, User};
use App\Models\Master\{ArrivalLocation, Station, ArrivalSubLocation, ProductSlab};
use App\Models\AuditLog;
use App\Models\Master\Miller;
use Illuminate\Support\Facades\Validator;
use DB;
use Illuminate\Validation\ValidationException;
class ArrivalMasterRevertController extends Controller
{

    function __construct()
    {
        $this->middleware('check.company:arrival-master-control', ['only' => ['arrivalRevert']]);
    }
    /**
     * Display the master revert form
     */
    public function arrivalRevert(Request $request, $id)
    {
        $authUserCompany = $request->company_id;
        $authUser = auth()->user();
        $isSuperAdmin = $authUser->user_type === 'super-admin';
        $userLocation = $authUser->companyLocation ?? null;
        $source = $request->source ?? false;





        $arrivalPurchaseOrders = ArrivalPurchaseOrder::with(['product', 'supplier', 'saudaType'])
            ->where('purchase_type', 'regular')
            ->when(!$isSuperAdmin, function ($q) use ($userLocation) {
                $q->where('company_location_id', $userLocation?->id);
            })
            ->orderByDesc('id')
            ->get();


        $bagTypes = BagType::all();
        $bagConditions = BagCondition::all();
        $bagPackings = BagPacking::all();
        $arrivalSubLocations = ArrivalSubLocation::where('status', 'Active')->get();
        $suppliers = Supplier::where('status', 'active')->get();
        $products = Product::where('status', 'active')->get();

        $accountsOf = User::role('Purchaser')
            ->whereHas('companies', function ($q) use ($authUserCompany) {
                $q->where('companies.id', $authUserCompany);
            })
            ->get();

        $arrivalTicket = ArrivalTicket::findOrFail($id);


        $ArrivalLocations = ArrivalLocation::where('status', 'active')
            ->where('company_location_id', $arrivalTicket->location_id)

            ->get();


        $latestRequestIds = ArrivalSamplingRequest::selectRaw('MAX(id) as id')
            ->where('arrival_ticket_id', $arrivalTicket->id)
            // ->where('is_done', 'yes')
            ->groupBy('arrival_ticket_id')
            ->pluck('id');
        // dd($latestRequestIds);
        $arrivalSamplingRequest = ArrivalSamplingRequest::where('arrival_ticket_id', $arrivalTicket->id)
            ->whereIn('id', $latestRequestIds)
            // ->where(function ($q) {
            //     $q->where('approved_status', '!=', 'pending')
            //         ->orWhere(function ($q) {
            //             $q->where('decision_making', 1);
            //         });
            // })
            ->latest()
            ->first();
        // dd( $arrivalSamplingRequest);
        $slabs = collect();
        $productSlabCalculations = null;
        $results = collect();
        $Compulsuryresults = collect();
        $arrivalPurchaseOrders = collect();
        $sampleTakenByUsers = collect();
        // $saudaTypes = collect();
        $saudaTypes = SaudaType::all();

        $allInitialRequests = collect();
        $allInnerRequests = collect();
        $initialRequestsData = [];
        $innerRequestsData = [];

        if ($arrivalSamplingRequest) {
            $slabs = ProductSlab::where('product_id', $arrivalSamplingRequest->arrival_product_id)
                ->get()
                ->groupBy('product_slab_type_id')
                ->map(function ($group) {
                    return $group->sortBy('from')->first();
                });

            if ($arrivalSamplingRequest->arrival_product_id) {
                $productSlabCalculations = ProductSlab::where('product_id', $arrivalSamplingRequest->arrival_product_id)->get();
            }

            $results = ArrivalSamplingResult::where('arrival_sampling_request_id', $arrivalSamplingRequest->id)->get();
            foreach ($results as $result) {
                $matchingSlabs = [];
                if ($productSlabCalculations) {
                    $matchingSlabs = $productSlabCalculations->where('product_slab_type_id', $result->product_slab_type_id)
                        ->values()
                        ->all();
                }
                $result->matching_slabs = $matchingSlabs;
            }

            $results->map(function ($item) use ($slabs) {
                $slab = $slabs->get($item->product_slab_type_id);
                $item->max_range = $slab ? $slab->to : null;
                return $item;
            });

            $Compulsuryresults = ArrivalSamplingResultForCompulsury::where('arrival_sampling_request_id', $arrivalSamplingRequest->id)->get();

            $arrivalPurchaseOrders = ArrivalPurchaseOrder::where('product_id', $arrivalSamplingRequest->arrivalTicket->product_id)->get();
            $sampleTakenByUsers = User::all();
            $authUserCompany = $request->company_id;
            $saudaTypes = SaudaType::all();

            $allInitialRequests = ArrivalSamplingRequest::where('sampling_type', 'initial')
                ->where('arrival_ticket_id', $arrivalTicket->id)
                ->where('approved_status', '!=', 'pending')
                ->where('id', '!=', $latestRequestIds)
                ->orderBy('created_at', 'asc')

                ->get();

            $allInnerRequests = ArrivalSamplingRequest::where('sampling_type', 'inner')
                ->where('arrival_ticket_id', $arrivalTicket->id)
                ->where('approved_status', '!=', 'pending')
                ->where('id', '!=', $arrivalSamplingRequest->id)
                ->orderBy('created_at', 'asc')
                ->get();

            foreach ($allInitialRequests as $initialReq) {
                $initialResults = ArrivalSamplingResult::where('arrival_sampling_request_id', $initialReq->id)->get();
                $initialCompulsuryResults = ArrivalSamplingResultForCompulsury::where('arrival_sampling_request_id', $initialReq->id)->get();

                $initialResults->map(function ($item) use ($slabs) {
                    $slab = $slabs->get($item->product_slab_type_id);
                    $item->max_range = $slab ? $slab->to : null;
                    return $item;
                });

                $initialRequestsData[] = [
                    'request' => $initialReq,
                    'results' => $initialResults,
                    'compulsuryResults' => $initialCompulsuryResults
                ];
            }

            foreach ($allInnerRequests as $innerReq) {
                $innerResults = ArrivalSamplingResult::where('arrival_sampling_request_id', $innerReq->id)->get();
                $innerCompulsuryResults = ArrivalSamplingResultForCompulsury::where('arrival_sampling_request_id', $innerReq->id)->get();

                $innerResults->map(function ($item) use ($slabs) {
                    $slab = $slabs->get($item->product_slab_type_id);
                    $item->max_range = $slab ? $slab->to : null;
                    return $item;
                });

                $innerRequestsData[] = [
                    'request' => $innerReq,
                    'results' => $innerResults,
                    'compulsuryResults' => $innerCompulsuryResults
                ];
            }
        }

        $layout = !isset($source) || $source != 'contract' ? 'management.layouts.master' : 'management.layouts.master_blank';

        return view('management.arrival.ticket.arrival-revert', compact(
            'arrivalTicket',
            'source',
            'layout',
            'innerRequestsData',
            'arrivalSamplingRequest',
            'initialRequestsData',
            'results',
            'Compulsuryresults',
            'accountsOf',
            'ArrivalLocations',
            'arrivalSubLocations',
            'bagTypes',
            'bagConditions',
            'bagPackings',
            'saudaTypes',
            'suppliers',
            'products',
            'arrivalPurchaseOrders'
        ));
    }

    /**
     * Handle master revert operations
     */


    public function update(Request $request, $id)
    {
        $arrivalTicket = ArrivalTicket::findOrFail($id);

        DB::beginTransaction();

        try {




            // ==================== UPDATE OPERATIONS ====================



            // Handle Ticket UPDATE
            if ($request->has('ticket_submit')) {
                try {
                    $this->updateTicket($request, $arrivalTicket);
                    DB::commit();
                    return response()->json([
                        'success' => 'Ticket updated successfully.',
                        'data' => $arrivalTicket
                    ], 201);
                } catch (ValidationException $e) {
                    DB::rollBack();
                    return response()->json([
                        'message' => 'Validation failed.',
                        'errors' => $e->errors()
                    ], 422);
                } catch (\Exception $e) {
                    DB::rollBack();
                    return response()->json([
                        'message' => 'Ticket update failed.',
                        'error' => $e->getMessage()
                    ], 500);
                }
            }


            // Handle Location Transfer UPDATE
            if ($request->has('location_transfer_submit')) {
                try {
                    $this->updateLocationTransfer($request, $arrivalTicket);
                    DB::commit();
                    return response()->json([
                        'success' => 'Location transfer updated successfully.',
                        'data' => $arrivalTicket
                    ], 201);
                } catch (ValidationException $e) {
                    DB::rollBack();
                    return response()->json([
                        'message' => 'Validation failed.',
                        'errors' => $e->errors()
                    ], 422);
                } catch (\Exception $e) {
                    DB::rollBack();
                    return response()->json([
                        'message' => 'Location transfer update failed.',
                        'error' => $e->getMessage()
                    ], 500);
                }
            }

            // Handle First Weighbridge UPDATE
            if ($request->has('first_weighbridge_submit')) {
                try {
                    $this->updateFirstWeighbridge($request, $arrivalTicket);
                    DB::commit();
                    return response()->json([
                        'success' => 'First weighbridge updated successfully.',
                        'data' => $arrivalTicket
                    ], 201);
                } catch (ValidationException $e) {
                    DB::rollBack();
                    return response()->json([
                        'message' => 'Validation failed.',
                        'errors' => $e->errors()
                    ], 422);
                } catch (\Exception $e) {
                    DB::rollBack();
                    return response()->json([
                        'message' => 'First weighbridge update failed.',
                        'error' => $e->getMessage()
                    ], 500);
                }
            }

            // Handle Second Weighbridge UPDATE
            if ($request->has('second_weighbridge_submit')) {
                try {
                    $this->updateSecondWeighbridge($request, $arrivalTicket);
                    DB::commit();
                    return response()->json([
                        'success' => 'Second weighbridge updated successfully.',
                        'data' => $arrivalTicket
                    ], 201);
                } catch (ValidationException $e) {
                    DB::rollBack();
                    return response()->json([
                        'message' => 'Validation failed.',
                        'errors' => $e->errors()
                    ], 422);
                } catch (\Exception $e) {
                    DB::rollBack();
                    return response()->json([
                        'message' => 'Second weighbridge update failed.',
                        'error' => $e->getMessage()
                    ], 500);
                }
            }

            // Handle Half/Full Approval UPDATE
            if ($request->has('half_full_approve_submit')) {
                try {
                    $this->updateHalfFullApproval($request, $arrivalTicket);
                    DB::commit();
                    return response()->json([
                        'success' => 'Half/Full approval updated successfully.',
                        'data' => $arrivalTicket
                    ], 201);
                } catch (ValidationException $e) {
                    DB::rollBack();
                    return response()->json([
                        'message' => 'Validation failed.',
                        'errors' => $e->errors()
                    ], 422);
                } catch (\Exception $e) {
                    DB::rollBack();
                    return response()->json([
                        'message' => 'Half/Full approval update failed.',
                        'error' => $e->getMessage()
                    ], 500);
                }
            }
            // Handle Half/Full Approval UPDATE
            if ($request->has('last_qc_submit')) {
                try {

                    $this->updateLastQc($request, $arrivalTicket);
                    DB::commit();
                    return response()->json([
                        'success' => 'Half/Full approval updated successfully.',
                        'data' => $arrivalTicket
                    ], 201);
                } catch (ValidationException $e) {
                    DB::rollBack();
                    return response()->json([
                        'message' => 'Validation failed.',
                        'errors' => $e->errors()
                    ], 422);
                } catch (\Exception $e) {
                    DB::rollBack();
                    return response()->json([
                        'message' => 'Half/Full approval update failed.',
                        'error' => $e->getMessage()
                    ], 500);
                }
            }

            // ==================== REVERT OPERATIONS ====================

            // Handle Location Transfer REVERT
            if ($request->has('qc_request_revert')) {
                try {

                    $requestisPending = ArrivalSamplingRequest::findOrFail($request->arrivalSamplingRequestid);
                    if ($requestisPending && $requestisPending->is_done == 'no') {
                        $this->revertQcRequest($requestisPending, $arrivalTicket);
                        DB::commit();
                        return response()->json([
                            'success' => 'Request Reverted successfully.',
                            'data' => $arrivalTicket
                        ], 201);
                    } else {
                        return response()->json([
                            'success' => 'Qc Already Performed you can not revert it please referesh page to see the QC result',
                            'data' => $arrivalTicket
                        ], 422);

                    }
                } catch (\Exception $e) {
                    DB::rollBack();
                    return response()->json([
                        'message' => 'Revert failed:' . $e->getMessage(),
                        'error' => $e->getMessage()
                    ], 500);
                }
            }
            // Handle Location Transfer REVERT
            if ($request->has('location_transfer_revert')) {
                try {
                    $this->revertLocationTransfer($arrivalTicket);
                    DB::commit();
                    return response()->json([
                        'success' => 'Location transfer reverted successfully.',
                        'data' => $arrivalTicket
                    ], 201);
                } catch (\Exception $e) {
                    DB::rollBack();
                    return response()->json([
                        'message' => 'Location transfer revert failed.',
                        'error' => $e->getMessage()
                    ], 500);
                }
            }

            // Handle First Weighbridge REVERT
            if ($request->has('first_weighbridge_revert')) {
                try {
                    $this->revertFirstWeighbridge($arrivalTicket);
                    DB::commit();
                    return response()->json([
                        'success' => 'First weighbridge reverted successfully.',
                        'data' => $arrivalTicket
                    ], 201);
                } catch (\Exception $e) {
                    DB::rollBack();
                    return response()->json([
                        'message' => 'First weighbridge revert failed.',
                        'error' => $e->getMessage()
                    ], 500);
                }
            }

            // Handle Second Weighbridge REVERT
            if ($request->has('second_weighbridge_revert')) {
                try {
                    $this->revertSecondWeighbridge($arrivalTicket);
                    DB::commit();
                    return response()->json([
                        'success' => 'Second weighbridge reverted successfully.',
                        'data' => $arrivalTicket
                    ], 201);
                } catch (\Exception $e) {
                    DB::rollBack();
                    return response()->json([
                        'message' => 'Second weighbridge revert failed.',
                        'error' => $e->getMessage()
                    ], 500);
                }
            }

            // Handle Half/Full Approval REVERT
            if ($request->has('half_full_approve_revert')) {
                try {
                    $this->revertHalfFullApproval($arrivalTicket);
                    DB::commit();
                    return response()->json([
                        'success' => 'Half/Full approval reverted successfully.',
                        'data' => $arrivalTicket
                    ], 201);
                } catch (\Exception $e) {
                    DB::rollBack();
                    return response()->json([
                        'message' => 'Half/Full approval revert failed.',
                        'error' => $e->getMessage()
                    ], 500);
                }
            }
            // Handle Half/Full Approval REVERT
            if ($request->has('freight_revert')) {
                try {
                    $this->revertFreight($arrivalTicket);
                    DB::commit();
                    return response()->json([
                        'success' => 'Freight reverted successfully.',
                        'data' => $arrivalTicket
                    ], 201);
                } catch (\Exception $e) {
                    DB::rollBack();
                    return response()->json([
                        'message' => 'Freight revert failed.',
                        'error' => $e->getMessage()
                    ], 500);
                }
            }

            // Handle Complete Ticket REVERT
            if ($request->has('complete_ticket_revert')) {
                try {
                    $this->revertCompleteTicket($arrivalTicket);
                    DB::commit();
                    return response()->json([
                        'success' => 'Complete ticket reverted successfully.',
                        'data' => $arrivalTicket
                    ], 201);
                } catch (\Exception $e) {
                    DB::rollBack();
                    return response()->json([
                        'message' => 'Complete ticket revert failed.',
                        'error' => $e->getMessage()
                    ], 500);
                }
            }

            // Handle Master UPDATE
            if ($request->has('master_update_submit')) {
                try {
                    $this->handleMasterUpdate($request, $arrivalTicket);
                    DB::commit();
                    return response()->json([
                        'success' => 'Master data updated successfully.',
                        'data' => $arrivalTicket
                    ], 201);
                } catch (ValidationException $e) {
                    DB::rollBack();
                    return response()->json([
                        'message' => 'Validation failed.',
                        'errors' => $e->errors()
                    ], 422);
                } catch (\Exception $e) {
                    DB::rollBack();
                    return response()->json([
                        'message' => 'Master data update failed.',
                        'error' => $e->getMessage()
                    ], 500);
                }
            }

            DB::commit();
            return response()->json([
                'message' => 'Invalid action.'
            ], 400);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Operation failed.',
                'error' => $e->getMessage()
            ], 500);
        }
    }






    /**
     * Update Half/Full Approval
     */
    private function updateTicket($request, $arrivalTicket)
    {
        $requestData = $request->validate([
            'miller_name' => 'required|string|max:255',
            'loading_date' => 'nullable|date',
            'remarks' => 'nullable|string|max:500',
            'station' => 'required|string|max:255',
            'bilty_no' => 'required|string|max:255',
            'truck_no' => 'required|string|max:255',
            'bags' => 'required|numeric',
            'truck_type_id' => 'required|max:255',
            'sample_money_type' => 'required|in:n/a,single,double',
            'sample_money' => 'required|numeric',
            'first_weight' => 'required|numeric',
            'second_weight' => 'required|numeric',
            'arrival_purchase_order_id' => 'nullable|integer',
            'accounts_of' => 'required|string'
        ]);

        // Update or create approval record
        if ($arrivalTicket) {

            if (!empty($requestData['station'])) {
                $station = Station::firstOrCreate(
                    [
                        'name' => $requestData['station'],
                        'company_id' => $request->company_id ?? null,
                    ]
                );

                $requestData['station_id'] = $station->id;
                $requestData['station_name'] = $station->name;
            }


            if (!empty($requestData['accounts_of'])) {
              //  dd($requestData['accounts_of']);
                $supplier = Supplier::where('name', $requestData['accounts_of'])->first();
                $requestData['accounts_of_id'] = $supplier ? $supplier->id : null;
                $requestData['accounts_of_name'] = $requestData['accounts_of'];
            }

            if (!empty($requestData['miller_name'])) {
                $miller = Miller::where('name', $requestData['miller_name'])->first();
                if (!$miller) {
                    $miller = Miller::create(['name' => $requestData['miller_name']]);
                }
                $requestData['miller_id'] = $miller->id;
            }

            //    dd($requestData);
            $arrivalTicket->update($requestData);
        }

        $this->logRevertAction($arrivalTicket, 'half_full_approval_update', 'Half/Full approval updated');
    }

    /**
     * Update Location Transfer
     */
    private function updateLocationTransfer($request, $arrivalTicket)
    {

        $validated = $request->validate([
            'arrival_location_id' => 'required|exists:arrival_locations,id'
        ]);

        if ($arrivalTicket->unloadingLocation) {

            $arrivalTicket->unloadingLocation()->update([
                'arrival_location_id' => $validated['arrival_location_id']
            ]);

        }

        $this->logRevertAction($arrivalTicket->unloadingLocation, 'location_transfer_update', 'Location transfer updated');
    }

    /**
     * Update First Weighbridge
     */
    private function updateFirstWeighbridge($request, $arrivalTicket)
    {

        $validated = $request->validate([
            'arrival_first_weight' => 'required|numeric|min:0'
        ]);


        if ($arrivalTicket->firstWeighbridge) {
            $arrivalTicket->firstWeighbridge()->update([
                'weight' => $validated['arrival_first_weight']
            ]);
        }

        $this->logRevertAction($arrivalTicket->firstWeighbridge, 'first_weighbridge_update', 'First weighbridge weight updated');
    }

    /**
     * Update Second Weighbridge
     */
    private function updateSecondWeighbridge($request, $arrivalTicket)
    {
        $validated = $request->validate([
            'arrival_second_weight' => 'required|numeric|min:0'
        ]);

        if ($arrivalTicket->secondWeighbridge) {
            $arrivalTicket->secondWeighbridge()->update([
                'weight' => $validated['arrival_second_weight']
            ]);
        } else {
            // Create new second weighbridge if doesn't exist
            ArrivalWeighbridge::create([
                'arrival_ticket_id' => $arrivalTicket->id,
                'weight' => $validated['arrival_second_weight'],
                'type' => 'second'
            ]);
        }

        // Auto-calculate net weight difference
        if ($arrivalTicket->firstWeighbridge) {
            $firstWeight = $arrivalTicket->firstWeighbridge->weight;
            $netWeight = $firstWeight - $validated['arrival_second_weight'];
            // You can save this net weight if needed
        }

        $this->logRevertAction($arrivalTicket, 'second_weighbridge_update', 'Second weighbridge weight updated');
    }

    /**
     * Update Half/Full Approval
     */
    private function updateHalfFullApproval($request, $arrivalTicket)
    {
        $validated = $request->validate([
            'gala_id' => 'required|exists:arrival_sub_locations,id',
            'bag_type_id' => 'required|exists:bag_types,id',
            'filling_bags_no' => 'required|integer|min:0',
            'bag_condition_id' => 'required|exists:bag_conditions,id',
            'bag_packing_id' => 'required|exists:bag_packings,id',
            'total_bags' => 'required|integer|min:0',
            'total_rejection' => 'required|integer|min:0',
            'amanat' => 'required|in:Yes,No',
            'remark' => 'nullable|string'
        ]);

        // Update or create approval record
        if ($arrivalTicket->approvals) {

            $arrivalTicket->approvals()->update($validated);
        } else {
            $arrivalTicket->approvals()->create($validated);
        }

        $this->logRevertAction($arrivalTicket, 'half_full_approval_update', 'Half/Full approval updated');
    }


    private function updateLastQc($request, $arrivalTicket)
    {
        $id = $request->arrivalSamplingRequestid;
        $validated = $request->validate([
            // 'stage_status' => 'required',
            'sauda_type_id' => 'required'
        ]);


        try {
            $ArrivalSamplingRequest = ArrivalSamplingRequest::findOrFail($id);
            $reqStatus = $ArrivalSamplingRequest->approved_status;

            // if ($reqStatus === 'approved' && $request->stage_status !== 'approved') {
            //     return response()->json([
            //         'errors' => [
            //             'stage_status' => ['This request is already approved, stage status must be "approved"']
            //         ]
            //     ], 422);
            // }

            $decisionMakingValue = 'off';
            $isLumpsum = 0;

            if ($ArrivalSamplingRequest->sampling_type === 'initial' && $reqStatus === 'pending' && $request->stage_status === 'resampling') {
                $decisionMakingValue = 'off';
                $isLumpsum = 0;
            } else {
                if ($reqStatus === 'approved') {
                    $decisionMakingValue = $request->decision_making ?? 'off';
                    $isLumpsum = convertToBoolean($request->is_lumpsum_deduction ?? 'off');
                } elseif ($reqStatus === 'pending') {
                    $decisionMakingValue = ($request->stage_status === 'approved')
                        ? ($request->decision_making ?? 'off')
                        : 'off';
                    $isLumpsum = convertToBoolean($request->is_lumpsum_deduction ?? 'off');
                }
            }

            $decisionMadeOn = null;
            $isDecisionMaking = convertToBoolean($decisionMakingValue);
            $isDecisionMakingReq = convertToBoolean($request->decision_making ?? 'off');

            if (!$isDecisionMakingReq && $ArrivalSamplingRequest->arrivalTicket->decision_making === 1) {
                $decisionMadeOn = now();
            }

            $ArrivalSamplingRequest->update([
                'remark' => $request->remarks,
                'decision_making' => $isDecisionMaking,
                'lumpsum_deduction' => (float) $request->lumpsum_deduction ?? 0.00,
                'lumpsum_deduction_kgs' => (float) $request->lumpsum_deduction_kgs ?? 0.00,
                'is_lumpsum_deduction' => $isLumpsum,
                'is_done' => 'yes',
                'done_by' => auth()->user()->id,
            ]);

            $records = ArrivalSamplingResult::where('arrival_sampling_request_id', $id)->get();

            foreach ($records as $record) {
                $record->delete();
            }

            $recordsQc = ArrivalSamplingResultForCompulsury::where('arrival_sampling_request_id', $id)->get();

            foreach ($recordsQc as $recordQc) {
                $recordQc->delete();
            }

            if (!empty($request->product_slab_type_id) && !empty($request->checklist_value)) {
                foreach ($request->product_slab_type_id as $key => $slabTypeId) {
                    ArrivalSamplingResult::create([
                        'company_id' => $request->company_id,
                        'arrival_sampling_request_id' => $id,
                        'product_slab_type_id' => $slabTypeId,
                        'checklist_value' => $request->checklist_value[$key] ?? null,
                        'suggested_deduction' => $request->suggested_deduction[$key] ?? null,
                        'applied_deduction' => $request->applied_deduction[$key] ?? null,
                    ]);
                }
            }

            if (!empty($request->compulsory_param_id)) {
                foreach ($request->compulsory_param_id as $key => $slabTypeId) {
                    ArrivalSamplingResultForCompulsury::create([
                        'company_id' => $request->company_id,
                        'arrival_sampling_request_id' => $id,
                        'arrival_compulsory_qc_param_id' => $slabTypeId,
                        'compulsory_checklist_value' => $request->compulsory_checklist_value[$key] ?? null,
                        'applied_deduction' => $request->compulsory_aapplied_deduction[$key] ?? 0,
                        'remark' => $request->remarks ?? null,
                    ]);
                }
            }

            if ($reqStatus == 'pending') {
                if ($request->stage_status == 'resampling') {
                    ArrivalSamplingRequest::create([
                        'company_id' => $ArrivalSamplingRequest->company_id,
                        'arrival_ticket_id' => $ArrivalSamplingRequest->arrival_ticket_id,
                        'sampling_type' => $ArrivalSamplingRequest->sampling_type,
                        'is_re_sampling' => 'yes',
                        'is_done' => 'no',
                        'remark' => null,
                    ]);
                    $ArrivalSamplingRequest->is_resampling_made = 'yes';
                }
            }

            $updateData = [
                'lumpsum_deduction' => (float) ($request->lumpsum_deduction ?? 0.00),
                'lumpsum_deduction_kgs' => (float) ($request->lumpsum_deduction_kgs ?? 0.00),
                'is_lumpsum_deduction' => $isLumpsum,
                'decision_making' => $isDecisionMaking,
                'decision_making_time' => $decisionMadeOn,
                //'location_transfer_status' => $request->stage_status == 'approved' ? 'pending' : null,
                'sauda_type_id' => $request->sauda_type_id,
                // 'arrival_purchase_order_id' => $request->arrival_purchase_order_id,
            ];

            if ($ArrivalSamplingRequest->sampling_type == 'inner') {
                $updateData['second_qc_status'] = $request->stage_status;
            } else {
                // if ($reqStatus !== 'approved') {
                // dd($request->stage_status);
                $updateData['first_qc_status'] = $request->stage_status;
                if ($reqStatus !== 'approved') {
                    $updateData['location_transfer_status'] = $request->stage_status == 'approved' ? 'pending' : null;
                }
                if ($request->stage_status == 'rejected') {
                    $updateData['location_transfer_status'] = null;
                }
                // }
            }

            $ArrivalSamplingRequest->arrivalTicket()->first()->update($updateData);

            // if ($reqStatus == 'pending') {
            $ArrivalSamplingRequest->approved_status = $request->stage_status;
            // }

            $ArrivalSamplingRequest->save();

            return response()->json([
                'success' => 'Data stored successfully',
                'data' => [],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong!',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    // ==================== REVERT METHODS ====================

    /**
     * Revert Location Transfer
     */
    private function revertQcRequest($requestisPending, $arrivalTicket)
    {
        // dd($request->arrivalSamplingRequestid);
        if ($requestisPending) {
            $requestisPending->delete();
            $this->logRevertAction($arrivalTicket, 'qc_request_revert', 'QC Request  reverted');
        }
    }
    private function revertLocationTransfer($arrivalTicket)
    {
        if ($arrivalTicket->unloadingLocation) {
            $arrivalTicket->unloadingLocation->delete();
            $arrivalTicket->update([
                'location_transfer_status' => 'pending',
                'first_weighbridge_status' => null,
            ]);
            $this->logRevertAction($arrivalTicket, 'location_transfer_revert', 'Location transfer reverted');
        }
    }

    /**
     * Revert First Weighbridge
     */
    private function revertFirstWeighbridge($arrivalTicket)
    {
        if ($arrivalTicket->firstWeighbridge) {
            $arrivalTicket->firstWeighbridge->delete();
            $arrivalTicket->update([
                'first_weighbridge_status' => 'pending',
                'document_approval_status' => null,
            ]);

            $this->logRevertAction($arrivalTicket, 'first_weighbridge_revert', 'First weighbridge reverted');
        }
    }

    /**
     * Revert Second Weighbridge
     */
    private function revertSecondWeighbridge($arrivalTicket)
    {
        if ($arrivalTicket->secondWeighbridge) {
            $arrivalTicket->secondWeighbridge->delete();
            $arrivalTicket->update([
                'second_weighbridge_status' => 'pending',
                'freight_status' => null,
            ]);

            $this->logRevertAction($arrivalTicket, 'second_weighbridge_revert', 'Second weighbridge reverted');
        }
    }

    /**
     * Revert Half/Full Approval
     */
    private function revertHalfFullApproval($arrivalTicket)
    {
        if ($arrivalTicket->approvals) {
            $arrivalTicket->approvals->delete();
            $arrivalTicket->update([
                'document_approval_status' => null,
            ]);
            $this->logRevertAction($arrivalTicket, 'half_full_approval_revert', 'Half/Full approval reverted');
        }
    }
    private function revertFreight($arrivalTicket)
    {
        if ($arrivalTicket->freight) {

            $grnNo = $arrivalTicket->arrivalSlip->unique_no;


            // Delete transactions
            $Transaction = Transaction::where('grn_no', $grnNo)->delete();


            // Delete GRN
            //   $arrivalTicket->arrivalSlip->grnNumber()->delete();
            GrnNumber::where('unique_no', $grnNo)->delete();


            // Delete arrival slip
            $arrivalTicket->arrivalSlip->delete();

            // Delete freight
            $arrivalTicket->freight->delete();

            $arrivalTicket->update([
                'freight_status' => 'pending',
                'arrival_slip_status' => null
            ]);

            $this->logRevertAction($arrivalTicket, 'freight_revert', 'Freight reverted');
        }
    }


    /**
     * Revert Complete Ticket (Master Revert)
     */
    private function revertCompleteTicket($arrivalTicket)
    {
        // Revert in reverse order (dependencies first)
        if ($arrivalTicket->approvals) {
            $arrivalTicket->approvals->delete();
        }

        if ($arrivalTicket->secondWeighbridge) {
            $arrivalTicket->secondWeighbridge->delete();
        }

        if ($arrivalTicket->firstWeighbridge) {
            $arrivalTicket->firstWeighbridge->delete();
        }

        if ($arrivalTicket->unloadingLocation) {
            $arrivalTicket->unloadingLocation->delete();
        }

        $this->logRevertAction($arrivalTicket, 'complete_ticket_revert', 'Complete ticket reverted to initial state');
    }

    /**
     * Handle Master Data Updates
     */
    private function handleMasterUpdate($request, $arrivalTicket)
    {
        $validated = $request->validate([
            // Ticket Details
            'truck_no' => 'sometimes|required|string|max:255',
            'bilty_no' => 'sometimes|required|string|max:255',
            'bags' => 'sometimes|required|integer|min:0',
            'sample_money' => 'sometimes|required|numeric|min:0',
            'loading_date' => 'sometimes|required|date',
            'remarks' => 'nullable|string',

            // Loading Weight Details
            'first_weight' => 'sometimes|required|numeric|min:0',
            'second_weight' => 'sometimes|required|numeric|min:0',
            'net_weight' => 'sometimes|required|numeric|min:0',

            // Location Transfer
            'arrival_location_id' => 'sometimes|required|exists:arrival_locations,id',

            // Weighbridge Details
            'arrival_first_weight' => 'sometimes|required|numeric|min:0',
            'arrival_second_weight' => 'sometimes|required|numeric|min:0',

            // Approval Details
            'gala_id' => 'sometimes|required|exists:arrival_sub_locations,id',
            'bag_type_id' => 'sometimes|required|exists:bag_types,id',
            'filling_bags_no' => 'sometimes|required|integer|min:0',
            'bag_condition_id' => 'sometimes|required|exists:bag_conditions,id',
            'bag_packing_id' => 'sometimes|required|exists:bag_packings,id',
            'total_bags' => 'sometimes|required|integer|min:0',
            'total_rejection' => 'sometimes|required|integer|min:0',
            'amanat' => 'sometimes|required|in:Yes,No',
            'note' => 'nullable|string'
        ]);

        // Update basic ticket info
        $ticketUpdates = [];
        $ticketFields = ['truck_no', 'bilty_no', 'bags', 'sample_money', 'loading_date', 'remarks', 'first_weight', 'second_weight', 'net_weight'];

        foreach ($ticketFields as $field) {
            if (isset($validated[$field])) {
                $ticketUpdates[$field] = $validated[$field];
            }
        }

        if (!empty($ticketUpdates)) {
            $arrivalTicket->update($ticketUpdates);
        }

        // Update Location Transfer
        if (isset($validated['arrival_location_id'])) {
            if ($arrivalTicket->unloadingLocation) {
                $arrivalTicket->unloadingLocation->update([
                    'arrival_location_id' => $validated['arrival_location_id']
                ]);
            } else {
                ArrivalUnloadingLocation::create([
                    'arrival_ticket_id' => $arrivalTicket->id,
                    'arrival_location_id' => $validated['arrival_location_id']
                ]);
            }
        }

        // Update First Weighbridge
        if (isset($validated['arrival_first_weight'])) {
            if ($arrivalTicket->firstWeighbridge) {
                $arrivalTicket->firstWeighbridge->update([
                    'weight' => $validated['arrival_first_weight']
                ]);
            } else {
                ArrivalWeighbridge::create([
                    'arrival_ticket_id' => $arrivalTicket->id,
                    'weight' => $validated['arrival_first_weight'],
                    'type' => 'first'
                ]);
            }
        }

        // Update Second Weighbridge
        if (isset($validated['arrival_second_weight'])) {
            if ($arrivalTicket->secondWeighbridge) {
                $arrivalTicket->secondWeighbridge->update([
                    'weight' => $validated['arrival_second_weight']
                ]);
            } else {
                ArrivalWeighbridge::create([
                    'arrival_ticket_id' => $arrivalTicket->id,
                    'weight' => $validated['arrival_second_weight'],
                    'type' => 'second'
                ]);
            }
        }

        // Update Approval Data
        $approvalUpdates = [];
        $approvalFields = ['gala_id', 'bag_type_id', 'filling_bags_no', 'bag_condition_id', 'bag_packing_id', 'total_bags', 'total_rejection', 'amanat', 'note'];

        foreach ($approvalFields as $field) {
            if (isset($validated[$field])) {
                $approvalUpdates[$field] = $validated[$field];
            }
        }

        if (!empty($approvalUpdates)) {
            if ($arrivalTicket->approvals) {
                $arrivalTicket->approvals->update($approvalUpdates);
            } else {
                $arrivalTicket->approvals()->create($approvalUpdates);
            }
        }

        $this->logRevertAction($arrivalTicket, 'master_update', 'Master data updated via revert controller');
    }

    /**
     * Log revert actions for audit trail
     */
    private function logRevertAction($model, $actionType, $description)
    {

        //dd($arrivalTicket->getOriginal(),$arrivalTicket->getAttributes());
        // You can create an audit log model or use activity logs
        try {
            $data = [
                'user_id' => auth()->user()->id,
                'action' => $actionType,
                'description' => $description,
                'model_type' => get_class($model),
                'model_id' => $model->id,
                'old_values' => json_encode($model->getOriginal()),
                'new_values' => json_encode($model->getAttributes()),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent()
            ];

            $d = AuditLog::create($data);

        } catch (\Exception $e) {
            dd($e->getMessage());
            \Log::error('Audit Log Error: ' . $e->getMessage());
            \Log::info('=== AUDIT LOG DEBUG END WITH ERROR ===');
        }
    }

    /**
     * Get revert history for a ticket
     */
    public function revertHistory($id)
    {
        $arrivalTicket = ArrivalTicket::findOrFail($id);
        $revertHistory = \App\Models\AuditLog::where('model_type', get_class($arrivalTicket))
            ->where('model_id', $arrivalTicket->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return view('management.arrival.ticket.revert-history', compact('arrivalTicket', 'revertHistory'));
    }
}