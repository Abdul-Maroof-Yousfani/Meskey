<?php

namespace App\Models\Production;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Acl\Company;
use App\Models\Master\Plant;
use App\Models\Production\ProductionVoucher;
use App\Models\User;

class PlantBreakdown extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'date',
        'plant_id',
        'production_voucher_id',
        'user_id',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function plant()
    {
        return $this->belongsTo(Plant::class);
    }

    public function productionVoucher()
    {
        return $this->belongsTo(ProductionVoucher::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(PlantBreakdownItem::class);
    }
}
