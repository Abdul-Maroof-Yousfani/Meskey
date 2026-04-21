<?php

namespace App\Models\Export;

use App\Models\Master\Customer;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExportDeliveryChallanData extends Model
{
    use HasFactory;

    protected $table = 'delivery_challan_data';

    protected $guarded = ['id', 'created_at', 'updated_at'];

    public function product()
    {
        return $this->belongsTo(Product::class, 'item_id');
    }

    public function deliveryChallan()
    {
        return $this->belongsTo(ExportDeliveryChallan::class, 'delivery_challan_id');
    }

    public function deliveryOrderData()
    {
        return $this->belongsTo(ExportDeliveryOrderPackingItem::class, 'do_data_id');
    }

    public function exportPackingItem()
    {
        return $this->belongsTo(ExportDeliveryOrderPackingItem::class, 'do_data_id');
    }

    public function loadingProgramItem()
    {
        return $this->belongsTo(\App\Models\Sales\LoadingProgramItem::class, 'ticket_id');
    }

    public static function booted()
    {
        static::created(function ($deliveryChallanData) {
            $customerId = $deliveryChallanData->deliveryChallan->customer_id;
            $debitAccount = Customer::select('id', 'account_id')->find($customerId);
            $voucherTypeId = 3;
            $voucherNo = $deliveryChallanData->deliveryChallan->dc_no;
            $creditAccountId = $deliveryChallanData->product->account_id ?? null;

            if (!$debitAccount?->account_id || !$creditAccountId) {
                return;
            }

            createTransaction(
                $deliveryChallanData->qty * $deliveryChallanData->rate,
                $debitAccount->account_id,
                $voucherTypeId,
                $voucherNo,
                'debit',
                'no',
                [
                    'payment_against' => 'Delivery challan',
                    'remarks' => $deliveryChallanData->deliveryChallan->remarks,
                ]
            );

            createTransaction(
                $deliveryChallanData->qty * $deliveryChallanData->rate,
                $creditAccountId,
                $voucherTypeId,
                $voucherNo,
                'credit',
                'no',
                [
                    'payment_against' => 'Delivery challan',
                    'remarks' => $deliveryChallanData->deliveryChallan->remarks,
                ]
            );

            createStockTransaction(
                $deliveryChallanData->product->account_id,
                'delivery_challan',
                $voucherNo,
                $deliveryChallanData->qty,
                'stock-out',
                $deliveryChallanData->qty * $deliveryChallanData->rate,
                $deliveryChallanData->qty * $deliveryChallanData->rate,
            );
        });
    }
}

