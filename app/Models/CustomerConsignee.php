<?php

namespace App\Models;

use App\Models\Master\Customer;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerConsignee extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'name',
        'address',
        'contact',
        'contact_person',
        'email',
    ];

    // Relationship back to customer
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
