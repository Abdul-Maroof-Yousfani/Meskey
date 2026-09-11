<x-sticky-table :items="$tickets" :leftSticky="2" :rightSticky="1" :emptyMessage="'No records found'" :pagination="false">
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
        <th>No of Bags (Loaded)</th>
        <th>Loaded Weight (KG)</th>
        <th>Truck #</th>
        <th>Amanat</th>
        @foreach ($product_slab_types as $slab)
            <th>Avg. {{ $slab->name }} </th>
        @endforeach
        {{-- <th>Avg. Broken</th>
        <th>Avg. Moisture</th>
        <th>Avg. Paddy</th>
        <th>Avg. Damage</th> --}}
        @foreach ($arrival_compulsory_qc_params as $compulsory_slab_type)
            <th>{{ $compulsory_slab_type->name }}</th>
        @endforeach
        {{-- <th>QC Advice</th>
        <th>QC Remarks</th>
        <th>Unloading Instruction</th> --}}
        <th>QC Analysis By</th>
        <th>Total Inner Samples</th>
        <th>Commodity</th>
        <th>Sauda Terms</th>
        <th>Status</th>
        <th>Tabaar Instructions</th>
        <th>Warehouse</th>
        <th>Galaa #</th>
        <th>Location Type</th>
        <th>1st Weight</th>
        <th>1st Weight Time</th>
        <th>2nd Weight</th>
        <th>2nd Weight Time</th>
        <th>Freight Charges</th>
        <th>Labor Charges</th>
        <th>Unpaid Labor Charges</th>
        <th>Other Charges (-)</th>
        <th>Kanta Charges</th>
        {{-- <th>Weighbridge Charges</th> --}}
        <th>Full Reject</th>
        <th>Full Reject By</th>
        <th>Full Reject Time</th>
        <th>Full Reject Comments</th>
        <th>Half Reject</th>
        <th>Half Reject By</th>
        <th>Half Reject Time</th>
        <th>Half Reject Comments</th>
        <th>Confirm Unloading</th>
        <th>Confirm Unloading By</th>
        <th>Confirm Unloading Time</th>
        <th>Confirm Unloading Comments</th>
        <th>Bag Packing</th>
        <th>Bag Type</th>
        <th>Filling Bags</th>
        <th>Total Bags</th>
        <th>Completion</th>

        <th>Final QC Report</th>
        <th>Bilty</th>
        <th>Loading Weight</th>
        <th>Arrival Slip</th>
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
                <td>{{ $row->bags }}</td>
                <td>{{ $row->net_weight }}</td>
                <td>{{ $row->truck_no }}</td>
                <td>{{ $row->approvals?->amanat ?? 'No' }}</td>
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
                {{-- <td>{{ $avgBroken }}</td>
                <td>{{ $avgMoisture }}</td>
                <td>{{ $avgPaddy }}</td>
                <td>{{ $avgDamage }}</td> --}}
                @foreach ($arrival_compulsory_qc_params as $compulsory_slab_type)
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
                @endforeach
                {{-- <td>{{ $row->approvals?->qc_advice ?? ($row->initialSampling?->approved_status ?? ($row->first_qc_status ?? 'N/A')) }}</td>
                <td>{{ $row->initialSampling?->approved_remarks ?? ($row->remarks ?? 'N/A') }}</td>
                <td>{{ $row->unloading_instruction ?? ($row->unloadingLocation?->remark ?? 'N/A') }}</td> --}}
                <td>{{ $row->initialSampling?->takenByUser?->name ?? ($row->innerSampling?->takenByUser?->name ?? 'N/A') }}</td>
                <td>{{ $innerSampleCount }}</td>
                <td>{{ $row->qcProduct?->name ?? ($row->product?->name ?? 'N/A') }}</td>
                <td>{{ $row->saudaType?->name ?? 'N/A' }}</td>
                <td>{{ $row->status ?? 'N/A' }}</td>
                <td>{{ $row->latestPurchaseSamplingRequest?->remark ?? 'N/A' }}</td>
                <td>{{ $row->unloadingLocation?->arrivalLocation?->warehouse?->name ?? ($row->approvals?->gala?->arrivalLocation?->name ?? 'N/A') }}</td>
                <td>{{ $row->unloadingLocation?->arrivalLocation?->gala_name ?? ($row->approvals?->gala?->name ?? ($row->approvals?->gala_name ?? 'N/A')) }}</td>
                <td>{{ $row->unloadingLocation?->location_type ?? ($row->approvals?->locationType?->name ?? 'N/A') }}</td>
                <td>{{ $row->firstWeighbridge?->weight ?? 0 }}</td>
                <td>{{ formatDateTime($row->firstWeighbridge?->created_at, 'd M Y h:i:s A', 'N/A') }}</td>
                <td>{{ $row->secondWeighbridge?->weight ?? 0 }}</td>
                <td>{{ formatDateTime($row->secondWeighbridge?->created_at, 'd M Y h:i:s A', 'N/A') }}</td>
                <td>{{ $row->freight?->gross_freight_amount ?? 'N/A' }}</td>
                <td>{{ $row->freight?->labor_amount ?? 'N/A' }}</td>
                <td>{{ $row->freight?->unpaid_labor_charges ?? 'N/A' }}</td>
                <td>{{ $row->freight?->other_labour_charges ?? 'N/A' }}</td>
                <td>{{ $row->freight?->karachi_kanta_charges ?? 'N/A' }}</td>
                {{-- <td>{{ $row->freight?->karachi_kanta_charges ?? 'N/A' }}</td> --}}

                @php
                    $isHalfReject = ($row->approvals?->bag_packing_approval == 'Half Approved' || ($row->approvals?->total_rejection > 0) || $row->document_approval_status == 'half_approved');
                    $isFullReject = ($row->first_qc_status == 'rejected' || $row->status == 'Reject Full');
                @endphp
                <td>{{ $isFullReject ? 'Yes' : 'No' }}</td>
                <td>{{ $isFullReject ? ($row->initialSampling?->takenByUser?->name ?? ($row->decisionBy?->name ?? 'N/A')) : 'N/A' }}</td>
                <td>{{ $isFullReject ? formatDateTime($row->initialSampling?->created_at, 'd M Y h:i:s A', 'N/A') : 'N/A' }}</td>
                <td>{{ $isFullReject ? ($row->initialSampling?->approved_remarks ?? ($row->remarks ?? 'N/A')) : 'N/A' }}</td>
                <td>{{ $isHalfReject ? 'Yes' : 'No' }}</td>
                <td>{{ $isHalfReject ? ($row->approvals?->creator?->name ?? 'N/A') : 'N/A' }}</td>
                <td>{{ $isHalfReject ? formatDateTime($row->approvals?->created_at, 'd M Y h:i:s A', 'N/A') : 'N/A' }}</td>
                <td>{{ $isHalfReject ? ($row->approvals?->remark ?? 'N/A') : 'N/A' }}</td>
                
                <td>{{ $row->approvals ? 'Yes' : 'No' }}</td>
                <td>{{ $row->approvals?->creator?->name ?? 'N/A' }}</td>
                <td>{{ formatDateTime($row->approvals?->created_at, 'd M Y h:i:s A', 'N/A') }}</td>
                <td>{{ $row->approvals?->remark ?? 'N/A' }}</td>
                
                <td>{{ $row->approvals?->bagPacking?->name ?? 'N/A' }}</td>
                <td>{{ $row->approvals?->bagType?->name ?? 'N/A' }}</td>
                <td>{{ $row->approvals?->filling_bags_no ?? 'N/A' }}</td>
                <td>{{ $row->arrivalSlip?->total_bags ?? ($row->approvals?->total_bags ?? 'N/A') }}</td>
                <td>{{ $row->freight_status == 'completed' || $row->status == 'completed' ? 'Yes' : 'No' }}</td>

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
