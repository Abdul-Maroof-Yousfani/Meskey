{{-- Header notification dropdown ki rows (10 10 karke). dropdown_items (pehle 10) + NotificationController@fetchDropdown (page>1) --}}
@foreach ($notifications as $notification)
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
@endforeach
