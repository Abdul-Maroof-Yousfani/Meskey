<?php

namespace App\Models\Sales;

use App\Models\Master\Customer;
use App\Models\Product;
use App\Traits\HasBalancing;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SaleReturnData extends Model
{
    use HasFactory;

    protected $fillable = [
        "quantity",
        "sale_return_id",
        "sale_invoice_data_id",
        "rate",
        "gross_amount",
        "discount_percent",
        "discount_amount",
        "amount",
        "gst",
        "gst_percentage",
        "gst_amount",
        "net_amount",
        "line_desc",
        "truck_no",
        "packing",
        "no_of_bags"
    ];

    public function sale_return() {
        return $this->belongsTo(SalesReturn::class, "sale_return_id");
    }

    public function sale_invoice_data() {
        return $this->belongsTo(SalesInvoiceData::class, "sale_invoice_data_id");
    }

    public function getItemAttribute()
    {
        if ($this->sale_invoice_data && $this->sale_invoice_data->item) {
            return $this->sale_invoice_data->item;
        }

        $dcData = \App\Models\Sales\DeliveryChallanData::with('product.unitOfMeasure')->find($this->sale_invoice_data_id);
        if ($dcData && $dcData->product) {
            return $dcData->product;
        }

        $rrItem = \App\Models\Sales\ReceivingRequestItem::with('product.unitOfMeasure')->find($this->sale_invoice_data_id);
        if ($rrItem && $rrItem->product) {
            return $rrItem->product;
        }

        return null;
    }

    public function getItemIdAttribute()
    {
        return $this->item?->id ?? null;
    }

    public function getItemNameAttribute()
    {
        return $this->item?->name ?? 'N/A';
    }
}
