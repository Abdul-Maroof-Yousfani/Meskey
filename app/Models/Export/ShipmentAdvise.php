<?php

namespace App\Models\Export;

use App\Traits\HasApproval;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShipmentAdvise extends Model
{
    use HasFactory, HasApproval;

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $casts = [
        'snapshot_data' => 'array',
        'goods_summary' => 'array',
    ];

    public function packingList()
    {
        return $this->belongsTo(PackingList::class);
    }

    public function commercialInvoice()
    {
        return $this->belongsTo(CommercialInvoice::class);
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
