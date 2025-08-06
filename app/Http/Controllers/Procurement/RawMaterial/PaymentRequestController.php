<?php

namespace App\Http\Controllers\Procurement\RawMaterial;

use App\Http\Controllers\Controller;
use App\Http\Requests\Procurement\PaymentRequestRequest;
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
use App\Models\Procurement\PaymentRequestApproval;
use App\Models\Procurement\PaymentRequestData;
use App\Models\Procurement\PaymentRequestSamplingResult;
use App\Models\Procurement\PurchaseFreight;
use App\Models\Product;
use App\Models\PurchaseTicket;
use Illuminate\Http\Request;
use App\Models\PurchaseSamplingRequest;
use App\Models\TruckSizeRange;
use Illuminate\Support\Facades\DB;

class PaymentRequestController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('management.procurement.raw_material.payment_request.index');
    }

    /**
     * Get list of tickets with completed freight status.
     */
    public function getList(Request $request)
    {
        $query = PurchaseTicket::where('freight_status', 'completed')
            // ->whereHas('purchaseOrder.arrivalTickets', function ($q) {
            //     return $q->where('is_ticket_verified', 1);
            //     // ->where('is_ticket_verified', 1);
            //     // $q->where('freight_status', 'completed')
            //     //     ->where('is_ticket_verified', 1);
            // })
            ->with([
                'paymentRequestData.paymentRequests',
                'paymentRequestData.paymentRequests.approvals',
                'purchaseOrder.supplier',
                'product',
                'qcProduct',
                'purchaseFreight',
                'paymentRequestData' => function ($query) {
                    $query->with(['paymentRequests' => function ($q) {
                        $q->selectRaw('payment_request_data_id, request_type, status, SUM(amount) as total_amount')
                            ->groupBy('payment_request_data_id', 'request_type', 'status');
                    }]);
                }
            ])
            ->when($request->filled('company_location_id'), function ($q) use ($request) {
                return $q->whereHas('purchaseOrder', function ($query) use ($request) {
                    $query->where('company_location_id', $request->company_location_id);
                });
            })
            ->when($request->filled('supplier_id'), function ($q) use ($request) {
                return $q->whereHas('purchaseOrder', function ($query) use ($request) {
                    $query->where('supplier_id', $request->supplier_id);
                });
            })
            ->when($request->filled('daterange'), function ($q) use ($request) {
                $dates = explode(' - ', $request->daterange);
                $startDate = \Carbon\Carbon::createFromFormat('m/d/Y', trim($dates[0]))->format('Y-m-d');
                $endDate = \Carbon\Carbon::createFromFormat('m/d/Y', trim($dates[1]))->format('Y-m-d');

                return $q->whereDate('created_at', '>=', $startDate)
                    ->whereDate('created_at', '<=', $endDate);
            })
            ->orderByDesc(function ($query) {
                $query->select('created_at')
                    ->from('purchase_freights')
                    ->whereColumn('purchase_freights.purchase_ticket_id', 'purchase_tickets.id')
                    ->limit(1);
            })
            ->orderByDesc('created_at');

        // if ($request->has('supplier_id') && $request->supplier_id != '') {
        //     $query->whereHas('purchaseOrder', function ($q) use ($request) {
        //         $q->where('supplier_id', $request->supplier_id);
        //     });
        // }

        if ($request->has('product_id') && $request->product_id != '') {
            $query->where('qc_product', $request->product_id);
        }

        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('unique_no', 'like', "%{$search}%")
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
                'created_at' => $ticket->purchaseFreight->created_at ?? $ticket->created_at
            ];

            return $ticket;
        });

        return view('management.procurement.raw_material.payment_request.getList', [
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

        return view('management.procurement.raw_material.payment_request.create', $data);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(PaymentRequestRequest $request)
    {
        return DB::transaction(function () use ($request) {
            $requestData = $request->all();
            $requestData['is_loading'] = $request->loading_type === 'loading';
            $requestData['module_type'] = 'purchase_order';

            $stockInTransitAccount = Account::where('name', 'Stock in Transit')->first();
            $ticket = PurchaseTicket::where('id', $requestData['ticket_id'])->first();
            $purchaseOrder = ArrivalPurchaseOrder::where('id', $requestData['purchase_order_id'])->first();

            $existingApprovals = PaymentRequestData::where('purchase_order_id', $purchaseOrder->id)
                ->where('ticket_id', $ticket->id)
                ->first();

            $paymentRequestData = PaymentRequestData::create($requestData);

            $this->createPaymentRequests($paymentRequestData, $request);

            if (isset($request->sampling_results) || isset($request->compulsory_results)) {
                $this->saveSamplingResults($paymentRequestData, $request);
            }

            $this->manageLedgerCalculations($requestData, $purchaseOrder, $ticket, $stockInTransitAccount, $existingApprovals);

            $message = $request->freight_pay_request_amount ?
                'Payment and freight payment requests created successfully' :
                'Payment request created successfully';

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
                    'against_reference_no' => "$truckNo/$biltyNo",
                    'remarks' => $supplierData['remarks']
                ]
            );
        }

        $transitTxn = Transaction::where('voucher_no', $contractNo)
            ->where('purpose', 'stock-in-transit')
            ->where('against_reference_no', "$truckNo/$biltyNo")
            ->first();

        $transitData = [
            'amount' => $inventoryAmount,
            'account_id' => $stockInTransitAccount->id,
            'type' => 'debit',
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
                    'payment_against' => "thadda-purchase",
                    'against_reference_no' => "$truckNo/$biltyNo",
                    'remarks' => $transitData['remarks']
                ]
            );
        }


        if (!$existingApprovals && $purchaseOrder->broker_one_id && $purchaseOrder->broker_one_commission && $loadingWeight) {
            $amount = ($loadingWeight * $purchaseOrder->broker_one_commission);

            createTransaction(
                $amount,
                $purchaseOrder->broker->account_id,
                1,
                $purchaseOrder->contract_no,
                'credit',
                'no',
                [
                    'purpose' => "broker",
                    'payment_against' => "thadda-purchase",
                    'against_reference_no' => "$truckNo/$biltyNo",
                    'remarks' => 'Recording accounts payable for "Thadda" purchase. Amount to be paid to broker.'
                ]
            );
        }

        if (!$existingApprovals && $purchaseOrder->broker_two_id && $purchaseOrder->broker_two_commission && $loadingWeight) {
            $amount = ($loadingWeight * $purchaseOrder->broker_two_commission);

            createTransaction(
                $amount,
                $purchaseOrder->brokerTwo->account_id,
                1,
                $purchaseOrder->contract_no,
                'credit',
                'no',
                [
                    'purpose' => "broker",
                    'payment_against' => "thadda-purchase",
                    'against_reference_no' => "$truckNo/$biltyNo",
                    'remarks' => 'Recording accounts payable for "Thadda" purchase. Amount to be paid to broker.'
                ]
            );
        }

        if (!$existingApprovals && $purchaseOrder->broker_three_id && $purchaseOrder->broker_three_commission && $loadingWeight) {
            $amount = ($loadingWeight * $purchaseOrder->broker_three_commission);

            createTransaction(
                $amount,
                $purchaseOrder->brokerThree->account_id,
                1,
                $purchaseOrder->contract_no,
                'credit',
                'no',
                [
                    'purpose' => "broker",
                    'payment_against' => "thadda-purchase",
                    'against_reference_no' => "$truckNo/$biltyNo",
                    'remarks' => 'Recording accounts payable for "Thadda" purchase. Amount to be paid to broker.'
                ]
            );
        }

        $existingFreightTrx = Transaction::where('voucher_no', $contractNo)
            ->where('purpose', 'thadda-freight')
            ->where('against_reference_no', "$truckNo/$biltyNo")
            ->first();
        $advanceFreight = (int)($requestData['advance_freight']);

        if ($existingFreightTrx) {
            $existingFreightTrx->update([
                'amount' => $advanceFreight,
                'account_id' => $purchaseOrder->supplier->account_id,
                'type' => 'credit',
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
                    'against_reference_no' => "$truckNo/$biltyNo",
                    'remarks' => "Freight payable for truck no. $truckNo and bilty no. $biltyNo against contract ($contractNo). Amount adjusted from supplier account."
                ]
            );
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
                        $advanceFreight,
                        $purchaseOrder->supplier->account_id,
                        1,
                        $contractNo,
                        'credit',
                        'no',
                        [
                            'purpose' => "thadda-freight",
                            'payment_against' => "thadda-purchase",
                            'against_reference_no' => "$truckNo/$biltyNo",
                            'remarks' => "Freight payable for truck no. $truckNo and bilty no. $biltyNo against contract ($contractNo). Amount adjusted from supplier account."
                        ]
                    );

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

            // Prepare update data
            // $requestData = $request->validated();
            $requestData = $request->all();
            $requestData['is_loading'] = $request->loading_type === 'loading';

            // Calculate remaining amount
            $requestData['remaining_amount'] = $requestData['total_amount'] -
                ($requestData['paid_amount'] ?? 0) -
                ($requestData['payment_request_amount'] ?? 0) -
                ($requestData['freight_pay_request_amount'] ?? 0);

            // Update main data
            $paymentRequestData->update($requestData);

            // Delete existing payment requests and create new ones
            $paymentRequestData->paymentRequests()->delete();
            $this->createPaymentRequests($paymentRequestData, $request);

            // Update sampling results if exists
            if (isset($request->sampling_results) || isset($request->compulsory_results) || isset($request->other_deduction)) {
                $this->updateSamplingResults($paymentRequestData, $request);
            }

            return response()->json(['success' => 'Payment request updated successfully']);
        });
    }

    protected function createPaymentRequests($paymentRequestData, $request)
    {
        if (isset($request->ticket_id)) {
            $ticket = PurchaseTicket::find($request->ticket_id);
            if ($ticket && $ticket->purchaseOrder) {
                // $ticket->purchaseOrder->update(['bag_weight' => $request->bag_weight]);
                $ticket->update(['bag_weight' => $request->bag_weight, 'bag_rate' => $request->bag_rate]);
            }
        }

        if ($request->payment_request_amount && $request->payment_request_amount > 0) {
            PaymentRequest::create([
                'payment_request_data_id' => $paymentRequestData->id,
                'other_deduction_kg' => $request->other_deduction['kg_value'] ?? 0,
                'other_deduction_value' => $request->other_deduction['kg_amount'] ?? 0,
                'request_type' => 'payment',
                'module_type' => 'purchase_order',
                'amount' => $request->payment_request_amount ?? 0
            ]);
        }

        if ($request->freight_pay_request_amount && $request->freight_pay_request_amount > 0) {
            PaymentRequest::create([
                'payment_request_data_id' => $paymentRequestData->id,
                'request_type' => 'freight_payment',
                'module_type' => 'purchase_order',
                'other_deduction_kg' => $request->other_deduction['kg_value'] ?? 0,
                'other_deduction_value' => $request->other_deduction['kg_amount'] ?? 0,
                'amount' => $request->freight_pay_request_amount
            ]);
        }
    }

    protected function updateSamplingResults($paymentRequestData, $request)
    {
        $paymentRequestData->samplingResults()->delete();

        if (isset($request->sampling_results)) {
            foreach ($request->sampling_results as $result) {
                PaymentRequestSamplingResult::create([
                    'payment_request_data_id' => $paymentRequestData->id,
                    'slab_type_id' => $result['slab_type_id'] ?? null,
                    'name' => $result['slab_name'] ?? '',
                    'checklist_value' => $result['checklist_value'] ?? 0,
                    'suggested_deduction' => $result['suggested_deduction'] ?? 0,
                    'applied_deduction' => $result['applied_deduction'] ?? 0,
                    'deduction_type' => $result['deduction_type'] ?? 'amount',
                    'deduction_amount' => $result['deduction_amount'] ?? 0,
                    'is_other_deduction' => false
                ]);
            }
        }

        if (isset($request->compulsory_results)) {
            foreach ($request->compulsory_results as $result) {
                PaymentRequestSamplingResult::create([
                    'payment_request_data_id' => $paymentRequestData->id,
                    'qc_param_id' => $result['qc_param_id'] ?? null,
                    'name' => $result['qc_name'] ?? '',
                    'applied_deduction' => $result['applied_deduction'] ?? 0,
                    'deduction_amount' => $result['deduction_amount'] ?? 0,
                ]);
            }
        }
    }

    public function edit($id)
    {
        $data['ticket'] = $ticket = PurchaseTicket::with(['purchaseOrder', 'purchaseFreight'])->findOrFail($id);
        $data['truckSizeRanges'] = TruckSizeRange::where('status', 'active')->get();
        $data['products'] = Product::where('product_type', 'raw_material')->get();

        $paymentRequestData = PaymentRequestData::where('ticket_id', $ticket->id)->where('module_type', 'purchase_order')->orderByDesc('id')->first();

        $requestedAmount = PaymentRequest::whereHas('paymentRequestData', fn($q) => $q->where('ticket_id', $ticket->id))
            ->where('request_type', 'payment')->sum('amount');

        $approvedAmount = PaymentRequest::whereHas('paymentRequestData', fn($q) => $q->where('ticket_id', $ticket->id))
            ->where('request_type', 'payment')->where('status', 'approved')->sum('amount');

        $pRsSumForFreight = PaymentRequest::whereHas('paymentRequestData', function ($query) use ($ticket) {
            $query->where('ticket_id', $ticket->id);
        })
            ->where('request_type', 'freight_payment')
            ->sum('amount');

        $samplingRequest = null;
        $samplingRequestCompulsuryResults = collect();
        $samplingRequestResults = collect();
        $otherDeduction = null;

        if ($ticket) {
            $samplingRequest = PurchaseSamplingRequest::where('purchase_ticket_id', $id)
                ->whereIn('approved_status', ['approved', 'rejected'])
                ->latest()
                ->first();

            if ($samplingRequest) {
                $rmPoSlabs = collect();
                if ($ticket->purchaseOrder && $samplingRequest->arrival_product_id) {
                    $rmPoSlabs = ProductSlabForRmPo::where('arrival_purchase_order_id', $ticket->purchaseOrder->id)
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

            $otherDeduction = PaymentRequest::whereHas('paymentRequestData', function ($query) use ($ticket) {
                $query->where('ticket_id', $ticket->id);
            })->select('other_deduction_kg', 'other_deduction_value')
                ->latest()
                ->first();
        }

        if (isset($request->is_debug)) {
            $orignalsamplingRequestResults = $samplingRequestResults;
            $samplingRequestResults = $samplingRequestResults->filter(function ($result) {
                return $result->applied_deduction;
            });
        }

        $brokers = Broker::all();

        $data['html'] = view('management.procurement.raw_material.payment_request.snippets.requestPurchaseForm', [
            'ticket' => $ticket,
            'brokers' => $brokers,
            'paymentRequestData' => $paymentRequestData,
            'samplingRequest' => $samplingRequest,
            'purchaseOrder' => $ticket->purchaseOrder,
            'samplingRequestCompulsuryResults' => $samplingRequestCompulsuryResults,
            'samplingRequestResults' => $samplingRequestResults,
            'pRsSumForFreight' => $pRsSumForFreight,
            'requestedAmount' => $requestedAmount,
            'otherDeduction' => $otherDeduction,
            'approvedAmount' => $approvedAmount,
            'isRequestApprovalPage' => false
        ])->render();

        return view('management.procurement.raw_material.payment_request.create', $data);
    }

    public function editOld($id)
    {
        $paymentRequestData = PaymentRequestData::with([
            'ticket.purchaseOrder',
            'samplingResults.slabType',
            'paymentRequests'
        ])->findOrFail($id);

        $requestedAmount = PaymentRequest::whereHas('paymentRequestData', fn($q) => $q->where('ticket_id', $paymentRequestData->ticket_id))
            ->where('request_type', 'payment')->sum('amount');

        $approvedAmount = PaymentRequest::whereHas('paymentRequestData', fn($q) => $q->where('ticket_id', $paymentRequestData->ticket_id))
            ->where('request_type', 'payment')->where('status', 'approved')->sum('amount');

        $pRsSumForFreight = PaymentRequest::whereHas('paymentRequestData', function ($query) use ($paymentRequestData) {
            $query->where('ticket_id', $paymentRequestData->ticket_id);
        })
            ->where('request_type', 'freight_payment')
            ->sum('amount');

        $paymentRequest = $paymentRequestData->paymentRequests->where('request_type', 'payment')->first();
        $freightRequest = $paymentRequestData->paymentRequests->where('request_type', 'freight_payment')->first();

        return view('management.procurement.raw_material.payment_request.edit', [
            'paymentRequestData' => $paymentRequestData,
            'paymentRequest' => $paymentRequest,
            'freightRequest' => $freightRequest,
            'requestedAmount' => $requestedAmount,
            'approvedAmount' => $approvedAmount,
            'pRsSumForFreight' => $pRsSumForFreight,
            'samplingResults' => $paymentRequestData->samplingResults,
        ]);
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

        $html = view('management.procurement.raw_material.payment_request.snippets.requestPurchaseForm', [
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
