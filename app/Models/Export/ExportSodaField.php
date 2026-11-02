<?php

namespace App\Models\Export;

use App\Traits\HasApproval;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExportSodaField extends Model
{
    use HasFactory, HasApproval;
    protected $guarded = ['id'];

    protected static function booted()
    {
        static::updating(function ($model) {
            $originalStatus = strtolower($model->getOriginal('am_approval_status') ?? '');
            $newStatus = strtolower($model->am_approval_status ?? '');
            if ($model->isDirty('am_approval_status')) {
                if (in_array($originalStatus, ['approved', 'rejected'])) {
                    throw new \Exception("Export Sauda is already {$originalStatus} and status cannot be changed.");
                }
                if ($originalStatus === 'reverted' && $newStatus !== 'pending') {
                    throw new \Exception("Export Sauda is reverted and cannot be {$newStatus} directly. It must be updated to pending first.");
                }
            }

            $changes = $model->getDirty();
            if (!empty($changes) && !isset($changes['am_change_made'])) {
                if ($model->getAttribute('am_change_made') !== null) {
                    $model->am_change_made = 1;
                }
            }
        });

        static::deleting(function ($model) {
            $status = strtolower($model->am_approval_status ?? '');
            if (in_array($status, ['approved', 'rejected'])) {
                throw new \Exception("Export Sauda is already {$status} and cannot be deleted.");
            }
        });
    }

    protected $casts = [
        'commission_percentage' => 'float',
        'commission_amount_per_ton' => 'float',
        'commission' => 'float',
        'shipment_date_from' => 'date',
        'shipment_date_to' => 'date',
    ];
    
    protected $appends = ['total_qty_mt'];

    public function getTotalQtyMtAttribute()
    {
        return $this->packingItems()->sum('metric_tons') ?? 0;
    }

    public function buyer()
    {
        return $this->belongsTo(\App\Models\Master\Customer::class , 'buyer_id');
    }

    public function product()
    {
        return $this->belongsTo(\App\Models\Product::class , 'product_id');
    }

    public function quotation()
    {
        return $this->belongsTo(\App\Models\Export\Quotation::class, 'quotation_id');
    }

    public function quotationBySoda()
    {
        return $this->hasOne(\App\Models\Export\Quotation::class, 'export_soda_id');
    }

    public function getLinkedQuotationIdAttribute()
    {
        return $this->quotation_id ?? $this->quotationBySoda?->id;
    }

    public function packingItems()
    {
        return $this->hasMany(ExportSodaPackingItem::class , 'export_soda_id');
    }

    public function incoterm()
    {
        return $this->belongsTo(\App\Models\Export\IncoTerm::class , 'incoterm_id');
    }

    public function modeOfTerm()
    {
        return $this->belongsTo(\App\Models\Export\ModeOfTerm::class , 'mode_of_term_id');
    }

    public function company()
    {
        return $this->belongsTo(\App\Models\Master\Company::class , 'company_id');
    }
}
