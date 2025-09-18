<?php

namespace App\Http\Controllers\API\Arrival;

use App\Http\Controllers\Controller;
use App\Models\Arrival\ArrivalTicket;
use App\Models\BagCondition;
use App\Models\BagPacking;
use App\Models\BagType;
use App\Models\ArrivalApprove;
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
                ->when(!$isSuperAdmin, function ($q) use ($authUser) {
                    return $q->whereHas('unloadingLocation', function ($query) use ($authUser) {
                        $query->where('arrival_location_id', $authUser->arrival_location_id);
                    });
                })
                ->select('arrival_tickets.*')
                ->get();

            return ApiResponse::success($tickets, 'Available tickets retrieved successfully');
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to retrieve available tickets: ' . $e->getMessage(), 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'arrival_ticket_id' => 'required|exists:arrival_tickets,id',
                'gala_name' => 'required|string',
                'company_id' => 'required',
                'truck_no' => 'required|string',
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
                'note' => 'nullable|string'
            ]);

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
