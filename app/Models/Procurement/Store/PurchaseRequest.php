<?php

namespace App\Models\Procurement\Store;

use App\Models\Master\CompanyLocation;
use App\Traits\HasApproval;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseRequest extends Model
{
    use HasFactory, HasApproval;

    protected $fillable = [
        'purchase_request_no',
        'company_id',
        'purchase_date',
        'location_id',
        'reference_no',
        'description',
        'purchase_request_status',
        'approved_user_name',
        'status',
        'po_status',
        'am_approval_status',
    ];

    protected static function booted()
    {
        static::updating(
            function ($model) {
                $changes = $model->getDirty();
                $changedColumns = [];

                foreach ($changes as $key => $newValue) {
                    if ($key !== "am_change_made") {
                        $oldValue = $model->getOriginal($key);
                        $changedColumns[$key] = [
                            'old' => $oldValue,
                            'new' => $newValue,
                        ];
                    }
                }

                if (!empty($changedColumns)) {
                    if ($model->getAttribute('am_change_made') !== null) {
                        $model->am_change_made = 1;
                    }
                }
            }
        );
    }

    public function location()
    {
        return $this->belongsTo(CompanyLocation::class, 'location_id');
    }

    public function PurchaseData()
    {
        return $this->hasMany(PurchaseRequestData::class);
    }

    public function quotation()
    {
        return $this->hasOne(PurchaseQuotation::class, 'purchase_request_id');
    }
}
