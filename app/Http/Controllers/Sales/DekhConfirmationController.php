<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\ApprovalsModule\ApprovalModuleRole;
use App\Models\Product;
use App\Models\Sales\DekhConfirmation;
use App\Models\Sales\PreSaleInspection;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;

class DekhConfirmationController extends Controller
{
    public function index()
    {
        abort_if(!canAccess('dekh-confirmation-list') && !auth()->user()?->can('dekh-confirmation-list'), 403);

        $items = Product::all();
        $locations = get_locations();

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

        $userRoleIds = array_filter(array_unique(array_merge(
            $user->roles->pluck('id')->toArray(),
            [$user->companies()->where('company_id', $user->current_company_id)->first()?->pivot->role_id]
        )));

        $isApprover = false;
        if (!$isSuperAdmin) {
            $isApprover = ApprovalModuleRole::where('module_id', 43)
                ->whereIn('role_id', $userRoleIds)
                ->exists();
        }

        // Only show Dekh records that are Stage-1 approved
        $inspections = PreSaleInspection::where('am_approval_status', 'approved')
            ->with([
                'location',
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
            // Permission-based visibility scope
            ->when(!$isSuperAdmin && !($canComplete && $canVehicle), function ($query) use ($canComplete, $canVehicle, $isApprover) {
                return $query->where(function ($q) use ($canComplete, $canVehicle, $isApprover) {
                    $hasCondition = false;

                    // 1. Mark as Complete user: sees pending completion records (and completed ones)
                    if ($canComplete) {
                        $q->where(function ($cq) {
                            $cq->where('is_completed', 0)
                               ->orWhereNull('is_completed')
                               ->orWhere('is_completed', 1);
                        });
                        $hasCondition = true;
                    }

                    // 2. Approver: sees completed records awaiting/processed for approval (does not see pending completion)
                    if ($isApprover) {
                        if ($hasCondition) {
                            $q->orWhere('is_completed', 1);
                        } else {
                            $q->where('is_completed', 1);
                            $hasCondition = true;
                        }
                    }

                    // 3. Vehicle user: ONLY sees records after Dekh Confirmation has been APPROVED
                    if ($canVehicle) {
                        $vehicleScope = function ($vq) {
                            $vq->where('confirmation_approval_status', 'approved')
                               ->orWhereHas('confirmation', function ($sub) {
                                   $sub->where('am_approval_status', 'approved');
                               });
                        };

                        if ($hasCondition) {
                            $q->orWhere($vehicleScope);
                        } else {
                            $q->where($vehicleScope);
                            $hasCondition = true;
                        }
                    }

                    // Fallback: If only dekh-confirmation-list is held, show only approved records
                    if (!$hasCondition) {
                        $q->where('confirmation_approval_status', 'approved');
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
            ->when($request->filled('location_id') && $request->location_id != 'all', function ($q) use ($request) {
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

        if (!$isSuperAdmin) {
            $userRoleIds = array_filter(array_unique(array_merge(
                $user->roles->pluck('id')->toArray(),
                [$user->companies()->where('company_id', $user->current_company_id)->first()?->pivot->role_id]
            )));
            $isApprover = ApprovalModuleRole::where('module_id', 43)
                ->whereIn('role_id', $userRoleIds)
                ->exists();

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

        $inspection = PreSaleInspection::findOrFail($id);

        if (strtolower($inspection->am_approval_status ?? '') !== 'approved') {
            return response()->json([
                'error' => 'Pre Sale Dekh must be approved before marking as complete.',
                'message' => 'Pre Sale Dekh must be approved before marking as complete.'
            ], 422);
        }

        if ($inspection->is_completed) {
            return response()->json([
                'error' => 'This Dekh has already been marked as complete.',
                'message' => 'This Dekh has already been marked as complete.'
            ], 422);
        }

        try {
            DB::beginTransaction();

            $confirmation = DekhConfirmation::updateOrCreate(
                ['pre_sale_inspection_id' => $inspection->id],
                [
                    'is_completed' => 1,
                    'completed_at' => now(),
                    'completed_by' => auth()->user()->id ?? 1,
                    'am_approval_status' => 'pending',
                    'am_change_made' => 1,
                ]
            );

            // Ensure approval rows exist for Dekh Confirmation module
            if ($confirmation->approvalRows()->where('module_id', 43)->count() == 0) {
                $confirmation->createApprovalRows();
            }

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

        $inspection = PreSaleInspection::with('confirmation')->findOrFail($id);
        $confirmation = $inspection->confirmation;

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
