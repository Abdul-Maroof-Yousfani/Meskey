<?php

namespace App\Models\Sales;

use App\Models\Master\ArrivalLocation;
use App\Models\Master\CompanyLocation;
use App\Models\Master\Customer;
use App\Models\User;
use App\Models\Sales\SalesInvoiceData;
use App\Models\Sales\DeliveryChallanData;
use App\Traits\HasBalancing;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasApproval;

class SalesInvoice extends Model
{
    use HasFactory, HasApproval;


    protected $table = "sales_invoices";

    protected $fillable = [
        'customer_id',
        'invoice_address',
        'location_id',
        'arrival_id',
        'si_no',
        'invoice_date',
        'reference_number',
        'sauda_type',
        'remarks',
        'company_id',
        'created_by_id',
        'am_approval_status',
        'am_change_made'
    ];

    protected static function booted()
    {
        static::updating(function ($model) {
            $originalStatus = strtolower($model->getOriginal('am_approval_status') ?? '');
            $newStatus = strtolower($model->am_approval_status ?? '');
            if ($model->isDirty('am_approval_status')) {
                if (in_array($originalStatus, ['approved', 'rejected'])) {
                    throw new \Exception("Sales Invoice is already {$originalStatus} and status cannot be changed.");
                }
                if ($originalStatus === 'reverted' && $newStatus !== 'pending') {
                    throw new \Exception("Sales Invoice is reverted and cannot be {$newStatus} directly. It must be updated to pending first.");
                }
            }
        });

        static::deleting(function ($model) {
            $status = strtolower($model->am_approval_status ?? '');
            if (in_array($status, ['approved', 'rejected'])) {
                throw new \Exception("Sales Invoice is already {$status} and cannot be deleted.");
            }
        });
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function location()
    {
        return $this->belongsTo(CompanyLocation::class, 'location_id');
    }

    public function arrival_location()
    {
        return $this->belongsTo(ArrivalLocation::class, 'arrival_id');
    }

    public function delivery_challans()
    {
        return $this->belongsToMany(DeliveryChallan::class, 'sales_invoice_delivery_challan', 'sales_invoice_id', 'delivery_challan_id');
    }

    public function sales_invoice_data()
    {
        return $this->hasMany(SalesInvoiceData::class, 'sales_invoice_id');
    }

    public function created_by()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function scopeApproved($query) {
        return $query->where("am_approval_status", "approved");
    }
}

