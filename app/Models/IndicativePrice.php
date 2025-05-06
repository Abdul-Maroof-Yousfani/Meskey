<?php

namespace App\Models;

use App\Models\Master\CompanyLocation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class IndicativePrice extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'product_id',
        'location_id',
        'type_id',
        'crop_year',
        'delivery_condition',
        'cash_rate',
        'cash_days',
        'credit_rate',
        'credit_days',
        'time',
        'others',
        'remarks',
        'created_by'
    ];

    protected $casts = [
        'crop_year' => 'integer',
        'cash_rate' => 'decimal:2',
        'credit_rate' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->time)) {
                $model->time = now()->format('H:i:s');
            }
        });
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function location()
    {
        return $this->belongsTo(CompanyLocation::class, 'location_id');
    }

    public function type()
    {
        return $this->belongsTo(SaudaType::class, 'type_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
