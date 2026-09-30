<table class="table table-hover m-0">
    <thead class="bg-light">
        <tr>
            <th width="14%">Inspection #</th>
            <th width="10%">Date</th>
            <th width="12%">Location</th>
            <th width="18%">Party Name & Contact</th>
            <th width="34%">Items, Factory, Section & Weight</th>
            <th width="12%" class="text-center">Action</th>
        </tr>
    </thead>
    <tbody>
        @forelse($inspections as $row)
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
                            <i class="ft-map-pin text-primary"></i> {{ $row->location->name }}
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
                            @if($it->factory || $it->section)
                                <div style="font-size: 11px;" class="text-muted mt-1">
                                    @if($it->factory)
                                        <span class="mr-2"><i class="ft-home"></i> <strong>Factory:</strong> {{ $it->factory->name }}</span>
                                    @endif
                                    @if($it->section)
                                        <span><i class="ft-layers"></i> <strong>Section:</strong> {{ $it->section->name }}</span>
                                    @endif
                                </div>
                            @endif
                        </div>
                    @empty
                        <span class="text-muted">-</span>
                    @endforelse
                    @if($row->remarks)
                        <small class="text-muted d-block mt-1" title="{{ $row->remarks }}"><i class="ft-message-square"></i> {{ Str::limit($row->remarks, 45) }}</small>
                    @endif
                </td>
                <td class="text-center align-middle">
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-sm btn-info"
                            onclick="openModal(this,'{{ route('sales.pre-sale-inspection.view', $row->id) }}','View Pre Sale Inspection', false, '80%')"
                            title="View" style="margin-right: 5px;">
                            <i class="ft-eye"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-warning"
                            onclick="openModal(this,'{{ route('sales.pre-sale-inspection.edit', $row->id) }}','Edit Pre Sale Inspection', false, '80%')"
                            title="Edit" style="margin-right: 5px;">
                            <i class="ft-edit"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-danger"
                            onclick="deletemodal('{{ route('sales.pre-sale-inspection.destroy', $row->id) }}', '{{ route('sales.get.pre-sale-inspection.list') }}')"
                            title="Delete">
                            <i class="ft-trash-2"></i>
                        </button>
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="6" class="text-center py-4 text-muted">
                    No Pre Sale Inspection records found.
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
