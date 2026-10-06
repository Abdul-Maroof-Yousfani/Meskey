<table class="table table-hover m-0">
    <thead class="bg-light">
        <tr>
            <th width="12%">Dekh #</th>
            <th width="9%">Date</th>
            <th width="10%">Location</th>
            <th width="14%">Party Details</th>
            <th width="21%">Items & Weight</th>
            <th width="10%" class="text-center">Completion</th>
            <th width="10%" class="text-center">Approval</th>
            <th width="9%" class="text-center">Vehicle No</th>
            <th width="5%" class="text-center">Action</th>
        </tr>
    </thead>
    <tbody>
        @forelse($inspections as $row)
            @php
                $conf = $row->confirmation;
                $isCompleted = $conf && $conf->is_completed;
                $appStatus = $conf ? strtolower($conf->am_approval_status ?? '') : '';
                $vehicleNo = $conf?->vehicle_no ?? $row->vehicle_no;
            @endphp
            <tr>
                <td class="align-middle font-weight-bold" style="background-color: #f0f7ff;">
                    <span class="text-primary">#{{ $row->inspection_no }}</span>
                    @if($row->salesInquiries && $row->salesInquiries->count() > 0)
                        <br>
                        <small class="badge badge-info p-1 mt-1" title="Linked Sales Inquiry">
                            <i class="ft-file-text"></i> {{ $row->salesInquiries->first()->inquiry_no }}
                        </small>
                    @endif
                    @if($row->salesOrders && $row->salesOrders->count() > 0)
                        <br>
                        <small class="badge badge-success p-1 mt-1" title="Linked Sales Order">
                            <i class="ft-shopping-cart"></i> {{ $row->salesOrders->first()->reference_no }}
                        </small>
                    @endif
                </td>
                <td class="align-middle">
                    {{ $row->date ? \Carbon\Carbon::parse($row->date)->format('d M Y') : 'N/A' }}
                    <br>
                    <small class="text-muted">{{ $row->created_at ? $row->created_at->format('h:i A') : '' }}</small>
                </td>
                <td class="align-middle">
                    @if($row->location)
                        <span class="badge badge-light border text-dark">
                            {{ $row->location->name }}
                        </span>
                    @else
                        <span class="text-muted">-</span>
                    @endif
                </td>
                <td class="align-middle">
                    <strong>{{ $row->party_name }}</strong>
                    @if($row->party_contact_no)
                        <br><small class="text-muted"><i class="ft-phone"></i> {{ $row->party_contact_no }}</small>
                    @endif
                    @if($row->reference)
                        <br><small class="text-secondary"><i class="ft-tag"></i> Ref: {{ $row->reference }}</small>
                    @endif
                </td>
                <td class="align-middle">
                    @forelse($row->items as $it)
                        <div class="mb-1 pb-1 {{ !$loop->last ? 'border-bottom' : '' }}">
                            <strong class="text-dark">{{ $it->item?->name ?? 'N/A' }}</strong>:
                            <span class="badge badge-light text-primary font-weight-bold border" style="font-size: 11px;">
                                {{ number_format($it->weight, 2) }} kg
                            </span>
                        </div>
                    @empty
                        <span class="text-muted">-</span>
                    @endforelse
                </td>
                {{-- Completion Status --}}
                <td class="align-middle text-center">
                    @if($isCompleted)
                        <span class="badge badge-success" title="Completed by {{ $conf->completedBy?->name ?? 'User' }}">
                            Completed
                        </span>
                        <br>
                        <small class="text-muted" style="font-size: 10px;">
                            {{ $conf->completed_at ? \Carbon\Carbon::parse($conf->completed_at)->format('d M, h:i A') : '' }}
                        </small>
                    @else
                        <span class="badge badge-warning">
                            Pending
                        </span>
                    @endif
                </td>
                {{-- Confirmation Approval Status --}}
                <td class="align-middle text-center">
                    @if(!$isCompleted)
                        <span class="badge badge-light border text-muted">Awaiting Completion</span>
                    @else
                        @php
                            $badgeClass = match($appStatus) {
                                'approved' => 'badge-success',
                                'rejected' => 'badge-danger',
                                'reverted' => 'badge-secondary',
                                default => 'badge-info',
                            };
                        @endphp
                        <span class="badge {{ $badgeClass }}">
                            {{ ucfirst($appStatus ?: 'Pending') }}
                        </span>
                    @endif
                </td>
                {{-- Vehicle Number --}}
                <td class="align-middle text-center font-weight-bold">
                    @if(!empty($vehicleNo))
                        <span class="badge badge-primary px-2 py-1" style="font-size: 12px;">
                            {{ $vehicleNo }}
                        </span>
                    @else
                        <span class="text-muted font-italic" style="font-size: 11px;">Not Assigned</span>
                    @endif
                </td>
                {{-- Action --}}
                <td class="align-middle text-center">
                    <button type="button" class="btn btn-sm btn-info"
                        onclick="openModal(this,'{{ route('sales.dekh-confirmation.view', $row->id) }}','Dekh Confirmation - #{{ $row->inspection_no }}', false, '85%')"
                        title="View & Process">
                        <i class="ft-eye"></i>
                    </button>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="9" class="text-center py-4 text-muted">
                    No Approved Pre Sale Dekh records found for confirmation.
                </td>
            </tr>
        @endforelse
    </tbody>
</table>

<div class="row align-items-center mt-3">
    <div class="col-md-6 col-12">
        <p class="text-muted mb-0">
            Showing {{ $inspections->firstItem() ?? 0 }} to {{ $inspections->lastItem() ?? 0 }} of {{ $inspections->total() }} entries
        </p>
    </div>
    <div class="col-md-6 col-12 d-flex justify-content-end">
        {!! $inspections->links() !!}
    </div>
</div>
