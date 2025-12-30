<?php

namespace App\Models\Sales;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoadingProgram extends Model
{
    use HasFactory;

    protected $table = 'loading_programs';

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $casts = [
        'company_locations' => 'array',
        'arrival_locations' => 'array',
        'sub_arrival_locations' => 'array',
    ];

    public function saleOrder()
    {
        return $this->belongsTo(SalesOrder::class, 'sale_order_id');
    }

    public function deliveryOrder()
    {
        return $this->belongsTo(DeliveryOrder::class, 'delivery_order_id');
    }

    public function loadingProgramItems()
    {
        return $this->hasMany(LoadingProgramItem::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }
}
