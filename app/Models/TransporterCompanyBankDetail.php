<?php

namespace App\Models;

use App\Models\Master\Transporter;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransporterCompanyBankDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'transporter_id',
        'bank_name',
        'branch_name',
        'branch_code',
        'account_title',
        'account_number',
    ];

    public function transporter()
    {
        return $this->belongsTo(Transporter::class);
    }
}
