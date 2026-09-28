<?php

namespace App\Models\Master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductSlabType extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'name',
        'description',
        'calculation_base_type',
        'qc_symbol',
        'status',
        'for_general_item',
        'order_by',
    ];

    public function getOrderByAttribute()
    {
        return $this->attributes['order_by'] ?? null;
    }

    public function setOrderByAttribute($value)
    {
        $this->attributes['order_by'] = $value;
    }

    public function slabs()
    {
        return $this->hasMany(ProductSlab::class);
    }

    /**
     * Scope a query to sort records by order_by ascending (with 0 and null at bottom), then by id ascending.
     */
    public function scopeOrdered($query)
    {
        return $query->orderByRaw('CASE WHEN order_by IS NULL OR order_by = 0 THEN 1 ELSE 0 END ASC, order_by ASC, id ASC');
    }

    public function scopeOrderByCustom($query)
    {
        return $this->scopeOrdered($query);
    }

    /**
     * Get ProductSlabType records based on Tareeqa A (Tickets -> Products -> Slabs -> ProductSlabTypes).
     * Filters by location and/or commodity, respecting user location permissions.
     *
     * @param mixed $locationId (int|string|array|null)
     * @param mixed $commodityId (int|string|array|null)
     * @param string $ticketType ('arrival'|'purchase')
     * @return \Illuminate\Database\Eloquent\Collection|\Illuminate\Support\Collection
     */
    public static function getForArrivalReport($locationId = null, $commodityId = null, $ticketType = 'arrival')
    {
        $locationIds = is_array($locationId)
            ? array_values(array_filter($locationId, fn($v) => !is_null($v) && $v !== ''))
            : (!is_null($locationId) && $locationId !== '' ? [$locationId] : []);

        $commodityIds = is_array($commodityId)
            ? array_values(array_filter($commodityId, fn($v) => !is_null($v) && $v !== ''))
            : (!is_null($commodityId) && $commodityId !== '' ? [$commodityId] : []);

        // Apply user location permissions if user is authenticated and not super-admin
        if (auth()->check() && auth()->user()->user_type !== 'super-admin') {
            $allowedLocations = [];
            try {
                if (function_exists('getUserCurrentCompanyLocations')) {
                    $allowedLocations = getUserCurrentCompanyLocations() ?: [];
                }
            } catch (\Throwable $e) {
                $allowedLocations = [];
            }

            if (!empty($locationIds)) {
                $locationIds = array_values(array_intersect($locationIds, $allowedLocations));
                if (empty($locationIds)) {
                    return collect([]);
                }
            } else {
                $locationIds = $allowedLocations;
            }
        }

        if ($ticketType === 'purchase') {
            $ticketQuery = \App\Models\PurchaseTicket::with('purchaseOrder');

            if (!empty($locationIds)) {
                $ticketQuery->where(function ($q) use ($locationIds) {
                    $q->whereHas('purchaseOrder', function ($po) use ($locationIds) {
                        $po->whereIn('company_location_id', $locationIds);
                    });
                    if (auth()->check() && auth()->user()->user_type !== 'super-admin') {
                        $q->orWhereNull('purchase_order_id');
                    }
                });
            }

            if (!empty($commodityIds)) {
                $ticketQuery->where(function ($q) use ($commodityIds) {
                    $q->whereIn('purchase_tickets.product_id', $commodityIds)
                      ->orWhereIn('purchase_tickets.qc_product', $commodityIds)
                      ->orWhereHas('purchaseOrder', function ($po) use ($commodityIds) {
                          $po->whereIn('product_id', $commodityIds)
                             ->orWhereIn('qc_product', $commodityIds);
                      });
                });
            }

            $tickets = $ticketQuery->get();
            $productIds = [];
            foreach ($tickets as $t) {
                if ($t->product_id) $productIds[] = $t->product_id;
                if ($t->qc_product) $productIds[] = $t->qc_product;
                if ($t->purchaseOrder?->product_id) $productIds[] = $t->purchaseOrder->product_id;
                if ($t->purchaseOrder?->qc_product) $productIds[] = $t->purchaseOrder->qc_product;
            }
            $productIds = array_unique(array_filter($productIds));
        } else {
            $ticketQuery = \App\Models\Arrival\ArrivalTicket::query();

            if (!empty($locationIds)) {
                $ticketQuery->whereIn('location_id', $locationIds);
            }

            if (!empty($commodityIds)) {
                $ticketQuery->where(function ($q) use ($commodityIds) {
                    $q->whereIn('product_id', $commodityIds)
                      ->orWhereIn('qc_product', $commodityIds);
                });
            }

            $distinctProducts = $ticketQuery->select(['product_id', 'qc_product'])->distinct()->get();
            $productIds = array_unique(array_filter(array_merge(
                $distinctProducts->pluck('product_id')->toArray(),
                $distinctProducts->pluck('qc_product')->toArray()
            )));
        }

        if (!empty($commodityIds)) {
            $targetProductIds = array_intersect($commodityIds, $productIds);
            if (empty($targetProductIds)) {
                $targetProductIds = $commodityIds;
            }
        } else {
            $targetProductIds = $productIds;
        }

        if (empty($targetProductIds)) {
            // Fallback: If no location filter was applied at all, return all ordered slabs
            if (empty($locationIds)) {
                return self::ordered()->get();
            }
            return collect([]);
        }

        return self::whereHas('slabs', function ($q) use ($targetProductIds) {
            $q->whereIn('product_id', $targetProductIds);
        })->ordered()->get();
    }

    public static function getForReport($locationId = null, $commodityId = null, $ticketType = 'arrival')
    {
        return self::getForArrivalReport($locationId, $commodityId, $ticketType);
    }
}
