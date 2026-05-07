@php
    $preview = $preview ?? [];
    $goodsSummary = $goodsSummary ?? [];
    $rows = $goodsSummary['rows'] ?? [];
    $totals = $goodsSummary['totals'] ?? [];

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

    $fmt = fn ($value, $decimals = 3) => number_format((float) $value, $decimals);
@endphp

<style>
    .pl-sheet {
        position: relative;
        background: #fff;
        color: #111;
        border: 1px solid #666;
        padding: 26px 14px 14px;
        font-size: 12px;
        font-family: "Times New Roman", serif;
    }

    .pl-sheet table {
        width: 100%;
        border-collapse: collapse;
    }

    .pl-sheet td,
    .pl-sheet th {
        border: 1px solid #666;
        padding: 4px 6px;
        vertical-align: top;
    }

    .pl-title-floating {
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

    .pl-label {
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
    }

    .pl-center {
        text-align: center;
    }

    .pl-strong {
        font-weight: 700;
    }

    .pl-wrap {
        white-space: normal;
        overflow-wrap: anywhere;
    }

    .pl-lines div {
        margin-bottom: 3px;
    }

    .pl-no-pad {
        padding: 0 !important;
    }

    .pl-split-box {
        height: 100%;
        display: flex;
        flex-direction: column;
    }

    .pl-split-box > div {
        flex: 1;
        padding: 6px;
    }

    .pl-split-box > div + div {
        border-top: 1px solid #666;
    }

    .pl-buyer-cell {
        padding-bottom: 14px !important;
    }

    .pl-lower-grid {
        width: 100%;
        border-collapse: collapse;
    }

    .pl-lower-grid td,
    .pl-lower-grid th {
        border: 1px solid #666;
        padding: 4px 6px;
        vertical-align: top;
    }

    @media print {
        body * {
            visibility: hidden;
        }

        #packingListPreviewContainer,
        #packingListPreviewContainer * {
            visibility: visible;
        }

        #packingListPreviewContainer {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
        }
    }
</style>

<div class="pl-sheet">
    <div class="pl-title-floating">PACKING LIST</div>

    <table style="margin-bottom: 8px;">
        <tr>
            <td style="width: 16%;">
                <span class="pl-label">Consignee / Buyer</span>
            </td>
            <td class="pl-wrap pl-buyer-cell" colspan="5">{!! nl2br(e($preview['buyer_block'] ?? 'N/A')) !!}</td>
        </tr>
        <tr>
            <td style="width: 16%;">
                <div class="pl-label">CI Invoice No</div>
                <div>{{ $preview['commercial_invoice_no'] ?? 'N/A' }}</div>
            </td>
            <td style="width: 16%;">
                <div class="pl-label">CI Invoice Date</div>
                <div>{{ $formatDate($preview['commercial_invoice_date'] ?? null) }}</div>
            </td>
            <td style="width: 22%;">
                <div class="pl-label">Port of Loading</div>
                <div>{{ $preview['port_of_loading'] ?? 'N/A' }}</div>
            </td>
            <td colspan="3">
                <div class="pl-label">Contents</div>
                <div>{{ $preview['contents'] ?? 'N/A' }}</div>
            </td>
        </tr>
        <tr>
            <td>
                <div class="pl-label">Contract / PO No / DC No</div>
                <div>{{ $preview['contract_po_dc_no'] ?? 'N/A' }}</div>
            </td>
            <td>
                <div class="pl-label">Date of Issue</div>
                <div>{{ $formatDate($preview['export_order_date'] ?? null) }}</div>
            </td>
            <td>
                <div class="pl-label">Port of Discharge</div>
                <div>{{ $preview['port_of_discharge'] ?? 'N/A' }}</div>
            </td>
            <td colspan="3">
                <div class="pl-label">Export Form Number</div>
                <div>
                    {{ $preview['export_form_no'] ?? 'N/A' }}
                    @if (!empty($preview['export_form_date']))
                        / {{ $formatDate($preview['export_form_date']) }}
                    @endif
                </div>
            </td>
        </tr>
        <tr>
            <td>
                <div class="pl-label">BOL No</div>
                <div>{{ $preview['bill_of_lading_no'] ?? 'N/A' }}</div>
            </td>
            <td>
                <div class="pl-label">BOL Date</div>
                <div>{{ $formatDate($preview['bill_of_lading_date'] ?? null) }}</div>
            </td>
            <td colspan="2">
                <div class="pl-label">Final Destination</div>
                <div>{{ $preview['final_destination'] ?? 'N/A' }}</div>
            </td>
            <td style="width: 18%;" class="pl-center" rowspan="2">
                <div class="pl-label">Quantity</div>
                <div class="pl-strong">{{ $preview['quantity_summary'] ?? 'N/A' }}</div>
            </td>
            <td style="width: 18%;" class="pl-center" rowspan="2">
                <div class="pl-label">Total No. of Bags</div>
                <div class="pl-strong">{{ $preview['total_bags_summary'] ?? 'N/A' }}</div>
            </td>
        </tr>
        <tr>
            <td>
                <div class="pl-label">Vessel's Name</div>
                <div>{{ $preview['vessel_name'] ?? 'N/A' }}</div>
            </td>
            <td>
                <div class="pl-label">Country of Origin</div>
                <div>{{ $preview['country_of_origin'] ?? 'N/A' }}</div>
            </td>
            <td colspan="2">
                <div class="pl-label">Payment Terms</div>
                <div>{{ $preview['payment_terms'] ?? 'N/A' }}</div>
            </td>
        </tr>
    </table>

    <table>
        <thead>
            <tr>
                <th style="width: 14%;" class="pl-center">Shipping Marks</th>
                <th style="width: 52%;" class="pl-center">Description of Goods</th>
                <th style="width: 17%;" class="pl-center">Net Weight</th>
                <th style="width: 17%;" class="pl-center">Gross Weight</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td class="pl-center">{{ $row['shipping_marks'] ?? ($preview['shipping_marks'] ?? 'N/M') }}</td>
                    <td class="pl-lines">
                        @foreach (($row['description_lines'] ?? []) as $line)
                            <div>{{ $line }}</div>
                        @endforeach
                    </td>
                    <td class="pl-center">
                        {{ $fmt($row['net_weight_mt'] ?? 0) }} M.TONS
                    </td>
                    <td class="pl-center">
                        {{ $fmt($row['gross_weight_mt'] ?? 0) }} M.TONS
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="pl-center">No packing data available.</td>
                </tr>
            @endforelse
            <tr>
                <td colspan="4" class="pl-no-pad">
                    <table class="pl-lower-grid">
                        <tr>
                            <th style="width: 10%;" class="pl-center">S. NO</th>
                            <th style="width: 15%;" class="pl-center">BAGS</th>
                            <th style="width: 35%;" class="pl-center">CONTAINER NUMBER</th>
                            <th style="width: 20%;" class="pl-center">NET WEIGHT</th>
                            <th style="width: 20%;" class="pl-center">GROSS WEIGHT</th>
                        </tr>
                        @foreach ($rows as $row)
                            <tr>
                                <td class="pl-center">{{ $row['serial_no'] ?? 'N/A' }}</td>
                                <td class="pl-center">{{ number_format((float) ($row['bags'] ?? 0)) }}</td>
                                <td class="pl-center">ATTACHED SHEET</td>
                                <td class="pl-center">{{ $fmt($row['net_weight_mt'] ?? 0) }}</td>
                                <td class="pl-center">{{ $fmt($row['gross_weight_mt'] ?? 0) }}</td>
                            </tr>
                        @endforeach
                        <tr>
                            <td colspan="3" class="pl-lines">
                                <div><span class="pl-strong">NET WEIGHT:</span> {{ $fmt($totals['net_weight_mt'] ?? 0) }} M.TONS</div>
                                <div><span class="pl-strong">GROSS WEIGHT:</span> {{ $fmt($totals['gross_weight_mt'] ?? 0) }} M.TONS</div>
                            </td>
                            <td class="pl-center">
                                <div class="pl-label">Total Nett Weight</div>
                                <div class="pl-strong">{{ $fmt($totals['net_weight_mt'] ?? 0) }} M.TONS</div>
                            </td>
                            <td class="pl-center">
                                <div class="pl-label">Total Gross Weight</div>
                                <div class="pl-strong">{{ $fmt($totals['gross_weight_mt'] ?? 0) }} M.TONS</div>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </tbody>
    </table>

    @if (!empty($preview['remarks']))
        <div style="margin-top: 20px; border-top: 1px solid #666; padding-top: 10px;">
            <div class="pl-label" style="margin-bottom: 5px;">Remarks:</div>
            <div class="pl-wrap">{!! $preview['remarks'] !!}</div>
        </div>
    @endif
</div>
