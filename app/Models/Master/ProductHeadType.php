<?php

namespace App\Models\Master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductHeadType extends Model
{
    use HasFactory;

    protected $table = 'product_head_types';

    protected $fillable = [
        'company_id',
        'name',
        'slug',
        'sort_order',
        'status',
    ];

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Default fallbacks if table is empty or being initialized.
     */
    public static function defaultTypes(): array
    {
        return [
            [
                'name'       => 'Head Product',
                'slug'       => 'head',
                'sort_order' => 1,
                'status'     => 'active',
            ],
            [
                'name'       => 'B2 Product',
                'slug'       => 'b2',
                'sort_order' => 2,
                'status'     => 'active',
            ],
            [
                'name'       => 'By Product',
                'slug'       => 'by',
                'sort_order' => 3,
                'status'     => 'active',
            ],
        ];
    }

    /**
     * Get all active types, auto-seeding defaults if none exist.
     */
    public static function getActiveTypes($companyId = null)
    {
        $query = static::active()
            ->when($companyId, fn($q) => $q->where(fn($sub) => $sub->where('company_id', $companyId)->orWhereNull('company_id')))
            ->orderBy('sort_order')
            ->orderBy('id');

        $types = $query->get();

        if ($types->isEmpty()) {
            foreach (static::defaultTypes() as $def) {
                static::create($def);
            }
            $types = $query->get();
        }

        return $types;
    }
}
