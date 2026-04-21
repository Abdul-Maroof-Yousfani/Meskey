<?php

namespace App\Models\Export;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CommercialInvoice extends Model
{
    use HasFactory;

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $casts = [
        'invoice_date' => 'date',
    ];

    public function exportOrder()
    {
        return $this->belongsTo(ExportOrder::class);
    }

    public function billOfLading()
    {
        return $this->belongsTo(BillOfLading::class, 'bill_of_lading_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }
}
