<?php

namespace App\Models\Sales;

use App\Models\Master\Account\Account;
use App\Models\Master\Account\Stock;
use App\Models\Master\Customer;
use App\Models\Master\Transporter;
use App\Models\Master\Vendor;
use App\Models\Procurement\Store\FactoryLocation;
use App\Models\Procurement\Store\SectionLocation;
use App\Traits\HasApproval;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class DeliveryChallan extends Model
{
    use HasFactory;
    use HasApproval {
        onApprovalComplete as traitOnApprovalComplete;
        onApprovalRejected as traitOnApprovalRejected;
    }

    protected $table = 'delivery_challans';

    protected $fillable = [
        "customer_id",
        "reference_number",
        "dispatch_date",
        "dc_no",
        "sauda_type",
        "location_id",
        "arrival_id",
        "company_id",
        "remarks",
        "labour",
        "labour_amount",
        "transporter",
        "transporter_id",
        "transporter_amount",
        "inhouse-weighbridge",
        "weighbridge-amount",
        "subarrival_id",
        "created_by_id",
        "section_id",
        'labour_rate',
        "labour_status",
        "am_approval_status",
        "am_change_made"
    ];

    protected static function booted()
    {
        static::creating(function ($model) {
            $model->type = 'sale_delivery_challan';
        });

        static::updating(function ($model) {
            $model->type = 'sale_delivery_challan';
        });

        static::addGlobalScope('sale_type', function ($builder) {
            $builder->where('delivery_challans.type', 'sale_delivery_challan');
        });
    }


    public function delivery_challan_data()
    {
        return $this->hasMany(DeliveryChallanData::class, "delivery_challan_id", "id");
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function delivery_order()
    {
        return $this->belongsToMany(DeliveryOrder::class, "delivery_challan_delivery_order", "delivery_challan_id", "delivery_order_id");
    }

    public function receivingRequest()
    {
        return $this->hasOne(ReceivingRequest::class, "delivery_challan_id");
    }


    public function factories()
    {
        return $this->morphMany(FactoryLocation::class, 'factoryable');
    }

    public function sections()
    {
        return $this->morphMany(SectionLocation::class, 'sectionable');
    }

    protected function onApprovalComplete()
    {
        $this->traitOnApprovalComplete();
        app(\App\Services\SalesLedgerService::class)->handleDeliveryChallanApproval($this);
    }

    protected function onApprovalRejected()
    {
        $this->traitOnApprovalRejected();

        foreach ($this->delivery_challan_data as $data) {
            if ($data->ticket_id) {
                $ticket = \App\Models\Sales\LoadingProgramItem::find($data->ticket_id);
                if ($ticket) {
                    $ticket->update(['process_status' => 'DC Rejected']);
                }
            }
        }

        // Delete stock transaction if rejected
        Stock::where('voucher_no', $this->dc_no)
            ->where('voucher_type', 'delivery_challan')
            ->delete();
    }

}
