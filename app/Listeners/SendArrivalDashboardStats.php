<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Events\RefreshArrivalDashboard

class SendArrivalDashboardStats
{
    public function handle(RefreshArrivalDashboard $event)
    {
        $service = app(ArrivalDashboardService::class);

        $data = $service->getArrivalDashboardData(
            now()->subDays(30),
            now(),
            $event->companyId
        );

        broadcast(new ArrivalDashboardStats($event->companyId, $data));
    }
}
