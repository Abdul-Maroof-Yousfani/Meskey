<?php

namespace App\Http\Controllers\Procurement\RawMaterial;

use App\Http\Controllers\Controller;
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
use Illuminate\Http\Request;
use App\Models\PurchaseSamplingRequest;
use App\Models\PurchaseTicket;
use App\Models\TruckSizeRange;
use Illuminate\Support\Facades\DB;
use App\Models\AuditLog;
use App\Services\AuditLogService;

class TicketPaymentRequestController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('management.procurement.raw_material.ticket_payment_request.index');
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
                $query->with([
                    'paymentRequests' => function ($q) {
                        $q->selectRaw('payment_request_data_id, request_type, status, SUM(amount) as total_amount')
                            ->where('status', '!=', 'rejected')
                            ->groupBy('payment_request_data_id', 'request_type', 'status');
                    }
                ]);
            }
        ])
            ->whereIn('location_id', getUserCurrentCompanyLocations())
            ->where('is_ticket_verified', 1)
            ->when($request->filled('company_location_id'), function ($q) use ($request) {
                return $q->where('location_id', $request->company_location_id);
            })
            ->when($request->filled('supplier_id'), function ($q) use ($request) {
                return $q->where('accounts_of_id', $request->supplier_id);
            })
            ->when($request->filled('contract_no'), function ($q) use ($request) {
                return $q->whereHas('purchaseOrder', function ($query) use ($request) {
                    $query->where('contract_no', 'like', "%{$request->contract_no}%");
                });
            })
            ->when($request->filled('bilty_no'), function ($q) use ($request) {
                return $q->where('bilty_no', 'like', "%{$request->bilty_no}%");
            })
            ->when($request->filled('truck_no'), function ($q) use ($request) {
                return $q->where('truck_no', 'like', "%{$request->truck_no}%");
            })
            ->when($request->filled('loading_date'), function ($q) use ($request) {
                return $q->whereDate('loading_date', $request->loading_date);
            })
            ->when($request->filled('amount_for_filter'), function ($q) use ($request) {
                return $q->whereHas('paymentRequestData', function ($query) use ($request) {
                    $query->where('total_amount', 'like', "%{$request->amount_for_filter}%");
                });
            })
            ->when($request->filled('requested_amount_for_filter'), function ($q) use ($request) {
                return $q->whereHas('paymentRequestData', function ($query) use ($request) {
                    $query->whereHas('paymentRequests', function ($pq) use ($request) {
                        $pq->where('amount', 'like', "%{$request->requested_amount_for_filter}%");
                    });
                });
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
                $q->where('purchase_type', '!=', 'gate_buying');
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
                        if ($pRequest->request_type == 'freight_payment') {
                            if ($pRequest->request_type == 'freight_payment') {

                            }
                            $totalFreightSum += $pRequest->total_amount;
                            if ($pRequest->status == 'approved') {
                                $approvedFreightSum += $pRequest->total_amount;
                            }
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

        return view('management.procurement.raw_material.ticket_payment_request.getList', [
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

    /**
     * Store a newly created resource in storage.
     */
    public function store(TicketPaymentRequestRequest $request)
    {
        return DB::transaction(function () use ($request) {
            $requestData = $request->all();
            $requestData['is_loading'] = $request->loading_type === 'loading';
            $requestData['module_type'] = 'ticket';

            $stockInTransitAccount = Account::where('name', 'Stock in Transit')->first();
            $ticket = ArrivalTicket::where('id', $requestData['ticket_id'])->first();
            $purchaseOrder = ArrivalPurchaseOrder::where('id', $requestData['purchase_order_id'])->first();

            $existingApprovals = PaymentRequestData::where('purchase_order_id', $purchaseOrder->id)
                ->where('ticket_id', $ticket->id)
                ->count();

            $previousData = PaymentRequestData::where(function ($q) use ($ticket) {
                    $q->where('ticket_id', $ticket->id)
                      ->orWhere('arrival_ticket_id', $ticket->id);
                })
                ->where('purchase_order_id', $purchaseOrder->id)
                ->latest('id')
                ->first();
            if (!$previousData) {
                $previousData = PaymentRequestData::where(function ($q) use ($ticket) {
                        $q->where('ticket_id', $ticket->id)
                          ->orWhere('arrival_ticket_id', $ticket->id);
                    })
                    ->latest('id')
                    ->first();
            }

            $samplingRequest = ArrivalSamplingRequest::where('arrival_ticket_id', $ticket->id)
                ->whereIn('approved_status', ['approved', 'rejected'])
                ->latest()
                ->first();
            if (!$samplingRequest) {
                $samplingRequest = PurchaseSamplingRequest::where('purchase_ticket_id', $ticket->id)
                    ->whereIn('approved_status', ['approved', 'rejected'])
                    ->latest()
                    ->first();
            }

            $prevPaymentRequest = null;
            $oldPaymentAmount = 0;
            $oldFreightAmount = 0;
            if ($previousData) {
                $prevPaymentRequest = PaymentRequest::where('payment_request_data_id', $previousData->id)
                    ->where('request_type', 'payment')
                    ->latest('id')
                    ->first();
            }
            if (!$prevPaymentRequest) {
                $prevPaymentRequest = PaymentRequest::whereHas('paymentRequestData', function ($query) use ($ticket) {
                    $query->where('ticket_id', $ticket->id)->orWhere('arrival_ticket_id', $ticket->id);
                })->where('request_type', 'payment')->latest('id')->first();
            }

            $newPaymentAmount = (float) ($request->payment_request_amount ?? 0);
            $newFreightAmount = (float) ($request->freight_pay_request_amount ?? 0);

            // Calculate cumulative requested amounts across all tranches for this ticket
            $oldTotalPaymentSum = (float) PaymentRequest::whereHas('paymentRequestData', function ($query) use ($ticket) {
                $query->where('ticket_id', $ticket->id)->orWhere('arrival_ticket_id', $ticket->id);
            })->where('request_type', 'payment')->sum('amount');

            $oldTotalFreightSum = (float) PaymentRequest::whereHas('paymentRequestData', function ($query) use ($ticket) {
                $query->where('ticket_id', $ticket->id)->orWhere('arrival_ticket_id', $ticket->id);
            })->where('request_type', 'freight_payment')->sum('amount');

            $newTotalPaymentSum = $oldTotalPaymentSum + $newPaymentAmount;
            $newTotalFreightSum = $oldTotalFreightSum + $newFreightAmount;

            $accountId = $purchaseOrder->supplier->account_id ?? null;

            // Calculate net weight for lumpsum calculations
            $loadingW = (float) (
                ($ticket && $ticket->freight ? ($ticket->freight->loading_weight ?? $ticket->freight->arrived_weight ?? $ticket->freight->loaded_weight) : null)
                ?? ($ticket ? ($ticket->arrived_net_weight ?? 0) : 0)
                ?? ($previousData->billing_weight ?? $previousData->loading_weight ?? 0)
            );
            $bagsCount = (float) (
                ($ticket && $ticket->freight ? ($ticket->freight->no_of_bags ?? $ticket->freight->bags) : null)
                ?? ($ticket ? ($ticket->bags ?? $ticket->bag_count ?? $ticket->total_bags ?? 0) : 0)
                ?? ($previousData->no_of_bags ?? 0)
            );
            $oldBagW = (float) ($previousData->bag_weight ?? ($ticket ? $ticket->bag_weight : 0));
            $oldNetWeight = $loadingW - ($oldBagW * $bagsCount);

            $paymentDetailsForLog = calculatePaymentDetails($ticket->id, 1);
            $helperLump = $paymentDetailsForLog['deductions']['sampling_deduction_details']['lumpsum'] ?? null;
            $oldLumpAmount = $samplingRequest ? (float) $samplingRequest->lumpsum_deduction : 0;
            $oldLumpKgs = $samplingRequest ? (float) $samplingRequest->lumpsum_deduction_kgs : 0;
            $oldLumpAmountTotal = $helperLump ? (float) ($helperLump['amount_deduction'] ?? 0) : ($oldLumpAmount * $oldNetWeight);
            $ratePerKg = (float) ($purchaseOrder->rate_per_kg ?? 0);
            $oldLumpKgsTotal = $helperLump ? (float) ($helperLump['kgs_deduction'] ?? 0) : ($oldNetWeight > 0 ? (($oldLumpKgs * $oldNetWeight / 100) * $ratePerKg) : 0);

            $newLumpAmount = $request->input('lumpsum.amount.deduction_value') ?? $request->input('lump_sum_deduction_rupees');
            $newLumpAmountTotal = $request->input('lumpsum.amount.deduction_amount') ?? $request->input('lump_sum_deduction_rupees_total');
            $newLumpKgs = $request->input('lumpsum.kgs.deduction_value') ?? $request->input('lump_sum_deduction_kgs');
            $newLumpKgsTotal = $request->input('lumpsum.kgs.deduction_amount') ?? $request->input('lump_sum_deduction_kgs_total');

            // Save lumpsum deduction updates to sampling request if provided
            if ($samplingRequest && ($request->has('lumpsum') || $request->has('lump_sum_deduction_rupees') || $request->has('lump_sum_deduction_kgs'))) {
                $samplingUpdates = [];
                if ($newLumpAmount !== null && $newLumpAmount !== '') {
                    $samplingUpdates['lumpsum_deduction'] = (float) $newLumpAmount;
                }
                if ($newLumpKgs !== null && $newLumpKgs !== '') {
                    $samplingUpdates['lumpsum_deduction_kgs'] = (float) $newLumpKgs;
                }
                if (!empty($samplingUpdates)) {
                    $samplingRequest->update($samplingUpdates);
                }
            }

            $requestData['account_id'] = $accountId;
            $requestData['arrival_ticket_id'] = $ticket->id ?? null;
            $requestData['grn_no'] = $ticket->arrivalSlip->unique_no ?? null;
            $requestData['broker_id'] = $request->broker_id ?: null;
            $requestData['brokery_amount'] = $request->brokery_amount ?? 0;
            $requestData['commission_amount'] = $request->supplier_commission ?? ($request->commission_amount ?? 0);

            $paymentRequestData = PaymentRequestData::create($requestData);

            $this->createPaymentRequests($paymentRequestData, $request, $accountId);

            if (isset($request->sampling_results) || isset($request->compulsory_results)) {
                $this->saveSamplingResults($paymentRequestData, $request);
            }

            $this->manageLedgerCalculations($requestData, $purchaseOrder, $ticket, $stockInTransitAccount, $existingApprovals);

            // Audit Log: Collect and compare all form fields
            $oldOtherDedKg = $prevPaymentRequest ? (float) $prevPaymentRequest->other_deduction_kg : 0;
            $oldOtherDedVal = $prevPaymentRequest ? (float) $prevPaymentRequest->other_deduction_value : 0;
            $oldRerateKg = $prevPaymentRequest ? (float) $prevPaymentRequest->rerate_on_access_weight_kg : 0;
            $oldRerateRate = $prevPaymentRequest ? (float) $prevPaymentRequest->rerate_on_access_weight_rate : 0;
            $oldRerateAmount = $prevPaymentRequest ? (float) $prevPaymentRequest->rerate_on_access_weight_amount : 0;
            $oldOtherAdj = $prevPaymentRequest ? (float) $prevPaymentRequest->other_adjustment_amount : 0;
            $oldFillingBagsNo = $prevPaymentRequest ? (float) $prevPaymentRequest->no_of_filling_bags : 0;
            $oldFillingBagsRate = $prevPaymentRequest ? (float) $prevPaymentRequest->filling_bag_rate : 0;
            $oldFillingBagsAmount = $prevPaymentRequest ? (float) $prevPaymentRequest->filling_bag_amount : 0;
            $oldExemptedWeight = $previousData ? (float) $previousData->exempted_weight : 0;
            $oldBillingWeight = $previousData ? (float) $previousData->billing_weight : 0;

            $oldTotalAmount = $previousData ? (float) $previousData->total_amount : 0;
            $totalTicketAmount = (float) ($request->total_amount ?? ($previousData->total_amount ?? 0));
            $oldRemainingAmount = max(0, $totalTicketAmount - $oldTotalPaymentSum);
            $newRemainingAmount = max(0, $totalTicketAmount - $newTotalPaymentSum);
            $oldPaidAmount = $previousData ? (float) $previousData->paid_amount : 0;

            $oldBagWeight = $oldBagW;
            $oldBagWeightTotal = $previousData ? (float) $previousData->bag_weight_total : ($oldBagWeight * $bagsCount);
            $oldBagWeightAmount = $previousData ? (float) $previousData->bag_weight_amount : ($ratePerKg * $oldBagWeightTotal);
            $oldBagRate = $previousData ? (float) $previousData->bag_rate : ($ticket ? (float) $ticket->bag_rate : 0);
            $oldBagRateAmount = $previousData ? (float) $previousData->bag_rate_amount : ($oldBagRate * $bagsCount);
            $oldLoadingWeighbridge = $previousData ? (float) $previousData->loading_weighbridge_amount : 0;
            $oldBrokerId = $previousData ? $previousData->broker_id : null;
            $oldBrokeryAmount = $previousData ? (float) $previousData->brokery_amount : 0;
            $oldCommissionAmount = $previousData ? (float) $previousData->commission_amount : 0;
            $oldAdvanceFreight = $previousData ? (float) $previousData->advance_freight : ($ticket && $ticket->freight ? (float) $ticket->freight->advance_freight : 0);
            $oldNotes = $previousData ? $previousData->notes : null;

            $newFillingBagsNo = (float) ($request->filling_bags_no ?? 0);
            $newFillingBagsRate = (float) ($request->filling_bags_rate ?? 0);
            $newFillingBagsAmount = (float) ($request->filling_bags_amount ?? ($newFillingBagsNo * $newFillingBagsRate));

            $fieldsToTrack = [
                'payment_request_amount'         => ['old' => $oldTotalPaymentSum, 'new' => $newTotalPaymentSum, 'type' => 'numeric'],
                'freight_pay_request_amount'     => ['old' => $oldTotalFreightSum, 'new' => $newTotalFreightSum, 'type' => 'numeric'],
                'total_amount'                   => ['old' => $oldTotalAmount, 'new' => (float) ($request->total_amount ?? 0), 'type' => 'numeric'],
                'remaining_amount'               => ['old' => $oldRemainingAmount, 'new' => $newRemainingAmount, 'type' => 'numeric'],
                'paid_amount'                    => ['old' => $oldPaidAmount, 'new' => (float) ($request->paid_amount ?? 0), 'type' => 'numeric'],
                'advance_freight'                => ['old' => $oldAdvanceFreight, 'new' => (float) ($request->advance_freight ?? 0), 'type' => 'numeric'],

                'lumpsum_deduction'              => ['old' => $oldLumpAmount, 'new' => $newLumpAmount !== null && $newLumpAmount !== '' ? (float) $newLumpAmount : $oldLumpAmount, 'type' => 'numeric'],
                'lumpsum_deduction_amount'       => ['old' => $oldLumpAmountTotal, 'new' => $newLumpAmountTotal !== null && $newLumpAmountTotal !== '' ? (float) $newLumpAmountTotal : $oldLumpAmountTotal, 'type' => 'numeric'],
                'lumpsum_deduction_kgs'          => ['old' => $oldLumpKgs, 'new' => $newLumpKgs !== null && $newLumpKgs !== '' ? (float) $newLumpKgs : $oldLumpKgs, 'type' => 'numeric'],
                'lumpsum_deduction_kgs_amount'   => ['old' => $oldLumpKgsTotal, 'new' => $newLumpKgsTotal !== null && $newLumpKgsTotal !== '' ? (float) $newLumpKgsTotal : $oldLumpKgsTotal, 'type' => 'numeric'],

                'other_deduction_kg'             => ['old' => $oldOtherDedKg, 'new' => (float) ($request->input('other_deduction.kg_value') ?? 0), 'type' => 'numeric'],
                'other_deduction_value'          => ['old' => $oldOtherDedVal, 'new' => (float) ($request->input('other_deduction.kg_amount') ?? $request->input('other_deduction.deduction_amount') ?? 0), 'type' => 'numeric'],
                'rerate_on_access_weight_kg'     => ['old' => $oldRerateKg, 'new' => (float) ($request->rerate_on_access_weight_kg ?? 0), 'type' => 'numeric'],
                'rerate_on_access_weight_rate'   => ['old' => $oldRerateRate, 'new' => (float) ($request->rerate_on_access_weight_rate ?? 0), 'type' => 'numeric'],
                'rerate_on_access_weight_amount' => ['old' => $oldRerateAmount, 'new' => (float) ($request->rerate_on_access_weight_amount ?? 0), 'type' => 'numeric'],
                'other_adjustment_amount'        => ['old' => $oldOtherAdj, 'new' => (float) ($request->other_adjustment_amount ?? 0), 'type' => 'numeric'],

                'filling_bags_no'                => ['old' => $oldFillingBagsNo, 'new' => $newFillingBagsNo, 'type' => 'numeric'],
                'filling_bags_rate'              => ['old' => $oldFillingBagsRate, 'new' => $newFillingBagsRate, 'type' => 'numeric'],
                'filling_bags_amount'            => ['old' => $oldFillingBagsAmount, 'new' => $newFillingBagsAmount, 'type' => 'numeric'],
                'exempted_weight'                => ['old' => $oldExemptedWeight, 'new' => (float) ($request->exempted_weight ?? 0), 'type' => 'numeric'],
                'billing_weight'                 => ['old' => $oldBillingWeight, 'new' => (float) ($request->billing_weight ?? 0), 'type' => 'numeric'],

                'bag_weight'                     => ['old' => $oldBagWeight, 'new' => (float) ($request->bag_weight ?? 0), 'type' => 'numeric'],
                'bag_weight_total'               => ['old' => $oldBagWeightTotal, 'new' => (float) ($request->bag_weight_total ?? 0), 'type' => 'numeric'],
                'bag_weight_amount'              => ['old' => $oldBagWeightAmount, 'new' => (float) ($request->bag_weight_amount ?? 0), 'type' => 'numeric'],
                'bag_rate'                       => ['old' => $oldBagRate, 'new' => (float) ($request->bag_rate ?? 0), 'type' => 'numeric'],
                'bag_rate_amount'                => ['old' => $oldBagRateAmount, 'new' => (float) ($request->bag_rate_amount ?? 0), 'type' => 'numeric'],
                'loading_weighbridge_amount'     => ['old' => $oldLoadingWeighbridge, 'new' => (float) ($request->loading_weighbridge_amount ?? 0), 'type' => 'numeric'],

                'broker_id'                      => ['old' => $oldBrokerId, 'new' => $request->broker_id ?: null, 'type' => 'string'],
                'brokery_amount'                 => ['old' => $oldBrokeryAmount, 'new' => (float) ($request->brokery_amount ?? 0), 'type' => 'numeric'],
                'commission_amount'              => ['old' => $oldCommissionAmount, 'new' => (float) ($request->supplier_commission ?? $request->commission_amount ?? 0), 'type' => 'numeric'],

                'notes'                          => ['old' => $oldNotes, 'new' => $request->notes ?: null, 'type' => 'string'],
            ];

            if ($request->has('sampling_results') && is_array($request->sampling_results)) {
                foreach ($request->sampling_results as $sId => $sData) {
                    $slabTypeId = $sData['slab_type_id'] ?? $sId;
                    $appliedVal = (float) ($sData['applied_deduction'] ?? 0);

                    $prevSlab = null;
                    if ($previousData) {
                        $prevSlab = PaymentRequestSamplingResult::where('payment_request_data_id', $previousData->id)
                            ->where('slab_type_id', $slabTypeId)
                            ->first();
                    }
                    $oldSlabVal = $prevSlab ? (float) $prevSlab->applied_deduction : 0;
                    if (abs($oldSlabVal - $appliedVal) > 0.0001) {
                        $fieldsToTrack["slab_deduction_{$slabTypeId}"] = [
                            'old'  => $oldSlabVal,
                            'new'  => $appliedVal,
                            'type' => 'numeric',
                        ];
                    }
                }
            }

            $oldValues = [];
            $newValues = [];

            foreach ($fieldsToTrack as $key => $conf) {
                $hasDiff = false;
                if ($conf['type'] === 'numeric') {
                    $hasDiff = abs((float) $conf['old'] - (float) $conf['new']) > 0.0001;
                } else {
                    $hasDiff = trim((string) ($conf['old'] ?? '')) !== trim((string) ($conf['new'] ?? ''));
                }

                if ($hasDiff) {
                    $oldValues[$key] = is_numeric($conf['old']) ? (string) $conf['old'] : $conf['old'];
                    $newValues[$key] = is_numeric($conf['new']) ? (string) $conf['new'] : $conf['new'];
                }
            }

            if (empty($oldValues)) {
                $oldValues['payment_request_amount'] = (string) $oldTotalPaymentSum;
                $newValues['payment_request_amount'] = (string) $newTotalPaymentSum;
            }

            $descAmount = $newPaymentAmount > 0 ? "Payment: " . number_format($newPaymentAmount, 2) : "";
            if ($newFreightAmount > 0) {
                $descAmount .= ($descAmount ? ", " : "") . "Freight: " . number_format($newFreightAmount, 2);
            }

            $newValues['ticket_id'] = (int) $ticket->id;
            $newValues['purchase_order_id'] = (int) $purchaseOrder->id;

            AuditLogService::log(
                $paymentRequestData,
                'ticket_payment_request_updated',
                "Payment Request updated for Arrival Ticket #{$ticket->unique_no} (Contract #{$purchaseOrder->contract_no}) - " . ($descAmount ?: "Updated"),
                $oldValues,
                $newValues
            );

            return response()->json(['success' => 'Payment request created successfully']);
        });
    }

    public function manageLedgerCalculations($requestData, $purchaseOrder, $ticket, $stockInTransitAccount, $existingApprovals)
    {
        $paymentDetails = calculatePaymentDetails($requestData['ticket_id'], 1);
        $contractNo = $purchaseOrder->contract_no;
        $arrivalSlipNo = $ticket->arrivalSlip->unique_no;
        $truckNo = $ticket->truck_no ?? 'N/A';
        $biltyNo = $ticket->bilty_no ?? 'N/A';
        $grnNo = $ticket->arrivalSlip->unique_no;
        $qcAccountId = $ticket->qcProduct->account_id;
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
            'counter_account_id' => $qcAccountId,
            'grn_no' => $grnNo,
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
                    'counter_account_id' => $qcAccountId,
                    'grn_no' => $grnNo,
                    'payment_against' => "pohanch-purchase",
                    'against_reference_no' => "$truckNo/$biltyNo",
                    'remarks' => $supplierData['remarks']
                ]
            );
        }

        $transitTxn = Transaction::where('voucher_no', $contractNo)
            ->where('purpose', 'arrival-slip')
            ->where('against_reference_no', "$truckNo/$biltyNo")
            ->first();

        $transitData = [
            'amount' => $inventoryAmount,
            'account_id' => $ticket->qcProduct->account_id,
            'type' => 'debit',
            'counter_account_id' => $purchaseOrder->supplier->account_id,
            'grn_no' => $grnNo,
            'remarks' => 'Inventory ledger update for raw material arrival. Recording purchase of raw material (weight: ' . $ticket->arrived_net_weight . ' kg) at rate ' . $ticket->purchaseOrder->rate_per_kg . '/kg.'
        ];

        if ($transitTxn) {
            $transitTxn->update($transitData);
        } else {
            createTransaction(
                $amount,
                $ticket->qcProduct->account_id,
                1,
                $contractNo,
                'debit',
                'no',
                [
                    'purpose' => "arrival-slip",
                    'payment_against' => "pohanch-purchase",
                    'grn_no' => $grnNo,
                    'counter_account_id' => $purchaseOrder->supplier->account_id,
                    'against_reference_no' => "$truckNo/$biltyNo",
                    'remarks' => $transitData['remarks']
                ]
            );
        }

        $loadingWeight = $ticket->arrived_net_weight;

        if ($purchaseOrder->broker_one_id && $purchaseOrder->broker_one_commission && $loadingWeight) {
            $amount = ($loadingWeight * $purchaseOrder->broker_one_commission);

            $existingBrokerTrx = Transaction::where('voucher_no', $contractNo)
                ->where('payment_against', 'pohanch-purchase')
                ->where('account_id', $purchaseOrder->broker->account_id)
                ->where('against_reference_no', "$truckNo/$biltyNo")
                ->first();

            if ($existingBrokerTrx) {
                $existingBrokerTrx->update([
                    'amount' => $amount,
                    'account_id' => $purchaseOrder->broker->account_id,
                    'counter_account_id' => $qcAccountId,
                    'type' => 'credit',
                    'grn_no' => $grnNo,
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
                        'counter_account_id' => $qcAccountId,
                        'grn_no' => $grnNo,
                        'payment_against' => "pohanch-purchase",
                        'against_reference_no' => "$truckNo/$biltyNo",
                        'remarks' => 'Recording accounts payable for "Pohanch" purchase. Amount to be paid to broker.'
                    ]
                );
            }
        }

        if ($purchaseOrder->broker_two_id && $purchaseOrder->broker_two_commission && $loadingWeight) {
            $amount = ($loadingWeight * $purchaseOrder->broker_two_commission);

            $existingBrokerTrx = Transaction::where('voucher_no', $contractNo)
                ->where('payment_against', 'pohanch-purchase')
                ->where('account_id', $purchaseOrder->brokerTwo->account_id)
                ->where('against_reference_no', "$truckNo/$biltyNo")
                ->first();

            if ($existingBrokerTrx) {
                $existingBrokerTrx->update([
                    'amount' => $amount,
                    'account_id' => $purchaseOrder->brokerTwo->account_id,
                    'counter_account_id' => $qcAccountId,
                    'type' => 'credit',
                    'grn_no' => $grnNo,
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
                        'counter_account_id' => $qcAccountId,
                        'grn_no' => $grnNo,
                        'payment_against' => "pohanch-purchase",
                        'against_reference_no' => "$truckNo/$biltyNo",
                        'remarks' => 'Recording accounts payable for "Pohanch" purchase. Amount to be paid to broker.'
                    ]
                );
            }
        }

        if ($purchaseOrder->broker_three_id && $purchaseOrder->broker_three_commission && $loadingWeight) {
            $amount = ($loadingWeight * $purchaseOrder->broker_three_commission);

            $existingBrokerTrx = Transaction::where('voucher_no', $contractNo)
                ->where('payment_against', 'pohanch-purchase')
                ->where('account_id', $purchaseOrder->brokerThree->account_id)
                ->where('against_reference_no', "$truckNo/$biltyNo")
                ->first();

            if ($existingBrokerTrx) {
                $existingBrokerTrx->update([
                    'counter_account_id' => $qcAccountId,
                    'amount' => $amount,
                    'grn_no' => $grnNo,
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
                        'counter_account_id' => $qcAccountId,
                        'grn_no' => $grnNo,
                        'payment_against' => "pohanch-purchase",
                        'against_reference_no' => "$truckNo/$biltyNo",
                        'remarks' => 'Recording accounts payable for "Pohanch" purchase. Amount to be paid to broker.'
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
                            'grn_no' => $grnNo,
                            'counter_account_id' => $qcAccountId,
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
                            'counter_account_id' => $purchaseOrder->supplier->account_id,
                            'payment_against' => "thadda-purchase",
                            'grn_no' => $grnNo,
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

        if ($request->other_deduction && isset($request->other_deduction['kg_value']) && $request->other_deduction['kg_value'] > 0) {
            // PaymentRequestSamplingResult::create([
            //     'payment_request_data_id' => $paymentRequest->id,
            //     'slab_type_id' => null,
            //     'name' => $request->other_deduction['slab_name'] ?? 'Other Deduction',
            //     'checklist_value' => 0,
            //     'suggested_deduction' => 0,
            //     'applied_deduction' => $request->other_deduction['kg_value'],
            //     'deduction_type' => 'kg',
            //     'deduction_amount' => $request->other_deduction['deduction_amount'],
            //     'is_other_deduction' => true,
            //     'kg_value' => $request->other_deduction['kg_value']
            // ]);
        }
    }

    public function update(PaymentRequestRequest $request, $id)
    {
        return DB::transaction(function () use ($request, $id) {
            $paymentRequestData = PaymentRequestData::findOrFail($id);
            $ticket = ArrivalTicket::where('id', $paymentRequestData->ticket_id ?? $paymentRequestData->arrival_ticket_id)->first();
            $purchaseOrder = ArrivalPurchaseOrder::where('id', $paymentRequestData->purchase_order_id)->first();

            $samplingRequest = null;
            if ($ticket) {
                $samplingRequest = ArrivalSamplingRequest::where('arrival_ticket_id', $ticket->id)
                    ->whereIn('approved_status', ['approved', 'rejected'])
                    ->latest()
                    ->first();
                if (!$samplingRequest) {
                    $samplingRequest = PurchaseSamplingRequest::where('purchase_ticket_id', $ticket->id)
                        ->whereIn('approved_status', ['approved', 'rejected'])
                        ->latest()
                        ->first();
                }
            }

            $oldPaymentAmount = (float) PaymentRequest::where('payment_request_data_id', $paymentRequestData->id)
                ->where('request_type', 'payment')
                ->sum('amount');
            $oldFreightAmount = (float) PaymentRequest::where('payment_request_data_id', $paymentRequestData->id)
                ->where('request_type', 'freight_payment')
                ->sum('amount');
            $prevPaymentRequest = PaymentRequest::where('payment_request_data_id', $paymentRequestData->id)
                ->where('request_type', 'payment')
                ->latest('id')
                ->first();

            $newPaymentAmount = (float) ($request->payment_request_amount ?? 0);
            $newFreightAmount = (float) ($request->freight_pay_request_amount ?? 0);

            // Calculate net weight for lumpsum calculations
            $loadingW = (float) (
                ($ticket && $ticket->freight ? ($ticket->freight->loading_weight ?? $ticket->freight->loaded_weight ?? $ticket->freight->arrived_weight) : null)
                ?? ($ticket ? ($ticket->arrived_net_weight ?? 0) : 0)
                ?? ($paymentRequestData->billing_weight ?? $paymentRequestData->loading_weight ?? 0)
            );
            $bagsCount = (float) (
                ($ticket && $ticket->freight ? ($ticket->freight->no_of_bags ?? $ticket->freight->bags) : null)
                ?? ($ticket ? ($ticket->bags ?? $ticket->bag_count ?? $ticket->total_bags ?? 0) : 0)
                ?? ($paymentRequestData->no_of_bags ?? 0)
            );
            $oldBagW = (float) ($paymentRequestData->bag_weight ?? ($ticket ? $ticket->bag_weight : 0));
            $oldNetWeight = $loadingW - ($oldBagW * $bagsCount);

            $oldLumpAmount = $samplingRequest ? (float) $samplingRequest->lumpsum_deduction : 0;
            $oldLumpKgs = $samplingRequest ? (float) $samplingRequest->lumpsum_deduction_kgs : 0;
            $oldLumpAmountTotal = $oldLumpAmount * $oldNetWeight;
            $ratePerKg = (float) ($purchaseOrder ? ($purchaseOrder->rate_per_kg ?? 0) : 0);
            $oldLumpKgsTotal = $oldNetWeight > 0 ? (($oldLumpKgs * $oldNetWeight / 100) * $ratePerKg) : 0;

            $newLumpAmount = $request->input('lumpsum.amount.deduction_value') ?? $request->input('lump_sum_deduction_rupees');
            $newLumpAmountTotal = $request->input('lumpsum.amount.deduction_amount') ?? $request->input('lump_sum_deduction_rupees_total');
            $newLumpKgs = $request->input('lumpsum.kgs.deduction_value') ?? $request->input('lump_sum_deduction_kgs');
            $newLumpKgsTotal = $request->input('lumpsum.kgs.deduction_amount') ?? $request->input('lump_sum_deduction_kgs_total');

            // Save lumpsum deduction updates to sampling request if provided
            if ($samplingRequest && ($request->has('lumpsum') || $request->has('lump_sum_deduction_rupees') || $request->has('lump_sum_deduction_kgs'))) {
                $samplingUpdates = [];
                if ($newLumpAmount !== null && $newLumpAmount !== '') {
                    $samplingUpdates['lumpsum_deduction'] = (float) $newLumpAmount;
                }
                if ($newLumpKgs !== null && $newLumpKgs !== '') {
                    $samplingUpdates['lumpsum_deduction_kgs'] = (float) $newLumpKgs;
                }
                if (!empty($samplingUpdates)) {
                    $samplingRequest->update($samplingUpdates);
                }
            }

            // Old values
            $oldOtherDedKg = $prevPaymentRequest ? (float) $prevPaymentRequest->other_deduction_kg : 0;
            $oldOtherDedVal = $prevPaymentRequest ? (float) $prevPaymentRequest->other_deduction_value : 0;
            $oldRerateKg = $prevPaymentRequest ? (float) $prevPaymentRequest->rerate_on_access_weight_kg : 0;
            $oldRerateRate = $prevPaymentRequest ? (float) $prevPaymentRequest->rerate_on_access_weight_rate : 0;
            $oldRerateAmount = $prevPaymentRequest ? (float) $prevPaymentRequest->rerate_on_access_weight_amount : 0;
            $oldOtherAdj = $prevPaymentRequest ? (float) $prevPaymentRequest->other_adjustment_amount : 0;
            $oldFillingBagsNo = $prevPaymentRequest ? (float) $prevPaymentRequest->no_of_filling_bags : 0;
            $oldFillingBagsRate = $prevPaymentRequest ? (float) $prevPaymentRequest->filling_bag_rate : 0;
            $oldFillingBagsAmount = $prevPaymentRequest ? (float) $prevPaymentRequest->filling_bag_amount : 0;
            $oldExemptedWeight = (float) $paymentRequestData->exempted_weight;
            $oldBillingWeight = (float) $paymentRequestData->billing_weight;

            $oldTotalAmount = (float) $paymentRequestData->total_amount;
            $oldRemainingAmount = (float) $paymentRequestData->remaining_amount;
            $oldPaidAmount = (float) $paymentRequestData->paid_amount;

            $oldBagWeight = $oldBagW;
            $oldBagWeightTotal = (float) $paymentRequestData->bag_weight_total;
            $oldBagWeightAmount = (float) $paymentRequestData->bag_weight_amount;
            $oldBagRate = (float) ($paymentRequestData->bag_rate ?? ($ticket ? $ticket->bag_rate : 0));
            $oldBagRateAmount = (float) $paymentRequestData->bag_rate_amount;
            $oldLoadingWeighbridge = (float) $paymentRequestData->loading_weighbridge_amount;
            $oldBrokerId = $paymentRequestData->broker_id;
            $oldBrokeryAmount = (float) $paymentRequestData->brokery_amount;
            $oldCommissionAmount = (float) $paymentRequestData->commission_amount;
            $oldAdvanceFreight = (float) $paymentRequestData->advance_freight;
            $oldNotes = $paymentRequestData->notes;

            $newFillingBagsNo = (float) ($request->filling_bags_no ?? 0);
            $newFillingBagsRate = (float) ($request->filling_bags_rate ?? 0);
            $newFillingBagsAmount = (float) ($request->filling_bags_amount ?? ($newFillingBagsNo * $newFillingBagsRate));

            // Prepare update data
            $requestData = $request->all();
            $requestData['is_loading'] = $request->loading_type === 'loading';
            $requestData['broker_id'] = $request->broker_id ?: null;
            $requestData['brokery_amount'] = $request->brokery_amount ?? 0;
            $requestData['commission_amount'] = $request->supplier_commission ?? ($request->commission_amount ?? 0);

            // Calculate remaining amount
            $requestData['remaining_amount'] = $requestData['total_amount'] -
                ($requestData['paid_amount'] ?? 0) -
                ($requestData['payment_request_amount'] ?? 0) -
                ($requestData['freight_pay_request_amount'] ?? 0);

            $paymentRequestData->update($requestData);

            $paymentRequestData->paymentRequests()->delete();
            $this->createPaymentRequests($paymentRequestData, $request);

            if (isset($request->sampling_results) || isset($request->compulsory_results) || isset($request->other_deduction)) {
                $this->updateSamplingResults($paymentRequestData, $request);
            }

            $fieldsToTrack = [
                'payment_request_amount'         => ['old' => $oldPaymentAmount, 'new' => $newPaymentAmount, 'type' => 'numeric'],
                'freight_pay_request_amount'     => ['old' => $oldFreightAmount, 'new' => $newFreightAmount, 'type' => 'numeric'],
                'total_amount'                   => ['old' => $oldTotalAmount, 'new' => (float) ($request->total_amount ?? 0), 'type' => 'numeric'],
                'remaining_amount'               => ['old' => $oldRemainingAmount, 'new' => (float) ($requestData['remaining_amount'] ?? 0), 'type' => 'numeric'],
                'paid_amount'                    => ['old' => $oldPaidAmount, 'new' => (float) ($request->paid_amount ?? 0), 'type' => 'numeric'],
                'advance_freight'                => ['old' => $oldAdvanceFreight, 'new' => (float) ($request->advance_freight ?? 0), 'type' => 'numeric'],

                'lumpsum_deduction'              => ['old' => $oldLumpAmount, 'new' => $newLumpAmount !== null && $newLumpAmount !== '' ? (float) $newLumpAmount : $oldLumpAmount, 'type' => 'numeric'],
                'lumpsum_deduction_amount'       => ['old' => $oldLumpAmountTotal, 'new' => $newLumpAmountTotal !== null && $newLumpAmountTotal !== '' ? (float) $newLumpAmountTotal : $oldLumpAmountTotal, 'type' => 'numeric'],
                'lumpsum_deduction_kgs'          => ['old' => $oldLumpKgs, 'new' => $newLumpKgs !== null && $newLumpKgs !== '' ? (float) $newLumpKgs : $oldLumpKgs, 'type' => 'numeric'],
                'lumpsum_deduction_kgs_amount'   => ['old' => $oldLumpKgsTotal, 'new' => $newLumpKgsTotal !== null && $newLumpKgsTotal !== '' ? (float) $newLumpKgsTotal : $oldLumpKgsTotal, 'type' => 'numeric'],

                'other_deduction_kg'             => ['old' => $oldOtherDedKg, 'new' => (float) ($request->input('other_deduction.kg_value') ?? 0), 'type' => 'numeric'],
                'other_deduction_value'          => ['old' => $oldOtherDedVal, 'new' => (float) ($request->input('other_deduction.kg_amount') ?? $request->input('other_deduction.deduction_amount') ?? 0), 'type' => 'numeric'],
                'rerate_on_access_weight_kg'     => ['old' => $oldRerateKg, 'new' => (float) ($request->rerate_on_access_weight_kg ?? 0), 'type' => 'numeric'],
                'rerate_on_access_weight_rate'   => ['old' => $oldRerateRate, 'new' => (float) ($request->rerate_on_access_weight_rate ?? 0), 'type' => 'numeric'],
                'rerate_on_access_weight_amount' => ['old' => $oldRerateAmount, 'new' => (float) ($request->rerate_on_access_weight_amount ?? 0), 'type' => 'numeric'],
                'other_adjustment_amount'        => ['old' => $oldOtherAdj, 'new' => (float) ($request->other_adjustment_amount ?? 0), 'type' => 'numeric'],

                'filling_bags_no'                => ['old' => $oldFillingBagsNo, 'new' => $newFillingBagsNo, 'type' => 'numeric'],
                'filling_bags_rate'              => ['old' => $oldFillingBagsRate, 'new' => $newFillingBagsRate, 'type' => 'numeric'],
                'filling_bags_amount'            => ['old' => $oldFillingBagsAmount, 'new' => $newFillingBagsAmount, 'type' => 'numeric'],
                'exempted_weight'                => ['old' => $oldExemptedWeight, 'new' => (float) ($request->exempted_weight ?? 0), 'type' => 'numeric'],
                'billing_weight'                 => ['old' => $oldBillingWeight, 'new' => (float) ($request->billing_weight ?? 0), 'type' => 'numeric'],

                'bag_weight'                     => ['old' => $oldBagWeight, 'new' => (float) ($request->bag_weight ?? 0), 'type' => 'numeric'],
                'bag_weight_total'               => ['old' => $oldBagWeightTotal, 'new' => (float) ($request->bag_weight_total ?? 0), 'type' => 'numeric'],
                'bag_weight_amount'              => ['old' => $oldBagWeightAmount, 'new' => (float) ($request->bag_weight_amount ?? 0), 'type' => 'numeric'],
                'bag_rate'                       => ['old' => $oldBagRate, 'new' => (float) ($request->bag_rate ?? 0), 'type' => 'numeric'],
                'bag_rate_amount'                => ['old' => $oldBagRateAmount, 'new' => (float) ($request->bag_rate_amount ?? 0), 'type' => 'numeric'],
                'loading_weighbridge_amount'     => ['old' => $oldLoadingWeighbridge, 'new' => (float) ($request->loading_weighbridge_amount ?? 0), 'type' => 'numeric'],

                'broker_id'                      => ['old' => $oldBrokerId, 'new' => $request->broker_id ?: null, 'type' => 'string'],
                'brokery_amount'                 => ['old' => $oldBrokeryAmount, 'new' => (float) ($request->brokery_amount ?? 0), 'type' => 'numeric'],
                'commission_amount'              => ['old' => $oldCommissionAmount, 'new' => (float) ($request->supplier_commission ?? $request->commission_amount ?? 0), 'type' => 'numeric'],

                'notes'                          => ['old' => $oldNotes, 'new' => $request->notes ?: null, 'type' => 'string'],
            ];

            if ($request->has('sampling_results') && is_array($request->sampling_results)) {
                foreach ($request->sampling_results as $sId => $sData) {
                    $slabTypeId = $sData['slab_type_id'] ?? $sId;
                    $appliedVal = (float) ($sData['applied_deduction'] ?? 0);

                    $prevSlab = PaymentRequestSamplingResult::where('payment_request_data_id', $paymentRequestData->id)
                        ->where('slab_type_id', $slabTypeId)
                        ->first();
                    $oldSlabVal = $prevSlab ? (float) $prevSlab->applied_deduction : 0;
                    if (abs($oldSlabVal - $appliedVal) > 0.0001) {
                        $fieldsToTrack["slab_deduction_{$slabTypeId}"] = [
                            'old'  => $oldSlabVal,
                            'new'  => $appliedVal,
                            'type' => 'numeric',
                        ];
                    }
                }
            }

            $oldValues = [];
            $newValues = [];

            foreach ($fieldsToTrack as $key => $conf) {
                $hasDiff = false;
                if ($conf['type'] === 'numeric') {
                    $hasDiff = abs((float) $conf['old'] - (float) $conf['new']) > 0.0001;
                } else {
                    $hasDiff = trim((string) ($conf['old'] ?? '')) !== trim((string) ($conf['new'] ?? ''));
                }

                if ($hasDiff) {
                    $oldValues[$key] = is_numeric($conf['old']) ? (string) $conf['old'] : $conf['old'];
                    $newValues[$key] = is_numeric($conf['new']) ? (string) $conf['new'] : $conf['new'];
                }
            }

            $descAmount = $newPaymentAmount > 0 ? "Payment: " . number_format($newPaymentAmount, 2) : "";
            if ($newFreightAmount > 0) {
                $descAmount .= ($descAmount ? ", " : "") . "Freight: " . number_format($newFreightAmount, 2);
            }

            $effectiveTicketId = $ticket ? $ticket->id : ($paymentRequestData->ticket_id ?? null);
            if ($effectiveTicketId) {
                $newValues['ticket_id'] = (int) $effectiveTicketId;
            }
            if ($purchaseOrder) {
                $newValues['purchase_order_id'] = (int) $purchaseOrder->id;
            }

            $uniqueTicketNo = $ticket ? $ticket->unique_no : ($paymentRequestData->ticket_id ?? $id);
            $contractNo = $purchaseOrder ? $purchaseOrder->contract_no : 'N/A';

            AuditLogService::log(
                $paymentRequestData,
                'ticket_payment_request_updated',
                "Payment Request updated for Arrival Ticket #{$uniqueTicketNo} (Contract #{$contractNo}) - " . ($descAmount ?: "Updated"),
                $oldValues,
                $newValues
            );

            return response()->json(['success' => 'Payment request updated successfully']);
        });
    }

    protected function createPaymentRequests($paymentRequestData, $request, $accountId = null)
    {
        if (isset($request->ticket_id)) {
            $ticket = ArrivalTicket::find($request->ticket_id);

            if ($ticket && $ticket->purchaseOrder) {
                $ticket->update(['bag_weight' => $request->bag_weight]);
            }
        }

        PaymentRequest::create([
            'payment_request_data_id' => $paymentRequestData->id,
            'other_deduction_kg' => $request->other_deduction['kg_value'] ?? 0,
            'other_deduction_value' => $request->other_deduction['kg_amount'] ?? 0,
            'rerate_on_access_weight_kg' => $request->rerate_on_access_weight_kg ?? 0,
            'rerate_on_access_weight_rate' => $request->rerate_on_access_weight_rate ?? 0,
            'rerate_on_access_weight_amount' => $request->rerate_on_access_weight_amount ?? 0,
            'other_adjustment_amount' => $request->other_adjustment_amount ?? 0,
            'no_of_filling_bags' => $request->filling_bags_no ?? 0,
            'filling_bag_rate' => $request->filling_bags_rate ?? 0,
            'filling_bag_amount' => $request->filling_bags_amount ?? 0,
            'request_type' => 'payment',
            'account_id' => $accountId,
            'module_type' => 'ticket',
            'amount' => $request->payment_request_amount ?? 0
        ]);
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

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $data['arrivalTicket'] = $arrivalTicket = ArrivalTicket::findOrFail($id);
        $data['truckSizeRanges'] = TruckSizeRange::where('status', 'active')->get();
        $data['products'] = Product::where('product_type', 'raw_material')->get();

        $paymentRequestData = PaymentRequestData::where('ticket_id', $arrivalTicket->id)->where('module_type', 'ticket')->orderByDesc('id')->first();
        $paymentRequests = PaymentRequest::whereHas('paymentRequestData', function ($query) use ($arrivalTicket) {
            $query->where('ticket_id', $arrivalTicket->id);
        })->with([
                    'paymentRequestData' => function ($query) use ($arrivalTicket) {
                        $query->where('ticket_id', $arrivalTicket->id);
                    },
                    'paymentRequestData.purchaseOrder',
                    'paymentRequestData.purchaseTicket.purchaseOrder',
                    'paymentRequestData.purchaseTicket.purchaseFreight',
                    'paymentRequestData.arrivalTicket.freight',
                    'approval'
                ])->get();

        $requestedAmount = PaymentRequest::whereHas('paymentRequestData', function ($q) use ($arrivalTicket, $id) {
            $q->where('ticket_id', $id)
                ->where('purchase_order_id', $arrivalTicket->arrival_purchase_order_id);
        })
            ->where('request_type', 'payment')
            ->where('status', '!=', 'rejected')
            ->sum('amount');
        // dd($requestedAmount, $arrivalTicket->arrival_purchase_order_id, $id);
        $approvedAmount = PaymentRequest::whereHas('paymentRequestData', function ($q) use ($arrivalTicket) {
            $q->where('ticket_id', $arrivalTicket->id)
                ->where('purchase_order_id', $arrivalTicket->arrival_purchase_order_id);
        })
            ->where('request_type', 'payment')
            ->where('status', 'approved')
            ->sum('amount');

        $pRsSumForFreight = PaymentRequest::whereHas('paymentRequestData', function ($q) use ($arrivalTicket) {
            $q->where('ticket_id', $arrivalTicket->id)
                ->where('purchase_order_id', $arrivalTicket->arrival_purchase_order_id);
        })
            ->where('request_type', 'freight_payment')
            ->sum('amount');

        $freightPaymentRequestgrossAmount = paymentRequestData::where('ticket_id', $arrivalTicket->id)
            // ->where('purchase_order_id', $arrivalTicket->arrival_purchase_order_id)
            ->where('module_type', 'freight_payment')
            ->latest()->first(); // id ya created_at ke hisaab se last record
        // ->value('gross_amount');
        // dd($freightPaymentRequestgrossAmount);


        // dd($freightPaymentRequestgrossAmount, $freightPaymentRequestgrossAmount->godown_penalty);
        if ($freightPaymentRequestgrossAmount) {
            if ($freightPaymentRequestgrossAmount->is_paid_by_supplier == 1) {
                $freightPaymentRequestgrossAmount = $freightPaymentRequestgrossAmount->godown_penalty + $freightPaymentRequestgrossAmount->other_minus_labour + $freightPaymentRequestgrossAmount->commission_amount;
            } else {
                $freightPaymentRequestgrossAmount = $freightPaymentRequestgrossAmount->gross_amount;
            }
        } else {
            $freightPaymentRequestgrossAmount = null;
        }


        // dd($freightPaymentRequestgrossAmount);

        $samplingRequest = null;
        $samplingRequestCompulsuryResults = collect();
        $samplingRequestResults = collect();
        $otherDeduction = null;

        if ($arrivalTicket) {
            $samplingRequest = ArrivalSamplingRequest::where('arrival_ticket_id', $id)
                ->whereIn('approved_status', ['approved', 'rejected'])
                ->latest()
                ->first();

            if ($samplingRequest) {
                $rmPoSlabs = collect();
                if ($arrivalTicket && $samplingRequest->arrival_product_id) {
                    // $rmPoSlabs = ProductSlabForRmPo::where('arrival_ticket_id', $arrivalTicket->id)
                    //     ->where('product_id', $samplingRequest->arrival_product_id)
                    //     ->get()
                    //     ->groupBy('product_slab_type_id');
                }

                $samplingRequestCompulsuryResults = ArrivalSamplingResultForCompulsury::where('arrival_sampling_request_id', $samplingRequest->id)->get();
                $samplingRequestResults = ArrivalSamplingResult::where('arrival_sampling_request_id', $samplingRequest->id)->get();

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

            $otherDeduction = PaymentRequest::whereHas('paymentRequestData', function ($query) use ($arrivalTicket) {
                $query->where('ticket_id', $arrivalTicket->id);
                $query->where('module_type', 'ticket');

                $query->where('purchase_order_id', $arrivalTicket->arrival_purchase_order_id);
            })->select('other_deduction_kg', 'other_deduction_value', 'rerate_on_access_weight_kg', 'rerate_on_access_weight_rate', 'rerate_on_access_weight_amount', 'no_of_filling_bags', 'filling_bag_rate', 'filling_bag_amount', 'other_adjustment_amount')
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
        $data['purchaseOrder'] = $arrivalTicket->purchaseOrder;

        $data['html'] = view('management.procurement.raw_material.ticket_payment_request.snippets.requestPurchaseForm', [
            'purchaseOrder' => $arrivalTicket->purchaseOrder,
            'brokers' => $brokers,
            'arrivalTicket' => $arrivalTicket,
            'paymentRequestData' => $paymentRequestData,
            'paymentRequests' => $paymentRequests,
            'samplingRequest' => $samplingRequest,
            'samplingRequestCompulsuryResults' => $samplingRequestCompulsuryResults,
            'samplingRequestResults' => $samplingRequestResults,
            'requestedAmount' => $requestedAmount,
            'approvedAmount' => $approvedAmount,
            'pRsSumForFreight' => $pRsSumForFreight,
            'otherDeduction' => $otherDeduction,
            'isRequestApprovalPage' => false,
            'isTicketApprovalPage' => false,
            'freightPaymentRequestgrossAmount' => $freightPaymentRequestgrossAmount,
            'isTicketPage' => true,
        ])->render();

        return view('management.procurement.raw_material.ticket_payment_request.create', $data);
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
        $purchaseOrder = ArrivalPurchaseOrder::findOrFail($request->purchase_order_id);

        $requestedAmount = PaymentRequest::whereHas('paymentRequestData', fn($q) => $q->where('purchase_order_id', $purchaseOrder->id))
            ->where('request_type', 'payment')->sum('amount');

        $approvedAmount = PaymentRequest::whereHas('paymentRequestData', fn($q) => $q->where('purchase_order_id', $purchaseOrder->id))
            ->where('request_type', 'payment')->where('status', 'approved')->sum('amount');

        $pRsSumForFreight = PaymentRequest::whereHas('paymentRequestData', function ($query) use ($purchaseOrder) {
            $query->where('purchase_order_id', $purchaseOrder->id);
        })
            ->where('request_type', 'freight_payment')
            ->sum('amount');

        $purchaseOrders = ArrivalPurchaseOrder::where('freight_status', 'completed')->get();

        $samplingRequest = null;
        $samplingRequestCompulsuryResults = collect();
        $samplingRequestResults = collect();
        $otherDeduction = null;

        if ($purchaseOrder) {
            $samplingRequest = PurchaseSamplingRequest::where('arrival_purchase_order_id', $request->purchase_order_id)
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

            $existingPaymentRequestData = PaymentRequestData::where('purchase_order_id', $request->purchase_order_id)->first();
            if ($existingPaymentRequestData) {
                // $otherDeduction = PaymentRequestSamplingResult::where('payment_request_data_id', $existingPaymentRequestData->id)
                //     ->where('is_other_deduction', true)
                //     ->first();
            }
        }

        if (isset($request->is_debug)) {
            $orignalsamplingRequestResults = $samplingRequestResults;
            $samplingRequestResults = $samplingRequestResults->filter(function ($result) {
                return $result->applied_deduction;
            });
            // dd($samplingRequestResults, $orignalsamplingRequestResults, $rmPoSlabs);
        }

        $html = view('management.procurement.raw_material.ticket_payment_request.snippets.requestPurchaseForm', [
            'purchaseOrders' => $purchaseOrders,
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
