<x-sticky-table :items="$tickets" :leftSticky="3" :rightSticky="1" :emptyMessage="'No records found'" :pagination="false">
    @slot('head')
        <th>Ticket #</th>
        <th>Entry Date</th>
        <th>QC Date</th>
        <th>Analys Time</th>
        <th>Total Inner Samples</th>
        <th>Taken By</th>
        <th>Analysis By</th>
        <th>QC Advice</th>
        <th>QC Remarks</th>
        <th>Unloading Instructions</th>
        <th>Commodity</th>

        @foreach ($arrival_compulsory_qc_params as $compulsory_param)
            <th>{{ $compulsory_param->name }}</th>
        @endforeach

        @foreach ($product_slab_types as $slab)
            <th>{{ $slab->name }}</th>
        @endforeach

        <th>QC Report</th>
        <th>Bilty</th>
        <th>Loading Weight</th>
        <th>Arrival Slip</th>
    @endslot

    @slot('body')
        @foreach ($tickets as $row)
            @php
                $sampling = $row->lastInitialSampling;
                $innerSampleCount = $row->arrivalSamplingRequests ? $row->arrivalSamplingRequests->where('sampling_type', 'inner')->count() : 0;

                // Slab Deductions
                $deductionValueSlabinitial = [];
                if ($sampling && isset($sampling->slabResults)) {
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

                // Compulsory Deductions
                $compulsoryDeductionValueSlab = [];
                if ($sampling && isset($sampling->compulsoryResults)) {
                    foreach ($sampling->compulsoryResults as $result) {
                        if ($result->qcParam) {
                            $compulsoryDeductionValueSlab[$result->qcParam->id] = [
                                'checklist_value' => $result->compulsory_checklist_value,
                                'name' => $result->qcParam->name,
                            ];
                        }
                    }
                }

                $qcAdvice = $sampling?->decision_making ?? '';
                if (empty($qcAdvice)) {
                    if ($row->first_qc_status == 'rejected' || $sampling?->approved_status == 'rejected') {
                        $qcAdvice = 'Rejected';
                    } elseif ($sampling?->approved_status == 'approved') {
                        $qcAdvice = 'Approved';
                    }
                }

                $qcRemarks = $sampling?->remark ?? ($sampling?->approved_remarks ?? '-');
                $unloadingInstructions = $row->unloadingLocation?->remarks ?? '-';
            @endphp
            <tr>
                <td>#{{ $row->unique_no ?? 'N/A' }}</td>
                <td>{{ formatDate($row->created_at) }}</td>
                <td>{{ formatDate($sampling?->created_at) }}</td>
                <td>{{ $sampling?->created_at ? formatTime($sampling->created_at) : 'N/A' }}</td>
                <td>{{ $innerSampleCount }}</td>
                <td>{{ $sampling?->takenByUser?->name ?? 'N/A' }}</td>
                <td>{{ $sampling?->doneByUser?->name ?? ($sampling?->approvedByUser?->name ?? 'N/A') }}</td>
                <td>
                    @if ($qcAdvice == 'Rejected' || str_contains(strtolower($qcAdvice), 'reject'))
                        <span class="badge bg-danger">{{ $qcAdvice }}</span>
                    @elseif ($qcAdvice == 'Approved' || str_contains(strtolower($qcAdvice), 'approv'))
                        <span class="badge bg-success">{{ $qcAdvice }}</span>
                    @else
                        {{ $qcAdvice ?: '-' }}
                    @endif
                </td>
                <td>{{ $qcRemarks ?: '-' }}</td>
                <td>{{ $unloadingInstructions ?: '-' }}</td>
                <td>{{ $row->qcProduct?->name ?? ($row->product?->name ?? 'N/A') }}</td>

                <!-- COMPULSORY QC DEDUCTIONS -->
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

                <!-- SLAB DEDUCTIONS -->
                @foreach ($product_slab_types as $slab)
                    @php
                        $initialValue = $deductionValueSlabinitial[$slab->id]['checklist_value'] ?? 0;
                        $slabSymbol = $slab->qc_symbol ?? '';
                    @endphp
                    <td>
                        @if ($initialValue != 0)
                            {{ $initialValue }}{{ $slabSymbol }}
                        @else
                            0
                        @endif
                    </td>
                @endforeach
                <!-- Action Buttons -->
                <td>
                    <button class="info p-1 text-center mr-2 position-relative btn"
                        onclick="openModal(this,'{{ route('ticket.show', ['ticket' => $row->id, 'source' => 'contract']) }}','Ticket: {{ $row->unique_no }}', true, '90%')">
                        <a href="#"><i class="ft-eye font-medium-3"></i></a>
                    </button>
                </td>
                <td>
                    <button class="info p-1 text-center mr-2 position-relative btn" @disabled(
                        !$row->freight ||
                        !$row->freight?->bilty_document
                    )
                        @if ($row->freight && $row->freight?->bilty_document) onclick="openImageModal(['{{
                        asset($row->freight->bilty_document) }}'], 'Ticket: {{ $row->unique_no }}')" @endif>
                        <a href="#"><i class="ft-eye font-medium-3"></i></a>
                    </button>
                </td>
                <td>
                    <button class="info p-1 text-center mr-2 position-relative btn" @disabled(
                        !$row->freight ||
                        !$row->freight?->loading_weight_document
                    )
                        @if ($row->freight && $row->freight?->loading_weight_document) onclick="openImageModal(['{{
                        asset($row->freight->loading_weight_document) }}'], 'Ticket: {{ $row->unique_no }}')" @endif>
                        <a href="#"><i class="ft-eye font-medium-3"></i></a>
                    </button>
                </td>
                <td>
                    <button class="info p-1 text-center mr-2 position-relative btn" @disabled(!$row->arrivalSlip)
                        @if ($row->arrivalSlip) onclick="openModal(this,'{{ route('arrival-slip.edit', $row->arrivalSlip->id)
                        }}','Ticket: {{ $row->unique_no }}', true, '100%')" @endif>
                        <a href="#"><i class="ft-eye font-medium-3"></i></a>
                    </button>
                </td>
            </tr>
        @endforeach
    @endslot
</x-sticky-table>
