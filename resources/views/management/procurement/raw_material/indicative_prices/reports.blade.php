@extends('management.layouts.master')
@section('title')
    Indicative Prices - Daily Report
@endsection
@section('content')
    <div class="content-wrapper">
        <section id="extended">
            <div class="row w-100 mx-auto">
                <div class="col-xs-6 col-sm-6 col-md-6 col-lg-6">
                    <h2 class="page-title">Indicative Price Daily Report</h2>
                </div>

                <div class="col-md-6 text-right">
                    <button id="exportToExcel" class="btn btn-success">
                        <i class="fa fa-file-excel-o"></i> Export to Excel
                    </button>
                </div>
            </div>
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <form id="filterForm" class="form">
                                <input type="hidden" name="page" value="{{ request('page', 1) }}">
                                <input type="hidden" name="per_page" value="{{ request('per_page', 25) }}">
                                <div class="row">
                                    <div class="col-md-6"></div>
                                    <div class="col-md-6 my-1">
                                        <div class="row justify-content-end text-left">
                                            <div class="col-xs-4 col-sm-4 col-md-4">
                                                <div class="form-group">
                                                    <label>Date:</label>
                                                    <input type="date" name="date" id="reportDateFilter"
                                                        class="form-control" value="{{ now()->format('Y-m-d') }}"
                                                        max="{{ now()->format('Y-m-d') }}">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                        <style>
                            /* Hover effect colors */
                            .commodity-hover {
                                background-color: rgba(255, 0, 0, 0.2) !important;
                                /* Light red */
                            }

                            .location-hover {
                                background-color: rgba(0, 128, 0, 0.2) !important;
                                /* Light green */
                            }

                            /* Add a class to identify commodity and location cells */
                            .commodity-cell {
                                cursor: pointer;
                            }

                            .location-cell {
                                cursor: pointer;
                            }

                            /* Add transition for smooth color change */
                            .table td {
                                transition: background-color 0.2s ease;
                            }
                        </style>
                        <div class="card-content">
                            <div class="card-body table-responsive" id="filteredData">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            {{-- <th rowspan="2">S.no</th> --}}
                                            <th rowspan="2">Commodity Name</th>
                                            <th rowspan="2">Location</th>
                                            <th rowspan="2">Type</th>
                                            <th rowspan="2">Crop Year</th>
                                            <th rowspan="2">Delivery Condition</th>
                                            <th colspan="2" class="text-center">Details</th>
                                            <th colspan="2" class="text-center">Cash</th>
                                            <th colspan="2" class="text-center">Credit</th>
                                            <th rowspan="2">Others</th>
                                            <th rowspan="2">Remarks</th>
                                        </tr>
                                        <tr>
                                            <th>Time</th>
                                            <th>Purchaser</th>
                                            <th>Rate</th>
                                            <th>Days</th>
                                            <th>Rate</th>
                                            <th>Days</th>
                                        </tr>
                                    </thead>
                                    <tbody id="reportTableBody">
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection

@section('script')
    <script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
    <script>
        $(document).ready(function() {
            filterationCommon(`{{ route('indicative-prices.reports.get-list') }}`)

            $(document).on('mouseenter', '.commodity-cell', function() {
                const commodity = $(this).data('commodity');
                $(`[data-commodity="${commodity}"]`).addClass('commodity-hover');
            });

            $(document).on('mouseleave', '.commodity-cell', function() {
                const commodity = $(this).data('commodity');
                $(`[data-commodity="${commodity}"]`).removeClass('commodity-hover');
            });

            $(document).on('mouseenter', '.location-cell', function() {
                const commodity = $(this).data('commodity');
                const location = $(this).data('location');
                $(`[data-commodity="${commodity}"][data-location="${location}"]`).addClass(
                    'location-hover');
            });

            $(document).on('mouseleave', '.location-cell', function() {
                const commodity = $(this).data('commodity');
                const location = $(this).data('location');
                $(`[data-commodity="${commodity}"][data-location="${location}"]`).removeClass(
                    'location-hover');
            });

            $(document).on('mouseenter', 'tr', function() {
                const commodity = $(this).data('commodity');
                const location = $(this).data('location');

                if (commodity && location) {
                    $(`.commodity-cell[data-commodity="${commodity}"]`).addClass('commodity-hover');

                    $(`.location-cell[data-commodity="${commodity}"][data-location="${location}"]`)
                        .addClass('location-hover');
                }
            });

            $(document).on('mouseleave', 'tr', function() {
                const commodity = $(this).data('commodity');
                const location = $(this).data('location');

                if (commodity && location) {
                    $(`.commodity-cell[data-commodity="${commodity}"]`).removeClass('commodity-hover');

                    $(`.location-cell[data-commodity="${commodity}"][data-location="${location}"]`)
                        .removeClass('location-hover');
                }
            });
        });

        $('#exportToExcel').on('click', function() {
            const selectedDate = $('#reportDateFilter').val();
            const formattedDate = formatDateForFilename(selectedDate);

            const wb = XLSX.utils.book_new();

            const tableClone = $('table.table-bordered').clone();

            tableClone.find('.commodity-hover, .location-hover').removeClass('commodity-hover location-hover');

            const ws = XLSX.utils.table_to_sheet(tableClone[0]);

            const colWidths = [];
            tableClone.find('thead tr:first th').each(function() {
                colWidths.push({
                    wch: 15
                });
            });
            ws['!cols'] = colWidths;

            XLSX.utils.book_append_sheet(wb, ws, "Indicative Prices");

            const fileName = `Indicative_Prices_${formattedDate}.xlsx`;

            XLSX.writeFile(wb, fileName);
        });

        function formatDateForFilename(dateString) {
            if (!dateString) {
                const today = new Date();
                return today.toISOString().split('T')[0];
            }
            return dateString;
        }

        function updateExportButtonText() {
            const selectedDate = $('#reportDateFilter').val();
            $('#exportToExcel').html(
                `<i class="fa fa-file-excel-o"></i> Export ${selectedDate} Report`
            );
        }

        updateExportButtonText();

        $('#reportDateFilter').on('input', function() {
            updateExportButtonText();
        });
    </script>
@endsection
