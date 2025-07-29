<?php

namespace App\Models\Master;

use App\Models\Acl\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class CompanyLocation extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'name',
        'truck_no_format',
        'code',
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
                $model->created_by = Auth::id();
                $model->updated_by = Auth::id();
            }
        });

        static::updating(function ($model) {
            if (Auth::check()) {
                $model->updated_by = Auth::id();
            }
        });

        static::deleting(function ($model) {
            if (Auth::check()) {
                $model->deleted_by = Auth::id();
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
}
