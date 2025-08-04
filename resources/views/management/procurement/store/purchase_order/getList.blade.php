<table class="table m-0">
    <thead>
        <tr>
            <th class="col-sm-2">Purchase Order No </th>
            <th class="col-sm-2">Purchase Order Date</th>
            <th class="col-sm-2">Location</th>
            <th class="col-sm-2">Category</th>
            <th class="col-sm-2">Item</th>
            <th class="col-sm-2">Item UOM</th>
            <th class="col-sm-2">Supplier</th>
            <th class="col-sm-2">Qty</th>
            <th class="col-sm-2">Rate</th>
            <th class="col-sm-2">Total Amount</th>
            {{-- <th class="col-sm-2">Item Status</th> --}}
            <th class="col-sm-1">Action</th>
        </tr>
    </thead>
    <tbody>
        @if (count($PurchaseOrder) != 0)
            @foreach ($PurchaseOrder as $key => $row)
                <tr>
                    <td>
                        <p class="m-0">
                            {{ $row->purchase_order->purchase_order_no }} <br>
                        </p>
                    </td>
                    <td>
                         <p class="m-0">
                            {{ \Carbon\Carbon::parse($row->purchase_order->created_at)->format('Y-m-d') }} /
                            {{ \Carbon\Carbon::parse($row->purchase_order->created_at)->format('h:i A') }} <br>

                        </p>
                    </td>
                    <td>
                        <p class="m-0">
                            
                            {{ optional($row->purchase_order->location)->name}} 
                        </p>
                    </td>
                    <td>
                        <p class="m-0">
                            
                            {{ optional($row->category)->name}} 
                        </p>
                    </td>
                    <td>
                        <p class="m-0">
                            
                            {{ optional($row->item)->name}} 
                        </p>
                    </td>
                    <td>
                        <p class="m-0">
                            
                            {{ optional($row->item->unitOfMeasure)->name}} 
                        </p>
                    </td>
                     <td>
                        <p class="m-0">
                            
                            {{ optional($row->supplier)->name}} 
                        </p>
                    </td>
                    <td>
                        <p class="m-0">
                            
                            {{ $row->qty}} 
                        </p>
                    </td>
                    <td>
                        <p class="m-0">
                            
                            {{ $row->rate}} 
                        </p>
                    </td>
                    <td>
                        <p class="m-0">
                            
                            {{ $row->total}} 
                        </p>
                    </td>
                    
                   
                    <td>
                        @can('role-edit')
                            <a onclick="openModal(this,'{{ route('store.purchase-order.edit', $row->id) }}','Edit Purchase Order',false,'80%')"
                                class="info p-1 text-center mr-2 position-relative ">
                                <i class="ft-edit font-medium-3"></i>
                            </a>
                        @endcan
                        @can('role-delete')
                            <a onclick="deletemodal('{{ route('store.purchase-order.destroy', $row->id) }}','{{ route('store.get.purchase-order') }}')"
                                class="danger p-1 text-center mr-2 position-relative ">

                                <i class="ft-x font-medium-3"></i>
                            </a>
                        @endcan
                        {{-- @can('role-delete') --}}
                        {{-- @php
                            $currentUserRoleId = Auth::user()->role_id; // adjust if many-to-many
                            $alreadyApproved = $row->approval()->where('role_id', $currentUserRoleId)->where('status_id', 2)->exists();
                        @endphp
                            @if(!$alreadyApproved)
                                <a onclick="approveItem('{{ route('store.purchase-request.approve', $row->id) }}')"
                                    class="success p-1 text-center position-relative" title="Approve">
                                    <i class="ft-check font-medium-3"></i>
                                </a>
                            @else
                                <span class="badge badge-success">Approved</span>
                            @endif
                        @endcan --}}
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
        {{ $PurchaseOrder->links() }}
    </div>
</div>
<script>
    function approveItem(url) {
        Swal.fire({
            title: 'Are you sure?',
            text: "You are about to approve this item.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, approve it!',
            cancelButtonText: 'Cancel',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: url,
                    type: 'GET',
                   
                    success: function(res) {
                        Swal.fire({
                            title: 'Approved!',
                            text: res.message,
                            icon: 'success',
                            timer: 2000,
                            showConfirmButton: false
                        }).then(() => {
                            location.reload();
                        });
                    },
                    error: function() {
                        Swal.fire('Error', 'Error occurred while approving item.', 'error');
                    }
                });
            }
        });
    }

</script>
