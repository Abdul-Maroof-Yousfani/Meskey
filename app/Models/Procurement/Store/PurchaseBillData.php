<?php

namespace App\Models\Procurement\Store;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class PurchaseBillData extends Model
{
    use HasFactory;
    protected $table = "purchase_bills_data";
    protected $guarded = [
        "id",
        "created_at",
        "updated_at"
    ];


}
