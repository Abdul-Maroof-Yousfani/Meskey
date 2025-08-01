<x-sticky-table :items="$tickets" :leftSticky="2" :rightSticky="1" :emptyMessage="'No tickets found'" :pagination="$tickets->links()">
    @slot('head')
        <th>Ticket #</th>
        <th>GRN #</th>
        <th>Miller</th>
        <th>Broker</th>
        <th>A/c Of</th>
        <th>Commodity</th>
        <th>Truck #</th>
        <th>Status</th>
        <th>Station</th>
        <th>Tabaar Remarks</th>
        <th>Loaded Weight</th>
        <th>Arrived Weight</th>
        <th>Contract</th>
        <th>Final QC Report</th>
        <th>Bilty</th>
        <th>Loading Weight</th>
        <th>Arrival Slip</th>
        <th>Action</th>
    @endslot

    @slot('body')
        @foreach ($tickets as $row)
            @php
                $tabaar = formatDeductionsAsString(getTicketDeductions($row));
                $tabaar = $tabaar == '' ? 'N/A' : $tabaar;
            @endphp
            <tr
                class="{{ !$row->purchaseOrder || ($row->purchaseOrder->status ?? '') == 'draft' ? ' bg-orange ' : '' }} {{ $row->first_qc_status == 'rejected' ? ' bg-red ' : '' }}">
                <td>
                    #{{ $row->unique_no ?? 'N/A' }}
                    @if ($row->first_qc_status == 'rejected')
                        <span class="badge bg-danger ml-1">Rejected</span>
                    @elseif (is_null($row->arrival_purchase_order_id))
                        <span class="badge bg-warning ml-1">Pending</span>
                    @elseif ($row->arrival_purchase_order_id && $row->is_ticket_verified == 0)
                        <span class="badge bg-warning ml-1">Not Verified</span>
                    @elseif ($row->is_ticket_verified == 1)
                        <span class="badge bg-success ml-1">Verified</span>
                    @endif
                </td>
                <td>{{ $row->grn_unique_no ?? 'N/A' }}</td>
                <td>{{ $row->miller->name ?? 'N/A' }}</td>
                <td>{{ $row->broker_name ?? ($row->purchaseOrder->broker_one_name ?? 'N/A') }}</td>
                <td>{{ $row->accounts_of_name ?? 'N/A' }}</td>
                <td>{{ $row->qcProduct->name ?? ($row->product->name ?? 'N/A') }}</td>
                <td>{{ $row->truck_no ?? ($row->purchaseOrder->truck_no ?? 'N/A') }}</td>
                <td>
                    @php
                        $status = 'RF';
                        if (isset($row->saudaType->id)) {
                            if ($row->saudaType->id == 1) {
                                if ($row->document_approval_status == 'fully_approved') {
                                    $status = 'OK';
                                } elseif ($row->document_approval_status == 'half_approved') {
                                    $status = 'P-RH';
                                } else {
                                    $status = 'RF';
                                }
                            } elseif ($row->saudaType->id == 2) {
                                if ($row->document_approval_status == 'fully_approved') {
                                    $status = 'TS';
                                } elseif ($row->document_approval_status == 'half_approved') {
                                    $status = 'TS-RH';
                                } else {
                                    $status = 'RF';
                                }
                            } else {
                                $status = 'RF';
                            }
                        } else {
                            $status = 'RF';
                        }
                    @endphp
                    @if ($row->first_qc_status == 'rejected')
                        <span class="badge bg-danger">RH</span>
                    @else
                        @if ($status == 'OK')
                            <span class="badge bg-success">OK</span>
                        @elseif ($status == 'P-RH')
                            <span class="badge bg-warning">P-RH</span>
                        @elseif ($status == 'TS')
                            <span class="badge bg-primary">TS</span>
                        @elseif ($status == 'TS-RH')
                            <span class="badge bg-warning">TS-RH</span>
                        @else
                            <span class="badge bg-info">RF</span>
                        @endif
                    @endif
                </td>
                <td>{{ $row->station->name ?? 'N/A' }}</td>
                <td>{{ $tabaar }}</td>
                <td>{{ $row->net_weight ?? 'N/A' }}</td>
                <td>{{ $row->arrived_net_weight ?? 0 }}</td>
                <td>{{ $row->purchaseOrder->contract_no ?? 'N/A' }}</td>
                <td>
                    <button class="info p-1 text-center mr-2 position-relative btn"
                        onclick="openModal(this,'{{ route('ticket.show', ['ticket' => $row->id, 'source' => 'contract']) }}','Ticket: {{ $row->unique_no }}', true, '90%')">
                        <a href="#">
                            <i class="ft-eye font-medium-3"></i>
                        </a>
                    </button>
                </td>
                <td>
                    <button class="info p-1 text-center mr-2 position-relative btn" @disabled(!$row->freight->bilty_document)
                        onclick="openImageModal(['{{ $row->freight->bilty_document ? asset($row->freight->bilty_document) : '' }}'], 'Ticket: {{ $row->unique_no }}')">
                        <a href="#">
                            <i class="ft-eye font-medium-3"></i>
                        </a>
                    </button>
                </td>
                <td>
                    <button class="info p-1 text-center mr-2 position-relative btn" @disabled(!$row->freight->loading_weight_document)
                        onclick="openImageModal(['{{ $row->freight->loading_weight_document ? asset($row->freight->loading_weight_document) : '' }}'], 'Ticket: {{ $row->unique_no }}')">
                        <a href="#">
                            <i class="ft-eye font-medium-3"></i>
                        </a>
                    </button>
                </td>
                <td>
                    <button
                        onclick="openModal(this,'{{ route('arrival-slip.edit', $row->arrivalSlip->id) }}','Ticket: {{ $row->unique_no }}', true, '100%')"
                        class="info p-1 text-center mr-2 position-relative btn">
                        <a href="#">
                            <i class="ft-eye font-medium-3"></i>
                        </a>
                    </button>
                </td>
                <td>
                    @if (!$row->purchaseOrder || ($row->purchaseOrder->status ?? '') == 'draft')
                        <a href="{{ route('raw-material.ticket-contracts.create', ['ticket_id' => $row->id]) }}"
                            class="info p-1 text-center mr-2 position-relative">
                            <i class="ft-edit font-medium-3"></i>
                        </a>
                    @else
                        <a href="{{ route('raw-material.ticket-contracts.edit', $row->id) }}"
                            class="info p-1 text-center mr-2 position-relative">
                            <i class="ft-eye font-medium-3"></i>
                        </a>
                    @endif
                </td>
            </tr>
        @endforeach
    @endslot
</x-sticky-table>
