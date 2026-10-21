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
     */
    public static function sendPaymentVoucherNotification(PaymentVoucher $paymentVoucher): void
    {
        try {
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
            $targetUrl = route('payment-voucher.show', $paymentVoucher->id);

            $message = '<a href="' . $targetUrl . '" style="color: inherit; text-decoration: none;"><strong>' . $userName . '</strong> created a Payment Voucher: <strong>' . $voucherNo . '</strong>' . $partyText . $amountText . '</a>';

            self::send($recipients, $message, 'Payment Voucher', $targetUrl, auth()->user()?->id);
        } catch (\Throwable $e) {
            Log::error('Payment Voucher Notification Error: ' . $e->getMessage());
        }
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
