<?php

namespace App\Models\Production;

use App\Models\Master\ArrivalLocation;
use App\Models\Master\CompanyLocation;
use App\Models\Master\Plant;
use App\Models\Product;
use App\Models\Production\JobOrder\JobOrder;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Raised when an Output Analysis QC result is outside its Job Order spec.
 * checklist: rows of [slab_type_id, name, qc_symbol, spec_text, spec_value, value_type, result, status (ok|warn|none)]
 */
class ProductionOutputAlert extends Model
{
    protected $table = 'production_output_alerts';

    protected $fillable = [
        'alert_no',
        'company_id',
        'production_analysis_id',
        'analysis_request_id',
        'parent_alert_id',
        'attempt',
        'company_location_id',
        'arrival_location_id',
        'plant_id',
        'job_order_id',
        'product_id',
        'checklist',
        'difference_count',
        'status',
        'remarks',
        'remarked_by',
        'remarked_at',
        'new_analysis_request_id',
    ];

    protected $casts = [
        'checklist' => 'array',
        'remarked_at' => 'datetime',
    ];

    public const STATUS_PENDING = 'pending';
    public const STATUS_RESOLVED = 'resolved';

    public function analysis()
    {
        return $this->belongsTo(ProductionAnalysis::class, 'production_analysis_id');
    }

    public function analysisRequest()
    {
        return $this->belongsTo(ProductionAnalysisRequest::class, 'analysis_request_id');
    }

    public function newAnalysisRequest()
    {
        return $this->belongsTo(ProductionAnalysisRequest::class, 'new_analysis_request_id');
    }

    public function parentAlert()
    {
        return $this->belongsTo(self::class, 'parent_alert_id');
    }

    public function companyLocation()
    {
        return $this->belongsTo(CompanyLocation::class, 'company_location_id');
    }

    public function arrivalLocation()
    {
        return $this->belongsTo(ArrivalLocation::class, 'arrival_location_id');
    }

    public function plant()
    {
        return $this->belongsTo(Plant::class, 'plant_id');
    }

    public function jobOrder()
    {
        return $this->belongsTo(JobOrder::class, 'job_order_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function remarkedBy()
    {
        return $this->belongsTo(User::class, 'remarked_by');
    }

    /**
     * Next date-wise code: {LOCATION}-QCA-{serial}-{m-d-Y}, e.g. KHI-QCA-001-11-20-2026.
     * The serial starts again at 001 each day for each location.
     */
    public static function generateNumber($date = null, $companyLocationId = null): string
    {
        $datePart = \Carbon\Carbon::parse($date ?: now())->format('m-d-Y');
        $locationCode = $companyLocationId ? CompanyLocation::whereKey($companyLocationId)->value('code') : null;
        $prefix = strtoupper(trim($locationCode ?: 'LOC')) . '-QCA';

        $regex = '/^' . preg_quote($prefix, '/') . '-(\d+)-' . preg_quote($datePart, '/') . '$/';
        $max = self::where('alert_no', 'like', $prefix . '-%-' . $datePart)
            ->lockForUpdate()
            ->pluck('alert_no')
            ->reduce(fn($max, $no) => preg_match($regex, $no, $m) ? max($max, (int) $m[1]) : $max, 0);

        return $prefix . '-' . str_pad((string) ($max + 1), 3, '0', STR_PAD_LEFT) . '-' . $datePart;
    }

    /** Older rows without a saved code fall back to the id. */
    public function getAlertNoAttribute($value): string
    {
        return $value ?: 'QCA-' . str_pad((string) $this->id, 5, '0', STR_PAD_LEFT);
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }
}
