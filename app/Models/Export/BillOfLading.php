<?php

namespace App\Models\Export;

use App\Traits\HasApproval;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BillOfLading extends Model
{
    use HasFactory, HasApproval;

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $casts = [
        'bill_date' => 'date',
        'shipped_on_board_date' => 'date',
        'selected_form_e_ids' => 'array',
        'selected_delivery_challan_ids' => 'array',
        'selected_delivery_order_ids' => 'array',
        'snapshot_data' => 'array',
        'goods_summary' => 'array',
    ];

    public function exportDeliveryChallan()
    {
        return $this->belongsTo(ExportDeliveryChallan::class, 'export_delivery_challan_id');
    }

    public function deliveryOrder()
    {
        return $this->belongsTo(ExportDeliveryOrder::class, 'delivery_order_id');
    }

    public function exportOrder()
    {
        return $this->belongsTo(ExportOrder::class, 'export_order_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }
}
