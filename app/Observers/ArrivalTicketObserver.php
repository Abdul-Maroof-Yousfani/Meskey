<?php

namespace App\Observers;

use App\Models\Arrival\ArrivalTicket;
use App\Models\Arrival\ArrivalSamplingRequest;

class ArrivalTicketObserver
{
    public function creating(ArrivalTicket $arrivalTicket)
    {
         $datePrefix = date('m-d-Y').'-';
        $arrivalTicket->unique_no = generateUniqueNumber($datePrefix, 'arrival_tickets', null, 'unique_no');
    }

    /**
     * Handle the ArrivalTicket "created" event.
     */
    public function created(ArrivalTicket $arrivalTicket): void
    {
        ArrivalSamplingRequest::create([
            'company_id'       => $arrivalTicket->company_id,
            'arrival_ticket_id'=> $arrivalTicket->id,
            'sampling_type'    => 'initial',
            'is_re_sampling'   => 'no',
            'is_done'          => 'no',
            'remark'           => null,
        ]);
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
