<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gate Out Pass - #{{ $rejectionReturn->return_no }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            color: #333;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            text-transform: uppercase;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }
        .info-item {
            font-size: 14px;
            margin-bottom: 5px;
        }
        .info-item strong {
            width: 140px;
            display: inline-block;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 40px;
        }
        table th, table td {
            border: 1px solid #000;
            padding: 10px;
            text-align: left;
            font-size: 13px;
        }
        table th {
            background-color: #f2f2f2;
        }
        .footer {
            margin-top: 60px;
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 40px;
            text-align: center;
        }
        .signature-line {
            border-top: 1px solid #000;
            padding-top: 5px;
            font-size: 12px;
            font-weight: bold;
        }
        @media print {
            .no-print {
                display: none;
            }
            body {
                padding: 0;
            }
        }
        .btn-print {
            background: #007bff;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button onclick="window.print()" class="btn-print">Print Gate Pass</button>
    </div>

    <div class="header">
        <h1>Gate Out Pass (Rejection Return)</h1>
        <p>Document No: <strong>#{{ $rejectionReturn->return_no }}</strong></p>
    </div>

    <div class="info-grid">
        <div>
            <div class="info-item"><strong>GRN Reference:</strong> {{ $rejectionReturn->grn->purchase_order_receiving_no ?? 'N/A' }}</div>
            <div class="info-item"><strong>Supplier:</strong> {{ $rejectionReturn->supplier->name ?? 'N/A' }}</div>
            <div class="info-item"><strong>Date:</strong> {{ $rejectionReturn->date }}</div>
        </div>
        <div>
            <div class="info-item"><strong>Truck No:</strong> {{ $rejectionReturn->truck_no ?? 'N/A' }}</div>
            <div class="info-item"><strong>Reference No:</strong> {{ $rejectionReturn->reference_no ?? 'N/A' }}</div>
            <div class="info-item"><strong>Issued By:</strong> {{ $rejectionReturn->creator->name ?? 'System' }}</div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 50px;">S#</th>
                <th>Item Description</th>
                <th style="text-align: center;">Returned Quantity</th>
                <th style="text-align: center;">Weight (grams)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($rejectionReturn->items as $index => $item)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $item->item->name ?? 'N/A' }}</td>
                <td style="text-align: center;">{{ $item->quantity }} {{ $item->item->unitOfMeasure->name ?? '' }}</td>
                <td style="text-align: center;">{{ $item->weight ?? '-' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div style="margin-bottom: 20px;">
        <strong>Remarks:</strong><br>
        {{ $rejectionReturn->remarks ?? 'No remarks provided.' }}
    </div>

    <div class="footer">
        <div>
            <div class="signature-line">Authorized Signature</div>
        </div>
        <div>
            <div class="signature-line">Store Representative</div>
        </div>
        <div>
            <div class="signature-line">Driver's Signature</div>
        </div>
    </div>

    <script>
        window.print();
        window.onafterprint = function() {
            window.close();
        };
    </script>
</body>
</html>
