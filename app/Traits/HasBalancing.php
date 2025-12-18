<?php

namespace App\Traits;

use App\Models\ApprovalsModule\ApprovalLog;
use App\Models\ApprovalsModule\ApprovalModule;
use App\Models\ApprovalsModule\ApprovalRow;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;


trait HasBalancing {
    
    public function balance($id) {
        $line_item_class = $this->line_item_class;
        $parent_line_item = $this->parent_line_item;
        $foreign_key = $this->foreign_key;
        $to_balance_column = $this->balancing_column;
        
        $data = $line_item_class::get();
        dd($id);
        
        $spent = $data->sum($to_balance_column);
        $able_to_spend = ($parent_line_item::where("id", $id)->first());
        dd($data);
        $balance = (int)$able_to_spend - (int)$spent;

        return $balance;
    }

    public function spent($id) {
        $line_item_class = $this->line_item_class;
        $foreign_key = $this->foreign_key;
        $to_balance_column = $this->balancing_column;

        $data = $line_item_class::where($foreign_key, $id)->get();
        
        $spent = $data->sum($to_balance_column);
        return $spent;
    }
}