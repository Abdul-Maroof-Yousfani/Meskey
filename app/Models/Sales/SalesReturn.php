<?php

namespace App\Models\Sales;

use App\Traits\HasApproval;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesReturn extends Model
{
    use HasFactory;
    use HasApproval {
        onApprovalComplete as traitOnApprovalComplete;
        onApprovalRejected as traitOnApprovalRejected;
    }

    protected $fillable = [
        "customer_id",
        "sr_no",
        "date",
        "reference_number",
        "contract_type",
        "company_location_id",
        "arrival_location_id",
        "storage_location_id",
        "remarks",
        "am_approval_status",
        "am_change_made",
        "created_by",
        "company_id"
    ];

    protected $table = "sales_return";

    protected static function booted()
    {
        static::updating(function ($model) {
            $originalStatus = strtolower($model->getOriginal('am_approval_status') ?? '');
            $newStatus = strtolower($model->am_approval_status ?? '');
            if ($model->isDirty('am_approval_status')) {
                if (in_array($originalStatus, ['approved', 'rejected'])) {
                    throw new \Exception("Sales Return is already {$originalStatus} and status cannot be changed.");
                }
                if ($originalStatus === 'reverted' && $newStatus !== 'pending') {
                    throw new \Exception("Sales Return is reverted and cannot be {$newStatus} directly. It must be updated to pending first.");
                }
            }
        });

        static::deleting(function ($model) {
            $status = strtolower($model->am_approval_status ?? '');
            if (in_array($status, ['approved', 'rejected'])) {
                throw new \Exception("Sales Return is already {$status} and cannot be deleted.");
            }
        });
    }

    public function customer()
    {
        return $this->belongsTo(\App\Models\Master\Customer::class, 'customer_id');
    }

    public function sale_return_data() {
        return $this->hasMany(SaleReturnData::class, "sale_return_id");
    }

    public function sale_invoices() {
        return $this->belongsToMany(SalesInvoice::class, "sale_return_sale_invoice", "sale_return_id", "sale_invoice_id");
    }

    public function receiving_requests() {
        return $this->belongsToMany(ReceivingRequest::class, "sale_return_sale_invoice", "sale_return_id", "sale_invoice_id");
    }

    protected function onApprovalComplete()
    {
        $this->traitOnApprovalComplete();
        app(\App\Services\SalesLedgerService::class)->handleSalesReturnApproval($this);
    }

    protected function onApprovalRejected()
    {
        $this->traitOnApprovalRejected();

        \App\Models\Master\Account\Stock::where('voucher_no', $this->sr_no)
            ->where('voucher_type', 'sale_return')
            ->delete();

        \App\Models\Master\Account\Transaction::where('voucher_no', $this->sr_no)
            ->delete();
    }
}
