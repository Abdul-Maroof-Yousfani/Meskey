<?php

namespace App\Http\Controllers\Procurement\RawMaterial;

use App\Models\Procurement\PaymentRequestApproval;
use App\Models\Product;
use Illuminate\Http\Request;
use App\Models\Master\Broker;
use App\Models\TruckSizeRange;
use App\Models\PurchaseTicket;
use App\Models\Master\ProductSlab;
use Illuminate\Support\Facades\DB;
use App\Models\ArrivalPurchaseOrder;
use App\Http\Controllers\Controller;
use App\Http\Requests\Arrival\FreightRequest;
use App\Http\Requests\Procurement\FreightPaymentRequestRequest;
use App\Models\Master\Account\Account;
use App\Models\PurchaseSamplingRequest;
use App\Models\Master\ProductSlabForRmPo;
use App\Models\Master\Account\Transaction;
use App\Models\Procurement\PaymentRequest;
use App\Models\Procurement\PaymentRequestData;
use App\Models\Arrival\PurchaseSamplingResult;
use App\Models\Procurement\PaymentRequestSamplingResult;
use App\Http\Requests\Procurement\PaymentRequestRequest;
use App\Models\Arrival\ArrivalSamplingRequest;
use App\Models\Arrival\ArrivalSamplingResult;
use App\Models\Arrival\ArrivalSamplingResultForCompulsury;
use App\Models\Arrival\ArrivalSlip;
use App\Models\Arrival\ArrivalTicket;
use App\Models\Arrival\PurchaseSamplingResultForCompulsury;
use App\Models\Master\Vendor;
use App\Models\Procurement\FreightPaymentRequest;
use Illuminate\Pagination\LengthAwarePaginator;

class FreightRequestController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('management.procurement.raw_material.freight_request.index');
    }

    /**
     * Get list of tickets with completed freight status.
     */
    public function getList(Request $request)
    {
        // $purchaseQuery = PurchaseTicket::where('freight_status', 'completed')
        //     ->with([
        //         'paymentRequestData.paymentRequests',
        //         'paymentRequestData.paymentRequests.approvals',
        //         'purchaseOrder.supplier',
        //         'product',
        //         'qcProduct',
        //         'purchaseFreight',
        //         'paymentRequestData' => function ($query) {
        //             $query->with(['paymentRequests' => function ($q) {
        //                 $q->selectRaw('payment_request_data_id, request_type, status, SUM(amount) as total_amount')
        //                     ->groupBy('payment_request_data_id', 'request_type', 'status');
        //             }]);
        //         }
        //     ])
        //     ->when($request->filled('company_location_id'), function ($q) use ($request) {
        //         return $q->whereHas('purchaseOrder', function ($query) use ($request) {
        //             $query->where('company_location_id', $request->company_location_id);
        //         });
        //     })
        //     ->when($request->filled('supplier_id'), function ($q) use ($request) {
        //         return $q->whereHas('purchaseOrder', function ($query) use ($request) {
        //             $query->where('supplier_id', $request->supplier_id);
        //         });
        //     })
        //     ->when($request->filled('daterange'), function ($q) use ($request) {
        //         $dates = explode(' - ', $request->daterange);
        //         $startDate = \Carbon\Carbon::createFromFormat('m/d/Y', trim($dates[0]))->format('Y-m-d');
        //         $endDate = \Carbon\Carbon::createFromFormat('m/d/Y', trim($dates[1]))->format('Y-m-d');

        //         return $q->whereDate('created_at', '>=', $startDate)
        //             ->whereDate('created_at', '<=', $endDate);
        //     });

        // if ($request->has('product_id') && $request->product_id != '') {
        //     $purchaseQuery->where('qc_product', $request->product_id);
        // }

        // Query for Arrival Tickets (Pohanch requests)
        $arrivalQuery = ArrivalTicket::with([
            'purchaseOrder',
            'paymentRequestData.paymentRequests',
            'paymentRequestData.paymentRequests.approvals',
            'broker',
            'product',
            'qcProduct',
            'freight',
            'paymentRequestData' => function ($query) {
                $query->with([
                    'paymentRequests' => function ($q) {
                        $q->selectRaw('payment_request_data_id, request_type, status, SUM(amount) as total_amount')
                            ->groupBy('payment_request_data_id', 'request_type', 'status');
                    }
                ]);
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
            ->whereHas('purchaseOrder');

        if ($request->has('broker_id') && $request->broker_id != '') {
            $arrivalQuery->whereHas('purchaseOrder', function ($q) use ($request) {
                $q->where('broker_id', $request->broker_id);
            });
        }

        if ($request->has('product_id') && $request->product_id != '') {
            $arrivalQuery->where('qc_product', $request->product_id);
        }

        if ($request->has('search') && $request->search != '') {
            $search = $request->search;

            $arrivalQuery->where(function ($q) use ($search) {
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

        $arrivalTickets = $arrivalQuery->get();

        $arrivalTickets->transform(function ($ticket) {
            $approvedPaymentSum = 0;
            $approvedFreightSum = 0;
            $totalPaymentSum = 0;
            $totalFreightSum = 0;
            $totalAmount = 0;
            $paidAmount = 0;
            $remainingAmount = 0;
            $totalRequestsCount = 0;
            $requestStatus = NULL;

            foreach ($ticket->paymentRequestData->where('module_type', 'freight_payment') as $data) {
                $totalAmount = $data->total_amount ?? 0;
                $paidAmount = $data->paid_amount ?? 0;

                // Count all requests for this ticket
                $totalRequestsCount += $data->paymentRequests->count();

                foreach ($data->paymentRequests as $pRequest) {
                    $requestStatus = $pRequest->status;
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

            return [
                'type' => $ticket->saudaType->name,
                'model' => $ticket,
                'unique_no' => $ticket->unique_no ?? 'N/A',
                'purchaseOrder' => $ticket->purchaseOrder,
                'requestStatus' => $requestStatus,
                'product' => $ticket->product,
                'qcProduct' => $ticket->qcProduct,
                'purchaseFreight' => null,
                'freight' => $ticket->freight,
                'total_requests_count' => $totalRequestsCount,
                'calculated_values' => [
                    'total_payment_sum' => $totalPaymentSum,
                    'total_freight_sum' => $totalFreightSum,
                    'approved_payment_sum' => $approvedPaymentSum,
                    'approved_freight_sum' => $approvedFreightSum,
                    'total_amount' => $totalAmount,
                    'paid_amount' => $paidAmount,
                    'remaining_amount' => $remainingAmount,
                    'created_at' => $ticket?->freight?->first()->created_at ?? $ticket->created_at
                ]
            ];
        });

        // $combinedTickets = $purchaseTickets->concat($arrivalTickets);
        $combinedTickets = $arrivalTickets;

        $sortedTickets = $combinedTickets->sortByDesc(function ($item) {
            return $item['calculated_values']['created_at'];
        });

        $page = LengthAwarePaginator::resolveCurrentPage();
        $perPage = $request->per_page ?? 10;
        $paginatedItems = $sortedTickets->slice(($page - 1) * $perPage, $perPage)->values();
        $tickets = new LengthAwarePaginator($paginatedItems, $sortedTickets->count(), $perPage, $page, [
            'path' => LengthAwarePaginator::resolveCurrentPath()
        ]);

        return view('management.procurement.raw_material.freight_request.getList', [
            'tickets' => $tickets,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $data['tickets'] = PurchaseTicket::where('freight_status', 'completed')
            ->with(['purchaseOrder', 'purchaseFreight'])
            ->get();
        $data['truckSizeRanges'] = TruckSizeRange::where('status', 'active')->get();
        $data['products'] = Product::where('product_type', 'raw_material')->get();

        return view('management.procurement.raw_material.freight_request.create', $data);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(FreightPaymentRequestRequest $request)
    {
     
        return DB::transaction(function () use ($request) {
            $requestData = $request->all();
            $requestData['module_type'] = 'freight_payment';
            $requestData['total_amount'] = $requestData['net_amount'];

            $ticketID = $requestData['ticket_id'];
            $purchaseOrderID = $requestData['purchase_order_id'];

            $purchaseOrder = ArrivalPurchaseOrder::where('id', $purchaseOrderID)->first();

            $vendor = Vendor::where('id', $request->vendor_id)->first();
            $accountId = $vendor->account_id ?? null;

            $requestData['account_id'] = $accountId;
            $requestData['payment_to_type'] = 'vendors';
            $requestData['payment_to'] = $request->vendor_id;
            $requestData['supplier_name'] = $purchaseOrder->supplier->name;

            $paymentRequestData = PaymentRequestData::create($requestData);

            $this->createPaymentRequests($paymentRequestData, $request, $accountId);

            if (isset($request->sampling_results) || isset($request->compulsory_results)) {
                $this->saveSamplingResults($paymentRequestData, $request);
            }

            $message = $request->freight_pay_request_amount ?
                'Payment and freight payment requests created successfully' : 'Payment request created successfully';

            return response()->json(['success' => $message]);
        });
    }

    public function manageLedgerCalculations($requestData, $purchaseOrder, $ticket, $stockInTransitAccount, $existingApprovals)
    {
        $paymentDetails = calculatePaymentDetails($requestData['ticket_id'], 2);
        $contractNo = $purchaseOrder->contract_no;
        $qcProduct = $purchaseOrder->qcProduct->name;
        $truckNo = $ticket->purchaseFreight->truck_no ?? 'N/A';
        $biltyNo = $ticket->purchaseFreight->bilty_no ?? 'N/A';
        $loadingWeight = $ticket->purchaseFreight->loading_weight ?? 0;

        $amount = $paymentDetails['calculations']['net_amount'] ?? 0;
        $inventoryAmount = $paymentDetails['calculations']['inventory_amount'] ?? 0;

        $supplierTxn = Transaction::where('voucher_no', $contractNo)
            ->where('purpose', 'supplier-payable')
            ->where('against_reference_no', "$truckNo/$biltyNo")
            ->first();

        $supplierData = [
            'amount' => $paymentDetails['calculations']['supplier_net_amount'] ?? 0,
            'account_id' => $purchaseOrder->supplier->account_id,
            'type' => 'credit',
            'counter_account_id' => $stockInTransitAccount->id,
            'remarks' => "Accounts payable recorded against the contract ($contractNo) for Bilty: $biltyNo - Truck No: $truckNo. Amount payable to the supplier.",
        ];

        if ($supplierTxn) {
            $supplierTxn->update($supplierData);
        } else {
            createTransaction(
                $paymentDetails['calculations']['supplier_net_amount'] ?? 0,
                $purchaseOrder->supplier->account_id,
                1,
                $contractNo,
                'credit',
                'no',
                [
                    'purpose' => "supplier-payable",
                    'payment_against' => "thadda-purchase",
                    'counter_account_id' => $stockInTransitAccount->id,
                    'against_reference_no' => "$truckNo/$biltyNo",
                    'remarks' => $supplierData['remarks']
                ]
            );
        }

        $transitTxn = Transaction::where('voucher_no', $contractNo)
            ->where('purpose', 'stock-in-transit')
            ->where('type', 'debit')
            ->where('against_reference_no', "$truckNo/$biltyNo")
            ->first();

        $transitData = [
            'amount' => $inventoryAmount,
            'account_id' => $stockInTransitAccount->id,
            'counter_account_id' => $purchaseOrder->supplier->account_id,
            'remarks' => "Stock-in-transit recorded for arrival of $qcProduct under contract ($contractNo) via Bilty: $biltyNo - Truck No: $truckNo. Weight: {$requestData['loading_weight']} kg at rate {$purchaseOrder->rate_per_kg}/kg."
        ];

        if ($transitTxn) {
            $transitTxn->update($transitData);
        } else {
            createTransaction(
                $inventoryAmount,
                $stockInTransitAccount->id,
                1,
                $contractNo,
                'debit',
                'no',
                [
                    'purpose' => "stock-in-transit",
                    'counter_account_id' => $purchaseOrder->supplier->account_id,
                    'payment_against' => "thadda-purchase",
                    'against_reference_no' => "$truckNo/$biltyNo",
                    'remarks' => $transitData['remarks']
                ]
            );
        }

        if ($purchaseOrder->broker_one_id && $purchaseOrder->broker_one_commission && $loadingWeight) {
            $amount = ($loadingWeight * $purchaseOrder->broker_one_commission);

            $existingBrokerTrx = Transaction::where('voucher_no', $contractNo)
                ->where('payment_against', 'thadda-purchase')
                ->where('account_id', $purchaseOrder->broker->account_id)
                ->where('against_reference_no', "$truckNo/$biltyNo")
                ->first();

            if ($existingBrokerTrx) {
                $existingBrokerTrx->update([
                    'amount' => $amount,
                    'counter_account_id' => $stockInTransitAccount->id,
                    'account_id' => $purchaseOrder->broker->account_id,
                    'type' => 'credit',
                ]);
            } else {
                createTransaction(
                    $amount,
                    $purchaseOrder->broker->account_id,
                    1,
                    $purchaseOrder->contract_no,
                    'credit',
                    'no',
                    [
                        'purpose' => "broker",
                        'counter_account_id' => $stockInTransitAccount->id,
                        'payment_against' => "thadda-purchase",
                        'against_reference_no' => "$truckNo/$biltyNo",
                        'remarks' => 'Recording accounts payable for "Thadda" purchase. Amount to be paid to broker.'
                    ]
                );
            }
        }

        if ($purchaseOrder->broker_two_id && $purchaseOrder->broker_two_commission && $loadingWeight) {
            $amount = ($loadingWeight * $purchaseOrder->broker_two_commission);


            $existingBrokerTrx = Transaction::where('voucher_no', $contractNo)
                ->where('payment_against', 'thadda-purchase')
                ->where('account_id', $purchaseOrder->brokerTwo->account_id)
                ->where('against_reference_no', "$truckNo/$biltyNo")
                ->first();

            if ($existingBrokerTrx) {
                $existingBrokerTrx->update([
                    'amount' => $amount,
                    'counter_account_id' => $stockInTransitAccount->id,
                    'account_id' => $purchaseOrder->brokerTwo->account_id,
                    'type' => 'credit',
                ]);
            } else {
                createTransaction(
                    $amount,
                    $purchaseOrder->brokerTwo->account_id,
                    1,
                    $purchaseOrder->contract_no,
                    'credit',
                    'no',
                    [
                        'purpose' => "broker",
                        'counter_account_id' => $stockInTransitAccount->id,
                        'payment_against' => "thadda-purchase",
                        'against_reference_no' => "$truckNo/$biltyNo",
                        'remarks' => 'Recording accounts payable for "Thadda" purchase. Amount to be paid to broker.'
                    ]
                );
            }
        }

        if ($purchaseOrder->broker_three_id && $purchaseOrder->broker_three_commission && $loadingWeight) {
            $amount = ($loadingWeight * $purchaseOrder->broker_three_commission);

            $existingBrokerTrx = Transaction::where('voucher_no', $contractNo)
                ->where('payment_against', 'thadda-purchase')
                ->where('account_id', $purchaseOrder->brokerThree->account_id)
                ->where('against_reference_no', "$truckNo/$biltyNo")
                ->first();

            if ($existingBrokerTrx) {
                $existingBrokerTrx->update([
                    'amount' => $amount,
                    'counter_account_id' => $stockInTransitAccount->id,
                    'account_id' => $purchaseOrder->brokerThree->account_id,
                    'type' => 'credit',
                ]);
            } else {
                createTransaction(
                    $amount,
                    $purchaseOrder->brokerThree->account_id,
                    1,
                    $purchaseOrder->contract_no,
                    'credit',
                    'no',
                    [
                        'purpose' => "broker",
                        'counter_account_id' => $stockInTransitAccount->id,
                        'payment_against' => "thadda-purchase",
                        'against_reference_no' => "$truckNo/$biltyNo",
                        'remarks' => 'Recording accounts payable for "Thadda" purchase. Amount to be paid to broker.'
                    ]
                );
            }
        }

        $existingFreightTrx = Transaction::where('voucher_no', $contractNo)
            ->where('purpose', 'thadda-freight')
            ->where('against_reference_no', "$truckNo/$biltyNo")
            ->first();

        $existingSiTFreightTrx = Transaction::where('voucher_no', $contractNo)
            ->where('purpose', 'stock-in-transit')
            ->where('payment_against', 'thadda-freight')
            ->where('type', 'debit')
            ->where('against_reference_no', "$truckNo/$biltyNo")
            ->first();

        $advanceFreight = (int) ($requestData['advance_freight']);

        if ($advanceFreight > 0) {
            if ($existingSiTFreightTrx) {
                $existingSiTFreightTrx->update([
                    'amount' => $advanceFreight,
                    'account_id' => $stockInTransitAccount->id,
                    'counter_account_id' => $purchaseOrder->supplier->account_id,
                    'type' => 'debit',
                    'remarks' => "Freight payable (stock-in-transit) for truck no. $truckNo and bilty no. $biltyNo against contract ($contractNo). Amount adjusted from supplier account.",
                ]);
            } else {
                createTransaction(
                    $advanceFreight,
                    $stockInTransitAccount->id,
                    1,
                    $contractNo,
                    'debit',
                    'no',
                    [
                        'purpose' => "stock-in-transit",
                        'payment_against' => "thadda-freight",
                        'counter_account_id' => $purchaseOrder->supplier->account_id,
                        'against_reference_no' => "$truckNo/$biltyNo",
                        'remarks' => "Freight payable (stock-in-transit) for truck no. $truckNo and bilty no. $biltyNo against contract ($contractNo). Amount adjusted from supplier account."
                    ]
                );
            }


            if ($existingFreightTrx) {
                $existingFreightTrx->update([
                    'amount' => $advanceFreight,
                    'account_id' => $purchaseOrder->supplier->account_id,
                    'type' => 'credit',
                    'counter_account_id' => $stockInTransitAccount->id,
                    'remarks' => "Freight payable for truck no. $truckNo and bilty no. $biltyNo against contract ($contractNo). Amount adjusted from supplier account.",
                ]);
            } else {
                createTransaction(
                    $advanceFreight,
                    $purchaseOrder->supplier->account_id,
                    1,
                    $contractNo,
                    'credit',
                    'no',
                    [
                        'purpose' => "thadda-freight",
                        'payment_against' => "thadda-purchase",
                        'counter_account_id' => $stockInTransitAccount->id,
                        'against_reference_no' => "$truckNo/$biltyNo",
                        'remarks' => "Freight payable for truck no. $truckNo and bilty no. $biltyNo against contract ($contractNo). Amount adjusted from supplier account."
                    ]
                );
            }
        }

        if (
            isset($requestData['brokery_amount'], $requestData['broker_id']) &&
            $requestData['brokery_amount'] < 0
        ) {
            $existingTxn = Transaction::where('voucher_no', $contractNo)
                ->where('purpose', 'supplier-brokery')
                ->where('against_reference_no', "$truckNo/$biltyNo")
                ->exists();

            if (!$existingTxn) {
                $broker = Broker::find($requestData['broker_id']);
                if ($broker && $broker->account_id) {
                    $brokeryAmount = abs($requestData['brokery_amount']);

                    createTransaction(
                        $brokeryAmount,
                        $purchaseOrder->supplier->account_id,
                        1,
                        $contractNo,
                        'debit',
                        'no',
                        [
                            'purpose' => "supplier-brokery",
                            'payment_against' => "thadda-purchase",
                            'counter_account_id' => $broker->account_id,
                            'against_reference_no' => "$truckNo/$biltyNo",
                            'remarks' => "Brokery amount adjustment against contract ($contractNo). Transferred from supplier to broker."
                        ]
                    );

                    createTransaction(
                        $brokeryAmount,
                        $broker->account_id,
                        1,
                        $contractNo,
                        'credit',
                        'no',
                        [
                            'counter_account_id' => $purchaseOrder->supplier->account_id,
                            'purpose' => "supplier-brokery",
                            'payment_against' => "thadda-purchase",
                            'against_reference_no' => "$truckNo/$biltyNo",
                            'remarks' => "Brokery amount adjustment received from supplier for contract ($contractNo)."
                        ]
                    );
                }
            }
        }
    }

    private function saveSamplingResults($paymentRequest, $request)
    {
        if ($request->sampling_results) {
            foreach ($request->sampling_results as $result) {
                PaymentRequestSamplingResult::create([
                    'payment_request_data_id' => $paymentRequest->id,
                    'slab_type_id' => $result['slab_type_id'],
                    'name' => $result['slab_name'],
                    'checklist_value' => $result['checklist_value'],
                    'suggested_deduction' => $result['suggested_deduction'],
                    'applied_deduction' => $result['applied_deduction'],
                    'deduction_type' => $result['suggested_deduction'] > 0 ? 'amount' : 'percentage',
                    'deduction_amount' => $result['deduction_amount'],
                ]);
            }
        }

        // Save compulsory sampling results
        if ($request->compulsory_results) {
            foreach ($request->compulsory_results as $result) {
                PaymentRequestSamplingResult::create([
                    'payment_request_data_id' => $paymentRequest->id,
                    'slab_type_id' => $result['qc_param_id'],
                    'name' => $result['qc_name'],
                    'checklist_value' => 0,
                    'suggested_deduction' => 0,
                    'applied_deduction' => $result['applied_deduction'],
                    'deduction_type' => 'amount',
                    'deduction_amount' => $result['deduction_amount'],
                ]);
            }
        }
    }

    public function update(PaymentRequestRequest $request, $id)
    {
        return DB::transaction(function () use ($request, $id) {
            $paymentRequestData = PaymentRequestData::findOrFail($id);

            $requestData = $request->all();
            $requestData['is_loading'] = $request->loading_type === 'loading';

            $requestData['remaining_amount'] = $requestData['total_amount'] -
                ($requestData['paid_amount'] ?? 0) -
                ($requestData['payment_request_amount'] ?? 0) -
                ($requestData['freight_pay_request_amount'] ?? 0);

            $paymentRequestData->update($requestData);

            $paymentRequestData->paymentRequests()->delete();

            $this->createPaymentRequests($paymentRequestData, $request);

            return response()->json(['success' => 'Payment request updated successfully']);
        });
    }

    protected function createPaymentRequests($paymentRequestData, $request, $accountId = null)
    {
        if ($request->request_amount && $request->request_amount > 0) {
            PaymentRequest::create([

                'payment_request_data_id' => $paymentRequestData->id,
                'other_deduction_kg' => 0,
                'other_deduction_value' => 0,
                'request_type' => 'payment',
                'module_type' => 'freight_payment',
                'account_id' => $accountId,
                'payment_to_type' => $paymentRequestData->payment_to_type,
                'payment_to' => $paymentRequestData->payment_to,
                'amount' => $request->request_amount ?? 0
            ]);
        }
    }

    public function edit($id)
    {
        $arrivalTicket = ArrivalTicket::findOrFail($id);

        $requestedAmount = PaymentRequest::whereHas('paymentRequestData', function ($q) use ($arrivalTicket, $id) {
            $q->where('ticket_id', $id)
                ->where('module_type', 'freight_payment')
                ->where('purchase_order_id', $arrivalTicket->arrival_purchase_order_id);
        })
            ->where('module_type', 'freight_payment')
            ->sum('amount');

        $approvedAmount = PaymentRequest::whereHas('paymentRequestData', function ($q) use ($arrivalTicket) {
            $q->where('ticket_id', $arrivalTicket->id)
                ->where('module_type', 'freight_payment')
                ->where('purchase_order_id', $arrivalTicket->arrival_purchase_order_id);
        })
            ->where('module_type', 'freight_payment')
            ->where('status', 'approved')
            ->sum('amount');

        $paymentRequestData = PaymentRequestData::where('ticket_id', $arrivalTicket->id)
            ->where('module_type', 'freight_payment')
            ->where('purchase_order_id', $arrivalTicket->arrival_purchase_order_id)
            ->latest()
            ->first();

        $data = [
            'purchaseOrder' => $arrivalTicket->purchaseOrder,
            'arrivalTicket' => $arrivalTicket,
            'ticket' => $arrivalTicket,
            'requestedAmount' => $requestedAmount,
            'approvedAmount' => $approvedAmount,
            'isRequestApprovalPage' => false,
            'isTicketApprovalPage' => false,
            'isTicketPage' => true,
            'paymentRequestData' => $paymentRequestData,
        ];
        $data['vendors'] = Vendor::get();

        return view('management.procurement.raw_material.freight_request.create', $data);
    }

    public function view($id)
    {
        $arrivalTicket = ArrivalTicket::findOrFail($id);
        $freightPaymentRequest = FreightPaymentRequest::where('arrival_ticket_id', $arrivalTicket->id)
            ->where('arrival_slip_id', $arrivalTicket->arrivalSlip->id)
            ->first();

        $requestedAmount = PaymentRequest::whereHas('paymentRequestData', function ($q) use ($arrivalTicket, $id) {
            $q->where('ticket_id', $id)
                ->where('module_type', 'freight_payment')
                ->where('purchase_order_id', $arrivalTicket->arrival_purchase_order_id);
        })
            ->where('module_type', 'freight_payment')
            ->sum('amount');

        $approvedAmount = PaymentRequest::whereHas('paymentRequestData', function ($q) use ($arrivalTicket) {
            $q->where('ticket_id', $arrivalTicket->id)
                ->where('module_type', 'freight_payment')
                ->where('purchase_order_id', $arrivalTicket->arrival_purchase_order_id);
        })
            ->where('module_type', 'freight_payment')
            ->where('status', 'approved')
            ->sum('amount');

        $data = [
            'purchaseOrder' => $arrivalTicket->purchaseOrder,
            'arrivalTicket' => $arrivalTicket,
            'ticket' => $arrivalTicket,
            'requestedAmount' => $requestedAmount,
            'freightPaymentRequest' => $freightPaymentRequest,
            'approvedAmount' => $approvedAmount,
            'isRequestApprovalPage' => false,
            'isTicketApprovalPage' => false,
            'isTicketPage' => true,
        ];
        $data['vendors'] = Vendor::get();

        return view('management.procurement.raw_material.freight_request.create', $data);
    }

    public function updateStatus(Request $request)
    {
        $request->validate([
            'request_id' => 'required|exists:arrival_sampling_requests,id',
            'status' => 'required|in:approved,rejected,resampling'
        ]);

        $sampling = PurchaseSamplingRequest::find($request->request_id);

        if ($request->status == 'resampling') {
            PurchaseSamplingRequest::create([
                'company_id' => $sampling->company_id,
                'purchase_contract' => $sampling->purchase_contract,
                'sampling_type' => 'initial',
                'is_re_sampling' => 'yes',
                'is_done' => 'no',
                'remark' => null,
            ]);
            $sampling->is_resampling_made = 'yes';
        }

        $sampling->approved_status = $request->status;
        $sampling->save();

        return response()->json(['message' => 'Request status updated successfully!']);
    }

    public function pohouch_freight_payment_request_approval(Request $request)
    {

        return DB::transaction(function () use ($request) {
            $paymentRequest = PaymentRequest::findOrFail($request->payment_request_id);
            $paymentRequestData = $paymentRequest->paymentRequestData;
$vendorAccId = $paymentRequest->account_id;
            $purchaseOrder = $paymentRequestData->purchaseOrder;

            //  if ($request->has('total_amount') || $request->has('bag_weight')) {
            //    $this->updatePaymentRequestData($paymentRequestData, $request);
            // }

            if ($request->has('payment_request_amount')) {
                $paymentRequest->update(['amount' => $request->payment_request_amount]);
            }

            PaymentRequestApproval::create([
                'payment_request_id' => $request->payment_request_id,
                'payment_request_data_id' => $paymentRequest->payment_request_data_id,
                'ticket_id' => $request->ticket_id,
                'purchase_order_id' => $purchaseOrder->id,
                'approver_id' => auth()->user()->id,
                'status' => $request->status,
                'remarks' => $request->remarks,
                'amount' => $paymentRequest->amount,
                'request_type' => $paymentRequest->request_type
            ]);

            $paymentRequest->update(['status' => $request->status]);



            $ticket = ArrivalTicket::where('id', $request->ticket_id)->first();
            $paymentDetails = calculatePaymentDetails($ticket->id, 1);
            $qcAccountId = $ticket->qcProduct->account_id;
            $truckNo = $ticket->truck_no ?? 'N/A';
            $biltyNo = $ticket->bilty_no ?? 'N/A';
            $rate = $request->contract_rate;
            $grnNo = $ticket->arrivalSlip->unique_no;


            $amount = $paymentDetails['calculations']['supplier_net_amount'] ?? 0;
            $inventoryAmount = $paymentDetails['calculations']['inventory_amount'] ?? 0;

            $inventoryAmountwithFreight = $inventoryAmount + $request->net_amount;

            $txnInv = Transaction::where('grn_no', $grnNo)
                ->where('purpose', 'arrival-slip')
                ->first();
            if ($txnInv) {
                $txnInv->update([
                    'amount' => $inventoryAmountwithFreight,
                    'account_id' => $qcAccountId,
                    'counter_account_id' => $purchaseOrder->supplier->account_id,
                    'type' => 'debit',
                    'voucher_no' => $purchaseOrder->contract_no,
                    'grn_no' => $grnNo,
                    'remarks' => "Inventory ledger update for raw material arrival. Recording purchase of raw material (weight: $ticket->arrived_net_weight kg) at rate $rate/kg. Total amount: $inventoryAmountwithFreight to be paid to supplier."
                ]);
            } else {
                createTransaction(
                    $inventoryAmountwithFreight,
                    $qcAccountId,
                    1,
                    $purchaseOrder->contract_no,
                    'debit',
                    'no',
                    [
                        'grn_no' => $grnNo,
                        'counter_account_id' => $purchaseOrder->supplier->account_id,
                        'purpose' => "arrival-slip",
                        'payment_against' => "pohouch-purchase",
                        'against_reference_no' => "$truckNo/$biltyNo",
                        'remarks' => "Inventory ledger update for raw material arrival. Recording purchase of raw material (weight: $ticket->arrived_net_weight kg) at rate $rate/kg. Total amount: $inventoryAmountwithFreight to be paid to supplier."
                    ]
                );
            }

            $txnVendor = Transaction::where('grn_no', $grnNo)
                ->where('purpose', 'pohouch-freight')
                ->first();
            if ($txnVendor) {
                $txnVendor->update([
                    'amount' => $request->net_amount,
                    'account_id' => $vendorAccId,
                    'counter_account_id' => $purchaseOrder->supplier->account_id,
                    'type' => 'debit',
                    'voucher_no' => $purchaseOrder->contract_no,
                    'grn_no' => $grnNo,
                    'remarks' => "Inventory ledger update for raw material arrival. Recording purchase of raw material (weight: $ticket->arrived_net_weight kg) at rate $rate/kg. Total amount: $inventoryAmountwithFreight to be paid to supplier."
                ]);
            } else {
                createTransaction(
                    $request->net_amount,
                    $vendorAccId,
                    1,
                    $purchaseOrder->contract_no,
                    'credit',
                    'no',
                    [
                        'grn_no' => $grnNo,
                        'counter_account_id' => $qcAccountId,
                        'purpose' => "arrival-slip",
                        'payment_against' => "pohouch-purchase",
                        'against_reference_no' => "$truckNo/$biltyNo",
                        'remarks' => "Inventory ledger update for raw material arrival. Recording purchase of raw material (weight: $ticket->arrived_net_weight kg) at rate $rate/kg. Total amount: $inventoryAmountwithFreight to be paid to supplier."
                    ]
                );
            }



            return response()->json([
                'success' => 'Payment request ' . $request->status . ' successfully!'
            ]);
        });

    }
    public function getSlabsByPaymentRequestParams(Request $request)
    {
        $ticket = PurchaseTicket::with(['purchaseOrder', 'purchaseFreight'])->findOrFail($request->ticket_id);
        $purchaseOrder = $ticket->purchaseOrder;

        $requestedAmount = PaymentRequest::whereHas('paymentRequestData', fn($q) => $q->where('ticket_id', $ticket->id))
            ->where('request_type', 'payment')->sum('amount');

        $approvedAmount = PaymentRequest::whereHas('paymentRequestData', fn($q) => $q->where('ticket_id', $ticket->id))
            ->where('request_type', 'payment')->where('status', 'approved')->sum('amount');

        $pRsSumForFreight = PaymentRequest::whereHas('paymentRequestData', function ($query) use ($ticket) {
            $query->where('ticket_id', $ticket->id);
        })
            ->where('request_type', 'freight_payment')
            ->sum('amount');

        $tickets = PurchaseTicket::where('freight_status', 'completed')->get();
        $samplingRequest = null;
        $samplingRequestCompulsuryResults = collect();
        $samplingRequestResults = collect();
        $otherDeduction = null;

        if ($ticket) {
            $samplingRequest = PurchaseSamplingRequest::where('ticket_id', $request->ticket_id)
                ->whereIn('approved_status', ['approved', 'rejected'])
                ->latest()
                ->first();

            if ($samplingRequest) {
                $rmPoSlabs = collect();
                if ($purchaseOrder && $samplingRequest->arrival_product_id) {
                    $rmPoSlabs = ProductSlabForRmPo::where('arrival_purchase_order_id', $purchaseOrder->id)
                        ->where('product_id', $samplingRequest->arrival_product_id)
                        ->get()
                        ->groupBy('product_slab_type_id');
                }

                $samplingRequestCompulsuryResults = PurchaseSamplingResultForCompulsury::where('purchase_sampling_request_id', $samplingRequest->id)->get();
                $samplingRequestResults = PurchaseSamplingResult::where('purchase_sampling_request_id', $samplingRequest->id)->get();

                $productSlabCalculations = null;
                if ($samplingRequest->arrival_product_id) {
                    $productSlabCalculations = ProductSlab::where('product_id', $samplingRequest->arrival_product_id)->get();
                }

                foreach ($samplingRequestResults as &$result) {
                    $matchingSlabs = [];
                    $result->rm_po_slabs = $rmPoSlabs->get($result->product_slab_type_id, []);

                    if ($productSlabCalculations) {
                        $matchingSlabs = $productSlabCalculations->where('product_slab_type_id', $result->product_slab_type_id)
                            ->values()
                            ->all();
                        if (!empty($matchingSlabs)) {
                            $result->deduction_type = $matchingSlabs[0]->deduction_type;
                        }
                    }
                    $result->matching_slabs = $matchingSlabs;
                }
            }

            $existingPaymentRequestData = PaymentRequestData::where('ticket_id', $request->ticket_id)->first();
        }

        if (isset($request->is_debug)) {
            $orignalsamplingRequestResults = $samplingRequestResults;
            $samplingRequestResults = $samplingRequestResults->filter(function ($result) {
                return $result->applied_deduction;
            });
        }

        $html = view('management.procurement.raw_material.freight_request.snippets.requestPurchaseForm', [
            'tickets' => $tickets,
            'ticket' => $ticket,
            'purchaseOrder' => $purchaseOrder,
            'samplingRequest' => $samplingRequest,
            'samplingRequestCompulsuryResults' => $samplingRequestCompulsuryResults,
            'samplingRequestResults' => $samplingRequestResults,
            'pRsSumForFreight' => $pRsSumForFreight,
            'otherDeduction' => $otherDeduction,
            'requestedAmount' => $requestedAmount,
            'approvedAmount' => $approvedAmount,
        ])->render();

        return response()->json(['success' => true, 'html' => $html]);
    }



}
