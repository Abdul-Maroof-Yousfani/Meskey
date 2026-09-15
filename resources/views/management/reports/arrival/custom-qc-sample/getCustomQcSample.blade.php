<x-sticky-table :items="$tickets" :leftSticky="2" :rightSticky="1" :emptyMessage="'No records found'" :pagination="false">
    @slot('head')
        <th>Ticket #</th>
        <th>Date</th>
        <th>Submit By</th>
        <th>Supplier</th>
        <th>Total Bags</th>
        <th>Qty</th>
        <th>Party Ref. No</th>
        <th>Received From</th>
        <th>Analysis By</th>
        <th>Commodity</th>

        @foreach ($arrival_compulsory_qc_params as $compulsory_param)
            <th>{{ $compulsory_param->name }}</th>
        @endforeach

        @foreach ($product_slab_types as $slab)
            <th>{{ $slab->name }}</th>
        @endforeach

        <th>QC Remarks</th>
        <th>Image</th>
    @endslot

    @slot('body')
        @foreach ($tickets as $row)
            @php
                $sampling = $row->lastInitialSampling ?? ($row->purchaseSamplingRequests->first() ?? null);
                $freight = $row->purchaseFreight;

                // Slab Deductions by slab ID
                $deductionValueSlabinitial = [];
                if ($sampling && $sampling->slabResults) {
                    foreach ($sampling->slabResults as $result) {
                        if ($result->slabType) {
                            $deductionValueSlabinitial[$result->slabType->id] = [
                                'checklist_value' => $result->checklist_value,
                                'name' => $result->slabType->name,
                                'deduction' => $result->applied_deduction,
                                'symbol' => $result->slabType->qc_symbol ?? '',
                            ];
                        }
                    }
                }

                // Compulsory Deductions by ID and normalized name
                $compulsoryDeductionValueSlab = [];
                $compulsoryByName = [];
                if ($sampling && $sampling->compulsoryResults) {
                    foreach ($sampling->compulsoryResults as $result) {
                        if ($result->qcParam) {
                            $compulsoryDeductionValueSlab[$result->qcParam->id] = [
                                'checklist_value' => $result->compulsory_checklist_value,
                                'name' => $result->qcParam->name,
                            ];
                            $normName = strtolower(trim($result->qcParam->name));
                            $compulsoryByName[$normName] = $result->compulsory_checklist_value;
                        }
                    }
                }

                $getCompVal = function(array $aliases) use ($compulsoryByName) {
                    foreach ($aliases as $alias) {
                        $key = strtolower(trim($alias));
                        if (isset($compulsoryByName[$key]) && $compulsoryByName[$key] !== null && $compulsoryByName[$key] !== '') {
                            return $compulsoryByName[$key];
                        }
                    }
                    // Fuzzy match check
                    foreach ($aliases as $alias) {
                        $key = strtolower(trim($alias));
                        foreach ($compulsoryByName as $paramKey => $paramVal) {
                            if ((str_contains($paramKey, $key) || str_contains($key, $paramKey)) && $paramVal !== null && $paramVal !== '') {
                                return $paramVal;
                            }
                        }
                    }
                    return '-';
                };

                // Image array
                $images = [];
                if ($freight?->bilty_slip) {
                    $images[] = asset($freight->bilty_slip);
                }
                if ($freight?->weighbridge_slip) {
                    $images[] = asset($freight->weighbridge_slip);
                }
                if ($freight?->supplier_bill) {
                    $images[] = asset($freight->supplier_bill);
                }

                $qcRemarks = $sampling?->remark ?? ($sampling?->approved_remarks ?? ($getCompVal(['qc remarks', 'remarks']) !== '-' ? $getCompVal(['qc remarks', 'remarks']) : '-'));
                $totalBags = $freight?->no_of_bags ?? ($row->purchaseOrder?->max_bags ?? ($row->purchaseOrder?->min_bags ?? '-'));
                $qty = $freight?->loading_weight ? number_format($freight->loading_weight, 2) : ($row->purchaseOrder?->total_quantity ? number_format($row->purchaseOrder->total_quantity, 2) : ($row->purchaseOrder?->max_quantity ? number_format($row->purchaseOrder->max_quantity, 2) : '-'));
                $supplierName = $row->purchaseOrder?->supplier_name ?? ($row->purchaseOrder?->supplier?->name ?? ($sampling?->supplier_name ?? 'N/A'));
                $receivedFrom = $freight?->station_name ?? ($freight?->station?->name ?? ($sampling?->address ?? ($row->purchaseOrder?->location?->name ?? '-')));
                $commodity = $row->qcProduct?->name ?? ($row->product?->name ?? ($sampling?->product?->name ?? ($row->purchaseOrder?->product?->name ?? 'N/A')));
                $partyRefNo = $sampling?->party_ref_no ?? ($row->purchaseOrder?->ref_no ?? '-');
            @endphp
            <tr>
                <td>#{{ $row->unique_no ?? 'N/A' }}</td>
                <td>{{ formatDate($row->created_at) }}</td>
                <td>{{ $sampling?->takenByUser?->name ?? 'N/A' }}</td>
                <td>{{ $supplierName }}</td>
                <td>{{ $totalBags }}</td>
                <td>{{ $qty }}</td>
                <td>{{ $partyRefNo }}</td>
                <td>{{ $receivedFrom }}</td>
                <td>{{ $sampling?->doneByUser?->name ?? ($sampling?->approvedByUser?->name ?? 'N/A') }}</td>
                <td>{{ $commodity }}</td>

                <!-- COMPULSORY QC PARAMETERS -->
                @foreach ($arrival_compulsory_qc_params as $compulsory_param)
                    @php
                        $compulsoryValue = $compulsoryDeductionValueSlab[$compulsory_param->id]['checklist_value'] ?? null;
                    @endphp
                    <td>
                        @if ($compulsoryValue !== null && $compulsoryValue !== '')
                            {{ $compulsoryValue }}
                        @else
                            {{ $compulsory_param->default_options ?? 'N/A' }}
                        @endif
                    </td>
                @endforeach

                <!-- DYNAMIC SLABS -->
                @foreach ($product_slab_types as $slab)
                    @php
                        $initialValue = $deductionValueSlabinitial[$slab->id]['checklist_value'] ?? 0;
                        $slabSymbol = $slab->qc_symbol ?? '';
                    @endphp
                    <td>
                        @if ($initialValue !== null && $initialValue !== '' && $initialValue != 0)
                            {{ $initialValue }}{{ $slabSymbol }}
                        @else
                            0
                        @endif
                    </td>
                @endforeach

                <td>{{ $qcRemarks }}</td>
                <td>
                    @if (count($images) > 0)
                        <button class="info p-1 text-center btn"
                            onclick="openImageModal({{ json_encode($images) }}, 'Ticket: #{{ $row->unique_no }}')">
                            <i class="ft-eye font-medium-3"></i>
                        </button>
                    @else
                        <button class="btn p-1 text-center text-muted" disabled>
                            <i class="ft-eye font-medium-3"></i>
                        </button>
                    @endif
                </td>
            </tr>
        @endforeach
    @endslot
</x-sticky-table>
