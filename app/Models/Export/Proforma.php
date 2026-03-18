<?php

namespace App\Models\Export;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Proforma extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function exportOrder()
    {
        return $this->belongsTo(ExportOrder::class, 'export_order_id');
    }
}
