<?php

namespace App\Models\Export;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExportSodaField extends Model
{
    use HasFactory;
    protected $guarded = ['id'];

    protected $casts = [
        'price_per_kg' => 'float',
        'price_per_mound' => 'float',
        'price_per_100_kg' => 'float',
        'quantity_in_kg' => 'float',
        'quantity_in_ton' => 'float',
    ];

    public function buyer()
    {
        return $this->belongsTo(\App\Models\User::class, 'buyer_id');
    }

    public function product()
    {
        return $this->belongsTo(\App\Models\Product::class, 'product_id');
    }

    public function packing()
    {
        return $this->belongsTo(\App\Models\BagPacking::class, 'bag_packing_id');
    }

    public function incoterm()
    {
        return $this->belongsTo(\App\Models\Export\IncoTerm::class, 'incoterm_id');
    }

    public function modeOfTerm()
    {
        return $this->belongsTo(\App\Models\Export\ModeOfTerm::class, 'mode_of_term_id');
    }

    public function company()
    {
        return $this->belongsTo(\App\Models\Master\Company::class, 'company_id');
    }
}
