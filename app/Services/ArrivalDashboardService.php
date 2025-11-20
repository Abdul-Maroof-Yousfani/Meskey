<?php

namespace App\Services;

use App\Models\Arrival\ArrivalTicket;
use App\Models\Arrival\ArrivalSamplingRequest;
use Carbon\Carbon;

class ArrivalDashboardService
{
    public function getArrivalDashboardData($fromDate, $toDate, $companyId)
    {

        $authUser = auth()->user();
        $dateRange = [Carbon::parse($fromDate)->startOfDay(), Carbon::parse($toDate)->endOfDay()];

        $totalTickets = ArrivalTicket::where('company_id', $companyId)
            // ->whereIn('first_qc_status', ['pending', 'resampling'])
            ->when(auth()->user()->user_type != 'super-admin', function ($q) {
                return $q->where('location_id', auth()->user()->company_location_id);
            })
            ->whereBetween('created_at', $dateRange)
            ->count();

        $newTickets = ArrivalTicket::where('company_id', $companyId)
            ->whereIn('first_qc_status', ['pending', 'resampling'])
            ->whereBetween('created_at', $dateRange)
            ->count();


        $initialSamplingRequested = ArrivalSamplingRequest::whereHas('arrivalTicket', function ($q) use ($companyId, $dateRange) {
            $q->where('company_id', $companyId)
                ->whereBetween('created_at', $dateRange);
        })

            //Superadmin
            ->when(auth()->user()->user_type != 'super-admin', function ($q) {
                return $q->whereHas('arrivalTicket', function ($sq) {
                    $sq->where('location_id', auth()->user()->company_location_id);
                });
            })
            ->where('sampling_type', 'initial')
            ->where('is_done', 'no')
            ->count();



        $initialSamplingDone = ArrivalSamplingRequest::whereHas('arrivalTicket', function ($q) use ($companyId, $dateRange) {
            $q->where('company_id', $companyId)
                ->whereBetween('created_at', $dateRange);
        })
        
            ->where('sampling_type', 'initial')
            ->where('is_done', 'yes')
            ->where('approved_status', 'pending')
              // Superadmin
              ->when(auth()->user()->user_type != 'super-admin', function ($q) {
                return $q->whereHas('arrivalTicket', function ($sq) {
                    $sq->where('location_id', auth()->user()->company_location_id);
                });
            })
            ->count();

        $resamplingRequired = ArrivalSamplingRequest::whereHas('arrivalTicket', function ($q) use ($companyId, $dateRange) {
            $q->where('company_id', $companyId)
                ->whereBetween('created_at', $dateRange);
        })
            ->where('sampling_type', 'initial')
            ->where('is_re_sampling', 'yes')
            ->where('is_done', 'no')
            ->count();

        $locationTransferPending = ArrivalTicket::where('company_id', $companyId)
            ->where('location_transfer_status', 'pending')
            //superadmin
            ->when(auth()->user()->user_type != 'super-admin', function ($q) {
                return $q->where('location_id', auth()->user()->company_location_id);
            })
            ->whereBetween('created_at', $dateRange)
            ->count();

        $rejectedTickets = ArrivalTicket::where('company_id', $companyId)
            ->where('first_qc_status', 'rejected')
            // ->where('bilty_return_confirmation', 0)
            ->when(auth()->user()->user_type != 'super-admin', function ($q) {
                return $q->where('location_id', auth()->user()->company_location_id);
            })
            ->whereBetween('created_at', $dateRange)
            ->count();

        $completedTickets = ArrivalTicket::where('company_id', $companyId)
            ->where('arrival_slip_status', 'generated')
            ->whereBetween('created_at', $dateRange)
            // Superadmin
            ->when(auth()->user()->user_type != 'super-admin', function ($q) {
                return $q->where('location_id', auth()->user()->company_location_id);
            })
            ->when(
                $authUser->user_type != 'super-admin' && $authUser->arrival_location_id,
                function ($query) use ($authUser) {
                    return $query->whereHas('unloadingLocation', function ($q) use ($authUser) {
                        $q->where('arrival_location_id', $authUser->arrival_location_id);
                    });
                }
            )
            ->count();

        $firstWeighbridgePending = ArrivalTicket::where('company_id', $companyId)
            ->where('location_transfer_status', 'transfered')
            ->where('first_weighbridge_status', 'pending')
            //superadmin
            ->when(auth()->user()->user_type != 'super-admin', function ($q) {
                return $q->where('location_id', auth()->user()->company_location_id);
            })
            ->when(
                auth()->user()->user_type != 'super-admin' && auth()->user()->arrival_location_id,
                function ($query) use ($authUser) {
                    return $query->whereHas('unloadingLocation', function ($q) use ($authUser) {
                        $q->where('arrival_location_id', $authUser->arrival_location_id);
                    });
                }
            )
            ->whereBetween('created_at', $dateRange)
            ->count();

        $innerSamplingRequested = ArrivalSamplingRequest::whereHas('arrivalTicket', function ($q) use ($companyId, $dateRange) {
            $q->where('company_id', $companyId)
                ->whereBetween('created_at', $dateRange);
        })
            ->when(auth()->user()->user_type != 'super-admin', function ($q) {
                return $q->whereHas('arrivalTicket', function ($sq) {
                    $sq->where('location_id', auth()->user()->company_location_id);
                });
            })
            ->where('sampling_type', 'inner')
            ->where('is_done', 'no')
            ->count();

        $innerSamplingPendingApproval = ArrivalSamplingRequest::whereHas('arrivalTicket', function ($q) use ($companyId, $dateRange) {
            $q->where('company_id', $companyId)
                ->whereBetween('created_at', $dateRange);
        })
            ->where('sampling_type', 'inner')
            ->where('is_done', 'yes')
            ->where('approved_status', 'pending')
            ->count();

        $decisionOnAverageEnabled = ArrivalTicket::where('company_id', $companyId)
            ->where('decision_making', 1)
            ->whereBetween('created_at', $dateRange)
            ->count();

        $halfFullApprovePending = ArrivalTicket::where('company_id', $companyId)
            ->where('first_weighbridge_status', 'completed')
            ->whereNull('document_approval_status')
            ->whereDoesntHave('arrivalSamplingRequests', function ($q) {
                $q->where('sampling_type', 'inner')
                    ->where('approved_status', 'pending');
            })
            //superadmin
            ->when(auth()->user()->user_type != 'super-admin', function ($q) {
                return $q->where('location_id', auth()->user()->company_location_id);
            })
            ->whereBetween('created_at', $dateRange)
            ->count();

        $secondWeighbridgePending = ArrivalTicket::where('company_id', $companyId)
            ->whereIn('document_approval_status', ['half_approved', 'fully_approved'])
            ->where('second_weighbridge_status', 'pending')
            //superadmin
            ->when(auth()->user()->user_type != 'super-admin', function ($q) {
                return $q->where('location_id', auth()->user()->company_location_id);
            })
            ->whereBetween('created_at', $dateRange)
            ->count();

        $freightPending = ArrivalTicket::where('company_id', $companyId)
            ->where('second_weighbridge_status', 'completed')
            ->whereNull('freight_status')
            ->where('decision_making', 0)
            ->whereBetween('created_at', $dateRange)
            ->count();

        $freightReady = ArrivalTicket::where('company_id', $companyId)
            ->where('second_weighbridge_status', 'completed')
            ->where('freight_status', 'pending')
            ->whereBetween('created_at', $dateRange)
            ->count();

        return [
            'total_tickets' => $totalTickets,
            'new_tickets' => $newTickets,
            'initial_sampling_requested' => $initialSamplingRequested,
            'initial_sampling_done' => $initialSamplingDone,
            'resampling_required' => $resamplingRequired,
            'location_transfer_pending' => $locationTransferPending,
            'rejected_tickets' => $rejectedTickets,
            'completed_tickets' => $completedTickets,
            'first_weighbridge_pending' => $firstWeighbridgePending,
            'inner_sampling_requested' => $innerSamplingRequested,
            'inner_sampling_pending_approval' => $innerSamplingPendingApproval,
            'decision_on_average_enabled' => $decisionOnAverageEnabled,
            'freight_ready' => $freightReady,
            'half_full_approve_pending' => $halfFullApprovePending,
            'second_weighbridge_pending' => $secondWeighbridgePending,
        ];
    }

}
