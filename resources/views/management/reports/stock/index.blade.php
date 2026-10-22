@extends('management.layouts.master')

@section('title')
    Stock Movement & Inventory Report
@endsection

@section('content')
    <div class="content-wrapper">
        <section id="stock-report-section">
            <!-- Header Row -->
            <div class="row w-100 mx-auto mb-3 align-items-center">
                <div class="col-md-7">
                    <h2 class="page-title mb-1 font-weight-bold" style="font-size: 22px; color: #1e293b;">
                        Stock Movement & Inventory Report
                    </h2>
                    <p class="text-muted mb-0 small">
                        Real-time physical stock ledger, inward/outward movements, valuation & commodity/factory closing balances
                    </p>
                </div>
                <div class="col-md-5 d-flex justify-content-md-end align-items-center mt-2 mt-md-0">
                    <button type="button" class="btn btn-secondary mr-2 shadow-sm" onclick="exportToExcel('stockReportTable', 'Stock_Report_' + new Date().toISOString().slice(0,10))">
                        Export to Excel
                    </button>
                    <button type="button" class="btn btn-outline-primary shadow-sm" onclick="window.print()">
                        Print Report
                    </button>
                </div>
            </div>

            <!-- Filter Card -->
            <div class="row">
                <div class="col-12">
                    <div class="card shadow-sm border-0 mb-3" style="border-radius: 8px;">
                        <div class="card-header bg-white border-bottom py-2 d-flex justify-content-between align-items-center">
                            <h6 class="mb-0 font-weight-bold text-dark">
                                Filter Inventory Records
                            </h6>
                            <small class="text-muted">Filter by date, warehouse, factory, brand, commodity, etc.</small>
                        </div>
                        <div class="card-body py-3">
                            <form id="filterForm" class="form">
                                @csrf
                                <div class="row">
                                    <!-- Date Range -->
                                    <div class="col-lg-3 col-md-6 mb-2">
                                        <label class="font-weight-bold text-secondary small">Date Range:</label>
                                        <input type="text" name="daterange" id="daterange" class="form-control form-control-sm"
                                            placeholder="All Time or Select Range"
                                            value="{{ request('daterange', '') }}" autocomplete="off" />
                                    </div>

                                    <!-- Warehouse / Location -->
                                    <div class="col-lg-3 col-md-6 mb-2">
                                        <label class="font-weight-bold text-secondary small">Warehouse / City Location:</label>
                                        <select name="company_location_id[]" id="company_location_id" multiple class="form-control form-control-sm select2" style="width: 100%;">
                                            @foreach ($locations as $loc)
                                                <option value="{{ $loc->id }}">
                                                    {{ $loc->name }} {{ $loc->code ? "({$loc->code})" : "" }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <!-- Factory / Unit Filter (Dependent on Warehouse) -->
                                    <div class="col-lg-3 col-md-6 mb-2">
                                        <label class="font-weight-bold text-secondary small">
                                            Factory / Unit:
                                        </label>
                                        <select name="factory_id[]" id="factory_id" multiple class="form-control form-control-sm select2" style="width: 100%;">
                                            @foreach ($factories as $fact)
                                                <option value="{{ $fact->id }}" data-location-id="{{ $fact->company_location_id }}">
                                                    {{ $fact->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <!-- Brand Filter -->
                                    <div class="col-lg-3 col-md-6 mb-2">
                                        <label class="font-weight-bold text-secondary small">
                                            Brand:
                                        </label>
                                        <select name="brand_id[]" id="brand_id" multiple class="form-control form-control-sm select2" style="width: 100%;">
                                            @foreach ($brands as $brand)
                                                <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <!-- Commodity / Product -->
                                    <div class="col-lg-3 col-md-6 mb-2">
                                        <label class="font-weight-bold text-secondary small">Product / Commodity:</label>
                                        <select name="product_id[]" id="product_id" multiple class="form-control form-control-sm select2" style="width: 100%;">
                                            @foreach ($products as $prod)
                                                <option value="{{ $prod->id }}">
                                                    {{ $prod->name }} ({{ $prod->unique_no }})
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <!-- Voucher Type -->
                                    <div class="col-lg-3 col-md-6 mb-2">
                                        <label class="font-weight-bold text-secondary small">Voucher Type:</label>
                                        <select name="voucher_type" id="voucher_type" class="form-control form-control-sm select2" style="width: 100%;">
                                            <option value="">All Voucher Types</option>
                                            @foreach ($voucherTypes as $vtKey => $vtLabel)
                                                <option value="{{ $vtKey }}">{{ $vtLabel }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <!-- Movement Type -->
                                    <div class="col-lg-3 col-md-6 mb-2">
                                        <label class="font-weight-bold text-secondary small">Movement Type:</label>
                                        <select name="type" id="type" class="form-control form-control-sm select2" style="width: 100%;">
                                            <option value="">All Movements (In & Out)</option>
                                            @foreach ($movementTypes as $mtKey => $mtLabel)
                                                <option value="{{ $mtKey }}">{{ $mtLabel }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <!-- Bag Packing -->
                                    <div class="col-lg-3 col-md-6 mb-2">
                                        <label class="font-weight-bold text-secondary small">Packaging / Bag Packing:</label>
                                        <select name="bag_packing_id" id="bag_packing_id" class="form-control form-control-sm select2" style="width: 100%;">
                                            <option value="">All Packaging Types</option>
                                            @foreach ($bagPackings as $bp)
                                                <option value="{{ $bp->id }}">{{ $bp->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <!-- Keyword Search -->
                                    <div class="col-lg-6 col-md-8 mb-2">
                                        <label class="font-weight-bold text-secondary small">Search Voucher No / Narration:</label>
                                        <input type="text" name="keyword" id="keyword" class="form-control form-control-sm"
                                            placeholder="e.g. KHI-10-2026, Delivery Note, etc..." />
                                    </div>

                                    <!-- Filter Buttons -->
                                    <div class="col-lg-6 col-md-4 mb-2 d-flex align-items-end justify-content-end">
                                        <button type="submit" class="btn btn-sm btn-primary px-4 shadow-sm mr-2" id="submitBtn">
                                            Generate Report
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-secondary px-3 shadow-sm" id="resetBtn" title="Reset all filters">
                                            Reset
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Dynamic Result Container (Populated via AJAX with Loader) -->
            <div id="filteredData">
                <div class="card border-0 shadow-sm my-3" style="border-radius: 8px;">
                    <div class="card-body text-center py-5">
                        <div class="spinner-border text-primary" role="status" style="width: 2.8rem; height: 2.8rem;">
                            <span class="sr-only">Loading data...</span>
                        </div>
                        <h6 class="mt-3 font-weight-bold text-dark">Loading stock records...</h6>
                        <p class="text-muted small mb-0">Please wait while calculations & inventory movements are processed.</p>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection

@section('script')
    <script>
        $(document).ready(function() {
            // All factories dataset with their warehouse association
            const allFactories = {!! json_encode($factoriesJson) !!};

            // Initialize Select2 on form elements
            $('#product_id, #company_location_id, #factory_id, #brand_id, #voucher_type, #type, #bag_packing_id').select2({
                placeholder: function() {
                    return $(this).data('placeholder') || 'Select option';
                },
                allowClear: true
            });

            // Dynamic Dependent Filter: Warehouse -> Factory
            $('#company_location_id').on('change', function() {
                const selectedLocations = $(this).val() || [];
                const $factorySelect = $('#factory_id');
                const previousSelected = $factorySelect.val() || [];

                $factorySelect.empty();

                let availableFactories = allFactories;
                if (selectedLocations.length > 0) {
                    availableFactories = allFactories.filter(function(f) {
                        return selectedLocations.includes(f.location_id);
                    });
                }

                availableFactories.forEach(function(f) {
                    const isSelected = previousSelected.includes(f.id);
                    const label = (selectedLocations.length > 1 || selectedLocations.length === 0) 
                        ? `${f.name} (${f.location_name})` 
                        : f.name;
                    const opt = new Option(label, f.id, isSelected, isSelected);
                    $factorySelect.append(opt);
                });

                $factorySelect.trigger('change.select2');
            });

            // Master AJAX filtration function with Loader
            window.runFilter = function() {
                const $container = $('#filteredData');
                
                // Show clean modern loader
                $container.html(`
                    <div class="card border-0 shadow-sm my-3" style="border-radius: 8px;">
                        <div class="card-body text-center py-5">
                            <div class="spinner-border text-primary" role="status" style="width: 2.8rem; height: 2.8rem;">
                                <span class="sr-only">Loading data...</span>
                            </div>
                            <h6 class="mt-3 font-weight-bold text-dark">Loading stock records...</h6>
                            <p class="text-muted small mb-0">Please wait while calculations & inventory movements are processed.</p>
                        </div>
                    </div>
                `);

                const formData = $('#filterForm').serialize();

                $.ajax({
                    url: `{{ route('reports.get.stock-report') }}`,
                    type: 'POST',
                    data: formData,
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(responseHtml) {
                        $container.html(responseHtml);
                    },
                    error: function(xhr, status, error) {
                        console.error('Stock Report AJAX Error:', error);
                        $container.html(`
                            <div class="alert alert-danger my-3 p-4 text-center" style="border-radius: 8px;">
                                <h6 class="font-weight-bold mb-1">Failed to load stock report data</h6>
                                <p class="small text-muted mb-2">An error occurred while fetching inventory records.</p>
                                <button type="button" class="btn btn-sm btn-outline-danger" onclick="runFilter()">Try Again</button>
                            </div>
                        `);
                    }
                });
            };

            // Handle form submission
            $('#filterForm').on('submit', function(e) {
                e.preventDefault();
                runFilter();
            });

            // Handle Reset button
            $('#resetBtn').on('click', function() {
                $('#filterForm')[0].reset();
                $('#product_id').val(null).trigger('change');
                $('#company_location_id').val(null).trigger('change');
                $('#factory_id').val(null).trigger('change');
                $('#brand_id').val(null).trigger('change');
                $('#voucher_type').val('').trigger('change');
                $('#type').val('').trigger('change');
                $('#bag_packing_id').val('').trigger('change');
                $('#daterange').val('');
                runFilter();
            });

            // Initial load on page ready
            runFilter();
        });
    </script>

    <style>
        @media print {
            .navbar, .main-menu, #filterForm, .page-title, .btn, .breadcrumb, footer {
                display: none !important;
            }
            .content-wrapper {
                margin: 0 !important;
                padding: 0 !important;
            }
            .card {
                border: none !important;
                box-shadow: none !important;
            }
            .table-responsive {
                overflow: visible !important;
            }
            .print-header {
                display: block !important;
            }
        }
        .print-header {
            display: none;
        }
    </style>
@endsection
