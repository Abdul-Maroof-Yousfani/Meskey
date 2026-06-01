<?php

namespace App\Models\Export;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CFreight extends Model
{
    protected $guarded = [];

    public function exportOrder()
    {
        return $this->belongsTo(ExportOrder::class, 'export_order_id');
    }

    public function rates()
    {
        return $this->hasMany(CFreightRate::class, 'c_freight_id');
    }
}
