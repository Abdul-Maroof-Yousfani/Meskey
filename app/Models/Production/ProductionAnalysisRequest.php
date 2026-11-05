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
use Illuminate\Support\Facades\DB;

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
        'production_voucher_id',
        'machine_plan_setting_id',
        'type',
        'remarks',
        'status',
        'is_auto_generated',
        'auto_generated_at',
        'auto_scheduled_interval',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'request_date' => 'date',
        'is_auto_generated' => 'boolean',
        'auto_generated_at' => 'datetime',
        'auto_scheduled_interval' => 'integer',
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

    public function productionVoucher()
    {
        return $this->belongsTo(ProductionVoucher::class, 'production_voucher_id');
    }

    public function machinePlanSetting()
    {
        return $this->belongsTo(MachinePlanSetting::class, 'machine_plan_setting_id');
    }

    /**
     * Job orders an analysis made from this request belongs to: those of its Production Voucher,
     * plus the single job order older requests were created against.
     */
    public function jobOrderIdsForAnalysis(): array
    {
        $ids = $this->production_voucher_id
            ? DB::table('production_voucher_job_orders')->where('production_voucher_id', $this->production_voucher_id)->pluck('job_order_id')->all()
            : [];

        if ($this->job_order_id) {
            $ids[] = $this->job_order_id;
        }

        return array_values(array_unique(array_map('intval', $ids)));
    }

    private $suggestedProductIdCache = false;

    /** Product to suggest on the analysis form: the voucher's Head Product, else its first job order's product. */
    public function suggestedProductId(): ?int
    {
        if ($this->suggestedProductIdCache !== false) {
            return $this->suggestedProductIdCache;
        }

        $voucher = $this->productionVoucher;
        if ($voucher && $voucher->product_id) {
            return $this->suggestedProductIdCache = (int) $voucher->product_id;
        }

        $firstJobOrderId = $this->jobOrderIdsForAnalysis()[0] ?? null;
        $productId = $firstJobOrderId ? JobOrder::whereKey($firstJobOrderId)->value('product_id') : null;

        return $this->suggestedProductIdCache = $productId ? (int) $productId : null;
    }

    /** "PV no [Draft] - JO-1, JO-2" for the analysis forms; the job order no. for older requests. */
    public function getVoucherDisplayAttribute(): ?string
    {
        $voucher = $this->productionVoucher;
        if ($voucher) {
            $jobOrders = $voucher->jobOrders()->pluck('job_order_no')->implode(', ');
            return $voucher->prod_no . ($voucher->is_draft ? ' [Draft]' : '') . ($jobOrders ? ' - ' . $jobOrders : '');
        }

        return $this->jobOrder?->job_order_no;
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

    /**
     * Set a completed request back to pending when no analysis is linked to it anymore
     * (e.g. after its analysis was deleted).
     */
    public static function revertToPendingIfUnused(?int $requestId): void
    {
        if (!$requestId) {
            return;
        }

        $request = self::find($requestId);
        if (!$request || $request->status !== 'completed') {
            return;
        }

        $stillUsed = ProductionAnalysis::where('analysis_request_id', $requestId)->exists()
            || ProductionMachineAnalysis::where('analysis_request_id', $requestId)->exists();

        if (!$stillUsed) {
            $request->update(['status' => 'pending']);
        }
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
