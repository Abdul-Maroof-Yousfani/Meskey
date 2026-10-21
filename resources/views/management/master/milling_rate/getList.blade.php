<table class="table m-0">
    <thead>
        <tr>
            <th>Commodity</th>
            <th>Location & Plant</th>
            <th>Effective Dates</th>
            <th>Title</th>
            <th>Status</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
        @if (count($milling_rates) != 0)
            @foreach ($milling_rates as $key => $row)
                @php
                    $todayStr = $today ?? now()->toDateString();
                    $comboKey = "{$row->location_id}_{$row->sublocation_id}_{$row->plant_id}_{$row->product_id}";
                    $hasActiveOrFuture = isset($activeCombos[$comboKey]);
                    $isPast = $row->to_date && ($row->to_date->format('Y-m-d') < $todayStr);
                    $isOrphanExpired = $isPast && !$hasActiveOrFuture;
                    $isFuture = $row->from_date && ($row->from_date->format('Y-m-d') > $todayStr);
                @endphp
                <tr>
                    <td>
                        <strong class="text-primary">{{ $row->product->name ?? 'N/A' }}</strong>
                    </td>
                    <td>
                        <div><strong class="text-dark">{{ $row->location->name ?? '-' }}</strong> / {{ $row->plant->name ?? '-' }}</div>
                        @if($row->subLocation)
                            <small class="text-muted">{{ $row->subLocation->name }}</small>
                        @endif
                    </td>
                    <td>
                        <span class="badge badge-light border text-dark">
                            <i class="ft-calendar mr-1"></i>
                            {{ $row->from_date ? $row->from_date->format('d M, Y') : '-' }} to {{ $row->to_date ? $row->to_date->format('d M, Y') : '-' }}
                        </span>
                    </td>
                    <td>
                        <p class="m-0 font-weight-bold">{{ $row->title ?: '-' }}</p>
                        @if($row->description)
                            <small class="text-muted d-block">{{ Str::limit($row->description, 40) }}</small>
                        @endif
                        @if($row->variables && $row->variables->count() > 0)
                            <small class="badge badge-light border mt-1">
                                <i class="ft-sliders mr-1 text-primary"></i> {{ $row->variables->count() }} Variables
                            </small>
                        @endif
                    </td>
                    <td>
                        @if ($isOrphanExpired)
                            <span class="badge badge-danger">
                                <i class="ft-alert-circle mr-1"></i> Expired
                            </span>
                            <small class="text-danger d-block mt-1 font-small-1">No active rate configured</small>
                        @elseif ($isPast)
                            <span class="badge badge-secondary">Past Rate</span>
                        @elseif ($isFuture)
                            <span class="badge badge-info">Upcoming</span>
                        @elseif ($row->status == 1)
                            <span class="badge badge-success">Active</span>
                        @else
                            <span class="badge badge-warning">Inactive</span>
                        @endif
                    </td>

                    <td>
                        <a onclick="openModal(this,'{{ route('milling-rate.show', $row->id) }}','View Milling Rate')"
                            class="primary p-1 text-center position-relative cursor-pointer" title="View">
                            <i class="ft-eye font-medium-3"></i>
                        </a>
                        <a onclick="openModal(this,'{{ route('milling-rate.edit', $row->id) }}','Edit Milling Rate')"
                            class="info p-1 text-center position-relative cursor-pointer" title="Edit">
                            <i class="ft-edit font-medium-3"></i>
                        </a>
                    </td>
                </tr>
            @endforeach
        @else
            <tr class="ant-table-placeholder">
                <td colspan="6" class="ant-table-cell text-center py-4">
                    <p class="text-muted m-0">No milling rates found.</p>
                </td>
            </tr>
        @endif
    </tbody>
</table>

<div class="row w-100 mx-auto justify-content-between align-items-center mt-2">
    <div class="col-md-6">
        Showing {{ $milling_rates->firstItem() ?? 0 }} to {{ $milling_rates->lastItem() ?? 0 }} of {{ $milling_rates->total() }} entries
    </div>
    <div class="col-md-6 text-right">
        {{ $milling_rates->links() }}
    </div>
</div>
