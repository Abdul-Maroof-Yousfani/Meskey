<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Container List - {{ $packingList->commercialInvoice->invoice_no }}</title>
    <style>
        @page {
            size: A4;
            margin: 15mm;
        }
        body {
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 11px;
            color: #111;
            line-height: 1.5;
        }
        .print-bar {
            padding: 10px;
            text-align: center;
            background: #f4f4f4;
            border-bottom: 1px solid #ddd;
        }
        .print-btn {
            border: 0;
            background: #2d4580;
            color: #fff;
            padding: 10px 30px;
            font-weight: bold;
            cursor: pointer;
            border-radius: 4px;
        }
        .document {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .header-box {
            border: 2px solid #333;
            padding: 15px;
            text-align: center;
            margin-bottom: 30px;
        }
        .header-box h1 {
            margin: 0 0 10px;
            font-size: 20px;
            text-decoration: underline;
            text-transform: uppercase;
        }
        .header-box p {
            margin: 2px 0;
            font-size: 13px;
            font-weight: bold;
        }
        
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        .items-table th, .items-table td {
            border: 1px solid #000;
            padding: 8px;
            text-align: center;
            height: 25px;
        }
        .items-table th {
            background: #f2f2f2;
            text-transform: uppercase;
            font-size: 10px;
        }
        .items-table .total-row td {
            font-weight: bold;
            background: #f9f9f9;
        }
        
        @media print {
            .print-bar {
                display: none;
            }
            .document {
                padding: 0;
            }
            .header-box {
                margin-top: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="print-bar">
        <button class="print-btn" onclick="window.print()">PRINT CONTAINER LIST</button>
    </div>

    <div class="document">
        @php
            $invoice = $packingList->commercialInvoice;
            $order = $invoice->exportOrder;
            $pod = $order->portOfDischarge;
            $bol = $packingList->billOfLading;
            
            $destination = strtoupper(($pod->name ?? 'N/A') . ($pod && $pod->country ? ', ' . $pod->country->name : ''));
            $blNo = $bol->bill_no ?: 'N/A';
            $contractNo = $order->contract_no ?: ($order->voucher_no ?: 'N/A');
        @endphp

        @php
            $company = $order->company;
        @endphp
        
        <table style="width: 100%; border-collapse: collapse; margin-bottom: 10px;">
            <tr>
                <td style="width: 44%; border: none; text-align: left; vertical-align: top; padding: 0;">
                    @if ($company && $company->logo)
                        <img src="{{ image_path($company->logo) }}" style="max-width: 200px; max-height: 80px;" alt="Logo">
                    @else
                        <strong style="font-size: 18px;">{{ $company->name ?? 'MESKEY & FEMTEE' }}</strong>
                    @endif
                </td>
                <td style="width: 56%; border: none; text-align: right; vertical-align: top; padding: 0; font-size: 9px; line-height: 1.4; color: #333;">
                    <strong style="font-size: 16px; display: block; margin-bottom: 2px; color: #000;">{{ $company->name ?? 'MESKEY & FEMTEE (PVT) LTD' }}</strong>
                    <div>{{ $company->address ?? '' }}</div>
                    <div>{{ $company->phone ?? '' }}{{ !empty($company->phone) && !empty($company->email) ? ' | ' : '' }}{{ $company->email ?? '' }}</div>
                </td>
            </tr>
        </table>

        <div class="header-box">
            <h1>CONTAINER LIST</h1>
            <p>{{ $destination }}</p>
            <p>B/L NO. {{ $blNo }}</p>
            <p>CONTRACT NO. {{ $contractNo }}</p>
        </div>

        <table class="items-table">
            <thead>
                <tr>
                    <th width="10%">S. NO</th>
                    <th width="30%">CONTAINER NO</th>
                    <th width="20%">NO. OF BAGS</th>
                    <th width="20%">NET WEIGHT (KG)</th>
                    <th width="20%">GROSS WEIGHT (KG)</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($containerItems as $index => $item)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $item->container_number }}</td>
                        <td>{{ number_format($item->bags) }}</td>
                        <td>{{ number_format($item->net_weight, 2) }}</td>
                        <td>{{ number_format($item->gross_weight, 2) }}</td>
                    </tr>
                @endforeach
                
                {{-- Minimum rows to maintain professional look --}}
                @php $rowCount = count($containerItems); @endphp
                @for ($i = $rowCount; $i < 10; $i++)
                    <tr>
                        <td>&nbsp;</td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                    </tr>
                @endfor
            </tbody>
            <tfoot>
                <tr class="total-row">
                    <td colspan="2">TOTAL</td>
                    <td>{{ number_format($totals['bags']) }}</td>
                    <td>{{ number_format($totals['net_weight'], 2) }}</td>
                    <td>{{ number_format($totals['gross_weight'], 2) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>

    <script>
        window.onload = function() {
            setTimeout(function() { 
                // window.print(); 
            }, 500);
        };
    </script>
</body>
</html>
