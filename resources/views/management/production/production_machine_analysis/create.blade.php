<form action="{{ route('production-machine-analysis.store') }}" method="POST" id="ajaxSubmit" autocomplete="off">
    @csrf
    <input type="hidden" id="listRefresh" value="{{ route('get.production-machine-analysis') }}">
    <div class="row form-mar">
        <!-- Header Information -->
        <div class="col-md-12">
            <h6 class="header-heading-sepration">Machine Analysis Information</h6>
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Date:</label>
                        <input type="date" name="date" class="form-control" value="{{ date('Y-m-d') }}" readonly>
                    </div>
                </div>
                <div class="col-md-5">
                    <div class="form-group">
                        <label>Analysis Request: <span class="text-danger">*</span></label>
                        <select name="analysis_request_id" id="analysis_request_id" class="form-control select2" required>
                            <option value="">Select Analysis Request</option>
                            @foreach($analysisRequests as $req)
                                <option value="{{ $req->id }}"
                                    data-company-location-id="{{ $req->company_location_id }}"
                                    data-company-location-name="{{ $req->companyLocation?->name }}"
                                    data-arrival-location-id="{{ $req->arrival_location_id }}"
                                    data-arrival-location-name="{{ $req->arrivalLocation?->name }}"
                                    data-plant-id="{{ $req->plant_id }}"
                                    data-plant-name="{{ $req->plant?->name }}"
                                    @selected(isset($analysisRequest) && $analysisRequest->id == $req->id)>
                                    {{ $req->request_no }} - {{ $req->companyLocation?->name }} (Plant: {{ $req->plant?->name ?? 'N/A' }}) - {{ $req->request_date->format('d-m-Y') }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Machine: <span class="text-danger">*</span></label>
                        <select name="production_machine_id" id="production_machine_id" class="form-control select2" required>
                            <option value="">Select Machine</option>
                        </select>
                    </div>
                </div>

                <!-- Selected Request Details Summary -->
                <div id="request_details_box" class="col-12 mb-2 {{ isset($analysisRequest) && $analysisRequest ? '' : 'd-none' }}">
                    <div class="alert alert-light border py-2 px-3 mb-0 d-flex flex-wrap align-items-center justify-content-between" style="background: #f8fafc; border-radius: 6px;">
                        <div>
                            <i class="ft-map-pin text-primary mr-1"></i> Location: <strong id="req_disp_location">{{ $analysisRequest->companyLocation?->name ?? '' }}</strong> &bull;
                            <i class="ft-navigation text-info mr-1 ml-2"></i> Arrival: <span id="req_disp_arrival">{{ $analysisRequest->arrivalLocation?->name ?? '' }}</span> &bull;
                            <i class="ft-cpu text-success mr-1 ml-2"></i> Plant: <span id="req_disp_plant">{{ $analysisRequest->plant?->name ?? '' }}</span>
                        </div>
                    </div>
                </div>

                <!-- Hidden Location, Arrival Location, Plant dropdowns -->
                <div class="d-none">
                    <select name="company_location_id" id="company_location_id">
                        <option value="">Select Location</option>
                        @foreach($companyLocations as $location)
                            <option value="{{ $location->id }}" @selected($preSelectedLocationId == $location->id)>
                                {{ $location->name }}
                            </option>
                        @endforeach
                    </select>
                    <select name="arrival_location_id" id="arrival_location_id">
                        <option value="">Select Arrival Location</option>
                        @if(isset($initialArrivalLocations))
                            @foreach($initialArrivalLocations as $arr)
                                <option value="{{ $arr->id }}" @selected(isset($analysisRequest) && $analysisRequest->arrival_location_id == $arr->id)>
                                    {{ $arr->name }}
                                </option>
                            @endforeach
                        @endif
                    </select>
                    <select name="plant_id" id="plant_id">
                        <option value="">Select Plant</option>
                        @if(isset($initialPlants))
                            @foreach($initialPlants as $pl)
                                <option value="{{ $pl->id }}" @selected(isset($analysisRequest) && $analysisRequest->plant_id == $pl->id)>
                                    {{ $pl->name }}
                                </option>
                            @endforeach
                        @endif
                    </select>
                </div>
            </div>
        </div>

        <!-- Line Items Section -->
        <div class="col-md-12 mt-4">
            <h6 class="header-heading-sepration d-flex justify-content-between align-items-center">
                Line Items
                <div>
                    <button type="button" class="btn btn-sm btn-success" id="addLineItem">Add Row</button>
                </div>
            </h6>
            <div class="table-responsive" style="overflow-x: auto;">
                <table class="table table-bordered" id="lineItemsTable" style="min-width: 1200px;">
                    <thead>
                        <tr id="headerRow">
                            <th style="min-width: 150px;">Time</th>
                            <th style="min-width: 150px;">Unit</th>
                            @foreach($productSlabTypes as $productSlabType)
                                <th class="dynamic-col" data-slab-id="{{ $productSlabType->id }}" style="min-width: 150px;">{{ $productSlabType->name }} {{ $productSlabType->qc_symbol }}</th>
                            @endforeach
                            <th style="width: 50px;">Action</th>
                        </tr>
                    </thead>
                    <tbody id="lineItemsBody">
                        <tr>
                            <td>
                                <input type="time" name="items[0][time]" class="form-control" value="{{ date('H:i') }}">
                            </td>
                            <td>
                                <select name="items[0][unit_id]" class="form-control select2">
                                    <option value="">Select Unit</option>
                                    @foreach($units as $unit)
                                        <option value="{{ $unit->id }}">{{ $unit->name }}</option>
                                    @endforeach
                                </select>
                            </td>
                            @foreach($productSlabTypes as $productSlabType)
                                <td class="dynamic-col">
                                    <input type="text" name="items[0][params][{{ $productSlabType->id }}]" class="form-control">
                                </td>
                            @endforeach
                            <td>
                                <button type="button" class="btn btn-sm btn-danger remove-row"><i class="ft-trash"></i></button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Remarks Section -->
        <div class="col-md-12 mt-4">
            <div class="form-group">
                <label>Remarks:</label>
                <textarea name="remarks" class="form-control" rows="4" placeholder="Enter Remarks"></textarea>
            </div>
        </div>
    </div>

    <div class="row bottom-button-bar">
        <div class="col-12 text-right">
            <a type="button" class="btn btn-danger modal-sidebar-close closebutton">Close</a>
            <button type="submit" class="btn btn-primary submitbutton">Save Analysis</button>
        </div>
    </div>
</form>

<script>
    $(document).ready(function() {
        // Initialize Select2
        function initSelect2(el) {
            if ($.fn.select2) {
                $(el).select2({ width: '100%' });
            }
        }
        initSelect2('.select2');

        // Trigger change if only one location is pre-selected
        if ($('#company_location_id').val()) {
            $('#company_location_id').trigger('change');
        }

        function addRow() {
            let rowCount = $('#lineItemsBody tr').length;
            let currentTime = new Date().getHours().toString().padStart(2, '0') + ':' + new Date().getMinutes().toString().padStart(2, '0');
            
            let newRow = `<tr>
                <td><input type="time" name="items[${rowCount}][time]" class="form-control" value="${currentTime}"></td>
                <td>
                    <select name="items[${rowCount}][unit_id]" class="form-control select2-row">
                        <option value="">Select Unit</option>
                        @foreach($units as $unit)
                            <option value="{{ $unit->id }}">{{ $unit->name }}</option>
                        @endforeach
                    </select>
                </td>`;
            
            $('#headerRow th.dynamic-col').each(function() {
                let slabId = $(this).data('slab-id');
                newRow += `<td class="dynamic-col"><input type="text" name="items[${rowCount}][params][${slabId}]" class="form-control"></td>`;
            });
            
            newRow += `<td><button type="button" class="btn btn-sm btn-danger remove-row"><i class="ft-trash"></i></button></td></tr>`;
            
            let $row = $(newRow);
            $('#lineItemsBody').append($row);
            initSelect2($row.find('.select2-row'));
        }

        // Add Row Button
        $('#addLineItem').on('click', function() {
            addRow();
        });

        // Remove Row
        $(document).on('click', '.remove-row', function() {
            if ($('#lineItemsBody tr').length > 1) {
                $(this).closest('tr').remove();
            } else {
                alert('At least one row is required.');
            }
        });

        function loadMachines(arrivalId, plantId, selectedMachineId = null) {
            let $machSelect = $('#production_machine_id');
            $machSelect.html('<option value="">Select Machine</option>').prop('disabled', true);
            if (arrivalId && plantId) {
                $machSelect.html('<option value="">Loading...</option>');
                let url = '{{ route("production-machine-analysis.get-machines", [":arrivalId", ":plantId"]) }}';
                url = url.replace(':arrivalId', arrivalId).replace(':plantId', plantId);
                $.get(url, function(data) {
                    $machSelect.html('<option value="">Select Machine</option>').prop('disabled', false);
                    $.each(data, function(i, item) {
                        let isSel = (selectedMachineId && selectedMachineId == item.id) ? 'selected' : '';
                        $machSelect.append(`<option value="${item.id}" ${isSel}>${item.name}</option>`);
                    });
                    $machSelect.trigger('change');
                });
            }
        }

        // Handle Analysis Request Selection
        $('#analysis_request_id').on('change', function() {
            let $opt = $(this).find(':selected');
            let compLocId = $opt.data('company-location-id');
            let arrivalLocId = $opt.data('arrival-location-id');
            let plantId = $opt.data('plant-id');
            let compLocName = $opt.data('company-location-name');
            let arrivalLocName = $opt.data('arrival-location-name');
            let plantName = $opt.data('plant-name');

            if (compLocId) {
                $('#company_location_id').val(compLocId);
                $('#arrival_location_id').val(arrivalLocId);
                $('#plant_id').val(plantId);

                $('#req_disp_location').text(compLocName || '');
                $('#req_disp_arrival').text(arrivalLocName || '');
                $('#req_disp_plant').text(plantName || '');
                $('#request_details_box').removeClass('d-none');

                loadMachines(arrivalLocId, plantId);
            } else {
                $('#company_location_id').val('');
                $('#arrival_location_id').val('');
                $('#plant_id').val('');
                $('#request_details_box').addClass('d-none');
                $('#production_machine_id').html('<option value="">Select Machine</option>').prop('disabled', true).trigger('change');
            }
        });

        if ($('#analysis_request_id').val()) {
            $('#analysis_request_id').trigger('change');
        }
    });
</script>
