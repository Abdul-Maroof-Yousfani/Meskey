<?php

namespace App\Models;

use App\Models\Master\Supplier;
use Illuminate\Database\Eloquent\Model;

class SupplierCompanyBankDetail extends Model
{
    protected $fillable = [
        'supplier_id',
        'bank_name',
        'account_title',
        'account_number'
    ];

    // Relationship back to supplier
    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }
}