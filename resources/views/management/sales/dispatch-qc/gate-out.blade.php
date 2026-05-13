<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LOCAL GATE PASS - #{{ $DispatchQc->loadingProgramItem->transaction_number ?? '' }}</title>
    <style>
        @page {
            size: A4;
            margin: 10mm;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            color: #000;
            font-size: 12px;
            line-height: 1.4;
            -webkit-print-color-adjust: exact;
        }
        .container {
            width: 100%;
            max-width: 800px;
            margin: 0 auto;
            padding: 10px;
            background: white;
        }
        .header {
            display: flex;
            align-items: flex-start;
            margin-bottom: 5px;
        }
        .logo-container {
            width: 80px;
            text-align: center;
        }
        .logo-container img {
            width: 100%;
            height: auto;
        }
        .logo-text {
            font-size: 8px;
            margin-top: 2px;
            font-weight: bold;
        }
        .company-info {
            flex-grow: 1;
            padding-left: 15px;
        }
        .info-row {
            display: flex;
            margin-bottom: 2px;
        }
        .info-label {
            font-weight: bold;
            text-decoration: underline;
            min-width: 80px;
            font-size: 9px;
        }
        .info-content {
            font-size: 9px;
            padding-left: 5px;
        }
        .contact-details {
            font-size: 8.5px;
            margin-top: 5px;
            border-top: 1px solid #000;
            padding-top: 2px;
        }
        .iso-code {
            text-align: right;
            font-weight: bold;
            font-size: 11px;
            margin-top: -10px;
        }
        
        .pass-title-section {
            text-align: center;
            margin: 20px 0;
        }
        .pass-title {
            font-size: 18px;
            font-weight: bold;
            text-decoration: underline;
            text-transform: uppercase;
        }
        .pass-subtitle {
            font-size: 14px;
            font-weight: bold;
            margin-top: 5px;
        }

        .main-content {
            display: grid;
            grid-template-columns: 1.2fr 1fr;
            gap: 40px;
            margin-top: 30px;
        }
        
        .field-row {
            display: flex;
            margin-bottom: 12px;
            align-items: baseline;
        }
        .field-label {
            font-weight: bold;
            min-width: 120px;
        }
        .field-value {
            border-bottom: none;
            flex-grow: 1;
        }

        .footer {
            margin-top: 100px;
            display: flex;
            justify-content: space-between;
        }
        .signature-item {
            text-align: center;
            width: 24%;
        }
        .signature-line {
            border-top: 1px solid #000;
            margin-bottom: 5px;
        }
        .signature-label {
            font-weight: bold;
        }

        @media print {
            .no-print, .modal-header, .close, .modal-sidebar-close, button { display: none !important; }
            body, html { background: white; overflow: hidden !important; height: auto !important; }
            * { overflow: visible !important; }
            .modal, .modal-dialog, .modal-content, .modal-body { 
                overflow: visible !important; 
                height: auto !important; 
                border: none !important; 
                box-shadow: none !important;
                padding: 0 !important;
                margin: 0 !important;
            }
            .container { padding: 0; margin: 0; border: none; box-shadow: none; width: 100% !important; max-width: 100% !important; }
        }

        .print-button-container {
            text-align: center;
            margin: 20px 0;
        }
        .btn-print {
            padding: 10px 25px;
            background: #28a745;
            color: #fff;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="print-button-container no-print">
        <button class="btn-print" onclick="window.print()">Print Gate Out Pass</button>
    </div>

    <div class="container" id="gate-out-pass">
        <div class="header">
            <div class="logo-container">
                <img src="{{ asset('management/app-assets/img/meskay-logo.png') }}" alt="Logo">
                <div class="logo-text">Original / Duplicate</div>
            </div>
            <div class="company-info">
                <div class="info-row">
                    <span class="info-label">Head office:</span>
                    <span class="info-content">Saima Trade Tower, Tower B, Room # 1511-13, I. I. Chundrigar Road, Karachi.</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Factory:</span>
                    <span class="info-content">Plot No A-43, A-45 & A-46, Eastern, Industrial Zone, Port Qasim, Karachi, Pakistan.</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Retail Outlet:</span>
                    <span class="info-content">Shop No. 4, K.A.I Center, Opp City Court, Dandia Bazar, Karachi Ph: +92 23 32713369, 3378</span>
                </div>
                <div class="contact-details">
                    Tel: +92 21 32214981 - 82, 32275349-51 Fax: +92 21 23375352<br>
                    Email: info@mft.com.pk, Web: www.mft.com.pk
                </div>
            </div>
        </div>

        <div class="iso-code">ISO Code: MFT/QR/033</div>

        <div class="pass-title-section">
            <div class="pass-title">LOCAL GATE PASS</div>
            <div class="pass-subtitle">ORIGINAL</div>
        </div>

        <div class="main-content">
            <div class="left-col">
                <div class="field-row">
                    <div class="field-label">Date:</div>
                    <div class="field-value">{{ $DispatchQc->created_at->format('d/m/Y') }}</div>
                </div>
                <div class="field-row">
                    <div class="field-label">SO #:</div>
                    <div class="field-value">
                        @if($DispatchQc->loadingProgramItem->saleOrders->isNotEmpty())
                            {{ $DispatchQc->loadingProgramItem->saleOrders->pluck('reference_no')->implode(', ') }}
                        @else
                            {{ $DispatchQc->loadingProgramItem->loadingProgram->saleOrder->reference_no ?? 'N/A' }}
                        @endif
                    </div>
                </div>
                <div class="field-row">
                    <div class="field-label">Commodity #:</div>
                    <div class="field-value">{{ $DispatchQc->commodity ?? ($DispatchQc->loadingProgramItem->loadingProgram->deliveryOrder->delivery_order_data->first()->item->name ?? 'N/A') }}</div>
                </div>
                <div class="field-row">
                    <div class="field-label">Trucks No.:</div>
                    <div class="field-value">{{ $DispatchQc->loadingProgramItem->truck_number ?? 'N/A' }}</div>
                </div>
                <div class="field-row">
                    <div class="field-label">No. of Bags:</div>
                    <div class="field-value">{{ $DispatchQc->loadingProgramItem->loadingSlip->no_of_bags ?? 'N/A' }}</div>
                </div>
                <div class="field-row">
                    <div class="field-label">Loader Name:</div>
                    <div class="field-value">{{ $DispatchQc->loadingProgramItem->loadingSlip->labour ?? 'N/A' }}</div>
                </div>
                <div class="field-row">
                    <div class="field-label">Net Weight:</div>
                    <div class="field-value">
                        @php
                            $secondWeighbridge = $DispatchQc->loadingProgramItem->loadingSlip->secondWeighbridge ?? null;
                            $netWeight = $secondWeighbridge ? $secondWeighbridge->net_weight : 0;
                        @endphp
                        {{ number_format($netWeight, 0) }}
                    </div>
                </div>
                <div class="field-row">
                    <div class="field-label">Location:</div>
                    <div class="field-value">{{ $DispatchQc->factory ?? ($DispatchQc->loadingProgramItem->arrivalLocation->name ?? 'N/A') }}</div>
                </div>
            </div>
            <div class="right-col">
                <div class="field-row">
                    <div class="field-label">G.P. NO. :</div>
                    <div class="field-value">{{ $DispatchQc->id + 3000 }}</div>
                </div>
                <div class="field-row">
                    <div class="field-label">Party Name:</div>
                    <div class="field-value">{{ $DispatchQc->customer ?? ($DispatchQc->loadingProgramItem->loadingProgram->deliveryOrder->customer->name ?? 'N/A') }}</div>
                </div>
                <div class="field-row">
                    <div class="field-label">Delivery Order No.:</div>
                    <div class="field-value">
                        @if($DispatchQc->loadingProgramItem->deliveryOrders->isNotEmpty())
                            {{ $DispatchQc->loadingProgramItem->deliveryOrders->pluck('reference_no')->implode(', ') }}
                        @else
                            {{ $DispatchQc->loadingProgramItem->loadingProgram->deliveryOrder->reference_no ?? 'N/A' }}
                        @endif
                    </div>
                </div>
                <div class="field-row">
                    <div class="field-label">Container No.:</div>
                    <div class="field-value">{{ $DispatchQc->loadingProgramItem->container_number ?? '0' }}</div>
                </div>
                <div class="field-row">
                    <div class="field-label">Bag Packing:</div>
                    <div class="field-value">{{ $DispatchQc->loadingProgramItem->packing ?? 'N/A' }}</div>
                </div>
                <div class="field-row">
                    <div class="field-label">Prepared By:</div>
                    <div class="field-value">Factory</div>
                </div>
                <div class="field-row">
                    <div class="field-label">Gala:</div>
                    <div class="field-value">{{ $DispatchQc->gala ?? ($DispatchQc->loadingProgramItem->subArrivalLocation->name ?? 'N/A') }}</div>
                </div>
            </div>
        </div>

        <div class="footer">
            <div class="signature-item">
                <div class="signature-line"></div>
                <div class="signature-label">Confirmed From</div>
            </div>
            <div class="signature-item">
                <div class="signature-line"></div>
                <div class="signature-label">Contact Number</div>
            </div>
            <div class="signature-item">
                <div style="font-weight: bold; margin-bottom: 25px;">Factory</div>
                <div class="signature-line"></div>
                <div class="signature-label">Prepared By</div>
            </div>
        </div>
    </div>

    <script>
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 500);
        };
    </script>
</body>
</html>
