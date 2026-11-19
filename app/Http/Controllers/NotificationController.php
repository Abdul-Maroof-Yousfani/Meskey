<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

class NotificationController extends Controller
{
    public function getUnreadCount()
    {
        // Use () to perform count and first at the database level instead of loading all rows into RAM
        $count = auth()->user()->unreadNotifications()->count();
        $latest = auth()->user()->unreadNotifications()->first();
        
        $latestData = null;
        if ($latest) {
            $latestData = [
                'id' => $latest->id,
                'message' => strip_tags($latest->data['message'] ?? 'You have a new notification.')
            ];
        }

        return response()->json([
            'count' => $count,
            'latest' => $latestData
        ]);
    }

    /**
     * Header notification dropdown - 10 10 karke (infinite scroll).
     * page=1 : poora dropdown (header + pehli 10 + footer)
     * page>1 : sirf agli 10 rows ka HTML (JS list ke aakhir me jorta hai)
     */
    public function fetchDropdown(Request $request)
    {
        $perPage = 10;
        $page = max(1, (int) $request->query('page', 1));

        if ($page === 1) {
            // Scope cleanup to current user and ONLY delete READ notifications older than 3 months
            auth()->user()->notifications()->whereNotNull('read_at')->where('created_at', '<', now()->subMonths(3))->delete();
        }

        // ek zyada mangwao: pata chal jaye aur hain ya nahi (count query ki zaroorat nahi)
        $notifications = auth()->user()->notifications()
            ->skip(($page - 1) * $perPage)
            ->take($perPage + 1)
            ->get();

        $hasMore = $notifications->count() > $perPage;
        $notifications = $notifications->take($perPage);

        $html = $page === 1
            ? view('management.notifications.dropdown_items', compact('notifications', 'hasMore'))->render()
            : view('management.notifications.partials.dropdown_rows', compact('notifications'))->render();

        return response()->json([
            'html' => $html,
            'has_more' => $hasMore,
            'next_page' => $page + 1,
        ]);
    }

    public function markAllAsRead()
    {
        // Update at database level directly, avoiding loading hundreds of models into memory
        auth()->user()->unreadNotifications()->update(['read_at' => now()]);
        return response()->json(['success' => true]);
    }

    public function readAndRedirect($id)
    {
        $notification = auth()->user()->notifications()->where('id', $id)->first();
        if ($notification) {
            $notification->markAsRead();
            
            // Extract URL from the message link
            preg_match('/href="([^"]+)"/', $notification->data['message'], $matches);
            if (isset($matches[1])) {
                return redirect($matches[1]);
            }
        }
        
        return redirect()->back();
    }

    public function index()
    {
        // Scope cleanup to current user and ONLY delete READ notifications older than 3 months
        auth()->user()->notifications()->whereNotNull('read_at')->where('created_at', '<', now()->subMonths(3))->delete();

        return view('management.notifications.index');
    }

    public function getList(Request $request)
    {
        $perPage = $request->get('per_page', 25);
        $search = $request->search;

        $query = auth()->user()->notifications();

        if ($search) {
            $query->where('data', 'like', "%$search%");
        }

        $notifications = $query->paginate($perPage);

        return view('management.notifications.getList', compact('notifications'));
    }
}
