<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Sales\DekhConfirmation;
use App\Models\Sales\PreSaleInspection;
use App\Models\Sales\PreSaleInspectionItem;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;

class DekhConfirmationController extends Controller
{
    /**
     * Fetch user's assigned factory (arrival) locations.
     * Uses getUserCurrentCompanyArrivalLocations(), $user->arrival_location_ids, and $user->arrival_location_id.
     */
    private function getUserFactoryLocations($user = null): array
    {
        $user = $user ?: auth()->user();
        if (!$user) {
            return [];
        }

        $locs = [];

        // 1. Current Company pivot arrival_locations (primary in Meskey for current company)
        if (function_exists('getUserCurrentCompanyArrivalLocations')) {
            try {
                $companyLocs = getUserCurrentCompanyArrivalLocations();
                if (!empty($companyLocs) && is_array($companyLocs)) {
                    $locs = array_merge($locs, $companyLocs);
                }
            } catch (\Throwable $e) {}
        }

        // 2. Direct arrival_location_ids on users table (keyed by company_id) if pivot was empty
        if (empty($locs) && !empty($user->arrival_location_ids)) {
            $arrIds = is_array($user->arrival_location_ids)
                ? $user->arrival_location_ids
                : (json_decode($user->arrival_location_ids, true) ?: []);

            $currentCompanyId = $user->current_company_id;
            if ($currentCompanyId && isset($arrIds[$currentCompanyId])) {
                $compLocs = is_array($arrIds[$currentCompanyId]) ? $arrIds[$currentCompanyId] : [$arrIds[$currentCompanyId]];
                $locs = array_merge($locs, $compLocs);
            }
        }

        // 3. Direct arrival_location_id on users table if still empty
        if (empty($locs) && !empty($user->arrival_location_id)) {
            $locs[] = (int)$user->arrival_location_id;
        }

        return array_values(array_unique(array_filter(array_map('intval', $locs))));
    }

    /**
     * Fetch user's assigned company locations (Port Qasim, Shikarpur, etc.).
     */
    private function getUserCompanyLocations($user = null): array
    {
        $user = $user ?: auth()->user();
        if (!$user) {
            return [];
        }
        return get_locations($user->id)->pluck('id')->map(fn($id) => (int)$id)->toArray();
    }

    public function index()
    {
        abort_if(!canAccess('dekh-confirmation-list') && !auth()->user()?->can('dekh-confirmation-list'), 403);

        $user = auth()->user();
        $isSuperAdmin = $user->user_type === 'super-admin' || $user->hasRole('Admin') || $user->hasRole('admin');

        $items = Product::all();
        $locations = $isSuperAdmin ? get_locations() : get_locations($user->id);

        return view('management.sales.dekh_confirmation.index', compact('items', 'locations'));
    }

    public function getList(Request $request)
    {
        abort_if(!canAccess('dekh-confirmation-list') && !auth()->user()?->can('dekh-confirmation-list'), 403);

        $perPage = $request->get('per_page', 25);
        $user = auth()->user();

        $isSuperAdmin = $user->user_type === 'super-admin' || $user->hasRole('Admin') || $user->hasRole('admin');
        $canComplete = canAccess('dekh-confirmation-complete') || $user->can('dekh-confirmation-complete');
        $canVehicle = canAccess('dekh-confirmation-vehicle') || $user->can('dekh-confirmation-vehicle');
        $isApprover = $isSuperAdmin || canAccess('dekh-confirmation-approval') || $user->can('dekh-confirmation-approval');

        // Location restrictions for non-super-admin
        $userCompanyLocationIds = [];
        $userFactoryLocationIds = [];
        if (!$isSuperAdmin) {
            $userCompanyLocationIds = $this->getUserCompanyLocations($user);
            $userFactoryLocationIds = $this->getUserFactoryLocations($user);
        }

        // Only show Dekh records that are Stage-1 approved
        $inspections = PreSaleInspection::where('am_approval_status', 'approved')
            ->with([
                'location',
                'factory',
                'items.item',
                'items.factory',
                'items.section',
                'creator',
                'confirmation.completedBy',
                'confirmation.vehicleAssignedBy',
                'confirmation.approvalRows',
                'salesInquiries',
                'salesOrders'
            ])
            // 1. MANDATORY COMPANY LOCATION FILTER (Port Qasim, Shikarpur, etc.):
            // Super Admin sees all. Non-super-admin ONLY sees their assigned company locations.
            // Shikarpur user NEVER sees Port Qasim! Port Qasim user NEVER sees Shikarpur!
            ->when(!$isSuperAdmin, function ($query) use ($userCompanyLocationIds) {
                if (!empty($userCompanyLocationIds)) {
                    return $query->whereIn('location_id', $userCompanyLocationIds);
                }
                return $query->whereRaw('1 = 0');
            })
            // 2. STAGE, ROLE & FACTORY LOCATION SCOPE:
            ->when(!$isSuperAdmin, function ($query) use ($canComplete, $canVehicle, $isApprover, $userFactoryLocationIds) {
                return $query->where(function ($q) use ($canComplete, $canVehicle, $isApprover, $userFactoryLocationIds) {
                    $hasCondition = false;

                    // A. Completion User:
                    // MUST ONLY see records matching their FACTORY location (e.g. A-45 user only sees A-45)!
                    // Uncompleted records or records of other factory locations (A-43, etc.) will NEVER show.
                    if ($canComplete) {
                        $q->where(function ($cq) use ($userFactoryLocationIds, $isApprover) {
                            if (!empty($userFactoryLocationIds)) {
                                $cq->where(function ($fq) use ($userFactoryLocationIds) {
                                    $fq->whereIn('arrival_location_id', $userFactoryLocationIds)
                                       ->orWhereHas('items', function ($iq) use ($userFactoryLocationIds) {
                                           $iq->whereIn('arrival_location_id', $userFactoryLocationIds);
                                       });
                                });
                                // If user is also an approver, completion stage applies to pending records only
                                if ($isApprover) {
                                    $cq->where(function($sub) {
                                        $sub->where('is_completed', 0)
                                            ->orWhereNull('is_completed');
                                    });
                                }
                            } else {
                                // No factory assigned to completion user => cannot see completion records
                                $cq->whereRaw('1 = 0');
                            }
                        });
                        $hasCondition = true;
                    }

                    // B. Approver (Stage 2 Approval):
                    // Once marked complete (is_completed = 1), approval goes to COMPANY LOCATION approver!
                    // Approver sees completed records for their company location (e.g. Port Qasim).
                    if ($isApprover) {
                        if ($hasCondition) {
                            $q->orWhere('is_completed', 1);
                        } else {
                            $q->where('is_completed', 1);
                            $hasCondition = true;
                        }
                    }

                    // C. Vehicle User: ONLY sees approved records
                    if ($canVehicle) {
                        $vehicleScope = function ($vq) use ($userFactoryLocationIds) {
                            $vq->where(function($sub) {
                                $sub->where('confirmation_approval_status', 'approved')
                                    ->orWhereHas('confirmation', function ($c) {
                                        $c->where('am_approval_status', 'approved');
                                    });
                            });
                            if (!empty($userFactoryLocationIds)) {
                                $vq->where(function ($fq) use ($userFactoryLocationIds) {
                                    $fq->whereIn('arrival_location_id', $userFactoryLocationIds)
                                       ->orWhereHas('items', function ($iq) use ($userFactoryLocationIds) {
                                           $iq->whereIn('arrival_location_id', $userFactoryLocationIds);
                                       });
                                });
                            }
                        };

                        if ($hasCondition) {
                            $q->orWhere($vehicleScope);
                        } else {
                            $q->where($vehicleScope);
                            $hasCondition = true;
                        }
                    }

                    // Fallback: If user only has dekh-confirmation-list without complete, approver or vehicle
                    if (!$hasCondition) {
                        $q->whereRaw('1 = 0');
                    }
                });
            })
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = '%' . strtolower($request->search) . '%';
                return $q->where(function ($sq) use ($search) {
                    $sq->whereRaw('LOWER(inspection_no) LIKE ?', [$search])
                        ->orWhereRaw('LOWER(party_name) LIKE ?', [$search])
                        ->orWhereRaw('LOWER(party_contact_no) LIKE ?', [$search])
                        ->orWhereRaw('LOWER(vehicle_no) LIKE ?', [$search])
                        ->orWhereRaw('LOWER(reference) LIKE ?', [$search])
                        ->orWhereHas('location', function ($lq) use ($search) {
                            $lq->whereRaw('LOWER(name) LIKE ?', [$search]);
                        })
                        ->orWhereHas('factory', function ($fq) use ($search) {
                            $fq->whereRaw('LOWER(name) LIKE ?', [$search]);
                        })
                        ->orWhereHas('items.item', function ($iq) use ($search) {
                            $iq->whereRaw('LOWER(name) LIKE ?', [$search]);
                        });
                });
            })
            ->when($request->filled('inspection_no'), function ($q) use ($request) {
                return $q->where('inspection_no', 'like', '%' . $request->inspection_no . '%');
            })
            ->when($request->filled('party_name'), function ($q) use ($request) {
                return $q->where('party_name', 'like', '%' . $request->party_name . '%');
            })
            ->when($request->filled('location_id') && $request->location_id != 'all', function ($q) use ($request, $isSuperAdmin, $userCompanyLocationIds) {
                if (!$isSuperAdmin && !empty($userCompanyLocationIds)) {
                    if (in_array((int)$request->location_id, $userCompanyLocationIds)) {
                        return $q->where('location_id', $request->location_id);
                    }
                    return $q->whereRaw('1 = 0');
                }
                return $q->where('location_id', $request->location_id);
            })
            ->when($request->filled('completion_status') && $request->completion_status != 'all', function ($q) use ($request) {
                if ($request->completion_status === 'completed') {
                    return $q->where('is_completed', 1);
                } elseif ($request->completion_status === 'pending') {
                    return $q->where(function($sq) {
                        $sq->where('is_completed', 0)->orWhereNull('is_completed');
                    });
                }
            })
            ->when($request->filled('confirmation_approval_status') && $request->confirmation_approval_status != 'all', function ($q) use ($request) {
                return $q->where('confirmation_approval_status', $request->confirmation_approval_status);
            })
            ->when($request->filled('date_range'), function ($q) use ($request) {
                $dates = explode(' - ', $request->date_range);
                if (count($dates) == 2) {
                    return $q->whereBetween('date', [trim($dates[0]), trim($dates[1])]);
                }
            })
            ->orderBy('id', 'desc')
            ->paginate($perPage);

        return view('management.sales.dekh_confirmation.getList', compact('inspections'));
    }

    public function view($id)
    {
        abort_if(!canAccess('dekh-confirmation-list') && !auth()->user()?->can('dekh-confirmation-list'), 403);

        $inspection = PreSaleInspection::with([
            'location',
            'factory',
            'items.item',
            'items.factory',
            'items.section',
            'creator',
            'confirmation.completedBy',
            'confirmation.vehicleAssignedBy',
            'confirmation.approvalRows',
            'confirmation.approvalLogs.user',
            'confirmation.approvalLogs.role',
            'salesInquiries',
            'salesOrders'
        ])->findOrFail($id);

        $user = auth()->user();
        $isSuperAdmin = $user->user_type === 'super-admin' || $user->hasRole('Admin') || $user->hasRole('admin');
        $canComplete = canAccess('dekh-confirmation-complete') || $user->can('dekh-confirmation-complete');
        $canVehicle = canAccess('dekh-confirmation-vehicle') || $user->can('dekh-confirmation-vehicle');
        $isApprover = $isSuperAdmin || canAccess('dekh-confirmation-approval') || $user->can('dekh-confirmation-approval');

        if (!$isSuperAdmin) {
            // STRICT Company Location validation:
            // User can ONLY view Dekh if its location_id matches their assigned company location!
            $userCompanyLocationIds = $this->getUserCompanyLocations($user);
            if (empty($userCompanyLocationIds) || !in_array((int)$inspection->location_id, $userCompanyLocationIds)) {
                abort(403, 'You do not have access to Dekh records for this company location.');
            }

            // Factory location check for completion stage (A-45 user cannot open A-43 record)
            $isCompleted = (bool)$inspection->is_completed;
            if (!$isApprover || !$isCompleted) {
                $userFactoryLocationIds = $this->getUserFactoryLocations($user);
                if (!empty($userFactoryLocationIds)) {
                    $inspectionFactoryId = (int)($inspection->arrival_location_id ?: $inspection->items->first()?->arrival_location_id);
                    $itemFactoryIds = $inspection->items->pluck('arrival_location_id')->map(fn($id) => (int)$id)->toArray();
                    $allInspectionFactories = array_unique(array_filter(array_merge([$inspectionFactoryId], $itemFactoryIds)));

                    if (empty(array_intersect($allInspectionFactories, $userFactoryLocationIds))) {
                        abort(403, 'You do not have access to Dekh records for this factory location.');
                    }
                } else {
                    abort(403, 'You do not have any assigned factory location.');
                }
            }

            $isApproved = strtolower($inspection->confirmation_approval_status ?? '') === 'approved'
                || strtolower($inspection->confirmation?->am_approval_status ?? '') === 'approved';

            // Vehicle user ONLY cannot view before approval
            if ($canVehicle && !$canComplete && !$isApprover && !$isApproved) {
                abort(403, 'Dekh confirmation is not yet approved and cannot be viewed.');
            }

            // Approver ONLY cannot view before completion
            if ($isApprover && !$canComplete && !$canVehicle && !$inspection->is_completed) {
                abort(403, 'Dekh confirmation is awaiting completion and cannot be viewed.');
            }
        }

        return view('management.sales.dekh_confirmation.view', compact('inspection'));
    }

    public function markComplete(Request $request, $id)
    {
        abort_if(!canAccess('dekh-confirmation-complete') && !auth()->user()?->can('dekh-confirmation-complete'), 403);

        $inspection = PreSaleInspection::with('items')->findOrFail($id);

        $user = auth()->user();
        $isSuperAdmin = $user->user_type === 'super-admin' || $user->hasRole('Admin') || $user->hasRole('admin');
        if (!$isSuperAdmin) {
            // STRICT Company location check
            $userCompanyLocationIds = $this->getUserCompanyLocations($user);
            if (empty($userCompanyLocationIds) || !in_array((int)$inspection->location_id, $userCompanyLocationIds)) {
                return response()->json([
                    'error' => 'You do not have permission to mark completion for this company location.',
                    'message' => 'You do not have permission to mark completion for this company location.'
                ], 403);
            }

            // Factory location check
            $userFactoryLocationIds = $this->getUserFactoryLocations($user);
            if (!empty($userFactoryLocationIds)) {
                $inspectionFactoryId = (int)($inspection->arrival_location_id ?: $inspection->items->first()?->arrival_location_id);
                $itemFactoryIds = $inspection->items->pluck('arrival_location_id')->map(fn($id) => (int)$id)->toArray();
                $allInspectionFactories = array_unique(array_filter(array_merge([$inspectionFactoryId], $itemFactoryIds)));

                if (empty(array_intersect($allInspectionFactories, $userFactoryLocationIds))) {
                    return response()->json([
                        'error' => 'You do not have permission to mark completion for this factory location.',
                        'message' => 'You do not have permission to mark completion for this factory location.'
                    ], 403);
                }
            } else {
                return response()->json([
                    'error' => 'You do not have any assigned factory location.',
                    'message' => 'You do not have any assigned factory location.'
                ], 403);
            }
        }

        if (strtolower($inspection->am_approval_status ?? '') !== 'approved') {
            return response()->json([
                'error' => 'Pre Sale Dekh must be approved before marking as complete.',
                'message' => 'Pre Sale Dekh must be approved before marking as complete.'
            ], 422);
        }

        $isReverted = strtolower($inspection->confirmation_approval_status ?? '') === 'reverted';

        if ($inspection->is_completed && !$isReverted) {
            return response()->json([
                'error' => 'This Dekh has already been marked as complete.',
                'message' => 'This Dekh has already been marked as complete.'
            ], 422);
        }

        // Validate items selection: at least 1 item must be selected
        $confirmedItemIds = $request->input('confirmed_item_ids', []);
        if (is_string($confirmedItemIds)) {
            $confirmedItemIds = json_decode($confirmedItemIds, true) ?: [];
        }

        if (empty($confirmedItemIds) || !is_array($confirmedItemIds)) {
            return response()->json([
                'error' => 'Please select at least one item to confirm.',
                'message' => 'Please select at least one item to confirm.'
            ], 422);
        }

        // Verify that submitted item IDs belong to this inspection
        $validInspectionItemIds = $inspection->items->pluck('id')->toArray();
        $validConfirmedIds = array_values(array_intersect(array_map('intval', $confirmedItemIds), $validInspectionItemIds));

        if (empty($validConfirmedIds)) {
            return response()->json([
                'error' => 'Selected items are invalid for this Dekh inspection.',
                'message' => 'Selected items are invalid for this Dekh inspection.'
            ], 422);
        }

        $remarks = trim($request->input('remarks', ''));

        try {
            DB::beginTransaction();

            // Update items is_confirmed status
            PreSaleInspectionItem::where('pre_sale_inspection_id', $inspection->id)
                ->whereIn('id', $validConfirmedIds)
                ->update(['is_confirmed' => 1]);

            PreSaleInspectionItem::where('pre_sale_inspection_id', $inspection->id)
                ->whereNotIn('id', $validConfirmedIds)
                ->update(['is_confirmed' => 0]);

            $confirmation = DekhConfirmation::updateOrCreate(
                ['pre_sale_inspection_id' => $inspection->id],
                [
                    'is_completed' => 1,
                    'completed_at' => now(),
                    'completed_by' => auth()->user()->id ?? 1,
                    'am_approval_status' => 'pending',
                    'am_change_made' => 1,
                    'remarks' => $remarks ?: null,
                ]
            );

            // Ensure approval rows exist for Dekh Confirmation
            $confirmation->createApprovalRows();

            $inspection->update([
                'is_completed' => 1,
                'completed_at' => now(),
                'completed_by' => auth()->user()->id ?? 1,
                'confirmation_approval_status' => 'pending',
                'confirmation_am_change_made' => 1,
            ]);

            DB::commit();

            return response()->json([
                'success' => 'Dekh has been marked as complete successfully and submitted for confirmation approval.'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function saveVehicle(Request $request, $id)
    {
        abort_if(!canAccess('dekh-confirmation-vehicle') && !auth()->user()?->can('dekh-confirmation-vehicle'), 403);

        $request->validate([
            'vehicle_no' => 'required|string|max:100',
        ]);

        $inspection = PreSaleInspection::with('confirmation', 'items')->findOrFail($id);
        $confirmation = $inspection->confirmation;

        $user = auth()->user();
        $isSuperAdmin = $user->user_type === 'super-admin' || $user->hasRole('Admin') || $user->hasRole('admin');
        if (!$isSuperAdmin) {
            // STRICT Company location check
            $userCompanyLocationIds = $this->getUserCompanyLocations($user);
            if (empty($userCompanyLocationIds) || !in_array((int)$inspection->location_id, $userCompanyLocationIds)) {
                return response()->json([
                    'error' => 'You do not have permission to assign vehicle for this company location.',
                    'message' => 'You do not have permission to assign vehicle for this company location.'
                ], 403);
            }

            // Factory location check
            $userFactoryLocationIds = $this->getUserFactoryLocations($user);
            if (!empty($userFactoryLocationIds)) {
                $inspectionFactoryId = (int)($inspection->arrival_location_id ?: $inspection->items->first()?->arrival_location_id);
                $itemFactoryIds = $inspection->items->pluck('arrival_location_id')->map(fn($id) => (int)$id)->toArray();
                $allInspectionFactories = array_unique(array_filter(array_merge([$inspectionFactoryId], $itemFactoryIds)));

                if (empty(array_intersect($allInspectionFactories, $userFactoryLocationIds))) {
                    return response()->json([
                        'error' => 'You do not have permission to assign vehicle for this factory location.',
                        'message' => 'You do not have permission to assign vehicle for this factory location.'
                    ], 403);
                }
            } else {
                return response()->json([
                    'error' => 'You do not have any assigned factory location.',
                    'message' => 'You do not have any assigned factory location.'
                ], 403);
            }
        }

        if (!$confirmation || strtolower($confirmation->am_approval_status ?? '') !== 'approved') {
            return response()->json([
                'error' => 'Dekh Confirmation must be approved before assigning vehicle number.',
                'message' => 'Dekh Confirmation must be approved before assigning vehicle number.'
            ], 422);
        }

        try {
            DB::beginTransaction();

            $vehicleNo = trim($request->vehicle_no);

            $confirmation->update([
                'vehicle_no' => $vehicleNo,
                'vehicle_assigned_at' => now(),
                'vehicle_assigned_by' => auth()->user()->id ?? 1,
            ]);

            $inspection->update([
                'vehicle_no' => $vehicleNo,
                'vehicle_assigned_at' => now(),
                'vehicle_assigned_by' => auth()->user()->id ?? 1,
            ]);

            DB::commit();

            return response()->json([
                'success' => 'Vehicle number has been saved successfully.'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
