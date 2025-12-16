<?php

namespace App\Models\Export;

use App\Models\Acl\Company;
use App\Models\ExportOrderSpecification;
use App\Models\Master\Broker;
use App\Models\Master\CompanyLocation;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ExportOrder extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'company_location_ids' => 'array',
        'arrival_location_ids' => 'array',
        'arrival_sub_location_ids' => 'array',
    ];

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
