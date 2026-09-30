<?php

namespace App\Models\Sales;

use App\Models\Acl\Company;
use App\Models\Master\CompanyLocation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PreSaleInspection extends Model
{
    use HasFactory;

    protected $table = 'pre_sale_inspections';
    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $casts = [
        'date' => 'date',
    ];

    public function location()
    {
        return $this->belongsTo(CompanyLocation::class, 'location_id');
    }

    public function items()
    {
        return $this->hasMany(PreSaleInspectionItem::class, 'pre_sale_inspection_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function salesInquiries()
    {
        return $this->hasMany(SalesInquiry::class, 'pre_sale_inspection_id');
    }

    public function salesOrders()
    {
        return $this->hasMany(SalesOrder::class, 'pre_sale_inspection_id');
    }
}
