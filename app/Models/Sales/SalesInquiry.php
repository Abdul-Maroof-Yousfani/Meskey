<?php

namespace App\Models\Sales;

use App\Models\Procurement\Store\FactoryLocation;
use App\Models\Procurement\Store\Location;
use App\Models\Procurement\Store\SectionLocation;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasApproval;
use App\Traits\PreventsUpdateWhenApproved;

class SalesInquiry extends Model
{
    use HasFactory, HasApproval;
    protected $table = "sales_inquiries";
    protected $guarded = ["id", "created_at", "updated_at"];

    protected static function booted()
    {
        static::updating(function ($model) {
            $originalStatus = strtolower($model->getOriginal('am_approval_status') ?? '');
            $newStatus = strtolower($model->am_approval_status ?? '');
            if ($model->isDirty('am_approval_status')) {
                if (in_array($originalStatus, ['approved', 'rejected'])) {
                    throw new \Exception("Sales Inquiry record is already {$originalStatus} and status cannot be changed.");
                }
                if ($originalStatus === 'reverted' && !in_array($newStatus, ['pending', 'reverted'])) {
                    throw new \Exception("Sales Inquiry record is reverted and cannot be {$newStatus} directly. It must be updated to pending first.");
                }
            }
        });

        static::deleting(function ($model) {
            $status = strtolower($model->getOriginal('am_approval_status') ?? $model->am_approval_status ?? '');
            if (in_array($status, ['approved', 'rejected'])) {
                throw new \Exception("Sales Inquiry record is already {$status} and cannot be deleted.");
            }
        });
    }
    
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
