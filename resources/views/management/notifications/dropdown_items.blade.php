<li class="dropdown-menu-header">
    <div class="dropdown-header d-flex justify-content-between align-items-center m-0 px-3 py-2 white bg-primary">
        <div class="d-flex align-items-center">
            <i class="ft-bell font-medium-3 mr-2"></i>
            <span class="noti-title font-weight-bold">{{ auth()->user()->unreadNotifications->count() }} New Notification(s)</span>
        </div>
        @if(auth()->user()->unreadNotifications->count() > 0)
        <a href="javascript:void(0)" class="mark-all-read-btn text-white" style="font-size: 0.8rem; text-decoration: underline; opacity: 0.9;">
            Mark all read
        </a>
        @endif
    </div>
</li>
{{-- 10 10 karke: neeche pahunchne par header.blade.php ka JS data-next-page wala page mangwata hai --}}
<li class="scrollable-container" data-next-page="2" data-has-more="{{ !empty($hasMore) ? 1 : 0 }}"
    style="max-height: 22rem; overflow-y: auto !important; overflow-x: hidden; position: relative; background: #ffffff;">
    @if ($notifications->isEmpty())
        <div class="d-flex justify-content-center align-items-center py-4">
            <span class="text-muted font-small-3">No notifications found.</span>
        </div>
    @else
        @include('management.notifications.partials.dropdown_rows', ['notifications' => $notifications])
        {{-- agli 10 aate waqt spinner (JS dikhata hai) --}}
        <div class="noti-loadmore" style="display: none;">
            <span class="noti-loadmore-spinner"></span>
        </div>
    @endif
</li>
<li class="dropdown-menu-footer" style="background-color: #f8f9fa; border-top: 1px solid #dee2e6; position: relative; z-index: 10;">
    <div class="d-flex justify-content-between align-items-center px-3 py-2 w-100">
        <a href="javascript:void(0)" class="mark-all-read-btn text-primary font-small-3 font-weight-bold" style="text-decoration: none; cursor: pointer;">
            <i class="ft-check mr-1"></i> Read All
        </a>
        <a href="{{ route('notifications.all') }}" class="text-primary font-small-3 font-weight-bold" style="text-decoration: none; cursor: pointer;">
            Show All <i class="ft-arrow-right ml-1"></i>
        </a>
    </div>
</li>
