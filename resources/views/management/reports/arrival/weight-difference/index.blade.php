@extends('management.layouts.master')
@section('title')
    Arrival Weight Difference Report
@endsection
@section('content')
    <div class="content-wrapper">
        <section id="extended">
            <div class="row w-100 mx-auto">
                <div class="col-xs-6 col-sm-6 col-md-6 col-lg-6">
                    <h2 class="page-title">
                        Arrival Weight Difference Report
                    </h2>
                </div>
                <div class="col-md-6 d-flex align-items-end justify-content-end">
                    <div class="form-group mb-0">
                        <button class="btn btn-secondary" onclick="exportToExcel('exportableTable','Arrival_Weight_Difference')">
                            <i class="fa fa-file-excel-o mr-2"></i> Export to Excel
                        </button>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <form id="filterForm" class="form">
                                <div class="row">
                                    <div class="col-md-12 my-1">
                                        <div class="row justify-content-nd text">
                                            <div class="col-md-2">
                                                <div class="form-group mb-0">
                                                    <label>Location:</label>
                                                    <select name="company_location_id[]" id="company_location_id" multiple
                                                        class="form-control selectWithoutAjax" data-placeholder="Select Location">
                                                        @if (count($locations) > 1)
                                                            <option value="">Select Location</option>
                                                        @endif
                                                        @foreach ($locations as $location)
                                                            <option value="{{ $location->id }}"
                                                                {{ count($locations) == 1 || (is_array(request('company_location_id')) && in_array($location->id, request('company_location_id'))) ? 'selected' : '' }}>
                                                                {{ $location->name }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-2">
                                                <div class="form-group mb-0">
                                                    <label>Sub Location:</label>
                                                    <select name="sub_location_id[]" id="sub_location_id" multiple
                                                        class="form-control selectWithoutAjax" data-placeholder="Select Sub Location">
                                                        @foreach ($subLocations as $subLocation)
                                                            <option value="{{ $subLocation->id }}"
                                                                {{ is_array(request('sub_location_id')) && in_array($subLocation->id, request('sub_location_id')) ? 'selected' : '' }}>
                                                                {{ $subLocation->name }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-2">
                                                <div class="form-group mb-0">
                                                    <label>Warehouse:</label>
                                                    <select name="warehouse_id[]" id="warehouse_id"
                                                        {{ count($warehouses) == 1 ? 'disabled' : 'multiple' }}
                                                        class="form-control selectWithoutAjax">
                                                        <option value="">Select Warehouse</option>
                                                        @foreach ($warehouses as $warehouse)
                                                            <option value="{{ $warehouse->id }}"
                                                                data-location="{{ $warehouse->company_location_id }}"
                                                                {{ (is_array(request('warehouse_id')) && in_array($warehouse->id, request('warehouse_id'))) || count($warehouses) == 1 ? 'selected' : '' }}>
                                                                {{ $warehouse->name }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-2">
                                                <div class="form-group mb-0">
                                                    <label>Date Range:</label>
                                                    <input type="text" name="daterange" class="form-control"
                                                        value="{{ request('daterange', \Carbon\Carbon::now()->subMonth()->format('m/d/Y') . ' - ' . \Carbon\Carbon::now()->format('m/d/Y')) }}" />
                                                </div>
                                            </div>
                                            <div class="col-md-2">
                                                <div class="form-group mb-0">
                                                    <label>Station:</label>
                                                    <select name="station_id" id="station_id"
                                                        class="form-control selectWithoutAjax">
                                                        <option value="">Select Station</option>
                                                        @foreach ($stations as $station)
                                                            <option value="{{ $station->id }}"
                                                                {{ request('station_id') == $station->id ? 'selected' : '' }}>
                                                                {{ $station->name }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-2">
                                                <div class="form-group mb-0">
                                                    <label>Commodity:</label>
                                                    <select name="commodity_id[]" id="commodity_id" multiple
                                                        class="form-control selectWithoutAjax">
                                                        <option value="">Select Commodity</option>
                                                        @foreach ($commodities as $commodity)
                                                            <option value="{{ $commodity->id }}"
                                                                {{ is_array(request('commodity_id')) && in_array($commodity->id, request('commodity_id')) ? 'selected' : '' }}>
                                                                {{ $commodity->name }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-2">
                                                <div class="form-group mb-0">
                                                    <label>Status:</label>
                                                    @php
                                                        $defaultStatuses = ['ok', 'ts'];
                                                        $selectedStatuses = request()->has('status_id')
                                                            ? (array) request('status_id')
                                                            : $defaultStatuses;
                                                    @endphp
                                                    <select name="status_id[]" id="status_id" multiple
                                                        class="form-control selectWithoutAjax" data-placeholder="Select Status">
                                                        <option value="ok" {{ in_array('ok', $selectedStatuses) ? 'selected' : '' }}>OK</option>
                                                        <option value="ts" {{ in_array('ts', $selectedStatuses) ? 'selected' : '' }}>TS</option>
                                                        <option value="half_approved" {{ in_array('half_approved', $selectedStatuses) ? 'selected' : '' }}>Reject Half</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mt-2">
                                            <div class="form-group mb-0">
                                                <input type="submit" class="btn btn-primary" name="generatebtn" value="Generate">
                                            </div>
                                        </div>
                                        <div class="row justify-content-nd text mt-2">
                                            <input type="hidden" name="page" value="{{ request('page', 1) }}">
                                            <input type="hidden" name="per_page" value="{{ request('per_page', 25) }}">
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                        <div class="card-content">
                            <div class="card-body table-responsive" id="filteredData">
                                <table class="table m-0" id="exportableTable">
                                    <thead>
                                        <tr>
                                            <th>Product</th>
                                            <th>Sum of Loading Weight</th>
                                            <th>Sum of Net Weight</th>
                                            <th>Sum of Weight Difference</th>
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
@endsection
@section('script')
    <script>
        $(document).ready(function () {
            const allWarehouses = @json($warehouses);
            const allSubLocations = @json($subLocations);

            function selectedValues(selector) {
                let values = $(selector).val() || [];
                if (!Array.isArray(values)) {
                    values = values ? [values] : [];
                }
                return values.filter(v => v !== '' && v !== 'all').map(String);
            }

            // Sub locations of the selected locations (all when no location is selected)
            function updateSubLocationOptions() {
                let selectedLocations = selectedValues('#company_location_id');
                let $subSelect = $('#sub_location_id');
                let currentlySelected = selectedValues('#sub_location_id');

                let subLocationsToShow = allSubLocations;
                if (selectedLocations.length > 0) {
                    subLocationsToShow = allSubLocations.filter(s => selectedLocations.includes(String(s.company_location_id)));
                }

                let validSelected = currentlySelected.filter(id =>
                    subLocationsToShow.some(s => String(s.id) === id)
                );

                $subSelect.empty();
                subLocationsToShow.forEach(s => {
                    $subSelect.append(new Option(s.name, s.id, false, validSelected.includes(String(s.id))));
                });

                $subSelect.val(validSelected).trigger('change.select2');
            }

            // Warehouses of the selected sub locations; when no sub location is selected, all warehouses of the selected locations
            function updateWarehouseOptions() {
                let selectedLocations = selectedValues('#company_location_id');
                let selectedSubLocations = selectedValues('#sub_location_id');

                let $warehouseSelect = $('#warehouse_id');
                let currentlySelectedWarehouses = $warehouseSelect.val() || [];
                if (!Array.isArray(currentlySelectedWarehouses)) {
                    currentlySelectedWarehouses = currentlySelectedWarehouses ? [currentlySelectedWarehouses] : [];
                }

                let warehousesToShow = allWarehouses;
                if (selectedSubLocations.length > 0) {
                    warehousesToShow = allWarehouses.filter(w => selectedSubLocations.includes(String(w.arrival_location_id)));
                } else if (selectedLocations.length > 0) {
                    warehousesToShow = allWarehouses.filter(w => selectedLocations.includes(String(w.company_location_id)));
                }

                let validSelected = currentlySelectedWarehouses.filter(id => 
                    warehousesToShow.some(w => String(w.id) === String(id))
                );

                $warehouseSelect.empty();
                $warehouseSelect.append(new Option('Select Warehouse', ''));
                warehousesToShow.forEach(w => {
                    let isSelected = validSelected.includes(String(w.id));
                    $warehouseSelect.append(new Option(w.name, w.id, false, isSelected));
                });

                $warehouseSelect.val(validSelected).trigger('change.select2');
            }

            // If only 1 location is available, auto-select it
            let $locSelect = $('#company_location_id');
            let $locValidOptions = $locSelect.find('option').filter(function () {
                return $(this).val() !== '';
            });
            if ($locValidOptions.length === 1) {
                let singleId = $locValidOptions.first().val();
                $locSelect.val([singleId]);
            }

            // Ensure status defaults to OK and TS if not already set
            let $statusSelect = $('#status_id');
            if (!$statusSelect.val() || $statusSelect.val().length === 0) {
                $statusSelect.val(['ok', 'ts']);
            }

            $('.selectWithoutAjax').select2();

            $('#company_location_id').on('change', function () {
                updateSubLocationOptions();
                updateWarehouseOptions();
            });

            $('#sub_location_id').on('change', function () {
                updateWarehouseOptions();
            });

            if (selectedValues('#company_location_id').length > 0 || selectedValues('#sub_location_id').length > 0) {
                updateSubLocationOptions();
                updateWarehouseOptions();
            }

            const runFilter = filterationCommon_withbtn(
                `{{ route('reports.arrival.get.arrival-weight') }}`
            );

            $('#filterForm').on('submit', function (e) {
                e.preventDefault();
                runFilter();
            });

            runFilter();
        });
    </script>
@endsection
