<x-sticky-table :items="$tickets" :leftSticky="2" :rightSticky="1" :emptyMessage="'No tickets found'"
    :pagination="false">
    @slot('head')
    <th>Ticket #</th>
    <th>Status</th>

    <th>Miller</th>
    <th>Broker</th>
    <th>A/c Of</th>
    <th>decision Of</th>
    <th>Truck #</th>
    {{-- <th>Ticket Commodity</th> --}}
    <th>QC Commodity</th>
    <th>Location</th>
    <th>Party Ref.#</th>
    <th>Status</th>
    <th>Station</th>
    <th>Bilty #</th>
    <th>Loading Weight</th>
    <th>1st Weight</th>
    <th>2nd Weight</th>
    <th>Net Weight</th>
    <th>Wt. Diff.</th>
    <th>Avg. Weight.</th>
    {{-- <th>GRN #</th> --}}
    {{-- <th>Sauda Type</th>
    <th>Station</th> --}}
    <th>Bag Type</th>
    <th>Bag Condition</th>
    <th>Bag Packing</th>
    <th>No. Bag</th>
    <th>Warehouse</th>
    <th>Gala</th>
    <th>Tabaar Remarks</th>
    @foreach (getTableData('product_slab_types') as $slab)
        <th>{{ $slab->name }}</th>
        <th>Inner {{ $slab->name }} </th>
    @endforeach
    @foreach (getTableData('arrival_compulsory_qc_params') as $compulsory_slab_type)
        <th>{{ $compulsory_slab_type->name }}</th>
    @endforeach

    {{-- <th>Tabaar Remarks</th> --}}

    {{-- <th>Contract</th> --}}
    <th>Final QC Report</th>
    <th>Bilty</th>
    <th>Loading Weight</th>
    <th>Arrival Slip</th>
    {{-- <th>Action</th> --}}
    @endslot

    @slot('body')
    @foreach ($tickets as $row)


        @php
            // ==========================================
            // 1. GET INITIAL SAMPLING
            // ==========================================
            $initialRequest = $row->initialSampling;
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
            // 2. GET INNER SAMPLING
            // ==========================================
            $innerRequest = $row->innerSampling;
            $deductionValueSlabInner = [];

            if ($innerRequest) {
                foreach ($innerRequest->slabResults as $result) {
                    if ($result->slabType) {
                        $deductionValueSlabInner[$result->slabType->id] = [
                            'checklist_value' => $result->checklist_value,
                            'name' => $result->slabType->name,
                            'deduction' => $result->applied_deduction,
                            'symbol' => $result->slabType->qc_symbol ?? '',
                        ];
                    }
                }
            }

            // dd($deductionValueSlabInner, $deductionValueSlabinitial);
            // ==========================================
            // 3. COMPULSORY DEDUCTIONS
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

            // ==========================================
            // 4. TABAAR
            // ==========================================
            // $tabaar = 'N/A';
            // if ($initialRequest && $initialRequest->applied_deduction) {
            //     $tabaar = number_format($initialRequest->applied_deduction, 2) . '%';
            // }

                $tabaar = formatDeductionsAsString(getTicketDeductions($row));
    $tabaar = $tabaar == '' ? 'N/A' : $tabaar;

            // DEBUG - Check if inner exists
            // if($innerRequest) {
            // dd('Inner found for ticket: ' . $row->id, $deductionValueSlabInner);
            // }
        @endphp

        <tr>
            <!-- Ticket # -->
            <td>#{{ $row->unique_no ?? 'N/A' }}</td>

            <!-- Status -->
            <td>
                @if ($row->first_qc_status == 'rejected')
                    <span class="badge bg-danger ml-1">Rejected</span>
                @elseif($row->arrival_slip_status == 'generated')
                    <span class="badge bg-success ml-1">Completed</span>
                @else
                    <span class="badge bg-warning ml-1">Pending</span>
                @endif
            </td>

            <!-- Miller -->
            <td>{{ $row->miller->name ?? 'N/A' }}</td>

            <!-- Broker -->
            <td>{{ $row->broker_name ?? ($row->purchaseOrder->broker_one_name ?? 'N/A') }}</td>

            <!-- A/c Of -->
            <td>{{ $row->accounts_of_name ?? 'N/A' }}</td>

            <!-- decision Of -->
            <td>{{ $row->decisionBy->name ?? 'N/A' }}</td>

            <!-- Truck # -->
            <td>{{ $row->truck_no ?? ($row->purchaseOrder->truck_no ?? 'N/A') }}</td>

            <!-- QC Commodity -->
            <td>{{ $row->qcProduct->name ?? 'N/A' }}</td>

            <!-- Location -->
            <td>{{ $row->location->name ?? 'N/A' }}</td>

            <!-- Party Ref.# -->
            <td>N/A</td>

            <!-- Status -->
            <td>
                @php
                    $status = 'RF';
                    if (isset($row->saudaType->id)) {
                        if ($row->saudaType->id == 1) {
                            if ($row->document_approval_status == 'fully_approved')
                                $status = 'OK';
                            elseif ($row->document_approval_status == 'half_approved')
                                $status = 'P-RH';
                        } elseif ($row->saudaType->id == 2) {
                            if ($row->document_approval_status == 'fully_approved')
                                $status = 'TS';
                            elseif ($row->document_approval_status == 'half_approved')
                                $status = 'TS-RH';
                        }
                    }
                @endphp
                @if ($row->first_qc_status == 'rejected')
                    <span class="badge bg-danger">RF</span>
                @else
                    @if ($status == 'OK') <span class="badge bg-success">OK</span>
                    @elseif ($status == 'P-RH') <span class="badge bg-warning">P-RH</span>
                    @elseif ($status == 'TS') <span class="badge bg-primary">TS</span>
                    @elseif ($status == 'TS-RH') <span class="badge bg-warning">TS-RH</span>
                    @else <span class="badge bg-warning">In-Process</span>
                    @endif
                @endif
            </td>

            <!-- Station -->
            <td>{{ $row->station_name ?? 'N/A' }}</td>

            <!-- Bilty # -->
            <td>{{ $row->bilty_no ?? 'N/A' }}</td>

            <!-- Loading Weight -->
            <td>{{ $row->net_weight ?? 'N/A' }}</td>

            <!-- 1st Weight -->
            <td>{{ $row->firstWeighbridge->weight ?? 0 }}</td>

            <!-- 2nd Weight -->
            <td>{{ $row->secondWeighbridge->weight ?? 0 }}</td>

            <!-- Net Weight -->
            <td>{{ $row->arrived_net_weight ?? 0 }}</td>

            <!-- Wt. Diff. -->
            <td>{{ ($row->arrived_net_weight ?? 0) - ($row->net_weight ?? 0) }}</td>

            <!-- Avg. Weight -->
            <td>
                {{ $row->approvals?->total_bags
            ? number_format($row->arrived_net_weight / $row->approvals->total_bags, 2)
            : 'N/A'
                }}
            </td>

            <!-- Bag Type -->
            <td>{{ $row->approvals->bagType->name ?? 'N/A' }}</td>

            <!-- Bag Condition -->
            <td>{{ $row->approvals->bagCondition->name ?? 'N/A' }}</td>

            <!-- Bag Packing -->
            <td>{{ $row->approvals->bagPacking->name ?? 'N/A' }}</td>

            <!-- No. Bag -->
            <td>{{ $row->approvals->total_bags ?? 'N/A' }}</td>

            <!-- Warehouse -->
            <td>Warehouse {{ $row->unloadingLocation->arrivalLocation->name ?? 'N/A' }}</td>

            <!-- Gala -->
            <td>{{ $row->approvals->gala_name ?? 'N/A' }}</td>

            <!-- Tabaar Remarks -->
            <td>{{ $tabaar }}</td>

            <!-- ========================================== -->
            <!-- INITIAL & INNER SLAB DEDUCTIONS - SIDE BY SIDE -->
            <!-- ========================================== -->
            @foreach (getTableData('product_slab_types') as $slab)
                @php
                    $initialValue = $deductionValueSlabinitial[$slab->id]['checklist_value'] ?? 0;
                    $innerValue = $deductionValueSlabInner[$slab->id]['checklist_value'] ?? 0;
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

                <!-- INNER Column -->
                <td>
                    @if($innerValue != 0)
                        {{ $innerValue }}{{ $slabSymbol }}
                    @else
                        0
                    @endif
                </td>
            @endforeach

            <!-- ========================================== -->
            <!-- COMPULSORY QC DEDUCTIONS -->
            <!-- ========================================== -->
            @foreach (getTableData('arrival_compulsory_qc_params') as $compulsory_slab_type)
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