<?php

namespace App\Models\ApprovalsModule;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ApprovalModule extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'model_class',
        'approval_column',
        'is_active',
        'requires_sequential_approval'
    ];

    protected $appends = ['model_label'];

    public function getModelLabelAttribute()
    {
        $map = [
            'App\Models\PaymentVoucher' => 'Payment Voucher',
            'App\Models\Procurement\Store\PurchaseRequestData' => 'Purchase Request Item',
            'App\Models\Procurement\Store\PurchaseQuotationData' => 'Purchase Quotation Item',
        ];

        return $map[$this->model_class] ?? $this->model_class;
    }

    public function roles(): HasMany
    {
        return $this->hasMany(ApprovalModuleRole::class, 'module_id')
            ->orderBy('approval_order');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(ApprovalLog::class, 'module_id');
    }
}
