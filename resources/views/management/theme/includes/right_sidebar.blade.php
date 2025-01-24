@php
use App\Models\Notification;
use Carbon\Carbon;

$notifications = Notification::with('notifiable')
->whereDate('date', '>=', Carbon::now()->subDays(1))
->where('user_id',auth()->user()->id)
->where('status',1)
->get();

@endphp
<div class="right-sidebar">
    <ul class="nav navbar-nav bookmark-icons li-mb">

        <li class="nav-item dropdown dropdown-notification">
            <a class="nav-link not-bt"  data-toggle="dropdown" title="Notifications" onclick="fetchNotifications()">
                <i class="fa-solid fa-bell"></i>
                @if(count($notifications) > 0)
                    <span class="badge badge-pill badge-danger badge-up">{{count($notifications)}}</span>
                @endif
            </a>
            <ul class="dropdown-menu dropdown-menu-media dropdown-menu-left drp-set" id="notifications">
                
            </ul>
        </li>
        <li class="nav-item d-none d-lg-block">
            <a class="nav-link r-link msg-em-bt" href="{{route('email')}}" data-toggle="tooltip" data-placement="top" title="Email">
                <i class="fa-solid fa-envelope"></i>
            </a>
        </li>
        <li class="nav-item d-none d-lg-block">
            <a class="nav-link r-link chat-em-bt" href="{{route('chatify')}}" data-toggle="tooltip" data-placement="top" title="Chat">
                <i class="fa-solid fa-comment"></i>
            </a>
        </li>
        <li class="nav-item d-none d-lg-block">
            <a class="nav-link r-link cal-bt" href="{{route('calendars.index')}}" data-toggle="tooltip" data-placement="top" title="Calendar">
                <i class="fa-solid fa-calendar-days"></i>
            </a>
        </li>
    </ul>
</div>

<script>
    function onNotificaitonClick(message,type,url,id){
        
        let route='{{ route("UpdateNotification") }}';

        $.ajax({
        url: route,
        method: "GET",
        data: {id:id},
        dataType: "json",
        success: function (res) {
            if(type=='Page'){
                window.open(url, '_blank');
            }
            if(type=='CalanderEvent'){
                toastr.options = {
                    closeButton: true,
                    progressBar: true,
                    showMethod: 'slideDown',
                    timeOut: 5000,
                };
                toastr.info(message);
            }
        },
        error: function (error) {
            // Handle errors here
            $('.loader-container').hide();
            console.error("Error:", error);
        }
        });

            
    }
    

    function fetchNotifications(){
        $('.loader-container').show();
        let url='{{ route("fetchNotifications") }}';
        $('#notifications').html('');
        $.ajax({
            url: url,
            method: "GET",
            dataType: "html",
            success: function (res) {
                $('.loader-container').hide();
                $('#notifications').html(res);
            },
            error: function (error) {
                // Handle errors here
                $('.loader-container').hide();
                console.error("Error:", error);
            }
        });
    }


</script>    
