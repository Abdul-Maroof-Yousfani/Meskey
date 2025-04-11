<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ArrivalPurchaseOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'unique_no',
        'company_id',
        'product_id',
        'supplier_id',
        'broker_id',
        'po_date',
        'remarks',
        'sauda_type_id'
    ];
}
