<?php

use App\Models\Arrival\ArrivalTicket;
use App\Models\Arrival\ArrivalSamplingRequest;
use App\Models\Arrival\ArrivalSamplingResult;
use App\Models\Arrival\ArrivalSamplingResultForCompulsury;
use App\Models\PurchaseTicket;
use App\Models\PurchaseSamplingRequest;
use App\Models\Arrival\PurchaseSamplingResult;
use App\Models\Arrival\PurchaseSamplingResultForCompulsury;
use App\Models\Master\ProductSlab;
use App\Models\Master\ProductSlabForRmPo;
use App\Models\Procurement\PaymentRequest;

/**
 * Calculate payment details for a ticket based on sauda type
 * 
 * @param int $ticketId
 * @param int $saudaType (1 = Pohanch, 2 = Thadda)
 * @return object
 */
function calculatePaymentDetails($ticketId, $saudaType)
{
    if ($saudaType == 1) {
        return calculatePohaunchPayment($ticketId);
    } else {
        return calculateThaddaPayment($ticketId);
    }
}

/**
 * Calculate payment details for Pohanch (ArrivalTicket)
 */
function calculatePohaunchPayment($ticketId)
{
    $arrivalTicket = ArrivalTicket::with([
        'purchaseOrder',
        'freight',
        'paymentRequestData.paymentRequests'
    ])->findOrFail($ticketId);

    $purchaseOrder = $arrivalTicket->purchaseOrder ?? null;

    // Basic Information
    $basicInfo = [
        'ticket_number' => $arrivalTicket->unique_no,
        'supplier_name' => $purchaseOrder->supplier_name ?? ($purchaseOrder->supplier->name ?? 'N/A'),
        'contract_no' => $purchaseOrder->contract_no ?? 'N/A',
        'contract_rate' => $purchaseOrder->rate_per_kg ?? 0,
        'min_contract_range' => $purchaseOrder->min_quantity ?? 0,
        'max_contract_range' => $purchaseOrder->max_quantity ?? 0,
        'truck_no' => $arrivalTicket->truck_no ?? 'N/A',
        'arrival_date' => $arrivalTicket->freight->created_at ?? $arrivalTicket->created_at,
        'bilty_no' => $arrivalTicket->bilty_no ?? 'N/A',
        'station_name' => $arrivalTicket->station_name ?? 'N/A',
    ];

    // Loading Information
    $bagWeight = $arrivalTicket->bag_weight ?? 0;
    $bagRate = 0; // For Pohanch, bag rate is 0
    $loadingWeight = $arrivalTicket->freight->arrived_weight ?? 0;
    $noOfBags = $arrivalTicket->bags ?? 0;
    $ratePerKg = $purchaseOrder->rate_per_kg ?? 0;
    $kantaCharges = $arrivalTicket->freight->karachi_kanta_charges ?? 0;
    $grossFreightAmount = $arrivalTicket->freight->gross_freight_amount ?? 0;

    $loadingInfo = [
        'bag_weight' => $bagWeight,
        'bag_rate' => $bagRate,
        'loading_weight' => $loadingWeight,
        'no_of_bags' => $noOfBags,
        'rate_per_kg' => $ratePerKg,
        'kanta_charges' => $kantaCharges,
        'gross_freight_amount' => $grossFreightAmount,
        'avg_rate' => $noOfBags > 0 ? $loadingWeight / $noOfBags : 0,
        'net_weight' => $loadingWeight - ($bagWeight * $noOfBags),
    ];

    // Get Sampling Results
    $samplingData = getPohaunchSamplingResults($ticketId, $loadingInfo['net_weight'], $ratePerKg);

    // Calculate Deductions
    $deductions = calculatePohaunchDeductions($loadingInfo, $samplingData, $ratePerKg, $ticketId);

    // Get Payment History
    $paymentHistory = getPaymentHistory($arrivalTicket->paymentRequestData);

    // Calculate Amounts
    $amounts = calculatePohaunchAmounts($loadingInfo, $deductions, $ratePerKg, $grossFreightAmount, $paymentHistory);

    $supplierAmount = $loadingInfo['loading_weight'] * $purchaseOrder->supplier_commission;

    return   [
        'ticket_type' => 'pohanch',
        'ticket_id' => $ticketId,
        'basic_info' => $basicInfo,
        'loading_info' => $loadingInfo,
        // 'sampling_data' => $samplingData,
        'deductions' => $deductions,
        // 'amounts' => $amounts,
        'payment_history' => $paymentHistory,
        'calculations' => [
            'gross_amount' => $ratePerKg * $loadingWeight,
            'total_deductions' => $deductions['total_deductions'],
            'net_amount' => $amounts['total_amount'],
            'supplier_net_amount' => $purchaseOrder->supplier_commission < 0
                ? $amounts['total_amount'] - abs($supplierAmount)
                : $amounts['total_amount'] + abs($supplierAmount),
            'remaining_amount' => $amounts['remaining_amount'],
        ]
    ];
}

/**
 * Calculate payment details for Thadda (PurchaseTicket)
 */
function calculateThaddaPayment($ticketId)
{
    $purchaseTicket = PurchaseTicket::with([
        'purchaseOrder',
        'purchaseFreight',
        'paymentRequestData.paymentRequests'
    ])->findOrFail($ticketId);

    $purchaseOrder = $purchaseTicket->purchaseOrder;

    // Basic Information
    $basicInfo = [
        'ticket_number' => $purchaseTicket->unique_no,
        'supplier_name' => $purchaseOrder->supplier_name ?? ($purchaseOrder->supplier->name ?? 'N/A'),
        'contract_no' => $purchaseOrder->contract_no ?? 'N/A',
        'contract_rate' => $purchaseOrder->rate_per_kg ?? 0,
        'min_contract_range' => $purchaseOrder->min_quantity ?? 0,
        'max_contract_range' => $purchaseOrder->max_quantity ?? 0,
        'truck_no' => $purchaseTicket->purchaseFreight->truck_no ?? 'N/A',
        'loading_date' => $purchaseTicket->purchaseFreight->loading_date ?? null,
        'bilty_no' => $purchaseTicket->purchaseFreight->bilty_no ?? 'N/A',
        'station_name' => $purchaseOrder->station_name ?? 'N/A',
    ];

    // Loading Information
    $bagWeight = $purchaseTicket->bag_weight ?? 0;
    $bagRate = $purchaseTicket->bag_rate ?? 0;
    $loadingWeight = $purchaseTicket->purchaseFreight->loading_weight ?? 0;
    $noOfBags = $purchaseTicket->purchaseFreight->no_of_bags ?? 0;
    $ratePerKg = $purchaseOrder->rate_per_kg ?? 0;
    $kantaCharges = $purchaseTicket->purchaseFreight->kanta_charges ?? 0;
    $advanceFreight = $purchaseTicket->purchaseFreight->advance_freight ?? 0;

    $loadingInfo = [
        'bag_weight' => $bagWeight,
        'bag_rate' => $bagRate,
        'loading_weight' => $loadingWeight,
        'no_of_bags' => $noOfBags,
        'rate_per_kg' => $ratePerKg,
        'kanta_charges' => $kantaCharges,
        'advance_freight' => $advanceFreight,
        'avg_rate' => $noOfBags > 0 ? $loadingWeight / $noOfBags : 0,
        'net_weight' => $loadingWeight - ($bagWeight * $noOfBags),
    ];

    // Get Sampling Results
    $samplingData = getThaddaSamplingResults($ticketId, $loadingInfo['net_weight'], $ratePerKg);

    // Calculate Deductions
    $deductions = calculateThaddaDeductions($loadingInfo, $samplingData, $ratePerKg, $ticketId);

    // Get Payment History
    $paymentHistory = getPaymentHistory($purchaseTicket->paymentRequestData);

    // Calculate Amounts
    $amounts = calculateThaddaAmounts($loadingInfo, $deductions, $ratePerKg, $paymentHistory);

    // Get Freight Information
    $freightInfo = getFreightInfo($purchaseTicket->paymentRequestData, $advanceFreight);

    $supplierAmount = $loadingInfo['loading_weight'] * $purchaseOrder->supplier_commission;

    return   [
        'ticket_type' => 'thadda',
        'ticket_id' => $ticketId,
        'basic_info' => $basicInfo,
        'loading_info' => $loadingInfo,
        // 'sampling_data' => $samplingData,
        'deductions' => $deductions,
        // 'amounts' => $amounts,
        'payment_history' => $paymentHistory,
        'freight_info' => $freightInfo,
        'calculations' => [
            'gross_amount' => $ratePerKg * $loadingWeight,
            'total_deductions' => $deductions['total_deductions'],
            'net_amount' => $amounts['total_amount'],
            'supplier_net_amount' => $purchaseOrder->supplier_commission < 0
                ? $amounts['total_amount'] - abs($supplierAmount)
                : $amounts['total_amount'] + abs($supplierAmount),
            'remaining_amount' => $amounts['remaining_amount'],
        ]
    ];
}

/**
 * Get sampling results for Pohanch
 */
function getPohaunchSamplingResults($ticketId, $netWeight, $ratePerKg)
{
    $samplingRequest = ArrivalSamplingRequest::where('arrival_ticket_id', $ticketId)
        ->whereIn('approved_status', ['approved', 'rejected'])
        ->latest()
        ->first();

    if (!$samplingRequest) {
        return [
            'sampling_request' => null,
            'sampling_results' => collect(),
            'compulsory_results' => collect(),
            'show_lumpsum' => false,
            'lumpsum_deduction' => 0,
            'lumpsum_deduction_kgs' => 0,
        ];
    }

    $showLumpSum = $samplingRequest->is_lumpsum_deduction && $samplingRequest->lumpsum_deduction > 0;

    $samplingResults = collect();
    $compulsoryResults = collect();

    if (!$showLumpSum) {
        $samplingResults = ArrivalSamplingResult::where('arrival_sampling_request_id', $samplingRequest->id)
            ->with('slabType')
            ->get()
            ->filter(function ($result) {
                return $result->applied_deduction > 0;
            });

        $compulsoryResults = ArrivalSamplingResultForCompulsury::where('arrival_sampling_request_id', $samplingRequest->id)
            ->with('qcParam')
            ->get()
            ->filter(function ($result) {
                return $result->applied_deduction > 0;
            });

        // Add matching slabs for each result
        foreach ($samplingResults as $result) {
            if ($samplingRequest->arrival_product_id) {
                $productSlabs = ProductSlab::where('product_id', $samplingRequest->arrival_product_id)
                    ->where('product_slab_type_id', $result->product_slab_type_id)
                    ->get();

                $result->matching_slabs = $productSlabs->toArray();
                $result->deduction_type = $productSlabs->first()->deduction_type ?? 'amount';
            }
        }
    }

    return [
        'sampling_request' => $samplingRequest,
        'sampling_results' => $samplingResults,
        'compulsory_results' => $compulsoryResults,
        'show_lumpsum' => $showLumpSum,
        'lumpsum_deduction' => $samplingRequest->lumpsum_deduction ?? 0,
        'lumpsum_deduction_kgs' => $samplingRequest->lumpsum_deduction_kgs ?? 0,
    ];
}

/**
 * Get sampling results for Thadda
 */
function getThaddaSamplingResults($ticketId, $netWeight, $ratePerKg)
{
    $samplingRequest = PurchaseSamplingRequest::where('purchase_ticket_id', $ticketId)
        ->whereIn('approved_status', ['approved', 'rejected'])
        ->latest()
        ->first();

    if (!$samplingRequest) {
        return [
            'sampling_request' => null,
            'sampling_results' => collect(),
            'compulsory_results' => collect(),
            'show_lumpsum' => false,
            'lumpsum_deduction' => 0,
            'lumpsum_deduction_kgs' => 0,
        ];
    }

    $showLumpSum = $samplingRequest->is_lumpsum_deduction && $samplingRequest->lumpsum_deduction > 0;

    $samplingResults = collect();
    $compulsoryResults = collect();

    if (!$showLumpSum) {
        $samplingResults = PurchaseSamplingResult::where('purchase_sampling_request_id', $samplingRequest->id)
            ->with('slabType')
            ->get()
            ->filter(function ($result) {
                return $result->applied_deduction > 0;
            });

        $compulsoryResults = PurchaseSamplingResultForCompulsury::where('purchase_sampling_request_id', $samplingRequest->id)
            ->with('qcParam')
            ->get()
            ->filter(function ($result) {
                return $result->applied_deduction > 0;
            });

        // Add matching slabs and RM PO slabs for each result
        foreach ($samplingResults as $result) {
            if ($samplingRequest->arrival_product_id) {
                $productSlabs = ProductSlab::where('product_id', $samplingRequest->arrival_product_id)
                    ->where('product_slab_type_id', $result->product_slab_type_id)
                    ->get();

                $result->matching_slabs = $productSlabs->toArray();
                $result->deduction_type = $productSlabs->first()->deduction_type ?? 'amount';

                // Get RM PO Slabs
                $ticket = PurchaseTicket::find($ticketId);
                if ($ticket && $ticket->purchaseOrder) {
                    $rmPoSlabs = ProductSlabForRmPo::where('arrival_purchase_order_id', $ticket->purchaseOrder->id)
                        ->where('product_id', $samplingRequest->arrival_product_id)
                        ->where('product_slab_type_id', $result->product_slab_type_id)
                        ->get();

                    $result->rm_po_slabs = $rmPoSlabs->toArray();
                }
            }
        }
    }

    return [
        'sampling_request' => $samplingRequest,
        'sampling_results' => $samplingResults,
        'compulsory_results' => $compulsoryResults,
        'show_lumpsum' => $showLumpSum,
        'lumpsum_deduction' => $samplingRequest->lumpsum_deduction ?? 0,
        'lumpsum_deduction_kgs' => $samplingRequest->lumpsum_deduction_kgs ?? 0,
    ];
}

/**
 * Calculate deductions for Pohanch
 */
function calculatePohaunchDeductions($loadingInfo, $samplingData, $ratePerKg, $ticketId)
{
    $totalSamplingDeductions = 0;
    $samplingDeductionDetails = [];
    $compulsoryDeductionDetails = [];

    if ($samplingData['show_lumpsum']) {
        // Lumpsum calculations
        $lumpsumCalculatedValue = 0;
        $lumpsumKgsCalculatedValue = 0;

        if ($samplingData['lumpsum_deduction'] > 0) {
            $lumpsumCalculatedValue = $samplingData['lumpsum_deduction'] * $loadingInfo['net_weight'];
            $totalSamplingDeductions += $lumpsumCalculatedValue;
        }

        if ($samplingData['lumpsum_deduction_kgs'] > 0) {
            $lumpsumKgsCalculatedValue = $samplingData['lumpsum_deduction_kgs'] * $loadingInfo['net_weight'];
            $lumpsumKgsCalculatedValue = ($lumpsumKgsCalculatedValue / 100) * $ratePerKg;
            $totalSamplingDeductions += $lumpsumKgsCalculatedValue;
        }

        $samplingDeductionDetails['lumpsum'] = [
            'amount_deduction' => $lumpsumCalculatedValue,
            'kgs_deduction' => $lumpsumKgsCalculatedValue,
            'total' => $lumpsumCalculatedValue + $lumpsumKgsCalculatedValue
        ];
    } else {
        // Regular sampling results
        // foreach ($samplingData['sampling_results'] as $slab) {
        //     $calculatedValue = calculateSlabDeduction($slab, $loadingInfo['net_weight'], $ratePerKg);
        //     $totalSamplingDeductions += $calculatedValue;

        //     $samplingDeductionDetails[] = [
        //         'id' => $slab->id,
        //         'name' => $slab->slabType->name,
        //         'applied_deduction' => $slab->applied_deduction,
        //         'calculated_value' => $calculatedValue,
        //         'deduction_type' => $slab->deduction_type ?? 'amount'
        //     ];
        // }

        foreach ($samplingData['sampling_results'] as $slab) {
            $calculatedValue =  calculateSlabDeduction($slab, $loadingInfo['net_weight'], $ratePerKg);
            $totalSamplingDeductions += $calculatedValue;

            $samplingDeductionDetails[] = [
                'id' => $slab->id,
                'name' => $slab->slabType->name,
                'applied_deduction' => $slab->applied_deduction,
                'calculated_value' => $calculatedValue,
                'deduction_type' => $slab->deduction_type ?? 'amount'
            ];
        }

        // Compulsory results
        foreach ($samplingData['compulsory_results'] as $slab) {
            $calculatedValue = $slab->applied_deduction * $loadingInfo['net_weight'];
            $totalSamplingDeductions += $calculatedValue;

            $compulsoryDeductionDetails[] = [
                'id' => $slab->id,
                'name' => $slab->qcParam->name ?? 'Compulsory',
                'applied_deduction' => $slab->applied_deduction,
                'calculated_value' => $calculatedValue
            ];
        }
    }

    // Other calculations
    //  $bagWeightInKgSum = $ratePerKg * ($loadingInfo['bag_weight'] * $loadingInfo['no_of_bags']);
    $bagWeightInKgSum = 0;
    // $loadingWeighbridgeSum = $loadingInfo['kanta_charges'] / 2;
    $loadingWeighbridgeSum = 0;
    // $bagsRateSum = $loadingInfo['bag_rate'] * $loadingInfo['no_of_bags'];
    $bagsRateSum = 0;
    $otherDeductionValue = 0;

    if (!empty($samplingData['sampling_request'])) {
        $otherDeduction = PaymentRequest::whereHas('paymentRequestData', function ($query) use ($ticketId) {
            $query->where('ticket_id', $ticketId)
                ->where('module_type', 'ticket');
        })
            ->select('other_deduction_kg', 'other_deduction_value')
            ->latest()
            ->first();

        $otherDeductionValue = (float)($otherDeduction->other_deduction_value ?? 0);
    }

    return [
        'total_sampling_deductions' => $totalSamplingDeductions,
        'sampling_deduction_details' => $samplingDeductionDetails,
        'compulsory_deduction_details' => $compulsoryDeductionDetails,
        'bag_weight_in_kg_sum' => $bagWeightInKgSum,
        'other_deduction_calculated' => $otherDeductionValue,
        'loading_weighbridge_sum' => $loadingWeighbridgeSum,
        'bags_rate_sum' => $bagsRateSum,
        'total_deductions' => $totalSamplingDeductions + $bagWeightInKgSum + $loadingWeighbridgeSum + $bagsRateSum,
    ];
}

/**
 * Calculate deductions for Thadda
 */
function calculateThaddaDeductions($loadingInfo, $samplingData, $ratePerKg, $ticketId)
{
    $totalSamplingDeductions = 0;
    $samplingDeductionDetails = [];
    $compulsoryDeductionDetails = [];

    if ($samplingData['show_lumpsum']) {
        // Lumpsum calculations
        $lumpsumCalculatedValue = 0;
        $lumpsumKgsCalculatedValue = 0;

        if ($samplingData['lumpsum_deduction'] > 0) {
            $lumpsumCalculatedValue = $samplingData['lumpsum_deduction'] * $loadingInfo['net_weight'];
            $totalSamplingDeductions += $lumpsumCalculatedValue;
        }

        if ($samplingData['lumpsum_deduction_kgs'] > 0) {
            $lumpsumKgsCalculatedValue = $samplingData['lumpsum_deduction_kgs'] * $loadingInfo['net_weight'];
            $lumpsumKgsCalculatedValue = ($lumpsumKgsCalculatedValue / 100) * $ratePerKg;
            $totalSamplingDeductions += $lumpsumKgsCalculatedValue;
        }

        $samplingDeductionDetails['lumpsum'] = [
            'amount_deduction' => $lumpsumCalculatedValue,
            'kgs_deduction' => $lumpsumKgsCalculatedValue,
            'total' => $lumpsumCalculatedValue + $lumpsumKgsCalculatedValue
        ];
    } else {
        // Regular sampling results with RM PO Slabs consideration
        foreach ($samplingData['sampling_results'] as $slab) {
            $calculatedValue = calculateSlabDeductionWithRmPo($slab, $loadingInfo['net_weight'], $ratePerKg);
            $totalSamplingDeductions += $calculatedValue;

            $samplingDeductionDetails[] = [
                'id' => $slab->id,
                'name' => $slab->slabType->name,
                'applied_deduction' => $slab->applied_deduction,
                'calculated_value' => $calculatedValue,
                'deduction_type' => $slab->deduction_type ?? 'amount'
            ];
        }

        // Compulsory results
        foreach ($samplingData['compulsory_results'] as $slab) {
            $calculatedValue = $slab->applied_deduction * $loadingInfo['net_weight'];
            $totalSamplingDeductions += $calculatedValue;

            $compulsoryDeductionDetails[] = [
                'id' => $slab->id,
                'name' => $slab->qcParam->name ?? 'Compulsory',
                'applied_deduction' => $slab->applied_deduction,
                'calculated_value' => $calculatedValue
            ];
        }
    }

    // Other calculations
    $bagWeightInKgSum = $ratePerKg * ($loadingInfo['bag_weight'] * $loadingInfo['no_of_bags']);
    $loadingWeighbridgeSum = $loadingInfo['kanta_charges'] / 2;
    $bagsRateSum = $loadingInfo['bag_rate'] * $loadingInfo['no_of_bags'];

    // $otherDeductionValue = 0;

    // if ($samplingData['sampling_request']) {
    //     $otherDeduction = PaymentRequest::whereHas('paymentRequestData', function ($query) use ($ticketId) {
    //         $query->where('ticket_id', $ticketId);
    //     })->select('other_deduction_kg', 'other_deduction_value')
    //         ->latest()
    //         ->first();

    //     $otherDeductionValue = (float)($otherDeduction->other_deduction_value ?? '0');
    // }

    $otherDeductionValue = 0;

    if (!empty($samplingData['sampling_request'])) {
        $otherDeduction = PaymentRequest::whereHas('paymentRequestData', function ($query) use ($ticketId) {
            $query->where('ticket_id', $ticketId)
                ->where('module_type', 'purchase_order');
        })
            ->select('other_deduction_kg', 'other_deduction_value')
            ->latest()
            ->first();

        $otherDeductionValue = (float)($otherDeduction->other_deduction_value ?? 0);
    }

    return [
        'total_sampling_deductions' => $totalSamplingDeductions,
        'sampling_deduction_details' => $samplingDeductionDetails,
        'compulsory_deduction_details' => $compulsoryDeductionDetails,
        'bag_weight_in_kg_sum' => $bagWeightInKgSum,
        'other_deduction_calculated' => $otherDeductionValue,
        'loading_weighbridge_sum' => $loadingWeighbridgeSum,
        'bags_rate_sum' => $bagsRateSum,
        'total_deductions' => $totalSamplingDeductions + $bagWeightInKgSum + $loadingWeighbridgeSum + $bagsRateSum,
    ];
}

/**
 * Calculate amounts for Pohanch
 */
function calculatePohaunchAmounts($loadingInfo, $deductions, $ratePerKg, $grossFreightAmount, $paymentHistory)
{
    $grossAmount = $ratePerKg * $loadingInfo['loading_weight'];
    $totalDeductionsForFormula = $deductions['total_sampling_deductions'] +
        $deductions['bag_weight_in_kg_sum'] +
        $deductions['loading_weighbridge_sum'] +
        $deductions['other_deduction_calculated'];;

    $totalAmount = $grossAmount - $totalDeductionsForFormula + $deductions['bags_rate_sum'] - $grossFreightAmount;

    return [
        'gross_amount' => $grossAmount,
        'total_amount' => $totalAmount,
        'remaining_amount' => $totalAmount - $paymentHistory['total_payment_sum'],
    ];
}

/**
 * Calculate amounts for Thadda
 */
function calculateThaddaAmounts($loadingInfo, $deductions, $ratePerKg)
{
    $grossAmount = $ratePerKg * $loadingInfo['loading_weight'];
    $totalDeductionsForFormula = $deductions['total_sampling_deductions'] +
        $deductions['bag_weight_in_kg_sum'] +
        $deductions['loading_weighbridge_sum'] +
        $deductions['other_deduction_calculated'];

    $totalAmount = $grossAmount - $totalDeductionsForFormula + $deductions['bags_rate_sum'];

    return [
        'gross_amount' => $grossAmount,
        'total_amount' => $totalAmount,
        'remaining_amount' => $totalAmount, // Will be updated with payment history
    ];
}

/**
 * Calculate slab deduction (for Pohanch - simpler calculation)
 */
// function calculateSlabDeduction($slab, $netWeight, $ratePerKg)
// {
//     $deductionValue = $slab->applied_deduction ?? 0;
//     $calculatedValue = $deductionValue * $netWeight;

//     if (($slab->deduction_type ?? 'amount') !== 'amount') {
//         $calculatedValue = ($calculatedValue / 100) * $ratePerKg;
//     }

//     return $calculatedValue;
// }


function calculateSlabDeduction($slab, $netWeight, $ratePerKg)
{
    $dValCalculatedOn = $slab->slabType->calculation_base_type ?? 1;
    $appliedDeduction = $slab->applied_deduction ?? 0;
    $matchingSlabs = $slab->matching_slabs ?? [];
    $val = $slab->applied_deduction;
    $deductionValue = 0;
    $sumOfMatchingValues = '';

    if ($dValCalculatedOn == SLAB_TYPE_PERCENTAGE && $matchingSlabs) {
        // Sort matching slabs by 'from' value
        usort($matchingSlabs, function ($a, $b) {
            return floatval($a['from']) <=> floatval($b['from']);
        });

        $rmPoSlabs = $slab->rm_po_slabs ?? [];
        $highestRmPoEnd = 0;

        // For Pohanch, RM PO slabs might not be present, but keep the logic consistent
        foreach ($rmPoSlabs as $rmPoSlab) {
            $rmPoTo = isset($rmPoSlab['to']) ? floatval($rmPoSlab['to']) : 0;
            if ($rmPoTo > $highestRmPoEnd) {
                $highestRmPoEnd = $rmPoTo;
            }
        }

        foreach ($matchingSlabs as $mSlab) {
            $from = floatval($mSlab['from']);
            $to = floatval($mSlab['to']);
            $isTiered = intval($mSlab['is_tiered']);
            $deductionVal = floatval($mSlab['deduction_value'] ?? 0);

            if ($val >= $from) {
                $effectiveFrom = max($from, $highestRmPoEnd + 1);
                $effectiveTo = min($to, $val);

                if ($effectiveFrom <= $effectiveTo) {
                    if ($isTiered === 1) {
                        $applicableAmount = $effectiveTo - $effectiveFrom + 1;
                        $sumOfMatchingValues .= "$deductionVal x $applicableAmount = " .
                            ($deductionVal * $applicableAmount) . '<br>';
                        $deductionValue += $deductionVal * $applicableAmount;
                    } else {
                        $deductionValue += $deductionVal;
                        $sumOfMatchingValues .= "$deductionVal<br>";
                    }
                }
            }
        }

        if (!empty($rmPoSlabs)) {
            $sumOfMatchingValues .= '<br><br>RM PO Slabs (Free Ranges):<br>';
            foreach ($rmPoSlabs as $rmPoSlab) {
                $sumOfMatchingValues .= "{$rmPoSlab['from']} - {$rmPoSlab['to']}<br>";
            }
            $sumOfMatchingValues .= "<br>Only values above $highestRmPoEnd are calculated";
        }
    } else {
        $deductionValue = $appliedDeduction;
    }

    // Calculate deduction amount based on net weight
    $calculatedValue = $deductionValue * $netWeight;
    if (($slab->deduction_type ?? 'amount') !== 'amount') {
        $calculatedValue = ($calculatedValue / 100) * $ratePerKg;
    }

    return $calculatedValue;
}

/**
 * Calculate slab deduction with RM PO consideration (for Thadda)
 */
function calculateSlabDeductionWithRmPo($slab, $netWeight, $ratePerKg)
{
    $dValCalculatedOn = $slab->slabType->calculation_base_type ?? 1;
    $appliedDeduction = $slab->applied_deduction ?? 0;
    $matchingSlabs = $slab->matching_slabs ?? [];
    $rmPoSlabs = $slab->rm_po_slabs ?? [];
    $val = $slab->applied_deduction;
    $deductionValue = 0;

    if ($dValCalculatedOn == SLAB_TYPE_PERCENTAGE && !empty($matchingSlabs)) {
        // Sort matching slabs by 'from' value
        usort($matchingSlabs, function ($a, $b) {
            return floatval($a['from']) <=> floatval($b['from']);
        });

        // Find highest RM PO end value
        $highestRmPoEnd = 0;
        foreach ($rmPoSlabs as $rmPoSlab) {
            $rmPoTo = isset($rmPoSlab['to']) ? floatval($rmPoSlab['to']) : 0;
            if ($rmPoTo > $highestRmPoEnd) {
                $highestRmPoEnd = $rmPoTo;
            }
        }

        foreach ($matchingSlabs as $mSlab) {
            $from = floatval($mSlab['from']);
            $to = floatval($mSlab['to']);
            $isTiered = intval($mSlab['is_tiered']);
            $deductionVal = floatval($mSlab['deduction_value'] ?? 0);

            if ($val >= $from) {
                $effectiveFrom = max($from, $highestRmPoEnd + 1);
                $effectiveTo = min($to, $val);

                if ($effectiveFrom <= $effectiveTo) {
                    if ($isTiered === 1) {
                        $applicableAmount = $effectiveTo - $effectiveFrom + 1;
                        $deductionValue += $deductionVal * $applicableAmount;
                    } else {
                        $deductionValue += $deductionVal;
                    }
                }
            }
        }
    } else {
        $deductionValue = $appliedDeduction;
    }

    // Calculate final value based on net weight
    $calculatedValue = $deductionValue * $netWeight;
    if (($slab->deduction_type ?? 'amount') !== 'amount') {
        $calculatedValue = ($calculatedValue / 100) * $ratePerKg;
    }

    return $calculatedValue;
}

/**
 * Get payment history
 */
function getPaymentHistory($paymentRequestData)
{
    $requestedAmount = 0;
    $approvedAmount = 0;
    $totalPaymentSum = 0;
    $totalFreightSum = 0;
    $approvedPaymentSum = 0;
    $approvedFreightSum = 0;

    foreach ($paymentRequestData as $data) {
        foreach ($data->paymentRequests as $pRequest) {
            if ($pRequest->request_type == 'payment') {
                $totalPaymentSum += $pRequest->amount;
                if ($pRequest->status == 'approved') {
                    $approvedPaymentSum += $pRequest->amount;
                }
            } else {
                $totalFreightSum += $pRequest->amount;
                if ($pRequest->status == 'approved') {
                    $approvedFreightSum += $pRequest->amount;
                }
            }
        }
    }

    return [
        'total_payment_sum' => $totalPaymentSum,
        'approved_payment_sum' => $approvedPaymentSum,
        'total_freight_sum' => $totalFreightSum,
        'total_approved_freight_sum' => $approvedFreightSum,
    ];
}

/**
 * Get freight information (for Thadda)
 */
function getFreightInfo($paymentRequestData, $advanceFreight)
{
    $paidFreight = 0;

    foreach ($paymentRequestData as $data) {
        foreach ($data->paymentRequests as $pRequest) {
            if ($pRequest->request_type == 'freight_payment') {
                // if ($pRequest->request_type == 'freight_payment' && $pRequest->status == 'approved') {
                $paidFreight += $pRequest->amount;
            }
        }
    }

    return [
        'advance_freight' => $advanceFreight,
        'paid_freight' => $paidFreight,
        'remaining_freight' => $advanceFreight - $paidFreight,
    ];
}
