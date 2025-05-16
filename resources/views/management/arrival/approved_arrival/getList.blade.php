<table class="table m-0">
    <thead>
        <tr>
            <th class="col-sm-2">Ticket No.</th>
            <th class="col-sm-1">Product</th>
            <th class="col-sm-1">Gala Name</th>
            <th class="col-sm-1">Truck No</th>
            <th class="col-sm-2">Bags Detail</th>
            {{-- <th class="col-sm-1">Bag</th>
            <th class="col-sm-1">Bag Condition</th>
            <th class="col-sm-1">Bag Packing</th> --}}
            <th class="col-sm-1">Approval Type</th>
            <th class="col-sm-1">Recv. / Rej.</th>
            {{-- <th class="col-sm-1">Rejections</th> --}}
            <th class="col-sm-1">Amanat</th>
            <th class="col-sm-1">Created At</th>
            <th class="col-sm-1">Actions</th>
        </tr>
    </thead>
    <tbody>
        @if (count($ArrivalApproves) != 0)
            @foreach ($ArrivalApproves as $approval)
                <tr>
                    <td>
                        <p class="m-0">
                            <small> {{ $approval->arrivalTicket->unique_no ?? '-' }} </small>
                        </p>
                    </td>
                    <td>{{ $approval->arrivalTicket->product->name ?? '-' }}</td>
                    <td>{{ $approval->gala_name ?? '--' }}</td>
                    <td>{{ $approval->truck_no ?? '--' }}</td>
                    <td>
                        <div class="div-box-b">
                            <small>
                                <strong>Filling Bags:</strong> {{ $approval->filling_bags_no ?? '--' }} <br>
                                <strong>Bag Type:</strong> {{ $approval->bagType->name ?? '--' }} <br>
                                <strong>Bag Condition:</strong> {{ $approval->bagCondition->name ?? '--' }} <br>
                                <strong>Bag Packing:</strong> {{ $approval->bagPacking->name ?? '--' }} <br></small>
                        </div>
                    </td>
                    {{-- <td>{{ $approval->bagType->name ?? '--' }}</td>
                    <td>{{ $approval->bagCondition->name ?? '--' }}</td>
                    <td>{{ $approval->bagPacking->name ?? '--' }}</td> --}}
                    <td>
                        <span
                            class="badge bg-light-{{ $approval->bag_packing_approval == 'Full Approved' ? 'success' : 'warning' }}">
                            {{ $approval->bag_packing_approval }}
                        </span>
                    </td>
                    <td>
                        <div class="div-box-b">
                            <small>
                                <strong>Recieved:</strong> {{ $approval->total_bags ?? '--' }} <br>
                                <strong>Rejected:</strong> {{ $approval->total_rejection ?? '0' }}
                            </small>
                        </div>
                    </td>
                    {{-- <td>{{ $approval->total_rejection ?? '--' }}</td> --}}
                    <td>
                        <span class="badge bg-light-{{ $approval->amanat == 'Yes' ? 'danger' : 'success' }}">
                            {{ $approval->amanat }}
                        </span>
                    </td>
                    <td>
                        <p class="m-0">
                            {{ \Carbon\Carbon::parse($approval->created_at)->format('Y-m-d') }}<br>
                            {{ \Carbon\Carbon::parse($approval->created_at)->format('H:i A') }}
                        </p>
                    </td>
                    <td>
                        <a onclick="openModal(this,'{{ route('arrival-approve.edit', $approval->id) }}','Edit Arrival Approval', true)"
                            class="info p-1 text-center mr-2 position-relative">
                            <i class="ft-eye font-medium-3"></i>
                        </a>
                    </td>
                </tr>
            @endforeach
        @else
            <tr class="ant-table-placeholder">
                <td colspan="13" class="ant-table-cell text-center">
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

<div class="row d-flex" id="paginationLinks">
    <div class="col-md-12 text-right">
        {{ $ArrivalApproves->links() }}
    </div>
</div>
