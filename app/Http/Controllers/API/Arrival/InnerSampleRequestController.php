<?php

namespace App\Http\Controllers\API\Arrival;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Arrival\InnerSamplingRequest;
use App\Models\Arrival\ArrivalSamplingRequest;
use App\Models\Arrival\ArrivalTicket;
use App\Models\Arrival\ArrivalLocationTransfer;
use App\Models\Master\ArrivalLocation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class InnerSampleRequestController extends Controller
{
    public function getAvailableTickets(Request $request)
    {
        try {
            $authUser = auth()->user();
            $isSuperAdmin = $authUser->user_type === 'super-admin';

            $tickets = ArrivalTicket::where('first_weighbridge_status', 'completed')
                ->where(function ($q) {
                    $q->where('document_approval_status', '!=', 'fully_approved')
                        ->where('document_approval_status', '!=', 'half_approved')
                        ->orWhereNull('document_approval_status');
                })
                ->leftJoin('arrival_sampling_requests', function ($join) {
                    $join->on('arrival_tickets.id', '=', 'arrival_sampling_requests.arrival_ticket_id')
                        ->where('sampling_type', 'inner')
                        ->where('approved_status', 'pending');
                })
                ->when(!$isSuperAdmin, function ($q) use ($authUser) {
                    return $q->whereHas('unloadingLocation', function ($query) use ($authUser) {
                        $query->where('arrival_location_id', $authUser->arrival_location_id);
                    });
                })
                ->whereNull('arrival_sampling_requests.id')
                ->select('arrival_tickets.*')
                ->distinct()
                ->get();

            $responseData = [
                'success' => true,
                'message' => 'Available tickets fetched successfully',
                'data' => $tickets
            ];

            return ApiResponse::success($responseData, 'Available tickets fetched successfully');
        } catch (\Exception $e) {
            return ApiResponse::error('Server error', 500, [
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    public function store(InnerSamplingRequest $request)
    {
        try {
            $validated = $request->all();

            $ticket = ArrivalTicket::findOrFail($validated['ticket_id']);

            if ($ticket->document_approval_status === 'fully_approved' || $ticket->document_approval_status === 'half_approved') {
                return ApiResponse::error('This truck has already been approved', 422, [
                    'success' => false,
                    'message' => 'This truck has already been approved'
                ]);
            }

            $existingRequest = ArrivalSamplingRequest::where('arrival_ticket_id', $validated['ticket_id'])
                ->where('sampling_type', 'inner')
                ->where('approved_status', 'pending')
                ->first();

            if ($existingRequest) {
                return ApiResponse::error('A pending inner sampling request already exists for this ticket', 422, [
                    'success' => false,
                    'message' => 'A pending inner sampling request already exists for this ticket'
                ]);
            }

            $authUser = auth()->user();
            $isSuperAdmin = $authUser->user_type === 'super-admin';

            if (!$isSuperAdmin) {
                $hasAccess = $ticket->unloadingLocation &&
                    $ticket->unloadingLocation->arrival_location_id == $authUser->arrival_location_id;

                if (!$hasAccess) {
                    return ApiResponse::error('You do not have permission to create sampling requests for this location', 403, [
                        'success' => false,
                        'message' => 'You do not have permission to create sampling requests for this location'
                    ]);
                }
            }

            $arrivalSampleReq = ArrivalSamplingRequest::create([
                'company_id'        => $validated['company_id'],
                'arrival_ticket_id' => $validated['ticket_id'],
                'sampling_type'     => 'inner',
                'is_re_sampling'    => 'no',
                'is_done'           => 'no',
                'remark'            => $request->remark ?? null,
                'created_by'        => auth()->id(),
            ]);

            $responseData = [
                'success' => true,
                'message' => 'Inner Sampling Request created successfully',
                'data' => $arrivalSampleReq
            ];

            return ApiResponse::success($responseData, 'Inner Sampling Request created successfully', 201);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return ApiResponse::error('Ticket not found', 404, [
                'success' => false,
                'message' => 'Ticket not found'
            ]);
        } catch (\Exception $e) {
            return ApiResponse::error('Server error', 500, [
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
}
