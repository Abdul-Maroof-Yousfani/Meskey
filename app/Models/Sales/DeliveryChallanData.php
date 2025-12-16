<?php

namespace App\Models\Sales;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeliveryChallanData extends Model
{
    use HasFactory;
    protected $guarded = [ "id", "created_at", "updated" ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'item_id');
    }

    public function deliveryChallan()
    {
        return $this->belongsTo(DeliveryChallan::class, 'delivery_challan_id');
    }

    public static function booted() {
        static::created(function($delivery_challan_data) {
            $debit_account_id = 103;
            $credit_account_id = 28;
            $voucher_type_id = 3;

            $voucher_no = $delivery_challan_data->deliveryChallan->dc_no;

            createTransaction(
                $delivery_challan_data->qty * $delivery_challan_data->rate,
                $debit_account_id,
                $voucher_type_id,
                $voucher_no,
                'debit',
                'no',
                [
                    'payment_against' => "Delivery challan",
                    'remarks' => $delivery_challan_data->deliveryChallan->remarks
                ]  
            );

            createTransaction(
                $delivery_challan_data->qty * $delivery_challan_data->rate,
                $credit_account_id,
                $voucher_type_id,
                $voucher_no,
                'credit',
                'no',
                [
                    'payment_against' => "Delivery challan",
                    'remarks' => $delivery_challan_data->deliveryChallan->remarks
                ]  
            );

            createStockTransaction(
                $delivery_challan_data->item_id,
                'delivery_challan',
                $voucher_no,
                $delivery_challan_data->qty,
                'stock-out',
                $delivery_challan_data->qty * $delivery_challan_data->rate,
                $delivery_challan_data->qty * $delivery_challan_data->rate,
            );

        });
    }
}

