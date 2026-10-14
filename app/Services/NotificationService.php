<?php

namespace App\Services;

use App\Models\Master\Account\Account;
use App\Models\PaymentVoucher;
use App\Models\Sales\PaymentIntimation;
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
     * @return bool
     */
    public static function send($users, string $message, string $title = 'Meskey ERP', ?string $url = null, mixed $senderId = null): bool
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

        // 2. Real-time broadcast via Pusher
        self::broadcastToPusher($users, $message, $title, $url, $senderId, $senderName);

        return true;
    }

    /**
     * Broadcast notification payload via Pusher.
     */
    protected static function broadcastToPusher($users, string $message, string $title, ?string $url, int $senderId, string $senderName): void
    {
        $pusherKey = env('PUSHER_APP_KEY');
        $pusherSecret = env('PUSHER_APP_SECRET');
        $pusherAppId = env('PUSHER_APP_ID');
        $pusherCluster = env('PUSHER_APP_CLUSTER', 'ap2');

        if (empty($pusherKey) || empty($pusherSecret) || empty($pusherAppId) || !class_exists(Pusher::class)) {
            return;
        }

        try {
            $options = [
                'cluster' => $pusherCluster,
                'useTLS' => true,
            ];

            $pusher = new Pusher($pusherKey, $pusherSecret, $pusherAppId, $options);

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
}
