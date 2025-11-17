<?php

namespace App\Observers;

use App\\Models\\Org\\Employee\\ShiftTime;

class ShiftTimeObserver
{
    /**
     * Handle the ShiftTime "created" event.
     */
    public function created(ShiftTime $shiftTime): void
    {
        //
    }

    /**
     * Handle the ShiftTime "updated" event.
     */
    public function updated(ShiftTime $shiftTime): void
    {
        //
    }

    /**
     * Handle the ShiftTime "deleted" event.
     */
    public function deleted(ShiftTime $shiftTime): void
    {
        //
    }

    /**
     * Handle the ShiftTime "restored" event.
     */
    public function restored(ShiftTime $shiftTime): void
    {
        //
    }

    /**
     * Handle the ShiftTime "force deleted" event.
     */
    public function forceDeleted(ShiftTime $shiftTime): void
    {
        //
    }
}
