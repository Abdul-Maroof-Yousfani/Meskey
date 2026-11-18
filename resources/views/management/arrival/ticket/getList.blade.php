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

    /* =====================================================================
       MOBILE CARDS (Tareeqa B): mobile par alag cards (partials/mobile-card),
       desktop par table. Pagination dono me dikhti hai.
       ===================================================================== */
    .tkc-list {
        display: none;
    }

    @media (max-width: 767.98px) {

        /* table chhupao, pagination (component ke andar) dikhti rahe */
        .tk-table-wrap .sticky-table-scroller {
            display: none;
        }

        .tkc-list {
            display: block;
            position: relative;
        }

        /* list ke gird wala safed card: transparent, left/right padding nahi (cards khud card hain) */
        .card:has(#filteredData) {
            background: transparent !important;
            border: 0 !important;
            box-shadow: none !important;
        }

        #filteredData {
            padding-left: 0 !important;
            padding-right: 0 !important;
            background: transparent !important;
        }

        /* ---- Pagination (global vendor/pagination/bootstrap-5) ----
           Us view me < 576px par sirf ek disabled "1" dikhta hai aur asli pagination
           "d-none d-sm-flex" se chhupa hota hai. Mobile par asli wala dikhao. */
        #paginationLinks {
            margin: 4px 0 0;
        }

        #paginationLinks > [class*="col"] {
            padding: 0;
            text-align: center !important;
        }

        #paginationLinks .d-sm-none {
            display: none !important;            /* bekaar "1" wala hissa */
        }

        /* asli pagination: safed card (notifications page jaisa) */
        #paginationLinks .d-none.d-sm-flex {
            display: flex !important;
            flex-direction: column;
            align-items: stretch;
            gap: 12px;
            width: 100%;
            padding: 12px;
            border: 1px solid #eceef4;
            border-radius: 16px;
            background: #fff;
            box-shadow: 0 2px 10px rgba(20, 30, 70, .04);
        }

        #paginationLinks .d-none.d-sm-flex > div {
            width: 100%;
        }

        /* "Showing 1 to 25 of 240 results" */
        #paginationLinks p.small {
            margin: 0;
            font-size: 13px;
            text-align: center;
            color: #8a8797 !important;
        }

        #paginationLinks p.small .fw-semibold {
            font-weight: 700;
            color: #2b2a35;
        }

        #paginationLinks .pagination {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            align-items: center;
            gap: 6px;
            margin: 0;
        }

        #paginationLinks .page-link {
            min-width: 34px;
            height: 34px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0 8px;
            border: 1px solid #eceef4;
            border-radius: 11px !important;
            background: #fff;
            color: #2b2a35;
            font-size: 12.5px;
            font-weight: 600;
            box-shadow: none;
        }

        #paginationLinks .page-item.active .page-link {
            color: #fff;
            border-color: transparent;
            background: linear-gradient(135deg, #4a6fd1, #27489a);
            box-shadow: 0 6px 14px rgba(39, 72, 154, .28);
        }

        #paginationLinks .page-item.disabled .page-link {
            opacity: .5;
        }

        /* "/ Page" dropdown: apni line me, poori width */
        #paginationLinks .pagination > li:last-child {
            flex-basis: 100%;
            margin-top: 4px;
        }

        #paginationLinks #per_page {
            width: 100%;
            height: 38px;
            margin: 0 !important;
            padding: 0 32px 0 12px;
            border: 1px solid #eceef4;
            border-radius: 11px;
            background: #fff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%238a8797' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E") no-repeat right 12px center;
            color: #2b2a35;
            font-size: 13px;
            font-weight: 600;
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
        }

        /* dark mode */
        .layout-dark #paginationLinks .d-none.d-sm-flex {
            border-color: #2a2a30;
            background: #1c1c20;
            box-shadow: none;
        }

        .layout-dark #paginationLinks p.small .fw-semibold,
        .layout-dark #paginationLinks .page-link,
        .layout-dark #paginationLinks #per_page {
            color: #e4e4e8;
        }

        .layout-dark #paginationLinks .page-link,
        .layout-dark #paginationLinks #per_page {
            border-color: #2a2a30;
            background-color: #26262c;
        }

        .layout-dark #paginationLinks .page-item.active .page-link {
            color: #fff;
            background: linear-gradient(135deg, #4a6fd1, #27489a);
        }

        /* loading (filterationCommon shimmer chhupi table me banata hai) -> cards halke + spinner */
        #filteredData:has(tbody.shimmer-table) .tkc-list {
            opacity: .45;
            pointer-events: none;
        }

        #filteredData:has(tbody.shimmer-table) .tkc-list::after {
            content: "";
            position: absolute;
            top: 40px;
            left: 50%;
            width: 34px;
            height: 34px;
            margin-left: -17px;
            border-radius: 50%;
            border: 3px solid rgba(39, 72, 154, .15);
            border-top-color: #27489a;
            animation: tkcSpin .7s linear infinite;
        }

        @keyframes tkcSpin {
            to { transform: rotate(360deg); }
        }

        /* ---- card ---- */
        .tkc {
            position: relative;
            margin-bottom: 12px;
            border: 1px solid #eceef4;
            border-radius: 16px;
            background: #fff;
            box-shadow: 0 2px 10px rgba(20, 30, 70, .05);
            overflow: hidden;
        }

        .tkc.is-rejected {
            background: #fff6f6;
            border-color: rgba(229, 72, 77, .30);
        }

        .tkc.is-locked {
            background: #f3f4f7;
        }

        .tkc.is-rejected::before,
        .tkc.is-locked::before {
            content: "";
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 4px;
            background: #9aa3b5;
        }

        .tkc.is-rejected::before {
            background: #e5484d;
        }

        .tkc-main {
            padding: 14px 14px 10px;
            cursor: pointer;
            -webkit-tap-highlight-color: transparent;
        }

        .tkc-main:active {
            background: rgba(39, 72, 154, .03);
        }

        .tkc-top {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 10px;
        }

        .tkc-no {
            font-size: 15px;
            font-weight: 700;
            color: #27489a;
            word-break: break-all;
        }

        .tkc-no .ft-lock {
            margin-left: 4px;
            font-size: 13px;
            color: #f59e0b;
        }

        .tkc-qc {
            flex-shrink: 0;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 10.5px;
            font-weight: 700;
            letter-spacing: .3px;
            text-transform: uppercase;
            color: #27489a;
            background: rgba(39, 72, 154, .10);
        }

        .tkc-qc.is-rejected {
            color: #d93a40;
            background: rgba(229, 72, 77, .12);
        }

        .tkc-qc.is-pending {
            color: #c2410c;
            background: rgba(249, 115, 22, .14);
        }

        .tkc-qc.is-approved,
        .tkc-qc.is-completed {
            color: #15803d;
            background: rgba(34, 197, 94, .14);
        }

        .tkc-date {
            margin-top: 2px;
            font-size: 11.5px;
            color: #8a8797;
        }

        .tkc-date i {
            margin-right: 3px;
            font-size: 11px;
        }

        .tkc-commodity {
            margin-top: 10px;
            font-size: 14px;
            font-weight: 600;
            color: #2b2a35;
        }

        .tkc-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px 12px;
            margin-top: 10px;
        }

        .tkc-grid > div {
            min-width: 0;
        }

        .tkc-label {
            display: block;
            margin-bottom: 2px;
            font-size: 10px;
            font-weight: 600;
            letter-spacing: .4px;
            text-transform: uppercase;
            color: #9a98a6;
        }

        .tkc-val {
            display: block;
            font-size: 13px;
            color: #2b2a35;
            word-break: break-word;
        }

        .tkc-chips {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-top: 12px;
        }

        .tkc-chip {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            max-width: 100%;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            color: #4a4858;
            background: #f3f2f8;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .tkc-chip i {
            color: #8a8797;
        }

        /* ---- actions ---- */
        .tkc-actions {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px 14px 12px;
            border-top: 1px solid #f0f1f5;
        }

        .tkc-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            height: 34px;
            padding: 0 14px;
            border: 0;
            border-radius: 20px;
            font-size: 12.5px;
            font-weight: 600;
            color: #27489a;
            background: rgba(39, 72, 154, .08);
        }

        .tkc-btn-warn {
            color: #b7791f;
            background: rgba(245, 158, 11, .14);
        }

        .tkc-btn:active,
        .tkc-more:active {
            transform: scale(.96);
        }

        .tkc-more {
            margin-left: auto;
            width: 34px;
            height: 34px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 0;
            border-radius: 50%;
            font-size: 18px;
            color: #4a4858;
            background: #f3f2f8;
        }

        /* ---- empty ---- */
        .tkc-empty {
            padding: 40px 16px;
            text-align: center;
            color: #8a8797;
            border: 1px dashed #e3e4ea;
            border-radius: 16px;
        }

        .tkc-empty i {
            display: block;
            margin-bottom: 8px;
            font-size: 30px;
            color: #b4b2be;
        }

        /* ---- ⋯ actions sheet ---- */
        .tkc-backdrop {
            position: fixed;
            inset: 0;
            z-index: 1044;
            background: rgba(15, 22, 45, .45);
            opacity: 0;
            visibility: hidden;
            transition: opacity .25s, visibility 0s linear .25s;
        }

        .tkc-backdrop.show {
            opacity: 1;
            visibility: visible;
            transition: opacity .25s, visibility 0s;
        }

        .tkc-sheet {
            position: fixed;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: 1045;
            max-height: 75vh;
            overflow-y: auto;
            padding: 20px 16px calc(16px + env(safe-area-inset-bottom));
            border-radius: 24px 24px 0 0;
            background: #fff;
            box-shadow: 0 -12px 40px rgba(15, 22, 45, .25);
            transform: translateY(105%);
            visibility: hidden;
            transition: transform .3s cubic-bezier(.2, .8, .2, 1), visibility 0s linear .3s;
        }

        .tkc-sheet.open {
            transform: translateY(0);
            visibility: visible;
            transition: transform .3s cubic-bezier(.2, .8, .2, 1), visibility 0s;
        }

        .tkc-sheet-grip {
            position: absolute;
            top: 8px;
            left: 50%;
            width: 40px;
            height: 4px;
            margin-left: -20px;
            border-radius: 4px;
            background: #d6d8e2;
        }

        .tkc-sheet-title {
            margin-bottom: 12px;
            font-size: 15px;
            font-weight: 700;
            color: #2b2a35;
        }

        .tkc-sheet-item {
            display: flex;
            align-items: center;
            gap: 12px;
            width: 100%;
            margin-bottom: 8px;
            padding: 13px 14px;
            border-radius: 14px;
            font-size: 14px;
            font-weight: 600;
            color: #2b2a35 !important;
            background: #f5f6fa;
            text-decoration: none !important;
        }

        .tkc-sheet-item i {
            width: 20px;
            text-align: center;
            font-size: 16px;
            color: #4a6fd1;
        }

        .tkc-sheet-item.is-danger i { color: #e5484d; }
        .tkc-sheet-item.is-warn i { color: #d97706; }
        .tkc-sheet-item.is-success i { color: #1f9d55; }

        .tkc-sheet-cancel {
            width: 100%;
            height: 46px;
            margin-top: 4px;
            border: 0;
            border-radius: 14px;
            font-size: 14px;
            font-weight: 600;
            color: #4a4858;
            background: transparent;
        }

        body.tkc-sheet-open {
            overflow: hidden;
        }

        /* ---- dark mode ---- */
        .layout-dark .tkc {
            border-color: #2a2a30;
            background: #1c1c20;
            box-shadow: none;
        }

        .layout-dark .tkc.is-rejected {
            background: #2a1b1d;
            border-color: rgba(229, 72, 77, .40);
        }

        .layout-dark .tkc.is-locked {
            background: #23252b;
        }

        .layout-dark .tkc-no { color: #9db4f0; }
        .layout-dark .tkc-qc.is-pending { color: #fdba74; background: rgba(249, 115, 22, .18); }
        .layout-dark .tkc-qc.is-approved,
        .layout-dark .tkc-qc.is-completed { color: #86efac; background: rgba(34, 197, 94, .16); }
        .layout-dark .tkc-commodity,
        .layout-dark .tkc-val { color: #e4e4e8; }
        .layout-dark .tkc-chip { color: #c9c8d1; background: #26262c; }
        .layout-dark .tkc-actions { border-top-color: #2a2a30; }
        .layout-dark .tkc-btn { color: #9db4f0; background: rgba(90, 130, 230, .14); }
        .layout-dark .tkc-btn-warn { color: #fbbf24; background: rgba(245, 158, 11, .14); }
        .layout-dark .tkc-more { color: #e4e4e8; background: #26262c; }
        .layout-dark .tkc-sheet { background: #1c1c20; }
        .layout-dark .tkc-sheet-title { color: #e4e4e8; }
        .layout-dark .tkc-sheet-item { color: #e4e4e8 !important; background: #26262c; }
        .layout-dark .tkc-sheet-cancel { color: #c9c8d1; }
    }
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
