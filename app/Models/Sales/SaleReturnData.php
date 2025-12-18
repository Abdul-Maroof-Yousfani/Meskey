<?php

namespace App\Models\Sales;

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
}
