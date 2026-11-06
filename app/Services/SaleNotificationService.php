<?php

namespace App\Services;

use App\Models\Sales\PaymentIntimation;
use App\Models\Sales\SalesOrder;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class SaleNotificationService
{
    /**
     * Dispatch notification for newly created Sale Order to authorized family members at the same location.
     */
    public static function sendSaleOrderCreatedNotification(SalesOrder $salesOrder): void
    {
        self::notifyStage1Approvers($salesOrder, 'created Sale Order:');
    }

    /**
     * A reverted Sale Order was edited and is back at Stage 1.
     */
    public static function sendSaleOrderResubmittedNotification(SalesOrder $salesOrder): void
    {
        self::notifyStage1Approvers($salesOrder, 'edited and resubmitted Sale Order:', auth()->user());
    }

    /**
     * A delivery date change on an approved Sale Order sent it back to Stage 1.
     */
    public static function sendSaleOrderDateAmendmentNotification(SalesOrder $salesOrder, ?string $newDate = null): void
    {
        $change = $newDate ? 'changed delivery date to ' . e($newDate) . ' on Sale Order:' : 'changed delivery date on Sale Order:';

        self::notifyStage1Approvers($salesOrder, $change, auth()->user());
    }

    /**
     * Tell whoever created the Sale Order what happened to it: approved, reverted or rejected.
     */
    public static function sendSaleOrderDecisionNotification(SalesOrder $salesOrder, string $decision, ?string $comments = null, bool $isDateAmendment = false): void
    {
        try {
            $creator = $salesOrder->created_by ? User::find($salesOrder->created_by) : null;
            $actor = auth()->user();
            if (!$creator || $creator->status !== 'active' || (int) $creator->id === (int) $actor?->id) {
                return;
            }

            $what = $isDateAmendment ? 'the delivery date change on Sale Order:' : 'Sale Order:';
            $after = match (true) {
                $decision === 'approved' && !$isDateAmendment => ' (Final Approved)',
                $decision === 'reverted' && !$isDateAmendment => ' (Please edit and resubmit)',
                $isDateAmendment && $decision !== 'approved' => ' (Original delivery date retained)',
                default => '',
            };
            $reason = $comments ? ' Remarks: ' . e(\Illuminate\Support\Str::limit($comments, 200)) : '';

            $message = self::soMessage($salesOrder, $actor?->name ?? 'User', "{$decision} {$what}", $after . $reason);

            NotificationService::send(collect([$creator]), $message, 'Sale Order ' . ucfirst($decision), self::soUrl($salesOrder), $actor?->id);
        } catch (\Throwable $e) {
            Log::error('Sale Order Decision Notification Error: ' . $e->getMessage());
        }
    }

    /**
     * Parent, and siblings / children who can approve at the Sale Order's factory.
     * The parent gets it for every factory, since all the child's Sale Orders are on his list.
     */
    private static function notifyStage1Approvers(SalesOrder $salesOrder, string $action, ?User $actor = null): void
    {
        try {
            $creatorId = $salesOrder->created_by ?: auth()->id();
            $creator = $creatorId ? User::find($creatorId) : auth()->user();
            if (!$creator) {
                return;
            }
            $actor = $actor ?: $creator;

            // 1. Collect potential recipient family members (Parent, Siblings, Children)
            $candidateUserIds = collect();

            // (a) Parent of creator
            if (!empty($creator->parent_user_id)) {
                $candidateUserIds->push($creator->parent_user_id);

                // (b) Siblings of creator (other children under the same parent)
                $siblingIds = User::where('parent_user_id', $creator->parent_user_id)
                    ->where('id', '!=', $creator->id)
                    ->pluck('id');
                $candidateUserIds = $candidateUserIds->merge($siblingIds);
            } else {
                // A creator with no parent approves his own Sale Order, so he needs to hear when someone else sends it back
                $candidateUserIds->push($creator->id);
            }

            // (c) If Sale Order has a specific Sell By / Parent user different from creator
            if (!empty($salesOrder->parent_user_id) && $salesOrder->parent_user_id != $creator->id) {
                $candidateUserIds->push($salesOrder->parent_user_id);
            }

            // (d) Children of creator
            $childIds = User::where('parent_user_id', $creator->id)->pluck('id');
            $candidateUserIds = $candidateUserIds->merge($childIds);

            // Clean candidate IDs (unique, not empty, nobody is told about his own action)
            $candidateUserIds = $candidateUserIds
                ->filter(fn($id) => !empty($id) && (int)$id !== (int)$actor->id)
                ->unique()
                ->values()
                ->all();

            if (empty($candidateUserIds)) {
                return;
            }

            // 2. Resolve Sale Order's assigned factory / arrival locations
            $salesOrder->loadMissing(['factories', 'customer', 'locations']);
            $soFactoryIds = array_values(array_unique(array_filter(array_merge(
                [$salesOrder->arrival_location_id],
                $salesOrder->factories ? $salesOrder->factories->pluck('arrival_location_id')->toArray() : []
            ))));

            // 3. Fetch candidate users and filter strictly by:
            //    - Permission: sale-order-approval (or super-admin)
            //    - Location: must be assigned to the Sale Order's factory/location, except the parent
            $candidateUsers = User::whereIn('id', $candidateUserIds)
                ->where('status', 'active')
                ->get();

            $recipients = $candidateUsers->filter(function ($user) use ($creator, $salesOrder, $soFactoryIds) {
                // Rule 1: Approval authority check (Matching View Page logic)
                // - Parent user is naturally authorized to approve by hierarchy
                // - Siblings / Children require 'sale-order-approval' permission (or super-admin)
                $isParent = (!empty($creator->parent_user_id) && (int)$user->id === (int)$creator->parent_user_id)
                    || (!empty($salesOrder->parent_user_id) && (int)$user->id === (int)$salesOrder->parent_user_id)
                    || (empty($creator->parent_user_id) && (int)$user->id === (int)$creator->id);

                if ($isParent) {
                    return true;
                }

                if ($user->user_type !== 'super-admin' && !$user->can('sale-order-approval')) {
                    return false;
                }

                // Rule 2: Must be assigned to the Sale Order's factory location
                if (!empty($soFactoryIds)) {
                    $userFactoryIds = SalesOrder::getUserFactoryLocationIds($user);
                    return !empty(array_intersect($soFactoryIds, $userFactoryIds));
                }

                return true;
            });

            if ($recipients->isEmpty()) {
                return;
            }

            // 4. Build notification payload
            $message = self::soMessage($salesOrder, $actor->name ?? 'System', $action, ' (Pending Approval)');

            NotificationService::send($recipients, $message, 'Sale Order Approval Required', self::soUrl($salesOrder), $actor->id);
        } catch (\Throwable $e) {
            Log::error('Sale Order Notification Error: ' . $e->getMessage());
        }
    }

    private static function soUrl(SalesOrder $salesOrder): string
    {
        return route('sales.sale-order.index', ['view_id' => $salesOrder->id]);
    }

    private static function soMessage(SalesOrder $salesOrder, string $userName, string $action, string $after): string
    {
        $soNo = $salesOrder->reference_no ?? ('SO#' . $salesOrder->id);
        $customerName = $salesOrder->customer?->name;
        $customerText = $customerName ? " for: <strong>{$customerName}</strong>" : "";

        return '<a href="' . self::soUrl($salesOrder) . '" style="color: inherit; text-decoration: none;"><strong>' . $userName . '</strong> ' . $action . ' <strong>' . $soNo . '</strong>' . $customerText . $after . '</a>';
    }

    /**
     * Dispatch notification when Sale Order is Stage 1 approved and moves to Head Office Pending.
     */
    public static function sendSaleOrderHeadOfficeNotification(SalesOrder $salesOrder): void
    {
        try {
            $approver = auth()->user();
            $approverId = $approver?->id ?? 1;

            // 1. Fetch users with 'headoffice-saleorder-approval' permission
            $usersWithPermission = User::permission('headoffice-saleorder-approval')
                ->where('status', 'active')
                ->get();

            // Exclude the approver himself
            $candidates = $usersWithPermission->filter(fn($u) => (int)$u->id !== (int)$approverId);

            if ($candidates->isEmpty()) {
                return;
            }

            // 2. Resolve Sale Order's assigned factories / arrival locations
            $salesOrder->loadMissing(['factories', 'customer']);
            $soFactoryIds = array_values(array_unique(array_filter(array_merge(
                [$salesOrder->arrival_location_id],
                $salesOrder->factories ? $salesOrder->factories->pluck('arrival_location_id')->toArray() : []
            ))));

            // 3. Filter candidates strictly by location match
            $recipients = $candidates->filter(function ($user) use ($soFactoryIds) {
                if (!empty($soFactoryIds)) {
                    $userFactoryIds = SalesOrder::getUserFactoryLocationIds($user);
                    return !empty(array_intersect($soFactoryIds, $userFactoryIds));
                }

                return true;
            });

            if ($recipients->isEmpty()) {
                return;
            }

            // 4. Build notification payload
            $userName = $approver?->name ?? 'User';
            $soNo = $salesOrder->reference_no ?? ('SO#' . $salesOrder->id);
            $customerName = $salesOrder->customer?->name;
            $customerText = $customerName ? " for: <strong>{$customerName}</strong>" : "";
            $targetUrl = route('sales.sale-order.index', ['view_id' => $salesOrder->id]);

            $message = '<a href="' . $targetUrl . '" style="color: inherit; text-decoration: none;"><strong>' . $userName . '</strong> approved Stage 1 for Sale Order: <strong>' . $soNo . '</strong>' . $customerText . ' (Pending Head Office Approval)</a>';

            NotificationService::send($recipients, $message, 'Sale Order Head Office Approval', $targetUrl, $approverId);
        } catch (\Throwable $e) {
            Log::error('Sale Order Head Office Notification Error: ' . $e->getMessage());
        }
    }

    /**
     * Dispatch notification for Payment Intimation.
     */
    public static function sendPaymentIntimationNotification(PaymentIntimation $paymentIntimation): void
    {
        try {
            $users = User::permission('payment-intimation')->get();
            $superAdmins = User::where('user_type', 'super-admin')->get();
            $recipients = $users->merge($superAdmins)->unique('id');

            if ($recipients->isEmpty()) {
                return;
            }

            $userName = auth()->user()?->name ?? 'System';
            $paymentIntimation->loadMissing(['sale_order', 'customer']);
            $soNo = $paymentIntimation->sale_order->reference_no ?? 'N/A';
            $customerName = $paymentIntimation->customer->name ?? 'N/A';
            $targetUrl = route('sales.payment-intimation.index');

            $message = '<a href="' . $targetUrl . '" style="color: inherit; text-decoration: none;"><strong>' . $userName . '</strong> created a Payment Intimation for: <strong>' . $customerName . '</strong> • SO No: <strong>' . $soNo . '</strong></a>';

            NotificationService::send($recipients, $message, 'Payment Intimation', $targetUrl, auth()->user()?->id);
        } catch (\Throwable $e) {
            Log::error('Payment Intimation Notification Error: ' . $e->getMessage());
        }
    }
}

// Optional alias for plural naming if referenced
if (!class_exists('App\Services\SaleNotificationServices', false)) {
    class_alias(SaleNotificationService::class, 'App\Services\SaleNotificationServices');
}
