<table class="table table-hover m-0">
    <thead class="bg-light">
        <tr>
            <th width="80%">Notification</th>
            <th width="20%" class="text-right">Time</th>
        </tr>
    </thead>
    <tbody>
        @forelse($notifications as $notification)
            <tr style="{{ is_null($notification->read_at) ? 'background-color: #e4e7ed; font-weight: bold;' : '' }}">
                <td class="align-middle">
                    <a href="{{ route('notifications.readAndRedirect', $notification->id) }}" style="color: inherit; text-decoration: none; display: block;">
                        {!! strip_tags($notification->data['message'], '<strong><b><i><em><br>') !!}
                    </a>
                </td>
                <td class="align-middle text-right text-muted">
                    {{ $notification->created_at->diffForHumans() }}
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="2" class="text-center align-middle py-5">
                    <p class="text-muted mt-3">No Notifications found</p>
                </td>
            </tr>
        @endforelse
    </tbody>
</table>

<div class="row d-flex" id="paginationLinks">
    <div class="col-md-12 text-right">
        {{ $notifications->links() }}
    </div>
</div>
