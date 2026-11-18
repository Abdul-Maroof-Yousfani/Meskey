{{--
    NOTE: list ko <table> hi rakha hai kyun ke scripts.js ka filterationCommon / renderLoadingTable
    loading shimmer ke liye #filteredData ke andar table + thead dhoondta hai.
    CSS (index.blade.php) har row ko card ki tarah dikhata hai.
--}}
<table class="nt-table">
    <thead class="nt-sr-only">
        <tr>
            <th>Notification</th>
        </tr>
    </thead>
    <tbody>
        @php $lastGroup = null; @endphp
        @forelse($notifications as $notification)
            @php
                $created = $notification->created_at;
                $group = $created->isToday() ? 'Today' : ($created->isYesterday() ? 'Yesterday' : $created->format('d M Y'));
                $isUnread = is_null($notification->read_at);
            @endphp

            @if ($group !== $lastGroup)
                <tr class="nt-group">
                    <td>{{ $group }}</td>
                </tr>
                @php $lastGroup = $group; @endphp
            @endif

            <tr class="nt-row {{ $isUnread ? 'is-unread' : '' }}">
                <td>
                    <a class="nt-card" href="{{ route('notifications.readAndRedirect', $notification->id) }}">
                        <span class="nt-ic"><i class="ft-bell"></i></span>
                        <span class="nt-body">
                            <span class="nt-text">
                                {!! strip_tags($notification->data['message'], '<strong><b><i><em><br>') !!}
                            </span>
                            <span class="nt-time" title="{{ $created->format('d M Y, h:i A') }}">
                                <i class="ft-clock"></i>{{ $created->diffForHumans() }}
                                <span class="nt-dot-sep">&middot;</span>{{ $created->format('h:i A') }}
                            </span>
                        </span>
                        @if ($isUnread)
                            <span class="nt-new">New</span>
                        @endif
                        <i class="ft-chevron-right nt-arrow"></i>
                    </a>
                </td>
            </tr>
        @empty
            <tr class="nt-empty-row">
                <td>
                    <div class="nt-empty">
                        <span><i class="ft-bell-off"></i></span>
                        <h5>No notifications found</h5>
                        <p>
                            @if (request('search'))
                                Nothing matches "{{ request('search') }}". Try a different search.
                            @else
                                You're all caught up. New updates will appear here.
                            @endif
                        </p>
                    </div>
                </td>
            </tr>
        @endforelse
    </tbody>
</table>

<div class="row d-flex" id="paginationLinks">
    <div class="col-md-12 text-right">
        {{ $notifications->links('management.notifications.pagination') }}
    </div>
</div>
