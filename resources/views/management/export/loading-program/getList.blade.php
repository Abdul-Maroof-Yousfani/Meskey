<table class="table table-striped m-0">
    <thead>
        <tr>
            <th>ID</th>
            <th>EO No.</th>
            <th>DO No.</th>
            <th>Vessel Name</th>
            <th>Status</th>
            <th>Created By</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
        @if($loadingPrograms->count() > 0)
            @foreach($loadingPrograms as $loadingProgram)
                @php
                    $eoReferences = $loadingProgram->exportOrders->map(function ($eo) {
                        return $eo->voucher_no ?? $eo->contract_no ?? 'EO-' . $eo->id;
                    })->toArray();
                    $eoReferences = array_unique(array_filter($eoReferences));

                    $doReferences = $loadingProgram->deliveryOrders->pluck('reference_no')->toArray();
                    $doReferences = array_unique(array_filter($doReferences));
                @endphp
                <tr>
                    <td>{{ $loadingProgram->id }}</td>
                    <td>
                        @forelse($eoReferences as $ref)
                            <div class="badge badge-secondary mb-1 d-block">{{ $ref }}</div>
                        @empty
                            N/A
                        @endforelse
                    </td>
                    <td>
                        @forelse($doReferences as $ref)
                            <div class="badge badge-success mb-1 d-block">{{ $ref }}</div>
                        @empty
                            N/A
                        @endforelse
                    </td>
                    <td>{{ $loadingProgram->vessel_name ?? 'N/A' }}</td>
                    <td>
                        @if(!empty($loadingProgram->status) && $loadingProgram->status == 'pending')
                            <span class="badge badge-warning">Pending</span>
                        @elseif(!empty($loadingProgram->status) && $loadingProgram->status == 'completed')
                            <span class="badge badge-success">Completed</span>
                        @else
                            -
                        @endif
                    </td>
                    <td>
                        {{ $loadingProgram->createdBy->name ?? 'N/A' }}<br>
                        <small class="text-muted">{{ $loadingProgram->created_at->format('d-m-Y H:i') }}</small>
                    </td>
                    <td>
                        <div class="d-flex gap-1">
                            <!-- @if($loadingProgram->status == 'pending')
                                <a onclick="openModal(this,'{{ route('export-loading-program.edit', $loadingProgram->id) }}','Edit Loading Program Request', false, '90%')"
                                    class="warning p-1 text-center mr-1 position-relative" title="Edit">
                                    <i class="ft-edit font-medium-3"></i>
                                </a>
                            @endif -->
                            <a onclick="openModal(this,'{{ route('export-loading-program-complete.edit', $loadingProgram->id) }}','Manage Loading Program Items', false, '90%')"
                                class="warning p-1 text-center mr-1 position-relative" title="Manage Items (Trucks)">
                                <i class="ft-edit font-medium-3"></i>
                            </a>
                            <a onclick="openModal(this,'{{ route('export-loading-program.show', $loadingProgram->id) }}','View Loading Program', true, '90%')"
                                class="info p-1 text-center mr-1 position-relative" title="View">
                                <i class="ft-eye font-medium-3"></i>
                            </a>
                        </div>
                    </td>
                </tr>
            @endforeach
        @else
            <tr class="ant-table-placeholder">
                <td colspan="7" class="ant-table-cell text-center">
                    <div class="my-5">
                        <svg width="64" height="41" viewBox="0 0 64 41" xmlns="http://www.w3.org/2000/svg">
                            <g transform="translate(0 1)" fill="none" fill-rule="evenodd">
                                <ellipse fill="#f5f5f5" cx="32" cy="33" rx="32" ry="7"></ellipse>
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
                        <p class="ant-empty-description">No Loading Program Requests found</p>
                    </div>
                </td>
            </tr>
        @endif
    </tbody>
</table>

<!-- Pagination -->
<div class="row d-flex" id="paginationLinks">
    <div class="col-md-12 text-right">
        {{ $loadingPrograms->links() }}
    </div>
</div>