<?php

namespace App\Services;

use App\Models\Arrival\ArrivalTicket;
use App\Models\Arrival\ArrivalSamplingRequest;
use App\Models\Master\ArrivalLocation;
use Carbon\Carbon;

class ArrivalDashboardService
{
    public function getArrivalDashboardData($fromDate, $toDate, $companyId, $request = null)
    {

        $authUser = auth()->user();
        $dateRange = [Carbon::parse($fromDate)->startOfDay(), Carbon::parse($toDate)->endOfDay()];

        $oldpendingtrucks = ArrivalTicket::where('company_id', $companyId)
            ->when($request->has('location_id') && $request->location_id != '', function ($q) use ($request) {
                return $q->where('location_id', $request->location_id);
            })
            ->whereIn('location_id', getUserCurrentCompanyLocations())
            ->where(function ($q) {
                $q->whereNull('arrival_slip_status')
                    ->orWhere('arrival_slip_status', '!=', 'generated');
            })
            ->where('first_qc_status', '!=', 'rejected')
            ->where('created_at', '<', Carbon::parse($fromDate)->startOfDay())
            ->count();


        $totalTickets = ArrivalTicket::where('company_id', $companyId)
            // ->whereIn('first_qc_status', ['pending', 'resampling'])
            // ->when(auth()->user()->user_type != 'super-admin', function ($q) {
            //     return $q->where('location_id', auth()->user()->company_location_id);
            // })
            ->when($request->has('location_id') && $request->location_id != '', function ($q) use ($request) {
                return $q->where('location_id', $request->location_id);
            })
            ->whereIn('location_id', getUserCurrentCompanyLocations())
            ->whereBetween('created_at', $dateRange)
            ->count();

        $inprocesstickets = ArrivalTicket::where('company_id', $companyId)
            // ->whereIn('first_qc_status', ['pending', 'resampling'])
            // ->when(auth()->user()->user_type != 'super-admin', function ($q) {
            //     return $q->where('location_id', auth()->user()->company_location_id);
            // })
            ->when($request->has('location_id') && $request->location_id != '', function ($q) use ($request) {
                return $q->where('location_id', $request->location_id);
            })
            ->where('first_qc_status', '!=', 'rejected')
            ->where(function ($q) {
                $q->whereNull('arrival_slip_status')
                    ->orWhere('arrival_slip_status', '!=', 'generated');
            })
            ->whereIn('location_id', getUserCurrentCompanyLocations())
            ->whereBetween('created_at', $dateRange)
            ->count();

        $totalTicketsKgs = ArrivalTicket::where('company_id', $companyId)
            // ->whereIn('first_qc_status', ['pending', 'resampling'])
            // ->when(auth()->user()->user_type != 'super-admin', function ($q) {
            //     return $q->where('location_id', auth()->user()->company_location_id);
            // })
            ->when($request->has('location_id') && $request->location_id != '', function ($q) use ($request) {
                return $q->where('location_id', $request->location_id);
            })
            ->whereIn('location_id', getUserCurrentCompanyLocations())
            ->whereBetween('created_at', $dateRange)
            ->sum('net_weight');


        $newTickets = ArrivalTicket::where('company_id', $companyId)
            ->whereIn('first_qc_status', ['pending', 'resampling'])
            ->whereBetween('created_at', $dateRange)
            ->count();


        $initialSamplingRequested = ArrivalSamplingRequest::whereHas('arrivalTicket', function ($q) use ($companyId, $dateRange) {
            $q->where('company_id', $companyId)
                ->whereBetween('created_at', $dateRange);
        })

            //Superadmin
            // ->when(auth()->user()->user_type != 'super-admin', function ($q) {
            //     return $q->whereHas('arrivalTicket', function ($sq) {
            //         $sq->where('location_id', auth()->user()->company_location_id   );
            //     });
            // })
            ->when($request->has('location_id') && $request->location_id != '', function ($q) use ($request) {
                return $q->whereHas('arrivalTicket', function ($sq) use ($request) {
                    $sq->where('location_id', $request->location_id);
                });
            })
            ->whereHas('arrivalTicket', function ($q) {
                $q->whereIn('location_id', getUserCurrentCompanyLocations());
            })
            ->where('sampling_type', 'initial')
            ->where('is_done', 'no')
            ->where('is_re_sampling', 'no')
            ->count();


        $initialRe_SamplingRequested = ArrivalSamplingRequest::whereHas('arrivalTicket', function ($q) use ($companyId, $dateRange) {
            $q->where('company_id', $companyId)
                ->whereBetween('created_at', $dateRange);
        })

            //Superadmin
            // ->when(auth()->user()->user_type != 'super-admin', function ($q) {
            //     return $q->whereHas('arrivalTicket', function ($sq) {
            //         $sq->where('location_id', auth()->user()->company_location_id   );
            //     });
            // })
            ->when($request->has('location_id') && $request->location_id != '', function ($q) use ($request) {
                return $q->whereHas('arrivalTicket', function ($sq) use ($request) {
                    $sq->where('location_id', $request->location_id);
                });
            })
            ->whereHas('arrivalTicket', function ($q) {
                $q->whereIn('location_id', getUserCurrentCompanyLocations());
            })
            ->where('sampling_type', 'initial')
            ->where('is_re_sampling', 'yes')
            ->where('is_done', 'no')
            ->count();



        $initialSamplingDone = ArrivalSamplingRequest::whereHas('arrivalTicket', function ($q) use ($companyId, $dateRange) {
            $q->where('company_id', $companyId)
                ->whereBetween('created_at', $dateRange);
        })

            ->where('sampling_type', 'initial')
            ->where('is_done', 'yes')
            ->where('approved_status', 'pending')
            ->when($request->has('location_id') && $request->location_id != '', function ($q) use ($request) {
                return $q->whereHas('arrivalTicket', function ($sq) use ($request) {
                    $sq->where('location_id', $request->location_id);
                });
            })
            ->whereHas('arrivalTicket', function ($q) {
                $q->whereIn('location_id', getUserCurrentCompanyLocations());
            })
            // Superadmin
            // ->when(auth()->user()->user_type != 'super-admin', function ($q) {
            //         return $q->whereHas('arrivalTicket', function ($sq) {
            //             $sq->where('location_id', auth()->user()->company_location_id);
            //         });
            //     })
            ->count();

        $initialSamplingDonePurchaserwise = ArrivalSamplingRequest::whereHas('arrivalTicket', function ($q) use ($companyId, $dateRange) {
            $q->where('company_id', $companyId)
                ->whereBetween('created_at', $dateRange);
        })

            ->where('sampling_type', 'initial')
            ->where('is_done', 'yes')
            ->where('approved_status', 'pending')
            ->when($request->has('location_id') && $request->location_id != '', function ($q) use ($request) {
                return $q->whereHas('arrivalTicket', function ($sq) use ($request) {
                    $sq->where('location_id', $request->location_id);
                });
            })
            ->whereHas('arrivalTicket', function ($q) {
                $q->whereIn('location_id', getUserCurrentCompanyLocations());
            });
        // Superadmin
        // ->when(auth()->user()->user_type != 'super-admin', function ($q) {
        //         return $q->whereHas('arrivalTicket', function ($sq) {
        //             $sq->where('location_id', auth()->user()->company_location_id);
        //         });
        //     })



        $resamplingRequired = ArrivalSamplingRequest::whereHas('arrivalTicket', function ($q) use ($companyId, $dateRange) {
            $q->where('company_id', $companyId)
                ->whereBetween('created_at', $dateRange);
        })
            // ->when(auth()->user()->user_type != 'super-admin', function ($q) {
            //     return $q->whereHas('arrivalTicket', function ($sq) {
            //         $sq->where('location_id', auth()->user()->company_location_id);
            //     });
            // })
            ->when($request->has('location_id') && $request->location_id != '', function ($q) use ($request) {
                return $q->whereHas('arrivalTicket', function ($sq) use ($request) {
                    $sq->where('location_id', $request->location_id);
                });
            })
            ->whereHas('arrivalTicket', function ($q) {
                $q->whereIn('location_id', getUserCurrentCompanyLocations());
            })
            ->where('sampling_type', 'initial')
            ->where('is_re_sampling', 'yes')
            ->where('is_done', 'no')
            ->count();

        $locationTransferPending = ArrivalTicket::where('company_id', $companyId)
            ->where('location_transfer_status', 'pending')
            //superadmin
            // ->when(auth()->user()->user_type != 'super-admin', function ($q) {
            //     return $q->where('location_id', auth()->user()->company_location_id);
            // })
            ->when($request->has('location_id') && $request->location_id != '', function ($q) use ($request) {
                return $q->where('location_id', $request->location_id);
            })
            ->whereIn('location_id', getUserCurrentCompanyLocations())
            ->whereBetween('created_at', $dateRange)
            ->count();

        $rejectedTickets = ArrivalTicket::where('company_id', $companyId)
            ->where('first_qc_status', 'rejected')
            // ->where('bilty_return_confirmation', 0)
            // ->when(auth()->user()->user_type != 'super-admin', function ($q) {
            //     return $q->where('location_id', auth()->user()->company_location_id);
            // })
            ->when($request->has('location_id') && $request->location_id != '', function ($q) use ($request) {
                return $q->where('location_id', $request->location_id);
            })
            ->whereIn('location_id', getUserCurrentCompanyLocations())
            ->whereBetween('created_at', $dateRange)
            ->count();


        $halfRejectedTickets = ArrivalTicket::where('company_id', $companyId)
            ->where('document_approval_status', 'half_approved')
            // ->where('bilty_return_confirmation', 0)
            // ->when(auth()->user()->user_type != 'super-admin', function ($q) {
            //     return $q->where('location_id', auth()->user()->company_location_id);
            // })
            ->when($request->has('location_id') && $request->location_id != '', function ($q) use ($request) {
                return $q->where('location_id', $request->location_id);
            })
            ->whereHas('unloadingLocation', function ($q) {
                return $q->whereIn('arrival_location_id', getUserCurrentCompanyArrivalLocations());
            })
            ->whereIn('location_id', getUserCurrentCompanyLocations())
            ->whereBetween('created_at', $dateRange)
            ->count();

        $completedTickets = ArrivalTicket::where('company_id', $companyId)
            ->where('arrival_slip_status', 'generated')
            ->whereBetween('created_at', $dateRange)
            ->when($request->has('location_id') && $request->location_id != '', function ($q) use ($request) {
                return $q->where('location_id', $request->location_id);
            })
            ->whereIn('location_id', getUserCurrentCompanyLocations())
            ->whereHas('unloadingLocation', function ($q) {
                $q->whereIn('arrival_location_id', getUserCurrentCompanyArrivalLocations());
            })
            // Superadmin
            // ->when(auth()->user()->user_type != 'super-admin', function ($q) {
            //     return $q->where('location_id', auth()->user()->company_location_id);
            // })
            // ->when(
            //     $authUser->user_type != 'super-admin' && $authUser->arrival_location_id,
            //     function ($query) use ($authUser) {
            //         return $query->whereHas('unloadingLocation', function ($q) use ($authUser) {
            //             $q->where('arrival_location_id', $authUser->arrival_location_id);
            //         });
            //     }
            // )
            ->count();

        $firstWeighbridgePending = ArrivalTicket::where('company_id', $companyId)
            ->where('location_transfer_status', 'transfered')
            ->where('first_weighbridge_status', 'pending')
            //superadmin
            // ->when(auth()->user()->user_type != 'super-admin', function ($q) {
            //     return $q->where('location_id', auth()->user()->company_location_id);
            // })
            // ->when(
            //     auth()->user()->user_type != 'super-admin' && auth()->user()->arrival_location_id,
            //     function ($query) use ($authUser) {
            //         return $query->whereHas('unloadingLocation', function ($q) use ($authUser) {
            //             $q->where('arrival_location_id', $authUser->arrival_location_id);
            //         });
            //     }
            // )
            ->when($request->has('location_id') && $request->location_id != '', function ($q) use ($request) {
                return $q->where('location_id', $request->location_id);
            })
            ->whereIn('location_id', getUserCurrentCompanyLocations())
            ->whereHas('unloadingLocation', function ($q) {
                $q->whereIn('arrival_location_id', getUserCurrentCompanyArrivalLocations());
            })
            ->whereBetween('created_at', $dateRange)
            ->count();



        $firstWeighbridgePendingLocationwisebk = ArrivalTicket::selectRaw('arrival_locations.name as location_name, COUNT(arrival_tickets.id) as total')
            ->join('arrival_location_transfers', 'arrival_tickets.id', '=', 'arrival_location_transfers.arrival_ticket_id')
            ->join('arrival_locations', 'arrival_location_transfers.arrival_location_id', '=', 'arrival_locations.id')
            ->where('arrival_tickets.company_id', $companyId)
            ->where('arrival_tickets.location_transfer_status', 'transfered')
            ->where('arrival_tickets.first_weighbridge_status', 'pending')
            ->when($request->has('location_id') && $request->location_id != '', function ($q) use ($request) {
                return $q->where('arrival_tickets.location_id', $request->location_id);
            })
            ->whereIn('arrival_tickets.location_id', getUserCurrentCompanyLocations())
            ->whereIn('arrival_locations.id', getUserCurrentCompanyArrivalLocations())
            ->whereBetween('arrival_tickets.created_at', $dateRange)
            ->groupBy('arrival_locations.id', 'arrival_locations.name')
            ->get();




        // Get all locations
        $locations = ArrivalLocation::whereIn('id', getUserCurrentCompanyArrivalLocations())
            ->when($request->has('location_id') && $request->location_id != '', function ($q) use ($request) {
                // Agar location_id filter hai toh sirf woh location dikhao
                return $q->where('company_location_id', $request->location_id);
            })
            ->select('id', 'name')
            ->get();

        // Get the counts for locations with data
        $countsQuery = ArrivalTicket::selectRaw("
        arrival_locations.id as location_id,
        COUNT(CASE WHEN arrival_tickets.first_weighbridge_status = 'pending' THEN 1 END) as first_count,
        COUNT(CASE WHEN arrival_tickets.second_weighbridge_status = 'pending' THEN 1 END) as second_count
    ")
            ->join('arrival_location_transfers', 'arrival_tickets.id', '=', 'arrival_location_transfers.arrival_ticket_id')
            ->join('arrival_locations', 'arrival_location_transfers.arrival_location_id', '=', 'arrival_locations.id')
            ->where('arrival_tickets.company_id', $companyId)
            ->whereIn('arrival_tickets.document_approval_status', ['half_approved', 'fully_approved'])
            ->where('arrival_tickets.location_transfer_status', 'transfered')
            ->whereIn('arrival_tickets.location_id', getUserCurrentCompanyLocations())
            ->whereIn('arrival_locations.id', getUserCurrentCompanyArrivalLocations())
            ->whereBetween('arrival_tickets.created_at', $dateRange);

        // Apply location_id filter on arrival_tickets.location_id (original logic)
        if ($request->has('location_id') && $request->location_id != '') {
            $countsQuery->where('arrival_tickets.location_id', $request->location_id);
        }

        $counts = $countsQuery->groupBy('arrival_locations.id')
            ->get()
            ->keyBy('location_id'); // Key by location_id for easy merging
        // dd($locations);
        // Merge the data
        $weighbridgePendingLocationwise = $locations->map(function ($location) use ($counts) {
            return (object) [
                'location_name' => $location->name,
                'location_id' => $location->id,
                'first_count' => $counts[$location->id]->first_count ?? 0,
                'second_count' => $counts[$location->id]->second_count ?? 0,
            ];
        });










        // Get the counts for locations with data
        $unloadingcountsQuery = ArrivalTicket::selectRaw("
    arrival_locations.id as location_id,
    COUNT(DISTINCT arrival_tickets.id) as unloading_count
")
            ->join('arrival_location_transfers', 'arrival_tickets.id', '=', 'arrival_location_transfers.arrival_ticket_id')
            ->join('arrival_locations', 'arrival_location_transfers.arrival_location_id', '=', 'arrival_locations.id')
            ->where('arrival_tickets.company_id', $companyId)
            ->whereIn('arrival_tickets.location_id', getUserCurrentCompanyLocations())
            ->whereIn('arrival_locations.id', getUserCurrentCompanyArrivalLocations())
            ->where('arrival_tickets.first_weighbridge_status', 'completed')
            ->whereNull('arrival_tickets.document_approval_status')
            ->whereDoesntHave('arrivalSamplingRequests', function ($q) {
                $q->where('sampling_type', 'inner')
                    ->where('approved_status', 'pending');
            })
            ->whereBetween('arrival_tickets.created_at', $dateRange);

        // Apply location_id filter on arrival_tickets.location_id (original logic)
        if ($request->has('location_id') && $request->location_id != '') {
            $unloadingcountsQuery->where('arrival_tickets.location_id', $request->location_id);
        }

        $countsd = $unloadingcountsQuery->groupBy('arrival_locations.id')
            ->get()
            ->keyBy('location_id');

        // Debug to see what's actually being returned
// dd($countsd);

        // Merge the data
        $unloadingPendingLocationwise = $locations->map(function ($location) use ($countsd) {
            return (object) [
                'location_name' => $location->name,
                'location_id' => $location->id,
                'unloading_count' => isset($countsd[$location->id]) ? $countsd[$location->id]->unloading_count : 0,
            ];
        });
        // dd($weighbridgePendingLocationwise);

        $innerSamplingRequested = ArrivalSamplingRequest::whereHas('arrivalTicket', function ($q) use ($companyId, $dateRange) {
            $q->where('company_id', $companyId)
                ->whereBetween('created_at', $dateRange);
        })
            // ->when(auth()->user()->user_type != 'super-admin', function ($q) {
            //     return $q->whereHas('arrivalTicket', function ($sq) {
            //         $sq->where('location_id', auth()->user()->company_location_id);
            //     });
            // })
            ->when($request->has('location_id') && $request->location_id != '', function ($q) use ($request) {
                return $q->whereHas('arrivalTicket', function ($sq) use ($request) {
                    $sq->where('location_id', $request->location_id);
                });
            })
            ->whereHas('arrivalTicket', function ($q) {
                $q->whereIn('location_id', getUserCurrentCompanyLocations());
            })
            ->where('sampling_type', 'inner')
            ->where('is_re_sampling', 'no')
            ->where('is_done', 'no')
            ->count();


        $innerRe_SamplingRequested = ArrivalSamplingRequest::whereHas('arrivalTicket', function ($q) use ($companyId, $dateRange) {
            $q->where('company_id', $companyId)
                ->whereBetween('created_at', $dateRange);
        })
            // ->when(auth()->user()->user_type != 'super-admin', function ($q) {
            //     return $q->whereHas('arrivalTicket', function ($sq) {
            //         $sq->where('location_id', auth()->user()->company_location_id);
            //     });
            // })
            ->when($request->has('location_id') && $request->location_id != '', function ($q) use ($request) {
                return $q->whereHas('arrivalTicket', function ($sq) use ($request) {
                    $sq->where('location_id', $request->location_id);
                });
            })
            ->whereHas('arrivalTicket', function ($q) {
                $q->whereIn('location_id', getUserCurrentCompanyLocations());
            })
            ->where('sampling_type', 'inner')
            ->where('is_re_sampling', 'yes')
            ->where('is_done', 'no')
            ->count();

        $innerSamplingPendingApproval = ArrivalSamplingRequest::whereHas('arrivalTicket', function ($q) use ($companyId, $dateRange) {
            $q->where('company_id', $companyId)
                ->whereBetween('created_at', $dateRange);
        })
            // ->when(auth()->user()->user_type != 'super-admin', function ($q) {
            //     return $q->whereHas('arrivalTicket', function ($sq) {
            //         $sq->where('location_id', auth()->user()->company_location_id);
            //     });
            // })
            ->when($request->has('location_id') && $request->location_id != '', function ($q) use ($request) {
                return $q->whereHas('arrivalTicket', function ($sq) use ($request) {
                    $sq->where('location_id', $request->location_id);
                });
            })
            ->whereHas('arrivalTicket', function ($q) {
                $q->whereIn('location_id', getUserCurrentCompanyLocations());
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
            ->when($request->has('location_id') && $request->location_id != '', function ($q) use ($request) {
                return $q->where('location_id', $request->location_id);
            })
            ->whereIn('location_id', getUserCurrentCompanyLocations())
            //superadmin
            // ->when(auth()->user()->user_type != 'super-admin', function ($q) {
            //     return $q->where('location_id', auth()->user()->company_location_id);
            // })
            ->whereBetween('created_at', $dateRange)
            ->count();

        $secondWeighbridgePending = ArrivalTicket::where('company_id', $companyId)
            ->whereIn('document_approval_status', ['half_approved', 'fully_approved'])
            ->where('second_weighbridge_status', 'pending')
            //superadmin
            // ->when(auth()->user()->user_type != 'super-admin', function ($q) {
            //     return $q->where('location_id', auth()->user()->company_location_id);
            // })
            ->whereIn('location_id', getUserCurrentCompanyLocations())
            ->when($request->has('location_id') && $request->location_id != '', function ($q) use ($request) {
                return $q->where('location_id', $request->location_id);
            })
            ->whereBetween('created_at', $dateRange)
            ->count();

        $freightPending = ArrivalTicket::where('company_id', $companyId)
            ->where('second_weighbridge_status', 'completed')
            ->whereNull('freight_status')
            ->where('decision_making', 0)
            // ->when(auth()->user()->user_type != 'super-admin', function ($q) {
            //     return $q->where('location_id', auth()->user()->company_location_id);
            // })
            ->when($request->has('location_id') && $request->location_id != '', function ($q) use ($request) {
                return $q->where('location_id', $request->location_id);
            })
            ->whereIn('location_id', getUserCurrentCompanyLocations())
            ->whereBetween('created_at', $dateRange)
            ->count();

        $freightReady = ArrivalTicket::where('company_id', $companyId)
            ->where('second_weighbridge_status', 'completed')
            ->where('freight_status', 'pending')
            // ->when(auth()->user()->user_type != 'super-admin', function ($q) {
            //     return $q->where('location_id', auth()->user()->company_location_id);
            // })
            ->whereIn('location_id', getUserCurrentCompanyLocations())
            ->when($request->has('location_id') && $request->location_id != '', function ($q) use ($request) {
                return $q->where('location_id', $request->location_id);
            })
            ->whereBetween('created_at', $dateRange)
            ->count();


        $truckForBuiltReturn = ArrivalTicket::where('company_id', $companyId)
            ->where('first_qc_status', 'rejected')
            ->where('bilty_return_confirmation', 0)
            // ->when(auth()->user()->user_type != 'super-admin', function ($q) {
            //     return $q->where('location_id', auth()->user()->company_location_id);
            // })
            ->whereIn('location_id', getUserCurrentCompanyLocations())
            ->when($request->has('location_id') && $request->location_id != '', function ($q) use ($request) {
                return $q->where('location_id', $request->location_id);
            })
            ->whereBetween('created_at', $dateRange)
            ->count();

        $truckOut = ArrivalTicket::where('company_id', $companyId)
            ->where('arrival_slip_status', 'generated')
            // ->when(auth()->user()->user_type != 'super-admin', function ($q) {
            //     return $q->where('location_id', auth()->user()->company_location_id);
            // })
            ->whereIn('location_id', getUserCurrentCompanyLocations())
            ->when($request->has('location_id') && $request->location_id != '', function ($q) use ($request) {
                return $q->where('location_id', $request->location_id);
            })
            ->whereBetween('created_at', $dateRange)
            ->count();


        $truckAtHo = ArrivalTicket::where('company_id', $companyId)
            ->where('arrival_slip_status', 'generated')
            ->whereNotNull('arrival_purchase_order_id')
            ->where('is_ticket_verified', 0)
            // ->when(auth()->user()->user_type != 'super-admin', function ($q) {
            //     return $q->where('location_id', auth()->user()->company_location_id);
            // })
            ->whereIn('location_id', getUserCurrentCompanyLocations())
            ->when($request->has('location_id') && $request->location_id != '', function ($q) use ($request) {
                return $q->where('location_id', $request->location_id);
            })
            ->whereHas('unloadingLocation', function ($q) {
                return $q->whereIn('arrival_location_id', getUserCurrentCompanyArrivalLocations());
            })

            ->whereBetween('created_at', $dateRange)
            ->count();

        return [
            'initial_sampling_done_purchaserwise' => $initialSamplingDonePurchaserwise,
            'total_tickets' => $totalTickets,
            'inprocess_tickets' => $inprocesstickets,
            'old_pending_trucks' => $oldpendingtrucks,
            'total_tickets_kgs' => $totalTicketsKgs,
            'new_tickets' => $newTickets,
            'rejected_tickets' => $rejectedTickets,
            'half_rejected_tickets' => $halfRejectedTickets,
            'initial_sampling_requested' => $initialSamplingRequested,
            'initial_re_sampling_requested' => $initialRe_SamplingRequested,
            'initial_sampling_done' => $initialSamplingDone,
            'resampling_required' => $resamplingRequired,
            'location_transfer_pending' => $locationTransferPending,

            'completed_tickets' => $completedTickets,
            'first_weighbridge_pending' => $firstWeighbridgePending,
            'weighbridge_pending_locationwise' => $weighbridgePendingLocationwise,
            'inner_sampling_requested' => $innerSamplingRequested,
            'inner_re_sampling_requested' => $innerRe_SamplingRequested,
            'inner_sampling_pending_approval' => $innerSamplingPendingApproval,
            'decision_on_average_enabled' => $decisionOnAverageEnabled,
            'freight_ready' => $freightReady,
            'half_full_approve_pending' => $halfFullApprovePending,
            'second_weighbridge_pending' => $secondWeighbridgePending,
            'truck_for_built_return' => $truckForBuiltReturn,
            'truck_out' => $truckOut,
            'truck_at_ho' => $truckAtHo,
            'unloading_pending_locationwise' => $unloadingPendingLocationwise,
        ];
    }

}
