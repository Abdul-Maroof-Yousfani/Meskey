<?php

namespace App\Traits;

use App\Models\ApprovalsModule\ApprovalLog;
use App\Models\ApprovalsModule\ApprovalModule;
use App\Models\ApprovalsModule\ApprovalRow;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;


trait HasBalancing {
    
    public function balance($id) {
        $data = DeliveryChallanData::where("do_data_id", $delivery_order_data_id)->get();
        
        $spent = $data->sum("no_of_bags");
        $able_to_spend = (DeliveryOrderData::where("id", $delivery_order_data_id)->first())->no_of_bags;
        $balance = (int)$able_to_spend - (int)$spent;

        return $balance;
    }
}