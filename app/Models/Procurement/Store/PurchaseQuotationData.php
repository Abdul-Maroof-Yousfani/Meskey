<?php

namespace App\Models\Procurement\Store;

use App\Models\Category;
use App\Models\Master\Supplier;
use App\Models\Product;
use App\Traits\HasApproval;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseQuotationData extends Model
{
    use HasFactory, HasApproval;

    protected $table = "purchase_quotation_data";
    protected $fillable = [
        'purchase_quotation_id',
        'purchase_request_data_id',
        'category_id',
        'item_id',
        'supplier_id',
        'qty',
        'rate',
        'am_approval_status',
        'total',
        'remarks',
        'quotation_status',
        'po_status',
        'status',
    ];

    protected $casts = [
        'qty' => 'decimal:2',
        'rate' => 'decimal:2',
        'total' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
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

    public function purchase_quotation()
    {
        return $this->belongsTo(PurchaseQuotation::class, 'purchase_quotation_id', 'id');
    }
    

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function item()
    {
        return $this->belongsTo(Product::class, 'item_id');
    }

    public function approval()
    {
        return $this->hasMany(PurchaseItemApprove::class, 'purchase_request_data_id');
    }
}
