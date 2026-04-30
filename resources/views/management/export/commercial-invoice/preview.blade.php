@php
    $preview = $preview ?? [];
    $goodsSummary = $goodsSummary ?? [];
    $rows = $goodsSummary['rows'] ?? [];
    $totals = $goodsSummary['totals'] ?? [];
    $bank = $preview['bank_details'] ?? [];

    $formatDate = function ($value) {
        if (!$value) {
            return 'N/A';
        }

        try {
            return \Carbon\Carbon::parse($value)->format('d.m.Y');
        } catch (\Throwable $e) {
            return $value;
        }
    };

    $fmt = fn ($value, $decimals = 2) => number_format((float) $value, $decimals);

    $customerLines = collect([
        $preview['customer_name'] ?? null,
        $preview['customer_address'] ?? null,
        $preview['customer_phone'] ?? null ? 'PHONE: ' . $preview['customer_phone'] : null,
    ])->filter()->values();

    $quantityBlocks = collect($rows)->map(function ($row) use ($fmt) {
        return $fmt($row['quantity_mt'] ?? 0, 3) . ' MTS';
    })->filter()->values();

    $countryOrigin = trim(($preview['origin_name'] ?? 'PAKISTAN'));
@endphp

<style>
    .ci-sheet {
        position: relative;
        background: #fff;
        color: #111;
        border: 1px solid #666;
        padding: 26px 14px 14px;
        font-size: 12px;
        font-family: "Times New Roman", serif;
    }

    .ci-sheet table {
        width: 100%;
        border-collapse: collapse;
    }

    .ci-sheet td,
    .ci-sheet th {
        border: 1px solid #666;
        padding: 4px 6px;
        vertical-align: top;
    }

    .ci-title-floating {
        position: absolute;
        top: -11px;
        left: 50%;
        transform: translateX(-50%);
        background: #fff;
        padding: 0 12px;
        font-weight: 700;
        font-size: 18px;
        letter-spacing: .8px;
    }

    .ci-label {
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
    }

    .ci-strong {
        font-weight: 700;
    }

    .ci-right {
        text-align: right;
    }

    .ci-center {
        text-align: center;
    }

    .ci-wrap {
        white-space: normal;
        overflow-wrap: anywhere;
    }

    .ci-tight div {
        margin: 8px 0;
        line-height: 1.2;
    }

    .ci-u {
        text-decoration: underline;
        font-weight: 700;
    }

    .ci-subblock {
        border-bottom: 1px solid #bbb;
        padding: 2px 0;
    }

    .ci-subblock:last-child {
        border-bottom: none;
    }

    .ci-no-pad {
        padding: 0 !important;
    }

    @media print {
        body * {
            visibility: hidden;
        }

        #commercialInvoicePreviewContainer,
        #commercialInvoicePreviewContainer * {
            visibility: visible;
        }

        #commercialInvoicePreviewContainer {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
        }
    }
</style>

<div class="ci-sheet">
    <div class="ci-title-floating">COMMERCIAL INVOICE</div>

    <div style="margin-bottom: 8px;">
        <span class="ci-strong">TO:</span>
        <span class="ci-wrap">{!! nl2br(e($customerLines->implode("\n") ?: 'N/A')) !!}</span>
    </div>

    <table style="margin-bottom: 8px;">
        <tr>
            <td style="width: 20%;">
                <div class="ci-label">Invoice No</div>
                <div>{{ $preview['invoice_no'] ?? 'N/A' }}</div>
            </td>
            <td style="width: 18%;">
                <div class="ci-label">Date</div>
                <div>{{ $formatDate($preview['invoice_date'] ?? null) }}</div>
            </td>
            <td style="width: 21%;">
                <div class="ci-label">Port of Loading</div>
                <div>{{ $preview['port_of_loading'] ?? 'N/A' }}</div>
            </td>
            <td style="width: 21%;">
                <div class="ci-label">Port of Discharge</div>
                <div>{{ $preview['port_of_discharge'] ?? 'N/A' }}</div>
            </td>
            <td style="width: 20%;">
                <div class="ci-label">Contents</div>
                <div>{{ $preview['contents'] ?? 'N/A' }}</div>
            </td>
        </tr>
        <tr>
            <td></td>
            <td></td>
            <td>
                <div class="ci-label">Country of Origin</div>
                <div>{{ $countryOrigin }}</div>
            </td>
            <td>
                <div class="ci-label">Payment Terms</div>
                <div>{{ $preview['payment_terms'] ?? 'N/A' }}</div>
            </td>
            <td>
                <div class="ci-label">Export Order No</div>
                <div>{{ $preview['export_order_no'] ?? 'N/A' }}</div>
            </td>
        </tr>
        <tr>
            <td>
                <div class="ci-label">Vessel's Name</div>
                <div>{{ $preview['vessel_name'] ?? 'N/A' }}</div>
            </td>
            <td>
                <div class="ci-label">On Board Date</div>
                <div>{{ $formatDate($preview['shipped_on_board_date'] ?? null) }}</div>
            </td>
            <td colspan="2">
                <div class="ci-label text-center">Bill of Lading No</div>
                <div class="ci-center">{{ $preview['bill_of_lading_no'] ?? 'N/A' }}</div>
            </td>
            <td class="ci-no-pad">
                <div style="padding: 4px 6px;" class="ci-label">Quantity</div>
                @forelse ($quantityBlocks as $block)
                    <div class="ci-subblock" style="padding: 4px 6px;">{{ $block }}</div>
                @empty
                    <div style="padding: 4px 6px;">N/A</div>
                @endforelse
            </td>
        </tr>
    </table>

    <table>
        <thead>
            <tr>
                <th style="width: 22%; text-align: center;">Shipping Marks</th>
                <th style="width: 43%; text-align: center;">Description of Goods</th>
                <th style="width: 17%; text-align: center;">Unit Price</th>
                <th style="width: 18%; text-align: center;">Amount</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td class="ci-center align-middle">
                        <div class="ci-strong">{{ $row['brand_name'] ?? 'N/A' }}</div>
                    </td>
                    <td class="align-middle">
                        <div class="ci-u">{{ $fmt($row['quantity_mt'] ?? 0, 2) }} MTS</div>
                        <div>{{ $row['product_visual_name'] ?? 'N/A' }}</div>
                        <div><span class="ci-strong">Packing:</span></div>
                        <span>{{ ($row['packing_text'] ?? 'N/A') . ' KG ' . strtoupper((string) ($row['bag_type'] ?? 'N/A') . ' Bags Stuffed in ') . ($row['master_packing_text'] ?? 'N/A') . ' Bag' }}</span>
                    </td>
                    <td class="ci-wrap ci-center ci-tight">
                        @foreach (($row['unit_price_lines'] ?? []) as $line)
                            <div>{{ $line }}</div>
                        @endforeach
                    </td>
                    <td class="ci-center"><span class="ci-strong">USD</span> {{ $fmt($row['amount'] ?? 0, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="ci-center">No packing data available.</td>
                </tr>
            @endforelse
            <tr>
                <td class="ci-wrap ci-tight">
                    <div class=" ci-strong">Bank Details</div>
                    <div>{{ $bank['bank_address'] ?? 'N/A' }}</div>
                    <div>SWIFT CODE: {{ $bank['swift_code'] ?? 'N/A' }}</div>
                    <div>ACCOUNT TITLE: {{ $bank['account_title'] ?? 'N/A' }}</div>
                    <div>ACCOUNT NO: {{ $bank['account_no'] ?? 'N/A' }}</div>
                    <div>IBAN: {{ $bank['iban'] ?? 'N/A' }}</div>
                </td>
                <td class="ci-wrap ci-tight align-bottom">
                    <div><span class="ci-strong">NET WEIGHT:</span> {{ $fmt($preview['net_weight_mt'] ?? 0, 3) }} M.TONS</div>
                    <div><span class="ci-strong">GROSS WEIGHT:</span> {{ $fmt($preview['gross_weight_mt'] ?? 0, 3) }} M.TONS</div>
                    <div class="mt-3"><span class="ci-strong" style="text-decoration: underline !important;">TOTAL AMOUNT:</span></div>
                    <span>{{ strtoupper($preview['amount_in_words'] ?? 'N/A') }}</span>
                </td>
                <td class="ci-center ci-strong align-bottom">TOTAL AMOUNT</td>
                <td class="ci-center ci-strong align-bottom"><span class="ci-strong">USD</span> {{ $fmt($preview['total_amount'] ?? 0, 2) }}</td>
            </tr>
        </tbody>
    </table>
    
    @if (!empty($preview['remarks']))
        <div style="margin-top: 20px; border-top: 1px solid #666; padding-top: 10px;">
            <div class="ci-label" style="margin-bottom: 5px;">Remarks:</div>
            <div class="ci-wrap">{!! $preview['remarks'] !!}</div>
        </div>
    @endif
</div>
