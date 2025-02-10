<?php

namespace App\Observers;

use App\Models\Arrival\ArrivalTicket;

class ArrivalTicketObserver
{

    public function creating(ArrivalTicket $ArrivalTicket)
    {
        $ArrivalTicket->unique_no = generateUniqueNumber(null, 'arrival_tickets', null, 'unique_no');

    }
    /**
     * Handle the ArrivalTicket "created" event.
     */
    public function created(ArrivalTicket $arrivalTicket): void
    {
        //
    }

    /**
     * Handle the ArrivalTicket "updated" event.
     */
    public function updated(ArrivalTicket $arrivalTicket): void
    {
        //
    }

    /**
     * Handle the ArrivalTicket "deleted" event.
     */
    public function deleted(ArrivalTicket $arrivalTicket): void
    {
        //
    }

    /**
     * Handle the ArrivalTicket "restored" event.
     */
    public function restored(ArrivalTicket $arrivalTicket): void
    {
        //
    }

    /**
     * Handle the ArrivalTicket "force deleted" event.
     */
    public function forceDeleted(ArrivalTicket $arrivalTicket): void
    {
        //
    }
}
