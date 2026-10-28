<?php

namespace App\Services;

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
     * Forward domain-specific notification calls to module services (SaleNotificationService / FinanceNotificationService) for backward compatibility.
     */
    public static function __callStatic($method, $arguments)
    {
        if (method_exists(SaleNotificationService::class, $method)) {
            return SaleNotificationService::$method(...$arguments);
        }

        if (method_exists(FinanceNotificationService::class, $method)) {
            return FinanceNotificationService::$method(...$arguments);
        }

        throw new \BadMethodCallException("Method {$method} does not exist on NotificationService.");
    }
}
