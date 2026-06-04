<li class="dropdown-menu-header">
    <div class="dropdown-header d-flex justify-content-between m-0 px-3 py-2 white bg-primary">
        <div class="d-flex">
            <i class="ft-bell font-medium-3 d-flex align-items-center mr-2"></i>
            <span class="noti-title">{{ auth()->user()->unreadNotifications->count() }} New Notification(s)</span>
        </div>
    </div>
</li>
<li class="scrollable-container">
    @forelse($notifications as $notification)
        <a class="d-flex justify-content-between" href="{{ route('notifications.readAndRedirect', $notification->id) }}" style="{{ is_null($notification->read_at) ? 'background-color: #e4e7ed;' : '' }}">
            <div class="media d-flex align-items-center">
                <div class="media-body">
                    <h6 class="noti-text font-small-3 m-0" style="line-height: 1.5; {{ is_null($notification->read_at) ? 'font-weight: bold;' : '' }}">
                        {!! strip_tags($notification->data['message'], '<strong><b><i><em><br>') !!}
                    </h6>
                    <small class="grey lighten-1 font-italic float-right">{{ $notification->created_at->diffForHumans() }}</small>
                </div>
            </div>
        </a>
    @empty
        <a class="d-flex justify-content-center py-2" href="javascript:void(0)">
            <span class="text-muted">No notifications found.</span>
        </a>
    @endforelse
</li>
<li class="dropdown-menu-footer">
    <div class="row m-0 border-top">
        <div class="col-6 p-0">
            <a class="dropdown-item text-center text-primary py-2 mark-all-read-btn" href="javascript:void(0)">Read All</a>
        </div>
        <div class="col-6 p-0 border-left">
            <a class="dropdown-item text-center text-primary py-2" href="{{ route('notifications.all') }}">Show All</a>
        </div>
    </div>
</li>
