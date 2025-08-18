<?php

namespace App\Models\Procurement;

use App\Models\Arrival\ArrivalSlip;
use App\Models\Arrival\ArrivalTicket;
use App\Models\Master\Vendor;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FreightPaymentRequest extends Model
{
    use HasFactory;

    protected $table = 'freight_payments';

    protected $fillable = [
        'arrival_ticket_id',
        'arrival_slip_id',
        'arrival_slip_no',
        'vendor_id',
        'contract_rate',
        'exempt',
        'freight_amount',
        'freight_per_ton',
        'loading_kanta',
        'arrived_kanta',
        'other_labour_positive',
        'dehari_extra',
        'market_comm',
        'over_weight_ded',
        'godown_penalty',
        'other_labour_negative',
        'extra_ded',
        'commission_ded',
        'gross_amount',
        'total_deductions',
        'net_amount',
        'request_amount',
        'status'
    ];

    protected $casts = [
        'contract_rate' => 'decimal:2',
        'exempt' => 'decimal:2',
        'freight_amount' => 'decimal:2',
        'freight_per_ton' => 'decimal:2',
        'loading_kanta' => 'decimal:2',
        'arrived_kanta' => 'decimal:2',
        'other_labour_positive' => 'decimal:2',
        'dehari_extra' => 'decimal:2',
        'market_comm' => 'decimal:2',
        'over_weight_ded' => 'decimal:2',
        'godown_penalty' => 'decimal:2',
        'other_labour_negative' => 'decimal:2',
        'extra_ded' => 'decimal:2',
        'commission_ded' => 'decimal:2',
        'gross_amount' => 'decimal:2',
        'total_deductions' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'request_amount' => 'decimal:2',
    ];

    /**
     * Relationship with ArrivalTicket
     */
    public function arrivalTicket()
    {
        return $this->belongsTo(ArrivalTicket::class, 'arrival_ticket_id');
    }

    /**
     * Relationship with Vendor
     */
    public function vendor()
    {
        return $this->belongsTo(Vendor::class, 'vendor_id');
    }

    /**
     * Relationship with ArrivalSlip (if arrival_slip_id is a foreign key)
     * Note: If arrival_slip_id is just a string reference, this relationship won't work
     */
    public function arrivalSlip()
    {
        return $this->belongsTo(ArrivalSlip::class, 'arrival_slip_id');
    }

    /**
     * Automatically generate a payment reference number when creating a new record
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->arrival_slip_no = $model->arrival_slip_no ?? static::generatePaymentReference();
        });
    }

    /**
     * Generate a unique payment reference number
     */
    public static function generatePaymentReference()
    {
        $prefix = 'FP-';
        $latest = static::latest('id')->first();
        $number = $latest ? (int) str_replace($prefix, '', $latest->arrival_slip_no) + 1 : 1;
        return $prefix . str_pad($number, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Scope for pending payments
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for approved payments
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope for rejected payments
     */
    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    /**
     * Check if payment is pending
     */
    public function isPending()
    {
        return $this->status === 'pending';
    }

    /**
     * Check if payment is approved
     */
    public function isApproved()
    {
        return $this->status === 'approved';
    }

    /**
     * Check if payment is rejected
     */
    public function isRejected()
    {
        return $this->status === 'rejected';
    }
}
