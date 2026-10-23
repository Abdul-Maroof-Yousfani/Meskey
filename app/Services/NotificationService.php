<?php

namespace App\Services;

use App\Models\Master\Account\Account;
use App\Models\PaymentVoucher;
use App\Models\Sales\PaymentIntimation;
use App\Models\Sales\SalesOrder;
use App\Models\User;
use App\Notifications\NewNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Pusher\Pusher;

class NotificationService
{
    /**
     * Send notification to specified users via Database and Pusher.
     *
     * @param mixed $users
     * @param string $message
     * @param string $title
     * @param string|null $url
     * @param int|null $senderId
     * @param mixed $users
     * @param string $message
     * @param string $title
     * @param string|null $url
     * @param int|null $senderId
     * @param mixed|null $company
     * @return bool
     */
    public static function send($users, string $message, string $title = 'Meskey ERP', ?string $url = null, mixed $senderId = null, mixed $company = null): bool
    {
        if (empty($users) || (is_countable($users) && count($users) === 0)) {
            return false;
        }

        $senderId = (!empty($senderId) && is_numeric($senderId)) ? (int)$senderId : (auth()->user()?->id ? (int)auth()->user()->id : 1);
        $senderName = auth()->user()?->name ?? 'System';

        // 1. Save to Database for notification bell / dropdown
        try {
            Notification::send($users, new NewNotification($message, $senderId, ['database']));
        } catch (\Throwable $e) {
            Log::error('NotificationService DB Error: ' . $e->getMessage());
        }

        // 2. Real-time broadcast via Pusher (Uses Company Settings or .env)
        self::broadcastToPusher($users, $message, $title, $url, $senderId, $senderName, $company);

        return true;
    }

    /**
     * Broadcast notification payload via Pusher using company settings.
     */
    protected static function broadcastToPusher($users, string $message, string $title, ?string $url, int $senderId, string $senderName, mixed $company = null): void
    {
        // 1. Resolve Company
        $companyModel = null;
        if ($company instanceof \App\Models\Acl\Company) {
            $companyModel = $company;
        } elseif (!empty($company) && is_numeric($company)) {
            $companyModel = \App\Models\Acl\Company::find($company);
        } else {
            $companyModel = getCurrentCompany();
        }

        // If company is still not resolved, attempt to extract from recipient user
        if (!$companyModel) {
            $firstRecipient = $users instanceof Collection ? $users->first() : (is_array($users) ? reset($users) : $users);
            if ($firstRecipient instanceof User && $firstRecipient->current_company_id) {
                $companyModel = \App\Models\Acl\Company::find($firstRecipient->current_company_id);
            }
        }

        // 2. Fetch dynamic Pusher configuration
        $pusherConfig = getPusherConfig($companyModel);

        if (!$pusherConfig['enabled'] || empty($pusherConfig['key']) || empty($pusherConfig['secret']) || empty($pusherConfig['app_id']) || !class_exists(Pusher::class)) {
            return;
        }

        try {
            $options = [
                'cluster' => $pusherConfig['cluster'],
                'useTLS'  => true,
            ];

            $pusher = new Pusher(
                $pusherConfig['key'],
                $pusherConfig['secret'],
                $pusherConfig['app_id'],
                $options
            );

            $userIds = [];
            if ($users instanceof Collection) {
                $userIds = $users->pluck('id')->filter()->unique()->values()->all();
            } elseif (is_array($users)) {
                foreach ($users as $u) {
                    $userIds[] = is_object($u) ? ($u->id ?? null) : $u;
                }
                $userIds = array_values(array_unique(array_filter($userIds)));
            } elseif (is_object($users)) {
                $userIds = [$users->id];
            }
            $userIds = array_values(array_map('intval', array_filter($userIds)));

            $payload = [
                'id'          => (string) \Illuminate\Support\Str::uuid(),
                'title'       => $title,
                'message'     => $message,
                'clean_text'  => strip_tags($message),
                'url'         => $url,
                'sender_id'   => $senderId,
                'sender_name' => $senderName,
                'user_ids'    => $userIds,
                'company_id'  => $companyModel?->id,
                'created_at'  => now()->toIso8601String(),
            ];

            $pusher->trigger('notify-channel', 'notify-event', $payload);
        } catch (\Throwable $e) {
            Log::error('NotificationService Pusher Error: ' . $e->getMessage());
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

            self::send($recipients, $message, 'Payment Intimation', $targetUrl, auth()->user()?->id);
        } catch (\Throwable $e) {
            Log::error('Payment Intimation Notification Error: ' . $e->getMessage());
        }
    }

    /**
     * Dispatch notification for Payment Voucher.
     * Follows sequential approval-module role order if configured, otherwise falls back to permission broadcast.
     */
    public static function sendPaymentVoucherNotification(PaymentVoucher $paymentVoucher): void
    {
        try {
            // 1. Attempt to notify the first pending approval role in sequence
            if (self::notifyPendingApprovalRoleForVoucher($paymentVoucher, 'created')) {
                return;
            }

            // If approval module is configured and active, do NOT send to super-admin or arbitrary users.
            $module = $paymentVoucher->getApprovalModule();
            if ($module && $module->is_active && $module->roles()->count() > 0) {
                return;
            }

            // 2. Fallback ONLY if no approval module is configured:
            // Broadcast strictly to users with 'payment-voucher' permission
            $users = User::permission('payment-voucher')->get();
            $superAdmins = User::where('user_type', 'super-admin')->get();
            $recipients = $users->merge($superAdmins)->unique('id');

            if ($recipients->isEmpty()) {
                return;
            }

            $userName = auth()->user()?->name ?? 'System';
            $voucherNo = $paymentVoucher->unique_no ?? ('PV#' . $paymentVoucher->id);
            $amount = (float) ($paymentVoucher->total_amount ?? 0);

            // Resolve party name
            $partyName = self::resolveVoucherPartyName($paymentVoucher);
            $partyText = $partyName ? " for: <strong>{$partyName}</strong>" : "";
            $amountText = $amount > 0 ? " • Amount: <strong>Rs. " . number_format($amount, 2) . "</strong>" : "";
            $targetUrl = route('payment-voucher.index', ['open_approval' => $paymentVoucher->id, 'search' => $voucherNo]);

            $message = '<a href="' . $targetUrl . '" style="color: inherit; text-decoration: none;"><strong>' . $userName . '</strong> created a Payment Voucher: <strong>' . $voucherNo . '</strong>' . $partyText . $amountText . '</a>';

            self::send($recipients, $message, 'Payment Voucher', $targetUrl, auth()->user()?->id);
        } catch (\Throwable $e) {
            Log::error('Payment Voucher Notification Error: ' . $e->getMessage());
        }
    }

    /**
     * Dispatch notification when an approval step is performed on a Payment Voucher.
     * If fully approved, notifies the creator. Otherwise forwards to the next pending role.
     */
    public static function sendPaymentVoucherApprovalStepNotification(PaymentVoucher $paymentVoucher): void
    {
        try {
            $paymentVoucher->refresh();

            $isFullyApproved = in_array(strtolower((string)$paymentVoucher->getApprovalStatus()), ['approved'])
                || strtolower((string)$paymentVoucher->am_approval_status) === 'approved';

            if ($isFullyApproved) {
                self::notifyVoucherFullyApproved($paymentVoucher);
                return;
            }

            // Forward to the next pending approval role in sequence
            self::notifyPendingApprovalRoleForVoucher($paymentVoucher, 'step_approved');
        } catch (\Throwable $e) {
            Log::error('sendPaymentVoucherApprovalStepNotification Error: ' . $e->getMessage());
        }
    }

    /**
     * Notify voucher creator that the Payment Voucher has been fully approved.
     */
    public static function notifyVoucherFullyApproved(PaymentVoucher $paymentVoucher): void
    {
        try {
            $creator = self::resolveVoucherCreator($paymentVoucher);
            if (!$creator) {
                return;
            }

            $approverName = auth()->user()?->name ?? 'Approver';
            $voucherNo = $paymentVoucher->unique_no ?? ('PV#' . $paymentVoucher->id);
            $amount = (float) ($paymentVoucher->total_amount ?? 0);
            $partyName = self::resolveVoucherPartyName($paymentVoucher);
            $partyText = $partyName ? " for: <strong>{$partyName}</strong>" : "";
            $amountText = $amount > 0 ? " • Amount: <strong>Rs. " . number_format($amount, 2) . "</strong>" : "";
            $targetUrl = route('payment-voucher.index', ['search' => $voucherNo]);

            $message = '<a href="' . $targetUrl . '" style="color: inherit; text-decoration: none;">Payment Voucher: <strong>' . $voucherNo . '</strong>' . $partyText . $amountText . ' has been <strong>fully approved</strong> by <strong>' . $approverName . '</strong>.</a>';

            self::send(collect([$creator]), $message, 'Payment Voucher Approved', $targetUrl, auth()->user()?->id);
        } catch (\Throwable $e) {
            Log::error('notifyVoucherFullyApproved Error: ' . $e->getMessage());
        }
    }

    /**
     * Dispatch notification when a Payment Voucher is rejected or reverted.
     */
    public static function sendPaymentVoucherApprovalStatusNotification(PaymentVoucher $paymentVoucher, string $action, ?string $comments = null): void
    {
        try {
            $creator = self::resolveVoucherCreator($paymentVoucher);
            if (!$creator) {
                return;
            }

            $actorName = auth()->user()?->name ?? 'System';
            $voucherNo = $paymentVoucher->unique_no ?? ('PV#' . $paymentVoucher->id);
            $targetUrl = route('payment-voucher.index', ['search' => $voucherNo]);
            $commentText = !empty($comments) ? " Reason: <em>" . htmlspecialchars($comments, ENT_QUOTES, 'UTF-8') . "</em>" : "";
            $actionTitle = ucfirst($action);

            $message = '<a href="' . $targetUrl . '" style="color: inherit; text-decoration: none;">Payment Voucher: <strong>' . $voucherNo . '</strong> was <strong>' . $action . '</strong> by <strong>' . $actorName . '</strong>.' . $commentText . '</a>';

            self::send(collect([$creator]), $message, "Payment Voucher {$actionTitle}", $targetUrl, auth()->user()?->id);
        } catch (\Throwable $e) {
            Log::error('sendPaymentVoucherApprovalStatusNotification Error: ' . $e->getMessage());
        }
    }

    /**
     * Notify the current active pending approval role's users for a Payment Voucher.
     */
    public static function notifyPendingApprovalRoleForVoucher(PaymentVoucher $paymentVoucher, string $context = 'created'): bool
    {
        try {
            $module = $paymentVoucher->getApprovalModule();
            if (!$module || !$module->is_active || $module->roles()->count() === 0) {
                return false;
            }

            $currentCycle = $paymentVoucher->getCurrentApprovalCycle();
            $moduleRoles = $module->roles()->orderBy('approval_order')->get();

            // Ensure approval rows exist for current cycle
            $hasRows = $paymentVoucher->approvalRows()
                ->where('module_id', $module->id)
                ->where('approval_cycle', $currentCycle)
                ->exists();

            if (!$hasRows) {
                $paymentVoucher->createApprovalRows();
            }

            // Find lowest order role whose approval_row is still pending
            $pendingRole = null;
            foreach ($moduleRoles as $mRole) {
                $row = $paymentVoucher->approvalRows()
                    ->where('module_id', $module->id)
                    ->where('approval_cycle', $currentCycle)
                    ->where('role_id', $mRole->role_id)
                    ->first();

                if ($row && $row->current_count < $row->required_count && !in_array($row->status, ['rejected', 'reverted'])) {
                    $pendingRole = $mRole;
                    break;
                }
            }

            if (!$pendingRole) {
                return false;
            }

            // Find all active users belonging to this role
            $recipients = self::getUsersForRole($pendingRole->role_id);
            if ($recipients->isEmpty()) {
                Log::warning("NotificationService: No active users found for pending approval role ID {$pendingRole->role_id} on Payment Voucher #{$paymentVoucher->id}");
                return false;
            }

            $actorName = auth()->user()?->name ?? 'System';
            $voucherNo = $paymentVoucher->unique_no ?? ('PV#' . $paymentVoucher->id);
            $amount = (float) ($paymentVoucher->total_amount ?? 0);
            $partyName = self::resolveVoucherPartyName($paymentVoucher);
            $partyText = $partyName ? " for: <strong>{$partyName}</strong>" : "";
            $amountText = $amount > 0 ? " • Amount: <strong>Rs. " . number_format($amount, 2) . "</strong>" : "";
            $targetUrl = route('payment-voucher.index', ['open_approval' => $paymentVoucher->id, 'search' => $voucherNo]);

            $actionText = ($context === 'step_approved')
                ? "<strong>{$actorName}</strong> approved Payment Voucher: <strong>{$voucherNo}</strong>{$partyText}{$amountText} (Pending your approval)"
                : "<strong>{$actorName}</strong> created a Payment Voucher: <strong>{$voucherNo}</strong>{$partyText}{$amountText} (Pending your approval)";

            self::send($recipients, '<a href="' . $targetUrl . '" style="color: inherit; text-decoration: none;">' . $actionText . '</a>', 'Payment Voucher Approval', $targetUrl, auth()->user()?->id);

            return true;
        } catch (\Throwable $e) {
            Log::error('notifyPendingApprovalRoleForVoucher Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Resolve creator user of a Payment Voucher from audit logs or auth.
     */
    public static function resolveVoucherCreator(PaymentVoucher $paymentVoucher): ?User
    {
        try {
            $log = \Illuminate\Support\Facades\DB::table('audit_logs')
                ->where('model_id', $paymentVoucher->id)
                ->where('model_type', 'like', '%PaymentVoucher%')
                ->where('action', 'like', '%created%')
                ->orderBy('id', 'asc')
                ->first();

            if ($log && !empty($log->user_id)) {
                $user = User::find($log->user_id);
                if ($user) {
                    return $user;
                }
            }
        } catch (\Throwable $e) {
            Log::error('resolveVoucherCreator Error: ' . $e->getMessage());
        }

        return auth()->user();
    }

    /**
     * Get all active users belonging to a specific role ID (either directly or via company assignment).
     */
    public static function getUsersForRole(int $roleId, ?int $companyId = null): Collection
    {
        // 1. Direct role assignment via Spatie model_has_roles
        $directUsers = User::whereHas('roles', function ($q) use ($roleId) {
            $q->where('roles.id', $roleId);
        })->where(function ($q) {
            $q->whereNull('status')->orWhere('status', 'active');
        })->get();

        // 2. Company role assignment via company_user_role pivot table
        $companyUsers = User::whereHas('companies', function ($q) use ($roleId, $companyId) {
            $q->where('company_user_role.role_id', $roleId);
            if ($companyId) {
                $q->where('company_user_role.company_id', $companyId);
            }
        })->where(function ($q) {
            $q->whereNull('status')->orWhere('status', 'active');
        })->get();

        return $directUsers->merge($companyUsers)->unique('id');
    }

    /**
     * Resolve party name for Payment Voucher.
     */
    protected static function resolveVoucherPartyName(PaymentVoucher $paymentVoucher): ?string
    {
        if ($paymentVoucher->is_direct) {
            $firstItem = $paymentVoucher->paymentVoucherData()->first();
            if ($firstItem && $firstItem->account_id) {
                return Account::find($firstItem->account_id)?->name;
            }
        } elseif (!empty($paymentVoucher->supplier_id) && $paymentVoucher->supplier_id !== '-') {
            return Account::find($paymentVoucher->supplier_id)?->name
                ?? $paymentVoucher->supplier?->name;
        } elseif (!empty($paymentVoucher->request_account_id) && $paymentVoucher->request_account_id !== '-') {
            return Account::find($paymentVoucher->request_account_id)?->name;
        }

        $firstBill = $paymentVoucher->billPaymentVoucherData()->with('purchaseBill.supplier')->first();
        if ($firstBill && $firstBill->purchaseBill) {
            return $firstBill->purchaseBill->supplier?->name;
        }

        $firstItem = $paymentVoucher->paymentVoucherData()->with(['account', 'paymentRequest.paymentRequestData'])->first();
        if ($firstItem) {
            if ($firstItem->account_id) {
                return Account::find($firstItem->account_id)?->name;
            } elseif ($firstItem->paymentRequest) {
                $prd = $firstItem->paymentRequest->paymentRequestData;
                return $prd?->supplier?->name ?? $prd?->transporter?->name;
            }
        }

        return null;
    }

    /**
     * Dispatch notification for newly created Sale Order to authorized family members at the same location.
     */
    public static function sendSaleOrderCreatedNotification(SalesOrder $salesOrder): void
    {
        try {
            $creatorId = $salesOrder->created_by ?: auth()->id();
            $creator = $creatorId ? User::find($creatorId) : auth()->user();
            if (!$creator) {
                return;
            }

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
            }

            // (c) If Sale Order has a specific Sell By / Parent user different from creator
            if (!empty($salesOrder->parent_user_id) && $salesOrder->parent_user_id != $creator->id) {
                $candidateUserIds->push($salesOrder->parent_user_id);
            }

            // (d) Children of creator
            $childIds = User::where('parent_user_id', $creator->id)->pluck('id');
            $candidateUserIds = $candidateUserIds->merge($childIds);

            // Clean candidate IDs (unique, not empty, exclude creator himself)
            $candidateUserIds = $candidateUserIds
                ->filter(fn($id) => !empty($id) && (int)$id !== (int)$creator->id)
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
            //    - Location: must be assigned to the Sale Order's factory/location
            $candidateUsers = User::whereIn('id', $candidateUserIds)
                ->where('status', 'active')
                ->get();

            $recipients = $candidateUsers->filter(function ($user) use ($creator, $salesOrder, $soFactoryIds) {
                // Rule 1: Approval authority check (Matching View Page logic)
                // - Parent user is naturally authorized to approve by hierarchy
                // - Siblings / Children require 'sale-order-approval' permission (or super-admin)
                $isParent = (!empty($creator->parent_user_id) && (int)$user->id === (int)$creator->parent_user_id)
                    || (!empty($salesOrder->parent_user_id) && (int)$user->id === (int)$salesOrder->parent_user_id);

                $hasPermission = $isParent
                    || ($user->user_type === 'super-admin')
                    || $user->can('sale-order-approval');

                if (!$hasPermission) {
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
            $userName = $creator->name ?? 'System';
            $soNo = $salesOrder->reference_no ?? ('SO#' . $salesOrder->id);
            $customerName = $salesOrder->customer?->name;
            $customerText = $customerName ? " for: <strong>{$customerName}</strong>" : "";
            $targetUrl = route('sales.sale-order.index', ['view_id' => $salesOrder->id]);

            $message = '<a href="' . $targetUrl . '" style="color: inherit; text-decoration: none;"><strong>' . $userName . '</strong> created Sale Order: <strong>' . $soNo . '</strong>' . $customerText . ' (Pending Approval)</a>';

            self::send($recipients, $message, 'Sale Order Approval Required', $targetUrl, $creator->id);
        } catch (\Throwable $e) {
            Log::error('Sale Order Notification Error: ' . $e->getMessage());
        }
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

            self::send($recipients, $message, 'Sale Order Head Office Approval', $targetUrl, $approverId);
        } catch (\Throwable $e) {
            Log::error('Sale Order Head Office Notification Error: ' . $e->getMessage());
        }
    }
}
