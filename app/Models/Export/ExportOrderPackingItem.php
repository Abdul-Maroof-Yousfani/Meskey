<?php

namespace App\Models\Export;

use App\Models\BagCondition;
use App\Models\BagPacking;
use App\Models\BagType;
use App\Models\Master\Brands;
use App\Models\Master\Color;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExportOrderPackingItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'export_order_id',
        // 'company_location_id',
        'brand_id',
        'bag_type_id',
        'bag_packing_id',
        'bag_condition_id',
        'bag_color_id',
        'bag_size',
        'metric_tons',
        'no_of_bags',
        'total_kgs',
        'stuffing_in_container',
        'no_of_containers',
        'rate',
        'rate_per_maund',
        'maunds',
        'stuffing_maunds',
        'amount',
        'amount_pkr',
    ];

    protected $casts = [
        'bag_size' => 'decimal:2',
        'metric_tons' => 'decimal:3',
        'maunds' => 'decimal:3',
        'stuffing_in_container' => 'decimal:3',
        'stuffing_maunds' => 'decimal:3',
        'total_kgs' => 'decimal:2',
        'rate' => 'decimal:2',
        'rate_per_maund' => 'decimal:2',
        'amount' => 'decimal:2',
        'amount_pkr' => 'decimal:2',
    ];

    /**
     * Auto calculations
     */
    protected static function booted()
    {
        static::saving(function ($item) {

            // Total KGs & Bags
            $item->total_kgs = $item->metric_tons * 1000;
            if ($item->bag_size > 0) {
                $item->no_of_bags = $item->total_kgs / $item->bag_size;
            }

            // Maunds from MTs (1 MT = 25 Maunds)
            $item->maunds = $item->metric_tons * 25;
            $item->stuffing_maunds = $item->stuffing_in_container * 25;

            // Rate Sync (if one is missing or for consistency)
            if ($item->rate > 0 && (!$item->rate_per_maund || $item->rate_per_maund == 0)) {
                $item->rate_per_maund = $item->rate / 25;
            } elseif ($item->rate_per_maund > 0 && (!$item->rate || $item->rate == 0)) {
                $item->rate = $item->rate_per_maund * 25;
            }

            // Amount (Rate per Ton)
            $item->amount = $item->metric_tons * $item->rate;

            $exportOrder = ExportOrder::find($item->export_order_id);

            // PKR conversion
            if ($exportOrder && $exportOrder->currency_rate > 0) {
                $item->amount_pkr = $item->amount * $exportOrder->currency_rate;
            }
        });
    }

    public function exportOrder()
    {
        return $this->belongsTo(ExportOrder::class);
    }

    public function brand()
    {
        return $this->belongsTo(Brands::class);
    }

    public function bagType()
    {
        return $this->belongsTo(BagType::class);
    }

    public function bagPacking()
    {
        return $this->belongsTo(BagPacking::class);
    }

    public function bagCondition()
    {
        return $this->belongsTo(BagCondition::class);
    }

    public function bagColor()
    {
        return $this->belongsTo(Color::class);
    }
}
