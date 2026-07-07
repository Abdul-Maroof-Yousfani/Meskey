<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Contract - {{ $exportOrder->voucher_no }}</title>
    <style>
        @page {
            size: A4;
            margin: 20mm;
        }
        body {
            font-family: 'Arial', sans-serif;
            font-size: 15px;
            line-height: 1.6;
            color: #000;
            background: #fff;
            margin: 0;
            padding: 0;
        }
        .print-bar {
            padding: 10px;
            text-align: center;
            background: #eef2f7;
            border-bottom: 1px solid #cbd5e1;
            margin-bottom: 20px;
        }
        .print-btn {
            border: 0;
            background: #1d4ed8;
            color: #fff;
            padding: 8px 24px;
            font-weight: 700;
            cursor: pointer;
            font-size: 14px;
        }
        @media print {
            .print-bar {
                display: none;
            }
            .data-section {
                page-break-inside: avoid;
            }
            .signatures {
                page-break-inside: avoid;
            }
        }
        .header {
            text-align: left;
            margin-bottom: 20px;
        }
        .logo {
            width: 150px;
        }
        .header-title {
            text-align: center;
            font-size: 24px;
            font-weight: bold;
            text-decoration: underline;
            margin: 15px 0;
            text-transform: uppercase;
        }
        .contract-info {
            display: flex;
            justify-content: space-between;
            font-weight: bold;
            margin-bottom: 30px;
            text-transform: uppercase;
            font-size: 16px;
        }
        .section-title {
            font-size: 16px;
            font-weight: bold;
            text-decoration: underline;
            margin: 25px 0 10px;
            text-transform: uppercase;
        }
        .parties {
            display: flex;
            flex-direction: column;
            gap: 20px;
            margin-bottom: 30px;
        }
        .party-box strong {
            display: block;
            text-decoration: underline;
            margin-bottom: 5px;
            text-transform: uppercase;
            font-size: 16px;
        }
        .party-box p {
            margin: 0;
            text-transform: uppercase;
        }
        .agreement-text {
            margin: 25px 0;
            text-transform: uppercase;
            font-weight: bold;
        }
        .data-section {
            margin-bottom: 20px;
        }
        .data-section p {
            margin: 0 0 5px 0;
            text-transform: uppercase;
        }
        table.specs-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            text-transform: uppercase;
        }
        table.specs-table th, table.specs-table td {
            border: 1px solid #000;
            padding: 8px 12px;
        }
        table.specs-table td:first-child {
            width: 60%;
            font-weight: bold;
        }
        table.specs-table td:last-child {
            text-align: center;
            width: 40%;
        }
        .condition-list {
            margin: 0;
            padding-left: 25px;
            list-style-type: decimal;
            text-transform: uppercase;
        }
        .condition-list li {
            margin-bottom: 8px;
        }
        .dynamic-html p, .dynamic-html ol, .dynamic-html ul {
            margin: 0 0 10px 0;
            padding: 0;
            text-transform: uppercase;
        }
        .dynamic-html ol, .dynamic-html ul {
            padding-left: 25px;
        }
        .signatures {
            margin-top: 80px;
            display: flex;
            justify-content: space-between;
            text-transform: uppercase;
            font-weight: bold;
        }
        .signature-box {
            width: 40%;
            text-align: center;
        }
        .signature-box strong {
            display: block;
            text-decoration: underline;
            margin-bottom: 80px;
            text-align: left;
        }
        .signature-box p {
            border-top: 1px solid #000;
            padding-top: 10px;
            margin: 0;
        }
    </style>
</head>
<body>
    <div class="print-bar">
        <button class="print-btn" onclick="window.print()">PRINT SALES CONTRACT</button>
    </div>

    @php
        $company = $exportOrder->company;
        $totalAmount = $exportOrder->packingItems->sum('amount');
        $totalMt = $exportOrder->packingItems->sum('metric_tons');
        $currencyCode = $exportOrder->currency->currency_code ?? 'USD';

        // Calculate rate (Amount / MT) if possible, else just use the first item's rate or 0
        $avgRate = $totalMt > 0 ? ($totalAmount / $totalMt) : 0;
        
        $scNames = [];
        if (is_array($exportOrder->shipment_country)) {
            $scNames = \App\Models\Export\ShipmentCountry::whereIn('id', $exportOrder->shipment_country)->pluck('name')->toArray();
        }
        $countriesText = count($scNames) ? implode(' / ', $scNames) : '';
        
        $documents = $exportOrder->documents_to_be_provided;
        $otherConditions = $exportOrder->other_condition;
    @endphp

    <div class="content-wrapper">
        <div class="header">
            @if ($company && $company->logo)
                <img src="{{ image_path($company->logo) }}" class="logo" alt="Logo">
            @endif
        </div>

        <div class="header-title">SALES CONTRACT</div>

        <div class="contract-info">
            <div>CONTRACT NO: {{ $exportOrder->voucher_no ?? ''}}</div>
            <div>DATE: {{ $exportOrder->voucher_date ? $exportOrder->voucher_date->format('d.m.Y') : 'XX.XX.XXXX' }}</div>
        </div>

        <div class="agreement-text">
            THIS CONTRACT IS DRAWN BETWEEN THE FOLLOWING PARTIES.
        </div>

        <div class="parties">
            <div class="party-box">
                <strong>SELLER:</strong>
                <p>{{ $company->name ?? 'MESKAY & FEMTEE TRADING COMPANY (PVT) LTD.' }}</p>
                <p>{{ $company->address ?? '' }}</p>
            </div>
            <div class="party-box">
                <strong>BUYER:</strong>
                <p>{{ $exportOrder->buyer->name ?? 'UNKNOWN BUYER' }}</p>
                <p>{{ $exportOrder->buyer->address ?? '' }}</p>
            </div>
        </div>

        <div class="agreement-text">
            THE SELLER AGREES TO SELL AND BUYER AGREES TO BUY ON THE FOLLOWING TERMS AND CONDITIONS.
        </div>

        <div class="section-title">COMMODITY:</div>
        <div class="data-section">
            <p>{{ $exportOrder->visual_name ?? ($exportOrder->product->name ?? '') }}</p>
        </div>

        <div class="section-title">QUANTITY:</div>
        <div class="data-section">
            <p>{{ number_format((float) $totalMt, 2) }} METRIC TONS (+/- 5% AT SELLER'S OPTION) IN BULK</p>
        </div>

        <div class="section-title">SPECIFICATIONS:</div>
        <div class="data-section">
            @if($exportOrder->specifications->count() > 0)
                <table class="specs-table">
                    @foreach($exportOrder->specifications as $spec)
                        <tr>
                            <td>{{ $spec->spec_name }}</td>
                            <td>{{ $spec->spec_value }} {{ $spec->uom }} {{ strtoupper($spec->value_type) }}</td>
                        </tr>
                    @endforeach
                    <tr>
                        <td colspan="2" style="border: 0; padding-top: 15px; text-align: left;">
                            OTHER DETAILS AS PER PAKISTAN EXPORT STANDARD
                        </td>
                    </tr>
                    {{-- @if($exportOrder->other_specifications)
                        <tr>
                            <td colspan="2" style="border: 0; padding-top: 15px; text-align: left;">
                                OTHER DETAILS AS PER PAKISTAN EXPORT STANDARD<br>
                                {{ $exportOrder->other_specifications }}
                            </td>
                        </tr>
                    @endif --}}
                </table>
            @else
                <p>AS PER STANDARD EXPORT QUALITY.</p>
            @endif
        </div>

        <div class="section-title">PRICE:</div>
        <div class="data-section">
            <p>{{ $currencyCode }} {{ number_format((float) $avgRate, 2) }} PMT {{ strtoupper($exportOrder->incoterm->name ?? '') }} {{ strtoupper($exportOrder->portOfDischarge->name ?? '') }}</p>
            <p>TOTAL: {{ $currencyCode }} {{ number_format((float) $totalAmount, 2) }}</p>
        </div>

        <div class="section-title">ORIGIN:</div>
        <div class="data-section">
            <p>{{ strtoupper($exportOrder->originCountry->name ?? 'PAKISTAN') }}</p>
        </div>

        <div class="section-title">PACKING:</div>
        <div class="data-section">
            @foreach($exportOrder->packingItems as $item)
                <p style="margin-bottom: 5px;">
                    IN {{ strtoupper($item->bagCondition->name ?? '') }} {{ strtoupper($item->bagType->name ?? '') }} BAGS OF {{ number_format((float) $item->bag_size) }} KGS NET EACH WITH {{ strtoupper($item->brand->name ?? "BUYER'S MARK") }}. 
                    @if($item->min_weight_empty_bags > 0)
                    TARE WEIGHT TO BE MINIMUM {{ number_format((float) $item->min_weight_empty_bags) }} GRAMS.
                    @endif
                    @if($item->empty_bags_percentage > 0)
                    {{ number_format((float) $item->empty_bags_percentage) }}% EMPTY BAGS TO BE SHIPPED BY THE SELLER WITH SHIPMENT FREE OF CHARGE.
                    @endif
                </p>
            @endforeach
        </div>

        <div class="section-title">PORT OF LOADING:</div>
        <div class="data-section">
            <p>{{ strtoupper($exportOrder->portOfLoading->name ?? '') }}</p>
        </div>

        <div class="section-title">PORT OF DISCHARGE:</div>
        <div class="data-section">
            <p>{{ strtoupper($exportOrder->portOfDischarge->name ?? '') }}</p>
        </div>

        <div class="section-title">DISCHARGE RATE:</div>
        <div class="data-section">
            <p>CARGO TO BE DISCHARGED AT THE RATE OF {{ number_format((float) $exportOrder->discharge_rate) }} METRIC TONS PWWD {{ strtoupper($exportOrder->discharge_term_type ?? 'SHEX EIU') }} {{ strtoupper($exportOrder->discharge_shex_eiu) }}. VESSEL AGENT AT DISCHARGE PORT TO BE AT BUYERS CHOICE AND OWNER / SHIPPER TO PAY PORT DA SUBJECT REASONABLE FDA.</p>
            <p style="margin-top: 10px;">NOTICE OF READINESS (N.O.R.) TENDERABLE IN WRITING BETWEEN THE NORMAL BUSINESS HOURS OF 08:00 TO 17:00 HOURS MONDAY THROUGH FRIDAY AND BETWEEN 08:00 AND 12:00 HOURS ON SATURDAYS.</p>
            <p style="margin-top: 10px;">NOR MAY BE TENDERED WHETHER IN BERTH OR NOT (WIBON), WHETHER IN PORT OR NOT (WIPON), WHETHER IN FREE PRATIQUE OR NOT (WIFPON), WHETHER CUSTOM CLEARED OR NOT (WCCON).</p>
            <p style="margin-top: 10px;">LAYTIME TO COMMENCE AFTER N.O.R. TENDERED WHETHER IN BERTH OR NOT (WIBON), WHETHER IN PORT OR NOT (WIPON), WHETHER IN FREE PRATIQUE OR NOT (WIFPON), WHETHER CUSTOM CLEARED OR NOT (WCCON).</p>
            <p style="margin-top: 10px;">LAYTIME COMMENCE AT 1.00 P.M. IF NOR IS TENDERED DURING THE OFFICE HOURS BEFORE NOON 12.00 P.M AND AT 8.00 A.M. OF THE NEXT WORKING DAY, IF NOR IS TENDERED IN THE AFTERNOON DURING OFFICE HOURS.</p>
            <p style="margin-top: 10px;">TIME FROM NOON ON SATURDAY OR FROM 1700 HOURS ON A DAY PRECEDING OFFICIAL OR PORT HOLIDAY TO 0800 HOURS MONDAY OR NEXT WORKING DAY NOT TO COUNT EVEN IF USED.</p>
            <p style="margin-top: 10px;">NOR TO BE TENDERED BY CABLE/ TELEX/ FAX /EMAIL IN WRITING.</p>
            <p style="margin-top: 10px;">DEMURRAGE RATE: AS PER CP(BUT MAXIMUM 15,000 USD/HALF DISPATCH)</p>
            <p style="margin-top: 10px;">DEMURRAGE / DISCHARGE TO BE SETTLED WITHIN 10 DAYS AFTER COMPLETION OF DISCHARGE AND SUBMISSION OF CHARTEREERS LAYTIME CALCULATION ALONGWITH SUPPORTING DOCUMENTS</p>
        </div>

        <div class="section-title">SHIPMENT PERIOD:</div>
        <div class="data-section">
            <p>
                @if($exportOrder->shipment_delivery_date_from && $exportOrder->shipment_delivery_date_to)
                    {{ strtoupper($exportOrder->shipment_delivery_date_from->format('F')) }} TO {{ strtoupper($exportOrder->shipment_delivery_date_to->format('F - jS - Y')) }}
                @else
                    AS PER AGREED SCHEDULE
                @endif
            </p>
        </div>

        <div class="section-title">PAYMENT:</div>
        <div class="data-section">
            <p>{{ strtoupper($exportOrder->modeOfTerm->name ?? '100% DP AT SIGHT') }}</p>
        </div>

        <div class="section-title">DOCUMENTS REQUIRED:</div>
        <div class="data-section">
            <div class="dynamic-html">
                {!! $documents !!}
            </div>
        </div>

        <div class="section-title">INSPECTION:</div>
        <div class="data-section">
            <ol class="condition-list">
                <li>BY THIRD PARTY INDEPENDENT SURVEYOR AT BUYER'S CHOICE AND SELLER'S COST FINAL AT LOAD PORT.</li>
                <li>FIRST TESTING COST WILL BE ON BUYER'S ACCOUNT WHILE SUBSEQUENT COST ON SELLER'S ACCOUNT.</li>
            </ol>
        </div>

        @php
            $incotermName = strtoupper(trim($exportOrder->incoterm?->name ?? ''));
        @endphp
        @if($incotermName == 'CNF' || $incotermName == 'FOB')
        <div class="section-title">INSURANCE:</div>
        <div class="data-section">
            <p>AT BUYER'S COST & ACCOUNT.</p>
            <p style="margin-top: 10px;">THE BUYER UNDERTAKES TO MAKE SURE THAT CARGO IS INSURED AFTER SAILING FROM LOAD PORT AT THEIR COST, RISK AND ACCOUNT. THE BUYER ALSO UNDERTAKES TO PROVIDE A COPY OF THE SAME TO THE SELLER PRIOR TO SAILING OF VESSEL.</p>
        </div>
        @elseif($incotermName == 'CIF')
        <div class="section-title">INSURANCE:</div>
        <div class="data-section">
            <p>AT SELLER'S COST & ACCOUNT.</p>
            <p style="margin-top: 10px;">THE SELLER UNDERTAKES TO MAKE SURE THAT CARGO IS INSURED AT THEIR COST, RISK AND ACCOUNT. THE SELLER ALSO UNDERTAKES TO PROVIDE A COPY OF THE SAME TO THE BUYER.</p>
        </div>
        @endif

        <div class="section-title">SELLER'S BANK DETAILS:</div>
        <div class="data-section">
            @php $bank = $exportOrder->correspondentBank; @endphp
            @if($bank)
                <p>{{ strtoupper($bank->bank_name ?? '') }}</p>
                <p>{{ strtoupper($bank->bank_address ?? '') }}</p>
                <p>SWIFT CODE: {{ strtoupper($bank->swift_code ?? '') }}</p>
                <p>ACCOUNT TITLE: {{ strtoupper($bank->account_title ?? '') }}</p>
                <p>ACCOUNT NO: {{ strtoupper($bank->account_no ?? '') }}</p>
                <p>IBAN NO: {{ strtoupper($bank->iban ?? '') }}</p>
            @else
                <p>N/A</p>
            @endif
            <p style="margin-top: 10px; font-weight: bold;">(PLEASE CONFIRM BANK DETAILS VIA VERBAL PHONE CALL OR VIA WHATSAPP BEFORE REMITTING THE PAYMENT)</p>
        </div>

        <div class="section-title">ADDITIONAL CONDITIONS:</div>
        <div class="data-section">
            <div class="dynamic-html">
                {!! $otherConditions !!}
            </div>
        </div>

        <div class="section-title">ARBITRATION:</div>
        <div class="data-section">
            <p>ALL OTHER TERMS AND CONDITIONS WHEN NOT IN CONTRADICTION WITH THE ABOVE, PER {{ strtoupper($exportOrder->gafta->name ?? '') }} WITH ARBITRATION IN {{ strtoupper($countriesText) }} PER GAFTA 125.</p>
        </div>

        <div class="section-title">FORCE MAJEURE:</div>
        <div class="data-section">
            <div class="dynamic-html">
                {!! $exportOrder->force_majure !!}
            </div>
        </div>

        <div class="section-title">GOVERNING LAW:</div>
        <div class="data-section">
            <div class="dynamic-html">
                {!! $exportOrder->application_law !!}
            </div>
        </div>

        <div class="section-title">CONFIDENTIALITY:</div>
        <div class="data-section">
            <div class="dynamic-html">
                {!! $exportOrder->confidentiality !!}
            </div>
        </div>

        <div class="signatures">
            <div class="signature-box">
                <strong>SELLER:</strong>
                <p>AUTHORISED SIGNATORY</p>
            </div>
            <div class="signature-box">
                <strong>BUYER:</strong>
                <p>AUTHORISED SIGNATORY</p>
            </div>
        </div>
    </div>

    <script>
        window.onload = function () {
            setTimeout(function () { window.print(); }, 500);
        };
        window.onafterprint = function() {
            window.close();
        };
    </script>
</body>
</html>
