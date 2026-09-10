<x-sticky-table :items="$tickets" :leftSticky="2" :rightSticky="1" :emptyMessage="'No records found'"
    :pagination="false">
    @slot('head')
    <th>Ticket #</th>
    <th>Entry Date</th>
    <th>Entry Time</th>
    <th>Entry By</th>
    <th>Broker</th>
    <th>Supplier</th>
    <th>Station</th>
    <th>Account of</th>
    <th>Decision</th>
    <th>Bilty #</th>
    <th>Truck Type</th>
    <th>Loading Date</th>

    @foreach ($product_slab_types as $slab)
        <th>Avg. {{ $slab->name }} </th>
    @endforeach


    <th>No of Bags (Loaded)</th>
    <th>Loaded Weight (KG)</th>
    <th>Arrived Net Weight (KG)</th>
    <th>Truck #</th>
    <th>QC Remarks</th>
    <th>Amanat</th>

    <!-- @foreach ($arrival_compulsory_qc_params as $compulsory_slab_type)
            <th>{{ $compulsory_slab_type->name }}</th>
        @endforeach -->
    {{-- <th>QC Advice</th>
    <th>QC Remarks</th>
    <th>Unloading Instruction</th> --}}
    <!-- <th>QC Analysis By</th>
        <th>Total Inner Samples</th>
        <th>Commodity</th>
        <th>Sauda Terms</th>
        <th>Status</th> -->

    @endslot

    @slot('body')
    @foreach ($tickets as $row)
        @php
            $sampling = $row->lastInitialSampling;
            $avgBroken = '';
            $avgMoisture = '';
            $avgPaddy = '';
            $avgDamage = '';
            if ($sampling && isset($sampling->slabResults)) {
                foreach ($sampling->slabResults as $slabRes) {
                    $name = strtolower($slabRes->slabType?->name ?? 'N/A');
                    if (str_contains($name, 'broken')) {
                        $avgBroken = $slabRes->checklist_value . ' %';
                    } elseif (str_contains($name, 'moisture')) {
                        $avgMoisture = $slabRes->checklist_value . ' %';
                    } elseif (str_contains($name, 'paddy')) {
                        $avgPaddy = $slabRes->checklist_value . ' %';
                    } elseif (str_contains($name, 'damage')) {
                        $avgDamage = $slabRes->checklist_value . ' %';
                    }
                }
            }
            $innerSampleCount = $row->arrivalSamplingRequests ? $row->arrivalSamplingRequests->where('sampling_type', 'inner')->count() : 0;

            // Use for foreach :)
            // ==========================================
            // 1. GET INITIAL SAMPLING
            // ==========================================
            $initialRequest = $row->lastInitialSampling;
            $deductionValueSlabinitial = [];

            if ($initialRequest) {
                foreach ($initialRequest->slabResults as $result) {
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

            // ==========================================
            // 2. COMPULSORY DEDUCTIONS
            // ==========================================
            $compulsoryDeductionValueSlab = [];
            if ($initialRequest) {
                foreach ($initialRequest->compulsoryResults as $result) {
                    if ($result->qcParam) {
                        $compulsoryDeductionValueSlab[$result->qcParam->id] = [
                            'checklist_value' => $result->compulsory_checklist_value,
                            'name' => $result->qcParam->name,
                        ];
                    }
                }
            }
        @endphp
        <tr>
            <td>#{{ $row->unique_no ?? 'N/A' }}</td>
            <td>{{ formatDate($row->created_at, 'd-M-Y', 'N/A') }}</td>
            <td>{{ formatTime($row->created_at, 'h:i:s A', 'N/A') }}</td>
            <td>{{ $row->creator?->name ?? 'Main Gate' }}</td>
            <td>{{ $row->broker_name ?? ($row->broker?->name ?? '< Not Available >') }}</td>
            <td>{{ $row->miller?->name ?? ($row->accountsOf?->name ?? '< Not Available >') }}</td>
            <td>{{ $row->station_name ?? ($row->station?->name ?? 'N/A') }}</td>
            <td>{{ $row->accounts_of_name ?? ($row->accountsOf?->name ?? '< Not Available >') }}</td>
            <td>{{ $row->decisionBy?->name ?? 'N/A' }}</td>
            <td>{{ $row->bilty_no }}</td>
            <td>{{ $row->truckType?->name ?? 'N/A' }}</td>
            <td>{{ formatDate($row->loading_date, 'd M Y', 'N/A') }}</td>

            @foreach ($product_slab_types as $slab)
                @php
                    $initialValue = $deductionValueSlabinitial[$slab->id]['checklist_value'] ?? 0;
                    $slabSymbol = $slab->qc_symbol ?? '';
                @endphp

                <!-- INITIAL Column -->
                <td>
                    @if($initialValue != 0)
                        {{ $initialValue }}{{ $slabSymbol }}
                    @else
                        0
                    @endif
                </td>
            @endforeach
            <td>{{ $row->bags }}</td>
            <td>{{ $row->net_weight }}</td>
            <td>{{ $row->arrived_net_weight }}</td>
            <td>{{ $row->truck_no }}</td>
            <td>{{ $initialRequest->remark ?? 'N/A' }}</td>
            <td>{{ $row->approvals?->amanat ?? 'No' }}</td>
            {{-- <td>{{ $avgBroken }}</td>
            <td>{{ $avgMoisture }}</td>
            <td>{{ $avgPaddy }}</td>
            <td>{{ $avgDamage }}</td> --}}
            <!-- @foreach ($arrival_compulsory_qc_params as $compulsory_slab_type)
                            <td>
                                @php
                                    $compulsoryValue = $compulsoryDeductionValueSlab[$compulsory_slab_type->id]['checklist_value'] ?? null;
                                @endphp
                                @if($compulsoryValue !== null)
                                    {{ $compulsoryValue }}
                                @else
                                    {{ $compulsory_slab_type->default_options }}
                                @endif
                            </td>
                        @endforeach -->





        </tr>
    @endforeach
    @endslot
</x-sticky-table>