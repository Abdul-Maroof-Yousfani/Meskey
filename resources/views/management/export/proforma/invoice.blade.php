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
            margin: 0;
            font-family: Arial, sans-serif;
            font-size: 10px;
            color: #111;
            line-height: 1.25;
        }
        .print-bar {
            padding: 10px;
            text-align: center;
            background: #eef2f7;
            border-bottom: 1px solid #cbd5e1;
        }
        .print-btn {
            border: 0;
            background: #1d4ed8;
            color: #fff;
            padding: 8px 24px;
            font-weight: 700;
            cursor: pointer;
        }
        .invoice-box {
            max-width: 790px;
            margin: 0 auto;
            padding: 10px;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
            border-bottom: 2px solid #111;
        }
        .header-table td {
            vertical-align: top;
            padding-bottom: 6px;
        }
        .logo {
            max-width: 180px;
            max-height: 72px;
        }
        .company-info {
            text-align: right;
            font-size: 9px;
        }
        .company-info strong {
            display: block;
            font-size: 15px;
            margin-bottom: 2px;
        }
        .party-table,
        .meta-table,
        .items-table,
        .bank-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }
        .meta-table td,
        .party-table td,
        .items-table td,
        .items-table th,
        .bank-table td {
            border: 1px solid #111;
            padding: 5px 6px;
            vertical-align: top;
        }
        .ref-row {
            margin: 2px 0 12px;
            font-size: 11px;
            font-weight: 700;
        }
        .ref-row::after {
            content: "";
            display: block;
            clear: both;
        }
        .ref-left {
            float: left;
            width: 68%;
        }
        .ref-right {
            float: right;
            width: 28%;
            text-align: right;
        }
        .items-table th {
            background: #e8edf5;
            text-align: left;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 9px;
        }
        .label {
            width: 22%;
            background: #f6f8fb;
            font-weight: 700;
        }
        .block-label {
            width: 18%;
            font-weight: 700;
            text-transform: uppercase;
            background: #eef3f8;
        }
        .party-table td strong {
            display: block;
            margin-bottom: 2px;
        }
        .party-table td,
        .meta-table td,
        .bank-table td {
            padding: 7px 8px;
        }
        .stack-line {
            margin-top: 2px;
        }
        .party-content {
            line-height: 1.4;
        }
        .total-row td {
            font-weight: 700;
            font-size: 12px;
            padding: 10px 8px;
            background: #f4f7fb;
            text-align: center;
        }
        .bank-title {
            margin: 14px 0 6px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
        }
        .section-title {
            margin: 14px 0 6px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
        }
        .closing {
            margin-top: 14px;
        }
        .closing p {
            margin: 0 0 4px;
        }
        .footer {
            margin-top: 28px;
            width: 100%;
        }
        .footer td {
            width: 100%;
            text-align: right;
            vertical-align: bottom;
        }
        .signature-line {
            margin: 0 0 6px auto;
            border-top: 1px solid #111;
            display: inline-block;
            width: 180px;
        }
        .align-center {
            text-align: center;
        }
        .meta-value {
            line-height: 1.4;
        }
        .text-nowrap {
            white-space: nowrap;
        }
        @media print {
            .print-bar {
                display: none;
            }
            .invoice-box {
                padding-top: 0;
            }
        }
    </style>
</head>
<body>
    <div class="print-bar">
        <button class="print-btn" onclick="window.print()">PRINT PROFORMA</button>
    </div>

    @php
        $company = $exportOrder->company;
        $bank = $proforma->customer_bank;
        $buyer = $exportOrder->buyer;
        $consignee = $exportOrder->consignee;
        $notifyParty = $proforma->consigned_details;
        $packingItems = $exportOrder->packingItems;
        $totalAmount = $packingItems->sum('amount');
        $shipmentPeriod = $exportOrder->shipment_delivery_date_from && $exportOrder->shipment_delivery_date_to
            ? $exportOrder->shipment_delivery_date_from->format('F') . ' - ' . $exportOrder->shipment_delivery_date_to->format('F Y')
            : '-';
    @endphp

    <div class="invoice-box">
        <table class="header-table">
            <tr>
                <td width="44%">
                    @if ($company && $company->logo)
                        <img src="{{ image_path($company->logo) }}" class="logo" alt="Logo">
                    @else
                        <strong>{{ $company->name ?? 'MESKEY & FEMTEE' }}</strong>
                    @endif
                </td>
                <td width="56%" class="company-info">
                    <strong>{{ $company->name ?? 'MESKEY & FEMTEE (PVT) LTD' }}</strong>
                    <div>{{ $company->address ?? '' }}</div>
                    <div>{{ $company->phone ?? '' }}{{ !empty($company->phone) && !empty($company->email) ? ' | ' : '' }}{{ $company->email ?? '' }}</div>
                    <div>NTN: {{ $company->ntn ?? 'N/A' }} | STRN: {{ $company->stn ?? 'N/A' }}</div>
                </td>
            </tr>
        </table>

        <div class="ref-row">
            <div class="ref-left">REFERENCE NO: {{ $proforma->proforma_no ?: ($exportOrder->contract_no ?? $exportOrder->voucher_no) }}</div>
            <div class="ref-right">DATE: {{ \Carbon\Carbon::parse($proforma->proforma_date ?? $exportOrder->voucher_date)->format('d.m.Y') }}</div>
        </div>

        <table class="party-table">
            <tr>
                <td class="block-label">Shipper</td>
                <td class="party-content">
                    <strong>{{ $company->name ?? '-' }}</strong>
                    <div>{!! nl2br(e($company->address ?? '-')) !!}</div>
                </td>
            </tr>
            {{-- <tr>
                <td class="block-label">Consignee</td>
                <td class="party-content">
                    @if ($consignee)
                        <strong>{{ $consignee->name ?: '-' }}</strong>
                        <div class="stack-line">{{ $consignee->contact ?: '' }}</div>
                        <div class="stack-line">{!! nl2br(e($consignee->address ?: '-')) !!}</div>
                    @else
                        <strong>{{ $buyer->name ?? '-' }}</strong>
                        <div class="stack-line">{!! nl2br(e($buyer->address ?? '-')) !!}</div>
                    @endif
                </td>
            </tr> --}}
            @if (filled(strip_tags((string) $notifyParty)) || $buyer)
                <tr>
                    <td class="block-label">Notify Party</td>
                    <td class="party-content">
                        @if (filled(strip_tags((string) $notifyParty)))
                            {!! $notifyParty !!}
                        @else
                            <strong>{{ $buyer->name ?? '-' }}</strong>
                            <div class="stack-line">{!! nl2br(e($buyer->address ?? '-')) !!}</div>
                        @endif
                    </td>
                </tr>
            @endif
        </table>

        <table class="items-table">
            <thead>
                <tr>
                    <th width="24%">Commodity</th>
                    <th width="24%">Specification</th>
                    <th width="12%">Quantity</th>
                    <th width="18%">Packing</th>
                    <th width="10%">Bags Marks</th>
                    <th width="12%">Unit Price</th>
                </tr>
            </thead>
            <tbody>
                @foreach($packingItems as $item)
                <tr>
                    <td>
                        <strong>{{ $exportOrder->visual_name ?? $exportOrder->product->name ?? 'Commodity' }}</strong>
                    </td>
                    <td>AS PER CONTRACT.</td>
                    <td class="align-center">{{ number_format((float) $item->metric_tons, 3) }} MT</td>
                    <td>
                        IN {{ rtrim(rtrim(number_format((float) $item->bag_size, 2), '0'), '.') }} KG {{ $item->bagCondition->name ?? '-' }} {{ $item->bagType->name ?? '' }} BAG.
                        {{-- @if($item->bagPacking?->name)
                            <div class="stack-line">{{ $item->bagPacking->name }}</div>
                        @endif --}}
                    </td>
                    <td>{{ $item->brand->name ?? ($exportOrder->marking_labeling ?: '-') }}</td>
                    <td class="text-nowrap">
                        {{ $exportOrder->currency->currency_code ?? 'USD' }} {{ number_format((float) $item->rate, 2) }}
                        @if($exportOrder->incoterm || $exportOrder->portOfDischarge)
                            <div class="stack-line">
                                PMT {{ strtoupper($exportOrder->incoterm->name ?? '') }}{{ $exportOrder->portOfDischarge ? ' ' . strtoupper($exportOrder->portOfDischarge->name) : '' }}
                            </div>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="total-row">
                    <td colspan="6">
                        TOTAL AMOUNTING {{ $exportOrder->currency->currency_code ?? 'USD' }} {{ number_format((float) $totalAmount, 2) }}
                    </td>
                </tr>
            </tfoot>
        </table>

        <div class="section-title">Shipment Terms</div>
        <table class="meta-table">
            <tr>
                <td class="label">Inspection</td>
                <td class="meta-value">{{ $exportOrder->inspection_required ?: 'SGS' }}</td>
            </tr>
            <tr>
                <td class="label">Insurance</td>
                <td class="meta-value">{{ $exportOrder->insurance_covered_by ? 'AT ' . strtoupper($exportOrder->insurance_covered_by) . "'S COST & ACCOUNT." : "AT BUYER'S COST & ACCOUNT." }}</td>
            </tr>
            <tr>
                <td class="label">Shipment</td>
                <td class="meta-value">{{ $shipmentPeriod }}</td>
            </tr>
            <tr>
                <td class="label">Payment Term</td>
                <td class="meta-value">{{ $exportOrder->modeOfTerm->name ?? '-' }}</td>
            </tr>
        </table>

        @if($bank)
            <div class="bank-title">Banking Details:</div>
            <table class="bank-table">
                <tr>
                    <td class="label">Bank Name</td>
                    <td>{{ $bank->bank_name ?? '-' }}</td>
                </tr>
                <tr>
                    <td class="label">SWIFT Code</td>
                    <td>{{ $bank->swift_code ?? '-' }}</td>
                </tr>
                <tr>
                    <td class="label">Account Title</td>
                    <td>{{ $bank->account_title ?? '-' }}</td>
                </tr>
                <tr>
                    <td class="label">Account No</td>
                    <td>{{ $bank->account_no ?? $bank->account_number ?? '-' }}</td>
                </tr>
                <tr>
                    <td class="label">IBAN No</td>
                    <td>{{ $bank->iban ?? '-' }}</td>
                </tr>
            </table>
        @endif

        <div class="footer" style="margin: 80px 0 0 0;">
            <table width="100%">
                <tr>
                    <td>
                        <div class="signature-line"></div>
                        <p>Authorized Signature</p>
                    </td>
                </tr>
            </table>
        </div>
    </div>
    <script>
        window.onload = function () {
            setTimeout(function () { window.print(); }, 300);
        };
    </script>
</body>
</html>
