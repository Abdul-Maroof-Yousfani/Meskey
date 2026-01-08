<?php

namespace App\Models\Production\JobOrder;
use App\Models\Master\CropYear;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Master\{InspectionCompany, FumigationCompany, ArrivalLocation,CompanyLocation};
use App\Models\Product;
use App\Models\Acl\Company;
use App\Models\User;
class JobOrder extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'job_order_no',
        'job_order_date',
        'company_location_id',
        'ref_no',
        'attention_to',
        'product_id',
        'remarks',
        'order_description',
        'inspection_company_id',
    // 'fumigation_company_id',
        'delivery_date',
        'loading_date',
        'arrival_locations',
        'packing_description',
        'crop_year_id',
        'other_specifications',
        'company_id'
    ];

    protected $casts = [
        'inspection_company_id' => 'array',
        'fumigation_company_id' => 'array',
        'arrival_locations' => 'array',
        'job_order_date' => 'date',
        // 'delivery_date' => 'date',
        'loading_date' => 'date'
    ];


    // public function companyLocation()
    // {
    //     return $this->belongsTo(CompanyLocation::class, 'company_location_id');
    // }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
    public function cropYear()
    {
        return $this->belongsTo(CropYear::class);
    }
    public function attentionUsers()
    {
        return \App\Models\User::whereIn('id', $this->attention_to ?? []);
    }
    public function inspectionCompanies()
    {
        return InspectionCompany::whereIn('id', $this->inspection_company_id ?? []);
    }

    // public function fumigationCompanies()
    // {
    //     return FumigationCompany::whereIn('id', $this->fumigation_company_id ?? []);
    // }

    public function arrivalLocationRecords()
    {
        return ArrivalLocation::whereIn('id', $this->arrival_locations ?? []);
    }

    public function packingItems()
    {
        return $this->hasMany(JobOrderPackingItem::class);
    }

    public function productionOutputs()
    {
        return $this->hasMany(\App\Models\Production\ProductionOutput::class);
    }
    // New relationship for specifications
    public function specifications()
    {
        return $this->hasMany(JobOrderSpecification::class);
    }

    public function company()
    {
        return $this->belongsTo(\App\Models\Acl\Company::class);
    }

    // Auto calculate total fields
    public function getTotalBagsAttribute()
    {
        return $this->packingItems->sum('total_bags');
    }

    public function getTotalKgsAttribute()
    {
        return $this->packingItems->sum('total_kgs');
    }

    public function getTotalMetricTonsAttribute()
    {
        return $this->packingItems->sum('metric_tons');
    }

    public function getTotalContainersAttribute()
    {
        return $this->packingItems->sum('no_of_containers');
    }

      // Get all company locations from packing items (sorted and unique)
      public function getCompanyLocationsAttribute()
      {
          return CompanyLocation::whereIn('id', $this->packingItems->pluck('company_location_id')->unique()->toArray())
              ->orderBy('name')
              ->get();
      }
  
      // Get company locations as comma separated string
      public function getCompanyLocationsStringAttribute()
      {
        // company_locations_string
          $locations = $this->company_locations;
          return $locations->pluck('name')->implode(', ');
      }

      public function rawMaterialQcs()
      {
        return $this->hasMany(JobOrderRawMaterialQc::class);
      }

      public function containerProtectionItems()
      {
        return $this->belongsToMany(Product::class, 'job_order_container_protection_items', 'job_order_id', 'product_id')
            ->withPivot('quantity_per_container')
            ->withTimestamps();
      }
}