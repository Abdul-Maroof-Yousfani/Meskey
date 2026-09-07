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
        <th>Avg. Broken</th>
        <th>Avg. Moisture</th>
        <th>Avg. Paddy</th>
        <th>Avg. Damage</th>
        <th>QC Advice</th>
        <th>QC Remarks</th>
        <th>Unloading Instruction</th>
        <th>QC Analysis By</th>
        <th>Total Inner Samples</th>
        <th>Commodity</th>
        <th>Sauda Terms</th>
        <th>Status</th>
        <th>Tabaar Instructions</th>
        <th>Broken Level</th>
        <th>Moisture Level</th>
        <th>Paddy Level</th>
        <th>Damage Level</th>
        <th>Under Milled Level</th>
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
        <th>Weighbridge Charges</th>
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
                $sampling = $row->initialSampling ?? $row->innerSampling;
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
            @endphp
            <tr>
                <td>#{{ $row->unique_no ?? 'N/A' }}</td>
                <td>{{ $row->created_at ? $row->created_at->format('d-M-Y') : 'N/A' }}</td>
                <td>{{ $row->created_at ? $row->created_at->format('h:i:s A') : 'N/A' }}</td>
                <td>{{ $row->creator?->name ?? 'Main Gate' }}</td>
                <td>{{ $row->broker_name ?? ($row->broker?->name ?? '< Not Available >') }}</td>
                <td>{{ $row->miller?->name ?? ($row->accountsOf?->name ?? '< Not Available >') }}</td>
                <td>{{ $row->station_name ?? ($row->station?->name ?? 'N/A') }}</td>
                <td>{{ $row->accounts_of_name ?? ($row->accountsOf?->name ?? '< Not Available >') }}</td>
                <td>{{ $row->decisionBy?->name ?? 'N/A' }}</td>
                <td>{{ $row->bilty_no }}</td>
                <td>{{ $row->truckType?->name ?? 'N/A' }}</td>
                <td>{{ $row->loading_date ? \Carbon\Carbon::parse($row->loading_date)->format('d M Y') : 'N/A' }}</td>
                <td>{{ $row->bags }}</td>
                <td>{{ $row->loading_weight }}</td>
                <td>{{ $row->truck_no }}</td>
                <td>{{ $row->approvals?->amanat ?? 'No' }}</td>
                <td>{{ $avgBroken }}</td>
                <td>{{ $avgMoisture }}</td>
                <td>{{ $avgPaddy }}</td>
                <td>{{ $avgDamage }}</td>
                <td>{{ $row->approvals?->qc_advice ?? ($row->initialSampling?->approved_status ?? ($row->first_qc_status ?? 'N/A')) }}</td>
                <td>{{ $row->initialSampling?->approved_remarks ?? ($row->remarks ?? 'N/A') }}</td>
                <td>{{ $row->unloading_instruction ?? ($row->unloadingLocation?->remark ?? 'N/A') }}</td>
                <td>{{ $row->initialSampling?->takenByUser?->name ?? ($row->innerSampling?->takenByUser?->name ?? 'N/A') }}</td>
                <td>{{ $innerSampleCount }}</td>
                <td>{{ $row->qcProduct?->name ?? ($row->product?->name ?? 'N/A') }}</td>
                <td>{{ $row->saudaType?->name ?? 'N/A' }}</td>
                <td>{{ $row->status ?? 'N/A' }}</td>
                <td>{{ $row->tabaar_instructions ?? 'N/A' }}</td>
                <td>{{ $row->broken_level ?? 0 }}</td>
                <td>{{ $row->moisture_level ?? 0 }}</td>
                <td>{{ $row->paddy_level ?? 0 }}</td>
                <td>{{ $row->damage_level ?? 0 }}</td>
                <td>{{ $row->under_milled_level ?? 0 }}</td>
                <td>{{ $row->unloadingLocation?->arrivalLocation?->warehouse?->name ?? ($row->approvals?->gala?->arrivalLocation?->name ?? 'N/A') }}</td>
                <td>{{ $row->unloadingLocation?->arrivalLocation?->gala_name ?? ($row->approvals?->gala?->name ?? ($row->approvals?->gala_name ?? 'N/A')) }}</td>
                <td>{{ $row->unloadingLocation?->location_type ?? ($row->approvals?->locationType?->name ?? 'N/A') }}</td>
                <td>{{ $row->firstWeighbridge?->gross_weight ?? ($row->first_weight ?? 'N/A') }}</td>
                <td>{{ $row->firstWeighbridge?->created_at ? \Carbon\Carbon::parse($row->firstWeighbridge->created_at)->format('d M Y h:i:s A') : 'N/A' }}</td>
                <td>{{ $row->secondWeighbridge?->tare_weight ?? ($row->second_weight ?? 'N/A') }}</td>
                <td>{{ $row->secondWeighbridge?->created_at ? \Carbon\Carbon::parse($row->secondWeighbridge->created_at)->format('d M Y h:i:s A') : 'N/A' }}</td>
                <td>{{ $row->freight?->freight_amount ?? 'N/A' }}</td>
                <td>{{ $row->freight?->labor_amount ?? 'N/A' }}</td>
                <td>{{ $row->freight?->unpaid_labor_amount ?? 'N/A' }}</td>
                <td>{{ $row->freight?->other_charges ?? 'N/A' }}</td>
                <td>{{ $row->freight?->kanta_charges ?? 'N/A' }}</td>
                <td>{{ $row->freight?->weighbridge_charges ?? 'N/A' }}</td>
                <td>{{ $row->status == 'Reject Full' ? 'Yes' : 'No' }}</td>
                <td>{{ $row->full_reject_by ?? 'N/A' }}</td>
                <td>{{ $row->full_reject_time ?? 'N/A' }}</td>
                <td>{{ $row->full_reject_comments ?? 'N/A' }}</td>
                <td>{{ $row->status == 'Reject Half' ? 'Yes' : 'No' }}</td>
                <td>{{ $row->half_reject_by ?? 'N/A' }}</td>
                <td>{{ $row->half_reject_time ?? 'N/A' }}</td>
                <td>{{ $row->half_reject_comments ?? 'N/A' }}</td>
                <td>{{ $row->confirm_unloading ?? ($row->arrivalSlip ? 'Yes' : 'No') }}</td>
                <td>{{ $row->arrivalSlip?->createdBy?->name ?? 'N/A' }}</td>
                <td>{{ $row->arrivalSlip?->created_at ? \Carbon\Carbon::parse($row->arrivalSlip->created_at)->format('d M Y h:i:s A') : 'N/A' }}</td>
                <td>{{ $row->arrivalSlip?->remark ?? 'N/A' }}</td>
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
