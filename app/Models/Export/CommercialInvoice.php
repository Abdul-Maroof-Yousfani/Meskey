<?php

namespace App\Models\Export;

use App\Traits\HasApproval;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CommercialInvoice extends Model
{
    use HasFactory, HasApproval;

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $casts = [
        'invoice_date' => 'date',
        'selected_bill_of_lading_ids' => 'array',
    ];

    public function exportOrder()
    {
        return $this->belongsTo(ExportOrder::class);
    }

    public function billOfLading()
    {
        return $this->belongsTo(BillOfLading::class, 'bill_of_lading_id');
    }

    public function getResolvedBillOfLadingIdsAttribute(): array
    {
        $ids = $this->selected_bill_of_lading_ids ?? [];

        if (empty($ids) && $this->bill_of_lading_id) {
            $ids = [$this->bill_of_lading_id];
        }

        return array_values(array_unique(array_map('intval', array_filter($ids))));
    }

    public function createdBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }
}
