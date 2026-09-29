<table class="table table-hover m-0">
    <thead class="bg-light">
        <tr>
            <th width="13%">Inspection #</th>
            <th width="10%">Date</th>
            <th width="18%">Party Name & Contact</th>
            <th width="20%">Items & Weight</th>
            <th width="14%">Locations</th>
            <th width="15%">Factory / Section</th>
            <th width="10%">Action</th>
        </tr>
    </thead>
    <tbody>
        @forelse($inspections as $row)
            <tr>
                <td class="align-middle font-weight-bold" style="background-color: #f0f7ff;">
                    <span class="text-primary">#{{ $row->inspection_no }}</span>
                    @if($row->salesInquiries && $row->salesInquiries->count() > 0)
                        <br>
                        <small class="badge badge-info p-1 mt-1">
                            <i class="ft-link"></i> {{ $row->salesInquiries->first()->inquiry_no }}
                        </small>
                    @endif
                </td>
                <td class="align-middle">
                    {{ $row->date ? \Carbon\Carbon::parse($row->date)->format('d M Y') : 'N/A' }}
                    <br>
                    <small class="text-muted">{{ $row->created_at ? $row->created_at->format('h:i A') : '' }}</small>
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
                        <div class="mb-1">
                            <strong class="text-dark">{{ $it->item?->name ?? 'N/A' }}</strong>:
                            <span class="badge badge-light text-primary font-weight-bold border" style="font-size: 11px;">
                                {{ number_format($it->weight, 2) }} kg
                            </span>
                        </div>
                    @empty
                        <strong>{{ $row->item?->name ?? 'N/A' }}</strong>
                    @endforelse
                    @if($row->remarks)
                        <small class="text-muted d-block mt-1" title="{{ $row->remarks }}"><i class="ft-message-square"></i> {{ Str::limit($row->remarks, 35) }}</small>
                    @endif
                </td>
                <td class="align-middle">
                    @php
                        $locNames = [];
                        if (is_array($row->locations) && count($row->locations) > 0) {
                            $locNames = \App\Models\Master\CompanyLocation::whereIn('id', $row->locations)->pluck('name')->toArray();
                        } elseif ($row->locationModels && $row->locationModels->count() > 0) {
                            $locNames = $row->locationModels->map(fn($l) => $l->companyLocation?->name)->filter()->toArray();
                        }
                    @endphp
                    @if(count($locNames) > 0)
                        @foreach($locNames as $locName)
                            <span class="badge badge-light border mb-1">{{ $locName }}</span>
                        @endforeach
                    @else
                        <span class="text-muted">-</span>
                    @endif
                </td>
                <td class="align-middle">
                    @php
                        $factoryNames = [];
                        if (is_array($row->factories) && count($row->factories) > 0) {
                            $factoryNames = \App\Models\Master\ArrivalLocation::whereIn('id', $row->factories)->pluck('name')->toArray();
                        } elseif ($row->factoryModels && $row->factoryModels->count() > 0) {
                            $factoryNames = $row->factoryModels->map(fn($f) => $f->factory?->name)->filter()->toArray();
                        }

                        $sectionNames = [];
                        if (is_array($row->sections) && count($row->sections) > 0) {
                            $sectionNames = \App\Models\Master\ArrivalSubLocation::whereIn('id', $row->sections)->pluck('name')->toArray();
                        } elseif ($row->sectionModels && $row->sectionModels->count() > 0) {
                            $sectionNames = $row->sectionModels->map(fn($s) => $s->section?->name)->filter()->toArray();
                        }
                    @endphp
                    @if(count($factoryNames) > 0)
                        <div><strong class="text-secondary" style="font-size: 11px;">Factory:</strong> {{ implode(', ', $factoryNames) }}</div>
                    @endif
                    @if(count($sectionNames) > 0)
                        <div><strong class="text-secondary" style="font-size: 11px;">Section:</strong> {{ implode(', ', $sectionNames) }}</div>
                    @endif
                    @if(count($factoryNames) === 0 && count($sectionNames) === 0)
                        <span class="text-muted">-</span>
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
                <td colspan="7" class="text-center py-4 text-muted">
                    No Pre Sale Inspection records found.
                </td>
            </tr>
        @endforelse
    </tbody>
</table>

<div class="row mx-0 mt-3">
    <div class="col-12 d-flex justify-content-between align-items-center">
        <div>
            Showing {{ $inspections->firstItem() ?? 0 }} to {{ $inspections->lastItem() ?? 0 }} of {{ $inspections->total() }} entries
        </div>
        <div>
            {!! $inspections->links('pagination::bootstrap-4') !!}
        </div>
    </div>
</div>
