<?php

namespace App\Models\Export;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuotationPackingItem extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function quotation()
    {
        return $this->belongsTo(Quotation::class);
    }
}
