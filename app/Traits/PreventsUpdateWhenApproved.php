<?php 

namespace App\Traits;

trait PreventsUpdateWhenApproved {
    protected static function bootedPreventsUpdateWhenApproved(): void
    {
        static::updating(function ($model) {
            dd("test");
            $statusColumn = property_exists($model, 'approvalStatusColumn') 
                ? $model->approvalStatusColumn 
                : 'am_approval_status';

            if ($model->getAttribute($statusColumn) === 'approved') {
                
                throw ValidationException::withMessages([
                    $statusColumn => 'This record has been approved and cannot be updated.'
                ]);
            }
        });
    }
}