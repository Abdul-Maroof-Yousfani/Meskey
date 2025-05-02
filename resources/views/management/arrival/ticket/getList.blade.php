<table class="table m-0">
    <thead>
        <tr>
            <th class="col-sm-2">Ticket No. </th>
            <th class="col-sm-2">Commodity</th>
            <th class="col-sm-2">Supplier</th>
            <th class="col-sm-1">Truck No</th>
            <th class="col-sm-1">Bilty No</th>
            <th class="col-sm-1">First QC</th>
            <th class="col-sm-1">Created</th>
            <th class="col-sm-2">Action</th>
        </tr>
    </thead>
    <tbody>
        @if (count($UnitOfMeasures) != 0)
            @foreach ($UnitOfMeasures as $key => $row)
                <tr class="@if ($row->first_qc_status == 'rejected') bg-red @endif">
                    <td>
                        <p class="m-0">
                            #{{ $row->unique_no }} <br>
                        </p>
                    </td>
                    <td>
                        <p class="m-0">
                            {{ optional($row->product)->name ?? 'No Found' }} <br>
                        </p>
                    </td>
                    <td>
                        <p class="m-0">
                            {{ $row->supplier_name }} <br>
                        </p>
                    <td>
                        <p class="m-0">
                            {{ $row->truck_no }} <br>
                        </p>
                    </td>
                    <td>
                        <p class="m-0">
                            {{ $row->bilty_no }} <br>
                        </p>
                    </td>
                    <td>
                        <label
                            class="badge text-uppercase m-0 {{ $row->first_qc_status == 'rejected' ? 'badge-danger' : 'badge-primary' }}">
                            {{ $row->first_qc_status }} <br>
                        </label>
                    </td>
                    <td>
                        <p class="m-0 white-nowrap">
                            {{ \Carbon\Carbon::parse($row->created_at)->format('Y-m-d') }} <br>
                            {{ \Carbon\Carbon::parse($row->created_at)->format('H:i A') }}
                        </p>
                    </td>
                    <td>
                        <div class="d-flex gap-2 align-items-center justify-content-center">
                            @can('role-edit')
                                <a onclick="openModal(this,'{{ route('ticket.edit', $row->id) }}','View Ticket', true)"
                                    class="info p-1 text-center mr-2 position-relative">
                                    <i class="ft-eye font-medium-3"></i>
                                </a>
                            @endcan
                            @if ($row->first_qc_status == 'rejected' && $row->bilty_return_confirmation == 0)
                                <button onclick="confirmBiltyReturn({{ $row->id }})"
                                    class="btn btn-sm btn-danger">
                                    Confirm Bilty Return
                                </button>
                            @elseif($row->first_qc_status == 'rejected' && $row->bilty_return_confirmation == 1)
                                <span class="badge badge-success">Return Confirmed</span>
                            @endif
                        </div>
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

<div class="row d-flex" id="paginationLinks">
    <div class="col-md-12 text-right">
        {{ $UnitOfMeasures->links() }}
    </div>
</div>

<script>
    function confirmBiltyReturn(ticketId) {
        Swal.fire({
            title: 'Are you sure?',
            text: "You want to confirm the bilty return?",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, confirm it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '/arrival/ticket/' + ticketId + '/confirm-bilty-return',
                    type: 'PUT',
                    data: {
                        _token: '{{ csrf_token() }}',
                        _method: 'PUT'
                    },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire(
                                'Confirmed!',
                                'Bilty return has been confirmed.',
                                'success'
                            ).then(() => {
                                filterationCommon(`{{ route('get.ticket') }}`)
                            });
                        } else {
                            Swal.fire(
                                'Error!',
                                'Something went wrong.',
                                'error'
                            );
                        }
                    },
                    error: function() {
                        Swal.fire(
                            'Error!',
                            'Something went wrong.',
                            'error'
                        );
                    }
                });
            }
        });
    }
</script>
