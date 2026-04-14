<?php

namespace App\Models\Sales;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DispatchQc extends Model
{
    use HasFactory;
    protected $table = "dispatch_qc";
    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected static function booted()
    {
        static::creating(function ($model) {
            $model->type = 'sale_dispatch_qc';
        });

        static::updating(function ($model) {
            $model->type = 'sale_dispatch_qc';
        });

        static::addGlobalScope('sale_type', function ($builder) {
            $builder->where('type', 'sale_dispatch_qc');
        });
    }

    public function loadingProgramItem()
    {
        return $this->belongsTo(LoadingProgramItem::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function attachments()
    {
        return $this->hasMany(DispatchQcAttachment::class);
    }
}
