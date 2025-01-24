        <?php
            use Hashids\Hashids;
        ?>
        
        <li class="dropdown-menu-header bor-b">
            <div class="dropdown-header d-flex">
                <h4 class="notification-title mb-0 mr-auto">Notifications</h4>
                <div class="badge badge-pill badge-light-primary">{{count($notifications)}} New</div>
            </div>
        </li>
        <li class="scrollable-container media-list"><a class="d-flex" href="javascript:void(0)">
        @php
        foreach ($notifications as $notification) {
            $notifiable = $notification->notifiable;
            switch ($notification->type) {
                case 'CalanderEvent':
                    $typeName = 'Event 📅';
                    $title = $notification->notifiable->title.", happening on ". date("d-M-Y", strtotime($notification->notifiable->start)); 
                    $message=  $notification->message;
                    $route= '';
                    break;

                case 'Page':
                    $typeName = 'Page 📰';
                    $title = $notification->message.' on '. date("d-M-Y", strtotime($notification->date));
                    $totalDuration = $notification->notifiable->analytics->sum('total_duration');
                    $hours = floor($totalDuration / 3600);
                    $minutes = floor(($totalDuration % 3600) / 60);
                    $message = 'page is viewed for '. $hours.' hrs and '.$minutes.' mins on '.date("d-M-Y", strtotime($notification->date)); 
                    $hashids = new Hashids(env('HASH_SALT'), 10);
                    $encryptedId = $hashids->encode($notification->notifiable->id);
                    $route= route('viewpage', ['encrypted' => $encryptedId ]);
                    break;

                case 'Lead':
                    $typeName = 'Lead 🧍';
                    $title = $notification->notifiable->title.", created on ".date("d-M-Y",strtotime($notification->notifiable->date));
                    $message = $notification->message;
                    $route= route('leads.show',$notification->notifiable->id);
                    break;

                default:
                    $typeName = 'Unknown';
                    $title= 'Unknown';
                    $message= 'Unknown';
                    $route='';
                    break;
            }
        @endphp       
            <a class="d-flex"  onclick="onNotificaitonClick('<?php echo $message ?>','<?php echo $notification->type ?>','<?php echo $route ?>','<?php echo $notification->id ?>')" >
                <div class="media d-flex align-items-start cus-de">
                    <div class="media-left">
                        <div class="avatar"><img src="../theme/assets/images/portrait/small/avatar-s-15.jpg" alt="avatar" width="32" height="32"></div>
                    </div>
                    <div class="media-body">
                        <p class="media-heading">
                        <span class="font-weight-bolder">
                            {{ $typeName }} 
                        </span>
                            {{ $title }}
                        </p>
                        <small class="notification-text"> 
                            {{ $message }}
                        </small>
                    </div>
                </div>
            </a>
        @php
        }
        @endphp    
        </li>

    