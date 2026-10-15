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
<li class="scrollable-container" style="max-height: 22rem; overflow-y: auto !important; overflow-x: hidden; position: relative; background: #ffffff;">
    @forelse($notifications as $notification)
        <a class="d-flex justify-content-between notification-row" href="{{ route('notifications.readAndRedirect', $notification->id) }}" style="display: block; border-bottom: 1px solid #f0f0f0; text-decoration: none; {{ is_null($notification->read_at) ? 'background-color: rgba(39, 72, 154, 0.08);' : 'background-color: #ffffff;' }}">
            <div class="media d-flex align-items-start p-2" style="width: 100%;">
                <div class="media-body" style="width: 100%;">
                    <div class="noti-text font-small-3 text-dark m-0" style="line-height: 1.45; {{ is_null($notification->read_at) ? 'font-weight: 600;' : 'font-weight: 400;' }}">
                        {!! strip_tags($notification->data['message'], '<strong><b><i><em><br>') !!}
                    </div>
                    <div class="text-right mt-1">
                        <small class="text-muted font-italic font-small-1">{{ $notification->created_at->diffForHumans() }}</small>
                    </div>
                </div>
            </div>
        </a>
    @empty
        <div class="d-flex justify-content-center align-items-center py-4">
            <span class="text-muted font-small-3">No notifications found.</span>
        </div>
    @endforelse
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
