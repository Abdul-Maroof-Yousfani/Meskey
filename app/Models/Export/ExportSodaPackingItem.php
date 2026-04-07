<?php

namespace App\Models\Export;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExportSodaPackingItem extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function exportSoda()
    {
        return $this->belongsTo(ExportSodaField::class, 'export_soda_id');
    }

    public function bagType()
    {
        return $this->belongsTo(\App\Models\BagType::class, 'bag_type_id');
    }

    public function bagPacking()
    {
        return $this->belongsTo(\App\Models\BagPacking::class, 'bag_packing_id');
    }
}
