<?php

namespace App\Models\Sales;

use App\Models\Procurement\Store\FactoryLocation;
use App\Models\Procurement\Store\SectionLocation;
use App\Traits\HasApproval;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
    

    public function delivery_challan_data() {
        return $this->hasMany(DeliveryChallanData::class, "delivery_challan_id", "id");
    }

    public function customer() {
        return $this->belongsTo(\App\Models\Master\Customer::class, 'customer_id');
    }

    public function delivery_order() {
        return $this->belongsToMany(DeliveryOrder::class, "delivery_challan_delivery_order", "delivery_challan_id", "delivery_order_id");
    }

    public function receivingRequest() {
        return $this->hasOne(ReceivingRequest::class, "delivery_challan_id");
    }


    public function factories() {
        return $this->morphMany(FactoryLocation::class, 'factoryable');
    }

    public function sections() {
        return $this->morphMany(SectionLocation::class, 'sectionable');
    }

    protected function onApprovalComplete()
    {
        $this->traitOnApprovalComplete();

        foreach ($this->delivery_challan_data as $data) {
            if ($data->ticket_id) {
                $ticket = \App\Models\Sales\LoadingProgramItem::find($data->ticket_id);
                if ($ticket) {
                    $ticket->update(['process_status' => 'DC Generated']);
                }
            }
        }

        // Create Receiving Request upon DC Approval
        if (strtolower($this->sauda_type) == 'pohanch') {
            $receivingRequest = $this->receivingRequest;
            $dcDataFirst = $this->delivery_challan_data->first();
            
            if (!$receivingRequest) {
                $receivingRequest = \App\Models\Sales\ReceivingRequest::create([
                    'delivery_challan_id' => $this->id,
                    'dc_no' => $this->dc_no,
                    'dc_date' => $this->dispatch_date,
                    'truck_number' => $dcDataFirst?->truck_no ?? null,
                    'labour' => $this->labour,
                    'transporter' => $this->transporter,
                    'inhouse_weighbridge' => $this->{'inhouse-weighbridge'} ?? null,
                    'labour_amount' => $this->labour_amount ?? 0,
                    'transporter_amount' => $this->transporter_amount ?? 0,
                    'inhouse_weighbridge_amount' => $this->{'weighbridge-amount'} ?? 0,
                    'company_id' => $this->company_id,
                    'created_by_id' => $this->created_by_id,
                    'am_approval_status' => 'draft',
                ]);
            } else {
                $receivingRequest->update([
                    'dc_no' => $this->dc_no,
                    'dc_date' => $this->dispatch_date,
                    'truck_number' => $dcDataFirst?->truck_no ?? null,
                    'labour' => $this->labour,
                    'transporter' => $this->transporter,
                    'inhouse_weighbridge' => $this->{'inhouse-weighbridge'} ?? null,
                    'labour_amount' => $this->labour_amount ?? 0,
                    'transporter_amount' => $this->transporter_amount ?? 0,
                    'inhouse_weighbridge_amount' => $this->{'weighbridge-amount'} ?? 0,
                    'am_approval_status' => 'draft',
                    'created_by_id' => $this->created_by_id,
                ]);
            }

            // Sync Receiving Request Items
            $receivingRequest->items()->delete();
            foreach ($this->delivery_challan_data as $dcData) {
                $product = $dcData->product;
                \App\Models\Sales\ReceivingRequestItem::create([
                    'receiving_request_id' => $receivingRequest->id,
                    'delivery_challan_data_id' => $dcData->id,
                    'item_id' => $dcData->item_id,
                    'item_name' => $product?->name ?? 'N/A',
                    'dispatch_weight' => $dcData->qty ?? 0,
                    'receiving_weight' => 0,
                    'difference_weight' => $dcData->qty ?? 0,
                    'seller_portion' => 0,
                    'remaining_amount' => $dcData->qty ?? 0,
                ]);
            }
        } else {
            $receivingRequest = $this->receivingRequest;
            if ($receivingRequest) {
                $receivingRequest->items()->delete();
                $receivingRequest->delete();
            }
        }
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
    }
    
}
