<?php

namespace App\Http\Controllers\Procurement\RawMaterial;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;


use App\Http\Requests\Procurement\PaymentRequestRequest;
use App\Http\Requests\Procurement\TicketPaymentRequestRequest;
use App\Models\Arrival\ArrivalSamplingRequest;
use App\Models\Arrival\ArrivalSamplingResult;
use App\Models\Arrival\ArrivalSamplingResultForCompulsury;
use App\Models\Arrival\ArrivalTicket;
use App\Models\Arrival\PurchaseSamplingResult;
use App\Models\Arrival\PurchaseSamplingResultForCompulsury;
use App\Models\ArrivalPurchaseOrder;
use App\Models\Master\Account\Account;
use App\Models\Master\Account\Transaction;
use App\Models\Master\ArrivalCompulsoryQcParam;
use App\Models\Master\Broker;
use App\Models\Master\ProductSlab;
use App\Models\Master\ProductSlabForRmPo;
use App\Models\Master\ProductSlabType;
use App\Models\Master\Supplier;
use App\Models\Procurement\PaymentRequest;
use App\Models\Procurement\PaymentRequestData;
use App\Models\Procurement\PaymentRequestSamplingResult;
use App\Models\Product;
use App\Models\PurchaseSamplingRequest;
use App\Models\PurchaseTicket;
use App\Models\TruckSizeRange;
use Illuminate\Support\Facades\DB;



class GateBuyingPaymentRequestController extends Controller
{
   public function index()
    {
        return view('management.procurement.raw_material.gate_buying_payment_request.index');
    }

    /**
     * Get list of categories.
     */
    public function getList(Request $request)
    {
        $query = ArrivalTicket::with([
            'purchaseOrder',
            'paymentRequestData.paymentRequests',
            'paymentRequestData.paymentRequests.approvals',
            'broker',
            'product',
            'qcProduct',
            'freight',
            'paymentRequestData' => function ($query) {
                $query->with(['paymentRequests' => function ($q) {
                    $q->selectRaw('payment_request_data_id, request_type, status, SUM(amount) as total_amount')
                        ->groupBy('payment_request_data_id', 'request_type', 'status');
                }]);
            }
        ])
            ->where('is_ticket_verified', 1)
            ->when($request->filled('company_location_id'), function ($q) use ($request) {
                return $q->where('location_id', $request->company_location_id);
            })
            ->when($request->filled('supplier_id'), function ($q) use ($request) {
                return $q->where('accounts_of_id', $request->supplier_id);
            })
            ->when($request->filled('daterange'), function ($q) use ($request) {
                $dates = explode(' - ', $request->daterange);
                $startDate = \Carbon\Carbon::createFromFormat('m/d/Y', trim($dates[0]))->format('Y-m-d');
                $endDate = \Carbon\Carbon::createFromFormat('m/d/Y', trim($dates[1]))->format('Y-m-d');

                return $q->whereDate('created_at', '>=', $startDate)
                    ->whereDate('created_at', '<=', $endDate);
            })
            ->where('first_qc_status', '!=', 'rejected')
             ->whereHas('purchaseOrder', function ($q) {
        $q->where('purchase_type', 'gate_buying');
    })
            ->whereHas('purchaseOrder')->where('sauda_type_id', 1)->orderByDesc(function ($query) {
                $query->select('created_at')
                    ->from('arrival_freights')
                    ->whereColumn('arrival_freights.arrival_ticket_id', 'arrival_tickets.id')
                    ->limit(1);
            })
            ->orderByDesc('created_at');

        if ($request->has('broker_id') && $request->broker_id != '') {
            $query->whereHas('purchaseOrder', function ($q) use ($request) {
                $q->where('broker_id', $request->broker_id);
            });
        }

        if ($request->has('product_id') && $request->product_id != '') {
            $query->where('qc_product', $request->product_id);
        }

        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('unique_no', 'like', "%{$search}%")
                    ->orWhere('truck_no', 'like', "%{$search}%")
                    ->orWhereHas('purchaseOrder', function ($q) use ($search) {
                        $q->where('contract_no', 'like', "%{$search}%")
                            ->orWhere('ref_no', 'like', "%{$search}%");
                    })
                    ->orWhereHas('purchaseOrder.supplier', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('product', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    });
            });
        }

        $tickets = $query->paginate($request->per_page ?? 10);

        $tickets->getCollection()->transform(function ($ticket) {
            $approvedPaymentSum = 0;
            $approvedFreightSum = 0;
            $totalPaymentSum = 0;
            $totalFreightSum = 0;
            $totalAmount = 0;
            $paidAmount = 0;
            $remainingAmount = 0;

            foreach ($ticket->paymentRequestData as $data) {
                $totalAmount = $data->total_amount ?? 0;
                $paidAmount = $data->paid_amount ?? 0;

                foreach ($data->paymentRequests as $pRequest) {
                    if ($pRequest->request_type == 'payment') {
                        $totalPaymentSum += $pRequest->total_amount;
                        if ($pRequest->status == 'approved') {
                            $approvedPaymentSum += $pRequest->total_amount;
                        }
                    } else {
                        $totalFreightSum += $pRequest->total_amount;
                        if ($pRequest->status == 'approved') {
                            $approvedFreightSum += $pRequest->total_amount;
                        }
                    }
                }
            }

            $remainingAmount = ($totalAmount - $approvedPaymentSum);

            $ticket->calculated_values = [
                'total_payment_sum' => $totalPaymentSum,
                'total_freight_sum' => $totalFreightSum,
                'approved_payment_sum' => $approvedPaymentSum,
                'approved_freight_sum' => $approvedFreightSum,
                'total_amount' => $totalAmount,
                'paid_amount' => $paidAmount,
                'remaining_amount' => $remainingAmount,
                'created_at' => $ticket?->freight?->first()->created_at ?? $ticket->created_at
            ];

            return $ticket;
        });

        return view('management.procurement.raw_material.gate_buying_payment_request.getList', [
            'tickets' => $tickets,
        ]);
    }
    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $data['purchaseOrders'] = ArrivalPurchaseOrder::where('sauda_type_id', 2)->get();
        $data['truckSizeRanges'] = TruckSizeRange::where('status', 'active')->get();
        $data['products'] = Product::where('product_type', 'raw_material')->get();

        return view('management.procurement.raw_material.ticket_payment_request.create', $data);
    }
}
