@php
    $preview = $preview ?? [];
    $goodsSummary = $goodsSummary ?? [];
    $rows = $goodsSummary['rows'] ?? ($preview['line_items'] ?? []);
    $totals = $goodsSummary['totals'] ?? [];
    $isMultiple = (bool) ($preview['is_multiple_items'] ?? count($rows) > 1);

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

    $formatTon = function ($value) {
        return number_format((float) $value, 3) . ' M.TONS';
    };

    $bagMarkingLines = collect(preg_split("/\r\n|\n|\r/", (string) ($preview['bag_markings'] ?? '')))
        ->filter(fn ($line) => trim((string) $line) !== '')
        ->values();
@endphp

<style>
    .sa-sheet {
        background: #fff;
        color: #111;
        border: 1px solid #d7d7d7;
        padding: 42px 56px 54px;
        font-size: 15px;
        line-height: 1.45;
        font-family: "Arial", sans-serif;
    }

    .sa-topbar,
    .sa-meta-row,
    .sa-detail-row,
    .sa-signoff {
        display: flex;
        justify-content: space-between;
        gap: 18px;
    }

    .sa-topbar {
        align-items: flex-start;
        margin-bottom: 24px;
    }

    .sa-company {
        max-width: 42%;
        margin-left: auto;
        text-align: left;
        font-size: 13px;
        line-height: 1.35;
    }

    .sa-buyer {
        max-width: 68%;
        white-space: pre-line;
        font-weight: 700;
        text-transform: uppercase;
    }

    .sa-date {
        min-width: 160px;
        text-align: right;
        font-weight: 700;
    }

    .sa-ref {
        margin: 18px 0 30px;
        font-weight: 700;
        text-transform: uppercase;
    }

    .sa-title {
        text-align: center;
        font-size: 22px;
        font-weight: 700;
        text-decoration: underline;
        margin-bottom: 28px;
        text-transform: uppercase;
    }

    .sa-body-copy {
        margin-bottom: 16px;
        font-size: 14px;
        font-weight: 700;
        text-transform: uppercase;
    }

    .sa-detail-table {
        width: 100%;
        border-collapse: collapse;
    }

    .sa-detail-table td {
        padding: 4px 0;
        vertical-align: top;
        font-size: 14px;
    }

    .sa-label {
        width: 33%;
        font-weight: 700;
        text-transform: uppercase;
        padding-right: 12px;
    }

    .sa-colon {
        width: 3%;
        font-weight: 700;
        text-align: center;
    }

    .sa-value {
        width: 64%;
        font-weight: 700;
        text-transform: uppercase;
    }

    .sa-multi-block {
        margin-bottom: 16px;
    }

    .sa-multi-row {
        display: flex;
        gap: 20px;
        margin-bottom: 4px;
    }

    .sa-multi-code {
        width: 42px;
        font-weight: 700;
        text-transform: uppercase;
    }

    .sa-multi-qty {
        width: 160px;
        font-weight: 700;
        text-decoration: underline;
        text-transform: uppercase;
    }

    .sa-multi-text {
        flex: 1;
        font-weight: 700;
        text-transform: uppercase;
    }

    .sa-bag-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 6px 18px;
        font-weight: 700;
        text-transform: uppercase;
    }

    .sa-signoff {
        flex-direction: column;
        margin-top: 38px;
        font-weight: 700;
        text-transform: uppercase;
    }

    @media print {
        body * {
            visibility: hidden;
        }

        #ShipmentAdvisePreviewContainer,
        #ShipmentAdvisePreviewContainer * {
            visibility: visible;
        }

        #ShipmentAdvisePreviewContainer {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
        }
    }
</style>

<div class="sa-sheet">
    <div class="sa-topbar">
        <div class="sa-buyer">
            TO,
            <br>
            {!! nl2br(e($preview['buyer_block'] ?? 'N/A')) !!}
        </div>

        <div class="sa-company">
            @if (!empty($preview['company_name']))
                <div style="font-weight: 700; text-transform: uppercase;">{{ $preview['company_name'] }}</div>
            @endif
            @if (!empty($preview['company_address']))
                <div style="text-transform: uppercase;">{{ $preview['company_address'] }}</div>
            @endif
            @if (!empty($preview['company_phone']))
                <div>T: {{ $preview['company_phone'] }}</div>
            @endif
        </div>
    </div>

    <div class="sa-date">DATED: {{ $formatDate($preview['document_date'] ?? null) }}</div>
    <div class="sa-ref">REF NO: {{ $preview['reference_no'] ?? 'N/A' }}</div>

    <div class="sa-title">SHIPMENT ADVISE</div>

    <div class="sa-body-copy">DEAR SIR / MADAM,</div>
    <div class="sa-body-copy">PLEASE NOTE FOLLOWING FULL SHIPMENT DETAILS FOR YOUR INFORMATION:</div>

    <table class="sa-detail-table">
        @if (!$isMultiple)
            <tr>
                <td class="sa-label">QUANTITY</td>
                <td class="sa-colon">:</td>
                <td class="sa-value">{{ $preview['quantity_text'] ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td class="sa-label">PACKING</td>
                <td class="sa-colon">:</td>
                <td class="sa-value">{{ $preview['packing_text'] ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td class="sa-label">BAG MARKING</td>
                <td class="sa-colon">:</td>
                <td class="sa-value">{{ $preview['bag_marking_text'] ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td class="sa-label">DESCRIPTION OF GOODS</td>
                <td class="sa-colon">:</td>
                <td class="sa-value">{{ $preview['description_of_goods'] ?? 'N/A' }}</td>
            </tr>
        @else
            <tr>
                <td class="sa-label">DESCRIPTION OF GOODS</td>
                <td class="sa-colon">:</td>
                <td class="sa-value">
                    @foreach ($rows as $row)
                        <div class="sa-multi-block">
                            <div class="sa-multi-row">
                                <div class="sa-multi-code">{{ ($row['label'] ?? '-') . ')' }}</div>
                                <div class="sa-multi-qty">{{ $row['quantity_text'] ?? 'N/A' }}</div>
                                <div class="sa-multi-text">{{ $row['description'] ?? 'N/A' }}</div>
                            </div>
                            <div class="sa-multi-row">
                                <div class="sa-multi-code"></div>
                                <div class="sa-multi-qty">PACKING:</div>
                                <div class="sa-multi-text">{{ $row['packing_text'] ?? 'N/A' }}</div>
                            </div>
                            <div class="sa-multi-row">
                                <div class="sa-multi-code"></div>
                                <div class="sa-multi-qty">NUMBER OF BAGS:</div>
                                <div class="sa-multi-text">
                                    @foreach (($row['number_of_bags_lines'] ?? []) as $bagLine)
                                        <div>{{ $bagLine }}</div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endforeach
                </td>
            </tr>
            <tr>
                <td class="sa-label">BAG MARKING</td>
                <td class="sa-colon">:</td>
                <td class="sa-value">
                    <div class="sa-bag-grid">
                        @foreach ($bagMarkingLines as $line)
                            <div>{{ $line }}</div>
                        @endforeach
                    </div>
                </td>
            </tr>
        @endif

        <tr>
            <td class="sa-label">GROSS WEIGHT</td>
            <td class="sa-colon">:</td>
            <td class="sa-value">{{ $preview['gross_weight_text'] ?? $formatTon($totals['gross_weight_mt'] ?? 0) }}</td>
        </tr>
        <tr>
            <td class="sa-label">NET WEIGHT</td>
            <td class="sa-colon">:</td>
            <td class="sa-value">{{ $preview['net_weight_text'] ?? $formatTon($totals['net_weight_mt'] ?? 0) }}</td>
        </tr>
        <tr>
            <td class="sa-label">NUMBER OF BAGS</td>
            <td class="sa-colon">:</td>
            <td class="sa-value">
                {{ $isMultiple ? ($preview['total_bags_summary'] ?? 'N/A') : ($preview['number_of_bags_text'] ?? 'N/A') }}
            </td>
        </tr>
        <tr>
            <td class="sa-label">VESSEL'S NAME</td>
            <td class="sa-colon">:</td>
            <td class="sa-value">{{ $preview['vessel_name'] ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td class="sa-label">ETD FROM LOADING PORT</td>
            <td class="sa-colon">:</td>
            <td class="sa-value">{{ $preview['etd_from_loading_port'] ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td class="sa-label">ETA AT DISCHARGE PORT</td>
            <td class="sa-colon">:</td>
            <td class="sa-value">{{ $preview['eta_at_discharge_port'] ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td class="sa-label">LOADING PORT</td>
            <td class="sa-colon">:</td>
            <td class="sa-value">{{ $preview['loading_port'] ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td class="sa-label">DISCHARGE PORT</td>
            <td class="sa-colon">:</td>
            <td class="sa-value">{{ $preview['discharge_port'] ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td class="sa-label">BILL OF LADING NUMBER</td>
            <td class="sa-colon">:</td>
            <td class="sa-value">{{ $preview['bill_of_lading_no'] ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td class="sa-label">BILL OF LADING DATE</td>
            <td class="sa-colon">:</td>
            <td class="sa-value">{{ $formatDate($preview['bill_of_lading_date'] ?? null) }}</td>
        </tr>
    </table>

    <div class="sa-signoff">
        <div>EXPORT DEPARTMENT</div>
        <div>FOR: {{ strtoupper($preview['company_name'] ?? 'MESKAY & FEMTEE TRADING COMPANY (PVT) LTD.') }}</div>
    </div>
</div>
