<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Proforma Invoice - {{ $proforma->proforma_no }}</title>
    <style>
        @page {
            size: A4;
            margin: 10mm;
        }
        body {
            font-family: 'Arial', sans-serif;
            font-size: 10px;
            color: #000;
            line-height: 1.2;
            margin: 0;
            padding: 0;
        }
        .invoice-box {
            max-width: 800px;
            margin: auto;
            padding: 10px;
        }
        .header-table {
            width: 100%;
            margin-bottom: 10px;
            border-bottom: 2px solid #000;
            padding-bottom: 5px;
        }
        .logo {
            max-width: 180px;
        }
        .company-info h1 {
            margin: 0;
            font-size: 20px;
        }
        .company-info p {
            margin: 1px 0;
        }
        .title-section {
            text-align: center;
            margin-bottom: 10px;
        }
        .title-section h2 {
            margin: 0;
            text-decoration: underline;
            font-size: 16px;
            text-transform: uppercase;
        }
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        .info-table td {
            width: 50%;
            vertical-align: top;
            padding: 4px;
            border: 1px solid #000;
        }
        .info-table th {
            background: #eee;
            text-align: left;
            padding: 3px;
            border: 1px solid #000;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 9px;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        .items-table th, .items-table td {
            border: 1px solid #000;
            padding: 5px;
            text-align: center;
        }
        .items-table th {
            background-color: #eee;
            font-size: 9px;
        }
        .details-section {
            margin-bottom: 10px;
        }
        .details-section table {
            width: 100%;
            border-collapse: collapse;
        }
        .details-section td {
            border: 1px solid #000;
            padding: 4px;
        }
        .bank-details {
            border: 1px solid #000;
            padding: 5px;
            margin-bottom: 10px;
        }
        .bank-details h4 {
            margin: 0 0 5px 0;
            text-decoration: underline;
            font-size: 10px;
        }
        .footer {
            margin-top: 30px;
            width: 100%;
        }
        .footer td {
            width: 50%;
            text-align: center;
        }
        .signature-line {
            margin-top: 25px;
            border-top: 1px solid #000;
            display: inline-block;
            width: 180px;
        }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .font-bold { font-weight: bold; }
        @media print {
            .no-print { display: none; }
            body { margin: 0; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="text-align: center; padding: 10px; background: #eee; border-bottom: 1px solid #ccc;">
        <button onclick="window.print()" style="padding: 10px 30px; cursor: pointer; background: #007bff; color: white; border: none; font-weight: bold;">PRINT INVOICE</button>
    </div>

    <div class="invoice-box">
        <table class="header-table">
            <tr>
                <td width="50%">
                    @if (getCurrentCompany() && getCurrentCompany()->logo)
                        <img src="{{ image_path(getCurrentCompany()->logo) }}" class="logo" alt="Logo">
                    @else
                        <h1>{{ getCurrentCompany()->name ?? 'MESKEY & FEMTEE' }}</h1>
                    @endif
                </td>
                <td width="50%" class="text-right company-info">
                    <p><strong>{{ $exportOrder->company->name ?? 'MESKEY & FEMTEE (PVT) LTD' }}</strong></p>
                    <p>{{ $exportOrder->company->address ?? '' }}</p>
                    <p>Phone: {{ $exportOrder->company->phone ?? '' }} Email: {{ $exportOrder->company->email ?? '' }}</p>
                    <p>NTN: {{ $exportOrder->company->ntn ?? 'N/A' }} STRN: {{ $exportOrder->company->stn ?? 'N/A' }}</p>
                </td>
            </tr>
        </table>

        <div class="title-section">
            <h2>Proforma Invoice</h2>
        </div>

        <table class="info-table">
            <tr>
                <th width="50%">Exporter / Beneficiary</th>
                <th width="50%">Invoice Details</th>
            </tr>
            <tr>
                <td>
                    <strong>{{ $exportOrder->company->name ?? 'N/A' }}</strong><br>
                    {!! nl2br(e($exportOrder->company->address ?? '')) !!}
                </td>
                <td>
                    <table width="100%" style="border:none;">
                        <tr><td style="border:none; padding:1px;">Invoice No:</td><td style="border:none; padding:1px;"><strong>{{ $proforma->proforma_no }}</strong></td></tr>
                        <tr><td style="border:none; padding:1px;">Date:</td><td style="border:none; padding:1px;"><strong>{{ \Carbon\Carbon::parse($proforma->proforma_date)->format('d-M-Y') }}</strong></td></tr>
                        <tr><td style="border:none; padding:1px;">Contract No:</td><td style="border:none; padding:1px;">{{ $exportOrder->contract_no ?? 'N/A' }}</td></tr>
                        <tr><td style="border:none; padding:1px;">Voucher No:</td><td style="border:none; padding:1px;">{{ $exportOrder->voucher_no ?? 'N/A' }}</td></tr>
                    </table>
                </td>
            </tr>
            <tr>
                <th>Buyer / Importer</th>
                <th>Consignee</th>
            </tr>
            <tr>
                <td>
                    <strong>{{ $exportOrder->buyer->name ?? 'N/A' }}</strong><br>
                    {!! nl2br(e($exportOrder->buyer->address ?? 'N/A')) !!}
                </td>
                <td>
                    {!! nl2br(e($proforma->consigned_details ?? 'SAME AS BUYER')) !!}
                </td>
            </tr>
        </table>

        <table class="info-table" style="margin-top: -11px; border-top:none;">
            <tr>
                <td width="25%"><strong>Origin:</strong> {{ $exportOrder->originCountry?->name ?? 'PAKISTAN' }}</td>
                <td width="25%"><strong>Loading:</strong> {{ $exportOrder->portOfLoading?->name ?? 'N/A' }}</td>
                <td width="25%"><strong>Discharge:</strong> {{ $exportOrder->portOfDischarge?->name ?? 'N/A' }}</td>
                <td width="25%"><strong>Transport:</strong> {{ $exportOrder->modeOfTransport?->name ?? 'BY SEA' }}</td>
            </tr>
        </table>

        <table class="items-table">
            <thead>
                <tr>
                    <th width="35%">Description of Goods</th>
                    <th width="12%">HS Code</th>
                    <th width="15%">Quantity</th>
                    <th width="18%">Unit Price ({{ $exportOrder->currency->currency_code ?? '$' }})</th>
                    <th width="20%">Total Amount</th>
                </tr>
            </thead>
            <tbody>
                @php $totalAmount = 0; $totalMT = 0; @endphp
                @foreach($exportOrder->packingItems as $item)
                <tr>
                    <td class="text-left">
                        <strong>{{ $exportOrder->product->name ?? 'Commodity' }}</strong><br>
                        <span style="font-size: 9px;">
                            Packing: {{ $item->bagType->name ?? '' }} ({{ $item->bag_size }} kg)<br>
                            Brand: {{ $item->brand->name ?? 'N/A' }}
                        </span>
                    </td>
                    <td>{{ $exportOrder->hsCode?->code ?? 'N/A' }}</td>
                    <td>{{ number_format($item->metric_tons, 3) }} MT</td>
                    <td>{{ number_format($item->rate, 2) }}</td>
                    <td>{{ number_format($item->amount, 2) }}</td>
                </tr>
                @php 
                    $totalAmount += $item->amount; 
                    $totalMT += $item->metric_tons;
                @endphp
                @endforeach
                
                @if($exportOrder->specifications->count() > 0)
                <tr>
                    <td colspan="5" class="text-left" style="background: #fafafa; font-size: 9px;">
                        <strong>Quality Specifications:</strong> 
                        @foreach($exportOrder->specifications as $spec)
                            {{ $spec->spec_name }}: {{ $spec->spec_value }} {{ $spec->uom }} |
                        @endforeach
                    </td>
                </tr>
                @endif
            </tbody>
            <tfoot>
                <tr class="font-bold">
                    <td colspan="2" class="text-right">TOTAL</td>
                    <td>{{ number_format($totalMT, 3) }} MT</td>
                    <td></td>
                    <td>{{ $exportOrder->currency->currency_code ?? '' }} {{ number_format($totalAmount, 2) }}</td>
                </tr>
                <tr>
                    <td colspan="5" class="text-left">
                        <strong>Amount in Words:</strong> {{ $amountInWords }}
                    </td>
                </tr>
            </tfoot>
        </table>

        <table class="info-table">
            <tr>
                <th colspan="2">Terms and Conditions</th>
            </tr>
            <tr><td width="30%"><strong>Payment Terms:</strong></td><td>{{ $exportOrder->modeOfTerm->name ?? 'N/A' }} @if($exportOrder->payment_days) ({{ $exportOrder->payment_days }} Days) @endif</td></tr>
            <tr><td><strong>Incoterms:</strong></td><td>{{ $exportOrder->incoterm?->name ?? 'N/A' }}</td></tr>
            <tr><td><strong>Shipment Period:</strong></td><td>{{ \Carbon\Carbon::parse($exportOrder->shipment_delivery_date_from)->format('d-M-Y') }} To {{ \Carbon\Carbon::parse($exportOrder->shipment_delivery_date_to)->format('d-M-Y') }}</td></tr>
            <tr><td><strong>Shipping Marks:</strong></td><td>{!! nl2br(e($exportOrder->marking_labeling ?? 'N/A')) !!}</td></tr>
            <tr><td><strong>Packing Details:</strong></td><td>{!! nl2br(e($exportOrder->packing_description ?? 'N/A')) !!}</td></tr>
            @if($exportOrder->other_condition)<tr><td><strong>Other Conditions:</strong></td><td>{!! $exportOrder->other_condition !!}</td></tr>@endif
            @if($exportOrder->force_majure)<tr><td><strong>Force Majeure:</strong></td><td>{!! $exportOrder->force_majure !!}</td></tr>@endif
            @if($exportOrder->application_law)<tr><td><strong>Application Law:</strong></td><td>{!! $exportOrder->application_law !!}</td></tr>@endif
        </table>

        @php $bank = $proforma->customer_bank; @endphp
        @if($bank)
        <div class="bank-details">
            <h4 style="text-decoration: none;">Beneficiary Bank Details:</h4>
            <table width="100%" style="border:none;">
                <tr><td width="30%" style="border:none; padding:2px;"><strong>Beneficiary Bank:</strong></td><td style="border:none; padding:2px;">{{ $bank->bank_name }}</td></tr>
                <tr><td style="border:none; padding:2px;"><strong>Account Title:</strong></td><td style="border:none; padding:2px;">{{ $bank->account_title }}</td></tr>
                <tr><td style="border:none; padding:2px;"><strong>Bank Name:</strong></td><td style="border:none; padding:2px;">{{ $bank->bank_name }}</td></tr>
                <tr><td style="border:none; padding:2px;"><strong>Branch Name:</strong></td><td style="border:none; padding:2px;">{{ $bank->branch_name }}</td></tr>
                <tr><td style="border:none; padding:2px;"><strong>Account No:</strong></td><td style="border:none; padding:2px;">{{ $bank->account_number }}</td></tr>
                <tr><td style="border:none; padding:2px;"><strong>Branch Code:</strong></td><td style="border:none; padding:2px;">{{ $bank->branch_code }}</td></tr>
            </table>
        </div>
        @endif

        <div class="footer">
            <table width="100%">
                <tr>
                    <td><div class="signature-line"></div><p>Buyer's Signature</p></td>
                    <td><div class="signature-line"></div><p>Authorized Signature<br><strong>{{ $exportOrder->company->name ?? 'Meskey & Femtee' }}</strong></p></td>
                </tr>
            </table>
        </div>
    </div>
    <script>window.onload = function() { setTimeout(function() { window.print(); }, 500); }</script>
</body>
</html>
