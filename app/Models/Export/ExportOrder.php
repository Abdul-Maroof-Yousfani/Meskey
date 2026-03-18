<?php

namespace App\Models\Export;

use App\Models\Acl\Company;
use App\Models\ExportOrderSpecification;
use App\Models\Master\Broker;
use App\Models\Master\CompanyLocation;
use App\Models\Product;
use App\Models\User;
use App\Traits\HasApproval;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ExportOrder extends Model
{
    use HasApproval, HasFactory, SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'company_location_ids' => 'array',
        'arrival_location_ids' => 'array',
        'arrival_sub_location_ids' => 'array',
    ];

    protected static function booted()
    {
        static::updating(
            function ($model) {
                $changes = $model->getDirty();
                $changedColumns = [];

                foreach ($changes as $key => $newValue) {
                    if ($key !== 'am_change_made') {
                        $oldValue = $model->getOriginal($key);
                        $changedColumns[$key] = [
                            'old' => $oldValue,
                            'new' => $newValue,
                        ];
                    }
                }

                if (! empty($changedColumns)) {
                    if ($model->getAttribute('am_change_made') !== null) {
                        $model->am_change_made = 1;
                    }
                }
            }
        );
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function specifications()
    {
        return $this->hasMany(ExportOrderSpecification::class);
    }

    public function broker()
    {
        return $this->belongsTo(Broker::class);
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }

    public function packingItems()
    {
        return $this->hasMany(ExportOrderPackingItem::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function modeOfTerm()
    {
        return $this->belongsTo(ModeOfTerm::class, 'mode_of_term_id');
    }

    public function buyer()
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function proforma()
    {
        return $this->hasOne(Proforma::class);
    }

    // Auto calculate total fields
    // public function getTotalBagsAttribute()
    // {
    //     return $this->packingItems->sum('total_bags');
    // }

    // public function getTotalKgsAttribute()
    // {
    //     return $this->packingItems->sum('total_kgs');
    // }

    // public function getTotalMetricTonsAttribute()
    // {
    //     return $this->packingItems->sum('metric_tons');
    // }

    // public function getTotalContainersAttribute()
    // {
    //     return $this->packingItems->sum('no_of_containers');
    // }

    //   // Get all company locations from packing items (sorted and unique)
    //   public function getCompanyLocationsAttribute()
    //   {
    //       return CompanyLocation::whereIn('id', $this->packingItems->pluck('company_location_id')->unique()->toArray())
    //           ->orderBy('name')
    //           ->get();
    //   }

    //   // Get company locations as comma separated string
    //   public function getCompanyLocationsStringAttribute()
    //   {
    //     // company_locations_string
    //       $locations = $this->company_locations;
    //       return $locations->pluck('name')->implode(', ');
    //   }
}
