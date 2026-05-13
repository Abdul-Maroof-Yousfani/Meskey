<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Loading Account (Local) - #{{ $loadingSlip->loadingProgramItem->transaction_number ?? '' }}</title>
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
            font-size: 11px;
        }
        .container {
            width: 100%;
            max-width: 800px;
            margin: 0 auto;
            padding: 10px;
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
            margin-bottom: 3px;
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
        .slip-title-container {
            text-align: right;
            margin-top: -15px;
        }
        .slip-title {
            display: inline-block;
            border: 1px solid #000;
            padding: 3px 15px;
            font-weight: bold;
            font-size: 11px;
            background-color: #fff;
        }
        
        .main-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-top: 10px;
        }
        
        .field-group {
            display: flex;
            align-items: center;
            margin-bottom: 4px;
        }
        .field-label {
            min-width: 90px;
            font-weight: bold;
        }
        .field-value {
            flex-grow: 1;
            border: 1px solid #ccc;
            padding: 3px 8px;
            min-height: 14px;
            background-color: #fff;
        }

        .stacks-section {
            margin-top: 20px;
        }
        .section-title {
            font-weight: bold;
            margin-bottom: 5px;
        }
        .stacks-box {
            width: 100%;
            border: 1px solid #ccc;
            padding: 5px;
            min-height: 20px;
            margin-bottom: 10px;
        }

        .totals-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-top: 10px;
        }
        
        .footer {
            margin-top: 60px;
            display: flex;
            justify-content: space-between;
            border-top: none;
        }
        .signature-item {
            text-align: center;
            width: 22%;
        }
        .signature-line {
            border-top: 1px solid #000;
            margin-bottom: 3px;
        }
        .signature-label {
            font-weight: bold;
            font-size: 10px;
        }

        @media print {
            .no-print, .no-print-btn { display: none !important; }
            body { background: white; overflow: hidden !important; }
            .container { box-shadow: none; border: none; padding: 0; width: 100%; max-width: 100%; }
        }

        .no-print-btn {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 10px 20px;
            background: #007bff;
            color: #fff;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            z-index: 1000;
        }
    </style>
</head>
<body>
    <button class="no-print-btn no-print" onclick="window.print()">Print Slip</button>

    <div class="container">
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

        <div class="slip-title-container">
            <div class="slip-title">Loading Account (Local)</div>
        </div>

        <div class="main-grid">
            <div class="left-col">
                <div class="field-group">
                    <div class="field-label">Ticket #</div>
                    <div class="field-value">{{ $loadingSlip->loadingProgramItem->transaction_number ?? '' }}</div>
                </div>
                <div class="field-group">
                    <div class="field-label">Commodity</div>
                    <div class="field-value">{{ $loadingSlip->commodity ?? '' }}</div>
                </div>
                <div class="field-group">
                    <div class="field-label">Brand</div>
                    <div class="field-value">{{ $loadingSlip->brand ?? $loadingSlip->loadingProgramItem->brand->name ?? '' }}</div>
                </div>
                <div class="field-group">
                    <div class="field-label">Second Weight</div>
                    <div class="field-value">{{ number_format($loadingSlip->secondWeighbridge->second_weight ?? 0, 0) }}</div>
                </div>
                <div class="field-group">
                    <div class="field-label">Location</div>
                    <div class="field-value">{{ $loadingSlip->loadingProgramItem->arrivalLocation->name ?? '' }}</div>
                </div>
                <div class="field-group">
                    <div class="field-label">Truck No</div>
                    <div class="field-value">{{ $loadingSlip->loadingProgramItem->truck_number ?? '' }}</div>
                </div>
                <div class="field-group">
                    <div class="field-label">Seal No</div>
                    <div class="field-value">{{ $loadingSlip->seal_no ?? '' }}</div>
                </div>
                <div class="field-group">
                    <div class="field-label">Empty Bags</div>
                    <div class="field-value">0</div>
                </div>
                <div class="field-group">
                    <div class="field-label">Broker Name</div>
                    <div class="field-value"></div>
                </div>
                <div class="field-group">
                    <div class="field-label">Bag Type</div>
                    <div class="field-value">P.P</div>
                </div>
            </div>
            <div class="right-col">
                <div class="field-group">
                    <div class="field-label">Date</div>
                    <div class="field-value">{{ $loadingSlip->created_at->format('d-M-Y h:i A') }}</div>
                </div>
                <div class="field-group">
                    <div class="field-label">Driver Name</div>
                    <div class="field-value">{{ $loadingSlip->loadingProgramItem->driver_name ?? '' }}</div>
                </div>
                <div class="field-group">
                    <div class="field-label">Driver Contact</div>
                    <div class="field-value">{{ $loadingSlip->loadingProgramItem->contact_details ?? '' }}</div>
                </div>
                <div class="field-group">
                    <div class="field-label">First Weight</div>
                    <div class="field-value">{{ number_format($loadingSlip->secondWeighbridge->first_weight ?? 0, 0) }}</div>
                </div>
                <div class="field-group">
                    <div class="field-label">Gaala</div>
                    <div class="field-value">{{ $loadingSlip->loadingProgramItem->subArrivalLocation->name ?? '' }}</div>
                </div>
                <div class="field-group">
                    <div class="field-label">Tare Weight</div>
                    <div class="field-value">0</div>
                </div>
                <div class="field-group">
                    <div class="field-label">Net Weight</div>
                    <div class="field-value">{{ number_format($loadingSlip->secondWeighbridge->net_weight ?? 0, 0) }}</div>
                </div>
                <div class="field-group">
                    <div class="field-label">Party Name</div>
                    <div class="field-value">{{ $loadingSlip->customer ?? '' }}</div>
                </div>
                <div class="field-group">
                    <div class="field-label">DO Qty</div>
                    <div class="field-value">{{ number_format($loadingSlip->do_qty ?? 0, 0) }}</div>
                </div>
                <div class="field-group">
                    <div class="field-label">Station</div>
                    <div class="field-value"></div>
                </div>
            </div>
        </div>

        <div class="stacks-section">
            <div class="section-title">Stacks</div>
            <div class="field-value" style="min-height: 20px;">{{ $loadingSlip->no_of_bags ?? '' }}</div>
        </div>

        <div class="totals-grid">
            <div class="left-total">
                <div class="field-group">
                    <div class="field-label">Total Stacks</div>
                    <div class="field-value">1</div>
                </div>
                <div class="field-group">
                    <div class="field-label">Total Bags</div>
                    <div class="field-value">{{ $loadingSlip->no_of_bags ?? '' }}</div>
                </div>
            </div>
            <div class="right-total">
                <!-- Empty for spacing or additional fields -->
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
        window.onafterprint = function() {
            window.close();
        };
    </script>
</body>
</html>
