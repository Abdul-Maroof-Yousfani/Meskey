@if (isset($expiredAlerts) && $expiredAlerts->isNotEmpty())
    <div class="alert alert-danger mb-3 p-3 shadow-sm" role="alert" style="border-left: 6px solid #e74c3c; background-color: #fdf2f2; color: #78281f;">
        <div class="d-flex align-items-start">
            <i class="ft-alert-triangle font-large-1 mr-3 text-danger mt-1"></i>
            <div class="w-100">
                <h5 class="alert-heading font-weight-bold mb-1 text-danger">
                    Action Required: Expired Milling Rates Detected!
                </h5>
                <p class="mb-2 font-medium-1 text-dark">
                    The following milling rate(s) have passed their effective end date and <strong>no subsequent active milling rate</strong> is currently configured. Please configure a new milling rate for these commodities:
                </p>
                <div class="table-responsive bg-white rounded p-1 border">
                    <table class="table table-sm table-bordered m-0 text-dark">
                        <thead class="bg-light">
                            <tr>
                                <th>Commodity</th>
                                <th>Location & Plant</th>
                                <th>Expired On</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($expiredAlerts as $alertRate)
                                <tr>
                                    <td class="font-weight-bold text-danger">{{ $alertRate->product->name ?? 'Commodity' }}</td>
                                    <td>{{ $alertRate->location->name ?? '-' }} / {{ $alertRate->plant->name ?? '-' }}</td>
                                    <td><span class="badge badge-danger">{{ $alertRate->to_date ? $alertRate->to_date->format('d M, Y') : '-' }}</span></td>
                                    <td class="text-center">
                                        <button onclick="openModal(this,'{{ route('milling-rate.create') }}?product_id={{ $alertRate->product_id }}','Add Milling Rate')" class="btn btn-sm btn-outline-danger py-0 px-2">
                                            <i class="ft-plus mr-1"></i> Add New Rate
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endif

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
                <tr class="{{ $isOrphanExpired ? 'table-danger' : '' }}">
                    <td>
                        <strong class="text-primary">{{ $row->product->name ?? 'N/A' }}</strong>
                    </td>
                    <td>
                        <div>{{ $row->location->name ?? '-' }} / {{ $row->plant->name ?? '-' }}</div>
                        @if($row->subLocation)
                            <small class="text-muted">{{ $row->subLocation->name }}</small>
                        @endif
                    </td>
                    <td>
                        @if ($isOrphanExpired)
                            <span class="badge badge-danger">
                                <i class="ft-calendar mr-1"></i>
                                {{ $row->from_date ? $row->from_date->format('d M, Y') : '-' }} to {{ $row->to_date ? $row->to_date->format('d M, Y') : '-' }}
                            </span>
                            <div class="text-danger small font-weight-bold mt-1">
                                <i class="ft-alert-circle mr-1"></i> Expired! No subsequent rate configured.
                            </div>
                        @elseif ($isPast)
                            <span class="badge badge-secondary">
                                <i class="ft-calendar mr-1"></i>
                                {{ $row->from_date ? $row->from_date->format('d M, Y') : '-' }} to {{ $row->to_date ? $row->to_date->format('d M, Y') : '-' }}
                            </span>
                            <small class="text-muted d-block mt-1">Past rate (Superseded)</small>
                        @elseif ($isFuture)
                            <span class="badge badge-primary">
                                <i class="ft-calendar mr-1"></i>
                                {{ $row->from_date ? $row->from_date->format('d M, Y') : '-' }} to {{ $row->to_date ? $row->to_date->format('d M, Y') : '-' }}
                            </span>
                            <small class="text-primary d-block mt-1">Upcoming rate</small>
                        @else
                            <span class="badge badge-success">
                                <i class="ft-calendar mr-1"></i>
                                {{ $row->from_date ? $row->from_date->format('d M, Y') : '-' }} to {{ $row->to_date ? $row->to_date->format('d M, Y') : '-' }}
                            </span>
                            <small class="text-success d-block mt-1 font-weight-bold"><i class="ft-check mr-1"></i>Currently Active</small>
                        @endif
                    </td>
                    <td>
                        <p class="m-0 font-weight-bold">{{ $row->title ?: '-' }}</p>
                        @if($row->description)
                            <small class="text-muted">{{ Str::limit($row->description, 40) }}</small>
                        @endif
                    </td>
                    <td>
                        @if ($isOrphanExpired)
                            <span class="badge badge-danger font-weight-bold">
                                <i class="ft-alert-triangle mr-1"></i> Expired (No Active Rate)
                            </span>
                        @elseif ($isPast)
                            <span class="badge badge-secondary">Archived / Past</span>
                        @elseif ($isFuture)
                            <span class="badge badge-info">Scheduled</span>
                        @elseif ($row->status == 1)
                            <span class="badge badge-success">Active</span>
                        @else
                            <span class="badge badge-warning">Pending</span>
                        @endif
                    </td>

                    <td>
                        <a onclick="openModal(this,'{{ route('milling-rate.show', $row->id) }}','View Milling Rate')"
                            class="primary p-1 text-center position-relative " title="View">
                            <i class="ft-eye font-medium-3"></i>
                        </a>
                        <a onclick="openModal(this,'{{ route('milling-rate.edit', $row->id) }}','Edit Milling Rate')"
                            class="info p-1 text-center position-relative " title="Edit">
                            <i class="ft-edit font-medium-3"></i>
                        </a>
                    </td>
                </tr>
            @endforeach
        @else
            <tr class="ant-table-placeholder">
                <td colspan="11" class="ant-table-cell text-center">
                    <div class="my-5">
                        <svg width="64" height="41" viewBox="0 0 64 41" xmlns="http://www.w3.org/2000/svg">
                            <g transform="translate(0 1)" fill="none" fill-rule="evenodd">
                                <ellipse fill="#f5f5f5" cx="32" cy="33" rx="32" ry="7">
                                </ellipse>
                                <g fill-rule="nonzero" stroke="#d9d9d9">
                                    <path
                                        d="M55 12.76L44.854 1.258C44.367.474 43.656 0 42.907 0H21.093c-.749 0-1.46.474-1.947 1.257L9 12.761V22h46v-9.24z">
                                    </path>
                                    <path
                                        d="M41.613 15.931c0-1.605.994-2.93 2.227-2.931H55v18.137C55 33.26 53.68 35 52.05 35h-40.1C10.32 35 9 33.259 9 31.137V13h11.16c1.233 0 2.227 1.323 2.227 2.928v.022c0 1.605 1.005 2.901 2.237 2.901h14.752c1.232 0 2.237-1.308 2.237-2.913v-.007z"
                                        fill="#fafafa"></path>
                                </g>
                            </g>
                        </svg>
                        <p class="ant-empty-description">No data</p>
                    </div>
                </td>
            </tr>
        @endif
    </tbody>
</table>
{{-- <div id="paginationLinks">
    {{ $roles->links() }}
</div> --}}



<div class="row d-flex" id="paginationLinks">
    <div class="col-md-12 text-right">
        {{ $milling_rates->links() }}
    </div>
</div>
