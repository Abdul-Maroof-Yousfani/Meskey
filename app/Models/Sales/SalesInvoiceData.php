<?php

namespace App\Models\Sales;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesInvoiceData extends Model
{
    use HasFactory;

    protected $table = "sales_invoice_data";

    protected $fillable = [
        'sales_invoice_id',
        'item_id',
        'packing',
        'no_of_bags',
        'qty',
        'rate',
        'gross_amount',
        'discount_percent',
        'discount_amount',
        'amount',
        'gst_percent',
        'gst_amount',
        'net_amount',
        'dc_data_id',
        'line_desc',
        'truck_no',
        'description'
    ];

    public function sales_invoice()
    {
        return $this->belongsTo(SalesInvoice::class, 'sales_invoice_id');
    }

    public function item()
    {
        return $this->belongsTo(Product::class, 'item_id');
    }

    public function delivery_challan_data()
    {
        return $this->belongsTo(DeliveryChallanData::class, 'dc_data_id');
    }
}

