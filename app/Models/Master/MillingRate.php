<?php

namespace App\Models\Master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MillingRate extends Model
{
    use HasFactory;

    protected $fillable = [
        'location_id',
        'sublocation_id',
        'plant_id',
        'product_id',
        'title',
        'from_date',
        'to_date',
        'description',
        'status',
        'company_id',
    ];

    protected $casts = [
        'from_date' => 'date',
        'to_date' => 'date',
    ];

    public function location()
    {
        return $this->belongsTo(CompanyLocation::class, 'location_id');
    }

    public function subLocation()
    {
        return $this->belongsTo(ArrivalLocation::class, 'sublocation_id');
    }

    public function plant()
    {
        return $this->belongsTo(Plant::class, 'plant_id');
    }

    public function product()
    {
        return $this->belongsTo(\App\Models\Product::class, 'product_id');
    }

    public function company()
    {
        return $this->belongsTo(\App\Models\Acl\Company::class, 'company_id');
    }

    public function variables()
    {
        return $this->belongsToMany(Variable::class, 'milling_rate_variables', 'milling_rate_id', 'variable_id')
                    ->withPivot('value')
                    ->withTimestamps();
    }

    /**
     * Check if this rate's to_date has expired.
     */
    public function isExpired(): bool
    {
        if (!$this->to_date) {
            return false;
        }
        return $this->to_date->endOfDay()->isPast();
    }

    /**
     * Check if another active or future rate exists for the same commodity and plant.
     */
    public function hasSuccessor(): bool
    {
        return static::where('id', '!=', $this->id)
            ->where('location_id', $this->location_id)
            ->where('sublocation_id', $this->sublocation_id)
            ->where('plant_id', $this->plant_id)
            ->where('product_id', $this->product_id)
            ->when($this->company_id, fn($q) => $q->where('company_id', $this->company_id))
            ->where('to_date', '>=', now()->toDateString())
            ->exists();
    }

    /**
     * Get active milling rate for given product and plant on a specific date.
     */
    public static function getActiveRate($productId, $plantId, $date = null, $companyId = null)
    {
        $targetDate = $date ? \Carbon\Carbon::parse($date)->toDateString() : now()->toDateString();

        return static::where('product_id', $productId)
            ->where('plant_id', $plantId)
            ->when($companyId, fn($q) => $q->where('company_id', $companyId))
            ->where('status', 1)
            ->where('from_date', '<=', $targetDate)
            ->where('to_date', '>=', $targetDate)
            ->latest('from_date')
            ->first();
    }
}
