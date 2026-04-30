<?php

namespace App\Models\Export;

use App\Models\Procurement\Store\FactoryLocation;
use App\Models\Procurement\Store\SectionLocation;
use App\Models\Sales\ReceivingRequest;
use App\Traits\HasApproval;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExportDeliveryChallan extends Model
{
    use HasFactory, HasApproval;

    protected $table = 'delivery_challans';

    protected $fillable = [
        'customer_id',
        'reference_number',
        'dispatch_date',
        'dc_no',
        'sauda_type',
        'location_id',
        'arrival_id',
        'company_id',
        'remarks',
        'labour',
        'labour_amount',
        'transporter',
        'transporter_id',
        'transporter_amount',
        'inhouse-weighbridge',
        'weighbridge-amount',
        'subarrival_id',
        'created_by_id',
        'section_id',
        'labour_rate',
        'labour_status',
        'am_approval_status',
        'am_change_made',
    ];

    protected static function booted()
    {
        static::creating(function ($model) {
            $model->type = 'export_delivery_challan';
        });

        static::updating(function ($model) {
            $model->type = 'export_delivery_challan';
        });

        static::addGlobalScope('export_type', function ($builder) {
            $builder->where('delivery_challans.type', 'export_delivery_challan');
        });
    }

    public function delivery_challan_data()
    {
        return $this->hasMany(ExportDeliveryChallanData::class, 'delivery_challan_id', 'id');
    }

    public function delivery_order()
    {
        return $this->belongsToMany(ExportDeliveryOrder::class, 'delivery_challan_delivery_order', 'delivery_challan_id', 'delivery_order_id');
    }

    public function receivingRequest()
    {
        return $this->hasOne(ReceivingRequest::class, 'delivery_challan_id');
    }

    public function customer()
    {
        return $this->belongsTo(\App\Models\Master\Customer::class, 'customer_id');
    }

    public function billOfLading()
    {
        return $this->hasOne(BillOfLading::class, 'export_delivery_challan_id');
    }

    public function factories()
    {
        return $this->morphMany(FactoryLocation::class, 'factoryable');
    }

    public function sections()
    {
        return $this->morphMany(SectionLocation::class, 'sectionable');
    }

    public function createdBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by_id');
    }
}
