<?php

namespace App\Models\Sales;

use App\Traits\HasApproval;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesReturn extends Model
{
    use HasFactory, HasApproval;

    protected $fillable = [
        "customer_id",
        "sr_no",
        "date",
        "reference_number",
        "contract_type",
        "company_location_id",
        "arrival_location_id",
        "storage_location_id",
        "remarks",
        "am_approval_status",
        "am_change_made"
    ];

    protected $table = "sales_return";

    public function sale_return_data() {
        return $this->hasMany(SaleReturnData::class, "sale_return_id");
    }

    public function sale_invoices() {
        return $this->belongsToMany(SalesInvoice::class, "sale_return_sale_invoice", "sale_return_id", "sale_invoice_id");
    }
   

}
