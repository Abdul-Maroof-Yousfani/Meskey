<x-sticky-table :items="$tickets" :leftSticky="3" :rightSticky="1" :emptyMessage="'No purchase orders found'"
    :pagination="$tickets->links()">
    @slot('head')
    <th>Ticket No. </th>
    <th>Commodity</th>
    <th>Acc Of.</th>
    <th>Miller</th>
    <th>Net Weight</th>
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
                    {{ $row->net_weight ?? 'N/A' }} <br>
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
                {!! dateFormatHtml($row->created_at) !!}
            </td>
            <td>
                <div class="d-flex gap-2 align-items-center justify-content-">

                    <a onclick="openModal(this,'{{ route('ticket.edit', $row->id) }}','View Ticket', true)"
                        class="info p-1 text-center mr-2 position-relative">
                        <i class="ft-eye font-medium-3"></i>
                    </a>
                    @canAccess('arrival-master-control')
                    <a href="{{ route('ticket.arrival-revert', $row->id) }}" class="badge badge-danger border-0 mr-2">
                        Master Control
                    </a>
                    @if (auth()->user()->user_type == 'super-admin')
                    <a href="{{ route('ticket.arrival-revert-test', $row->id) }}" class="badge badge-danger border-0 mr-2">
                        Master Control Test
                    </a>
                    @endif
                    @endcanAccess

                    @if ($row->first_qc_status == 'rejected' && $row->bilty_return_confirmation == 0)
                        <button onclick="confirmBiltyReturn({{ $row->id }})" class="badge badge-warning border-0 mr-2">
                            Confirm Bilty Return
                        </button>
                    @elseif($row->first_qc_status == 'rejected' && $row->bilty_return_confirmation == 1)
                        <button
                            onclick="openModal(this,'{{ route('ticket.get-bilty-attachments', $row->id) }}','Bilty Return Attachments', true)"
                            class="badge badge-success border-0 mr-2">
                            <i class="ft-eye font-medium-3"></i> Return Confirmed
                        </button>
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
                <div class="mb-3">
                    <label for="weightslip" class="form-label">Weight Slip (Optional)</label>
                    <input type="file" id="weightslipattachment" class="form-control">
                </div>
                <div class="mb-3">
                    <label for="other" class="form-label">Other (Optional)</label>
                    <input type="file" id="otherattachment" class="form-control">
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
                    attachment: document.getElementById('biltyReturnAttachment').files[0],
                    weightslipattachment: document.getElementById('weightslipattachment').files[0],
                    otherattachment: document.getElementById('otherattachment').files[0]
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

                if (result.value.weightslipattachment) {
                    formData.append('weightslip_attachment', result.value.weightslipattachment);
                }

                if (result.value.otherattachment) {
                    formData.append('other_attachment', result.value.otherattachment);
                }

                $.ajax({
                    url: '/arrival/ticket/' + ticketId + '/confirm-bilty-return',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function (response) {
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
                    error: function () {
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
