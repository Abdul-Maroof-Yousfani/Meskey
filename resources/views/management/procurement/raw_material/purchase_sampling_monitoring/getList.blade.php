<table class="table m-0">
    <thead>
        <tr>
            <th class="col-sm-2">Contract No.</th>
            <th class="col-sm-2">Supplier</th>
            <th class="col-sm-2">Product</th>
            {{-- <th class="col-sm-1">Type</th> --}}
            <th class="col-sm-3">Remark</th>
            <th class="col-sm-1">Status</th>
            <th class="col-sm-2">Created</th>
            <th class="col-sm-1">Action</th>
        </tr>
    </thead>
    <tbody>
        @if (count($samplingRequests) != 0)
            @foreach ($samplingRequests as $key => $row)
                <?php
                if ($row->approved_status == 'pending') {
                    $color = 'orange';
                } elseif ($row->approved_status == 'rejected') {
                    $color = 'red';
                } elseif ($row->approved_status == 'approved') {
                    $color = 'green';
                } else {
                    $color = 'grey';
                }
                
                ?>
                <tr class="bg-{{ $color }}">
                    <td>
                        <p class="m-0">
                            #{{ $row->purchaseOrder->contract_no ?? ($row->purchaseTicket->unique_no ?? 'N/A') }}
                            {{ $row->is_custom_qc == 'yes' ? '(Without Contract)' : '' }} <br>
                        </p>
                    </td>
                    <td>
                        {{-- @dd($row) --}}
                        <p class="m-0">
                            {{ $row->purchaseOrder->supplier->name ?? ($row->supplier_name ?? 'N/A') }} <br>
                        </p>
                    </td>
                    <td>
                        <p class="m-0">
                            {{ $row->purchaseOrder->product->name ?? ($row->product->name ?? 'N/A') }} <br>
                        </p>
                    </td>
                    {{-- <td>
                        <label for=""
                            class="badge text-uppercase {{ $row->sampling_type == 'initial' ? 'badge-secondary' : 'badge-success' }}">
                            {{ $row->sampling_type }} </label>
                    </td> --}}
                    <td>
                        <p class="m-0">
                            {{ $row->remark ?? '---' }} <br>
                        </p>
                    </td>
                    <td>
                        @if ($row->approved_status == 'pending')
                            <div class="badge badge-warning text-uppercase">{{ $row->approved_status }}</div>
                        @elseif($row->approved_status == 'rejected')
                            <div class="badge badge-danger text-uppercase">{{ $row->approved_status }}</div>
                        @elseif($row->approved_status == 'approved')
                            <div class="badge badge-success text-uppercase">{{ $row->approved_status }}</div>
                        @else
                            <div class="badge badge-secondary text-uppercase">{{ $row->approved_status }}</div>
                        @endif
                    </td>
                    <td>
                        <p class="m-0">
                            {{ \Carbon\Carbon::parse($row->created_at)->format('Y-m-d') }} /
                            {{ \Carbon\Carbon::parse($row->created_at)->format('h:i A') }} <br>
                        </p>
                    </td>
                    <td>
                        @can('role-edit')
                            <a onclick="openModal(this,'{{ route('raw-material.sampling-monitoring.edit', $row->id) }}','View Approval Requests (Purchase)')"
                                class="info p-1 text-center mr-2 position-relative">
                                <i class="ft-eye font-medium-3"></i>
                            </a>
                        @endcan
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
        {{ $samplingRequests->links() }}
    </div>
</div>


<script>
    function updateRequestStatus(requestId, status) {
        Swal.fire({
            title: "Are you sure?",
            text: "You want to mark this request as " + status + "?",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: "Yes, proceed!"
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: "{{ route('initialsampling.updateStatus') }}",
                    type: "POST",
                    data: {
                        _token: "{{ csrf_token() }}",
                        request_id: requestId,
                        status: status
                    },
                    beforeSend: function() {
                        Swal.fire({
                            title: "Processing...",
                            text: "Please wait",
                            allowOutsideClick: false,
                            showConfirmButton: false,
                            willOpen: () => {
                                Swal.showLoading();
                            }
                        });
                    },
                    success: function(response) {
                        Swal.fire({
                            title: "Success!",
                            text: response.message,
                            icon: "success"
                        }).then(() => {
                            location.reload();
                        });
                    },
                    error: function(xhr) {
                        Swal.fire({
                            title: "Error!",
                            text: xhr.responseJSON.message || "Something went wrong!",
                            icon: "error"
                        });
                    }
                });
            }
        });
    }
</script>
