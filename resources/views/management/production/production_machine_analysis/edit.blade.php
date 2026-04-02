<form action="{{ route('production-machine-analysis.update', $item->id) }}" method="POST" id="ajaxSubmit" autocomplete="off">
    @csrf
    @method('PUT')
    <input type="hidden" id="listRefresh" value="{{ route('get.production-machine-analysis') }}">
    <div class="row form-mar">
        <!-- Header Information -->
        <div class="col-md-12">
            <h6 class="header-heading-sepration">Edit Machine Analysis Information</h6>
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Date:</label>
                        <input type="date" name="date" class="form-control" value="{{ $item->analysis_date }}" readonly>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Company Location:</label>
                        <select name="company_location_id" id="company_location_id" class="form-control select2" required>
                            <option value="">Select Location</option>
                            @foreach($companyLocations as $location)
                                <option value="{{ $location->id }}" @selected($item->company_location_id == $location->id)>
                                    {{ $location->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Arrival Location:</label>
                        <select name="arrival_location_id" id="arrival_location_id" class="form-control select2" required>
                            <option value="">Select Arrival Location</option>
                            @foreach($arrivalLocations as $loc)
                                <option value="{{ $loc->id }}" @selected($item->arrival_location_id == $loc->id)>
                                    {{ $loc->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Plant:</label>
                        <select name="plant_id" id="plant_id" class="form-control select2" required>
                            <option value="">Select Plant</option>
                            @foreach($plants as $pl)
                                <option value="{{ $pl->id }}" @selected($item->plant_id == $pl->id)>
                                    {{ $pl->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Machine:</label>
                        <select name="production_machine_id" id="production_machine_id" class="form-control select2" required>
                            <option value="">Select Machine</option>
                            @foreach($machines as $m)
                                <option value="{{ $m->id }}" @selected($item->production_machine_id == $m->id)>
                                    {{ $m->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
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
                        @foreach($item->items as $index => $analysisItem)
                            <tr>
                                <td>
                                    <input type="time" name="items[{{ $index }}][time]" class="form-control" value="{{ \Carbon\Carbon::parse($analysisItem->analysis_time)->format('H:i') }}">
                                </td>
                                <td>
                                    <select name="items[{{ $index }}][unit_id]" class="form-control select2">
                                        <option value="">Select Unit</option>
                                        @foreach($units as $unit)
                                            <option value="{{ $unit->id }}" @selected($analysisItem->unit_id == $unit->id)>
                                                {{ $unit->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </td>
                                @foreach($productSlabTypes as $productSlabType)
                                    @php
                                        $slabValue = $analysisItem->slabs->where('slab_type_id', $productSlabType->id)->first();
                                    @endphp
                                    <td class="dynamic-col">
                                        <input type="text" name="items[{{ $index }}][params][{{ $productSlabType->id }}]" 
                                               class="form-control" value="{{ $slabValue->analysis_value ?? '' }}">
                                    </td>
                                @endforeach
                                <td>
                                    <button type="button" class="btn btn-sm btn-danger remove-row"><i class="ft-trash"></i></button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Remarks Section -->
        <div class="col-md-12 mt-4">
            <div class="form-group">
                <label>Remarks:</label>
                <textarea name="remarks" class="form-control" rows="4" placeholder="Enter Remarks">{{ $item->remarks }}</textarea>
            </div>
        </div>
    </div>

    <div class="row bottom-button-bar">
        <div class="col-12 text-right">
            <a type="button" class="btn btn-danger modal-sidebar-close closebutton">Close</a>
            <button type="submit" class="btn btn-primary submitbutton">Update Analysis</button>
        </div>
    </div>
</form>

<script>
    $(document).ready(function() {
        function initSelect2(el) {
            if ($.fn.select2) {
                $(el).select2({ width: '100%' });
            }
        }
        initSelect2('.select2');

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

        $('#addLineItem').on('click', function() {
            addRow();
        });

        $(document).on('click', '.remove-row', function() {
            if ($('#lineItemsBody tr').length > 1) {
                $(this).closest('tr').remove();
            } else {
                alert('At least one row is required.');
            }
        });

        // Cascading Dropdowns
        $('#company_location_id').on('change', function() {
            let companyId = $(this).val();
            $('#arrival_location_id').html('<option value="">Select Arrival Location</option>').trigger('change');
            $('#plant_id').html('<option value="">Select Plant</option>').trigger('change');
            $('#production_machine_id').html('<option value="">Select Machine</option>').trigger('change');
            
            if (companyId) {
                $('#arrival_location_id').html('<option value="">Loading...</option>').trigger('change');
                let url = '{{ route("production-machine-analysis.get-arrival-locations", ":id") }}';
                url = url.replace(':id', companyId);
                $.get(url, function(data) {
                    $('#arrival_location_id').html('<option value="">Select Arrival Location</option>');
                    $.each(data, function(i, item) {
                        $('#arrival_location_id').append(`<option value="${item.id}">${item.name}</option>`);
                    });
                    $('#arrival_location_id').trigger('change');
                });
            }
        });

        $('#arrival_location_id').on('change', function() {
            let companyId = $('#company_location_id').val();
            let arrivalId = $(this).val();
            $('#plant_id').html('<option value="">Select Plant</option>').trigger('change');
            $('#production_machine_id').html('<option value="">Select Machine</option>').trigger('change');
            
            if (companyId && arrivalId) {
                $('#plant_id').html('<option value="">Loading...</option>').trigger('change');
                let url = '{{ route("production-machine-analysis.get-plants", [":companyId", ":arrivalId"]) }}';
                url = url.replace(':companyId', companyId).replace(':arrivalId', arrivalId);
                $.get(url, function(data) {
                    $('#plant_id').html('<option value="">Select Plant</option>');
                    $.each(data, function(i, item) {
                        $('#plant_id').append(`<option value="${item.id}">${item.name}</option>`);
                    });
                    $('#plant_id').trigger('change');
                });
            }
        });

        $('#plant_id').on('change', function() {
            let arrivalId = $('#arrival_location_id').val();
            let plantId = $(this).val();
            $('#production_machine_id').html('<option value="">Select Machine</option>').trigger('change');
            
            if (arrivalId && plantId) {
                $('#production_machine_id').html('<option value="">Loading...</option>').trigger('change');
                let url = '{{ route("production-machine-analysis.get-machines", [":arrivalId", ":plantId"]) }}';
                url = url.replace(':arrivalId', arrivalId).replace(':plantId', plantId);
                $.get(url, function(data) {
                    $('#production_machine_id').html('<option value="">Select Machine</option>');
                    $.each(data, function(i, item) {
                        $('#production_machine_id').append(`<option value="${item.id}">${item.name}</option>`);
                    });
                    $('#production_machine_id').trigger('change');
                });
            }
        });
    });
</script>
