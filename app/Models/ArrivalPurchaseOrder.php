<?php

namespace App\Models;

use App\Models\Master\Broker;
use App\Models\Master\Supplier;
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

    public function broker()
    {
        return $this->belongsTo(Broker::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }
}
