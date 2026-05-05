<style>
    .gate-out-pass {
        background: #fff;
        max-width: 900px;
        margin: 20px auto;
        font-family: Arial, sans-serif;
        color: #000;
    }
    .gate-out-pass-inner {
        padding: 30px;
    }
    .gate-out-header {
        text-align: center;
        margin-bottom: 40px;
    }
    .gate-out-header h3 {
        font-size: 22px;
        font-weight: bold;
        margin-bottom: 5px;
        text-decoration: underline;
    }
    .gate-out-header h4 {
        font-size: 16px;
        font-weight: bold;
    }
    .gate-field {
        margin-bottom: 15px;
        display: flex;
        align-items: baseline;
    }
    .gate-label {
        font-weight: bold;
        font-size: 14px;
        min-width: 140px;
    }
    .gate-value {
        font-size: 14px;
        flex: 1;
    }
    
    .print-button-container {
        text-align: center;
        margin: 40px auto;
    }
    @media print {
        body * {
            visibility: hidden !important;
        }
        #gate-out-pass,
        #gate-out-pass * {
            visibility: visible !important;
        }
        #gate-out-pass {
            position: absolute !important;
            left: 0 !important;
            top: 0 !important;
            width: 100% !important;
            margin: 0 !important;
            padding: 20px !important;
        }
        .print-button-container, .no-print {
            display: none !important;
        }
        @page {
            margin: 1cm;
            size: A4 portrait;
        }
    }
</style>

@php
    $item = $DispatchQc->loadingProgramItem;
    $loadingSlip = $item?->exportLoadingSlip;
    $exportOrder = $item?->exportLoadingProgram?->exportOrder;
    $secondWeighbridge = $loadingSlip?->secondWeighbridge;
    $netWeight = $secondWeighbridge?->net_weight ?? 0;
    
    // Variables mapped from image requirements
    $date = \Carbon\Carbon::parse($deliveryChallan->dispatch_date ?? now())->format('d/m/Y');
    
    $allDos = $item->deliveryOrders()->where('delivery_order.type', 'export_order')->get();
    if ($allDos->isEmpty() && $item->exportLoadingProgram?->deliveryOrder) {
        $allDos = collect([$item->exportLoadingProgram->deliveryOrder]);
    }
    
    $deliveryOrder = $allDos->first();
    $partyName = $DispatchQc->customer ?? ($deliveryOrder->customer->name ?? '');
    
    $brandName = '';
    if ($deliveryChallan && $deliveryChallan->delivery_challan_data->isNotEmpty()) {
        $brandName = getBrandById($deliveryChallan->delivery_challan_data->first()->brand_id)?->name ?? '';
    } else {
        $brandName = $deliveryOrder?->exportPackingItems?->first()?->brand?->name ?? '';
    }
    
    $commodity = $DispatchQc->commodity ?? ($exportOrder->product->name ?? '');
    $truckNo = $item->truck_number ?? '';
    $containerNo = $item->container_number ?? '';
    
    // Proportional calculation for Bags based on the entire Export Order (EO) totals
    $eoPackingItems = $exportOrder?->packingItems ?? collect();
    $totalEoMt = (float) $eoPackingItems->sum('metric_tons');
    $totalEoEmptyBags = (float) $eoPackingItems->sum('empty_bags');
    $totalEoNoOfBags = (float) $eoPackingItems->sum('no_of_bags');
    
    $actualWeightMt = $netWeight / 1000;
    $noOfBags = (float) ($loadingSlip?->no_of_bags ?? 0);
    
    $emptyBags = 0;
    if ($totalEoMt > 0) {
        $ratio = $actualWeightMt / $totalEoMt;
        $emptyBags = round($totalEoEmptyBags * $ratio);
        
        if ($noOfBags == 0 && $totalEoNoOfBags > 0) {
            $noOfBags = round($totalEoNoOfBags * $ratio);
        }
    }

    $deliveryOrder = $allDos->first();
    $packingItem = $eoPackingItems->first();
    $packingSize = $packingItem?->bag_size ?? '';
    $bagType = $packingItem?->bagType?->name ?? '';
    
    $galaa = $DispatchQc->gala ?? ($item->subArrivalLocation->name ?? '');
    $location = $DispatchQc->factory ?? ($item->arrivalLocation->name ?? '');
    
    $gpNo = ''; // blank
    $brokerName = $exportOrder?->broker?->name ?? '';
    $formENo = $allDos->map(function($do) { return $do->exportFormE?->form_e_no; })->filter()->unique()->implode(' & ');
    $doNo = $allDos->pluck('reference_no')->unique()->implode(' / ');
    $sealNo = $loadingSlip?->seal_no ?? '';
    $port = $exportOrder?->portOfLoading?->name ?? '';
    $vesselName = $item?->exportLoadingProgram?->vessel_name ?? '';
    $shedBerthNo = $item?->berth_no ?? '';
    $sBillNo = $item?->s_bill_no ?? '0';
    $clearingAgent = $deliveryOrder?->c_agent ?? '';
    $loaderName = ''; // blank
    $preparedBy = $deliveryChallan?->createdBy?->name ?? '';
@endphp

<div class="gate-out-pass" id="gate-out-pass">
    <div class="gate-out-pass-inner">
        <div class="gate-out-header">
            <h3>EXPORT GATE PASS</h3>
            <h4>ORIGINAL</h4>
        </div>

        <div class="row">
            <!-- Left Column -->
            <div class="col-6">
                <div class="gate-field">
                    <div class="gate-label">Date:</div>
                    <div class="gate-value">{{ $date }}</div>
                </div>
                <div class="gate-field">
                    <div class="gate-label">Party Name:</div>
                    <div class="gate-value">{{ $partyName }}</div>
                </div>
                <div class="gate-field">
                    <div class="gate-label">Brand Name:</div>
                    <div class="gate-value">{{ $brandName }}</div>
                </div>
                <div class="gate-field">
                    <div class="gate-label">Commodity:</div>
                    <div class="gate-value">{{ $commodity }}</div>
                </div>
                <div class="gate-field">
                    <div class="gate-label">Trucks No.:</div>
                    <div class="gate-value">{{ $truckNo }}</div>
                </div>
                <div class="gate-field">
                    <div class="gate-label">Container No.:</div>
                    <div class="gate-value">{{ $containerNo }}</div>
                </div>
                <div class="gate-field">
                    <div class="gate-label">Packing in KG:</div>
                    <div class="gate-value">{{ $packingSize }}</div>
                </div>
                <div class="gate-field">
                    <div class="gate-label">Empty Bags:</div>
                    <div class="gate-value">{{ $emptyBags }}</div>
                </div>
                <div class="gate-field">
                    <div class="gate-label">No. of Bags:</div>
                    <div class="gate-value">{{ $noOfBags }}</div>
                </div>
                <div class="gate-field">
                    <div class="gate-label">Bag Type:</div>
                    <div class="gate-value">{{ $bagType }}</div>
                </div>
                <div class="gate-field">
                    <div class="gate-label">Net Weight:</div>
                    <div class="gate-value">{{ number_format($netWeight, 0) }}</div>
                </div>
                <div class="gate-field">
                    <div class="gate-label">Galaa:</div>
                    <div class="gate-value">{{ $galaa }}</div>
                </div>
                <div class="gate-field">
                    <div class="gate-label">Location:</div>
                    <div class="gate-value">{{ $location }}</div>
                </div>
            </div>

            <!-- Right Column -->
            <div class="col-6">
                <div class="gate-field">
                    <div class="gate-label">G.P. NO.</div>
                    <div class="gate-value">{{ $gpNo }}</div>
                </div>
                <div class="gate-field">
                    <div class="gate-label">Broker Name:</div>
                    <div class="gate-value">{{ $brokerName }}</div>
                </div>
                <div class="gate-field">
                    <div class="gate-label">E - Form No.:</div>
                    <div class="gate-value">{{ $formENo }}</div>
                </div>
                <div class="gate-field">
                    <div class="gate-label">Delivery Order No.:</div>
                    <div class="gate-value">{{ $doNo }}</div>
                </div>
                <div class="gate-field">
                    <div class="gate-label">Seal No.:</div>
                    <div class="gate-value">{{ $sealNo }}</div>
                </div>
                <div class="gate-field">
                    <div class="gate-label">Port:</div>
                    <div class="gate-value">{{ $port }}</div>
                </div>
                <div class="gate-field">
                    <div class="gate-label">Vessel Name:</div>
                    <div class="gate-value">{{ $vesselName }}</div>
                </div>
                <div class="gate-field">
                    <div class="gate-label">Shed / Berth No.:</div>
                    <div class="gate-value">{{ $shedBerthNo }}</div>
                </div>
                <div class="gate-field">
                    <div class="gate-label">S. Bill No.:</div>
                    <div class="gate-value">{{ $sBillNo }}</div>
                </div>
                <div class="gate-field">
                    <div class="gate-label">Clearing Agent:</div>
                    <div class="gate-value">{{ $clearingAgent }}</div>
                </div>
                <div class="gate-field">
                    <div class="gate-label">Loader Name:</div>
                    <div class="gate-value">{{ $loaderName }}</div>
                </div>
                <div class="gate-field">
                    <div class="gate-label">Prepared By:</div>
                    <div class="gate-value">{{ $preparedBy }}</div>
                </div>
            </div>
        </div>

    </div>
</div>

<div class="print-button-container no-print">
    <button class="btn btn-primary" onclick="window.print()">
        <i class="ft-printer mr-2"></i> Print Gate Out Pass
    </button>
</div>
