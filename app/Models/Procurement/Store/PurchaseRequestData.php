<?php

namespace App\Models\Procurement\Store;

use App\Models\Category;
use App\Models\Product;
use App\Traits\HasApproval;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseRequestData extends Model
{
    use HasFactory, HasApproval;

    protected $fillable = [
        'purchase_request_id',
        'category_id',
        'item_id',
        'qty',
        'approved_qty',
        'min_weight',
        'color',
        'construction_per_square_inch',
        'size',
        'stitching',
        'printing_sample',
        'remarks',
        'quotation_status',
        'am_approval_status',
        'po_status',
        'status',
        'brand_id',
        'micron',
        "is_single_job_order"
    ];

    protected $casts = [
        'qty' => 'decimal:2',
        'quotation_status' => 'integer',
        'po_status' => 'integer',
        'status' => 'boolean',
    ];

    protected $guarded = [];

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

    public function JobOrder()
    {
        return $this->hasMany(PurchaseAgainstJobOrder::class);
    }

    public function purchase_request()
    {
        return $this->belongsTo(PurchaseRequest::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function item()
    {
        return $this->belongsTo(Product::class, 'item_id');
    }

    public function purchase_quotation_data()
    {
        return $this->hasMany(PurchaseQuotationData::class, 'purchase_request_data_id');
    }

    public function purchase_order_data()
    {
        return $this->hasMany(PurchaseOrderData::class, 'purchase_request_data_id');
    }

    public function approved_purchase_quotation()
    {
        return $this->hasOne(PurchaseQuotationData::class, 'purchase_request_data_id')
            ->where('am_approval_status', 'approved');
    }

    public function approval()
    {
        return $this->hasMany(PurchaseItemApprove::class, 'purchase_request_data_id');
    }
}
