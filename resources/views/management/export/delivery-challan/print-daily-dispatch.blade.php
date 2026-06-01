<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daily Export Dispatch</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #ffffff;
            color: #000000;
            margin: 0;
            padding: 20px;
            font-size: 11px;
        }
        .container {
            max-width: 100%;
            margin: 0 auto;
            border: 2px solid #000000;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .fw-bold { font-weight: bold; }
        .border-bottom { border-bottom: 1px solid #000000; }
        .border-right { border-right: 1px solid #000000; }
        
        .header-title {
            font-size: 24px;
            padding: 10px 0;
            text-transform: uppercase;
            font-weight: bold;
        }
        
        .sub-header {
            display: flex;
            justify-content: space-between;
            padding: 5px 10px;
            font-size: 10px;
            border-bottom: 1px solid #000000;
            border-top: 1px solid #000000;
        }
        
        .main-title {
            text-align: center;
            padding: 8px 0;
            font-size: 14px;
            font-weight: bold;
        }

        .info-grid {
            display: flex;
            border-bottom: 1px solid #000000;
        }

        .info-grid .left-col {
            width: 75%;
            display: flex;
            flex-direction: column;
        }

        .info-grid .right-col {
            width: 25%;
            display: flex;
            flex-direction: column;
            border-left: 1px solid #000000;
        }

        .row-flex {
            display: flex;
            width: 100%;
            border-bottom: 1px solid #000000;
        }
        .row-flex:last-child {
            border-bottom: none;
        }

        .cell-label {
            width: 30%;
            padding: 5px;
            text-align: center;
            font-weight: bold;
            display: flex;
            align-items: center;
            justify-content: center;
            border-right: 1px solid #000000;
        }
        
        .cell-value {
            width: 70%;
            padding: 5px;
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .top-row-flex {
            display: flex;
            border-bottom: 1px solid #000000;
            border-top: 1px solid #000000;
        }
        
        .top-cell-label {
            width: 22.5%;
            padding: 8px;
            text-align: center;
            font-size: 14px;
            font-weight: bold;
        }
        
        .top-cell-value {
            width: 52.5%;
            padding: 8px;
            text-align: center;
            font-size: 14px;
            font-weight: bold;
            border-left: 1px solid #000000;
            border-right: 1px solid #000000;
        }
        
        .top-cell-label-right {
            width: 15%;
            padding: 8px;
            text-align: center;
            font-size: 12px;
            font-weight: bold;
            border-right: 1px solid #000000;
        }
        
        .top-cell-value-right {
            width: 10%;
            padding: 8px;
            text-align: center;
            font-size: 12px;
            font-weight: bold;
        }

        /* Table Styles */
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 6px 4px;
            text-align: center;
            border: 1px solid #000000;
        }

        th {
            font-size: 10px;
            font-weight: bold;
        }
        
        td {
            font-size: 11px;
        }
        
        .footer-row td {
            font-weight: bold;
            padding: 10px 4px;
        }
        
        .avg-row td {
            text-align: right;
            font-style: italic;
            padding-top: 5px;
        }

        @media print {
            body {
                background-color: #ffffff !important;
                color: #000000 !important;
                -webkit-print-color-adjust: exact;
                color-adjust: exact;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="header-title text-center">
        MESKAY & FEMTEE TRADING COMPANY (PVT) LTD
    </div>
    
    <div class="sub-header">
        <div>ISO Doc Control Code</div>
        <div>{{ $iso_code ?? 'MFT/QR/037' }}</div>
        <div>Issue Date</div>
        <div>{{ $issue_date ?? '18-07-14' }}</div>
        <div>Issue# {{ $issue_no ?? '1' }}</div>
    </div>
    
    <div class="main-title border-bottom">
        DAILY EXPORT DISPATCH
    </div>

    <div class="top-row-flex border-bottom">
        <div class="top-cell-label">Form-E Reference</div>
        <div class="top-cell-value">
            @php
                $formENo = $delivery_order->exportFormE->form_e_no ?? 'N/A';
            @endphp
            {{ $formENo }}
        </div>
        <div class="top-cell-label-right">Sheet #</div>
        <div class="top-cell-value-right">1</div>
    </div>

    <div class="info-grid">
        <div class="left-col">
            <div class="row-flex">
                <div class="cell-label">Brand Name</div>
                <div class="cell-value border-right">{{ $delivery_order->exportPackingItems->first()?->brand?->name ?? 'N/A' }}</div>
                <div class="cell-label">Location</div>
                <div class="cell-value">{{ $delivery_order->do_factories_string ?: 'N/A' }}</div>
            </div>
            <div class="row-flex">
                <div class="cell-label">Commodity</div>
                <div class="cell-value border-right">{{ $delivery_order->exportOrder->product->name ?? 'N/A' }}</div>
                <div class="cell-label">Date</div>
                <div class="cell-value">{{ date('d-M-Y') }}</div>
            </div>
            <div class="row-flex">
                <div class="cell-label">JO#</div>
                <div class="cell-value border-right">{{ collect($delivery_order->exportOrder->jobOrders)->pluck('job_order_no')->filter()->implode(', ') ?: 'N/A' }}</div>
                <div class="cell-label">DO #</div>
                <div class="cell-value">{{ $delivery_order->reference_no ?? $delivery_order->do_no ?? $delivery_order->id }}</div>
            </div>
            <div class="row-flex">
                <div class="cell-label">Clearing Agent</div>
                <div class="cell-value border-right">{{ $delivery_order->clearing_agent ?? 'SELF - MESKAY' }}</div>
                <div class="cell-label">Station</div>
                <div class="cell-value">{{ $delivery_order->do_station_string ?: 'N/A' }}</div>
            </div>
        </div>
        <div class="right-col">
            <!-- Empty lines matching the image right side block which seems to just have some spacing or additional fields if any -->
            <div class="row-flex" style="flex:1;">
                <div class="cell-value" style="width:100%;"></div>
            </div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th width="3%">Sq#</th>
                <th width="10%">Container #</th>
                <th width="8%">Truck #</th>
                <th width="5%">Packing</th>
                <th width="6%">Bags/Ctn</th>
                <th width="7%">Weight</th>
                <th width="8%">Seal#</th>
                <th width="5%">Galla#</th>
                <th width="10%">Dosage</th>
                <th width="5%">Dry Bags</th>
                <th width="8%">Craft Paper</th>
                <th width="15%">Empty/Naubahar</th>
                <th width="10%">GP#</th>
            </tr>
        </thead>
        <tbody>
            @foreach($report_data as $row)
            <tr>
                <td>{{ $row['seq'] }}</td>
                <td>{{ $row['container'] }}</td>
                <td>{{ $row['truck'] }}</td>
                <td>{{ $row['packing'] }}</td>
                <td>{{ number_format($row['bags']) }}</td>
                <td>{{ number_format($row['weight']) }}</td>
                <td>{{ $row['seal'] }}</td>
                <td>{{ $row['galla'] }}</td>
                <td>{{ $row['dosage'] }}</td>
                <td>{{ $row['dry_bags'] }}</td>
                <td>{{ $row['craft_paper'] }}</td>
                <td>{{ $row['empty_bags'] }}</td>
                <td>{{ $row['gp'] }}</td>
            </tr>
            @endforeach
            
            <!-- Fill empty rows if needed to make it look full like image -->
            @php $empty_rows = 20 - count($report_data); @endphp
            @if($empty_rows > 0)
                @for($i = 0; $i < $empty_rows; $i++)
                <tr>
                    <td>{{ count($report_data) + $i + 1 }}</td>
                    <td></td><td></td><td></td><td></td><td></td><td></td>
                    <td></td><td></td><td></td><td></td><td></td><td></td>
                </tr>
                @endfor
            @endif

            <tr class="footer-row">
                <td colspan="4" class="text-right">-</td>
                <td>{{ number_format($total_bags) }}</td>
                <td>{{ number_format($total_weight) }}</td>
                <td colspan="7" class="text-right">-</td>
            </tr>
        </tbody>
    </table>
    
    <div style="text-align: right; font-style: italic; font-weight: bold; padding-top: 5px; padding-right: 10px;">
        Avg Weight per bag &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; 
        @php
            $avg = $total_bags > 0 ? ($total_weight / $total_bags) : 0;
        @endphp
        {{ number_format($avg, 2) }}
    </div>

</div>

<script>
    window.onload = function() {
        window.print();
    }
</script>

</body>
</html>
