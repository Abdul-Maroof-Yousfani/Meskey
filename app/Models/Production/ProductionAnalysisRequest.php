<?php

namespace App\Models\Production;

use App\Models\Acl\Company;
use App\Models\Master\ArrivalLocation;
use App\Models\Master\CompanyLocation;
use App\Models\Master\Plant;
use App\Models\Production\JobOrder\JobOrder;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductionAnalysisRequest extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'production_analysis_requests';

    protected $fillable = [
        'request_no',
        'request_date',
        'company_id',
        'company_location_id',
        'arrival_location_id',
        'plant_id',
        'job_order_id',
        'type',
        'remarks',
        'status',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'request_date' => 'date',
    ];

    // Allowed analysis types constant
    public const TYPE_INPUT = 'production-input-analysis';
    public const TYPE_OUTPUT = 'production-output-analysis';
    public const TYPE_MACHINE = 'production-machine-analysis';

    public static function getTypes(): array
    {
        return [
            self::TYPE_INPUT => 'Input Analysis',
            self::TYPE_OUTPUT => 'Output Analysis',
            self::TYPE_MACHINE => 'Machine Analysis',
        ];
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
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

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function productionAnalysis()
    {
        return $this->hasOne(ProductionAnalysis::class, 'analysis_request_id');
    }

    public function machineAnalysis()
    {
        return $this->hasOne(ProductionMachineAnalysis::class, 'analysis_request_id');
    }

    public function getTypeNameAttribute(): string
    {
        return match ($this->type) {
            self::TYPE_INPUT => 'Input Analysis',
            self::TYPE_OUTPUT => 'Output Analysis',
            self::TYPE_MACHINE => 'Machine Analysis',
            default => ucfirst(str_replace(['production-', '-'], ['', ' '], (string) $this->type)),
        };
    }

    public function getTypeBadgeClassAttribute(): string
    {
        return match ($this->type) {
            self::TYPE_INPUT => 'badge-info',
            self::TYPE_OUTPUT => 'badge-success',
            self::TYPE_MACHINE => 'badge-warning',
            default => 'badge-secondary',
        };
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            'completed' => 'badge-success',
            'in_progress' => 'badge-primary',
            'cancelled' => 'badge-danger',
            default => 'badge-secondary', // pending
        };
    }

    /**
     * Get route to create the analysis from this request
     */
    public function getCreateAnalysisRoute(): string
    {
        return match ($this->type) {
            self::TYPE_INPUT => route('production-input-analysis.create', ['analysis_request_id' => $this->id]),
            self::TYPE_OUTPUT => route('production-output-analysis.create', ['analysis_request_id' => $this->id]),
            self::TYPE_MACHINE => route('production-machine-analysis.create', ['analysis_request_id' => $this->id]),
            default => '#',
        };
    }
}
