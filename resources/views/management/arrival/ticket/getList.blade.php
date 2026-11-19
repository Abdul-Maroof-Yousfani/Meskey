<style>
    .sticky-table tr.bg-locked :is(th, td) {
        background-color: #e9ecef !important;
    }
    .sticky-table tr.bg-locked:hover :is(th, td) {
        background-color: #dfe3e8 !important;
    }
    .layout-dark .sticky-table tr.bg-locked :is(th, td) {
        background-color: #2b3035 !important;
    }
    .layout-dark .sticky-table tr.bg-locked:hover :is(th, td) {
        background-color: #343a40 !important;
    }

    /* Mobile cards + pagination CSS: public/management/assets/css/mobile-ui.css (section 6) */
</style>

{{-- ===== MOBILE: cards (wahi $tickets, koi extra query nahi) ===== --}}
<div class="tkc-list">
    @forelse ($tickets as $row)
        @include('management.arrival.ticket.partials.mobile-card', ['row' => $row])
    @empty
        <div class="tkc-empty">
            <i class="ft-inbox"></i>
            No tickets found
        </div>
    @endforelse
</div>

{{-- ===== DESKTOP: table (mobile par sirf iski pagination dikhti hai) ===== --}}
<div class="tk-table-wrap">
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
    <th>Station</th>
    <th>First QC</th>
    <th>Created</th>
    <th>Action</th>
    @endslot

    @slot('body')
    @foreach ($tickets as $key => $row)
        <tr class="@if ($row->first_qc_status == 'rejected') bg-red @elseif ($row->is_arrival_lock) bg-locked @endif"
            @if ($row->is_arrival_lock) title="Arrival Locked Ticket" @endif>
            <td>
                <p class="m-0">
                    #{{ $row->unique_no }}
                    @if ($row->is_arrival_lock)
                        <i class="ft-lock text-warning ml-1" title="Locked Arrival Ticket" style="font-size: 13px;"></i>
                    @endif
                    <br>
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
                <p class="m-0">
                    {{ $row->station_name ?? $row->station->name ?? 'N/A' }} <br>
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
                        class="info p-1 text-center mr-2 position-relative" title="View Ticket">
                        <i class="ft-eye font-medium-3"></i>
                    </a>
                    @if ($row->is_arrival_lock)
                    <a onclick="openModal(this,'{{ route('ticket.edit', $row->id) }}','Edit Ticket', false)"
                        class="warning p-1 text-center mr-2 position-relative" title="Edit Ticket">
                        <i class="ft-edit font-medium-3"></i>
                    </a>
                    @endif
                    @canAccess('arrival-master-control')
                    <a href="{{ route('ticket.arrival-revert', $row->id) }}" class="badge badge-danger border-0 mr-2">
                        Master Control
                    </a>
                    <a href="{{ route('ticket.arrival-revert-test', $row->id) }}" class="badge badge-danger border-0 mr-2">
                        Master Control (Maund)
                    </a>
                    <a onclick="openModal(this,'{{ route('ticket.audit-logs', $row->id) }}?section=ticket_detail','Audit Logs: #{{ $row->unique_no }}', false, '55%')"
                        class="badge badge-info text-white border-0 mr-2" title="View Audit Logs" style="cursor: pointer;">
                        <i class="fa fa-history"></i>
                    </a>
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
</div>

<script>
    // ===== Mobile card: ⋯ actions sheet =====
    // getList har filter/page par AJAX se dobara aata hai -> sheet + listeners sirf EK baar banao
    (function () {
        if (window.tkcSheetReady) return;
        window.tkcSheetReady = true;

        var backdrop = document.createElement('div');
        backdrop.className = 'tkc-backdrop';
        var sheet = document.createElement('div');
        sheet.className = 'tkc-sheet';
        sheet.setAttribute('role', 'dialog');
        sheet.setAttribute('aria-modal', 'true');
        sheet.innerHTML =
            '<span class="tkc-sheet-grip"></span>' +
            '<div class="tkc-sheet-title"></div>' +
            '<div class="tkc-sheet-body"></div>' +
            '<button type="button" class="tkc-sheet-cancel">Cancel</button>';
        document.body.appendChild(backdrop);
        document.body.appendChild(sheet);

        function closeSheet() {
            sheet.classList.remove('open');
            backdrop.classList.remove('show');
            document.body.classList.remove('tkc-sheet-open');
        }

        function openSheet(btn) {
            var card = btn.closest('.tkc');
            var tpl = card ? card.querySelector('template.tkc-extra') : null;
            if (!tpl) return;
            sheet.querySelector('.tkc-sheet-title').textContent = btn.getAttribute('data-title') || 'Actions';
            var body = sheet.querySelector('.tkc-sheet-body');
            body.innerHTML = '';
            body.appendChild(tpl.content.cloneNode(true));
            backdrop.classList.add('show');
            sheet.classList.add('open');
            document.body.classList.add('tkc-sheet-open');
        }

        // (actions card ke "tap = View" wale hisse ke bahar hain, is liye wo View trigger nahi karte)
        document.addEventListener('click', function (e) {
            var more = e.target.closest('.tkc-more');
            if (more) {
                e.preventDefault();
                openSheet(more);
            }
        });

        backdrop.addEventListener('click', closeSheet);
        sheet.querySelector('.tkc-sheet-cancel').addEventListener('click', closeSheet);
        // sheet ka koi action dabaya -> uska onclick/href chale, phir sheet band
        sheet.querySelector('.tkc-sheet-body').addEventListener('click', function (e) {
            if (e.target.closest('a, button')) setTimeout(closeSheet, 0);
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') closeSheet();
        });
    })();

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
                                // button wala filter laga ho to usi se refresh (auto-filter wapas na lage)
                                if (window.filterRunners && typeof window.filterRunners.filterForm === 'function') {
                                    window.filterRunners.filterForm(true);
                                } else {
                                    filterationCommon(`{{ route('get.ticket') }}`)
                                }
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
