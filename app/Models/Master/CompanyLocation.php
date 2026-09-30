<?php

namespace App\Models\Master;

use App\Models\Acl\Company;
use App\Models\City;
use App\Models\Production\JobOrder\JobOrder;
use App\Models\Production\JobOrder\JobOrderPackingItem;
use App\Models\User;
use App\Models\Master\ProductionPhase;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class CompanyLocation extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'city_id',
        'name',
        'truck_no_format',
        'code',
        'bank_charges_for_gate_buying',
        'description',
        'production_phases',
        'is_protected',
        'status',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'production_phases' => 'array',
        'truck_no_format' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (Auth::check()) {
                $model->created_by = auth()->user()->id;
                $model->updated_by = auth()->user()->id;
            }
        });

        static::updating(function ($model) {
            if (Auth::check()) {
                $model->updated_by = auth()->user()->id;
            }
        });

        static::deleting(function ($model) {
            if (Auth::check()) {
                $model->deleted_by = auth()->user()->id;
                $model->save(); // update deleted_by before soft delete
            }
        });
    }

    // 🔁 Relationships
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function arrivalLocations()
    {
        return $this->hasMany(ArrivalLocation::class);
    }
    public function jobOrder()
    {
        return $this->hasMany(JobOrder::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function deleter()
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    public function city()
    {
        return $this->belongsTo(City::class);
    }


    // Relationship with packing items
    public function packingItems()
    {
        return $this->hasMany(JobOrderPackingItem::class, 'company_location_id');
    }

    // Relationship with job orders through packing items
    public function jobOrders()
    {
        return $this->hasManyThrough(
            JobOrder::class,
            JobOrderPackingItem::class,
            'company_location_id', // Foreign key on packing_items table
            'id', // Foreign key on job_orders table
            'id', // Local key on company_locations table
            'job_order_id' // Local key on packing_items table
        );
    }

    // Get total kgs for a specific job order number in this location
    public function getTotalKgsForJobOrder($jobOrderID)
    {
        return $this->packingItems()
            ->whereHas('jobOrder', function ($query) use ($jobOrderID) {
                $query->where('id', $jobOrderID);
            })
            ->sum('total_kgs');
    }

    /**
     * Default production phase IDs
     */
    public static function defaultProductionPhaseIds(): array
    {
        return ProductionPhase::active()->pluck('id')->toArray();
    }

    /**
     * Accessor for production_phases attribute: returns array of phase IDs (empty array if null)
     */
    public function getProductionPhasesAttribute($value)
    {
        if (is_null($value)) {
            return [];
        }

        $decoded = is_string($value) ? json_decode($value, true) : $value;
        if (!is_array($decoded) || empty($decoded)) {
            return [];
        }

        // If it was stored previously as array of objects, extract IDs
        if (is_array($decoded[0] ?? null)) {
            $ids = [];
            foreach ($decoded as $item) {
                if (!empty($item['enabled']) && !empty($item['key'])) {
                    $phase = ProductionPhase::findByKey($item['key']);
                    if ($phase) {
                        $ids[] = (int)$phase->id;
                    }
                }
            }
            return $ids;
        }

        return array_values(array_unique(array_filter(array_map('intval', $decoded))));
    }

    /**
     * Get ProductionPhase models assigned to this location
     */
    public function phases()
    {
        $ids = $this->production_phases ?? [];
        if (empty($ids)) {
            return ProductionPhase::whereRaw('1 = 0')->get();
        }

        return ProductionPhase::whereIn('id', $ids)->get();
    }

    /**
     * Return only active / enabled phases as collection
     */
    public function getActivePhases()
    {
        return $this->phases();
    }

    /**
     * Return keys of active / enabled phases (e.g. ['phase_1', 'phase_3'])
     */
    public function getActivePhaseKeys(): array
    {
        $ids = $this->production_phases ?? [];
        if (empty($ids)) {
            return [];
        }

        return ProductionPhase::whereIn('id', $ids)->pluck('key')->toArray();
    }

    /**
     * Check if a specific phase key or ID is enabled for this location
     */
    public function hasPhase($keyOrId): bool
    {
        if (is_numeric($keyOrId)) {
            return in_array((int)$keyOrId, (array)($this->production_phases ?? []), true);
        }

        return in_array((string)$keyOrId, $this->getActivePhaseKeys(), true);
    }

    /**
     * Get display label for a phase by its key or ID
     */
    public function getPhaseLabel(string $key, string $default = ''): string
    {
        $phase = ProductionPhase::findByKey($key);
        return $phase ? $phase->name : ($default ?: $key);
    }
}
