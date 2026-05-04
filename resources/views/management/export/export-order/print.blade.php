<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Export Order - {{ $exportOrder->voucher_no }}</title>
    <style>
        @page {
            size: A4;
            margin: 10mm;
        }
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            font-size: 10px;
            color: #111827;
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
        .document {
            max-width: 800px;
            margin: 0 auto;
            padding: 10px;
        }
        .header-table,
        .info-table,
        .items-table,
        .sub-items-table {
            width: 100%;
            border-collapse: collapse;
        }
        .header-table {
            margin-bottom: 8px;
            border-bottom: 2px solid #111827;
        }
        .header-table td {
            vertical-align: top;
            padding-bottom: 6px;
        }
        .logo {
            max-width: 175px;
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
        .title {
            text-align: center;
            margin: 8px 0 10px;
        }
        .title h1 {
            margin: 0;
            font-size: 18px;
            text-transform: uppercase;
        }
        .title .ref {
            margin-top: 3px;
            font-size: 10px;
        }
        .section-title {
            margin: 10px 0 4px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            background: #e2e8f0;
            padding: 5px 6px;
        }
        .info-table td,
        .info-table th,
        .items-table td,
        .items-table th,
        .sub-items-table td,
        .sub-items-table th {
            border: 1px solid #cbd5e1;
            padding: 4px 5px;
            vertical-align: top;
        }
        .info-table th,
        .items-table th,
        .sub-items-table th {
            background: #f8fafc;
            text-align: left;
            font-size: 9px;
            text-transform: uppercase;
        }
        .label {
            width: 18%;
            font-weight: 700;
            background: #f8fafc;
        }
        .split {
            width: 100%;
        }
        .split td {
            width: 50%;
            vertical-align: top;
        }
        .narrative-content p,
        .narrative-content ol,
        .narrative-content ul {
            margin: 0 0 4px;
            padding-left: 16px;
        }
        .narrative-content p:last-child,
        .narrative-content ol:last-child,
        .narrative-content ul:last-child {
            margin-bottom: 0;
        }
        .packing-note-row td {
            padding-top: 6px;
            padding-bottom: 6px;
        }
        .master-packing-row td {
            padding: 8px 0 4px;
            border-top: 0;
        }
        .master-packing-wrap {
            padding: 0 6px 2px;
        }
        .packing-spacer td {
            border: 0;
            height: 10px;
            padding: 0;
        }
        .signature-wrap {
            margin-top: 18px;
            width: 100%;
        }
        .signature-wrap td {
            width: 50%;
            text-align: center;
            vertical-align: bottom;
            padding-top: 26px;
        }
        .line {
            width: 200px;
            border-top: 1px solid #111827;
            margin: 0 auto 6px;
        }
        .signature-line {
            width: 200px;
            border-top: 1px solid #111827;
            margin: 0 auto 6px;
        }
        @media print {
            .print-bar {
                display: none;
            }
            .document {
                padding-top: 0;
            }
        }
    </style>
</head>
<body>
    <div class="print-bar">
        <button class="print-btn" onclick="window.print()">PRINT EXPORT ORDER</button>
    </div>

    @php
        $company = $exportOrder->company;
        $beneficiaryBank = $exportOrder->customerBank;
        $correspondentBank = $exportOrder->correspondentBank;
        $quotationLabel = $exportOrder->quotation ? (($exportOrder->quotation->reference ?? ('#' . $exportOrder->quotation_id)) . ' - ' . ($exportOrder->quotation->product->name ?? '')) : '-';
    @endphp

    <div class="document">
        <table class="header-table">
            <tr>
                <td width="42%">
                    @if ($company && $company->logo)
                        <img src="{{ image_path($company->logo) }}" class="logo" alt="Logo">
                    @else
                        <strong>{{ $company->name ?? 'Export Company' }}</strong>
                    @endif
                </td>
                <td width="58%" class="company-info">
                    <strong>{{ $company->name ?? 'Export Company' }}</strong>
                    <div>{{ $company->address ?? '' }}</div>
                    <div>{{ $company->phone ?? '' }}{{ !empty($company->phone) && !empty($company->email) ? ' | ' : '' }}{{ $company->email ?? '' }}</div>
                </td>
            </tr>
        </table>

        <div class="title">
            <h1>Export Order</h1>
            <div class="ref">Ref: {{ $exportOrder->voucher_no }}{{ $exportOrder->contract_no ? ' / ' . $exportOrder->contract_no : '' }}</div>
        </div>

        <table class="info-table">
            <tr>
                <td class="label">Quotation</td>
                <td>{{ $quotationLabel }}</td>
                <td class="label">Contract Date</td>
                <td>{{ $exportOrder->voucher_date?->format('d-M-Y') ?? '-' }}</td>
            </tr>
            <tr>
                <td class="label">Buyer</td>
                <td>{{ $exportOrder->buyer->name ?? '-' }}</td>
                <td class="label">Broker</td>
                <td>{{ $exportOrder->broker->name ?? '-' }}</td>
            </tr>
            <tr>
                <td class="label">Shipment</td>
                <td>{{ $exportOrder->shipment_delivery_date_from?->format('d-M-Y') ?? '-' }} to {{ $exportOrder->shipment_delivery_date_to?->format('d-M-Y') ?? '-' }}</td>
                <td class="label">Marking</td>
                <td>{{ $exportOrder->marking_labeling ?: '-' }}</td>
            </tr>
        </table>

        @if ($exportOrder->consignee)
            <table class="info-table" style="margin-top:8px;">
                <tr>
                    <td class="label">Consignee</td>
                    <td>{{ $exportOrder->consignee->name ?: '-' }}</td>
                    <td class="label">Contact</td>
                    <td>{{ $exportOrder->consignee->contact ?: '-' }}</td>
                </tr>
                <tr>
                    <td class="label">Address</td>
                    <td colspan="3">{{ $exportOrder->consignee->address ?: '-' }}</td>
                </tr>
            </table>
        @endif

        <table class="split" style="margin-top:8px;">
            <tr>
                <td style="padding-right:4px;">
                    <div class="section-title">Product And Specs</div>
                    <table class="info-table">
                        <tr>
                            <td class="label">Product</td>
                            <td>{{ $exportOrder->product->name ?? '-' }}</td>
                            <td class="label">Visual</td>
                            <td>{{ $exportOrder->visual_name ?? '-' }}</td>
                        </tr>
                        @foreach ($exportOrder->specifications as $spec)
                            <tr>
                                <td class="label">{{ $spec->spec_name }}</td>
                                <td>{{ $spec->spec_value }} {{ $spec->uom }}</td>
                                <td class="label">Type</td>
                                <td>{{ ucfirst($spec->value_type ?? 'min') }}</td>
                            </tr>
                        @endforeach
                        <tr>
                            <td class="label">Other Spec</td>
                            <td colspan="3">{{ $exportOrder->other_specifications ?: '-' }}</td>
                        </tr>
                    </table>
                </td>
                <td style="padding-left:4px;">
                    <div class="section-title">Export Details</div>
                    <table class="info-table">
                        <tr>
                            <td class="label">Incoterm</td>
                            <td>{{ $exportOrder->incoterm->name ?? '-' }}</td>
                            <td class="label">Packing</td>
                            <td>{{ $exportOrder->packing_type ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="label">Mode Term</td>
                            <td>{{ $exportOrder->modeOfTerm->name ?? '-' }}</td>
                            <td class="label">Transport</td>
                            <td>{{ $exportOrder->modeOfTransport->name ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="label">Origin</td>
                            <td>{{ $exportOrder->originCountry->name ?? '-' }}</td>
                            <td class="label">HS Code</td>
                            <td>{{ $exportOrder->hsCode->code ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="label">Loading</td>
                            <td>{{ $exportOrder->portOfLoading->name ?? '-' }}</td>
                            <td class="label">Discharge</td>
                            <td>{{ $exportOrder->portOfDischarge->name ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="label">Currency</td>
                            <td>{{ $exportOrder->currency->currency_name ?? '-' }}</td>
                            <td class="label">Payment Days</td>
                            <td>{{ $exportOrder->payment_days ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="label">Advance</td>
                            <td>{{ $exportOrder->advance_payment ?? 0 }}%</td>
                            <td class="label">Insurance</td>
                            <td>{{ $exportOrder->insurance_covered_by ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="label">Partial Pay</td>
                            <td>{{ $exportOrder->partial_payment ?? '-' }}</td>
                            <td class="label">Transhipment</td>
                            <td>{{ $exportOrder->transhipment ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="label">Part Shipment</td>
                            <td colspan="3">{{ $exportOrder->part_shipment ?? '-' }}</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <div class="section-title">Packing Details</div>
        <table class="items-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Brand</th>
                    <th>Bag</th>
                    <th>Bag Size</th>
                    <th>Bags</th>
                    <th>Extra</th>
                    <th>Empty</th>
                    <th>MT</th>
                    <th>Containers</th>
                    <th>Stuffing</th>
                    <th>Rate</th>
                    <th>Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($exportOrder->packingItems as $index => $item)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $item->brand->name ?? '-' }}</td>
                        <td>{{ $item->bagType->name ?? '-' }}</td>
                        <td>{{ number_format((float) $item->bag_size, 2) }}</td>
                        <td>{{ number_format((float) $item->no_of_bags, 0) }}</td>
                        <td>{{ $item->extra_bags ?? 0 }} / {{ number_format((float) ($item->extra_bags_percentage ?? 0), 2) }}%</td>
                        <td>{{ $item->empty_bags ?? 0 }} / {{ number_format((float) ($item->empty_bags_percentage ?? 0), 2) }}%</td>
                        <td>{{ number_format((float) $item->metric_tons, 3) }}</td>
                        <td>{{ $item->no_of_containers ?? 0 }}</td>
                        <td>{{ number_format((float) $item->stuffing_in_container, 3) }}</td>
                        <td>{{ number_format((float) $item->rate, 2) }}</td>
                        <td>{{ number_format((float) $item->amount, 2) }}</td>
                    </tr>
                    <tr class="packing-note-row">
                        <td></td>
                        <td colspan="4"><strong>Packing:</strong> {{ $item->bagPacking->name ?? '-' }} | <strong>Condition:</strong> {{ $item->bagCondition->name ?? '-' }}</td>
                        <td colspan="3"><strong>Colors:</strong> {{ $item->bagColor->color ?? '-' }} / {{ $item->threadColor->color ?? '-' }}</td>
                        <td colspan="2"><strong>Stitching:</strong> {{ $item->stitching->name ?? '-' }}</td>
                        <td colspan="2"><strong>Min Empty Bag Wt:</strong> {{ number_format((float) $item->min_weight_empty_bags, 2) }}</td>
                    </tr>
                    @if ($item->subItems->count())
                        <tr class="master-packing-row">
                            <td colspan="12" class="master-packing-wrap">
                                <table class="sub-items-table">
                                    <thead>
                                        <tr>
                                            <th colspan="7">Master Packing</th>
                                        </tr>
                                        <tr>
                                            <th>Bag Type</th>
                                            <th>Bag Size</th>
                                            <th>Primary Bags</th>
                                            <th>No. of Bags</th>
                                            <th>Empty Bags</th>
                                            <th>Extra Bags</th>
                                            <th>Total Bags</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($item->subItems as $subItem)
                                            <tr>
                                                <td>{{ $subItem->bagType->name ?? '-' }}</td>
                                                <td>{{ $subItem->bagSize->size ?? '-' }}</td>
                                                <td>{{ $subItem->no_of_primary_bags ?? 0 }}</td>
                                                <td>{{ $subItem->no_of_bags ?? 0 }}</td>
                                                <td>{{ $subItem->empty_bags ?? 0 }}</td>
                                                <td>{{ $subItem->extra_bags ?? 0 }}</td>
                                                <td>{{ $subItem->total_bags ?? 0 }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </td>
                        </tr>
                    @endif
                    @if (! $loop->last)
                        <tr class="packing-spacer">
                            <td colspan="12"></td>
                        </tr>
                    @endif
                @endforeach
                <tr>
                    <td colspan="7" style="text-align:right;"><strong>Total</strong></td>
                    <td><strong>{{ number_format((float) $totalMetricTons, 3) }}</strong></td>
                    <td colspan="3"></td>
                    <td><strong>{{ ($exportOrder->currency->currency_code ?? '') . ' ' . number_format((float) $totalAmount, 2) }}</strong></td>
                </tr>
                <tr>
                    <td colspan="12"><strong>Amount In Words:</strong> {{ $amountInWords }}</td>
                </tr>
            </tbody>
        </table>

        <table class="split" style="margin-top:8px;">
            <tr>
                <td style="padding-right:4px;">
                    <div class="section-title">Beneficiary Bank Details</div>
                    <table class="info-table">
                        <tr><td class="label">Account Title</td><td>{{ $beneficiaryBank->account_title ?? '-' }}</td></tr>
                        <tr><td class="label">Bank Name</td><td>{{ $beneficiaryBank->bank_name ?? '-' }}</td></tr>
                        <tr><td class="label">Account No</td><td>{{ $beneficiaryBank->account_no ?? $beneficiaryBank->account_number ?? '-' }}</td></tr>
                        <tr><td class="label">IBAN</td><td>{{ $beneficiaryBank->iban ?? '-' }}</td></tr>
                        <tr><td class="label">SWIFT</td><td>{{ $beneficiaryBank->swift_code ?? '-' }}</td></tr>
                        <tr><td class="label">Address</td><td>{{ $beneficiaryBank->bank_address ?? '-' }}</td></tr>
                    </table>
                </td>
                <td style="padding-left:4px;">
                    <div class="section-title">Correspondent Bank Details</div>
                    <table class="info-table">
                        <tr><td class="label">Account Title</td><td>{{ $correspondentBank->account_title ?? '-' }}</td></tr>
                        <tr><td class="label">Bank Name</td><td>{{ $correspondentBank->bank_name ?? '-' }}</td></tr>
                        <tr><td class="label">Account No</td><td>{{ $correspondentBank->account_no ?? '-' }}</td></tr>
                        <tr><td class="label">IBAN</td><td>{{ $correspondentBank->iban ?? '-' }}</td></tr>
                        <tr><td class="label">SWIFT</td><td>{{ $correspondentBank->swift_code ?? '-' }}</td></tr>
                        <tr><td class="label">Address</td><td>{{ $correspondentBank->bank_address ?? '-' }}</td></tr>
                    </table>
                </td>
            </tr>
        </table>

        @php
            $narrativeRows = [
                'Shipping Instructions' => $exportOrder->shipping_instructions,
                'Documents To Be Provided' => $exportOrder->documents_to_be_provided,
                'Other Condition' => $exportOrder->other_condition,
                'Force Majeure' => $exportOrder->force_majure,
                'Application Law' => $exportOrder->application_law,
            ];
        @endphp

        @if (collect($narrativeRows)->filter(fn ($value) => filled(strip_tags((string) $value)))->isNotEmpty() || filled(strip_tags((string) $exportOrder->additional_info)))
            <div class="section-title">Narrative Details</div>
            <table class="info-table">
                @foreach ($narrativeRows as $title => $value)
                    @if (filled(strip_tags((string) $value)))
                        <tr>
                            <td class="label">{{ $title }}</td>
                            <td class="narrative-content">{!! $value !!}</td>
                        </tr>
                    @endif
                @endforeach
                @if (filled(strip_tags((string) $exportOrder->additional_info)))
                    <tr>
                        <td class="label">Additional Info</td>
                        <td class="narrative-content">{!! $exportOrder->additional_info !!}</td>
                    </tr>
                @endif
            </table>
        @endif

        <table class="signature-wrap">
            <tr>
                <td></td>
                <td>
                    <div class="signature-line"></div>
                    Authorized Signature<br>
                    <strong>{{ $company->name ?? 'Export Company' }}</strong>
                </td>
            </tr>
        </table>
    </div>

    <script>
        window.onload = function () {
            setTimeout(function () { window.print(); }, 300);
        };
    </script>
</body>
</html>
