<?php

namespace App\Http\Controllers\Procurement\RawMaterial;

use App\Http\Controllers\Controller;
use App\Http\Requests\Procurement\PaymentRequestApprovalRequest;
use App\Models\Arrival\ArrivalSamplingRequest;
use App\Models\Arrival\ArrivalSamplingResult;
use App\Models\Arrival\ArrivalSamplingResultForCompulsury;
use App\Models\Arrival\PurchaseSamplingResult;
use App\Models\Arrival\PurchaseSamplingResultForCompulsury;
use App\Models\Procurement\PaymentRequest;
use App\Models\Procurement\PaymentRequestApproval;
use App\Models\Procurement\PaymentRequestData;
use App\Models\Procurement\PaymentRequestSamplingResult;
use App\Models\ArrivalPurchaseOrder;
use App\Models\Master\Account\Account;
use App\Models\Master\Account\Transaction;
use App\Models\Master\Broker;
use App\Models\Master\ProductSlab;
use App\Models\Master\ProductSlabForRmPo;
use App\Models\Master\Vendor;
use App\Models\Procurement\FreightPaymentRequest;
use App\Models\PurchaseSamplingRequest;
use App\Models\PurchaseTicket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\AuditLog;
use App\Services\AuditLogService;

class PaymentRequestApprovalController extends Controller
{
    public function index()
    {
        return view('management.procurement.raw_material.payment_request_approval.index');
    }

    public function getList(Request $request)
    {
        $paymentRequests = PaymentRequest::with([
            'paymentRequestData.purchaseOrder.saudaType',
            'paymentRequestData.purchaseTicket.purchaseOrder.saudaType',
            'paymentRequestData.purchaseTicket.purchaseFreight',
            'paymentRequestData.arrivalTicket.saudaType',
            'paymentRequestData.arrivalTicket.freight',
            'approvals.approver'
        ])
            ->when($request->filled('company_location_id'), function ($q) use ($request) {
                return $q->whereHas('paymentRequestData.purchaseOrder', function ($query) use ($request) {
                    $query->where('company_location_id', $request->company_location_id);
                });
            })
            // ->whereHas('paymentRequestData.purchaseOrder', function ($query) {
            //     $query->whereIn('company_location_id', getUserCurrentCompanyLocations());
            // })
            ->when($request->filled('supplier_id'), function ($q) use ($request) {
                return $q->whereHas('paymentRequestData.purchaseOrder', function ($query) use ($request) {
                    $query->where('supplier_id', $request->supplier_id);
                });
            })
            ->when($request->filled('status'), function ($q) use ($request) {
                return $q->where('status', $request->status);
            })
            ->when($request->filled('contract_no'), function ($q) use ($request) {
                return $q->whereHas('paymentRequestData.purchaseOrder', function ($query) use ($request) {
                    $query->where('contract_no', 'like', "%{$request->contract_no}%");
                });
            })
            ->when($request->filled('request_type'), function ($q) use ($request) {
                return $q->where('request_type', $request->request_type);
            })

            // ->when($request->filled('truck_no'), function ($q) use ($request) {
            //     return $q->whereHas('paymentRequestData', function ($query) use ($request) {
            //         $query->where('truck_no', 'like', "%{$request->truck_no}%");
            //     });
            // })
            ->when($request->filled('truck_no'), function ($q) use ($request) {
                return $q->where(function ($query) use ($request) {
                    $query->whereHas('paymentRequestData.arrivalTicket', function ($subQuery) use ($request) {
                        $subQuery->where('truck_no', 'like', "%{$request->truck_no}%");
                    })
                        ->orWhereHas('paymentRequestData.purchaseTicket', function ($subQuery) use ($request) {
                            $subQuery->where('truck_no', 'like', "%{$request->truck_no}%");
                        })
                        ->orWhereHas('paymentRequestData.purchaseTicket.purchaseFreight', function ($subQuery) use ($request) {
                            $subQuery->where('truck_no', 'like', "%{$request->truck_no}%");
                        });
                });
            })
            // ->when($request->filled('bilty_no'), function ($q) use ($request) {
            //     return $q->whereHas('paymentRequestData', function ($query) use ($request) {
            //         $query->where('bilty_no', 'like', "%{$request->bilty_no}%");
            //     });
            // })

            ->when($request->filled('bilty_no'), function ($q) use ($request) {
                return $q->where(function ($query) use ($request) {
                    $query->whereHas('paymentRequestData.arrivalTicket', function ($subQuery) use ($request) {
                        $subQuery->where('bilty_no', 'like', "%{$request->bilty_no}%");
                    })
                        ->orWhereHas('paymentRequestData.purchaseTicket', function ($subQuery) use ($request) {
                            $subQuery->where('bilty_no', 'like', "%{$request->bilty_no}%");
                        })
                        ->orWhereHas('paymentRequestData.purchaseTicket.purchaseFreight', function ($subQuery) use ($request) {
                            $subQuery->where('bilty_no', 'like', "%{$request->bilty_no}%");
                        });
                });
            })
            ->when($request->filled('amount'), function ($q) use ($request) {
                return $q->where('amount', 'like', "%{$request->amount}%");
            })
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = $request->search;
                return $q->where(function ($query) use ($search) {
                    $query->where('amount', 'like', "%{$search}%")
                        ->orWhere('status', 'like', "%{$search}%")
                        ->orWhereHas('paymentRequestData.purchaseOrder', function ($sub) use ($search) {
                            $sub->where('contract_no', 'like', "%{$search}%")
                                ->orWhereHas('supplier', function ($s) use ($search) {
                                    $s->where('name', 'like', "%{$search}%");
                                });
                        })
                        ->orWhereHas('paymentRequestData.purchaseTicket', function ($sub) use ($search) {
                            $sub->where('unique_no', 'like', "%{$search}%");
                        })
                        ->orWhereHas('paymentRequestData.arrivalTicket', function ($sub) use ($search) {
                            $sub->where('unique_no', 'like', "%{$search}%");
                        });
                });
            })
            ->when($request->filled('approval_status_filter'), function ($q) use ($request) {
                if ($request->approval_status_filter == 'pending') {
                    return $q->where('status', 'pending');
                } else if ($request->approval_status_filter == 'completed') {
                    return $q->whereIn('status', ['approved', 'rejected', 'sampling']);
                }
            })
            ->when($request->filled('daterange'), function ($q) use ($request) {
                $dates = explode(' - ', $request->daterange);
                $startDate = \Carbon\Carbon::createFromFormat('m/d/Y', trim($dates[0]))->format('Y-m-d');
                $endDate = \Carbon\Carbon::createFromFormat('m/d/Y', trim($dates[1]))->format('Y-m-d');

                return $q->whereDate('created_at', '>=', $startDate)
                    ->whereDate('created_at', '<=', $endDate);
            })
            // ->when($request->filled('arrival_daterange'), function ($q) use ($request) {
            //     $dates = explode(' - ', $request->arrival_daterange);
            //     $startDate = \Carbon\Carbon::createFromFormat('m/d/Y', trim($dates[0]))->format('Y-m-d');
            //     $endDate = \Carbon\Carbon::createFromFormat('m/d/Y', trim($dates[1]))->format('Y-m-d');

            //     return $q->whereHas('paymentRequestData.arrivalTicket', function ($sub) use ($startDate, $endDate) {
            //         $sub->whereDate('arrival_tickets.created_at', '>=', $startDate)
            //             ->whereDate('arrival_tickets.created_at', '<=', $endDate);
            //     });
            // })
            ->orderBy('created_at', 'desc')
            ->paginate(25);

        $paymentRequests->getCollection()->transform(function ($request) {
            $freightData = null;

            if ($request->module_type === 'purchase_order') {
                $freightData = optional($request->paymentRequestData)->purchaseTicket->purchaseFreight ?? null;
            } else {
                $freightData = optional($request->paymentRequestData)->arrivalTicket->freight ?? null;
            }

            $request->freight_data = $freightData;

            // Set Sauda Type logic as requested: $ticket->saudaType->name
            $moduleType = optional($request->paymentRequestData)->module_type;
            $ticket = ($moduleType === 'purchase_order')
                ? $request->paymentRequestData?->purchaseTicket
                : $request->paymentRequestData?->arrivalTicket;

            $saudaType = optional($ticket?->saudaType)->name;

            // Fallback to Purchase Order if ticket doesn't have its own sauda type
            if (!$saudaType) {
                $saudaType = optional($ticket?->purchaseOrder?->saudaType)->name;
            }

            // Final fallback to module type detection
            if (!$saudaType) {
                $saudaType = (in_array($moduleType, ['ticket', 'freight_payment'])) ? 'pohanch' : 'thadda';
            }

            $request->sauda_type = $saudaType;

            return $request;
        });

        return view('management.procurement.raw_material.payment_request_approval.getList', [
            'paymentRequests' => $paymentRequests
        ]);
    }

    public function store(PaymentRequestApprovalRequest $request)
    {
        // dd($request);
        return DB::transaction(function () use ($request) {
            $paymentRequest = PaymentRequest::findOrFail($request->payment_request_id);
            if ($paymentRequest->status == 'approved' || $paymentRequest->status == 'rejected') {
                return response()->json([
                    'message' => 'Payment request already approved or rejected',
                ], 422);
            }
            $paymentRequestData = $paymentRequest->paymentRequestData;
            // dd($paymentRequest, $paymentRequestData);
            $moduleType = $paymentRequestData->module_type;
            $ticket = ($moduleType === 'ticket' || $moduleType === 'freight_payment') ? $paymentRequestData->arrivalTicket : $paymentRequestData->purchaseTicket;
            $purchaseOrder = $ticket->purchaseOrder;
            $saudaType = ($moduleType === 'ticket' || $moduleType === 'freight_payment') ? 'pohanch' : 'thadda';
            $stockInTransitAccount = Account::where('name', 'Stock in Transit')->first();

            $truckNo = $paymentRequestData->arrivalTicket->truck_no ?? $paymentRequestData->purchaseTicket->purchaseFreight->truck_no ?? 'N/A';
            $biltyNo = $paymentRequestData->arrivalTicket->bilty_no ?? $paymentRequestData->purchaseTicket->purchaseFreight->bilty_no ?? 'N/A';

            // Resolve sampling request based on module type
            $samplingRequest = null;
            if ($moduleType === 'ticket' || $moduleType === 'freight_payment') {
                $samplingRequest = ArrivalSamplingRequest::where('arrival_ticket_id', $ticket->id)
                    ->whereIn('approved_status', ['approved', 'rejected'])
                    ->latest()
                    ->first();
                if (!$samplingRequest) {
                    $samplingRequest = ArrivalSamplingRequest::where('arrival_ticket_id', $ticket->id)->latest()->first();
                }
            } else if ($moduleType === 'purchase_order') {
                $samplingRequest = PurchaseSamplingRequest::where('purchase_ticket_id', $ticket->id)
                    ->whereIn('approved_status', ['approved', 'rejected'])
                    ->latest()
                    ->first();
                if (!$samplingRequest) {
                    $samplingRequest = PurchaseSamplingRequest::where('purchase_ticket_id', $ticket->id)->latest()->first();
                }
            }

            // Calculate net weight for lumpsum calculations
            $loadingW = (float) ($ticket && $ticket->freight ? $ticket->freight->loading_weight : ($ticket && $ticket->purchaseFreight ? $ticket->purchaseFreight->loading_weight : ($ticket->arrived_net_weight ?? ($paymentRequestData->loading_weight ?? 0))));
            $bagsCount = (float) ($ticket && $ticket->freight ? $ticket->freight->no_of_bags : ($ticket && $ticket->purchaseFreight ? $ticket->purchaseFreight->no_of_bags : ($ticket->bags ?? ($paymentRequestData->no_of_bags ?? 0))));
            $oldBagW = (float) ($paymentRequestData->bag_weight ?? ($ticket ? $ticket->bag_weight : 0));
            $oldNetWeight = $loadingW - ($oldBagW * $bagsCount);
            $ratePerKg = (float) ($purchaseOrder->rate_per_kg ?? 0);

            $oldLumpAmount = $samplingRequest ? (float) $samplingRequest->lumpsum_deduction : 0;
            $oldLumpKgs = $samplingRequest ? (float) $samplingRequest->lumpsum_deduction_kgs : 0;
            $oldLumpAmountTotal = $oldLumpAmount * $oldNetWeight;
            $oldLumpKgsTotal = $oldNetWeight > 0 ? (($oldLumpKgs * $oldNetWeight / 100) * $ratePerKg) : 0;

            $newLumpAmount = $request->input('lumpsum.amount.deduction_value') ?? $request->input('lump_sum_deduction_rupees');
            $newLumpAmountTotal = $request->input('lumpsum.amount.deduction_amount') ?? $request->input('lump_sum_deduction_rupees_total');
            $newLumpKgs = $request->input('lumpsum.kgs.deduction_value') ?? $request->input('lump_sum_deduction_kgs');
            $newLumpKgsTotal = $request->input('lumpsum.kgs.deduction_amount') ?? $request->input('lump_sum_deduction_kgs_total');

            // Save lumpsum deduction updates to sampling request if provided
            if ($samplingRequest && ($request->has('lumpsum') || $request->has('lump_sum_deduction_rupees') || $request->has('lump_sum_deduction_kgs'))) {
                $samplingUpdates = [];
                if ($newLumpAmount !== null && $newLumpAmount !== '') {
                    $samplingUpdates['lumpsum_deduction'] = $this->cleanNumber($newLumpAmount);
                }
                if ($newLumpKgs !== null && $newLumpKgs !== '') {
                    $samplingUpdates['lumpsum_deduction_kgs'] = $this->cleanNumber($newLumpKgs);
                }
                if (!empty($samplingUpdates)) {
                    $samplingRequest->update($samplingUpdates);
                }
            }

            // Snapshot old values before making any updates
            $oldStatus = $paymentRequest->status;
            $oldAmount = $paymentRequest->amount;
            $oldFillingBagsNo = $paymentRequest->no_of_filling_bags;
            $oldFillingBagRate = $paymentRequest->filling_bag_rate;
            $oldFillingBagAmount = $paymentRequest->filling_bag_amount;
            $oldOtherDeductionKg = $paymentRequest->other_deduction_kg;
            $oldOtherDeductionVal = $paymentRequest->other_deduction_value;
            $oldRerateKg = $paymentRequest->rerate_on_access_weight_kg;
            $oldRerateRate = $paymentRequest->rerate_on_access_weight_rate;
            $oldRerateAmount = $paymentRequest->rerate_on_access_weight_amount;
            $oldOtherAdj = $paymentRequest->other_adjustment_amount;

            $oldTotalAmount = $paymentRequestData ? (float) $paymentRequestData->total_amount : 0;
            $oldRemainingAmount = $paymentRequestData ? (float) $paymentRequestData->remaining_amount : 0;
            $oldPaidAmount = $paymentRequestData ? (float) $paymentRequestData->paid_amount : 0;
            $oldBagWeight = $oldBagW;
            $oldBagWeightTotal = $paymentRequestData ? (float) $paymentRequestData->bag_weight_total : ($oldBagWeight * $bagsCount);
            $oldBagWeightAmount = $paymentRequestData ? (float) $paymentRequestData->bag_weight_amount : ($ratePerKg * $oldBagWeightTotal);
            $oldBagRate = $paymentRequestData ? (float) $paymentRequestData->bag_rate : ($ticket ? (float) $ticket->bag_rate : 0);
            $oldBagRateAmount = $paymentRequestData ? (float) $paymentRequestData->bag_rate_amount : ($oldBagRate * $bagsCount);
            $oldLoadingWeighbridge = $paymentRequestData ? (float) $paymentRequestData->loading_weighbridge_amount : 0;
            $oldBrokerId = $paymentRequestData ? $paymentRequestData->broker_id : null;
            $oldBrokeryAmount = $paymentRequestData ? (float) $paymentRequestData->brokery_amount : 0;
            $oldCommissionAmount = $paymentRequestData ? (float) $paymentRequestData->commission_amount : 0;
            $oldExemptedWeight = $paymentRequestData ? (float) $paymentRequestData->exempted_weight : 0;
            $oldBillingWeight = $paymentRequestData ? (float) $paymentRequestData->billing_weight : 0;

            $lastApproval = PaymentRequestApproval::where('payment_request_id', $paymentRequest->id)->latest()->first();
            $oldRemarks = $lastApproval ? $lastApproval->remarks : null;

            // Snapshot existing sampling results before updating/deleting them
            $prevSlabResults = collect();
            if ($paymentRequestData) {
                $prevSlabResults = PaymentRequestSamplingResult::where('payment_request_data_id', $paymentRequestData->id)
                    ->get()
                    ->keyBy('slab_type_id');
            }

            $this->updatePaymentRequestData($paymentRequestData, $request, $ticket, ($moduleType === 'ticket' || $moduleType === 'freight_payment') ? false : true);

            if (($request->has('sampling_results') || $request->has('compulsory_results') || $request->has('other_deduction'))) {
                $this->updateSamplingResults($paymentRequestData, $request);
            }

            if ($request->has('bag_weight')) {
                $cleanBagW = $this->cleanNumber($request->bag_weight);
                $ticket->update(['bag_weight' => $cleanBagW]);
            }

            $prUpdates = [];
            if ($request->has('payment_request_amount')) {
                $prUpdates['amount'] = $this->cleanNumber($request->payment_request_amount);
            }
            if ($request->has('filling_bags_no')) {
                $prUpdates['no_of_filling_bags'] = $this->cleanNumber($request->filling_bags_no);
            }
            if ($request->has('filling_bags_rate')) {
                $prUpdates['filling_bag_rate'] = $this->cleanNumber($request->filling_bags_rate);
            }
            if ($request->has('filling_bags_amount') || $request->has('filling_bags_amount_hidden') || $request->has('filling_bags.amount_hidden')) {
                $fillingAmount = $request->filling_bags_amount_hidden ?? $request->input('filling_bags.amount_hidden') ?? $request->filling_bags_amount;
                $prUpdates['filling_bag_amount'] = $this->cleanNumber($fillingAmount);
            } elseif (isset($prUpdates['no_of_filling_bags']) && isset($prUpdates['filling_bag_rate'])) {
                $prUpdates['filling_bag_amount'] = ((float) $prUpdates['no_of_filling_bags']) * ((float) $prUpdates['filling_bag_rate']);
            }
            if ($request->has('rerate_on_access_weight_kg')) {
                $prUpdates['rerate_on_access_weight_kg'] = $this->cleanNumber($request->rerate_on_access_weight_kg);
            }
            if ($request->has('rerate_on_access_weight_rate')) {
                $prUpdates['rerate_on_access_weight_rate'] = $this->cleanNumber($request->rerate_on_access_weight_rate);
            }
            if ($request->has('rerate_on_access_weight_amount')) {
                $prUpdates['rerate_on_access_weight_amount'] = $this->cleanNumber($request->rerate_on_access_weight_amount);
            }
            if ($request->has('other_adjustment_amount')) {
                $prUpdates['other_adjustment_amount'] = $this->cleanNumber($request->other_adjustment_amount);
            }
            if ($request->has('other_deduction')) {
                $prUpdates['other_deduction_kg'] = $this->cleanNumber($request->other_deduction['kg_value'] ?? 0);
                $prUpdates['other_deduction_value'] = $this->cleanNumber($request->other_deduction['kg_amount'] ?? 0);
            }
            $prUpdates['status'] = $request->status;

            $paymentRequest->update($prUpdates);

            if ($request->has('freight_pay_request_amount') && $this->cleanNumber($request->freight_pay_request_amount) > 0 && $moduleType !== 'ticket') {
                $freightRequest = PaymentRequest::where('payment_request_data_id', $paymentRequestData->id)
                    ->where('request_type', 'freight_payment')
                    ->first();

                if ($freightRequest) {
                    $freightRequest->update(['amount' => $this->cleanNumber($request->freight_pay_request_amount)]);
                } else {
                    PaymentRequest::create([
                        'payment_request_data_id' => $paymentRequestData->id,
                        'request_type' => 'freight_payment',
                        'other_deduction_kg' => $this->cleanNumber($request->other_deduction['kg_value'] ?? 0),
                        'other_deduction_value' => $this->cleanNumber($request->other_deduction['kg_amount'] ?? 0),
                        'amount' => $this->cleanNumber($request->freight_pay_request_amount)
                    ]);
                }
            }

            PaymentRequestApproval::create([
                'payment_request_id' => $request->payment_request_id,
                'payment_request_data_id' => $paymentRequest->payment_request_data_id,
                'ticket_id' => $paymentRequestData->ticket_id,
                'purchase_order_id' => $purchaseOrder->id,
                'approver_id' => auth()->user()->id,
                'status' => $request->status,
                'remarks' => $request->remarks,
                'amount' => $paymentRequest->amount,
                'request_type' => $paymentRequest->request_type
            ]);

            // Audit Log: Collect and compare all form fields with accurate tolerance diffs
            $newFillingBagsNo = (float) ($this->cleanNumber($request->filling_bags_no) ?? $paymentRequest->no_of_filling_bags ?? 0);
            $newFillingBagsRate = (float) ($this->cleanNumber($request->filling_bags_rate) ?? $paymentRequest->filling_bag_rate ?? 0);
            $newFillingBagsAmount = (float) ($this->cleanNumber($request->filling_bags_amount) ?? $paymentRequest->filling_bag_amount ?? ($newFillingBagsNo * $newFillingBagsRate));

            $newPaymentAmount = $request->has('payment_request_amount') ? (float) $this->cleanNumber($request->payment_request_amount) : (float) $paymentRequest->amount;
            $newOtherDedKg = $request->has('other_deduction') ? (float) $this->cleanNumber($request->input('other_deduction.kg_value') ?? 0) : (float) ($paymentRequest->other_deduction_kg ?? 0);
            $newOtherDedVal = $request->has('other_deduction') ? (float) $this->cleanNumber($request->input('other_deduction.kg_amount') ?? $request->input('other_deduction.deduction_amount') ?? 0) : (float) ($paymentRequest->other_deduction_value ?? 0);

            $fieldsToTrack = [
                'status'                         => ['old' => $oldStatus, 'new' => $request->status, 'type' => 'string'],
                'remarks'                        => ['old' => $oldRemarks, 'new' => $request->remarks, 'type' => 'string'],
                'amount'                         => ['old' => (float) $oldAmount, 'new' => $newPaymentAmount, 'type' => 'numeric'],
                'total_amount'                   => ['old' => $oldTotalAmount, 'new' => (float) ($this->cleanNumber($request->total_amount) ?? $paymentRequestData?->total_amount ?? 0), 'type' => 'numeric'],
                'remaining_amount'               => ['old' => $oldRemainingAmount, 'new' => (float) ($this->cleanNumber($request->remaining_amount) ?? $paymentRequestData?->remaining_amount ?? 0), 'type' => 'numeric'],

                'lumpsum_deduction'              => ['old' => $oldLumpAmount, 'new' => $newLumpAmount !== null && $newLumpAmount !== '' ? (float) $this->cleanNumber($newLumpAmount) : $oldLumpAmount, 'type' => 'numeric'],
                'lumpsum_deduction_amount'       => ['old' => $oldLumpAmountTotal, 'new' => $newLumpAmountTotal !== null && $newLumpAmountTotal !== '' ? (float) $this->cleanNumber($newLumpAmountTotal) : $oldLumpAmountTotal, 'type' => 'numeric'],
                'lumpsum_deduction_kgs'          => ['old' => $oldLumpKgs, 'new' => $newLumpKgs !== null && $newLumpKgs !== '' ? (float) $this->cleanNumber($newLumpKgs) : $oldLumpKgs, 'type' => 'numeric'],
                'lumpsum_deduction_kgs_amount'   => ['old' => $oldLumpKgsTotal, 'new' => $newLumpKgsTotal !== null && $newLumpKgsTotal !== '' ? (float) $this->cleanNumber($newLumpKgsTotal) : $oldLumpKgsTotal, 'type' => 'numeric'],

                'other_deduction_kg'             => ['old' => (float) ($oldOtherDeductionKg ?? 0), 'new' => $newOtherDedKg, 'type' => 'numeric'],
                'other_deduction_value'          => ['old' => (float) ($oldOtherDeductionVal ?? 0), 'new' => $newOtherDedVal, 'type' => 'numeric'],
                'rerate_on_access_weight_kg'     => ['old' => (float) ($oldRerateKg ?? 0), 'new' => (float) ($this->cleanNumber($request->rerate_on_access_weight_kg) ?? $paymentRequest->rerate_on_access_weight_kg ?? 0), 'type' => 'numeric'],
                'rerate_on_access_weight_rate'   => ['old' => (float) ($oldRerateRate ?? 0), 'new' => (float) ($this->cleanNumber($request->rerate_on_access_weight_rate) ?? $paymentRequest->rerate_on_access_weight_rate ?? 0), 'type' => 'numeric'],
                'rerate_on_access_weight_amount' => ['old' => (float) ($oldRerateAmount ?? 0), 'new' => (float) ($this->cleanNumber($request->rerate_on_access_weight_amount) ?? $paymentRequest->rerate_on_access_weight_amount ?? 0), 'type' => 'numeric'],
                'other_adjustment_amount'        => ['old' => (float) ($oldOtherAdj ?? 0), 'new' => (float) ($this->cleanNumber($request->other_adjustment_amount) ?? $paymentRequest->other_adjustment_amount ?? 0), 'type' => 'numeric'],

                'no_of_filling_bags'             => ['old' => (float) ($oldFillingBagsNo ?? 0), 'new' => $newFillingBagsNo, 'type' => 'numeric'],
                'filling_bag_rate'               => ['old' => (float) ($oldFillingBagRate ?? 0), 'new' => $newFillingBagsRate, 'type' => 'numeric'],
                'filling_bag_amount'             => ['old' => (float) ($oldFillingBagAmount ?? 0), 'new' => $newFillingBagsAmount, 'type' => 'numeric'],
                'exempted_weight'                => ['old' => $oldExemptedWeight, 'new' => (float) ($this->cleanNumber($request->exempted_weight) ?? $paymentRequestData?->exempted_weight ?? 0), 'type' => 'numeric'],
                'billing_weight'                 => ['old' => $oldBillingWeight, 'new' => (float) ($this->cleanNumber($request->billing_weight) ?? $paymentRequestData?->billing_weight ?? 0), 'type' => 'numeric'],

                'bag_weight'                     => ['old' => $oldBagWeight, 'new' => (float) ($this->cleanNumber($request->bag_weight) ?? $ticket?->bag_weight ?? 0), 'type' => 'numeric'],
                'bag_weight_total'               => ['old' => $oldBagWeightTotal, 'new' => (float) ($this->cleanNumber($request->bag_weight_total) ?? $paymentRequestData?->bag_weight_total ?? 0), 'type' => 'numeric'],
                'bag_weight_amount'              => ['old' => $oldBagWeightAmount, 'new' => (float) ($this->cleanNumber($request->bag_weight_amount) ?? $paymentRequestData?->bag_weight_amount ?? 0), 'type' => 'numeric'],
                'bag_rate'                       => ['old' => $oldBagRate, 'new' => (float) ($this->cleanNumber($request->bag_rate) ?? $ticket?->bag_rate ?? 0), 'type' => 'numeric'],
                'bag_rate_amount'                => ['old' => $oldBagRateAmount, 'new' => (float) ($this->cleanNumber($request->bag_rate_amount) ?? $paymentRequestData?->bag_rate_amount ?? 0), 'type' => 'numeric'],
                'loading_weighbridge_amount'     => ['old' => $oldLoadingWeighbridge, 'new' => (float) ($this->cleanNumber($request->loading_weighbridge_amount) ?? $paymentRequestData?->loading_weighbridge_amount ?? 0), 'type' => 'numeric'],

                'broker_id'                      => ['old' => $oldBrokerId, 'new' => $request->has('broker_id') ? ($request->broker_id ?: null) : $oldBrokerId, 'type' => 'string'],
                'brokery_amount'                 => ['old' => $oldBrokeryAmount, 'new' => (float) ($this->cleanNumber($request->brokery_amount) ?? $paymentRequestData?->brokery_amount ?? 0), 'type' => 'numeric'],
                'commission_amount'              => ['old' => $oldCommissionAmount, 'new' => (float) ($this->cleanNumber($request->supplier_commission ?? $request->commission_amount) ?? $paymentRequestData?->commission_amount ?? 0), 'type' => 'numeric'],
            ];

            if ($request->has('sampling_results') && is_array($request->sampling_results)) {
                foreach ($request->sampling_results as $sId => $sData) {
                    $slabTypeId = $sData['slab_type_id'] ?? $sId;
                    $appliedVal = (float) ($sData['applied_deduction'] ?? 0);

                    $prevSlab = $prevSlabResults->get($slabTypeId);
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

            if ($request->has('compulsory_results') && is_array($request->compulsory_results)) {
                foreach ($request->compulsory_results as $cId => $cData) {
                    $qcParamId = $cData['qc_param_id'] ?? $cId;
                    $appliedVal = (float) ($cData['applied_deduction'] ?? 0);

                    $prevSlab = $prevSlabResults->get($qcParamId);
                    $oldSlabVal = $prevSlab ? (float) $prevSlab->applied_deduction : 0;
                    if (abs($oldSlabVal - $appliedVal) > 0.0001) {
                        $fieldsToTrack["slab_deduction_{$qcParamId}"] = [
                            'old'  => $oldSlabVal,
                            'new'  => $appliedVal,
                            'type' => 'numeric',
                        ];
                    }
                }
            }

            $diffOld = [];
            $diffNew = [];

            foreach ($fieldsToTrack as $key => $conf) {
                $hasDiff = false;
                if ($conf['type'] === 'numeric') {
                    $hasDiff = abs((float) $conf['old'] - (float) $conf['new']) > 0.0001;
                } else {
                    $hasDiff = trim((string) ($conf['old'] ?? '')) !== trim((string) ($conf['new'] ?? ''));
                }

                if ($hasDiff) {
                    $diffOld[$key] = is_numeric($conf['old']) ? (string) $conf['old'] : $conf['old'];
                    $diffNew[$key] = is_numeric($conf['new']) ? (string) $conf['new'] : $conf['new'];
                }
            }

            if (empty($diffOld)) {
                $diffOld['status'] = (string) $oldStatus;
                $diffNew['status'] = (string) $request->status;
            }

            $diffOld['ticket_id'] = $paymentRequestData->ticket_id;
            $diffOld['payment_request_id'] = $paymentRequest->id;
            $diffNew['ticket_id'] = $paymentRequestData->ticket_id;
            $diffNew['payment_request_id'] = $paymentRequest->id;

            $ticketNo = $ticket ? ($ticket->unique_no ?? 'N/A') : 'N/A';
            $contractNo = $purchaseOrder ? ($purchaseOrder->contract_no ?? 'N/A') : 'N/A';
            $statusLabel = ucfirst($request->status);

            AuditLogService::log(
                $paymentRequest,
                'payment_request_approval_updated',
                "Payment Request Approval: {$statusLabel} for Ticket #{$ticketNo} (Contract #{$contractNo}) - Amount: " . number_format($paymentRequest->amount, 2),
                $diffOld,
                $diffNew
            );

            // ---------------------- 
            if ($moduleType !== 'freight_payment') {
                $paymentDetails = calculatePaymentDetails($ticket->id, $moduleType === 'ticket' ? 1 : 2);
                $grnNo = $ticket->arrivalSlip->unique_no ?? null;
                $contractNo = $purchaseOrder->contract_no;
                $qcProduct = $purchaseOrder->qcProduct->name ?? $purchaseOrder->product->name;
                $loadingWeight = ($moduleType === 'ticket' ? $paymentRequestData->arrivalTicket->arrived_net_weight : $paymentRequestData->purchaseTicket->purchaseFreight->loading_weight) ?? 0;
                $inventoryAmount = $paymentDetails['calculations']['inventory_amount'] ?? 0;
                $accId = $moduleType === 'ticket' ? $ticket->qcProduct->account_id : $stockInTransitAccount->id;

                $amount = $paymentDetails['calculations']['net_amount'] ?? 0;

                $supplierTxn = Transaction::where('voucher_no', $contractNo)
                    ->where('purpose', 'supplier-payable')
                    ->where('against_reference_no', "$truckNo/$biltyNo")
                    ->first();

                $supplierData = [
                    'amount' => $paymentDetails['calculations']['supplier_net_amount'] ?? 0,
                    'account_id' => $purchaseOrder->supplier->account_id,
                    'payment_against' => $moduleType === 'ticket' ? "pohanch-purchase" : "thadda-purchase",
                    'grn_no' => $grnNo,
                    'type' => 'credit',
                    'counter_account_id' => $accId,
                    'remarks' => "Accounts payable recorded against the contract ($purchaseOrder->contract_no) for Bilty: $biltyNo - Truck No: $truckNo. Amount payable to the supplier.",
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
                            'counter_account_id' => $accId,
                            'grn_no' => $grnNo,
                            'purpose' => "supplier-payable",
                            'payment_against' => $moduleType === 'ticket' ? "pohanch-purchase" : "thadda-purchase",
                            'against_reference_no' => "$truckNo/$biltyNo",
                            'remarks' => $supplierData['remarks']
                        ]
                    );
                }

                if ($moduleType !== 'ticket') {
                    $transitTxn = Transaction::where('voucher_no', $contractNo)
                        ->where('purpose', 'stock-in-transit')
                        ->where('type', 'debit')
                        ->where('against_reference_no', "$truckNo/$biltyNo")
                        ->first();

                    $transitTxnCdt = Transaction::where('voucher_no', $contractNo)
                        ->where('purpose', 'stock-in-transit')
                        ->where('type', 'credit')
                        ->where('against_reference_no', "$truckNo/$biltyNo")
                        ->first();

                    $transitData = [
                        'amount' => $inventoryAmount,
                        'counter_account_id' => $purchaseOrder->supplier->account_id,
                        'account_id' => $stockInTransitAccount->id,
                        'remarks' => "Stock-in-transit recorded for arrival of $qcProduct under contract ($contractNo) via Bilty: $biltyNo - Truck No: $truckNo. Weight: {$loadingWeight} kg at rate {$purchaseOrder->rate_per_kg}/kg."
                    ];

                    if ($transitTxnCdt) {
                        $transitTxnCdt->update($transitData);
                    }

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
                                'payment_against' => "pohanch-purchase",
                                'against_reference_no' => "$truckNo/$biltyNo",
                                'remarks' => $transitData['remarks']
                            ]
                        );
                    }

                    $existingFreightTrx = Transaction::where('voucher_no', $contractNo)
                        ->where('purpose', 'thadda-freight')
                        ->where('against_reference_no', "$truckNo/$biltyNo")
                        ->first();
                    $advanceFreight = (int) ($request->advance_freight);

                    if ($advanceFreight > 0) {
                        if ($existingFreightTrx) {
                            $existingFreightTrx->update([
                                'amount' => $advanceFreight,
                                'account_id' => $purchaseOrder->supplier->account_id,
                                'counter_account_id' => $stockInTransitAccount->id,
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
                                    'counter_account_id' => $stockInTransitAccount->id,
                                    'purpose' => "thadda-freight",
                                    'payment_against' => "thadda-purchase",
                                    'against_reference_no' => "$truckNo/$biltyNo",
                                    'remarks' => "Freight payable for truck no. $truckNo and bilty no. $biltyNo against contract ($contractNo). Amount adjusted from supplier account."
                                ]
                            );
                        }
                    }

                    if ($purchaseOrder->broker_one_id && $purchaseOrder->broker_one_commission && $loadingWeight) {
                        if ($purchaseOrder->broker_one_calculation_type == 'quantity') {
                            $amount = ($loadingWeight * $purchaseOrder->broker_one_commission);
                        } else {
                            $amount = $purchaseOrder->broker_one_commission;
                        }

                        $existingBrokerTrx = Transaction::where('voucher_no', $contractNo)
                            ->where('payment_against', 'thadda-purchase')
                            ->where('account_id', $purchaseOrder->broker->account_id)
                            ->where('against_reference_no', "$truckNo/$biltyNo")
                            ->first();

                        if ($existingBrokerTrx) {
                            $existingBrokerTrx->update([
                                'amount' => $amount,
                                'account_id' => $purchaseOrder->broker->account_id,
                                'type' => 'credit',
                                'counter_account_id' => $stockInTransitAccount->id,
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
                                    'counter_account_id' => $stockInTransitAccount->id,
                                    'purpose' => "broker",
                                    'payment_against' => "thadda-purchase",
                                    'against_reference_no' => "$truckNo/$biltyNo",
                                    'remarks' => 'Recording accounts payable for "Thadda" purchase. Amount to be paid to broker.'
                                ]
                            );
                        }
                    }

                    if ($purchaseOrder->broker_two_id && $purchaseOrder->broker_two_commission && $loadingWeight) {
                        if ($purchaseOrder->broker_two_calculation_type == 'quantity') {
                            $amount = ($loadingWeight * $purchaseOrder->broker_two_commission);
                        } else {
                            $amount = $purchaseOrder->broker_two_commission;
                        }


                        $existingBrokerTrx = Transaction::where('voucher_no', $contractNo)
                            ->where('payment_against', 'thadda-purchase')
                            ->where('account_id', $purchaseOrder->brokerTwo->account_id)
                            ->where('against_reference_no', "$truckNo/$biltyNo")
                            ->first();

                        if ($existingBrokerTrx) {
                            $existingBrokerTrx->update([
                                'amount' => $amount,
                                'account_id' => $purchaseOrder->brokerTwo->account_id,
                                'counter_account_id' => $stockInTransitAccount->id,
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
                                    'payment_against' => "thadda-purchase",
                                    'counter_account_id' => $stockInTransitAccount->id,
                                    'against_reference_no' => "$truckNo/$biltyNo",
                                    'remarks' => 'Recording accounts payable for "Thadda" purchase. Amount to be paid to broker.'
                                ]
                            );
                        }
                    }

                    if ($purchaseOrder->broker_three_id && $purchaseOrder->broker_three_commission && $loadingWeight) {
                        if ($purchaseOrder->broker_three_calculation_type == 'quantity') {
                            $amount = ($loadingWeight * $purchaseOrder->broker_three_commission);
                        } else {
                            $amount = $purchaseOrder->broker_three_commission;
                        }

                        $existingBrokerTrx = Transaction::where('voucher_no', $contractNo)
                            ->where('payment_against', 'thadda-purchase')
                            ->where('account_id', $purchaseOrder->brokerThree->account_id)
                            ->where('against_reference_no', "$truckNo/$biltyNo")
                            ->first();

                        if ($existingBrokerTrx) {
                            $existingBrokerTrx->update([
                                'amount' => $amount,
                                'account_id' => $purchaseOrder->brokerThree->account_id,
                                'counter_account_id' => $stockInTransitAccount->id,
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

                    $transitTxn = Transaction::where('voucher_no', $contractNo)
                        ->where('purpose', 'arrival-slip')
                        ->where('against_reference_no', "$truckNo/$biltyNo")
                        ->first();

                    if ($transitTxn) {
                        $transitTxn->update([
                            'amount' => $inventoryAmount,
                            'account_id' => $ticket->purchaseOrder->qcProduct->account_id ?? $ticket->purchaseOrder->product->account_id,
                            'remarks' => 'Inventory ledger update for raw material arrival. Recording purchase of raw material (weight: ' . $loadingWeight . ' kg) at rate ' . $purchaseOrder->rate_per_kg . '/kg.'
                        ]);
                    }
                } else {
                    $transitTxn = Transaction::where('voucher_no', $contractNo)
                        ->where('purpose', 'arrival-slip')
                        ->where('against_reference_no', "$truckNo/$biltyNo")
                        ->first();

                    if ($transitTxn) {
                        $transitTxn->update([
                            'amount' => $inventoryAmount,
                            // 'account_id' =>    $ticket->purchaseOrder->qcProduct->account_id,
                            'account_id' => $ticket->qcProduct->account_id ?? $ticket->product->account_id,
                            'grn_no' => $grnNo,
                            'type' => 'debit',
                            'remarks' => 'Inventory ledger update for raw material arrival. Recording purchase of raw material (weight: ' . $loadingWeight . ' kg) at rate ' . $purchaseOrder->rate_per_kg . '/kg.'
                        ]);
                    } else {
                        createTransaction(
                            $amount,
                            $ticket->qcProduct->account_id ?? $ticket->product->account_id,
                            1,
                            $contractNo,
                            'debit',
                            'no',
                            [
                                'purpose' => "arrival-slip",
                                'grn_no' => $grnNo,
                                'payment_against' => "pohanch-purchase",
                                'against_reference_no' => "$truckNo/$biltyNo",
                                'remarks' => 'Inventory ledger update for raw material arrival. Recording purchase of raw material (weight: ' . $loadingWeight . ' kg) at rate ' . $purchaseOrder->rate_per_kg . '/kg.'
                            ]
                        );
                    }
                }

                if (
                    isset($request->brokery_amount, $request->broker_id) &&
                    $request->brokery_amount < 0
                ) {
                    $existingTxn = Transaction::where('voucher_no', $contractNo)
                        ->where('purpose', 'supplier-brokery')
                        ->where('against_reference_no', "$truckNo/$biltyNo")
                        ->exists();

                    if (!$existingTxn) {
                        $broker = Broker::find($request->broker_id);
                        if ($broker && $broker->account_id) {
                            $brokeryAmount = abs($request->brokery_amount);

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
            // ----------------------

            return response()->json([
                'success' => 'Payment request ' . $request->status . ' successfully!'
            ]);
        });
    }

    private function cleanNumber($val)
    {
        if ($val === null || $val === '') {
            return null;
        }
        if (is_string($val)) {
            $val = str_replace(',', '', trim($val));
        }
        return is_numeric($val) ? (float) $val : 0;
    }

    private function updatePaymentRequestData($paymentRequestData, $request, $ticket, $isTicket = false)
    {
        $updateData = [];

        if ($request->has('total_amount')) {
            $updateData['total_amount'] = $this->cleanNumber($request->total_amount);
        }

        if ($request->has('remaining_amount')) {
            $updateData['remaining_amount'] = $this->cleanNumber($request->remaining_amount);
        }

        if ($request->has('supplier_name')) {
            $updateData['supplier_name'] = $request->supplier_name;
        }

        if ($request->has('bag_weight')) {
            $updateData['bag_weight'] = $this->cleanNumber($request->bag_weight);
        }

        if ($request->has('bag_weight_total')) {
            $updateData['bag_weight_total'] = $this->cleanNumber($request->bag_weight_total);
        }

        if ($request->has('bag_rate')) {
            $updateData['bag_rate'] = $this->cleanNumber($request->bag_rate);
        }

        if ($request->has('bag_weight_amount')) {
            $updateData['bag_weight_amount'] = $this->cleanNumber($request->bag_weight_amount);
        }

        if ($request->has('bag_rate_amount')) {
            $updateData['bag_rate_amount'] = $this->cleanNumber($request->bag_rate_amount);
        }

        if ($request->has('loading_weighbridge_amount')) {
            $updateData['loading_weighbridge_amount'] = $this->cleanNumber($request->loading_weighbridge_amount);
        }

        if ($request->has('broker_id')) {
            $updateData['broker_id'] = $request->broker_id ?: null;
        }

        if ($request->has('brokery_amount')) {
            $updateData['brokery_amount'] = $this->cleanNumber($request->brokery_amount);
        }

        if ($request->has('supplier_commission') || $request->has('commission_amount')) {
            $updateData['commission_amount'] = $this->cleanNumber($request->supplier_commission ?? $request->commission_amount);
        }

        if ($request->has('exempted_weight')) {
            $updateData['exempted_weight'] = $this->cleanNumber($request->exempted_weight);
        }

        if ($request->has('billing_weight')) {
            $updateData['billing_weight'] = $this->cleanNumber($request->billing_weight);
        }

        if (!empty($updateData)) {
            $paymentRequestData->update($updateData);
            if ($isTicket && $ticket) {
                $ticketUpdates = array_intersect_key($updateData, array_flip(['bag_weight', 'bag_rate']));
                if (!empty($ticketUpdates)) {
                    $ticket->update($ticketUpdates);
                }
            }
        }
    }

    private function updateSamplingResults($paymentRequestData, $request)
    {
        $paymentRequestData->samplingResults()->delete();
        if ($request->has('sampling_results')) {
            foreach ($request->sampling_results as $result) {
                PaymentRequestSamplingResult::create([
                    'payment_request_data_id' => $paymentRequestData->id,
                    'slab_type_id' => $result['slab_type_id'] ?? null,
                    'name' => $result['slab_name'] ?? '',
                    'checklist_value' => $this->cleanNumber($result['checklist_value'] ?? 0) ?? 0,
                    'suggested_deduction' => $this->cleanNumber($result['suggested_deduction'] ?? 0) ?? 0,
                    'applied_deduction' => $this->cleanNumber($result['applied_deduction'] ?? 0) ?? 0,
                    'deduction_type' => $result['deduction_type'] ?? 'amount',
                    'deduction_amount' => $this->cleanNumber($result['deduction_amount'] ?? 0) ?? 0,
                    'is_other_deduction' => false
                ]);
            }
        }

        if ($request->has('compulsory_results')) {
            foreach ($request->compulsory_results as $result) {
                PaymentRequestSamplingResult::create([
                    'payment_request_data_id' => $paymentRequestData->id,
                    'slab_type_id' => $result['qc_param_id'] ?? null,
                    'name' => $result['qc_name'],
                    'checklist_value' => 0,
                    'suggested_deduction' => 0,
                    'applied_deduction' => $this->cleanNumber($result['applied_deduction'] ?? 0) ?? 0,
                    'deduction_type' => 'amount',
                    'deduction_amount' => $this->cleanNumber($result['deduction_amount'] ?? 0) ?? 0,
                ]);
            }
        }
    }

    public function edit($paymentRequestId)
    {
        $paymentRequest = PaymentRequest::with([
            'paymentRequestData.purchaseOrder',
            'paymentRequestData.purchaseTicket.purchaseFreight',
        ])->findOrFail($paymentRequestId);

        $isUpdated = 0;
        $approval = null;
        if (!$paymentRequest->canBeApproved()) {
            $approval = PaymentRequestApproval::where('payment_request_id', $paymentRequestId)
                ->latest()
                ->first();

            $isUpdated = 1;
        }
        $moduleType = $paymentRequest->paymentRequestData->module_type;
        $ticket = null;
        if ($moduleType == 'ticket' || $moduleType == 'freight_payment') {
            $ticket = $paymentRequest->paymentRequestData->arrivalTicket;
        } else {
            $ticket = $paymentRequest->paymentRequestData->purchaseTicket;
        }

        $purchaseOrderGateBuying = $paymentRequest->paymentRequestData->purchaseOrder ?? null;
        $purchaseOrder = $ticket->purchaseOrder;

        $requestedAmount = PaymentRequest::whereHas('paymentRequestData', fn($q) => $q->where('ticket_id', $ticket->id))
            ->where('request_type', 'payment')
            ->where('status', '!=', 'rejected')
            ->where('module_type', $moduleType)->sum('amount');

        $approvedAmount = PaymentRequest::whereHas('paymentRequestData', fn($q) => $q->where('ticket_id', $ticket->id))
            ->where('request_type', 'payment')
            ->where('module_type', $moduleType)->where('status', 'approved')->sum('amount');

        $pRsSumForFreight = PaymentRequest::whereHas('paymentRequestData', function ($query) use ($ticket) {
            $query->where('ticket_id', $ticket->id);
        })
            ->where('request_type', 'freight_payment')
            ->where('module_type', $moduleType)
            ->sum('amount');

        $samplingRequest = null;
        $samplingRequestCompulsuryResults = collect();
        $samplingRequestResults = collect();
        $otherDeduction = null;
        // $paymentRequestData = PaymentRequestData::where('ticket_id', $ticket->id)->where('module_type', 'purchase_order')->orderByDesc('id')->first();
        $paymentRequestData = $paymentRequest->paymentRequestData;
        $brokers = Broker::all();

        if (($purchaseOrderGateBuying->purchase_type ?? null) == 'gate_buying') {
            if ($ticket) {
                $samplingRequest = ArrivalSamplingRequest::where('arrival_ticket_id', $ticket->id)
                    ->whereIn('approved_status', ['approved', 'rejected'])
                    ->latest()
                    ->first();

                if ($samplingRequest) {
                    $rmPoSlabs = collect();

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

                $otherDeduction = PaymentRequest::whereHas('paymentRequestData', function ($query) use ($ticket, $moduleType) {
                    $query->where('ticket_id', $ticket->id);
                    $query->where('module_type', $moduleType);
                })->select('other_deduction_kg', 'other_deduction_value', 'rerate_on_access_weight_kg', 'rerate_on_access_weight_rate', 'rerate_on_access_weight_amount', 'filling_bag_amount', 'filling_bag_rate', 'no_of_filling_bags', 'other_adjustment_amount')
                    ->latest()
                    ->first();
            }
            $requestPurchaseForm = view('management.procurement.raw_material.gate_buying_payment_request.snippets.requestPurchaseForm', [
                'arrivalTicket' => $ticket,
                'ticket' => $ticket,
                'brokers' => $brokers,
                'purchaseOrder' => $purchaseOrder,
                'samplingRequest' => $samplingRequest,
                'samplingRequestCompulsuryResults' => $samplingRequestCompulsuryResults,
                'samplingRequestResults' => $samplingRequestResults,
                'requestedAmount' => $requestedAmount,
                'approvedAmount' => $approvedAmount,
                'pRsSumForFreight' => $pRsSumForFreight,
                'paymentRequestData' => $paymentRequestData,
                'otherDeduction' => $otherDeduction,
                'isRequestApprovalPage' => true,
                'paymentRequest' => $paymentRequest
            ])->render();
        } else if ($moduleType == 'ticket') {
            if ($ticket) {

                $samplingRequest = ArrivalSamplingRequest::where('arrival_ticket_id', $ticket->id)
                    ->whereIn('approved_status', ['approved', 'rejected'])
                    ->latest()
                    ->first();
                $freightPaymentRequestgrossAmount = paymentRequestData::where('ticket_id', $ticket->id)
                    // ->where('purchase_order_id', $arrivalTicket->arrival_purchase_order_id)
                    ->where('module_type', 'freight_payment')
                    ->latest()->first(); // id ya created_at ke hisaab se last record
                // dd($freightPaymentRequestgrossAmount);


                if ($freightPaymentRequestgrossAmount) {
                    if ($freightPaymentRequestgrossAmount->is_paid_by_supplier == 1) {
                        $freightPaymentRequestgrossAmount = $freightPaymentRequestgrossAmount->godown_penalty + $freightPaymentRequestgrossAmount->other_minus_labour + $freightPaymentRequestgrossAmount->commission_amount;
                    } else {
                        $freightPaymentRequestgrossAmount = $freightPaymentRequestgrossAmount->gross_amount;
                    }
                } else {
                    $freightPaymentRequestgrossAmount = null;
                }
                if ($samplingRequest) {
                    $rmPoSlabs = collect();

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

                $otherDeduction = PaymentRequest::whereHas('paymentRequestData', function ($query) use ($ticket, $moduleType) {
                    $query->where('ticket_id', $ticket->id);
                    $query->where('module_type', $moduleType);
                })->select('other_deduction_kg', 'other_deduction_value', 'rerate_on_access_weight_kg', 'rerate_on_access_weight_rate', 'rerate_on_access_weight_amount', 'filling_bag_amount', 'filling_bag_rate', 'no_of_filling_bags', 'other_adjustment_amount')
                    ->latest()
                    ->first();
            }

            // dd($otherDeduction);
            $requestPurchaseForm = view('management.procurement.raw_material.ticket_payment_request.snippets.requestPurchaseForm', [
                'arrivalTicket' => $ticket,
                'ticket' => $ticket,
                'brokers' => $brokers,
                'purchaseOrder' => $purchaseOrder,
                'samplingRequest' => $samplingRequest,
                'samplingRequestCompulsuryResults' => $samplingRequestCompulsuryResults,
                'samplingRequestResults' => $samplingRequestResults,
                'requestedAmount' => $requestedAmount,
                'approvedAmount' => $approvedAmount,
                'pRsSumForFreight' => $pRsSumForFreight,
                'paymentRequestData' => $paymentRequestData,
                'otherDeduction' => $otherDeduction,
                'isRequestApprovalPage' => true,
                'paymentRequest' => $paymentRequest,
                'freightPaymentRequestgrossAmount' => $freightPaymentRequestgrossAmount,
            ])->render();
        } else if ($moduleType == 'purchase_order') {
            if ($ticket) {
                $samplingRequest = PurchaseSamplingRequest::where('purchase_ticket_id', $ticket->id)
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

                $otherDeduction = PaymentRequest::whereHas('paymentRequestData', function ($query) use ($ticket, $moduleType) {
                    $query->where('ticket_id', $ticket->id);
                    $query->where('module_type', $moduleType);
                })->select('other_deduction_kg', 'other_deduction_value', 'rerate_on_access_weight_kg', 'rerate_on_access_weight_rate', 'rerate_on_access_weight_amount', 'filling_bag_amount', 'filling_bag_rate', 'no_of_filling_bags', 'other_adjustment_amount')
                    ->latest()
                    ->first();
            }
            $requestPurchaseForm = view('management.procurement.raw_material.payment_request.snippets.requestPurchaseForm', [
                'ticket' => $ticket,
                'purchaseOrder' => $purchaseOrder,
                'brokers' => $brokers,
                'paymentRequestData' => $paymentRequestData,
                'samplingRequest' => $samplingRequest,
                'samplingRequestCompulsuryResults' => $samplingRequestCompulsuryResults,
                'samplingRequestResults' => $samplingRequestResults,
                'requestedAmount' => $requestedAmount,
                'approvedAmount' => $approvedAmount,
                'pRsSumForFreight' => $pRsSumForFreight,
                'otherDeduction' => $otherDeduction,
                'isRequestApprovalPage' => true,
                'paymentRequest' => $paymentRequest
            ])->render();
        } else if ($moduleType == 'freight_payment') {
            $vendors = Vendor::all();
            $freightPaymentRequest = FreightPaymentRequest::where('arrival_ticket_id', $ticket->id)
                ->where('arrival_slip_id', $ticket->arrivalSlip->id)
                ->first();

            return view('management.procurement.raw_material.freight_request.create', [
                'arrivalTicket' => $ticket,
                'ticket' => $ticket,
                'freightPaymentRequest' => $freightPaymentRequest,
                'purchaseOrder' => $purchaseOrder,
                'vendors' => $vendors,
                'brokers' => $brokers,
                'isUpdated' => $isUpdated,
                'approval' => $approval,
                'paymentRequestData' => $paymentRequestData,
                'samplingRequest' => $samplingRequest,
                'samplingRequestCompulsuryResults' => $samplingRequestCompulsuryResults,
                'samplingRequestResults' => $samplingRequestResults,
                'requestedAmount' => $requestedAmount,
                'approvedAmount' => $approvedAmount,
                'pRsSumForFreight' => $pRsSumForFreight,
                'otherDeduction' => $otherDeduction,
                'isRequestApprovalPage' => true,
                'paymentRequest' => $paymentRequest
            ]);

            return view('management.procurement.raw_material.freight_request.snippets.approvalForm', [
                'paymentRequest' => $paymentRequest,
                'ticket' => $ticket,
                'requestPurchaseForm' => $requestPurchaseForm,
                'approval' => $approval
            ]);
        }

        return view('management.procurement.raw_material.payment_request_approval.snippets.approvalForm', [
            'paymentRequest' => $paymentRequest,
            'ticket' => $ticket,
            'isUpdated' => $isUpdated,
            'requestPurchaseForm' => $requestPurchaseForm,
            'approval' => $approval
        ]);
    }

    }
