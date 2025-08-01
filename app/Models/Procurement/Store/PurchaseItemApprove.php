<?php

namespace App\Models\Procurement\Store;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseItemApprove extends Model
{
    use HasFactory;

    protected $table = 'purchase_item_approve';
    protected $fillable = ['purchase_request_data_id', 'status_id'];
}
