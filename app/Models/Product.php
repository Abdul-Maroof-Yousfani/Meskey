<?php

namespace App\Models;

use App\Models\Acl\Company;
use App\Models\Master\ExportProductSlab;
use App\Models\Master\ProductSlab;
use App\Models\Master\QcReliefParameter;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    // Fillable attributes for mass assignment
    protected $fillable = [
        'company_id',
        'parent_id',
        'category_id',
        'account_id',
        'bag_weight_for_purchasing',
        'unit_of_measure_id',
        'product_type',
        'description',
        'unique_no',
        'bardcode',
        'status',
        'image',
        'price',
        'name',
        'is_bag',
        'product_category_flags',
    ];

    // Relationships
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function unitOfMeasure()
    {
        return $this->belongsTo(UnitOfMeasure::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function slabs()
    {
        return $this->hasMany(ProductSlab::class)->generalEnabled();
    }

    public function exportSlabs()
    {
        return $this->hasMany(ExportProductSlab::class)->exportEnabled();
    }

    public function reliefParameters()
    {
        return $this->hasMany(QcReliefParameter::class);
    }

    public function children()
    {
        return $this->hasMany(Product::class, 'parent_id');
    }

    public function parent()
    {
        return $this->belongsTo(Product::class, 'parent_id');
    }

    protected static function booted()
    {
        static::observe(\App\Observers\UniversalAuditObserver::class);
    }

    public function auditIdentifier(): string
    {
        return (string) ($this->name ?: "#{$this->id}");
    }

    public static function auditLogConfig(): array
    {
        return [
            'title' => 'Product Audit Logs',
            'subtitle' => function ($model) {
                $parts = ["Product: <strong>" . e($model->name) . "</strong>"];
                if ($model->unique_no) $parts[] = 'Code: ' . e($model->unique_no);
                if ($model->product_type) {
                    $typeMap = [
                        'raw_material'  => 'Raw Material',
                        'finish_good'   => 'Finish Good',
                        'general_items' => 'General Items',
                    ];
                    $parts[] = 'Type: ' . ($typeMap[$model->product_type] ?? ucwords(str_replace('_', ' ', $model->product_type)));
                }
                return implode(' | ', $parts);
            },
            'empty_message' => 'No changes or updates have been recorded for this product yet.',
            'field_labels' => [
                'name'                      => 'Product Name',
                'code'                      => 'Product Code',
                'unique_no'                 => 'Product Code #',
                'product_type'              => 'Product Type',
                'product_category_flags'    => 'Sort Type',
                'category_id'               => 'Category',
                'parent_id'                 => 'Parent Product',
                'unit_of_measure_id'        => 'Unit of Measure',
                'account_id'                => 'Inventory Account',
                'bag_weight_for_purchasing' => 'Bag Weight for Purchasing',
                'bardcode'                  => 'Barcode',
                'price'                     => 'Price',
                'description'               => 'Description',
                'status'                    => 'Status',
                'is_bag'                    => 'Bag Type',
            ],
            'skip_keys' => [
                'image', 'company_id', 'deleted_at',
            ],
            'relation_resolvers' => [
                'parent_id' => function ($val) {
                    $p = \App\Models\Product::find($val);
                    return $p ? $p->name : ($val ? "Product #{$val}" : '-');
                },
                'category_id' => function ($val) {
                    $c = \App\Models\Category::find($val);
                    return $c ? $c->name : ($val ? "Category #{$val}" : '-');
                },
                'unit_of_measure_id' => function ($val) {
                    $u = \App\Models\UnitOfMeasure::find($val);
                    return $u ? $u->name : ($val ? "UOM #{$val}" : '-');
                },
                'account_id' => function ($val) {
                    $acc = \App\Models\Master\Account\Account::find($val);
                    return $acc ? "{$acc->name} [{$acc->unique_no}]" : ($val ? "Account #{$val}" : '-');
                },
                'product_category_flags' => function ($val) {
                    $map = [
                        'head' => 'Head Product',
                        'b2'   => 'B2 Product',
                        'by'   => 'By Product',
                    ];
                    return $map[$val] ?? ucfirst($val);
                },
                'product_type' => function ($val) {
                    $map = [
                        'raw_material'  => 'Raw Material',
                        'finish_good'   => 'Finish Good',
                        'general_items' => 'General Items',
                    ];
                    return $map[$val] ?? ucwords(str_replace('_', ' ', $val));
                },
                'is_bag' => function ($val) {
                    return ($val == 1 || $val === '1' || $val === true) ? 'Bag' : 'Not Bag';
                },
                'status' => function ($val) {
                    return is_string($val) ? ucfirst($val) : $val;
                },
            ],
        ];
    }
}
