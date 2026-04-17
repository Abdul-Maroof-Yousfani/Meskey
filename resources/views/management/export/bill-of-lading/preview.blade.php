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

    $formatNumber = function ($value, $decimals = 2) {
        return number_format((float) $value, $decimals);
    };

    $consigneeBlock = trim(collect([
        $preview['consignee_name'] ?? null,
        $preview['consignee_address'] ?? null,
        $preview['consignee_phone'] ?? null ? 'Phone: ' . $preview['consignee_phone'] : null,
        $preview['consignee_contact_person'] ?? null ? 'Contact Person: ' . $preview['consignee_contact_person'] : null,
    ])->filter()->implode("\n"));

    $notifyBlock = trim(collect([
        $preview['notify_name'] ?? null,
        $preview['notify_address'] ?? null,
        $preview['notify_phone'] ?? null ? 'Phone: ' . $preview['notify_phone'] : null,
        $preview['notify_contact_person'] ?? null ? 'Contact Person: ' . $preview['notify_contact_person'] : null,
    ])->filter()->implode("\n"));

    $shipperBlock = trim(collect([
        $preview['shipper_name'] ?? null,
        $preview['shipper_address'] ?? null,
        $preview['shipper_phone'] ?? null ? 'Phone: ' . $preview['shipper_phone'] : null,
        $preview['on_behalf_of'] ?? null ? 'ON BEHALF OF ' . $preview['on_behalf_of'] : null,
    ])->filter()->implode("\n"));
    $bagMarkings = collect($rows)->pluck('bag_markings')->filter()->unique()->values();
    $numberOfBagsLines = collect($rows)->map(function ($row) use ($formatNumber) {
        return trim(number_format((float) ($row['no_of_bags'] ?? 0)) . ' BAGS OF ' . ($row['packing_text'] . ' KG' ?? 'N/A') . ' | ' . $formatNumber($row['quantity_mt'] ?? 0, 2) . ' MT | ' . strtoupper((string) ($row['bag_type'] ?? 'N/A')));
    })->filter()->values();
@endphp

<style>
    .bol-print-sheet { background: #fff; color: #111; border: 1px solid #777; padding: 12px; font-size: 12px; font-family: inherit; }
    .bol-print-sheet table { width: 100%; border-collapse: collapse; }
    .bol-print-sheet td, .bol-print-sheet th { border: 1px solid #777; padding: 4px 6px; vertical-align: top; }
    .bol-wrap { overflow-wrap: anywhere; word-break: break-word; }
    .bol-center { text-align: center; }
    .bol-right { text-align: right; }
    .bol-no-border td, .bol-no-border th { border: none; padding: 0; }
    .bol-title { font-size: 20px; letter-spacing: 1px; font-weight: 700; }
    .bol-small { font-size: 11px; }
    .bol-smaller { font-size: 10px; }
    .bol-strong { font-weight: 700; }

    @media print {
        body * { visibility: hidden; }
        #bolPreviewContainer, #bolPreviewContainer * { visibility: visible; }
        #bolPreviewContainer { position: absolute; top: 0; left: 0; width: 100%; }
    }
</style>

<div class="bol-print-sheet">
    <table class="bol-no-border">
        <tr>
            <td style="width:30%;">
                <div class="bol-small">B/L NO</div>
                <div class="bol-strong">{{ $preview['bill_no'] ?? 'N/A' }}</div>
            </td>
            <td class="bol-center" style="width:40%;">
                <div class="bol-title">BILL OF LADING</div>
                <div class="bol-small">TO BE USED WITH CHARTER PARTIES</div>
            </td>
            <td style="width:30%;" class="bol-right">
                <div class="bol-small">B/L DATE</div>
                <div class="bol-strong">{{ $formatDate($preview['bill_date'] ?? null) }}</div>
            </td>
        </tr>
    </table>

    <table>
        <tr>
            <td style="width:50%;">
                <div class="bol-smaller bol-strong">SHIPPER</div>
                <div>{!! nl2br(e($shipperBlock ?: 'N/A')) !!}</div>
            </td>
            <td style="width:50%;">
                <div class="bol-smaller bol-strong">CONSIGNEE</div>
                <div>{!! nl2br(e($consigneeBlock ?: 'N/A')) !!}</div>
            </td>
        </tr>
        <tr>
            <td>
                <div class="bol-smaller bol-strong">NOTIFY PARTY</div>
                <div>{!! nl2br(e($notifyBlock ?: 'N/A')) !!}</div>
            </td>
            <td>
                <div class="bol-smaller bol-strong">CARRIER NAME</div>
                <div>{{ $preview['carrier_name'] ?? 'N/A' }}</div>
            </td>
        </tr>
    </table>

    <table>
        <tr>
            <td style="width:34%;">
                <div class="bol-smaller bol-strong">Vessel</div>
                <div>{{ $preview['vessel_name'] ?? 'N/A' }}</div>
            </td>
            <td style="width:33%;">
                <div class="bol-smaller bol-strong">Port of Loading</div>
                <div>{{ $preview['port_of_loading'] ?? 'N/A' }}</div>
            </td>
            <td style="width:33%;">
                <div class="bol-smaller bol-strong">Port of Discharge</div>
                <div>{{ $preview['port_of_discharge'] ?? 'N/A' }}</div>
            </td>
        </tr>
    </table>

    <table>
        <thead>
            <tr>
                <th style="width:8%;"></th>
                <th style="width:62%;">Description of Goods</th>
                <th style="width:15%;">Net Weight (MT)</th>
                <th style="width:15%;">Gross Weight (MT)</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td>{{ $row['row_label'] ?? '-' }}</td>
                    <td>
                        <div class="bol-strong">{{ $formatNumber($row['quantity_mt'] ?? 0, 2) }} MT {{ $row['product_name'] ?? '' }}</div>
                        <div>PACKING: {{ $row['packing_text'] . ' KG' ?? 'N/A' }}</div>
                        <div>BAG TYPE: {{ strtoupper((string) ($row['bag_type'] ?? 'N/A')) }}</div>
                        <div>NO. OF BAGS: {{ number_format((float) ($row['no_of_bags'] ?? 0)) }}</div>
                    </td>
                    <td>{{ $formatNumber($row['quantity_mt'] ?? 0, 2) }}</td>
                    <td>{{ $formatNumber((($row['gross_weight_kg'] ?? 0) / 1000), 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="bol-center">No packing data available.</td>
                </tr>
            @endforelse
            <tr>
                <td colspan="2" class="bol-right bol-strong">Total</td>
                <td class="bol-strong">{{ $formatNumber($totals['quantity_mt'] ?? 0, 2) }}</td>
                <td class="bol-strong">{{ $formatNumber((($totals['gross_weight_kg'] ?? 0) / 1000), 2) }}</td>
            </tr>
        </tbody>
    </table>

    <table>
        <tr>
            <td style="width:50%;">
                <div class="bol-smaller bol-strong">BAG MARKINGS</div>
                @forelse ($bagMarkings as $bagMarking)
                    <div>{{ $bagMarking . ' KG' }}</div>
                @empty
                    <div>N/A</div>
                @endforelse
            </td>
            <td style="width:50%;">
                <div class="bol-smaller bol-strong">NUMBER OF BAGS</div>
                @forelse ($numberOfBagsLines as $line)
                    <div>{{ $line }}</div>
                @empty
                    <div>N/A</div>
                @endforelse
            </td>
        </tr>
    </table>

    <table>
        <tr>
            <td style="width:50%;">
                <div>Form-E: {{ $preview['form_e_no'] ?? 'N/A' }}</div>
                <div>Form-E Date: {{ $preview['form_e_date'] ?? 'N/A' }}</div>
                <div>Delivery Challan: {{ $preview['delivery_challan_no'] ?? 'N/A' }}</div>
                <div>Delivery Order: {{ $preview['delivery_order_no'] ?? 'N/A' }}</div>
            </td>
            <td style="width:50%;">
                <div>Shipped On Board: {{ $formatDate($preview['shipped_on_board_date'] ?? null) }}</div>
                <div>Place of Issue: {{ $preview['place_of_issue'] ?? 'N/A' }}</div>
                <div>Freight: PREPAID</div>
            </td>
        </tr>
    </table>

    <table>
        <tr>
            <td style="width:50%;">
                <div class="bol-smaller bol-strong">CHARTER PARTY DATED</div>
                <div>{{ $preview['charter_party_dated'] ?? 'N/A' }}</div>
            </td>
            <td style="width:50%;">
                <div class="bol-smaller bol-strong">CAUTIONS</div>
                <div class="bol-wrap">{!! $preview['cautions_text'] ?? 'N/A' !!}</div>
            </td>
        </tr>
    </table>

    <table>
        <tr style="height: 60px !important;">
            <td style="width:50%; text-align: center; vertical-align: middle;">
                <div class="bol-smaller bol-strong">SIGNATURE</div>
            </td>
            <td style="width:50%;"></td>
        </tr>
    </table>
</div>
