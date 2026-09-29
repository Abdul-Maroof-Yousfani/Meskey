<?php

namespace App\Models\Sales;

use App\Models\Acl\Company;
use App\Models\Master\ArrivalLocation;
use App\Models\Master\ArrivalSubLocation;
use App\Models\Procurement\Store\FactoryLocation;
use App\Models\Procurement\Store\Location;
use App\Models\Procurement\Store\SectionLocation;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PreSaleInspection extends Model
{
    use HasFactory;

    protected $table = 'pre_sale_inspections';
    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $casts = [
        'locations' => 'array',
        'factories' => 'array',
        'sections' => 'array',
        'date' => 'date',
    ];

    public function items()
    {
        return $this->hasMany(PreSaleInspectionItem::class, 'pre_sale_inspection_id');
    }

    public function item()
    {
        return $this->belongsTo(Product::class, 'item_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function arrivalLocation()
    {
        return $this->belongsTo(ArrivalLocation::class, 'arrival_location_id');
    }

    public function arrivalSubLocation()
    {
        return $this->belongsTo(ArrivalSubLocation::class, 'arrival_sub_location_id');
    }

    public function locations()
    {
        return $this->morphMany(Location::class, 'locationable');
    }

    public function factories()
    {
        return $this->morphMany(FactoryLocation::class, 'factoryable');
    }

    public function sections()
    {
        return $this->morphMany(SectionLocation::class, 'sectionable');
    }

    public function locationModels()
    {
        return $this->morphMany(Location::class, 'locationable');
    }

    public function factoryModels()
    {
        return $this->morphMany(FactoryLocation::class, 'factoryable');
    }

    public function sectionModels()
    {
        return $this->morphMany(SectionLocation::class, 'sectionable');
    }

    public function salesInquiries()
    {
        return $this->hasMany(SalesInquiry::class, 'pre_sale_inspection_id');
    }
}
