<?php

namespace App\Models\Sales;

use App\Models\Master\Customer;
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

    public function deliveryOrderData() {
        return $this->belongsTo(DeliveryOrderData::class, "do_data_id");
    }

    public static function booted() {
        static::created(function($delivery_challan_data) {
            $customer_id = $delivery_challan_data->deliveryChallan->customer_id;
            $debit_account_id = Customer::select("id", "account_id")->find($customer_id);
            $voucher_type_id = 3;
            
            $voucher_no = $delivery_challan_data->deliveryChallan->dc_no;
            $credit_account_id = $delivery_challan_data->product->account_id;

            createTransaction(
                $delivery_challan_data->qty * $delivery_challan_data->rate,
                $debit_account_id->account_id,
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
                $delivery_challan_data->product->account_id,
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

