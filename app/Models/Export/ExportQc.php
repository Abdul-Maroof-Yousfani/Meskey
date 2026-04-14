<?php

namespace App\Models\Export;

use App\Traits\HasApproval;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExportQc extends Model
{
    use HasFactory, HasApproval;

    protected $table = 'sales_qc';

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected static function booted()
    {
        static::creating(function ($model) {
            $model->type = 'export_qc';
        });

        static::updating(function ($model) {
            $model->type = 'export_qc';
        });

        static::addGlobalScope('export_type', function ($builder) {
            $builder->where('type', 'export_qc');
        });
    }

    public function loadingProgramItem()
    {
        return $this->belongsTo(\App\Models\Sales\LoadingProgramItem::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function attachments()
    {
        return $this->hasMany(ExportQcAttachment::class, 'sales_qc_id');
    }
}
