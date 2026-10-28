<?php

namespace App\Services;

use App\Models\Master\Account\Account;
use App\Models\PaymentVoucher;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FinanceNotificationService
{
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

            NotificationService::send($recipients, $message, 'Payment Voucher', $targetUrl, auth()->user()?->id);
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

            NotificationService::send(collect([$creator]), $message, 'Payment Voucher Approved', $targetUrl, auth()->user()?->id);
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

            NotificationService::send(collect([$creator]), $message, "Payment Voucher {$actionTitle}", $targetUrl, auth()->user()?->id);
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
                Log::warning("FinanceNotificationService: No active users found for pending approval role ID {$pendingRole->role_id} on Payment Voucher #{$paymentVoucher->id}");
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

            NotificationService::send($recipients, '<a href="' . $targetUrl . '" style="color: inherit; text-decoration: none;">' . $actionText . '</a>', 'Payment Voucher Approval', $targetUrl, auth()->user()?->id);

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
            $log = DB::table('audit_logs')
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
}

// Optional alias for plural naming if referenced
if (!class_exists('App\Services\FinanceNotificationServices', false)) {
    class_alias(FinanceNotificationService::class, 'App\Services\FinanceNotificationServices');
}
