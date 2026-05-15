@php
    $preview = $preview ?? [];
    $goodsSummary = $goodsSummary ?? [];
    $rows = $goodsSummary['rows'] ?? [];
    $totals = $goodsSummary['totals'] ?? [];
    $firstRow = $rows[0] ?? [];
    $isMultiple = count($rows) > 1;

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

    $formatNumber = fn ($value, $decimals = 3) => number_format((float) $value, $decimals);

    $consigneeBlock = trim(collect([
        $preview['consignee_name'] ?? null,
        $preview['consignee_address'] ?? null,
    ])->filter()->implode("\n"));

    $notifyBlock = trim(collect([
        $preview['notify_name'] ?? null,
        $preview['notify_address'] ?? null,
    ])->filter()->implode("\n"));

    $shipperBlock = trim(collect([
        $preview['shipper_name'] ?? null,
        $preview['shipper_address'] ?? null,
    ])->filter()->implode("\n"));

    $bagMarkingText = $preview['bag_marking_text'] ?? ($firstRow['brand_name'] ?? 'N/A');
@endphp

<style>
    .bolc-sheet {
        background: #fff;
        color: #111;
        border: 1px solid #222;
        padding: 10px;
        font-size: 12px;
        font-family: Arial, sans-serif;
    }

    .bolc-sheet table {
        width: 100%;
        border-collapse: collapse;
    }

    .bolc-sheet td,
    .bolc-sheet th {
        border: 1px solid #222;
        padding: 4px 6px;
        vertical-align: top;
    }

    .bolc-no-border td,
    .bolc-no-border th {
        border: none;
        padding: 0;
    }

    .bolc-title {
        text-align: center;
        font-weight: 700;
        font-size: 18px;
        text-decoration: underline;
        padding-top: 8px;
        text-transform: uppercase;
    }

    .bolc-label {
        font-weight: 700;
        text-decoration: underline;
    }

    .bolc-block {
        white-space: pre-line;
        line-height: 1.4;
        text-transform: uppercase;
    }

    .bolc-type-box {
        text-align: center;
        font-size: 15px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 1px;
        padding-top: 60px !important;
    }

    .bolc-center {
        text-align: center;
    }

    .bolc-right {
        text-align: right;
    }

    .bolc-desc-box {
        min-height: 520px;
    }

    .bolc-desc-lines div {
        margin-bottom: 10px;
        text-transform: uppercase;
    }

    .bolc-desc-lines .spaced {
        margin-top: 26px;
    }

    .bolc-weight-box {
        min-height: 520px;
        text-align: right;
        text-transform: uppercase;
    }

    .bolc-weight-box .metric {
        margin-top: 90px;
        line-height: 1.5;
        font-weight: 700;
    }

    .bolc-line-item {
        margin-bottom: 18px;
    }

    .bolc-line-item:last-child {
        margin-bottom: 0;
    }

    .bolc-line-item .head {
        font-weight: 700;
        margin-bottom: 6px;
    }

    .bolc-line-item .sub {
        margin-bottom: 4px;
    }

    .bolc-stamp {
        display: inline-block;
        border: 2px solid #555;
        padding: 4px 10px;
        font-weight: 700;
        margin-top: 20px;
    }

    .bolc-footer-note {
        margin-top: 8px;
        font-weight: 700;
    }

    @media print {
        body * { visibility: hidden; }
        #bolPreviewContainer, #bolPreviewContainer * { visibility: visible; }
        #bolPreviewContainer { position: absolute; top: 0; left: 0; width: 100%; }
    }
</style>

<div class="bolc-sheet">
    <table class="bolc-no-border" style="margin-bottom: 6px;">
        <tr>
            <td style="width:58%;"></td>
            <td class="bolc-title" style="width:42%;">BL FORMAT</td>
        </tr>
    </table>

    <table>
        <tr>
            <td style="width:58%;">
                <div class="bolc-label">Shipper:</div>
                <div class="bolc-block">{{ $shipperBlock ?: 'N/A' }}</div>
            </td>
            <td rowspan="4" class="bolc-type-box" style="width:42%;">
                Container
            </td>
        </tr>
        <tr>
            <td>
                <div class="bolc-label">Consignee :</div>
                <div class="bolc-block">{{ $consigneeBlock ?: 'N/A' }}</div>
            </td>
        </tr>
        <tr>
            <td>
                <div class="bolc-label">Notify Party :</div>
                <div class="bolc-block">{{ $notifyBlock ?: 'N/A' }}</div>
            </td>
        </tr>
        <tr>
            <td style="padding:0;">
                <table>
                    <tr>
                        <td style="width:50%;">
                            <div class="bolc-label">Vessel</div>
                            <div class="bolc-block">{{ $preview['vessel_name'] ?? 'N/A' }}</div>
                        </td>
                        <td style="width:50%;">
                            <div class="bolc-label">Port of Loading :</div>
                            <div class="bolc-block">{{ $preview['port_of_loading'] ?? 'N/A' }}</div>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <div class="bolc-label">Port of Discharge:</div>
                            <div class="bolc-block">{{ $preview['port_of_discharge'] ?? 'N/A' }}</div>
                        </td>
                        <td>
                            <div class="bolc-label">Place of Delivery :</div>
                            <div class="bolc-block">{{ $preview['place_of_delivery'] ?? 'N/A' }}</div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <table>
        <tr>
            <th style="width:20%;">BAG MARKS:</th>
            <th style="width:62%;">DESCRIPTION</th>
            <th style="width:18%;"></th>
        </tr>
        <tr>
            <td class="bolc-center" style="text-transform: uppercase; font-weight:700;">{{ $bagMarkingText }}</td>
            <td class="bolc-desc-box">
                @if ($isMultiple)
                    <div class="bolc-desc-lines">
                        @foreach ($rows as $index => $row)
                            <div class="bolc-line-item">
                                <div class="head">{{ chr(65 + $index) }}) {{ $formatNumber($row['quantity_mt'] ?? 0, 3) }} MT IN {{ number_format((float) ($row['no_of_containers'] ?? 0)) }} CONTAINERS</div>
                                <div class="sub">{{ $row['product_name'] ?? 'N/A' }}</div>
                                <div class="sub"><span class="bolc-label">PACKING:</span> {{ $row['container_packing_description'] ?? 'N/A' }}</div>
                                <div class="sub"><span class="bolc-label">NO OF BAGS:</span> {{ $row['container_bag_count_summary'] ?? 'N/A' }}</div>
                            </div>
                        @endforeach
                        <div class="spaced"><span class="bolc-label">FI NO:</span> {{ $preview['financial_instrument_no'] ?? 'N/A' }}</div>
                        @if (!empty($preview['empty_bags_note']))
                            <div>{{ $preview['empty_bags_note'] }}</div>
                        @endif
                        <div class="spaced">NET WEIGHT: <span style="float:right;">{{ $formatNumber($preview['net_weight_mt'] ?? 0, 3) }} M.TONS</span></div>
                        <div>GROSS WEIGHT: <span style="float:right;">{{ $formatNumber($preview['gross_weight_mt'] ?? 0, 3) }} M.TONS</span></div>
                        <div class="spaced">21 DAYS FREE DETENTION/DEMURRAGE COMBINED AT DESTINATION</div>
                        <div class="bolc-center">
                            <div class="bolc-stamp">FREIGHT PREPAID</div>
                            <div class="bolc-footer-note">SHIPPED ON BOARD</div>
                        </div>
                    </div>
                @else
                    <div class="bolc-desc-lines">
                        <div>{{ $preview['quantity_summary'] ?? 'N/A' }}</div>
                        <div>{{ $preview['product_name'] ?? 'N/A' }}</div>
                        <div class="spaced"><span class="bolc-label">PACKING:</span></div>
                        <div>{{ $preview['packing_description'] ?? 'N/A' }}</div>
                        <div class="spaced"><span class="bolc-label">NO OF BAGS:</span> {{ $preview['number_of_bags_summary'] ?? 'N/A' }}</div>
                        <div><span class="bolc-label">FI NO:</span> {{ $preview['financial_instrument_no'] ?? 'N/A' }}</div>
                        @if (!empty($preview['empty_bags_note']))
                            <div class="spaced">{{ $preview['empty_bags_note'] }}</div>
                        @endif
                        <div class="spaced">NET WEIGHT: <span style="float:right;">{{ $formatNumber($preview['net_weight_mt'] ?? 0, 3) }} M.TONS</span></div>
                        <div>GROSS WEIGHT: <span style="float:right;">{{ $formatNumber($preview['gross_weight_mt'] ?? 0, 3) }} M.TONS</span></div>
                        <div class="spaced">21 DAYS FREE DETENTION/DEMURRAGE COMBINED AT DESTINATION</div>
                        <div class="bolc-center">
                            <div class="bolc-stamp">FREIGHT PREPAID</div>
                            <div class="bolc-footer-note">SHIPPED ON BOARD</div>
                        </div>
                    </div>
                @endif
            </td>
            <td class="bolc-weight-box">
                <div class="metric">
                    <div><span class="bolc-label">Gross Weight</span></div>
                    <div>{{ $formatNumber(($preview['gross_weight_mt'] ?? (($totals['gross_weight_kg'] ?? 0) / 1000)), 3) }}</div>
                    <div>KGS</div>
                    <br><br>
                    <div><span class="bolc-label">Net Weight</span></div>
                    <div>{{ $formatNumber(($preview['net_weight_mt'] ?? (($totals['net_weight_kg'] ?? 0) / 1000)), 3) }}</div>
                    <div>KGS</div>
                </div>
            </td>
        </tr>
    </table>
</div>
