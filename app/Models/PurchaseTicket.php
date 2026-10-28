<?php

namespace App\Models;

use App\Models\Acl\Company;
use App\Models\Procurement\PaymentRequestData;
use App\Models\Procurement\PurchaseFreight;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseTicket extends Model
{
    use SoftDeletes;

    protected $table = 'purchase_tickets';

    protected $fillable = [
        'unique_no',
        'company_id',
        'product_id',
        'is_custom_qc',
        'qc_product',
        'purchase_order_id',
        'bag_weight',
        'bag_rate',
        'qc_status',
        'first_qc_status',
        'freight_status',
        'payment_request_status',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function qcProduct()
    {
        return $this->belongsTo(Product::class, 'qc_product');
    }

    public function purchaseOrder()
    {
        return $this->belongsTo(ArrivalPurchaseOrder::class, 'purchase_order_id');
    }

    public function paymentRequestData()
    {
        return $this->hasMany(PaymentRequestData::class, 'ticket_id');
    }

    public function purchaseFreight()
    {
        return $this->hasOne(PurchaseFreight::class, 'purchase_ticket_id');
    }

    public function purchaseSamplingRequests()
    {
        return $this->hasMany(PurchaseSamplingRequest::class, 'purchase_ticket_id');
    }

    public function lastInitialSampling()
    {
        return $this->hasOne(PurchaseSamplingRequest::class, 'purchase_ticket_id')->latestOfMany();
    }

    public function latestSampling()
    {
        return $this->hasOne(PurchaseSamplingRequest::class, 'purchase_ticket_id')->latestOfMany();
    }

    public function purchaseOrderLoadedQuantity()
    {
        return $this->hasOneThrough(
            PurchaseFreight::class,
            ArrivalPurchaseOrder::class,
            'id',
            'arrival_purchase_order_id',
            'purchase_order_id',
            'id'
        )
            ->selectRaw('arrival_purchase_order_id, SUM(loading_weight) as total_loaded_quantity')
            ->groupBy('arrival_purchase_order_id');
    }

    public function purchaseOrderFreights()
    {
        return $this->hasManyThrough(
            PurchaseFreight::class,
            ArrivalPurchaseOrder::class,
            'id',
            'arrival_purchase_order_id',
            'purchase_order_id',
            'id'
        );
    }

    public function getLoadedQuantityAttribute()
    {
        $loadingWeightSum = $this->purchaseOrderFreights()->sum('loading_weight');

        return [
            'loading_weight_sum' => $loadingWeightSum,
            'max_quantity' => $this->purchaseOrder->max_quantity ?? null,
            'remaining_quantity' => ($this->purchaseOrder->max_quantity ?? 0) - $loadingWeightSum
        ];
    }

    protected static function booted()
    {
        static::observe(\App\Observers\UniversalAuditObserver::class);
    }

    public function auditIdentifier(): string
    {
        return (string) ($this->unique_no ?: "#{$this->id}");
    }

    public static function auditLogConfig(): array
    {
        return [
            'title' => 'Purchase Ticket Audit Logs',
            'subtitle' => function ($model) {
                $parts = ["Ticket #" . ($model->unique_no ?? $model->id)];
                if ($model->purchaseOrder) $parts[] = 'PO #' . ($model->purchaseOrder->contract_no ?? $model->purchase_order_id);
                if ($model->product) $parts[] = 'Product: ' . ($model->product->name ?? 'N/A');
                if ($model->qc_status) $parts[] = 'QC: ' . ucfirst($model->qc_status);
                return implode(' | ', $parts);
            },
            'empty_message' => 'No changes or updates have been recorded for this ticket yet.',
            'field_labels' => [
                'unique_no'              => 'Ticket #',
                'purchase_order_id'      => 'Purchase Order',
                'product_id'             => 'Product',
                'qc_product'             => 'QC Product',
                'bag_weight'             => 'Bag Weight',
                'bag_rate'               => 'Bag Rate',
                'qc_status'              => 'QC Status',
                'first_qc_status'        => 'First QC Status',
                'freight_status'         => 'Freight Status',
                'payment_request_status' => 'Payment Request Status',
            ],
            'relation_resolvers' => [
                'purchase_order_id' => fn($val) => \App\Models\ArrivalPurchaseOrder::find($val)?->contract_no ?? $val,
                'product_id'        => fn($val) => \App\Models\Product::find($val)?->name ?? $val,
                'qc_product'        => fn($val) => \App\Models\Product::find($val)?->name ?? $val,
            ]
        ];
    }
}
