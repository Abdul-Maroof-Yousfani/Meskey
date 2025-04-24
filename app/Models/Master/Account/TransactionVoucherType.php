<?php

namespace App\Models\Master\Account;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransactionVoucherType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'description',
        'status'
    ];

    protected $casts = [
        'status' => 'string'
    ];

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }
}
