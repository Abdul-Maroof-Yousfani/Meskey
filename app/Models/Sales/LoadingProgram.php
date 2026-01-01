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
    public function loadingSlips() {
        return $this->hasManyThrough(
            LoadingSlip::class,
            LoadingProgramItem::class,
            'loading_program_id', // foreign key on loading_program_items table
            'loading_program_item_id', // foreign key on loading_slips table
            'id', // local key on loading_programs table
            'id' // local key on loading_program_items table
        );
    }
}
