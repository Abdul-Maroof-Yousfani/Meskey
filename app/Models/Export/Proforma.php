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

    public function getCustomerBankAttribute()
    {
        if ($this->customer_bank_type === 'owner') {
            return \App\Models\CustomerOwnerBankDetail::find($this->customer_bank_id);
        } elseif ($this->customer_bank_type === 'company') {
            return \App\Models\CustomerCompanyBankDetail::find($this->customer_bank_id);
        }

        return $this->exportOrder?->customer_bank ?? null;
    }
}
