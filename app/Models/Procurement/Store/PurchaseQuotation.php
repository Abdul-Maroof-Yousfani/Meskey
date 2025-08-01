<?php

namespace App\Models\Procurement\Store;

use App\Models\Master\CompanyLocation;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseQuotation extends Model
{
    use HasFactory;

    protected $table = "purchase_quotations";
    protected $guarded = [];

    public function location()
    {
        return $this->belongsTo(CompanyLocation::class, 'location_id');
    }

    public function quotation_data()
    {
        return $this->hasMany(PurchaseQuotationData::class);
    }
}
