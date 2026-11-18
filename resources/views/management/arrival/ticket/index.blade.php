@extends('management.layouts.master')
@section('title')
    Arrival Ticket
@endsection
@section('content')
    <div class="content-wrapper ">
        <section id="extended">
            {{-- tk-page-head: mobile par app-bar (neeche <style>/<script> me, sirf is page ke liye). Desktop same. --}}
            <div class="row w-100 mx-auto align-items-center tk-page-head">
                <div class="col-xs-6 col-sm-6 col-md-6 col-lg-6 col-6 tk-title-col">
                    <div class="tk-sub">Arrival</div>
                    <h2 class="page-title mb-0"> Ticket List</h2>
                </div>
                <div class="col-xs-6 col-sm-6 col-md-6 col-lg-6 col-6 text-right tk-actions">
                    <!-- <button onclick="openModal(this,'{{ route('ticket.create') }}?quick=1','Add Ticket')" type="button"
                                    class="btn btn-primary position-relative ">
                                    Quick Create Ticket
                                </button> -->
                    <a href="{{ route('ticket.create') }}" class="btn btn-primary position-relative tk-create"
                        aria-label="Create Ticket" title="Create Ticket">
                        <i class="ft-plus tk-create-icon"></i><span class="tk-create-text">Create Ticket</span>
                    </a>
                </div>
            </div>
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <form id="filterForm" class="form">
                                @php
                                    $today = \Carbon\Carbon::today()->format('Y-m-d');
                                    $oneMonthAgo = \Carbon\Carbon::today()->subMonth()->format('Y-m-d');
                                @endphp

                                <div class="row mx-0 align-items-end flex-nowrap"
                                    style="overflow-x: auto; padding-bottom: 10px;">
                                    <div class="px-1 text-left" style="min-width: 180px; flex: 1 1 180px;">
                                        <label>Location:</label>
                                        <select name="company_location_id" id="company_location"
                                            class="form-control select22">
                                            <option value="">Location</option>
                                        </select>
                                    </div>
                                    <div class="px-1 text-left" style="min-width: 180px; flex: 1 1 180px;">
                                        <label>Date Range:</label>
                                        <input type="text" name="daterange" class="form-control"
                                            value="{{ request('daterange', \Carbon\Carbon::now()->subMonth()->format('m/d/Y') . ' - ' . \Carbon\Carbon::now()->format('m/d/Y')) }}" />
                                    </div>
                                    <div class="px-1 text-left" style="min-width: 180px; flex: 1 1 180px;">
                                        <label>Accounts Of:</label>
                                        <select name="supplier_id" id="supplier_id_f" class="form-control select2">
                                            <option value="">All</option>
                                        </select>
                                    </div>
                                    <div class="px-1 text-left" style="min-width: 180px; flex: 1 1 180px;">
                                        <label>QC Status:</label>
                                        <select name="qc_status" id="qc_status_f" class="form-control selectWithoutAjax">
                                            <option value="">All</option>
                                            <option value="pending">Pending</option>
                                            <option value="approved">Approved</option>
                                            <option value="rejected">Rejected</option>
                                        </select>
                                    </div>
                                    <div class="px-1 text-left" style="min-width: 180px; flex: 1 1 180px;">
                                        <label class="form-label">Ticket No.</label>
                                        <input type="text" class="form-control" placeholder="Ticket#"
                                            name="unique_no_filter" value="{{ request('unique_no_filter') }}">
                                    </div>
                                    <div class="px-1 text-left" style="min-width: 180px; flex: 1 1 180px;">
                                        <label class="form-label">Commodity</label>
                                        <select name="product_id" id="commodity_f" class="form-control select2">
                                            <option value="">Commodity</option>
                                        </select>
                                    </div>
                                    <div class="px-1 text-left" style="min-width: 180px; flex: 1 1 180px;">
                                        <label class="form-label">Miller</label>
                                        <select name="miller_id" id="miller_id_f" class="form-control select2">
                                            <option value="">All</option>
                                        </select>
                                    </div>
                                    <div class="px-1 text-left" style="min-width: 180px; flex: 1 1 180px;">
                                        <label class="form-label">Truck No</label>
                                        <input type="text" class="form-control" placeholder="Truck#" name="truck_no"
                                            value="{{ request('truck_no') }}">
                                    </div>
                                    <div class="px-1 text-left" style="min-width: 180px; flex: 1 1 180px;">
                                        <label class="form-label">Bilty No</label>
                                        <input type="text" class="form-control" placeholder="Bilty#" name="bilty_no"
                                            value="{{ request('bilty_no') }}">
                                    </div>
                                    <div class="px-1 text-left" style="min-width: 180px; flex: 1 1 180px;">
                                        <label class="form-label">Station</label>
                                        <select name="station_id" id="station_id_f" class="form-control select2">
                                            <option value="">All</option>
                                        </select>
                                    </div>

                                    <input type="hidden" name="page" value="{{ request('page', 1) }}">
                                    <input type="hidden" name="per_page" value="{{ request('per_page', 25) }}">
                                </div>
                            </form>
                        </div>
                        <div class="card-content">
                            <div class="card-body table-responsive" id="filteredData">
                                <table class="table m-0">
                                    <thead>
                                        <tr>
                                            <th class="col-sm-2">Ticket No. </th>
                                            <th class="col-sm-3">Commodity</th>
                                            <th class="col-sm-3">Miller</th>
                                            <th class="col-sm-1">Truck No</th>
                                            <th class="col-sm-1">Bilty No</th>
                                            <th class="col-sm-1">Station</th>
                                            <th class="col-sm-1">First QC</th>
                                            <th class="col-sm-1">Created</th>
                                            <th class="col-sm-1">Action</th>
                                        </tr>
                                    </thead>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <style>
        /* ================= Ticket page header - sirf mobile ================= */
        /* desktop: sirf "Create Ticket" text (pehle jaisa) */
        .tk-sub,
        .tk-create-icon {
            display: none;
        }

        @media (max-width: 767.98px) {

            /* theme ke .wrapper ka overflow:hidden sticky tod deta hai; clip sticky nahi rokta */
            @supports (overflow: clip) {
                .wrapper {
                    overflow: clip;
                }
            }

            /* poori width app-bar, top header ke neeche chipka hua (JS margin-top set karta hai) */
            .tk-page-head {
                display: flex;
                flex-wrap: nowrap !important;
                align-items: center !important;
                gap: 12px;
                width: 100vw !important;
                max-width: 100vw !important;
                margin-left: calc(50% - 50vw) !important;
                margin-right: calc(50% - 50vw) !important;
                margin-bottom: 14px !important;
                padding: 10px 16px;
                border-bottom: 1px solid #ececf2;
                background: #fff;
                box-shadow: 0 4px 14px rgba(30, 20, 60, .05);
            }

            /* sticky: header dikhe to us ke neeche, header chhupe (m-bars-hidden) to bilkul top par */
            .tk-page-head.tk-sticky {
                position: sticky !important;
                top: var(--tk-sticky-top, 60px);
                z-index: 990;
                transition: top .28s cubic-bezier(.2, .8, .2, 1);
            }

            body.m-bars-hidden .tk-page-head.tk-sticky {
                top: 0;
            }

            /* scroll par theme top header ko shadow deti hai (body.page-scrolled) jo neeche chipke
               Arrival bar par girti hai - is page par mobile me hata do */
            body.page-scrolled nav.header-navbar,
            body nav.header-navbar.navbar-shadow {
                box-shadow: none !important;
            }

            .tk-page-head > [class*="col"] {
                flex: 0 0 auto !important;
                width: auto !important;
                max-width: none !important;
                padding: 0 !important;
            }

            /* "Welcome back / naam" jaisa: Arrival (chhota grey) / Ticket List (semibold) */
            .tk-page-head .tk-title-col {
                flex: 1 1 auto !important;
                min-width: 0;
                display: flex;
                flex-direction: column;
                justify-content: center;
                line-height: 1.2;
                text-align: left !important;
            }

            .tk-sub {
                display: block;
                font-size: 11px;
                font-weight: 400;
                line-height: 1.2;
                color: #8a8a8a;
            }

            .tk-page-head .page-title {
                margin: 0 !important;
                font-size: 13px;
                font-weight: 600;
                line-height: 1.2;
                color: #342e49;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }

            /* actions: [filter] [+] */
            .tk-page-head .tk-actions {
                display: flex;
                align-items: center;
                justify-content: flex-end;
                gap: 6px;
                flex-shrink: 0 !important;
            }

            /* ➕ soft gol button, sirf icon */
            .tk-create {
                display: inline-flex !important;
                align-items: center;
                justify-content: center;
                width: 36px;
                height: 36px;
                min-width: 0;
                margin: 0 !important;
                padding: 0 !important;
                border: 0 !important;
                border-radius: 50% !important;
                color: #4a6fd1 !important;
                background: rgba(39, 72, 154, .06) !important;
                box-shadow: none !important;
            }

            .tk-create:active {
                background: rgba(39, 72, 154, .12) !important;
            }

            .tk-create-text {
                display: none;
            }

            .tk-create-icon {
                display: inline-block;
                font-size: 16px;
                line-height: 1;
            }

            /* dark mode */
            body.layout-dark .tk-page-head {
                border-bottom-color: #2a2a30;
                background: #1b1b1f;
                box-shadow: none;
            }

            body.layout-dark .tk-sub {
                color: #9a9a9a;
            }

            body.layout-dark .tk-page-head .page-title {
                color: #e6e6e6;
            }

            body.layout-dark .tk-create {
                color: #9db4f0 !important;
                background: rgba(90, 130, 230, .10) !important;
            }
        }
    </style>
@endsection
@section('script')
    <script>
        // ===== Ticket page header (sirf mobile): top header se chipkao + sticky =====
        //  desktop par kuch nahi hota (title row normal scroll karta hai)
        (function () {
            var MOBILE = window.matchMedia('(max-width: 767.98px)');

            function fitHead() {
                var row = document.querySelector('.tk-page-head');
                var header = document.querySelector('nav.header-navbar');
                if (!row) return;

                // reset (sticky hata kar asal jagah naapo)
                row.classList.remove('tk-sticky');
                row.style.removeProperty('margin-top');
                if (!MOBILE.matches || !header) return;

                // header ki asal height (offsetHeight transform se affect nahi hoti - header chhupa ho tab bhi sahi)
                var top = header.offsetTop + header.offsetHeight;
                document.documentElement.style.setProperty('--tk-sticky-top', top + 'px');

                // header ke bilkul neeche chipkao (beech ka gap khatam)
                var rowTop = row.getBoundingClientRect().top + window.pageYOffset;
                var gap = rowTop - top;
                if (gap > 0 && gap < 140) {
                    var current = parseFloat(window.getComputedStyle(row).marginTop) || 0;
                    row.style.setProperty('margin-top', (current - gap) + 'px', 'important');
                }
                row.classList.add('tk-sticky');
            }

            document.addEventListener('DOMContentLoaded', fitHead);
            window.addEventListener('load', fitHead);
            var t;
            window.addEventListener('resize', function () {
                clearTimeout(t);
                t = setTimeout(fitHead, 150);
            });
        })();
    </script>
    <script>
        $(document).ready(function () {
            $('#qc_status_f').select2();
            filterationCommon(`{{ route('get.ticket') }}`);
            initializeDynamicSelect2('#commodity_f', 'products', 'name', 'id', true, false, true, true);
            initializeDynamicSelect2('#miller_id_f', 'millers', 'name', 'id', true, false, true, true);
            initializeDynamicSelect2('#station_id_f', 'stations', 'name', 'id', true, false, true, true);

            // Custom Dependent Select for Arrival Ticket to include "All Accounts"
            const $locationEl = $('#company_location');
            const $supplierEl = $('#supplier_id_f');

            $locationEl.select2({
                ajax: {
                    url: "/dynamic-dependent-fetch-data",
                    type: "GET",
                    dataType: "json",
                    delay: 250,
                    data: function (params) {
                        return {
                            search: params.term || "",
                            table: 'company_locations',
                            column: 'name',
                            idColumn: 'id',
                            enableTags: true,
                            targetTable: 'suppliers',
                            targetColumn: 'company_location_ids',
                            fetchMode: "source",
                        };
                    },
                    processResults: function (data) {
                        return { results: data.items };
                    },
                },
                minimumInputLength: 0,
                placeholder: "Location",
                allowClear: true
            });

            $supplierEl.select2({
                ajax: {
                    url: "/dynamic-dependent-fetch-data",
                    dataType: "json",
                    delay: 250,
                    data: function (params) {
                        return {
                            search: params.term,
                            table: 'suppliers',
                            column: 'name',
                            idColumn: "id",
                            targetTable: 'suppliers',
                            targetColumn: 'company_location_ids',
                            fetchMode: "target",
                            sourceId: $locationEl.val(),
                        };
                    },
                    processResults: function (data) {
                        let res = data.items;
                        res.unshift({ id: "", text: "All Accounts" });
                        return { results: res };
                    },
                },
                minimumInputLength: 0,
                placeholder: "All Accounts",
                allowClear: true
            });

            $locationEl.on("change", function () {
                const selectedId = $(this).val();
                $supplierEl.val(null).trigger("change");

                if (selectedId) {
                    $.ajax({
                        url: "/dynamic-dependent-fetch-data",
                        data: {
                            table: 'suppliers',
                            column: 'name',
                            fetchMode: "target",
                            sourceId: selectedId,
                        },
                        success: function (data) {
                            const options = data.items.map(
                                (item) => new Option(item.text, item.id)
                            );
                            options.unshift(new Option("All Accounts", ""));
                            $supplierEl.empty().append(options).trigger("change");
                        },
                    });
                }
            });
        });
    </script>
@endsection