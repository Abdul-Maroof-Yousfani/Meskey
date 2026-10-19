<?php

namespace App\Models\Master;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ArrivalQcReliefParameter extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'arrival_qc_relief_parameters';

    protected $fillable = [
        'company_id',
        'product_id',
        'location_id',
        'start_date',
        'end_date',
        'slab_type_id',
        'parameter_name',
        'relief_type',
        'from_range',
        'to_range',
        'fixed_value',
        'relief_value',
        'relief_percentage',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'from_range' => 'decimal:2',
        'to_range' => 'decimal:2',
        'fixed_value' => 'decimal:2',
        'relief_value' => 'decimal:2',
        'relief_percentage' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function setCreatedByAttribute($value)
    {
        if (is_numeric($value)) {
            $this->attributes['created_by'] = (int)$value;
        } elseif (auth()->check() && is_numeric(auth()->user()->id)) {
            $this->attributes['created_by'] = (int)auth()->user()->id;
        } else {
            $this->attributes['created_by'] = null;
        }
    }

    public function setUpdatedByAttribute($value)
    {
        if (is_numeric($value)) {
            $this->attributes['updated_by'] = (int)$value;
        } elseif (auth()->check() && is_numeric(auth()->user()->id)) {
            $this->attributes['updated_by'] = (int)auth()->user()->id;
        } else {
            $this->attributes['updated_by'] = null;
        }
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function location()
    {
        return $this->belongsTo(CompanyLocation::class, 'location_id');
    }

    public function slabType()
    {
        return $this->belongsTo(ProductSlabType::class, 'slab_type_id');
    }

    /**
     * Resolve the active relief rule for a given product, location, date, parameter, and measured value.
     *
     * @param int $productId
     * @param int|null $locationId
     * @param string|\DateTimeInterface|null $date
     * @param string $parameterName
     * @param float $actualValue
     * @return array|null
     */
    public static function getReliefRule($productId, $locationId, $date, $parameterName, $actualValue)
    {
        $query = static::where('product_id', $productId)
            ->where('is_active', true)
            ->where('parameter_name', $parameterName)
            ->where('from_range', '<=', $actualValue)
            ->where('to_range', '>=', $actualValue);

        if ($date) {
            $dateString = is_string($date) ? date('Y-m-d', strtotime($date)) : $date->format('Y-m-d');
            $query->where('start_date', '<=', $dateString)
                  ->where('end_date', '>=', $dateString);
        }

        $rules = $query->get();

        // Location-specific rule takes precedence over "All Locations" (null location_id)
        $rule = $rules->firstWhere('location_id', $locationId) ?: $rules->firstWhere('location_id', null);

        if (!$rule) {
            return null;
        }

        $reliefValue = (float)($rule->relief_value ?: $rule->fixed_value);

        if ($rule->relief_type === 'minus_amount') {
            $finalValue = max(0, (float)$actualValue - $reliefValue);
        } else {
            // 'fixed'
            $finalValue = $reliefValue;
        }

        return [
            'rule' => $rule,
            'relief_type' => $rule->relief_type,
            'original_value' => (float)$actualValue,
            'relief_value' => $reliefValue,
            'final_value' => $finalValue,
        ];
    }
}

