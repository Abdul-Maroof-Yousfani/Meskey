<?php

namespace App\Models\Arrival;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Freight extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'estimated_freight',
        'loaded_weight',
        'arrived_weight',
        'difference',
        'exempted_weight',
        'pq_rate',
        'net_shortage',
        'shortage_weight_freight_deduction',
        'freight_per_ton',
        'kanta_golarchi_charges',
        'other_labour_charges',
        'other_deduction',
        'unpaid_labor_charges',
        'freight_written_on_billy',
        'gross_freight_amount',
        'net_freight',
        'billy_document',
        'loading_weight_document',
        'other_document',
        'other_document_2',
        'status',
        'company_id'
    ];

    protected $dates = ['created_at', 'updated_at', 'deleted_at'];
}
