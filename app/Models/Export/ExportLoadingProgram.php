<?php

namespace App\Models\Export;

use App\Models\Sales\LoadingProgramItem;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExportLoadingProgram extends Model
{
    protected $table = 'loading_programs';
    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected static function booted()
    {
        static::creating(function ($model) {
            $model->type = 'export_order';
        });

        static::updating(function ($model) {
            $model->type = 'export_order';
        });

        static::addGlobalScope('export_type', function ($builder) {
            $builder->where('type', 'export_order');
        });
    }

    protected $casts = [
        'company_locations' => 'array',
        'arrival_locations' => 'array',
        'sub_arrival_locations' => 'array',
    ];

    public function deliveryOrder()
    {
        return $this->belongsTo(ExportDeliveryOrder::class, 'delivery_order_id');
    }

    public function deliveryOrders()
    {
        return $this->belongsToMany(ExportDeliveryOrder::class, 'loading_program_delivery_order', 'loading_program_id', 'delivery_order_id')->withTimestamps();
    }

    public function exportOrder()
    {
        return $this->belongsTo(\App\Models\Export\ExportOrder::class, 'export_order_id');
    }

    public function exportOrders()
    {
        return $this->belongsToMany(\App\Models\Export\ExportOrder::class, 'loading_program_export_order', 'loading_program_id', 'export_order_id')->withTimestamps();
    }

    public function loadingProgramItems()
    {
        return $this->hasMany(LoadingProgramItem::class, 'loading_program_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }
}
