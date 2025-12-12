<?php

namespace App\Models\Sales;

use App\Models\Procurement\Store\FactoryLocation;
use App\Models\Procurement\Store\Location;
use App\Models\Procurement\Store\SectionLocation;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasApproval;

class SalesInquiry extends Model
{
    use HasFactory, HasApproval;
    protected $table = "sales_inquiries";
    protected $guarded = ["id", "created_at", "updated_at"];

    public function sales_inquiry_data()
    {
        return $this->hasMany(SalesInquiryData::class, "inquiry_id", "id");
    }

    public function locations() {
        return $this->morphMany(Location::class, 'locationable');
    }

    public function factories() {
        return $this->morphMany(FactoryLocation::class, 'factoryable');
    }

    public function sections() {
        return $this->morphMany(SectionLocation::class, 'sectionable');
    }

    public function sale_order() {
        return $this->hasOne(SalesOrder::class, "inquiry_id", "id");
    }
}
