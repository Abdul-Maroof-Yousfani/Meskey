<?php

namespace App\Models\Export;

use App\Models\Sales\DispatchQc;

class ExportDispatchQc extends DispatchQc
{
    protected $table = 'dispatch_qc';

    protected static function booted()
    {
        static::creating(function ($model) {
            $model->type = 'export_dispatch_qc';
        });

        static::updating(function ($model) {
            $model->type = 'export_dispatch_qc';
        });

        static::addGlobalScope('export_type', function ($builder) {
            $builder->withoutGlobalScope('sale_type')->where('type', 'export_dispatch_qc');
        });
    }

    public function attachments()
    {
        return $this->hasMany(ExportDispatchQcAttachment::class, 'dispatch_qc_id');
    }
}
