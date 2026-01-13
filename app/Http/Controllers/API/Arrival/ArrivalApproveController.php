<?php

namespace App\Http\Controllers\API\Arrival;

use App\Http\Controllers\Controller;
use App\Models\Arrival\ArrivalTicket;
use App\Models\BagCondition;
use App\Models\BagPacking;
use App\Models\BagType;
use App\Models\ArrivalApprove;
use App\Models\Master\ArrivalSubLocation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Helpers\ApiResponse;

class ArrivalApproveController extends Controller
{
    public function getAvailableTickets(Request $request)
    {
        try {
            $authUser = auth()->user();
            $isSuperAdmin = $authUser->user_type === 'super-admin';

            $tickets = ArrivalTicket::where('first_weighbridge_status', 'completed')
                ->whereNull('document_approval_status')
                ->leftJoin('arrival_sampling_requests', function ($join) {
                    $join->on('arrival_tickets.id', '=', 'arrival_sampling_requests.arrival_ticket_id')
                        ->where('sampling_type', 'inner')
                        ->where('approved_status', 'pending');
                })
                ->whereNull('arrival_sampling_requests.id')
                // ->when(!$isSuperAdmin, function ($q) use ($authUser) {
                //     return $q->whereHas('unloadingLocation', function ($query) use ($authUser) {
                //         $query->where('arrival_location_id', $authUser->arrival_location_id);
                //     });
                // })
                ->whereHas('unloadingLocation', function ($query) {
                    $query->whereIn('arrival_location_id', getUserCurrentCompanyArrivalLocations());
                })
                ->select('arrival_tickets.*')
                ->get()
                ->map(function ($ticket) {
                    if ($ticket->second_qc_status === 'rejected') {
                        $ticket->unloading_approval_status = 'Half Approved';
                    } elseif ($ticket->second_qc_status === 'approved') {
                        $ticket->unloading_approval_status = 'Full Approved';
                    } elseif (is_null($ticket->second_qc_status) && $ticket->first_qc_status === 'approved') {
                        $ticket->unloading_approval_status = 'Full Approved';
                    } else {
                        $ticket->unloading_approval_status = null;
                    }
                    return $ticket;
                });


            return ApiResponse::success($tickets, 'Available tickets retrieved successfully');
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to retrieve available tickets: ' . $e->getMessage(), 500);
        }
    }


    public function getAvailableTicketsInnerSamplingStatusbk(Request $request)
    {
        try {
            $authUser = auth()->user();
            $isSuperAdmin = $authUser->user_type === 'super-admin';

            $tickets = ArrivalTicket::with(['qcProduct:id,name','location:id,name','unloadingLocation.arrivalLocation:id,name'])->where('first_weighbridge_status', 'completed')
                ->whereNull('document_approval_status')
                ->leftJoin('arrival_sampling_requests', function ($join) {
                    $join->on('arrival_tickets.id', '=', 'arrival_sampling_requests.arrival_ticket_id')
                        ->where('sampling_type', 'inner')
                        ->where('approved_status', 'pending');
                })
                // ->whereNull('arrival_sampling_requests.id')
                ->when(!$isSuperAdmin, function ($q) use ($authUser) {
                    return $q->whereHas('unloadingLocation', function ($query) use ($authUser) {
                        $query->where('arrival_location_id', $authUser->arrival_location_id);
                    });
                })
                // ->select(['arrival_tickets.id', 'arrival_tickets.unique_no', 'arrival_tickets.truck_no', 'arrival_tickets.created_at'],'arrival_sampling_requests.*')
                ->select('arrival_tickets.*')
                ->get()
                ->map(function ($ticket) {
                    $ticket->warehouse = $ticket->unloadingLocation->arrivalLocation ?? null;

                    unset($ticket->unloadingLocation);
                    $ticket->slabsQc = SlabTypeWisegetTicketDeductions($ticket);
                    if ($ticket->second_qc_status === 'rejected') {
                        $ticket->unloading_approval_status = 'Half Approved';
                    } elseif ($ticket->second_qc_status === 'approved') {
                        $ticket->unloading_approval_status = 'Full Approved';
                    } elseif (is_null($ticket->second_qc_status) && $ticket->first_qc_status === 'approved') {
                        $ticket->unloading_approval_status = 'Full Approved';
                    } else {
                        $ticket->unloading_approval_status = null;
                    }
                    return $ticket;
                });


            return ApiResponse::success($tickets, 'Available tickets retrieved successfully');
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to retrieve available tickets: ' . $e->getMessage(), 500);
        }
    }

    public function getAvailableTicketsInnerSamplingStatus13Jan2026(Request $request)
    {
        try {
            $authUser = auth()->user();
            $isSuperAdmin = $authUser->user_type === 'super-admin';
    
            $tickets = ArrivalTicket::with([
                'qcProduct:id,name',
                'location:id,name',
                'unloadingLocation.arrivalLocation:id,name'
            ])
            ->where('first_weighbridge_status', 'completed')
            ->whereNull('document_approval_status')
            ->leftJoin('arrival_sampling_requests as inner_req', function ($join) {
                $join->on('arrival_tickets.id', '=', 'inner_req.arrival_ticket_id')
                     ->where('inner_req.sampling_type', 'inner');
            })
            ->leftJoin('arrival_sampling_requests as initial_req', function ($join) {
                $join->on('arrival_tickets.id', '=', 'initial_req.arrival_ticket_id')
                     ->where('initial_req.sampling_type', 'initial');
            })
            // ->when(!$isSuperAdmin, function ($q) use ($authUser) {
            //     return $q->whereHas('unloadingLocation', function ($query) use ($authUser) {
            //         $query->where('arrival_location_id', $authUser->arrival_location_id);
            //     });
            // })
            ->whereHas('unloadingLocation', function ($query) {
                $query->whereIn('arrival_location_id', getUserCurrentCompanyArrivalLocations());
            })
            ->select(
                'arrival_tickets.id',
                'arrival_tickets.unique_no',
                'arrival_tickets.truck_no',
                'arrival_tickets.bilty_no',
                'arrival_tickets.created_at',
                'inner_req.is_done as inner_is_done',
                'inner_req.approved_status as inner_approved_status',
                'inner_req.is_re_sampling as inner_is_re_sampling',
                'initial_req.is_done as initial_is_done',
                'initial_req.approved_status as initial_approved_status',
                'initial_req.is_re_sampling as initial_is_re_sampling'
            )
            ->get()
            ->map(function ($ticket) {

                // Warehouse
                $ticket->warehouse = $ticket->unloadingLocation->arrivalLocation ?? null;
                unset($ticket->unloadingLocation);
            
                // Slabs QC
                $ticket->slabsQc = SlabTypeWisegetTicketDeductions($ticket);
            
                // Document status logic (inner / initial / ticket)
                if (isset($ticket->inner_is_done)) {
                    $status_source = 'inner';
                } elseif (isset($ticket->initial_is_done)) {
                    $status_source = 'initial';
                } else {
                    $status_source = 'ticket';
                }
            
                switch ($status_source) {
                    case 'inner':
                        if ($ticket->inner_is_re_sampling === 'yes') {
                            $ticket->document_status = 'Resampling';
                        } elseif ($ticket->inner_approved_status === 'approved') {
                            $ticket->document_status = 'fully_approved';
                        } elseif ($ticket->inner_approved_status === 'rejected') {
                            $ticket->document_status = 'half_approved';
                        } elseif ($ticket->inner_is_done == 'yes' && $ticket->inner_approved_status === 'pending') {
                            $ticket->document_status = 'Waiting for Approval';
                        } elseif ($ticket->inner_is_done == 'no' && $ticket->inner_approved_status === 'pending') {
                            $ticket->document_status = 'Sampling Pending';
                        } 
                        break;
            
                    case 'initial':
                        if ($ticket->initial_is_re_sampling === 'yes') {
                            $ticket->document_status = 'Resampling';
                        } elseif ($ticket->initial_approved_status === 'approved') {
                            $ticket->document_status = 'fully_approved';
                        } elseif ($ticket->initial_approved_status === 'rejected') {
                            $ticket->document_status = 'full_rejected';
                        } elseif ($ticket->initial_approved_status === 'pending') {
                            $ticket->document_status = 'Waiting for Approval -- initial';
                        }
                        break;
            
                    case 'ticket':
                        $ticket->document_status = $ticket->document_approval_status;
                        break;
                }
            
                // // Combine with sauda type ONLY if current document_status is one of approved / half_approved / full_rejected
                // $approvedStatuses = ['fully_approved', 'half_approved', 'full_rejected'];
                // if (isset($ticket->saudaType->id) && in_array($ticket->document_status, $approvedStatuses)) {
                //     $saudaId = $ticket->saudaType->id;
                //     $docStatus = $ticket->document_status;
            
                //     if ($saudaId == 1) {
                //         if ($docStatus == 'fully_approved') {
                //             $ticket->document_status = 'OK';
                //         } elseif ($docStatus == 'half_approved') {
                //             $ticket->document_status = 'P-RH';
                //         } elseif($docStatus == 'full_rejected') {
                //             $ticket->document_status = 'RF';
                //         }
                //     } elseif ($saudaId == 2) {
                //         if ($docStatus == 'fully_approved') {
                //             $ticket->document_status = 'TS';
                //         } elseif ($docStatus == 'half_approved') {
                //             $ticket->document_status = 'TS-RH';
                //         } elseif($docStatus == 'full_rejected') {
                //             $ticket->document_status = 'RF';
                //         }
                //     } else {
                //         $ticket->document_status = 'RF';
                //     }
                // }
            

            
                return $ticket;
            });
            
    
            return ApiResponse::success($tickets, 'Available tickets retrieved successfully');
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to retrieve available tickets: ' . $e->getMessage(), 500);
        }
    }
    

    public function getAvailableTicketsInnerSamplingStatus(Request $request)
{
    try {
        $authUser = auth()->user();

        $tickets = ArrivalTicket::with([
                'qcProduct:id,name',
                'location:id,name',
                'unloadingLocation.arrivalLocation:id,name'
            ])
            ->where('arrival_tickets.first_weighbridge_status', 'completed')
            ->whereNull('arrival_tickets.document_approval_status')

            /* ===== Latest INNER Sampling ===== */
            ->leftJoin(DB::raw("
                (
                    SELECT r1.*
                    FROM arrival_sampling_requests r1
                    WHERE r1.sampling_type = 'inner'
                    AND r1.id = (
                        SELECT MAX(r2.id)
                        FROM arrival_sampling_requests r2
                        WHERE r2.arrival_ticket_id = r1.arrival_ticket_id
                        AND r2.sampling_type = 'inner'
                    )
                ) AS inner_req
            "), 'arrival_tickets.id', '=', 'inner_req.arrival_ticket_id')

            /* ===== Latest INITIAL Sampling ===== */
            ->leftJoin(DB::raw("
                (
                    SELECT r1.*
                    FROM arrival_sampling_requests r1
                    WHERE r1.sampling_type = 'initial'
                    AND r1.id = (
                        SELECT MAX(r2.id)
                        FROM arrival_sampling_requests r2
                        WHERE r2.arrival_ticket_id = r1.arrival_ticket_id
                        AND r2.sampling_type = 'initial'
                    )
                ) AS initial_req
            "), 'arrival_tickets.id', '=', 'initial_req.arrival_ticket_id')

            /* ===== Location Restriction ===== */
            ->whereHas('unloadingLocation', function ($query) {
                $query->whereIn(
                    'arrival_location_id',
                    getUserCurrentCompanyArrivalLocations()
                );
            })

            /* ===== Select ===== */
            ->select(
                'arrival_tickets.id',
                'arrival_tickets.unique_no',
                'arrival_tickets.truck_no',
                'arrival_tickets.bilty_no',
                'arrival_tickets.created_at',

                'inner_req.is_done as inner_is_done',
                'inner_req.approved_status as inner_approved_status',
                'inner_req.is_re_sampling as inner_is_re_sampling',

                'initial_req.is_done as initial_is_done',
                'initial_req.approved_status as initial_approved_status',
                'initial_req.is_re_sampling as initial_is_re_sampling'
            )
            ->orderBy('arrival_tickets.id', 'desc')
            ->get()

            /* ===== Final Mapping ===== */
            ->map(function ($ticket) {

                // Warehouse
                $ticket->warehouse = $ticket->unloadingLocation->arrivalLocation ?? null;
                unset($ticket->unloadingLocation);

                // Slabs QC
                $ticket->slabsQc = SlabTypeWisegetTicketDeductions($ticket);

                /* ===== Document Status Logic (LATEST ONLY) ===== */
                if (!is_null($ticket->inner_is_done)) {

                    if ($ticket->inner_is_re_sampling === 'yes') {
                        $ticket->document_status = 'Resampling';

                    } elseif ($ticket->inner_approved_status === 'approved') {
                        $ticket->document_status = 'fully_approved';

                    } elseif ($ticket->inner_approved_status === 'rejected') {
                        $ticket->document_status = 'half_approved';

                    } elseif ($ticket->inner_is_done === 'yes') {
                        $ticket->document_status = 'Waiting for Approval';

                    } else {
                        $ticket->document_status = 'Sampling Pending';
                    }

                } elseif (!is_null($ticket->initial_is_done)) {

                    if ($ticket->initial_is_re_sampling === 'yes') {
                        $ticket->document_status = 'Resampling';

                    } elseif ($ticket->initial_approved_status === 'approved') {
                        $ticket->document_status = 'fully_approved';

                    } elseif ($ticket->initial_approved_status === 'rejected') {
                        $ticket->document_status = 'full_rejected';

                    } else {
                        $ticket->document_status = 'Waiting for Approval';
                    }

                } else {
                    $ticket->document_status = 'Sampling Pending';
                }

                return $ticket;
            });

        return ApiResponse::success($tickets, 'Available tickets retrieved successfully');

    } catch (\Exception $e) {
        return ApiResponse::error(
            'Failed to retrieve available tickets: ' . $e->getMessage(),
            500
        );
    }
}


    public function store(Request $request)
    {
        try {
            $validator = Validator::make(
                $request->all(),
                [
                    'arrival_ticket_id' => 'required|exists:arrival_tickets,id',
                    'company_id' => 'required',
                    'truck_no' => 'required|string',
                    'gala_id' => 'required|exists:arrival_sub_locations,id',
                    'bag_type_id' => 'required|exists:bag_types,id',
                    'bag_packing_approval' => [
                        'required',
                        function ($attribute, $value, $fail) {
                            if (!in_array($value, ['Half Approved', 'Full Approved'])) {
                                $fail('The packing approval must be either "Half Approved" or "Full Approved".');
                            }
                        }
                    ],
                    'total_bags' => 'required|integer|min:1',
                    'total_rejection' => 'nullable|integer',
                    'amanat' => [
                        'required',
                        function ($attribute, $value, $fail) {
                            if (!in_array($value, ['Yes', 'No'])) {
                                $fail('The amanat must be either "Yes" or "No".');
                            }
                        }
                    ],
                    'note' => 'nullable|string',

                    // // ✅ Conditionally required fields
                    // 'filling_bags_no' => 'required_if:bag_type_id,15|integer',
                    // 'bag_condition_id' => 'required_if:bag_type_id,15|exists:bag_conditions,id',
                    // 'bag_packing_id' => 'required_if:bag_type_id,15|exists:bag_packings,id',
                ],
                [
                    // ✅ Custom error messages (optional)
                    'gala_id.required' => 'The Gala field is required.',
                    'gala_id.exists' => 'The selected Gala is invalid.',
                ],
            );

            $validator->sometimes('total_rejection', 'required|integer|min:1', function ($input) {
                return $input->bag_packing_approval === 'Half Approved' || isset($input->is_rejected_ticket);
            });

            if ($validator->fails()) {
                return ApiResponse::error('Validation failed', 422, $validator->errors());
            }

            $ticket = ArrivalTicket::where('id', $request->arrival_ticket_id)
                ->where('first_weighbridge_status', 'completed')
                ->whereNull('document_approval_status')
                ->whereNotExists(function ($query) {
                    $query->select('id')
                        ->from('arrival_sampling_requests')
                        ->whereColumn('arrival_ticket_id', 'arrival_tickets.id')
                        ->where('sampling_type', 'inner')
                        ->where('approved_status', 'pending');
                })
                ->first();

            if (!$ticket) {
                return ApiResponse::error('This ticket is not eligible for approval. Please check if it has completed first weighbridge and has no pending inner sampling requests.', 422);
            }

            $gala = ArrivalSubLocation::where('id', $request->gala_id)->first();

            $request['gala_name'] = $gala ? $gala->name : null;
            $request['creator_id'] = auth()->user()->id;
            $request['remark'] = $request->note ?? '';

            $arrivalApprove = ArrivalApprove::create($request->all());

            ArrivalTicket::where('id', $request->arrival_ticket_id)
                ->update(['document_approval_status' => $request->bag_packing_approval == 'Half Approved' ? 'half_approved' : 'fully_approved', 'second_weighbridge_status' => 'pending']);

            return ApiResponse::success($arrivalApprove, 'Arrival Approval created successfully', 201);
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to create arrival approval: ' . $e->getMessage(), 500);
        }
    }
}
