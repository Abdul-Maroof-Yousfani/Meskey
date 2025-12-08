<?php

namespace App\Models\Master;

use App\Models\Acl\Company;
use App\Models\City;
use App\Models\Production\JobOrder\JobOrder;
use App\Models\Production\JobOrder\JobOrderPackingItem;
use App\Models\User;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class CompanyLocation extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'city_id',
        'name',
        'truck_no_format',
        'code',
        'bank_charges_for_gate_buying',
        'description',
        'is_protected',
        'status',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (Auth::check()) {
                $model->created_by = auth()->user()->id;
                $model->updated_by = auth()->user()->id;
            }
        });

        static::updating(function ($model) {
            if (Auth::check()) {
                $model->updated_by = auth()->user()->id;
            }
        });

        static::deleting(function ($model) {
            if (Auth::check()) {
                $model->deleted_by = auth()->user()->id;
                $model->save(); // update deleted_by before soft delete
            }
        });
    }

    // 🔁 Relationships
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function arrivalLocations()
    {
        return $this->hasMany(ArrivalLocation::class);
    }
    public function jobOrder()
    {
        return $this->hasMany(JobOrder::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function deleter()
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    public function city()
    {
        return $this->belongsTo(City::class);
    }


    // Relationship with packing items
    public function packingItems()
    {
        return $this->hasMany(JobOrderPackingItem::class, 'company_location_id');
    }

    // Relationship with job orders through packing items
    public function jobOrders()
    {
        return $this->hasManyThrough(
            JobOrder::class,
            JobOrderPackingItem::class,
            'company_location_id', // Foreign key on packing_items table
            'id', // Foreign key on job_orders table
            'id', // Local key on company_locations table
            'job_order_id' // Local key on packing_items table
        );
    }

    // Get total kgs for a specific job order number in this location
    public function getTotalKgsForJobOrder($jobOrderID)
    {
        return $this->packingItems()
            ->whereHas('jobOrder', function ($query) use ($jobOrderID) {
                $query->where('id', $jobOrderID);
            })
            ->sum('total_kgs');
    }
}
