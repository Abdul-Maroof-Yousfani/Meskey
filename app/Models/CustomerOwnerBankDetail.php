<?php

namespace App\Models;

use App\Models\Master\Customer;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerOwnerBankDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'bank_name',
        'branch_name',
        'branch_code',
        'account_title',
        'account_number'
    ];

    // Relationship back to customer
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
