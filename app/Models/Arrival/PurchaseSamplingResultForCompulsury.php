<?php

namespace App\Models\Arrival;

use App\Models\Acl\Company;
use App\Models\Master\ArrivalCompulsoryQcParam;
use App\Models\PurchaseSamplingRequest;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseSamplingResultForCompulsury extends Model
{
    use HasFactory;

    protected $table = "purchase_sampling_results_for_compulsury";

    protected $fillable = [
        'company_id',
        'purchase_sampling_request_id',
        'arrival_compulsory_qc_param_id',
        'compulsory_checklist_value',
        'applied_deduction',
        'remark',
    ];

    // Relationships
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function samplingRequest()
    {
        return $this->belongsTo(PurchaseSamplingRequest::class, 'purchase_sampling_request_id');
    }

    public function qcParam()
    {
        return $this->belongsTo(ArrivalCompulsoryQcParam::class, 'arrival_compulsory_qc_param_id');
    }
}
