<?php

namespace App\Models;

use App\Models\Master\ClearingAgent;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClearingAgentCompanyBankDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'bank_name',
        'branch_name',
        'branch_code',
        'account_title',
        'account_number',
        'clearing_agent_id',
    ];

    public function clearingAgent()
    {
        return $this->belongsTo(ClearingAgent::class);
    }
}
