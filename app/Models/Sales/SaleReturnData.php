<?php

namespace App\Models\Sales;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SaleReturnData extends Model
{
    use HasFactory;

    protected $fillable = [
        "quantity",
        "sale_return_id",
        "sale_invoice_data_id"
    ];

    public function sale_return() {
        return $this->belongsTo(SalesReturn::class, "sale_return_id");
    }

    public function sale_invoice_data() {
        return $this->belongsTo(SalesInvoiceData::class, "sale_invoice_data_id");
    }
}
