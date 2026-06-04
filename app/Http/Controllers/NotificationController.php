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

    public function fetchDropdown()
    {
        // Scope cleanup to current user and ONLY delete READ notifications older than 3 months
        auth()->user()->notifications()->whereNotNull('read_at')->where('created_at', '<', now()->subMonths(3))->delete();

        $notifications = auth()->user()->notifications()->take(50)->get();
        
        $html = view('management.notifications.dropdown_items', compact('notifications'))->render();
        return response()->json(['html' => $html]);
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
