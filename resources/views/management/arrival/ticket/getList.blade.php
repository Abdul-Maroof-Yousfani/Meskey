<x-sticky-table :items="$tickets" :leftSticky="3" :rightSticky="1" :emptyMessage="'No purchase orders found'" :pagination="$tickets->links()">
    @slot('head')
        <th>Ticket No. </th>
        <th>Commodity</th>
        <th>Acc Of.</th>
        <th>Miller</th>
        <th>Truck No</th>
        <th>Bilty No</th>
        <th>First QC</th>
        <th>Created</th>
        <th>Action</th>
    @endslot

    @slot('body')
        @foreach ($tickets as $key => $row)
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
                        {{ $row->accounts_of_name ?? 'N/A' }} <br>
                    </p>
                </td>
                <td>
                    <p class="m-0">
                        {{ $row->miller->name ?? 'N/A' }} <br>
                    </p>
                </td>
                <td>
                    <p class="m-0">
                        {{ $row->truck_no }}
                        <br>
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
                        {{ \Carbon\Carbon::parse($row->created_at)->format('h:i A') }}
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
                            <button onclick="confirmBiltyReturn({{ $row->id }})" class="btn btn-sm btn-danger">
                                Confirm Bilty Return
                            </button>
                        @elseif($row->first_qc_status == 'rejected' && $row->bilty_return_confirmation == 1)
                            <span class="badge badge-success">Return Confirmed</span>
                        @endif
                    </div>
                </td>
            </tr>
        @endforeach
    @endslot
</x-sticky-table>

<script>
    function confirmBiltyReturn(ticketId) {
        Swal.fire({
            title: 'Confirm Bilty Return',
            html: `
            <div class="text-left">
                <div class="mb-3">
                    <label for="biltyReturnReason" class="form-label">Reason for Return</label>
                    <textarea id="biltyReturnReason" class="form-control" placeholder="Enter reason..."></textarea>
                </div>
                <div class="mb-3">
                    <label for="biltyReturnAttachment" class="form-label">Attachment (Optional)</label>
                    <input type="file" id="biltyReturnAttachment" class="form-control">
                </div>
            </div>
        `,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Confirm Return',
            preConfirm: () => {
                return {
                    reason: document.getElementById('biltyReturnReason').value,
                    attachment: document.getElementById('biltyReturnAttachment').files[0]
                }
            }
        }).then((result) => {
            if (result.isConfirmed) {
                const formData = new FormData();
                formData.append('_token', '{{ csrf_token() }}');
                formData.append('_method', 'PUT');
                formData.append('bilty_return_reason', result.value.reason);

                if (result.value.attachment) {
                    formData.append('bilty_return_attachment', result.value.attachment);
                }

                $.ajax({
                    url: '/arrival/ticket/' + ticketId + '/confirm-bilty-return',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
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
