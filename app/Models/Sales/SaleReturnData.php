<?php

namespace App\Models\Sales;

use App\Models\Master\Customer;
use App\Models\Product;
use App\Traits\HasBalancing;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SaleReturnData extends Model
{
    use HasFactory;

    protected $fillable = [
        "quantity",
        "sale_return_id",
        "sale_invoice_data_id",
        "rate",
        "gross_amount",
        "discount_percent",
        "discount_amount",
        "amount",
        "gst",
        "gst_percentage",
        "gst_amount",
        "net_amount",
        "line_desc",
        "truck_no",
        "packing",
        "no_of_bags"
    ];

    public static function booted() {
        static::created(function($sale_return_data) {
            $customer_id = $sale_return_data->sale_return->customer_id;
            $debit_account_id = Customer::select("id", "account_id")->find($customer_id);
            $voucher_type_id = 10;
            
            $voucher_no = $sale_return_data->sale_return->sr_no;
            $item_id = $sale_return_data->sale_invoice_data->item_id;
            $product = Product::select("id", "account_id")->find($item_id);
            $credit_account_id = $product->account_id;

            createTransaction(
                $sale_return_data->quantity * $sale_return_data->rate,
                $debit_account_id->account_id,
                $voucher_type_id,
                $voucher_no,
                'credit',
                'no',
                [
                    'payment_against' => "Sale Return",
                ]  
            );

            createTransaction(
                $sale_return_data->quantity * $sale_return_data->rate,
                $credit_account_id,
                $voucher_type_id,
                $voucher_no,
                'debit',
                'no',
                [
                    'payment_against' => "Sale Return",
                ]  
            );

            createStockTransaction(
                $credit_account_id,
                'sale_return',
                $voucher_no,
                $sale_return_data->quantity,
                'stock-in',
                $sale_return_data->quantity * $sale_return_data->rate,
                $sale_return_data->quantity * $sale_return_data->rate,
            );

        });
    }
    public function sale_return() {
        return $this->belongsTo(SalesReturn::class, "sale_return_id");
    }

    public function sale_invoice_data() {
        return $this->belongsTo(SalesInvoiceData::class, "sale_invoice_data_id");
    }
}
