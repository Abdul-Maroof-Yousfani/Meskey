<?php

namespace App\Models\Export;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExportSodaField extends Model
{
    use HasFactory;
    protected $guarded = ['id'];

    protected $casts = [
        'commission_percentage' => 'float',
        'commission_amount_per_ton' => 'float',
        'commission' => 'float',
        'shipment_period' => 'date',
    ];

    public function buyer()
    {
        return $this->belongsTo(\App\Models\Master\Customer::class , 'buyer_id');
    }

    public function product()
    {
        return $this->belongsTo(\App\Models\Product::class , 'product_id');
    }

    public function packingItems()
    {
        return $this->hasMany(ExportSodaPackingItem::class , 'export_soda_id');
    }

    public function incoterm()
    {
        return $this->belongsTo(\App\Models\Export\IncoTerm::class , 'incoterm_id');
    }

    public function modeOfTerm()
    {
        return $this->belongsTo(\App\Models\Export\ModeOfTerm::class , 'mode_of_term_id');
    }

    public function company()
    {
        return $this->belongsTo(\App\Models\Master\Company::class , 'company_id');
    }
}
