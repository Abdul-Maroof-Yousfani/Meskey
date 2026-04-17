<style>
    .gate-out-pass {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 12px;
        padding: 4px;
        max-width: 900px;
        margin: 20px auto;
        box-shadow: 0 10px 30px rgba(0,0,0,0.15);
    }
    .gate-out-pass-inner {
        background: #fff;
        border-radius: 10px;
        padding: 30px;
    }
    .gate-out-header {
        text-align: center;
        border-bottom: 2px dashed #ddd;
        padding-bottom: 20px;
        margin-bottom: 25px;
    }
    .gate-out-header h3 {
        color: #667eea;
        font-weight: 700;
        margin-bottom: 5px;
        font-size: 26px;
    }
    .gate-out-header .ticket-number {
        font-size: 15px;
        color: #666;
    }
    .gate-out-field {
        margin-bottom: 18px;
    }
    .gate-out-field label {
        font-weight: 600;
        color: #555;
        font-size: 13px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 6px;
        display: block;
    }
    .gate-out-field .value {
        font-size: 16px;
        color: #333;
        padding: 12px 15px;
        background: #f8f9fa;
        border-radius: 8px;
        border: 1px solid #e9ecef;
    }
    .gate-out-field .value.highlight {
        background: linear-gradient(135deg, #667eea15 0%, #764ba215 100%);
        border-color: #667eea40;
        font-weight: 600;
    }
    .status-badge {
        display: inline-block;
        padding: 8px 20px;
        border-radius: 30px;
        font-size: 14px;
        font-weight: 600;
        text-transform: uppercase;
    }
    .status-badge.accepted {
        background: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }
    .gate-out-footer {
        margin-top: 30px;
        padding-top: 20px;
        border-top: 2px dashed #ddd;
        text-align: center;
    }
    .gate-out-footer .timestamp {
        font-size: 13px;
        color: #888;
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
            position: fixed !important;
            left: 0 !important;
            top: 0 !important;
            width: 100% !important;
            max-width: none !important;
            margin: 0 !important;
            padding: 20px !important;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
            border-radius: 12px !important;
            z-index: 9999 !important;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
        .gate-out-pass-inner {
            padding: 40px !important;
            border-radius: 10px !important;
            background: white !important;
        }
        .row {
            display: flex !important;
            flex-wrap: wrap !important;
            margin: 0 -15px !important;
        }
        .col-md-4, .col-md-6, .col-md-12 {
            padding: 0 15px !important;
            box-sizing: border-box !important;
        }
        .col-md-4 { flex: 0 0 33.3333%; max-width: 33.3333%; }
        .col-md-6 { flex: 0 0 50%; max-width: 50%; }
        .col-md-12 { flex: 0 0 100%; max-width: 100%; }
        .modal, .modal-backdrop, .print-button-container, button, header, nav, footer, .no-print {
            display: none !important;
            visibility: hidden !important;
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
    $deliveryOrder = $loadingSlip?->deliveryOrder
        ?? $item?->deliveryOrders?->where('type', 'export_order')->first()
        ?? $item?->exportLoadingProgram?->deliveryOrder
        ?? $item?->exportLoadingProgram?->deliveryOrders?->first();
    $secondWeighbridge = $loadingSlip?->secondWeighbridge;
    $netWeight = $secondWeighbridge?->net_weight ?? 0;
    $companyLocationId = $item?->exportLoadingProgram?->company_locations[0] ?? null;
    $noOfBags = $loadingSlip?->no_of_bags ?? $deliveryOrder?->exportPackingItems?->first()?->no_of_bags ?? 'N/A';
    $dcQtyKg = ((float) ($item?->delivery_challan_data?->qty ?? 0)) * 1000;
@endphp

<div class="gate-out-pass" id="gate-out-pass">
    <div class="gate-out-pass-inner">
        <div class="gate-out-header">
            <h3><i class="ft-file-text mr-2"></i>EXPORT GATE OUT</h3>
            <div class="ticket-number">
                Ticket: <strong>{{ $item->transaction_number ?? 'N/A' }}</strong>
                &nbsp;|&nbsp;
                Truck: <strong>{{ $item->truck_number ?? 'N/A' }}</strong>
            </div>
            <div class="mt-2">
                <span class="status-badge accepted">
                    <i class="ft-check-circle mr-1"></i> Accepted
                </span>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="gate-out-field">
                    <label><i class="ft-user mr-1"></i>Customer Name</label>
                    <div class="value">
                        {{ $DispatchQc->customer ?? ($deliveryOrder->customer->name ?? 'N/A') }}
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="gate-out-field">
                    <label><i class="ft-package mr-1"></i>Commodity</label>
                    <div class="value">
                        {{ $DispatchQc->commodity ?? ($deliveryOrder->exportOrder->product->name ?? 'N/A') }}
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="gate-out-field">
                    <label><i class="ft-package mr-1"></i>Brand</label>
                    <div class="value">
                        {{ getBrandById($item->brand_id)?->name ?? 'N/A' }}
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="gate-out-field">
                    <label><i class="ft-package mr-1"></i>Packing</label>
                    <div class="value">
                        {{ $item->packing ?? 'N/A' }}
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="gate-out-field">
                    <label><i class="ft-package mr-1"></i>No of Bags</label>
                    <div class="value">
                        {{ $noOfBags }}
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="gate-out-field">
                    <label><i class="ft-home mr-1"></i>Company Location</label>
                    <div class="value">
                        {{ get_location_name_by_id($companyLocationId) ?? 'N/A' }}
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="gate-out-field">
                    <label><i class="ft-home mr-1"></i>Factory</label>
                    <div class="value">
                        {{ $DispatchQc->factory ?? ($item->arrivalLocation->name ?? 'N/A') }}
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="gate-out-field">
                    <label><i class="ft-map-pin mr-1"></i>Gala</label>
                    <div class="value">
                        {{ $DispatchQc->gala ?? ($item->subArrivalLocation->name ?? 'N/A') }}
                    </div>
                </div>
            </div>

            <div class="col-md-12">
                <div class="gate-out-field">
                    <label><i class="ft-package mr-1"></i>Delivery Challan Qty</label>
                    <div class="value highlight">
                        <span style="font-size: 18px;">{{ number_format($dcQtyKg, 2) }} Kg</span>
                    </div>
                </div>
            </div>

            <div class="col-md-12">
                <div class="gate-out-field">
                    <label><i class="ft-activity mr-1"></i>Net Weight</label>
                    <div class="value highlight">
                        <span style="font-size: 18px;">{{ number_format($netWeight, 2) }} Kg</span>
                    </div>
                </div>
            </div>

            @if($DispatchQc->qc_remarks)
                <div class="col-md-12">
                    <div class="gate-out-field">
                        <label><i class="ft-message-square mr-1"></i>QC Remarks</label>
                        <div class="value">
                            {{ $DispatchQc->qc_remarks }}
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <div class="gate-out-footer">
            <div class="timestamp">
                <i class="ft-clock mr-1"></i>
                Generated on: {{ now()->format('d M Y, h:i A') }}
            </div>
            <div class="timestamp mt-1">
                Dispatch QC Created: {{ $DispatchQc->created_at->format('d M Y, h:i A') }}
                @if($DispatchQc->createdBy)
                    by {{ $DispatchQc->createdBy->name ?? 'N/A' }}
                @endif
            </div>
        </div>
    </div>
</div>

<div class="print-button-container no-print">
    <button class="btn btn-primary" onclick="window.print()">
        <i class="ft-printer mr-2"></i> Print Gate Out Pass
    </button>
</div>
