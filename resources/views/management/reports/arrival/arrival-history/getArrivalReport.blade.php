<x-sticky-table :items="$tickets" :leftSticky="2" :rightSticky="1" :emptyMessage="'No tickets found'" :pagination="$tickets->links()">
    @slot('head')
        <th>Ticket #</th>
        <th>Status</th>
       
        <th>Miller</th>
        <th>Broker</th>
        <th>A/c Of</th>
        <th>Truck #</th>
     
                <th>Commodity</th>

        <th>Party Ref. No</th>
        <th>Status</th>
        <th>Station</th>
        <th>Bilty #</th>
        <th>Loading Weight</th>
        <th>1st Weight</th>
        <th>2nd Weight</th>
        <th>Net Weight</th>
        <th>Wt. Diff.</th>
        {{-- <th>GRN #</th> --}}
        {{-- <th>Sauda Type</th>
        <th>Station</th> --}}
        <th>Bag Type</th>
        <th>Bag Condition</th>
        <th>Bag Packing</th>
        <th>No. Bag</th>
        @foreach (getTableData('product_slab_types') as $slab)
            <th>{{ $slab->name }}</th>
        @endforeach
        @foreach (getTableData('arrival_compulsory_qc_params') as $compulsory_slab_type)
            <th>{{ $compulsory_slab_type->name }}</th>
        @endforeach
        <th>Warehouse</th>
        <th>Gala</th>
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
                    {{-- @if (!$row->freight || !$row->arrivalSlip)
                        @continue
                    @endif --}}
                    @php
            $tabaar = formatDeductionsAsString(getTicketDeductions($row));
            $tabaar = $tabaar == '' ? 'N/A' : $tabaar;
           $deductionValueSlab = SlabTypeWisegetTicketDeductions($row)['deductions'] ?? [];
           $compulsoryDeductionValueSlab = SlabTypeWisegetTicketDeductions($row)['compulsory_deductions'] ?? [];
                    @endphp
                    {{-- @dd($deductionValueSlab); --}}
                    <tr
                        class="{{ is_null($row->arrival_purchase_order_id) || ($row->arrival_purchase_order_id && $row->is_ticket_verified == 0) ? ' bg-success ' : '' }} {{ $row->first_qc_status == 'rejected' ? ' bg-red ' : '' }}">
                        <td>
                            #{{ $row->unique_no ?? 'N/A' }}

                        </td>
                        <td>
                        @if ($row->first_qc_status == 'rejected')
                            <span class="badge bg-danger ml-1">Rejected</span>
                        @elseif($row->arrival_slip_status == 'generated')
                            <span class="badge bg-success ml-1">Completed</span>
                            @else
                            <span class="badge bg-warning ml-1">Pending</span>
                        @endif
                        </td>
                      
                        <td>{{ $row->miller->name ?? 'N/A' }}</td>
                        <td>{{ $row->broker_name ?? ($row->purchaseOrder->broker_one_name ?? 'N/A') }}</td>
                        <td>{{ $row->accounts_of_name ?? 'N/A' }}</td>
                         <td>{{ $row->truck_no ?? ($row->purchaseOrder->truck_no ?? 'N/A') }}</td>
                        <td>{{ $row->qcProduct->name ?? ($row->product->name ?? 'N/A') }}</td>
                        <td>N/A</td>
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
                                <span class="badge bg-danger">RF</span>
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
                        <td>{{ $row->station_name ?? 'N/A' }}</td>
                        <td>{{ $row->bilty_no ??  'N/A' }}</td>


                    <td>{{ $row->net_weight ?? 'N/A' }}</td>
                    <td>{{ $row->firstWeighbridge->weight ?? 0 }}</td>
                    <td>{{ $row->secondWeighbridge->weight ?? 0 }}</td>
                    <td>{{ $row->arrived_net_weight ?? 0 }}</td>
                    <td>{{ $row->arrived_net_weight - $row->net_weight ?? 0 }}</td>

                        {{-- <td>{{ $row->grn_unique_no ?? 'N/A' }}</td>
                        <td>
                            @if ($row->saudaType->name == 'Thadda')
                                <span class="badge bg-warning">Thadda</span>
                            @else
                                <span class="badge bg-success">Pohanch</span>
                            @endif
                        </td>
                        <td>{{ $row->station->name ?? 'N/A' }}</td> --}}
                        <td>{{ $row->approvals->bagType->name ?? 'N/A' }}</td>
                        <td>{{ $row->approvals->bagCondition->name ?? 'N/A' }}</td>
                        <td>{{ $row->approvals->bagPacking->name ?? 'N/A' }}</td>
                        <td>{{ $row->approvals->total_bags ?? 'N/A' }}</td>
                      
                        @foreach (getTableData('product_slab_types') as $slab)
                            <td data-slaptypename="{{ $deductionValueSlab[$slab->id]['name'] ?? 'N/A' }}">
                                @if(isset($deductionValueSlab[$slab->id]['deduction']))
                                    {{-- {{ $deductionValueSlab[$slab->id]['deduction'] }}
                                    {{ $deductionValueSlab[$slab->id]['unit'] ?? '' }} --}}
                                    {{ $deductionValueSlab[$slab->id]['checklist_value'] ?? '' }}{{$slab->qc_symbol ?? ''}}
                                @else
                                N/A
                                @endif
                                
                            </td>
                        @endforeach
                        @foreach (getTableData('arrival_compulsory_qc_params') as $compulsory_slab_type)
                           @php
    $options = json_decode($compulsory_slab_type->options, true);
@endphp


                            <td data-slaptypename="{{ $compulsoryDeductionValueSlab[$compulsory_slab_type->id]['name'] ?? 'N/A' }}">
                                @if(isset($compulsoryDeductionValueSlab[$compulsory_slab_type->id]['deduction']))
                                    {{-- {{ $compulsoryDeductionValueSlab[$compulsory_slab_type->id]['deduction'] }}
                                    {{ $compulsoryDeductionValueSlab[$compulsory_slab_type->id]['unit'] ?? '' }} --}}
                                    {{ $compulsoryDeductionValueSlab[$compulsory_slab_type->id]['checklist_value'] ?? '' }} 
                                @else
                                    {{ $compulsory_slab_type->default_options }}
                                @endif
                            </td>
                        @endforeach
                            
                
                        {{-- <td>{{ $tabaar }}</td> --}}
    
                        <td>Warehouse {{ $row->unloadingLocation->arrivalLocation->name ?? 'N/A' }}</td>
                        <td>{{ $row->approvals->gala_name ?? 'N/A' }}</td>
                        {{-- <td>{{ $row->purchaseOrder->contract_no ?? 'N/A' }}</td> --}}
                        <td>
                            <button class="info p-1 text-center mr-2 position-relative btn"
                                onclick="openModal(this,'{{ route('ticket.show', ['ticket' => $row->id, 'source' => 'contract']) }}','Ticket: {{ $row->unique_no }}', true, '90%')">
                                <a href="#">
                                    <i class="ft-eye font-medium-3"></i>
                                </a>
                            </button>
                        </td>
                        <td>
                            <button class="info p-1 text-center mr-2 position-relative btn" @disabled(!$row->freight || !$row->freight?->bilty_document)
                                @if (!$row->freight || !$row->freight?->bilty_document) disabled @endif
                                @if ($row->freight && $row->freight?->bilty_document) onclick="openImageModal(['{{ asset($row->freight->bilty_document) }}'], 'Ticket: {{ $row->unique_no }}')" @endif>
                                <a href="#">
                                    <i class="ft-eye font-medium-3"></i>
                                </a>
                            </button>
                        </td>
                        <td>
                            <button class="info p-1 text-center mr-2 position-relative btn" @disabled(!$row->freight || !$row->freight?->loading_weight_document)
                                @if (!$row->freight || !$row->freight?->loading_weight_document) disabled @endif
                                @if ($row->freight && $row->freight?->loading_weight_document) onclick="openImageModal(['{{ asset($row->freight->loading_weight_document) }}'], 'Ticket: {{ $row->unique_no }}')" @endif>
                                <a href="#">
                                    <i class="ft-eye font-medium-3"></i>
                                </a>
                            </button>
                        </td>
                        <td>
                            <button class="info p-1 text-center mr-2 position-relative btn" @disabled(!$row->arrivalSlip)
                                @if (!$row->arrivalSlip) disabled
                                @else
                                    onclick="openModal(this,'{{ route('arrival-slip.edit', $row->arrivalSlip->id) }}','Ticket: {{ $row->unique_no }}', true, '100%')" @endif>
                                <a href="#">
                                    <i class="ft-eye font-medium-3"></i>
                                </a>
                            </button>
                        </td>
                        {{-- <td>
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
                        </td> --}}
                    </tr>
        @endforeach
    @endslot
</x-sticky-table>
